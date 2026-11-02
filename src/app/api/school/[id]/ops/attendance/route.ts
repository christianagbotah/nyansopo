import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendAutomated } from "@/lib/sms";

// ── Attendance ops (iEduPro CI3 register parity) ──────────────
//    GET  ?action=register&classId=&date=YYYY-MM-DD   — daily register payload
//         (shape-compatible with the legacy /attendance resource the Register
//          tab used to read, + excusedReason on each learner row)
//         ?action=analytics&classroomId=&from=&to=    — summary + per-learner
//          aggregates + daily trend over a date range
//         ?action=monthly&month=YYYY-MM&classroomId=  — day-by-day status map
//    POST action=save-register — find-or-create session for (classroom, date),
//         replace records, offline-queue idempotency via the SYNC:clientId
//         marker (same pattern as the legacy resource + /api/sync).

/** Free-string status column — the CI3 register set (legacy SICK kept). */
const VALID_STATUSES = new Set(["PRESENT", "LATE", "ABSENT", "SICK", "SICK_HOME", "SICK_CLINIC", "BUSY", "EXCUSED"]);
const DATE_RE = /^\d{4}-\d{2}-\d{2}$/;

// ── GET /api/school/[id]/ops/attendance ───────────────────────
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["attendance.view"] });
  if (!auth.ok) return auth.res;
  const url = new URL(req.url);
  const q = (k: string) => url.searchParams.get(k) ?? undefined;
  const action = q("action") ?? "register";

  // ── analytics: per-learner aggregates over a date range ──
  if (action === "analytics") {
    const classroomId = q("classroomId");
    const to = q("to") ?? new Date().toISOString().slice(0, 10);
    const from = q("from") ?? new Date(new Date(to).getTime() - 29 * 864e5).toISOString().slice(0, 10);
    if (!DATE_RE.test(from) || !DATE_RE.test(to)) return NextResponse.json({ error: "from/to must be YYYY-MM-DD" }, { status: 400 });

    const [classrooms, sessions, enrollments] = await Promise.all([
      db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" }, select: { id: true, name: true } }),
      db.attendanceSession.findMany({
        where: { schoolId, date: { gte: new Date(from), lte: new Date(`${to}T23:59:59.999Z`) }, ...(classroomId ? { classroomId } : {}) },
        include: { records: { include: { student: { select: { firstName: true, lastName: true } } } }, classroom: { select: { name: true } } },
        orderBy: { date: "asc" },
      }),
      db.enrollment.findMany({
        where: { schoolId, status: "ACTIVE", ...(classroomId ? { classroomId } : {}) },
        include: { student: true, classroom: { select: { name: true } } },
        orderBy: { admissionDate: "asc" },
      }),
    ]);

    // roster rows — first active enrollment wins the classroom label
    const rowsById = new Map<string, {
      studentId: string; name: string; classroom: string;
      present: number; absent: number; late: number; sickHome: number; sickClinic: number; sickBay: number; busy: number; excused: number; marked: number;
    }>();
    for (const e of enrollments) {
      if (rowsById.has(e.studentId)) continue;
      rowsById.set(e.studentId, {
        studentId: e.studentId, name: `${e.student.firstName} ${e.student.lastName}`, classroom: e.classroom.name,
        present: 0, absent: 0, late: 0, sickHome: 0, sickClinic: 0, sickBay: 0, busy: 0, excused: 0, marked: 0,
      });
    }

    const summary = { sessions: sessions.length, marked: 0, present: 0, late: 0, absent: 0, sickBay: 0, sickHome: 0, sickClinic: 0, busy: 0, excused: 0 };
    const trendByDate = new Map<string, { present: number; total: number }>();
    for (const s of sessions) {
      const dKey = s.date.toISOString().slice(0, 10);
      const t = trendByDate.get(dKey) ?? { present: 0, total: 0 };
      for (const r of s.records) {
        summary.marked++; t.total++;
        if (r.status === "PRESENT") { summary.present++; t.present++; }
        else if (r.status === "LATE") { summary.late++; t.present++; }
        else if (r.status === "ABSENT") summary.absent++;
        else if (r.status === "SICK") summary.sickBay++;
        else if (r.status === "SICK_HOME") summary.sickHome++;
        else if (r.status === "SICK_CLINIC") summary.sickClinic++;
        else if (r.status === "BUSY") summary.busy++;
        else if (r.status === "EXCUSED") summary.excused++;
        // rows: include learners that hold records but left the roll since
        const row = rowsById.get(r.studentId) ?? (() => {
          const fallback = {
            studentId: r.studentId, name: `${r.student.firstName} ${r.student.lastName}`, classroom: s.classroom.name,
            present: 0, absent: 0, late: 0, sickHome: 0, sickClinic: 0, sickBay: 0, busy: 0, excused: 0, marked: 0,
          };
          rowsById.set(r.studentId, fallback);
          return fallback;
        })();
        row.marked++;
        if (r.status === "PRESENT") row.present++;
        else if (r.status === "LATE") row.late++;
        else if (r.status === "ABSENT") row.absent++;
        else if (r.status === "SICK") row.sickBay++;
        else if (r.status === "SICK_HOME") row.sickHome++;
        else if (r.status === "SICK_CLINIC") row.sickClinic++;
        else if (r.status === "BUSY") row.busy++;
        else if (r.status === "EXCUSED") row.excused++;
      }
      trendByDate.set(dKey, t);
    }

    const rows = Array.from(rowsById.values())
      .map((r) => ({ ...r, rate: r.marked ? Math.round(((r.present + r.late) / r.marked) * 100) : null }))
      .sort((a, b) => a.name.localeCompare(b.name));
    const trend = Array.from(trendByDate.entries())
      .map(([date, v]) => ({ date, rate: v.total ? Math.round((v.present / v.total) * 100) : 0, present: v.present, total: v.total }));

    return NextResponse.json({
      classrooms,
      range: { from, to },
      summary: {
        ...summary,
        sick: summary.sickBay + summary.sickHome + summary.sickClinic,
        presentPct: summary.marked ? Math.round((summary.present / summary.marked) * 100) : 0,
        absentPct: summary.marked ? Math.round((summary.absent / summary.marked) * 100) : 0,
        attendanceRate: summary.marked ? Math.round(((summary.present + summary.late) / summary.marked) * 100) : 0,
      },
      rows,
      trend,
    });
  }

  // ── monthly: day-by-day status map for one classroom ──
  if (action === "monthly") {
    const month = q("month") ?? new Date().toISOString().slice(0, 7);
    if (!/^\d{4}-\d{2}$/.test(month)) return NextResponse.json({ error: "month must be YYYY-MM" }, { status: 400 });
    const classroomId = q("classroomId");
    const [y, m] = month.split("-").map(Number);
    const start = new Date(Date.UTC(y, m - 1, 1));
    const end = new Date(Date.UTC(y, m, 1)); // exclusive
    const daysInMonth = new Date(Date.UTC(y, m, 0)).getUTCDate();

    const classrooms = await db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" }, select: { id: true, name: true } });
    const cls = classrooms.find((c) => c.id === classroomId);
    if (!cls) return NextResponse.json({ classrooms, month, daysInMonth, classroomId: null, classroomName: null, students: [] });

    const [sessions, enrolled] = await Promise.all([
      db.attendanceSession.findMany({ where: { schoolId, classroomId: cls.id, date: { gte: start, lt: end } }, include: { records: true }, orderBy: { date: "asc" } }),
      db.enrollment.findMany({ where: { classroomId: cls.id, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" } }),
    ]);

    // every learner on roll gets a row (empty grid = nothing marked yet)
    const byStudent = new Map<string, { name: string; rollNo: number | null; days: Record<string, { status: string; reason?: string }> }>();
    for (const e of enrolled) {
      byStudent.set(e.studentId, { name: `${e.student.firstName} ${e.student.lastName}`, rollNo: e.rollNo, days: {} });
    }
    for (const s of sessions) {
      const day = String(s.date.getUTCDate()); // sessions are stored at UTC midnight
      for (const r of s.records) {
        const row = byStudent.get(r.studentId) ?? (() => {
          const fresh = { name: "", rollNo: null, days: {} as Record<string, { status: string; reason?: string }> };
          byStudent.set(r.studentId, fresh);
          return fresh;
        })();
        row.days[day] = { status: r.status, ...(r.status === "EXCUSED" && r.excusedReason ? { reason: r.excusedReason } : {}) };
      }
    }

    const nameById = new Map(enrolled.map((e) => [e.studentId, `${e.student.firstName} ${e.student.lastName}`]));
    return NextResponse.json({
      classrooms, month, daysInMonth, classroomId: cls.id, classroomName: cls.name,
      students: Array.from(byStudent.entries()).map(([id, v]) => ({ id, name: v.name || nameById.get(id) || id, rollNo: v.rollNo, days: v.days })),
    });
  }

  // ── register (default): legacy /attendance GET payload shape ──
  const classId = q("classId") ?? q("classroomId");
  const dateStr = q("date") ?? new Date().toISOString().slice(0, 10);
  const date = new Date(dateStr); // date-only strings parse as UTC midnight, matching session writes
  const classrooms = await db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
  if (!classId) return NextResponse.json({ classrooms: classrooms.map((c) => ({ id: c.id, name: c.name, grade: c.gradeLevel, stage: c.stageLabel })), session: null, date: dateStr, termId: null, students: [] });

  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
  const session = await db.attendanceSession.findFirst({ where: { classroomId: classId, date }, include: { records: { include: { student: true } } } });
  if (!session) {
    const enrolled = await db.enrollment.findMany({ where: { classroomId: classId, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" } });
    return NextResponse.json({
      classrooms: classrooms.map((c) => ({ id: c.id, name: c.name, grade: c.gradeLevel, stage: c.stageLabel })),
      session: null, date: dateStr, termId: term?.id,
      students: enrolled.map((e) => ({ id: e.student.id, name: `${e.student.firstName} ${e.student.lastName}`, rollNo: e.rollNo, status: null })),
    });
  }
  return NextResponse.json({
    classrooms: classrooms.map((c) => ({ id: c.id, name: c.name, grade: c.gradeLevel, stage: c.stageLabel })),
    session: { id: session.id, takenByName: session.takenByName },
    date: dateStr, termId: session.termId,
    students: session.records
      .map((r) => ({ id: r.student.id, name: `${r.student.firstName} ${r.student.lastName}`, rollNo: null, status: r.status, markedOffline: r.markedOffline, excusedReason: r.excusedReason }))
      .sort((a, b) => a.name.localeCompare(b.name)),
  });
}

// ── POST /api/school/[id]/ops/attendance ──────────────────────
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["attendance.take"] });
  if (!auth.ok) return auth.res;
  const { user } = auth;

  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  if (action !== "save-register") return NextResponse.json({ error: "Unknown action" }, { status: 404 });

  const { classroomId, date, records, offline, clientId } = body;
  if (!classroomId || typeof classroomId !== "string") return NextResponse.json({ error: "classroomId is required" }, { status: 400 });
  if (typeof date !== "string" || !DATE_RE.test(date)) return NextResponse.json({ error: "date must be YYYY-MM-DD" }, { status: 400 });
  if (!Array.isArray(records) || !records.length) return NextResponse.json({ error: "records[] is required" }, { status: 400 });

  const cls = await db.classroom.findFirst({ where: { id: classroomId, schoolId } });
  if (!cls) return NextResponse.json({ error: "Unknown classroom for this school" }, { status: 400 });

  // validate statuses + EXCUSED reasons; dedupe per learner (last mark wins)
  const byStudent = new Map<string, { studentId: string; status: string; excusedReason: string | null }>();
  for (const r of records) {
    if (!r || typeof r.studentId !== "string") return NextResponse.json({ error: "records[].studentId is required" }, { status: 400 });
    if (typeof r.status !== "string" || !VALID_STATUSES.has(r.status)) {
      return NextResponse.json({ error: `Unknown status "${r.status}"` }, { status: 400 });
    }
    if (r.status === "EXCUSED" && (typeof r.excusedReason !== "string" || !r.excusedReason.trim())) {
      return NextResponse.json({ error: "Excused requires a reason — capture why the learner is excused" }, { status: 400 });
    }
    byStudent.set(r.studentId, { studentId: r.studentId, status: r.status, excusedReason: r.status === "EXCUSED" ? r.excusedReason.trim() : null });
  }
  const finalRecords = Array.from(byStudent.values());

  // offline-queue idempotency (SYNC: clientId marker, same as the legacy resource)
  if (clientId) {
    const existing = await db.attendanceSession.findFirst({ where: { classroomId, date: new Date(date), takenByName: { contains: `SYNC:${clientId}` } } });
    if (existing) return NextResponse.json({ session: { id: existing.id }, duplicate: true, saved: 0 });
  }

  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
  if (!term) return NextResponse.json({ error: "No current term for this school" }, { status: 400 });

  // find-or-create the session for (classroom, date), then replace its records
  let session = await db.attendanceSession.findFirst({ where: { classroomId, date: new Date(date) } });
  if (session) await db.attendanceRecord.deleteMany({ where: { sessionId: session.id } });
  else session = await db.attendanceSession.create({ data: { classroomId, schoolId, termId: term.id, date: new Date(date), takenByName: `${user.fullName}${clientId ? ` (SYNC:${clientId})` : ""}` } });
  for (const r of finalRecords) {
    await db.attendanceRecord.create({ data: { sessionId: session.id, studentId: r.studentId, status: r.status, excusedReason: r.excusedReason, markedOffline: !!offline } });
  }

  // auto absence alerts to guardians (legacy semantics: online saves, ABSENT
  // only, deduplicated per learner + day)
  const smsSetting = await db.smsSetting.findUnique({ where: { schoolId } });
  if (smsSetting?.autoAbsenceAlerts && !offline) {
    const absent = finalRecords.filter((r) => r.status === "ABSENT");
    for (const r of absent) {
      const already = await db.smsMessage.findFirst({ where: { schoolId, studentId: r.studentId, category: "ATTENDANCE", sentAt: { gte: new Date(new Date(date).setHours(0, 0, 0, 0)) } } });
      if (already) continue;
      const st = await db.student.findUnique({ where: { id: r.studentId } });
      if (!st) continue;
      await sendAutomated({
        db, schoolId, trigger: "ABSENCE_MARKED",
        defaultBody: `${st.firstName} was marked ABSENT today (${new Date(date).toLocaleDateString("en-GB")}). Please contact the school if this is unexpected.`,
        vars: { student: st.firstName, date: new Date(date).toLocaleDateString("en-GB") },
        studentId: st.id, recipientName: st.guardianName, recipientPhone: st.guardianPhone, sentByName: user.fullName,
      });
    }
  }

  return NextResponse.json({ session: { id: session.id }, saved: finalRecords.length });
}

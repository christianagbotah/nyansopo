import { NextRequest, NextResponse } from "next/server";
import { sortClassrooms } from "@/lib/classes";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendAutomated, smsEventEnabled } from "@/lib/sms";
import { applyAttendanceChargeRule, applyTransportUsage, collectDailyItems, resolveTransportFare, effectivePrice, TRANSPORT_USAGES, type TransportUsage } from "@/lib/daily-billing";
import { canAny } from "@/lib/rbac";

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

    const classrooms =sortClassrooms(await db.classroom.findMany({ where: { schoolId }, select: {  id: true, name: true, gradeLevel: true } }));
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
  const classrooms =sortClassrooms(await db.classroom.findMany({ where: { schoolId }, }));
  if (!classId) return NextResponse.json({ classrooms: classrooms.map((c) => ({ id: c.id, name: c.name, grade: c.gradeLevel, stage: c.stageLabel })), session: null, date: dateStr, termId: null, students: [] });

  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
  const session = await db.attendanceSession.findFirst({ where: { classroomId: classId, date }, include: { records: { include: { student: true } } } });

  // CI3 mark page shape: the FULL class roll — enrolled learners get their
  // persisted status (null when unmarked); records without an enrollment row
  // (left the roll) are still shown so their marks are not lost.
  const enrolled = await db.enrollment.findMany({ where: { classroomId: classId, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" } });
  const byStudent = new Map<string, { id: string; name: string; rollNo: number | null; status: string | null; markedOffline?: boolean; excusedReason?: string | null }>();
  for (const e of enrolled) {
    byStudent.set(e.student.id, { id: e.student.id, name: `${e.student.firstName} ${e.student.lastName}`, rollNo: e.rollNo, status: null });
  }
  if (session) {
    for (const r of session.records) {
      byStudent.set(r.studentId, {
        id: r.studentId,
        name: `${r.student.firstName} ${r.student.lastName}`,
        rollNo: byStudent.get(r.studentId)?.rollNo ?? null,
        status: r.status, markedOffline: r.markedOffline, excusedReason: r.excusedReason,
      });
    }
  }
  const students = Array.from(byStudent.values()).sort((a, b) => a.name.localeCompare(b.name));

  /* fee collection block on the register (CI3 save_attendance_and_fees):
     per-learner item state + transport fare, only when the session may view
     daily services — the register UI gates the inputs on daily.collect. */
  let fees: null | {
    studentId: string;
    items: { serviceId: string; name: string; category: string; rateCents: number; arrearsCents: number; prepaidCents: number; optional: boolean; optedOut: boolean; optedIn: boolean; weekly: boolean; weeklyBilled: boolean; chargedToday: boolean }[];
    transport: { routeName: string; stopName: string | null; onewayCents: number } | null;
  }[] = null;
  if (canAny(auth.user.permissions, ["daily.view"])) {
    const day = new Date(date); day.setHours(0, 0, 0, 0);
    const dayEnd = new Date(day); dayEnd.setHours(23, 59, 59, 999);
    const weekStart = new Date(day); weekStart.setDate(day.getDate() - ((day.getDay() + 6) % 7));
    const weekEnd = new Date(weekStart); weekEnd.setDate(weekStart.getDate() + 7); weekEnd.setMilliseconds(-1);
    const ids = students.map((s) => s.id);
    const enrollRows = await db.dailyServiceEnrollment.findMany({
      where: { studentId: { in: ids }, active: true, service: { schoolId, active: true, category: { not: "TRANSPORT" } } },
      include: { service: { include: { classRates: true } } },
    });
    const exceptions = await db.dailyMealException.findMany({ where: { studentId: { in: ids }, date: { gte: day, lte: dayEnd }, status: "APPROVED" } });
    const owedRows = await db.dailyCharge.findMany({ where: { studentId: { in: ids }, status: "OWED" } });
    const weekCharges = await db.dailyCharge.findMany({ where: { studentId: { in: ids }, date: { gte: weekStart, lte: weekEnd } }, include: { service: true } });
    const todayCharges = weekCharges.filter((c) => c.date >= day && c.date <= dayEnd);
    const balances = await db.serviceBalance.findMany({ where: { studentId: { in: ids } } });
    const fares = await Promise.all(ids.map((sid) => resolveTransportFare(db, sid)));

    fees = ids.map((sid, i) => {
      const items = enrollRows
        .filter((e) => e.studentId === sid)
        .map((e) => {
          const svc = e.service;
          const classRate = svc.classRates.find((r) => r.classroomId === classId)?.priceCents ?? null;
          const optedOut = exceptions.some((x) => x.studentId === sid && x.serviceId === svc.id && x.kind === "OPT_OUT");
          const optedIn = exceptions.some((x) => x.studentId === sid && x.serviceId === svc.id && x.kind === "OPT_IN");
          const weekly = svc.chargeMode === "WEEKLY";
          const weeklyBilled = weekly && weekCharges.some((c) => c.studentId === sid && c.serviceId === svc.id && c.service.chargeMode === "WEEKLY");
          return {
            serviceId: svc.id,
            name: svc.name,
            category: svc.category,
            rateCents: effectivePrice(svc, e, classRate),
            arrearsCents: owedRows.filter((c) => c.studentId === sid && c.serviceId === svc.id).reduce((a, c) => a + c.amountCents, 0),
            prepaidCents: balances.find((b) => b.studentId === sid && b.serviceId === svc.id)?.prepaidCents ?? 0,
            optional: svc.optInDaily,
            optedOut,
            optedIn,
            weekly,
            weeklyBilled,
            chargedToday: todayCharges.some((c) => c.studentId === sid && c.serviceId === svc.id),
          };
        });
      const fare = fares[i];
      return {
        studentId: sid,
        items,
        transport: fare ? { routeName: fare.routeName, stopName: fare.stopName, onewayCents: fare.onewayCents } : null,
      };
    });
  }

  return NextResponse.json({
    classrooms: classrooms.map((c) => ({ id: c.id, name: c.name, grade: c.gradeLevel, stage: c.stageLabel })),
    session: session ? { id: session.id, takenByName: session.takenByName } : null,
    date: dateStr, termId: session?.termId ?? term?.id ?? null,
    students,
    ...(fees ? { fees } : {}),
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

  const { classroomId, date, records, fees, offline, clientId } = body;
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

  // fee collection rides on the register (CI3 save_attendance_and_fees): each
  // entry carries per-item amounts + optional transport usage. Permission split:
  // attendance.save registers the roll; daily.collect takes money.
  const canCollect = canAny(user.permissions, ["daily.collect", "daily.manage"]);
  let feeEntries: { studentId: string; items: { serviceId: string; amountCents: number }[]; transportUsage?: string }[] = [];
  if (Array.isArray(fees) && fees.length) {
    if (!canCollect) return NextResponse.json({ error: "Forbidden — collecting daily fees needs the daily.collect permission" }, { status: 403 });
    for (const f of fees) {
      if (!f || typeof f.studentId !== "string") continue;
      const items = Array.isArray(f.items)
        ? f.items.map((i: any) => ({ serviceId: String(i?.serviceId ?? ""), amountCents: Math.max(0, Math.round(Number(i?.amountCents) || 0)) })).filter((i: any) => i.serviceId && i.amountCents > 0)
        : [];
      const usage = typeof f.transportUsage === "string" && TRANSPORT_USAGES.includes(f.transportUsage as any) ? f.transportUsage : undefined;
      if (items.length || usage) feeEntries.push({ studentId: f.studentId, items, transportUsage: usage });
    }
    feeEntries = feeEntries.filter((f) => byStudent.has(f.studentId) || true); // allow fee-only rows too
  }

  // offline-queue idempotency (SYNC: clientId marker, same as the legacy resource)
  if (clientId) {
    const existing = await db.attendanceSession.findFirst({ where: { classroomId, date: new Date(date), takenByName: { contains: `SYNC:${clientId}` } } });
    if (existing) return NextResponse.json({ session: { id: existing.id }, duplicate: true, saved: 0 });
  }

  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
  if (!term) return NextResponse.json({ error: "No current term for this school" }, { status: 400 });

  // find-or-create the session; records are UPSERTED per learner (CI3 partial
  // update — a re-save touches only the learners sent, the rest keep their marks)
  let session: any = await db.attendanceSession.findFirst({ where: { classroomId, date: new Date(date) }, include: { records: true } });
  if (!session) {
    session = await db.attendanceSession.create({
      data: { classroomId, schoolId, termId: term.id, date: new Date(date), takenByName: `${user.fullName}${clientId ? ` (SYNC:${clientId})` : ""}` },
    });
    session.records = [];
  }
  const before = new Map<string, string | null>(session.records.map((r: any) => [r.studentId, r.status]));
  const priorById = new Map<string, any>(session.records.map((r: any) => [r.studentId, r]));

  const chargeOutcomes: { studentId: string; waived: number; credited: number; reinstated: number; created: number }[] = [];
  for (const r of finalRecords) {
    const existing = priorById.get(r.studentId);
    if (existing) {
      await db.attendanceRecord.update({
        where: { id: existing.id },
        data: { status: r.status, excusedReason: r.excusedReason, markedOffline: !!offline },
      });
    } else {
      await db.attendanceRecord.create({
        data: { sessionId: session.id, studentId: r.studentId, status: r.status, excusedReason: r.excusedReason, markedOffline: !!offline },
      });
    }
    // CI3 status-change charge rule (save_attendance_and_fees 158–300)
    if (!offline) {
      const outcome = await applyAttendanceChargeRule(db, {
        schoolId, studentId: r.studentId, date: new Date(date),
        from: before.get(r.studentId) ?? null, to: r.status,
        byName: user.fullName, byRole: user.role,
      });
      if (outcome.waived || outcome.credited || outcome.reinstated || outcome.created) chargeOutcomes.push({ studentId: r.studentId, ...outcome });
    }
  }

  // fee collection on the register (CI3): per-item amounts settle that item's
  // arrears FIFO, excess banks as the item's prepaid advance
  const receipts: { studentId: string; receiptNo: string; amountCents: number }[] = [];
  if (!offline && feeEntries.length) {
    const svcRows = feeEntries.flatMap((f) => f.items.map((i) => i.serviceId));
    const services = svcRows.length ? await db.dailyService.findMany({ where: { schoolId, id: { in: svcRows } } }) : [];
    for (const f of feeEntries) {
      if (f.transportUsage) {
        const applied = await applyTransportUsage(db, schoolId, f.studentId, new Date(date), f.transportUsage as TransportUsage, { performedByName: user.fullName, performedByRole: user.role });
        if (f.transportUsage !== "NOT_USING" && !applied) {
          return NextResponse.json({ error: "One of the learners has no active transport assignment" }, { status: 400 });
        }
      }
      if (f.items.length) {
        const { payment } = await collectDailyItems(db, {
          schoolId, studentId: f.studentId, items: f.items,
          services: services.map((s) => ({ id: s.id, name: s.name })),
          method: "CASH", receivedByName: user.fullName, performedByRole: user.role,
          paidAt: new Date(date),
        });
        receipts.push({ studentId: f.studentId, receiptNo: payment.receiptNo, amountCents: payment.amountCents });
      }
    }
  }

  // auto absence alerts to guardians (legacy semantics: online saves, ABSENT
  // only, deduplicated per learner + day) — gated by evtLearnerAbsent
  const smsSetting = await db.smsSetting.findUnique({ where: { schoolId } });
  if (smsSetting?.autoAbsenceAlerts && !offline && (await smsEventEnabled(db, schoolId, "LEARNER_ABSENT"))) {
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

  return NextResponse.json({
    session: { id: session.id },
    saved: finalRecords.length,
    chargeOutcomes,
    receipts,
  });
}

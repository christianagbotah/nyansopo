import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

// ── Barcode scanner attendance (iEduPro admin/barcode_scanner parity) ──
// An operator at the gate scans a learner's ID card (the Nyansapo Index,
// e.g. NY-2019-000123) and the learner is checked in / out:
//   • CHECK_IN  — marks the day's register PRESENT (duplicate scans rejected,
//                 learners owing invoice balances are denied unless the
//                 operator ticks "APPROVE" to admit them)
//   • CHECK_OUT — logged against the day's check-in
// iEduPro gates the scanner to admin level ≤ 2 → SUPER_ADMIN / SCHOOL_ADMIN.

type ScanOutcome =
  | "GRANTED" | "APPROVED" | "DENIED" | "DUPLICATE" | "UNKNOWN"
  | "CHECKED_OUT" | "NOT_CHECKED_IN" | "CANCELLED";

type ScanEvent = {
  id: string;
  at: number; // epoch ms
  mode: "CHECK_IN" | "CHECK_OUT";
  outcome: ScanOutcome;
  studentId: string | null;
  learnerName: string | null;
  nyansapoIndex: string | null;
  classroomName: string | null;
  amountOwedCents?: number;
  byName: string;
  /** bookkeeping for cancel(): the register record this scan created / flipped */
  recordId?: string;
  createdRecord?: boolean;
  priorStatus?: string | null;
};

/** In-memory scan log, keyed `${schoolId}|${date}` (local-memory cache per app
 *  conventions — attendance state itself lives in the DB register sessions). */
const scanLog = new Map<string, ScanEvent[]>();
const MAX_EVENTS_PER_DAY = 500;

const dayKey = (schoolId: string, dateStr: string) => `${schoolId}|${dateStr}`;

function dayBounds(dateStr: string) {
  const start = new Date(`${dateStr}T00:00:00.000Z`);
  return { start, end: new Date(start.getTime() + 864e5) };
}

function pushEvent(schoolId: string, dateStr: string, e: ScanEvent) {
  const key = dayKey(schoolId, dateStr);
  const list = scanLog.get(key) ?? [];
  list.push(e);
  if (list.length > MAX_EVENTS_PER_DAY) list.splice(0, list.length - MAX_EVENTS_PER_DAY);
  scanLog.set(key, list);
  // prune keys older than two days so the log cannot grow unbounded
  const cutoff = Date.now() - 2 * 864e5;
  for (const k of Array.from(scanLog.keys())) {
    const d = k.split("|")[1];
    if (new Date(`${d}T00:00:00.000Z`).getTime() < cutoff) scanLog.delete(k);
  }
}

function normalizeDate(v: unknown): string {
  return typeof v === "string" && /^\d{4}-\d{2}-\d{2}$/.test(v) ? v : new Date().toISOString().slice(0, 10);
}

/** iEduPro admin level ≤ 2: the scanner console is administrators-only. */
function adminOnly(user: { role: string }): NextResponse | null {
  if (user.role !== "SUPER_ADMIN" && user.role !== "SCHOOL_ADMIN") {
    return NextResponse.json({ error: "Scanner is restricted to administrators (level ≤ 2)" }, { status: 403 });
  }
  return null;
}

// ── GET /api/school/[id]/ops/scanner?date=YYYY-MM-DD — today's scan state ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["attendance.view"] });
  if (!auth.ok) return auth.res;
  const gate = adminOnly(auth.user);
  if (gate) return gate;

  const url = new URL(req.url);
  const dateStr = normalizeDate(url.searchParams.get("date"));
  const { start, end } = dayBounds(dateStr);

  const sessions = await db.attendanceSession.findMany({
    where: { schoolId, date: { gte: start, lt: end } },
    include: { classroom: true, records: { include: { student: true } } },
    orderBy: { date: "asc" },
  });

  const allRecords = sessions.flatMap((s) => s.records);
  const checkedIn = new Set(allRecords.filter((r) => r.status === "PRESENT" || r.status === "LATE").map((r) => r.studentId));
  const summary = {
    checkedIn: checkedIn.size,
    present: allRecords.filter((r) => r.status === "PRESENT").length,
    absent: allRecords.filter((r) => r.status === "ABSENT").length,
    late: allRecords.filter((r) => r.status === "LATE").length,
  };

  const recentScans = (scanLog.get(dayKey(schoolId, dateStr)) ?? []).slice().reverse().slice(0, 200);

  return NextResponse.json({
    date: dateStr,
    summary,
    recentScans,
    sessions: sessions.map((s) => ({
      id: s.id,
      classroomId: s.classroomId,
      classroom: s.classroom.name,
      takenByName: s.takenByName,
      records: s.records
        .map((r) => ({ id: r.id, studentId: r.studentId, name: `${r.student.firstName} ${r.student.lastName}`, nyansapoIndex: r.student.nyansapoIndex, status: r.status }))
        .sort((a, b) => a.name.localeCompare(b.name)),
    })),
  });
}

// ── POST /api/school/[id]/ops/scanner — scan & cancel ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["attendance.take"] });
  if (!auth.ok) return auth.res;
  const gate = adminOnly(auth.user);
  if (gate) return gate;
  const user = auth.user;

  // ── scan: CHECK_IN / CHECK_OUT a learner's Nyansapo Index ──
  if (action === "scan") {
    const code = String(body.code ?? "").trim();
    const mode: "CHECK_IN" | "CHECK_OUT" = body.mode === "CHECK_OUT" ? "CHECK_OUT" : "CHECK_IN";
    const dateStr = normalizeDate(body.date);
    if (!code) return NextResponse.json({ error: "Learner code is required" }, { status: 400 });

    // learner lookup: exact (case-insensitive) index, scoped to an ACTIVE enrolment in this school
    const student = await db.student.findFirst({
      where: { nyansapoIndex: { equals: code, mode: "insensitive" } },
      include: {
        enrollments: { where: { schoolId, status: "ACTIVE" }, include: { classroom: true }, orderBy: { admissionDate: "desc" }, take: 1 },
      },
    });
    if (!student || !student.enrollments.length) {
      pushEvent(schoolId, dateStr, {
        id: `ev-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`, at: Date.now(), mode,
        outcome: "UNKNOWN", studentId: null, learnerName: null, nyansapoIndex: code,
        classroomName: null, byName: user.fullName,
      });
      return NextResponse.json({ found: false, message: "Unknown learner code" });
    }
    const enrollment = student.enrollments[0];
    const learner = {
      id: student.id, name: `${student.firstName} ${student.lastName}`,
      nyansapoIndex: student.nyansapoIndex, gender: student.gender,
      guardianName: student.guardianName, guardianPhone: student.guardianPhone,
      photoSeed: student.photoSeed,
    };
    const classroom = { id: enrollment.classroomId, name: enrollment.classroom.name };

    // the day's register session(s) for the learner's class — the app keeps a
    // single register session per (classroom, date), matching the register module
    const { start, end } = dayBounds(dateStr);
    const sessions = await db.attendanceSession.findMany({
      where: { classroomId: enrollment.classroomId, date: { gte: start, lt: end } },
      include: { records: { where: { studentId: student.id } } },
      orderBy: { date: "asc" },
    });
    const presentRecord = sessions.flatMap((s) => s.records).find((r) => r.status === "PRESENT" || r.status === "LATE");

    if (mode === "CHECK_OUT") {
      if (!presentRecord) {
        pushEvent(schoolId, dateStr, {
          id: `ev-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`, at: Date.now(), mode,
          outcome: "NOT_CHECKED_IN", studentId: student.id, learnerName: learner.name,
          nyansapoIndex: learner.nyansapoIndex, classroomName: classroom.name, byName: user.fullName,
        });
        return NextResponse.json({ error: "Learner has not checked in", learner, classroom });
      }
      // CHECK_OUT semantics: AttendanceSession carries no session-type field and
      // every flow reads the single (classroom, date) register — so no afternoon
      // session is created; the register stays PRESENT and the exit is recorded
      // in the scan log only (charge-free, consistent with the model).
      pushEvent(schoolId, dateStr, {
        id: `ev-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`, at: Date.now(), mode,
        outcome: "CHECKED_OUT", studentId: student.id, learnerName: learner.name,
        nyansapoIndex: learner.nyansapoIndex, classroomName: classroom.name, byName: user.fullName,
      });
      return NextResponse.json({ checkedOut: true, learner, classroom, at: Date.now() });
    }

    // ── CHECK_IN ──
    if (presentRecord) {
      pushEvent(schoolId, dateStr, {
        id: `ev-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`, at: Date.now(), mode,
        outcome: "DUPLICATE", studentId: student.id, learnerName: learner.name,
        nyansapoIndex: learner.nyansapoIndex, classroomName: classroom.name, byName: user.fullName,
      });
      return NextResponse.json({
        duplicate: true,
        message: `${learner.name} was already checked in today`,
        learner, classroom,
      });
    }

    // arrears gate: outstanding balance across the learner's invoices in this school
    const invoices = await db.invoice.findMany({ where: { studentId: student.id, schoolId, writtenOff: false } });
    const owedCents = invoices.reduce((s, i) => s + (i.totalCents - i.discountCents - i.paidCents), 0);
    if (owedCents > 0 && !body.approve) {
      pushEvent(schoolId, dateStr, {
        id: `ev-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`, at: Date.now(), mode,
        outcome: "DENIED", studentId: student.id, learnerName: learner.name,
        nyansapoIndex: learner.nyansapoIndex, classroomName: classroom.name,
        amountOwedCents: owedCents, byName: user.fullName,
      });
      return NextResponse.json({
        denied: true,
        amountOwedCents: owedCents,
        message: `${learner.name} owes GH₵${(owedCents / 100).toFixed(2)} — entry denied`,
        learner, classroom,
      });
    }

    // find/create the day's register session (same model as the register module's save)
    let sessionId = sessions[0]?.id ?? null;
    if (!sessionId) {
      const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
      if (!term) return NextResponse.json({ error: "No current term — attendance cannot be recorded" }, { status: 400 });
      const created = await db.attendanceSession.create({
        data: { classroomId: enrollment.classroomId, schoolId, termId: term.id, date: start, takenByName: `Scanner — ${user.fullName}` },
      });
      sessionId = created.id;
    }

    // upsert the learner's record to PRESENT (flips an ABSENT from a saved register)
    const prior = sessions.flatMap((s) => s.records).find((r) => r.studentId === student.id) ?? null;
    let recordId: string;
    let createdRecord = false;
    let priorStatus: string | null = null;
    if (prior) {
      priorStatus = prior.status;
      recordId = prior.id;
      await db.attendanceRecord.update({ where: { id: prior.id }, data: { status: "PRESENT" } });
    } else {
      const rec = await db.attendanceRecord.create({ data: { sessionId, studentId: student.id, status: "PRESENT" } });
      recordId = rec.id;
      createdRecord = true;
    }

    pushEvent(schoolId, dateStr, {
      id: `ev-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`, at: Date.now(), mode,
      outcome: owedCents > 0 ? "APPROVED" : "GRANTED", studentId: student.id, learnerName: learner.name,
      nyansapoIndex: learner.nyansapoIndex, classroomName: classroom.name,
      amountOwedCents: owedCents > 0 ? owedCents : undefined,
      recordId, createdRecord, priorStatus, byName: user.fullName,
    });
    return NextResponse.json({
      granted: true,
      approved: owedCents > 0,
      amountOwedCents: owedCents > 0 ? owedCents : 0,
      learner, classroom,
      record: { id: recordId, status: "PRESENT" },
      at: Date.now(),
    });
  }

  // ── cancel: undo a learner's scanner check-in for the day ──
  if (action === "cancel") {
    const { studentId } = body;
    const dateStr = normalizeDate(body.date);
    if (!studentId) return NextResponse.json({ error: "studentId is required" }, { status: 400 });

    const enrollment = await db.enrollment.findFirst({ where: { studentId, schoolId, status: "ACTIVE" }, include: { classroom: true, student: true } });
    if (!enrollment) return NextResponse.json({ error: "Learner is not actively enrolled in this school" }, { status: 404 });

    const events = scanLog.get(dayKey(schoolId, dateStr)) ?? [];
    const checkIn = [...events].reverse().find(
      (e) => e.studentId === studentId && (e.outcome === "GRANTED" || e.outcome === "APPROVED"),
    );
    if (!checkIn) return NextResponse.json({ error: "No scanner check-in found for this learner today" }, { status: 400 });

    // remove the scanner-created record (or restore the register status it flipped)
    if (checkIn.recordId) {
      if (checkIn.createdRecord) {
        await db.attendanceRecord.deleteMany({ where: { id: checkIn.recordId, studentId } });
      } else if (checkIn.priorStatus) {
        await db.attendanceRecord.updateMany({ where: { id: checkIn.recordId, studentId }, data: { status: checkIn.priorStatus } });
      }
    }

    // tidy-up: a scanner-created session left with no records is removed so the
    // class register stays clean for the teacher's own register flow
    const { start, end } = dayBounds(dateStr);
    const session = await db.attendanceSession.findFirst({
      where: { classroomId: enrollment.classroomId, date: { gte: start, lt: end } },
      include: { records: true },
    });
    if (session && session.takenByName?.startsWith("Scanner —") && session.records.length === 0) {
      await db.attendanceSession.delete({ where: { id: session.id } });
    }

    // drop the learner's scan events for the day, then log the cancellation
    scanLog.set(dayKey(schoolId, dateStr), events.filter((e) => e.studentId !== studentId));
    pushEvent(schoolId, dateStr, {
      id: `ev-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`, at: Date.now(), mode: "CHECK_IN",
      outcome: "CANCELLED", studentId, learnerName: `${enrollment.student.firstName} ${enrollment.student.lastName}`,
      nyansapoIndex: enrollment.student.nyansapoIndex, classroomName: enrollment.classroom.name, byName: user.fullName,
    });
    return NextResponse.json({ cancelled: true });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

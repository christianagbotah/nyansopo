import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";

// ── TERM CLOSING & ROLLOVER (wave 76) ─────────────────────────────────────
// The end-of-term moment every Ghanaian head walks through — results approved
// and locked per class, the term's invoices billed, the library's books back,
// the calendar's next term ready — gathered into ONE readiness board, with
// close / reopen / roll-over as first-class, audited actions:
//   GET  — readiness checklist for the current term (or ?termId=)
//   POST close    { force? }  — stamp Term.closedAt (results gate unless forced)
//   POST reopen              — clear the stamp (a closed term may reopen)
//   POST rollover { force? } — flip isCurrent to the calendar's next term
//         (same transaction invariant the sessions switcher uses: exactly one
//          current term and its year current — dashboards, registers and fee
//          rails all resolve "the live term" through these flags)
// ──────────────────────────────────────────────────────────────────────────

type ApprovalStatus = "DRAFT" | "SUBMITTED" | "APPROVED" | "LOCKED";
type TermRow = NonNullable<Awaited<ReturnType<typeof loadTerm>>>;

async function loadTerm(schoolId: string, termIdParam: string | null) {
  return termIdParam
    ? db.term.findFirst({ where: { id: termIdParam, academicYear: { schoolId } }, include: { academicYear: true } })
    : db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } }, include: { academicYear: true } });
}

/** The calendar's next term: same year, next sequence; else the earliest
 *  term of the year whose startsAt follows this year's endsAt. */
async function nextTermOf(schoolId: string, term: TermRow) {
  const sameYear = await db.term.findFirst({
    where: { academicYearId: term.academicYearId, sequence: { gt: term.sequence } },
    orderBy: { sequence: "asc" },
    include: { academicYear: true },
  });
  if (sameYear) return sameYear;
  const nextYear = await db.academicYear.findFirst({
    where: { schoolId, startsAt: { gt: term.academicYear.endsAt } },
    orderBy: { startsAt: "asc" },
    include: { terms: { orderBy: { sequence: "asc" }, include: { academicYear: true } } },
  });
  return nextYear?.terms[0] ?? null;
}

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["reports.view"] });
  if (!auth.ok) return auth.res;

  const url = new URL(req.url);
  const term = await loadTerm(schoolId, url.searchParams.get("termId"));
  if (!term) return NextResponse.json({ error: "No term found" }, { status: 404 });

  const [classrooms, approvals, enrollments, invoices, attendanceCount, nextTerm] = await Promise.all([
    db.classroom.findMany({ where: { schoolId }, select: { id: true, name: true }, orderBy: { name: "asc" } }),
    db.resultApproval.findMany({ where: { schoolId, termId: term.id }, select: { classroomId: true, status: true } }),
    db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, select: { classroomId: true, studentId: true } }),
    db.invoice.findMany({ where: { termId: term.id }, select: { studentId: true, totalCents: true } }),
    db.attendanceSession.count({ where: { termId: term.id } }),
    nextTermOf(schoolId, term),
  ]);

  const classRows = classrooms.map((c) => {
    const approval = approvals.find((r) => r.classroomId === c.id);
    const status: ApprovalStatus = (approval?.status as ApprovalStatus) ?? "DRAFT";
    return { classroomId: c.id, name: c.name, enrolled: enrollments.filter((e) => e.classroomId === c.id).length, status };
  });
  const lockedCount = classRows.filter((r) => r.status === "LOCKED").length;
  const approvedCount = classRows.filter((r) => r.status === "APPROVED" || r.status === "LOCKED").length;
  const allLocked = classrooms.length > 0 && lockedCount === classrooms.length;

  const billedStudents = new Set(invoices.map((i) => i.studentId).filter(Boolean));
  const enrolledStudents = new Set(enrollments.map((e) => e.studentId));
  const unbilled = [...enrolledStudents].filter((s) => !billedStudents.has(s)).length;
  const billedTotal = invoices.reduce((s, i) => s + i.totalCents, 0);

  const [loansOut, loansOverdue] = await Promise.all([
    db.libraryLoan.count({ where: { book: { schoolId }, returnedAt: null } }),
    db.libraryLoan.count({ where: { book: { schoolId }, returnedAt: null, dueAt: { lt: new Date() } } }),
  ]);

  return NextResponse.json({
    term: {
      id: term.id, name: term.name, yearName: term.academicYear.name,
      startsAt: term.startsAt, endsAt: term.endsAt, closedAt: term.closedAt, isCurrent: term.isCurrent,
    },
    results: {
      classes: classRows, total: classrooms.length,
      approved: approvedCount, locked: lockedCount, allLocked,
    },
    billing: {
      invoices: invoices.length, billedTotal,
      enrolled: enrolledStudents.size, billed: billedStudents.size, unbilled,
      coverage: enrolledStudents.size ? Math.min(100, Math.round((billedStudents.size / enrolledStudents.size) * 100)) : 100,
    },
    library: { loansOut, loansOverdue },
    attendance: { sessions: attendanceCount },
    calendar: nextTerm
      ? { id: nextTerm.id, name: nextTerm.name, yearName: nextTerm.academicYear?.name ?? null, startsAt: nextTerm.startsAt }
      : null,
  });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["settings.manage"] });
  if (!auth.ok) return auth.res;

  const term = await loadTerm(schoolId, body.termId ?? null);
  if (!term) return NextResponse.json({ error: "No term found" }, { status: 404 });
  const label = `${term.name} · ${term.academicYear.name}`;

  if (action === "close") {
    if (term.closedAt) return NextResponse.json({ error: `${label} is already closed` }, { status: 409 });
    const [approvals, classroomCount] = await Promise.all([
      db.resultApproval.findMany({ where: { schoolId, termId: term.id }, select: { classroomId: true, status: true } }),
      db.classroom.count({ where: { schoolId } }),
    ]);
    const unlocked = classroomCount - approvals.filter((a) => a.status === "LOCKED").length;
    if (unlocked > 0 && !body.force) {
      return NextResponse.json({
        error: `${unlocked} of ${classroomCount} classes are not locked yet — approve & lock their terminal results first (or force the close)`,
        unlocked,
      }, { status: 409 });
    }
    const closedAt = new Date();
    await db.term.update({ where: { id: term.id }, data: { closedAt } });
    await audit({
      schoolId, actor: auth.user, action: "LOCK", entityType: "Term", entityId: term.id, entityLabel: label, req,
      summary: `${label} closed by ${auth.user.fullName}${unlocked > 0 ? ` (forced — ${unlocked} class result${unlocked > 1 ? "s" : ""} still unlocked)` : " — all class results locked"}`,
      after: { closedAt: closedAt.toISOString(), forced: unlocked > 0 },
    });
    return NextResponse.json({ ok: true, closedAt, message: `${label} is closed${unlocked > 0 ? ` — ${unlocked} class result${unlocked > 1 ? "s" : ""} were still unlocked` : ""}` });
  }

  if (action === "reopen") {
    if (!term.closedAt) return NextResponse.json({ error: `${label} is not closed` }, { status: 409 });
    await db.term.update({ where: { id: term.id }, data: { closedAt: null } });
    await audit({
      schoolId, actor: auth.user, action: "UNLOCK", entityType: "Term", entityId: term.id, entityLabel: label, req,
      summary: `${label} reopened by ${auth.user.fullName}`,
      before: { closedAt: term.closedAt.toISOString() },
    });
    return NextResponse.json({ ok: true, message: `${label} is open again` });
  }

  if (action === "rollover") {
    if (term.closedAt == null && !body.force) {
      return NextResponse.json({ error: `${label} is not closed yet — close it first (or force the rollover)` }, { status: 409 });
    }
    const next = await nextTermOf(schoolId, term);
    if (!next) {
      return NextResponse.json({ error: "No next term on the calendar — create the next academic year and its terms in Academic sessions first" }, { status: 409 });
    }
    const year = await db.academicYear.findUnique({ where: { id: next.academicYearId }, select: { name: true } });
    const nextLabel = `${next.name} · ${year?.name ?? ""}`;
    await db.$transaction(async (tx) => {
      await tx.term.updateMany({ where: { academicYear: { schoolId }, NOT: { id: next.id } }, data: { isCurrent: false } });
      await tx.term.update({ where: { id: next.id }, data: { isCurrent: true } });
      await tx.academicYear.updateMany({ where: { schoolId, NOT: { id: next.academicYearId } }, data: { isCurrent: false } });
      await tx.academicYear.update({ where: { id: next.academicYearId }, data: { isCurrent: true } });
    });
    await audit({
      schoolId, actor: auth.user, action: "UPDATE", entityType: "Term", entityId: next.id, entityLabel: nextLabel, req,
      summary: `Term rolled over: ${label} → ${nextLabel} by ${auth.user.fullName}${term.closedAt ? "" : " (forced while open)"}`,
      before: { currentTermId: term.id, currentTerm: label },
      after: { currentTermId: next.id, currentTerm: nextLabel },
    });
    return NextResponse.json({ ok: true, message: `Live session is now ${nextLabel}`, current: { termId: next.id, name: nextLabel } });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

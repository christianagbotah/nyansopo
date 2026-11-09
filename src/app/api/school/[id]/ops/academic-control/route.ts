import { NextRequest, NextResponse } from "next/server";
import { sortClassrooms } from "@/lib/classes";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

// ── ACADEMIC CONTROL (CI3 Academic_control parity) ──
// Head-teacher governance over each class's terminal results.
//   GET  ?termId= — per-class completion metrics (portfolio / class work /
//        exam) + workflow status; ?termId=&classroomId= adds the audit trail.
//   POST submit | approve | lock | unlock — the exact CI3 state machine with
//        its error strings, every action appending to ResultApprovalAudit.

const UNLOCK_ROLES = new Set(["SUPER_ADMIN", "GROUP_OWNER", "SCHOOL_ADMIN"]);

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["reports.view"] });
  if (!auth.ok) return auth.res;

  const url = new URL(req.url);
  const termIdParam = url.searchParams.get("termId");
  const classroomParam = url.searchParams.get("classroomId");

  const term = termIdParam
    ? await db.term.findFirst({ where: { id: termIdParam, academicYear: { schoolId } }, include: { academicYear: true } })
    : await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } }, include: { academicYear: true } });
  if (!term) return NextResponse.json({ error: "No term found" }, { status: 404 });

  const terms = await db.term.findMany({ where: { academicYear: { schoolId } }, include: { academicYear: true }, orderBy: [{ academicYear: { name: "asc" } }, { sequence: "asc" }] });
  const classrooms =sortClassrooms(await db.classroom.findMany({ where: { schoolId }, select: {  id: true, name: true, gradeLevel: true  } }));
  const classIds = classrooms.map((c) => c.id);

  const [enrollments, portfolioEntries, assessments, approvals] = await Promise.all([
    db.enrollment.findMany({ where: { schoolId, status: "ACTIVE", classroomId: { in: classIds } }, select: { classroomId: true, studentId: true } }),
    db.portfolioEntry.findMany({
      where: { schoolId, entryDate: { gte: term.startsAt, lte: term.endsAt }, classroomId: { in: classIds } },
      select: { classroomId: true, studentId: true, score: true },
    }),
    db.assessment.findMany({
      where: { schoolId, termId: term.id },
      select: { classroomId: true, type: true, marks: { select: { studentId: true, score: true } } },
    }),
    db.resultApproval.findMany({ where: { schoolId, termId: term.id } }),
  ]);

  const rows = classrooms.map((c) => {
    const enrolled = enrollments.filter((e) => e.classroomId === c.id);
    const ids = new Set(enrolled.map((e) => e.studentId));
    // portfolio: any scored weekly entry this term
    const portfolioDone = new Set(portfolioEntries.filter((p) => p.classroomId === c.id && ids.has(p.studentId) && p.score > 0).map((p) => p.studentId));
    // class work (SBA): any non-exam assessment mark this term
    const sbaDone = new Set<string>();
    const examDone = new Set<string>();
    for (const a of assessments.filter((a) => a.classroomId === c.id)) {
      for (const m of a.marks) {
        if (!ids.has(m.studentId) || m.score == null) continue;
        (a.type === "END_OF_TERM" ? examDone : sbaDone).add(m.studentId);
      }
    }
    const pct = (n: number) => (enrolled.length ? Math.round((n / enrolled.length) * 1000) / 10 : 0);
    const approval = approvals.find((r) => r.classroomId === c.id);
    return {
      classroomId: c.id, name: c.name, gradeLevel: c.gradeLevel, enrolled: enrolled.length,
      portfolioCompletion: pct(portfolioDone.size),
      sbaCompletion: pct(sbaDone.size),
      examCompletion: pct(examDone.size),
      status: approval?.status ?? "DRAFT",
      submittedBy: approval?.submittedBy ?? null, submittedAt: approval?.submittedAt ?? null,
      approvedBy: approval?.approvedBy ?? null, approvedAt: approval?.approvedAt ?? null,
      lockedBy: approval?.lockedBy ?? null, lockedAt: approval?.lockedAt ?? null,
      notes: approval?.notes ?? null,
    };
  });

  // audit trail for one class (optional)
  let audit: { action: string; previousStatus: string; newStatus: string; performedBy: string; reason: string | null; createdAt: Date }[] = [];
  if (classroomParam) {
    const approval = approvals.find((r) => r.classroomId === classroomParam);
    if (approval) {
      const rowsA = await db.resultApprovalAudit.findMany({ where: { approvalId: approval.id }, orderBy: { createdAt: "desc" } });
      audit = rowsA.map((a) => ({ action: a.action, previousStatus: a.previousStatus, newStatus: a.newStatus, performedBy: a.performedBy, reason: a.reason, createdAt: a.createdAt }));
    }
  }

  return NextResponse.json({
    term: { id: term.id, name: term.name, yearName: term.academicYear.name, startsAt: term.startsAt, endsAt: term.endsAt },
    terms: terms.map((t) => ({ id: t.id, name: `${t.name} — ${t.academicYear.name}`, isCurrent: t.isCurrent })),
    canUnlock: UNLOCK_ROLES.has(auth.user.role),
    rows,
    audit,
  });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["reports.publish"] });
  if (!auth.ok) return auth.res;
  const actor = auth.user.fullName;

  const { classroomId, termId, notes, reason } = body;
  if (!classroomId || !termId) return NextResponse.json({ error: "classroomId and termId are required" }, { status: 400 });
  const [cls, term] = await Promise.all([
    db.classroom.findFirst({ where: { id: classroomId, schoolId } }),
    db.term.findFirst({ where: { id: termId, academicYear: { schoolId } } }),
  ]);
  if (!cls || !term) return NextResponse.json({ error: "Class or term not found" }, { status: 404 });

  // state machine — CI3 Result_approval_service transitions + error strings
  const TRANSITIONS: Record<string, { from: string; to: string; error: string; requireReason?: boolean }> = {
    submit: { from: "DRAFT", to: "SUBMITTED", error: "Results already submitted" },
    approve: { from: "SUBMITTED", to: "APPROVED", error: "Results must be submitted first" },
    lock: { from: "APPROVED", to: "LOCKED", error: "Results must be approved first" },
    unlock: { from: "LOCKED", to: "DRAFT", error: "Results not locked", requireReason: true },
  };
  const t = TRANSITIONS[action];
  if (!t) return NextResponse.json({ error: "Unknown action" }, { status: 400 });
  if (t.requireReason && !reason?.trim()) return NextResponse.json({ error: "Reason required for unlock" }, { status: 400 });
  if (action === "unlock" && !UNLOCK_ROLES.has(auth.user.role)) return NextResponse.json({ error: "Only the head teacher or an administrator can unlock results" }, { status: 403 });

  const result = await db.$transaction(async (tx) => {
    const row = await tx.resultApproval.findUnique({ where: { classroomId_termId: { classroomId, termId } } });
    // submit is the only transition that may CREATE the row (from DRAFT);
    // approve/lock/unlock must find it in exactly the expected prior state
    if (action === "submit") {
      if (row && row.status !== t.from) return { error: t.error };
    } else if (!row || row.status !== t.from) {
      return { error: t.error };
    }
    const now = new Date();
    const stamps: Record<string, unknown> = {};
    if (action === "submit") { stamps.submittedBy = actor; stamps.submittedAt = now; stamps.notes = notes ?? null; }
    if (action === "approve") { stamps.approvedBy = actor; stamps.approvedAt = now; }
    if (action === "lock") { stamps.lockedBy = actor; stamps.lockedAt = now; }
    if (action === "unlock") { stamps.lockedBy = null; stamps.lockedAt = null; }
    const saved = await tx.resultApproval.upsert({
      where: { classroomId_termId: { classroomId, termId } },
      create: { schoolId, classroomId, termId, status: t.to, ...(stamps as any) },
      update: { status: t.to, ...(stamps as any) },
    });
    await tx.resultApprovalAudit.create({
      data: {
        approvalId: saved.id, action: action.toUpperCase(), previousStatus: t.from, newStatus: t.to,
        performedBy: actor, reason: (reason ?? notes ?? null) || null,
      },
    });
    return { status: saved.status };
  });

  if ("error" in result && (result as any).error) return NextResponse.json({ error: (result as any).error }, { status: 400 });
  return NextResponse.json({ status: (result as any).status });
}

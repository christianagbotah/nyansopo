import { db } from "@/lib/db";

// ── RESULTS GOVERNANCE (CI3 Academic_control / Result_approval_service parity)
//    One ResultApproval row per (class, term). DRAFT → SUBMITTED → APPROVED →
//    LOCKED; unlocking returns to DRAFT (submitted/approved stamps are kept).
//    Marks entry is blocked the moment a class leaves DRAFT; report-card
//    recomputation is blocked while LOCKED. CI3 left these guards unwired —
//    here they actually run on every marks-bearing endpoint.

export type GateResult = { blocked: false } | { blocked: true; status: string; error: string };

/** Current approval status for (class, term) — "DRAFT" when no row exists yet. */
export async function approvalStatus(schoolId: string, classroomId: string, termId: string): Promise<string> {
  const row = await db.resultApproval.findUnique({ where: { classroomId_termId: { classroomId, termId } }, select: { status: true } });
  return row?.status ?? "DRAFT";
}

/** Gate for MARKS-ENTRY actions: anything past DRAFT refuses edits. */
export async function assertResultsEditable(schoolId: string, classroomId: string, termId?: string): Promise<GateResult> {
  const term = termId
    ? await db.term.findFirst({ where: { id: termId, academicYear: { schoolId } }, select: { id: true } })
    : await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } }, select: { id: true } });
  if (!term) return { blocked: false }; // no term context → nothing to gate
  const status = await approvalStatus(schoolId, classroomId, term.id);
  if (status === "DRAFT") return { blocked: false };
  return {
    blocked: true, status,
    error: `Results are ${status.toLowerCase()} and cannot be edited. Contact the head teacher to unlock.`,
  };
}

/** Gate for RECOMPUTATION (report-card generate): locked results are frozen. */
export async function assertResultsComputable(schoolId: string, classroomId: string, termId: string): Promise<GateResult> {
  const status = await approvalStatus(schoolId, classroomId, termId);
  if (status === "LOCKED") return { blocked: true, status, error: "Results are locked. Cannot recompute. Unlock first." };
  return { blocked: false };
}

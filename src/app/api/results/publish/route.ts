import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

/* ─────────────────────────────────────────────────────────────
   POST /api/results/publish — wave 21 results publication.

   Body: { schoolId, termId, classId, publish?: boolean }
   (perm: reports.publish; publish=true by default)

   Flips the published flag (and publishedAt stamp) on every
   TermResult ledger row and compiled ReportCard for ONE class in
   ONE term — the moment learners and parents see the results in
   the portals.

   Governance (waves 15 + 20 doctrine carried through):
   • Publishing requires the class's ResultApproval to be APPROVED
     or LOCKED — DRAFT / SUBMITTED classes are refused (423) and
     the caller is pointed at Results Control (Exams & Reports).
   • Recalling to draft (publish=false) is allowed in any state —
     it is the head teacher's emergency off-switch.
   • The recompute route returns freshly compiled rows to draft, so
     published sheets always match the approved ledger exactly.
   ───────────────────────────────────────────────────────────── */

export async function POST(req: NextRequest) {
  const body = await req.json().catch(() => ({}));
  const S = (v: unknown) => (typeof v === "string" ? v.trim() : "");
  const schoolId = S(body.schoolId);
  const termId = S(body.termId);
  const classId = S(body.classId);
  const publish = body.publish !== false;
  if (!schoolId || !termId || !classId) {
    return NextResponse.json({ error: "schoolId, termId and classId are required" }, { status: 400 });
  }

  const auth = await opsAuth(req, schoolId, "POST", { perms: ["reports.publish"] });
  if (!auth.ok) return auth.res;

  const [term, classroom] = await Promise.all([
    db.term.findFirst({
      where: { id: termId, academicYear: { schoolId } },
      include: { academicYear: { select: { name: true } } },
    }),
    db.classroom.findFirst({ where: { id: classId, schoolId } }),
  ]);
  if (!term) return NextResponse.json({ error: "Term not found in this school" }, { status: 404 });
  if (!classroom) return NextResponse.json({ error: "Class not found in this school" }, { status: 404 });

  const enrolled = await db.enrollment.findMany({
    where: { classroomId: classId, status: "ACTIVE" },
    select: { studentId: true },
  });
  if (!enrolled.length) return NextResponse.json({ error: "No learners are actively enrolled in this class" }, { status: 400 });
  const studentIds = enrolled.map((e) => e.studentId);

  if (publish) {
    const approval = await db.resultApproval.findUnique({
      where: { classroomId_termId: { classroomId: classId, termId } },
      select: { status: true },
    });
    const status = approval?.status ?? "DRAFT";
    if (status !== "APPROVED" && status !== "LOCKED") {
      return NextResponse.json(
        {
          error:
            status === "DRAFT"
              ? "Results are still draft — submit and approve them in Results Control (Exams & Reports) before publishing."
              : "Results are awaiting approval — the head teacher must approve them in Results Control (Exams & Reports) before publishing.",
          status,
        },
        { status: 423 },
      );
    }
  }

  const publishedAt = publish ? new Date() : null;
  // TermResult carries only the boolean flag; the publication timestamp
  // lives on the ReportCard (the sheet parents actually receive)
  const [results, cards] = await db.$transaction([
    db.termResult.updateMany({
      where: { termId, classroomId: classId, studentId: { in: studentIds } },
      data: { published: publish },
    }),
    db.reportCard.updateMany({
      where: { termId, studentId: { in: studentIds } },
      data: { published: publish, publishedAt },
    }),
  ]);

  return NextResponse.json({
    ok: true,
    publish,
    results: results.count,
    cards: cards.count,
    term: { id: term.id, name: term.name, yearName: term.academicYear.name },
    classroom: { id: classroom.id, name: classroom.name },
  });
}

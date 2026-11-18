import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

/* ─────────────────────────────────────────────────────────────
   GET /api/results/report-cards/[id]?schoolId= — wave 20
   printable report-card detail (perm: reports.view).

   One compiled ReportCard plus everything the Ghana-format sheet
   prints: school header block, learner particulars, the term's
   normalized TermResult ledger rows (30/70 composition, BECE band,
   subject position, remark) and the card's summary block (best-6
   aggregate, class position, attendance %, remarks). Falls back to
   the legacy ReportCardResult rows when no TermResult ledger exists
   (e.g. early-years cards compiled by the creche generator).
   ───────────────────────────────────────────────────────────── */

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const schoolId = new URL(req.url).searchParams.get("schoolId") ?? "";
  if (!schoolId) return NextResponse.json({ error: "schoolId is required" }, { status: 400 });

  const auth = await opsAuth(req, schoolId, "GET", { perms: ["reports.view"] });
  if (!auth.ok) return auth.res;

  const card = await db.reportCard.findUnique({
    where: { id },
    include: {
      student: {
        select: {
          id: true, firstName: true, lastName: true, otherNames: true, nyansapoIndex: true,
          gender: true, dateOfBirth: true, guardianName: true, guardianPhone: true,
        },
      },
      term: { include: { academicYear: { select: { name: true, schoolId: true } } } },
      results: true,
    },
  });
  if (!card) return NextResponse.json({ error: "Report card not found" }, { status: 404 });
  if (card.term.academicYear.schoolId !== schoolId) {
    return NextResponse.json({ error: "Report card not found in this school" }, { status: 404 });
  }

  const [school, rows, enroll] = await Promise.all([
    db.school.findUnique({
      where: { id: schoolId },
      select: {
        id: true, name: true, code: true, motto: true, town: true, region: true,
        district: true, gpsAddress: true, phone: true, email: true, logoColor: true, curriculum: true,
      },
    }),
    db.termResult.findMany({
      where: { studentId: card.studentId, termId: card.termId },
      include: { subject: { select: { id: true, name: true, code: true } } },
      orderBy: { subject: { name: "asc" } },
    }),
    db.enrollment.findFirst({
      where: { studentId: card.studentId, status: "ACTIVE" },
      include: { classroom: { select: { id: true, name: true, classTeacherId: true } } },
    }),
  ]);

  const classTeacher = enroll?.classroom.classTeacherId
    ? await db.staff.findUnique({ where: { id: enroll.classroom.classTeacherId }, select: { fullName: true, title: true } })
    : null;

  return NextResponse.json({
    school,
    classTeacher: classTeacher?.fullName ?? null,
    classroom: enroll?.classroom ? { id: enroll.classroom.id, name: enroll.classroom.name } : null,
    card: {
      id: card.id,
      classroomName: card.classroomName,
      term: { id: card.term.id, name: card.term.name, sequence: card.term.sequence, yearName: card.term.academicYear.name },
      published: card.published,
      publishedAt: card.publishedAt,
      totalScore: card.totalScore,
      average: card.average,
      classPosition: card.classPosition,
      classSize: card.classSize,
      aggregate: card.aggregate,
      attendance: card.attendance,
      attendancePct: card.attendancePct,
      conduct: card.conduct,
      interest: card.interest,
      teacherRemark: card.teacherRemark,
      headRemark: card.headRemark,
      promotion: card.promotion,
      promoted: card.promoted,
    },
    student: card.student,
    // normalized wave-20 ledger rows
    rows: rows.map((r) => ({
      id: r.id,
      subjectName: r.subject.name,
      subjectCode: r.subject.code,
      classScore: r.classScore,
      examScore: r.examScore,
      total: r.total,
      grade: r.grade,
      position: r.position,
      remarks: r.remarks,
    })),
    // legacy rows (fallback for early-years / creche cards)
    legacyResults: card.results,
  });
}

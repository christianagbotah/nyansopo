import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

/* ─────────────────────────────────────────────────────────────
   GET /api/results/report-cards?schoolId&termId&classId — wave 20
   selector payload + results grid for the Results & Report Cards
   module (perm: reports.view).

   Returns the school header block (for the printable Ghana report
   sheet), every term/class of the school for the selectors, and —
   when termId+classId resolve — the compiled report cards plus the
   normalized TermResult ledger rows for that class in that term.
   Defaults to the school's current term when termId is omitted.
   ───────────────────────────────────────────────────────────── */

export async function GET(req: NextRequest) {
  const url = new URL(req.url);
  const q = (k: string) => url.searchParams.get(k) ?? "";
  const schoolId = q("schoolId");
  if (!schoolId) return NextResponse.json({ error: "schoolId is required" }, { status: 400 });

  const auth = await opsAuth(req, schoolId, "GET", { perms: ["reports.view"] });
  if (!auth.ok) return auth.res;

  const [school, years, classrooms] = await Promise.all([
    db.school.findUnique({
      where: { id: schoolId },
      select: {
        id: true, name: true, code: true, motto: true, town: true, region: true,
        district: true, gpsAddress: true, phone: true, email: true, logoColor: true, curriculum: true,
      },
    }),
    db.academicYear.findMany({
      where: { schoolId },
      orderBy: { startsAt: "desc" },
      include: { terms: { orderBy: { sequence: "asc" } } },
    }),
    db.classroom.findMany({
      where: { schoolId },
      orderBy: [{ gradeLevel: "asc" }, { name: "asc" }],
      select: { id: true, name: true, gradeLevel: true },
    }),
  ]);
  if (!school) return NextResponse.json({ error: "School not found" }, { status: 404 });

  const terms = years.flatMap((y) =>
    y.terms.map((t) => ({ id: t.id, name: t.name, sequence: t.sequence, yearName: y.name, isCurrent: t.isCurrent })),
  );
  const termId = q("termId") || terms.find((t) => t.isCurrent)?.id || terms[0]?.id || null;
  const classId = q("classId") || null;

  let cards: unknown[] = [];
  let results: unknown[] = [];
  let students: unknown[] = [];

  if (termId && classId) {
    const term = await db.term.findFirst({ where: { id: termId, academicYear: { schoolId } }, select: { id: true } });
    const classroom = await db.classroom.findFirst({ where: { id: classId, schoolId }, select: { id: true } });
    if (term && classroom) {
      const enroll = await db.enrollment.findMany({
        where: { classroomId: classId, status: "ACTIVE" },
        include: { student: { select: { id: true, firstName: true, lastName: true, otherNames: true, nyansapoIndex: true, gender: true } } },
        orderBy: { rollNo: "asc" },
      });
      students = enroll.map((e) => ({
        id: e.student.id,
        name: [e.student.lastName, e.student.firstName, e.student.otherNames].filter(Boolean).join(" "),
        indexNo: e.student.nyansapoIndex,
        gender: e.student.gender,
      }));
      const studentIds = enroll.map((e) => e.studentId);
      const [cardRows, resultRows] = await Promise.all([
        db.reportCard.findMany({
          where: { termId, studentId: { in: studentIds } },
          include: { student: { select: { firstName: true, lastName: true, otherNames: true, nyansapoIndex: true } } },
          orderBy: { classPosition: "asc" },
        }),
        db.termResult.findMany({
          where: { termId, classroomId: classId },
          include: { subject: { select: { id: true, name: true, code: true } } },
          orderBy: { subject: { name: "asc" } },
        }),
      ]);
      cards = cardRows.map((c) => ({
        id: c.id,
        studentId: c.studentId,
        studentName: [c.student.lastName, c.student.firstName, c.student.otherNames].filter(Boolean).join(" "),
        indexNo: c.student.nyansapoIndex,
        classroomName: c.classroomName,
        totalScore: c.totalScore,
        average: c.average,
        classPosition: c.classPosition,
        classSize: c.classSize,
        aggregate: c.aggregate,
        attendancePct: c.attendancePct,
        promoted: c.promoted,
        published: c.published,
        teacherRemark: c.teacherRemark,
        headRemark: c.headRemark,
      }));
      results = resultRows.map((r) => ({
        id: r.id,
        studentId: r.studentId,
        subjectId: r.subject.id,
        subjectName: r.subject.name,
        subjectCode: r.subject.code,
        classScore: r.classScore,
        examScore: r.examScore,
        total: r.total,
        grade: r.grade,
        position: r.position,
        remarks: r.remarks,
        published: r.published,
      }));
    }
  }

  return NextResponse.json({
    school,
    terms,
    classrooms,
    students,
    termId,
    classId,
    cards,
    results,
  });
}

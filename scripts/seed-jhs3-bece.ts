/* Give JHS 3 (B9) a full BECE subject load (9 subjects: ENG MAT SCI SOS RME GHL
   CRA ICT FRE) with class work + END_OF_TERM exams, so the BECE best-six
   aggregate can be demoed on terminal report sheets. */
import { PrismaClient } from "@prisma/client";
const db = new PrismaClient();

async function main() {
  const school = await db.school.findFirst({ where: { name: { contains: "Golden Excellence" } } });
  if (!school) throw new Error("GEA school not found");
  const year = await db.academicYear.findFirst({ where: { schoolId: school.id, isCurrent: true }, include: { terms: true } });
  const term = year?.terms.find((t) => t.isCurrent);
  const cls = await db.classroom.findFirst({ where: { schoolId: school.id, name: "JHS 3 (B9)" } });
  if (!term || !cls) throw new Error("term or JHS 3 classroom not found");

  const enrolled = await db.enrollment.findMany({ where: { classroomId: cls.id, status: "ACTIVE" } });
  const subjects = await db.subject.findMany({ where: { schoolId: school.id } });
  const want = ["ENG", "MAT", "SCI", "SOS", "RME", "GHL", "CRA", "ICT", "FRE"];
  let assessments = 0, marks = 0;
  for (const code of want) {
    const subj = subjects.find((s) => s.code === code);
    if (!subj) continue;
    // two class-work items (10 + 20 = 30) + one END_OF_TERM exam (70)
    for (const [title, type, max] of [["Class Exercise 1", "EXERCISE", 10], ["Mid-Term Quiz", "MID_TERM", 20]] as const) {
      const a = await db.assessment.create({ data: { classroomId: cls.id, schoolId: school.id, termId: term.id, subjectId: subj.id, title, type, maxScore: max, date: new Date() } });
      assessments++;
      for (const e of enrolled) {
        await db.mark.create({ data: { assessmentId: a.id, studentId: e.studentId, score: Math.max(2, Math.min(max, Math.round(max * (0.45 + Math.random() * 0.5)))) } });
        marks++;
      }
    }
    const ex = await db.assessment.create({ data: { classroomId: cls.id, schoolId: school.id, termId: term.id, subjectId: subj.id, title: `End-of-Term Exam — ${subj.name}`, type: "END_OF_TERM", maxScore: 70, date: new Date() } });
    assessments++;
    for (const e of enrolled) {
      await db.mark.create({ data: { assessmentId: ex.id, studentId: e.studentId, score: Math.max(16, Math.min(70, Math.round(70 * (0.4 + Math.random() * 0.55)))) } });
      marks++;
    }
  }
  console.log(`JHS 3: added ${assessments} assessments / ${marks} marks for ${enrolled.length} learners across ${want.length} subjects.`);
}

main().finally(() => db.$disconnect());

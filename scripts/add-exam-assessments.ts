/* Adds END_OF_TERM exam assessments (70 marks) for every GEA class that has
   class-work marks this term, so the 30/70 terminal report split is demoable. */
import { PrismaClient } from "@prisma/client";
const db = new PrismaClient();

async function main() {
  const school = await db.school.findFirst({ where: { name: { contains: "Golden Excellence" } } });
  if (!school) throw new Error("GEA school not found");
  const year = await db.academicYear.findFirst({ where: { schoolId: school.id, isCurrent: true }, include: { terms: true } });
  const term = year?.terms.find((t) => t.isCurrent) ?? year?.terms[0];
  if (!term) throw new Error("current term not found");

  const assessments = await db.assessment.findMany({
    where: { schoolId: school.id, termId: term.id },
    include: { subject: true, marks: true },
  });
  // group by classroom+subject
  const byKey: Record<string, { classroomId: string; subjectId: string; name: string; hasExam: boolean; students: Set<string> }> = {};
  for (const a of assessments) {
    const k = `${a.classroomId}:${a.subjectId}`;
    byKey[k] ??= { classroomId: a.classroomId, subjectId: a.subjectId, name: a.subject.name, hasExam: false, students: new Set() };
    if (a.type === "END_OF_TERM") byKey[k].hasExam = true;
    for (const m of a.marks) byKey[k].students.add(m.studentId);
  }
  let added = 0, marks = 0;
  for (const k of Object.keys(byKey)) {
    const g = byKey[k];
    if (g.hasExam) continue;
    const a = await db.assessment.create({
      data: { classroomId: g.classroomId, schoolId: school.id, termId: term.id, subjectId: g.subjectId, title: `End-of-Term Exam — ${g.name}`, type: "END_OF_TERM", maxScore: 70, date: new Date() },
    });
    added++;
    for (const studentId of g.students) {
      const score = Math.max(14, Math.min(70, Math.round(70 * (0.4 + Math.random() * 0.55))));
      await db.mark.create({ data: { assessmentId: a.id, studentId, score } });
      marks++;
    }
  }
  console.log(`Added ${added} END_OF_TERM assessments with ${marks} marks for term "${term.name}".`);
}

main().finally(() => db.$disconnect());

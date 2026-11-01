/* Demo online exams + notices (iEduPro online_exam + noticeboard parity).
   Idempotent — safe to re-run. Run: bun prisma/seed-exams.ts */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

async function main() {
  const schools = await db.school.findMany({ select: { id: true, code: true } });
  const gea = schools.find((s) => s.code === "GEA");
  if (!gea) { console.log("no GEA school — skip"); return; }
  const schoolId = gea.id;

  const existing = await db.onlineExam.count({ where: { schoolId } });
  if (existing > 0) { console.log(`⏭️  ${existing} exams already seeded`); return; }

  const jhs3 = await db.classroom.findFirst({ where: { schoolId, name: { contains: "JHS 3" } } });
  const b4 = await db.classroom.findFirst({ where: { schoolId, name: { contains: "Basic 4" } } });

  // 1. published exam for JHS 3 (maths, fractions) with some attempts
  const exam1 = await db.onlineExam.create({
    data: {
      schoolId, classroomId: jhs3?.id ?? null, subjectName: "Mathematics",
      title: "Fractions — end-of-unit check", description: "Answer all questions. One option each.",
      durationMins: 20, status: "PUBLISHED", createdByName: "Mr. Kwame Duah",
      questions: {
        create: [
          { prompt: "Simplify 8/12 to its lowest terms.", optionA: "2/3", optionB: "4/6", optionC: "3/4", optionD: "1/2", correctOption: "A", marks: 2, sortOrder: 0 },
          { prompt: "Which fraction is equivalent to 0.75?", optionA: "3/5", optionB: "7/10", optionC: "3/4", optionD: "2/3", correctOption: "C", marks: 2, sortOrder: 1 },
          { prompt: "1/2 + 1/4 = ?", optionA: "2/6", optionB: "3/4", optionC: "1/6", optionD: "2/4", correctOption: "B", marks: 2, sortOrder: 2 },
          { prompt: "Convert 5/4 to a mixed number.", optionA: "1 1/4", optionB: "1 1/2", optionC: "2 1/4", optionD: "4/5", correctOption: "A", marks: 2, sortOrder: 3 },
        ],
      },
    },
  });

  // a few JHS 3 learners sit it (skip the demo student-portal learner — her
  // attempt is left unsent so the interactive submit flow stays testable)
  const jhs3Enrolments = await db.enrollment.findMany({
    where: { schoolId, status: "ACTIVE", classroomId: jhs3?.id, student: { guardianEmail: { not: "student@nyansapo.gh" } } },
    include: { student: true }, take: 6,
  });
  // read the questions back and submit attempts
  let sat = 0;
  const q1 = await db.onlineExamQuestion.findMany({ where: { examId: exam1.id }, orderBy: { sortOrder: "asc" } });
  for (const [i, en] of jhs3Enrolments.entries()) {
    const answers: Record<string, string> = {};
    q1.forEach((q, qi) => {
      // learner 0&1 perfect, 2 half, 3 one wrong
      const bias = i % 4;
      answers[String(qi)] = bias === 0 || bias === 1 ? q.correctOption : qi % 2 === 0 ? q.correctOption : (q.correctOption === "A" ? "B" : "A");
    });
    const score = q1.reduce((s, q, qi) => s + (answers[String(qi)] === q.correctOption ? q.marks : 0), 0);
    await db.onlineExamAttempt.create({ data: { examId: exam1.id, studentId: en.studentId, schoolId, answers: JSON.stringify(answers), score } });
    sat++;
  }
  console.log(`✅ published exam for ${jhs3?.name} — ${q1.length} questions, ${sat} attempts`);

  // 2. closed exam for Basic 4 (english)
  const exam2 = await db.onlineExam.create({
    data: {
      schoolId, classroomId: b4?.id ?? null, subjectName: "English Language",
      title: "Comprehension skills check", description: null,
      durationMins: 15, status: "CLOSED", createdByName: "Madam Grace Mensah",
      questions: {
        create: [
          { prompt: "Choose the correct spelling.", optionA: "recieve", optionB: "receive", optionC: "receeve", optionD: "recive", correctOption: "B", marks: 1, sortOrder: 0 },
          { prompt: "\"The book is ___ the table.\" Pick the right preposition.", optionA: "in", optionB: "of", optionC: "on", optionD: "at", correctOption: "C", marks: 1, sortOrder: 1 },
          { prompt: "Past tense of \"go\".", optionA: "goed", optionB: "gone", optionC: "went", optionD: "going", correctOption: "C", marks: 1, sortOrder: 2 },
        ],
      },
    },
  });
  const b4Enrolments = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE", classroomId: b4?.id }, include: { student: true }, take: 8 });
  const q2 = await db.onlineExamQuestion.findMany({ where: { examId: exam2.id }, orderBy: { sortOrder: "asc" } });
  let sat2 = 0;
  for (const [i, en] of b4Enrolments.entries()) {
    const answers: Record<string, string> = {};
    q2.forEach((q, qi) => { answers[String(qi)] = (i + qi) % 3 === 0 ? q.correctOption : q.correctOption === "B" ? "A" : "B"; });
    const score = q2.reduce((s, q, qi) => s + (answers[String(qi)] === q.correctOption ? q.marks : 0), 0);
    await db.onlineExamAttempt.create({ data: { examId: exam2.id, studentId: en.studentId, schoolId, answers: JSON.stringify(answers), score } });
    sat2++;
  }
  console.log(`✅ closed exam for ${b4?.name} — ${q2.length} questions, ${sat2} attempts`);

  // 3. a draft
  await db.onlineExam.create({
    data: {
      schoolId, classroomId: null, subjectName: "Integrated Science",
      title: "Photosynthesis quick check (draft)", description: "Draft — not visible to learners yet.",
      durationMins: 10, status: "DRAFT", createdByName: "Mr. Kwame Duah",
      questions: { create: [{ prompt: "Plants make food using…", optionA: "sunlight", optionB: "moonlight", optionC: "streetlight", optionD: "candlelight", correctOption: "A", marks: 1, sortOrder: 0 }] },
    },
  });
  console.log("✅ draft exam seeded");

  // 4. a couple of school notices
  const noticeCount = await db.announcement.count({ where: { schoolId } });
  if (noticeCount === 0) {
    await db.announcement.createMany({
      data: [
        { schoolId, title: "PTA meeting — Friday 4pm", body: "Dear parents, our termly PTA meeting holds in the school hall this Friday at 4pm. Attendance is strongly encouraged.", audience: "PARENTS", category: "CALENDAR", priority: "HIGH", postedByName: "Madam Akosua Danso" },
        { schoolId, title: "Inter-schools sports festival", body: "GEA competes in the zonal athletics festival next Wednesday. Kits go home with the team on Tuesday.", audience: "PARENTS", category: "GENERAL", priority: "NORMAL", postedByName: "Mr. Yaw Frimpong" },
      ],
    });
    console.log("✅ 2 school notices seeded");
  }

  console.log("🎉 exam seed done.");
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

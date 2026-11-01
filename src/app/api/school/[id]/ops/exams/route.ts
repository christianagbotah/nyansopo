import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { getUser } from "@/lib/session";

// ── ONLINE EXAMS (iEduPro online_exam parity) ──
// GET  — list exams (with questions & attempts); ?classroomId= filters.
//        Learners (STUDENT) see only PUBLISHED papers for their own class,
//        with the answer key stripped.
// POST — save-exam | publish | close | delete (staff) · submit-attempt (learner)
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;

  // learner scope first: students reach their own class's open papers
  const direct = await getUser(req);
  if (direct && direct.role === "STUDENT") {
    if (direct.schoolId !== schoolId && !(direct.schoolIds ?? []).includes(schoolId))
      return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    const myEn = direct.studentId
      ? await db.enrollment.findFirst({ where: { studentId: direct.studentId, schoolId, status: "ACTIVE" } })
      : null;
    const classroomId = myEn?.classroomId ?? null;
    const exams = await db.onlineExam.findMany({
      where: { schoolId, status: "PUBLISHED", OR: [{ classroomId: null }, ...(classroomId ? [{ classroomId }] : [])] },
      include: { classroom: { select: { name: true } }, questions: { orderBy: { sortOrder: "asc" } }, attempts: { where: { studentId: direct.studentId ?? "" } } },
      orderBy: { createdAt: "desc" },
    });
    return NextResponse.json({
      exams: exams.map((e) => ({
        id: e.id, title: e.title, subjectName: e.subjectName, description: e.description,
        classroom: e.classroom?.name ?? null, classroomId: e.classroomId,
        durationMins: e.durationMins, scheduledAt: e.scheduledAt, status: e.status,
        questionCount: e.questions.length,
        totalMarks: e.questions.reduce((s, q) => s + q.marks, 0),
        // answer key stripped — learners never see correctOption
        questions: e.questions.map((q) => ({ id: q.id, prompt: q.prompt, optionA: q.optionA, optionB: q.optionB, optionC: q.optionC, optionD: q.optionD })),
        attempts: e.attempts.map((a) => ({ studentId: a.studentId, student: "you", score: a.score, max: e.questions.reduce((s, q) => s + q.marks, 0), submittedAt: a.submittedAt })),
        avgScore: null,
        createdByName: e.createdByName, createdAt: e.createdAt,
      })),
    });
  }

  const auth = await opsAuth(req, schoolId, "GET", { perms: ["exams.online"] });
  if (!auth.ok) return auth.res;

  const url = new URL(req.url);
  const classroomId = url.searchParams.get("classroomId");
  const exams = await db.onlineExam.findMany({
    where: { schoolId, ...(classroomId ? { classroomId } : {}) },
    include: {
      classroom: { select: { name: true } },
      questions: { orderBy: { sortOrder: "asc" } },
      attempts: { include: { student: { select: { firstName: true, lastName: true } } } },
    },
    orderBy: { createdAt: "desc" },
  });

  return NextResponse.json({
    exams: exams.map((e) => {
      const totalMarks = e.questions.reduce((s, q) => s + q.marks, 0);
      const attempts = e.attempts.map((a) => ({
        id: a.id, studentId: a.studentId, student: `${a.student.firstName} ${a.student.lastName}`,
        score: a.score, max: totalMarks, submittedAt: a.submittedAt,
      }));
      return {
        id: e.id, title: e.title, subjectName: e.subjectName, description: e.description,
        classroom: e.classroom?.name ?? null, classroomId: e.classroomId,
        durationMins: e.durationMins, scheduledAt: e.scheduledAt, status: e.status,
        createdByName: e.createdByName, createdAt: e.createdAt,
        questionCount: e.questions.length, totalMarks,
        avgScore: attempts.length ? Math.round(attempts.reduce((s, a) => s + (a.score ?? 0), 0) / attempts.length) : null,
        questions: e.questions.map((q) => ({ id: q.id, prompt: q.prompt, optionA: q.optionA, optionB: q.optionB, optionC: q.optionC, optionD: q.optionD, correctOption: q.correctOption, marks: q.marks })),
        attempts,
      };
    }),
  });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;

  // learner scope: students only ever submit-attempt — no staff permission needed
  if (action === "submit-attempt") {
    const learner = await getUser(req);
    if (!learner) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
    if (learner.role === "STUDENT") {
      if (learner.schoolId !== schoolId && !(learner.schoolIds ?? []).includes(schoolId))
        return NextResponse.json({ error: "Forbidden" }, { status: 403 });
      const { examId, studentId, answers } = body;
      if (!examId || !studentId || !answers || typeof answers !== "object") return NextResponse.json({ error: "examId, studentId and answers required" }, { status: 400 });
      if (learner.studentId !== studentId) return NextResponse.json({ error: "You may only sit your own exam" }, { status: 403 });
      const exam = await db.onlineExam.findUnique({ where: { id: examId }, include: { questions: { orderBy: { sortOrder: "asc" } } } });
      if (!exam || exam.schoolId !== schoolId) return NextResponse.json({ error: "Exam not found" }, { status: 404 });
      if (exam.status !== "PUBLISHED") return NextResponse.json({ error: "This exam is not open" }, { status: 400 });
      if (exam.classroomId) {
        const en = await db.enrollment.findFirst({ where: { studentId, schoolId, status: "ACTIVE" } });
        if (en?.classroomId !== exam.classroomId) return NextResponse.json({ error: "This exam is for another class" }, { status: 403 });
      }
      const existing = await db.onlineExamAttempt.findUnique({ where: { examId_studentId: { examId, studentId } } });
      if (existing) return NextResponse.json({ error: "Already submitted" }, { status: 400 });
      let score = 0, max = 0;
      for (const q of exam.questions) {
        max += q.marks;
        if (answers[String(q.sortOrder)] === q.correctOption) score += q.marks;
      }
      const attempt = await db.onlineExamAttempt.create({ data: { examId, studentId, schoolId, answers: JSON.stringify(answers), score } });
      return NextResponse.json({ attempt: { ...attempt, max }, score, max });
    }
    // staff submitting on behalf isn't supported — fall through to the staff gate
  }

  // staff actions (exams.online)
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["exams.online"] });
  if (!auth.ok) return auth.res;

  if (action === "save-exam") {
    const { id, title, subjectName, description, classroomId, durationMins, scheduledAt, questions } = body;
    if (!title?.trim() || !subjectName?.trim()) return NextResponse.json({ error: "Title and subject are required" }, { status: 400 });
    if (!Array.isArray(questions) || !questions.length) return NextResponse.json({ error: "Add at least one question" }, { status: 400 });
    for (const [i, q] of questions.entries()) {
      if (!q.prompt?.trim() || !q.optionA?.trim() || !q.optionB?.trim() || !q.optionC?.trim() || !q.optionD?.trim())
        return NextResponse.json({ error: `Question ${i + 1} is missing a part` }, { status: 400 });
      if (!["A", "B", "C", "D"].includes(q.correctOption)) return NextResponse.json({ error: `Question ${i + 1} needs a correct option (A–D)` }, { status: 400 });
    }
    const cls = classroomId ? await db.classroom.findUnique({ where: { id: classroomId } }) : null;
    if (classroomId && (!cls || cls.schoolId !== schoolId)) return NextResponse.json({ error: "Classroom not found" }, { status: 404 });

    const data = {
      title: title.trim(), subjectName: subjectName.trim(), description: description?.trim() ?? null,
      classroomId: cls?.id ?? null, durationMins: Number(durationMins) || 30,
      scheduledAt: scheduledAt ? new Date(scheduledAt) : null,
    };
    const exam = id
      ? await db.onlineExam.update({ where: { id }, data })
      : await db.onlineExam.create({ data: { ...data, schoolId, createdByName: auth.user.fullName } });
    // rewrite the question set (drafts are editable; publishing snapshots the key)
    await db.onlineExamQuestion.deleteMany({ where: { examId: exam.id } });
    await db.onlineExamQuestion.createMany({
      data: questions.map((q: any, i: number) => ({
        examId: exam.id, prompt: q.prompt.trim(), optionA: q.optionA.trim(), optionB: q.optionB.trim(),
        optionC: q.optionC.trim(), optionD: q.optionD.trim(), correctOption: q.correctOption,
        marks: Number(q.marks) || 1, sortOrder: i,
      })),
    });
    return NextResponse.json({ exam: { ...exam, questionCount: questions.length } });
  }

  if (action === "publish") {
    const exam = await db.onlineExam.findUnique({ where: { id: body.id }, include: { questions: true } });
    if (!exam || exam.schoolId !== schoolId) return NextResponse.json({ error: "Exam not found" }, { status: 404 });
    if (!exam.questions.length) return NextResponse.json({ error: "Add questions before publishing" }, { status: 400 });
    const updated = await db.onlineExam.update({ where: { id: exam.id }, data: { status: "PUBLISHED" } });
    return NextResponse.json({ exam: updated });
  }

  if (action === "close") {
    const exam = await db.onlineExam.findUnique({ where: { id: body.id } });
    if (!exam || exam.schoolId !== schoolId) return NextResponse.json({ error: "Exam not found" }, { status: 404 });
    const updated = await db.onlineExam.update({ where: { id: exam.id }, data: { status: "CLOSED" } });
    return NextResponse.json({ exam: updated });
  }

  if (action === "delete") {
    const exam = await db.onlineExam.findUnique({ where: { id: body.id } });
    if (!exam || exam.schoolId !== schoolId) return NextResponse.json({ error: "Exam not found" }, { status: 404 });
    if (exam.status === "PUBLISHED") return NextResponse.json({ error: "Close the exam before deleting it" }, { status: 400 });
    await db.onlineExam.delete({ where: { id: exam.id } });
    return NextResponse.json({ ok: true });
  }

  // learner submits answers: { [sortOrder]: "A"|"B"|"C"|"D" } — auto-marked
  if (action === "submit-attempt") {
    const { examId, studentId, answers } = body;
    if (!examId || !studentId || !answers || typeof answers !== "object") return NextResponse.json({ error: "examId, studentId and answers required" }, { status: 400 });
    const exam = await db.onlineExam.findUnique({ where: { id: examId }, include: { questions: { orderBy: { sortOrder: "asc" } } } });
    if (!exam || exam.schoolId !== schoolId) return NextResponse.json({ error: "Exam not found" }, { status: 404 });
    if (exam.status !== "PUBLISHED") return NextResponse.json({ error: "This exam is not open" }, { status: 400 });
    // the attempt must belong to the signed-in learner (students only pass their own id)
    if (auth.user.role === "STUDENT" && auth.user.studentId !== studentId)
      return NextResponse.json({ error: "You may only sit your own exam" }, { status: 403 });
    // class scope: the exam may target a class the learner must be in
    if (exam.classroomId) {
      const en = await db.enrollment.findFirst({ where: { studentId, schoolId, status: "ACTIVE" } });
      if (en?.classroomId !== exam.classroomId) return NextResponse.json({ error: "This exam is for another class" }, { status: 403 });
    }
    const existing = await db.onlineExamAttempt.findUnique({ where: { examId_studentId: { examId, studentId } } });
    if (existing) return NextResponse.json({ error: "Already submitted" }, { status: 400 });
    // auto-mark
    let score = 0, max = 0;
    for (const q of exam.questions) {
      max += q.marks;
      if (answers[String(q.sortOrder)] === q.correctOption) score += q.marks;
    }
    const attempt = await db.onlineExamAttempt.create({
      data: { examId, studentId, schoolId, answers: JSON.stringify(answers), score },
    });
    return NextResponse.json({ attempt: { ...attempt, max }, score, max });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

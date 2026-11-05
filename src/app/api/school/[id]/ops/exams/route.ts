import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { getUser } from "@/lib/session";

// ── ONLINE EXAMS (iEduPro online_exam parity + CI3 question types) ──
// GET  — list exams (with questions & attempts); ?classroomId= filters.
//        Learners (STUDENT) see only PUBLISHED papers for their own class,
//        with the answer key stripped.
//        ?monitor=<examId> — live monitor: roster + per-learner state
//        (not started / in progress / submitted with score).
// POST — save-exam | publish | close | delete (staff)
//        · start-attempt (learner opens the paper — live-monitor signal)
//        · submit-attempt (learner) — auto-marked per question type:
//          MCQ exact option · TRUE/FALSE exact · FILL_BLANKS all-or-nothing
//          word-by-word (case-insensitive) — CI3 parity.

const QUESTION_TYPES = ["MCQ", "TRUE_FALSE", "FILL_BLANKS"] as const;

/** FILL_BLANKS: normalise a student/teacher answer list — split on commas,
 *  trim + lowercase each word (CI3 marks case-insensitively, exact spelling). */
function normalizeBlankWords(v: unknown): string[] {
  const arr = Array.isArray(v) ? v : typeof v === "string" ? v.split(",") : [];
  return arr.map((x) => String(x).trim().toLowerCase()).filter((x) => x !== "");
}

function countBlanks(prompt: string): number {
  return prompt.split("-").length - 1;
}

/** auto-mark one question against a submitted answer */
function markQuestion(q: { type: string; correctOption: string; blankAnswers: string | null }, submitted: unknown): boolean {
  if (q.type === "TRUE_FALSE") return submitted === q.correctOption; // "TRUE" | "FALSE"
  if (q.type === "FILL_BLANKS") {
    const correct = normalizeBlankWords(JSON.parse(q.blankAnswers ?? "[]"));
    const got = normalizeBlankWords(submitted);
    return correct.length === got.length && correct.every((w, i) => w === got[i]);
  }
  return submitted === q.correctOption; // MCQ: A | B | C | D
}

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;

  // live monitor (staff): roster + attempt states for one exam
  const url = new URL(req.url);
  const monitorId = url.searchParams.get("monitor");
  if (monitorId) {
    const auth = await opsAuth(req, schoolId, "GET", { perms: ["exams.online"] });
    if (!auth.ok) return auth.res;
    const exam = await db.onlineExam.findUnique({
      where: { id: monitorId },
      include: { classroom: { select: { name: true } }, questions: { select: { marks: true } }, attempts: true },
    });
    if (!exam || exam.schoolId !== schoolId) return NextResponse.json({ error: "Exam not found" }, { status: 404 });
    // roster = class enrolments (scoped exams) ∪ every learner with an attempt
    // (all-classes exams list learners as they start)
    const enrolled = exam.classroomId
      ? await db.enrollment.findMany({ where: { classroomId: exam.classroomId, status: "ACTIVE" }, include: { student: { select: { id: true, firstName: true, lastName: true } } } })
      : [];
    const attemptStudents = await db.student.findMany({
      where: { id: { in: exam.attempts.map((a) => a.studentId) } },
      select: { id: true, firstName: true, lastName: true },
    });
    const rosterMap = new Map<string, { id: string; name: string }>();
    for (const e of enrolled) rosterMap.set(e.student.id, { id: e.student.id, name: `${e.student.firstName} ${e.student.lastName}` });
    for (const st of attemptStudents) if (!rosterMap.has(st.id)) rosterMap.set(st.id, { id: st.id, name: `${st.firstName} ${st.lastName}` });
    const totalMarks = exam.questions.reduce((s, q) => s + q.marks, 0);
    return NextResponse.json({
      exam: { id: exam.id, title: exam.title, subjectName: exam.subjectName, status: exam.status, classroom: exam.classroom?.name ?? null, durationMins: exam.durationMins, totalMarks },
      roster: [...rosterMap.values()].map((st) => {
        const a = exam.attempts.find((x) => x.studentId === st.id);
        return {
          studentId: st.id, name: st.name,
          state: !a ? "NOT_STARTED" : a.score == null ? "IN_PROGRESS" : "SUBMITTED",
          startedAt: a?.startedAt ?? null, submittedAt: a?.score == null ? null : a.submittedAt,
          score: a?.score ?? null, max: totalMarks,
        };
      }),
    });
  }

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
        // answer key stripped — learners never see correctOption or blankAnswers
        questions: e.questions.map((q) => ({ id: q.id, type: q.type, prompt: q.prompt, optionA: q.optionA, optionB: q.optionB, optionC: q.optionC, optionD: q.optionD })),
        attempts: e.attempts.map((a) => ({ studentId: a.studentId, student: "you", score: a.score, max: e.questions.reduce((s, q) => s + q.marks, 0), submittedAt: a.submittedAt })),
        avgScore: null,
        createdByName: e.createdByName, createdAt: e.createdAt,
      })),
    });
  }

  const auth = await opsAuth(req, schoolId, "GET", { perms: ["exams.online"] });
  if (!auth.ok) return auth.res;

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
        score: a.score, max: totalMarks, startedAt: a.startedAt, submittedAt: a.submittedAt,
      }));
      return {
        id: e.id, title: e.title, subjectName: e.subjectName, description: e.description,
        classroom: e.classroom?.name ?? null, classroomId: e.classroomId,
        durationMins: e.durationMins, scheduledAt: e.scheduledAt, status: e.status,
        createdByName: e.createdByName, createdAt: e.createdAt,
        questionCount: e.questions.length, totalMarks,
        avgScore: attempts.length ? Math.round(attempts.reduce((s, a) => s + (a.score ?? 0), 0) / attempts.length) : null,
        questions: e.questions.map((q) => ({
          id: q.id, type: q.type, prompt: q.prompt, optionA: q.optionA, optionB: q.optionB, optionC: q.optionC, optionD: q.optionD,
          correctOption: q.correctOption, blankAnswers: q.blankAnswers, marks: q.marks,
        })),
        attempts,
      };
    }),
  });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;

  // learner scope: students start / submit their own attempts — no staff permission needed
  if (action === "start-attempt" || action === "submit-attempt") {
    const learner = await getUser(req);
    if (learner && learner.role === "STUDENT") {
      if (learner.schoolId !== schoolId && !(learner.schoolIds ?? []).includes(schoolId))
        return NextResponse.json({ error: "Forbidden" }, { status: 403 });
      const { examId, studentId } = body;
      if (!examId || !studentId) return NextResponse.json({ error: "examId and studentId required" }, { status: 400 });
      if (learner.studentId !== studentId) return NextResponse.json({ error: "You may only sit your own exam" }, { status: 403 });
      const exam = await db.onlineExam.findUnique({ where: { id: examId }, include: { questions: { orderBy: { sortOrder: "asc" } } } });
      if (!exam || exam.schoolId !== schoolId) return NextResponse.json({ error: "Exam not found" }, { status: 404 });
      if (exam.status !== "PUBLISHED") return NextResponse.json({ error: "This exam is not open" }, { status: 400 });
      if (exam.classroomId) {
        const en = await db.enrollment.findFirst({ where: { studentId, schoolId, status: "ACTIVE" } });
        if (en?.classroomId !== exam.classroomId) return NextResponse.json({ error: "This exam is for another class" }, { status: 403 });
      }
      const existing = await db.onlineExamAttempt.findUnique({ where: { examId_studentId: { examId, studentId } } });

      if (action === "start-attempt") {
        // opening the paper — live-monitor signal; no-op if already started/submitted
        if (existing) return NextResponse.json({ state: existing.score == null ? "IN_PROGRESS" : "SUBMITTED" });
        await db.onlineExamAttempt.create({ data: { examId, studentId, schoolId, answers: "{}", score: null, startedAt: new Date() } });
        return NextResponse.json({ state: "IN_PROGRESS" });
      }

      // submit-attempt
      const answers = body.answers;
      if (!answers || typeof answers !== "object") return NextResponse.json({ error: "answers required" }, { status: 400 });
      if (existing && existing.score != null) return NextResponse.json({ error: "Already submitted" }, { status: 400 });
      let score = 0, max = 0;
      for (const q of exam.questions) {
        max += q.marks;
        if (markQuestion(q, answers[String(q.sortOrder)])) score += q.marks;
      }
      const payload = { answers: JSON.stringify(answers), score, submittedAt: new Date() };
      const attempt = existing
        ? await db.onlineExamAttempt.update({ where: { id: existing.id }, data: payload })
        : await db.onlineExamAttempt.create({ data: { examId, studentId, schoolId, startedAt: new Date(), ...payload } });
      return NextResponse.json({ attempt: { ...attempt, max }, score, max });
    }
    // staff submitting isn't supported — fall through to the staff gate
  }

  // staff actions (exams.online)
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["exams.online"] });
  if (!auth.ok) return auth.res;

  if (action === "save-exam") {
    const { id, title, subjectName, description, classroomId, durationMins, scheduledAt, questions } = body;
    if (!title?.trim() || !subjectName?.trim()) return NextResponse.json({ error: "Title and subject are required" }, { status: 400 });
    if (!Array.isArray(questions) || !questions.length) return NextResponse.json({ error: "Add at least one question" }, { status: 400 });
    for (const [i, q] of questions.entries()) {
      const type = QUESTION_TYPES.includes(q.type) ? q.type : "MCQ";
      if (!q.prompt?.trim()) return NextResponse.json({ error: `Question ${i + 1} is missing its prompt` }, { status: 400 });
      if (type === "MCQ") {
        if (!q.optionA?.trim() || !q.optionB?.trim() || !q.optionC?.trim() || !q.optionD?.trim())
          return NextResponse.json({ error: `Question ${i + 1} is missing an option (A–D)` }, { status: 400 });
        if (!["A", "B", "C", "D"].includes(q.correctOption)) return NextResponse.json({ error: `Question ${i + 1} needs a correct option (A–D)` }, { status: 400 });
      } else if (type === "TRUE_FALSE") {
        if (!["TRUE", "FALSE"].includes(q.correctOption)) return NextResponse.json({ error: `Question ${i + 1} needs the correct answer — True or False` }, { status: 400 });
      } else {
        // FILL_BLANKS: '-' marks each blank in the prompt; answers are comma-separated, in blank order
        const blanks = countBlanks(q.prompt);
        const words = normalizeBlankWords(q.blankAnswers);
        if (blanks < 1) return NextResponse.json({ error: `Question ${i + 1}: mark each blank with a hyphen "-" in the prompt` }, { status: 400 });
        if (!words.length) return NextResponse.json({ error: `Question ${i + 1}: give the accepted answer words, comma-separated` }, { status: 400 });
        if (words.length !== blanks)
          return NextResponse.json({ error: `Question ${i + 1}: ${blanks} blank${blanks > 1 ? "s" : ""} in the prompt but ${words.length} answer word${words.length > 1 ? "s" : ""} — they must match one-to-one, in order` }, { status: 400 });
      }
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
      data: questions.map((q: any, i: number) => {
        const type = QUESTION_TYPES.includes(q.type) ? q.type : "MCQ";
        return {
          examId: exam.id, prompt: q.prompt.trim(), type,
          optionA: type === "MCQ" ? q.optionA.trim() : null,
          optionB: type === "MCQ" ? q.optionB.trim() : null,
          optionC: type === "MCQ" ? q.optionC.trim() : null,
          optionD: type === "MCQ" ? q.optionD.trim() : null,
          correctOption: type === "TRUE_FALSE" ? (["TRUE", "FALSE"].includes(q.correctOption) ? q.correctOption : "TRUE") : type === "FILL_BLANKS" ? "-" : q.correctOption,
          blankAnswers: type === "FILL_BLANKS" ? JSON.stringify(normalizeBlankWords(q.blankAnswers)) : null,
          marks: Number(q.marks) || 1, sortOrder: i,
        };
      }),
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
    // closing finalises in-progress attempts so no learner stays "in progress" forever
    await db.onlineExamAttempt.updateMany({ where: { examId: exam.id, score: null }, data: { submittedAt: new Date() } });
    const updated = await db.onlineExam.update({ where: { id: exam.id }, data: { status: "CLOSED" } });
    return NextResponse.json({ exam: updated });
  }

  if (action === "delete") {
    const exam = await db.onlineExam.findUnique({ where: { id: body.id } });
    if (!exam || exam.schoolId !== schoolId) return NextResponse.json({ error: "Exam not found" }, { status: 404 });
    if (exam.status === "PUBLISHED") return NextResponse.json({ error: "Close the exam before deleting it" }, { status: 400 });
    // questions and attempts reference the paper — clear them first
    await db.onlineExamAttempt.deleteMany({ where: { examId: exam.id } });
    await db.onlineExamQuestion.deleteMany({ where: { examId: exam.id } });
    await db.onlineExam.delete({ where: { id: exam.id } });
    return NextResponse.json({ ok: true });
  }

  // learner submits answers: { [sortOrder]: "A" | "TRUE" | "accra,ghana" } — auto-marked
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
    if (existing && existing.score != null) return NextResponse.json({ error: "Already submitted" }, { status: 400 });
    // auto-mark per question type
    let score = 0, max = 0;
    for (const q of exam.questions) {
      max += q.marks;
      if (markQuestion(q, answers[String(q.sortOrder)])) score += q.marks;
    }
    const payload = { answers: JSON.stringify(answers), score, submittedAt: new Date() };
    const attempt = existing
      ? await db.onlineExamAttempt.update({ where: { id: existing.id }, data: payload })
      : await db.onlineExamAttempt.create({ data: { examId, studentId, schoolId, startedAt: new Date(), ...payload } });
    return NextResponse.json({ attempt: { ...attempt, max }, score, max });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

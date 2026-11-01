import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendSms } from "@/lib/sms";
import { gradeFor } from "@/lib/grading";

/* ── Academic parity batch (iEduPro / CI3 parity) ────────────────
   GET  ?tab=portfolio|rawgrades|cumulative|papers|marks-sms (default: bootstrap)
   POST actions: portfolio.save / portfolio.aggregate / rawgrade.create|update|delete /
                 paper.create|update|delete / marks-sms.send                       */

const TYPE_LABELS: Record<string, string> = {
  EXERCISE: "Class Exercise", HOMEWORK: "Homework", PROJECT: "Project", QUIZ: "Quiz",
  GROUP_WORK: "Group Work", MID_TERM: "Mid-Term", END_OF_TERM: "End-of-Term Exam",
};

const round2 = (x: number) => Math.round(x * 100) / 100;
const ymd = (d: Date) => d.toISOString().slice(0, 10);

/** Monday (UTC) of an ISO week string like "2026-W42". */
function mondayOfISOWeek(week: string | null | undefined): Date | null {
  const m = /^(\d{4})-W(\d{1,2})$/.exec(week ?? "");
  if (!m) return null;
  const year = Number(m[1]);
  const wk = Number(m[2]);
  if (wk < 1 || wk > 53) return null;
  const jan4 = new Date(Date.UTC(year, 0, 4)); // Jan 4 always falls in ISO week 1
  const monday = new Date(jan4);
  monday.setUTCDate(jan4.getUTCDate() - ((jan4.getUTCDay() + 6) % 7) + (wk - 1) * 7);
  return monday;
}

type RawGrade = { id: string; name: string; gradePoint: string; gradePointNumeric: number; markFrom: number; markUpto: number; comment: string | null; sortOrder: number };

/** CI3 crud_model::get_grade — first band with markFrom ≤ pct ≤ markUpto. */
function rawGradeFor(grades: RawGrade[], pct: number): RawGrade | null {
  return grades.find((g) => pct >= g.markFrom && pct <= g.markUpto) ?? null;
}

const titleCase = (s: string) => s.toLowerCase().replace(/\b\w/g, (c) => c.toUpperCase());

/** CI3 exam-marks SMS subject abbreviations (Admin.php exam_marks_sms). */
function abbreviateSubject(name: string): string {
  const first = name.trim().split(/\s+/)[0]?.toLowerCase() ?? "";
  const map: Record<string, string> = {
    religious: "R.M.E", english: "English", information: "ICT", health: "H&Safety",
    citizenship: "C.E", mathematics: "Maths", creative: "C.A", numeracy: "Numeracy",
    natural: "Science", integrated: "Science", our: "O.W.O.P", physical: "P.E",
  };
  return map[first] ?? name;
}

/** Per-subject totals for a class+term — the exact 30/70 composition the reports
 *  module uses (class work 30 · end-of-term exam 70, single-component fallback). */
async function termSubjectTotals(classroomId: string, termId: string) {
  const assessments = await db.assessment.findMany({ where: { classroomId, termId }, include: { subject: true, marks: true } });
  const bySubject: Record<string, { got: Record<string, number>; max: number; examGot: Record<string, number>; examMax: number }> = {};
  for (const a of assessments) {
    const key = a.subject.name;
    bySubject[key] ??= { got: {}, max: 0, examGot: {}, examMax: 0 };
    const isExam = a.type === "END_OF_TERM";
    const got = isExam ? bySubject[key].examGot : bySubject[key].got;
    for (const m of a.marks) {
      if (m.score === null) continue;
      got[m.studentId] = (got[m.studentId] ?? 0) + m.score;
    }
    if (isExam) bySubject[key].examMax += a.maxScore;
    else bySubject[key].max += a.maxScore;
  }
  const subjects = Object.keys(bySubject).sort();
  const totalFor = (subject: string, studentId: string): number | null => {
    const s = bySubject[subject];
    if (!s) return null;
    const classPct = s.max > 0 && s.got[studentId] !== undefined ? s.got[studentId] / s.max : null;
    const examPct = s.examMax > 0 && s.examGot[studentId] !== undefined ? s.examGot[studentId] / s.examMax : null;
    if (classPct !== null && examPct !== null) return Math.round(classPct * 30) + Math.round(examPct * 70);
    const pct = classPct ?? examPct;
    return pct !== null ? Math.round(pct * 100) : null;
  };
  return { subjects, totalFor };
}

/** Build the CI3 exam-marks message per learner for a class. assessmentId null/empty
 *  → composite per-subject term totals; a specific assessment → that paper alone. */
async function buildMarksSms(schoolId: string, classroomId: string, assessmentId?: string | null) {
  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } }, include: { academicYear: true } });
  if (!term) throw new Error("No current term for this school");
  const enrolled = await db.enrollment.findMany({ where: { classroomId, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" } });
  const grades = await db.rawScoreGrade.findMany({ where: { schoolId }, orderBy: { sortOrder: "asc" } });

  const partsByStudent: Record<string, string[]> = {};
  if (assessmentId) {
    const a = await db.assessment.findUnique({ where: { id: assessmentId }, include: { subject: true, marks: true } });
    if (!a || a.schoolId !== schoolId) throw new Error("Assessment not found");
    for (const en of enrolled) {
      const m = a.marks.find((x) => x.studentId === en.studentId);
      if (!m || m.score === null || a.maxScore <= 0) continue;
      const total = Math.round((m.score / a.maxScore) * 100);
      const g = rawGradeFor(grades, total);
      partsByStudent[en.studentId] = [`${abbreviateSubject(a.subject.name)} ${total}% ${g?.gradePoint ?? ""} ${g ? titleCase(g.name) : ""}`.replace(/\s+/g, " ").trim()];
    }
  } else {
    const { subjects, totalFor } = await termSubjectTotals(classroomId, term.id);
    for (const en of enrolled) {
      const parts: string[] = [];
      for (const subject of subjects) {
        const total = totalFor(subject, en.studentId);
        if (total === null) continue;
        const g = rawGradeFor(grades, total);
        parts.push(`${abbreviateSubject(subject)} ${total}% ${g?.gradePoint ?? ""} ${g ? titleCase(g.name) : ""}`.replace(/\s+/g, " ").trim());
      }
      partsByStudent[en.studentId] = parts;
    }
  }

  const rows = enrolled.map((en) => {
    const name = `${en.student.firstName} ${en.student.lastName}`;
    return {
      studentId: en.studentId,
      student: name,
      guardianName: en.student.guardianName || `Guardian of ${en.student.firstName}`,
      phone: en.student.guardianPhone || null,
      message: `Year|Term: ${term.academicYear.name}|${term.name} Exam Scores of ${name}:\n${partsByStudent[en.studentId]?.join(", ") ?? ""}`,
    };
  });
  return { term, rows };
}

// ── GET /api/school/[id]/ops/academics ──────────────────────────
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["gradebook.view", "reports.view", "settings.manage", "sms.view"] });
  if (!auth.ok) return auth.res;

  const url = new URL(req.url);
  const tab = url.searchParams.get("tab") ?? "";
  const q = (k: string) => url.searchParams.get(k) ?? undefined;

  /* ── PORTFOLIO (weekly SBA grid — CI3 portfolio_assessment parity) ── */
  if (tab === "portfolio") {
    const classroomId = q("classroomId");
    const subjectId = q("subjectId");
    const week = q("week") ?? "";
    // strict ISO Monday of the label; when entries already exist for this week
    // but start in a different Monday (legacy week-number convention, e.g. the
    // seeded 2026-W42 = Oct 19–23), anchor the grid on the recorded data instead
    let monday = mondayOfISOWeek(week);
    if (!classroomId || !subjectId || !monday) {
      return NextResponse.json({ error: "classroomId, subjectId and week (YYYY-Www) are required" }, { status: 400 });
    }
    const classroom = await db.classroom.findFirst({ where: { id: classroomId, schoolId } });
    const subject = await db.subject.findFirst({ where: { id: subjectId, schoolId } });
    if (!classroom || !subject) return NextResponse.json({ error: "Class or subject not found" }, { status: 404 });
    const existingEntries = await db.portfolioEntry.findMany({ where: { schoolId, classroomId, subjectId, week } });
    if (existingEntries.length) {
      const first = new Date(Math.min(...existingEntries.map((e) => e.entryDate.getTime())));
      const recordedMonday = new Date(first);
      recordedMonday.setUTCDate(first.getUTCDate() - ((first.getUTCDay() + 6) % 7));
      if (ymd(recordedMonday) !== ymd(monday)) monday = recordedMonday;
    }
    const days = Array.from({ length: 5 }, (_, i) => {
      const d = new Date(monday);
      d.setUTCDate(monday.getUTCDate() + i);
      return d;
    });
    const entries = existingEntries;
    const enrolled = await db.enrollment.findMany({ where: { classroomId, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" } });

    const strandByDate: Record<string, string> = {};
    for (const e of entries) {
      const k = ymd(e.entryDate);
      if (e.strandCode && !strandByDate[k]) strandByDate[k] = e.strandCode;
    }
    const scoreOf = (studentId: string, date: string) => {
      const matches = entries.filter((x) => x.studentId === studentId && ymd(x.entryDate) === date);
      if (!matches.length) return null;
      return matches.reduce((a, b) => (a.createdAt > b.createdAt ? a : b)).score; // latest edit wins
    };
    const today = ymd(new Date());
    const setting = await db.opsSetting.findUnique({ where: { schoolId_key: { schoolId, key: "PORTFOLIO_ASSESSMENT" } } });

    return NextResponse.json({
      week,
      classroom: { id: classroom.id, name: classroom.name },
      subject: { id: subject.id, name: subject.name },
      portfolioEnabled: setting?.value === "1",
      days: days.map((d) => ({
        date: ymd(d),
        label: d.toLocaleDateString("en-GB", { weekday: "short", day: "numeric", month: "short", timeZone: "UTC" }),
        strandCode: strandByDate[ymd(d)] ?? "",
        past: ymd(d) < today,
      })),
      rows: enrolled.map((en, i) => ({
        studentId: en.studentId,
        name: `${en.student.firstName} ${en.student.lastName}`,
        rollNo: en.rollNo ?? i + 1,
        scores: Object.fromEntries(days.map((d) => [ymd(d), scoreOf(en.studentId, ymd(d))])),
      })),
    });
  }

  /* ── WAEC RAW-SCORE GRADES (CI3 raw_score_grade parity) ── */
  if (tab === "rawgrades") {
    const grades = await db.rawScoreGrade.findMany({ where: { schoolId }, orderBy: [{ sortOrder: "asc" }, { markFrom: "desc" }] });
    const passCandidates = grades.filter((g) => g.markFrom >= 50);
    return NextResponse.json({
      grades: grades.map((g) => ({ id: g.id, name: g.name, gradePoint: g.gradePoint, gradePointNumeric: g.gradePointNumeric, markFrom: g.markFrom, markUpto: g.markUpto, comment: g.comment })),
      stats: {
        total: grades.length,
        highest: grades.length ? Math.max(...grades.map((g) => g.markUpto)) : 0,
        lowest: grades.length ? Math.min(...grades.map((g) => g.markFrom)) : 0,
        passMark: passCandidates.length ? Math.min(...passCandidates.map((g) => g.markFrom)) : 50,
      },
    });
  }

  /* ── CUMULATIVE (multi-term) REPORT ── */
  if (tab === "cumulative") {
    const classroomId = q("classroomId");
    const studentId = q("studentId");
    if (!classroomId) return NextResponse.json({ error: "classroomId is required" }, { status: 400 });
    const classroom = await db.classroom.findFirst({ where: { id: classroomId, schoolId } });
    if (!classroom) return NextResponse.json({ error: "Class not found" }, { status: 404 });
    const enrolled = await db.enrollment.findMany({ where: { classroomId, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" } });
    if (!studentId) {
      return NextResponse.json({
        learners: enrolled.map((en, i) => ({ id: en.studentId, name: `${en.student.firstName} ${en.student.lastName}`, rollNo: en.rollNo ?? i + 1 })),
      });
    }
    const en = enrolled.find((x) => x.studentId === studentId);
    if (!en) return NextResponse.json({ error: "Learner not found in this class" }, { status: 404 });
    const learner = { id: en.studentId, name: `${en.student.firstName} ${en.student.lastName}`, index: en.student.nyansapoIndex, classroom: classroom.name };

    // terms: every term with assessments for this class + always the current term
    const distinct = await db.assessment.findMany({ where: { classroomId }, select: { termId: true }, distinct: ["termId"] });
    const termIds = new Set(distinct.map((a) => a.termId));
    const currentTerm = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
    if (currentTerm) termIds.add(currentTerm.id);
    const terms = (await db.term.findMany({ where: { id: { in: [...termIds] } }, include: { academicYear: true } }))
      .sort((a, b) => a.academicYear.startsAt.getTime() - b.academicYear.startsAt.getTime() || a.sequence - b.sequence);

    const grades = await db.rawScoreGrade.findMany({ where: { schoolId }, orderBy: { sortOrder: "asc" } });
    const school = await db.school.findUnique({ where: { id: schoolId } });
    const codeFor = (total: number) => (rawGradeFor(grades, total)?.gradePoint ?? gradeFor(school?.curriculum ?? "GES", total).grade);

    const subjectSet = new Set<string>();
    const termPayloads: { id: string; name: string; isCurrent: boolean; subjects: { subject: string; total: number | null; grade: string | null; numeric: number | null }[]; average: number | null; averageGrade: string | null }[] = [];
    for (const t of terms) {
      const { subjects, totalFor } = await termSubjectTotals(classroomId, t.id);
      subjects.forEach((s) => subjectSet.add(s));
      const rows = subjects.map((s) => {
        const total = totalFor(s, learner.id);
        return { subject: s, total, grade: total === null ? null : codeFor(total), numeric: total === null ? null : rawGradeFor(grades, total)?.gradePointNumeric ?? null };
      });
      const scored = rows.filter((r) => r.total !== null);
      const average = scored.length ? Math.round(scored.reduce((s, r) => s + (r.total ?? 0), 0) / scored.length) : null;
      termPayloads.push({
        id: t.id,
        name: `${t.name} — ${t.academicYear.name}`,
        isCurrent: t.isCurrent,
        subjects: rows,
        average,
        averageGrade: average === null ? null : codeFor(average),
      });
    }

    const scoredTerms = termPayloads.filter((t) => t.average !== null);
    const cumulativeAverage = scoredTerms.length ? Math.round(scoredTerms.reduce((s, t) => s + (t.average ?? 0), 0) / scoredTerms.length) : null;
    // WAEC / BECE aggregate for the CURRENT term — sum of grade points over subjects
    const cur = termPayloads.find((t) => t.isCurrent);
    const waecAggregate = cur && cur.subjects.some((s) => s.total !== null) && grades.length
      ? cur.subjects.reduce((s, r) => s + (r.total === null ? 0 : rawGradeFor(grades, r.total)?.gradePointNumeric ?? 0), 0)
      : null;

    return NextResponse.json({
      learner,
      subjects: [...subjectSet].sort(),
      terms: termPayloads,
      cumulative: { average: cumulativeAverage, grade: cumulativeAverage === null ? null : codeFor(cumulativeAverage), termsCounted: scoredTerms.length },
      waecAggregate,
    });
  }

  /* ── QUESTION PAPERS (CI3 question_paper parity) ── */
  if (tab === "papers") {
    const classrooms = await db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
    const papers = await db.questionPaper.findMany({ where: { schoolId }, include: { classroom: true }, orderBy: { updatedAt: "desc" }, take: 200 });
    return NextResponse.json({
      classrooms: classrooms.map((c) => ({ id: c.id, name: c.name })),
      papers: papers.map((p) => ({
        id: p.id, title: p.title, classroomId: p.classroomId, classroom: p.classroom.name,
        examName: p.examName, authorName: p.authorName, body: p.body, updatedAt: p.updatedAt,
      })),
    });
  }

  /* ── EXAM-MARKS SMS previews (CI3 exam_marks_sms parity) ── */
  if (tab === "marks-sms") {
    const classroomId = q("classroomId");
    const assessmentId = q("assessmentId");
    const receiver = q("receiver") === "learner" ? "learner" : "guardian";
    if (!classroomId) return NextResponse.json({ error: "classroomId is required" }, { status: 400 });
    const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } }, include: { academicYear: true } });
    if (!term) return NextResponse.json({ error: "No current term for this school" }, { status: 400 });
    // exam select: mid-term / end-of-term papers of the current term; fall back to all assessments
    let exams = await db.assessment.findMany({ where: { classroomId, termId: term.id, type: { in: ["MID_TERM", "END_OF_TERM"] } }, include: { subject: true }, orderBy: { date: "asc" } });
    if (!exams.length) {
      exams = await db.assessment.findMany({ where: { classroomId, termId: term.id }, include: { subject: true }, orderBy: { date: "asc" } });
    }
    let previews: Awaited<ReturnType<typeof buildMarksSms>>["rows"] = [];
    try {
      previews = (await buildMarksSms(schoolId, classroomId, assessmentId || null)).rows;
    } catch (e: any) {
      return NextResponse.json({ error: e.message }, { status: 400 });
    }
    return NextResponse.json({
      assessments: exams.map((a) => ({ id: a.id, label: `${a.title} · ${TYPE_LABELS[a.type] ?? a.type} — ${a.subject.name}` })),
      yearName: term.academicYear.name,
      termName: term.name,
      receiver,
      previews,
      withPhone: previews.filter((p) => p.phone).length,
      withoutPhone: previews.filter((p) => !p.phone).length,
    });
  }

  /* ── default bootstrap for all tabs ── */
  const classrooms = await db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
  const subjects = await db.subject.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
  const currentTerm = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } }, include: { academicYear: true } });
  const grades = await db.rawScoreGrade.findMany({ where: { schoolId }, orderBy: { sortOrder: "asc" } });
  const setting = await db.opsSetting.findUnique({ where: { schoolId_key: { schoolId, key: "PORTFOLIO_ASSESSMENT" } } });
  return NextResponse.json({
    classrooms: classrooms.map((c) => ({ id: c.id, name: c.name })),
    subjects: subjects.map((s) => ({ id: s.id, name: s.name, code: s.code })),
    currentTerm: currentTerm ? { id: currentTerm.id, name: currentTerm.name, yearName: currentTerm.academicYear.name } : null,
    portfolioEnabled: setting?.value === "1",
    grades: grades.map((g) => ({ id: g.id, name: g.name, gradePoint: g.gradePoint, gradePointNumeric: g.gradePointNumeric, markFrom: g.markFrom, markUpto: g.markUpto, comment: g.comment })),
  });
}

// ── POST /api/school/[id]/ops/academics ─────────────────────────
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;

  const POST_PERMS: Record<string, string[]> = {
    "portfolio.save": ["gradebook.enter"],
    "portfolio.aggregate": ["gradebook.enter"],
    "rawgrade.create": ["settings.manage"],
    "rawgrade.update": ["settings.manage"],
    "rawgrade.delete": ["settings.manage"],
    "paper.create": ["classes.manage", "reports.publish"],
    "paper.update": ["classes.manage", "reports.publish"],
    "paper.delete": ["classes.manage", "reports.publish"],
    "marks-sms.send": ["sms.send"],
  };
  const auth = await opsAuth(req, schoolId, "POST", { perms: POST_PERMS[action] });
  if (!auth.ok) return auth.res;
  const user = auth.user;

  /* ── PORTFOLIO: save the weekly SBA grid ── */
  if (action === "portfolio.save") {
    const { classroomId, subjectId, week, days, rows } = body as {
      classroomId: string; subjectId: string; week: string;
      days: { date: string; strandCode?: string }[];
      rows: { studentId: string; scores: Record<string, number | null> }[];
    };
    if (!classroomId || !subjectId || !mondayOfISOWeek(week)) return NextResponse.json({ error: "classroomId, subjectId and week (YYYY-Www) are required" }, { status: 400 });
    if (!Array.isArray(days) || !days.length || !Array.isArray(rows)) return NextResponse.json({ error: "days[] and rows[] are required" }, { status: 400 });
    if (days.length > 7 || rows.length > 200) return NextResponse.json({ error: "At most 7 days × 200 learners per save" }, { status: 400 });
    const classroom = await db.classroom.findFirst({ where: { id: classroomId, schoolId } });
    const subject = await db.subject.findFirst({ where: { id: subjectId, schoolId } });
    if (!classroom || !subject) return NextResponse.json({ error: "Class or subject not found" }, { status: 404 });

    const strandByDate: Record<string, string | null> = {};
    for (const d of days) {
      if (!/^\d{4}-\d{2}-\d{2}$/.test(d.date ?? "")) return NextResponse.json({ error: `Invalid day date: ${d.date}` }, { status: 400 });
      strandByDate[d.date] = d.strandCode?.toString().slice(0, 40) || null;
    }

    let saved = 0;
    for (const row of rows) {
      if (!row?.studentId) continue;
      for (const [date, strandCode] of Object.entries(strandByDate)) {
        const raw = row.scores?.[date];
        const num = Number(raw);
        const score = raw === null || raw === undefined || !Number.isFinite(num) ? 0 : Math.max(0, Math.min(10, num));
        // seeded/legacy rows can carry a time-of-day in entryDate — match on the
        // whole calendar day rather than the exact midnight timestamp
        const existing = await db.portfolioEntry.findFirst({
          where: {
            studentId: row.studentId, subjectId,
            entryDate: { gte: new Date(`${date}T00:00:00.000Z`), lte: new Date(`${date}T23:59:59.999Z`) },
          },
        });
        if (existing) {
          await db.portfolioEntry.update({ where: { id: existing.id }, data: { score, strandCode, week, classroomId } });
        } else {
          await db.portfolioEntry.create({ data: { schoolId, studentId: row.studentId, classroomId, subjectId, subjectName: subject.name, week, entryDate: new Date(`${date}T00:00:00.000Z`), strandCode, score } });
        }
        saved++;
      }
    }
    return NextResponse.json({ saved, week, days: Object.keys(strandByDate).length, learners: rows.length });
  }

  /* ── PORTFOLIO: aggregate the week into a class-score assessment ──
     CI3 formula: test1 = round(((total × 10) / numRows) × 0.2, 2) — the weekly
     average (/10 → /100) taken as 20% of the class score, capped at 20.        */
  if (action === "portfolio.aggregate") {
    const { classroomId, subjectId, week } = body as { classroomId: string; subjectId: string; week: string };
    const monday = mondayOfISOWeek(week);
    if (!classroomId || !subjectId || !monday) return NextResponse.json({ error: "classroomId, subjectId and week (YYYY-Www) are required" }, { status: 400 });
    const classroom = await db.classroom.findFirst({ where: { id: classroomId, schoolId } });
    const subject = await db.subject.findFirst({ where: { id: subjectId, schoolId } });
    if (!classroom || !subject) return NextResponse.json({ error: "Class or subject not found" }, { status: 404 });
    const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
    if (!term) return NextResponse.json({ error: "No current term for this school" }, { status: 400 });

    const entriesRaw = await db.portfolioEntry.findMany({ where: { schoolId, classroomId, subjectId, week } });
    // one score per learner per calendar day — the most recently created row wins
    const entries = Object.values(
      entriesRaw.reduce((acc: Record<string, typeof entriesRaw[number]>, e) => {
        const key = `${e.studentId}|${ymd(e.entryDate)}`;
        if (!acc[key] || acc[key].createdAt < e.createdAt) acc[key] = e;
        return acc;
      }, {}),
    );
    const enrolled = await db.enrollment.findMany({ where: { classroomId, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" } });

    const title = `Portfolio SBA ${week}`;
    let assessment = await db.assessment.findFirst({ where: { classroomId, subjectId, termId: term.id, type: "PROJECT", title } });
    if (assessment) {
      assessment = await db.assessment.update({ where: { id: assessment.id }, data: { maxScore: 20, date: monday } });
    } else {
      assessment = await db.assessment.create({ data: { schoolId, classroomId, termId: term.id, subjectId, title, type: "PROJECT", maxScore: 20, date: monday } });
    }
    await db.mark.deleteMany({ where: { assessmentId: assessment.id } });

    const outRows: { studentId: string; name: string; days: number; average: number | null; classScore: number | null }[] = [];
    let marked = 0;
    for (const en of enrolled) {
      const scored = entries.filter((e) => e.studentId === en.studentId && e.score > 0).map((e) => e.score);
      const total = scored.reduce((s, v) => s + v, 0);
      const numRows = scored.length;
      const test1 = numRows ? round2(((total * 10) / numRows) * 0.2) : 0;
      const classScore = numRows ? Math.min(20, test1) : 0;
      if (numRows > 0) {
        await db.mark.create({ data: { assessmentId: assessment.id, studentId: en.studentId, score: classScore } });
        marked++;
      }
      outRows.push({
        studentId: en.studentId,
        name: `${en.student.firstName} ${en.student.lastName}`,
        days: numRows,
        average: numRows ? round2(total / numRows) : null, // /10
        classScore: numRows ? classScore : null,           // /20
      });
    }
    return NextResponse.json({
      assessment: { id: assessment.id, title, maxScore: 20, termName: term.name },
      rows: outRows,
      marked,
    });
  }

  /* ── WAEC RAW-SCORE GRADES CRUD ── */
  if (action === "rawgrade.create" || action === "rawgrade.update") {
    const { id, name, gradePoint, gradePointNumeric, markFrom, markUpto, comment, sortOrder } = body;
    if (!name || !gradePoint) return NextResponse.json({ error: "Remark name and grade point are required" }, { status: 400 });
    const from = Number(markFrom), upto = Number(markUpto);
    if (!Number.isFinite(from) || !Number.isFinite(upto)) return NextResponse.json({ error: "markFrom and markUpto must be numbers" }, { status: 400 });
    if (from > upto) return NextResponse.json({ error: "markFrom cannot exceed markUpto" }, { status: 400 });
    const data = {
      name: String(name).trim().slice(0, 60),
      gradePoint: String(gradePoint).trim().slice(0, 8),
      gradePointNumeric: Number.isFinite(Number(gradePointNumeric)) ? Number(gradePointNumeric) : 0,
      markFrom: from, markUpto: upto,
      comment: comment ? String(comment).slice(0, 200) : null,
    };
    if (action === "rawgrade.update") {
      const existing = await db.rawScoreGrade.findUnique({ where: { id } });
      if (!existing || existing.schoolId !== schoolId) return NextResponse.json({ error: "Grade not found" }, { status: 404 });
      const g = await db.rawScoreGrade.update({ where: { id }, data: { ...data, ...(sortOrder !== undefined ? { sortOrder: Number(sortOrder) || 0 } : {}) } });
      return NextResponse.json({ grade: g });
    }
    const g = await db.rawScoreGrade.create({ data: { ...data, schoolId, sortOrder: Number(sortOrder) || 0 } });
    return NextResponse.json({ grade: g });
  }

  if (action === "rawgrade.delete") {
    const g = await db.rawScoreGrade.findUnique({ where: { id: body.id } });
    if (!g || g.schoolId !== schoolId) return NextResponse.json({ error: "Grade not found" }, { status: 404 });
    await db.rawScoreGrade.delete({ where: { id: g.id } });
    return NextResponse.json({ ok: true });
  }

  /* ── QUESTION PAPERS CRUD ── */
  if (action === "paper.create" || action === "paper.update") {
    const { id, classroomId, title, body: paperBody, examName, authorName } = body;
    if (!title || !classroomId) return NextResponse.json({ error: "Title and class are required" }, { status: 400 });
    const classroom = await db.classroom.findFirst({ where: { id: classroomId, schoolId } });
    if (!classroom) return NextResponse.json({ error: "Class not found" }, { status: 404 });
    const data = {
      classroomId, title: String(title).trim().slice(0, 200),
      body: typeof paperBody === "string" ? paperBody : "",
      examName: examName ? String(examName).trim().slice(0, 120) : null,
      authorName: authorName ? String(authorName).trim().slice(0, 120) : user.fullName,
    };
    if (action === "paper.update") {
      const existing = await db.questionPaper.findUnique({ where: { id } });
      if (!existing || existing.schoolId !== schoolId) return NextResponse.json({ error: "Question paper not found" }, { status: 404 });
      const p = await db.questionPaper.update({ where: { id }, data });
      return NextResponse.json({ paper: p });
    }
    const p = await db.questionPaper.create({ data: { ...data, schoolId } });
    return NextResponse.json({ paper: p });
  }

  if (action === "paper.delete") {
    const p = await db.questionPaper.findUnique({ where: { id: body.id } });
    if (!p || p.schoolId !== schoolId) return NextResponse.json({ error: "Question paper not found" }, { status: 404 });
    await db.questionPaper.delete({ where: { id: p.id } });
    return NextResponse.json({ ok: true });
  }

  /* ── EXAM-MARKS SMS BLAST (CI3 exam_marks_sms parity) ── */
  if (action === "marks-sms.send") {
    const { classroomId, assessmentId, receiver: receiverRaw } = body as { classroomId: string; assessmentId?: string; receiver?: string };
    const receiver = receiverRaw === "learner" ? "learner" : "guardian";
    if (!classroomId) return NextResponse.json({ error: "classroomId is required" }, { status: 400 });
    let built: Awaited<ReturnType<typeof buildMarksSms>>;
    try {
      built = await buildMarksSms(schoolId, classroomId, assessmentId || null);
    } catch (e: any) {
      return NextResponse.json({ error: e.message }, { status: 400 });
    }
    const sendable = built.rows.filter((r) => r.phone);
    if (!sendable.length) return NextResponse.json({ error: "No recipients with a phone number on file" }, { status: 400 });

    const setting = await db.smsSetting.findUnique({ where: { schoolId } });
    if (setting && setting.credits < sendable.length) {
      return NextResponse.json({ error: `Not enough credits (${setting.credits} left, ${sendable.length} needed) — top up first.` }, { status: 400 });
    }

    let sent = 0, failed = 0, skipped = 0;
    for (const r of built.rows) {
      if (!r.phone) { skipped++; continue; }
      const recipientName = receiver === "learner" ? r.student : r.guardianName;
      const msg = await sendSms({
        db, schoolId, studentId: r.studentId,
        recipientName, recipientPhone: r.phone,
        body: r.message, category: "RESULTS", sentByName: user.fullName,
      });
      if (msg.status === "FAILED") failed++; else sent++;
    }
    return NextResponse.json({ sent, failed, skipped, total: built.rows.length });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

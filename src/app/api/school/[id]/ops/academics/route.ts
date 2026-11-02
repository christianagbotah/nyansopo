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

/* ── CRÈCHE / EARLY-YEARS DEFAULTS (NaCCA developmental domains) ──
   Seeded idempotently the first time creche-setup is requested. */
const CRECHE_SCALE_SEED = [
  { code: "EX", label: "Excellent", sortOrder: 1 },
  { code: "GD", label: "Good", sortOrder: 2 },
  { code: "SD", label: "Developing", sortOrder: 3 },
  { code: "NA", label: "Needs Attention", sortOrder: 4 },
];
const CRECHE_DOMAIN_SEED: { name: string; skills: string[] }[] = [
  { name: "Communication & Language", skills: ["Listens and responds to simple instructions", "Speaks in understandable sentences", "Uses new words in conversation", "Shows interest in story books", "Answers questions about a story"] },
  { name: "Physical & Motor Development", skills: ["Runs, jumps and climbs with control", "Holds crayon / pencil correctly", "Threads beads and buttons clothing", "Balances on one foot", "Catches and throws a ball"] },
  { name: "Social & Emotional", skills: ["Plays and shares with others", "Follows classroom routines", "Expresses feelings with words", "Shows care for others", "Separates happily from caregiver"] },
  { name: "Cognitive / Discovery", skills: ["Counts objects up to 20", "Sorts and matches by colour / shape", "Recognises own name in print", "Asks questions about the environment", "Completes simple puzzles"] },
  { name: "Creative Arts", skills: ["Sings familiar songs and rhymes", "Draws and colours with purpose", "Moves creatively to music", "Role-plays everyday activities"] },
];

/* ── SBA MARKS CONFIG (system_settings parity) ────────────────────
   Stored as SchoolSetting rows (JSON-encoded values). */
const SBA_DEFAULTS: Record<string, number> = {
  "sba.classTestMax": 20,
  "sba.groupWorkMax": 10,
  "sba.test2Max": 20,
  "sba.projectMax": 10,
  "sba.classScoreWeightPct": 50,
  "sba.portfolioAutoFillPct": 30,
};

/** Read the SBA config merged over defaults. */
async function sbaConfig(schoolId: string) {
  const rows = await db.schoolSetting.findMany({ where: { schoolId, key: { startsWith: "sba." } } });
  const out: Record<string, number> = { ...SBA_DEFAULTS };
  for (const r of rows) {
    const n = Number(JSON.parse(r.value));
    if (Number.isFinite(n)) out[r.key] = n;
  }
  return out;
}

/** SBA sheet column definitions: fixed four columns + the exam paper.
    Assessment title convention: "SBA Class Test · <term name>". */
const SBA_COLUMNS = [
  { key: "classTest", label: "Class Test", title: "SBA Class Test", type: "EXERCISE", setting: "sba.classTestMax" },
  { key: "groupWork", label: "Group Work", title: "SBA Group Work", type: "GROUP_WORK", setting: "sba.groupWorkMax" },
  { key: "test2", label: "Test 2", title: "SBA Test 2", type: "QUIZ", setting: "sba.test2Max" },
  { key: "project", label: "Project", title: "SBA Project", type: "PROJECT", setting: "sba.projectMax" },
] as const;
type SbaKey = (typeof SBA_COLUMNS)[number]["key"];
const SBA_EXAM_TITLE = "SBA Term Exam";
/** Full title of an SBA column assessment for a given term name. */
const sbaTitle = (base: string, termName: string) => `${base} · ${termName}`;

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
  const tab = url.searchParams.get("tab") ?? url.searchParams.get("action") ?? "";
  const q = (k: string) => url.searchParams.get(k) ?? undefined;

  /* ── CRÈCHE SETUP: rating scale + domains + skills in one payload,
     default-seeded (upsert) on first load. ── */
  if (tab === "creche-setup") {
    const scaleCount = await db.crecheRatingScale.count({ where: { schoolId } });
    if (scaleCount === 0) {
      for (const s of CRECHE_SCALE_SEED) {
        await db.crecheRatingScale.upsert({
          where: { schoolId_code: { schoolId, code: s.code } },
          create: { schoolId, ...s }, update: {},
        });
      }
    }
    const domainCount = await db.crecheDomain.count({ where: { schoolId } });
    if (domainCount === 0) {
      for (const [di, d] of CRECHE_DOMAIN_SEED.entries()) {
        const dom = await db.crecheDomain.create({ data: { schoolId, name: d.name, sortOrder: di + 1, active: true } });
        await db.crecheSkill.createMany({ data: d.skills.map((name, si) => ({ schoolId, domainId: dom.id, name, sortOrder: si + 1 })) });
      }
    }
    const scale = await db.crecheRatingScale.findMany({ where: { schoolId }, orderBy: [{ sortOrder: "asc" }, { code: "asc" }] });
    const domains = await db.crecheDomain.findMany({ where: { schoolId }, orderBy: [{ sortOrder: "asc" }, { name: "asc" }] });
    const skills = await db.crecheSkill.findMany({ where: { schoolId }, orderBy: [{ sortOrder: "asc" }, { name: "asc" }] });
    return NextResponse.json({
      scale: scale.map((s) => ({ id: s.id, code: s.code, label: s.label, sortOrder: s.sortOrder })),
      domains: domains.map((d) => ({
        id: d.id, name: d.name, sortOrder: d.sortOrder, active: d.active,
        skills: skills.filter((sk) => sk.domainId === d.id).map((sk) => ({ id: sk.id, domainId: sk.domainId, name: sk.name, sortOrder: sk.sortOrder })),
      })),
    });
  }

  /* ── CRÈCHE RATING GRID: learners of a class + saved ratings for a term ── */
  if (tab === "creche-ratings") {
    const classroomId = q("classroomId");
    const termId = q("termId");
    if (!classroomId || !termId) return NextResponse.json({ error: "classroomId and termId are required" }, { status: 400 });
    const classroom = await db.classroom.findFirst({ where: { id: classroomId, schoolId } });
    const term = await db.term.findFirst({ where: { id: termId, academicYear: { schoolId } } });
    if (!classroom || !term) return NextResponse.json({ error: "Class or term not found" }, { status: 404 });
    const [scale, domains, skills, enrolled, ratings] = await Promise.all([
      db.crecheRatingScale.findMany({ where: { schoolId }, orderBy: [{ sortOrder: "asc" }, { code: "asc" }] }),
      db.crecheDomain.findMany({ where: { schoolId }, orderBy: [{ sortOrder: "asc" }, { name: "asc" }] }),
      db.crecheSkill.findMany({ where: { schoolId }, orderBy: [{ sortOrder: "asc" }, { name: "asc" }] }),
      db.enrollment.findMany({ where: { classroomId, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" } }),
      db.crecheRating.findMany({ where: { schoolId, termId } }),
    ]);
    const ratingMap: Record<string, string> = {};
    for (const r of ratings) ratingMap[`${r.studentId}_${r.skillId}`] = r.code;
    return NextResponse.json({
      scale: scale.map((s) => ({ id: s.id, code: s.code, label: s.label, sortOrder: s.sortOrder })),
      domains: domains.map((d) => ({
        id: d.id, name: d.name, sortOrder: d.sortOrder, active: d.active,
        skills: skills.filter((sk) => sk.domainId === d.id).map((sk) => ({ id: sk.id, domainId: sk.domainId, name: sk.name, sortOrder: sk.sortOrder })),
      })),
      students: enrolled.map((en, i) => ({ id: en.studentId, name: `${en.student.firstName} ${en.student.lastName}`, rollNo: en.rollNo ?? i + 1 })),
      ratings: ratingMap,
    });
  }

  /* ── SBA CONFIG: the four column maxes + weights ── */
  if (tab === "sba-config") {
    return NextResponse.json({ config: await sbaConfig(schoolId) });
  }

  /* ── SBA SHEET: one row per learner, four fixed SBA columns + exam,
     matched by the SBA title convention. ── */
  if (tab === "sba-sheet") {
    const classroomId = q("classroomId");
    const subjectId = q("subjectId");
    const termId = q("termId");
    if (!classroomId || !subjectId || !termId) return NextResponse.json({ error: "classroomId, subjectId and termId are required" }, { status: 400 });
    const classroom = await db.classroom.findFirst({ where: { id: classroomId, schoolId } });
    const subject = await db.subject.findFirst({ where: { id: subjectId, schoolId } });
    const term = await db.term.findFirst({ where: { id: termId, academicYear: { schoolId } }, include: { academicYear: true } });
    if (!classroom || !subject || !term) return NextResponse.json({ error: "Class, subject or term not found" }, { status: 404 });
    const config = await sbaConfig(schoolId);
    const cols = SBA_COLUMNS.map((c) => ({ ...c, max: config[c.setting] }));
    const titles = new Map<string, string>();
    for (const c of cols) titles.set(sbaTitle(c.title, term.name), c.key);
    const examTitle = sbaTitle(SBA_EXAM_TITLE, term.name);
    const assessments = await db.assessment.findMany({
      where: { classroomId, subjectId, termId, title: { in: [...titles.keys(), examTitle] } },
      include: { marks: true },
    });
    const enrolled = await db.enrollment.findMany({ where: { classroomId, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" } });
    // scores per column key (and "exam") → { studentId: score }
    const scores: Record<string, Record<string, number | null>> = {};
    for (const c of [...cols.map((c) => c.key), "exam"]) scores[c] = {};
    for (const a of assessments) {
      const key = titles.get(a.title) ?? (a.title === examTitle ? "exam" : null);
      if (!key) continue;
      for (const m of a.marks) scores[key][m.studentId] = m.score;
    }
    const actualMax = cols.reduce((s, c) => s + c.max, 0);
    const weight = config["sba.classScoreWeightPct"];
    return NextResponse.json({
      classroom: { id: classroom.id, name: classroom.name },
      subject: { id: subject.id, name: subject.name },
      term: { id: term.id, name: term.name, yearName: term.academicYear.name },
      config: { ...config, actualMax, examWeightPct: 100 - weight },
      columns: cols.map((c) => ({ key: c.key, label: c.label, type: c.type, max: c.max })),
      examMax: 100,
      students: enrolled.map((en, i) => ({
        id: en.studentId, name: `${en.student.firstName} ${en.student.lastName}`, rollNo: en.rollNo ?? i + 1,
        scores: Object.fromEntries(Object.keys(scores).map((k) => [k, scores[k][en.studentId] ?? null])),
      })),
    });
  }

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
    // early-years (crèche) rating system + SBA marks config
    "creche-scale-save": ["settings.manage"],
    "creche-scale-delete": ["settings.manage"],
    "creche-domain-save": ["settings.manage"],
    "creche-domain-delete": ["settings.manage"],
    "creche-skill-save": ["settings.manage"],
    "creche-skill-delete": ["settings.manage"],
    "creche-ratings-save": ["gradebook.enter"],
    "creche-report-generate": ["reports.publish"],
    "sba-config": ["settings.manage"],
    "sba-sheet-save": ["gradebook.enter"],
    "sba-portfolio-fill": ["gradebook.enter"],
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

  /* ── CRÈCHE RATING SCALE CRUD (settings.manage) ── */
  if (action === "creche-scale-save") {
    const { id, code, label, sortOrder } = body;
    if (!code || !label) return NextResponse.json({ error: "Code and label are required" }, { status: 400 });
    const data = { code: String(code).trim().slice(0, 8).toUpperCase(), label: String(label).trim().slice(0, 60), sortOrder: Number(sortOrder) || 0 };
    if (id) {
      const existing = await db.crecheRatingScale.findUnique({ where: { id } });
      if (!existing || existing.schoolId !== schoolId) return NextResponse.json({ error: "Rating not found" }, { status: 404 });
      const s = await db.crecheRatingScale.update({ where: { id }, data });
      return NextResponse.json({ scale: s });
    }
    const dup = await db.crecheRatingScale.findUnique({ where: { schoolId_code: { schoolId, code: data.code } } });
    if (dup) return NextResponse.json({ error: `Code ${data.code} already exists on the scale` }, { status: 409 });
    const s = await db.crecheRatingScale.create({ data: { ...data, schoolId } });
    return NextResponse.json({ scale: s });
  }

  if (action === "creche-scale-delete") {
    const s = await db.crecheRatingScale.findUnique({ where: { id: body.id } });
    if (!s || s.schoolId !== schoolId) return NextResponse.json({ error: "Rating not found" }, { status: 404 });
    await db.crecheRatingScale.delete({ where: { id: s.id } });
    return NextResponse.json({ ok: true });
  }

  /* ── CRÈCHE DOMAINS & SKILLS CRUD (settings.manage) ── */
  if (action === "creche-domain-save") {
    const { id, name, sortOrder, active } = body;
    if (!name) return NextResponse.json({ error: "Domain name is required" }, { status: 400 });
    const data = { name: String(name).trim().slice(0, 120), sortOrder: Number(sortOrder) || 0, active: active === undefined ? true : !!active };
    if (id) {
      const existing = await db.crecheDomain.findUnique({ where: { id } });
      if (!existing || existing.schoolId !== schoolId) return NextResponse.json({ error: "Domain not found" }, { status: 404 });
      const d = await db.crecheDomain.update({ where: { id }, data });
      return NextResponse.json({ domain: d });
    }
    const d = await db.crecheDomain.create({ data: { ...data, schoolId } });
    return NextResponse.json({ domain: d });
  }

  if (action === "creche-domain-delete") {
    const d = await db.crecheDomain.findUnique({ where: { id: body.id }, include: { skills: true } });
    if (!d || d.schoolId !== schoolId) return NextResponse.json({ error: "Domain not found" }, { status: 404 });
    const skillIds = d.skills.map((s) => s.id);
    if (skillIds.length) {
      await db.crecheRating.deleteMany({ where: { skillId: { in: skillIds } } });
      await db.crecheSkill.deleteMany({ where: { id: { in: skillIds } } });
    }
    await db.crecheDomain.delete({ where: { id: d.id } });
    return NextResponse.json({ ok: true, removedSkills: skillIds.length });
  }

  if (action === "creche-skill-save") {
    const { id, domainId, name, sortOrder } = body;
    if (!name) return NextResponse.json({ error: "Skill name is required" }, { status: 400 });
    const domain = await db.crecheDomain.findFirst({ where: { id: domainId, schoolId } });
    if (!domain) return NextResponse.json({ error: "Domain not found" }, { status: 404 });
    const data = { name: String(name).trim().slice(0, 200), sortOrder: Number(sortOrder) || 0, domainId: domain.id };
    if (id) {
      const existing = await db.crecheSkill.findUnique({ where: { id } });
      if (!existing || existing.schoolId !== schoolId) return NextResponse.json({ error: "Skill not found" }, { status: 404 });
      const s = await db.crecheSkill.update({ where: { id }, data });
      return NextResponse.json({ skill: s });
    }
    const s = await db.crecheSkill.create({ data: { ...data, schoolId } });
    return NextResponse.json({ skill: s });
  }

  if (action === "creche-skill-delete") {
    const s = await db.crecheSkill.findUnique({ where: { id: body.id } });
    if (!s || s.schoolId !== schoolId) return NextResponse.json({ error: "Skill not found" }, { status: 404 });
    await db.crecheRating.deleteMany({ where: { skillId: s.id } });
    await db.crecheSkill.delete({ where: { id: s.id } });
    return NextResponse.json({ ok: true });
  }

  /* ── CRÈCHE RATINGS: batch upsert by student+term+skill (gradebook.enter) ── */
  if (action === "creche-ratings-save") {
    const { classroomId, termId, ratings } = body as {
      classroomId: string; termId: string;
      ratings: { studentId: string; skillId: string; code: string | null }[];
    };
    if (!classroomId || !termId || !Array.isArray(ratings)) return NextResponse.json({ error: "classroomId, termId and ratings[] are required" }, { status: 400 });
    if (ratings.length > 1000) return NextResponse.json({ error: "At most 1000 ratings per save" }, { status: 400 });
    const classroom = await db.classroom.findFirst({ where: { id: classroomId, schoolId } });
    const term = await db.term.findFirst({ where: { id: termId, academicYear: { schoolId } } });
    if (!classroom || !term) return NextResponse.json({ error: "Class or term not found" }, { status: 404 });
    const scale = await db.crecheRatingScale.findMany({ where: { schoolId } });
    const validCodes = new Set(scale.map((s) => s.code));
    const enrolled = await db.enrollment.findMany({ where: { classroomId, status: "ACTIVE" }, select: { studentId: true } });
    const studentIds = new Set(enrolled.map((e) => e.studentId));

    let saved = 0, cleared = 0;
    for (const r of ratings) {
      if (!r?.studentId || !r?.skillId) continue;
      if (!studentIds.has(r.studentId)) return NextResponse.json({ error: "Learner is not actively enrolled in this class" }, { status: 400 });
      const skill = await db.crecheSkill.findFirst({ where: { id: r.skillId, schoolId } });
      if (!skill) return NextResponse.json({ error: "Skill not found" }, { status: 404 });
      if (r.code !== null && !validCodes.has(r.code)) return NextResponse.json({ error: `Unknown rating code: ${r.code}` }, { status: 400 });
      if (r.code === null || r.code === "") {
        // clearing the rating removes the row
        const res = await db.crecheRating.deleteMany({ where: { studentId: r.studentId, termId, skillId: r.skillId } });
        cleared += res.count;
      } else {
        await db.crecheRating.upsert({
          where: { studentId_termId_skillId: { studentId: r.studentId, termId, skillId: r.skillId } },
          create: { schoolId, studentId: r.studentId, termId, skillId: r.skillId, code: r.code, byName: user.fullName },
          update: { code: r.code, byName: user.fullName, at: new Date() },
        });
        saved++;
      }
    }
    return NextResponse.json({ saved, cleared });
  }

  /* ── CRÈCHE REPORT GENERATOR: ReportCards with one row per domain —
     grade = dominant rating code (mode), remark = scale label, position
     = rated skill count. Creates/publishes like the academic generator. ── */
  if (action === "creche-report-generate") {
    const { classroomId, termId } = body as { classroomId: string; termId: string };
    if (!classroomId || !termId) return NextResponse.json({ error: "classroomId and termId are required" }, { status: 400 });
    const classroom = await db.classroom.findFirst({ where: { id: classroomId, schoolId } });
    const term = await db.term.findFirst({ where: { id: termId, academicYear: { schoolId } } });
    if (!classroom || !term) return NextResponse.json({ error: "Class or term not found" }, { status: 404 });
    const [scale, domains, skills, enrolled, ratings] = await Promise.all([
      db.crecheRatingScale.findMany({ where: { schoolId }, orderBy: { sortOrder: "asc" } }),
      db.crecheDomain.findMany({ where: { schoolId, active: true }, orderBy: { sortOrder: "asc" } }),
      db.crecheSkill.findMany({ where: { schoolId } }),
      db.enrollment.findMany({ where: { classroomId, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" } }),
      db.crecheRating.findMany({ where: { schoolId, termId } }),
    ]);
    if (!enrolled.length) return NextResponse.json({ error: "No learners actively enrolled in this class" }, { status: 400 });
    if (!ratings.length) return NextResponse.json({ error: "No crèche ratings recorded for this term yet — rate the learners in the Gradebook first." }, { status: 400 });
    const scaleByCode = new Map(scale.map((s) => [s.code, s]));
    const codeOrder = new Map(scale.map((s, i) => [s.code, i]));
    const ratingByStudent = new Map<string, string[]>(); // studentId → skillIds rated
    for (const r of ratings) {
      const list = ratingByStudent.get(r.studentId) ?? [];
      list.push(r.skillId);
      ratingByStudent.set(r.studentId, list);
    }
    const codeByStudentSkill = new Map(ratings.map((r) => [`${r.studentId}_${r.skillId}`, r.code]));
    // dominant code of a list (mode; tie → higher on the scale list)
    const dominant = (codes: string[]): string | null => {
      if (!codes.length) return null;
      const counts = new Map<string, number>();
      for (const c of codes) counts.set(c, (counts.get(c) ?? 0) + 1);
      return [...counts.entries()].sort((a, b) => b[1] - a[1] || (codeOrder.get(a[0]) ?? 99) - (codeOrder.get(b[0]) ?? 99))[0][0];
    };
    const remarkFor = (code: string | null): string | null => {
      if (!code) return null;
      if (code === "EX" || code === "GD") return "A lovely term — keep nurturing these strengths at home.";
      if (code === "SD") return "Developing well — more practice will steady the progress.";
      return "Needs closer support and encouragement both at school and at home.";
    };

    let created = 0;
    for (const en of enrolled) {
      const existing = await db.reportCard.findFirst({ where: { studentId: en.studentId, termId } });
      if (existing) { await db.reportCardResult.deleteMany({ where: { reportCardId: existing.id } }); await db.reportCard.delete({ where: { id: existing.id } }); }
      const allCodes = (ratingByStudent.get(en.studentId) ?? []).map((sk) => codeByStudentSkill.get(`${en.studentId}_${sk}`)).filter(Boolean) as string[];
      const overall = dominant(allCodes);
      const rc = await db.reportCard.create({
        data: {
          studentId: en.studentId, termId, classroomName: classroom.name,
          classSize: enrolled.length, published: false,
          teacherRemark: remarkFor(overall),
          headRemark: overall ? `${scaleByCode.get(overall)?.label ?? overall} overall performance across the developmental domains.` : null,
        },
      });
      for (const d of domains) {
        const domSkills = skills.filter((s) => s.domainId === d.id);
        const codes = domSkills.map((s) => codeByStudentSkill.get(`${en.studentId}_${s.id}`)).filter(Boolean) as string[];
        const dom = dominant(codes);
        await db.reportCardResult.create({
          data: {
            reportCardId: rc.id, subjectName: d.name,
            grade: dom, remark: dom ? (scaleByCode.get(dom)?.label ?? null) : null,
            position: codes.length || null, // rated skill count for this domain
          },
        });
      }
      created++;
    }
    return NextResponse.json({ created, domains: domains.length });
  }

  /* ── SBA MARKS CONFIG (settings.manage) ── */
  if (action === "sba-config") {
    const { classTestMax, groupWorkMax, test2Max, projectMax, classScoreWeightPct, portfolioAutoFillPct } = body;
    const entries: [string, unknown][] = [
      ["sba.classTestMax", classTestMax], ["sba.groupWorkMax", groupWorkMax],
      ["sba.test2Max", test2Max], ["sba.projectMax", projectMax],
      ["sba.classScoreWeightPct", classScoreWeightPct], ["sba.portfolioAutoFillPct", portfolioAutoFillPct],
    ];
    for (const [key, raw] of entries) {
      if (raw === undefined || raw === null || raw === "") continue;
      const n = Number(raw);
      if (!Number.isFinite(n) || n < 0 || n > 100) return NextResponse.json({ error: `${key} must be a number between 0 and 100` }, { status: 400 });
    }
    for (const [key, raw] of entries) {
      if (raw === undefined || raw === null || raw === "") continue;
      const value = JSON.stringify(Number(raw));
      await db.schoolSetting.upsert({
        where: { schoolId_key: { schoolId, key } },
        create: { schoolId, key, value }, update: { value },
      });
    }
    return NextResponse.json({ config: await sbaConfig(schoolId) });
  }

  /* ── SBA SHEET SAVE: persists the four SBA columns + exam as ordinary
     Assessment + Mark rows (one assessment per column, title convention
     "SBA Class Test · <term name>", maxScore = configured max). ── */
  if (action === "sba-sheet-save") {
    const { classroomId, subjectId, termId, rows } = body as {
      classroomId: string; subjectId: string; termId: string;
      rows: { studentId: string; classTest?: number | null; groupWork?: number | null; test2?: number | null; project?: number | null; exam?: number | null }[];
    };
    if (!classroomId || !subjectId || !termId || !Array.isArray(rows)) return NextResponse.json({ error: "classroomId, subjectId, termId and rows[] are required" }, { status: 400 });
    if (rows.length > 200) return NextResponse.json({ error: "At most 200 learners per save" }, { status: 400 });
    const classroom = await db.classroom.findFirst({ where: { id: classroomId, schoolId } });
    const subject = await db.subject.findFirst({ where: { id: subjectId, schoolId } });
    const term = await db.term.findFirst({ where: { id: termId, academicYear: { schoolId } } });
    if (!classroom || !subject || !term) return NextResponse.json({ error: "Class, subject or term not found" }, { status: 404 });
    const config = await sbaConfig(schoolId);
    const cols = SBA_COLUMNS.map((c) => ({ ...c, max: config[c.setting] }));

    // find-or-create one assessment per column (+ exam /100)
    const assessments: Record<string, { id: string; max: number }> = {};
    for (const c of [...cols.map((c) => ({ ...c, kind: c.key as SbaKey })), { title: SBA_EXAM_TITLE, type: "END_OF_TERM", max: 100, kind: "exam" as const }] as { title: string; type: string; max: number; kind: string }[]) {
      const title = sbaTitle(c.title, term.name);
      let a = await db.assessment.findFirst({ where: { classroomId, subjectId, termId, title } });
      if (a) {
        if (a.maxScore !== c.max) a = await db.assessment.update({ where: { id: a.id }, data: { maxScore: c.max } });
      } else {
        a = await db.assessment.create({ data: { schoolId, classroomId, termId, subjectId, title, type: c.type, maxScore: c.max, date: new Date() } });
      }
      assessments[c.kind] = { id: a.id, max: c.max };
    }

    let savedMarks = 0, clearedMarks = 0;
    for (const row of rows) {
      if (!row?.studentId) continue;
      for (const [kind, rawUnknown] of Object.entries({ classTest: row.classTest, groupWork: row.groupWork, test2: row.test2, project: row.project, exam: row.exam })) {
        const target = assessments[kind];
        if (!target) continue;
        const raw: unknown = rawUnknown;
        if (raw === null || raw === undefined || raw === "") {
          const res = await db.mark.deleteMany({ where: { assessmentId: target.id, studentId: row.studentId } });
          clearedMarks += res.count;
          continue;
        }
        const num = Number(raw);
        if (!Number.isFinite(num)) continue;
        const score = Math.max(0, Math.min(target.max, num));
        const existing = await db.mark.findFirst({ where: { assessmentId: target.id, studentId: row.studentId } });
        if (existing) await db.mark.update({ where: { id: existing.id }, data: { score } });
        else await db.mark.create({ data: { assessmentId: target.id, studentId: row.studentId, score } });
        savedMarks++;
      }
    }
    return NextResponse.json({
      saved: savedMarks, cleared: clearedMarks,
      assessments: Object.entries(assessments).map(([kind, a]) => ({ kind, id: a.id, maxScore: a.max })),
    });
  }

  /* ── SBA PORTFOLIO AUTO-FILL: class test column ← portfolio term
     average % × portfolioAutoFillPct / 100, clamped to classTestMax.
     Only empty cells unless overwrite=true. ── */
  if (action === "sba-portfolio-fill") {
    const { classroomId, subjectId, termId, overwrite } = body as { classroomId: string; subjectId: string; termId: string; overwrite?: boolean };
    if (!classroomId || !subjectId || !termId) return NextResponse.json({ error: "classroomId, subjectId and termId are required" }, { status: 400 });
    const classroom = await db.classroom.findFirst({ where: { id: classroomId, schoolId } });
    const subject = await db.subject.findFirst({ where: { id: subjectId, schoolId } });
    const term = await db.term.findFirst({ where: { id: termId, academicYear: { schoolId } } });
    if (!classroom || !subject || !term) return NextResponse.json({ error: "Class, subject or term not found" }, { status: 404 });
    const config = await sbaConfig(schoolId);
    const classTestMax = config["sba.classTestMax"];
    const pct = config["sba.portfolioAutoFillPct"];

    // portfolio entries inside the term — one score per learner per day, latest wins
    const entriesRaw = await db.portfolioEntry.findMany({
      where: { schoolId, classroomId, subjectId, entryDate: { gte: term.startsAt, lte: term.endsAt } },
    });
    const entries = Object.values(
      entriesRaw.reduce((acc: Record<string, typeof entriesRaw[number]>, e) => {
        const key = `${e.studentId}|${ymd(e.entryDate)}`;
        if (!acc[key] || acc[key].createdAt < e.createdAt) acc[key] = e;
        return acc;
      }, {}),
    );
    const enrolled = await db.enrollment.findMany({ where: { classroomId, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" } });

    // find-or-create the Class Test assessment
    const title = sbaTitle("SBA Class Test", term.name);
    let assessment = await db.assessment.findFirst({ where: { classroomId, subjectId, termId, title } });
    if (assessment) {
      if (assessment.maxScore !== classTestMax) assessment = await db.assessment.update({ where: { id: assessment.id }, data: { maxScore: classTestMax } });
    } else {
      assessment = await db.assessment.create({ data: { schoolId, classroomId, termId, subjectId, title, type: "EXERCISE", maxScore: classTestMax, date: new Date() } });
    }

    let filled = 0, skipped = 0;
    const outRows: { studentId: string; name: string; days: number; averagePct: number | null; classTest: number | null }[] = [];
    for (const en of enrolled) {
      const mine = entries.filter((e) => e.studentId === en.studentId);
      const existing = await db.mark.findFirst({ where: { assessmentId: assessment.id, studentId: en.studentId } });
      let classTest: number | null = null;
      if (mine.length) {
        const avg = mine.reduce((s, e) => s + e.score, 0) / mine.length; // /10
        const avgPct = round2((avg / 10) * 100);
        classTest = Math.min(classTestMax, round2((avgPct * pct) / 100));
        if (existing && existing.score !== null && !overwrite) {
          skipped++; // keep the teacher's own entry
        } else {
          if (existing) await db.mark.update({ where: { id: existing.id }, data: { score: classTest } });
          else await db.mark.create({ data: { assessmentId: assessment.id, studentId: en.studentId, score: classTest } });
          filled++;
        }
        outRows.push({ studentId: en.studentId, name: `${en.student.firstName} ${en.student.lastName}`, days: mine.length, averagePct: avgPct, classTest });
      } else {
        skipped++;
        outRows.push({ studentId: en.studentId, name: `${en.student.firstName} ${en.student.lastName}`, days: 0, averagePct: null, classTest: existing?.score ?? null });
      }
    }
    return NextResponse.json({ filled, skipped, portfolioPct: pct, classTestMax, assessment: { id: assessment.id, title, maxScore: classTestMax }, rows: outRows });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

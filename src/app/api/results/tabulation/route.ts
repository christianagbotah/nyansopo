import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

/* ─────────────────────────────────────────────────────────────
   GET /api/results/tabulation?schoolId&termId&classId — wave 33.

   Exam tabulation sheet (CI3 tabulation_sheet / tabulation_sheet_
   raw_score parity, perm: reports.view): the class-term ledger laid
   out the way the exams officer keeps the master sheet — one row
   per learner, one CLASS / EXAM / TOTAL column group per subject,
   class-score and exam-score sub-totals, the grand total and a
   competition-ranked position (ties share a position, same
   doctrine as the wave-20 subject ranks).

   Subject stats footer mirrors the sheet's pencilled margins:
   entries, highest, lowest, mean, pass rate (≥50) and the B3-and-
   above distinction count per subject. Every number comes from the
   same TermResult ledger the broadsheet and report cards read, so
   the tabulation sheet can never disagree with a printed report.
   ───────────────────────────────────────────────────────────── */

const round1 = (n: number) => Math.round(n * 10) / 10;

export async function GET(req: NextRequest) {
  const url = new URL(req.url);
  const q = (k: string) => url.searchParams.get(k) ?? "";
  const schoolId = q("schoolId");
  const termId = q("termId");
  const classId = q("classId");
  if (!schoolId || !termId || !classId) {
    return NextResponse.json({ error: "schoolId, termId and classId are required" }, { status: 400 });
  }

  const auth = await opsAuth(req, schoolId, "GET", { perms: ["reports.view"] });
  if (!auth.ok) return auth.res;

  const [school, term, classroom] = await Promise.all([
    db.school.findUnique({ where: { id: schoolId }, select: { id: true, name: true, code: true, logoColor: true } }),
    db.term.findFirst({
      where: { id: termId, academicYear: { schoolId } },
      include: { academicYear: { select: { name: true } } },
    }),
    db.classroom.findFirst({ where: { id: classId, schoolId }, select: { id: true, name: true } }),
  ]);
  if (!term) return NextResponse.json({ error: "Term not found in this school" }, { status: 404 });
  if (!classroom) return NextResponse.json({ error: "Class not found in this school" }, { status: 404 });

  const enrolled = await db.enrollment.findMany({
    where: { classroomId: classId, status: "ACTIVE" },
    include: { student: { select: { id: true, firstName: true, lastName: true, otherNames: true, gender: true } } },
    orderBy: { rollNo: "asc" },
  });
  const studentIds = enrolled.map((e) => e.studentId);
  const nameOf = (s: { firstName: string; lastName: string; otherNames: string | null }) =>
    [s.lastName, s.firstName, s.otherNames].filter(Boolean).join(" ");

  const [rows, cards, studentsDb] = await Promise.all([
    db.termResult.findMany({
      where: { termId, classroomId: classId, studentId: { in: studentIds } },
      include: { subject: { select: { id: true, name: true, code: true } } },
      orderBy: { createdAt: "asc" },
    }),
    db.reportCard.findMany({
      where: { termId, studentId: { in: studentIds } },
      select: { id: true, studentId: true, published: true },
    }),
    db.student.findMany({
      where: { id: { in: studentIds } },
      select: { id: true, nyansapoIndex: true },
    }),
  ]);
  const indexById = new Map(studentsDb.map((s) => [s.id, s.nyansapoIndex]));

  /* subject order — alphabetical, exactly like the printed sheet */
  const subjectMap = new Map<string, { id: string; name: string; code: string }>();
  for (const r of rows) {
    if (!subjectMap.has(r.subjectId)) subjectMap.set(r.subjectId, { id: r.subjectId, name: r.subject.name, code: r.subject.code });
  }
  const subjects = [...subjectMap.values()].sort((a, b) => a.name.localeCompare(b.name));

  /* per-student cell grid + sub-totals */
  type Cell = {
    classScore: number | null; examScore: number | null; total: number | null;
    grade: string | null; position: number | null; published: boolean;
  };
  type Agg = {
    cells: Map<string, Cell>;
    classTotal: number; examTotal: number; grandTotal: number | null;
  };
  const byStudent = new Map<string, Agg>();
  for (const r of rows) {
    let a = byStudent.get(r.studentId);
    if (!a) { a = { cells: new Map(), classTotal: 0, examTotal: 0, grandTotal: null }; byStudent.set(r.studentId, a); }
    a.cells.set(r.subjectId, {
      classScore: r.classScore, examScore: r.examScore, total: r.total,
      grade: r.grade, position: r.position, published: r.published,
    });
    if (r.classScore !== null) a.classTotal += r.classScore;
    if (r.examScore !== null) a.examTotal += r.examScore;
    if (r.total !== null) a.grandTotal = (a.grandTotal ?? 0) + r.total;
  }

  /* competition rank on the grand totals ("1224" style) */
  const ranked = [...byStudent.entries()]
    .filter(([, a]) => a.grandTotal !== null)
    .sort((x, y) => (y[1].grandTotal as number) - (x[1].grandTotal as number));
  const positionOf = new Map<string, number>();
  let pos = 0;
  let prev: number | null = null;
  for (const [i, [sid, a]] of ranked.entries()) {
    if (prev === null || a.grandTotal !== prev) { pos = i + 1; prev = a.grandTotal; }
    positionOf.set(sid, pos);
  }

  const cardByStudent = new Map(cards.map((c) => [c.studentId, c]));

  const studentRows = enrolled.map((e) => {
    const a = byStudent.get(e.studentId);
    const cells: Record<string, Cell> = {};
    if (a) for (const [sid, cell] of a.cells) cells[sid] = cell;
    const card = cardByStudent.get(e.studentId);
    return {
      studentId: e.studentId,
      name: nameOf(e.student),
      indexNo: indexById.get(e.studentId) ?? "",
      gender: e.student.gender,
      cells,
      classTotal: a ? round1(a.classTotal) : null,
      examTotal: a ? round1(a.examTotal) : null,
      grandTotal: a && a.grandTotal !== null ? round1(a.grandTotal) : null,
      position: positionOf.get(e.studentId) ?? null,
      compiled: !!card,
      published: !!card && !!card.published,
    };
  });

  /* per-subject stats — the sheet's pencilled margins */
  const subjectStats = subjects.map((s) => {
    const totals = rows
      .filter((r) => r.subjectId === s.id && r.total !== null)
      .map((r) => r.total as number);
    const n = totals.length;
    const average = n ? totals.reduce((a, b) => a + b, 0) / n : null;
    return {
      subjectId: s.id,
      entries: n,
      highest: n ? round1(Math.max(...totals)) : null,
      lowest: n ? round1(Math.min(...totals)) : null,
      average: average !== null ? round1(average) : null,
      passRate: n ? Math.round((totals.filter((t) => t >= 50).length / n) * 1000) / 10 : null,
      distinctions: totals.filter((t) => t >= 65).length, // B3-and-above doctrine
    };
  });

  const graded = rows.filter((r) => r.total !== null);
  const grandAvg = graded.length
    ? round1(graded.reduce((s, r) => s + (r.total as number), 0) / graded.length)
    : null;

  return NextResponse.json({
    school,
    term: { id: term.id, name: term.name, yearName: term.academicYear.name },
    classroom,
    subjects,
    rows: studentRows,
    subjectStats,
    summary: {
      onRoll: enrolled.length,
      compiled: cards.length,
      published: cards.filter((c) => c.published).length,
      subjects: subjects.length,
      entries: graded.length,
      grandAvg,
      passRate: graded.length
        ? Math.round((graded.filter((r) => (r.total as number) >= 50).length / graded.length) * 1000) / 10
        : null,
    },
  });
}

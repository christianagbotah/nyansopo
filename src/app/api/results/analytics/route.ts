import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

/* ─────────────────────────────────────────────────────────────
   GET /api/results/analytics?schoolId&termId&classId — wave 22.

   Terminal-analytics payload for the Results & Report Cards module
   (perm: reports.view): the class-term's ledger read as WAEC charts —
   per-subject averages, pass rates (≥50) and full A1..F9 grade
   distributions, plus the class-level summary (overall average, pass
   rate, distinction/referral counts, top & bottom performers and the
   gender split). Everything is computed from the same TermResult
   rows the broadsheet grid shows, so analytics can never disagree
   with the printed reports.
   ───────────────────────────────────────────────────────────── */

/** canonical WAEC BECE band order (A1 best … F9 worst) */
const WAEC_BANDS = ["A1", "B2", "B3", "C4", "C5", "C6", "D7", "E8", "F9"] as const;

const bandColor = (grade: string): string =>
  grade === "A1" || grade === "B2" || grade === "B3"
    ? "emerald"
    : grade === "C4" || grade === "C5" || grade === "C6"
      ? "amber"
      : "red";

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
    db.school.findUnique({ where: { id: schoolId }, select: { id: true, name: true, code: true } }),
    db.term.findFirst({ where: { id: termId, academicYear: { schoolId } }, include: { academicYear: { select: { name: true } } } }),
    db.classroom.findFirst({ where: { id: classId, schoolId }, select: { id: true, name: true } }),
  ]);
  if (!term) return NextResponse.json({ error: "Term not found in this school" }, { status: 404 });
  if (!classroom) return NextResponse.json({ error: "Class not found in this school" }, { status: 404 });

  const enrolled = await db.enrollment.findMany({
    where: { classroomId: classId, status: "ACTIVE" },
    include: { student: { select: { id: true, firstName: true, lastName: true, otherNames: true, gender: true } } },
  });
  const studentIds = enrolled.map((e) => e.studentId);
  const nameOf = (id: string) => {
    const s = enrolled.find((e) => e.studentId === id)?.student;
    return s ? [s.lastName, s.firstName, s.otherNames].filter(Boolean).join(" ") : "—";
  };

  const [rows, cards] = await Promise.all([
    db.termResult.findMany({
      where: { termId, classroomId: classId, studentId: { in: studentIds } },
      include: { subject: { select: { id: true, name: true, code: true } } },
    }),
    db.reportCard.findMany({
      where: { termId, studentId: { in: studentIds } },
      include: { student: { select: { id: true, gender: true } } },
    }),
  ]);

  /* ── per-subject analytics ── */
  const bySubject = new Map<string, {
    subjectId: string; name: string; code: string;
    totals: { studentId: string; total: number }[];
  }>();
  for (const r of rows) {
    const b = bySubject.get(r.subjectId) ?? { subjectId: r.subjectId, name: r.subject.name, code: r.subject.code, totals: [] };
    if (r.total !== null) b.totals.push({ studentId: r.studentId, total: r.total });
    bySubject.set(r.subjectId, b);
  }

  const subjects = [...bySubject.values()].sort((a, b) => a.name.localeCompare(b.name)).map((b) => {
    const n = b.totals.length;
    const average = n ? b.totals.reduce((s, t) => s + t.total, 0) / n : null;
    const passed = b.totals.filter((t) => t.total >= 50).length;
    const grades = WAEC_BANDS.map((g) => ({
      grade: g as string,
      count: rows.filter((r) => r.subjectId === b.subjectId && r.grade === g && r.total !== null).length,
      tone: bandColor(g),
    }));
    const sorted = [...b.totals].sort((x, y) => y.total - x.total);
    const top = sorted[0] ? { name: nameOf(sorted[0].studentId), total: sorted[0].total } : null;
    const weak = sorted.length > 1 ? { name: nameOf(sorted[sorted.length - 1].studentId), total: sorted[sorted.length - 1].total } : null;
    return {
      subjectId: b.subjectId, name: b.name, code: b.code,
      entries: n,
      average: average !== null ? Math.round(average * 10) / 10 : null,
      passRate: n ? Math.round((passed / n) * 1000) / 10 : null,
      grades,
      top, weak,
    };
  });

  /* ── class-level analytics ── */
  const graded = rows.filter((r) => r.total !== null);
  const classAverage = graded.length ? graded.reduce((s, r) => s + (r.total as number), 0) / graded.length : null;
  const classPassed = graded.filter((r) => (r.total as number) >= 50).length;
  const classGrades = WAEC_BANDS.map((g) => ({
    grade: g as string,
    count: graded.filter((r) => r.grade === g).length,
    tone: bandColor(g),
  }));

  const cardsWithAvg = cards.filter((c) => c.average !== null);
  const sortedCards = [...cardsWithAvg].sort((a, b) => (b.average as number) - (a.average as number));
  const publishedCount = cards.filter((c) => c.published).length;
  const boys = cards.filter((c) => c.student.gender === "M" && c.average !== null);
  const girls = cards.filter((c) => c.student.gender === "F" && c.average !== null);
  const mean = (xs: typeof cards) => (xs.length ? Math.round((xs.reduce((s, c) => s + (c.average as number), 0) / xs.length) * 10) / 10 : null);

  const summary = {
    learners: cards.length,
    published: publishedCount,
    subjects: bySubject.size,
    entries: graded.length,
    average: classAverage !== null ? Math.round(classAverage * 10) / 10 : null,
    passRate: graded.length ? Math.round((classPassed / graded.length) * 1000) / 10 : null,
    distinctions: cardsWithAvg.filter((c) => (c.average as number) >= 65).length,
    belowBar: cardsWithAvg.filter((c) => (c.average as number) < 50).length,
    grades: classGrades,
    top: sortedCards.slice(0, 3).map((c) => ({ name: nameOf(c.studentId), average: c.average, position: c.classPosition })),
    bottom: sortedCards.length > 3 ? sortedCards.slice(-3).reverse().map((c) => ({ name: nameOf(c.studentId), average: c.average, position: c.classPosition })) : [],
    boys: { count: cards.filter((c) => c.student.gender === "M").length, average: mean(boys) },
    girls: { count: cards.filter((c) => c.student.gender === "F").length, average: mean(girls) },
  };

  return NextResponse.json({
    school, term: { id: term.id, name: term.name, yearName: term.academicYear.name }, classroom,
    subjects, summary,
    weakSubjects: subjects.filter((s) => s.passRate !== null && s.passRate < 50).map((s) => ({ name: s.name, passRate: s.passRate })),
  });
}

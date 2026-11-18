import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

/* ─────────────────────────────────────────────────────────────
   GET /api/results/transcript?schoolId&studentId — wave 21
   printable academic transcript (perm: reports.view).

   The learner's whole academic history on one sheet: every term
   that carries either a compiled ReportCard or TermResult ledger
   rows, ordered chronologically. Each term section carries the
   Ghana-format subject rows (30/70/100, WAEC BECE band, subject
   position, remark) straight from the wave-20 ledger — falling
   back to the legacy ReportCardResult rows for early-years terms
   compiled before the ledger existed — plus the term summary
   (average, class position, best-6 aggregate, attendance %).

   Summary strip: terms on record, best aggregate (lowest), and
   the overall average of the term averages.
   ───────────────────────────────────────────────────────────── */

export async function GET(req: NextRequest) {
  const url = new URL(req.url);
  const schoolId = url.searchParams.get("schoolId") ?? "";
  const studentId = url.searchParams.get("studentId") ?? "";
  if (!schoolId || !studentId) {
    return NextResponse.json({ error: "schoolId and studentId are required" }, { status: 400 });
  }

  const auth = await opsAuth(req, schoolId, "GET", { perms: ["reports.view"] });
  if (!auth.ok) return auth.res;

  const student = await db.student.findUnique({
    where: { id: studentId },
    select: {
      id: true, firstName: true, lastName: true, otherNames: true,
      nyansapoIndex: true, gender: true, dateOfBirth: true,
    },
  });
  // Student carries no schoolId — scope through ledger rows or enrolment
  const [viaLedger, viaEnrollment] = await Promise.all([
    db.termResult.count({ where: { studentId, schoolId } }),
    db.enrollment.count({ where: { studentId, classroom: { schoolId } } }),
  ]);
  if (!student || (!viaLedger && !viaEnrollment)) {
    return NextResponse.json({ error: "Student not found in this school" }, { status: 404 });
  }

  const [school, cards, ledger] = await Promise.all([
    db.school.findUnique({
      where: { id: schoolId },
      select: {
        id: true, name: true, code: true, motto: true, town: true, region: true,
        district: true, gpsAddress: true, phone: true, email: true, logoColor: true, curriculum: true,
      },
    }),
    db.reportCard.findMany({
      where: { studentId, term: { academicYear: { schoolId } } },
      include: { term: { include: { academicYear: true } }, results: { orderBy: { subjectName: "asc" } } },
    }),
    db.termResult.findMany({
      where: { studentId, schoolId },
      include: { subject: { select: { id: true, name: true, code: true } }, term: { include: { academicYear: true } } },
    }),
  ]);

  const classroomIds = [...new Set(ledger.map((r) => r.classroomId))];
  const classrooms = classroomIds.length
    ? await db.classroom.findMany({ where: { id: { in: classroomIds } }, select: { id: true, name: true } })
    : [];
  const classNameById = new Map(classrooms.map((c) => [c.id, c.name]));

  /* merge every term that carries either a card or ledger rows */
  type TermSection = {
    termId: string; termName: string; yearName: string; classroomName: string;
    cardId: string | null;
    totalScore: number | null; average: number | null;
    classPosition: number | null; classSize: number | null;
    aggregate: number | null; attendancePct: number | null;
    promoted: boolean | null; published: boolean;
    teacherRemark: string | null; headRemark: string | null;
    rows: {
      subjectName: string; subjectCode: string | null;
      classScore: number | null; examScore: number | null;
      total: number | null; grade: string | null; position: number | null; remark: string | null;
    }[];
    startsAt: string; sequence: number;
  };

  const sections = new Map<string, TermSection>();

  const bump = (t: { id: string; name: string; sequence: number; academicYear: { name: string; startsAt: Date } }) => {
    const existing = sections.get(t.id);
    if (existing) return existing;
    const fresh: TermSection = {
      termId: t.id, termName: t.name, yearName: t.academicYear.name, classroomName: "",
      cardId: null, totalScore: null, average: null, classPosition: null, classSize: null,
      aggregate: null, attendancePct: null, promoted: null, published: false,
      teacherRemark: null, headRemark: null, rows: [],
      startsAt: t.academicYear.startsAt.toISOString(), sequence: t.sequence,
    };
    sections.set(t.id, fresh);
    return fresh;
  };

  for (const c of cards) {
    const s = bump(c.term);
    s.cardId = c.id;
    s.classroomName = c.classroomName;
    s.totalScore = c.totalScore;
    s.average = c.average;
    s.classPosition = c.classPosition;
    s.classSize = c.classSize;
    s.aggregate = c.aggregate;
    s.attendancePct = c.attendancePct;
    s.promoted = c.promoted;
    s.published = c.published;
    s.teacherRemark = c.teacherRemark;
    s.headRemark = c.headRemark;
    s.rows = c.results.map((r) => ({
      subjectName: r.subjectName, subjectCode: r.subjectCode,
      classScore: r.classScore, examScore: r.examScore, total: r.total,
      grade: r.grade, position: r.position, remark: r.remark,
    }));
  }

  // ledger rows — the wave-20 source of truth when present; they also
  // back-fill class names for terms that only have ledger rows
  const ledgerByTerm = new Map<string, typeof ledger>();
  for (const r of ledger) {
    const list = ledgerByTerm.get(r.termId) ?? [];
    list.push(r);
    ledgerByTerm.set(r.termId, list);
  }
  for (const [termId, list] of ledgerByTerm) {
    const s = bump(list[0].term);
    s.classroomName = s.classroomName || classNameById.get(list[0].classroomId) || "";
    s.published = list.every((r) => r.published);
    if (list.length) {
      s.rows = list
        .slice()
        .sort((a, b) => a.subject.name.localeCompare(b.subject.name))
        .map((r) => ({
          subjectName: r.subject.name, subjectCode: r.subject.code,
          classScore: r.classScore, examScore: r.examScore, total: r.total,
          grade: r.grade, position: r.position, remark: r.remarks,
        }));
    }
  }

  // chronological — oldest academic year first, then term sequence
  const terms = [...sections.values()].sort(
    (a, b) => a.startsAt.localeCompare(b.startsAt) || a.sequence - b.sequence,
  );

  const averages = terms.map((t) => t.average).filter((a): a is number => a !== null);
  const aggregates = terms.map((t) => t.aggregate).filter((a): a is number => a !== null);

  return NextResponse.json({
    school,
    student,
    terms,
    summary: {
      terms: terms.length,
      bestAggregate: aggregates.length ? Math.min(...aggregates) : null,
      overallAverage: averages.length ? Math.round((averages.reduce((s, a) => s + a, 0) / averages.length) * 10) / 10 : null,
    },
  });
}

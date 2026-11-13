import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { assertResultsComputable } from "@/lib/result-approval";
import {
  weightedTotal, gradeBand, gradeFor, rankClass, bestSixAggregate, resultRemark, autoRemarks,
} from "@/lib/grading";

/* ─────────────────────────────────────────────────────────────
   POST /api/results/compute — wave 20 Term Results compiler.

   Body: { schoolId, termId, classId }  (perm: reports.publish)

   Ghana BECE-style compile for ONE class in ONE term:
   1. Rolls every Assessment/Mark up into per-subject components —
      class assessment (all non-END_OF_TERM work, scaled to /30) and
      the end-of-term exam (END_OF_TERM, scaled to /70). A subject
      with only one component present is graded on what exists
      (total = pct × 100, components left blank) — same doctrine as
      the legacy Exams & Reports generator.
   2. Upserts a TermResult row per student×term×subject
      (@@unique studentId+termId+subjectId) with the weighted total,
      WAEC BECE grade band (A1..F9) and a short band remark.
   3. Competition-ranks each subject across the class (ties share a
      position) and ranks the term average for the class position.
   4. Compiles each learner's ReportCard: best-6 BECE aggregate
      (core ENG/MAT/SCI/SOS + best two others — GES schools only),
      class position/size, attendance % from the term's attendance
      sessions, promoted flag (average ≥ 40) and deterministic
      teacher/head remarks. ReportCardResult rows are rewritten too,
      so the legacy Exams & Reports and parent-portal views stay in
      sync with the new ledger.

   Governance: a LOCKED ResultApproval (class, term) blocks the
   recompute (423), exactly like the legacy generator.
   ───────────────────────────────────────────────────────────── */

/** BECE core subjects drive the best-6 aggregate (WAEC JHS doctrine). */
const BECE_CORE_CODES = new Set(["ENG", "MAT", "SCI", "SOS"]);
/** Attendance statuses that count as a day attended (LATE = came). */
const ATTENDED = new Set(["PRESENT", "LATE", "BUSY"]);

export async function POST(req: NextRequest) {
  const body = await req.json().catch(() => ({}));
  const S = (v: unknown) => (typeof v === "string" ? v.trim() : "");
  const schoolId = S(body.schoolId);
  const termId = S(body.termId);
  const classId = S(body.classId);
  if (!schoolId || !termId || !classId) {
    return NextResponse.json({ error: "schoolId, termId and classId are required" }, { status: 400 });
  }

  const auth = await opsAuth(req, schoolId, "POST", { perms: ["reports.publish"] });
  if (!auth.ok) return auth.res;

  const [school, term, classroom] = await Promise.all([
    db.school.findUnique({ where: { id: schoolId }, select: { curriculum: true } }),
    db.term.findFirst({ where: { id: termId, academicYear: { schoolId } }, include: { academicYear: { select: { id: true, name: true } } } }),
    db.classroom.findFirst({ where: { id: classId, schoolId } }),
  ]);
  if (!term) return NextResponse.json({ error: "Term not found in this school" }, { status: 404 });
  if (!classroom) return NextResponse.json({ error: "Class not found in this school" }, { status: 404 });

  // results governance — LOCKED classes cannot be recomputed
  const gate = await assertResultsComputable(schoolId, classId, termId);
  if (gate.blocked) return NextResponse.json({ error: gate.error }, { status: 423 });

  const enrolled = await db.enrollment.findMany({
    where: { classroomId: classId, status: "ACTIVE" },
    include: { student: { select: { id: true, firstName: true, lastName: true } } },
    orderBy: { rollNo: "asc" },
  });
  if (!enrolled.length) return NextResponse.json({ error: "No learners are actively enrolled in this class" }, { status: 400 });

  const assessments = await db.assessment.findMany({
    where: { classroomId: classId, termId },
    include: { subject: true, marks: true },
  });
  if (!assessments.length) {
    return NextResponse.json({ error: "No assessments recorded for this class and term yet — enter marks in the Gradebook first." }, { status: 400 });
  }

  const curriculum = school?.curriculum ?? "GES";
  const isGES = curriculum === "GES";
  // BECE core flags: canonical core codes when the school uses them,
  // otherwise every subject counts as core (best-6 becomes best-6 overall)
  const beceCoreCodes = assessments.some((a) => BECE_CORE_CODES.has(a.subject.code));

  /* ── 1. roll assessments up into per-subject components ── */
  type Bucket = {
    subjectId: string; name: string; code: string;
    classGot: Map<string, number>; classMax: number;
    examGot: Map<string, number>; examMax: number;
  };
  const buckets = new Map<string, Bucket>();
  for (const a of assessments) {
    const b = buckets.get(a.subjectId) ?? {
      subjectId: a.subjectId, name: a.subject.name, code: a.subject.code,
      classGot: new Map(), classMax: 0, examGot: new Map(), examMax: 0,
    };
    const got = a.type === "END_OF_TERM" ? b.examGot : b.classGot;
    for (const m of a.marks) {
      if (m.score === null) continue;
      got.set(m.studentId, (got.get(m.studentId) ?? 0) + m.score);
    }
    if (a.type === "END_OF_TERM") b.examMax += a.maxScore; else b.classMax += a.maxScore;
    buckets.set(a.subjectId, b);
  }
  const subjectOrder = [...buckets.values()].sort((x, y) => x.name.localeCompare(y.name));

  /* ── 2. per-student subject rows on the 30/70 scale ── */
  type Row = {
    studentId: string; subjectId: string;
    classScore: number | null; examScore: number | null; total: number | null;
    grade: string | null; remark: string | null; position: number | null;
  };
  const rowsByStudent = new Map<string, Row[]>();
  for (const e of enrolled) {
    const rows: Row[] = [];
    for (const b of subjectOrder) {
      const classPct = b.classMax > 0 && b.classGot.has(e.studentId) ? (b.classGot.get(e.studentId) as number) / b.classMax : null;
      const examPct = b.examMax > 0 && b.examGot.has(e.studentId) ? (b.examGot.get(e.studentId) as number) / b.examMax : null;
      if (classPct === null && examPct === null) continue; // nothing assessed for this learner

      let classScore: number | null = null;
      let examScore: number | null = null;
      let total: number | null = null;
      if (classPct !== null && examPct !== null) {
        classScore = Math.round(classPct * 30);
        examScore = Math.round(examPct * 70);
        total = weightedTotal(classScore, examScore);
      } else {
        // single-component subject: grade on what exists, no artificial split
        const pct = classPct ?? examPct ?? 0;
        total = Math.round(pct * 100);
      }
      const band = isGES ? gradeBand(total) : gradeFor(curriculum, total ?? 0);
      rows.push({
        studentId: e.studentId, subjectId: b.subjectId,
        classScore, examScore, total,
        grade: band?.grade ?? null,
        remark: resultRemark(total),
        position: null,
      });
    }
    rowsByStudent.set(e.studentId, rows);
  }

  /* ── 3. competition ranks — per subject, then overall average ── */
  for (const b of subjectOrder) {
    const items: { id: string; total: number | null }[] = [];
    for (const [studentId, rows] of rowsByStudent) {
      const row = rows.find((r) => r.subjectId === b.subjectId);
      if (row) items.push({ id: `${studentId}:${b.subjectId}`, total: row.total });
    }
    const ranks = rankClass(items);
    for (const [key, pos] of ranks) {
      const [studentId] = key.split(":");
      const row = rowsByStudent.get(studentId)?.find((r) => r.subjectId === b.subjectId);
      if (row) row.position = pos;
    }
  }

  const stats = [...rowsByStudent.entries()].map(([studentId, rows]) => {
    const totals = rows.map((r) => r.total).filter((t): t is number => t !== null);
    const average = totals.length ? Math.round((totals.reduce((s, t) => s + t, 0) / totals.length) * 10) / 10 : null;
    return { studentId, rows, average };
  });
  const overallRanks = rankClass(stats.map((s) => ({ id: s.studentId, total: s.average })));

  /* ── attendance % from the term's sessions for this class ── */
  const sessions = await db.attendanceSession.findMany({
    where: { classroomId: classId, termId },
    select: { records: { select: { studentId: true, status: true } } },
  });
  const att = new Map<string, { attended: number; taken: number }>();
  for (const s of sessions) {
    for (const r of s.records) {
      const a = att.get(r.studentId) ?? { attended: 0, taken: 0 };
      a.taken += 1;
      if (ATTENDED.has(r.status)) a.attended += 1;
      att.set(r.studentId, a);
    }
  }

  /* ── 4. persist — one transaction keeps the ledger consistent ── */
  const summary = await db.$transaction(async (tx) => {
    let resultCount = 0;
    let aggregateCount = 0;

    for (const st of stats) {
      const { studentId, rows, average } = st;
      const position = overallRanks.get(studentId) ?? null;
      const record = att.get(studentId);
      const attendancePct = record && record.taken > 0 ? Math.round((record.attended / record.taken) * 100) : null;
      const remarks = autoRemarks(average);
      const promoted = average !== null ? average >= 40 : null;

      // BECE best-6 aggregate (GES schools only)
      let aggregate: number | null = null;
      if (isGES) {
        aggregate = bestSixAggregate(
          rows.map((r) => ({ grade: r.grade, isCore: beceCoreCodes ? BECE_CORE_CODES.has(buckets.get(r.subjectId)?.code ?? "") : true })),
        );
      }
      if (aggregate !== null) aggregateCount++;

      for (const r of rows) {
        await tx.termResult.upsert({
          where: { studentId_termId_subjectId: { studentId, termId, subjectId: r.subjectId } },
          create: {
            schoolId, studentId, termId, classroomId: classId, subjectId: r.subjectId,
            classScore: r.classScore, examScore: r.examScore, total: r.total,
            grade: r.grade, position: r.position, remarks: r.remark, published: false,
          },
          update: {
            classScore: r.classScore, examScore: r.examScore, total: r.total,
            grade: r.grade, position: r.position, remarks: r.remark, classroomId: classId,
            // wave 21: the ledger changed — anything previously published
            // is recalled to draft so portals never show stale marks
            published: false, publishedAt: null,
          },
        });
        resultCount++;
      }

      // compiled report card — now unique per (student, term)
      const card = await tx.reportCard.upsert({
        where: { studentId_termId: { studentId, termId } },
        create: {
          studentId, termId, classroomName: classroom.name,
          totalScore: average !== null ? Math.round(average * rows.length) : null,
          average, classPosition: position, classSize: enrolled.length,
          attendance: record && record.taken > 0 ? `Present ${record.attended} of ${record.taken} days` : null,
          attendancePct, aggregate, promoted,
          teacherRemark: remarks.teacherRemark, headRemark: remarks.headRemark,
          promotion: promoted === null ? null : promoted ? "PROMOTED" : "PROBATION",
          published: false,
        },
        update: {
          classroomName: classroom.name,
          totalScore: average !== null ? Math.round(average * rows.length) : null,
          average, classPosition: position, classSize: enrolled.length,
          attendance: record && record.taken > 0 ? `Present ${record.attended} of ${record.taken} days` : null,
          attendancePct, aggregate, promoted,
          teacherRemark: remarks.teacherRemark, headRemark: remarks.headRemark,
          promotion: promoted === null ? null : promoted ? "PROMOTED" : "PROBATION",
          // wave 21: recomputed cards go back to draft (recall-on-change)
          published: false, publishedAt: null,
        },
      });

      // legacy per-subject rows — keep Exams & Reports / parent portal in sync
      await tx.reportCardResult.deleteMany({ where: { reportCardId: card.id } });
      for (const r of rows) {
        await tx.reportCardResult.create({
          data: {
            reportCardId: card.id,
            subjectName: buckets.get(r.subjectId)?.name ?? "",
            subjectCode: buckets.get(r.subjectId)?.code ?? null,
            isCore: beceCoreCodes ? BECE_CORE_CODES.has(buckets.get(r.subjectId)?.code ?? "") : true,
            classScore: r.classScore, examScore: r.examScore, total: r.total,
            grade: r.grade, position: r.position, remark: r.remark,
          },
        });
      }
    }

    return {
      students: stats.length,
      subjects: subjectOrder.length,
      results: resultCount,
      cards: stats.length,
      aggregates: aggregateCount,
    };
  });

  return NextResponse.json({
    ok: true,
    term: { id: term.id, name: term.name, yearName: term.academicYear.name },
    classroom: { id: classroom.id, name: classroom.name },
    ...summary,
  });
}

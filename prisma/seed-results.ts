/* Wave 20 — one demo term of compiled results for the JHS 3 (B9) class so the
   Results & Report Cards module is populated on first load.

   Reads the BECE subject marks that scripts/seed-jhs3-bece.ts laid down
   (2 class-work items = /30 + one END_OF_TERM exam = /70), then runs the SAME
   pure grading functions the API route uses (src/lib/grading.ts) to upsert a
   full TermResult ledger + compiled ReportCards with:
     • weightedTotal  — class (30) + exam (70)
     • gradeBand      — WAEC BECE A1..F9
     • rankClass      — competition ranking (ties share a position)
     • bestSixAggregate — BECE core ENG/MAT/SCI/SOS + best two others
   Idempotent: safe to re-run (upserts on the wave-20 unique keys).
   Run: DATABASE_URL=... bun prisma/seed-results.ts */
import { PrismaClient } from "@prisma/client";
import {
  weightedTotal, gradeBand, rankClass, bestSixAggregate, resultRemark, autoRemarks,
} from "../src/lib/grading";

const db = new PrismaClient();

/** BECE core subjects drive the best-6 aggregate (WAEC JHS doctrine). */
const BECE_CORE_CODES = new Set(["ENG", "MAT", "SCI", "SOS"]);

async function main() {
  const school = await db.school.findFirst({ where: { name: { contains: "Golden Excellence" } } });
  if (!school) throw new Error("GEA school not found");
  const year = await db.academicYear.findFirst({ where: { schoolId: school.id, isCurrent: true }, include: { terms: true } });
  const term = year?.terms.find((t) => t.isCurrent) ?? year?.terms[0];
  const cls = await db.classroom.findFirst({ where: { schoolId: school.id, name: "JHS 3 (B9)" } });
  if (!term || !cls) throw new Error("current term or JHS 3 (B9) classroom not found");

  const enrolled = await db.enrollment.findMany({
    where: { classroomId: cls.id, status: "ACTIVE" },
    include: { student: true },
    orderBy: { rollNo: "asc" },
  });
  if (!enrolled.length) throw new Error("no learners enrolled in JHS 3 (B9)");

  const assessments = await db.assessment.findMany({
    where: { classroomId: cls.id, termId: term.id },
    include: { subject: true, marks: true },
  });
  if (!assessments.length) throw new Error("no assessments for JHS 3 (B9) in the current term — run seed-jhs3-bece first");

  // roll assessments up per subject: class work → /30, exam → /70
  type Bucket = { subjectId: string; name: string; code: string; classGot: Map<string, number>; classMax: number; examGot: Map<string, number>; examMax: number };
  const buckets = new Map<string, Bucket>();
  for (const a of assessments) {
    const b = buckets.get(a.subjectId) ?? { subjectId: a.subjectId, name: a.subject.name, code: a.subject.code, classGot: new Map(), classMax: 0, examGot: new Map(), examMax: 0 };
    const got = a.type === "END_OF_TERM" ? b.examGot : b.classGot;
    for (const m of a.marks) {
      if (m.score === null) continue;
      got.set(m.studentId, (got.get(m.studentId) ?? 0) + m.score);
    }
    if (a.type === "END_OF_TERM") b.examMax += a.maxScore; else b.classMax += a.maxScore;
    buckets.set(a.subjectId, b);
  }
  const subjectOrder = [...buckets.values()].sort((x, y) => x.name.localeCompare(y.name));

  // per-student subject rows through the wave-20 pure functions
  type Row = { subjectId: string; classScore: number | null; examScore: number | null; total: number | null; grade: string | null; remark: string | null; position: number | null };
  const rowsByStudent = new Map<string, Row[]>();
  for (const e of enrolled) {
    const rows: Row[] = [];
    for (const b of subjectOrder) {
      const classPct = b.classMax > 0 && b.classGot.has(e.studentId) ? (b.classGot.get(e.studentId) as number) / b.classMax : null;
      const examPct = b.examMax > 0 && b.examGot.has(e.studentId) ? (b.examGot.get(e.studentId) as number) / b.examMax : null;
      if (classPct === null && examPct === null) continue;
      let classScore: number | null = null;
      let examScore: number | null = null;
      let total: number | null = null;
      if (classPct !== null && examPct !== null) {
        classScore = Math.round(classPct * 30);
        examScore = Math.round(examPct * 70);
        total = weightedTotal(classScore, examScore);
      } else {
        total = Math.round((classPct ?? examPct ?? 0) * 100);
      }
      rows.push({ subjectId: b.subjectId, classScore, examScore, total, grade: gradeBand(total)?.grade ?? null, remark: resultRemark(total), position: null });
    }
    rowsByStudent.set(e.studentId, rows);
  }

  // competition ranks — per subject, then the overall average
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

  // attendance % (JHS 3 likely has none seeded — stays null gracefully)
  const sessions = await db.attendanceSession.findMany({
    where: { classroomId: cls.id, termId: term.id },
    select: { records: { select: { studentId: true, status: true } } },
  });
  const ATTENDED = new Set(["PRESENT", "LATE", "BUSY"]);
  const att = new Map<string, { attended: number; taken: number }>();
  for (const s of sessions) {
    for (const r of s.records) {
      const a = att.get(r.studentId) ?? { attended: 0, taken: 0 };
      a.taken += 1;
      if (ATTENDED.has(r.status)) a.attended += 1;
      att.set(r.studentId, a);
    }
  }

  let results = 0, cards = 0, aggregates = 0;
  for (const st of stats) {
    const { studentId, rows, average } = st;
    const position = overallRanks.get(studentId) ?? null;
    const record = att.get(studentId);
    const attendancePct = record && record.taken > 0 ? Math.round((record.attended / record.taken) * 100) : null;
    const remarks = autoRemarks(average);
    const promoted = average !== null ? average >= 40 : null;
    const aggregate = bestSixAggregate(rows.map((r) => ({ grade: r.grade, isCore: BECE_CORE_CODES.has(buckets.get(r.subjectId)?.code ?? "") })));
    if (aggregate !== null) aggregates++;

    for (const r of rows) {
      await db.termResult.upsert({
        where: { studentId_termId_subjectId: { studentId, termId: term.id, subjectId: r.subjectId } },
        create: {
          schoolId: school.id, studentId, termId: term.id, classroomId: cls.id, subjectId: r.subjectId,
          classScore: r.classScore, examScore: r.examScore, total: r.total,
          grade: r.grade, position: r.position, remarks: r.remark, published: false,
        },
        update: {
          classScore: r.classScore, examScore: r.examScore, total: r.total,
          grade: r.grade, position: r.position, remarks: r.remark, classroomId: cls.id,
        },
      });
      results++;
    }

    const card = await db.reportCard.upsert({
      where: { studentId_termId: { studentId, termId: term.id } },
      create: {
        studentId, termId: term.id, classroomName: cls.name,
        totalScore: average !== null ? Math.round(average * rows.length) : null,
        average, classPosition: position, classSize: enrolled.length,
        attendance: record && record.taken > 0 ? `Present ${record.attended} of ${record.taken} days` : null,
        attendancePct, aggregate, promoted,
        teacherRemark: remarks.teacherRemark, headRemark: remarks.headRemark,
        promotion: promoted === null ? null : promoted ? "PROMOTED" : "PROBATION",
        published: false,
      },
      update: {
        classroomName: cls.name,
        totalScore: average !== null ? Math.round(average * rows.length) : null,
        average, classPosition: position, classSize: enrolled.length,
        attendance: record && record.taken > 0 ? `Present ${record.attended} of ${record.taken} days` : null,
        attendancePct, aggregate, promoted,
        teacherRemark: remarks.teacherRemark, headRemark: remarks.headRemark,
        promotion: promoted === null ? null : promoted ? "PROMOTED" : "PROBATION",
      },
    });
    cards++;

    await db.reportCardResult.deleteMany({ where: { reportCardId: card.id } });
    for (const r of rows) {
      await db.reportCardResult.create({
        data: {
          reportCardId: card.id,
          subjectName: buckets.get(r.subjectId)?.name ?? "",
          subjectCode: buckets.get(r.subjectId)?.code ?? null,
          isCore: BECE_CORE_CODES.has(buckets.get(r.subjectId)?.code ?? ""),
          classScore: r.classScore, examScore: r.examScore, total: r.total,
          grade: r.grade, position: r.position, remark: r.remark,
        },
      });
    }
  }

  /* wave 21 — Results Control governance for the demo class-term:
     submit + approve so the "Publish results" flow in the Results &
     Report Cards module is live on first load (idempotent upsert). */
  const approval = await db.resultApproval.upsert({
    where: { classroomId_termId: { classroomId: cls.id, termId: term.id } },
    create: {
      schoolId: school.id, classroomId: cls.id, termId: term.id, status: "APPROVED",
      submittedBy: "Seed — Mr. Kwame Duah (class teacher)", submittedAt: new Date(),
      approvedBy: "Seed — Head teacher", approvedAt: new Date(),
      notes: "Demo term results submitted by the class teacher and approved by the head teacher.",
    },
    update: { status: "APPROVED" },
  });
  const audits = await db.resultApprovalAudit.count({ where: { approvalId: approval.id } });
  if (!audits) {
    await db.resultApprovalAudit.create({ data: { approvalId: approval.id, action: "SUBMIT", previousStatus: "DRAFT", newStatus: "SUBMITTED", performedBy: "Seed — Mr. Kwame Duah (class teacher)" } });
    await db.resultApprovalAudit.create({ data: { approvalId: approval.id, action: "APPROVE", previousStatus: "SUBMITTED", newStatus: "APPROVED", performedBy: "Seed — Head teacher" } });
  }

  /* wave 22 — results release notifications:
     1. guardian emails for the demo cohort so the email leg of the
        "Notify parents" blast is exercisable (phones already exist);
     2. an active RESULTS_PUBLISHED automation rule that overrides the
        default release message (idempotent — matched by name). */
  for (const e of enrolled) {
    if (!e.student.guardianEmail) {
      const slug = `${e.student.firstName}.${e.student.lastName}`.toLowerCase().replace(/[^a-z.]/g, "");
      await db.student.update({
        where: { id: e.studentId },
        data: { guardianEmail: `${slug}@example.gh` },
      });
    }
  }

  const RULE_NAME = "Results release — guardian summary";
  const rule = await db.smsAutomation.findFirst({ where: { schoolId: school.id, name: RULE_NAME } });
  if (!rule) {
    await db.smsAutomation.create({
      data: {
        schoolId: school.id, name: RULE_NAME, trigger: "RESULTS_PUBLISHED", recipients: "GUARDIANS",
        body: "{school}: {learner}'s {term} ({year}) results are live — average {average}%, position {position} of {size}, best-6 aggregate {aggregate}, attendance {attendance}%. {promotion}. Full report in your portal.",
        active: true,
      },
    });
  }

  console.log(`Results seed: ${enrolled.length} learners · ${subjectOrder.length} subjects · ${results} term results · ${cards} report cards (${aggregates} BECE aggregates) for ${cls.name} — ${term.name} (${year?.name}). Results control: APPROVED.`);
}

main().finally(() => db.$disconnect());

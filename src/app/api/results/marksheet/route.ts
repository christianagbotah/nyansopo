import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

/* ─────────────────────────────────────────────────────────────
   /api/results/marksheet — wave 35 legacy student_marksheet parity.

   GET ?schoolId
     → selection payload: classrooms + terms + the school's conduct /
       interest item library (mirrors the legacy marksheet picker).

   GET ?schoolId&studentId&termId
     → the full interactive marksheet payload for ONE learner:
       subject rows on the 30/70 doctrine (class score, exam score,
       total, grade, remark, subject position), totals, average,
       class position/size, the school's conduct / interest item
       libraries, teacher remark templates and head-teacher remark
       bands (auto-remark by percentage — Head_teacher_remarks
       find_by_percentage parity), attendance days from the term's
       attendance sessions, and the learner's saved aggregation
       (conductIds c1..c15 parity, interest, remarks, attendance).

   POST { action: "save", schoolId, studentId, termId, conductIds[],
         interestId, teacherRemark, headRemark, daysOpened, daysPresent }
     → conducts_update parity: refuses to save when the learner has
       no compiled card for the term ("no mark"), caps conduct picks
       at 15, stamps remark provenance (TEMPLATE vs CUSTOM), keeps
       the legacy attendance line in sync with the ReportCard and
       refreshes the printable attendance string.
   ───────────────────────────────────────────────────────────── */

const ATTENDED = new Set(["PRESENT", "LATE", "BUSY"]);

export async function GET(req: NextRequest) {
  const url = new URL(req.url);
  const q = (k: string) => url.searchParams.get(k) ?? "";
  const schoolId = q("schoolId");
  const studentId = q("studentId");
  if (!schoolId) return NextResponse.json({ error: "schoolId is required" }, { status: 400 });

  const auth = await opsAuth(req, schoolId, "GET", { perms: ["reports.view"] });
  if (!auth.ok) return auth.res;

  /* ── selection payload: class → student picker ── */
  if (!studentId) {
    const [school, years, classrooms, conductItems] = await Promise.all([
      db.school.findUnique({ where: { id: schoolId }, select: { id: true, name: true, code: true, motto: true, town: true, region: true, district: true, gpsAddress: true, phone: true, email: true, logoColor: true, curriculum: true } }),
      db.academicYear.findMany({ where: { schoolId }, orderBy: { startsAt: "desc" }, include: { terms: { orderBy: { sequence: "asc" } } } }),
      db.classroom.findMany({ where: { schoolId }, orderBy: [{ gradeLevel: "asc" }, { name: "asc" }], select: { id: true, name: true, gradeLevel: true } }),
      db.conductItem.findMany({ where: { schoolId, active: true }, orderBy: [{ kind: "asc" }, { sortOrder: "asc" }], select: { id: true, name: true, kind: true } }),
    ]);
    if (!school) return NextResponse.json({ error: "School not found" }, { status: 404 });
    const terms = years.flatMap((y) => y.terms.map((t) => ({ id: t.id, name: t.name, sequence: t.sequence, yearName: y.name, isCurrent: t.isCurrent })));
    return NextResponse.json({ school, terms, classrooms, conductItems });
  }

  /* ── one learner's full marksheet ── */
  const termId = q("termId");
  if (!termId) return NextResponse.json({ error: "termId is required with studentId" }, { status: 400 });

  const [school, enrollment, term] = await Promise.all([
    db.school.findUnique({ where: { id: schoolId }, select: { id: true, name: true, code: true, motto: true, town: true, region: true, district: true, gpsAddress: true, phone: true, email: true, logoColor: true, curriculum: true } }),
    db.enrollment.findFirst({
      where: { studentId, status: "ACTIVE", classroom: { schoolId } },
      include: {
        student: { select: { id: true, firstName: true, lastName: true, otherNames: true, nyansapoIndex: true, gender: true, guardianName: true, guardianPhone: true } },
        classroom: { select: { id: true, name: true, classTeacherId: true } },
      },
    }),
    db.term.findFirst({ where: { id: termId, academicYear: { schoolId } }, include: { academicYear: { select: { name: true } } } }),
  ]);
  if (!school) return NextResponse.json({ error: "School not found" }, { status: 404 });
  if (!enrollment) return NextResponse.json({ error: "Learner not found in this school" }, { status: 404 });
  if (!term) return NextResponse.json({ error: "Term not found in this school" }, { status: 404 });

  const [rows, card, conductItems, remarkTemplates, sessions] = await Promise.all([
    db.termResult.findMany({
      where: { termId, studentId },
      include: { subject: { select: { id: true, name: true, code: true } } },
      orderBy: { subject: { name: "asc" } },
    }),
    db.reportCard.findUnique({ where: { studentId_termId: { studentId, termId } } }),
    db.conductItem.findMany({ where: { schoolId, active: true }, orderBy: [{ kind: "asc" }, { sortOrder: "asc" }], select: { id: true, name: true, kind: true } }),
    db.remarkTemplate.findMany({ where: { schoolId, active: true }, orderBy: [{ category: "asc" }, { minPercent: "asc" }] }),
    db.attendanceSession.findMany({
      where: { termId, classroomId: enrollment.classroomId },
      select: { id: true, records: { where: { studentId }, select: { status: true } } },
    }),
  ]);

  const daysTaken = sessions.length;
  const daysAttended = sessions.reduce((n, s) => n + (s.records.length && ATTENDED.has(s.records[0].status) ? 1 : 0), 0);

  /* class-teacher identity for the signature line (Classroom.classTeacherId) */
  const classTeacher = await db.staff.findUnique({
    where: { id: enrollment.classroom?.classTeacherId ?? "" },
    select: { fullName: true },
  });

  /* suggested head remark — legacy Head_teacher_remarks::find_by_percentage */
  const average = card?.average ?? null;
  const headBands = remarkTemplates.filter((t) => t.category === "HEAD_RANGE");
  const suggestedHead = average !== null
    ? headBands.find((b) => average >= (b.minPercent ?? 0) && average <= (b.maxPercent ?? 100))?.text ?? null
    : null;

  const classScoreTotal = rows.reduce((s, r) => s + (r.classScore ?? 0), 0);
  const examScoreTotal = rows.reduce((s, r) => s + (r.examScore ?? 0), 0);
  const totalTotal = rows.reduce((s, r) => s + (r.total ?? 0), 0);

  return NextResponse.json({
    school,
    term: { id: term.id, name: term.name, sequence: term.sequence, yearName: term.academicYear.name },
    student: enrollment.student,
    classroom: enrollment.classroom,
    classTeacher: classTeacher?.fullName ?? null,
    rows: rows.map((r) => ({
      subjectId: r.subject.id, subjectName: r.subject.name, subjectCode: r.subject.code,
      classScore: r.classScore, examScore: r.examScore, total: r.total,
      grade: r.grade, remark: r.remarks, position: r.position,
    })),
    totals: { classScore: round2(classScoreTotal), examScore: round2(examScoreTotal), total: round2(totalTotal), subjects: rows.length },
    card: card ? {
      id: card.id,
      totalScore: card.totalScore, average: card.average, classPosition: card.classPosition, classSize: card.classSize,
      aggregate: card.aggregate, promoted: card.promoted, published: card.published,
      conductIds: Array.isArray(card.conductIds) ? card.conductIds.filter((v): v is string => typeof v === "string") : null,
      interestId: card.interestId,
      teacherRemark: card.teacherRemark, headRemark: card.headRemark,
      remarkSource: card.remarkSource, headSource: card.headSource,
      daysOpened: card.daysOpened, daysPresent: card.daysPresent,
      attendance: card.attendance, attendancePct: card.attendancePct,
    } : null,
    attendance: { daysTaken, daysAttended },
    conductItems,
    teacherTemplates: remarkTemplates.filter((t) => t.category.startsWith("TEACHER_")).map((t) => ({ id: t.id, category: t.category, text: t.text })),
    headBands: headBands.map((t) => ({ id: t.id, minPercent: t.minPercent, maxPercent: t.maxPercent, text: t.text })),
    suggestedHead,
  });
}

function round2(n: number) { return Math.round(n * 100) / 100; }

export async function POST(req: NextRequest) {
  const body = await req.json().catch(() => ({}));
  const S = (v: unknown) => (typeof v === "string" ? v.trim() : "");
  const action = S(body.action) || "save";
  if (action !== "save") return NextResponse.json({ error: `Unknown action: ${action}` }, { status: 400 });

  const schoolId = S(body.schoolId);
  const studentId = S(body.studentId);
  const termId = S(body.termId);
  if (!schoolId || !studentId || !termId) {
    return NextResponse.json({ error: "schoolId, studentId and termId are required" }, { status: 400 });
  }

  const auth = await opsAuth(req, schoolId, "POST", { perms: ["reports.publish"] });
  if (!auth.ok) return auth.res;

  const card = await db.reportCard.findUnique({
    where: { studentId_termId: { studentId, termId } },
    include: { student: { select: { firstName: true, lastName: true } }, term: { select: { id: true, name: true } } },
  });
  /* legacy conducts_update: refuses when the learner has no marks row —
     "Please enter student's marks before proceeding with the conducts." */
  if (!card) {
    return NextResponse.json(
      { error: "Please enter the learner's marks before proceeding with conducts — compile this class's term results first (no mark)." },
      { status: 400 },
    );
  }

  /* conduct picks: legacy c1..c15 slots, numeric ids only, deduped */
  const rawConducts = Array.isArray(body.conductIds) ? body.conductIds : [];
  const conductIds = [...new Set(rawConducts.map((v: unknown) => (typeof v === "string" ? v : String(v ?? ""))).filter(Boolean))].slice(0, 15);
  if (conductIds.length) {
    const valid = await db.conductItem.findMany({ where: { schoolId, id: { in: conductIds }, kind: "CONDUCT" }, select: { id: true } });
    if (valid.length !== conductIds.length) {
      return NextResponse.json({ error: "One or more conduct picks are not active conduct items for this school" }, { status: 400 });
    }
  }

  /* interest: single active INTEREST item */
  const interestId = S(body.interestId) || null;
  if (interestId) {
    const okInterest = await db.conductItem.findFirst({ where: { schoolId, id: interestId, kind: "INTEREST" }, select: { id: true, name: true } });
    if (!okInterest) return NextResponse.json({ error: "The selected interest is not an active interest item for this school" }, { status: 400 });
  }

  const teacherRemark = S(body.teacherRemark).slice(0, 600) || null;
  const headRemark = S(body.headRemark).slice(0, 600) || null;
  const remarkTemplateId = S(body.remarkTemplateId) || null;
  const headTemplateId = S(body.headTemplateId) || null;

  /* remark provenance — TEMPLATE when the text matches a live template */
  let remarkSource: string | null = null;
  if (teacherRemark && remarkTemplateId) {
    const t = await db.remarkTemplate.findFirst({ where: { schoolId, id: remarkTemplateId, category: { startsWith: "TEACHER_" } } });
    remarkSource = t && t.text === teacherRemark ? "TEMPLATE" : "CUSTOM";
  } else if (teacherRemark) remarkSource = "CUSTOM";
  let headSource: string | null = null;
  if (headRemark && headTemplateId) {
    const t = await db.remarkTemplate.findFirst({ where: { schoolId, id: headTemplateId, category: "HEAD_RANGE" } });
    headSource = t && t.text === headRemark ? "TEMPLATE" : "CUSTOM";
  } else if (headRemark) headSource = "CUSTOM";

  /* attendance line — legacy days_opened / days_present */
  const N = (v: unknown) => (typeof v === "number" && Number.isFinite(v) ? Math.max(0, Math.floor(v)) : null);
  const daysOpened = N(body.daysOpened);
  const daysPresent = N(body.daysPresent);
  if (daysPresent !== null && daysOpened !== null && daysPresent > daysOpened) {
    return NextResponse.json({ error: "Days present cannot exceed days opened" }, { status: 400 });
  }

  /* keep the printable attendance string + pct in sync with the sheet */
  const attendanceLine = daysOpened !== null
    ? `Present ${daysPresent ?? 0} of ${daysOpened} day${daysOpened === 1 ? "" : "s"}`
    : card.attendance;
  const attendancePct = daysOpened !== null && daysOpened > 0 && daysPresent !== null
    ? Math.round((daysPresent / daysOpened) * 1000) / 10
    : card.attendancePct;

  const updated = await db.reportCard.update({
    where: { studentId_termId: { studentId, termId } },
    data: {
      conductIds: conductIds.length ? conductIds : undefined,
      interestId: interestId ?? undefined,
      teacherRemark, headRemark,
      remarkSource, headSource,
      daysOpened, daysPresent,
      attendance: attendanceLine, attendancePct,
      aiGenerated: false,
    },
    select: { id: true },
  });

  return NextResponse.json({
    ok: true, cardId: updated.id,
    saved: {
      conductCount: conductIds.length, interestId,
      teacherRemark, headRemark, remarkSource, headSource,
      daysOpened, daysPresent, attendance: attendanceLine,
    },
    studentName: `${card.student.firstName} ${card.student.lastName}`.trim(),
    termName: card.term.name,
  });
}

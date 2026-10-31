import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser, canOperateSchool } from "@/lib/session";

// GET /api/student/[id] — 360° profile across the network
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });

  const student = await db.student.findUnique({
    where: { id },
    include: {
      enrollments: { include: { school: { select: { id: true, name: true, town: true, region: true, phone: true, email: true, curriculum: true } }, classroom: true }, orderBy: { admissionDate: "asc" } },
      invoices: { include: { term: { include: { academicYear: true } }, payments: true }, orderBy: { createdAt: "desc" } },
      flags: { include: { school: { select: { id: true, name: true, phone: true, email: true } }, verifications: true }, orderBy: { raisedAt: "desc" } },
      transfers: { include: { fromSchool: { select: { id: true, name: true } }, toSchool: { select: { id: true, name: true } } } },
      recordRequests: { include: { requestingSchool: { select: { id: true, name: true } }, targetSchool: { select: { id: true, name: true } } } },
      reportCards: { include: { term: { include: { academicYear: true } }, results: true }, orderBy: { createdAt: "desc" } },
      riskScores: { orderBy: { computedAt: "desc" }, take: 3 },
      marks: { include: { assessment: { include: { subject: true } } } },
      attendanceRecords: { include: { session: true } },
    },
  });
  if (!student) return NextResponse.json({ error: "Student not found" }, { status: 404 });

  // attendance summary
  const att = student.attendanceRecords;
  const attSummary = {
    total: att.length,
    present: att.filter((r) => r.status === "PRESENT").length,
    late: att.filter((r) => r.status === "LATE").length,
    absent: att.filter((r) => r.status === "ABSENT").length,
    sick: att.filter((r) => r.status === "SICK").length,
  };
  // academic summary by subject (current term marks)
  const bySubject: Record<string, { got: number; max: number }> = {};
  student.marks.forEach((m) => {
    if (m.score === null) return;
    const k = m.assessment.subject.name;
    bySubject[k] ??= { got: 0, max: 0 };
    bySubject[k].got += m.score;
    bySubject[k].max += m.assessment.maxScore;
  });

  return NextResponse.json({
    student: {
      id: student.id, nyansapoIndex: student.nyansapoIndex, firstName: student.firstName, lastName: student.lastName,
      otherNames: student.otherNames, gender: student.gender, dateOfBirth: student.dateOfBirth,
      guardianName: student.guardianName, guardianPhone: student.guardianPhone, guardianAltPhone: student.guardianAltPhone,
      guardianEmail: student.guardianEmail, guardianOccupation: student.guardianOccupation, guardianAddress: student.guardianAddress,
      relationship: student.relationship, hometown: student.hometown, previousSchool: student.previousSchool, status: student.status,
    },
    enrollments: student.enrollments.map((e) => ({ id: e.id, school: e.school, classroom: e.classroom.name, admissionDate: e.admissionDate, exitDate: e.exitDate, status: e.status, exitReason: e.exitReason })),
    invoices: student.invoices.map((i) => ({ id: i.id, invoiceNo: i.invoiceNo, school: undefined, term: `${i.term.name} ${i.term.academicYear.name}`, total: i.totalCents, discount: i.discountCents, paid: i.paidCents, balance: i.totalCents - i.discountCents - i.paidCents, status: i.status, payments: i.payments.map((p) => ({ receiptNo: p.receiptNo, amount: p.amountCents, method: p.method, paidAt: p.paidAt })) })),
    flags: student.flags.map((f) => ({ id: f.id, type: f.type, severity: f.severity, status: f.status, amountOwed: f.amountOwedCents, description: f.description, raisedAt: f.raisedAt, raisedByName: f.raisedByName, school: f.school, resolvedAt: f.resolvedAt, resolutionNote: f.resolutionNote, verifications: f.verifications })),
    transfers: student.transfers.map((t) => ({ id: t.id, from: t.fromSchool.name, to: t.toSchool.name, date: t.date, reason: t.reason, outstanding: t.outstandingCents, status: t.status, documentsReleased: t.documentsReleased, notes: t.notes, fromId: t.fromSchoolId, toId: t.toSchoolId })),
    recordRequests: student.recordRequests.map((r) => ({ id: r.id, from: r.requestingSchool.name, to: r.targetSchool.name, status: r.status, requestedAt: r.requestedAt, purpose: r.purpose })),
    reportCards: student.reportCards.map((rc) => ({ id: rc.id, term: `${rc.term.name} ${rc.term.academicYear.name}`, classroom: rc.classroomName, average: rc.average, position: rc.classPosition, classSize: rc.classSize, published: rc.published, conduct: rc.conduct, teacherRemark: rc.teacherRemark, headRemark: rc.headRemark, results: rc.results.map((r) => ({ subject: r.subjectName, total: r.total, grade: r.grade, remark: r.remark })) })),
    attendance: attSummary,
    academics: Object.entries(bySubject).map(([subject, v]) => ({ subject, pct: Math.round((v.got / v.max) * 100) })),
    risk: student.riskScores[0] ? { score: student.riskScores[0].score, level: student.riskScores[0].level, factors: JSON.parse(student.riskScores[0].factorsJson), recommendation: student.riskScores[0].recommendation } : null,
  });
}

// POST /api/student/[id] — actions: withdraw, release-transfer, decline-transfer
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
  const body = await req.json();
  const student = await db.student.findUnique({ where: { id }, include: { enrollments: true } });
  if (!student) return NextResponse.json({ error: "Student not found" }, { status: 404 });

  if (body.action === "withdraw") {
    const active = student.enrollments.find((e) => e.status === "ACTIVE");
    if (!active) return NextResponse.json({ error: "Student has no active enrollment" }, { status: 400 });
    if (!canOperateSchool(user, active.schoolId)) return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    const { reason, flag, amountOwedCents, severity } = body;
    await db.enrollment.update({ where: { id: active.id }, data: { status: reason === "transfer" ? "TRANSFERRED" : "WITHDRAWN", exitDate: new Date(), exitReason: body.exitNote ?? reason } });
    await db.student.update({ where: { id }, data: { status: "TRACKED" } });
    if (flag) {
      await db.studentFlag.create({ data: { studentId: id, schoolId: active.schoolId, raisedByName: user.fullName, type: "WITHDREW_OWING", severity: severity ?? "HIGH", status: "ACTIVE", amountOwedCents: Number(amountOwedCents) || null, description: body.flagDescription ?? `Withdrawn from school with outstanding balance of GH₵${((amountOwedCents ?? 0) / 100).toFixed(2)}.` } });
    }
    const n = await db.enrollment.count({ where: { schoolId: active.schoolId, status: "ACTIVE" } });
    await db.school.update({ where: { id: active.schoolId }, data: { studentsOnRoll: n } });
    return NextResponse.json({ ok: true, flagged: !!flag });
  }

  if (body.action === "release-transfer") {
    const tr = await db.transferRecord.findUnique({ where: { id: body.transferId }, include: { student: true } });
    if (!tr) return NextResponse.json({ error: "Transfer not found" }, { status: 404 });
    if (!canOperateSchool(user, tr.fromSchoolId)) return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    const approve = body.approve !== false;
    await db.transferRecord.update({ where: { id: tr.id }, data: { status: approve ? "COMPLETED" : "DECLINED", documentsReleased: approve, notes: body.note ?? tr.notes } });
    return NextResponse.json({ ok: true, approved: approve });
  }
  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

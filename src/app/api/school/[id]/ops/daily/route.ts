import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { generateDayCharges, settleDailyCharges, effectivePrice } from "@/lib/daily-billing";
import { sendSms } from "@/lib/sms";

// ── GET /api/school/[id]/ops/daily — services, ledger, POS workspace ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET");
  if (!auth.ok) return auth.res;

  const url = new URL(req.url);
  const q = (k: string) => url.searchParams.get(k) ?? undefined;
  const dateStr = q("date") ?? new Date().toISOString().slice(0, 10);
  const date = new Date(dateStr);
  const monthStart = new Date(date.getFullYear(), date.getMonth(), 1);
  const monthEnd = new Date(date.getFullYear(), date.getMonth() + 1, 0, 23, 59, 59);

  const services = await db.dailyService.findMany({
    where: { schoolId },
    include: { _count: { select: { enrollments: { where: { active: true } } } } },
    orderBy: { name: "asc" },
  });

  // per-service stats (this month)
  const charges = await db.dailyCharge.findMany({ where: { schoolId, date: { gte: monthStart, lte: monthEnd } } });
  const byService: Record<string, { billed: number; collected: number; owed: number; waived: number; count: number }> = {};
  charges.forEach((c) => {
    const e = (byService[c.serviceId] ??= { billed: 0, collected: 0, owed: 0, waived: 0, count: 0 });
    e.billed += c.amountCents;
    e.count++;
    if (c.status === "PAID") e.collected += c.amountCents;
    else if (c.status === "OWED") e.owed += c.amountCents;
    else e.waived += c.amountCents;
  });

  // 14-day trend
  const trendStart = new Date(date.getTime() - 20 * 864e5);
  const recent = await db.dailyCharge.findMany({ where: { schoolId, date: { gte: trendStart, lte: date } } });
  const dayMap: Record<string, { billed: number; collected: number }> = {};
  recent.forEach((c) => {
    const k = c.date.toISOString().slice(0, 10);
    const e = (dayMap[k] ??= { billed: 0, collected: 0 });
    e.billed += c.amountCents;
    if (c.status === "PAID") e.collected += c.amountCents;
  });
  const trend = Object.entries(dayMap).map(([d, v]) => ({ date: d, ...v })).sort((a, b) => a.date.localeCompare(b.date)).slice(-14);

  // ledger: per-student outstanding (all time)
  const ledgerCharges = await db.dailyCharge.findMany({ where: { schoolId, status: "OWED" }, include: { student: true, service: true } });
  const ledger: Record<string, { studentId: string; student: string; classroom: string | null; guardianPhone: string; owed: number; items: Record<string, number>; oldest: string }> = {};
  for (const c of ledgerCharges) {
    const e = (ledger[c.studentId] ??= { studentId: c.studentId, student: `${c.student.firstName} ${c.student.lastName}`, classroom: null, guardianPhone: c.student.guardianPhone, owed: 0, items: {}, oldest: c.date.toISOString() });
    e.owed += c.amountCents;
    e.items[c.service.name] = (e.items[c.service.name] ?? 0) + c.amountCents;
    if (c.date.toISOString() < e.oldest) e.oldest = c.date.toISOString();
  }
  const enrollments = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" } });
  const classByStudent: Record<string, string> = {};
  enrollments.forEach((e) => (classByStudent[e.studentId] = e.classroomId));
  const classrooms = await db.classroom.findMany({ where: { schoolId } });
  const clsName = Object.fromEntries(classrooms.map((c) => [c.id, c.name]));
  Object.values(ledger).forEach((l) => (l.classroom = clsName[classByStudent[l.studentId]] ?? null));

  // POS workspace: today's charges + recent POS payments
  const todayCharges = await db.dailyCharge.findMany({ where: { schoolId, date }, include: { student: true, service: true } });
  const posPayments = await db.payment.findMany({ where: { schoolId, category: "DAILY" }, orderBy: { paidAt: "desc" }, take: 10, include: { student: true } });

  // students list for POS picker (with enrolled services + today status)
  const studentRows = await db.enrollment.findMany({
    where: { schoolId, status: "ACTIVE" },
    include: { student: { include: { serviceEnrollments: { where: { active: true }, include: { service: true } } } }, classroom: true },
  });
  const todayByStudent: Record<string, { charged: number; owed: number }> = {};
  todayCharges.forEach((c) => {
    const e = (todayByStudent[c.studentId] ??= { charged: 0, owed: 0 });
    e.charged += c.amountCents;
    if (c.status === "OWED") e.owed += c.amountCents;
  });
  const posStudents = studentRows.map((r) => ({
    id: r.student.id,
    name: `${r.student.firstName} ${r.student.lastName}`,
    classroom: r.classroom.name,
    guardianPhone: r.student.guardianPhone,
    services: r.student.serviceEnrollments.map((se) => ({ id: se.serviceId, name: se.service.name, price: effectivePrice(se.service, se) })),
    balance: (ledger[r.student.id]?.owed ?? 0),
    today: todayByStudent[r.student.id] ?? null,
  }));

  return NextResponse.json({
    date: dateStr,
    services: services.map((s) => ({
      id: s.id, name: s.name, category: s.category, price: s.priceCents, chargeMode: s.chargeMode,
      weekdays: s.weekdays, active: s.active, enrolled: s._count.enrollments,
      month: byService[s.id] ?? { billed: 0, collected: 0, owed: 0, waived: 0, count: 0 },
    })),
    month: {
      billed: charges.reduce((a, c) => a + c.amountCents, 0),
      collected: charges.filter((c) => c.status === "PAID").reduce((a, c) => a + c.amountCents, 0),
      owed: charges.filter((c) => c.status === "OWED").reduce((a, c) => a + c.amountCents, 0),
      waived: charges.filter((c) => c.status === "WAIVED").reduce((a, c) => a + c.amountCents, 0),
    },
    today: {
      charged: todayCharges.reduce((a, c) => a + c.amountCents, 0),
      collected: todayCharges.filter((c) => c.status === "PAID").reduce((a, c) => a + c.amountCents, 0),
      owed: todayCharges.filter((c) => c.status === "OWED").reduce((a, c) => a + c.amountCents, 0),
      count: todayCharges.length,
      generated: todayCharges.length > 0,
    },
    trend,
    ledger: Object.values(ledger).sort((a, b) => b.owed - a.owed),
    posStudents,
    posPayments: posPayments.map((p) => ({ id: p.id, receiptNo: p.receiptNo, amount: p.amountCents, method: p.method, paidAt: p.paidAt, student: p.student ? `${p.student.firstName} ${p.student.lastName}` : "—" })),
  });
}

// ── POST /api/school/[id]/ops/daily — services, enrolment, POS, generation ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST");
  if (!auth.ok) return auth.res;
  const user = auth.user;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;

  if (action === "save-service") {
    const { id, name, category, priceCents, chargeMode, weekdays } = body;
    if (!name || !Number(priceCents)) return NextResponse.json({ error: "Name and price are required" }, { status: 400 });
    const data = { name, category: category ?? "OTHER", priceCents: Number(priceCents), chargeMode: chargeMode ?? "DAILY", weekdays: weekdays ?? "1,2,3,4,5" };
    const svc = id ? await db.dailyService.update({ where: { id }, data }) : await db.dailyService.create({ data: { ...data, schoolId } });
    return NextResponse.json({ service: svc });
  }

  if (action === "toggle-service") {
    const svc = await db.dailyService.findUnique({ where: { id: body.id } });
    if (!svc || svc.schoolId !== schoolId) return NextResponse.json({ error: "Service not found" }, { status: 404 });
    const updated = await db.dailyService.update({ where: { id: body.id }, data: { active: !svc.active } });
    return NextResponse.json({ service: updated });
  }

  if (action === "enroll") {
    const { serviceId, studentIds, priceCentsOverride } = body;
    if (!serviceId || !Array.isArray(studentIds) || !studentIds.length) return NextResponse.json({ error: "serviceId and studentIds[] required" }, { status: 400 });
    let n = 0;
    for (const studentId of studentIds) {
      await db.dailyServiceEnrollment.upsert({
        where: { serviceId_studentId: { serviceId, studentId } },
        update: { active: true, priceCentsOverride: priceCentsOverride ? Number(priceCentsOverride) : null },
        create: { serviceId, studentId, priceCentsOverride: priceCentsOverride ? Number(priceCentsOverride) : null },
      });
      n++;
    }
    return NextResponse.json({ enrolled: n });
  }

  if (action === "unenroll") {
    await db.dailyServiceEnrollment.update({ where: { id: body.enrollmentId }, data: { active: false } }).catch(() => null);
    return NextResponse.json({ ok: true });
  }

  if (action === "generate-day") {
    const date = new Date(body.date ?? new Date().toISOString().slice(0, 10));
    const res = await generateDayCharges(db, schoolId, date);
    return NextResponse.json(res);
  }

  if (action === "pos-collect") {
    const { studentId, amountCents, method, reference } = body;
    const student = await db.student.findUnique({ where: { id: studentId } });
    if (!student) return NextResponse.json({ error: "Learner not found" }, { status: 404 });
    const owed = await db.dailyCharge.aggregate({ where: { studentId, status: "OWED" }, _sum: { amountCents: true } });
    const owedCents = owed._sum.amountCents ?? 0;
    if (owedCents <= 0) return NextResponse.json({ error: "No outstanding daily balance" }, { status: 400 });
    const amt = Math.max(0, Math.min(Number(amountCents), owedCents));
    if (amt <= 0) return NextResponse.json({ error: "Amount exceeds balance or is invalid" }, { status: 400 });
    const seq = (await db.payment.count({ where: { schoolId } })) + 1001;
    const payment = await db.payment.create({
      data: { receiptNo: `RCP-2026-${String(seq).padStart(5, "0")}`, studentId, category: "DAILY", schoolId, amountCents: amt, method: method ?? "CASH", reference: reference ?? null, receivedByName: user.fullName, paidAt: new Date() },
    });
    const applied = await settleDailyCharges(db, payment.id, studentId, amt);
    // auto receipt SMS
    const setting = await db.smsSetting.findUnique({ where: { schoolId } });
    if (setting?.autoPaymentReceipts) {
      await sendSms({ db, schoolId, studentId, recipientName: student.guardianName, recipientPhone: student.guardianPhone, body: `We have received GH₵${(amt / 100).toFixed(2)} for ${student.firstName} (${payment.receiptNo}) towards daily services. Thank you.`, category: "PAYMENT", sentByName: user.fullName });
    }
    return NextResponse.json({ payment: { ...payment, applied }, student: `${student.firstName} ${student.lastName}` });
  }

  if (action === "waive-charge") {
    const c = await db.dailyCharge.findUnique({ where: { id: body.chargeId } });
    if (!c || c.schoolId !== schoolId) return NextResponse.json({ error: "Charge not found" }, { status: 404 });
    const updated = await db.dailyCharge.update({ where: { id: body.chargeId }, data: { status: "WAIVED" } });
    return NextResponse.json({ charge: updated });
  }

  if (action === "waive-student-day") {
    const { studentId, date } = body;
    const res = await db.dailyCharge.updateMany({ where: { schoolId, studentId, date: new Date(date), status: "OWED" }, data: { status: "WAIVED" } });
    return NextResponse.json({ waived: res.count });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

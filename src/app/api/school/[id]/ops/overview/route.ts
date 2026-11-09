import { NextRequest, NextResponse } from "next/server";
import { sortClassrooms } from "@/lib/classes";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { currentPeriod } from "@/lib/payroll";

// method legs of a payment — SPLIT payments carry their parts in splitJson
type MethodLeg = { method: string; amountCents: number; reference?: string | null };
function methodLegs(p: { method: string; amountCents: number; splitJson: unknown; reference?: string | null }): MethodLeg[] {
  const parts = Array.isArray((p as any).splitJson) ? ((p as any).splitJson as { method?: string; amountCents?: number; reference?: string | null }[]) : null;
  if (parts && parts.length) {
    return parts.filter((x) => x.method && x.amountCents).map((x) => ({ method: String(x.method), amountCents: Number(x.amountCents), reference: x.reference ?? null }));
  }
  return [{ method: p.method, amountCents: p.amountCents, reference: p.reference ?? null }];
}

// ── GET /api/school/[id]/ops/overview — dashboard widgets for ops modules ──
// daily strip + payroll + inventory + sms + transport, plus the dashboard KPI
// extras: per-service "received today" cards, unpaid daily-fee balances,
// learner distribution (classroom gender bands) and, for staff-linked callers,
// their class-teacher "my classes" grid data.
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET");
  if (!auth.ok) return auth.res;
  const user = auth.user;

  const url = new URL(req.url);
  const action = url.searchParams.get("action");

  // ── CI3 modal_daily_revenue parity: filtered payment list for the dashboard
  //    breakdown modal — source (invoices / daily fees / all), payment method,
  //    classroom, daily service and date. ──
  if (action === "payments") {
    const dateStr = url.searchParams.get("date") ?? new Date().toISOString().slice(0, 10);
    const source = url.searchParams.get("source") ?? "all"; // all | invoices | daily
    const method = url.searchParams.get("method") ?? "all";
    const classroomId = url.searchParams.get("classroomId") ?? "all";
    const serviceId = url.searchParams.get("serviceId") ?? "all";
    const dStart = new Date(`${dateStr}T00:00:00.000Z`);
    const dEnd = new Date(`${dateStr}T23:59:59.999Z`);

    let studentIds: string[] | null = null;
    if (classroomId !== "all") {
      const enrolled = await db.enrollment.findMany({ where: { schoolId, classroomId, status: "ACTIVE" }, select: { studentId: true } });
      studentIds = enrolled.map((e) => e.studentId);
    }

    const payments = await db.payment.findMany({
      where: {
        schoolId, voided: false, paymentType: { not: "REFUND" }, paidAt: { gte: dStart, lte: dEnd },
        ...(source === "invoices" ? { category: "INVOICE" } : source === "daily" ? { category: "DAILY" } : {}),
        ...(studentIds ? { studentId: { in: studentIds } } : {}),
      },
      include: { student: { select: { firstName: true, lastName: true } } },
      orderBy: { paidAt: "desc" },
    });

    // daily charges per payment (service split) for the daily-fee lines
    const payIds = payments.map((p) => p.id);
    const charges = payIds.length ? await db.dailyCharge.findMany({ where: { paymentId: { in: payIds }, ...(serviceId !== "all" ? { serviceId } : {}) }, include: { service: { select: { name: true } } } }) : [];
    const chargesByPay: Record<string, { service: string; cents: number }[]> = {};
    for (const c of charges) {
      ((chargesByPay[c.paymentId ?? ""] ??= [])).push({ service: c.service.name, cents: c.amountCents });
    }

    // classroom names for the rows (current enrolment)
    const rowStudentIds = [...new Set(payments.map((p) => p.studentId).filter(Boolean))] as string[];
    const enrollments = rowStudentIds.length ? await db.enrollment.findMany({ where: { schoolId, studentId: { in: rowStudentIds }, status: "ACTIVE" }, include: { classroom: { select: { name: true } } } }) : [];
    const classByStudent: Record<string, string> = {};
    for (const e of enrollments) classByStudent[e.studentId] = e.classroom.name;

    const services = await db.dailyService.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
    const classrooms = sortClassrooms(await db.classroom.findMany({ where: { schoolId }, select: { id: true, name: true, gradeLevel: true } }));

    let rows = payments.map((p) => ({
      id: p.id,
      receiptNo: p.receiptNo,
      student: p.student ? `${p.student.firstName} ${p.student.lastName}` : "—",
      classroom: p.studentId ? classByStudent[p.studentId] ?? "" : "",
      category: p.category,
      amountCents: p.amountCents,
      legs: methodLegs(p),
      paidAt: p.paidAt.toISOString(),
      receivedByName: p.receivedByName,
      services: chargesByPay[p.id] ?? [],
    }));
    if (method !== "all") rows = rows.filter((r) => r.legs.some((l) => l.method === method));

    // summary by method (split-aware) and by source
    const byMethod: Record<string, number> = {};
    for (const r of rows) for (const l of r.legs) byMethod[l.method] = (byMethod[l.method] ?? 0) + l.amountCents;
    const bySource = {
      invoices: rows.filter((r) => r.category === "INVOICE").reduce((s, r) => s + r.amountCents, 0),
      daily: rows.filter((r) => r.category === "DAILY").reduce((s, r) => s + r.amountCents, 0),
    };
    return NextResponse.json({
      date: dateStr,
      total: rows.reduce((s, r) => s + r.amountCents, 0),
      count: rows.length,
      byMethod: Object.entries(byMethod).map(([m, cents]) => ({ method: m, cents })),
      bySource,
      rows,
      services: services.map((s) => ({ id: s.id, name: s.name })),
      classrooms: classrooms.map((c) => ({ id: c.id, name: c.name })),
    });
  }

  const today = new Date();
  const todayStr = today.toISOString().slice(0, 10);
  const date = new Date(todayStr);
  const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
  const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0, 23, 59, 59);

  // daily services
  const todayCharges = await db.dailyCharge.findMany({ where: { schoolId, date } });
  const monthCharges = await db.dailyCharge.findMany({ where: { schoolId, date: { gte: monthStart, lte: monthEnd } } });
  const daily = {
    todayCharged: todayCharges.reduce((s, c) => s + c.amountCents, 0),
    todayCollected: todayCharges.filter((c) => c.status === "PAID").reduce((s, c) => s + c.amountCents, 0),
    todayCount: todayCharges.length,
    monthCollected: monthCharges.filter((c) => c.status === "PAID").reduce((s, c) => s + c.amountCents, 0),
    outstanding: await db.dailyCharge.aggregate({ where: { schoolId, status: "OWED" }, _sum: { amountCents: true } }).then((r) => r._sum.amountCents ?? 0),
  };

  // payroll (latest run)
  const latestRun = await db.payrollRun.findFirst({ where: { schoolId }, orderBy: { period: "desc" } });
  const payroll = {
    latest: latestRun ? { period: latestRun.period, status: latestRun.status, net: latestRun.totalNetCents, employerSsnit: latestRun.totalEmployerSsnitCents } : null,
    currentPeriod: currentPeriod(),
    needsRun: !latestRun || latestRun.period !== currentPeriod(),
  };

  // inventory
  const items = await db.inventoryItem.findMany({ where: { schoolId } });
  const inventory = {
    stockValue: items.reduce((s, i) => s + i.stockQty * i.unitCostCents, 0),
    lowStock: items.filter((i) => i.stockQty < i.reorderLevel).length,
    lowStockItems: items.filter((i) => i.stockQty < i.reorderLevel).slice(0, 3).map((i) => ({ name: i.name, qty: i.stockQty, reorder: i.reorderLevel, unit: i.unit })),
  };

  // sms
  const setting = await db.smsSetting.findUnique({ where: { schoolId } });
  const smsThisMonth = await db.smsMessage.count({ where: { schoolId, sentAt: { gte: monthStart } } });
  const sms = {
    credits: setting?.credits ?? 0,
    sentThisMonth: smsThisMonth,
    autoReceipts: setting?.autoPaymentReceipts ?? false,
  };

  // transport
  const routes = await db.transportRoute.findMany({ where: { schoolId, active: true }, include: { assignments: { where: { active: true } } } });
  const transport = {
    routes: routes.length,
    learners: routes.reduce((s, r) => s + r.assignments.length, 0),
    capacity: routes.reduce((s, r) => s + r.capacity, 0),
    fareRevenue: routes.reduce((s, r) => s + r.assignments.reduce((x, a) => x + (a.fareOverrideCents ?? r.farePerTermCents), 0), 0),
  };

  // ── daily-fees strip (dashboard KPI): "received today" per service.
  //    Mirrors the ops/daily today query shape: whole calendar day, per-service
  //    PAID totals + charge counts, with WEEKLY services aggregated across the
  //    whole ISO week (iedupro parity). ──
  const dayStart = new Date(today); dayStart.setHours(0, 0, 0, 0);
  const dayEnd = new Date(today); dayEnd.setHours(23, 59, 59, 999);
  const services = await db.dailyService.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
  const dayCharges = await db.dailyCharge.findMany({ where: { schoolId, date: { gte: dayStart, lte: dayEnd } } });
  const svcToday: Record<string, { collected: number; count: number }> = {};
  for (const c of dayCharges) {
    const e = (svcToday[c.serviceId] ??= { collected: 0, count: 0 });
    e.count++;
    if (c.status === "PAID") e.collected += c.amountCents;
  }
  const weeklyServiceIds = services.filter((s) => s.chargeMode === "WEEKLY").map((s) => s.id);
  if (weeklyServiceIds.length) {
    const wk = new Date(dayStart);
    const dow = (wk.getDay() + 6) % 7; // Monday = 0
    wk.setDate(wk.getDate() - dow); wk.setHours(0, 0, 0, 0);
    const wkEnd = new Date(wk); wkEnd.setDate(wkEnd.getDate() + 7);
    const weekCharges = await db.dailyCharge.findMany({ where: { schoolId, serviceId: { in: weeklyServiceIds }, date: { gte: wk, lt: wkEnd } } });
    for (const c of weekCharges) {
      const e = (svcToday[c.serviceId] ??= { collected: 0, count: 0 });
      e.count++;
      if (c.status === "PAID") e.collected += c.amountCents;
    }
  }
  const dailyToday = services.map((s) => ({ service: s.id, label: s.name, cents: svcToday[s.id]?.collected ?? 0, txns: svcToday[s.id]?.count ?? 0 }));
  // unpaid daily-fee balances — the daily module's arrears definition: all-time
  // OWED day-charges (wallets are prepaid credit; arrears derive from the ledger)
  const dailyArrearsCents = daily.outstanding;

  // ── CI3 dashboard_enterprise “total income received today” KPI + breakdown:
  //    all non-voided payments today (invoice fees + daily fees), split-aware
  //    method mix, per-source split, per-service method mix for the daily cards ──
  const todayPayments = await db.payment.findMany({
    where: { schoolId, voided: false, paymentType: { not: "REFUND" }, paidAt: { gte: dayStart, lte: dayEnd } },
    include: { dailyCharges: { where: { status: "PAID" }, include: { service: { select: { id: true, name: true } } } } },
  });
  const methodMix: Record<string, number> = {};
  for (const p of todayPayments) for (const l of methodLegs(p)) methodMix[l.method] = (methodMix[l.method] ?? 0) + l.amountCents;
  // per daily service, per method (from the linked day-charges' payments)
  const svcMethodMix: Record<string, Record<string, number>> = {};
  for (const p of todayPayments) {
    for (const c of p.dailyCharges) {
      const mix = (svcMethodMix[c.service.id] ??= {});
      for (const l of methodLegs(p)) mix[l.method] = (mix[l.method] ?? 0) + Math.round(l.amountCents * (c.amountCents / Math.max(1, p.amountCents)));
    }
  }
  const incomeToday = {
    totalCents: todayPayments.reduce((s, p) => s + p.amountCents, 0),
    count: todayPayments.length,
    invoicesCents: todayPayments.filter((p) => p.category === "INVOICE").reduce((s, p) => s + p.amountCents, 0),
    dailyCents: todayPayments.filter((p) => p.category === "DAILY").reduce((s, p) => s + p.amountCents, 0),
    byMethod: Object.entries(methodMix).map(([method, cents]) => ({ method, cents })),
  };
  const dailyTodayByMethod = Object.fromEntries(
    services.map((s) => [s.id, Object.entries(svcMethodMix[s.id] ?? {}).map(([method, cents]) => ({ method, cents }))])
  );

  // ── learner distribution (dashboard KPI): active enrolment by classroom,
  //    male/female split per classroom — top 10 by class size ──
  const enrollRows = await db.enrollment.findMany({
    where: { schoolId, status: "ACTIVE" },
    include: { classroom: { select: { name: true } }, student: { select: { gender: true } } },
  });
  const bandMap: Record<string, { classroom: string; m: number; f: number }> = {};
  for (const e of enrollRows) {
    const b = (bandMap[e.classroom.name] ??= { classroom: e.classroom.name, m: 0, f: 0 });
    if (e.student.gender === "M") b.m++;
    else b.f++;
  }
  const classroomBands = Object.values(bandMap).sort((a, b) => (b.m + b.f) - (a.m + a.f)).slice(0, 10);

  // ── teacher "my classes" (dashboard KPI): classrooms this caller's linked
  //    Staff record class-teaches, with today's register snapshot ──
  let myClasses: { classroomId: string; name: string; learners: number; presentToday: number; absentToday: number; registerTaken: boolean }[] = [];
  if (user.staffId) {
    const myRooms =sortClassrooms(await db.classroom.findMany({ where: { schoolId, classTeacherId: user.staffId }, }));
    if (myRooms.length) {
      const todaySessions = await db.attendanceSession.findMany({
        where: { schoolId, date: { gte: dayStart, lte: dayEnd } },
        include: { records: true },
      });
      for (const room of myRooms) {
        const learners = await db.enrollment.count({ where: { schoolId, classroomId: room.id, status: "ACTIVE" } });
        const session = todaySessions.filter((s) => s.classroomId === room.id).sort((a, b) => b.date.getTime() - a.date.getTime())[0] ?? null;
        const presentToday = session ? session.records.filter((r) => r.status === "PRESENT" || r.status === "LATE").length : 0;
        const absentToday = session ? session.records.filter((r) => r.status === "ABSENT").length : 0;
        myClasses.push({ classroomId: room.id, name: room.name, learners, presentToday, absentToday, registerTaken: !!session });
      }
    }
  }

  return NextResponse.json({ daily, payroll, inventory, sms, transport, dailyToday, dailyTodayByMethod, incomeToday, dailyArrearsCents, classroomBands, myClasses });
}

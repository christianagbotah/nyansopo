import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { currentPeriod } from "@/lib/payroll";

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
    const myRooms = await db.classroom.findMany({ where: { schoolId, classTeacherId: user.staffId }, orderBy: { name: "asc" } });
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

  return NextResponse.json({ daily, payroll, inventory, sms, transport, dailyToday, dailyArrearsCents, classroomBands, myClasses });
}

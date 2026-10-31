import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { currentPeriod } from "@/lib/payroll";

// ── GET /api/school/[id]/ops/overview — dashboard widgets for ops modules ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET");
  if (!auth.ok) return auth.res;

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

  return NextResponse.json({ daily, payroll, inventory, sms, transport });
}

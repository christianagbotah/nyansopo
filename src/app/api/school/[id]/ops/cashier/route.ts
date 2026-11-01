import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

// ── GET /api/school/[id]/ops/cashier — cashier console (iEduPro parity)
//    period stats, fee-type breakdown (collected vs outstanding),
//    per-collector performance, recent receipts ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["cashier.reports"] });
  if (!auth.ok) return auth.res;

  const url = new URL(req.url);
  const from = url.searchParams.get("from") ? new Date(url.searchParams.get("from")!) : new Date(new Date().toDateString());
  const toSrc = url.searchParams.get("to") ? new Date(url.searchParams.get("to")!) : new Date();
  const to = new Date(toSrc.getTime() + 864e5 - 1); // end of day
  const collector = url.searchParams.get("collector") ?? undefined; // "all" or receivedByName
  const inRange = { paidAt: { gte: from, lte: to } };

  // ── payments in window (cashier takings: invoice + daily + transport counters) ──
  const payments = await db.payment.findMany({
    where: { schoolId, voided: false, paymentType: "PAYMENT", ...inRange, ...(collector && collector !== "all" ? { receivedByName: collector } : {}) },
    include: { student: true, invoice: true, dailyCharges: { include: { service: true } } },
    orderBy: { paidAt: "desc" },
  });

  const total = payments.reduce((s, p) => s + p.amountCents, 0);
  const studentsServed = new Set(payments.map((p) => p.studentId)).size;

  // ── method breakdown ──
  const byMethod: Record<string, number> = {};
  payments.forEach((p) => { byMethod[p.method] = (byMethod[p.method] ?? 0) + p.amountCents; });

  // ── daily-fee type breakdown: collected (linked to a service) vs outstanding (all-time OWED) ──
  const collectedByService: Record<string, number> = {};
  payments.forEach((p) => {
    if (p.dailyCharges.length) {
      p.dailyCharges.forEach((c) => { collectedByService[c.service.name] = (collectedByService[c.service.name] ?? 0) + c.amountCents; });
    } else if (p.category === "DAILY") {
      collectedByService["Other daily"] = (collectedByService["Other daily"] ?? 0) + p.amountCents;
    }
  });
  const owedCharges = await db.dailyCharge.findMany({ where: { schoolId, status: "OWED" }, include: { service: true } });
  const outstandingByService: Record<string, number> = {};
  owedCharges.forEach((c) => { outstandingByService[c.service.name] = (outstandingByService[c.service.name] ?? 0) + c.amountCents; });

  // invoice-side collected in window (payments not linked to daily charges)
  const invoiceCollected = payments.filter((p) => p.category === "INVOICE").reduce((s, p) => s + p.amountCents, 0);
  const dailyCollected = payments.filter((p) => p.category === "DAILY" || p.dailyCharges.length).reduce((s, p) => s + p.amountCents, 0);
  const transportCollected = payments.filter((p) => p.category === "TRANSPORT").reduce((s, p) => s + p.amountCents, 0);

  // ── collection performance by collector (unfiltered by collector param) ──
  const allPayments = await db.payment.findMany({
    where: { schoolId, voided: false, paymentType: "PAYMENT", ...inRange },
    select: { receivedByName: true, amountCents: true },
  });
  const perf: Record<string, { name: string; total: number; count: number }> = {};
  allPayments.forEach((p) => {
    const k = p.receivedByName ?? "Unattributed";
    perf[k] ??= { name: k, total: 0, count: 0 };
    perf[k].total += p.amountCents;
    perf[k].count++;
  });
  const grand = allPayments.reduce((s, p) => s + p.amountCents, 0) || 1;
  const performance = Object.values(perf)
    .map((v) => ({ ...v, share: Math.round((v.total / grand) * 100) }))
    .sort((a, b) => b.total - a.total);

  // ── roster of collectors (for the filter dropdown) ──
  const collectors = Object.keys(perf).sort();

  return NextResponse.json({
    period: { from: from.toISOString().slice(0, 10), to: toSrc.toISOString().slice(0, 10) },
    collector: collector ?? "all",
    totals: { collected: total, transactions: payments.length, studentsServed },
    split: { invoiceCollected, dailyCollected, transportCollected },
    byMethod,
    feeBreakdown: Object.keys({ ...collectedByService, ...outstandingByService }).map((service) => ({
      service,
      collected: collectedByService[service] ?? 0,
      outstanding: outstandingByService[service] ?? 0,
    })).sort((a, b) => (b.collected + b.outstanding) - (a.collected + a.outstanding)),
    performance,
    collectors,
    recent: payments.slice(0, 12).map((p) => ({
      id: p.id, receiptNo: p.receiptNo, amount: p.amountCents, method: p.method, paidAt: p.paidAt,
      student: p.student ? `${p.student.firstName} ${p.student.lastName}` : "—",
      category: p.category, receivedByName: p.receivedByName,
    })),
  });
}

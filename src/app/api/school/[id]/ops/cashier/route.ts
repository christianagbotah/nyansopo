import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { paymentMethodLabel, type CollectMethodPart } from "@/lib/daily-billing";

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

  // ── method breakdown (SPLIT payments expand into their parts, e.g. part cash + part MoMo) ──
  const byMethod: Record<string, number> = {};
  payments.forEach((p) => {
    if (p.method === "SPLIT" && Array.isArray(p.splitJson)) {
      (p.splitJson as CollectMethodPart[]).forEach((m) => { byMethod[m.method] = (byMethod[m.method] ?? 0) + m.amountCents; });
    } else {
      byMethod[p.method] = (byMethod[p.method] ?? 0) + p.amountCents;
    }
  });

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

  // ── CI3 my_collections parity: detailed rows (per-fee-type split per
  //    transaction) + class summary, for the Detailed / Class Summary reports ──
  const classroomById = new Map<string, string>();
  (await db.classroom.findMany({ where: { schoolId }, select: { id: true, name: true } })).forEach((c) => classroomById.set(c.id, c.name));
  const classByStudent = new Map<string, string>();
  (await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, select: { studentId: true, classroomId: true } })).forEach((e) => classByStudent.set(e.studentId, classroomById.get(e.classroomId) ?? ""));
  const rows = payments.map((p) => {
    const byService: Record<string, number> = {};
    if (p.dailyCharges.length) p.dailyCharges.forEach((c) => { byService[c.service.name] = (byService[c.service.name] ?? 0) + c.amountCents; });
    if (p.category === "INVOICE") byService["Termly fees"] = p.amountCents;
    else if (!p.dailyCharges.length && p.category === "DAILY") byService["Other daily"] = p.amountCents;
    else if (p.category === "POS") byService["Store / tuck"] = p.amountCents;
    else if (p.category === "TRANSPORT") byService["Transport"] = p.amountCents;
    else if (!p.dailyCharges.length) byService[p.category === "INVOICE" ? "Termly fees" : "Other"] = p.amountCents;
    return {
      id: p.id, date: p.paidAt, student: p.student ? `${p.student.firstName} ${p.student.lastName}` : "—",
      classroom: p.student ? (classByStudent.get(p.student.id) ?? "") : "",
      byService, total: p.amountCents, method: paymentMethodLabel(p), receiptNo: p.receiptNo,
      collector: p.receivedByName ?? "Unattributed",
    };
  });
  const serviceNames = [...new Set(rows.flatMap((r) => Object.keys(r.byService)))];
  const classAgg: Record<string, { classroom: string; students: Set<string>; totals: Record<string, number>; total: number; count: number }> = {};
  rows.forEach((r) => {
    const key = r.classroom || "—";
    classAgg[key] ??= { classroom: key, students: new Set(), totals: {}, total: 0, count: 0 };
    classAgg[key].students.add(r.student);
    classAgg[key].count++;
    classAgg[key].total += r.total;
    Object.entries(r.byService).forEach(([svc, amt]) => { classAgg[key].totals[svc] = (classAgg[key].totals[svc] ?? 0) + amt; });
  });
  const classSummary = Object.values(classAgg)
    .map((c) => ({ classroom: c.classroom, students: c.students.size, totals: c.totals, total: c.total, count: c.count }))
    .sort((a, b) => b.total - a.total);
  const serviceTotals: Record<string, number> = {};
  rows.forEach((r) => Object.entries(r.byService).forEach(([svc, amt]) => { serviceTotals[svc] = (serviceTotals[svc] ?? 0) + amt; }));

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
      id: p.id, receiptNo: p.receiptNo, amount: p.amountCents, method: paymentMethodLabel(p), paidAt: p.paidAt,
      student: p.student ? `${p.student.firstName} ${p.student.lastName}` : "—",
      category: p.category, receivedByName: p.receivedByName,
    })),
    // CI3 my_collections report payload
    report: {
      serviceNames,
      serviceTotals,
      grandTotal: total,
      count: rows.length,
      rows: rows.map((r) => ({ ...r, date: r.date.toISOString() })),
      classSummary,
    },
    // learner roster for the student-ledger lookup (CI3 cashier sidebar parity)
    roster: (await db.enrollment.findMany({
      where: { schoolId, status: "ACTIVE" },
      select: { student: { select: { id: true, firstName: true, lastName: true, nyansapoIndex: true } }, classroom: { select: { name: true } } },
      orderBy: { rollNo: "asc" },
    })).map((e) => ({ id: e.student.id, name: `${e.student.firstName} ${e.student.lastName}`, index: e.student.nyansapoIndex, classroom: e.classroom.name })),
  });
}

// ── POST /api/school/[id]/ops/cashier — student ledger lookup (CI3 parity)
//    debit/credit running balance across invoices + payments ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["cashier.reports"] });
  if (!auth.ok) return auth.res;
  const body = await req.json().catch(() => ({}));
  if (body.action !== "ledger") return NextResponse.json({ error: "Unknown action" }, { status: 400 });
  const student = await db.student.findUnique({
    where: { id: String(body.studentId ?? "") },
    include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: true } } },
  });
  if (!student || !await db.enrollment.count({ where: { studentId: student.id, schoolId } }))
    return NextResponse.json({ error: "Learner not found in this school" }, { status: 404 });
  const invoices = await db.invoice.findMany({
    where: { studentId: student.id, schoolId },
    include: { items: true, term: { include: { academicYear: true } } },
    orderBy: { createdAt: "asc" },
  });
  const payments = await db.payment.findMany({
    where: { studentId: student.id, schoolId, voided: false },
    orderBy: { paidAt: "asc" },
  });
  type Row = { date: string; kind: "DEBIT" | "CREDIT"; description: string; reference: string; amount: number; balance: number };
  const rows: Row[] = [];
  invoices.forEach((i) => rows.push({
    date: i.createdAt.toISOString(), kind: "DEBIT",
    description: `${i.term.name} ${i.term.academicYear.name} — ${i.items.map((x) => x.name).join(", ") || "Term bill"}`,
    reference: i.invoiceNo, amount: i.totalCents - i.discountCents, balance: 0,
  }));
  payments.forEach((p) => rows.push({
    date: p.paidAt.toISOString(), kind: "CREDIT",
    description: `${p.category === "DAILY" ? "Daily services" : p.category === "POS" ? "Store purchase" : p.category === "TRANSPORT" ? "Transport fare" : "Fees payment"} · ${paymentMethodLabel(p)}`,
    reference: p.receiptNo, amount: p.amountCents, balance: 0,
  }));
  rows.sort((a, b) => (a.date < b.date ? -1 : 1));
  let bal = 0;
  rows.forEach((r) => { bal += r.kind === "DEBIT" ? r.amount : -r.amount; r.balance = bal; });
  return NextResponse.json({
    student: { name: `${student.firstName} ${student.lastName}`, index: student.nyansapoIndex, classroom: student.enrollments[0]?.classroom?.name ?? null, guardian: student.guardianName, guardianPhone: student.guardianPhone },
    rows,
    closing: bal,
  });
}

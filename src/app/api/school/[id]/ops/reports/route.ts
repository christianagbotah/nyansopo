import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

/* ── /api/school/[id]/ops/reports — finance parity batch (Task 3-d)
   iEduPro CI3 parity:
   · GET  ?tab=approvals                    — receipt/invoice modification-approval queue
   · GET  ?tab=ssnit&period=YYYY-MM         — SSNIT Tier 1 / Tier 2 statutory schedules
   · GET  ?tab=aging                        — receivables aging report (advanced, dueAt-based)
   · GET  ?tab=cashier-summary&date&collector — cashier daily summary + handover data
   · POST action=modreq.create / modreq.decide / modreq.revoke / handover.save ── */

const DAY_MS = 864e5;
const fmtGhs = (cents: number) => `GH₵${(cents / 100).toFixed(2)}`;

/** invoice status per the fees conventions: PAID when paid ≥ total−discount, PARTIAL above 0,
 *  UNPAID at 0, OVERDUE when past the due date and not fully paid (chase-list semantics). */
function invoiceStatus(totalCents: number, discountCents: number, paidCents: number, dueDate?: Date | null): string {
  const due = totalCents - discountCents;
  if (due > 0 && paidCents >= due) return "PAID";
  if (dueDate && dueDate.getTime() < Date.now() && paidCents < due) return "OVERDUE";
  if (paidCents <= 0) return "UNPAID";
  return "PARTIAL";
}

/** Reconstruct an invoice's settled amount (paidCents) from the live ledger under the
 *  overpayment-credit semantics: each receipt contributes min(requested, cap-at-the-time) —
 *  the banked excess lives on StudentCredit CREDIT rows (sourcePaymentId), and credit money
 *  settled onto the bill lives on APPLY rows. Clamped to [0, total−discount]. */
async function resyncPaidCents(tx: any, invoiceId: string): Promise<number> {
  const [inv, live, credits, applies] = await Promise.all([
    tx.invoice.findUnique({ where: { id: invoiceId }, select: { totalCents: true, discountCents: true } }),
    tx.payment.aggregate({ where: { invoiceId, voided: false }, _sum: { amountCents: true } }),
    tx.payment.findMany({ where: { invoiceId, voided: false }, select: { id: true } }).then((rows: { id: string }[]) =>
      rows.length ? tx.studentCredit.aggregate({ where: { kind: "CREDIT", sourcePaymentId: { in: rows.map((r) => r.id) } }, _sum: { amountCents: true } }) : { _sum: { amountCents: 0 } }),
    tx.studentCredit.aggregate({ where: { kind: "APPLY", invoiceId }, _sum: { amountCents: true } }),
  ]);
  if (!inv) return 0;
  const credited = Math.max(0, credits._sum.amountCents ?? 0); // banked excess — never settled the bill
  const applied = Math.abs(applies._sum.amountCents ?? 0); // credit money settled onto the bill
  const cap = Math.max(0, inv.totalCents - inv.discountCents);
  return Math.max(0, Math.min(cap, (live._sum.amountCents ?? 0) - credited + applied));
}

// ─────────────────────────────────────────────────────────────
// GET
// ─────────────────────────────────────────────────────────────
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const url = new URL(req.url);
  const tab = url.searchParams.get("tab") ?? "";

  const PERMS: Record<string, string[]> = {
    approvals: ["fees.view", "approvals.request", "approvals.manage"],
    ssnit: ["payroll.view"],
    aging: ["finance.view"],
    advanced: ["finance.view"],
    "cashier-summary": ["cashier.reports"],
  };
  if (!PERMS[tab]) return NextResponse.json({ error: "Unknown or missing ?tab= (approvals | ssnit | aging | cashier-summary)" }, { status: 400 });
  const auth = await opsAuth(req, schoolId, "GET", { perms: PERMS[tab] });
  if (!auth.ok) return auth.res;

  /* ══════════════════ TAB: approvals (modification queue) ══════════════════ */
  if (tab === "approvals") {
    const requests = await db.modificationRequest.findMany({
      where: { schoolId },
      orderBy: { createdAt: "desc" },
      take: 100,
    });
    const shape = (r: (typeof requests)[number]) => ({
      id: r.id,
      targetType: r.targetType, // PAYMENT | INVOICE
      recordId: r.recordId,
      recordCode: r.recordCode, // receipt no / invoice no
      requestType: r.requestType, // EDIT | DELETE
      reason: r.reason,
      status: r.status, // PENDING | APPROVED | REJECTED | REVOKED
      requestedById: r.requestedById,
      requestedByName: r.requestedByName,
      originalData: r.originalData ? JSON.parse(r.originalData) : null,
      newData: r.newData ? JSON.parse(r.newData) : null,
      handledByName: r.handledByName,
      handledAt: r.handledAt,
      decisionNote: r.decisionNote,
      createdAt: r.createdAt,
    });
    return NextResponse.json({
      pending: requests.filter((r) => r.status === "PENDING").map(shape),
      recent: requests.filter((r) => r.status !== "PENDING").map(shape),
    });
  }

  /* ══════════════════ TAB: ssnit (Tier 1 / Tier 2 statutory schedules) ══════════════════ */
  if (tab === "ssnit") {
    const periodParam = url.searchParams.get("period") ?? undefined;
    const period = periodParam && /^\d{4}-\d{2}$/.test(periodParam)
      ? periodParam
      : (await db.payrollRun.findFirst({ where: { schoolId }, orderBy: { period: "desc" } }))?.period ?? null;

    // statutory rates from OpsSetting (SSNIT_TIER1_EMPLOYER=13.5, SSNIT_TIER1_EMPLOYEE=5.5, SSNIT_TIER2=5.0)
    const settingRows = await db.opsSetting.findMany({ where: { schoolId, key: { in: ["SSNIT_TIER1_EMPLOYER", "SSNIT_TIER1_EMPLOYEE", "SSNIT_TIER2", "SSNIT_NUMBER"] } } });
    const settings: Record<string, string> = Object.fromEntries(settingRows.map((r) => [r.key, r.value]));
    const rateTier1Employer = parseFloat(settings.SSNIT_TIER1_EMPLOYER ?? "13.5");
    const rateTier1Employee = parseFloat(settings.SSNIT_TIER1_EMPLOYEE ?? "5.5");
    const rateTier2 = parseFloat(settings.SSNIT_TIER2 ?? "5");

    const school = await db.school.findUnique({ where: { id: schoolId } });
    const run = period ? await db.payrollRun.findUnique({ where: { schoolId_period: { schoolId, period } } }) : null;
    const payslips = run
      ? await db.payslip.findMany({ where: { payrollRunId: run.id }, include: { staff: true }, orderBy: { staff: { fullName: "asc" } } })
      : [];

    // CI3 computes from basic × statutory rate (uncapped); stored payslip cents are exposed alongside
    const rows = payslips.map((p, i) => {
      const base = p.baseSalaryCents;
      const nameParts = p.staff.fullName.trim().split(/\s+/);
      return {
        sn: i + 1,
        staffId: p.staffId,
        firstName: nameParts[0] ?? p.staff.fullName,
        lastName: nameParts.length > 1 ? nameParts.slice(1).join(" ") : "—",
        otherNames: nameParts.length > 2 ? nameParts.slice(1, -1).join(" ") : "",
        lastToken: nameParts.length > 1 ? nameParts[nameParts.length - 1] : "—",
        ssnitNumber: p.staff.ssnitNumber ?? "",
        tier2MemberId: p.staff.tier2Provider ?? "",
        tier2Provider: p.staff.tier2Provider ?? "",
        staffCode: p.staff.staffCode ?? "",
        basicSalaryCents: base,
        tier1EmployerCents: Math.round((base * rateTier1Employer) / 100),
        tier1EmployeeCents: Math.round((base * rateTier1Employee) / 100),
        tier2Cents: Math.round((base * rateTier2) / 100),
        storedSsnitEmployeeCents: p.ssnitEmployeeCents,
        storedSsnitEmployerCents: p.ssnitEmployerCents,
      };
    });

    return NextResponse.json({
      period,
      run: run ? { id: run.id, status: run.status, payslipCount: payslips.length } : null,
      rates: { tier1Employer: rateTier1Employer, tier1Employee: rateTier1Employee, tier2: rateTier2 },
      establishment: school?.name ?? "—",
      registrationNo: settings.SSNIT_NUMBER ?? school?.code ?? "",
      town: school?.town ?? null,
      region: school?.region ?? null,
      rows,
      totals: {
        basicSalaryCents: rows.reduce((s, r) => s + r.basicSalaryCents, 0),
        tier1EmployerCents: rows.reduce((s, r) => s + r.tier1EmployerCents, 0),
        tier1EmployeeCents: rows.reduce((s, r) => s + r.tier1EmployeeCents, 0),
        tier2Cents: rows.reduce((s, r) => s + r.tier2Cents, 0),
      },
      providers: [...new Set(rows.map((r) => r.tier2Provider).filter(Boolean))],
    });
  }

  /* ══════════════════ TAB: advanced (CI3 Advanced_reports parity) ══════════════════
     Financial summary · cash flow (monthly) · payment-method mix (split payments
     expanded) · expense categories · per-student financial profile. */
  if (tab === "advanced") {
    const fromStr = url.searchParams.get("from") ?? new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10);
    const toStr = url.searchParams.get("to") ?? new Date().toISOString().slice(0, 10);
    const studentId = url.searchParams.get("studentId") || null;
    const from = new Date(`${fromStr}T00:00:00.000Z`);
    const to = new Date(`${toStr}T23:59:59.999Z`);
    const inRange = { gte: from, lte: to };

    const [invoices, payments, expenses] = await Promise.all([
      db.invoice.findMany({ where: { schoolId, createdAt: inRange }, select: { totalCents: true, discountCents: true, paidCents: true, dueDate: true, createdAt: true, studentId: true } }),
      db.payment.findMany({ where: { schoolId, voided: false, paymentType: "PAYMENT", paidAt: inRange }, select: { amountCents: true, method: true, splitJson: true, paidAt: true, studentId: true } }),
      db.expense.findMany({ where: { schoolId, expenseDate: inRange }, select: { amountCents: true, category: true, expenseDate: true } }),
    ]);

    // ── financial summary
    const billed = invoices.reduce((s, i) => s + i.totalCents - i.discountCents, 0);
    const collected = payments.reduce((s, p) => s + p.amountCents, 0);
    const expenseTotal = expenses.reduce((s, e) => s + e.amountCents, 0);
    const allOpen = await db.invoice.findMany({ where: { schoolId, writtenOff: false, status: { not: "WRITTEN_OFF" } }, select: { totalCents: true, discountCents: true, paidCents: true, dueDate: true } });
    const outstanding = allOpen.reduce((s, i) => s + Math.max(0, i.totalCents - i.discountCents - i.paidCents), 0);
    const nowMs = Date.now();
    const overdue = allOpen.reduce((acc, i) => {
      const bal = i.totalCents - i.discountCents - i.paidCents;
      if (bal > 0 && i.dueDate && i.dueDate.getTime() < nowMs) { acc.amount += bal; acc.count++; }
      return acc;
    }, { amount: 0, count: 0 });

    // ── cash flow by month
    const monthKey = (d: Date) => `${d.getUTCFullYear()}-${String(d.getUTCMonth() + 1).padStart(2, "0")}`;
    const flow: Record<string, { inflow: number; outflow: number }> = {};
    for (const p of payments) { const k = monthKey(p.paidAt); (flow[k] ??= { inflow: 0, outflow: 0 }).inflow += p.amountCents; }
    for (const e of expenses) { const k = monthKey(e.expenseDate); (flow[k] ??= { inflow: 0, outflow: 0 }).outflow += e.amountCents; }
    const cashFlow = Object.entries(flow).sort(([a], [b]) => a.localeCompare(b)).map(([period, v]) => ({ period, inflowCents: v.inflow, outflowCents: v.outflow, netCents: v.inflow - v.outflow }));

    // ── payment-method mix (SPLIT payments expanded into their parts)
    const METHOD_LABEL: Record<string, string> = { CASH: "Cash", MOMO_MTN: "MTN MoMo", MOMO_TELECEL: "Telecel Cash", MOMO_AT: "AT Money", BANK: "Bank transfer", CHEQUE: "Cheque", CARD: "Card" };
    const mix: Record<string, { count: number; total: number }> = {};
    for (const p of payments) {
      const parts = Array.isArray(p.splitJson) ? (p.splitJson as { method: string; amountCents: number }[]) : null;
      if (parts && parts.length) {
        for (const part of parts) {
          const key = METHOD_LABEL[part.method] ?? part.method;
          (mix[key] ??= { count: 0, total: 0 }); mix[key].count++; mix[key].total += part.amountCents;
        }
      } else {
        const key = METHOD_LABEL[p.method] ?? p.method;
        (mix[key] ??= { count: 0, total: 0 }); mix[key].count++; mix[key].total += p.amountCents;
      }
    }
    const methods = Object.entries(mix).map(([method, v]) => ({ method, count: v.count, totalCents: v.total, avgCents: Math.round(v.total / Math.max(1, v.count)) })).sort((a, b) => b.totalCents - a.totalCents);

    // ── expense categories
    const cat: Record<string, { count: number; total: number }> = {};
    for (const e of expenses) { (cat[e.category] ??= { count: 0, total: 0 }); cat[e.category].count++; cat[e.category].total += e.amountCents; }
    const categories = Object.entries(cat).map(([category, v]) => ({ category, count: v.count, totalCents: v.total })).sort((a, b) => b.totalCents - a.totalCents);

    // ── per-student financial profile
    let profile: null | {
      student: string; classroom: string | null;
      summary: { invoices: number; billedCents: number; paidCents: number; outstandingCents: number };
      invoices: { invoiceNo: string; createdAt: string; totalCents: number; paidCents: number; balanceCents: number; status: string }[];
      payments: { receiptNo: string; paidAt: string; amountCents: number; method: string }[];
      monthly: { month: string; totalCents: number }[];
    } = null;
    if (studentId) {
      const st = await db.student.findFirst({ where: { id: studentId, enrollments: { some: { schoolId, status: "ACTIVE" } } }, include: { enrollments: { where: { schoolId, status: "ACTIVE" }, take: 1, include: { classroom: { select: { name: true } } } } } });
      if (st) {
        const [stInvoices, stPayments] = await Promise.all([
          db.invoice.findMany({ where: { schoolId, studentId }, orderBy: { createdAt: "desc" }, take: 50 }),
          db.payment.findMany({ where: { schoolId, studentId, voided: false, paymentType: "PAYMENT" }, orderBy: { paidAt: "desc" }, take: 50 }),
        ]);
        const monthly: Record<string, number> = {};
        for (const p of stPayments) { const k = monthKey(p.paidAt); monthly[k] = (monthly[k] ?? 0) + p.amountCents; }
        profile = {
          student: `${st.firstName} ${st.lastName}`,
          classroom: st.enrollments[0]?.classroom?.name ?? null,
          summary: {
            invoices: stInvoices.length,
            billedCents: stInvoices.reduce((s, i) => s + i.totalCents - i.discountCents, 0),
            paidCents: stPayments.reduce((s, p) => s + p.amountCents, 0),
            outstandingCents: stInvoices.reduce((s, i) => s + Math.max(0, i.totalCents - i.discountCents - i.paidCents), 0),
          },
          invoices: stInvoices.map((i) => ({ invoiceNo: i.invoiceNo, createdAt: i.createdAt.toISOString(), totalCents: i.totalCents - i.discountCents, paidCents: i.paidCents, balanceCents: Math.max(0, i.totalCents - i.discountCents - i.paidCents), status: i.status })),
          payments: stPayments.map((p) => ({ receiptNo: p.receiptNo, paidAt: p.paidAt.toISOString(), amountCents: p.amountCents, method: METHOD_LABEL[p.method] ?? p.method })),
          monthly: Object.entries(monthly).sort(([a], [b]) => a.localeCompare(b)).map(([month, totalCents]) => ({ month, totalCents })),
        };
      }
    }

    return NextResponse.json({
      range: { from: fromStr, to: toStr },
      summary: {
        invoices: invoices.length, billedCents: billed, collectedCents: collected, outstandingCents: outstanding,
        overdueCents: overdue.amount, overdueCount: overdue.count,
        payments: payments.length, avgPaymentCents: payments.length ? Math.round(collected / payments.length) : 0,
        expenses: expenses.length, expenseCents: expenseTotal, netCents: collected - expenseTotal,
      },
      cashFlow, methods, categories, profile,
    });
  }

  /* ══════════════════ TAB: aging (receivables, CI3 advanced — dueAt when present) ══════════════════ */
  if (tab === "aging") {
    const invoices = await db.invoice.findMany({
      where: { schoolId, writtenOff: false, status: { not: "WRITTEN_OFF" } },
      include: { student: true },
      orderBy: { createdAt: "asc" },
    });
    const now = Date.now();
    const summary = { currentCents: 0, currentCount: 0, d31Cents: 0, d31Count: 0, d61Cents: 0, d61Count: 0, d90Cents: 0, d90Count: 0 };
    const rows = invoices
      .map((i) => {
        const balanceCents = i.totalCents - i.discountCents - i.paidCents;
        if (balanceCents <= 0) return null;
        const anchor = i.dueDate ?? i.createdAt; // CI3 advanced aging: age from due date when set
        const daysOutstanding = Math.max(0, Math.floor((now - anchor.getTime()) / DAY_MS));
        const bucket = daysOutstanding <= 30 ? "current" : daysOutstanding <= 60 ? "d31" : daysOutstanding <= 90 ? "d61" : "d90";
        if (bucket === "current") { summary.currentCents += balanceCents; summary.currentCount++; }
        else if (bucket === "d31") { summary.d31Cents += balanceCents; summary.d31Count++; }
        else if (bucket === "d61") { summary.d61Cents += balanceCents; summary.d61Count++; }
        else { summary.d90Cents += balanceCents; summary.d90Count++; }
        return {
          student: `${i.student.firstName} ${i.student.lastName}`,
          classroom: i.classroomName,
          invoiceNo: i.invoiceNo,
          invoiceDate: i.createdAt,
          dueDate: i.dueDate,
          daysOutstanding,
          balanceCents,
          bucket, // current | d31 | d61 | d90
        };
      })
      .filter((r): r is NonNullable<typeof r> => r !== null)
      .sort((a, b) => b.daysOutstanding - a.daysOutstanding || b.balanceCents - a.balanceCents);

    return NextResponse.json({
      summary,
      rows,
      totalCents: summary.currentCents + summary.d31Cents + summary.d61Cents + summary.d90Cents,
      totalCount: summary.currentCount + summary.d31Count + summary.d61Count + summary.d90Count,
    });
  }

  /* ══════════════════ TAB: cashier-summary (daily account rendering + handover) ══════════════════ */
  if (tab === "cashier-summary") {
    const dateStr = url.searchParams.get("date") ?? new Date().toISOString().slice(0, 10);
    const collector = url.searchParams.get("collector") ?? ""; // "" or "all" = everyone
    const dayStart = new Date(`${dateStr}T00:00:00.000Z`);
    const dayEnd = new Date(dayStart.getTime() + DAY_MS - 1);
    const inDay = { gte: dayStart, lte: dayEnd };

    const payments = await db.payment.findMany({
      where: {
        schoolId, voided: false, paymentType: "PAYMENT", paidAt: inDay,
        ...(collector && collector !== "all" ? { receivedByName: collector } : {}),
      },
      include: { student: { include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: true } } } } },
      orderBy: { paidAt: "asc" },
    });

    const totalCents = payments.reduce((s, p) => s + p.amountCents, 0);
    const byCategory: Record<string, number> = {};
    const byMethod: Record<string, number> = {};
    const students = new Set<string>();
    for (const p of payments) {
      byCategory[p.category] = (byCategory[p.category] ?? 0) + p.amountCents;
      byMethod[p.method] = (byMethod[p.method] ?? 0) + p.amountCents;
      if (p.studentId) students.add(p.studentId);
    }

    // outstanding daily-fee arrears (all-time OWED day-charges — same derivation as the cashier console)
    const owed = await db.dailyCharge.aggregate({ where: { schoolId, status: "OWED" }, _sum: { amountCents: true } });

    // wallet top-ups of the day (prepaid + transport credits)
    const topups = await db.walletTxn.findMany({
      where: { schoolId, kind: "TOPUP", at: inDay },
      include: { student: true },
      orderBy: { at: "asc" },
    });

    // existing signed handover reports for the date
    const handovers = await db.handoverReport.findMany({ where: { schoolId, date: inDay }, orderBy: { createdAt: "desc" } });

    // collector roster (anyone who ever took a payment) + back-office users for the handover signature selects
    const collectorRows = await db.payment.findMany({ where: { schoolId, receivedByName: { not: null } }, select: { receivedByName: true }, distinct: ["receivedByName"] });
    const users = await db.user.findMany({
      where: { schoolId, role: { in: ["SUPER_ADMIN", "GROUP_OWNER", "SCHOOL_ADMIN", "ACCOUNTANT", "CASHIER"] }, status: { not: "SUSPENDED" } },
      select: { id: true, fullName: true, role: true },
      orderBy: { fullName: "asc" },
    });

    return NextResponse.json({
      date: dateStr,
      collector: collector || "all",
      totals: {
        totalCents,
        cashCents: byMethod.CASH ?? 0,
        transactions: payments.length,
        distinctStudents: students.size,
        receipts: payments.length,
      },
      byCategory,
      byMethod,
      dailyArrearsCents: owed._sum.amountCents ?? 0,
      topups: topups.map((t) => ({
        id: t.id, student: `${t.student.firstName} ${t.student.lastName}`, wallet: t.wallet,
        amountCents: Math.abs(t.amountCents), byName: t.byName, at: t.at,
      })),
      receipts: payments.map((p) => ({
        id: p.id, receiptNo: p.receiptNo,
        student: p.student ? `${p.student.firstName} ${p.student.lastName}` : "—",
        classroom: p.student?.enrollments[0]?.classroom?.name ?? null,
        amountCents: p.amountCents, method: p.method, category: p.category,
        collector: p.receivedByName, paidAt: p.paidAt,
      })),
      handovers,
      collectors: collectorRows.map((c) => c.receivedByName!).filter(Boolean).sort(),
      users,
    });
  }

  return NextResponse.json({ error: "Unknown tab" }, { status: 400 });
}

// ─────────────────────────────────────────────────────────────
// POST — modreq.create / modreq.decide / modreq.revoke / handover.save
// ─────────────────────────────────────────────────────────────
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;

  const PERMS: Record<string, string[]> = {
    "modreq.create": ["approvals.request", "approvals.manage"],
    "modreq.decide": ["approvals.manage"], // iEduPro: only level-1 admins adjudicate
    "modreq.revoke": ["approvals.manage"],
    "handover.save": ["cashier.reports"],
  };
  if (!PERMS[action]) return NextResponse.json({ error: "Unknown action" }, { status: 400 });
  const auth = await opsAuth(req, schoolId, "POST", { perms: PERMS[action] });
  if (!auth.ok) return auth.res;
  const user = auth.user;

  /* ══════════════════ cashier handover save (one per cashier+date, upsert-style replace) ══════════════════ */
  if (action === "handover.save") {
    const { date, cashierId, cashierName, receiverId, receiverName, totalCents, cashCents } = body;
    if (!date || !cashierId || !cashierName || !receiverId || !receiverName) {
      return NextResponse.json({ error: "Date, cashier and receiver are required" }, { status: 400 });
    }
    const dayStart = new Date(`${String(date).slice(0, 10)}T00:00:00.000Z`);
    const dayEnd = new Date(dayStart.getTime() + DAY_MS - 1);
    const total = Math.max(0, Math.round(Number(totalCents) || 0));
    const cash = Math.min(total, Math.max(0, Math.round(Number(cashCents) || 0)));
    const saved = await db.$transaction(async (tx) => {
      await tx.handoverReport.deleteMany({ where: { schoolId, cashierId, date: { gte: dayStart, lte: dayEnd } } });
      return tx.handoverReport.create({
        data: {
          schoolId, date: dayStart, cashierId, cashierName, receiverId, receiverName,
          totalCents: total, cashCents: cash,
        },
      });
    });
    return NextResponse.json({ handover: saved });
  }

  /* ══════════════════ modreq.create — cashier/bursar raises an EDIT/DELETE request ══════════════════ */
  if (action === "modreq.create") {
    const { targetType, recordId, requestType, reason, newData } = body;
    if (!["PAYMENT", "INVOICE"].includes(targetType)) return NextResponse.json({ error: "targetType must be PAYMENT or INVOICE" }, { status: 400 });
    if (!["EDIT", "DELETE"].includes(requestType)) return NextResponse.json({ error: "requestType must be EDIT or DELETE" }, { status: 400 });
    if (!reason?.trim()) return NextResponse.json({ error: "A reason is required" }, { status: 400 });
    if (requestType === "EDIT" && !newData) return NextResponse.json({ error: "EDIT requests need the proposed newData" }, { status: 400 });

    let recordCode: string;
    let resolvedRecordId: string; // the row id — decide-time actions always act on this
    let originalData: Record<string, unknown>;
    // recordId may be the row id OR the human code (receipt no / invoice no) — cashiers paste what they see
    const recordKey = String(recordId ?? "").trim();
    if (targetType === "PAYMENT") {
      const p = await db.payment.findFirst({ where: { schoolId, OR: [{ id: recordKey }, { receiptNo: recordKey }] } });
      if (!p) return NextResponse.json({ error: "Receipt not found" }, { status: 404 });
      recordCode = p.receiptNo;
      resolvedRecordId = p.id;
      originalData = {
        id: p.id, receiptNo: p.receiptNo, amountCents: p.amountCents, method: p.method,
        category: p.category, paidAt: p.paidAt, invoiceId: p.invoiceId, voided: p.voided,
      };
      if (newData) {
        if (newData.amountCents !== undefined && (!Number.isFinite(Number(newData.amountCents)) || Number(newData.amountCents) <= 0)) {
          return NextResponse.json({ error: "newData.amountCents must be a positive amount (pesewas)" }, { status: 400 });
        }
      }
    } else {
      const i = await db.invoice.findFirst({ where: { schoolId, OR: [{ id: recordKey }, { invoiceNo: recordKey }] } });
      if (!i) return NextResponse.json({ error: "Invoice not found" }, { status: 404 });
      recordCode = i.invoiceNo;
      resolvedRecordId = i.id;
      originalData = {
        id: i.id, invoiceNo: i.invoiceNo, totalCents: i.totalCents, discountCents: i.discountCents,
        paidCents: i.paidCents, status: i.status, studentId: i.studentId,
      };
    }

    // dedupe: only one PENDING request per record
    const dupe = await db.modificationRequest.findFirst({ where: { schoolId, recordCode, status: "PENDING" } });
    if (dupe) {
      return NextResponse.json({ error: `A pending ${dupe.requestType.toLowerCase()} request already exists for ${recordCode}`, status: "PENDING", requestId: dupe.id }, { status: 409 });
    }

    const created = await db.modificationRequest.create({
      data: {
        schoolId, targetType, recordId: resolvedRecordId, recordCode, requestType,
        reason: String(reason).trim(),
        status: "PENDING",
        requestedById: user.id, requestedByName: user.fullName,
        originalData: JSON.stringify(originalData),
        newData: newData ? JSON.stringify(newData) : null,
      },
    });
    return NextResponse.json({ request: created });
  }

  /* ══════════════════ modreq.decide — level-1 admin approves / rejects ══════════════════ */
  if (action === "modreq.decide") {
    const { id, decision, note } = body;
    if (!["APPROVED", "REJECTED"].includes(decision)) return NextResponse.json({ error: "decision must be APPROVED or REJECTED" }, { status: 400 });
    const reqRow = await db.modificationRequest.findFirst({ where: { id, schoolId } });
    if (!reqRow) return NextResponse.json({ error: "Request not found" }, { status: 404 });
    if (reqRow.status !== "PENDING") return NextResponse.json({ error: `Request is already ${reqRow.status}` }, { status: 400 });

    const adminNote = note?.trim() ? ` ${String(note).trim()}` : "";
    let audit = "";

    if (decision === "APPROVED") {
      // perform the modification faithfully, inside one transaction
      try {
        await db.$transaction(async (tx) => {
          if (reqRow.targetType === "PAYMENT") {
            const p = await tx.payment.findFirst({ where: { id: reqRow.recordId, schoolId } });
            if (!p) {
              audit = `Approved — but receipt ${reqRow.recordCode} no longer exists; nothing to ${reqRow.requestType.toLowerCase()}.`;
              return;
            }
            if (reqRow.requestType === "DELETE") {
              // 1) reverse the linked invoice's paid amount — subtract only the portion
              //    that actually settled the bill (overpayment excess went to credit)
              if (p.invoiceId) {
                const inv = await tx.invoice.findUnique({ where: { id: p.invoiceId } });
                if (inv) {
                  const attributable = Math.min(p.amountCents, inv.paidCents);
                  const paid = Math.max(0, inv.paidCents - attributable);
                  const status = invoiceStatus(inv.totalCents, inv.discountCents, paid, inv.dueDate);
                  await tx.invoice.update({ where: { id: inv.id }, data: { paidCents: paid, status } });
                  audit += `Invoice ${inv.invoiceNo} paidCents reversed ${fmtGhs(inv.paidCents)} → ${fmtGhs(paid)}, status ${inv.status} → ${status}. `;
                }
              }
              // 1b) reverse any overpayment credit banked from this receipt (append-only ADJUST row)
              const banked = await tx.studentCredit.findFirst({ where: { kind: "CREDIT", sourcePaymentId: p.id } });
              if (banked && banked.amountCents > 0) {
                const agg = await tx.studentCredit.aggregate({ where: { schoolId, studentId: banked.studentId }, _sum: { amountCents: true } });
                const bal = agg._sum.amountCents ?? 0;
                await tx.studentCredit.create({
                  data: {
                    schoolId, studentId: banked.studentId, kind: "ADJUST", amountCents: -banked.amountCents,
                    balanceAfter: bal - banked.amountCents,
                    note: `Receipt ${p.receiptNo} deleted — overpayment credit reversed`, byName: user.fullName,
                  },
                });
                audit += `Prepaid credit reversed ${fmtGhs(banked.amountCents)}. `;
              }
              // 2) unlink any day-charges settled by this receipt so the daily ledger stays honest
              const charges = await tx.dailyCharge.findMany({ where: { paymentId: p.id } });
              if (charges.length) {
                await tx.dailyCharge.updateMany({ where: { paymentId: p.id }, data: { paymentId: null, status: "OWED" } });
                audit += `${charges.length} daily charge(s) reverted to OWED. `;
              }
              // 3) delete the payment row
              await tx.payment.delete({ where: { id: p.id } });
              audit += `Receipt ${p.receiptNo} (${fmtGhs(p.amountCents)}, ${p.method}) deleted.`;
            } else {
              // EDIT: apply proposed amount/method, then re-sync the invoice from its live receipts
              const nd = reqRow.newData ? JSON.parse(reqRow.newData) : {};
              const newAmount = nd.amountCents !== undefined ? Math.round(Number(nd.amountCents)) : p.amountCents;
              const newMethod = nd.method ?? p.method;
              if (!Number.isFinite(newAmount) || newAmount <= 0) throw new Error("Proposed amount is invalid");
              const updated = await tx.payment.update({ where: { id: p.id }, data: { amountCents: newAmount, method: newMethod } });
              audit += `Receipt ${p.receiptNo} adjusted ${fmtGhs(p.amountCents)}/${p.method} → ${fmtGhs(newAmount)}/${newMethod}.`;
              if (p.invoiceId) {
                // ledger-aware resync: payments − banked excess + applied credits, clamped
                const paid = await resyncPaidCents(tx, p.invoiceId);
                const inv = await tx.invoice.findUnique({ where: { id: p.invoiceId } });
                if (inv) {
                  const status = invoiceStatus(inv.totalCents, inv.discountCents, paid, inv.dueDate);
                  await tx.invoice.update({ where: { id: inv.id }, data: { paidCents: paid, status } });
                  audit += ` Invoice ${inv.invoiceNo} paidCents re-synced ${fmtGhs(inv.paidCents)} → ${fmtGhs(paid)}, status ${inv.status} → ${status}.`;
                }
              }
              void updated;
            }
          } else {
            // INVOICE target
            const inv = await tx.invoice.findFirst({ where: { id: reqRow.recordId, schoolId } });
            if (!inv) {
              audit = `Approved — but invoice ${reqRow.recordCode} no longer exists; nothing to ${reqRow.requestType.toLowerCase()}.`;
              return;
            }
            if (reqRow.requestType === "DELETE") {
              const linked = await tx.payment.count({ where: { invoiceId: inv.id, voided: false } });
              if (linked > 0) {
                throw new Error(`Invoice ${inv.invoiceNo} has ${linked} linked receipt(s) — reverse them first (request a receipt deletion)`);
              }
              await tx.invoice.delete({ where: { id: inv.id } });
              audit = `Invoice ${inv.invoiceNo} (${fmtGhs(inv.totalCents - inv.discountCents)} billed) deleted.`;
            } else {
              const nd = reqRow.newData ? JSON.parse(reqRow.newData) : {};
              const total = nd.totalCents !== undefined ? Math.round(Number(nd.totalCents)) : inv.totalCents;
              const discount = nd.discountCents !== undefined ? Math.round(Number(nd.discountCents)) : inv.discountCents;
              if (!Number.isFinite(total) || total <= 0) throw new Error("Proposed totalCents is invalid");
              const status = invoiceStatus(total, Math.max(0, discount), inv.paidCents, inv.dueDate);
              await tx.invoice.update({ where: { id: inv.id }, data: { totalCents: total, discountCents: Math.max(0, discount), status } });
              audit = `Invoice ${inv.invoiceNo} adjusted total ${fmtGhs(inv.totalCents)} → ${fmtGhs(total)}${discount !== inv.discountCents ? `, discount ${fmtGhs(inv.discountCents)} → ${fmtGhs(discount)}` : ""}, status ${inv.status} → ${status}.`;
            }
          }
        });
      } catch (e: unknown) {
        const msg = e instanceof Error ? e.message : "Modification failed";
        return NextResponse.json({ error: `Could not apply approval: ${msg}` }, { status: 400 });
      }
    } else {
      audit = "No data change — request rejected.";
    }

    const updated = await db.modificationRequest.update({
      where: { id: reqRow.id },
      data: {
        status: decision,
        handledById: user.id, handledByName: user.fullName, handledAt: new Date(),
        decisionNote: `${decision === "APPROVED" ? "Approved" : "Rejected"} by ${user.fullName}.${adminNote}${audit ? ` ${audit}` : ""}`,
      },
    });
    return NextResponse.json({ request: updated });
  }

  /* ══════════════════ modreq.revoke — un-approve (manual reversal, CI3 audit posture) ══════════════════ */
  if (action === "modreq.revoke") {
    const reqRow = await db.modificationRequest.findFirst({ where: { id: body.id, schoolId } });
    if (!reqRow) return NextResponse.json({ error: "Request not found" }, { status: 404 });
    if (reqRow.status !== "APPROVED") return NextResponse.json({ error: "Only approved requests can be revoked" }, { status: 400 });
    const prior = reqRow.decisionNote ?? "";
    const updated = await db.modificationRequest.update({
      where: { id: reqRow.id },
      data: {
        status: "REVOKED",
        handledById: user.id, handledByName: user.fullName, handledAt: new Date(),
        decisionNote: `${prior} Revoked by ${user.fullName} — manual reversal required.`,
      },
    });
    return NextResponse.json({ request: updated });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

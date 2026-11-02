import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

/* ── GET /api/school/[id]/ops/finance — income dashboard, expenditure, aging,
   student credit wallets (prepaid credits from overpayment — CI3 parity) ──
   iedupro parity: Income & Revenue analytics, AR aging, expenditure dashboard. */
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const url = new URL(req.url);
  const q = (k: string) => url.searchParams.get(k) ?? undefined;
  const action = q("action");

  // ── student credit wallets ──
  if (action === "credits" || action === "credit-history") {
    // fees-side gate: the Credits tab lives in the Fees module (fees.view),
    // the finance dashboard keeps its own finance.view — any-of.
    const auth = await opsAuth(req, schoolId, "GET", { perms: ["fees.view", "finance.view"] });
    if (!auth.ok) return auth.res;

    if (action === "credits") {
      const grouped = await db.studentCredit.groupBy({
        by: ["studentId"],
        where: { schoolId },
        _sum: { amountCents: true },
        _count: { _all: true },
        _max: { at: true },
      });
      const ids = grouped.map((g) => g.studentId);
      const students = ids.length
        ? await db.student.findMany({ where: { id: { in: ids } }, include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: true } } } })
        : [];
      const byId = new Map(students.map((s) => [s.id, s]));
      const rows = grouped
        .map((g) => {
          const s = byId.get(g.studentId);
          return {
            studentId: g.studentId,
            name: s ? `${s.firstName} ${s.lastName}` : "Unknown learner",
            classroom: s?.enrollments[0]?.classroom?.name ?? null,
            balanceCents: g._sum.amountCents ?? 0,
            records: g._count._all,
            lastAt: g._max.at?.toISOString() ?? null,
          };
        })
        .sort((a, b) => b.balanceCents - a.balanceCents || a.name.localeCompare(b.name));

      // stats: created = credits entering from outside (overpayment/manual/refund-in
      // + positive corrections); applied = credits consumed on school bills
      // (invoice auto/manual apply + daily-wallet conversion). Transfers are
      // internal moves and excluded from both sides of the ratio.
      const ledger = await db.studentCredit.findMany({ where: { schoolId }, select: { kind: true, amountCents: true } });
      const createdCents = ledger.filter((l) => l.amountCents > 0 && (l.kind === "CREDIT" || l.kind === "ADJUST")).reduce((s, l) => s + l.amountCents, 0);
      const appliedCents = ledger.filter((l) => l.amountCents < 0 && (l.kind === "APPLY" || l.kind === "DAILY_WALLET")).reduce((s, l) => s - l.amountCents, 0);
      const stats = {
        activeCents: rows.reduce((s, r) => s + Math.max(0, r.balanceCents), 0),
        studentsWithCredits: rows.filter((r) => r.balanceCents > 0).length,
        appliedCents,
        createdCents,
        utilizationPct: createdCents ? Math.round((appliedCents / createdCents) * 100) : 0,
      };

      // roster for the add-credit / transfer picker (every active learner)
      const enrolled = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, include: { student: true, classroom: true } });
      const roster = enrolled
        .map((e) => ({ id: e.studentId, name: `${e.student.firstName} ${e.student.lastName}`, classroom: e.classroom.name }))
        .sort((a, b) => a.name.localeCompare(b.name));
      return NextResponse.json({ rows, stats, students: roster });
    }

    // action === "credit-history"
    const studentId = q("studentId");
    if (!studentId) return NextResponse.json({ error: "studentId required" }, { status: 400 });
    const student = await db.student.findUnique({ where: { id: studentId } });
    if (!student) return NextResponse.json({ error: "Learner not found" }, { status: 404 });
    const ledger = await db.studentCredit.findMany({ where: { schoolId, studentId }, orderBy: { at: "asc" } });
    // StudentCredit.invoiceId is a raw column (no Prisma relation) — resolve numbers in a second query
    const invIds = [...new Set(ledger.map((r) => r.invoiceId).filter((x): x is string => !!x))];
    const invoices = invIds.length ? await db.invoice.findMany({ where: { id: { in: invIds } }, select: { id: true, invoiceNo: true } }) : [];
    const invNoById = new Map(invoices.map((i) => [i.id, i.invoiceNo]));
    return NextResponse.json({
      student: { name: `${student.firstName} ${student.lastName}` },
      balanceCents: ledger.length ? ledger[ledger.length - 1].balanceAfter : 0,
      rows: ledger.map((r) => ({ id: r.id, at: r.at.toISOString(), kind: r.kind, amountCents: r.amountCents, balanceAfter: r.balanceAfter, note: r.note, byName: r.byName, invoiceNo: r.invoiceId ? invNoById.get(r.invoiceId) ?? null : null })),
    });
  }

  const auth = await opsAuth(req, schoolId, "GET", { perms: ["finance.view"] });
  if (!auth.ok) return auth.res;

  const yearId = q("yearId");

  const year = yearId
    ? await db.academicYear.findFirst({ where: { schoolId, id: yearId } })
    : await db.academicYear.findFirst({ where: { schoolId, isCurrent: true } });
  const years = await db.academicYear.findMany({ where: { schoolId }, orderBy: { name: "desc" } });

  const now = new Date();
  const yearStart = year?.startsAt ?? new Date(now.getFullYear(), 0, 1);
  const yearEnd = year?.endsAt ?? new Date(now.getFullYear(), 11, 31, 23, 59, 59);

  // ── revenue sources: invoices, daily, POS, transport ──
  const payments = await db.payment.findMany({
    where: { schoolId, voided: false, paidAt: { gte: yearStart, lte: yearEnd } },
  });
  const byCategory: Record<string, number> = { INVOICE: 0, DAILY: 0, POS: 0, TRANSPORT: 0 };
  const byMonth: Record<string, number> = {};
  for (const p of payments) {
    byCategory[p.category] = (byCategory[p.category] ?? 0) + p.amountCents;
    const k = `${p.paidAt.getFullYear()}-${String(p.paidAt.getMonth() + 1).padStart(2, "0")}`;
    byMonth[k] = (byMonth[k] ?? 0) + p.amountCents;
  }
  const totalRevenue = payments.reduce((s, p) => s + p.amountCents, 0);

  // month series Jan..Dec of the academic year window
  const monthly: { month: string; revenue: number; expenses: number }[] = [];
  const expensesAll = await db.expense.findMany({ where: { schoolId, expenseDate: { gte: yearStart, lte: yearEnd } } });
  const expByMonth: Record<string, number> = {};
  for (const e of expensesAll) {
    const k = `${e.expenseDate.getFullYear()}-${String(e.expenseDate.getMonth() + 1).padStart(2, "0")}`;
    expByMonth[k] = (expByMonth[k] ?? 0) + e.amountCents;
  }
  const cur = new Date(yearStart);
  const MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
  while (cur <= yearEnd && monthly.length < 14) {
    const k = `${cur.getFullYear()}-${String(cur.getMonth() + 1).padStart(2, "0")}`;
    monthly.push({ month: MONTHS[cur.getMonth()], revenue: byMonth[k] ?? 0, expenses: expByMonth[k] ?? 0 });
    cur.setMonth(cur.getMonth() + 1);
  }

  // ── outstanding: unpaid invoices (this year) ──
  const invoices = await db.invoice.findMany({
    where: { schoolId, term: { academicYearId: year?.id ?? "___" }, status: { in: ["UNPAID", "PARTIAL", "OVERDUE"] } },
    include: { student: true, items: true, term: { include: { academicYear: true } } },
  });
  const outstandingTotal = invoices.reduce((s, i) => s + (i.totalCents - i.discountCents - i.paidCents), 0);

  // ── AR aging buckets by invoice due date ──
  const today = now.getTime();
  const buckets = { current: { amount: 0, students: new Set<string>() }, b31: { amount: 0, students: new Set<string>() }, b61: { amount: 0, students: new Set<string>() }, b90: { amount: 0, students: new Set<string>() } };
  for (const i of invoices) {
    const bal = i.totalCents - i.discountCents - i.paidCents;
    if (bal <= 0) continue;
    const days = i.dueDate ? Math.floor((today - i.dueDate.getTime()) / 864e5) : 0;
    const b = days <= 30 ? buckets.current : days <= 60 ? buckets.b31 : days <= 90 ? buckets.b61 : buckets.b90;
    b.amount += bal;
    b.students.add(i.studentId);
  }

  // ── expenditure by category ──
  const expByCategory: Record<string, number> = {};
  for (const e of expensesAll) expByCategory[e.category] = (expByCategory[e.category] ?? 0) + e.amountCents;
  const totalExpenditure = expensesAll.reduce((s, e) => s + e.amountCents, 0);
  const recentExpenses = await db.expense.findMany({ where: { schoolId }, orderBy: { expenseDate: "desc" }, take: 12 });

  // ── student picker for ledger ──
  const enrolled = await db.enrollment.findMany({
    where: { schoolId, status: "ACTIVE" },
    include: { student: true, classroom: true },
  });

  return NextResponse.json({
    yearLabel: year?.name ?? "—",
    years: years.map((y) => ({ id: y.id, label: y.name, isCurrent: y.isCurrent })),
    income: {
      totalRevenue,
      byCategory,
      monthly,
      outstanding: outstandingTotal,
      outstandingCount: invoices.length,
      lastMonth: monthly.length >= 2 ? monthly[monthly.length - 2].revenue : 0,
    },
    aging: {
      current: { amount: buckets.current.amount, students: buckets.current.students.size },
      b31: { amount: buckets.b31.amount, students: buckets.b31.students.size },
      b61: { amount: buckets.b61.amount, students: buckets.b61.students.size },
      b90: { amount: buckets.b90.amount, students: buckets.b90.students.size },
    },
    expenditure: {
      total: totalExpenditure,
      byCategory: Object.entries(expByCategory).map(([k, v]) => ({ category: k, amount: v })).sort((a, b) => b.amount - a.amount),
      recent: recentExpenses.map((e) => ({ id: e.id, category: e.category, description: e.description, amount: e.amountCents, method: e.method, date: e.expenseDate, recordedByName: e.recordedByName })),
    },
    students: enrolled.map((e) => ({ id: e.studentId, name: `${e.student.firstName} ${e.student.lastName}`, classroom: e.classroom.name, guardianPhone: e.student.guardianPhone })),
  });
}

/* ── POST /api/school/[id]/ops/finance — ledger, terminal bills, expenses ── */
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: action === "save-expense" || action === "delete-expense" ? ["finance.manage"] : action === "cashier-report" ? ["cashier.reports"] : action === "terminal-bills" ? ["fees.view", "cashier.reports", "daily.view"] : action.startsWith("credit-") ? ["fees.manage", "fees.collect"] : ["fees.view"] });
  if (!auth.ok) return auth.res;
  const user = auth.user;

  // ── student credit wallets: manual credit / adjustment (CI3 parity) ──
  if (action === "credit-create" || action === "credit-adjust") {
    const { studentId, amountCents, note } = body;
    if (!studentId) return NextResponse.json({ error: "studentId required" }, { status: 400 });
    const amt = Math.round(Number(amountCents) || 0);
    if (!amt) return NextResponse.json({ error: "Enter a non-zero amount" }, { status: 400 });
    if (action === "credit-create" && amt <= 0) return NextResponse.json({ error: "A manual credit must be a positive amount" }, { status: 400 });
    if (action === "credit-adjust" && !note) return NextResponse.json({ error: "A note is required for credit adjustments (audit trail)" }, { status: 400 });
    const student = await db.student.findUnique({ where: { id: studentId } });
    if (!student) return NextResponse.json({ error: "Learner not found" }, { status: 404 });
    const bal = await creditBalance(schoolId, studentId);
    const after = bal + amt;
    if (after < 0) return NextResponse.json({ error: `Adjustment refused — it would overdraw the credit. Current balance is GH₵${(bal / 100).toFixed(2)}` }, { status: 400 });
    const row = await db.studentCredit.create({
      data: {
        schoolId, studentId,
        kind: action === "credit-create" ? "CREDIT" : "ADJUST",
        amountCents: amt, balanceAfter: after,
        note: note ?? (action === "credit-create" ? "Manual credit" : "Balance adjustment"),
        byName: user.fullName,
      },
    });
    return NextResponse.json({ credit: row, balanceCents: after });
  }

  // ── transfer a credit balance to another learner (sibling/family moves) ──
  if (action === "credit-transfer") {
    const { fromStudentId, toStudentId, amountCents, note } = body;
    const amt = Math.round(Number(amountCents) || 0);
    if (!fromStudentId || !toStudentId) return NextResponse.json({ error: "fromStudentId and toStudentId are required" }, { status: 400 });
    if (fromStudentId === toStudentId) return NextResponse.json({ error: "Choose two different learners" }, { status: 400 });
    if (!amt || amt <= 0) return NextResponse.json({ error: "Enter a transfer amount above zero" }, { status: 400 });
    const [from, to] = await Promise.all([
      db.student.findUnique({ where: { id: fromStudentId } }),
      db.student.findUnique({ where: { id: toStudentId } }),
    ]);
    if (!from || !to) return NextResponse.json({ error: "Learner not found" }, { status: 404 });
    const balFrom = await creditBalance(schoolId, fromStudentId);
    if (balFrom < amt) return NextResponse.json({ error: `Insufficient credit — ${from.firstName}'s balance is GH₵${(balFrom / 100).toFixed(2)}` }, { status: 400 });
    const balTo = await creditBalance(schoolId, toStudentId);
    const [out, inn] = await db.$transaction([
      db.studentCredit.create({ data: { schoolId, studentId: fromStudentId, kind: "TRANSFER_OUT", amountCents: -amt, balanceAfter: balFrom - amt, note: note ?? null, byName: user.fullName } }),
      db.studentCredit.create({ data: { schoolId, studentId: toStudentId, kind: "TRANSFER_IN", amountCents: amt, balanceAfter: balTo + amt, note: note ?? null, byName: user.fullName } }),
    ]);
    return NextResponse.json({ out, in: inn });
  }

  // ── convert credit → daily-services prepaid wallet ──
  if (action === "credit-to-daily") {
    const { studentId, amountCents, note } = body;
    if (!studentId) return NextResponse.json({ error: "studentId required" }, { status: 400 });
    const amt = Math.round(Number(amountCents) || 0);
    if (!amt || amt <= 0) return NextResponse.json({ error: "Enter an amount above zero" }, { status: 400 });
    const student = await db.student.findUnique({ where: { id: studentId } });
    if (!student) return NextResponse.json({ error: "Learner not found" }, { status: 404 });
    const bal = await creditBalance(schoolId, studentId);
    if (bal < amt) return NextResponse.json({ error: `Insufficient credit — balance is GH₵${(bal / 100).toFixed(2)}` }, { status: 400 });
    const existing = await db.studentWallet.findUnique({ where: { studentId } });
    const result = await db.$transaction(async (tx) => {
      const credit = await tx.studentCredit.create({
        data: { schoolId, studentId, kind: "DAILY_WALLET", amountCents: -amt, balanceAfter: bal - amt, note: note ?? "Converted to the daily-services prepaid wallet", byName: user.fullName },
      });
      const w = existing
        ? await tx.studentWallet.update({ where: { studentId }, data: { prepaidCents: { increment: amt } } })
        : await tx.studentWallet.create({ data: { schoolId, studentId, prepaidCents: amt } });
      const txn = await tx.walletTxn.create({
        data: { schoolId, studentId, wallet: "PREPAID", kind: "TOPUP", amountCents: amt, balanceAfter: w.prepaidCents, note: note ?? "Converted from fee credit", byName: user.fullName },
      });
      return { credit, wallet: w, txn };
    });
    return NextResponse.json(result);
  }

  // ── student ledger: full statement of invoices (debits) & payments (credits) ──
  if (action === "ledger") {
    const { studentId, yearId } = body;
    const student = await db.student.findUnique({ where: { id: studentId }, include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: true } } } });
    if (!student) return NextResponse.json({ error: "Learner not found" }, { status: 404 });
    const year = yearId ? await db.academicYear.findFirst({ where: { schoolId, id: yearId } }) : await db.academicYear.findFirst({ where: { schoolId, isCurrent: true } });
    const invoices = await db.invoice.findMany({
      where: { studentId, schoolId, ...(year ? { term: { academicYearId: year.id } } : {}) },
      include: { items: true, term: { include: { academicYear: true } } },
      orderBy: { createdAt: "asc" },
    });
    const payments = await db.payment.findMany({
      where: { studentId, schoolId, voided: false, ...(year ? { paidAt: { gte: year.startsAt, lte: year.endsAt } } : {}) },
      orderBy: { paidAt: "asc" },
    });
    type Row = { date: string; kind: "DEBIT" | "CREDIT"; description: string; reference: string; amount: number; balance: number };
    const rows: Row[] = [];
    invoices.forEach((i) => {
      rows.push({
        date: i.createdAt.toISOString(), kind: "DEBIT",
        description: i.items.map((x) => x.name).join(", ") || "Term bill",
        reference: i.invoiceNo, amount: i.totalCents - i.discountCents, balance: 0,
      });
    });
    payments.forEach((p) => {
      rows.push({
        date: p.paidAt.toISOString(), kind: "CREDIT",
        description: `${p.category === "DAILY" ? "Daily services" : p.category === "POS" ? "Store purchase" : p.category === "TRANSPORT" ? "Transport fare" : "Fees payment"} · ${p.method}`,
        reference: p.receiptNo, amount: p.amountCents, balance: 0,
      });
    });
    rows.sort((a, b) => (a.date < b.date ? -1 : 1));
    let bal = 0;
    rows.forEach((r) => { bal += r.kind === "DEBIT" ? r.amount : -r.amount; r.balance = bal; });
    return NextResponse.json({
      student: { name: `${student.firstName} ${student.lastName}`, index: student.nyansapoIndex, classroom: student.enrollments[0]?.classroom?.name ?? null, guardian: student.guardianName, guardianPhone: student.guardianPhone },
      yearLabel: year?.name ?? "All time",
      rows,
      closing: bal,
    });
  }

  // ── terminal bills: arrears + next-term bill slips per student (printable) ──
  if (action === "terminal-bills") {
    const { classroomId, studentIds, feeCategory } = body; // feeCategory: ALL | INVOICE | DAILY
    const year = await db.academicYear.findFirst({ where: { schoolId, isCurrent: true }, include: { terms: true } });
    if (!year) return NextResponse.json({ error: "No academic year" }, { status: 400 });
    const term = year.terms.find((t) => t.isCurrent) ?? year.terms[year.terms.length - 1];
    if (!term) return NextResponse.json({ error: "No terms configured" }, { status: 400 });

    const where: any = { schoolId, termId: term.id };
    if (classroomId) {
      const enrollments = await db.enrollment.findMany({ where: { classroomId, status: "ACTIVE" }, select: { studentId: true } });
      const ids = enrollments.map((e) => e.studentId);
      where.studentId = { in: studentIds?.length ? ids.filter((i: string) => studentIds.includes(i)) : ids };
    } else if (studentIds?.length) {
      where.studentId = { in: studentIds };
    }
    const invoices = await db.invoice.findMany({
      where,
      include: { student: { include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: true } } } }, items: true, term: true, payments: true },
    });

    // daily arrears per student (all-time OWED)
    const dailyOwed = await db.dailyCharge.groupBy({
      by: ["studentId"],
      where: { schoolId, status: "OWED" },
      _sum: { amountCents: true },
    });
    const dailyByStudent: Record<string, number> = {};
    dailyOwed.forEach((d) => (dailyByStudent[d.studentId] = d._sum.amountCents ?? 0));

    // transport assignment fares for context
    const assignments = await db.transportAssignment.findMany({ where: { active: true }, include: { route: true } });
    const routeByStudent: Record<string, { route: string; fare: number }> = {};
    assignments.forEach((a) => (routeByStudent[a.studentId] = { route: a.route.name, fare: a.fareOverrideCents ?? a.route.farePerTermCents }));

    // prepaid credit balances (invoice-scope) offset the total due on the slip
    const creditAgg = await db.studentCredit.groupBy({ by: ["studentId"], where: { schoolId }, _sum: { amountCents: true } });
    const creditByStudent: Record<string, number> = {};
    creditAgg.forEach((c) => (creditByStudent[c.studentId] = c._sum.amountCents ?? 0));

    const bills = invoices.map((i) => {
      const bal = i.totalCents - i.discountCents - i.paidCents;
      const daily = feeCategory === "INVOICE" ? 0 : (dailyByStudent[i.studentId] ?? 0);
      const t = routeByStudent[i.studentId];
      return {
        student: { id: i.studentId, name: `${i.student.firstName} ${i.student.lastName}`, index: i.student.nyansapoIndex, guardian: i.student.guardianName, guardianPhone: i.student.guardianPhone },
        classroom: i.student.enrollments[0]?.classroom?.name ?? i.classroomName ?? null,
        invoiceNo: i.invoiceNo,
        termLabel: `${i.term.name} · ${year.name}`,
        items: feeCategory === "DAILY" ? [] : i.items.map((x) => ({ name: x.name, amount: x.amountCents })),
        discount: feeCategory === "DAILY" ? 0 : i.discountCents,
        discountReason: feeCategory === "DAILY" ? null : i.discountReason,
        paid: feeCategory === "DAILY" ? 0 : i.paidCents,
        balance: feeCategory === "DAILY" ? 0 : bal,
        dailyArrears: daily,
        transport: t ?? null,
        credit: Math.max(0, creditByStudent[i.studentId] ?? 0),
      };
    });
    return NextResponse.json({ termLabel: `${term.name} · ${year.name}`, bills });
  }

  // ── cashier reports (iedupro parity: dashboard / summary / collections) ──
  // Daily account rendering & reconciliation: collector + date + class + method
  // filters, service-category breakdown, method breakdown and per-cashier performance.
  if (action === "cashier-report") {
    const { collector, dateFrom, dateTo, classroomId, method } = body;
    const from = dateFrom ? new Date(dateFrom) : new Date(new Date().toDateString());
    const toDefault = new Date();
    toDefault.setHours(23, 59, 59, 999);
    const to = dateTo ? new Date(new Date(dateTo).getTime() + 864e5 - 1) : toDefault;

    let studentIds: string[] | null = null;
    if (classroomId) {
      const en = await db.enrollment.findMany({ where: { classroomId, status: "ACTIVE" }, select: { studentId: true } });
      studentIds = en.map((e) => e.studentId);
    }

    const payments = await db.payment.findMany({
      where: {
        schoolId, voided: false, paidAt: { gte: from, lte: to },
        ...(collector ? { receivedByName: collector } : {}),
        ...(method ? { method } : {}),
        ...(studentIds ? { studentId: { in: studentIds } } : {}),
      },
      include: {
        student: { include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: true } } } },
        dailyCharges: { include: { service: true } },
      },
      orderBy: { paidAt: "desc" },
    });

    // ── buckets: per-service (daily charges split), methods, collectors ──
    const byService: Record<string, number> = {};
    const byMethod: Record<string, number> = {};
    const byCollector: Record<string, { name: string; total: number; txns: number }> = {};
    const studentsServed = new Set<string>();
    let grand = 0;
    for (const p of payments) {
      grand += p.amountCents;
      byMethod[p.method] = (byMethod[p.method] ?? 0) + p.amountCents;
      const cname = p.receivedByName ?? "Unassigned";
      byCollector[cname] = byCollector[cname] ?? { name: cname, total: 0, txns: 0 };
      byCollector[cname].total += p.amountCents;
      byCollector[cname].txns += 1;
      if (p.studentId) studentsServed.add(p.studentId);
      // service split: daily charges carry their service; POS/TRANSPORT/INVOICE use their category label
      if (p.category === "DAILY" && p.dailyCharges.length) {
        for (const c of p.dailyCharges) byService[c.service.name] = (byService[c.service.name] ?? 0) + c.amountCents;
      } else {
        const label = p.category === "POS" ? "Store sales" : p.category === "TRANSPORT" ? "Transport fares" : "Termly fees";
        byService[label] = (byService[label] ?? 0) + p.amountCents;
      }
    }

    const transactions = payments.slice(0, 400).map((p) => {
      const split: Record<string, number> = {};
      if (p.category === "DAILY" && p.dailyCharges.length) {
        for (const c of p.dailyCharges) split[c.service.name] = (split[c.service.name] ?? 0) + c.amountCents;
      } else {
        split[p.category === "POS" ? "Store sales" : p.category === "TRANSPORT" ? "Transport fares" : "Termly fees"] = p.amountCents;
      }
      return {
        id: p.id, receiptNo: p.receiptNo, date: p.paidAt.toISOString(),
        student: p.student ? `${p.student.firstName} ${p.student.lastName}` : "—",
        classroom: p.student?.enrollments[0]?.classroom?.name ?? null,
        split, total: p.amountCents, method: p.method, collector: p.receivedByName ?? "Unassigned",
      };
    });

    // roster of cashiers for the filter dropdown (anyone who ever recorded a payment)
    const cashiersRaw = await db.payment.findMany({ where: { schoolId, receivedByName: { not: null } }, select: { receivedByName: true }, distinct: ["receivedByName"] });
    const classrooms = await db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" }, select: { id: true, name: true } });

    return NextResponse.json({
      period: { from: from.toISOString(), to: to.toISOString(), collector: collector ?? null },
      totals: { collected: grand, transactions: payments.length, studentsServed: studentsServed.size },
      byService: Object.entries(byService).map(([name, amount]) => ({ name, amount })).sort((a, b) => b.amount - a.amount),
      byMethod: Object.entries(byMethod).map(([name, amount]) => ({ name, amount })).sort((a, b) => b.amount - a.amount),
      collectors: Object.values(byCollector).sort((a, b) => b.total - a.total),
      transactions,
      cashiers: cashiersRaw.map((c) => c.receivedByName!).filter(Boolean).sort(),
      classrooms: classrooms.map((c) => ({ id: c.id, name: c.name })),
    });
  }

  // ── expenses CRUD ──
  if (action === "save-expense") {
    const { category, description, amountCents, method, expenseDate } = body;
    if (!description || !Number(amountCents)) return NextResponse.json({ error: "Description and amount are required" }, { status: 400 });
    const e = await db.expense.create({
      data: {
        schoolId, category: category ?? "OTHER", description,
        amountCents: Math.round(Number(amountCents)), method: method ?? "CASH",
        expenseDate: expenseDate ? new Date(expenseDate) : new Date(), recordedByName: user.fullName,
      },
    });
    return NextResponse.json({ expense: e });
  }

  if (action === "delete-expense") {
    await db.expense.delete({ where: { id: body.id } }).catch(() => null);
    return NextResponse.json({ ok: true });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

/* Credit balance for a learner = Σ signed StudentCredit.amountCents (one query).
 * Enforced everywhere: transfers / adjustments / daily-wallet conversions can
 * never take the balance below zero. */
async function creditBalance(schoolId: string, studentId: string): Promise<number> {
  const agg = await db.studentCredit.aggregate({ where: { schoolId, studentId }, _sum: { amountCents: true } });
  return agg._sum.amountCents ?? 0;
}

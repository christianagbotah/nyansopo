import { NextRequest, NextResponse } from "next/server";
import { sortClassrooms } from "@/lib/classes";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

/* ── GET /api/school/[id]/ops/finance — income dashboard, expenditure, aging,
   student credit wallets (prepaid credits from overpayment — CI3 parity) ──
   iedupro parity: Income & Revenue analytics, AR aging, expenditure dashboard.
   ?action= credits | credit-history | receivables | payables | income-expenditure
   (CI3 financial-statement parity); no action → the full dashboard payload. */
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

  /* ══════════════════ CI3 STATEMENTS — Task 5 (reports parity) ══════════════════
     Receivables · Payables · Income & Expenditure, mirroring
     scripts/ci3app …/views/backend/admin/reports/{get_receivables,get_payables,
     income_expenditure}.php. All money in integer pesewas (cents). */

  // ── receivables: per-student outstanding on open invoices, aged by due date ──
  // CI3 get_receivables.php lists the invoices owing per student; get_fct_
  // receivables.php rides the daily-fee arrears alongside. from/to & termId scope
  // which invoices count (CI3's "customize" period); aging is as of today.
  if (action === "receivables") {
    const auth = await opsAuth(req, schoolId, "GET", { perms: ["finance.view"] });
    if (!auth.ok) return auth.res;

    const from = q("from");
    const to = q("to");
    const termId = q("termId");
    const created = dayRange(from, to);

    const [invoices, dailyOwed, classrooms, year] = await Promise.all([
      db.invoice.findMany({
        where: {
          schoolId, writtenOff: false, status: { in: ["UNPAID", "PARTIAL", "OVERDUE"] },
          ...(termId ? { termId } : {}),
          ...(created ? { createdAt: created } : {}),
        },
        include: { student: { include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: { select: { name: true, gradeLevel: true } } } } } } },
      }),
      db.dailyCharge.groupBy({ by: ["studentId"], where: { schoolId, status: "OWED" }, _sum: { amountCents: true } }),
      db.classroom.findMany({ where: { schoolId }, select: { name: true, gradeLevel: true } }),
      db.academicYear.findFirst({ where: { schoolId, isCurrent: true }, include: { terms: { orderBy: { sequence: "asc" } } } }),
    ]);

    const dailyByStudent = new Map(dailyOwed.map((d) => [d.studentId, d._sum.amountCents ?? 0]));
    // school class order (KG → B9) so statements follow the register, not the alphabet
    const classOrder = new Map(sortClassrooms(classrooms).map((c, i) => [c.name, i]));
    const rankOf = (name: string | null) => classOrder.get(name ?? "") ?? classrooms.length + 1;

    const nowMs = Date.now();
    type Aging = { currentCents: number; d1Cents: number; d31Cents: number; d61Cents: number; d90Cents: number };
    const AGING_KEYS = ["currentCents", "d1Cents", "d31Cents", "d61Cents", "d90Cents"] as const;
    const zeroAging = (): Aging => ({ currentCents: 0, d1Cents: 0, d31Cents: 0, d61Cents: 0, d90Cents: 0 });
    type StuRow = {
      studentId: string; name: string; classroom: string | null; rank: number; invoices: number;
      billedCents: number; discountCents: number; paidCents: number; outstandingCents: number; dailyArrearsCents: number; aging: Aging;
    };
    const byStudent = new Map<string, StuRow>();
    for (const inv of invoices) {
      const bal = inv.totalCents - inv.discountCents - inv.paidCents;
      if (bal <= 0) continue; // settled / overpaid rows are not receivables
      const classroom = inv.student.enrollments[0]?.classroom?.name ?? inv.classroomName ?? null;
      let row = byStudent.get(inv.studentId);
      if (!row) {
        row = {
          studentId: inv.studentId, name: `${inv.student.firstName} ${inv.student.lastName}`, classroom, rank: rankOf(classroom),
          invoices: 0, billedCents: 0, discountCents: 0, paidCents: 0, outstandingCents: 0,
          dailyArrearsCents: dailyByStudent.get(inv.studentId) ?? 0, aging: zeroAging(),
        };
        byStudent.set(inv.studentId, row);
      }
      row.invoices += 1;
      row.billedCents += inv.totalCents;
      row.discountCents += inv.discountCents;
      row.paidCents += inv.paidCents;
      row.outstandingCents += bal;
      const days = inv.dueDate ? Math.floor((nowMs - inv.dueDate.getTime()) / 864e5) : 0;
      const k = days < 1 ? "currentCents" : days <= 30 ? "d1Cents" : days <= 60 ? "d31Cents" : days <= 90 ? "d61Cents" : "d90Cents";
      row.aging[k] += bal;
    }

    const rows = [...byStudent.values()]
      .sort((a, b) => a.rank - b.rank || (a.classroom ?? "").localeCompare(b.classroom ?? "") || b.outstandingCents - a.outstandingCents || a.name.localeCompare(b.name))
      .map(({ rank, ...r }) => r);

    // per-class sub-totals (bold rows in the statement) + grand total
    type ClassAgg = { classroom: string; students: number; invoices: number; billedCents: number; paidCents: number; outstandingCents: number; dailyArrearsCents: number; aging: Aging };
    const classAgg = new Map<string, ClassAgg>();
    const totals = { students: 0, invoices: 0, billedCents: 0, paidCents: 0, discountCents: 0, outstandingCents: 0, dailyArrearsCents: 0, aging: zeroAging() };
    for (const r of rows) {
      const cls = r.classroom ?? "Unassigned";
      const g = classAgg.get(cls) ?? { classroom: cls, students: 0, invoices: 0, billedCents: 0, paidCents: 0, outstandingCents: 0, dailyArrearsCents: 0, aging: zeroAging() };
      g.students += 1;
      g.invoices += r.invoices;
      g.billedCents += r.billedCents;
      g.paidCents += r.paidCents;
      g.outstandingCents += r.outstandingCents;
      g.dailyArrearsCents += r.dailyArrearsCents;
      for (const k of AGING_KEYS) g.aging[k] += r.aging[k];
      classAgg.set(cls, g);
      totals.students += 1;
      totals.invoices += r.invoices;
      totals.billedCents += r.billedCents;
      totals.discountCents += r.discountCents;
      totals.paidCents += r.paidCents;
      totals.outstandingCents += r.outstandingCents;
      totals.dailyArrearsCents += r.dailyArrearsCents;
      for (const k of AGING_KEYS) totals.aging[k] += r.aging[k];
    }

    // daily-fee arrears that belongs to learners with no open invoice (CI3 keeps
    // those in the separate fct report) — surfaced so nothing is hidden
    const dailyOnly = { students: 0, amountCents: 0 };
    for (const [sid, amt] of dailyByStudent) {
      if (!byStudent.has(sid)) { dailyOnly.students += 1; dailyOnly.amountCents += amt; }
    }

    return NextResponse.json({
      period: { from: from ?? null, to: to ?? null, termId: termId ?? null },
      terms: year?.terms.map((t) => ({ id: t.id, label: t.name, isCurrent: t.isCurrent })) ?? [],
      rows,
      classes: [...classAgg.values()],
      totals,
      dailyOnly,
    });
  }

  // ── payables: everything the school OWES right now ──
  // CI3 get_payables.php/get_fct_payables.php tracked advance payments (negative
  // invoice dues and wallet balances) the school owes parents — in Nyansapo those
  // live in StudentCredit wallets. The statement adds the obligations CI3 never
  // modelled: pending supplier purchase orders and approved-but-unpaid payroll.
  if (action === "payables") {
    const auth = await opsAuth(req, schoolId, "GET", { perms: ["finance.view"] });
    if (!auth.ok) return auth.res;

    const from = q("from");
    const to = q("to");
    const range = dayRange(from, to);

    const [purchaseOrders, payrollRuns, creditAgg] = await Promise.all([
      // PENDING = ordered, not yet received/settled; window on expected delivery
      db.purchaseOrder.findMany({
        where: { schoolId, status: "PENDING", ...(range ? { OR: [{ expectedDate: range }, { AND: [{ expectedDate: null }, { createdAt: range }] }] } : {}) },
        include: { supplier: true },
        orderBy: [{ expectedDate: "asc" }, { poNo: "asc" }],
      }),
      // APPROVED and awaiting cash — DRAFT / PENDING_APPROVAL / REJECTED / PAID excluded
      db.payrollRun.findMany({ where: { schoolId, status: "APPROVED", ...(range ? { createdAt: range } : {}) }, orderBy: { period: "asc" } }),
      db.studentCredit.groupBy({ by: ["studentId"], where: { schoolId }, _sum: { amountCents: true } }),
    ]);

    const poRows = purchaseOrders.map((po) => {
      let lines = 0;
      try { lines = (JSON.parse(po.linesJson ?? "[]") as unknown[]).length; } catch { lines = 0; }
      return {
        poNo: po.poNo, supplier: po.supplier?.name ?? "Unassigned supplier", supplierPhone: po.supplier?.phone ?? null,
        expectedDate: po.expectedDate?.toISOString() ?? null, createdAt: po.createdAt.toISOString(),
        lines, totalCents: po.totalCents, note: po.note,
      };
    });
    const bySupplier = new Map<string, { supplier: string; phone: string | null; orders: number; totalCents: number }>();
    for (const r of poRows) {
      const g = bySupplier.get(r.supplier) ?? { supplier: r.supplier, phone: r.supplierPhone, orders: 0, totalCents: 0 };
      g.orders += 1;
      g.totalCents += r.totalCents;
      bySupplier.set(r.supplier, g);
    }

    const payrollRows = payrollRuns.map((run) => ({
      id: run.id, period: run.period, status: run.status,
      totalGrossCents: run.totalGrossCents, totalDeductionsCents: run.totalDeductionsCents, totalNetCents: run.totalNetCents,
      approvedByName: run.approvedByName, createdAt: run.createdAt.toISOString(),
    }));

    // advance payments owed to parents — live credit balances (CI3 wallet parity)
    const creditRows = creditAgg
      .filter((c) => (c._sum.amountCents ?? 0) > 0)
      .map((c) => ({ studentId: c.studentId, balanceCents: c._sum.amountCents ?? 0 }));
    const creditStudents = creditRows.length
      ? await db.student.findMany({ where: { id: { in: creditRows.map((r) => r.studentId) } }, include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: { select: { name: true } } } } } })
      : [];
    const studentById = new Map(creditStudents.map((s) => [s.id, s]));
    const credits = creditRows
      .map((r) => {
        const s = studentById.get(r.studentId);
        return {
          studentId: r.studentId,
          name: s ? `${s.firstName} ${s.lastName}` : "Unknown learner",
          classroom: s?.enrollments[0]?.classroom?.name ?? null,
          balanceCents: r.balanceCents,
        };
      })
      .sort((a, b) => b.balanceCents - a.balanceCents || a.name.localeCompare(b.name));

    const purchaseOrdersCents = poRows.reduce((s, r) => s + r.totalCents, 0);
    const payrollCents = payrollRows.reduce((s, r) => s + r.totalNetCents, 0);
    const creditsCents = credits.reduce((s, r) => s + r.balanceCents, 0);

    return NextResponse.json({
      period: { from: from ?? null, to: to ?? null },
      purchaseOrders: { rows: poRows, suppliers: [...bySupplier.values()].sort((a, b) => b.totalCents - a.totalCents), totalCents: purchaseOrdersCents },
      payroll: { rows: payrollRows, totalCents: payrollCents },
      credits: { rows: credits, totalCents: creditsCents },
      totals: {
        purchaseOrdersCents, purchaseOrdersCount: poRows.length,
        payrollCents, payrollCount: payrollRows.length,
        creditsCents, creditsCount: credits.length,
        totalCents: purchaseOrdersCents + payrollCents + creditsCents,
      },
    });
  }

  // ── income & expenditure account (CI3 income_expenditure.php layout) ──
  // INCOME: billed-invoice receipts (method sub-totals) · daily services
  // collection per service (CI3's feeding/breakfast/classes/water/transport
  // lines) · transport fares · store sales · other income (unattached receipts).
  // EXPENDITURE: the bursar's expense book by category · received purchase
  // orders (inventory cash out) · payroll paid (net salaries).
  // Footer: SURPLUS/(DEFICIT) = income − expenditure.
  if (action === "income-expenditure") {
    const auth = await opsAuth(req, schoolId, "GET", { perms: ["finance.view"] });
    if (!auth.ok) return auth.res;

    const now = new Date();
    const from = q("from") ?? new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), 1)).toISOString().slice(0, 10);
    const to = q("to") ?? now.toISOString().slice(0, 10);
    const range = dayRange(from, to)!;

    const [payments, expenses, receivedOrders, paidRuns] = await Promise.all([
      db.payment.findMany({ where: { schoolId, voided: false, paymentType: "PAYMENT", paidAt: range }, include: { dailyCharges: { include: { service: { select: { name: true } } } } } }),
      db.expense.findMany({ where: { schoolId, expenseDate: range } }),
      db.purchaseOrder.findMany({ where: { schoolId, status: "RECEIVED", receivedAt: range }, include: { supplier: true }, orderBy: { receivedAt: "asc" } }),
      db.payrollRun.findMany({ where: { schoolId, status: "PAID", paidAt: range }, orderBy: { period: "asc" } }),
    ]);

    // ── income side ──
    const invMethods = new Map<string, { count: number; amountCents: number }>();
    const otherMethods = new Map<string, { count: number; amountCents: number }>();
    const dailyServices = new Map<string, number>();
    let invoicesCents = 0, dailyCents = 0, transportCents = 0, posCents = 0, otherCents = 0, transportCount = 0, posCount = 0;
    for (const p of payments) {
      if (p.category === "DAILY") {
        // per-service split from the linked charges; wallet top-up remainder kept apart
        let allocated = 0;
        for (const c of p.dailyCharges) {
          dailyServices.set(c.service.name, (dailyServices.get(c.service.name) ?? 0) + c.amountCents);
          allocated += c.amountCents;
        }
        const rest = p.amountCents - allocated;
        if (rest > 0) dailyServices.set("Wallet top-ups & unallocated", (dailyServices.get("Wallet top-ups & unallocated") ?? 0) + rest);
        dailyCents += p.amountCents;
      } else if (p.invoiceId) {
        // CI3 billed-invoice income — receipts tied to an invoice, split by method
        const m = invMethods.get(p.method) ?? { count: 0, amountCents: 0 };
        m.count += 1;
        m.amountCents += p.amountCents;
        invMethods.set(p.method, m);
        invoicesCents += p.amountCents;
      } else if (p.category === "POS") {
        posCents += p.amountCents;
        posCount += 1;
      } else if (p.category === "TRANSPORT") {
        transportCents += p.amountCents;
        transportCount += 1;
      } else {
        // CI3 "other income": money in that is not tied to any invoice
        const m = otherMethods.get(p.method) ?? { count: 0, amountCents: 0 };
        m.count += 1;
        m.amountCents += p.amountCents;
        otherMethods.set(p.method, m);
        otherCents += p.amountCents;
      }
    }

    // ── expenditure side ──
    const expenseCats = new Map<string, { count: number; amountCents: number }>();
    for (const e of expenses) {
      const g = expenseCats.get(e.category) ?? { count: 0, amountCents: 0 };
      g.count += 1;
      g.amountCents += e.amountCents;
      expenseCats.set(e.category, g);
    }
    const expensesCents = expenses.reduce((s, e) => s + e.amountCents, 0);
    const purchaseOrdersCents = receivedOrders.reduce((s, po) => s + po.totalCents, 0);
    const payrollCents = paidRuns.reduce((s, r) => s + r.totalNetCents, 0);

    const incomeTotal = invoicesCents + dailyCents + transportCents + posCents + otherCents;
    const expenditureTotal = expensesCents + purchaseOrdersCents + payrollCents;

    type IeSection = { key: string; label: string; rows: { label: string; count: number; amountCents: number }[]; subtotalCents: number };
    const methodRows = (m: Map<string, { count: number; amountCents: number }>) =>
      [...m.entries()].map(([label, v]) => ({ label, count: v.count, amountCents: v.amountCents })).sort((a, b) => b.amountCents - a.amountCents);
    const sections: IeSection[] = [
      { key: "invoices", label: "Billed invoices (school fees, PTA…)", rows: methodRows(invMethods), subtotalCents: invoicesCents },
      { key: "daily", label: "Daily services collection", rows: [...dailyServices.entries()].map(([label, amountCents]) => ({ label, count: 0, amountCents })).sort((a, b) => b.amountCents - a.amountCents), subtotalCents: dailyCents },
    ];
    if (transportCents > 0) sections.push({ key: "transport", label: "Transport fares", rows: [{ label: "Fare payments", count: transportCount, amountCents: transportCents }], subtotalCents: transportCents });
    if (posCents > 0) sections.push({ key: "pos", label: "Store sales (inventory)", rows: [{ label: "Products / items sold", count: posCount, amountCents: posCents }], subtotalCents: posCents });
    if (otherCents > 0) sections.push({ key: "other", label: "Other income", rows: methodRows(otherMethods), subtotalCents: otherCents });

    return NextResponse.json({
      period: { from, to },
      income: { sections, totalCents: incomeTotal },
      expenditure: {
        categories: [...expenseCats.entries()].map(([category, v]) => ({ category, count: v.count, amountCents: v.amountCents })).sort((a, b) => b.amountCents - a.amountCents),
        expensesCents,
        purchaseOrders: {
          rows: receivedOrders.map((po) => ({ poNo: po.poNo, supplier: po.supplier?.name ?? "Unassigned supplier", receivedAt: po.receivedAt?.toISOString() ?? null, totalCents: po.totalCents })),
          totalCents: purchaseOrdersCents,
        },
        payroll: {
          rows: paidRuns.map((r) => ({ period: r.period, paidAt: r.paidAt?.toISOString() ?? null, totalNetCents: r.totalNetCents })),
          totalCents: payrollCents,
        },
        totalCents: expenditureTotal,
      },
      surplusCents: incomeTotal - expenditureTotal,
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
    const classrooms =sortClassrooms(await db.classroom.findMany({ where: { schoolId }, select: {  id: true, name: true, gradeLevel: true } }));

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

/* Day-window helper for the CI3 statement actions: YYYY-MM-DD search params →
 * a Prisma DateTime filter anchored at UTC midnight (matches ops/reports). */
function dayRange(from?: string, to?: string): { gte?: Date; lte?: Date } | undefined {
  if (!from && !to) return undefined;
  return {
    ...(from ? { gte: new Date(`${from}T00:00:00.000Z`) } : {}),
    ...(to ? { lte: new Date(`${to}T23:59:59.999Z`) } : {}),
  };
}

import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

/* ─────────────────────────────────────────────────────────────
   GET  /api/school/[id]/ops/accounts — double-entry ledger payload
   POST /api/school/[id]/ops/accounts — action dispatch
   CI3 parity: Accounts.php + Accounts_model.php + Bank_reconciliation.php.

   BALANCE SEMANTICS (documented once, used everywhere):
   • Accounts are debit-normal (ASSET, EXPENSE) or credit-normal
     (LIABILITY, EQUITY, REVENUE).
   • balanceCents = Σdebits − Σcredits (debit-normal) / Σcredits − Σdebits
     (credit-normal), computed LIVE from POSTED journal lines only —
     no cached balance column, so post/void can never drift.
   • DRAFT lines never affect balances; VOIDED entries stay visible but
     are inert — excluded from every balance & report (CI3 parity: void
     leaves the entry in the journal but reverses its effect; because we
     compute live, "reversal" is simply excluding the status).
   • Dates are UTC day bounds ("YYYY-MM-DD" → T00:00:00.000Z .. +24h−1ms).
   ───────────────────────────────────────────────────────────── */

const DAY_MS = 86_400_000;
const ACCT_TYPES = ["ASSET", "LIABILITY", "EQUITY", "REVENUE", "EXPENSE"] as const;
const DEBIT_NORMAL = new Set(["ASSET", "EXPENSE"]);
const SOURCES = ["MANUAL", "PAYMENT", "EXPENSE", "FEES"] as const;
const DIRECTIONS = ["DEPOSIT", "WITHDRAWAL"] as const;

const parseDay = (s: string | null | undefined): Date | null => {
  if (!s) return null;
  const d = new Date(s.length === 10 ? `${s}T00:00:00.000Z` : s);
  return Number.isNaN(d.getTime()) ? null : d;
};
const endOfDay = (d: Date) => new Date(d.getTime() + DAY_MS - 1);
const utcDay = (d: Date) => Math.floor(d.getTime() / DAY_MS);
const dayDiff = (a: Date, b: Date) => Math.abs(utcDay(a) - utcDay(b));
const iso = (d: Date) => d.toISOString().slice(0, 10);
const fmtGhs = (cents: number) => `GH₵${(cents / 100).toLocaleString("en-GH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

type Act = { debit: number; credit: number };
type Activity = Map<string, Act>;

/** Posted-line movement per account, optionally date-bounded (Prisma entryDate range). */
async function postedActivity(schoolId: string, range?: { gte?: Date; lte?: Date; lt?: Date }): Promise<Activity> {
  const lines = await db.journalEntryLine.findMany({
    where: { account: { schoolId }, entry: { schoolId, status: "POSTED", ...(range ? { entryDate: range } : {}) } },
    select: { accountId: true, debitCents: true, creditCents: true },
  });
  const map: Activity = new Map();
  for (const l of lines) {
    const cur = map.get(l.accountId) ?? { debit: 0, credit: 0 };
    cur.debit += l.debitCents;
    cur.credit += l.creditCents;
    map.set(l.accountId, cur);
  }
  return map;
}

/** Natural-side balance from movement totals. */
const balanceOf = (type: string, a?: Act) => {
  const m = a ?? { debit: 0, credit: 0 };
  return DEBIT_NORMAL.has(type) ? m.debit - m.credit : m.credit - m.debit;
};

/** Amount in cents must be a whole number ≥ 0 (for debit/credit columns). */
const isCents = (v: unknown): v is number => typeof v === "number" && Number.isInteger(v) && v >= 0;

const csvEscape = (v: string | number | null | undefined) => {
  const s = v == null ? "" : String(v);
  return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
};
const csvResponse = (filename: string, header: (string | number)[], rows: (string | number | null | undefined)[][]) =>
  new NextResponse([header, ...rows].map((r) => r.map(csvEscape).join(",")).join("\n"), {
    headers: {
      "content-type": "text/csv; charset=utf-8",
      "content-disposition": `attachment; filename="${filename}"`,
    },
  });

/* ══════════════════════════════════════════════════════════════
   GET — module payload, per-bank txns, recon workspace, reports
   ══════════════════════════════════════════════════════════════ */
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const url = new URL(req.url);
  const q = (k: string) => url.searchParams.get(k) ?? undefined;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["accounts.view"] });
  if (!auth.ok) return auth.res;
  const now = new Date();
  const monthStart = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), 1));

  /* ── reconciliation workspace detail ── */
  if (q("action") === "recon" && q("id")) {
    const recon = await db.reconciliation.findFirst({
      where: { id: q("id")!, schoolId },
      include: {
        bankAccount: { select: { id: true, name: true, bankName: true, openingBalanceCents: true, accountNumber: true } },
        items: { orderBy: { date: "asc" }, include: { bankTxn: true } },
      },
    });
    if (!recon) return NextResponse.json({ error: "Reconciliation not found" }, { status: 404 });
    // system-computed expected balance at statement date: opening + signed txns ≤ statementDate
    const stmtEnd = endOfDay(recon.statementDate);
    const txns = await db.bankTxn.findMany({ where: { bankAccountId: recon.bankAccountId }, select: { date: true, direction: true, amountCents: true } });
    const expectedCents =
      recon.bankAccount.openingBalanceCents +
      txns.filter((t) => t.date.getTime() <= stmtEnd.getTime()).reduce((s, t) => s + (t.direction === "DEPOSIT" ? t.amountCents : -t.amountCents), 0);
    // system txns: unreconciled feed; ones already linked to an item of THIS
    // reconciliation carry inUse so the workspace can badge them
    const used = new Set(recon.items.map((i) => i.bankTxnId).filter(Boolean) as string[]);
    const systemTxns = (
      await db.bankTxn.findMany({ where: { bankAccountId: recon.bankAccountId, reconciledAt: null }, orderBy: { date: "asc" } })
    ).map((t) => ({ id: t.id, date: t.date, direction: t.direction, amountCents: t.amountCents, reference: t.reference, description: t.description, source: t.source, inUse: used.has(t.id) }));
    return NextResponse.json({
      reconciliation: {
        id: recon.id,
        bankAccountId: recon.bankAccountId,
        bankAccountName: recon.bankAccount.name,
        statementDate: recon.statementDate,
        statementEndCents: recon.statementEndCents,
        status: recon.status,
        createdByName: recon.createdByName,
        completedAt: recon.completedAt,
        items: recon.items.map((i) => ({
          id: i.id,
          date: i.date,
          description: i.description,
          amountCents: i.amountCents,
          matched: i.matched,
          bankTxnId: i.bankTxnId,
          bankTxn: i.bankTxn ? { id: i.bankTxn.id, date: i.bankTxn.date, direction: i.bankTxn.direction, amountCents: i.bankTxn.amountCents, description: i.bankTxn.description, reference: i.bankTxn.reference } : null,
        })),
      },
      systemTxns,
      expectedCents,
      differenceCents: recon.statementEndCents - expectedCents,
    });
  }

  /* ── reports (?report=trial-balance|balance-sheet|income-statement|general-ledger) ── */
  const report = q("report");
  if (report) {
    const asOf = parseDay(q("asOf")) ?? now;
    const from = parseDay(q("from")) ?? monthStart;
    const to = parseDay(q("to")) ? endOfDay(parseDay(q("to"))!) : endOfDay(now);
    const asOfEnd = endOfDay(asOf);
    const accounts = await db.chartAccount.findMany({ where: { schoolId }, orderBy: { code: "asc" } });
    const acct = new Map(accounts.map((a) => [a.id, a]));

    if (report === "trial-balance") {
      // classic trial balance: the net balance sits in the account's natural column
      // (debit-normal → debit column when positive); grand totals prove ΣDr = ΣCr
      const act = await postedActivity(schoolId, { lte: asOfEnd });
      const rows = accounts
        .filter((a) => a.isActive && ((act.get(a.id)?.debit ?? 0) > 0 || (act.get(a.id)?.credit ?? 0) > 0))
        .map((a) => {
          const bal = balanceOf(a.type, act.get(a.id));
          const m = act.get(a.id) ?? { debit: 0, credit: 0 };
          return {
            id: a.id, code: a.code, name: a.name, type: a.type,
            debitCents: DEBIT_NORMAL.has(a.type) ? Math.max(bal, 0) : Math.max(-bal, 0),
            creditCents: DEBIT_NORMAL.has(a.type) ? Math.max(-bal, 0) : Math.max(bal, 0),
            movementDebitCents: m.debit, movementCreditCents: m.credit,
          };
        });
      const totalDebitCents = rows.reduce((s, r) => s + r.debitCents, 0);
      const totalCreditCents = rows.reduce((s, r) => s + r.creditCents, 0);
      if (q("format") === "csv")
        return csvResponse(`trial-balance-${iso(asOf)}.csv`, ["Code", "Account", "Type", "Debit (GHS)", "Credit (GHS)"],
          rows.map((r) => [r.code, r.name, r.type, (r.debitCents / 100).toFixed(2), (r.creditCents / 100).toFixed(2)])
            .concat([["", "TOTAL", "", (totalDebitCents / 100).toFixed(2), (totalCreditCents / 100).toFixed(2)]]));
      return NextResponse.json({ kind: "trial-balance", asOf: asOf.toISOString(), rows, totalDebitCents, totalCreditCents, balanced: totalDebitCents === totalCreditCents });
    }

    if (report === "balance-sheet") {
      const act = await postedActivity(schoolId, { lte: asOfEnd });
      const section = (type: string) => accounts.filter((a) => a.isActive && a.type === type).map((a) => ({ id: a.id, code: a.code, name: a.name, balanceCents: balanceOf(a.type, act.get(a.id)) }));
      const assets = section("ASSET");
      const liabilities = section("LIABILITY");
      const equity = section("EQUITY");
      // net income = revenue − expenses from ALL posted lines ≤ asOf (fiscal years are a
      // deliberate scope cut — a "current year" window would unbalance the sheet without them)
      const netIncomeCents = accounts.filter((a) => a.type === "REVENUE" || a.type === "EXPENSE").reduce((s, a) => s + balanceOf(a.type, act.get(a.id)) * (a.type === "REVENUE" ? 1 : -1), 0);
      const totalAssetsCents = assets.reduce((s, a) => s + a.balanceCents, 0);
      const totalLiabilitiesCents = liabilities.reduce((s, a) => s + a.balanceCents, 0);
      const totalEquityCents = equity.reduce((s, a) => s + a.balanceCents, 0);
      const checkCents = totalAssetsCents - totalLiabilitiesCents - totalEquityCents - netIncomeCents;
      if (q("format") === "csv")
        return csvResponse(`balance-sheet-${iso(asOf)}.csv`, ["Section", "Code", "Account", "Amount (GHS)"], [
          ...assets.map((a) => ["ASSETS", a.code, a.name, (a.balanceCents / 100).toFixed(2)]),
          ["ASSETS", "", "TOTAL ASSETS", (totalAssetsCents / 100).toFixed(2)],
          ...liabilities.map((a) => ["LIABILITIES", a.code, a.name, (a.balanceCents / 100).toFixed(2)]),
          ["LIABILITIES", "", "TOTAL LIABILITIES", (totalLiabilitiesCents / 100).toFixed(2)],
          ...equity.map((a) => ["EQUITY", a.code, a.name, (a.balanceCents / 100).toFixed(2)]),
          ["EQUITY", "", "Retained earnings (to date)", (netIncomeCents / 100).toFixed(2)],
          ["EQUITY", "", "TOTAL EQUITY", ((totalEquityCents + netIncomeCents) / 100).toFixed(2)],
        ]);
      return NextResponse.json({ kind: "balance-sheet", asOf: asOf.toISOString(), assets, liabilities, equity, totalAssetsCents, totalLiabilitiesCents, totalEquityCents, netIncomeCents, checkCents, balanced: checkCents === 0 });
    }

    if (report === "income-statement") {
      const act = await postedActivity(schoolId, { gte: from, lte: to });
      const rev = accounts.filter((a) => a.isActive && a.type === "REVENUE" && balanceOf(a.type, act.get(a.id)) !== 0)
        .map((a) => ({ id: a.id, code: a.code, name: a.name, amountCents: balanceOf(a.type, act.get(a.id)) }));
      const exp = accounts.filter((a) => a.isActive && a.type === "EXPENSE" && balanceOf(a.type, act.get(a.id)) !== 0)
        .map((a) => ({ id: a.id, code: a.code, name: a.name, amountCents: balanceOf(a.type, act.get(a.id)) }));
      const totalRevenueCents = rev.reduce((s, r) => s + r.amountCents, 0);
      const totalExpenseCents = exp.reduce((s, r) => s + r.amountCents, 0);
      if (q("format") === "csv")
        return csvResponse(`income-statement-${iso(from)}-to-${iso(to)}.csv`, ["Section", "Code", "Account", "Amount (GHS)"], [
          ...rev.map((r) => ["REVENUE", r.code, r.name, (r.amountCents / 100).toFixed(2)]),
          ["REVENUE", "", "TOTAL REVENUE", (totalRevenueCents / 100).toFixed(2)],
          ...exp.map((r) => ["EXPENSES", r.code, r.name, (r.amountCents / 100).toFixed(2)]),
          ["EXPENSES", "", "TOTAL EXPENSES", (totalExpenseCents / 100).toFixed(2)],
          ["", "", "SURPLUS/(DEFICIT)", ((totalRevenueCents - totalExpenseCents) / 100).toFixed(2)],
        ]);
      return NextResponse.json({ kind: "income-statement", from: from.toISOString(), to: to.toISOString(), revenue: rev, expenses: exp, totalRevenueCents, totalExpenseCents, surplusCents: totalRevenueCents - totalExpenseCents });
    }

    if (report === "general-ledger") {
      const accountId = q("accountId");
      const account = accountId ? acct.get(accountId) : undefined;
      if (!account) return NextResponse.json({ error: "accountId is required and must be one of this school's accounts" }, { status: 400 });
      const before = await postedActivity(schoolId, { lt: from });
      const openingCents = balanceOf(account.type, before.get(account.id));
      const lines = await db.journalEntryLine.findMany({
        where: { accountId: account.id, entry: { schoolId, status: "POSTED", entryDate: { gte: from, lte: to } } },
        orderBy: [{ entry: { entryDate: "asc" } }, { entry: { createdAt: "asc" } }],
        include: { entry: { select: { entryNumber: true, entryDate: true, description: true } } },
      });
      let running = openingCents;
      const rows = lines.map((l) => {
        running += DEBIT_NORMAL.has(account.type) ? l.debitCents - l.creditCents : l.creditCents - l.debitCents;
        return {
          date: l.entry.entryDate, entryNumber: l.entry.entryNumber, description: l.entry.description,
          memo: l.memo, debitCents: l.debitCents, creditCents: l.creditCents, runningCents: running,
        };
      });
      if (q("format") === "csv")
        return csvResponse(`general-ledger-${account.code}-${iso(from)}-to-${iso(to)}.csv`,
          ["Date", "Entry", "Description", "Memo", "Debit (GHS)", "Credit (GHS)", "Balance (GHS)"],
          [
            ["", "", "Opening balance", "", "", "", (openingCents / 100).toFixed(2)],
            ...rows.map((r) => [iso(r.date), r.entryNumber, r.description, r.memo ?? "", (r.debitCents / 100).toFixed(2), (r.creditCents / 100).toFixed(2), (r.runningCents / 100).toFixed(2)]),
            ["", "", "Closing balance", "", "", "", (running / 100).toFixed(2)],
          ]);
      return NextResponse.json({
        kind: "general-ledger",
        account: { id: account.id, code: account.code, name: account.name, type: account.type },
        from: from.toISOString(), to: to.toISOString(),
        openingCents, lines: rows, closingCents: running, debitNormal: DEBIT_NORMAL.has(account.type),
      });
    }

    return NextResponse.json({ error: "Unknown report — use trial-balance, balance-sheet, income-statement or general-ledger" }, { status: 400 });
  }

  /* ── default: the full module payload (optionally + one bank account's txn feed) ── */
  const [school, accounts, entries, bankAccounts, recons, journalStatus, unmatchedTxnCount] = await Promise.all([
    db.school.findUnique({ where: { id: schoolId }, select: { name: true } }),
    db.chartAccount.findMany({ where: { schoolId }, orderBy: { code: "asc" } }),
    db.journalEntry.findMany({
      where: { schoolId },
      orderBy: [{ entryDate: "desc" }, { createdAt: "desc" }],
      take: 50,
      include: { lines: { orderBy: { id: "asc" }, include: { account: { select: { id: true, code: true, name: true, type: true } } } } },
    }),
    db.bankAccount.findMany({
      where: { schoolId },
      orderBy: { name: "asc" },
      include: { chartAccount: { select: { id: true, code: true, name: true, isBank: true } }, _count: { select: { txns: true, reconciliations: true } } },
    }),
    db.reconciliation.findMany({
      where: { schoolId },
      orderBy: { createdAt: "desc" },
      take: 20,
      include: { bankAccount: { select: { id: true, name: true } }, items: { select: { id: true, matched: true } } },
    }),
    db.journalEntry.groupBy({ by: ["status"], where: { schoolId }, _count: { _all: true } }),
    db.bankTxn.count({ where: { schoolId, reconciledAt: null } }),
  ]);

  const [activity, mtd, txnAgg] = await Promise.all([
    postedActivity(schoolId),
    postedActivity(schoolId, { gte: monthStart }),
    db.bankTxn.groupBy({ by: ["bankAccountId", "direction"], where: { schoolId }, _sum: { amountCents: true } }),
  ]);
  const txnSum = new Map<string, number>();
  for (const g of txnAgg) txnSum.set(g.bankAccountId, (txnSum.get(g.bankAccountId) ?? 0) + (g.direction === "DEPOSIT" ? g._sum.amountCents ?? 0 : -(g._sum.amountCents ?? 0)));

  const accountsPayload = accounts.map((a) => ({ ...a, balanceCents: balanceOf(a.type, activity.get(a.id)) }));
  const revMtd = accounts.filter((a) => a.type === "REVENUE").reduce((s, a) => s + balanceOf(a.type, mtd.get(a.id)), 0);
  const expMtd = accounts.filter((a) => a.type === "EXPENSE").reduce((s, a) => s + balanceOf(a.type, mtd.get(a.id)), 0);

  const bankAccountsPayload = bankAccounts.map((b) => ({
    id: b.id, name: b.name, bankName: b.bankName, accountNumber: b.accountNumber,
    chartAccountId: b.chartAccountId, chartAccount: b.chartAccount,
    openingBalanceCents: b.openingBalanceCents, isActive: b.isActive,
    txnCount: b._count.txns, reconCount: b._count.reconciliations,
    currentBalanceCents: b.openingBalanceCents + (txnSum.get(b.id) ?? 0),
    openReconId: recons.find((r) => r.bankAccountId === b.id && r.status === "IN_PROGRESS")?.id ?? null,
  }));

  let bankTxns: unknown[] | undefined;
  if (q("bankAccountId")) {
    const ba = bankAccounts.find((b) => b.id === q("bankAccountId"));
    if (!ba) return NextResponse.json({ error: "Bank account not found" }, { status: 404 });
    bankTxns = await db.bankTxn.findMany({ where: { bankAccountId: ba.id }, orderBy: [{ date: "desc" }, { createdAt: "desc" }], take: 100 });
  }

  const statusCount: Record<string, number> = { DRAFT: 0, POSTED: 0, VOIDED: 0 };
  for (const g of journalStatus) statusCount[g.status] = g._count._all;

  return NextResponse.json({
    school: { name: school?.name ?? "" },
    accounts: accountsPayload,
    journals: entries.map((e) => {
      const totalDebitCents = e.lines.reduce((s, l) => s + l.debitCents, 0);
      const totalCreditCents = e.lines.reduce((s, l) => s + l.creditCents, 0);
      return {
        id: e.id, entryNumber: e.entryNumber, entryDate: e.entryDate, description: e.description,
        reference: e.reference, status: e.status, createdByName: e.createdByName,
        postedAt: e.postedAt, postedByName: e.postedByName, createdAt: e.createdAt,
        totalDebitCents, totalCreditCents, balanced: totalDebitCents === totalCreditCents,
        lines: e.lines.map((l) => ({ id: l.id, accountId: l.accountId, code: l.account.code, name: l.account.name, memo: l.memo, debitCents: l.debitCents, creditCents: l.creditCents })),
      };
    }),
    bankAccounts: bankAccountsPayload,
    bankTxns,
    reconciliations: recons.map((r) => ({
      id: r.id, bankAccountId: r.bankAccountId, bankAccountName: r.bankAccount.name,
      statementDate: r.statementDate, statementEndCents: r.statementEndCents,
      status: r.status, createdByName: r.createdByName, completedAt: r.completedAt, createdAt: r.createdAt,
      itemCount: r.items.length, matchedCount: r.items.filter((i) => i.matched).length,
    })),
    dashboard: {
      totalAssetsCents: accountsPayload.filter((a) => a.isActive && a.type === "ASSET").reduce((s, a) => s + a.balanceCents, 0),
      totalLiabilitiesCents: accountsPayload.filter((a) => a.isActive && a.type === "LIABILITY").reduce((s, a) => s + a.balanceCents, 0),
      totalEquityCents: accountsPayload.filter((a) => a.isActive && a.type === "EQUITY").reduce((s, a) => s + a.balanceCents, 0),
      revenueMtdCents: revMtd,
      expensesMtdCents: expMtd,
      netIncomeMtdCents: revMtd - expMtd,
      journalCounts: statusCount,
      unmatchedTxnCount,
      bankTotalCents: bankAccountsPayload.filter((b) => b.isActive).reduce((s, b) => s + b.currentBalanceCents, 0),
      openReconCount: recons.filter((r) => r.status === "IN_PROGRESS").length,
      activeAccountCount: accounts.filter((a) => a.isActive).length,
    },
  });
}

/* ══════════════════════════════════════════════════════════════
   POST — action dispatch (all actions need accounts.manage)
   ══════════════════════════════════════════════════════════════ */
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action ?? "";
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["accounts.manage"] });
  if (!auth.ok) return auth.res;
  const user = auth.user;
  const S = (v: unknown) => (typeof v === "string" ? v.trim() : "");
  const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });

  /* ── chart of accounts ─────────────────────────────────────── */
  if (action === "save-account") {
    const id = typeof body.id === "string" && body.id ? body.id : null;
    const code = S(body.code);
    const name = S(body.name);
    const type = S(body.type).toUpperCase();
    if (!code) return bad("Account code is required");
    if (!name) return bad("Account name is required");
    if (!ACCT_TYPES.includes(type as (typeof ACCT_TYPES)[number])) return bad(`Account type must be one of ${ACCT_TYPES.join(", ")}`);
    const dup = await db.chartAccount.findFirst({ where: { schoolId, code, ...(id ? { id: { not: id } } : {}) } });
    if (dup) return bad(`Code ${code} is already used by "${dup.name}"`, 409);
    let parentId: string | null = null;
    if (body.parentId && typeof body.parentId === "string") {
      const parent = await db.chartAccount.findFirst({ where: { id: body.parentId, schoolId } });
      if (!parent) return bad("Parent account not found in this school");
      if (id && parent.id === id) return bad("An account cannot be its own parent");
      // simple cycle guard — walk up the tree; reaching `id` means a loop
      let cur: { id: string; parentId: string | null } | null = parent;
      const seen = new Set<string>();
      while (cur?.parentId) {
        if (cur.parentId === id) return bad("That parent would create a cycle in the account tree");
        if (seen.has(cur.parentId)) break;
        seen.add(cur.parentId);
        cur = await db.chartAccount.findUnique({ where: { id: cur.parentId } });
      }
      parentId = parent.id;
    }
    const data = { code, name, type, parentId, isBank: body.isBank === true, isActive: body.isActive !== false };
    const account = id
      ? await db.chartAccount.update({ where: { id }, data }).catch(() => null)
      : await db.chartAccount.create({ data: { schoolId, ...data } });
    if (!account) return bad("Account not found in this school", 404);
    return NextResponse.json({ account });
  }

  if (action === "delete-account") {
    const id = S(body.id);
    const account = await db.chartAccount.findFirst({ where: { id, schoolId } });
    if (!account) return bad("Account not found in this school", 404);
    const [lineCount, bankCount, childCount] = await Promise.all([
      db.journalEntryLine.count({ where: { accountId: id } }),
      db.bankAccount.count({ where: { chartAccountId: id } }),
      db.chartAccount.count({ where: { parentId: id } }),
    ]);
    if (lineCount) return bad(`Cannot delete — ${lineCount} journal line(s) reference this account`, 409);
    if (bankCount) return bad(`Cannot delete — ${bankCount} bank account(s) link this account as their ledger account`, 409);
    if (childCount) return bad(`Cannot delete — ${childCount} sub-account(s) sit under it (re-parent them first)`, 409);
    await db.chartAccount.delete({ where: { id } });
    return NextResponse.json({ ok: true });
  }

  /* ── journal entries ───────────────────────────────────────── */
  if (action === "save-journal") {
    const id = typeof body.id === "string" && body.id ? body.id : null;
    const entryDate = parseDay(S(body.entryDate));
    if (!entryDate) return bad("Enter a valid entry date");
    const description = S(body.description);
    if (!description) return bad("A description is required");
    const reference = S(body.reference) || null;
    const rawLines = Array.isArray(body.lines) ? body.lines : [];
    if (rawLines.length < 2) return bad("A journal entry needs at least two lines (a debit and a credit)");
    const accounts = await db.chartAccount.findMany({ where: { schoolId }, select: { id: true } });
    const known = new Set(accounts.map((a) => a.id));
    let sumDebit = 0;
    let sumCredit = 0;
    const lines: { accountId: string; debitCents: number; creditCents: number; memo: string | null }[] = [];
    for (const [i, l] of rawLines.entries()) {
      const accountId = typeof (l as { accountId?: unknown })?.accountId === "string" ? (l as { accountId: string }).accountId : "";
      const debit = Number((l as { debitCents?: unknown })?.debitCents ?? 0);
      const credit = Number((l as { creditCents?: unknown })?.creditCents ?? 0);
      if (!isCents(debit) || !isCents(credit)) return bad(`Line ${i + 1}: debit and credit must be whole non-negative cents`);
      if (debit > 0 && credit > 0) return bad(`Line ${i + 1}: enter either a debit or a credit — not both`);
      if (debit === 0 && credit === 0) return bad(`Line ${i + 1}: enter a debit or a credit amount`);
      if (!known.has(accountId)) return bad(`Line ${i + 1}: pick an account from this school's chart`);
      sumDebit += debit;
      sumCredit += credit;
      lines.push({ accountId, debitCents: debit, creditCents: credit, memo: S((l as { memo?: unknown })?.memo) || null });
    }
    if (sumDebit !== sumCredit) return bad(`Entry is unbalanced — debits ${fmtGhs(sumDebit)} vs credits ${fmtGhs(sumCredit)}`);

    if (id) {
      const existing = await db.journalEntry.findFirst({ where: { id, schoolId } });
      if (!existing) return bad("Journal entry not found in this school", 404);
      if (existing.status !== "DRAFT") return bad("Posted entries cannot be edited; void and re-enter");
      const entry = await db.$transaction(async (tx) => {
        await tx.journalEntryLine.deleteMany({ where: { entryId: id } });
        await tx.journalEntryLine.createMany({ data: lines.map((l) => ({ entryId: id, ...l })) });
        return tx.journalEntry.update({ where: { id }, data: { entryDate, description, reference }, include: { lines: true } });
      });
      return NextResponse.json({ entry });
    }

    // JE-YYYY-#### sequence — globally unique, allocated transactionally (max existing + 1)
    const year = new Date().getUTCFullYear();
    const entry = await db.$transaction(async (tx) => {
      const last = await tx.journalEntry.findFirst({ where: { entryNumber: { startsWith: `JE-${year}-` } }, orderBy: { entryNumber: "desc" } });
      const next = (last ? parseInt(last.entryNumber.slice(-4), 10) || 0 : 0) + 1;
      return tx.journalEntry.create({
        data: {
          schoolId, entryNumber: `JE-${year}-${String(next).padStart(4, "0")}`,
          entryDate, description, reference, status: "DRAFT", createdByName: user.fullName,
          lines: { create: lines },
        },
        include: { lines: true },
      });
    });
    return NextResponse.json({ entry });
  }

  if (action === "post-journal") {
    const id = S(body.id);
    const result = await db.$transaction(async (tx) => {
      const entry = await tx.journalEntry.findFirst({ where: { id, schoolId }, include: { lines: true } });
      if (!entry) return { error: "Journal entry not found in this school", status: 404 };
      if (entry.status === "POSTED") return { error: "Already posted", status: 400 };
      if (entry.status === "VOIDED") return { error: "Voided entries cannot be posted", status: 400 };
      const d = entry.lines.reduce((s, l) => s + l.debitCents, 0);
      const c = entry.lines.reduce((s, l) => s + l.creditCents, 0);
      if (d !== c) return { error: `Entry is unbalanced — debits ${fmtGhs(d)} vs credits ${fmtGhs(c)}`, status: 400 };
      const updated = await tx.journalEntry.update({ where: { id }, data: { status: "POSTED", postedAt: new Date(), postedByName: user.fullName } });
      return { entry: updated };
    });
    if ("error" in result && result.error) return bad(result.error, result.status);
    return NextResponse.json({ entry: (result as { entry: unknown }).entry });
  }

  if (action === "void-journal") {
    const id = S(body.id);
    const entry = await db.journalEntry.findFirst({ where: { id, schoolId } });
    if (!entry) return bad("Journal entry not found in this school", 404);
    if (entry.status === "DRAFT") return bad("Drafts are deleted, not voided — use delete-journal");
    if (entry.status === "VOIDED") return bad("This entry is already voided");
    // VOIDED lines are excluded from all balances/reports (live computation),
    // so voiding is a status flip — the entry stays visible but inert.
    const voided = await db.journalEntry.update({ where: { id }, data: { status: "VOIDED" } });
    return NextResponse.json({ entry: voided });
  }

  if (action === "delete-journal") {
    const id = S(body.id);
    const entry = await db.journalEntry.findFirst({ where: { id, schoolId } });
    if (!entry) return bad("Journal entry not found in this school", 404);
    if (entry.status === "POSTED") return bad("Posted entries must be voided first — delete is for drafts only");
    if (entry.status === "VOIDED") return bad("Voided entries are kept as history and cannot be deleted");
    await db.journalEntry.delete({ where: { id } });
    return NextResponse.json({ ok: true });
  }

  /* ── bank accounts & txns ──────────────────────────────────── */
  if (action === "save-bank-account") {
    const id = typeof body.id === "string" && body.id ? body.id : null;
    const name = S(body.name);
    if (!name) return bad("Account name is required");
    let chartAccountId: string | null = null;
    let warning: string | null = null;
    if (body.chartAccountId && typeof body.chartAccountId === "string") {
      const linked = await db.chartAccount.findFirst({ where: { id: body.chartAccountId, schoolId } });
      if (!linked) return bad("Linked ledger account must belong to this school");
      if (!linked.isBank) warning = `"${linked.code} ${linked.name}" is not flagged as a bank account in the chart — allowed, but tick "bank account" on it for pickers.`;
      chartAccountId = linked.id;
    }
    const openingBalanceCents = Number(body.openingBalanceCents ?? 0);
    if (!Number.isInteger(openingBalanceCents)) return bad("Opening balance must be whole cents");
    const data = { name, bankName: S(body.bankName) || null, accountNumber: S(body.accountNumber) || null, chartAccountId, openingBalanceCents, isActive: body.isActive !== false };
    const account = id
      ? await db.bankAccount.update({ where: { id }, data }).catch(() => null)
      : await db.bankAccount.create({ data: { schoolId, ...data } });
    if (!account) return bad("Bank account not found in this school", 404);
    return NextResponse.json({ account, ...(warning ? { warning } : {}) });
  }

  if (action === "delete-bank-account") {
    const id = S(body.id);
    const account = await db.bankAccount.findFirst({ where: { id, schoolId } });
    if (!account) return bad("Bank account not found in this school", 404);
    const [txnCount, reconCount] = await Promise.all([
      db.bankTxn.count({ where: { bankAccountId: id } }),
      db.reconciliation.count({ where: { bankAccountId: id } }),
    ]);
    if (txnCount || reconCount) return bad(`Cannot delete — ${txnCount} transaction(s) and ${reconCount} reconciliation(s) exist on this account`, 409);
    await db.bankAccount.delete({ where: { id } });
    return NextResponse.json({ ok: true });
  }

  if (action === "add-bank-txn") {
    const bankAccountId = S(body.bankAccountId);
    const account = await db.bankAccount.findFirst({ where: { id: bankAccountId, schoolId } });
    if (!account) return bad("Bank account not found in this school", 404);
    const date = parseDay(S(body.date));
    if (!date) return bad("Enter a valid transaction date");
    const direction = S(body.direction).toUpperCase();
    if (!DIRECTIONS.includes(direction as (typeof DIRECTIONS)[number])) return bad("Direction must be DEPOSIT or WITHDRAWAL");
    const amountCents = Number(body.amountCents);
    if (!Number.isInteger(amountCents) || amountCents <= 0) return bad("Enter an amount above zero");
    const description = S(body.description);
    if (!description) return bad("A description is required");
    const source = S(body.source).toUpperCase() || "MANUAL";
    if (!SOURCES.includes(source as (typeof SOURCES)[number])) return bad(`Source must be one of ${SOURCES.join(", ")}`);
    // NOTE (deliberate CI3 deviation): bank txns do NOT auto-post a journal entry.
    // They are the operational feed for reconciliation; ledger posting stays manual
    // so automatic postings can never double-count with manual journals.
    const txn = await db.bankTxn.create({ data: { schoolId, bankAccountId, date, direction, amountCents, reference: S(body.reference) || null, description, source } });
    return NextResponse.json({ txn });
  }

  if (action === "delete-bank-txn") {
    const id = S(body.id);
    const txn = await db.bankTxn.findFirst({ where: { id, schoolId } });
    if (!txn) return bad("Bank transaction not found in this school", 404);
    const itemCount = await db.reconciliationItem.count({ where: { bankTxnId: id } });
    if (itemCount) return bad("Cannot delete — this transaction is referenced by a reconciliation line (remove the line first)", 409);
    await db.bankTxn.delete({ where: { id } });
    return NextResponse.json({ ok: true });
  }

  /* ── bank reconciliation ───────────────────────────────────── */
  if (action === "create-reconciliation") {
    const bankAccountId = S(body.bankAccountId);
    const account = await db.bankAccount.findFirst({ where: { id: bankAccountId, schoolId } });
    if (!account) return bad("Bank account not found in this school", 404);
    const statementDate = parseDay(S(body.statementDate));
    if (!statementDate) return bad("Enter a valid statement date");
    const statementEndCents = Number(body.statementEndCents);
    if (!Number.isInteger(statementEndCents)) return bad("Statement ending balance must be whole cents");
    const open = await db.reconciliation.findFirst({ where: { bankAccountId, status: "IN_PROGRESS" } });
    if (open) return bad("Complete or delete the open reconciliation first", 409);
    const reconciliation = await db.reconciliation.create({ data: { schoolId, bankAccountId, statementDate, statementEndCents, status: "IN_PROGRESS", createdByName: user.fullName } });
    return NextResponse.json({ reconciliation });
  }

  if (action === "add-statement-item") {
    const reconciliationId = S(body.reconciliationId);
    const recon = await db.reconciliation.findFirst({ where: { id: reconciliationId, schoolId } });
    if (!recon) return bad("Reconciliation not found in this school", 404);
    if (recon.status !== "IN_PROGRESS") return bad("Completed reconciliations are read-only");
    const date = parseDay(S(body.date));
    if (!date) return bad("Enter a valid line date");
    const description = S(body.description);
    if (!description) return bad("A description is required");
    const amountCents = Number(body.amountCents);
    if (!Number.isInteger(amountCents) || amountCents === 0) return bad("Enter a non-zero signed amount (+ deposit / − withdrawal)");
    let bankTxnId: string | null = null;
    let matched = false;
    if (body.bankTxnId && typeof body.bankTxnId === "string") {
      const txn = await db.bankTxn.findFirst({ where: { id: body.bankTxnId, bankAccountId: recon.bankAccountId } });
      if (!txn) return bad("Linked transaction must be on this reconciliation's bank account");
      bankTxnId = txn.id;
      matched = Math.abs(amountCents) === txn.amountCents;
    }
    const item = await db.reconciliationItem.create({ data: { reconciliationId, date, description, amountCents, bankTxnId, matched } });
    return NextResponse.json({ item });
  }

  if (action === "match-item" || action === "unmatch-item") {
    const itemId = S(body.itemId);
    const item = await db.reconciliationItem.findFirst({ where: { id: itemId, reconciliation: { schoolId } }, include: { reconciliation: true } });
    if (!item) return bad("Statement line not found", 404);
    if (item.reconciliation.status !== "IN_PROGRESS") return bad("Completed reconciliations are read-only");
    if (action === "unmatch-item") {
      const cleared = await db.reconciliationItem.update({ where: { id: itemId }, data: { bankTxnId: null, matched: false } });
      return NextResponse.json({ item: cleared });
    }
    const bankTxnId = S(body.bankTxnId);
    const txn = await db.bankTxn.findFirst({ where: { id: bankTxnId, bankAccountId: item.reconciliation.bankAccountId } });
    if (!txn) return bad("Pick a transaction from this bank account");
    // matched flips on automatically when the signed amounts agree
    const matched = Math.abs(item.amountCents) === txn.amountCents;
    const updated = await db.reconciliationItem.update({ where: { id: itemId }, data: { bankTxnId, matched } });
    return NextResponse.json({ item: updated, amountsMatch: matched });
  }

  if (action === "remove-item") {
    const itemId = S(body.itemId);
    const item = await db.reconciliationItem.findFirst({ where: { id: itemId, reconciliation: { schoolId } }, include: { reconciliation: true } });
    if (!item) return bad("Statement line not found", 404);
    if (item.reconciliation.status !== "IN_PROGRESS") return bad("Completed reconciliations are read-only");
    await db.reconciliationItem.delete({ where: { id: itemId } });
    return NextResponse.json({ ok: true });
  }

  if (action === "auto-match") {
    const reconciliationId = S(body.reconciliationId);
    const recon = await db.reconciliation.findFirst({ where: { id: reconciliationId, schoolId } });
    if (!recon) return bad("Reconciliation not found in this school", 404);
    if (recon.status !== "IN_PROGRESS") return bad("Completed reconciliations are read-only");
    const items = await db.reconciliationItem.findMany({ where: { reconciliationId, matched: false }, orderBy: { date: "asc" } });
    const used = new Set((await db.reconciliationItem.findMany({ where: { reconciliationId, bankTxnId: { not: null } }, select: { bankTxnId: true } })).map((i) => i.bankTxnId as string));
    const txns = await db.bankTxn.findMany({ where: { bankAccountId: recon.bankAccountId, reconciledAt: null, ...(used.size ? { id: { notIn: [...used] } } : {}) }, orderBy: { date: "asc" } });
    const taken = new Set<string>();
    let matched = 0;
    // two passes (CI3 heuristic): ① |amount| + direction AND date within ±3 days,
    // then ② |amount| + direction only — amount-first, date-second relaxation.
    // Sign of the statement line must agree with the txn direction (+ = DEPOSIT).
    for (const item of items) {
      const wantDeposit = item.amountCents > 0;
      const abs = Math.abs(item.amountCents);
      const fits = (t: (typeof txns)[number]) => !taken.has(t.id) && t.amountCents === abs && (t.direction === "DEPOSIT") === wantDeposit;
      const hit = txns.find((t) => fits(t) && dayDiff(t.date, item.date) <= 3) ?? txns.find((t) => fits(t));
      if (hit) {
        taken.add(hit.id);
        await db.reconciliationItem.update({ where: { id: item.id }, data: { bankTxnId: hit.id, matched: true } });
        matched++;
      }
    }
    return NextResponse.json({ matched });
  }

  if (action === "complete-reconciliation") {
    const reconciliationId = S(body.reconciliationId);
    const result = await db.$transaction(async (tx) => {
      const recon = await tx.reconciliation.findFirst({ where: { id: reconciliationId, schoolId }, include: { items: true } });
      if (!recon) return { error: "Reconciliation not found in this school", status: 404 };
      if (recon.status !== "IN_PROGRESS") return { error: "This reconciliation is already completed", status: 400 };
      const unmatched = recon.items.filter((i) => !i.matched).length;
      if (unmatched > 0) return { error: `${unmatched} of ${recon.items.length} statement lines are still unmatched`, status: 400 };
      const reconciledCents = recon.items.filter((i) => i.matched).reduce((s, i) => s + i.amountCents, 0);
      const updated = await tx.reconciliation.update({ where: { id: reconciliationId }, data: { status: "COMPLETED", completedAt: new Date() } });
      const txnIds = recon.items.map((i) => i.bankTxnId).filter(Boolean) as string[];
      if (txnIds.length) await tx.bankTxn.updateMany({ where: { id: { in: txnIds } }, data: { reconciledAt: new Date() } });
      return { reconciliation: updated, reconciledCents, stampedTxns: txnIds.length };
    });
    if ("error" in result && result.error) return bad(result.error, result.status);
    const ok = result as { reconciliation: unknown; reconciledCents: number; stampedTxns: number };
    return NextResponse.json({ reconciliation: ok.reconciliation, reconciledCents: ok.reconciledCents, stampedTxns: ok.stampedTxns });
  }

  if (action === "delete-reconciliation") {
    const id = S(body.id);
    const recon = await db.reconciliation.findFirst({ where: { id, schoolId } });
    if (!recon) return bad("Reconciliation not found in this school", 404);
    if (recon.status === "COMPLETED") return bad("Completed reconciliations are history — they cannot be deleted");
    await db.reconciliation.delete({ where: { id } }); // items cascade
    return NextResponse.json({ ok: true });
  }

  return bad("Unknown action");
}

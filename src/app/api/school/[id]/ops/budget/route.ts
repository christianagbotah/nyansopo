import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { Prisma } from "@prisma/client";

/* ─────────────────────────────────────────────────────────────
   GET  /api/school/[id]/ops/budget — budgets & fiscal years payload
   POST /api/school/[id]/ops/budget — action dispatch
   CI3 parity: Budget_management.php + Accounts.php budgets()/fiscal_year().

   SEMANTICS (documented once, used everywhere):
   • Fiscal years segment the accounting calendar ("2025/26"); budgets
     allocate expected amounts per chart account over a window.
   • ACTUALS ARE LIVE — never cached: for a budget line on account A over
     [startsAt, endsAt] (UTC day bounds), actualCents = Σ(debits−credits) of
     POSTED journal lines on A when A is debit-normal (ASSET/EXPENSE), else
     Σ(credits−debits). Budget actualCents = Σ line actuals — so posting or
     voiding an entry instantly re-flows every budget variance (same
     live-compute doctrine as the accounts route's account balances).
   • Lifecycle (CI3 parity): fiscal year OPEN→CLOSED (stamped; if it was
     current, the latest other OPEN year inherits isCurrent); budget
     DRAFT→APPROVED (stamped)→CLOSED (stamped). Only DRAFT budgets are
     editable and deletable — an approved budget must be closed first:
     safer than CI3's raw delete, which could erase an approved plan from
     the audit history.
   • Budget codes BGT-YYYY-NNNN are sync-safe CodeCounter mints (scope
     `BGT:<year of startsAt>`) — create-or-increment pattern copied from
     src/lib/codes.ts nextSeq(), so concurrent/offline minting can't collide.
   • totalBudgetedCents is denormalized (Σ lines.budgetedCents, recomputed on
     every line change) for list views; budget detail recomputes totals from
     the live lines, never trusting the column.
   ───────────────────────────────────────────────────────────── */

const DAY_MS = 86_400_000;
const DEBIT_NORMAL = new Set(["ASSET", "EXPENSE"]);

const parseDay = (s: string | null | undefined): Date | null => {
  if (!s) return null;
  const d = new Date(s.length === 10 ? `${s}T00:00:00.000Z` : s);
  return Number.isNaN(d.getTime()) ? null : d;
};
const startOfDay = (d: Date) => new Date(`${d.toISOString().slice(0, 10)}T00:00:00.000Z`);
const endOfDay = (d: Date) => new Date(d.getTime() + DAY_MS - 1);
/** UTC day-bounded window covered by a budget (start of startsAt .. end of endsAt). */
const windowOf = (b: { startsAt: Date; endsAt: Date }) => ({ gte: startOfDay(b.startsAt), lte: endOfDay(startOfDay(b.endsAt)) });
const pct = (num: number, den: number) => (den > 0 ? Math.round((num / den) * 100) : 0);

type Movement = { debit: number; credit: number };

/** Posted movement per account over a day-bounded window. JournalEntryLine has
 *  no schoolId — the school filter rides the journal entry relation. */
async function postedSums(schoolId: string, accountIds: string[], window: { gte: Date; lte: Date }): Promise<Map<string, Movement>> {
  if (!accountIds.length) return new Map();
  const rows = await db.journalEntryLine.groupBy({
    by: ["accountId"],
    where: { accountId: { in: accountIds }, entry: { schoolId, status: "POSTED", entryDate: window } },
    _sum: { debitCents: true, creditCents: true },
  });
  return new Map(rows.map((r) => [r.accountId, { debit: r._sum.debitCents ?? 0, credit: r._sum.creditCents ?? 0 }]));
}

/** Natural-side actual for one account: debits−credits when debit-normal. */
const actualOf = (type: string, m?: Movement) => {
  const v = m ?? { debit: 0, credit: 0 };
  return DEBIT_NORMAL.has(type) ? v.debit - v.credit : v.credit - v.debit;
};

/** BGT-YYYY-NNNN — sync-safe budget code from the CodeCounter row for scope
 *  `BGT:<year>` — the exact minting pattern of src/lib/codes.ts nextSeq():
 *  the first allocation seeds the counter from the highest existing code of
 *  that kind (legacy/seed data keeps its numbers), and every later
 *  allocation is a single atomic SQL increment, so two clerks — or an
 *  offline device flushing a queue — can never mint the same number. */
async function nextBudgetCode(year: number): Promise<string> {
  const scope = `BGT:${year}`;
  const prefix = `BGT-${year}-`;
  const last = await db.budget.findFirst({ where: { code: { startsWith: prefix } }, orderBy: { code: "desc" }, select: { code: true } });
  const seed = last ? parseInt(last.code.slice(prefix.length), 10) || 0 : 0;
  try {
    await db.codeCounter.create({ data: { scope, next: seed + 1 } });
    return `${prefix}${String(seed + 1).padStart(4, "0")}`;
  } catch (e) {
    if (!(e instanceof Prisma.PrismaClientKnownRequestError && e.code === "P2002")) throw e;
  }
  const row = await db.codeCounter.update({ where: { scope }, data: { next: { increment: 1 } } });
  return `${prefix}${String(row.next).padStart(4, "0")}`;
}

/* ══════════════════════════════════════════════════════════════
   GET — module dashboard, single-budget detail, account picker
   ══════════════════════════════════════════════════════════════ */
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const url = new URL(req.url);
  const q = (k: string) => url.searchParams.get(k) ?? undefined;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["budget.view"] });
  if (!auth.ok) return auth.res;

  /* ── single budget: lines with account + live actuals/variance ── */
  if (q("action") === "budget" && q("budgetId")) {
    const budget = await db.budget.findFirst({
      where: { id: q("budgetId")!, schoolId },
      include: {
        fiscalYear: { select: { id: true, name: true, status: true } },
        lines: { orderBy: { chartAccount: { code: "asc" } }, include: { chartAccount: { select: { id: true, code: true, name: true, type: true } } } },
      },
    });
    if (!budget) return NextResponse.json({ error: "Budget not found in this school" }, { status: 404 });
    const sums = await postedSums(schoolId, budget.lines.map((l) => l.chartAccountId), windowOf(budget));
    const lines = budget.lines.map((l) => {
      const actualCents = actualOf(l.chartAccount.type, sums.get(l.chartAccountId));
      return {
        id: l.id,
        account: { id: l.chartAccount.id, code: l.chartAccount.code, name: l.chartAccount.name, type: l.chartAccount.type },
        budgetedCents: l.budgetedCents,
        actualCents,
        varianceCents: l.budgetedCents - actualCents,
        utilizationPct: pct(actualCents, l.budgetedCents),
        notes: l.notes,
      };
    });
    // budget-level totals recomputed from the live lines — not the denormalized column
    const totalBudgetedCents = lines.reduce((s, l) => s + l.budgetedCents, 0);
    const actualCents = lines.reduce((s, l) => s + l.actualCents, 0);
    return NextResponse.json({
      budget: {
        id: budget.id, code: budget.code, name: budget.name, notes: budget.notes, status: budget.status,
        fiscalYear: budget.fiscalYear, startsAt: budget.startsAt, endsAt: budget.endsAt,
        createdByName: budget.createdByName, approvedByName: budget.approvedByName, approvedAt: budget.approvedAt,
        closedByName: budget.closedByName, closedAt: budget.closedAt, createdAt: budget.createdAt,
        lines,
        totalBudgetedCents, actualCents, remainingCents: totalBudgetedCents - actualCents,
        utilizationPct: pct(actualCents, totalBudgetedCents),
      },
    });
  }

  /* ── active chart accounts for the budget-line picker ── */
  if (q("action") === "accounts") {
    const accounts = await db.chartAccount.findMany({
      where: { schoolId, isActive: true },
      orderBy: { code: "asc" },
      select: { id: true, code: true, name: true, type: true },
    });
    return NextResponse.json({ accounts });
  }

  /* ── dashboard: fiscal years + budgets (live actuals) + KPIs ── */
  const [fiscalYears, budgets] = await Promise.all([
    db.fiscalYear.findMany({ where: { schoolId }, orderBy: { startsAt: "desc" } }),
    db.budget.findMany({
      where: { schoolId },
      orderBy: { createdAt: "desc" },
      include: {
        fiscalYear: { select: { name: true } },
        lines: { select: { chartAccountId: true, budgetedCents: true, chartAccount: { select: { type: true } } } },
      },
    }),
  ]);

  const budgetPayload = await Promise.all(
    budgets.map(async (b) => {
      const sums = await postedSums(schoolId, b.lines.map((l) => l.chartAccountId), windowOf(b));
      const totalBudgetedCents = b.lines.reduce((s, l) => s + l.budgetedCents, 0);
      const actualCents = b.lines.reduce((s, l) => s + actualOf(l.chartAccount.type, sums.get(l.chartAccountId)), 0);
      return {
        id: b.id, code: b.code, name: b.name, status: b.status, notes: b.notes, lineCount: b.lines.length,
        fiscalYearId: b.fiscalYearId, fiscalYearName: b.fiscalYear.name,
        startsAt: b.startsAt, endsAt: b.endsAt,
        createdByName: b.createdByName, approvedByName: b.approvedByName, approvedAt: b.approvedAt,
        closedByName: b.closedByName, closedAt: b.closedAt, createdAt: b.createdAt,
        totalBudgetedCents, actualCents, remainingCents: totalBudgetedCents - actualCents,
        utilizationPct: pct(actualCents, totalBudgetedCents),
      };
    }),
  );

  const approved = budgetPayload.filter((b) => b.status === "APPROVED");
  const budgetCountByYear = new Map<string, number>();
  for (const b of budgetPayload) budgetCountByYear.set(b.fiscalYearId, (budgetCountByYear.get(b.fiscalYearId) ?? 0) + 1);

  return NextResponse.json({
    fiscalYears: fiscalYears.map((y) => ({
      id: y.id, name: y.name, startsAt: y.startsAt, endsAt: y.endsAt, status: y.status, isCurrent: y.isCurrent,
      notes: y.notes, createdByName: y.createdByName, closedAt: y.closedAt, closedByName: y.closedByName,
      createdAt: y.createdAt, budgetCount: budgetCountByYear.get(y.id) ?? 0,
    })),
    budgets: budgetPayload,
    kpis: {
      budgets: budgetPayload.length,
      approved: approved.length,
      drafts: budgetPayload.filter((b) => b.status === "DRAFT").length,
      allocatedCents: approved.reduce((s, b) => s + b.totalBudgetedCents, 0),
      utilizedCents: approved.reduce((s, b) => s + b.actualCents, 0),
    },
  });
}

/* ══════════════════════════════════════════════════════════════
   POST — action dispatch (all actions need budget.manage — the
   finance-route per-action gate collapses to one requirement here
   because every budget action is a management mutation)
   ══════════════════════════════════════════════════════════════ */
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action ?? "";
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["budget.manage"] });
  if (!auth.ok) return auth.res;
  const user = auth.user;
  const S = (v: unknown) => (typeof v === "string" ? v.trim() : "");
  const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });

  /* ── fiscal years ──────────────────────────────────────────── */
  if (action === "save-year") {
    const yearId = typeof body.yearId === "string" && body.yearId ? body.yearId : null;
    const name = S(body.name);
    if (!name) return bad("Fiscal year name is required");
    const startsAt = parseDay(S(body.startsAt));
    const endsAt = parseDay(S(body.endsAt));
    if (!startsAt || !endsAt) return bad("Enter valid start and end dates");
    if (startsAt >= endsAt) return bad("The fiscal year must start before it ends");
    const notes = S(body.notes) || null;

    if (yearId) {
      const existing = await db.fiscalYear.findFirst({ where: { id: yearId, schoolId } });
      if (!existing) return bad("Fiscal year not found in this school", 404);
      if (existing.status === "CLOSED") return bad("Closed fiscal years cannot be edited");
      const isCurrent = typeof body.isCurrent === "boolean" ? body.isCurrent : existing.isCurrent;
      if (isCurrent) await db.fiscalYear.updateMany({ where: { schoolId, isCurrent: true, id: { not: yearId } }, data: { isCurrent: false } });
      const year = await db.fiscalYear.update({ where: { id: yearId }, data: { name, startsAt, endsAt, notes, isCurrent } });
      return NextResponse.json({ year });
    }

    // default: current when the school has no OPEN current year (CI3 parity)
    const openCurrent = await db.fiscalYear.findFirst({ where: { schoolId, isCurrent: true, status: "OPEN" }, select: { id: true } });
    const isCurrent = typeof body.isCurrent === "boolean" ? body.isCurrent : !openCurrent;
    if (isCurrent) await db.fiscalYear.updateMany({ where: { schoolId, isCurrent: true }, data: { isCurrent: false } });
    const year = await db.fiscalYear.create({ data: { schoolId, name, startsAt, endsAt, notes, isCurrent, createdByName: user.fullName } });
    return NextResponse.json({ year });
  }

  if (action === "close-year") {
    const year = await db.fiscalYear.findFirst({ where: { id: S(body.yearId), schoolId } });
    if (!year) return bad("Fiscal year not found in this school", 404);
    if (year.status !== "OPEN") return bad("Only OPEN fiscal years can be closed");
    const closed = await db.fiscalYear.update({
      where: { id: year.id },
      data: { status: "CLOSED", closedAt: new Date(), closedByName: user.fullName, isCurrent: false },
    });
    if (year.isCurrent) {
      // promote the latest other OPEN year, if any (CI3 parity)
      const successor = await db.fiscalYear.findFirst({ where: { schoolId, status: "OPEN", id: { not: year.id } }, orderBy: { startsAt: "desc" } });
      if (successor) await db.fiscalYear.update({ where: { id: successor.id }, data: { isCurrent: true } });
    }
    return NextResponse.json({ year: closed });
  }

  if (action === "delete-year") {
    const year = await db.fiscalYear.findFirst({ where: { id: S(body.yearId), schoolId } });
    if (!year) return bad("Fiscal year not found in this school", 404);
    const count = await db.budget.count({ where: { fiscalYearId: year.id } });
    if (count) return bad(`Cannot delete — ${count} budget(s) reference this fiscal year`, 409);
    await db.fiscalYear.delete({ where: { id: year.id } });
    return NextResponse.json({ ok: true });
  }

  /* ── budgets ───────────────────────────────────────────────── */
  if (action === "save-budget") {
    const budgetId = typeof body.budgetId === "string" && body.budgetId ? body.budgetId : null;
    const name = S(body.name);
    if (!name) return bad("Budget name is required");
    const fiscalYear = await db.fiscalYear.findFirst({ where: { id: S(body.fiscalYearId), schoolId } });
    if (!fiscalYear) return bad("Fiscal year not found in this school", 404);
    const startsAt = parseDay(S(body.startsAt));
    const endsAt = parseDay(S(body.endsAt));
    if (!startsAt || !endsAt) return bad("Enter valid budget start and end dates");
    if (startsAt >= endsAt) return bad("The budget must start before it ends");
    if (endsAt.getTime() - startsAt.getTime() > 10 * 365 * DAY_MS) return bad("Budget window spans more than 10 years — check the dates");
    const notes = S(body.notes) || null;

    const rawLines = Array.isArray(body.lines) ? body.lines : [];
    if (rawLines.length < 1) return bad("A budget needs at least one line");
    const accounts = await db.chartAccount.findMany({ where: { schoolId }, select: { id: true, code: true, name: true } });
    const known = new Map(accounts.map((a) => [a.id, a]));
    const lines: { chartAccountId: string; budgetedCents: number; notes: string | null }[] = [];
    let totalBudgetedCents = 0;
    for (const [i, l] of rawLines.entries()) {
      const chartAccountId = typeof (l as { chartAccountId?: unknown })?.chartAccountId === "string" ? (l as { chartAccountId: string }).chartAccountId : "";
      const account = known.get(chartAccountId);
      if (!account) return bad(`Line ${i + 1}: pick an account from this school's chart`);
      const budgetedCents = Number((l as { budgetedCents?: unknown })?.budgetedCents ?? 0);
      if (!Number.isInteger(budgetedCents) || budgetedCents <= 0) return bad(`Line ${i + 1}: budgeted amount must be whole cents above zero`);
      if (lines.some((x) => x.chartAccountId === chartAccountId)) return bad(`Line ${i + 1}: account ${account.code} ${account.name} is already budgeted above — one line per account`);
      totalBudgetedCents += budgetedCents;
      lines.push({ chartAccountId, budgetedCents, notes: S((l as { notes?: unknown })?.notes) || null });
    }

    if (budgetId) {
      const existing = await db.budget.findFirst({ where: { id: budgetId, schoolId } });
      if (!existing) return bad("Budget not found in this school", 404);
      if (existing.status !== "DRAFT") return bad("Only DRAFT budgets can be edited — approved and closed budgets are frozen");
      // full line replacement, transactionally (same pattern as save-journal)
      const budget = await db.$transaction(async (tx) => {
        await tx.budgetLine.deleteMany({ where: { budgetId } });
        await tx.budgetLine.createMany({ data: lines.map((l) => ({ budgetId, ...l })) });
        return tx.budget.update({
          where: { id: budgetId },
          data: { name, fiscalYearId: fiscalYear.id, startsAt, endsAt, notes, totalBudgetedCents },
        });
      });
      return NextResponse.json({ budget });
    }

    // BGT-YYYY-NNNN — sync-safe mint, scoped to the budget's start year
    const code = await nextBudgetCode(startsAt.getUTCFullYear());
    const budget = await db.budget.create({
      data: {
        schoolId, code, name, fiscalYearId: fiscalYear.id, startsAt, endsAt, notes,
        status: "DRAFT", totalBudgetedCents, createdByName: user.fullName,
        lines: { create: lines },
      },
    });
    return NextResponse.json({ budget });
  }

  if (action === "approve-budget") {
    const budget = await db.budget.findFirst({ where: { id: S(body.budgetId), schoolId } });
    if (!budget) return bad("Budget not found in this school", 404);
    if (budget.status !== "DRAFT") return bad("Only DRAFT budgets can be approved");
    const approved = await db.budget.update({
      where: { id: budget.id },
      data: { status: "APPROVED", approvedAt: new Date(), approvedByName: user.fullName },
    });
    return NextResponse.json({ budget: approved });
  }

  if (action === "close-budget") {
    const budget = await db.budget.findFirst({ where: { id: S(body.budgetId), schoolId } });
    if (!budget) return bad("Budget not found in this school", 404);
    if (budget.status !== "APPROVED") return bad("Only APPROVED budgets can be closed");
    const closed = await db.budget.update({
      where: { id: budget.id },
      data: { status: "CLOSED", closedAt: new Date(), closedByName: user.fullName },
    });
    return NextResponse.json({ budget: closed });
  }

  if (action === "delete-budget") {
    const budget = await db.budget.findFirst({ where: { id: S(body.budgetId), schoolId } });
    if (!budget) return bad("Budget not found in this school", 404);
    // only DRAFT deletable — an approved budget must be closed first. Safer
    // than CI3's raw delete, which could erase an approved plan from history.
    if (budget.status !== "DRAFT") return bad("Only DRAFT budgets can be deleted — close approved budgets instead (audit trail)");
    await db.budget.delete({ where: { id: budget.id } }); // lines cascade
    return NextResponse.json({ ok: true });
  }

  return bad("Unknown action");
}

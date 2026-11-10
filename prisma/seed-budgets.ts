// ── Budgets & fiscal years seed (CI3 Budget_management parity — task 19-c).
// Two fiscal years (one closed, one current) + three budgets across them so
// the dashboard opens with KPIs, utilisation bars and a CLOSED→OPEN history.
// Idempotent: wipes this school's budgets/fiscal years and recreates them
// (live rows created while testing are replaced by these equivalents).
// Codes reuse the real BGT-YYYY-NNNN mint format; the CodeCounter scopes
// BGT:<year> are set to the max used, so future route mints continue the
// sequence without colliding (same self-seeding doctrine as src/lib/codes.ts
// nextSeq() — the counter row is only raised, never rolled back).
// Run: DATABASE_URL=postgresql://… bun prisma/seed-budgets.ts
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

const D = (s: string) => new Date(`${s}T00:00:00.000Z`);

async function main() {
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!school) { console.log("GEA school missing — run the main seed first"); return; }
  const schoolId = school.id;

  // expense accounts from the chart-of-accounts seed (seed-accounts.ts)
  const accounts = await db.chartAccount.findMany({ where: { schoolId, type: "EXPENSE" }, select: { id: true, code: true } });
  const A = new Map(accounts.map((a) => [a.code, a.id]));
  const acc = (code: string) => {
    const id = A.get(code);
    if (!id) throw new Error(`chart account ${code} missing — run seed-accounts first`);
    return id;
  };

  // ── wipe (budget lines cascade with their budget) ──
  const wipedBudgets = await db.budget.deleteMany({ where: { schoolId } });
  const wipedYears = await db.fiscalYear.deleteMany({ where: { schoolId } });

  // ── fiscal years ──
  const fy2425 = await db.fiscalYear.create({
    data: {
      schoolId, name: "2024/25", startsAt: D("2024-09-01"), endsAt: D("2025-08-31"),
      status: "CLOSED", isCurrent: false, notes: "Archived after the 2025 year-end close.",
      createdByName: "Madam Akosua Danso", closedAt: D("2025-09-05"), closedByName: "Madam Akosua Danso",
    },
  });
  const fy2526 = await db.fiscalYear.create({
    data: {
      schoolId, name: "2025/26", startsAt: D("2025-09-01"), endsAt: D("2026-08-31"),
      status: "OPEN", isCurrent: true, notes: "Current academic/financial year.",
      createdByName: "Madam Akosua Danso",
    },
  });

  // ── budgets ──
  const by = "Madam Akosua Danso";
  const mk = async (args: {
    code: string; name: string; fiscalYearId: string; startsAt: string; endsAt: string;
    status: "DRAFT" | "APPROVED" | "CLOSED"; notes: string;
    approved?: string; closed?: string;
    lines: [code: string, cents: number, note: string][];
  }) => {
    const totalBudgetedCents = args.lines.reduce((s, [, c]) => s + c, 0);
    return db.budget.create({
      data: {
        schoolId, code: args.code, name: args.name, fiscalYearId: args.fiscalYearId,
        startsAt: D(args.startsAt), endsAt: D(args.endsAt), status: args.status, notes: args.notes,
        totalBudgetedCents, createdByName: by,
        approvedAt: args.approved ? D(args.approved) : null, approvedByName: args.approved ? by : null,
        closedAt: args.closed ? D(args.closed) : null, closedByName: args.closed ? by : null,
        lines: { create: args.lines.map(([code, cents, note]) => ({ chartAccountId: acc(code), budgetedCents: cents, notes: note })) },
      },
    });
  };

  // a. FY 2024/25 whole-year operations plan — closed with the year
  await mk({
    code: "BGT-2024-0001", name: "FY 2024/25 Operations", fiscalYearId: fy2425.id,
    startsAt: "2024-09-01", endsAt: "2025-08-31", status: "CLOSED", closed: "2025-09-05",
    notes: "Whole-year operations plan; closed together with the fiscal year.",
    lines: [
      ["5000", 120_000, "Gross salaries — 12 staff"],
      ["5200", 30_000, "ECG electricity & GWCL water"],
      ["5300", 15_000, "School bus fuel & servicing"],
    ],
  });

  // b. current-year operations plan — approved, live actuals flow into it
  await mk({
    code: "BGT-2025-0001", name: "Term 1 Operations", fiscalYearId: fy2526.id,
    startsAt: "2025-09-01", endsAt: "2026-08-31", status: "APPROVED", approved: "2025-09-08",
    notes: "Approved by the proprietor at the start-of-term board review.",
    lines: [
      ["5000", 50_000, "Salaries — first tranche"],
      ["5200", 20_000, "Utilities"],
      ["5400", 12_000, "Teaching materials & textbooks"],
      ["5700", 8_000, "Cleaning & sanitation"],
    ],
  });

  // c. ICT refresh — still a draft being costed
  await mk({
    code: "BGT-2025-0002", name: "ICT & Lab Refresh", fiscalYearId: fy2526.id,
    startsAt: "2025-09-01", endsAt: "2025-12-31", status: "DRAFT",
    notes: "Draft: computer-lab refurbishment quote pending PTA approval.",
    lines: [
      ["5500", 30_000, "Lab benches & electrical repairs"],
      ["5400", 10_000, "Software licences & teaching aids"],
    ],
  });

  // ── sync-safe counters: raise BGT:<year> to the max sequence used above
  // (never lower — a live-minted counter must not roll back) ──
  for (const [scope, used] of [["BGT:2024", 1], ["BGT:2025", 2]] as const) {
    const existing = await db.codeCounter.findUnique({ where: { scope } });
    await db.codeCounter.upsert({
      where: { scope },
      create: { scope, next: used },
      update: { next: Math.max(existing?.next ?? 0, used) },
    });
  }

  console.log(`✅ Budgets seed complete — 2 fiscal years (2024/25 CLOSED, 2025/26 OPEN·current) + 3 budgets (BGT-2024-0001 CLOSED ₵1,650 · BGT-2025-0001 APPROVED ₵900 · BGT-2025-0002 DRAFT ₵400) for ${school.name}${wipedBudgets.count || wipedYears.count ? ` (wiped ${wipedBudgets.count} budget(s) / ${wipedYears.count} fiscal year(s) first)` : ""}`);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

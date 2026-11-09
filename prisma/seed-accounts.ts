// ── Double-entry accounts seed (CI3 Accounts parity — task 3-b).
// Ghana private-school chart of accounts + demo ledger: posted/draft journals,
// two bank feeds, one in-progress reconciliation with auto-matchable lines.
// Idempotent: skips when the school already has a chart of accounts.
// Run: DATABASE_URL=postgresql://… bun prisma/seed-accounts.ts
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const DAY = 86_400_000;

async function main() {
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!school) { console.log("GEA school missing — run the main seed first"); return; }
  const schoolId = school.id;
  const existing = await db.chartAccount.count({ where: { schoolId } });
  if (existing > 0) { console.log(`already seeded (${existing} accounts) — skipping`); return; }

  const bursar = await db.user.findUnique({ where: { email: "bursar@nyansapo.gh" } });
  const byName = bursar?.fullName ?? "Bursar";

  // UTC-midnight date n days ago
  const now = new Date();
  const base = Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate());
  const day = (n: number) => new Date(base - n * DAY);
  // a date INSIDE the current calendar month (clamped to today) so MTD
  // dashboard metrics are non-zero whenever the seed is run
  const monthDay = (d: number) => new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), Math.min(d, now.getUTCDate())));

  // ── chart of accounts (Ghana private-school layout) ──
  const CHART: { code: string; name: string; type: string; isBank?: boolean }[] = [
    // ASSETS
    { code: "1000", name: "Cash in Hand", type: "ASSET" },
    { code: "1010", name: "Main Bank Account", type: "ASSET", isBank: true },
    { code: "1020", name: "MTN MoMo Wallet", type: "ASSET", isBank: true },
    { code: "1030", name: "Petty Cash", type: "ASSET" },
    { code: "1100", name: "Accounts Receivable (School Fees)", type: "ASSET" },
    { code: "1200", name: "Prepaid Expenses", type: "ASSET" },
    { code: "1300", name: "Inventory (Tuck Store)", type: "ASSET" },
    { code: "1400", name: "Furniture & Equipment", type: "ASSET" },
    // LIABILITIES
    { code: "2000", name: "Accounts Payable", type: "LIABILITY" },
    { code: "2100", name: "SSNIT Payable", type: "LIABILITY" },
    { code: "2200", name: "PAYE Payable", type: "LIABILITY" },
    { code: "2300", name: "Salaries Payable", type: "LIABILITY" },
    { code: "2400", name: "School Fees Received in Advance", type: "LIABILITY" },
    { code: "2500", name: "Supplier Deposits", type: "LIABILITY" },
    // EQUITY
    { code: "3000", name: "Proprietor's Capital", type: "EQUITY" },
    { code: "3100", name: "Accumulated Surplus", type: "EQUITY" },
    // REVENUE
    { code: "4000", name: "School Fees Income", type: "REVENUE" },
    { code: "4100", name: "Admission Fees", type: "REVENUE" },
    { code: "4200", name: "Daily Services Income", type: "REVENUE" },
    { code: "4300", name: "Tuck Shop Sales", type: "REVENUE" },
    { code: "4400", name: "Uniform & Books Sales", type: "REVENUE" },
    { code: "4500", name: "Other Income", type: "REVENUE" },
    // EXPENSES
    { code: "5000", name: "Salaries & Wages", type: "EXPENSE" },
    { code: "5100", name: "SSNIT Employer Contribution", type: "EXPENSE" },
    { code: "5200", name: "Utilities (Electricity & Water)", type: "EXPENSE" },
    { code: "5300", name: "Transport Fuel & Maintenance", type: "EXPENSE" },
    { code: "5400", name: "Teaching Materials", type: "EXPENSE" },
    { code: "5500", name: "Repairs & Maintenance", type: "EXPENSE" },
    { code: "5600", name: "Examinations & Assessment", type: "EXPENSE" },
    { code: "5700", name: "Cleaning & Sanitation", type: "EXPENSE" },
    { code: "5900", name: "Miscellaneous", type: "EXPENSE" },
  ];
  const A = new Map<string, string>();
  for (const c of CHART) {
    const row = await db.chartAccount.create({ data: { schoolId, code: c.code, name: c.name, type: c.type, isBank: c.isBank === true } });
    A.set(c.code, row.id);
  }

  // ── bank accounts ──
  const mainBank = await db.bankAccount.create({
    data: {
      schoolId, name: "Main Bank — GCB", bankName: "GCB Bank", accountNumber: "1041230004567",
      chartAccountId: A.get("1010")!, openingBalanceCents: 1_200_000, isActive: true,
    },
  });
  const momo = await db.bankAccount.create({
    data: {
      schoolId, name: "MTN MoMo Wallet", bankName: "MTN Ghana", accountNumber: "0244556677",
      chartAccountId: A.get("1020")!, openingBalanceCents: 85_000, isActive: true,
    },
  });

  // ── journal entries: 10 posted + 2 drafts (last 60 days, Ghana figures) ──
  type Line = [code: string, debit: number, credit: number, memo?: string];
  type Entry = { date: Date; description: string; reference?: string; posted: boolean; lines: Line[] };
  const ENTRIES: Entry[] = [
    { date: day(58), description: "Opening capital injection", reference: "CAP-2026", posted: true, lines: [["1010", 250_000, 0, "GCB deposit"], ["3000", 0, 250_000, "Proprietor"]] },
    { date: day(57), description: "Term fees invoiced — all classes", reference: "INV-TERM2", posted: true, lines: [["1100", 620_000, 0], ["4000", 0, 620_000, "Term 2 tuition"]] },
    { date: day(52), description: "School fees received — cash batch", reference: "RBT-012", posted: true, lines: [["1000", 185_000, 0], ["1100", 0, 185_000, "Receipts batch 12"]] },
    { date: day(50), description: "School fees received — MoMo collections", reference: "RBT-013", posted: true, lines: [["1020", 96_500, 0, "MoMo merchant wallet"], ["1100", 0, 96_500]] },
    {
      date: day(45), description: "Monthly salary accrual (12 staff)", reference: "PAY-012", posted: true,
      lines: [
        ["5000", 84_000, 0, "Gross salaries"], ["5100", 10_920, 0, "Employer SSNIT 13%"],
        ["2100", 0, 21_840, "Tier 1 + Tier 2"], ["2200", 0, 7_560, "PAYE"], ["2300", 0, 65_520, "Net salaries"],
      ],
    },
    { date: day(43), description: "Salaries & SSNIT remitted from bank", reference: "TRF-012", posted: true, lines: [["2300", 65_520, 0], ["2100", 21_840, 0], ["1010", 0, 87_360, "GCB transfer"]] },
    { date: day(36), description: "Utilities paid — ECG & GWCL", reference: "ECG-4471", posted: true, lines: [["5200", 12_400, 0, "Electricity + water"], ["1010", 0, 12_400]] },
    { date: day(30), description: "Petty cash float top-up", posted: true, lines: [["1030", 8_000, 0], ["1010", 0, 8_000, "Cheque 0031"]] },
    { date: monthDay(6), description: "Tuck store stock purchase — on credit", reference: "PO-MEL-7", posted: true, lines: [["1300", 8_600, 0, "Melchizedek Traders"], ["2000", 0, 8_600]] },
    { date: monthDay(5), description: "Tuck shop sales — this week", posted: true, lines: [["1000", 14_250, 0], ["4300", 0, 14_250]] },
    { date: monthDay(3), description: "School bus fuel & servicing", reference: "GOIL-88", posted: true, lines: [["5300", 6_800, 0], ["1030", 0, 6_800]] },
    // drafts
    { date: day(4), description: "Boarding transport fuel — pending approval", posted: false, lines: [["5300", 4_500, 0], ["1030", 0, 4_500]] },
    { date: day(2), description: "BECE registration fee — draft", reference: "WAEC-BECE", posted: false, lines: [["5600", 3_200, 0], ["1010", 0, 3_200]] },
  ];
  const year = now.getUTCFullYear();
  for (const [i, e] of ENTRIES.entries()) {
    await db.journalEntry.create({
      data: {
        schoolId, entryNumber: `JE-${year}-${String(i + 1).padStart(4, "0")}`,
        entryDate: e.date, description: e.description, reference: e.reference ?? null,
        status: e.posted ? "POSTED" : "DRAFT", createdByName: byName,
        ...(e.posted ? { postedAt: new Date(Math.min(e.date.getTime() + DAY, Date.now())), postedByName: byName } : {}),
        lines: { create: e.lines.map(([code, dr, cr, memo]) => ({ accountId: A.get(code)!, debitCents: dr, creditCents: cr, memo: memo ?? null })) },
      },
    });
  }

  // ── main-bank txn feed (14): 4 mirror journal amounts exactly (auto-matchable),
  //    the rest are the operational noise every bursar reconciles by hand ──
  type Txn = { daysAgo: number; dir: "DEPOSIT" | "WITHDRAWAL"; amount: number; ref?: string; desc: string; source?: string };
  const TXNS: Txn[] = [
    { daysAgo: 58, dir: "DEPOSIT", amount: 250_000, ref: "CAP-2026", desc: "Opening capital deposit" },
    { daysAgo: 52, dir: "DEPOSIT", amount: 185_000, ref: "LOD-012", desc: "Cash lodgement — fee receipts batch 12" },
    { daysAgo: 49, dir: "DEPOSIT", amount: 96_500, ref: "MOMO-OUT", desc: "MoMo cash-out transfer", source: "PAYMENT" },
    { daysAgo: 43, dir: "WITHDRAWAL", amount: 87_360, ref: "TRF-012", desc: "Salaries & SSNIT transfer" },
    { daysAgo: 40, dir: "WITHDRAWAL", amount: 4_500, desc: "Bank charges & SMS alert fees" },
    { daysAgo: 36, dir: "WITHDRAWAL", amount: 12_400, ref: "ECG-4471", desc: "ECG electricity + GWCL water" },
    { daysAgo: 33, dir: "DEPOSIT", amount: 62_000, ref: "PTA-LEVY", desc: "PTA development levy lodgement" },
    { daysAgo: 30, dir: "WITHDRAWAL", amount: 8_000, ref: "CHQ-0031", desc: "Petty cash cheque" },
    { daysAgo: 26, dir: "WITHDRAWAL", amount: 9_300, ref: "GOIL-88", desc: "School bus fuel & servicing", source: "EXPENSE" },
    { daysAgo: 15, dir: "WITHDRAWAL", amount: 3_000, desc: "Cleaning supplies — Market Square" },
    { daysAgo: 10, dir: "DEPOSIT", amount: 50_000, desc: "Uniform & books sales lodgement", source: "FEES" },
    { daysAgo: 7, dir: "WITHDRAWAL", amount: 5_600, desc: "Examination printing & photocopying" },
    { daysAgo: 3, dir: "DEPOSIT", amount: 30_000, desc: "Canteen takings lodgement" },
    { daysAgo: 1, dir: "DEPOSIT", amount: 120_000, desc: "Admission fees — new intakes" },
  ];
  for (const t of TXNS) {
    await db.bankTxn.create({
      data: { schoolId, bankAccountId: mainBank.id, date: day(t.daysAgo), direction: t.dir, amountCents: t.amount, reference: t.ref ?? null, description: t.desc, source: t.source ?? "MANUAL" },
    });
  }

  // ── one IN_PROGRESS reconciliation: 6 statement lines, 4 auto-matchable ──
  const stmtDate = day(27);
  // statement end = bank's view: system txns ≤ statement date + one uncleared cheque
  // the bank knows about (−GH₵95.00) that has no system txn — the classic recon gap
  const bankViewTxns = TXNS.filter((t) => t.daysAgo >= 27);
  const statementEndCents =
    1_200_000 + bankViewTxns.reduce((s, t) => s + (t.dir === "DEPOSIT" ? t.amount : -t.amount), 0) - 9_500;
  const recon = await db.reconciliation.create({
    data: { schoolId, bankAccountId: mainBank.id, statementDate: stmtDate, statementEndCents, status: "IN_PROGRESS", createdByName: byName },
  });
  const ITEMS: { daysAgo: number; desc: string; amount: number }[] = [
    { daysAgo: 58, desc: "Capital injection — GCB", amount: 250_000 },
    { daysAgo: 49, desc: "MoMo cash-out transfer", amount: 96_500 },
    { daysAgo: 43, desc: "Salaries & SSNIT transfer", amount: -87_360 },
    { daysAgo: 36, desc: "ECG electricity & GWCL water", amount: -12_400 },
    { daysAgo: 33, desc: "PTA development levy", amount: 61_500 }, // GH₵615 vs GH₵620 system — off by a bank fee, manual work
    { daysAgo: 27, desc: "Cheque 0033 — Melchizedek Traders", amount: -9_500 }, // uncleared: no system txn at all
  ];
  for (const it of ITEMS) {
    await db.reconciliationItem.create({ data: { reconciliationId: recon.id, date: day(it.daysAgo), description: it.desc, amountCents: it.amount } });
  }

  console.log(`✅ Accounts seed complete — ${CHART.length} chart accounts, ${ENTRIES.length} journal entries (${ENTRIES.filter((e) => e.posted).length} posted / ${ENTRIES.filter((e) => !e.posted).length} draft), 2 bank accounts, ${TXNS.length} bank txns, 1 in-progress reconciliation (6 lines, 4 auto-matchable) for ${school.name}`);
  console.log(`   Main bank: ${mainBank.id} · MoMo wallet: ${momo.id}`);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

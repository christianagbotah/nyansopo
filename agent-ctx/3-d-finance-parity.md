# Task 3-d — Finance parity batch (work record)

Implemented the finance-parity batch (receipt/invoice modification-approval queue,
SSNIT Tier-1/Tier-2 printable reports, AR aging report, cashier daily summary +
handover printables) in the Nyansapo Next.js 16 + TS + Prisma/PostgreSQL app at
`/home/z/my-project`. iEduPro CI3 parity.

## Files changed (only these five)
1. `src/app/api/school/[id]/ops/reports/route.ts` — **REPLACED** the stub with the full API
2. `src/components/modules/fees.tsx` — added "Approvals" tab (other tabs untouched)
3. `src/components/modules/payroll.tsx` — replaced the old simple "ssnit" schedule with the
   full printable SSNIT Reports tab (tier toggle, month picker, CI3 layout); converted the
   module to controlled tabs honoring `navTarget?.tab` (runs/staff/ssnit/statutory/ghana)
4. `src/components/modules/finance.tsx` — replaced the old aging summary card with the full
   CI3 aging report tab (colored buckets, detail table, CSV + print)
5. `src/components/modules/cashier-console.tsx` — wrapped the existing console in Tabs
   ("Today's counter" | "Summary & handover"); counter tab markup untouched; navTarget
   deep-link support (`counter` / `summary`)

## API design (ops/reports)
- **GET `?tab=approvals`** — perms `["fees.view","approvals.request","approvals.manage"]` →
  `{pending[], recent[]}` (latest 100, `originalData`/`newData` parsed JSON for the UI).
- **GET `?tab=ssnit&period=YYYY-MM`** — perms `["payroll.view"]`. Period defaults to the
  latest `PayrollRun.period`. Rates read from `OpsSetting` `SSNIT_TIER1_EMPLOYER` (13.5),
  `SSNIT_TIER1_EMPLOYEE` (5.5), `SSNIT_TIER2` (5.0) — CI3 computes from basic × statutory
  rate (uncapped); payslip-stored `ssnitEmployeeCents`/`ssnitEmployerCents` are also exposed
  (CSV carries them for reconciliation). Registration no from `SSNIT_NUMBER` setting else
  school code. Rows include split names (`firstName` + rest, `lastToken`, `otherNames`),
  ssnitNumber, `tier2MemberId` (= `staff.tier2Provider`), computed tier amounts + totals.
- **GET `?tab=aging`** — perms `["finance.view"]`. All invoices with
  `totalCents−discountCents−paidCents > 0`, `writtenOff:false`, status ≠ WRITTEN_OFF.
  Days outstanding from `dueDate` when present else `createdAt` (CI3 advanced aging,
  clamped ≥0). Buckets current 0–30 / d31 / d61 / d90 → `summary{currentCents,currentCount,…}`
  + rows + grand totals.
- **GET `?tab=cashier-summary&date=YYYY-MM-DD&collector=`** — perms `["cashier.reports"]`.
  Day window = UTC midnight→23:59:59.999 (matches daily-route convention). Payments
  (`voided:false, paymentType:"PAYMENT"`) → totals, byCategory, byMethod, txns, distinct
  students, receipts list; all-time OWED `DailyCharge` sum as `dailyArrearsCents`; the day's
  `WalletTxn` TOPUPs; `HandoverReport` rows for the date; `collectors` (distinct
  receivedByName all-time) + `users` (back-office user roster for the handover signature selects).
- **POST `modreq.create`** — perms `["approvals.request","approvals.manage"]`. `recordId`
  accepts **row id OR pasted receipt no / invoice no** (resolved via `OR: [{id},{receiptNo|invoiceNo}]`);
  the **resolved row id** is stored in `recordId` (bug caught & fixed in e2e — see below).
  Snapshots `originalData`; validates `newData.amountCents > 0`. Dedupe: one PENDING per
  recordCode → **409**.
- **POST `modreq.decide`** — perms `["approvals.manage"]` ONLY (iEduPro level-1 admin).
  APPROVED executes inside ONE `db.$transaction`:
  - PAYMENT DELETE → invoice `paidCents -= amount` (clamp ≥0), status recomputed
    PAID/PARTIAL/UNPAID (fees conventions); linked `DailyCharge`s unlinked + reverted to OWED;
    payment row deleted.
  - PAYMENT EDIT `{amountCents, method}` → payment updated, invoice `paidCents` re-synced from
    the live sum of its non-voided payments, status recomputed.
  - INVOICE DELETE → refused (400, whole tx rolls back, request stays PENDING) if it still has
    receipts; else deleted.
  - INVOICE EDIT `{totalCents?, discountCents?}` → totals adjusted + status recomputed.
  - Missing target record → approved with honest "no longer exists" audit note.
  `decisionNote` = "Approved by X. <admin note> <full audit of what changed>".
  REJECTED → no data change.
- **POST `modreq.revoke`** — perms `["approvals.manage"]`; only on APPROVED → REVOKED,
  appends "revoked — manual reversal required" (CI3 audit posture; NO auto data reversal).
- **POST `handover.save`** — perms `["cashier.reports"]`; deleteMany+create per
  (school, cashierId, day) = upsert-style replace (one per cashier+date).

## UI
- **fees.tsx → Approvals tab**: pending count badge on the tab trigger; 4 StatCards
  (pending/approved/rejected/revoked); pending queue cards (target badge, code, EDIT/DELETE
  badge, reason, requester, original→proposed amounts); Approve/Reject open a decision dialog
  with optional note — rendered only when `canAny(session.permissions, ["approvals.manage"])`;
  Recent decisions table with full audit trail + Revoke button on APPROVED rows; "Request
  modification" dialog (searchable term register or pasted receipt/invoice no, target/type,
  corrected amount+method / invoice total for EDIT, reason). `navTarget?.tab==="approvals"` honored.
- **payroll.tsx → SSNIT reports tab**: month picker (defaults to selected run period), Tier 1 |
  Tier 2 toggle, `print-area` A4 schedule — header "Social Security and National Insurance Trust
  Contribution Report" + establishment + Registration No. + Year & Month; Tier 1 columns
  S/N · SSNIT No. · First Name · Last Name · Option (ACT 766) · Hazd. (NO) · Salary · SSNIT
  (13.5% employer) + TOTAL; Tier 2 columns S/N · Tier 2 Member ID · Last · First · Other ·
  Basic Salary · Tier 2 Contribution (5%) + provider name line + TOTAL. Print (A4) + CSV export
  (CSV also carries the payslip-stored cents).
- **finance.tsx → Aging tab**: 4 clickable bucket cards in CI3 colors #10b981 / #f59e0b /
  #ef4444 / #991b1b (click to filter), detail table Student | Class | Invoice | Date | Days
  outstanding | Balance | colored bucket badge, bucket totals footer strip, CSV + print.
- **cashier-console.tsx → Summary & handover tab**: date + cashier filter, printable Daily
  Collection Summary (totals, transactions, learners served, all-time daily arrears, fee-type
  grid, method grid, wallet top-ups) + printable Cash Handover Report (collections summary,
  method mix, two dashed "Handed Over By"/"Received By" signature boxes with signature-date
  lines, Shift: Full Day); handover-party selects (defaults to session user as cashier) +
  Save handover; saved handovers for the date listed below.

## Verification (curl, server 127.0.0.1:3000, school GEA `cmvycnn80000zowqnvv4wfnq9`)
Logins: `super@nyansapo.gh` / `cashier@nyansapo.gh`, password **`nyansapo`** (the DB seed
value — the task brief's "/nyansapo" does not match the seeded hash), auth via `x-user-id`.
- GET approvals (cashier & super) 200 — seeded pending visible.
- modreq.create DELETE (cashier, on a fresh CASH payment recorded via /fees API) → 200;
  duplicate create → **409**; cashier decide → **403**; super APPROVE → invoice
  INV-2026-0002 paidCents 500.00 → 0.00, PARTIAL → UNPAID, payment gone (verified via psql).
- EDIT path: create EDIT {30000 CASH → 45000 MOMO_MTN}, super approve → payment updated,
  invoice paidCents re-synced 300.00 → 450.00.
- Reject path → REJECTED, no data change. Revoke path → REVOKED + "manual reversal required".
- **Bug found in e2e**: create-by-pasted-receipt-no stored the receiptNo in `recordId`, so
  decide found no payment ("no longer exists"). Fixed to store the resolved row id; regression
  test re-run → "Invoice INV-2026-0002 paidCents reversed GH₵550.00 → GH₵450.00 … Receipt
  RCP-2026-03557 deleted" ✓.
- SSNIT GET 2026-08 & 2026-09 & default: rates 13.5/5.5/5 from OpsSetting; payslip math
  verified base 85000 → 11475 / 4675 / 4250; totals sum to row sums ✓.
- Aging GET: buckets sum == total == Σ row balances; counts == rows (66 invoices, GH₵131,874.96).
- cashier-summary 2026-09-16: total 1902502 == ΣbyCategory == ΣbyMethod; 12 txns; cash 116622;
  daily arrears 255250; handover saved (API) and again via the browser UI (Save handover →
  POST 200 → re-fetch shows Lawrencia → Patrick, total 1902502, cash 116622).
- Teacher (no cashier.reports) handover.save → 403.
- **Browser smoke test (agent-browser, signed in as super)**: Fees → Approvals tab renders
  (badge "1", Request modification, Approve/Reject, audit trails); Payroll → SSNIT reports
  (Tier 1 ACT 766 table, Registration No. GEA, Year & Month October 2026; Tier 2 provider line
  "Petra Trust, Enterprise Trustees"); Finance → Receivables ageing (4 colored buckets, 66
  invoices); Cashier Console → Summary & handover (date change to 2026-09-16 reloads data;
  Save handover round-trip verified). Screenshots in `tool-results/`.

## Quality gates
- `npx tsc --noEmit` → **0 errors** project-wide (my 5 files clean throughout; transient
  errors from parallel agents' in-flight files resolved by them).
- `eslint` on the 5 files → **0 errors**, 1 pre-existing warning (fees.tsx:246 ternary,
  untouched original line).
- Dev server note: it crashed several times during the session — once from a parallel agent's
  transient `@/components/progress` import error (blocked ALL routes; resolved by that agent),
  otherwise the shared dev process died and was restarted with `bun run dev`.

## Conventions / notes for future agents
- Invoice status recompute after any payment mutation:
  `paid >= total-discount → PAID; paid <= 0 → UNPAID; else PARTIAL` (matches fees route).
- The seeded modreq/handover demo rows come from `prisma/seed-parity2.ts`.
- `PayrollRun.period` is `"YYYY-MM"` (unique per school) — SSNIT report resolves periods from it.
- Money everywhere in integer pesewas; CSVs export cedis (÷100).

# Task — Student Credit Management (prepaid credit wallets from overpayment)

Implemented the CI3-parity student credit system in the Nyansapo app at
`/home/z/my-project`: overpayments at the counter become per-learner prepaid
credit (StudentCredit ledger), auto-applied at billing, manageable via a new
Credits tab, transferable between learners, convertible to the daily-services
wallet, shown on receipts and terminal bills.

## Files changed (only these seven)
1. `src/app/api/school/[id]/[resource]/route.ts` — **record-payment** (fees case only):
   now accepts amounts ABOVE the invoice balance. `min(amount, balance)` settles the
   invoice (PAID/PARTIAL as before); the excess is stored on the payment in full
   (`payment.amountCents = full received`) and banked as `StudentCredit kind=CREDIT,
   amountCents=+excess, sourcePaymentId=receipt.id, byName=user.fullName`, with
   running `balanceAfter = Σ signed rows`. Optional `bankName`/`chequeNo` now pass
   through to `payment.create`. Response adds top-level `creditCreated` and spreads
   `creditCreated` into the receipt payload. Receipt SMS mentions the credit when
   one is created. ClientId idempotency + offline-queue logic untouched. Paying into
   an already-PAID invoice is now allowed (whole payment becomes credit — CI3 advance).
   **generate-invoices** (same file): after the discount block, each fresh invoice
   auto-applies the learner's credit: `APPLY` ledger row `−min(balance, due)`,
   `invoiceId` set, `paidCents = applied`, status PAID/PARTIAL; response now also
   returns `creditsApplied`.
2. `src/app/api/school/[id]/ops/finance/route.ts` — new endpoints extending the
   action pattern (GET branch parses `?action=` before auth so credits GETs can use
   `["fees.view","finance.view"]` any-of instead of the dashboard's finance.view):
   - GET `?action=credits` → `{rows:[{studentId,name,classroom,balanceCents,records,lastAt}],
     stats:{activeCents,studentsWithCredits,appliedCents,createdCents,utilizationPct},
     students:[roster for pickers]}`. Utilization = applied/created where
     created = kind CREDIT + positive ADJUST (outside money in), applied = kinds
     APPLY + DAILY_WALLET (consumed on school bills); transfers are internal moves,
     excluded from both sides.
   - GET `?action=credit-history&studentId=` → ledger rows asc + current balance
     (invoiceNos resolved in a 2nd query — `StudentCredit.invoiceId` is a raw column,
     no Prisma relation).
   - POST `credit-create` / `credit-adjust` (note REQUIRED on adjust — audit trail;
     overdraw below 0 refused 400) / `credit-transfer` (two rows TRANSFER_OUT/TRANSFER_IN
     in one $transaction, insufficient balance 400, same-learner 400) /
     `credit-to-daily` (DAILY_WALLET −x row + StudentWallet.prepaidCents += x + WalletTxn
     TOPUP row, one $transaction — mirrors the daily-route wallet-topup pattern).
     All gated any-of `["fees.manage","fees.collect"]` (like record-payment).
   - Helper `creditBalance(schoolId, studentId)` = one aggregate Σ query at file bottom.
   - **terminal-bills** action: bills now carry `credit` (learner's credit balance),
     used by the finance UI's LESS line.
3. `src/components/modules/fees.tsx` — new **Credits** tab (trigger between Arrears and
   Approvals, deep-link `navTarget.tab === "credits"` honored): 4 StatCards (active
   credits / learners with credit / credits applied / utilization %), search filter,
   wallet table (learner+class, balance, records, last credit date; actions History /
   Adjust / Transfer / Daily wallet), "Add credit" button. Dialogs: CreditHistoryDialog
   (shared at Fees level — opened from the tab AND the PayDialog widget; ledger table
   with kind badges, ± amounts, balance-after, note, by, invoice no), AddCreditDialog +
   TransferCreditDialog with a searchable LearnerPicker (roster from the API),
   AdjustCreditDialog (± with mandatory note + live resulting-balance preview, overdraft
   pre-blocked), ToDailyWalletDialog (defaults to full balance). **PayDialog**: fetches
   the learner's credit on open; when > 0 shows a green box "Prepaid credit available:
   GH₵x — applied automatically to the next generated bill" with a "View history" link
   (opens the shared history dialog); amount hint "Amounts above the balance are banked
   as prepaid credit"; toast when an overpayment banks a credit.
4. `src/components/modules/finance.tsx` — TerminalBill: new emerald
   **"LESS: Prepaid credit applied"** row (only when the learner's credit balance > 0)
   between the arrears rows and the transport row; `creditApplied =
   min(credit, termArrears + dailyArrears)` (credit never offsets the FUTURE bus fare),
   `Total to settle / bring for resumption` + amount-in-words recomputed accordingly.
   TerminalBill type + `credit: number` field.
5. `src/components/receipts.tsx` — the four receipt styles already rendered
   `creditCreated` (type field pre-existed); aligned the FORMAL style wording to
   "CREDIT CREATED — GH₵x applied to future bills" to match the CI3 line exactly.
6. `prisma/seed-credits.ts` (NEW, `seed.ts` untouched) — exports `seedCredits()`;
   standalone-runnable. For GEA (looked up by school code): Linda gets a GH₵40
   overpayment credit from a real manual Payment row (sourcePaymentId-linked, bursar)
   of which GH₵15 is TRANSFER_OUT to sibling Elikem (→ GH₵25 balance); Dzifa gets a
   GH₵10 manual credit (head, PTA welfare); Elikem ends with GH₵15 TRANSFER_IN — a
   genuine transfer pair, 3 learners with balances 25/10/15. Idempotent: skips when
   any StudentCredit row exists for the school. Ran twice (2nd run skipped).
7. `scripts/smoke-credits.ts` (NEW) — smoke-curriculum.ts pattern (PrismaClient +
   fetch, `x-user-id` header auth, head = SCHOOL_ADMIN, teacher = RBAC negative).

## Smoke test results — 🎉 40 passed, 0 failed
(a) overpay GH₵20 → payment stores full amount, invoice PAID with only balance
    applied, credit row kind=CREDIT receipt-linked, `creditCreated` on response AND
    receipt payload; (b) list+stats (balance row, activeCents, studentsWithCredits,
    utilization numeric, roster); (c) adjust +5/−3 with correct running balance,
    overdraft 400, missing note 400; (d) transfer A→B TRANSFER_OUT/TRANSFER_IN pair
    with balanceAfter, insufficient 400; (e) credit-to-daily → prepaidCents +400,
    WalletTxn TOPUP row, DAILY_WALLET −400 row; (f) credit-create → delete a
    payment-free invoice → generate-invoices → fresh invoice has paidCents =
    min(credit, due), status PAID/PARTIAL, APPLY ledger row; original invoice
    restored from snapshot; (g) teacher 403 on credit-adjust/credit-create/credits
    list. Cleanup verified: all test credit rows / payments / invoices / wallet
    txns / automated SMS removed, ledger back to pre-test state, overpaid invoice
    restored (GEA autoPaymentReceipts + autoFeeReminders are ON, so SMS cleanup was
    needed). Extra edge test (manual, cleaned up): overpaying a PAID invoice → whole
    payment becomes credit, bankName/chequeNo stored + rendered on receipt.

## Quality gates
- `npx tsc --noEmit` → **0 errors project-wide** (transient admissions-route errors
  from a parallel agent resolved on re-run).
- `eslint` on my 7 files → **0 errors**, 1 pre-existing warning (fees.tsx receipts
  checkbox ternary — original line, shifted).
- Fixed 2 `react-hooks/set-state-in-effect` errors by switching the Credits tab and
  history dialog to a `loadedFor`-key pattern (no sync setState in effects).
- Dev server: died twice during the session (shared process); restarted detached
  (`setsid nohup bun run dev`), `GET /` 200 (all modules incl. fees/finance/receipts
  compile), no 500s in dev.log.

## Deliberate divergences
- **Payment stores the FULL received amount** (not just the applied part) — the cash
  book must reflect money actually taken; the excess sits as credit liability.
- **Utilization denominator** counts only outside money (CREDIT + positive ADJUST);
  transfers excluded so sibling moves don't dilute the ratio. DAILY_WALLET counts as
  "applied" (terminal consumption of credit).
- **Terminal-bill credit offsets term + daily arrears but NOT the future bus fare**
  (credits are school-charge scope; the fare is a next-term optional).
- **credit-adjust requires a note** (audit posture, like wallet corrections).
- **GET credits gate is any-of fees.view/finance.view** because the Credits tab lives
  in the Fees module (cashiers have fees.view but not finance.view).
- Paying an already-PAID invoice now succeeds as a pure credit top-up (old code
  rejected with "Amount exceeds balance"); the UI Pay button stays disabled at
  balance 0, so this is API/counter-flow only.

## Known gaps
- **Reprints** via the `receipt-payloads` action (and `lib/receipt-payload.ts`, which
  I do not own) do not re-derive `creditCreated` from StudentCredit — only the
  freshly-recorded payment response carries it. The note appears on first print;
  a later "Reprint" of the same receipt omits the credit line. One-line fix in
  `invoicePaymentReceipt` (query StudentCredit by sourcePaymentId) for whoever owns
  that file.
- Credits tab lists learners with ANY ledger rows (zero-balance included) rather
  than only positive balances — intentional (history/adjust still useful), stats
  count only positive balances.
- generate-invoices response gained a `creditsApplied` field (additive; UI "generate"
  flow shows created count only — Fees → structures tab doesn't surface it yet).

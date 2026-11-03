# Task 13 — Offline per-item collection parity (work record)

Closed the follow-up left by task 12: the offline action queue's DAILY
flush replayed only the legacy single-amount shape, losing per-item
settlement, advances and the register/SMS side effects. Wave 5 makes the
offline replay bit-for-bit equivalent to the online counter.

## 1. Shared collection core (`src/lib/daily-billing.ts`)
- `sanitizeCollectItems(raw)` — validates a queued/POSTed `items[]`
  payload into `{ serviceId, amountCents }` entries (0/negative dropped,
  "at least one fee item" when empty).
- `collectDailyItems(db, opts)` — the full per-item collection: mints the
  receipt (`nextReceiptNo`), settles each item's arrears FIFO
  (`settleDailyCharges … serviceId`), banks the excess on that item via
  `creditItemBalance` (advance — RCT), classifies payment_type
  (arrears | current | advance | mixed), and writes the daily_fee_audit
  row (per-item breakdown in the notes, ref, arrears before/after).
  Takes `offlineQueued`/`syncedAt`/`receivedByName` so the same core
  serves live and replayed payments.
- `markPresentForDate(db, { schoolId, studentId, date })` — the POS
  "mark present" hook extracted from the route (no-class / no-session /
  marked / updated / already).

## 2. Online route (`ops/daily` → `pos-collect` items path)
- The in-line payment/loop/audit block was replaced by one
  `collectDailyItems` call + one `markPresentForDate` call. Behavior is
  unchanged (verified by the full regression); gate/SMS/receipt/response
  stay in the route.

## 3. Sync handler (`/api/sync`, DAILY branch)
- New `items[]` path, taken when the queued payload carries items: the
  POS dialog has always queued them (`apiPostQueueable` spreads the full
  payload), the handler just ignored them.
  - school-scoped service validation (same 400-family error string),
  - transport-usage replay **before** settlement
    (`applyTransportUsage` — the day's stop fare materialises at the
    paid date; NOT_USING waives at 0),
  - `collectDailyItems` with `reference: SYNC:<clientId>`,
    `receivedByName: "<user> (offline)"`, `offlineQueued`, `syncedAt`,
  - mark-present replay onto that day's register,
  - auto receipt SMS (same `PAYMENT_RECEIVED` trigger + default body as
    the counter) — fires when the device reconnects.
- Idempotency unchanged: the pre-existing `payment.reference =
  SYNC:<clientId>` dup check short-circuits the whole op ("already
  synced"); per-op try/catch marks malformed entries failed
  (unknown service / all-zero items) without sinking the flush.
- Legacy single-amount entries (older clients, quick-pay) keep the old
  FIFO-across-items path, capped at owed.

## 4. Smoke suite — `scripts/smoke-daily-sync.ts` (40 checks)
A. per-item flush basics: FIFO split (300 PAID + 100/200 split),
   isolation (water untouched), offline flags on the payment, audit
   before/after delta, receipt detail string
B. excess → item advance (mixed), ITEM TOPUP txn, audit "(+GH₵7.00
   advance)" note
C. pure advance on an item with no charges
D. idempotent re-flush (same clientId → "already synced", no duplicate)
E. transport usage BOTH replay: charge at 2 × stop fare, PAID with
   paymentId in the same flush, CHARGE audit row
F. NOT_USING waives at 0
G. mark-present replay (record created, detail "present marked")
H. legacy single-amount path (capped, FIFO attached to oldest charges)
I. auto receipt SMS at flush (SENT, exactly 1 credit, "(offline)" by-line)
J. malformed payloads fail the op, not the flush
Cleanup keys off the `SYNC:syncsmk-` reference prefix (backdated `paidAt`
values escape a `paidAt >= testStart` window — the first run left
state that made run 2 report "already synced" and drifted the receipt
counter into a P2002; the prefix selection restores the
minted-==-deleted invariant, so the counter rollback is exact again and
the suite is re-runnable).

## Verification
- New suite 40/40 (run twice back-to-back to prove re-runnability).
- Full regression: **538 checks, 0 failures** across 16 suites — codes
  23, credits 40, curriculum 29, attendance-analytics 29, bulk-admission
  48, creche-sba 64, staff-dashboard 51, admissions-extra 14, fees-parity
  9, lesson-note-sms 32, preferences 11, media 27, sms-gateway 39,
  daily-rates 34, daily-sync 40, portals 24+24. `tsc --noEmit` clean;
  eslint clean on all changed files.
- No browser pass this wave: the client diff is zero (the dialog already
  queued `items[]`/`transportUsage`/`markPresent`; only the server
  learned to read them), and the suite drives the exact production
  endpoint with the exact payload shape the client mapper emits.
- Sandbox was recycled mid-wave: `node_modules` + the embedded PG
  cluster had to be rebuilt (bun install, initdb, db:push, reseed
  chain). Nothing was lost — everything was committed, exactly why the
  "commit and push" discipline exists.

## Notes for later agents
- `collectDailyItems` is now the ONLY place that mints DAILY receipts
  with per-item allocation — route and sync both call it; don't fork it
  again.
- The sync route still processes ops sequentially (no wrapping
  transaction per op); a mid-op failure can leave the payment row
  without settlement. The per-op catch reports it; the client keeps the
  queue entry? (No — flushQueue drops successfully-flushed entries and
  reports failures; a failed entry stays queued for the next flush,
  where the SYNC dup check makes it a no-op if it half-landed.)
- Receipt SMS for offline collections fires at flush time with the
  receipt number of the just-minted receipt — accurate by construction.

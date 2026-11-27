# Wave 77 — Parent payments & the School Shop

## What shipped
Parents can now **pay from the portal** and **order from the school shop**; the
shop attendant fulfils from a dedicated console, and every step texts the
guardian.

### Online payments (parent self-service)
- The existing payments engine (`src/lib/payment-requests.ts` — Hubtel MoMo
  prompts + hosted checkout links, SIMULATED fallback, idempotent ledger
  application) gained a `SHOP_ORDER` purpose and is now driven by the
  **parent portal**:
  - `fees.pay-online` — settle any invoice (full balance prefilled, partial
    allowed), excess → StudentCredit as before.
  - `wallet.topup` — prepaid (tuck/meals) or transport wallet, credited via
    the WalletTxn ledger on completion.
  - `shop.order-pay` — settles a placed shop order.
  - `payment.check` — parent-scoped status poll (3 s) that also advances
    live-Hubtel requests (checkout token / txn status).
- Parent-side auth: role must be PARENT and the student must be in
  `linkedStudentIds`; staff accounts are refused all three.
- Sandbox behaviour: with no Hubtel keys, `paymentsMode=SIMULATED` and the
  pay dialog shows the amber sandbox notice + "Open payment page"
  (`/pay/{ref}`) where the flow completes; live keys flip to MoMo prompts /
  hosted checkout automatically (no UI change needed).

### School shop
- Schema: `ShopItem` (name/category/priceCents, stock nullable = untracked,
  lowStockAt, active), `ShopOrder` (orderNo SO-00001 per-school atomic
  counter, parent link, ward, deliveryNote, lifecycle
  PENDING_PAYMENT→PAID→READY→DELIVERED|CANCELLED, paymentMethod ONLINE|COUNTER),
  `ShopOrderItem` (name/price/qty snapshotted), `PaymentRequest.shopOrderId`,
  `SmsSetting.evtShopOrders` (default true).
- Parent portal **Shop tab**: catalog grid with category filter + stock guard
  (over-stock refused server-side), basket, checkout (ward + delivery note +
  pay online now / pay at counter), My orders with status chips, Pay-online
  and Cancel (only while PENDING_PAYMENT) actions.
- Staff **School Shop module** (`shop` in the shell, `shop.view`/`shop.manage`
  perms; ACCOUNTANT/CASHIER/NON_TEACHING defaults): orders queue with filter
  chips + counters, counter payment mints a numbered receipt
  (`nextReceiptNo`, Payment category SHOP, method selectable) — receipt-first
  then CAS the order so a double click can never double-charge — pack →
  deliver trail, cancel with reason (PAID orders warn to arrange refund),
  catalog CRUD with inline ±stock steppers, delist/relist, delete refused
  once an item has order history, low-stock strip.
- Lifecycle transitions decrement tracked stock (tolerant of concurrent
  sales — logged, never blocks) and text the guardian (SHOP category, gated
  on `evtShopOrders`): paid (online or counter, includes receipt no), packed,
  delivered.
- Seed: 10-item GEA starter catalog in seed-ops (guarded by count check).

### SMS providers
Unchanged plumbing — the shop/portal events ride the existing
`sendSms` dispatch (SIMULATED | ARKESEL | TERMII | HUBTEL, credits-gated), so
a school picks its provider in Settings and both the payment receipts and the
shop trail follow it.

## API surface
- `GET/POST /api/school/[id]/ops/shop` — opsAuth perms shop.view / shop.manage
- `POST /api/school/[id]/ops/portal` — new actions `shop.order-create`,
  `shop.order-pay`, `shop.order-cancel`, `fees.pay-online`, `wallet.topup`,
  `payment.check`; GET parent view now carries `shopItems`, `shopOrders`,
  `paymentRequests`, `paymentsMode`.

## Verification
- `scripts/smoke-shop.sh` — 13 steps, ALL GREEN:
  shape, RBAC (teacher 403 ×3, foreign learner 403), catalog CRUD + bad-price
  refusal, order create (stock untouched), over-stock refusal, cancel/reorder,
  simulated MoMo completion (order PAID/ONLINE, stock 10→8), double-simulate
  refused, counter payment (RCP-2026-…, stock 7), deliver-before-ready 409,
  pack→deliver, wallet top-up (0→₵20 via WalletTxn), partial fee payment
  (₵2, paid +200). Then `cleanup-shop-smoke.ts --verify` checks the ledger in
  the DB (4 SHOP/fee Payment rows, receipt-no shapes, 1+3 order SMS trail,
  3 PAYMENT receipt SMS, wallet txn, stock drawn) before restoring everything.
- Browser E2E: parent paid ₵50 of Ama's invoice through the dialog (poll
  flipped to "Paid — Invoice … settled ₵50.00"), placed SO-00013 (2× exercise
  book, 3× pen, delivery note) → pay page → PAID → My orders "Paid — being
  packed"; bursar packed + delivered it from the new console (stat tiles +
  toasts "the parent has been notified"); SMS trail verified in the DB;
  catalog stock 120→118→restored; mobile 390px layout checked on both sides.
- Screenshots: agent-ctx/w77-parent-pay.png, w77-parent-shop.png,
  w77-shop-console.png, w77-shop-mobile-final.png.

## Gotchas
- Bun resolves `@prisma/client` from its global cache for scripts under /tmp —
  run DB scripts from `scripts/` (or the project root) and always prefix
  `DATABASE_URL='postgresql://nyansapo@127.0.0.1:5433/…'` (the client's dotenv
  overrides the shell env otherwise… inconsistently; prefix + project dir wins).
- The 4 GB sandbox OOM-killed next-server (2.7 GB RSS) mid-E2E when turbopack
  compiled the new chunks alongside lint/tsc — restart with
  `bash scripts/ensure-server.sh` and let the root page settle before
  navigating. Not an app bug; console was clean before/after.
- The smoke's cancelled first order is intentionally not part of the state
  file (cancel path has no ledger effects) — the E2E cleaned it separately.

# Wave 78 — Multi-gateway collections (Paystack + Flutterwave)

## What shipped
The w77 payment engine now rides **three Ghana gateways**: Hubtel (MoMo
prompts + hosted checkout), **Paystack** and **Flutterwave** (hosted
checkouts — card, bank transfer & every Ghana MoMo wallet on one page).
Every request names its gateway; the ledger layer is unchanged.

### Provider model (`src/lib/payment-requests.ts`)
- `PAYMENT_PROVIDER_LABELS` + `availablePaymentProviders()` (env-gated:
  HUBTEL_* trio, `PAYSTACK_SECRET_KEY`, `FLUTTERWAVE_SECRET_KEY`) +
  `resolvePaymentProvider(requested)`: an explicit choice must be configured —
  unless the deployment has NO keys at all, in which case any labelled
  gateway runs **SIMULATED** (sandbox rehearse, same philosophy as SMS).
- `isSimulatedRequest()` — SIM- token/txn marks sandbox rows; they never poll
  a gateway and are the only rows the pay page may complete by button.
- References are gateway-prefixed: `HTL-` / `PSK-` / `FLW-` (≤ 32 chars).
- Paystack/Flutterwave always collect via PAYMENT_LINK (hosted checkout);
  direct MoMo prompts stay Hubtel-only.
- `applyCompletion` labels are provider-aware (`Paystack — {payer}`, receipt
  SMS `sentByName` "{provider} payments"). Ledger math untouched.

### Adapters (new files)
- `src/lib/paystack.ts` — `paystackConfigured`, `paystackInitTransaction`
  (POST /transaction/initialize, pesewas = our cents), `paystackVerify`
  (abandoned stays PENDING), `parsePaystackEvent`, HMAC-SHA512 webhook check.
- `src/lib/flutterwave.ts` — `flwInitPayment` (POST /v3/payments → data.link,
  decimal GHS at the boundary), `flwVerify` (verify_by_reference),
  `parseFlwEvent`, verif-hash = SHA-256(secret) webhook check.

### Webhooks
- `/api/webhooks/paystack` — x-paystack-signature (HMAC-SHA512 of raw body);
  `/api/webhooks/flutterwave` — verif-hash (SHA-256 of secret). Both: amount
  mismatch defence, event filtering, unknown-ref ignore, 500 → gateway
  retry, CAS idempotency via `applyCompletion`. **Sandbox mode** (no keys):
  unsigned callbacks accepted only for clearly-sandbox references
  (PSK-/FLW- prefix) so smoke can rehearse the webhook path; anything else
  → 401.

### API surface
- portal `fees.pay-online` / `wallet.topup` / `shop.order-pay` accept
  `provider`; GET parent view carries `paymentsProviders` (+ `provider` on
  paymentRequests rows).
- ops/payments `create` accepts `provider`; GET carries `providers`;
  `simulate-fail` now guards on `isSimulatedRequest` (any labelled gateway).
- public pay GET: `providerLabel` + request-level `simulated`; POST simulate
  requires simulated row AND sandbox deployment.

### Frontend
- Parent portal `PayDialog` + `ShopCheckoutDialog` + bursar MoMo console:
  shared **GatewayPicker** chips (Hubtel / Paystack / Flutterwave) with
  live/sandbox/not-set-up states; non-Hubtel choice swaps "Pay with" to a
  static secure-checkout hint; tracking panel is provider-aware; fees-tab
  history rows show "via {provider}".
- /pay/{ref}: "Secured by {provider} · Nyansapo School Management".

### SMS (user ask re-verified, no changes needed)
Hubtel + Arkesel + Termii + SIMULATED dispatch already existed
(`src/lib/sms.ts`, SMS Centre settings dropdown) — payment/top-up/order
notifications ride it (`autoPaymentReceipts`, `evtShopOrders`).

## Verification
- `scripts/smoke-providers.sh` — 13 steps ALL GREEN (from pristine baseline):
  shape, RBAC, Paystack wallet top-up completed by sandbox webhook
  (PSK- ref, wallet 0→₵12), webhook defences (amount mismatch ignored,
  unknown ref ignored, foreign ref 401), replay no-double-post (CAS),
  Flutterwave top-up via pay page (FLW- ref), Paystack partial fee payment
  (INV balance −₵10), payment.check inert on simulated rows, Flutterwave
  shop order (PAID/ONLINE + stock 5→4), FLW webhook defences, bursar console
  provider labels. Then `cleanup-providers-smoke.ts --verify` — 8 ledger
  checks green (4 Payments RCP-2026-…, 2 WalletTxn, fees receipt on the
  invoice, stock drawn, 4 PAYMENT SMS + 1 SHOP SMS) and full restore
  (wallet 0, invoice paid=137004 PARTIAL, stock 5, order + SMS removed).
- Browser E2E (parent@nyansapo.gh): fees Pay now → **Paystack chip** →
  sandbox notice → /pay page ("Secured by Paystack") → I have paid →
  dialog flipped "Paid — Invoice INV-2026-0054 — settled ₵50.00"
  (RCP-2026-00520); wallet card → Top up → **Flutterwave chip** → paid
  (FLW-261127-I9GC5D); Shop → basket → checkout → gateway picker →
  **Paystack** → SO-00004 paid → bursar console pack → **delivered** to the
  learner ("the parent has been notified"). Mobile 390px pay dialog checked.
  Screenshots: agent-ctx/w78-pay-dialog-paystack.png, w78-tracking-paystack.png,
  w78-pay-page-paystack.png, w78-pay-page-paid.png, w78-dialog-paid.png,
  w78-wallet-flw-dialog.png, w78-wallet-flw-paid.png,
  w78-shop-checkout-paystack.png, w78-shop-order-paid.png,
  w78-shop-delivered.png, w78-mobile-pay-dialog.png.
- Kept as demo data: exercise-book catalog item, ₵25 tuck wallet on Ama,
  the ₵50 fee receipt + delivered SO-00004 (legitimate ledger rows).

## Gotchas
- Sandbox reset between sessions wiped node_modules + scripts/pg/data —
  recover with `bash scripts/pg/restore-cluster.sh` (backups/latest-cluster.tar.gz)
  then `bash .zscripts/restore-all.sh`; DB scripts must run from `scripts/`
  with DATABASE_URL prefixed (Bun global-cache gotcha, w77).
- 4 GB sandbox OOM-killed next-server (2.3 GB RSS) mid-smoke twice while
  turbopack compiled cold chunks; warm every route first or run the smoke
  on an already-compiled server. Watchdog/ensure-server recovers it.
- Smoke-script lesson: unquoted `$VAR` with spaces inside a single-quoted
  JSON body word-splits and truncates the payload (KPHONE "+233 24 818
  3344" → `{"error":"Unknown action"}`) — quote expansions or precompose
  bodies into variables. MultiEdit batches are NOT atomic here: a failed
  old_str can leave earlier edits applied — re-read the file after any
  batch failure.
- origin/main is stale (diverged pre-w63); push with `--force-with-lease`.

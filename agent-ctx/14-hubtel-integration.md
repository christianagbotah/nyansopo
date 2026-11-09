# Task 14 — Hubtel integration: SMS provider + MoMo payments (work record)

User: "we will use hubtel for sms and payment and other features needed from
their portal. you can check from this current browser, i have logged in
https://developers.hubtel.com/docs/business/home."

The Hubtel developer docs portal sits behind a phone-OTP login (my sandbox
browser has no shared session with the user's), so the API surface was
researched from Hubtel's own published PDFs, SDKs (ovac/hubtel-payment,
paulmajora/hubtelpayment), the news.hubtel.com SMS FAQ and production
integration write-ups. Everything is env-credential driven — the user only
needs to drop their Hubtel keys into the deployment env to go live.

## 1. Hubtel client (`src/lib/hubtel.ts`)
- Auth: HTTP Basic `clientId:clientSecret` on every endpoint; keys ONLY in
  env (never the DB): `HUBTEL_CLIENT_ID` / `HUBTEL_CLIENT_SECRET` /
  `HUBTEL_MERCHANT_ACCOUNT_NUMBER` (+ `SMS_HUBTEL_*` overrides for
  Programmable-SMS keys), `HUBTEL_API_BASE` (default api.hubtel.com),
  `HUBTEL_SMS_BASE` (default smsc.hubtel.com), `HUBTEL_CALLBACK_BASE`,
  `HUBTEL_WEBHOOK_SECRET` (optional `?secret=` on the callback).
- Endpoints (v1 Merchant Account / Sales API):
  - SMS `POST {sms}/v1/messages/send` `{From, To, Content}`
  - Receive money `POST {api}/v1/merchantaccount/merchants/{acc}/receive/mobilemoney`
  - Send money `POST …/send/mobilemoney` · Refund `POST …/transactions/refund`
  - Txn status `GET …/transactions/status?TransactionId=&ClientReference=`
  - Checkout `POST {api}/v1/merchantaccount/onlinecheckout/invoice/create`
    (+ `GET …/invoice/status/{token}`)
- `detectMomoChannel` (024/054/055/059/025→mtn-gh, 020/050→vodafone-gh,
  026/027/056/057→tigo-gh), `newClientReference` (HTL-YYMMDD-XXXXXX, ≤32),
  multi-generation success parsing (ResponseCode 00/0000, status Success,
  checkout token/URL shapes).
- SMS gateway: `hubtelSmsSend` adapter + HUBTEL branch in `dispatch`
  (lib/sms.ts), provider allowlist ["ARKESEL","TERMII","HUBTEL"], status in
  `gatewayConfigStatus()`, SMS Centre provider picker shows "Hubtel (Ghana)"
  with key-detected hints. `toGhanaIntl` moved to `src/lib/phone.ts`
  (breaks the lib↔gateway import cycle).

## 2. Payment requests (schema + engine)
- Prisma: `PaymentRequest` (school/student, purpose INVOICE | DAILY_ITEMS |
  ITEM_BALANCE | WALLET, items JSON, walletKind, amountCents, method
  MOMO_PROMPT | PAYMENT_LINK, channel, payer, status, provider HUBTEL |
  SIMULATED, clientReference unique, providerTxnId/checkoutToken/
  checkoutUrl, appliedPaymentId→Payment, appliedNote, failureReason,
  events[]) + `PaymentEvent` audit trail (INITIATED/CALLBACK/STATUS_CHECK/
  COMPLETED/FAILED/CANCELLED/LINK_SHARED/ERROR with raw payloads).
- Engine (`src/lib/payment-requests.ts`):
  - `createPaymentRequest` — validates purpose × inputs (invoice belongs to
    learner & open, item sums = total, ≥GH₵0.50 Hubtel minimum, learner
    enrolled in school), normalises payer MSISDN, calls Hubtel FIRST so a
    gateway failure leaves no dangling row; SIMULATED fallback mirrors the
    SMS gateway (local prompt/link, public page can simulate).
  - `checkPaymentRequest` — polls checkout token / txn status; completed →
    apply, failed → fail, cancelled → cancel.
  - `applyCompletion` — ATOMIC claim (`updateMany status PENDING→COMPLETED`)
    so webhook + status-check + simulate can race safely; then posts to the
    right ledger: INVOICE (payment + invoice.paidCents/status, excess →
    StudentCredit), DAILY_ITEMS (collectDailyItems FIFO + excess → item
    advance), ITEM_BALANCE (creditItemBalance), WALLET (wallet txn trail);
    receipt SMS via sendAutomated PAYMENT_RECEIVED (respects the automation
    setting); on any apply error the claim rolls back to PENDING (never
    lose money silently).
  - `parseHubtelCallback` — normalises all callback generations (ResponseCode
    0000/00, Data.Status Paid, form-encoded, transactionId-only).

## 3. Routes
- `GET/POST /api/school/[id]/ops/payments` — list (+search/status filter),
  7-day stats, provider/config card values; actions student-accounts (open
  invoices + per-item arrears/balances + wallet), create (+optional link SMS),
  check, cancel, share-link (re-SMS the link), simulate-fail (SIMULATED only).
  RBAC: GET fees.view/fees.collect, POST fees.collect/daily.collect.
  Absolute links resolve from HUBTEL_CALLBACK_BASE → request origin, so
  preview hosts work with zero config.
- `POST /api/webhooks/hubtel` — optional ?secret= check, JSON + form-encoded
  bodies, amount-mismatch rejection, idempotent completion, 500 on apply
  failure (Hubtel retries), GET health probe.
- `GET/POST /api/public/pay/[ref]` + `/pay/[ref]` page — parent-facing status
  page (school colour header, status banner, receipt + applied note, 5s poll
  while pending, SIMULATED "I have paid / Simulate failure" buttons); only
  minimal fields exposed (first name + surname initial).

## 4. Module UI (`momo` — "MoMo & Payment Links")
- ModuleId `momo`, nav Finance section (SmartphoneNfc icon), metro tile auto-
  included, permission `payments.momo` (Accountant/Cashier defaults, implies
  fees.view).
- Requests tab: 4 stat cards (awaiting, collected 7d, failed 7d, provider
  LIVE/DEMO), search + status chips, table (learner, colourful purpose chips,
  amount, method with momo-channel badges MTN/Telecel/AT, status chips,
  receipt + applied note, actions Check/Copy/Open/SMS/Cancel), 10s auto-
  refresh while anything is pending.
- New-request dialog: debounced learner search → purpose cards (Termly fees /
  Daily fee items / Fee-item advance / Wallet top-up) → per-purpose inputs
  (invoice list with Full-balance button; per-item grid with category chips,
  arrears prefills + advance hints; item picker; wallet kind with balances)
  → delivery (MoMo prompt with detected network + override, or payment link
  with "SMS the link now") → result panel with copy/open.
- Setup tab: LIVE/DEMO status, env checklist, webhook URL + copy, flow notes.

## 5. Seeds & tests
- `prisma/seed-momo.ts` (added to reseed-chain): 5 scenarios via the real
  engine — completed invoice (receipt linked), completed daily-items, pending
  link, pending prompt, failed — idempotent per purpose+status, targets the
  demo head's school.
- `scripts/smoke-momo-payments.ts` — **58/58**: channel detection, reference
  gen, callback parsing (6 shapes), mock-Hubtel adapters (auth header,
  endpoint paths, payloads, success/fail), SIMULATED E2E (prompt→simulate→
  invoice settled + receipt + events; link→webhook→daily FIFO 600 PAID + 300
  advance), duplicate-webhook idempotency, amount-mismatch ignore, wallet
  top-up txn, item advance, cancel/simulate-fail never post payments,
  share-link SMS, RBAC 403, GET list/stats/filters, validation guards.
- Full regression: **596 checks, 0 failures** (codes 23, credits 40,
  curriculum 29, attendance-analytics 29, bulk-admission 48, creche-sba 64,
  staff-dashboard 51, admissions-extra 14, fees-parity 9, lesson-note-sms 32,
  preferences 11, media 27, sms-gateway 39, daily-rates 34, daily-sync 40,
  portals 24+24, momo 58). `tsc --noEmit` 0; eslint 0 on all changed files.
- Browser (head account): module renders (stat cards, seeded rows with
  MTN/Telecel/AT badges); full UI E2E — new request (Linda Mould, invoice
  INV-2026-0010, GH₵200 link + SMS) → public pay page shows school branding
  → "I have paid" → receipt RCP-2026-00531 + "settled GH₵200.00" → app auto-
  refresh shows COMPLETED; Metro Start tile present; SMS Centre provider
  dropdown lists Hubtel; zero app console errors.

## Notes for later agents
- Going live: set HUBTEL_CLIENT_ID / HUBTEL_CLIENT_SECRET /
  HUBTEL_MERCHANT_ACCOUNT_NUMBER (+ HUBTEL_CALLBACK_BASE to the public URL
  and ask Hubtel to add the "Receive Money" scope to the keys). SMS keys can
  be the same pair or separate SMS_HUBTEL_*.
- The general prepaid wallet never auto-deducts (CI3 parity) — only per-item
  balances do; a WALLET-purpose momo top-up credits the wallet explicitly.
- `applyCompletion` claims COMPLETED before posting and rolls back on error;
  any new purpose must keep that invariant (claim → apply → link payment).
- Live-mode MOMO_PROMPT requires HUBTEL_CALLBACK_BASE (validated at create);
  payment links resolve the origin from the request when the env is unset.

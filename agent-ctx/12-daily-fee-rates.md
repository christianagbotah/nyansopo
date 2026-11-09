# Task 12 — Daily-fee rates & CI3 UX parity (work record)

Implemented the user's daily-fee requirements against the CI3 reference
(`Daily_fee_model`, `daily_fee_wallet`, `daily_fee_rates`,
`student_daily_fee_preferences`, `transport` + `transport_stops`,
`metro_menu.php`): route-based transport fares, per-item payment entry with
per-item prepaid/arrears tracking, weekly water billing surfaced in the UI,
colorful per-item cards, and a Metro Start screen.

## 1. Transport fare is route-based, never class-based (the core fix)
- The fare resolution (`resolveTransportFare`, unchanged in shape) is
  **pickup-stop fare → route one-way default**; 1× per direction, 2× for
  both ways. Two learners in the SAME class on different stops now pay
  different fares (verified: Stop A both-ways GH₵6.00 vs Stop B morning
  GH₵7.00).
- `TransportStop` (model existed, was never wired) now powers everything:
  - `save-route` accepts structured `stopRecords: [{name, fareCents?}]` +
    `farePerTripCents` (route default); writes stop rows (replace-all,
    sortOrder canonical) and keeps the legacy `stops` string in sync.
  - GET /ops/transport returns `stopRecords`, per-rider `onewayFare` +
    `stopFare`; route cards show per-stop fare badges; the assign dialog
    previews the resolved fare live ("one-way GH₵8.00 from Community 25
    (stop fare) · both ways GH₵16.00").
  - Route dialog: stops & fares grid editor (name + optional one-way fare,
    "route default" placeholder), default one-way fare field.
- Rates tab guard: `rate-save` on a TRANSPORT-category service → 400
  ("Transport fare is not class-based — …manage fares in Transportation →
  Routes"); the tab renders an explanatory card instead of the matrix.
- `scripts/upgrade-daily-rates.ts` — one-time migration for existing
  installs: fills `farePerTripCents` (demo fare table by name, else
  term÷55) + creates stop records graduated around the default;
  `--demo` seeds a handful of per-item balances.

## 2. Per-item payment entry + per-item balances (daily_fee_wallet parity)
- Schema: new `ServiceBalance` (studentId × serviceId unique, prepaidCents)
  + `WalletTxn.serviceId`; `StudentWallet` (general prepaid + transport)
  kept for compat — the general wallet remains a flexible any-item credit.
- Engine (`src/lib/daily-billing.ts`):
  - `settleDailyCharges(…, serviceId?)` — per-item FIFO settlement.
  - `creditItemBalance` (top-up / advance) and
    `autoDeductFromItemBalance` (CI3 `apply_charge`: balance → 0, remainder
    to arrears; partial charges split into PAID + OWED rows).
  - `generateDayCharges` and `applyTransportUsage` auto-deduct fresh
    charges from the item's prepaid balance, with WalletTxn PAY trails and
    audit rows noting the deduction.
- API (`ops/daily`):
  - `pos-collect` accepts `items: [{serviceId, amountCents}]` — per item:
    settle that item's arrears FIFO, **excess → item balance as advance**
    (payment_type arrears/current/advance/mixed, CI3
    daily_fee_transactions parity); one receipt, per-item allocation via
    charge.paymentId; audit note carries the full breakdown. Legacy
    single-amount path kept (offline queue + old clients).
  - `wallet-topup` / `wallet-adjust` accept `serviceId` → per-item balance
    ops (wallet "ITEM" + txn.serviceId).
  - GET payload: `posStudents[].items[]` (per-item rate, arrears, balance,
    today state, weekly + weeklyBilledThisWeek), wallets rows gain per-item
    breakdown, walletTxns gain the item name.
- UI (daily-services.tsx):
  - **CollectDialog rebuilt**: one colorful card per fee item (category
    icon chip: orange meals / cyan drinks / violet classes / emerald
    transport), each showing rate (/day or /wk), arrears (red), item
    balance (emerald), today's charge status, weekly state ("billed this
    week — no further charge until next week" / "bills Wed — once per
    week"), a per-item amount input pre-filled with that item's arrears,
    live "+GH₵x → item balance (advance)" hint, and a running total.
    Transport usage picker retained (route + stop fare preview).
  - POS student rows: per-item chips (red when that item is in arrears,
    emerald amount when it holds credit, weekly marker).
  - Wallets tab: 4 stat cards (general / transport / **per-item balances**
    / arrears) + per-learner item chips (balance + −arrears); TopUp dialog
    gains a "Fee item — per-item balance" wallet option with item picker.
  - Services cards: colorful category chips, weekly badge, "per route"
    badge + copy for transport, "(billed once, first run day)" for weekly.

## 3. Weekly water
- Already WEEKLY chargeMode (bills on the first configured school day of
  the ISO week, once per learner per week). Now *visible*: weekly badge +
  per-learner week state in the POS/collect UI; once paid, no rebill until
  the following week (verified end-to-end). Seed water = GH₵5.00/week
  (user's example).

## 4. Metro Start screen (metro_menu.php parity)
- `src/components/metro-menu.tsx`: full-screen overlay, dark backdrop,
  radial-gradient start background, grouped colorful tiles (CI3 palette
  hexes keyed by module id), staggered scale-in animation, live search
  (filters tiles + groups), Esc/backdrop close, body scroll lock, current
  module ring. Opened via the header **Start** button and a floating
  right-edge trigger (CI3's pinned launcher). Tiles mirror the
  permission-gated nav exactly.
- Group sections keyed by position (a section can appear in two bands —
  Finance either side of Facilities); conditional mount so each open is
  fresh (caught + fixed via browser console during verification).

## 5. Seeds
- `seed-ops.ts`: routes now carry `farePerTrip` + per-stop fares
  (graduated); GEA sachet water GH₵5.00 weekly.
- `seed-daily-ext.ts`: per-item balances (every 4th learner a meal-item
  credit, every 7th a water-item credit) with ITEM wallet txns.

## Verification
- New `scripts/smoke-daily-rates.ts` — **34/34**: stop-based fares
  (same-class divergence, GET fare payloads), weekly water (once/week,
  paid→no rebill, next week→rebill, weeklyBilledThisWeek flag), per-item
  entry (isolation, mixed payment type, advance→item balance, ITEM txn),
  auto-deduct (full + partial), per-item top-up/correction, transport
  class-rate guard, GET items[]/wallets payloads.
- Full regression: **449 checks, 0 failures** across 15 suites + portals
  (codes 23, credits 40, curriculum 29, attendance-analytics 29,
  bulk-admission 48, creche-sba 64, staff-dashboard 51, admissions-extra
  14, fees-parity 9, lesson-note-sms 32, preferences 11, media 27,
  sms-gateway 39, daily-rates 34, portals 24+24). `tsc --noEmit` 0;
  eslint 0 on all changed files.
- Browser (head account): Metro Start opens from the header button
  (23 permission-gated tiles, search "daily" filters to the tile, click
  lands on Daily Services), Esc closes, zero console errors after the key
  fix; POS rows show per-item chips (arrears red / balance emerald);
  Collect dialog renders the per-item grid (Lunch rate GH₵3/day arrears
  GH₵20 prefilled, Sachet Water weekly badge + GH₵5 item balance, total
  GH₵21); Transportation route card shows stop fares GH₵6/8/9/10 and
  rider "/trip" fares; route editor grid (stop names + fare inputs,
  default one-way GH₵8); Wallets tab shows PER-ITEM BALANCES card +
  per-learner item chips. VLM screenshot checks: metro "colorful tiles,
  grouped, no layout issues".
- Demo DB upgraded in place via `scripts/upgrade-daily-rates.ts --demo`
  (6 routes + 106 item balances); route default fares realigned to the
  stop tables.

## Notes for later agents
- The general prepaid wallet (StudentWallet.prepaidCents) is deliberately
  NOT auto-deducted — only per-item balances auto-deduct (CI3 parity).
- Offline queue entries for the new dialog fall back to the legacy
  single-amount shape (total) — sync settles FIFO across items; acceptable
  until the sync handler learns `items[]`.
- `fareOverrideCents` on assignments is the per-TERM fare override
  (termly billing path); daily fares come from stop/route only.

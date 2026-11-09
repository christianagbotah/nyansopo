# Task 16 — billing UX + split collections + meal opt-in/opt-out register (work record)

User: "the billing page needs enhancement with the input fields layouts…
create bill item section, all input fields should be in same row with the
buttons right aligned on the next row… sidebar auto collapse… edit bill item
button should either open on a modal or right below that row… daily fee
collection should allow multiple payment method on a single collection… admin
should be able to approve those students who do not want to eat on a specific
day to avoid being charged for lunch. Remember breakfast is optional… create
bill tab should also implement proper input fields alignment and validation…
filters should follow that of the ci3 (day / boarding / all)… when taking
payment and cash is selected, then hide the momo id input… how do we call all
these [payment channels]?… check and update advanced features and easy to use
features"

## 1. Fees → Bill items (fees-billing.tsx rebuild)
- **One-row form**: title | category | amount | class-category | specific-classes
  (Popover multi-pick) | description — `xl:grid-cols-[2.1fr_1.1fr_1fr_1.3fr_1.3fr_1.8fr]`,
  wraps to 2 cols on small screens. Buttons on the NEXT row, right-aligned
  (Clear ghost + Add), validation summary on the left.
- **Inline validation** (draftErrors): title required, amount > 0 — red borders +
  hints + a "Fix the highlighted fields" row; toast on submit.
- **Edit → modal**: row pencil opens `Edit bill item — <title>` (max-w-4xl)
  reusing the same BillItemFields; bulk inline edit (Edit selected) unchanged.
- Add-form no longer doubles as edit form (resetForm simplified).

## 2. Sidebar auto-collapse (app-shell.tsx)
- `rail` mode: "auto" | "open" | "closed" persisted in `localStorage["sidebar-rail"]`.
- Collapsed = closed, or auto && module === "fees". Collapsed rail = w-14 icon
  column (tooltips via title), pin button (PanelLeftClose/Open) in the header
  expands/closes; expanded keeps the full NavList. Mobile Sheet untouched.
- Browser-verified: fees page → 56px rail; pin → expands; other modules stay expanded.

## 3. Create invoices — CI3 students category + validation
- Students category select (CI3 students_category): All Students (126) /
  All Boarding Students (12) / All Day Students (114) / By class group.
  Source: Enrollment.residenceType (DAY|BOARDING) — fees GET roster rows now
  carry `residence`; rows show a Boarder chip.
- Item rows: one line (picker | title | amount) + description below; per-row
  hints (title required / amount > 0 / duplicate row title) shown once Create
  is attempted (`tried`), Create blocked until every row is valid.

## 4. Split daily collections (multiple methods, one receipt)
- Prisma: `Payment.splitJson Json?` — parts [{method, amountCents, reference}].
- daily-billing: `sanitizeMethodParts()` (known methods, positive amounts, sum
  == items total), `paymentMethodLabel()` (expands SPLIT → "Cash + MTN MoMo").
  `collectDailyItems` takes `methods[]`; method column = "SPLIT" when multi,
  reference = first non-empty part; audit note lists every part.
- ops/daily pos-collect accepts `methods[]`; offline queue stays legacy
  (UI blocks split collections offline with a toast).
- Consumers expanded: ops/cashier byMethod (parts summed, no SPLIT key),
  rows/recent/ledger description labels, dailyServicesReceipt method label.
- CollectDialog UI: method rows (Select + Amount + reference-per-method;
  CASH row hides the reference field entirely), "Split payment" adds a row
  prefilled with the remaining balance (max 3), allocation banner
  (Fully allocated / still to allocate / over-allocated), Record gated until
  allocated == total. Tendered/change shown only when a CASH row exists.

## 5. Meal opt-in / opt-out register (dining parity)
- Prisma: `DailyMealException` (student × service × date × kind unique;
  OPT_OUT | OPT_IN, PENDING | APPROVED | DECLINED, requested/decided names);
  `DailyService.optInDaily` flag.
- generateDayCharges: APPROVED OPT_OUT skips (and WAIVES an unpaid charge
  generated earlier, CHARGE_REVERSAL audit "waived — meal opt-out approved");
  optInDaily services bill only APPROVED OPT_IN learners.
- ops/daily actions (daily.manage): meal-exception-save (staff → APPROVED
  immediately + waive-on-approve), -decide (APPROVED|DECLINED + waive on
  OPT_OUT approval), -delete. OPT_IN on default meals / OPT_OUT on opt-in
  services refused with explanatory 400s.
- GET payload: mealExceptions register for the date + per-POS-item
  `optedOut` status (CollectDialog shows "not eating today"/"skip requested"
  badges, blocks the amount input on approved skips).
- **Meals & opt-ins tab** (admin): explainer strip, Record-a-skip/opt-in form
  (kind inferred from the service's optInDaily), parent-request queue with
  Approve/Decline, day register table with delete. Nav: Daily Services →
  Meals & opt-ins deep link.
- **Parent portal**: meal-request.create (own children only, MEAL/DRINK
  services, today-or-future, no duplicate PENDING/APPROVED) + payload
  mealServices/mealRequests; Overview card "Meals & dining" per child —
  skip/join button label flips with the service type, request list with
  status badges.

## 6. Cash hides MoMo input + channel naming
- fees PayDialog: CASH → no reference input, hint card instead; MoMo →
  "MoMo transaction ID" (wallet-SMS hint), CHEQUE → cheque number, BANK/CARD
  → bank reference.
- MoMo module explainer card: "The four ways money reaches the school" —
  1 Cash at the counter · 2 Direct MoMo transfer (record with wallet-SMS txn
  ID) · 3 Online checkout / payment link (parent self-service, Hubtel hosted
  page: MoMo/card/bank) · 4 MoMo prompt (assisted, accountant issues the
  USSD push, parent approves with PIN). Channels 3-4 are the existing
  PaymentRequest MOMO_PROMPT | PAYMENT_LINK flows.

## 7. Seeds & tests
- No new seeds (bill-item catalog unchanged; optInDaily off by default).
- New `scripts/smoke-payments-enhancements.ts` — **31/31**: split refused on
  mismatch/unknown method; split accepted (method=SPLIT, splitJson parts,
  receipt label "Cash + MTN MoMo", reference = first part, charge settled,
  byMethod expanded, no SPLIT key); OPT_IN/OPT_OUT guard rails; opt-out
  waives an existing unpaid charge; opt-in gating on breakfast-type billing;
  GET register + POS badge; parent request scoping (403 other child, 409 dup,
  400 backdated), PENDING → APPROVED with approver; roster residence.
- Full regression: 19 suites, 0 failures (daily-rates 34/34, daily-sync 40/40,
  momo 58/58, ci3-views 43/43, portals healthy, credits 40/40…). tsc 0;
  eslint 0 errors (4 pre-existing-style warnings).
- Browser E2E (production build): fees rail collapse (56px) + expand pin;
  one-row bill-item form + right-aligned button; edit modal from row; CI3
  category select (All 126 / Boarding 12 / Day 114 / by class) with Boarder
  chips; Meals tab; POS split flow → receipt RCP-2026-00511 printed
  "MTN MoMo + Cash · Ref MP2411.0045.1122" on ONE receipt; parent portal
  Meals & dining card. (Demo split payment left in place as realistic data.)

## Notes for later agents
- SPLIT payments carry parts in Payment.splitJson — never group payments by
  method without expanding it (use paymentMethodLabel / the cashier pattern).
- DailyMealException unique key is (studentId, serviceId, date, kind) — the
  upsert in meal-exception-save flips PENDING parent requests to APPROVED.
- Rail collapse auto-triggers on module === "fees" only; add more wide
  modules to `railCollapsed` in app-shell if needed.
- The old `method`/`reference` payload shape still works everywhere —
  methods[] is optional and additive.

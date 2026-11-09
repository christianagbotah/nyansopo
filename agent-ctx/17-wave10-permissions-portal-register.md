# Wave 10 — Page-search Start, permission/role management, collection portal, CI3 register

## User asks (this wave)
1. Metro Start loaded with ALL possible page links — virtual search for any page
2. Fee collection portal: select a student → all enabled fee modules load; optional → toggles;
   admin-approved lunch opt-outs disable the card; discounted learners don't get that card;
   transport riders get the transport card; prepayment (arrears first, excess kept on the item)
3. Mark attendance with the exact + enhanced CI3 logic
4. Everything permission-based — comprehensive role/permission management (cashiers barred
   from billing invoices, only accountants)

## What shipped

### 10.1 PAGE_INDEX + Start page-search (24adebd)
- `src/lib/page-index.ts` — 100+ pages: every tab of every module (bill items, opt-ins,
  SSNIT, BECE papers…), each with deep-link target, permission gate and search keywords.
- MetroMenu: search now queries the FULL catalogue ("receipts", "opt out", "SSNIT" hit);
  "All pages N" chip toggles A–Z browse grouped by section (94 pages for the super-admin
  session); page hits render above the classic tiles; deep links verified in-browser
  (search "opt out" → Meals & opt-ins tab selected).
- Filtered per session via canModule + per-entry perms; portal/proprietor/association pages
  hard-gated on the real role.

### 10.2 Role configuration layer (24adebd, 5f92be3)
- `RoleConfig` model (school × role, unique): tune any ladder role's permission set, mint
  custom roles (isCustom) with level + landing module; absent/inactive row → code defaults.
- Resolution: role-config base → implication expansion → per-user extra/denied. Session
  (getUser + /api/auth) resolves configs: roleLabel, roleLevel, roleBackoffice, landingModule;
  canOperateSchool accepts custom backoffice roles; gateRole maps them onto the school shell.
- ops/rbac: `role.save` (self-lockout guard), `role.delete` (custom only, reassignTo moves
  users first), role cards + per-user roleBase in the GET.
- RBAC console: **Roles & permissions tab** — ladder cards with perm meters, tune/revert,
  mint custom role dialog (level + landing + matrix), delete-with-reassign flow; users table
  + create dialog offer custom roles; per-user matrix bases on the school's actual config.
- Browser-verified: tuned CASHIER (barred from invoice billing, kept daily.collect — 403 on
  record-payment), minted "Front Desk" via API + "Bursar Assistant" via UI, delete blocked
  while a user holds the role, reassigned + deleted, reverted to defaults.

### 10.3 Fee collection portal (1ef103b, 5f92be3)
- `ops/cashier?action=student-info`: per-learner payload — daily cards (optInDaily → toggle,
  APPROVED OPT_OUT exceptions disable the card, 100% discount hides it, partial discounts
  subsidise), transport card for riders (per-stop one-way fare), open invoices, wallet state,
  attendance chip, discount badges, recent receipts.
- CollectTab (first tab of the Cashier Console): debounced learner search over the students
  directory (CI3 select2 parity) → conditional cards → one payment rail: split methods
  (Cash + MTN MoMo), cash tendered/change, mark-present; per-item projection (settles X
  arrears · banks Y advance on the SAME item) — prepayment is the engine's collectDailyItems
  (verified: GH₵100 on a GH₵84 item → paymentType mixed, GH₵16 item prepaid).
- Optional cards render as toggles ("eating/skip") + a skip-list chip row; approved opt-outs
  render as a disabled-card strip ("these cards are disabled today"); invoice cards are
  fees.collect-gated so cashiers can be barred while accountants keep them.
- `record-presence` action: boarding direction / present only — no money moves.
- Browser-verified: Yao Asante — lunch 84 + breakfast toggle ON 30, split 80 cash + 50 MoMo,
  receipt "Total settled GH₵130 · Cash + MTN MoMo"; post-collection portal shows item-credit
  badges and "already marked" disabled present box.

### 10.4 CI3-exact attendance register (5f92be3)
- Default unmarked = ABSENT (CI3 form parity); full class roll merged with session records;
  per-learner upserts (partial re-saves touch only the learners sent, checkbox-gated).
- `applyAttendanceChargeRule` (CI3 save_attendance_and_fees 158–300): PRESENT/LATE →
  ABSENT/SICK/EXCUSED waives the day's non-transport charges (OWED → WAIVED; PAID → money
  held on the item as prepaid credit, payments never deleted); back to PRESENT re-instates
  (WAIVED → OWED or created) auto-deducting the reversal credit; weekday/opt-out/opt-in/
  weekly-dedup aware. Audited via daily_fee_audit_log (CHARGE_REVERSAL rows).
- Fee inputs ride the register (daily.collect-gated): per-item amounts (prefilled with
  arrears), optional toggles, opt-out badges, rider bus-direction pick (AM/PM/Both with live
  fare); save toast reports reversals + collected totals; register GET carries the fee block
  for daily.view holders.
- Browser-verified: Yao marked Present → DAILY FEES block appears (lunch ✓ + breakfast
  toggle); save toast "12 learners · 10 charges reversed (money held as prepaid) · GH₵389
  collected"; persisted state returns "Present (P)" + Update checkboxes on reload.

### 10.5 Permission split fixes
- MODULE_PERMS.cashier = any-of cashier.reports/fees.collect/daily.collect; console tabs
  render by permission (collect vs reports); ops/cashier GET split — reports need
  cashier.reports, student-info accepts any collecting permission (teachers keep classroom
  collections, CI3 hybrid); teachers still 403 the takings report (smoke-portals parity).
- Seed: "Breakfast (optional)" now genuinely optInDaily (GEA + GKA) so the toggle demos.

## Verification
- **smoke-wave10: 44/44** — role configs (tune/mint/delete/revert, 403 invoice billing,
  custom session layering), portal conditional cards (opt-out disabled, 100% discount hidden,
  transport per-stop, invoices), prepayment (mixed typing, item banking), register charge
  rule (waive, PAID→prepaid credit, reinstate, EXCUSED validation, partial updates).
- **Full regression: 21/21 suites** (admissions 14, attendance-analytics 29, bulk 48,
  ci3-views 43, codes 23, creche 64, credits 40, curriculum 29, daily-rates 34, daily-sync
  40, fees-parity 9, framework 13, lesson-note-sms 32, media 27, momo 58,
  payments-enhancements 31, portals ALL HEALTHY, preferences 11, sms 39, staff-dashboard 51).
- Browser (prod build, super admin): page search + A–Z browse (94 pages), deep links,
  Roles tab + UI role minting, collection portal end-to-end with split payment + receipt,
  register defaults + fee inputs + outcome toasts. Screenshots:
  shot-wave10-portal.png, shot-wave10-register.png, shot-wave10-roles.png.

## Commits
24adebd rbac+metro · 1ef103b portal · 5f92be3 attendance+split · seed flip (this commit)

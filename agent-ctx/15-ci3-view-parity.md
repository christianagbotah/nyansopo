# Task 15 — CI3 view parity: cashier portal, metro squares, student_payment tabs, system settings, dashboard, mark attendance (work record)

User: "let's continue with the ci3 views and features building. the cashier
collection portal, metro menu cards sizes are square as implemented on the ci3
with uniform number of cards without any section longer or shorter than the
other, bill item, manage bulk invoice, create bill tabs on the student invoice
or student_payment.php view page are all not implemented, system settings page
with ssnit logo and all the other fields and tabs as in ci3 were not fully
implemented, admin dashboard kpis and links as implemented on ci3 should be
implemented, mark attendance page and its implementation should be done, the
rich admin and teacher navigation menu views in the ci3 should be fully
developed, all links and buttons should have cursor pointer on hover etc"

Researched via 4 parallel Explore agents over the CI3 source
(`student_payment.php` 6.7k lines, cashier views, system_settings_modern,
dashboard.php, mark_attendance.php, metro_menu.php, admin/teacher
navigation.php) plus the iedupro-research page captures.

## 1. Global cursor affordance (globals.css)
`a, button, [role=button/tab/menuitem/option/combobox/switch/checkbox/radio/
slider/spinbutton], label, select, summary, .clickable → cursor: pointer`;
disabled → not-allowed; text inputs keep the I-beam. Browser a11y snapshots
now show `cursor:pointer` on interactive elements.

## 2. Metro Start — square tiles, EXACTLY 6 per section (metro-menu.tsx + app-shell)
- metro-menu.tsx rewritten: `MetroSection[]` props; each section renders a
  2-column grid of `aspect-square` tiles (CI3 150×150); groups flow as
  column blocks (1/2/3/4 cols responsive); MS flat palette; staggered
  +80ms tile-in sorted by position; live search; Esc close.
- app-shell builds `metroSections` from 7 titled sections (Dashboard & Quick
  Access / Students & People / Student Billing / Collections & Cashier / Daily
  Services & Transport / Academics & Exams / Finance & Communication), each
  with 6-9 permission-gated candidates — first 6 allowed win, sections with
  <6 reachable tiles are skipped ENTIRELY so no section is longer or shorter.
  Teacher fallback candidates (Wallets, Attendance Trends, Lesson Notes,
  Monthly Register, 14-Day Trend, Barcode Scanner) fill the sections for
  classroom roles.
- Verified in browser: admin = 7 sections × 6 = 42 tiles (42/42 square,
  width==height ±2px); teacher = 4 × 6 = 24.

## 3. Student invoice page — student_payment.php tabs (fees-billing.tsx + fees.tsx)
- New Prisma `BillItem` (school-wide catalog: title unique/school, category,
  classCategory ALL|CRECHE|NURSERY|KG|PRIMARY|JHS|SENIOR, specificClassIds
  Json, amountCents, description; `@@unique([schoolId,title])`) + School
  relation, db:push'd.
- API (`[resource]/route.ts` fees case):
  - GET adds `billItems[]`, `classrooms[]`, `roster[]` (active learners).
  - `bill-item-save` (create/update, dup-title + zero-amount refused),
    `bill-items-bulk-save`, `bill-item-delete`/`bill-items-bulk-delete`
    (refused while invoice lines reference the title — CI3 parity),
  - `mass-invoice-create` {studentIds[], items[{title,amountCents}],
    termId?, dueInDays?} — one invoice per learner; discount auto-apply
    (catalog auto + approved profiles, percent stacks / fixed subtracts) and
    prepaid-credit auto-apply exactly like generate-invoices; learners with
    an existing term invoice get only the MISSING items appended (CI3
    updates the bill instead of double-billing); fully-billed → skipped.
- UI: new "Bill items (n)" tab (add form with category/class-category/
  specific-class chips, catalog table with inline edit, bulk toolbar: Edit
  selected / Save all / Delete selected / Clear) and "Create invoices" tab
  (wizard: term + due-days + students-category scope → left students list
  with search/select-all, right invoice-item rows picked from the catalog
  with editable amounts, purple-gradient confirm modal with per-learner
  total + grand total, discounts/credits note). "Invoices" renamed "Bulk
  manage": checkbox column + select-all + sticky bulk bar (Copy statement
  links / SMS reminders / Export CSV / Clear); bulk-SMS dialog feeds the new
  `STUDENTS:<ids>` audience on the sms route (renders {student}/{balance},
  category FEES).
- Browser E2E: picked KG 1 — Sunflower, Select all, TUITION FEE item →
  confirm → 9 invoices updated (items appended); demo data reverted after.

## 4. Cashier collection portal (cashier-console.tsx rebuild + ops/cashier)
- GET /ops/cashier now also returns `report` (serviceNames, serviceTotals,
  grandTotal, rows[] with per-transaction byService split that sums exactly
  to total, classSummary[]) and `roster[]` (learners for the ledger picker).
- POST /ops/cashier {action:"ledger", studentId} — debit/credit running
  balance across invoices + payments (school-scoped, 404 unknown).
- UI: 5 deep-linkable tabs —
  - **Collection portal**: CI3 KPI strip (one colourful card per fee type +
    gradient Total collected), quick actions (Collect daily fees → POS,
    Create invoices, Print receipts, My collections), headline tiles,
    fee-type breakdown (collected vs outstanding bars), payment-method mix,
    collector performance, recent transactions table.
  - **My collections**: report-type toggle (Detailed / Class summary),
    class + method + search filters, per-fee-type summary cards + GRAND
    TOTAL, detailed table with per-fee-type columns + TOTAL footer row,
    class summary table, Export Excel (CSV) + print.
  - **Daily summary**: unchanged SummaryHandoverTab (six stat cards,
    printable summary + signed handover, handover.save).
  - **Print receipts**: fee-type + search filters, checkbox table, Select
    all, Print N thermal → fees receipt-payloads forced to THERMAL style via
    BulkReceiptsDocument (80mm).
  - **Student ledger**: debounced learner search over roster → ledger table
    (Date / Description / Reference / Debit / Credit / running Balance),
    closing balance, guardian line, printable.
- Browser E2E: all tabs render; ledger for Linda Owusu-Ansah shows 38 rows,
  closing = debits − credits.

## 5. System settings (settings.tsx + settings API)
- GET settings adds `system` (system.* SchoolSetting KV) and `statutory`
  (OpsSetting rates — the same store payroll runs read) + branding gains
  `ssnitLogoUrl`.
- POST `update-preferences` accepts `ssnitLogoUrl` (data-URL → MediaAsset,
  null clears). New POST `update-system` whitelists slogan/address/boxNumber/
  digitalAddress/website/ssnitNumber/currency/halfPaymentBy/fullPaymentBy
  (date-normalised)/momoAccountName/momoAccountNumber/autoLock.
- UI: three new tabs —
  - **System** (CI3 General+Payment+Security): School information (system
    name, location, slogan, address, box number, GhanaPost GPS, website,
    **SSNIT Establishment Number**, currency, email), Payment settings (half
    payment by week-select, full payment by date, momo account name/number),
    Financial security (auto-lock toggle + modification-queue callout),
    floating "Save all settings".
  - **Branding & logos**: previews + uploads for school logo, head-teacher
    signature and **SSNIT logo** (Tier 1/Tier 2 statutory reports), default
    receipt style; the same uploads remain on the profile tab card.
  - **Statutory (SSNIT)**: CI3 payroll_statutory_settings parity — amber
    "affects new runs only / verify with GRA" warning, rates table (Tier 1
    employer/employee, Tier 2, GETFund, NHIL) saving through the payroll
    save-rates action so payroll maths picks the same numbers, SSNIT-cap
    note, reset button.
- Fixed a stray `)` in the attendance status `<option>` labels (was
  rendering "Present (P))") and GH&curren; → GH₵ in the statutory note.

## 6. Admin dashboard KPIs + links (dashboard.tsx + dashboard API)
- API stats add teacherCount (TEACHING staff), parentCount (PARENT users),
  unpaidCount, expensesTerm (expense aggregate over the term window).
- Non-teacher dashboard rows, CI3 order: Key metrics (Total students →
  Students, Active teachers → Staff Portal, Registered parents → SMS,
  Present today → Attendance) · Quick actions card (Add student, Mark
  attendance, Billing, Take payment, Messages, Bill reminders — icon tiles
  with hover lift) · Financial summary gradient cards (Total due invoices,
  Total income, Unpaid invoices, Termly expenses).

## 7. Mark attendance — CI3 card grid (attendance.tsx register tab)
- Sticky filter card (class select + date + "Type Student Name..." search +
  offline badge + taken-by), amber bulk-select bar on saved days ("Select
  All Students (uncheck to update specific students only)" + n/N included
  counter), learner CARD grid (`auto-fill minmax(240px,1fr)`), each card:
  name + roll, colour-coded status `<select>` with the CI3 letters —
  Present (P) / Late (L) / Absent (A) / Sick-Home (S) / Sick-Clinic (C) /
  Excused (E) (+legacy Sick/Busy), EXCUSED reveals the reason textarea,
  update checkbox on saved days (excluded cards dim to 50%), All present /
  All absent, purple-gradient save FAB (fixed bottom-right, expands on
  hover) alongside the header save. Save posts only the picked learners.
- Browser E2E: marked Yao Asante EXCUSED with reason → FAB save → session
  persisted (12 records, EXCUSED reason intact) → reload shows the bulk
  bar + taken-by.

## 8. Rich navigation (app-shell SCHOOL_NAV children)
- Cashier → Fee collection portal / My collections / Daily summary / Print
  receipts / Student ledger (deep-link tabs).
- Fees → Bill items / Create invoices / Bulk manage / Receipts / Structures
  / Discounts / Arrears / Credits / Modification approvals.
- Attendance → Mark attendance / Analytics & trends / Monthly report /
  Barcode scanner. Settings → System / Branding & logos / Calendar / Classes
  / Subjects / Grading / Statutory (SSNIT). Students/Classes keep the
  class-band trees. Teacher role verified: Attendance & Students sub-trees
  expand with the right entries.

## 9. Seeds & tests
- `prisma/seed-bill-items.ts` (added to reseed-chain): 7-8 catalog items per
  school (TUITION FEE, EXAMINATION FEE, BOOKS, ICT, CRECHE CARE, SPORTS,
  GRADUATION + a class-specific BECE REGISTRATION for the last JHS class).
- New `scripts/smoke-ci3-views.ts` — **43/43**: bill-item CRUD (dup/zero
  refused, update, bulk save), GET payload shape, mass-invoice validation +
  create/append/skip semantics on two dedicated test learners (created &
  cleaned up), delete-refused-once-billed, cashier report rows (byService
  sums = total) + classSummary + roster, ledger (closing = D−C, 404),
  update-system round-trip (ssnitNumber, autoLock, ISO date), statutory
  save + GET, branding.ssnitLogoUrl key, STUDENTS: sms audience (2 sent,
  FEES category), dashboard stat fields. Pre-cleanup block makes it
  re-runnable after crashes.
- Full regression: **639 checks, 0 failures** (admissions-extra 14,
  attendance-analytics 29, bulk-admission 48, ci3-views 43, codes 23,
  creche-sba 64, credits 40, curriculum 29, daily-rates 34, daily-sync 40,
  fees-parity 9, lesson-note-sms 32, media 27, momo 58, portals 24+24,
  preferences 11, sms-gateway 39, staff-dashboard 51). `tsc --noEmit` 0;
  eslint 0 errors on changed files (warnings match the pre-existing ternary
  style).

## Notes for later agents
- Bill items are the CATALOG; fee structures (per-class termly) still feed
  generate-invoices — both paths apply discounts + credits identically.
- mass-invoice-create APPENDS to existing term invoices (CI3 update
  semantics); no second invoice is ever created for the same learner/term.
- The statutory tab writes OpsSetting keys (SSNIT_TIER1_EMPLOYEE etc.) —
  the payroll route's loadRates() reads the same keys, so settings and
  payroll stay in sync automatically.
- Metro sections MUST keep exactly 6 rendered tiles; when adding a tile,
  append it as a candidate (never past position 6) or the uniform grid
  breaks. Candidates beyond 6 act as role fallbacks.
- Dev server crashed twice during the session (schema push + hot reload) —
  if smoke tests get ConnectionRefused, restart `bun run dev` first.

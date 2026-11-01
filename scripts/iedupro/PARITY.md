# Nyansapo ↔ iEduPro (CI3) — parity map

Live record of the CI3 feature surface vs the Nyansapo implementation.
CI3 source: `scripts/ci3app/` (1,947 files · 598 admin views · ~150K view lines).
This file is the continuation plan — check items off as they land.

Legend: ✅ done & verified · 🟡 partial (notes) · ⬜ not yet

## 1. Daily fees (CI3 Fee_collection + Daily_fee_model, 5,956 lines)
- ✅ One-way route fare model: MORNING/IN 1× · AFTERNOON/OUT 1× · BOTH 2× ·
      NOT_USING 0 · BOARDED as recorded (route fare 8 → both ways 16)
- ✅ Transport day-charges created lazily (direction pick or boarding mark) —
      generate-day never auto-bills transport (42 riders skipped, verified)
- ✅ Conductor boarding: leg-aware merge (IN after OUT → BOTH, no double bill),
      unpaid leg → arrears; transport-wallet auto-deduct with PAY txn
- ✅ Per-class rate matrix (daily_fee_rates parity): student override → class
      rate → standard; rate editor UI; 11 seeded overrides
- ✅ WEEKLY services bill once per ISO week (water parity)
- ✅ Wallets: prepaid + transport, top-ups, adjustments, audit trail
- ✅ Collection policy: CLASSROOM/CASHIER/HYBRID + teacher matrix
      (ALL/NONE/SELECTIVE per class & service)
- ✅ Collection UX: search learner, method, backdate, tendered/change,
      mark-present, wallet pay-from, receipt SMS
- ✅ Daily reconciliation + collector handover (billed vs counter vs wallet,
      per-collector breakdown, variance)
- ✅ Daily-fee discounts at charge time (subsidy → enrolment override)
- ✅ Fee-module enable/disable per service (active flag)
- 🟡 Charge log audit trail: covered by wallet txn log + reconciliation
      (CI3's daily_fee_audit_log per-charge rows not mirrored 1:1)
- ⬜ Cashier "daily summary"/"handover report" as printable PDF views
      (data exists in reconciliation tab; print layout not built)

## 2. Navigation (CI3 navigation.php, 1,616 lines)
- ✅ Parent → child → grandchild nesting with class groups
      (Creche&Nursery/KG/Primary B1–B6/JHS B7–B9/Senior, canonical order)
- ✅ Class deep links: Students → level → class (directory pre-filtered);
      Classes → level → class; Daily → POS/wallets/ledger/rates/recon/trend;
      Fees → invoices/receipts/structures/discounts/arrears
- ✅ URL deep links ?module=…&tab=…&class=…
- ✅ Permission-gated per role (RBAC levels 1–5 + all portals)
- 🟡 CI3 per-class Subject & per-class Timetable sub-menus: covered by the
      same class groups (Subjects live inside the Classes module)

## 3. People (CI3 student management)
- ✅ Promotion wizard (cohort move up + graduate-to-alumni), promotion trail
- ✅ Muted students view + mute/unmute (temporarily off roll)
- ✅ Alumni view (graduated, off roll)
- ✅ Special diet notes (per-learner editor + class-list indicator)
- ✅ 360° profile (fees, academics, flags, transfers), CSV export
- ✅ Admission wizard (bill preview + discounts + deposit) — earlier session
- ⬜ Bulk student ID cards (printable sheet)
- ⬜ Barcode-scanner attendance (CI3 level ≤2)

## 4. Online exams (CI3 online_exam)
- ✅ Full module: MCQ papers, publish→close lifecycle, results ranking
- ✅ Learner sitting from student portal; auto-marked; anti-forgery
      (own-exam + class-scope + double-submit guards); answer key stripped
      from learner responses
- ✅ exams.online RBAC permission (admins + teachers)

## 5. Reports & exams (CI3 examination, 598 views)
- ✅ 30/70 class-exam split, per-subject positions, grade codes + chips
- ✅ BECE best-six aggregate; 3 sheet styles (Classic GES broadsheet /
      Modern / Primary-KG descriptor); editable remarks & conduct;
      remark template library + conduct/interest traits (settings)
- ✅ Bulk print views; receipts register + bulk reprint (4 styles)
- ⬜ Portfolio assessment mode
- ⬜ Cumulative (multi-term) reports
- ⬜ WAEC raw-score grading view (settings has rawScore in CI3)
- ⬜ Exam-marks SMS blast (SMS centre exists; the marks-specific blast not wired)
- ⬜ Question-paper bank

## 6. Communication
- ✅ SMS centre: compose, automations (9 triggers), templates, log, settings
- ✅ Noticeboard (school notices: audience/priority) → parent & student portals
- 🟡 Lesson notes: INACTIVE in CI3 itself (commented out of their nav)

## 7. Finance & payroll
- ✅ Invoicing, fee structures, discounts (profiles/rules/applications/
      approvals workflow), student ledger, arrears chase-list
- ✅ Cashier console + reports; income/expenditure dashboards; finance analytics
- ✅ Payroll (Ghana statutory: SSNIT tiers, PAYE, GETFund, NHIL; payslips;
      pension providers; statutory settings)
- ✅ Inventory: dashboard, POS, products, suppliers, sales, returns,
      purchase orders, categories
- ✅ Transport: routes, vehicles, drivers, statutory expiry, assignments,
      trips, per-stop fares, term billing, conductor portal
- ⬜ Receipt/invoice modification-approval queue (payment void flag exists in
      the schema; no admin UI yet)
- ⬜ SSNIT tier-1/tier-2 printable reports (payroll data exists)
- ⬜ Aging report printable (data exists in finance module)
- ⬜ Bank reconciliation / chart of accounts (CI3 marks most of these
      sections commented-out in its own nav)

## 8. Portals
- ✅ Super admin / admin / accountant / cashier / bus conductor (levels 1–5)
- ✅ Teacher, non-teaching staff, librarian, parent, student
- ✅ Parent portal: children, fees & payments (+ MoMo how-to), attendance,
      reports, transport, messages, notices
- ✅ Student portal: attendance, results, timetable, library, online exams
- ✅ Employee (staff) portal: payslips, profile, payment history
- ⬜ Parent/student library book-request flow (library circulation exists;
      the request queue from portals not built)
- ⬜ HOD portal (CI3's is lesson-note approval — inactive in CI3)

## 9. Boarding management (CI3 boarding_system)
- ⬜ Boarding houses / dormitories / beds / boarding reports — no schema
      models yet; CI3 gates it behind the boarding_system setting

## 10. Cloud sync
- 🟡 PWA offline queue + sync exists (queueable payments/attendance/marks).
      CI3's multi-device table-level sync dashboard is an architecture of its
      own (WAMP ↔ cloud) — represented here by the offline queue & sync badge

## Verification harness (all green at time of writing)
- scripts/test-fare-engine.sh — 14 checks (one-way fare model)
- scripts/test-students-exams.sh — 15 checks (roster/promotion/diet/exams)
- scripts/verify-rbac.sh — 10 roles + 9 API permission checks
- scripts/verify-portals-ui.sh — 9 portal renders, zero console errors
- scripts/verify-interactions.sh — library issue + permission editor

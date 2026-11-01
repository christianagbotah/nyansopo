# Nyansapo ↔ iEduPro (CI3) — parity map

Live record of the CI3 feature surface vs the Nyansapo implementation.
CI3 source: `upload/ci3_full/` (1,947 files · ~150K view lines, full controllers/models/views).
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
- ✅ Charge log audit trail: per-charge DailyFeeAudit rows (CI3
      daily_fee_audit_log parity) — CHARGE/PAYMENT/TOPUP/ADJUSTMENT/
      RECONCILIATION with balance & arrears before→after, actor name+role,
      reference; viewer in Reconciliation tab (latest 100 + learner filter)
- ✅ Cashier "daily summary"/"handover report" as printable views —
      Summary & Handover tab in cashier console (fee-category totals,
      payment-method split, txn stats, signature boxes, save + print)

## 2. Navigation (CI3 navigation.php, 1,616 lines)
- ✅ Parent → child → grandchild nesting with class groups
      (Creche&Nursery/KG/Primary B1–B6/JHS B7–B9/Senior, canonical order)
- ✅ Class deep links: Students → level → class (directory pre-filtered);
      Classes → level → class; Daily → POS/wallets/ledger/rates/recon/trend;
      Fees → invoices/receipts/structures/discounts/arrears/approvals;
      Boarding → houses/dorms/beds/assignment/reports
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
- ✅ Bulk student ID cards — printable 10-per-sheet cards (kente header,
      learner photo/avatar, nyansapoIndex + CSS barcode, guardian phone,
      print-only CSS, CSV roster)
- ✅ Barcode-scanner attendance — Scanner tab in Attendance (AUTO/MANUAL,
      check-in/check-out, duplicate rejection, arrears gate + operator
      approve-bypass, unknown-code handling, today's log with cancel;
      admin level ≤2 enforced server-side)

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
- ✅ Portfolio assessment mode — weekly SBA grid (class × subject × ISO week,
      Mon–Fri strand codes + 0–10 scores, today-only editing), aggregate
      feeds a 20-mark PROJECT assessment (CI3 test1 = avg×10×0.2 formula)
- ✅ Cumulative (multi-term) reports — per-subject × per-term totals,
      cumulative average + grade, WAEC aggregate column (red ≥15), print
- ✅ WAEC raw-score grading — editable A1(80-100)…F9(0-39) table with
      numeric points, stats (pass-mark = min markFrom ≥ 50), settings tab
- ✅ Exam-marks SMS blast — exact CI3 message format
      "Year|Term: … Exam Scores of {name}: Subject {n}% {grade} {remark}, …"
      guardian/learner targeting, preview + send (SmsMessage rows)
- ✅ Question-paper bank — rich-text papers per class/exam, CRUD + print
      (reports tab), 3 seeded past papers

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
- ✅ Receipt/invoice modification-approval queue — cashiers/bursars request
      EDIT/DELETE with reason + snapshot; super-admin (level 1) decides;
      APPROVED executes transactionally (payment delete reverses invoice
      paidCents, edit re-syncs); REJECT/REVOKE paths; dedupe one-pending;
      Approvals tab in Fees with pending badge
- ✅ SSNIT tier-1/tier-2 printable reports — CI3 layouts (Tier 1: ACT 766,
      Hazd. NO, 13.5% employer column; Tier 2: member IDs + provider line),
      statutory-setting rates with 13.5/5.5/5.0 fallbacks, totals, CSV + A4 print
- ✅ Aging report printable — 0-30/31-60/61-90/90+ buckets (CI3 palette),
      stat cards, detail table, CSV + print
- 🟡 Bank reconciliation / chart of accounts (CI3 marks most of these
      sections commented-out in its own nav — represented by finance module)

## 8. Portals
- ✅ Super admin / admin / accountant / cashier / bus conductor (levels 1–5)
- ✅ Teacher, non-teaching staff, librarian, parent, student
- ✅ Parent portal: children, fees & payments (+ MoMo how-to), attendance,
      reports, transport, messages, notices, library (requests on behalf)
- ✅ Student portal: attendance, results, timetable, library (request a book
      + my requests), online exams
- ✅ Employee (staff) portal: payslips, profile, payment history
- ✅ Parent/student library book-request flow — request with dates →
      librarian queue → accept (creates loan, decrements availability,
      409 when no copies) / reject; overdue flags; forged-studentId 403
- 🟡 HOD portal (CI3's is lesson-note approval — inactive in CI3)

## 9. Boarding management (CI3 boarding_system)
- ✅ Boarding houses (house master, prefect, capacity, GPS, termly fee,
      status) / dormitories (house, type M/F, floor) / beds (code, SINGLE/
      BUNK, status) — full CRUD, referential guards (409 on delete with
      children), bulk bed creation (PREFIX-001…N, cap 100)
- ✅ Student assignment: bed picker grouped by house→dorm, assign/release
      transactions, DAY↔BOARDING residence conversion, unassigned-boarders
      queue
- ✅ Boarding reports: per-house + per-dorm occupancy (rate badges
      danger>80/warning>50), summary cards, boarder directory, print
- ✅ RBAC boarding.view/manage; nav under Facilities (CI3 parity)

## 10. Cloud sync
- 🟡 PWA offline queue + sync exists (queueable payments/attendance/marks).
      CI3's multi-device table-level sync dashboard is an architecture of its
      own (WAMP ↔ cloud) — represented here by the offline queue & sync badge

## Verification harness (all green at time of writing)
- scripts/test-fare-engine.sh — 14 checks (one-way fare model)
- scripts/test-students-exams.sh — 15 checks (roster/promotion/diet/exams)
- scripts/test-parity2.sh — 41 checks (boarding · scanner · portfolio ·
  cumulative · WAEC · papers · marks-SMS · approvals · SSNIT math ·
  aging · cashier summary/handover · library requests · audit log)
- scripts/verify-rbac.sh — 10 roles + API permission enforcement
- scripts/verify-portals-ui.sh — portal renders, zero console errors
- scripts/verify-interactions.sh — library issue + permission editor

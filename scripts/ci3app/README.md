# SchoolManager — CodeIgniter 3 School Management System

A comprehensive school management system built on **CodeIgniter 3** (PHP) covering admissions,
academics (marks/marksheets/results), attendance, **daily fee collection** (feeding/lunch,
breakfast, classes, water, transport), invoicing, payroll, transport management, SMS,
notifications and multi-location sync.

---

## 1. Architecture at a glance

| Layer | Location | Notes |
|---|---|---|
| Controllers | `application/controllers/` | `Admin.php` is the admin mega-controller; feature controllers: `Fee_collection`, `Cashier`, `Discount`, `Discount_modification`, `Attendance_daily_fees`, `Transport`, `Payroll`, `Teacher`, … |
| Models | `application/models/` | `Daily_fee_model` (daily-fee engine), `Discount_model` (discount lookups), `Ajaxdataload_model` (server-side DataTables), `Crud_model`, … |
| Views | `application/views/backend/<role>/` | `admin/`, `teacher/`, `parent/`, `student/`, `cashier/`, … rendered through `backend/main.php` (Neon + Bootstrap 3 + Tailwind + FontAwesome) |
| Migrations | `application/migrations/*.sql` | Plain SQL, apply manually (see §3) |
| Full schema | `application/schoolmanager.sql` | ~270 tables, structure-only dump |

### Daily fees (the core money engine)

* **Fee types**: `feeding` (lunch, mandatory), `breakfast` (opt-in), `classes` (mandatory), `water` (weekly), `transport` (route fare × trips).
* **Rates** are class-based: `daily_fee_rates (class_id, feeding_rate, breakfast_rate, classes_rate, water_rate, year, term)`.
* **Modules** toggled via settings keys `fee_module_<type>` (Fee Collection Settings).
* **Charging** happens through attendance: `Daily_fee_model::process_daily_charges()` → wallet `daily_fee_wallet` → arrears, logged in `daily_charge_log`; payments in `daily_fee_transactions`.

### Discount profiles (scoped daily-fee discounts)

* `discount_profiles` — reusable profile: `discount_category` (`daily_fees` | `invoice`), scope
  (`discount_type` = one fee type, a CSV like `feeding,water`, or the `all_daily_fees` wildcard),
  `discount_method` (`percentage` | `fixed`) and `discount_value` (100 = full, 50 = half …).
* `discount_profile_rules` — optional per-class / per-fee-type value overrides.
* `student_discount_assignments` — student ↔ profile link with a **snapshot** of the profile terms,
  approval workflow (`pending` → `approved`, super-admin auto-approves).
* Canonical lookup (used by charging AND previews):
  `Discount_model::get_student_discount_for_date($student_id, $year, $term, $class_id, $fee_type, $date)`
  — resolves scope (exact fee type > multi-fee CSV > `all_daily_fees`), applies class rules,
  returns `discount_method`/`discount_value`.

---

## 2. Recent enhancements (2026-11)

### Discount engine correctness (financial bugs fixed)
1. **Percentage key bug** — `Daily_fee_model` (18 sites), `Attendance_enterprise_model`,
   `Bus_conductor` and `manage_attendance_view` tested `discount_type == 'percentage'`, but the
   lookup returns the method in `discount_method` (`discount_type` holds the *fee name*).
   Percentage discounts were silently charged as fixed amounts. All fixed.
2. **Multi-fee scopes never applied** — profiles scoped like `feeding,water` are matched via
   `FIND_IN_SET` now, with specificity ordering exact > CSV > wildcard.
3. **Wildcard priority inverted** — `all_daily_fees` used to beat fee-specific profiles.
4. **`Discount::do_assign` dead rows** — assignments were inserted without the profile snapshot
   (NULL type, 0.00 value); now snapshots like `Admin::assign_student_discount`.
5. **`discount_profile_rules` never consumed** — rules now override the profile value per
   class/fee type inside the canonical lookup (`get_profile_rule_value`), including the
   100%-hide check (`has_full_discount_on_all_daily_fees`).
6. **Missing model methods** — `Discount_model::get_daily_fee_discount()` and
   `get_student_discounts()` implemented (Bus_conductor / attendance view previously fataled).
7. **`Bus_conductor.php` parse error** — stray class-closing brace left `discount_profiles()`
   outside the class.
8. Attendance view previews now use the **same canonical engine** as charging (what you see is
   what is charged) and show applied profile names.
9. Fixed discounts in `Fee_collection::calculate_discount_amounts` are capped at the fee rate.

### UX / features
* **Quick presets** on the create-discount-profile modal: Full/Half Lunch, Full/Half Class Fee,
  Full Breakfast, Full Water, All Fees FULL/50% — one click fills the form.
* **Filters** (this pass): payment history (date range, method, term, year — server-side),
  fee-collection portal reports (date range + class + CSV export), student lists
  (search + section, admin & teacher portals), teacher list (gender/status/designation),
  teacher daily payment report (**All My Classes** option).
* Server-side search bug fixed in `Ajaxdataload_model` (search dropped the income/year scope
  and the OR-grouping broke the WHERE clause).

---

## 2b. Session 2 (2026-11-02) — portals completed, whole codebase parses

### Critical fatals fixed
* **Cashier controller fataled on every request** — a stray class-closing brace orphaned all
  `daily_fee_rates*` methods outside the class (pre-existing parse error).
* **Entire vehicle/haulage module fataled** — 40 `ajaxload->` DataTable backends called by
  Driver/Vehicle/Type/Model/Insurance/Department/Expenditure/Destination/Loading/Claims
  models never existed. Implemented in `models/vehicle_ajaxdataload.php`, dispatched
  transparently through `Ajaxdataload_model::__call()` (search + full filter sets + joins).
* **Entire accountant portal fataled** — controllers referenced views that only existed for
  admin. 9 page wrappers + 4 income fragments now render; hardcoded `site_url('admin/…')`
  calls in shared views are parametrized to `login_type`-based URLs.
* **9 views were corrupted** (truncated double-paste at byte 1024) — parent/subject_teachers,
  parent/tabulation_sheet, librarian/modal_edit_book, teacher/class_routine_section_subject_edit,
  teacher/manage_online_exam_question, teacher/marks_get_subject, student/attendance_report_view,
  plus `\'` escape bugs in payments/receivables_MODERNIZED. All repaired.
* **Final sweep: 1291 PHP files, zero parse errors.**

### Views built this session (every static view reference in the codebase now resolves)
* Discount profile **create/edit modals** — grid layout, quick presets, wildcard + multi-fee
  scopes with live preview, cashier-restricted mode; `discount_profiles_modern` is now
  portal-aware (admin/cashier).
* **Cashier daily-fee receipt** (itemised, auto-print), **student receipts viewer** with
  year/term filters (+ `receipt_by_payment` route).
* **Student termly bill** — modal + printable bill (arrears + approved discounts), ported
  from an unroutable snippet into `Admin.php`.
* **Financial statement report** — monthly billed vs collected, variance/outstanding cards,
  collections by payment method, date-range navigation.
* **SMS debtor-reminder wizard** (3 steps): compose with `{name}/{class}/{amount}/{school}`
  placeholders + debtor selection → personalised preview → batch send via
  `Sms_model::send_sms_batch_personalized` with audit log (`003_create_sms_send_log.sql`)
  and an "SMS Debtors" button on the receivables report.
* **Vehicle module print/report pages**: drivers, vehicles, expenditure, trucks loading,
  all-trucks sales (per-truck aggregation), fuel usage assessment.
* **Teacher portal**: bulk cumulative reports (one sheet per student), student payment
  history table.
* **One-time sync tool UI** (+ results), **bank checkout** (parents), **public invoice
  expired** page, **404 page**, **sync test harness**, **bulk receipts print**.
* Stale method-collection "snippet" files quarantined to `application/snippets/`.

## 3. Applying migrations

Run against your database (e.g. `mysql -u user -p schoolmanager < file`):

1. `application/migrations/002_discount_rules_and_daily_fee_rates.sql`
   * `discount_profile_rules.bill_category_id` → `VARCHAR(50)` (daily-fee rules store the fee
     type name; INT coercion used to destroy it)
   * adds `daily_fee_rates.section_id` (code already writes it)
   * adds a lookup index on `student_discount_assignments`
2. `application/migrations/003_create_sms_send_log.sql` — audit table for the SMS
   debtor-reminder wizard (required before using "SMS Debtors" on the receivables report)

## 4. Connecting this repo to GitHub

```bash
cd ci3_full
git remote add origin git@github.com:<you>/schoolmanager.git   # or https URL
git push -u origin main
```

Regular commits keep history reviewable — this session's work is split into focused commits
(`git log --oneline`).

## 5. Notes

* Database name: `schoolmanager` (`application/config/database.php`).
* Views may query the DB directly (legacy pattern); keep new logic in models where possible.
* Backup files (`*.bak`, `*_backup*`, `*.broken*`, `Admin_backup.php`) are kept for history —
  do not edit them.

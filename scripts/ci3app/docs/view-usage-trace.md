# CI3 View-Usage Trace — Admin & Teacher Portals (2026-11-02)

**Purpose:** decide the real migration scope for the modern nyansapo app. Instead of migrating
every file in `application/views/`, this traces what is actually reachable in the browser from the
**live navigation** of the admin and teacher portals, then follows every page's own links, forms,
AJAX calls, popups and redirects up to 3 hops from the menu.

**Method (static, browser-equivalent):**

* Entry points = the navigation files that the layout really loads:
  * Admin: `backend/admin/navigation.php` (sidebar, all admin levels) **+**
    `backend/admin/metro_menu.php` (metro mega menu, `admin_level < 4`) **+**
    `backend/admin/navigation_cashier.php` (level-4 cashier sidebar, included by the sidebar) **+**
    `backend/header.php` (shared quick links, loaded on every page).
  * Teacher: `backend/teacher/navigation.php` (sidebar) + shared header.
* PHP/HTML comments are stripped first — commented-out menu sections are invisible to the
  browser, so they are excluded here too.
* Each menu URL is resolved through `routes.php` to a controller method; each method's
  `page_name` / `load->view()` is resolved to the real view file using the same rules as
  `backend/main.php` → `include_main.php` (`attendance/*` and `examination/*` are shared; role
  controllers render under their own folder).
* Full inventory: **`docs/nyansapo_ci3_view_usage.xlsx`** (menu maps, per-page connections,
  used/unused lists). Regenerate the raw graph with `tools/view-trace/trace_views.py` +
  `build_report.py`.

---

## Headline numbers

| | Admin portal | Teacher portal |
|---|---|---|
| Menu entries (unique URLs) | **151** (sidebar + metro + cashier + header) | **31** sidebar items |
| Controller endpoints reachable | **494** | 435 (incl. cross-links into admin pages) |
| View files in scope | **257** (admin folder + shared) | **47** (teacher folder + shared) |
| Files in role folder | 686 in `backend/admin/` | 151 in `backend/teacher/` |
| Folder files actually used | 248 | 40 |
| **Unused by ANY portal** | **438 (64%)** | **111 (73%)** |

**Do not migrate blind:** two thirds of the admin view folder and almost three quarters of the
teacher view folder are not reachable from any portal's live navigation.

---

## Key findings

### 1. Accounts & Bookkeeping module is switched OFF in the UI
The whole `SECTION 2: PROFESSIONAL ACCOUNTS MODULE` block in `admin/navigation.php`
(accounts dashboard, chart of accounts, journal entries, bank accounts, budgets, fiscal year,
all 5 financial reports, audit trail) is wrapped in an HTML comment — invisible in the browser.
No other live view links to `accounts/*`. The controllers/models still exist, but the UI is
disconnected. Decide deliberately whether the modern app needs this module.

### 2. Modernized view pairs — only 3 of 8 admin variants are wired
| Feature | Old view | Modern view | Live |
|---|---|---|---|
| Discount profiles | `discount_profiles.php` | `discount_profiles_modern.php` | **modern** |
| Students daily attendance | `students_daily_attendance.php` | `students_daily_attendance_modern.php` | **modern** |
| System settings | `system_settings.php` | `system_settings_modern.php` | **modern** |
| Curriculum standards / strands / sub-strands | unused | `*_modern.php` unused | **neither** (feature disconnected from nav) |
| Manage attendance (admin) | `manage_attendance.php` | `manage_attendance_modern.php` | **old** |
| Student bulk add | `student_bulk_add.php` | `student_bulk_add_modern.php` | **old** |
| Teacher marks manage | `marks_manage.php` | `marks_manage_modern.php` | **old** |
| Teacher navigation | `navigation.php` | `navigation_modern.php` | **old** (modern is dead code) |

### 3. Three broken flows (missing view files)
* `user_permissions::module_overview` renders `backend/admin/user_permissions_module.php` — **missing**; linked from the User Permissions page.
* `interest_items::create` → `backend/admin/interest_items/create.php` — **missing**; linked from the interest-items modal.
* `conduct_items::create` (and `::edit`) → `backend/admin/conduct_items/create.php` — **missing**; linked from the conduct-items modal.

### 4. Broken SMS button (JS bug)
`backend/admin/students_invoice_info.php:119` — the Send-SMS dropdown link emits
`navigation(http://…/admin/message/sms_send…)` with an **unquoted URL** in `onclick`
(PHP string-concat loses the quotes) → SyntaxError, button does nothing.

### 5. Teacher portal cross-links into admin pages
Teacher-reachable shared views link to `admin/*` URLs (fee-collection portal, inventory,
discount management, user permissions, conduct/interest items). Those pages render only under
`backend/admin/` and are permission-gated for teachers — they belong to the **admin** migration
scope, not the teacher scope. The XLSX "Teacher Menu Map" marks them as
*Shared header links (many are admin-gated)*.

---

## Admin portal — what the menus open

151 unique menu URLs (247 menu entries counting each nav source):

* **Admin sidebar** (`navigation.php`) — 113 entries: dashboard, students (admissions, profiles,
  ID cards, promotion), teachers, classes/sections/subjects, routines, attendance
  (`attendance/dashboard`, `attendance/report`), exams & marks (grades, marksheet lists,
  tabulation, cumulative reports, exam SMS), daily fees (rates, reconciliation, module settings),
  invoicing (all invoices, fee structure, discounts & approvals), financial reports
  (receivables/payables/payments/income-expenditure/aging), payroll (payslips, SSNIT tiers,
  statutory settings), transport, dormitory/hostel, library, messaging/SMS centre,
  settings (notification, payment, SMS automation), backup, user permissions.
* **Metro mega menu** (`metro_menu.php`, level < 4) — 103 entries mirroring the sidebar's main
  sections plus quick actions.
* **Cashier sidebar** (`navigation_cashier.php`, level 4) — 12 entries: cashier dashboard,
  daily summary, handover report, daily fee collection, receipts.
* **Shared header** — 19 quick links (profile, session/term changers, notifications, backup…).

Full table with labels, endpoints and rendered views: **XLSX → "Admin Menu Map"**.
What each page connects to (2nd/3rd hop): **XLSX → "Admin Page Connections"** (830 traced
connections).

## Teacher portal — the 31 sidebar items

| Menu | URL | Renders |
|---|---|---|
| Dashboard | `teacher/dashboard/ajax` | `teacher/dashboard.php` |
| Attendance dashboard | `attendance/dashboard` | `attendance/dashboard.php` (shared) |
| Mark attendance | `attendance/mark?class_id=` | `teacher/mark_attendance.php` |
| School-wide attendance | `admin/students_att/` | `admin/students_daily_attendance_modern.php` (admin page) |
| Attendance report | `teacher/attendance_report` | `teacher/attendance_report.php` |
| Manage exam marks | `teacher/marks_manage` | `teacher/marks_manage.php` |
| Exam reports archives | `teacher/exam_reports` | `teacher/exam_reports.php` |
| Student marksheet | `teacher/student_marksheet_list` | JSON + print views |
| Assessment graphs | `teacher/assessment_graph` | JSON/AJAX |
| Portfolio assessment | `teacher/portfolio_assessment_manage` | `teacher/portfolio_assessment_manage.php` |
| Cumulative reports | `modal/popup/modal_cumulative_report` | modal + print views |
| Create online exam | `teacher/create_online_exam` | `teacher/add_online_exam.php` |
| Manage online exam | `teacher/manage_online_exam` | `teacher/manage_online_exam.php` |
| Class routine | `teacher/class_routine_view/` | `teacher/class_routine_view.php` |
| Academic syllabus | `teacher/academic_syllabus` | `teacher/academic_syllabus.php` |
| Study material | `teacher/study_material` | `teacher/study_material.php` |
| Library | `teacher/book` | `teacher/book.php` |
| Student information | `teacher/student_information/` | `teacher/student_information.php` |
| Student promotion | `teacher/student_promotion` | `teacher/student_promotion.php` |
| Daily payment report | `teacher/daily_payment_report` | `teacher/daily_payment_report.php` |
| Transport | `teacher/transport` | `teacher/transport.php` |
| Message | `teacher/message` | `teacher/message.php` |
| Noticeboard | `teacher/noticeboard` | `teacher/noticeboard.php` |
| Profile | `teacher/manage_profile` | `teacher/manage_profile.php` |
| My payslips | `teacher/payslipList/` | JSON + payslip views |
| Teachers | `teacher/teacher_list` | `teacher/teacher.php` |
| Conduct items | `conduct_items` | `teacher/conduct_items.php` |
| Interest items | `interest_items` | `teacher/interest_items.php` |
| Head teacher remarks | `head_teacher_remarks` | `teacher/head_teacher_remarks.php` |
| Teacher remarks templates | `teacher_remarks_templates` | `teacher/teacher_remarks_templates.php` |
| Raw-score grade | `admin/grade/raw_score_grade` | `admin/raw_score_grade.php` (admin page) |

Per-page connections: **XLSX → "Teacher Page Connections"** (114 connections within teacher scope).

---

## Migration guidance

1. **Migrate the reachable graph only** — the XLSX "Used Views" sheets are the working set:
   257 admin-scope files and 47 teacher-scope files (plus the endpoints listed in the menu-map /
   connection sheets, 494 admin + 435 teacher URLs, which the modern app must expose as pages
   or APIs).
2. **Skip the 438 admin / 111 teacher unused files** unless a feature is explicitly re-enabled
   (see "Unused Views" sheet; `_modern` variants not wired are flagged).
3. **Decisions needed from the product side:** Accounts & Bookkeeping module (currently OFF),
   curriculum strands feature (disconnected), and whether the admin modernized variants
   (`manage_attendance_modern`, `student_bulk_add_modern`) should become the reference UX.
4. **Fix the three missing views + the SMS button bug** in CI3 if those flows are still needed
   before/without migration.

## Caveats

* Static trace, browser-equivalent for comments and live navs; it cannot see runtime-only
  redirects driven by DB values or JS-built URLs — those rare cases are marked `(JSON/AJAX only)`
  or missing in the sheets.
* Header quick links are shared across portals; admin-gated links appearing in the teacher trace
  are marked as admin-gated.
* Depth limit is 3 hops from the menu (menu → page → secondary → tertiary), which covers every
  flow we found; deeper hops only repeat endpoints already in the graph.

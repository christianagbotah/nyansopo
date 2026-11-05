# Wave 10 — CI3 back-office parity: results governance, exam question types, advanced finance, fleet, coupons, WAEC statement

## User asks (this wave)
"continue with the project" / "go on" — following wave 9's gap audit of the
CI3 source, implement the remaining high-value CI3 features with
enhancements: academic results control, online-exam question types &
tools, advanced financial reports, driver/vehicle/insurance masters,
transport fare reports, dining coupons, teacher remark templates (already
present as school-level library), WAEC statement of results.

## What shipped

### 10.1 Academic results control (3ec69ae + 60cedf8)
- Prisma `ResultApproval` (unique classroom+term; DRAFT→SUBMITTED→
  APPROVED→LOCKED; submitted/approved/locked by+at; notes) +
  `ResultApprovalAudit` (action, prev→new status, performer, reason).
- API `ops/academic-control`: GET metrics per class — portfolio %
  (scored PortfolioEntry in term), class-work % (any non-exam assessment
  mark), exam % (END_OF_TERM marks) + status; ?classroomId= adds the audit
  trail. POST submit/approve/lock/unlock with the exact CI3 state machine
  and error strings ("Results already submitted", "Results must be
  submitted first", "Reason required for unlock", …). Unlock restricted to
  SUPER_ADMIN/GROUP_OWNER/SCHOOL_ADMIN (CI3 admin_level 1–2).
- **The gate CI3 never wired now actually runs**: `src/lib/result-approval.ts`
  `assertResultsEditable` (blocks when ≠ DRAFT) on gradebook
  save-marks/create-assessment/delete-assessment, portfolio.save/aggregate,
  sba-sheet-save, sba-portfolio-fill, creche-ratings-save;
  `assertResultsComputable` (blocks when LOCKED) on report-card generate.
  HTTP 423 with "Contact the head teacher to unlock."
- Reports module → "Results control" tab: class cards (CI3 dashboard
  colours: draft yellow / submitted blue / approved green / locked red),
  three progress bars, action button per state + Audit, notes dialog,
  unlock requires a reason.
- Browser-verified: Basic 1 DRAFT→SUBMITTED→APPROVED→LOCKED→unlocked with
  the full audit chain and "submitted/approved/locked by Madam Grace Mensah".

### 10.2 Online exam question types + tools (5bc31fd)
- `OnlineExamQuestion.type` MCQ | TRUE_FALSE | FILL_BLANKS; options
  nullable; `blankAnswers` JSON array (lowercased, trimmed); hyphen `-`
  marks each blank (CI3 convention).
- Marking: MCQ exact option; T/F exact TRUE/FALSE; fill-blanks
  case-insensitive word-for-word, all-or-nothing (CI3 parity). Server
  validates blank-count == word-count with a clear message.
- **Live monitor** (CI3 left a static placeholder — ours is real):
  `start-attempt` stamps `startedAt` when a learner opens the paper;
  GET ?monitor=examId returns roster (class enrollments ∪ attempts) with
  NOT_STARTED / IN_PROGRESS / SUBMITTED + score; Monitor dialog polls
  every 5 s with auto-refresh toggle and three count tiles.
- Printable question paper (CI3 print_view layout): school header + logo,
  title pill, class/subject/marks/duration, instructions; blanks render as
  underlines; "With answers" toggles ✓/correct-answer annotations.
- Student portal: per-blank inputs (one per hyphen — easier than CI3's
  single comma box), True/False buttons, countdown that auto-submits at
  zero (CI3 modal_time_out parity), "Continue" for started attempts.
- Closing an exam finalises in-progress attempts; delete clears attempts +
  questions first (FK).

### 10.3 Advanced financial reports (6c5d2c4)
- `ops/reports?tab=advanced&from&to&studentId` (CI3 Advanced_reports
  parity): summary (billed/collected/avg payment/expenses/net/outstanding/
  overdue), monthly cash flow (inflow−outflow), payment-method mix —
  **SPLIT payments expanded into their parts** (wave-8 note honored),
  expense categories, per-learner profile (invoices, payments, monthly).
- Finance module → "Advanced reports" tab: KPI cards, recharts cash-flow
  bars, method bars, category table, outstanding/overdue panel, learner
  picker (fixed `x.name` mapping — students API returns precomputed name),
  CSV export per table.

### 10.4 Transport fleet + fare report (99c838b)
- Fleet tab (CI3 Driver/Vehicle/Insurance masters in one view): driver
  registry (DVLA license no + expiry) and vehicle registry (insurance +
  roadworthy expiry) with compliance chips EXPIRED / due <30d / valid and
  aggregate stat cards; routes gain optional driverId/vehicleId links
  (route editor gets "Link driver/vehicle record" selects; linked cards
  show compliance inline).
- Fare report tab (CI3 transport_fare_report parity): boarding-based
  collections over a date range + route filter; stats (boardings, fares
  charged, collected, owing, both-ways/one-way) + Excel (CSV) export.
- Demo data added through the UI: driver Kwesi Anane (DVLA-2026-118,
  valid → 15 Mar 2027), vehicle GR 4521-21 (insurance valid, roadworthy
  due <30d).

### 10.5 Dining coupons (3071a61)
- `ops/daily?tab=coupons&date&serviceId`: every learner charged a
  MEAL/DRINK service on the date (WAIVED excluded), payment type resolved
  (Direct payment / Prepaid wallet / Owing).
- Daily Services → "Dining coupons" dialog: 4×5 slips per page (CI3 grid),
  colour-keyed per category (meal green / drink cyan), name + code + class
  + amount badge, "Valid … Expires: 5:00 PM", faded placeholders complete
  the grid; print via the standard print-area flow.

### 10.6 WAEC BECE statement (3071a61)
- New "BECE statement" sheet style in Reports (CI3 Examination::waec_result
  parity — with the controller/view contract bug FIXED per CI3's own
  caption): double-border statement, candidate name + index number, CORE +
  ELECTIVE subject tables graded on the WAEC scale (A1 80–100 … F9 0–39
  with interpretations), **aggregate = best 4 core + best 2 elective
  points** (lower is better — not CI3's broken mean), grading key, head
  signature + school/office stamp boxes, official-document footer.

## Verification
- `scripts/smoke-backoffice.ts` — **43/43**: control metrics shape; all
  state-machine refusals with exact strings; marks gate 423 + reopen;
  audit cycle; mixed-type exam validation + save + learner key-strip;
  start-attempt → monitor IN_PROGRESS/SUBMITTED; 6/6 per-type marking;
  second-submission refusal; SPLIT expansion; profile shape; fleet CRUD +
  compliance + links + cleanup; fare report filters; coupon rows.
- Full regression **21/21 suites** (639+43 checks; portals re-verified
  standalone — the sandbox reaps the server mid-chain, not a regression).
  tsc 0 errors; eslint 0 errors on changed files (4 set-state-in-effect
  issues fixed: derived coupon defaults, key-based loading flag, timeout-
  wrapped first poll + countdown).
- Browser (prod build, head teacher @ GEA): results-control full cycle +
  audit modal; exam type editor + print paper with/without answers; fleet
  CRUD with chips; fare report; coupons on live data (Prepaid wallet +
  Direct payment slips); BECE statement for JHS 3 (Elikem Antwi, core A1s,
  aggregate 4, full grade key). Screenshots: shot-results-control,
  shot-audit-trail, shot-exam-paper(-answers), shot-advanced-reports,
  shot-learner-profile, shot-fleet(-driver), shot-fare-report,
  shot-coupons, shot-bece-statement.
- Cleanup: leaked SMOKE exams from the two crashed smoke runs deleted;
  orphan "SMOKE Clone Room" classroom removed (fee items → structures →
  classroom).

## Commits
3ec69ae schema · 5bc31fd exams · 60cedf8 results control · 6c5d2c4 finance ·
99c838b transport · 3071a61 coupons+WAEC · ff468a4 smoke suite

## Notes for later agents
- The sandbox now reaps the server BETWEEN tool calls — use
  `bash scripts/with-server.sh <cmd>` for any browser/HTTP work; smoke
  suites must start the server in the same command.
- `assertResultsEditable` gates by (classroomId, termId|current-term);
  new marks-bearing endpoints should call it — DRAFT never blocks, so
  existing flows stay untouched until a head teacher actually submits.
- FILL_BLANKS answers are stored lowercase+trimmed JSON; client sends one
  input per hyphen, joined with commas server-side compatible.
- Payment-method grouping MUST expand `splitJson` (see the advanced tab /
  cashier byMethod) — never group by the raw `method` column.
- Remaining CI3 gaps (deliberate, product-direction calls): double-entry
  accounts & bank reconciliation (Accounts.php), internal messaging,
  public school-website CMS, email channel, DB backup/restore UI,
  financial alerts, transaction recovery.

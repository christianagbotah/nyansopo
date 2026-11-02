# CI3 Priority-View Review — Migration Coverage

Review of the specific iEduPro CI3 views requested for migration, produced by
tracing the live CI3 code (`application/views/backend/…`) and comparing each
view's behavior against the modern app. This wave closed the gaps marked
**[FIXED]**; everything else was already covered by earlier parity work.

Verdicts: ✅ covered · 🟡 partial (gaps listed) · ❌ missing (before this wave)

## Fees & payments

| CI3 view | Verdict → now | Notes |
|---|---|---|
| `student_payment.php` (billing hub) | ✅ | invoices, structures, discounts, arrears chase-list, approvals, receipts analytics all live. Bulk arrears CSV import + clone-previous-bills remain open ideas |
| `modal_take_payment.php` | ✅ | per-invoice PayDialog with MoMo/cheque refs, offline queue, auto receipt SMS. **[FIXED]** overpayment now creates credit; cheque/bank fields pass through |
| `fee_collection_portal.php` | ✅ | Daily Services POS (counter/prepaid/transport, tendered/change, mark present, offline queue) |
| `fee_collection_receipt.php` | ✅ | Thermal + formal A5 receipt styles |
| `get_student_payment_history.php` | ✅ | Receipts tab with filters + modification requests |
| `student_credits.php` + `credit_statistics.php` + `student_credit_widget.php` + `receipt_credit_integration.php` | ❌ → **[FIXED]** ✅ | full credit system: overpayment→credit ledger, auto-apply at billing, adjust / learner↔learner transfer / convert to daily-fee wallet, stats + utilization, PayDialog credit widget, "CREDIT CREATED" receipt line |
| `creche_bill.php` (+ landscape) | 🟡 | Terminal bills tab (arrears + next-term, A5, print-all). **[FIXED]** adds "LESS: Prepaid Credit Applied" line. Landscape variant + per-item discount breakdown remain open |

## Admissions, settings, classes, staff

| CI3 view | Verdict → now | Notes |
|---|---|---|
| `student_add_new.php` | 🟡 | 6-step admission wizard covers the core; father/mother split fields, medical section, photo upload, boarding allocation remain open |
| `student_bulk_add_modern.php` | ❌ → **[FIXED]** ✅ | full bulk CSV pipeline: template → validate (row errors, dupes, existing-guardian warnings) → confirm reuse → import with sequential rollNo + NY-YYYY-NNNNNN indexes |
| `system_settings_modern.php` | 🟡 | profile/curriculum/grading/WAEC/remarks/conduct + **[FIXED]** early-years rating scale & domains, SBA marks config (maxes + weights + portfolio auto-fill 30%). Branding uploads, term runtime (days opened), receipt-style picker remain open |
| `class.php` (class & section) | ✅ | classes + sections via classrooms, timetable with overlap checks, print routine |
| `modal_teacher_add.php` | 🟡 → **[FIXED]** ✅ | staff-code auto-generation (TCR-/NTS-/ADM- prefixes, live uniqueness check), sex/DOB/Ghana Card/SSNIT/Tier-2/bank fields, optional linked login. Photo upload remains open |

## Attendance & assessment

| CI3 view | Verdict → now | Notes |
|---|---|---|
| `mark_attendance.php` / `manage_attendance_view.php` | ✅ | Register tab; **[FIXED]** sick @ home / sick @ clinic / busy statuses + excused-with-reason + mark-all buttons |
| `attendance_report.php` + monthly grid + print | ❌ → **[FIXED]** ✅ | Analytics tab (range stats, per-learner rates with badges, CSV export) + Monthly grid tab (day letters, legend, print) |
| `student_marksheet*.php` (selection, print ×3, bulk) | ✅ | reports module + report-sheets (Classic/Modern/Compact), whole-class print |
| `marks_manage.php` (SBA) | 🟡 → **[FIXED]** ✅ | SBA sheet tab: four fixed columns with configurable maxes, sub-total → class score conversion, exam weighting, portfolio → class-test auto-fill |
| creche marksheet family (`*_creche.php`, `grade_creche.php`) | ❌ → **[FIXED]** ✅ | rating-scale + NaCCA domains/skills CRUD, per-learner ratings entry, early-years report cards + A5 print sheet |
| `portfolio_assessment/` (dashboard, sba_management) | ✅ | Portfolio (weekly SBA) tab + auto-fill sync into the SBA sheet |

## Dashboards & navigation

| CI3 area | Verdict → now | Notes |
|---|---|---|
| Admin dashboard | 🟡 → **[FIXED]** ✅ | daily-fees received-today strip per service + unpaid-balances card, gender & classroom distribution charts |
| Cashier dashboard | ✅ | cashier console (today/week/month/term, per-fee breakdown, handover) |
| Teacher dashboard | 🟡 → **[FIXED]** ✅ | My-classes grid (learners, present/absent today, deep-link to register) |
| Metro menu | ✅ | grouped sidebar covers all 18 CI3 tile groups, RBAC-gated; global Ctrl+K palette remains open |
| `sync_status_bar.php` | ✅ | offline badge + queued-op count + manual sync (superset of the deprecated CI3 bar) |
| Shared header | ✅ | school switcher, profile menu, theme, PWA install; notification bell remains open |

## Cross-cutting fixes this wave

- **Searchable selects**: every `<Select>` with ≥ 7 options now gets a filter box
  (type-to-filter, Enter picks first match, arrows navigate, Esc closes) —
  all ~144 call sites benefit with zero call-site changes.
- **Service worker**: no longer caches anything on localhost — Turbopack reuses
  chunk filenames across edits, so cached chunks silently ran stale code.
- **Horizontal scroll**: base `grid-cols-1` added to 45 responsive grids +
  `minmax(0,1fr)` tracks + trigger label fixes — audited in-browser at
  390/768/1280 px across all 19 modules, zero page-level overflow.

## Open items (not blocking, tracked for later)

1. ~~Split father/mother guardian fields + medical section + photo upload in the
   admission wizard; boarding house/bed allocation at intake.~~ — closed by
   open-items wave 1 (`dded0fa`).
2. ~~Terminal-bill landscape variant + per-item discount breakdown.~~ — closed
   by wave 1.
3. ~~Settings branding uploads (logo, head-teacher signature) + term runtime
   (`days opened` per term) + receipt-style picker.~~ — closed by wave 1.
4. ~~Notification centre (bell + list) and Ctrl+K command palette.~~ — closed
   by wave 1; wave 2 (`lesson-note SMS`) fixed the record-request bell item
   (deep-links to Admissions › Record requests; gated on `admissions.manage`
   so school admins actually see it).
5. ~~Bulk arrears CSV import + clone-previous-term bills in Fees.~~ — closed
   by wave 1.
6. ~~Print layouts assume A4 portrait; wide months/classes render small.~~ —
   closed by wave 1 (landscape toggles + per-sheet `@page` rules).

**Follow-ons closed by wave 2** (`agent-ctx/10-lesson-note-sms.md`): SMS to
teachers when GES lesson notes are returned for revision (default on) or
endorsed (opt-in rule), with school × teacher × credits gating and
self-service opt-out in the Staff Portal — iEduPro `Sms_model` /
`sms_automation` / `user_notification_preferences` parity.

**Closed by wave 3** (`agent-ctx/11-media-sms-gateway.md`): portraits &
branding images now live as binary `MediaAsset` rows served from
`/api/media/{id}` (auth + school-scoped, immutable cache) instead of base64
data-URLs inside hot rows — `scripts/backfill-media.ts` migrates legacy
values. The real SMS gateways are wired: `SmsSetting.provider` ARKESEL /
TERMII dispatches through `src/lib/sms-gateway.ts` (env-keyed, Ghana
MSISDN normalisation, provider message refs, debit-only-on-success); the
SMS Centre shows which gateways have keys configured. Remaining for
rollout: set `SMS_ARKESEL_API_KEY` / `SMS_TERMII_API_KEY` in the
deployment environment.

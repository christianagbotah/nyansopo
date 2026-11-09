# Task 9 — Open-items wave 1 (work record)

Cleared the tracked open items from `docs/ci3-priority-view-review.md` §"Open
items": notification centre, Ctrl+K palette, admission-wizard completeness,
settings branding/runtime/receipt-style, bulk arrears CSV + clone-previous-term
bills, terminal-bill landscape + per-item discount breakdown, landscape print
CSS for wide sheets.

## 1. Notification centre (bell + list)
- `GET /api/notifications?schoolId=…` — derived at request time from live
  tables, permission-gated per item: pending modification requests +
  cross-school record requests (`approvals.manage`), pending discount
  applications (`discounts.manage`), lesson notes awaiting endorsement
  (`curriculum.review`) or own returned notes (`curriculum.manage`), low/out-of
  stock (`inventory.view`), overdue invoices with outstanding total
  (`fees.view`). Severity-ranked (danger→info).
- `NotificationBell` (app shell): polls 60s + on focus, per-user "last read" in
  localStorage (`nyansapo-notif-read-{userId}`), unread badge (red pulse when a
  danger item exists), click → deep-link (module+tab), mark-all-read.
- Inventory module now consumes `navTarget.tab` (bell "low stock" lands on the
  Low stock tab).

## 2. Ctrl+K command palette
- `CommandPalette` on the global ⌘K/Ctrl+K handler + a header Search button:
  Modules group (the session's permission-gated nav), Jump-to-class group
  (flattened class deep-links for Students/Classes), Actions (sync offline
  queue, theme toggle, sign out).

## 3. Admission wizard completeness (iEduPro student_add_new parity)
- Schema: Student += father/mother name+phone+occupation, nationality,
  religion, bloodGroup, allergies, medicalConditions, emergencyContact,
  photoUrl (data-URL).
- Wizard: Learner step gains nationality/religion + portrait upload
  (client-resized ≤320px JPEG) + Medical & welfare block; Guardian step is now
  Primary guardian + Father / Mother columns; Placement step offers optional
  boarding allocation (house → dorm → AVAILABLE beds only); Review step shows
  everything; success screen shows the assigned bed.
- API admit: persists all fields, validates the portrait (image data-URL
  <700KB), fail-fast bed claim (409 if taken), `photoSeed` fallback.
- Student profile dialog: "Family & medical" card appears when any field is
  captured.

## 4. Settings completeness (system_settings_modern parity)
- New `update-preferences` action + GET payload additions: `branding.logoUrl`,
  `branding.signatureUrl` (sanitised data-URLs), `receipts.style`
  (A5/FORMAL/MODERN/THERMAL), `term.daysOpened:{termId}` (0–400, foreign-term
  404).
- Profile tab: "Branding & receipts" card (logo + signature uploads with
  previews, default receipt style picker, save).
- Calendar tab: per-term "Days opened" inputs + save-changes button.
- Receipt wiring: `receiptSchool()` now returns logoUrl/signatureUrl +
  `preferredStyle`; every builder sets `style` from the saved preference; A5
  letterhead swaps in the school logo; FORMAL bursar signature line and MODERN
  stamp render the uploaded signature.

## 5. Fees: clone-previous-term + bulk arrears CSV
- `clone-structures`: copies every fee structure (items + optional flags) from
  a chosen source term into the current term, skipping already-billed classes;
  self-clone 400. Structures tab gains a "Clone previous term" dialog.
- `import-arrears`: CSV rows `studentIndex,description,amount` → one OVERDUE
  arrears invoice per row on the current term's chase-list (sync-safe invoice
  numbers, unknown-index/invalid-amount row errors, duplicate rows skipped on
  re-import). Arrears tab gains an import dialog (template download, paste or
  upload, per-row results).

## 6. Terminal bills: landscape + per-item discount breakdown
- Portrait/Landscape toggle on the generated-bills toolbar; landscape injects
  `@page { size: A4 landscape }` + `.landscape-sheet` width (272mm).
- The items table is now Gross | Discount | Net with the invoice-level discount
  apportioned pro-rata across items (last row absorbs rounding), a
  "Total discount — <reason>" row, and Paid-to-date spanning correctly.

## 7. Landscape print CSS for wide sheets
- Attendance Monthly grid: Portrait/Landscape toggle (defaults landscape) +
  injected @page rule.
- Classes & Timetable "Print routine": always landscape via mounted @page rule.
- `globals.css`: `.a5-sheet.landscape-sheet { width: 272mm }` under print.

## Verification (all green)
- New suites: `smoke-admissions-extra` 14/14 (incl. bed race 409 + junk-photo
  rejection), `smoke-preferences` 11/11 (incl. THERMAL default flowing into a
  real payment receipt payload + logo on the letterhead block),
  `smoke-fees-parity` 9/9 (clone ≥1 + items + self-clone 400; arrears 2
  imported/2 failed/dupes skipped/bad header 400).
- Regression: codes 23, credits 40, curriculum 29, attendance-analytics 29,
  bulk-admission 48, creche-SBA 64, staff-dashboard 51, portals 24+24 —
  **318 checks, 0 failures**.
- `tsc --noEmit` 0; eslint 0 errors on every changed file.
- Browser (head account): bell "4 unread" → items with counts/details →
  deep-link to Fees › Arrears → badge clears; ⌘K palette filters ("curric" →
  Curriculum & Notes) and class deep-link lands on Basic 4 — Gold roster;
  Settings Branding card + days-opened inputs render; Fees shows both new
  buttons. Zero page errors.

## Notes for later agents
- The injected `<style>{"@media print { @page … }"}</style>` pattern is the
  only reliable way to vary print orientation per sheet — @page rules cannot
  be scoped by class.
- Record-request notifications deep-link to the Students roster (no
  school-wide requests surface yet — the requests live per-student in the
  profile dialog's Network tab).
- Admission portrait + branding images are stored as data-URLs in Postgres
  (client-resized ≤700KB); move to blob storage before large-scale rollouts.

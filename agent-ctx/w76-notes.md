# Wave 76 — Term Closing & Rollover

## Why
The roadmap's named items (Library w74, Transport w75) were done. The end-of-term
walk — results governance → billing → library returns → close → next term — was
stitched across four modules with no single room. This wave gives it one.

## Delivered
- `Term.closedAt DateTime?` (schema + db push).
- `ops/term-close` route: GET readiness board (results per class from the
  academic-control approvals, billing coverage, library out/overdue, registers,
  calendar next term) + POST `close` (results gate unless force, audited LOCK) /
  `reopen` (audited UNLOCK) / `rollover` (refused while open unless force,
  refused with calendar guidance when no successor; flips isCurrent through the
  same transaction invariant as sessions' set-current-term).
- Settings → Calendar & terms: the Term closing panel — five chips (green/amber),
  Close / Reopen / Roll over buttons, confirm dialog naming unlocked classes with
  an explicit force checkbox.

## Gotchas
- `create-year` returns `{ ok, yearId }` (no year object) — the first smoke run
  mistyped it, orphaned a "SMOKE 2099/2100" year AND left the school on Second
  Term; both restored by hand before the rerun (delete-year + set-current-term).
- Billing coverage can exceed 100% (billed students who left ACTIVE enrollment)
  → capped at 100 in the API.
- ResultApproval "APPROVED" ≠ locked: seeded school had 1 approval of 11 classes,
  0 locked → the refusal path is always live on the demo school.

## Smoke — scripts/smoke-term-close.sh (11 checks, state restored)
Board shape · teacher read-yes/close-no · refusal names the unlocked count ·
forced close + stamp · double-close refused · reopen · rollover-while-open gate ·
forced rollover lands on Second Term (Third Term next) · restore via
set-current-term · no-successor guard on a throwaway year (then deleted) ·
final state = seeded state.

## Verified live (browser, Settings → Calendar & terms)
Panel renders chips + buttons; close dialog names Basic 1/2/3…; forced close
flips the badge to "closed 27 Nov 2026" with toast; Reopen restores "open".

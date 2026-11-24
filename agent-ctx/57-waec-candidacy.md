# Wave 57 — WAEC Candidacy (BECE / WASSCE registration & mock analysis)

Ghana-tailored examination deepening per the standing directive ("much attention
on … examination"). Every JHS3/SHS3 year runs through WAEC; this module runs the
whole candidacy pipeline in-house.

## Schema (prisma/schema.prisma)
- `WaecCentre` — exam type (BECE | WASSCE | NOVDEC) × year, WAEC centre number
  + name, per-candidate registration fee, OPEN/CLOSED status.
  Unique per school + type + year.
- `WaecCandidacy` — student × centre, serial (roll order), index number,
  status REGISTERED → INDEXED → SUBMITTED (WITHDRAWN side-track), feeInvoiced
  flag. Unique (centre, student) + (centre, serial).
- `WaecSubjectEntry` — subjects sat per candidate (name, WAEC code, isCore).
- `WaecMockResult` — objective / theory / practical per subject × mock (1–3),
  maxScore, total, auto grade + BECE aggregate weight. Unique per
  candidacy+subject+mock.

## API — src/app/api/school/[id]/ops/waec/route.ts
GET centres (full include) · classrooms · subjects · RawScoreGrade bands ·
class-roster picker helper. POST actions: save-centre (upsert), delete-centre,
register-bulk (ACTIVE enrolments, dedupe, serial = max+1), withdraw /
reinstate / set-status, assign-indexes (centre№ + serial padded to 10 chars —
5021406 + 7 → 5021406007; manual-index clash guard), set-index (manual,
uniqueness-validated), save-subjects (replace set), copy-class-offering
(timetable → assessments → school core subjects fallback), invoice-fees
(bulk: amend current-term invoice or mint INV- via the sync-safe counter,
idempotent title check), save-mock (auto-grade on RawScoreGrade bands),
delete-mock. Audit trail on every action.

## UI — src/components/modules/waec.tsx
Tabs Candidacies / Mock results / Analysis. Centre strip + stat cards.
Register-candidates dialog (class roll → checkboxes, already-on-roll guard).
Candidate table: serial, index, subjects, fee, status, withdraw/reinstate,
candidate-slip print (A5) + entry-summary print (A4 landscape). Subject-entries
dialog (copy offering / per-row subject picker with core flag). Mock dialog
(mock 1–3, obj/theory/practical/max inputs, clear-mock). Analysis: per-mock
aggregate boards (class avg + best), subject credit-rate tables with bars,
candidate broadsheet with CSV export. `exams.waec` perm (admin ladder + teacher),
sidebar children, metro tile, three Ctrl+K page-index entries.

## Verified in-browser (agent-browser, head admin)
- centre BECE 2027 №5021406 created → strip shows Centre № / Open / ₵350
- 13 candidates registered from JHS 3 (B9) roll (all pre-checked, 0 skipped)
- assign-indexes → 5021406001…5021406013 (10-char WAEC format), INDEXED status
- copy class offering → 11 subjects (timetable grid was subject-less; school
  core fallback fired) → saved
- bill registration fees → ₵4,550 invoiced, DB: INV-2026-0113+ carry
  "BECE 2027 REGISTRATION FEE" ₵350 items (amend-existing path, 13/13 billed)
- mock 1 for Boateng Kojo: Eng 38+45=83→A1(1), Maths 35+38=73→B2(2),
  Sci 30+30=60→C4(4), SocSt 25+25=50→C6(6) — DB-verified grades/points
- analysis: aggregate board 13.0 avg / 13 best (hand-checked 1+2+4+6),
  English Language 1 sat / 1 credit+ / 100%, broadsheet + CSV button
- command palette "WAEC" → deep-links all three tabs; metro tile + sidebar
  children present; dashboard regression clean (62% = old 63% with new ₵4.55k
  billed base); zero console errors throughout

## Notes
- One mid-flight bug fixed during verification: copy-class-offering saved
  server-side but the dialog's local state didn't refresh — API now returns
  the copied subjects and the dialog applies them.
- Invoice-fees amends the current-term invoice when one exists (same policy as
  the mass-invoice wizard) — 13 amended, 0 created this run.

Screenshots: agent-ctx/w57-*.png

# Wave 73 — Break hours & free periods: the booking rule the desk can see

> Follow-up to wave 72 (Front-desk Appointments & the visitor answer workflow).

## The rule, made explicit

User clarification: **teachers and non-teaching staff CAN be visited during break hours** —
only lessons-in-progress are off-limits, and odd hours (outside Mon–Fri 07:00–17:00)
remain administration-only. Wave 72 already enforced the "no pulling a teacher out of
a lesson" half (`teachingClash`) and let gap times through implicitly; wave 73 makes the
open doors *visible and one-tap* instead of an invisible gap the desk has to guess.

## What changed

### 1. Free-window engine — `src/lib/appointments.ts`
- `staffFreeWindows(db, staff, date)` → `{ anytime, wholeSchoolDay, windows[] }`:
  - **ADMINISTRATION** → `anytime: true` (the head's door never closes).
  - **NON_TEACHING** → `wholeSchoolDay: true`, one "School hours" window (07:00–17:00).
  - **TEACHING** → the complement of their `TimetableSlot`s for that weekday inside
    school hours; gaps ≥ 15 min, merging abutting slots across classes, labelled
    **Before first lesson / Break (≤ 40 min) / Free period / After school** — and
    "No lessons — the whole day is free" when the timetable is empty that day.
- `nextFreeHint(db, staff, at)` → "Their next free slot that day is 08:40–09:20 (break)."
  (or "No free slot left that day — try another day.") for refusal messages.

### 2. API — `ops/frontoffice` + public decision route
- New GET `?action=staff-free&staffId=&date=` (perm `frontoffice.view`) returns the
  windows + category for the booking form's chips.
- Every class-clash refusal (`appointment-create`, `appointment-respond` RESCHEDULE,
  `appointment-reschedule`, public `/api/public/appointments/[code]` RESCHEDULE) now
  appends the next-free hint to the message and carries `freeWindows` in the 400
  payload — the refusal tells you *where the open door is*, not just that it shut.

### 3. Desk UI — `src/components/modules/front-office.tsx`
- `FreeChips` component: one-tap chips of the staff member's free periods for the
  chosen date, in **both** the Book-appointment dialog and the desk Move dialog
  (tap sets the time field; verb adapts: "book the visit" / "move it").
- Category-aware hints under the staff picker: teachers → free periods inside school
  hours; non-teaching → any time in school hours; administration → any hour
  (matches the odd-hours mirror already in the picker).

### 4. Smoke — `scripts/smoke-appointments.sh` (checks 9c)
- `staff-free` returns Isaac's Monday windows and surfaces the 08:40–09:20 timetable
  gap as a break; a booking INTO the first free window succeeds; the class-clash
  refusal carries the "next free" hint; Grace (administration) reports `anytime`.
  All 20 checks green.

## Verified live (browser, desk account)
- Book dialog → Isaac Boateng + Mon 30 Nov → chips **Before first lesson 07:00–08:00,
  Break 08:40–09:20, After school 10:00–17:00**; tapping the Break chip set 08:40 and
  the booking landed (APT-2026-0008, SMS request SENT).
- Booking the same teacher at 08:15 (inside Mathematics 08:00–08:40) → refused with
  "Class clash: … teaching Mathematics (Basic 4 — Gold) 08:00–08:40 on Mondays …
  Their next free slot that day is 08:40–09:20 (break)." — toast captured in
  `agent-ctx/w73-clash-toast.png`.
- Move dialog on the teacher-hosted row shows the same chips (`w73-move-chips.png`).
- Console clean; rows used for E2E cancelled afterwards.

## Ops notes (this session)
- Sandbox recycle had rewound local git (ahead 2 / behind 25 vs origin) → safety-net
  reported `PUSH=failed`; fixed with `git reset --hard origin/main` (working tree was
  clean; no work lost) and re-run: `PG=up DEPS=ok SEEDED=10 BK=ok PUSH=ok SERVER=up`.
- The restored cluster snapshot predated wave 72's schema (`Appointment.visitorPhone`
  missing, P2022) → `bun run db:push` synced it; smoke then fully green.
- Dev server was OOM-killed mid-verification; the wave-60 watchdog revived it (~2 min).

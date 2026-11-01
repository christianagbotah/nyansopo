# Task 3-b — Barcode-scanner attendance + bulk student ID cards (work record)

## Scope delivered
Implemented iEduPro `admin/barcode_scanner` parity (gate check-in/check-out console with
arrears gate) and `admin/print_bulk_id` parity (printable class sheets of learner ID cards)
in the Nyansapo Next.js 16 + TS + Prisma/PostgreSQL app at `/home/z/my-project`.

## Files changed (only these three)
1. `src/app/api/school/[id]/ops/scanner/route.ts` — full API (replaced stub)
2. `src/components/modules/attendance.tsx` — added "Scanner" tab (register tab untouched)
3. `src/components/modules/students.tsx` — added "ID cards" tab (roster tab untouched)

## API design (ops/scanner)
- **GET** `?date=YYYY-MM-DD` — perms `["attendance.view"]` → day's `sessions`
  (classroom + records with learner name/index/status), `summary` {checkedIn (distinct
  PRESENT/LATE learners), present, absent, late}, `recentScans` (newest first, capped 200).
- **POST** — perms `["attendance.take"]`. Actions:
  - `scan { code, mode: CHECK_IN|CHECK_OUT, approve?, date? }` — learner found by
    `nyansapoIndex` **case-insensitive** (`mode: "insensitive"` on unique index), scoped to an
    ACTIVE enrollment in the school (else `200 { found:false, message:"Unknown learner code" }`).
    - **CHECK_IN**: duplicate (day PRESENT/LATE record exists → `{ duplicate:true }`); arrears
      gate (sum of `totalCents − discountCents − paidCents` over the learner's non-written-off
      invoices in the school; owing > 0 without `approve` → `200 { denied:true, amountOwedCents,
      learner, classroom }`, nothing marked); else find/create the day's register session
      (`(classroomId, date)`, `takenByName: "Scanner — {operator}"`, current term) and **upsert**
      the record to PRESENT (flips a register ABSENT; tracks recordId + priorStatus in the log
      for undo) → `{ granted:true, approved?, learner, classroom }`.
    - **CHECK_OUT**: requires a PRESENT/LATE record today (else `{ error:"Learner has not
      checked in" }`). **Storage decision:** `AttendanceSession` has NO `type`/session field and
      every existing flow (register GET/POST, daily-billing markPresent, sync) does
      `findFirst({classroomId, date})` assuming ONE session per class/day — a second "afternoon"
      session would corrupt those lookups. So, per the task's sanctioned fallback, checkout is
      recorded in the scan log only (register stays PRESENT; no extra session, no daily charge).
  - `cancel { studentId, date }` — removes the learner's **scanner-created** record (or restores
    the register status it flipped, e.g. ABSENT), deletes an empty scanner-created session so
    the teacher's register stays clean, logs a CANCELLED event → `{ cancelled:true }`.
- **Role gate (both verbs)**: after `opsAuth`, role must be SUPER_ADMIN or SCHOOL_ADMIN
  (iEduPro admin level ≤ 2) else `403 { error: "Scanner is restricted to administrators (level ≤ 2)" }`.
- **Scan log**: module-level in-memory `Map<schoolId|date, ScanEvent[]>` (local-memory cache per
  stack conventions), pruned after 2 days, 500 events/day cap. Attendance state itself is the DB
  register — the log adds timestamps, denials, checkouts and cancel bookkeeping.

## UI — attendance.tsx "Scanner" tab
- Module restructured into `Tabs` ("Register" default | "Scanner"); the register tab's markup,
  logic and offline-queue save are byte-identical in behavior. `navTarget?.tab === "scanner"`
  preselects (same render-time-adjust pattern as the students module).
- Scan card: Check in / Check out segmented toggle (aria-pressed), AUTO/MANUAL segmented note
  (helper text switches: hardware-Enter vs manual), single autofocus mono input (form submit on
  Enter), "Approve owing learner" Checkbox with helper text (amber-highlight while a denial is
  active, **auto-resets after every scan** so an override never leaks to the next learner).
- Result banners: green "Welcome — {name} checked in" (+ "Admitted with operator approval —
  still owes GH₵X"), green "Checked out — {name}", amber "Denied — {name} owes GH₵X (tick
  Approve to admit)", red duplicate / unknown / rejected (aria-live status).
- Learner feedback panel: initials avatar (`avatarColor`+`initials` — matches students.tsx
  convention; no photoSeed renderer exists in the app), name, class, mono index, guardian phone.
- Today's scans table (max-h-96 nice-scroll): time, learner+index, class, outcome pill
  (GRANTED / APPROVED / DENIED / DUPLICATE / UNKNOWN / CHECKED_OUT / NOT_CHECKED_IN /
  CANCELLED), per-row Cancel button on active scanner check-ins. Summary chips card
  (checked in / present / late / absent) + session count; log date input.

## UI — students.tsx "ID cards" tab
- Top-level `Tabs` ("Roster" | "ID cards"); `navTarget.tab === "idcards"` preselects, and the
  roster's `navTarget.classroomId` preselect feeds the cards' default class. Profile sheet and
  all dialogs stay mounted outside the Tabs.
- `IdCardsTab`: fetches the existing `students?status=ACTIVE` API once per mount (remounts via
  `key={schoolId}`; falls back to the already-loaded roster while fetching); class `Select`
  from the settings classrooms; badge "N cards · M sheets"; **Download CSV** (index/name/class/
  guardian/phone/gender); **Print sheet(s)** → `window.print()`.
- Cards: 10 per sheet (2×5 grid) inside `.print-area` (existing global print CSS prints ONLY
  the cards) with `.print-sheet` page-breaks between sheets and `print-avoid-break` per card.
  Card = primary-color header strip (school name from `useStore().schools`, "Nyansapo Learner
  ID") + `.kente-strip` accent + initials avatar + full name + classroom + guardian name/phone +
  pure-CSS barcode (deterministic bar widths derived from the index, guard bars, no library) +
  bold monospace `nyansapoIndex` (the scanner's barcode payload).

## Verification (all passed, curl + real UI via agent-browser)
- Login `super@nyansapo.gh` → school Golden Excellence Academy (123 learners, 64 owing).
- Owing learner (Blessing Baffour, NY-2014-00076, owes 246 000 cents): scan → `denied:true,
  amountOwedCents:246000`; `approve:true` → `granted:true, approved:true`; re-scan →
  `duplicate:true` ("already checked in today").
- Clean learner: lowercase index `ny-2018-00031` → granted (case-insensitive lookup proven);
  CHECK_OUT → `checkedOut:true`; CHECK_OUT a never-checked-in learner → "Learner has not
  checked in"; unknown code → `found:false "Unknown learner code"`.
- cancel → `cancelled:true`, record gone (GET shows no PRESENT row), re-scan → granted again;
  empty scanner-created session auto-removed.
- Register↔scanner integration: teacher-saved register (12 PRESENT) → scanner summary
  "Checked in 12"; scanning a register-marked learner → duplicate (cross-flow rejection).
- UI: all banners/avatars/log/cancel verified in-browser; ID cards tab renders 12 cards / 2
  sheets for Basic 4 — Gold with 12 barcodes, kente strips, print-area present.
- Role gates: **CASHIER → 403** on scanner POST ("missing permission") and GET ("Forbidden");
  TEACHER (has attendance.take) → 403 "Scanner is restricted to administrators (level ≤ 2)".
- `npx tsc --noEmit` → **exit 0, no errors**. `bun run lint` → scanner route + attendance.tsx
  clean; students.tsx only the two pre-existing warnings (unused disable directive line 64,
  PromotionWizard ternary line 705); repo lint failures are the pre-existing `upload/` legacy
  PHP-app JS parse errors, untouched.

## Notes for later agents
- Scanner checkout is log-only by design (see CHECK_OUT storage decision above) — if a future
  schema change adds a session `type` or record `checkOutAt`, migrate the log events then.
- The in-memory scan log resets on dev-server restart (hot reload too); attendance records in
  the register persist, only scan timestamps/denials/checkout history are ephemeral.
- Test data cleaned: all scanner check-ins cancelled; Basic 4 — Gold left with a normal
  teacher-saved register for today (realistic demo state).

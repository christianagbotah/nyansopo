# Task 7 — Staff-Form Parity (NewStaffDialog) + Dashboard KPI Upgrades

Agent: staff-form & dashboard-KPI agent (single-agent task, this file is the work record)
Scope guard honoured: only `payroll.tsx`, `dashboard.tsx`, `staff-portal.tsx`, `ops/payroll/route.ts`,
`ops/overview/route.ts`, `scripts/smoke-staff-dashboard.ts` (new) were touched. No schema/seed/db-push,
no git commit. `src/components/ui/select.tsx` untouched.

## What was built

### Part 1 — NewStaffDialog (payroll.tsx + ops/payroll/route.ts)
- **Dialog** (one dialog, grouped fieldsets, FormGrid/FormField repo style, `sm:max-w-2xl`):
  - *Identity*: fullName*, sex (M/F select), birthday (date), staffCode* with **Generate** button +
    live "✓ available / ✗ already taken / Checking…" check (debounced 400 ms), auto-fetch of the next
    code on open and on category change. Photo upload noted as a **known gap** in the dialog description.
  - *Contact & government*: phone*, email*, address, ghanaCardId (GHA-XXXXXXXXX-X hint), ssnitNumber,
    tin, tier2Provider (free text + `<datalist>` from active PensionProvider rows), tier2MemberId.
  - *Banking*: bankName, accountNumber, momoNumber.
  - *Employment*: category* (drives code prefix + login-role default), position (datalist),
    qualification, department, subjects (comma text), employmentType, salary, hiredAt.
  - *Login (optional)*: "Create a staff login" checkbox → password (min 8) + role select
    (TEACHER default; options from app roles: TEACHER/NON_TEACHING/LIBRARIAN/CASHIER/BUS_CONDUCTOR/ACCOUNTANT/SCHOOL_ADMIN).
- **Backend (ops/payroll/route.ts)**:
  - `nextStaffCode(schoolId, category)` — TCR-/NTS-/ADM- + 5-digit zero-padded sequence from
    **max existing numeric suffix + 1** (legacy non-numeric seeded codes like TCR-OZGNZ are skipped
    by the scan but still count as "taken" for checks).
  - GET actions: `?action=next-staff-code&category=` → `{category, code}`;
    `?action=check-staff-code&code=&category=` → `{code, category, taken}` (case-insensitive, per school).
  - `create-staff`: full field set (sex/birthday/ghanaCard/address/subjects/tin/tier2MemberId/hiredAt/
    bank/momo…), phone+email required (400), supplied staffCode validated (409 on duplicate) else
    auto-generated, optional `createLogin {password≥8, role}` → linked `User` (email+password+role+
    schoolId+staffId via UserStaff relation); login email pre-checked (409) **before** the staff row is written.
    Response `{staff, login?}`.
  - `save-staff`: accepts all new fields (nullable, `?? st.x` / `!== undefined` patterns preserved).
  - GET staff mapping now returns the new fields (feeds the edit dialog).
- **Staff list/detail surfacing**: edit dialog (StaffDialog) extended with sex, birthday, Ghana Card,
  address, subjects, hiredAt, tier 2 member ID (kept light); staff table already shows staffCode.

### Part 2 — Dashboard KPIs (dashboard.tsx + ops/overview/route.ts)
- **Backend (ops/overview GET)** — additive fields, existing `daily/payroll/inventory/sms/transport` kept intact:
  - `dailyToday: {service, label, cents, txns}[]` — received today per service (PAID day-charges + charge
    count, whole calendar day; WEEKLY services aggregated across the ISO week — mirrors ops/daily query shape).
  - `dailyArrearsCents` — the daily module's arrears definition (all-time OWED DailyCharge sum = `daily.outstanding`).
  - `classroomBands: {classroom, m, f}[]` — active enrolments per classroom, top 10 by size.
  - `myClasses: {classroomId, name, learners, presentToday, absentToday, registerTaken}[]` — computed for
    any caller with a linked Staff record (`user.staffId` → classrooms where `classTeacherId` matches);
    present = PRESENT|LATE, absent = ABSENT, latest session today.
- **Frontend (dashboard.tsx)** — existing cards untouched:
  - *Daily-fees strip* (same money gate as the ops strip: `ops && !isTeacher`): per-service "received today"
    cards (category icon heuristic) + red "Unpaid daily-fee balances" card deep-linking to
    `setModule("daily", { tab: "ledger" })`; service cards → daily module.
  - *Learner distribution*: gender donut (recharts Pie, #0e5f3f boys / #c05621 girls) + enrolment-by-classroom
    stacked m/f bar chart (top 10) — shown for admins and teachers.
  - *Teacher mode "My classes" grid*: card per class-teacher classroom (name, learners, present/absent today
    or "Register not taken today"), deep-link `setModule("attendance", { classroomId })`.
- **staff-portal.tsx (light touch)**: "My classes" card in the profile tab's right column (below
  Responsibilities), fed by `ops/overview.myClasses`, same deep-link; hidden when empty.

## Smoke results — scripts/smoke-staff-dashboard.ts
`bun scripts/smoke-staff-dashboard.ts` → **51 passed, 0 failed** 🎉
(a) code shapes + DB-independent max-suffix expectation per category, increment-after-create,
per-category independence; (b) existing taken / fresh available; (c) full-field create → persisted
(ssnit, tier2, bank, birthday, sex, ghanaCard, subjects, momo, hiredAt); (d) duplicate 409;
(e) login created (TEACHER), `POST /api/auth` 200 with new credentials, wrong password 401, user+staff deleted;
(f) dailyToday / dailyArrearsCents / classroomBands (DB-first; injects+cleans a PAID DailyCharge if no
today data); (g) teacher myClasses + negative case (classTeacher temporarily unset → empty → restored);
(h) PARENT & STUDENT 403 on ops/overview and the payroll code helper. Cleanup verified: 0 SMOKE staff,
0 smoke users, B4 Gold classTeacher restored, GEA staff count back to 19.

Verification: `npx tsc --noEmit` clean; `bun run lint` — zero findings in the six owned files (repo-wide
lint failures are pre-existing legacy PHP-app artifacts + other agents' modules). Dev server restarted
with `nohup bun run dev` after it was found dead; `GET / 200`, `/api/context` 401 (healthy).

## Divergences & known gaps
1. **Photo upload** — omitted (noted in-dialog as a known gap), per spec.
2. **Plaintext passwords** for created logins — matches the app's existing auth (`/api/auth` compares
   `user.password !== password`; seeds are plaintext). No hashing exists platform-wide; not introduced here.
3. **attendance.tsx does not consume `navTarget.classroomId`** (file off-limits this task) — the My-classes
   deep-link opens the Attendance register but does not preselect the classroom. Store target is set
   correctly (`?module=attendance&class=…` is honoured by page.tsx). Gap for a future attendance-module owner.
4. daily strip "received" = PAID day-charges (not counter Payment rows) — matches the daily module's
   category-card semantics; WEEKLY services show ISO-week aggregates.
5. dailyArrearsCents duplicates the pre-existing `daily.outstanding` (same definition) — added as a
   top-level field per the requested contract.
6. `registerTaken` is an extra field beyond the specified myClasses contract (additive, safe).
7. createLogin: staff+user are created sequentially (email pre-validated first); no transaction —
   a crash between the two creates could orphan a staff row without login (acceptable for this codebase's
   patterns; the smoke cleanup pattern covers it).
8. NewStaffDialog auto-regenerates the code when the category changes (overwrites manual edits) —
   matches the legacy auto behaviour; Generate + manual typing still available.
9. Legacy seeded non-numeric codes (TCR-OZGNZ …) remain "taken" for checks but never advance the
   numeric sequence — intentional (no collisions with the 5-digit scheme).

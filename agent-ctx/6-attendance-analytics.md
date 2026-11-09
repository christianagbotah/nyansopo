# 6 — Attendance Analytics & Register Enhancements

## What was built

### 1. Register upgrades (`src/components/modules/attendance.tsx`, Register tab)
- Extended status set (CI3 parity): PRESENT, LATE, ABSENT, SICK (legacy "Sick"), SICK_HOME ("Sick @ home"), SICK_CLINIC ("Sick @ clinic"), BUSY, EXCUSED — same compact dot UI per learner, 8 status columns.
- EXCUSED requires a reason: inline compact input appears under the learner's name when EXCUSED is selected; save is blocked client-side (toast listing offenders) AND server-side (400).
- "All present" / "All absent" mark-all buttons next to Save.
- Register save REPOINTED: `apiPostQueueable` now posts to `/api/school/{id}/ops/attendance` with `{ action: "save-register", classroomId, date, records: [{studentId, status, excusedReason?}], offline?, clientId? }`. The offline queue path (`/api/sync`, kind "attendance") is unchanged — syncPayload still carries `{schoolId, classroomId, date, records, clientId}`.
- Register GET now reads `?action=register` from the new route (same payload shape as the old `[resource]` route + `excusedReason` per learner). Stat cards extended to Present/Late/Absent/Sick/Excused/Rate.

### 2. New route `src/app/api/school/[id]/ops/attendance/route.ts`
- `GET ?action=register&classId=&date=` — legacy-shaped register payload (+excusedReason). Auth: `opsAuth GET ["attendance.view"]`.
- `GET ?action=analytics&classroomId=&from=&to=` — summary (sessions, present/absent counts+pct, late, sick-all-kinds, excused, overall rate), per-learner rows (counts per status + rate badge basis), daily trend (rate per day). Rate = (present+late)/marked.
- `GET ?action=monthly&month=YYYY-MM&classroomId=` — roster + `days: { dayNum: { status, reason? } }` map.
- `POST action=save-register` — replicates legacy `[resource]` attendance save semantics: clientId idempotency via `SYNC:` marker on `takenByName`, find-or-create session for (classroom, date), replace records, auto-absence SMS (deduped per learner/day, skipped when offline), `attendance.take` permission + school-operate gate. Adds: status whitelist, EXCUSED-reason 400, classroom-belongs-to-school check, per-learner dedupe.

### 3. Analytics tab (attendance.tsx)
Filters (All classes + each, from/to dates defaulting to last 30 days), 7 summary cards, daily-trend AreaChart (recharts), per-learner table (Name, Class, P, A, L, SH, SC, E, Rate badge ≥90 green / ≥75 amber / else red), sortable by rate, CSV export (house pattern).

### 4. Monthly tab (attendance.tsx)
Month picker (`type="month"`) + classroom filter (auto-selects first class), day-columns 1..N grid with status letters (P/L/A/S/SH/SC/B/E) + tooltip (full label + EXCUSED reason), sticky learner column, weekend shading, legend, `print-area` grid card + Print button (repo print pattern).

### 5. Seed `prisma/seed-attendance-extra.ts`
Idempotent backfill for GEA's two largest classes (currently Basic 6 — Gold, JHS 1 (B7)): last ~3 weeks of school days, per-day guard skips any (classroom, date) that already has a session, realistic mix (≈86% P + LATE/ABSENT/SICK_HOME/SICK_CLINIC/SICK/BUSY/EXCUSED-with-reason), first backfilled day per class forces one of each extended status for demo value. Deterministic PRNG. Run: `DATABASE_URL=postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public bun prisma/seed-attendance-extra.ts`. Applied: +10 sessions / +130 records (days 2026-10-13..16 + 2026-11-02 for the 2 classes); re-run confirmed 0 created.

## Smoke results — `scripts/smoke-attendance-analytics.ts`
**29 passed, 0 failed.** Covers: (a) mixed-status save as TEACHER + DB row verification (incl. EXCUSED reason, non-excused reason null, register GET round-trip); (b) EXCUSED-without-reason → 400, unknown status → 400, rejected save leaves register untouched; (c) same clientId twice → `duplicate: true`, exactly 1 session, 3 records, SYNC marker, markedOffline flags; (d) analytics per-student counts + summary + rate formula verified against direct DB aggregation over the seeded range (2026-10-13→2026-11-02, B6 Gold); (e) monthly day-status map fully cross-checked against DB + EXCUSED reason surfaced; (f) parent@nyansapo.gh (PARENT, no perms) → 403, unknown action → 404; (g) mark-all buttons are UI-only (tsc/lint). Cleanup: smoke sessions + absence-alert SMS deleted, SMS credit refunded.

## Files changed/created
- `src/components/modules/attendance.tsx` (enhanced — Register + new Analytics + Monthly tabs; Scanner untouched)
- `src/app/api/school/[id]/ops/attendance/route.ts` (NEW)
- `prisma/seed-attendance-extra.ts` (NEW — seed.ts untouched)
- `scripts/smoke-attendance-analytics.ts` (NEW)

## Register-save migration notes
- The OLD generic endpoint `src/app/api/school/[id]/[resource]/route.ts` (action=save, ~line 420) still exists and works, but the Register tab no longer calls it — it reads AND writes through `/ops/attendance` now. Any other caller of the old endpoint (none found in src/) is unaffected.
- `/api/sync` (offline queue replay, kind "attendance") was NOT touched (not in my file ownership) — it still replays offline registers itself and does not persist `excusedReason` on replay. Known gap: an offline-queued EXCUSED mark syncs with the status but loses the reason text. Fix would be a 2-line change in `src/app/api/sync/route.ts` (add `excusedReason: r.excusedReason` to the record create) — left for the sync owner.

## Verification
- `npx tsc --noEmit` → 0 errors (project-wide).
- `bun run lint` → no issues in owned files (remaining errors are pre-existing legacy CI3 JS under upload/ & scripts/ci3app).
- Dev server :3000 healthy (`GET /` 200; `/api/context` 401 without auth = healthy).

## Known gaps / notes
- Offline-queue replay drops `excusedReason` (see above).
- Analytics "Sick (all)" card groups SICK + SICK_HOME + SICK_CLINIC; the per-learner table shows SH/SC columns only (legacy sick-bay + busy are counted in `marked`/rate but not table columns) — matches the requested spec columns.
- Monthly grid prints A4 portrait (global @page rule); very wide months print small but legible.
- Dashboard trend widget untouched (dashboard.tsx owned by another agent).
- Attendance rate convention: (PRESENT + LATE) / marked — consistent with the existing register Rate card and dashboard trend.

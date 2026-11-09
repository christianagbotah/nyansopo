# Task 3-a — Boarding Management module (work record)

## Scope delivered
Implemented the full Boarding Management module (iEduPro `boarding_system` parity) in the
Nyansapo Next.js 15 + TypeScript + Prisma/PostgreSQL app at `/home/z/my-project`.

## Files changed (only these two)
1. `src/app/api/school/[id]/ops/boarding/route.ts` — full API (replaced stub)
2. `src/components/modules/boarding.tsx` — full module UI (replaced stub)

## API design
- **GET** `/api/school/[id]/ops/boarding` — perms `["boarding.view"]`. One payload:
  `houses` (with nested `dormitories`, master/prefect names, bedCount, occupied),
  `dorms` (flat, bedCount/availableBeds), `beds` (flat, dorm+house names, occupant),
  `stats` (houses/dorms/beds/occupied/available/maintenance/occupancyRate/boarders/unassigned),
  `unassignedBoarders` (BOARDING residence, no bed), `occupants` (bed + learner + assignedAt),
  `activeLearners` (up to 500, with residenceType + bedCode — feeds prefect selects & DAY→BOARDING conversion),
  `teachingStaff` (category TEACHING, active).
- **POST** — perms `["boarding.manage"]` for every action:
  - `house.create` / `house.update` / `house.delete` (delete → 409 when dormitories remain; houseMaster must be TEACHING staff of the school; prefect must be actively-enrolled learner; statuses canonical UPPERCASE)
  - `dorm.create` / `dorm.update` / `dorm.delete` (delete → 409 when beds remain; type normalised Male/Female/null)
  - `bed.create` (unique code per school, 409 on duplicate), `bed.bulk` (PREFIX-001…PREFIX-N, cap 100, skips existing codes, returns created/skipped), `bed.update` (blocked 409 if occupied & status/dorm change attempted), `bed.delete` (blocked 409 when ASSIGNED)
  - `bed.assign` (transaction: bed → ASSIGNED + studentId + assignedAt; ACTIVE enrollments → residenceType BOARDING; guards: occupied bed, learner already in another bed, non-AVAILABLE bed; P2002 race → 409)
  - `bed.release` (transaction: bed → AVAILABLE, clear studentId/assignedAt; learner's ACTIVE enrollment → DAY)
  - `student.setResidence` ({studentId, residenceType: DAY|BOARDING} on ACTIVE enrollments)
- Error style: `{ error }` with 400 (validation) / 404 (not found) / 409 (blocked-conflict).

## UI design (boarding.tsx)
- Tabs (shadcn `Tabs`): `houses | dorms | beds | assign | reports`; honours `navTarget.tab`
  on mount (sidebar deep-links houses/dorms/beds/assign/reports all verified working).
- **Houses**: table (name+description, master, prefect, capacity, year, termly fee `fmtCedi`,
  dorm count, occupancy progress, status pill) + create/edit dialog (master from teaching staff,
  prefect from learners, fee in GH₵ → cents, status select) + delete (server 409 surfaces as toast).
- **Dormitories**: table (name+prefect, house, Male/Female badge, floor, capacity, beds, available,
  status) + dialog.
- **Beds**: table (code mono, dorm, house, SINGLE/BUNK, status, occupant) in `max-h-[560px]`
  scroll; single-add dialog (code/dorm/type; edit also status, locked while occupied with amber notice);
  bulk dialog (dorm + prefix + count 1–100 + type; >50 requires an explicit confirm checkbox).
- **Assignment**: two panes — unassigned boarders each with a bed `Select` grouped by house → dorm
  (`SelectGroup`/`SelectLabel`) + Assign; occupants list with Release (`max-h-96` scroll);
  "Convert a day learner to boarder" dialog (DAY learner + bed → `bed.assign`).
- **Reports**: `print-area` wrapper with print header (school name, date, totals), summary
  `StatCard`s (houses/dorms/beds occupied/occupancy %), occupancy-by-house table with indented
  per-dorm rows and rate badges (danger >80%, amber >50%, emerald else), boarder directory
  (house → dorm → bed, `max-h-96` scroll), Print button (`window.print()`; controls `no-print`).
- Conventions followed: `apiGet`/`apiPost`, `ModuleHeader/LoadingBlock/EmptyState/StatCard/StatusBadge`,
  `fmtCedi/fmtDate`, `toast` from `@/hooks/use-toast`, `nice-scroll`, responsive grids, aria-labels
  on icon buttons, no blue/indigo colours.

## Notes for later agents
- The dev server must be **restarted** after any `prisma generate` (global PrismaClient singleton
  keeps the old client). Used `scripts/ensure-server.sh` for the sanctioned restart.
- `db.bed` unique code lookup uses compound `schoolId_code`.
- Verification baseline (GEA school): 2 houses (Agyei/Sutherland), 4 dorms, 36 beds, 12 boarders,
  occupancy 33.3% — data restored to this state after testing.

## Verification (all passed)
- curl login → `super@nyansapo.gh`; GET: houses 2, beds 36 (12 occupied / 24 available / 33.3%).
- assign + release round-trip via curl **and** via the real UI (convert dialog → assign → Release button);
  residenceType flips BOARDING/DAY correctly in both directions.
- Blocked deletes return friendly 409s; duplicate bed code 409; bulk cap 400; unknown action 400; no
  auth header 401.
- `npx tsc --noEmit` → **exit 0**; `bun run lint` → boarding files clean (repo lint still flags
  unrelated WIP files from other tasks).
- Headless browser (agent-browser): all five tabs render with correct data, navTarget deep-links work,
  zero page errors.

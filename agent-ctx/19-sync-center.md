# Wave 39 — Sync Center (the offline empire's control room)

Closes the last 🟡 in `scripts/iedupro/PARITY.md` (§10 Cloud sync): the legacy
CI3 family `sync_dashboard / sync_devices / sync_conflicts /
sync_conflict_merge / sync_audit_trail / sync_metrics /
sync_table_management / location_registry`, adapted honestly to Nyansapo's
actual sync truth — the PWA offline queue (payments / attendance / marks /
enrolment). The queue already existed; this wave gave it the supervision
console the legacy system had.

## What shipped

### 1. Data model (`prisma/schema.prisma`)
- `SyncDevice` — a browser/POS tablet/scanner that holds queued ops. Browsers
  **self-register on their first flush** (CI3 `register_device` parity — the
  client sends a device identity, the server mints `DEV-0001`-style codes and
  returns it; the browser remembers it in localStorage `nyansapo.device`).
  Lifetime counters (opsSynced/opsFailed), last sync/seen, last IP + user.
- `SyncConflict` — a replay-vs-server collision, both sides frozen as JSON:
  local = the queued op's payload, remote = the server state at flush time.
- `SyncEvent` — the journal: one FLUSH row per school involved in a flush
  (counts, conflicts, durationMs, device, user) + REGISTER/REVOKE/RESOLVE rows.

### 2. Flush rewire (`/api/sync` — business logic untouched)
- Device identity: auto-register on first flush; **REVOKED devices are
  refused outright (403)** — the kill switch from the Sync Center.
- Per-kind **sync policy** (SchoolSetting `sync.policy` — CI3
  sync_table_management parity): a disabled kind is refused per-op and
  journaled FAILED.
- **Conflict detection** (both sides frozen, op consumed so re-flush can't
  re-file):
  - marks — server scores for the assessment differ from the queued ones →
    conflict instead of the old silent clobber (`deleteMany` + recreate).
  - attendance — a session for the same class+date already exists (taken
    online after queueing) → conflict instead of minting a duplicate session
    (the old behaviour created a SECOND register for the same day).
- Journaling: FLUSH SyncEvents per school + device counter stamping +
  `durationMs`; response gains `deviceCode` + `conflicts` count.
- Client `flushQueue` sends the device identity, persists the minted code,
  and surfaces conflicts in toasts (app-shell manual sync, Ctrl+K palette,
  PWA auto-flush on reconnect).

### 3. Ops API (`/ops/sync`)
- GET `dashboard` — KPI cards (today/7d synced-failed-conflicts, health score
  = 100 − 2·failures − 10·pending conflicts − 15·revoked), 14-day timeline,
  devices at a glance, policy, recent conflicts/events.
- GET `conflicts` — pending + resolved, with learner names resolved for a
  human side-by-side diff (`diffRows`).
- GET `events` — the journal with kind filter.
- POST (sync.manage): `device-save` (register/rename/notes), `device-toggle`
  (ACTIVE⇄REVOKED), `conflict-resolve` (LOCAL_WINS genuinely replays the
  frozen payload — marks recreate, attendance overwrite-or-create; REMOTE_WINS
  discards; IGNORED keeps on file), `policy-save`.

### 4. Module UI (`sync-center.tsx`, id `sync`, Support section)
Dashboard / Devices / Conflicts / Journal / Policy tabs; pending-conflict
cards with learner-level diff table and the resolve dialog (radio choices +
note); device register/rename/revoke; policy switches; RBAC `sync.view`
(view) / `sync.manage` (operate — SCHOOL_ADMIN/super family via role
defaults, ACCOUNTANT view-only). Wired: sidebar nav + children, PAGE_INDEX
entries (5 pages — live in the Start "Pages 140" catalogue), ModuleId union.

### 5. Seed (`prisma/seed-sync.ts`, added to `reseed-chain.sh`)
4 devices (canteen POS tablet, gate scanner, front desk, bursar's Chrome),
~360 ops across 14 days of flush journal, 2 pending conflicts built from REAL
assessment/classroom/learner rows, 3 resolved with history.

## E2E verified (browser + API, head account)
- Dashboard 78→88 health after resolving; timeline chart; devices table.
- Resolved the marks conflict **LOCAL_WINS with a note** — Kojo's French mark
  genuinely changed 58→60 server-side; pending 2→1; RESOLVE journal entry +
  resolution history row with the note.
- Policy round-trip: marks disabled → direct flush refused with "sync disabled
  for marks ops"; restored all-enabled.
- Kill switch: DEV-0005 revoked → flush 403 "Device DEV-0005 is revoked";
  re-activated → OK. Test rows cleaned up.
- Live conflict detection: two attendance flushes for the same class+date →
  second files a conflict with both sides frozen ([PRESENT, LATE, PRESENT] vs
  [PRESENT, ABSENT, PRESENT]).
- Self-registration: first flush minted DEV-0005 + REGISTER journal entry.
- Mobile 390px + desktop 1920px; zero browser console errors; dev.log clean.

## Courtesy: cleared the pre-existing `tsc --noEmit` backlog (15 errors)
marksheet conduct typing; drivelist + receivables `sortClassrooms` (gradeLevel
now selected); certificates school phone (select + local type); finance
alerts null badge; front-office DateField null-coalescing + FormField label.
**Repo now: `tsc --noEmit` 0 errors, `bun run lint` 0 errors.**

## Sandbox recovery note
The dev server gets reaped between long tool calls — `bash
scripts/ensure-server.sh` (idempotent) brings it back; PG + seed chain per
`scripts/pg/README.md` when the cluster is wiped.

# Wave 40 — Offline Data Pack (read-side sync, own architecture)

User direction at the start of this wave: for the sync module, the CI3 legacy
way is not required — "use your own architecture and logics". Wave 39 had
already shipped the push side (offline queue → supervised flush) in full CI3
parity. What the legacy family ALSO had — full-table row sync WAMP↔cloud,
`location_registry`, row-level `sync_table_management` dumps — doesn't fit a
PWA whose offline truth is the localStorage action queue. This wave closes the
gap that actually existed: **the read side**. Offline, every module did a bare
`apiGet` on mount and went blank — a blackout killed the screens even though
the writes queued fine. (The `public/sw.js` response cache exists but only
registers in production builds — a dev-preview Turbopack hazard — so it is
not the offline read layer in this environment.)

## The architecture (deliberately NOT CI3's)

**The Offline Data Pack** — a per-user localStorage snapshot of the last
server response of each cacheable screen:

- `src/lib/offline-pack.ts` — pack storage: `packWrite` / `packRead` /
  `packInfo` / `packPurge`. Namespaced to the signed-in user (a shared
  workstation never serves one user's data to another), keyed by full request
  path (school id is in the path → per-school isolation). LRU-capped at 60
  sections; quota-escape prunes oldest thirds until it fits; best-effort —
  pack failures never break the caller.
- **Sibling-date fallback**: for date-parameterised screens (attendance
  register, POS daily) an offline day that was never fetched serves the newest
  cached section of the same screen (same pathname + same query minus
  `date=`) — offline the register for tomorrow renders from today's roster.
- `apiGetCacheable` (`src/lib/api.ts`) — transparent swap for `apiGet`:
  online → fetch + `packWrite`; offline → `packRead` (exact, then sibling),
  throwing only when nothing was ever cached.
- **Reconnect = both directions**: `pwa-bootstrap` flushes the queue (writes
  up) then `warmPack()`s every section (data down) silently and journals a
  **PULL** — the read twin of the flush journal.
- **Writes never touch the pack** — the four queueable kinds queue exactly as
  before.

## What shipped

1. **lib**: `offline-pack.ts` (new), `api.ts` (+`apiGetCacheable`,
   +`warmPack` with PULL journaling + device identity), `store.ts`
   (+`packAt` freshness).
2. **`/api/sync` POST `action=pull`** — journals the pull (SyncEvent kind
   PULL, sections + durationMs + device code + user), device lookup mints a
   DEV- row on first pull (a read-mostly tablet registers here rather than at
   flush), stamps device lastSync/lastSeen/IP/user. Best-effort: journaling
   failure never fails the warm.
3. **Module swaps** (six call sites): attendance register + gate scanner,
   POS daily services, students roster + settings, gradebook grid →
   `apiGetCacheable`. Zero call-site shape changes.
4. **Sync Center "Data pack" tab** (6th tab + sidebar child + Start-catalogue
   page "Sync Data Pack" — pages 140 → 141): sections/size/freshest stat
   cards, per-section table (label, path, size, ago), **Warm now** (manual
   re-pull + journal), **Purge pack** (confirm dialog — local browser state
   only). Journal filter gains "Data pulls" + teal PULL tone.
5. **Offline banner**: app-shell offline badges (topbar + mobile sheet) show
   `data <ago>` pack freshness; sign-out purges the pack (shared-workstation
   hygiene).

## E2E verified (agent-browser, head account, GEA)

- Pack fill: visiting Students / Attendance (class register) / POS daily /
  Gradebook packed 6 sections (~280 KB).
- **Truly offline** (`set offline on` — Playwright context offline):
  Students directory renders fully from the pack ("126 learners", Offline ·
  data 2 min ago badge, zero error toasts).
- **Date fallback**: offline on the NEXT day (19 Nov, never fetched), Basic 4
  Gold register renders the full roster from the 18th's cached section —
  learner cards, PRESENT/ABSENT controls, "Offline — marks will queue".
- Offline write loop: All present + Save → "1 queued"; reconnect → queue
  drained, FLUSH journal row "12 marked", device DEV-0005, attendance session
  landed server-side.
- Auto-warm on reconnect → PULL journal row (6 sections, 1004 ms) with device
  + user; second PULL row from the manual Warm now; "Data pulls" filter
  shows exactly the two PULL rows.
- Data pack tab: stat cards, section table, warm round-trip ("freshest
  section — just now"), purge dialog → empty state → re-pack on next online
  visit. Start menu deep link opens the pack tab directly.
- tsc --noEmit 0 · eslint 0 · dev.log clean (zero errors, all endpoints 200)
  · zero browser console errors · 390px + 1920px screenshots
  (`w40-*.png`).

## Notes for later agents

- Adding a screen to the pack = swap its mount-time `apiGet` →
  `apiGetCacheable` in that module; nothing else changes. The pack's
  LABELS regex in `offline-pack.ts` drives the friendly name in the Sync
  Center table.
- The pull journal is per school (the client's current school); a flush may
  span schools, a pull does not — acceptable: the pack warms what this
  browser's current school screens hold.
- `sw.js` (production-only registration) now complements the pack rather
  than duplicating it: SW covers the app shell, the pack covers API reads in
  dev and prod alike.
- Sandbox recovery: `scripts/ensure-server.sh` after long tool calls;
  PG + seed chain per `scripts/pg/README.md`.

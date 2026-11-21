# Wave 51 — Cluster durability & off-site backups

The session that preceded this wave began with a sandbox reset that took the
embedded PostgreSQL down and (in an earlier occurrence) actually lost data.
Wave 50's end-to-end verification then proved everything was intact — but the
backup story itself was hollow: `restore-db.sh` only ever **reseeds a fresh
demo dataset**, the daily safety-net self-heals infrastructure but never
preserves **live data**, `DbBackup` table was empty, and the sandbox cron
(Job 516260) that drove the safety-net was gone (no `crontab` binary exists in
this sandbox at all).

## What this wave ships

**1. Cold-snapshot backups — `scripts/pg/backup-cluster.sh`**
The bundled relocatable PG 17 binaries are `initdb` / `pg_ctl` / `postgres`
only — no `pg_dump`, no `pg_restore`, no `pg_resetwal`. So the exact-fidelity
option is a filesystem snapshot of a cleanly-stopped cluster:

- `pg_ctl -m fast stop` (clean-shutdown checkpoint) → `tar -czf` of
  `scripts/pg/data` → `pg_ctl start` → port verified; app downtime ≈ 5-10 s.
- WAL **is** included: after a clean stop only ~3 segments remain and gzip
  flattens them to ~8 MB. Excluding WAL proved un-restorable without
  `pg_resetwal` (invalid-checkpoint PANIC — found by the clone test, fixed by
  including WAL). `pg_replslot`/`postmaster.pid`/logs are excluded from the
  tar but `pg_replslot` is re-created on restore (PG refuses to start without
  it — also found by the clone test).
- Output: `backups/latest-cluster.tar.gz` (stable name, **git-tracked** —
  every safety-net push carries a restorable copy of live data to GitHub)
  plus `backups/history/cluster-*.tar.gz` (timestamped local history,
  git-ignored, pruned to 10).
- Size: 15.3 MB for the full cluster (10 schools / 432 students / 5,075
  daily charges / invoices / discounts / sync book / ledger ...).
- Contract: prints `BK: ok <file> <bytes>` / `BK: fail <reason>`; never
  exits non-zero, always best-effort-restarts the server.

**2. One-command restore — `scripts/pg/restore-cluster.sh`**
`restore-cluster.sh [snapshot]` stops the live server, moves the current data
dir aside to `scripts/pg/data.pre-restore-<ts>` (forensics, never
auto-deleted), unpacks the snapshot, re-creates `pg_replslot`, boots via
`.zscripts/pg-start.sh` (which also bootstraps aux dirs), then **verifies the
data actually came back** (`School.count() > 0` via Prisma). Prints
`RESTORE: ok schools=<n>` / `RESTORE: fail <reason>`.

**3. Safety-net integration — `.zscripts/safety-net.sh`**
New step 3b runs the snapshot only when the DB actually has data (never right
after a fresh reseed of nothing), and the REPORT line gained `BK=`:
`REPORT ... PG=up DEPS=ok SEEDED=8 BK=ok(15293016) COMMIT=... PUSH=ok ...`.
Because `backups/latest-cluster.tar.gz` is tracked, the existing
`git add -A + commit + push` step now publishes the off-site copy too.

**4. Scheduling restored — agent-level cron job 541145**
No `crontab` binary exists in the sandbox, so the scheduler is the agent-level
cron: **daily 03:00 Atlantic/Reykjavik**, job "Nyansapo daily safety-net"
(id 541145). Its turn runs `bash .zscripts/safety-net.sh`, reads the last
REPORT line, and only escalates to interactive fixing when the line is not
fully green (PG down / server down / push failed / BK failed / LOCAL≠ORIGIN).

## Verification performed

- Backup round-trip **proven with a live clone test**: unpacked
  `latest-cluster.tar.gz` into a scratch dir, started a second cluster on
  `:5434`, queried it over TCP — **schools=10, students=432,
  discountApplications=11, dailyCharges=5075 — exact match with the live
  DB** — then stopped and deleted the clone.
- The two restore-blocking failures (invalid checkpoint without WAL; missing
  `pg_replslot`) were reproduced, root-caused and fixed in the scripts above.
- App served HTTP 200 immediately after backup stop/start cycles.

## Maintenance notes

- Each day's safety-net commit adds ~15 MB of git history (one new dump
  blob). Acceptable for a private GitHub repo; if it ever matters, squash
  `backups/` history periodically (`git checkout <old> -- backups/…` +
  recommit) or drop the file for a release.
- The snapshot is version-locked to the bundled PG 17 binaries in
  `scripts/pg/` — both are in-repo, so a GitHub-side restore works anywhere
  the repo clones: `scripts/pg/restore-cluster.sh` + `.zscripts/pg-start.sh`.
- `scripts/restore-db.sh` (reseed-from-scratch) remains as the last-resort
  path when no snapshot exists.

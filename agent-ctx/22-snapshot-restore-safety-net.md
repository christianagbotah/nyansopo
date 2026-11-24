# Wave 52 — Snapshot-restore safety-net (first real incident passed)

The night after wave 51 shipped, the sandbox wiped the embedded PostgreSQL
cluster again — the first live test of the durability wave. It exposed two
real defects, both fixed in this wave.

## Incident timeline (2026-11-22 ~03:00 UTC, cron job 541145)

1. Cluster found **empty** (school count 0) after the wipe.
2. Safety-net's step 3b reported `BK=fail subprocess` — because
   `scripts/pg/backup-cluster.sh` **did not exist on disk**.
3. Safety-net blind-reseeded the demo dataset and pushed it nowhere useful
   (`PUSH=failed` — see below); REPORT line: fully degraded.

## Root causes found & fixed

**RC1 — the wave-51 scripts were never actually committed.**
`.gitignore`'s broad `/scripts/pg/*` rule silently swallowed the two new
scripts: wave 51's commit contains only 4 files (`.gitignore`,
`safety-net.sh`, the wave record, the snapshot blob) — `git add -A` skipped
the ignored scripts without any warning. The wipe then removed the untracked
files from disk. *Fix:* both scripts recreated from the wave record/commit
history, and the ignore list now whitelists them explicitly
(`!/scripts/pg/backup-cluster.sh`, `!/scripts/pg/restore-cluster.sh`);
verified with `git check-ignore` (exit 1 = tracked). Lesson recorded: **after
adding a script under an ignored path, check `git check-ignore` before
trusting `git add -A`.**

**RC2 — the safety-net reseeded demo data instead of restoring the real
snapshot.** Step 3 only knew one recovery move (reseed), even though
yesterday's real snapshot (with 34 collection-audit rows, 51 discount
overrides, 4,054 payments) was sitting git-tracked in the repo. *Fix:* the
recovery order when the cluster is found empty is now:
1. `scripts/pg/restore-cluster.sh` from `backups/latest-cluster.tar.gz`
   (real data) → REPORT shows `SEEDED=restored`
2. demo reseed only as the fallback (no snapshot yet / restore failed /
   first-ever boot)

**Side finding — divergent git history after the wipe.** The sandbox
checkpoint rewind re-created yesterday's "chore: re-sync" commit as `0b99bcf`
(different hash, same message) while origin had `3f42abc`; push failed with
non-fast-forward. Resolved by `git diff origin/main HEAD` (proved the delta
was only a dev-PID file + mode bits) then `git reset --hard origin/main`.

## Recovery actually performed

- `git reset --hard origin/main` → repo aligned, wave-51 tree restored from
  GitHub (this is what made the snapshot blob available again — the off-site
  copy paid off within 12 hours).
- `restore-cluster.sh` → `RESTORE: ok schools=10`; app served HTTP 200
  immediately; verified the data was REAL (34 `DailyFeeAudit` rows, 51
  enrollment overrides, 4,054 payments — all zero/absent in a fresh reseed).
- Recreated + whitelisted + committed both scripts; hardened safety-net
  recovery order; backup re-verified (`BK: ok ... 15307340`); full safety-net
  re-run → **all green**: `PG=up DEPS=ok SEEDED=10 BK=ok(15288605)
  COMMIT=0cb1837 PUSH=ok LOCAL=ORIGIN SERVER=up`.

## State after the wave

- GitHub holds the post-incident snapshot (the restored real data).
- The pre-restore demo cluster copy (`data.pre-restore-20261122-030504`) was
  inspected, confirmed worthless (fresh reseed output), and deleted.
- Next wipe cycle is expected to self-heal with REAL data: wipe → pg-start
  bootstraps empty cluster → safety-net sees `SEEDED=0` → restores the
  git-tracked snapshot → backs the restored cluster up again → pushes.

# Wave-78 post-rollback repair — 2026-11-27/28

## What happened
- The sandbox machine rolled back ~22:57 (Nov 27): local `.git` returned to the
  wave-62 era while the worktree kept the wave-78 content. Origin already had
  waves 76-78 committed and pushed (`3b27c72`, `f36c382`, `ed2f2d3`).
- The 8h safety-net then fired on the stale base: restored the PG cluster from
  `latest-cluster.tar.gz` (a snapshot captured mid-smoke), auto-committed the
  worktree onto the old HEAD (`bac02c0`), and PUSH failed (non-fast-forward)
  → DEGRADED.
- The restored DB therefore carried wave-78 smoke residue: 4 PSK-/FLW-261127-
  payment requests (wallet ×2, fees ×1, shop ×1), receipts RCP-2026-00520..523,
  order SO-00004, items "Wave 78 smoke notebook" / "Exercise book (200 pages)",
  their wallet top-ups, one ₵50 partial payment on INV-2026-0054 and 3 SMS.
  The leftover items also blocked the GEA 10-item catalog seed (exists-guard).

## Repair (all verified)
1. `git reset --hard origin/main` — worktree content was byte-identical to
   origin (only screenshot binaries + exec bits differed). Local == origin at
   `ed2f2d3`. Push failure root-caused to nothing but the stale base.
2. `scripts/cleanup-wave78-leftovers.ts` — surgical, idempotent removal:
   wallet balances decremented + txns deleted (idempotent via txn lookup),
   INV-2026-0054 restored to ₵1,370.04 PARTIAL (idempotency guard: only
   restore while paidCents == Σ payment rows), SO-00004 + stock restored,
   events → requests → receipts → SMS → items deleted, then the seed-ops
   10-item GEA starter catalog re-seeded. Verify: smokeReqs=0 smokeOrders=0
   orphanPayments=0 catalog=10.
3. Browser E2E re-verified on the repaired DB (screenshots
   `w78-repair-*.png`): parent portal Shop tab (10 items + Sandbox badge +
   stock counts), Fees tab (Ama's INV-2026-0054 balance ₵1,089.96) with the
   Pay-online dialog showing all three gateway chips (Hubtel / Paystack /
   Flutterwave, sandbox), bursar School Shop console clean (0 awaiting,
   0 to pack). Zero console errors.

## Notes
- The untracked `db/custom.db` + SQLite `DATABASE_URL` in `.env` are leftovers
  from the rollback checkpoint; all launchers (safety-net, watchdog, dev.sh)
  export the real `postgresql://…:5433/nyansapo` URL, so they are inert.
- Lesson (added to the standing list): after any machine rollback, check
  `git branch -vv` for divergence BEFORE running anything that auto-commits —
  the safety-net will happily commit onto a stale base and go DEGRADED on push.

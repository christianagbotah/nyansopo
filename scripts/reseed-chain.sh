#!/bin/bash
# Re-seed a fresh cluster with the full demo-data chain (dependency order).
# Usage: bash scripts/reseed-chain.sh
set -u
cd "$(dirname "$0")/.."
export DATABASE_URL='postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public'
SCRIPTS=(
  prisma/seed.ts
  prisma/seed-rbac.ts
  prisma/seed-classes.ts
  prisma/seed-ops.ts
  prisma/seed-ops-extra.ts
  prisma/seed-credits.ts
  prisma/seed-curriculum.ts
  prisma/seed-attendance-extra.ts
  prisma/seed-creche.ts
  prisma/seed-daily-ext.ts
  prisma/seed-discount-profiles.ts
  prisma/seed-exams.ts
  prisma/seed-fare-parity.ts
  prisma/seed-parity2.ts
  scripts/seed-parity-extras.ts
  scripts/seed-jhs3-bece.ts
  scripts/seed-pos-sales.ts
)
fail=0
for s in "${SCRIPTS[@]}"; do
  echo "── $s"
  if ! bun "$s" >/tmp/seed-out.log 2>&1; then
    echo "FAILED: $s"
    tail -15 /tmp/seed-out.log
    fail=1
  else
    tail -2 /tmp/seed-out.log | grep -vE '^\s*$' | head -2
  fi
done
[ $fail -eq 0 ] && echo "ALL SEEDS OK" || echo "SOME SEEDS FAILED"
exit $fail

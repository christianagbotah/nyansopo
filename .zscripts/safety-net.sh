#!/bin/bash
# Daily safety-net for the Nyansapo sandbox (companion to cron Job 516260).
#
# Self-heals everything a sandbox reset can wipe, then commits & pushes:
#   1. embedded PostgreSQL up (bootstraps cluster/role/db if wiped)
#   2. bun install (if node_modules missing)
#   3. prisma db push + reseed chain (if DB is empty)
#   4. git add -A + daily safety-net commit + push (idempotent)
#   5. dev server up on :3000
#
# Appends a one-line REPORT to .zscripts/safety-net.log (git-ignored via *.log).
set -u

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT" || exit 1
LOG="$ROOT/.zscripts/safety-net.log"

# log rotation: keep the file bounded
if [ -f "$LOG" ] && [ "$(wc -l < "$LOG" 2>/dev/null || echo 0)" -gt 4000 ]; then
  tail -n 1000 "$LOG" > "$LOG.t" && mv "$LOG.t" "$LOG"
fi

exec >> "$LOG" 2>&1
echo "=== safety-net $(date '+%F %T') ==="

PG=down SEEDED=unknown COMMIT=none PUSH=skipped LOCAL=unknown ORIGIN=unknown SERVER=down

# --- 0. git credentials (self-heal if GH_TOKEN provided in env; no secrets in repo) --
if [ ! -s "$HOME/.git-credentials" ] && [ -n "${GH_TOKEN:-}" ]; then
  printf 'https://christianagbotah:%s@github.com\n' "$GH_TOKEN" > "$HOME/.git-credentials"
  chmod 600 "$HOME/.git-credentials"
fi
git config --global credential.helper store 2>/dev/null || true

# --- 1. PostgreSQL ------------------------------------------------------------------
if bash "$ROOT/.zscripts/pg-start.sh" | grep -q '^UP:'; then
  PG=up
else
  echo "FATAL: postgres did not come up"; tail -8 "$ROOT/scripts/pg/postgres.log" 2>/dev/null
fi
export DATABASE_URL='postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public'

# --- 2. dependencies ----------------------------------------------------------------
if [ ! -x node_modules/.bin/next ]; then
  echo "--- bun install ---"
  bun install && DEPS=ok || DEPS=failed
else
  DEPS=ok
fi

# --- 3. schema + seed (only when DB is empty) ----------------------------------------
SEEDED=$(node -e 'const{PrismaClient}=require("@prisma/client");const p=new PrismaClient();p.school.count().then(c=>{console.log(c);return p.$disconnect()}).catch(()=>{console.log(0)})' 2>/dev/null || echo 0)
if [ "$PG" = "up" ] && [ "$SEEDED" = "0" ]; then
  echo "--- prisma db push + reseed ---"
  if bunx prisma db push --skip-generate >/dev/null 2>&1 && bash scripts/reseed-chain.sh; then
    SEEDED=reseeded
  else
    SEEDED=reseed-failed
  fi
fi

# --- 4. commit + push ----------------------------------------------------------------
git add -A 2>/dev/null || true
if ! git diff --cached --quiet 2>/dev/null; then
  if git commit -m "chore(auto): daily safety-net commit $(date +%F)" >/dev/null 2>&1; then
    COMMIT="$(git rev-parse --short HEAD)"
  else
    COMMIT=failed
  fi
fi
if git push origin main >/dev/null 2>&1; then
  PUSH=ok
else
  PUSH=failed
fi
git fetch origin >/dev/null 2>&1 || true
LOCAL="$(git rev-parse HEAD 2>/dev/null || echo unknown)"
ORIGIN="$(git rev-parse origin/main 2>/dev/null || echo unknown)"

# --- 5. dev server -------------------------------------------------------------------
code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 3 http://localhost:3000/ || true)
if [ "$code" != "200" ]; then
  echo "--- dev start ---"
  pgrep -f "next dev" | xargs -r kill 2>/dev/null; sleep 1
  rm -f dev.log
  setsid nohup bun run dev > dev.log 2>&1 &
  sleep 3
fi
for i in $(seq 1 40); do
  code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 http://localhost:3000/ || true)
  [ "$code" = "200" ] && break
  sleep 4
done
case "$code" in 200) SERVER=up ;; *) SERVER="down($code)"; tail -5 dev.log 2>/dev/null ;; esac

echo "REPORT $(date '+%F %T') PG=$PG DEPS=$DEPS SEEDED=$SEEDED COMMIT=$COMMIT PUSH=$PUSH LOCAL=$LOCAL ORIGIN=$ORIGIN SERVER=$SERVER"
if [ "$PG" = "up" ] && [ "$SERVER" = "up" ] && [ "$LOCAL" = "$ORIGIN" ]; then
  echo "=== safety-net OK ==="
else
  echo "=== safety-net DEGRADED ==="
fi

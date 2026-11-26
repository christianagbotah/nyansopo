#!/bin/bash
# Persistent dev-server watchdog LOOP (wave 60).
# Runs forever (started by safety-net.sh / dev-watchdog.sh, idempotently):
# every 2 minutes it curls the dev server and, when the OOM killer has taken
# it down (the Turbopack dev server grows to ~2.2 GB on this 4 GB sandbox and
# gets killed every ~30-40 min of active compilation), brings it back and
# waits for the first compile. The loop itself is a ~1 MB bash+curl process,
# so the OOM killer (which targets the largest RSS) never touches it.
#
# Log: .zscripts/watchdog.log (bounded by dev-watchdog.sh's rotation).
set -u
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT" || exit 1
LOG="$ROOT/.zscripts/watchdog.log"

# single instance guard
if pgrep -f "watchdog-loop.sh" | grep -v "$$" | grep -q . 2>/dev/null; then
  echo "$(date '+%F %T') loop: another instance already running — exiting" >> "$LOG"
  exit 0
fi

echo "$(date '+%F %T') loop: watchdog started (pid $$)" >> "$LOG"
while true; do
  code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 60 http://localhost:3000/ || true)
  if [ "$code" != "200" ]; then
    echo "$(date '+%F %T') loop: server down (http=$code) — restarting" >> "$LOG"
    pgrep -f "next dev" | xargs -r kill 2>/dev/null
    sleep 1
    rm -f dev.log
    setsid nohup bun run dev > dev.log 2>&1 &
    # wait up to ~4 min for the first compile of /
    for _ in $(seq 1 48); do
      code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 60 http://localhost:3000/ || true)
      [ "$code" = "200" ] && break
      sleep 5
    done
    echo "$(date '+%F %T') loop: restart settled (http=$code)" >> "$LOG"
  fi
  sleep 120
done

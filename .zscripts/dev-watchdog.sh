#!/bin/bash
# Lightweight dev-server watchdog (wave 60).
# The sandbox has 4 GB RAM; the Turbopack dev server slowly grows to ~2.2 GB
# during active compilation and the OOM killer takes it down every ~30-40 min
# of heavy use — the root cause behind intermittent "preview not working"
# reports. The daily safety-net restores it once a night; this watchdog closes
# the gap by checking every few minutes and restarting only when the server
# is actually down. Idempotent, cheap (one curl when healthy).
set -u
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT" || exit 1
LOG="$ROOT/.zscripts/watchdog.log"

# keep the log bounded
if [ -f "$LOG" ] && [ "$(wc -l < "$LOG" 2>/dev/null || echo 0)" -gt 2000 ]; then
  tail -n 500 "$LOG" > "$LOG.t" && mv "$LOG.t" "$LOG"
fi

code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 8 http://localhost:3000/ || true)
if [ "$code" != "200" ]; then
  echo "$(date '+%F %T') watchdog: server down (http=$code) — restarting" >> "$LOG"
  pgrep -f "next dev" | xargs -r kill 2>/dev/null
  sleep 1
  rm -f dev.log
  setsid nohup bun run dev > dev.log 2>&1 &
  sleep 5
  # wait up to ~3 min for the first compile of /
  for _ in $(seq 1 36); do
    code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 8 http://localhost:3000/ || true)
    [ "$code" = "200" ] && break
    sleep 5
  done
  echo "$(date '+%F %T') watchdog: restart settled (http=$code)" >> "$LOG"
fi

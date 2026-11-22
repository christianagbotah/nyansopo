#!/bin/bash
# Cold-snapshot the embedded PostgreSQL cluster for Nyansapo.
#
# Stops the server briefly (fast checkpoint), tars the data directory
# (WAL included — see below), restarts it, and keeps:
#   backups/latest-cluster.tar.gz   — stable name, git-tracked (off-site copy)
#   backups/history/cluster-*.tar.gz — timestamped local history (git-ignored)
#
# Stopping with `-m fast` performs a clean-shutdown checkpoint, so the few
# remaining WAL segments compress to a few MB — they ARE included in the
# snapshot (pg_control references them; excluding WAL would require
# pg_resetwal, which the bundled binary set does not ship).
#
# Contract: prints "BK: ok <file> <bytes>" on success, "BK: fail <reason>" on
# failure (never exits non-zero — callers treat failure as advisory).
set -u

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
PGDIR="$ROOT/scripts/pg"
DATA="$PGDIR/data"
OUT_DIR="$ROOT/backups"
HIST_DIR="$OUT_DIR/history"
LATEST="$OUT_DIR/latest-cluster.tar.gz"
LOG="$PGDIR/postgres.log"
export LD_LIBRARY_PATH="$PGDIR/lib:${LD_LIBRARY_PATH:-}"

url_ok() { timeout 3 bash -c 'cat < /dev/null > /dev/tcp/127.0.0.1/5433' 2>/dev/null; }

if [ ! -f "$DATA/PG_VERSION" ]; then
  echo "BK: fail no-cluster"
  exit 0
fi

mkdir -p "$HIST_DIR"

WAS_RUNNING=no
url_ok && WAS_RUNNING=yes

# --- stop (fast checkpoint) ---------------------------------------------------------
if [ "$WAS_RUNNING" = "yes" ]; then
  "$PGDIR/bin/pg_ctl" -D "$DATA" -m fast stop >/dev/null 2>&1
  for i in $(seq 1 15); do url_ok || break; sleep 1; done
  if url_ok; then echo "BK: fail could-not-stop"; exit 0; fi
fi

# --- snapshot -----------------------------------------------------------------------
TS="$(date +%Y%m%d-%H%M)"
STAMPED="$HIST_DIR/cluster-$TS.tar.gz"
rm -f "$STAMPED"
if ! tar -C "$PGDIR" \
       --exclude='data/pg_replslot' \
       --exclude='data/postmaster.pid' \
       --exclude='data/postgres.log' \
       --exclude='data/log' \
       --exclude='sockets/*' \
       -czf "$STAMPED" data 2>>"$LOG"; then
  echo "BK: fail tar"
  # best-effort restart before giving up
  "$PGDIR/bin/pg_ctl" -D "$DATA" -l "$LOG" -o "-p 5433 -k $PGDIR/sockets" start >/dev/null 2>&1
  exit 0
fi

# --- restart -------------------------------------------------------------------------
if [ "$WAS_RUNNING" = "yes" ]; then
  rm -f "$DATA/postmaster.pid" 2>/dev/null
  "$PGDIR/bin/pg_ctl" -D "$DATA" -l "$LOG" -o "-p 5433 -k $PGDIR/sockets" start >/dev/null 2>&1
  for i in $(seq 1 30); do url_ok && break; sleep 1; done
  if ! url_ok; then echo "BK: fail restart"; exit 0; fi
fi

# --- publish -------------------------------------------------------------------------
cp -f "$STAMPED" "$LATEST.tmp" && mv -f "$LATEST.tmp" "$LATEST"

# --- prune history (keep 10 newest) ---------------------------------------------------
ls -1t "$HIST_DIR"/cluster-*.tar.gz 2>/dev/null | tail -n +11 | xargs -r rm -f

BYTES="$(wc -c < "$LATEST" | tr -d ' ')"
echo "BK: ok $LATEST $BYTES"

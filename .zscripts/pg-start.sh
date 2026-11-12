#!/bin/bash
# Start (or bootstrap) the embedded PostgreSQL 17 for Nyansapo on 127.0.0.1:5433.
#
# Self-healing + relocatable: derives every path from this script's own
# location, bootstraps the whole cluster (initdb + role + database) if the
# sandbox reset wiped scripts/pg/data/, then blocks until the TCP port is
# accepting connections.
#
# Contract: prints "UP: postgresql://nyansapo@127.0.0.1:5433/nyansapo" and
# exits 0 on success; prints "DOWN" and exits 1 otherwise.
set -u

# project root = one level up from this script's directory (.zscripts/)
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PGDIR="$ROOT/scripts/pg"
DATA="$PGDIR/data"
LOG="$PGDIR/postgres.log"
export LD_LIBRARY_PATH="$PGDIR/lib:${LD_LIBRARY_PATH:-}"

url_ok() { timeout 3 bash -c 'cat < /dev/null > /dev/tcp/127.0.0.1/5433' 2>/dev/null; }

# --- 1. bootstrap cluster if wiped -------------------------------------------------
if [ ! -f "$DATA/PG_VERSION" ]; then
  echo "--- initdb (fresh cluster) ---"
  rm -rf "$DATA"
  "$PGDIR/bin/initdb" -D "$DATA" -U nyansapo --auth=trust -E UTF8 --locale=C || exit 1
  # auxiliary dirs + perms (defensive; initdb normally creates these itself)
  mkdir -p "$DATA"/pg_notify "$DATA"/pg_stat "$DATA"/pg_stat_tmp \
           "$DATA"/pg_snapshots "$DATA"/pg_tblspc "$DATA"/pg_twophase \
           "$DATA"/pg_logical/snapshots "$DATA"/pg_logical/mappings
  chmod 700 "$DATA"
  # zonky binaries ship no psql — single-user mode creates the app database
  echo "CREATE DATABASE nyansapo OWNER nyansapo;" \
    | "$PGDIR/bin/postgres" --single -D "$DATA" postgres || exit 1
fi

# --- 2. already up? ----------------------------------------------------------------
if url_ok; then
  echo "UP: postgresql://nyansapo@127.0.0.1:5433/nyansapo"
  exit 0
fi

# --- 3. start ---------------------------------------------------------------------
rm -f "$DATA/postmaster.pid" 2>/dev/null
mkdir -p "$PGDIR/sockets"
"$PGDIR/bin/pg_ctl" -D "$DATA" -l "$LOG" -o "-p 5433 -k $PGDIR/sockets" start || true

# --- 4. wait for TCP ---------------------------------------------------------------
for i in $(seq 1 30); do
  url_ok && { echo "UP: postgresql://nyansapo@127.0.0.1:5433/nyansapo"; exit 0; }
  sleep 1
done

echo "DOWN"
tail -5 "$LOG" 2>/dev/null
exit 1

#!/bin/bash
# Start embedded PostgreSQL 17 for Nyansapo (no root required).
# Relocatable: derives PGHOME from this script's own location,
# so it works both in the sandbox (/home/z/my-project/scripts/pg)
# and inside the deployed artifact (/app/pg).
PGHOME="$(cd "$(dirname "$0")" && pwd)"
export LD_LIBRARY_PATH="$PGHOME/lib:$LD_LIBRARY_PATH"

# Data directory lives next to the binaries (bundled) or sibling (dev).
DATA_DIR="$PGHOME/data"
[ -d "$DATA_DIR" ] || DATA_DIR="$PGHOME/../pg-data"

mkdir -p "$PGHOME/sockets"

if "$PGHOME/bin/pg_ctl" -D "$DATA_DIR" status >/dev/null 2>&1; then
  echo "PostgreSQL already running"
  exit 0
fi

rm -f "$DATA_DIR/postmaster.pid" 2>/dev/null || true
"$PGHOME/bin/pg_ctl" -D "$DATA_DIR" -l "$PGHOME/postgres.log" \
  -o "-p 5433 -k $PGHOME/sockets" start
for i in 1 2 3 4 5; do
  sleep 1
  if grep -q "ready to accept connections" "$PGHOME/postgres.log" 2>/dev/null; then
    break
  fi
done
tail -3 "$PGHOME/postgres.log"

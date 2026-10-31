#!/bin/bash
# Start embedded PostgreSQL 17 for Nyansapo (no root required)
PGHOME=/home/z/my-project/scripts/pg
export LD_LIBRARY_PATH="$PGHOME/lib:$LD_LIBRARY_PATH"

if "$PGHOME/bin/pg_ctl" -D "$PGHOME/data" status >/dev/null 2>&1; then
  echo "PostgreSQL already running"
  exit 0
fi

rm -f "$PGHOME/postmaster.pid" 2>/dev/null || true
"$PGHOME/bin/pg_ctl" -D "$PGHOME/data" -l "$PGHOME/postgres.log" -o "-p 5433 -k $PGHOME/sockets" start
sleep 1
tail -3 "$PGHOME/postgres.log"

#!/bin/bash
# Restore the embedded PostgreSQL cluster from a snapshot produced by
# backup-cluster.sh (cold tar of scripts/pg/data).
#
# Usage: restore-cluster.sh [snapshot.tar.gz]
#   snapshot defaults to backups/latest-cluster.tar.gz
#
# CAUTION: replaces the current cluster directory. The live server is
# stopped first and the wiped data directory is moved aside to
# scripts/pg/data.pre-restore-<ts> (kept for forensics, not auto-deleted).
#
# Contract: prints "RESTORE: ok" and exits 0 on success (port accepting);
# prints "RESTORE: fail <reason>" and exits 1 otherwise.
set -u

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
PGDIR="$ROOT/scripts/pg"
DATA="$PGDIR/data"
LOG="$PGDIR/postgres.log"
export LD_LIBRARY_PATH="$PGDIR/lib:${LD_LIBRARY_PATH:-}"

SNAP="${1:-$ROOT/backups/latest-cluster.tar.gz}"

if [ ! -s "$SNAP" ]; then
  echo "RESTORE: fail no-snapshot ($SNAP)"
  exit 1
fi

url_ok() { timeout 3 bash -c 'cat < /dev/null > /dev/tcp/127.0.0.1/5433' 2>/dev/null; }

# --- stop live server ----------------------------------------------------------------
if url_ok; then
  "$PGDIR/bin/pg_ctl" -D "$DATA" -m fast stop >/dev/null 2>&1
  for i in $(seq 1 15); do url_ok || break; sleep 1; done
fi
url_ok && { echo "RESTORE: fail server-wont-stop"; exit 1; }

# --- swap data dir -------------------------------------------------------------------
TS="$(date +%Y%m%d-%H%M%S)"
rm -rf "$DATA.pre-restore-$TS"
if [ -d "$DATA" ]; then
  mv "$DATA" "$DATA.pre-restore-$TS"
fi
mkdir -p "$PGDIR"

# --- unpack --------------------------------------------------------------------------
if ! tar -xzf "$SNAP" -C "$PGDIR"; then
  echo "RESTORE: fail untar"
  exit 1
fi
[ -f "$DATA/PG_VERSION" ] || { echo "RESTORE: fail bad-snapshot"; exit 1; }

# excluded from the snapshot; PG requires the dir to exist
mkdir -p "$DATA/pg_replslot"

# --- start (pg-start.sh also bootstraps aux dirs / perms) ----------------------------
if ! bash "$ROOT/.zscripts/pg-start.sh" | grep -q '^UP:'; then
  echo "RESTORE: fail start"
  exit 1
fi

# --- verify data actually came back ---------------------------------------------------
COUNT="$(DATABASE_URL='postgresql://nyansapo@127.0.0.1:5433/nyansapo' node -e '
const {PrismaClient}=require("@prisma/client");
const p=new PrismaClient();
p.school.count().then(c=>{console.log(c);return p.$disconnect()}).catch(()=>{console.log(0)});
' 2>/dev/null || echo 0)"
if [ "${COUNT:-0}" = "0" ]; then
  echo "RESTORE: fail empty-data (schools=0)"
  exit 1
fi

echo "RESTORE: ok schools=$COUNT snapshot=$SNAP (previous cluster kept at scripts/pg/data.pre-restore-$TS)"

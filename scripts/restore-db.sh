#!/bin/bash
# Restore a fresh embedded Postgres cluster after a sandbox recycle.
set -euo pipefail
cd /home/z/my-project
export DATABASE_URL='postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public'
PGHOME=/home/z/my-project/scripts/pg
DATA_DIR=/home/z/my-project/scripts/pg-data
export LD_LIBRARY_PATH="$PGHOME/lib${LD_LIBRARY_PATH:+:$LD_LIBRARY_PATH}"

if [ ! -d "$DATA_DIR" ]; then
  echo "── initdb"
  "$PGHOME/bin/initdb" -D "$DATA_DIR" -U nyansapo --auth=trust >/tmp/initdb.log 2>&1
fi
bash scripts/pg/start-postgres.sh
for i in $(seq 1 20); do
  if bun -e "const sql=new Bun.SQL('postgres://nyansapo@127.0.0.1:5433/postgres'); await sql\`select 1\`; await sql.end()" 2>/dev/null; then break; fi
  sleep 1
done
bun -e "const sql=new Bun.SQL('postgres://nyansapo@127.0.0.1:5433/postgres'); const r=await sql\`select 1 from pg_database where datname='nyansapo'\`; if(!r.length){await sql.unsafe('CREATE DATABASE nyansapo OWNER nyansapo')}; await sql.end()" || { echo "CREATE DATABASE failed"; exit 1; }
echo "── bun install"
bun install >/tmp/buninstall.log 2>&1 || { tail -5 /tmp/buninstall.log; exit 1; }
echo "── prisma db push"
bunx prisma db push --skip-generate >/tmp/dbpush.log 2>&1 || { tail -5 /tmp/dbpush.log; exit 1; }
echo "── reseed chain"
bash scripts/reseed-chain.sh
echo "── done"

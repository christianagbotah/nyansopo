#!/bin/bash
# One-shot environment restore after a sandbox reset. Idempotent.
exec >> /tmp/restore-all.log 2>&1
echo "=== restore-all $(date '+%F %T') ==="
cd /home/z/my-project || exit 1
export DATABASE_URL='postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public'
if [ ! -x node_modules/.bin/next ]; then echo "--- bun install ---"; bun install || exit 1; fi
PGDIR=/home/z/my-project/scripts/pg
export LD_LIBRARY_PATH="$PGDIR/lib:$LD_LIBRARY_PATH"
if [ ! -f "$PGDIR/data/PG_VERSION" ]; then
  echo "--- initdb ---"
  "$PGDIR/bin/initdb" -D "$PGDIR/data" -U nyansapo --auth=trust -E UTF8 --locale=C || exit 1
  echo "CREATE DATABASE nyansapo OWNER nyansapo;" | "$PGDIR/bin/postgres" --single -D "$PGDIR/data" postgres || exit 1
fi
if ! timeout 3 bash -c 'cat < /dev/null > /dev/tcp/127.0.0.1/5433' 2>/dev/null; then
  echo "--- pg start ---"
  rm -f "$PGDIR/data/postmaster.pid"
  timeout 90 "$PGDIR/bin/pg_ctl" -D "$PGDIR/data" -l "$PGDIR/postgres.log" -o "-p 5433 -k $PGDIR/sockets" start || true
  for i in $(seq 1 20); do timeout 3 bash -c 'cat < /dev/null > /dev/tcp/127.0.0.1/5433' 2>/dev/null && break; sleep 1; done
fi
timeout 3 bash -c 'cat < /dev/null > /dev/tcp/127.0.0.1/5433' 2>/dev/null && echo "PG UP" || { echo "PG DOWN"; tail -5 "$PGDIR/postgres.log"; exit 1; }
SEEDED=$(node -e "const{PrismaClient}=require('@prisma/client');const p=new PrismaClient();p.school.count().then(c=>{console.log(c);return p.\$disconnect()}).catch(()=>{console.log(0)})" 2>/dev/null || echo 0)
if [ "$SEEDED" = "0" ]; then
  echo "--- prisma db push + reseed ---"
  bunx prisma db push --skip-generate > /dev/null 2>&1 || exit 1
  bash scripts/reseed-chain.sh || exit 1
fi
code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 3 http://localhost:3000/ || true)
if [ "$code" != "200" ]; then
  echo "--- dev start ---"
  pgrep -f "next dev" | xargs -r kill 2>/dev/null; sleep 1
  rm -f dev.log
  nohup bun run dev > dev.log 2>&1 &
  sleep 3
fi
for i in $(seq 1 40); do code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 http://localhost:3000/ || true); [ "$code" = "200" ] && break; sleep 4; done
[ "$code" = "200" ] && echo "DEV UP (200)" || { echo "DEV DOWN (last: $code)"; tail -5 dev.log; exit 1; }
echo "=== restore-all OK ==="

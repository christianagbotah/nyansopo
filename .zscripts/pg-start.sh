#!/bin/bash
# Start (or restart) portable PostgreSQL 16 for Nyansapo on 127.0.0.1:5433
# DB: nyansapo  User: nyansapo (trust auth)  — matches DATABASE_URL in .env
# NOTE: the sandbox resets wiped the distro PG; this portable install lives in /home/z.
export PATH=/home/z/pgsql/bin:$PATH
if pg_ctl -D /home/z/pgdata status > /dev/null 2>&1; then
  echo "PG already running"
else
  rm -f /home/z/pgdata/postmaster.pid
  pg_ctl -D /home/z/pgdata -l /home/z/pg.log -w start
fi
for i in $(seq 1 20); do
  if psql -h 127.0.0.1 -p 5433 -U nyansapo -d nyansapo -tc "SELECT 1" > /dev/null 2>&1; then
    echo "UP: postgresql://nyansapo@127.0.0.1:5433/nyansapo"
    exit 0
  fi
  sleep 1
done
echo "DOWN"
exit 1

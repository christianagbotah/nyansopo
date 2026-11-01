# Embedded PostgreSQL 17 (sandbox / no-root runtime)

The app runs on **PostgreSQL 17**, not SQLite. Because the sandbox has no root
and no system PostgreSQL, we run a self-contained server from this directory.

## Layout

```
scripts/pg/
├── start-postgres.sh   # relocatable launcher (also bundled into deploys)
├── bin/                # postgres, initdb, pg_ctl (zonky embedded binaries)
├── lib/, share/        # runtime libs + timezone/share data
├── data/               # the database cluster (nyansapo superuser, trust auth)
└── sockets/            # unix socket dir; TCP listener on 127.0.0.1:5433
```

## Connection

```
DATABASE_URL=postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public
```

## Daily use

- Start: `bash scripts/pg/start-postgres.sh` (idempotent — no-op if running).
  `dev.sh` and the deploy `start.sh` both call it automatically.
- Schema sync: `DATABASE_URL=... npx prisma db push`
- The stale `DATABASE_URL=file:...db/custom.db` shell env var (inherited in the
  sandbox) overrides `.env` — always export the postgres URL in ad-hoc commands.

## Re-provisioning (fresh machine)

1. Download the zonky embedded binaries jar from Maven (Aliyun mirror is
   friendlier from this region):
   `curl -L -o pg-binaries.jar https://maven.aliyun.com/repository/central/io/zonky/test/postgres/embedded-postgres-binaries-linux-amd64/17.4.0/embedded-postgres-binaries-linux-amd64-17.4.0.jar`
2. `unzip pg-binaries.jar -d pgdist && tar -xJf pgdist/postgres-linux-x86_64.txz`
   (this extracts `bin/ lib/ share/` into the current dir)
3. `./bin/initdb -D "$PWD/data" -U nyansapo --auth=trust -E UTF8 --locale=C`
4. `bash start-postgres.sh && npx prisma db push`
5. Optional demo data: `bun prisma/seed.ts && bun prisma/seed-ops.ts && bun prisma/seed-ops-extra.ts && bun prisma/seed-classes.ts && bun scripts/seed-parity-extras.ts`

## Deploy packaging

`.zscripts/database-runtime-build.sh` bundles `bin/ lib/ share/ data/` +
`start-postgres.sh` into the build artifact (as `/app/pg`); the deploy
`start.sh` boots it unless an external `DATABASE_URL` (e.g. Supabase/Neon) is
provided — in that case the external database is used and the bundled one is
ignored.

## Legacy SQLite

The original SQLite database is kept at `db/legacy-sqlite-backup.db`
(git-ignored) for reference. `scripts/migrate-to-postgres.ts` performed the
one-time data migration (17,887 rows across 61 models, verified 1:1).
`prisma/schema-sqlite-read.prisma` (+ `generated/sqlite-client`) exist only so
that script can read the old file; the live schema is `prisma/schema.prisma`.

## Recycling recovery (sandbox wiped `data/`)

The DB cluster (`scripts/pg/data/`) is NOT tracked. If it is missing:

```bash
export LD_LIBRARY_PATH=scripts/pg/lib:$LD_LIBRARY_PATH
scripts/pg/bin/initdb -D scripts/pg/data -U nyansapo -A trust
mkdir -p scripts/pg/data/pg_notify scripts/pg/data/pg_stat scripts/pg/data/pg_stat_tmp \
  scripts/pg/data/pg_snapshots scripts/pg/data/pg_tblspc scripts/pg/data/pg_twophase \
  scripts/pg/data/pg_logical/snapshots scripts/pg/data/pg_logical/mappings
chmod 700 scripts/pg/data
bash scripts/pg/start-postgres.sh
node -e "const{Client}=require('pg');(async()=>{const c=new Client({connectionString:'postgresql://nyansapo@127.0.0.1:5433/postgres'});await c.connect();const r=await c.query(\"SELECT 1 FROM pg_database WHERE datname='nyansapo'\");if(!r.rowCount)await c.query('CREATE DATABASE nyansapo');await c.end();})()"
export DATABASE_URL='postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public'
npx prisma db push --accept-data-loss
for s in prisma/seed.ts prisma/seed-classes.ts prisma/seed-ops.ts prisma/seed-ops-extra.ts \
         prisma/seed-rbac.ts prisma/seed-daily-ext.ts prisma/seed-exams.ts \
         prisma/seed-fare-parity.ts prisma/seed-parity2.ts \
         scripts/seed-jhs3-bece.ts scripts/seed-pos-sales.ts; do bun "$s"; done
```

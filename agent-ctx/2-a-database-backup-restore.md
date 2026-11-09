# Task 2-a (Wave 12 / Backlog E) — Database Backup & Restore (CI3 `admin/backup` parity)

## What was built

### 1. API — `src/app/api/school/[id]/ops/backup/route.ts` (new)
Follows the established ops-route style (sms route pattern): `opsAuth` + action-dispatch POST.

- **GET** (perms `["backup.manage"]`, `strict: true` — SCHOOL_ADMIN/ACCOUNTANT get 403, association view NOT enough):
  - default → `{ backups: [ { id, filename, sizeBytes, note, kind, createdByName, createdAt, fileExists } ] (newest first), diskUsageBytes (sum of .sql in dir), dir: "db/backups", lastBackupAt }`
  - `?file=<filename>` → streams the dump with `Content-Disposition: attachment`.
- **POST** actions (perms `["backup.manage"]`):
  - `create` — spawns `/home/z/pgsql/bin/pg_dump -h 127.0.0.1 -p 5433 -U nyansapo --no-password --clean --if-exists nyansapo` (conn parsed from DATABASE_URL, trust auth), stdout streamed to `db/backups/db-YYYY-MM-DD_HH-MM-SS.sql` (5-min timeout, same-second collisions get `_n` suffix). On success creates the DbBackup row (sizeBytes from fs.stat, note ≤200 chars, createdByName = auth.user.fullName). On failure: 500 + stderr tail, partial file removed.
  - `delete` — file + row, 404 when neither exists.
  - `restore` — requires `body.confirm === "RESTORE"` (exact) else 400 "Type RESTORE to confirm"; runs `psql … -v ON_ERROR_STOP=0 -f <file>` (10-min timeout), returns exit code + last 12 output lines + "refresh the app" note. Destructive by design (dump includes DROP TABLE IF EXISTS — CI3 `add_drop=TRUE` parity).
  - `cleanup` — deletes ALL matching files + all DbBackup rows.
- **Filename validation**: `^db-\d{4}-\d{2}-\d{2}[_0-9-]*\.sql$` **plus** resolved-dirname must equal the backups dir (defense in depth; regex itself excludes `.` `/` `\`). NOTE: brief's regex `[_0-9]*` was missing the hyphen needed for the documented `db-…_HH-MM-SS.sql` name format — fixed to `[_0-9-]*`, documented here.

### 2. UI — `src/components/modules/settings.tsx` (modified)
- `canBackup = session?.permissions?.includes("backup.manage")` (mirrors the Theme tab's `canTheme` gating).
- Deep-link list now includes `...(canBackup ? ["backup"] : [])` (PAGE_INDEX `pg-set-backup` → tab "backup" already existed).
- New "Backups" TabsTrigger (DatabaseBackup icon) + `{canBackup && currentSchoolId && <TabsContent value="backup">}` rendering the new `BackupTab()` sub-component (same-file pattern as `SbaConfigTab`/`WaecGradingTab`).
- BackupTab: amber warning banner (CI3 info-alert copy), create card with optional note + spinner + toast, 3 StatCards (count / on-disk size / last backup), table (filename+note+kind badge, human size, created by, date, icon-only action buttons with aria-labels), "Clear all", restore Dialog requiring the user to type RESTORE (destructive copy, destructive button disabled until match), delete AlertDialog, LoadingBlock while fetching, EmptyState when none. Download = fetch→blob (API needs the `x-user-id` header, so no plain `<a>` link). No blue/indigo; responsive (sm/lg grids, overflow-x-auto table).

### 3. Smoke — `scripts/smoke-backup.ts` (new, smoke-theme.ts pattern)
24 checks: (a) super GET 200+shape, (b) head@ GET+POST 403, (c) bursar POST 403, (d) create→file+row+size>0+note/author, (e) list+disk usage, (f) download attachment+SQL header, (g) traversal + malformed filename rejected, (h) 2nd create + delete 1st, (i) restore w/o confirm 400, (j) restore w/ confirm → 200 + app healthy, (k) cleanup → all gone, (l) delete non-existent 404. Snapshots pre-existing files+rows (copies files to `db/backups-smoke-keep/`, recreates rows) and restores that state in `finally`.

## Verification
- `DATABASE_URL=postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public bun scripts/smoke-backup.ts` → **24/24 PASS** (run twice; second run after an OOM-killed dev-server restart).
- `bunx tsc --noEmit` → **0 errors** (whole project).
- `bunx eslint src/app/api/school/[id]/ops/backup/route.ts src/components/modules/settings.tsx scripts/smoke-backup.ts` → **0 errors**.

## Key facts for later agents
- **pg auth**: trust auth on 127.0.0.1:5433 for user `nyansapo` (see `.zscripts/pg-start.sh`) — no password anywhere; pg_dump uses `--no-password`, psql uses `-w`.
- **pg_dump MUST use `--clean --if-exists`**: without it a restore into a populated DB just errors "already exists" everywhere and the COPY blocks fail on PK conflicts (no data actually restored). With it, restore is a true snapshot replay: exit 0, no errors, and **DbBackup rows created after the dump vanish** (the dump predates them) — expected, matches CI3.
- Restore while the dev server's Prisma pool is open works fine (idle connections hold no locks).
- **The dev server gets OOM-killed sporadically** by the sandbox (next-server anon-rss ~1.8–2GB, `dmesg` shows oom-kill). It is NOT caused by this feature. Restart: `cd /home/z/my-project && (setsid nohup bun run dev > /dev/null 2>&1 < /dev/null &)` and wait ~8s. It died twice during this task (after manual curl testing, and after tsc/eslint runs) and was restarted both times.
- Did NOT touch: `prisma/schema.prisma` (DbBackup model already existed), `src/lib/rbac.ts` (`backup.manage` already existed, SCHOOL_ADMIN excluded), `src/components/app-shell.tsx`, `src/lib/page-index.ts` (`pg-set-backup` already existed), `src/lib/store.ts`, `src/app/page.tsx`, sms files.
- Smoke leaves zero state behind (dir + table verified empty after both runs).

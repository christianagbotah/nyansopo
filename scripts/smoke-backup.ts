/* Database backup/restore (CI3 admin/backup parity) smoke test —
   (a) super GET list shape, (b) school-admin GET+POST 403, (c) accountant
   POST 403, (d) create → file + row + size, (e) list shows it, (f) download
   (Content-Disposition + SQL header), (g) path traversal rejected,
   (h) second backup + delete first, (i) restore without confirm 400,
   (j) restore with confirm → app still healthy, (k) cleanup → all gone,
   (l) delete non-existent 404.
   Snapshots pre-existing backups (files + rows) and restores that state.
   Run: DATABASE_URL=postgresql://… bun scripts/smoke-backup.ts */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";
import { copyFile, mkdir, readdir, rm, stat } from "node:fs/promises";
import path from "node:path";

const db = new PrismaClient();
const BASE = "http://localhost:3000";
const BACKUP_DIR = path.join(process.cwd(), "db", "backups");
const KEEP_DIR = path.join(process.cwd(), "db", "backups-smoke-keep");
let pass = 0, fail = 0;

async function check(label: string, cond: boolean, extra = "") {
  if (cond) { pass++; console.log(`  ✅ ${label}`); }
  else { fail++; console.log(`  ❌ ${label} ${extra}`); }
}

async function main() {
  const [superAdmin, headAdmin, bursar] = await Promise.all([
    db.user.findUnique({ where: { email: "super@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "head@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "bursar@nyansapo.gh" } }),
  ]);
  if (!superAdmin || !headAdmin || !bursar) { console.log("missing demo users — run the seed first"); process.exit(1); }
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!school) { console.log("GEA school missing"); process.exit(1); }
  const schoolId = school.id;
  const H = (u: typeof superAdmin) => ({ "content-type": "application/json", "x-user-id": u.id });
  const get = async (u: typeof superAdmin, url: string) => fetch(BASE + url, { headers: H(u) });
  const post = async (u: typeof superAdmin, url: string, body: any) => fetch(BASE + url, { method: "POST", headers: H(u), body: JSON.stringify(body) });

  // ── snapshot pre-existing state (files + rows) so it can be restored ──
  const preRows = await db.dbBackup.findMany();
  const preFiles = (await readdir(BACKUP_DIR).catch(() => [] as string[])).filter((f) => f.endsWith(".sql"));
  if (preFiles.length) { await mkdir(KEEP_DIR, { recursive: true }); for (const f of preFiles) await copyFile(path.join(BACKUP_DIR, f), path.join(KEEP_DIR, f)); }

  try {
    // (a) super admin list
    const g1 = await get(superAdmin, `/api/school/${schoolId}/ops/backup`);
    const j1 = await g1.json();
    check("(a) super GET → 200", g1.status === 200, `got ${g1.status}`);
    check("(a) payload has backups array + disk usage", Array.isArray(j1.backups) && typeof j1.diskUsageBytes === "number" && typeof j1.dir === "string", JSON.stringify(j1).slice(0, 120));

    // (b) school admin (SCHOOL_ADMIN — backup.manage excluded) blocked both ways
    const g2 = await get(headAdmin, `/api/school/${schoolId}/ops/backup`);
    check("(b) school-admin GET → 403", g2.status === 403, `got ${g2.status}`);
    const p2 = await post(headAdmin, `/api/school/${schoolId}/ops/backup`, { action: "create" });
    check("(b) school-admin POST → 403", p2.status === 403, `got ${p2.status}`);

    // (c) accountant POST blocked
    const p3 = await post(bursar, `/api/school/${schoolId}/ops/backup`, { action: "create" });
    check("(c) accountant POST → 403", p3.status === 403, `got ${p3.status}`);

    // (d) create backup
    const p4 = await post(superAdmin, `/api/school/${schoolId}/ops/backup`, { action: "create", note: "smoke run 1" });
    const j4 = await p4.json();
    check("(d) create → 200 with backup row", p4.status === 200 && !!j4.backup?.filename, JSON.stringify(j4).slice(0, 120));
    const f1name: string = j4.backup?.filename ?? "";
    const f1row = await db.dbBackup.findUnique({ where: { filename: f1name } });
    const f1stat = await stat(path.join(BACKUP_DIR, f1name)).catch(() => null);
    check("(d) dump file exists on disk", !!f1stat, f1name);
    check("(d) DbBackup row exists with sizeBytes > 0", !!f1row && f1row.sizeBytes > 0, JSON.stringify(f1row?.sizeBytes));
    check("(d) row carries note + author", f1row?.note === "smoke run 1" && f1row?.createdByName === superAdmin.fullName, `${f1row?.note}/${f1row?.createdByName}`);

    // (e) list shows it
    const g5 = await get(superAdmin, `/api/school/${schoolId}/ops/backup`);
    const j5 = await g5.json();
    check("(e) list shows the new backup", j5.backups?.some((b: any) => b.filename === f1name && b.fileExists === true), JSON.stringify(j5.backups?.map((b: any) => b.filename)));
    check("(e) disk usage counts the dump", j5.diskUsageBytes >= (f1stat?.size ?? 0), String(j5.diskUsageBytes));

    // (f) download
    const g6 = await get(superAdmin, `/api/school/${schoolId}/ops/backup?file=${encodeURIComponent(f1name)}`);
    const t6 = await g6.text();
    check("(f) download → 200 with attachment header", g6.status === 200 && (g6.headers.get("content-disposition") ?? "").includes("attachment"), `${g6.status}/${g6.headers.get("content-disposition")}`);
    check("(f) body is a SQL dump (has -- header)", t6.includes("--"), t6.slice(0, 40));

    // (g) path traversal rejected
    const g7 = await get(superAdmin, `/api/school/${schoolId}/ops/backup?file=../schema.prisma`);
    check("(g) path traversal → 400/404", g7.status === 400 || g7.status === 404, `got ${g7.status}`);
    const g7b = await get(superAdmin, `/api/school/${schoolId}/ops/backup?file=${encodeURIComponent("db-2020-01-01_00-00-00;rm.sql")}`);
    check("(g) malformed filename → 400/404", g7b.status === 400 || g7b.status === 404, `got ${g7b.status}`);

    // (h) second backup, then delete the first
    const p8 = await post(superAdmin, `/api/school/${schoolId}/ops/backup`, { action: "create", note: "smoke run 2" });
    const j8 = await p8.json();
    const f2name: string = j8.backup?.filename ?? "";
    check("(h) second create → 200", p8.status === 200 && !!f2name && f2name !== f1name, JSON.stringify(j8).slice(0, 120));
    const p9 = await post(superAdmin, `/api/school/${schoolId}/ops/backup`, { action: "delete", filename: f1name });
    check("(h) delete first → 200", p9.status === 200, `got ${p9.status}`);
    const gone = await stat(path.join(BACKUP_DIR, f1name)).then(() => false).catch(() => true);
    const rowGone = !(await db.dbBackup.findUnique({ where: { filename: f1name } }));
    check("(h) first file + row gone", gone && rowGone, `${gone}/${rowGone}`);

    // (i) restore without confirm
    const p10 = await post(superAdmin, `/api/school/${schoolId}/ops/backup`, { action: "restore", filename: f2name });
    const j10 = await p10.json();
    check("(i) restore without confirm → 400", p10.status === 400 && /RESTORE/i.test(j10.error ?? ""), `${p10.status}/${j10.error}`);

    // (j) restore with confirm — destructive snapshot replay
    const p11 = await post(superAdmin, `/api/school/${schoolId}/ops/backup`, { action: "restore", filename: f2name, confirm: "RESTORE" });
    const j11 = await p11.json();
    check("(j) restore with confirm → 200", p11.status === 200 && j11.exitCode === 0, `${p11.status}/${j11.exitCode}/${JSON.stringify(j11).slice(0, 160)}`);
    const g12 = await get(superAdmin, `/api/school/${schoolId}/ops/backup`);
    check("(j) app still healthy after restore (GET 200)", g12.status === 200, `got ${g12.status}`);

    // (k) cleanup — everything gone
    const p13 = await post(superAdmin, `/api/school/${schoolId}/ops/backup`, { action: "cleanup" });
    const j13 = await p13.json();
    check("(k) cleanup → 200", p13.status === 200 && j13.ok === true, `${p13.status}/${JSON.stringify(j13).slice(0, 120)}`);
    const filesLeft = (await readdir(BACKUP_DIR).catch(() => [] as string[])).filter((f) => f.endsWith(".sql"));
    const rowsLeft = await db.dbBackup.count();
    check("(k) no files or rows left", filesLeft.length === 0 && rowsLeft === 0, `${filesLeft.length} files / ${rowsLeft} rows`);

    // (l) delete a non-existent backup
    const p14 = await post(superAdmin, `/api/school/${schoolId}/ops/backup`, { action: "delete", filename: "db-1999-01-01_00-00-00.sql" });
    check("(l) delete non-existent → 404", p14.status === 404, `got ${p14.status}`);
  } finally {
    // ── cleanup: restore the pre-existing backup state ──
    await db.dbBackup.deleteMany({});
    if (preRows.length) {
      await db.dbBackup.createMany({ data: preRows.map((r) => ({ id: r.id, schoolId: r.schoolId, filename: r.filename, sizeBytes: r.sizeBytes, note: r.note, kind: r.kind, createdByName: r.createdByName, createdAt: r.createdAt })) });
    }
    for (const f of preFiles) await copyFile(path.join(KEEP_DIR, f), path.join(BACKUP_DIR, f));
    await rm(KEEP_DIR, { recursive: true, force: true });
    // any strays this run created (e.g. after a failed check) are removed
    const nowFiles = (await readdir(BACKUP_DIR).catch(() => [] as string[])).filter((f) => f.endsWith(".sql") && !preFiles.includes(f));
    for (const f of nowFiles) await rm(path.join(BACKUP_DIR, f), { force: true });
  }

  console.log(`\nsmoke-backup: ${pass} passed, ${fail} failed`);
  process.exit(fail ? 1 : 0);
}

main().finally(() => db.$disconnect());

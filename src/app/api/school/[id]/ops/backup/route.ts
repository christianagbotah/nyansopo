import { NextRequest, NextResponse } from "next/server";
import { spawn } from "node:child_process";
import { createWriteStream, existsSync } from "node:fs";
import { mkdir, readdir, readFile, stat, unlink } from "node:fs/promises";
import path from "node:path";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

// ── Database backup & restore (CI3 Admin::backup() parity) ──
// pg_dump/psql live in the portable PG install; the cluster on 127.0.0.1:5433
// uses trust auth (see .zscripts/pg-start.sh), so no password is involved.

const PG_BIN_DIR = "/home/z/pgsql/bin";

/** The standalone server chdirs to .next/standalone at boot, and that folder
 *  MIRRORS the project (package.json, prisma/, even a stale .git — enough to
 *  fool any content sniffing), so a plain cwd-relative path lands inside the
 *  ephemeral build folder, which is wiped on every rebuild: backups would
 *  silently vanish. Deterministic resolution instead: 1) explicit env
 *  override, 2) the standalone shape cwd === <root>/.next/standalone → root,
 *  3) dev server, whose cwd IS the checkout. */
function projectRoot(): string {
  const cwd = process.cwd();
  if (path.basename(cwd) === "standalone" && path.basename(path.dirname(cwd)) === ".next") {
    return path.dirname(path.dirname(cwd));
  }
  return cwd;
}
const BACKUP_DIR = process.env.NYANSAPO_BACKUP_DIR
  ? path.resolve(process.env.NYANSAPO_BACKUP_DIR)
  : path.join(projectRoot(), "db", "backups");
// db-YYYY-MM-DD_HH-MM-SS.sql — digits/underscore/hyphen only after the date,
// so no ".", "/" or "\" can ever smuggle a path traversal past this pattern.
const FILENAME_RE = /^db-\d{4}-\d{2}-\d{2}[_0-9-]*\.sql$/;
const DUMP_TIMEOUT_MS = 5 * 60_000;
const RESTORE_TIMEOUT_MS = 10 * 60_000;

/** connection bits from DATABASE_URL (trust auth — no password by design) */
function pgConn(): { host: string; port: string; user: string; db: string } {
  try {
    const u = new URL(process.env.DATABASE_URL ?? "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public");
    return {
      host: u.hostname || "127.0.0.1",
      port: u.port || "5432",
      user: decodeURIComponent(u.username) || "nyansapo",
      db: u.pathname.replace(/^\//, "").split("?")[0] || "nyansapo",
    };
  } catch {
    return { host: "127.0.0.1", port: "5433", user: "nyansapo", db: "nyansapo" };
  }
}

/** strict filename validation + resolution inside the backups dir (no traversal) */
function resolveBackupFile(name: unknown): string | null {
  if (typeof name !== "string" || !FILENAME_RE.test(name)) return null;
  const resolved = path.resolve(BACKUP_DIR, name);
  if (path.dirname(resolved) !== BACKUP_DIR) return null;
  return resolved;
}

/** db-YYYY-MM-DD_HH-MM-SS (CI3 date("Y-m-d_H-i-s")) */
function timestampBase(now = new Date()): string {
  const p = (n: number) => String(n).padStart(2, "0");
  return `db-${now.getFullYear()}-${p(now.getMonth() + 1)}-${p(now.getDate())}_${p(now.getHours())}-${p(now.getMinutes())}-${p(now.getSeconds())}`;
}

/** run a PG binary, buffering output (tailed so huge dumps can't exhaust memory) */
function runPg(args: string[], timeoutMs: number): Promise<{ code: number; stdout: string; stderr: string; timedOut: boolean; spawnError: string | null }> {
  return new Promise((resolve) => {
    const child = spawn(path.join(PG_BIN_DIR, "psql"), args, { env: { ...process.env } });
    let stdout = "";
    let stderr = "";
    let timedOut = false;
    const tail = (s: string, chunk: Buffer) => (s + chunk.toString()).slice(-8_000);
    const timer = setTimeout(() => { timedOut = true; child.kill("SIGKILL"); }, timeoutMs);
    child.stdout?.on("data", (d: Buffer) => { stdout = tail(stdout, d); });
    child.stderr?.on("data", (d: Buffer) => { stderr = tail(stderr, d); });
    const finish = (code: number, spawnError: string | null) => {
      clearTimeout(timer);
      resolve({ code, stdout, stderr, timedOut, spawnError });
    };
    child.on("error", (err) => finish(-1, err.message));
    child.on("close", (code) => finish(code ?? -1, null));
  });
}

/** pg_dump straight to a file (streamed — never buffered in memory) */
function dumpToFile(args: string[], outPath: string, timeoutMs: number): Promise<{ code: number; stderr: string; timedOut: boolean; spawnError: string | null }> {
  return new Promise((resolve) => {
    const child = spawn(path.join(PG_BIN_DIR, "pg_dump"), args, { env: { ...process.env } });
    const out = createWriteStream(outPath);
    let stderr = "";
    let timedOut = false;
    let settled = false;
    const tail = (s: string, chunk: Buffer) => (s + chunk.toString()).slice(-4_000);
    const timer = setTimeout(() => { timedOut = true; child.kill("SIGKILL"); }, timeoutMs);
    const finish = (code: number, spawnError: string | null) => {
      if (settled) return;
      settled = true;
      clearTimeout(timer);
      out.end();
      resolve({ code, stderr, timedOut, spawnError });
    };
    child.stdout?.pipe(out);
    child.stderr?.on("data", (d: Buffer) => { stderr = tail(stderr, d); });
    out.on("error", (err) => { stderr = `${stderr}\n${err.message}`.trim(); child.kill("SIGKILL"); });
    child.on("error", (err) => finish(-1, err.message));
    child.on("close", (code) => finish(code ?? -1, null));
  });
}

/** total size of the .sql dumps currently on disk */
async function diskUsageBytes(): Promise<number> {
  let total = 0;
  const entries = await readdir(BACKUP_DIR).catch(() => [] as string[]);
  for (const e of entries) {
    if (!e.endsWith(".sql")) continue;
    try { total += (await stat(path.join(BACKUP_DIR, e))).size; } catch { /* raced away */ }
  }
  return total;
}

// ── GET /api/school/[id]/ops/backup ──
// ?file=<filename> → stream that dump as an attachment (Content-Disposition).
// otherwise → backup list (newest first) + disk usage + last backup time.
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["backup.manage"], strict: true });
  if (!auth.ok) return auth.res;
  const url = new URL(req.url);
  const file = url.searchParams.get("file");

  if (file !== null) {
    const filePath = resolveBackupFile(file);
    if (!filePath) return NextResponse.json({ error: "Invalid backup filename" }, { status: 400 });
    try {
      const buf = await readFile(filePath);
      const st = await stat(filePath);
      return new NextResponse(new Uint8Array(buf), {
        headers: {
          "content-type": "application/sql; charset=utf-8",
          "content-disposition": `attachment; filename="${path.basename(filePath)}"`,
          "content-length": String(st.size),
          "cache-control": "no-store",
        },
      });
    } catch {
      return NextResponse.json({ error: "Backup file not found" }, { status: 404 });
    }
  }

  const rows = await db.dbBackup.findMany({ where: { schoolId }, orderBy: { createdAt: "desc" } });
  const usage = await diskUsageBytes();
  return NextResponse.json({
    backups: rows.map((r) => ({
      id: r.id, filename: r.filename, sizeBytes: r.sizeBytes, note: r.note, kind: r.kind,
      createdByName: r.createdByName, createdAt: r.createdAt,
      fileExists: existsSync(path.join(BACKUP_DIR, r.filename)),
    })),
    diskUsageBytes: usage,
    dir: "db/backups",
    lastBackupAt: rows[0]?.createdAt ?? null,
  });
}

// ── POST /api/school/[id]/ops/backup — action dispatch ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["backup.manage"] });
  if (!auth.ok) return auth.res;
  const user = auth.user;
  const conn = pgConn();

  // create: pg_dump the whole school-system DB into db/backups (CI3 create_backup)
  if (action === "create") {
    await mkdir(BACKUP_DIR, { recursive: true });
    const base = timestampBase();
    let filename = `${base}.sql`;
    // same-second collisions (filename is globally unique) get a _n suffix
    for (let n = 1; existsSync(path.join(BACKUP_DIR, filename)) || await db.dbBackup.findUnique({ where: { filename } }); n++) {
      filename = `${base}_${n}.sql`;
    }
    const outPath = path.join(BACKUP_DIR, filename);
    // --clean --if-exists → DROP ... IF EXISTS before each CREATE (CI3 dbutil add_drop=TRUE):
    // restoring must actually replace the tables, not collide with them.
    const res = await dumpToFile(
      ["-h", conn.host, "-p", conn.port, "-U", conn.user, "--no-password", "--clean", "--if-exists", conn.db],
      outPath, DUMP_TIMEOUT_MS,
    );
    if (res.code !== 0 || res.timedOut || res.spawnError) {
      await unlink(outPath).catch(() => null);
      const detail = (res.spawnError ?? (res.stderr || "no stderr output")).split("\n").filter(Boolean).slice(-6).join("\n");
      return NextResponse.json({ error: res.timedOut ? `pg_dump timed out after ${DUMP_TIMEOUT_MS / 1000}s` : "pg_dump failed", detail }, { status: 500 });
    }
    const st = await stat(outPath);
    const note = typeof body.note === "string" && body.note.trim() ? body.note.trim().slice(0, 200) : null;
    const row = await db.dbBackup.create({
      data: { schoolId, filename, sizeBytes: st.size, note, kind: "MANUAL", createdByName: user.fullName },
    });
    return NextResponse.json({ backup: row, message: `Backup created: ${filename}` });
  }

  // delete: remove the dump file + its row (CI3 delete)
  if (action === "delete") {
    const filePath = resolveBackupFile(body.filename);
    if (!filePath) return NextResponse.json({ error: "Invalid backup filename" }, { status: 400 });
    let deletedFile = false;
    let deletedRow = false;
    try { await unlink(filePath); deletedFile = true; } catch (e: any) {
      if (e?.code !== "ENOENT") return NextResponse.json({ error: "Failed to delete the backup file", detail: String(e?.message ?? e) }, { status: 500 });
    }
    const row = await db.dbBackup.findUnique({ where: { filename: body.filename } });
    if (row) { await db.dbBackup.delete({ where: { id: row.id } }); deletedRow = true; }
    if (!deletedFile && !deletedRow) return NextResponse.json({ error: "Backup not found" }, { status: 404 });
    return NextResponse.json({ ok: true, deletedFile, deletedRow, message: `${body.filename} deleted` });
  }

  // restore: replay a dump with psql (CI3 restore — destructive, DROP/CREATE included)
  if (action === "restore") {
    if (body.confirm !== "RESTORE") return NextResponse.json({ error: "Type RESTORE to confirm" }, { status: 400 });
    const filePath = resolveBackupFile(body.filename);
    if (!filePath) return NextResponse.json({ error: "Invalid backup filename" }, { status: 400 });
    try { await stat(filePath); } catch {
      return NextResponse.json({ error: "Backup file not found" }, { status: 404 });
    }
    const res = await runPg(
      ["-h", conn.host, "-p", conn.port, "-U", conn.user, "-w", conn.db, "-v", "ON_ERROR_STOP=0", "-f", filePath],
      RESTORE_TIMEOUT_MS,
    );
    const output = `${res.stdout}\n${res.stderr}`.split("\n").map((l) => l.trim()).filter(Boolean).slice(-12).join("\n");
    if (res.spawnError) return NextResponse.json({ error: "Could not run psql", detail: res.spawnError }, { status: 500 });
    if (res.timedOut) return NextResponse.json({ error: `psql timed out after ${RESTORE_TIMEOUT_MS / 1000}s`, output }, { status: 500 });
    if (res.code !== 0) return NextResponse.json({ error: "Restore failed (psql exited non-zero)", exitCode: res.code, output }, { status: 500 });
    return NextResponse.json({
      ok: true, exitCode: res.code, output,
      message: `Restored ${body.filename} — the data on screen is now the snapshot; refresh the app to reload it.`,
    });
  }

  // cleanup: wipe every backup file + row ("Clear all" — same permission gate)
  if (action === "cleanup") {
    const entries = await readdir(BACKUP_DIR).catch(() => [] as string[]);
    let files = 0;
    for (const e of entries) {
      if (FILENAME_RE.test(e)) {
        await unlink(path.join(BACKUP_DIR, e)).catch(() => null);
        files++;
      }
    }
    const rows = await db.dbBackup.deleteMany({});
    return NextResponse.json({ ok: true, filesDeleted: files, rowsDeleted: rows.count, message: `Cleared ${files} file(s) and ${rows.count} log row(s)` });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

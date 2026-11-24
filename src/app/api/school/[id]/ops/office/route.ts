import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";

// ── Nyansapo Office (wave 56 — Microsoft Office-like portal) ──
// Teachers & staff type documents (word processor), workbooks (spreadsheet)
// and presentations (slides) directly inside the system. Files are stored
// per-school with kind-specific editor state in contentJson:
//   DOC   → { html }
//   SHEET → { cols, rows, cells: { A1: { v, b, i, a, fmt } } }
//   SLIDE → { slides: [{ title, body, bg, notes }], theme }
// Folders are free-form strings (My Documents / Shared / Reports …).
// Autosaves are NOT audited (they would flood the trail) — create, rename,
// move, duplicate and delete are.

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });

const KINDS = ["DOC", "SHEET", "SLIDE"] as const;
type Kind = (typeof KINDS)[number];
const isKind = (v: unknown): v is Kind => typeof v === "string" && (KINDS as readonly string[]).includes(v);

const cleanTitle = (v: unknown): string | null => {
  if (typeof v !== "string") return null;
  const t = v.trim().slice(0, 120);
  return t.length ? t : null;
};
const cleanFolder = (v: unknown): string | null => {
  if (typeof v !== "string") return null;
  const t = v.trim().slice(0, 60);
  return t.length ? t : null;
};

const MAX_CONTENT = 3_000_000; // ~3 MB of editor JSON
const cleanContent = (v: unknown): string | null => {
  if (typeof v !== "string") return null;
  if (v.length > MAX_CONTENT) return null;
  try { JSON.parse(v); } catch { return null; }
  return v;
};

/** Starter content for a fresh file of each kind. */
function seedContent(kind: Kind): string {
  if (kind === "DOC") return JSON.stringify({ html: "<p><br></p>" });
  if (kind === "SHEET") return JSON.stringify({ cols: 18, rows: 40, cells: {} });
  return JSON.stringify({ slides: [{ title: "", body: "", bg: "", notes: "" }], theme: "kente" });
}

const FILE_COLS = {
  id: true, kind: true, title: true, folder: true, starred: true,
  createdByName: true, updatedByName: true, createdAt: true, updatedAt: true,
};

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["office.use"] });
  if (!auth.ok) return auth.res;

  const fileId = new URL(req.url).searchParams.get("id");
  if (fileId) {
    const file = await db.officeFile.findFirst({ where: { id: fileId, schoolId } });
    if (!file) return bad("File not found", 404);
    return NextResponse.json({ ok: true, file });
  }

  const [files, folders] = await Promise.all([
    db.officeFile.findMany({ where: { schoolId }, orderBy: { updatedAt: "desc" }, select: FILE_COLS }),
    db.officeFile.findMany({ where: { schoolId }, distinct: ["folder"], select: { folder: true }, orderBy: { folder: "asc" } }),
  ]);
  return NextResponse.json({
    ok: true,
    files,
    folders: folders.map((f) => f.folder),
    counts: {
      all: files.length,
      DOC: files.filter((f) => f.kind === "DOC").length,
      SHEET: files.filter((f) => f.kind === "SHEET").length,
      SLIDE: files.filter((f) => f.kind === "SLIDE").length,
    },
  });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["office.use"] });
  if (!auth.ok) return auth.res;
  const actor = { id: auth.user.id, fullName: auth.user.fullName ?? "Office user", role: auth.user.role };
  const actorName = actor.fullName;

  let body: any;
  try { body = await req.json(); } catch { return bad("Invalid JSON body"); }
  const action = body?.action;

  // ── create ── new blank document / workbook / presentation
  if (action === "create") {
    if (!isKind(body.kind)) return bad("kind must be DOC, SHEET or SLIDE");
    const title = cleanTitle(body.title);
    if (!title) return bad("A file name is required");
    const folder = cleanFolder(body.folder) ?? "My Documents";
    const row = await db.officeFile.create({
      data: { schoolId, kind: body.kind, title, folder, contentJson: seedContent(body.kind), createdByName: actorName, updatedByName: actorName },
    });
    await audit({ schoolId, actor, action: "CREATE", entityType: "OfficeFile", entityId: row.id, entityLabel: title, summary: `Office file created: ${title} (${body.kind}) in ${folder}`, req });
    return NextResponse.json({ ok: true, file: row });
  }

  const existing = await db.officeFile.findFirst({ where: { id: String(body?.id ?? ""), schoolId } });
  if (!existing && action !== "create") return bad("File not found", 404);

  // ── save ── persist editor content (autosave + Ctrl+S)
  if (action === "save") {
    const content = cleanContent(body.contentJson);
    if (!content) return bad(content === null && typeof body.contentJson === "string" ? "File too large or invalid content" : "contentJson (a JSON string) is required");
    const title = cleanTitle(body.title);
    const row = await db.officeFile.update({
      where: { id: existing!.id },
      data: { contentJson: content, ...(title ? { title } : {}), updatedByName: actorName },
    });
    return NextResponse.json({ ok: true, file: { id: row.id, updatedAt: row.updatedAt } });
  }

  // ── rename ──
  if (action === "rename") {
    const title = cleanTitle(body.title);
    if (!title) return bad("A file name is required");
    const row = await db.officeFile.update({ where: { id: existing!.id }, data: { title, updatedByName: actorName } });
    await audit({ schoolId, actor, action: "UPDATE", entityType: "OfficeFile", entityId: row.id, entityLabel: title, summary: `Office file renamed: ${existing!.title} → ${title}`, req });
    return NextResponse.json({ ok: true, file: { id: row.id, title: row.title } });
  }

  // ── move ── change folder
  if (action === "move") {
    const folder = cleanFolder(body.folder);
    if (!folder) return bad("A folder name is required");
    const row = await db.officeFile.update({ where: { id: existing!.id }, data: { folder, updatedByName: actorName } });
    await audit({ schoolId, actor, action: "UPDATE", entityType: "OfficeFile", entityId: row.id, entityLabel: row.title, summary: `Office file moved: ${row.title} → ${folder}`, req });
    return NextResponse.json({ ok: true, file: { id: row.id, folder: row.folder } });
  }

  // ── star ── pin/unpin
  if (action === "star") {
    const row = await db.officeFile.update({ where: { id: existing!.id }, data: { starred: !!body.starred } });
    return NextResponse.json({ ok: true, file: { id: row.id, starred: row.starred } });
  }

  // ── duplicate ──
  if (action === "duplicate") {
    const row = await db.officeFile.create({
      data: {
        schoolId, kind: existing!.kind,
        title: `${existing!.title} (copy)`.slice(0, 120),
        folder: existing!.folder,
        contentJson: existing!.contentJson,
        createdByName: actorName, updatedByName: actorName,
      },
    });
    await audit({ schoolId, actor, action: "CREATE", entityType: "OfficeFile", entityId: row.id, entityLabel: row.title, summary: `Office file duplicated: ${existing!.title} → ${row.title}`, req });
    return NextResponse.json({ ok: true, file: { id: row.id, title: row.title } });
  }

  // ── delete ──
  if (action === "delete") {
    await db.officeFile.delete({ where: { id: existing!.id } });
    await audit({ schoolId, actor, action: "DELETE", entityType: "OfficeFile", entityId: existing!.id, entityLabel: existing!.title, summary: `Office file deleted: ${existing!.title}`, req });
    return NextResponse.json({ ok: true });
  }

  return bad("Unknown action");
}

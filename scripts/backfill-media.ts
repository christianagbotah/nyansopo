/**
 * Backfill script — migrate legacy data-URL images to MediaAsset blobs.
 *
 * Before the blob-storage wave, admission portraits (Student.photoUrl) and
 * branding images (SchoolSetting branding.logoUrl / branding.signatureUrl)
 * were stored as base64 data-URLs inside hot rows. This script walks those
 * columns, stores each valid image as a MediaAsset and rewrites the field
 * to the short /api/media/{id} reference. Idempotent: data-URL-shaped
 * values are converted, /api/media/… values are skipped, junk is left alone.
 *
 * Usage: bun scripts/backfill-media.ts [--dry]
 */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const dry = process.argv.includes("--dry");

const DATA_URL_RE = /^data:(image\/(?:png|jpeg|webp));base64,([A-Za-z0-9+/=]+)$/i;

let converted = 0, skipped = 0, junk = 0;

async function toAsset(schoolId: string, kind: string, value: string): Promise<string | null> {
  const m = DATA_URL_RE.exec(value);
  if (!m) return null;
  const bytes = Buffer.from(m[2], "base64");
  if (!bytes.length || bytes.length > 700 * 1024) return null;
  const asset = await db.mediaAsset.create({ data: { schoolId, kind, mimeType: m[1].toLowerCase(), sizeBytes: bytes.length, data: bytes } });
  return `/api/media/${asset.id}`;
}

async function main() {
  // 1) Student portraits (school resolved via enrollment — Student has no direct schoolId)
  const students = await db.student.findMany({ where: { photoUrl: { startsWith: "data:image/" } }, select: { id: true, photoUrl: true } });
  for (const s of students) {
    const enrol = await db.enrollment.findFirst({ where: { studentId: s.id }, orderBy: { admissionDate: "desc" } });
    if (!enrol) { junk++; continue; }
    const url = await toAsset(enrol.schoolId, "PORTRAIT", s.photoUrl!);
    if (url) {
      converted++;
      if (!dry) await db.student.update({ where: { id: s.id }, data: { photoUrl: url } });
    } else junk++;
  }
  // 2) Branding settings
  const settings = await db.schoolSetting.findMany({ where: { key: { in: ["branding.logoUrl", "branding.signatureUrl"] } } });
  for (const row of settings) {
    const raw = (() => { try { return JSON.parse(row.value); } catch { return null; } })();
    if (typeof raw !== "string") { skipped++; continue; } // null / cleared value
    if (raw.startsWith("/api/media/")) { skipped++; continue; }
    if (!raw.startsWith("data:image/")) { junk++; continue; }
    const kind = row.key.endsWith("logoUrl") ? "LOGO" : "SIGNATURE";
    const url = await toAsset(row.schoolId, kind, raw);
    if (url) {
      converted++;
      if (!dry) await db.schoolSetting.update({ where: { id: row.id }, data: { value: JSON.stringify(url) } });
    } else junk++;
  }

  console.log(`media backfill ${dry ? "(dry run) " : ""}— converted: ${converted}, already migrated/null: ${skipped}, junk (left as-is): ${junk}`);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

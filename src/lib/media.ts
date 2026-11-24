import { PrismaClient } from "@prisma/client";
import { randomBytes } from "crypto";

// ── Media/blob storage helper ────────────────────────────────
// Portraits & branding uploads arrive as client-resized data-URLs but are
// stored as binary MediaAsset rows; the entity field keeps only a short
// reference URL (`/api/media/{id}`). Keeps Student / SchoolSetting rows
// slim and makes the fetch lazy + immutable-cacheable. To move to S3 or
// a CDN later, change this module + GET /api/media/[id] only.

export type MediaKind = "PORTRAIT" | "LOGO" | "SIGNATURE" | "SITE";

/** mime types accepted for uploads (matches the client-side resize output) */
const ALLOWED_MIME = new Set(["image/png", "image/jpeg", "image/webp"]);

/** decoded byte cap — the client resizes to well below this (~320px JPEG) */
export const MAX_MEDIA_BYTES = 700 * 1024;

/** Parse + decode an image data-URL into bytes, enforcing mime + size caps. */
export function decodeImageDataUrl(value: string): { mimeType: string; bytes: Buffer } | null {
  if (typeof value !== "string" || !value.startsWith("data:")) return null;
  const m = /^data:([a-z]+\/[a-z0-9.+-]+);base64,([\s\S]+)$/i.exec(value);
  if (!m) return null;
  const [, mimeType, b64] = m;
  if (!ALLOWED_MIME.has(mimeType.toLowerCase())) return null;
  let bytes: Buffer;
  try {
    bytes = Buffer.from(b64, "base64");
  } catch {
    return null;
  }
  if (!bytes.length || bytes.length > MAX_MEDIA_BYTES) return null;
  return { mimeType: mimeType.toLowerCase(), bytes };
}

/** URL form a stored asset is referenced by. */
export const MEDIA_URL_RE = /^\/api\/media\/[A-Za-z0-9]{20,}(\?.*)?$/;

/** Cryptographically strong public token for website assets (wave 54). */
export function makePublicToken(): string {
  return randomBytes(24).toString("base64url");
}

/**
 * Store a data-URL as a binary MediaAsset and return its reference URL.
 * With `public: true` (wave 54 — public school website) the asset gets a
 * publicToken and is served unauthenticated at /api/media/{id}?t={token}.
 */
export async function storeMedia(opts: {
  db: PrismaClient;
  schoolId: string;
  kind: MediaKind;
  dataUrl: string;
  public?: boolean;
}): Promise<string | null> {
  const decoded = decodeImageDataUrl(opts.dataUrl);
  if (!decoded) return null;
  const publicToken = opts.public ? makePublicToken() : null;
  const asset = await opts.db.mediaAsset.create({
    data: {
      schoolId: opts.schoolId,
      kind: opts.kind,
      mimeType: decoded.mimeType,
      sizeBytes: decoded.bytes.length,
      data: new Uint8Array(decoded.bytes), // plain Uint8Array (Prisma Bytes typing)
      publicToken,
    },
  });
  return `/api/media/${asset.id}`;
}

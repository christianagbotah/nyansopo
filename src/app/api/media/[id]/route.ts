import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser, canOperateSchool } from "@/lib/session";

// GET /api/media/[id] — stream a stored media asset (portrait / logo /
// signature / public website asset). Two access paths:
//   1. session-authenticated + school-scoped (staff can read their school's
//      assets; association officers keep read-only oversight) — privately
//      cacheable for as long as the session lives;
//   2. wave 54 — public: assets carrying a publicToken (kind SITE, shown on
//      the public school website /site/{code}) are served unauthenticated
//      via ?t={publicToken} — immutable + publicly cacheable.
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  if (!/^[A-Za-z0-9]{20,}$/.test(id)) return NextResponse.json({ error: "Not found" }, { status: 404 });

  const asset = await db.mediaAsset.findUnique({ where: { id }, include: { school: true } });
  if (!asset) return NextResponse.json({ error: "Not found" }, { status: 404 });

  const token = req.nextUrl.searchParams.get("t");
  if (token && asset.publicToken && token === asset.publicToken) {
    const bytes = new Uint8Array(asset.data);
    return new NextResponse(bytes, {
      status: 200,
      headers: {
        "content-type": asset.mimeType,
        "content-length": String(bytes.byteLength),
        "cache-control": "public, max-age=31536000, immutable",
      },
    });
  }

  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });

  const canView = canOperateSchool(user, asset.schoolId) || user.nodeIds.includes(asset.school.associationNodeId);
  if (!canView) return NextResponse.json({ error: "Forbidden" }, { status: 403 });

  const bytes = new Uint8Array(asset.data);
  return new NextResponse(bytes, {
    status: 200,
    headers: {
      "content-type": asset.mimeType,
      "content-length": String(bytes.byteLength),
      "cache-control": "private, max-age=31536000, immutable",
    },
  });
}

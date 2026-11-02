import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser, canOperateSchool } from "@/lib/session";

// GET /api/media/[id] — stream a stored media asset (portrait / logo /
// signature). Auth + school-scoped: school staff can read their school's
// assets, association officers keep their read-only oversight. Content is
// immutable per id (a new upload creates a new asset), so responses are
// privately cacheable for as long as the session lives.
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  if (!/^[A-Za-z0-9]{20,}$/.test(id)) return NextResponse.json({ error: "Not found" }, { status: 404 });

  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });

  const asset = await db.mediaAsset.findUnique({ where: { id }, include: { school: true } });
  if (!asset) return NextResponse.json({ error: "Not found" }, { status: 404 });

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

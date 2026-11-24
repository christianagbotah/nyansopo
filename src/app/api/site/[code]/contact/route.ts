import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";

// ── Public contact form (wave 54 — CI3 send_contact_message parity) ──
// Unauthenticated POST from the public school website contact page; stores
// the message in the school's Website inbox (SiteContactMessage) where
// website.manage holders triage it (handled / delete). Requires the school's
// site to be published — an unpublished site never accepts enquiries.

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });

export async function POST(req: NextRequest, { params }: { params: Promise<{ code: string }> }) {
  const { code } = await params;

  let body: any;
  try { body = await req.json(); } catch { return bad("Invalid JSON body"); }

  const firstName = typeof body?.firstName === "string" ? body.firstName.trim() : "";
  const comment = typeof body?.comment === "string" ? body.comment.trim() : "";
  if (!firstName || !comment) return bad("Name and message are required");
  if (firstName.length > 80 || comment.length > 4000) return bad("Message too long");

  const school = await db.school.findUnique({
    where: { code },
    include: { siteSetting: { select: { published: true } } },
  });
  if (!school) return bad("School not found", 404);
  if (!school.siteSetting?.published) return bad("This school's website is not accepting messages right now", 403);

  const str = (v: unknown, max: number) => {
    const s = typeof v === "string" ? v.trim() : "";
    return s && s.length <= max ? s : null;
  };

  const row = await db.siteContactMessage.create({
    data: {
      schoolId: school.id,
      firstName,
      lastName: str(body?.lastName, 80),
      email: str(body?.email, 160),
      phone: str(body?.phone, 40),
      address: str(body?.address, 240),
      comment,
    },
  });

  return NextResponse.json({ ok: true, id: row.id });
}

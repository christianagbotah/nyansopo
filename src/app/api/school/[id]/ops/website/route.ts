import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";
import { storeMedia, MEDIA_URL_RE } from "@/lib/media";

// ── Public school website CMS (wave 54 — CI3 frontend_pages / Frontend_model
// parity). One published site per school at /site/{school.code}: hero slider
// (3 slides), events (reuses SchoolEvent audience=ALL — managed in Front
// Office), news posts, gallery albums, staff showcase toggles, about / terms /
// privacy pages, contact inbox and the publish switch.
//
// Image uploads arrive as client-resized data-URLs and are stored as kind=SITE
// MediaAsset rows carrying a publicToken so the public site can stream them
// unauthenticated via /api/media/{id}?t={token}.

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });
const optStr = (v: unknown): string | null => {
  if (typeof v !== "string") return null;
  const t = v.trim();
  return t.length ? t : null;
};

/** Guard a stored media reference: must be /api/media/{id} (query token ok). */
const mediaRef = (v: unknown): string | null => {
  const s = optStr(v);
  if (!s) return null;
  return MEDIA_URL_RE.test(s) ? s : null;
};

/** /api/media/{id}?t=... → the bare MediaAsset id. */
const mediaIdFromUrl = (url: string | null | undefined): string | null => {
  if (!url) return null;
  const m = /^\/api\/media\/([A-Za-z0-9]{20,})/.exec(url);
  return m ? m[1] : null;
};

/** Upload an image data-URL as a public SITE MediaAsset; returns its /api/media/{id} URL. */
async function uploadPublicImage(schoolId: string, dataUrl: unknown): Promise<string | null> {
  if (typeof dataUrl !== "string" || !dataUrl.startsWith("data:")) return null;
  return storeMedia({ db, schoolId, kind: "SITE", dataUrl, public: true });
}

const absDay = (v: unknown): Date | null => {
  const s = optStr(v);
  if (!s) return null;
  const d = new Date(s);
  return isNaN(d.getTime()) ? null : d;
};

type Slide = { title: string; description: string; imageId: string | null };
function parseSlider(json: string): Slide[] {
  try {
    const raw = JSON.parse(json);
    if (!Array.isArray(raw)) return [];
    return raw.slice(0, 3).map((s: any) => ({
      title: typeof s?.title === "string" ? s.title : "",
      description: typeof s?.description === "string" ? s.description : "",
      imageId: typeof s?.imageId === "string" ? s.imageId : null,
    }));
  } catch {
    return [];
  }
}

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["website.manage"] });
  if (!auth.ok) return auth.res;

  const school = await db.school.findUnique({
    where: { id: schoolId },
    select: { code: true, name: true, motto: true, town: true, region: true },
  });
  if (!school) return bad("School not found", 404);

  const [setting, news, albums, staff, messages, events, unreadCount] = await Promise.all([
    db.siteSetting.findUnique({ where: { schoolId } }),
    db.siteNews.findMany({ where: { schoolId }, orderBy: { publishedAt: "desc" } }),
    db.siteGalleryAlbum.findMany({
      where: { schoolId },
      orderBy: { createdAt: "desc" },
      include: { images: { orderBy: { createdAt: "asc" } } },
    }),
    db.staff.findMany({
      where: { schoolId, active: true },
      select: { id: true, fullName: true, role: true, title: true, qualification: true, showOnWebsite: true },
      orderBy: [{ role: "asc" }, { fullName: "asc" }],
    }),
    db.siteContactMessage.findMany({ where: { schoolId }, orderBy: { createdAt: "desc" }, take: 100 }),
    db.schoolEvent.findMany({
      where: { schoolId, audience: "ALL", startsAt: { gte: new Date(new Date().toISOString().slice(0, 10)) } },
      orderBy: { startsAt: "asc" },
      take: 12,
      select: { id: true, title: true, category: true, startsAt: true, endsAt: true, location: true, description: true },
    }),
    db.siteContactMessage.count({ where: { schoolId, handled: false } }),
  ]);

  return NextResponse.json({
    ok: true,
    school,
    publicUrl: `/site/${school.code}`,
    setting: setting ?? null,
    slider: parseSlider(setting?.sliderJson ?? "[]"),
    news,
    albums,
    staff,
    messages,
    events,
    unread: unreadCount,
  });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["website.manage"] });
  if (!auth.ok) return auth.res;
  const actor = { id: auth.user.id, fullName: auth.user.fullName ?? "Website admin", role: auth.user.role };

  let body: any;
  try { body = await req.json(); } catch { return bad("Invalid JSON body"); }
  const action = optStr(body?.action);
  if (!action) return bad("action is required");

  /* ── settings.save — slider + pages + contact + publish (CI3 slider/about/terms/privacy parity) ── */
  if (action === "settings.save") {
    const current = await db.siteSetting.upsert({
      where: { schoolId },
      create: { schoolId },
      update: {},
    });
    const patch: any = {};

    if (Array.isArray(body?.slider)) {
      const incoming = body.slider.slice(0, 3) as any[];
      const slides: Slide[] = [];
      for (let i = 0; i < 3; i++) {
        const s = incoming[i] ?? {};
        let imageId: string | null = typeof s?.imageId === "string" ? s.imageId : null;
        if (s?.imageDataUrl) {
          imageId = mediaIdFromUrl(await uploadPublicImage(schoolId, s.imageDataUrl));
          if (!imageId) return bad(`Slide ${i + 1}: image upload failed (use a small PNG/JPEG/WebP)`);
        }
        slides.push({
          title: optStr(s?.title) ?? "",
          description: optStr(s?.description) ?? "",
          imageId,
        });
      }
      patch.sliderJson = JSON.stringify(slides);
    }
    if (body?.aboutUs !== undefined) patch.aboutUs = optStr(body.aboutUs);
    if (body?.terms !== undefined) patch.terms = optStr(body.terms);
    if (body?.privacy !== undefined) patch.privacy = optStr(body.privacy);
    if (body?.contactEmail !== undefined) patch.contactEmail = optStr(body.contactEmail);
    if (body?.contactPhone !== undefined) patch.contactPhone = optStr(body.contactPhone);
    if (body?.aboutUsImage !== undefined) {
      if (body.aboutUsImage === null) {
        patch.aboutUsImageId = null;
      } else {
        const url = await uploadPublicImage(schoolId, body.aboutUsImage);
        if (!url) return bad("About image upload failed (use a small PNG/JPEG/WebP)");
        patch.aboutUsImageId = mediaIdFromUrl(url);
      }
    }
    if (body?.published !== undefined) patch.published = body.published === true;

    const row = await db.siteSetting.update({ where: { schoolId }, data: patch });

    if (body?.published !== undefined && body.published !== current.published) {
      await audit({
        schoolId, actor,
        action: body.published ? "PUBLISH" : "UNPUBLISH",
        entityType: "SiteSetting", entityId: row.id, entityLabel: "Public website",
        summary: body.published
          ? `Public website published at /site/${await schoolCode(schoolId)}`
          : "Public website unpublished",
        req,
      });
    } else {
      await audit({
        schoolId, actor, action: "UPDATE",
        entityType: "SiteSetting", entityId: row.id, entityLabel: "Public website",
        summary: "Website content updated (slider / pages / contact)",
        req,
      });
    }
    return NextResponse.json({ ok: true, setting: row, slider: parseSlider(row.sliderJson) });
  }

  /* ── news.add | news.edit | news.delete (CI3 frontend_news CRUD) ── */
  if (action === "news.add" || action === "news.edit") {
    const title = optStr(body?.title);
    const text = optStr(body?.body);
    if (!title) return bad("News title is required");
    if (!text) return bad("News body is required");
    if (action === "news.edit") {
      const id = optStr(body?.newsId);
      if (!id) return bad("newsId is required");
      const existing = await db.siteNews.findUnique({ where: { id } });
      if (!existing || existing.schoolId !== schoolId) return bad("News post not found", 404);
      let imageId = existing.imageId;
      if (body?.imageDataUrl) {
        const url = await uploadPublicImage(schoolId, body.imageDataUrl);
        if (!url) return bad("News image upload failed (use a small PNG/JPEG/WebP)");
        imageId = mediaIdFromUrl(url);
      } else if (body?.removeImage === true) {
        imageId = null;
      }
      const row = await db.siteNews.update({
        where: { id },
        data: {
          title, body: text, imageId,
          publishedAt: body?.publishedAt ? absDay(body.publishedAt) ?? existing.publishedAt : existing.publishedAt,
        },
      });
      await audit({ schoolId, actor, action: "UPDATE", entityType: "SiteNews", entityId: row.id, entityLabel: title, summary: `Website news edited: ${title}`, req });
      return NextResponse.json({ ok: true, news: row });
    }
    let imageId: string | null = null;
    if (body?.imageDataUrl) {
      const url = await uploadPublicImage(schoolId, body.imageDataUrl);
      if (!url) return bad("News image upload failed (use a small PNG/JPEG/WebP)");
      imageId = mediaIdFromUrl(url);
    }
    const row = await db.siteNews.create({
      data: {
        schoolId, title, body: text, imageId,
        publishedAt: body?.publishedAt ? absDay(body.publishedAt) ?? new Date() : new Date(),
      },
    });
    await audit({ schoolId, actor, action: "CREATE", entityType: "SiteNews", entityId: row.id, entityLabel: title, summary: `Website news published: ${title}`, req });
    return NextResponse.json({ ok: true, news: row });
  }

  if (action === "news.delete") {
    const id = optStr(body?.newsId);
    if (!id) return bad("newsId is required");
    const existing = await db.siteNews.findUnique({ where: { id } });
    if (!existing || existing.schoolId !== schoolId) return bad("News post not found", 404);
    await db.siteNews.delete({ where: { id } });
    await audit({ schoolId, actor, action: "DELETE", entityType: "SiteNews", entityId: id, entityLabel: existing.title, summary: `Website news deleted: ${existing.title}`, req });
    return NextResponse.json({ ok: true });
  }

  /* ── album.add | album.edit | album.delete | album.toggle (CI3 frontend_gallery CRUD) ── */
  if (action === "album.add" || action === "album.edit") {
    const title = optStr(body?.title);
    if (!title) return bad("Album title is required");
    const description = optStr(body?.description);
    if (action === "album.edit") {
      const id = optStr(body?.albumId);
      if (!id) return bad("albumId is required");
      const existing = await db.siteGalleryAlbum.findUnique({ where: { id } });
      if (!existing || existing.schoolId !== schoolId) return bad("Album not found", 404);
      let coverId = existing.coverId;
      if (body?.coverDataUrl) {
        const url = await uploadPublicImage(schoolId, body.coverDataUrl);
        if (!url) return bad("Cover upload failed (use a small PNG/JPEG/WebP)");
        coverId = mediaIdFromUrl(url);
      }
      const row = await db.siteGalleryAlbum.update({
        where: { id },
        data: {
          title, description,
          showOnWebsite: body?.showOnWebsite === undefined ? existing.showOnWebsite : body.showOnWebsite === true,
          coverId,
        },
      });
      await audit({ schoolId, actor, action: "UPDATE", entityType: "SiteGalleryAlbum", entityId: row.id, entityLabel: title, summary: `Gallery album edited: ${title}`, req });
      return NextResponse.json({ ok: true, album: row });
    }
    let coverId: string | null = null;
    if (body?.coverDataUrl) {
      const url = await uploadPublicImage(schoolId, body.coverDataUrl);
      if (!url) return bad("Cover upload failed (use a small PNG/JPEG/WebP)");
      coverId = mediaIdFromUrl(url);
    }
    const row = await db.siteGalleryAlbum.create({
      data: { schoolId, title, description, showOnWebsite: body?.showOnWebsite !== false, coverId },
    });
    await audit({ schoolId, actor, action: "CREATE", entityType: "SiteGalleryAlbum", entityId: row.id, entityLabel: title, summary: `Gallery album created: ${title}`, req });
    return NextResponse.json({ ok: true, album: row });
  }

  if (action === "album.toggle") {
    const id = optStr(body?.albumId);
    if (!id) return bad("albumId is required");
    const existing = await db.siteGalleryAlbum.findUnique({ where: { id } });
    if (!existing || existing.schoolId !== schoolId) return bad("Album not found", 404);
    const row = await db.siteGalleryAlbum.update({ where: { id }, data: { showOnWebsite: !existing.showOnWebsite } });
    await audit({ schoolId, actor, action: row.showOnWebsite ? "PUBLISH" : "UNPUBLISH", entityType: "SiteGalleryAlbum", entityId: id, entityLabel: existing.title, summary: `Album ${row.showOnWebsite ? "shown on" : "hidden from"} website: ${existing.title}`, req });
    return NextResponse.json({ ok: true, album: row });
  }

  if (action === "album.delete") {
    const id = optStr(body?.albumId);
    if (!id) return bad("albumId is required");
    const existing = await db.siteGalleryAlbum.findUnique({ where: { id } });
    if (!existing || existing.schoolId !== schoolId) return bad("Album not found", 404);
    await db.siteGalleryAlbum.delete({ where: { id } });
    await audit({ schoolId, actor, action: "DELETE", entityType: "SiteGalleryAlbum", entityId: id, entityLabel: existing.title, summary: `Gallery album deleted: ${existing.title}`, req });
    return NextResponse.json({ ok: true });
  }

  /* ── album-image.add | album-image.delete (CI3 gallery_images multi-upload) ── */
  if (action === "album-image.add") {
    const albumId = optStr(body?.albumId);
    if (!albumId) return bad("albumId is required");
    const album = await db.siteGalleryAlbum.findUnique({ where: { id: albumId } });
    if (!album || album.schoolId !== schoolId) return bad("Album not found", 404);
    const urls: string[] = Array.isArray(body?.images) ? body.images : [];
    if (!urls.length && body?.imageDataUrl) urls.push(body.imageDataUrl);
    if (!urls.length) return bad("At least one image is required");
    const created: string[] = [];
    for (const dataUrl of urls.slice(0, 10)) {
      const url = await uploadPublicImage(schoolId, dataUrl);
      if (!url) continue;
      const imageId = mediaIdFromUrl(url)!;
      await db.siteGalleryImage.create({
        data: { albumId, imageId, caption: optStr(body?.caption) },
      });
      if (!album.coverId) {
        await db.siteGalleryAlbum.update({ where: { id: albumId }, data: { coverId: imageId } });
      }
      created.push(imageId);
    }
    if (!created.length) return bad("Image upload failed (use small PNG/JPEG/WebP files)");
    await audit({ schoolId, actor, action: "UPDATE", entityType: "SiteGalleryAlbum", entityId: albumId, entityLabel: album.title, summary: `${created.length} photo(s) added to album: ${album.title}`, req });
    return NextResponse.json({ ok: true, created });
  }

  if (action === "album-image.delete") {
    const id = optStr(body?.imageRowId);
    if (!id) return bad("imageRowId is required");
    const img = await db.siteGalleryImage.findUnique({ where: { id }, include: { album: true } });
    if (!img || img.album.schoolId !== schoolId) return bad("Image not found", 404);
    await db.siteGalleryImage.delete({ where: { id } });
    await audit({ schoolId, actor, action: "DELETE", entityType: "SiteGalleryImage", entityId: id, entityLabel: img.album.title, summary: `Photo removed from album: ${img.album.title}`, req });
    return NextResponse.json({ ok: true });
  }

  /* ── staff-site.toggle (CI3 teacher.show_on_website parity) ── */
  if (action === "staff-site.toggle") {
    const id = optStr(body?.staffId);
    if (!id) return bad("staffId is required");
    const existing = await db.staff.findUnique({ where: { id } });
    if (!existing || existing.schoolId !== schoolId) return bad("Staff not found", 404);
    const row = await db.staff.update({ where: { id }, data: { showOnWebsite: !existing.showOnWebsite } });
    await audit({ schoolId, actor, action: row.showOnWebsite ? "PUBLISH" : "UNPUBLISH", entityType: "Staff", entityId: id, entityLabel: existing.fullName, summary: `${existing.fullName} ${row.showOnWebsite ? "now shown on" : "hidden from"} website staff page`, req });
    return NextResponse.json({ ok: true, staff: row });
  }

  /* ── contact.handled | contact.delete (contact inbox) ── */
  if (action === "contact.handled" || action === "contact.delete") {
    const id = optStr(body?.messageId);
    if (!id) return bad("messageId is required");
    const existing = await db.siteContactMessage.findUnique({ where: { id } });
    if (!existing || existing.schoolId !== schoolId) return bad("Message not found", 404);
    if (action === "contact.handled") {
      const row = await db.siteContactMessage.update({ where: { id }, data: { handled: !existing.handled } });
      await audit({ schoolId, actor, action: "UPDATE", entityType: "SiteContactMessage", entityId: id, entityLabel: `${existing.firstName} ${existing.lastName ?? ""}`.trim(), summary: `Contact message marked ${row.handled ? "handled" : "unhandled"}`, req });
      return NextResponse.json({ ok: true, message: row });
    }
    await db.siteContactMessage.delete({ where: { id } });
    await audit({ schoolId, actor, action: "DELETE", entityType: "SiteContactMessage", entityId: id, entityLabel: `${existing.firstName} ${existing.lastName ?? ""}`.trim(), summary: "Contact message deleted", req });
    return NextResponse.json({ ok: true });
  }

  return bad(`Unknown action: ${action}`);
}

async function schoolCode(schoolId: string): Promise<string> {
  const s = await db.school.findUnique({ where: { id: schoolId }, select: { code: true } });
  return s?.code ?? "school";
}

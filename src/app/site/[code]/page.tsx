/* Public school website (wave 54 — CI3 frontend_pages / Frontend_model parity).
   /site/{school.code} — the school's face on the internet: hero slider, events,
   news, gallery, staff showcase, about, terms & privacy and a contact form.
   Content is managed by school admins in the Website module; the page only
   renders when the school's SiteSetting.published flag is on. */
import { db } from "@/lib/db";
import { notFound } from "next/navigation";
import type { Metadata } from "next";
import PublicSite, { type SitePayload } from "./public-site";

export const dynamic = "force-dynamic";

export async function generateMetadata({ params }: { params: Promise<{ code: string }> }): Promise<Metadata> {
  const { code } = await params;
  const school = await db.school.findUnique({ where: { code }, select: { name: true, motto: true } });
  if (!school) return { title: "School not found — Nyansapo" };
  return { title: `${school.name}${school.motto ? ` — ${school.motto}` : ""}`, description: `Official website of ${school.name}, ${school.town ?? ""} ${school.region ?? ""}.`.trim() };
}

export default async function SitePage({ params }: { params: Promise<{ code: string }> }) {
  const { code } = await params;
  const school = await db.school.findUnique({
    where: { code },
    select: { id: true, code: true, name: true, motto: true, town: true, region: true, logoColor: true, establishedYear: true },
  });
  if (!school) notFound();

  const setting = await db.siteSetting.findUnique({ where: { schoolId: school.id } });
  if (!setting?.published) {
    return (
      <div className="flex min-h-screen flex-col items-center justify-center gap-3 bg-neutral-100 px-4 text-center">
        <div className="text-3xl font-bold text-neutral-700">{school.name}</div>
        <p className="max-w-md text-sm text-neutral-500">
          The official website of this school is not published yet. Please check back soon.
        </p>
      </div>
    );
  }

  const [events, news, albums, staff] = await Promise.all([
    db.schoolEvent.findMany({
      where: { schoolId: school.id, audience: "ALL", startsAt: { gte: new Date(new Date().toISOString().slice(0, 10)) } },
      orderBy: { startsAt: "asc" },
      take: 12,
      select: { id: true, title: true, category: true, startsAt: true, endsAt: true, location: true, description: true },
    }),
    db.siteNews.findMany({ where: { schoolId: school.id }, orderBy: { publishedAt: "desc" }, take: 12 }),
    db.siteGalleryAlbum.findMany({
      where: { schoolId: school.id, showOnWebsite: true },
      orderBy: { createdAt: "desc" },
      include: { images: { orderBy: { createdAt: "asc" } } },
    }),
    db.staff.findMany({
      where: { schoolId: school.id, active: true, showOnWebsite: true },
      orderBy: [{ role: "asc" }, { fullName: "asc" }],
      select: { id: true, fullName: true, role: true, title: true, qualification: true },
    }),
  ]);

  // Resolve every referenced MediaAsset to a tokenised public URL
  const imageIds = new Set<string>();
  for (const s of safeSlides(setting.sliderJson)) if (s.imageId) imageIds.add(s.imageId);
  if (setting.aboutUsImageId) imageIds.add(setting.aboutUsImageId);
  for (const n of news) if (n.imageId) imageIds.add(n.imageId);
  for (const a of albums) {
    if (a.coverId) imageIds.add(a.coverId);
    for (const im of a.images) imageIds.add(im.imageId);
  }
  const assets = imageIds.size
    ? await db.mediaAsset.findMany({ where: { id: { in: [...imageIds] } }, select: { id: true, publicToken: true } })
    : [];
  const mediaUrl = (imageId: string | null | undefined) => {
    if (!imageId) return null;
    const hit = assets.find((a) => a.id === imageId);
    return hit?.publicToken ? `/api/media/${hit.id}?t=${hit.publicToken}` : null;
  };

  const payload: SitePayload = {
    school: {
      name: school.name,
      motto: school.motto,
      town: school.town,
      region: school.region,
      logoColor: school.logoColor,
      establishedYear: school.establishedYear,
      code: school.code,
    },
    slider: safeSlides(setting.sliderJson).map((s) => ({ ...s, imageUrl: mediaUrl(s.imageId) })),
    aboutUs: setting.aboutUs,
    aboutUsImageUrl: mediaUrl(setting.aboutUsImageId),
    terms: setting.terms,
    privacy: setting.privacy,
    contactEmail: setting.contactEmail,
    contactPhone: setting.contactPhone,
    events: events.map((e) => ({ ...e, startsAt: e.startsAt.toISOString(), endsAt: e.endsAt ? e.endsAt.toISOString() : null })),
    news: news.map((n) => ({ id: n.id, title: n.title, body: n.body, imageUrl: mediaUrl(n.imageId), publishedAt: n.publishedAt.toISOString() })),
    albums: albums.map((a) => ({
      id: a.id,
      title: a.title,
      description: a.description,
      coverUrl: mediaUrl(a.coverId),
      images: a.images.map((im) => ({ id: im.id, imageUrl: mediaUrl(im.imageId), caption: im.caption })),
    })),
    staff,
  };

  return <PublicSite data={payload} />;
}

type RawSlide = { title: string; description: string; imageId: string | null };
function safeSlides(json: string): RawSlide[] {
  try {
    const raw = JSON.parse(json);
    if (!Array.isArray(raw)) return [];
    return raw
      .slice(0, 3)
      .map((s: any) => ({
        title: typeof s?.title === "string" ? s.title : "",
        description: typeof s?.description === "string" ? s.description : "",
        imageId: typeof s?.imageId === "string" ? s.imageId : null,
      }));
  } catch {
    return [];
  }
}

"use client";
// ── Public site renderer (wave 54 — mirrors CI3 frontend/default theme) ──
// Top navigation, auto-rotating hero slider, upcoming events, latest news,
// photo gallery with lightbox, staff showcase, about, contact form (posts to
// /api/site/{code}/contact) and a footer with terms / privacy links.
// Styled with the school's own logo colour.
import { useEffect, useMemo, useState } from "react";
import { cn } from "@/lib/utils";
import {
  Globe, MapPin, Phone, Mail, CalendarRange, Newspaper, Image as ImageIcon,
  Users, Info, ChevronLeft, ChevronRight, Send, GraduationCap, CheckCircle2, Loader2,
} from "lucide-react";

export type SitePayload = {
  school: { name: string; motto: string | null; town: string; region: string; logoColor: string; establishedYear: number | null; code: string };
  slider: { title: string; description: string; imageUrl: string | null }[];
  aboutUs: string | null;
  aboutUsImageUrl: string | null;
  terms: string | null;
  privacy: string | null;
  contactEmail: string | null;
  contactPhone: string | null;
  events: { id: string; title: string; category: string; startsAt: string; endsAt: string | null; location: string | null; description: string | null }[];
  news: { id: string; title: string; body: string; imageUrl: string | null; publishedAt: string }[];
  albums: { id: string; title: string; description: string | null; coverUrl: string | null; images: { id: string; imageUrl: string | null; caption: string | null }[] }[];
  staff: { id: string; fullName: string; role: string; title: string | null; qualification: string | null }[];
};

type PageId = "home" | "about" | "events" | "news" | "gallery" | "staff" | "contact" | "terms" | "privacy";

const NAV: { id: PageId; label: string }[] = [
  { id: "home", label: "Home" },
  { id: "about", label: "About us" },
  { id: "events", label: "Events" },
  { id: "news", label: "News" },
  { id: "gallery", label: "Gallery" },
  { id: "staff", label: "Our staff" },
  { id: "contact", label: "Contact" },
];

const CATEGORY_LABEL: Record<string, string> = {
  PTA_MEETING: "PTA meeting", OPEN_DAY: "Open day", SPORTS: "Sports", PRIZE_GIVING: "Prize-giving",
  EXAMINATION: "Examination", HOLIDAY: "Holiday", OTHER: "Event",
};
const ROLE_LABEL: Record<string, string> = {
  HEADTEACHER: "Head teacher", TEACHER: "Teacher", ACCOUNTANT: "Accountant", ASSISTANT: "Assistant", BURSAR: "Bursar",
};

const day = (iso: string) => new Date(iso).toLocaleDateString(undefined, { day: "numeric", month: "short", year: "numeric" });

export default function PublicSite({ data }: { data: SitePayload }) {
  const accent = data.school.logoColor || "#0F6B47";
  const [page, setPage] = useState<PageId>("home");
  const [lightbox, setLightbox] = useState<{ url: string; caption: string | null } | null>(null);

  const go = (p: PageId) => {
    setPage(p);
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  return (
    <div className="min-h-screen bg-neutral-50 text-neutral-900" style={{ fontFamily: "system-ui, -apple-system, sans-serif" }}>
      {/* top bar */}
      <header className="sticky top-0 z-40 border-b border-neutral-200 bg-white/95 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
          <button onClick={() => go("home")} className="flex min-w-0 items-center gap-2.5 text-left">
            <div className="flex size-9 shrink-0 items-center justify-center rounded-lg text-white" style={{ backgroundColor: accent }}>
              <GraduationCap className="size-5" />
            </div>
            <div className="min-w-0">
              <div className="truncate text-sm font-bold leading-tight">{data.school.name}</div>
              {data.school.motto && <div className="truncate text-[11px] italic text-neutral-500">{data.school.motto}</div>}
            </div>
          </button>
          <nav className="hidden items-center gap-1 md:flex" aria-label="Site sections">
            {NAV.map((n) => (
              <button
                key={n.id}
                onClick={() => go(n.id)}
                className={cn("rounded-full px-3 py-1.5 text-sm transition-colors", page === n.id ? "font-semibold text-white" : "text-neutral-600 hover:bg-neutral-100")}
                style={page === n.id ? { backgroundColor: accent } : undefined}
              >
                {n.label}
              </button>
            ))}
          </nav>
          <button
            onClick={() => go("contact")}
            className="rounded-full px-4 py-2 text-sm font-semibold text-white shadow-sm transition-transform hover:scale-[1.02]"
            style={{ backgroundColor: accent }}
          >
            Enrol / Visit
          </button>
        </div>
        {/* mobile nav */}
        <div className="flex gap-1 overflow-x-auto border-t border-neutral-100 px-3 py-2 md:hidden [scrollbar-width:none]">
          {NAV.map((n) => (
            <button
              key={n.id}
              onClick={() => go(n.id)}
              className={cn("shrink-0 rounded-full px-3 py-1.5 text-xs", page === n.id ? "font-semibold text-white" : "bg-neutral-100 text-neutral-600")}
              style={page === n.id ? { backgroundColor: accent } : undefined}
            >
              {n.label}
            </button>
          ))}
        </div>
      </header>

      <main>
        {page === "home" && <HomePage data={data} accent={accent} go={go} />}
        {page === "about" && <AboutPage data={data} accent={accent} />}
        {page === "events" && <EventsPage data={data} accent={accent} />}
        {page === "news" && <NewsPage data={data} accent={accent} />}
        {page === "gallery" && <GalleryPage data={data} accent={accent} setLightbox={setLightbox} />}
        {page === "staff" && <StaffPage data={data} accent={accent} />}
        {page === "contact" && <ContactPage data={data} accent={accent} />}
        {page === "terms" && <StaticPage title="Terms &amp; conditions" body={data.terms} accent={accent} />}
        {page === "privacy" && <StaticPage title="Privacy policy" body={data.privacy} accent={accent} />}
      </main>

      {/* footer */}
      <footer className="mt-12 border-t border-neutral-200 bg-white">
        <div className="mx-auto grid max-w-6xl gap-6 px-4 py-8 sm:grid-cols-3">
          <div>
            <div className="text-sm font-bold" style={{ color: accent }}>{data.school.name}</div>
            {data.school.motto && <div className="mt-1 text-xs italic text-neutral-500">{data.school.motto}</div>}
            <div className="mt-2 flex items-start gap-1 text-xs text-neutral-500">
              <MapPin className="mt-0.5 size-3 shrink-0" />
              {data.school.town}, {data.school.region}
            </div>
          </div>
          <div className="text-xs text-neutral-500">
            <div className="mb-1.5 font-semibold text-neutral-700">Reach us</div>
            {data.contactPhone && <div className="flex items-center gap-1"><Phone className="size-3" /> {data.contactPhone}</div>}
            {data.contactEmail && <div className="flex items-center gap-1"><Mail className="size-3" /> {data.contactEmail}</div>}
          </div>
          <div className="text-xs text-neutral-500">
            <div className="mb-1.5 font-semibold text-neutral-700">Legal</div>
            <button className="block underline hover:text-neutral-700" onClick={() => go("terms")}>Terms &amp; conditions</button>
            <button className="mt-1 block underline hover:text-neutral-700" onClick={() => go("privacy")}>Privacy policy</button>
          </div>
        </div>
        <div className="border-t border-neutral-100 py-3 text-center text-[11px] text-neutral-400">
          © {new Date().getFullYear()} {data.school.name}
          {data.school.establishedYear ? ` · Est. ${data.school.establishedYear}` : ""} · Powered by Nyansapo School Cloud
        </div>
      </footer>

      {/* lightbox */}
      {lightbox && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" onClick={() => setLightbox(null)} role="dialog" aria-modal="true" aria-label="Photo viewer">
          <div className="max-h-full max-w-4xl" onClick={(e) => e.stopPropagation()}>
            <img src={lightbox.url} alt={lightbox.caption ?? "photo"} className="max-h-[80vh] w-auto rounded-lg object-contain" />
            {lightbox.caption && <div className="mt-2 text-center text-sm text-neutral-200">{lightbox.caption}</div>}
          </div>
          <button className="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20" onClick={() => setLightbox(null)} aria-label="Close">
            <ChevronRight className="hidden" /> ✕
          </button>
        </div>
      )}
    </div>
  );
}

/* ── Home: hero slider + upcoming events + latest news ── */
function HomePage({ data, accent, go }: { data: SitePayload; accent: string; go: (p: PageId) => void }) {
  const slides = data.slider.filter((s) => s.imageUrl || s.title || s.description);
  const fallback = [
    {
      title: data.school.name,
      description: data.school.motto ?? `Quality education in ${data.school.town}, ${data.school.region}.`,
      imageUrl: null as string | null,
    },
  ];
  const deck = slides.length ? slides : fallback;
  const [idx, setIdx] = useState(0);

  useEffect(() => {
    if (deck.length < 2) return;
    const t = setInterval(() => setIdx((i) => (i + 1) % deck.length), 6000);
    return () => clearInterval(t);
  }, [deck.length]);

  const upcoming = data.events.slice(0, 4);
  const latestNews = data.news.slice(0, 3);

  return (
    <div className="space-y-10 pb-4">
      {/* hero slider */}
      <section className="relative mx-auto max-w-6xl px-4 pt-6">
        <div className="relative overflow-hidden rounded-2xl shadow-lg" style={{ minHeight: 340 }}>
          {deck[idx].imageUrl ? (
            <img src={deck[idx].imageUrl!} alt={deck[idx].title || "hero"} className="absolute inset-0 h-full w-full object-cover" />
          ) : (
            <div className="absolute inset-0" style={{ background: `linear-gradient(135deg, ${accent}, ${accent}CC 60%, #1c1917)` }} />
          )}
          <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent" />
          <div className="relative z-10 flex min-h-[340px] flex-col justify-end p-6 sm:p-10">
            <h1 className="max-w-2xl text-2xl font-extrabold text-white drop-shadow sm:text-4xl">{deck[idx].title || data.school.name}</h1>
            {deck[idx].description && <p className="mt-2 max-w-xl text-sm text-neutral-100 sm:text-base">{deck[idx].description}</p>}
            <div className="mt-4 flex gap-2">
              <button onClick={() => go("about")} className="rounded-full bg-white px-4 py-2 text-sm font-semibold text-neutral-900 shadow hover:bg-neutral-100">Learn about us</button>
              <button onClick={() => go("contact")} className="rounded-full border border-white/60 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">Contact us</button>
            </div>
          </div>
          {deck.length > 1 && (
            <>
              <button aria-label="Previous slide" onClick={() => setIdx((idx - 1 + deck.length) % deck.length)} className="absolute left-3 top-1/2 z-10 -translate-y-1/2 rounded-full bg-black/35 p-2 text-white hover:bg-black/55">
                <ChevronLeft className="size-5" />
              </button>
              <button aria-label="Next slide" onClick={() => setIdx((idx + 1) % deck.length)} className="absolute right-3 top-1/2 z-10 -translate-y-1/2 rounded-full bg-black/35 p-2 text-white hover:bg-black/55">
                <ChevronRight className="size-5" />
              </button>
              <div className="absolute bottom-3 right-4 z-10 flex gap-1.5">
                {deck.map((_, i) => (
                  <button key={i} aria-label={`Slide ${i + 1}`} onClick={() => setIdx(i)} className={cn("h-1.5 rounded-full transition-all", i === idx ? "w-6 bg-white" : "w-1.5 bg-white/50")} />
                ))}
              </div>
            </>
          )}
        </div>
      </section>

      {/* upcoming events */}
      <section className="mx-auto max-w-6xl px-4">
        <SectionHeading icon={<CalendarRange className="size-4" />} title="Upcoming events" accent={accent} onViewAll={data.events.length > 4 ? () => go("events") : undefined} />
        {upcoming.length === 0 ? (
          <p className="rounded-xl border border-dashed bg-white p-6 text-center text-sm text-neutral-500">No upcoming events announced yet.</p>
        ) : (
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {upcoming.map((e) => (
              <div key={e.id} className="rounded-xl border bg-white p-4 shadow-sm">
                <Badge accent={accent}>{CATEGORY_LABEL[e.category] ?? "Event"}</Badge>
                <div className="mt-2 text-sm font-semibold leading-snug">{e.title}</div>
                <div className="mt-1 text-xs text-neutral-500">{day(e.startsAt)}{e.location ? ` · ${e.location}` : ""}</div>
                {e.description && <p className="mt-1.5 line-clamp-2 text-xs text-neutral-500">{e.description}</p>}
              </div>
            ))}
          </div>
        )}
      </section>

      {/* latest news */}
      <section className="mx-auto max-w-6xl px-4">
        <SectionHeading icon={<Newspaper className="size-4" />} title="Latest news" accent={accent} onViewAll={data.news.length > 3 ? () => go("news") : undefined} />
        {latestNews.length === 0 ? (
          <p className="rounded-xl border border-dashed bg-white p-6 text-center text-sm text-neutral-500">No news published yet.</p>
        ) : (
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
            {latestNews.map((n) => (
              <button key={n.id} onClick={() => go("news")} className="overflow-hidden rounded-xl border bg-white text-left shadow-sm transition-shadow hover:shadow-md">
                {n.imageUrl ? (
                  <img src={n.imageUrl} alt={n.title} className="h-36 w-full object-cover" />
                ) : (
                  <div className="flex h-36 w-full items-center justify-center" style={{ backgroundColor: `${accent}14` }}><Newspaper className="size-7" style={{ color: accent }} /></div>
                )}
                <div className="p-4">
                  <div className="text-[11px] uppercase tracking-wide text-neutral-400">{day(n.publishedAt)}</div>
                  <div className="mt-1 text-sm font-semibold leading-snug">{n.title}</div>
                  <p className="mt-1.5 line-clamp-3 text-xs text-neutral-500">{n.body}</p>
                </div>
              </button>
            ))}
          </div>
        )}
      </section>
    </div>
  );
}

/* ── About ── */
function AboutPage({ data, accent }: { data: SitePayload; accent: string }) {
  return (
    <div className="mx-auto max-w-4xl px-4 py-10">
      <PageTitle icon={<Info className="size-5" />} title="About our school" accent={accent} />
      {data.aboutUsImageUrl && (
        <img src={data.aboutUsImageUrl} alt="Our school" className="mb-6 h-64 w-full rounded-2xl object-cover shadow-md" />
      )}
      {data.aboutUs ? (
        <div className="whitespace-pre-wrap rounded-2xl border bg-white p-6 text-sm leading-relaxed text-neutral-700 shadow-sm">{data.aboutUs}</div>
      ) : (
        <div className="rounded-xl border border-dashed bg-white p-6 text-center text-sm text-neutral-500">
          <p className="font-medium text-neutral-700">{data.school.name}</p>
          <p className="mt-1">{data.school.motto ?? "Knowledge · Excellence · Service"}</p>
          <p className="mt-2">A proud member of the Nyansapo school network{data.school.establishedYear ? `, established ${data.school.establishedYear}` : ""}, serving {data.school.town}, {data.school.region}.</p>
        </div>
      )}
    </div>
  );
}

/* ── Events ── */
function EventsPage({ data, accent }: { data: SitePayload; accent: string }) {
  return (
    <div className="mx-auto max-w-4xl px-4 py-10">
      <PageTitle icon={<CalendarRange className="size-5" />} title="Upcoming events" accent={accent} />
      {data.events.length === 0 ? (
        <p className="rounded-xl border border-dashed bg-white p-6 text-center text-sm text-neutral-500">No upcoming events announced yet.</p>
      ) : (
        <div className="space-y-3">
          {data.events.map((e) => (
            <div key={e.id} className="flex gap-4 rounded-xl border bg-white p-4 shadow-sm">
              <div className="flex w-16 shrink-0 flex-col items-center justify-center rounded-lg py-2 text-white" style={{ backgroundColor: accent }}>
                <div className="text-xl font-extrabold leading-none">{new Date(e.startsAt).getDate()}</div>
                <div className="text-[10px] uppercase">{new Date(e.startsAt).toLocaleDateString(undefined, { month: "short" })}</div>
              </div>
              <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-2">
                  <span className="text-sm font-semibold">{e.title}</span>
                  <Badge accent={accent}>{CATEGORY_LABEL[e.category] ?? "Event"}</Badge>
                </div>
                <div className="mt-0.5 text-xs text-neutral-500">{day(e.startsAt)}{e.endsAt && e.endsAt !== e.startsAt ? ` – ${day(e.endsAt)}` : ""}{e.location ? ` · ${e.location}` : ""}</div>
                {e.description && <p className="mt-1.5 text-sm text-neutral-600">{e.description}</p>}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

/* ── News ── */
function NewsPage({ data, accent }: { data: SitePayload; accent: string }) {
  return (
    <div className="mx-auto max-w-4xl px-4 py-10">
      <PageTitle icon={<Newspaper className="size-5" />} title="News &amp; announcements" accent={accent} />
      {data.news.length === 0 ? (
        <p className="rounded-xl border border-dashed bg-white p-6 text-center text-sm text-neutral-500">No news published yet.</p>
      ) : (
        <div className="space-y-5">
          {data.news.map((n) => (
            <article key={n.id} className="overflow-hidden rounded-xl border bg-white shadow-sm">
              {n.imageUrl && (
                <img src={n.imageUrl} alt={n.title} className="h-56 w-full object-cover" />
              )}
              <div className="p-5">
                <div className="text-[11px] uppercase tracking-wide" style={{ color: accent }}>{day(n.publishedAt)}</div>
                <h2 className="mt-1 text-lg font-bold">{n.title}</h2>
                <p className="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-neutral-600">{n.body}</p>
              </div>
            </article>
          ))}
        </div>
      )}
    </div>
  );
}

/* ── Gallery ── */
function GalleryPage({ data, accent, setLightbox }: { data: SitePayload; accent: string; setLightbox: (v: { url: string; caption: string | null } | null) => void }) {
  return (
    <div className="mx-auto max-w-5xl px-4 py-10">
      <PageTitle icon={<ImageIcon className="size-5" />} title="Photo gallery" accent={accent} />
      {data.albums.length === 0 ? (
        <p className="rounded-xl border border-dashed bg-white p-6 text-center text-sm text-neutral-500">No albums published yet.</p>
      ) : (
        <div className="space-y-8">
          {data.albums.map((a) => (
            <div key={a.id}>
              <div className="mb-3 flex items-end justify-between">
                <div>
                  <h3 className="text-base font-bold">{a.title}</h3>
                  {a.description && <p className="text-xs text-neutral-500">{a.description}</p>}
                </div>
                <span className="text-xs text-neutral-400">{a.images.length} photo(s)</span>
              </div>
              <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                {a.images.length === 0 ? (
                  <div className="col-span-full rounded-lg border border-dashed p-4 text-center text-xs text-neutral-400">Album is empty.</div>
                ) : (
                  a.images.map((im) =>
                    im.imageUrl ? (
                      <button key={im.id} className="group relative overflow-hidden rounded-lg" onClick={() => setLightbox({ url: im.imageUrl!, caption: im.caption })} aria-label={`Open photo ${im.caption ?? ""}`}>
                        <img src={im.imageUrl} alt={im.caption ?? a.title} className="h-40 w-full object-cover transition-transform group-hover:scale-105" />
                      </button>
                    ) : null,
                  )
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

/* ── Staff showcase (CI3 teacher.show_on_website parity) ── */
function StaffPage({ data, accent }: { data: SitePayload; accent: string }) {
  const initials = (name: string) => name.split(" ").filter(Boolean).slice(0, 2).map((p) => p[0]!.toUpperCase()).join("");
  return (
    <div className="mx-auto max-w-5xl px-4 py-10">
      <PageTitle icon={<Users className="size-5" />} title="Meet our staff" accent={accent} />
      {data.staff.length === 0 ? (
        <p className="rounded-xl border border-dashed bg-white p-6 text-center text-sm text-neutral-500">Staff profiles coming soon.</p>
      ) : (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {data.staff.map((s) => (
            <div key={s.id} className="flex items-center gap-3 rounded-xl border bg-white p-4 shadow-sm">
              <div className="flex size-12 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white" style={{ backgroundColor: accent }}>
                {initials(s.fullName)}
              </div>
              <div className="min-w-0">
                <div className="truncate text-sm font-semibold">{s.fullName}</div>
                <div className="text-xs text-neutral-500">{ROLE_LABEL[s.role] ?? s.role}{s.title ? ` · ${s.title}` : ""}</div>
                {s.qualification && <div className="truncate text-[11px] text-neutral-400">{s.qualification}</div>}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

/* ── Contact (CI3 send_contact_message parity — posts to the school inbox) ── */
function ContactPage({ data, accent }: { data: SitePayload; accent: string }) {
  const [form, setForm] = useState({ firstName: "", lastName: "", email: "", phone: "", address: "", comment: "" });
  const [state, setState] = useState<"idle" | "sending" | "sent" | "error">("idle");
  const [error, setError] = useState("");

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.firstName.trim() || !form.comment.trim()) {
      setState("error");
      setError("Your name and the message are required.");
      return;
    }
    setState("sending");
    try {
      const res = await fetch(`/api/site/${data.school.code}/contact`, {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify(form),
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(json.error ?? "Could not send the message");
      setState("sent");
    } catch (err: any) {
      setState("error");
      setError(err.message);
    }
  };

  return (
    <div className="mx-auto grid max-w-5xl gap-6 px-4 py-10 lg:grid-cols-[1fr_360px]">
      <div>
        <PageTitle icon={<Mail className="size-5" />} title="Contact us" accent={accent} />
        {state === "sent" ? (
          <div className="flex flex-col items-center gap-3 rounded-xl border bg-white p-8 text-center shadow-sm">
            <CheckCircle2 className="size-10" style={{ color: accent }} />
            <div className="text-lg font-bold">Message sent</div>
            <p className="text-sm text-neutral-500">Thank you — the school office will get back to you shortly.</p>
          </div>
        ) : (
          <form onSubmit={submit} className="space-y-3 rounded-xl border bg-white p-5 shadow-sm">
            {state === "error" && <div className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">{error}</div>}
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <input className="rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2" style={{ ["--tw-ring-color" as any]: `${accent}66` }} placeholder="First name *" value={form.firstName} onChange={(e) => setForm({ ...form, firstName: e.target.value })} />
              <input className="rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2" placeholder="Surname" value={form.lastName} onChange={(e) => setForm({ ...form, lastName: e.target.value })} />
              <input className="rounded-lg border px-3 py-2 text-sm outline-none" type="email" placeholder="E-mail" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
              <input className="rounded-lg border px-3 py-2 text-sm outline-none" type="tel" placeholder="Phone" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
            </div>
            <input className="w-full rounded-lg border px-3 py-2 text-sm outline-none" placeholder="Town / address" value={form.address} onChange={(e) => setForm({ ...form, address: e.target.value })} />
            <textarea className="w-full rounded-lg border px-3 py-2 text-sm outline-none" rows={5} placeholder="Your message * — admissions, fees, visits…" value={form.comment} onChange={(e) => setForm({ ...form, comment: e.target.value })} />
            <button
              type="submit"
              disabled={state === "sending" || !form.firstName.trim() || !form.comment.trim()}
              className="flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold text-white shadow disabled:opacity-50"
              style={{ backgroundColor: accent }}
            >
              {state === "sending" ? <Loader2 className="size-4 animate-spin" /> : <Send className="size-4" />}
              {state === "sending" ? "Sending…" : "Send message"}
            </button>
          </form>
        )}
      </div>
      <aside className="space-y-3">
        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="text-sm font-bold" style={{ color: accent }}>{data.school.name}</div>
          <div className="mt-2 flex items-start gap-2 text-sm text-neutral-600"><MapPin className="mt-0.5 size-4 shrink-0" /> {data.school.town}, {data.school.region}</div>
          {data.contactPhone && <div className="mt-1.5 flex items-center gap-2 text-sm text-neutral-600"><Phone className="size-4" /> {data.contactPhone}</div>}
          {data.contactEmail && <div className="mt-1.5 flex items-center gap-2 text-sm text-neutral-600"><Mail className="size-4" /> {data.contactEmail}</div>}
        </div>
        <div className="rounded-xl border bg-white p-5 shadow-sm">
          <div className="flex items-center gap-2 text-sm font-semibold"><Globe className="size-4" style={{ color: accent }} /> Visit our offices</div>
          <p className="mt-1.5 text-xs leading-relaxed text-neutral-500">
            Prospective parents are welcome during school hours — ask at the front desk for a campus tour.
          </p>
        </div>
      </aside>
    </div>
  );
}

/* ── Terms / Privacy ── */
function StaticPage({ title, body, accent }: { title: string; body: string | null; accent: string }) {
  return (
    <div className="mx-auto max-w-3xl px-4 py-10">
      <PageTitle icon={<Info className="size-5" />} title={title} accent={accent} />
      {body ? (
        <div className="whitespace-pre-wrap rounded-2xl border bg-white p-6 text-sm leading-relaxed text-neutral-700 shadow-sm">{body}</div>
      ) : (
        <p className="rounded-xl border border-dashed bg-white p-6 text-center text-sm text-neutral-500">This page has not been published yet.</p>
      )}
    </div>
  );
}

/* ── shared bits ── */
function Badge({ children, accent }: { children: React.ReactNode; accent: string }) {
  return (
    <span className="inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide" style={{ backgroundColor: `${accent}1A`, color: accent }}>
      {children}
    </span>
  );
}
function SectionHeading({ icon, title, accent, onViewAll }: { icon: React.ReactNode; title: string; accent: string; onViewAll?: () => void }) {
  return (
    <div className="mb-3 flex items-center justify-between">
      <h2 className="flex items-center gap-2 text-lg font-bold"><span style={{ color: accent }}>{icon}</span> {title}</h2>
      {onViewAll && (
        <button onClick={onViewAll} className="text-xs font-semibold underline" style={{ color: accent }}>
          View all →
        </button>
      )}
    </div>
  );
}
function PageTitle({ icon, title, accent }: { icon: React.ReactNode; title: string; accent: string }) {
  return (
    <h1 className="mb-6 flex items-center gap-2 text-2xl font-extrabold">
      <span style={{ color: accent }}>{icon}</span> {title}
    </h1>
  );
}

"use client";
// ── Public Website (wave 54 — CI3 frontend_pages / Frontend_model parity) ──
// The school's public face on the internet: a hero slider (3 slides), events
// (reuses Front Office SchoolEvents audience=ALL), news posts, photo gallery
// albums, the staff showcase (CI3 teacher.show_on_website), about / terms /
// privacy pages, the contact inbox and the publish switch. The rendered
// public site lives at /site/{school.code}.
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard, FormGrid, FormField } from "@/components/ui-helpers";
import { fmtDateShort, fmtDateTime, initials, avatarColor } from "@/lib/format";
import { useToast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  Globe, ExternalLink, Upload, Trash2, Plus, Send, Eye, EyeOff, Inbox,
  Image as ImageIcon, Newspaper, CalendarRange, Users, CheckCheck, Pencil, Layers,
} from "lucide-react";

const OPS = "/ops/website";

type Slide = { title: string; description: string; imageId: string | null };
type SettingRow = {
  id: string; published: boolean; sliderJson: string;
  aboutUs: string | null; aboutUsImageId: string | null;
  terms: string | null; privacy: string | null;
  contactEmail: string | null; contactPhone: string | null;
};
type NewsRow = { id: string; title: string; body: string; imageId: string | null; publishedAt: string };
type AlbumImage = { id: string; imageId: string; caption: string | null };
type AlbumRow = { id: string; title: string; description: string | null; showOnWebsite: boolean; coverId: string | null; images: AlbumImage[] };
type StaffRow = { id: string; fullName: string; role: string; title: string | null; qualification: string | null; showOnWebsite: boolean };
type MessageRow = { id: string; firstName: string; lastName: string | null; email: string | null; phone: string | null; address: string | null; comment: string; handled: boolean; createdAt: string };
type EventRow = { id: string; title: string; category: string; startsAt: string; endsAt: string | null; location: string | null; description: string | null };
type WebsiteData = {
  ok: boolean;
  school: { code: string; name: string; motto: string | null; town: string; region: string };
  publicUrl: string;
  setting: SettingRow | null;
  slider: Slide[];
  news: NewsRow[];
  albums: AlbumRow[];
  staff: StaffRow[];
  messages: MessageRow[];
  events: EventRow[];
  unread: number;
};

/** Client-side image compression (mirrors settings branding — ≤640px PNG). */
function imageToDataUrl(file: File, maxW: number, maxH: number): Promise<string> {
  return new Promise((resolve, reject) => {
    const img = new Image();
    const url = URL.createObjectURL(file);
    img.onload = () => {
      const scale = Math.min(1, maxW / Math.max(1, img.width), maxH / Math.max(1, img.height));
      const canvas = document.createElement("canvas");
      canvas.width = Math.max(1, Math.round(img.width * scale));
      canvas.height = Math.max(1, Math.round(img.height * scale));
      canvas.getContext("2d")!.drawImage(img, 0, 0, canvas.width, canvas.height);
      URL.revokeObjectURL(url);
      resolve(canvas.toDataURL("image/png"));
    };
    img.onerror = () => { URL.revokeObjectURL(url); reject(new Error("Not a readable image")); };
    img.src = url;
  });
}

const mediaUrl = (imageId: string | null | undefined) => (imageId ? `/api/media/${imageId}` : null);
const CATEGORY_LABEL: Record<string, string> = {
  PTA_MEETING: "PTA meeting", OPEN_DAY: "Open day", SPORTS: "Sports", PRIZE_GIVING: "Prize-giving",
  EXAMINATION: "Examination", HOLIDAY: "Holiday", OTHER: "Other",
};

export function Website() {
  const { currentSchoolId, navTarget } = useStore();
  const { toast } = useToast();
  const schoolId = currentSchoolId ?? "";
  const [data, setData] = useState<WebsiteData | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [tab, setTab] = useState("publish");

  useEffect(() => {
    if (navTarget?.tab) setTab(navTarget.tab);
  }, [navTarget]);

  const load = useCallback(async () => {
    if (!schoolId) return;
    setLoading(true);
    try { setData(await apiGet<WebsiteData>(`/api/school/${schoolId}${OPS}`)); }
    catch (e: any) { toast({ title: "Could not load website", description: e.message, variant: "destructive" }); }
    finally { setLoading(false); }
  }, [schoolId, toast]);

  useEffect(() => { load(); }, [load]);

  const act = useCallback(async (body: Record<string, unknown>, successMsg: string) => {
    if (!schoolId) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}${OPS}`, body);
      toast({ title: successMsg });
      await load();
    } catch (e: any) {
      toast({ title: "Action failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }, [schoolId, load, toast]);

  if (loading && !data) return <LoadingBlock label="Loading website manager…" />;
  if (!data) return <EmptyState icon={<Globe />} title="Website unavailable" hint="Try reloading the module." />;

  const published = data.setting?.published === true;
  const shownStaff = data.staff.filter((s) => s.showOnWebsite);
  const shownAlbums = data.albums.filter((a) => a.showOnWebsite);
  const unreadCount = data.unread;

  return (
    <div className="space-y-4">
      <ModuleHeader
        title="Public Website"
        description="The school's face on the internet — hero slider, events, news, gallery, staff showcase and a contact inbox, published at /site/{school code}."
        actions={
          published ? (
            <Badge className="bg-emerald-600 text-white gap-1"><Globe className="size-3" /> Published</Badge>
          ) : (
            <Badge variant="outline" className="text-amber-600 border-amber-300 gap-1"><EyeOff className="size-3" /> Unpublished</Badge>
          )
        }
      />

      {/* ── KPI strip ── */}
      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <StatCard icon={<Layers className="size-4" />} label="Slider slides" value={data.slider.filter((s) => s.imageId || s.title).length || 0} sub="of 3 slots" />
        <StatCard icon={<Newspaper className="size-4" />} label="News posts" value={data.news.length} sub="latest first" />
        <StatCard icon={<ImageIcon className="size-4" />} label="Live albums" value={`${shownAlbums.length}/${data.albums.length}`} sub="shown on site" />
        <StatCard icon={<Inbox className="size-4" />} label="Contact inbox" value={unreadCount} sub={unreadCount ? "awaiting reply" : "all handled"} tone={unreadCount ? "gold" : "default"} />
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex flex-wrap h-auto gap-1">
          <TabsTrigger value="publish">Publish &amp; link</TabsTrigger>
          <TabsTrigger value="slider">Hero slider</TabsTrigger>
          <TabsTrigger value="events">Events ({data.events.length})</TabsTrigger>
          <TabsTrigger value="news">News ({data.news.length})</TabsTrigger>
          <TabsTrigger value="gallery">Gallery ({data.albums.length})</TabsTrigger>
          <TabsTrigger value="pages">About, terms &amp; privacy</TabsTrigger>
          <TabsTrigger value="staff">Staff showcase ({shownStaff.length})</TabsTrigger>
          <TabsTrigger value="inbox" className="relative">
            Inbox {unreadCount ? <span className="ml-1 inline-block size-2 rounded-full bg-amber-500" /> : null}
          </TabsTrigger>
        </TabsList>

        {/* ── PUBLISH ── */}
        <TabsContent value="publish" className="space-y-4">
          <PublishTab data={data} busy={busy} act={act} setTab={setTab} />
        </TabsContent>

        {/* ── SLIDER ── */}
        <TabsContent value="slider">
          <SliderTab slider={data.slider} busy={busy} act={act} />
        </TabsContent>

        {/* ── EVENTS (read-only — managed in Front Office) ── */}
        <TabsContent value="events" className="space-y-4">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><CalendarRange className="size-4" /> Upcoming public events</CardTitle></CardHeader>
            <CardContent>
              <p className="text-xs text-muted-foreground mb-3">
                These come from the Front Office events calendar with audience <span className="font-medium">ALL</span> — edit them there.
              </p>
              {data.events.length === 0 ? (
                <EmptyState icon={<CalendarRange />} title="No upcoming public events" hint="Add an ALL-audience event in Front Office → Events." />
              ) : (
                <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                  {data.events.map((e) => (
                    <div key={e.id} className="rounded-lg border p-3">
                      <div className="flex items-start justify-between gap-2">
                        <div className="font-medium text-sm">{e.title}</div>
                        <Badge variant="outline" className="text-[10px]">{CATEGORY_LABEL[e.category] ?? e.category}</Badge>
                      </div>
                      <div className="text-xs text-muted-foreground mt-1">
                        {fmtDateShort(e.startsAt)}{e.endsAt ? ` – ${fmtDateShort(e.endsAt)}` : ""}{e.location ? ` · ${e.location}` : ""}
                      </div>
                      {e.description && <p className="text-xs mt-1 line-clamp-2 text-muted-foreground">{e.description}</p>}
                    </div>
                  ))}
                </div>
              )}
              <Button variant="outline" size="sm" className="mt-3" onClick={() => useStore.getState().setModule("frontoffice", { tab: "events" })}>
                Manage events in Front Office
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── NEWS ── */}
        <TabsContent value="news">
          <NewsTab news={data.news} busy={busy} act={act} />
        </TabsContent>

        {/* ── GALLERY ── */}
        <TabsContent value="gallery">
          <GalleryTab albums={data.albums} busy={busy} act={act} />
        </TabsContent>

        {/* ── PAGES ── */}
        <TabsContent value="pages">
          <PagesTab setting={data.setting} busy={busy} act={act} />
        </TabsContent>

        {/* ── STAFF SHOWCASE ── */}
        <TabsContent value="staff">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Users className="size-4" /> Staff shown on the website</CardTitle></CardHeader>
            <CardContent>
              <p className="text-xs text-muted-foreground mb-3">Toggle which teachers &amp; staff appear on the public staff page (CI3 teacher.show_on_website parity).</p>
              {data.staff.length === 0 ? (
                <EmptyState icon={<Users />} title="No active staff" hint="Add staff in HR & Payroll first." />
              ) : (
                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                  {data.staff.map((s) => (
                    <div key={s.id} className={cn("flex items-center gap-3 rounded-lg border p-3", s.showOnWebsite && "border-emerald-300 bg-emerald-50/50 dark:bg-emerald-950/20")}>
                      <div className={cn("flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white", avatarColor(s.fullName))}>
                        {initials(s.fullName)}
                      </div>
                      <div className="min-w-0 flex-1">
                        <div className="truncate text-sm font-medium">{s.fullName}</div>
                        <div className="truncate text-xs text-muted-foreground">{s.role}{s.qualification ? ` · ${s.qualification}` : ""}</div>
                      </div>
                      <Switch checked={s.showOnWebsite} disabled={busy} onCheckedChange={() => act({ action: "staff-site.toggle", staffId: s.id }, s.showOnWebsite ? "Hidden from website" : "Now shown on website")} />
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── CONTACT INBOX ── */}
        <TabsContent value="inbox">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Inbox className="size-4" /> Contact form inbox</CardTitle></CardHeader>
            <CardContent>
              {data.messages.length === 0 ? (
                <EmptyState icon={<Inbox />} title="No messages yet" hint="Messages from the website contact form land here." />
              ) : (
                <div className="max-h-96 space-y-2 overflow-y-auto pr-1 [scrollbar-width:thin]">
                  {data.messages.map((m) => (
                    <div key={m.id} className={cn("rounded-lg border p-3", m.handled && "opacity-60")}>
                      <div className="flex items-start justify-between gap-2">
                        <div>
                          <div className="text-sm font-medium">{m.firstName} {m.lastName ?? ""}</div>
                          <div className="text-xs text-muted-foreground">{fmtDateTime(m.createdAt)}{m.phone ? ` · ${m.phone}` : ""}{m.email ? ` · ${m.email}` : ""}</div>
                        </div>
                        <div className="flex shrink-0 gap-1">
                          <Button size="sm" variant={m.handled ? "outline" : "default"} className="h-8" onClick={() => act({ action: "contact.handled", messageId: m.id }, m.handled ? "Marked unhandled" : "Marked handled")}>
                            <CheckCheck className="size-3.5" /> {m.handled ? "Reopen" : "Handled"}
                          </Button>
                          <Button size="sm" variant="ghost" className="h-8 text-red-600" onClick={() => act({ action: "contact.delete", messageId: m.id }, "Message deleted")}>
                            <Trash2 className="size-3.5" />
                          </Button>
                        </div>
                      </div>
                      {m.address && <div className="mt-1 text-xs text-muted-foreground">Address: {m.address}</div>}
                      <p className="mt-1.5 whitespace-pre-wrap text-sm">{m.comment}</p>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}

/* ── Publish & link ── */
function PublishTab({ data, busy, act, setTab }: { data: WebsiteData; busy: boolean; act: (b: Record<string, unknown>, m: string) => Promise<void>; setTab: (t: string) => void }) {
  const { toast } = useToast();
  const published = data.setting?.published === true;
  const url = `${typeof window !== "undefined" ? window.location.origin : ""}${data.publicUrl}`;
  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base">Publish switch</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center justify-between gap-4 rounded-lg border p-4">
            <div>
              <div className="text-sm font-medium">{published ? "Website is live" : "Website is hidden"}</div>
              <p className="text-xs text-muted-foreground mt-0.5">
                {published
                  ? "Anyone with the link can visit the public site. Unpublish to take it offline without losing content."
                  : "Content is saved but the public page returns “not published”. Flip the switch when it is ready."}
              </p>
            </div>
            <Switch checked={published} disabled={busy} onCheckedChange={(v) => act({ action: "settings.save", published: v }, v ? "Website published" : "Website unpublished")} />
          </div>
          <div className="rounded-lg border p-4">
            <div className="text-sm font-medium">Public address</div>
            <p className="mt-1 text-xs text-muted-foreground">Share this link with parents &amp; the community (opens in a new tab):</p>
            <div className="mt-2 flex items-center gap-2">
              <code className="min-w-0 flex-1 truncate rounded bg-muted px-2 py-1.5 text-xs">{url}</code>
              <Button size="sm" variant="outline" className="gap-1" onClick={() => { navigator.clipboard?.writeText(url); toast({ title: "Link copied", description: url }); }}>
                Copy
              </Button>
              <Button size="sm" variant="outline" className="gap-1" onClick={() => window.open(data.publicUrl, "_blank")}>
                <ExternalLink className="size-3.5" /> Open
              </Button>
            </div>
          </div>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <QuickStart icon={<Layers />} label="Hero slider" hint="3 slides" onClick={() => setTab("slider")} />
            <QuickStart icon={<Newspaper />} label="News" hint={`${data.news.length} posts`} onClick={() => setTab("news")} />
            <QuickStart icon={<ImageIcon />} label="Gallery" hint={`${data.albums.length} albums`} onClick={() => setTab("gallery")} />
            <QuickStart icon={<Pencil />} label="Pages" hint="About / terms" onClick={() => setTab("pages")} />
            <QuickStart icon={<Users />} label="Staff" hint={`${data.staff.filter((s) => s.showOnWebsite).length} shown`} onClick={() => setTab("staff")} />
            <QuickStart icon={<Inbox />} label="Inbox" hint={`${data.unread} new`} onClick={() => setTab("inbox")} />
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
function QuickStart({ icon, label, hint, onClick }: { icon: React.ReactNode; label: string; hint: string; onClick: () => void }) {
  return (
    <button onClick={onClick} className="flex items-center gap-3 rounded-lg border p-3 text-left transition-colors hover:bg-muted/50">
      <div className="flex size-9 items-center justify-center rounded-full bg-primary/10 text-primary">{icon}</div>
      <div className="min-w-0">
        <div className="text-sm font-medium">{label}</div>
        <div className="text-xs text-muted-foreground">{hint}</div>
      </div>
    </button>
  );
}

/* ── Hero slider (CI3 slider_images JSON parity — 3 slots) ── */
function seedSlides(slider: Slide[]): Slide[] {
  const s = [...slider];
  while (s.length < 3) s.push({ title: "", description: "", imageId: null });
  return s.slice(0, 3);
}
function SliderTab({ slider, busy, act }: { slider: Slide[]; busy: boolean; act: (b: Record<string, unknown>, m: string) => Promise<void> }) {
  const { toast } = useToast();
  const [slides, setSlides] = useState<Slide[]>(() => seedSlides(slider));
  const [newPics, setNewPics] = useState<(string | null)[]>(() => [null, null, null]);

  // adjust-to-props pattern (no effect): re-seed the draft when the saved slider changes
  const signature = JSON.stringify(slider);
  const [seen, setSeen] = useState(signature);
  if (seen !== signature) {
    setSeen(signature);
    setSlides(seedSlides(slider));
    setNewPics([null, null, null]);
  }

  const pick = async (i: number, f: File | undefined) => {
    if (!f) return;
    try { const d = await imageToDataUrl(f, 960, 540); setNewPics((p) => p.map((x, j) => (j === i ? d : x))); }
    catch (e: any) { toast({ title: "Skipped", description: e.message, variant: "destructive" }); }
  };

  return (
    <Card>
      <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Layers className="size-4" /> Hero slider — 3 slides</CardTitle></CardHeader>
      <CardContent className="space-y-4">
        {slides.map((s, i) => (
          <div key={i} className="rounded-lg border p-4">
            <div className="grid grid-cols-1 gap-4 md:grid-cols-[220px_1fr]">
              <div>
                {newPics[i] ? (
                  <img src={newPics[i]!} alt={`Slide ${i + 1} preview`} className="h-32 w-full rounded-md border object-cover" />
                ) : s.imageId ? (
                  <img src={mediaUrl(s.imageId)!} alt={`Slide ${i + 1}`} className="h-32 w-full rounded-md border object-cover" />
                ) : (
                  <label className="flex h-32 w-full cursor-pointer flex-col items-center justify-center gap-1 rounded-md border border-dashed text-xs text-muted-foreground hover:bg-muted/50">
                    <Upload className="size-5" />
                    Upload slide {i + 1}
                    <input type="file" accept="image/*" className="sr-only" onChange={(e) => pick(i, e.target.files?.[0])} />
                  </label>
                )}
                <div className="mt-1.5 flex gap-1">
                  <label className="cursor-pointer text-xs text-primary underline">
                    {s.imageId || newPics[i] ? "Replace" : "Upload"}
                    <input type="file" accept="image/*" className="sr-only" onChange={(e) => pick(i, e.target.files?.[0])} />
                  </label>
                  {(s.imageId || newPics[i]) && (
                    <button className="text-xs text-muted-foreground underline" onClick={() => { setNewPics((p) => p.map((x, j) => (j === i ? null : x))); setSlides((p) => p.map((x, j) => (j === i ? { ...x, imageId: null } : x))); }}>
                      Remove
                    </button>
                  )}
                </div>
              </div>
              <div className="space-y-2">
                <Input placeholder={`Slide ${i + 1} title`} value={s.title} onChange={(e) => setSlides((p) => p.map((x, j) => (j === i ? { ...x, title: e.target.value } : x)))} />
                <Textarea rows={2} placeholder="Short caption shown over the image" value={s.description} onChange={(e) => setSlides((p) => p.map((x, j) => (j === i ? { ...x, description: e.target.value } : x)))} />
              </div>
            </div>
          </div>
        ))}
        <Button
          disabled={busy}
          onClick={() => {
            const payload = slides.map((s, i) => ({ title: s.title, description: s.description, imageId: newPics[i] ? undefined : s.imageId, imageDataUrl: newPics[i] ?? undefined }));
            act({ action: "settings.save", slider: payload }, "Slider saved");
          }}
          className="gap-1"
        >
          <Upload className="size-4" /> {busy ? "Saving…" : "Save slider"}
        </Button>
      </CardContent>
    </Card>
  );
}

/* ── News (CI3 frontend_news CRUD parity) ── */
function NewsTab({ news, busy, act }: { news: NewsRow[]; busy: boolean; act: (b: Record<string, unknown>, m: string) => Promise<void> }) {
  const { toast } = useToast();
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<NewsRow | null>(null);
  const [form, setForm] = useState({ title: "", body: "", publishedAt: new Date().toISOString().slice(0, 10) });
  const [pic, setPic] = useState<string | null>(null);

  const startAdd = () => { setEditing(null); setForm({ title: "", body: "", publishedAt: new Date().toISOString().slice(0, 10) }); setPic(null); setOpen(true); };
  const startEdit = (n: NewsRow) => { setEditing(n); setForm({ title: n.title, body: n.body, publishedAt: n.publishedAt.slice(0, 10) }); setPic(null); setOpen(true); };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between pb-3">
        <CardTitle className="text-base flex items-center gap-2"><Newspaper className="size-4" /> News posts</CardTitle>
        <Button size="sm" className="gap-1" onClick={startAdd}><Plus className="size-4" /> Add news</Button>
      </CardHeader>
      <CardContent>
        {news.length === 0 ? (
          <EmptyState icon={<Newspaper />} title="No news yet" hint="Publish the first story — open days, exam success, PTA news." />
        ) : (
          <div className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            {news.map((n) => (
              <div key={n.id} className="overflow-hidden rounded-lg border">
                {n.imageId ? (
                  <img src={mediaUrl(n.imageId)!} alt={n.title} className="h-32 w-full object-cover" />
                ) : (
                  <div className="flex h-32 w-full items-center justify-center bg-muted text-muted-foreground"><Newspaper className="size-6" /></div>
                )}
                <div className="p-3">
                  <div className="text-sm font-medium leading-snug">{n.title}</div>
                  <div className="mt-0.5 text-xs text-muted-foreground">{fmtDateShort(n.publishedAt)}</div>
                  <p className="mt-1.5 line-clamp-3 text-xs text-muted-foreground">{n.body}</p>
                  <div className="mt-2 flex gap-1">
                    <Button size="sm" variant="outline" className="h-7 gap-1" onClick={() => startEdit(n)}><Pencil className="size-3" /> Edit</Button>
                    <Button size="sm" variant="ghost" className="h-7 text-red-600" onClick={() => act({ action: "news.delete", newsId: n.id }, "News deleted")}><Trash2 className="size-3" /></Button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}

        <Dialog open={open} onOpenChange={setOpen}>
          <DialogContent className="max-w-lg">
            <DialogHeader>
              <DialogTitle>{editing ? "Edit news post" : "Publish news"}</DialogTitle>
              <DialogDescription>Shown on the website news page, latest first.</DialogDescription>
            </DialogHeader>
            <div className="space-y-3">
              <FormField label="Title" required>
                <Input value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} placeholder="Inter-schools quiz victory" />
              </FormField>
              <FormField label="Body" required>
                <Textarea rows={5} value={form.body} onChange={(e) => setForm({ ...form, body: e.target.value })} placeholder="Write the story…" />
              </FormField>
              <FormGrid cols={2}>
                <FormField label="Date">
                  <Input type="date" value={form.publishedAt} onChange={(e) => setForm({ ...form, publishedAt: e.target.value })} />
                </FormField>
                <FormField label="Photo" hint="≤640px, auto-resized">
                  <div className="flex items-center gap-2">
                    {pic ? (
                      <img src={pic} alt="preview" className="h-10 w-16 rounded border object-cover" />
                    ) : null}
                    <label className="cursor-pointer text-xs text-primary underline">
                      {pic ? "Replace" : (editing?.imageId ? "Replace image" : "Upload")}
                      <input type="file" accept="image/*" className="sr-only" onChange={async (e) => { const f = e.target.files?.[0]; if (!f) return; try { setPic(await imageToDataUrl(f, 640, 480)); } catch (err: any) { toast({ title: "Skipped", description: err.message, variant: "destructive" }); } }} />
                    </label>
                  </div>
                </FormField>
              </FormGrid>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
              <Button
                disabled={busy || !form.title.trim() || !form.body.trim()}
                onClick={async () => {
                  await act(
                    editing
                      ? { action: "news.edit", newsId: editing.id, ...form, imageDataUrl: pic ?? undefined, removeImage: editing.imageId && !pic ? false : undefined }
                      : { action: "news.add", ...form, imageDataUrl: pic ?? undefined },
                    editing ? "News updated" : "News published",
                  );
                  setOpen(false);
                }}
                className="gap-1"
              >
                <Send className="size-4" /> {editing ? "Save" : "Publish"}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </CardContent>
    </Card>
  );
}

/* ── Gallery (CI3 frontend_gallery + gallery_image parity) ── */
function GalleryTab({ albums, busy, act }: { albums: AlbumRow[]; busy: boolean; act: (b: Record<string, unknown>, m: string) => Promise<void> }) {
  const { toast } = useToast();
  const [albumOpen, setAlbumOpen] = useState(false);
  const [editing, setEditing] = useState<AlbumRow | null>(null);
  const [form, setForm] = useState({ title: "", description: "" });
  const [cover, setCover] = useState<string | null>(null);
  const [photosOpen, setPhotosOpen] = useState<AlbumRow | null>(null);
  const [pics, setPics] = useState<string[]>([]);

  const startAdd = () => { setEditing(null); setForm({ title: "", description: "" }); setCover(null); setAlbumOpen(true); };
  const startEdit = (a: AlbumRow) => { setEditing(a); setForm({ title: a.title, description: a.description ?? "" }); setCover(null); setAlbumOpen(true); };

  const pickPhotos = async (files: FileList | null) => {
    if (!files?.length) return;
    const out: string[] = [];
    for (const f of Array.from(files).slice(0, 10)) {
      try { out.push(await imageToDataUrl(f, 800, 600)); } catch { /* skip unreadable */ }
    }
    setPics(out);
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between pb-3">
        <CardTitle className="text-base flex items-center gap-2"><ImageIcon className="size-4" /> Photo albums</CardTitle>
        <Button size="sm" className="gap-1" onClick={startAdd}><Plus className="size-4" /> New album</Button>
      </CardHeader>
      <CardContent>
        {albums.length === 0 ? (
          <EmptyState icon={<ImageIcon />} title="No albums yet" hint="Create an album — sports day, science fair, graduation." />
        ) : (
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            {albums.map((a) => (
              <div key={a.id} className={cn("overflow-hidden rounded-lg border", !a.showOnWebsite && "opacity-70")}>
                <div className="relative">
                  {a.coverId ? (
                    <img src={mediaUrl(a.coverId)!} alt={a.title} className="h-36 w-full object-cover" />
                  ) : (
                    <div className="flex h-36 w-full items-center justify-center bg-muted text-muted-foreground"><ImageIcon className="size-6" /></div>
                  )}
                  <Badge className={cn("absolute right-2 top-2", a.showOnWebsite ? "bg-emerald-600 text-white" : "bg-neutral-700 text-white")}>
                    {a.showOnWebsite ? "Shown" : "Hidden"}
                  </Badge>
                </div>
                <div className="p-3">
                  <div className="text-sm font-medium">{a.title}</div>
                  {a.description && <p className="mt-0.5 line-clamp-2 text-xs text-muted-foreground">{a.description}</p>}
                  <div className="mt-1 text-xs text-muted-foreground">{a.images.length} photo(s)</div>
                  <div className="mt-2 flex flex-wrap gap-1">
                    <Button size="sm" variant="outline" className="h-7 gap-1" onClick={() => { setPhotosOpen(a); setPics([]); }}><Plus className="size-3" /> Photos</Button>
                    <Button size="sm" variant="outline" className="h-7 gap-1" onClick={() => startEdit(a)}><Pencil className="size-3" /> Edit</Button>
                    <Button size="sm" variant="outline" className="h-7 gap-1" onClick={() => act({ action: "album.toggle", albumId: a.id }, a.showOnWebsite ? "Album hidden" : "Album shown")}>
                      {a.showOnWebsite ? <EyeOff className="size-3" /> : <Eye className="size-3" />}
                    </Button>
                    <Button size="sm" variant="ghost" className="h-7 text-red-600" onClick={() => act({ action: "album.delete", albumId: a.id }, "Album deleted")}><Trash2 className="size-3" /></Button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* album editor */}
        <Dialog open={albumOpen} onOpenChange={setAlbumOpen}>
          <DialogContent className="max-w-md">
            <DialogHeader>
              <DialogTitle>{editing ? "Edit album" : "New album"}</DialogTitle>
              <DialogDescription>Albums appear on the website gallery page.</DialogDescription>
            </DialogHeader>
            <div className="space-y-3">
              <FormField label="Title" required>
                <Input value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} placeholder="Sports day 2026" />
              </FormField>
              <FormField label="Description">
                <Textarea rows={3} value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
              </FormField>
              <FormField label="Cover photo" hint={editing?.coverId ? "Leave empty to keep the current cover" : "Optional — first uploaded photo becomes the cover"}>
                <div className="flex items-center gap-2">
                  {cover ? (
                    <img src={cover} alt="cover preview" className="h-10 w-16 rounded border object-cover" />
                  ) : null}
                  <label className="cursor-pointer text-xs text-primary underline">
                    {cover ? "Replace" : (editing?.coverId ? "Replace cover" : "Upload cover")}
                    <input type="file" accept="image/*" className="sr-only" onChange={async (e) => { const f = e.target.files?.[0]; if (!f) return; try { setCover(await imageToDataUrl(f, 800, 600)); } catch (err: any) { toast({ title: "Skipped", description: err.message, variant: "destructive" }); } }} />
                  </label>
                </div>
              </FormField>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setAlbumOpen(false)}>Cancel</Button>
              <Button
                disabled={busy || !form.title.trim()}
                onClick={async () => {
                  await act(
                    editing
                      ? { action: "album.edit", albumId: editing.id, ...form, coverDataUrl: cover ?? undefined }
                      : { action: "album.add", ...form, coverDataUrl: cover ?? undefined },
                    editing ? "Album updated" : "Album created",
                  );
                  setAlbumOpen(false);
                }}
              >
                {editing ? "Save" : "Create"}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        {/* photo manager */}
        <Dialog open={!!photosOpen} onOpenChange={(v) => !v && setPhotosOpen(null)}>
          <DialogContent className="max-w-2xl">
            <DialogHeader>
              <DialogTitle>Album photos — {photosOpen?.title}</DialogTitle>
              <DialogDescription>Upload up to 10 photos at a time; remove any photo from the grid.</DialogDescription>
            </DialogHeader>
            <div className="space-y-4">
              {photosOpen && photosOpen.images.length > 0 && (
                <div className="grid max-h-64 grid-cols-3 gap-2 overflow-y-auto rounded-lg border p-2 sm:grid-cols-4">
                  {photosOpen.images.map((im) => (
                    <div key={im.id} className="group relative">
                      <img src={mediaUrl(im.imageId)!} alt={im.caption ?? "photo"} className="h-20 w-full rounded border object-cover" />
                      <button
                        className="absolute right-1 top-1 hidden rounded bg-red-600 p-1 text-white group-hover:block"
                        onClick={() => act({ action: "album-image.delete", imageRowId: im.id }, "Photo removed")}
                        aria-label="Remove photo"
                      >
                        <Trash2 className="size-3" />
                      </button>
                    </div>
                  ))}
                </div>
              )}
              <label className="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border border-dashed p-6 text-sm text-muted-foreground hover:bg-muted/50">
                <Upload className="size-5" />
                {pics.length ? `${pics.length} photo(s) ready — save to upload` : "Select photos (up to 10)"}
                <input type="file" accept="image/*" multiple className="sr-only" onChange={(e) => pickPhotos(e.target.files)} />
              </label>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setPhotosOpen(null)}>Done</Button>
              <Button
                disabled={busy || !pics.length}
                onClick={async () => {
                  await act({ action: "album-image.add", albumId: photosOpen!.id, images: pics }, "Photos added");
                  setPics([]);
                  setPhotosOpen(null);
                }}
              >
                Upload {pics.length || ""}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </CardContent>
    </Card>
  );
}

/* ── Pages (CI3 about_us / terms / privacy parity) ── */
function PagesTab({ setting, busy, act }: { setting: SettingRow | null; busy: boolean; act: (b: Record<string, unknown>, m: string) => Promise<void> }) {
  const { toast } = useToast();
  const [aboutUs, setAboutUs] = useState(setting?.aboutUs ?? "");
  const [terms, setTerms] = useState(setting?.terms ?? "");
  const [privacy, setPrivacy] = useState(setting?.privacy ?? "");
  const [contactEmail, setContactEmail] = useState(setting?.contactEmail ?? "");
  const [contactPhone, setContactPhone] = useState(setting?.contactPhone ?? "");
  const [aboutPic, setAboutPic] = useState<string | null>(null);

  // adjust-to-props pattern (no effect): re-seed the form when the saved setting changes
  const signature = [setting?.aboutUs, setting?.terms, setting?.privacy, setting?.contactEmail, setting?.contactPhone, setting?.aboutUsImageId].join("\u0000");
  const [seen, setSeen] = useState(signature);
  if (seen !== signature) {
    setSeen(signature);
    setAboutUs(setting?.aboutUs ?? "");
    setTerms(setting?.terms ?? "");
    setPrivacy(setting?.privacy ?? "");
    setContactEmail(setting?.contactEmail ?? "");
    setContactPhone(setting?.contactPhone ?? "");
    setAboutPic(null);
  }

  return (
    <Card>
      <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Pencil className="size-4" /> Static pages &amp; contact details</CardTitle></CardHeader>
      <CardContent className="space-y-4">
        <FormGrid cols={2}>
          <FormField label="Contact e-mail" hint="Where the website contact form is routed">
            <Input type="email" value={contactEmail} onChange={(e) => setContactEmail(e.target.value)} placeholder="info@school.gh" />
          </FormField>
          <FormField label="Contact phone">
            <Input value={contactPhone} onChange={(e) => setContactPhone(e.target.value)} placeholder="030 000 0000" />
          </FormField>
        </FormGrid>
        <FormField label="About us" hint="Tell the school's story — history, mission, values.">
          <Textarea rows={6} value={aboutUs} onChange={(e) => setAboutUs(e.target.value)} placeholder="Founded in …, the school has grown to …" />
        </FormField>
        <div className="flex items-center gap-3">
          {setting?.aboutUsImageId && !aboutPic ? (
            <img src={mediaUrl(setting.aboutUsImageId)!} alt="About us" className="h-16 w-28 rounded border object-cover" />
          ) : aboutPic ? (
            <img src={aboutPic} alt="About us preview" className="h-16 w-28 rounded border object-cover" />
          ) : null}
          <label className="cursor-pointer text-xs text-primary underline">
            {setting?.aboutUsImageId || aboutPic ? "Replace image" : "Upload image"}
            <input type="file" accept="image/*" className="sr-only" onChange={async (e) => { const f = e.target.files?.[0]; if (!f) return; try { setAboutPic(await imageToDataUrl(f, 800, 500)); } catch (err: any) { toast({ title: "Skipped", description: err.message, variant: "destructive" }); } }} />
          </label>
        </div>
        <FormGrid cols={2}>
          <FormField label="Terms &amp; conditions">
            <Textarea rows={5} value={terms} onChange={(e) => setTerms(e.target.value)} />
          </FormField>
          <FormField label="Privacy policy">
            <Textarea rows={5} value={privacy} onChange={(e) => setPrivacy(e.target.value)} />
          </FormField>
        </FormGrid>
        <Button
          disabled={busy}
          onClick={() => act({ action: "settings.save", aboutUs, terms, privacy, contactEmail, contactPhone, aboutUsImage: aboutPic ?? undefined }, "Pages saved")}
        >
          {busy ? "Saving…" : "Save pages"}
        </Button>
      </CardContent>
    </Card>
  );
}

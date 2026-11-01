"use client";
import { useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { ModuleHeader, LoadingBlock, StatusBadge, StatCard, EmptyState } from "@/components/ui-helpers";
import { SCALES_TABLE } from "@/lib/grading-scale";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import { Settings as SettingsIcon, GraduationCap, BookOpen, Users, CalendarRange, Plus, Save, MessageSquareQuote, Sparkles, Trash2, Award, Pencil } from "lucide-react";
import { Textarea } from "@/components/ui/textarea";

export function Settings() {
  const { currentSchoolId, navTarget } = useStore();
  const [data, setData] = useState<any | null>(null);
  const [busy, setBusy] = useState(false);
  const [clsOpen, setClsOpen] = useState(false);
  const [tab, setTab] = useState("profile");
  const [form, setForm] = useState<any>({ motto: "", phone: "", email: "", curriculum: "GES", nasiaStatus: "", gnacopsStatus: "" });

  /* iEduPro nested nav: preselect the WAEC Grading tab on deep link */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab === "waec") setTab("waec");
  }

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<any>(`/api/school/${currentSchoolId}/settings`).then((d) => {
      setData(d);
      setForm({ motto: d.school.motto ?? "", phone: d.school.phone ?? "", email: d.school.email ?? "", curriculum: d.school.curriculum, nasiaStatus: d.school.nasiaStatus, gnacopsStatus: d.school.gnacopsStatus });
    }).catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(load, [currentSchoolId]);

  if (!data) return <LoadingBlock label="Loading settings…" />;
  const s = data.school;

  async function saveSchool() {
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/settings`, { action: "update-school", ...form });
      toast({ title: "School profile updated", description: form.curriculum !== s.curriculum ? `Curriculum switched to ${form.curriculum}. New grade bands and subjects now apply across the app.` : undefined });
      load();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  return (
    <div>
      <ModuleHeader title="School Settings" description="Profile, curriculum pathway, classes, subjects and the academic calendar." />
      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex-wrap">
          <TabsTrigger value="profile">Profile & curriculum</TabsTrigger>
          <TabsTrigger value="classes">Classes ({data.classrooms.length})</TabsTrigger>
          <TabsTrigger value="subjects">Subjects ({data.subjects.length})</TabsTrigger>
          <TabsTrigger value="calendar">Calendar</TabsTrigger>
          <TabsTrigger value="staff">Staff ({data.staff.length})</TabsTrigger>
          <TabsTrigger value="grading">Grading scale</TabsTrigger>
          <TabsTrigger value="waec" className="gap-1.5"><Award className="size-4" /> WAEC grading</TabsTrigger>
          <TabsTrigger value="reportlib">Report library ({(data.remarkTemplates?.length ?? 0) + (data.conductItems?.length ?? 0)})</TabsTrigger>
        </TabsList>

        <TabsContent value="profile">
          <div className="grid lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><SettingsIcon className="size-4" /> School profile</CardTitle></CardHeader>
              <CardContent className="space-y-3">
                <div className="grid grid-cols-2 gap-3 text-sm">
                  <div className="rounded-lg border p-3"><div className="text-xs text-muted-foreground">School code</div><div className="font-mono font-semibold">{s.code}</div></div>
                  <div className="rounded-lg border p-3"><div className="text-xs text-muted-foreground">NaSIA registration</div><div className="font-semibold">{s.nasiaRegNo ?? "—"}</div></div>
                  <div className="rounded-lg border p-3"><div className="text-xs text-muted-foreground">Location</div><div className="font-semibold">{s.town}, {s.district}, {s.region}</div></div>
                  <div className="rounded-lg border p-3"><div className="text-xs text-muted-foreground">Zone</div><div className="font-semibold">{s.associationNode?.name}</div></div>
                </div>
                <div className="space-y-1.5"><Label>Motto</Label><Input value={form.motto} onChange={(e) => setForm({ ...form, motto: e.target.value })} /></div>
                <div className="grid grid-cols-2 gap-3">
                  <div className="space-y-1.5"><Label>Phone</Label><Input value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} /></div>
                  <div className="space-y-1.5"><Label>Email</Label><Input value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} /></div>
                </div>
                <div className="grid grid-cols-2 gap-3">
                  <div className="space-y-1.5">
                    <Label>NaSIA status</Label>
                    <Select value={form.nasiaStatus} onValueChange={(v) => setForm({ ...form, nasiaStatus: v })}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent><SelectItem value="LICENSED">Licensed</SelectItem><SelectItem value="PROVISIONAL">Provisional</SelectItem><SelectItem value="PENDING">Pending</SelectItem><SelectItem value="EXPIRED">Expired</SelectItem></SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-1.5">
                    <Label>GNACOPS membership</Label>
                    <Select value={form.gnacopsStatus} onValueChange={(v) => setForm({ ...form, gnacopsStatus: v })}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent><SelectItem value="VERIFIED">Verified</SelectItem><SelectItem value="PENDING">Pending</SelectItem><SelectItem value="LAPSED">Lapsed</SelectItem></SelectContent>
                    </Select>
                  </div>
                </div>
                <Button className="gap-1.5" onClick={saveSchool} disabled={busy}><Save className="size-4" /> {busy ? "Saving…" : "Save profile"}</Button>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><GraduationCap className="size-4" /> Curriculum pathway</CardTitle></CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-1.5">
                  <Label>Curriculum</Label>
                  <Select value={form.curriculum} onValueChange={(v) => setForm({ ...form, curriculum: v })}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="GES">GES — NaCCA Standards-Based & Common Core (BECE)</SelectItem>
                      <SelectItem value="CAIE">Cambridge — CAIE (Primary → IGCSE → A-Level)</SelectItem>
                      <SelectItem value="IB">IB World School — PYP / MYP / Diploma</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className={`rounded-xl border p-4 ${form.curriculum === s.curriculum ? "" : "border-amber-400 bg-amber-50/50 dark:bg-amber-950/20"}`}>
                  <div className="font-medium text-sm">{SCALES_TABLE[form.curriculum]?.name}</div>
                  <p className="text-xs text-muted-foreground mt-1">{SCALES_TABLE[form.curriculum]?.blurb}</p>
                  <div className="mt-3 space-y-1">
                    {SCALES_TABLE[form.curriculum]?.bands.map((b: any) => (
                      <div key={b.grade} className="flex justify-between text-xs">
                        <span><b>{b.grade}</b> · {b.label}</span>
                        <span className="text-muted-foreground">≥ {b.min}%</span>
                      </div>
                    ))}
                  </div>
                  {form.curriculum !== s.curriculum && <div className="text-xs text-amber-700 dark:text-amber-400 mt-3 font-medium">Switching re-labels class stages and applies the new grading scale to generated report cards.</div>}
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="classes">
          <Card>
            <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
              <CardTitle className="text-base">Classes & streams</CardTitle>
              <Button size="sm" className="gap-1.5" onClick={() => setClsOpen(true)}><Plus className="size-4" /> Add class</Button>
            </CardHeader>
            <CardContent className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
              {data.classrooms.map((c: any) => (
                <div key={c.id} className="rounded-lg border p-3">
                  <div className="font-medium text-sm">{c.name}</div>
                  <div className="text-xs text-muted-foreground">{c.stage} · capacity {c.capacity}</div>
                  <Badge variant="secondary" className="mt-2">{c.grade}</Badge>
                </div>
              ))}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="subjects">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><BookOpen className="size-4" /> Subjects offered ({s.curriculum} pathway)</CardTitle></CardHeader>
            <CardContent className="flex flex-wrap gap-2">
              {data.subjects.map((sub: any) => (
                <Badge key={sub.id} variant="secondary" className="py-1.5 text-xs">{sub.name}</Badge>
              ))}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="calendar">
          <div className="space-y-4">
            {data.years.map((y: any) => (
              <Card key={y.id}>
                <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
                  <CardTitle className="text-base flex items-center gap-2"><CalendarRange className="size-4" /> {y.name}</CardTitle>
                  {y.isCurrent && <Badge>Current year</Badge>}
                </CardHeader>
                <CardContent className="grid sm:grid-cols-3 gap-3">
                  {y.terms.map((t: any) => (
                    <div key={t.id} className={`rounded-lg border p-3 ${t.isCurrent ? "border-primary" : ""}`}>
                      <div className="font-medium text-sm flex items-center justify-between">{t.name} {t.isCurrent && <Badge variant="secondary">current</Badge>}</div>
                      <div className="text-xs text-muted-foreground mt-1">{new Date(t.startsAt).toLocaleDateString("en-GB")} → {new Date(t.endsAt).toLocaleDateString("en-GB")}</div>
                    </div>
                  ))}
                </CardContent>
              </Card>
            ))}
          </div>
        </TabsContent>

        <TabsContent value="staff">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Users className="size-4" /> Staff</CardTitle></CardHeader>
            <CardContent className="space-y-2">
              {data.staff.map((st: any) => (
                <div key={st.id} className="rounded-lg border p-3 flex items-center justify-between">
                  <div>
                    <div className="font-medium text-sm">{st.fullName}</div>
                    <div className="text-xs text-muted-foreground">{st.title ?? st.role}</div>
                  </div>
                  <Badge variant="outline">{st.role}</Badge>
                </div>
              ))}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="grading">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base">Active grading scale — {s.curriculum}</CardTitle></CardHeader>
            <CardContent>
              <div className="rounded-xl border overflow-hidden">
                <table className="w-full text-sm">
                  <thead><tr className="bg-secondary text-left"><th className="px-3 py-2 font-medium">Grade</th><th className="px-3 py-2 font-medium">Descriptor</th><th className="px-3 py-2 font-medium text-right">Minimum score</th></tr></thead>
                  <tbody>
                    {SCALES_TABLE[s.curriculum]?.bands.map((b: any) => (
                      <tr key={b.grade} className="border-t"><td className="px-3 py-2 font-semibold">{b.grade}</td><td className="px-3 py-2">{b.label}</td><td className="px-3 py-2 text-right tabular-nums">{b.min}%</td></tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <p className="text-xs text-muted-foreground mt-3">{SCALES_TABLE[s.curriculum]?.examLabel} — class score 30% + exam 70% (GES basic convention).</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="waec">
          <WaecGradingTab />
        </TabsContent>

        <TabsContent value="reportlib">
          <ReportLibraryTab data={data} onChanged={load} />
        </TabsContent>
      </Tabs>

      <AddClassDialog open={clsOpen} onOpenChange={setClsOpen} onDone={load} />
    </div>
  );
}

function AddClassDialog({ open, onOpenChange, onDone }: { open: boolean; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [form, setForm] = useState({ name: "", grade: "B1", stage: "Primary (SBC)", capacity: "30" });
  const [busy, setBusy] = useState(false);

  async function submit() {
    if (!form.name) { toast({ title: "Class name required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/settings`, { action: "create-classroom", ...form, capacity: Number(form.capacity) });
      toast({ title: "Class created" });
      onOpenChange(false); onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader><DialogTitle>Add a class</DialogTitle></DialogHeader>
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1.5 col-span-2"><Label>Class name</Label><Input placeholder="e.g. Basic 6 — Emerald" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} /></div>
          <div className="space-y-1.5">
            <Label>Grade level</Label>
            <Input value={form.grade} onChange={(e) => setForm({ ...form, grade: e.target.value })} placeholder="B6 / Year 6 / MYP 2" />
          </div>
          <div className="space-y-1.5"><Label>Capacity</Label><Input type="number" value={form.capacity} onChange={(e) => setForm({ ...form, capacity: e.target.value })} /></div>
          <div className="space-y-1.5 col-span-2">
            <Label>Stage</Label>
            <Select value={form.stage} onValueChange={(v) => setForm({ ...form, stage: v })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="Kindergarten (SBC)">Kindergarten (SBC)</SelectItem>
                <SelectItem value="Primary (SBC)">Primary (SBC)</SelectItem>
                <SelectItem value="Common Core (CCP)">Common Core (CCP)</SelectItem>
                <SelectItem value="Cambridge Primary">Cambridge Primary</SelectItem>
                <SelectItem value="Cambridge Lower Secondary">Cambridge Lower Secondary</SelectItem>
                <SelectItem value="Cambridge Upper Secondary / IGCSE">Cambridge Upper Secondary / IGCSE</SelectItem>
                <SelectItem value="IB Primary Years Programme">IB Primary Years Programme</SelectItem>
                <SelectItem value="IB Middle Years Programme">IB Middle Years Programme</SelectItem>
                <SelectItem value="IB Diploma Programme">IB Diploma Programme</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Creating…" : "Create class"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── REPORT SHEET LIBRARY — remark templates & conduct traits (iedupro parity) ── */
const TEMPLATE_CATEGORIES: { value: string; label: string; hint: string }[] = [
  { value: "TEACHER_POSITIVE", label: "Teacher — positive", hint: "used for scores ≥ 68%" },
  { value: "TEACHER_NEUTRAL", label: "Teacher — neutral", hint: "used for 40–67%" },
  { value: "TEACHER_NEGATIVE", label: "Teacher — concern", hint: "used below 40%" },
  { value: "HEAD_RANGE", label: "Head — by average band", hint: "picked by the learner's overall average" },
];

function ReportLibraryTab({ data, onChanged }: { data: any; onChanged: () => void }) {
  const { currentSchoolId } = useStore();
  const templates: { id: string; category: string; minPercent: number | null; maxPercent: number | null; text: string; active: boolean }[] = data.remarkTemplates ?? [];
  const conductItems: { id: string; name: string; kind: string; active: boolean }[] = data.conductItems ?? [];
  const [busy, setBusy] = useState<string | null>(null);
  const [draft, setDraft] = useState<{ category: string; minPercent: string; maxPercent: string; text: string }>({ category: "TEACHER_POSITIVE", minPercent: "", maxPercent: "", text: "" });
  const [conductDraft, setConductDraft] = useState<{ name: string; kind: string }>({ name: "", kind: "CONDUCT" });

  async function act(payload: Record<string, unknown>, key: string, okMsg: string) {
    if (!currentSchoolId) return;
    setBusy(key);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/settings`, payload);
      toast({ title: okMsg, description: res.created ? `${res.created} starter entries added.` : undefined });
      onChanged();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(null); }
  }

  return (
    <div className="grid lg:grid-cols-2 gap-4 items-start">
      {/* remark templates */}
      <Card>
        <CardHeader className="pb-3 flex-row items-center justify-between space-y-0 flex-wrap gap-2">
          <CardTitle className="text-base flex items-center gap-2"><MessageSquareQuote className="size-4" /> Remarks library ({templates.length})</CardTitle>
          <Button size="sm" variant="outline" className="h-8 gap-1.5" disabled={busy === "seed" || templates.length > 0} onClick={() => act({ action: "seed-remark-templates" }, "seed", "Starter library loaded")}>
            <Sparkles className="size-3.5" /> Load starters
          </Button>
        </CardHeader>
        <CardContent className="space-y-3">
          <p className="text-xs text-muted-foreground">
            When report cards are generated, a remark is drawn at random from the matching band — subject remarks by score, head remarks by the learner&apos;s average. Keep several per band for natural variety.
          </p>
          {templates.length === 0 ? (
            <div className="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground">
              No custom remarks yet — the generator falls back to built-in phrases. Load the starter library or add your own below.
            </div>
          ) : (
            <div className="rounded-xl border max-h-[320px] overflow-y-auto nice-scroll divide-y">
              {templates.map((t) => {
                const cat = TEMPLATE_CATEGORIES.find((c) => c.value === t.category);
                return (
                  <div key={t.id} className="p-2.5 flex items-start gap-2.5">
                    <div className="flex-1 min-w-0">
                      <div className="flex flex-wrap items-center gap-1.5">
                        <Badge variant="secondary" className="text-[10px]">{cat?.label ?? t.category}</Badge>
                        {t.category === "HEAD_RANGE" && <Badge variant="outline" className="text-[10px]">{t.minPercent}–{t.maxPercent}%</Badge>}
                        {!t.active && <Badge variant="outline" className="text-[10px] text-muted-foreground">off</Badge>}
                      </div>
                      <p className="text-[13px] mt-1 leading-snug">{t.text}</p>
                    </div>
                    <div className="flex flex-col gap-1">
                      <Button size="sm" variant="ghost" className="h-6 px-2 text-[11px]" disabled={busy === t.id} onClick={() => act({ action: "toggle-remark-template", id: t.id, active: !t.active }, t.id, t.active ? "Template disabled" : "Template enabled")}>{t.active ? "Disable" : "Enable"}</Button>
                      <Button size="sm" variant="ghost" className="h-6 px-2 text-[11px] text-destructive" disabled={busy === t.id} onClick={() => act({ action: "delete-remark-template", id: t.id }, t.id, "Template deleted")}><Trash2 className="size-3" /></Button>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
          {/* add form */}
          <div className="rounded-xl border bg-secondary/30 p-3 space-y-2.5">
            <div className="grid grid-cols-2 gap-2">
              <Select value={draft.category} onValueChange={(v) => setDraft({ ...draft, category: v })}>
                <SelectTrigger className="h-8 text-xs"><SelectValue /></SelectTrigger>
                <SelectContent>
                  {TEMPLATE_CATEGORIES.map((c) => (
                    <SelectItem key={c.value} value={c.value} className="text-xs">
                      <span className="flex flex-col"><span>{c.label}</span><span className="text-[10px] text-muted-foreground">{c.hint}</span></span>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {draft.category === "HEAD_RANGE" ? (
                <div className="flex gap-1.5">
                  <Input className="h-8 text-xs" placeholder="from %" value={draft.minPercent} onChange={(e) => setDraft({ ...draft, minPercent: e.target.value })} />
                  <Input className="h-8 text-xs" placeholder="to %" value={draft.maxPercent} onChange={(e) => setDraft({ ...draft, maxPercent: e.target.value })} />
                </div>
              ) : <div className="text-[11px] text-muted-foreground self-center">Applies by subject score automatically.</div>}
            </div>
            <Textarea rows={2} className="text-[13px]" placeholder="e.g. A promising term — neater work and steadier revision will lift the scores further." value={draft.text} onChange={(e) => setDraft({ ...draft, text: e.target.value })} />
            <div className="flex justify-end">
              <Button size="sm" className="h-8 gap-1.5" disabled={!draft.text.trim() || busy === "add"} onClick={() => act({ action: "save-remark-template", category: draft.category, minPercent: draft.minPercent, maxPercent: draft.maxPercent, text: draft.text }, "add", "Remark template added").then(() => setDraft({ ...draft, text: "" }))}>
                <Plus className="size-3.5" /> Add remark
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* conduct & interests */}
      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><GraduationCap className="size-4" /> Conduct & interests ({conductItems.length})</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <p className="text-xs text-muted-foreground">
            Traits the class teacher can pick for the &quot;Conduct&quot; and &quot;Interests&quot; boxes on terminal report sheets.
          </p>
          <div className="flex flex-wrap gap-1.5">
            {conductItems.length === 0 && <span className="text-sm text-muted-foreground">None yet — add the ones your school reports on.</span>}
            {conductItems.map((c) => (
              <Badge key={c.id} variant={c.kind === "INTEREST" ? "outline" : "secondary"} className="gap-1 py-1 pl-2.5 pr-1">
                {c.name}
                <button className="rounded-full hover:bg-destructive/15 p-0.5" disabled={busy === c.id} onClick={() => act({ action: "delete-conduct-item", id: c.id }, c.id, "Removed")} aria-label={`Remove ${c.name}`}>
                  <Trash2 className="size-3 text-destructive" />
                </button>
              </Badge>
            ))}
          </div>
          <div className="rounded-xl border bg-secondary/30 p-3 flex gap-2">
            <Input className="h-8 text-xs flex-1" placeholder="e.g. Polite & respectful" value={conductDraft.name} onChange={(e) => setConductDraft({ ...conductDraft, name: e.target.value })} />
            <Select value={conductDraft.kind} onValueChange={(v) => setConductDraft({ ...conductDraft, kind: v })}>
              <SelectTrigger className="h-8 w-28 text-xs"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="CONDUCT" className="text-xs">Conduct</SelectItem>
                <SelectItem value="INTEREST" className="text-xs">Interest</SelectItem>
              </SelectContent>
            </Select>
            <Button size="sm" className="h-8 gap-1.5" disabled={!conductDraft.name.trim() || busy === "addc"} onClick={() => act({ action: "save-conduct-item", name: conductDraft.name, kind: conductDraft.kind }, "addc", "Added").then(() => setConductDraft({ name: "", kind: conductDraft.kind }))}>
              <Plus className="size-3.5" /> Add
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

/* ═══════════════════════════════════════════════════════════════
   WAEC RAW-SCORE GRADING (CI3 raw_score_grade parity) — the A1–F9
   scale used by exam-marks SMS, cumulative sheets and BECE
   aggregates. CRUD + CI3 stats (pass mark = lowest markFrom ≥ 50). */
type RawGradeRow = { id: string; name: string; gradePoint: string; gradePointNumeric: number; markFrom: number; markUpto: number; comment: string | null };

function WaecGradingTab() {
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<{ grades: RawGradeRow[]; stats: { total: number; highest: number; lowest: number; passMark: number } } | null>(null);
  const [editing, setEditing] = useState<RawGradeRow | "new" | null>(null);
  const [deleting, setDeleting] = useState<RawGradeRow | null>(null);
  const [busy, setBusy] = useState(false);

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<{ grades: RawGradeRow[]; stats: { total: number; highest: number; lowest: number; passMark: number } }>(`/api/school/${currentSchoolId}/ops/academics?tab=rawgrades`)
      .then(setData)
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(load, [currentSchoolId]);

  async function remove(g: RawGradeRow) {
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/academics`, { action: "rawgrade.delete", id: g.id });
      toast({ title: "Grade deleted", description: `${g.gradePoint} (${g.name}) removed from the scale.` });
      setDeleting(null);
      load();
    } catch (e: any) {
      toast({ title: "Delete failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  if (!data) return <LoadingBlock label="Loading WAEC grading scale…" />;

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<Award className="size-4" />} label="Total grades" value={data.stats.total} tone="primary" sub="A1 … F9 bands" />
        <StatCard label="Highest mark" value={`${data.stats.highest}%`} sub="top of the scale" />
        <StatCard label="Lowest mark" value={`${data.stats.lowest}%`} sub="bottom of the scale" />
        <StatCard label="Pass mark" value={`${data.stats.passMark}%`} tone="gold" sub="lowest credit band (≥ 50)" />
      </div>

      <Card>
        <CardHeader className="pb-3 flex-row flex-wrap items-center justify-between gap-2 space-y-0">
          <div>
            <CardTitle className="text-base">WAEC raw-score grades</CardTitle>
            <p className="text-xs text-muted-foreground mt-1">Raw exam percentages map onto these bands for exam-marks SMS, cumulative sheets and the BECE aggregate (grade points summed).</p>
          </div>
          <Button size="sm" className="gap-1.5" onClick={() => setEditing("new")}><Plus className="size-4" /> Add grade</Button>
        </CardHeader>
        <CardContent className="p-0">
          {data.grades.length === 0 ? (
            <EmptyState icon={<Award className="size-5" />} title="No grades configured" hint="Add the A1–F9 WAEC bands so raw scores can be graded." />
          ) : (
            <div className="overflow-x-auto nice-scroll">
              <table className="w-full text-sm">
                <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                  <tr className="text-left border-b">
                    <th className="px-3 py-2.5 font-medium">Grade</th>
                    <th className="px-3 py-2.5 font-medium">Remark</th>
                    <th className="px-3 py-2.5 font-medium text-right">Numeric</th>
                    <th className="px-3 py-2.5 font-medium text-right">Marks</th>
                    <th className="px-3 py-2.5 font-medium hidden md:table-cell">Comment</th>
                    <th className="px-3 py-2.5"></th>
                  </tr>
                </thead>
                <tbody>
                  {data.grades.map((g) => (
                    <tr key={g.id} className="border-b last:border-0 hover:bg-secondary/30">
                      <td className="px-3 py-2.5">
                        <Badge className={cn("font-semibold", g.markFrom >= 50 ? "bg-emerald-600" : g.markFrom >= 40 ? "bg-amber-600" : "bg-red-600")}>{g.gradePoint}</Badge>
                      </td>
                      <td className="px-3 py-2.5 font-medium">{g.name}</td>
                      <td className="px-3 py-2.5 text-right tabular-nums">{g.gradePointNumeric}</td>
                      <td className="px-3 py-2.5 text-right tabular-nums">{g.markFrom}–{g.markUpto}%</td>
                      <td className="px-3 py-2.5 text-muted-foreground hidden md:table-cell">{g.comment ?? "—"}</td>
                      <td className="px-3 py-2.5 text-right whitespace-nowrap">
                        <Button size="sm" variant="outline" className="h-8 gap-1" onClick={() => setEditing(g)}><Pencil className="size-3.5" /> Edit</Button>
                        <Button size="sm" variant="ghost" className="h-8 size-8 p-0 text-destructive ml-1" onClick={() => setDeleting(g)} aria-label={`Delete ${g.gradePoint}`}><Trash2 className="size-4" /></Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>

      <RawGradeDialog grade={editing} onOpenChange={(o) => !o && setEditing(null)} onDone={() => { setEditing(null); load(); }} />

      <AlertDialog open={!!deleting} onOpenChange={(o) => !o && setDeleting(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete grade {deleting?.gradePoint}?</AlertDialogTitle>
            <AlertDialogDescription>
              {deleting?.name} ({deleting?.markFrom}–{deleting?.markUpto}%) will be removed from the WAEC scale. Raw scores in that band will fall through to the next matching grade.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction className="bg-destructive text-destructive-foreground hover:bg-destructive/90" disabled={busy} onClick={() => deleting && remove(deleting)}>Delete</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}

function RawGradeDialog({ grade, onOpenChange, onDone }: { grade: RawGradeRow | "new" | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const isNew = grade === "new";
  const [form, setForm] = useState({ name: "", gradePoint: "", gradePointNumeric: "1", markFrom: "0", markUpto: "100", comment: "" });
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (grade && grade !== "new") {
      setForm({ name: grade.name, gradePoint: grade.gradePoint, gradePointNumeric: String(grade.gradePointNumeric), markFrom: String(grade.markFrom), markUpto: String(grade.markUpto), comment: grade.comment ?? "" });
    } else if (grade === "new") {
      setForm({ name: "CREDIT", gradePoint: "", gradePointNumeric: "1", markFrom: "50", markUpto: "54", comment: "" });
    }
  }, [grade]);

  async function submit() {
    if (!form.name.trim() || !form.gradePoint.trim()) { toast({ title: "Remark name and grade point are required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/academics`, {
        action: isNew ? "rawgrade.create" : "rawgrade.update",
        id: isNew ? undefined : (grade as RawGradeRow).id,
        name: form.name, gradePoint: form.gradePoint, gradePointNumeric: Number(form.gradePointNumeric) || 0,
        markFrom: Number(form.markFrom), markUpto: Number(form.markUpto), comment: form.comment || null,
      });
      toast({ title: isNew ? "Grade added" : "Grade updated", description: `${form.gradePoint} · ${form.name} (${form.markFrom}–${form.markUpto}%).` });
      onDone();
    } catch (e: any) {
      toast({ title: "Failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!grade} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader><DialogTitle>{isNew ? "Add WAEC grade" : `Edit grade ${grade ? grade.gradePoint : ""}`}</DialogTitle></DialogHeader>
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1.5"><Label>Grade point *</Label><Input placeholder="e.g. C6" value={form.gradePoint} onChange={(e) => setForm({ ...form, gradePoint: e.target.value.toUpperCase().slice(0, 8) })} /></div>
          <div className="space-y-1.5">
            <Label>Remark name *</Label>
            <Select value={form.name} onValueChange={(v) => setForm({ ...form, name: v })}>
              <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
              <SelectContent>
                {["EXCELLENT", "VERY GOOD", "GOOD", "CREDIT", "PASS", "FAIL"].map((n) => <SelectItem key={n} value={n}>{n.charAt(0) + n.slice(1).toLowerCase()}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5"><Label>Mark from (%)</Label><Input type="number" value={form.markFrom} onChange={(e) => setForm({ ...form, markFrom: e.target.value })} /></div>
          <div className="space-y-1.5"><Label>Mark up to (%)</Label><Input type="number" value={form.markUpto} onChange={(e) => setForm({ ...form, markUpto: e.target.value })} /></div>
          <div className="space-y-1.5"><Label>Grade numeric (BECE weight)</Label><Input type="number" value={form.gradePointNumeric} onChange={(e) => setForm({ ...form, gradePointNumeric: e.target.value })} /></div>
          <div className="space-y-1.5 col-span-2"><Label>Comment</Label><Input placeholder="Shown with the grade" value={form.comment} onChange={(e) => setForm({ ...form, comment: e.target.value })} /></div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : isNew ? "Add grade" : "Save grade"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

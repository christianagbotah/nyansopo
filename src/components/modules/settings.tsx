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
import { ModuleHeader, LoadingBlock, StatusBadge } from "@/components/ui-helpers";
import { SCALES_TABLE } from "@/lib/grading-scale";
import { toast } from "@/hooks/use-toast";
import { Settings as SettingsIcon, GraduationCap, BookOpen, Users, CalendarRange, Plus, Save } from "lucide-react";

export function Settings() {
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<any | null>(null);
  const [busy, setBusy] = useState(false);
  const [clsOpen, setClsOpen] = useState(false);
  const [form, setForm] = useState<any>({ motto: "", phone: "", email: "", curriculum: "GES", nasiaStatus: "", gnacopsStatus: "" });

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
      <Tabs defaultValue="profile">
        <TabsList className="flex-wrap">
          <TabsTrigger value="profile">Profile & curriculum</TabsTrigger>
          <TabsTrigger value="classes">Classes ({data.classrooms.length})</TabsTrigger>
          <TabsTrigger value="subjects">Subjects ({data.subjects.length})</TabsTrigger>
          <TabsTrigger value="calendar">Calendar</TabsTrigger>
          <TabsTrigger value="staff">Staff ({data.staff.length})</TabsTrigger>
          <TabsTrigger value="grading">Grading scale</TabsTrigger>
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

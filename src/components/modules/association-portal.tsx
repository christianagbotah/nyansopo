"use client";
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { StatCard, ModuleHeader, LoadingBlock, StatusBadge, SeverityBadge, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, fmtDateTime, FLAG_LABELS } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { CURRICULUM_BADGE } from "@/lib/format";
import { Landmark, School, Flag, ArrowLeftRight, Megaphone, ShieldCheck, Search, BadgeCheck, FileSearch, Building2 } from "lucide-react";

export function AssociationPortal() {
  const { session } = useStore();
  const [tab, setTab] = useState("overview");
  const [overview, setOverview] = useState<any | null>(null);
  const [schools, setSchools] = useState<any[] | null>(null);
  const [verifyFor, setVerifyFor] = useState<any | null>(null);
  const [announceOpen, setAnnounceOpen] = useState(false);
  const [search, setSearch] = useState("");
  const [curriculumFilter, setCurriculumFilter] = useState("all");

  const load = () => {
    apiGet<any>(`/api/association/overview`).then(setOverview).catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
    apiGet<any>(`/api/association/schools`).then((d) => setSchools(d.schools)).catch(() => setSchools([]));
  };
  useEffect(load, [session]);

  const filteredSchools = useMemo(() => {
    if (!schools) return [];
    let out = schools;
    if (curriculumFilter !== "all") out = out.filter((s) => s.curriculum === curriculumFilter);
    if (search.trim()) {
      const q = search.toLowerCase();
      out = out.filter((s) => `${s.name} ${s.town} ${s.zone} ${s.code}`.toLowerCase().includes(q));
    }
    return out;
  }, [schools, search, curriculumFilter]);

  if (!overview || !schools) return <LoadingBlock label="Loading the association picture…" />;
  const st = overview.stats;

  return (
    <div>
      <ModuleHeader
        title={overview.scope.node?.name ?? "Association Portal"}
        description={`${overview.scope.node?.leadTitle ?? ""} ${overview.scope.node?.leadName ? `— ${overview.scope.node.leadName}` : ""} · oversight of ${st.schools} schools and ${overview.scope.studentCount?.toLocaleString()} learners`}
        actions={
          <Button size="sm" className="gap-1.5" onClick={() => setAnnounceOpen(true)}><Megaphone className="size-4" /> Broadcast</Button>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4">
        <StatCard icon={<School className="size-4" />} label="Schools in scope" value={st.schools} sub={`${st.licensed} NaSIA-licensed · ${st.provisional} provisional`} tone="primary" />
        <StatCard icon={<BadgeCheck className="size-4" />} label="GNACOPS verified" value={st.verified} sub={`${st.pendingGnacops} awaiting verification`} tone="gold" />
        <StatCard icon={<Flag className="size-4" />} label="Active flags" value={st.activeFlags} sub={`${st.criticalFlags} critical · ${st.contestedFlags} contested`} tone="danger" />
        <StatCard icon={<ArrowLeftRight className="size-4" />} label="Pending releases" value={st.pendingReleases} sub="transfers blocked pending clearance" />
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex-wrap">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="schools">Schools ({schools.length})</TabsTrigger>
          <TabsTrigger value="flags">Flagged registry ({overview.flags.length})</TabsTrigger>
          <TabsTrigger value="transfers">Transfers</TabsTrigger>
          <TabsTrigger value="announcements">Announcements</TabsTrigger>
        </TabsList>

        {/* ── OVERVIEW ── */}
        <TabsContent value="overview">
          <div className="grid lg:grid-cols-3 gap-4">
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base">Schools by region</CardTitle></CardHeader>
              <CardContent className="space-y-2">
                {Object.entries(st.byRegion).map(([region, count]) => (
                  <div key={region} className="flex items-center gap-2">
                    <span className="text-xs w-36 truncate">{region}</span>
                    <div className="flex-1 h-2 rounded-full bg-secondary overflow-hidden"><div className="h-full bg-primary rounded-full" style={{ width: `${(Number(count) / st.schools) * 100}%` }} /></div>
                    <span className="text-xs font-semibold w-6 text-right">{count as number}</span>
                  </div>
                ))}
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base">Compliance snapshot</CardTitle></CardHeader>
              <CardContent className="space-y-2.5">
                {[
                  { l: "NaSIA licensed", v: st.licensed, tone: "text-emerald-600" },
                  { l: "NaSIA provisional", v: st.provisional, tone: "text-amber-600" },
                  { l: "NaSIA pending/expired", v: st.pendingNasia, tone: "text-destructive" },
                  { l: "GNACOPS membership lapsed", v: st.pendingGnacops, tone: "text-destructive" },
                ].map((x) => (
                  <div key={x.l} className="flex justify-between text-sm"><span className="text-muted-foreground">{x.l}</span><span className={`font-bold ${x.tone}`}>{x.v}</span></div>
                ))}
                <p className="text-[11px] text-muted-foreground border-t pt-2">Use the Schools tab to validate a school&apos;s NaSIA license and GNACOPS membership status.</p>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base">Defaulter pressure</CardTitle></CardHeader>
              <CardContent className="space-y-3">
                <div className="text-sm text-muted-foreground">Flags raised by schools in your scope — verified by you before they bind the network.</div>
                <div className="flex gap-2 flex-wrap">
                  <Badge variant="destructive">{st.criticalFlags} critical</Badge>
                  <Badge variant="secondary">{st.contestedFlags} contested</Badge>
                  <Badge>{st.activeFlags} total active</Badge>
                </div>
                <Button size="sm" variant="outline" className="gap-1.5" onClick={() => setTab("flags")}><Flag className="size-4" /> Open the registry</Button>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* ── SCHOOLS ── */}
        <TabsContent value="schools">
          <Card>
            <CardHeader className="pb-3 flex-row flex-wrap items-center justify-between gap-2 space-y-0">
              <CardTitle className="text-base flex items-center gap-2"><Building2 className="size-4" /> School directory</CardTitle>
              <div className="flex gap-2">
                <div className="relative">
                  <Search className="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                  <Input className="pl-8 w-56" placeholder="Search school, town, zone…" value={search} onChange={(e) => setSearch(e.target.value)} />
                </div>
                <Select value={curriculumFilter} onValueChange={setCurriculumFilter}>
                  <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All curricula</SelectItem>
                    <SelectItem value="GES">GES</SelectItem><SelectItem value="CAIE">Cambridge</SelectItem><SelectItem value="IB">IB</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </CardHeader>
            <CardContent className="p-0">
              <div className="overflow-x-auto nice-scroll max-h-[520px]">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                    <tr className="text-left border-b">
                      <th className="px-3 py-2.5 font-medium">School</th>
                      <th className="px-3 py-2.5 font-medium hidden md:table-cell">Zone / District</th>
                      <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Curriculum</th>
                      <th className="px-3 py-2.5 font-medium text-right">Learners</th>
                      <th className="px-3 py-2.5 font-medium hidden sm:table-cell">NaSIA</th>
                      <th className="px-3 py-2.5 font-medium hidden sm:table-cell">GNACOPS</th>
                      <th className="px-3 py-2.5 font-medium text-right hidden md:table-cell">Flags</th>
                      <th className="px-3 py-2.5"></th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredSchools.map((s) => (
                      <tr key={s.id} className="border-b last:border-0 hover:bg-secondary/30">
                        <td className="px-3 py-2.5">
                          <div className="font-medium">{s.name}{s.group && !s.isBranchMain && <span className="text-xs text-muted-foreground"> · branch</span>}</div>
                          <div className="text-xs text-muted-foreground">{s.town}, {s.region} · est. {s.establishedYear}</div>
                        </td>
                        <td className="px-3 py-2.5 hidden md:table-cell text-muted-foreground">{s.zone}</td>
                        <td className="px-3 py-2.5 hidden lg:table-cell"><Badge variant="outline" className={CURRICULUM_BADGE[s.curriculum]?.className}>{CURRICULUM_BADGE[s.curriculum]?.label ?? s.curriculum}</Badge></td>
                        <td className="px-3 py-2.5 text-right tabular-nums font-semibold">{s.studentsOnRoll}</td>
                        <td className="px-3 py-2.5 hidden sm:table-cell"><StatusBadge status={s.nasiaStatus} /></td>
                        <td className="px-3 py-2.5 hidden sm:table-cell"><StatusBadge status={s.gnacopsStatus} /></td>
                        <td className="px-3 py-2.5 text-right hidden md:table-cell">{s.activeFlags > 0 ? <Badge variant="destructive">{s.activeFlags}</Badge> : "—"}</td>
                        <td className="px-3 py-2.5 text-right">
                          <Button size="sm" variant="outline" className="h-7 gap-1" onClick={() => setVerifyFor(s)}><ShieldCheck className="size-3.5" /> Validate</Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── FLAGS REGISTRY ── */}
        <TabsContent value="flags">
          <div className="space-y-3">
            {overview.flags.length === 0 && <Card><CardContent><EmptyState icon={<Flag className="size-5" />} title="No flags in your scope" hint="A clean register across member schools." /></CardContent></Card>}
            {overview.flags.map((f: any) => (
              <Card key={f.id} className={f.status === "ACTIVE" && f.severity === "CRITICAL" ? "border-red-300 dark:border-red-900" : ""}>
                <CardContent className="pt-5">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <div className="font-semibold">{f.student} <span className="font-mono text-xs text-muted-foreground font-normal">{f.nyansapoIndex}</span></div>
                      <div className="text-xs text-muted-foreground mt-0.5">
                        {FLAG_LABELS[f.type] ?? f.type}{f.amountOwed ? ` — ${fmtCedi(f.amountOwed)}` : ""} · raised by <b>{f.school.name}</b> ({f.school.town}) · {fmtDate(f.raisedAt)}
                      </div>
                    </div>
                    <div className="flex items-center gap-1.5">
                      <SeverityBadge severity={f.severity} />
                      <StatusBadge status={f.status} />
                    </div>
                  </div>
                  <p className="text-sm text-muted-foreground mt-2">{f.description}</p>
                  {f.verifications.length > 0 && (
                    <div className="mt-2 space-y-1">
                      {f.verifications.map((v: any) => (
                        <div key={v.id} className="rounded bg-secondary/60 px-2.5 py-1.5 text-xs">
                          <b>{v.action}</b> — {v.officerName} ({v.officerTitle}) · {fmtDate(v.verifiedAt)}{v.note ? ` · ${v.note}` : ""}
                        </div>
                      ))}
                    </div>
                  )}
                  {f.status === "ACTIVE" && (
                    <div className="flex flex-wrap gap-2 mt-3">
                      <Button size="sm" variant="outline" className="h-8 gap-1" onClick={() => setVerifyFor({ ...f, isFlag: true })}><ShieldCheck className="size-3.5" /> Verify this flag</Button>
                      <Button size="sm" variant="ghost" className="h-8 gap-1" onClick={() => { setTab("schools"); toast({ title: "Cross-check the school", description: `Find ${f.school.name} in the directory to review its compliance status.` }); }}><FileSearch className="size-3.5" /> View school</Button>
                    </div>
                  )}
                </CardContent>
              </Card>
            ))}
          </div>
        </TabsContent>

        {/* ── TRANSFERS ── */}
        <TabsContent value="transfers">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><ArrowLeftRight className="size-4" /> Recent transfers across your scope</CardTitle></CardHeader>
            <CardContent className="p-0">
              <div className="overflow-x-auto nice-scroll max-h-[480px]">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                    <tr className="text-left border-b">
                      <th className="px-3 py-2.5 font-medium">Learner</th>
                      <th className="px-3 py-2.5 font-medium">From → To</th>
                      <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Date</th>
                      <th className="px-3 py-2.5 font-medium text-right hidden md:table-cell">Outstanding</th>
                      <th className="px-3 py-2.5 font-medium">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {overview.transfers.map((t: any) => (
                      <tr key={t.id} className="border-b last:border-0">
                        <td className="px-3 py-2.5 font-medium">{t.student}</td>
                        <td className="px-3 py-2.5 text-muted-foreground">{t.from.name} → {t.to.name}</td>
                        <td className="px-3 py-2.5 hidden sm:table-cell">{fmtDate(t.date)}</td>
                        <td className="px-3 py-2.5 text-right hidden md:table-cell">{t.outstanding > 0 ? <span className="text-destructive font-semibold">{fmtCedi(t.outstanding)}</span> : "—"}</td>
                        <td className="px-3 py-2.5"><StatusBadge status={t.status} /></td>
                      </tr>
                    ))}
                    {!overview.transfers.length && <tr><td colSpan={5}><EmptyState title="No transfers recorded yet" /></td></tr>}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── ANNOUNCEMENTS ── */}
        <TabsContent value="announcements">
          <div className="space-y-3">
            {overview.announcements.map((a: any) => (
              <Card key={a.id}>
                <CardContent className="pt-5">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <div className="font-semibold flex items-center gap-2">{a.title} {a.priority === "HIGH" && <Badge variant="destructive">urgent</Badge>}</div>
                      <div className="text-xs text-muted-foreground mt-0.5">{a.source} · {fmtDateTime(a.postedAt)} · {a.category.toLowerCase()}</div>
                    </div>
                  </div>
                  <p className="text-sm text-muted-foreground mt-2 leading-relaxed">{a.body}</p>
                </CardContent>
              </Card>
            ))}
            {!overview.announcements.length && <Card><CardContent><EmptyState icon={<Megaphone className="size-5" />} title="No announcements yet" /></CardContent></Card>}
          </div>
        </TabsContent>
      </Tabs>

      <ValidateDialog target={verifyFor} onOpenChange={(o) => !o && setVerifyFor(null)} onDone={load} />
      <AnnounceDialog open={announceOpen} onOpenChange={setAnnounceOpen} onDone={load} />
    </div>
  );
}

function ValidateDialog({ target, onOpenChange, onDone }: { target: any | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const isFlag = !!target?.isFlag;
  const [nasia, setNasia] = useState<string>("");
  const [gnacops, setGnacops] = useState<string>("");
  const [action, setAction] = useState("CONFIRMED");
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (target && !isFlag) { setNasia(target.nasiaStatus ?? "LICENSED"); setGnacops(target.gnacopsStatus ?? "VERIFIED"); }
  }, [target]);

  async function submit() {
    if (!target) return;
    setBusy(true);
    try {
      if (isFlag) {
        await apiPost(`/api/association/verify-flag`, { flagId: target.id, action, note });
        toast({ title: "Verification recorded", description: `Flag ${action.toLowerCase()} — visible to all member schools.` });
      } else {
        await apiPost(`/api/association/validate`, { schoolId: target.id, nasiaStatus: nasia, gnacopsStatus: gnacops });
        toast({ title: "School validation updated", description: `${target.name} — NaSIA: ${nasia}, GNACOPS: ${gnacops}.` });
      }
      onOpenChange(false); onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  return (
    <Dialog open={!!target} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Landmark className="size-4" /> {isFlag ? "Verify flag" : "Validate school"}</DialogTitle>
          <DialogDescription>
            {isFlag ? `Flag on ${target?.student} raised by ${target?.school?.name}. Association verification gives the flag network-wide authority.` : `${target?.name} — update NaSIA licensing and GNACOPS membership status.`}
          </DialogDescription>
        </DialogHeader>
        {isFlag ? (
          <div className="space-y-3">
            <div className="space-y-1.5">
              <Label>Decision</Label>
              <Select value={action} onValueChange={setAction}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="CONFIRMED">Confirm the flag (verified)</SelectItem>
                  <SelectItem value="EVIDENCE_REQUESTED">Request more evidence</SelectItem>
                  <SelectItem value="DISMISSED">Dismiss the flag</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Note</Label>
              <Textarea rows={3} value={note} onChange={(e) => setNote(e.target.value)} placeholder="e.g. Records verified during the district spot-check on …" />
            </div>
          </div>
        ) : (
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>NaSIA license</Label>
              <Select value={nasia} onValueChange={setNasia}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="LICENSED">Licensed</SelectItem><SelectItem value="PROVISIONAL">Provisional</SelectItem><SelectItem value="PENDING">Pending</SelectItem><SelectItem value="EXPIRED">Expired</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>GNACOPS membership</Label>
              <Select value={gnacops} onValueChange={setGnacops}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="VERIFIED">Verified</SelectItem><SelectItem value="PENDING">Pending</SelectItem><SelectItem value="LAPSED">Lapsed</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="text-xs text-muted-foreground sm:col-span-2">Current registration: {target?.nasiaRegNo ?? "—"}</div>
          </div>
        )}
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : "Confirm"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function AnnounceDialog({ open, onOpenChange, onDone }: { open: boolean; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const [form, setForm] = useState({ title: "", body: "", audience: "SCHOOLS", category: "GENERAL", priority: "NORMAL" });
  const [busy, setBusy] = useState(false);

  async function submit() {
    if (!form.title || !form.body) { toast({ title: "Title and body required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/association/announce`, form);
      toast({ title: "Announcement broadcast", description: "Member schools in your scope will see it on their dashboards." });
      onOpenChange(false); setForm({ title: "", body: "", audience: "SCHOOLS", category: "GENERAL", priority: "NORMAL" }); onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader><DialogTitle>Broadcast to member schools</DialogTitle><DialogDescription>Reaches all member schools under your scope.</DialogDescription></DialogHeader>
        <div className="space-y-3">
          <div className="space-y-1.5"><Label>Title</Label><Input value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} /></div>
          <div className="space-y-1.5"><Label>Message</Label><Textarea rows={4} value={form.body} onChange={(e) => setForm({ ...form, body: e.target.value })} /></div>
          <div className="grid grid-cols-3 gap-3">
            <div className="space-y-1.5">
              <Label>Audience</Label>
              <Select value={form.audience} onValueChange={(v) => setForm({ ...form, audience: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent><SelectItem value="SCHOOLS">Schools</SelectItem><SelectItem value="ZONAL">Zones</SelectItem><SelectItem value="DISTRICT">Districts</SelectItem><SelectItem value="REGIONAL">Regions</SelectItem><SelectItem value="NATIONAL">National</SelectItem></SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Category</Label>
              <Select value={form.category} onValueChange={(v) => setForm({ ...form, category: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent><SelectItem value="GENERAL">General</SelectItem><SelectItem value="COMPLIANCE">Compliance</SelectItem><SelectItem value="CALENDAR">Calendar</SelectItem><SelectItem value="TRAINING">Training</SelectItem><SelectItem value="URGENT">Urgent</SelectItem></SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Priority</Label>
              <Select value={form.priority} onValueChange={(v) => setForm({ ...form, priority: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent><SelectItem value="NORMAL">Normal</SelectItem><SelectItem value="HIGH">High</SelectItem></SelectContent>
              </Select>
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy} className="gap-1.5"><Megaphone className="size-4" /> {busy ? "Broadcasting…" : "Broadcast"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

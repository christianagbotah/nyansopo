"use client";
// ── NTC Licences & CPD (wave 58 — Ghana National Teaching Council) ──
// Ghanaian teachers hold a renewable NTC licence; renewal requires CPD
// points over the licence cycle across NTC's programme categories
// (mandatory / rank-based / recommended). This module keeps the school's
// register:
//   • Licence register — per-staff NTC number, category, issue & expiry
//     with renewal alerts (expiring ≤90 days, critical ≤30, expired)
//   • CPD log — in-service activities with points, evidence and a
//     LOGGED → VERIFIED / REJECTED trail by the head of school
//   • Points board — verified points per category against the cycle
//     targets, with the school's NTC targets editor
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Progress } from "@/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard, FormGrid, FormField, FilterBar, FilterSelect, FilterSearch, downloadCsv } from "@/components/ui-helpers";
import { fmtDate } from "@/lib/format";
import { useToast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  BadgeCheck, Plus, Pencil, Trash2, Search, Printer, Download, Target, NotebookPen, ShieldCheck, GraduationCap, Ban,
} from "lucide-react";

const OPS = "/ops/staffcpd";
const DAY = 864e5;

type StaffRow = {
  id: string; fullName: string; role: string; title: string | null; category: string; position: string | null;
  qualification: string | null; sex: string | null; staffCode: string | null; active: boolean;
  ntcLicenceNo: string | null; ntcCategory: string | null; ntcIssued: string | null; ntcExpiry: string | null;
};
type Activity = {
  id: string; staffId: string; title: string; provider: string | null; category: string; activityDate: string;
  points: number; hours: number | null; evidence: string | null; status: string; verifiedByName: string | null;
  verifiedAt: string | null; note: string | null; staff: { id: string; fullName: string; category: string };
};
type Setting = { targetMandatory: number; targetRank: number; targetRecommended: number; cycleYears: number };
type Summary = { staffId: string; points: Record<string, number>; total: number; logged: number; cycle: { start: string; end: string; usesLicenceDates: boolean } };
type CpdData = { staff: StaffRow[]; activities: Activity[]; setting: Setting; summary: Summary[] };

const CAT_LABEL: Record<string, string> = { MANDATORY: "Mandatory", RANK: "Rank-based", RECOMMENDED: "Recommended" };
const LICENCE_CATEGORIES = ["Professional", "Provisional", "Non-professional"];

/** Licence status bucket from today (client-side twin of the API helper). */
function licenceStatus(expiry: string | null): { key: string; label: string; cls: string; days: number | null } {
  if (!expiry) return { key: "NONE", label: "Not captured", cls: "bg-zinc-400", days: null };
  const days = Math.floor((new Date(expiry).getTime() - Date.now()) / DAY);
  if (days < 0) return { key: "EXPIRED", label: "Expired", cls: "bg-rose-600", days };
  if (days <= 30) return { key: "CRITICAL", label: `Critical · ${days}d`, cls: "bg-red-500", days };
  if (days <= 90) return { key: "EXPIRING", label: `Expiring · ${days}d`, cls: "bg-amber-500", days };
  return { key: "VALID", label: `Valid · ${days}d`, cls: "bg-emerald-600", days };
}

const iso = (d: string | null) => (d ? new Date(d).toISOString().slice(0, 10) : "");
const today = () => new Date().toISOString().slice(0, 10);

const emptyLicence = { staffId: "", ntcLicenceNo: "", ntcCategory: "", ntcIssued: "", ntcExpiry: "" };
const emptyActivity = { staffId: "", title: "", provider: "", category: "RECOMMENDED", activityDate: today(), points: "2", hours: "", evidence: "", note: "" };

export function StaffCpd() {
  const { currentSchoolId, navTarget } = useStore();
  const { toast } = useToast();
  const schoolId = currentSchoolId ?? "";
  const [data, setData] = useState<CpdData | null>(null);
  const [tab, setTab] = useState("licences");
  const [busy, setBusy] = useState(false);
  const [search, setSearch] = useState("");
  const [licFilter, setLicFilter] = useState("ALL");
  const [logStaff, setLogStaff] = useState("ALL");
  const [logCat, setLogCat] = useState("ALL");
  const [logStatus, setLogStatus] = useState("ALL");
  const [boardFilter, setBoardFilter] = useState("TEACHING");
  const [licenceDialog, setLicenceDialog] = useState(false);
  const [licenceForm, setLicenceForm] = useState(emptyLicence);
  const [activityDialog, setActivityDialog] = useState(false);
  const [activityForm, setActivityForm] = useState(emptyActivity);
  const [targetsDialog, setTargetsDialog] = useState(false);
  const [targetsForm, setTargetsForm] = useState<Setting>({ targetMandatory: 20, targetRank: 40, targetRecommended: 40, cycleYears: 3 });

  useEffect(() => { if (navTarget?.tab) setTab(navTarget.tab); }, [navTarget]);

  const load = useCallback(async () => {
    if (!schoolId) return;
    try { setData(await apiGet<CpdData>(`/api/school/${schoolId}${OPS}`)); }
    catch (e: any) { toast({ title: "Could not load NTC & CPD data", description: e.message, variant: "destructive" }); }
  }, [schoolId, toast]);
  useEffect(() => { load(); }, [load]);
  useEffect(() => { if (data) setTargetsForm(data.setting); }, [data]);

  const act = useCallback(async (body: Record<string, unknown>, successMsg: string) => {
    if (!schoolId) return null;
    setBusy(true);
    try {
      const res = await apiPost<Record<string, unknown>>(`/api/school/${schoolId}${OPS}`, body);
      toast({ title: successMsg });
      await load();
      return res;
    } catch (e: any) {
      toast({ title: "Action failed", description: e.message, variant: "destructive" });
      return null;
    } finally { setBusy(false); }
  }, [schoolId, load, toast]);

  const teaching = useMemo(() => (data?.staff ?? []).filter((s) => s.category === "TEACHING"), [data]);
  const teachingActive = useMemo(() => teaching.filter((s) => s.active), [teaching]);
  const stats = useMemo(() => {
    const st = (k: string) => teachingActive.filter((s) => licenceStatus(s.ntcExpiry).key === k).length;
    return { teaching: teachingActive.length, valid: st("VALID"), expiring: st("EXPIRING"), critical: st("CRITICAL"), expired: st("EXPIRED"), none: st("NONE") };
  }, [teachingActive]);

  const licenceRows = useMemo(() => {
    const q = search.trim().toLowerCase();
    return (data?.staff ?? []).filter((s) => {
      const st = licenceStatus(s.ntcExpiry).key;
      if (licFilter !== "ALL" && licFilter !== st) return false;
      if (q && !s.fullName.toLowerCase().includes(q) && !(s.ntcLicenceNo ?? "").toLowerCase().includes(q)) return false;
      return true;
    });
  }, [data, licFilter, search]);

  const activityRows = useMemo(() => {
    const q = search.trim().toLowerCase();
    return (data?.activities ?? []).filter((a) => {
      if (logStaff !== "ALL" && a.staffId !== logStaff) return false;
      if (logCat !== "ALL" && a.category !== logCat) return false;
      if (logStatus !== "ALL" && a.status !== logStatus) return false;
      if (q && !a.title.toLowerCase().includes(q) && !a.staff.fullName.toLowerCase().includes(q) && !(a.provider ?? "").toLowerCase().includes(q)) return false;
      return true;
    });
  }, [data, logStaff, logCat, logStatus, search]);

  const boardRows = useMemo(() => {
    if (!data) return [];
    const targetTotal = data.setting.targetMandatory + data.setting.targetRank + data.setting.targetRecommended;
    return data.staff
      .filter((s) => (boardFilter === "TEACHING" ? s.category === "TEACHING" : true))
      .map((s) => {
        const sum = data.summary.find((x) => x.staffId === s.id)!;
        return { staff: s, sum, targetTotal, pct: targetTotal ? Math.min(100, Math.round((sum.total / targetTotal) * 100)) : 0 };
      })
      .sort((a, b) => b.sum.total - a.sum.total);
  }, [data, boardFilter]);

  const onTrack = useMemo(() => boardRows.filter((r) => r.sum.total >= r.targetTotal).length, [boardRows]);

  // ── licence register printout (A4) ─────────────────────────────
  function printRegister() {
    if (!data) return;
    const w = window.open("", "_blank", "width=1000,height=800");
    if (!w) return;
    w.document.write(`<!doctype html><html><head><title>NTC licence register</title>
      <style>
        body{font-family:Arial,Helvetica,sans-serif;padding:20px;font-size:12px}
        h1{font-size:16px;margin:0}h2{font-size:11px;font-weight:normal;color:#444;margin:2px 0 14px}
        table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #999;padding:4px 5px;text-align:left}
        th{background:#eee}
        @media print{@page{size:A4 landscape;margin:10mm}}
      </style></head><body>
      <h1>NTC Licence Register — teaching staff</h1>
      <h2>Generated ${new Date().toLocaleString("en-GB")} · ${teachingActive.length} active teaching staff</h2>
      <table><tr><th>#</th><th>Staff</th><th>Licence №</th><th>Category</th><th>Issued</th><th>Expiry</th><th>Status</th></tr>
      ${teaching.map((s, i) => { const st = licenceStatus(s.ntcExpiry); return `<tr><td>${i + 1}</td><td>${s.fullName}</td><td>${s.ntcLicenceNo ?? "—"}</td><td>${s.ntcCategory ?? "—"}</td><td>${iso(s.ntcIssued) || "—"}</td><td>${iso(s.ntcExpiry) || "—"}</td><td>${st.label}</td></tr>`; }).join("")}
      </table></body></html>`);
    w.document.close();
    setTimeout(() => w.print(), 350);
  }

  function openLicence(s: StaffRow) {
    setLicenceForm({ staffId: s.id, ntcLicenceNo: s.ntcLicenceNo ?? "", ntcCategory: s.ntcCategory ?? "", ntcIssued: iso(s.ntcIssued), ntcExpiry: iso(s.ntcExpiry) });
    setLicenceDialog(true);
  }

  if (!data) return <LoadingBlock label="Loading the NTC licence register…" />;

  const staffPick = (
    <Select value={activityForm.staffId} onValueChange={(v) => setActivityForm((f) => ({ ...f, staffId: v }))}>
      <SelectTrigger><SelectValue placeholder="Select staff" /></SelectTrigger>
      <SelectContent className="max-h-72">
        {data.staff.filter((s) => s.active).map((s) => (
          <SelectItem key={s.id} value={s.id}>{s.fullName} · {s.category === "TEACHING" ? "Teacher" : s.category === "ADMINISTRATION" ? "Admin" : "Non-teaching"}</SelectItem>
        ))}
      </SelectContent>
    </Select>
  );

  return (
    <div className="space-y-4">
      <ModuleHeader
        title="NTC Licences & CPD"
        description="Ghana National Teaching Council register — renewable teacher licences, in-service CPD points and cycle targets."
        actions={
          <>
            <Button variant="outline" className="gap-1.5" onClick={printRegister}><Printer className="size-4" /> Print register</Button>
            <Button className="gap-1.5" onClick={() => { setActivityForm(emptyActivity); setActivityDialog(true); }}><Plus className="size-4" /> Log activity</Button>
          </>
        }
      />

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList>
          <TabsTrigger value="licences" className="gap-1.5"><BadgeCheck className="size-4" /> Licence register</TabsTrigger>
          <TabsTrigger value="log" className="gap-1.5"><NotebookPen className="size-4" /> CPD log</TabsTrigger>
          <TabsTrigger value="board" className="gap-1.5"><Target className="size-4" /> Points board</TabsTrigger>
        </TabsList>

        {/* ── licence register ─────────────────────────────────── */}
        <TabsContent value="licences" className="space-y-4">
          <div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
            <StatCard icon={<GraduationCap className="size-4" />} label="Teaching staff" value={stats.teaching} sub="active on roll" />
            <StatCard icon={<BadgeCheck className="size-4" />} label="Licensed" value={stats.valid} sub="valid >90 days" tone="primary" />
            <StatCard icon={<BadgeCheck className="size-4" />} label="Expiring ≤90d" value={stats.expiring} sub="renewal window" tone="gold" />
            <StatCard icon={<BadgeCheck className="size-4" />} label="Critical ≤30d" value={stats.critical} sub="urgent renewal" tone="danger" />
            <StatCard icon={<BadgeCheck className="size-4" />} label="Expired" value={stats.expired} sub="cannot teach" tone="danger" />
            <StatCard icon={<BadgeCheck className="size-4" />} label="Not captured" value={stats.none} sub="no record yet" />
          </div>

          <FilterBar label="Licence filters" count={licenceRows.length} total={data.staff.length} active={licFilter !== "ALL" || !!search} onClear={() => { setLicFilter("ALL"); setSearch(""); }}
            actions={
              <Button variant="outline" size="sm" className="gap-1.5"
                onClick={() => downloadCsv("ntc-licence-register.csv", ["Staff", "Category", "LicenceNo", "NTC category", "Issued", "Expiry", "Status"],
                  licenceRows.map((s) => [s.fullName, s.category, s.ntcLicenceNo ?? "", s.ntcCategory ?? "", iso(s.ntcIssued), iso(s.ntcExpiry), licenceStatus(s.ntcExpiry).label]))}>
                <Download className="size-4" /> CSV
              </Button>
            }>
            <FilterSearch value={search} onChange={setSearch} placeholder="Search staff or licence №…" />
            <FilterSelect label="" value={licFilter} onChange={setLicFilter} options={[
              { value: "ALL", label: "All statuses" }, { value: "VALID", label: "Valid" }, { value: "EXPIRING", label: "Expiring ≤90d" },
              { value: "CRITICAL", label: "Critical ≤30d" }, { value: "EXPIRED", label: "Expired" }, { value: "NONE", label: "Not captured" },
            ]} />
          </FilterBar>

          <Card>
            <CardContent className="p-0">
              {licenceRows.length === 0 ? (
                <div className="p-6"><EmptyState icon={<BadgeCheck className="size-8" />} title="No staff match" hint="Adjust the filters or search." /></div>
              ) : (
                <div className="max-h-[520px] overflow-y-auto">
                  <Table>
                    <TableHeader><TableRow>
                      <TableHead>Staff</TableHead><TableHead>Licence №</TableHead><TableHead>NTC category</TableHead>
                      <TableHead>Issued</TableHead><TableHead>Expiry</TableHead><TableHead>Status</TableHead><TableHead className="text-right">Record</TableHead>
                    </TableRow></TableHeader>
                    <TableBody>
                      {licenceRows.map((s) => {
                        const st = licenceStatus(s.ntcExpiry);
                        return (
                          <TableRow key={s.id} className={cn(!s.active && "opacity-50")}>
                            <TableCell>
                              <div className="font-medium">{s.fullName}</div>
                              <div className="text-xs text-muted-foreground">{s.category === "TEACHING" ? "Teacher" : s.category === "ADMINISTRATION" ? "Administration" : "Non-teaching"}{s.qualification ? ` · ${s.qualification}` : ""}</div>
                            </TableCell>
                            <TableCell className="font-mono text-xs">{s.ntcLicenceNo ?? "—"}</TableCell>
                            <TableCell>{s.ntcCategory ?? "—"}</TableCell>
                            <TableCell className="text-xs">{iso(s.ntcIssued) || "—"}</TableCell>
                            <TableCell className="text-xs">{iso(s.ntcExpiry) || "—"}</TableCell>
                            <TableCell><Badge className={`${st.cls} text-white`}>{st.label}</Badge></TableCell>
                            <TableCell className="text-right"><Button variant="ghost" size="sm" className="gap-1.5" onClick={() => openLicence(s)}><Pencil className="size-3.5" /> Licence</Button></TableCell>
                          </TableRow>
                        );
                      })}
                    </TableBody>
                  </Table>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── CPD log ──────────────────────────────────────────── */}
        <TabsContent value="log" className="space-y-4">
          <FilterBar label="CPD filters" count={activityRows.length} total={data.activities.length} active={logStaff !== "ALL" || logCat !== "ALL" || logStatus !== "ALL" || !!search}
            onClear={() => { setLogStaff("ALL"); setLogCat("ALL"); setLogStatus("ALL"); setSearch(""); }}
            actions={
              <Button variant="outline" size="sm" className="gap-1.5"
                onClick={() => downloadCsv("cpd-log.csv", ["Date", "Staff", "Activity", "Provider", "Category", "Points", "Hours", "Status", "Verified by"],
                  activityRows.map((a) => [iso(a.activityDate), a.staff.fullName, a.title, a.provider ?? "", CAT_LABEL[a.category] ?? a.category, a.points, a.hours ?? "", a.status, a.verifiedByName ?? ""]))}>
                <Download className="size-4" /> CSV
              </Button>
            }>
            <FilterSelect label="" value={logStaff} onChange={setLogStaff} options={[{ value: "ALL", label: "All staff" }, ...data.staff.map((s) => ({ value: s.id, label: s.fullName }))]} />
            <FilterSelect label="" value={logCat} onChange={setLogCat} options={[{ value: "ALL", label: "All categories" }, { value: "MANDATORY", label: "Mandatory" }, { value: "RANK", label: "Rank-based" }, { value: "RECOMMENDED", label: "Recommended" }]} />
            <FilterSelect label="" value={logStatus} onChange={setLogStatus} options={[{ value: "ALL", label: "All statuses" }, { value: "LOGGED", label: "Logged" }, { value: "VERIFIED", label: "Verified" }, { value: "REJECTED", label: "Rejected" }]} />
            <FilterSearch value={search} onChange={setSearch} placeholder="Search activity, provider…" />
          </FilterBar>

          <Card>
            <CardContent className="p-0">
              {activityRows.length === 0 ? (
                <div className="p-6"><EmptyState icon={<NotebookPen className="size-8" />} title="No CPD activities yet" hint="Log the first in-service training with ‘Log activity’." /></div>
              ) : (
                <div className="max-h-[560px] overflow-y-auto">
                  <Table>
                    <TableHeader><TableRow>
                      <TableHead>Date</TableHead><TableHead>Staff</TableHead><TableHead>Activity</TableHead><TableHead>Category</TableHead>
                      <TableHead>Points</TableHead><TableHead>Status</TableHead><TableHead className="text-right">Actions</TableHead>
                    </TableRow></TableHeader>
                    <TableBody>
                      {activityRows.map((a) => (
                        <TableRow key={a.id}>
                          <TableCell className="whitespace-nowrap text-xs">{fmtDate(a.activityDate)}</TableCell>
                          <TableCell className="font-medium">{a.staff.fullName}</TableCell>
                          <TableCell>
                            <div className="font-medium">{a.title}</div>
                            <div className="text-xs text-muted-foreground">{[a.provider, a.hours != null ? `${a.hours}h` : null, a.evidence].filter(Boolean).join(" · ") || "—"}</div>
                          </TableCell>
                          <TableCell><Badge variant="outline">{CAT_LABEL[a.category] ?? a.category}</Badge></TableCell>
                          <TableCell className="font-semibold">{a.points}</TableCell>
                          <TableCell>
                            <Badge className={a.status === "VERIFIED" ? "bg-emerald-600" : a.status === "REJECTED" ? "bg-rose-600" : "bg-zinc-500"}>
                              {a.status === "VERIFIED" && a.verifiedByName ? `Verified · ${a.verifiedByName}` : a.status}
                            </Badge>
                          </TableCell>
                          <TableCell className="text-right">
                            <div className="flex justify-end gap-1">
                              {a.status === "LOGGED" && (
                                <>
                                  <Button variant="ghost" size="sm" className="gap-1 text-emerald-600" disabled={busy} onClick={() => act({ action: "verify", id: a.id }, "Activity verified — points now count")}><ShieldCheck className="size-3.5" /> Verify</Button>
                                  <Button variant="ghost" size="sm" className="gap-1 text-rose-600" disabled={busy} onClick={() => act({ action: "reject", id: a.id }, "Activity rejected")}><Ban className="size-3.5" /> Reject</Button>
                                </>
                              )}
                              <Button variant="ghost" size="sm" className="text-rose-600" disabled={busy} onClick={() => act({ action: "delete-activity", id: a.id }, "Activity deleted")}><Trash2 className="size-3.5" /></Button>
                            </div>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── points board ─────────────────────────────────────── */}
        <TabsContent value="board" className="space-y-4">
          <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
            <StatCard icon={<Target className="size-4" />} label="Cycle target" value={data.setting.targetMandatory + data.setting.targetRank + data.setting.targetRecommended} sub={`${data.setting.cycleYears}-year NTC cycle`} tone="primary" />
            <StatCard icon={<ShieldCheck className="size-4" />} label="On track" value={`${onTrack}/${boardRows.length}`} sub="target met (verified)" tone="gold" />
            <StatCard icon={<NotebookPen className="size-4" />} label="Awaiting verification" value={data.activities.filter((a) => a.status === "LOGGED").length} sub="logged, not yet counted" />
            <StatCard icon={<Search className="size-4" />} label="Verified points" value={boardRows.reduce((s, r) => s + r.sum.total, 0)} sub="school-wide this cycle" />
          </div>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-base">Verified points per teacher</CardTitle>
              <div className="flex items-center gap-2">
                <FilterSelect label="" value={boardFilter} onChange={setBoardFilter} options={[{ value: "TEACHING", label: "Teaching staff" }, { value: "ALL", label: "All staff" }]} />
                <Button variant="outline" size="sm" className="gap-1.5" onClick={() => { setTargetsForm(data.setting); setTargetsDialog(true); }}><Target className="size-4" /> Targets</Button>
              </div>
            </CardHeader>
            <CardContent className="p-0">
              {boardRows.length === 0 ? (
                <div className="p-6"><EmptyState icon={<Target className="size-8" />} title="No staff to show" hint="Switch to ‘All staff’ or add staff records." /></div>
              ) : (
                <div className="max-h-[560px] overflow-y-auto">
                  <Table>
                    <TableHeader><TableRow>
                      <TableHead>Staff</TableHead><TableHead>Mandatory</TableHead><TableHead>Rank-based</TableHead>
                      <TableHead>Recommended</TableHead><TableHead>Total</TableHead><TableHead className="w-56">Cycle progress</TableHead>
                    </TableRow></TableHeader>
                    <TableBody>
                      {boardRows.map(({ staff: s, sum, targetTotal, pct }) => (
                        <TableRow key={s.id}>
                          <TableCell>
                            <div className="font-medium">{s.fullName}</div>
                            <div className="text-xs text-muted-foreground">
                              {sum.cycle.usesLicenceDates ? `licence cycle ${iso(sum.cycle.start)} → ${iso(sum.cycle.end)}` : `rolling ${data.setting.cycleYears}-year window`}
                              {sum.logged > 0 ? ` · ${sum.logged} awaiting verification` : ""}
                            </div>
                          </TableCell>
                          <TableCell>{sum.points.MANDATORY} <span className="text-xs text-muted-foreground">/ {data.setting.targetMandatory}</span></TableCell>
                          <TableCell>{sum.points.RANK} <span className="text-xs text-muted-foreground">/ {data.setting.targetRank}</span></TableCell>
                          <TableCell>{sum.points.RECOMMENDED} <span className="text-xs text-muted-foreground">/ {data.setting.targetRecommended}</span></TableCell>
                          <TableCell className="font-semibold">{sum.total} <span className="text-xs text-muted-foreground">/ {targetTotal}</span></TableCell>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <Progress value={pct} className="h-2" />
                              <span className={cn("w-10 text-right text-xs font-medium", pct >= 100 ? "text-emerald-600" : pct >= 60 ? "text-amber-600" : "text-rose-600")}>{pct}%</span>
                            </div>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* ── licence record dialog ─────────────────────────────── */}
      <Dialog open={licenceDialog} onOpenChange={setLicenceDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>NTC licence record</DialogTitle>
            <DialogDescription>{data.staff.find((s) => s.id === licenceForm.staffId)?.fullName ?? ""} — licence number, category and validity window.</DialogDescription>
          </DialogHeader>
          <FormGrid cols={2}>
            <FormField label="Licence number" span={2}><Input value={licenceForm.ntcLicenceNo} onChange={(e) => setLicenceForm((f) => ({ ...f, ntcLicenceNo: e.target.value }))} placeholder="e.g. PT/0123456" /></FormField>
            <FormField label="NTC category">
              <Select value={licenceForm.ntcCategory || "none"} onValueChange={(v) => setLicenceForm((f) => ({ ...f, ntcCategory: v === "none" ? "" : v }))}>
                <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                <SelectContent>{LICENCE_CATEGORIES.map((c) => <SelectItem key={c} value={c}>{c}</SelectItem>)}</SelectContent>
              </Select>
            </FormField>
            <FormField label="Issued" hint="yyyy-mm-dd"><Input type="date" value={licenceForm.ntcIssued} onChange={(e) => setLicenceForm((f) => ({ ...f, ntcIssued: e.target.value }))} /></FormField>
            <FormField label="Expiry" hint="yyyy-mm-dd — licences run 3 years"><Input type="date" value={licenceForm.ntcExpiry} onChange={(e) => setLicenceForm((f) => ({ ...f, ntcExpiry: e.target.value }))} /></FormField>
          </FormGrid>
          <DialogFooter>
            <Button variant="outline" onClick={() => setLicenceDialog(false)}>Cancel</Button>
            <Button disabled={busy} onClick={async () => { if (await act({ action: "save-licence", ...licenceForm }, "Licence record saved")) setLicenceDialog(false); }}>Save record</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── log activity dialog ───────────────────────────────── */}
      <Dialog open={activityDialog} onOpenChange={setActivityDialog}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>Log a CPD activity</DialogTitle>
            <DialogDescription>In-service training, workshop or portfolio programme. Points count once verified.</DialogDescription>
          </DialogHeader>
          <FormGrid cols={2}>
            <FormField label="Staff" required span={2}>{staffPick}</FormField>
            <FormField label="Activity title" required span={2}><Input value={activityForm.title} onChange={(e) => setActivityForm((f) => ({ ...f, title: e.target.value }))} placeholder="e.g. GES Standard-based Curriculum refresher" /></FormField>
            <FormField label="Provider"><Input value={activityForm.provider} onChange={(e) => setActivityForm((f) => ({ ...f, provider: e.target.value }))} placeholder="e.g. University of Cape Coast" /></FormField>
            <FormField label="Category">
              <Select value={activityForm.category} onValueChange={(v) => setActivityForm((f) => ({ ...f, category: v }))}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="MANDATORY">Mandatory</SelectItem>
                  <SelectItem value="RANK">Rank-based</SelectItem>
                  <SelectItem value="RECOMMENDED">Recommended</SelectItem>
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="Date"><Input type="date" value={activityForm.activityDate} onChange={(e) => setActivityForm((f) => ({ ...f, activityDate: e.target.value }))} /></FormField>
            <FormField label="Points" hint="NTC-awarded points"><Input type="number" min="0" step="0.5" value={activityForm.points} onChange={(e) => setActivityForm((f) => ({ ...f, points: e.target.value }))} /></FormField>
            <FormField label="Contact hours"><Input type="number" min="0" step="0.5" value={activityForm.hours} onChange={(e) => setActivityForm((f) => ({ ...f, hours: e.target.value }))} /></FormField>
            <FormField label="Evidence" hint="certificate № or proof"><Input value={activityForm.evidence} onChange={(e) => setActivityForm((f) => ({ ...f, evidence: e.target.value }))} placeholder="e.g. NTC cert 2026/118" /></FormField>
            <FormField label="Note" span={2}><Input value={activityForm.note} onChange={(e) => setActivityForm((f) => ({ ...f, note: e.target.value }))} placeholder="optional" /></FormField>
          </FormGrid>
          <DialogFooter>
            <Button variant="outline" onClick={() => setActivityDialog(false)}>Cancel</Button>
            <Button disabled={busy} onClick={async () => { if (await act({ action: "log-activity", ...activityForm, hours: activityForm.hours === "" ? null : activityForm.hours }, "CPD activity logged — awaiting verification")) { setActivityDialog(false); setTab("log"); } }}>Log activity</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── cycle targets dialog ──────────────────────────────── */}
      <Dialog open={targetsDialog} onOpenChange={setTargetsDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>NTC cycle targets</DialogTitle>
            <DialogDescription>Points the school expects per teacher over one licence cycle. NTC defaults: 20 mandatory / 40 rank / 40 recommended over 3 years.</DialogDescription>
          </DialogHeader>
          <FormGrid cols={2}>
            <FormField label="Mandatory points"><Input type="number" min="0" step="0.5" value={targetsForm.targetMandatory} onChange={(e) => setTargetsForm((f) => ({ ...f, targetMandatory: Number(e.target.value) }))} /></FormField>
            <FormField label="Rank-based points"><Input type="number" min="0" step="0.5" value={targetsForm.targetRank} onChange={(e) => setTargetsForm((f) => ({ ...f, targetRank: Number(e.target.value) }))} /></FormField>
            <FormField label="Recommended points"><Input type="number" min="0" step="0.5" value={targetsForm.targetRecommended} onChange={(e) => setTargetsForm((f) => ({ ...f, targetRecommended: Number(e.target.value) }))} /></FormField>
            <FormField label="Cycle length (years)"><Input type="number" min="1" max="6" value={targetsForm.cycleYears} onChange={(e) => setTargetsForm((f) => ({ ...f, cycleYears: Number(e.target.value) }))} /></FormField>
          </FormGrid>
          <DialogFooter>
            <Button variant="outline" onClick={() => setTargetsDialog(false)}>Cancel</Button>
            <Button disabled={busy} onClick={async () => { if (await act({ action: "save-targets", ...targetsForm }, "Cycle targets saved")) setTargetsDialog(false); }}>Save targets</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

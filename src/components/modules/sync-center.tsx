"use client";

/* ── wave 39 — Sync Center (CI3 sync_dashboard / sync_devices / sync_conflicts /
 *    sync_conflict_merge / sync_audit_trail / sync_metrics parity) ────────────
 *
 * The offline empire's control room. The PWA offline queue lets cashiers,
 * teachers and the front desk keep working through network blackouts; this
 * module supervises the pipeline end-to-end:
 *
 *   Dashboard — health score, today/7-day KPIs, 14-day flush timeline, the
 *               devices' last sync, latest conflicts and journal entries
 *   Devices   — register POS tablets / scanners / workstations, watch their
 *               lifetime ops, and REVOKE a lost or compromised device so its
 *               flushes are refused outright (the kill switch)
 *   Conflicts — when an offline replay collides with newer server state
 *               (marks re-entered online, a register taken at the counter),
 *               both sides are frozen side-by-side and an adjudicator picks:
 *               local wins (replay), server wins (discard), or ignore
 *   Journal   — the sync audit book: every flush with counts, duration,
 *               device and user; device registrations/revocations; conflict
 *               resolutions
 *   Policy    — per-kind sync policy: disable a queueable op kind school-wide
 *               and its flushes are refused (CI3 sync_table_management parity)
 * ──────────────────────────────────────────────────────────────────────── */

import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Textarea } from "@/components/ui/textarea";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard } from "@/components/ui-helpers";
import { fmtDateTime, fmtAgo } from "@/lib/format";
import { cn } from "@/lib/utils";
import { toast } from "@/hooks/use-toast";
import { canAny } from "@/lib/rbac";
import {
  ResponsiveContainer, BarChart, Bar, XAxis, YAxis, Tooltip, CartesianGrid, Legend,
} from "recharts";
import {
  RefreshCcw, Laptop, ScanLine, TabletSmartphone, MonitorCheck, Users, ShieldCheck,
  ShieldOff, Plus, TriangleAlert, GitMerge, History, Activity, Smartphone, PencilLine,
  CheckCircle2, XCircle, CloudOff, Gauge,
} from "lucide-react";

const OPS = "/ops/sync";

type Device = {
  id: string; code: string; name: string; kind: string; status: string;
  lastSyncAt: string | null; lastSeenAt: string | null; lastIP: string | null;
  lastUser: string | null; opsSynced: number; opsFailed: number; notes: string | null;
  createdAt: string;
};
type ConflictRow = {
  id: string; deviceCode: string | null; localUser: string; table: string; recordKey: string;
  localAt: string; remoteAt: string; status: string; resolution: string | null;
  resolvedBy: string | null; resolvedAt: string | null; resolveNote: string | null;
  createdAt: string; diffRows: { name: string; local: string; remote: string }[];
  remoteTakenBy: string | null;
};
type EventRow = {
  id: string; deviceCode: string | null; userName: string; kind: string;
  opsSynced: number; opsFailed: number; conflicts: number; durationMs: number | null;
  detail: string | null; createdAt: string;
};
type Dash = {
  devices: Device[];
  kpi: {
    todaySynced: number; todayFailed: number; synced7: number; failed7: number;
    conflictsDetected7: number; conflictsPending: number; activeDevices: number;
    revoked: number; lastFlushAt: string | null; lastFlushUser: string | null;
  };
  timeline: { date: string; synced: number; failed: number; conflicts: number; flushes: number }[];
  policy: Record<string, boolean>;
  health: number;
  recentConflicts: { id: string; recordKey: string; table: string; status: string; localUser: string; createdAt: string }[];
  recentEvents: EventRow[];
};

const KIND_META: Record<string, { label: string; icon: any }> = {
  POS_TABLET: { label: "POS tablet", icon: TabletSmartphone },
  SCANNER: { label: "Attendance scanner", icon: ScanLine },
  FRONT_DESK: { label: "Front desk", icon: MonitorCheck },
  BROWSER: { label: "Workstation browser", icon: Laptop },
  OTHER: { label: "Other device", icon: Smartphone },
};

const EVENT_TONE: Record<string, string> = {
  FLUSH: "bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300",
  REGISTER: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  REVOKE: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300",
  RESOLVE: "bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300",
};

function healthTone(score: number) {
  if (score >= 90) return "text-emerald-600 dark:text-emerald-400";
  if (score >= 70) return "text-amber-600 dark:text-amber-400";
  return "text-red-600 dark:text-red-400";
}

export function SyncCenter() {
  const { currentSchoolId, session, navTarget, online, queue } = useStore();
  const schoolId = currentSchoolId ?? "";
  const perms = session?.permissions ?? [];
  const canManage = canAny(perms, ["sync.manage"]);

  const [tab, setTab] = useState("dashboard");
  const [dash, setDash] = useState<Dash | null>(null);
  const [devices, setDevices] = useState<Device[] | null>(null);
  const [conflicts, setConflicts] = useState<{ pending: ConflictRow[]; resolved: ConflictRow[] } | null>(null);
  const [events, setEvents] = useState<EventRow[] | null>(null);
  const [eventKind, setEventKind] = useState("all");
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);

  // register / edit device dialog
  const [devDlg, setDevDlg] = useState<{ mode: "new" | "edit"; dev?: Device } | null>(null);
  const [devName, setDevName] = useState("");
  const [devKind, setDevKind] = useState("POS_TABLET");
  const [devNotes, setDevNotes] = useState("");

  // resolve conflict dialog
  const [resDlg, setResDlg] = useState<ConflictRow | null>(null);
  const [resChoice, setResChoice] = useState<"LOCAL_WINS" | "REMOTE_WINS" | "IGNORED">("REMOTE_WINS");
  const [resNote, setResNote] = useState("");

  // policy (local editable copy)
  const [policy, setPolicy] = useState<Record<string, boolean> | null>(null);

  useEffect(() => {
    if (navTarget?.tab && ["dashboard", "devices", "conflicts", "journal", "policy"].includes(navTarget.tab)) setTab(navTarget.tab);
  }, [navTarget]);

  const loadDashboard = useCallback(async () => {
    if (!schoolId) return;
    try {
      const res = await apiGet<Dash>(`/api/school/${schoolId}${OPS}`);
      setDash(res);
      setPolicy(res.policy);
    } catch { setDash(null); }
  }, [schoolId]);

  const loadDevices = useCallback(async () => {
    if (!schoolId) return;
    try { setDash((d) => d); const res = await apiGet<Dash>(`/api/school/${schoolId}${OPS}`); setDevices(res.devices); setDash(res); }
    catch { setDevices(null); }
  }, [schoolId]);

  const loadConflicts = useCallback(async () => {
    if (!schoolId) return;
    try { setConflicts(await apiGet<{ pending: ConflictRow[]; resolved: ConflictRow[] }>(`/api/school/${schoolId}${OPS}?action=conflicts`)); }
    catch { setConflicts(null); }
  }, [schoolId]);

  const loadEvents = useCallback(async (kind: string) => {
    if (!schoolId) return;
    try { setEvents((await apiGet<{ events: EventRow[] }>(`/api/school/${schoolId}${OPS}?action=events&kind=${kind}`)).events); }
    catch { setEvents(null); }
  }, [schoolId]);

  useEffect(() => { setLoading(true); Promise.all([loadDashboard(), loadConflicts(), loadEvents("all")]).finally(() => setLoading(false)); }, [loadDashboard, loadConflicts, loadEvents]);
  useEffect(() => { loadEvents(eventKind); }, [eventKind, loadEvents]);

  const timelineData = useMemo(() => (dash?.timeline ?? []).map((t) => ({ ...t, date: t.date.slice(5) })), [dash]);

  async function saveDevice() {
    if (!devDlg || !schoolId) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}${OPS}`, {
        action: "device-save",
        ...(devDlg.mode === "edit" ? { id: devDlg.dev!.id } : {}),
        name: devName.trim(), kind: devKind, notes: devNotes.trim() || null,
      });
      toast({ title: devDlg.mode === "edit" ? "Device updated" : "Device registered", description: devDlg.mode === "edit" ? `${devName} saved.` : `${devName} can now flush offline queues.` });
      setDevDlg(null);
      await Promise.all([loadDashboard(), loadEvents("all")]);
    } catch (e: any) {
      toast({ title: "Could not save device", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  async function toggleDevice(dev: Device) {
    if (!schoolId) return;
    setBusy(true);
    try {
      const res = await apiPost<{ status: string }>(`/api/school/${schoolId}${OPS}`, { action: "device-toggle", id: dev.id });
      toast({ title: res.status === "REVOKED" ? `${dev.code} revoked` : `${dev.code} re-activated`, description: res.status === "REVOKED" ? "Its flushes will be rejected until re-activated." : "The device can flush again." });
      await Promise.all([loadDashboard(), loadEvents("all")]);
    } catch (e: any) {
      toast({ title: "Could not toggle device", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  async function resolveConflict() {
    if (!resDlg || !schoolId) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}${OPS}`, { action: "conflict-resolve", id: resDlg.id, resolution: resChoice, note: resNote.trim() || null });
      const verb = resChoice === "LOCAL_WINS" ? "Queued version replayed" : resChoice === "REMOTE_WINS" ? "Server version kept" : "Conflict ignored";
      toast({ title: "Conflict resolved", description: `${verb} — ${resDlg.recordKey}.` });
      setResDlg(null); setResNote(""); setResChoice("REMOTE_WINS");
      await Promise.all([loadDashboard(), loadConflicts(), loadEvents("all")]);
    } catch (e: any) {
      toast({ title: "Could not resolve", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  async function savePolicy() {
    if (!policy || !schoolId) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}${OPS}`, { action: "policy-save", policy });
      toast({ title: "Sync policy saved", description: "Disabled kinds will be refused at flush time and journaled as failures." });
      await loadDashboard();
    } catch (e: any) {
      toast({ title: "Could not save policy", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  if (loading) return <LoadingBlock label="Loading the sync empire's control room…" />;
  const kpi = dash?.kpi;
  const pending = conflicts?.pending ?? [];
  const resolved = conflicts?.resolved ?? [];

  return (
    <div className="space-y-4">
      <ModuleHeader
        title="Sync Center"
        description="The offline empire's control room — registered devices, flush health, replay conflicts and the sync journal. The offline queue's supervisor (iEduPro sync console parity)."
        actions={
          <div className="flex items-center gap-2">
            <Badge variant={online ? "secondary" : "destructive"} className={cn("gap-1", online ? "" : "animate-pulse")}>
              {online ? <CheckCircle2 className="size-3" /> : <CloudOff className="size-3" />}
              {online ? "Online now" : "Offline"}
            </Badge>
            {queue.length > 0 && <Badge variant="outline" className="gap-1"><RefreshCcw className="size-3" /> {queue.length} queued here</Badge>}
          </div>
        }
      />

      {/* KPI strip — always visible */}
      <div className="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
        <StatCard icon={<Gauge className="size-4" />} label="Sync health" value={<span className={healthTone(dash?.health ?? 0)}>{dash?.health ?? 0}<span className="text-muted-foreground text-sm font-normal">/100</span></span>} sub="2 pts per failed op · 10 per pending conflict" />
        <StatCard icon={<RefreshCcw className="size-4" />} label="Ops synced (7d)" value={kpi?.synced7 ?? 0} sub={`${kpi?.todaySynced ?? 0} today`} />
        <StatCard icon={<XCircle className="size-4" />} label="Ops failed (7d)" value={kpi?.failed7 ?? 0} sub={`${kpi?.todayFailed ?? 0} today`} tone={(kpi?.failed7 ?? 0) > 0 ? "danger" : "default"} />
        <StatCard icon={<TriangleAlert className="size-4" />} label="Pending conflicts" value={pending.length} sub={`${kpi?.conflictsDetected7 ?? 0} detected (7d)`} tone={pending.length ? "gold" : "default"} />
        <StatCard icon={<Laptop className="size-4" />} label="Devices" value={`${kpi?.activeDevices ?? 0} active`} sub={kpi?.revoked ? `${kpi.revoked} revoked` : "none revoked"} />
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex-wrap h-auto">
          <TabsTrigger value="dashboard" className="gap-1.5"><Gauge className="size-4" /> Dashboard</TabsTrigger>
          <TabsTrigger value="devices" className="gap-1.5"><Laptop className="size-4" /> Devices</TabsTrigger>
          <TabsTrigger value="conflicts" className="gap-1.5">
            <TriangleAlert className="size-4" /> Conflicts
            {pending.length > 0 && <Badge className="ml-1 h-5 px-1.5 bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300">{pending.length}</Badge>}
          </TabsTrigger>
          <TabsTrigger value="journal" className="gap-1.5"><History className="size-4" /> Journal</TabsTrigger>
          <TabsTrigger value="policy" className="gap-1.5"><ShieldCheck className="size-4" /> Policy</TabsTrigger>
        </TabsList>

        {/* ── Dashboard ── */}
        <TabsContent value="dashboard" className="space-y-4">
          <div className="grid lg:grid-cols-3 gap-4">
            <Card className="lg:col-span-2">
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Activity className="size-4 text-muted-foreground" /> Flushes — last 14 days</CardTitle></CardHeader>
              <CardContent>
                {timelineData.length ? (
                  <div className="h-56">
                    <ResponsiveContainer width="100%" height="100%">
                      <BarChart data={timelineData} margin={{ top: 4, right: 8, left: -18, bottom: 0 }}>
                        <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                        <XAxis dataKey="date" tick={{ fontSize: 11 }} stroke="hsl(var(--muted-foreground))" />
                        <YAxis allowDecimals={false} tick={{ fontSize: 11 }} stroke="hsl(var(--muted-foreground))" />
                        <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8 }} />
                        <Legend wrapperStyle={{ fontSize: 11 }} />
                        <Bar dataKey="synced" name="Ops synced" stackId="a" fill="#059669" radius={[0, 0, 0, 0]} />
                        <Bar dataKey="failed" name="Ops failed" stackId="a" fill="#dc2626" radius={[3, 3, 0, 0]} />
                      </BarChart>
                    </ResponsiveContainer>
                  </div>
                ) : <EmptyState icon={<RefreshCcw className="size-5" />} title="No flushes in the last 14 days" hint="Queued offline actions land here the moment they are synced." />}
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Laptop className="size-4 text-muted-foreground" /> Devices at a glance</CardTitle></CardHeader>
              <CardContent className="space-y-2">
                {(dash?.devices ?? []).length ? dash!.devices.map((d) => {
                  const meta = KIND_META[d.kind] ?? KIND_META.OTHER;
                  return (
                    <div key={d.id} className="flex items-center justify-between gap-2 rounded-lg border p-2.5">
                      <div className="min-w-0">
                        <div className="flex items-center gap-1.5 text-sm font-medium truncate">
                          <meta.icon className="size-3.5 text-muted-foreground shrink-0" />
                          <span className="truncate">{d.name}</span>
                        </div>
                        <div className="text-[11px] text-muted-foreground">{d.code}{d.lastUser ? ` · ${d.lastUser}` : ""}</div>
                      </div>
                      <div className="text-right shrink-0">
                        <Badge className={d.status === "ACTIVE" ? "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300" : "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300"}>{d.status}</Badge>
                        <div className="text-[11px] text-muted-foreground mt-0.5">{d.lastSyncAt ? fmtAgo(d.lastSyncAt) : "never synced"}</div>
                      </div>
                    </div>
                  );
                }) : <EmptyState icon={<Laptop className="size-5" />} title="No devices registered" hint="Devices register themselves on their first offline flush — or register one ahead of time from the Devices tab." />}
                <div className="pt-1 text-xs text-muted-foreground border-t">
                  Last flush {dash?.kpi.lastFlushAt ? `${fmtAgo(dash.kpi.lastFlushAt)} by ${dash.kpi.lastFlushUser}` : "— none yet"}
                </div>
              </CardContent>
            </Card>
          </div>

          <div className="grid lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><TriangleAlert className="size-4 text-muted-foreground" /> Latest conflicts</CardTitle></CardHeader>
              <CardContent className="space-y-2">
                {(dash?.recentConflicts ?? []).length ? dash!.recentConflicts.map((c) => (
                  <div key={c.id} className="flex items-center justify-between gap-2 rounded-lg border p-2.5 text-sm">
                    <div className="min-w-0">
                      <div className="font-medium truncate">{c.recordKey}</div>
                      <div className="text-[11px] text-muted-foreground">{c.table} · queued by {c.localUser} · {fmtAgo(c.createdAt)}</div>
                    </div>
                    <Badge className={c.status === "PENDING" ? "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300" : "bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300"}>{c.status.toLowerCase()}</Badge>
                  </div>
                )) : <EmptyState icon={<CheckCircle2 className="size-5" />} title="No conflicts on record" hint="Collisions between offline replays and newer server data appear here." />}
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><History className="size-4 text-muted-foreground" /> Sync journal — latest</CardTitle></CardHeader>
              <CardContent className="space-y-2 max-h-72 overflow-y-auto">
                {(dash?.recentEvents ?? []).length ? dash!.recentEvents.map((e) => (
                  <div key={e.id} className="flex items-center justify-between gap-2 rounded-lg border p-2.5 text-sm">
                    <div className="min-w-0">
                      <div className="flex items-center gap-1.5">
                        <Badge className={EVENT_TONE[e.kind] ?? ""}>{e.kind.toLowerCase()}</Badge>
                        <span className="text-muted-foreground text-xs truncate">{e.deviceCode ?? "manual"}</span>
                      </div>
                      <div className="text-[11px] text-muted-foreground truncate">{e.detail ?? "—"}</div>
                    </div>
                    <div className="text-right shrink-0 text-[11px] text-muted-foreground">
                      {e.userName}<br />{fmtAgo(e.createdAt)}
                    </div>
                  </div>
                )) : <EmptyState icon={<History className="size-5" />} title="Journal empty" hint="Every flush, registration and resolution lands here." />}
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* ── Devices ── */}
        <TabsContent value="devices" className="space-y-4">
          <div className="flex items-center justify-between gap-2">
            <p className="text-sm text-muted-foreground">
              Devices that hold offline queues. Browsers self-register on their first flush; POS tablets and scanners can be registered ahead of time. Revoking a device refuses its future flushes outright.
            </p>
            {canManage && (
              <Button size="sm" className="gap-1.5 shrink-0" onClick={() => { setDevDlg({ mode: "new" }); setDevName(""); setDevKind("POS_TABLET"); setDevNotes(""); }}>
                <Plus className="size-4" /> Register device
              </Button>
            )}
          </div>
          <Card>
            <CardContent className="p-0">
              {(devices ?? dash?.devices ?? []).length ? (
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Device</TableHead>
                        <TableHead>Kind</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Last sync</TableHead>
                        <TableHead>Lifetime ops</TableHead>
                        <TableHead>Last user</TableHead>
                        <TableHead className="text-right">Actions</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {(devices ?? dash?.devices ?? []).map((d) => {
                        const meta = KIND_META[d.kind] ?? KIND_META.OTHER;
                        return (
                          <TableRow key={d.id}>
                            <TableCell>
                              <div className="font-medium flex items-center gap-1.5"><meta.icon className="size-3.5 text-muted-foreground" />{d.name}</div>
                              <div className="text-[11px] text-muted-foreground">{d.code}{d.lastIP ? ` · ${d.lastIP}` : ""}{d.notes ? ` · ${d.notes}` : ""}</div>
                            </TableCell>
                            <TableCell><Badge variant="outline">{meta.label}</Badge></TableCell>
                            <TableCell>
                              <Badge className={d.status === "ACTIVE" ? "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300" : "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300"}>{d.status.toLowerCase()}</Badge>
                            </TableCell>
                            <TableCell className="text-sm">
                              {d.lastSyncAt ? fmtDateTime(d.lastSyncAt) : <span className="text-muted-foreground">never</span>}
                              {d.lastSeenAt && d.lastSeenAt !== d.lastSyncAt && <div className="text-[11px] text-muted-foreground">seen {fmtAgo(d.lastSeenAt)}</div>}
                            </TableCell>
                            <TableCell className="text-sm">
                              <span className="text-emerald-700 dark:text-emerald-400">{d.opsSynced} synced</span>
                              {d.opsFailed > 0 && <span className="text-red-600 dark:text-red-400"> · {d.opsFailed} failed</span>}
                            </TableCell>
                            <TableCell className="text-sm">{d.lastUser ?? "—"}</TableCell>
                            <TableCell className="text-right">
                              {canManage && (
                                <div className="flex justify-end gap-1.5">
                                  <Button variant="ghost" size="icon" className="size-8" title="Rename / edit notes"
                                    onClick={() => { setDevDlg({ mode: "edit", dev: d }); setDevName(d.name); setDevKind(d.kind); setDevNotes(d.notes ?? ""); }}>
                                    <PencilLine className="size-4" />
                                  </Button>
                                  <Button variant="ghost" size="icon" className="size-8" disabled={busy} title={d.status === "ACTIVE" ? "Revoke — refuse this device's flushes" : "Re-activate"}
                                    onClick={() => toggleDevice(d)}>
                                    {d.status === "ACTIVE" ? <ShieldOff className="size-4 text-red-500" /> : <ShieldCheck className="size-4 text-emerald-500" />}
                                  </Button>
                                </div>
                              )}
                            </TableCell>
                          </TableRow>
                        );
                      })}
                    </TableBody>
                  </Table>
                </div>
              ) : (
                <EmptyState icon={<Laptop className="size-6" />} title="No devices registered yet" hint={canManage ? "Register the POS tablets and scanners that work offline — or just wait: browsers self-register on their first flush." : "Browsers self-register on their first offline flush."} />
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── Conflicts ── */}
        <TabsContent value="conflicts" className="space-y-4">
          <p className="text-sm text-muted-foreground">
            When an offline replay collides with newer server state — marks re-entered online, a register already taken at the counter — both versions are frozen side-by-side here. An adjudicator decides which side wins.
          </p>
          {pending.length ? pending.map((c) => (
            <Card key={c.id} className="border-amber-300 dark:border-amber-800">
              <CardHeader className="pb-2">
                <CardTitle className="text-base flex flex-wrap items-center gap-2">
                  <TriangleAlert className="size-4 text-amber-500" />
                  {c.recordKey}
                  <Badge variant="outline">{c.table}</Badge>
                  {c.deviceCode && <Badge variant="outline">{c.deviceCode}</Badge>}
                </CardTitle>
                <div className="text-xs text-muted-foreground">
                  Queued by <strong>{c.localUser}</strong> {fmtAgo(c.localAt)} · server version {c.remoteTakenBy ? `taken by ${c.remoteTakenBy}` : "re-entered"} {fmtAgo(c.remoteAt)}
                </div>
              </CardHeader>
              <CardContent className="space-y-3">
                {c.diffRows.length > 0 && (
                  <div className="overflow-x-auto rounded-lg border">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Learner</TableHead>
                          <TableHead className="w-1/3">Queued (this device)</TableHead>
                          <TableHead className="w-1/3">On the server</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {c.diffRows.map((r, i) => (
                          <TableRow key={i}>
                            <TableCell className="font-medium">{r.name}</TableCell>
                            <TableCell className={cn("font-mono", r.local !== r.remote ? "bg-amber-50/60 dark:bg-amber-950/30" : "text-muted-foreground")}>{r.local}</TableCell>
                            <TableCell className={cn("font-mono", r.local !== r.remote ? "bg-sky-50/60 dark:bg-sky-950/30" : "text-muted-foreground")}>{r.remote}</TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                )}
                {canManage && (
                  <div className="flex justify-end">
                    <Button size="sm" variant="outline" className="gap-1.5" onClick={() => { setResDlg(c); setResChoice("REMOTE_WINS"); setResNote(""); }}>
                      <GitMerge className="size-4" /> Resolve…
                    </Button>
                  </div>
                )}
              </CardContent>
            </Card>
          )) : (
            <EmptyState icon={<CheckCircle2 className="size-6" />} title="No pending conflicts" hint="Every offline replay has matched the server's state — or the queue has not collided yet." />
          )}

          {resolved.length > 0 && (
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base">Resolution history</CardTitle></CardHeader>
              <CardContent className="p-0">
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Record</TableHead>
                        <TableHead>Outcome</TableHead>
                        <TableHead>Resolved by</TableHead>
                        <TableHead>When</TableHead>
                        <TableHead>Note</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {resolved.map((r) => (
                        <TableRow key={r.id}>
                          <TableCell className="font-medium">{r.recordKey}<div className="text-[11px] text-muted-foreground">{r.table} · {r.localUser}</div></TableCell>
                          <TableCell>
                            <Badge className={r.resolution === "LOCAL_WINS" ? "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300" : r.resolution === "REMOTE_WINS" ? "bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300" : "bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300"}>
                              {(r.resolution ?? "").replace("_", " ").toLowerCase()}
                            </Badge>
                          </TableCell>
                          <TableCell className="text-sm">{r.resolvedBy}</TableCell>
                          <TableCell className="text-sm">{r.resolvedAt ? fmtDateTime(r.resolvedAt) : "—"}</TableCell>
                          <TableCell className="text-sm text-muted-foreground max-w-xs">{r.resolveNote ?? "—"}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        {/* ── Journal ── */}
        <TabsContent value="journal" className="space-y-4">
          <div className="flex items-center gap-2">
            <Select value={eventKind} onValueChange={setEventKind}>
              <SelectTrigger className="w-44"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All entries</SelectItem>
                <SelectItem value="FLUSH">Flushes</SelectItem>
                <SelectItem value="REGISTER">Registrations</SelectItem>
                <SelectItem value="REVOKE">Revocations</SelectItem>
                <SelectItem value="RESOLVE">Resolutions</SelectItem>
              </SelectContent>
            </Select>
            <p className="text-sm text-muted-foreground">The sync audit book — every flush with counts and duration, every device lifecycle act, every adjudication.</p>
          </div>
          <Card>
            <CardContent className="p-0">
              {(events ?? []).length ? (
                <div className="overflow-x-auto max-h-[28rem] overflow-y-auto">
                  <Table>
                    <TableHeader className="sticky top-0 bg-background z-10">
                      <TableRow>
                        <TableHead>When</TableHead>
                        <TableHead>Kind</TableHead>
                        <TableHead>Device</TableHead>
                        <TableHead>User</TableHead>
                        <TableHead>Ops</TableHead>
                        <TableHead>Conflicts</TableHead>
                        <TableHead>Duration</TableHead>
                        <TableHead>Detail</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {events!.map((e) => (
                        <TableRow key={e.id}>
                          <TableCell className="text-sm whitespace-nowrap">{fmtDateTime(e.createdAt)}</TableCell>
                          <TableCell><Badge className={EVENT_TONE[e.kind] ?? ""}>{e.kind.toLowerCase()}</Badge></TableCell>
                          <TableCell className="text-sm font-mono text-xs">{e.deviceCode ?? "—"}</TableCell>
                          <TableCell className="text-sm">{e.userName}</TableCell>
                          <TableCell className="text-sm">
                            <span className="text-emerald-700 dark:text-emerald-400">{e.opsSynced}</span>
                            {e.opsFailed > 0 && <span className="text-red-600 dark:text-red-400"> / {e.opsFailed} failed</span>}
                          </TableCell>
                          <TableCell className="text-sm">{e.conflicts || "—"}</TableCell>
                          <TableCell className="text-sm">{e.durationMs != null ? `${e.durationMs} ms` : "—"}</TableCell>
                          <TableCell className="text-sm text-muted-foreground max-w-sm truncate" title={e.detail ?? ""}>{e.detail ?? "—"}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              ) : <EmptyState icon={<History className="size-6" />} title="No journal entries" hint="Flushes, device registrations and conflict resolutions land here." />}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── Policy ── */}
        <TabsContent value="policy" className="space-y-4">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-base flex items-center gap-2"><ShieldCheck className="size-4 text-muted-foreground" /> Queueable op policy</CardTitle>
              <p className="text-sm text-muted-foreground">
                School-wide sync policy (the iEduPro table-sync toggles): a disabled kind is refused the moment a flush tries it — the op is journaled as failed and stays on the device. Clients can still queue it offline, so disable a kind only when you really mean it.
              </p>
            </CardHeader>
            <CardContent className="space-y-3">
              {[
                { k: "payment", t: "Payments & collections", d: "Fee receipts, POS collections, transport fares and daily-service charges captured offline." },
                { k: "attendance", t: "Attendance registers", d: "Class register marks (and the scanner's check-ins) captured offline." },
                { k: "marks", t: "Assessment marks", d: "Gradebook scores entered offline (SBA, class tests, exams)." },
                { k: "student", t: "Learner enrolment", d: "New admissions captured offline at the front desk." },
              ].map(({ k, t, d }) => (
                <div key={k} className="flex items-center justify-between gap-4 rounded-lg border p-3.5">
                  <div>
                    <div className="text-sm font-medium">{t}</div>
                    <div className="text-xs text-muted-foreground">{d}</div>
                  </div>
                  <Switch checked={policy?.[k] ?? true} disabled={!canManage || busy || !policy}
                    onCheckedChange={(v) => setPolicy((p) => ({ ...(p ?? {}), [k]: v }))} />
                </div>
              ))}
              {canManage && (
                <div className="flex justify-end">
                  <Button size="sm" className="gap-1.5" disabled={busy || !policy} onClick={savePolicy}>
                    <ShieldCheck className="size-4" /> Save policy
                  </Button>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* register / edit device dialog */}
      <Dialog open={!!devDlg} onOpenChange={(o) => !o && setDevDlg(null)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>{devDlg?.mode === "edit" ? `Edit ${devDlg?.dev?.code}` : "Register a sync device"}</DialogTitle>
            <DialogDescription>
              {devDlg?.mode === "edit" ? "Rename the device or adjust its notes." : "Pre-register a POS tablet, scanner or front-desk workstation so you can watch and revoke it before it ever flushes."}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <div className="space-y-1.5">
              <Label>Device name</Label>
              <Input value={devName} onChange={(e) => setDevName(e.target.value)} placeholder="e.g. Canteen POS tablet 2" />
            </div>
            <div className="space-y-1.5">
              <Label>Kind</Label>
              <Select value={devKind} onValueChange={setDevKind} disabled={devDlg?.mode === "edit"}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {Object.entries(KIND_META).map(([k, m]) => <SelectItem key={k} value={k}>{m.label}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Notes (optional)</Label>
              <Textarea rows={2} value={devNotes} onChange={(e) => setDevNotes(e.target.value)} placeholder="Location, custodian, anything the office should know" />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDevDlg(null)}>Cancel</Button>
            <Button disabled={busy || !devName.trim()} onClick={saveDevice}>{devDlg?.mode === "edit" ? "Save changes" : "Register"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* resolve conflict dialog */}
      <Dialog open={!!resDlg} onOpenChange={(o) => !o && setResDlg(null)}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><GitMerge className="size-4" /> Resolve — {resDlg?.recordKey}</DialogTitle>
            <DialogDescription>
              Pick which version survives. "Queued version" replays the frozen offline payload onto the server now; "Server version" discards the queued replay.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            {resDlg?.diffRows && resDlg.diffRows.length > 0 && (
              <div className="rounded-lg border text-sm divide-y max-h-40 overflow-y-auto">
                {resDlg.diffRows.slice(0, 8).map((r, i) => (
                  <div key={i} className="flex justify-between gap-3 px-3 py-1.5">
                    <span className="font-medium truncate">{r.name}</span>
                    <span className="font-mono text-xs"><span className="text-amber-700 dark:text-amber-400">{r.local}</span> → <span className="text-sky-700 dark:text-sky-400">{r.remote}</span></span>
                  </div>
                ))}
              </div>
            )}
            <div className="space-y-2">
              {[
                { v: "LOCAL_WINS", t: "Queued version wins", d: "Replay the offline payload — overwrites the server's rows." },
                { v: "REMOTE_WINS", t: "Server version wins", d: "Keep what the server holds — discard the queued replay." },
                { v: "IGNORED", t: "Ignore (keep both on file)", d: "Neither side is applied; the conflict stays on record as ignored." },
              ].map(({ v, t, d }) => (
                <label key={v} className={cn("flex items-start gap-3 rounded-lg border p-3 cursor-pointer", resChoice === v && "border-primary ring-1 ring-primary")}>
                  <input type="radio" name="resolution" className="mt-1 accent-primary" checked={resChoice === v} onChange={() => setResChoice(v as any)} />
                  <div>
                    <div className="text-sm font-medium">{t}</div>
                    <div className="text-xs text-muted-foreground">{d}</div>
                  </div>
                </label>
              ))}
            </div>
            <div className="space-y-1.5">
              <Label>Resolution note (optional)</Label>
              <Textarea rows={2} value={resNote} onChange={(e) => setResNote(e.target.value)} placeholder="Why this side won — kept for the journal" />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setResDlg(null)}>Cancel</Button>
            <Button disabled={busy} onClick={resolveConflict}>Resolve conflict</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

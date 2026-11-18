"use client";
// ── Audit Trail (wave 31 — the compliance book) ──
// The ledger of consequential acts: every payment taken, invoice edited,
// result published, certificate issued or revoked, fee adjusted — who did
// it, when, from where, and the exact before/after state. iEduPro parity
// with the legacy audit_log_viewer's Before Data / After Data panes, plus
// modern filters (actor · action · entity · date range · keyword), stat
// cards, per-action mix bars, actor leaderboards and CSV export.
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard } from "@/components/ui-helpers";
import { DateField } from "@/components/date-field";
import { fmtDateTime, initials, avatarColor } from "@/lib/format";
import { cn } from "@/lib/utils";
import {
  ScrollText, Search, Download, ChevronLeft, ChevronRight, ShieldCheck, Undo2,
  History, AlertTriangle, Trash2, Eye, FileWarning, Activity, ArrowRightLeft,
} from "lucide-react";
import {
  ResponsiveContainer, BarChart, Bar, XAxis, YAxis, Tooltip, CartesianGrid,
} from "recharts";

const OPS = "/ops/audit";

type Row = {
  id: string; actorName: string; actorRole: string | null;
  action: string; entityType: string; entityLabel: string | null;
  summary: string; before: Record<string, unknown> | null; after: Record<string, unknown> | null;
  ip: string | null; createdAt: string;
};
type Stats = {
  today: number; week: number; month: number;
  byAction: { action: string; count: number }[];
  byEntity: { entityType: string; count: number }[];
  topActors: { name: string; count: number }[];
};
type Data = {
  rows: Row[]; total: number; page: number; pages: number;
  stats: Stats;
  actors: { id: string; name: string; role: string }[];
  entityTypes: string[];
  actions: string[];
};

const ACTION_TONE: Record<string, string> = {
  CREATE: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  UPDATE: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
  DELETE: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300",
  ISSUE: "bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300",
  REVOKE: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300",
  REINSTATE: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  PUBLISH: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  UNPUBLISH: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
  APPROVE: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  REJECT: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300",
  LOCK: "bg-stone-200 text-stone-700 dark:bg-stone-800 dark:text-stone-300",
  UNLOCK: "bg-stone-200 text-stone-700 dark:bg-stone-800 dark:text-stone-300",
  VOID: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300",
  EXPORT: "bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300",
  ADJUST: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
  TRANSFER: "bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300",
  LOGIN: "bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300",
};

const ENTITY_ICON: Record<string, typeof ShieldCheck> = {
  PAYMENT: ArrowRightLeft, INVOICE: FileWarning, STUDENT: Eye, RESULT: ShieldCheck,
  CERTIFICATE: ScrollText, STAFF: Activity, TIMETABLE: History, RBAC: ShieldCheck,
  FEES: ArrowRightLeft, ATTENDANCE: Activity, SETTING: History, DISCOUNT: Undo2,
};

/** one flat key/value pane — like the legacy "Before Data" / "After Data" panes */
function DiffPane({ title, tone, obj }: { title: string; tone: string; obj: Record<string, unknown> | null }) {
  return (
    <div className={cn("rounded-lg border", tone)}>
      <div className="px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide border-b">{title}</div>
      <div className="p-3 space-y-1 max-h-72 overflow-y-auto">
        {obj && Object.keys(obj).length ? Object.entries(obj).map(([k, v]) => (
          <div key={k} className="flex gap-2 text-[11.5px] leading-tight">
            <span className="font-mono text-muted-foreground shrink-0">{k}</span>
            <span className="font-mono break-all">{v === null ? "—" : typeof v === "object" ? JSON.stringify(v) : String(v)}</span>
          </div>
        )) : <p className="text-[11.5px] text-muted-foreground italic">No data recorded on this side.</p>}
      </div>
    </div>
  );
}

/** value that changed between the two panes gets a highlight */
function DiffPair({ row }: { row: Row }) {
  const keys = Array.from(new Set([...Object.keys(row.before ?? {}), ...Object.keys(row.after ?? {})]));
  if (!keys.length) return null;
  return (
    <div className="grid md:grid-cols-2 gap-3">
      <DiffPane title="Before" tone="bg-red-50/60 dark:bg-red-950/30 border-red-200 dark:border-red-900" obj={row.before} />
      <DiffPane title="After" tone="bg-emerald-50/60 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-900" obj={row.after} />
    </div>
  );
}

export function AuditTrail() {
  const { session, currentSchoolId } = useStore();
  const schoolId = currentSchoolId ?? "";
  const [data, setData] = useState<Data | null>(null);
  const [loading, setLoading] = useState(true);
  const [fAction, setFAction] = useState("all");
  const [fEntity, setFEntity] = useState("all");
  const [fActor, setFActor] = useState("all");
  const [fFrom, setFFrom] = useState("");
  const [fTo, setFTo] = useState("");
  const [q, setQ] = useState("");
  const [page, setPage] = useState(1);
  const [detail, setDetail] = useState<Row | null>(null);

  const load = useCallback(async () => {
    if (!schoolId) return;
    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (fAction !== "all") params.set("action", fAction);
      if (fEntity !== "all") params.set("entityType", fEntity);
      if (fActor !== "all") params.set("actorId", fActor);
      if (fFrom) params.set("from", fFrom);
      if (fTo) params.set("to", fTo);
      if (q.trim()) params.set("q", q.trim());
      params.set("page", String(page));
      const res = await apiGet<Data>(`/api/school/${schoolId}${OPS}?${params.toString()}`);
      setData(res);
    } catch {
      setData(null);
    } finally {
      setLoading(false);
    }
  }, [schoolId, fAction, fEntity, fActor, fFrom, fTo, q, page]);

  useEffect(() => { load(); }, [load]);
  useEffect(() => { setPage(1); }, [fAction, fEntity, fActor, fFrom, fTo, q]);

  const s = data?.stats;
  const actionData = useMemo(() => (s?.byAction ?? []).slice(0, 8).map((a) => ({ name: a.action, count: a.count })), [s]);
  const canPrev = (data?.page ?? 1) > 1;
  const canNext = (data?.page ?? 1) < (data?.pages ?? 1);

  return (
    <div className="space-y-4">
      <ModuleHeader
        title="Audit Trail"
        description="The compliance book — every consequential act in the school's records: who did it, when, from where, and the before/after state of what changed."
        actions={
          <Button variant="outline" size="sm" className="gap-1.5"
            onClick={() => {
              const params = new URLSearchParams();
              if (fAction !== "all") params.set("action", fAction);
              if (fEntity !== "all") params.set("entityType", fEntity);
              if (fFrom) params.set("from", fFrom);
              if (fTo) params.set("to", fTo);
              params.set("format", "csv");
              window.location.href = `/api/school/${schoolId}${OPS}?${params.toString()}`;
            }}>
            <Download className="size-4" /> Export CSV
          </Button>
        }
      />

      {loading && !data ? <LoadingBlock /> : !data ? <EmptyState icon={<AlertTriangle className="size-8" />} title="Could not load the audit trail" hint="Check your connection and permission, then try again." /> : (
        <>
          {/* stat cards */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <StatCard icon={<History className="size-4" />} label="Events today" value={s?.today ?? 0} sub="since midnight" />
            <StatCard icon={<Activity className="size-4" />} label="Last 7 days" value={s?.week ?? 0} sub="rolling week" tone="primary" />
            <StatCard icon={<ScrollText className="size-4" />} label="Last 30 days" value={s?.month ?? 0} sub="rolling month" />
            <StatCard icon={<Eye className="size-4" />} label="Matching events" value={data.total} sub="current filter" tone="gold" />
          </div>

          {/* filters */}
          <Card>
            <CardContent className="p-3 grid gap-2 md:grid-cols-6 items-center">
              <div className="relative md:col-span-2">
                <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search actor, reference, summary…" className="pl-9" aria-label="Search audit trail" />
              </div>
              <Select value={fAction} onValueChange={setFAction}>
                <SelectTrigger aria-label="Filter by action"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All actions</SelectItem>
                  {(data.actions ?? []).map((a) => <SelectItem key={a} value={a}>{a}</SelectItem>)}
                </SelectContent>
              </Select>
              <Select value={fEntity} onValueChange={setFEntity}>
                <SelectTrigger aria-label="Filter by entity"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All records</SelectItem>
                  {(data.entityTypes ?? []).map((t) => <SelectItem key={t} value={t}>{t}</SelectItem>)}
                </SelectContent>
              </Select>
              <DateField value={fFrom} onChange={(v) => setFFrom(v ?? "")} aria-label="From date" />
              <DateField value={fTo} onChange={(v) => setFTo(v ?? "")} aria-label="To date" />
            </CardContent>
          </Card>

          <div className="grid lg:grid-cols-3 gap-4">
            {/* timeline */}
            <Card className="lg:col-span-2">
              <CardHeader className="pb-2"><CardTitle className="text-sm font-semibold flex items-center gap-2"><ScrollText className="size-4" /> The register</CardTitle></CardHeader>
              <CardContent className="p-0">
                {loading ? <LoadingBlock /> : !data.rows.length ? (
                  <EmptyState icon={<ScrollText className="size-8" />} title="No audit events match" hint="Adjust or clear the filters — events land here as staff work the system." />
                ) : (
                  <div className="max-h-[28rem] overflow-y-auto divide-y">
                    {data.rows.map((r) => {
                      const Icon = ENTITY_ICON[r.entityType] ?? History;
                      return (
                        <button key={r.id} className="w-full text-left px-4 py-2.5 hover:bg-muted/60 transition-colors flex gap-3 items-start"
                          onClick={() => setDetail(r)}>
                          <span className={cn("mt-0.5 shrink-0 grid place-items-center size-8 rounded-full text-[11px] font-bold", avatarColor(r.actorName))}>{initials(r.actorName)}</span>
                          <span className="min-w-0 flex-1">
                            <span className="flex flex-wrap items-center gap-1.5">
                              <span className="text-[13px] font-semibold truncate">{r.actorName}</span>
                              <Badge variant="outline" className={cn("text-[9.5px] px-1.5 h-4.5", ACTION_TONE[r.action] ?? "")}>{r.action}</Badge>
                              <Badge variant="secondary" className="text-[9.5px] px-1.5 h-4.5 gap-1"><Icon className="size-3" />{r.entityType}</Badge>
                              {(r.before || r.after) && <Badge variant="outline" className="text-[9.5px] px-1.5 h-4.5 text-muted-foreground gap-1"><FileWarning className="size-3" />diff</Badge>}
                            </span>
                            <span className="block text-[12.5px] text-foreground/90 leading-snug mt-0.5">{r.summary}</span>
                            <span className="block text-[10.5px] text-muted-foreground mt-0.5">{fmtDateTime(r.createdAt)}{r.ip ? ` · ${r.ip}` : ""}{r.actorRole ? ` · ${r.actorRole.replace(/_/g, " ").toLowerCase()}` : ""}</span>
                          </span>
                        </button>
                      );
                    })}
                  </div>
                )}
                {/* pagination */}
                <div className="flex items-center justify-between px-4 py-2 border-t text-[11.5px] text-muted-foreground">
                  <span>{data.total} events · page {data.page} of {data.pages}</span>
                  <span className="flex gap-1.5">
                    <Button size="sm" variant="outline" className="h-7 px-2" disabled={!canPrev} onClick={() => setPage((p) => Math.max(1, p - 1))}><ChevronLeft className="size-3.5" /> Prev</Button>
                    <Button size="sm" variant="outline" className="h-7 px-2" disabled={!canNext} onClick={() => setPage((p) => p + 1)}>Next <ChevronRight className="size-3.5" /></Button>
                  </span>
                </div>
              </CardContent>
            </Card>

            {/* side analytics */}
            <div className="space-y-4">
              <Card>
                <CardHeader className="pb-2"><CardTitle className="text-sm font-semibold">Action mix</CardTitle></CardHeader>
                <CardContent className="h-44">
                  {actionData.length ? (
                    <ResponsiveContainer width="100%" height="100%">
                      <BarChart data={actionData} layout="vertical" margin={{ left: 4, right: 12, top: 4, bottom: 4 }}>
                        <CartesianGrid strokeDasharray="2 4" horizontal={false} />
                        <XAxis type="number" hide />
                        <YAxis type="category" dataKey="name" width={64} tick={{ fontSize: 9.5 }} />
                        <Tooltip contentStyle={{ fontSize: 11 }} />
                        <Bar dataKey="count" fill="currentColor" className="text-primary/70" radius={[0, 3, 3, 0]} />
                      </BarChart>
                    </ResponsiveContainer>
                  ) : <p className="text-[11.5px] text-muted-foreground italic pt-8 text-center">No events yet.</p>}
                </CardContent>
              </Card>
              <Card>
                <CardHeader className="pb-2"><CardTitle className="text-sm font-semibold">Most active staff</CardTitle></CardHeader>
                <CardContent className="space-y-1.5">
                  {(s?.topActors ?? []).length ? (s!.topActors).map((a, i) => (
                    <div key={a.name} className="flex items-center gap-2 text-[12px]">
                      <span className={cn("grid place-items-center size-5 rounded-full text-[10px] font-bold shrink-0", avatarColor(a.name))}>{i + 1}</span>
                      <span className="flex-1 truncate">{a.name}</span>
                      <Badge variant="secondary" className="text-[10px]">{a.count}</Badge>
                    </div>
                  )) : <p className="text-[11.5px] text-muted-foreground italic">No activity recorded yet.</p>}
                </CardContent>
              </Card>
              <Card>
                <CardHeader className="pb-2"><CardTitle className="text-sm font-semibold">Records touched</CardTitle></CardHeader>
                <CardContent className="flex flex-wrap gap-1.5">
                  {(s?.byEntity ?? []).map((e) => (
                    <Badge key={e.entityType} variant="outline" className="text-[10px] gap-1"><Eye className="size-3 text-muted-foreground" />{e.entityType} · {e.count}</Badge>
                  ))}
                </CardContent>
              </Card>
            </div>
          </div>
        </>
      )}

      {/* before/after detail dialog — the legacy audit_log_viewer panes */}
      <Dialog open={!!detail} onOpenChange={(o) => !o && setDetail(null)}>
        <DialogContent className="max-w-2xl">
          {detail ? (
            <>
              <DialogHeader>
                <DialogTitle className="flex flex-wrap items-center gap-2 text-base">
                  <Badge className={cn("text-[10px]", ACTION_TONE[detail.action] ?? "")}>{detail.action}</Badge>
                  {detail.entityType}{detail.entityLabel ? <span className="text-muted-foreground font-normal">· {detail.entityLabel}</span> : null}
                </DialogTitle>
                <DialogDescription>
                  {detail.actorName} · {fmtDateTime(detail.createdAt)}{detail.ip ? ` · from ${detail.ip}` : ""}
                </DialogDescription>
              </DialogHeader>
              <p className="text-[13px]">{detail.summary}</p>
              <DiffPair row={detail} />
            </>
          ) : null}
        </DialogContent>
      </Dialog>
    </div>
  );
}

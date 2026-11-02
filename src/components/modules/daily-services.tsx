"use client";
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost, apiPostQueueable } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { StatCard, ModuleHeader, LoadingBlock, EmptyState, FilterBar, FilterSelect, FilterSearch } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, fmtDateTime, PAYMENT_LABELS } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { UtensilsCrossed, Wallet, CalendarPlus, Plus, TrendingUp, Search, Droplets, BookOpenCheck, Coffee, HandCoins, CheckCircle2, Printer, Percent, Bus, Settings2, CircleDollarSign, ArrowDownToLine, History, BadgeCheck, UserCheck, Landmark, ScrollText } from "lucide-react";
import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";
import { ReceiptDocument } from "@/components/receipts";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";

type Service = {
  id: string; name: string; category: string; price: number; chargeMode: string; weekdays: string; active: boolean; enrolled: number;
  classRates: { classroomId: string; classroom: string; price: number }[];
  month: { billed: number; collected: number; owed: number; waived: number; count: number };
  today: { billed: number; collected: number; count: number; owedCount: number };
};
type LedgerRow = { studentId: string; student: string; classroom: string | null; guardianPhone: string; owed: number; items: Record<string, number>; oldest: string };
type PosStudent = {
  id: string; name: string; classroom: string; classroomId: string; guardianPhone: string;
  balance: number;
  services: { id: string; name: string; category: string; price: number }[];
  wallet: { prepaidCents: number; transportCents: number };
  todayTransport: { id: string; amount: number; status: string; usage: string | null } | null;
  today: { charged: number; owed: number } | null;
  transportFare: { oneway: number; routeName: string; stopName: string | null; routeId: string } | null;
};
type WalletTxnRow = { id: string; student: string; wallet: string; kind: string; amount: number; balanceAfter: number; note: string | null; byName: string | null; at: string };
type DailySettings = { collectionMode: "CLASSROOM" | "CASHIER" | "HYBRID"; teacherPerms: { mode: "ALL" | "NONE" | "SELECTIVE"; classroomIds: string[]; serviceIds: string[] }; classrooms: { id: string; name: string }[] };
type Reconciliation = {
  billed: number; collected: number; waived: number; outstanding: number; counter: number; walletPays: number; variance: number; txns: number;
  byCollector: { name: string; counter: number; receipts: number; wallet: number; total: number }[];
};
// iEduPro daily_fee_audit_log parity: one row per charge / payment / top-up / adjustment event
type AuditRow = {
  id: string; studentId: string; student: string; classroom: string | null;
  actionType: string; service: string; amount: number;
  balanceBefore: number; balanceAfter: number; arrearsBefore: number; arrearsAfter: number;
  by: string; byRole: string; reference: string | null; notes: string | null; at: string;
};
const AUDIT_ACTION: Record<string, { label: string; className: string }> = {
  CHARGE: { label: "Charge", className: "bg-amber-600 text-white" },
  CHARGE_REVERSAL: { label: "Charge reversed", className: "bg-amber-800 text-white" },
  PAYMENT: { label: "Payment", className: "bg-emerald-600 text-white" },
  PAYMENT_REVERSAL: { label: "Payment reversed", className: "bg-destructive text-white" },
  TOPUP: { label: "Top-up", className: "bg-primary text-primary-foreground" },
  ADJUSTMENT: { label: "Adjustment", className: "bg-zinc-500 text-white" },
  RECONCILIATION: { label: "Reconciliation", className: "bg-zinc-700 text-white" },
};
type DailyData = {
  date: string;
  services: Service[];
  month: { billed: number; collected: number; owed: number; waived: number };
  today: { charged: number; collected: number; owed: number; count: number; generated: boolean };
  trend: { date: string; billed: number; collected: number }[];
  ledger: LedgerRow[];
  posStudents: PosStudent[];
  posPayments: { id: string; receiptNo: string; amount: number; method: string; paidAt: string; student: string }[];
  wallets: { studentId: string; student: string; classroom: string; prepaid: number; transport: number; arrears: number }[];
  walletTxns: WalletTxnRow[];
  settings: DailySettings;
  reconciliation: Reconciliation;
  audit: AuditRow[];
};

const MODE_LABELS: Record<string, string> = { CLASSROOM: "Classroom only", CASHIER: "Cashier only", HYBRID: "Hybrid (classroom + counter)" };

const CAT_ICON: Record<string, any> = { MEAL: Coffee, DRINK: Droplets, CLASS: BookOpenCheck, CARE: HandCoins, TRANSPORT: Bus, OTHER: UtensilsCrossed };
const DAY_LABELS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
const weekdayLabel = (wd: string) => wd.split(",").map((n) => DAY_LABELS[Number(n)]).join(" · ");

export function DailyServices() {
  const { currentSchoolId, online, session, navTarget } = useStore();
  const [data, setData] = useState<DailyData | null>(null);
  const [tab, setTab] = useState("pos");
  const [date, setDate] = useState(new Date().toISOString().slice(0, 10));
  const [collectFor, setCollectFor] = useState<PosStudent | null>(null);
  const [svcDialog, setSvcDialog] = useState<Service | "new" | null>(null);
  const [enrollFor, setEnrollFor] = useState<Service | null>(null);
  const [receipt, setReceipt] = useState<any | null>(null);
  const [busy, setBusy] = useState(false);
  const [search, setSearch] = useState("");
  const [posClass, setPosClass] = useState("all");
  const [posOwingOnly, setPosOwingOnly] = useState(false);
  const [walSearch, setWalSearch] = useState("");
  const [walClass, setWalClass] = useState("all");
  const [walState, setWalState] = useState("all");
  const [ledSearch, setLedSearch] = useState("");
  const [ledClass, setLedClass] = useState("all");
  const [ledService, setLedService] = useState("all");
  const [topUpFor, setTopUpFor] = useState<PosStudent | null>(null);
  const [settingsOpen, setSettingsOpen] = useState(false);
  const [auditStudentId, setAuditStudentId] = useState(""); // audit-log learner filter (recon tab)

  /* iEduPro nested nav: open on the deep-linked tab (POS / wallets / rates…) */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab && ["pos", "wallets", "ledger", "services", "rates", "recon", "trend"].includes(navTarget.tab)) setTab(navTarget.tab);
  }

  const isAdmin = session?.role === "SCHOOL_ADMIN" || session?.role === "GROUP_OWNER";
  const isTeacher = session?.role === "TEACHER";

  const load = (d?: string) => {
    if (!currentSchoolId) return;
    apiGet<DailyData>(`/api/school/${currentSchoolId}/ops/daily?date=${d ?? date}${auditStudentId ? `&auditStudentId=${auditStudentId}` : ""}`)
      .then(setData)
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(() => { load(date); }, [currentSchoolId, date, auditStudentId]);

  const filteredStudents = useMemo(() => {
    const q = search.toLowerCase();
    return (data?.posStudents ?? []).filter((s) =>
      (!q || s.name.toLowerCase().includes(q) || s.classroom.toLowerCase().includes(q)) &&
      (posClass === "all" || s.classroom === posClass) &&
      (!posOwingOnly || s.balance > 0));
  }, [data, search, posClass, posOwingOnly]);
  const withBalance = useMemo(() => filteredStudents.filter((s) => s.balance > 0), [filteredStudents]);
  // wallet + ledger filters (client-side — payloads already carry class + service breakdown)
  const classroomOptions = useMemo(() => {
    const names = new Set<string>();
    (data?.posStudents ?? []).forEach((s) => names.add(s.classroom));
    (data?.ledger ?? []).forEach((r) => r.classroom && names.add(r.classroom));
    return [...names].sort().map((c) => ({ value: c, label: c }));
  }, [data]);
  const filteredWallets = useMemo(() => (data?.wallets ?? []).filter((w) =>
    (walClass === "all" || w.classroom === walClass) &&
    (walState === "all" || (walState === "prepaid" ? w.prepaid > 0 : walState === "transport" ? w.transport > 0 : w.arrears > 0)) &&
    (!walSearch.trim() || `${w.student} ${w.classroom}`.toLowerCase().includes(walSearch.toLowerCase()))
  ), [data, walClass, walState, walSearch]);
  const walFiltersActive = walClass !== "all" || walState !== "all" || !!walSearch.trim();
  const ledgerServices = useMemo(() => {
    const names = new Set<string>();
    (data?.ledger ?? []).forEach((r) => Object.keys(r.items).forEach((k) => names.add(k)));
    return [...names].sort().map((c) => ({ value: c, label: c }));
  }, [data]);
  const filteredLedger = useMemo(() => (data?.ledger ?? []).filter((r) =>
    (ledClass === "all" || r.classroom === ledClass) &&
    (ledService === "all" || (r.items[ledService] ?? 0) > 0) &&
    (!ledSearch.trim() || `${r.student} ${r.classroom ?? ""} ${r.guardianPhone}`.toLowerCase().includes(ledSearch.toLowerCase()))
  ), [data, ledClass, ledService, ledSearch]);
  const ledFiltersActive = ledClass !== "all" || ledService !== "all" || !!ledSearch.trim();

  /* iedupro §1 collection policy — client mirror of the server gate (server stays authoritative) */
  const collectGate = (s: PosStudent): { ok: boolean; reason?: string } => {
    if (!data) return { ok: false };
    if (isAdmin) return { ok: true };
    const mode = data.settings.collectionMode;
    const perms = data.settings.teacherPerms;
    if (mode === "CLASSROOM" && !isTeacher) return { ok: false, reason: "Classroom-only — the class teacher collects." };
    if (mode === "CASHIER" && session?.role !== "ACCOUNTANT") return { ok: false, reason: "Cashier-only — see the bursar." };
    if (isTeacher) {
      if (perms.mode === "NONE") return { ok: false, reason: "No collection permission." };
      if (perms.mode === "SELECTIVE") {
        if (!perms.classroomIds.includes(s.classroomId)) return { ok: false, reason: "Not your class." };
        const svcIds = s.services.map((x) => x.id);
        if (svcIds.some((id) => !perms.serviceIds.includes(id))) return { ok: false, reason: "Some fee types are outside your permission." };
      }
    }
    return { ok: true };
  };

  async function generateDay() {
    if (!currentSchoolId) return;
    setBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/ops/daily`, { action: "generate-day", date });
      toast({ title: "Day generated", description: `${res.created} charges created${res.skipped ? ` · ${res.skipped} already existed` : ""}.` });
      load(date);
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  if (!data) return <LoadingBlock label="Loading daily services…" />;

  return (
    <div>
      <ModuleHeader
        title="Daily Services & Tuck"
        description="Canteen, breakfast, sachet water, remedial classes, transport fare — micro-billing charged by the school day. Collect at the counter or in class, track every pesewa."
        actions={
          <div className="flex items-center gap-2 flex-wrap">
            {data && <Badge variant="secondary" className="gap-1 h-8 px-2.5"><Landmark className="size-3.5" /> {MODE_LABELS[data.settings.collectionMode]}</Badge>}
            <Input type="date" value={date} onChange={(e) => setDate(e.target.value)} className="w-40" />
            <Button size="sm" className="h-8 gap-1.5" onClick={generateDay} disabled={busy}>
              <CalendarPlus className="size-4" /> {data?.today.generated ? "Re-generate day" : "Generate day charges"}
            </Button>
            {isAdmin && data && (
              <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={() => setSettingsOpen(true)}>
                <Settings2 className="size-4" /> Collection policy
              </Button>
            )}
          </div>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<Wallet className="size-4" />} label="Today collected" value={fmtCedi(data.today.collected, { compact: true })} tone="primary" sub={`${data.today.count} charges · ${fmtCedi(data.today.owed, { compact: true })} open`} />
        <StatCard icon={<TrendingUp className="size-4" />} label="This month" value={fmtCedi(data.month.collected, { compact: true })} sub={`${fmtCedi(data.month.billed, { compact: true })} billed`} />
        <StatCard icon={<Percent className="size-4" />} label="Daily arrears" value={fmtCedi(data.month.owed, { compact: true })} tone={data.month.owed > 0 ? "danger" : "default"} sub={`waived ${fmtCedi(data.month.waived, { compact: true })}`} />
        <StatCard icon={<UtensilsCrossed className="size-4" />} label="Services live" value={data.services.filter((s) => s.active).length} tone="gold" sub={`${data.services.reduce((a, s) => a + s.enrolled, 0)} enrolments`} />
      </div>

      <Tabs value={tab} onValueChange={setTab} className="mt-5">
        <TabsList>
          <TabsTrigger value="pos">Counter / POS</TabsTrigger>
          <TabsTrigger value="wallets">Wallets</TabsTrigger>
          <TabsTrigger value="ledger">Ledger & arrears ({data.ledger.length})</TabsTrigger>
          {!isTeacher && <TabsTrigger value="services">Services & enrolment</TabsTrigger>}
          {!isTeacher && <TabsTrigger value="rates">Fee rates</TabsTrigger>}
          {!isTeacher && <TabsTrigger value="recon">Reconciliation</TabsTrigger>}
          <TabsTrigger value="trend">14-day trend</TabsTrigger>
        </TabsList>

        {/* ── POS ── */}
        <TabsContent value="pos">
          {/* per-service today cards — iedupro cashier-portal style */}
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 mb-4">
            {data.services.filter((s) => s.active).map((s) => {
              const Icon = CAT_ICON[s.category] ?? UtensilsCrossed;
              return (
                <div key={s.id} className="rounded-xl border bg-card p-3">
                  <div className="flex items-center gap-1.5 text-muted-foreground"><Icon className="size-3.5" /><span className="text-[10px] font-semibold uppercase tracking-wide truncate">{s.name.replace(" (optional)", "").replace(" (canteen)", "").replace(" (remedial)", "")}</span></div>
                  <div className="text-lg font-bold tabular-nums mt-1 leading-none">{fmtCedi(s.today?.collected ?? 0, { compact: true })}</div>
                  <div className="text-[11px] text-muted-foreground mt-1">
                    {s.chargeMode === "WEEKLY" ? `${s.today?.count ?? 0} this week` : `${s.today?.count ?? 0} today`}
                    {(s.today?.owedCount ?? 0) > 0 && <span className="text-destructive"> · {s.today.owedCount} owing</span>}
                  </div>
                </div>
              );
            })}
          </div>
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <Card className="lg:col-span-2">
              <CardHeader className="pb-3 flex-row items-center justify-between space-y-0 flex-wrap gap-2">
                <CardTitle className="text-base">Collect a payment</CardTitle>
                <div className="flex items-center gap-1.5 flex-wrap">
                  <FilterSearch value={search} onChange={setSearch} placeholder="Learner or class…" />
                  <FilterSelect label="" value={posClass} onChange={setPosClass} options={[{ value: "all", label: "All classes" }, ...classroomOptions]} />
                  <button type="button" onClick={() => setPosOwingOnly(!posOwingOnly)}
                    className={`rounded-full border px-2.5 py-1 text-[11px] transition-colors ${posOwingOnly ? "border-primary bg-primary text-primary-foreground" : "hover:bg-secondary"}`}>
                    Owing only
                  </button>
                </div>
              </CardHeader>
              <CardContent className="max-h-[520px] overflow-y-auto nice-scroll space-y-1.5">
                {withBalance.length === 0 && <EmptyState icon={<CheckCircle2 className="size-5" />} title={posOwingOnly || posClass !== "all" || search ? "No learners match" : "No outstanding daily balances"} hint={posOwingOnly || posClass !== "all" || search ? "Adjust the search, class or owing filter." : "Everything collected — or generate today's charges first."} />}
                {withBalance.slice(0, 60).map((s) => {
                  const gate = collectGate(s);
                  return (
                    <div key={s.id} className="rounded-lg border p-3 flex flex-wrap items-center justify-between gap-2 hover:bg-secondary/30 transition-colors">
                      <div className="min-w-0">
                        <div className="font-medium text-sm truncate">{s.name} <span className="text-muted-foreground font-normal">· {s.classroom}</span></div>
                        <div className="text-[11px] text-muted-foreground">
                          {s.services.map((x) => x.name.replace(" (optional)", "").replace(" (canteen)", "").replace(" (remedial)", "")).join(" · ") || "—"}
                        </div>
                        {(s.wallet.prepaidCents > 0 || s.wallet.transportCents > 0) && (
                          <div className="flex gap-1 mt-1">
                            {s.wallet.prepaidCents > 0 && <Badge variant="outline" className="text-[10px] gap-1"><CircleDollarSign className="size-3" /> prepaid {fmtCedi(s.wallet.prepaidCents)}</Badge>}
                            {s.wallet.transportCents > 0 && <Badge variant="outline" className="text-[10px] gap-1"><Bus className="size-3" /> transport {fmtCedi(s.wallet.transportCents)}</Badge>}
                          </div>
                        )}
                      </div>
                      <div className="flex items-center gap-2 shrink-0">
                        <span className="font-semibold text-destructive tabular-nums text-sm">{fmtCedi(s.balance)}</span>
                        <Button size="sm" className="h-8 gap-1" disabled={!gate.ok} title={gate.reason ?? ""} onClick={() => setCollectFor(s)}><Wallet className="size-3.5" /> Collect</Button>
                      </div>
                    </div>
                  );
                })}
              </CardContent>
            </Card>
            <div className="space-y-4">
              <Card>
                <CardHeader className="pb-3"><CardTitle className="text-base">Recent counter receipts</CardTitle></CardHeader>
                <CardContent className="space-y-2 max-h-[240px] overflow-y-auto nice-scroll">
                  {data.posPayments.length === 0 && <EmptyState title="No receipts yet" />}
                  {data.posPayments.map((p) => (
                    <div key={p.id} className="flex items-center justify-between gap-2 text-sm border-b last:border-0 pb-2 last:pb-0">
                      <div className="min-w-0">
                        <div className="font-medium truncate">{p.student}</div>
                        <div className="text-[11px] text-muted-foreground font-mono">{p.receiptNo} · {PAYMENT_LABELS[p.method] ?? p.method}</div>
                      </div>
                      <div className="text-right shrink-0">
                        <div className="tabular-nums font-semibold">{fmtCedi(p.amount)}</div>
                        <div className="text-[11px] text-muted-foreground">{fmtDate(p.paidAt)}</div>
                      </div>
                    </div>
                  ))}
                </CardContent>
              </Card>
              <Card className="bg-primary/5 border-primary/20">
                <CardContent className="pt-4 text-sm space-y-1.5">
                  <div className="font-semibold flex items-center gap-1.5"><UtensilsCrossed className="size-4 text-primary" /> How it works</div>
                  <p className="text-muted-foreground text-xs leading-relaxed">
                    Generate the day&apos;s charges each morning (only learners enrolled in a service running that weekday are billed).
                    Collect at the counter in cash or MoMo — the oldest charges settle first, and the guardian gets an automatic SMS receipt.
                  </p>
                </CardContent>
              </Card>
            </div>
          </div>
        </TabsContent>

        {/* ── WALLETS (iedupro §1: prepaid + transport wallets, top-ups, audit log) ── */}
        <TabsContent value="wallets">
          <div className="grid grid-cols-3 gap-3 mb-4">
            <StatCard icon={<CircleDollarSign className="size-4" />} label="Prepaid wallets" value={fmtCedi(data.wallets.reduce((a, w) => a + w.prepaid, 0), { compact: true })} tone="primary" sub={`${data.wallets.filter((w) => w.prepaid > 0).length} learners holding credit`} />
            <StatCard icon={<Bus className="size-4" />} label="Transport wallets" value={fmtCedi(data.wallets.reduce((a, w) => a + w.transport, 0), { compact: true })} tone="gold" sub={`${data.wallets.filter((w) => w.transport > 0).length} learners holding credit`} />
            <StatCard icon={<Percent className="size-4" />} label="Arrears balance" value={fmtCedi(data.wallets.reduce((a, w) => a + w.arrears, 0), { compact: true })} tone={data.wallets.some((w) => w.arrears > 0) ? "danger" : "default"} sub="derived from unpaid charges" />
          </div>
          <div className="space-y-3">
            <FilterBar active={walFiltersActive} onClear={() => { setWalClass("all"); setWalState("all"); setWalSearch(""); }} count={filteredWallets.length} total={data.wallets.length} label="Wallet filters">
              <FilterSearch value={walSearch} onChange={setWalSearch} placeholder="Learner or class…" />
              <FilterSelect label="Class" value={walClass} onChange={setWalClass} options={[{ value: "all", label: "All classes" }, ...classroomOptions]} />
              <FilterSelect label="Wallet" value={walState} onChange={setWalState} options={[
                { value: "all", label: "All" }, { value: "prepaid", label: "Has prepaid" }, { value: "transport", label: "Has transport" }, { value: "arrears", label: "In arrears" },
              ]} />
            </FilterBar>
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <Card className="lg:col-span-2">
              <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
                <CardTitle className="text-base">Learner wallets</CardTitle>
                {!isTeacher && data.posStudents.length > 0 && (
                  <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={() => setTopUpFor(data.posStudents[0])}>
                    <ArrowDownToLine className="size-4" /> Top up
                  </Button>
                )}
              </CardHeader>
              <CardContent className="p-0">
                <div className="overflow-x-auto max-h-[420px] nice-scroll">
                  <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                      <tr className="text-left border-b">
                        <th className="px-3 py-2.5 font-medium">Learner</th>
                        <th className="px-3 py-2.5 font-medium hidden md:table-cell">Class</th>
                        <th className="px-3 py-2.5 font-medium text-right">Prepaid</th>
                        <th className="px-3 py-2.5 font-medium text-right">Transport</th>
                        <th className="px-3 py-2.5 font-medium text-right">Arrears</th>
                        <th className="px-3 py-2.5 text-right"></th>
                      </tr>
                    </thead>
                    <tbody>
                      {filteredWallets.length === 0 && <tr><td colSpan={6}><EmptyState icon={<BadgeCheck className="size-5" />} title={walFiltersActive ? "No wallets match" : "No wallet balances"} hint={walFiltersActive ? "Adjust the search, class or wallet filter." : "Top up a learner to open a prepaid wallet."} /></td></tr>}
                      {filteredWallets.map((w) => {
                        const ps = data.posStudents.find((p) => p.id === w.studentId) ?? null;
                        return (
                          <tr key={w.studentId} className="border-b last:border-0 hover:bg-secondary/30">
                            <td className="px-3 py-2.5"><div className="font-medium">{w.student}</div></td>
                            <td className="px-3 py-2.5 hidden md:table-cell text-muted-foreground">{w.classroom}</td>
                            <td className="px-3 py-2.5 text-right tabular-nums">{w.prepaid > 0 ? fmtCedi(w.prepaid) : <span className="text-muted-foreground">—</span>}</td>
                            <td className="px-3 py-2.5 text-right tabular-nums">{w.transport > 0 ? fmtCedi(w.transport) : <span className="text-muted-foreground">—</span>}</td>
                            <td className="px-3 py-2.5 text-right tabular-nums">{w.arrears > 0 ? <span className="text-destructive font-semibold">{fmtCedi(w.arrears)}</span> : <span className="text-muted-foreground">—</span>}</td>
                            <td className="px-3 py-2.5 text-right">
                              <div className="flex gap-1.5 justify-end">
                                {!isTeacher && ps && <Button size="sm" variant="ghost" className="h-7" onClick={() => setTopUpFor(ps)}>Top up</Button>}
                                {ps && ps.balance > 0 && <Button size="sm" variant="outline" className="h-7" onClick={() => setCollectFor(ps)}>Collect</Button>}
                              </div>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><History className="size-4 text-primary" /> Wallet activity</CardTitle></CardHeader>
              <CardContent className="space-y-2 max-h-[420px] overflow-y-auto nice-scroll">
                {data.walletTxns.length === 0 && <EmptyState title="No wallet activity yet" />}
                {data.walletTxns.map((t) => (
                  <div key={t.id} className="flex items-center justify-between gap-2 text-sm border-b last:border-0 pb-2 last:pb-0">
                    <div className="min-w-0">
                      <div className="font-medium truncate">{t.student}</div>
                      <div className="text-[11px] text-muted-foreground">
                        {t.wallet === "PREPAID" ? "Prepaid" : "Transport"} · {t.kind === "TOPUP" ? "top-up" : t.kind === "PAY" ? "paid charges" : "correction"}{t.byName ? ` · ${t.byName}` : ""}
                      </div>
                    </div>
                    <div className="text-right shrink-0">
                      <div className={`tabular-nums font-semibold ${t.kind === "PAY" ? "text-destructive" : "text-emerald-600"}`}>{t.kind === "PAY" ? "−" : "+"}{fmtCedi(t.amount)}</div>
                      <div className="text-[11px] text-muted-foreground">bal {fmtCedi(t.balanceAfter)} · {fmtDate(t.at)}</div>
                    </div>
                  </div>
                ))}
              </CardContent>
            </Card>
          </div>
          </div>
        </TabsContent>

        {/* ── LEDGER ── */}
        <TabsContent value="ledger">
          <div className="space-y-3">
            <FilterBar active={ledFiltersActive} onClear={() => { setLedClass("all"); setLedService("all"); setLedSearch(""); }} count={filteredLedger.length} total={data.ledger.length} label="Arrears filters">
              <FilterSearch value={ledSearch} onChange={setLedSearch} placeholder="Learner, class, guardian…" />
              <FilterSelect label="Class" value={ledClass} onChange={setLedClass} options={[{ value: "all", label: "All classes" }, ...classroomOptions]} />
              <FilterSelect label="Service" value={ledService} onChange={setLedService} options={[{ value: "all", label: "All services" }, ...ledgerServices]} />
            </FilterBar>
            <Card>
            <CardContent className="p-0">
              <div className="overflow-x-auto nice-scroll max-h-[560px]">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                    <tr className="text-left border-b">
                      <th className="px-3 py-2.5 font-medium">Learner</th>
                      <th className="px-3 py-2.5 font-medium hidden md:table-cell">Class</th>
                      <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Breakdown</th>
                      <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Oldest unpaid</th>
                      <th className="px-3 py-2.5 font-medium text-right">Owed</th>
                      <th className="px-3 py-2.5 text-right"></th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredLedger.length === 0 && <tr><td colSpan={6}><EmptyState icon={<CheckCircle2 className="size-5" />} title={ledFiltersActive ? "No arrears match" : "Clean ledger — no daily arrears"} hint={ledFiltersActive ? "Adjust the search, class or service filter." : undefined} /></td></tr>}
                    {filteredLedger.map((r) => (
                      <tr key={r.studentId} className="border-b last:border-0 hover:bg-secondary/30">
                        <td className="px-3 py-2.5">
                          <div className="font-medium">{r.student}</div>
                          <div className="text-[11px] text-muted-foreground lg:hidden">{r.classroom}</div>
                        </td>
                        <td className="px-3 py-2.5 hidden md:table-cell text-muted-foreground">{r.classroom}</td>
                        <td className="px-3 py-2.5 hidden lg:table-cell">
                          <div className="flex flex-wrap gap-1">
                            {Object.entries(r.items).map(([k, v]) => (
                              <Badge key={k} variant="outline" className="text-[10px]">{k.replace(" (optional)", "")} {fmtCedi(v)}</Badge>
                            ))}
                          </div>
                        </td>
                        <td className="px-3 py-2.5 hidden sm:table-cell text-muted-foreground text-xs">{fmtDate(r.oldest)}</td>
                        <td className="px-3 py-2.5 text-right tabular-nums font-semibold text-destructive">{fmtCedi(r.owed)}</td>
                        <td className="px-3 py-2.5 text-right">
                          <Button size="sm" variant="outline" className="h-7 gap-1"
                            onClick={() => setCollectFor(data.posStudents.find((s) => s.id === r.studentId) ?? null)}>
                            <Wallet className="size-3.5" /> Collect
                          </Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
          </div>
        </TabsContent>

        {/* ── SERVICES ── */}
        <TabsContent value="services">
          <div className="flex justify-end mb-3">
            <Button size="sm" className="gap-1.5" onClick={() => setSvcDialog("new")}><Plus className="size-4" /> New service</Button>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            {data.services.map((s) => {
              const Icon = CAT_ICON[s.category] ?? UtensilsCrossed;
              const rate = s.month.billed ? Math.round((s.month.collected / s.month.billed) * 100) : 0;
              return (
                <Card key={s.id} className={s.active ? "" : "opacity-60"}>
                  <CardHeader className="pb-2 flex-row items-center justify-between space-y-0">
                    <CardTitle className="text-sm flex items-center gap-2"><Icon className="size-4 text-primary" /> {s.name}</CardTitle>
                    <div className="flex items-center gap-1.5">
                      {!s.active && <Badge variant="outline">paused</Badge>}
                      <Badge variant="secondary">{fmtCedi(s.price)}</Badge>
                    </div>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    <div className="text-xs text-muted-foreground">
                      {s.chargeMode === "WEEKLY" ? `Weekly — ${weekdayLabel(s.weekdays)}` : `Daily — ${weekdayLabel(s.weekdays)}`} · {s.enrolled} learners enrolled
                    </div>
                    <div className="space-y-1">
                      <div className="flex justify-between text-xs"><span className="text-muted-foreground">Month collection</span><span className="tabular-nums">{rate}%</span></div>
                      <Progress value={rate} className="h-1.5" />
                      <div className="flex justify-between text-[11px] text-muted-foreground">
                        <span>{fmtCedi(s.month.collected)} of {fmtCedi(s.month.billed)}</span>
                        {s.month.owed > 0 && <span className="text-destructive">{fmtCedi(s.month.owed)} owed</span>}
                      </div>
                    </div>
                    <div className="flex gap-2 pt-1">
                      <Button size="sm" variant="outline" className="h-7 flex-1" onClick={() => setEnrollFor(s)}>Enrol learners</Button>
                      <Button size="sm" variant="ghost" className="h-7" onClick={() => setSvcDialog(s)}>Edit</Button>
                      <Button size="sm" variant="ghost" className="h-7"
                        onClick={async () => {
                          await apiPost(`/api/school/${currentSchoolId}/ops/daily`, { action: "toggle-service", id: s.id });
                          toast({ title: s.active ? "Service paused" : "Service resumed" });
                          load(date);
                        }}>{s.active ? "Pause" : "Resume"}</Button>
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </TabsContent>

        {/* ── TREND ── */}
        <TabsContent value="trend">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base">Billed vs collected — last 14 school days</CardTitle></CardHeader>
            <CardContent>
              <div className="h-72">
                <ResponsiveContainer width="100%" height="100%">
                  <AreaChart data={data.trend.map((t) => ({ ...t, label: new Date(t.date).toLocaleDateString("en-GB", { day: "numeric", month: "short" }) }))}>
                    <defs>
                      <linearGradient id="dailyBilled" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="#a97c0b" stopOpacity={0.25} />
                        <stop offset="95%" stopColor="#a97c0b" stopOpacity={0} />
                      </linearGradient>
                      <linearGradient id="dailyCollected" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="#0e5f3f" stopOpacity={0.35} />
                        <stop offset="95%" stopColor="#0e5f3f" stopOpacity={0} />
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                    <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                    <YAxis tick={{ fontSize: 11 }} tickFormatter={(v) => `₵${(v / 100000).toFixed(0)}k`} width={44} />
                    <Tooltip formatter={(v: any) => fmtCedi(Number(v))} contentStyle={{ fontSize: 12, borderRadius: 8 }} />
                    <Area dataKey="billed" name="Billed" stroke="#a97c0b" fill="url(#dailyBilled)" strokeWidth={2} />
                    <Area dataKey="collected" name="Collected" stroke="#0e5f3f" fill="url(#dailyCollected)" strokeWidth={2} />
                  </AreaChart>
                </ResponsiveContainer>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── FEE RATES (iedupro daily_fee_rates: per class-section per fee type) ── */}
        <TabsContent value="rates">
          <RatesTab data={data} onSaved={() => load(date)} />
        </TabsContent>

        {/* ── RECONCILIATION (iedupro daily_fee_reconciliation + handover + audit log) ── */}
        <TabsContent value="recon">
          <ReconciliationTab data={data} date={date} auditStudentId={auditStudentId} onAuditStudent={setAuditStudentId} />
        </TabsContent>
      </Tabs>

      {/* collect dialog */}
      <CollectDialog
        student={collectFor}
        date={date}
        online={online}
        onOpenChange={(o) => !o && setCollectFor(null)}
        onDone={(rc) => { setReceipt(rc); setCollectFor(null); load(date); }}
      />
      {receipt && <ReceiptDialog receipt={receipt} onOpenChange={(o) => !o && setReceipt(null)} />}

      {/* top-up dialog */}
      <TopUpDialog
        students={data.posStudents}
        initialStudent={topUpFor}
        open={!!topUpFor}
        onOpenChange={(o) => !o && setTopUpFor(null)}
        onDone={() => { setTopUpFor(null); load(date); }}
      />

      {/* collection policy dialog */}
      <DailySettingsDialog
        open={settingsOpen}
        data={data}
        onOpenChange={(o) => !o && setSettingsOpen(false)}
        onDone={() => { setSettingsOpen(false); load(date); }}
      />

      {/* service dialog */}
      <ServiceDialog
        service={svcDialog}
        onOpenChange={(o) => !o && setSvcDialog(null)}
        onDone={() => { setSvcDialog(null); load(date); }}
      />

      {/* enroll dialog */}
      <EnrollDialog
        service={enrollFor}
        students={data.posStudents}
        onOpenChange={(o) => !o && setEnrollFor(null)}
        onDone={() => { setEnrollFor(null); load(date); }}
      />
    </div>
  );
}

function CollectDialog({ student, date, online, onOpenChange, onDone }: { student: PosStudent | null; date: string; online: boolean; onOpenChange: (o: boolean) => void; onDone: (receipt: any) => void }) {
  const { currentSchoolId } = useStore();
  const [amount, setAmount] = useState("");
  const [method, setMethod] = useState("MOMO_MTN");
  const [reference, setReference] = useState("");
  const [paidOn, setPaidOn] = useState(date);
  const [payFrom, setPayFrom] = useState<"COUNTER" | "PREPAID" | "TRANSPORT">("COUNTER");
  const [tendered, setTendered] = useState("");
  const [usage, setUsage] = useState<string>("");
  const [markPresent, setMarkPresent] = useState(false);
  const [busy, setBusy] = useState(false);

  const transportSvc = student?.services.find((x) => x.category === "TRANSPORT");

  useEffect(() => {
    if (student) {
      setAmount((student.balance / 100).toFixed(2));
      setReference("");
      setPaidOn(date);
      setPayFrom("COUNTER");
      setTendered("");
      setMarkPresent(false);
      setUsage("");
    }
  }, [student, date]);

  const cents = Math.round(parseFloat(amount || "0") * 100);
  const walletCap = payFrom === "PREPAID" ? (student?.wallet.prepaidCents ?? 0) : payFrom === "TRANSPORT" ? (student?.wallet.transportCents ?? 0) : Infinity;
  const capped = Math.min(cents, walletCap);
  const change = Math.max(0, Math.round(parseFloat(tendered || "0") * 100) - cents);

  async function submit() {
    if (!student || !currentSchoolId) return;
    if (!cents || cents <= 0) { toast({ title: "Enter a valid amount" }); return; }
    if (payFrom !== "COUNTER" && cents > walletCap) { toast({ title: "Wallet too small", description: `Balance is ${fmtCedi(walletCap)} — lower the amount.` }); return; }
    setBusy(true);
    try {
      const payload = {
        action: "pos-collect", studentId: student.id, amountCents: cents,
        method: payFrom === "COUNTER" ? method : undefined,
        payFrom, reference: reference || undefined, paidAt: paidOn,
        transportUsage: usage || undefined, markPresent, date: paidOn,
      };
      const res = await apiPostQueueable<any>(
        `/api/school/${currentSchoolId}/ops/daily`, "payment", payload,
        `Daily services ${fmtCedi(cents)} — ${student.name}`,
        (p, clientId) => ({ ...p, category: "DAILY", schoolId: currentSchoolId, studentId: student.id, amountCents: cents, method: payFrom === "COUNTER" ? method : payFrom === "PREPAID" ? "WALLET" : "TRANSPORT_WALLET", paidAt: paidOn, clientId })
      );
      if (res.queued) {
        toast({ title: "Queued offline", description: "Receipt will be issued when you reconnect." });
        onDone(null);
      } else {
        if (res.data?.present === "marked" || res.data?.present === "updated") toast({ title: "Marked present", description: "Class register updated." });
        onDone(res.data?.receipt ?? { ...res.data?.payment, student: student.name });
      }
    } catch (e: any) { toast({ title: "Collection failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!student} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Wallet className="size-4" /> Collect daily payment — {student?.name}</DialogTitle>
          <DialogDescription>
            {student?.classroom} · outstanding <b className="text-destructive">{student ? fmtCedi(student.balance) : ""}</b>
            {student && student.wallet.prepaidCents > 0 && <> · prepaid <b>{fmtCedi(student.wallet.prepaidCents)}</b></>}
            {student && student.wallet.transportCents > 0 && <> · transport wallet <b>{fmtCedi(student.wallet.transportCents)}</b></>}
            {!online && " · offline — will queue & sync"}
          </DialogDescription>
        </DialogHeader>
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-1.5">
            <Label>Amount (GH₵) *</Label>
            <Input type="number" step="0.01" max={payFrom === "COUNTER" ? undefined : (walletCap / 100).toFixed(2)} value={amount} onChange={(e) => setAmount(e.target.value)} />
            {payFrom !== "COUNTER" && capped < cents && (
              <p className="text-[11px] text-destructive">Wallet balance is {fmtCedi(walletCap)} — amount will be capped.</p>
            )}
          </div>
          <div className="space-y-1.5">
            <Label>Payment date</Label>
            <Input type="date" value={paidOn} onChange={(e) => setPaidOn(e.target.value)} />
          </div>
          <div className="space-y-1.5">
            <Label>Pay from</Label>
            <Select value={payFrom} onValueChange={(v: any) => setPayFrom(v)}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="COUNTER">Counter — cash / MoMo</SelectItem>
                {student && student.wallet.prepaidCents > 0 && <SelectItem value="PREPAID">Prepaid wallet ({fmtCedi(student.wallet.prepaidCents)})</SelectItem>}
                {student && student.wallet.transportCents > 0 && <SelectItem value="TRANSPORT">Transport wallet ({fmtCedi(student.wallet.transportCents)})</SelectItem>}
              </SelectContent>
            </Select>
          </div>
          {payFrom === "COUNTER" ? (
            <div className="space-y-1.5">
              <Label>Method</Label>
              <Select value={method} onValueChange={setMethod}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {Object.entries(PAYMENT_LABELS).filter(([k]) => k !== "WALLET" && k !== "TRANSPORT_WALLET").map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
          ) : (
            <div className="space-y-1.5">
              <Label>Tendered (GH₵)</Label>
              <Input type="number" step="0.5" value={tendered} onChange={(e) => setTendered(e.target.value)} placeholder="0.00" disabled />
              <p className="text-[11px] text-muted-foreground">Wallet payments deduct directly — no tender needed.</p>
            </div>
          )}
          {payFrom === "COUNTER" && (
            <div className="space-y-1.5">
              <Label>Tendered (GH₵)</Label>
              <Input type="number" step="0.5" value={tendered} onChange={(e) => setTendered(e.target.value)} placeholder="e.g. 20.00" />
              {tendered && change >= 0 && (
                <p className="text-[11px]">Change: <b className="text-emerald-600">{fmtCedi(change)}</b></p>
              )}
              {tendered && change < 0 && (
                <p className="text-[11px] text-destructive">Short by {fmtCedi(-change)}</p>
              )}
            </div>
          )}
          <div className="space-y-1.5 sm:col-span-2">
            <Label>MoMo / reference</Label>
            <Input placeholder="e.g. MP2401.5533.77" value={reference} onChange={(e) => setReference(e.target.value)} />
          </div>
          {transportSvc && (
            <div className="space-y-1.5 sm:col-span-2">
              <Label>Transport usage today</Label>
              <Select value={usage} onValueChange={setUsage}>
                <SelectTrigger><SelectValue placeholder={student?.transportFare ? "Both ways — 2× one-way fare" : "No route assigned"} /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="BOTH" disabled={!student?.transportFare}>Both ways — {student?.transportFare ? fmtCedi(student.transportFare.oneway * 2) : "—"}</SelectItem>
                  <SelectItem value="MORNING" disabled={!student?.transportFare}>Morning only (one-way) — {student?.transportFare ? fmtCedi(student.transportFare.oneway) : "—"}</SelectItem>
                  <SelectItem value="AFTERNOON" disabled={!student?.transportFare}>Afternoon only (one-way) — {student?.transportFare ? fmtCedi(student.transportFare.oneway) : "—"}</SelectItem>
                  <SelectItem value="NOT_USING">Not using bus — waive fare (GH₵0)</SelectItem>
                  <SelectItem value="BOARDED" disabled={!student?.transportFare}>Already boarded — as recorded</SelectItem>
                </SelectContent>
              </Select>
              <p className="text-[11px] text-muted-foreground">
                {student?.transportFare
                  ? <>Route <b>{student.transportFare.routeName}</b> — one-way fare <b>{fmtCedi(student.transportFare.oneway)}</b>{student.transportFare.stopName ? <> (stop fare from {student.transportFare.stopName})</> : null}. Both ways = <b>{fmtCedi(student.transportFare.oneway * 2)}</b>.</>
                  : "This learner has no active route assignment — the transport fare follows the bus route."}
                {student?.todayTransport
                  ? ` Today's charge: ${fmtCedi(student.todayTransport.amount)} (${student.todayTransport.status.toLowerCase()}${student.todayTransport.usage ? ` · ${student.todayTransport.usage.toLowerCase()}` : ""}).`
                  : " No transport charge exists for today — picking a usage creates it."}
              </p>
            </div>
          )}
          <label className="sm:col-span-2 flex items-center gap-2 text-sm rounded-lg border p-2.5 cursor-pointer hover:bg-secondary/40">
            <input type="checkbox" checked={markPresent} onChange={(e) => setMarkPresent(e.target.checked)} className="accent-primary" />
            <UserCheck className="size-4 text-primary" />
            <span>Mark present in class register</span>
          </label>
        </div>
        <p className="text-xs text-muted-foreground">Charges settle oldest-first. A receipt SMS goes to {student?.guardianPhone} automatically.</p>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy} className="gap-1.5">{busy ? "Recording…" : <><ReceiptIcon /> Record payment</>}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
function ReceiptIcon() { return <Printer className="size-4" />; }

function ReceiptDialog({ receipt, onOpenChange }: { receipt: any; onOpenChange: (o: boolean) => void }) {
  const isPayload = receipt && receipt.type && receipt.school;
  return (
    <Dialog open={!!receipt} onOpenChange={onOpenChange}>
      <DialogContent className={isPayload ? "max-w-[560px] max-h-[92vh] overflow-y-auto nice-scroll" : "max-w-sm"}>
        <DialogHeader className="text-center no-print">
          <DialogTitle className="flex items-center justify-center gap-2"><CheckCircle2 className="size-5 text-emerald-600" /> Payment received</DialogTitle>
        </DialogHeader>
        {isPayload ? (
          <div className="print-area"><ReceiptDocument data={receipt} /></div>
        ) : (
          <div className="text-center space-y-1 py-2">
            <div className="text-3xl font-bold text-primary">{fmtCedi(receipt?.amountCents ?? 0)}</div>
            <div className="text-sm">{receipt?.student}</div>
            <div className="font-mono text-xs text-muted-foreground">{receipt?.receiptNo}</div>
            <div className="text-xs text-muted-foreground">daily services · SMS receipt sent to guardian</div>
          </div>
        )}
        <DialogFooter className="sm:justify-center gap-2 no-print">
          <Button variant="outline" size="sm" onClick={() => window.print()}><Printer className="size-4" /> Print</Button>
          <Button size="sm" onClick={() => onOpenChange(false)}>Done</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function ServiceDialog({ service, onOpenChange, onDone }: { service: Service | "new" | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [name, setName] = useState("");
  const [category, setCategory] = useState("MEAL");
  const [price, setPrice] = useState("");
  const [chargeMode, setChargeMode] = useState("DAILY");
  const [weekdays, setWeekdays] = useState<string[]>(["1", "2", "3", "4", "5"]);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (service && service !== "new") {
      setName(service.name); setCategory(service.category); setPrice((service.price / 100).toFixed(2));
      setChargeMode(service.chargeMode); setWeekdays(service.weekdays.split(","));
    } else if (service === "new") {
      setName(""); setCategory("MEAL"); setPrice(""); setChargeMode("DAILY"); setWeekdays(["1", "2", "3", "4", "5"]);
    }
  }, [service]);

  async function submit() {
    if (!currentSchoolId || !name || !price) { toast({ title: "Name and price are required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/daily`, {
        action: "save-service",
        id: service && service !== "new" ? service.id : undefined,
        name, category, priceCents: Math.round(parseFloat(price) * 100), chargeMode,
        weekdays: (chargeMode === "WEEKLY" ? weekdays.slice(0, 1) : weekdays).join(","),
      });
      toast({ title: service === "new" ? "Service created" : "Service updated" });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  const toggleDay = (d: string) => setWeekdays((w) => (w.includes(d) ? w.filter((x) => x !== d) : [...w, d]));

  return (
    <Dialog open={!!service} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{service === "new" ? "New daily service" : "Edit service"}</DialogTitle>
          <DialogDescription>e.g. Lunch GH₵8 every school day, or Sachet Water GH₵1 on Wednesdays only.</DialogDescription>
        </DialogHeader>
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-1.5 sm:col-span-2">
            <Label>Name *</Label>
            <Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Lunch (canteen)" />
          </div>
          <div className="space-y-1.5">
            <Label>Category</Label>
            <Select value={category} onValueChange={setCategory}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="MEAL">Meal</SelectItem><SelectItem value="DRINK">Drink</SelectItem>
                <SelectItem value="CLASS">Class / remedial</SelectItem><SelectItem value="CARE">Care</SelectItem>
                <SelectItem value="OTHER">Other</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Price (GH₵) *</Label>
            <Input type="number" step="0.5" value={price} onChange={(e) => setPrice(e.target.value)} placeholder="8.00" />
          </div>
          <div className="space-y-1.5 sm:col-span-2">
            <Label>Charging pattern</Label>
            <Select value={chargeMode} onValueChange={setChargeMode}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="DAILY">Daily — every selected school day</SelectItem>
                <SelectItem value="WEEKLY">Weekly — once a week</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5 sm:col-span-2">
            <Label>Days it runs {chargeMode === "WEEKLY" && <span className="text-muted-foreground font-normal">(pick one day)</span>}</Label>
            <div className="flex gap-1.5 flex-wrap">
              {DAY_LABELS.slice(1, 6).map((d, i) => (
                <button key={d} type="button"
                  onClick={() => toggleDay(String(i + 1))}
                  className={`size-9 rounded-lg border text-xs font-medium transition-colors ${weekdays.includes(String(i + 1)) ? "bg-primary text-primary-foreground border-primary" : "hover:bg-secondary"}`}>
                  {d}
                </button>
              ))}
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : "Save service"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function EnrollDialog({ service, students, onOpenChange, onDone }: { service: Service | null; students: PosStudent[]; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [picked, setPicked] = useState<string[]>([]);
  const [subsidy, setSubsidy] = useState("");
  const [search, setSearch] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => { setPicked([]); setSearch(""); setSubsidy(""); }, [service]);

  const list = useMemo(() => {
    const q = search.toLowerCase();
    return students.filter((s) => !q || s.name.toLowerCase().includes(q) || s.classroom.toLowerCase().includes(q));
  }, [students, search]);

  async function submit() {
    if (!service || !currentSchoolId || !picked.length) return;
    setBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/ops/daily`, {
        action: "enroll", serviceId: service.id, studentIds: picked,
        priceCentsOverride: subsidy ? Math.round(parseFloat(subsidy) * 100) : undefined,
      });
      toast({ title: `${res.enrolled} learners enrolled`, description: `${service.name}${subsidy ? ` at GH₵${subsidy} subsidised rate` : ""}.` });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!service} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle>Enrol learners — {service?.name}</DialogTitle>
          <DialogDescription>Enrolled learners are billed automatically when you generate the day&apos;s charges.</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="relative">
            <Search className="size-4 absolute left-2.5 top-2.5 text-muted-foreground" />
            <Input placeholder="Search learner…" value={search} onChange={(e) => setSearch(e.target.value)} className="pl-8" />
          </div>
          <div className="border rounded-lg max-h-64 overflow-y-auto nice-scroll">
            {list.map((s) => (
              <label key={s.id} className="flex items-center gap-2.5 px-3 py-2 border-b last:border-0 hover:bg-secondary/40 cursor-pointer text-sm">
                <input type="checkbox" checked={picked.includes(s.id)} onChange={() => setPicked((p) => (p.includes(s.id) ? p.filter((x) => x !== s.id) : [...p, s.id]))} className="accent-primary" />
                <span className="flex-1">{s.name}</span>
                <span className="text-xs text-muted-foreground">{s.classroom}</span>
              </label>
            ))}
          </div>
          <div className="space-y-1.5">
            <Label>Subsidised rate (GH₵) — optional</Label>
            <Input type="number" step="0.5" value={subsidy} onChange={(e) => setSubsidy(e.target.value)} placeholder={`Standard ${service ? fmtCedi(service.price) : ""}`} />
            <p className="text-xs text-muted-foreground">For needy-family support or staff wards — overrides the standard price for these learners.</p>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || !picked.length}>{busy ? "Enrolling…" : `Enrol ${picked.length || ""} learner${picked.length === 1 ? "" : "s"}`}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── TOP-UP DIALOG (iedupro §1 wallets): credit a prepaid or transport wallet ── */
function TopUpDialog({ students, initialStudent, open, onOpenChange, onDone }: {
  students: PosStudent[]; initialStudent: PosStudent | null; open: boolean;
  onOpenChange: (o: boolean) => void; onDone: () => void;
}) {
  const { currentSchoolId } = useStore();
  const [studentId, setStudentId] = useState("");
  const [wallet, setWallet] = useState<"PREPAID" | "TRANSPORT">("PREPAID");
  const [amount, setAmount] = useState("");
  const [note, setNote] = useState("");
  const [asAdjust, setAsAdjust] = useState(false);
  const [search, setSearch] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (open) {
      setStudentId(initialStudent?.id ?? "");
      setWallet("PREPAID"); setAmount(""); setNote(""); setAsAdjust(false); setSearch("");
    }
  }, [open, initialStudent]);

  const picked = students.find((s) => s.id === studentId) ?? null;
  const currentBal = picked ? (wallet === "PREPAID" ? picked.wallet.prepaidCents : picked.wallet.transportCents) : 0;

  const list = useMemo(() => {
    const q = search.toLowerCase();
    return students.filter((s) => !q || s.name.toLowerCase().includes(q) || s.classroom.toLowerCase().includes(q)).slice(0, 200);
  }, [students, search]);

  async function submit() {
    if (!currentSchoolId || !studentId) { toast({ title: "Pick a learner first" }); return; }
    const cents = Math.round(parseFloat(amount || "0") * 100);
    if (!cents || cents < 0) { toast({ title: "Enter a valid amount" }); return; }
    if (asAdjust && wallet === "PREPAID" ? !picked?.wallet : false) { toast({ title: "No wallet to adjust" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/daily`, {
        action: asAdjust ? "wallet-adjust" : "wallet-topup",
        studentId, wallet, amountCents: cents, note: note || undefined,
      });
      toast({
        title: asAdjust ? "Wallet corrected" : `${wallet === "PREPAID" ? "Prepaid" : "Transport"} wallet topped up`,
        description: asAdjust ? `${picked?.name} → ${fmtCedi(cents)}` : `${picked?.name} +${fmtCedi(cents)}`,
      });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><ArrowDownToLine className="size-4" /> {asAdjust ? "Correct wallet balance" : "Top up a wallet"}</DialogTitle>
          <DialogDescription>
            Prepaid wallets pay for any daily service; transport wallets are reserved for bus fare.
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="relative">
            <Search className="size-4 absolute left-2.5 top-2.5 text-muted-foreground" />
            <Input placeholder="Search learner…" value={search} onChange={(e) => setSearch(e.target.value)} className="pl-8" />
          </div>
          <div className="border rounded-lg max-h-44 overflow-y-auto nice-scroll">
            {list.map((s) => (
              <label key={s.id} className={`flex items-center gap-2.5 px-3 py-2 border-b last:border-0 cursor-pointer text-sm ${studentId === s.id ? "bg-primary/10" : "hover:bg-secondary/40"}`}>
                <input type="radio" checked={studentId === s.id} onChange={() => setStudentId(s.id)} className="accent-primary" />
                <span className="flex-1">{s.name}</span>
                <span className="text-xs text-muted-foreground">{s.classroom}</span>
                <span className="text-xs text-muted-foreground">
                  {s.wallet.prepaidCents > 0 && <>prepaid {fmtCedi(s.wallet.prepaidCents)} </>}
                  {s.wallet.transportCents > 0 && <>· bus {fmtCedi(s.wallet.transportCents)}</>}
                </span>
              </label>
            ))}
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Wallet</Label>
              <Select value={wallet} onValueChange={(v: any) => setWallet(v)}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="PREPAID">Prepaid — any daily service</SelectItem>
                  <SelectItem value="TRANSPORT">Transport — bus fare only</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>{asAdjust ? "New balance (GH₵)" : "Amount (GH₵)"}</Label>
              <Input type="number" step="0.5" value={amount} onChange={(e) => setAmount(e.target.value)} placeholder={asAdjust ? "0.00" : "e.g. 20.00"} />
              {picked && !asAdjust && <p className="text-[11px] text-muted-foreground">Current {wallet === "PREPAID" ? "prepaid" : "transport"} balance: {fmtCedi(currentBal)}</p>}
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Note — optional</Label>
            <Input value={note} onChange={(e) => setNote(e.target.value)} placeholder={asAdjust ? "reason for correction" : "e.g. Monday top-up from guardian"} />
          </div>
          <label className="flex items-center gap-2 text-sm rounded-lg border p-2.5 cursor-pointer hover:bg-secondary/40">
            <input type="checkbox" checked={asAdjust} onChange={(e) => setAsAdjust(e.target.checked)} className="accent-primary" />
            <span>Set an exact balance instead (correction)</span>
          </label>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || !studentId}>{busy ? "Saving…" : asAdjust ? "Correct balance" : "Top up"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── COLLECTION POLICY DIALOG (iedupro §1): mode + teacher permission matrix ── */
function DailySettingsDialog({ open, data, onOpenChange, onDone }: {
  open: boolean; data: DailyData | null; onOpenChange: (o: boolean) => void; onDone: () => void;
}) {
  const { currentSchoolId } = useStore();
  const [mode, setMode] = useState<"CLASSROOM" | "CASHIER" | "HYBRID">("HYBRID");
  const [permMode, setPermMode] = useState<"ALL" | "NONE" | "SELECTIVE">("ALL");
  const [classIds, setClassIds] = useState<string[]>([]);
  const [svcIds, setSvcIds] = useState<string[]>([]);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (open && data) {
      setMode(data.settings.collectionMode);
      setPermMode(data.settings.teacherPerms.mode);
      setClassIds(data.settings.teacherPerms.classroomIds);
      setSvcIds(data.settings.teacherPerms.serviceIds);
    }
  }, [open, data]);

  if (!data) return null;
  const activeServices = data.services.filter((s) => s.active);
  const toggle = (arr: string[], v: string, set: (a: string[]) => void) => set(arr.includes(v) ? arr.filter((x) => x !== v) : [...arr, v]);

  async function submit() {
    if (!currentSchoolId) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/daily`, {
        action: "settings-save",
        collectionMode: mode,
        teacherPerms: permMode === "SELECTIVE" ? { mode: permMode, classroomIds: classIds, serviceIds: svcIds } : { mode: permMode, classroomIds: [], serviceIds: [] },
      });
      toast({ title: "Collection policy saved", description: `${MODE_LABELS[mode]} · teacher permission: ${permMode === "ALL" ? "all fee types" : permMode === "NONE" ? "none" : "selective"}.` });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  const MODE_CARDS: { id: "CLASSROOM" | "CASHIER" | "HYBRID"; title: string; desc: string; icon: any }[] = [
    { id: "CLASSROOM", title: "Classroom only", desc: "Class teachers collect in class; the bursar's counter is closed for daily fees.", icon: UserCheck },
    { id: "CASHIER", title: "Cashier only", desc: "Only the bursar's counter may collect — teachers cannot take money.", icon: Landmark },
    { id: "HYBRID", title: "Hybrid", desc: "Collect either in class or at the counter — the default.", icon: CircleDollarSign },
  ];

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Settings2 className="size-4" /> Daily-fee collection policy</DialogTitle>
          <DialogDescription>Who may collect daily fees, and which fee types teachers may touch (iedupro parity).</DialogDescription>
        </DialogHeader>
        <div className="space-y-4">
          <div className="space-y-1.5">
            <Label className="text-xs uppercase tracking-wide text-muted-foreground">Collection mode</Label>
            <div className="grid gap-2">
              {MODE_CARDS.map((m) => (
                <button key={m.id} type="button" onClick={() => setMode(m.id)}
                  className={`flex items-start gap-2.5 rounded-lg border p-3 text-left transition-colors ${mode === m.id ? "border-primary bg-primary/5" : "hover:bg-secondary/40"}`}>
                  <m.icon className={`size-4 mt-0.5 ${mode === m.id ? "text-primary" : "text-muted-foreground"}`} />
                  <span className="min-w-0">
                    <span className="block text-sm font-medium">{m.title}{mode === m.id && <Badge className="ml-2 h-4 px-1.5 text-[10px]" variant="secondary">selected</Badge>}</span>
                    <span className="block text-xs text-muted-foreground">{m.desc}</span>
                  </span>
                </button>
              ))}
            </div>
          </div>
          <div className="space-y-1.5">
            <Label className="text-xs uppercase tracking-wide text-muted-foreground">Teacher collection permission</Label>
            <Select value={permMode} onValueChange={(v: any) => setPermMode(v)}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="ALL">All — every teacher, every fee type</SelectItem>
                <SelectItem value="NONE">None — teachers cannot collect</SelectItem>
                <SelectItem value="SELECTIVE">Selective — per class & fee type</SelectItem>
              </SelectContent>
            </Select>
          </div>
          {permMode === "SELECTIVE" && (
            <div className="grid gap-3 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label>Classes</Label>
                <div className="border rounded-lg max-h-40 overflow-y-auto nice-scroll">
                  {data.settings.classrooms.map((c) => (
                    <label key={c.id} className="flex items-center gap-2 px-2.5 py-1.5 border-b last:border-0 text-sm hover:bg-secondary/40 cursor-pointer">
                      <input type="checkbox" checked={classIds.includes(c.id)} onChange={() => toggle(classIds, c.id, setClassIds)} className="accent-primary" />
                      <span className="truncate">{c.name}</span>
                    </label>
                  ))}
                </div>
                <p className="text-[11px] text-muted-foreground">{classIds.length} of {data.settings.classrooms.length} classes</p>
              </div>
              <div className="space-y-1.5">
                <Label>Fee types</Label>
                <div className="border rounded-lg max-h-40 overflow-y-auto nice-scroll">
                  {activeServices.map((s) => (
                    <label key={s.id} className="flex items-center gap-2 px-2.5 py-1.5 border-b last:border-0 text-sm hover:bg-secondary/40 cursor-pointer">
                      <input type="checkbox" checked={svcIds.includes(s.id)} onChange={() => toggle(svcIds, s.id, setSvcIds)} className="accent-primary" />
                      <span className="truncate">{s.name}</span>
                    </label>
                  ))}
                </div>
                <p className="text-[11px] text-muted-foreground">{svcIds.length} of {activeServices.length} services</p>
              </div>
            </div>
          )}
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : "Save policy"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── FEE RATES tab (iedupro daily_fee_rates: per class-section per fee type) ──
// Resolution order shown in the legend: student override → class rate → standard.
function RatesTab({ data, onSaved }: { data: DailyData; onSaved: () => void }) {
  const { currentSchoolId } = useStore();
  const [svcId, setSvcId] = useState<string>(data.services[0]?.id ?? "");
  const [edits, setEdits] = useState<Record<string, string>>({});
  const [busy, setBusy] = useState(false);
  const svc = data.services.find((s) => s.id === svcId) ?? data.services[0];
  const rateByClass = new Map((svc?.classRates ?? []).map((r) => [r.classroomId, r.price]));

  async function save(classroomId: string) {
    if (!svc || !currentSchoolId) return;
    const raw = edits[classroomId];
    if (raw == null) return;
    const price = Math.round(parseFloat(raw || "0") * 100);
    setBusy(true);
    try {
      if (!raw || price <= 0) {
        await apiPost(`/api/school/${currentSchoolId}/ops/daily`, { action: "rate-clear", serviceId: svc.id, classroomId });
        toast({ title: "Override removed", description: "This class now bills at the standard rate." });
      } else {
        await apiPost(`/api/school/${currentSchoolId}/ops/daily`, { action: "rate-save", serviceId: svc.id, classroomId, priceCents: price });
        toast({ title: "Class rate saved", description: `${svc.name} — ${fmtCedi(price)} for this class.` });
      }
      setEdits((e) => { const n = { ...e }; delete n[classroomId]; return n; });
      onSaved();
    } catch (e: any) { toast({ title: "Save failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  if (!svc) return <EmptyState title="No services yet" hint="Create a service first." />;
  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="pb-3 flex-row items-center justify-between space-y-0 flex-wrap gap-2">
          <CardTitle className="text-base">Rate matrix — {svc.name}</CardTitle>
          <div className="flex items-center gap-2 text-xs text-muted-foreground">
            <Badge variant="outline" className="text-[10px]">standard {fmtCedi(svc.price)}</Badge>
            <span>{svc.chargeMode === "WEEKLY" ? "weekly service" : "daily service"} · {svc.enrolled} enrolled</span>
          </div>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap gap-1.5 mb-3">
            {data.services.map((s) => (
              <button key={s.id} onClick={() => setSvcId(s.id)}
                className={`rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors ${s.id === svcId ? "border-primary bg-primary/10 text-primary" : "hover:bg-secondary/50"}`}>
                {s.name}
              </button>
            ))}
          </div>
          <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            {data.settings.classrooms.map((c) => {
              const override = rateByClass.get(c.id);
              const value = edits[c.id] ?? (override != null ? (override / 100).toFixed(2) : "");
              const dirty = c.id in edits;
              return (
                <div key={c.id} className={`rounded-xl border p-3 ${override != null ? "bg-amber-50/60 dark:bg-amber-950/20 border-amber-200 dark:border-amber-900" : ""}`}>
                  <div className="flex items-center justify-between gap-2">
                    <div className="text-sm font-medium truncate">{c.name}</div>
                    {override != null && <Badge className="bg-amber-500 text-[9px]">class rate</Badge>}
                  </div>
                  <div className="text-[11px] text-muted-foreground mb-2">
                    {override != null ? "overridden" : `standard ${fmtCedi(svc.price)}`}
                  </div>
                  <div className="flex gap-1.5">
                    <Input
                      type="number" step="0.25" min="0" placeholder={`std ${(svc.price / 100).toFixed(2)}`}
                      value={value}
                      onChange={(e) => setEdits((ed) => ({ ...ed, [c.id]: e.target.value }))}
                      className="h-8"
                    />
                    <Button size="sm" className="h-8" disabled={!dirty || busy} onClick={() => save(c.id)}>Save</Button>
                  </div>
                </div>
              );
            })}
          </div>
          <p className="text-xs text-muted-foreground mt-3">
            A learner's effective rate resolves <b>student override → class rate → standard</b> — the same order iEduPro uses for its per-class fee rates. Transport follows the bus route's one-way fare instead (set it in the Transportation module).
          </p>
        </CardContent>
      </Card>
    </div>
  );
}

// ── RECONCILIATION tab (iedupro daily_fee_reconciliation + collector handover
//    + daily_fee_audit_log: every charge/payment/top-up/adjustment event) ──
function ReconciliationTab({ data, date, auditStudentId, onAuditStudent }: { data: DailyData; date: string; auditStudentId: string; onAuditStudent: (id: string) => void }) {
  const r = data.reconciliation;
  const ok = Math.abs(r.variance) < 1; // pesewa-level match
  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <StatCard icon={<Percent className="size-4" />} label="Charged today" value={fmtCedi(r.billed, { compact: true })} sub={`${r.txns} charges`} />
        <StatCard icon={<Wallet className="size-4" />} label="Collected" value={fmtCedi(r.collected, { compact: true })} tone="primary" sub={`counter ${fmtCedi(r.counter, { compact: true })} · wallets ${fmtCedi(r.walletPays, { compact: true })}`} />
        <StatCard icon={<History className="size-4" />} label="Still owing" value={fmtCedi(r.outstanding, { compact: true })} tone={r.outstanding > 0 ? "danger" : "default"} sub={`waived ${fmtCedi(r.waived, { compact: true })}`} />
        <StatCard icon={<BadgeCheck className="size-4" />} label="Variance" value={ok ? "Balanced" : fmtCedi(Math.abs(r.variance), { compact: true })} tone={ok ? "primary" : "danger"} sub={ok ? "charges trace to receipts" : "untraced settlements"} />
      </div>
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base">Collector handover — {fmtDate(date)}</CardTitle>
        </CardHeader>
        <CardContent>
          {r.byCollector.length ? (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Collector</TableHead>
                  <TableHead className="text-right">Counter takings</TableHead>
                  <TableHead className="text-right">Receipts</TableHead>
                  <TableHead className="text-right">Wallet deductions</TableHead>
                  <TableHead className="text-right">Handover total</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {r.byCollector.map((c) => (
                  <TableRow key={c.name}>
                    <TableCell className="font-medium">{c.name}</TableCell>
                    <TableCell className="text-right tabular-nums">{fmtCedi(c.counter)}</TableCell>
                    <TableCell className="text-right tabular-nums">{c.receipts}</TableCell>
                    <TableCell className="text-right tabular-nums">{fmtCedi(c.wallet)}</TableCell>
                    <TableCell className="text-right tabular-nums font-semibold">{fmtCedi(c.total)}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          ) : (
            <EmptyState title="No collections on this day" hint="Takings appear here as soon as the counter or a wallet settles a charge." />
          )}
          <p className="text-xs text-muted-foreground mt-3">
            Counter takings are cash/MoMo receipts; wallet deductions are prepaid and transport-wallet settlements (including boarding marks made by the conductor). Balanced means every settled charge traces back to a receipt or wallet entry.
          </p>
        </CardContent>
      </Card>

      {/* ── AUDIT LOG (iEduPro daily_fee_audit_log): every wallet/charge event, newest first ── */}
      <Card>
        <CardHeader className="pb-3 flex-row items-center justify-between space-y-0 flex-wrap gap-2">
          <CardTitle className="text-base flex items-center gap-2"><ScrollText className="size-4 text-primary" /> Daily-fee audit log</CardTitle>
          <div className="flex items-center gap-2">
            <span className="text-xs text-muted-foreground hidden sm:inline">Learner</span>
            <Select value={auditStudentId || "all"} onValueChange={(v) => onAuditStudent(v === "all" ? "" : v)}>
              <SelectTrigger className="h-8 w-52"><SelectValue /></SelectTrigger>
              <SelectContent className="max-h-64">
                <SelectItem value="all">All learners</SelectItem>
                {data.posStudents.map((s) => (
                  <SelectItem key={s.id} value={s.id}>{s.name} · {s.classroom}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </CardHeader>
        <CardContent className="p-0">
          {data.audit.length ? (
            <div className="overflow-x-auto max-h-[520px] nice-scroll">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="whitespace-nowrap">When</TableHead>
                    <TableHead>Learner</TableHead>
                    <TableHead>Action</TableHead>
                    <TableHead className="hidden md:table-cell">Service</TableHead>
                    <TableHead className="text-right">Amount</TableHead>
                    <TableHead className="text-right hidden lg:table-cell whitespace-nowrap">Wallet →</TableHead>
                    <TableHead className="text-right hidden lg:table-cell whitespace-nowrap">Arrears →</TableHead>
                    <TableHead className="hidden md:table-cell">By</TableHead>
                    <TableHead className="hidden xl:table-cell">Reference / notes</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data.audit.map((a) => {
                    const act = AUDIT_ACTION[a.actionType] ?? { label: a.actionType, className: "bg-zinc-500 text-white" };
                    const isCredit = a.actionType === "TOPUP";
                    const isDebit = a.actionType === "CHARGE" || a.actionType === "ADJUSTMENT";
                    return (
                      <TableRow key={a.id} className={a.actionType === "PAYMENT_REVERSAL" || a.actionType === "CHARGE_REVERSAL" ? "bg-destructive/5" : undefined}>
                        <TableCell className="whitespace-nowrap text-xs text-muted-foreground">{fmtDateTime(a.at)}</TableCell>
                        <TableCell>
                          <div className="font-medium">{a.student}</div>
                          <div className="text-[11px] text-muted-foreground">{a.classroom ?? "—"}</div>
                        </TableCell>
                        <TableCell><span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full whitespace-nowrap ${act.className}`}>{act.label}</span></TableCell>
                        <TableCell className="hidden md:table-cell text-xs">{a.service}</TableCell>
                        <TableCell className={`text-right tabular-nums font-semibold whitespace-nowrap ${isCredit ? "text-emerald-600 dark:text-emerald-400" : isDebit ? "text-amber-600 dark:text-amber-400" : ""}`}>
                          {fmtCedi(a.amount)}
                        </TableCell>
                        <TableCell className="text-right hidden lg:table-cell tabular-nums text-xs whitespace-nowrap">
                          {a.balanceBefore === a.balanceAfter
                            ? <span className="text-muted-foreground">{fmtCedi(a.balanceAfter, { compact: true })}</span>
                            : <>{fmtCedi(a.balanceBefore, { compact: true })} <span className="text-muted-foreground">→</span> <b>{fmtCedi(a.balanceAfter, { compact: true })}</b></>}
                        </TableCell>
                        <TableCell className="text-right hidden lg:table-cell tabular-nums text-xs whitespace-nowrap">
                          {a.arrearsBefore === a.arrearsAfter
                            ? <span className="text-muted-foreground">{fmtCedi(a.arrearsAfter, { compact: true })}</span>
                            : <>{fmtCedi(a.arrearsBefore, { compact: true })} <span className="text-muted-foreground">→</span> <b className={a.arrearsAfter > a.arrearsBefore ? "text-destructive" : "text-emerald-600 dark:text-emerald-400"}>{fmtCedi(a.arrearsAfter, { compact: true })}</b></>}
                        </TableCell>
                        <TableCell className="hidden md:table-cell text-xs">
                          <div>{a.by}</div>
                          <div className="text-[10px] text-muted-foreground">{a.byRole}</div>
                        </TableCell>
                        <TableCell className="hidden xl:table-cell text-xs text-muted-foreground max-w-56">
                          <div className="truncate">{a.notes ?? "—"}</div>
                          {a.reference && <div className="font-mono text-[10px] truncate">{a.reference}</div>}
                        </TableCell>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            </div>
          ) : (
            <EmptyState
              icon={<ScrollText className="size-6" />}
              title={auditStudentId ? "No audit entries for this learner yet" : "No audit entries yet"}
              hint={"Every day-charge generation, counter or wallet payment, top-up, waiver and balance correction is recorded here (latest 100)."}
            />
          )}
          <p className="text-xs text-muted-foreground px-4 pb-4 pt-2">
            Immutable trail (iEduPro daily_fee_audit_log): wallet column shows prepaid + transport credit before → after the event; arrears show unpaid daily charges before → after. Latest 100 events{auditStudentId ? " · filtered to one learner" : ""}.
          </p>
        </CardContent>
      </Card>
    </div>
  );
}

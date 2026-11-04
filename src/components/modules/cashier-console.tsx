"use client";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { ModuleHeader, LoadingBlock, EmptyState, downloadCsv } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, PAYMENT_LABELS } from "@/lib/format";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { toast } from "@/hooks/use-toast";
import { ReceiptDocument, BulkReceiptsDocument } from "@/components/receipts";
import {
  Receipt, Banknote, TrendingUp, Users, UtensilsCrossed, Banknote as CashIcon, Smartphone, RefreshCcw, Wallet,
  Percent, Printer, Save, ClipboardCheck, CalendarDays, ArrowLeftRight, Search, FileSpreadsheet, ListChecks,
  BookOpen, Droplets, Bus, Coffee, HandCoins, ChevronRight,
} from "lucide-react";

type ReportRow = {
  id: string; date: string; student: string; classroom: string;
  byService: Record<string, number>; total: number; method: string; receiptNo: string; collector: string;
};
type CashierData = {
  period: { from: string; to: string };
  collector: string;
  totals: { collected: number; transactions: number; studentsServed: number };
  split: { invoiceCollected: number; dailyCollected: number; transportCollected: number };
  byMethod: Record<string, number>;
  feeBreakdown: { service: string; collected: number; outstanding: number }[];
  performance: { name: string; total: number; count: number; share: number }[];
  collectors: string[];
  recent: { id: string; receiptNo: string; amount: number; method: string; paidAt: string; student: string; category: string; receivedByName: string | null }[];
  report: {
    serviceNames: string[];
    serviceTotals: Record<string, number>;
    grandTotal: number; count: number;
    rows: ReportRow[];
    classSummary: { classroom: string; students: number; totals: Record<string, number>; total: number; count: number }[];
  };
  roster: { id: string; name: string; index: string; classroom: string }[];
};

const todayStr = () => new Date().toISOString().slice(0, 10);
const METHOD_ICON: Record<string, React.ReactNode> = {
  CASH: <CashIcon className="size-3.5" />, MOMO_MTN: <Smartphone className="size-3.5" />, MOMO_TELECEL: <Smartphone className="size-3.5" />,
  MOMO_AT: <Smartphone className="size-3.5" />, BANK: <Banknote className="size-3.5" />, CHEQUE: <Banknote className="size-3.5" />, CARD: <Banknote className="size-3.5" />,
};
/* CI3 per-fee-type colour system (fee_collection portal) */
const SERVICE_ICON: Record<string, React.ReactNode> = {
  Lunch: <UtensilsCrossed className="size-4" />, Feeding: <UtensilsCrossed className="size-4" />,
  Breakfast: <Coffee className="size-4" />, "Sachet Water": <Droplets className="size-4" />,
  Water: <Droplets className="size-4" />, Classes: <BookOpen className="size-4" />,
  "Transport Fare (Bus)": <Bus className="size-4" />, Transport: <Bus className="size-4" />,
  "Termly fees": <Receipt className="size-4" />, "Store / tuck": <Wallet className="size-4" />,
};
const SERVICE_TONE: Record<string, string> = {
  Lunch: "border-amber-400 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200",
  Feeding: "border-amber-400 bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200",
  Breakfast: "border-orange-400 bg-orange-50 dark:bg-orange-950/40 text-orange-900 dark:text-orange-200",
  "Sachet Water": "border-sky-400 bg-sky-50 dark:bg-sky-950/40 text-sky-900 dark:text-sky-200",
  Water: "border-sky-400 bg-sky-50 dark:bg-sky-950/40 text-sky-900 dark:text-sky-200",
  Classes: "border-violet-400 bg-violet-50 dark:bg-violet-950/40 text-violet-900 dark:text-violet-200",
  "Transport Fare (Bus)": "border-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200",
  Transport: "border-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200",
  "Termly fees": "border-blue-400 bg-blue-50 dark:bg-blue-950/40 text-blue-900 dark:text-blue-200",
  "Store / tuck": "border-teal-400 bg-teal-50 dark:bg-teal-950/40 text-teal-900 dark:text-teal-200",
};

export function CashierConsole() {
  const { currentSchoolId, setModule, navTarget } = useStore();
  const [from, setFrom] = useState(todayStr());
  const [to, setTo] = useState(todayStr());
  const [collector, setCollector] = useState("all");
  const [data, setData] = useState<CashierData | null>(null);
  const [loading, setLoading] = useState(true);
  const [tab, setTab] = useState("portal");

  /* iEduPro nested nav: deep-link to any console tab */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab && ["portal", "collections", "summary", "receipts", "ledger", "counter"].includes(navTarget.tab)) setTab(navTarget.tab === "counter" ? "portal" : navTarget.tab);
  }

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<CashierData>(`/api/school/${currentSchoolId}/ops/cashier?from=${from}&to=${to}&collector=${encodeURIComponent(collector)}`)
      .then(setData)
      .catch((e) => toast({ title: "Could not load collections", description: e.message, variant: "destructive" }))
      .finally(() => setLoading(false));
  }, [currentSchoolId, from, to, collector]);
  useEffect(() => { load(); }, [load]);

  return (
    <div>
      <ModuleHeader
        title="Cashier Console"
        description="The fee-collection portal — today's takings per fee type, my collections report, daily summary & signed handover, thermal receipts and the student ledger."
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <div className="flex items-center gap-1.5">
              <Label className="text-xs text-muted-foreground hidden sm:block">From</Label>
              <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="h-8 w-36" />
            </div>
            <div className="flex items-center gap-1.5">
              <Label className="text-xs text-muted-foreground hidden sm:block">To</Label>
              <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} className="h-8 w-36" />
            </div>
            {data && data.collectors.length > 0 && (
              <Select value={collector} onValueChange={setCollector}>
                <SelectTrigger className="h-8 w-44"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All cashiers</SelectItem>
                  {data.collectors.map((c) => <SelectItem key={c} value={c}>{c}</SelectItem>)}
                </SelectContent>
              </Select>
            )}
            <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={load}><RefreshCcw className="size-3.5" /> Refresh</Button>
          </div>
        }
      />

      <Tabs value={tab} onValueChange={setTab} className="mt-0">
        <TabsList className="flex-wrap">
          <TabsTrigger value="portal" className="gap-1.5"><Receipt className="size-4" /> Collection portal</TabsTrigger>
          <TabsTrigger value="collections" className="gap-1.5"><HandCoins className="size-4" /> My collections</TabsTrigger>
          <TabsTrigger value="summary" className="gap-1.5"><ClipboardCheck className="size-4" /> Daily summary</TabsTrigger>
          <TabsTrigger value="receipts" className="gap-1.5"><Printer className="size-4" /> Print receipts</TabsTrigger>
          <TabsTrigger value="ledger" className="gap-1.5"><ListChecks className="size-4" /> Student ledger</TabsTrigger>
        </TabsList>

        <TabsContent value="portal">
      {loading && !data ? <LoadingBlock label="Counting the drawer…" /> : data ? (
        <>
          {/* CI3 fee-collection portal KPI strip — one colourful card per fee type + total */}
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
            {Object.entries(data.report.serviceTotals).sort((a, b) => b[1] - a[1]).slice(0, 5).map(([svc, amt]) => (
              <div key={svc} className="rounded-xl border-l-4 border bg-card p-3" style={{ borderColor: undefined }}>
                <div className="flex items-center gap-1.5 text-xs font-semibold">{SERVICE_ICON[svc] ?? <Wallet className="size-4" />} {svc}</div>
                <div className="mt-1 text-lg font-bold tabular-nums">{fmtCedi(amt, { compact: true })}</div>
                <div className="text-[11px] text-muted-foreground">{fmtDate(data.period.from)}{data.period.from !== data.period.to ? ` → ${fmtDate(data.period.to)}` : ""}</div>
              </div>
            ))}
            <div className="rounded-xl bg-gradient-to-br from-emerald-600 to-emerald-500 text-white p-3 shadow-sm">
              <div className="flex items-center gap-1.5 text-xs font-semibold"><Banknote className="size-4" /> Total collected</div>
              <div className="mt-1 text-lg font-bold tabular-nums">{fmtCedi(data.totals.collected, { compact: true })}</div>
              <div className="text-[11px] text-white/80">{data.totals.transactions} receipts</div>
            </div>
          </div>

          {/* quick actions — CI3 portal shortcuts */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
            <Button variant="outline" className="h-auto flex-col gap-1 py-3" onClick={() => setModule("daily", { tab: "pos" })}>
              <UtensilsCrossed className="size-5 text-primary" /><span className="text-xs font-semibold">Collect daily fees</span><span className="text-[10px] text-muted-foreground">counter / POS</span>
            </Button>
            <Button variant="outline" className="h-auto flex-col gap-1 py-3" onClick={() => setModule("fees", { tab: "create" })}>
              <Receipt className="size-5 text-primary" /><span className="text-xs font-semibold">Create invoices</span><span className="text-[10px] text-muted-foreground">bill learners</span>
            </Button>
            <Button variant="outline" className="h-auto flex-col gap-1 py-3" onClick={() => setTab("receipts")}>
              <Printer className="size-5 text-primary" /><span className="text-xs font-semibold">Print receipts</span><span className="text-[10px] text-muted-foreground">80mm thermal</span>
            </Button>
            <Button variant="outline" className="h-auto flex-col gap-1 py-3" onClick={() => setTab("collections")}>
              <FileSpreadsheet className="size-5 text-primary" /><span className="text-xs font-semibold">My collections</span><span className="text-[10px] text-muted-foreground">render the day</span>
            </Button>
          </div>

          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <Tile icon={<Wallet className="size-4" />} label="Total collected" value={fmtCedi(data.totals.collected, { compact: true })} sub={`${fmtDate(data.period.from)} → ${fmtDate(data.period.to)}`} tone="gold" />
            <Tile icon={<Receipt className="size-4" />} label="Transactions" value={data.totals.transactions} sub="receipts issued" />
            <Tile icon={<Users className="size-4" />} label="Learners served" value={data.totals.studentsServed} sub="unique at the counter" />
            <Tile icon={<TrendingUp className="size-4" />} label="Invoices vs daily" value={`${fmtCedi(data.split.invoiceCollected, { compact: true })} / ${fmtCedi(data.split.dailyCollected + data.split.transportCollected, { compact: true })}`} sub="term bills vs daily & transport" />
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
            {/* fee breakdown — collected vs outstanding */}
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-base flex items-center gap-2"><UtensilsCrossed className="size-4" /> Fee-type breakdown</CardTitle>
              </CardHeader>
              <CardContent>
                {data.feeBreakdown.length ? (
                  <div className="space-y-3">
                    {data.feeBreakdown.map((f) => {
                      const max = Math.max(f.collected, f.outstanding, 1);
                      return (
                        <div key={f.service}>
                          <div className="flex items-center justify-between text-sm">
                            <span className="font-medium">{f.service}</span>
                            <span className="text-xs text-muted-foreground tabular-nums">{fmtCedi(f.collected)} in · {fmtCedi(f.outstanding)} owed</span>
                          </div>
                          <div className="mt-1 flex gap-1 h-2">
                            <div className="rounded-full bg-emerald-500" style={{ width: `${(f.collected / max) * 100}%` }} />
                            <div className="rounded-full bg-destructive/70" style={{ width: `${(f.outstanding / max) * 100}%` }} />
                          </div>
                        </div>
                      );
                    })}
                    <div className="flex gap-4 pt-1 text-[11px] text-muted-foreground">
                      <span className="flex items-center gap-1"><span className="size-2 rounded-full bg-emerald-500" /> collected (period)</span>
                      <span className="flex items-center gap-1"><span className="size-2 rounded-full bg-destructive/70" /> outstanding (all-time)</span>
                    </div>
                  </div>
                ) : <EmptyState title="No daily-fee activity" hint="Charges appear once daily services run." />}
              </CardContent>
            </Card>

            {/* method mix + performance */}
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Percent className="size-4" /> How parents paid</CardTitle></CardHeader>
              <CardContent>
                {Object.keys(data.byMethod).length ? (
                  <div className="space-y-2">
                    {Object.entries(data.byMethod).sort((a, b) => b[1] - a[1]).map(([m, v]) => {
                      const total = data.totals.collected || 1;
                      return (
                        <div key={m} className="flex items-center gap-3">
                          <span className="flex size-8 items-center justify-center rounded-lg bg-secondary text-muted-foreground">{METHOD_ICON[m] ?? <Banknote className="size-3.5" />}</span>
                          <div className="flex-1 min-w-0">
                            <div className="flex justify-between text-sm"><span className="font-medium truncate">{PAYMENT_LABELS[m] ?? m}</span><span className="tabular-nums">{fmtCedi(v)}</span></div>
                            <div className="mt-0.5 h-1.5 rounded-full bg-secondary overflow-hidden"><div className="h-full bg-primary" style={{ width: `${(v / total) * 100}%` }} /></div>
                          </div>
                          <span className="text-xs text-muted-foreground tabular-nums w-10 text-right">{Math.round((v / total) * 100)}%</span>
                        </div>
                      );
                    })}
                  </div>
                ) : <EmptyState title="No payments in this window" />}
              </CardContent>
            </Card>
          </div>

          {/* recent transactions — CI3 dashboard_recent table */}
          <Card>
            <CardHeader className="pb-2 flex-row items-center justify-between space-y-0">
              <CardTitle className="text-base">Recent transactions</CardTitle>
              <Button size="sm" variant="ghost" className="h-7 gap-1 text-xs" onClick={() => setTab("collections")}>Full report <ChevronRight className="size-3.5" /></Button>
            </CardHeader>
            <CardContent className="p-0">
              <div className="overflow-x-auto nice-scroll max-h-80">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                    <tr className="text-left border-b">
                      <th className="px-3 py-2.5 font-medium">Time</th>
                      <th className="px-3 py-2.5 font-medium">Learner</th>
                      <th className="px-3 py-2.5 font-medium">Receipt</th>
                      <th className="px-3 py-2.5 font-medium text-right">Amount</th>
                      <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Method</th>
                      <th className="px-3 py-2.5 font-medium hidden md:table-cell">Collector</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.recent.map((r) => (
                      <tr key={r.id} className="border-b last:border-0 hover:bg-secondary/30">
                        <td className="px-3 py-2 text-xs text-muted-foreground">{new Date(r.paidAt).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" })}</td>
                        <td className="px-3 py-2 font-medium">{r.student}</td>
                        <td className="px-3 py-2"><Badge variant="outline" className="font-mono text-[10px]">{r.receiptNo}</Badge></td>
                        <td className="px-3 py-2 text-right tabular-nums font-semibold text-emerald-600">{fmtCedi(r.amount)}</td>
                        <td className="px-3 py-2 hidden sm:table-cell"><span className="inline-flex items-center gap-1.5 text-xs">{METHOD_ICON[r.method]} {PAYMENT_LABELS[r.method] ?? r.method}</span></td>
                        <td className="px-3 py-2 hidden md:table-cell text-xs text-muted-foreground">{r.receivedByName ?? "—"}</td>
                      </tr>
                    ))}
                    {!data.recent.length && <tr><td colSpan={6}><EmptyState title="No transactions yet" hint="Collections appear here the moment they are taken." /></td></tr>}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </>
      ) : <LoadingBlock label="Counting the drawer…" />}
        </TabsContent>

        <TabsContent value="collections">
          <MyCollectionsTab data={data} collector={collector} />
        </TabsContent>

        <TabsContent value="summary">
          <SummaryHandoverTab />
        </TabsContent>

        <TabsContent value="receipts">
          <PrintReceiptsTab data={data} onReprint={() => load()} />
        </TabsContent>

        <TabsContent value="ledger">
          <StudentLedgerTab data={data} />
        </TabsContent>
      </Tabs>
    </div>
  );
}

/* ── MY COLLECTIONS (CI3 my_collections.php — detailed & class summary reports) ── */
function MyCollectionsTab({ data, collector }: { data: CashierData | null; collector: string }) {
  const [reportType, setReportType] = useState<"detailed" | "class">("detailed");
  const [classFilter, setClassFilter] = useState("all");
  const [methodFilter, setMethodFilter] = useState("all");
  const [search, setSearch] = useState("");

  const rows = useMemo(() => {
    let list = data?.report.rows ?? [];
    if (classFilter !== "all") list = list.filter((r) => r.classroom === classFilter);
    if (methodFilter !== "all") list = list.filter((r) => r.method === methodFilter);
    if (search.trim()) {
      const q = search.trim().toLowerCase();
      list = list.filter((r) => `${r.student} ${r.receiptNo} ${r.classroom}`.toLowerCase().includes(q));
    }
    return list;
  }, [data, classFilter, methodFilter, search]);

  const serviceNames = useMemo(() => {
    const names = new Set<string>();
    rows.forEach((r) => Object.keys(r.byService).forEach((s) => names.add(s)));
    return [...names];
  }, [rows]);
  const totals = useMemo(() => {
    const t: Record<string, number> = {};
    let grand = 0;
    rows.forEach((r) => { grand += r.total; Object.entries(r.byService).forEach(([s, v]) => { t[s] = (t[s] ?? 0) + v; }); });
    return { ...t, grand };
  }, [rows]);
  const classOptions = useMemo(() => [...new Set((data?.report.rows ?? []).map((r) => r.classroom).filter(Boolean))].sort(), [data]);
  const methodOptions = useMemo(() => [...new Set((data?.report.rows ?? []).map((r) => r.method))], [data]);

  function exportCsv() {
    const header = ["Date", "Student", "Class", ...serviceNames, "Total", "Method", "Receipt", "Collector"];
    const body = rows.map((r) => [
      new Date(r.date).toLocaleString("en-GB"), r.student, r.classroom,
      ...serviceNames.map((s) => ((r.byService[s] ?? 0) / 100).toFixed(2)),
      (r.total / 100).toFixed(2), r.method, r.receiptNo, r.collector,
    ]);
    downloadCsv(`collections-${data?.period.from}_${data?.period.to}.csv`, header, body);
  }

  if (!data) return <LoadingBlock label="Rendering the account…" />;
  return (
    <div className="space-y-4">
      {/* filters — CI3 report controls */}
      <Card>
        <CardContent className="p-4 flex flex-wrap items-end gap-3">
          <div className="space-y-1.5">
            <Label className="text-xs">Report type</Label>
            <Select value={reportType} onValueChange={(v: any) => setReportType(v)}>
              <SelectTrigger className="w-44 h-9"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="detailed">Detailed report</SelectItem>
                <SelectItem value="class">Class summary</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label className="text-xs">Class</Label>
            <Select value={classFilter} onValueChange={setClassFilter}>
              <SelectTrigger className="w-44 h-9"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All classes</SelectItem>
                {classOptions.map((c) => <SelectItem key={c} value={c}>{c}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label className="text-xs">Method</Label>
            <Select value={methodFilter} onValueChange={setMethodFilter}>
              <SelectTrigger className="w-40 h-9"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All methods</SelectItem>
                {methodOptions.map((m) => <SelectItem key={m} value={m}>{PAYMENT_LABELS[m] ?? m}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="relative flex-1 min-w-44">
            <Label className="text-xs">Search</Label>
            <div className="relative">
              <Search className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
              <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Student, receipt, class…" className="h-9 pl-8" />
            </div>
          </div>
          <div className="flex gap-2 ml-auto">
            <Button size="sm" variant="outline" className="gap-1.5 h-9" onClick={exportCsv} disabled={!rows.length}><FileSpreadsheet className="size-4" /> Export Excel</Button>
            <Button size="sm" variant="outline" className="gap-1.5 h-9" onClick={() => window.print()}><Printer className="size-4" /> Print report</Button>
          </div>
        </CardContent>
      </Card>

      {/* per-fee-type summary cards + grand total (CI3 summary cards) */}
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 print-area">
        {Object.entries(totals).filter(([k]) => k !== "grand").sort((a, b) => b[1] - a[1]).slice(0, 5).map(([svc, amt]) => (
          <div key={svc} className={`rounded-xl border-l-4 bg-card p-3 ${SERVICE_TONE[svc] ? "" : "border-border"}`} style={{ borderLeftColor: undefined }}>
            <div className="flex items-center gap-1.5 text-xs font-semibold">{SERVICE_ICON[svc] ?? <Wallet className="size-4" />} {svc}</div>
            <div className="mt-1 text-lg font-bold tabular-nums">{fmtCedi(amt, { compact: true })}</div>
          </div>
        ))}
        <div className="rounded-xl bg-gradient-to-br from-emerald-600 to-emerald-500 text-white p-3">
          <div className="text-xs font-semibold uppercase tracking-wide">Grand total</div>
          <div className="mt-1 text-xl font-bold tabular-nums">{fmtCedi(totals.grand)}</div>
          <div className="text-[11px] text-white/80">{rows.length} transaction{rows.length !== 1 ? "s" : ""}</div>
        </div>
      </div>

      {reportType === "detailed" ? (
        <Card className="print-area">
          <CardContent className="p-0">
            <div className="overflow-x-auto nice-scroll max-h-[560px]">
              <table className="w-full text-sm">
                <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                  <tr className="text-left border-b">
                    <th className="px-3 py-2.5 font-medium">#</th>
                    <th className="px-3 py-2.5 font-medium">Date</th>
                    <th className="px-3 py-2.5 font-medium">Student</th>
                    <th className="px-3 py-2.5 font-medium hidden md:table-cell">Class</th>
                    {serviceNames.map((s) => <th key={s} className="px-3 py-2.5 font-medium text-right hidden lg:table-cell">{s}</th>)}
                    <th className="px-3 py-2.5 font-medium text-right">Total</th>
                    <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Method</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.map((r, i) => (
                    <tr key={r.id} className="border-b last:border-0 hover:bg-secondary/30">
                      <td className="px-3 py-2 text-muted-foreground">{i + 1}</td>
                      <td className="px-3 py-2 text-xs">{new Date(r.date).toLocaleDateString("en-GB")} <span className="text-muted-foreground">{new Date(r.date).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" })}</span></td>
                      <td className="px-3 py-2 font-medium">{r.student}</td>
                      <td className="px-3 py-2 hidden md:table-cell text-muted-foreground">{r.classroom}</td>
                      {serviceNames.map((s) => <td key={s} className="px-3 py-2 text-right tabular-nums hidden lg:table-cell">{r.byService[s] ? fmtCedi(r.byService[s]) : "—"}</td>)}
                      <td className="px-3 py-2 text-right tabular-nums font-semibold">{fmtCedi(r.total)}</td>
                      <td className="px-3 py-2 hidden sm:table-cell"><span className="inline-flex items-center gap-1.5 text-xs">{METHOD_ICON[r.method]} {PAYMENT_LABELS[r.method] ?? r.method}</span></td>
                    </tr>
                  ))}
                  {!rows.length && <tr><td colSpan={4 + serviceNames.length + 3}><EmptyState title="No collections match" hint="Adjust the class, method or search — or widen the dates." /></td></tr>}
                </tbody>
                {rows.length > 0 && (
                  <tfoot className="sticky bottom-0 bg-secondary/90 backdrop-blur font-semibold">
                    <tr className="border-t">
                      <td colSpan={3 + serviceNames.length} className="px-3 py-2.5">TOTAL — {collector === "all" ? "all collectors" : collector}</td>
                      <td className="px-3 py-2.5 text-right tabular-nums">{fmtCedi(totals.grand)}</td>
                      <td colSpan={2} />
                    </tr>
                  </tfoot>
                )}
              </table>
            </div>
          </CardContent>
        </Card>
      ) : (
        <Card className="print-area">
          <CardContent className="p-0">
            <div className="overflow-x-auto nice-scroll">
              <table className="w-full text-sm">
                <thead className="bg-secondary/70">
                  <tr className="text-left border-b">
                    <th className="px-3 py-2.5 font-medium">Class</th>
                    <th className="px-3 py-2.5 font-medium">Students</th>
                    {serviceNames.map((s) => <th key={s} className="px-3 py-2.5 font-medium text-right hidden lg:table-cell">{s}</th>)}
                    <th className="px-3 py-2.5 font-medium text-right">Total</th>
                    <th className="px-3 py-2.5 font-medium text-right hidden sm:table-cell">Receipts</th>
                  </tr>
                </thead>
                <tbody>
                  {(data.report.classSummary.filter((c) => classFilter === "all" || c.classroom === classFilter)).map((c) => (
                    <tr key={c.classroom} className="border-b last:border-0 hover:bg-secondary/30">
                      <td className="px-3 py-2.5 font-medium">{c.classroom}</td>
                      <td className="px-3 py-2.5 tabular-nums">{c.students}</td>
                      {serviceNames.map((s) => <td key={s} className="px-3 py-2.5 text-right tabular-nums hidden lg:table-cell">{c.totals[s] ? fmtCedi(c.totals[s]) : "—"}</td>)}
                      <td className="px-3 py-2.5 text-right tabular-nums font-semibold">{fmtCedi(c.total)}</td>
                      <td className="px-3 py-2.5 text-right tabular-nums hidden sm:table-cell text-muted-foreground">{c.count}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}

/* ── PRINT RECEIPTS (CI3 print_receipts.php — filter + bulk 80mm thermal print) ── */
function PrintReceiptsTab({ data, onReprint }: { data: CashierData | null; onReprint: () => void }) {
  const { currentSchoolId } = useStore();
  const [service, setService] = useState("all");
  const [search, setSearch] = useState("");
  const [checked, setChecked] = useState<Set<string>>(new Set());
  const [payloads, setPayloads] = useState<any[] | null>(null);
  const [busy, setBusy] = useState(false);

  const rows = useMemo(() => {
    let list = data?.report.rows ?? [];
    if (service !== "all") list = list.filter((r) => (r.byService[service] ?? 0) > 0);
    if (search.trim()) {
      const q = search.trim().toLowerCase();
      list = list.filter((r) => `${r.student} ${r.receiptNo} ${r.classroom}`.toLowerCase().includes(q));
    }
    return list;
  }, [data, service, search]);

  async function printSelected() {
    if (!currentSchoolId || !checked.size) return;
    setBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/fees`, { action: "receipt-payloads", paymentIds: [...checked] });
      const list = (res.receipts ?? []).map((r: any) => ({ ...r, style: "THERMAL" }));
      if (!list.length) return toast({ title: "No printable receipts", description: "The receipts could not be reconstructed.", variant: "destructive" });
      setPayloads(list);
    } catch (e: any) { toast({ title: "Load failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  if (!data) return <LoadingBlock label="Loading receipts…" />;
  return (
    <div className="space-y-4">
      <Card>
        <CardContent className="p-4 flex flex-wrap items-end gap-3">
          <div className="space-y-1.5">
            <Label className="text-xs">Fee type</Label>
            <Select value={service} onValueChange={setService}>
              <SelectTrigger className="w-48 h-9"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All items</SelectItem>
                {data.report.serviceNames.map((s) => <SelectItem key={s} value={s}>{s}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="relative flex-1 min-w-44">
            <Label className="text-xs">Student name</Label>
            <div className="relative">
              <Search className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
              <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Filter receipts…" className="h-9 pl-8" />
            </div>
          </div>
          <div className="flex gap-2 ml-auto">
            <Button size="sm" variant="outline" className="h-9 gap-1.5" onClick={() => setChecked(new Set(rows.map((r) => r.id)))} disabled={!rows.length}>Select all</Button>
            <Button size="sm" className="h-9 gap-1.5" onClick={printSelected} disabled={busy || !checked.size}>
              <Printer className="size-4" /> {busy ? "Preparing…" : `Print ${checked.size || ""} thermal`}
            </Button>
          </div>
        </CardContent>
      </Card>
      <p className="text-xs text-muted-foreground">
        {fmtDate(data.period.from)}{data.period.from !== data.period.to ? ` → ${fmtDate(data.period.to)}` : ""} · {rows.length} receipt{rows.length !== 1 ? "s" : ""} · tick the rows and press Print — each prints on the 80mm counter roll.
      </p>
      <Card>
        <CardContent className="p-0">
          <div className="overflow-x-auto nice-scroll max-h-[520px]">
            <table className="w-full text-sm">
              <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                <tr className="text-left border-b">
                  <th className="px-3 py-2.5 w-8">
                    <Checkbox checked={rows.length > 0 && rows.every((r) => checked.has(r.id))} onCheckedChange={(v) => setChecked(v ? new Set(rows.map((r) => r.id)) : new Set())} aria-label="Select all" />
                  </th>
                  <th className="px-3 py-2.5 font-medium">Receipt</th>
                  <th className="px-3 py-2.5 font-medium">Student</th>
                  <th className="px-3 py-2.5 font-medium hidden md:table-cell">Class</th>
                  <th className="px-3 py-2.5 font-medium text-right">Amount</th>
                  <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Method</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((r) => (
                  <tr key={r.id} className="border-b last:border-0 hover:bg-secondary/30">
                    <td className="px-3 py-2">
                      <Checkbox checked={checked.has(r.id)} onCheckedChange={(v) => setChecked((s) => { const n = new Set(s); v ? n.add(r.id) : n.delete(r.id); return n; })} aria-label={`Print ${r.receiptNo}`} />
                    </td>
                    <td className="px-3 py-2"><Badge variant="outline" className="font-mono text-[10px]">{r.receiptNo}</Badge></td>
                    <td className="px-3 py-2 font-medium">{r.student}</td>
                    <td className="px-3 py-2 hidden md:table-cell text-muted-foreground">{r.classroom}</td>
                    <td className="px-3 py-2 text-right tabular-nums font-semibold">{fmtCedi(r.total)}</td>
                    <td className="px-3 py-2 hidden sm:table-cell"><span className="inline-flex items-center gap-1.5 text-xs">{METHOD_ICON[r.method]} {PAYMENT_LABELS[r.method] ?? r.method}</span></td>
                  </tr>
                ))}
                {!rows.length && <tr><td colSpan={6}><EmptyState title="No receipts match" hint="Adjust the fee type or search, or widen the date range." /></td></tr>}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
      {payloads && <BulkReceiptsDocument receipts={payloads} onDone={() => { setPayloads(null); setChecked(new Set()); onReprint(); }} />}
    </div>
  );
}

/* ── STUDENT LEDGER (CI3 student_ledger.php — debit/credit running balance) ── */
function StudentLedgerTab({ data }: { data: CashierData | null }) {
  const { currentSchoolId } = useStore();
  const [search, setSearch] = useState("");
  const [studentId, setStudentId] = useState<string | null>(null);
  const [ledger, setLedger] = useState<{ student: any; rows: { date: string; kind: string; description: string; reference: string; amount: number; balance: number }[]; closing: number } | null>(null);
  const [busy, setBusy] = useState(false);

  const matches = useMemo(() => {
    if (!search.trim()) return [];
    const q = search.trim().toLowerCase();
    return (data?.roster ?? []).filter((r) => `${r.name} ${r.index} ${r.classroom}`.toLowerCase().includes(q)).slice(0, 8);
  }, [data, search]);

  const load = useCallback((id: string) => {
    if (!currentSchoolId) return;
    setBusy(true);
    apiPost<any>(`/api/school/${currentSchoolId}/ops/cashier`, { action: "ledger", studentId: id })
      .then(setLedger)
      .catch((e) => toast({ title: "Ledger failed", description: e.message, variant: "destructive" }))
      .finally(() => setBusy(false));
  }, [currentSchoolId]);
  useEffect(() => {
    let alive = true;
    (async () => {
      if (!studentId) { setLedger(null); return; }
      setBusy(true);
      try {
        const res = await apiPost<any>(`/api/school/${currentSchoolId}/ops/cashier`, { action: "ledger", studentId });
        if (alive) setLedger(res);
      } catch (e: any) {
        if (alive) toast({ title: "Ledger failed", description: e.message, variant: "destructive" });
      } finally {
        if (alive) setBusy(false);
      }
    })();
    return () => { alive = false; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [studentId, currentSchoolId]);

  return (
    <div className="space-y-4">
      <Card>
        <CardContent className="p-4 space-y-2">
          <Label className="text-xs">Student</Label>
          <div className="relative max-w-md">
            <Search className="size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
            <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Type a name, index or class…" className="pl-8" />
            {matches.length > 0 && (
              <div className="absolute z-20 mt-1 w-full rounded-lg border bg-popover shadow-lg overflow-hidden">
                {matches.map((m) => (
                  <button key={m.id} className="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-secondary" onClick={() => { setStudentId(m.id); setSearch(""); }}>
                    <span className="font-medium">{m.name}</span>
                    <span className="text-xs text-muted-foreground">{m.classroom} · {m.index}</span>
                  </button>
                ))}
              </div>
            )}
          </div>
        </CardContent>
      </Card>
      {!studentId ? (
        <EmptyState icon={<ListChecks className="size-5" />} title="Search a learner to generate their ledger" hint="Every bill and payment on one running balance — printable." />
      ) : busy || !ledger ? (
        <LoadingBlock label="Building the ledger…" />
      ) : (
        <Card className="print-area">
          <CardContent className="p-0">
            <div className="border-b px-4 py-3 flex flex-wrap items-center justify-between gap-2">
              <div>
                <div className="font-semibold">{ledger.student.name} <span className="text-xs text-muted-foreground font-normal">· {ledger.student.classroom} · {ledger.student.index}</span></div>
                <div className="text-xs text-muted-foreground">Guardian: {ledger.student.guardian} ({ledger.student.guardianPhone})</div>
              </div>
              <div className="text-right">
                <div className="text-xs uppercase text-muted-foreground font-semibold">Closing balance</div>
                <div className={`text-xl font-bold tabular-nums ${ledger.closing > 0 ? "text-destructive" : "text-emerald-600"}`}>{fmtCedi(ledger.closing)}</div>
              </div>
            </div>
            <div className="overflow-x-auto nice-scroll max-h-[480px]">
              <table className="w-full text-sm">
                <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                  <tr className="text-left border-b">
                    <th className="px-3 py-2.5 font-medium">Date</th>
                    <th className="px-3 py-2.5 font-medium">Description</th>
                    <th className="px-3 py-2.5 font-medium">Reference</th>
                    <th className="px-3 py-2.5 font-medium text-right text-destructive">Debit</th>
                    <th className="px-3 py-2.5 font-medium text-right text-emerald-600">Credit</th>
                    <th className="px-3 py-2.5 font-medium text-right">Balance</th>
                  </tr>
                </thead>
                <tbody>
                  {ledger.rows.map((r, i) => (
                    <tr key={i} className="border-b last:border-0 hover:bg-secondary/30">
                      <td className="px-3 py-2 text-xs">{new Date(r.date).toLocaleDateString("en-GB")}</td>
                      <td className="px-3 py-2">{r.description}</td>
                      <td className="px-3 py-2"><Badge variant="outline" className="font-mono text-[10px]">{r.reference}</Badge></td>
                      <td className="px-3 py-2 text-right tabular-nums">{r.kind === "DEBIT" ? fmtCedi(r.amount) : "—"}</td>
                      <td className="px-3 py-2 text-right tabular-nums text-emerald-600">{r.kind === "CREDIT" ? fmtCedi(r.amount) : "—"}</td>
                      <td className="px-3 py-2 text-right tabular-nums font-semibold">{fmtCedi(r.balance)}</td>
                    </tr>
                  ))}
                  {!ledger.rows.length && <tr><td colSpan={6}><EmptyState title="No transactions" /></td></tr>}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}

/* ── SUMMARY & HANDOVER (CI3 cashier daily summary + signed handover printables) ── */
type CashierSummary = {
  date: string;
  collector: string;
  totals: { totalCents: number; cashCents: number; transactions: number; distinctStudents: number; receipts: number };
  byCategory: Record<string, number>;
  byMethod: Record<string, number>;
  dailyArrearsCents: number;
  topups: { id: string; student: string; wallet: string; amountCents: number; byName: string | null; at: string }[];
  receipts: { id: string; receiptNo: string; student: string; classroom: string | null; amountCents: number; method: string; category: string; collector: string | null; paidAt: string }[];
  handovers: { id: string; date: string; cashierId: string; cashierName: string; receiverId: string; receiverName: string; totalCents: number; cashCents: number; createdAt: string }[];
  collectors: string[];
  users: { id: string; fullName: string; role: string }[];
};

const CATEGORY_LABELS: Record<string, string> = {
  INVOICE: "Termly fees", DAILY: "Daily services", TRANSPORT: "Transport fares", POS: "Store sales",
};

function SummaryHandoverTab() {
  const { currentSchoolId, schools, session } = useStore();
  const schoolName = schools.find((s) => s.id === currentSchoolId)?.name ?? "";
  const [date, setDate] = useState(todayStr());
  const [collector, setCollector] = useState("all");
  const [summary, setSummary] = useState<CashierSummary | null>(null);
  const [busy, setBusy] = useState(false);
  const [cashierId, setCashierId] = useState(session?.id ?? "");
  const [receiverId, setReceiverId] = useState("");
  const [saving, setSaving] = useState(false);

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    setBusy(true);
    apiGet<CashierSummary>(`/api/school/${currentSchoolId}/ops/reports?tab=cashier-summary&date=${date}&collector=${encodeURIComponent(collector)}`)
      .then(setSummary)
      .catch((e) => toast({ title: "Could not load the daily summary", description: e.message, variant: "destructive" }))
      .finally(() => setBusy(false));
  }, [currentSchoolId, date, collector]);
  useEffect(() => { load(); }, [load]);

  const cashier = summary?.users.find((u) => u.id === cashierId) ?? (summary?.users.find((u) => u.fullName === collector) ?? null);
  const receiver = summary?.users.find((u) => u.id === receiverId) ?? null;
  const categories = Object.entries(summary?.byCategory ?? {});
  const methods = Object.entries(summary?.byMethod ?? {}).sort((a, b) => b[1] - a[1]);

  async function saveHandover() {
    if (!currentSchoolId || !summary) return;
    if (!cashierId || !receiverId) { toast({ title: "Choose the handing-over cashier and the receiver" }); return; }
    setSaving(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/reports`, {
        action: "handover.save",
        date,
        cashierId,
        cashierName: cashier?.fullName ?? session?.fullName ?? "—",
        receiverId,
        receiverName: receiver?.fullName ?? "—",
        totalCents: summary.totals.totalCents,
        cashCents: summary.totals.cashCents,
      });
      toast({ title: "Handover saved", description: `${fmtDate(date)} — ${cashier?.fullName} → ${receiver?.fullName}. Re-saving the same cashier + date replaces the record.` });
      load();
    } catch (e: any) { toast({ title: "Could not save handover", description: e.message, variant: "destructive" }); } finally { setSaving(false); }
  }

  return (
    <div className="space-y-4">
      {/* controls */}
      <Card className="no-print">
        <CardContent className="p-4 flex flex-wrap items-end gap-3">
          <div className="space-y-1.5">
            <Label className="text-xs"><CalendarDays className="size-3 inline mr-1" /> Summary date</Label>
            <Input type="date" value={date} onChange={(e) => setDate(e.target.value)} className="w-40 h-9" />
          </div>
          <div className="space-y-1.5">
            <Label className="text-xs">Cashier</Label>
            <Select value={collector} onValueChange={setCollector}>
              <SelectTrigger className="w-48 h-9"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All cashiers</SelectItem>
                {(summary?.collectors ?? []).map((c) => <SelectItem key={c} value={c}>{c}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="flex gap-2 ml-auto">
            <Button size="sm" variant="outline" className="gap-1.5 h-9" onClick={load} disabled={busy}><RefreshCcw className="size-4" /> {busy ? "Loading…" : "Refresh"}</Button>
            <Button size="sm" variant="outline" className="gap-1.5 h-9" onClick={() => window.print()} disabled={!summary}><Printer className="size-4" /> Print report</Button>
            <Button size="sm" className="gap-1.5 h-9" onClick={saveHandover} disabled={saving || !summary}><Save className="size-4" /> {saving ? "Saving…" : "Save handover"}</Button>
          </div>
        </CardContent>
      </Card>

      {!summary && busy ? <Card><CardContent><LoadingBlock label="Rendering the day's account…" /></CardContent></Card> : !summary ? null : (
        <div className="print-area space-y-4">
          {/* ── printable daily summary ── */}
          <div className="bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden">
            <div className="px-6 py-5">
              <div className="flex flex-wrap items-start justify-between gap-2 border-b pb-3">
                <div>
                  <div className="text-base font-bold">{schoolName}</div>
                  <div className="text-sm font-semibold mt-0.5">Cashier Daily Collection Summary</div>
                  <div className="text-xs text-muted-foreground mt-0.5">
                    {fmtDate(date)} · {collector === "all" ? "All cashiers" : collector} · Shift: Full Day
                  </div>
                </div>
                <div className="text-right">
                  <div className="text-2xl font-bold tabular-nums text-primary">{fmtCedi(summary.totals.totalCents)}</div>
                  <div className="text-[11px] text-muted-foreground">total collected · cash {fmtCedi(summary.totals.cashCents)}</div>
                </div>
              </div>

              <div className="grid sm:grid-cols-3 gap-3 mt-4">
                <div className="rounded-lg bg-secondary/50 p-3">
                  <div className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">Transactions</div>
                  <div className="text-xl font-bold tabular-nums">{summary.totals.transactions}</div>
                  <div className="text-[11px] text-muted-foreground">receipts issued</div>
                </div>
                <div className="rounded-lg bg-secondary/50 p-3">
                  <div className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">Learners served</div>
                  <div className="text-xl font-bold tabular-nums">{summary.totals.distinctStudents}</div>
                  <div className="text-[11px] text-muted-foreground">unique at the counter</div>
                </div>
                <div className="rounded-lg bg-secondary/50 p-3">
                  <div className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">Daily-fee arrears (all-time)</div>
                  <div className="text-xl font-bold tabular-nums text-destructive">{fmtCedi(summary.dailyArrearsCents, { compact: true })}</div>
                  <div className="text-[11px] text-muted-foreground">still owed on day charges</div>
                </div>
              </div>

              <div className="grid sm:grid-cols-2 gap-4 mt-4">
                <div>
                  <div className="text-[10px] uppercase tracking-wider text-muted-foreground font-semibold border-b border-dashed pb-1 mb-2">Collections by fee type</div>
                  {categories.length === 0 && <div className="text-xs text-muted-foreground">Nothing collected on this day.</div>}
                  {categories.map(([k, v]) => (
                    <div key={k} className="flex justify-between text-sm py-0.5">
                      <span>{CATEGORY_LABELS[k] ?? k}</span>
                      <span className="tabular-nums font-medium">{fmtCedi(v)}</span>
                    </div>
                  ))}
                  {categories.length > 0 && (
                    <div className="flex justify-between text-sm py-1 border-t mt-1">
                      <span className="font-semibold">Total</span>
                      <span className="tabular-nums font-bold text-primary">{fmtCedi(summary.totals.totalCents)}</span>
                    </div>
                  )}
                </div>
                <div>
                  <div className="text-[10px] uppercase tracking-wider text-muted-foreground font-semibold border-b border-dashed pb-1 mb-2">Payment methods</div>
                  {methods.length === 0 && <div className="text-xs text-muted-foreground">No payments recorded.</div>}
                  {methods.map(([m, v]) => (
                    <div key={m} className="flex justify-between text-sm py-0.5">
                      <span>{PAYMENT_LABELS[m] ?? m}</span>
                      <span className="tabular-nums font-medium">{fmtCedi(v)}</span>
                    </div>
                  ))}
                </div>
              </div>

              {summary.topups.length > 0 && (
                <div className="mt-4">
                  <div className="text-[10px] uppercase tracking-wider text-muted-foreground font-semibold border-b border-dashed pb-1 mb-2">Wallet top-ups today</div>
                  <div className="text-xs space-y-1">
                    {summary.topups.map((t) => (
                      <div key={t.id} className="flex justify-between">
                        <span>{t.student} · {t.wallet === "TRANSPORT" ? "Transport wallet" : "Prepaid wallet"}{t.byName ? ` · by ${t.byName}` : ""}</span>
                        <span className="tabular-nums font-medium">{fmtCedi(t.amountCents)}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              <div className="mt-4 text-[11px] text-muted-foreground">Generated {new Date().toLocaleString("en-GB")} · {summary.totals.receipts} receipts on the day.</div>
            </div>
          </div>

          {/* ── printable handover report ── */}
          <div className="bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden">
            <div className="px-6 py-5">
              <div className="flex flex-wrap items-start justify-between gap-2 border-b pb-3">
                <div>
                  <div className="text-base font-bold">{schoolName}</div>
                  <div className="text-sm font-semibold mt-0.5">Cash Handover Report</div>
                  <div className="text-xs text-muted-foreground mt-0.5">{fmtDate(date)} · Shift: Full Day</div>
                </div>
                <Badge variant="outline" className="gap-1"><ArrowLeftRight className="size-3" /> float handover</Badge>
              </div>

              <div className="grid sm:grid-cols-2 gap-4 mt-4">
                <div className="space-y-1">
                  <div className="text-[10px] uppercase tracking-wider text-muted-foreground font-semibold border-b border-dashed pb-1 mb-2">Collections summary</div>
                  <div className="flex justify-between text-sm py-0.5"><span>Total collections</span><span className="tabular-nums font-semibold">{fmtCedi(summary.totals.totalCents)}</span></div>
                  <div className="flex justify-between text-sm py-0.5"><span>Cash to hand over</span><span className="tabular-nums font-semibold text-primary">{fmtCedi(summary.totals.cashCents)}</span></div>
                  <div className="flex justify-between text-sm py-0.5"><span>Electronic (MoMo / bank / card)</span><span className="tabular-nums">{fmtCedi(summary.totals.totalCents - summary.totals.cashCents)}</span></div>
                  <div className="flex justify-between text-sm py-0.5"><span>Receipts issued</span><span className="tabular-nums">{summary.totals.receipts}</span></div>
                  <div className="flex justify-between text-sm py-0.5"><span>Learners served</span><span className="tabular-nums">{summary.totals.distinctStudents}</span></div>
                </div>
                <div className="space-y-1">
                  <div className="text-[10px] uppercase tracking-wider text-muted-foreground font-semibold border-b border-dashed pb-1 mb-2">Method mix</div>
                  {methods.length === 0 && <div className="text-xs text-muted-foreground">No payments recorded.</div>}
                  {methods.map(([m, v]) => (
                    <div key={m} className="flex justify-between text-sm py-0.5">
                      <span>{PAYMENT_LABELS[m] ?? m}</span>
                      <span className="tabular-nums">{fmtCedi(v)}</span>
                    </div>
                  ))}
                </div>
              </div>

              {/* signature area */}
              <div className="grid grid-cols-2 gap-4 mt-6">
                <div className="border-2 border-dashed rounded-lg p-3 text-center min-h-28">
                  <div className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">Handed Over By</div>
                  <div className="text-sm font-semibold mt-1">{cashier?.fullName ?? "—"}</div>
                  <div className="text-[10px] text-muted-foreground">{cashier?.role === "CASHIER" ? "Cashier" : cashier ? "Collections" : ""}</div>
                  <div className="text-[10px] text-muted-foreground mt-6 border-t pt-1">Signature · Date: {fmtDate(date)}</div>
                </div>
                <div className="border-2 border-dashed rounded-lg p-3 text-center min-h-28">
                  <div className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">Received By</div>
                  <div className="text-sm font-semibold mt-1">{receiver?.fullName ?? "—"}</div>
                  <div className="text-[10px] text-muted-foreground">{receiver ? (receiver.role === "ACCOUNTANT" ? "Bursar / Accountant" : receiver.role === "SCHOOL_ADMIN" ? "Administrator" : "Finance") : ""}</div>
                  <div className="text-[10px] text-muted-foreground mt-6 border-t pt-1">Signature · Date: {fmtDate(date)}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* cashier / receiver pickers + saved handovers (screen only) */}
      {summary && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 no-print">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><ClipboardCheck className="size-4 text-primary" /> Handover parties</CardTitle></CardHeader>
            <CardContent className="space-y-3">
              <div className="space-y-1.5">
                <Label className="text-xs">Handed over by (cashier)</Label>
                <Select value={cashierId} onValueChange={setCashierId}>
                  <SelectTrigger className="h-9"><SelectValue placeholder="Choose cashier" /></SelectTrigger>
                  <SelectContent>
                    {summary.users.map((u) => <SelectItem key={u.id} value={u.id}>{u.fullName} — {u.role === "CASHIER" ? "Cashier" : u.role === "ACCOUNTANT" ? "Bursar" : u.role === "SCHOOL_ADMIN" ? "Admin" : u.role}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5">
                <Label className="text-xs">Received by</Label>
                <Select value={receiverId} onValueChange={setReceiverId}>
                  <SelectTrigger className="h-9"><SelectValue placeholder="Choose receiver" /></SelectTrigger>
                  <SelectContent>
                    {summary.users.filter((u) => u.id !== cashierId).map((u) => <SelectItem key={u.id} value={u.id}>{u.fullName} — {u.role === "ACCOUNTANT" ? "Bursar" : u.role === "SCHOOL_ADMIN" ? "Admin" : u.role}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
              <p className="text-xs text-muted-foreground">Saving records one handover per cashier per day — re-saving replaces it. The printed signature boxes above double as the paper record.</p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Save className="size-4 text-primary" /> Saved handovers — {fmtDate(date)}</CardTitle></CardHeader>
            <CardContent className="space-y-1.5">
              {summary.handovers.length === 0 && <EmptyState title="No handover saved for this date" hint="Pick the parties and press Save handover." />}
              {summary.handovers.map((h) => (
                <div key={h.id} className="rounded-lg border px-3 py-2 flex flex-wrap items-center justify-between gap-2">
                  <div className="text-sm">
                    <b>{h.cashierName}</b> → {h.receiverName}
                    <div className="text-[11px] text-muted-foreground">saved {new Date(h.createdAt).toLocaleString("en-GB")}</div>
                  </div>
                  <div className="text-right">
                    <div className="font-semibold tabular-nums">{fmtCedi(h.totalCents)}</div>
                    <div className="text-[11px] text-muted-foreground">cash {fmtCedi(h.cashCents)}</div>
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
}

function Tile({ icon, label, value, sub, tone }: { icon: React.ReactNode; label: string; value: React.ReactNode; sub?: string; tone?: "gold" }) {
  return (
    <div className="rounded-xl border bg-card p-4">
      <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</span>{icon}</div>
      <div className={`mt-1.5 text-xl font-bold tabular-nums ${tone === "gold" ? "text-primary" : ""}`}>{value}</div>
      <div className="text-[11px] text-muted-foreground truncate">{sub}</div>
    </div>
  );
}

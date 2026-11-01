"use client";
import { useCallback, useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, PAYMENT_LABELS } from "@/lib/format";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "@/hooks/use-toast";
import { Receipt, Banknote, TrendingUp, Users, UtensilsCrossed, Banknote as CashIcon, Smartphone, RefreshCcw, Wallet, Percent, Printer, Save, ClipboardCheck, CalendarDays, ArrowLeftRight } from "lucide-react";

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
};

const todayStr = () => new Date().toISOString().slice(0, 10);
const METHOD_ICON: Record<string, React.ReactNode> = {
  CASH: <CashIcon className="size-3.5" />, MOMO_MTN: <Smartphone className="size-3.5" />, MOMO_TELECEL: <Smartphone className="size-3.5" />,
  MOMO_AT: <Smartphone className="size-3.5" />, BANK: <Banknote className="size-3.5" />, CHEQUE: <Banknote className="size-3.5" />, CARD: <Banknote className="size-3.5" />,
};

export function CashierConsole() {
  const { currentSchoolId, setModule, navTarget } = useStore();
  const [from, setFrom] = useState(todayStr());
  const [to, setTo] = useState(todayStr());
  const [collector, setCollector] = useState("all");
  const [data, setData] = useState<CashierData | null>(null);
  const [loading, setLoading] = useState(true);
  const [tab, setTab] = useState("counter");

  /* iEduPro nested nav: deep-link to the summary & handover tab */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab && ["counter", "summary"].includes(navTarget.tab)) setTab(navTarget.tab);
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
        description="Your collection counter — daily takings, fee-type breakdown, performance per cashier, printable daily summary and signed float handover."
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
        <TabsList>
          <TabsTrigger value="counter">Today&apos;s counter</TabsTrigger>
          <TabsTrigger value="summary">Summary &amp; handover</TabsTrigger>
        </TabsList>

        <TabsContent value="counter">
      {loading && !data ? <LoadingBlock label="Counting the drawer…" /> : data ? (
        <>
          {/* headline numbers */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <Tile icon={<Wallet className="size-4" />} label="Total collected" value={fmtCedi(data.totals.collected, { compact: true })} sub={`${fmtDate(data.period.from)} → ${fmtDate(data.period.to)}`} tone="gold" />
            <Tile icon={<Receipt className="size-4" />} label="Transactions" value={data.totals.transactions} sub="receipts issued" />
            <Tile icon={<Users className="size-4" />} label="Learners served" value={data.totals.studentsServed} sub="unique at the counter" />
            <Tile icon={<TrendingUp className="size-4" />} label="Invoices vs daily" value={`${fmtCedi(data.split.invoiceCollected, { compact: true })} / ${fmtCedi(data.split.dailyCollected + data.split.transportCollected, { compact: true })}`} sub="term bills vs daily & transport" />
          </div>

          <div className="grid lg:grid-cols-2 gap-4 mb-4">
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

          <div className="grid lg:grid-cols-2 gap-4">
            {/* collection performance */}
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base">Collection performance</CardTitle></CardHeader>
              <CardContent>
                {data.performance.length ? (
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Collector</TableHead>
                        <TableHead>Total collected</TableHead>
                        <TableHead className="hidden sm:table-cell">Transactions</TableHead>
                        <TableHead>Share</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {data.performance.map((p) => (
                        <TableRow key={p.name} className={p.name === "Unattributed" ? "opacity-60" : ""}>
                          <TableCell className="font-medium">{p.name}</TableCell>
                          <TableCell className="tabular-nums">{fmtCedi(p.total)}</TableCell>
                          <TableCell className="hidden sm:table-cell tabular-nums">{p.count}</TableCell>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <div className="h-1.5 w-16 rounded-full bg-secondary overflow-hidden"><div className="h-full bg-primary" style={{ width: `${p.share}%` }} /></div>
                              <span className="text-xs tabular-nums text-muted-foreground">{p.share}%</span>
                            </div>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                ) : <EmptyState title="No collections in this window" />}
              </CardContent>
            </Card>

            {/* recent receipts */}
            <Card>
              <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                  <CardTitle className="text-base">Recent receipts</CardTitle>
                  <div className="flex gap-1.5">
                    <Button size="sm" variant="outline" className="h-7 text-xs" onClick={() => setModule("fees")}>Fees counter</Button>
                    <Button size="sm" variant="outline" className="h-7 text-xs" onClick={() => setModule("daily")}>Daily services</Button>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                {data.recent.length ? (
                  <div className="space-y-1.5 max-h-80 overflow-y-auto nice-scroll">
                    {data.recent.map((r) => (
                      <div key={r.id} className="flex items-center justify-between rounded-lg border px-3 py-2">
                        <div className="min-w-0">
                          <div className="text-sm font-medium truncate">{r.student}</div>
                          <div className="text-xs text-muted-foreground flex items-center gap-1.5">
                            {METHOD_ICON[r.method] ?? <Banknote className="size-3" />} {PAYMENT_LABELS[r.method] ?? r.method} · {fmtDate(r.paidAt)}
                            {r.category && r.category !== "INVOICE" && <Badge variant="outline" className="text-[9px] px-1">{r.category.toLowerCase()}</Badge>}
                          </div>
                        </div>
                        <div className="text-right shrink-0">
                          <div className="text-sm font-semibold tabular-nums">{fmtCedi(r.amount)}</div>
                          <div className="text-[10px] font-mono text-muted-foreground">{r.receiptNo}</div>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : <EmptyState title="No receipts in this window" hint="Take a payment at the fees counter or daily services to see it here." />}
              </CardContent>
            </Card>
          </div>
        </>
      ) : null}
        </TabsContent>

        <TabsContent value="summary">
          <SummaryHandoverTab />
        </TabsContent>
      </Tabs>
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
        <div className="grid lg:grid-cols-2 gap-4 no-print">
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

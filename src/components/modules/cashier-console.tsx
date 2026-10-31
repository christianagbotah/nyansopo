"use client";
import { useCallback, useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet } from "@/lib/api";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, PAYMENT_LABELS } from "@/lib/format";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { toast } from "@/hooks/use-toast";
import { Receipt, Banknote, TrendingUp, Users, UtensilsCrossed, Banknote as CashIcon, Smartphone, RefreshCcw, Wallet, Percent } from "lucide-react";

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
  const { currentSchoolId, setModule } = useStore();
  const [from, setFrom] = useState(todayStr());
  const [to, setTo] = useState(todayStr());
  const [collector, setCollector] = useState("all");
  const [data, setData] = useState<CashierData | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<CashierData>(`/api/school/${currentSchoolId}/ops/cashier?from=${from}&to=${to}&collector=${encodeURIComponent(collector)}`)
      .then(setData)
      .catch((e) => toast({ title: "Could not load collections", description: e.message, variant: "destructive" }))
      .finally(() => setLoading(false));
  }, [currentSchoolId, from, to, collector]);
  useEffect(() => { load(); }, [load]); // eslint-disable-line react-hooks/set-state-in-effect

  return (
    <div>
      <ModuleHeader
        title="Cashier Console"
        description="Your collection counter — daily takings, fee-type breakdown and performance per cashier."
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

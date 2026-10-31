"use client";
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost, apiPostQueueable } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { StatCard, ModuleHeader, LoadingBlock, StatusBadge, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, PAYMENT_LABELS } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { Banknote, Receipt, AlertTriangle, CheckCircle2, Plus, Flag, Printer, Wallet } from "lucide-react";

type FeeData = {
  terms: { id: string; name: string; isCurrent: boolean }[];
  termId: string;
  structures: { id: string; classroom: string; classroomId: string; total: number; items: { name: string; amount: number; optional: boolean }[] }[];
  invoices: {
    id: string; invoiceNo: string; student: string; studentId: string; classroom: string | null; total: number; discount: number;
    discountReason: string | null; paid: number; balance: number; status: string; dueDate: string | null; guardianPhone: string;
    payments: { id: string; receiptNo: string; amount: number; method: string; paidAt: string; reference: string | null }[];
  }[];
  stats: { billed: number; collected: number; arrears: number; count: number; paid: number; partial: number; unpaid: number };
};

export function Fees() {
  const { currentSchoolId, online } = useStore();
  const [data, setData] = useState<FeeData | null>(null);
  const [termId, setTermId] = useState<string | null>(null);
  const [tab, setTab] = useState("invoices");
  const [payFor, setPayFor] = useState<FeeData["invoices"][0] | null>(null);
  const [flagFor, setFlagFor] = useState<FeeData["invoices"][0] | null>(null);
  const [receipt, setReceipt] = useState<any | null>(null);

  const load = (t?: string) => {
    if (!currentSchoolId) return;
    apiGet<FeeData>(`/api/school/${currentSchoolId}/fees${t ? `?termId=${t}` : ""}`)
      .then((d) => { setData(d); setTermId(d.termId); })
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(() => { load(); }, [currentSchoolId]);

  const overdue = useMemo(() => (data?.invoices ?? []).filter((i) => i.status === "OVERDUE" || (i.status === "UNPAID" && i.balance > 0)).sort((a, b) => b.balance - a.balance), [data]);

  if (!data) return <LoadingBlock label="Loading billing…" />;

  return (
    <div>
      <ModuleHeader
        title="Fees & Billing"
        description="Term-based billing in Ghana Cedis — tuition, exam, books, ICT, meals & transport. Record MoMo, cash and bank payments; chase arrears; flag defaulters to the network."
        actions={
          <Select value={termId ?? undefined} onValueChange={(v) => { setTermId(v); load(v); }}>
            <SelectTrigger className="w-56"><SelectValue /></SelectTrigger>
            <SelectContent>{data.terms.map((t) => <SelectItem key={t.id} value={t.id}>{t.name}{t.isCurrent ? " (current)" : ""}</SelectItem>)}</SelectContent>
          </Select>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<Banknote className="size-4" />} label="Billed" value={fmtCedi(data.stats.billed, { compact: true })} sub={`${data.stats.count} invoices`} />
        <StatCard icon={<CheckCircle2 className="size-4" />} label="Collected" value={fmtCedi(data.stats.collected, { compact: true })} tone="primary" sub={`${data.stats.paid} fully paid`} />
        <StatCard icon={<AlertTriangle className="size-4" />} label="Arrears" value={fmtCedi(data.stats.arrears, { compact: true })} tone="danger" sub={`${data.stats.unpaid + data.stats.partial} outstanding`} />
        <StatCard icon={<Receipt className="size-4" />} label="Collection rate" value={`${data.stats.billed ? Math.round((data.stats.collected / data.stats.billed) * 100) : 0}%`} tone="gold" sub={`${data.stats.partial} on payment plans`} />
      </div>

      <Tabs value={tab} onValueChange={setTab} className="mt-5">
        <TabsList>
          <TabsTrigger value="invoices">Invoices</TabsTrigger>
          <TabsTrigger value="structures">Fee structures</TabsTrigger>
          <TabsTrigger value="arrears">Arrears chase-list ({overdue.length})</TabsTrigger>
        </TabsList>

        <TabsContent value="invoices">
          <Card>
            <CardContent className="p-0">
              <div className="overflow-x-auto nice-scroll max-h-[560px]">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                    <tr className="text-left border-b">
                      <th className="px-3 py-2.5 font-medium">Invoice</th>
                      <th className="px-3 py-2.5 font-medium">Learner</th>
                      <th className="px-3 py-2.5 font-medium hidden md:table-cell">Class</th>
                      <th className="px-3 py-2.5 font-medium text-right">Total</th>
                      <th className="px-3 py-2.5 font-medium text-right hidden sm:table-cell">Paid</th>
                      <th className="px-3 py-2.5 font-medium text-right">Balance</th>
                      <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Status</th>
                      <th className="px-3 py-2.5"></th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.invoices.map((inv) => (
                      <tr key={inv.id} className="border-b last:border-0 hover:bg-secondary/30">
                        <td className="px-3 py-2.5 font-mono text-xs">{inv.invoiceNo}</td>
                        <td className="px-3 py-2.5">
                          <div className="font-medium">{inv.student}</div>
                          <div className="text-[11px] text-muted-foreground lg:hidden"><StatusBadge status={inv.status} /></div>
                        </td>
                        <td className="px-3 py-2.5 hidden md:table-cell text-muted-foreground">{inv.classroom}</td>
                        <td className="px-3 py-2.5 text-right tabular-nums">{fmtCedi(inv.total)}{inv.discount > 0 && <div className="text-[10px] text-emerald-600">−{fmtCedi(inv.discount)} {inv.discountReason ?? ""}</div>}</td>
                        <td className="px-3 py-2.5 text-right tabular-nums text-emerald-600 hidden sm:table-cell">{fmtCedi(inv.paid)}</td>
                        <td className="px-3 py-2.5 text-right tabular-nums font-semibold">{inv.balance > 0 ? <span className="text-destructive">{fmtCedi(inv.balance)}</span> : "—"}</td>
                        <td className="px-3 py-2.5 hidden lg:table-cell"><StatusBadge status={inv.status} /></td>
                        <td className="px-3 py-2.5 text-right">
                          <Button size="sm" variant="outline" className="h-7 gap-1" disabled={inv.balance <= 0} onClick={() => setPayFor(inv)}>
                            <Wallet className="size-3.5" /> Pay
                          </Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="structures">
          <div className="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            {data.structures.map((s) => (
              <Card key={s.id}>
                <CardHeader className="pb-2 flex-row items-center justify-between space-y-0">
                  <CardTitle className="text-sm">{s.classroom}</CardTitle>
                  <Badge variant="secondary">{fmtCedi(s.total)}</Badge>
                </CardHeader>
                <CardContent className="space-y-1.5">
                  {s.items.map((it) => (
                    <div key={it.name} className="flex justify-between text-sm">
                      <span className={it.optional ? "text-muted-foreground" : ""}>{it.name}{it.optional ? " (opt.)" : ""}</span>
                      <span className="tabular-nums">{fmtCedi(it.amount)}</span>
                    </div>
                  ))}
                  <div className="flex justify-between text-sm font-semibold border-t pt-1.5">
                    <span>Core total</span><span className="tabular-nums">{fmtCedi(s.total)}</span>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </TabsContent>

        <TabsContent value="arrears">
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-base flex items-center gap-2"><Flag className="size-4 text-destructive" /> Arrears chase-list</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2 max-h-[520px] overflow-y-auto nice-scroll">
              {overdue.length === 0 && <EmptyState icon={<CheckCircle2 className="size-5" />} title="No arrears — everything collected!" />}
              {overdue.slice(0, 40).map((inv) => (
                <div key={inv.id} className="rounded-lg border p-3 flex flex-wrap items-center gap-3 justify-between">
                  <div>
                    <div className="font-medium text-sm">{inv.student} <span className="text-muted-foreground font-normal">· {inv.classroom}</span></div>
                    <div className="text-xs text-muted-foreground">{inv.guardianPhone} · due {fmtDate(inv.dueDate)}</div>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="font-semibold text-destructive tabular-nums">{fmtCedi(inv.balance)}</span>
                    <StatusBadge status={inv.status} />
                    <Button size="sm" variant="outline" className="h-7 gap-1" onClick={() => setPayFor(inv)}><Wallet className="size-3.5" /> Receive</Button>
                    <Button size="sm" variant="ghost" className="h-7 gap-1 text-destructive" onClick={() => setFlagFor(inv)}><Flag className="size-3.5" /> Flag</Button>
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      <PayDialog
        invoice={payFor}
        onOpenChange={(o) => !o && setPayFor(null)}
        onDone={(rc) => { setReceipt(rc); setPayFor(null); load(termId ?? undefined); }}
      />
      <FlagDialog
        invoice={flagFor}
        onOpenChange={(o) => !o && setFlagFor(null)}
        onDone={() => { setFlagFor(null); load(termId ?? undefined); }}
      />
      <ReceiptDialog receipt={receipt} onOpenChange={(o) => !o && setReceipt(null)} />
    </div>
  );
}

function PayDialog({ invoice, onOpenChange, onDone }: { invoice: any; onOpenChange: (o: boolean) => void; onDone: (receipt: any) => void }) {
  const { currentSchoolId, online } = useStore();
  const [amount, setAmount] = useState("");
  const [method, setMethod] = useState("MOMO_MTN");
  const [reference, setReference] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => { if (invoice) setAmount((invoice.balance / 100).toFixed(2)); }, [invoice]);

  async function submit() {
    if (!invoice || !currentSchoolId) return;
    const cents = Math.round(parseFloat(amount || "0") * 100);
    if (!cents || cents <= 0) { toast({ title: "Enter a valid amount" }); return; }
    setBusy(true);
    try {
      const payload = { action: "record-payment", invoiceId: invoice.id, amountCents: cents, method, reference: reference || undefined };
      const res = await apiPostQueueable<any>(
        `/api/school/${currentSchoolId}/fees`, "payment", payload,
        `Payment ${fmtCedi(cents)} — ${invoice.student}`,
        (p, clientId) => ({ ...p, schoolId: currentSchoolId, amountCents: cents, method, reference, paidAt: new Date().toISOString(), invoiceId: invoice.id, payloadClientId: clientId, clientId })
      );
      if (res.queued) {
        toast({ title: "Queued offline", description: "Receipt will be issued automatically when you reconnect." });
        onDone(null);
      } else {
        onDone(res.data?.payment ?? null);
      }
    } catch (e: any) {
      toast({ title: "Payment failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!invoice} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Receive payment — {invoice?.student}</DialogTitle>
          <DialogDescription>
            {invoice?.invoiceNo} · balance <b className="text-destructive">{invoice ? fmtCedi(invoice.balance) : ""}</b>
            {!online && " · offline, will queue & sync"}
          </DialogDescription>
        </DialogHeader>
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1.5">
            <Label>Amount (GH₵) *</Label>
            <Input type="number" step="0.01" value={amount} onChange={(e) => setAmount(e.target.value)} />
          </div>
          <div className="space-y-1.5">
            <Label>Method</Label>
            <Select value={method} onValueChange={setMethod}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                {Object.entries(PAYMENT_LABELS).map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5 col-span-2">
            <Label>MoMo / transaction reference</Label>
            <Input placeholder="e.g. MP2401.5533.77 GCASH" value={reference} onChange={(e) => setReference(e.target.value)} />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy} className="gap-1.5">{busy ? "Recording…" : <><Receipt className="size-4" /> Record payment</>}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function FlagDialog({ invoice, onOpenChange, onDone }: { invoice: any; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [description, setDescription] = useState("");
  const [severity, setSeverity] = useState("HIGH");
  const [busy, setBusy] = useState(false);

  useEffect(() => { if (invoice) setDescription(`Outstanding balance of ${fmtCedi(invoice.balance)} for the current term despite reminders. Guardian: ${invoice.guardianPhone}.`); }, [invoice]);

  async function submit() {
    if (!invoice || !currentSchoolId) return;
    setBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/fees`, { action: "flag-defaulter", studentId: invoice.studentId, amountCents: invoice.balance, description, severity, type: "FEE_ARREARS" });
      toast({ title: "Defaulter flagged", description: "The flag is now live on the association registry — any school this learner applies to will see it." });
      onDone();
    } catch (e: any) {
      toast({ title: "Failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!invoice} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Flag className="size-4 text-destructive" /> Flag defaulter — {invoice?.student}</DialogTitle>
          <DialogDescription>Balance {invoice ? fmtCedi(invoice.balance) : ""}. Flags are visible across the network and to association officers.</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="space-y-1.5">
            <Label>Severity</Label>
            <Select value={severity} onValueChange={setSeverity}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="LOW">Low</SelectItem><SelectItem value="MEDIUM">Medium</SelectItem><SelectItem value="HIGH">High</SelectItem><SelectItem value="CRITICAL">Critical</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Description</Label>
            <Input value={description} onChange={(e) => setDescription(e.target.value)} />
          </div>
          <p className="text-xs text-muted-foreground">Best practice: record reminders sent and any promises made, so association verification is smooth.</p>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button variant="destructive" onClick={submit} disabled={busy}>{busy ? "Flagging…" : "Raise flag"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function ReceiptDialog({ receipt, onOpenChange }: { receipt: any; onOpenChange: (o: boolean) => void }) {
  if (!receipt) return null;
  return (
    <Dialog open={!!receipt} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-sm">
        <DialogHeader className="text-center">
          <DialogTitle className="flex items-center justify-center gap-2"><CheckCircle2 className="size-5 text-emerald-600" /> Payment received</DialogTitle>
        </DialogHeader>
        <div className="text-center space-y-1 py-2">
          <div className="text-3xl font-bold text-primary">{fmtCedi(receipt.amountCents ?? 0)}</div>
          <div className="text-sm">{receipt.student}</div>
          <div className="font-mono text-xs text-muted-foreground">{receipt.receiptNo}</div>
          <div className="text-xs text-muted-foreground">{PAYMENT_LABELS[receipt.method] ?? receipt.method} · {fmtDate(receipt.paidAt)}</div>
        </div>
        <DialogFooter className="sm:justify-center gap-2">
          <Button variant="outline" size="sm" onClick={() => window.print()}><Printer className="size-4" /> Print receipt</Button>
          <Button size="sm" onClick={() => onOpenChange(false)}>Done</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

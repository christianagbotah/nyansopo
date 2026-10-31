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
import { Banknote, Receipt, AlertTriangle, CheckCircle2, Plus, Flag, Printer, Wallet, Ticket, Percent, UserPlus, XCircle, ListChecks, Copy as CopyIcon, Eye, Link2 } from "lucide-react";
import { ReceiptDocument, BulkReceiptsDocument, RECEIPT_STYLES, type ReceiptStyle } from "@/components/receipts";
import { Checkbox } from "@/components/ui/checkbox";

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

type DiscountRow = { id: string; name: string; type: string; value: number; scope: string; autoApply: boolean; active: boolean; notes: string | null; applied: number };
type ApplicationRow = { id: string; discountId: string; discountName: string; type: string; value: number; scope: string; serviceId: string | null; student: string; studentId: string; guardianPhone: string; status: string; reason: string | null; appliedByName: string; createdAt: string };
type DiscountData = { discounts: DiscountRow[]; applications: ApplicationRow[]; services: { id: string; name: string; price: number }[] };

export function Fees() {
  const { currentSchoolId, online } = useStore();
  const [data, setData] = useState<FeeData | null>(null);
  const [discounts, setDiscounts] = useState<DiscountData | null>(null);
  const [termId, setTermId] = useState<string | null>(null);
  const [tab, setTab] = useState("invoices");
  const [payFor, setPayFor] = useState<FeeData["invoices"][0] | null>(null);
  const [flagFor, setFlagFor] = useState<FeeData["invoices"][0] | null>(null);
  const [discFor, setDiscFor] = useState<FeeData["invoices"][0] | null>(null);
  const [discDialog, setDiscDialog] = useState<DiscountRow | "new" | null>(null);
  const [applyDialog, setApplyDialog] = useState<DiscountRow | null>(null);
  const [receipt, setReceipt] = useState<any | null>(null);
  const [receiptStyle, setReceiptStyle] = useState<"A5" | "THERMAL">("A5");
  const [checkedReceipts, setCheckedReceipts] = useState<Set<string>>(new Set());
  const [bulkReceipts, setBulkReceipts] = useState<any[] | null>(null);
  const [bulkBusy, setBulkBusy] = useState(false);

  /* reprint machinery — full receipt payloads come back from the API */
  async function fetchPayloads(ids: string[]): Promise<any[]> {
    const res = await apiPost<any>(`/api/school/${currentSchoolId}/fees`, { action: "receipt-payloads", paymentIds: ids });
    return res.receipts ?? [];
  }
  async function viewReceipt(p: { id: string; receiptNo: string }) {
    try {
      const [payload] = await fetchPayloads([p.id]);
      if (payload) setReceipt(payload);
      else toast({ title: "Receipt unavailable", description: `${p.receiptNo} could not be reconstructed — it may reference archived data.`, variant: "destructive" });
    } catch (e: any) { toast({ title: "Load failed", description: e.message, variant: "destructive" }); }
  }
  async function reprintSelected() {
    if (!checkedReceipts.size || !currentSchoolId) return;
    setBulkBusy(true);
    try {
      const receipts = await fetchPayloads([...checkedReceipts]);
      if (receipts.length) setBulkReceipts(receipts);
      else toast({ title: "No printable receipts", description: "None of the selected receipts could be reconstructed.", variant: "destructive" });
    } catch (e: any) { toast({ title: "Bulk print failed", description: e.message, variant: "destructive" }); }
    finally { setBulkBusy(false); }
  }

  /* public statement link for the guardian (no login needed) */
  function copyParentLink(inv: { id: string; invoiceNo: string; student: string }) {
    const url = `${location.origin}/invoice/${inv.id}`;
    navigator.clipboard?.writeText(url).then(
      () => toast({ title: "Parent link copied", description: `Shareable statement for ${inv.student} (${inv.invoiceNo}) — open it on any phone, no login needed.` }),
      () => toast({ title: "Copy this link", description: url }),
    );
  }

  const load = (t?: string) => {
    if (!currentSchoolId) return;
    apiGet<FeeData>(`/api/school/${currentSchoolId}/fees${t ? `?termId=${t}` : ""}`)
      .then((d) => { setData(d); setTermId(d.termId); })
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
    apiGet<DiscountData>(`/api/school/${currentSchoolId}/ops/discounts`).then(setDiscounts).catch(() => null);
  };
  useEffect(() => { load(); }, [currentSchoolId]);

  const overdue = useMemo(() => (data?.invoices ?? []).filter((i) => i.status === "OVERDUE" || (i.status === "UNPAID" && i.balance > 0)).sort((a, b) => b.balance - a.balance), [data]);
  const allPayments = useMemo(() =>
    (data?.invoices ?? []).flatMap((i) => i.payments.map((p) => ({ ...p, student: i.student, classroom: i.classroom, invoiceNo: i.invoiceNo }))).sort((a, b) => new Date(b.paidAt).getTime() - new Date(a.paidAt).getTime()),
  [data]);

  if (!data) return <LoadingBlock label="Loading billing…" />;

  return (
    <div>
      <ModuleHeader
        title="Fees & Billing"
        description="Term-based billing in Ghana Cedis — tuition, exam, books, ICT, meals & transport. Record MoMo, cash and bank payments; chase arrears; flag defaulters to the network."
        actions={
          <Select value={termId ?? ""} onValueChange={(v) => { setTermId(v); load(v); }}>
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
          <TabsTrigger value="receipts">Receipts ({allPayments.length})</TabsTrigger>
          <TabsTrigger value="structures">Fee structures</TabsTrigger>
          <TabsTrigger value="discounts">Discounts ({discounts?.discounts.length ?? 0})</TabsTrigger>
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
                          <div className="flex gap-1 justify-end">
                            <Button size="sm" variant="ghost" className="h-7 gap-1" title="Apply discount" onClick={() => setDiscFor(inv)}><Percent className="size-3.5" /></Button>
                            <Button size="sm" variant="ghost" className="h-7 gap-1" title="Copy shareable parent statement link" onClick={() => copyParentLink(inv)}><Link2 className="size-3.5" /></Button>
                            <Button size="sm" variant="outline" className="h-7 gap-1" disabled={inv.balance <= 0} onClick={() => setPayFor(inv)}>
                              <Wallet className="size-3.5" /> Pay
                            </Button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="receipts">
          <Card>
            <CardHeader className="pb-3 flex-row items-center justify-between space-y-0 flex-wrap gap-2">
              <CardTitle className="text-base flex items-center gap-2"><Receipt className="size-4" /> Issued receipts — {allPayments.length} this term</CardTitle>
              <div className="flex items-center gap-2">
                <Checkbox
                  checked={allPayments.length > 0 && checkedReceipts.size === allPayments.length}
                  onCheckedChange={(v) => setCheckedReceipts(v ? new Set(allPayments.map((p) => p.id)) : new Set())}
                  aria-label="Select all receipts"
                />
                <span className="text-xs text-muted-foreground select-all-label">Select all</span>
                <Button size="sm" className="gap-1.5" disabled={!checkedReceipts.size || bulkBusy} onClick={reprintSelected}>
                  <Printer className={cn("size-4", bulkBusy && "animate-spin")} /> Reprint {checkedReceipts.size || ""} selected
                </Button>
              </div>
            </CardHeader>
            <CardContent className="p-0">
              <div className="overflow-x-auto nice-scroll max-h-[560px]">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                    <tr className="text-left border-b">
                      <th className="px-3 py-2.5 w-8"></th>
                      <th className="px-3 py-2.5 font-medium">Receipt</th>
                      <th className="px-3 py-2.5 font-medium">Learner</th>
                      <th className="px-3 py-2.5 font-medium hidden md:table-cell">Class</th>
                      <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Invoice</th>
                      <th className="px-3 py-2.5 font-medium text-right">Amount</th>
                      <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Method</th>
                      <th className="px-3 py-2.5 font-medium hidden xl:table-cell">Date</th>
                      <th className="px-3 py-2.5"></th>
                    </tr>
                  </thead>
                  <tbody>
                    {allPayments.slice(0, 150).map((p) => (
                      <tr key={p.id} className="border-b last:border-0 hover:bg-secondary/30">
                        <td className="px-3 py-2.5">
                          <Checkbox
                            checked={checkedReceipts.has(p.id)}
                            onCheckedChange={(v) => { const next = new Set(checkedReceipts); v ? next.add(p.id) : next.delete(p.id); setCheckedReceipts(next); }}
                            aria-label={`Select receipt ${p.receiptNo}`}
                          />
                        </td>
                        <td className="px-3 py-2.5 font-mono text-xs">{p.receiptNo}</td>
                        <td className="px-3 py-2.5 font-medium">{p.student}</td>
                        <td className="px-3 py-2.5 hidden md:table-cell text-muted-foreground">{p.classroom ?? "—"}</td>
                        <td className="px-3 py-2.5 hidden lg:table-cell font-mono text-[11px] text-muted-foreground">{p.invoiceNo}</td>
                        <td className="px-3 py-2.5 text-right tabular-nums font-semibold text-emerald-600">{fmtCedi(p.amount)}</td>
                        <td className="px-3 py-2.5 hidden sm:table-cell text-xs">{PAYMENT_LABELS[p.method] ?? p.method}</td>
                        <td className="px-3 py-2.5 hidden xl:table-cell text-xs text-muted-foreground">{fmtDate(p.paidAt)}</td>
                        <td className="px-3 py-2.5 text-right">
                          <Button size="sm" variant="outline" className="h-7 gap-1" onClick={() => viewReceipt(p)}><Eye className="size-3.5" /> Reprint</Button>
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

        <TabsContent value="discounts">
          <DiscountsTab
            discounts={discounts}
            invoices={data.invoices}
            onEdit={(d) => setDiscDialog(d)}
            onApply={(d) => setApplyDialog(d)}
            onRefresh={() => load(termId ?? undefined)}
          />
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
      <InvoiceDiscountDialog
        invoice={discFor}
        catalog={discounts?.discounts ?? []}
        onOpenChange={(o) => !o && setDiscFor(null)}
        onDone={() => { setDiscFor(null); load(termId ?? undefined); }}
      />
      <DiscountCatalogDialog
        discount={discDialog}
        onOpenChange={(o) => !o && setDiscDialog(null)}
        onDone={() => { setDiscDialog(null); load(termId ?? undefined); }}
      />
      <ApplyDiscountDialog
        discount={applyDialog}
        invoices={data.invoices}
        services={discounts?.services ?? []}
        onOpenChange={(o) => !o && setApplyDialog(null)}
        onDone={() => { setApplyDialog(null); load(termId ?? undefined); }}
      />
      <ReceiptDialog receipt={receipt} onOpenChange={(o) => !o && setReceipt(null)} />
      {bulkReceipts && <BulkReceiptsDocument receipts={bulkReceipts} onDone={() => { setBulkReceipts(null); setCheckedReceipts(new Set()); }} />}
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
        onDone(res.data?.receipt ?? res.data?.payment ?? null);
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
  const isPayload = receipt && receipt.type && receipt.school;
  const [style, setStyle] = useState<ReceiptStyle>((receipt?.style as ReceiptStyle) ?? "A5");
  const [copy, setCopy] = useState<"ORIGINAL" | "DUPLICATE">("ORIGINAL");
  useEffect(() => { if (receipt?.style) setStyle(receipt.style); }, [receipt]);
  return (
    <Dialog open={!!receipt} onOpenChange={onOpenChange}>
      <DialogContent className={isPayload ? "max-w-[560px] max-h-[92vh] overflow-y-auto nice-scroll" : "max-w-sm"}>
        <DialogHeader className="text-center no-print">
          <DialogTitle className="flex items-center justify-center gap-2"><CheckCircle2 className="size-5 text-emerald-600" /> {isPayload ? "Official receipt" : "Payment received"}</DialogTitle>
        </DialogHeader>
        {isPayload ? (
          <>
            <div className="print-area"><ReceiptDocument data={{ ...receipt, style }} copy={copy} /></div>
            <div className="no-print flex flex-wrap items-center justify-center gap-1.5">
              {RECEIPT_STYLES.map((s) => (
                <Button key={s.value} size="sm" variant={style === s.value ? "default" : "outline"} className="h-7 text-xs" title={s.hint} onClick={() => setStyle(s.value)}>{s.label}</Button>
              ))}
              <Button size="sm" variant={copy === "DUPLICATE" ? "default" : "outline"} className="h-7 text-xs gap-1" onClick={() => setCopy(copy === "ORIGINAL" ? "DUPLICATE" : "ORIGINAL")} title="Mark this print as a duplicate copy">
                <CopyIcon className="size-3.5" /> {copy}
              </Button>
            </div>
          </>
        ) : (
          <div className="text-center space-y-1 py-2">
            <div className="text-3xl font-bold text-primary">{fmtCedi(receipt?.amountCents ?? 0)}</div>
            <div className="text-sm">{receipt?.student}</div>
            <div className="font-mono text-xs text-muted-foreground">{receipt?.receiptNo}</div>
            <div className="text-xs text-muted-foreground">{PAYMENT_LABELS[receipt?.method] ?? receipt?.method} · {fmtDate(receipt?.paidAt)}</div>
          </div>
        )}
        <DialogFooter className="sm:justify-center gap-2 no-print">
          <Button variant="outline" size="sm" onClick={() => window.print()}><Printer className="size-4" /> Print receipt</Button>
          <Button size="sm" onClick={() => onOpenChange(false)}>Done</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── DISCOUNTS TAB ───────────────────────────────────────────
function DiscountsTab({ discounts, invoices, onEdit, onApply, onRefresh }: {
  discounts: DiscountData | null;
  invoices: FeeData["invoices"];
  onEdit: (d: DiscountRow | "new") => void;
  onApply: (d: DiscountRow) => void;
  onRefresh: () => void;
}) {
  const { currentSchoolId } = useStore();

  async function revoke(applicationId: string) {
    await apiPost(`/api/school/${currentSchoolId}/ops/discounts`, { action: "revoke", applicationId });
    toast({ title: "Discount revoked" });
    onRefresh();
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <p className="text-sm text-muted-foreground max-w-2xl">
          Scholarships, sibling rates and needy-family support — applied to termly invoices or to daily services as a subsidised rate.
          Auto-apply discounts are computed every time invoices are generated.
        </p>
        <Button size="sm" className="gap-1.5" onClick={() => onEdit("new")}><Plus className="size-4" /> New discount</Button>
      </div>

      <div className="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
        {(discounts?.discounts ?? []).map((d) => (
          <Card key={d.id} className={d.active ? "" : "opacity-60"}>
            <CardHeader className="pb-2 flex-row items-center justify-between space-y-0">
              <CardTitle className="text-sm flex items-center gap-2"><Ticket className="size-4 text-primary" /> {d.name}</CardTitle>
              <Badge variant="secondary">{d.type === "PERCENT" ? `${d.value}%` : fmtCedi(d.value)}</Badge>
            </CardHeader>
            <CardContent className="space-y-2.5">
              <div className="flex flex-wrap gap-1.5">
                <Badge variant="outline" className="text-[10px]">{d.scope === "INVOICE" ? "Termly invoices" : "Daily services"}</Badge>
                {d.autoApply && <Badge className="text-[10px] bg-primary">auto-apply</Badge>}
                {!d.active && <Badge variant="outline" className="text-[10px]">paused</Badge>}
                <Badge variant="secondary" className="text-[10px]">{d.applied} learner{d.applied === 1 ? "" : "s"}</Badge>
              </div>
              {d.notes && <p className="text-xs text-muted-foreground leading-relaxed">{d.notes}</p>}
              <div className="flex gap-2 pt-1">
                <Button size="sm" className="h-7 flex-1 gap-1" onClick={() => onApply(d)}><UserPlus className="size-3.5" /> Apply to learner</Button>
                <Button size="sm" variant="ghost" className="h-7" onClick={() => onEdit(d)}>Edit</Button>
              </div>
            </CardContent>
          </Card>
        ))}
        {(discounts?.discounts ?? []).length === 0 && (
          <Card className="md:col-span-2 xl:col-span-3"><CardContent><EmptyState icon={<Ticket className="size-5" />} title="No discounts yet" hint="Create sibling, scholarship or needy-support discounts." /></CardContent></Card>
        )}
      </div>

      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base">Active applications</CardTitle></CardHeader>
        <CardContent className="p-0">
          <div className="overflow-x-auto nice-scroll max-h-80">
            <table className="w-full text-sm">
              <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                <tr className="text-left border-b">
                  <th className="px-3 py-2.5 font-medium">Learner</th>
                  <th className="px-3 py-2.5 font-medium">Discount</th>
                  <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Reason</th>
                  <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Applied by</th>
                  <th className="px-3 py-2.5 font-medium">Status</th>
                  <th className="px-3 py-2.5 text-right"></th>
                </tr>
              </thead>
              <tbody>
                {(discounts?.applications ?? []).length === 0 && <tr><td colSpan={6}><EmptyState title="No applications" /></td></tr>}
                {(discounts?.applications ?? []).map((a) => (
                  <tr key={a.id} className={`border-b last:border-0 hover:bg-secondary/30 ${a.status !== "ACTIVE" ? "opacity-50" : ""}`}>
                    <td className="px-3 py-2.5 font-medium">{a.student}</td>
                    <td className="px-3 py-2.5">{a.discountName} <Badge variant="secondary" className="text-[10px] ml-1">{a.type === "PERCENT" ? `${a.value}%` : fmtCedi(a.value)}</Badge></td>
                    <td className="px-3 py-2.5 hidden sm:table-cell text-muted-foreground text-xs max-w-64 truncate">{a.reason ?? "—"}</td>
                    <td className="px-3 py-2.5 hidden lg:table-cell text-muted-foreground text-xs">{a.appliedByName}</td>
                    <td className="px-3 py-2.5"><StatusBadge status={a.status === "ACTIVE" ? "APPROVED" : a.status} /></td>
                    <td className="px-3 py-2.5 text-right">
                      {a.status === "ACTIVE" && (
                        <Button size="sm" variant="ghost" className="h-7 gap-1 text-destructive" onClick={() => revoke(a.id)}><XCircle className="size-3.5" /> Revoke</Button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
      <p className="text-xs text-muted-foreground">{invoices.length} current-term invoices · discounts show as a green line under the invoice total.</p>
    </div>
  );
}

function DiscountCatalogDialog({ discount, onOpenChange, onDone }: { discount: DiscountRow | "new" | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [name, setName] = useState("");
  const [type, setType] = useState("PERCENT");
  const [value, setValue] = useState("");
  const [scope, setScope] = useState("INVOICE");
  const [autoApply, setAutoApply] = useState(false);
  const [notes, setNotes] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (discount && discount !== "new") {
      setName(discount.name); setType(discount.type); setValue(String(discount.value)); setScope(discount.scope); setAutoApply(discount.autoApply); setNotes(discount.notes ?? "");
    } else if (discount === "new") { setName(""); setType("PERCENT"); setValue(""); setScope("INVOICE"); setAutoApply(false); setNotes(""); }
  }, [discount]);

  async function submit() {
    if (!name || !value) { toast({ title: "Name and value are required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/discounts`, {
        action: "save-discount", id: discount && discount !== "new" ? discount.id : undefined,
        name, type, value: Number(value), scope, autoApply, notes,
      });
      toast({ title: discount === "new" ? "Discount created" : "Discount updated" });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!discount} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{discount === "new" ? "New discount" : "Edit discount"}</DialogTitle>
          <DialogDescription>Percentage or fixed amount, on termly invoices or daily services.</DialogDescription>
        </DialogHeader>
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1.5 col-span-2"><Label>Name *</Label><Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Sibling discount (2+ learners)" /></div>
          <div className="space-y-1.5">
            <Label>Type</Label>
            <Select value={type} onValueChange={setType}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent><SelectItem value="PERCENT">Percentage</SelectItem><SelectItem value="FIXED">Fixed amount</SelectItem></SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5"><Label>Value *</Label><Input type="number" value={value} onChange={(e) => setValue(e.target.value)} placeholder={type === "PERCENT" ? "10" : "5000 (pesewas)"} /></div>
          <div className="space-y-1.5">
            <Label>Applies to</Label>
            <Select value={scope} onValueChange={setScope}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent><SelectItem value="INVOICE">Termly invoices</SelectItem><SelectItem value="DAILY_SERVICE">Daily services</SelectItem></SelectContent>
            </Select>
          </div>
          {scope === "INVOICE" && (
            <div className="space-y-1.5">
              <Label>Auto-apply at generation</Label>
              <div className="flex gap-2 pt-1.5">
                {[true, false].map((v) => (
                  <button key={String(v)} type="button" onClick={() => setAutoApply(v)}
                    className={`px-3 py-1.5 rounded-lg border text-xs font-medium ${autoApply === v ? "bg-primary text-primary-foreground border-primary" : "hover:bg-secondary"}`}>
                    {v ? "Yes" : "Manual only"}
                  </button>
                ))}
              </div>
            </div>
          )}
          <div className="space-y-1.5 col-span-2"><Label>Notes</Label><Input value={notes} onChange={(e) => setNotes(e.target.value)} placeholder="Policy or eligibility note" /></div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : "Save discount"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function ApplyDiscountDialog({ discount, invoices, services, onOpenChange, onDone }: {
  discount: DiscountRow | null; invoices: FeeData["invoices"]; services: DiscountData["services"];
  onOpenChange: (o: boolean) => void; onDone: () => void;
}) {
  const { currentSchoolId } = useStore();
  const [studentId, setStudentId] = useState("");
  const [serviceId, setServiceId] = useState("");
  const [reason, setReason] = useState("");
  const [search, setSearch] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => { setStudentId(""); setServiceId(""); setReason(""); setSearch(""); }, [discount]);

  const list = invoices.filter((i) => !search || i.student.toLowerCase().includes(search) || (i.classroom ?? "").toLowerCase().includes(search));

  async function submit() {
    if (!discount || !studentId) { toast({ title: "Choose a learner" }); return; }
    if (discount.scope === "DAILY_SERVICE" && !serviceId) { toast({ title: "Choose the service" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/discounts`, {
        action: "apply", discountId: discount.id, studentId,
        serviceId: discount.scope === "DAILY_SERVICE" ? serviceId : undefined, reason: reason || undefined,
      });
      toast({
        title: "Discount applied",
        description: discount.scope === "DAILY_SERVICE" ? "The learner's daily rate is now subsidised." : "Will be applied when this term's invoice is generated (auto-apply), or add it manually on the invoice with the % button.",
      });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!discount} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Ticket className="size-4 text-primary" /> Apply — {discount?.name}</DialogTitle>
          <DialogDescription>{discount?.type === "PERCENT" ? `${discount.value}% off` : fmtCedi(discount?.value ?? 0)} · {discount?.scope === "INVOICE" ? "termly invoices" : "daily services"}</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <Input placeholder="Search learner…" value={search} onChange={(e) => setSearch(e.target.value)} />
          <div className="border rounded-lg max-h-48 overflow-y-auto nice-scroll">
            {list.slice(0, 40).map((i) => (
              <button key={i.studentId} type="button" onClick={() => setStudentId(i.studentId)}
                className={`w-full flex items-center justify-between px-3 py-2 border-b last:border-0 text-sm text-left ${studentId === i.studentId ? "bg-primary text-primary-foreground" : "hover:bg-secondary/50"}`}>
                <span>{i.student}</span><span className="text-xs opacity-70">{i.classroom}</span>
              </button>
            ))}
          </div>
          {discount?.scope === "DAILY_SERVICE" && (
            <div className="space-y-1.5">
              <Label>Service to subsidise</Label>
              <Select value={serviceId} onValueChange={setServiceId}>
                <SelectTrigger><SelectValue placeholder="Choose service" /></SelectTrigger>
                <SelectContent>{services.map((s) => <SelectItem key={s.id} value={s.id}>{s.name} ({fmtCedi(s.price)})</SelectItem>)}</SelectContent>
              </Select>
            </div>
          )}
          <div className="space-y-1.5">
            <Label>Reason / evidence</Label>
            <Input value={reason} onChange={(e) => setReason(e.target.value)} placeholder="e.g. Two siblings enrolled · PTA welfare verified" />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || !studentId}>{busy ? "Applying…" : "Apply discount"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function InvoiceDiscountDialog({ invoice, catalog, onOpenChange, onDone }: {
  invoice: FeeData["invoices"][0] | null; catalog: DiscountRow[];
  onOpenChange: (o: boolean) => void; onDone: () => void;
}) {
  const { currentSchoolId } = useStore();
  const [discountId, setDiscountId] = useState("ADHOC");
  const [type, setType] = useState("PERCENT");
  const [value, setValue] = useState("10");
  const [busy, setBusy] = useState(false);

  useEffect(() => { if (invoice) { setDiscountId("ADHOC"); setType("PERCENT"); setValue("10"); } }, [invoice]);

  const chosen = catalog.find((c) => c.id === discountId);

  async function submit() {
    if (!invoice) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/discounts`, {
        action: "apply-to-invoice", invoiceId: invoice.id,
        discountId: discountId !== "ADHOC" ? discountId : undefined,
        type: discountId !== "ADHOC" ? undefined : type,
        value: discountId !== "ADHOC" ? undefined : Number(value),
        reason: discountId !== "ADHOC" ? undefined : "Manual discount",
      });
      toast({ title: "Discount applied", description: `${invoice.student}'s invoice updated.` });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!invoice} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Percent className="size-4" /> Discount — {invoice?.student}</DialogTitle>
          <DialogDescription>
            {invoice?.invoiceNo} · total {invoice ? fmtCedi(invoice.total) : ""}{invoice && invoice.discount > 0 ? ` · already discounted ${fmtCedi(invoice.discount)} (${invoice.discountReason ?? ""})` : ""}
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="space-y-1.5">
            <Label>Discount</Label>
            <Select value={discountId} onValueChange={(v) => setDiscountId(v)}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="ADHOC">Ad-hoc (custom)</SelectItem>
                {catalog.filter((c) => c.scope === "INVOICE" && c.active).map((c) => <SelectItem key={c.id} value={c.id}>{c.name} ({c.type === "PERCENT" ? `${c.value}%` : fmtCedi(c.value)})</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          {discountId === "ADHOC" && (
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label>Type</Label>
                <Select value={type} onValueChange={setType}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent><SelectItem value="PERCENT">Percentage</SelectItem><SelectItem value="FIXED">Fixed (GH₵)</SelectItem></SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5"><Label>Value</Label><Input type="number" value={value} onChange={(e) => setValue(e.target.value)} /></div>
            </div>
          )}
          {chosen && <p className="text-xs text-muted-foreground">{chosen.notes ?? "From the discount catalog."}</p>}
          <p className="text-xs text-muted-foreground">Discounts reduce the balance owed; if the learner has already paid beyond the new balance, the difference becomes a credit.</p>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Applying…" : "Apply to invoice"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function cn(...args: any[]) { return args.filter(Boolean).join(" "); }

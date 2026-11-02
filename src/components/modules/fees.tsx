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
import { StatCard, ModuleHeader, LoadingBlock, StatusBadge, EmptyState, FilterBar, FilterSelect, FilterSearch, downloadCsv } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, PAYMENT_LABELS } from "@/lib/format";
import { canAny } from "@/lib/rbac";
import { toast } from "@/hooks/use-toast";
import { Banknote, Receipt, AlertTriangle, CheckCircle2, Plus, Flag, Printer, Wallet, Ticket, Percent, UserPlus, XCircle, ListChecks, Copy as CopyIcon, Eye, Link2, ClipboardList, ShieldCheck, Undo2, PencilLine, Trash2, FileWarning, Ban, Download } from "lucide-react";
import { ReceiptDocument, BulkReceiptsDocument, RECEIPT_STYLES, type ReceiptStyle } from "@/components/receipts";
import { Checkbox } from "@/components/ui/checkbox";
import { DiscountProfilesTab, DiscountApprovalsTab, type DiscountData } from "@/components/modules/discounts-workspace";

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
export type { DiscountData };

/* receipt/invoice modification-approval queue (iEduPro parity) */
type ModRequest = {
  id: string; targetType: "PAYMENT" | "INVOICE"; recordId: string; recordCode: string;
  requestType: "EDIT" | "DELETE"; reason: string; status: string;
  requestedById: string; requestedByName: string;
  originalData: Record<string, any> | null; newData: Record<string, any> | null;
  handledByName: string | null; handledAt: string | null; decisionNote: string | null; createdAt: string;
};
type ApprovalsData = { pending: ModRequest[]; recent: ModRequest[] };

export function Fees() {
  const { currentSchoolId, online, navTarget, session } = useStore();
  const [data, setData] = useState<FeeData | null>(null);
  const [discounts, setDiscounts] = useState<DiscountData | null>(null);
  const [approvals, setApprovals] = useState<ApprovalsData | null>(null);
  const [termId, setTermId] = useState<string | null>(null);
  const [tab, setTab] = useState("invoices");
  const [payFor, setPayFor] = useState<FeeData["invoices"][0] | null>(null);
  const [flagFor, setFlagFor] = useState<FeeData["invoices"][0] | null>(null);
  const [discFor, setDiscFor] = useState<FeeData["invoices"][0] | null>(null);

  /* iEduPro nested nav: open on the deep-linked tab (invoices / receipts / discounts…) */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab && ["invoices", "receipts", "structures", "discounts", "arrears", "approvals"].includes(navTarget.tab)) setTab(navTarget.tab);
  }
  const canManageApprovals = canAny(session?.permissions, ["approvals.manage"]);
  const canRequestMods = canAny(session?.permissions, ["approvals.request", "approvals.manage"]);
  const [discDialog, setDiscDialog] = useState<DiscountRow | "new" | null>(null);
  const [applyDialog, setApplyDialog] = useState<DiscountRow | null>(null);
  const [receipt, setReceipt] = useState<any | null>(null);
  const [receiptStyle, setReceiptStyle] = useState<"A5" | "THERMAL">("A5");
  const [checkedReceipts, setCheckedReceipts] = useState<Set<string>>(new Set());
  const [bulkReceipts, setBulkReceipts] = useState<any[] | null>(null);
  const [bulkBusy, setBulkBusy] = useState(false);

  // invoice + receipt filters (filter-on-demand for busy termly ledgers)
  const [invSearch, setInvSearch] = useState("");
  const [invClass, setInvClass] = useState("all");
  const [invStatus, setInvStatus] = useState("all");
  const [rcpSearch, setRcpSearch] = useState("");
  const [rcpMethod, setRcpMethod] = useState("all");

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
    apiGet<ApprovalsData>(`/api/school/${currentSchoolId}/ops/reports?tab=approvals`).then(setApprovals).catch(() => null);
  };
  const loadApprovals = () => {
    if (!currentSchoolId) return;
    apiGet<ApprovalsData>(`/api/school/${currentSchoolId}/ops/reports?tab=approvals`).then(setApprovals).catch(() => null);
  };
  useEffect(() => { load(); }, [currentSchoolId]);

  const overdue = useMemo(() => (data?.invoices ?? []).filter((i) => i.status === "OVERDUE" || (i.status === "UNPAID" && i.balance > 0)).sort((a, b) => b.balance - a.balance), [data]);
  const allPayments = useMemo(() =>
    (data?.invoices ?? []).flatMap((i) => i.payments.map((p) => ({ ...p, student: i.student, classroom: i.classroom, invoiceNo: i.invoiceNo }))).sort((a, b) => new Date(b.paidAt).getTime() - new Date(a.paidAt).getTime()),
  [data]);
  const classOptions = useMemo(() => {
    const seen = new Map<string, string>();
    (data?.invoices ?? []).forEach((i) => { if (i.classroom) seen.set(i.classroom, i.classroom); });
    return [...seen.values()].sort().map((c) => ({ value: c, label: c }));
  }, [data]);
  const filteredInvoices = useMemo(() => (data?.invoices ?? []).filter((i) => {
    if (invClass !== "all" && i.classroom !== invClass) return false;
    if (invStatus !== "all") {
      if (invStatus === "owing") { if (!(i.balance > 0)) return false; }
      else if (i.status !== invStatus) return false;
    }
    if (invSearch.trim()) {
      const q = invSearch.toLowerCase();
      if (!`${i.student} ${i.invoiceNo} ${i.classroom ?? ""}`.toLowerCase().includes(q)) return false;
    }
    return true;
  }), [data, invClass, invStatus, invSearch]);
  const invFiltersActive = invClass !== "all" || invStatus !== "all" || !!invSearch.trim();
  const visiblePayments = useMemo(() => allPayments.filter((p) => {
    if (rcpMethod !== "all" && p.method !== rcpMethod) return false;
    if (rcpSearch.trim()) {
      const q = rcpSearch.toLowerCase();
      if (!`${p.student} ${p.receiptNo} ${p.classroom ?? ""} ${p.invoiceNo}`.toLowerCase().includes(q)) return false;
    }
    return true;
  }), [allPayments, rcpMethod, rcpSearch]);
  const rcpFiltersActive = rcpMethod !== "all" || !!rcpSearch.trim();

  const exportInvoicesCsv = () => downloadCsv(
    `invoices-${data?.terms.find((t) => t.id === termId)?.name ?? "term"}.csv`,
    ["Invoice", "Learner", "Class", "Total (GH¢)", "Discount (GH¢)", "Paid (GH¢)", "Balance (GH¢)", "Status"],
    filteredInvoices.map((i) => [i.invoiceNo, i.student, i.classroom ?? "", i.total / 100, i.discount / 100, i.paid / 100, i.balance / 100, i.status]),
  );

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
          <TabsTrigger value="approvals" className="gap-1.5">
            Approvals
            {(approvals?.pending.length ?? 0) > 0 && <span className="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-destructive text-destructive-foreground text-[10px] font-bold">{approvals!.pending.length}</span>}
          </TabsTrigger>
        </TabsList>

        <TabsContent value="invoices">
          <div className="space-y-3">
            <FilterBar active={invFiltersActive} onClear={() => { setInvClass("all"); setInvStatus("all"); setInvSearch(""); }} count={filteredInvoices.length} total={data.invoices.length} label="Invoice filters"
              actions={<Button size="sm" variant="outline" className="h-7 gap-1 text-xs" onClick={exportInvoicesCsv} disabled={!filteredInvoices.length}><Download className="size-3.5" /> CSV</Button>}>
              <FilterSearch value={invSearch} onChange={setInvSearch} placeholder="Learner, invoice no…" />
              <FilterSelect label="Class" value={invClass} onChange={setInvClass} options={[{ value: "all", label: "All classes" }, ...classOptions]} />
              <FilterSelect label="Status" value={invStatus} onChange={setInvStatus} options={[
                { value: "all", label: "All" }, { value: "owing", label: "Owing" }, { value: "UNPAID", label: "Unpaid" },
                { value: "PARTIAL", label: "Partial" }, { value: "PAID", label: "Paid" }, { value: "OVERDUE", label: "Overdue" },
              ]} />
            </FilterBar>
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
                      {filteredInvoices.map((inv) => (
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
                      {!filteredInvoices.length && <tr><td colSpan={8}><EmptyState icon={<Receipt className="size-5" />} title="No invoices match" hint="Adjust the class, status or search." /></td></tr>}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
          </div>
        </TabsContent>

        <TabsContent value="receipts">
          <div className="space-y-3">
            <FilterBar active={rcpFiltersActive} onClear={() => { setRcpMethod("all"); setRcpSearch(""); }} count={visiblePayments.length} total={allPayments.length} label="Receipt filters">
              <FilterSearch value={rcpSearch} onChange={setRcpSearch} placeholder="Learner, receipt no…" />
              <FilterSelect label="Method" value={rcpMethod} onChange={setRcpMethod} options={[
                { value: "all", label: "All methods" },
                ...Object.entries(PAYMENT_LABELS).map(([k, v]) => ({ value: k, label: v })),
              ]} />
            </FilterBar>
          <Card>
            <CardHeader className="pb-3 flex-row items-center justify-between space-y-0 flex-wrap gap-2">
              <CardTitle className="text-base flex items-center gap-2"><Receipt className="size-4" /> Issued receipts — {visiblePayments.length} shown</CardTitle>
              <div className="flex items-center gap-2">
                <Checkbox
                  checked={visiblePayments.length > 0 && visiblePayments.every((p) => checkedReceipts.has(p.id))}
                  onCheckedChange={(v) => setCheckedReceipts(v ? new Set(visiblePayments.map((p) => p.id)) : new Set())}
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
                    {visiblePayments.slice(0, 150).map((p) => (
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
          </div>
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

        <TabsContent value="approvals">
          <ApprovalsTab
            approvals={approvals}
            canManage={canManageApprovals}
            canRequest={canRequestMods}
            receipts={allPayments}
            invoices={data.invoices}
            onRefresh={() => { loadApprovals(); load(termId ?? undefined); }}
          />
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
  const [style, setStyle] = useState<ReceiptStyle>("A5");
  const [copy, setCopy] = useState<"ORIGINAL" | "DUPLICATE">("ORIGINAL");
  const activeStyle = (receipt?.style as ReceiptStyle) ?? style;
  return (
    <Dialog open={!!receipt} onOpenChange={onOpenChange}>
      <DialogContent className={isPayload ? "max-w-[560px] max-h-[92vh] overflow-y-auto nice-scroll" : "max-w-sm"}>
        <DialogHeader className="text-center no-print">
          <DialogTitle className="flex items-center justify-center gap-2"><CheckCircle2 className="size-5 text-emerald-600" /> {isPayload ? "Official receipt" : "Payment received"}</DialogTitle>
        </DialogHeader>
        {isPayload ? (
          <>
            <div className="print-area"><ReceiptDocument data={{ ...receipt, style: activeStyle }} copy={copy} /></div>
            <div className="no-print flex flex-wrap items-center justify-center gap-1.5">
              {RECEIPT_STYLES.map((s) => (
                <Button key={s.value} size="sm" variant={activeStyle === s.value ? "default" : "outline"} className="h-7 text-xs" title={s.hint} onClick={() => setStyle(s.value)}>{s.label}</Button>
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
  const [sub, setSub] = useState("catalog");
  const students = useMemo(() => {
    const seen = new Map<string, { id: string; name: string; classroom: string | null }>();
    invoices.forEach((i) => seen.set(i.studentId, { id: i.studentId, name: i.student, classroom: i.classroom }));
    return [...seen.values()];
  }, [invoices]);

  async function revoke(applicationId: string) {
    await apiPost(`/api/school/${currentSchoolId}/ops/discounts`, { action: "revoke", applicationId });
    toast({ title: "Discount revoked" });
    onRefresh();
  }

  return (
    <div className="space-y-4">
      <Tabs value={sub} onValueChange={setSub}>
        <TabsList>
          <TabsTrigger value="catalog">Catalog ({discounts?.discounts.length ?? 0})</TabsTrigger>
          <TabsTrigger value="profiles">Profiles ({discounts?.profiles.length ?? 0})</TabsTrigger>
          <TabsTrigger value="approvals">
            Approvals{discounts?.counts.pending ? ` (${discounts.counts.pending})` : ""}
          </TabsTrigger>
          <TabsTrigger value="assignments">All assignments</TabsTrigger>
        </TabsList>

        {/* ── catalog ── */}
        <TabsContent value="catalog" className="space-y-4">
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
        <CardHeader className="pb-3"><CardTitle className="text-base">Applications</CardTitle></CardHeader>
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
        </TabsContent>

        {/* ── profiles + approvals + assignments (iedupro parity) ── */}
        <TabsContent value="profiles">
          <DiscountProfilesTab data={discounts} students={students} onRefresh={onRefresh} />
        </TabsContent>
        <TabsContent value="approvals">
          <DiscountApprovalsTab data={discounts} onRefresh={onRefresh} defaultFilter="PENDING" />
        </TabsContent>
        <TabsContent value="assignments">
          <DiscountApprovalsTab data={discounts} onRefresh={onRefresh} defaultFilter="ALL" />
        </TabsContent>
      </Tabs>
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

/* ── APPROVALS TAB (receipt/invoice modification queue — iEduPro parity) ─── */
function ApprovalsTab({ approvals, canManage, canRequest, receipts, invoices, onRefresh }: {
  approvals: ApprovalsData | null;
  canManage: boolean;
  canRequest: boolean;
  receipts: { id: string; receiptNo: string; amount: number; method: string; paidAt: string; student: string; invoiceNo: string }[];
  invoices: FeeData["invoices"];
  onRefresh: () => void;
}) {
  const { currentSchoolId } = useStore();
  const [requestOpen, setRequestOpen] = useState(false);
  const [decideFor, setDecideFor] = useState<{ req: ModRequest; decision: "APPROVED" | "REJECTED" } | null>(null);
  const [busy, setBusy] = useState(false);

  async function decide(req: ModRequest, decision: "APPROVED" | "REJECTED", note: string) {
    if (!currentSchoolId) return;
    setBusy(true);
    try {
      const res = await apiPost<{ request: ModRequest }>(`/api/school/${currentSchoolId}/ops/reports`, { action: "modreq.decide", id: req.id, decision, note: note || undefined });
      toast({
        title: decision === "APPROVED" ? "Modification approved" : "Request rejected",
        description: res.request?.decisionNote ?? undefined,
      });
      setDecideFor(null);
      onRefresh();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  async function revoke(req: ModRequest) {
    if (!currentSchoolId) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/reports`, { action: "modreq.revoke", id: req.id });
      toast({ title: "Approval revoked", description: "The data change is NOT auto-reversed — manual reversal required.", variant: "destructive" });
      onRefresh();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  if (!approvals) return <Card><CardContent><LoadingBlock label="Loading the approval queue…" /></CardContent></Card>;

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <p className="text-sm text-muted-foreground max-w-2xl">
          Cashiers request receipt &amp; invoice corrections; only a Level-1 administrator (or proprietor) can approve, reject or revoke them.
          Approving a deletion reverses the linked invoice automatically; revocation never auto-reverses — that stays a manual, audited step.
        </p>
        {canRequest && (
          <Button size="sm" className="gap-1.5" onClick={() => setRequestOpen(true)}><PencilLine className="size-4" /> Request modification</Button>
        )}
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<ClipboardList className="size-4" />} label="Pending" value={approvals.pending.length} tone={approvals.pending.length ? "danger" : "default"} sub="awaiting decision" />
        <StatCard icon={<ShieldCheck className="size-4" />} label="Approved" value={approvals.recent.filter((r) => r.status === "APPROVED").length} tone="primary" sub="all time (recent)" />
        <StatCard icon={<Ban className="size-4" />} label="Rejected" value={approvals.recent.filter((r) => r.status === "REJECTED").length} sub="no data change" />
        <StatCard icon={<Undo2 className="size-4" />} label="Revoked" value={approvals.recent.filter((r) => r.status === "REVOKED").length} tone="gold" sub="manual reversal" />
      </div>

      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base flex items-center gap-2"><ClipboardList className="size-4 text-destructive" /> Pending queue — {approvals.pending.length}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-2 max-h-[520px] overflow-y-auto nice-scroll">
          {approvals.pending.length === 0 && <EmptyState icon={<CheckCircle2 className="size-5" />} title="Nothing awaiting approval" hint="Correction requests raised at the counter appear here for the administrator's decision." />}
          {approvals.pending.map((r) => (
            <div key={r.id} className="rounded-lg border p-3 flex flex-wrap items-start gap-3 justify-between">
              <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-1.5">
                  <Badge variant={r.targetType === "PAYMENT" ? "secondary" : "outline"} className="text-[10px] uppercase tracking-wide">{r.targetType === "PAYMENT" ? "Receipt" : "Invoice"}</Badge>
                  <span className="font-mono text-xs font-semibold">{r.recordCode}</span>
                  <Badge variant={r.requestType === "DELETE" ? "destructive" : "default"} className="text-[10px] gap-1">
                    {r.requestType === "DELETE" ? <Trash2 className="size-3" /> : <PencilLine className="size-3" />} {r.requestType}
                  </Badge>
                </div>
                <div className="text-sm mt-1.5">{r.reason}</div>
                <div className="text-xs text-muted-foreground mt-1">
                  requested by {r.requestedByName} · {fmtDate(r.createdAt)}
                  {r.originalData?.amountCents != null && <> · original <b className="text-foreground">{fmtCedi(r.originalData.amountCents)}</b> {r.originalData.method ? `(${PAYMENT_LABELS[r.originalData.method] ?? r.originalData.method})` : ""}</>}
                  {r.originalData?.totalCents != null && <> · billed <b className="text-foreground">{fmtCedi(r.originalData.totalCents)}</b></>}
                  {r.newData?.amountCents != null && <> → proposed <b className="text-primary">{fmtCedi(r.newData.amountCents)}</b>{r.newData.method ? ` (${PAYMENT_LABELS[r.newData.method] ?? r.newData.method})` : ""}</>}
                  {r.newData?.totalCents != null && <> → proposed total <b className="text-primary">{fmtCedi(r.newData.totalCents)}</b></>}
                </div>
              </div>
              {canManage && (
                <div className="flex gap-2">
                  <Button size="sm" className="h-8 gap-1" disabled={busy} onClick={() => setDecideFor({ req: r, decision: "APPROVED" })}><ShieldCheck className="size-3.5" /> Approve</Button>
                  <Button size="sm" variant="destructive" className="h-8 gap-1" disabled={busy} onClick={() => setDecideFor({ req: r, decision: "REJECTED" })}><XCircle className="size-3.5" /> Reject</Button>
                </div>
              )}
            </div>
          ))}
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base flex items-center gap-2"><FileWarning className="size-4 text-muted-foreground" /> Recent decisions</CardTitle>
        </CardHeader>
        <CardContent className="p-0">
          <div className="overflow-x-auto nice-scroll max-h-80">
            <table className="w-full text-sm">
              <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                <tr className="text-left border-b">
                  <th className="px-3 py-2.5 font-medium">Record</th>
                  <th className="px-3 py-2.5 font-medium">Type</th>
                  <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Reason</th>
                  <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Requested by</th>
                  <th className="px-3 py-2.5 font-medium">Status</th>
                  <th className="px-3 py-2.5 font-medium hidden md:table-cell">Decision &amp; audit trail</th>
                  <th className="px-3 py-2.5 text-right"></th>
                </tr>
              </thead>
              <tbody>
                {approvals.recent.length === 0 && <tr><td colSpan={7}><EmptyState title="No decisions yet" /></td></tr>}
                {approvals.recent.map((r) => (
                  <tr key={r.id} className="border-b last:border-0 hover:bg-secondary/30">
                    <td className="px-3 py-2.5 font-mono text-xs">{r.recordCode}</td>
                    <td className="px-3 py-2.5 text-xs">{r.targetType === "PAYMENT" ? "Receipt" : "Invoice"} · {r.requestType}</td>
                    <td className="px-3 py-2.5 hidden sm:table-cell text-muted-foreground text-xs max-w-56 truncate" title={r.reason}>{r.reason}</td>
                    <td className="px-3 py-2.5 hidden lg:table-cell text-xs text-muted-foreground">{r.requestedByName}</td>
                    <td className="px-3 py-2.5"><StatusBadge status={r.status} /></td>
                    <td className="px-3 py-2.5 hidden md:table-cell text-xs text-muted-foreground max-w-80">
                      <div className="truncate" title={r.decisionNote ?? undefined}>{r.decisionNote ?? "—"}</div>
                      {r.handledByName && <div className="text-[10px]">{r.handledByName} · {fmtDate(r.handledAt)}</div>}
                    </td>
                    <td className="px-3 py-2.5 text-right">
                      {canManage && r.status === "APPROVED" && (
                        <Button size="sm" variant="ghost" className="h-7 gap-1 text-destructive" disabled={busy} onClick={() => revoke(r)}><Undo2 className="size-3.5" /> Revoke</Button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <RequestModificationDialog
        open={requestOpen}
        receipts={receipts}
        invoices={invoices}
        onOpenChange={(o) => !o && setRequestOpen(false)}
        onDone={() => { setRequestOpen(false); onRefresh(); }}
      />
      <DecideDialog
        key={decideFor ? `${decideFor.req.id}:${decideFor.decision}` : "none"}
        target={decideFor}
        onOpenChange={(o) => !o && setDecideFor(null)}
        busy={busy}
        onConfirm={(req, decision, note) => decide(req, decision, note)}
      />
    </div>
  );
}

/* raise an EDIT/DELETE request from the receipts register (or a pasted code) */
function RequestModificationDialog({ open, receipts, invoices, onOpenChange, onDone }: {
  open: boolean;
  receipts: { id: string; receiptNo: string; amount: number; method: string; paidAt: string; student: string; invoiceNo: string }[];
  invoices: FeeData["invoices"];
  onOpenChange: (o: boolean) => void;
  onDone: () => void;
}) {
  const { currentSchoolId } = useStore();
  const [targetType, setTargetType] = useState<"PAYMENT" | "INVOICE">("PAYMENT");
  const [requestType, setRequestType] = useState<"DELETE" | "EDIT">("DELETE");
  const [selected, setSelected] = useState(""); // row id from the register
  const [code, setCode] = useState(""); // pasted receipt no / invoice no
  const [reason, setReason] = useState("");
  const [newAmount, setNewAmount] = useState("");
  const [newMethod, setNewMethod] = useState("CASH");
  const [newTotal, setNewTotal] = useState("");
  const [search, setSearch] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (open) { setTargetType("PAYMENT"); setRequestType("DELETE"); setSelected(""); setCode(""); setReason(""); setNewAmount(""); setNewMethod("CASH"); setNewTotal(""); setSearch(""); }
  }, [open]);

  const list = targetType === "PAYMENT"
    ? receipts.filter((p) => !search || p.receiptNo.toLowerCase().includes(search.toLowerCase()) || p.student.toLowerCase().includes(search.toLowerCase()))
    : invoices.filter((i) => !search || i.invoiceNo.toLowerCase().includes(search.toLowerCase()) || i.student.toLowerCase().includes(search.toLowerCase()));

  const chosen: any = targetType === "PAYMENT"
    ? receipts.find((p) => p.id === selected)
    : invoices.find((i) => i.id === selected);

  async function submit() {
    const recordId = selected || code.trim();
    if (!recordId) { toast({ title: "Pick a record or paste its number" }); return; }
    if (!reason.trim()) { toast({ title: "A reason is required for the audit trail" }); return; }
    let newData: Record<string, unknown> | undefined;
    if (requestType === "EDIT") {
      if (targetType === "PAYMENT") {
        const cents = Math.round(parseFloat(newAmount || "0") * 100);
        if (!cents || cents <= 0) { toast({ title: "Enter the corrected amount" }); return; }
        newData = { amountCents: cents, method: newMethod };
      } else {
        const cents = Math.round(parseFloat(newTotal || "0") * 100);
        if (!cents || cents <= 0) { toast({ title: "Enter the corrected invoice total" }); return; }
        newData = { totalCents: cents };
      }
    }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/reports`, {
        action: "modreq.create", targetType, recordId, requestType, reason: reason.trim(),
        newData: requestType === "EDIT" ? newData : undefined,
      });
      toast({ title: "Request submitted", description: "A Level-1 administrator will review it — the queue lives under Fees → Approvals." });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg max-h-[92vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><PencilLine className="size-4" /> Request a receipt / invoice modification</DialogTitle>
          <DialogDescription>Corrections need administrator approval before any money record changes — nothing moves until they decide.</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>Target</Label>
              <Select value={targetType} onValueChange={(v: any) => { setTargetType(v); setSelected(""); setCode(""); }}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="PAYMENT">Receipt (payment)</SelectItem>
                  <SelectItem value="INVOICE">Invoice</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Request type</Label>
              <Select value={requestType} onValueChange={(v: any) => setRequestType(v)}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="DELETE">Delete</SelectItem>
                  <SelectItem value="EDIT">Edit</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="space-y-1.5">
            <Label>Pick from this term&apos;s register</Label>
            <Input placeholder={targetType === "PAYMENT" ? "Search receipt no / learner…" : "Search invoice no / learner…"} value={search} onChange={(e) => setSearch(e.target.value)} />
            <div className="border rounded-lg max-h-40 overflow-y-auto nice-scroll">
              {list.slice(0, 40).map((r: any) => (
                <button key={r.id} type="button" onClick={() => { setSelected(r.id); setCode(""); }}
                  className={`w-full flex items-center justify-between px-3 py-2 border-b last:border-0 text-sm text-left ${selected === r.id ? "bg-primary text-primary-foreground" : "hover:bg-secondary/50"}`}>
                  <span className="truncate">
                    <span className="font-mono text-xs">{targetType === "PAYMENT" ? r.receiptNo : r.invoiceNo}</span>
                    <span className="ml-2 opacity-80">{r.student}</span>
                  </span>
                  <span className="text-xs tabular-nums shrink-0 ml-2">{fmtCedi(targetType === "PAYMENT" ? r.amount : r.total)}</span>
                </button>
              ))}
              {list.length === 0 && <div className="px-3 py-3 text-xs text-muted-foreground">No matches in this term&apos;s register — paste the number below instead.</div>}
            </div>
          </div>

          <div className="space-y-1.5">
            <Label>…or paste the {targetType === "PAYMENT" ? "receipt no" : "invoice no"} directly</Label>
            <Input value={code} onChange={(e) => { setCode(e.target.value); setSelected(""); }} placeholder={targetType === "PAYMENT" ? "e.g. RCP-2026-00109" : "e.g. INV-2026-0002"} className="font-mono" />
          </div>

          {requestType === "EDIT" && (
            targetType === "PAYMENT" ? (
              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1.5">
                  <Label>Corrected amount (GH₵)</Label>
                  <Input type="number" step="0.01" value={newAmount} onChange={(e) => setNewAmount(e.target.value)} placeholder={chosen ? (chosen.amount / 100).toFixed(2) : "0.00"} />
                </div>
                <div className="space-y-1.5">
                  <Label>Method</Label>
                  <Select value={newMethod} onValueChange={setNewMethod}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>{Object.entries(PAYMENT_LABELS).map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}</SelectContent>
                  </Select>
                </div>
              </div>
            ) : (
              <div className="space-y-1.5">
                <Label>Corrected invoice total (GH₵)</Label>
                <Input type="number" step="0.01" value={newTotal} onChange={(e) => setNewTotal(e.target.value)} placeholder={chosen ? (chosen.total / 100).toFixed(2) : "0.00"} />
              </div>
            )
          )}

          <div className="space-y-1.5">
            <Label>Reason *</Label>
            <Input value={reason} onChange={(e) => setReason(e.target.value)} placeholder="e.g. Amount keyed as 300 instead of 450 — guardian's slip attached" />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || (!selected && !code.trim())}>{busy ? "Submitting…" : "Submit for approval"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* approve / reject with an optional decision note (Level-1 admin) */
function DecideDialog({ target, onOpenChange, busy, onConfirm }: {
  target: { req: ModRequest; decision: "APPROVED" | "REJECTED" } | null;
  onOpenChange: (o: boolean) => void;
  busy: boolean;
  onConfirm: (req: ModRequest, decision: "APPROVED" | "REJECTED", note: string) => void;
}) {
  const [note, setNote] = useState("");
  const approving = target?.decision === "APPROVED";
  return (
    <Dialog open={!!target} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            {approving ? <ShieldCheck className="size-4 text-primary" /> : <XCircle className="size-4 text-destructive" />}
            {approving ? "Approve" : "Reject"} — <span className="font-mono text-sm">{target?.req.recordCode}</span>
          </DialogTitle>
          <DialogDescription>
            {target?.req.requestType === "DELETE" ? "Deleting" : "Editing"} the {target?.req.targetType === "PAYMENT" ? "receipt" : "invoice"}
            {approving
              ? target?.req.targetType === "PAYMENT"
                ? " — the linked invoice will be reversed / re-synced automatically and the change recorded in the audit trail."
                : " — the invoice will be adjusted as requested."
              : " — no data will change."}
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="rounded-lg bg-secondary/50 p-3 text-sm space-y-1">
            <div>{target?.req.reason}</div>
            <div className="text-xs text-muted-foreground">requested by {target?.req.requestedByName} · {fmtDate(target?.req.createdAt)}</div>
          </div>
          <div className="space-y-1.5">
            <Label>Decision note (optional)</Label>
            <Input value={note} onChange={(e) => setNote(e.target.value)} placeholder={approving ? "e.g. Bank slip verified with the guardian." : "e.g. Bank confirmation shows the cash was received."} />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button variant={approving ? "default" : "destructive"} disabled={busy} onClick={() => target && onConfirm(target.req, target.decision, note)}>
            {busy ? "Applying…" : approving ? "Approve & apply" : "Reject request"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function cn(...args: any[]) { return args.filter(Boolean).join(" "); }

"use client";
import { useEffect, useMemo, useRef, useState } from "react";
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
import { cn } from "@/lib/utils";
import { Banknote, Receipt, AlertTriangle, CheckCircle2, Plus, Flag, Printer, Wallet, Ticket, Percent, UserPlus, XCircle, ListChecks, Copy as CopyIcon, Eye, Link2, ClipboardList, ShieldCheck, Undo2, PencilLine, Trash2, FileWarning, Ban, Download, History, ArrowRightLeft, PiggyBank, HandCoins, CopyPlus, FileUp, Smartphone, Search, UserRound, GraduationCap, Mail } from "lucide-react";
import { ReceiptDocument, BulkReceiptsDocument, RECEIPT_STYLES, type ReceiptStyle } from "@/components/receipts";
import { BillItemsTab, CreateInvoicesTab, type BillItemRow, type RosterRow } from "@/components/modules/fees-billing";
import { Checkbox } from "@/components/ui/checkbox";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { DiscountProfilesTab, DiscountApprovalsTab, type DiscountData } from "@/components/modules/discounts-workspace";

type FeeData = {
  terms: { id: string; name: string; isCurrent: boolean }[];
  termId: string;
  classrooms: { id: string; name: string; gradeLevel: string; stage?: string | null }[];
  billItems: BillItemRow[];
  roster: RosterRow[];
  structures: { id: string; classroom: string; classroomId: string; total: number; items: { name: string; amount: number; optional: boolean }[] }[];
  invoices: {
    id: string; invoiceNo: string; student: string; studentId: string; classroom: string | null; total: number; discount: number;
    discountReason: string | null; paid: number; balance: number; status: string; dueDate: string | null; guardianPhone: string; guardianEmail: string | null;
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

/* ── student credit wallets (prepaid credits from overpayment — CI3 parity) ── */
type CreditRow = { studentId: string; name: string; classroom: string | null; balanceCents: number; records: number; lastAt: string | null };
type CreditsData = {
  rows: CreditRow[];
  stats: { activeCents: number; studentsWithCredits: number; appliedCents: number; createdCents: number; utilizationPct: number };
  students: { id: string; name: string; classroom: string }[];
};
type CreditLedgerRow = { id: string; at: string; kind: string; amountCents: number; balanceAfter: number; note: string | null; byName: string | null; invoiceNo: string | null };
const CREDIT_KIND_LABELS: Record<string, string> = {
  CREDIT: "Credit in", APPLY: "Applied to bill", TRANSFER_OUT: "Transfer out", TRANSFER_IN: "Transfer in",
  DAILY_WALLET: "To daily wallet", ADJUST: "Adjustment",
};

export function Fees() {
  const { currentSchoolId, online, navTarget, session } = useStore();
  const [data, setData] = useState<FeeData | null>(null);
  const [discounts, setDiscounts] = useState<DiscountData | null>(null);
  const [approvals, setApprovals] = useState<ApprovalsData | null>(null);
  const [termId, setTermId] = useState<string | null>(null);
  const [tab, setTab] = useState("invoices");
  const [payFor, setPayFor] = useState<FeeData["invoices"][0] | null>(null);
  // "Take payment" (CI3 modal_take_payment) — opened blank from the module header
  const [payOpen, setPayOpen] = useState(false);
  const [flagFor, setFlagFor] = useState<FeeData["invoices"][0] | null>(null);
  const [discFor, setDiscFor] = useState<FeeData["invoices"][0] | null>(null);
  // clone-previous-term billing + bulk arrears CSV import (CI3 parity)
  const [cloneOpen, setCloneOpen] = useState(false);
  const [cloneSource, setCloneSource] = useState("");
  const [cloneBusy, setCloneBusy] = useState(false);
  const [arrearsOpen, setArrearsOpen] = useState(false);
  const [arrearsCsv, setArrearsCsv] = useState("");
  const [arrearsResult, setArrearsResult] = useState<any | null>(null);
  const [arrearsBusy, setArrearsBusy] = useState(false);

  /* iEduPro nested nav: open on the deep-linked tab (bill-items / create / invoices / receipts / credits / discounts…) */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab && ["bill-items", "create", "invoices", "receipts", "structures", "discounts", "arrears", "credits", "approvals"].includes(navTarget.tab)) setTab(navTarget.tab);
  }
  const canManageApprovals = canAny(session?.permissions, ["approvals.manage"]);
  const canRequestMods = canAny(session?.permissions, ["approvals.request", "approvals.manage"]);
  /* email channel (CI3 Invoice_email parity): per-row invoice mail button */
  const canEmailInvoice = canAny(session?.permissions, ["email.send"]);
  const [emailFor, setEmailFor] = useState<string | null>(null);
  const [discDialog, setDiscDialog] = useState<DiscountRow | "new" | null>(null);
  const [applyDialog, setApplyDialog] = useState<DiscountRow | null>(null);
  const [receipt, setReceipt] = useState<any | null>(null);
  const [receiptStyle, setReceiptStyle] = useState<"A5" | "THERMAL">("A5");
  const [checkedReceipts, setCheckedReceipts] = useState<Set<string>>(new Set());
  const [bulkReceipts, setBulkReceipts] = useState<any[] | null>(null);
  const [bulkBusy, setBulkBusy] = useState(false);
  /* credit wallet history — shared by the Credits tab and the PayDialog widget */
  const [creditHistoryFor, setCreditHistoryFor] = useState<{ studentId: string; name: string } | null>(null);

  // invoice + receipt filters (filter-on-demand for busy termly ledgers)
  const [invSearch, setInvSearch] = useState("");
  const [invClass, setInvClass] = useState("all");
  const [invStatus, setInvStatus] = useState("all");
  const [rcpSearch, setRcpSearch] = useState("");
  const [rcpMethod, setRcpMethod] = useState("all");
  /* CI3 bulk-invoice management: row selection + sticky bulk bar */
  const [invChecked, setInvChecked] = useState<Set<string>>(new Set());
  const [bulkSmsOpen, setBulkSmsOpen] = useState(false);
  const [bulkSmsBody, setBulkSmsBody] = useState("");

  async function sendBulkSms() {
    if (!currentSchoolId || !invChecked.size) return;
    const ids = filteredInvoices.filter((i) => invChecked.has(i.id)).map((i) => i.studentId);
    const unique = [...new Set(ids)];
    setBulkBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/ops/sms`, { action: "send", audience: `STUDENTS:${unique.join(",")}`, body: bulkSmsBody || undefined, templateId: undefined });
      toast({ title: `${res.sent} reminder${res.sent !== 1 ? "s" : ""} sent`, description: res.failed ? `${res.failed} failed — check the SMS log.` : undefined });
      setBulkSmsOpen(false); setBulkSmsBody("");
    } catch (e: any) { toast({ title: "Send failed", description: e.message, variant: "destructive" }); }
    finally { setBulkBusy(false); }
  }

  /* email one invoice to the guardian (CI3 Invoice_email::send parity) —
     the channel logs a row even when SMTP is not configured (SIMULATED) */
  async function emailInvoice(inv: FeeData["invoices"][0]) {
    if (!currentSchoolId) return;
    setEmailFor(inv.id);
    try {
      const res = await apiPost<{ message: { status: string; toAddress: string; errorReason: string | null } }>(`/api/school/${currentSchoolId}/ops/email`, { action: "send-invoice", invoiceId: inv.id });
      const st = res.message.status;
      toast({
        title: st === "SENT" ? "Invoice emailed" : st === "SIMULATED" ? "Invoice email logged" : "Invoice email failed",
        description: st === "SIMULATED" ? "SMTP is not enabled — logged in the email channel (Messaging › Email channel)." : res.message.errorReason ?? `Delivered to ${res.message.toAddress}.`,
        variant: st === "FAILED" ? "destructive" : undefined,
      });
    } catch (e: any) { toast({ title: "Email failed", description: e.message, variant: "destructive" }); }
    finally { setEmailFor(null); }
  }

  function copySelectedLinks() {
    const links = filteredInvoices.filter((i) => invChecked.has(i.id)).map((i) => `${location.origin}/invoice/${i.id}`);
    navigator.clipboard?.writeText(links.join("\n"));
    toast({ title: `${links.length} statement link${links.length !== 1 ? "s" : ""} copied`, description: "One per line — paste into WhatsApp or email." });
  }

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
          <>
            {canAny(session?.permissions, ["fees.collect", "fees.manage"]) && (
              <Button className="gap-1.5" onClick={() => setPayOpen(true)}>
                <Wallet className="size-4" /> Take payment
              </Button>
            )}
            <Select value={termId ?? ""} onValueChange={(v) => { setTermId(v); load(v); }}>
              <SelectTrigger className="w-56"><SelectValue /></SelectTrigger>
              <SelectContent>{data.terms.map((t) => <SelectItem key={t.id} value={t.id}>{t.name}{t.isCurrent ? " (current)" : ""}</SelectItem>)}</SelectContent>
            </Select>
          </>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<Banknote className="size-4" />} label="Billed" value={fmtCedi(data.stats.billed, { compact: true })} sub={`${data.stats.count} invoices`} />
        <StatCard icon={<CheckCircle2 className="size-4" />} label="Collected" value={fmtCedi(data.stats.collected, { compact: true })} tone="primary" sub={`${data.stats.paid} fully paid`} />
        <StatCard icon={<AlertTriangle className="size-4" />} label="Arrears" value={fmtCedi(data.stats.arrears, { compact: true })} tone="danger" sub={`${data.stats.unpaid + data.stats.partial} outstanding`} />
        <StatCard icon={<Receipt className="size-4" />} label="Collection rate" value={`${data.stats.billed ? Math.round((data.stats.collected / data.stats.billed) * 100) : 0}%`} tone="gold" sub={`${data.stats.partial} on payment plans`} />
      </div>

      <Tabs value={tab} onValueChange={setTab} className="mt-5">
        <TabsList className="flex-wrap">
          <TabsTrigger value="bill-items" className="gap-1.5"><ListChecks className="size-4" /> Bill items ({data.billItems.length})</TabsTrigger>
          <TabsTrigger value="create" className="gap-1.5"><ClipboardList className="size-4" /> Create invoices</TabsTrigger>
          <TabsTrigger value="invoices">Bulk manage ({data.invoices.length})</TabsTrigger>
          <TabsTrigger value="receipts">Receipts ({allPayments.length})</TabsTrigger>
          <TabsTrigger value="structures">Fee structures</TabsTrigger>
          <TabsTrigger value="discounts">Discounts ({discounts?.discounts.length ?? 0})</TabsTrigger>
          <TabsTrigger value="arrears">Arrears chase-list ({overdue.length})</TabsTrigger>
          <TabsTrigger value="credits">Credits</TabsTrigger>
          <TabsTrigger value="approvals" className="gap-1.5">
            Approvals
            {(approvals?.pending.length ?? 0) > 0 && <span className="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-destructive text-destructive-foreground text-[10px] font-bold">{approvals!.pending.length}</span>}
          </TabsTrigger>
        </TabsList>

        {/* CI3 student_payment.php tab 1 — the reusable bill-item catalog */}
        <TabsContent value="bill-items">
          <BillItemsTab billItems={data.billItems} classrooms={data.classrooms} onChanged={() => load(termId ?? undefined)} />
        </TabsContent>

        {/* CI3 tab 2 — mass invoice wizard (single / by class / all students) */}
        <TabsContent value="create">
          <CreateInvoicesTab billItems={data.billItems} roster={data.roster} terms={data.terms} termId={data.termId} onChanged={() => load(termId ?? undefined)} />
        </TabsContent>

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
            {/* CI3 sticky bulk bar — selection drives SMS/export/copy */}
            {invChecked.size > 0 && (
              <div className="sticky top-14 z-20 flex flex-wrap items-center gap-2 rounded-lg border border-blue-200 dark:border-blue-900 bg-blue-50/95 dark:bg-blue-950/80 backdrop-blur border-t-2 border-t-blue-500 px-3 py-2 text-sm shadow-sm">
                <span className="font-medium text-blue-800 dark:text-blue-200">{invChecked.size} invoice{invChecked.size > 1 ? "s" : ""} selected</span>
                <div className="ml-auto flex flex-wrap gap-2">
                  <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={copySelectedLinks}><Link2 className="size-3.5" /> Copy links</Button>
                  <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={() => setBulkSmsOpen(true)}><Smartphone className="size-3.5" /> SMS reminders</Button>
                  <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={exportInvoicesCsv}><Download className="size-3.5" /> Export</Button>
                  <Button size="sm" variant="ghost" className="h-8" onClick={() => setInvChecked(new Set())}>Clear</Button>
                </div>
              </div>
            )}
            <Card>
              <CardContent className="p-0">
                <div className="overflow-x-auto nice-scroll max-h-[560px]">
                  <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                      <tr className="text-left border-b">
                        <th className="px-3 py-2.5 w-8">
                          <Checkbox
                            checked={filteredInvoices.length > 0 && filteredInvoices.every((i) => invChecked.has(i.id))}
                            onCheckedChange={(v) => setInvChecked(v ? new Set(filteredInvoices.map((i) => i.id)) : new Set())}
                            aria-label="Select all invoices"
                          />
                        </th>
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
                      <tr key={inv.id} className={cn("border-b last:border-0 hover:bg-secondary/30", invChecked.has(inv.id) && "bg-blue-50/50 dark:bg-blue-950/20")}>
                        <td className="px-3 py-2.5">
                          <Checkbox
                            checked={invChecked.has(inv.id)}
                            onCheckedChange={(v) => setInvChecked((s) => { const n = new Set(s); if (v) n.add(inv.id); else n.delete(inv.id); return n; })}
                            aria-label={`Select ${inv.invoiceNo}`}
                          />
                        </td>
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
                            {canEmailInvoice && (
                              <Button size="sm" variant="ghost" className="h-7 gap-1" title={inv.guardianEmail ? `Email invoice to ${inv.guardianEmail}` : "No guardian email on file"} aria-label={inv.guardianEmail ? `Email invoice ${inv.invoiceNo} to guardian` : "No guardian email on file"} disabled={!inv.guardianEmail || emailFor === inv.id} onClick={() => emailInvoice(inv)}>
                                <Mail className={cn("size-3.5", emailFor === inv.id && "animate-pulse")} />
                              </Button>
                            )}
                            <Button size="sm" variant="ghost" className="h-7 gap-1" title="Apply discount" onClick={() => setDiscFor(inv)}><Percent className="size-3.5" /></Button>
                            <Button size="sm" variant="ghost" className="h-7 gap-1" title="Copy shareable parent statement link" onClick={() => copyParentLink(inv)}><Link2 className="size-3.5" /></Button>
                            <Button size="sm" variant="outline" className="h-7 gap-1" disabled={inv.balance <= 0} onClick={() => setPayFor(inv)}>
                              <Wallet className="size-3.5" /> Pay
                            </Button>
                          </div>
                        </td>
                      </tr>
                    ))}
                      {!filteredInvoices.length && <tr><td colSpan={9}><EmptyState icon={<Receipt className="size-5" />} title="No invoices match" hint="Adjust the class, status or search." /></td></tr>}
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
                            onCheckedChange={(v) => { const next = new Set(checkedReceipts); if (v) next.add(p.id); else next.delete(p.id); setCheckedReceipts(next); }}
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
          <div className="flex flex-wrap items-center justify-between gap-2 mb-4">
            <p className="text-sm text-muted-foreground">Per-class fee structures for the selected term.</p>
            {canAny(session?.permissions, ["fees.manage"]) && (
              <Button size="sm" variant="outline" className="gap-1.5" onClick={() => { setCloneSource(""); setCloneOpen(true); }}>
                <CopyPlus className="size-4" /> Clone previous term
              </Button>
            )}
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
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
              {canAny(session?.permissions, ["fees.manage"]) && (
                <Button size="sm" variant="outline" className="gap-1.5" onClick={() => { setArrearsCsv(""); setArrearsResult(null); setArrearsOpen(true); }}>
                  <FileUp className="size-4" /> Import arrears (CSV)
                </Button>
              )}
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

        <TabsContent value="credits">
          <CreditsTab onHistory={(studentId, name) => setCreditHistoryFor({ studentId, name })} />
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
        open={payOpen || !!payFor}
        invoice={payFor}
        roster={data.roster}
        invoices={data.invoices}
        onOpenChange={(o) => { if (!o) { setPayOpen(false); setPayFor(null); } }}
        onDone={(rc) => { setReceipt(rc); setPayOpen(false); setPayFor(null); load(termId ?? undefined); }}
        onCreditHistory={(studentId, name) => setCreditHistoryFor({ studentId, name })}
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
      <CreditHistoryDialog target={creditHistoryFor} onOpenChange={(o) => !o && setCreditHistoryFor(null)} />
      {bulkReceipts && <BulkReceiptsDocument receipts={bulkReceipts} onDone={() => { setBulkReceipts(null); setCheckedReceipts(new Set()); }} />}

      {/* ── clone previous term's fee structures ── */}
      <Dialog open={cloneOpen} onOpenChange={setCloneOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><CopyPlus className="size-4" /> Clone previous term bills</DialogTitle>
            <DialogDescription>
              Copies every fee structure from the term you pick into <b>{data.terms.find((t) => t.id === data.termId)?.name ?? "the current term"}</b>.
              Classes already billed this term are left untouched — nothing is overwritten.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5">
            <Label>Clone from</Label>
            <Select value={cloneSource} onValueChange={setCloneSource}>
              <SelectTrigger><SelectValue placeholder="Pick the source term" /></SelectTrigger>
              <SelectContent>
                {data.terms.filter((t) => t.id !== data.termId).map((t) => (
                  <SelectItem key={t.id} value={t.id}>{t.name}{t.isCurrent ? " (current)" : ""}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCloneOpen(false)}>Cancel</Button>
            <Button
              disabled={!cloneSource || cloneBusy}
              onClick={async () => {
                setCloneBusy(true);
                try {
                  const res = await apiPost<any>(`/api/school/${currentSchoolId}/fees`, { action: "clone-structures", sourceTermId: cloneSource });
                  toast({ title: `${res.cloned} structure${res.cloned === 1 ? "" : "s"} cloned`, description: `${res.skipped} skipped (already billed) · now billing ${res.term}` });
                  setCloneOpen(false);
                  load(termId ?? undefined);
                } catch (e: any) { toast({ title: "Clone failed", description: e.message, variant: "destructive" }); }
                finally { setCloneBusy(false); }
              }}
            >
              {cloneBusy ? "Cloning…" : "Clone structures"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── bulk arrears CSV import ── */}
      <Dialog open={arrearsOpen} onOpenChange={setArrearsOpen}>
        <DialogContent className="max-w-xl">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><FileUp className="size-4" /> Import arrears (CSV)</DialogTitle>
            <DialogDescription>
              Bring legacy balances onto this term's chase-list. One row per learner: <code className="text-xs">studentIndex,description,amount</code> —
              e.g. <code className="text-xs">NY-2012-00103,Balance from Term 2,246.00</code>. Each row becomes an overdue arrears invoice.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <div className="flex items-center gap-2">
              <Button
                size="sm" variant="outline" className="gap-1.5"
                onClick={() => downloadCsv("nyansapo-arrears-template.csv", ["studentIndex", "description", "amount"], [["NY-2012-00103", "Balance from Term 2", 246.00]])}
              >
                <Download className="size-3.5" /> Template
              </Button>
              <Input
                type="file" accept=".csv,text/csv" className="h-8 flex-1 text-xs file:mr-2 file:h-6 file:px-2 file:text-xs"
                onChange={async (e) => { const f = e.target.files?.[0]; if (f) setArrearsCsv(await f.text()); }}
              />
            </div>
            <Textarea rows={5} value={arrearsCsv} onChange={(e) => setArrearsCsv(e.target.value)} placeholder="…or paste CSV rows here" className="font-mono text-xs" />
            {arrearsResult && (
              <div className="rounded-lg border p-3 text-xs space-y-1.5 max-h-48 overflow-y-auto nice-scroll">
                <div className="font-semibold">
                  {arrearsResult.imported} imported · {arrearsResult.failed} failed · total {fmtCedi(arrearsResult.importedCents ?? 0)}
                </div>
                {(arrearsResult.results ?? []).filter((r: any) => !r.ok || r.duplicate).slice(0, 20).map((r: any, i: number) => (
                  <div key={i} className={r.ok ? "text-muted-foreground" : "text-destructive"}>
                    Line {r.line} · {r.index} — {r.duplicate ? "already imported (skipped)" : r.error}
                  </div>
                ))}
              </div>
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setArrearsOpen(false)}>Close</Button>
            <Button
              disabled={!arrearsCsv.trim() || arrearsBusy}
              onClick={async () => {
                setArrearsBusy(true);
                try {
                  const res = await apiPost<any>(`/api/school/${currentSchoolId}/fees`, { action: "import-arrears", csv: arrearsCsv });
                  setArrearsResult(res);
                  if (res.imported > 0) { toast({ title: `${res.imported} arrears invoice${res.imported > 1 ? "s" : ""} imported`, description: `${fmtCedi(res.importedCents)} added to the chase-list` }); load(termId ?? undefined); }
                  else toast({ title: "Nothing imported", description: `${res.failed} row(s) failed — see the details below.`, variant: "destructive" });
                } catch (e: any) { toast({ title: "Import failed", description: e.message, variant: "destructive" }); }
                finally { setArrearsBusy(false); }
              }}
            >
              {arrearsBusy ? "Importing…" : "Import arrears"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* CI3 bulk-invoice SMS — reminders to the guardians of selected invoices */}
      <Dialog open={bulkSmsOpen} onOpenChange={setBulkSmsOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><Smartphone className="size-5 text-primary" /> Send bill reminders</DialogTitle>
            <DialogDescription>
              SMS to the guardians of the {invChecked.size} selected invoice{invChecked.size > 1 ? "s" : ""}. Each message renders the learner&rsquo;s name and their current balance.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-2">
            <Label>Message</Label>
            <Textarea rows={3} value={bulkSmsBody} onChange={(e) => setBulkSmsBody(e.target.value)} placeholder="Dear Parent, {student}'s fee balance is {balance}. Kindly settle to avoid interruption. Thank you — {school}." />
            <p className="text-xs text-muted-foreground">{ "{student}" }, { "{balance}" } and { "{school}" } are replaced per learner. Leave empty to send the default reminder text.</p>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setBulkSmsOpen(false)}>Cancel</Button>
            <Button onClick={sendBulkSms} disabled={bulkBusy} className="gap-1.5">
              {bulkBusy ? "Sending…" : <>Send to {[...new Set(filteredInvoices.filter((i) => invChecked.has(i.id)).map((i) => i.studentId))].length} guardian{[...new Set(filteredInvoices.filter((i) => invChecked.has(i.id)).map((i) => i.studentId))].length !== 1 ? "s" : ""}</>}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

/* ── TAKE PAYMENT (CI3 modal_take_payment.php parity) ─────────────────────
   Learner search right on the modal → STUDENT'S DETAILS → open-invoice
   picker → single or SPLIT payment, with the print-receipt toggle.
   Opened blank from the module header or pre-filled from an invoice row. */
function openInvoicesFor(invoices: FeeData["invoices"], studentId: string | null) {
  const due = (d: string | null) => (d ? new Date(d).getTime() : Number.MAX_SAFE_INTEGER);
  return invoices.filter((i) => i.studentId === studentId && i.balance > 0).sort((a, b) => due(a.dueDate) - due(b.dueDate));
}

function PayDialog({ open, invoice, roster, invoices, onOpenChange, onDone, onCreditHistory }: {
  open: boolean;
  invoice: FeeData["invoices"][0] | null;
  roster: RosterRow[];
  invoices: FeeData["invoices"];
  onOpenChange: (o: boolean) => void;
  onDone: (receipt: any) => void;
  onCreditHistory?: (studentId: string, name: string) => void;
}) {
  const { currentSchoolId, online } = useStore();
  const [studentId, setStudentId] = useState<string | null>(null);
  const [selectedInvoiceId, setSelectedInvoiceId] = useState<string | null>(null);
  const [search, setSearch] = useState("");
  const [query, setQuery] = useState(""); // debounced 300ms (CI3 parity)
  const [showResults, setShowResults] = useState(false);
  const [amount, setAmount] = useState("");
  const [method, setMethod] = useState("MOMO_MTN"); // "SPLIT" = two methods
  const [receiptNo, setReceiptNo] = useState("");
  const [txnId, setTxnId] = useState("");
  const [bankName, setBankName] = useState("");
  const [chequeNo, setChequeNo] = useState("");
  const [splitMethodA, setSplitMethodA] = useState("CASH");
  const [splitAmountA, setSplitAmountA] = useState("");
  const [splitMethodB, setSplitMethodB] = useState("MOMO_MTN");
  const [splitAmountB, setSplitAmountB] = useState("");
  const [printReceipt, setPrintReceipt] = useState(true);
  const [busy, setBusy] = useState(false);
  const [creditBal, setCreditBal] = useState(0);
  const searchWrapRef = useRef<HTMLDivElement>(null);

  /* reset everything whenever the modal closes (CI3 reloads it fresh) */
  useEffect(() => {
    if (open) return;
    setStudentId(null); setSelectedInvoiceId(null); setSearch(""); setQuery(""); setShowResults(false);
    setAmount(""); setMethod("MOMO_MTN"); setReceiptNo(""); setTxnId(""); setBankName(""); setChequeNo("");
    setSplitMethodA("CASH"); setSplitAmountA(""); setSplitMethodB("MOMO_MTN"); setSplitAmountB("");
    setPrintReceipt(true); setBusy(false);
  }, [open]);

  /* preselected path: opened from an invoice row — populate that learner */
  useEffect(() => {
    if (!open || !invoice) return;
    setStudentId(invoice.studentId);
    setSelectedInvoiceId(invoice.id);
    setSearch(invoice.student.toUpperCase());
    setAmount((invoice.balance / 100).toFixed(2));
  }, [open, invoice]);

  /* debounced learner search — 300ms, min 2 chars, over the roster */
  useEffect(() => {
    const t = setTimeout(() => setQuery(search), 300);
    return () => clearTimeout(t);
  }, [search]);
  const owesById = useMemo(() => {
    const m = new Map<string, number>();
    invoices.forEach((i) => { if (i.balance > 0) m.set(i.studentId, (m.get(i.studentId) ?? 0) + i.balance); });
    return m;
  }, [invoices]);
  const results = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (q.length < 2) return [];
    return roster
      .filter((s) => `${s.name} ${s.index} ${s.classroom}`.toLowerCase().includes(q))
      .sort((a, b) => (owesById.get(b.id) ?? 0) - (owesById.get(a.id) ?? 0) || a.name.localeCompare(b.name))
      .slice(0, 50);
  }, [query, roster, owesById]);
  const searching = search.trim() !== query;

  /* click-outside closes the results dropdown; re-focusing the input reopens it */
  useEffect(() => {
    if (!open) return;
    const onDocDown = (e: MouseEvent) => {
      if (searchWrapRef.current && !searchWrapRef.current.contains(e.target as Node)) setShowResults(false);
    };
    document.addEventListener("mousedown", onDocDown);
    return () => document.removeEventListener("mousedown", onDocDown);
  }, [open]);

  const studentInvoices = useMemo(() => openInvoicesFor(invoices, studentId), [invoices, studentId]);
  const selectedInvoice = studentInvoices.find((i) => i.id === selectedInvoiceId) ?? null;
  const student = roster.find((s) => s.id === studentId) ?? null;
  const studentName = student?.name ?? invoices.find((i) => i.studentId === studentId)?.student ?? null;
  const studentClass = student?.classroom ?? invoices.find((i) => i.studentId === studentId)?.classroom ?? null;
  const totalPayable = studentId ? owesById.get(studentId) ?? 0 : 0;

  /* prepaid credit the learner already holds (overpayments banked earlier) */
  useEffect(() => {
    setCreditBal(0);
    if (!studentId || !currentSchoolId) return;
    apiGet<{ balanceCents: number }>(`/api/school/${currentSchoolId}/ops/finance?action=credit-history&studentId=${studentId}`)
      .then((d) => setCreditBal(d.balanceCents ?? 0))
      .catch(() => setCreditBal(0));
  }, [studentId, currentSchoolId]);

  function pickStudent(s: RosterRow) {
    setStudentId(s.id);
    setSearch(s.name.toUpperCase());
    setQuery(s.name.toUpperCase());
    setShowResults(false);
    // default-select the OLDEST open invoice (CI3 arrears-first settlement)
    const oldest = openInvoicesFor(invoices, s.id)[0];
    if (oldest) { setSelectedInvoiceId(oldest.id); setAmount((oldest.balance / 100).toFixed(2)); }
    else { setSelectedInvoiceId(null); setAmount(""); }
  }
  function pickInvoice(inv: FeeData["invoices"][0]) {
    setSelectedInvoiceId(inv.id);
    setAmount((inv.balance / 100).toFixed(2));
  }

  const amountCents = Math.round((parseFloat(amount || "0") || 0) * 100);
  const splitACents = Math.round((parseFloat(splitAmountA || "0") || 0) * 100);
  const splitBCents = Math.round((parseFloat(splitAmountB || "0") || 0) * 100);
  const splitSumCents = splitACents + splitBCents;
  const splitBalanced = splitACents > 0 && splitBCents > 0 && splitSumCents === amountCents;
  const chequeNeeded = method === "CHEQUE" || (method === "SPLIT" && (splitMethodA === "CHEQUE" || splitMethodB === "CHEQUE"));
  const chequeMissing = chequeNeeded && (!bankName.trim() || !chequeNo.trim());
  const canSubmit = !!selectedInvoice && !busy && amountCents > 0 && (method !== "SPLIT" || splitBalanced) && !chequeMissing;

  /* one record-payment call — offline it enqueues on the sync queue */
  async function recordOne(payMethod: string, cents: number): Promise<{ queued: boolean; receipt: any; credit: number }> {
    const ref = payMethod.startsWith("MOMO") ? txnId.trim() || undefined : receiptNo.trim() || undefined;
    const bank = payMethod === "CHEQUE" ? bankName.trim() || undefined : undefined;
    const chq = payMethod === "CHEQUE" ? chequeNo.trim() || undefined : undefined;
    const payload = { action: "record-payment", invoiceId: selectedInvoice!.id, amountCents: cents, method: payMethod, reference: ref, bankName: bank, chequeNo: chq };
    const res = await apiPostQueueable<any>(
      `/api/school/${currentSchoolId}/fees`, "payment", payload,
      `Payment ${fmtCedi(cents)} — ${studentName ?? "learner"}`,
      (p, clientId) => ({ ...p, schoolId: currentSchoolId, amountCents: cents, method: payMethod, reference: ref, bankName: bank, chequeNo: chq, paidAt: new Date().toISOString(), invoiceId: selectedInvoice!.id, payloadClientId: clientId, clientId })
    );
    if (res.queued) return { queued: true, receipt: null, credit: 0 };
    return { queued: false, receipt: res.data?.receipt ?? res.data?.payment ?? null, credit: res.data?.creditCreated ?? 0 };
  }

  async function submit() {
    if (!selectedInvoice || !currentSchoolId || !canSubmit) return;
    setBusy(true);
    try {
      if (method === "SPLIT") {
        const legs: [string, number][] = [[splitMethodA, splitACents], [splitMethodB, splitBCents]];
        let queued = 0, ok = 0, okCents = 0, credit = 0;
        let lastReceipt: any = null;
        const errors: string[] = [];
        for (const [m, c] of legs) {
          try {
            const r = await recordOne(m, c);
            if (r.queued) queued++;
            else { ok++; okCents += c; if (r.receipt) lastReceipt = r.receipt; credit += r.credit; }
          } catch (e: any) { errors.push(`${PAYMENT_LABELS[m] ?? m}: ${e.message}`); }
        }
        if (queued === 2) {
          toast({ title: "Queued offline", description: "Both legs of the split are queued — receipts will be issued automatically when you reconnect." });
          onDone(null);
        } else if (ok === 2) {
          toast({ title: "Split payment recorded", description: `${fmtCedi(amountCents)} collected in two receipts.` });
          if (credit > 0) toast({ title: "Payment received", description: `Overpayment of ${fmtCedi(credit)} banked as prepaid credit — it will be applied to future bills.` });
          onDone(printReceipt ? lastReceipt : null);
        } else if (ok + queued === 2) {
          toast({ title: "Split payment recorded", description: `One receipt issued now and one leg queued to sync when you reconnect${credit ? ` · ${fmtCedi(credit)} banked as prepaid credit` : ""}.` });
          onDone(printReceipt ? lastReceipt : null);
        } else if (ok + queued === 1) {
          toast({ title: "Partial payment recorded", description: `Only one leg of the split succeeded — ${fmtCedi(okCents)} received${errors.length ? `; failed leg: ${errors.join(" · ")}` : ""}. Re-take the failed part if it is still owed.`, variant: "destructive" });
          onDone(printReceipt ? lastReceipt : null);
        } else {
          toast({ title: "Payment failed", description: errors.join(" · ") || "Both legs failed.", variant: "destructive" });
        }
      } else {
        const r = await recordOne(method, amountCents);
        if (r.queued) {
          toast({ title: "Queued offline", description: "Receipt will be issued automatically when you reconnect." });
          onDone(null);
        } else {
          if (r.credit > 0) toast({ title: "Payment received", description: `Overpayment of ${fmtCedi(r.credit)} banked as prepaid credit — it will be applied to future bills.` });
          onDone(printReceipt ? r.receipt : null);
        }
      }
    } catch (e: any) {
      toast({ title: "Payment failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  const resultsOpen = showResults && query.trim().length >= 2;

  function splitRow(label: string, m: string, setM: (v: string) => void, amt: string, setAmt: (v: string) => void) {
    return (
      <div className="grid grid-cols-2 gap-2">
        <div className="space-y-1">
          <Label className="text-[11px] font-semibold uppercase tracking-wide">{label} — method</Label>
          <Select value={m} onValueChange={setM} disabled={!selectedInvoice}>
            <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
            <SelectContent>
              {Object.entries(PAYMENT_LABELS).map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}
            </SelectContent>
          </Select>
        </div>
        <div className="space-y-1">
          <Label className="text-[11px] font-semibold uppercase tracking-wide">{label} — amount (₵)</Label>
          <Input type="number" step="0.01" min="0" value={amt} onChange={(e) => setAmt(e.target.value)} className="h-9" disabled={!selectedInvoice} />
        </div>
      </div>
    );
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg max-h-[92vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Receipt className="size-4" /> Take payment</DialogTitle>
          <DialogDescription>
            {selectedInvoice
              ? <>{selectedInvoice.invoiceNo} · balance <b className="text-destructive">{fmtCedi(selectedInvoice.balance)}</b></>
              : "Search for a learner, pick an open invoice, then record the payment."}
            {!online && " · offline, will queue & sync"}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          {/* SELECT STUDENT (CI3) */}
          <section className="space-y-2">
            <div className="font-mono text-xs font-bold uppercase tracking-wider text-muted-foreground border-b pb-1.5">Select student</div>
            <div className="relative" ref={searchWrapRef}>
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none" />
              <Input
                value={search} autoComplete="off"
                onChange={(e) => { setSearch(e.target.value); setShowResults(true); }}
                onFocus={() => { if (query.trim().length >= 2) setShowResults(true); }}
                placeholder="Type student name or code to search…"
                className="h-12 pl-10 text-base"
              />
              {resultsOpen && (
                <div className="absolute top-full inset-x-0 z-50 mt-1 max-h-96 overflow-y-auto nice-scroll rounded-lg border bg-popover shadow-lg">
                  {searching ? (
                    <div className="p-4 text-center text-sm text-muted-foreground">Searching…</div>
                  ) : results.length ? (
                    results.map((s) => {
                      const owes = owesById.get(s.id) ?? 0;
                      return (
                        <button key={s.id} type="button" onClick={() => pickStudent(s)}
                          className="flex w-full items-center justify-between gap-3 border-b last:border-0 px-4 py-3 text-left transition-colors hover:bg-secondary/50">
                          <div className="min-w-0">
                            <div className="truncate text-sm font-semibold uppercase">{s.name}</div>
                            <div className="mt-0.5 flex items-center gap-3 text-xs text-muted-foreground">
                              <span className="flex min-w-0 items-center gap-1"><UserRound className="size-3 shrink-0" />{s.index}</span>
                              <span className="flex min-w-0 items-center gap-1"><GraduationCap className="size-3 shrink-0" />{s.classroom}</span>
                            </div>
                          </div>
                          <div className="shrink-0 text-right">
                            <div className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">Owes</div>
                            <div className={cn("text-sm font-bold tabular-nums", owes > 0 ? "text-destructive" : "text-muted-foreground")}>{fmtCedi(owes)}</div>
                          </div>
                        </button>
                      );
                    })
                  ) : (
                    <div className="p-4 text-center text-sm text-muted-foreground">No learners match “{query.trim()}”.</div>
                  )}
                </div>
              )}
            </div>
          </section>

          {/* STUDENT'S DETAILS (CI3) */}
          <section className="space-y-2">
            <div className="font-mono text-xs font-bold uppercase tracking-wider text-muted-foreground border-b pb-1.5">Student&rsquo;s details</div>
            <div className="grid grid-cols-2 gap-x-4 gap-y-1.5">
              <div className="text-sm font-semibold uppercase text-muted-foreground">Name:</div>
              <div className="text-sm font-semibold uppercase truncate">{studentName ?? "No student selected"}</div>
              <div className="text-sm font-semibold uppercase text-muted-foreground">Class:</div>
              <div className="text-sm font-semibold uppercase truncate">{studentClass ?? "No class assigned"}</div>
              <div className="text-sm font-semibold uppercase text-muted-foreground">Total payable:</div>
              <div className="text-sm font-semibold uppercase tabular-nums">{studentId ? fmtCedi(totalPayable) : "—"}</div>
            </div>
            {creditBal > 0 && (
              <div className="rounded-lg border border-emerald-500/50 bg-emerald-50 dark:bg-emerald-950/40 px-3 py-2 text-xs text-emerald-700 dark:text-emerald-300 flex flex-wrap items-center justify-between gap-2">
                <span className="flex items-center gap-1.5 font-medium">
                  <Wallet className="size-3.5" />
                  Prepaid credit available: <b>{fmtCedi(creditBal)}</b> — applied automatically to the next generated bill.
                </span>
                {onCreditHistory && studentId && (
                  <button type="button" className="underline underline-offset-2 shrink-0 font-medium" onClick={() => onCreditHistory(studentId, studentName ?? "")}>
                    View history
                  </button>
                )}
              </div>
            )}
          </section>

          {/* OPEN INVOICES — pick the payment target (CI3 modal_take_payment2) */}
          {studentId && (
            <section className="space-y-2">
              <div className="font-mono text-xs font-bold uppercase tracking-wider text-muted-foreground border-b pb-1.5">Select invoice</div>
              {studentInvoices.length === 0 && (
                <p className="rounded-lg bg-secondary/60 px-3 py-2 text-xs text-muted-foreground">No outstanding fees for this learner — nothing to collect.</p>
              )}
              <div className="space-y-1.5">
                {studentInvoices.map((inv) => {
                  const active = inv.id === selectedInvoiceId;
                  return (
                    <button key={inv.id} type="button" onClick={() => pickInvoice(inv)}
                      className={cn("w-full rounded-lg border px-3 py-2 text-left transition-colors", active ? "border-primary bg-primary/5" : "hover:bg-secondary/40")}>
                      <div className="flex items-center justify-between gap-2">
                        <div className="flex min-w-0 items-center gap-2">
                          <span className={cn("size-2.5 shrink-0 rounded-full border-2", active ? "border-primary bg-primary" : "border-muted-foreground/40")} aria-hidden />
                          <span className="font-mono text-xs font-medium truncate">{inv.invoiceNo}</span>
                        </div>
                        <span className="shrink-0 text-sm font-bold tabular-nums text-destructive">{fmtCedi(inv.balance)}</span>
                      </div>
                      <div className="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 pl-[1.15rem] text-[11px] text-muted-foreground">
                        <span>Total <span className="tabular-nums">{fmtCedi(inv.total)}</span></span>
                        <span>Paid <span className="tabular-nums text-emerald-600">{fmtCedi(inv.paid)}</span></span>
                        <span>Due {fmtDate(inv.dueDate)}</span>
                      </div>
                    </button>
                  );
                })}
              </div>
            </section>
          )}

          {/* PAYMENT FORM (CI3 field order) */}
          <section className="space-y-3 border-t pt-3">
            <div className="grid gap-3 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label className="text-xs font-bold uppercase tracking-wide">Payment mode</Label>
                <Select value={method} onValueChange={setMethod} disabled={!selectedInvoice}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="SPLIT">Split payment (two methods)</SelectItem>
                    {Object.entries(PAYMENT_LABELS).map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5">
                <Label className="text-xs font-bold uppercase tracking-wide">Amount (₵) *</Label>
                <Input type="number" step="0.01" min="0" placeholder="600" value={amount} onChange={(e) => setAmount(e.target.value)} disabled={!selectedInvoice} />
                <p className="text-[11px] text-muted-foreground">Amounts above the balance are banked as prepaid credit.</p>
              </div>
              <div className="space-y-1.5 sm:col-span-2">
                <Label className="text-xs font-bold uppercase tracking-wide">Receipt no.</Label>
                <Input value={receiptNo} onChange={(e) => setReceiptNo(e.target.value)} placeholder="4012252" className="tracking-wider" disabled={!selectedInvoice} />
                <p className="text-[11px] text-muted-foreground">Optional — printed on the receipt and the audit trail.</p>
              </div>
            </div>

            {/* conditional per-method fields (CI3 showMthodDetails) */}
            {method !== "SPLIT" && method.startsWith("MOMO") && (
              <div className="space-y-1.5">
                <Label className="text-xs font-bold uppercase tracking-wide">Transaction ID <span className="font-normal normal-case text-muted-foreground">(optional — auto-generated if empty)</span></Label>
                <Input value={txnId} onChange={(e) => setTxnId(e.target.value)} placeholder="Leave empty to auto-generate" className="tracking-wider" disabled={!selectedInvoice} />
                <p className="text-[11px] text-muted-foreground">The parent sent money from their own wallet to the school — copy the transaction ID from their wallet SMS.</p>
              </div>
            )}
            {chequeNeeded && (
              <div className="grid gap-3 sm:grid-cols-2">
                <div className="space-y-1.5">
                  <Label className="text-xs font-bold uppercase tracking-wide">Bank name *</Label>
                  <Input value={bankName} onChange={(e) => setBankName(e.target.value)} placeholder="e.g. Ecobank" disabled={!selectedInvoice} />
                </div>
                <div className="space-y-1.5">
                  <Label className="text-xs font-bold uppercase tracking-wide">Cheque no. *</Label>
                  <Input value={chequeNo} onChange={(e) => setChequeNo(e.target.value)} placeholder="0011234567812345" className="tracking-wider" disabled={!selectedInvoice} />
                </div>
                {chequeMissing && <p className="sm:col-span-2 text-[11px] text-destructive">Bank name and cheque number are required for cheque payments.</p>}
              </div>
            )}
            {method === "CASH" && (
              <p className="text-[11px] text-muted-foreground rounded-lg bg-secondary/60 px-3 py-2">
                Cash at the counter — no transaction reference needed. Online checkouts and MoMo prompts are issued from the MoMo &amp; Links module.
              </p>
            )}

            {/* SPLIT — two methods, amounts must add up */}
            {method === "SPLIT" && (
              <div className="space-y-3 rounded-lg border bg-secondary/20 p-3">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <div className="font-mono text-xs font-bold uppercase tracking-wider text-muted-foreground">Split — two methods</div>
                  <div className={cn("text-xs font-semibold tabular-nums", amountCents > 0 && splitBalanced ? "text-emerald-600" : "text-destructive")}>
                    Total: {fmtCedi(splitSumCents)}{amountCents > 0 && !splitBalanced ? ` of ${fmtCedi(amountCents)}` : ""}
                  </div>
                </div>
                {splitRow("First", splitMethodA, setSplitMethodA, splitAmountA, setSplitAmountA)}
                {splitRow("Second", splitMethodB, setSplitMethodB, splitAmountB, setSplitAmountB)}
                <p className="text-[11px] text-muted-foreground">Both amounts must be above zero and together equal the payment amount above.</p>
              </div>
            )}
          </section>
        </div>

        <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-3">
          <div className="flex items-center gap-2">
            <Label htmlFor="pay-print-receipt" className="text-xs font-bold uppercase tracking-wide text-muted-foreground cursor-pointer">Print receipt?</Label>
            <Switch id="pay-print-receipt" checked={printReceipt} onCheckedChange={setPrintReceipt} aria-label="Print receipt" />
          </div>
          <div className="flex gap-2">
            <Button variant="outline" onClick={() => onOpenChange(false)} disabled={busy}>Cancel</Button>
            <Button onClick={submit} disabled={!canSubmit} className="gap-1.5">{busy ? "Recording…" : <><Receipt className="size-4" /> Take payment</>}</Button>
          </div>
        </div>
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

      <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
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
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-1.5 sm:col-span-2"><Label>Name *</Label><Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Sibling discount (2+ learners)" /></div>
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
          <div className="space-y-1.5 sm:col-span-2"><Label>Notes</Label><Input value={notes} onChange={(e) => setNotes(e.target.value)} placeholder="Policy or eligibility note" /></div>
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
            <div className="grid gap-3 sm:grid-cols-2">
              <div className="space-y-1.5">
                <Label>Type</Label>
                <Select value={type} onValueChange={setType}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent><SelectItem value="PERCENT">Percentage</SelectItem><SelectItem value="FIXED">Fixed (₵)</SelectItem></SelectContent>
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
          <div className="grid gap-3 sm:grid-cols-2">
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
              <div className="grid gap-3 sm:grid-cols-2">
                <div className="space-y-1.5">
                  <Label>Corrected amount (₵)</Label>
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
                <Label>Corrected invoice total (₵)</Label>
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


/* ── CREDITS TAB (prepaid credit wallets from overpayment — CI3 parity) ─── */
function CreditsTab({ onHistory }: { onHistory: (studentId: string, name: string) => void }) {
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<CreditsData | null>(null);
  const [loadedFor, setLoadedFor] = useState<string | null>(null);
  const [addOpen, setAddOpen] = useState(false);
  const [adjustFor, setAdjustFor] = useState<CreditRow | null>(null);
  const [transferFor, setTransferFor] = useState<CreditRow | null>(null);
  const [dailyFor, setDailyFor] = useState<CreditRow | null>(null);
  const [search, setSearch] = useState("");

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<CreditsData>(`/api/school/${currentSchoolId}/ops/finance?action=credits`)
      .then((d) => { setData(d); setLoadedFor(currentSchoolId); })
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(() => { load(); }, [currentSchoolId]);

  const rows = (data?.rows ?? []).filter((r) => {
    if (!search.trim()) return true;
    return `${r.name} ${r.classroom ?? ""}`.toLowerCase().includes(search.trim().toLowerCase());
  });

  if (!data || loadedFor !== currentSchoolId) return <Card><CardContent><LoadingBlock label="Loading credit wallets…" /></CardContent></Card>;

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <p className="text-sm text-muted-foreground max-w-2xl">
          Prepaid credit wallets — overpayments banked at the counter are held per learner, auto-applied to the next term&apos;s bill,
          transferable between siblings, or convertible to the daily-services wallet.
        </p>
        <Button size="sm" className="gap-1.5" onClick={() => setAddOpen(true)}><Plus className="size-4" /> Add credit</Button>
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<PiggyBank className="size-4" />} label="Active credits" value={fmtCedi(data.stats.activeCents, { compact: true })} tone="primary" sub="held by learners now" />
        <StatCard icon={<Wallet className="size-4" />} label="Learners with credit" value={data.stats.studentsWithCredits} sub="holding a balance" />
        <StatCard icon={<CheckCircle2 className="size-4" />} label="Credits applied" value={fmtCedi(data.stats.appliedCents, { compact: true })} tone="gold" sub="to bills & daily wallets" />
        <StatCard icon={<Percent className="size-4" />} label="Utilization rate" value={`${data.stats.utilizationPct}%`} sub={`of ${fmtCedi(data.stats.createdCents, { compact: true })} created`} />
      </div>

      <Card>
        <CardHeader className="pb-3 flex-row items-center justify-between space-y-0 flex-wrap gap-2">
          <CardTitle className="text-base flex items-center gap-2"><PiggyBank className="size-4 text-primary" /> Credit wallets — {rows.length} learner{rows.length === 1 ? "" : "s"}</CardTitle>
          <FilterSearch value={search} onChange={setSearch} placeholder="Learner, class…" />
        </CardHeader>
        <CardContent className="p-0">
          <div className="overflow-x-auto nice-scroll max-h-[520px]">
            <table className="w-full text-sm">
              <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                <tr className="text-left border-b">
                  <th className="px-3 py-2.5 font-medium">Learner</th>
                  <th className="px-3 py-2.5 font-medium hidden md:table-cell">Class</th>
                  <th className="px-3 py-2.5 font-medium text-right">Credit balance</th>
                  <th className="px-3 py-2.5 font-medium text-right hidden sm:table-cell">Records</th>
                  <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Last credit</th>
                  <th className="px-3 py-2.5 text-right"></th>
                </tr>
              </thead>
              <tbody>
                {rows.map((r) => (
                  <tr key={r.studentId} className="border-b last:border-0 hover:bg-secondary/30">
                    <td className="px-3 py-2.5 font-medium">{r.name}</td>
                    <td className="px-3 py-2.5 hidden md:table-cell text-muted-foreground">{r.classroom ?? "—"}</td>
                    <td className="px-3 py-2.5 text-right tabular-nums font-semibold text-emerald-600">{fmtCedi(r.balanceCents)}</td>
                    <td className="px-3 py-2.5 text-right hidden sm:table-cell tabular-nums text-muted-foreground">{r.records}</td>
                    <td className="px-3 py-2.5 hidden lg:table-cell text-xs text-muted-foreground">{r.lastAt ? fmtDate(r.lastAt) : "—"}</td>
                    <td className="px-3 py-2.5 text-right">
                      <div className="flex gap-1 justify-end">
                        <Button size="sm" variant="ghost" className="h-7 gap-1" title="Credit history" onClick={() => onHistory(r.studentId, r.name)}><History className="size-3.5" /> History</Button>
                        <Button size="sm" variant="ghost" className="h-7 gap-1" title="Adjust the balance (±)" onClick={() => setAdjustFor(r)}><PencilLine className="size-3.5" /> Adjust</Button>
                        <Button size="sm" variant="ghost" className="h-7 gap-1" title="Transfer credit to another learner" disabled={r.balanceCents <= 0} onClick={() => setTransferFor(r)}><ArrowRightLeft className="size-3.5" /> Transfer</Button>
                        <Button size="sm" variant="ghost" className="h-7 gap-1" title="Send credit to the daily-services prepaid wallet" disabled={r.balanceCents <= 0} onClick={() => setDailyFor(r)}><HandCoins className="size-3.5" /> Daily wallet</Button>
                      </div>
                    </td>
                  </tr>
                ))}
                {!rows.length && <tr><td colSpan={6}><EmptyState icon={<PiggyBank className="size-5" />} title="No credit wallets yet" hint="Overpayments at the counter are banked here — or add a manual credit for a learner." /></td></tr>}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <AddCreditDialog open={addOpen} students={data.students} onOpenChange={(o) => !o && setAddOpen(false)} onDone={() => { setAddOpen(false); load(); }} />
      <AdjustCreditDialog row={adjustFor} onOpenChange={(o) => !o && setAdjustFor(null)} onDone={() => { setAdjustFor(null); load(); }} />
      <TransferCreditDialog row={transferFor} students={data.students} onOpenChange={(o) => !o && setTransferFor(null)} onDone={() => { setTransferFor(null); load(); }} />
      <ToDailyWalletDialog row={dailyFor} onOpenChange={(o) => !o && setDailyFor(null)} onDone={() => { setDailyFor(null); load(); }} />
    </div>
  );
}

/* credit ledger for one learner — shared by the Credits tab and the PayDialog widget */
function CreditHistoryDialog({ target, onOpenChange }: { target: { studentId: string; name: string } | null; onOpenChange: (o: boolean) => void }) {
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<{ balanceCents: number; rows: CreditLedgerRow[] } | null>(null);
  const [loadedFor, setLoadedFor] = useState<string | null>(null);
  const key = target?.studentId ?? null;

  useEffect(() => {
    if (!key || !currentSchoolId) return;
    apiGet<{ balanceCents: number; rows: CreditLedgerRow[] }>(`/api/school/${currentSchoolId}/ops/finance?action=credit-history&studentId=${key}`)
      .then((d) => { setData(d); setLoadedFor(key); })
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  }, [key, currentSchoolId]);
  const ready = !!data && loadedFor === key;

  return (
    <Dialog open={!!target} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><PiggyBank className="size-4 text-primary" /> Credit history — {target?.name}</DialogTitle>
          <DialogDescription>
            {ready ? <>Current prepaid credit: <b className="text-emerald-600">{fmtCedi(data!.balanceCents)}</b> · applied automatically to the next generated bill.</> : "Loading…"}
          </DialogDescription>
        </DialogHeader>
        {!ready ? <LoadingBlock label="Loading ledger…" /> : (
          <div className="overflow-x-auto nice-scroll max-h-[420px]">
            <table className="w-full text-sm">
              <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                <tr className="text-left border-b">
                  <th className="px-2 py-2 font-medium">Date</th>
                  <th className="px-2 py-2 font-medium">Kind</th>
                  <th className="px-2 py-2 font-medium text-right">Amount</th>
                  <th className="px-2 py-2 font-medium text-right">Balance after</th>
                  <th className="px-2 py-2 font-medium hidden sm:table-cell">Note</th>
                  <th className="px-2 py-2 font-medium hidden md:table-cell">By</th>
                </tr>
              </thead>
              <tbody>
                {data.rows.map((r) => (
                  <tr key={r.id} className="border-b last:border-0 hover:bg-secondary/30">
                    <td className="px-2 py-2 text-xs text-muted-foreground whitespace-nowrap">{fmtDate(r.at)}</td>
                    <td className="px-2 py-2">
                      <Badge variant={r.amountCents >= 0 ? "default" : "secondary"} className="text-[10px]">
                        {CREDIT_KIND_LABELS[r.kind] ?? r.kind}
                      </Badge>
                      {r.invoiceNo && <div className="font-mono text-[10px] text-muted-foreground mt-0.5">{r.invoiceNo}</div>}
                    </td>
                    <td className={`px-2 py-2 text-right tabular-nums font-semibold ${r.amountCents >= 0 ? "text-emerald-600" : "text-destructive"}`}>
                      {r.amountCents >= 0 ? "+" : "−"}{fmtCedi(Math.abs(r.amountCents))}
                    </td>
                    <td className="px-2 py-2 text-right tabular-nums">{fmtCedi(r.balanceAfter)}</td>
                    <td className="px-2 py-2 hidden sm:table-cell text-xs text-muted-foreground max-w-56 truncate" title={r.note ?? undefined}>{r.note ?? "—"}</td>
                    <td className="px-2 py-2 hidden md:table-cell text-xs text-muted-foreground">{r.byName ?? "—"}</td>
                  </tr>
                ))}
                {!data.rows.length && <tr><td colSpan={6}><EmptyState title="No credit activity yet" /></td></tr>}
              </tbody>
            </table>
          </div>
        )}
        <DialogFooter>
          <Button variant="outline" size="sm" onClick={() => onOpenChange(false)}>Close</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* searchable learner picker (shared by add-credit and transfer dialogs) */
function LearnerPicker({ students, selected, onSelect, exclude }: {
  students: { id: string; name: string; classroom: string }[];
  selected: string;
  onSelect: (id: string) => void;
  exclude?: string;
}) {
  const [search, setSearch] = useState("");
  const list = students.filter((s) => s.id !== exclude && (!search.trim() || `${s.name} ${s.classroom}`.toLowerCase().includes(search.trim().toLowerCase())));
  return (
    <div className="space-y-1.5">
      <Input placeholder="Search learner…" value={search} onChange={(e) => setSearch(e.target.value)} />
      <div className="border rounded-lg max-h-44 overflow-y-auto nice-scroll">
        {list.slice(0, 40).map((s) => (
          <button key={s.id} type="button" onClick={() => onSelect(s.id)}
            className={`w-full flex items-center justify-between px-3 py-2 border-b last:border-0 text-sm text-left ${selected === s.id ? "bg-primary text-primary-foreground" : "hover:bg-secondary/50"}`}>
            <span>{s.name}</span><span className="text-xs opacity-70">{s.classroom}</span>
          </button>
        ))}
        {!list.length && <div className="px-3 py-3 text-xs text-muted-foreground">No learners match.</div>}
      </div>
    </div>
  );
}

/* manual credit (e.g. bursar banks a deposit before billing) */
function AddCreditDialog({ open, students, onOpenChange, onDone }: {
  open: boolean;
  students: { id: string; name: string; classroom: string }[];
  onOpenChange: (o: boolean) => void;
  onDone: () => void;
}) {
  const { currentSchoolId } = useStore();
  const [studentId, setStudentId] = useState("");
  const [amount, setAmount] = useState("");
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => { if (open) { setStudentId(""); setAmount(""); setNote(""); } }, [open]);

  async function submit() {
    const cents = Math.round(parseFloat(amount || "0") * 100);
    if (!studentId) { toast({ title: "Choose a learner" }); return; }
    if (!cents || cents <= 0) { toast({ title: "Enter a credit amount above zero" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/finance`, { action: "credit-create", studentId, amountCents: cents, note: note || undefined });
      toast({ title: "Credit added", description: `${fmtCedi(cents)} banked as prepaid credit.` });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Plus className="size-4 text-primary" /> Add prepaid credit</DialogTitle>
          <DialogDescription>Manually bank credit for a learner — applied automatically when their next invoice is generated.</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <LearnerPicker students={students} selected={studentId} onSelect={setStudentId} />
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Amount (₵) *</Label>
              <Input type="number" step="0.01" value={amount} onChange={(e) => setAmount(e.target.value)} placeholder="e.g. 50.00" />
            </div>
            <div className="space-y-1.5">
              <Label>Note</Label>
              <Input value={note} onChange={(e) => setNote(e.target.value)} placeholder="e.g. Deposit for next term" />
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || !studentId}>{busy ? "Saving…" : "Add credit"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ± correction with a mandatory audit note */
function AdjustCreditDialog({ row, onOpenChange, onDone }: { row: CreditRow | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [amount, setAmount] = useState("");
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => { if (row) { setAmount(""); setNote(""); } }, [row]);
  const cents = Math.round(parseFloat(amount || "0") * 100);
  const resulting = (row?.balanceCents ?? 0) + cents;

  async function submit() {
    if (!row) return;
    if (!cents) { toast({ title: "Enter a non-zero amount (use − to reduce)" }); return; }
    if (!note.trim()) { toast({ title: "A note is required for adjustments" }); return; }
    if (resulting < 0) { toast({ title: "Adjustment refused", description: "It would overdraw the credit wallet." }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/finance`, { action: "credit-adjust", studentId: row.studentId, amountCents: cents, note: note.trim() });
      toast({ title: "Credit adjusted", description: `${row.name}'s balance is now ${fmtCedi(resulting)}.` });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!row} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><PencilLine className="size-4" /> Adjust credit — {row?.name}</DialogTitle>
          <DialogDescription>Current balance <b className="text-emerald-600">{row ? fmtCedi(row.balanceCents) : ""}</b>. Use a negative amount to reduce.</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Amount (± ₵) *</Label>
              <Input type="number" step="0.01" value={amount} onChange={(e) => setAmount(e.target.value)} placeholder="e.g. 5 or -5" />
              <div className="flex gap-1.5">
                <Button type="button" size="sm" variant="outline" className="h-7 text-xs" onClick={() => setAmount((v) => String(parseFloat(v || "0") - 5))}>−5</Button>
                <Button type="button" size="sm" variant="outline" className="h-7 text-xs" onClick={() => setAmount("5")}>+5</Button>
                <Button type="button" size="sm" variant="outline" className="h-7 text-xs" onClick={() => setAmount((v) => String(parseFloat(v || "0") + 5))}>+5 more</Button>
              </div>
            </div>
            <div className="space-y-1.5">
              <Label>Resulting balance</Label>
              <div className={`rounded-lg border px-3 py-2 text-sm font-semibold tabular-nums ${resulting < 0 ? "border-destructive text-destructive" : "border-emerald-500/50 text-emerald-600"}`}>
                {fmtCedi(resulting)}
              </div>
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Reason / note *</Label>
            <Input value={note} onChange={(e) => setNote(e.target.value)} placeholder="e.g. Guardian paid ₵5 extra at the bank — slip attached" />
          </div>
          <p className="text-xs text-muted-foreground">Adjustments are permanent ledger entries (kind ADJUST) with your name attached for the audit trail.</p>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || !cents || !note.trim() || resulting < 0}>{busy ? "Adjusting…" : "Adjust credit"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* move credit to another learner (siblings / family consolidation) */
function TransferCreditDialog({ row, students, onOpenChange, onDone }: {
  row: CreditRow | null;
  students: { id: string; name: string; classroom: string }[];
  onOpenChange: (o: boolean) => void;
  onDone: () => void;
}) {
  const { currentSchoolId } = useStore();
  const [toId, setToId] = useState("");
  const [amount, setAmount] = useState("");
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => { if (row) { setToId(""); setAmount((row.balanceCents / 100).toFixed(2)); setNote(""); } }, [row]);
  const cents = Math.round(parseFloat(amount || "0") * 100);
  const target = students.find((s) => s.id === toId);

  async function submit() {
    if (!row) return;
    if (!target) { toast({ title: "Choose the receiving learner" }); return; }
    if (!cents || cents <= 0) { toast({ title: "Enter a transfer amount above zero" }); return; }
    if (cents > row.balanceCents) { toast({ title: "Insufficient credit", description: `${row.name} holds ${fmtCedi(row.balanceCents)}.` }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/finance`, { action: "credit-transfer", fromStudentId: row.studentId, toStudentId: toId, amountCents: cents, note: note || undefined });
      toast({ title: "Credit transferred", description: `${fmtCedi(cents)} moved from ${row.name} to ${target.name}.` });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!row} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><ArrowRightLeft className="size-4 text-primary" /> Transfer credit — {row?.name}</DialogTitle>
          <DialogDescription>Available credit <b className="text-emerald-600">{row ? fmtCedi(row.balanceCents) : ""}</b> · moves to another learner&apos;s wallet (two audited ledger rows).</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <LearnerPicker students={students} selected={toId} onSelect={setToId} exclude={row?.studentId} />
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Amount (₵) *</Label>
              <Input type="number" step="0.01" value={amount} onChange={(e) => setAmount(e.target.value)} />
            </div>
            <div className="space-y-1.5">
              <Label>Note</Label>
              <Input value={note} onChange={(e) => setNote(e.target.value)} placeholder="e.g. Consolidate siblings' credits" />
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || !toId || !cents || cents <= 0 || cents > (row?.balanceCents ?? 0)}>{busy ? "Transferring…" : "Transfer credit"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* convert credit → daily-services prepaid wallet */
function ToDailyWalletDialog({ row, onOpenChange, onDone }: { row: CreditRow | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [amount, setAmount] = useState("");
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => { if (row) { setAmount((row.balanceCents / 100).toFixed(2)); setNote(""); } }, [row]);
  const cents = Math.round(parseFloat(amount || "0") * 100);

  async function submit() {
    if (!row) return;
    if (!cents || cents <= 0) { toast({ title: "Enter an amount above zero" }); return; }
    if (cents > row.balanceCents) { toast({ title: "Insufficient credit", description: `${row.name} holds ${fmtCedi(row.balanceCents)}.` }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/finance`, { action: "credit-to-daily", studentId: row.studentId, amountCents: cents, note: note || undefined });
      toast({ title: "Moved to daily wallet", description: `${fmtCedi(cents)} is now prepaid credit for meals, water and other daily services.` });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!row} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><HandCoins className="size-4 text-primary" /> Send to daily wallet — {row?.name}</DialogTitle>
          <DialogDescription>
            Converts fee credit into daily-services prepaid balance (meals, water, transport extras) — the learner&apos;s card/wallet is topped up immediately.
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Amount (₵) *</Label>
              <Input type="number" step="0.01" value={amount} onChange={(e) => setAmount(e.target.value)} />
              <p className="text-[11px] text-muted-foreground">Available credit: {row ? fmtCedi(row.balanceCents) : ""}</p>
            </div>
            <div className="space-y-1.5">
              <Label>Note</Label>
              <Input value={note} onChange={(e) => setNote(e.target.value)} placeholder="e.g. Guardian wants it on lunch" />
            </div>
          </div>
          <p className="text-xs text-muted-foreground">Recorded as a DAILY_WALLET ledger row plus a wallet TOPUP — both audited with your name.</p>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || !cents || cents <= 0 || cents > (row?.balanceCents ?? 0)}>{busy ? "Converting…" : "Send to daily wallet"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

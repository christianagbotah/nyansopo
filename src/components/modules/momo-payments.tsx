"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { StatCard, ModuleHeader, LoadingBlock, EmptyState, FilterBar, FilterSearch } from "@/components/ui-helpers";
import { fmtDateTime } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import {
  SmartphoneNfc, Plus, RefreshCcw, Link2, Copy, ExternalLink, MessageSquareText, Ban, CheckCircle2,
  XCircle, Clock, Search, Wallet, Receipt, UtensilsCrossed, Banknote, CircleCheck, Loader2, LinkIcon,
} from "lucide-react";

/* ── MoMo & Payment Links (Hubtel) ─────────────────────────────
   Request school money from parents by mobile money — a USSD prompt to
   the guardian's phone or a shareable hosted-checkout link. Completions
   arrive by webhook (or on-demand status check) and post straight into
   the ledgers: invoices, per-item daily arrears, item advances, wallets. */

type MomoRequest = {
  id: string; studentId: string | null; student: string | null; index: string | null;
  purpose: string; purposeLabel: string; invoiceNo: string | null;
  amountCents: number; method: string; channel: string | null; channelLabel: string | null;
  payerName: string; payerMsisdn: string; status: string; provider: string;
  clientReference: string; checkoutUrl: string | null; payUrl: string | null;
  receiptNo: string | null; appliedNote: string | null; failureReason: string | null;
  createdByName: string | null; createdAt: string; completedAt: string | null; lastCheckedAt: string | null;
};

type MomoData = {
  provider: "HUBTEL" | "SIMULATED";
  configured: boolean;
  webhookUrl: string | null;
  callbackBase: string | null;
  stats: { pending: number; completedWeek: number; collectedWeekCents: number; failedWeek: number };
  requests: MomoRequest[];
};

type StudentLite = { id: string; name: string; classroom: string; guardianName: string; guardianPhone: string };

type Accounts = {
  student: { id: string; name: string; guardianName: string; guardianPhone: string; intlPhone: string | null; channel: { channel: string; label: string } };
  invoices: { id: string; invoiceNo: string; balanceCents: number; dueDate: string | null }[];
  dailyItems: { serviceId: string; name: string; category: string; chargeMode: string; rateCents: number; arrearsCents: number; balanceCents: number }[];
  wallet: { prepaidCents: number; transportCents: number };
};

const fmt = (cents: number) => `GH₵${(cents / 100).toFixed(2)}`;

/** Absolute pay link in the browser — env-based URL when present, else the
 *  current origin + /pay/{ref} (preview hosts and LAN deployments). */
function resolveLink(r: { checkoutUrl: string | null; payUrl: string | null; clientReference: string }): string | null {
  if (r.checkoutUrl?.startsWith("http")) return r.checkoutUrl;
  if (r.payUrl?.startsWith("http")) return r.payUrl;
  if (typeof window !== "undefined") return `${window.location.origin}/pay/${r.clientReference}`;
  return null;
}

const STATUS_STYLE: Record<string, string> = {
  PENDING: "bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300",
  COMPLETED: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  FAILED: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300",
  CANCELLED: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
};

const PURPOSE_STYLE: Record<string, string> = {
  INVOICE: "bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300",
  DAILY_ITEMS: "bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300",
  ITEM_BALANCE: "bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300",
  WALLET: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
};

const CHANNEL_STYLE: Record<string, string> = {
  "mtn-gh": "bg-yellow-100 text-yellow-900 dark:bg-yellow-950 dark:text-yellow-300",
  "vodafone-gh": "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300",
  "tigo-gh": "bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300",
};

const CAT_CHIP: Record<string, string> = {
  MEAL: "bg-orange-500/15 text-orange-700 dark:text-orange-300 border border-orange-500/30",
  DRINK: "bg-cyan-500/15 text-cyan-700 dark:text-cyan-300 border border-cyan-500/30",
  CLASS: "bg-violet-500/15 text-violet-700 dark:text-violet-300 border border-violet-500/30",
  CARE: "bg-pink-500/15 text-pink-700 dark:text-pink-300 border border-pink-500/30",
  TRANSPORT: "bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30",
  OTHER: "bg-muted text-foreground",
};

const PURPOSES: { id: string; label: string; hint: string; icon: any }[] = [
  { id: "INVOICE", label: "Termly fees", hint: "Settle an open invoice", icon: Banknote },
  { id: "DAILY_ITEMS", label: "Daily fee items", hint: "Lunch, water, transport arrears", icon: UtensilsCrossed },
  { id: "ITEM_BALANCE", label: "Fee-item advance", hint: "Prepay one item", icon: CircleCheck },
  { id: "WALLET", label: "Wallet top-up", hint: "Prepaid / transport wallet", icon: Wallet },
];

export function MomoPayments() {
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<MomoData | null>(null);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("ALL");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [checking, setChecking] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (!currentSchoolId) return;
    try {
      const d = await apiGet<MomoData>(`/api/school/${currentSchoolId}/ops/payments?status=${statusFilter}`);
      setData(d);
    } catch (e: any) {
      toast({ title: "Could not load payments", description: e?.message, variant: "destructive" });
    } finally {
      setLoading(false);
    }
  }, [currentSchoolId, statusFilter]);

  useEffect(() => { setLoading(true); load(); }, [load]);

  // auto-refresh while requests are pending (webhook/simulate may complete them)
  const hasPending = !!data?.requests.some((r) => r.status === "PENDING");
  const loadRef = useRef(load);
  loadRef.current = load;
  useEffect(() => {
    if (!hasPending) return;
    const t = setInterval(() => loadRef.current(), 10_000);
    return () => clearInterval(t);
  }, [hasPending]);

  async function checkOne(r: MomoRequest) {
    setChecking(r.id);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/payments`, { action: "check", id: r.id });
      await load();
      toast({ title: "Status refreshed", description: `${r.student ?? r.payerName} · ${r.clientReference}` });
    } catch (e: any) {
      toast({ title: "Check failed", description: e?.message, variant: "destructive" });
    } finally {
      setChecking(null);
    }
  }

  async function cancelOne(r: MomoRequest) {
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/payments`, { action: "cancel", id: r.id });
      await load();
    } catch (e: any) {
      toast({ title: "Could not cancel", description: e?.message, variant: "destructive" });
    }
  }

  async function shareLink(r: MomoRequest) {
    try {
      const res = await apiPost<{ smsSent: boolean }>(`/api/school/${currentSchoolId}/ops/payments`, {
        action: "share-link",
        id: r.id,
      });
      if (res.smsSent) toast({ title: "Link sent by SMS", description: `To ${r.payerMsisdn}` });
      else toast({ title: "Link", description: r.checkoutUrl ?? r.payUrl ?? "" });
    } catch (e: any) {
      const link = resolveLink(r);
      if (link && navigator.clipboard) { await navigator.clipboard.writeText(link); toast({ title: "Link copied" }); }
      else toast({ title: "Share failed", description: e?.message, variant: "destructive" });
    }
  }

  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase();
    if (!q) return data?.requests ?? [];
    return (data?.requests ?? []).filter((r) =>
      (r.student ?? "").toLowerCase().includes(q) ||
      r.clientReference.toLowerCase().includes(q) ||
      r.payerMsisdn.includes(q) ||
      (r.receiptNo ?? "").toLowerCase().includes(q));
  }, [data?.requests, search]);

  const live = data?.provider === "HUBTEL";

  return (
    <div className="space-y-4">
      <ModuleHeader
        title="MoMo & Payment Links"
        description={live ? "Hubtel live — momo prompts & hosted checkout links post straight into your ledgers." : "Demo mode — Hubtel credentials not yet configured; rehearse prompts & links end-to-end."}
        actions={
          <>
            <Button variant="outline" size="sm" onClick={() => load()}><RefreshCcw className="size-4" /> Refresh</Button>
            <Button size="sm" onClick={() => setDialogOpen(true)}><Plus className="size-4" /> New request</Button>
          </>
        }
      />

      {data ? (
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <StatCard icon={<Clock className="size-4" />} label="Awaiting payment" value={String(data.stats.pending)} tone="primary" sub="prompts & links outstanding" />
          <StatCard icon={<CheckCircle2 className="size-4" />} label="Collected · 7 days" value={fmt(data.stats.collectedWeekCents)} tone="gold" sub={`${data.stats.completedWeek} payments`} />
          <StatCard icon={<XCircle className="size-4" />} label="Failed · 7 days" value={String(data.stats.failedWeek)} tone={data.stats.failedWeek > 0 ? "danger" : "default"} sub="declined / timed out" />
          <StatCard icon={<SmartphoneNfc className="size-4" />} label="Provider" value={live ? "Hubtel · live" : "Demo mode"} sub={live ? "webhook connected" : "HUBTEL_* env not set"} />
        </div>
      ) : null}

      <Tabs defaultValue="requests">
        <TabsList>
          <TabsTrigger value="requests">Requests</TabsTrigger>
          <TabsTrigger value="setup">Hubtel setup</TabsTrigger>
        </TabsList>

        <TabsContent value="requests" className="space-y-3">
          <FilterBar active={statusFilter !== "ALL" || !!search.trim()} onClear={() => { setStatusFilter("ALL"); setSearch(""); }} count={filtered.length} total={data?.requests.length ?? 0} label="Payment filters">
            <FilterSearch placeholder="Search learner, reference, phone, receipt…" value={search} onChange={setSearch} />
            <div className="flex gap-1 flex-wrap">
              {["ALL", "PENDING", "COMPLETED", "FAILED", "CANCELLED"].map((s) => (
                <button key={s} onClick={() => setStatusFilter(s)}
                  className={`px-2.5 py-1.5 rounded-md text-xs font-medium border transition-colors ${statusFilter === s ? "bg-primary text-primary-foreground border-primary" : "bg-background hover:bg-muted border-border"}`}>
                  {s === "ALL" ? "All" : s[0] + s.slice(1).toLowerCase()}
                </button>
              ))}
            </div>
          </FilterBar>

          {loading ? <LoadingBlock label="Loading requests…" /> : !filtered.length ? (
            <EmptyState icon={<SmartphoneNfc className="size-8" />} title="No payment requests yet"
              hint="Send a momo prompt to a guardian's phone, or share a hosted checkout link — completions post automatically into invoices, daily items and wallets." />
          ) : (
            <Card>
              <CardContent className="p-0">
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b bg-muted/50 text-left text-xs uppercase tracking-wide text-muted-foreground">
                        <th className="px-3 py-2.5 font-medium">Learner</th>
                        <th className="px-3 py-2.5 font-medium">For</th>
                        <th className="px-3 py-2.5 font-medium text-right">Amount</th>
                        <th className="px-3 py-2.5 font-medium">Method</th>
                        <th className="px-3 py-2.5 font-medium">Status</th>
                        <th className="px-3 py-2.5 font-medium">Receipt / note</th>
                        <th className="px-3 py-2.5 font-medium">Created</th>
                        <th className="px-3 py-2.5 font-medium text-right">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {filtered.map((r) => (
                        <tr key={r.id} className="border-b last:border-0 hover:bg-muted/30">
                          <td className="px-3 py-2.5">
                            <div className="font-medium">{r.student ?? "—"}</div>
                            <div className="text-xs text-muted-foreground">{r.index} · {r.payerName}</div>
                          </td>
                          <td className="px-3 py-2.5">
                            <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${PURPOSE_STYLE[r.purpose] ?? ""}`}>{r.purposeLabel}</span>
                            {r.invoiceNo ? <div className="text-xs text-muted-foreground mt-0.5">{r.invoiceNo}</div> : null}
                          </td>
                          <td className="px-3 py-2.5 text-right font-semibold">{fmt(r.amountCents)}</td>
                          <td className="px-3 py-2.5">
                            {r.method === "MOMO_PROMPT" ? (
                              <div className="flex items-center gap-1.5">
                                <SmartphoneNfc className="size-3.5 text-muted-foreground" />
                                <span className="text-xs">{r.payerMsisdn}</span>
                                {r.channelLabel ? <span className={`rounded-full px-1.5 py-0.5 text-[10px] font-semibold ${CHANNEL_STYLE[r.channel ?? ""] ?? ""}`}>{r.channelLabel}</span> : null}
                              </div>
                            ) : (
                              <div className="flex items-center gap-1.5 text-xs text-muted-foreground"><LinkIcon className="size-3.5" /> Checkout link</div>
                            )}
                          </td>
                          <td className="px-3 py-2.5">
                            <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_STYLE[r.status] ?? ""}`}>
                              {r.status === "PENDING" ? <Clock className="size-3 animate-pulse" /> : r.status === "COMPLETED" ? <CheckCircle2 className="size-3" /> : <XCircle className="size-3" />}
                              {r.status[0] + r.status.slice(1).toLowerCase()}
                            </span>
                            {r.failureReason ? <div className="text-[11px] text-muted-foreground mt-0.5 max-w-40 truncate">{r.failureReason}</div> : null}
                          </td>
                          <td className="px-3 py-2.5">
                            {r.receiptNo ? <div className="font-mono text-xs">{r.receiptNo}</div> : null}
                            {r.appliedNote ? <div className="text-[11px] text-muted-foreground max-w-52 truncate" title={r.appliedNote}>{r.appliedNote}</div> : null}
                          </td>
                          <td className="px-3 py-2.5 text-xs text-muted-foreground">{fmtDateTime(r.createdAt)}</td>
                          <td className="px-3 py-2.5">
                            <div className="flex justify-end gap-1">
                              {r.status === "PENDING" ? (
                                <Button variant="ghost" size="sm" className="h-7 gap-1 text-xs" disabled={checking === r.id} onClick={() => checkOne(r)}>
                                  {checking === r.id ? <Loader2 className="size-3.5 animate-spin" /> : <RefreshCcw className="size-3.5" />} Check
                                </Button>
                              ) : null}
                              {r.method === "PAYMENT_LINK" && r.status === "PENDING" ? (
                                <>
                                  <Button variant="ghost" size="sm" className="h-7 gap-1 text-xs" onClick={() => {
                                    const link = resolveLink(r);
                                    if (link && navigator.clipboard) navigator.clipboard.writeText(link).then(() => toast({ title: "Link copied" }));
                                  }}><Copy className="size-3.5" /> Copy</Button>
                                  {(r.checkoutUrl?.startsWith("http") || !r.provider || r.provider === "SIMULATED") ? (
                                    <a href={resolveLink(r) ?? "#"} target="_blank" rel="noreferrer">
                                      <Button variant="ghost" size="sm" className="h-7 gap-1 text-xs"><ExternalLink className="size-3.5" /> Open</Button>
                                    </a>
                                  ) : null}
                                  <Button variant="ghost" size="sm" className="h-7 gap-1 text-xs" onClick={() => shareLink(r)}><MessageSquareText className="size-3.5" /> SMS</Button>
                                </>
                              ) : null}
                              {r.status === "PENDING" ? (
                                <Button variant="ghost" size="sm" className="h-7 gap-1 text-xs text-destructive" onClick={() => cancelOne(r)}><Ban className="size-3.5" /> Cancel</Button>
                              ) : null}
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        <TabsContent value="setup">
          <div className="grid md:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><SmartphoneNfc className="size-4" /> Hubtel connection</CardTitle></CardHeader>
              <CardContent className="space-y-3 text-sm">
                <div className="flex items-center gap-2">
                  <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${live ? "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300" : "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"}`}>
                    {live ? "LIVE" : "DEMO (SIMULATED)"}
                  </span>
                  <span className="text-muted-foreground text-xs">{live ? "Requests hit the Hubtel Merchant API" : "Requests stay local until credentials are added"}</span>
                </div>
                <div className="rounded-lg bg-muted/50 p-3 text-xs space-y-1.5 font-mono">
                  <div>HUBTEL_CLIENT_ID {data?.configured ? "= ✓ set" : "= — add from Hubtel portal"}</div>
                  <div>HUBTEL_CLIENT_SECRET {data?.configured ? "= ✓ set" : "= — add from Hubtel portal"}</div>
                  <div>HUBTEL_MERCHANT_ACCOUNT_NUMBER {data?.configured ? "= ✓ set" : "= — add from Hubtel portal"}</div>
                  <div>HUBTEL_CALLBACK_BASE {data?.callbackBase ? `= ${data.callbackBase}` : "= — public URL of this deployment"}</div>
                  <div>HUBTEL_WEBHOOK_SECRET = optional (?secret=… on the callback)</div>
                </div>
                <p className="text-xs text-muted-foreground">
                  Keys come from your Hubtel account — Payments: <b>developers.hubtel.com → Business → Authentication</b> (create keys with the “Receive Money” scope); SMS: <b>Messaging → Manage → Programmable SMS</b>. Set them as deployment environment variables, never in the database.
                </p>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Link2 className="size-4" /> Webhook & links</CardTitle></CardHeader>
              <CardContent className="space-y-3 text-sm">
                <div className="space-y-1.5">
                  <Label className="text-xs">Payment callback URL (give this to Hubtel integrations if they ask)</Label>
                  <div className="flex gap-2">
                    <Input readOnly value={data?.webhookUrl ?? "— set HUBTEL_CALLBACK_BASE to generate —"} className="font-mono text-xs" />
                    {data?.webhookUrl ? <Button variant="outline" size="sm" onClick={() => navigator.clipboard?.writeText(data.webhookUrl!).then(() => toast({ title: "Copied" }))}><Copy className="size-4" /></Button> : null}
                  </div>
                </div>
                <ul className="text-xs text-muted-foreground space-y-1.5 list-disc pl-4">
                  <li><b>MoMo prompt</b> — the guardian gets a USSD push on their phone; approve with PIN and the ledger updates by webhook.</li>
                  <li><b>Payment link</b> — a hosted Hubtel checkout; share by SMS/WhatsApp. The parent returns to a friendly status page.</li>
                  <li>Every completed request posts <b>automatically</b>: invoices settle (excess → credit), daily items clear FIFO (excess → item advance), wallets top up, and the receipt SMS goes out if enabled.</li>
                  <li>Failed / cancelled requests never touch the ledgers; use <b>Check</b> to reconcile a missed webhook.</li>
                </ul>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>

      <NewRequestDialog open={dialogOpen} onOpenChange={(o) => { setDialogOpen(o); if (!o) load(); }} live={live} />
    </div>
  );
}

/* ── New request dialog ──────────────────────────────────────── */
function NewRequestDialog({ open, onOpenChange, live }: { open: boolean; onOpenChange: (o: boolean) => void; live: boolean }) {
  const { currentSchoolId } = useStore();
  const [search, setSearch] = useState("");
  const [students, setStudents] = useState<StudentLite[]>([]);
  const [studentId, setStudentId] = useState<string | null>(null);
  const [accounts, setAccounts] = useState<Accounts | null>(null);
  const [loadingAccts, setLoadingAccts] = useState(false);

  const [purpose, setPurpose] = useState("INVOICE");
  const [invoiceId, setInvoiceId] = useState<string | null>(null);
  const [invoiceAmount, setInvoiceAmount] = useState("");
  const [itemAmounts, setItemAmounts] = useState<Record<string, string>>({});
  const [itemBalanceId, setItemBalanceId] = useState<string | null>(null);
  const [itemBalanceAmount, setItemBalanceAmount] = useState("");
  const [walletKind, setWalletKind] = useState("PREPAID");
  const [walletAmount, setWalletAmount] = useState("");
  const [method, setMethod] = useState<"MOMO_PROMPT" | "PAYMENT_LINK">("MOMO_PROMPT");
  const [phone, setPhone] = useState("");
  const [channelOverride, setChannelOverride] = useState<string>("");
  const [sendLinkSms, setSendLinkSms] = useState(true);
  const [busy, setBusy] = useState(false);
  const [created, setCreated] = useState<{ clientReference: string; checkoutUrl: string | null; payUrl: string | null; method: string } | null>(null);

  const reset = () => {
    setSearch(""); setStudents([]); setStudentId(null); setAccounts(null);
    setPurpose("INVOICE"); setInvoiceId(null); setInvoiceAmount(""); setItemAmounts({});
    setItemBalanceId(null); setItemBalanceAmount(""); setWalletKind("PREPAID"); setWalletAmount("");
    setMethod("MOMO_PROMPT"); setPhone(""); setChannelOverride(""); setCreated(null);
  };

  useEffect(() => {
     
    if (!open) reset();
  }, [open]);

  // debounced learner search
  useEffect(() => {
     
    if (!open || studentId) return;
    if (!search.trim()) { setStudents([]); return; }
    const t = setTimeout(() => {
      apiGet<{ students: StudentLite[] }>(`/api/school/${currentSchoolId}/students?search=${encodeURIComponent(search)}`)
        .then((d) => setStudents(d.students.slice(0, 6)))
        .catch(() => setStudents([]));
    }, 250);
    return () => clearTimeout(t);
  }, [search, currentSchoolId, open, studentId]);

  // load accounts when a learner is chosen
  useEffect(() => {
     
    if (!studentId || !currentSchoolId) return;
    setLoadingAccts(true);
    apiPost<Accounts>(`/api/school/${currentSchoolId}/ops/payments`, { action: "student-accounts", studentId })
      .then((a) => {
        setAccounts(a);
        setPhone(a.student.guardianPhone);
        // sensible defaults: first open invoice / first item with arrears
        if (a.invoices.length) { setInvoiceId(a.invoices[0].id); setInvoiceAmount(String(a.invoices[0].balanceCents / 100)); }
        const firstArrears = a.dailyItems.find((i) => i.arrearsCents > 0);
        if (firstArrears) setItemAmounts({ [firstArrears.serviceId]: String(firstArrears.arrearsCents / 100) });
        if (a.dailyItems.length) setItemBalanceId(a.dailyItems[0].serviceId);
      })
      .catch(() => toast({ title: "Could not load accounts", variant: "destructive" }))
      .finally(() => setLoadingAccts(false));
  }, [studentId, currentSchoolId]);

  const itemTotal = Object.values(itemAmounts).reduce((s, v) => s + Math.round((parseFloat(v) || 0) * 100), 0);
  const amountCents = purpose === "INVOICE" ? Math.round((parseFloat(invoiceAmount) || 0) * 100)
    : purpose === "DAILY_ITEMS" ? itemTotal
    : purpose === "ITEM_BALANCE" ? Math.round((parseFloat(itemBalanceAmount) || 0) * 100)
    : Math.round((parseFloat(walletAmount) || 0) * 100);

  const selectedInvoice = accounts?.invoices.find((i) => i.id === invoiceId) ?? null;
  const detectedChannel = accounts?.student.channel;
  const effChannel = channelOverride || detectedChannel?.channel || "mtn-gh";

  async function submit() {
    if (!studentId) return;
    setBusy(true);
    try {
      const items = purpose === "DAILY_ITEMS"
        ? Object.entries(itemAmounts).filter(([, v]) => (parseFloat(v) || 0) > 0).map(([serviceId, v]) => ({ serviceId, amountCents: Math.round(parseFloat(v) * 100) }))
        : purpose === "ITEM_BALANCE" && itemBalanceId
          ? [{ serviceId: itemBalanceId, amountCents: Math.round((parseFloat(itemBalanceAmount) || 0) * 100) }]
          : undefined;
      const res = await apiPost<{ request: { clientReference: string; checkoutUrl: string | null; payUrl: string | null; method: string }; smsSent: boolean }>(`/api/school/${currentSchoolId}/ops/payments`, {
        action: "create",
        studentId, purpose,
        invoiceId: purpose === "INVOICE" ? invoiceId : null,
        items, walletKind: purpose === "WALLET" ? walletKind : null,
        amountCents, method,
        payerMsisdn: phone || undefined,
        channel: method === "MOMO_PROMPT" ? effChannel : null,
        sendLinkSms: method === "PAYMENT_LINK" && sendLinkSms,
      });
      setCreated({ clientReference: res.request.clientReference, checkoutUrl: res.request.checkoutUrl, payUrl: res.request.payUrl, method: res.request.method });
    } catch (e: any) {
      toast({ title: "Could not create request", description: e?.message, variant: "destructive" });
    } finally {
      setBusy(false);
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><SmartphoneNfc className="size-5" /> Request a momo payment</DialogTitle>
          <DialogDescription>
            Send a payment prompt to the guardian&apos;s phone, or create a checkout link — {live ? "live via Hubtel" : "demo mode (no Hubtel credentials yet)"}.
          </DialogDescription>
        </DialogHeader>

        {created ? (
          <div className="space-y-4">
            <div className="rounded-xl bg-emerald-500/10 p-4 text-center">
              <CheckCircle2 className="size-8 mx-auto text-emerald-600" />
              <p className="font-semibold mt-1">{created.method === "MOMO_PROMPT" ? "Prompt sent" : "Checkout link ready"}</p>
              <p className="text-xs text-muted-foreground">Reference {created.clientReference} · {fmt(amountCents)}</p>
            </div>
            {created.method === "MOMO_PROMPT" ? (
              <p className="text-sm text-muted-foreground text-center">The guardian should enter their PIN to approve. Status updates automatically here; use <b>Check</b> to reconcile.</p>
            ) : (
              <div className="space-y-2">
                <div className="flex gap-2">
                  <Input readOnly value={created.checkoutUrl?.startsWith("http") ? created.checkoutUrl : `${window.location.origin}/pay/${created.clientReference}`} className="font-mono text-xs" />
                  <Button variant="outline" onClick={() => {
                    const link = created.checkoutUrl?.startsWith("http") ? created.checkoutUrl : `${window.location.origin}/pay/${created.clientReference}`;
                    navigator.clipboard?.writeText(link).then(() => toast({ title: "Link copied" }));
                  }}><Copy className="size-4" /></Button>
                  {created.checkoutUrl?.startsWith("http") ? <a href={created.checkoutUrl} target="_blank" rel="noreferrer"><Button variant="outline"><ExternalLink className="size-4" /></Button></a> : null}
                </div>
                <p className="text-xs text-muted-foreground">Share it by SMS / WhatsApp. After paying, the parent lands on the friendly status page and your ledger updates automatically.</p>
              </div>
            )}
            <div className="flex justify-end gap-2">
              <Button variant="outline" onClick={() => reset()}>New request</Button>
              <Button onClick={() => onOpenChange(false)}>Done</Button>
            </div>
          </div>
        ) : (
          <div className="space-y-4">
            {/* learner picker */}
            {!studentId ? (
              <div className="space-y-2">
                <Label>Learner *</Label>
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                  <Input autoFocus value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search by name, index, guardian…" className="pl-9" />
                </div>
                <div className="divide-y rounded-lg border max-h-56 overflow-y-auto">
                  {students.map((s) => (
                    <button key={s.id} className="w-full flex items-center justify-between px-3 py-2.5 text-left hover:bg-muted/50" onClick={() => setStudentId(s.id)}>
                      <div>
                        <div className="text-sm font-medium">{s.name}</div>
                        <div className="text-xs text-muted-foreground">{s.classroom} · guardian {s.guardianName}</div>
                      </div>
                      <div className="text-xs text-muted-foreground">{s.guardianPhone}</div>
                    </button>
                  ))}
                  {!students.length ? <div className="px-3 py-6 text-center text-sm text-muted-foreground">{search.trim() ? "No learners match" : "Type to search learners"}</div> : null}
                </div>
              </div>
            ) : loadingAccts ? (
              <div className="py-8 text-center text-sm text-muted-foreground flex items-center justify-center gap-2"><Loader2 className="size-4 animate-spin" /> Loading accounts…</div>
            ) : accounts ? (
              <>
                {/* chosen learner */}
                <div className="flex items-center justify-between rounded-lg border bg-secondary/40 px-3 py-2">
                  <div className="text-sm"><b>{accounts.student.name}</b> <span className="text-xs text-muted-foreground">· guardian {accounts.student.guardianName} · {accounts.student.guardianPhone}</span></div>
                  <Button variant="ghost" size="sm" className="h-6 text-xs" onClick={() => { setStudentId(null); setAccounts(null); }}>change</Button>
                </div>

                {/* purpose cards */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                  {PURPOSES.map((p) => (
                    <button key={p.id} onClick={() => setPurpose(p.id)}
                      className={`rounded-xl border p-3 text-left transition-colors ${purpose === p.id ? "border-primary bg-primary/5 ring-1 ring-primary" : "hover:bg-muted/50"}`}>
                      <p.icon className={`size-4 mb-1 ${purpose === p.id ? "text-primary" : "text-muted-foreground"}`} />
                      <div className="text-xs font-semibold leading-tight">{p.label}</div>
                      <div className="text-[11px] text-muted-foreground leading-tight mt-0.5">{p.hint}</div>
                    </button>
                  ))}
                </div>

                {/* per-purpose input */}
                {purpose === "INVOICE" ? (
                  <div className="space-y-2">
                    <Label>Open invoice *</Label>
                    {accounts.invoices.length ? (
                      <div className="grid gap-2">
                        {accounts.invoices.map((inv) => (
                          <button key={inv.id} onClick={() => { setInvoiceId(inv.id); setInvoiceAmount(String(inv.balanceCents / 100)); }}
                            className={`flex items-center justify-between rounded-lg border px-3 py-2 text-left text-sm ${invoiceId === inv.id ? "border-primary bg-primary/5" : "hover:bg-muted/50"}`}>
                            <span>Invoice <b>{inv.invoiceNo}</b>{inv.dueDate ? <span className="text-xs text-muted-foreground"> · due {new Date(inv.dueDate).toLocaleDateString("en-GB")}</span> : null}</span>
                            <span className="font-semibold">{fmt(inv.balanceCents)}</span>
                          </button>
                        ))}
                        <div className="grid grid-cols-2 gap-2 items-end">
                          <div className="space-y-1.5">
                            <Label>Amount (GH₵) *</Label>
                            <Input type="number" min="0.5" step="0.01" value={invoiceAmount} onChange={(e) => setInvoiceAmount(e.target.value)} />
                          </div>
                          {selectedInvoice ? (
                            <Button type="button" variant="outline" onClick={() => setInvoiceAmount(String(selectedInvoice.balanceCents / 100))}>Full balance — {fmt(selectedInvoice.balanceCents)}</Button>
                          ) : null}
                        </div>
                      </div>
                    ) : <p className="text-sm text-muted-foreground rounded-lg border border-dashed p-3">No open invoices — generate invoices in Fees &amp; Billing first.</p>}
                  </div>
                ) : purpose === "DAILY_ITEMS" ? (
                  <div className="space-y-2">
                    <Label>Daily fee items — enter the amount per item *</Label>
                    <div className="grid sm:grid-cols-2 gap-2">
                      {accounts.dailyItems.map((it) => (
                        <div key={it.serviceId} className={`rounded-lg border p-2.5 ${parseFloat(itemAmounts[it.serviceId] ?? "") > 0 ? "border-primary/60 bg-primary/5" : ""}`}>
                          <div className="flex items-center justify-between mb-1.5">
                            <div className="flex items-center gap-1.5 flex-wrap">
                              <span className={`rounded-full px-1.5 py-0.5 text-[10px] font-semibold ${CAT_CHIP[it.category] ?? CAT_CHIP.TUCK}`}>{it.name}</span>
                              <span className="text-[10px] text-muted-foreground">{it.chargeMode === "WEEKLY" ? "weekly" : fmt(it.rateCents) + (it.chargeMode === "PER_DAY" ? "/day" : "")}</span>
                            </div>
                          </div>
                          <div className="flex items-center gap-2">
                            <Input type="number" min="0" step="0.01" placeholder="0.00" value={itemAmounts[it.serviceId] ?? ""} onChange={(e) => setItemAmounts({ ...itemAmounts, [it.serviceId]: e.target.value })} className="h-8" />
                            {it.arrearsCents > 0 ? (
                              <Button type="button" variant="ghost" size="sm" className="h-8 text-xs shrink-0 text-red-600" onClick={() => setItemAmounts({ ...itemAmounts, [it.serviceId]: String(it.arrearsCents / 100) })}>
                                arrears {fmt(it.arrearsCents)}
                              </Button>
                            ) : it.balanceCents > 0 ? <span className="text-[10px] text-emerald-600 shrink-0">credit {fmt(it.balanceCents)}</span> : null}
                          </div>
                        </div>
                      ))}
                    </div>
                    <div className="text-xs text-muted-foreground text-right">Selected total <b className="text-foreground">{fmt(itemTotal)}</b> — excess lands on the item as advance.</div>
                  </div>
                ) : purpose === "ITEM_BALANCE" ? (
                  <div className="grid grid-cols-2 gap-2">
                    <div className="space-y-1.5">
                      <Label>Fee item *</Label>
                      <Select value={itemBalanceId ?? ""} onValueChange={setItemBalanceId}>
                        <SelectTrigger><SelectValue placeholder="Choose item" /></SelectTrigger>
                        <SelectContent>
                          {accounts.dailyItems.map((it) => <SelectItem key={it.serviceId} value={it.serviceId}>{it.name}</SelectItem>)}
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="space-y-1.5">
                      <Label>Amount (GH₵) *</Label>
                      <Input type="number" min="0.5" step="0.01" value={itemBalanceAmount} onChange={(e) => setItemBalanceAmount(e.target.value)} placeholder="e.g. 20" />
                    </div>
                  </div>
                ) : (
                  <div className="grid grid-cols-2 gap-2">
                    <div className="space-y-1.5">
                      <Label>Wallet *</Label>
                      <Select value={walletKind} onValueChange={setWalletKind}>
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                          <SelectItem value="PREPAID">Prepaid wallet — {fmt(accounts.wallet.prepaidCents)}</SelectItem>
                          <SelectItem value="TRANSPORT">Transport wallet — {fmt(accounts.wallet.transportCents)}</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="space-y-1.5">
                      <Label>Amount (GH₵) *</Label>
                      <Input type="number" min="0.5" step="0.01" value={walletAmount} onChange={(e) => setWalletAmount(e.target.value)} placeholder="e.g. 50" />
                    </div>
                  </div>
                )}

                {/* delivery */}
                <div className="space-y-2 rounded-xl border p-3">
                  <Label>Delivery *</Label>
                  <div className="grid grid-cols-2 gap-2">
                    <button onClick={() => setMethod("MOMO_PROMPT")} className={`rounded-lg border p-2.5 text-left ${method === "MOMO_PROMPT" ? "border-primary bg-primary/5" : "hover:bg-muted/50"}`}>
                      <div className="text-xs font-semibold flex items-center gap-1.5"><SmartphoneNfc className="size-3.5" /> MoMo prompt</div>
                      <div className="text-[11px] text-muted-foreground">USSD push — guardian approves with PIN</div>
                    </button>
                    <button onClick={() => setMethod("PAYMENT_LINK")} className={`rounded-lg border p-2.5 text-left ${method === "PAYMENT_LINK" ? "border-primary bg-primary/5" : "hover:bg-muted/50"}`}>
                      <div className="text-xs font-semibold flex items-center gap-1.5"><Link2 className="size-3.5" /> Payment link</div>
                      <div className="text-[11px] text-muted-foreground">Hosted checkout — share by SMS/WhatsApp</div>
                    </button>
                  </div>
                  {method === "MOMO_PROMPT" ? (
                    <div className="grid grid-cols-2 gap-2">
                      <div className="space-y-1.5">
                        <Label>MoMo number *</Label>
                        <Input value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="024…" />
                      </div>
                      <div className="space-y-1.5">
                        <Label>Network {detectedChannel ? <span className="text-[10px] text-muted-foreground">(detected {detectedChannel.label})</span> : null}</Label>
                        <Select value={effChannel} onValueChange={setChannelOverride}>
                          <SelectTrigger><SelectValue /></SelectTrigger>
                          <SelectContent>
                            <SelectItem value="mtn-gh">MTN MoMo</SelectItem>
                            <SelectItem value="vodafone-gh">Telecel Cash</SelectItem>
                            <SelectItem value="tigo-gh">AT Money</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                    </div>
                  ) : (
                    <label className="flex items-center gap-2 text-sm">
                      <input type="checkbox" checked={sendLinkSms} onChange={(e) => setSendLinkSms(e.target.checked)} className="rounded" />
                      SMS the link to {accounts.student.guardianName} ({accounts.student.guardianPhone}) right away
                    </label>
                  )}
                </div>

                <DialogFooter className="items-center gap-3">
                  <div className="text-sm mr-auto">Total <b className="text-base">{fmt(amountCents)}</b></div>
                  <Button disabled={busy || amountCents < 50 || (purpose === "INVOICE" && !invoiceId) || (purpose === "DAILY_ITEMS" && itemTotal < 50) || (purpose === "ITEM_BALANCE" && (!itemBalanceId || amountCents < 50))} onClick={submit}>
                    {busy ? <Loader2 className="size-4 animate-spin" /> : <SmartphoneNfc className="size-4" />}
                    {method === "MOMO_PROMPT" ? "Send prompt" : "Create link"}
                  </Button>
                </DialogFooter>
              </>
            ) : null}
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}

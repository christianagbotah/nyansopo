"use client";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost, getHeaders } from "@/lib/api";
import { canAny } from "@/lib/rbac";
import { fmtCedi, fmtDate } from "@/lib/format";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { DateField } from "@/components/date-field";
import { StatCard, ModuleHeader, LoadingBlock, EmptyState, FormGrid, FormField } from "@/components/ui-helpers";
import { toast } from "@/hooks/use-toast";
import {
  Landmark, Plus, Pencil, Trash2, Eye, CheckCheck, Ban, BookOpen, Wallet, ArrowDownLeft, ArrowUpRight,
  Scale, FileSpreadsheet, Printer, Download, Wand2, CheckCircle2, AlertTriangle, Building2, Layers, FileX2,
} from "lucide-react";

/* ══════════════════════════════════════════════════════════════
   Accounts & Ledger — CI3 Accounts.php / Bank_reconciliation.php
   parity: chart of accounts, journal (draft→post→void), bank feeds,
   reconciliation workspace with auto-match, and the four core
   statements. Balances are computed live from POSTED lines.
   ══════════════════════════════════════════════════════════════ */

type AcctType = "ASSET" | "LIABILITY" | "EQUITY" | "REVENUE" | "EXPENSE";
type Account = { id: string; code: string; name: string; type: AcctType; parentId: string | null; isBank: boolean; isActive: boolean; balanceCents: number };
type JLine = { id: string; accountId: string; code: string; name: string; memo: string | null; debitCents: number; creditCents: number };
type JStatus = "DRAFT" | "POSTED" | "VOIDED";
type Journal = { id: string; entryNumber: string; entryDate: string; description: string; reference: string | null; status: JStatus; createdByName: string; postedAt: string | null; postedByName: string | null; createdAt: string; totalDebitCents: number; totalCreditCents: number; balanced: boolean; lines: JLine[] };
type BankAcct = { id: string; name: string; bankName: string | null; accountNumber: string | null; chartAccountId: string | null; chartAccount: { id: string; code: string; name: string; isBank: boolean } | null; openingBalanceCents: number; isActive: boolean; txnCount: number; reconCount: number; currentBalanceCents: number; openReconId: string | null };
type BTxn = { id: string; date: string; direction: "DEPOSIT" | "WITHDRAWAL"; amountCents: number; reference: string | null; description: string; source: string; reconciledAt: string | null };
type Recon = { id: string; bankAccountId: string; bankAccountName: string; statementDate: string; statementEndCents: number; status: "IN_PROGRESS" | "COMPLETED"; createdByName: string; completedAt: string | null; createdAt: string; itemCount: number; matchedCount: number };
type ReconItem = { id: string; date: string; description: string; amountCents: number; matched: boolean; bankTxnId: string | null; bankTxn: { id: string; date: string; direction: string; amountCents: number; description: string; reference: string | null } | null };
type SysTxn = { id: string; date: string; direction: "DEPOSIT" | "WITHDRAWAL"; amountCents: number; reference: string | null; description: string; source: string; inUse: boolean };
type ReconDetail = { reconciliation: Recon & { items: ReconItem[] }; systemTxns: SysTxn[]; expectedCents: number; differenceCents: number };
type Dashboard = {
  totalAssetsCents: number; totalLiabilitiesCents: number; totalEquityCents: number;
  revenueMtdCents: number; expensesMtdCents: number; netIncomeMtdCents: number;
  journalCounts: Record<string, number>; unmatchedTxnCount: number; bankTotalCents: number; openReconCount: number; activeAccountCount: number;
};
type Data = { school: { name: string }; accounts: Account[]; journals: Journal[]; bankAccounts: BankAcct[]; bankTxns?: BTxn[]; reconciliations: Recon[]; dashboard: Dashboard };

const ACCT_TYPES: AcctType[] = ["ASSET", "LIABILITY", "EQUITY", "REVENUE", "EXPENSE"];
const TYPE_BADGE: Record<AcctType, { label: string; className: string }> = {
  ASSET: { label: "Asset", className: "bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200" },
  LIABILITY: { label: "Liability", className: "bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200" },
  EQUITY: { label: "Equity", className: "bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-200" },
  REVENUE: { label: "Revenue", className: "bg-lime-100 text-lime-900 dark:bg-lime-950 dark:text-lime-200" },
  EXPENSE: { label: "Expense", className: "bg-orange-100 text-orange-900 dark:bg-orange-950 dark:text-orange-200" },
};
const StatusChip = ({ status }: { status: JStatus }) =>
  status === "POSTED" ? <Badge className="bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">POSTED</Badge>
    : status === "DRAFT" ? <Badge className="bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200">DRAFT</Badge>
      : <Badge variant="outline" className="line-through opacity-60">VOIDED</Badge>;
const DirChip = ({ d }: { d: "DEPOSIT" | "WITHDRAWAL" }) =>
  d === "DEPOSIT" ? <Badge className="bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200 gap-1"><ArrowDownLeft className="size-3" />In</Badge>
    : <Badge className="bg-red-100 text-red-900 dark:bg-red-950 dark:text-red-200 gap-1"><ArrowUpRight className="size-3" />Out</Badge>;

const toCents = (s: string): number => {
  const v = parseFloat(s.replace(/,/g, ""));
  return Number.isFinite(v) ? Math.round(v * 100) : 0;
};
const fromCents = (c: number) => (c ? (c / 100).toString() : "");
const pad2 = (n: number) => String(n).padStart(2, "0");
const todayISO = () => {
  const d = new Date();
  return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
};
const monthStartISO = () => {
  const d = new Date();
  return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-01`;
};
const isoDate = (v: string | null | undefined) => (v ? new Date(v).toISOString().slice(0, 10) : "");

type Confirm = { title: string; description: string; action: string; extra: Record<string, unknown>; confirmLabel?: string };

export function Accounts() {
  const { currentSchoolId, navTarget, session } = useStore();
  const [data, setData] = useState<Data | null>(null);
  const [busy, setBusy] = useState(false);
  const [tab, setTab] = useState("dashboard");
  const canManage = canAny(session?.permissions, ["accounts.manage"]);

  /* iEduPro nested nav: deep-link tabs dashboard|chart|journal|bank|reconcile|reports */
  const [appliedTarget, setAppliedTarget] = useState<unknown>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab && ["dashboard", "chart", "journal", "bank", "reconcile", "reports"].includes(navTarget.tab)) setTab(navTarget.tab);
  }

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<Data>(`/api/school/${currentSchoolId}/ops/accounts`)
      .then(setData)
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  }, [currentSchoolId]);
  useEffect(() => { load(); }, [load]);

  async function act(action: string, extra: Record<string, unknown> = {}, okMsg?: string) {
    if (!currentSchoolId) return null;
    setBusy(true);
    try {
      const res = await apiPost<{ warning?: string }>(`/api/school/${currentSchoolId}/ops/accounts`, { action, ...extra });
      if (okMsg) toast({ title: okMsg });
      if (res?.warning) toast({ title: "Heads up", description: res.warning });
      load();
      return res;
    } catch (e) {
      toast({ title: "Failed", description: (e as Error).message, variant: "destructive" });
      return null;
    } finally { setBusy(false); }
  }

  const [confirmState, setConfirmState] = useState<Confirm | null>(null);
  const runConfirm = async () => {
    if (!confirmState) return;
    await act(confirmState.action, confirmState.extra, confirmState.confirmLabel ?? "Done");
    setConfirmState(null);
  };

  if (!data) return <LoadingBlock label="Loading accounts…" />;
  const d = data.dashboard;

  return (
    <div className="p-4 md:p-6 space-y-4 md:space-y-5">
      <ModuleHeader
        title="Accounts & Ledger"
        description="Double-entry books — chart of accounts, journal (draft → post → void), bank feeds, reconciliation and the core statements. Balances are computed live from posted entries."
        actions={<Badge variant="secondary" className="gap-1.5"><Landmark className="size-3.5" />{data.school.name}</Badge>}
      />

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex-wrap h-auto">
          <TabsTrigger value="dashboard">Dashboard</TabsTrigger>
          <TabsTrigger value="chart">Chart of accounts ({data.accounts.length})</TabsTrigger>
          <TabsTrigger value="journal">Journal ({data.journals.length})</TabsTrigger>
          <TabsTrigger value="bank">Bank</TabsTrigger>
          <TabsTrigger value="reconcile">Reconcile{d.openReconCount ? ` · ${d.openReconCount}` : ""}</TabsTrigger>
          <TabsTrigger value="reports">Reports</TabsTrigger>
        </TabsList>

        <TabsContent value="dashboard"><DashboardTab d={d} go={setTab} /></TabsContent>
        <TabsContent value="chart"><ChartTab data={data} canManage={canManage} busy={busy} act={act} setConfirm={setConfirmState} /></TabsContent>
        <TabsContent value="journal"><JournalTab data={data} canManage={canManage} busy={busy} act={act} setConfirm={setConfirmState} /></TabsContent>
        <TabsContent value="bank"><BankTab currentSchoolId={currentSchoolId} data={data} canManage={canManage} busy={busy} act={act} load={load} setConfirm={setConfirmState} /></TabsContent>
        <TabsContent value="reconcile"><ReconcileTab currentSchoolId={currentSchoolId} data={data} canManage={canManage} busy={busy} act={act} load={load} setConfirm={setConfirmState} /></TabsContent>
        <TabsContent value="reports"><ReportsTab accounts={data.accounts} schoolName={data.school.name} /></TabsContent>
      </Tabs>

      <AlertDialog open={!!confirmState} onOpenChange={(o) => !o && setConfirmState(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{confirmState?.title}</AlertDialogTitle>
            <AlertDialogDescription>{confirmState?.description}</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={runConfirm}>{confirmState?.confirmLabel ?? "Confirm"}</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}

/* ═══════════════════════ DASHBOARD ═══════════════════════ */
function DashboardTab({ d, go }: { d: Dashboard; go: (t: string) => void }) {
  const surplus = d.netIncomeMtdCents;
  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4">
        <StatCard icon={<Landmark className="size-4" />} label="Total assets" value={fmtCedi(d.totalAssetsCents, { compact: true })} tone="primary" sub="posted debit balances" />
        <StatCard icon={<Building2 className="size-4" />} label="Total liabilities" value={fmtCedi(d.totalLiabilitiesCents, { compact: true })} sub="payables & statutory" />
        <StatCard icon={<Layers className="size-4" />} label="Total equity" value={fmtCedi(d.totalEquityCents, { compact: true })} sub="capital & surplus" />
        <StatCard icon={<ArrowDownLeft className="size-4" />} label="Revenue MTD" value={fmtCedi(d.revenueMtdCents, { compact: true })} tone="gold" sub="this calendar month" />
        <StatCard icon={<ArrowUpRight className="size-4" />} label="Expenses MTD" value={fmtCedi(d.expensesMtdCents, { compact: true })} sub="this calendar month" />
        <StatCard icon={<Scale className="size-4" />} label="Surplus MTD" value={fmtCedi(Math.abs(surplus), { compact: true })}
          sub={surplus >= 0 ? <span className="text-emerald-600 dark:text-emerald-400">▲ surplus {fmtCedi(surplus, { compact: true })}</span> : <span className="text-red-600 dark:text-red-400">▼ deficit {fmtCedi(surplus, { compact: true })}</span>} />
      </div>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <Card><CardContent className="p-4 flex items-center gap-3">
          <span className="rounded-lg bg-amber-100 dark:bg-amber-950 p-2 text-amber-900 dark:text-amber-200"><FileSpreadsheet className="size-4" /></span>
          <div><p className="text-xs text-muted-foreground">Draft entries</p><p className="text-xl font-semibold tabular-nums">{d.journalCounts.DRAFT ?? 0}</p></div>
        </CardContent></Card>
        <Card><CardContent className="p-4 flex items-center gap-3">
          <span className="rounded-lg bg-emerald-100 dark:bg-emerald-950 p-2 text-emerald-900 dark:text-emerald-200"><CheckCheck className="size-4" /></span>
          <div><p className="text-xs text-muted-foreground">Posted entries</p><p className="text-xl font-semibold tabular-nums">{d.journalCounts.POSTED ?? 0}</p></div>
        </CardContent></Card>
        <Card><CardContent className="p-4 flex items-center gap-3">
          <span className="rounded-lg bg-red-100 dark:bg-red-950 p-2 text-red-900 dark:text-red-200"><FileX2 className="size-4" /></span>
          <div><p className="text-xs text-muted-foreground">Voided entries</p><p className="text-xl font-semibold tabular-nums">{d.journalCounts.VOIDED ?? 0}</p></div>
        </CardContent></Card>
        <Card><CardContent className="p-4 flex items-center gap-3">
          <span className="rounded-lg bg-orange-100 dark:bg-orange-950 p-2 text-orange-900 dark:text-orange-200"><AlertTriangle className="size-4" /></span>
          <div><p className="text-xs text-muted-foreground">Unmatched bank txns</p><p className="text-xl font-semibold tabular-nums">{d.unmatchedTxnCount}</p></div>
        </CardContent></Card>
      </div>

      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-sm">Quick links</CardTitle></CardHeader>
        <CardContent className="flex flex-wrap gap-2">
          <Button size="sm" variant="outline" className="gap-1.5" onClick={() => go("chart")}><BookOpen className="size-4" /> Chart of accounts</Button>
          <Button size="sm" variant="outline" className="gap-1.5" onClick={() => go("journal")}><FileSpreadsheet className="size-4" /> Journal entries</Button>
          <Button size="sm" variant="outline" className="gap-1.5" onClick={() => go("bank")}><Wallet className="size-4" /> Bank accounts · {fmtCedi(d.bankTotalCents, { compact: true })}</Button>
          <Button size="sm" variant="outline" className="gap-1.5" onClick={() => go("reconcile")}><Scale className="size-4" /> Reconciliation</Button>
          <Button size="sm" variant="outline" className="gap-1.5" onClick={() => go("reports")}><FileSpreadsheet className="size-4" /> Statements & reports</Button>
        </CardContent>
      </Card>
    </div>
  );
}

/* ═══════════════════════ CHART OF ACCOUNTS ═══════════════════════ */
function ChartTab({ data, canManage, busy, act, setConfirm }: {
  data: Data; canManage: boolean; busy: boolean;
  act: (a: string, e?: Record<string, unknown>, m?: string) => Promise<unknown>;
  setConfirm: (c: Confirm | null) => void;
}) {
  const [typeFilter, setTypeFilter] = useState<string>("ALL");
  const [dialog, setDialog] = useState<{ open: boolean; editing: Account | null }>({ open: false, editing: null });
  const filtered = data.accounts.filter((a) => typeFilter === "ALL" || a.type === typeFilter);
  const byId = useMemo(() => new Map(data.accounts.map((a) => [a.id, a])), [data.accounts]);

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap items-center gap-2">
        <div className="flex flex-wrap gap-1.5">
          {["ALL", ...ACCT_TYPES].map((t) => (
            <Button key={t} size="sm" variant={typeFilter === t ? "default" : "outline"} className="h-8"
              onClick={() => setTypeFilter(t)}>{t === "ALL" ? "All" : TYPE_BADGE[t as AcctType].label}</Button>
          ))}
        </div>
        <div className="ml-auto">
          {canManage && (
            <Button size="sm" className="gap-1.5" onClick={() => setDialog({ open: true, editing: null })}><Plus className="size-4" /> New account</Button>
          )}
        </div>
      </div>

      {filtered.length === 0 ? (
        <EmptyState icon={<BookOpen className="size-6" />} title="No accounts in this section" hint="Create one with “New account”, or re-run the chart-of-accounts seed." />
      ) : (
        <Card>
          <CardContent className="p-0">
            <div className="max-h-[65vh] overflow-y-auto nice-scroll">
              <Table>
                <TableHeader className="sticky top-0 bg-card z-10">
                  <TableRow>
                    <TableHead className="w-20">Code</TableHead>
                    <TableHead>Account</TableHead>
                    <TableHead className="hidden sm:table-cell">Type</TableHead>
                    <TableHead className="hidden md:table-cell">Parent</TableHead>
                    <TableHead className="text-right">Balance</TableHead>
                    {canManage && <TableHead className="w-24 text-right">Actions</TableHead>}
                  </TableRow>
                </TableHeader>
                {ACCT_TYPES.filter((t) => filtered.some((a) => a.type === t)).map((t) => (
                  <TableBody key={t}>
                    <TableRow className="bg-secondary/50 hover:bg-secondary/50">
                      <TableCell colSpan={canManage ? 6 : 5} className="py-1.5">
                        <span className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold ${TYPE_BADGE[t].className}`}>{TYPE_BADGE[t].label}</span>
                        <span className="text-xs text-muted-foreground ml-2">
                          {filtered.filter((a) => a.type === t).length} account(s) · {fmtCedi(filtered.filter((a) => a.type === t).reduce((s, a) => s + a.balanceCents, 0))}
                        </span>
                      </TableCell>
                    </TableRow>
                    {filtered.filter((a) => a.type === t).sort((a, b) => a.code.localeCompare(b.code)).map((a) => {
                      const parent = a.parentId ? byId.get(a.parentId) : null;
                      return (
                        <TableRow key={a.id} className={a.isActive ? "" : "opacity-50"}>
                          <TableCell className="font-mono text-xs tabular-nums">{a.code}</TableCell>
                          <TableCell>
                            <span className="font-medium">{a.name}</span>
                            {a.isBank && <Badge variant="secondary" className="ml-2 gap-1 text-[10px]"><Wallet className="size-3" />bank</Badge>}
                            {!a.isActive && <Badge variant="outline" className="ml-2 text-[10px]">inactive</Badge>}
                          </TableCell>
                          <TableCell className="hidden sm:table-cell"><span className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ${TYPE_BADGE[a.type].className}`}>{TYPE_BADGE[a.type].label}</span></TableCell>
                          <TableCell className="hidden md:table-cell text-xs text-muted-foreground">{parent ? `${parent.code} · ${parent.name}` : "—"}</TableCell>
                          <TableCell className={`text-right font-mono tabular-nums ${a.balanceCents < 0 ? "text-red-600 dark:text-red-400" : ""}`}>
                            {fmtCedi(a.balanceCents)}
                          </TableCell>
                          {canManage && (
                            <TableCell className="text-right">
                              <div className="inline-flex gap-1">
                                <Button size="icon" variant="ghost" className="size-8" title="Edit account" onClick={() => setDialog({ open: true, editing: a })}><Pencil className="size-4" /></Button>
                                <Button size="icon" variant="ghost" className="size-8 text-red-600 hover:text-red-700" title="Delete account" disabled={busy}
                                  onClick={() => setConfirm({
                                    title: `Delete ${a.code} ${a.name}?`,
                                    description: "Blocked with 409 when journal lines, bank links or sub-accounts reference it.",
                                    action: "delete-account", extra: { id: a.id }, confirmLabel: "Delete",
                                  })}><Trash2 className="size-4" /></Button>
                              </div>
                            </TableCell>
                          )}
                        </TableRow>
                      );
                    })}
                  </TableBody>
                ))}
              </Table>
            </div>
          </CardContent>
        </Card>
      )}

      <AccountDialog key={`${dialog.open ? "o" : "c"}:${dialog.editing?.id ?? "new"}`} open={dialog.open} editing={dialog.editing} accounts={data.accounts} busy={busy}
        onClose={() => setDialog({ open: false, editing: null })}
        onSave={(payload) => act("save-account", payload, dialog.editing ? "Account updated" : "Account created").then(() => setDialog({ open: false, editing: null }))} />
    </div>
  );
}

function AccountDialog({ open, editing, accounts, busy, onClose, onSave }: {
  open: boolean; editing: Account | null; accounts: Account[]; busy: boolean;
  onClose: () => void; onSave: (p: Record<string, unknown>) => void;
}) {
  // remounted by the parent (key) whenever the dialog opens for a different
  // target — so plain useState initialisers give a fresh form every time
  const [f, setF] = useState(() => ({
    code: editing?.code ?? "", name: editing?.name ?? "", type: (editing?.type ?? "ASSET") as AcctType,
    parentId: editing?.parentId ?? "", isBank: editing?.isBank ?? false, isActive: editing?.isActive ?? true,
  }));
  const sameType = accounts.filter((a) => a.type === f.type && a.id !== editing?.id).sort((a, b) => a.code.localeCompare(b.code));
  const valid = f.code.trim() && f.name.trim();

  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{editing ? `Edit ${editing.code} — ${editing.name}` : "New ledger account"}</DialogTitle>
          <DialogDescription>Codes are unique per school; parents must sit in the same tree without cycles.</DialogDescription>
        </DialogHeader>
        <FormGrid cols={2}>
          <FormField label="Code" required hint="e.g. 1210"><Input value={f.code} onChange={(e) => setF({ ...f, code: e.target.value })} placeholder="1210" /></FormField>
          <FormField label="Name" required><Input value={f.name} onChange={(e) => setF({ ...f, name: e.target.value })} placeholder="Stationery & consumables" /></FormField>
          <FormField label="Type" required>
            <Select value={f.type} onValueChange={(v) => setF({ ...f, type: v as AcctType, parentId: "" })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>{ACCT_TYPES.map((t) => <SelectItem key={t} value={t}>{TYPE_BADGE[t].label}</SelectItem>)}</SelectContent>
            </Select>
          </FormField>
          <FormField label="Parent account" hint="optional">
            <Select value={f.parentId || "none"} onValueChange={(v) => setF({ ...f, parentId: v === "none" ? "" : v })}>
              <SelectTrigger><SelectValue placeholder="— none —" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">— none —</SelectItem>
                {sameType.map((a) => <SelectItem key={a.id} value={a.id}>{a.code} · {a.name}</SelectItem>)}
              </SelectContent>
            </Select>
          </FormField>
          <FormField label="Bank account" hint="shows in bank pickers">
            <div className="flex items-center gap-2 pt-1.5"><Switch checked={f.isBank} onCheckedChange={(v) => setF({ ...f, isBank: v })} id="acct-isbank" /><Label htmlFor="acct-isbank" className="text-xs font-normal text-muted-foreground">{f.isBank ? "Yes — bank / wallet" : "No"}</Label></div>
          </FormField>
          <FormField label="Active">
            <div className="flex items-center gap-2 pt-1.5"><Switch checked={f.isActive} onCheckedChange={(v) => setF({ ...f, isActive: v })} id="acct-active" /><Label htmlFor="acct-active" className="text-xs font-normal text-muted-foreground">{f.isActive ? "Active" : "Inactive"}</Label></div>
          </FormField>
        </FormGrid>
        <DialogFooter>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button disabled={!valid || busy} onClick={() => onSave({ id: editing?.id, code: f.code.trim(), name: f.name.trim(), type: f.type, parentId: f.parentId || undefined, isBank: f.isBank, isActive: f.isActive })}>
            {editing ? "Save changes" : "Create account"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ═══════════════════════ JOURNAL ═══════════════════════ */
type EditLine = { accountId: string; memo: string; debit: string; credit: string };
const emptyLine = (): EditLine => ({ accountId: "", memo: "", debit: "", credit: "" });

function JournalTab({ data, canManage, busy, act, setConfirm }: {
  data: Data; canManage: boolean; busy: boolean;
  act: (a: string, e?: Record<string, unknown>, m?: string) => Promise<unknown>;
  setConfirm: (c: Confirm | null) => void;
}) {
  const [filter, setFilter] = useState<string>("ALL");
  const [view, setView] = useState<Journal | null>(null);
  const [editor, setEditor] = useState<{ open: boolean; entry: Journal | null }>({ open: false, entry: null });
  const rows = data.journals.filter((j) => filter === "ALL" || j.status === filter);

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap items-center gap-2">
        <div className="flex flex-wrap gap-1.5">
          {["ALL", "DRAFT", "POSTED", "VOIDED"].map((s) => (
            <Button key={s} size="sm" variant={filter === s ? "default" : "outline"} className="h-8" onClick={() => setFilter(s)}>{s === "ALL" ? "All" : s}</Button>
          ))}
        </div>
        {canManage && <div className="ml-auto"><Button size="sm" className="gap-1.5" onClick={() => setEditor({ open: true, entry: null })}><Plus className="size-4" /> New entry</Button></div>}
      </div>

      {rows.length === 0 ? (
        <EmptyState icon={<FileSpreadsheet className="size-6" />} title="No journal entries" hint={filter === "ALL" ? "Create the first double-entry with “New entry”." : `No ${filter.toLowerCase()} entries yet.`} />
      ) : (
        <Card>
          <CardContent className="p-0">
            <div className="max-h-[65vh] overflow-y-auto nice-scroll">
              <Table>
                <TableHeader className="sticky top-0 bg-card z-10">
                  <TableRow>
                    <TableHead className="w-32">Entry</TableHead>
                    <TableHead className="w-28">Date</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead className="hidden md:table-cell w-24">Ref</TableHead>
                    <TableHead className="w-20">Status</TableHead>
                    <TableHead className="text-right w-28">Amount</TableHead>
                    <TableHead className="w-40 text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {rows.map((j) => (
                    <TableRow key={j.id} className={j.status === "VOIDED" ? "opacity-55" : ""}>
                      <TableCell className="font-mono text-xs">{j.entryNumber}</TableCell>
                      <TableCell className="text-sm">{fmtDate(j.entryDate)}</TableCell>
                      <TableCell><span className={j.status === "VOIDED" ? "line-through" : ""}>{j.description}</span>
                        <span className="block text-xs text-muted-foreground">{j.lines.length} lines · {j.createdByName}</span></TableCell>
                      <TableCell className="hidden md:table-cell text-xs text-muted-foreground">{j.reference ?? "—"}</TableCell>
                      <TableCell><StatusChip status={j.status} /></TableCell>
                      <TableCell className="text-right font-mono tabular-nums">{fmtCedi(j.totalDebitCents)}</TableCell>
                      <TableCell className="text-right">
                        <div className="inline-flex gap-1">
                          <Button size="icon" variant="ghost" className="size-8" title="View lines" onClick={() => setView(j)}><Eye className="size-4" /></Button>
                          {canManage && j.status === "DRAFT" && (
                            <>
                              <Button size="icon" variant="ghost" className="size-8 text-emerald-600 hover:text-emerald-700" title="Post to the ledger" disabled={busy}
                                onClick={() => setConfirm({ title: `Post ${j.entryNumber}?`, description: "Posting makes the entry permanent — debits and credits hit every account balance. Posted entries can only be voided, never edited.", action: "post-journal", extra: { id: j.id }, confirmLabel: "Post entry" })}><CheckCheck className="size-4" /></Button>
                              <Button size="icon" variant="ghost" className="size-8" title="Edit draft" disabled={busy} onClick={() => setEditor({ open: true, entry: j })}><Pencil className="size-4" /></Button>
                              <Button size="icon" variant="ghost" className="size-8 text-red-600 hover:text-red-700" title="Delete draft" disabled={busy}
                                onClick={() => setConfirm({ title: `Delete draft ${j.entryNumber}?`, description: "The draft and its lines are removed. Nothing was ever posted.", action: "delete-journal", extra: { id: j.id }, confirmLabel: "Delete" })}><Trash2 className="size-4" /></Button>
                            </>
                          )}
                          {canManage && j.status === "POSTED" && (
                            <Button size="icon" variant="ghost" className="size-8 text-amber-600 hover:text-amber-700" title="Void entry" disabled={busy}
                              onClick={() => setConfirm({ title: `Void ${j.entryNumber}?`, description: "Voiding keeps the entry visible but inert — its lines drop out of every balance and report.", action: "void-journal", extra: { id: j.id }, confirmLabel: "Void entry" })}><Ban className="size-4" /></Button>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      )}

      {/* view entry */}
      <Dialog open={!!view} onOpenChange={(o) => !o && setView(null)}>
        <DialogContent className="sm:max-w-2xl">
          {view && (
            <>
              <DialogHeader>
                <DialogTitle className="flex items-center gap-2 flex-wrap"><span className="font-mono text-sm">{view.entryNumber}</span> <StatusChip status={view.status} /></DialogTitle>
                <DialogDescription>
                  {fmtDate(view.entryDate)} · {view.description}
                  {view.reference ? ` · ref ${view.reference}` : ""}
                  {view.postedByName ? ` · posted by ${view.postedByName}` : ""}
                </DialogDescription>
              </DialogHeader>
              <div className="max-h-[55vh] overflow-y-auto nice-scroll rounded-lg border">
                <Table>
                  <TableHeader className="sticky top-0 bg-card">
                    <TableRow>
                      <TableHead>Account</TableHead>
                      <TableHead className="hidden sm:table-cell">Memo</TableHead>
                      <TableHead className="text-right w-28">Debit</TableHead>
                      <TableHead className="text-right w-28">Credit</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {view.lines.map((l) => (
                      <TableRow key={l.id}>
                        <TableCell><span className="font-mono text-xs text-muted-foreground">{l.code}</span> {l.name}</TableCell>
                        <TableCell className="hidden sm:table-cell text-xs text-muted-foreground">{l.memo ?? "—"}</TableCell>
                        <TableCell className="text-right font-mono tabular-nums">{l.debitCents ? fmtCedi(l.debitCents) : "—"}</TableCell>
                        <TableCell className="text-right font-mono tabular-nums">{l.creditCents ? fmtCedi(l.creditCents) : "—"}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                  <TableBody>
                    <TableRow className="bg-secondary/40 font-semibold">
                      <TableCell colSpan={2}>Totals</TableCell>
                      <TableCell className="text-right font-mono tabular-nums">{fmtCedi(view.totalDebitCents)}</TableCell>
                      <TableCell className="text-right font-mono tabular-nums">{fmtCedi(view.totalCreditCents)}</TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
              <div className="flex items-center justify-between">
                {view.balanced
                  ? <Badge className="gap-1 bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"><CheckCircle2 className="size-3.5" /> Balanced — Σdebit = Σcredit</Badge>
                  : <Badge variant="destructive" className="gap-1"><AlertTriangle className="size-3.5" /> Unbalanced by {fmtCedi(Math.abs(view.totalDebitCents - view.totalCreditCents))}</Badge>}
                {canManage && view.status === "DRAFT" && (
                  <Button size="sm" variant="outline" className="gap-1.5" onClick={() => { setEditor({ open: true, entry: view }); setView(null); }}><Pencil className="size-4" /> Edit draft</Button>
                )}
              </div>
            </>
          )}
        </DialogContent>
      </Dialog>

      <JournalEditor key={`${editor.open ? "o" : "c"}:${editor.entry?.id ?? "new"}`} open={editor.open} entry={editor.entry} accounts={data.accounts} busy={busy}
        onClose={() => setEditor({ open: false, entry: null })}
        onSave={(payload) => act("save-journal", payload, editor.entry ? "Draft updated" : "Draft saved").then(() => setEditor({ open: false, entry: null }))} />
    </div>
  );
}

function JournalEditor({ open, entry, accounts, busy, onClose, onSave }: {
  open: boolean; entry: Journal | null; accounts: Account[]; busy: boolean;
  onClose: () => void; onSave: (p: Record<string, unknown>) => void;
}) {
  // remounted by the parent (key) whenever the dialog opens for a different entry
  const [f, setF] = useState(() => ({ date: isoDate(entry?.entryDate) || todayISO(), description: entry?.description ?? "", reference: entry?.reference ?? "" }));
  const [lines, setLines] = useState<EditLine[]>(() =>
    entry?.lines.length
      ? entry.lines.map((l) => ({ accountId: l.accountId, memo: l.memo ?? "", debit: l.debitCents ? (l.debitCents / 100).toString() : "", credit: l.creditCents ? (l.creditCents / 100).toString() : "" }))
      : [emptyLine(), emptyLine()]);

  const sumD = lines.reduce((s, l) => s + toCents(l.debit), 0);
  const sumC = lines.reduce((s, l) => s + toCents(l.credit), 0);
  const diff = sumD - sumC;
  const linesValid = lines.length >= 2 && lines.every((l) => l.accountId && ((toCents(l.debit) > 0) !== (toCents(l.credit) > 0)));
  const canSave = !!f.description.trim() && !!f.date && linesValid && diff === 0;
  const setLine = (i: number, patch: Partial<EditLine>) => setLines((ls) => ls.map((l, x) => (x === i ? { ...l, ...patch } : l)));

  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="sm:max-w-3xl">
        <DialogHeader>
          <DialogTitle>{entry ? `Edit draft ${entry.entryNumber}` : "New journal entry"}</DialogTitle>
          <DialogDescription>Saved as DRAFT — nothing hits the books until you post it. Debits must equal credits.</DialogDescription>
        </DialogHeader>
        <FormGrid cols={3}>
          <FormField label="Date" required><DateField mode="date" value={f.date} onChange={(v) => setF({ ...f, date: v ?? "" })} aria-label="Entry date" /></FormField>
          <FormField label="Description" required span={2}><Input value={f.description} onChange={(e) => setF({ ...f, description: e.target.value })} placeholder="e.g. Fees received — cash batch" /></FormField>
          <FormField label="Reference" hint="receipt / PO / payslip"><Input value={f.reference} onChange={(e) => setF({ ...f, reference: e.target.value })} placeholder="RBT-014" /></FormField>
        </FormGrid>

        <div className="rounded-lg border max-h-[45vh] overflow-y-auto nice-scroll">
          <Table>
            <TableHeader className="sticky top-0 bg-card z-10">
              <TableRow>
                <TableHead>Account</TableHead>
                <TableHead className="hidden md:table-cell min-w-32">Memo</TableHead>
                <TableHead className="w-28">Debit (₵)</TableHead>
                <TableHead className="w-28">Credit (₵)</TableHead>
                <TableHead className="w-10" />
              </TableRow>
            </TableHeader>
            <TableBody>
              {lines.map((l, i) => (
                <TableRow key={i}>
                  <TableCell>
                    <Select value={l.accountId} onValueChange={(v) => setLine(i, { accountId: v })}>
                      <SelectTrigger className="h-8 text-xs"><SelectValue placeholder="Pick account" /></SelectTrigger>
                      <SelectContent>
                        {ACCT_TYPES.map((t) => (
                          <SelectGroup key={t}>
                            <SelectLabel className={`px-2 py-1 text-[10px] font-semibold ${TYPE_BADGE[t].className}`}>{TYPE_BADGE[t].label}</SelectLabel>
                            {accounts.filter((a) => a.type === t).map((a) => <SelectItem key={a.id} value={a.id}>{a.code} · {a.name}</SelectItem>)}
                          </SelectGroup>
                        ))}
                      </SelectContent>
                    </Select>
                  </TableCell>
                  <TableCell className="hidden md:table-cell"><Input className="h-8 text-xs" value={l.memo} onChange={(e) => setLine(i, { memo: e.target.value })} placeholder="—" /></TableCell>
                  <TableCell><Input className="h-8 text-xs font-mono" inputMode="decimal" value={l.debit} placeholder="0.00"
                    onChange={(e) => setLine(i, { debit: e.target.value, credit: "" })} /></TableCell>
                  <TableCell><Input className="h-8 text-xs font-mono" inputMode="decimal" value={l.credit} placeholder="0.00"
                    onChange={(e) => setLine(i, { credit: e.target.value, debit: "" })} /></TableCell>
                  <TableCell>
                    <Button size="icon" variant="ghost" className="size-7 text-red-600" title="Remove line" disabled={lines.length <= 2}
                      onClick={() => setLines((ls) => ls.filter((_, x) => x !== i))}><Trash2 className="size-3.5" /></Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <Button size="sm" variant="outline" className="gap-1.5" onClick={() => setLines((ls) => [...ls, emptyLine()])}><Plus className="size-4" /> Add line</Button>
          <div className="ml-auto flex flex-wrap items-center gap-3 text-sm tabular-nums">
            <span className="text-muted-foreground">Σ debit <strong className="font-mono">{fmtCedi(sumD)}</strong></span>
            <span className="text-muted-foreground">Σ credit <strong className="font-mono">{fmtCedi(sumC)}</strong></span>
            {diff === 0
              ? <Badge className="gap-1 bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"><CheckCircle2 className="size-3.5" /> balanced</Badge>
              : <Badge variant="destructive" className="gap-1"><AlertTriangle className="size-3.5" /> off by {fmtCedi(Math.abs(diff))}</Badge>}
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button disabled={!canSave || busy} onClick={() => onSave({
            id: entry?.id, entryDate: f.date, description: f.description.trim(), reference: f.reference.trim() || undefined,
            lines: lines.map((l) => ({ accountId: l.accountId, memo: l.memo.trim() || undefined, debitCents: toCents(l.debit), creditCents: toCents(l.credit) })),
          })}>{entry ? "Save changes" : "Save draft"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ═══════════════════════ BANK ═══════════════════════ */
function BankTab({ currentSchoolId, data, canManage, busy, act, load, setConfirm }: {
  currentSchoolId: string | null; data: Data; canManage: boolean; busy: boolean;
  act: (a: string, e?: Record<string, unknown>, m?: string) => Promise<unknown>;
  load: () => void; setConfirm: (c: Confirm | null) => void;
}) {
  const [selectedId, setSelectedId] = useState<string | null>(null);
  // derived selection: falls back to the first bank when unset or deleted
  const selected = selectedId && data.bankAccounts.some((b) => b.id === selectedId) ? selectedId : (data.bankAccounts[0]?.id ?? null);
  const [txnState, setTxnState] = useState<{ bankId: string; txns: BTxn[] } | null>(null);
  const [bankDialog, setBankDialog] = useState<{ open: boolean; editing: BankAcct | null }>({ open: false, editing: null });
  const [txnDialog, setTxnDialog] = useState(false);
  const bank = data.bankAccounts.find((b) => b.id === selected) ?? null;
  const txns = txnState?.bankId === selected ? txnState.txns : [];
  const loadingTxns = txnState?.bankId !== selected;

  const loadTxns = useCallback((bankId: string) => {
    apiGet<{ bankTxns: BTxn[] }>(`/api/school/${currentSchoolId}/ops/accounts?bankAccountId=${bankId}`)
      .then((d) => setTxnState({ bankId, txns: d.bankTxns ?? [] }))
      .catch((e) => { toast({ title: "Load failed", description: e.message, variant: "destructive" }); setTxnState({ bankId, txns: [] }); });
  }, [currentSchoolId]);
  useEffect(() => { if (selected) loadTxns(selected); }, [selected, loadTxns]);

  const masked = (n: string | null) => (n ? `••••${n.slice(-4)}` : "—");

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center gap-2">
        <h2 className="text-sm font-semibold text-muted-foreground">Bank & wallet accounts</h2>
        {canManage && <div className="ml-auto"><Button size="sm" className="gap-1.5" onClick={() => setBankDialog({ open: true, editing: null })}><Plus className="size-4" /> Add account</Button></div>}
      </div>

      {data.bankAccounts.length === 0 ? (
        <EmptyState icon={<Wallet className="size-6" />} title="No bank accounts yet" hint="Add the school's bank account and MoMo wallets — they feed the reconciliation workspace." />
      ) : (
        <div className="grid gap-3 sm:gap-4 md:grid-cols-2 xl:grid-cols-3">
          {data.bankAccounts.map((b) => (
            <Card key={b.id} className={`cursor-pointer transition-all hover:shadow-md ${selected === b.id ? "ring-2 ring-primary" : ""} ${b.isActive ? "" : "opacity-60"}`}
              onClick={() => setSelectedId(b.id)}>
              <CardContent className="p-4 space-y-2">
                <div className="flex items-start justify-between gap-2">
                  <div>
                    <p className="font-semibold flex items-center gap-1.5"><Landmark className="size-4 text-muted-foreground" /> {b.name}</p>
                    <p className="text-xs text-muted-foreground">{b.bankName ?? "—"} · {masked(b.accountNumber)}</p>
                  </div>
                  {b.isActive ? <Badge variant="secondary">active</Badge> : <Badge variant="outline">inactive</Badge>}
                </div>
                <div className="flex items-baseline justify-between">
                  <span className="text-xs text-muted-foreground">Current balance</span>
                  <span className={`font-mono font-semibold tabular-nums ${b.currentBalanceCents < 0 ? "text-red-600 dark:text-red-400" : ""}`}>{fmtCedi(b.currentBalanceCents)}</span>
                </div>
                <div className="text-xs text-muted-foreground flex flex-wrap gap-x-3 gap-y-1">
                  <span>opening {fmtCedi(b.openingBalanceCents)}</span>
                  <span>{b.txnCount} txns</span>
                  <span>{b.reconCount} recons</span>
                </div>
                <div className="flex items-center justify-between gap-2 pt-1">
                  <span className="text-xs text-muted-foreground truncate">
                    {b.chartAccount ? <>ledger <span className="font-mono">{b.chartAccount.code}</span> · {b.chartAccount.name}</> : "no linked ledger account"}
                  </span>
                  {canManage && (
                    <div className="inline-flex gap-1 shrink-0" onClick={(e) => e.stopPropagation()}>
                      <Button size="icon" variant="ghost" className="size-7" title="Edit account" onClick={() => setBankDialog({ open: true, editing: b })}><Pencil className="size-3.5" /></Button>
                      <Button size="icon" variant="ghost" className="size-7 text-red-600 hover:text-red-700" title="Delete account" disabled={busy}
                        onClick={() => setConfirm({ title: `Delete ${b.name}?`, description: "Refused with 409 while transactions or reconciliations exist on the account.", action: "delete-bank-account", extra: { id: b.id }, confirmLabel: "Delete" })}><Trash2 className="size-3.5" /></Button>
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {bank && (
        <Card>
          <CardHeader className="pb-3">
            <div className="flex flex-wrap items-center gap-2">
              <CardTitle className="text-sm">Transaction feed — {bank.name}</CardTitle>
              <Badge variant="secondary" className="tabular-nums">{fmtCedi(bank.currentBalanceCents)}</Badge>
              {bank.openReconId && <Badge className="gap-1 bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200"><Scale className="size-3" /> open reconciliation</Badge>}
              {canManage && (
                <Button size="sm" className="gap-1.5 ml-auto" onClick={() => setTxnDialog(true)}><Plus className="size-4" /> Add transaction</Button>
              )}
            </div>
          </CardHeader>
          <CardContent className="p-0">
            {loadingTxns ? <div className="p-6 text-sm text-muted-foreground text-center">Loading transactions…</div> : txns.length === 0 ? (
              <EmptyState icon={<ArrowDownLeft className="size-6" />} title="No transactions yet" hint="Record deposits and withdrawals as they appear on the bank statement." />
            ) : (
              <div className="max-h-[55vh] overflow-y-auto nice-scroll">
                <Table>
                  <TableHeader className="sticky top-0 bg-card z-10">
                    <TableRow>
                      <TableHead className="w-28">Date</TableHead>
                      <TableHead className="w-20">Flow</TableHead>
                      <TableHead className="text-right w-28">Amount</TableHead>
                      <TableHead>Description</TableHead>
                      <TableHead className="hidden md:table-cell w-20">Ref</TableHead>
                      <TableHead className="hidden lg:table-cell w-24">Source</TableHead>
                      <TableHead className="w-24">Status</TableHead>
                      {canManage && <TableHead className="w-10" />}
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {txns.map((t) => (
                      <TableRow key={t.id}>
                        <TableCell className="text-sm">{fmtDate(t.date)}</TableCell>
                        <TableCell><DirChip d={t.direction} /></TableCell>
                        <TableCell className={`text-right font-mono tabular-nums ${t.direction === "DEPOSIT" ? "text-emerald-700 dark:text-emerald-400" : "text-red-700 dark:text-red-400"}`}>
                          {t.direction === "DEPOSIT" ? "+" : "−"}{fmtCedi(t.amountCents)}
                        </TableCell>
                        <TableCell className="text-sm">{t.description}</TableCell>
                        <TableCell className="hidden md:table-cell text-xs text-muted-foreground">{t.reference ?? "—"}</TableCell>
                        <TableCell className="hidden lg:table-cell"><Badge variant="outline" className="text-[10px]">{t.source}</Badge></TableCell>
                        <TableCell>{t.reconciledAt ? <Badge className="gap-1 bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"><CheckCircle2 className="size-3" /> reconciled</Badge> : <Badge variant="outline" className="text-[10px]">unmatched</Badge>}</TableCell>
                        {canManage && (
                          <TableCell>
                            <Button size="icon" variant="ghost" className="size-7 text-red-600" title="Delete transaction" disabled={busy}
                              onClick={() => setConfirm({ title: "Delete this transaction?", description: t.description, action: "delete-bank-txn", extra: { id: t.id }, confirmLabel: "Delete" })}><Trash2 className="size-3.5" /></Button>
                          </TableCell>
                        )}
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            )}
          </CardContent>
        </Card>
      )}

      <BankDialog key={`${bankDialog.open ? "o" : "c"}:${bankDialog.editing?.id ?? "new"}`} open={bankDialog.open} editing={bankDialog.editing} accounts={data.accounts} busy={busy}
        onClose={() => setBankDialog({ open: false, editing: null })}
        onSave={(p) => act("save-bank-account", p, bankDialog.editing ? "Bank account updated" : "Bank account added").then(() => { setBankDialog({ open: false, editing: null }); loadTxns(selected ?? ""); })} />

      <TxnDialog key={`${txnDialog ? "o" : "c"}:${bank?.id ?? "none"}`} open={txnDialog} busy={busy} bank={bank}
        onClose={() => setTxnDialog(false)}
        onSave={(p) => act("add-bank-txn", p, "Transaction recorded").then(() => { setTxnDialog(false); loadTxns(selected ?? ""); load(); })} />
    </div>
  );
}

function BankDialog({ open, editing, accounts, busy, onClose, onSave }: {
  open: boolean; editing: BankAcct | null; accounts: Account[]; busy: boolean;
  onClose: () => void; onSave: (p: Record<string, unknown>) => void;
}) {
  // remounted by the parent (key) whenever the dialog opens for a different account
  const [f, setF] = useState(() => ({
    name: editing?.name ?? "", bankName: editing?.bankName ?? "", accountNumber: editing?.accountNumber ?? "",
    chartAccountId: editing?.chartAccountId ?? "", opening: editing ? fromCents(editing.openingBalanceCents) : "", isActive: editing?.isActive ?? true,
  }));
  const sorted = [...accounts].sort((a, b) => (b.isBank ? 1 : 0) - (a.isBank ? 1 : 0) || a.code.localeCompare(b.code));

  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{editing ? `Edit ${editing.name}` : "New bank / wallet account"}</DialogTitle>
          <DialogDescription>Feeds the reconciliation workspace. Linking a ledger account is recommended but optional.</DialogDescription>
        </DialogHeader>
        <FormGrid cols={2}>
          <FormField label="Account name" required span={2}><Input value={f.name} onChange={(e) => setF({ ...f, name: e.target.value })} placeholder="Main Bank — GCB" /></FormField>
          <FormField label="Bank / provider"><Input value={f.bankName} onChange={(e) => setF({ ...f, bankName: e.target.value })} placeholder="GCB Bank / MTN Ghana" /></FormField>
          <FormField label="Account number" hint="shown masked"><Input value={f.accountNumber} onChange={(e) => setF({ ...f, accountNumber: e.target.value })} placeholder="1041…" /></FormField>
          <FormField label="Linked ledger account" span={2} hint="bank-flagged accounts listed first">
            <Select value={f.chartAccountId || "none"} onValueChange={(v) => setF({ ...f, chartAccountId: v === "none" ? "" : v })}>
              <SelectTrigger><SelectValue placeholder="— none —" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">— none —</SelectItem>
                {sorted.map((a) => <SelectItem key={a.id} value={a.id}>{a.code} · {a.name}{a.isBank ? " (bank)" : ""}</SelectItem>)}
              </SelectContent>
            </Select>
          </FormField>
          <FormField label="Opening balance (₵)"><Input inputMode="decimal" value={f.opening} onChange={(e) => setF({ ...f, opening: e.target.value })} placeholder="12000.00" /></FormField>
          <FormField label="Active">
            <div className="flex items-center gap-2 pt-1.5"><Switch checked={f.isActive} onCheckedChange={(v) => setF({ ...f, isActive: v })} id="bank-active" /><Label htmlFor="bank-active" className="text-xs font-normal text-muted-foreground">{f.isActive ? "Active" : "Inactive"}</Label></div>
          </FormField>
        </FormGrid>
        <DialogFooter>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button disabled={!f.name.trim() || busy} onClick={() => onSave({
            id: editing?.id, name: f.name.trim(), bankName: f.bankName.trim() || undefined, accountNumber: f.accountNumber.trim() || undefined,
            chartAccountId: f.chartAccountId || undefined, openingBalanceCents: toCents(f.opening), isActive: f.isActive,
          })}>{editing ? "Save changes" : "Add account"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function TxnDialog({ open, busy, bank, onClose, onSave }: {
  open: boolean; busy: boolean; bank: BankAcct | null;
  onClose: () => void; onSave: (p: Record<string, unknown>) => void;
}) {
  // remounted by the parent (key) whenever it opens — fresh form every time
  const [f, setF] = useState(() => ({ date: todayISO(), direction: "DEPOSIT", amount: "", reference: "", description: "", source: "MANUAL" }));
  const amountCents = toCents(f.amount);

  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Record transaction — {bank?.name ?? ""}</DialogTitle>
          <DialogDescription>Bank txns are the reconciliation feed; they do not auto-post journal entries (posting stays manual by design).</DialogDescription>
        </DialogHeader>
        <FormGrid cols={2}>
          <FormField label="Date" required><DateField mode="date" value={f.date} onChange={(v) => setF({ ...f, date: v ?? "" })} aria-label="Transaction date" /></FormField>
          <FormField label="Direction" required>
            <Select value={f.direction} onValueChange={(v) => setF({ ...f, direction: v })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="DEPOSIT">Deposit — money in</SelectItem>
                <SelectItem value="WITHDRAWAL">Withdrawal — money out</SelectItem>
              </SelectContent>
            </Select>
          </FormField>
          <FormField label="Amount (₵)" required><Input inputMode="decimal" value={f.amount} onChange={(e) => setF({ ...f, amount: e.target.value })} placeholder="450.00" /></FormField>
          <FormField label="Reference"><Input value={f.reference} onChange={(e) => setF({ ...f, reference: e.target.value })} placeholder="TRF-015" /></FormField>
          <FormField label="Description" required span={2}><Input value={f.description} onChange={(e) => setF({ ...f, description: e.target.value })} placeholder="ECG electricity bill" /></FormField>
          <FormField label="Source">
            <Select value={f.source} onValueChange={(v) => setF({ ...f, source: v })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="MANUAL">Manual</SelectItem>
                <SelectItem value="PAYMENT">Payment</SelectItem>
                <SelectItem value="EXPENSE">Expense</SelectItem>
                <SelectItem value="FEES">Fees</SelectItem>
              </SelectContent>
            </Select>
          </FormField>
        </FormGrid>
        <DialogFooter>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button disabled={!f.description.trim() || amountCents <= 0 || !f.date || busy} onClick={() => onSave({
            bankAccountId: bank?.id, date: f.date, direction: f.direction, amountCents,
            reference: f.reference.trim() || undefined, description: f.description.trim(), source: f.source,
          })}>Record transaction</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ═══════════════════════ RECONCILE ═══════════════════════ */
function ReconcileTab({ currentSchoolId, data, canManage, busy, act, load, setConfirm }: {
  currentSchoolId: string | null; data: Data; canManage: boolean; busy: boolean;
  act: (a: string, e?: Record<string, unknown>, m?: string) => Promise<unknown>;
  load: () => void; setConfirm: (c: Confirm | null) => void;
}) {
  const [bankSel, setBankSel] = useState<string | null>(null);
  const [reconId, setReconId] = useState<string | null>(null);
  const [wsState, setWsState] = useState<{ id: string; ws: ReconDetail } | null>(null);
  const [newReconOpen, setNewReconOpen] = useState(false);
  const [itemOpen, setItemOpen] = useState(false);

  const banks = data.bankAccounts.filter((b) => b.isActive);
  // derived bank pick: falls back to the first active account when unset/inactive
  const effectiveBank = bankSel && banks.some((b) => b.id === bankSel) ? bankSel : (banks[0]?.id ?? null);
  const bank = data.bankAccounts.find((b) => b.id === effectiveBank) ?? null;
  const recons = data.reconciliations.filter((r) => r.bankAccountId === effectiveBank);
  // a deleted reconciliation falls out of the refreshed list — drop the selection
  if (reconId && !data.reconciliations.some((r) => r.id === reconId)) setReconId(null);

  const ws = wsState?.id === reconId ? wsState.ws : null;
  const loadingWs = !!reconId && wsState?.id !== reconId;
  const loadWs = useCallback((id: string) => {
    apiGet<ReconDetail>(`/api/school/${currentSchoolId}/ops/accounts?action=recon&id=${id}`)
      .then((w) => setWsState({ id, ws: w }))
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  }, [currentSchoolId]);
  useEffect(() => { if (reconId) loadWs(reconId); }, [reconId, loadWs]);

  const refresh = () => { load(); if (reconId) loadWs(reconId); };
  const items = ws?.reconciliation.items ?? [];
  const unmatched = items.filter((i) => !i.matched).length;
  const matchable = (ws?.systemTxns ?? []).filter((t) => !t.inUse);

  async function runAction(action: string, extra: Record<string, unknown>, okMsg?: string) {
    const res = await act(action, extra, okMsg);
    if (res !== null) refresh();
    return res;
  }

  if (banks.length === 0) return <EmptyState icon={<Scale className="size-6" />} title="No active bank accounts" hint="Add a bank account first — reconciliation works off its transaction feed." />;

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-end gap-3">
        <div className="w-full sm:w-72">
          <Label className="text-xs text-muted-foreground">Bank account</Label>
          <Select value={effectiveBank ?? ""} onValueChange={(v) => { setBankSel(v); setReconId(null); }}>
            <SelectTrigger className="mt-1.5"><SelectValue /></SelectTrigger>
            <SelectContent>{banks.map((b) => <SelectItem key={b.id} value={b.id}>{b.name}</SelectItem>)}</SelectContent>
          </Select>
        </div>
        {canManage && (
          <Button size="sm" className="gap-1.5 ml-auto" disabled={!bank || !!bank.openReconId}
            title={bank?.openReconId ? "Complete or delete the open reconciliation first" : undefined}
            onClick={() => setNewReconOpen(true)}><Plus className="size-4" /> New reconciliation</Button>
        )}
      </div>

      {/* reconciliation history */}
      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-sm">Reconciliations — {bank?.name}</CardTitle></CardHeader>
        <CardContent className="p-0">
          {recons.length === 0 ? <div className="p-6"><EmptyState icon={<Scale className="size-6" />} title="No reconciliations yet" hint="Start one from the bank statement's closing balance, then match statement lines to system transactions." /></div> : (
            <div className="max-h-72 overflow-y-auto nice-scroll">
              <Table>
                <TableHeader className="sticky top-0 bg-card z-10">
                  <TableRow>
                    <TableHead className="w-28">Statement date</TableHead>
                    <TableHead className="text-right w-32">End balance</TableHead>
                    <TableHead className="w-24">Status</TableHead>
                    <TableHead className="w-28">Progress</TableHead>
                    <TableHead className="hidden md:table-cell">Created by</TableHead>
                    <TableHead className="w-24 text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {recons.map((r) => (
                    <TableRow key={r.id} className={`cursor-pointer ${reconId === r.id ? "bg-secondary/60" : ""}`} onClick={() => setReconId(r.id === reconId ? null : r.id)}>
                      <TableCell className="text-sm">{fmtDate(r.statementDate)}</TableCell>
                      <TableCell className="text-right font-mono tabular-nums">{fmtCedi(r.statementEndCents)}</TableCell>
                      <TableCell>{r.status === "IN_PROGRESS"
                        ? <Badge className="bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200">IN PROGRESS</Badge>
                        : <Badge className="bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">COMPLETED</Badge>}</TableCell>
                      <TableCell className="text-xs tabular-nums">{r.matchedCount}/{r.itemCount} matched</TableCell>
                      <TableCell className="hidden md:table-cell text-xs text-muted-foreground">{r.createdByName}{r.completedAt ? ` · ${fmtDate(r.completedAt)}` : ""}</TableCell>
                      <TableCell className="text-right">
                        <div className="inline-flex gap-1" onClick={(e) => e.stopPropagation()}>
                          <Button size="sm" variant="outline" className="h-7 text-xs" onClick={() => setReconId(r.id)}>Open</Button>
                          {canManage && r.status === "IN_PROGRESS" && (
                            <Button size="icon" variant="ghost" className="size-7 text-red-600" title="Delete reconciliation" disabled={busy}
                              onClick={() => setConfirm({ title: "Delete this reconciliation?", description: "Statement lines are removed with it. Completed reconciliations are kept as history.", action: "delete-reconciliation", extra: { id: r.id }, confirmLabel: "Delete" })}><Trash2 className="size-3.5" /></Button>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>

      {/* workspace */}
      {reconId && (loadingWs || !ws ? <LoadingBlock label="Loading workspace…" /> : (
        <div className="space-y-4">
          <Card>
            <CardContent className="p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              <div><p className="text-xs text-muted-foreground">Statement date</p><p className="font-semibold">{fmtDate(ws.reconciliation.statementDate)}</p></div>
              <div><p className="text-xs text-muted-foreground">Statement end balance</p><p className="font-mono font-semibold tabular-nums">{fmtCedi(ws.reconciliation.statementEndCents)}</p></div>
              <div><p className="text-xs text-muted-foreground">System expected balance</p><p className="font-mono font-semibold tabular-nums">{fmtCedi(ws.expectedCents)}</p></div>
              <div>
                <p className="text-xs text-muted-foreground">Difference</p>
                {ws.differenceCents === 0
                  ? <Badge className="gap-1 bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"><CheckCircle2 className="size-3.5" /> {fmtCedi(0)} — agrees</Badge>
                  : <Badge variant="destructive" className="gap-1"><AlertTriangle className="size-3.5" /> off by {fmtCedi(Math.abs(ws.differenceCents))}</Badge>}
              </div>
              <div className="sm:col-span-2 lg:col-span-4 flex flex-wrap gap-2 items-center pt-1">
                <Badge variant="secondary" className="tabular-nums">{items.length - unmatched}/{items.length} lines matched</Badge>
                {canManage && ws.reconciliation.status === "IN_PROGRESS" && (
                  <>
                    <Button size="sm" variant="outline" className="gap-1.5" disabled={busy || items.length === 0}
                      onClick={() => runAction("auto-match", { reconciliationId: reconId }).then((r: unknown) => {
                        const n = (r as { matched?: number } | null)?.matched ?? 0;
                        toast({ title: n ? `Auto-matched ${n} line${n === 1 ? "" : "s"}` : "No auto-matches found", description: n ? "Amount + direction, dates within ±3 days first, then amount only." : "Try matching the remaining lines by hand." });
                      })}><Wand2 className="size-4" /> Auto-match</Button>
                    <Button size="sm" variant="outline" className="gap-1.5" onClick={() => setItemOpen(true)}><Plus className="size-4" /> Add statement line</Button>
                    <Button size="sm" className="gap-1.5 ml-auto" disabled={busy || unmatched > 0}
                      title={unmatched > 0 ? `${unmatched} statement line(s) still unmatched` : undefined}
                      onClick={() => runAction("complete-reconciliation", { reconciliationId: reconId }, "Reconciliation completed")}>
                      <CheckCheck className="size-4" /> Complete reconciliation</Button>
                  </>
                )}
                {ws.reconciliation.status === "COMPLETED" && (
                  <Badge className="gap-1 bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200 ml-auto"><CheckCircle2 className="size-3.5" /> completed {ws.reconciliation.completedAt ? fmtDate(ws.reconciliation.completedAt) : ""}</Badge>
                )}
              </div>
            </CardContent>
          </Card>

          <div className="grid gap-4 lg:grid-cols-2">
            {/* statement lines */}
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-sm">Bank statement lines</CardTitle></CardHeader>
              <CardContent className="p-0">
                {items.length === 0 ? <div className="p-6"><EmptyState icon={<FileSpreadsheet className="size-6" />} title="No statement lines" hint="Add the lines from the paper statement, then match each to a system transaction." /></div> : (
                  <div className="max-h-[52vh] overflow-y-auto nice-scroll">
                    <Table>
                      <TableHeader className="sticky top-0 bg-card z-10">
                        <TableRow>
                          <TableHead className="w-24">Date</TableHead>
                          <TableHead>Description</TableHead>
                          <TableHead className="text-right w-28">Amount</TableHead>
                          <TableHead className="w-44">Match</TableHead>
                          {canManage && ws.reconciliation.status === "IN_PROGRESS" && <TableHead className="w-10" />}
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {items.map((it) => (
                          <TableRow key={it.id} className={it.matched ? "bg-emerald-50/60 dark:bg-emerald-950/30" : ""}>
                            <TableCell className="text-sm">{fmtDate(it.date)}</TableCell>
                            <TableCell className="text-sm">
                              {it.description}
                              {it.bankTxn && <span className="block text-[11px] text-muted-foreground">↔ {it.bankTxn.direction === "DEPOSIT" ? "+" : "−"}{fmtCedi(it.bankTxn.amountCents)} · {it.bankTxn.description}</span>}
                            </TableCell>
                            <TableCell className={`text-right font-mono tabular-nums ${it.amountCents >= 0 ? "text-emerald-700 dark:text-emerald-400" : "text-red-700 dark:text-red-400"}`}>
                              {it.amountCents >= 0 ? "+" : "−"}{fmtCedi(Math.abs(it.amountCents))}
                            </TableCell>
                            <TableCell>
                              {it.matched ? (
                                <div className="flex items-center gap-1.5">
                                  <Badge className="gap-1 bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"><CheckCircle2 className="size-3" /> matched</Badge>
                                  {canManage && ws.reconciliation.status === "IN_PROGRESS" && (
                                    <Button size="sm" variant="ghost" className="h-6 px-2 text-xs" disabled={busy} onClick={() => runAction("unmatch-item", { itemId: it.id }, "Unmatched")}>undo</Button>
                                  )}
                                </div>
                              ) : canManage && ws.reconciliation.status === "IN_PROGRESS" ? (
                                <Select value="" onValueChange={(v) => v && runAction("match-item", { itemId: it.id, bankTxnId: v }, "Linked — matched flips on when amounts agree")}>
                                  <SelectTrigger className="h-8 text-xs"><SelectValue placeholder="Link a system txn…" /></SelectTrigger>
                                  <SelectContent>
                                    {matchable.map((t) => (
                                      <SelectItem key={t.id} value={t.id}>
                                        {t.direction === "DEPOSIT" ? "+" : "−"}{fmtCedi(t.amountCents)} · {isoDate(t.date)} · {t.description.slice(0, 28)}
                                      </SelectItem>
                                    ))}
                                  </SelectContent>
                                </Select>
                              ) : <Badge variant="outline" className="text-[10px]">unmatched</Badge>}
                            </TableCell>
                            {canManage && ws.reconciliation.status === "IN_PROGRESS" && (
                              <TableCell>
                                <Button size="icon" variant="ghost" className="size-7 text-red-600" title="Remove line" disabled={busy}
                                  onClick={() => runAction("remove-item", { itemId: it.id }, "Statement line removed")}><Trash2 className="size-3.5" /></Button>
                              </TableCell>
                            )}
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* system txns */}
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-sm">System transactions — unreconciled ({ws.systemTxns.length})</CardTitle></CardHeader>
              <CardContent className="p-0">
                {ws.systemTxns.length === 0 ? <div className="p-6"><EmptyState icon={<CheckCircle2 className="size-6" />} title="All caught up" hint="Every transaction on this account is reconciled." /></div> : (
                  <div className="max-h-[52vh] overflow-y-auto nice-scroll">
                    <Table>
                      <TableHeader className="sticky top-0 bg-card z-10">
                        <TableRow>
                          <TableHead className="w-24">Date</TableHead>
                          <TableHead className="w-20">Flow</TableHead>
                          <TableHead className="text-right w-28">Amount</TableHead>
                          <TableHead>Description</TableHead>
                          <TableHead className="w-24">State</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {ws.systemTxns.map((t) => (
                          <TableRow key={t.id} className={t.inUse ? "bg-secondary/40" : ""}>
                            <TableCell className="text-sm">{fmtDate(t.date)}</TableCell>
                            <TableCell><DirChip d={t.direction} /></TableCell>
                            <TableCell className="text-right font-mono tabular-nums">{fmtCedi(t.amountCents)}</TableCell>
                            <TableCell className="text-sm">{t.description}{t.reference ? <span className="block text-[11px] text-muted-foreground">{t.reference}</span> : null}</TableCell>
                            <TableCell>{t.inUse ? <Badge variant="secondary" className="text-[10px]">in use</Badge> : <Badge variant="outline" className="text-[10px]">available</Badge>}</TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </div>
      ))}

      <NewReconDialog key={`${newReconOpen ? "o" : "c"}:${bank?.id ?? "none"}`} open={newReconOpen} busy={busy} bank={bank} onClose={() => setNewReconOpen(false)}
        onSave={(p) => act("create-reconciliation", p, "Reconciliation started").then((r) => { if (r) { setNewReconOpen(false); load(); } })} />
      <ItemDialog key={`${itemOpen ? "o" : "c"}:${reconId ?? "none"}`} open={itemOpen} busy={busy} reconId={reconId} onClose={() => setItemOpen(false)}
        onSave={(p) => runAction("add-statement-item", p, "Statement line added").then(() => setItemOpen(false))} />
    </div>
  );
}

function NewReconDialog({ open, busy, bank, onClose, onSave }: {
  open: boolean; busy: boolean; bank: BankAcct | null;
  onClose: () => void; onSave: (p: Record<string, unknown>) => void;
}) {
  // remounted by the parent (key) whenever it opens — fresh form every time
  const [f, setF] = useState(() => ({ date: todayISO(), end: "" }));
  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>New reconciliation — {bank?.name}</DialogTitle>
          <DialogDescription>Only one reconciliation per bank account can be open at a time.</DialogDescription>
        </DialogHeader>
        <FormGrid cols={2}>
          <FormField label="Statement date" required><DateField mode="date" value={f.date} onChange={(v) => setF({ ...f, date: v ?? "" })} aria-label="Statement date" /></FormField>
          <FormField label="Statement end balance (₵)" required><Input inputMode="decimal" value={f.end} onChange={(e) => setF({ ...f, end: e.target.value })} placeholder="14867.40" /></FormField>
        </FormGrid>
        <DialogFooter>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button disabled={!f.date || !f.end.trim() || busy} onClick={() => onSave({ bankAccountId: bank?.id, statementDate: f.date, statementEndCents: toCents(f.end) })}>Start reconciliation</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function ItemDialog({ open, busy, reconId, onClose, onSave }: {
  open: boolean; busy: boolean; reconId: string | null;
  onClose: () => void; onSave: (p: Record<string, unknown>) => void;
}) {
  // remounted by the parent (key) whenever it opens — fresh form every time
  const [f, setF] = useState(() => ({ date: todayISO(), description: "", amount: "" }));
  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Add statement line</DialogTitle>
          <DialogDescription>Enter it exactly as printed on the bank statement — deposits positive, withdrawals negative.</DialogDescription>
        </DialogHeader>
        <FormGrid cols={2}>
          <FormField label="Date" required><DateField mode="date" value={f.date} onChange={(v) => setF({ ...f, date: v ?? "" })} aria-label="Line date" /></FormField>
          <FormField label="Amount (₵)" required hint="+ in / − out"><Input inputMode="decimal" value={f.amount} onChange={(e) => setF({ ...f, amount: e.target.value })} placeholder="-95.00" /></FormField>
          <FormField label="Description" required span={2}><Input value={f.description} onChange={(e) => setF({ ...f, description: e.target.value })} placeholder="Cheque 0033 — supplier" /></FormField>
        </FormGrid>
        <DialogFooter>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button disabled={!f.description.trim() || !f.amount.trim() || f.amount === "0" || !f.date || busy}
            onClick={() => onSave({ reconciliationId: reconId, date: f.date, description: f.description.trim(), amountCents: toCents(f.amount) })}>Add line</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ═══════════════════════ REPORTS ═══════════════════════ */
type ReportKind = "trial-balance" | "balance-sheet" | "income-statement" | "general-ledger";
const REPORTS: { id: ReportKind; label: string }[] = [
  { id: "trial-balance", label: "Trial balance" },
  { id: "balance-sheet", label: "Balance sheet" },
  { id: "income-statement", label: "Income statement" },
  { id: "general-ledger", label: "General ledger" },
];

type TrialBalance = { kind: "trial-balance"; asOf: string; rows: { id: string; code: string; name: string; type: string; debitCents: number; creditCents: number }[]; totalDebitCents: number; totalCreditCents: number; balanced: boolean };
type BalanceSheet = { kind: "balance-sheet"; asOf: string; assets: { id: string; code: string; name: string; balanceCents: number }[]; liabilities: { id: string; code: string; name: string; balanceCents: number }[]; equity: { id: string; code: string; name: string; balanceCents: number }[]; totalAssetsCents: number; totalLiabilitiesCents: number; totalEquityCents: number; netIncomeCents: number; checkCents: number; balanced: boolean };
type IncomeStatement = { kind: "income-statement"; from: string; to: string; revenue: { id: string; code: string; name: string; amountCents: number }[]; expenses: { id: string; code: string; name: string; amountCents: number }[]; totalRevenueCents: number; totalExpenseCents: number; surplusCents: number };
type GeneralLedger = { kind: "general-ledger"; account: { id: string; code: string; name: string; type: string }; from: string; to: string; openingCents: number; lines: { date: string; entryNumber: string; description: string; memo: string | null; debitCents: number; creditCents: number; runningCents: number }[]; closingCents: number; debitNormal: boolean };
type ReportData = TrialBalance | BalanceSheet | IncomeStatement | GeneralLedger;

function ReportsTab({ accounts, schoolName }: { accounts: Account[]; schoolName: string }) {
  const { currentSchoolId } = useStore();
  const [kind, setKind] = useState<ReportKind>("trial-balance");
  const [asOf, setAsOf] = useState(todayISO());
  const [from, setFrom] = useState(monthStartISO());
  const [to, setTo] = useState(todayISO());
  const [accountIdSel, setAccountId] = useState<string>("");
  // derived default: Cash in Hand when present, else the first account
  const accountId = accountIdSel && accounts.some((a) => a.id === accountIdSel) ? accountIdSel : (accounts.find((a) => a.code === "1000")?.id ?? accounts[0]?.id ?? "");
  const [csvBusy, setCsvBusy] = useState(false);

  const params = useMemo(() => {
    const p = new URLSearchParams({ report: kind });
    if (kind === "trial-balance" || kind === "balance-sheet") p.set("asOf", asOf);
    if (kind === "income-statement" || kind === "general-ledger") { p.set("from", from); p.set("to", to); }
    if (kind === "general-ledger" && accountId) p.set("accountId", accountId);
    return p;
  }, [kind, asOf, from, to, accountId]);

  const url = `/api/school/${currentSchoolId}/ops/accounts?${params.toString()}`;
  const fetchable = !!currentSchoolId && !(kind === "general-ledger" && !accountId);
  // loading is derived: a report whose url differs from the loaded one is in flight
  const [loaded, setLoaded] = useState<{ url: string; data: ReportData | null } | null>(null);
  const loading = fetchable && loaded?.url !== url;
  const rep = loaded?.url === url ? loaded.data : null;
  useEffect(() => {
    if (!fetchable) return;
    let stale = false;
    apiGet<ReportData>(url)
      .then((data) => { if (!stale) setLoaded({ url, data }); })
      .catch((e) => { if (!stale) { setLoaded({ url, data: null }); toast({ title: "Report failed", description: e.message, variant: "destructive" }); } });
    return () => { stale = true; };
  }, [url, fetchable]);

  async function downloadCsv() {
    setCsvBusy(true);
    try {
      const res = await fetch(`${url}&format=csv`, { headers: getHeaders() });
      if (!res.ok) throw new Error((await res.json().catch(() => ({}))).error ?? `Export failed (${res.status})`);
      const blob = await res.blob();
      const cd = res.headers.get("content-disposition") ?? "";
      const name = /filename="([^"]+)"/.exec(cd)?.[1] ?? `${kind}.csv`;
      const a = document.createElement("a");
      a.href = URL.createObjectURL(blob);
      a.download = name;
      a.click();
      URL.revokeObjectURL(a.href);
    } catch (e) { toast({ title: "Export failed", description: (e as Error).message, variant: "destructive" }); }
    finally { setCsvBusy(false); }
  }

  const money = (c: number) => <span className={`font-mono tabular-nums ${c < 0 ? "text-red-600 dark:text-red-400" : ""}`}>{fmtCedi(c)}</span>;

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap items-center gap-2 no-print">
        <div className="flex flex-wrap gap-1.5">
          {REPORTS.map((r) => <Button key={r.id} size="sm" variant={kind === r.id ? "default" : "outline"} className="h-8" onClick={() => setKind(r.id)}>{r.label}</Button>)}
        </div>
        <div className="ml-auto flex flex-wrap items-end gap-2">
          {(kind === "trial-balance" || kind === "balance-sheet") && (
            <div>
              <Label className="text-xs text-muted-foreground">As of</Label>
              <DateField mode="date" value={asOf} onChange={(v) => setAsOf(v ?? todayISO())} className="mt-1" aria-label="As of date" />
            </div>
          )}
          {(kind === "income-statement" || kind === "general-ledger") && (
            <>
              <div>
                <Label className="text-xs text-muted-foreground">From</Label>
                <DateField mode="date" value={from} onChange={(v) => setFrom(v ?? monthStartISO())} className="mt-1" aria-label="From date" />
              </div>
              <div>
                <Label className="text-xs text-muted-foreground">To</Label>
                <DateField mode="date" value={to} onChange={(v) => setTo(v ?? todayISO())} className="mt-1" aria-label="To date" />
              </div>
            </>
          )}
          {kind === "general-ledger" && (
            <div className="w-56">
              <Label className="text-xs text-muted-foreground">Account</Label>
              <Select value={accountId} onValueChange={setAccountId}>
                <SelectTrigger className="mt-1"><SelectValue placeholder="Pick an account" /></SelectTrigger>
                <SelectContent>
                  {ACCT_TYPES.map((t) => (
                    <SelectGroup key={t}>
                      <SelectLabel className={`px-2 py-1 text-[10px] font-semibold ${TYPE_BADGE[t].className}`}>{TYPE_BADGE[t].label}</SelectLabel>
                      {accounts.filter((a) => a.type === t).map((a) => <SelectItem key={a.id} value={a.id}>{a.code} · {a.name}</SelectItem>)}
                    </SelectGroup>
                  ))}
                </SelectContent>
              </Select>
            </div>
          )}
          <Button size="sm" variant="outline" className="gap-1.5" onClick={downloadCsv} disabled={csvBusy || loading || !rep}><Download className="size-4" /> CSV</Button>
          <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()} disabled={!rep}><Printer className="size-4" /> Print</Button>
        </div>
      </div>

      {loading ? <LoadingBlock label="Building report…" /> : !rep ? <EmptyState icon={<FileSpreadsheet className="size-6" />} title="Nothing to show" hint="Pick an account and date range, then the report builds itself." /> : (
        <Card className="print-area">
          <CardContent className="p-4 sm:p-6">
            <div className="text-center border-b pb-3 mb-4">
              <p className="font-semibold tracking-wide">{schoolName}</p>
              <p className="text-sm text-muted-foreground">
                {rep.kind === "trial-balance" ? `Trial balance — as of ${fmtDate((rep as TrialBalance).asOf)}`
                  : rep.kind === "balance-sheet" ? `Balance sheet — as of ${fmtDate((rep as BalanceSheet).asOf)}`
                    : rep.kind === "income-statement" ? `Income statement — ${fmtDate((rep as IncomeStatement).from)} to ${fmtDate((rep as IncomeStatement).to)}`
                      : `General ledger — ${(rep as GeneralLedger).account.code} ${(rep as GeneralLedger).account.name} · ${fmtDate((rep as GeneralLedger).from)} to ${fmtDate((rep as GeneralLedger).to)}`}
              </p>
            </div>

            {/* trial balance */}
            {rep.kind === "trial-balance" && (
              <div className="max-h-[60vh] overflow-y-auto nice-scroll">
                <Table>
                  <TableHeader className="sticky top-0 bg-card">
                    <TableRow>
                      <TableHead className="w-20">Code</TableHead><TableHead>Account</TableHead><TableHead className="hidden sm:table-cell w-24">Type</TableHead>
                      <TableHead className="text-right w-32">Debit</TableHead><TableHead className="text-right w-32">Credit</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {(rep as TrialBalance).rows.map((r) => (
                      <TableRow key={r.id}>
                        <TableCell className="font-mono text-xs">{r.code}</TableCell>
                        <TableCell className="text-sm">{r.name}</TableCell>
                        <TableCell className="hidden sm:table-cell"><span className={`inline-flex rounded-full px-2 py-0.5 text-[11px] ${TYPE_BADGE[r.type as AcctType]?.className ?? ""}`}>{r.type}</span></TableCell>
                        <TableCell className="text-right">{r.debitCents ? money(r.debitCents) : "—"}</TableCell>
                        <TableCell className="text-right">{r.creditCents ? money(r.creditCents) : "—"}</TableCell>
                      </TableRow>
                    ))}
                    <TableRow className="font-semibold bg-secondary/40">
                      <TableCell colSpan={3}>TOTAL</TableCell>
                      <TableCell className="text-right">{money((rep as TrialBalance).totalDebitCents)}</TableCell>
                      <TableCell className="text-right">{money((rep as TrialBalance).totalCreditCents)}</TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
                <div className="mt-3">
                  {(rep as TrialBalance).balanced
                    ? <Badge className="gap-1 bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"><CheckCircle2 className="size-3.5" /> Σdebit = Σcredit — the books balance</Badge>
                    : <Badge variant="destructive" className="gap-1"><AlertTriangle className="size-3.5" /> out of balance</Badge>}
                </div>
              </div>
            )}

            {/* balance sheet */}
            {rep.kind === "balance-sheet" && (() => {
              const bs = rep as BalanceSheet;
              const section = (title: string, rows: typeof bs.assets, total: number) => (
                <div className="mb-4">
                  <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">{title}</p>
                  <Table>
                    <TableBody>
                      {rows.map((r) => (
                        <TableRow key={r.id}>
                          <TableCell className="w-20 font-mono text-xs">{r.code}</TableCell>
                          <TableCell className="text-sm">{r.name}</TableCell>
                          <TableCell className="text-right w-40">{money(r.balanceCents)}</TableCell>
                        </TableRow>
                      ))}
                      <TableRow className="font-semibold bg-secondary/40">
                        <TableCell colSpan={2}>TOTAL {title}</TableCell>
                        <TableCell className="text-right">{money(total)}</TableCell>
                      </TableRow>
                    </TableBody>
                  </Table>
                </div>
              );
              return (
                <div>
                  {section("Assets", bs.assets, bs.totalAssetsCents)}
                  {section("Liabilities", bs.liabilities, bs.totalLiabilitiesCents)}
                  {section("Equity", bs.equity, bs.totalEquityCents)}
                  <div className="rounded-lg border p-3 mb-4">
                    <div className="flex justify-between text-sm"><span>Retained earnings (surplus to date)</span><span className="w-40 text-right">{money(bs.netIncomeCents)}</span></div>
                    <div className="flex justify-between text-sm font-semibold border-t mt-2 pt-2"><span>TOTAL EQUITY + RETAINED</span><span className="w-40 text-right font-mono tabular-nums">{fmtCedi(bs.totalEquityCents + bs.netIncomeCents)}</span></div>
                  </div>
                  {bs.balanced
                    ? <Badge className="gap-1 bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"><CheckCircle2 className="size-3.5" /> Balanced — Assets = Liabilities + Equity + Retained earnings</Badge>
                    : <Badge variant="destructive" className="gap-1"><AlertTriangle className="size-3.5" /> off by {fmtCedi(Math.abs(bs.checkCents))}</Badge>}
                </div>
              );
            })()}

            {/* income statement */}
            {rep.kind === "income-statement" && (() => {
              const i = rep as IncomeStatement;
              return (
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">Revenue</p>
                  <Table className="mb-4">
                    <TableBody>
                      {i.revenue.length === 0 && <TableRow><TableCell className="text-sm text-muted-foreground">No revenue in this period</TableCell><TableCell className="text-right w-40">—</TableCell></TableRow>}
                      {i.revenue.map((r) => (
                        <TableRow key={r.id}>
                          <TableCell><span className="font-mono text-xs text-muted-foreground">{r.code}</span> <span className="text-sm">{r.name}</span></TableCell>
                          <TableCell className="text-right w-40">{money(r.amountCents)}</TableCell>
                        </TableRow>
                      ))}
                      <TableRow className="font-semibold bg-secondary/40">
                        <TableCell>TOTAL REVENUE</TableCell><TableCell className="text-right">{money(i.totalRevenueCents)}</TableCell>
                      </TableRow>
                    </TableBody>
                  </Table>
                  <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">Expenses</p>
                  <Table className="mb-4">
                    <TableBody>
                      {i.expenses.length === 0 && <TableRow><TableCell className="text-sm text-muted-foreground">No expenses in this period</TableCell><TableCell className="text-right w-40">—</TableCell></TableRow>}
                      {i.expenses.map((r) => (
                        <TableRow key={r.id}>
                          <TableCell><span className="font-mono text-xs text-muted-foreground">{r.code}</span> <span className="text-sm">{r.name}</span></TableCell>
                          <TableCell className="text-right w-40">{money(r.amountCents)}</TableCell>
                        </TableRow>
                      ))}
                      <TableRow className="font-semibold bg-secondary/40">
                        <TableCell>TOTAL EXPENSES</TableCell><TableCell className="text-right">{money(i.totalExpenseCents)}</TableCell>
                      </TableRow>
                    </TableBody>
                  </Table>
                  <div className="rounded-lg border p-3 flex justify-between items-center">
                    <span className="font-bold">{i.surplusCents >= 0 ? "SURPLUS" : "DEFICIT"}</span>
                    <span className={`font-mono font-bold tabular-nums text-lg ${i.surplusCents < 0 ? "text-red-600 dark:text-red-400" : "text-emerald-700 dark:text-emerald-400"}`}>{fmtCedi(Math.abs(i.surplusCents))}</span>
                  </div>
                </div>
              );
            })()}

            {/* general ledger */}
            {rep.kind === "general-ledger" && (() => {
              const g = rep as GeneralLedger;
              return (
                <div>
                  <div className="max-h-[55vh] overflow-y-auto nice-scroll">
                    <Table>
                      <TableHeader className="sticky top-0 bg-card">
                        <TableRow>
                          <TableHead className="w-24">Date</TableHead><TableHead className="w-28">Entry</TableHead><TableHead>Description</TableHead>
                          <TableHead className="hidden md:table-cell min-w-32">Memo</TableHead>
                          <TableHead className="text-right w-28">Debit</TableHead><TableHead className="text-right w-28">Credit</TableHead>
                          <TableHead className="text-right w-32">Balance</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        <TableRow className="bg-secondary/30">
                          <TableCell colSpan={6} className="text-sm font-medium">Opening balance</TableCell>
                          <TableCell className="text-right">{money(g.openingCents)}</TableCell>
                        </TableRow>
                        {g.lines.length === 0 && <TableRow><TableCell colSpan={7} className="text-center text-sm text-muted-foreground py-4">No posted lines in this period</TableCell></TableRow>}
                        {g.lines.map((l, idx) => (
                          <TableRow key={idx}>
                            <TableCell className="text-sm">{fmtDate(l.date)}</TableCell>
                            <TableCell className="font-mono text-xs">{l.entryNumber}</TableCell>
                            <TableCell className="text-sm">{l.description}</TableCell>
                            <TableCell className="hidden md:table-cell text-xs text-muted-foreground">{l.memo ?? "—"}</TableCell>
                            <TableCell className="text-right">{l.debitCents ? money(l.debitCents) : "—"}</TableCell>
                            <TableCell className="text-right">{l.creditCents ? money(l.creditCents) : "—"}</TableCell>
                            <TableCell className="text-right">{money(l.runningCents)}</TableCell>
                          </TableRow>
                        ))}
                        <TableRow className="font-semibold bg-secondary/40">
                          <TableCell colSpan={6}>Closing balance</TableCell>
                          <TableCell className="text-right">{money(g.closingCents)}</TableCell>
                        </TableRow>
                      </TableBody>
                    </Table>
                  </div>
                  <p className="text-xs text-muted-foreground mt-3">
                    Balance runs on the account's natural side ({g.debitNormal ? "debit-normal — deposits/charges increase it" : "credit-normal — credits increase it"}).
                  </p>
                </div>
              );
            })()}
          </CardContent>
        </Card>
      )}
    </div>
  );
}

"use client";
import { useCallback, useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { canAny } from "@/lib/rbac";
import { fmtCedi, fmtDate } from "@/lib/format";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { DateField } from "@/components/date-field";
import { StatCard, ModuleHeader, LoadingBlock, EmptyState, FormGrid, FormField } from "@/components/ui-helpers";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  PiggyBank, CalendarRange, Plus, Pencil, Trash2, Eye, CheckCheck, Ban, Target, Wallet, TrendingUp, FileSpreadsheet, AlertTriangle,
} from "lucide-react";

/* ══════════════════════════════════════════════════════════════
   Budgets & Fiscal Years — CI3 budget_planning.php /
   Budget_management.php + Accounts.php budgets()/fiscal_year()
   parity. Fiscal years segment the accounting calendar; budgets
   allocate expected amounts per chart account over a window.
   Actuals, utilisation and variance are LIVE — recomputed from
   POSTED journal entries on every load, so posting or voiding an
   entry instantly re-flows every budget card here.
   Lifecycle: FY OPEN→CLOSED (irreversible) · budget
   DRAFT→APPROVED→CLOSED, only DRAFTs editable/deletable.
   ══════════════════════════════════════════════════════════════ */

type AcctType = "ASSET" | "LIABILITY" | "EQUITY" | "REVENUE" | "EXPENSE";
type Acct = { id: string; code: string; name: string; type: AcctType };
type FY = {
  id: string; name: string; startsAt: string; endsAt: string; status: "OPEN" | "CLOSED"; isCurrent: boolean;
  notes: string | null; createdByName: string; closedAt: string | null; closedByName: string | null; createdAt: string; budgetCount: number;
};
type Budget = {
  id: string; code: string; name: string; status: "DRAFT" | "APPROVED" | "CLOSED"; notes: string | null; lineCount: number;
  fiscalYearId: string; fiscalYearName: string; startsAt: string; endsAt: string;
  createdByName: string; approvedByName: string | null; approvedAt: string | null;
  closedByName: string | null; closedAt: string | null; createdAt: string;
  totalBudgetedCents: number; actualCents: number; remainingCents: number; utilizationPct: number;
};
type Kpis = { budgets: number; approved: number; drafts: number; allocatedCents: number; utilizedCents: number };
type Data = { fiscalYears: FY[]; budgets: Budget[]; kpis: Kpis };
type DetailLine = { id: string; account: Acct; budgetedCents: number; actualCents: number; varianceCents: number; utilizationPct: number; notes: string | null };
type BudgetDetail = {
  budget: Omit<Budget, "fiscalYearName" | "fiscalYearId" | "lineCount"> & {
    fiscalYear: { id: string; name: string; status: string };
    lines: DetailLine[];
  };
};

const ACCT_TYPES: AcctType[] = ["ASSET", "LIABILITY", "EQUITY", "REVENUE", "EXPENSE"];
const TYPE_BADGE: Record<AcctType, { label: string; className: string }> = {
  ASSET: { label: "Asset", className: "bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200" },
  LIABILITY: { label: "Liability", className: "bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200" },
  EQUITY: { label: "Equity", className: "bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-200" },
  REVENUE: { label: "Revenue", className: "bg-lime-100 text-lime-900 dark:bg-lime-950 dark:text-lime-200" },
  EXPENSE: { label: "Expense", className: "bg-orange-100 text-orange-900 dark:bg-orange-950 dark:text-orange-200" },
};

const toCents = (s: string): number => {
  const v = parseFloat(s.replace(/,/g, ""));
  return Number.isFinite(v) ? Math.round(v * 100) : 0;
};
const isoDate = (v: string | null | undefined) => (v ? new Date(v).toISOString().slice(0, 10) : "");
/** "Sep 2025" — month+year for fiscal-year range chips (CI3 parity). */
const fmtMonthYear = (d: string | null | undefined) =>
  d ? new Date(d).toLocaleDateString("en-GB", { month: "short", year: "numeric" }) : "—";

/* status chips (CI3 budget list parity) */
const BudgetStatusChip = ({ status }: { status: Budget["status"] }) =>
  status === "APPROVED" ? <Badge className="bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">APPROVED</Badge>
    : status === "DRAFT" ? <Badge variant="outline" className="border-amber-300 text-amber-700 dark:border-amber-800 dark:text-amber-300">DRAFT</Badge>
      : <Badge variant="secondary">CLOSED</Badge>;
const YearStatusChip = ({ status }: { status: FY["status"] }) =>
  status === "OPEN" ? <Badge variant="outline" className="border-emerald-300 text-emerald-700 dark:border-emerald-800 dark:text-emerald-300">OPEN</Badge>
    : <Badge variant="secondary">CLOSED</Badge>;

/** Utilisation bar — primary below 75%, amber ≥75%, red ≥90% (CI3 colour doctrine). */
const UtilBar = ({ pct, className }: { pct: number; className?: string }) => (
  <div className={cn("h-2 rounded-full bg-secondary overflow-hidden", className)}>
    <div
      className={cn("h-full rounded-full transition-[width]", pct >= 90 ? "bg-red-600" : pct >= 75 ? "bg-amber-500" : "bg-primary")}
      style={{ width: `${Math.max(0, Math.min(100, pct))}%` }}
    />
  </div>
);

type Confirm = { title: string; description: string; action: string; extra: Record<string, unknown>; confirmLabel?: string };

export function Budgets() {
  const { currentSchoolId, session } = useStore();
  const [data, setData] = useState<Data | null>(null);
  const [busy, setBusy] = useState(false);
  const canManage = canAny(session?.permissions, ["budget.manage"]);
  const [accounts, setAccounts] = useState<Acct[] | null>(null); // lazy cache for the line picker

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<Data>(`/api/school/${currentSchoolId}/ops/budget`)
      .then(setData)
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  }, [currentSchoolId]);
  useEffect(() => { load(); }, [load]);

  /** Chart-account picker options — fetched once, when the editor first opens
   *  (retried while empty so a seed landing later is still picked up). */
  const ensureAccounts = useCallback(() => {
    if ((accounts && accounts.length > 0) || !currentSchoolId) return;
    apiGet<{ accounts: Acct[] }>(`/api/school/${currentSchoolId}/ops/budget?action=accounts`)
      .then((d) => setAccounts(d.accounts ?? []))
      .catch((e) => toast({ title: "Could not load accounts", description: e.message, variant: "destructive" }));
  }, [accounts, currentSchoolId]);

  async function act(action: string, extra: Record<string, unknown> = {}, okMsg?: string) {
    if (!currentSchoolId) return null;
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/budget`, { action, ...extra });
      if (okMsg) toast({ title: okMsg });
      load();
      return true;
    } catch (e) {
      toast({ title: "Failed", description: (e as Error).message, variant: "destructive" });
      return null;
    } finally { setBusy(false); }
  }

  /* destructive confirms — one dialog, many actions (accounts-module pattern) */
  const [confirmState, setConfirmState] = useState<Confirm | null>(null);
  const runConfirm = async () => {
    if (!confirmState) return;
    await act(confirmState.action, confirmState.extra, confirmState.confirmLabel ?? "Done");
    setConfirmState(null);
  };

  /* fiscal-year dialog */
  const [yearDialog, setYearDialog] = useState<{ open: boolean; editing: FY | null }>({ open: false, editing: null });

  /* budget editor — editing needs the live lines, so the detail is fetched first */
  const [editor, setEditor] = useState<{ open: boolean; budget: BudgetDetail["budget"] | null }>({ open: false, budget: null });
  function openEditor(b: Budget | null) {
    ensureAccounts();
    if (!b || !currentSchoolId) { setEditor({ open: true, budget: null }); return; }
    apiGet<BudgetDetail>(`/api/school/${currentSchoolId}/ops/budget?action=budget&budgetId=${b.id}`)
      .then((d) => setEditor({ open: true, budget: d.budget }))
      .catch((e) => toast({ title: "Could not load budget", description: e.message, variant: "destructive" }));
  }

  /* budget detail dialog (View + card click) */
  const [detailId, setDetailId] = useState<string | null>(null);
  const [detail, setDetail] = useState<BudgetDetail | null>(null);
  useEffect(() => {
    if (!detailId || !currentSchoolId) { setDetail(null); return; }
    setDetail(null);
    apiGet<BudgetDetail>(`/api/school/${currentSchoolId}/ops/budget?action=budget&budgetId=${detailId}`)
      .then(setDetail)
      .catch((e) => { toast({ title: "Could not load budget", description: e.message, variant: "destructive" }); setDetailId(null); });
  }, [detailId, currentSchoolId]);

  if (!data) return <LoadingBlock label="Loading budgets…" />;
  const k = data.kpis;
  const utilPct = k.allocatedCents > 0 ? Math.round((k.utilizedCents / k.allocatedCents) * 100) : 0;

  return (
    <div className="p-4 md:p-6 space-y-4 md:space-y-5">
      <ModuleHeader
        title="Budgets & Fiscal Years"
        description="Plan expected spending per chart account over a fiscal-year window. Actuals and variance are computed live from posted journal entries — the moment an entry posts, every budget re-flows."
        actions={<Badge variant="secondary" className="gap-1.5"><PiggyBank className="size-3.5" />{data.budgets.length} budget{data.budgets.length === 1 ? "" : "s"} · {data.fiscalYears.length} fiscal year{data.fiscalYears.length === 1 ? "" : "s"}</Badge>}
      />

      {/* ── KPI row ── */}
      <div className="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<FileSpreadsheet className="size-4" />} label="Total budgets" value={String(k.budgets)} sub={`${k.drafts} draft${k.drafts === 1 ? "" : "s"} pending approval`} />
        <StatCard icon={<CheckCheck className="size-4" />} label="Active (approved)" value={String(k.approved)} tone="primary" sub="approved plans in force" />
        <StatCard icon={<Target className="size-4" />} label="Total allocated" value={fmtCedi(k.allocatedCents, { compact: true })} tone="gold" sub="Σ approved budget lines" />
        <StatCard icon={<TrendingUp className="size-4" />} label="Total utilized" value={fmtCedi(k.utilizedCents, { compact: true })} sub={`${utilPct}% of allocated · live actuals`} />
      </div>

      {/* ── FISCAL YEARS ── */}
      <Card>
        <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
          <CardTitle className="text-sm flex items-center gap-2"><CalendarRange className="size-4 text-primary" /> Fiscal years</CardTitle>
          {canManage && (
            <Button size="sm" className="gap-1.5" onClick={() => setYearDialog({ open: true, editing: null })}><Plus className="size-4" /> New fiscal year</Button>
          )}
        </CardHeader>
        <CardContent className="pt-0">
          {data.fiscalYears.length === 0 ? (
            <EmptyState icon={<CalendarRange className="size-6" />} title="No fiscal years yet" hint="Create the first accounting year (e.g. 2025/26) — budgets hang off fiscal years." />
          ) : (
            <div className="divide-y rounded-lg border">
              {data.fiscalYears.map((y) => (
                <div key={y.id} className="flex flex-wrap items-center gap-3 px-3 sm:px-4 py-3">
                  <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                      <span className="font-semibold">{y.name}</span>
                      {y.isCurrent && <Badge variant="outline" className="border-primary/40 text-primary text-[10px] tracking-wide">CURRENT</Badge>}
                      <YearStatusChip status={y.status} />
                    </div>
                    <div className="text-xs text-muted-foreground mt-0.5">
                      {fmtMonthYear(y.startsAt)} → {fmtMonthYear(y.endsAt)} · {y.budgetCount} budget{y.budgetCount === 1 ? "" : "s"}
                      {y.notes ? ` · ${y.notes}` : ""}
                      {y.closedByName ? ` · closed by ${y.closedByName}` : ""}
                    </div>
                  </div>
                  {canManage && y.status === "OPEN" && (
                    <div className="inline-flex gap-1">
                      <Button size="icon" variant="ghost" className="size-8" title="Edit fiscal year" disabled={busy} onClick={() => setYearDialog({ open: true, editing: y })}><Pencil className="size-4" /></Button>
                      <Button size="icon" variant="ghost" className="size-8 text-amber-600 hover:text-amber-700" title="Close fiscal year" disabled={busy}
                        onClick={() => setConfirmState({
                          title: `Close ${y.name}?`,
                          description: "Closing a fiscal year is irreversible — it becomes read-only and can no longer host new budgets. If it is the current year, the latest other open year takes over as current.",
                          action: "close-year", extra: { yearId: y.id }, confirmLabel: "Close year",
                        })}><Ban className="size-4" /></Button>
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* ── BUDGETS ── */}
      <Card>
        <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
          <CardTitle className="text-sm flex items-center gap-2"><PiggyBank className="size-4 text-primary" /> Budgets</CardTitle>
          {canManage && data.fiscalYears.length > 0 && (
            <Button size="sm" className="gap-1.5" onClick={() => openEditor(null)}><Plus className="size-4" /> New budget</Button>
          )}
        </CardHeader>
        <CardContent className="pt-0">
          {data.budgets.length === 0 ? (
            <EmptyState icon={<PiggyBank className="size-6" />} title={data.fiscalYears.length ? "No budgets yet" : "No budgets yet — create a fiscal year first"}
              hint="A budget allocates expected amounts per chart account over a window; actuals flow in live from posted journal entries." />
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">
              {data.budgets.map((b) => (
                <Card key={b.id} className="cursor-pointer transition-shadow hover:shadow-md" onClick={() => setDetailId(b.id)}>
                  <CardContent className="p-4 space-y-3">
                    <div className="flex items-start justify-between gap-2">
                      <div className="min-w-0">
                        <span className="font-mono text-[11px] text-muted-foreground">{b.code}</span>
                        <p className="font-semibold truncate" title={b.name}>{b.name}</p>
                      </div>
                      <BudgetStatusChip status={b.status} />
                    </div>
                    <div className="flex flex-wrap items-center gap-2 text-xs">
                      <Badge variant="secondary" className="text-[10px]">{b.fiscalYearName}</Badge>
                      <span className="text-muted-foreground">{fmtDate(b.startsAt)} → {fmtDate(b.endsAt)}</span>
                    </div>
                    <div className="space-y-1">
                      <UtilBar pct={b.utilizationPct} />
                      <p className="text-xs text-muted-foreground tabular-nums">
                        {fmtCedi(b.actualCents, { compact: true })} of {fmtCedi(b.totalBudgetedCents, { compact: true })} · {b.utilizationPct}%
                      </p>
                    </div>
                    <div className="grid grid-cols-3 gap-2 text-center border-t pt-2.5">
                      <div><p className="text-[10px] uppercase tracking-wide text-muted-foreground">Allocated</p><p className="text-xs font-mono tabular-nums">{fmtCedi(b.totalBudgetedCents, { compact: true })}</p></div>
                      <div><p className="text-[10px] uppercase tracking-wide text-muted-foreground">Utilized</p><p className="text-xs font-mono tabular-nums">{fmtCedi(b.actualCents, { compact: true })}</p></div>
                      <div><p className="text-[10px] uppercase tracking-wide text-muted-foreground">Remaining</p><p className={cn("text-xs font-mono tabular-nums", b.remainingCents < 0 && "text-red-600 dark:text-red-400")}>{fmtCedi(b.remainingCents, { compact: true })}</p></div>
                    </div>
                    <p className="text-[11px] text-muted-foreground">
                      {b.lineCount} line{b.lineCount === 1 ? "" : "s"} · by {b.createdByName}
                      {b.approvedByName ? ` · approved by ${b.approvedByName}` : ""}
                    </p>
                    <div className="flex flex-wrap gap-1 border-t pt-2.5" onClick={(e) => e.stopPropagation()}>
                      <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={() => setDetailId(b.id)}><Eye className="size-3.5" /> View</Button>
                      {canManage && b.status === "DRAFT" && (
                        <>
                          <Button size="sm" variant="outline" className="h-8 gap-1.5 text-emerald-600 hover:text-emerald-700" disabled={busy}
                            onClick={() => setConfirmState({
                              title: `Approve ${b.code}?`,
                              description: "Approval freezes the plan — approved budgets can no longer be edited or deleted, only closed. Utilisation starts counting against live actuals.",
                              action: "approve-budget", extra: { budgetId: b.id }, confirmLabel: "Approve",
                            })}><CheckCheck className="size-3.5" /> Approve</Button>
                          <Button size="sm" variant="outline" className="h-8 gap-1.5" disabled={busy} onClick={() => openEditor(b)}><Pencil className="size-3.5" /> Edit</Button>
                          <Button size="sm" variant="outline" className="h-8 gap-1.5 text-red-600 hover:text-red-700" disabled={busy}
                            onClick={() => setConfirmState({
                              title: `Delete draft ${b.code}?`,
                              description: "The draft and its lines are removed. Nothing was ever approved — no audit history is lost.",
                              action: "delete-budget", extra: { budgetId: b.id }, confirmLabel: "Delete",
                            })}><Trash2 className="size-3.5" /> Delete</Button>
                        </>
                      )}
                      {canManage && b.status === "APPROVED" && (
                        <Button size="sm" variant="outline" className="h-8 gap-1.5 text-amber-600 hover:text-amber-700" disabled={busy}
                          onClick={() => setConfirmState({
                            title: `Close ${b.code}?`,
                            description: "Closing ends the budget's active life — it drops out of the approved KPIs while its history and live variance stay readable. This cannot be undone.",
                            action: "close-budget", extra: { budgetId: b.id }, confirmLabel: "Close budget",
                          })}><Ban className="size-3.5" /> Close</Button>
                      )}
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* ── dialogs ── */}
      <YearDialog key={`${yearDialog.open ? "o" : "c"}:${yearDialog.editing?.id ?? "new"}`} open={yearDialog.open} editing={yearDialog.editing} busy={busy}
        onClose={() => setYearDialog({ open: false, editing: null })}
        onSave={(payload) => act("save-year", payload, yearDialog.editing ? "Fiscal year updated" : "Fiscal year created").then(() => setYearDialog({ open: false, editing: null }))} />

      <BudgetEditor key={`${editor.open ? "o" : "c"}:${editor.budget?.id ?? "new"}`} open={editor.open} budget={editor.budget}
        fiscalYears={data.fiscalYears} accounts={accounts ?? []} busy={busy}
        onClose={() => setEditor({ open: false, budget: null })}
        onSave={(payload) => act("save-budget", payload, editor.budget ? "Budget updated" : "Budget created").then(() => setEditor({ open: false, budget: null }))} />

      {/* ── budget detail ── */}
      <Dialog open={!!detailId} onOpenChange={(o) => !o && setDetailId(null)}>
        <DialogContent className="sm:max-w-3xl">
          {!detail ? <LoadingBlock label="Loading budget…" /> : (
            <>
              <DialogHeader>
                <DialogTitle className="flex flex-wrap items-center gap-2">
                  <span className="font-mono text-sm">{detail.budget.code}</span>
                  <BudgetStatusChip status={detail.budget.status} />
                  <span className="truncate font-semibold">{detail.budget.name}</span>
                </DialogTitle>
                <DialogDescription>
                  {detail.budget.fiscalYear.name} · {fmtDate(detail.budget.startsAt)} → {fmtDate(detail.budget.endsAt)}
                  {detail.budget.approvedByName ? ` · approved by ${detail.budget.approvedByName}` : ""}
                  {detail.budget.closedByName ? ` · closed by ${detail.budget.closedByName}` : ""}
                  {detail.budget.notes ? ` — ${detail.budget.notes}` : ""}
                </DialogDescription>
              </DialogHeader>
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                {[
                  { label: "Allocated", value: fmtCedi(detail.budget.totalBudgetedCents) },
                  { label: "Utilized", value: fmtCedi(detail.budget.actualCents) },
                  { label: "Remaining", value: fmtCedi(detail.budget.remainingCents), danger: detail.budget.remainingCents < 0 },
                  { label: "Utilization", value: `${detail.budget.utilizationPct}%` },
                ].map((s) => (
                  <div key={s.label} className="rounded-lg border p-2.5 text-center">
                    <p className="text-[10px] uppercase tracking-wide text-muted-foreground">{s.label}</p>
                    <p className={cn("text-sm font-semibold font-mono tabular-nums", s.danger && "text-red-600 dark:text-red-400")}>{s.value}</p>
                  </div>
                ))}
              </div>
              <UtilBar pct={detail.budget.utilizationPct} />
              <div className="max-h-[45vh] overflow-y-auto nice-scroll rounded-lg border">
                <Table>
                  <TableHeader className="sticky top-0 bg-card z-10">
                    <TableRow>
                      <TableHead>Account</TableHead>
                      <TableHead className="text-right">Budgeted</TableHead>
                      <TableHead className="text-right">Actual</TableHead>
                      <TableHead className="text-right">Variance</TableHead>
                      <TableHead className="w-28">Utilization</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {detail.budget.lines.map((l) => (
                      <TableRow key={l.id}>
                        <TableCell>
                          <span className="font-mono text-xs text-muted-foreground">{l.account.code}</span> {l.account.name}
                          <span className={cn("ml-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold", TYPE_BADGE[l.account.type].className)}>{TYPE_BADGE[l.account.type].label}</span>
                        </TableCell>
                        <TableCell className="text-right font-mono tabular-nums">{fmtCedi(l.budgetedCents)}</TableCell>
                        <TableCell className="text-right font-mono tabular-nums">{fmtCedi(l.actualCents)}</TableCell>
                        <TableCell className={cn("text-right font-mono tabular-nums", l.varianceCents < 0 ? "text-red-600 dark:text-red-400" : l.varianceCents > 0 ? "text-emerald-600 dark:text-emerald-400" : "")}>
                          {fmtCedi(l.varianceCents)}
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <UtilBar pct={l.utilizationPct} className="flex-1" />
                            <span className="text-xs tabular-nums text-muted-foreground w-9 text-right">{l.utilizationPct}%</span>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))}
                    <TableRow className="bg-secondary/40 font-semibold">
                      <TableCell>Totals — {detail.budget.lines.length} line{detail.budget.lines.length === 1 ? "" : "s"}</TableCell>
                      <TableCell className="text-right font-mono tabular-nums">{fmtCedi(detail.budget.totalBudgetedCents)}</TableCell>
                      <TableCell className="text-right font-mono tabular-nums">{fmtCedi(detail.budget.actualCents)}</TableCell>
                      <TableCell className={cn("text-right font-mono tabular-nums", detail.budget.remainingCents < 0 ? "text-red-600 dark:text-red-400" : "text-emerald-600 dark:text-emerald-400")}>{fmtCedi(detail.budget.remainingCents)}</TableCell>
                      <TableCell className="text-right text-xs tabular-nums">{detail.budget.utilizationPct}%</TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
              <p className="text-[11px] leading-snug text-muted-foreground flex items-start gap-1.5">
                <Wallet className="size-3.5 shrink-0 mt-0.5" />
                Actuals are computed live from posted journal entries on each account within the budget window — positive variance means under budget, negative means over.
              </p>
            </>
          )}
        </DialogContent>
      </Dialog>

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

/* ═══════════════════════ FISCAL YEAR DIALOG ═══════════════════════ */
function YearDialog({ open, editing, busy, onClose, onSave }: {
  open: boolean; editing: FY | null; busy: boolean;
  onClose: () => void; onSave: (p: Record<string, unknown>) => void;
}) {
  // remounted by the parent (key) on each open — plain useState seeds a fresh form
  const [f, setF] = useState(() => ({
    name: editing?.name ?? "", startsAt: isoDate(editing?.startsAt), endsAt: isoDate(editing?.endsAt), notes: editing?.notes ?? "",
  }));
  const datesOk = !!f.startsAt && !!f.endsAt && f.startsAt < f.endsAt;
  const valid = !!f.name.trim() && datesOk;

  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{editing ? `Edit fiscal year ${editing.name}` : "New fiscal year"}</DialogTitle>
          <DialogDescription>The accounting calendar year — budgets attach to it. A new year becomes CURRENT when no other open year holds the flag.</DialogDescription>
        </DialogHeader>
        <FormGrid cols={2}>
          <FormField label="Name" required hint="e.g. 2025/26"><Input value={f.name} onChange={(e) => setF({ ...f, name: e.target.value })} placeholder="2025/26" /></FormField>
          <FormField label="Starts" required>
            <DateField mode="date" value={f.startsAt} onChange={(v) => setF({ ...f, startsAt: v ?? "" })} aria-label="Fiscal year start" />
          </FormField>
          <FormField label="Ends" required>
            <DateField mode="date" value={f.endsAt} onChange={(v) => setF({ ...f, endsAt: v ?? "" })} aria-label="Fiscal year end" />
          </FormField>
          <FormField label="Notes" span={2} hint="optional — e.g. GES academic calendar year">
            <Textarea value={f.notes} onChange={(e) => setF({ ...f, notes: e.target.value })} placeholder="—" className="min-h-16" />
          </FormField>
        </FormGrid>
        {!datesOk && (f.startsAt || f.endsAt) && (
          <p className="text-xs text-destructive flex items-center gap-1.5"><AlertTriangle className="size-3.5" /> The fiscal year must start before it ends.</p>
        )}
        <DialogFooter>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button disabled={!valid || busy} onClick={() => onSave({ yearId: editing?.id, name: f.name.trim(), startsAt: f.startsAt, endsAt: f.endsAt, notes: f.notes.trim() || undefined })}>
            {editing ? "Save changes" : "Create fiscal year"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ═══════════════════════ BUDGET EDITOR (create + edit, with lines) ═══════════════════════ */
type EditLine = { accountId: string; amount: string };
const emptyLine = (): EditLine => ({ accountId: "", amount: "" });
const MAX_LINES = 30;

function BudgetEditor({ open, budget, fiscalYears, accounts, busy, onClose, onSave }: {
  open: boolean; budget: BudgetDetail["budget"] | null; fiscalYears: FY[]; accounts: Acct[]; busy: boolean;
  onClose: () => void; onSave: (p: Record<string, unknown>) => void;
}) {
  // remounted by the parent (key) on each open — plain useState seeds a fresh form
  const [f, setF] = useState(() => ({
    name: budget?.name ?? "", fiscalYearId: budget?.fiscalYear?.id ?? "", startsAt: isoDate(budget?.startsAt), endsAt: isoDate(budget?.endsAt), notes: budget?.notes ?? "",
  }));
  const [lines, setLines] = useState<EditLine[]>(() =>
    budget?.lines?.length ? budget.lines.map((l) => ({ accountId: l.account.id, amount: (l.budgetedCents / 100).toFixed(2) })) : [emptyLine()],
  );

  /* FY picker: open years for a new budget; keep the budget's own year selectable when editing */
  const openYears = fiscalYears.filter((y) => y.status === "OPEN");
  const ownYearMissing = budget?.fiscalYear && !openYears.some((y) => y.id === budget.fiscalYear.id);
  const fyOptions = ownYearMissing ? [budget!.fiscalYear as unknown as FY, ...openYears] : openYears;

  /* picking a fiscal year pre-seeds the window (CI3 parity) — only while untouched */
  function pickFY(id: string) {
    const y = fiscalYears.find((x) => x.id === id);
    setF((prev) => ({
      ...prev, fiscalYearId: id,
      startsAt: prev.startsAt || isoDate(y?.startsAt), endsAt: prev.endsAt || isoDate(y?.endsAt),
    }));
  }

  const setLine = (i: number, patch: Partial<EditLine>) => setLines((ls) => ls.map((l, x) => (x === i ? { ...l, ...patch } : l)));

  const total = lines.reduce((s, l) => s + toCents(l.amount), 0);
  const dupIds = new Set(lines.filter((l, i) => l.accountId && lines.findIndex((x) => x.accountId === l.accountId) !== i).map((l) => l.accountId));
  const datesOk = !!f.startsAt && !!f.endsAt && f.startsAt < f.endsAt;
  const linesValid = lines.length >= 1 && lines.every((l) => l.accountId && toCents(l.amount) > 0) && dupIds.size === 0;
  const valid = !!f.name.trim() && !!f.fiscalYearId && datesOk && linesValid;

  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>{budget ? `Edit draft ${budget.code}` : "New budget"}</DialogTitle>
          <DialogDescription>
            Saved as DRAFT — only drafts are editable, so approve when the plan is settled. Actuals are tracked live from posted journal entries per account over the window.
          </DialogDescription>
        </DialogHeader>

        {fyOptions.length === 0 ? (
          <EmptyState icon={<CalendarRange className="size-5" />} title="No open fiscal year" hint="Create or reopen a fiscal year first — budgets attach to one." />
        ) : (
          <>
            <FormGrid cols={2}>
              <FormField label="Name" required><Input value={f.name} onChange={(e) => setF({ ...f, name: e.target.value })} placeholder="Term 1 operations" /></FormField>
              <FormField label="Fiscal year" required>
                <Select value={f.fiscalYearId || "none"} onValueChange={pickFY}>
                  <SelectTrigger><SelectValue placeholder="Pick a fiscal year" /></SelectTrigger>
                  <SelectContent>
                    {fyOptions.map((y) => (
                      <SelectItem key={y.id} value={y.id}>
                        {y.name}{y.status === "OPEN" ? "" : " (closed)"}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Starts" required>
                <DateField mode="date" value={f.startsAt} onChange={(v) => setF({ ...f, startsAt: v ?? "" })} aria-label="Budget start" />
              </FormField>
              <FormField label="Ends" required>
                <DateField mode="date" value={f.endsAt} onChange={(v) => setF({ ...f, endsAt: v ?? "" })} aria-label="Budget end" />
              </FormField>
              <FormField label="Notes" span={2} hint="optional">
                <Textarea value={f.notes} onChange={(e) => setF({ ...f, notes: e.target.value })} placeholder="—" className="min-h-16" />
              </FormField>
            </FormGrid>
            {!datesOk && (f.startsAt || f.endsAt) && (
              <p className="text-xs text-destructive flex items-center gap-1.5"><AlertTriangle className="size-3.5" /> The budget must start before it ends.</p>
            )}

            {/* ── lines repeater (CI3 accounts/budgets.php parity) ── */}
            <div className="space-y-2">
              <Label className="text-xs font-medium">Budget lines <span className="text-destructive">*</span></Label>
              <div className="space-y-2">
                {lines.map((l, i) => {
                  const acct = accounts.find((a) => a.id === l.accountId);
                  return (
                    <div key={i} className="space-y-1">
                      <div className="flex flex-col sm:flex-row gap-2">
                        <Select value={l.accountId} onValueChange={(v) => setLine(i, { accountId: v })}>
                          <SelectTrigger className="sm:flex-1 text-xs"><SelectValue placeholder="Pick account" /></SelectTrigger>
                          <SelectContent>
                            {ACCT_TYPES.map((t) => (
                              <SelectGroup key={t}>
                                <SelectLabel className={`px-2 py-1 text-[10px] font-semibold ${TYPE_BADGE[t].className}`}>{TYPE_BADGE[t].label}</SelectLabel>
                                {accounts.filter((a) => a.type === t).map((a) => <SelectItem key={a.id} value={a.id}>{a.code} · {a.name}</SelectItem>)}
                              </SelectGroup>
                            ))}
                          </SelectContent>
                        </Select>
                        <div className="flex gap-2 flex-1">
                          <div className="relative flex-1 min-w-24">
                            <span className="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-muted-foreground">₵</span>
                            <Input className="pl-6 font-mono" inputMode="decimal" value={l.amount} placeholder="0.00"
                              onChange={(e) => setLine(i, { amount: e.target.value })} aria-label={`Line ${i + 1} amount`} />
                          </div>
                          <Button size="icon" variant="outline" className="size-9 shrink-0 text-red-600 hover:text-red-700" title="Remove line"
                            disabled={lines.length <= 1} onClick={() => setLines((ls) => ls.filter((_, x) => x !== i))}><Trash2 className="size-4" /></Button>
                        </div>
                      </div>
                      {dupIds.has(l.accountId) && (
                        <p className="text-[11px] text-destructive flex items-center gap-1"><AlertTriangle className="size-3" /> {acct ? `${acct.code} ${acct.name}` : "This account"} is already budgeted above — one line per account.</p>
                      )}
                    </div>
                  );
                })}
              </div>
              <div className="flex flex-wrap items-center gap-2">
                <Button size="sm" variant="outline" className="gap-1.5" disabled={lines.length >= MAX_LINES} onClick={() => setLines((ls) => [...ls, emptyLine()])}>
                  <Plus className="size-4" /> Add line{lines.length >= MAX_LINES ? " (max 30)" : ""}
                </Button>
                <span className="ml-auto text-xs text-muted-foreground tabular-nums">
                  <strong className="font-mono text-foreground">{fmtCedi(total)}</strong> across {lines.length} line{lines.length === 1 ? "" : "s"}
                </span>
              </div>
              {accounts.length === 0 && (
                <p className="text-[11px] text-muted-foreground">Loading chart accounts…</p>
              )}
            </div>
          </>
        )}

        <DialogFooter>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button disabled={!valid || busy || fyOptions.length === 0} onClick={() => onSave({
            budgetId: budget?.id, name: f.name.trim(), fiscalYearId: f.fiscalYearId, startsAt: f.startsAt, endsAt: f.endsAt,
            notes: f.notes.trim() || undefined,
            lines: lines.map((l) => ({ chartAccountId: l.accountId, budgetedCents: toCents(l.amount) })),
          })}>{budget ? "Save changes" : "Create budget"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

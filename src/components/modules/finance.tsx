"use client";
/* Nyansapo — Finance & Analytics (iedupro parity).
   Income dashboard · Student ledger · AR aging · Terminal bills · Expenditure ·
   CI3 statements (receivables · payables · income & expenditure). */
import { Fragment, useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { canModule } from "@/lib/rbac";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { DateField } from "@/components/date-field";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Checkbox } from "@/components/ui/checkbox";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { StatCard, ModuleHeader, LoadingBlock, EmptyState, FilterBar, FilterSelect, FilterSearch } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, PAYMENT_LABELS } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { LogoMark, KenteStrip } from "@/components/brand";
import { amountInWords } from "@/components/receipts";
import { cn } from "@/lib/utils";
import {
  TrendingUp, Wallet, AlertTriangle, Receipt, PieChart, BookOpenText, Timer, FileText, Plus,
  ArrowUpRight, ArrowDownLeft, Printer, Banknote, Trash2, Search, Users, ReceiptText, Download,
  HandCoins, Scale,
} from "lucide-react";
import { Bar, BarChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";

type Monthly = { month: string; revenue: number; expenses: number };
type FinanceData = {
  yearLabel: string;
  years: { id: string; label: string; isCurrent: boolean }[];
  income: {
    totalRevenue: number;
    byCategory: Record<string, number>;
    monthly: Monthly[];
    outstanding: number;
    outstandingCount: number;
    lastMonth: number;
  };
  aging: {
    current: { amount: number; students: number };
    b31: { amount: number; students: number };
    b61: { amount: number; students: number };
    b90: { amount: number; students: number };
  };
  expenditure: {
    total: number;
    byCategory: { category: string; amount: number }[];
    recent: { id: string; category: string; description: string; amount: number; method: string; date: string; recordedByName: string | null }[];
  };
  students: { id: string; name: string; classroom: string; guardianPhone: string }[];
};
type LedgerRow = { date: string; kind: "DEBIT" | "CREDIT"; description: string; reference: string; amount: number; balance: number };
type Ledger = { student: { name: string; index: string; classroom: string | null; guardian: string; guardianPhone: string }; yearLabel: string; rows: LedgerRow[]; closing: number };
type TerminalBill = {
  student: { id: string; name: string; index: string; guardian: string; guardianPhone: string };
  classroom: string | null;
  invoiceNo: string;
  termLabel: string;
  items: { name: string; amount: number }[];
  discount: number;
  discountReason: string | null;
  paid: number;
  balance: number;
  dailyArrears: number;
  transport: { route: string; fare: number } | null;
  credit: number; // prepaid credit balance (overpayment) offsetting the total due
};

const EXPENSE_CATEGORIES: { key: string; label: string }[] = [
  { key: "SALARIES", label: "Salaries & wages" },
  { key: "UTILITIES", label: "Utilities (ECG / water / internet)" },
  { key: "MAINTENANCE", label: "Repairs & maintenance" },
  { key: "TRANSPORT_FUEL", label: "Transport & fuel" },
  { key: "TEACHING_MATERIALS", label: "Teaching materials" },
  { key: "EVENTS", label: "Events & excursions" },
  { key: "ADMIN", label: "Administration" },
  { key: "OTHER", label: "Other" },
];
const catLabel = (k: string) => EXPENSE_CATEGORIES.find((c) => c.key === k)?.label ?? k;

export function Finance() {
  const { currentSchoolId, navTarget } = useStore();
  const [data, setData] = useState<FinanceData | null>(null);
  const [yearId, setYearId] = useState<string | null>(null);
  const [tab, setTab] = useState("income");

  /* CI3-statements deep links (Reports module quick cards, iedupro nested nav):
     ?tab=statements | receivables | payables | income-expenditure → open the tab. */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    const t = navTarget?.tab;
    if (t && ["statements", "receivables", "payables", "income-expenditure"].includes(t)) setTab("statements");
  }

  const load = (y?: string) => {
    if (!currentSchoolId) return;
    apiGet<FinanceData>(`/api/school/${currentSchoolId}/ops/finance${y ? `?yearId=${y}` : ""}`)
      .then((d) => { setData(d); setYearId(d.years.find((x) => x.isCurrent)?.id ?? d.years[0]?.id ?? null); })
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(() => { load(); }, [currentSchoolId]);
  const reload = (y: string) => { setYearId(y); apiGet<FinanceData>(`/api/school/${currentSchoolId}/ops/finance?yearId=${y}`).then(setData).catch(() => null); };

  if (!data) return <LoadingBlock label="Loading finance & analytics…" />;

  const growth = data.income.lastMonth ? Math.round(((data.income.monthly[data.income.monthly.length - 1]?.revenue - data.income.lastMonth) / data.income.lastMonth) * 100) : 0;
  const totalSources = Object.values(data.income.byCategory).reduce((a, b) => a + b, 0) || 1;

  return (
    <div>
      <ModuleHeader
        title="Finance & Analytics"
        description="Revenue from invoices, daily services, store sales and transport — receivables ageing, learner ledgers, terminal bills and expenditure, all in one bursar's console."
        actions={
          <Select value={yearId ?? ""} onValueChange={reload}>
            <SelectTrigger className="w-48"><SelectValue /></SelectTrigger>
            <SelectContent>{data.years.map((y) => <SelectItem key={y.id} value={y.id}>{y.label}{y.isCurrent ? " (current)" : ""}</SelectItem>)}</SelectContent>
          </Select>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<TrendingUp className="size-4" />} label={`Revenue · ${data.yearLabel}`} value={fmtCedi(data.income.totalRevenue, { compact: true })} tone="primary" sub={`${growth >= 0 ? "▲" : "▼"} ${Math.abs(growth)}% vs last month`} />
        <StatCard icon={<Wallet className="size-4" />} label="Outstanding" value={fmtCedi(data.income.outstanding, { compact: true })} tone={data.income.outstanding > 0 ? "danger" : "default"} sub={`${data.income.outstandingCount} invoices open`} />
        <StatCard icon={<ArrowDownLeft className="size-4" />} label="Expenditure" value={fmtCedi(data.expenditure.total, { compact: true })} sub={`${data.expenditure.byCategory.length} categories`} />
        <StatCard icon={<PieChart className="size-4" />} label="Net position" value={fmtCedi(data.income.totalRevenue - data.expenditure.total, { compact: true })} tone="gold" sub="revenue − expenses" />
      </div>

      <Tabs value={tab} onValueChange={setTab} className="mt-5">
        <TabsList className="flex-wrap">
          <TabsTrigger value="income">Income</TabsTrigger>
          <TabsTrigger value="cashier">Cashier reports</TabsTrigger>
          <TabsTrigger value="ledger">Student ledger</TabsTrigger>
          <TabsTrigger value="aging">Receivables ageing</TabsTrigger>
          <TabsTrigger value="bills">Terminal bills</TabsTrigger>
          <TabsTrigger value="expenditure">Expenditure</TabsTrigger>
          <TabsTrigger value="advanced">Advanced reports</TabsTrigger>
          <TabsTrigger value="statements">CI3 statements</TabsTrigger>
        </TabsList>

        {/* ── CASHIER REPORTS ── */}
        <TabsContent value="cashier">
          <CashierTab />
        </TabsContent>

        {/* ── INCOME ── */}
        <TabsContent value="income">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <Card className="lg:col-span-2">
              <CardHeader className="pb-3"><CardTitle className="text-base">Revenue vs expenditure — {data.yearLabel}</CardTitle></CardHeader>
              <CardContent>
                <div className="h-72">
                  <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={data.income.monthly}>
                      <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                      <XAxis dataKey="month" tick={{ fontSize: 11 }} />
                      <YAxis tick={{ fontSize: 11 }} tickFormatter={(v) => `₵${(v / 100000).toFixed(0)}k`} width={44} />
                      <Tooltip formatter={(v: any) => fmtCedi(Number(v))} contentStyle={{ fontSize: 12, borderRadius: 8 }} />
                      <Legend wrapperStyle={{ fontSize: 12 }} />
                      <Bar dataKey="revenue" name="Revenue" fill="#0e5f3f" radius={[3, 3, 0, 0]} />
                      <Bar dataKey="expenses" name="Expenditure" fill="#b45309" radius={[3, 3, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base">Revenue sources</CardTitle></CardHeader>
              <CardContent className="space-y-3">
                {[
                  { key: "INVOICE", label: "Billed invoices", icon: ReceiptText, color: "bg-emerald-600" },
                  { key: "DAILY", label: "Daily services", icon: Receipt, color: "bg-amber-600" },
                  { key: "POS", label: "Store sales", icon: Banknote, color: "bg-cyan-700" },
                  { key: "TRANSPORT", label: "Transport fares", icon: Users, color: "bg-rose-700" },
                ].map((s) => {
                  const amount = data.income.byCategory[s.key] ?? 0;
                  const pct = Math.round((amount / totalSources) * 100);
                  return (
                    <div key={s.key}>
                      <div className="flex justify-between text-sm">
                        <span className="flex items-center gap-1.5"><s.icon className="size-3.5 text-muted-foreground" />{s.label}</span>
                        <span className="tabular-nums font-semibold">{fmtCedi(amount, { compact: true })}</span>
                      </div>
                      <div className="h-1.5 rounded-full bg-secondary mt-1 overflow-hidden">
                        <div className={`h-full ${s.color}`} style={{ width: `${pct}%` }} />
                      </div>
                      <div className="text-[10px] text-muted-foreground text-right mt-0.5">{pct}%</div>
                    </div>
                  );
                })}
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* ── STUDENT LEDGER ── */}
        <TabsContent value="ledger">
          <LedgerTab data={data} yearId={yearId} />
        </TabsContent>

        {/* ── AGING (CI3 advanced receivables aging — dueAt-based buckets) ── */}
        <TabsContent value="aging">
          <AgingTab />
        </TabsContent>

        {/* ── TERMINAL BILLS ── */}
        <TabsContent value="bills">
          <TerminalBillsTab data={data} />
        </TabsContent>

        {/* ── EXPENDITURE ── */}
        <TabsContent value="expenditure">
          <ExpenditureTab data={data} onRefresh={() => reload(yearId ?? "")} />
        </TabsContent>
        <TabsContent value="advanced">
          <AdvancedReportsTab />
        </TabsContent>

        {/* ── CI3 STATEMENTS (receivables · payables · income & expenditure) ── */}
        <TabsContent value="statements">
          <StatementsTab />
        </TabsContent>
      </Tabs>
    </div>
  );
}

/* ── STUDENT LEDGER ─────────────────────────────────────────── */
function LedgerTab({ data, yearId }: { data: FinanceData; yearId: string | null }) {
  const { currentSchoolId } = useStore();
  const [studentId, setStudentId] = useState("");
  const [search, setSearch] = useState("");
  const [ledger, setLedger] = useState<Ledger | null>(null);
  const [busy, setBusy] = useState(false);

  const filtered = useMemo(() => {
    const q = search.toLowerCase();
    return data.students.filter((s) => !q || s.name.toLowerCase().includes(q) || s.classroom.toLowerCase().includes(q));
  }, [data, search]);

  async function generate(sid?: string) {
    const id = sid ?? studentId;
    if (!id) { toast({ title: "Choose a learner first" }); return; }
    setBusy(true);
    try {
      const res = await apiPost<Ledger>(`/api/school/${currentSchoolId}/ops/finance`, { action: "ledger", studentId: id, yearId });
      setLedger(res);
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Card>
        <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
          <CardTitle className="text-base">Pick a learner</CardTitle>
          <div className="relative">
            <Search className="size-4 absolute left-2.5 top-2.5 text-muted-foreground" />
            <Input placeholder="Search…" value={search} onChange={(e) => setSearch(e.target.value)} className="pl-8 w-40 h-8" />
          </div>
        </CardHeader>
        <CardContent className="max-h-[520px] overflow-y-auto nice-scroll space-y-1">
          {filtered.slice(0, 100).map((s) => (
            <button key={s.id} onClick={() => { setStudentId(s.id); generate(s.id); }}
              className={`w-full text-left rounded-lg border p-2.5 text-sm transition-colors ${studentId === s.id ? "border-primary bg-primary/5" : "hover:bg-secondary/40"}`}>
              <div className="font-medium">{s.name}</div>
              <div className="text-[11px] text-muted-foreground">{s.classroom}</div>
            </button>
          ))}
        </CardContent>
      </Card>
      <Card className="lg:col-span-2">
        {!ledger ? (
          <CardContent className="h-full flex items-center justify-center">
            <EmptyState icon={<BookOpenText className="size-5" />} title="No statement yet" hint="Pick a learner on the left to generate their full ledger — every debit and credit with a running balance." />
          </CardContent>
        ) : (
          <CardContent className="p-0">
            <div className="p-4 border-b flex flex-wrap items-center justify-between gap-2 no-print">
              <div>
                <div className="font-semibold">{ledger.student.name}</div>
                <div className="text-xs text-muted-foreground">{ledger.student.classroom} · {ledger.student.index} · {ledger.yearLabel}</div>
              </div>
              <div className="flex items-center gap-2">
                <Badge variant={ledger.closing > 0 ? "destructive" : "default"}>closing balance {fmtCedi(ledger.closing)}</Badge>
                <Button size="sm" variant="outline" onClick={() => window.print()}><Printer className="size-4" /> Print</Button>
              </div>
            </div>
            <div className="overflow-x-auto nice-scroll max-h-[460px]">
              <table className="w-full text-sm">
                <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                  <tr className="text-left border-b">
                    <th className="px-3 py-2.5 font-medium">Date</th>
                    <th className="px-3 py-2.5 font-medium">Description</th>
                    <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Reference</th>
                    <th className="px-3 py-2.5 font-medium text-right">Debit</th>
                    <th className="px-3 py-2.5 font-medium text-right">Credit</th>
                    <th className="px-3 py-2.5 font-medium text-right">Balance</th>
                  </tr>
                </thead>
                <tbody>
                  {ledger.rows.map((r, i) => (
                    <tr key={i} className="border-b last:border-0 hover:bg-secondary/30">
                      <td className="px-3 py-2 text-muted-foreground whitespace-nowrap">{fmtDate(r.date)}</td>
                      <td className="px-3 py-2 max-w-[280px]"><div className="truncate" title={r.description}>{r.description}</div></td>
                      <td className="px-3 py-2 hidden sm:table-cell font-mono text-xs text-muted-foreground">{r.reference}</td>
                      <td className="px-3 py-2 text-right tabular-nums">{r.kind === "DEBIT" ? fmtCedi(r.amount) : "—"}</td>
                      <td className="px-3 py-2 text-right tabular-nums text-emerald-600">{r.kind === "CREDIT" ? fmtCedi(r.amount) : "—"}</td>
                      <td className={`px-3 py-2 text-right tabular-nums font-semibold ${r.balance > 0 ? "text-destructive" : ""}`}>{fmtCedi(r.balance)}</td>
                    </tr>
                  ))}
                  {ledger.rows.length === 0 && <tr><td colSpan={6}><EmptyState title="No transactions in this period" /></td></tr>}
                </tbody>
              </table>
            </div>
          </CardContent>
        )}
      </Card>
    </div>
  );
}

/* ── TERMINAL BILLS (printable A5 slips: arrears + next term bill) ── */
function TerminalBillsTab({ data }: { data: FinanceData }) {
  const { currentSchoolId } = useStore();
  const [classrooms, setClassrooms] = useState<{ id: string; name: string }[]>([]);
  const [classroomId, setClassroomId] = useState("");
  const [students, setStudents] = useState<{ id: string; name: string }[]>([]);
  const [selected, setSelected] = useState<Set<string>>(new Set());
  const [feeCategory, setFeeCategory] = useState("ALL");
  const [bills, setBills] = useState<TerminalBill[] | null>(null);
  const [busy, setBusy] = useState(false);
  // print orientation (CI3 creche_bill_landscape parity): landscape spreads
  // wide item names / month grids across the A4 sheet
  const [landscape, setLandscape] = useState(false);

  useEffect(() => {
    if (!currentSchoolId) return;
    apiGet<{ classrooms: { id: string; name: string }[] }>(`/api/school/${currentSchoolId}/settings`).then((d) => setClassrooms(d.classrooms ?? [])).catch(() => null);
  }, [currentSchoolId]);
  useEffect(() => {
    setStudents(data.students.filter((s) => !classroomId || s.classroom === classrooms.find((c) => c.id === classroomId)?.name));
    setSelected(new Set());
  }, [classroomId, data, classrooms]);

  async function generate() {
    if (!currentSchoolId) return;
    setBusy(true);
    try {
      const res = await apiPost<{ termLabel: string; bills: TerminalBill[] }>(`/api/school/${currentSchoolId}/ops/finance`, {
        action: "terminal-bills", classroomId: classroomId || undefined, studentIds: Array.from(selected), feeCategory,
      });
      setBills(res.bills);
      if (!res.bills.length) toast({ title: "No bills generated", description: "The selected learners have no current-term invoices." });
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <div>
      <Card className="mb-4 no-print">
        <CardContent className="pt-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
          <div className="space-y-1.5">
            <Label>Class</Label>
            <Select value={classroomId} onValueChange={setClassroomId}>
              <SelectTrigger><SelectValue placeholder="All classes" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All classes</SelectItem>
                {classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Fee category</Label>
            <Select value={feeCategory} onValueChange={setFeeCategory}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="ALL">Everything (invoices + daily)</SelectItem>
                <SelectItem value="INVOICE">Billed invoices only</SelectItem>
                <SelectItem value="DAILY">Daily fees arrears only</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Learners ({selected.size} selected)</Label>
            <div className="flex gap-2">
              <Button size="sm" variant="outline" className="h-9" onClick={() => setSelected(new Set(students.map((s) => s.id)))}>Select all</Button>
              <Button size="sm" variant="outline" className="h-9" onClick={() => setSelected(new Set())}>Clear</Button>
            </div>
          </div>
          <Button onClick={generate} disabled={busy} className="gap-1.5"><FileText className="size-4" /> {busy ? "Generating…" : "Generate terminal bills"}</Button>
        </CardContent>
        {students.length > 0 && (
          <CardContent className="max-h-32 overflow-y-auto nice-scroll flex flex-wrap gap-1.5 pt-0">
            {students.slice(0, 200).map((s) => (
              <label key={s.id} className={`flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs cursor-pointer transition-colors ${selected.has(s.id) ? "border-primary bg-primary/10" : "hover:bg-secondary/50"}`}>
                <Checkbox checked={selected.has(s.id)} onCheckedChange={() => {
                  const n = new Set(selected);
                  if (n.has(s.id)) n.delete(s.id); else n.add(s.id);
                  setSelected(n);
                }} />
                {s.name}
              </label>
            ))}
          </CardContent>
        )}
      </Card>

      {!bills ? (
        <Card><CardContent><EmptyState icon={<FileText className="size-5" />} title="No terminal bills generated yet" hint="Pick a class, choose learners (or select all), then generate — each learner gets a printable slip with arrears and next-term bill." /></CardContent></Card>
      ) : (
        <div className="print-area space-y-4">
          {landscape && <style>{"@media print { @page { size: A4 landscape; margin: 10mm; } }"}</style>}
          <div className="no-print flex flex-wrap items-center justify-between gap-2">
            <div className="text-sm text-muted-foreground">{bills.length} bill{bills.length === 1 ? "" : "s"} ready</div>
            <div className="flex items-center gap-2">
              <div className="flex rounded-lg border overflow-hidden">
                <button onClick={() => setLandscape(false)} className={cn("px-3 py-1.5 text-xs font-medium", !landscape ? "bg-primary text-primary-foreground" : "hover:bg-secondary/60")}>Portrait</button>
                <button onClick={() => setLandscape(true)} className={cn("px-3 py-1.5 text-xs font-medium", landscape ? "bg-primary text-primary-foreground" : "hover:bg-secondary/60")}>Landscape</button>
              </div>
              <Button size="sm" onClick={() => window.print()} className="gap-1.5"><Printer className="size-4" /> Print all</Button>
            </div>
          </div>
          {bills.map((b, i) => <TerminalBill key={i} bill={b} landscape={landscape} />)}
        </div>
      )}
    </div>
  );
}

function TerminalBill({ bill, landscape }: { bill: TerminalBill; landscape?: boolean }) {
  const net = bill.items.reduce((a, x) => a + x.amount, 0);
  const balance = bill.balance;
  // prepaid credit (overpayment) offsets the arrears — never the future bus fare
  const creditApplied = bill.credit > 0 ? Math.min(bill.credit, balance + bill.dailyArrears) : 0;
  const totalDue = balance + bill.dailyArrears - creditApplied;
  const grandTotal = totalDue + (bill.transport?.fare ?? 0);
  // per-item discount breakdown: the invoice-level discount apportioned
  // pro-rata across the bill items (last row absorbs the rounding difference)
  const itemsWithDisc = (() => {
    const gross = bill.items.reduce((s, x) => s + x.amount, 0);
    if (!bill.discount || gross <= 0) return bill.items.map((x) => ({ ...x, disc: 0 }));
    let left = bill.discount;
    return bill.items.map((x, idx) => {
      const isLast = idx === bill.items.length - 1;
      const disc = isLast ? left : Math.round((x.amount * bill.discount) / gross);
      left -= disc;
      return { ...x, disc };
    });
  })();
  return (
    <div className={cn("print-sheet a5-sheet mx-auto bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden", landscape && "landscape-sheet")}>
      <KenteStrip />
      <div className="px-6 py-5">
        <div className="flex items-start justify-between gap-3">
          <div className="flex items-center gap-2.5">
            <LogoMark size={40} />
            <div className="leading-tight">
              <div className="font-bold text-base">Terminal Bill</div>
              <div className="text-[10px] text-muted-foreground">Arrears &amp; next-term fees</div>
            </div>
          </div>
          <div className="text-right">
            <div className="text-[10px] uppercase tracking-wider text-muted-foreground">Statement of account</div>
            <div className="font-mono text-xs">{bill.invoiceNo}</div>
            <div className="text-[10px] text-muted-foreground">{bill.termLabel}</div>
          </div>
        </div>

        <div className="grid grid-cols-2 gap-2 mt-4 text-[12.5px]">
          <div className="rounded-lg bg-secondary/60 p-2.5">
            <div className="text-[9px] uppercase tracking-wider text-muted-foreground">Learner</div>
            <div className="font-semibold">{bill.student.name}</div>
            <div className="text-[11px] text-muted-foreground">{bill.student.index}</div>
          </div>
          <div className="rounded-lg bg-secondary/60 p-2.5">
            <div className="text-[9px] uppercase tracking-wider text-muted-foreground">Class / guardian</div>
            <div className="font-semibold">{bill.classroom ?? "—"}</div>
            <div className="text-[11px] text-muted-foreground">{bill.student.guardian}</div>
          </div>
        </div>

        {bill.items.length > 0 && (
          <table className="w-full text-[12px] mt-3">
            <thead>
              <tr className="border-b text-left text-[9px] uppercase tracking-wider text-muted-foreground">
                <th className="py-1.5 font-medium">Current term bill item</th>
                <th className="py-1.5 font-medium text-right">Gross</th>
                {bill.discount > 0 && <th className="py-1.5 font-medium text-right">Discount</th>}
                <th className="py-1.5 font-medium text-right">Net</th>
              </tr>
            </thead>
            <tbody>
              {itemsWithDisc.map((it, i) => (
                <tr key={i} className="border-b border-dashed">
                  <td className="py-1.5">{it.name}</td>
                  <td className="py-1.5 text-right tabular-nums">{fmtCedi(it.amount)}</td>
                  {bill.discount > 0 && <td className="py-1.5 text-right tabular-nums text-emerald-700 dark:text-emerald-400">{it.disc ? `−${fmtCedi(it.disc)}` : "—"}</td>}
                  <td className="py-1.5 text-right tabular-nums font-medium">{fmtCedi(it.amount - it.disc)}</td>
                </tr>
              ))}
              {bill.discount > 0 && (
                <tr className="border-b border-dashed text-emerald-700 font-medium">
                  <td className="py-1.5">Total discount{bill.discountReason ? ` — ${bill.discountReason}` : ""}</td>
                  <td className="py-1.5 text-right tabular-nums">{fmtCedi(net)}</td>
                  <td className="py-1.5 text-right tabular-nums">−{fmtCedi(bill.discount)}</td>
                  <td className="py-1.5 text-right tabular-nums">{fmtCedi(net - bill.discount)}</td>
                </tr>
              )}
              <tr className="border-b"><td className="py-1.5 font-semibold" colSpan={bill.discount > 0 ? 3 : 2}>Paid to date</td><td className="py-1.5 text-right font-semibold tabular-nums text-emerald-700">{fmtCedi(bill.paid)}</td></tr>
            </tbody>
          </table>
        )}

        <div className="mt-3 space-y-1.5 text-[12.5px]">
          {balance > 0 && (
            <div className="flex justify-between rounded-lg bg-destructive/10 px-3 py-2">
              <span className="font-medium">Term bill arrears</span>
              <span className="font-bold tabular-nums text-destructive">{fmtCedi(balance)}</span>
            </div>
          )}
          {bill.dailyArrears > 0 && (
            <div className="flex justify-between rounded-lg bg-amber-500/10 px-3 py-2">
              <span className="font-medium">Daily services arrears (meals, water…)</span>
              <span className="font-bold tabular-nums text-amber-700 dark:text-amber-400">{fmtCedi(bill.dailyArrears)}</span>
            </div>
          )}
          {creditApplied > 0 && (
            <div className="flex justify-between rounded-lg bg-emerald-500/10 px-3 py-2">
              <span className="font-medium">LESS: Prepaid credit applied</span>
              <span className="font-bold tabular-nums text-emerald-700 dark:text-emerald-400">−{fmtCedi(creditApplied)}</span>
            </div>
          )}
          {bill.transport && (
            <div className="flex justify-between rounded-lg bg-secondary/60 px-3 py-2">
              <span className="font-medium">Bus route {bill.transport.route} — next term fare</span>
              <span className="font-semibold tabular-nums">{fmtCedi(bill.transport.fare)}</span>
            </div>
          )}
          <div className="flex justify-between rounded-lg bg-primary/10 px-3 py-2.5">
            <span className="font-bold">Total to settle / bring for resumption</span>
            <span className="font-bold tabular-nums text-primary text-lg">{fmtCedi(grandTotal)}</span>
          </div>
          {grandTotal > 0 && <div className="text-[10px] italic text-muted-foreground text-right">{amountInWords(grandTotal)}</div>}
        </div>

        <div className="grid grid-cols-2 gap-8 pt-5 text-[10px] text-muted-foreground">
          <div className="border-t border-border pt-1">Bursar&apos;s signature</div>
          <div className="border-t border-border pt-1 text-right">Parent / guardian signature</div>
        </div>
      </div>
      <KenteStrip />
    </div>
  );
}

/* ── EXPENDITURE ────────────────────────────────────────────── */
function ExpenditureTab({ data, onRefresh }: { data: FinanceData; onRefresh: () => void }) {
  const { currentSchoolId, setModule, session } = useStore();
  const [dialog, setDialog] = useState(false);
  const [category, setCategory] = useState("UTILITIES");
  const [description, setDescription] = useState("");
  const [amount, setAmount] = useState("");
  const [method, setMethod] = useState("CASH");
  const [busy, setBusy] = useState(false);
  // expense-list filters
  const [expCat, setExpCat] = useState("all");
  const [expMethod, setExpMethod] = useState("all");
  const [expSearch, setExpSearch] = useState("");
  const max = Math.max(1, ...data.expenditure.byCategory.map((c) => c.amount));
  const expFiltersActive = expCat !== "all" || expMethod !== "all" || !!expSearch.trim();
  const filteredExpenses = useMemo(() => data.expenditure.recent.filter((e) =>
    (expCat === "all" || e.category === expCat) &&
    (expMethod === "all" || e.method === expMethod) &&
    (!expSearch.trim() || `${e.description} ${e.category} ${e.method}`.toLowerCase().includes(expSearch.toLowerCase()))
  ), [data, expCat, expMethod, expSearch]);

  async function submit() {
    const cents = Math.round(parseFloat(amount || "0") * 100);
    if (!description || !cents) { toast({ title: "Description and amount are required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/finance`, { action: "save-expense", category, description, amountCents: cents, method });
      toast({ title: "Expense recorded" });
      setDialog(false); setDescription(""); setAmount("");
      onRefresh();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Card>
        <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
          <CardTitle className="text-base">Category breakdown</CardTitle>
          <Button size="sm" className="gap-1" onClick={() => setDialog(true)}><Plus className="size-4" /> Expense</Button>
        </CardHeader>
        <CardContent className="space-y-3">
          {data.expenditure.byCategory.length === 0 && <EmptyState title="No expenses recorded yet" hint="Track utilities, salaries, maintenance and supplies to see the true net position." />}
          {data.expenditure.byCategory.map((c) => (
            <div key={c.category}>
              <div className="flex justify-between text-sm">
                <span>{catLabel(c.category)}</span>
                <span className="tabular-nums font-semibold">{fmtCedi(c.amount, { compact: true })}</span>
              </div>
              <div className="h-1.5 rounded-full bg-secondary mt-1 overflow-hidden">
                <div className="h-full bg-amber-600" style={{ width: `${Math.round((c.amount / max) * 100)}%` }} />
              </div>
            </div>
          ))}
        </CardContent>
      </Card>
      <Card className="lg:col-span-2">
        <CardHeader className="pb-3 flex-row items-center justify-between space-y-0 flex-wrap gap-2">
          <CardTitle className="text-base">Recent expenses</CardTitle>
          {/* staff costs live in HR & Payroll — surfaced here so the bursar's
              expenditure view links to salary runs without hunting the nav */}
          {canModule(session?.role, session?.permissions, "payroll") && (
            <Button size="sm" variant="outline" className="gap-1" title="Salaries & staff costs — HR & Payroll module" onClick={() => setModule("payroll")}>
              Payroll runs <ArrowUpRight className="size-3.5" />
            </Button>
          )}
        </CardHeader>
        <CardContent className="p-0">
          <div className="px-3 py-2 border-b">
            <FilterBar active={expFiltersActive} onClear={() => { setExpCat("all"); setExpMethod("all"); setExpSearch(""); }} count={filteredExpenses.length} total={data.expenditure.recent.length} label="Expense filters">
              <FilterSearch value={expSearch} onChange={setExpSearch} placeholder="Description…" />
              <FilterSelect label="Category" value={expCat} onChange={setExpCat}
                options={[{ value: "all", label: "All" }, ...EXPENSE_CATEGORIES.map((c) => ({ value: c.key, label: c.label }))]} />
              <FilterSelect label="Method" value={expMethod} onChange={setExpMethod}
                options={[{ value: "all", label: "All" }, ...Object.entries(PAYMENT_LABELS).map(([k, v]) => ({ value: k, label: v }))]} />
            </FilterBar>
          </div>
          <div className="overflow-x-auto nice-scroll max-h-[440px]">
            <table className="w-full text-sm">
              <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                <tr className="text-left border-b">
                  <th className="px-3 py-2.5 font-medium">Date</th>
                  <th className="px-3 py-2.5 font-medium">Description</th>
                  <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Category</th>
                  <th className="px-3 py-2.5 font-medium hidden md:table-cell">Method</th>
                  <th className="px-3 py-2.5 font-medium text-right">Amount</th>
                  <th className="px-3 py-2.5"></th>
                </tr>
              </thead>
              <tbody>
                {filteredExpenses.map((e) => (
                  <tr key={e.id} className="border-b last:border-0 hover:bg-secondary/30">
                    <td className="px-3 py-2.5 text-muted-foreground whitespace-nowrap">{fmtDate(e.date)}</td>
                    <td className="px-3 py-2.5"><div className="font-medium">{e.description}</div><div className="text-[11px] text-muted-foreground lg:hidden">{catLabel(e.category)}</div></td>
                    <td className="px-3 py-2.5 hidden sm:table-cell"><Badge variant="outline" className="text-[10px]">{catLabel(e.category)}</Badge></td>
                    <td className="px-3 py-2.5 hidden md:table-cell text-muted-foreground text-xs">{PAYMENT_LABELS[e.method] ?? e.method}</td>
                    <td className="px-3 py-2.5 text-right tabular-nums font-semibold">{fmtCedi(e.amount)}</td>
                    <td className="px-3 py-2.5 text-right">
                      <button className="text-muted-foreground hover:text-destructive" onClick={async () => { await apiPost(`/api/school/${currentSchoolId}/ops/finance`, { action: "delete-expense", id: e.id }); toast({ title: "Expense deleted" }); onRefresh(); }}>
                        <Trash2 className="size-3.5" />
                      </button>
                    </td>
                  </tr>
                ))}
                {filteredExpenses.length === 0 && <tr><td colSpan={6}><EmptyState title={expFiltersActive ? "No expenses match" : "Nothing recorded yet"} hint={expFiltersActive ? "Adjust the search, category or method filter." : undefined} /></td></tr>}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <Dialog open={dialog} onOpenChange={setDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Record an expense</DialogTitle>
            <DialogDescription>Keep the bursar&apos;s book digital — feeds straight into the net position and expenditure analytics.</DialogDescription>
          </DialogHeader>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5 sm:col-span-2">
              <Label>Category</Label>
              <Select value={category} onValueChange={setCategory}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{EXPENSE_CATEGORIES.map((c) => <SelectItem key={c.key} value={c.key}>{c.label}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5 sm:col-span-2"><Label>Description *</Label><Input value={description} onChange={(e) => setDescription(e.target.value)} placeholder="e.g. ECG postpaid — September" /></div>
            <div className="space-y-1.5"><Label>Amount (₵) *</Label><Input type="number" step="0.01" value={amount} onChange={(e) => setAmount(e.target.value)} /></div>
            <div className="space-y-1.5">
              <Label>Paid via</Label>
              <Select value={method} onValueChange={setMethod}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{Object.entries(PAYMENT_LABELS).map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}</SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialog(false)}>Cancel</Button>
            <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : "Record expense"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

/* ── CASHIER REPORTS (iedupro parity: cashier dashboard / summary / collections) ──
   Daily account rendering & reconciliation — filter by collector, date range,
   class and payment method; per-service breakdown, method mix, per-cashier
   performance and a printable transactions register. */
type CashierReport = {
  period: { from: string; to: string; collector: string | null };
  totals: { collected: number; transactions: number; studentsServed: number };
  byService: { name: string; amount: number }[];
  byMethod: { name: string; amount: number }[];
  collectors: { name: string; total: number; txns: number }[];
  transactions: { id: string; receiptNo: string; date: string; student: string; classroom: string | null; split: Record<string, number>; total: number; method: string; collector: string }[];
  cashiers: string[];
  classrooms: { id: string; name: string }[];
};

function CashierTab() {
  const { currentSchoolId, schools } = useStore();
  const schoolName = schools.find((s) => s.id === currentSchoolId)?.name ?? "";
  const today = new Date().toISOString().slice(0, 10);
  const [collector, setCollector] = useState("ALL");
  const [from, setFrom] = useState(today);
  const [to, setTo] = useState(today);
  const [classroomId, setClassroomId] = useState("ALL");
  const [method, setMethod] = useState("ALL");
  const [report, setReport] = useState<CashierReport | null>(null);
  const [busy, setBusy] = useState(false);
  const [search, setSearch] = useState("");

  async function load() {
    setBusy(true);
    try {
      const res = await apiPost<CashierReport>(`/api/school/${currentSchoolId}/ops/finance`, {
        action: "cashier-report",
        collector: collector === "ALL" ? undefined : collector,
        dateFrom: from, dateTo: to,
        classroomId: classroomId === "ALL" ? undefined : classroomId,
        method: method === "ALL" ? undefined : method,
      });
      setReport(res);
    } catch (e: any) { toast({ title: "Failed to load report", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  useEffect(() => { load(); }, []);

  const txns = useMemo(() => (report?.transactions ?? []).filter((t) =>
    !search || t.student.toLowerCase().includes(search) || t.receiptNo.toLowerCase().includes(search) || (t.classroom ?? "").toLowerCase().includes(search)
  ), [report, search]);

  const allSplitKeys = useMemo(() => {
    const keys = new Set<string>();
    (report?.transactions ?? []).forEach((t) => Object.keys(t.split).forEach((k) => keys.add(k)));
    return [...keys];
  }, [report]);

  return (
    <div className="space-y-4">
      {/* filters */}
      <Card>
        <CardContent className="p-4">
          <div className="text-sm font-medium mb-3 flex items-center gap-2"><Search className="size-4 text-primary" /> Filter cashier report</div>
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div className="space-y-1"><Label className="text-xs">Cashier</Label>
              <Select value={collector} onValueChange={setCollector}>
                <SelectTrigger className="h-8 text-xs"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="ALL">All cashiers</SelectItem>
                  {(report?.cashiers ?? []).map((c) => <SelectItem key={c} value={c}>{c}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1"><Label className="text-xs">From</Label><DateField className="h-8 text-xs" value={from} onChange={(v) => setFrom(v ?? "")} /></div>
            <div className="space-y-1"><Label className="text-xs">To</Label><DateField className="h-8 text-xs" value={to} onChange={(v) => setTo(v ?? "")} /></div>
            <div className="space-y-1"><Label className="text-xs">Class</Label>
              <Select value={classroomId} onValueChange={setClassroomId}>
                <SelectTrigger className="h-8 text-xs"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="ALL">All classes</SelectItem>
                  {(report?.classrooms ?? []).map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1"><Label className="text-xs">Method</Label>
              <Select value={method} onValueChange={setMethod}>
                <SelectTrigger className="h-8 text-xs"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="ALL">All methods</SelectItem>
                  {Object.entries(PAYMENT_LABELS).map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="flex items-end"><Button size="sm" className="w-full gap-1.5" onClick={load} disabled={busy}><ReceiptText className="size-4" /> {busy ? "Loading…" : "Load report"}</Button></div>
          </div>
        </CardContent>
      </Card>

      {/* headline stats */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<Banknote className="size-4" />} label="Total collected" value={fmtCedi(report?.totals.collected ?? 0, { compact: true })} tone="primary" sub={report ? `${fmtDate(report.period.from)} – ${fmtDate(report.period.to)}` : ""} />
        <StatCard icon={<ReceiptText className="size-4" />} label="Transactions" value={String(report?.totals.transactions ?? 0)} />
        <StatCard icon={<Users className="size-4" />} label="Learners served" value={String(report?.totals.studentsServed ?? 0)} tone="gold" />
        <StatCard icon={<Wallet className="size-4" />} label="Report mode" value={collector === "ALL" ? "All cashiers" : "One cashier"} sub={collector === "ALL" ? "dashboard & summary" : "collections rendering"} />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* collections by service */}
        <Card>
          <CardHeader className="pb-3"><CardTitle className="text-base">Collections by service</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {(report?.byService ?? []).length === 0 && <EmptyState title="Nothing collected in this window" />}
            {(report?.byService ?? []).map((s) => (
              <div key={s.name} className="flex items-center justify-between rounded-lg border px-3 py-2">
                <div className="flex items-center gap-2 text-sm"><Receipt className="size-3.5 text-primary" /> {s.name}</div>
                <div className="font-semibold tabular-nums">{fmtCedi(s.amount)}</div>
              </div>
            ))}
            {(report?.byService ?? []).length > 0 && (
              <div className="flex items-center justify-between rounded-lg bg-primary/10 border border-primary/30 px-3 py-2 mt-1">
                <div className="text-sm font-semibold">Grand total</div>
                <div className="font-bold tabular-nums text-primary">{fmtCedi(report?.totals.collected ?? 0)}</div>
              </div>
            )}
          </CardContent>
        </Card>

        {/* payment methods + collector performance */}
        <Card>
          <CardHeader className="pb-3"><CardTitle className="text-base">Payment methods & cashier performance</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
              {(report?.byMethod ?? []).map((m) => (
                <div key={m.name} className="rounded-lg border p-2.5">
                  <div className="text-[10px] uppercase tracking-wide text-muted-foreground">{PAYMENT_LABELS[m.name] ?? m.name}</div>
                  <div className="text-sm font-semibold tabular-nums">{fmtCedi(m.amount, { compact: true })}</div>
                </div>
              ))}
              {(report?.byMethod ?? []).length === 0 && <div className="col-span-full text-xs text-muted-foreground">No payments in this window.</div>}
            </div>
            {collector === "ALL" && (
              <div className="overflow-x-auto nice-scroll">
                <table className="w-full text-sm">
                  <thead className="bg-secondary/70">
                    <tr className="text-left border-b text-xs uppercase tracking-wide text-muted-foreground">
                      <th className="px-3 py-2 font-medium">Cashier</th>
                      <th className="px-3 py-2 font-medium text-right">Collected</th>
                      <th className="px-3 py-2 font-medium text-right hidden sm:table-cell">Txns</th>
                      <th className="px-3 py-2 font-medium text-right">% of total</th>
                    </tr>
                  </thead>
                  <tbody>
                    {(report?.collectors ?? []).map((c) => (
                      <tr key={c.name} className="border-b last:border-0">
                        <td className="px-3 py-2 font-medium">{c.name}</td>
                        <td className="px-3 py-2 text-right tabular-nums">{fmtCedi(c.total)}</td>
                        <td className="px-3 py-2 text-right tabular-nums hidden sm:table-cell">{c.txns}</td>
                        <td className="px-3 py-2 text-right tabular-nums">{report?.totals.collected ? Math.round((c.total / report.totals.collected) * 100) : 0}%</td>
                      </tr>
                    ))}
                    {(report?.collectors ?? []).length === 0 && <tr><td colSpan={4} className="px-3 py-3 text-xs text-muted-foreground">No collections recorded.</td></tr>}
                  </tbody>
                </table>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* transactions register */}
      <Card>
        <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
          <CardTitle className="text-base">Transactions ({report?.totals.transactions ?? 0})</CardTitle>
          <div className="flex items-center gap-2">
            <Input placeholder="Search learner / receipt…" className="h-8 w-56 text-xs" value={search} onChange={(e) => setSearch(e.target.value)} />
            <Button size="sm" variant="outline" className="h-8 gap-1" onClick={() => window.print()}><Printer className="size-3.5" /> Print</Button>
          </div>
        </CardHeader>
        <CardContent className="p-0">
          <div className="overflow-x-auto nice-scroll max-h-[480px]">
            <table className="w-full text-sm">
              <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                <tr className="text-left border-b text-xs uppercase tracking-wide text-muted-foreground">
                  <th className="px-3 py-2.5 font-medium">#</th>
                  <th className="px-3 py-2.5 font-medium">Date</th>
                  <th className="px-3 py-2.5 font-medium">Learner</th>
                  <th className="px-3 py-2.5 font-medium hidden md:table-cell">Class</th>
                  {allSplitKeys.map((k) => <th key={k} className="px-3 py-2.5 font-medium text-right hidden lg:table-cell">{k}</th>)}
                  <th className="px-3 py-2.5 font-medium text-right">Total</th>
                  <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Method</th>
                  <th className="px-3 py-2.5 font-medium hidden xl:table-cell">Cashier</th>
                </tr>
              </thead>
              <tbody>
                {txns.length === 0 && <tr><td colSpan={5 + allSplitKeys.length}><EmptyState title="No transactions in this window" /></td></tr>}
                {txns.map((t, i) => (
                  <tr key={t.id} className="border-b last:border-0 hover:bg-secondary/30">
                    <td className="px-3 py-2.5 text-xs text-muted-foreground">{i + 1}</td>
                    <td className="px-3 py-2.5 text-xs whitespace-nowrap">{new Date(t.date).toLocaleDateString("en-GB", { day: "numeric", month: "short" })}</td>
                    <td className="px-3 py-2.5 font-medium">{t.student}</td>
                    <td className="px-3 py-2.5 hidden md:table-cell text-muted-foreground text-xs">{t.classroom ?? "—"}</td>
                    {allSplitKeys.map((k) => (
                      <td key={k} className="px-3 py-2.5 text-right tabular-nums text-xs hidden lg:table-cell">{t.split[k] ? fmtCedi(t.split[k], { compact: true }) : "—"}</td>
                    ))}
                    <td className="px-3 py-2.5 text-right font-semibold tabular-nums">{fmtCedi(t.total)}</td>
                    <td className="px-3 py-2.5 hidden sm:table-cell"><Badge variant="outline" className="text-[10px]">{PAYMENT_LABELS[t.method] ?? t.method}</Badge></td>
                    <td className="px-3 py-2.5 hidden xl:table-cell text-xs text-muted-foreground">{t.collector}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <p className="px-3 py-2 text-[10px] text-muted-foreground border-t">
            {schoolName} · cashier collections report · rendered {new Date().toLocaleString("en-GB")}
          </p>
        </CardContent>
      </Card>
    </div>
  );
}

/* ── AGING REPORT (CI3 advanced receivables aging — dueAt-based buckets) ──
   Buckets: Current 0–30 (#10b981), 31–60 (#f59e0b), 61–90 (#ef4444), Over 90 (#991b1b).
   Age is counted from the due date when present, else the invoice date. */
type AgingRow = {
  student: string; classroom: string | null; invoiceNo: string; invoiceDate: string;
  dueDate: string | null; daysOutstanding: number; balanceCents: number; bucket: "current" | "d31" | "d61" | "d90";
};
type AgingReport = {
  summary: { currentCents: number; currentCount: number; d31Cents: number; d31Count: number; d61Cents: number; d61Count: number; d90Cents: number; d90Count: number };
  rows: AgingRow[];
  totalCents: number;
  totalCount: number;
};

const AGING_BUCKETS: { key: "current" | "d31" | "d61" | "d90"; label: string; color: string }[] = [
  { key: "current", label: "Current (0–30 days)", color: "#10b981" },
  { key: "d31", label: "31–60 days", color: "#f59e0b" },
  { key: "d61", label: "61–90 days", color: "#ef4444" },
  { key: "d90", label: "Over 90 days", color: "#991b1b" },
];

function AgingTab() {
  const { currentSchoolId, schools } = useStore();
  const schoolName = schools.find((s) => s.id === currentSchoolId)?.name ?? "";
  const [report, setReport] = useState<AgingReport | null>(null);
  const [busy, setBusy] = useState(true);
  const [filter, setFilter] = useState<"all" | AgingRow["bucket"]>("all");

  const load = () => {
    if (!currentSchoolId) return;
    setBusy(true);
    apiGet<AgingReport>(`/api/school/${currentSchoolId}/ops/reports?tab=aging`)
      .then(setReport)
      .catch((e) => toast({ title: "Could not load aging report", description: e.message, variant: "destructive" }))
      .finally(() => setBusy(false));
  };
  useEffect(() => { load(); }, [currentSchoolId]); // eslint-disable-line react-hooks/set-state-in-effect

  const rows = (report?.rows ?? []).filter((r) => filter === "all" || r.bucket === filter);

  function exportCsv() {
    if (!report) return;
    const header = ["Student", "Class", "Invoice", "Invoice Date", "Due Date", "Days Outstanding", "Balance (₵)", "Aging Bucket"];
    const lines = report.rows.map((r) => [
      r.student, r.classroom ?? "", r.invoiceNo, r.invoiceDate.slice(0, 10), r.dueDate ? r.dueDate.slice(0, 10) : "",
      r.daysOutstanding, r.balanceCents / 100, AGING_BUCKETS.find((b) => b.key === r.bucket)?.label ?? r.bucket,
    ]);
    lines.push(["", "", "", "", "TOTAL", "", report.totalCents / 100, `${report.totalCount} invoices`]);
    const csv = [header, ...lines].map((l) => l.map((c) => `"${String(c ?? "").replace(/"/g, '""')}"`).join(",")).join("\n");
    const url = URL.createObjectURL(new Blob([csv], { type: "text/csv" }));
    const a = document.createElement("a"); a.href = url; a.download = "receivables-aging.csv"; a.click(); URL.revokeObjectURL(url);
  }

  if (!report && busy) return <Card><CardContent><LoadingBlock label="Aging the receivables…" /></CardContent></Card>;

  return (
    <div className="space-y-4">
      {/* bucket cards — CI3 colors */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 no-print">
        {AGING_BUCKETS.map((b) => {
          const cents = report ? report.summary[`${b.key}Cents` as keyof AgingReport["summary"]] as number : 0;
          const count = report ? report.summary[`${b.key}Count` as keyof AgingReport["summary"]] as number : 0;
          return (
            <button key={b.key} type="button" onClick={() => setFilter(filter === b.key ? "all" : b.key)}
              className={`rounded-xl border bg-card p-4 text-left transition-colors ${filter === b.key ? "ring-2 ring-offset-background" : "hover:bg-secondary/40"}`}
              style={filter === b.key ? { boxShadow: `0 0 0 2px ${b.color}` } : undefined}>
              <div className="flex items-center justify-between">
                <span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{b.label}</span>
                <span className="size-2.5 rounded-full" style={{ backgroundColor: b.color }} />
              </div>
              <div className="mt-2 text-2xl font-bold tabular-nums" style={{ color: b.color }}>{fmtCedi(cents, { compact: true })}</div>
              <div className="mt-1 text-xs text-muted-foreground">{count} invoice{count === 1 ? "" : "s"}</div>
            </button>
          );
        })}
      </div>

      <div className="flex flex-wrap items-center justify-between gap-2 no-print">
        <div className="flex items-center gap-2 text-sm text-muted-foreground">
          <Timer className="size-4" />
          {filter === "all"
            ? <>All {report?.totalCount ?? 0} open invoices · <b className="text-foreground tabular-nums">{fmtCedi(report?.totalCents ?? 0)}</b> receivables</>
            : <>Filtered to <b className="text-foreground">{AGING_BUCKETS.find((b) => b.key === filter)?.label}</b> — <button className="underline" onClick={() => setFilter("all")}>clear</button></>}
        </div>
        <div className="flex gap-2">
          <Button size="sm" variant="outline" className="gap-1.5" disabled={!report} onClick={exportCsv}><Download className="size-4" /> Export CSV</Button>
          <Button size="sm" variant="outline" className="gap-1.5" disabled={!report} onClick={() => window.print()}><Printer className="size-4" /> Print</Button>
          <Button size="sm" variant="ghost" className="gap-1.5" onClick={load}><ArrowUpRight className="size-4" /> Refresh</Button>
        </div>
      </div>

      {/* printable aging statement */}
      <div className="print-area">
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-base">Receivables aging — {schoolName}</CardTitle>
            <p className="text-xs text-muted-foreground">
              Aged from the due date when set (invoice date otherwise) · rendered {new Date().toLocaleString("en-GB")}
            </p>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto nice-scroll max-h-[560px]">
              <table className="w-full text-sm">
                <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                  <tr className="text-left border-b">
                    <th className="px-3 py-2.5 font-medium">Student</th>
                    <th className="px-3 py-2.5 font-medium hidden md:table-cell">Class</th>
                    <th className="px-3 py-2.5 font-medium">Invoice</th>
                    <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Date</th>
                    <th className="px-3 py-2.5 font-medium text-right">Days outstanding</th>
                    <th className="px-3 py-2.5 font-medium text-right">Balance</th>
                    <th className="px-3 py-2.5 font-medium">Status</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.length === 0 && <tr><td colSpan={7}><EmptyState title="No open receivables" hint="Every invoice in this bucket is settled — the school is whole." /></td></tr>}
                  {rows.map((r) => {
                    const b = AGING_BUCKETS.find((x) => x.key === r.bucket)!;
                    return (
                      <tr key={r.invoiceNo} className="border-b last:border-0 hover:bg-secondary/30">
                        <td className="px-3 py-2.5 font-medium">{r.student}</td>
                        <td className="px-3 py-2.5 hidden md:table-cell text-muted-foreground">{r.classroom ?? "—"}</td>
                        <td className="px-3 py-2.5 font-mono text-xs">{r.invoiceNo}</td>
                        <td className="px-3 py-2.5 hidden sm:table-cell text-muted-foreground whitespace-nowrap">{fmtDate(r.invoiceDate)}</td>
                        <td className="px-3 py-2.5 text-right tabular-nums">{r.daysOutstanding}</td>
                        <td className="px-3 py-2.5 text-right tabular-nums font-semibold text-destructive">{fmtCedi(r.balanceCents)}</td>
                        <td className="px-3 py-2.5">
                          <span className="text-[11px] font-semibold px-2 py-0.5 rounded-full text-white whitespace-nowrap" style={{ backgroundColor: b.color }}>{b.label}</span>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
            <div className="grid grid-cols-2 sm:grid-cols-4 border-t">
              {AGING_BUCKETS.map((b) => (
                <div key={b.key} className="px-4 py-2.5 border-r last:border-0">
                  <div className="text-[10px] uppercase tracking-wide text-muted-foreground">{b.label}</div>
                  <div className="text-sm font-bold tabular-nums" style={{ color: b.color }}>
                    {fmtCedi(report ? (report.summary[`${b.key}Cents` as keyof AgingReport["summary"]] as number) : 0)}
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>

      <Card className="no-print">
        <CardContent className="text-sm text-muted-foreground space-y-2 pt-5">
          <p>
            <b className="text-foreground">{report?.summary.d90Count ?? 0}</b> invoices are beyond 90 days — these belong on the association defaulter watch-list (Fees &amp; Billing → flag) and qualify for guardian SMS campaigns from the SMS Centre.
          </p>
          <p>
            Use <b className="text-foreground">Terminal bills</b> to print end-of-term arrears slips with next-term bills attached, exactly like the paper terminal bills parents expect.
          </p>
        </CardContent>
      </Card>
    </div>
  );
}

/* ── Advanced reports (CI3 Advanced_reports parity) ──
   Financial summary · cash flow by month · payment-method mix (split
   payments expanded) · expense categories · per-student financial profile,
   with CSV exports. */
type AdvData = {
  range: { from: string; to: string };
  summary: {
    invoices: number; billedCents: number; collectedCents: number; outstandingCents: number;
    overdueCents: number; overdueCount: number; payments: number; avgPaymentCents: number;
    expenses: number; expenseCents: number; netCents: number;
  };
  cashFlow: { period: string; inflowCents: number; outflowCents: number; netCents: number }[];
  methods: { method: string; count: number; totalCents: number; avgCents: number }[];
  categories: { category: string; count: number; totalCents: number }[];
  profile: null | {
    student: string; classroom: string | null;
    summary: { invoices: number; billedCents: number; paidCents: number; outstandingCents: number };
    invoices: { invoiceNo: string; createdAt: string; totalCents: number; paidCents: number; balanceCents: number; status: string }[];
    payments: { receiptNo: string; paidAt: string; amountCents: number; method: string }[];
    monthly: { month: string; totalCents: number }[];
  };
};

function csvDownload(filename: string, header: string[], rows: (string | number)[][]) {
  const esc = (v: string | number) => `"${String(v).replace(/"/g, '""')}"`;
  const csv = [header, ...rows].map((r) => r.map(esc).join(",")).join("\n");
  const url = URL.createObjectURL(new Blob(["\uFEFF" + csv], { type: "text/csv;charset=utf-8" }));
  const a = document.createElement("a");
  a.href = url; a.download = filename; a.click();
  URL.revokeObjectURL(url);
}

function AdvancedReportsTab() {
  const { currentSchoolId } = useStore();
  const [from, setFrom] = useState(() => new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10));
  const [to, setTo] = useState(() => new Date().toISOString().slice(0, 10));
  const [data, setData] = useState<AdvData | null>(null);
  const [students, setStudents] = useState<{ id: string; name: string; classroom: string | null }[]>([]);
  const [studentId, setStudentId] = useState<string>("");

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<AdvData>(`/api/school/${currentSchoolId}/ops/reports?tab=advanced&from=${from}&to=${to}${studentId ? `&studentId=${studentId}` : ""}`)
      .then(setData)
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  }, [currentSchoolId, from, to, studentId]);
  useEffect(() => { load(); }, [load]);

  // learner picker for the financial profile
  useEffect(() => {
    if (!currentSchoolId) return;
    apiGet<any>(`/api/school/${currentSchoolId}/students`)
      .then((d) => setStudents((d.students ?? []).map((x: any) => ({ id: x.id, name: x.name || `${x.firstName ?? ""} ${x.lastName ?? ""}`.trim() || x.nyansapoIndex || "Learner", classroom: x.classroom ?? x.classroomName ?? null }))))
      .catch(() => {});
  }, [currentSchoolId]);

  const chartData = (data?.cashFlow ?? []).map((c) => ({ period: c.period.slice(2), Inflow: c.inflowCents / 100, Outflow: c.outflowCents / 100 }));
  const methodMax = Math.max(1, ...(data?.methods ?? []).map((m) => m.totalCents));

  return (
    <div className="space-y-4">
      <Card>
        <CardContent className="pt-4 flex flex-wrap gap-2 items-end">
          <div className="space-y-1"><Label className="text-xs">From</Label><DateField value={from} onChange={(v) => setFrom(v ?? "")} className="h-9 w-40" /></div>
          <div className="space-y-1"><Label className="text-xs">To</Label><DateField value={to} onChange={(v) => setTo(v ?? "")} className="h-9 w-40" /></div>
          <div className="space-y-1">
            <Label className="text-xs">Learner profile (optional)</Label>
            <Select value={studentId || "none"} onValueChange={setStudentId}>
              <SelectTrigger className="h-9 w-64"><SelectValue placeholder="Pick a learner…" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">None</SelectItem>
                {students.map((x) => <SelectItem key={x.id} value={x.id}>{x.name}{x.classroom ? ` — ${x.classroom}` : ""}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <Button size="sm" className="h-9" onClick={load}>Load</Button>
        </CardContent>
      </Card>

      {!data ? <LoadingBlock label="Crunching the numbers…" /> : (
        <>
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <StatCard icon={<FileText className="size-4" />} label="Billed" value={fmtCedi(data.summary.billedCents, { compact: true })} sub={`${data.summary.invoices} invoices in range`} />
            <StatCard icon={<ArrowDownLeft className="size-4" />} label="Collected" value={fmtCedi(data.summary.collectedCents, { compact: true })} tone="primary" sub={`avg ${fmtCedi(data.summary.avgPaymentCents, { compact: true })} × ${data.summary.payments}`} />
            <StatCard icon={<Receipt className="size-4" />} label="Expenses" value={fmtCedi(data.summary.expenseCents, { compact: true })} sub={`${data.summary.expenses} entries`} />
            <StatCard icon={<PieChart className="size-4" />} label="Net income" value={fmtCedi(data.summary.netCents, { compact: true })} tone={data.summary.netCents >= 0 ? "gold" : "danger"} sub="collected − expenses" />
          </div>

          <div className="grid lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base flex items-center justify-between">Cash flow
                  <Button size="sm" variant="outline" className="h-7 gap-1" onClick={() => csvDownload(`Cash_Flow_${from}_${to}.csv`, ["Period", "Inflow (GHS)", "Outflow (GHS)", "Net (GHS)"], data.cashFlow.map((c) => [c.period, (c.inflowCents / 100).toFixed(2), (c.outflowCents / 100).toFixed(2), (c.netCents / 100).toFixed(2)]))}>
                    <Download className="size-3.5" /> CSV
                  </Button>
                </CardTitle>
              </CardHeader>
              <CardContent>
                {chartData.length ? (
                  <div className="h-56">
                    <ResponsiveContainer width="100%" height="100%">
                      <BarChart data={chartData}>
                        <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                        <XAxis dataKey="period" fontSize={11} /><YAxis fontSize={11} />
                        <Tooltip formatter={(v: number) => fmtCedi(Math.round(v * 100))} />
                        <Legend /><Bar dataKey="Inflow" fill="#10b981" /><Bar dataKey="Outflow" fill="#ef4444" />
                      </BarChart>
                    </ResponsiveContainer>
                  </div>
                ) : <EmptyState title="No movement in this range" />}
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base flex items-center justify-between">Payment methods
                  <Button size="sm" variant="outline" className="h-7 gap-1" onClick={() => csvDownload(`Payment_Methods_${from}_${to}.csv`, ["Method", "Count", "Total (GHS)", "Average (GHS)"], data.methods.map((m) => [m.method, m.count, (m.totalCents / 100).toFixed(2), (m.avgCents / 100).toFixed(2)]))}>
                    <Download className="size-3.5" /> CSV
                  </Button>
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-2">
                {data.methods.length ? data.methods.map((m) => (
                  <div key={m.method}>
                    <div className="flex justify-between text-xs mb-1"><span>{m.method} <span className="text-muted-foreground">× {m.count}</span></span><span className="tabular-nums font-medium">{fmtCedi(m.totalCents)}</span></div>
                    <div className="bg-secondary h-2 rounded-full overflow-hidden"><div className="h-full rounded-full bg-violet-600" style={{ width: `${(m.totalCents / methodMax) * 100}%` }} /></div>
                  </div>
                )) : <EmptyState title="No payments in this range" />}
                <p className="text-[11px] text-muted-foreground pt-1">Split collections are expanded into each method that formed them.</p>
              </CardContent>
            </Card>
          </div>

          <div className="grid lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base flex items-center justify-between">Expenses by category
                  <Button size="sm" variant="outline" className="h-7 gap-1" onClick={() => csvDownload(`Category_Expenses_${from}_${to}.csv`, ["Category", "Count", "Total (GHS)"], data.categories.map((c) => [c.category, c.count, (c.totalCents / 100).toFixed(2)]))}>
                    <Download className="size-3.5" /> CSV
                  </Button>
                </CardTitle>
              </CardHeader>
              <CardContent>
                {data.categories.length ? (
                  <Table>
                    <TableHeader><TableRow><TableHead>Category</TableHead><TableHead className="text-right">Count</TableHead><TableHead className="text-right">Total</TableHead></TableRow></TableHeader>
                    <TableBody>
                      {data.categories.map((c) => (
                        <TableRow key={c.category}>
                          <TableCell className="font-medium">{c.category}</TableCell>
                          <TableCell className="text-right tabular-nums">{c.count}</TableCell>
                          <TableCell className="text-right tabular-nums">{fmtCedi(c.totalCents)}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                ) : <EmptyState title="No expenses in this range" />}
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base">Outstanding &amp; overdue</CardTitle></CardHeader>
              <CardContent className="grid grid-cols-2 gap-3">
                <div className="rounded-lg border p-4">
                  <div className="text-2xl font-bold tabular-nums text-primary">{fmtCedi(data.summary.outstandingCents, { compact: true })}</div>
                  <div className="text-xs text-muted-foreground">total outstanding (all open invoices)</div>
                </div>
                <div className="rounded-lg border p-4 bg-red-50 dark:bg-red-950/20">
                  <div className="text-2xl font-bold tabular-nums text-red-600">{fmtCedi(data.summary.overdueCents, { compact: true })}</div>
                  <div className="text-xs text-muted-foreground">overdue · {data.summary.overdueCount} past due date</div>
                </div>
                <div className="col-span-2 text-[11px] text-muted-foreground">Full bucket breakdown (current / 31–60 / 61–90 / 90+) lives in the Receivables ageing tab.</div>
              </CardContent>
            </Card>
          </div>

          {data.profile && (
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base">
                  Learner profile — {data.profile.student}
                  <span className="text-sm text-muted-foreground font-normal"> {data.profile.classroom ? `· ${data.profile.classroom}` : ""}</span>
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                  <div className="rounded-lg border p-3"><div className="text-lg font-bold tabular-nums">{data.profile.summary.invoices}</div><div className="text-xs text-muted-foreground">invoices</div></div>
                  <div className="rounded-lg border p-3"><div className="text-lg font-bold tabular-nums">{fmtCedi(data.profile.summary.billedCents, { compact: true })}</div><div className="text-xs text-muted-foreground">billed</div></div>
                  <div className="rounded-lg border p-3"><div className="text-lg font-bold tabular-nums text-emerald-600">{fmtCedi(data.profile.summary.paidCents, { compact: true })}</div><div className="text-xs text-muted-foreground">paid</div></div>
                  <div className="rounded-lg border p-3"><div className="text-lg font-bold tabular-nums text-red-600">{fmtCedi(data.profile.summary.outstandingCents, { compact: true })}</div><div className="text-xs text-muted-foreground">outstanding</div></div>
                </div>
                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <div className="text-sm font-semibold mb-1.5">Invoices</div>
                    <div className="max-h-52 overflow-y-auto nice-scroll">
                      <Table>
                        <TableHeader><TableRow><TableHead>Invoice</TableHead><TableHead>Date</TableHead><TableHead className="text-right">Balance</TableHead></TableRow></TableHeader>
                        <TableBody>
                          {data.profile.invoices.map((i) => (
                            <TableRow key={i.invoiceNo}>
                              <TableCell className="font-mono text-xs">{i.invoiceNo}</TableCell>
                              <TableCell className="text-xs">{fmtDate(i.createdAt)}</TableCell>
                              <TableCell className={cn("text-right tabular-nums text-xs font-medium", i.balanceCents > 0 ? "text-red-600" : "text-emerald-600")}>{fmtCedi(i.balanceCents)}</TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </div>
                  </div>
                  <div>
                    <div className="text-sm font-semibold mb-1.5">Recent payments</div>
                    <div className="max-h-52 overflow-y-auto nice-scroll">
                      <Table>
                        <TableHeader><TableRow><TableHead>Receipt</TableHead><TableHead>Method</TableHead><TableHead className="text-right">Amount</TableHead></TableRow></TableHeader>
                        <TableBody>
                          {data.profile.payments.map((p) => (
                            <TableRow key={p.receiptNo}>
                              <TableCell className="font-mono text-xs">{p.receiptNo}</TableCell>
                              <TableCell className="text-xs">{p.method}</TableCell>
                              <TableCell className="text-right tabular-nums text-xs">{fmtCedi(p.amountCents)}</TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          )}
        </>
      )}
    </div>
  );
}

/* ════════════════════════════════════════════════════════════════
   CI3 STATEMENTS — Task 5 parity with scripts/ci3app …/admin/reports/
   (receivables · payables · income_expenditure). Everything renders as a
   printable statement: per-class / per-category sub-total rows in bold,
   grand-total footers and the two-sided account with SURPLUS/(DEFICIT). */
type AgingBuckets = { currentCents: number; d1Cents: number; d31Cents: number; d61Cents: number; d90Cents: number };
const AGING_COLS: { key: keyof AgingBuckets; label: string }[] = [
  { key: "currentCents", label: "Current" },
  { key: "d1Cents", label: "1–30" },
  { key: "d31Cents", label: "31–60" },
  { key: "d61Cents", label: "61–90" },
  { key: "d90Cents", label: "90+" },
];

function StatementsTab() {
  const { navTarget } = useStore();
  const [rep, setRep] = useState<"receivables" | "payables" | "income-expenditure">("receivables");
  /* deep link from the Reports module quick cards picks the exact statement */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    const t = navTarget?.tab;
    if (t === "payables") setRep("payables");
    if (t === "income-expenditure") setRep("income-expenditure");
    if (t === "statements" || t === "receivables") setRep("receivables");
  }
  const PICKS: { key: typeof rep; label: string; icon: typeof Timer }[] = [
    { key: "receivables", label: "Receivables", icon: Timer },
    { key: "payables", label: "Payables", icon: HandCoins },
    { key: "income-expenditure", label: "Income & Expenditure", icon: Scale },
  ];
  return (
    <div className="space-y-4">
      <div className="no-print flex flex-wrap gap-1.5">
        {PICKS.map((p) => (
          <Button key={p.key} size="sm" variant={rep === p.key ? "default" : "outline"} className="gap-1.5" onClick={() => setRep(p.key)}>
            <p.icon className="size-4" /> {p.label}
          </Button>
        ))}
      </div>
      {rep === "receivables" && <ReceivablesReport />}
      {rep === "payables" && <PayablesReport />}
      {rep === "income-expenditure" && <IncomeExpenditureReport />}
    </div>
  );
}

/* ── RECEIVABLES — per-student outstanding on open invoices, aged by due date,
   class sub-totals + grand total (CI3 get_receivables.php + get_fct_ daily
   arrears riding alongside). */
type ReceivablesStatement = {
  period: { from: string | null; to: string | null; termId: string | null };
  terms: { id: string; label: string; isCurrent: boolean }[];
  rows: { studentId: string; name: string; classroom: string | null; invoices: number; billedCents: number; discountCents: number; paidCents: number; outstandingCents: number; dailyArrearsCents: number; aging: AgingBuckets }[];
  classes: { classroom: string; students: number; invoices: number; billedCents: number; paidCents: number; outstandingCents: number; dailyArrearsCents: number; aging: AgingBuckets }[];
  totals: { students: number; invoices: number; billedCents: number; paidCents: number; discountCents: number; outstandingCents: number; dailyArrearsCents: number; aging: AgingBuckets };
  dailyOnly: { students: number; amountCents: number };
};

function ReceivablesReport() {
  const { currentSchoolId, schools } = useStore();
  const schoolName = schools.find((s) => s.id === currentSchoolId)?.name ?? "";
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [termId, setTermId] = useState("all");
  const [data, setData] = useState<ReceivablesStatement | null>(null);
  const [busy, setBusy] = useState(false);

  const load = () => {
    if (!currentSchoolId) return;
    setBusy(true);
    const params = new URLSearchParams({ action: "receivables" });
    if (from) params.set("from", from);
    if (to) params.set("to", to);
    if (termId !== "all") params.set("termId", termId);
    apiGet<ReceivablesStatement>(`/api/school/${currentSchoolId}/ops/finance?${params.toString()}`)
      .then(setData)
      .catch((e) => toast({ title: "Could not load receivables", description: e.message, variant: "destructive" }))
      .finally(() => setBusy(false));
  };
  useEffect(() => { load(); }, []); // eslint-disable-line react-hooks/set-state-in-effect

  // flat render list: class rows, each block closing with a bold sub-total row
  const items = useMemo(() => {
    if (!data) return [] as ({ kind: "row"; row: ReceivablesStatement["rows"][number] } | { kind: "subtotal"; cls: ReceivablesStatement["classes"][number] })[];
    const byClass = new Map(data.classes.map((c) => [c.classroom, c]));
    const out: ({ kind: "row"; row: ReceivablesStatement["rows"][number] } | { kind: "subtotal"; cls: ReceivablesStatement["classes"][number] })[] = [];
    let prev: string | null = null;
    for (const r of data.rows) {
      const cls = r.classroom ?? "Unassigned";
      if (prev !== null && cls !== prev) {
        const sub = byClass.get(prev);
        if (sub) out.push({ kind: "subtotal", cls: sub });
      }
      out.push({ kind: "row", row: r });
      prev = cls;
    }
    if (prev !== null) {
      const sub = byClass.get(prev);
      if (sub) out.push({ kind: "subtotal", cls: sub });
    }
    return out;
  }, [data]);

  const agingCell = (cents: number, bold = false) => (
    <td className={cn("px-3 py-2.5 text-right tabular-nums", bold && "font-semibold", cents === 0 && "text-muted-foreground")}>
      {cents ? fmtCedi(cents) : "—"}
    </td>
  );

  function exportCsv() {
    if (!data) return;
    const header = ["Student", "Class", "Invoices", "Billed (GHS)", "Paid (GHS)", "Outstanding (GHS)", "Daily arrears (GHS)", "Current (GHS)", "1-30 (GHS)", "31-60 (GHS)", "61-90 (GHS)", "90+ (GHS)"];
    const cents = (c: number) => (c / 100).toFixed(2);
    const lines: (string | number)[][] = [];
    for (const it of items) {
      if (it.kind === "row") {
        lines.push([it.row.name, it.row.classroom ?? "", it.row.invoices, cents(it.row.billedCents), cents(it.row.paidCents), cents(it.row.outstandingCents), cents(it.row.dailyArrearsCents), cents(it.row.aging.currentCents), cents(it.row.aging.d1Cents), cents(it.row.aging.d31Cents), cents(it.row.aging.d61Cents), cents(it.row.aging.d90Cents)]);
      } else {
        lines.push([`Sub-total — ${it.cls.classroom}`, `${it.cls.students} learners`, it.cls.invoices, cents(it.cls.billedCents), cents(it.cls.paidCents), cents(it.cls.outstandingCents), cents(it.cls.dailyArrearsCents), cents(it.cls.aging.currentCents), cents(it.cls.aging.d1Cents), cents(it.cls.aging.d31Cents), cents(it.cls.aging.d61Cents), cents(it.cls.aging.d90Cents)]);
      }
    }
    lines.push(["GRAND TOTAL", `${data.totals.students} learners`, data.totals.invoices, cents(data.totals.billedCents), cents(data.totals.paidCents), cents(data.totals.outstandingCents), cents(data.totals.dailyArrearsCents), cents(data.totals.aging.currentCents), cents(data.totals.aging.d1Cents), cents(data.totals.aging.d31Cents), cents(data.totals.aging.d61Cents), cents(data.totals.aging.d90Cents)]);
    csvDownload(`Receivables_${from || "all"}_${to || "all"}.csv`, header, lines);
  }

  const termLabel = data?.terms.find((t) => t.id === termId)?.label ?? null;

  return (
    <div className="space-y-4">
      <Card className="no-print">
        <CardContent className="pt-4 flex flex-wrap gap-2 items-end">
          <div className="space-y-1"><Label className="text-xs">Billed from</Label><DateField value={from} onChange={(v) => setFrom(v ?? "")} className="h-9 w-40" /></div>
          <div className="space-y-1"><Label className="text-xs">Billed to</Label><DateField value={to} onChange={(v) => setTo(v ?? "")} className="h-9 w-40" /></div>
          <div className="space-y-1">
            <Label className="text-xs">Term</Label>
            <Select value={termId} onValueChange={setTermId}>
              <SelectTrigger className="h-9 w-44"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All terms</SelectItem>
                {(data?.terms ?? []).map((t) => <SelectItem key={t.id} value={t.id}>{t.label}{t.isCurrent ? " (current)" : ""}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <Button size="sm" className="h-9 gap-1.5" onClick={load} disabled={busy}><FileText className="size-4" /> {busy ? "Loading…" : "Generate statement"}</Button>
        </CardContent>
      </Card>

      {!data ? <Card><CardContent><LoadingBlock label="Aging the receivables…" /></CardContent></Card> : (
        <>
          <div className="no-print flex flex-wrap items-center justify-between gap-2">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <Timer className="size-4" />
              {data.totals.students} learners · {data.totals.invoices} open invoices · <b className="text-foreground tabular-nums">{fmtCedi(data.totals.outstandingCents)}</b> receivable
            </div>
            <div className="flex gap-2">
              <Button size="sm" variant="outline" className="gap-1.5" onClick={exportCsv}><Download className="size-4" /> Export CSV</Button>
              <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()}><Printer className="size-4" /> Print</Button>
            </div>
          </div>

          <div className="print-area">
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base">Accounts receivables — {schoolName}</CardTitle>
                <p className="text-xs text-muted-foreground">
                  {data.period.from || data.period.to
                    ? `Invoices billed ${data.period.from ? fmtDate(data.period.from) : "…"} – ${data.period.to ? fmtDate(data.period.to) : "…"}`
                    : "All open invoices"}
                  {termLabel ? ` · ${termLabel}` : ""} · aged from the due date · rendered {new Date().toLocaleString("en-GB")}
                </p>
              </CardHeader>
              <CardContent className="p-0">
                <div className="overflow-x-auto nice-scroll max-h-[560px]">
                  <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                      <tr className="text-left border-b text-xs uppercase tracking-wide text-muted-foreground">
                        <th className="px-3 py-2.5 font-medium">Student</th>
                        <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Class</th>
                        <th className="px-3 py-2.5 font-medium text-right">Inv.</th>
                        <th className="px-3 py-2.5 font-medium text-right hidden xl:table-cell">Billed</th>
                        <th className="px-3 py-2.5 font-medium text-right hidden xl:table-cell">Paid</th>
                        <th className="px-3 py-2.5 font-medium text-right">Outstanding</th>
                        <th className="px-3 py-2.5 font-medium text-right hidden md:table-cell">Daily arrears</th>
                        {AGING_COLS.map((c) => <th key={c.key} className="px-3 py-2.5 font-medium text-right">{c.label}</th>)}
                      </tr>
                    </thead>
                    <tbody>
                      {items.length === 0 && <tr><td colSpan={12}><EmptyState title="No open receivables in this window" hint="Every invoice billed in this period is settled — the school is whole." /></td></tr>}
                      {items.map((it, i) => it.kind === "row" ? (
                        <tr key={`r${i}`} className="border-b last:border-0 hover:bg-secondary/30">
                          <td className="px-3 py-2.5 font-medium">{it.row.name}</td>
                          <td className="px-3 py-2.5 hidden lg:table-cell text-muted-foreground">{it.row.classroom ?? "—"}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums">{it.row.invoices}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums hidden xl:table-cell">{fmtCedi(it.row.billedCents)}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums hidden xl:table-cell">{fmtCedi(it.row.paidCents)}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums font-semibold text-destructive">{fmtCedi(it.row.outstandingCents)}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums hidden md:table-cell">{it.row.dailyArrearsCents ? fmtCedi(it.row.dailyArrearsCents) : "—"}</td>
                          {AGING_COLS.map((c) => <Fragment key={c.key}>{agingCell(it.row.aging[c.key])}</Fragment>)}
                        </tr>
                      ) : (
                        <tr key={`s${i}`} className="border-b bg-secondary/50 font-semibold">
                          <td className="px-3 py-2" colSpan={2}>{it.cls.classroom} — sub-total · {it.cls.students} learners</td>
                          <td className="px-3 py-2 text-right tabular-nums">{it.cls.invoices}</td>
                          <td className="px-3 py-2 text-right tabular-nums hidden xl:table-cell">{fmtCedi(it.cls.billedCents)}</td>
                          <td className="px-3 py-2 text-right tabular-nums hidden xl:table-cell">{fmtCedi(it.cls.paidCents)}</td>
                          <td className="px-3 py-2 text-right tabular-nums text-destructive">{fmtCedi(it.cls.outstandingCents)}</td>
                          <td className="px-3 py-2 text-right tabular-nums hidden md:table-cell">{it.cls.dailyArrearsCents ? fmtCedi(it.cls.dailyArrearsCents) : "—"}</td>
                          {AGING_COLS.map((c) => <Fragment key={c.key}>{agingCell(it.cls.aging[c.key], true)}</Fragment>)}
                        </tr>
                      ))}
                      {items.length > 0 && (
                        <tr className="border-t-2 bg-primary/5 font-bold">
                          <td className="px-3 py-3" colSpan={2}>GRAND TOTAL — {data.totals.students} learners</td>
                          <td className="px-3 py-3 text-right tabular-nums">{data.totals.invoices}</td>
                          <td className="px-3 py-3 text-right tabular-nums hidden xl:table-cell">{fmtCedi(data.totals.billedCents)}</td>
                          <td className="px-3 py-3 text-right tabular-nums hidden xl:table-cell">{fmtCedi(data.totals.paidCents)}</td>
                          <td className="px-3 py-3 text-right tabular-nums text-destructive">{fmtCedi(data.totals.outstandingCents)}</td>
                          <td className="px-3 py-3 text-right tabular-nums hidden md:table-cell">{data.totals.dailyArrearsCents ? fmtCedi(data.totals.dailyArrearsCents) : "—"}</td>
                          {AGING_COLS.map((c) => <Fragment key={c.key}>{agingCell(data.totals.aging[c.key], true)}</Fragment>)}
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
                {data.dailyOnly.amountCents > 0 && (
                  <p className="px-3 py-2 text-[10px] text-muted-foreground border-t">
                    A further {fmtCedi(data.dailyOnly.amountCents)} of daily-service arrears sits with {data.dailyOnly.students} learners who have no open invoice (CI3 fct receivables) — collect them from the daily register in Fees.
                  </p>
                )}
              </CardContent>
            </Card>
          </div>
        </>
      )}
    </div>
  );
}

/* ── PAYABLES — everything the school owes: pending supplier purchase orders,
   approved-but-unpaid payroll runs and learner credit balances (CI3 advance
   payments, get_payables.php / get_fct_payables.php parity). */
type PayablesStatement = {
  period: { from: string | null; to: string | null };
  purchaseOrders: { rows: { poNo: string; supplier: string; supplierPhone: string | null; expectedDate: string | null; createdAt: string; lines: number; totalCents: number; note: string | null }[]; suppliers: { supplier: string; phone: string | null; orders: number; totalCents: number }[]; totalCents: number };
  payroll: { rows: { id: string; period: string; status: string; totalGrossCents: number; totalDeductionsCents: number; totalNetCents: number; approvedByName: string | null; createdAt: string }[]; totalCents: number };
  credits: { rows: { studentId: string; name: string; classroom: string | null; balanceCents: number }[]; totalCents: number };
  totals: { purchaseOrdersCents: number; purchaseOrdersCount: number; payrollCents: number; payrollCount: number; creditsCents: number; creditsCount: number; totalCents: number };
};

function PayablesReport() {
  const { currentSchoolId, schools } = useStore();
  const schoolName = schools.find((s) => s.id === currentSchoolId)?.name ?? "";
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [data, setData] = useState<PayablesStatement | null>(null);
  const [busy, setBusy] = useState(false);

  const load = () => {
    if (!currentSchoolId) return;
    setBusy(true);
    const params = new URLSearchParams({ action: "payables" });
    if (from) params.set("from", from);
    if (to) params.set("to", to);
    apiGet<PayablesStatement>(`/api/school/${currentSchoolId}/ops/finance?${params.toString()}`)
      .then(setData)
      .catch((e) => toast({ title: "Could not load payables", description: e.message, variant: "destructive" }))
      .finally(() => setBusy(false));
  };
  useEffect(() => { load(); }, []); // eslint-disable-line react-hooks/set-state-in-effect

  // PO rows grouped by supplier, each block closing with a bold supplier sub-total
  const poItems = useMemo(() => {
    type PoRow = PayablesStatement["purchaseOrders"]["rows"][number];
    type Item = { kind: "row"; row: PoRow } | { kind: "subtotal"; supplier: string; orders: number; totalCents: number };
    const rows = [...(data?.purchaseOrders.rows ?? [])].sort((a, b) => a.supplier.localeCompare(b.supplier) || a.poNo.localeCompare(b.poNo));
    const out: Item[] = [];
    let prev: string | null = null;
    let acc = { orders: 0, totalCents: 0 };
    for (const r of rows) {
      if (prev !== null && r.supplier !== prev) {
        out.push({ kind: "subtotal", supplier: prev, ...acc });
        acc = { orders: 0, totalCents: 0 };
      }
      out.push({ kind: "row", row: r });
      acc.orders += 1;
      acc.totalCents += r.totalCents;
      prev = r.supplier;
    }
    if (prev !== null) out.push({ kind: "subtotal", supplier: prev, ...acc });
    return out;
  }, [data]);

  return (
    <div className="space-y-4">
      <Card className="no-print">
        <CardContent className="pt-4 flex flex-wrap gap-2 items-end">
          <div className="space-y-1"><Label className="text-xs">Expected from</Label><DateField value={from} onChange={(v) => setFrom(v ?? "")} className="h-9 w-40" /></div>
          <div className="space-y-1"><Label className="text-xs">Expected to</Label><DateField value={to} onChange={(v) => setTo(v ?? "")} className="h-9 w-40" /></div>
          <Button size="sm" className="h-9 gap-1.5" onClick={load} disabled={busy}><FileText className="size-4" /> {busy ? "Loading…" : "Generate statement"}</Button>
          <p className="w-full text-xs text-muted-foreground">Windows the pending purchase orders and approved payroll runs; learner credit wallets are live balances and always show in full.</p>
        </CardContent>
      </Card>

      {!data ? <Card><CardContent><LoadingBlock label="Adding up what the school owes…" /></CardContent></Card> : (
        <>
          <div className="no-print flex flex-wrap items-center justify-between gap-2">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <HandCoins className="size-4" />
              {data.totals.purchaseOrdersCount} pending orders · {data.totals.payrollCount} unpaid payroll runs · {data.totals.creditsCount} credit wallets
            </div>
            <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()}><Printer className="size-4" /> Print</Button>
          </div>

          <div className="print-area space-y-4">
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base">Accounts payables — {schoolName}</CardTitle>
                <p className="text-xs text-muted-foreground">
                  {data.period.from || data.period.to ? `Orders expected ${data.period.from ? fmtDate(data.period.from) : "…"} – ${data.period.to ? fmtDate(data.period.to) : "…"}` : "All pending orders"} · rendered {new Date().toLocaleString("en-GB")}
                </p>
              </CardHeader>
              <CardContent className="p-0">
                <div className="overflow-x-auto nice-scroll max-h-[420px]">
                  <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                      <tr className="text-left border-b text-xs uppercase tracking-wide text-muted-foreground">
                        <th className="px-3 py-2.5 font-medium">PO #</th>
                        <th className="px-3 py-2.5 font-medium">Supplier</th>
                        <th className="px-3 py-2.5 font-medium hidden md:table-cell">Expected</th>
                        <th className="px-3 py-2.5 font-medium text-right hidden sm:table-cell">Lines</th>
                        <th className="px-3 py-2.5 font-medium text-right">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      {poItems.length === 0 && <tr><td colSpan={5}><EmptyState title="No pending purchase orders" hint="Nothing ordered and awaiting delivery in this window." /></td></tr>}
                      {poItems.map((it, i) => it.kind === "row" ? (
                        <tr key={`p${i}`} className="border-b last:border-0 hover:bg-secondary/30">
                          <td className="px-3 py-2.5 font-mono text-xs">{it.row.poNo}</td>
                          <td className="px-3 py-2.5">
                            <div className="font-medium">{it.row.supplier}</div>
                            {it.row.supplierPhone && <div className="text-[11px] text-muted-foreground">{it.row.supplierPhone}</div>}
                          </td>
                          <td className="px-3 py-2.5 hidden md:table-cell text-muted-foreground whitespace-nowrap">{it.row.expectedDate ? fmtDate(it.row.expectedDate) : fmtDate(it.row.createdAt)}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums hidden sm:table-cell">{it.row.lines}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums font-semibold">{fmtCedi(it.row.totalCents)}</td>
                        </tr>
                      ) : (
                        <tr key={`ps${i}`} className="border-b bg-secondary/50 font-semibold">
                          <td className="px-3 py-2" colSpan={4}>{it.supplier} — sub-total · {it.orders} order{it.orders === 1 ? "" : "s"}</td>
                          <td className="px-3 py-2 text-right tabular-nums">{fmtCedi(it.totalCents)}</td>
                        </tr>
                      ))}
                      {poItems.length > 0 && (
                        <tr className="border-t-2 bg-primary/5 font-bold">
                          <td className="px-3 py-3" colSpan={4}>Sub-total — pending purchase orders</td>
                          <td className="px-3 py-3 text-right tabular-nums">{fmtCedi(data.purchaseOrders.totalCents)}</td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base">Approved payroll awaiting payment</CardTitle></CardHeader>
              <CardContent className="p-0">
                <div className="overflow-x-auto nice-scroll max-h-[300px]">
                  <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                      <tr className="text-left border-b text-xs uppercase tracking-wide text-muted-foreground">
                        <th className="px-3 py-2.5 font-medium">Period</th>
                        <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Approved by</th>
                        <th className="px-3 py-2.5 font-medium text-right">Gross</th>
                        <th className="px-3 py-2.5 font-medium text-right hidden md:table-cell">Deductions</th>
                        <th className="px-3 py-2.5 font-medium text-right">Net due</th>
                      </tr>
                    </thead>
                    <tbody>
                      {data.payroll.rows.length === 0 && <tr><td colSpan={5}><EmptyState title="No approved-but-unpaid payroll runs" hint="Runs must be approved in Payroll before they count as payable; drafts and rejected runs never do." /></td></tr>}
                      {data.payroll.rows.map((r) => (
                        <tr key={r.id} className="border-b last:border-0 hover:bg-secondary/30">
                          <td className="px-3 py-2.5 font-medium">{r.period}</td>
                          <td className="px-3 py-2.5 hidden sm:table-cell text-muted-foreground">{r.approvedByName ?? "—"}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums">{fmtCedi(r.totalGrossCents)}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums hidden md:table-cell">{fmtCedi(r.totalDeductionsCents)}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums font-semibold">{fmtCedi(r.totalNetCents)}</td>
                        </tr>
                      ))}
                      {data.payroll.rows.length > 0 && (
                        <tr className="border-t-2 bg-primary/5 font-bold">
                          <td className="px-3 py-3" colSpan={4}>Sub-total — payroll payable</td>
                          <td className="px-3 py-3 text-right tabular-nums">{fmtCedi(data.payroll.totalCents)}</td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base">Learner credit balances — advance payments owed to parents</CardTitle></CardHeader>
              <CardContent className="p-0">
                <div className="overflow-x-auto nice-scroll max-h-[300px]">
                  <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                      <tr className="text-left border-b text-xs uppercase tracking-wide text-muted-foreground">
                        <th className="px-3 py-2.5 font-medium">Learner</th>
                        <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Class</th>
                        <th className="px-3 py-2.5 font-medium text-right">Credit owed</th>
                      </tr>
                    </thead>
                    <tbody>
                      {data.credits.rows.length === 0 && <tr><td colSpan={3}><EmptyState title="No learner credits outstanding" hint="Overpayments and manual credits the school owes back to parents." /></td></tr>}
                      {data.credits.rows.map((r) => (
                        <tr key={r.studentId} className="border-b last:border-0 hover:bg-secondary/30">
                          <td className="px-3 py-2.5 font-medium">{r.name}</td>
                          <td className="px-3 py-2.5 hidden sm:table-cell text-muted-foreground">{r.classroom ?? "—"}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums font-semibold">{fmtCedi(r.balanceCents)}</td>
                        </tr>
                      ))}
                      {data.credits.rows.length > 0 && (
                        <tr className="border-t-2 bg-primary/5 font-bold">
                          <td className="px-3 py-3" colSpan={2}>Sub-total — learner credits</td>
                          <td className="px-3 py-3 text-right tabular-nums">{fmtCedi(data.credits.totalCents)}</td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>

            <div className={cn("flex items-center justify-between rounded-xl border-2 px-4 py-3.5", data.totals.totalCents > 0 ? "border-destructive/40 bg-destructive/5" : "border-border bg-secondary/40")}>
              <div>
                <div className="font-bold text-lg">TOTAL PAYABLE</div>
                <div className="text-xs text-muted-foreground">orders + payroll + learner credits · CI3 accounts payables</div>
              </div>
              <div className={cn("text-2xl font-bold tabular-nums", data.totals.totalCents > 0 ? "text-destructive" : "")}>{fmtCedi(data.totals.totalCents)}</div>
            </div>
          </div>
        </>
      )}
    </div>
  );
}

/* ── INCOME & EXPENDITURE — the formal two-sided account statement
   (CI3 income_expenditure.php): lettered income sections with per-method /
   per-service sub-totals, expenditure by category plus received orders and
   paid payroll, closing on SURPLUS/(DEFICIT). */
type IeStatement = {
  period: { from: string; to: string };
  income: { sections: { key: string; label: string; rows: { label: string; count: number; amountCents: number }[]; subtotalCents: number }[]; totalCents: number };
  expenditure: {
    categories: { category: string; count: number; amountCents: number }[];
    expensesCents: number;
    purchaseOrders: { rows: { poNo: string; supplier: string; receivedAt: string | null; totalCents: number }[]; totalCents: number };
    payroll: { rows: { period: string; paidAt: string | null; totalNetCents: number }[]; totalCents: number };
    totalCents: number;
  };
  surplusCents: number;
};

function IncomeExpenditureReport() {
  const { currentSchoolId, schools } = useStore();
  const schoolName = schools.find((s) => s.id === currentSchoolId)?.name ?? "";
  const [from, setFrom] = useState(() => new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10));
  const [to, setTo] = useState(() => new Date().toISOString().slice(0, 10));
  const [data, setData] = useState<IeStatement | null>(null);
  const [busy, setBusy] = useState(false);

  const load = () => {
    if (!currentSchoolId) return;
    setBusy(true);
    const params = new URLSearchParams({ action: "income-expenditure", from, to });
    apiGet<IeStatement>(`/api/school/${currentSchoolId}/ops/finance?${params.toString()}`)
      .then(setData)
      .catch((e) => toast({ title: "Could not build the account", description: e.message, variant: "destructive" }))
      .finally(() => setBusy(false));
  };
  useEffect(() => { load(); }, []); // eslint-disable-line react-hooks/set-state-in-effect

  const methodLabel = (sectionKey: string, label: string) =>
    sectionKey === "invoices" || sectionKey === "other" ? PAYMENT_LABELS[label] ?? label : label;

  return (
    <div className="space-y-4">
      <Card className="no-print">
        <CardContent className="pt-4 flex flex-wrap gap-2 items-end">
          <div className="space-y-1"><Label className="text-xs">From</Label><DateField value={from} onChange={(v) => setFrom(v ?? "")} className="h-9 w-40" /></div>
          <div className="space-y-1"><Label className="text-xs">To</Label><DateField value={to} onChange={(v) => setTo(v ?? "")} className="h-9 w-40" /></div>
          <Button size="sm" className="h-9 gap-1.5" onClick={load} disabled={busy}><Scale className="size-4" /> {busy ? "Loading…" : "Generate account"}</Button>
        </CardContent>
      </Card>

      {!data ? <Card><CardContent><LoadingBlock label="Balancing the books…" /></CardContent></Card> : (
        <>
          <div className="no-print flex flex-wrap items-center justify-between gap-2">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <Scale className="size-4" />
              {fmtDate(data.period.from)} – {fmtDate(data.period.to)} · income <b className="text-foreground tabular-nums">{fmtCedi(data.income.totalCents)}</b> vs expenditure <b className="text-foreground tabular-nums">{fmtCedi(data.expenditure.totalCents)}</b>
            </div>
            <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()}><Printer className="size-4" /> Print</Button>
          </div>

          <div className="print-area space-y-4">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {/* ── INCOME side ── */}
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base">INCOME — {schoolName}</CardTitle>
                <p className="text-xs text-muted-foreground">{fmtDate(data.period.from)} to {fmtDate(data.period.to)}</p>
              </CardHeader>
              <CardContent className="p-0">
                <table className="w-full text-sm">
                  <tbody>
                    {data.income.sections.map((s, si) => {
                      const letter = String.fromCharCode(65 + si);
                      return (
                        <Fragment key={s.key}>
                          <tr className="bg-secondary/60"><td className="px-3 py-2 font-semibold text-[13px]" colSpan={3}>{letter}. {s.label}</td></tr>
                          {s.rows.map((r) => (
                            <tr key={`${s.key}-${r.label}`} className="border-b last:border-0">
                              <td className="px-3 py-2 pl-8 text-muted-foreground">{methodLabel(s.key, r.label)}</td>
                              <td className="px-3 py-2 text-right text-xs text-muted-foreground w-14">{r.count ? `× ${r.count}` : ""}</td>
                              <td className="px-3 py-2 text-right tabular-nums w-36">{fmtCedi(r.amountCents)}</td>
                            </tr>
                          ))}
                          {s.rows.length === 0 && <tr><td className="px-3 py-2 pl-8 text-xs text-muted-foreground" colSpan={3}>Nothing collected in this window</td></tr>}
                          <tr className="bg-secondary/30 font-semibold">
                            <td className="px-3 py-2 text-right" colSpan={2}>Sub-total ({letter})</td>
                            <td className="px-3 py-2 text-right tabular-nums">{fmtCedi(s.subtotalCents)}</td>
                          </tr>
                        </Fragment>
                      );
                    })}
                    <tr className="border-t-2 bg-primary/10 font-bold">
                      <td className="px-3 py-3 text-right" colSpan={2}>TOTAL INCOME</td>
                      <td className="px-3 py-3 text-right tabular-nums text-primary">{fmtCedi(data.income.totalCents)}</td>
                    </tr>
                  </tbody>
                </table>
              </CardContent>
            </Card>

            {/* ── EXPENDITURE side ── */}
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base">EXPENDITURE</CardTitle>
                <p className="text-xs text-muted-foreground">Bursar&apos;s book · inventory purchases · payroll paid</p>
              </CardHeader>
              <CardContent className="p-0">
                <table className="w-full text-sm">
                  <tbody>
                    <tr className="bg-secondary/60"><td className="px-3 py-2 font-semibold text-[13px]" colSpan={3}>A. Operating expenses (bursar&apos;s book)</td></tr>
                    {data.expenditure.categories.map((c) => (
                      <tr key={c.category} className="border-b last:border-0">
                        <td className="px-3 py-2 pl-8 text-muted-foreground">{catLabel(c.category)}</td>
                        <td className="px-3 py-2 text-right text-xs text-muted-foreground w-14">× {c.count}</td>
                        <td className="px-3 py-2 text-right tabular-nums w-36">{fmtCedi(c.amountCents)}</td>
                      </tr>
                    ))}
                    {data.expenditure.categories.length === 0 && <tr><td className="px-3 py-2 pl-8 text-xs text-muted-foreground" colSpan={3}>No expenses recorded in this window</td></tr>}
                    <tr className="bg-secondary/30 font-semibold">
                      <td className="px-3 py-2 text-right" colSpan={2}>Sub-total (A)</td>
                      <td className="px-3 py-2 text-right tabular-nums">{fmtCedi(data.expenditure.expensesCents)}</td>
                    </tr>

                    <tr className="bg-secondary/60"><td className="px-3 py-2 font-semibold text-[13px]" colSpan={3}>B. Inventory purchases — received orders</td></tr>
                    {data.expenditure.purchaseOrders.rows.map((po) => (
                      <tr key={po.poNo} className="border-b last:border-0">
                        <td className="px-3 py-2 pl-8 text-muted-foreground">{po.poNo} · {po.supplier}{po.receivedAt ? ` · ${fmtDate(po.receivedAt)}` : ""}</td>
                        <td />
                        <td className="px-3 py-2 text-right tabular-nums">{fmtCedi(po.totalCents)}</td>
                      </tr>
                    ))}
                    {data.expenditure.purchaseOrders.rows.length === 0 && <tr><td className="px-3 py-2 pl-8 text-xs text-muted-foreground" colSpan={3}>No orders received in this window</td></tr>}
                    <tr className="bg-secondary/30 font-semibold">
                      <td className="px-3 py-2 text-right" colSpan={2}>Sub-total (B)</td>
                      <td className="px-3 py-2 text-right tabular-nums">{fmtCedi(data.expenditure.purchaseOrders.totalCents)}</td>
                    </tr>

                    <tr className="bg-secondary/60"><td className="px-3 py-2 font-semibold text-[13px]" colSpan={3}>C. Salaries paid via payroll (net)</td></tr>
                    {data.expenditure.payroll.rows.map((r) => (
                      <tr key={r.period} className="border-b last:border-0">
                        <td className="px-3 py-2 pl-8 text-muted-foreground">{r.period}{r.paidAt ? ` · paid ${fmtDate(r.paidAt)}` : ""}</td>
                        <td />
                        <td className="px-3 py-2 text-right tabular-nums">{fmtCedi(r.totalNetCents)}</td>
                      </tr>
                    ))}
                    {data.expenditure.payroll.rows.length === 0 && <tr><td className="px-3 py-2 pl-8 text-xs text-muted-foreground" colSpan={3}>No payroll paid in this window</td></tr>}
                    <tr className="bg-secondary/30 font-semibold">
                      <td className="px-3 py-2 text-right" colSpan={2}>Sub-total (C)</td>
                      <td className="px-3 py-2 text-right tabular-nums">{fmtCedi(data.expenditure.payroll.totalCents)}</td>
                    </tr>

                    <tr className="border-t-2 bg-destructive/10 font-bold">
                      <td className="px-3 py-3 text-right" colSpan={2}>TOTAL EXPENDITURE</td>
                      <td className="px-3 py-3 text-right tabular-nums text-destructive">{fmtCedi(data.expenditure.totalCents)}</td>
                    </tr>
                  </tbody>
                </table>
              </CardContent>
            </Card>
            </div>

            {/* ── SURPLUS / (DEFICIT) ── */}
            <Card className={cn("border-2", data.surplusCents > 0 ? "border-emerald-500/40" : data.surplusCents < 0 ? "border-destructive/40" : "border-border")}>
              <CardContent className="py-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <div className="font-bold text-lg">
                    {data.surplusCents > 0 ? "SURPLUS" : data.surplusCents < 0 ? "(DEFICIT)" : "BREAK-EVEN"}
                  </div>
                  <div className={cn("text-2xl font-bold tabular-nums", data.surplusCents > 0 ? "text-emerald-600" : data.surplusCents < 0 ? "text-destructive" : "")}>
                    {data.surplusCents < 0 ? `(${fmtCedi(Math.abs(data.surplusCents))})` : fmtCedi(data.surplusCents)}
                  </div>
                </div>
                <p className="text-xs text-muted-foreground mt-1">income − expenditure · CI3 income &amp; expenditure account</p>
              </CardContent>
            </Card>
          </div>
        </>
      )}
    </div>
  );
}

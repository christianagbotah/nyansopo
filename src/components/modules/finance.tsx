"use client";
/* Nyansapo — Finance & Analytics (iedupro parity).
   Income dashboard · Student ledger · AR aging · Terminal bills · Expenditure. */
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { StatCard, ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, PAYMENT_LABELS } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { LogoMark, KenteStrip } from "@/components/brand";
import { amountInWords } from "@/components/receipts";
import {
  TrendingUp, Wallet, AlertTriangle, Receipt, PieChart, BookOpenText, Timer, FileText, Plus,
  ArrowUpRight, ArrowDownLeft, Printer, Banknote, Trash2, Search, Users, ReceiptText,
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
  paid: number;
  balance: number;
  dailyArrears: number;
  transport: { route: string; fare: number } | null;
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
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<FinanceData | null>(null);
  const [yearId, setYearId] = useState<string | null>(null);
  const [tab, setTab] = useState("income");

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
          <TabsTrigger value="ledger">Student ledger</TabsTrigger>
          <TabsTrigger value="aging">Receivables ageing</TabsTrigger>
          <TabsTrigger value="bills">Terminal bills</TabsTrigger>
          <TabsTrigger value="expenditure">Expenditure</TabsTrigger>
        </TabsList>

        {/* ── INCOME ── */}
        <TabsContent value="income">
          <div className="grid lg:grid-cols-3 gap-4">
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

        {/* ── AGING ── */}
        <TabsContent value="aging">
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4">
            {[
              { label: "Current (0–30 days)", v: data.aging.current, tone: "default" as const, icon: <Timer className="size-4" /> },
              { label: "31–60 days", v: data.aging.b31, tone: "primary" as const, icon: <AlertTriangle className="size-4" /> },
              { label: "61–90 days", v: data.aging.b61, tone: "gold" as const, icon: <AlertTriangle className="size-4" /> },
              { label: "Over 90 days", v: data.aging.b90, tone: "danger" as const, icon: <AlertTriangle className="size-4" /> },
            ].map((b) => (
              <StatCard key={b.label} icon={b.icon} label={b.label} value={fmtCedi(b.v.amount, { compact: true })} tone={b.tone} sub={`${b.v.students} learners`} />
            ))}
          </div>
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base">Collection priorities</CardTitle></CardHeader>
            <CardContent className="text-sm text-muted-foreground space-y-2">
              <p>
                <b className="text-foreground">{data.aging.b90.students}</b> learners are beyond 90 days — these belong on the association defaulter watch-list (Fees &amp; Billing → flag) and qualify for guardian SMS campaigns from the SMS Centre.
              </p>
              <p>
                Use <b className="text-foreground">Terminal bills</b> to print end-of-term arrears slips with next-term bills attached, exactly like the paper terminal bills parents expect.
              </p>
              <div className="rounded-lg border bg-secondary/40 p-3 flex items-center justify-between">
                <span>Total receivables across all buckets</span>
                <b className="tabular-nums text-foreground">{fmtCedi(data.aging.current.amount + data.aging.b31.amount + data.aging.b61.amount + data.aging.b90.amount)}</b>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── TERMINAL BILLS ── */}
        <TabsContent value="bills">
          <TerminalBillsTab data={data} />
        </TabsContent>

        {/* ── EXPENDITURE ── */}
        <TabsContent value="expenditure">
          <ExpenditureTab data={data} onRefresh={() => reload(yearId ?? "")} />
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
    <div className="grid lg:grid-cols-3 gap-4">
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
          <div className="no-print flex items-center justify-between">
            <div className="text-sm text-muted-foreground">{bills.length} bill{bills.length === 1 ? "" : "s"} ready</div>
            <Button size="sm" onClick={() => window.print()} className="gap-1.5"><Printer className="size-4" /> Print all</Button>
          </div>
          {bills.map((b, i) => <TerminalBill key={i} bill={b} />)}
        </div>
      )}
    </div>
  );
}

function TerminalBill({ bill }: { bill: TerminalBill }) {
  const net = bill.items.reduce((a, x) => a + x.amount, 0);
  const balance = bill.balance;
  const totalDue = balance + bill.dailyArrears;
  return (
    <div className="print-sheet a5-sheet mx-auto bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden">
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
                <th className="py-1.5 font-medium text-right">Amount</th>
              </tr>
            </thead>
            <tbody>
              {bill.items.map((it, i) => (
                <tr key={i} className="border-b border-dashed">
                  <td className="py-1.5">{it.name}</td>
                  <td className="py-1.5 text-right tabular-nums">{fmtCedi(it.amount)}</td>
                </tr>
              ))}
              {bill.discount > 0 && <tr className="border-b border-dashed text-emerald-700"><td className="py-1.5">Discount</td><td className="py-1.5 text-right tabular-nums">−{fmtCedi(bill.discount)}</td></tr>}
              <tr className="border-b"><td className="py-1.5 font-semibold">Paid to date</td><td className="py-1.5 text-right font-semibold tabular-nums text-emerald-700">{fmtCedi(bill.paid)}</td></tr>
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
          {bill.transport && (
            <div className="flex justify-between rounded-lg bg-secondary/60 px-3 py-2">
              <span className="font-medium">Bus route {bill.transport.route} — next term fare</span>
              <span className="font-semibold tabular-nums">{fmtCedi(bill.transport.fare)}</span>
            </div>
          )}
          <div className="flex justify-between rounded-lg bg-primary/10 px-3 py-2.5">
            <span className="font-bold">Total to settle / bring for resumption</span>
            <span className="font-bold tabular-nums text-primary text-lg">{fmtCedi(totalDue + (bill.transport?.fare ?? 0))}</span>
          </div>
          {totalDue > 0 && <div className="text-[10px] italic text-muted-foreground text-right">{amountInWords(totalDue + (bill.transport?.fare ?? 0))}</div>}
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
  const { currentSchoolId } = useStore();
  const [dialog, setDialog] = useState(false);
  const [category, setCategory] = useState("UTILITIES");
  const [description, setDescription] = useState("");
  const [amount, setAmount] = useState("");
  const [method, setMethod] = useState("CASH");
  const [busy, setBusy] = useState(false);
  const max = Math.max(1, ...data.expenditure.byCategory.map((c) => c.amount));

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
    <div className="grid lg:grid-cols-3 gap-4">
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
        <CardHeader className="pb-3"><CardTitle className="text-base">Recent expenses</CardTitle></CardHeader>
        <CardContent className="p-0">
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
                {data.expenditure.recent.map((e) => (
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
                {data.expenditure.recent.length === 0 && <tr><td colSpan={6}><EmptyState title="Nothing recorded yet" /></td></tr>}
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
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5 col-span-2">
              <Label>Category</Label>
              <Select value={category} onValueChange={setCategory}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{EXPENSE_CATEGORIES.map((c) => <SelectItem key={c.key} value={c.key}>{c.label}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5 col-span-2"><Label>Description *</Label><Input value={description} onChange={(e) => setDescription(e.target.value)} placeholder="e.g. ECG postpaid — September" /></div>
            <div className="space-y-1.5"><Label>Amount (GH₵) *</Label><Input type="number" step="0.01" value={amount} onChange={(e) => setAmount(e.target.value)} /></div>
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

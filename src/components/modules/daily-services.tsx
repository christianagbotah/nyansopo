"use client";
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost, apiPostQueueable } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { StatCard, ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, PAYMENT_LABELS } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { UtensilsCrossed, Wallet, CalendarPlus, Plus, TrendingUp, Search, Droplets, BookOpenCheck, Coffee, HandCoins, CheckCircle2, Printer, Percent } from "lucide-react";
import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";

type Service = {
  id: string; name: string; category: string; price: number; chargeMode: string; weekdays: string; active: boolean; enrolled: number;
  month: { billed: number; collected: number; owed: number; waived: number; count: number };
};
type LedgerRow = { studentId: string; student: string; classroom: string | null; guardianPhone: string; owed: number; items: Record<string, number>; oldest: string };
type PosStudent = { id: string; name: string; classroom: string; guardianPhone: string; balance: number; services: { id: string; name: string; price: number }[]; today: { charged: number; owed: number } | null };
type DailyData = {
  date: string;
  services: Service[];
  month: { billed: number; collected: number; owed: number; waived: number };
  today: { charged: number; collected: number; owed: number; count: number; generated: boolean };
  trend: { date: string; billed: number; collected: number }[];
  ledger: LedgerRow[];
  posStudents: PosStudent[];
  posPayments: { id: string; receiptNo: string; amount: number; method: string; paidAt: string; student: string }[];
};

const CAT_ICON: Record<string, any> = { MEAL: Coffee, DRINK: Droplets, CLASS: BookOpenCheck, CARE: HandCoins, OTHER: UtensilsCrossed };
const DAY_LABELS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
const weekdayLabel = (wd: string) => wd.split(",").map((n) => DAY_LABELS[Number(n)]).join(" · ");

export function DailyServices() {
  const { currentSchoolId, online } = useStore();
  const [data, setData] = useState<DailyData | null>(null);
  const [tab, setTab] = useState("pos");
  const [date, setDate] = useState(new Date().toISOString().slice(0, 10));
  const [collectFor, setCollectFor] = useState<PosStudent | null>(null);
  const [svcDialog, setSvcDialog] = useState<Service | "new" | null>(null);
  const [enrollFor, setEnrollFor] = useState<Service | null>(null);
  const [receipt, setReceipt] = useState<any | null>(null);
  const [busy, setBusy] = useState(false);
  const [search, setSearch] = useState("");

  const load = (d?: string) => {
    if (!currentSchoolId) return;
    apiGet<DailyData>(`/api/school/${currentSchoolId}/ops/daily?date=${d ?? date}`)
      .then(setData)
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(() => { load(date); }, [currentSchoolId, date]);

  const filteredStudents = useMemo(() => {
    const q = search.toLowerCase();
    return (data?.posStudents ?? []).filter((s) => !q || s.name.toLowerCase().includes(q) || s.classroom.toLowerCase().includes(q));
  }, [data, search]);
  const withBalance = useMemo(() => filteredStudents.filter((s) => s.balance > 0), [filteredStudents]);

  async function generateDay() {
    if (!currentSchoolId) return;
    setBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/ops/daily`, { action: "generate-day", date });
      toast({ title: "Day generated", description: `${res.created} charges created${res.skipped ? ` · ${res.skipped} already existed` : ""}.` });
      load(date);
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  if (!data) return <LoadingBlock label="Loading daily services…" />;

  return (
    <div>
      <ModuleHeader
        title="Daily Services & Tuck"
        description="Canteen, breakfast, sachet water, remedial classes — micro-billing charged by the school day. Collect at the POS counter, track every pesewa."
        actions={
          <div className="flex items-center gap-2 flex-wrap">
            <Input type="date" value={date} onChange={(e) => setDate(e.target.value)} className="w-40" />
            <Button size="sm" className="gap-1.5" onClick={generateDay} disabled={busy}>
              <CalendarPlus className="size-4" /> {data.today.generated ? "Re-generate day" : "Generate day charges"}
            </Button>
          </div>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<Wallet className="size-4" />} label="Today collected" value={fmtCedi(data.today.collected, { compact: true })} tone="primary" sub={`${data.today.count} charges · ${fmtCedi(data.today.owed, { compact: true })} open`} />
        <StatCard icon={<TrendingUp className="size-4" />} label="This month" value={fmtCedi(data.month.collected, { compact: true })} sub={`${fmtCedi(data.month.billed, { compact: true })} billed`} />
        <StatCard icon={<Percent className="size-4" />} label="Daily arrears" value={fmtCedi(data.month.owed, { compact: true })} tone={data.month.owed > 0 ? "danger" : "default"} sub={`waived ${fmtCedi(data.month.waived, { compact: true })}`} />
        <StatCard icon={<UtensilsCrossed className="size-4" />} label="Services live" value={data.services.filter((s) => s.active).length} tone="gold" sub={`${data.services.reduce((a, s) => a + s.enrolled, 0)} enrolments`} />
      </div>

      <Tabs value={tab} onValueChange={setTab} className="mt-5">
        <TabsList>
          <TabsTrigger value="pos">Counter / POS</TabsTrigger>
          <TabsTrigger value="ledger">Ledger & arrears ({data.ledger.length})</TabsTrigger>
          <TabsTrigger value="services">Services & enrolment</TabsTrigger>
          <TabsTrigger value="trend">14-day trend</TabsTrigger>
        </TabsList>

        {/* ── POS ── */}
        <TabsContent value="pos">
          <div className="grid lg:grid-cols-3 gap-4">
            <Card className="lg:col-span-2">
              <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
                <CardTitle className="text-base">Collect a payment</CardTitle>
                <div className="relative">
                  <Search className="size-4 absolute left-2.5 top-2.5 text-muted-foreground" />
                  <Input placeholder="Search learner or class…" value={search} onChange={(e) => setSearch(e.target.value)} className="pl-8 w-56 h-8" />
                </div>
              </CardHeader>
              <CardContent className="max-h-[520px] overflow-y-auto nice-scroll space-y-1.5">
                {withBalance.length === 0 && <EmptyState icon={<CheckCircle2 className="size-5" />} title="No outstanding daily balances" hint="Everything collected — or generate today's charges first." />}
                {withBalance.slice(0, 60).map((s) => (
                  <div key={s.id} className="rounded-lg border p-3 flex flex-wrap items-center justify-between gap-2 hover:bg-secondary/30 transition-colors">
                    <div className="min-w-0">
                      <div className="font-medium text-sm truncate">{s.name} <span className="text-muted-foreground font-normal">· {s.classroom}</span></div>
                      <div className="text-[11px] text-muted-foreground">
                        {s.services.map((x) => x.name.replace(" (optional)", "").replace(" (canteen)", "").replace(" (remedial)", "")).join(" · ") || "—"}
                      </div>
                    </div>
                    <div className="flex items-center gap-2 shrink-0">
                      <span className="font-semibold text-destructive tabular-nums text-sm">{fmtCedi(s.balance)}</span>
                      <Button size="sm" className="h-8 gap-1" onClick={() => setCollectFor(s)}><Wallet className="size-3.5" /> Collect</Button>
                    </div>
                  </div>
                ))}
              </CardContent>
            </Card>
            <div className="space-y-4">
              <Card>
                <CardHeader className="pb-3"><CardTitle className="text-base">Recent counter receipts</CardTitle></CardHeader>
                <CardContent className="space-y-2 max-h-[240px] overflow-y-auto nice-scroll">
                  {data.posPayments.length === 0 && <EmptyState title="No receipts yet" />}
                  {data.posPayments.map((p) => (
                    <div key={p.id} className="flex items-center justify-between gap-2 text-sm border-b last:border-0 pb-2 last:pb-0">
                      <div className="min-w-0">
                        <div className="font-medium truncate">{p.student}</div>
                        <div className="text-[11px] text-muted-foreground font-mono">{p.receiptNo} · {PAYMENT_LABELS[p.method] ?? p.method}</div>
                      </div>
                      <div className="text-right shrink-0">
                        <div className="tabular-nums font-semibold">{fmtCedi(p.amount)}</div>
                        <div className="text-[11px] text-muted-foreground">{fmtDate(p.paidAt)}</div>
                      </div>
                    </div>
                  ))}
                </CardContent>
              </Card>
              <Card className="bg-primary/5 border-primary/20">
                <CardContent className="pt-4 text-sm space-y-1.5">
                  <div className="font-semibold flex items-center gap-1.5"><UtensilsCrossed className="size-4 text-primary" /> How it works</div>
                  <p className="text-muted-foreground text-xs leading-relaxed">
                    Generate the day&apos;s charges each morning (only learners enrolled in a service running that weekday are billed).
                    Collect at the counter in cash or MoMo — the oldest charges settle first, and the guardian gets an automatic SMS receipt.
                  </p>
                </CardContent>
              </Card>
            </div>
          </div>
        </TabsContent>

        {/* ── LEDGER ── */}
        <TabsContent value="ledger">
          <Card>
            <CardContent className="p-0">
              <div className="overflow-x-auto nice-scroll max-h-[560px]">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                    <tr className="text-left border-b">
                      <th className="px-3 py-2.5 font-medium">Learner</th>
                      <th className="px-3 py-2.5 font-medium hidden md:table-cell">Class</th>
                      <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Breakdown</th>
                      <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Oldest unpaid</th>
                      <th className="px-3 py-2.5 font-medium text-right">Owed</th>
                      <th className="px-3 py-2.5 text-right"></th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.ledger.length === 0 && <tr><td colSpan={6}><EmptyState icon={<CheckCircle2 className="size-5" />} title="Clean ledger — no daily arrears" /></td></tr>}
                    {data.ledger.map((r) => (
                      <tr key={r.studentId} className="border-b last:border-0 hover:bg-secondary/30">
                        <td className="px-3 py-2.5">
                          <div className="font-medium">{r.student}</div>
                          <div className="text-[11px] text-muted-foreground lg:hidden">{r.classroom}</div>
                        </td>
                        <td className="px-3 py-2.5 hidden md:table-cell text-muted-foreground">{r.classroom}</td>
                        <td className="px-3 py-2.5 hidden lg:table-cell">
                          <div className="flex flex-wrap gap-1">
                            {Object.entries(r.items).map(([k, v]) => (
                              <Badge key={k} variant="outline" className="text-[10px]">{k.replace(" (optional)", "")} {fmtCedi(v)}</Badge>
                            ))}
                          </div>
                        </td>
                        <td className="px-3 py-2.5 hidden sm:table-cell text-muted-foreground text-xs">{fmtDate(r.oldest)}</td>
                        <td className="px-3 py-2.5 text-right tabular-nums font-semibold text-destructive">{fmtCedi(r.owed)}</td>
                        <td className="px-3 py-2.5 text-right">
                          <Button size="sm" variant="outline" className="h-7 gap-1"
                            onClick={() => setCollectFor(data.posStudents.find((s) => s.id === r.studentId) ?? null)}>
                            <Wallet className="size-3.5" /> Collect
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

        {/* ── SERVICES ── */}
        <TabsContent value="services">
          <div className="flex justify-end mb-3">
            <Button size="sm" className="gap-1.5" onClick={() => setSvcDialog("new")}><Plus className="size-4" /> New service</Button>
          </div>
          <div className="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            {data.services.map((s) => {
              const Icon = CAT_ICON[s.category] ?? UtensilsCrossed;
              const rate = s.month.billed ? Math.round((s.month.collected / s.month.billed) * 100) : 0;
              return (
                <Card key={s.id} className={s.active ? "" : "opacity-60"}>
                  <CardHeader className="pb-2 flex-row items-center justify-between space-y-0">
                    <CardTitle className="text-sm flex items-center gap-2"><Icon className="size-4 text-primary" /> {s.name}</CardTitle>
                    <div className="flex items-center gap-1.5">
                      {!s.active && <Badge variant="outline">paused</Badge>}
                      <Badge variant="secondary">{fmtCedi(s.price)}</Badge>
                    </div>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    <div className="text-xs text-muted-foreground">
                      {s.chargeMode === "WEEKLY" ? `Weekly — ${weekdayLabel(s.weekdays)}` : `Daily — ${weekdayLabel(s.weekdays)}`} · {s.enrolled} learners enrolled
                    </div>
                    <div className="space-y-1">
                      <div className="flex justify-between text-xs"><span className="text-muted-foreground">Month collection</span><span className="tabular-nums">{rate}%</span></div>
                      <Progress value={rate} className="h-1.5" />
                      <div className="flex justify-between text-[11px] text-muted-foreground">
                        <span>{fmtCedi(s.month.collected)} of {fmtCedi(s.month.billed)}</span>
                        {s.month.owed > 0 && <span className="text-destructive">{fmtCedi(s.month.owed)} owed</span>}
                      </div>
                    </div>
                    <div className="flex gap-2 pt-1">
                      <Button size="sm" variant="outline" className="h-7 flex-1" onClick={() => setEnrollFor(s)}>Enrol learners</Button>
                      <Button size="sm" variant="ghost" className="h-7" onClick={() => setSvcDialog(s)}>Edit</Button>
                      <Button size="sm" variant="ghost" className="h-7"
                        onClick={async () => {
                          await apiPost(`/api/school/${currentSchoolId}/ops/daily`, { action: "toggle-service", id: s.id });
                          toast({ title: s.active ? "Service paused" : "Service resumed" });
                          load(date);
                        }}>{s.active ? "Pause" : "Resume"}</Button>
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </TabsContent>

        {/* ── TREND ── */}
        <TabsContent value="trend">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base">Billed vs collected — last 14 school days</CardTitle></CardHeader>
            <CardContent>
              <div className="h-72">
                <ResponsiveContainer width="100%" height="100%">
                  <AreaChart data={data.trend.map((t) => ({ ...t, label: new Date(t.date).toLocaleDateString("en-GB", { day: "numeric", month: "short" }) }))}>
                    <defs>
                      <linearGradient id="dailyBilled" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="#a97c0b" stopOpacity={0.25} />
                        <stop offset="95%" stopColor="#a97c0b" stopOpacity={0} />
                      </linearGradient>
                      <linearGradient id="dailyCollected" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="#0e5f3f" stopOpacity={0.35} />
                        <stop offset="95%" stopColor="#0e5f3f" stopOpacity={0} />
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                    <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                    <YAxis tick={{ fontSize: 11 }} tickFormatter={(v) => `₵${(v / 100000).toFixed(0)}k`} width={44} />
                    <Tooltip formatter={(v: any) => fmtCedi(Number(v))} contentStyle={{ fontSize: 12, borderRadius: 8 }} />
                    <Area dataKey="billed" name="Billed" stroke="#a97c0b" fill="url(#dailyBilled)" strokeWidth={2} />
                    <Area dataKey="collected" name="Collected" stroke="#0e5f3f" fill="url(#dailyCollected)" strokeWidth={2} />
                  </AreaChart>
                </ResponsiveContainer>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* collect dialog */}
      <CollectDialog
        student={collectFor}
        date={date}
        online={online}
        onOpenChange={(o) => !o && setCollectFor(null)}
        onDone={(rc) => { setReceipt(rc); setCollectFor(null); load(date); }}
      />
      {receipt && <ReceiptDialog receipt={receipt} onOpenChange={(o) => !o && setReceipt(null)} />}

      {/* service dialog */}
      <ServiceDialog
        service={svcDialog}
        onOpenChange={(o) => !o && setSvcDialog(null)}
        onDone={() => { setSvcDialog(null); load(date); }}
      />

      {/* enroll dialog */}
      <EnrollDialog
        service={enrollFor}
        students={data.posStudents}
        onOpenChange={(o) => !o && setEnrollFor(null)}
        onDone={() => { setEnrollFor(null); load(date); }}
      />
    </div>
  );
}

function CollectDialog({ student, date, online, onOpenChange, onDone }: { student: PosStudent | null; date: string; online: boolean; onOpenChange: (o: boolean) => void; onDone: (receipt: any) => void }) {
  const { currentSchoolId } = useStore();
  const [amount, setAmount] = useState("");
  const [method, setMethod] = useState("MOMO_MTN");
  const [reference, setReference] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => { if (student) { setAmount((student.balance / 100).toFixed(2)); setReference(""); } }, [student]);

  async function submit() {
    if (!student || !currentSchoolId) return;
    const cents = Math.round(parseFloat(amount || "0") * 100);
    if (!cents || cents <= 0) { toast({ title: "Enter a valid amount" }); return; }
    setBusy(true);
    try {
      const payload = { action: "pos-collect", studentId: student.id, amountCents: cents, method, reference: reference || undefined, date };
      const res = await apiPostQueueable<any>(
        `/api/school/${currentSchoolId}/ops/daily`, "payment", payload,
        `Daily services ${fmtCedi(cents)} — ${student.name}`,
        (p, clientId) => ({ ...p, category: "DAILY", schoolId: currentSchoolId, studentId: student.id, amountCents: cents, method, paidAt: new Date().toISOString(), clientId })
      );
      if (res.queued) {
        toast({ title: "Queued offline", description: "Receipt will be issued when you reconnect." });
        onDone(null);
      } else {
        onDone({ ...res.data?.payment, student: student.name });
      }
    } catch (e: any) { toast({ title: "Collection failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!student} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Wallet className="size-4" /> Collect daily payment — {student?.name}</DialogTitle>
          <DialogDescription>
            {student?.classroom} · outstanding <b className="text-destructive">{student ? fmtCedi(student.balance) : ""}</b>
            {!online && " · offline — will queue & sync"}
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
            <Label>MoMo / reference</Label>
            <Input placeholder="e.g. MP2401.5533.77" value={reference} onChange={(e) => setReference(e.target.value)} />
          </div>
        </div>
        <p className="text-xs text-muted-foreground">Charges settle oldest-first. A receipt SMS goes to {student?.guardianPhone} automatically.</p>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy} className="gap-1.5">{busy ? "Recording…" : <><ReceiptIcon /> Record payment</>}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
function ReceiptIcon() { return <Printer className="size-4" />; }

function ReceiptDialog({ receipt, onOpenChange }: { receipt: any; onOpenChange: (o: boolean) => void }) {
  return (
    <Dialog open={!!receipt} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-sm">
        <DialogHeader className="text-center">
          <DialogTitle className="flex items-center justify-center gap-2"><CheckCircle2 className="size-5 text-emerald-600" /> Payment received</DialogTitle>
        </DialogHeader>
        <div className="text-center space-y-1 py-2">
          <div className="text-3xl font-bold text-primary">{fmtCedi(receipt?.amountCents ?? 0)}</div>
          <div className="text-sm">{receipt?.student}</div>
          <div className="font-mono text-xs text-muted-foreground">{receipt?.receiptNo}</div>
          <div className="text-xs text-muted-foreground">daily services · SMS receipt sent to guardian</div>
        </div>
        <DialogFooter className="sm:justify-center gap-2">
          <Button size="sm" onClick={() => onOpenChange(false)}>Done</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function ServiceDialog({ service, onOpenChange, onDone }: { service: Service | "new" | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [name, setName] = useState("");
  const [category, setCategory] = useState("MEAL");
  const [price, setPrice] = useState("");
  const [chargeMode, setChargeMode] = useState("DAILY");
  const [weekdays, setWeekdays] = useState<string[]>(["1", "2", "3", "4", "5"]);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (service && service !== "new") {
      setName(service.name); setCategory(service.category); setPrice((service.price / 100).toFixed(2));
      setChargeMode(service.chargeMode); setWeekdays(service.weekdays.split(","));
    } else if (service === "new") {
      setName(""); setCategory("MEAL"); setPrice(""); setChargeMode("DAILY"); setWeekdays(["1", "2", "3", "4", "5"]);
    }
  }, [service]);

  async function submit() {
    if (!currentSchoolId || !name || !price) { toast({ title: "Name and price are required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/daily`, {
        action: "save-service",
        id: service && service !== "new" ? service.id : undefined,
        name, category, priceCents: Math.round(parseFloat(price) * 100), chargeMode,
        weekdays: (chargeMode === "WEEKLY" ? weekdays.slice(0, 1) : weekdays).join(","),
      });
      toast({ title: service === "new" ? "Service created" : "Service updated" });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  const toggleDay = (d: string) => setWeekdays((w) => (w.includes(d) ? w.filter((x) => x !== d) : [...w, d]));

  return (
    <Dialog open={!!service} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{service === "new" ? "New daily service" : "Edit service"}</DialogTitle>
          <DialogDescription>e.g. Lunch GH₵8 every school day, or Sachet Water GH₵1 on Wednesdays only.</DialogDescription>
        </DialogHeader>
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1.5 col-span-2">
            <Label>Name *</Label>
            <Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Lunch (canteen)" />
          </div>
          <div className="space-y-1.5">
            <Label>Category</Label>
            <Select value={category} onValueChange={setCategory}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="MEAL">Meal</SelectItem><SelectItem value="DRINK">Drink</SelectItem>
                <SelectItem value="CLASS">Class / remedial</SelectItem><SelectItem value="CARE">Care</SelectItem>
                <SelectItem value="OTHER">Other</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Price (GH₵) *</Label>
            <Input type="number" step="0.5" value={price} onChange={(e) => setPrice(e.target.value)} placeholder="8.00" />
          </div>
          <div className="space-y-1.5 col-span-2">
            <Label>Charging pattern</Label>
            <Select value={chargeMode} onValueChange={setChargeMode}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="DAILY">Daily — every selected school day</SelectItem>
                <SelectItem value="WEEKLY">Weekly — once a week</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5 col-span-2">
            <Label>Days it runs {chargeMode === "WEEKLY" && <span className="text-muted-foreground font-normal">(pick one day)</span>}</Label>
            <div className="flex gap-1.5 flex-wrap">
              {DAY_LABELS.slice(1, 6).map((d, i) => (
                <button key={d} type="button"
                  onClick={() => toggleDay(String(i + 1))}
                  className={`size-9 rounded-lg border text-xs font-medium transition-colors ${weekdays.includes(String(i + 1)) ? "bg-primary text-primary-foreground border-primary" : "hover:bg-secondary"}`}>
                  {d}
                </button>
              ))}
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : "Save service"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function EnrollDialog({ service, students, onOpenChange, onDone }: { service: Service | null; students: PosStudent[]; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [picked, setPicked] = useState<string[]>([]);
  const [subsidy, setSubsidy] = useState("");
  const [search, setSearch] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => { setPicked([]); setSearch(""); setSubsidy(""); }, [service]);

  const list = useMemo(() => {
    const q = search.toLowerCase();
    return students.filter((s) => !q || s.name.toLowerCase().includes(q) || s.classroom.toLowerCase().includes(q));
  }, [students, search]);

  async function submit() {
    if (!service || !currentSchoolId || !picked.length) return;
    setBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/ops/daily`, {
        action: "enroll", serviceId: service.id, studentIds: picked,
        priceCentsOverride: subsidy ? Math.round(parseFloat(subsidy) * 100) : undefined,
      });
      toast({ title: `${res.enrolled} learners enrolled`, description: `${service.name}${subsidy ? ` at GH₵${subsidy} subsidised rate` : ""}.` });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!service} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle>Enrol learners — {service?.name}</DialogTitle>
          <DialogDescription>Enrolled learners are billed automatically when you generate the day&apos;s charges.</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="relative">
            <Search className="size-4 absolute left-2.5 top-2.5 text-muted-foreground" />
            <Input placeholder="Search learner…" value={search} onChange={(e) => setSearch(e.target.value)} className="pl-8" />
          </div>
          <div className="border rounded-lg max-h-64 overflow-y-auto nice-scroll">
            {list.map((s) => (
              <label key={s.id} className="flex items-center gap-2.5 px-3 py-2 border-b last:border-0 hover:bg-secondary/40 cursor-pointer text-sm">
                <input type="checkbox" checked={picked.includes(s.id)} onChange={() => setPicked((p) => (p.includes(s.id) ? p.filter((x) => x !== s.id) : [...p, s.id]))} className="accent-primary" />
                <span className="flex-1">{s.name}</span>
                <span className="text-xs text-muted-foreground">{s.classroom}</span>
              </label>
            ))}
          </div>
          <div className="space-y-1.5">
            <Label>Subsidised rate (GH₵) — optional</Label>
            <Input type="number" step="0.5" value={subsidy} onChange={(e) => setSubsidy(e.target.value)} placeholder={`Standard ${service ? fmtCedi(service.price) : ""}`} />
            <p className="text-xs text-muted-foreground">For needy-family support or staff wards — overrides the standard price for these learners.</p>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || !picked.length}>{busy ? "Enrolling…" : `Enrol ${picked.length || ""} learner${picked.length === 1 ? "" : "s"}`}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

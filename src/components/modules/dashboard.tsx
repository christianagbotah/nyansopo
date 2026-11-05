"use client";
import { useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { StatCard, ModuleHeader, LoadingBlock, SeverityBadge, StatusBadge } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, PAYMENT_LABELS, FLAG_LABELS, fmtDateShort } from "@/lib/format";
import { AreaChart, Area, XAxis, YAxis, Tooltip, ResponsiveContainer, BarChart, Bar, PieChart, Pie, Cell } from "recharts";
import { Banknote, CalendarCheck, Flag, TrendingUp, Users, AlertTriangle, ArrowLeftRight, Inbox, UtensilsCrossed, Calculator, Package, Smartphone, Bus, Droplets, BookOpen, Baby, UserPlus, ClipboardList, MessageSquareText, BellRing, HandCoins, PiggyBank, Receipt, LayoutList } from "lucide-react";

type Dash = {
  school: any; year: { name: string } | null; term: any;
  stats: { students: number; gender: { M: number; F: number }; staffCount: number; classes: number; billed: number; collected: number; arrears: number; collectionRate: number; flagged: number; riskHigh: number; pendingRequests: number; attendanceRate: number | null; teacherCount?: number; parentCount?: number; unpaidCount?: number; expensesTerm?: number };
  recentPayments: { id: string; receiptNo: string; amount: number; method: string; paidAt: string; student: string; category?: string }[];
  arrearsByClass: { name: string; billed: number; collected: number; arrears: number; students: number }[];
  attendanceTrend: { date: string; rate: number }[];
  flags: { id: string; type: string; severity: string; status: string; amountOwed: number | null; student: string; raisedAt: string; description: string }[];
  inquiries: any[];
};

type DailyTodayRow = { service: string; label: string; cents: number; txns: number };
type ClassroomBand = { classroom: string; m: number; f: number };
type MyClass = { classroomId: string; name: string; learners: number; presentToday: number; absentToday: number; registerTaken?: boolean };

type OpsOverview = {
  daily: { todayCharged: number; todayCollected: number; todayCount: number; monthCollected: number; outstanding: number };
  payroll: { latest: { period: string; status: string; net: number; employerSsnit: number } | null; currentPeriod: string; needsRun: boolean };
  inventory: { stockValue: number; lowStock: number; lowStockItems: { name: string; qty: number; reorder: number; unit: string }[] };
  sms: { credits: number; sentThisMonth: number; autoReceipts: boolean };
  transport: { routes: number; learners: number; capacity: number; fareRevenue: number };
  dailyToday: DailyTodayRow[];
  dailyArrearsCents: number;
  classroomBands: ClassroomBand[];
  myClasses: MyClass[];
};

const SERVICE_ICONS: Record<string, React.ReactNode> = {
  MEAL: <UtensilsCrossed className="size-4 text-muted-foreground" />,
  DRINK: <Droplets className="size-4 text-muted-foreground" />,
  CLASS: <BookOpen className="size-4 text-muted-foreground" />,
  TRANSPORT: <Bus className="size-4 text-muted-foreground" />,
  CARE: <Baby className="size-4 text-muted-foreground" />,
  OTHER: <Package className="size-4 text-muted-foreground" />,
};

const GENDER_COLORS = { boys: "var(--chart-1)", girls: "var(--chart-2)" };

export function Dashboard() {
  const { currentSchoolId, setModule, session } = useStore();
  const [data, setData] = useState<Dash | null>(null);
  const [ops, setOps] = useState<OpsOverview | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const isTeacher = session?.role === "TEACHER";

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<Dash>(`/api/school/${currentSchoolId}/dashboard`)
      .then(setData)
      .catch((e) => setErr(e.message));
    apiGet<OpsOverview>(`/api/school/${currentSchoolId}/ops/overview`).then(setOps).catch(() => null);
  };
  useEffect(load, [currentSchoolId]);

  if (err) return <div className="text-destructive text-sm">{err}</div>;
  if (!data) return <LoadingBlock label="Loading your school…" />;

  const { stats } = data;
  const attData = data.attendanceTrend.map((t) => ({ ...t, date: fmtDateShort(t.date) }));
  const genderData = [
    { name: "Boys", value: stats.gender.M },
    { name: "Girls", value: stats.gender.F },
  ];
  const bands = ops?.classroomBands ?? [];

  return (
    <div>
      <ModuleHeader
        title={`${data.school.name}`}
        description={`${data.term?.name ?? "Term"} · ${data.year?.name ?? "Academic Year"} · ${data.school.town}, ${data.school.region} — ${data.school.motto ?? ""}`}
        actions={
          <>
            <Badge variant="outline" className="gap-1.5"><TrendingUp className="size-3.5" /> {stats.collectionRate}% collected</Badge>
            {stats.pendingRequests > 0 && (
              <Button size="sm" variant="outline" className="gap-1.5" onClick={() => setModule("admissions")}>
                <Inbox className="size-3.5" /> {stats.pendingRequests} record request{stats.pendingRequests > 1 ? "s" : ""}
              </Button>
            )}
          </>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<Users className="size-4" />} label="Learners on roll" value={stats.students} sub={`${stats.gender.M} boys · ${stats.gender.F} girls · ${stats.classes} classes`} tone="primary" />
        {isTeacher ? (
          <>
            <StatCard icon={<CalendarCheck className="size-4" />} label="Attendance" value={stats.attendanceRate !== null ? `${stats.attendanceRate}%` : "—"} sub={`last ${data.attendanceTrend.length} school days`} tone="gold" />
            <StatCard icon={<Flag className="size-4" />} label="Flagged learners" value={stats.flagged} sub={`${stats.riskHigh} at high risk`} tone="danger" />
            <StatCard icon={<Users className="size-4" />} label="Staff on roll" value={stats.staffCount} sub="teaching & non-teaching" />
          </>
        ) : (
          <>
            <StatCard icon={<Banknote className="size-4" />} label="Collected this term" value={fmtCedi(stats.collected, { compact: true })} sub={`of ${fmtCedi(stats.billed, { compact: true })} billed`} tone="gold" />
            <StatCard icon={<AlertTriangle className="size-4" />} label="Arrears" value={fmtCedi(stats.arrears, { compact: true })} sub={`${stats.collectionRate}% collection rate`} tone="danger" />
            <StatCard icon={<CalendarCheck className="size-4" />} label="Attendance" value={stats.attendanceRate !== null ? `${stats.attendanceRate}%` : "—"} sub={`last ${data.attendanceTrend.length} school days`} />
          </>
        )}
      </div>

      {!isTeacher && (
        <>
          {/* ── CI3 key metrics — every card links into its module. Duplicates the
              top KPI row never: roll/attendance live up top, so this row shows
              people + today's cash + classes (CI3 dashboard tile set) ── */}
          <div className="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <button onClick={() => setModule("staffhub")} className="rounded-xl border border-r-4 border-r-pink-500 bg-card p-4 text-left hover:bg-secondary/40 transition-colors">
              <div className="flex items-center justify-between"><span className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Active teachers</span><ClipboardList className="size-4 text-pink-500" /></div>
              <div className="mt-1 text-2xl font-bold tabular-nums">{stats.teacherCount ?? "—"}</div>
              <div className="text-[11px] text-muted-foreground">Teaching staff</div>
            </button>
            <button onClick={() => setModule("sms", { tab: "compose" })} className="rounded-xl border border-r-4 border-r-sky-500 bg-card p-4 text-left hover:bg-secondary/40 transition-colors">
              <div className="flex items-center justify-between"><span className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Registered parents</span><MessageSquareText className="size-4 text-sky-500" /></div>
              <div className="mt-1 text-2xl font-bold tabular-nums">{stats.parentCount ?? "—"}</div>
              <div className="text-[11px] text-muted-foreground">Guardians with portal logins</div>
            </button>
            <button onClick={() => setModule("cashier", { tab: "portal" })} className="rounded-xl border border-r-4 border-r-emerald-500 bg-card p-4 text-left hover:bg-secondary/40 transition-colors">
              <div className="flex items-center justify-between"><span className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Cash today</span><HandCoins className="size-4 text-emerald-500" /></div>
              <div className="mt-1 text-2xl font-bold tabular-nums">{ops ? fmtCedi(ops.daily.todayCollected, { compact: true }) : "—"}</div>
              <div className="text-[11px] text-muted-foreground">Counter + wallets collected today</div>
            </button>
            <button onClick={() => setModule("classes")} className="rounded-xl border border-r-4 border-r-amber-500 bg-card p-4 text-left hover:bg-secondary/40 transition-colors">
              <div className="flex items-center justify-between"><span className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Classes</span><LayoutList className="size-4 text-amber-500" /></div>
              <div className="mt-1 text-2xl font-bold tabular-nums">{stats.classes}</div>
              <div className="text-[11px] text-muted-foreground">Active classrooms</div>
            </button>
          </div>

          {/* ── CI3 quick actions ── */}
          <Card className="mt-4">
            <CardHeader className="pb-3"><CardTitle className="text-base">Quick actions</CardTitle></CardHeader>
            <CardContent className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
              {[
                { label: "Add student", hint: "admit a learner", icon: UserPlus, onClick: () => setModule("students", { tab: "directory" }) },
                { label: "Mark attendance", hint: "today's register", icon: CalendarCheck, onClick: () => setModule("attendance", { tab: "register" }) },
                { label: "Billing", hint: "invoices & items", icon: Banknote, onClick: () => setModule("fees", { tab: "invoices" }) },
                { label: "Take payment", hint: "cashier counter", icon: HandCoins, onClick: () => setModule("cashier", { tab: "portal" }) },
                { label: "Messages", hint: "SMS centre", icon: MessageSquareText, onClick: () => setModule("sms") },
                { label: "Bill reminders", hint: "chase arrears", icon: BellRing, onClick: () => setModule("fees", { tab: "arrears" }) },
              ].map((a) => (
                <button key={a.label} onClick={a.onClick} className="group rounded-xl border bg-card p-3 text-center transition-all hover:-translate-y-0.5 hover:shadow-md">
                  <span className="mx-auto grid size-10 place-items-center rounded-full bg-primary/10 text-primary group-hover:bg-primary group-hover:text-primary-foreground transition-colors"><a.icon className="size-5" /></span>
                  <span className="mt-2 block text-xs font-semibold">{a.label}</span>
                  <span className="block text-[10px] text-muted-foreground">{a.hint}</span>
                </button>
              ))}
            </CardContent>
          </Card>

          {/* ── CI3 financial summary — gradient cards ── */}
          <div className="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div className="rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 p-4 text-white shadow-sm">
              <div className="flex items-center justify-between"><span className="text-xs font-semibold uppercase tracking-wide text-white/80">Total due invoices</span><Receipt className="size-4" /></div>
              <div className="mt-1 text-2xl font-bold tabular-nums">{fmtCedi(stats.arrears, { compact: true })}</div>
              <div className="text-[11px] text-white/80">outstanding this term</div>
            </div>
            <div className="rounded-xl bg-gradient-to-br from-emerald-600 to-emerald-400 p-4 text-white shadow-sm">
              <div className="flex items-center justify-between"><span className="text-xs font-semibold uppercase tracking-wide text-white/80">Total income</span><PiggyBank className="size-4" /></div>
              <div className="mt-1 text-2xl font-bold tabular-nums">{fmtCedi(stats.collected, { compact: true })}</div>
              <div className="text-[11px] text-white/80">received this term</div>
            </div>
            <div className="rounded-xl bg-gradient-to-br from-violet-600 to-purple-500 p-4 text-white shadow-sm">
              <div className="flex items-center justify-between"><span className="text-xs font-semibold uppercase tracking-wide text-white/80">Unpaid invoices</span><AlertTriangle className="size-4" /></div>
              <div className="mt-1 text-2xl font-bold tabular-nums">{stats.unpaidCount ?? "—"}</div>
              <div className="text-[11px] text-white/80">learners still owing</div>
            </div>
            <div className="rounded-xl bg-gradient-to-br from-orange-600 to-amber-500 p-4 text-white shadow-sm">
              <div className="flex items-center justify-between"><span className="text-xs font-semibold uppercase tracking-wide text-white/80">Termly expenses</span><Calculator className="size-4" /></div>
              <div className="mt-1 text-2xl font-bold tabular-nums">{fmtCedi(stats.expensesTerm ?? 0, { compact: true })}</div>
              <div className="text-[11px] text-white/80">spent this term</div>
            </div>
          </div>
        </>
      )}

      {/* teacher mode — "my classes" grid: classrooms this teacher class-teaches,
          with today's register snapshot; deep-links into the register */}
      {isTeacher && !!ops?.myClasses?.length && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 mt-3">
          {ops.myClasses.map((c) => (
            <button key={c.classroomId} onClick={() => setModule("attendance", { classroomId: c.classroomId })}
              className="text-left rounded-xl border bg-card hover:bg-secondary/40 transition-colors p-4">
              <div className="flex items-center justify-between gap-2">
                <span className="text-sm font-semibold leading-tight">{c.name}</span>
                <Users className="size-4 text-muted-foreground shrink-0" />
              </div>
              <div className="mt-1.5 text-xl font-bold tabular-nums">
                {c.learners}<span className="text-xs font-normal text-muted-foreground ml-1">learners</span>
              </div>
              <div className="text-[11px] text-muted-foreground">
                {c.registerTaken ? `${c.presentToday} present · ${c.absentToday} absent today` : "Register not taken today"}
              </div>
              <div className="mt-2 flex items-center gap-1 text-[11px] font-medium text-primary">
                <CalendarCheck className="size-3" /> Take today&rsquo;s register
              </div>
            </button>
          ))}
        </div>
      )}

      {/* ops strip — daily services, payroll, inventory, sms, transport (staff with money scope only) */}
      {ops && !isTeacher && (
        <div className="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mt-3">
          <button onClick={() => setModule("daily")} className="text-left rounded-xl border bg-card hover:bg-secondary/40 transition-colors p-4">
            <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Tuck today</span><UtensilsCrossed className="size-4 text-muted-foreground" /></div>
            <div className="mt-1.5 text-xl font-bold tabular-nums text-primary">{fmtCedi(ops.daily.todayCollected, { compact: true })}</div>
            <div className="text-[11px] text-muted-foreground">{ops.daily.todayCount} charges · {fmtCedi(ops.daily.outstanding, { compact: true })} arrears</div>
          </button>
          <button onClick={() => setModule("payroll")} className="text-left rounded-xl border bg-card hover:bg-secondary/40 transition-colors p-4">
            <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Payroll</span><Calculator className="size-4 text-muted-foreground" /></div>
            <div className="mt-1.5 text-xl font-bold tabular-nums">{ops.payroll.latest ? fmtCedi(ops.payroll.latest.net, { compact: true }) : "—"}</div>
            <div className="text-[11px] text-muted-foreground">{ops.payroll.needsRun ? <span className="text-amber-600 font-medium">Run {ops.payroll.currentPeriod} payroll</span> : `${ops.payroll.latest?.period} · ${ops.payroll.latest?.status.toLowerCase()}`}</div>
          </button>
          <button onClick={() => setModule("inventory")} className="text-left rounded-xl border bg-card hover:bg-secondary/40 transition-colors p-4">
            <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Stock value</span><Package className="size-4 text-muted-foreground" /></div>
            <div className="mt-1.5 text-xl font-bold tabular-nums text-[#a97c0b]">{fmtCedi(ops.inventory.stockValue, { compact: true })}</div>
            <div className="text-[11px] text-muted-foreground">{ops.inventory.lowStock > 0 ? <span className="text-destructive font-medium">{ops.inventory.lowStock} items low</span> : "all stocked"}</div>
          </button>
          <button onClick={() => setModule("sms")} className="text-left rounded-xl border bg-card hover:bg-secondary/40 transition-colors p-4">
            <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">SMS credits</span><Smartphone className="size-4 text-muted-foreground" /></div>
            <div className="mt-1.5 text-xl font-bold tabular-nums">{ops.sms.credits.toLocaleString()}</div>
            <div className="text-[11px] text-muted-foreground">{ops.sms.sentThisMonth} sent this month</div>
          </button>
          <button onClick={() => setModule("transport")} className="text-left rounded-xl border bg-card hover:bg-secondary/40 transition-colors p-4 col-span-2 lg:col-span-1">
            <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">On the buses</span><Bus className="size-4 text-muted-foreground" /></div>
            <div className="mt-1.5 text-xl font-bold tabular-nums">{ops.transport.learners}</div>
            <div className="text-[11px] text-muted-foreground">{ops.transport.routes} routes · {fmtCedi(ops.transport.fareRevenue, { compact: true })}/term</div>
          </button>
        </div>
      )}

      {/* daily-fees strip — per daily-service "received today" cards + unpaid balances
          (admin/finance-visible roles only, same gate as the ops strip above) */}
      {ops && !isTeacher && (ops.dailyToday?.length ?? 0) > 0 && (
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mt-3">
          {ops.dailyToday.map((d) => (
            <button key={d.service} onClick={() => setModule("daily")} className="text-left rounded-xl border bg-card hover:bg-secondary/40 transition-colors p-4">
              <div className="flex items-center justify-between gap-2">
                <span className="text-xs font-medium uppercase tracking-wide text-muted-foreground truncate">{d.label} · today</span>
                {SERVICE_ICONS[cat(d.label)] ?? SERVICE_ICONS.OTHER}
              </div>
              <div className="mt-1.5 text-xl font-bold tabular-nums text-primary">{fmtCedi(d.cents, { compact: true })}</div>
              <div className="text-[11px] text-muted-foreground">{d.txns} charge{d.txns === 1 ? "" : "s"} received today</div>
            </button>
          ))}
          <button onClick={() => setModule("daily", { tab: "ledger" })}
            className="text-left rounded-xl border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/30 hover:bg-red-100 dark:hover:bg-red-950/50 transition-colors p-4">
            <div className="flex items-center justify-between gap-2">
              <span className="text-xs font-medium uppercase tracking-wide text-red-700 dark:text-red-300">Unpaid daily-fee balances</span>
              <AlertTriangle className="size-4 text-red-600 dark:text-red-400" />
            </div>
            <div className="mt-1.5 text-xl font-bold tabular-nums text-destructive">{fmtCedi(ops.dailyArrearsCents, { compact: true })}</div>
            <div className="text-[11px] text-red-700/80 dark:text-red-300/80">open the ledger to chase payments</div>
          </button>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
        <Card className="lg:col-span-2">
          <CardHeader className="pb-2"><CardTitle className="text-base">Attendance trend</CardTitle></CardHeader>
          <CardContent className="h-56">
            {attData.length ? (
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={attData} margin={{ top: 4, right: 8, left: -18, bottom: 0 }}>
                  <defs>
                    <linearGradient id="attGrad" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stopColor="var(--chart-1)" stopOpacity={0.35} />
                      <stop offset="100%" stopColor="var(--chart-1)" stopOpacity={0.02} />
                    </linearGradient>
                  </defs>
                  <XAxis dataKey="date" tick={{ fontSize: 11 }} stroke="#888" />
                  <YAxis domain={[0, 100]} tick={{ fontSize: 11 }} stroke="#888" unit="%" />
                  <Tooltip formatter={(v: any) => [`${v}%`, "Present"]} contentStyle={{ borderRadius: 10, border: "1px solid #e7ddc8" }} />
                  <Area type="monotone" dataKey="rate" stroke="var(--chart-1)" strokeWidth={2.2} fill="url(#attGrad)" />
                </AreaChart>
              </ResponsiveContainer>
            ) : (
              <div className="h-full grid place-items-center text-sm text-muted-foreground">No attendance sessions recorded yet — take the first register from the Attendance module.</div>
            )}
          </CardContent>
        </Card>

        {isTeacher ? (
          <Card>
            <CardHeader className="pb-2"><CardTitle className="text-base">Today&apos;s classroom</CardTitle></CardHeader>
            <CardContent className="space-y-2">
              <div className="text-xs text-muted-foreground">Jump straight into the class toolchain.</div>
              <Button size="sm" variant="outline" className="w-full justify-start gap-2" onClick={() => setModule("attendance")}><CalendarCheck className="size-4" /> Take the register</Button>
              <Button size="sm" variant="outline" className="w-full justify-start gap-2" onClick={() => setModule("gradebook")}><Banknote className="hidden" /><TrendingUp className="size-4" /> Enter assessment marks</Button>
              <Button size="sm" variant="outline" className="w-full justify-start gap-2" onClick={() => setModule("reports")}><Users className="hidden" /><Flag className="size-4" /> Draft report remarks</Button>
            </CardContent>
          </Card>
        ) : (
          <Card>
            <CardHeader className="pb-2"><CardTitle className="text-base">Term collection</CardTitle></CardHeader>
            <CardContent className="space-y-3">
              <div>
                <div className="flex justify-between text-sm mb-1"><span className="text-muted-foreground">Collected</span><span className="font-semibold">{fmtCedi(stats.collected)}</span></div>
                <div className="h-2.5 rounded-full bg-secondary overflow-hidden">
                  <div className="h-full bg-primary rounded-full transition-all" style={{ width: `${stats.collectionRate}%` }} />
                </div>
              </div>
              <div className="flex justify-between text-sm pt-1 border-t">
                <span className="text-muted-foreground">Outstanding</span>
                <span className="font-semibold text-destructive">{fmtCedi(stats.arrears)}</span>
              </div>
              <div className="text-xs text-muted-foreground">
                Mid-term benchmark: schools should sit above 65% collection by the mid-term break.
              </div>
              <Button size="sm" variant="outline" className="w-full gap-1.5" onClick={() => setModule("fees")}>
                <Banknote className="size-4" /> Open Fees &amp; Billing
              </Button>
            </CardContent>
          </Card>
        )}
      </div>

      {/* learner distribution — gender split donut + enrolment by classroom */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-base">Learners by gender</CardTitle></CardHeader>
          <CardContent className="h-56">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie data={genderData} dataKey="value" nameKey="name" innerRadius={44} outerRadius={68} paddingAngle={3} strokeWidth={0}>
                  <Cell fill={GENDER_COLORS.boys} />
                  <Cell fill={GENDER_COLORS.girls} />
                </Pie>
                <Tooltip formatter={(v: any) => [v, "learners"]} contentStyle={{ borderRadius: 10, border: "1px solid #e7ddc8" }} />
              </PieChart>
            </ResponsiveContainer>
            <div className="flex items-center justify-center gap-4 -mt-2 pb-1">
              <span className="flex items-center gap-1.5 text-xs text-muted-foreground"><span className="size-2.5 rounded-full" style={{ background: GENDER_COLORS.boys }} /> Boys · {stats.gender.M}</span>
              <span className="flex items-center gap-1.5 text-xs text-muted-foreground"><span className="size-2.5 rounded-full" style={{ background: GENDER_COLORS.girls }} /> Girls · {stats.gender.F}</span>
            </div>
          </CardContent>
        </Card>
        <Card className="lg:col-span-2">
          <CardHeader className="pb-2"><CardTitle className="text-base">Enrolment by classroom <span className="text-xs font-normal text-muted-foreground">— top 10, boys/girls stacked</span></CardTitle></CardHeader>
          <CardContent className="h-56">
            {bands.length ? (
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={bands} margin={{ top: 4, right: 8, left: -12, bottom: 0 }}>
                  <XAxis dataKey="classroom" tick={{ fontSize: 9 }} stroke="#888" interval={0} angle={-20} textAnchor="end" height={44} />
                  <YAxis tick={{ fontSize: 11 }} stroke="#888" allowDecimals={false} />
                  <Tooltip formatter={(v: any, n: any) => [v, n]} contentStyle={{ borderRadius: 10 }} />
                  <Bar dataKey="m" name="Boys" stackId="a" fill={GENDER_COLORS.boys} radius={[4, 0, 0, 0]} />
                  <Bar dataKey="f" name="Girls" stackId="a" fill={GENDER_COLORS.girls} radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            ) : (
              <div className="h-full grid place-items-center text-sm text-muted-foreground">No active enrolments yet.</div>
            )}
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
        {!isTeacher && (
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-base">Arrears by class</CardTitle></CardHeader>
          <CardContent className="h-64">
            {data.arrearsByClass.length ? (
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={data.arrearsByClass.slice(0, 8)} margin={{ top: 4, right: 8, left: -12, bottom: 0 }}>
                  <XAxis dataKey="name" tick={{ fontSize: 9 }} stroke="#888" interval={0} angle={-20} textAnchor="end" height={44} />
                  <YAxis tick={{ fontSize: 11 }} stroke="#888" />
                  <Tooltip formatter={(v: any) => [fmtCedi(Number(v)), "Arrears"]} contentStyle={{ borderRadius: 10 }} />
                  <Bar dataKey="arrears" fill="#c05621" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            ) : (
              <div className="h-full grid place-items-center text-sm text-muted-foreground">No billing data yet.</div>
            )}
          </CardContent>
        </Card>
        )}

        <Card>
          <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
            <CardTitle className="text-base flex items-center gap-2"><Flag className="size-4 text-destructive" /> Active flags ({stats.flagged})</CardTitle>
            <Button size="sm" variant="ghost" onClick={() => setModule("students")}>View students</Button>
          </CardHeader>
          <CardContent className="space-y-2.5 max-h-64 overflow-y-auto nice-scroll">
            {data.flags.length === 0 && <div className="text-sm text-muted-foreground py-6 text-center">No active flags — clean register. 🎉</div>}
            {data.flags.map((f) => (
              <div key={f.id} className="rounded-lg border p-3">
                <div className="flex items-center justify-between gap-2">
                  <div className="font-medium text-sm">{f.student}</div>
                  <div className="flex items-center gap-1.5"><SeverityBadge severity={f.severity} /><StatusBadge status={f.status} /></div>
                </div>
                <div className="text-xs text-muted-foreground mt-1">
                  {FLAG_LABELS[f.type] ?? f.type}{f.amountOwed ? ` — ${fmtCedi(f.amountOwed)}` : ""} · raised {fmtDate(f.raisedAt)}
                </div>
                <p className="text-xs mt-1.5 line-clamp-2 text-muted-foreground">{f.description}</p>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
        {!isTeacher && (
        <Card>
          <CardHeader className="pb-3"><CardTitle className="text-base">Recent receipts</CardTitle></CardHeader>
          <CardContent className="max-h-72 overflow-y-auto nice-scroll">
            <table className="w-full text-sm">
              <tbody>
                {data.recentPayments.map((p) => (
                  <tr key={p.id} className="border-b last:border-0">
                    <td className="py-2.5">
                      <div className="font-medium">{p.student}</div>
                      <div className="text-xs text-muted-foreground">{p.receiptNo} · {PAYMENT_LABELS[p.method] ?? p.method}{p.category === "DAILY" ? " · tuck" : ""}</div>
                    </td>
                    <td className="py-2.5 text-right text-muted-foreground text-xs">{fmtDate(p.paidAt)}</td>
                    <td className="py-2.5 text-right font-semibold tabular-nums">{fmtCedi(p.amount)}</td>
                  </tr>
                ))}
                {!data.recentPayments.length && <tr><td className="py-6 text-center text-muted-foreground">No payments yet this term.</td></tr>}
              </tbody>
            </table>
          </CardContent>
        </Card>
        )}

        <Card>
          <CardHeader className="pb-3"><CardTitle className="text-base">Attention needed</CardTitle></CardHeader>
          <CardContent className="space-y-2.5">
            <div className="flex items-center justify-between rounded-lg border border-orange-200 dark:border-orange-900 bg-orange-50 dark:bg-orange-950/40 p-3">
              <div className="flex items-center gap-2.5 text-sm">
                <AlertTriangle className="size-4.5 text-orange-600" />
                <span><b>{stats.riskHigh}</b> learners at HIGH/CRITICAL risk</span>
              </div>
              <Button size="sm" variant="outline" onClick={() => setModule("ai")}>Open AI</Button>
            </div>
            <div className="flex items-center justify-between rounded-lg border p-3 text-sm">
              <div className="flex items-center gap-2.5"><ArrowLeftRight className="size-4.5 text-primary" /> <span><b>{data.inquiries.length}</b> recent admission inquiries</span></div>
              <Button size="sm" variant="outline" onClick={() => setModule("admissions")}>Review</Button>
            </div>
            <div className="flex items-center justify-between rounded-lg border p-3 text-sm">
              <div className="flex items-center gap-2.5"><Flag className="size-4.5 text-destructive" /> <span><b>{stats.flagged}</b> active defaulter flags</span></div>
              <Button size="sm" variant="outline" onClick={() => setModule("students")}>Manage</Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

/* pick a strip icon from the service label — services are named e.g.
   "Lunch (canteen)", "Sachet Water"; fall back to OTHER */
function cat(label: string): string {
  const l = label.toLowerCase();
  if (/(lunch|breakfast|meal|canteen|food|snack)/.test(l)) return "MEAL";
  if (/(water|drink|juice|sachet|beverage)/.test(l)) return "DRINK";
  if (/(class|lesson|remedial|tutorial|extra)/.test(l)) return "CLASS";
  if (/(transport|bus|fare|shuttle)/.test(l)) return "TRANSPORT";
  if (/(care|creche|nurse|sick)/.test(l)) return "CARE";
  return "OTHER";
}

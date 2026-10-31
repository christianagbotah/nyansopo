"use client";
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet } from "@/lib/api";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, PAYMENT_LABELS } from "@/lib/format";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { toast } from "@/hooks/use-toast";
import { initials, avatarColor } from "@/lib/format";
import { Home, Wallet, CalendarCheck, GraduationCap, Bus, MessageSquareText, Megaphone, Phone, ArrowUpRight, CheckCircle2, AlertTriangle, Receipt } from "lucide-react";

type Invoice = {
  id: string; invoiceNo: string; status: string; dueDate: string | null; createdAt: string;
  total: number; discount: number; paid: number; balance: number;
  items: { name: string; amount: number }[]; term: string | null;
};
type ReportCard = {
  id: string; term: string; classroom: string; average: number | null; position: number | null; classSize: number | null;
  conduct: string | null; interest: string | null; attendance: string | null; teacherRemark: string | null; headRemark: string | null;
  promotion: string | null; published: boolean;
  results: { subject: string; isCore: boolean; total: number | null; grade: string | null; remark: string | null; position: number | null }[];
};
type Child = {
  id: string; name: string; otherNames: string | null; nyansapoIndex: string; gender: string; status: string; guardianPhone: string;
  classroom: string | null; classTeacher: string | null;
  invoices: Invoice[];
  payments: { receiptNo: string; amount: number; method: string; paidAt: string; invoiceNo: string }[];
  attendance: { total: number; present: number; late: number; absent: number; rate: number | null; recent: { date: string; status: string }[] };
  reportCards: ReportCard[];
  transport: null | {
    route: string; vehicleReg: string; driverName: string; driverPhone: string; pickupStop: string | null; dropStop: string | null;
    farePerTerm: number; farePerTrip: number;
    recentBoardings: { date: string; direction: string; fare: number; paid: number }[];
  };
  wallet: null | { prepaid: number; transport: number };
};
type ParentData = {
  role: string; school: { name: string; motto: string | null; phone: string | null; town: string; region: string } | null;
  year: string | null; term: string | null;
  children: Child[];
  announcements: { id: string; title: string; body: string; postedByName: string; postedAt: string; category: string; priority: string }[];
  messages: { id: string; recipientName: string; recipientPhone: string; body: string; sentAt: string; status: string }[];
};

const ATT_COLOR: Record<string, string> = { PRESENT: "bg-emerald-600", LATE: "bg-amber-500", ABSENT: "bg-destructive", SICK: "bg-sky-500", EXCUSED: "bg-zinc-400" };
const DIR_LABEL: Record<string, string> = { IN: "Morning pick-up", OUT: "Afternoon drop-off", BOTH: "Both ways", NONE: "Not riding" };

export function ParentPortal() {
  const { currentSchoolId, session } = useStore();
  const [data, setData] = useState<ParentData | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [childIdx, setChildIdx] = useState(0);

  useEffect(() => {
    if (!currentSchoolId) return;
    apiGet<ParentData>(`/api/school/${currentSchoolId}/ops/portal?view=parent`)
      .then((d) => { setData(d); setChildIdx(0); })
      .catch((e) => setErr(e.message));
  }, [currentSchoolId]);

  if (err) return <div className="text-destructive text-sm">{err}</div>;
  if (!data) return <LoadingBlock label="Fetching your children…" />;
  if (!data.children.length) return <EmptyState icon={<Home className="size-8" />} title="No children linked" hint="Ask the school office to link your children to this account." />;

  const child = data.children[Math.min(childIdx, data.children.length - 1)];
  const balance = child.invoices.reduce((s, i) => s + i.balance, 0);

  return (
    <div>
      <ModuleHeader
        title={`Akwaaba, ${session?.fullName?.split(" ").slice(-1)[0]}`}
        description={`${data.school?.name ?? "Your school"} · ${data.term ?? "Term"} ${data.year ? `· ${data.year}` : ""} — everything about your children in one place.`}
        actions={data.school?.phone ? <Badge variant="outline" className="gap-1.5"><Phone className="size-3.5" /> {data.school.phone}</Badge> : null}
      />

      {/* child switcher */}
      <div className="flex flex-wrap gap-2 mb-4">
        {data.children.map((c, i) => (
          <button key={c.id} onClick={() => setChildIdx(i)}
            className={`flex items-center gap-2.5 rounded-xl border px-3 py-2 transition-colors ${i === childIdx ? "border-primary bg-primary/5" : "hover:bg-secondary/50"}`}>
            <span className={`flex size-8 items-center justify-center rounded-full text-xs font-bold text-white`} style={{ background: avatarColor(c.id) }}>{initials(c.name)}</span>
            <span className="text-left">
              <span className="block text-sm font-medium leading-tight">{c.name}</span>
              <span className="block text-[11px] text-muted-foreground">{c.classroom ?? "—"}</span>
            </span>
          </button>
        ))}
      </div>

      {/* quick stats */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <StatTile icon={<Wallet className="size-4" />} label="Fee balance" value={fmtCedi(balance, { compact: true })} sub={balance > 0 ? "across all bills" : "fully settled — medaase!"} tone={balance > 0 ? "danger" : "good"} />
        <StatTile icon={<CalendarCheck className="size-4" />} label="Attendance" value={child.attendance.rate !== null ? `${child.attendance.rate}%` : "—"} sub={`${child.attendance.present} present · ${child.attendance.absent} absent`} />
        <StatTile icon={<GraduationCap className="size-4" />} label="Latest average" value={child.reportCards[0]?.average != null ? `${child.reportCards[0].average}%` : "—"} sub={child.reportCards[0]?.term ?? "no reports yet"} />
        <StatTile icon={<Bus className="size-4" />} label={child.transport ? "Transport" : "Transport"} value={child.transport ? child.transport.route : "Not enrolled"} sub={child.transport ? `${child.transport.pickupStop ?? "—"} → school` : "no route assigned"} />
      </div>

      <Tabs defaultValue="overview">
        <TabsList className="flex-wrap h-auto">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="fees">Fees & Payments</TabsTrigger>
          <TabsTrigger value="attendance">Attendance</TabsTrigger>
          <TabsTrigger value="reports">Reports</TabsTrigger>
          {child.transport && <TabsTrigger value="transport">Transport</TabsTrigger>}
          <TabsTrigger value="messages">Messages</TabsTrigger>
        </TabsList>

        <TabsContent value="overview" className="mt-3 space-y-4">
          <div className="grid lg:grid-cols-3 gap-4">
            <Card className="lg:col-span-2">
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Receipt className="size-4" /> Bills this year</CardTitle></CardHeader>
              <CardContent>
                {child.invoices.length ? (
                  <div className="space-y-2">
                    {child.invoices.slice(0, 4).map((inv) => (
                      <div key={inv.id} className="flex items-center justify-between rounded-lg border px-3 py-2">
                        <div className="min-w-0">
                          <div className="text-sm font-medium truncate">{inv.invoiceNo} {inv.term && <span className="text-xs text-muted-foreground">· {inv.term}</span>}</div>
                          <div className="text-xs text-muted-foreground">{inv.items.length} item{inv.items.length > 1 ? "s" : ""}{inv.dueDate ? ` · due ${fmtDate(inv.dueDate)}` : ""}</div>
                        </div>
                        <div className="text-right shrink-0">
                          <div className="text-sm font-semibold tabular-nums">{fmtCedi(inv.balance)}</div>
                          <Badge className={inv.status === "PAID" ? "bg-emerald-600" : inv.status === "PARTIAL" ? "bg-amber-600" : "bg-destructive"}>{inv.status}</Badge>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : <EmptyState title="No bills yet" hint="Invoices will appear here when the school issues them." />}
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Megaphone className="size-4" /> School notices</CardTitle></CardHeader>
              <CardContent className="space-y-2.5 max-h-72 overflow-y-auto nice-scroll">
                {data.announcements.length ? data.announcements.map((a) => (
                  <div key={a.id} className="rounded-lg border p-2.5">
                    <div className="flex items-center gap-1.5">
                      {a.priority === "HIGH" && <Badge className="bg-destructive text-[9px]">urgent</Badge>}
                      <span className="text-sm font-medium">{a.title}</span>
                    </div>
                    <p className="text-xs text-muted-foreground mt-1 line-clamp-3">{a.body}</p>
                    <div className="text-[10px] text-muted-foreground mt-1">{a.postedByName} · {fmtDate(a.postedAt)}</div>
                  </div>
                )) : <EmptyState title="No notices" />}
              </CardContent>
            </Card>
          </div>

          {child.wallet && (child.wallet.prepaid > 0 || child.wallet.transport > 0) && (
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Wallet className="size-4" /> Prepaid wallets</CardTitle></CardHeader>
              <CardContent className="grid grid-cols-2 gap-3">
                <div className="rounded-lg border p-3"><div className="text-xs text-muted-foreground">Tuck / meals wallet</div><div className="text-xl font-bold tabular-nums">{fmtCedi(child.wallet.prepaid)}</div></div>
                <div className="rounded-lg border p-3"><div className="text-xs text-muted-foreground">Transport wallet</div><div className="text-xl font-bold tabular-nums">{fmtCedi(child.wallet.transport)}</div></div>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        <TabsContent value="fees" className="mt-3 space-y-4">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-base">Bills & balances — {child.name}</CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Bill</TableHead>
                    <TableHead className="hidden md:table-cell">Items</TableHead>
                    <TableHead>Total</TableHead>
                    <TableHead>Paid</TableHead>
                    <TableHead>Balance</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead />
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {child.invoices.map((inv) => (
                    <TableRow key={inv.id}>
                      <TableCell>
                        <div className="font-medium">{inv.invoiceNo}</div>
                        <div className="text-xs text-muted-foreground">{inv.term ?? fmtDate(inv.createdAt)}</div>
                      </TableCell>
                      <TableCell className="hidden md:table-cell text-xs text-muted-foreground">{inv.items.map((i) => i.name).join(" · ")}</TableCell>
                      <TableCell className="tabular-nums">{fmtCedi(inv.total - inv.discount)}</TableCell>
                      <TableCell className="tabular-nums text-emerald-600 dark:text-emerald-400">{fmtCedi(inv.paid)}</TableCell>
                      <TableCell className="tabular-nums font-semibold">{fmtCedi(inv.balance)}</TableCell>
                      <TableCell><Badge className={inv.status === "PAID" ? "bg-emerald-600" : inv.status === "PARTIAL" ? "bg-amber-600" : "bg-destructive"}>{inv.status}</Badge></TableCell>
                      <TableCell>
                        <Button size="sm" variant="ghost" className="h-7 gap-1 text-xs" onClick={() => window.open(`/invoice/${inv.id}`, "_blank")}>
                          Statement <ArrowUpRight className="size-3" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
              {!child.invoices.length && <EmptyState title="No bills this year" />}
            </CardContent>
          </Card>

          <div className="grid md:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base">Payment history</CardTitle></CardHeader>
              <CardContent>
                {child.payments.length ? (
                  <div className="space-y-1.5 max-h-64 overflow-y-auto nice-scroll">
                    {child.payments.map((p, i) => (
                      <div key={i} className="flex items-center justify-between rounded-lg border px-3 py-2">
                        <div>
                          <div className="text-sm font-medium">{fmtCedi(p.amount)}</div>
                          <div className="text-xs text-muted-foreground">{fmtDate(p.paidAt)} · {PAYMENT_LABELS[p.method] ?? p.method}</div>
                        </div>
                        <span className="text-[10px] font-mono text-muted-foreground">{p.receiptNo}</span>
                      </div>
                    ))}
                  </div>
                ) : <EmptyState title="No payments recorded yet" />}
              </CardContent>
            </Card>

            <Card className="bg-secondary/30">
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Phone className="size-4" /> How to pay</CardTitle></CardHeader>
              <CardContent className="text-sm space-y-2.5 text-muted-foreground">
                <p><b className="text-foreground">Mobile Money</b> — the school&apos;s MoMo number is on every statement. Use your child&apos;s <b className="text-foreground">{child.nyansapoIndex}</b> as the reference so the payment lands on the right bill.</p>
                <p><b className="text-foreground">At the school office</b> — cash, cheque and card are accepted at the front desk during school hours; you get a printed receipt every time.</p>
                <p><b className="text-foreground">Bank transfer</b> — request the school&apos;s account details from the bursar&apos;s office and forward the slip on WhatsApp.</p>
                <p className="text-xs">Every payment is receipted. If a payment does not reflect within 24 hours, call the office with your receipt number.</p>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="attendance" className="mt-3 space-y-4">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base">Attendance — {child.name}</CardTitle></CardHeader>
            <CardContent>
              {child.attendance.total ? (
                <>
                  <div className="grid grid-cols-4 gap-3 mb-4">
                    <MiniStat label="Present" value={child.attendance.present} tone="good" />
                    <MiniStat label="Late" value={child.attendance.late} tone="warn" />
                    <MiniStat label="Absent" value={child.attendance.absent} tone="bad" />
                    <MiniStat label="Sessions" value={child.attendance.total} />
                  </div>
                  <div className="mb-1 flex justify-between text-xs text-muted-foreground"><span>Attendance rate</span><span className="font-semibold">{child.attendance.rate}%</span></div>
                  <Progress value={child.attendance.rate ?? 0} className="h-2" />
                  <div className="mt-4">
                    <div className="text-xs font-medium uppercase tracking-wide text-muted-foreground mb-2">Last 10 sessions (most recent first)</div>
                    <div className="flex gap-1.5 flex-wrap">
                      {child.attendance.recent.map((r, i) => (
                        <div key={i} className="w-16 rounded-lg border overflow-hidden">
                          <div className={`h-1.5 ${ATT_COLOR[r.status] ?? "bg-zinc-300"}`} />
                          <div className="px-1.5 py-1">
                            <div className="text-[10px] font-medium">{r.status}</div>
                            <div className="text-[9px] text-muted-foreground">{fmtDate(r.date).slice(0, 6)}</div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                </>
              ) : <EmptyState title="No attendance recorded yet" />}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="reports" className="mt-3 space-y-4">
          {child.reportCards.length ? child.reportCards.map((rc) => (
            <Card key={rc.id}>
              <CardHeader className="pb-2">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <CardTitle className="text-base">{rc.term} — {rc.classroom}</CardTitle>
                  <div className="flex items-center gap-2">
                    {rc.position != null && <Badge variant="secondary">{rc.position} of {rc.classSize}</Badge>}
                    {rc.promotion && <Badge className={rc.promotion === "PROMOTED" ? "bg-emerald-600" : "bg-amber-600"}>{rc.promotion}</Badge>}
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-3 gap-3 mb-3">
                  <MiniStat label="Average" value={rc.average != null ? `${rc.average}%` : "—"} tone="good" />
                  <MiniStat label="Conduct" value={rc.conduct ?? "—"} />
                  <MiniStat label="Attendance" value={rc.attendance ?? "—"} />
                </div>
                <Table>
                  <TableHeader>
                    <TableRow><TableHead>Subject</TableHead><TableHead>Score</TableHead><TableHead>Grade</TableHead><TableHead className="hidden sm:table-cell">Remark</TableHead></TableRow>
                  </TableHeader>
                  <TableBody>
                    {rc.results.map((r, i) => (
                      <TableRow key={i}>
                        <TableCell className="font-medium">{r.subject}{r.isCore && <Badge variant="outline" className="ml-1.5 text-[9px]">core</Badge>}</TableCell>
                        <TableCell className="tabular-nums">{r.total != null ? `${r.total}%` : "—"}</TableCell>
                        <TableCell>{r.grade ? <Badge variant="secondary">{r.grade}</Badge> : "—"}</TableCell>
                        <TableCell className="hidden sm:table-cell text-xs text-muted-foreground">{r.remark ?? "—"}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
                {rc.teacherRemark && <p className="mt-3 text-sm rounded-lg bg-secondary/50 p-3"><b>Class teacher:</b> {rc.teacherRemark}</p>}
                {rc.headRemark && <p className="mt-2 text-sm rounded-lg bg-secondary/50 p-3"><b>Head of school:</b> {rc.headRemark}</p>}
              </CardContent>
            </Card>
          )) : <EmptyState icon={<GraduationCap className="size-8" />} title="No reports yet" hint="Terminal reports appear here once the school publishes them." />}
        </TabsContent>

        {child.transport && (
          <TabsContent value="transport" className="mt-3 space-y-4">
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Bus className="size-4" /> {child.transport.route}</CardTitle></CardHeader>
              <CardContent>
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                  <MiniStat label="Vehicle" value={child.transport.vehicleReg} />
                  <MiniStat label="Driver" value={child.transport.driverName} />
                  <MiniStat label="Term fare" value={fmtCedi(child.transport.farePerTerm, { compact: true })} />
                  <MiniStat label="Per trip" value={fmtCedi(child.transport.farePerTrip, { compact: true })} />
                </div>
                <div className="text-xs font-medium uppercase tracking-wide text-muted-foreground mb-2">Recent boardings</div>
                {child.transport.recentBoardings.length ? (
                  <div className="space-y-1.5">
                    {child.transport.recentBoardings.map((b, i) => (
                      <div key={i} className="flex items-center justify-between rounded-lg border px-3 py-2">
                        <div className="text-sm">{fmtDate(b.date)}</div>
                        <div className="flex items-center gap-2">
                          <Badge variant="outline">{DIR_LABEL[b.direction] ?? b.direction}</Badge>
                          <span className="text-sm tabular-nums">{fmtCedi(b.fare)}</span>
                          <Badge className={b.paid >= b.fare ? "bg-emerald-600" : "bg-amber-600"}>{b.paid >= b.fare ? <CheckCircle2 className="size-3" /> : <AlertTriangle className="size-3" />} {b.paid >= b.fare ? "paid" : "owing"}</Badge>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : <EmptyState title="No boardings recorded yet" />}
                {child.transport.driverPhone && <p className="mt-3 text-xs text-muted-foreground">Driver contact: <b>{child.transport.driverPhone}</b></p>}
              </CardContent>
            </Card>
          </TabsContent>
        )}

        <TabsContent value="messages" className="mt-3">
          <Card>
            <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><MessageSquareText className="size-4" /> Messages from the school</CardTitle></CardHeader>
            <CardContent>
              {data.messages.length ? (
                <div className="space-y-2 max-h-96 overflow-y-auto nice-scroll">
                  {data.messages.map((m) => (
                    <div key={m.id} className="rounded-lg border p-3">
                      <div className="flex items-center justify-between text-xs text-muted-foreground">
                        <span>to {m.recipientName} · {m.recipientPhone}</span>
                        <span>{fmtDate(m.sentAt)}</span>
                      </div>
                      <p className="text-sm mt-1">{m.body}</p>
                    </div>
                  ))}
                </div>
              ) : <EmptyState title="No messages yet" hint="SMS alerts about attendance, fees and results will appear here." />}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}

function StatTile({ icon, label, value, sub, tone }: { icon: React.ReactNode; label: string; value: React.ReactNode; sub?: string; tone?: "good" | "danger" }) {
  return (
    <div className={`rounded-xl border bg-card p-4 ${tone === "danger" ? "border-destructive/30" : ""}`}>
      <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</span>{icon}</div>
      <div className={`mt-1.5 text-xl font-bold tabular-nums ${tone === "danger" ? "text-destructive" : tone === "good" ? "text-emerald-600 dark:text-emerald-400" : ""}`}>{value}</div>
      <div className="text-[11px] text-muted-foreground truncate">{sub}</div>
    </div>
  );
}

function MiniStat({ label, value, tone }: { label: string; value: React.ReactNode; tone?: "good" | "warn" | "bad" }) {
  return (
    <div className="rounded-lg border p-2.5 text-center">
      <div className="text-[10px] uppercase tracking-wide text-muted-foreground">{label}</div>
      <div className={`text-lg font-bold tabular-nums ${tone === "good" ? "text-emerald-600 dark:text-emerald-400" : tone === "bad" ? "text-destructive" : tone === "warn" ? "text-amber-600" : ""}`}>{value}</div>
    </div>
  );
}

"use client";
import { useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet } from "@/lib/api";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, initials, avatarColor } from "@/lib/format";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { UserRound, Receipt, Megaphone, Clock, BadgeCheck, CalendarDays, Wallet, TrendingDown } from "lucide-react";

type Allowance = { name: string; amountCents: number };
type Payslip = {
  id: string; period: string; runStatus: string; paidAt: string | null; reference: string | null; status: string;
  base: number; allowances: Allowance[]; gross: number; ssnit: number; paye: number;
  otherDeductions: Allowance[]; deductions: number; net: number; workingDays: number | null; daysPresent: number | null;
};
type StaffData = {
  role: string; school: { name: string; motto: string | null; town: string; region: string; phone: string | null } | null;
  me: {
    fullName: string; title: string | null; position: string | null; category: string; department: string | null;
    staffCode: string | null; qualification: string | null; employmentType: string; hiredAt: string | null;
    phone: string | null; email: string | null; ssnitNumber: string | null; accountNumber: string | null; momoNumber: string | null;
    subjects: string[]; classrooms: string[];
    timetable: { day: number; start: number; end: number; subject: string; classroom: string }[];
    payslips: Payslip[];
  };
  announcements: { id: string; title: string; body: string; postedByName: string; postedAt: string; priority: string }[];
};

const DAYS = ["", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
const fmtTime = (m: number) => `${String(Math.floor(m / 60)).padStart(2, "0")}:${String(m % 60).padStart(2, "0")}`;
const EMPLOYMENT_LABEL: Record<string, string> = { FULL_TIME: "Full-time", PART_TIME: "Part-time", CONTRACT: "Contract", CASUAL: "Casual" };

export function StaffPortal() {
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<StaffData | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [payslip, setPayslip] = useState<Payslip | null>(null);

  useEffect(() => {
    if (!currentSchoolId) return;
    apiGet<StaffData>(`/api/school/${currentSchoolId}/ops/portal?view=staff`)
      .then(setData)
      .catch((e) => setErr(e.message));
  }, [currentSchoolId]);

  if (err) return <div className="text-destructive text-sm">{err}</div>;
  if (!data) return <LoadingBlock label="Loading your staff portal…" />;
  const me = data.me;
  const latestPaid = me.payslips.find((p) => p.status === "PAID");
  const ytdNet = me.payslips.reduce((s, p) => s + p.net, 0);

  return (
    <div>
      <ModuleHeader
        title={`My Staff Portal — ${me.fullName}`}
        description={`${me.position ?? me.title ?? "Staff"} · ${EMPLOYMENT_LABEL[me.employmentType] ?? me.employmentType}${me.department ? ` · ${me.department}` : ""} — ${data.school?.name ?? ""}`}
        actions={me.staffCode ? <Badge variant="outline" className="font-mono">{me.staffCode}</Badge> : null}
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <Tile icon={<Wallet className="size-4" />} label="Latest net pay" value={latestPaid ? fmtCedi(latestPaid.net, { compact: true }) : "—"} sub={latestPaid ? latestPaid.period : "no payroll yet"} tone="good" />
        <Tile icon={<Receipt className="size-4" />} label="Payslips" value={me.payslips.length} sub="last 12 months" />
        <Tile icon={<TrendingDown className="size-4" />} label="Deductions (latest)" value={me.payslips[0] ? fmtCedi(me.payslips[0].deductions, { compact: true }) : "—"} sub="SSNIT · PAYE · others" />
        <Tile icon={<Clock className="size-4" />} label="Weekly periods" value={me.timetable.length} sub={me.classrooms.length ? me.classrooms.join(", ") : me.subjects.length ? me.subjects.join(", ") : me.timetable.length ? "teaching this term" : "no classes assigned"} />
      </div>

      <Tabs defaultValue="profile">
        <TabsList>
          <TabsTrigger value="profile">My profile</TabsTrigger>
          <TabsTrigger value="payslips">Payslips ({me.payslips.length})</TabsTrigger>
          {me.timetable.length > 0 && <TabsTrigger value="routine">My routine</TabsTrigger>}
          <TabsTrigger value="notices">Notices {data.announcements?.length ? <span className="ml-1 inline-flex items-center justify-center rounded-full bg-primary px-1.5 min-w-4 h-4 text-[10px] font-bold text-primary-foreground">{data.announcements.length}</span> : null}</TabsTrigger>
        </TabsList>

        <TabsContent value="profile" className="mt-3 grid lg:grid-cols-3 gap-4">
          <Card className="lg:col-span-2">
            <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><UserRound className="size-4" /> Staff record</CardTitle></CardHeader>
            <CardContent className="grid sm:grid-cols-2 gap-3">
              {[
                { k: "Full name", v: me.fullName },
                { k: "Position", v: me.position ?? me.title ?? "—" },
                { k: "Category", v: me.category === "TEACHING" ? "Teaching staff" : me.category === "NON_TEACHING" ? "Non-teaching staff" : "Administration" },
                { k: "Department", v: me.department ?? "—" },
                { k: "Qualification", v: me.qualification ?? "—" },
                { k: "Employment", v: EMPLOYMENT_LABEL[me.employmentType] ?? me.employmentType },
                { k: "Joined", v: me.hiredAt ? fmtDate(me.hiredAt) : "—" },
                { k: "Phone", v: me.phone ?? "—" },
                { k: "Email", v: me.email ?? "—" },
                { k: "SSNIT no.", v: me.ssnitNumber ?? "—" },
                { k: "Bank / momo account", v: me.accountNumber ?? "—" },
                { k: "MoMo number", v: me.momoNumber ?? "—" },
              ].map((f) => (
                <div key={f.k} className="rounded-lg border p-2.5">
                  <div className="text-[10px] uppercase tracking-wide text-muted-foreground">{f.k}</div>
                  <div className="text-sm font-medium truncate">{f.v}</div>
                </div>
              ))}
            </CardContent>
          </Card>

          <div className="space-y-4">
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><BadgeCheck className="size-4" /> Responsibilities</CardTitle></CardHeader>
              <CardContent className="space-y-2">
                {me.classrooms.length ? (
                  <div><div className="text-xs text-muted-foreground mb-1">Class teacher of</div><div className="flex flex-wrap gap-1.5">{me.classrooms.map((c) => <Badge key={c} variant="secondary">{c}</Badge>)}</div></div>
                ) : null}
                {me.subjects.length ? (
                  <div><div className="text-xs text-muted-foreground mb-1">Subjects</div><div className="flex flex-wrap gap-1.5">{me.subjects.map((s) => <Badge key={s} variant="outline">{s}</Badge>)}</div></div>
                ) : null}
                {!me.classrooms.length && !me.subjects.length && <EmptyState title="No classroom duties" hint="Your assignments will be listed here." />}
              </CardContent>
            </Card>
            <Card className="bg-secondary/30">
              <CardContent className="pt-4 text-xs text-muted-foreground leading-relaxed">
                <b className="text-foreground text-sm">Data privacy</b>
                <p className="mt-1">Sensitive numbers (SSNIT, bank, MoMo) are masked on screen. Contact the bursar&apos;s office to update your records.</p>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="payslips" className="mt-3">
          <Card>
            <CardHeader className="pb-2"><CardTitle className="text-base">My payslips — net {fmtCedi(ytdNet, { compact: true })} across {me.payslips.length} run{me.payslips.length === 1 ? "" : "s"}</CardTitle></CardHeader>
            <CardContent>
              {me.payslips.length ? (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Period</TableHead>
                      <TableHead>Reference</TableHead>
                      <TableHead>Gross</TableHead>
                      <TableHead>Deductions</TableHead>
                      <TableHead>Net pay</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead />
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {[...me.payslips].sort((a, b) => b.period.localeCompare(a.period)).map((p) => (
                      <TableRow key={p.id}>
                        <TableCell className="font-medium">{p.period}</TableCell>
                        <TableCell className="text-xs font-mono text-muted-foreground">{p.reference ?? "—"}</TableCell>
                        <TableCell className="tabular-nums">{fmtCedi(p.gross)}</TableCell>
                        <TableCell className="tabular-nums text-destructive">−{fmtCedi(p.deductions)}</TableCell>
                        <TableCell className="tabular-nums font-semibold">{fmtCedi(p.net)}</TableCell>
                        <TableCell><Badge className={p.status === "PAID" ? "bg-emerald-600" : "bg-amber-600"}>{p.status === "PAID" ? `paid${p.paidAt ? ` ${fmtDate(p.paidAt)}` : ""}` : "pending"}</Badge></TableCell>
                        <TableCell><Button size="sm" variant="ghost" className="h-7 text-xs" onClick={() => setPayslip(p)}>View</Button></TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              ) : <EmptyState icon={<Receipt className="size-8" />} title="No payslips yet" hint="Your payslips appear here after the first payroll run." />}
            </CardContent>
          </Card>
        </TabsContent>

        {me.timetable.length > 0 && (
          <TabsContent value="routine" className="mt-3">
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><CalendarDays className="size-4" /> My weekly routine</CardTitle></CardHeader>
              <CardContent className="grid md:grid-cols-5 gap-2">
                {[1, 2, 3, 4, 5].map((d) => {
                  const slots = me.timetable.filter((t) => t.day === d).sort((a, b) => a.start - b.start);
                  return (
                    <div key={d} className="rounded-lg border p-2.5 min-h-28">
                      <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1.5">{DAYS[d]}</div>
                      <div className="space-y-1">
                        {slots.map((s, i) => (
                          <div key={i} className="rounded-md bg-secondary/60 px-2 py-1.5">
                            <div className="text-xs font-medium truncate">{s.subject}</div>
                            <div className="text-[10px] text-muted-foreground">{fmtTime(s.start)}–{fmtTime(s.end)} · {s.classroom}</div>
                          </div>
                        ))}
                        {!slots.length && <div className="text-xs text-muted-foreground">—</div>}
                      </div>
                    </div>
                  );
                })}
              </CardContent>
            </Card>
          </TabsContent>
        )}

        <TabsContent value="notices" className="mt-3">
          <Card>
            <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Megaphone className="size-4" /> Staff notices</CardTitle></CardHeader>
            <CardContent className="space-y-2.5">
              {data.announcements.length ? data.announcements.map((a) => (
                <div key={a.id} className="rounded-lg border p-3">
                  <div className="flex items-center gap-1.5">
                    {a.priority === "HIGH" && <Badge className="bg-destructive text-[9px]">urgent</Badge>}
                    <span className="text-sm font-medium">{a.title}</span>
                  </div>
                  <p className="text-xs text-muted-foreground mt-1">{a.body}</p>
                  <div className="text-[10px] text-muted-foreground mt-1">{a.postedByName} · {fmtDate(a.postedAt)}</div>
                </div>
              )) : <EmptyState title="No notices" />}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* payslip detail */}
      <Dialog open={!!payslip} onOpenChange={(o) => !o && setPayslip(null)}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><Receipt className="size-4.5" /> Payslip — {payslip?.period}</DialogTitle>
            <DialogDescription>
              {payslip?.reference ?? "—"} · {data.school?.name}{payslip?.workingDays ? ` · ${payslip.daysPresent ?? "?"}/${payslip.workingDays} days` : ""}
            </DialogDescription>
          </DialogHeader>
          {payslip && (
            <div className="space-y-3 text-sm">
              <div className="rounded-lg border p-3 space-y-1.5">
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Earnings</div>
                <Row k="Basic salary" v={fmtCedi(payslip.base)} />
                {payslip.allowances.map((a) => <Row key={a.name} k={a.name} v={fmtCedi(a.amountCents)} />)}
                <div className="border-t pt-1.5"><Row k="Gross" v={fmtCedi(payslip.gross)} bold /></div>
              </div>
              <div className="rounded-lg border p-3 space-y-1.5">
                <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Deductions</div>
                <Row k="SSNIT Tier 1 (5%)" v={`−${fmtCedi(payslip.ssnit)}`} />
                <Row k="PAYE tax" v={`−${fmtCedi(payslip.paye)}`} />
                {payslip.otherDeductions.map((a) => <Row key={a.name} k={a.name} v={`−${fmtCedi(a.amountCents)}`} />)}
                <div className="border-t pt-1.5"><Row k="Total deductions" v={`−${fmtCedi(payslip.deductions)}`} bold /></div>
              </div>
              <div className="rounded-lg bg-primary/10 border border-primary/20 p-3 flex items-center justify-between">
                <span className="font-medium">Net pay</span>
                <span className="text-lg font-bold tabular-nums text-primary">{fmtCedi(payslip.net)}</span>
              </div>
              <div className="flex items-center justify-center">
                <span className="flex size-9 items-center justify-center rounded-full text-white text-xs font-bold" style={{ background: avatarColor(me.fullName) }}>{initials(me.fullName)}</span>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}

function Row({ k, v, bold }: { k: string; v: string; bold?: boolean }) {
  return (
    <div className={`flex items-center justify-between ${bold ? "font-semibold" : ""}`}>
      <span className={bold ? "" : "text-muted-foreground"}>{k}</span>
      <span className="tabular-nums">{v}</span>
    </div>
  );
}

function Tile({ icon, label, value, sub, tone }: { icon: React.ReactNode; label: string; value: React.ReactNode; sub?: string; tone?: "good" }) {
  return (
    <div className="rounded-xl border bg-card p-4">
      <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</span>{icon}</div>
      <div className={`mt-1.5 text-xl font-bold tabular-nums ${tone === "good" ? "text-emerald-600 dark:text-emerald-400" : ""}`}>{value}</div>
      <div className="text-[11px] text-muted-foreground truncate">{sub}</div>
    </div>
  );
}

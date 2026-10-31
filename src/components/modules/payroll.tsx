"use client";
import { useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Separator } from "@/components/ui/separator";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { StatCard, ModuleHeader, LoadingBlock, StatusBadge, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { Calculator, Users, Plus, CheckCheck, Banknote, Printer, Pencil, Trash2, ShieldCheck, FileText, AlertTriangle, Settings2, Landmark } from "lucide-react";
import { LogoMark, KenteStrip } from "@/components/brand";

type StaffRow = { id: string; fullName: string; role: string; title: string | null; phone: string | null; salary: number; employmentType: string; ssnitNumber: string | null; momoNumber: string | null; hiredAt: string | null; active: boolean };
type Run = { id: string; period: string; status: string; payslipCount: number; gross: number; deductions: number; net: number; employerSsnit: number; approvedByName: string | null; paidAt: string | null };
type Slip = {
  id: string; staffId: string; staff: string; role: string; title: string | null; ssnitNumber: string | null; momoNumber: string | null;
  base: number; allowances: { name: string; amountCents: number }[]; gross: number; ssnitEmployee: number; ssnitEmployer: number;
  paye: number; otherDeductions: { name: string; amountCents: number }[]; totalDeductions: number; net: number; status: string;
  ssnit: { tier1Employee: number; tier1Employer: number; tier2: number; getfund: number; nhil: number };
};
type Rates = { ssnitTier1Employee: number; ssnitTier1Employer: number; ssnitTier2: number; getfund: number; nhil: number };
type PayrollData = {
  school: { name: string; motto: string | null; town: string | null; region: string | null; phone: string | null; email: string | null } | null;
  rates: Rates;
  staff: StaffRow[];
  runs: Run[];
  selectedRun: { id: string; period: string; status: string } | null;
  payslips: Slip[];
  currentPeriod: string;
};

export function Payroll() {
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<PayrollData | null>(null);
  const [runId, setRunId] = useState<string | null>(null);
  const [staffDialog, setStaffDialog] = useState<StaffRow | null>(null);
  const [slipFor, setSlipFor] = useState<Slip | null>(null);
  const [newPeriod, setNewPeriod] = useState("");
  const [busy, setBusy] = useState(false);

  const load = (rid?: string) => {
    if (!currentSchoolId) return;
    apiGet<PayrollData>(`/api/school/${currentSchoolId}/ops/payroll${rid ? `?runId=${rid}` : ""}`)
      .then((d) => { setData(d); setRunId(d.selectedRun?.id ?? null); })
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(() => { load(); }, [currentSchoolId]);

  async function act(action: string, extra: any = {}) {
    if (!currentSchoolId) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/payroll`, { action, ...extra });
      toast({ title: "Done" });
      load(runId ?? undefined);
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  if (!data) return <LoadingBlock label="Loading payroll…" />;
  const run = data.runs.find((r) => r.id === runId) ?? null;

  return (
    <div>
      <ModuleHeader
        title="HR & Payroll"
        description="Monthly payroll with Ghana-compliant maths — SSNIT Tier 1 (5% employee / 13% employer), Tier 2 (5% employer) and progressive PAYE — statutory rates configurable, payslips print-ready."
        actions={
          <div className="flex gap-2 flex-wrap items-center">
            <Input type="month" value={newPeriod} onChange={(e) => setNewPeriod(e.target.value)} className="w-40" />
            <Button size="sm" className="gap-1.5" disabled={!newPeriod || busy} onClick={() => act("create-run", { period: newPeriod })}><Plus className="size-4" /> Run payroll</Button>
          </div>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<Users className="size-4" />} label="Salaried staff" value={data.staff.filter((s) => s.active && s.salary > 0).length} sub={`${data.staff.filter((s) => !s.active).length} inactive`} />
        <StatCard icon={<Calculator className="size-4" />} label="Latest net pay" value={run ? fmtCedi(run.net, { compact: true }) : "—"} tone="primary" sub={run ? `${run.period} · ${run.payslipCount} payslips` : "no runs yet"} />
        <StatCard icon={<ShieldCheck className="size-4" />} label="Employer SSNIT" value={run ? fmtCedi(run.employerSsnit, { compact: true }) : "—"} tone="gold" sub={`T1 ${data.rates.ssnitTier1Employer}% + T2 ${data.rates.ssnitTier2}%`} />
        <StatCard icon={<Banknote className="size-4" />} label="Total cost (month)" value={run ? fmtCedi(run.net + run.employerSsnit, { compact: true }) : "—"} sub="net + employer SSNIT" />
      </div>

      <Tabs defaultValue="runs" className="mt-5">
        <TabsList className="flex-wrap">
          <TabsTrigger value="runs">Payroll runs</TabsTrigger>
          <TabsTrigger value="staff">Staff & salaries ({data.staff.length})</TabsTrigger>
          <TabsTrigger value="ssnit">SSNIT reports</TabsTrigger>
          <TabsTrigger value="statutory">Statutory settings</TabsTrigger>
          <TabsTrigger value="ghana">Ghana payroll maths</TabsTrigger>
        </TabsList>

        {/* ── RUNS ── */}
        <TabsContent value="runs">
          <div className="flex flex-wrap gap-2 mb-3">
            {data.runs.map((r) => (
              <button key={r.id}
                onClick={() => { setRunId(r.id); load(r.id); }}
                className={`rounded-lg border px-3 py-1.5 text-sm flex items-center gap-2 transition-colors ${r.id === runId ? "bg-primary text-primary-foreground border-primary" : "hover:bg-secondary"}`}>
                {r.period} <StatusBadge status={r.status === "APPROVED" ? "APPROVED" : r.status === "PAID" ? "PAID" : "PENDING"} />
              </button>
            ))}
            {data.runs.length === 0 && <span className="text-sm text-muted-foreground">No payroll runs yet — pick a month and run payroll.</span>}
          </div>

          {run && (
            <Card>
              <CardHeader className="pb-3 flex-row items-center justify-between flex-wrap gap-2 space-y-0">
                <CardTitle className="text-base">{run.period} payroll — {run.payslipCount} payslips</CardTitle>
                <div className="flex gap-2">
                  {run.status === "DRAFT" && <>
                    <Button size="sm" variant="outline" className="h-8 gap-1 text-destructive" disabled={busy} onClick={() => act("delete-run", { runId: run.id })}><Trash2 className="size-3.5" /> Discard</Button>
                    <Button size="sm" className="h-8 gap-1" disabled={busy} onClick={() => act("approve-run", { runId: run.id })}><CheckCheck className="size-3.5" /> Approve</Button>
                  </>}
                  {run.status === "APPROVED" && <Button size="sm" className="h-8 gap-1" disabled={busy} onClick={() => act("pay-run", { runId: run.id })}><Banknote className="size-3.5" /> Pay via MoMo</Button>}
                  {run.status === "PAID" && <Badge className="gap-1 bg-emerald-600"><CheckCheck className="size-3.5" /> Paid {fmtDate(run.paidAt)}</Badge>}
                </div>
              </CardHeader>
              <CardContent className="p-0">
                <div className="overflow-x-auto nice-scroll max-h-[520px]">
                  <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                      <tr className="text-left border-b">
                        <th className="px-3 py-2.5 font-medium">Staff</th>
                        <th className="px-3 py-2.5 font-medium text-right hidden sm:table-cell">Basic</th>
                        <th className="px-3 py-2.5 font-medium text-right hidden md:table-cell">Allowances</th>
                        <th className="px-3 py-2.5 font-medium text-right">Gross</th>
                        <th className="px-3 py-2.5 font-medium text-right hidden lg:table-cell">SSNIT 5%</th>
                        <th className="px-3 py-2.5 font-medium text-right hidden lg:table-cell">PAYE</th>
                        <th className="px-3 py-2.5 font-medium text-right">Net pay</th>
                        <th className="px-3 py-2.5"></th>
                      </tr>
                    </thead>
                    <tbody>
                      {data.payslips.map((p) => (
                        <tr key={p.id} className="border-b last:border-0 hover:bg-secondary/30">
                          <td className="px-3 py-2.5">
                            <div className="font-medium">{p.staff}</div>
                            <div className="text-[11px] text-muted-foreground">{p.title ?? p.role.charAt(0) + p.role.slice(1).toLowerCase()}</div>
                          </td>
                          <td className="px-3 py-2.5 text-right tabular-nums hidden sm:table-cell">{fmtCedi(p.base)}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums hidden md:table-cell">{p.allowances.length ? fmtCedi(p.allowances.reduce((a, x) => a + x.amountCents, 0)) : "—"}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums font-medium">{fmtCedi(p.gross)}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums text-muted-foreground hidden lg:table-cell">{fmtCedi(p.ssnitEmployee)}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums text-muted-foreground hidden lg:table-cell">{fmtCedi(p.paye)}</td>
                          <td className="px-3 py-2.5 text-right tabular-nums font-semibold text-primary">{fmtCedi(p.net)}</td>
                          <td className="px-3 py-2.5 text-right">
                            <Button size="sm" variant="ghost" className="h-7 gap-1" onClick={() => setSlipFor(p)}><FileText className="size-3.5" /> Payslip</Button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
                <div className="flex flex-wrap justify-end gap-6 px-4 py-3 border-t bg-secondary/30 text-sm">
                  <span>Gross <b className="tabular-nums">{fmtCedi(run.gross)}</b></span>
                  <span>Deductions <b className="tabular-nums text-destructive">{fmtCedi(run.deductions)}</b></span>
                  <span>Employer SSNIT <b className="tabular-nums text-[#a97c0b]">{fmtCedi(run.employerSsnit)}</b></span>
                  <span>Net <b className="tabular-nums text-primary">{fmtCedi(run.net)}</b></span>
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        {/* ── STAFF ── */}
        <TabsContent value="staff">
          <Card>
            <CardContent className="p-0">
              <div className="overflow-x-auto nice-scroll max-h-[560px]">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                    <tr className="text-left border-b">
                      <th className="px-3 py-2.5 font-medium">Staff</th>
                      <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Role</th>
                      <th className="px-3 py-2.5 font-medium text-right">Basic salary</th>
                      <th className="px-3 py-2.5 font-medium hidden md:table-cell">Type</th>
                      <th className="px-3 py-2.5 font-medium hidden lg:table-cell">MoMo</th>
                      <th className="px-3 py-2.5 font-medium hidden xl:table-cell">SSNIT No.</th>
                      <th className="px-3 py-2.5"></th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.staff.map((s) => (
                      <tr key={s.id} className={`border-b last:border-0 hover:bg-secondary/30 ${!s.active ? "opacity-50" : ""}`}>
                        <td className="px-3 py-2.5">
                          <div className="font-medium">{s.fullName}</div>
                          <div className="text-[11px] text-muted-foreground sm:hidden">{s.role}</div>
                        </td>
                        <td className="px-3 py-2.5 hidden sm:table-cell text-muted-foreground">{s.title ?? s.role}</td>
                        <td className="px-3 py-2.5 text-right tabular-nums font-medium">{s.salary ? fmtCedi(s.salary) : <span className="text-muted-foreground font-normal">not set</span>}</td>
                        <td className="px-3 py-2.5 hidden md:table-cell"><Badge variant="outline" className="text-[10px]">{s.employmentType.replace("_", " ")}</Badge></td>
                        <td className="px-3 py-2.5 hidden lg:table-cell text-muted-foreground font-mono text-xs">{s.momoNumber ?? "—"}</td>
                        <td className="px-3 py-2.5 hidden xl:table-cell text-muted-foreground font-mono text-xs">{s.ssnitNumber ?? "—"}</td>
                        <td className="px-3 py-2.5 text-right">
                          <Button size="sm" variant="ghost" className="h-7 gap-1" onClick={() => setStaffDialog(s)}><Pencil className="size-3.5" /> Edit</Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── SSNIT REPORTS (Tier 1 / Tier 2 statutory schedules) ── */}
        <TabsContent value="ssnit">
          {!run ? <Card><CardContent><EmptyState icon={<Landmark className="size-5" />} title="No payroll run selected" hint="Run payroll first — Tier 1 and Tier 2 schedules are generated per run." /></CardContent></Card> : (
            <div className="space-y-4">
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-base flex items-center gap-2"><Landmark className="size-4 text-primary" /> SSNIT Tier 1 schedule — {run.period}</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                  <table className="w-full text-sm">
                    <thead className="bg-secondary/70">
                      <tr className="text-left border-b">
                        <th className="px-3 py-2 font-medium">Staff</th>
                        <th className="px-3 py-2 font-medium hidden sm:table-cell">SSNIT No.</th>
                        <th className="px-3 py-2 font-medium text-right">Basic</th>
                        <th className="px-3 py-2 font-medium text-right">Employee {data.rates.ssnitTier1Employee}%</th>
                        <th className="px-3 py-2 font-medium text-right">Employer {data.rates.ssnitTier1Employer}%</th>
                        <th className="px-3 py-2 font-medium text-right">Total Tier 1</th>
                      </tr>
                    </thead>
                    <tbody>
                      {data.payslips.map((p) => (
                        <tr key={p.id} className="border-b last:border-0">
                          <td className="px-3 py-2 font-medium">{p.staff}</td>
                          <td className="px-3 py-2 hidden sm:table-cell font-mono text-xs text-muted-foreground">{p.ssnitNumber ?? "—"}</td>
                          <td className="px-3 py-2 text-right tabular-nums">{fmtCedi(p.base)}</td>
                          <td className="px-3 py-2 text-right tabular-nums">{fmtCedi(p.ssnit.tier1Employee)}</td>
                          <td className="px-3 py-2 text-right tabular-nums">{fmtCedi(p.ssnit.tier1Employer)}</td>
                          <td className="px-3 py-2 text-right tabular-nums font-semibold">{fmtCedi(p.ssnit.tier1Employee + p.ssnit.tier1Employer)}</td>
                        </tr>
                      ))}
                      <tr className="border-t bg-secondary/40">
                        <td className="px-3 py-2 font-semibold" colSpan={3}>Totals</td>
                        <td className="px-3 py-2 text-right tabular-nums font-semibold">{fmtCedi(data.payslips.reduce((a, p) => a + p.ssnit.tier1Employee, 0))}</td>
                        <td className="px-3 py-2 text-right tabular-nums font-semibold">{fmtCedi(data.payslips.reduce((a, p) => a + p.ssnit.tier1Employer, 0))}</td>
                        <td className="px-3 py-2 text-right tabular-nums font-bold">{fmtCedi(data.payslips.reduce((a, p) => a + p.ssnit.tier1Employee + p.ssnit.tier1Employer, 0))}</td>
                      </tr>
                    </tbody>
                  </table>
                </CardContent>
              </Card>
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-base flex items-center gap-2"><ShieldCheck className="size-4 text-primary" /> SSNIT Tier 2 schedule (occupational pension) — {run.period}</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                  <table className="w-full text-sm">
                    <thead className="bg-secondary/70">
                      <tr className="text-left border-b">
                        <th className="px-3 py-2 font-medium">Staff</th>
                        <th className="px-3 py-2 font-medium hidden sm:table-cell">SSNIT No.</th>
                        <th className="px-3 py-2 font-medium text-right">Basic</th>
                        <th className="px-3 py-2 font-medium text-right">Tier 2 {data.rates.ssnitTier2}%</th>
                        <th className="px-3 py-2 font-medium text-right hidden md:table-cell">GETFund {data.rates.getfund}%</th>
                        <th className="px-3 py-2 font-medium text-right hidden md:table-cell">NHIL {data.rates.nhil}%</th>
                      </tr>
                    </thead>
                    <tbody>
                      {data.payslips.map((p) => (
                        <tr key={p.id} className="border-b last:border-0">
                          <td className="px-3 py-2 font-medium">{p.staff}</td>
                          <td className="px-3 py-2 hidden sm:table-cell font-mono text-xs text-muted-foreground">{p.ssnitNumber ?? "—"}</td>
                          <td className="px-3 py-2 text-right tabular-nums">{fmtCedi(p.base)}</td>
                          <td className="px-3 py-2 text-right tabular-nums font-semibold">{fmtCedi(p.ssnit.tier2)}</td>
                          <td className="px-3 py-2 text-right tabular-nums hidden md:table-cell">{fmtCedi(p.ssnit.getfund)}</td>
                          <td className="px-3 py-2 text-right tabular-nums hidden md:table-cell">{fmtCedi(p.ssnit.nhil)}</td>
                        </tr>
                      ))}
                      <tr className="border-t bg-secondary/40">
                        <td className="px-3 py-2 font-semibold" colSpan={3}>Totals</td>
                        <td className="px-3 py-2 text-right tabular-nums font-bold">{fmtCedi(data.payslips.reduce((a, p) => a + p.ssnit.tier2, 0))}</td>
                        <td className="px-3 py-2 text-right tabular-nums font-semibold hidden md:table-cell">{fmtCedi(data.payslips.reduce((a, p) => a + p.ssnit.getfund, 0))}</td>
                        <td className="px-3 py-2 text-right tabular-nums font-semibold hidden md:table-cell">{fmtCedi(data.payslips.reduce((a, p) => a + p.ssnit.nhil, 0))}</td>
                      </tr>
                    </tbody>
                  </table>
                </CardContent>
              </Card>
            </div>
          )}
        </TabsContent>

        {/* ── STATUTORY SETTINGS ── */}
        <TabsContent value="statutory">
          <StatutorySettings rates={data.rates} onSaved={() => load(runId ?? undefined)} />
        </TabsContent>

        {/* ── GHANA MATHS ── */}
        <TabsContent value="ghana">
          <div className="grid lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><ShieldCheck className="size-4 text-primary" /> SSNIT pension (Tiers 1 & 2)</CardTitle></CardHeader>
              <CardContent className="space-y-2 text-sm">
                <div className="flex justify-between"><span>Tier 1 — employee share</span><b>{data.rates.ssnitTier1Employee}% of basic</b></div>
                <div className="flex justify-between"><span>Tier 1 — employer share</span><b>{data.rates.ssnitTier1Employer}% of basic</b></div>
                <div className="flex justify-between"><span>Tier 2 — occupational</span><b>{data.rates.ssnitTier2}% of basic</b></div>
                <div className="flex justify-between"><span>GETFund / NHIL levies</span><b>{data.rates.getfund}% + {data.rates.nhil}%</b></div>
                <Separator />
                <p className="text-xs text-muted-foreground pt-1">Tier 1 contributions are capped at the SSNIT maximum pensionable earnings (GH₵35,000/month). All rates are configurable in Statutory settings and applied to every new payroll run.</p>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Calculator className="size-4 text-primary" /> PAYE progressive bands (monthly)</CardTitle></CardHeader>
              <CardContent className="space-y-1.5 text-sm">
                {[
                  ["First GH₵512", "0%"], ["Next GH₵184", "5%"], ["Next GH₵218", "10%"],
                  ["Next GH₵3,040", "17.5%"], ["Next GH₵1,621", "25%"], ["Next GH₵1,466", "30%"], ["Above GH₵7,041", "35%"],
                ].map(([band, rate]) => (
                  <div key={band} className="flex justify-between"><span className="text-muted-foreground">{band}</span><b>{rate}</b></div>
                ))}
                <Separator />
                <p className="text-xs text-muted-foreground">Taxable income = gross − employee SSNIT. A teacher on GH₵1,600 basic + GH₵100 allowance pays GH₵160 SSNIT and GH₵152 PAYE — net GH₵1,488.</p>
              </CardContent>
            </Card>
            <Card className="lg:col-span-2 bg-amber-50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800">
              <CardContent className="pt-4 flex gap-3">
                <AlertTriangle className="size-5 text-amber-600 shrink-0 mt-0.5" />
                <p className="text-sm text-muted-foreground leading-relaxed">
                  <b className="text-foreground">Compliance note.</b> PAYE bands follow the 2024+ Ghana Revenue Authority schedule. Tier 2 contributions must be remitted to a licensed Tier 2 pension manager, and Tier 3 (provident fund) is voluntary.
                  Confirm current rates with your accountant before filing — Nyansapo keeps the maths, you keep the relationship with GRA and SSNIT.
                </p>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>

      <StaffDialog staff={staffDialog} onOpenChange={(o) => !o && setStaffDialog(null)} onDone={() => { setStaffDialog(null); load(runId ?? undefined); }} />
      <PayslipDialog slip={slipFor} period={run?.period ?? ""} school={data.school} rates={data.rates} onOpenChange={(o) => !o && setSlipFor(null)} />
    </div>
  );
}

/* ── STATUTORY SETTINGS (iedupro parity) ────────────────────── */
function StatutorySettings({ rates, onSaved }: { rates: Rates; onSaved: () => void }) {
  const { currentSchoolId } = useStore();
  const [r, setR] = useState(rates);
  const [busy, setBusy] = useState(false);
  useEffect(() => setR(rates), [rates]);

  const fields: { key: keyof Rates; label: string; hint: string }[] = [
    { key: "ssnitTier1Employee", label: "SSNIT Tier 1 — employee share", hint: "Deducted from the employee's basic salary" },
    { key: "ssnitTier1Employer", label: "SSNIT Tier 1 — employer share", hint: "Paid by the school on top of salary (13.5% per Act 766)" },
    { key: "ssnitTier2", label: "SSNIT Tier 2 — occupational pension", hint: "Remitted to your licensed Tier 2 manager" },
    { key: "getfund", label: "GETFund levy", hint: "Ghana Education Trust Fund levy on basic salary" },
    { key: "nhil", label: "NHIL", hint: "National Health Insurance Levy on basic salary" },
  ];

  async function save() {
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/payroll`, { action: "save-rates", ...r });
      toast({ title: "Statutory rates saved", description: "New rates apply to all future payroll runs — existing payslips keep the rates they were computed with." });
      onSaved();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <div className="grid lg:grid-cols-3 gap-4">
      <Card className="lg:col-span-2">
        <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Settings2 className="size-4 text-primary" /> Statutory deduction rates (%)</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          {fields.map((f) => (
            <div key={f.key} className="grid sm:grid-cols-[1fr_120px] gap-2 items-center">
              <div>
                <Label>{f.label}</Label>
                <p className="text-[11px] text-muted-foreground">{f.hint}</p>
              </div>
              <Input type="number" step="0.1" min="0" max="100" value={r[f.key]} onChange={(e) => setR({ ...r, [f.key]: parseFloat(e.target.value) || 0 })} className="text-right tabular-nums" />
            </div>
          ))}
          <div className="flex gap-2 justify-end pt-2">
            <Button variant="outline" onClick={() => setR(rates)}>Reset</Button>
            <Button onClick={save} disabled={busy} className="gap-1.5"><CheckCheck className="size-4" /> {busy ? "Saving…" : "Save rates"}</Button>
          </div>
        </CardContent>
      </Card>
      <Card className="bg-amber-50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800 h-fit">
        <CardContent className="pt-4 space-y-2 text-sm">
          <div className="flex gap-2"><AlertTriangle className="size-4 text-amber-600 shrink-0 mt-0.5" /><b>Before you change rates</b></div>
          <p className="text-muted-foreground text-xs leading-relaxed">
            Changes affect <b>new</b> payroll calculations only — existing payslips are never rewritten. Verify current rates with the Ghana Revenue Authority and SSNIT before filing. The defaults follow the National Pensions Act 2008 (Act 766) as amended.
          </p>
        </CardContent>
      </Card>
    </div>
  );
}

function StaffDialog({ staff, onOpenChange, onDone }: { staff: StaffRow | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [salary, setSalary] = useState("");
  const [employmentType, setEmploymentType] = useState("FULL_TIME");
  const [ssnit, setSsnit] = useState("");
  const [momo, setMomo] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (staff) {
      setSalary(staff.salary ? (staff.salary / 100).toFixed(0) : "");
      setEmploymentType(staff.employmentType); setSsnit(staff.ssnitNumber ?? ""); setMomo(staff.momoNumber ?? "");
    }
  }, [staff]);

  async function submit() {
    if (!staff) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/payroll`, {
        action: "save-staff", staffId: staff.id,
        salaryCents: Math.round(parseFloat(salary || "0") * 100),
        employmentType, ssnitNumber: ssnit, momoNumber: momo,
      });
      toast({ title: "Salary details saved" });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!staff} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Payroll setup — {staff?.fullName}</DialogTitle>
          <DialogDescription>{staff?.title ?? staff?.role}</DialogDescription>
        </DialogHeader>
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1.5">
            <Label>Basic salary (GH₵/month)</Label>
            <Input type="number" value={salary} onChange={(e) => setSalary(e.target.value)} placeholder="1600" />
          </div>
          <div className="space-y-1.5">
            <Label>Employment type</Label>
            <Select value={employmentType} onValueChange={setEmploymentType}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="FULL_TIME">Full time</SelectItem><SelectItem value="PART_TIME">Part time</SelectItem>
                <SelectItem value="CONTRACT">Contract</SelectItem><SelectItem value="CASUAL">Casual</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>SSNIT number</Label>
            <Input value={ssnit} onChange={(e) => setSsnit(e.target.value)} placeholder="SSNIT-12345678" />
          </div>
          <div className="space-y-1.5">
            <Label>MoMo payout number</Label>
            <Input value={momo} onChange={(e) => setMomo(e.target.value)} placeholder="+233 24 000 0000" />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : "Save"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── PROFESSIONAL PRINTABLE PAYSLIP ─────────────────────────── */
function PayslipDialog({ slip, period, school, rates, onOpenChange }: { slip: Slip | null; period: string; school: PayrollData["school"]; rates: Rates; onOpenChange: (o: boolean) => void }) {
  if (!slip) return null;
  return (
    <Dialog open={!!slip} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-[560px] max-h-[92vh] overflow-y-auto nice-scroll">
        <DialogHeader className="no-print">
          <DialogTitle className="text-base">Payslip — {period}</DialogTitle>
          <DialogDescription>{slip.staff}</DialogDescription>
        </DialogHeader>
        <div className="print-area">
          <div className="print-sheet a5-sheet mx-auto bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden">
            <KenteStrip />
            <div className="px-6 py-5">
              <div className="flex items-start justify-between gap-3">
                <div className="flex items-center gap-2.5">
                  <LogoMark size={44} />
                  <div className="leading-tight">
                    <div className="font-bold text-[15px] tracking-tight">{school?.name ?? "—"}</div>
                    <div className="text-[10px] text-muted-foreground">{[school?.town, school?.region].filter(Boolean).join(", ")}</div>
                    {school?.motto && <div className="text-[10px] italic text-muted-foreground">{school.motto}</div>}
                  </div>
                </div>
                <div className="text-right">
                  <div className="inline-block rounded-md bg-primary/10 text-primary px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest">Payslip</div>
                  <div className="font-semibold text-sm mt-1.5">{period}</div>
                  <div className="text-[10px] text-muted-foreground">{slip.status === "PAID" ? "PAID" : "DRAFT — not yet paid"}</div>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-2 mt-4 text-[12px]">
                <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[9px] uppercase tracking-wider text-muted-foreground">Staff</div><div className="font-semibold">{slip.staff}</div></div>
                <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[9px] uppercase tracking-wider text-muted-foreground">Role / SSNIT No.</div><div className="font-semibold">{slip.title ?? slip.role} {slip.ssnitNumber ? `· ${slip.ssnitNumber}` : ""}</div></div>
              </div>

              <table className="w-full text-[12.5px] mt-4">
                <tbody>
                  <tr className="border-b border-dashed"><td className="py-1.5 font-semibold uppercase text-[9px] tracking-wider text-muted-foreground" colSpan={2}>Earnings</td></tr>
                  <tr className="border-b border-dashed"><td className="py-1.5">Basic salary</td><td className="py-1.5 text-right tabular-nums">{fmtCedi(slip.base)}</td></tr>
                  {slip.allowances.map((a) => (
                    <tr key={a.name} className="border-b border-dashed"><td className="py-1.5">{a.name}</td><td className="py-1.5 text-right tabular-nums">{fmtCedi(a.amountCents)}</td></tr>
                  ))}
                  <tr className="border-b"><td className="py-1.5 font-semibold">Gross earnings</td><td className="py-1.5 text-right font-semibold tabular-nums">{fmtCedi(slip.gross)}</td></tr>

                  <tr className="border-b border-dashed"><td className="py-1.5 pt-3 font-semibold uppercase text-[9px] tracking-wider text-muted-foreground" colSpan={2}>Deductions</td></tr>
                  <tr className="border-b border-dashed"><td className="py-1.5">SSNIT Tier 1 employee ({rates.ssnitTier1Employee}%) + Tier 2 ({rates.ssnitTier2}%)</td><td className="py-1.5 text-right tabular-nums">{fmtCedi(slip.ssnitEmployee)}</td></tr>
                  <tr className="border-b border-dashed"><td className="py-1.5">PAYE income tax</td><td className="py-1.5 text-right tabular-nums">{fmtCedi(slip.paye)}</td></tr>
                  {slip.otherDeductions.map((d) => (
                    <tr key={d.name} className="border-b border-dashed"><td className="py-1.5">{d.name}</td><td className="py-1.5 text-right tabular-nums">{fmtCedi(d.amountCents)}</td></tr>
                  ))}
                  <tr className="border-b"><td className="py-1.5 font-semibold">Total deductions</td><td className="py-1.5 text-right font-semibold tabular-nums text-destructive">{fmtCedi(slip.totalDeductions)}</td></tr>
                </tbody>
              </table>

              <div className="mt-3 rounded-lg bg-primary/10 border border-primary/20 px-3 py-2.5 flex items-center justify-between">
                <span className="font-bold text-sm">NET PAY</span>
                <span className="font-bold text-xl text-primary tabular-nums">{fmtCedi(slip.net)}</span>
              </div>
              <div className="mt-1.5 text-[10px] text-muted-foreground">Paid to {slip.momoNumber ?? "MoMo number not set"} · employer contributions (not deducted): {fmtCedi(slip.ssnitEmployer)}</div>

              <div className="grid grid-cols-2 gap-8 pt-5 text-[10px] text-muted-foreground">
                <div className="border-t border-border pt-1">Employee signature</div>
                <div className="border-t border-border pt-1 text-right">Bursar / Head signature</div>
              </div>
            </div>
            <KenteStrip />
          </div>
        </div>
        <DialogFooter className="sm:justify-center gap-2 no-print">
          <Button variant="outline" size="sm" onClick={() => window.print()}><Printer className="size-4" /> Print payslip</Button>
          <Button size="sm" onClick={() => onOpenChange(false)}>Done</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

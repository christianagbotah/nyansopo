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
import { Calculator, Users, Plus, CheckCheck, Banknote, Printer, Pencil, Trash2, ShieldCheck, FileText, AlertTriangle } from "lucide-react";

type StaffRow = { id: string; fullName: string; role: string; title: string | null; phone: string | null; salary: number; employmentType: string; ssnitNumber: string | null; momoNumber: string | null; hiredAt: string | null; active: boolean };
type Run = { id: string; period: string; status: string; payslipCount: number; gross: number; deductions: number; net: number; employerSsnit: number; approvedByName: string | null; paidAt: string | null };
type Slip = { id: string; staffId: string; staff: string; role: string; momoNumber: string | null; base: number; allowances: { name: string; amountCents: number }[]; gross: number; ssnitEmployee: number; ssnitEmployer: number; paye: number; otherDeductions: { name: string; amountCents: number }[]; totalDeductions: number; net: number; status: string };
type PayrollData = {
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
        description="Monthly payroll with Ghana-compliant maths — SSNIT Tier 1 (5% employee / 13% employer), Tier 2 (5% employer) and progressive PAYE — paid out by MoMo."
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
        <StatCard icon={<ShieldCheck className="size-4" />} label="Employer SSNIT" value={run ? fmtCedi(run.employerSsnit, { compact: true }) : "—"} tone="gold" sub="Tier 1 13% + Tier 2 5%" />
        <StatCard icon={<Banknote className="size-4" />} label="Total cost (month)" value={run ? fmtCedi(run.net + run.employerSsnit, { compact: true }) : "—"} sub="net + employer SSNIT" />
      </div>

      <Tabs defaultValue="runs" className="mt-5">
        <TabsList>
          <TabsTrigger value="runs">Payroll runs</TabsTrigger>
          <TabsTrigger value="staff">Staff & salaries ({data.staff.length})</TabsTrigger>
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
                            <div className="text-[11px] text-muted-foreground">{p.role.charAt(0) + p.role.slice(1).toLowerCase()}</div>
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

        {/* ── GHANA MATHS ── */}
        <TabsContent value="ghana">
          <div className="grid lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><ShieldCheck className="size-4 text-primary" /> SSNIT pension (Tiers 1 & 2)</CardTitle></CardHeader>
              <CardContent className="space-y-2 text-sm">
                <div className="flex justify-between"><span>Tier 1 — employee share</span><b>5% of basic</b></div>
                <div className="flex justify-between"><span>Tier 1 — employer share</span><b>13% of basic</b></div>
                <div className="flex justify-between"><span>Tier 2 — employer occupational</span><b>5% of basic</b></div>
                <Separator />
                <div className="flex justify-between"><span>Total employer cost on top of salary</span><b className="text-primary">18%</b></div>
                <p className="text-xs text-muted-foreground pt-1">Tier 1 contributions are capped at the SSNIT maximum pensionable earnings (GH₵35,000/month); Tier 2 is uncapped. Both are computed automatically per payslip.</p>
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
                <p className="text-xs text-muted-foreground">Taxable income = gross − employee SSNIT (5%). A teacher on GH₵1,600 basic + GH₵100 allowance pays GH₵80 SSNIT and GH₵156.53 PAYE — net GH₵1,463.47.</p>
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
      <PayslipDialog slip={slipFor} period={run?.period ?? ""} onOpenChange={(o) => !o && setSlipFor(null)} />
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

function PayslipLine({ label, value, bold, danger, gold }: { label: string; value: string; bold?: boolean; danger?: boolean; gold?: boolean }) {
  return (
    <div className={`flex justify-between text-sm ${bold ? "font-semibold" : ""}`}>
      <span className={bold ? "" : "text-muted-foreground"}>{label}</span>
      <span className={`tabular-nums ${danger ? "text-destructive" : gold ? "text-[#a97c0b]" : ""}`}>{value}</span>
    </div>
  );
}

function PayslipDialog({ slip, period, onOpenChange }: { slip: Slip | null; period: string; onOpenChange: (o: boolean) => void }) {
  if (!slip) return null;
  return (
    <Dialog open={!!slip} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader className="text-center">
          <DialogTitle>Payslip — {period}</DialogTitle>
          <DialogDescription>{slip.staff} · {slip.role.charAt(0) + slip.role.slice(1).toLowerCase()}</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="rounded-lg border p-3 space-y-1.5">
            <div className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Earnings</div>
            <PayslipLine label="Basic salary" value={fmtCedi(slip.base)} />
            {slip.allowances.map((a) => <PayslipLine key={a.name} label={a.name} value={fmtCedi(a.amountCents)} />)}
            <Separator />
            <PayslipLine label="Gross" value={fmtCedi(slip.gross)} bold />
          </div>
          <div className="rounded-lg border p-3 space-y-1.5">
            <div className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Deductions</div>
            <PayslipLine label="SSNIT Tier 1 (5%)" value={fmtCedi(slip.ssnitEmployee)} danger />
            <PayslipLine label="PAYE income tax" value={fmtCedi(slip.paye)} danger />
            {slip.otherDeductions.map((d) => <PayslipLine key={d.name} label={d.name} value={fmtCedi(d.amountCents)} danger />)}
            <Separator />
            <PayslipLine label="Total deductions" value={fmtCedi(slip.totalDeductions)} bold danger />
          </div>
          <div className="rounded-lg bg-primary/10 border border-primary/20 p-3 space-y-1">
            <PayslipLine label="NET PAY" value={fmtCedi(slip.net)} bold />
            <p className="text-xs text-muted-foreground">Paid to {slip.momoNumber ?? "MoMo number not set"}</p>
          </div>
          <div className="text-xs text-muted-foreground text-right">
            Employer contributions (not deducted): SSNIT {fmtCedi(slip.ssnitEmployer)}
          </div>
        </div>
        <DialogFooter className="sm:justify-center gap-2">
          <Button variant="outline" size="sm" onClick={() => window.print()}><Printer className="size-4" /> Print payslip</Button>
          <Button size="sm" onClick={() => onOpenChange(false)}>Done</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

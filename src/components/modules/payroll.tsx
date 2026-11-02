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
import { StatCard, ModuleHeader, LoadingBlock, StatusBadge, EmptyState, FilterSearch, FormGrid, FormField } from "@/components/ui-helpers";
import { fmtCedi, fmtDate } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { Checkbox } from "@/components/ui/checkbox";
import { Calculator, Users, Plus, CheckCheck, Banknote, Printer, Pencil, Trash2, ShieldCheck, FileText, AlertTriangle, Settings2, Landmark, GraduationCap, Download, Wand2 } from "lucide-react";
import { LogoMark, KenteStrip } from "@/components/brand";

type StaffRow = { id: string; fullName: string; role: string; title: string | null; phone: string | null; email?: string | null; category?: string; position?: string | null; qualification?: string | null; department?: string | null; staffCode?: string | null; tier2Provider?: string | null; tier2MemberId?: string | null; accountNumber?: string | null; bankName?: string | null; tin?: string | null; salary: number; employmentType: string; ssnitNumber: string | null; momoNumber: string | null; hiredAt: string | null; active: boolean; sex?: string | null; birthday?: string | null; ghanaCardId?: string | null; address?: string | null; subjects?: string | null };
type Run = { id: string; period: string; status: string; payslipCount: number; gross: number; deductions: number; net: number; employerSsnit: number; approvedByName: string | null; paidAt: string | null };
type Slip = {
  id: string; staffId: string; staff: string; role: string; title: string | null; ssnitNumber: string | null; momoNumber: string | null;
  position?: string | null; staffCode?: string | null; accountNumber?: string | null; bankName?: string | null; tier2Provider?: string | null;
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
  providers: { id: string; name: string; code: string; contactPhone: string | null; active: boolean }[];
};

export function Payroll() {
  const { currentSchoolId, navTarget } = useStore();
  const [data, setData] = useState<PayrollData | null>(null);
  const [runId, setRunId] = useState<string | null>(null);
  const [staffDialog, setStaffDialog] = useState<StaffRow | null>(null);
  const [newStaffOpen, setNewStaffOpen] = useState(false);
  const [staffCat, setStaffCat] = useState<string>("ALL");
  const [staffSearch, setStaffSearch] = useState("");
  const [slipFor, setSlipFor] = useState<Slip | null>(null);
  const [newPeriod, setNewPeriod] = useState("");
  const [busy, setBusy] = useState(false);
  const [tab, setTab] = useState("runs");

  /* iEduPro nested nav: deep-link to a payroll tab (runs / staff / ssnit / statutory / ghana) */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab && ["runs", "staff", "ssnit", "statutory", "ghana"].includes(navTarget.tab)) setTab(navTarget.tab);
  }

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
        <StatCard icon={<GraduationCap className="size-4" />} label="Teaching staff" value={data.staff.filter((s) => s.active && (s.category ?? "TEACHING") === "TEACHING").length} sub={`${data.staff.filter((s) => s.active && s.category === "NON_TEACHING").length} non-teaching · ${data.staff.filter((s) => s.active && s.category === "ADMINISTRATION").length} admin`} />
        <StatCard icon={<Calculator className="size-4" />} label="Latest net pay" value={run ? fmtCedi(run.net, { compact: true }) : "—"} tone="primary" sub={run ? `${run.period} · ${run.payslipCount} payslips` : "no runs yet"} />
        <StatCard icon={<ShieldCheck className="size-4" />} label="Employer SSNIT" value={run ? fmtCedi(run.employerSsnit, { compact: true }) : "—"} tone="gold" sub={`T1 ${data.rates.ssnitTier1Employer}% + T2 ${data.rates.ssnitTier2}%`} />
        <StatCard icon={<Banknote className="size-4" />} label="Total cost (month)" value={run ? fmtCedi(run.net + run.employerSsnit, { compact: true }) : "—"} sub="net + employer SSNIT" />
      </div>

      <Tabs value={tab} onValueChange={setTab} className="mt-5">
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
            <CardContent className="pt-4 pb-0">
              <div className="flex flex-wrap items-center justify-between gap-2 mb-3">
                <div className="flex flex-wrap gap-1.5 items-center">
                  {["ALL", "TEACHING", "NON_TEACHING", "ADMINISTRATION"].map((c) => (
                    <button key={c}
                      onClick={() => setStaffCat(c)}
                      className={`rounded-full px-3 py-1 text-xs font-medium transition-colors ${staffCat === c ? "bg-primary text-primary-foreground" : "bg-secondary text-muted-foreground hover:text-foreground"}`}>
                      {c === "ALL" ? "All staff" : c === "TEACHING" ? "👨‍🏫 Teaching" : c === "NON_TEACHING" ? "👷 Non-teaching" : "🏢 Administration"}
                      <span className="ml-1 opacity-70">{c === "ALL" ? data.staff.filter((s) => s.active).length : data.staff.filter((s) => s.active && (s.category ?? (s.role === "TEACHER" || s.role === "HEADTEACHER" ? "TEACHING" : "ADMINISTRATION")) === c).length}</span>
                    </button>
                  ))}
                  <FilterSearch value={staffSearch} onChange={setStaffSearch} placeholder="Name, position, MoMo…" />
                </div>
                <Button size="sm" className="gap-1.5" onClick={() => setNewStaffOpen(true)}><Plus className="size-4" /> Add staff</Button>
              </div>
              <div className="overflow-x-auto nice-scroll max-h-[520px] -mx-6">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                    <tr className="text-left border-b">
                      <th className="px-3 py-2.5 font-medium">Staff</th>
                      <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Position</th>
                      <th className="px-3 py-2.5 font-medium text-right">Basic salary</th>
                      <th className="px-3 py-2.5 font-medium hidden md:table-cell">Type</th>
                      <th className="px-3 py-2.5 font-medium hidden lg:table-cell">MoMo</th>
                      <th className="px-3 py-2.5 font-medium hidden xl:table-cell">SSNIT No.</th>
                      <th className="px-3 py-2.5"></th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.staff
                      .filter((s) => staffCat === "ALL" || (s.category ?? (s.role === "TEACHER" || s.role === "HEADTEACHER" ? "TEACHING" : "ADMINISTRATION")) === staffCat)
                      .filter((s) => !staffSearch.trim() || `${s.fullName} ${s.position ?? ""} ${s.momoNumber ?? ""} ${s.staffCode ?? ""}`.toLowerCase().includes(staffSearch.toLowerCase()))
                      .map((s) => (
                      <tr key={s.id} className={`border-b last:border-0 hover:bg-secondary/30 ${!s.active ? "opacity-50" : ""}`}>
                        <td className="px-3 py-2.5">
                          <div className="font-medium">{s.fullName}</div>
                          <div className="text-[11px] text-muted-foreground flex items-center gap-1.5">
                            {s.staffCode && <span className="font-mono">{s.staffCode}</span>}
                            <span className="sm:hidden">{s.position ?? s.title ?? s.role}</span>
                          </div>
                        </td>
                        <td className="px-3 py-2.5 hidden sm:table-cell">
                          <div className="text-[13px]">{s.position ?? s.title ?? s.role}</div>
                          {s.department && <div className="text-[11px] text-muted-foreground">{s.department}</div>}
                        </td>
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

        {/* ── SSNIT REPORTS (Tier 1 / Tier 2 statutory schedules — CI3 printable parity) ── */}
        <TabsContent value="ssnit">
          <SsnitReportsTab defaultPeriod={run?.period ?? data.runs[0]?.period ?? data.currentPeriod} />
        </TabsContent>

        {/* ── STATUTORY SETTINGS ── */}
        <TabsContent value="statutory">
          <StatutorySettings rates={data.rates} providers={data.providers} onSaved={() => load(runId ?? undefined)} />
        </TabsContent>

        {/* ── GHANA MATHS ── */}
        <TabsContent value="ghana">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
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
      <NewStaffDialog open={newStaffOpen} onOpenChange={(o) => !o && setNewStaffOpen(false)} onDone={() => { setNewStaffOpen(false); load(runId ?? undefined); }} providers={data.providers} />
      <PayslipDialog slip={slipFor} period={run?.period ?? ""} school={data.school} rates={data.rates} onOpenChange={(o) => !o && setSlipFor(null)} />
    </div>
  );
}

/* ── STATUTORY SETTINGS (iedupro parity) ────────────────────── */
function StatutorySettings({ rates, providers, onSaved }: { rates: Rates; providers: ProviderRow[]; onSaved: () => void }) {
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
    <div className="space-y-4">
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
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
      <ProvidersPanel providers={providers} onSaved={onSaved} />
    </div>
  );
}

/* ── licensed Tier-2 / Tier-3 pension managers directory (iedupro parity) ── */
type ProviderRow = { id: string; name: string; code: string; contactPhone: string | null; active: boolean };

function ProvidersPanel({ providers, onSaved }: { providers: ProviderRow[]; onSaved: () => void }) {
  const { currentSchoolId } = useStore();
  const [name, setName] = useState("");
  const [code, setCode] = useState("");
  const [phone, setPhone] = useState("");
  const [busy, setBusy] = useState(false);

  async function add() {
    if (!name.trim() || !code.trim()) { toast({ title: "Provider name and code are required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/payroll`, { action: "save-provider", name, code, contactPhone: phone });
      toast({ title: "Provider added" }); setName(""); setCode(""); setPhone(""); onSaved();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  async function toggle(id: string) {
    await apiPost(`/api/school/${currentSchoolId}/ops/payroll`, { action: "toggle-provider", id });
    onSaved();
  }

  async function remove(id: string) {
    await apiPost(`/api/school/${currentSchoolId}/ops/payroll`, { action: "delete-provider", id }).catch(() => null);
    toast({ title: "Provider removed" });
    onSaved();
  }

  return (
    <Card>
      <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><ShieldCheck className="size-4 text-primary" /> Pension providers — licensed Tier 2 / Tier 3 managers</CardTitle></CardHeader>
      <CardContent className="space-y-4">
        <p className="text-xs text-muted-foreground">Your licensed occupational-pension and provident-fund managers, to whom Tier 2 (5%) and voluntary Tier 3 contributions are remitted.</p>
        <div className="overflow-x-auto nice-scroll">
          <table className="w-full text-sm">
            <thead className="bg-secondary/70">
              <tr className="text-left border-b text-xs uppercase tracking-wide text-muted-foreground">
                <th className="px-3 py-2.5 font-medium">Provider</th>
                <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Code</th>
                <th className="px-3 py-2.5 font-medium hidden md:table-cell">Contact</th>
                <th className="px-3 py-2.5 font-medium">Status</th>
                <th className="px-3 py-2.5 text-right font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              {providers.length === 0 && <tr><td colSpan={5} className="px-3 py-3 text-xs text-muted-foreground">No providers registered yet — add the managers your school remits to.</td></tr>}
              {providers.map((p) => (
                <tr key={p.id} className={`border-b last:border-0 hover:bg-secondary/30 ${p.active ? "" : "opacity-60"}`}>
                  <td className="px-3 py-2.5 font-medium">{p.name}</td>
                  <td className="px-3 py-2.5 hidden sm:table-cell font-mono text-xs">{p.code}</td>
                  <td className="px-3 py-2.5 hidden md:table-cell text-muted-foreground text-xs">{p.contactPhone ?? "—"}</td>
                  <td className="px-3 py-2.5"><Badge className={p.active ? "bg-emerald-600" : ""} variant={p.active ? "default" : "secondary"}>{p.active ? "Active" : "Inactive"}</Badge></td>
                  <td className="px-3 py-2.5 text-right">
                    <Button size="sm" variant="ghost" className="h-7" onClick={() => toggle(p.id)}>{p.active ? "Deactivate" : "Reactivate"}</Button>
                    <Button size="sm" variant="ghost" className="h-7 text-destructive" onClick={() => remove(p.id)}>Delete</Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="grid sm:grid-cols-[1fr_130px_150px_auto] gap-2 items-end border-t pt-4">
          <div className="space-y-1"><Label className="text-xs">Provider name</Label><Input className="h-8" value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Petra Trust" /></div>
          <div className="space-y-1"><Label className="text-xs">Code</Label><Input className="h-8" value={code} onChange={(e) => setCode(e.target.value)} placeholder="e.g. PTRA" /></div>
          <div className="space-y-1"><Label className="text-xs">Contact phone</Label><Input className="h-8" value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="+233 …" /></div>
          <Button size="sm" className="gap-1.5" onClick={add} disabled={busy}><Plus className="size-4" /> Add</Button>
        </div>
      </CardContent>
    </Card>
  );
}

function StaffDialog({ staff, onOpenChange, onDone }: { staff: StaffRow | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [f, setF] = useState({
    fullName: "", salary: "", employmentType: "FULL_TIME", ssnit: "", momo: "",
    phone: "", email: "", position: "", qualification: "", department: "", category: "TEACHING",
    tier2Provider: "", accountNumber: "", bankName: "", tin: "",
    sex: "", birthday: "", ghanaCard: "", address: "", subjects: "", tier2MemberId: "", hiredAt: "",
  });
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (staff) {
      setF({
        fullName: staff.fullName,
        salary: staff.salary ? String(Math.round(staff.salary / 100)) : "",
        employmentType: staff.employmentType,
        ssnit: staff.ssnitNumber ?? "", momo: staff.momoNumber ?? "",
        phone: staff.phone ?? "", email: staff.email ?? "",
        position: staff.position ?? staff.title ?? "", qualification: staff.qualification ?? "",
        department: staff.department ?? "",
        category: staff.category ?? (staff.role === "TEACHER" || staff.role === "HEADTEACHER" ? "TEACHING" : "ADMINISTRATION"),
        tier2Provider: staff.tier2Provider ?? "", accountNumber: staff.accountNumber ?? "",
        bankName: staff.bankName ?? "", tin: staff.tin ?? "",
        sex: staff.sex ?? "", birthday: staff.birthday ? staff.birthday.slice(0, 10) : "",
        ghanaCard: staff.ghanaCardId ?? "", address: staff.address ?? "", subjects: staff.subjects ?? "",
        tier2MemberId: staff.tier2MemberId ?? "", hiredAt: staff.hiredAt ? staff.hiredAt.slice(0, 10) : "",
      });
    }
  }, [staff]);

  async function submit() {
    if (!staff) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/payroll`, {
        action: "save-staff", staffId: staff.id,
        fullName: f.fullName, salaryCents: Math.round(parseFloat(f.salary || "0") * 100),
        employmentType: f.employmentType, ssnitNumber: f.ssnit, momoNumber: f.momo,
        phone: f.phone, email: f.email, position: f.position, qualification: f.qualification,
        department: f.department, category: f.category, tier2Provider: f.tier2Provider,
        accountNumber: f.accountNumber, bankName: f.bankName, tin: f.tin,
        sex: f.sex || null, birthday: f.birthday || null, ghanaCardId: f.ghanaCard,
        address: f.address, subjects: f.subjects, tier2MemberId: f.tier2MemberId, hiredAt: f.hiredAt || null,
      });
      toast({ title: "Staff record saved" });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  const POSITIONS = ["Teacher", "Headteacher", "Bursar", "Accountant", "Driver", "Cleaner", "Cook", "Security Guard", "Librarian", "IT Technician", "Janitor", "Gardener", "Maintenance Worker", "Kitchen Assistant", "Office Assistant", "Lab Technician", "Receptionist", "Nurse", "Messenger"];

  return (
    <Dialog open={!!staff} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[92vh] overflow-y-auto nice-scroll sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Staff record — {staff?.staffCode ?? staff?.fullName}</DialogTitle>
          <DialogDescription>{staff?.position ?? staff?.title ?? staff?.role}</DialogDescription>
        </DialogHeader>
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-1.5 sm:col-span-2">
            <Label>Full name</Label>
            <Input value={f.fullName} onChange={(e) => setF({ ...f, fullName: e.target.value })} />
          </div>
          <div className="space-y-1.5">
            <Label>Category</Label>
            <Select value={f.category} onValueChange={(v) => setF({ ...f, category: v })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="TEACHING">Teaching</SelectItem>
                <SelectItem value="NON_TEACHING">Non-teaching</SelectItem>
                <SelectItem value="ADMINISTRATION">Administration</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Position</Label>
            <Input list="staff-positions" value={f.position} onChange={(e) => setF({ ...f, position: e.target.value })} placeholder="Teacher / Driver / Cook…" />
            <datalist id="staff-positions">{POSITIONS.map((p) => <option key={p} value={p} />)}</datalist>
          </div>
          <div className="space-y-1.5">
            <Label>Qualification</Label>
            <Input value={f.qualification} onChange={(e) => setF({ ...f, qualification: e.target.value })} placeholder="B.Ed Basic Education" />
          </div>
          <div className="space-y-1.5">
            <Label>Department</Label>
            <Input value={f.department} onChange={(e) => setF({ ...f, department: e.target.value })} placeholder="Kitchen / Transport / Security" />
          </div>
          <div className="space-y-1.5">
            <Label>Phone</Label>
            <Input value={f.phone} onChange={(e) => setF({ ...f, phone: e.target.value })} />
          </div>
          <div className="space-y-1.5">
            <Label>Email</Label>
            <Input type="email" value={f.email} onChange={(e) => setF({ ...f, email: e.target.value })} />
          </div>
          <div className="space-y-1.5">
            <Label>Sex</Label>
            <Select value={f.sex} onValueChange={(v) => setF({ ...f, sex: v })}>
              <SelectTrigger><SelectValue placeholder="Choose" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="M">Male</SelectItem>
                <SelectItem value="F">Female</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Birthday</Label>
            <Input type="date" value={f.birthday} onChange={(e) => setF({ ...f, birthday: e.target.value })} />
          </div>
          <div className="space-y-1.5">
            <Label>Ghana Card</Label>
            <Input value={f.ghanaCard} onChange={(e) => setF({ ...f, ghanaCard: e.target.value })} placeholder="GHA-123456789-0" className="font-mono" />
          </div>
          <div className="space-y-1.5 sm:col-span-2">
            <Label>Residential address</Label>
            <Input value={f.address} onChange={(e) => setF({ ...f, address: e.target.value })} placeholder="House No. 21, Ogbojo" />
          </div>
          <div className="space-y-1.5 sm:col-span-2">
            <Label>Subjects</Label>
            <Input value={f.subjects} onChange={(e) => setF({ ...f, subjects: e.target.value })} placeholder="Mathematics, Science" />
          </div>
          <Separator className="sm:col-span-2 my-1" />
          <div className="text-[10px] uppercase tracking-wider text-muted-foreground font-semibold sm:col-span-2">Payroll</div>
          <div className="space-y-1.5">
            <Label>Basic salary (GH₵/month)</Label>
            <Input type="number" value={f.salary} onChange={(e) => setF({ ...f, salary: e.target.value })} placeholder="1600" />
          </div>
          <div className="space-y-1.5">
            <Label>Employment type</Label>
            <Select value={f.employmentType} onValueChange={(v) => setF({ ...f, employmentType: v })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="FULL_TIME">Full time</SelectItem><SelectItem value="PART_TIME">Part time</SelectItem>
                <SelectItem value="CONTRACT">Contract</SelectItem><SelectItem value="CASUAL">Casual</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Hired on</Label>
            <Input type="date" value={f.hiredAt} onChange={(e) => setF({ ...f, hiredAt: e.target.value })} />
          </div>
          <Separator className="sm:col-span-2 my-1" />
          <div className="text-[10px] uppercase tracking-wider text-muted-foreground font-semibold sm:col-span-2">Ghana statutory & payout</div>
          <div className="space-y-1.5">
            <Label>SSNIT number</Label>
            <Input value={f.ssnit} onChange={(e) => setF({ ...f, ssnit: e.target.value })} placeholder="C0123456789" />
          </div>
          <div className="space-y-1.5">
            <Label>TIN</Label>
            <Input value={f.tin} onChange={(e) => setF({ ...f, tin: e.target.value })} placeholder="C0012345678" />
          </div>
          <div className="space-y-1.5">
            <Label>Tier 2 provider</Label>
            <Input value={f.tier2Provider} onChange={(e) => setF({ ...f, tier2Provider: e.target.value })} placeholder="Petra Trust" />
          </div>
          <div className="space-y-1.5">
            <Label>Tier 2 member ID</Label>
            <Input value={f.tier2MemberId} onChange={(e) => setF({ ...f, tier2MemberId: e.target.value })} placeholder="PT-998877" className="font-mono" />
          </div>
          <div className="space-y-1.5">
            <Label>Bank name</Label>
            <Input value={f.bankName} onChange={(e) => setF({ ...f, bankName: e.target.value })} placeholder="GCB Bank" />
          </div>
          <div className="space-y-1.5">
            <Label>Account number</Label>
            <Input value={f.accountNumber} onChange={(e) => setF({ ...f, accountNumber: e.target.value })} />
          </div>
          <div className="space-y-1.5">
            <Label>MoMo payout number</Label>
            <Input value={f.momo} onChange={(e) => setF({ ...f, momo: e.target.value })} placeholder="024 000 0000" />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || !f.fullName.trim()}>{busy ? "Saving…" : "Save record"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── NEW STAFF (teacher / non-teaching / admin) — legacy iEduPro "add teacher" parity.
   One dialog, grouped fieldsets: identity → contact & government → banking →
   employment → optional linked login. Staff codes: TCR / NTS / ADM prefix +
   5-digit zero-padded sequence (max existing suffix + 1, generated on the server),
   with a live ✓ available / ✗ taken check and a Generate button. ───────────── */
const LOGIN_ROLE_OPTIONS = ["TEACHER", "NON_TEACHING", "LIBRARIAN", "CASHIER", "BUS_CONDUCTOR", "ACCOUNTANT", "SCHOOL_ADMIN"];
const NEW_STAFF_POSITIONS = {
  TEACHING: ["Teacher", "Headteacher", "Deputy Headteacher"],
  NON_TEACHING: ["Driver", "Cleaner", "Cook", "Security Guard", "Librarian", "IT Technician", "Janitor", "Gardener", "Maintenance Worker", "Kitchen Assistant", "Office Assistant", "Lab Technician", "Receptionist", "Nurse", "Messenger"],
  ADMINISTRATION: ["Bursar", "Accountant", "Administrator", "Receptionist"],
} as Record<string, string[]>;
const ALL_NEW_STAFF_POSITIONS = [...new Set(Object.values(NEW_STAFF_POSITIONS).flat())];

const blankNewStaff = () => ({
  fullName: "", sex: "", birthday: "", staffCode: "",
  category: "TEACHING", position: "Teacher", phone: "", email: "",
  address: "", ghanaCard: "", ssnit: "", tin: "", tier2Provider: "", tier2MemberId: "",
  bankName: "", accountNumber: "", momo: "",
  qualification: "", department: "", subjects: "",
  salary: "", employmentType: "FULL_TIME", hiredAt: "",
  createLogin: false, loginPassword: "", loginRole: "TEACHER",
});

function NewStaffDialog({ open, onOpenChange, onDone, providers }: { open: boolean; onOpenChange: (o: boolean) => void; onDone: () => void; providers: ProviderRow[] }) {
  const { currentSchoolId } = useStore();
  const [f, setF] = useState(blankNewStaff);
  const [codeState, setCodeState] = useState<"idle" | "checking" | "ok" | "taken" | "error">("idle");
  const [busy, setBusy] = useState(false);

  /* auto-generation: fetch the next code for the category on open + category change */
  useEffect(() => {
    if (!open || !currentSchoolId) return;
    apiGet<{ code: string }>(`/api/school/${currentSchoolId}/ops/payroll?action=next-staff-code&category=${f.category}`)
      .then((r) => { if (r.code) setF((p) => ({ ...p, staffCode: r.code })); })
      .catch(() => null);
  }, [open, f.category, currentSchoolId]);

  /* live availability check as the user types (debounced; unique per school) */
  useEffect(() => {
    const code = f.staffCode.trim();
    if (!open || !code) return;
    const t = setTimeout(() => {
      setCodeState("checking");
      apiGet<{ taken: boolean }>(`/api/school/${currentSchoolId}/ops/payroll?action=check-staff-code&code=${encodeURIComponent(code)}&category=${f.category}`)
        .then((r) => setCodeState(r.taken ? "taken" : "ok"))
        .catch(() => setCodeState("error"));
    }, 400);
    return () => clearTimeout(t);
  }, [f.staffCode, f.category, open, currentSchoolId]);

  async function generate() {
    if (!currentSchoolId) return;
    try {
      const r = await apiGet<{ code: string }>(`/api/school/${currentSchoolId}/ops/payroll?action=next-staff-code&category=${f.category}`);
      if (r.code) { setF((p) => ({ ...p, staffCode: r.code })); setCodeState("checking"); }
    } catch (e: any) { toast({ title: "Could not generate a code", description: e.message, variant: "destructive" }); }
  }

  function setCategory(v: string) {
    setF((p) => ({
      ...p, category: v,
      position: (NEW_STAFF_POSITIONS[v] ?? [])[0] ?? p.position,
      loginRole: v === "NON_TEACHING" ? "NON_TEACHING" : v === "ADMINISTRATION" ? "SCHOOL_ADMIN" : "TEACHER",
    }));
  }

  async function submit() {
    if (!f.fullName.trim()) { toast({ title: "Full name is required", variant: "destructive" }); return; }
    if (!f.staffCode.trim()) { toast({ title: "Staff code is required", description: "Tap Generate for the next code.", variant: "destructive" }); return; }
    if (codeState === "taken") { toast({ title: "Staff code already taken", description: `${f.staffCode} is in use at this school — generate a fresh code.`, variant: "destructive" }); return; }
    if (!f.phone.trim() || !f.email.trim()) { toast({ title: "Phone and email are required", variant: "destructive" }); return; }
    if (f.createLogin && f.loginPassword.length < 8) { toast({ title: "Login password too short", description: "Use at least 8 characters.", variant: "destructive" }); return; }
    setBusy(true);
    try {
      const res = await apiPost<{ login?: { email: string; role: string } | null }>(`/api/school/${currentSchoolId}/ops/payroll`, {
        action: "create-staff",
        fullName: f.fullName, category: f.category, position: f.position,
        phone: f.phone, email: f.email, qualification: f.qualification, department: f.department, subjects: f.subjects,
        address: f.address, ghanaCardId: f.ghanaCard, ssnitNumber: f.ssnit, tin: f.tin,
        tier2Provider: f.tier2Provider, tier2MemberId: f.tier2MemberId,
        bankName: f.bankName, accountNumber: f.accountNumber, momoNumber: f.momo,
        sex: f.sex || null, birthday: f.birthday || null, hiredAt: f.hiredAt || null,
        staffCode: f.staffCode.trim().toUpperCase(),
        salaryCents: Math.round(parseFloat(f.salary || "0") * 100),
        employmentType: f.employmentType,
        ...(f.createLogin ? { createLogin: { password: f.loginPassword, role: f.loginRole } } : {}),
      });
      toast({ title: "Staff member added", description: `${f.fullName} — ${f.staffCode}${res?.login ? ` · login created (${res.login.role})` : ""}` });
      setF(blankNewStaff());
      setCodeState("idle");
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[92vh] overflow-y-auto nice-scroll sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Users className="size-4" /> Add staff member</DialogTitle>
          <DialogDescription>
            Teachers, non-teaching staff and administrators — identity, contact &amp; government IDs, banking, employment and an optional linked login.
            Staff codes are auto-generated per category (TCR / NTS / ADM) and checked live. Photo upload is a known gap — not yet available.
          </DialogDescription>
        </DialogHeader>

        <FormGrid cols={2}>
          {/* ── Identity ── */}
          <div className="sm:col-span-2 text-[10px] uppercase tracking-wider text-muted-foreground font-semibold pt-1">Identity</div>
          <FormField label="Full name" required span={2}>
            <Input value={f.fullName} onChange={(e) => setF({ ...f, fullName: e.target.value })} placeholder="Madam Akosua Dede" />
          </FormField>
          <FormField label="Sex">
            <Select value={f.sex} onValueChange={(v) => setF({ ...f, sex: v })}>
              <SelectTrigger><SelectValue placeholder="Choose" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="M">Male</SelectItem>
                <SelectItem value="F">Female</SelectItem>
              </SelectContent>
            </Select>
          </FormField>
          <FormField label="Birthday">
            <Input type="date" value={f.birthday} onChange={(e) => setF({ ...f, birthday: e.target.value })} />
          </FormField>
          <FormField label="Staff code" required span={2} hint="TCR- teaching · NTS- non-teaching · ADM- administration — 5-digit sequence, unique per school.">
            <div className="flex gap-2">
              <Input value={f.staffCode} onChange={(e) => { const v = e.target.value.toUpperCase(); setF({ ...f, staffCode: v }); if (!v.trim()) setCodeState("idle"); }} placeholder="TCR-00001" className="font-mono" />
              <Button type="button" size="sm" variant="outline" className="h-9 shrink-0 gap-1.5" onClick={generate}><Wand2 className="size-3.5" /> Generate</Button>
            </div>
            {codeState === "ok" && <span className="text-[11px] font-medium text-emerald-600 dark:text-emerald-400">✓ available</span>}
            {codeState === "taken" && <span className="text-[11px] font-medium text-destructive">✗ already taken at this school</span>}
            {codeState === "checking" && <span className="text-[11px] text-muted-foreground">Checking…</span>}
          </FormField>

          {/* ── Contact & government ── */}
          <div className="sm:col-span-2 mt-2 pt-3 border-t text-[10px] uppercase tracking-wider text-muted-foreground font-semibold">Contact &amp; government</div>
          <FormField label="Phone" required>
            <Input value={f.phone} onChange={(e) => setF({ ...f, phone: e.target.value })} placeholder="024 000 0000" />
          </FormField>
          <FormField label="Email" required>
            <Input type="email" value={f.email} onChange={(e) => setF({ ...f, email: e.target.value })} placeholder="name@nyansapo.gh" />
          </FormField>
          <FormField label="Residential address" span={2}>
            <Input value={f.address} onChange={(e) => setF({ ...f, address: e.target.value })} placeholder="House No. 21, Ogbojo — Adenta" />
          </FormField>
          <FormField label="Ghana Card" hint="Format GHA-XXXXXXXXX-X">
            <Input value={f.ghanaCard} onChange={(e) => setF({ ...f, ghanaCard: e.target.value })} placeholder="GHA-123456789-0" className="font-mono" />
          </FormField>
          <FormField label="SSNIT number">
            <Input value={f.ssnit} onChange={(e) => setF({ ...f, ssnit: e.target.value })} placeholder="C0123456789" />
          </FormField>
          <FormField label="TIN">
            <Input value={f.tin} onChange={(e) => setF({ ...f, tin: e.target.value })} placeholder="C0012345678" />
          </FormField>
          <FormField label="Tier 2 provider" hint="Licensed occupational pension manager">
            <>
              <Input list="new-staff-tier2-providers" value={f.tier2Provider} onChange={(e) => setF({ ...f, tier2Provider: e.target.value })} placeholder="Petra Trust" />
              <datalist id="new-staff-tier2-providers">
                {providers.filter((p) => p.active).map((p) => <option key={p.id} value={p.name} />)}
              </datalist>
            </>
          </FormField>
          <FormField label="Tier 2 member ID" span={2}>
            <Input value={f.tier2MemberId} onChange={(e) => setF({ ...f, tier2MemberId: e.target.value })} placeholder="PT-998877" className="font-mono" />
          </FormField>

          {/* ── Banking ── */}
          <div className="sm:col-span-2 mt-2 pt-3 border-t text-[10px] uppercase tracking-wider text-muted-foreground font-semibold">Banking</div>
          <FormField label="Bank name">
            <Input value={f.bankName} onChange={(e) => setF({ ...f, bankName: e.target.value })} placeholder="GCB Bank" />
          </FormField>
          <FormField label="Account number">
            <Input value={f.accountNumber} onChange={(e) => setF({ ...f, accountNumber: e.target.value })} placeholder="1234567890123" className="font-mono" />
          </FormField>
          <FormField label="MoMo payout number" span={2}>
            <Input value={f.momo} onChange={(e) => setF({ ...f, momo: e.target.value })} placeholder="024 000 0000" />
          </FormField>

          {/* ── Employment ── */}
          <div className="sm:col-span-2 mt-2 pt-3 border-t text-[10px] uppercase tracking-wider text-muted-foreground font-semibold">Employment</div>
          <FormField label="Category" required>
            <Select value={f.category} onValueChange={setCategory}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="TEACHING">👨‍🏫 Teaching</SelectItem>
                <SelectItem value="NON_TEACHING">👷 Non-teaching</SelectItem>
                <SelectItem value="ADMINISTRATION">🏢 Administration</SelectItem>
              </SelectContent>
            </Select>
          </FormField>
          <FormField label="Position">
            <>
              <Input list="new-staff-positions" value={f.position} onChange={(e) => setF({ ...f, position: e.target.value })} placeholder="Teacher / Driver / Cook…" />
              <datalist id="new-staff-positions">
                {(NEW_STAFF_POSITIONS[f.category] ?? []).concat(ALL_NEW_STAFF_POSITIONS).map((p) => <option key={p} value={p} />)}
              </datalist>
            </>
          </FormField>
          <FormField label="Qualification">
            <Input value={f.qualification} onChange={(e) => setF({ ...f, qualification: e.target.value })} placeholder="B.Ed / WASSCE / HND" />
          </FormField>
          <FormField label="Department">
            <Input value={f.department} onChange={(e) => setF({ ...f, department: e.target.value })} placeholder="Kitchen / Transport" />
          </FormField>
          <FormField label="Subjects" span={2} hint="Comma-separated — teaching staff">
            <Input value={f.subjects} onChange={(e) => setF({ ...f, subjects: e.target.value })} placeholder="Mathematics, Science" />
          </FormField>
          <FormField label="Employment type">
            <Select value={f.employmentType} onValueChange={(v) => setF({ ...f, employmentType: v })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="FULL_TIME">Full time</SelectItem><SelectItem value="PART_TIME">Part time</SelectItem>
                <SelectItem value="CONTRACT">Contract</SelectItem><SelectItem value="CASUAL">Casual</SelectItem>
              </SelectContent>
            </Select>
          </FormField>
          <FormField label="Basic salary (GH₵/month)">
            <Input type="number" value={f.salary} onChange={(e) => setF({ ...f, salary: e.target.value })} placeholder="1600" />
          </FormField>
          <FormField label="Hired on">
            <Input type="date" value={f.hiredAt} onChange={(e) => setF({ ...f, hiredAt: e.target.value })} />
          </FormField>

          {/* ── Login (optional) ── */}
          <div className="sm:col-span-2 mt-2 pt-3 border-t text-[10px] uppercase tracking-wider text-muted-foreground font-semibold">Login (optional)</div>
          <div className="sm:col-span-2 flex items-start gap-2.5 rounded-lg border p-3">
            <Checkbox id="new-staff-login" checked={f.createLogin} onCheckedChange={(v) => setF({ ...f, createLogin: !!v })} className="mt-0.5" />
            <div className="text-sm leading-snug -mt-0.5">
              <label htmlFor="new-staff-login" className="font-medium cursor-pointer">Create a staff login</label>
              <p className="text-[11px] text-muted-foreground">Signs in with the email above (required) — linked to this staff record for the self-service portal.</p>
            </div>
          </div>
          {f.createLogin && (
            <>
              <FormField label="Login password" required hint="Minimum 8 characters">
                <Input type="password" autoComplete="new-password" value={f.loginPassword} onChange={(e) => setF({ ...f, loginPassword: e.target.value })} />
              </FormField>
              <FormField label="Login role" hint="Defaults from the staff category — TEACHER for teaching staff.">
                <Select value={f.loginRole} onValueChange={(v) => setF({ ...f, loginRole: v })}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    {LOGIN_ROLE_OPTIONS.map((r) => <SelectItem key={r} value={r}>{r === "NON_TEACHING" ? "Non-teaching" : r === "BUS_CONDUCTOR" ? "Bus conductor" : r === "SCHOOL_ADMIN" ? "School admin" : r.charAt(0) + r.slice(1).toLowerCase()}</SelectItem>)}
                  </SelectContent>
                </Select>
              </FormField>
            </>
          )}
        </FormGrid>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || !f.fullName.trim() || !f.staffCode.trim() || codeState === "taken"}>{busy ? "Adding…" : "Add staff member"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── PROFESSIONAL PRINTABLE PAYSLIP ─────────────────────────── */
function maskAccount(acc?: string | null): string {
  if (!acc) return "—";
  const s = acc.replace(/\s/g, "");
  if (s.length <= 5) return s;
  return `${s.slice(0, 2)}*-****-***${s.slice(-3)}`;
}

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
                <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[9px] uppercase tracking-wider text-muted-foreground">Staff</div><div className="font-semibold">{slip.staff} {slip.staffCode && <span className="font-mono text-[10px] text-muted-foreground">{slip.staffCode}</span>}</div></div>
                <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[9px] uppercase tracking-wider text-muted-foreground">Position · SSNIT No.</div><div className="font-semibold">{slip.position ?? slip.title ?? slip.role} {slip.ssnitNumber ? `· ${slip.ssnitNumber}` : ""}</div></div>
                {slip.bankName && (
                  <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[9px] uppercase tracking-wider text-muted-foreground">Bank / MoMo</div><div className="font-semibold">{slip.bankName} · {maskAccount(slip.accountNumber ?? slip.momoNumber)}</div></div>
                )}
                <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[9px] uppercase tracking-wider text-muted-foreground">Payment status</div><div className="font-semibold">{slip.status === "PAID" ? "Paid" : "Draft — not yet paid"}</div></div>
              </div>

              <div className="grid grid-cols-2 gap-4 mt-4">
                {/* earnings column */}
                <div>
                  <div className="font-semibold uppercase text-[9px] tracking-wider text-emerald-700 dark:text-emerald-400 border-b border-dashed pb-1 mb-1">Earnings</div>
                  <table className="w-full text-[12.5px]">
                    <tbody>
                      <tr><td className="py-1">Basic salary</td><td className="py-1 text-right tabular-nums">{fmtCedi(slip.base)}</td></tr>
                      {slip.allowances.map((a) => (
                        <tr key={a.name}><td className="py-1">{a.name}</td><td className="py-1 text-right tabular-nums">{fmtCedi(a.amountCents)}</td></tr>
                      ))}
                      <tr className="border-t"><td className="py-1 font-semibold">Gross earnings</td><td className="py-1 text-right font-semibold tabular-nums">{fmtCedi(slip.gross)}</td></tr>
                    </tbody>
                  </table>
                  <div className="mt-2 rounded-lg bg-primary/10 border border-primary/20 px-3 py-2">
                    <div className="text-[9px] uppercase tracking-wider text-primary/80 font-semibold">Net pay</div>
                    <div className="font-bold text-lg text-primary tabular-nums">{fmtCedi(slip.net)}</div>
                  </div>
                </div>
                {/* deductions column */}
                <div>
                  <div className="rounded-md bg-secondary/50 border px-2 py-1.5 mb-1.5">
                    <div className="text-[8.5px] uppercase tracking-wider text-muted-foreground font-semibold">Employer contributions (not deducted)</div>
                    <div className="flex justify-between text-[12px]"><span>SSNIT Tier 1 employer ({rates.ssnitTier1Employer}%)</span><span className="tabular-nums">{fmtCedi(slip.ssnit.tier1Employer)}</span></div>
                  </div>
                  <div className="text-[8.5px] uppercase tracking-wider text-muted-foreground font-semibold border-b border-dashed pb-1 mb-1">Employee deductions</div>
                  <table className="w-full text-[12.5px]">
                    <tbody>
                      <tr><td className="py-1">SSNIT Tier 1 employee ({rates.ssnitTier1Employee}%)</td><td className="py-1 text-right tabular-nums">{fmtCedi(slip.ssnit.tier1Employee)}</td></tr>
                      <tr>
                        <td className="py-1">{slip.tier2Provider ? <span>{slip.tier2Provider.toUpperCase()} <span className="text-[10px] text-muted-foreground">(Tier 2 {rates.ssnitTier2}%)</span></span> : `SSNIT Tier 2 (${rates.ssnitTier2}%)`}</td>
                        <td className="py-1 text-right tabular-nums">{fmtCedi(slip.ssnit.tier2)}</td>
                      </tr>
                      <tr><td className="py-1">PAYE income tax</td><td className="py-1 text-right tabular-nums">{fmtCedi(slip.paye)}</td></tr>
                      {slip.otherDeductions.map((d) => (
                        <tr key={d.name}><td className="py-1">{d.name}</td><td className="py-1 text-right tabular-nums">{fmtCedi(d.amountCents)}</td></tr>
                      ))}
                      <tr className="border-t"><td className="py-1 font-semibold">Total deductions</td><td className="py-1 text-right font-semibold tabular-nums text-destructive">{fmtCedi(slip.totalDeductions)}</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div className="mt-2 text-[10px] text-muted-foreground">Report discrepancies to the accounts office within 30 days. Computer-generated payslip — no signature required.</div>

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

/* ── SSNIT TIER 1 / TIER 2 PRINTABLE STATUTORY REPORTS (CI3 ssnit_report parity) ──
   Header: establishment + registration number + Year & Month.
   Tier 1: SSNIT column = employer share (13.5% under Act 766 as configured).
   Tier 2: occupational pension (5%) with member IDs + provider line.
   Amounts are computed from basic × statutory settings (CI3 behaviour);
   payslip-stored cents are carried in the CSV for reconciliation. */
type SsnitRow = {
  sn: number; staffId: string; firstName: string; lastName: string; otherNames: string; lastToken: string;
  ssnitNumber: string; tier2MemberId: string; tier2Provider: string; staffCode: string;
  basicSalaryCents: number; tier1EmployerCents: number; tier1EmployeeCents: number; tier2Cents: number;
  storedSsnitEmployeeCents: number; storedSsnitEmployerCents: number;
};
type SsnitReport = {
  period: string | null;
  run: { id: string; status: string; payslipCount: number } | null;
  rates: { tier1Employer: number; tier1Employee: number; tier2: number };
  establishment: string; registrationNo: string; town: string | null; region: string | null;
  rows: SsnitRow[];
  totals: { basicSalaryCents: number; tier1EmployerCents: number; tier1EmployeeCents: number; tier2Cents: number };
  providers: string[];
};

function SsnitReportsTab({ defaultPeriod }: { defaultPeriod?: string }) {
  const { currentSchoolId } = useStore();
  const [period, setPeriod] = useState(defaultPeriod ?? "");
  const [tier, setTier] = useState<"T1" | "T2">("T1");
  const [report, setReport] = useState<SsnitReport | null>(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => { if (defaultPeriod && !period) setPeriod(defaultPeriod); }, [defaultPeriod]); // eslint-disable-line react-hooks/set-state-in-effect

  const load = (p?: string) => {
    if (!currentSchoolId) return;
    const target = p ?? period;
    if (!target) return;
    setBusy(true);
    apiGet<SsnitReport>(`/api/school/${currentSchoolId}/ops/reports?tab=ssnit&period=${target}`)
      .then(setReport)
      .catch((e) => toast({ title: "Could not load SSNIT schedule", description: e.message, variant: "destructive" }))
      .finally(() => setBusy(false));
  };
  useEffect(() => { if (period) load(period); }, [currentSchoolId, period]); // eslint-disable-line react-hooks/set-state-in-effect

  const monthLabel = (p: string | null) => {
    if (!p) return "—";
    const [y, m] = p.split("-");
    return `${["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"][Number(m) - 1] ?? m} ${y}`;
  };

  function exportCsv() {
    if (!report) return;
    const r = report.rates;
    const header = tier === "T1"
      ? ["S/N", "SSNIT No.", "First Name", "Last Name", "Option", "Hazd.", `Salary (GH₵)`, `SSNIT ${r.tier1Employer}% (GH₵)`, `Employee ${r.tier1Employee}% (GH₵)`, "Payslip-stored employee SSNIT (GH₵)", "Payslip-stored employer SSNIT (GH₵)"]
      : ["S/N", "Tier 2 Member ID", "Last", "First", "Other", "Basic Salary (GH₵)", `Tier 2 Contribution ${r.tier2}% (GH₵)`, "Provider"];
    const lines = report.rows.map((x) => tier === "T1"
      ? [x.sn, x.ssnitNumber, x.firstName, x.lastName, "ACT 766", "NO", x.basicSalaryCents / 100, x.tier1EmployerCents / 100, x.tier1EmployeeCents / 100, x.storedSsnitEmployeeCents / 100, x.storedSsnitEmployerCents / 100]
      : [x.sn, x.tier2MemberId, x.lastToken, x.firstName, x.otherNames, x.basicSalaryCents / 100, x.tier2Cents / 100, x.tier2Provider]);
    lines.push(tier === "T1"
      ? ["", "", "TOTAL", "", "", "", report.totals.basicSalaryCents / 100, report.totals.tier1EmployerCents / 100, report.totals.tier1EmployeeCents / 100, "", ""]
      : ["", "", "TOTAL", "", "", report.totals.basicSalaryCents / 100, report.totals.tier2Cents / 100, ""]);
    const csv = [header, ...lines].map((l) => l.map((c) => `"${String(c ?? "").replace(/"/g, '""')}"`).join(",")).join("\n");
    const url = URL.createObjectURL(new Blob([csv], { type: "text/csv" }));
    const a = document.createElement("a"); a.href = url; a.download = `ssnit-tier${tier === "T1" ? "1" : "2"}-${report.period ?? "schedule"}.csv`; a.click(); URL.revokeObjectURL(url);
  }

  if (!report && busy) return <Card><CardContent><LoadingBlock label="Computing SSNIT contributions…" /></CardContent></Card>;

  return (
    <div className="space-y-4">
      {/* controls */}
      <Card className="no-print">
        <CardContent className="p-4 flex flex-wrap items-end gap-3">
          <div className="space-y-1.5">
            <Label className="text-xs">Payroll month</Label>
            <Input type="month" value={period} onChange={(e) => setPeriod(e.target.value)} className="w-40 h-9" />
          </div>
          <div className="flex rounded-lg border overflow-hidden h-9">
            {(["T1", "T2"] as const).map((t) => (
              <button key={t} type="button" onClick={() => setTier(t)}
                className={`px-4 text-sm font-medium transition-colors ${tier === t ? "bg-primary text-primary-foreground" : "hover:bg-secondary"}`}>
                {t === "T1" ? "Tier 1" : "Tier 2"}
              </button>
            ))}
          </div>
          <div className="flex gap-2 ml-auto">
            <Button size="sm" variant="outline" className="gap-1.5 h-9" disabled={!report} onClick={exportCsv}><Download className="size-4" /> Export CSV</Button>
            <Button size="sm" className="gap-1.5 h-9" disabled={!report} onClick={() => window.print()}><Printer className="size-4" /> Print (A4)</Button>
          </div>
        </CardContent>
      </Card>

      {!report ? (
        <Card><CardContent><EmptyState icon={<Landmark className="size-5" />} title="No schedule yet" hint="Pick a payroll month above — schedules are generated from that run's payslips." /></CardContent></Card>
      ) : report.rows.length === 0 ? (
        <Card><CardContent><EmptyState icon={<Landmark className="size-5" />} title={`No payslips for ${report.period}`} hint="Run payroll for that month first — the SSNIT schedules are generated per run." /></CardContent></Card>
      ) : (
        /* printable A4 statutory schedule */
        <div className="print-area">
          <div className="bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden">
            <KenteStrip />
            <div className="px-6 py-6">
              {/* establishment header */}
              <div className="text-center border-b-2 border-foreground/70 pb-3">
                <div className="text-lg font-bold uppercase tracking-wide">{report.establishment}</div>
                <div className="text-xs text-muted-foreground">{[report.town, report.region].filter(Boolean).join(", ")}</div>
                <div className="text-sm font-semibold mt-2">Social Security and National Insurance Trust Contribution Report</div>
                <div className="text-xs mt-1">
                  <span className="font-medium">Registration No.:</span> {report.registrationNo || "—"} · <span className="font-medium">Year &amp; Month:</span> {monthLabel(report.period)}
                </div>
                <div className="text-xs mt-0.5 font-medium">
                  {tier === "T1"
                    ? `Tier 1 — Basic Social Security (Act 766) · Employer ${report.rates.tier1Employer}% of basic`
                    : `Tier 2 — Occupational Pension · ${report.rates.tier2}% of basic${report.providers.length ? ` · Provider: ${report.providers.join(", ")}` : ""}`}
                </div>
              </div>

              {/* Tier 1 schedule */}
              {tier === "T1" && (
                <table className="w-full text-[11.5px] mt-4 border-collapse">
                  <thead>
                    <tr className="bg-secondary/70">
                      {["S/N", "SSNIT No.", "First Name", "Last Name", "Option", "Hazd.", "Salary (GH₵)", `SSNIT (${report.rates.tier1Employer}%)`].map((h) => (
                        <th key={h} className="border px-2 py-1.5 text-left font-semibold whitespace-nowrap">{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {report.rows.map((x) => (
                      <tr key={x.staffId}>
                        <td className="border px-2 py-1.5 tabular-nums">{x.sn}</td>
                        <td className="border px-2 py-1.5 font-mono">{x.ssnitNumber || "—"}</td>
                        <td className="border px-2 py-1.5">{x.firstName}</td>
                        <td className="border px-2 py-1.5">{x.lastName}</td>
                        <td className="border px-2 py-1.5 text-center">ACT 766</td>
                        <td className="border px-2 py-1.5 text-center">NO</td>
                        <td className="border px-2 py-1.5 text-right tabular-nums">{(x.basicSalaryCents / 100).toLocaleString("en-GH", { minimumFractionDigits: 2 })}</td>
                        <td className="border px-2 py-1.5 text-right tabular-nums font-semibold">{(x.tier1EmployerCents / 100).toLocaleString("en-GH", { minimumFractionDigits: 2 })}</td>
                      </tr>
                    ))}
                    <tr className="bg-secondary/40 font-bold">
                      <td className="border px-2 py-1.5" colSpan={6}>TOTAL</td>
                      <td className="border px-2 py-1.5 text-right tabular-nums">{(report.totals.basicSalaryCents / 100).toLocaleString("en-GH", { minimumFractionDigits: 2 })}</td>
                      <td className="border px-2 py-1.5 text-right tabular-nums">{(report.totals.tier1EmployerCents / 100).toLocaleString("en-GH", { minimumFractionDigits: 2 })}</td>
                    </tr>
                  </tbody>
                </table>
              )}

              {/* Tier 2 schedule */}
              {tier === "T2" && (
                <table className="w-full text-[11.5px] mt-4 border-collapse">
                  <thead>
                    <tr className="bg-secondary/70">
                      {["S/N", "Tier 2 Member ID", "Last", "First", "Other", "Basic Salary (GH₵)", `Tier 2 Contribution (${report.rates.tier2}%)`].map((h) => (
                        <th key={h} className="border px-2 py-1.5 text-left font-semibold whitespace-nowrap">{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {report.rows.map((x) => (
                      <tr key={x.staffId}>
                        <td className="border px-2 py-1.5 tabular-nums">{x.sn}</td>
                        <td className="border px-2 py-1.5 font-mono">{x.tier2MemberId || "—"}</td>
                        <td className="border px-2 py-1.5">{x.lastToken}</td>
                        <td className="border px-2 py-1.5">{x.firstName}</td>
                        <td className="border px-2 py-1.5">{x.otherNames || ""}</td>
                        <td className="border px-2 py-1.5 text-right tabular-nums">{(x.basicSalaryCents / 100).toLocaleString("en-GH", { minimumFractionDigits: 2 })}</td>
                        <td className="border px-2 py-1.5 text-right tabular-nums font-semibold">{(x.tier2Cents / 100).toLocaleString("en-GH", { minimumFractionDigits: 2 })}</td>
                      </tr>
                    ))}
                    <tr className="bg-secondary/40 font-bold">
                      <td className="border px-2 py-1.5" colSpan={5}>TOTAL</td>
                      <td className="border px-2 py-1.5 text-right tabular-nums">{(report.totals.basicSalaryCents / 100).toLocaleString("en-GH", { minimumFractionDigits: 2 })}</td>
                      <td className="border px-2 py-1.5 text-right tabular-nums">{(report.totals.tier2Cents / 100).toLocaleString("en-GH", { minimumFractionDigits: 2 })}</td>
                    </tr>
                  </tbody>
                </table>
              )}

              {/* footnotes + signature */}
              <div className="mt-3 text-[10px] text-muted-foreground leading-relaxed">
                Contributions computed from basic salary × statutory rates (employer {report.rates.tier1Employer}%, employee {report.rates.tier1Employee}%, Tier 2 {report.rates.tier2}%) as configured in Statutory settings.
                {report.providers.length > 0 && ` Tier 2 contributions are remitted to ${report.providers.join(", ")}.`}
                {" "}Run: {report.period} ({report.run?.status ?? "—"}, {report.rows.length} payslips).
              </div>
              <div className="grid grid-cols-3 gap-8 pt-8 text-[10px] text-muted-foreground">
                <div className="border-t border-border pt-1">Prepared by</div>
                <div className="border-t border-border pt-1">Head of Finance</div>
                <div className="border-t border-border pt-1 text-right">Date</div>
              </div>
            </div>
            <KenteStrip />
          </div>
        </div>
      )}
    </div>
  );
}

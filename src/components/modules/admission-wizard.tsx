"use client";
/* Nyansapo — Admission wizard (iedupro parity, modernised).
   Six steps: Learner → Placement → Guardian → Bill preview → Discount & deposit → Review.
   Creates Student + Enrollment + Invoice (+ optional first payment with receipt). */
import { useEffect, useMemo, useState } from "react";
import { apiGet, apiPost } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Separator } from "@/components/ui/separator";
import { fmtCedi, PAYMENT_LABELS } from "@/lib/format";
import { ReceiptDialog, ReceiptData } from "@/components/receipts";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  UserRound, GraduationCap, Users, ReceiptText, BadgePercent, ClipboardCheck, Check, ChevronRight, ChevronLeft,
  Plus, Trash2, Printer, Sparkles, School as SchoolIcon,
} from "lucide-react";

type ClassRow = {
  id: string; name: string; gradeLevel: string; stageLabel?: string | null; capacity: number;
  classTeacher: string | null; enrolled: number; feeItems: { name: string; amountCents: number; isOptional: boolean }[]; feeTotal: number;
};
type DiscountRow = { id: string; name: string; type: string; value: number; notes?: string | null };
type WizardData = {
  term: { name: string; year: string } | null;
  classrooms: ClassRow[];
  discounts: DiscountRow[];
  recent: any[];
};

const STEPS = [
  { key: "learner", label: "Learner", icon: UserRound },
  { key: "placement", label: "Placement", icon: GraduationCap },
  { key: "guardian", label: "Guardian", icon: Users },
  { key: "bill", label: "Term bill", icon: ReceiptText },
  { key: "concession", label: "Discount & deposit", icon: BadgePercent },
  { key: "review", label: "Review", icon: ClipboardCheck },
];

const money = (v: string | number) => Math.round(Number(v || 0) * 100);
const gh = (cents: number) => (cents / 100).toFixed(2);

export function AdmissionWizard({ schoolId, onAdmitted }: { schoolId: string; onAdmitted?: () => void }) {
  const [data, setData] = useState<WizardData | null>(null);
  const [step, setStep] = useState(0);

  // form state
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [otherNames, setOtherNames] = useState("");
  const [gender, setGender] = useState("");
  const [dateOfBirth, setDateOfBirth] = useState("");
  const [hometown, setHometown] = useState("");
  const [previousSchool, setPreviousSchool] = useState("");

  const [classroomId, setClassroomId] = useState("");
  const [guardianName, setGuardianName] = useState("");
  const [guardianPhone, setGuardianPhone] = useState("");
  const [guardianAltPhone, setGuardianAltPhone] = useState("");
  const [guardianEmail, setGuardianEmail] = useState("");
  const [guardianOccupation, setGuardianOccupation] = useState("");
  const [guardianAddress, setGuardianAddress] = useState("");
  const [relationship, setRelationship] = useState("Parent");

  const [bill, setBill] = useState<{ name: string; amountCents: number }[]>([]);
  const [extraItem, setExtraItem] = useState({ name: "", amount: "" });
  const [discountId, setDiscountId] = useState("");
  const [deposit, setDeposit] = useState("");
  const [depositMethod, setDepositMethod] = useState("CASH");

  const [busy, setBusy] = useState(false);
  const [receipt, setReceipt] = useState<ReceiptData | null>(null);
  const [done, setDone] = useState<null | { name: string; index: string; classroom: string; rollNo: number; invoiceNo?: string; payable: number }>(null);

  useEffect(() => {
    apiGet<WizardData>(`/api/school/${schoolId}/ops/admissions`).then(setData).catch(() => setData({ term: null, classrooms: [], discounts: [], recent: [] }));
  }, [schoolId]);

  const chosenClass = useMemo(() => data?.classrooms.find((c) => c.id === classroomId) ?? null, [data, classroomId]);

  // when class changes, (re)build the bill from the class fee structure
  useEffect(() => {
    if (!chosenClass) { setBill([]); return; }
    setBill(chosenClass.feeItems.filter((i) => !i.isOptional).map((i) => ({ name: i.name, amountCents: i.amountCents })));
    setDiscountId(""); setDeposit("");
  }, [classroomId]); // eslint-disable-line react-hooks/exhaustive-deps

  const gross = bill.reduce((s, i) => s + i.amountCents, 0);
  const discount = useMemo(() => {
    const d = data?.discounts.find((x) => x.id === discountId);
    if (!d) return 0;
    return d.type === "PERCENT" ? Math.round((gross * d.value) / 100) : Math.min(d.value, gross);
  }, [discountId, gross, data]);
  const payable = Math.max(0, gross - discount);
  const depositCents = Math.min(money(deposit || 0), payable);
  const balanceAfter = payable - depositCents;

  const canNext = [
    firstName.trim() && lastName.trim() && gender,
    !!classroomId,
    guardianName.trim() && guardianPhone.trim().length >= 9,
    bill.length > 0,
    true,
    true,
  ][step];

  async function submit() {
    setBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${schoolId}/ops/admissions`, {
        action: "admit",
        firstName, lastName, otherNames, gender, dateOfBirth: dateOfBirth || null,
        classroomId, guardianName, guardianPhone, guardianAltPhone, guardianEmail, guardianOccupation, guardianAddress,
        relationship, hometown, previousSchool,
        billItems: bill, discountId: discountId || null,
        initialPaymentCents: depositCents > 0 ? depositCents : 0,
        initialPaymentMethod: depositMethod,
      });
      setDone({
        name: res.student.name, index: res.student.nyansapoIndex,
        classroom: res.enrollment.classroom, rollNo: res.enrollment.rollNo,
        invoiceNo: res.invoice?.invoiceNo, payable: res.invoice?.payable ?? 0,
      });
      if (res.receipt) setReceipt(res.receipt);
      toast({ title: "Learner admitted", description: `${res.student.name} · ${res.student.nyansapoIndex}` });
      onAdmitted?.();
    } catch (e: any) {
      toast({ title: "Admission failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  function reset() {
    setStep(0); setDone(null); setReceipt(null);
    setFirstName(""); setLastName(""); setOtherNames(""); setGender(""); setDateOfBirth(""); setHometown(""); setPreviousSchool("");
    setClassroomId(""); setGuardianName(""); setGuardianPhone(""); setGuardianAltPhone(""); setGuardianEmail(""); setGuardianOccupation(""); setGuardianAddress(""); setRelationship("Parent");
    setBill([]); setDiscountId(""); setDeposit(""); setDepositMethod("CASH");
  }

  if (!data) return <div className="py-16 text-center text-sm text-muted-foreground">Loading admission desk…</div>;

  // ── success screen ──
  if (done) {
    return (
      <div className="space-y-4">
        <Card className="border-emerald-200 dark:border-emerald-900">
          <CardContent className="pt-6 pb-6 text-center space-y-4">
            <div className="mx-auto size-14 rounded-full bg-emerald-100 dark:bg-emerald-950 flex items-center justify-center">
              <Check className="size-7 text-emerald-600" />
            </div>
            <div>
              <div className="text-lg font-bold">{done.name} admitted</div>
              <div className="text-sm text-muted-foreground">
                {done.classroom} · Roll #{done.rollNo} · Nyansapo Index <span className="font-mono font-semibold text-foreground">{done.index}</span>
              </div>
            </div>
            <div className="flex flex-wrap justify-center gap-2 text-xs">
              {done.invoiceNo && <Badge variant="outline">Invoice {done.invoiceNo}</Badge>}
              {done.payable > 0 && <Badge variant="outline">Payable {fmtCedi(done.payable)}</Badge>}
              {depositCents > 0 && <Badge className="bg-emerald-600">Deposit {fmtCedi(depositCents)} received</Badge>}
            </div>
            <div className="flex justify-center gap-2 pt-2">
              {receipt && <Button size="sm" variant="outline" className="gap-1.5" onClick={() => setReceipt(receipt)}><Printer className="size-4" /> Reprint receipt</Button>}
              <Button size="sm" className="gap-1.5" onClick={reset}><Sparkles className="size-4" /> Admit another learner</Button>
            </div>
          </CardContent>
        </Card>
        <ReceiptDialog data={receipt} open={!!receipt} onOpenChange={(o) => !o && setReceipt(null)} />
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_340px] gap-5 items-start">
      {/* ── stepper + form ── */}
      <div className="space-y-4">
        <div className="flex items-center gap-1 overflow-x-auto pb-1">
          {STEPS.map((s, i) => (
            <button key={s.key} onClick={() => i <= step && setStep(i)}
              className={cn("flex items-center gap-1.5 rounded-full pl-2.5 pr-3.5 py-1.5 text-xs font-medium whitespace-nowrap transition-colors",
                i === step ? "bg-primary text-primary-foreground" : i < step ? "bg-primary/10 text-primary hover:bg-primary/20" : "text-muted-foreground")}>
              {i < step ? <Check className="size-3.5" /> : <s.icon className="size-3.5" />}
              {s.label}
              {i < STEPS.length - 1 && <ChevronRight className="size-3 opacity-50" />}
            </button>
          ))}
        </div>

        <Card>
          <CardContent className="pt-5 pb-5 space-y-4">
            {/* STEP 1 — learner */}
            {step === 0 && (
              <>
                <div className="grid sm:grid-cols-2 gap-3">
                  <div className="space-y-1.5">
                    <Label>First name *</Label>
                    <Input value={firstName} onChange={(e) => setFirstName(e.target.value)} placeholder="Ama" />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Last name *</Label>
                    <Input value={lastName} onChange={(e) => setLastName(e.target.value)} placeholder="Mensah" />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Middle / other names</Label>
                    <Input value={otherNames} onChange={(e) => setOtherNames(e.target.value)} placeholder="Kesi" />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Gender *</Label>
                    <Select value={gender} onValueChange={setGender}>
                      <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="F">Female</SelectItem>
                        <SelectItem value="M">Male</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-1.5">
                    <Label>Date of birth</Label>
                    <Input type="date" value={dateOfBirth} onChange={(e) => setDateOfBirth(e.target.value)} />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Hometown</Label>
                    <Input value={hometown} onChange={(e) => setHometown(e.target.value)} placeholder="Kumasi" />
                  </div>
                </div>
                <Separator />
                <div className="space-y-1.5">
                  <Label>Previous school (if transferring in)</Label>
                  <Input value={previousSchool} onChange={(e) => setPreviousSchool(e.target.value)} placeholder="Sunrise International School" />
                  <p className="text-[11px] text-muted-foreground">If the learner has prior Ghana schooling, also run a registry check to pull their history and any network flags.</p>
                </div>
              </>
            )}

            {/* STEP 2 — placement */}
            {step === 1 && (
              <div className="space-y-3">
                <p className="text-sm text-muted-foreground">Choose the class to enrol in. Capacity and this term&apos;s fee structure load automatically.</p>
                <div className="grid sm:grid-cols-2 gap-2.5">
                  {data.classrooms.map((c) => {
                    const full = c.enrolled >= c.capacity;
                    const active = c.id === classroomId;
                    return (
                      <button key={c.id} onClick={() => setClassroomId(c.id)}
                        className={cn("text-left rounded-xl border p-3 transition-colors",
                          active ? "border-primary bg-primary/5 ring-1 ring-primary" : "border-border hover:border-primary/40 hover:bg-secondary/40",
                          full && !active && "opacity-60")}>
                        <div className="flex items-center justify-between gap-2">
                          <span className="font-semibold text-sm">{c.name}</span>
                          {active && <Badge className="bg-primary text-[10px]">selected</Badge>}
                        </div>
                        <div className="text-[11px] text-muted-foreground mt-1">{c.stageLabel ?? c.gradeLevel}</div>
                        <div className="flex items-center gap-2 mt-2 text-[11px]">
                          <Badge variant={full ? "destructive" : "secondary"} className="text-[10px]">{c.enrolled}/{c.capacity}</Badge>
                          {c.classTeacher && <span className="text-muted-foreground truncate">· {c.classTeacher}</span>}
                        </div>
                        {c.feeTotal > 0 && <div className="text-[11px] mt-1.5 font-medium">{fmtCedi(c.feeTotal)} term bill</div>}
                      </button>
                    );
                  })}
                </div>
              </div>
            )}

            {/* STEP 3 — guardian */}
            {step === 2 && (
              <div className="grid sm:grid-cols-2 gap-3">
                <div className="space-y-1.5 sm:col-span-2">
                  <Label>Guardian name *</Label>
                  <Input value={guardianName} onChange={(e) => setGuardianName(e.target.value)} placeholder="Mr. Kwame Mensah" />
                </div>
                <div className="space-y-1.5">
                  <Label>Guardian phone (MoMo) *</Label>
                  <Input value={guardianPhone} onChange={(e) => setGuardianPhone(e.target.value)} placeholder="024 123 4567" />
                </div>
                <div className="space-y-1.5">
                  <Label>Alternative phone</Label>
                  <Input value={guardianAltPhone} onChange={(e) => setGuardianAltPhone(e.target.value)} placeholder="055 987 6543" />
                </div>
                <div className="space-y-1.5">
                  <Label>Email</Label>
                  <Input type="email" value={guardianEmail} onChange={(e) => setGuardianEmail(e.target.value)} placeholder="guardian@example.com" />
                </div>
                <div className="space-y-1.5">
                  <Label>Relationship</Label>
                  <Select value={relationship} onValueChange={setRelationship}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      {["Parent", "Father", "Mother", "Guardian", "Other Family"].map((r) => <SelectItem key={r} value={r}>{r}</SelectItem>)}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-1.5">
                  <Label>Occupation</Label>
                  <Input value={guardianOccupation} onChange={(e) => setGuardianOccupation(e.target.value)} placeholder="Trader" />
                </div>
                <div className="space-y-1.5">
                  <Label>Residential address</Label>
                  <Input value={guardianAddress} onChange={(e) => setGuardianAddress(e.target.value)} placeholder="Ashaiman, Taifa" />
                </div>
              </div>
            )}

            {/* STEP 4 — bill */}
            {step === 3 && (
              <div className="space-y-4">
                {!chosenClass ? (
                  <p className="text-sm text-muted-foreground">Choose a class first — the bill builds from its fee structure.</p>
                ) : (
                  <>
                    <div className="text-sm">
                      <span className="font-medium">{chosenClass.name}</span>
                      <span className="text-muted-foreground"> · {data.term ? `${data.term.name}, ${data.term.year}` : "current term"}</span>
                    </div>
                    <div className="rounded-xl border divide-y">
                      {bill.map((it, i) => (
                        <div key={i} className="flex items-center gap-2 px-3 py-2">
                          <Input className="h-8 border-0 bg-transparent px-0 focus-visible:ring-0 flex-1 font-medium" value={it.name}
                            onChange={(e) => setBill(bill.map((x, j) => j === i ? { ...x, name: e.target.value } : x))} />
                          <div className="relative w-32">
                            <span className="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-muted-foreground">GH₵</span>
                            <Input className="h-8 pl-10 text-right tabular-nums" type="number" min="0" step="0.01" value={gh(it.amountCents)}
                              onChange={(e) => setBill(bill.map((x, j) => j === i ? { ...x, amountCents: money(e.target.value) } : x))} />
                          </div>
                          <Button size="icon" variant="ghost" className="size-8 text-muted-foreground hover:text-destructive" onClick={() => setBill(bill.filter((_, j) => j !== i))}>
                            <Trash2 className="size-3.5" />
                          </Button>
                        </div>
                      ))}
                      {bill.length === 0 && <div className="px-3 py-4 text-sm text-muted-foreground text-center">No bill items — add the charges below.</div>}
                    </div>
                    <div className="flex flex-wrap items-end gap-2">
                      <div className="flex-1 min-w-40 space-y-1.5">
                        <Label>Add a bill item</Label>
                        <Input value={extraItem.name} onChange={(e) => setExtraItem({ ...extraItem, name: e.target.value })} placeholder="e.g. Admission fee" />
                      </div>
                      <div className="w-32 space-y-1.5">
                        <Label>Amount</Label>
                        <Input type="number" min="0" step="0.01" value={extraItem.amount} onChange={(e) => setExtraItem({ ...extraItem, amount: e.target.value })} placeholder="0.00" />
                      </div>
                      <Button variant="outline" className="gap-1.5" disabled={!extraItem.name.trim() || money(extraItem.amount) <= 0}
                        onClick={() => { setBill([...bill, { name: extraItem.name.trim(), amountCents: money(extraItem.amount) }]); setExtraItem({ name: "", amount: "" }); }}>
                        <Plus className="size-4" /> Add
                      </Button>
                    </div>
                    <p className="text-[11px] text-muted-foreground">Amounts are editable — waive or adjust individual charges before generating the invoice.</p>
                  </>
                )}
              </div>
            )}

            {/* STEP 5 — discount & deposit */}
            {step === 4 && (
              <div className="space-y-5">
                <div className="space-y-1.5">
                  <Label>Discount profile (invoice scope)</Label>
                  <Select value={discountId} onValueChange={setDiscountId}>
                    <SelectTrigger><SelectValue placeholder="No discount" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="none">No discount</SelectItem>
                      {data.discounts.map((d) => (
                        <SelectItem key={d.id} value={d.id}>
                          {d.name} · {d.type === "PERCENT" ? `${d.value}%` : fmtCedi(d.value)}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {data.discounts.length === 0 && <p className="text-[11px] text-muted-foreground">No active invoice discounts — create profiles under Fees → Discounts.</p>}
                </div>
                <Separator />
                <div className="grid sm:grid-cols-2 gap-3">
                  <div className="space-y-1.5">
                    <Label>Deposit received now (GH₵)</Label>
                    <Input type="number" min="0" step="0.01" value={deposit} onChange={(e) => setDeposit(e.target.value)} placeholder="0.00" />
                  </div>
                  <div className="space-y-1.5">
                    <Label>Method</Label>
                    <Select value={depositMethod} onValueChange={setDepositMethod}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        {Object.entries(PAYMENT_LABELS).map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                </div>
                {depositCents > 0 && (
                  <div className="rounded-lg bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-900 px-3 py-2 text-sm text-emerald-800 dark:text-emerald-300">
                    A receipt for {fmtCedi(depositCents)} will be issued immediately — balance {fmtCedi(balanceAfter)} carries on the term invoice.
                  </div>
                )}
              </div>
            )}

            {/* STEP 6 — review */}
            {step === 5 && (
              <div className="space-y-4 text-sm">
                <div className="grid sm:grid-cols-2 gap-x-6 gap-y-2.5">
                  {[
                    ["Learner", `${firstName} ${otherNames} ${lastName}`.replace(/\s+/g, " ")],
                    ["Gender", gender === "F" ? "Female" : "Male"],
                    ["Date of birth", dateOfBirth || "—"],
                    ["Class", chosenClass?.name ?? "—"],
                    ["Roll number", `#${(chosenClass?.enrolled ?? 0) + 1}`],
                    ["Guardian", `${guardianName} (${relationship})`],
                    ["Guardian phone", guardianPhone],
                    ["Previous school", previousSchool || "—"],
                  ].map(([k, v]) => (
                    <div key={k} className="flex gap-2">
                      <span className="text-muted-foreground w-36 shrink-0">{k}</span>
                      <span className="font-medium">{v}</span>
                    </div>
                  ))}
                </div>
                <Separator />
                <div className="rounded-xl border overflow-hidden">
                  <table className="w-full text-[13px]">
                    <tbody>
                      {bill.map((it, i) => (
                        <tr key={i} className="border-b border-dashed last:border-0">
                          <td className="px-3 py-1.5">{it.name}</td>
                          <td className="px-3 py-1.5 text-right tabular-nums">{fmtCedi(it.amountCents)}</td>
                        </tr>
                      ))}
                      <tr className="bg-secondary/50 font-semibold">
                        <td className="px-3 py-2">Gross</td>
                        <td className="px-3 py-2 text-right tabular-nums">{fmtCedi(gross)}</td>
                      </tr>
                      {discount > 0 && (
                        <tr className="text-emerald-700 dark:text-emerald-400">
                          <td className="px-3 py-1.5">Discount — {data.discounts.find((d) => d.id === discountId)?.name}</td>
                          <td className="px-3 py-1.5 text-right tabular-nums">−{fmtCedi(discount)}</td>
                        </tr>
                      )}
                      <tr className="bg-primary/5 font-bold">
                        <td className="px-3 py-2">Payable this term</td>
                        <td className="px-3 py-2 text-right tabular-nums">{fmtCedi(payable)}</td>
                      </tr>
                      {depositCents > 0 && (
                        <tr className="text-muted-foreground">
                          <td className="px-3 py-1.5">Deposit at admission</td>
                          <td className="px-3 py-1.5 text-right tabular-nums">{fmtCedi(depositCents)}</td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
                <p className="text-[11px] text-muted-foreground">
                  On submit: learner record + national index, class enrolment, term invoice{discountId ? ", discount application" : ""}{depositCents > 0 ? ", payment + receipt" : ""} and a welcome SMS to the guardian.
                </p>
              </div>
            )}

            {/* nav */}
            <div className="flex items-center justify-between pt-1">
              <Button variant="outline" size="sm" className="gap-1" disabled={step === 0 || busy} onClick={() => setStep(step - 1)}>
                <ChevronLeft className="size-4" /> Back
              </Button>
              {step < STEPS.length - 1 ? (
                <Button size="sm" className="gap-1" disabled={!canNext} onClick={() => setStep(step + 1)}>
                  Continue <ChevronRight className="size-4" />
                </Button>
              ) : (
                <Button size="sm" className="gap-1.5" disabled={busy || !canNext} onClick={submit}>
                  {busy ? "Admitting…" : "Admit learner"} <Check className="size-4" />
                </Button>
              )}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* ── live bill rail ── */}
      <div className="space-y-3 lg:sticky lg:top-20">
        <Card className="overflow-hidden">
          <div className="bg-primary text-primary-foreground px-4 py-3 flex items-center gap-2">
            <ReceiptText className="size-4" />
            <span className="font-semibold text-sm">Admission bill preview</span>
          </div>
          <CardContent className="pt-4 pb-4 space-y-2.5 text-sm">
            {!chosenClass ? (
              <p className="text-muted-foreground text-xs py-2">Select a class to preview the term bill.</p>
            ) : bill.length === 0 ? (
              <p className="text-muted-foreground text-xs py-2">No bill items yet.</p>
            ) : (
              <>
                {bill.map((it, i) => (
                  <div key={i} className="flex justify-between gap-2 text-[13px]">
                    <span className="truncate">{it.name}</span>
                    <span className="tabular-nums font-medium">{fmtCedi(it.amountCents)}</span>
                  </div>
                ))}
                <Separator className="my-1" />
                <div className="flex justify-between"><span className="text-muted-foreground">Gross</span><span className="font-semibold tabular-nums">{fmtCedi(gross)}</span></div>
                {discount > 0 && (
                  <div className="flex justify-between text-emerald-600 dark:text-emerald-400">
                    <span className="truncate max-w-52">Discount — {data.discounts.find((d) => d.id === discountId)?.name}</span>
                    <span className="tabular-nums font-medium">−{fmtCedi(discount)}</span>
                  </div>
                )}
                <div className="flex justify-between text-base font-bold pt-1 border-t">
                  <span>Payable</span><span className="tabular-nums">{fmtCedi(payable)}</span>
                </div>
                {depositCents > 0 && (
                  <div className="flex justify-between text-xs text-muted-foreground">
                    <span>Balance after deposit</span><span className="tabular-nums">{fmtCedi(balanceAfter)}</span>
                  </div>
                )}
              </>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-4 pb-4">
            <div className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-2.5 flex items-center gap-1.5">
              <SchoolIcon className="size-3.5" /> Recent admissions
            </div>
            <div className="space-y-2">
              {data.recent.slice(0, 5).map((r: any) => (
                <div key={r.id} className="flex items-center justify-between gap-2 text-xs">
                  <div className="min-w-0">
                    <div className="font-medium truncate">{r.student}</div>
                    <div className="text-muted-foreground">{r.classroom} · {new Date(r.admittedAt).toLocaleDateString()}</div>
                  </div>
                  <Badge variant="secondary" className="text-[9px] shrink-0 font-mono">{r.nyansapoIndex.slice(-6)}</Badge>
                </div>
              ))}
              {data.recent.length === 0 && <p className="text-xs text-muted-foreground">No recent admissions in the last 120 days.</p>}
            </div>
          </CardContent>
        </Card>
      </div>

      <ReceiptDialog data={receipt} open={!!receipt} onOpenChange={(o) => !o && setReceipt(null)} />
    </div>
  );
}

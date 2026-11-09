"use client";
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost, apiPostQueueable } from "@/lib/api";
import { Input } from "@/components/ui/input";
import { DateField } from "@/components/date-field";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Sheet, SheetContent, SheetHeader, SheetTitle } from "@/components/ui/sheet";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ModuleHeader, LoadingBlock, StatusBadge, SeverityBadge, RiskPill, EmptyState, FilterBar, FilterSelect, FilterSearch } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, FLAG_LABELS, PAYMENT_LABELS, fmtDateTime } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { initials, avatarColor } from "@/lib/format";
import { Search, UserPlus, Download, Flag, LogOut, Phone, Mail, MapPin, IdCard, GraduationCap, Banknote, ClipboardList, ArrowLeftRight, ShieldAlert, ArrowUpRight, Utensils, UserX, Undo2, ChevronRight, CheckSquare, Printer } from "lucide-react";
import { cn } from "@/lib/utils";

type StudentRow = {
  id: string; nyansapoIndex: string; name: string; otherNames: string | null; gender: string; dob: string | null;
  classroom: string; classroomId: string; rollNo: number | null; guardianName: string; guardianPhone: string;
  relationship: string; admissionDate: string; status: string;
  dietNote: string | null; promotedFrom: string | null; promotedAt: string | null;
  invoice: { billed: number; paid: number; status: string; invoiceNo: string } | null;
  flags: { id: string; type: string; severity: string; status: string; amountOwed: number | null; description: string; raisedAt: string; raisedByName: string }[];
  risk: { score: number; level: string } | null;
};

export function Students() {
  const { currentSchoolId, online, navTarget } = useStore();
  const [rows, setRows] = useState<StudentRow[] | null>(null);
  const [classrooms, setClassrooms] = useState<{ id: string; name: string }[]>([]);
  const [search, setSearch] = useState("");
  const [classId, setClassId] = useState("all");
  const [gender, setGender] = useState("all");
  const [feeState, setFeeState] = useState("all");
  const [selected, setSelected] = useState<StudentRow | null>(null);
  const [profile, setProfile] = useState<any | null>(null);
  const [addOpen, setAddOpen] = useState(false);
  const [withdrawFor, setWithdrawFor] = useState<StudentRow | null>(null);
  const [statusView, setStatusView] = useState<"ACTIVE" | "WITHDRAWN" | "GRADUATED">("ACTIVE");
  const [promoOpen, setPromoOpen] = useState(false);
  const [tab, setTab] = useState("roster");

  /* iEduPro nested nav: preselect the class when arriving from a
     Students → level → class deep link (state adjusted during render —
     the recommended pattern for reacting to store changes) */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.classroomId) setClassId(navTarget.classroomId);
    if (navTarget?.tab === "idcards") setTab("idcards");
  }

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<any>(`/api/school/${currentSchoolId}/students?status=${statusView}`)
      .then((d) => { setRows(d.students); })
      .catch((e) => toast({ title: "Could not load students", description: e.message, variant: "destructive" }));
    apiGet<any>(`/api/school/${currentSchoolId}/settings`).then((d) => setClassrooms(d.classrooms)).catch(() => {});
  };
  useEffect(load, [currentSchoolId, statusView]); // eslint-disable-line react-hooks/set-state-in-effect

  const filtered = useMemo(() => {
    if (!rows) return [];
    let out = rows;
    if (classId !== "all") out = out.filter((r) => r.classroomId === classId);
    if (gender !== "all") out = out.filter((r) => r.gender === gender);
    if (feeState !== "all") {
      out = out.filter((r) => {
        const bal = (r.invoice?.billed ?? 0) - (r.invoice?.paid ?? 0);
        if (feeState === "owing") return bal > 0;
        if (feeState === "settled") return bal <= 0;
        if (feeState === "overdue") return r.invoice?.status === "OVERDUE" || (r.invoice?.status === "UNPAID" && bal > 0);
        return true;
      });
    }
    if (search.trim()) {
      const q = search.toLowerCase();
      out = out.filter((r) => `${r.name} ${r.otherNames ?? ""} ${r.nyansapoIndex} ${r.guardianName} ${r.guardianPhone}`.toLowerCase().includes(q));
    }
    return out;
  }, [rows, classId, gender, feeState, search]);
  const filtersActive = classId !== "all" || gender !== "all" || feeState !== "all" || !!search.trim();
  const clearFilters = () => { setClassId("all"); setGender("all"); setFeeState("all"); setSearch(""); };

  async function openProfile(s: StudentRow) {
    setSelected(s);
    setProfile(null);
    try { setProfile(await apiGet<any>(`/api/student/${s.id}`)); } catch (e: any) { toast({ title: "Profile load failed", description: e.message, variant: "destructive" }); }
  }

  /* iEduPro mute/unmute (temporarily off roll) — placement status flips and
     the school's on-roll count is kept in sync server-side */
  async function mute(s: StudentRow, reason: string) {
    try {
      const r = await apiPost<any>(`/api/school/${currentSchoolId}/students`, { action: "mute", studentId: s.id, reason });
      toast({ title: `${s.name} muted`, description: `Off roll — ${r.onRoll} learners remaining.` });
      setSelected(null); load();
    } catch (e: any) { toast({ title: "Could not mute", description: e.message, variant: "destructive" }); }
  }
  async function unmute(s: StudentRow) {
    try {
      const r = await apiPost<any>(`/api/school/${currentSchoolId}/students`, { action: "unmute", studentId: s.id });
      toast({ title: `${s.name} restored`, description: `Back on roll — ${r.onRoll} learners.` });
      setSelected(null); load();
    } catch (e: any) { toast({ title: "Could not restore", description: e.message, variant: "destructive" }); }
  }

  const exportCsv = () => {
    if (!filtered.length) return;
    const header = ["Index", "Name", "Gender", "Class", "Guardian", "Phone", "Billed", "Paid", "Balance", "Status"];
    const lines = filtered.map((r) => [r.nyansapoIndex, r.name, r.gender, r.classroom, r.guardianName, r.guardianPhone, (r.invoice?.billed ?? 0) / 100, (r.invoice?.paid ?? 0) / 100, ((r.invoice?.billed ?? 0) - (r.invoice?.paid ?? 0)) / 100, r.status]);
    const csv = [header, ...lines].map((l) => l.map((c) => `"${String(c).replace(/"/g, '""')}"`).join(",")).join("\n");
    const url = URL.createObjectURL(new Blob([csv], { type: "text/csv" }));
    const a = document.createElement("a"); a.href = url; a.download = "nyansapo-students.csv"; a.click(); URL.revokeObjectURL(url);
  };

  if (!rows) return <LoadingBlock label="Loading learners…" />;

  return (
    <div>
      <Tabs value={tab} onValueChange={setTab}>
        <TabsList>
          <TabsTrigger value="roster">Roster</TabsTrigger>
          <TabsTrigger value="idcards" className="gap-1.5"><IdCard className="size-4" /> ID cards</TabsTrigger>
        </TabsList>

        {/* ── roster (the classic students module) ── */}
        <TabsContent value="roster" className="mt-4">
      <ModuleHeader
        title="Students"
        description={`${rows.length} learners on roll · every child carries a national Nyansapo Index that follows them across schools`}
        actions={
          <>
            <Button size="sm" variant="outline" className="gap-1.5" onClick={exportCsv}><Download className="size-4" /> Export CSV</Button>
            <Button size="sm" variant="outline" className="gap-1.5" onClick={() => setPromoOpen(true)}><ArrowUpRight className="size-4" /> Promotion</Button>
            <Button size="sm" className="gap-1.5" onClick={() => setAddOpen(true)}><UserPlus className="size-4" /> Enrol learner</Button>
          </>
        }
      />

      <FilterBar active={filtersActive} onClear={clearFilters} count={filtered.length} total={rows.length} label="Roster filters">
        <FilterSearch value={search} onChange={setSearch} placeholder="Name, index, guardian, MoMo…" className="min-w-52" />
        <FilterSelect label="Class" value={classId} onChange={setClassId}
          options={[{ value: "all", label: "All classes" }, ...classrooms.map((c) => ({ value: c.id, label: c.name }))]} />
        <FilterSelect label="Gender" value={gender} onChange={setGender}
          options={[{ value: "all", label: "All" }, { value: "M", label: "Boys" }, { value: "F", label: "Girls" }]} />
        <FilterSelect label="Fees" value={feeState} onChange={setFeeState}
          options={[{ value: "all", label: "All" }, { value: "owing", label: "Owing" }, { value: "settled", label: "Settled" }, { value: "overdue", label: "Overdue" }]} />
        {/* iEduPro roster views: on roll / muted / alumni */}
        <div className="inline-flex rounded-lg border bg-muted/40 p-0.5 ml-auto" role="group" aria-label="Roster view">
          {([
            ["ACTIVE", "On roll"],
            ["WITHDRAWN", "Muted"],
            ["GRADUATED", "Alumni"],
          ] as const).map(([k, label]) => (
            <button
              key={k}
              onClick={() => setStatusView(k)}
              aria-pressed={statusView === k}
              className={cn(
                "rounded-md px-3 py-1.5 text-xs font-semibold transition-all",
                statusView === k ? "bg-primary text-primary-foreground shadow-sm" : "text-muted-foreground hover:bg-background hover:text-foreground",
              )}
            >{label}</button>
          ))}
        </div>
      </FilterBar>

      <Card>
        <CardContent className="p-0">
          <div className="overflow-x-auto nice-scroll">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-secondary/50 text-left">
                  <th className="px-3 py-2.5 font-medium">Learner</th>
                  <th className="px-3 py-2.5 font-medium hidden md:table-cell">Index</th>
                  <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Class</th>
                  <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Guardian</th>
                  <th className="px-3 py-2.5 font-medium text-right">Balance</th>
                  <th className="px-3 py-2.5 font-medium hidden md:table-cell">Flags</th>
                  <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Risk</th>
                  <th className="px-3 py-2.5"></th>
                </tr>
              </thead>
              <tbody>
                {filtered.map((r) => {
                  const bal = (r.invoice?.billed ?? 0) - (r.invoice?.paid ?? 0);
                  return (
                    <tr key={r.id} className="border-b last:border-0 hover:bg-secondary/30 cursor-pointer" onClick={() => openProfile(r)}>
                      <td className="px-3 py-2.5">
                        <div className="flex items-center gap-2.5">
                          <Avatar className="size-8"><AvatarFallback style={{ background: avatarColor(r.name) }} className="text-white text-[11px]">{initials(r.name)}</AvatarFallback></Avatar>
                          <div>
                            <div className="font-medium">{r.name}</div>
                            <div className="text-xs text-muted-foreground">{r.gender === "M" ? "Boy" : "Girl"} · {fmtDate(r.dob)}</div>
                          </div>
                        </div>
                      </td>
                      <td className="px-3 py-2.5 font-mono text-xs text-muted-foreground hidden md:table-cell">{r.nyansapoIndex}</td>
                      <td className="px-3 py-2.5 hidden sm:table-cell">
                        {r.classroom}
                        {r.promotedFrom && <div className="text-[10px] text-amber-600">↑ from {r.promotedFrom}</div>}
                        {r.dietNote && <div className="text-[10px] text-sky-700 dark:text-sky-400">{"🍽 "}{r.dietNote}</div>}
                      </td>
                      <td className="px-3 py-2.5 hidden lg:table-cell">
                        <div>{r.guardianName}</div>
                        <div className="text-xs text-muted-foreground">{r.guardianPhone}</div>
                      </td>
                      <td className="px-3 py-2.5 text-right tabular-nums">
                        {bal > 0 ? <span className="font-semibold text-destructive">{fmtCedi(bal)}</span> : <span className="text-emerald-600 font-medium">Settled</span>}
                        {r.invoice && <div className="text-[11px]"><StatusBadge status={r.invoice.status} /></div>}
                      </td>
                      <td className="px-3 py-2.5 hidden md:table-cell">
                        {r.flags.length ? (
                          <div className="flex flex-wrap gap-1">{r.flags.map((f) => <SeverityBadge key={f.id} severity={f.severity} />)}</div>
                        ) : <span className="text-muted-foreground text-xs">—</span>}
                      </td>
                      <td className="px-3 py-2.5 hidden lg:table-cell">{r.risk ? <RiskPill level={r.risk.level} score={r.risk.score} /> : <span className="text-muted-foreground text-xs">—</span>}</td>
                      <td className="px-3 py-2.5 text-right">
                        <Button size="sm" variant="ghost" onClick={(e) => { e.stopPropagation(); setWithdrawFor(r); }} title="Withdraw / transfer out"><LogOut className="size-4" /></Button>
                      </td>
                    </tr>
                  );
                })}
                {!filtered.length && <tr><td colSpan={8}><EmptyState icon={<Search className="size-5" />} title="No learners match" hint="Adjust your search or class filter." /></td></tr>}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
        </TabsContent>

        {/* ── bulk ID cards (iEduPro admin/print_bulk_id) ── */}
        <TabsContent value="idcards" className="mt-4">
          {currentSchoolId ? (
            <IdCardsTab
              key={currentSchoolId}
              schoolId={currentSchoolId}
              classrooms={classrooms}
              fallbackRows={statusView === "ACTIVE" ? rows : null}
              initialClassId={classId !== "all" ? classId : ""}
            />
          ) : (
            <EmptyState title="No school selected" />
          )}
        </TabsContent>
      </Tabs>

      {/* ── 360° profile sheet ── */}
      <Sheet open={!!selected} onOpenChange={(o) => !o && setSelected(null)}>
        <SheetContent side="right" className="w-full sm:max-w-xl p-0 overflow-y-auto nice-scroll">
          {selected && (
            <>
              <SheetHeader className="p-5 pb-4 bg-secondary/40 border-b">
                <SheetTitle className="flex items-center gap-3">
                  <Avatar className="size-11"><AvatarFallback style={{ background: avatarColor(selected.name) }} className="text-white">{initials(selected.name)}</AvatarFallback></Avatar>
                  <div>
                    <div>{selected.name}</div>
                    <div className="text-xs font-normal text-muted-foreground font-mono">{selected.nyansapoIndex}</div>
                  </div>
                </SheetTitle>
                <div className="flex flex-wrap gap-1.5 mt-1">
                  <StatusBadge status={selected.status} />
                  {selected.promotedFrom && <Badge variant="outline" className="gap-1 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-800"><ArrowUpRight className="size-3" /> promoted from {selected.promotedFrom}</Badge>}
                  {selected.flags.map((f) => <Badge key={f.id} variant="destructive" className="gap-1"><ShieldAlert className="size-3" />{FLAG_LABELS[f.type] ?? f.type}</Badge>)}
                  {selected.risk && <RiskPill level={selected.risk.level} score={selected.risk.score} />}
                </div>
                {/* iEduPro per-learner tools: special diet, mute/unmute (temporarily off roll) */}
                <div className="mt-3 pt-3 border-t space-y-2">
                  <DietEditor student={selected} onDone={load} />
                  <div className="flex flex-wrap gap-1.5">
                    {selected.status === "ACTIVE" ? (
                      <Button size="sm" variant="outline" className="h-7 gap-1.5" onClick={() => mute(selected, "Muted from the students module")}><UserX className="size-3.5" /> Mute (off roll)</Button>
                    ) : selected.status === "WITHDRAWN" ? (
                      <Button size="sm" variant="outline" className="h-7 gap-1.5" onClick={() => unmute(selected)}><Undo2 className="size-3.5" /> Restore to roll</Button>
                    ) : null}
                  </div>
                </div>
              </SheetHeader>
              {!profile ? <LoadingBlock label="Pulling full records…" /> : <ProfileTabs profile={profile} onChanged={() => { load(); openProfile(selected); }} />}
            </>
          )}
        </SheetContent>
      </Sheet>

      <EnrolDialog open={addOpen} onOpenChange={setAddOpen} classrooms={classrooms} onDone={load} />
      <WithdrawDialog student={withdrawFor} onOpenChange={(o) => !o && setWithdrawFor(null)} onDone={() => { load(); }} />
      <PromotionWizard open={promoOpen} onOpenChange={(o) => !o && setPromoOpen(false)} rows={rows ?? []} classrooms={classrooms} onDone={load} />
    </div>
  );
}

function ProfileTabs({ profile, onChanged }: { profile: any; onChanged: () => void }) {
  const p = profile;
  const st = p.student;
  const att = p.attendance;
  return (
    <div className="p-5">
      <Tabs defaultValue="overview">
        <TabsList className="w-full flex-wrap h-auto">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="fees">Fees</TabsTrigger>
          <TabsTrigger value="academics">Academics</TabsTrigger>
          <TabsTrigger value="flags">Flags ({p.flags.length})</TabsTrigger>
          <TabsTrigger value="transfers">Network ({p.transfers.length + p.recordRequests.length})</TabsTrigger>
        </TabsList>

        <TabsContent value="overview" className="mt-4 space-y-4">
          <div className="grid grid-cols-2 gap-3 text-sm">
            {[
              { icon: IdCard, l: "Nyansapo Index", v: st.nyansapoIndex },
              { icon: GraduationCap, l: "Gender / DOB", v: `${st.gender === "M" ? "Boy" : "Girl"} · ${fmtDate(st.dateOfBirth)}` },
              { icon: Phone, l: "Guardian", v: `${st.guardianName} (${st.relationship})` },
              { icon: Phone, l: "Guardian MoMo", v: st.guardianPhone },
              { icon: MapPin, l: "Address", v: st.guardianAddress ?? "—" },
              { icon: MapPin, l: "Hometown", v: st.hometown ?? "—" },
            ].map((x) => (
              <div key={x.l} className="rounded-lg border p-3">
                <div className="text-xs text-muted-foreground flex items-center gap-1.5"><x.icon className="size-3.5" /> {x.l}</div>
                <div className="font-medium mt-1 break-words">{x.v}</div>
              </div>
            ))}
          </div>
          {/* father / mother / medical (iEduPro student_add_new parity) — only when captured */}
          {[st.fatherName, st.motherName, st.bloodGroup, st.allergies, st.medicalConditions, st.emergencyContact, st.dietNote].some((v: any) => v) && (
            <div className="rounded-lg border p-4 space-y-2.5">
              <div className="text-sm font-medium">Family &amp; medical</div>
              <div className="grid grid-cols-2 gap-2.5 text-[13px]">
                {st.fatherName && <div><span className="text-muted-foreground">Father</span><div className="font-medium">{st.fatherName}{st.fatherPhone ? ` · ${st.fatherPhone}` : ""}{st.fatherOccupation ? ` · ${st.fatherOccupation}` : ""}</div></div>}
                {st.motherName && <div><span className="text-muted-foreground">Mother</span><div className="font-medium">{st.motherName}{st.motherPhone ? ` · ${st.motherPhone}` : ""}{st.motherOccupation ? ` · ${st.motherOccupation}` : ""}</div></div>}
                {st.nationality && <div><span className="text-muted-foreground">Nationality</span><div className="font-medium">{st.nationality}{st.religion ? ` · ${st.religion}` : ""}</div></div>}
                {st.bloodGroup && <div><span className="text-muted-foreground">Blood group</span><div className="font-medium">{st.bloodGroup}</div></div>}
                {st.allergies && <div><span className="text-muted-foreground">Allergies</span><div className="font-medium break-words">{st.allergies}</div></div>}
                {st.dietNote && <div><span className="text-muted-foreground">Special diet</span><div className="font-medium break-words">{st.dietNote}</div></div>}
                {st.medicalConditions && <div className="col-span-2"><span className="text-muted-foreground">Medical conditions</span><div className="font-medium break-words">{st.medicalConditions}</div></div>}
                {st.emergencyContact && <div className="col-span-2"><span className="text-muted-foreground">Emergency contact</span><div className="font-medium">{st.emergencyContact}</div></div>}
              </div>
            </div>
          )}
          {att.total > 0 && (
            <div className="rounded-lg border p-4">
              <div className="text-sm font-medium mb-2">Attendance (recorded sessions)</div>
              <div className="flex gap-2 flex-wrap text-xs">
                <Badge className="bg-emerald-600">Present {att.present}</Badge>
                <Badge variant="secondary">Late {att.late}</Badge>
                <Badge variant="destructive">Absent {att.absent}</Badge>
                <Badge variant="outline">Sick {att.sick}</Badge>
                <Badge variant="outline">{att.total ? Math.round(((att.present + att.late) / att.total) * 100) : 0}% rate</Badge>
              </div>
            </div>
          )}
          {p.risk && (
            <div className="rounded-lg border border-orange-200 dark:border-orange-900 bg-orange-50 dark:bg-orange-950/40 p-4">
              <div className="flex items-center justify-between">
                <div className="text-sm font-medium">AI risk assessment</div>
                <RiskPill level={p.risk.level} score={p.risk.score} />
              </div>
              <p className="text-xs mt-2 text-muted-foreground">{p.risk.recommendation}</p>
            </div>
          )}
          <div>
            <div className="text-sm font-medium mb-2">Enrolment history</div>
            <div className="space-y-2">
              {p.enrollments.map((e: any) => (
                <div key={e.id} className="rounded-lg border p-3 flex items-start justify-between gap-2">
                  <div>
                    <div className="text-sm font-medium">{e.school.name}</div>
                    <div className="text-xs text-muted-foreground">{e.classroom} · {fmtDate(e.admissionDate)} → {e.exitDate ? fmtDate(e.exitDate) : "present"}</div>
                    {e.exitReason && <div className="text-xs text-destructive mt-0.5">{e.exitReason}</div>}
                  </div>
                  <StatusBadge status={e.status} />
                </div>
              ))}
            </div>
          </div>
        </TabsContent>

        <TabsContent value="fees" className="mt-4 space-y-3">
          {p.invoices.length === 0 && <EmptyState title="No invoices" icon={<Banknote className="size-5" />} />}
          {p.invoices.map((inv: any) => (
            <div key={inv.id} className="rounded-lg border p-3">
              <div className="flex items-center justify-between">
                <div className="text-sm font-medium">{inv.term}</div>
                <StatusBadge status={inv.status} />
              </div>
              <div className="grid grid-cols-3 gap-2 mt-2 text-xs">
                <div><div className="text-muted-foreground">Billed</div><div className="font-semibold">{fmtCedi(inv.total)}</div></div>
                <div><div className="text-muted-foreground">Paid</div><div className="font-semibold text-emerald-600">{fmtCedi(inv.paid)}</div></div>
                <div><div className="text-muted-foreground">Balance</div><div className="font-semibold text-destructive">{fmtCedi(inv.balance)}</div></div>
              </div>
              {inv.discount > 0 && <div className="text-[11px] mt-1.5 text-muted-foreground">Discount applied: {fmtCedi(inv.discount)}</div>}
              {inv.payments.length > 0 && (
                <div className="mt-2 pt-2 border-t space-y-1">
                  {inv.payments.map((pay: any) => (
                    <div key={pay.receiptNo} className="flex justify-between text-[11px] text-muted-foreground">
                      <span>{pay.receiptNo} · {PAYMENT_LABELS[pay.method] ?? pay.method}</span>
                      <span>{fmtCedi(pay.amount)} · {fmtDate(pay.paidAt)}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>
          ))}
        </TabsContent>

        <TabsContent value="academics" className="mt-4 space-y-4">
          {p.academics.length > 0 && (
            <div className="rounded-lg border p-4">
              <div className="text-sm font-medium mb-3 flex items-center gap-2"><ClipboardList className="size-4" /> Current-term subject averages</div>
              <div className="space-y-2">
                {p.academics.map((a: any) => (
                  <div key={a.subject} className="flex items-center gap-2">
                    <span className="text-xs w-40 truncate">{a.subject}</span>
                    <div className="flex-1 h-2 rounded-full bg-secondary overflow-hidden"><div className="h-full bg-primary rounded-full" style={{ width: `${a.pct}%` }} /></div>
                    <span className="text-xs font-semibold tabular-nums w-9 text-right">{a.pct}%</span>
                  </div>
                ))}
              </div>
            </div>
          )}
          <div className="text-sm font-medium flex items-center gap-2"><GraduationCap className="size-4" /> Report cards</div>
          {p.reportCards.length === 0 && <EmptyState title="No report cards yet" hint="Generate from Exams & Reports at term end." />}
          {p.reportCards.map((rc: any) => (
            <div key={rc.id} className="rounded-lg border p-3">
              <div className="flex items-center justify-between">
                <div className="text-sm font-medium">{rc.term}</div>
                <div className="flex gap-1.5 items-center">{rc.published ? <Badge>Published</Badge> : <Badge variant="secondary">Draft</Badge>}</div>
              </div>
              <div className="text-xs text-muted-foreground mt-1">Average {rc.average}% · Position {rc.position} of {rc.classSize} · {rc.classroom}</div>
              {rc.teacherRemark && <p className="text-xs mt-2 italic text-muted-foreground">“{rc.teacherRemark}”</p>}
              <div className="mt-2 grid grid-cols-2 sm:grid-cols-3 gap-1.5">
                {rc.results.map((r: any) => (
                  <div key={r.subject} className="rounded bg-secondary/60 px-2 py-1 text-[11px] flex justify-between gap-1">
                    <span className="truncate">{r.subject}</span><span className="font-semibold">{r.total}%</span>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </TabsContent>

        <TabsContent value="flags" className="mt-4 space-y-3">
          {p.flags.length === 0 && <EmptyState icon={<Flag className="size-5" />} title="No flags" hint="A clean record across the network." />}
          {p.flags.map((f: any) => (
            <div key={f.id} className="rounded-lg border p-3">
              <div className="flex items-center justify-between gap-2 flex-wrap">
                <div className="text-sm font-medium">{FLAG_LABELS[f.type] ?? f.type} {f.amountOwed ? `— ${fmtCedi(f.amountOwed)}` : ""}</div>
                <div className="flex gap-1.5"><SeverityBadge severity={f.severity} /><StatusBadge status={f.status} /></div>
              </div>
              <p className="text-xs mt-1.5 text-muted-foreground">{f.description}</p>
              <div className="text-[11px] text-muted-foreground mt-1.5">Raised by {f.raisedByName} · {f.school.name} · {fmtDate(f.raisedAt)}</div>
              {f.resolvedAt && <div className="text-[11px] text-emerald-600 mt-1">Resolved {fmtDate(f.resolvedAt)} — {f.resolutionNote}</div>}
              {f.verifications?.map((v: any) => (
                <div key={v.id} className="mt-2 rounded bg-secondary/60 px-2.5 py-1.5 text-[11px]">
                  <b>{v.action}</b> by {v.officerName} ({v.officerTitle}) — {v.note}
                </div>
              ))}
            </div>
          ))}
        </TabsContent>

        <TabsContent value="transfers" className="mt-4 space-y-4">
          <div>
            <div className="text-sm font-medium mb-2 flex items-center gap-2"><ArrowLeftRight className="size-4" /> Transfer history</div>
            {p.transfers.length === 0 && <EmptyState title="No transfers recorded" />}
            {p.transfers.map((t: any) => (
              <div key={t.id} className="rounded-lg border p-3 mb-2">
                <div className="flex items-center justify-between">
                  <div className="text-sm">{t.from} → {t.to}</div>
                  <StatusBadge status={t.status} />
                </div>
                <div className="text-xs text-muted-foreground mt-1">{fmtDate(t.date)} · {t.reason ?? "—"}{t.outstanding ? ` · ${fmtCedi(t.outstanding)} outstanding` : ""}</div>
              </div>
            ))}
          </div>
          <div>
            <div className="text-sm font-medium mb-2">Record requests</div>
            {p.recordRequests.length === 0 && <EmptyState title="No record requests" />}
            {p.recordRequests.map((r: any) => (
              <div key={r.id} className="rounded-lg border p-3 mb-2 flex items-center justify-between">
                <div className="text-sm">{r.from} → {r.to}</div>
                <div className="flex items-center gap-2"><span className="text-xs text-muted-foreground">{fmtDate(r.requestedAt)}</span><StatusBadge status={r.status} /></div>
              </div>
            ))}
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}

function EnrolDialog({ open, onOpenChange, classrooms, onDone }: { open: boolean; onOpenChange: (o: boolean) => void; classrooms: { id: string; name: string }[]; onDone: () => void }) {
  const { currentSchoolId, online, enqueue } = useStore();
  const [busy, setBusy] = useState(false);
  const [form, setForm] = useState({ firstName: "", lastName: "", gender: "M", dateOfBirth: "", guardianName: "", guardianPhone: "", guardianOccupation: "", relationship: "Parent", classroomId: "" });

  async function submit() {
    if (!form.firstName || !form.lastName || !form.guardianName || !form.guardianPhone || !form.classroomId) {
      toast({ title: "Missing fields", description: "First name, surname, guardian name, MoMo number and class are required.", variant: "destructive" });
      return;
    }
    setBusy(true);
    try {
      if (!online) {
        // collision-proof clientId (same scheme as apiPostQueueable) — two
        // enrolments queued in the same millisecond must never share an id
        enqueue({ clientId: `q-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`, kind: "student", payload: { ...form, schoolId: currentSchoolId }, label: `Enrol ${form.firstName} ${form.lastName}`, queuedAt: Date.now() });
        toast({ title: "Queued offline", description: "The enrolment will sync when you are back online." });
      } else {
        const res = await apiPost<any>(`/api/school/${currentSchoolId}/students`, { action: "create", ...form });
        toast({ title: "Learner enrolled", description: `${res.student.firstName} ${res.student.lastName} now carries index ${res.student.nyansapoIndex}. Invoice generated for the current term.` });
      }
      onOpenChange(false);
      setForm({ firstName: "", lastName: "", gender: "M", dateOfBirth: "", guardianName: "", guardianPhone: "", guardianOccupation: "", relationship: "Parent", classroomId: "" });
      onDone();
    } catch (e: any) {
      toast({ title: "Enrolment failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle>Enrol a new learner</DialogTitle>
          <DialogDescription>They will be registered on the national Nyansapo Index and billed for the current term.</DialogDescription>
        </DialogHeader>
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-1.5"><Label>First name *</Label><Input value={form.firstName} onChange={(e) => setForm({ ...form, firstName: e.target.value })} /></div>
          <div className="space-y-1.5"><Label>Surname *</Label><Input value={form.lastName} onChange={(e) => setForm({ ...form, lastName: e.target.value })} /></div>
          <div className="space-y-1.5">
            <Label>Gender</Label>
            <Select value={form.gender} onValueChange={(v) => setForm({ ...form, gender: v })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent><SelectItem value="M">Boy</SelectItem><SelectItem value="F">Girl</SelectItem></SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5"><Label>Date of birth</Label><DateField value={form.dateOfBirth} onChange={(v) => setForm({ ...form, dateOfBirth: v ?? "" })} /></div>
          <div className="space-y-1.5"><Label>Guardian name *</Label><Input value={form.guardianName} onChange={(e) => setForm({ ...form, guardianName: e.target.value })} /></div>
          <div className="space-y-1.5"><Label>Guardian MoMo *</Label><Input placeholder="+233 24 000 0000" value={form.guardianPhone} onChange={(e) => setForm({ ...form, guardianPhone: e.target.value })} /></div>
          <div className="space-y-1.5"><Label>Occupation</Label><Input value={form.guardianOccupation} onChange={(e) => setForm({ ...form, guardianOccupation: e.target.value })} /></div>
          <div className="space-y-1.5">
            <Label>Relationship</Label>
            <Select value={form.relationship} onValueChange={(v) => setForm({ ...form, relationship: v })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent><SelectItem value="Parent">Parent</SelectItem><SelectItem value="Father">Father</SelectItem><SelectItem value="Mother">Mother</SelectItem><SelectItem value="Guardian">Guardian</SelectItem></SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5 sm:col-span-2">
            <Label>Class *</Label>
            <Select value={form.classroomId} onValueChange={(v) => setForm({ ...form, classroomId: v })}>
              <SelectTrigger><SelectValue placeholder="Select class" /></SelectTrigger>
              <SelectContent>{classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent>
            </Select>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Enrolling…" : "Enrol learner"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function WithdrawDialog({ student, onOpenChange, onDone }: { student: StudentRow | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const [flag, setFlag] = useState(true);
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);
  const balance = student?.invoice ? student.invoice.billed - student.invoice.paid : 0;

  async function submit() {
    if (!student) return;
    setBusy(true);
    try {
      await apiPost(`/api/student/${student.id}`, {
        action: "withdraw", reason: "transfer", flag,
        amountOwedCents: balance, exitNote: note || undefined,
        flagDescription: note || `Withdrawn with outstanding balance of ${fmtCedi(balance)}.`,
        severity: balance > 100000 ? "CRITICAL" : "HIGH",
      });
      toast({ title: "Withdrawal recorded", description: flag ? "A defaulter flag is now visible to the association and any school the learner applies to." : "Clean withdrawal recorded." });
      onOpenChange(false); onDone();
    } catch (e: any) {
      toast({ title: "Failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!student} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Withdraw / transfer out — {student?.name}</DialogTitle>
          <DialogDescription>
            Current balance: <b className={balance > 0 ? "text-destructive" : "text-emerald-600"}>{fmtCedi(balance)}</b>. Withdrawing with a balance raises a network flag unless resolved.
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="flex items-start gap-2.5 rounded-lg border border-destructive/40 bg-destructive/5 p-3">
            <input type="checkbox" id="flagCheck" checked={flag} onChange={(e) => setFlag(e.target.checked)} className="mt-1" />
            <Label htmlFor="flagCheck" className="font-normal cursor-pointer">
              <b>Raise WITHDREW_OWING flag</b> ({fmtCedi(balance)}) — appears instantly on the national registry and at any school the guardian applies to, until resolved.
            </Label>
          </div>
          <div className="space-y-1.5">
            <Label>Note (reason / remarks)</Label>
            <Textarea rows={3} value={note} onChange={(e) => setNote(e.target.value)} placeholder="e.g. Family relocating to Kumasi; balance of ₵1,850.00 outstanding after three reminders." />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button variant="destructive" onClick={submit} disabled={busy}>{busy ? "Processing…" : flag ? "Withdraw & flag" : "Withdraw"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── special-diet editor (iEduPro student_on_special_diet parity) ── */
function DietEditor({ student, onDone }: { student: StudentRow; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [note, setNote] = useState(student.dietNote ?? "");
  const [busy, setBusy] = useState(false);

  async function save() {
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/students`, { action: "set-diet", studentId: student.id, dietNote: note });
      toast({ title: note.trim() ? "Special diet recorded" : "Diet note cleared", description: note.trim() || "—" });
      onDone();
    } catch (e: any) { toast({ title: "Could not save", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <div className="rounded-lg border bg-card p-2.5">
      <div className="flex items-center gap-2 text-sm font-medium"><Utensils className="size-4 text-sky-600" /> Special diet</div>
      <div className="mt-1.5 flex gap-1.5">
        <Input
          value={note}
          onChange={(e) => setNote(e.target.value)}
          placeholder="e.g. no pork (halal) · lactose-free · vegetarian"
          className="h-8"
        />
        <Button size="sm" className="h-8" disabled={busy || note === (student.dietNote ?? "")} onClick={save}>Save</Button>
      </div>
      <p className="text-[11px] text-muted-foreground mt-1">Shown on the class list & feeding rota — the kitchen sees it at lunch service.</p>
    </div>
  );
}

/* ── promotion wizard (iEduPro student_promotion parity): pick a class,
     review its learners, move them up — or graduate the finalists ── */
function PromotionWizard({ open, onOpenChange, rows, classrooms, onDone }: { open: boolean; onOpenChange: (o: boolean) => void; rows: StudentRow[]; classrooms: { id: string; name: string }[]; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [fromId, setFromId] = useState("");
  const [toId, setToId] = useState("");
  const [picked, setPicked] = useState<Set<string>>(new Set());
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (open) { setFromId(""); setToId(""); setPicked(new Set()); }
  }, [open]);

  const from = classrooms.find((c) => c.id === fromId);
  const to = classrooms.find((c) => c.id === toId);
  const cohort = useMemo(() => (fromId ? rows.filter((r) => r.classroomId === fromId && r.status === "ACTIVE") : []), [rows, fromId]);
  const graduateMode = !toId;

  async function run() {
    if (!fromId || !picked.size) return;
    setBusy(true);
    try {
      const r = await apiPost<any>(`/api/school/${currentSchoolId}/students`, {
        action: "promote", studentIds: [...picked], toClassroomId: toId || undefined, graduateInstead: graduateMode || undefined,
      });
      toast({
        title: graduateMode ? "Cohort graduated" : "Cohort promoted",
        description: r.graduated ? `${r.graduated} alumni (off roll, ${r.onRoll} remaining)` : `${r.moved} moved to ${to?.name} — from ${from?.name}`,
      });
      onOpenChange(false);
      onDone();
    } catch (e: any) { toast({ title: "Promotion failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-xl max-h-[92vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><ArrowUpRight className="size-4" /> Promotion</DialogTitle>
          <DialogDescription>Move a cohort up to the next class — or graduate the finalists to alumni (iEduPro student_promotion).</DialogDescription>
        </DialogHeader>
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-1.5">
            <Label>From class</Label>
            <Select value={fromId} onValueChange={(v) => { setFromId(v); setPicked(new Set()); }}>
              <SelectTrigger><SelectValue placeholder="Pick class" /></SelectTrigger>
              <SelectContent>
                {classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>To class — leave empty to graduate</Label>
            <Select value={toId} onValueChange={setToId}>
              <SelectTrigger><SelectValue placeholder="Graduate (alumni)" /></SelectTrigger>
              <SelectContent>
                {classrooms.filter((c) => c.id !== fromId).map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
        </div>
        {fromId && (
          <div className="space-y-2">
            <div className="flex items-center justify-between text-sm">
              <span className="font-medium">{cohort.length} on roll in {from?.name}</span>
              <div className="flex gap-1.5">
                <Button size="sm" variant="ghost" className="h-7" onClick={() => setPicked(new Set(cohort.map((r) => r.id)))}>All</Button>
                <Button size="sm" variant="ghost" className="h-7" onClick={() => setPicked(new Set())}>None</Button>
              </div>
            </div>
            <div className="max-h-64 overflow-y-auto nice-scroll rounded-xl border divide-y">
              {cohort.map((r) => {
                const on = picked.has(r.id);
                return (
                  <button
                    key={r.id}
                    onClick={() => setPicked((p) => { const n = new Set(p); on ? n.delete(r.id) : n.add(r.id); return n; })}
                    className={cn("w-full flex items-center gap-2.5 px-3 py-2 text-left text-sm transition-colors", on ? "bg-primary/10" : "hover:bg-secondary/40")}
                  >
                    <CheckSquare className={cn("size-4", on ? "text-primary" : "text-muted-foreground/50")} />
                    <span className="flex-1 truncate">{r.name}</span>
                    <span className="text-[11px] text-muted-foreground">{r.dietNote ? "🍽 " : ""}{r.nyansapoIndex}</span>
                  </button>
                );
              })}
              {!cohort.length && <div className="p-3 text-sm text-muted-foreground">No active learners in this class.</div>}
            </div>
            <p className="text-xs text-muted-foreground">
              {graduateMode
                ? "No destination class chosen — the selected learners will be graduated to alumni (off roll)."
                : `Selected learners move to ${to?.name ?? "—"}. Their placement is stamped "promoted from {from}".`}
            </p>
          </div>
        )}
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={run} disabled={busy || !fromId || !picked.size} className="gap-1.5">
            {busy ? "Working…" : graduateMode ? `Graduate ${picked.size}` : `Promote ${picked.size}`}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── bulk ID cards (iEduPro admin/print_bulk_id parity): sheets of learner
   ID cards — school header strip, avatar, class, guardian contact and a
   barcode of the Nyansapo Index (the scanner's payload). 10 cards / sheet. ── */
function IdCardsTab({ schoolId, classrooms, fallbackRows, initialClassId }: {
  schoolId: string;
  classrooms: { id: string; name: string }[];
  fallbackRows: StudentRow[] | null;
  initialClassId: string;
}) {
  const { schools } = useStore();
  const [rows, setRows] = useState<StudentRow[] | null>(fallbackRows);
  const [pickedClassId, setPickedClassId] = useState("");

  // fresh on-roll roster for the cards (reuses the existing students API);
  // remounted per school via key= so a single mount-time fetch is enough
  useEffect(() => {
    apiGet<any>(`/api/school/${schoolId}/students?status=ACTIVE`)
      .then((d) => setRows(d.students))
      .catch((e) => toast({ title: "Could not load the roster", description: e.message, variant: "destructive" }));
  }, [schoolId]);

  const classId = pickedClassId || initialClassId || classrooms[0]?.id || "";
  const className = classrooms.find((c) => c.id === classId)?.name;
  const schoolName = schools.find((s) => s.id === schoolId)?.name ?? "Nyansapo School";

  const classRoster = useMemo(
    () => (rows ?? []).filter((r) => (classId ? r.classroomId === classId : true)),
    [rows, classId],
  );
  // 10 cards per printed sheet (2 × 5)
  const sheets = useMemo(() => {
    const out: StudentRow[][] = [];
    for (let i = 0; i < classRoster.length; i += 10) out.push(classRoster.slice(i, i + 10));
    return out;
  }, [classRoster]);

  function downloadCsv() {
    if (!classRoster.length) return;
    const header = ["Index (barcode payload)", "Name", "Class", "Guardian", "Guardian Phone", "Gender"];
    const lines = classRoster.map((r) => [r.nyansapoIndex, r.name, r.classroom, r.guardianName, r.guardianPhone, r.gender]);
    const csv = [header, ...lines].map((l) => l.map((c) => `"${String(c).replace(/"/g, '""')}"`).join(",")).join("\n");
    const slug = className ? className.toLowerCase().replace(/[^a-z0-9]+/g, "-") : "all-classes";
    const url = URL.createObjectURL(new Blob([csv], { type: "text/csv" }));
    const a = document.createElement("a"); a.href = url; a.download = `nyansapo-id-cards-${slug}.csv`; a.click(); URL.revokeObjectURL(url);
  }

  return (
    <div>
      <ModuleHeader
        title="Bulk ID Cards"
        description="Print class sheets of learner ID cards — kente-accented school strip, avatar, class and guardian contact, with a barcode of the Nyansapo Index the gate scanner reads. 10 cards per sheet."
        actions={
          <div className="no-print flex items-center gap-2">
            <Button size="sm" variant="outline" className="gap-1.5" onClick={downloadCsv} disabled={!classRoster.length}>
              <Download className="size-4" /> Download CSV
            </Button>
            <Button size="sm" className="gap-1.5" onClick={() => window.print()} disabled={!classRoster.length}>
              <Printer className="size-4" /> Print sheet{sheets.length > 1 ? `s (${sheets.length})` : ""}
            </Button>
          </div>
        }
      />

      <div className="no-print flex flex-wrap items-center gap-2 mb-4">
        <Select value={classId} onValueChange={setPickedClassId}>
          <SelectTrigger className="w-56"><SelectValue placeholder="Select a class" /></SelectTrigger>
          <SelectContent>
            {classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
          </SelectContent>
        </Select>
        <Badge variant="secondary">{classRoster.length} cards · {sheets.length} sheet{sheets.length === 1 ? "" : "s"}</Badge>
        <span className="text-xs text-muted-foreground">Print opens your browser dialog — only the card sheets are printed.</span>
      </div>

      {!rows ? (
        <LoadingBlock label="Loading roster…" />
      ) : !classRoster.length ? (
        <Card>
          <CardContent>
            <EmptyState icon={<IdCard className="size-5" />} title="No active learners" hint="Pick another class — cards are generated for learners on roll." />
          </CardContent>
        </Card>
      ) : (
        <div className="print-area space-y-6">
          {sheets.map((sheet, si) => (
            <div key={si} className="print-sheet rounded-xl border border-dashed bg-muted/20 p-3 print:bg-transparent">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 print:grid-cols-2">
                {sheet.map((s) => <LearnerIdCard key={s.id} s={s} schoolName={schoolName} />)}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

/* a single learner card — the barcode payload is the Nyansapo Index */
function LearnerIdCard({ s, schoolName }: { s: StudentRow; schoolName: string }) {
  return (
    <div className="print-avoid-break rounded-lg border bg-card shadow-sm overflow-hidden">
      <div className="bg-primary px-2.5 pt-1.5 pb-1">
        <div className="text-[11px] font-bold uppercase tracking-wide text-primary-foreground truncate leading-tight">{schoolName}</div>
        <div className="text-[8px] uppercase tracking-[0.18em] text-primary-foreground/80">Nyansapo Learner ID</div>
      </div>
      <div className="kente-strip h-1" aria-hidden />
      <div className="p-2.5 flex items-center gap-2.5">
        <Avatar className="size-14 rounded-md">
          <AvatarFallback style={{ background: avatarColor(s.name) }} className="rounded-md text-white text-lg font-semibold">{initials(s.name)}</AvatarFallback>
        </Avatar>
        <div className="min-w-0 flex-1">
          <div className="font-bold text-sm leading-tight truncate">{s.name}</div>
          <div className="text-xs text-muted-foreground mt-0.5">{s.classroom}</div>
          <div className="text-[11px] text-muted-foreground truncate mt-0.5">Guardian: {s.guardianName} · {s.guardianPhone}</div>
        </div>
      </div>
      <div className="px-2.5 pb-2.5">
        <BarcodeBars value={s.nyansapoIndex} />
        <div className="text-center font-mono font-bold text-[11px] tracking-[0.12em]">{s.nyansapoIndex}</div>
      </div>
    </div>
  );
}

/* pure-CSS barcode: deterministic bar pattern derived from the index —
   no library, prints crisply (visual stand-in for a Code-128 render) */
function BarcodeBars({ value }: { value: string }) {
  const bars = useMemo(() => {
    const src = value || "NY";
    let h = 7;
    const out: number[] = [];
    for (let i = 0; i < 36; i++) {
      h = (h * 31 + src.charCodeAt(i % src.length)) % 9973;
      out.push((h % 3) + 1);
    }
    return out;
  }, [value]);
  return (
    <div className="flex items-stretch justify-center gap-[1px] h-9 mb-1.5" role="img" aria-label={`Barcode encoding ${value}`}>
      <span className="w-[2px] shrink-0 bg-foreground" />
      {bars.map((w, i) => (
        <span key={i} className="shrink-0 bg-foreground" style={{ width: `${w}px`, marginRight: i % 6 === 5 ? "2px" : undefined }} />
      ))}
      <span className="w-[2px] shrink-0 bg-foreground" />
    </div>
  );
}

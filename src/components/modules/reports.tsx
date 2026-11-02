"use client";
import { Fragment, useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { ModuleHeader, LoadingBlock, EmptyState, StatusBadge } from "@/components/ui-helpers";
import { toast } from "@/hooks/use-toast";
import { fmtDate } from "@/lib/format";
import { GraduationCap, Sparkles, Printer, Wand2, Send, RefreshCcw, Layers, FileText, Pencil, Save, Plus, Trash2, Eye, BookMarked } from "lucide-react";
import { ClassicSheet, ModernSheet, CompactSheet, type SheetData } from "@/components/report-sheets";
import { Textarea } from "@/components/ui/textarea";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";

type SheetStyle = "classic" | "modern" | "compact";
const STYLE_BUTTONS: { id: SheetStyle; label: string }[] = [
  { id: "classic", label: "Classic GES" },
  { id: "compact", label: "Primary / KG" },
  { id: "modern", label: "Modern" },
];
function StylePicker({ style, set }: { style: SheetStyle; set: (s: SheetStyle) => void }) {
  return <span className="flex gap-1 flex-wrap">{STYLE_BUTTONS.map((b) => (
    <Button key={b.id} size="sm" variant={style === b.id ? "default" : "outline"} className="h-7 text-xs" onClick={() => set(b.id)}>{b.label}</Button>
  ))}</span>;
}

type RepData = {
  terms: { id: string; name: string; isCurrent: boolean }[];
  termId: string;
  classrooms: { id: string; name: string }[];
  reportCards: {
    id: string; student: string; studentId: string; classroom: string; average: number | null; position: number | null;
    classSize: number | null; conduct: string | null; interest: string | null; promotion: string | null; published: boolean;
    teacherRemark: string | null; headRemark: string | null; aiGenerated: boolean; attendance: string | null; subjectCount: number;
  }[];
};

export function Reports() {
  const { currentSchoolId, navTarget } = useStore();
  const [data, setData] = useState<RepData | null>(null);
  const [termId, setTermId] = useState<string | null>(null);
  const [classId, setClassId] = useState<string | null>(null);
  const [selected, setSelected] = useState<RepData["reportCards"][0] | null>(null);
  const [detail, setDetail] = useState<any | null>(null);
  const [sheetStyle, setSheetStyle] = useState<SheetStyle>("classic");
  const [school, setSchool] = useState<SheetData["school"] | null>(null);
  const [busy, setBusy] = useState<string | null>(null);
  const [bulk, setBulk] = useState<{ loading: boolean; cards: SheetData[] } | null>(null);
  const [editRemarks, setEditRemarks] = useState(false);
  const [draft, setDraft] = useState<{ teacherRemark: string; headRemark: string; conduct: string }>({ teacherRemark: "", headRemark: "", conduct: "" });
  const [tab, setTab] = useState("cards");

  /* iEduPro nested nav: preselect Cumulative / Question Papers tabs on deep link */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab === "cumulative") setTab("cumulative");
    if (navTarget?.tab === "papers") setTab("papers");
  }

  const load = (t?: string) => {
    if (!currentSchoolId) return;
    apiGet<RepData>(`/api/school/${currentSchoolId}/reports${t ? `?termId=${t}` : ""}`)
      .then((d) => { setData(d); setTermId(d.termId); })
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
    apiGet<any>(`/api/school/${currentSchoolId}/settings`)
      .then((s) => setSchool({ name: s.school.name, motto: s.school.motto, town: s.school.town, region: s.school.region, phone: s.school.phone }))
      .catch(() => null);
  };
  useEffect(() => { load(); }, [currentSchoolId]);

  async function generate() {
    if (!currentSchoolId || !classId || !termId) { toast({ title: "Choose a class first" }); return; }
    setBusy("generate");
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/reports`, { action: "generate", classroomId: classId, termId });
      toast({ title: `Report cards generated for ${res.created} learners`, description: "Grades computed with the school's curriculum scale, positions and promotion status." });
      load(termId);
    } catch (e: any) {
      toast({ title: "Generation failed", description: e.message, variant: "destructive" });
    } finally { setBusy(null); }
  }

  async function aiRemark(cardId: string) {
    if (!currentSchoolId) return;
    setBusy(cardId);
    try {
      const res = await apiPost<any>(`/api/ai/remark`, { reportCardId: cardId });
      toast({ title: "AI remarks drafted", description: "Review and edit before publishing." });
      if (detail?.id === cardId) setDetail({ ...detail, teacherRemark: res.teacherRemark, headRemark: res.headRemark, aiGenerated: true });
      load(termId ?? undefined);
    } catch (e: any) {
      toast({ title: "AI remark failed", description: e.message, variant: "destructive" });
    } finally { setBusy(null); }
  }

  async function publish(cardId?: string) {
    if (!currentSchoolId) return;
    setBusy(cardId ?? "publish");
    try {
      await apiPost(`/api/school/${currentSchoolId}/reports`, cardId ? { action: "publish", reportCardId: cardId } : { action: "publish-all", termId });
      toast({ title: cardId ? "Report card published" : "All report cards published", description: "Guardians can now be issued printed copies." });
      load(termId ?? undefined);
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(null); }
  }

  async function openCard(card: RepData["reportCards"][0]) {
    setSelected(card); setDetail(null); setEditRemarks(false);
    const full = await apiGet<any>(`/api/student/${card.studentId}`);
    const rc = full.reportCards.find((r: any) => r.id === card.id);
    setDetail({ ...card, results: rc?.results ?? [], student: full.student, attendanceSummary: rc?.attendance });
    setDraft({ teacherRemark: card.teacherRemark ?? "", headRemark: card.headRemark ?? "", conduct: card.conduct ?? "" });
  }

  /* save edited remarks / conduct straight onto the report card */
  async function saveRemarks() {
    if (!currentSchoolId || !detail) return;
    setBusy("remarks");
    try {
      await apiPost(`/api/school/${currentSchoolId}/reports`, { action: "update-remarks", reportCardId: detail.id, teacherRemark: draft.teacherRemark, headRemark: draft.headRemark, conduct: draft.conduct });
      toast({ title: "Remarks saved", description: "The report sheet now carries the updated remarks." });
      setDetail({ ...detail, teacherRemark: draft.teacherRemark, headRemark: draft.headRemark, conduct: draft.conduct });
      setEditRemarks(false);
      load(termId ?? undefined);
    } catch (e: any) { toast({ title: "Save failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(null); }
  }

  /* bulk: print the whole class's report sheets in one go */
  async function printAll() {
    if (!currentSchoolId || !classId) { toast({ title: "Choose a class first" }); return; }
    setBulk({ loading: true, cards: [] });
    try {
      const cards: SheetData[] = [];
      const list = filtered;
      for (const rc of list) {
        const full = await apiGet<any>(`/api/student/${rc.studentId}`);
        const rcd = full.reportCards.find((r: any) => r.id === rc.id);
        cards.push({
          student: { name: `${full.student.firstName} ${full.student.lastName}`, index: full.student.nyansapoIndex, classroom: rc.classroom },
          termName: data!.terms.find((t) => t.id === termId)?.name ?? "",
          curriculum: schoolCurriculum,
          position: rc.position, classSize: rc.classSize, average: rc.average,
          conduct: rc.conduct, interest: rc.interest, promotion: rc.promotion,
          attendanceSummary: rcd?.attendance ?? rc.attendance,
          teacherRemark: rc.teacherRemark, headRemark: rc.headRemark, aiGenerated: rc.aiGenerated, published: rc.published,
          school: school ?? { name: "—" },
          results: rcd?.results ?? [],
        });
      }
      setBulk({ loading: false, cards });
    } catch (e: any) { toast({ title: "Bulk print failed", description: e.message, variant: "destructive" }); setBulk(null); }
  }

  if (!data) return <LoadingBlock label="Loading reports…" />;

  const filtered = classId ? data.reportCards.filter((r) => r.classroom === data.classrooms.find((c) => c.id === classId)?.name) : data.reportCards;
  const schoolCurriculum = (detail?.student?.curriculum as string) || "GES";

  const sheetData: SheetData | null = detail && school ? {
    student: { name: `${detail.student.firstName} ${detail.student.lastName}`, index: detail.student.nyansapoIndex, classroom: detail.classroom },
    termName: data.terms.find((t) => t.id === termId)?.name ?? "",
    curriculum: schoolCurriculum,
    position: detail.position, classSize: detail.classSize, average: detail.average,
    conduct: detail.conduct, interest: detail.interest, promotion: detail.promotion,
    attendanceSummary: detail.attendanceSummary ?? detail.attendance,
    teacherRemark: detail.teacherRemark, headRemark: detail.headRemark,
    aiGenerated: detail.aiGenerated, published: detail.published,
    school, results: detail.results ?? [],
  } : null;

  return (
    <div>
      <ModuleHeader
        title="Exams & Terminal Reports"
        description="Generate positioned report cards graded with your curriculum's scale — GES SBC/CCP proficiency bands, Cambridge or IB. Print classic GES broadsheets or modern cards, one learner or the whole class."
      />

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex-wrap h-auto">
          <TabsTrigger value="cards">Report cards</TabsTrigger>
          <TabsTrigger value="cumulative" className="gap-1.5"><Layers className="size-4" /> Cumulative</TabsTrigger>
          <TabsTrigger value="papers" className="gap-1.5"><BookMarked className="size-4" /> Question papers</TabsTrigger>
        </TabsList>

        {/* ── TERMINAL REPORT CARDS (existing module flow) ── */}
        <TabsContent value="cards" className="mt-4">
          <div className="flex flex-wrap gap-2 mb-4">
            <Select value={termId ?? ""} onValueChange={(v) => { setTermId(v); load(v); }}>
              <SelectTrigger className="w-52"><SelectValue /></SelectTrigger>
              <SelectContent>{data.terms.map((t) => <SelectItem key={t.id} value={t.id}>{t.name}</SelectItem>)}</SelectContent>
            </Select>
            <Select value={classId ?? ""} onValueChange={setClassId}>
              <SelectTrigger className="w-44"><SelectValue placeholder="Class (generate)" /></SelectTrigger>
              <SelectContent>{data.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent>
            </Select>
            <Button size="sm" className="gap-1.5" onClick={generate} disabled={!classId || busy === "generate"}>
              <RefreshCcw className={cn("size-4", busy === "generate" && "animate-spin")} /> Generate for class
            </Button>
            <Button size="sm" variant="outline" className="gap-1.5" onClick={printAll} disabled={!classId || !filtered.length || !!bulk?.loading}>
              <Layers className="size-4" /> {bulk?.loading ? "Preparing…" : "Print whole class"}
            </Button>
          </div>

          {data.reportCards.length === 0 ? (
            <Card>
              <CardContent>
                <EmptyState icon={<GraduationCap className="size-5" />} title="No report cards for this term yet" hint="Pick a class above and hit Generate — marks come from the Gradebook; GES/Cambridge/IB grades, positions and promotion are computed automatically." />
              </CardContent>
            </Card>
          ) : (
            <Card>
              <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
                <CardTitle className="text-base">{data.reportCards.length} report cards · {data.terms.find((t) => t.id === termId)?.name}</CardTitle>
                <Button size="sm" variant="outline" className="gap-1.5" onClick={() => publish()} disabled={busy === "publish"}><Send className="size-4" /> Publish all</Button>
              </CardHeader>
              <CardContent className="p-0">
                <div className="overflow-x-auto nice-scroll max-h-[520px]">
                  <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                      <tr className="text-left border-b">
                        <th className="px-3 py-2.5 font-medium">Learner</th>
                        <th className="px-3 py-2.5 font-medium hidden md:table-cell">Class</th>
                        <th className="px-3 py-2.5 font-medium text-right">Average</th>
                        <th className="px-3 py-2.5 font-medium text-center">Position</th>
                        <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Promotion</th>
                        <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Remarks</th>
                        <th className="px-3 py-2.5"></th>
                      </tr>
                    </thead>
                    <tbody>
                      {filtered.map((rc) => (
                        <tr key={rc.id} className="border-b last:border-0 hover:bg-secondary/30">
                          <td className="px-3 py-2.5 font-medium">{rc.student}</td>
                          <td className="px-3 py-2.5 hidden md:table-cell text-muted-foreground">{rc.classroom}</td>
                          <td className="px-3 py-2.5 text-right font-semibold tabular-nums">{rc.average}%</td>
                          <td className="px-3 py-2.5 text-center">{rc.position} / {rc.classSize}</td>
                          <td className="px-3 py-2.5 hidden lg:table-cell">{rc.promotion ? <StatusBadge status={rc.promotion} /> : "—"}</td>
                          <td className="px-3 py-2.5 hidden sm:table-cell">
                            {rc.teacherRemark ? <Badge variant={rc.aiGenerated ? "default" : "secondary"} className="gap-1">{rc.aiGenerated && <Sparkles className="size-3" />}{rc.aiGenerated ? "AI draft" : "Written"}</Badge> : <span className="text-muted-foreground text-xs">none</span>}
                          </td>
                          <td className="px-3 py-2.5 text-right whitespace-nowrap">
                            <Button size="sm" variant="ghost" className="h-8 gap-1" onClick={() => aiRemark(rc.id)} disabled={busy === rc.id}><Wand2 className="size-3.5" /> {busy === rc.id ? "…" : "AI"}</Button>
                            <Button size="sm" variant="outline" className="h-8" onClick={() => openCard(rc)}>View</Button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        {/* ── CUMULATIVE (multi-term) REPORTS — iEduPro parity ── */}
        <TabsContent value="cumulative" className="mt-4">
          <CumulativeTab />
        </TabsContent>

        {/* ── QUESTION-PAPER BANK — iEduPro parity ── */}
        <TabsContent value="papers" className="mt-4">
          <QuestionPapersTab />
        </TabsContent>
      </Tabs>

      {/* report card viewer with sheet style picker */}
      <Dialog open={!!selected} onOpenChange={(o) => !o && setSelected(null)}>
        <DialogContent className="max-w-3xl max-h-[92vh] overflow-y-auto nice-scroll">
          <DialogHeader className="no-print">
            <DialogTitle className="flex items-center justify-between gap-3 text-base flex-wrap">
              <span>Report sheet — {selected?.student}</span>
              <StylePicker style={sheetStyle} set={setSheetStyle} />
            </DialogTitle>
          </DialogHeader>
          {!sheetData ? <LoadingBlock label="Preparing report sheet…" /> : (
            <>
              <div className="print-area">
                {sheetStyle === "classic" ? <ClassicSheet d={sheetData} /> : sheetStyle === "compact" ? <CompactSheet d={sheetData} /> : <ModernSheet d={sheetData} />}
              </div>

              {/* remark / conduct editor (no-print) */}
              <div className="mt-4 no-print rounded-lg border bg-secondary/30 p-3">
                <button className="flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground" onClick={() => setEditRemarks(!editRemarks)}>
                  <Pencil className="size-3.5" /> {editRemarks ? "Hide remark editor" : "Edit remarks & conduct"}
                </button>
                {editRemarks && (
                  <div className="grid sm:grid-cols-3 gap-3 mt-3">
                    <label className="text-xs space-y-1.5">
                      <span className="font-medium">Class teacher's remarks</span>
                      <Textarea rows={3} value={draft.teacherRemark} onChange={(e) => setDraft({ ...draft, teacherRemark: e.target.value })} placeholder="Shown on every sheet style" />
                    </label>
                    <label className="text-xs space-y-1.5">
                      <span className="font-medium">Head of school's remarks</span>
                      <Textarea rows={3} value={draft.headRemark} onChange={(e) => setDraft({ ...draft, headRemark: e.target.value })} placeholder="Final word on the report" />
                    </label>
                    <label className="text-xs space-y-1.5">
                      <span className="font-medium">Conduct</span>
                      <Textarea rows={3} value={draft.conduct} onChange={(e) => setDraft({ ...draft, conduct: e.target.value })} placeholder="e.g. A — Polite and respectful" />
                    </label>
                    <div className="sm:col-span-3 flex justify-end">
                      <Button size="sm" className="gap-1.5" onClick={saveRemarks} disabled={busy === "remarks"}><Save className="size-3.5" /> {busy === "remarks" ? "Saving…" : "Save to report card"}</Button>
                    </div>
                  </div>
                )}
              </div>
            </>
          )}
          {sheetData && (
            <div className="flex flex-wrap gap-2 justify-end mt-4 no-print">
              <Button size="sm" variant="outline" className="gap-1.5" onClick={() => aiRemark(detail.id)} disabled={busy === detail.id}><Wand2 className="size-4" /> Draft AI remarks</Button>
              <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()}><Printer className="size-4" /> Print</Button>
              {!sheetData.published && <Button size="sm" className="gap-1.5" onClick={() => publish(detail.id)}><Send className="size-4" /> Publish</Button>}
            </div>
          )}
        </DialogContent>
      </Dialog>

      {/* bulk print overlay */}
      {bulk && (
        <Dialog open onOpenChange={() => setBulk(null)}>
          <DialogContent className="max-w-3xl max-h-[92vh] overflow-y-auto nice-scroll">
            <DialogHeader className="no-print">
              <DialogTitle className="flex items-center justify-between text-base flex-wrap gap-2">
                <span className="flex items-center gap-2"><FileText className="size-4" /> Class report sheets ({bulk.cards.length})</span>
                <StylePicker style={sheetStyle} set={setSheetStyle} />
              </DialogTitle>
            </DialogHeader>
            {bulk.loading ? <LoadingBlock label="Fetching every learner's sheet…" /> : (
              <div className="print-area space-y-4">
                <div className="no-print flex justify-end"><Button size="sm" onClick={() => window.print()} className="gap-1.5"><Printer className="size-4" /> Print all {bulk.cards.length}</Button></div>
                {bulk.cards.map((c, i) => sheetStyle === "classic" ? <ClassicSheet key={i} d={c} /> : sheetStyle === "compact" ? <CompactSheet key={i} d={c} /> : <ModernSheet key={i} d={c} />)}
              </div>
            )}
          </DialogContent>
        </Dialog>
      )}
    </div>
  );
}

/* ═══════════════════════════════════════════════════════════════
   CUMULATIVE (MULTI-TERM) REPORT — one row per subject, one column
   group per term (subject totals reuse the reports 30/70 logic),
   term averages + grades, cumulative average footer, and a WAEC
   aggregate column for the current term (BECE rule: red ≥ 15).  */
type CumTerm = { id: string; name: string; isCurrent: boolean; subjects: { subject: string; total: number | null; grade: string | null; numeric: number | null }[]; average: number | null; averageGrade: string | null };
type CumPayload = {
  learner: { id: string; name: string; index?: string; classroom: string };
  subjects: string[];
  terms: CumTerm[];
  cumulative: { average: number | null; grade: string | null; termsCounted: number };
  waecAggregate: number | null;
};

function CumulativeTab() {
  const { currentSchoolId, schools } = useStore();
  const [classId, setClassId] = useState<string | null>(null);
  const [classrooms, setClassrooms] = useState<{ id: string; name: string }[]>([]);
  const [learners, setLearners] = useState<{ id: string; name: string; rollNo: number | null }[] | null>(null);
  const [studentId, setStudentId] = useState<string>(""); // "" none · "bulk" whole class
  const [payloads, setPayloads] = useState<CumPayload[] | null>(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (!currentSchoolId) return;
    apiGet<any>(`/api/school/${currentSchoolId}/ops/academics`)
      .then((d) => setClassrooms(d.classrooms ?? []))
      .catch(() => setClassrooms([]));
  }, [currentSchoolId]);

  /* switching class resets the learner pick + built sheets from the handler
     (never synchronously inside an effect) */
  function pickClass(v: string) {
    setClassId(v);
    setStudentId("");
    setLearners(null);
    setPayloads(null);
  }

  useEffect(() => {
    if (!currentSchoolId || !classId) return;
    apiGet<{ learners: { id: string; name: string; rollNo: number | null }[] }>(`/api/school/${currentSchoolId}/ops/academics?tab=cumulative&classroomId=${classId}`)
      .then((d) => setLearners(d.learners ?? []))
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  }, [currentSchoolId, classId]);

  useEffect(() => {
    if (!currentSchoolId || !classId || !studentId) return;
    let alive = true;
    setBusy(true); // eslint-disable-line react-hooks/set-state-in-effect
    const ids = studentId === "bulk" ? (learners ?? []).map((l) => l.id) : [studentId];
    Promise.all(ids.map((id) => apiGet<CumPayload>(`/api/school/${currentSchoolId}/ops/academics?tab=cumulative&classroomId=${classId}&studentId=${id}`)))
      .then((ps) => { if (alive) setPayloads(ps.filter((p) => p.terms.some((t) => t.subjects.length))); })
      .catch((e) => { if (alive) toast({ title: "Could not build cumulative report", description: e.message, variant: "destructive" }); })
      .finally(() => { if (alive) setBusy(false); });
    return () => { alive = false; };
  }, [currentSchoolId, classId, studentId, learners]);

  const school = schools.find((s) => s.id === currentSchoolId);

  return (
    <div className="space-y-4">
      <Card>
        <CardContent className="pt-4">
          <div className="flex flex-wrap items-end gap-3">
            <div className="space-y-1.5 min-w-56">
              <Label className="text-xs">Class</Label>
              <Select value={classId ?? ""} onValueChange={pickClass}>
                <SelectTrigger><SelectValue placeholder="Select class" /></SelectTrigger>
                <SelectContent>{classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5 min-w-64">
              <Label className="text-xs">Learner</Label>
              <Select value={studentId} onValueChange={setStudentId} disabled={!classId || !learners?.length}>
                <SelectTrigger><SelectValue placeholder={classId ? "Select learner" : "Pick a class first"} /></SelectTrigger>
                <SelectContent>
                  {studentId === "bulk" && <SelectItem value="bulk">Whole class — bulk print ({learners?.length ?? 0})</SelectItem>}
                  {(learners ?? []).map((l) => <SelectItem key={l.id} value={l.id}>{l.rollNo != null ? `${l.rollNo}. ` : ""}{l.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            {learners && learners.length > 0 && studentId !== "bulk" && (
              <Button size="sm" variant="outline" className="gap-1.5" onClick={() => setStudentId("bulk")}><Layers className="size-4" /> Print whole class</Button>
            )}
            {payloads && payloads.length === 1 && (
              <Button size="sm" className="gap-1.5 ml-auto no-print" onClick={() => window.print()}><Printer className="size-4" /> Print report</Button>
            )}
            {payloads && payloads.length > 1 && (
              <Button size="sm" className="gap-1.5 ml-auto no-print" onClick={() => window.print()}><Printer className="size-4" /> Print all {payloads.length}</Button>
            )}
          </div>
          <p className="text-xs text-muted-foreground mt-3">
            Every term of the academic year side by side — subject totals carry the same 30/70 composition as the terminal reports, and the WAEC column sums
            the current term&apos;s grade points (BECE aggregate, red at 15 or more).
          </p>
        </CardContent>
      </Card>

      {busy && <LoadingBlock label="Building cumulative report…" />}
      {!busy && !classId && (
        <Card><CardContent><EmptyState icon={<Layers className="size-5" />} title="Pick a class to build cumulative reports" hint="One sheet per learner — every term on one page." /></CardContent></Card>
      )}
      {!busy && classId && !learners?.length && (
        <Card><CardContent><EmptyState icon={<Layers className="size-5" />} title="No learners enrolled in this class" /></CardContent></Card>
      )}
      {!busy && payloads && payloads.length === 0 && (
        <Card><CardContent><EmptyState icon={<Layers className="size-5" />} title="No recorded marks for this class yet" hint="Enter marks in the Gradebook first — the cumulative sheet fills automatically." /></CardContent></Card>
      )}
      {!busy && payloads && payloads.length > 0 && (
        <div className="print-area space-y-6">
          {payloads.map((p) => <CumulativeSheet key={p.learner.id} d={p} schoolName={school?.name ?? "School"} town={school?.town} region={school?.region} />)}
        </div>
      )}
    </div>
  );
}

function CumulativeSheet({ d, schoolName, town, region }: { d: CumPayload; schoolName: string; town?: string; region?: string }) {
  const cur = d.terms.find((t) => t.isCurrent);
  const cell = (t: CumTerm, subject: string) => t.subjects.find((s) => s.subject === subject);
  return (
    <div className="print-sheet bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden mx-auto max-w-[860px]">
      {/* letterhead */}
      <div className="px-8 pt-6 pb-4 text-center border-b-2 border-foreground/80">
        <div className="font-bold text-xl tracking-tight uppercase leading-tight">{schoolName}</div>
        <div className="text-[11px] text-muted-foreground">{[town, region].filter(Boolean).join(", ")}</div>
        <div className="mt-3 inline-block border-2 border-foreground px-4 py-1 font-bold tracking-[0.2em] text-sm uppercase">Cumulative Report Sheet</div>
        <div className="mt-2 flex flex-wrap justify-center gap-x-6 gap-y-1 text-sm">
          <span><span className="text-muted-foreground">Learner:</span> <b>{d.learner.name}</b></span>
          {d.learner.index && <span><span className="text-muted-foreground">Index:</span> <span className="font-mono">{d.learner.index}</span></span>}
          <span><span className="text-muted-foreground">Class:</span> <b>{d.learner.classroom}</b></span>
        </div>
      </div>

      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="bg-secondary text-left">
              <th className="px-3 py-2 font-semibold border-r" rowSpan={2}>Subject</th>
              {d.terms.map((t) => (
                <th key={t.id} className="px-3 py-2 font-semibold text-center border-r" colSpan={2}>
                  {t.name}{t.isCurrent && <Badge variant="secondary" className="ml-1.5 text-[9px] align-middle">current</Badge>}
                </th>
              ))}
              <th className="px-3 py-2 font-semibold text-center" rowSpan={2}>
                WAEC<br /><span className="text-[10px] font-normal">current term</span>
              </th>
            </tr>
            <tr className="bg-secondary text-left">
              {d.terms.map((t) => (
                <Fragment key={t.id}>
                  <th className="px-3 py-1.5 font-medium text-center border-r text-xs">Total</th>
                  <th className="px-3 py-1.5 font-medium text-center border-r text-xs">Grade</th>
                </Fragment>
              ))}
            </tr>
          </thead>
          <tbody>
            {d.subjects.map((subject) => (
              <tr key={subject} className="border-t">
                <td className="px-3 py-2 font-medium border-r">{subject}</td>
                {d.terms.map((t) => {
                  const c = cell(t, subject);
                  return (
                    <Fragment key={t.id}>
                      <td className="px-3 py-2 text-center tabular-nums border-r">{c?.total ?? <span className="text-muted-foreground">—</span>}{c?.total != null && "%"}</td>
                      <td className="px-3 py-2 text-center font-semibold border-r">{c?.grade ?? <span className="text-muted-foreground font-normal">—</span>}</td>
                    </Fragment>
                  );
                })}
                <td className="px-3 py-2 text-center tabular-nums font-semibold">
                  {cur && cell(cur, subject)?.numeric != null ? cell(cur, subject)!.numeric : <span className="text-muted-foreground font-normal">—</span>}
                </td>
              </tr>
            ))}
          </tbody>
          <tfoot>
            <tr className="border-t bg-secondary/50">
              <td className="px-3 py-2 font-semibold border-r">Term average</td>
              {d.terms.map((t) => (
                <Fragment key={t.id}>
                  <td className="px-3 py-2 text-center font-semibold tabular-nums border-r">{t.average != null ? `${t.average}%` : <span className="text-muted-foreground font-normal">—</span>}</td>
                  <td className="px-3 py-2 text-center border-r">{t.averageGrade ?? <span className="text-muted-foreground">—</span>}</td>
                </Fragment>
              ))}
              <td className={cn("px-3 py-2 text-center font-bold tabular-nums", d.waecAggregate != null && d.waecAggregate >= 15 ? "text-red-600" : "text-emerald-600")}>
                {d.waecAggregate ?? "—"}
              </td>
            </tr>
            <tr className="border-t">
              <td className="px-3 py-2.5 font-semibold" colSpan={d.terms.length * 2 + 1}>
                Cumulative average across {d.cumulative.termsCounted} term{d.cumulative.termsCounted === 1 ? "" : "s"}
              </td>
              <td className="px-3 py-2.5 text-center font-bold text-base tabular-nums">
                {d.cumulative.average != null ? `${d.cumulative.average}%` : "—"}{d.cumulative.grade && <span className="ml-1.5 text-sm">{d.cumulative.grade}</span>}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div className="px-8 py-3 text-[10px] text-muted-foreground border-t">
        Subject totals: class score 30% + end-of-term exam 70% (single-component subjects graded on what exists). WAEC column sums the current
        term&apos;s grade points (A1=1 … F9=9) over all subjects — BECE rule of thumb: an aggregate of 15 or more (red) calls for support.
      </div>
    </div>
  );
}

/* ═══════════════════════════════════════════════════════════════
   QUESTION-PAPER BANK (CI3 question_paper parity) — searchable
   table of exam papers with an HTML body editor, live preview,
   view/print dialog and delete-with-confirm.                      */
type PaperRow = { id: string; title: string; classroomId: string; classroom: string; examName: string | null; authorName: string; body: string; updatedAt: string };

function QuestionPapersTab() {
  const { currentSchoolId, schools } = useStore();
  const [data, setData] = useState<{ papers: PaperRow[]; classrooms: { id: string; name: string }[] } | null>(null);
  const [editing, setEditing] = useState<PaperRow | "new" | null>(null);
  const [viewing, setViewing] = useState<PaperRow | null>(null);
  const [deleting, setDeleting] = useState<PaperRow | null>(null);

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<{ papers: PaperRow[]; classrooms: { id: string; name: string }[] }>(`/api/school/${currentSchoolId}/ops/academics?tab=papers`)
      .then(setData)
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(load, [currentSchoolId]);

  async function remove(p: PaperRow) {
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/academics`, { action: "paper.delete", id: p.id });
      toast({ title: "Question paper deleted", description: p.title });
      setDeleting(null);
      load();
    } catch (e: any) {
      toast({ title: "Delete failed", description: e.message, variant: "destructive" });
    }
  }

  if (!data) return <LoadingBlock label="Loading question-paper bank…" />;
  const school = schools.find((s) => s.id === currentSchoolId);

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="pb-3 flex-row flex-wrap items-center justify-between gap-2 space-y-0">
          <div>
            <CardTitle className="text-base">Question papers ({data.papers.length})</CardTitle>
            <p className="text-xs text-muted-foreground mt-1">The school&apos;s exam paper bank — draft in HTML (sections, mark allocations), preview, print for the exam hall.</p>
          </div>
          <Button size="sm" className="gap-1.5" onClick={() => setEditing("new")}><Plus className="size-4" /> New paper</Button>
        </CardHeader>
        <CardContent className="p-0">
          {data.papers.length === 0 ? (
            <EmptyState icon={<BookMarked className="size-5" />} title="No question papers yet" hint="Create the first paper — the body accepts HTML markup for sections and mark allocations." />
          ) : (
            <div className="overflow-x-auto nice-scroll max-h-[520px]">
              <table className="w-full text-sm">
                <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                  <tr className="text-left border-b">
                    <th className="px-3 py-2.5 font-medium">Title</th>
                    <th className="px-3 py-2.5 font-medium hidden md:table-cell">Class</th>
                    <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Exam</th>
                    <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Author</th>
                    <th className="px-3 py-2.5 font-medium hidden xl:table-cell">Updated</th>
                    <th className="px-3 py-2.5"></th>
                  </tr>
                </thead>
                <tbody>
                  {data.papers.map((p) => (
                    <tr key={p.id} className="border-b last:border-0 hover:bg-secondary/30">
                      <td className="px-3 py-2.5 font-medium">{p.title}</td>
                      <td className="px-3 py-2.5 hidden md:table-cell text-muted-foreground">{p.classroom}</td>
                      <td className="px-3 py-2.5 hidden lg:table-cell text-muted-foreground">{p.examName ?? "—"}</td>
                      <td className="px-3 py-2.5 hidden sm:table-cell text-muted-foreground">{p.authorName}</td>
                      <td className="px-3 py-2.5 hidden xl:table-cell text-muted-foreground">{fmtDate(p.updatedAt)}</td>
                      <td className="px-3 py-2.5 text-right whitespace-nowrap">
                        <Button size="sm" variant="outline" className="h-8 gap-1" onClick={() => setViewing(p)}><Eye className="size-3.5" /> View</Button>
                        <Button size="sm" variant="ghost" className="h-8 gap-1 ml-1" onClick={() => setEditing(p)}><Pencil className="size-3.5" /> Edit</Button>
                        <Button size="sm" variant="ghost" className="h-8 size-8 p-0 text-destructive ml-1" onClick={() => setDeleting(p)} aria-label={`Delete ${p.title}`}><Trash2 className="size-4" /></Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>

      <PaperDialog paper={editing} classrooms={data.classrooms} onOpenChange={(o) => !o && setEditing(null)} onDone={() => { setEditing(null); load(); }} />

      {/* view / print dialog — print CSS prints only the paper */}
      <Dialog open={!!viewing} onOpenChange={(o) => !o && setViewing(null)}>
        <DialogContent className="max-w-3xl max-h-[92vh] overflow-y-auto nice-scroll">
          <DialogHeader className="no-print">
            <DialogTitle className="text-base flex items-center gap-2"><FileText className="size-4" /> {viewing?.title}</DialogTitle>
          </DialogHeader>
          {viewing && (
            <>
              <div className="print-area">
                <div className="print-sheet rounded-xl border bg-card text-card-foreground p-8">
                  <div className="text-center border-b-2 border-foreground/80 pb-4">
                    <div className="font-bold text-lg uppercase tracking-tight">{school?.name ?? "School"}</div>
                    {viewing.examName && <div className="text-sm font-semibold mt-1">{viewing.examName}</div>}
                    <div className="text-xs text-muted-foreground mt-1">{viewing.classroom} · Set by {viewing.authorName}</div>
                    <div className="mt-2 inline-block border-2 border-foreground px-4 py-0.5 font-bold tracking-[0.15em] text-xs uppercase">{viewing.title}</div>
                  </div>
                  <div className="mt-5 text-sm leading-relaxed [&_h3]:font-semibold [&_h3]:mt-4 [&_h3]:mb-1 [&_p]:mb-2 [&_ol]:list-decimal [&_ol]:pl-6 [&_ul]:list-disc [&_ul]:pl-6" dangerouslySetInnerHTML={{ __html: viewing.body || "<p class='text-muted-foreground'>This paper has no content yet.</p>" }} />
                </div>
              </div>
              <div className="flex justify-end gap-2 mt-4 no-print">
                <Button size="sm" variant="outline" className="gap-1.5" onClick={() => setEditing(viewing)}><Pencil className="size-4" /> Edit</Button>
                <Button size="sm" className="gap-1.5" onClick={() => window.print()}><Printer className="size-4" /> Print paper</Button>
              </div>
            </>
          )}
        </DialogContent>
      </Dialog>

      <AlertDialog open={!!deleting} onOpenChange={(o) => !o && setDeleting(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete “{deleting?.title}”?</AlertDialogTitle>
            <AlertDialogDescription>
              The question paper for {deleting?.classroom} will be removed from the bank. This cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction className="bg-destructive text-destructive-foreground hover:bg-destructive/90" onClick={() => deleting && remove(deleting)}>Delete paper</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}

function PaperDialog({ paper, classrooms, onOpenChange, onDone }: { paper: PaperRow | "new" | null; classrooms: { id: string; name: string }[]; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const isNew = paper === "new";
  const [form, setForm] = useState({ classroomId: "", title: "", examName: "", authorName: "", body: "" });
  const [preview, setPreview] = useState(false);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (paper && paper !== "new") {
      setForm({ classroomId: paper.classroomId, title: paper.title, examName: paper.examName ?? "", authorName: paper.authorName, body: paper.body });
    } else if (paper === "new") {
      setForm({ classroomId: classrooms[0]?.id ?? "", title: "", examName: "", authorName: "", body: "<h3>SECTION A — OBJECTIVE (40 marks)</h3>\n<p>Answer ALL questions.</p>\n<ol><li>Question one. <b>(2 marks)</b></li></ol>\n<h3>SECTION B — THEORY (60 marks)</h3>\n<p>Answer ANY FOUR questions.</p>" });
    }
  }, [paper]);

  async function submit() {
    if (!form.title.trim() || !form.classroomId) { toast({ title: "Title and class are required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/academics`, {
        action: isNew ? "paper.create" : "paper.update",
        id: isNew ? undefined : (paper as PaperRow).id,
        classroomId: form.classroomId, title: form.title, body: form.body,
        examName: form.examName || null, authorName: form.authorName || null,
      });
      toast({ title: isNew ? "Question paper created" : "Question paper updated", description: form.title });
      onDone();
    } catch (e: any) {
      toast({ title: "Failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!paper} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[92vh] overflow-y-auto nice-scroll">
        <DialogHeader><DialogTitle>{isNew ? "New question paper" : `Edit — ${paper ? paper.title : ""}`}</DialogTitle></DialogHeader>
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-1.5 sm:col-span-2"><Label>Title *</Label><Input placeholder="e.g. Mathematics — End of Term 1 Paper" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} /></div>
          <div className="space-y-1.5">
            <Label>Class *</Label>
            <Select value={form.classroomId} onValueChange={(v) => setForm({ ...form, classroomId: v })}>
              <SelectTrigger><SelectValue placeholder="Select class" /></SelectTrigger>
              <SelectContent>{classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5"><Label>Exam name</Label><Input placeholder="e.g. End of Term 1 Examination 2026/27" value={form.examName} onChange={(e) => setForm({ ...form, examName: e.target.value })} /></div>
          <div className="space-y-1.5 sm:col-span-2"><Label>Author</Label><Input placeholder="Teacher setting the paper" value={form.authorName} onChange={(e) => setForm({ ...form, authorName: e.target.value })} /></div>
          <div className="space-y-1.5 sm:col-span-2">
            <div className="flex items-center justify-between">
              <Label>Body (HTML)</Label>
              <Button size="sm" variant={preview ? "default" : "outline"} className="h-6 text-[11px] gap-1" onClick={() => setPreview(!preview)}><Eye className="size-3" /> {preview ? "Editing" : "Preview"}</Button>
            </div>
            {preview ? (
              <div className="rounded-md border min-h-48 p-4 text-sm leading-relaxed [&_h3]:font-semibold [&_h3]:mt-3 [&_h3]:mb-1 [&_p]:mb-2 [&_ol]:list-decimal [&_ol]:pl-6 [&_ul]:list-disc [&_ul]:pl-6" dangerouslySetInnerHTML={{ __html: form.body || "<p>No content.</p>" }} />
            ) : (
              <Textarea rows={12} className="font-mono text-xs" value={form.body} onChange={(e) => setForm({ ...form, body: e.target.value })} placeholder="<h3>SECTION A …</h3>" />
            )}
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : isNew ? "Create paper" : "Save paper"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function cn(...args: any[]) { return args.filter(Boolean).join(" "); }

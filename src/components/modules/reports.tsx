"use client";
import { useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { ModuleHeader, LoadingBlock, EmptyState, StatusBadge } from "@/components/ui-helpers";
import { toast } from "@/hooks/use-toast";
import { GraduationCap, Sparkles, Printer, Wand2, Send, RefreshCcw, Layers, FileText, Pencil, Save } from "lucide-react";
import { ClassicSheet, ModernSheet, CompactSheet, type SheetData } from "@/components/report-sheets";
import { Textarea } from "@/components/ui/textarea";

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
  const { currentSchoolId } = useStore();
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
        actions={
          <div className="flex flex-wrap gap-2">
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
        }
      />

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

function cn(...args: any[]) { return args.filter(Boolean).join(" "); }

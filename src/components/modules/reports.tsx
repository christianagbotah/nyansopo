"use client";
import { useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { ModuleHeader, LoadingBlock, EmptyState, StatusBadge } from "@/components/ui-helpers";
import { fmtCedi, fmtDate } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { GraduationCap, Sparkles, Printer, Wand2, Send, FileText, RefreshCcw } from "lucide-react";
import { LogoMark, KenteStrip } from "@/components/brand";

type RepData = {
  terms: { id: string; name: string; isCurrent: boolean }[];
  termId: string;
  classrooms: { id: string; name: string }[];
  reportCards: {
    id: string; student: string; studentId: string; classroom: string; average: number | null; position: number | null;
    classSize: number | null; conduct: string | null; promotion: string | null; published: boolean;
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
  const [busy, setBusy] = useState<string | null>(null);

  const load = (t?: string) => {
    if (!currentSchoolId) return;
    apiGet<RepData>(`/api/school/${currentSchoolId}/reports${t ? `?termId=${t}` : ""}`)
      .then((d) => { setData(d); setTermId(d.termId); })
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
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
    setSelected(card); setDetail(null);
    const full = await apiGet<any>(`/api/student/${card.studentId}`);
    const rc = full.reportCards.find((r: any) => r.id === card.id);
    setDetail({ ...card, results: rc?.results ?? [], student: full.student, attendanceSummary: rc?.attendance });
  }

  if (!data) return <LoadingBlock label="Loading reports…" />;

  const filtered = classId ? data.reportCards.filter((r) => r.classroom === data.classrooms.find((c) => c.id === classId)?.name) : data.reportCards;

  return (
    <div>
      <ModuleHeader
        title="Exams & Terminal Reports"
        description="Generate positioned report cards graded with your curriculum's scale — GES SBC/CCP proficiency bands, Cambridge or IB. Let AI draft the teacher's and head's remarks."
        actions={
          <div className="flex flex-wrap gap-2">
            <Select value={termId ?? undefined} onValueChange={(v) => { setTermId(v); load(v); }}>
              <SelectTrigger className="w-52"><SelectValue /></SelectTrigger>
              <SelectContent>{data.terms.map((t) => <SelectItem key={t.id} value={t.id}>{t.name}</SelectItem>)}</SelectContent>
            </Select>
            <Select value={classId ?? undefined} onValueChange={setClassId}>
              <SelectTrigger className="w-44"><SelectValue placeholder="Class (generate)" /></SelectTrigger>
              <SelectContent>{data.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent>
            </Select>
            <Button size="sm" className="gap-1.5" onClick={generate} disabled={!classId || busy === "generate"}>
              <RefreshCcw className={cn("size-4", busy === "generate" && "animate-spin")} /> Generate for class
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

      {/* report card viewer */}
      <Dialog open={!!selected} onOpenChange={(o) => !o && setSelected(null)}>
        <DialogContent className="max-w-2xl max-h-[92vh] overflow-y-auto nice-scroll">
          <DialogTitle className="sr-only">Report card — {selected?.student}</DialogTitle>
          {!detail ? <LoadingBlock label="Preparing report card…" /> : <ReportCardView detail={detail} termName={data.terms.find((t) => t.id === termId)?.name ?? ""} onAiRemark={() => aiRemark(detail.id)} busy={busy === detail.id} onPublish={() => publish(detail.id)} />}
        </DialogContent>
      </Dialog>
    </div>
  );
}

function cn(...args: any[]) { return args.filter(Boolean).join(" "); }

export function ReportCardView({ detail, termName, onAiRemark, onPublish, busy }: { detail: any; termName: string; onAiRemark: () => void; onPublish: () => void; busy: boolean }) {
  return (
    <div>
      <div className="print-area rounded-xl border overflow-hidden bg-card">
        <KenteStrip />
        <div className="p-5">
          <div className="flex items-center justify-between gap-3">
            <div className="flex items-center gap-2.5">
              <LogoMark size={40} />
              <div>
                <div className="font-bold text-lg leading-tight">Terminal Report Card</div>
                <div className="text-xs text-muted-foreground">{termName}</div>
              </div>
            </div>
            <Badge variant={detail.published ? "default" : "secondary"}>{detail.published ? "PUBLISHED" : "DRAFT"}</Badge>
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-4 text-sm">
            <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Learner</div><div className="font-semibold">{detail.student.firstName} {detail.student.lastName}</div></div>
            <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Index</div><div className="font-mono text-xs font-semibold mt-0.5">{detail.student.nyansapoIndex}</div></div>
            <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Class</div><div className="font-semibold">{detail.classroom}</div></div>
            <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Position</div><div className="font-semibold">{detail.position} of {detail.classSize}</div></div>
          </div>

          <table className="w-full text-sm mt-4 border rounded-lg overflow-hidden">
            <thead>
              <tr className="bg-secondary text-left">
                <th className="px-2.5 py-2 font-medium">Subject</th>
                <th className="px-2.5 py-2 font-medium text-center">Score</th>
                <th className="px-2.5 py-2 font-medium">Grade</th>
                <th className="px-2.5 py-2 font-medium hidden sm:table-cell">Remark</th>
              </tr>
            </thead>
            <tbody>
              {detail.results.map((r: any) => (
                <tr key={r.subject} className="border-t">
                  <td className="px-2.5 py-2">{r.subject}</td>
                  <td className="px-2.5 py-2 text-center font-semibold tabular-nums">{r.total}%</td>
                  <td className="px-2.5 py-2"><Badge variant="outline">{r.grade}</Badge></td>
                  <td className="px-2.5 py-2 text-muted-foreground text-xs hidden sm:table-cell">{r.remark}</td>
                </tr>
              ))}
            </tbody>
          </table>

          <div className="grid sm:grid-cols-3 gap-2 mt-4 text-sm">
            <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Average</div><div className="font-bold text-lg">{detail.average}%</div></div>
            <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Conduct</div><div className="font-semibold">{detail.conduct ?? "—"}</div></div>
            <div className="rounded-lg bg-secondary/60 p-2.5"><div className="text-[10px] uppercase text-muted-foreground">Promotion</div><div className="font-semibold">{detail.promotion ?? "—"}</div></div>
          </div>

          <div className="mt-4 space-y-3">
            <div className="rounded-lg border p-3">
              <div className="text-xs font-semibold uppercase text-muted-foreground">Class teacher&apos;s remarks {detail.aiGenerated && <Sparkles className="inline size-3 text-primary" />}</div>
              <p className="text-sm mt-1 italic">“{detail.teacherRemark ?? "—"}”</p>
            </div>
            <div className="rounded-lg border p-3">
              <div className="text-xs font-semibold uppercase text-muted-foreground">Head of school&apos;s remarks</div>
              <p className="text-sm mt-1 italic">“{detail.headRemark ?? "—"}”</p>
            </div>
            <div className="text-[11px] text-muted-foreground">
              Attendance: {detail.attendanceSummary ?? detail.attendance ?? "—"} · Generated by Nyansapo — The Wisdom Knot Cloud
            </div>
          </div>
        </div>
        <KenteStrip />
      </div>

      <div className="flex flex-wrap gap-2 justify-end mt-4 no-print">
        <Button size="sm" variant="outline" className="gap-1.5" onClick={onAiRemark} disabled={busy}><Wand2 className="size-4" /> Draft AI remarks</Button>
        <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()}><Printer className="size-4" /> Print</Button>
        {!detail.published && <Button size="sm" className="gap-1.5" onClick={onPublish}><Send className="size-4" /> Publish</Button>}
      </div>
    </div>
  );
}

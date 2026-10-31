"use client";
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost, apiPostQueueable } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import { ClipboardList, Plus, Save, Trash2, CloudOff, PencilLine } from "lucide-react";

type GBData = {
  classrooms: { id: string; name: string }[];
  classroom: { id: string; name: string; stage: string | null } | null;
  subjects: { id: string; name: string; code: string }[];
  termId: string; termName: string;
  students: { id: string; name: string; rollNo: number | null }[];
  assessments: { id: string; title: string; type: string; maxScore: number; date: string; subject: string; subjectId: string; marks: Record<string, number | null> }[];
};

const TYPE_LABELS: Record<string, string> = {
  EXERCISE: "Class Exercise", HOMEWORK: "Homework", PROJECT: "Project", QUIZ: "Quiz",
  GROUP_WORK: "Group Work", MID_TERM: "Mid-Term", END_OF_TERM: "End-of-Term Exam",
};

export function Gradebook() {
  const { currentSchoolId, online } = useStore();
  const [data, setData] = useState<GBData | null>(null);
  const [classId, setClassId] = useState<string | null>(null);
  const [addOpen, setAddOpen] = useState(false);
  const [editing, setEditing] = useState<string | null>(null); // assessment id being edited
  const [grid, setGrid] = useState<Record<string, Record<string, string>>>({});
  const [busy, setBusy] = useState(false);

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<GBData>(`/api/school/${currentSchoolId}/gradebook${classId ? `?classId=${classId}` : ""}`)
      .then((d) => {
        setData(d);
        if (classId) {
          setGrid(Object.fromEntries(
            (d.assessments ?? []).map((a) => [a.id, Object.fromEntries((d.students ?? []).map((s) => [s.id, a.marks[s.id] !== undefined && a.marks[s.id] !== null ? String(a.marks[s.id]) : ""]))])
          ));
        }
      })
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(() => { load();}, [currentSchoolId, classId]);

  const subjectAverages = useMemo(() => {
    if (!data || !classId) return [];
    const bySubject: Record<string, { got: number; max: number }> = {};
    for (const a of data.assessments ?? []) {
      bySubject[a.subject] ??= { got: 0, max: 0 };
      for (const s of data.students ?? []) {
        const v = a.marks[s.id];
        if (v !== null && v !== undefined) { bySubject[a.subject].got += v; bySubject[a.subject].max += a.maxScore; }
      }
    }
    return Object.entries(bySubject).map(([subject, v]) => ({ subject, pct: v.max ? Math.round((v.got / v.max) * 100) : 0 }));
  }, [data, classId]);

  async function saveMarks(assessmentId: string) {
    if (!currentSchoolId || !data) return;
    const assessment = (data.assessments ?? []).find((a) => a.id === assessmentId)!;
    if (!assessment) return;
    setBusy(true);
    try {
      const marks = (data.students ?? []).map((s) => ({ studentId: s.id, score: grid[assessmentId]?.[s.id] ?? null }));
      const payload = { action: "save-marks", assessmentId, marks };
      const res = await apiPostQueueable<any>(
        `/api/school/${currentSchoolId}/gradebook`, "marks", payload,
        `Marks — ${assessment.title}`,
        (p, clientId) => ({ schoolId: currentSchoolId, assessmentId, marks, clientId })
      );
      if (res.queued) toast({ title: "Saved offline", description: "Marks stored locally and will sync when online." });
      else toast({ title: "Marks saved", description: `${assessment.title} (${assessment.subject}) updated.` });
      setEditing(null);
      load();
    } catch (e: any) {
      toast({ title: "Save failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  async function deleteAssessment(id: string) {
    if (!currentSchoolId) return;
    await apiPost(`/api/school/${currentSchoolId}/gradebook`, { action: "delete-assessment", assessmentId: id });
    toast({ title: "Assessment deleted" });
    load();
  }

  if (!data) return <LoadingBlock label="Loading gradebook…" />;
  const students = data.students ?? [];
  const assessments = data.assessments ?? [];
  const gbReady = !!classId && Array.isArray(data.students);

  return (
    <div>
      <ModuleHeader
        title="Gradebook"
        description={`Continuous assessment for ${data.termName ?? "the current term"} — exercises, quizzes, projects and exams feeding the terminal report.`}
        actions={
          <div className="flex gap-2">
            <Select value={classId ?? ""} onValueChange={setClassId}>
              <SelectTrigger className="w-56"><SelectValue placeholder="Select class" /></SelectTrigger>
              <SelectContent>{data.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent>
            </Select>
            {classId && <Button size="sm" className="gap-1.5" onClick={() => setAddOpen(true)}><Plus className="size-4" /> New assessment</Button>}
          </div>
        }
      />

      {!gbReady ? (
        <Card><CardContent><EmptyState icon={<ClipboardList className="size-5" />} title={classId ? "Loading assessments…" : "Pick a class to view assessments"} hint={classId ? undefined : "Marks entry works offline too."} /></CardContent></Card>
      ) : (
        <>
          {subjectAverages.length > 0 && (
            <div className="flex flex-wrap gap-2 mb-4">
              {subjectAverages.map((s) => (
                <Badge key={s.subject} variant="secondary" className="gap-1.5 py-1.5">
                  {s.subject}
                  <span className={cn("font-bold", s.pct >= 65 ? "text-emerald-600" : s.pct >= 50 ? "text-amber-600" : "text-destructive")}>{s.pct}%</span>
                </Badge>
              ))}
            </div>
          )}

          {assessments.length === 0 && <Card><CardContent><EmptyState icon={<ClipboardList className="size-5" />} title="No assessments yet for this class" hint="Create the first class exercise to start capturing marks." /></CardContent></Card>}

          <div className="space-y-4">
            {assessments.map((a) => (
              <Card key={a.id}>
                <CardHeader className="pb-3 flex-row flex-wrap items-center justify-between gap-2 space-y-0">
                  <div>
                    <CardTitle className="text-sm flex items-center gap-2">
                      {a.title}
                      <Badge variant="outline">{TYPE_LABELS[a.type] ?? a.type}</Badge>
                      <Badge variant="secondary">{a.subject}</Badge>
                      <span className="text-xs font-normal text-muted-foreground">max {a.maxScore}</span>
                    </CardTitle>
                    <div className="text-xs text-muted-foreground mt-1">{new Date(a.date).toLocaleDateString("en-GB")} · {Object.keys(a.marks).length} of {data.students.length} marked</div>
                  </div>
                  <div className="flex gap-1.5">
                    {editing === a.id ? (
                      <>
                        {!online && <Badge className="bg-amber-500 gap-1"><CloudOff className="size-3" /> offline</Badge>}
                        <Button size="sm" className="gap-1 h-8" onClick={() => saveMarks(a.id)} disabled={busy}><Save className="size-3.5" /> Save</Button>
                      </>
                    ) : (
                      <Button size="sm" variant="outline" className="gap-1 h-8" onClick={() => setEditing(a.id)}><PencilLine className="size-3.5" /> Enter marks</Button>
                    )}
                    <Button size="sm" variant="ghost" className="size-8 p-0 text-destructive" onClick={() => deleteAssessment(a.id)}><Trash2 className="size-4" /></Button>
                  </div>
                </CardHeader>
                {editing === a.id && (
                  <CardContent className="pt-0">
                    <div className="overflow-x-auto nice-scroll max-h-72">
                      <table className="w-full text-sm">
                        <thead><tr className="text-left border-b"><th className="py-2 pr-3 font-medium w-12">#</th><th className="py-2 pr-3 font-medium">Learner</th><th className="py-2 font-medium w-28">Score (/{a.maxScore})</th></tr></thead>
                        <tbody>
                          {students.map((s, i) => (
                            <tr key={s.id} className="border-b last:border-0">
                              <td className="py-1.5 pr-3 text-muted-foreground">{s.rollNo ?? i + 1}</td>
                              <td className="py-1.5 pr-3">{s.name}</td>
                              <td className="py-1.5">
                                <Input
                                  type="number" min={0} max={a.maxScore} step="0.5"
                                  className="h-8 w-24"
                                  value={grid[a.id]?.[s.id] ?? ""}
                                  onChange={(e) => setGrid((g) => ({ ...g, [a.id]: { ...(g[a.id] ?? {}), [s.id]: e.target.value } }))}
                                  placeholder="—"
                                />
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </CardContent>
                )}
              </Card>
            ))}
          </div>
        </>
      )}

      <AddAssessmentDialog open={addOpen} onOpenChange={setAddOpen} classId={classId} subjects={data.subjects} onDone={load} />
    </div>
  );
}

function AddAssessmentDialog({ open, onOpenChange, classId, subjects, onDone }: { open: boolean; onOpenChange: (o: boolean) => void; classId: string | null; subjects: { id: string; name: string }[]; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [form, setForm] = useState({ subjectId: "", title: "", type: "EXERCISE", maxScore: "10", date: new Date().toISOString().slice(0, 10) });
  const [busy, setBusy] = useState(false);

  async function submit() {
    if (!form.subjectId || !form.title) { toast({ title: "Subject and title are required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/gradebook`, { action: "create-assessment", classroomId: classId, ...form, maxScore: Number(form.maxScore) });
      toast({ title: "Assessment created", description: "Start entering marks right away." });
      onOpenChange(false); onDone();
    } catch (e: any) {
      toast({ title: "Failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader><DialogTitle>New assessment</DialogTitle><DialogDescription>Class exercise, homework, project, quiz or exam.</DialogDescription></DialogHeader>
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1.5">
            <Label>Subject</Label>
            <Select value={form.subjectId} onValueChange={(v) => setForm({ ...form, subjectId: v })}>
              <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
              <SelectContent>{subjects.map((s) => <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>)}</SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Type</Label>
            <Select value={form.type} onValueChange={(v) => setForm({ ...form, type: v })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>{Object.entries(TYPE_LABELS).map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}</SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5 col-span-2"><Label>Title</Label><Input placeholder="e.g. Class Exercise 3 — Fractions" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} /></div>
          <div className="space-y-1.5"><Label>Max score</Label><Input type="number" value={form.maxScore} onChange={(e) => setForm({ ...form, maxScore: e.target.value })} /></div>
          <div className="space-y-1.5"><Label>Date</Label><Input type="date" value={form.date} onChange={(e) => setForm({ ...form, date: e.target.value })} /></div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Creating…" : "Create"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

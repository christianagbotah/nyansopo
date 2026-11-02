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
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import { ClipboardList, Plus, Save, Trash2, CloudOff, PencilLine, Sigma, CalendarRange, ShieldCheck } from "lucide-react";

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

/* most recent COMPLETED ISO week "YYYY-Www" — matches the seeded demo data */
function defaultPortfolioWeek(): string {
  const now = new Date();
  const monday = new Date(now);
  monday.setUTCDate(now.getUTCDate() - ((now.getUTCDay() + 6) % 7) - 7);
  const wk = Math.ceil((((monday.getTime() - Date.UTC(monday.getUTCFullYear(), 0, 1)) / 864e5) + 1) / 7);
  return `${monday.getUTCFullYear()}-W${String(wk).padStart(2, "0")}`;
}

export function Gradebook() {
  const { currentSchoolId, online, navTarget } = useStore();
  const [data, setData] = useState<GBData | null>(null);
  const [classId, setClassId] = useState<string | null>(null);
  const [tab, setTab] = useState("assessments");
  const [addOpen, setAddOpen] = useState(false);
  const [editing, setEditing] = useState<string | null>(null); // assessment id being edited
  const [grid, setGrid] = useState<Record<string, Record<string, string>>>({});
  const [busy, setBusy] = useState(false);

  /* iEduPro nested nav: preselect the Portfolio tab on deep link */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab === "portfolio") setTab("portfolio");
    if (navTarget?.classroomId && !classId) setClassId(navTarget.classroomId);
  }

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

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex-wrap h-auto">
          <TabsTrigger value="assessments">Assessments</TabsTrigger>
          <TabsTrigger value="portfolio" className="gap-1.5"><CalendarRange className="size-4" /> Portfolio (weekly SBA)</TabsTrigger>
        </TabsList>

        <TabsContent value="assessments" className="mt-4">
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
        </TabsContent>

        {/* ── PORTFOLIO ASSESSMENT (GES weekly SBA — iEduPro parity) ── */}
        <TabsContent value="portfolio" className="mt-4">
          <PortfolioTab classId={classId} />
        </TabsContent>
      </Tabs>

      <AddAssessmentDialog open={addOpen} onOpenChange={setAddOpen} classId={classId} subjects={data.subjects} onDone={load} />
    </div>
  );
}

/* ═══════════════════════════════════════════════════════════════
   PORTFOLIO (weekly SBA) — one grid per class + subject + ISO week:
   five weekday columns with a per-day strand code, a 0–10 daily
   score per learner, and a one-click aggregation into the class
   score (PROJECT assessment "Portfolio SBA {week}" out of 20).  */
type PortfolioDay = { date: string; label: string; strandCode: string; past: boolean };
type PortfolioGrid = {
  week: string;
  classroom: { id: string; name: string };
  subject: { id: string; name: string };
  portfolioEnabled: boolean;
  days: PortfolioDay[];
  rows: { studentId: string; name: string; rollNo: number | null; scores: Record<string, number | null> }[];
};
type AggRow = { studentId: string; name: string; days: number; average: number | null; classScore: number | null };

function PortfolioTab({ classId }: { classId: string | null }) {
  const { currentSchoolId } = useStore();
  const [boot, setBoot] = useState<{ subjects: { id: string; name: string }[]; currentTerm: { name: string; yearName: string } | null; portfolioEnabled: boolean } | null>(null);
  const [subjectId, setSubjectId] = useState<string | null>(null);
  const [week, setWeek] = useState(defaultPortfolioWeek);
  const [grid, setGrid] = useState<PortfolioGrid | null>(null);
  const [cells, setCells] = useState<Record<string, Record<string, string>>>({});
  const [strands, setStrands] = useState<Record<string, string>>({});
  const [busy, setBusy] = useState(false);
  const [agg, setAgg] = useState<{ assessment: { title: string; termName: string }; rows: AggRow[]; marked: number } | null>(null);

  useEffect(() => {
    if (!currentSchoolId) return;
    apiGet<any>(`/api/school/${currentSchoolId}/ops/academics`)
      .then((d) => setBoot({ subjects: d.subjects ?? [], currentTerm: d.currentTerm, portfolioEnabled: !!d.portfolioEnabled }))
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  }, [currentSchoolId]);

  useEffect(() => {
    if (!currentSchoolId || !classId || !subjectId || !week) { setGrid(null); return; }
    apiGet<PortfolioGrid>(`/api/school/${currentSchoolId}/ops/academics?tab=portfolio&classroomId=${classId}&subjectId=${subjectId}&week=${encodeURIComponent(week)}`)
      .then((d) => {
        setGrid(d);
        setAgg(null);
        setCells(Object.fromEntries(d.rows.map((r) => [r.studentId, Object.fromEntries(d.days.map((day) => [day.date, r.scores[day.date] !== null && r.scores[day.date] !== undefined ? String(r.scores[day.date]) : ""]))])));
        setStrands(Object.fromEntries(d.days.map((d2) => [d2.date, d2.strandCode ?? ""])));
      })
      .catch((e) => toast({ title: "Could not load week", description: e.message, variant: "destructive" }));
  }, [currentSchoolId, classId, subjectId, week]);

  const avgFor = (studentId: string) => {
    const vals = (grid?.days ?? []).map((d) => Number(cells[studentId]?.[d.date])).filter((v) => Number.isFinite(v) && v > 0);
    if (!vals.length) return null;
    return (vals.reduce((a, b) => a + b, 0) / vals.length).toFixed(1);
  };

  async function save() {
    if (!currentSchoolId || !grid || !classId || !subjectId) return;
    setBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/ops/academics`, {
        action: "portfolio.save",
        classroomId: classId, subjectId, week: grid.week,
        days: grid.days.map((d) => ({ date: d.date, strandCode: strands[d.date] ?? "" })),
        rows: grid.rows.map((r) => ({ studentId: r.studentId, scores: Object.fromEntries(grid.days.map((d) => [d.date, cells[r.studentId]?.[d.date] ?? null])) })),
      });
      toast({ title: "Portfolio week saved", description: `${res.saved} daily scores recorded for ${grid.week}.` });
    } catch (e: any) {
      toast({ title: "Save failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  async function aggregate() {
    if (!currentSchoolId || !grid || !classId || !subjectId) return;
    setBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/ops/academics`, {
        action: "portfolio.aggregate", classroomId: classId, subjectId, week: grid.week,
      });
      setAgg(res);
      toast({ title: "Aggregated to class score", description: `"${res.assessment.title}" (/20) updated with ${res.marked} learners' marks — visible under Assessments.` });
    } catch (e: any) {
      toast({ title: "Aggregation failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  const days = grid?.days ?? [];

  return (
    <div className="space-y-4">
      <Card>
        <CardContent className="pt-4 space-y-3">
          <div className="flex flex-wrap items-end gap-3">
            <div className="space-y-1.5 min-w-56">
              <Label className="text-xs">Subject</Label>
              <Select value={subjectId ?? ""} onValueChange={setSubjectId}>
                <SelectTrigger><SelectValue placeholder="Select subject" /></SelectTrigger>
                <SelectContent>{(boot?.subjects ?? []).map((s) => <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label className="text-xs">Week (Mon–Fri)</Label>
              <Input type="week" className="w-44" value={week} onChange={(e) => setWeek(e.target.value)} aria-label="ISO week" />
            </div>
            {boot?.portfolioEnabled && (
              <Badge className="gap-1 bg-emerald-600 hover:bg-emerald-600"><ShieldCheck className="size-3.5" /> Portfolio SBA enabled</Badge>
            )}
            <div className="flex gap-2 ml-auto">
              <Button size="sm" className="gap-1.5" onClick={save} disabled={!grid || busy || !classId || !subjectId}><Save className="size-4" /> {busy ? "Saving…" : "Save week"}</Button>
              <Button size="sm" variant="outline" className="gap-1.5" onClick={aggregate} disabled={!grid || busy || !classId || !subjectId}><Sigma className="size-4" /> Aggregate → class score</Button>
            </div>
          </div>
          <p className="text-xs text-muted-foreground">
            Daily strand scores are 0–10 (half marks allowed). Aggregating takes the week&apos;s average ×10 as 20% of the class score — a PROJECT assessment
            {" "}<span className="font-medium">Portfolio SBA {week}</span> out of 20 for {boot?.currentTerm ? `${boot.currentTerm.name}, ${boot.currentTerm.yearName}` : "the current term"}.
          </p>
        </CardContent>
      </Card>

      {!classId ? (
        <Card><CardContent><EmptyState icon={<CalendarRange className="size-5" />} title="Pick a class above to load its portfolio" hint="The GES weekly SBA grid — one column per school day, one strand code per day." /></CardContent></Card>
      ) : !subjectId || !grid ? (
        <Card><CardContent><EmptyState icon={<CalendarRange className="size-5" />} title={subjectId ? `Loading ${week}…` : "Pick a subject to load the week"} hint="Strand codes and scores already recorded for this week appear automatically." /></CardContent></Card>
      ) : grid.rows.length === 0 ? (
        <Card><CardContent><EmptyState icon={<ClipboardList className="size-5" />} title="No learners enrolled in this class" /></CardContent></Card>
      ) : (
        <Card>
          <CardHeader className="pb-3 flex-row flex-wrap items-center justify-between gap-2 space-y-0">
            <CardTitle className="text-base">{grid.classroom.name} · {grid.subject.name} — week {grid.week}</CardTitle>
            <span className="text-xs text-muted-foreground">{grid.rows.length} learners · {days.filter((d) => d.past).length} of {days.length} days locked (past)</span>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto nice-scroll max-h-[560px]">
              <table className="w-full text-sm">
                <thead>
                  <tr className="text-left border-b bg-secondary/70">
                    <th className="px-3 py-2 font-medium min-w-44">Learner</th>
                    {days.map((d) => (
                      <th key={d.date} className="px-3 py-2 font-medium text-center min-w-28">
                        <div>{d.label}</div>
                        <div className="text-[10px] font-normal text-muted-foreground">{d.date}</div>
                      </th>
                    ))}
                    <th className="px-3 py-2 font-medium text-right">Avg /10</th>
                  </tr>
                  <tr className="border-b bg-secondary/40">
                    <td className="px-3 py-1.5 text-xs text-muted-foreground">Strand code</td>
                    {days.map((d) => (
                      <td key={d.date} className="px-2 py-1.5">
                        <Input
                          value={strands[d.date] ?? ""}
                          onChange={(e) => setStrands((s) => ({ ...s, [d.date]: e.target.value }))}
                          readOnly={d.past}
                          placeholder="e.g. B4.1.1.1"
                          aria-label={`Strand code for ${d.label}`}
                          className={cn("h-8 text-center font-mono text-xs", d.past && "opacity-60")}
                        />
                      </td>
                    ))}
                    <td />
                  </tr>
                </thead>
                <tbody>
                  {grid.rows.map((r) => (
                    <tr key={r.studentId} className="border-b last:border-0 hover:bg-secondary/30">
                      <td className="px-3 py-1.5 whitespace-nowrap">
                        {r.rollNo != null && <span className="text-muted-foreground mr-1.5 tabular-nums">{r.rollNo}</span>}
                        <span className="font-medium">{r.name}</span>
                      </td>
                      {days.map((d) => (
                        <td key={d.date} className="px-2 py-1.5">
                          <Input
                            type="number" min={0} max={10} step={0.5}
                            className="h-8 w-20 text-center tabular-nums"
                            value={cells[r.studentId]?.[d.date] ?? ""}
                            onChange={(e) => setCells((c) => ({ ...c, [r.studentId]: { ...(c[r.studentId] ?? {}), [d.date]: e.target.value } }))}
                            placeholder="—"
                            aria-label={`${r.name} — ${d.label} score`}
                          />
                        </td>
                      ))}
                      <td className="px-3 py-1.5 text-right font-semibold tabular-nums">{avgFor(r.studentId) ?? <span className="text-muted-foreground font-normal">—</span>}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      )}

      {agg && (
        <Card>
          <CardHeader className="pb-3 flex-row flex-wrap items-center justify-between gap-2 space-y-0">
            <CardTitle className="text-base flex items-center gap-2"><Sigma className="size-4 text-primary" /> {agg.assessment.title} — /20 class score</CardTitle>
            <Badge variant="secondary">{agg.marked} marked · {agg.assessment.termName}</Badge>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto nice-scroll max-h-72">
              <table className="w-full text-sm">
                <thead><tr className="text-left border-b bg-secondary/70"><th className="px-3 py-2 font-medium">Learner</th><th className="px-3 py-2 font-medium text-right">Days scored</th><th className="px-3 py-2 font-medium text-right">Week avg /10</th><th className="px-3 py-2 font-medium text-right">Class score /20</th></tr></thead>
                <tbody>
                  {agg.rows.map((r) => (
                    <tr key={r.studentId} className="border-b last:border-0">
                      <td className="px-3 py-2 font-medium">{r.name}</td>
                      <td className="px-3 py-2 text-right tabular-nums">{r.days || <span className="text-muted-foreground">—</span>}</td>
                      <td className="px-3 py-2 text-right tabular-nums">{r.average ?? <span className="text-muted-foreground">—</span>}</td>
                      <td className="px-3 py-2 text-right font-semibold tabular-nums">
                        {r.classScore ?? <span className="text-muted-foreground font-normal">not marked</span>}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      )}
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
        <div className="grid gap-3 sm:grid-cols-2">
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
          <div className="space-y-1.5 sm:col-span-2"><Label>Title</Label><Input placeholder="e.g. Class Exercise 3 — Fractions" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} /></div>
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

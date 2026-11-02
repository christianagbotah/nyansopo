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
import { BarChart, Bar, LineChart, Line, XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid, Cell } from "recharts";
import { ClipboardList, Plus, Save, Trash2, CloudOff, PencilLine, Sigma, CalendarRange, ShieldCheck, BarChart3, TrendingUp, Baby, ClipboardCheck, Wand2 } from "lucide-react";

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
  const [analyticsStudent, setAnalyticsStudent] = useState<string>("");

  /* iEduPro nested nav: preselect the Portfolio tab on deep link */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab === "portfolio") setTab("portfolio");
    if (navTarget?.tab === "analytics") setTab("analytics");
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

  /* ── assessment-graph analytics (iEduPro assessment_graph parity) ── */
  const learnerAverages = useMemo(() => {
    if (!data || !classId) return [];
    return (data.students ?? []).map((s) => {
      let got = 0, max = 0;
      for (const a of data.assessments ?? []) {
        const v = a.marks[s.id];
        if (v !== null && v !== undefined) { got += v; max += a.maxScore; }
      }
      return { id: s.id, name: s.name, pct: max ? Math.round((got / max) * 100) : null, marked: max > 0 };
    }).filter((s) => s.marked).sort((a, b) => (b.pct ?? 0) - (a.pct ?? 0));
  }, [data, classId]);

  const distribution = useMemo(() => {
    const bands = ["0–20", "21–40", "41–50", "51–60", "61–70", "71–80", "81–90", "91–100"];
    const counts = new Array(bands.length).fill(0);
    for (const l of learnerAverages) {
      const p = l.pct ?? 0;
      const idx = p <= 20 ? 0 : p <= 40 ? 1 : p <= 50 ? 2 : p <= 60 ? 3 : p <= 70 ? 4 : p <= 80 ? 5 : p <= 90 ? 6 : 7;
      counts[idx]++;
    }
    return bands.map((band, i) => ({ band, learners: counts[i] }));
  }, [learnerAverages]);

  const trend = useMemo(() => {
    if (!data || !analyticsStudent) return [];
    const sorted = [...(data.assessments ?? [])].sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
    return sorted
      .map((a) => {
        const v = a.marks[analyticsStudent];
        if (v === null || v === undefined) return null;
        return { date: new Date(a.date).toLocaleDateString("en-GB", { day: "2-digit", month: "short" }), label: a.title, subject: a.subject, pct: Math.round((v / a.maxScore) * 100) };
      })
      .filter(Boolean) as { date: string; label: string; subject: string; pct: number }[];
  }, [data, analyticsStudent]);

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
          <TabsTrigger value="sba" className="gap-1.5"><ClipboardCheck className="size-4" /> SBA sheet</TabsTrigger>
          <TabsTrigger value="creche" className="gap-1.5"><Baby className="size-4" /> Crèche ratings</TabsTrigger>
          <TabsTrigger value="analytics" className="gap-1.5"><BarChart3 className="size-4" /> Analytics</TabsTrigger>
        </TabsList>

        <TabsContent value="analytics" className="mt-4">
          {!gbReady || learnerAverages.length === 0 ? (
            <Card><CardContent><EmptyState icon={<BarChart3 className="size-5" />} title={classId ? "No marks to analyse yet" : "Pick a class to see assessment graphs"} hint="Charts appear once assessments carry marks." /></CardContent></Card>
          ) : (
            <div className="space-y-4">
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><BarChart3 className="size-4" /> Subject averages — {data.classroom?.name}</CardTitle></CardHeader>
                  <CardContent className="h-64">
                    <ResponsiveContainer width="100%" height="100%">
                      <BarChart data={subjectAverages} margin={{ top: 4, right: 8, left: -20, bottom: 0 }}>
                        <CartesianGrid strokeDasharray="3 3" vertical={false} />
                        <XAxis dataKey="subject" tick={{ fontSize: 10 }} stroke="#888" interval={0} angle={-18} textAnchor="end" height={46} />
                        <YAxis tick={{ fontSize: 11 }} stroke="#888" domain={[0, 100]} unit="%" />
                        <Tooltip formatter={(v: any) => [`${v}%`, "Average"]} contentStyle={{ borderRadius: 10 }} />
                        <Bar dataKey="pct" radius={[4, 4, 0, 0]}>
                          {subjectAverages.map((s) => (
                            <Cell key={s.subject} fill={s.pct >= 65 ? "#059669" : s.pct >= 50 ? "#d97706" : "#dc2626"} />
                          ))}
                        </Bar>
                      </BarChart>
                    </ResponsiveContainer>
                  </CardContent>
                </Card>
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-base">Class distribution</CardTitle></CardHeader>
                  <CardContent className="h-64">
                    <ResponsiveContainer width="100%" height="100%">
                      <BarChart data={distribution} margin={{ top: 4, right: 8, left: -20, bottom: 0 }}>
                        <CartesianGrid strokeDasharray="3 3" vertical={false} />
                        <XAxis dataKey="band" tick={{ fontSize: 10 }} stroke="#888" interval={0} />
                        <YAxis tick={{ fontSize: 11 }} stroke="#888" allowDecimals={false} />
                        <Tooltip formatter={(v: any) => [v, "Learners"]} contentStyle={{ borderRadius: 10 }} />
                        <Bar dataKey="learners" fill="#7c3aed" radius={[4, 4, 0, 0]} />
                      </BarChart>
                    </ResponsiveContainer>
                  </CardContent>
                </Card>
              </div>

              <Card>
                <CardHeader className="pb-2 flex-row flex-wrap items-center justify-between gap-2 space-y-0">
                  <CardTitle className="text-base flex items-center gap-2"><TrendingUp className="size-4" /> Learner trend</CardTitle>
                  <Select value={analyticsStudent || learnerAverages[0]?.id || ""} onValueChange={setAnalyticsStudent}>
                    <SelectTrigger className="w-64"><SelectValue placeholder="Choose learner" /></SelectTrigger>
                    <SelectContent>{(data.students ?? []).map((s) => <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>)}</SelectContent>
                  </Select>
                </CardHeader>
                <CardContent className="h-64">
                  {trend.length === 0 ? (
                    <div className="h-full grid place-items-center text-sm text-muted-foreground">No marked assessments for this learner yet.</div>
                  ) : (
                    <ResponsiveContainer width="100%" height="100%">
                      <LineChart data={trend} margin={{ top: 4, right: 8, left: -20, bottom: 0 }}>
                        <CartesianGrid strokeDasharray="3 3" vertical={false} />
                        <XAxis dataKey="date" tick={{ fontSize: 10 }} stroke="#888" />
                        <YAxis tick={{ fontSize: 11 }} stroke="#888" domain={[0, 100]} unit="%" />
                        <Tooltip formatter={(v: any) => [`${v}%`, "Score"]} labelFormatter={(_, p: any) => `${p?.[0]?.payload?.label ?? ""} · ${p?.[0]?.payload?.subject ?? ""}`} contentStyle={{ borderRadius: 10 }} />
                        <Line type="monotone" dataKey="pct" stroke="#0f6b47" strokeWidth={2} dot={{ r: 3 }} activeDot={{ r: 5 }} />
                      </LineChart>
                    </ResponsiveContainer>
                  )}
                </CardContent>
              </Card>

              <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-base">Top performers</CardTitle></CardHeader>
                  <CardContent className="space-y-1.5">
                    {learnerAverages.slice(0, 5).map((l, i) => (
                      <div key={l.id} className="flex items-center justify-between text-sm">
                        <span className="flex items-center gap-2"><span className="text-muted-foreground tabular-nums w-4">{i + 1}</span>{l.name}</span>
                        <Badge className={cn("tabular-nums", (l.pct ?? 0) >= 65 ? "bg-emerald-600" : (l.pct ?? 0) >= 50 ? "bg-amber-600" : "bg-destructive")}>{l.pct}%</Badge>
                      </div>
                    ))}
                  </CardContent>
                </Card>
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-base">Needs support</CardTitle></CardHeader>
                  <CardContent className="space-y-1.5">
                    {[...learnerAverages].reverse().slice(0, 5).map((l, i) => (
                      <div key={l.id} className="flex items-center justify-between text-sm">
                        <span className="flex items-center gap-2"><span className="text-muted-foreground tabular-nums w-4">{i + 1}</span>{l.name}</span>
                        <Badge className={cn("tabular-nums", (l.pct ?? 0) >= 65 ? "bg-emerald-600" : (l.pct ?? 0) >= 50 ? "bg-amber-600" : "bg-destructive")}>{l.pct}%</Badge>
                      </div>
                    ))}
                  </CardContent>
                </Card>
              </div>
            </div>
          )}
        </TabsContent>

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

        {/* ── SBA SHEET (four fixed columns + exam → Assessment/Mark rows) ── */}
        <TabsContent value="sba" className="mt-4">
          <SbaSheetTab classId={classId} subjects={data.subjects} termId={data.termId} termName={data.termName} />
        </TabsContent>

        {/* ── CRÈCHE / EARLY-YEARS RATINGS (NaCCA developmental domains) ── */}
        <TabsContent value="creche" className="mt-4">
          <CrecheRatingsTab classId={classId} classroomName={data.classroom?.name ?? null} termId={data.termId} termName={data.termName} />
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

const round1 = (x: number) => Math.round(x * 10) / 10;

/* ═══════════════════════════════════════════════════════════════
   CRÈCHE / EARLY-YEARS RATINGS — GES NaCCA developmental domains.
   One domain at a time (keeps the table narrow), one row per learner,
   one rating select per observable skill. Upserts by learner+term+skill. */
type CrecheGrid = {
  scale: { id: string; code: string; label: string; sortOrder: number }[];
  domains: { id: string; name: string; sortOrder: number; active: boolean; skills: { id: string; name: string; sortOrder: number }[] }[];
  students: { id: string; name: string; rollNo: number | null }[];
  ratings: Record<string, string>; // "studentId_skillId" → code
};

function CrecheRatingsTab({ classId, classroomName, termId, termName }: { classId: string | null; classroomName: string | null; termId: string | null; termName: string }) {
  const { currentSchoolId } = useStore();
  const [grid, setGrid] = useState<CrecheGrid | null>(null);
  const [domainId, setDomainId] = useState<string | null>(null);
  const [cells, setCells] = useState<Record<string, Record<string, string>>>({}); // studentId → skillId → code ("" = none)
  const [savedSnapshot, setSavedSnapshot] = useState<Record<string, Record<string, string>>>({});
  const [busy, setBusy] = useState<string | null>(null);

  const earlyYearsHint = !!(classroomName && /kg|k\.?g|crech|crèch|nurs|day ?care|play ?group/i.test(classroomName));

  useEffect(() => {
    if (!currentSchoolId || !classId || !termId) { setGrid(null); return; }
    apiGet<CrecheGrid>(`/api/school/${currentSchoolId}/ops/academics?action=creche-ratings&classroomId=${classId}&termId=${termId}`)
      .then((d) => {
        setGrid(d);
        const init: Record<string, Record<string, string>> = {};
        for (const s of d.students) {
          init[s.id] = {};
          for (const dom of d.domains) for (const sk of dom.skills) init[s.id][sk.id] = d.ratings[`${s.id}_${sk.id}`] ?? "";
        }
        setCells(init);
        setSavedSnapshot(JSON.parse(JSON.stringify(init)));
        setDomainId((prev) => (prev && d.domains.some((x) => x.id === prev) ? prev : (d.domains.find((d2) => d2.active) ?? d.domains[0])?.id ?? null));
      })
      .catch((e) => toast({ title: "Could not load ratings", description: e.message, variant: "destructive" }));
  }, [currentSchoolId, classId, termId]);

  const activeDomains = (grid?.domains ?? []).filter((d) => d.active);
  const domain = activeDomains.find((d) => d.id === domainId) ?? null;

  const dirtyFor = (studentId: string) => {
    const cur = cells[studentId] ?? {}, saved = savedSnapshot[studentId] ?? {};
    const keys = new Set([...Object.keys(cur), ...Object.keys(saved)]);
    for (const k of keys) if ((cur[k] ?? "") !== (saved[k] ?? "")) return true;
    return false;
  };
  const dirtyCount = (grid?.students ?? []).filter((s) => dirtyFor(s.id)).length;

  function setCell(studentId: string, skillId: string, code: string) {
    setCells((c) => ({ ...c, [studentId]: { ...(c[studentId] ?? {}), [skillId]: code } }));
  }

  async function save(studentIds?: string[]) {
    if (!currentSchoolId || !classId || !termId || !grid) return;
    const ids = studentIds ?? grid.students.map((s) => s.id);
    const ratings = ids.flatMap((sid) =>
      grid.domains.flatMap((d) => d.skills.map((sk) => ({ studentId: sid, skillId: sk.id, code: cells[sid]?.[sk.id] || null }))),
    );
    setBusy(studentIds ? ids.join(",") : "all");
    try {
      const res = await apiPost<{ saved: number; cleared: number }>(`/api/school/${currentSchoolId}/ops/academics`, {
        action: "creche-ratings-save", classroomId: classId, termId, ratings,
      });
      setSavedSnapshot(JSON.parse(JSON.stringify(cells)));
      toast({ title: "Ratings saved", description: `${res.saved} rating${res.saved === 1 ? "" : "s"} recorded${res.cleared ? `, ${res.cleared} cleared` : ""} for ${termName}.` });
    } catch (e: any) {
      toast({ title: "Save failed", description: e.message, variant: "destructive" });
    } finally { setBusy(null); }
  }

  const codeBadge = (code: string) => {
    const s = grid?.scale.find((x) => x.code === code);
    return s ? `${s.code} — ${s.label}` : code;
  };

  return (
    <div className="space-y-4">
      <Card>
        <CardContent className="pt-4 space-y-3">
          <div className="flex flex-wrap items-end gap-3">
            <div className="space-y-1.5 min-w-64">
              <Label className="text-xs">Developmental domain</Label>
              <Select value={domainId ?? ""} onValueChange={setDomainId} disabled={!activeDomains.length}>
                <SelectTrigger><SelectValue placeholder={activeDomains.length ? "Select domain" : "No domains configured"} /></SelectTrigger>
                <SelectContent>
                  {activeDomains.map((d) => <SelectItem key={d.id} value={d.id}>{d.name} ({d.skills.length})</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            {grid && <Badge variant="secondary">{grid.scale.map((s) => s.code).join(" · ")}</Badge>}
            {earlyYearsHint ? (
              <Badge className="gap-1 bg-emerald-600 hover:bg-emerald-600"><Baby className="size-3.5" /> early-years class detected</Badge>
            ) : (
              <span className="text-xs text-muted-foreground">Any class can be rated — typically used for crèche / KG.</span>
            )}
            <div className="flex gap-2 ml-auto">
              <Button size="sm" variant="outline" className="gap-1.5" onClick={() => save()} disabled={!grid || busy !== null || dirtyCount === 0}>
                <Save className="size-4" /> Save all{dirtyCount > 0 ? ` (${dirtyCount})` : ""}
              </Button>
            </div>
          </div>
          <p className="text-xs text-muted-foreground">
            Rate every observable skill for <b>{termName}</b> — cells upsert by learner + term + skill, so re-saving never duplicates. “—” clears a rating.
            {" "}Configure the scale and domains in <b>Settings → Early years</b>.
          </p>
        </CardContent>
      </Card>

      {!classId ? (
        <Card><CardContent><EmptyState icon={<Baby className="size-5" />} title="Pick a class above to load its ratings" hint="Usually the crèche / KG classes — but any class can be rated on the developmental domains." /></CardContent></Card>
      ) : !grid ? (
        <LoadingBlock label="Loading developmental domains…" />
      ) : !activeDomains.length ? (
        <Card><CardContent><EmptyState icon={<Baby className="size-5" />} title="No developmental domains configured" hint="Ask the administrator to set them up under Settings → Early years (defaults seed automatically)." /></CardContent></Card>
      ) : !domain ? (
        <Card><CardContent><EmptyState icon={<Baby className="size-5" />} title="Select a domain to start rating" /></CardContent></Card>
      ) : grid.students.length === 0 ? (
        <Card><CardContent><EmptyState icon={<ClipboardList className="size-5" />} title="No learners enrolled in this class" /></CardContent></Card>
      ) : (
        <Card>
          <CardHeader className="pb-3 flex-row flex-wrap items-center justify-between gap-2 space-y-0">
            <CardTitle className="text-base">{domain.name} — {classroomName}</CardTitle>
            <span className="text-xs text-muted-foreground">{grid.students.length} learners · {domain.skills.length} indicators · {termName}</span>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto nice-scroll max-h-[560px]">
              <table className="w-full text-sm">
                <thead className="sticky top-0 bg-secondary/70 backdrop-blur z-10">
                  <tr className="text-left border-b">
                    <th className="px-3 py-2.5 font-medium min-w-44">Learner</th>
                    {domain.skills.map((sk) => (
                      <th key={sk.id} className="px-2 py-2.5 font-medium text-center min-w-24 max-w-40">
                        <span className="line-clamp-3 font-normal text-[11px] leading-tight" title={sk.name}>{sk.name}</span>
                      </th>
                    ))}
                    <th className="px-3 py-2.5 font-medium text-center w-24">Rated</th>
                    <th className="px-3 py-2.5 w-24"></th>
                  </tr>
                </thead>
                <tbody>
                  {grid.students.map((s) => {
                    const rated = domain.skills.filter((sk) => cells[s.id]?.[sk.id]).length;
                    const dirty = dirtyFor(s.id);
                    return (
                      <tr key={s.id} className="border-b last:border-0 hover:bg-secondary/30">
                        <td className="px-3 py-2 whitespace-nowrap">
                          {s.rollNo != null && <span className="text-muted-foreground mr-1.5 tabular-nums">{s.rollNo}</span>}
                          <span className="font-medium">{s.name}</span>
                        </td>
                        {domain.skills.map((sk) => {
                          const v = cells[s.id]?.[sk.id] ?? "";
                          return (
                            <td key={sk.id} className="px-2 py-2 text-center">
                              <Select value={v || "__none__"} onValueChange={(code) => setCell(s.id, sk.id, code === "__none__" ? "" : code)}>
                                <SelectTrigger className="h-8 w-20 mx-auto font-semibold" aria-label={`${s.name} — ${sk.name}`}>
                                  <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                  <SelectItem value="__none__">—</SelectItem>
                                  {grid.scale.map((sc) => <SelectItem key={sc.id} value={sc.code}>{codeBadge(sc.code)}</SelectItem>)}
                                </SelectContent>
                              </Select>
                            </td>
                          );
                        })}
                        <td className="px-3 py-2 text-center tabular-nums text-xs text-muted-foreground">{rated}/{domain.skills.length}</td>
                        <td className="px-3 py-2 text-center">
                          {busy === s.id ? (
                            <span className="text-xs text-muted-foreground">saving…</span>
                          ) : dirty ? (
                            <Button size="sm" variant="outline" className="h-7 gap-1 text-[11px]" onClick={() => save([s.id])}><Save className="size-3" /> Save</Button>
                          ) : (
                            <Badge variant="outline" className="text-[10px] gap-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200">saved</Badge>
                          )}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}

/* ═══════════════════════════════════════════════════════════════
   SBA SHEET — the four fixed School-Based Assessment columns plus the
   terminal exam, one row per learner. Persists as ordinary Assessment
   + Mark rows ("SBA Class Test · <term>" etc.) and auto-computes the
   sub-total, class score, exam score and total live.               */
type SbaSheet = {
  classroom: { id: string; name: string };
  subject: { id: string; name: string };
  term: { id: string; name: string; yearName: string };
  config: Record<string, number> & { actualMax: number; examWeightPct: number };
  columns: { key: string; label: string; type: string; max: number }[];
  examMax: number;
  students: { id: string; name: string; rollNo: number | null; scores: Record<string, number | null> }[];
};

function SbaSheetTab({ classId, subjects, termId, termName }: { classId: string | null; subjects: { id: string; name: string }[]; termId: string | null; termName: string }) {
  const { currentSchoolId } = useStore();
  const [subjectId, setSubjectId] = useState<string | null>(null);
  const [sheet, setSheet] = useState<SbaSheet | null>(null);
  const [cells, setCells] = useState<Record<string, Record<string, string>>>({}); // studentId → column key → raw string
  const [busy, setBusy] = useState<string | null>(null);
  const [overwrite, setOverwrite] = useState(false);

  useEffect(() => {
    if (!currentSchoolId || !classId || !subjectId || !termId) { setSheet(null); return; }
    apiGet<SbaSheet>(`/api/school/${currentSchoolId}/ops/academics?action=sba-sheet&classroomId=${classId}&subjectId=${subjectId}&termId=${termId}`)
      .then((d) => {
        setSheet(d);
        const init: Record<string, Record<string, string>> = {};
        for (const s of d.students) {
          init[s.id] = {};
          for (const k of [...d.columns.map((c) => c.key), "exam"]) init[s.id][k] = s.scores[k] !== null && s.scores[k] !== undefined ? String(s.scores[k]) : "";
        }
        setCells(init);
      })
      .catch((e) => toast({ title: "Could not load SBA sheet", description: e.message, variant: "destructive" }));
  }, [currentSchoolId, classId, subjectId, termId]);

  const clampTo = (raw: string, max: number) => {
    const n = Number(raw);
    if (!Number.isFinite(n)) return "";
    return String(Math.max(0, Math.min(max, n)));
  };
  const num = (sid: string, key: string) => {
    const v = Number(cells[sid]?.[key]);
    return Number.isFinite(v) && cells[sid]?.[key] !== "" ? v : null;
  };

  const weight = sheet?.config["sba.classScoreWeightPct"] ?? 50;
  const actualMax = sheet?.config.actualMax ?? 60;

  const compute = (sid: string) => {
    const cols = sheet?.columns ?? [];
    const subtotal = cols.reduce((s, c) => s + (num(sid, c.key) ?? 0), 0);
    const classScore = actualMax > 0 ? Math.min(weight, (subtotal * weight) / actualMax) : 0;
    const exam = num(sid, "exam");
    const examScore = exam === null ? null : (exam * (100 - weight)) / 100;
    return { subtotal, classScore, examScore, total: classScore + (examScore ?? 0) };
  };

  function setCell(sid: string, key: string, value: string, max: number) {
    setCells((c) => ({ ...c, [sid]: { ...(c[sid] ?? {}), [key]: clampTo(value, max) } }));
  }

  async function save() {
    if (!currentSchoolId || !classId || !subjectId || !termId || !sheet) return;
    setBusy("save");
    try {
      const res = await apiPost<{ saved: number }>(`/api/school/${currentSchoolId}/ops/academics`, {
        action: "sba-sheet-save", classroomId: classId, subjectId, termId,
        rows: sheet.students.map((s) => ({
          studentId: s.id,
          classTest: num(s.id, "classTest"), groupWork: num(s.id, "groupWork"),
          test2: num(s.id, "test2"), project: num(s.id, "project"), exam: num(s.id, "exam"),
        })),
      });
      toast({ title: "SBA sheet saved", description: `${res.saved} marks across the four SBA columns and the exam — visible as ordinary assessments too.` });
    } catch (e: any) {
      toast({ title: "Save failed", description: e.message, variant: "destructive" });
    } finally { setBusy(null); }
  }

  async function autoFill() {
    if (!currentSchoolId || !classId || !subjectId || !termId) return;
    setBusy("fill");
    try {
      const res = await apiPost<{ filled: number; skipped: number; portfolioPct: number }>(`/api/school/${currentSchoolId}/ops/academics`, {
        action: "sba-portfolio-fill", classroomId: classId, subjectId, termId, overwrite,
      });
      toast({ title: "Class test auto-filled from portfolio", description: `${res.filled} learners filled${res.skipped ? `, ${res.skipped} skipped (no portfolio entries or already scored${overwrite ? "" : " — tick overwrite to replace"})` : ""} at ${res.portfolioPct}% weight.` });
      // reload the sheet with the persisted marks
      const d = await apiGet<SbaSheet>(`/api/school/${currentSchoolId}/ops/academics?action=sba-sheet&classroomId=${classId}&subjectId=${subjectId}&termId=${termId}`);
      setSheet(d);
      const init: Record<string, Record<string, string>> = {};
      for (const s of d.students) {
        init[s.id] = {};
        for (const k of [...d.columns.map((c) => c.key), "exam"]) init[s.id][k] = s.scores[k] !== null && s.scores[k] !== undefined ? String(s.scores[k]) : "";
      }
      setCells(init);
    } catch (e: any) {
      toast({ title: "Auto-fill failed", description: e.message, variant: "destructive" });
    } finally { setBusy(null); }
  }

  return (
    <div className="space-y-4">
      <Card>
        <CardContent className="pt-4 space-y-3">
          <div className="flex flex-wrap items-end gap-3">
            <div className="space-y-1.5 min-w-56">
              <Label className="text-xs">Subject</Label>
              <Select value={subjectId ?? ""} onValueChange={setSubjectId}>
                <SelectTrigger><SelectValue placeholder="Select subject" /></SelectTrigger>
                <SelectContent>{subjects.map((s) => <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            {sheet && (
              <div className="flex flex-wrap gap-2">
                <Badge variant="secondary" className="gap-1">Sub-total out of {sheet.config.actualMax}</Badge>
                <Badge variant="secondary" className="gap-1">Class {weight}% · Exam {sheet.config.examWeightPct}%</Badge>
                {sheet.config.actualMax !== 60 && <Badge variant="outline" className="gap-1 border-amber-400 text-amber-700 dark:text-amber-400">non-standard sub-total</Badge>}
              </div>
            )}
            <div className="flex flex-wrap gap-2 items-center ml-auto">
              <label className="flex items-center gap-1.5 text-xs text-muted-foreground">
                <input type="checkbox" className="accent-primary size-3.5" checked={overwrite} onChange={(e) => setOverwrite(e.target.checked)} />
                overwrite existing
              </label>
              <Button size="sm" variant="outline" className="gap-1.5" onClick={autoFill} disabled={!classId || !subjectId || busy !== null}>
                <Wand2 className="size-4" /> {busy === "fill" ? "Filling…" : "Auto-fill class test from portfolio"}
              </Button>
              <Button size="sm" className="gap-1.5" onClick={save} disabled={!sheet || busy !== null}>
                <Save className="size-4" /> {busy === "save" ? "Saving…" : "Save sheet"}
              </Button>
            </div>
          </div>
          <p className="text-xs text-muted-foreground">
            Class score = sub-total × {weight} ÷ {actualMax || "…"} (capped at {weight}) · Exam score = raw exam × {100 - weight} ÷ 100 · Total = class + exam.
            {" "}Saving creates the four SBA assessments (e.g. <i>SBA Class Test · {termName}</i>) plus the end-of-term paper as ordinary assessment rows.
          </p>
        </CardContent>
      </Card>

      {!classId ? (
        <Card><CardContent><EmptyState icon={<ClipboardCheck className="size-5" />} title="Pick a class above to open its SBA sheet" hint="The four fixed columns — Class Test, Group Work, Test 2, Project — plus the terminal exam." /></CardContent></Card>
      ) : !subjectId || !sheet ? (
        <Card><CardContent><EmptyState icon={<ClipboardCheck className="size-5" />} title={subjectId ? "Loading SBA sheet…" : "Pick a subject to load the sheet"} hint="Column maxima come from Settings → SBA marks config." /></CardContent></Card>
      ) : sheet.students.length === 0 ? (
        <Card><CardContent><EmptyState icon={<ClipboardList className="size-5" />} title="No learners enrolled in this class" /></CardContent></Card>
      ) : (
        <Card>
          <CardHeader className="pb-3 flex-row flex-wrap items-center justify-between gap-2 space-y-0">
            <CardTitle className="text-base">{sheet.classroom.name} · {sheet.subject.name} — SBA sheet</CardTitle>
            <span className="text-xs text-muted-foreground">{sheet.term.name}, {sheet.term.yearName} · {sheet.students.length} learners</span>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto nice-scroll max-h-[560px]">
              <table className="w-full text-sm">
                <thead className="sticky top-0 bg-secondary/70 backdrop-blur z-10">
                  <tr className="text-left border-b">
                    <th className="px-3 py-2.5 font-medium min-w-44">Learner</th>
                    {sheet.columns.map((c) => (
                      <th key={c.key} className="px-2 py-2.5 font-medium text-center w-24">
                        <div>{c.label}</div>
                        <div className="text-[10px] font-normal text-muted-foreground">max {c.max}</div>
                      </th>
                    ))}
                    <th className="px-2 py-2.5 font-medium text-center w-20">Sub-total<div className="text-[10px] font-normal text-muted-foreground">/{sheet.config.actualMax}</div></th>
                    <th className="px-2 py-2.5 font-medium text-center w-20">Class score<div className="text-[10px] font-normal text-muted-foreground">/{weight}</div></th>
                    <th className="px-2 py-2.5 font-medium text-center w-24">Exam<div className="text-[10px] font-normal text-muted-foreground">/100 raw</div></th>
                    <th className="px-2 py-2.5 font-medium text-center w-20">Exam score<div className="text-[10px] font-normal text-muted-foreground">/{sheet.config.examWeightPct}</div></th>
                    <th className="px-3 py-2.5 font-medium text-center w-20">Total<div className="text-[10px] font-normal text-muted-foreground">/100</div></th>
                  </tr>
                </thead>
                <tbody>
                  {sheet.students.map((s) => {
                    const c = compute(s.id);
                    return (
                      <tr key={s.id} className="border-b last:border-0 hover:bg-secondary/30">
                        <td className="px-3 py-2 whitespace-nowrap">
                          {s.rollNo != null && <span className="text-muted-foreground mr-1.5 tabular-nums">{s.rollNo}</span>}
                          <span className="font-medium">{s.name}</span>
                        </td>
                        {sheet.columns.map((col) => (
                          <td key={col.key} className="px-2 py-2">
                            <Input
                              type="number" min={0} max={col.max} step="0.5"
                              className="h-8 w-20 text-center tabular-nums mx-auto"
                              value={cells[s.id]?.[col.key] ?? ""}
                              onChange={(e) => setCell(s.id, col.key, e.target.value, col.max)}
                              placeholder="—"
                              aria-label={`${s.name} — ${col.label}`}
                            />
                          </td>
                        ))}
                        <td className="px-2 py-2 text-center font-semibold tabular-nums">{round1(c.subtotal)}</td>
                        <td className="px-2 py-2 text-center font-semibold tabular-nums">{round1(c.classScore)}</td>
                        <td className="px-2 py-2">
                          <Input
                            type="number" min={0} max={100} step="0.5"
                            className="h-8 w-20 text-center tabular-nums mx-auto"
                            value={cells[s.id]?.["exam"] ?? ""}
                            onChange={(e) => setCell(s.id, "exam", e.target.value, 100)}
                            placeholder="—"
                            aria-label={`${s.name} — terminal exam raw score`}
                          />
                        </td>
                        <td className="px-2 py-2 text-center font-semibold tabular-nums">{c.examScore === null ? <span className="text-muted-foreground font-normal">—</span> : round1(c.examScore)}</td>
                        <td className="px-3 py-2 text-center font-bold tabular-nums">{round1(c.total)}</td>
                      </tr>
                    );
                  })}
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

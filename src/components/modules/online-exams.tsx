"use client";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard } from "@/components/ui-helpers";
import { fmtDate } from "@/lib/format";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Laptop, Plus, Send, Lock, Trash2, PencilLine, Users, Trophy, Clock, CheckCircle2, Printer, Radio, Eye, EyeOff, Sparkles, FileText, Loader2, ScrollText, Grid3x3, Shuffle, Rows3 } from "lucide-react";

type Question = {
  id?: string; type: string; prompt: string;
  optionA?: string | null; optionB?: string | null; optionC?: string | null; optionD?: string | null;
  correctOption: string; blankAnswers?: string | null; marks: number;
};
type Attempt = { id: string; studentId: string; student: string; score: number | null; max: number; startedAt?: string | null; submittedAt: string };
type Exam = {
  id: string; title: string; subjectName: string; description: string | null;
  classroom: string | null; classroomId: string | null; durationMins: number; scheduledAt: string | null;
  status: string; createdByName: string | null; createdAt: string;
  questionCount: number; totalMarks: number; avgScore: number | null;
  questions: { id: string; type: string; prompt: string; optionA: string | null; optionB: string | null; optionC: string | null; optionD: string | null; correctOption: string; blankAnswers: string | null; marks: number }[];
  attempts: Attempt[];
};

const STATUS_STYLE: Record<string, string> = { DRAFT: "bg-zinc-500", PUBLISHED: "bg-emerald-600", CLOSED: "bg-slate-600" };
const TYPE_LABEL: Record<string, string> = { MCQ: "Multiple choice", TRUE_FALSE: "True / False", FILL_BLANKS: "Fill in the blanks" };

/** render a fill-blanks prompt: "-" → underline gaps */
export function renderBlanks(prompt: string): (string | { blank: true })[] {
  return prompt.split("-").map((part, i, all) => (i === 0 ? part : [{ blank: true as const }, part])).flat();
}

export function OnlineExams() {
  const { currentSchoolId, session, navTarget } = useStore();
  const [exams, setExams] = useState<Exam[] | null>(null);
  const [edit, setEdit] = useState<Exam | "new" | null>(null);
  const [resultsFor, setResultsFor] = useState<Exam | null>(null);
  const [printFor, setPrintFor] = useState<Exam | null>(null);
  const [monitorFor, setMonitorFor] = useState<Exam | null>(null);
  const [tab, setTab] = useState(navTarget?.tab === "studio" ? "studio" : "exams");

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<{ exams: Exam[] }>(`/api/school/${currentSchoolId}/ops/exams`)
      .then((d) => setExams(d.exams))
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  }, [currentSchoolId]);
  useEffect(() => { load(); }, [load]);

  async function act(body: any, ok: string) {
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/exams`, body);
      toast({ title: ok });
      load();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
  }

  if (!exams) return <LoadingBlock label="Loading online exams…" />;
  const published = exams.filter((e) => e.status === "PUBLISHED");
  const attempts = exams.reduce((s, e) => s + e.attempts.length, 0);

  return (
    <div>
      <ModuleHeader
        title="Online Exams"
        description="Publish MCQ, true/false and fill-in-the-blanks papers to a class, watch learners sit them live, print the question paper, and auto-mark — iEduPro online_exam parity."
        actions={<Button size="sm" className="gap-1.5 h-8" onClick={() => setEdit("new")}><Plus className="size-4" /> Create exam</Button>}
      />
      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex-wrap h-auto">
          <TabsTrigger value="exams">Exams ({exams.length})</TabsTrigger>
          <TabsTrigger value="studio" className="gap-1.5"><Sparkles className="size-3.5" /> AI Paper Studio</TabsTrigger>
          <TabsTrigger value="hall" className="gap-1.5"><Grid3x3 className="size-3.5" /> Exam hall</TabsTrigger>
        </TabsList>

        <TabsContent value="exams" className="mt-2">
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <StatCard icon={<Laptop className="size-4" />} label="Exams" value={exams.length} sub={`${published.length} open now`} />
        <StatCard icon={<Users className="size-4" />} label="Attempts" value={attempts} sub="submissions marked" tone="primary" />
        <StatCard icon={<Trophy className="size-4" />} label="Best average" value={exams.length ? `${Math.max(...exams.map((e) => e.avgScore ?? 0))}%` : "—"} sub="across closed exams" tone="gold" />
        <StatCard icon={<Clock className="size-4" />} label="Drafts" value={exams.filter((e) => e.status === "DRAFT").length} sub="awaiting publish" />
      </div>

      {exams.length ? (
        <Card>
          <CardHeader className="pb-3"><CardTitle className="text-base">Exam papers</CardTitle></CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Exam</TableHead>
                  <TableHead className="hidden md:table-cell">Class</TableHead>
                  <TableHead className="hidden sm:table-cell">Questions</TableHead>
                  <TableHead className="hidden lg:table-cell">Duration</TableHead>
                  <TableHead>Attempts</TableHead>
                  <TableHead>Avg</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {exams.map((e) => (
                  <TableRow key={e.id}>
                    <TableCell>
                      <div className="font-medium flex items-center gap-2">
                        <span className={cn("size-2 rounded-full", STATUS_STYLE[e.status] ?? "bg-zinc-400")} />
                        {e.title}
                      </div>
                      <div className="text-[11px] text-muted-foreground">{e.subjectName} · by {e.createdByName ?? "—"} · {fmtDate(e.createdAt)}</div>
                    </TableCell>
                    <TableCell className="hidden md:table-cell">{e.classroom ?? "All classes"}</TableCell>
                    <TableCell className="hidden sm:table-cell">{e.questionCount} · {e.totalMarks} marks</TableCell>
                    <TableCell className="hidden lg:table-cell">{e.durationMins} min</TableCell>
                    <TableCell className="tabular-nums">{e.attempts.length}</TableCell>
                    <TableCell className="tabular-nums">{e.avgScore != null ? `${Math.round((e.avgScore / Math.max(1, e.totalMarks)) * 100)}%` : "—"}</TableCell>
                    <TableCell className="text-right">
                      <div className="inline-flex gap-1">
                        <Button size="sm" variant="ghost" className="h-7 gap-1" title="Print question paper" onClick={() => setPrintFor(e)}><Printer className="size-3.5" /></Button>
                        {e.status === "DRAFT" && (
                          <>
                            <Button size="sm" variant="ghost" className="h-7 gap-1" onClick={() => setEdit(e)}><PencilLine className="size-3.5" /> Edit</Button>
                            <Button size="sm" variant="ghost" className="h-7 gap-1 text-emerald-600" onClick={() => act({ action: "publish", id: e.id }, "Exam published")}><Send className="size-3.5" /> Publish</Button>
                            <Button size="sm" variant="ghost" className="h-7 gap-1 text-destructive" onClick={() => act({ action: "delete", id: e.id }, "Draft deleted")}><Trash2 className="size-3.5" /></Button>
                          </>
                        )}
                        {e.status === "PUBLISHED" && (
                          <>
                            <Button size="sm" variant="ghost" className="h-7 gap-1 text-violet-600" onClick={() => setMonitorFor(e)}><Radio className="size-3.5" /> Monitor</Button>
                            <Button size="sm" variant="ghost" className="h-7 gap-1" onClick={() => setResultsFor(e)}>Results</Button>
                            <Button size="sm" variant="ghost" className="h-7 gap-1" onClick={() => act({ action: "close", id: e.id }, "Exam closed")}><Lock className="size-3.5" /> Close</Button>
                          </>
                        )}
                        {e.status === "CLOSED" && (
                          <Button size="sm" variant="ghost" className="h-7 gap-1" onClick={() => setResultsFor(e)}>Results</Button>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      ) : (
        <EmptyState icon={<Laptop className="size-8" />} title="No online exams yet" hint="Create one — add questions of any type, publish it to a class, and learners sit it from the student portal." />
      )}
        </TabsContent>

        <TabsContent value="studio" className="mt-2">
          <ExamStudio onSavedExam={() => { setTab("exams"); load(); }} />
        </TabsContent>

        <TabsContent value="hall" className="mt-2">
          <ExamHall />
        </TabsContent>
      </Tabs>

      <ExamEditor exam={edit} onOpenChange={(o) => !o && setEdit(null)} onDone={() => { setEdit(null); load(); }} />
      <ResultsDialog exam={resultsFor} onOpenChange={(o) => !o && setResultsFor(null)} />
      <PrintPaperDialog exam={printFor} onOpenChange={(o) => !o && setPrintFor(null)} />
      <MonitorDialog exam={monitorFor} onOpenChange={(o) => !o && setMonitorFor(null)} />
    </div>
  );
}

const blankQuestion = (type = "MCQ"): Question =>
  type === "MCQ" ? { type, prompt: "", optionA: "", optionB: "", optionC: "", optionD: "", correctOption: "A", marks: 1 }
  : type === "TRUE_FALSE" ? { type, prompt: "", correctOption: "TRUE", marks: 1 }
  : { type, prompt: "", correctOption: "-", blankAnswers: "", marks: 1 };

function ExamEditor({ exam, onOpenChange, onDone }: { exam: Exam | "new" | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [title, setTitle] = useState("");
  const [subject, setSubject] = useState("");
  const [description, setDescription] = useState("");
  const [classroomId, setClassroomId] = useState<string>("all");
  const [duration, setDuration] = useState("30");
  const [questions, setQuestions] = useState<Question[]>([blankQuestion()]);
  const [busy, setBusy] = useState(false);
  const [classrooms, setClassrooms] = useState<{ id: string; name: string }[]>([]);

  useEffect(() => {
    if (!currentSchoolId) return;
    apiGet<any>(`/api/school/${currentSchoolId}/settings`).then((d) => setClassrooms(d.classrooms ?? [])).catch(() => {});
  }, [currentSchoolId]);

  useEffect(() => {
    if (exam && exam !== "new") {
      setTitle(exam.title); setSubject(exam.subjectName); setDescription(exam.description ?? "");
      setClassroomId(exam.classroomId ?? "all"); setDuration(String(exam.durationMins));
      setQuestions(exam.questions.map((q) => ({
        ...q,
        blankAnswers: q.blankAnswers ? (JSON.parse(q.blankAnswers) as string[]).join(", ") : "",
      })));
    } else if (exam === "new") {
      setTitle(""); setSubject(""); setDescription(""); setClassroomId("all"); setDuration("30"); setQuestions([blankQuestion()]);
    }
  }, [exam]);

  function setQ(i: number, patch: Partial<Question>) {
    setQuestions((qs) => qs.map((q, j) => (j === i ? { ...q, ...patch } : q)));
  }
  function switchType(i: number, type: string) {
    setQuestions((qs) => qs.map((q, j) => (j === i ? { ...blankQuestion(type), prompt: q.prompt, marks: q.marks } : q)));
  }

  async function save() {
    if (!title.trim() || !subject.trim()) { toast({ title: "Title and subject are required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/exams`, {
        action: "save-exam",
        id: exam && exam !== "new" ? exam.id : undefined,
        title, subjectName: subject, description, classroomId: classroomId === "all" ? null : classroomId,
        durationMins: Number(duration) || 30,
        questions,
      });
      toast({ title: "Exam saved", description: "Publish it when you're ready — learners then see it in the student portal." });
      onDone();
    } catch (e: any) { toast({ title: "Save failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!exam} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[92vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle>{exam && exam !== "new" ? "Edit exam" : "Create online exam"}</DialogTitle>
          <DialogDescription>Multiple choice, true/false and fill-in-the-blanks questions — marking is automatic.</DialogDescription>
        </DialogHeader>
        <div className="grid sm:grid-cols-2 gap-3">
          <div className="space-y-1.5">
            <Label>Title *</Label>
            <Input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="e.g. Fractions — end-of-unit check" />
          </div>
          <div className="space-y-1.5">
            <Label>Subject *</Label>
            <Input value={subject} onChange={(e) => setSubject(e.target.value)} placeholder="Mathematics" />
          </div>
          <div className="space-y-1.5">
            <Label>Class</Label>
            <Select value={classroomId} onValueChange={setClassroomId}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All classes</SelectItem>
                {classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Duration (minutes)</Label>
            <Input type="number" min={5} value={duration} onChange={(e) => setDuration(e.target.value)} />
          </div>
          <div className="space-y-1.5 sm:col-span-2">
            <Label>Instructions</Label>
            <Input value={description} onChange={(e) => setDescription(e.target.value)} placeholder="Optional — shown to learners before they start" />
          </div>
        </div>
        <div className="pt-2 space-y-3">
          <div className="flex items-center justify-between">
            <Label className="text-sm font-semibold">Questions ({questions.length})</Label>
            <Button size="sm" variant="outline" className="h-7 gap-1" onClick={() => setQuestions((qs) => [...qs, blankQuestion()])}><Plus className="size-3.5" /> Add</Button>
          </div>
          {questions.map((q, i) => {
            const blanks = q.prompt ? q.prompt.split("-").length - 1 : 0;
            const words = (q.blankAnswers ?? "").split(",").map((w) => w.trim()).filter(Boolean).length;
            return (
              <div key={i} className="rounded-xl border p-3 space-y-2">
                <div className="flex items-start gap-2">
                  <span className="mt-2 text-xs font-bold text-muted-foreground">Q{i + 1}</span>
                  <div className="flex-1 space-y-2">
                    <div className="flex items-center gap-2">
                      <Select value={q.type} onValueChange={(v) => switchType(i, v)}>
                        <SelectTrigger className="h-8 w-44"><SelectValue /></SelectTrigger>
                        <SelectContent>
                          <SelectItem value="MCQ">{TYPE_LABEL.MCQ}</SelectItem>
                          <SelectItem value="TRUE_FALSE">{TYPE_LABEL.TRUE_FALSE}</SelectItem>
                          <SelectItem value="FILL_BLANKS">{TYPE_LABEL.FILL_BLANKS}</SelectItem>
                        </SelectContent>
                      </Select>
                      <div className="ml-auto flex items-center gap-2">
                        <span className="text-xs text-muted-foreground">Marks</span>
                        <Input type="number" min={1} value={q.marks} onChange={(e) => setQ(i, { marks: Number(e.target.value) || 1 })} className="h-8 w-16" />
                        {questions.length > 1 && (
                          <Button size="sm" variant="ghost" className="h-8 text-destructive" onClick={() => setQuestions((qs) => qs.filter((_, j) => j !== i))}><Trash2 className="size-3.5" /></Button>
                        )}
                      </div>
                    </div>
                    <Input value={q.prompt} onChange={(e) => setQ(i, { prompt: e.target.value })}
                      placeholder={q.type === "FILL_BLANKS" ? "The capital of Ghana is - and it lies on the - coast." : "Question prompt…"} />
                    {q.type === "MCQ" && (
                      <>
                        <div className="grid grid-cols-2 gap-2">
                          {(["A", "B", "C", "D"] as const).map((L) => (
                            <Input key={L} value={(q as any)[`option${L}`] ?? ""} onChange={(e) => setQ(i, { [`option${L}`]: e.target.value } as any)} placeholder={`Option ${L}`} className="h-8" />
                          ))}
                        </div>
                        <div className="flex items-center gap-2">
                          <span className="text-xs text-muted-foreground">Correct</span>
                          <Select value={q.correctOption} onValueChange={(v) => setQ(i, { correctOption: v })}>
                            <SelectTrigger className="h-8 w-20"><SelectValue /></SelectTrigger>
                            <SelectContent>{["A", "B", "C", "D"].map((o) => <SelectItem key={o} value={o}>{o}</SelectItem>)}</SelectContent>
                          </Select>
                        </div>
                      </>
                    )}
                    {q.type === "TRUE_FALSE" && (
                      <div className="flex items-center gap-2">
                        <span className="text-xs text-muted-foreground">Correct answer</span>
                        {(["TRUE", "FALSE"] as const).map((v) => (
                          <Button key={v} size="sm" variant={q.correctOption === v ? "default" : "outline"} className="h-8"
                            onClick={() => setQ(i, { correctOption: v })}>{v === "TRUE" ? "True" : "False"}</Button>
                        ))}
                      </div>
                    )}
                    {q.type === "FILL_BLANKS" && (
                      <>
                        <Input value={q.blankAnswers ?? ""} onChange={(e) => setQ(i, { blankAnswers: e.target.value })}
                          placeholder="Accepted words, comma-separated, in blank order — e.g. Accra, Gulf" className="h-8" />
                        <div className="text-[11px] flex items-center gap-2">
                          <span className={cn("font-medium", blanks === 0 ? "text-amber-600" : words === blanks ? "text-emerald-600" : "text-amber-600")}>
                            {blanks} blank{blanks === 1 ? "" : "s"} in prompt · {words} answer word{words === 1 ? "" : "s"}
                          </span>
                          <span className="text-muted-foreground">— mark each blank with a hyphen “-”; matching is case-insensitive.</span>
                        </div>
                        <div className="rounded-md bg-secondary/50 px-3 py-1.5 text-sm">
                          {renderBlanks(q.prompt || "The capital of Ghana is - …").map((part, j) =>
                            typeof part === "string" ? <span key={j}>{part}</span> : <span key={j} className="inline-block w-16 border-b border-foreground/60 mx-1 align-baseline">&nbsp;</span>
                          )}
                        </div>
                      </>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={save} disabled={busy} className="gap-1.5">{busy ? "Saving…" : <><CheckCircle2 className="size-4" /> Save draft</>}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function ResultsDialog({ exam, onOpenChange }: { exam: Exam | null; onOpenChange: (o: boolean) => void }) {
  if (!exam) return <Dialog open={false} onOpenChange={onOpenChange}><DialogContent /></Dialog>;
  const sorted = [...exam.attempts].sort((a, b) => (b.score ?? 0) - (a.score ?? 0));
  return (
    <Dialog open={!!exam} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle>Results — {exam.title}</DialogTitle>
          <DialogDescription>{exam.subjectName} · {exam.classroom ?? "all classes"} · {exam.questionCount} questions · {exam.totalMarks} marks</DialogDescription>
        </DialogHeader>
        {sorted.length ? (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>#</TableHead>
                <TableHead>Learner</TableHead>
                <TableHead className="text-right">Score</TableHead>
                <TableHead className="text-right">%</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {sorted.map((a, i) => (
                <TableRow key={a.id}>
                  <TableCell className="tabular-nums">{i + 1}</TableCell>
                  <TableCell className="font-medium">{a.student}</TableCell>
                  <TableCell className="text-right tabular-nums">{a.score}/{a.max}</TableCell>
                  <TableCell className="text-right tabular-nums font-semibold">{a.max ? Math.round(((a.score ?? 0) / a.max) * 100) : 0}%</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        ) : <EmptyState title="No attempts yet" hint="Learners submit from the student portal once the exam is published." />}
      </DialogContent>
    </Dialog>
  );
}

/** Printable question paper — CI3 online_exam_questions_print_view parity. */
function PrintPaperDialog({ exam, onOpenChange }: { exam: Exam | null; onOpenChange: (o: boolean) => void }) {
  const { currentSchoolId } = useStore();
  const [withAnswers, setWithAnswers] = useState(false);
  const [schoolName, setSchoolName] = useState("");
  const [logoUrl, setLogoUrl] = useState<string | null>(null);

  useEffect(() => {
    if (!currentSchoolId || !exam) return;
    apiGet<any>(`/api/school/${currentSchoolId}/settings`)
      .then((d) => { setSchoolName(d.school?.name ?? ""); setLogoUrl(d.branding?.logoUrl ?? null); })
      .catch(() => {});
  }, [currentSchoolId, exam]);

  if (!exam) return <Dialog open={false} onOpenChange={onOpenChange}><DialogContent /></Dialog>;
  const blankWords = (q: { blankAnswers: string | null }) =>
    q.blankAnswers ? (JSON.parse(q.blankAnswers) as string[]).join(", ") : "";

  return (
    <Dialog open={!!exam} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-3xl max-h-[92vh] overflow-y-auto nice-scroll">
        <DialogHeader className="no-print">
          <DialogTitle>Print question paper</DialogTitle>
          <DialogDescription>{exam.title} · {exam.questionCount} questions · {exam.totalMarks} marks</DialogDescription>
        </DialogHeader>

        <div className="no-print flex items-center justify-between rounded-lg border bg-secondary/30 px-3 py-2">
          <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={() => setWithAnswers((v) => !v)}>
            {withAnswers ? <><EyeOff className="size-3.5" /> Without answers</> : <><Eye className="size-3.5" /> With answers</>}
          </Button>
          <Button size="sm" className="h-8 gap-1.5" onClick={() => window.print()}><Printer className="size-3.5" /> Print</Button>
        </div>

        {/* ── printable paper ── */}
        <div className="print-area rounded-lg border p-6 text-black bg-white" style={{ fontFamily: "Georgia, 'Times New Roman', serif" }}>
          <div className="text-center border-b-2 border-black pb-3 mb-4">
            {logoUrl && <img src={logoUrl} alt="" className="h-16 mx-auto mb-2" />}
            <div className="text-xl font-bold tracking-widest uppercase">{schoolName || "School"}</div>
            <div className="mt-2 inline-block bg-black text-white px-4 py-1 rounded-full text-sm font-semibold">{exam.title}</div>
            <div className="mt-2 text-sm">
              Class: {exam.classroom ?? "All classes"} &nbsp;·&nbsp; Subject: {exam.subjectName}
            </div>
            <div className="text-sm">
              Total marks: {exam.totalMarks} &nbsp;·&nbsp; Time: {exam.durationMins} minutes
            </div>
            {exam.description && <div className="mt-2 text-xs italic">Instructions: {exam.description}</div>}
          </div>

          {exam.questions.map((q, i) => (
            <div key={q.id} className="mb-5">
              <div className="flex justify-between gap-4">
                <div className="flex-1">
                  <span className="font-semibold">{i + 1}.</span>{" "}
                  {q.type === "FILL_BLANKS"
                    ? renderBlanks(q.prompt).map((part, j) =>
                        typeof part === "string" ? <span key={j}>{part}</span> : <span key={j} className="inline-block w-24 border-b-2 border-black mx-1 align-baseline">&nbsp;</span>
                      )
                    : <span>{q.prompt}</span>}
                </div>
                <div className="text-sm font-semibold tabular-nums">[{q.marks}]</div>
              </div>
              <div className="ml-5 mt-1">
                {q.type === "MCQ" && (
                  <ul className="list-disc ml-4 text-sm space-y-0.5">
                    {(["A", "B", "C", "D"] as const).map((L) => {
                      const text = (q as any)[`option${L}`];
                      if (!text) return null;
                      const isCorrect = withAnswers && q.correctOption === L;
                      return <li key={L} className={isCorrect ? "font-bold underline" : ""}>{L}. {text}{isCorrect ? " ✓" : ""}</li>;
                    })}
                  </ul>
                )}
                {q.type === "TRUE_FALSE" && (
                  <div className="text-sm">
                    <span className="mr-3">☐ True</span><span>☐ False</span>
                    {withAnswers && <span className="ml-3 italic font-bold">[correct answer — {q.correctOption === "TRUE" ? "True" : "False"}]</span>}
                  </div>
                )}
                {q.type === "FILL_BLANKS" && withAnswers && (
                  <div className="text-sm italic font-bold">[correct answers — {blankWords(q)}]</div>
                )}
              </div>
              <div className="mt-3 border-b border-dotted border-black/30" />
            </div>
          ))}

          <div className="mt-6 text-center text-xs italic">Good luck! End of paper.</div>
        </div>
      </DialogContent>
    </Dialog>
  );
}

type MonitorRow = { studentId: string; name: string; state: string; startedAt: string | null; submittedAt: string | null; score: number | null; max: number };

/** Live monitor — polls every 5 s while open: who has started, who is still
 *  writing, who submitted (CI3 left this a static placeholder; ours is real). */
function MonitorDialog({ exam, onOpenChange }: { exam: Exam | null; onOpenChange: (o: boolean) => void }) {
  const { currentSchoolId } = useStore();
  const [rows, setRows] = useState<MonitorRow[] | null>(null);
  const [examMeta, setExamMeta] = useState<{ title: string; status: string; totalMarks: number } | null>(null);
  const [auto, setAuto] = useState(true);
  const timer = useRef<ReturnType<typeof setInterval> | null>(null);

  const poll = useCallback(async () => {
    if (!currentSchoolId || !exam) return;
    try {
      const d = await apiGet<any>(`/api/school/${currentSchoolId}/ops/exams?monitor=${exam.id}`);
      setRows(d.roster); setExamMeta(d.exam);
    } catch (e: any) { toast({ title: "Monitor failed", description: e.message, variant: "destructive" }); }
  }, [currentSchoolId, exam]);

  useEffect(() => {
    if (!exam) return;
    if (timer.current) clearInterval(timer.current);
    // first poll runs off the sync path, then every 5 s while auto-refresh is on
    timer.current = setInterval(() => { if (auto) poll(); }, 5000);
    const first = setTimeout(poll, 0);
    return () => { if (timer.current) clearInterval(timer.current); clearTimeout(first); };
  }, [exam, auto, poll]);

  if (!exam) return <Dialog open={false} onOpenChange={onOpenChange}><DialogContent /></Dialog>;
  const counts = { not: 0, prog: 0, done: 0 };
  for (const r of rows ?? []) { if (r.state === "NOT_STARTED") counts.not++; else if (r.state === "IN_PROGRESS") counts.prog++; else counts.done++; }

  const STATE_CHIP: Record<string, string> = {
    NOT_STARTED: "bg-zinc-100 text-zinc-600",
    IN_PROGRESS: "bg-amber-100 text-amber-700",
    SUBMITTED: "bg-emerald-100 text-emerald-700",
  };

  return (
    <Dialog open={!!exam} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[92vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Radio className="size-4 text-violet-600" /> Live — {exam.title}</DialogTitle>
          <DialogDescription>
            {examMeta?.status === "PUBLISHED" ? "Refreshes every 5 seconds while the exam is open." : "Exam is closed — final states."}
          </DialogDescription>
        </DialogHeader>

        <div className="grid grid-cols-3 gap-3">
          <div className="rounded-lg border p-3 text-center"><div className="text-2xl font-bold tabular-nums">{counts.not}</div><div className="text-xs text-muted-foreground">not started</div></div>
          <div className="rounded-lg border p-3 text-center bg-amber-50"><div className="text-2xl font-bold tabular-nums text-amber-700">{counts.prog}</div><div className="text-xs text-muted-foreground">writing now</div></div>
          <div className="rounded-lg border p-3 text-center bg-emerald-50"><div className="text-2xl font-bold tabular-nums text-emerald-700">{counts.done}</div><div className="text-xs text-muted-foreground">submitted</div></div>
        </div>

        {rows === null ? <LoadingBlock label="Loading monitor…" /> : rows.length === 0 ? (
          <EmptyState title="No class roster" hint="This exam targets all classes — the monitor lists learners once they start (from the student portal)." />
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Learner</TableHead>
                <TableHead>State</TableHead>
                <TableHead className="hidden sm:table-cell">Started</TableHead>
                <TableHead className="text-right">Score</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {rows.map((r) => (
                <TableRow key={r.studentId}>
                  <TableCell className="font-medium">{r.name}</TableCell>
                  <TableCell><span className={cn("text-[11px] font-semibold px-2 py-0.5 rounded-full", STATE_CHIP[r.state])}>
                    {r.state === "NOT_STARTED" ? "Not started" : r.state === "IN_PROGRESS" ? "Writing…" : "Submitted"}
                  </span></TableCell>
                  <TableCell className="hidden sm:table-cell text-xs text-muted-foreground">{r.startedAt ? new Date(r.startedAt).toLocaleTimeString() : "—"}</TableCell>
                  <TableCell className="text-right tabular-nums">{r.score != null ? `${r.score}/${r.max}` : "—"}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}

        <div className="flex items-center justify-between">
          <label className="text-xs text-muted-foreground flex items-center gap-1.5 cursor-pointer">
            <input type="checkbox" checked={auto} onChange={(e) => setAuto(e.target.checked)} /> auto-refresh
          </label>
          <Button size="sm" variant="outline" className="h-8" onClick={poll}>Refresh now</Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}

/* ── wave 66 — AI Exam Paper Studio ──
   Type what the exam should cover; the AI drafts a complete Ghanaian paper —
   Section A objectives (A–D + answer key) and Section B theory with mark
   allocations and answer guides, NaCCA SBC style with Ghanaian context.
   Review the draft, then either save it as a printable question paper (the
   exam-paper bank in Exams & Reports) or drop Section A straight into an
   Online Exam draft the class can sit on devices, auto-marked. */
type McqDraft = { prompt: string; A: string; B: string; C: string; D: string; answer: string; marks: number };
type TheoryDraft = { prompt: string; marks: number; guide: string };
type StudioDraft = {
  title: string; sectionA: McqDraft[]; sectionB: TheoryDraft[];
  totalMarks: number; durationMins: number; className?: string; subject?: string;
};

const PAPER_TYPE_OPTS = [
  { v: "EXERCISE", label: "Class exercise" },
  { v: "CLASS_TEST", label: "Class test" },
  { v: "END_OF_TERM_PRACTICE", label: "End-of-term practice" },
  { v: "MOCK_BECE", label: "Mock BECE" },
];

function ExamStudio({ onSavedExam }: { onSavedExam: () => void }) {
  const { currentSchoolId } = useStore();
  const [classrooms, setClassrooms] = useState<{ id: string; name: string }[]>([]);
  const [form, setForm] = useState({
    subjectName: "", classroomId: "", examName: "", paperType: "CLASS_TEST",
    topics: "", numObjective: "10", numTheory: "2", durationMins: "45",
  });
  const [draft, setDraft] = useState<StudioDraft | null>(null);
  const [busy, setBusy] = useState(false);
  const [saving, setSaving] = useState<"paper" | "exam" | null>(null);
  const [includeScheme, setIncludeScheme] = useState(true);

  useEffect(() => {
    if (!currentSchoolId) return;
    apiGet<any>(`/api/school/${currentSchoolId}/settings`).then((d) => setClassrooms(d.classrooms ?? [])).catch(() => {});
  }, [currentSchoolId]);

  const klass = classrooms.find((c) => c.id === form.classroomId);

  async function generate() {
    if (!form.subjectName.trim() || !form.topics.trim()) {
      toast({ title: "Subject and topics are required", description: "Tell the AI what the paper should cover.", variant: "destructive" });
      return;
    }
    setBusy(true); setDraft(null);
    try {
      const d = await apiPost<{ draft: StudioDraft }>(`/api/school/${currentSchoolId}/ops/exam-studio`, {
        action: "exam-studio.generate",
        subjectName: form.subjectName, classroomId: form.classroomId || null, classroomName: klass?.name ?? "",
        paperType: form.paperType, topics: form.topics,
        numObjective: Number(form.numObjective) || 10, numTheory: Number(form.numTheory) || 0,
        durationMins: Number(form.durationMins) || 45,
      });
      setDraft(d.draft);
      toast({ title: "Draft ready", description: `${d.draft.sectionA.length} objectives + ${d.draft.sectionB.length} theory · ${d.draft.totalMarks} marks. Review before saving.` });
    } catch (e: any) {
      toast({ title: "Generation failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  async function savePaper() {
    if (!draft) return;
    setSaving("paper");
    try {
      const r = await apiPost<{ paper: { id: string; title: string } }>(`/api/school/${currentSchoolId}/ops/exam-studio`, {
        action: "exam-studio.save-paper",
        draft, classroomId: form.classroomId || null, examName: form.examName || null,
        subjectName: form.subjectName, durationMins: Number(form.durationMins) || 45, includeScheme,
      });
      toast({ title: "Paper saved to the bank", description: `“${r.paper.title}” — print it from Exams & Reports → Question papers.` });
    } catch (e: any) {
      toast({ title: "Could not save the paper", description: e.message, variant: "destructive" });
    } finally { setSaving(null); }
  }

  async function saveOnlineExam() {
    if (!draft) return;
    setSaving("exam");
    try {
      const questions = draft.sectionA.map((q) => ({
        type: "MCQ", prompt: q.prompt, optionA: q.A, optionB: q.B, optionC: q.C, optionD: q.D,
        correctOption: q.answer, marks: q.marks,
      }));
      await apiPost(`/api/school/${currentSchoolId}/ops/exams`, {
        action: "save-exam",
        title: draft.title, subjectName: form.subjectName || draft.subject || "Examination",
        description: draft.sectionB.length ? `AI-drafted from the Paper Studio. ${draft.sectionB.length} theory question(s) with mark allocations are in the printable paper.` : "AI-drafted from the Paper Studio.",
        classroomId: form.classroomId || null, durationMins: Number(form.durationMins) || 45,
        questions,
      });
      toast({ title: "Online exam draft created", description: "Section A is in — review it in the exam list, then publish to the class." });
      onSavedExam();
    } catch (e: any) {
      toast({ title: "Could not create the online exam", description: e.message, variant: "destructive" });
    } finally { setSaving(null); }
  }

  return (
    <div className="grid lg:grid-cols-3 gap-4">
      <Card className="lg:col-span-1">
        <CardHeader className="pb-2">
          <CardTitle className="text-base flex items-center gap-2"><Sparkles className="size-4 text-primary" /> Describe the paper</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <div className="space-y-1.5">
            <Label>Subject</Label>
            <Input value={form.subjectName} onChange={(e) => setForm({ ...form, subjectName: e.target.value })} placeholder="e.g. Integrated Science" />
          </div>
          <div className="space-y-1.5">
            <Label>Class</Label>
            <Select value={form.classroomId || "all"} onValueChange={(v) => setForm({ ...form, classroomId: v === "all" ? "" : v })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">No specific class</SelectItem>
                {classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>Paper type</Label>
              <Select value={form.paperType} onValueChange={(v) => setForm({ ...form, paperType: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {PAPER_TYPE_OPTS.map((p) => <SelectItem key={p.v} value={p.v}>{p.label}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Duration (mins)</Label>
              <Input type="number" min={10} max={180} value={form.durationMins} onChange={(e) => setForm({ ...form, durationMins: e.target.value })} />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Exam name (optional)</Label>
            <Input value={form.examName} onChange={(e) => setForm({ ...form, examName: e.target.value })} placeholder="e.g. End of Term 1 Examination 2026/27" />
          </div>
          <div className="space-y-1.5">
            <Label>Topics to cover</Label>
            <Textarea value={form.topics} onChange={(e) => setForm({ ...form, topics: e.target.value })} rows={4} placeholder="e.g. Photosynthesis and respiration; the human digestive system; forces and motion basics" />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>Objective questions</Label>
              <Input type="number" min={3} max={40} value={form.numObjective} onChange={(e) => setForm({ ...form, numObjective: e.target.value })} />
            </div>
            <div className="space-y-1.5">
              <Label>Theory questions</Label>
              <Input type="number" min={0} max={5} value={form.numTheory} onChange={(e) => setForm({ ...form, numTheory: e.target.value })} />
            </div>
          </div>
          <Button onClick={generate} disabled={busy} className="w-full gap-1.5">
            {busy ? <Loader2 className="size-4 animate-spin" /> : <Sparkles className="size-4" />}
            {busy ? "Drafting the paper…" : "Draft exam paper with AI"}
          </Button>
          <p className="text-[11px] text-muted-foreground leading-relaxed">
            You stay the examiner: nothing is saved until you review the draft. Distractors lean on common misconceptions, and every paper carries a marking scheme.
          </p>
        </CardContent>
      </Card>

      <div className="lg:col-span-2 space-y-4">
        {!draft ? (
          <Card className="border-dashed">
            <CardContent className="py-14 text-center">
              <ScrollText className="size-10 mx-auto text-muted-foreground/40" />
              <p className="text-sm font-medium mt-3">No draft yet</p>
              <p className="text-xs text-muted-foreground mt-1 max-w-md mx-auto">
                Fill in the subject, class and topics — the AI drafts a complete Section A (objectives with an answer key)
                and Section B (theory with mark allocations). Review it, then save it as a printable paper, an online exam, or both.
              </p>
            </CardContent>
          </Card>
        ) : (
          <>
            <Card>
              <CardHeader className="pb-2 flex-row flex-wrap items-center justify-between gap-2 space-y-0">
                <div>
                  <CardTitle className="text-base flex items-center gap-2"><FileText className="size-4" /> {draft.title}</CardTitle>
                  <p className="text-xs text-muted-foreground mt-1">
                    {draft.sectionA.length} objectives ({draft.sectionA.reduce((s, q) => s + q.marks, 0)} marks)
                    {draft.sectionB.length ? ` · ${draft.sectionB.length} theory (${draft.sectionB.reduce((s, q) => s + q.marks, 0)} marks)` : ""}
                    {" "}· {draft.totalMarks} marks · {draft.durationMins} minutes{draft.className ? ` · ${draft.className}` : ""}
                  </p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                  <div className="flex items-center gap-2 rounded-lg border px-3 py-2">
                    <span className="text-xs font-medium">Marking scheme in paper</span>
                    <Switch checked={includeScheme} onCheckedChange={setIncludeScheme} />
                  </div>
                  <Button size="sm" variant="outline" className="gap-1.5" disabled={saving !== null} onClick={savePaper}>
                    {saving === "paper" ? <Loader2 className="size-3.5 animate-spin" /> : <FileText className="size-3.5" />} Save printable paper
                  </Button>
                  <Button size="sm" className="gap-1.5" disabled={saving !== null || draft.sectionA.length === 0} onClick={saveOnlineExam}>
                    {saving === "exam" ? <Loader2 className="size-3.5 animate-spin" /> : <Laptop className="size-3.5" />} Save as online exam
                  </Button>
                </div>
              </CardHeader>
              <CardContent>
                <p className="text-[11px] text-muted-foreground">Saving as an online exam takes Section A only — the theory questions stay in the printable paper (learners write those in their booklets).</p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-sm">Section A — objectives ({draft.sectionA.reduce((s, q) => s + q.marks, 0)} marks)</CardTitle></CardHeader>
              <CardContent className="space-y-3 max-h-72 overflow-y-auto nice-scroll">
                {draft.sectionA.map((q, i) => (
                  <div key={i} className="rounded-lg border p-3">
                    <div className="text-sm font-medium leading-snug">{i + 1}. {q.prompt} <Badge variant="outline" className="ml-1 text-[10px]">{q.marks} mk</Badge></div>
                    <div className="mt-1.5 grid sm:grid-cols-2 gap-1 text-xs">
                      {(["A", "B", "C", "D"] as const).map((o) => (
                        <div key={o} className={cn("rounded-md border px-2 py-1", q.answer === o ? "border-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 font-semibold text-emerald-700 dark:text-emerald-300" : "text-muted-foreground")}>
                          <span className="font-medium mr-1">{o}.</span>{q[o]}
                          {q.answer === o && <CheckCircle2 className="inline size-3 ml-1" />}
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
              </CardContent>
            </Card>

            {draft.sectionB.length > 0 && (
              <Card>
                <CardHeader className="pb-2"><CardTitle className="text-sm">Section B — theory ({draft.sectionB.reduce((s, q) => s + q.marks, 0)} marks)</CardTitle></CardHeader>
                <CardContent className="space-y-3 max-h-56 overflow-y-auto nice-scroll">
                  {draft.sectionB.map((q, i) => (
                    <div key={i} className="rounded-lg border p-3">
                      <div className="text-sm font-medium leading-snug">{i + 1}. {q.prompt} <Badge variant="outline" className="ml-1 text-[10px]">{q.marks} mk</Badge></div>
                      {q.guide && <p className="text-xs text-muted-foreground mt-1.5"><span className="font-medium text-foreground">Answer guide: </span>{q.guide}</p>}
                    </div>
                  ))}
                </CardContent>
              </Card>
            )}
          </>
        )}
      </div>
    </div>
  );
}

/* ── wave 67 — Exam Hall & Seating Planner ──
   Pick the candidate classes and the hall grid; the planner distributes
   candidates — interleaved round-robin by class (neighbours are never
   classmates: the anti-copying layout every Ghanaian exam hall runs) or
   alphabetical — then the chart prints full-page with the school header. */
type HallPlan = {
  id: string; title: string; examName: string | null; rows: number; cols: number;
  interleave: boolean; classroomNames: string; candidateCount: number; createdBy: string;
  createdAt: string; updatedAt: string;
  entries: { seat: number; studentId: string; name: string; indexNo: string; className: string }[];
};
type HallDraft = {
  rows: number; cols: number; interleave: boolean; entries: HallPlan["entries"];
  candidateCount: number; classroomNames: string; blankSeats: number; byClass: Record<string, number>;
};

function ExamHall() {
  const { currentSchoolId, schools } = useStore();
  const [plans, setPlans] = useState<HallPlan[] | null>(null);
  const [classrooms, setClassrooms] = useState<{ id: string; name: string; candidates: number }[]>([]);
  const [form, setForm] = useState({ title: "", examName: "", rows: "6", cols: "6", interleave: true, classroomIds: [] as string[] });
  const [draft, setDraft] = useState<HallDraft | null>(null);
  const [viewing, setViewing] = useState<HallPlan | null>(null);
  const [busy, setBusy] = useState(false);
  const [deleting, setDeleting] = useState<HallPlan | null>(null);

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<{ plans: HallPlan[]; classrooms: { id: string; name: string; candidates: number }[] }>(`/api/school/${currentSchoolId}/ops/exam-hall`)
      .then((d) => { setPlans(d.plans); setClassrooms(d.classrooms); })
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  }, [currentSchoolId]);
  useEffect(load, [load]);

  const school = schools.find((s) => s.id === currentSchoolId);
  const pickedCandidates = classrooms.filter((c) => form.classroomIds.includes(c.id)).reduce((s, c) => s + c.candidates, 0);

  function toggleClass(id: string) {
    setForm((f) => ({ ...f, classroomIds: f.classroomIds.includes(id) ? f.classroomIds.filter((x) => x !== id) : [...f.classroomIds, id] }));
  }

  async function generate() {
    if (!form.classroomIds.length) { toast({ title: "Pick at least one class", variant: "destructive" }); return; }
    setBusy(true);
    try {
      const d = await apiPost<{ draft: HallDraft }>(`/api/school/${currentSchoolId}/ops/exam-hall`, {
        action: "hall.generate",
        classroomIds: form.classroomIds,
        rows: Number(form.rows) || 6, cols: Number(form.cols) || 6,
        interleave: form.interleave,
      });
      setDraft(d.draft);
      toast({ title: `${d.draft.candidateCount} candidates seated`, description: `${d.draft.rows} rows × ${d.draft.cols} seats${d.draft.blankSeats ? ` · ${d.draft.blankSeats} spare seats` : ""}` });
    } catch (e: any) {
      toast({ title: "Could not seat the hall", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  async function savePlan() {
    if (!draft) return;
    if (!form.title.trim()) { toast({ title: "Give the plan a title", variant: "destructive" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/exam-hall`, {
        action: "hall.save", title: form.title, examName: form.examName || null, draft,
      });
      toast({ title: "Seating plan saved", description: "Open it any time to print for the exam hall." });
      setDraft(null); setForm((f) => ({ ...f, title: "", examName: "" }));
      load();
    } catch (e: any) {
      toast({ title: "Could not save", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  async function removePlan(p: HallPlan) {
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/exam-hall`, { action: "hall.delete", id: p.id });
      toast({ title: "Plan deleted", description: p.title });
      setDeleting(null); load();
    } catch (e: any) {
      toast({ title: "Could not delete", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  /** seat grid — row-major cells with entries filled in seat order */
  const gridOf = (rows: number, cols: number, entries: HallPlan["entries"]) => {
    const cells: (HallPlan["entries"][number] | null)[] = Array.from({ length: rows * cols }, (_, i) => null);
    entries.forEach((e, i) => { if (i < cells.length) cells[i] = e; });
    return cells;
  };

  return (
    <div className="grid lg:grid-cols-3 gap-4">
      {/* planner */}
      <Card className="lg:col-span-1">
        <CardHeader className="pb-2">
          <CardTitle className="text-base flex items-center gap-2"><Grid3x3 className="size-4 text-primary" /> Seat the hall</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <div className="space-y-1.5">
            <Label>Plan title</Label>
            <Input value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} placeholder="e.g. End of Term 1 — Main Hall" />
          </div>
          <div className="space-y-1.5">
            <Label>Exam name (optional)</Label>
            <Input value={form.examName} onChange={(e) => setForm({ ...form, examName: e.target.value })} placeholder="e.g. End of Term 1 Examination" />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5"><Label>Rows</Label><Input type="number" min={1} max={20} value={form.rows} onChange={(e) => setForm({ ...form, rows: e.target.value })} /></div>
            <div className="space-y-1.5"><Label>Seats per row</Label><Input type="number" min={1} max={20} value={form.cols} onChange={(e) => setForm({ ...form, cols: e.target.value })} /></div>
          </div>
          <div className="rounded-lg border p-3 flex items-center justify-between gap-3">
            <div className="text-sm">
              <div className="font-medium flex items-center gap-1.5"><Shuffle className="size-3.5" /> Interleave classes</div>
              <div className="text-xs text-muted-foreground">Neighbours never share a class — the anti-copy pattern.</div>
            </div>
            <Switch checked={form.interleave} onCheckedChange={(v) => setForm({ ...form, interleave: v })} />
          </div>
          <div className="space-y-1.5">
            <Label>Candidate classes {form.classroomIds.length ? `· ${pickedCandidates} learners` : ""}</Label>
            <div className="rounded-lg border max-h-44 overflow-y-auto nice-scroll divide-y">
              {classrooms.map((c) => (
                <label key={c.id} className="flex items-center gap-2.5 px-3 py-2 text-sm cursor-pointer hover:bg-secondary/40">
                  <input type="checkbox" className="accent-primary" checked={form.classroomIds.includes(c.id)} onChange={() => toggleClass(c.id)} />
                  <span className="flex-1">{c.name}</span>
                  <Badge variant="secondary" className="text-[10px] tabular-nums">{c.candidates}</Badge>
                </label>
              ))}
              {!classrooms.length && <p className="px-3 py-3 text-xs text-muted-foreground">No classes yet.</p>}
            </div>
          </div>
          <Button onClick={generate} disabled={busy} className="w-full gap-1.5">
            {busy ? <Loader2 className="size-4 animate-spin" /> : <Rows3 className="size-4" />}
            {busy ? "Seating…" : "Generate seating"}
          </Button>
        </CardContent>
      </Card>

      {/* draft grid + saved plans */}
      <div className="lg:col-span-2 space-y-4">
        {draft && (
          <Card>
            <CardHeader className="pb-2 flex-row flex-wrap items-center justify-between gap-2 space-y-0">
              <div>
                <CardTitle className="text-base">{draft.candidateCount} candidates · {draft.rows}×{draft.cols}</CardTitle>
                <p className="text-xs text-muted-foreground mt-1">
                  {draft.classroomNames}{draft.interleave ? " · interleaved — neighbours from different classes" : " · alphabetical"}
                  {draft.blankSeats ? ` · ${draft.blankSeats} spare seats` : ""}
                </p>
              </div>
              <Button size="sm" className="gap-1.5" disabled={busy} onClick={savePlan}><CheckCircle2 className="size-3.5" /> Save plan</Button>
            </CardHeader>
            <CardContent className="overflow-x-auto">
              <div className="grid gap-1.5 min-w-[560px]" style={{ gridTemplateColumns: `repeat(${draft.cols}, minmax(0, 1fr))` }}>
                {gridOf(draft.rows, draft.cols, draft.entries).map((cell, i) => (
                  <div key={i} className={cn("rounded-md border px-2 py-1.5 min-h-14", cell ? "border-indigo-200 bg-indigo-50/60 dark:border-indigo-900 dark:bg-indigo-950/30" : "border-dashed border-muted-foreground/25")}>
                    {cell ? (
                      <>
                        <div className="text-[11px] font-semibold leading-tight truncate" title={cell.name}>{cell.name}</div>
                        <div className="text-[9px] text-muted-foreground truncate">{cell.className}</div>
                        <div className="text-[9px] font-mono text-muted-foreground truncate">{cell.indexNo}</div>
                      </>
                    ) : (
                      <div className="text-[10px] text-muted-foreground/50 h-full flex items-center">seat {i + 1}</div>
                    )}
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        <Card>
          <CardHeader className="pb-3"><CardTitle className="text-base">Saved seating plans ({plans?.length ?? 0})</CardTitle></CardHeader>
          <CardContent className="p-0">
            {plans && plans.length === 0 ? (
              <EmptyState icon={<Grid3x3 className="size-5" />} title="No seating plans yet" hint="Pick the classes, size the hall, and seat the exam." />
            ) : plans ? (
              <div className="overflow-x-auto nice-scroll max-h-[420px]">
                <table className="w-full text-sm">
                  <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                    <tr className="text-left border-b">
                      <th className="px-3 py-2.5 font-medium">Plan</th>
                      <th className="px-3 py-2.5 font-medium hidden md:table-cell">Classes</th>
                      <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Layout</th>
                      <th className="px-3 py-2.5 font-medium">Candidates</th>
                      <th className="px-3 py-2.5 font-medium hidden lg:table-cell">By</th>
                      <th className="px-3 py-2.5"></th>
                    </tr>
                  </thead>
                  <tbody>
                    {plans.map((p) => (
                      <tr key={p.id} className="border-b last:border-0 hover:bg-secondary/30">
                        <td className="px-3 py-2.5">
                          <div className="font-medium">{p.title}</div>
                          {p.examName && <div className="text-[11px] text-muted-foreground">{p.examName}</div>}
                        </td>
                        <td className="px-3 py-2.5 hidden md:table-cell text-muted-foreground text-xs">{p.classroomNames}</td>
                        <td className="px-3 py-2.5 hidden sm:table-cell text-muted-foreground">{p.rows}×{p.cols} {p.interleave && <Badge variant="outline" className="text-[9px] ml-1">interleaved</Badge>}</td>
                        <td className="px-3 py-2.5 tabular-nums font-medium">{p.candidateCount}</td>
                        <td className="px-3 py-2.5 hidden lg:table-cell text-muted-foreground">{p.createdBy}</td>
                        <td className="px-3 py-2.5 text-right whitespace-nowrap">
                          <Button size="sm" variant="outline" className="h-8 gap-1" onClick={() => setViewing(p)}><Printer className="size-3.5" /> Print</Button>
                          <Button size="sm" variant="ghost" className="h-8 size-8 p-0 text-destructive ml-1" aria-label={`Delete ${p.title}`} onClick={() => setDeleting(p)}><Trash2 className="size-4" /></Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : <LoadingBlock label="Loading plans…" />}
          </CardContent>
        </Card>
      </div>

      {/* printable sheet */}
      <Dialog open={!!viewing} onOpenChange={(o) => !o && setViewing(null)}>
        <DialogContent className="max-w-4xl max-h-[92vh] overflow-y-auto nice-scroll">
          <DialogHeader className="no-print">
            <DialogTitle className="text-base flex items-center gap-2"><Grid3x3 className="size-4" /> {viewing?.title}</DialogTitle>
            <DialogDescription>{viewing?.examName ?? "Seating plan"} · {viewing?.candidateCount} candidates</DialogDescription>
          </DialogHeader>
          {viewing && (
            <>
              <div className="print-area">
                <div className="print-sheet rounded-xl border bg-card p-8">
                  <div className="text-center border-b-2 border-foreground/80 pb-4">
                    <div className="font-bold text-lg uppercase tracking-tight">{school?.name ?? "School"}</div>
                    <div className="text-sm font-semibold mt-1">{viewing.examName ?? "Examination"}</div>
                    <div className="text-xs text-muted-foreground mt-1">Seating plan — {viewing.classroomNames}</div>
                    <div className="mt-2 inline-block border-2 border-foreground px-4 py-0.5 font-bold tracking-[0.15em] text-xs uppercase">{viewing.title}</div>
                    <div className="text-[11px] text-muted-foreground mt-1.5">{viewing.candidateCount} candidates · {viewing.rows} rows × {viewing.cols} seats{viewing.interleave ? " · interleaved (no two neighbours from the same class)" : ""}</div>
                  </div>
                  <div className="mt-5 overflow-x-auto">
                    <div className="grid gap-2 min-w-[600px]" style={{ gridTemplateColumns: `repeat(${viewing.cols}, minmax(0, 1fr))` }}>
                      {gridOf(viewing.rows, viewing.cols, viewing.entries).map((cell, i) => (
                        <div key={i} className={cn("rounded-md border px-2 py-2 min-h-16 text-center", cell ? "border-foreground/30" : "border-dashed border-foreground/20")}>
                          {cell ? (
                            <>
                              <div className="text-[10px] text-muted-foreground">SEAT {i + 1}</div>
                              <div className="text-xs font-bold leading-tight">{cell.name}</div>
                              <div className="text-[10px] text-muted-foreground">{cell.className}</div>
                              <div className="text-[10px] font-mono">{cell.indexNo}</div>
                            </>
                          ) : (
                            <div className="text-[10px] text-muted-foreground/60 h-full flex items-center justify-center">SPARE</div>
                          )}
                        </div>
                      ))}
                    </div>
                  </div>
                  <p className="text-[11px] text-muted-foreground mt-6 text-center">Candidates must remain in the seat printed beside their name. Any change must be approved by the invigilator.</p>
                </div>
              </div>
              <div className="flex justify-end gap-2 mt-3 no-print">
                <Button size="sm" className="gap-1.5" onClick={() => window.print()}><Printer className="size-4" /> Print seating chart</Button>
              </div>
            </>
          )}
        </DialogContent>
      </Dialog>

      {/* delete confirm */}
      <Dialog open={!!deleting} onOpenChange={(o) => !o && setDeleting(null)}>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle className="text-base">Delete “{deleting?.title}”?</DialogTitle>
            <DialogDescription>The seating chart for {deleting?.candidateCount} candidates will be removed. Print it again after regenerating.</DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" size="sm" onClick={() => setDeleting(null)}>Cancel</Button>
            <Button variant="destructive" size="sm" disabled={busy} onClick={() => deleting && removePlan(deleting)}>Delete plan</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

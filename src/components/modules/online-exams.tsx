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
import { Laptop, Plus, Send, Lock, Trash2, PencilLine, Users, Trophy, Clock, CheckCircle2, Printer, Radio, Eye, EyeOff } from "lucide-react";

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
  const { currentSchoolId, session } = useStore();
  const [exams, setExams] = useState<Exam[] | null>(null);
  const [edit, setEdit] = useState<Exam | "new" | null>(null);
  const [resultsFor, setResultsFor] = useState<Exam | null>(null);
  const [printFor, setPrintFor] = useState<Exam | null>(null);
  const [monitorFor, setMonitorFor] = useState<Exam | null>(null);

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

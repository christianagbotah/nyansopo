"use client";
import { useCallback, useEffect, useMemo, useState } from "react";
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
import { Laptop, Plus, Send, Lock, Trash2, PencilLine, Users, Trophy, Clock, ChevronLeft, ChevronRight, CheckCircle2 } from "lucide-react";

type Question = { id?: string; prompt: string; optionA: string; optionB: string; optionC: string; optionD: string; correctOption: string; marks: number };
type Attempt = { id: string; studentId: string; student: string; score: number | null; max: number; submittedAt: string };
type Exam = {
  id: string; title: string; subjectName: string; description: string | null;
  classroom: string | null; classroomId: string | null; durationMins: number; scheduledAt: string | null;
  status: string; createdByName: string | null; createdAt: string;
  questionCount: number; totalMarks: number; avgScore: number | null;
  questions: { id: string; prompt: string; optionA: string; optionB: string; optionC: string; optionD: string; correctOption: string; marks: number }[];
  attempts: Attempt[];
};

const STATUS_STYLE: Record<string, string> = {
  DRAFT: "bg-zinc-500",
  PUBLISHED: "bg-emerald-600",
  CLOSED: "bg-slate-600",
};

export function OnlineExams() {
  const { currentSchoolId, session } = useStore();
  const [exams, setExams] = useState<Exam[] | null>(null);
  const [edit, setEdit] = useState<Exam | "new" | null>(null);
  const [resultsFor, setResultsFor] = useState<Exam | null>(null);

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
        description="Publish MCQ papers to a class, let learners sit them from the student portal, and auto-mark against the answer key — iEduPro online_exam parity."
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
                        {e.status === "DRAFT" && (
                          <>
                            <Button size="sm" variant="ghost" className="h-7 gap-1" onClick={() => setEdit(e)}><PencilLine className="size-3.5" /> Edit</Button>
                            <Button size="sm" variant="ghost" className="h-7 gap-1 text-emerald-600" onClick={() => act({ action: "publish", id: e.id }, "Exam published")}><Send className="size-3.5" /> Publish</Button>
                            <Button size="sm" variant="ghost" className="h-7 gap-1 text-destructive" onClick={() => act({ action: "delete", id: e.id }, "Draft deleted")}><Trash2 className="size-3.5" /></Button>
                          </>
                        )}
                        {e.status === "PUBLISHED" && (
                          <>
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
        <EmptyState icon={<Laptop className="size-8" />} title="No online exams yet" hint="Create one — add MCQ questions, publish it to a class, and learners sit it from the student portal." />
      )}

      <ExamEditor
        exam={edit}
        onOpenChange={(o) => !o && setEdit(null)}
        onDone={() => { setEdit(null); load(); }}
      />
      <ResultsDialog exam={resultsFor} onOpenChange={(o) => !o && setResultsFor(null)} />
    </div>
  );
}

const blankQuestion = (): Question => ({ prompt: "", optionA: "", optionB: "", optionC: "", optionD: "", correctOption: "A", marks: 1 });

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
      setQuestions(exam.questions.map((q) => ({ ...q })));
    } else if (exam === "new") {
      setTitle(""); setSubject(""); setDescription(""); setClassroomId("all"); setDuration("30"); setQuestions([blankQuestion()]);
    }
  }, [exam]);

  function setQ(i: number, patch: Partial<Question>) {
    setQuestions((qs) => qs.map((q, j) => (j === i ? { ...q, ...patch } : q)));
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
          <DialogDescription>MCQ paper — learners pick one option per question; marking is automatic.</DialogDescription>
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
          {questions.map((q, i) => (
            <div key={i} className="rounded-xl border p-3 space-y-2">
              <div className="flex items-start gap-2">
                <span className="mt-2 text-xs font-bold text-muted-foreground">Q{i + 1}</span>
                <div className="flex-1 space-y-2">
                  <Input value={q.prompt} onChange={(e) => setQ(i, { prompt: e.target.value })} placeholder="Question prompt…" />
                  <div className="grid grid-cols-2 gap-2">
                    {(["A", "B", "C", "D"] as const).map((L) => (
                      <Input key={L} value={(q as any)[`option${L}`]} onChange={(e) => setQ(i, { [`option${L}`]: e.target.value } as any)} placeholder={`Option ${L}`} className="h-8" />
                    ))}
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="text-xs text-muted-foreground">Correct</span>
                    <Select value={q.correctOption} onValueChange={(v) => setQ(i, { correctOption: v })}>
                      <SelectTrigger className="h-8 w-20"><SelectValue /></SelectTrigger>
                      <SelectContent>{["A", "B", "C", "D"].map((o) => <SelectItem key={o} value={o}>{o}</SelectItem>)}</SelectContent>
                    </Select>
                    <span className="text-xs text-muted-foreground">Marks</span>
                    <Input type="number" min={1} value={q.marks} onChange={(e) => setQ(i, { marks: Number(e.target.value) || 1 })} className="h-8 w-16" />
                    {questions.length > 1 && (
                      <Button size="sm" variant="ghost" className="h-8 ml-auto text-destructive" onClick={() => setQuestions((qs) => qs.filter((_, j) => j !== i))}><Trash2 className="size-3.5" /></Button>
                    )}
                  </div>
                </div>
              </div>
            </div>
          ))}
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

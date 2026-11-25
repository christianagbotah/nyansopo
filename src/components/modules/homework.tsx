"use client";
// ── wave 65 — Homework & Assignments: the daily teacher workflow ──
// Post homework to a class (AI drafts it from a topic), every parent is SMS'd
// the moment it lands, learners submit from their portals, the teacher ticks
// handed-in / scores / feedback on a roll-ordered grid, pushes the scores into
// the Gradebook with one tap, and can remind parents of outstanding work.
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard } from "@/components/ui-helpers";
import { fmtDate } from "@/lib/format";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Skeleton } from "@/components/ui/skeleton";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { DateField } from "@/components/date-field";
import { PageTransition, CountUp } from "@/components/motion";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  BookPlus, BookOpen, CalendarClock, AlarmClock, Sparkles, Send, GraduationCap,
  CheckCircle2, Clock, XCircle, PenLine, RefreshCcw, Inbox, Loader2, Users, Search,
} from "lucide-react";

type HwItem = {
  id: string; title: string; subject: string; classroom: string; classroomId: string;
  postedBy: string; mine: boolean; dueDate: string; overdue: boolean; status: string;
  maxScore: number; notifyParents: boolean; createdAt: string;
  learners: number; pending: number; submitted: number; graded: number; late: number;
};
type ListData = {
  items: HwItem[];
  classrooms: { id: string; name: string }[];
  subjects: { id: string; name: string }[];
  mine: number;
  stats: { open: number; dueThisWeek: number; overdue: number };
};
type SubRow = {
  id: string; studentId: string; student: string; index: string; status: string;
  note: string | null; submittedAt: string | null; score: number | null;
  feedback: string | null; gradedAt: string | null; gradedByName: string | null;
};
type OneData = {
  homework: {
    id: string; title: string; instructions: string; subject: string; classroom: string;
    classroomId: string; postedBy: string; dueDate: string; maxScore: number; status: string;
    createdAt: string; gradebookAssessmentId: string | null;
  };
  submissions: SubRow[];
};

const STATUS_CHIP: Record<string, string> = {
  PENDING: "bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30",
  SUBMITTED: "bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30",
  LATE: "bg-orange-500/15 text-orange-700 dark:text-orange-300 border-orange-500/30",
  GRADED: "bg-primary/15 text-primary border-primary/30",
  EXCUSED: "bg-slate-500/15 text-slate-600 dark:text-slate-300 border-slate-500/30",
};
const STATUS_ICON: Record<string, React.ReactNode> = {
  PENDING: <Clock className="size-3" />,
  SUBMITTED: <CheckCircle2 className="size-3" />,
  LATE: <AlarmClock className="size-3" />,
  GRADED: <GraduationCap className="size-3" />,
  EXCUSED: <XCircle className="size-3" />,
};
const daysUntil = (iso: string) => Math.ceil((new Date(iso).getTime() - Date.now()) / 86400000);

export function HomeworkModule() {
  const { currentSchoolId, navTarget } = useStore();
  const [list, setList] = useState<ListData | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [classFilter, setClassFilter] = useState(navTarget?.classroomId ?? "all");
  const [scope, setScope] = useState<"all" | "mine">("all");
  const [search, setSearch] = useState("");
  const [tab, setTab] = useState<string>("board");
  const [openId, setOpenId] = useState<string | null>(null);

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<ListData>(`/api/school/${currentSchoolId}/ops/homework?view=list`)
      .then(setList)
      .catch((e) => setErr(e.message));
  }, [currentSchoolId]);

  useEffect(load, [load]);
  // deep-links: homework module target { classroomId } filters the board —
  // adjusted during render (React's derived-state pattern, no effect needed)
  const [lastTarget, setLastTarget] = useState<string | null>(navTarget?.classroomId ?? null);
  if ((navTarget?.classroomId ?? null) !== lastTarget) {
    setLastTarget(navTarget?.classroomId ?? null);
    if (navTarget?.classroomId) setClassFilter(navTarget.classroomId);
  }

  const filtered = useMemo(() => {
    if (!list) return [];
    let items = list.items;
    if (classFilter !== "all") items = items.filter((i) => i.classroomId === classFilter);
    if (scope === "mine") items = items.filter((i) => i.mine);
    const q = search.trim().toLowerCase();
    if (q) items = items.filter((i) => `${i.title} ${i.subject} ${i.classroom} ${i.postedBy}`.toLowerCase().includes(q));
    return items;
  }, [list, classFilter, scope, search]);

  if (err) return <div className="text-destructive text-sm">{err}</div>;
  if (!list) return <LoadingBlock label="Loading the homework board…" />;

  return (
    <PageTransition moduleKey="homework">
      <ModuleHeader
        title="Homework & Assignments"
        description="Post work, alert parents instantly, collect submissions, mark and push scores into the gradebook."
        actions={
          <Button onClick={() => setTab("new")} className="gap-1.5">
            <BookPlus className="size-4" /> New assignment
          </Button>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <StatCard icon={<BookOpen className="size-4" />} label="Open assignments" value={<CountUp value={list.stats.open} />} sub="on the board now" tone="primary" />
        <StatCard icon={<CalendarClock className="size-4" />} label="Due this week" value={<CountUp value={list.stats.dueThisWeek} />} sub="next 7 days" />
        <StatCard icon={<AlarmClock className="size-4" />} label="Overdue & open" value={<CountUp value={list.stats.overdue} />} sub="past due, not yet closed" tone={list.stats.overdue ? "danger" : "default"} />
        <StatCard icon={<PenLine className="size-4" />} label="Posted by me" value={<CountUp value={list.mine} />} sub="your assignments" />
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList>
          <TabsTrigger value="board">Board ({filtered.length})</TabsTrigger>
          <TabsTrigger value="new" className="gap-1.5"><Sparkles className="size-4" /> New assignment</TabsTrigger>
        </TabsList>

        <TabsContent value="board" className="mt-3 space-y-3">
          <div className="flex flex-wrap items-center gap-2">
            <Select value={classFilter} onValueChange={setClassFilter}>
              <SelectTrigger className="w-[180px]"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All classes</SelectItem>
                {list.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
              </SelectContent>
            </Select>
            <Select value={scope} onValueChange={(v) => setScope(v as "all" | "mine")}>
              <SelectTrigger className="w-[150px]"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Everyone&rsquo;s posts</SelectItem>
                <SelectItem value="mine">Posted by me</SelectItem>
              </SelectContent>
            </Select>
            <div className="relative flex-1 min-w-56">
              <Search className="size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
              <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search title, subject, teacher…" className="pl-8" />
            </div>
          </div>

          {filtered.length === 0 ? (
            <EmptyState
              icon={<BookPlus className="size-8" />}
              title="No homework on the board yet"
              hint="Post the first assignment — parents are told the moment it lands."
            />
          ) : (
            <div className="grid md:grid-cols-2 xl:grid-cols-3 gap-3">
              {filtered.map((h) => <HwCard key={h.id} h={h} onOpen={() => setOpenId(h.id)} />)}
            </div>
          )}
        </TabsContent>

        <TabsContent value="new" className="mt-3">
          <NewAssignmentForm
            classrooms={list.classrooms}
            subjects={list.subjects}
            onPosted={() => { setTab("board"); load(); }}
          />
        </TabsContent>
      </Tabs>

      {openId && (
        <MarkingDialog
          schoolId={currentSchoolId!}
          homeworkId={openId}
          onClose={() => setOpenId(null)}
          onChanged={load}
        />
      )}
    </PageTransition>
  );
}

/* ── board card ── */
function HwCard({ h, onOpen }: { h: HwItem; onOpen: () => void }) {
  const due = daysUntil(h.dueDate);
  const done = h.graded + h.submitted;
  const rate = h.learners ? Math.round((done / h.learners) * 100) : 0;
  return (
    <button onClick={onOpen} className="text-left rounded-xl border bg-card p-4 hover:shadow-md hover:border-primary/40 transition-all focus-visible:ring-2 focus-visible:ring-ring outline-none">
      <div className="flex items-start justify-between gap-2">
        <div className="min-w-0">
          <div className="flex items-center gap-1.5 flex-wrap">
            <Badge variant="secondary" className="text-[10px]">{h.classroom}</Badge>
            <Badge variant="outline" className="text-[10px]">{h.subject}</Badge>
            {h.mine && <Badge className="text-[10px] bg-primary">mine</Badge>}
            {h.status === "CLOSED" && <Badge variant="outline" className="text-[10px]">closed</Badge>}
          </div>
          <div className="font-semibold leading-snug mt-1.5 line-clamp-2">{h.title}</div>
          <div className="text-[11px] text-muted-foreground mt-0.5">by {h.postedBy} · posted {fmtDate(h.createdAt)}</div>
        </div>
        <div className="text-right shrink-0">
          <div className={cn("text-[11px] font-semibold", h.overdue && h.status === "OPEN" ? "text-destructive" : due <= 1 && h.status === "OPEN" ? "text-amber-600 dark:text-amber-400" : "text-muted-foreground")}>
            {h.overdue && h.status === "OPEN" ? "overdue" : due <= 0 ? "due today" : due === 1 ? "due tomorrow" : `due in ${due}d`}
          </div>
          <div className="text-[10px] text-muted-foreground">{fmtDate(h.dueDate)}</div>
        </div>
      </div>
      <div className="mt-3 flex items-center gap-1.5 flex-wrap">
        <Chip s="PENDING" n={h.pending} />
        <Chip s={h.late ? "LATE" : "SUBMITTED"} n={h.late || h.submitted} />
        <Chip s="GRADED" n={h.graded} />
        <span className="text-[10px] text-muted-foreground ml-auto tabular-nums">{h.learners} learners · {rate}% in</span>
      </div>
      <div className="mt-2 h-1.5 rounded-full bg-secondary overflow-hidden">
        <div className="h-full rounded-full bg-primary transition-all" style={{ width: `${rate}%` }} />
      </div>
    </button>
  );
}

function Chip({ s, n }: { s: string; n: number }) {
  return (
    <span className={cn("inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-medium", STATUS_CHIP[s] ?? "")}>
      {STATUS_ICON[s]} {n} {s.toLowerCase()}
    </span>
  );
}

/* ── new assignment (AI draft + instant parent SMS) ── */
function NewAssignmentForm({ classrooms, subjects, onPosted }: {
  classrooms: { id: string; name: string }[];
  subjects: { id: string; name: string }[];
  onPosted: () => void;
}) {
  const { currentSchoolId } = useStore();
  const [classroomId, setClassroomId] = useState(classrooms[0]?.id ?? "");
  const [subjectId, setSubjectId] = useState("none");
  const [title, setTitle] = useState("");
  const [instructions, setInstructions] = useState("");
  const [due, setDue] = useState("");
  const [maxScore, setMaxScore] = useState("10");
  const [notifyParents, setNotifyParents] = useState(true);
  const [topic, setTopic] = useState("");
  const [aiBusy, setAiBusy] = useState(false);
  const [posting, setPosting] = useState(false);

  const klass = classrooms.find((c) => c.id === classroomId)?.name ?? "";
  const subjectName = subjectId === "none" ? "" : subjects.find((s) => s.id === subjectId)?.name ?? "";

  async function aiDraft() {
    if (!topic.trim()) { toast({ title: "Give the AI a topic first", description: "e.g. \u201cAddition of common fractions\u201d or \u201cThe water cycle\u201d.", variant: "destructive" }); return; }
    setAiBusy(true);
    try {
      const res = await apiPost<{ draft: { title: string; instructions: string; maxScore: number } }>(
        `/api/school/${currentSchoolId}/ops/homework`,
        { action: "homework.ai-draft", topic, subject: subjectName || "the subject", classroom: klass, dueDays: Math.max(1, daysUntil(`${new Date().toISOString().slice(0, 10)}T00:00:00Z`) || 3) },
      );
      setTitle(res.draft.title);
      setInstructions(res.draft.instructions);
      setMaxScore(String(res.draft.maxScore));
      toast({ title: "AI draft ready", description: "Review, tweak and post — you stay the teacher." });
    } catch (e: any) {
      toast({ title: "AI draft failed", description: e.message, variant: "destructive" });
    } finally { setAiBusy(false); }
  }

  async function post() {
    if (!classroomId || !title.trim() || !instructions.trim() || !due) {
      toast({ title: "Missing details", description: "Class, title, instructions and a due date are required.", variant: "destructive" });
      return;
    }
    setPosting(true);
    try {
      const res = await apiPost<{ learners: number; sms: { sent: number; failed: number; skipped: number } }>(
        `/api/school/${currentSchoolId}/ops/homework`,
        { action: "homework.create", classroomId, subjectId: subjectId === "none" ? null : subjectId, subjectName, title, instructions, dueDate: `${due}T23:59:59.000Z`, maxScore: Number(maxScore) || 10, notifyParents },
      );
      toast({
        title: "Homework posted",
        description: notifyParents
          ? `${res.learners} learners seeded · parents SMS'd (${res.sms.sent} sent${res.sms.failed ? `, ${res.sms.failed} failed` : ""})`
          : `${res.learners} learners seeded · parent SMS skipped`,
      });
      setTitle(""); setInstructions(""); setTopic(""); setDue(""); setMaxScore("10");
      onPosted();
    } catch (e: any) {
      toast({ title: "Could not post", description: e.message, variant: "destructive" });
    } finally { setPosting(false); }
  }

  return (
    <div className="grid lg:grid-cols-3 gap-4">
      <Card className="lg:col-span-2">
        <CardHeader className="pb-2">
          <CardTitle className="text-base flex items-center gap-2"><BookPlus className="size-4" /> Post an assignment</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid sm:grid-cols-3 gap-3">
            <div className="space-y-1.5">
              <Label>Class</Label>
              <Select value={classroomId} onValueChange={setClassroomId}>
                <SelectTrigger><SelectValue placeholder="Choose class" /></SelectTrigger>
                <SelectContent>
                  {classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Subject</Label>
              <Select value={subjectId} onValueChange={setSubjectId}>
                <SelectTrigger><SelectValue placeholder="Choose subject" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">General (no catalog subject)</SelectItem>
                  {subjects.map((s) => <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Due date</Label>
              <DateField value={due} onChange={(v) => setDue(v ?? "")} placeholder="Pick a day" />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Title</Label>
            <Input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="e.g. Fractions worksheet 3 — addition with different denominators" />
          </div>
          <div className="space-y-1.5">
            <Label>Instructions for pupils & parents</Label>
            <Textarea value={instructions} onChange={(e) => setInstructions(e.target.value)} rows={7} placeholder="What exactly should the pupils do? Number the tasks. Mention the exercise book, showing working, and how to submit." />
          </div>
          <div className="grid sm:grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>Max score</Label>
              <Input type="number" min={1} max={100} value={maxScore} onChange={(e) => setMaxScore(e.target.value)} />
            </div>
            <div className="rounded-lg border p-3 flex items-center justify-between gap-3">
              <div className="text-sm">
                <div className="font-medium flex items-center gap-1.5"><Send className="size-3.5" /> SMS parents now</div>
                <div className="text-xs text-muted-foreground">Every guardian is texted the moment this lands.</div>
              </div>
              <Switch checked={notifyParents} onCheckedChange={setNotifyParents} />
            </div>
          </div>
          <div className="flex items-center justify-end gap-2 pt-1">
            <Button variant="outline" onClick={() => { setTitle(""); setInstructions(""); setTopic(""); }}>Clear</Button>
            <Button onClick={post} disabled={posting} className="gap-1.5 min-w-40">
              {posting ? <Loader2 className="size-4 animate-spin" /> : <BookPlus className="size-4" />}
              {posting ? "Posting…" : "Post to class"}
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card className="bg-secondary/30">
        <CardHeader className="pb-2">
          <CardTitle className="text-base flex items-center gap-2"><Sparkles className="size-4 text-primary" /> AI homework drafter</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <p className="text-xs text-muted-foreground leading-relaxed">
            Type a topic — the AI writes a title, step-by-step instructions pupils and parents can follow at home
            (NaCCA SBC style), and a fair max score. You review and edit before posting; nothing goes out without you.
          </p>
          <div className="space-y-1.5">
            <Label>Topic</Label>
            <Input value={topic} onChange={(e) => setTopic(e.target.value)} placeholder="e.g. Addition of common fractions" onKeyDown={(e) => e.key === "Enter" && aiDraft()} />
          </div>
          <Button onClick={aiDraft} disabled={aiBusy} className="w-full gap-1.5">
            {aiBusy ? <Loader2 className="size-4 animate-spin" /> : <Sparkles className="size-4" />}
            {aiBusy ? "Drafting…" : "Draft homework with AI"}
          </Button>
          <div className="rounded-lg border bg-background p-3 text-xs text-muted-foreground leading-relaxed">
            <b className="text-foreground">Why SMS?</b> In most Ghanaian homes the parent — not the learner — checks the phone.
            A homework text the day it is posted is the single biggest driver of completion rates.
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

/* ── marking dialog: roll-ordered grid, scores, feedback, gradebook push ── */
function MarkingDialog({ schoolId, homeworkId, onClose, onChanged }: {
  schoolId: string; homeworkId: string; onClose: () => void; onChanged: () => void;
}) {
  const [data, setData] = useState<OneData | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [edits, setEdits] = useState<Record<string, { status?: string; score?: string; feedback?: string }>>({});
  const [busy, setBusy] = useState(false);
  const [pushing, setPushing] = useState(false);
  const [reminding, setReminding] = useState(false);
  const [filter, setFilter] = useState<"all" | "pending" | "in" | "graded">("all");

  const load = useCallback(() => {
    apiGet<OneData>(`/api/school/${schoolId}/ops/homework?view=one&id=${homeworkId}`)
      .then((d) => { setData(d); setEdits({}); })
      .catch((e) => setErr(e.message));
  }, [schoolId, homeworkId]);
  useEffect(load, [load]);

  const rows = useMemo(() => {
    if (!data) return [];
    const f = filter;
    return data.submissions.filter((s) => {
      if (f === "all") return true;
      if (f === "pending") return s.status === "PENDING";
      if (f === "in") return s.status === "SUBMITTED" || s.status === "LATE";
      return s.status === "GRADED";
    });
  }, [data, filter]);

  const dirtyCount = Object.keys(edits).length;
  const gradedTotal = data ? data.submissions.filter((s) => s.score != null || edits[s.studentId]?.score != null && edits[s.studentId]?.score !== "").length : 0;

  function edit(studentId: string, patch: { status?: string; score?: string; feedback?: string }) {
    setEdits((prev) => ({ ...prev, [studentId]: { ...prev[studentId], ...patch } }));
  }

  async function saveMarks() {
    if (!data) return;
    const marks = Object.entries(edits).map(([studentId, e]) => ({
      studentId,
      ...(e.status ? { status: e.status } : {}),
      ...(e.score != null && e.score !== "" ? { score: Number(e.score) } : {}),
      ...(e.feedback != null ? { feedback: e.feedback } : {}),
    }));
    if (!marks.length) return;
    setBusy(true);
    try {
      const r = await apiPost<{ updated: number }>(`/api/school/${schoolId}/ops/homework`, { action: "homework.bulk-mark", homeworkId, marks });
      toast({ title: `Saved ${r.updated} submission${r.updated === 1 ? "" : "s"}` });
      load(); onChanged();
    } catch (e: any) {
      toast({ title: "Could not save", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  async function pushGradebook() {
    setPushing(true);
    try {
      const r = await apiPost<{ marks: number; assessmentId: string }>(`/api/school/${schoolId}/ops/homework`, { action: "homework.push-gradebook", homeworkId });
      toast({ title: "Scores in the gradebook", description: `${r.marks} mark${r.marks === 1 ? "" : "s"} filed as a HOMEWORK assessment.` });
      load(); onChanged();
    } catch (e: any) {
      toast({ title: "Could not push to gradebook", description: e.message, variant: "destructive" });
    } finally { setPushing(false); }
  }

  async function remind() {
    setReminding(true);
    try {
      const r = await apiPost<{ sent: number; failed: number; noPhone: number; pending: number }>(`/api/school/${schoolId}/ops/homework`, { action: "homework.remind", homeworkId });
      toast({ title: "Reminders on the way", description: `${r.sent} SMS sent${r.failed ? `, ${r.failed} failed` : ""}${r.noPhone ? `, ${r.noPhone} guardians have no phone` : ""}.` });
      onChanged();
    } catch (e: any) {
      toast({ title: "Could not send reminders", description: e.message, variant: "destructive" });
    } finally { setReminding(false); }
  }

  async function toggleStatus() {
    if (!data) return;
    const action = data.homework.status === "OPEN" ? "homework.close" : "homework.reopen";
    try {
      await apiPost(`/api/school/${schoolId}/ops/homework`, { action, homeworkId });
      load(); onChanged();
    } catch (e: any) {
      toast({ title: "Could not update", description: e.message, variant: "destructive" });
    }
  }

  const hw = data?.homework;
  return (
    <Dialog open onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-w-4xl max-h-[92vh] overflow-hidden flex flex-col">
        {!data && !err && (
          <div className="space-y-3 py-6">
            <Skeleton className="h-6 w-2/3" />
            <Skeleton className="h-4 w-1/2" />
            <Skeleton className="h-64 w-full" />
          </div>
        )}
        {err && <div className="text-destructive text-sm py-6">{err}</div>}
        {data && hw && (
          <>
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2 text-left"><PenLine className="size-4.5 shrink-0" /> {hw.title}</DialogTitle>
              <DialogDescription className="text-left">
                {hw.classroom} · {hw.subject} · due {fmtDate(hw.dueDate)} · max {hw.maxScore} · by {hw.postedBy}
                {hw.gradebookAssessmentId ? " · in gradebook" : ""}
              </DialogDescription>
            </DialogHeader>

            <div className="rounded-lg border bg-secondary/30 p-3 text-sm leading-relaxed max-h-28 overflow-y-auto whitespace-pre-wrap">{hw.instructions}</div>

            <div className="flex flex-wrap items-center gap-2">
              <Select value={filter} onValueChange={(v) => setFilter(v as typeof filter)}>
                <SelectTrigger className="w-[170px]"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All ({data.submissions.length})</SelectItem>
                  <SelectItem value="pending">Pending ({data.submissions.filter((s) => s.status === "PENDING").length})</SelectItem>
                  <SelectItem value="in">Handed in ({data.submissions.filter((s) => s.status === "SUBMITTED" || s.status === "LATE").length})</SelectItem>
                  <SelectItem value="graded">Graded ({data.submissions.filter((s) => s.status === "GRADED").length})</SelectItem>
                </SelectContent>
              </Select>
              <Button variant="outline" size="sm" onClick={remind} disabled={reminding} className="gap-1.5">
                {reminding ? <Loader2 className="size-3.5 animate-spin" /> : <Send className="size-3.5" />} Remind pending parents
              </Button>
              <Button variant="outline" size="sm" onClick={toggleStatus} className="gap-1.5">
                {hw.status === "OPEN" ? <><XCircle className="size-3.5" /> Close</> : <><RefreshCcw className="size-3.5" /> Reopen</>}
              </Button>
              <div className="ml-auto flex items-center gap-2">
                {dirtyCount > 0 && (
                  <Button size="sm" onClick={saveMarks} disabled={busy} className="gap-1.5">
                    {busy ? <Loader2 className="size-3.5 animate-spin" /> : <CheckCircle2 className="size-3.5" />} Save {dirtyCount}
                  </Button>
                )}
                <Button size="sm" variant="secondary" onClick={pushGradebook} disabled={pushing || gradedTotal === 0} className="gap-1.5">
                  {pushing ? <Loader2 className="size-3.5 animate-spin" /> : <GraduationCap className="size-3.5" />} Push to gradebook
                </Button>
              </div>
            </div>

            <div className="overflow-y-auto min-h-0 flex-1 -mx-1 px-1">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Learner</TableHead>
                    <TableHead className="w-36">Status</TableHead>
                    <TableHead className="w-20 text-right">Score</TableHead>
                    <TableHead>Feedback</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {rows.map((s) => {
                    const e = edits[s.studentId] ?? {};
                    const status = e.status ?? s.status;
                    const score = e.score ?? (s.score != null ? String(s.score) : "");
                    const note = s.note;
                    return (
                      <TableRow key={s.id} className={cn(note && "bg-amber-500/5")}>
                        <TableCell>
                          <div className="text-sm font-medium leading-tight">{s.student}</div>
                          <div className="text-[10px] text-muted-foreground font-mono">{s.index}</div>
                          {note && <div className="text-[11px] mt-1 rounded-md bg-amber-500/10 border border-amber-500/20 px-1.5 py-1 text-amber-800 dark:text-amber-300">&ldquo;{note}&rdquo;{s.submittedAt ? ` · ${fmtDate(s.submittedAt)}` : ""}</div>}
                          {s.feedback && !e.feedback && <div className="text-[11px] text-muted-foreground mt-0.5">feedback: {s.feedback}</div>}
                        </TableCell>
                        <TableCell>
                          <Select value={status} onValueChange={(v) => edit(s.studentId, { status: v })}>
                            <SelectTrigger className="h-8 text-xs"><span className={cn("inline-flex items-center gap-1", "")}>{STATUS_ICON[status]}<span className="capitalize">{status.toLowerCase()}</span></span></SelectTrigger>
                            <SelectContent>
                              {["PENDING", "SUBMITTED", "LATE", "GRADED", "EXCUSED"].map((st) => (
                                <SelectItem key={st} value={st}><span className="inline-flex items-center gap-1.5">{STATUS_ICON[st]} {st.charAt(0) + st.slice(1).toLowerCase()}</span></SelectItem>
                              ))}
                            </SelectContent>
                          </Select>
                        </TableCell>
                        <TableCell>
                          <Input type="number" min={0} max={hw.maxScore} value={score} onChange={(ev) => edit(s.studentId, { score: ev.target.value })} className="h-8 text-right tabular-nums" placeholder="—" />
                        </TableCell>
                        <TableCell>
                          <Input value={e.feedback ?? s.feedback ?? ""} onChange={(ev) => edit(s.studentId, { feedback: ev.target.value })} className="h-8 text-xs" placeholder="Optional comment…" />
                        </TableCell>
                      </TableRow>
                    );
                  })}
                  {rows.length === 0 && (
                    <TableRow><TableCell colSpan={4}><EmptyState title="Nothing here" hint="No submissions match this filter." /></TableCell></TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
            <DialogFooter className="text-xs text-muted-foreground items-center">
              <span className="inline-flex items-center gap-1"><Users className="size-3" /> {data.submissions.length} learners · {gradedTotal} scored · handing in after the due date auto-flags late</span>
            </DialogFooter>
          </>
        )}
      </DialogContent>
    </Dialog>
  );
}

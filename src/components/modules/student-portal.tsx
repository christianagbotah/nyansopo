"use client";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate } from "@/lib/format";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { DateField } from "@/components/date-field";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "@/hooks/use-toast";
import { avatarColor } from "@/lib/format";
import { cn } from "@/lib/utils";
import { Backpack, CalendarDays, BookOpen, GraduationCap, Wallet, Megaphone, Clock, AlertTriangle, CheckCircle2, UserRound, Laptop, Send, BookPlus, Hourglass, XCircle, MessageSquare, PenLine, Loader2, HandHeart, Lock } from "lucide-react";
import { PortalMessenger } from "@/components/portal-messenger";

type Slot = { day: number; start: number; end: number; subject: string; teacher: string | null };
type Mark = { subject: string; assessment: string; type: string; score: number; max: number };
type ReportCard = {
  id: string; term: string; classroom: string; average: number | null; position: number | null; classSize: number | null;
  conduct: string | null; interest: string | null; attendance: string | null; teacherRemark: string | null; headRemark: string | null;
  promotion: string | null; published: boolean;
  results: { subject: string; isCore: boolean; total: number | null; grade: string | null; remark: string | null }[];
};
// iEduPro parity: learners request books themselves and track the librarian's decision
type BookRequestLite = {
  id: string; book: string; startAt: string; endAt: string; status: string;
  handledAt: string | null; handledByName: string | null; createdAt: string; overdue: boolean;
};
type CatalogueBook = { id: string; title: string; author: string | null; category: string; available: number };
// wave 65 — homework from the teachers' board (portal view)
type HwLite = {
  id: string; title: string; subject: string; classroom: string; instructions: string;
  dueDate: string; overdue: boolean; status: string; maxScore: number; postedBy: string;
  myStatus: string; myNote: string | null; myScore: number | null; myFeedback: string | null;
};
const REQ_STATUS: Record<string, { label: string; className: string; icon: React.ReactNode }> = {
  PENDING: { label: "Waiting for librarian", className: "bg-amber-600", icon: <Hourglass className="size-3" /> },
  ISSUED: { label: "Issued", className: "bg-emerald-600", icon: <CheckCircle2 className="size-3" /> },
  REJECTED: { label: "Declined", className: "bg-destructive", icon: <XCircle className="size-3" /> },
};

type StudentData = {
  role: string; school: { name: string; motto: string | null; town: string; region: string } | null;
  year: string | null; term: string | null;
  me: {
    id: string; name: string; nyansapoIndex: string; gender: string;
    classroom: string | null; classroomId: string | null; classTeacher: string | null;
    attendance: { total: number; present: number; late: number; absent: number; rate: number | null; recent: { date: string; status: string }[] };
    timetable: Slot[];
    marks: Mark[];
    reportCards: ReportCard[];
    library: { book: string; dueAt: string; overdue: boolean }[];
    bookRequests: BookRequestLite[];
    wallet: { prepaid: number; transport: number } | null;
    feesBalance: number;
    homework: HwLite[];
  };
  announcements: { id: string; title: string; body: string; postedByName: string; postedAt: string; priority: string }[];
  libraryCatalogue: CatalogueBook[];
};

const DAYS = ["", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
const ATT_COLOR: Record<string, string> = { PRESENT: "bg-emerald-600", LATE: "bg-amber-500", ABSENT: "bg-destructive", SICK: "bg-sky-500", EXCUSED: "bg-zinc-400" };
const fmtTime = (m: number) => `${String(Math.floor(m / 60)).padStart(2, "0")}:${String(m % 60).padStart(2, "0")}`;

export function StudentPortal() {
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<StudentData | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [requestOpen, setRequestOpen] = useState(false);
  const [counselOpen, setCounselOpen] = useState(false);

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<StudentData>(`/api/school/${currentSchoolId}/ops/portal?view=student`)
      .then(setData)
      .catch((e) => setErr(e.message));
  }, [currentSchoolId]);
  useEffect(() => { load(); }, [load]);

  const byDay = useMemo(() => {
    const g: Record<number, Slot[]> = {};
    (data?.me.timetable ?? []).forEach((s) => { (g[s.day] ??= []).push(s); });
    Object.values(g).forEach((list) => list.sort((a, b) => a.start - b.start));
    return g;
  }, [data]);

  if (err) return <div className="text-destructive text-sm">{err}</div>;
  if (!data) return <LoadingBlock label="Loading your portal…" />;
  const me = data.me;
  const overdueBooks = me.library.filter((l) => l.overdue);
  const pendingRequests = me.bookRequests.filter((r) => r.status === "PENDING").length;
  /* wave 21 — only published terminal reports reach the learner */
  const pubCards = me.reportCards.filter((rc) => rc.published);
  /* wave 65 — outstanding homework count for the tab badge */
  const hwPending = me.homework.filter((h) => h.status === "OPEN" && (h.myStatus === "PENDING")).length;

  return (
    <div>
      <ModuleHeader
        title={`My Learning — ${me.name}`}
        description={`${me.classroom ?? "—"} · ${data.term ?? "Term"} ${data.year ? `· ${data.year}` : ""} · Class teacher: ${me.classTeacher ?? "—"}`}
        actions={<Badge variant="outline" className="font-mono">{me.nyansapoIndex}</Badge>}
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <Tile icon={<CalendarDays className="size-4" />} label="My attendance" value={me.attendance.rate !== null ? `${me.attendance.rate}%` : "—"} sub={`${me.attendance.present} present of ${me.attendance.total}`} tone={me.attendance.rate !== null && me.attendance.rate >= 90 ? "good" : undefined} />
        <Tile icon={<GraduationCap className="size-4" />} label="Latest average" value={pubCards[0]?.average != null ? `${pubCards[0].average}%` : "—"} sub={pubCards[0]?.term ?? "awaiting published reports"} />
        <Tile icon={<BookOpen className="size-4" />} label="Library books" value={me.library.length} sub={overdueBooks.length ? `${overdueBooks.length} overdue — return soon!` : "none overdue"} tone={overdueBooks.length ? "danger" : undefined} />
        <Tile icon={<Wallet className="size-4" />} label="Fees" value={me.feesBalance > 0 ? fmtCedi(me.feesBalance, { compact: true }) : "Settled"} sub={me.feesBalance > 0 ? "outstanding balance" : "nothing owing — keep it up!"} tone={me.feesBalance > 0 ? "danger" : "good"} />
      </div>

      <Tabs defaultValue="overview">
        <TabsList className="flex-wrap h-auto">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="homework" className="gap-1.5"><BookPlus className="size-4" /> Homework{hwPending ? ` (${hwPending})` : ""}</TabsTrigger>
          <TabsTrigger value="timetable">Timetable</TabsTrigger>
          <TabsTrigger value="results">My results</TabsTrigger>
          <TabsTrigger value="library">Library</TabsTrigger>
          <TabsTrigger value="attendance">Attendance</TabsTrigger>
          <TabsTrigger value="exams">Online exams</TabsTrigger>
          <TabsTrigger value="messages" className="gap-1.5"><MessageSquare className="size-4" /> Messages</TabsTrigger>
        </TabsList>

        <TabsContent value="overview" className="mt-3 grid lg:grid-cols-3 gap-4">
          <Card className="lg:col-span-2">
            <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><CalendarDays className="size-4" /> Today & this week</CardTitle></CardHeader>
            <CardContent>
              {me.timetable.length ? (
                <div className="grid sm:grid-cols-2 gap-2">
                  {[1, 2, 3, 4, 5].map((d) => (
                    <div key={d} className={`rounded-lg border p-3 ${d === (new Date().getDay() || 5) ? "border-primary/50 bg-primary/5" : ""}`}>
                      <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1.5">{DAYS[d]}{d === (new Date().getDay() || 5) ? " · today" : ""}</div>
                      {(byDay[d] ?? []).length ? (byDay[d] ?? []).map((s, i) => (
                        <div key={i} className="flex items-center justify-between text-sm py-0.5">
                          <span className="truncate">{s.subject}</span>
                          <span className="text-[10px] text-muted-foreground tabular-nums shrink-0 ml-2">{fmtTime(s.start)}–{fmtTime(s.end)}</span>
                        </div>
                      )) : <div className="text-xs text-muted-foreground">free day</div>}
                    </div>
                  ))}
                </div>
              ) : <EmptyState title="No timetable yet" hint="Your class routine will appear here." />}
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Megaphone className="size-4" /> Notices</CardTitle></CardHeader>
            <CardContent className="space-y-2.5 max-h-80 overflow-y-auto nice-scroll">
              {data.announcements.length ? data.announcements.map((a) => (
                <div key={a.id} className="rounded-lg border p-2.5">
                  <div className="flex items-center gap-1.5">
                    {a.priority === "HIGH" && <Badge className="bg-destructive text-[9px]">urgent</Badge>}
                    <span className="text-sm font-medium">{a.title}</span>
                  </div>
                  <p className="text-xs text-muted-foreground mt-1 line-clamp-3">{a.body}</p>
                  <div className="text-[10px] text-muted-foreground mt-1">{fmtDate(a.postedAt)}</div>
                </div>
              )) : <EmptyState title="No notices" />}
            </CardContent>
          </Card>

          {/* wave 71 — the G&C door: a private way to ask for a chat */}
          <Card className="lg:col-span-3 border-primary/30 bg-primary/5">
            <CardContent className="p-4 flex flex-col sm:flex-row sm:items-center gap-3">
              <span className="flex size-10 items-center justify-center rounded-full bg-primary/10 shrink-0"><HandHeart className="size-5 text-primary" /></span>
              <div className="min-w-0 flex-1">
                <div className="text-sm font-semibold">Need someone to talk to?</div>
                <div className="text-xs text-muted-foreground flex items-center gap-1"><Lock className="size-3" /> Send a private note to the school counsellor — only they and the head can read it. They will arrange a quiet time to chat.</div>
              </div>
              <Button size="sm" className="gap-1.5 shrink-0" onClick={() => setCounselOpen(true)}>
                <HandHeart className="size-4" /> Talk to the counsellor
              </Button>
            </CardContent>
          </Card>

          {me.marks.length > 0 && (
            <Card className="lg:col-span-3">
              <CardHeader className="pb-2"><CardTitle className="text-base">Latest exercises this term</CardTitle></CardHeader>
              <CardContent>
                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-2">
                  {me.marks.slice(0, 6).map((m, i) => {
                    const pct = Math.round((m.score / Math.max(1, m.max)) * 100);
                    return (
                      <div key={i} className="rounded-lg border p-3">
                        <div className="flex items-center justify-between">
                          <span className="text-sm font-medium truncate">{m.subject}</span>
                          <span className="text-sm font-bold tabular-nums">{m.score}/{m.max}</span>
                        </div>
                        <div className="text-[10px] text-muted-foreground mb-1.5 truncate">{m.assessment}</div>
                        <Progress value={pct} className="h-1.5" />
                      </div>
                    );
                  })}
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        <TabsContent value="homework" className="mt-3">
          <StudentHomework homework={me.homework} studentId={me.id} onChanged={load} />
        </TabsContent>

        <TabsContent value="timetable" className="mt-3">
          <Card>
            <CardHeader className="pb-2"><CardTitle className="text-base">Weekly routine — {me.classroom}</CardTitle></CardHeader>
            <CardContent>
              {me.timetable.length ? (
                <div className="grid grid-cols-1 md:grid-cols-5 gap-2">
                  {[1, 2, 3, 4, 5].map((d) => (
                    <div key={d} className="rounded-lg border p-2.5 min-h-32">
                      <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1.5">{DAYS[d]}</div>
                      <div className="space-y-1">
                        {(byDay[d] ?? []).map((s, i) => (
                          <div key={i} className="rounded-md bg-secondary/60 px-2 py-1.5">
                            <div className="text-xs font-medium truncate">{s.subject}</div>
                            <div className="text-[10px] text-muted-foreground flex items-center gap-1"><Clock className="size-2.5" /> {fmtTime(s.start)}–{fmtTime(s.end)}</div>
                            {s.teacher && <div className="text-[10px] text-muted-foreground truncate flex items-center gap-1"><UserRound className="size-2.5" /> {s.teacher}</div>}
                          </div>
                        ))}
                        {!(byDay[d] ?? []).length && <div className="text-xs text-muted-foreground">—</div>}
                      </div>
                    </div>
                  ))}
                </div>
              ) : <EmptyState title="No timetable published yet" />}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="results" className="mt-3 space-y-4">
          {/* wave 21 — the school publishes results from Results & Report Cards */}
          {pubCards.length ? pubCards.map((rc) => (
            <Card key={rc.id}>
              <CardHeader className="pb-2">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <CardTitle className="text-base">{rc.term} — {rc.classroom}</CardTitle>
                  <div className="flex items-center gap-2">
                    {rc.position != null && <Badge variant="secondary">{rc.position} of {rc.classSize}</Badge>}
                    {rc.promotion && <Badge className={rc.promotion === "PROMOTED" ? "bg-emerald-600" : "bg-amber-600"}>{rc.promotion}</Badge>}
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-3 gap-3 mb-3">
                  <Mini label="Average" value={rc.average != null ? `${rc.average}%` : "—"} tone="good" />
                  <Mini label="Conduct" value={rc.conduct ?? "—"} />
                  <Mini label="Interest" value={rc.interest ?? "—"} />
                </div>
                <Table>
                  <TableHeader>
                    <TableRow><TableHead>Subject</TableHead><TableHead>Score</TableHead><TableHead>Grade</TableHead><TableHead className="hidden sm:table-cell">Remark</TableHead></TableRow>
                  </TableHeader>
                  <TableBody>
                    {rc.results.map((r, i) => (
                      <TableRow key={i}>
                        <TableCell className="font-medium">{r.subject}{r.isCore && <Badge variant="outline" className="ml-1.5 text-[9px]">core</Badge>}</TableCell>
                        <TableCell className="tabular-nums">{r.total != null ? `${r.total}%` : "—"}</TableCell>
                        <TableCell>{r.grade ? <Badge variant="secondary">{r.grade}</Badge> : "—"}</TableCell>
                        <TableCell className="hidden sm:table-cell text-xs text-muted-foreground">{r.remark ?? "—"}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
                {rc.teacherRemark && <p className="mt-3 text-sm rounded-lg bg-secondary/50 p-3"><b>Class teacher:</b> {rc.teacherRemark}</p>}
                {rc.headRemark && <p className="mt-2 text-sm rounded-lg bg-secondary/50 p-3"><b>Head of school:</b> {rc.headRemark}</p>}
              </CardContent>
            </Card>
          )) : <EmptyState icon={<GraduationCap className="size-8" />} title={me.reportCards.length ? "Results not published yet" : "No reports yet"} hint={me.reportCards.length ? "Your school has compiled this term's results but hasn't published them yet — they will appear here once released." : "Your terminal reports appear here once published."} />}
        </TabsContent>

        <TabsContent value="library" className="mt-3 space-y-4">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-2 flex-row items-center justify-between space-y-0">
                <CardTitle className="text-base flex items-center gap-2"><BookOpen className="size-4" /> Books out in my name</CardTitle>
                <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={() => setRequestOpen(true)}>
                  <BookPlus className="size-3.5" /> Request a book
                </Button>
              </CardHeader>
              <CardContent>
                {me.library.length ? (
                  <div className="space-y-2">
                    {me.library.map((l, i) => (
                      <div key={i} className={`flex items-center justify-between rounded-lg border px-3 py-2.5 ${l.overdue ? "border-destructive/40 bg-destructive/5" : ""}`}>
                        <div className="flex items-center gap-2.5">
                          <span className="flex size-8 items-center justify-center rounded-full text-white" style={{ background: avatarColor(l.book) }}><BookOpen className="size-4" /></span>
                          <div>
                            <div className="text-sm font-medium">{l.book}</div>
                            <div className="text-xs text-muted-foreground">due {fmtDate(l.dueAt)}</div>
                          </div>
                        </div>
                        {l.overdue
                          ? <Badge className="bg-destructive gap-1"><AlertTriangle className="size-3" /> overdue</Badge>
                          : <Badge variant="secondary" className="gap-1"><CheckCircle2 className="size-3" /> on time</Badge>}
                      </div>
                    ))}
                  </div>
                ) : <EmptyState title="No books out" hint="Request a book below — the librarian will issue it." />}
              </CardContent>
            </Card>

            {/* iEduPro parity: my book requests with the librarian's decision */}
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-base flex items-center gap-2">
                  <BookPlus className="size-4" /> My requests
                  {pendingRequests > 0 && <Badge className="bg-amber-600 text-[10px]">{pendingRequests} waiting</Badge>}
                </CardTitle>
              </CardHeader>
              <CardContent>
                {me.bookRequests.length ? (
                  <div className="space-y-2 max-h-96 overflow-y-auto nice-scroll">
                    {me.bookRequests.map((r) => {
                      const s = REQ_STATUS[r.status] ?? REQ_STATUS.PENDING;
                      return (
                        <div key={r.id} className={`flex items-center justify-between gap-2 rounded-lg border px-3 py-2.5 ${r.overdue ? "border-destructive/40 bg-destructive/5" : ""}`}>
                          <div className="min-w-0">
                            <div className="text-sm font-medium truncate">{r.book}</div>
                            <div className="text-xs text-muted-foreground">
                              {fmtDate(r.startAt)} → {fmtDate(r.endAt)}
                              {r.status !== "PENDING" && r.handledByName ? ` · by ${r.handledByName}` : ""}
                            </div>
                          </div>
                          <div className="flex flex-col items-end gap-1 shrink-0">
                            <Badge className={cn("gap-1", s.className)}>{s.icon} {s.label}</Badge>
                            {r.overdue && <Badge className="bg-destructive gap-1"><AlertTriangle className="size-3" /> overdue</Badge>}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                ) : <EmptyState title="No requests yet" hint="Pick a book from the catalogue and choose your dates — the librarian confirms." />}
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="attendance" className="mt-3">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base">My attendance</CardTitle></CardHeader>
            <CardContent>
              {me.attendance.total ? (
                <>
                  <div className="grid grid-cols-4 gap-3 mb-4">
                    <Mini label="Present" value={me.attendance.present} tone="good" />
                    <Mini label="Late" value={me.attendance.late} tone="warn" />
                    <Mini label="Absent" value={me.attendance.absent} tone="bad" />
                    <Mini label="Sessions" value={me.attendance.total} />
                  </div>
                  <div className="mb-1 flex justify-between text-xs text-muted-foreground"><span>Attendance rate</span><span className="font-semibold">{me.attendance.rate}%</span></div>
                  <Progress value={me.attendance.rate ?? 0} className="h-2" />
                  <div className="mt-4 flex gap-1.5 flex-wrap">
                    {me.attendance.recent.map((r, i) => (
                      <div key={i} className="w-16 rounded-lg border overflow-hidden">
                        <div className={`h-1.5 ${ATT_COLOR[r.status] ?? "bg-zinc-300"}`} />
                        <div className="px-1.5 py-1">
                          <div className="text-[10px] font-medium">{r.status}</div>
                          <div className="text-[9px] text-muted-foreground">{fmtDate(r.date).slice(0, 6)}</div>
                        </div>
                      </div>
                    ))}
                  </div>
                </>
              ) : <EmptyState title="No attendance recorded yet" />}
            </CardContent>
          </Card>
        </TabsContent>
        {/* ── ONLINE EXAMS (iEduPro parity — learners sit published MCQ papers) ── */}
        <TabsContent value="exams" className="mt-3">
          <StudentExams studentId={me.id} classroomId={me.classroomId ?? null} />
        </TabsContent>
        <TabsContent value="messages" className="mt-3">
          {/* wave 40 — two-way conversations with the class teacher & the head
              (same threads the staff Messages module replies from) */}
          <PortalMessenger />
        </TabsContent>
      </Tabs>

      {/* iEduPro parity: request a book from the catalogue with desired dates */}
      <BookRequestDialog
        open={requestOpen}
        onOpenChange={setRequestOpen}
        catalogue={data.libraryCatalogue}
        studentId={me.id}
        schoolId={currentSchoolId!}
        onDone={load}
      />

      {/* wave 71 — confidential self-referral to the G&C unit */}
      <CounselRequestDialog
        open={counselOpen}
        onOpenChange={setCounselOpen}
        studentId={me.id}
        schoolId={currentSchoolId!}
      />
    </div>
  );
}

/* ── wave 71 — confidential self-referral: the learner (or their parent, from
    the parent portal) asks the G&C unit for a private chat. It lands as a
    SELF-source case on the counsellor's board — nothing is posted publicly. ── */
const COUNSEL_TOPICS = [
  { value: "ACADEMIC", label: "School work is hard right now" },
  { value: "PEER", label: "Friends / classmates" },
  { value: "FAMILY", label: "Something at home" },
  { value: "HEALTH", label: "Not feeling well (body or mind)" },
  { value: "FINANCIAL", label: "School fees or money worries" },
  { value: "OTHER", label: "Something else" },
];

function CounselRequestDialog({ open, onOpenChange, studentId, schoolId }: {
  open: boolean; onOpenChange: (o: boolean) => void; studentId: string; schoolId: string;
}) {
  const [topic, setTopic] = useState("PEER");
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (open) { setTopic("PEER"); setMessage(""); }
  }, [open]);

  async function submit() {
    if (!message.trim()) { toast({ title: "Write a short note first", description: "Just a few words — the counsellor will ask the rest gently." }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}/ops/portal`, {
        action: "counsel.self-refer",
        studentId, topic, message: message.trim(),
      });
      toast({
        title: "Note sent — well done for asking",
        description: "The counsellor will arrange a quiet time with you soon. This note is private.",
      });
      onOpenChange(false);
    } catch (e: any) {
      toast({ title: "Could not send the note", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><HandHeart className="size-4.5 text-primary" /> Talk to the counsellor</DialogTitle>
          <DialogDescription>
            This goes straight to the school counsellor as a private note. No teacher or classmate sees it.
          </DialogDescription>
        </DialogHeader>
        <div className="grid gap-3">
          <div className="space-y-1.5">
            <Label>What is it about?</Label>
            <Select value={topic} onValueChange={setTopic}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                {COUNSEL_TOPICS.map((t) => <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Your note (private)</Label>
            <Textarea rows={4} value={message} onChange={(e) => setMessage(e.target.value)} placeholder="Write a few words — you can say as much or as little as you want." />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button disabled={busy || !message.trim()} onClick={submit} className="gap-1.5">
            {busy ? "Sending…" : <><Send className="size-4" /> Send privately</>}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── Request-a-book dialog (iEduPro parity) — pick a title from the catalogue,
    choose the issue/return dates, and the librarian sees it in their queue. ── */
function BookRequestDialog({ open, onOpenChange, catalogue, studentId, schoolId, onDone }: {
  open: boolean; onOpenChange: (o: boolean) => void;
  catalogue: CatalogueBook[]; studentId: string; schoolId: string; onDone: () => void;
}) {
  const today = new Date().toISOString().slice(0, 10);
  const defaultEnd = new Date(Date.now() + 14 * 864e5).toISOString().slice(0, 10);
  const [bookId, setBookId] = useState("");
  const [startAt, setStartAt] = useState(today);
  const [endAt, setEndAt] = useState(defaultEnd);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (open) { setBookId(""); setStartAt(today); setEndAt(defaultEnd); }
  }, [open]);

  const book = catalogue.find((b) => b.id === bookId) ?? null;

  async function submit() {
    if (!bookId) { toast({ title: "Choose a book" }); return; }
    if (!startAt || !endAt || endAt <= startAt) { toast({ title: "Check your dates", description: "The return date must be after the issue date." }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}/ops/portal`, { action: "book-request.create", bookId, studentId, startAt, endAt });
      toast({ title: "Request sent", description: `"${book?.title}" — the librarian will confirm shortly.` });
      onOpenChange(false);
      onDone();
    } catch (e: any) {
      toast({ title: "Request failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><BookPlus className="size-4.5" /> Request a book</DialogTitle>
          <DialogDescription>Pick a title and when you&apos;d like it — the librarian issues it if a copy is on the shelf.</DialogDescription>
        </DialogHeader>
        <div className="grid gap-3">
          <div className="space-y-1.5">
            <Label>Book *</Label>
            <Select value={bookId} onValueChange={setBookId}>
              <SelectTrigger><SelectValue placeholder="Choose from the catalogue…" /></SelectTrigger>
              <SelectContent className="max-h-64">
                {catalogue.map((b) => (
                  <SelectItem key={b.id} value={b.id} disabled={b.available < 1}>
                    {b.title} — {b.available < 1 ? "all copies out" : `${b.available} on shelf`}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {book && <p className="text-[11px] text-muted-foreground">{book.author ?? "—"} · {book.category}</p>}
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Issue date</Label>
              <DateField value={startAt} onChange={(v) => setStartAt(v ?? "")} />
            </div>
            <div className="space-y-1.5">
              <Label>Return by</Label>
              <DateField value={endAt} onChange={(v) => setEndAt(v ?? "")} />
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button disabled={busy || !bookId} onClick={submit} className="gap-1.5">
            {busy ? "Sending…" : <><Send className="size-4" /> Send request</>}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function Tile({ icon, label, value, sub, tone }: { icon: React.ReactNode; label: string; value: React.ReactNode; sub?: string; tone?: "good" | "danger" }) {
  return (
    <div className={`rounded-xl border bg-card p-4 ${tone === "danger" ? "border-destructive/30" : ""}`}>
      <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</span>{icon}</div>
      <div className={`mt-1.5 text-xl font-bold tabular-nums ${tone === "danger" ? "text-destructive" : tone === "good" ? "text-emerald-600 dark:text-emerald-400" : ""}`}>{value}</div>
      <div className="text-[11px] text-muted-foreground truncate">{sub}</div>
    </div>
  );
}

function Mini({ label, value, tone }: { label: string; value: React.ReactNode; tone?: "good" | "warn" | "bad" }) {
  return (
    <div className="rounded-lg border p-2.5 text-center">
      <div className="text-[10px] uppercase tracking-wide text-muted-foreground">{label}</div>
      <div className={`text-lg font-bold tabular-nums ${tone === "good" ? "text-emerald-600 dark:text-emerald-400" : tone === "bad" ? "text-destructive" : tone === "warn" ? "text-amber-600" : ""}`}>{value}</div>
    </div>
  );
}

/* ── Online exams tab — the learner sits published papers for their class
    (iEduPro online_exam parity; MCQ + true/false + fill-in-the-blanks,
    auto-marked against the answer key; a countdown auto-submits) ── */
type PortalExam = {
  id: string; title: string; subjectName: string; description: string | null;
  classroom: string | null; classroomId: string | null; durationMins: number;
  status: string; questionCount: number; totalMarks: number;
  questions: { id: string; type: string; prompt: string; optionA: string | null; optionB: string | null; optionC: string | null; optionD: string | null }[];
  attempts: { studentId: string; score: number | null; max: number }[];
};

function StudentExams({ studentId, classroomId }: { studentId: string; classroomId: string | null }) {
  const { currentSchoolId } = useStore();
  const [exams, setExams] = useState<PortalExam[] | null>(null);
  const [sitting, setSitting] = useState<PortalExam | null>(null);
  const [answers, setAnswers] = useState<Record<string, string>>({});
  const [blankInputs, setBlankInputs] = useState<Record<string, string>>({});
  const [justScored, setJustScored] = useState<{ score: number; max: number } | null>(null);
  const [secsLeft, setSecsLeft] = useState<number | null>(null);
  const submittedRef = useRef(false);

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<{ exams: PortalExam[] }>(`/api/school/${currentSchoolId}/ops/exams`)
      .then((d) => setExams(d.exams))
      .catch(() => setExams([]));
  };
  useEffect(() => { load(); }, [currentSchoolId]);

  /** open the paper: tell the server (live-monitor signal) and start the countdown */
  async function openPaper(e: PortalExam) {
    setSitting(e); setAnswers({}); setBlankInputs({}); setJustScored(null);
    submittedRef.current = false;
    setSecsLeft(e.durationMins * 60);
    apiPost(`/api/school/${currentSchoolId}/ops/exams`, { action: "start-attempt", examId: e.id, studentId }).catch(() => {});
  }

  /** assemble fill-blank inputs (one per blank) into comma-joined answer strings */
  function assembled(): Record<string, string> {
    const out = { ...answers };
    for (const q of sitting?.questions ?? []) {
      if (q.type !== "FILL_BLANKS") continue;
      const idx = String(sitting!.questions.indexOf(q));
      const blanks = q.prompt.split("-").length - 1;
      const words: string[] = [];
      for (let b = 0; b < blanks; b++) {
        const v = blankInputs[`${idx}:${b}`]?.trim();
        if (v) words.push(v);
      }
      out[idx] = words.join(",");
    }
    return out;
  }

  async function submit(auto = false) {
    if (!sitting || submittedRef.current) return;
    submittedRef.current = true;
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/ops/exams`, {
        action: "submit-attempt", examId: sitting.id, studentId, answers: assembled(),
      });
      setJustScored({ score: res.score, max: res.max });
      setSitting(null);
      setAnswers({}); setBlankInputs({}); setSecsLeft(null);
      if (auto) toast({ title: "Time is up — your paper was submitted automatically" });
      load();
    } catch (e: any) {
      submittedRef.current = false;
      toast({ title: "Submit failed", description: e.message, variant: "destructive" });
    }
  }

  /* countdown — auto-submits at zero (iEduPro modal_time_out parity) */
  useEffect(() => {
    if (secsLeft == null || !sitting) return;
    const t = setTimeout(() => {
      if (secsLeft <= 0) submit(true);
      else setSecsLeft((s) => (s == null ? null : s - 1));
    }, 1000);
    return () => clearTimeout(t);
  }, [secsLeft, sitting]);

  if (!exams) return <LoadingBlock label="Loading exams…" />;
  const forMe = exams.filter((e) => e.status === "PUBLISHED" && (!e.classroomId || e.classroomId === classroomId));
  const answeredCount = () => {
    const a = assembled();
    return (sitting?.questions ?? []).filter((q, i) => a[String(i)] !== undefined && a[String(i)] !== "").length;
  };

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Laptop className="size-4" /> Open exams</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {forMe.length ? forMe.map((e) => {
            const att = e.attempts[0];
            const done = att && att.score != null;
            return (
              <div key={e.id} className="rounded-xl border p-3 flex flex-wrap items-center justify-between gap-2">
                <div className="min-w-0">
                  <div className="font-medium">{e.title}</div>
                  <div className="text-[11px] text-muted-foreground">
                    {e.subjectName} · {e.classroom ?? "all classes"} · {e.questionCount} questions · {e.totalMarks} marks · {e.durationMins} min
                    {e.description ? ` · ${e.description}` : ""}
                  </div>
                </div>
                {done ? (
                  <Badge variant="secondary" className="gap-1"><CheckCircle2 className="size-3" /> submitted — {att!.score}/{att!.max}</Badge>
                ) : (
                  <Button size="sm" className="gap-1.5" onClick={() => openPaper(e)}><Send className="size-3.5" /> {att ? "Continue" : "Sit exam"}</Button>
                )}
              </div>
            );
          }) : <EmptyState title="No open exams" hint="Your teachers will publish online exams here." />}
        </CardContent>
      </Card>
      {justScored && (
        <Card className="border-emerald-300 dark:border-emerald-900 bg-emerald-50/50 dark:bg-emerald-950/20">
          <CardContent className="pt-4 text-center">
            <div className="text-lg font-semibold">Medaase — submitted!</div>
            <div className="text-sm text-muted-foreground">You scored {justScored.score} of {justScored.max} ({Math.round((justScored.score / Math.max(1, justScored.max)) * 100)}%)</div>
          </CardContent>
        </Card>
      )}
      <Dialog open={!!sitting} onOpenChange={(o) => { if (!o) { setSitting(null); setAnswers({}); setBlankInputs({}); setSecsLeft(null); } }}>
        <DialogContent className="max-w-xl max-h-[92vh] overflow-y-auto nice-scroll">
          <DialogHeader>
            <DialogTitle className="flex items-center justify-between gap-2 pr-8">
              <span className="truncate">{sitting?.title}</span>
              {secsLeft != null && (
                <span className={cn("text-xs font-bold tabular-nums px-2 py-1 rounded-full", secsLeft <= 60 ? "bg-red-100 text-red-700 animate-pulse" : "bg-secondary")}>
                  {Math.floor(secsLeft / 60)}:{String(secsLeft % 60).padStart(2, "0")}
                </span>
              )}
            </DialogTitle>
            <DialogDescription>
              {sitting?.subjectName} · {sitting?.questionCount} questions · {sitting?.totalMarks} marks · {sitting?.durationMins} minutes.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            {(sitting?.questions ?? []).map((q, i) => (
              <div key={q.id} className="rounded-xl border p-3">
                {q.type === "FILL_BLANKS" ? (
                  <>
                    <div className="font-medium text-sm mb-2">
                      {i + 1}.{" "}
                      {q.prompt.split("-").map((part, j, all) => (
                        <span key={j}>
                          {part}
                          {j < all.length - 1 && (
                            <input
                              value={blankInputs[`${i}:${j}`] ?? ""}
                              onChange={(e) => setBlankInputs((b) => ({ ...b, [`${i}:${j}`]: e.target.value }))}
                              className="inline-block w-24 border-b-2 border-primary bg-transparent px-1 mx-1 text-center focus:outline-none"
                              placeholder="…"
                            />
                          )}
                        </span>
                      ))}
                    </div>
                    <div className="text-[11px] text-muted-foreground">Fill each blank — spelling counts, capital letters don't.</div>
                  </>
                ) : q.type === "TRUE_FALSE" ? (
                  <>
                    <div className="font-medium text-sm mb-2">{i + 1}. {q.prompt}</div>
                    <div className="grid grid-cols-2 gap-2">
                      {(["TRUE", "FALSE"] as const).map((v) => {
                        const picked = answers[String(i)] === v;
                        return (
                          <button
                            key={v}
                            onClick={() => setAnswers((a) => ({ ...a, [String(i)]: v }))}
                            className={cn(
                              "rounded-lg border p-2 text-left text-sm transition-colors",
                              picked ? "border-primary bg-primary/10 font-medium" : "hover:bg-secondary/50",
                            )}
                          >
                            {v === "TRUE" ? "True" : "False"}
                          </button>
                        );
                      })}
                    </div>
                  </>
                ) : (
                  <>
                    <div className="font-medium text-sm mb-2">{i + 1}. {q.prompt}</div>
                    <div className="grid grid-cols-2 gap-2">
                      {(["A", "B", "C", "D"] as const).map((L) => {
                        const opt = (q as any)[`option${L}`] as string | null;
                        if (!opt) return null;
                        const picked = answers[String(i)] === L;
                        return (
                          <button
                            key={L}
                            onClick={() => setAnswers((a) => ({ ...a, [String(i)]: L }))}
                            className={cn(
                              "rounded-lg border p-2 text-left text-sm transition-colors",
                              picked ? "border-primary bg-primary/10 font-medium" : "hover:bg-secondary/50",
                            )}
                          >
                            <b className="mr-1.5">{L}.</b>{opt}
                          </button>
                        );
                      })}
                    </div>
                  </>
                )}
              </div>
            ))}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => { setSitting(null); setAnswers({}); setBlankInputs({}); setSecsLeft(null); }}>Cancel</Button>
            <Button className="gap-1.5" onClick={() => submit()} disabled={answeredCount() !== (sitting?.questions.length ?? 0)}>
              Submit {answeredCount()}/{sitting?.questions.length ?? 0}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

/* ── wave 65 — Homework tab: the learner's own assignment list ──
   Read the teacher's instructions, confirm "I have done it" (late is flagged
   automatically after the due date), and see the score + teacher feedback
   once marking is done. Parents submit on the child's behalf from their own
   portal tab. */
function StudentHomework({ homework, studentId, onChanged }: {
  homework: HwLite[]; studentId: string; onChanged: () => void;
}) {
  const { currentSchoolId } = useStore();
  const [submitting, setSubmitting] = useState<HwLite | null>(null);
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);

  const open = homework.filter((h) => h.status === "OPEN");
  const done = homework.filter((h) => h.status === "CLOSED" || h.myStatus === "GRADED" || h.myStatus === "SUBMITTED" || h.myStatus === "LATE");

  async function submitHw() {
    if (!submitting) return;
    setBusy(true);
    try {
      const r = await apiPost<{ status: string }>(`/api/school/${currentSchoolId}/ops/portal`, {
        action: "homework.self-submit", homeworkId: submitting.id, studentId, note,
      });
      toast({
        title: r.status === "LATE" ? "Submitted — marked late" : "Homework submitted",
        description: r.status === "LATE"
          ? "The due date has passed. Your teacher will still see it."
          : "Nice one — your teacher has been notified.",
      });
      setSubmitting(null); setNote(""); onChanged();
    } catch (e: any) {
      toast({ title: "Could not submit", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  const HW_CHIP: Record<string, string> = {
    PENDING: "bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30",
    SUBMITTED: "bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30",
    LATE: "bg-orange-500/15 text-orange-700 dark:text-orange-300 border-orange-500/30",
    GRADED: "bg-primary/15 text-primary border-primary/30",
    EXCUSED: "bg-zinc-500/15 text-zinc-600 dark:text-zinc-300 border-zinc-500/30",
  };

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-3 gap-3">
        <Mini label="Outstanding" value={open.filter((h) => h.myStatus === "PENDING").length} tone={open.some((h) => h.myStatus === "PENDING") ? "warn" : "good"} />
        <Mini label="Handed in" value={homework.filter((h) => h.myStatus === "SUBMITTED" || h.myStatus === "LATE").length} tone="good" />
        <Mini label="Marked" value={homework.filter((h) => h.myStatus === "GRADED").length} />
      </div>

      {[open.length > 0 && "open", done.length > 0 && "done"].filter(Boolean).length === 0 && (
        <Card><CardContent className="py-8"><EmptyState icon={<BookPlus className="size-8" />} title="No homework yet" hint="When your teacher posts an assignment, it appears here." /></CardContent></Card>
      )}

      {open.length > 0 && (
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><PenLine className="size-4" /> Current assignments</CardTitle></CardHeader>
          <CardContent className="space-y-2.5">
            {open.map((h) => {
              const dueIn = Math.ceil((new Date(h.dueDate).getTime() - Date.now()) / 86400000);
              return (
                <div key={h.id} className="rounded-xl border p-3.5">
                  <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                      <div className="flex items-center gap-1.5 flex-wrap">
                        <Badge variant="secondary" className="text-[10px]">{h.subject}</Badge>
                        <span className={cn("inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium", HW_CHIP[h.myStatus] ?? "")}>{h.myStatus.toLowerCase()}</span>
                      </div>
                      <div className="font-semibold leading-snug mt-1.5">{h.title}</div>
                      <div className="text-xs text-muted-foreground mt-0.5">by {h.postedBy} · max {h.maxScore} marks</div>
                    </div>
                    <div className="text-right shrink-0">
                      <div className={cn("text-[11px] font-semibold", h.overdue ? "text-destructive" : dueIn <= 1 ? "text-amber-600 dark:text-amber-400" : "text-muted-foreground")}>
                        {h.overdue ? "overdue" : dueIn <= 0 ? "due today" : dueIn === 1 ? "due tomorrow" : `due in ${dueIn}d`}
                      </div>
                      <div className="text-[10px] text-muted-foreground">{fmtDate(h.dueDate)}</div>
                    </div>
                  </div>
                  <p className="text-sm mt-2 rounded-lg bg-secondary/40 border p-2.5 whitespace-pre-wrap leading-relaxed max-h-36 overflow-y-auto">{h.instructions}</p>
                  {h.myFeedback && (
                    <div className="mt-2 rounded-lg border border-primary/30 bg-primary/5 p-2.5 text-sm">
                      <span className="font-medium">Teacher: </span>{h.myFeedback}
                      {h.myScore != null && <Badge className="ml-2 bg-primary">{h.myScore}/{h.maxScore}</Badge>}
                    </div>
                  )}
                  {(h.myStatus === "PENDING") ? (
                    <Button size="sm" className="mt-2.5 gap-1.5" onClick={() => { setSubmitting(h); setNote(""); }}>
                      <CheckCircle2 className="size-3.5" /> I have done it
                    </Button>
                  ) : h.myNote ? (
                    <div className="text-[11px] text-muted-foreground mt-2">Your note: &ldquo;{h.myNote}&rdquo;</div>
                  ) : null}
                </div>
              );
            })}
          </CardContent>
        </Card>
      )}

      {done.length > 0 && (
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-base">Earlier work</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {done.map((h) => (
              <div key={h.id} className="flex items-center justify-between gap-3 rounded-lg border p-2.5">
                <div className="min-w-0">
                  <div className="text-sm font-medium truncate">{h.title}</div>
                  <div className="text-[11px] text-muted-foreground">{h.subject} · due {fmtDate(h.dueDate)}</div>
                </div>
                <div className="flex items-center gap-2 shrink-0">
                  <span className={cn("inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium", HW_CHIP[h.myStatus] ?? "")}>{h.myStatus.toLowerCase()}</span>
                  {h.myScore != null && <span className="text-sm font-bold tabular-nums">{h.myScore}/{h.maxScore}</span>}
                </div>
              </div>
            ))}
          </CardContent>
        </Card>
      )}

      <Dialog open={!!submitting} onOpenChange={(o) => !o && setSubmitting(null)}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><CheckCircle2 className="size-4.5" /> Confirm — {submitting?.title}</DialogTitle>
            <DialogDescription>
              Tell your teacher the work is done. Handing in after the due date is recorded as late.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <Label htmlFor="hw-note">Note for the teacher (optional)</Label>
            <Textarea id="hw-note" value={note} onChange={(e) => setNote(e.target.value)} rows={3} placeholder="e.g. I finished all 10 sums; my exercise book is in my bag." />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setSubmitting(null)}>Not yet</Button>
            <Button onClick={submitHw} disabled={busy} className="gap-1.5">
              {busy ? <Loader2 className="size-4 animate-spin" /> : <CheckCircle2 className="size-4" />} Submit homework
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

"use client";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate } from "@/lib/format";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "@/hooks/use-toast";
import { avatarColor } from "@/lib/format";
import { cn } from "@/lib/utils";
import { Backpack, CalendarDays, BookOpen, GraduationCap, Wallet, Megaphone, Clock, AlertTriangle, CheckCircle2, UserRound, Laptop, Send, BookPlus, Hourglass, XCircle } from "lucide-react";

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

  return (
    <div>
      <ModuleHeader
        title={`My Learning — ${me.name}`}
        description={`${me.classroom ?? "—"} · ${data.term ?? "Term"} ${data.year ? `· ${data.year}` : ""} · Class teacher: ${me.classTeacher ?? "—"}`}
        actions={<Badge variant="outline" className="font-mono">{me.nyansapoIndex}</Badge>}
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <Tile icon={<CalendarDays className="size-4" />} label="My attendance" value={me.attendance.rate !== null ? `${me.attendance.rate}%` : "—"} sub={`${me.attendance.present} present of ${me.attendance.total}`} tone={me.attendance.rate !== null && me.attendance.rate >= 90 ? "good" : undefined} />
        <Tile icon={<GraduationCap className="size-4" />} label="Latest average" value={me.reportCards[0]?.average != null ? `${me.reportCards[0].average}%` : "—"} sub={me.reportCards[0]?.term ?? "awaiting reports"} />
        <Tile icon={<BookOpen className="size-4" />} label="Library books" value={me.library.length} sub={overdueBooks.length ? `${overdueBooks.length} overdue — return soon!` : "none overdue"} tone={overdueBooks.length ? "danger" : undefined} />
        <Tile icon={<Wallet className="size-4" />} label="Fees" value={me.feesBalance > 0 ? fmtCedi(me.feesBalance, { compact: true }) : "Settled"} sub={me.feesBalance > 0 ? "outstanding balance" : "nothing owing — keep it up!"} tone={me.feesBalance > 0 ? "danger" : "good"} />
      </div>

      <Tabs defaultValue="overview">
        <TabsList className="flex-wrap h-auto">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="timetable">Timetable</TabsTrigger>
          <TabsTrigger value="results">My results</TabsTrigger>
          <TabsTrigger value="library">Library</TabsTrigger>
          <TabsTrigger value="attendance">Attendance</TabsTrigger>
          <TabsTrigger value="exams">Online exams</TabsTrigger>
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

        <TabsContent value="timetable" className="mt-3">
          <Card>
            <CardHeader className="pb-2"><CardTitle className="text-base">Weekly routine — {me.classroom}</CardTitle></CardHeader>
            <CardContent>
              {me.timetable.length ? (
                <div className="grid md:grid-cols-5 gap-2">
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
          {me.reportCards.length ? me.reportCards.map((rc) => (
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
          )) : <EmptyState icon={<GraduationCap className="size-8" />} title="No reports yet" hint="Your terminal reports appear here once published." />}
        </TabsContent>

        <TabsContent value="library" className="mt-3 space-y-4">
          <div className="grid lg:grid-cols-2 gap-4">
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
    </div>
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
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>Issue date</Label>
              <Input type="date" value={startAt} onChange={(e) => setStartAt(e.target.value)} />
            </div>
            <div className="space-y-1.5">
              <Label>Return by</Label>
              <Input type="date" value={endAt} onChange={(e) => setEndAt(e.target.value)} />
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

/* ── Online exams tab — the learner sits published MCQ papers for their class
    (iEduPro online_exam parity; auto-marked against the answer key) ── */
type PortalExam = {
  id: string; title: string; subjectName: string; description: string | null;
  classroom: string | null; classroomId: string | null; durationMins: number;
  status: string; questionCount: number; totalMarks: number;
  questions: { id: string; prompt: string; optionA: string; optionB: string; optionC: string; optionD: string }[];
  attempts: { studentId: string; score: number | null; max: number }[];
};

function StudentExams({ studentId, classroomId }: { studentId: string; classroomId: string | null }) {
  const { currentSchoolId } = useStore();
  const [exams, setExams] = useState<PortalExam[] | null>(null);
  const [sitting, setSitting] = useState<PortalExam | null>(null);
  const [answers, setAnswers] = useState<Record<string, string>>({});
  const [justScored, setJustScored] = useState<{ score: number; max: number } | null>(null);

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<{ exams: PortalExam[] }>(`/api/school/${currentSchoolId}/ops/exams`)
      .then((d) => setExams(d.exams))
      .catch(() => setExams([]));
  };
  useEffect(() => { load(); }, [currentSchoolId]);

  if (!exams) return <LoadingBlock label="Loading exams…" />;
  const forMe = exams.filter((e) => e.status === "PUBLISHED" && (!e.classroomId || e.classroomId === classroomId));
  const myAttempts = new Map(exams.flatMap((e) => e.attempts).map((a) => [a.studentId, a]));

  async function submit() {
    if (!sitting) return;
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/ops/exams`, {
        action: "submit-attempt", examId: sitting.id, studentId, answers,
      });
      setJustScored({ score: res.score, max: res.max });
      setSitting(null);
      setAnswers({});
      load();
    } catch (e: any) { toast({ title: "Submit failed", description: e.message, variant: "destructive" }); }
  }

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Laptop className="size-4" /> Open exams</CardTitle></CardHeader>
        <CardContent className="space-y-2">
          {forMe.length ? forMe.map((e) => {
            const done = myAttempts.has(studentId);
            return (
              <div key={e.id} className="rounded-xl border p-3 flex flex-wrap items-center justify-between gap-2">
                <div className="min-w-0">
                  <div className="font-medium">{e.title}</div>
                  <div className="text-[11px] text-muted-foreground">
                    {e.subjectName} · {e.classroom ?? "all classes"} · {e.questionCount} questions · {e.durationMins} min
                    {e.description ? ` · ${e.description}` : ""}
                  </div>
                </div>
                {done ? (
                  <Badge variant="secondary" className="gap-1"><CheckCircle2 className="size-3" /> submitted — {myAttempts.get(studentId)!.score}/{myAttempts.get(studentId)!.max}</Badge>
                ) : (
                  <Button size="sm" className="gap-1.5" onClick={() => { setSitting(e); setAnswers({}); setJustScored(null); }}><Send className="size-3.5" /> Sit exam</Button>
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
      <Dialog open={!!sitting} onOpenChange={(o) => { if (!o) { setSitting(null); setAnswers({}); } }}>
        <DialogContent className="max-w-xl max-h-[92vh] overflow-y-auto nice-scroll">
          <DialogHeader>
            <DialogTitle>{sitting?.title}</DialogTitle>
            <DialogDescription>
              {sitting?.subjectName} · {sitting?.questionCount} questions · {sitting?.durationMins} minutes · pick one option each.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            {(sitting?.questions ?? []).map((q, i) => (
              <div key={q.id} className="rounded-xl border p-3">
                <div className="font-medium text-sm mb-2">{i + 1}. {q.prompt}</div>
                <div className="grid grid-cols-2 gap-2">
                  {(["A", "B", "C", "D"] as const).map((L) => {
                    const opt = (q as any)[`option${L}`] as string;
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
              </div>
            ))}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => { setSitting(null); setAnswers({}); }}>Cancel</Button>
            <Button className="gap-1.5" onClick={submit} disabled={Object.keys(answers).length !== (sitting?.questions.length ?? 0)}>
              Submit {Object.keys(answers).length}/{sitting?.questions.length ?? 0}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

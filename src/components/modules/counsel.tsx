"use client";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { ModuleHeader, LoadingBlock, EmptyState, FilterSelect } from "@/components/ui-helpers";
import { fmtDate, fmtDateTime, fmtAgo } from "@/lib/format";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "@/hooks/use-toast";
import {
  HeartHandshake, Plus, FileLock2, Search, X, Printer, Sparkles, PhoneCall, CalendarClock,
  CheckCircle2, BellRing, UserRound, Building2, ClipboardList, Eye, EyeOff, ShieldAlert, HandHeart,
} from "lucide-react";

// ── wave 71 — Guidance & Counselling (G&C unit) ────────────────
// The confidential case file every GES school keeps beside the discipline
// register: refer a learner, log counselling sessions (AI-tidied summaries),
// track follow-up promises, refer serious cases out to DOVVSU / Social
// Welfare, and file the termly analytics. Confidential cases stay invisible
// to everyone but the assigned counsellor, the opener and the head.

type CaseRow = {
  id: string; caseNo: string; category: string; severity: string; status: string; source: string;
  title: string; summary: string | null; confidential: boolean; referredExternal: string | null;
  openedByName: string; closedAt: string | null; closedNote: string | null;
  createdAt: string; updatedAt: string;
  studentId: string; studentName: string; nyansapoIndex: string;
  classroomName: string | null; guardianName: string | null;
  assignedToId: string | null; assignedToName: string | null;
  sessionsCount: number; openFollowUps: number;
};
type SessionRow = { id: string; heldAt: string; mode: string; notes: string; aiSummary: string | null; outcome: string | null; attendees: string | null; loggedByName: string };
type FollowUpRow = { id: string; dueAt: string; note: string; doneAt: string | null; doneNote: string | null; overdue: boolean; remindedAt: string | null };
type StaffLite = { id: string; fullName: string; role: string; phone: string | null; smsNotify: boolean };
type StudentHit = { id: string; name: string; nyansapoIndex: string; classroomName: string | null };
type BoardData = {
  stat: Record<string, number>; criticalOpen: number; overdueFollowUps: number;
  recent: CaseRow[]; openCases: CaseRow[];
  staff: StaffLite[]; classrooms: { id: string; name: string }[];
  catalogues: { categories: string[]; severities: string[]; statuses: string[]; sources: string[]; modes: string[]; outcomes: string[] };
  canManage: boolean; meStaffId: string | null;
};
type AnalyticsData = {
  totals: { cases: number; open: number; resolved: number; referred: number; criticalOpen: number; sessions: number; avgDaysToResolve: number | null };
  categories: { name: string; count: number }[];
  bySeverity: Record<string, number>;
  byStatus: Record<string, number>;
  trend: { month: string; opened: number; resolved: number }[];
  referredOut: { caseNo: string; title: string; to: string; studentName: string; at: string }[];
  counsellors: { name: string; count: number }[];
};

const CATEGORY_LABELS: Record<string, string> = {
  ACADEMIC: "Academic", BEHAVIOURAL: "Behavioural", FAMILY: "Family / home", HEALTH: "Health",
  CAREER: "Career guidance", PEER: "Peer relations", ATTENDANCE: "Attendance",
  FINANCIAL: "Financial", ABUSE_REPORT: "Abuse report", OTHER: "Other",
};
const SOURCE_LABELS: Record<string, string> = {
  TEACHER_REFERRAL: "Teacher referral", SELF: "Learner self-referral", PARENT: "Parent",
  DISCIPLINE: "From discipline", ATTENDANCE: "From attendance", HEALTH: "From sick bay", ANON: "Anonymous",
};
const MODE_LABELS: Record<string, string> = { FACE_TO_FACE: "Face to face", GROUP: "Group session", PHONE: "Phone call", HOME_VISIT: "Home visit" };
const SEVERITY_CLS: Record<string, string> = {
  LOW: "bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300",
  MEDIUM: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
  HIGH: "bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300",
  CRITICAL: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300",
};
const STATUS_CLS: Record<string, string> = {
  OPEN: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
  IN_PROGRESS: "bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300",
  MONITORING: "bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300",
  RESOLVED: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  REFERRED: "bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-950 dark:text-fuchsia-300",
};
const OUTCOME_CLS: Record<string, string> = {
  IMPROVED: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  NO_CHANGE: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
  WORSE: "bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300",
  ESCALATED: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300",
};
const Chip = ({ label, cls }: { label: string; cls: string }) => (
  <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold whitespace-nowrap ${cls}`}>{label}</span>
);

export function Counsel() {
  const { currentSchoolId } = useStore();
  const [board, setBoard] = useState<BoardData | null>(null);
  const [rows, setRows] = useState<CaseRow[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [status, setStatus] = useState("active");
  const [category, setCategory] = useState("all");
  const [severity, setSeverity] = useState("all");
  const [mine, setMine] = useState(false);
  const [q, setQ] = useState("");
  const [newOpen, setNewOpen] = useState(false);
  const [fileId, setFileId] = useState<string | null>(null);
  const analyticsOpen = useRef(false);

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<BoardData>(`/api/school/${currentSchoolId}/ops/counsel`)
      .then(setBoard)
      .catch((e) => toast({ title: "Could not load the G&C board", description: e.message, variant: "destructive" }))
      .finally(() => setLoading(false));
  }, [currentSchoolId]);
  useEffect(() => { load(); }, [load]);

  const loadCases = useCallback(() => {
    if (!currentSchoolId) return;
    const sp = new URLSearchParams({ action: "cases", take: "200" });
    // "active" is a client-side pseudo-status across OPEN / IN_PROGRESS / MONITORING
    const isPseudo = status === "active" || status === "all";
    if (!isPseudo) sp.set("status", status);
    if (category !== "all") sp.set("category", category);
    if (severity !== "all") sp.set("severity", severity);
    if (mine) sp.set("mine", "1");
    if (q.trim()) sp.set("q", q.trim());
    apiGet<{ rows: CaseRow[]; total: number }>(`/api/school/${currentSchoolId}/ops/counsel?${sp.toString()}`)
      .then((d) => {
        const filtered = status === "active"
          ? d.rows.filter((r) => r.status === "OPEN" || r.status === "IN_PROGRESS" || r.status === "MONITORING")
          : d.rows;
        setRows(filtered);
        setTotal(status === "active" ? filtered.length : d.total);
      })
      .catch((e) => toast({ title: "Could not load cases", description: e.message, variant: "destructive" }));
  }, [currentSchoolId, status, category, severity, mine, q]);
  useEffect(() => { const t = setTimeout(loadCases, 250); return () => clearTimeout(t); }, [loadCases]);

  const active = board ? board.stat.OPEN + board.stat.IN_PROGRESS + board.stat.MONITORING : 0;
  const filtersActive = status !== "all" || category !== "all" || severity !== "all" || mine || !!q.trim();
  const refreshAll = useCallback(() => { load(); loadCases(); }, [load, loadCases]);

  if (loading || !board) return <LoadingBlock label="Opening the counselling unit…" />;

  return (
    <div>
      <ModuleHeader
        title="Guidance & Counselling"
        description="The confidential case file — refer learners, log sessions, keep follow-up promises, refer serious cases out, file the termly G&C report."
        actions={
          <Button size="sm" className="gap-1.5" onClick={() => setNewOpen(true)}>
            <Plus className="size-4" /> Refer a learner
          </Button>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
        {[
          { icon: <HeartHandshake className="size-4" />, label: "Active cases", value: active, sub: "open · in progress · monitoring", tone: "default" as const },
          { icon: <ShieldAlert className="size-4" />, label: "High & critical", value: board.criticalOpen, sub: "need attention now", tone: board.criticalOpen ? ("danger" as const) : ("default" as const) },
          { icon: <CalendarClock className="size-4" />, label: "Overdue follow-ups", value: board.overdueFollowUps, sub: "promises past due", tone: board.overdueFollowUps ? ("danger" as const) : ("default" as const) },
          { icon: <CheckCircle2 className="size-4" />, label: "Resolved", value: board.stat.RESOLVED, sub: "closed with a note", tone: "default" as const },
          { icon: <Building2 className="size-4" />, label: "Referred out", value: board.stat.REFERRED, sub: "DOVVSU · Social Welfare", tone: "default" as const },
        ].map((s) => (
          <div key={s.label} className={`rounded-xl border bg-card p-4 ${s.tone === "danger" ? "border-destructive/30" : ""}`}>
            <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{s.label}</span>{s.icon}</div>
            <div className={`mt-1.5 text-2xl font-bold tabular-nums ${s.tone === "danger" ? "text-destructive" : ""}`}>{s.value}</div>
            <div className="text-[11px] text-muted-foreground">{s.sub}</div>
          </div>
        ))}
      </div>

      <Tabs defaultValue="board" onValueChange={(v) => { if (v === "analytics") analyticsOpen.current = true; }}>
        <TabsList>
          <TabsTrigger value="board">Case board ({total})</TabsTrigger>
          <TabsTrigger value="followups" className={board.overdueFollowUps ? "text-destructive data-[state=active]:text-destructive" : ""}>
            Follow-ups{board.overdueFollowUps ? ` (${board.overdueFollowUps} overdue)` : ""}
          </TabsTrigger>
          <TabsTrigger value="analytics">Analytics</TabsTrigger>
        </TabsList>

        <TabsContent value="board" className="mt-3">
          <div className="rounded-xl border bg-card">
            <div className="p-3 border-b flex flex-wrap items-center gap-2">
              <div className="relative flex-1 max-w-sm">
                <Search className="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search learner name or index…" className="pl-8" />
              </div>
              <FilterSelect label="Status" value={status} onChange={setStatus}
                options={[
                  { value: "all", label: "All" }, { value: "active", label: "Active only" },
                  ...board.catalogues.statuses.map((s) => ({ value: s, label: s.replace(/_/g, " ").toLowerCase().replace(/\b\w/g, (c) => c.toUpperCase()) })),
                ]} />
              <FilterSelect label="Category" value={category} onChange={setCategory}
                options={[{ value: "all", label: "All categories" }, ...board.catalogues.categories.map((c) => ({ value: c, label: CATEGORY_LABELS[c] ?? c }))]} />
              <FilterSelect label="Severity" value={severity} onChange={setSeverity}
                options={[{ value: "all", label: "All severities" }, ...board.catalogues.severities.map((s) => ({ value: s, label: s.charAt(0) + s.slice(1).toLowerCase() }))]} />
              {board.canManage && (
                <Button size="sm" variant={mine ? "default" : "outline"} className="h-8 gap-1 text-xs" onClick={() => setMine(!mine)}>
                  <UserRound className="size-3.5" /> Mine
                </Button>
              )}
              {filtersActive && (
                <Button size="sm" variant="ghost" className="h-7 gap-1 text-xs" onClick={() => { setStatus("all"); setCategory("all"); setSeverity("all"); setMine(false); setQ(""); }}>
                  <X className="size-3" /> Reset
                </Button>
              )}
              <Badge variant="secondary" className="text-[10px] tabular-nums ml-auto">{rows.length} shown</Badge>
            </div>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Case</TableHead>
                  <TableHead>Learner</TableHead>
                  <TableHead className="hidden md:table-cell">Category</TableHead>
                  <TableHead>Severity</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="hidden lg:table-cell">Counsellor</TableHead>
                  <TableHead className="hidden sm:table-cell">Sessions</TableHead>
                  <TableHead className="hidden xl:table-cell">Updated</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((c) => (
                  <TableRow key={c.id} className="cursor-pointer" onClick={() => setFileId(c.id)}>
                    <TableCell>
                      <div className="font-medium flex items-center gap-1.5">
                        {c.confidential && <FileLock2 className="size-3 text-muted-foreground shrink-0" />}
                        <span className="font-mono text-xs text-muted-foreground">{c.caseNo}</span>
                      </div>
                      <div className="text-xs text-muted-foreground max-w-56 truncate">{c.title}</div>
                    </TableCell>
                    <TableCell>
                      <div className="font-medium">{c.studentName}</div>
                      <div className="text-xs text-muted-foreground">{c.classroomName ?? "—"} · {c.nyansapoIndex}</div>
                    </TableCell>
                    <TableCell className="hidden md:table-cell"><Badge variant="outline">{CATEGORY_LABELS[c.category] ?? c.category}</Badge></TableCell>
                    <TableCell><Chip label={c.severity} cls={SEVERITY_CLS[c.severity] ?? ""} /></TableCell>
                    <TableCell>
                      <Chip label={c.status.replace(/_/g, " ")} cls={STATUS_CLS[c.status] ?? ""} />
                      {c.referredExternal && <div className="text-[10px] text-muted-foreground mt-0.5 max-w-40 truncate">→ {c.referredExternal}</div>}
                    </TableCell>
                    <TableCell className="hidden lg:table-cell text-xs">{c.assignedToName ?? <span className="text-muted-foreground">unassigned</span>}</TableCell>
                    <TableCell className="hidden sm:table-cell">
                      <span className="tabular-nums">{c.sessionsCount}</span>
                      {c.openFollowUps > 0 && <Badge className="ml-1.5 bg-amber-600 text-[10px]">{c.openFollowUps} open</Badge>}
                    </TableCell>
                    <TableCell className="hidden xl:table-cell text-xs text-muted-foreground">{fmtAgo(c.updatedAt)}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
            {!rows.length && (
              <EmptyState
                icon={<HeartHandshake className="size-8" />}
                title={filtersActive ? "No cases match the filters" : "No counselling cases yet"}
                hint={filtersActive ? "Try another search." : "Refer the first learner — every teacher can."}
              />
            )}
          </div>
        </TabsContent>

        <TabsContent value="followups" className="mt-3">
          <FollowUpsTab schoolId={currentSchoolId!} canSee={(c) => true} onOpenCase={(id) => setFileId(id)} />
        </TabsContent>

        <TabsContent value="analytics" className="mt-3">
          <AnalyticsTab schoolId={currentSchoolId!} />
        </TabsContent>
      </Tabs>

      <NewCaseDialog
        open={newOpen}
        onOpenChange={setNewOpen}
        board={board}
        schoolId={currentSchoolId!}
        onDone={refreshAll}
      />
      {fileId && (
        <CaseFileDialog
          caseId={fileId}
          board={board}
          schoolId={currentSchoolId!}
          onOpenChange={(open) => { if (!open) setFileId(null); }}
          onChanged={refreshAll}
        />
      )}
    </div>
  );
}

// ── New case dialog — refer a learner ─────────────────────────
function NewCaseDialog({ open, onOpenChange, board, schoolId, onDone }: {
  open: boolean; onOpenChange: (v: boolean) => void; board: BoardData; schoolId: string; onDone: () => void;
}) {
  const [studentQ, setStudentQ] = useState("");
  const [hits, setHits] = useState<StudentHit[]>([]);
  const [student, setStudent] = useState<StudentHit | null>(null);
  const [category, setCategory] = useState("BEHAVIOURAL");
  const [severity, setSeverity] = useState("MEDIUM");
  const [source, setSource] = useState("TEACHER_REFERRAL");
  const [title, setTitle] = useState("");
  const [summary, setSummary] = useState("");
  const [confidential, setConfidential] = useState(true);
  const [assignee, setAssignee] = useState("me");
  const [fuDate, setFuDate] = useState("");
  const [fuNote, setFuNote] = useState("");
  const [busy, setBusy] = useState(false);
  const searching = useRef<string>("");

  useEffect(() => {
    if (!open) { setStudent(null); setStudentQ(""); setHits([]); setTitle(""); setSummary(""); setFuDate(""); setFuNote(""); setCategory("BEHAVIOURAL"); setSeverity("MEDIUM"); setSource("TEACHER_REFERRAL"); setConfidential(true); setAssignee("me"); }
  }, [open]);

  useEffect(() => {
    if (!open) return;
    const t = setTimeout(() => {
      const q = studentQ.trim();
      searching.current = q;
      apiGet<{ rows: StudentHit[] }>(`/api/school/${schoolId}/ops/counsel?action=search&q=${encodeURIComponent(q)}`)
        .then((d) => { if (searching.current === q) setHits(d.rows); })
        .catch(() => {});
    }, 250);
    return () => clearTimeout(t);
  }, [studentQ, open, schoolId]);

  async function submit() {
    if (!student) { toast({ title: "Pick the learner first", variant: "destructive" }); return; }
    if (!title.trim()) { toast({ title: "A short title is required", variant: "destructive" }); return; }
    setBusy(true);
    try {
      const res = await apiPost<{ ok: boolean; row: CaseRow; sms?: { status: string } | null }>(`/api/school/${schoolId}/ops/counsel`, {
        action: "case.create",
        studentId: student.id, category, severity, source,
        title: title.trim(), summary: summary.trim() || null,
        confidential,
        assignedToId: assignee === "me" ? null : assignee,
        followUpAt: fuDate || null, followUpNote: fuNote.trim() || null,
      });
      toast({
        title: `Case ${res.row.caseNo} opened`,
        description: res.sms ? `Assigned counsellor notified by SMS (${res.sms.status})` : `${student.name} — ${CATEGORY_LABELS[category]}`,
      });
      onDone();
      onOpenChange(false);
    } catch (e: any) {
      toast({ title: "Could not open the case", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[92vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><HandHeart className="size-5 text-primary" /> Refer a learner to the G&C unit</DialogTitle>
          <DialogDescription>
            Every teacher can refer. Abuse reports are always confidential and at least HIGH severity — and the assigned counsellor is texted the moment a HIGH/CRITICAL case lands.
          </DialogDescription>
        </DialogHeader>
        <div className="grid gap-4 py-1">
          <div className="grid gap-1.5">
            <Label>Learner</Label>
            {student ? (
              <div className="flex items-center justify-between rounded-lg border bg-muted/40 px-3 py-2">
                <div className="text-sm"><span className="font-medium">{student.name}</span> <span className="text-xs text-muted-foreground">· {student.classroomName ?? "—"} · {student.nyansapoIndex}</span></div>
                <Button size="sm" variant="ghost" className="h-7 gap-1 text-xs" onClick={() => { setStudent(null); setStudentQ(""); }}><X className="size-3" /> Change</Button>
              </div>
            ) : (
              <>
                <div className="relative">
                  <Search className="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                  <Input value={studentQ} onChange={(e) => setStudentQ(e.target.value)} placeholder="Type a name, class list appears…" className="pl-8" />
                </div>
                <div className="rounded-lg border max-h-40 overflow-y-auto">
                  {hits.slice(0, 10).map((h) => (
                    <button key={h.id} type="button" className="w-full text-left px-3 py-2 text-sm hover:bg-muted/60 border-b last:border-0"
                      onClick={() => setStudent(h)}>
                      <span className="font-medium">{h.name}</span>
                      <span className="text-xs text-muted-foreground"> · {h.classroomName ?? "—"} · {h.nyansapoIndex}</span>
                    </button>
                  ))}
                  {!hits.length && <div className="px-3 py-3 text-xs text-muted-foreground">No learners match.</div>}
                </div>
              </>
            )}
          </div>
          <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div className="grid gap-1.5">
              <Label>Category</Label>
              <Select value={category} onValueChange={(v) => { setCategory(v); if (v === "ABUSE_REPORT") { setConfidential(true); setSeverity((s) => (["HIGH", "CRITICAL"].includes(s) ? s : "HIGH")); } }}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{board.catalogues.categories.map((c) => <SelectItem key={c} value={c}>{CATEGORY_LABELS[c] ?? c}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div className="grid gap-1.5">
              <Label>Severity</Label>
              <Select value={severity} onValueChange={setSeverity}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{board.catalogues.severities.map((s) => <SelectItem key={s} value={s}>{s.charAt(0) + s.slice(1).toLowerCase()}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div className="grid gap-1.5">
              <Label>Source</Label>
              <Select value={source} onValueChange={setSource}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{board.catalogues.sources.map((s) => <SelectItem key={s} value={s}>{SOURCE_LABELS[s] ?? s}</SelectItem>)}</SelectContent>
              </Select>
            </div>
          </div>
          <div className="grid gap-1.5">
            <Label>Title <span className="text-destructive">*</span></Label>
            <Input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="e.g. Falling asleep in class — suspected home issue" />
          </div>
          <div className="grid gap-1.5">
            <Label>What have you observed?</Label>
            <Textarea rows={3} value={summary} onChange={(e) => setSummary(e.target.value)} placeholder="Facts only — the counsellor will probe with care." />
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div className="grid gap-1.5">
              <Label>Assigned counsellor</Label>
              <Select value={assignee} onValueChange={setAssignee}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="me">Me (opener)</SelectItem>
                  {board.staff.map((s) => <SelectItem key={s.id} value={s.id}>{s.fullName}{s.phone ? "" : " · no phone"}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="flex items-end gap-2 pb-1">
              <Switch checked={confidential} onCheckedChange={setConfidential} id="conf-sw" />
              <Label htmlFor="conf-sw" className="flex items-center gap-1.5 cursor-pointer">
                {confidential ? <EyeOff className="size-3.5" /> : <Eye className="size-3.5" />}
                Confidential {confidential ? "— only the counsellor, you and the head can see it" : "— visible on the staff board"}
              </Label>
            </div>
          </div>
          <div className="rounded-lg border bg-muted/30 p-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div className="grid gap-1.5">
              <Label className="text-xs flex items-center gap-1"><CalendarClock className="size-3" /> First follow-up due (optional)</Label>
              <Input type="date" value={fuDate} onChange={(e) => setFuDate(e.target.value)} />
            </div>
            <div className="grid gap-1.5">
              <Label className="text-xs">Follow-up note</Label>
              <Input value={fuNote} onChange={(e) => setFuNote(e.target.value)} placeholder="e.g. Check in with the guardian" />
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || !student || !title.trim()}>{busy ? "Opening…" : "Open case"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── Case file dialog — the confidential working file ──────────
function CaseFileDialog({ caseId, board, schoolId, onOpenChange, onChanged }: {
  caseId: string; board: BoardData; schoolId: string; onOpenChange: (v: boolean) => void; onChanged: () => void;
}) {
  const [data, setData] = useState<{ row: CaseRow; sessions: SessionRow[]; followUps: FollowUpRow[]; canManage: boolean } | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [status, setStatus] = useState<string>("");
  const [assignee, setAssignee] = useState<string>("");
  const [referTo, setReferTo] = useState<string>("");
  const [showRefer, setShowRefer] = useState(false);
  const [closeNote, setCloseNote] = useState("");
  const [showClose, setShowClose] = useState(false);
  const [sessMode, setSessMode] = useState("FACE_TO_FACE");
  const [sessDate, setSessDate] = useState("");
  const [sessAttendees, setSessAttendees] = useState("");
  const [sessOutcome, setSessOutcome] = useState("none");
  const [sessNotes, setSessNotes] = useState("");
  const [aiSummary, setAiSummary] = useState("");
  const [aiBusy, setAiBusy] = useState(false);
  const [fuNote, setFuNote] = useState("");
  const [fuDate, setFuDate] = useState("");

  const load = useCallback(() => {
    apiGet<{ row: CaseRow; sessions: SessionRow[]; followUps: FollowUpRow[]; canManage: boolean }>(`/api/school/${schoolId}/ops/counsel?action=case&caseId=${caseId}`)
      .then((d) => { setData(d); setStatus(d.row.status); setAssignee(d.row.assignedToId ?? ""); setReferTo(d.row.referredExternal ?? ""); })
      .catch((e) => { toast({ title: "Could not open the case file", description: e.message, variant: "destructive" }); onOpenChange(false); })
      .finally(() => setLoading(false));
  }, [caseId, schoolId, onOpenChange]);
  useEffect(() => { load(); }, [load]);

  async function act(body: Record<string, unknown>, okMsg: string, reload = true) {
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}/ops/counsel`, body);
      toast({ title: okMsg });
      if (reload) { load(); onChanged(); }
      return true;
    } catch (e: any) {
      toast({ title: "Action failed", description: e.message, variant: "destructive" });
      return false;
    } finally { setBusy(false); }
  }

  async function draftSummary() {
    if (!sessNotes.trim()) { toast({ title: "Write the raw session notes first", variant: "destructive" }); return; }
    setAiBusy(true);
    try {
      const r = await apiPost<{ summary: string; fallback?: boolean }>(`/api/school/${schoolId}/ops/counsel`, { action: "session.ai-draft", caseId, notes: sessNotes });
      setAiSummary(r.summary);
      toast({ title: r.fallback ? "Summary composed (AI offline — deterministic draft)" : "Summary drafted — review before saving" });
    } catch (e: any) {
      toast({ title: "AI draft failed", description: e.message, variant: "destructive" });
    } finally { setAiBusy(false); }
  }

  async function addSession() {
    if (!sessNotes.trim()) { toast({ title: "Session notes are required", variant: "destructive" }); return; }
    const ok = await act({
      action: "session.add", caseId, mode: sessMode,
      heldAt: sessDate || null, attendees: sessAttendees.trim() || null,
      outcome: sessOutcome === "none" ? null : sessOutcome,
      notes: sessNotes.trim(), aiSummary: aiSummary.trim() || null,
    }, "Session logged");
    if (ok) { setSessNotes(""); setAiSummary(""); setSessAttendees(""); setSessOutcome("none"); setSessDate(""); }
  }

  function printFile() {
    if (!data) return;
    const c = data.row;
    const esc = (s: string | null | undefined) => (s ?? "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    const w = window.open("", "_blank", "width=1000,height=900");
    if (!w) return;
    w.document.write(`<!doctype html><html><head><title>G&C case ${esc(c.caseNo)}</title><style>
      body{font-family:Georgia,'Times New Roman',serif;padding:26px;color:#111;position:relative}
      .wm{position:fixed;top:44%;left:50%;transform:translate(-50%,-50%) rotate(-24deg);font-size:72px;color:rgba(0,0,0,.05);font-weight:bold;letter-spacing:8px;pointer-events:none}
      .head{display:flex;justify-content:space-between;border-bottom:3px double #111;padding-bottom:10px}
      .head h1{font-size:17px;margin:0}.head .sub{font-size:11px;color:#444}
      .meta{margin:12px 0;font-size:12px;display:flex;gap:18px;flex-wrap:wrap}
      table{width:100%;border-collapse:collapse;margin-top:10px}
      td{padding:6px;border-bottom:1px solid #ddd;font-size:12px;vertical-align:top}
      td.k{width:26%;font-weight:bold;background:#f7f7f5}
      h2{font-size:13px;margin:16px 0 4px;letter-spacing:.5px}
      .note{white-space:pre-wrap;font-size:12px}
      .foot{margin-top:24px;font-size:11px;color:#555;display:flex;justify-content:space-between}
      @media print{@page{size:A4;margin:14mm}}
    </style></head><body>
      <div class="wm">CONFIDENTIAL</div>
      <div class="head"><div><h1>Guidance &amp; Counselling — Case File</h1><div class="sub">Issued ${new Date().toLocaleString("en-GB")} · ${esc(c.confidential ? "CONFIDENTIAL — restricted circulation" : "Internal")}</div></div><div style="text-align:right"><div class="sub">Case №</div><div style="font-family:monospace;font-size:16px;font-weight:bold">${esc(c.caseNo)}</div></div></div>
      <div class="meta"><span><b>Learner:</b> ${esc(c.studentName)}</span><span><b>Class:</b> ${esc(c.classroomName ?? "—")}</span><span><b>Index:</b> ${esc(c.nyansapoIndex)}</span><span><b>Category:</b> ${esc(CATEGORY_LABELS[c.category] ?? c.category)}</span><span><b>Severity:</b> ${esc(c.severity)}</span><span><b>Status:</b> ${esc(c.status)}</span></div>
      <table>
        <tr><td class="k">Title</td><td>${esc(c.title)}</td></tr>
        <tr><td class="k">Referral summary</td><td class="note">${esc(c.summary ?? "—")}</td></tr>
        <tr><td class="k">Source</td><td>${esc(SOURCE_LABELS[c.source] ?? c.source)} · opened by ${esc(c.openedByName)}</td></tr>
        <tr><td class="k">Assigned counsellor</td><td>${esc(c.assignedToName ?? "— unassigned —")}</td></tr>
        ${c.referredExternal ? `<tr><td class="k">Referred out to</td><td>${esc(c.referredExternal)}</td></tr>` : ""}
        ${c.closedAt ? `<tr><td class="k">Closed</td><td>${esc(fmtDate(c.closedAt))}${c.closedNote ? ` — ${esc(c.closedNote)}` : ""}</td></tr>` : ""}
      </table>
      <h2>SESSIONS (${data.sessions.length})</h2>
      ${data.sessions.map((s) => `<table><tr><td class="k" style="width:22%">${esc(fmtDate(s.heldAt))} · ${esc(MODE_LABELS[s.mode] ?? s.mode)}</td><td class="note">${esc(s.aiSummary ?? s.notes)}${s.outcome ? ` <b>Outcome:</b> ${esc(s.outcome)}` : ""}${s.attendees ? ` <i>(present: ${esc(s.attendees)})</i>` : ""} <span style="color:#777">— ${esc(s.loggedByName)}</span></td></tr></table>`).join("") || "<div style='font-size:12px;color:#666'>No sessions logged yet.</div>"}
      <h2>FOLLOW-UPS (${data.followUps.length})</h2>
      <table>${data.followUps.map((f) => `<tr><td class="k" style="width:22%">${esc(fmtDate(f.dueAt))}${f.overdue && !f.doneAt ? ' <span style="color:#b00">OVERDUE</span>' : ""}</td><td class="note">${esc(f.note)}${f.doneAt ? ` — <b>done</b> ${esc(fmtDate(f.doneAt))}` : ""}</td></tr>`).join("") || "<tr><td>No follow-ups.</td></tr>"}</table>
      <div class="foot"><span>Counsellor signature: ______________________</span><span>G&C coordinator: ______________________</span></div>
    </body></html>`);
    w.document.close();
    setTimeout(() => w.print(), 350);
  }

  const row = data?.row;
  return (
    <Dialog open onOpenChange={onOpenChange}>
      <DialogContent className="max-w-3xl max-h-[92vh] overflow-y-auto">
        <DialogTitle className="sr-only">G&C case file</DialogTitle>
        {loading || !data || !row ? (
          <LoadingBlock label="Opening the case file…" />
        ) : (
          <>
            <DialogHeader>
              <DialogTitle className="flex flex-wrap items-center gap-2">
                <span className="font-mono text-sm text-muted-foreground">{row.caseNo}</span>
                <span>{row.title}</span>
                {row.confidential && <FileLock2 className="size-4 text-muted-foreground" />}
              </DialogTitle>
              <DialogDescription className="flex flex-wrap items-center gap-2">
                <span className="font-medium text-foreground">{row.studentName}</span>
                <span>· {row.classroomName ?? "—"} · {row.nyansapoIndex}</span>
                <Chip label={row.severity} cls={SEVERITY_CLS[row.severity] ?? ""} />
                <Chip label={row.status.replace(/_/g, " ")} cls={STATUS_CLS[row.status] ?? ""} />
                <span>· opened {fmtDate(row.createdAt)} by {row.openedByName} · {SOURCE_LABELS[row.source] ?? row.source}</span>
              </DialogDescription>
            </DialogHeader>

            {row.summary && (
              <div className="rounded-lg border bg-muted/30 p-3 text-sm whitespace-pre-wrap">{row.summary}</div>
            )}

            {/* ── management bar (counsel.manage only) ── */}
            {data.canManage && (
              <div className="rounded-lg border p-3 grid gap-3">
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                  <div className="grid gap-1">
                    <Label className="text-xs">Status</Label>
                    <Select value={status} onValueChange={setStatus}>
                      <SelectTrigger className="h-8"><SelectValue /></SelectTrigger>
                      <SelectContent>{board.catalogues.statuses.map((s) => <SelectItem key={s} value={s}>{s.replace(/_/g, " ").toLowerCase().replace(/\b\w/g, (c) => c.toUpperCase())}</SelectItem>)}</SelectContent>
                    </Select>
                  </div>
                  <div className="grid gap-1">
                    <Label className="text-xs">Counsellor</Label>
                    <Select value={assignee} onValueChange={setAssignee}>
                      <SelectTrigger className="h-8"><SelectValue placeholder="Unassigned" /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="none">Unassigned</SelectItem>
                        {board.staff.map((s) => <SelectItem key={s.id} value={s.id}>{s.fullName}</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="flex items-end gap-2">
                    <Button size="sm" className="h-8 flex-1" disabled={busy || (status === row.status && (assignee || "none") === (row.assignedToId ?? "none"))}
                      onClick={async () => {
                        const ok = await act({ action: "case.update", id: row.id, status, assignedToId: assignee === "none" ? "" : assignee }, "Case updated");
                        if (ok && status === "RESOLVED") setShowClose(true);
                      }}>
                      Save changes
                    </Button>
                    <Button size="sm" variant="outline" className="h-8 gap-1" onClick={() => setShowRefer(!showRefer)}>
                      <Building2 className="size-3.5" /> {row.referredExternal ? "Edit referral" : "Refer out"}
                    </Button>
                  </div>
                </div>
                {showRefer && (
                  <div className="flex gap-2">
                    <Input value={referTo} onChange={(e) => setReferTo(e.target.value)} placeholder="DOVVSU · Social Welfare · CHPS · hospital…" className="h-8" />
                    <Button size="sm" className="h-8" disabled={busy || !referTo.trim()}
                      onClick={async () => {
                        const ok = await act({ action: "case.update", id: row.id, referredExternal: referTo.trim(), status: "REFERRED" }, `Case referred to ${referTo.trim()}`);
                        if (ok) { setShowRefer(false); setStatus("REFERRED"); }
                      }}>
                      Refer
                    </Button>
                  </div>
                )}
                {showClose && status === "RESOLVED" && (
                  <div className="flex gap-2">
                    <Input value={closeNote} onChange={(e) => setCloseNote(e.target.value)} placeholder="Closing note — how was it resolved?" className="h-8" />
                    <Button size="sm" className="h-8" disabled={busy} onClick={() => act({ action: "case.update", id: row.id, closedNote: closeNote.trim() || null }, "Closing note saved").then(() => setShowClose(false))}>
                      Save note
                    </Button>
                  </div>
                )}
                {row.confidential && row.category !== "ABUSE_REPORT" && (
                  <div className="text-[11px] text-muted-foreground flex items-center gap-1.5">
                    <FileLock2 className="size-3" /> Confidential — visible to {row.assignedToName ?? "the counsellor"}, {row.openedByName} and counsel managers only.
                  </div>
                )}
              </div>
            )}

            {/* ── sessions ── */}
            <div className="rounded-lg border">
              <div className="px-3 py-2 border-b text-xs font-semibold uppercase tracking-wide text-muted-foreground flex items-center justify-between">
                <span className="flex items-center gap-1.5"><ClipboardList className="size-3.5" /> Sessions ({data.sessions.length})</span>
                <Button size="sm" variant="outline" className="h-7 gap-1 text-xs" onClick={printFile}><Printer className="size-3" /> Print case file</Button>
              </div>
              <div className="max-h-64 overflow-y-auto">
                {data.sessions.map((s) => (
                  <div key={s.id} className="px-3 py-2.5 border-b last:border-0">
                    <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                      <span className="font-medium text-foreground">{fmtDate(s.heldAt)}</span>
                      <Badge variant="outline" className="text-[10px]">{MODE_LABELS[s.mode] ?? s.mode}</Badge>
                      {s.outcome && <Chip label={s.outcome.replace(/_/g, " ").toLowerCase()} cls={OUTCOME_CLS[s.outcome] ?? ""} />}
                      {s.attendees && <span>· present: {s.attendees}</span>}
                      <span className="ml-auto">logged by {s.loggedByName}</span>
                    </div>
                    {s.aiSummary && <div className="mt-1.5 text-sm">{s.aiSummary}</div>}
                    <details className="mt-1">
                      <summary className="text-[11px] text-muted-foreground cursor-pointer select-none">{s.aiSummary ? "Raw notes" : "Notes"}</summary>
                      <div className="mt-1 text-xs text-muted-foreground whitespace-pre-wrap">{s.notes}</div>
                    </details>
                  </div>
                ))}
                {!data.sessions.length && <div className="px-3 py-4 text-xs text-muted-foreground">No sessions yet — log the first conversation below.</div>}
              </div>
              <div className="p-3 border-t bg-muted/20 grid gap-2">
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                  <div className="grid gap-1">
                    <Label className="text-[11px]">Mode</Label>
                    <Select value={sessMode} onValueChange={setSessMode}>
                      <SelectTrigger className="h-8"><SelectValue /></SelectTrigger>
                      <SelectContent>{board.catalogues.modes.map((m) => <SelectItem key={m} value={m}>{MODE_LABELS[m] ?? m}</SelectItem>)}</SelectContent>
                    </Select>
                  </div>
                  <div className="grid gap-1">
                    <Label className="text-[11px]">Held on</Label>
                    <Input type="date" value={sessDate} onChange={(e) => setSessDate(e.target.value)} max={new Date().toISOString().slice(0, 10)} className="h-8" />
                  </div>
                  <div className="grid gap-1">
                    <Label className="text-[11px]">Present</Label>
                    <Input value={sessAttendees} onChange={(e) => setSessAttendees(e.target.value)} placeholder="learner + mother" className="h-8" />
                  </div>
                  <div className="grid gap-1">
                    <Label className="text-[11px]">Outcome</Label>
                    <Select value={sessOutcome} onValueChange={setSessOutcome}>
                      <SelectTrigger className="h-8"><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="none">To be seen</SelectItem>
                        {board.catalogues.outcomes.map((o) => <SelectItem key={o} value={o}>{o.replace(/_/g, " ").toLowerCase().replace(/\b\w/g, (c) => c.toUpperCase())}</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                </div>
                <Textarea rows={3} value={sessNotes} onChange={(e) => setSessNotes(e.target.value)} placeholder="Raw notes of the conversation — facts, quotes, observations…" />
                {aiSummary && (
                  <div className="rounded-md border border-primary/30 bg-primary/5 p-2.5 text-sm">
                    <div className="text-[10px] font-semibold uppercase tracking-wide text-primary mb-1 flex items-center gap-1"><Sparkles className="size-3" /> AI summary (editable)</div>
                    <Textarea rows={2} value={aiSummary} onChange={(e) => setAiSummary(e.target.value)} className="bg-transparent border-0 p-0 focus-visible:ring-0 text-sm" />
                  </div>
                )}
                <div className="flex gap-2">
                  <Button size="sm" variant="outline" className="gap-1" disabled={aiBusy || !sessNotes.trim()} onClick={draftSummary}>
                    <Sparkles className="size-3.5" /> {aiBusy ? "Drafting…" : "Draft with AI"}
                  </Button>
                  <Button size="sm" className="gap-1" disabled={busy || !sessNotes.trim()} onClick={addSession}>
                    <Plus className="size-3.5" /> Log session
                  </Button>
                </div>
              </div>
            </div>

            {/* ── follow-ups ── */}
            <div className="rounded-lg border">
              <div className="px-3 py-2 border-b text-xs font-semibold uppercase tracking-wide text-muted-foreground flex items-center gap-1.5">
                <CalendarClock className="size-3.5" /> Follow-ups ({data.followUps.filter((f) => !f.doneAt).length} open)
              </div>
              <div className="max-h-48 overflow-y-auto">
                {data.followUps.map((f) => (
                  <div key={f.id} className="px-3 py-2 border-b last:border-0 flex items-start gap-2 text-sm">
                    <div className={`rounded-md px-2 py-1 text-[11px] font-medium tabular-nums shrink-0 ${f.doneAt ? "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300" : f.overdue ? "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300" : "bg-muted"}`}>
                      {fmtDate(f.dueAt)}
                    </div>
                    <div className="min-w-0 flex-1">
                      <div>{f.note}</div>
                      {f.doneAt && <div className="text-[11px] text-emerald-700 dark:text-emerald-400">Done {fmtDate(f.doneAt)}{f.doneNote ? ` — ${f.doneNote}` : ""}</div>}
                    </div>
                    {!f.doneAt && data.canManage && (
                      <div className="flex gap-1 shrink-0">
                        <Button size="sm" variant="outline" className="h-7 gap-1 text-xs" disabled={busy}
                          onClick={() => act({ action: "followup.remind", id: f.id }, "Reminder SMS sent to the counsellor")}>
                          <BellRing className="size-3" /> Remind
                        </Button>
                        <Button size="sm" variant="outline" className="h-7 gap-1 text-xs" disabled={busy}
                          onClick={() => act({ action: "followup.complete", id: f.id }, "Follow-up completed")}>
                          <CheckCircle2 className="size-3" /> Done
                        </Button>
                      </div>
                    )}
                  </div>
                ))}
                {!data.followUps.length && <div className="px-3 py-4 text-xs text-muted-foreground">No follow-ups yet.</div>}
              </div>
              {data.canManage && (
                <div className="p-3 border-t bg-muted/20 flex flex-col sm:flex-row gap-2">
                  <Input value={fuNote} onChange={(e) => setFuNote(e.target.value)} placeholder="e.g. Speak with the guardian about school fees" className="h-8 flex-1" />
                  <Input type="date" value={fuDate} onChange={(e) => setFuDate(e.target.value)} className="h-8 sm:w-40" />
                  <Button size="sm" className="h-8" disabled={busy || !fuNote.trim() || !fuDate}
                    onClick={async () => {
                      const ok = await act({ action: "followup.create", caseId, dueAt: fuDate, note: fuNote.trim() }, "Follow-up added");
                      if (ok) { setFuNote(""); setFuDate(""); }
                    }}>
                    <Plus className="size-3.5" /> Add
                  </Button>
                </div>
              )}
            </div>
          </>
        )}
      </DialogContent>
    </Dialog>
  );
}

// ── Follow-ups tab — the promise list ─────────────────────────
function FollowUpsTab({ schoolId, onOpenCase }: { schoolId: string; onOpenCase: (id: string) => void }) {
  const [scope, setScope] = useState("week");
  const [rows, setRows] = useState<{ id: string; dueAt: string; note: string; overdue: boolean; remindedAt: string | null; case: CaseRow }[]>([]);
  const [loading, setLoading] = useState(true);
  const [busyId, setBusyId] = useState<string | null>(null);

  const load = useCallback(() => {
    apiGet<{ rows: typeof rows }>(`/api/school/${schoolId}/ops/counsel?action=followups&scope=${scope}`)
      .then((d) => setRows(d.rows))
      .catch((e) => toast({ title: "Could not load follow-ups", description: e.message, variant: "destructive" }))
      .finally(() => setLoading(false));
  }, [schoolId, scope]);
  useEffect(() => { load(); }, [load]);

  async function act(id: string, body: Record<string, unknown>, okMsg: string) {
    setBusyId(id);
    try {
      await apiPost(`/api/school/${schoolId}/ops/counsel`, body);
      toast({ title: okMsg });
      load();
    } catch (e: any) {
      toast({ title: "Action failed", description: e.message, variant: "destructive" });
    } finally { setBusyId(null); }
  }

  if (loading) return <LoadingBlock label="Loading follow-ups…" />;

  return (
    <div className="rounded-xl border bg-card">
      <div className="p-3 border-b flex flex-wrap items-center gap-2">
        <FilterSelect label="Scope" value={scope} onChange={setScope}
          options={[
            { value: "week", label: "Due this week" },
            { value: "overdue", label: "Overdue only" },
            { value: "all", label: "All open" },
          ]} />
        <Badge variant="secondary" className="text-[10px] tabular-nums ml-auto">{rows.length} open</Badge>
      </div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead className="w-28">Due</TableHead>
            <TableHead>Promise</TableHead>
            <TableHead>Case</TableHead>
            <TableHead className="hidden lg:table-cell">Counsellor</TableHead>
            <TableHead className="w-44" />
          </TableRow>
        </TableHeader>
        <TableBody>
          {rows.map((f) => (
            <TableRow key={f.id} className={f.overdue ? "bg-red-50/60 dark:bg-red-950/20" : ""}>
              <TableCell>
                <div className={`inline-block rounded-md px-2 py-1 text-[11px] font-medium tabular-nums ${f.overdue ? "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300" : "bg-muted"}`}>
                  {fmtDate(f.dueAt)}
                </div>
                {f.overdue && <div className="text-[10px] text-destructive mt-1">overdue</div>}
              </TableCell>
              <TableCell className="text-sm">{f.note}</TableCell>
              <TableCell>
                <button type="button" className="text-left hover:underline" onClick={() => onOpenCase(f.case.id)}>
                  <span className="font-mono text-xs text-muted-foreground">{f.case.caseNo}</span>
                  <div className="text-sm font-medium">{f.case.studentName}</div>
                </button>
              </TableCell>
              <TableCell className="hidden lg:table-cell text-xs">{f.case.assignedToName ?? <span className="text-muted-foreground">unassigned</span>}</TableCell>
              <TableCell>
                <div className="flex gap-1 justify-end">
                  <Button size="sm" variant="outline" className="h-7 gap-1 text-xs" disabled={busyId === f.id}
                    onClick={() => act(f.id, { action: "followup.remind", id: f.id }, "Reminder SMS sent")}>
                    <PhoneCall className="size-3" /> Remind
                  </Button>
                  <Button size="sm" variant="outline" className="h-7 gap-1 text-xs" disabled={busyId === f.id}
                    onClick={() => act(f.id, { action: "followup.complete", id: f.id }, "Follow-up completed")}>
                    <CheckCircle2 className="size-3" /> Done
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
      {!rows.length && (
        <EmptyState icon={<CalendarClock className="size-8" />} title="Nothing due" hint={scope === "overdue" ? "No overdue promises — well kept." : "No open follow-ups in this window."} />
      )}
    </div>
  );
}

// ── Analytics tab — the termly G&C report ─────────────────────
function AnalyticsTab({ schoolId }: { schoolId: string }) {
  const [data, setData] = useState<AnalyticsData | null>(null);
  useEffect(() => {
    apiGet<AnalyticsData>(`/api/school/${schoolId}/ops/counsel?action=analytics`)
      .then(setData)
      .catch((e) => toast({ title: "Could not load analytics", description: e.message, variant: "destructive" }));
  }, [schoolId]);
  if (!data) return <LoadingBlock label="Crunching the six-month window…" />;

  const maxCat = Math.max(1, ...data.categories.map((c) => c.count));
  const maxTrend = Math.max(1, ...data.trend.map((t) => Math.max(t.opened, t.resolved)));
  const sevOrder = ["LOW", "MEDIUM", "HIGH", "CRITICAL"] as const;

  return (
    <div className="grid gap-4 lg:grid-cols-2">
      <div className="rounded-xl border bg-card p-4 lg:col-span-2">
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
          {[
            { label: "Cases (6 mo)", value: data.totals.cases },
            { label: "Active", value: data.totals.open },
            { label: "Resolved", value: data.totals.resolved },
            { label: "Referred out", value: data.totals.referred },
            { label: "Sessions logged", value: data.totals.sessions },
            { label: "Avg days to resolve", value: data.totals.avgDaysToResolve ?? "—" },
          ].map((s) => (
            <div key={s.label} className="rounded-lg border bg-muted/30 p-3">
              <div className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">{s.label}</div>
              <div className="mt-1 text-xl font-bold tabular-nums">{s.value}</div>
            </div>
          ))}
        </div>
      </div>

      <div className="rounded-xl border bg-card p-4">
        <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-3">Cases by category</div>
        {data.categories.length ? (
          <div className="grid gap-2">
            {data.categories.map((c) => (
              <div key={c.name} className="flex items-center gap-2">
                <span className="text-xs w-32 shrink-0 truncate">{CATEGORY_LABELS[c.name] ?? c.name}</span>
                <div className="h-3 rounded-full bg-muted flex-1 overflow-hidden">
                  <div className="h-full rounded-full bg-primary/70" style={{ width: `${(c.count / maxCat) * 100}%` }} />
                </div>
                <span className="text-xs font-semibold tabular-nums w-6 text-right">{c.count}</span>
              </div>
            ))}
          </div>
        ) : <div className="text-xs text-muted-foreground">No cases in the window.</div>}
        <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mt-5 mb-2">Severity mix</div>
        <div className="flex gap-2 flex-wrap">
          {sevOrder.map((s) => (
            <div key={s} className="flex items-center gap-1.5">
              <Chip label={`${s.charAt(0) + s.slice(1).toLowerCase()} ${data.bySeverity[s] ?? 0}`} cls={SEVERITY_CLS[s]} />
            </div>
          ))}
        </div>
      </div>

      <div className="rounded-xl border bg-card p-4">
        <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-3">Opened vs resolved — six months</div>
        <div className="flex items-end gap-3 h-40">
          {data.trend.map((t) => (
            <div key={t.month} className="flex-1 flex flex-col items-center gap-1">
              <div className="w-full flex items-end justify-center gap-1 h-32">
                <div className="w-1/2 rounded-t bg-primary/70" style={{ height: `${(t.opened / maxTrend) * 100}%`, minHeight: t.opened ? 4 : 0 }} title={`${t.opened} opened`} />
                <div className="w-1/2 rounded-t bg-emerald-500/70" style={{ height: `${(t.resolved / maxTrend) * 100}%`, minHeight: t.resolved ? 4 : 0 }} title={`${t.resolved} resolved`} />
              </div>
              <span className="text-[10px] text-muted-foreground">{t.month}</span>
            </div>
          ))}
        </div>
        <div className="flex gap-4 mt-2 text-[10px] text-muted-foreground">
          <span className="flex items-center gap-1"><span className="size-2 rounded-sm bg-primary/70 inline-block" /> opened</span>
          <span className="flex items-center gap-1"><span className="size-2 rounded-sm bg-emerald-500/70 inline-block" /> resolved</span>
        </div>
      </div>

      <div className="rounded-xl border bg-card p-4">
        <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-3">Referred out to external services</div>
        {data.referredOut.length ? (
          <div className="grid gap-2">
            {data.referredOut.map((r) => (
              <div key={r.caseNo} className="flex items-center justify-between text-sm border-b last:border-0 pb-2 last:pb-0">
                <div>
                  <span className="font-mono text-xs text-muted-foreground">{r.caseNo}</span>
                  <span className="ml-2">{r.title}</span>
                  <div className="text-[11px] text-muted-foreground">{r.studentName} · {fmtDate(r.at)}</div>
                </div>
                <Badge variant="outline" className="shrink-0">{r.to}</Badge>
              </div>
            ))}
          </div>
        ) : <div className="text-xs text-muted-foreground">No external referrals in the window — every case is being handled in-house.</div>}
      </div>

      <div className="rounded-xl border bg-card p-4">
        <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-3">Counsellor workload (sessions logged)</div>
        {data.counsellors.length ? (
          <div className="grid gap-2">
            {data.counsellors.map((c) => (
              <div key={c.name} className="flex items-center justify-between text-sm border-b last:border-0 pb-2 last:pb-0">
                <span>{c.name}</span>
                <Badge variant="secondary" className="tabular-nums">{c.count}</Badge>
              </div>
            ))}
          </div>
        ) : <div className="text-xs text-muted-foreground">No sessions logged in the window.</div>}
      </div>
    </div>
  );
}

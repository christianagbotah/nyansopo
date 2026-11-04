"use client";
// ── Curriculum & Lesson Notes (iEduPro lesson-note lifecycle parity)
//    templates → weekly notes → HOD review → endorse / return →
//    scheme-of-learning coverage + study material + completion dashboard.
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { ModuleHeader, LoadingBlock, EmptyState, FilterBar, FormGrid, FormField, downloadCsv } from "@/components/ui-helpers";
import { cn } from "@/lib/utils";
import { fmtDate } from "@/lib/format";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Checkbox } from "@/components/ui/checkbox";
import { Progress } from "@/components/ui/progress";
import { Switch } from "@/components/ui/switch";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useToast } from "@/hooks/use-toast";
import { CURRICULA, CurriculumKey, frameworkGrade } from "@/lib/curricula";
import {
  NotebookPen, BookMarked, ListChecks, LibraryBig, ClipboardCheck, Plus, Pencil, Copy, Trash2,
  Printer, FileText, CheckCircle2, Undo2, Eye, ExternalLink, FileCheck2, Wand2,
} from "lucide-react";

// ── types ──────────────────────────────────────────────────────
type Review = { id: string; action: string; actorName: string; comment: string | null; createdAt: string };
type Note = {
  id: string; staffId: string; classroomId: string; subjectId: string; termId: string; templateId: string | null;
  title: string; description: string | null; weekNumber: number; lessonDate: string;
  objectives: string; activities: string; content: string;
  strandId: string | null; subStrandId: string | null; contentStandardId: string | null;
  indicatorIds: string | null; coreCompetencies: string | null; tlm: string | null; assessment: string | null;
  strand: { id: string; name: string; code: string | null; curriculum: string; subjectName: string; gradeLevel: string } | null;
  subStrand: { id: string; name: string; code: string | null } | null;
  contentStandard: { id: string; code: string | null; statement: string } | null;
  status: string; feedback: string | null; version: number; sourceNoteId: string | null;
  submittedAt: string | null; reviewedAt: string | null; reviewedByName: string | null;
  createdAt: string; updatedAt: string;
  staff: { fullName: string }; classroom: { name: string; gradeLevel: string }; subject: { name: string };
  reviews: Review[];
};
type Template = {
  id: string; name: string; subjectId: string | null; subject: string | null;
  classroomId: string | null; classroom: string | null; strand: string | null; subStrand: string | null;
  contentStandard: string | null; description: string | null; objectives: string; activities: string; content: string;
};
type StaffStat = {
  staffId: string; name: string; staffCode: string | null; classSubjectPairs: number;
  notes: number; endorsed: number; pending: number; revisions: number;
  weeksCovered: number; weeksElapsed: number; compliancePct: number;
  syllabusTotal: number; syllabusCompleted: number; syllabusPct: number | null;
};
type Overview = {
  canReview: boolean; myStaffId: string | null;
  term: { id: string; name: string; weeksElapsed: number } | null;
  classrooms: { id: string; name: string; gradeLevel: string; classTeacherId: string | null }[];
  subjects: { id: string; name: string; code: string }[];
  curricula: string[];
  terms: { id: string; name: string; isCurrent: boolean }[];
  notes: Note[]; templates: Template[];
  pendingReview: number;
  statusCounts: { DRAFT: number; PENDING: number; ENDORSED: number; REVISION_REQUIRED: number };
  staffStats: StaffStat[];
};
type Topic = {
  id: string; subjectId: string; subject: string; weekNumber: number; strand: string | null; subStrand: string | null;
  contentStandard: string | null; indicator: string | null; topic: string; completed: boolean; completedAt: string | null;
};
type Material = {
  id: string; classroomId: string; classroom: string; subjectId: string | null; subject: string | null;
  title: string; description: string | null; linkUrl: string | null; body: string | null;
  authorStaffId: string; author: string; createdAt: string;
};
type FrameworkNode = {
  id: string; code: string | null; name: string; sortOrder: number;
  subStrands?: { id: string; code: string | null; name: string; standards?: {
    id: string; code: string | null; statement: string; indicators?: { id: string; code: string | null; statement: string }[];
  }[] }[];
};
type FrameworkMeta = {
  enabled: string[]; primary: string;
  coverage: { curriculum: string; subjectName: string; gradeLevel: string }[];
  subjects: { id: string; name: string; curriculum: string | null }[];
};

const WEEKS = Array.from({ length: 15 }, (_, i) => i + 1);
const STATUS_BADGE: Record<string, { label: string; cls: string }> = {
  DRAFT: { label: "Draft", cls: "bg-secondary text-secondary-foreground" },
  PENDING: { label: "Awaiting review", cls: "bg-amber-600" },
  ENDORSED: { label: "Endorsed", cls: "bg-emerald-600" },
  REVISION_REQUIRED: { label: "Returned for revision", cls: "bg-destructive" },
};
const ACTION_LABEL: Record<string, string> = {
  CREATE: "Created", SUBMIT: "Submitted for review", UPDATE: "Updated",
  ENDORSE: "Endorsed", REQUEST_REVISION: "Returned for revision", RESUBMIT: "Resubmitted after revision",
};
const today = () => new Date().toISOString().slice(0, 10);

export function Curriculum() {
  const { currentSchoolId, navTarget } = useStore();
  const { toast } = useToast();
  const [data, setData] = useState<Overview | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  // tab + deep links
  const [tab, setTab] = useState("notes");
  const [appliedTarget, setAppliedTarget] = useState<unknown>(null);

  // note editor
  const [noteForm, setNoteForm] = useState<(Partial<Note> & { submit?: boolean; sourceNoteId?: string | null }) | null>(null);
  const [viewing, setViewing] = useState<Note | null>(null);
  const [reviewing, setReviewing] = useState<Note | null>(null);

  // template editor
  const [templateForm, setTemplateForm] = useState<Partial<Template> | null>(null);

  // syllabus tab
  const [syllabusClass, setSyllabusClass] = useState<string>("");
  const [syllabusSubject, setSyllabusSubject] = useState<string>("all");
  const [topics, setTopics] = useState<Topic[] | null>(null);
  const [coverage, setCoverage] = useState<{ total: number; completed: number; pct: number } | null>(null);
  const [topicForm, setTopicForm] = useState<(Partial<Topic> & { classroomId?: string }) | null>(null);

  // materials
  const [materials, setMaterials] = useState<Material[] | null>(null);
  const [materialForm, setMaterialForm] = useState<Partial<Material> | null>(null);

  // framework manager (canEdit mirrors curriculum-review rights)
  const fwCanEdit = data?.canReview ?? false;

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<Overview>(`/api/school/${currentSchoolId}/ops/curriculum`)
      .then((d) => { setData(d); if (!syllabusClass && d.classrooms[0]) setSyllabusClass(d.classrooms[0].id); })
      .catch((e) => setErr(e.message));
  }, [currentSchoolId, syllabusClass]);

  const loadSyllabus = useCallback(() => {
    if (!currentSchoolId || !syllabusClass) return;
    setTopics(null);
    const q = new URLSearchParams({ tab: "syllabus", classroomId: syllabusClass, ...(syllabusSubject !== "all" ? { subjectId: syllabusSubject } : {}) });
    apiGet<{ topics: Topic[]; coverage: { total: number; completed: number; pct: number } }>(`/api/school/${currentSchoolId}/ops/curriculum?${q}`)
      .then((d) => { setTopics(d.topics); setCoverage(d.coverage); })
      .catch((e) => { toast({ title: "Could not load scheme of learning", description: e.message, variant: "destructive" }); setTopics([]); });
  }, [currentSchoolId, syllabusClass, syllabusSubject, toast]);

  const loadMaterials = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<{ materials: Material[] }>(`/api/school/${currentSchoolId}/ops/curriculum?tab=materials`)
      .then((d) => setMaterials(d.materials))
      .catch((e) => { toast({ title: "Could not load study material", description: e.message, variant: "destructive" }); setMaterials([]); });
  }, [currentSchoolId, toast]);

  useEffect(() => { load(); }, [load]);
  useEffect(() => { if (tab === "syllabus") loadSyllabus(); }, [tab, loadSyllabus]);
  useEffect(() => { if (tab === "materials" && materials === null) loadMaterials(); }, [tab, materials, loadMaterials]);

  // navTarget deep links (curriculum: notes | review | templates | syllabus | materials | completion)
  useEffect(() => {
    if (navTarget !== appliedTarget) {
      setAppliedTarget(navTarget);
      if (navTarget?.tab && ["notes", "review", "templates", "syllabus", "materials", "completion", "framework"].includes(navTarget.tab)) setTab(navTarget.tab);
      if (navTarget?.classroomId) { setSyllabusClass(navTarget.classroomId); setTab((t) => (t === "notes" ? "syllabus" : t)); }
    }
  }, [navTarget, appliedTarget]);

  async function post(payload: Record<string, unknown>, okMsg: string) {
    if (!currentSchoolId) return false;
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/curriculum`, payload);
      toast({ title: okMsg });
      load();
      if (tab === "syllabus") loadSyllabus();
      if (tab === "materials" || payload.action?.toString().startsWith("material")) loadMaterials();
      return true;
    } catch (e: any) {
      toast({ title: "Action failed", description: e.message, variant: "destructive" });
      return false;
    } finally { setBusy(false); }
  }

  if (err) return <div className="text-destructive text-sm">{err}</div>;
  if (!data) return <LoadingBlock label="Loading curriculum workspace…" />;
  const myStats = data.staffStats.find((s) => s.staffId === data.myStaffId);

  return (
    <div>
      <ModuleHeader
        title="Curriculum & Lesson Notes"
        description={`Multi-curriculum lesson-note lifecycle — draft from a template, align to the strand / content standard / indicator, submit weekly, HOD endorses or returns${data.term ? ` · ${data.term.name} (week ${data.term.weeksElapsed} of term)` : ""}.`}
        actions={
          <div className="flex gap-2">
            {data.canReview && <Badge className="bg-primary gap-1"><FileCheck2 className="size-3" /> Reviewer</Badge>}
            <Button size="sm" className="gap-1.5" onClick={() => setNoteForm({ weekNumber: data.term?.weeksElapsed ?? 1, lessonDate: today(), status: "PENDING" })}>
              <Plus className="size-4" /> New lesson note
            </Button>
          </div>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <Tile icon={<FileText className="size-4" />} label="My notes" value={data.statusCounts.DRAFT + data.statusCounts.PENDING + data.statusCounts.ENDORSED + data.statusCounts.REVISION_REQUIRED} sub={`${data.statusCounts.ENDORSED} endorsed · ${data.statusCounts.PENDING} awaiting`} />
        <Tile icon={<Undo2 className="size-4" />} label="Returned to me" value={data.statusCounts.REVISION_REQUIRED} sub="revision requested" tone={data.statusCounts.REVISION_REQUIRED > 0 ? "warn" : undefined} />
        {data.canReview ? (
          <Tile icon={<ClipboardCheck className="size-4" />} label="Awaiting my review" value={data.pendingReview} sub="submitted by teachers" tone={data.pendingReview > 0 ? "warn" : undefined} />
        ) : (
          <Tile icon={<BookMarked className="size-4" />} label="Templates" value={data.templates.length} sub="reusable GES skeletons" />
        )}
        <Tile icon={<ListChecks className="size-4" />} label="My syllabus coverage" value={myStats?.syllabusPct != null ? `${myStats.syllabusPct}%` : "—"} sub={myStats ? `${myStats.syllabusCompleted}/${myStats.syllabusTotal} topics taught` : "scheme of learning"} tone={myStats?.syllabusPct != null && myStats.syllabusPct >= 70 ? "good" : undefined} />
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex-wrap h-auto">
          <TabsTrigger value="notes">Lesson notes</TabsTrigger>
          {data.canReview && <TabsTrigger value="review" className="gap-1.5">Review queue {data.pendingReview ? <span className="inline-flex items-center justify-center rounded-full bg-primary px-1.5 min-w-4 h-4 text-[10px] font-bold text-primary-foreground">{data.pendingReview}</span> : null}</TabsTrigger>}
          <TabsTrigger value="templates">Templates</TabsTrigger>
          <TabsTrigger value="syllabus">Scheme of learning</TabsTrigger>
          <TabsTrigger value="materials">Study material</TabsTrigger>
          <TabsTrigger value="completion">Completion</TabsTrigger>
          <TabsTrigger value="framework" className="gap-1.5"><BookMarked className="size-4" /> Framework</TabsTrigger>
        </TabsList>

        <NotesTab data={data} onEdit={(n) => setNoteForm({ ...n })} onView={setViewing} onCopy={(n) => setNoteForm({ classroomId: n.classroomId, subjectId: n.subjectId, termId: n.termId, weekNumber: Math.min(15, n.weekNumber + 1), title: n.title, objectives: n.objectives, activities: n.activities, content: n.content, description: n.description, strandId: n.strandId, subStrandId: n.subStrandId, contentStandardId: n.contentStandardId, indicatorIds: n.indicatorIds, coreCompetencies: n.coreCompetencies, tlm: n.tlm, assessment: n.assessment, strand: n.strand, subStrand: n.subStrand, contentStandard: n.contentStandard, sourceNoteId: n.id, status: "PENDING" })} onDelete={(n) => post({ action: "note.delete", id: n.id }, "Lesson note deleted")} busy={busy} />
        {data.canReview && <ReviewTab data={data} onView={setViewing} onReview={setReviewing} />}
        <TemplatesTab data={data} onEdit={(t) => setTemplateForm({ ...t })} onNew={() => setTemplateForm({})} onDelete={(t) => post({ action: "template.delete", id: t.id }, "Template deleted")} busy={busy} onUseTemplate={(t) => setNoteForm({ templateId: t.id, title: t.name, objectives: t.objectives, activities: t.activities, content: t.content, description: t.description, weekNumber: data.term?.weeksElapsed ?? 1, lessonDate: today(), status: "PENDING" })} />
        <SyllabusTab data={data} syllabusClass={syllabusClass} setSyllabusClass={setSyllabusClass} syllabusSubject={syllabusSubject} setSyllabusSubject={setSyllabusSubject} topics={topics} coverage={coverage} onEdit={(t) => setTopicForm({ ...t })} onNew={(week) => setTopicForm({ weekNumber: week, classroomId: syllabusClass, subjectId: syllabusSubject !== "all" ? syllabusSubject : undefined })} onToggle={async (t, completed) => { await post({ action: "syllabus.save", id: t.id, classroomId: syllabusClass, subjectId: t.subjectId, weekNumber: t.weekNumber, topic: t.topic, completed }, completed ? "Topic marked as taught" : "Topic reopened"); }} onDelete={(t) => post({ action: "syllabus.delete", id: t.id }, "Topic removed")} busy={busy} />
        <MaterialsTab data={data} materials={materials} onNew={() => setMaterialForm({})} onEdit={(m) => setMaterialForm({ ...m })} onDelete={(m) => post({ action: "material.delete", id: m.id }, "Material removed")} busy={busy} />
        <CompletionTab data={data} />
        <FrameworkTab schoolId={currentSchoolId!} data={data} canEdit={fwCanEdit} />
      </Tabs>

      {/* ── note editor ── */}
      <NoteDialog
        data={data} form={noteForm} busy={busy}
        onClose={() => setNoteForm(null)}
        onSave={async (payload, submit) => {
          const ok = await post({ action: "note.save", ...payload, status: submit ? "PENDING" : "DRAFT" }, submit ? "Lesson note submitted for review" : "Draft saved");
          if (ok) setNoteForm(null);
        }}
      />

      {/* ── note viewer + print ── */}
      <NoteViewDialog note={viewing} onClose={() => setViewing(null)} />

      {/* ── review decision ── */}
      <ReviewDialog
        note={reviewing} busy={busy} onClose={() => setReviewing(null)}
        onDecide={async (decision, comment) => {
          const ok = await post({ action: "note.review", id: reviewing!.id, decision, comment }, decision === "ENDORSE" ? "Lesson note endorsed" : "Lesson note returned for revision");
          if (ok) setReviewing(null);
        }}
      />

      {/* ── template editor ── */}
      <TemplateDialog
        data={data} form={templateForm} busy={busy}
        onClose={() => setTemplateForm(null)}
        onSave={async (payload) => {
          const ok = await post({ action: "template.save", ...(templateForm?.id ? { id: templateForm.id } : {}), ...payload }, "Template saved");
          if (ok) setTemplateForm(null);
        }}
      />

      {/* ── syllabus topic editor ── */}
      <TopicDialog
        data={data} form={topicForm} busy={busy}
        onClose={() => setTopicForm(null)}
        onSave={async (payload) => {
          const ok = await post({ action: "syllabus.save", ...(topicForm?.id ? { id: topicForm.id } : {}), classroomId: topicForm?.classroomId ?? syllabusClass, ...payload }, "Topic saved");
          if (ok) { setTopicForm(null); loadSyllabus(); }
        }}
      />

      {/* ── study material editor ── */}
      <MaterialDialog
        data={data} form={materialForm} busy={busy}
        onClose={() => setMaterialForm(null)}
        onSave={async (payload) => {
          const ok = await post({ action: "material.save", ...(materialForm?.id ? { id: materialForm.id } : {}), ...payload }, "Study material shared");
          if (ok) { setMaterialForm(null); loadMaterials(); }
        }}
      />
    </div>
  );
}

// ══════════════════════════════════════════════════════════════
// Tabs
// ══════════════════════════════════════════════════════════════

function NotesTab({ data, onEdit, onView, onCopy, onDelete, busy }: {
  data: Overview;
  onEdit: (n: Note) => void; onView: (n: Note) => void; onCopy: (n: Note) => void; onDelete: (n: Note) => void; busy: boolean;
}) {
  const [q, setQ] = useState("");
  const [fClass, setFClass] = useState("all");
  const [fSubject, setFSubject] = useState("all");
  const [fStatus, setFStatus] = useState("all");
  const [fWeek, setFWeek] = useState("all");

  const rows = useMemo(() => data.notes.filter((n) => {
    if (q && !`${n.title} ${n.classroom.name} ${n.subject.name} ${n.staff.fullName}`.toLowerCase().includes(q.toLowerCase())) return false;
    if (fClass !== "all" && n.classroomId !== fClass) return false;
    if (fSubject !== "all" && n.subjectId !== fSubject) return false;
    if (fStatus !== "all" && n.status !== fStatus) return false;
    if (fWeek !== "all" && String(n.weekNumber) !== fWeek) return false;
    return true;
  }), [data.notes, q, fClass, fSubject, fStatus, fWeek]);
  const active = !!q || fClass !== "all" || fSubject !== "all" || fStatus !== "all" || fWeek !== "all";

  return (
    <TabsContent value="notes" className="mt-4 space-y-3">
      <FilterBar active={active} count={rows.length} total={data.notes.length} onClear={() => { setQ(""); setFClass("all"); setFSubject("all"); setFStatus("all"); setFWeek("all"); }}>
        <Input placeholder="Search title, class, teacher…" value={q} onChange={(e) => setQ(e.target.value)} className="h-8 w-56" />
        <Select value={fClass} onValueChange={setFClass}><SelectTrigger className="h-8 w-40"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">All classes</SelectItem>{data.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent></Select>
        <Select value={fSubject} onValueChange={setFSubject}><SelectTrigger className="h-8 w-40"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">All subjects</SelectItem>{data.subjects.map((s) => <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>)}</SelectContent></Select>
        <Select value={fStatus} onValueChange={setFStatus}><SelectTrigger className="h-8 w-40"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">Any status</SelectItem>{Object.entries(STATUS_BADGE).map(([k, v]) => <SelectItem key={k} value={k}>{v.label}</SelectItem>)}</SelectContent></Select>
        <Select value={fWeek} onValueChange={setFWeek}><SelectTrigger className="h-8 w-28"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">Any week</SelectItem>{WEEKS.map((w) => <SelectItem key={w} value={String(w)}>Week {w}</SelectItem>)}</SelectContent></Select>
      </FilterBar>

      {rows.length === 0 ? (
        <Card><CardContent><EmptyState icon={<NotebookPen className="size-8" />} title="No lesson notes yet" hint="Start a weekly note from a GES template — the HOD reviews and endorses it." /></CardContent></Card>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Note</TableHead>
              <TableHead>Class · Subject</TableHead>
              <TableHead>Week</TableHead>
              {data.canReview && <TableHead>Teacher</TableHead>}
              <TableHead>Status</TableHead>
              <TableHead className="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {rows.map((n) => {
              const own = n.staffId === data.myStaffId;
              return (
                <TableRow key={n.id}>
                  <TableCell>
                    <button className="text-left" onClick={() => onView(n)}>
                      <div className="font-medium hover:underline">{n.title}</div>
                      <div className="text-[11px] text-muted-foreground">{fmtDate(n.lessonDate)} · v{n.version}{n.sourceNoteId ? " · copied" : ""}</div>
                    </button>
                  </TableCell>
                  <TableCell><div className="text-sm">{n.classroom.name}</div><div className="text-[11px] text-muted-foreground">{n.subject.name}</div></TableCell>
                  <TableCell className="tabular-nums">W{n.weekNumber}</TableCell>
                  {data.canReview && <TableCell className="text-sm">{n.staff.fullName}{own ? " (you)" : ""}</TableCell>}
                  <TableCell>
                    <Badge className={STATUS_BADGE[n.status]?.cls ?? ""}>{STATUS_BADGE[n.status]?.label ?? n.status}</Badge>
                    {n.status === "REVISION_REQUIRED" && n.feedback && <div className="text-[10px] text-destructive mt-0.5 max-w-48 truncate" title={n.feedback}>“{n.feedback}”</div>}
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex justify-end gap-1">
                      <Button size="sm" variant="ghost" className="h-7 w-7 p-0" title="View & print" onClick={() => onView(n)}><Eye className="size-3.5" /></Button>
                      {own && n.status !== "ENDORSED" && <Button size="sm" variant="ghost" className="h-7 w-7 p-0" title="Edit" disabled={busy} onClick={() => onEdit(n)}><Pencil className="size-3.5" /></Button>}
                      <Button size="sm" variant="ghost" className="h-7 w-7 p-0" title="Copy to another week" disabled={busy} onClick={() => onCopy(n)}><Copy className="size-3.5" /></Button>
                      {(own || data.canReview) && n.status !== "ENDORSED" && <Button size="sm" variant="ghost" className="h-7 w-7 p-0 text-destructive" title="Delete" disabled={busy} onClick={() => onDelete(n)}><Trash2 className="size-3.5" /></Button>}
                    </div>
                  </TableCell>
                </TableRow>
              );
            })}
          </TableBody>
        </Table>
      )}
    </TabsContent>
  );
}

function ReviewTab({ data, onView, onReview }: { data: Overview; onView: (n: Note) => void; onReview: (n: Note) => void }) {
  const pending = data.notes.filter((n) => n.status === "PENDING").sort((a, b) => new Date(a.submittedAt ?? a.createdAt).getTime() - new Date(b.submittedAt ?? b.createdAt).getTime());
  return (
    <TabsContent value="review" className="mt-4 space-y-3">
      {pending.length === 0 ? (
        <Card><CardContent><EmptyState icon={<CheckCircle2 className="size-8" />} title="Review queue is clear" hint="Weekly notes submitted by teachers appear here for endorsement." /></CardContent></Card>
      ) : pending.map((n) => (
        <Card key={n.id}>
          <CardContent className="pt-4 flex flex-wrap items-center justify-between gap-3">
            <div className="min-w-0">
              <div className="flex items-center gap-2 flex-wrap">
                <span className="font-medium">{n.title}</span>
                <Badge variant="secondary">{n.classroom.name}</Badge>
                <Badge variant="outline">{n.subject.name}</Badge>
                <Badge variant="outline">Week {n.weekNumber}</Badge>
              </div>
              <div className="text-xs text-muted-foreground mt-1">
                {n.staff.fullName} · submitted {fmtDate(n.submittedAt ?? n.createdAt)} · v{n.version}
                {n.reviews.some((r) => r.action === "RESUBMIT") ? " · resubmitted after feedback" : ""}
              </div>
            </div>
            <div className="flex gap-1.5">
              <Button size="sm" variant="outline" className="h-8 gap-1" onClick={() => onView(n)}><Eye className="size-3.5" /> Read</Button>
              <Button size="sm" className="h-8 gap-1 bg-emerald-600 hover:bg-emerald-700" onClick={() => onReview(n)}><FileCheck2 className="size-3.5" /> Endorse / return</Button>
            </div>
          </CardContent>
        </Card>
      ))}
    </TabsContent>
  );
}

function TemplatesTab({ data, onEdit, onNew, onDelete, busy, onUseTemplate }: {
  data: Overview; onEdit: (t: Template) => void; onNew: () => void; onDelete: (t: Template) => void; busy: boolean; onUseTemplate: (t: Template) => void;
}) {
  const [q, setQ] = useState("");
  const rows = data.templates.filter((t) => !q || `${t.name} ${t.subject ?? ""} ${t.classroom ?? ""} ${t.strand ?? ""}`.toLowerCase().includes(q.toLowerCase()));
  return (
    <TabsContent value="templates" className="mt-4 space-y-3">
      <FilterBar active={!!q} count={rows.length} total={data.templates.length} onClear={() => setQ("")} actions={<Button size="sm" className="h-8 gap-1" onClick={onNew}><Plus className="size-3.5" /> New template</Button>}>
        <Input placeholder="Search templates…" value={q} onChange={(e) => setQ(e.target.value)} className="h-8 w-56" />
      </FilterBar>
      {rows.length === 0 ? (
        <Card><CardContent><EmptyState icon={<BookMarked className="size-8" />} title="No templates yet" hint="Build the GES skeleton once — strand, sub-strand, content standard, core points — then reuse it every week." /></CardContent></Card>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
          {rows.map((t) => (
            <Card key={t.id}>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm flex items-start justify-between gap-2">
                  <span className="leading-snug">{t.name}</span>
                </CardTitle>
                <div className="flex flex-wrap gap-1 mt-1">
                  {t.subject && <Badge variant="secondary">{t.subject}</Badge>}
                  {t.classroom && <Badge variant="outline">{t.classroom}</Badge>}
                  {!t.classroom && <Badge variant="outline">any class</Badge>}
                </div>
              </CardHeader>
              <CardContent className="space-y-1.5">
                {t.strand && <div className="text-xs"><span className="text-muted-foreground">Strand:</span> {t.strand}{t.subStrand ? ` › ${t.subStrand}` : ""}</div>}
                {t.contentStandard && <div className="text-xs"><span className="text-muted-foreground">Standard:</span> {t.contentStandard}</div>}
                <p className="text-xs text-muted-foreground line-clamp-2">{t.description || t.objectives.slice(0, 120) || "—"}</p>
                <div className="flex gap-1.5 pt-1.5">
                  <Button size="sm" className="h-7 gap-1 text-xs" onClick={() => onUseTemplate(t)}><Plus className="size-3" /> New note from this</Button>
                  <Button size="sm" variant="ghost" className="h-7 w-7 p-0" title="Edit" disabled={busy} onClick={() => onEdit(t)}><Pencil className="size-3.5" /></Button>
                  <Button size="sm" variant="ghost" className="h-7 w-7 p-0 text-destructive" title="Delete" disabled={busy} onClick={() => onDelete(t)}><Trash2 className="size-3.5" /></Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </TabsContent>
  );
}

function SyllabusTab({ data, syllabusClass, setSyllabusClass, syllabusSubject, setSyllabusSubject, topics, coverage, onEdit, onNew, onToggle, onDelete, busy }: {
  data: Overview; syllabusClass: string; setSyllabusClass: (v: string) => void;
  syllabusSubject: string; setSyllabusSubject: (v: string) => void;
  topics: Topic[] | null; coverage: { total: number; completed: number; pct: number } | null;
  onEdit: (t: Topic) => void; onNew: (week: number) => void; onToggle: (t: Topic, completed: boolean) => void; onDelete: (t: Topic) => void; busy: boolean;
}) {
  const bySubject = useMemo(() => {
    const m = new Map<string, Topic[]>();
    for (const t of topics ?? []) {
      const arr = m.get(t.subject) ?? [];
      arr.push(t);
      m.set(t.subject, arr);
    }
    return [...m.entries()];
  }, [topics]);

  return (
    <TabsContent value="syllabus" className="mt-4 space-y-3">
      <FilterBar active={syllabusSubject !== "all"} onClear={() => setSyllabusSubject("all")} count={coverage?.completed} total={coverage?.total} label="Scheme of learning"
        actions={<Button size="sm" className="h-8 gap-1" onClick={() => onNew(data.term?.weeksElapsed ?? 1)}><Plus className="size-3.5" /> Add topic</Button>}>
        <Select value={syllabusClass} onValueChange={setSyllabusClass}><SelectTrigger className="h-8 w-44"><SelectValue /></SelectTrigger><SelectContent>{data.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent></Select>
        <Select value={syllabusSubject} onValueChange={setSyllabusSubject}><SelectTrigger className="h-8 w-40"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">All subjects</SelectItem>{data.subjects.map((s) => <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>)}</SelectContent></Select>
      </FilterBar>

      {coverage && (
        <Card><CardContent className="pt-4">
          <div className="flex items-center justify-between text-sm mb-1.5">
            <span className="font-medium">Term coverage — {data.classrooms.find((c) => c.id === syllabusClass)?.name ?? ""}</span>
            <span className="tabular-nums text-muted-foreground">{coverage.completed} of {coverage.total} topics taught</span>
          </div>
          <Progress value={coverage.pct} className="h-2.5" />
        </CardContent></Card>
      )}

      {topics === null ? <LoadingBlock label="Loading scheme of learning…" /> : topics.length === 0 ? (
        <Card><CardContent><EmptyState icon={<ListChecks className="size-8" />} title="No scheme of learning for this class yet" hint="Add the week-by-week topics (strand › sub-strand › topic) and tick them off as you teach." /></CardContent></Card>
      ) : (
        <div className="space-y-4">
          {bySubject.map(([subject, list]) => {
            const done = list.filter((t) => t.completed).length;
            return (
              <Card key={subject}>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm flex items-center justify-between">
                    <span>{subject}</span>
                    <span className="text-xs font-normal text-muted-foreground tabular-nums">{done}/{list.length} taught</span>
                  </CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead className="w-16">Week</TableHead>
                        <TableHead>Strand › Sub-strand</TableHead>
                        <TableHead>Topic &amp; indicator</TableHead>
                        <TableHead className="w-28 text-center">Taught</TableHead>
                        <TableHead className="w-20" />
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {list.map((t) => (
                        <TableRow key={t.id}>
                          <TableCell className="tabular-nums font-medium">W{t.weekNumber}</TableCell>
                          <TableCell className="text-xs">{t.strand ?? "—"}{t.subStrand ? <><br /><span className="text-muted-foreground">{t.subStrand}</span></> : null}</TableCell>
                          <TableCell>
                            <div className="text-sm">{t.topic}</div>
                            {t.indicator && <div className="text-[11px] text-muted-foreground">{t.indicator}</div>}
                          </TableCell>
                          <TableCell className="text-center"><Switch checked={t.completed} disabled={busy} onCheckedChange={(v) => onToggle(t, v)} /></TableCell>
                          <TableCell className="text-right">
                            <div className="flex justify-end gap-1">
                              <Button size="sm" variant="ghost" className="h-7 w-7 p-0" title="Edit" disabled={busy} onClick={() => onEdit(t)}><Pencil className="size-3.5" /></Button>
                              <Button size="sm" variant="ghost" className="h-7 w-7 p-0 text-destructive" title="Delete" disabled={busy} onClick={() => onDelete(t)}><Trash2 className="size-3.5" /></Button>
                            </div>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}
    </TabsContent>
  );
}

function MaterialsTab({ data, materials, onNew, onEdit, onDelete, busy }: {
  data: Overview; materials: Material[] | null;
  onNew: () => void; onEdit: (m: Material) => void; onDelete: (m: Material) => void; busy: boolean;
}) {
  const [fClass, setFClass] = useState("all");
  const [q, setQ] = useState("");
  const rows = (materials ?? []).filter((m) => (!q || `${m.title} ${m.subject ?? ""} ${m.author}`.toLowerCase().includes(q.toLowerCase())) && (fClass === "all" || m.classroomId === fClass));
  const active = !!q || fClass !== "all";
  return (
    <TabsContent value="materials" className="mt-4 space-y-3">
      <FilterBar active={active} count={rows.length} total={materials?.length ?? 0} onClear={() => { setQ(""); setFClass("all"); }} actions={<Button size="sm" className="h-8 gap-1" onClick={onNew}><Plus className="size-3.5" /> Share material</Button>}>
        <Input placeholder="Search material…" value={q} onChange={(e) => setQ(e.target.value)} className="h-8 w-56" />
        <Select value={fClass} onValueChange={setFClass}><SelectTrigger className="h-8 w-40"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">All classes</SelectItem>{data.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent></Select>
      </FilterBar>
      {materials === null ? <LoadingBlock label="Loading study material…" /> : rows.length === 0 ? (
        <Card><CardContent><EmptyState icon={<LibraryBig className="size-8" />} title="Nothing shared yet" hint="Share revision notes, past questions or reading links with a class." /></CardContent></Card>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
          {rows.map((m) => (
            <Card key={m.id}>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm leading-snug">{m.title}</CardTitle>
                <div className="flex flex-wrap gap-1 mt-1">
                  <Badge variant="secondary">{m.classroom}</Badge>
                  {m.subject && <Badge variant="outline">{m.subject}</Badge>}
                </div>
              </CardHeader>
              <CardContent className="space-y-1.5">
                {m.description && <p className="text-xs text-muted-foreground line-clamp-2">{m.description}</p>}
                {m.body && <p className="text-xs line-clamp-3 whitespace-pre-line">{m.body}</p>}
                {m.linkUrl && (
                  <a href={m.linkUrl} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 text-xs text-primary hover:underline break-all">
                    <ExternalLink className="size-3 shrink-0" /> {m.linkUrl}
                  </a>
                )}
                <div className="flex items-center justify-between pt-1.5">
                  <span className="text-[10px] text-muted-foreground">{m.author} · {fmtDate(m.createdAt)}</span>
                  <div className="flex gap-1">
                    {(m.authorStaffId === data.myStaffId || data.canReview) && <Button size="sm" variant="ghost" className="h-7 w-7 p-0" title="Edit" disabled={busy} onClick={() => onEdit(m)}><Pencil className="size-3.5" /></Button>}
                    {(m.authorStaffId === data.myStaffId || data.canReview) && <Button size="sm" variant="ghost" className="h-7 w-7 p-0 text-destructive" title="Delete" disabled={busy} onClick={() => onDelete(m)}><Trash2 className="size-3.5" /></Button>}
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </TabsContent>
  );
}

function CompletionTab({ data }: { data: Overview }) {
  const rows = [...data.staffStats].sort((a, b) => b.compliancePct - a.compliancePct);
  return (
    <TabsContent value="completion" className="mt-4 space-y-3">
      {rows.length === 0 ? (
        <Card><CardContent><EmptyState icon={<ClipboardCheck className="size-8" />} title="No teaching activity yet" hint="Once teachers write lesson notes or tick syllabus topics, their completion shows here." /></CardContent></Card>
      ) : (
        <Card>
          <CardHeader className="pb-2 flex-row items-center justify-between space-y-0">
            <CardTitle className="text-base">Teacher completion — {data.term?.name ?? "current term"} (week {data.term?.weeksElapsed ?? "?"})</CardTitle>
            <Button size="sm" variant="outline" className="h-8 gap-1" onClick={() => downloadCsv("teacher-completion.csv",
              ["Teacher", "Staff code", "Class-subject pairs", "Notes", "Endorsed", "Awaiting", "Returned", "Weeks covered", "Weeks elapsed", "Note compliance %", "Syllabus topics", "Taught", "Coverage %"],
              rows.map((r) => [r.name, r.staffCode ?? "", r.classSubjectPairs, r.notes, r.endorsed, r.pending, r.revisions, r.weeksCovered, r.weeksElapsed, r.compliancePct, r.syllabusTotal, r.syllabusCompleted, r.syllabusPct ?? ""]))}>
              <Printer className="size-3.5" /> Export CSV
            </Button>
          </CardHeader>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Teacher</TableHead>
                  <TableHead>Classes·subjects</TableHead>
                  <TableHead>Notes</TableHead>
                  <TableHead className="min-w-44">Weekly compliance</TableHead>
                  <TableHead className="min-w-44">Syllabus coverage</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((r) => (
                  <TableRow key={r.staffId}>
                    <TableCell>
                      <div className="font-medium">{r.name}</div>
                      <div className="text-[11px] text-muted-foreground font-mono">{r.staffCode ?? ""}</div>
                    </TableCell>
                    <TableCell className="tabular-nums">{r.classSubjectPairs}</TableCell>
                    <TableCell>
                      <span className="tabular-nums font-medium">{r.notes}</span>
                      <span className="text-[11px] text-muted-foreground"> ({r.endorsed} ✓ · {r.pending} ⏳ · {r.revisions} ↩)</span>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Progress value={r.compliancePct} className="h-2 flex-1" />
                        <span className="text-xs tabular-nums w-14 text-right">{r.weeksCovered}/{r.weeksElapsed} wk · {r.compliancePct}%</span>
                      </div>
                    </TableCell>
                    <TableCell>
                      {r.syllabusPct == null ? <span className="text-xs text-muted-foreground">—</span> : (
                        <div className="flex items-center gap-2">
                          <Progress value={r.syllabusPct} className="h-2 flex-1" />
                          <span className="text-xs tabular-nums w-14 text-right">{r.syllabusCompleted}/{r.syllabusTotal} · {r.syllabusPct}%</span>
                        </div>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      )}
    </TabsContent>
  );
}

// ══════════════════════════════════════════════════════════════
// Dialogs
// ══════════════════════════════════════════════════════════════

type NoteFormShape = Partial<Note> & { submit?: boolean; sourceNoteId?: string | null };

function NoteDialog(props: { data: Overview; form: NoteFormShape | null; busy: boolean; onClose: () => void; onSave: (payload: Record<string, unknown>, submit: boolean) => void }) {
  if (!props.form) return null;
  return <NoteDialogForm key={props.form.id ?? `new-${props.form.sourceNoteId ?? ""}-${props.form.weekNumber ?? ""}`} data={props.data} form={props.form} busy={props.busy} onClose={props.onClose} onSave={props.onSave} />;
}

function NoteDialogForm({ data, form, busy, onClose, onSave }: {
  data: Overview; form: NoteFormShape; busy: boolean;
  onClose: () => void; onSave: (payload: Record<string, unknown>, submit: boolean) => void;
}) {
  const { toast } = useToast();
  const schoolId = useStore().currentSchoolId;
  const [f, setF] = useState<Record<string, any>>(() => ({
    classroomId: form.classroomId ?? "", subjectId: form.subjectId ?? "", termId: form.termId ?? data.terms.find((t) => t.isCurrent)?.id ?? data.terms[0]?.id ?? "",
    weekNumber: form.weekNumber ?? data.term?.weeksElapsed ?? 1, lessonDate: form.lessonDate ? form.lessonDate.slice(0, 10) : today(),
    title: form.title ?? "", description: form.description ?? "", objectives: form.objectives ?? "", activities: form.activities ?? "", content: form.content ?? "",
    fwCurriculum: form.strand?.curriculum ?? data.curricula[0] ?? "GES",
    strandId: form.strandId ?? "", subStrandId: form.subStrandId ?? "", contentStandardId: form.contentStandardId ?? "",
    indicatorIds: (() => { try { return form.indicatorIds ? (JSON.parse(form.indicatorIds) as string[]) : []; } catch { return []; } })(),
    coreCompetencies: form.coreCompetencies ?? "", tlm: form.tlm ?? "", assessment: form.assessment ?? "",
  }));
  const editing = !!form.id;
  const termId = f.termId || data.terms.find((t) => t.isCurrent)?.id || data.terms[0]?.id || "";

  /* ── curriculum framework for the chosen class+subject+curriculum ── */
  const fwSubjectName = data.subjects.find((s) => s.id === f.subjectId)?.name ?? "";
  const fwGrade = data.classrooms.find((c) => c.id === f.classroomId)?.gradeLevel ?? "";
  const [fwTree, setFwTree] = useState<FrameworkNode[] | null>(null);
  useEffect(() => {
    if (!schoolId || !fwSubjectName || !fwGrade) { setFwTree(null); return; }
    let live = true;
    apiGet<{ strands: FrameworkNode[] }>(`/api/school/${schoolId}/ops/curriculum-framework?curriculum=${encodeURIComponent(f.fwCurriculum)}&subjectName=${encodeURIComponent(fwSubjectName)}&gradeLevel=${encodeURIComponent(frameworkGrade(f.fwCurriculum, fwGrade))}`)
      .then((d) => { if (live) setFwTree(d.strands); })
      .catch(() => { if (live) setFwTree([]); });
    return () => { live = false; };
  }, [schoolId, f.fwCurriculum, f.subjectId, f.classroomId, fwSubjectName, fwGrade]);
  const fwStrand = (fwTree ?? []).find((s) => s.id === f.strandId) ?? null;
  const fwSub = fwStrand?.subStrands?.find((b) => b.id === f.subStrandId) ?? null;
  const fwStandard = fwSub?.standards?.find((st) => st.id === f.contentStandardId) ?? null;

  const applyTemplate = (templateId: string) => {
    const t = data.templates.find((x) => x.id === templateId);
    if (!t) { setF({ ...f, templateId }); return; }
    setF({
      ...f, templateId,
      title: f.title || t.name, objectives: t.objectives, activities: t.activities, content: t.content,
      description: t.description ?? "", strand: t.strand, subStrand: t.subStrand, contentStandard: t.contentStandard,
    });
  };

  const toggleCompetency = (name: string) => {
    const list = (f.coreCompetencies || "").split("\n").map((x: string) => x.trim()).filter(Boolean);
    const next = list.includes(name) ? list.filter((x: string) => x !== name) : [...list, name];
    setF({ ...f, coreCompetencies: next.join("\n") });
  };
  const selectedCompetencies = (f.coreCompetencies || "").split("\n").map((x: string) => x.trim()).filter(Boolean);
  const competencies = CURRICULA[f.fwCurriculum as CurriculumKey]?.competencies ?? [];

  const toggleIndicator = (id: string) => {
    const set = new Set<string>(f.indicatorIds);
    if (set.has(id)) set.delete(id); else set.add(id);
    setF({ ...f, indicatorIds: [...set] });
  };
  /* one-click: pour the chosen indicators into the objectives box (GES format) */
  const indicatorsToObjectives = () => {
    const lines = (fwStandard?.indicators ?? []).filter((i) => f.indicatorIds.includes(i.id))
      .map((i) => `${i.code ? `${i.code} — ` : ""}${i.statement}`);
    if (!lines.length) { toast({ title: "Select at least one indicator first" }); return; }
    setF({ ...f, objectives: f.objectives ? `${f.objectives}\n${lines.join("\n")}` : `By the end of the lesson, learners will be able to:\n${lines.join("\n")}` });
  };

  const payload = () => ({
    id: form.id, classroomId: f.classroomId, subjectId: f.subjectId, termId,
    weekNumber: f.weekNumber, lessonDate: f.lessonDate, title: f.title, description: f.description,
    objectives: f.objectives, activities: f.activities, content: f.content,
    templateId: f.templateId && f.templateId !== "none" ? f.templateId : null,
    strandId: f.strandId || null, subStrandId: f.subStrandId || null, contentStandardId: f.contentStandardId || null,
    indicatorIds: f.indicatorIds, coreCompetencies: f.coreCompetencies || null, tlm: f.tlm || null, assessment: f.assessment || null,
  });

  return (
    <Dialog open onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><NotebookPen className="size-4.5" /> {editing ? `Edit lesson note — v${form.version}` : form.sourceNoteId ? "Copy lesson note to a new week" : "New lesson note"}</DialogTitle>
          <DialogDescription>
            {editing ? "Endorsed notes are immutable; a returned note goes back to the review queue when you save." : "Draft from a template, align to the curriculum framework, then submit to your HOD / head for endorsement."}
          </DialogDescription>
        </DialogHeader>
        <FormGrid cols={3}>
          <FormField label="Class" required>
            <Select value={f.classroomId} onValueChange={(v) => setF({ ...f, classroomId: v })}>
              <SelectTrigger><SelectValue placeholder="Choose class" /></SelectTrigger>
              <SelectContent>{data.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent>
            </Select>
          </FormField>
          <FormField label="Subject" required>
            <Select value={f.subjectId} onValueChange={(v) => setF({ ...f, subjectId: v })}>
              <SelectTrigger><SelectValue placeholder="Choose subject" /></SelectTrigger>
              <SelectContent>{data.subjects.map((s) => <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>)}</SelectContent>
            </Select>
          </FormField>
          <FormField label="Term" required>
            <Select value={termId} onValueChange={(v) => setF({ ...f, termId: v })}>
              <SelectTrigger><SelectValue placeholder="Choose term" /></SelectTrigger>
              <SelectContent>{data.terms.map((t) => <SelectItem key={t.id} value={t.id}>{t.name}</SelectItem>)}</SelectContent>
            </Select>
          </FormField>
          <FormField label="Week of term" required hint="1–15">
            <Select value={String(f.weekNumber)} onValueChange={(v) => setF({ ...f, weekNumber: Number(v) })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>{WEEKS.map((w) => <SelectItem key={w} value={String(w)}>Week {w}</SelectItem>)}</SelectContent>
            </Select>
          </FormField>
          <FormField label="Lesson date" required>
            <Input type="date" value={f.lessonDate} onChange={(e) => setF({ ...f, lessonDate: e.target.value })} />
          </FormField>
          <FormField label="Start from template" hint="Prefills objectives, activities & content">
            <Select value={f.templateId ?? "none"} onValueChange={applyTemplate}>
              <SelectTrigger><SelectValue placeholder="Blank note" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">Blank note</SelectItem>
                {data.templates.map((t) => <SelectItem key={t.id} value={t.id}>{t.name}{t.subject ? ` — ${t.subject}` : ""}</SelectItem>)}
              </SelectContent>
            </Select>
          </FormField>
          <FormField label="Title" required span={3}>
            <Input value={f.title} onChange={(e) => setF({ ...f, title: e.target.value })} placeholder="e.g. Reading & writing two-syllable words" />
          </FormField>
          <FormField label="Description" span={3} hint="One-line summary for the scheme-of-learning tracker">
            <Input value={f.description} onChange={(e) => setF({ ...f, description: e.target.value })} />
          </FormField>
        </FormGrid>

        {/* ── curriculum alignment (framework pickers) ── */}
        <div className="rounded-xl border bg-muted/30 p-3 space-y-3">
          <div className="flex items-center justify-between gap-2 flex-wrap">
            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Curriculum alignment</div>
            {fwTree && fwTree.length > 0 && <span className="text-[11px] text-muted-foreground">{fwTree.length} strands available</span>}
          </div>
          <FormGrid cols={3}>
            <FormField label="Curriculum">
              <Select value={f.fwCurriculum} onValueChange={(v) => setF({ ...f, fwCurriculum: v, strandId: "", subStrandId: "", contentStandardId: "", indicatorIds: [] })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{(data.curricula.length ? data.curricula : ["GES"]).map((c) => <SelectItem key={c} value={c}>{CURRICULA_MAP[c] ?? c}</SelectItem>)}</SelectContent>
              </Select>
            </FormField>
            <FormField label={CURRICULA[f.fwCurriculum as CurriculumKey]?.hierarchy.strand ?? "Strand"}>
              <Select value={f.strandId} onValueChange={(v) => setF({ ...f, strandId: v, subStrandId: "", contentStandardId: "", indicatorIds: [] })}>
                <SelectTrigger><SelectValue placeholder={fwTree === null ? "Choose class & subject first" : "Not aligned"} /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">Not aligned</SelectItem>
                  {(fwTree ?? []).map((s) => <SelectItem key={s.id} value={s.id}>{s.code ? `${s.code} · ` : ""}{s.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </FormField>
            <FormField label={CURRICULA[f.fwCurriculum as CurriculumKey]?.hierarchy.subStrand ?? "Sub-strand"}>
              <Select value={f.subStrandId} onValueChange={(v) => setF({ ...f, subStrandId: v, contentStandardId: "", indicatorIds: [] })} disabled={!fwStrand}>
                <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">—</SelectItem>
                  {(fwStrand?.subStrands ?? []).map((b) => <SelectItem key={b.id} value={b.id}>{b.code ? `${b.code} · ` : ""}{b.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </FormField>
          </FormGrid>
          {fwSub && (fwSub.standards ?? []).length > 0 && (
            <div className="space-y-3">
              <FormField label={CURRICULA[f.fwCurriculum as CurriculumKey]?.hierarchy.standard ?? "Content standard"}>
                <Select value={f.contentStandardId} onValueChange={(v) => setF({ ...f, contentStandardId: v, indicatorIds: [] })}>
                  <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">—</SelectItem>
                    {(fwSub.standards ?? []).map((st) => <SelectItem key={st.id} value={st.id}>{st.code ? `${st.code} · ` : ""}{st.statement.length > 90 ? `${st.statement.slice(0, 90)}…` : st.statement}</SelectItem>)}
                  </SelectContent>
                </Select>
              </FormField>
              {fwStandard && (fwStandard.indicators ?? []).length > 0 && (
                <div>
                  <div className="flex items-center justify-between mb-1.5">
                    <span className="text-xs font-medium">{CURRICULA[f.fwCurriculum as CurriculumKey]?.hierarchy.indicator ?? "Indicators"} <span className="text-muted-foreground">— tick what this lesson covers</span></span>
                    <Button type="button" size="sm" variant="outline" className="h-7 gap-1 text-xs" onClick={indicatorsToObjectives}><Wand2 className="size-3" /> Use as objectives</Button>
                  </div>
                  <div className="max-h-36 overflow-y-auto nice-scroll rounded-lg border bg-background divide-y">
                    {(fwStandard.indicators ?? []).map((ind) => (
                      <label key={ind.id} className="flex items-start gap-2 p-2 text-xs cursor-pointer hover:bg-muted/50">
                        <Checkbox className="mt-0.5" checked={f.indicatorIds.includes(ind.id)} onCheckedChange={() => toggleIndicator(ind.id)} />
                        <span>{ind.code ? <b className="tabular-nums text-muted-foreground mr-1">{ind.code}</b> : null}{ind.statement}</span>
                      </label>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}
        </div>

        <FormGrid cols={3}>
          <FormField label="Lesson objectives" span={3} hint="By the end of the lesson learners can…">
            <Textarea rows={3} value={f.objectives} onChange={(e) => setF({ ...f, objectives: e.target.value })} />
          </FormField>
          <FormField label="Learning activities" span={3} hint="Starter → main → plenary">
            <Textarea rows={4} value={f.activities} onChange={(e) => setF({ ...f, activities: e.target.value })} />
          </FormField>
          <FormField label="Lesson content / core points" span={3}>
            <Textarea rows={5} value={f.content} onChange={(e) => setF({ ...f, content: e.target.value })} />
          </FormField>
        </FormGrid>

        {/* ── competencies + TLM + assessment (GES lesson-plan fields) ── */}
        <div className="rounded-xl border bg-muted/30 p-3 space-y-3">
          <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{competencies.length ? CURRICULA[f.fwCurriculum as CurriculumKey]?.competenciesLabel ?? "Competencies" : "Competencies"} · materials · assessment</div>
          {competencies.length > 0 && (
            <div className="flex flex-wrap gap-1.5">
              {competencies.map((name) => {
                const on = selectedCompetencies.includes(name);
                return (
                  <button
                    key={name} type="button" onClick={() => toggleCompetency(name)}
                    className={cn("rounded-full border px-2.5 py-1 text-[11px] font-medium transition-colors cursor-pointer",
                      on ? "bg-primary text-primary-foreground border-primary" : "bg-background text-muted-foreground hover:border-primary/50")}
                  >
                    {name}
                  </button>
                );
              })}
            </div>
          )}
          <FormGrid cols={3}>
            <FormField label="Teaching & learning materials" hint="TLMs — manipulatives, charts, devices…">
              <Textarea rows={2} value={f.tlm} onChange={(e) => setF({ ...f, tlm: e.target.value })} placeholder="Number cards, bundle of sticks, place-value chart" />
            </FormField>
            <FormField label="Assessment" hint="How learning is checked">
              <Textarea rows={2} value={f.assessment} onChange={(e) => setF({ ...f, assessment: e.target.value })} placeholder="Q&A, exercise 3 on p.42, peer marking" />
            </FormField>
            <FormField label="Custom competencies" hint="Anything beyond the ticks above (one per line)">
              <Textarea rows={2} value={selectedCompetencies.filter((c) => !competencies.includes(c)).join("\n") || ""} onChange={(e) => setF({ ...f, coreCompetencies: [...selectedCompetencies.filter((c) => competencies.includes(c)), ...e.target.value.split("\n").filter(Boolean)].join("\n") })} />
            </FormField>
          </FormGrid>
        </div>

        <DialogFooter className="gap-2 sm:justify-between">
          <div className="text-xs text-muted-foreground">
            {editing && form.status === "REVISION_REQUIRED" && form.feedback ? <span className="text-destructive">Reviewer: “{form.feedback}”</span> : "\u00A0"}
          </div>
          <div className="flex gap-2">
            <Button variant="outline" disabled={busy} onClick={() => onSave(payload(), false)}>Save draft</Button>
            <Button disabled={busy} onClick={() => onSave(payload(), true)}>
              <FileCheck2 className="size-4" /> {editing ? "Save & resubmit" : "Submit for review"}
            </Button>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function NoteViewDialog({ note, onClose }: { note: Note | null; onClose: () => void }) {
  return (
    <Dialog open={!!note} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        {note && (
          <>
            <div className="print-area">
              <div className="border-b pb-3 mb-3">
                <div className="text-center">
                  <div className="font-bold text-lg">{note.classroom.name} — {note.subject.name}</div>
                  <div className="text-sm font-medium">{note.title}</div>
                  <div className="text-xs text-muted-foreground">Week {note.weekNumber} · {fmtDate(note.lessonDate)} · v{note.version}</div>
                  {note.strand && (
                    <div className="text-[11px] text-muted-foreground mt-1">
                      {CURRICULA_MAP[note.strand.curriculum] ?? note.strand.curriculum} · {note.strand.code ? `${note.strand.code} ` : ""}{note.strand.name}
                      {note.subStrand ? ` › ${note.subStrand.name}` : ""}
                      {note.contentStandard ? ` · ${note.contentStandard.code ?? ""}` : ""}
                    </div>
                  )}
                </div>
              </div>
              <Section title="Lesson objectives"><p className="whitespace-pre-line text-sm">{note.objectives || "—"}</p></Section>
              <Section title="Learning activities"><p className="whitespace-pre-line text-sm">{note.activities || "—"}</p></Section>
              <Section title="Lesson content / core points"><p className="whitespace-pre-line text-sm">{note.content || "—"}</p></Section>
              {(note.coreCompetencies || note.tlm || note.assessment) && (
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-2">
                  <div className="border rounded-lg p-2.5"><div className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">Competencies</div><p className="text-xs whitespace-pre-line">{note.coreCompetencies || "—"}</p></div>
                  <div className="border rounded-lg p-2.5"><div className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">TLMs</div><p className="text-xs whitespace-pre-line">{note.tlm || "—"}</p></div>
                  <div className="border rounded-lg p-2.5"><div className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground mb-1">Assessment</div><p className="text-xs whitespace-pre-line">{note.assessment || "—"}</p></div>
                </div>
              )}
              <div className="mt-4 border rounded-lg p-3 grid grid-cols-2 gap-3 text-sm">
                <div><span className="text-muted-foreground text-xs block">Teacher</span>{note.staff.fullName}</div>
                <div><span className="text-muted-foreground text-xs block">Status</span><Badge className={STATUS_BADGE[note.status]?.cls ?? ""}>{STATUS_BADGE[note.status]?.label ?? note.status}</Badge></div>
                <div><span className="text-muted-foreground text-xs block">Reviewed by</span>{note.reviewedByName ?? "—"}{note.reviewedAt ? ` · ${fmtDate(note.reviewedAt)}` : ""}</div>
                <div><span className="text-muted-foreground text-xs block">Feedback</span>{note.feedback ?? "—"}</div>
              </div>
            </div>
            <DialogHeader className="no-print">
              <DialogTitle className="flex items-center gap-2"><FileText className="size-4.5" /> Lesson note</DialogTitle>
            </DialogHeader>
            <div className="no-print space-y-3">
              {note.reviews.length > 0 && (
                <div className="rounded-lg border p-3">
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">Review history</div>
                  <div className="space-y-1.5">
                    {note.reviews.map((r) => (
                      <div key={r.id} className="flex gap-2 text-xs">
                        <span className="text-muted-foreground tabular-nums shrink-0">{fmtDate(r.createdAt)}</span>
                        <span><b>{ACTION_LABEL[r.action] ?? r.action}</b> — {r.actorName}{r.comment ? `: “${r.comment}”` : ""}</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
              <DialogFooter>
                <Button variant="outline" className="gap-1.5" onClick={() => window.print()}><Printer className="size-4" /> Print note</Button>
                <Button onClick={onClose}>Close</Button>
              </DialogFooter>
            </div>
          </>
        )}
      </DialogContent>
    </Dialog>
  );
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div className="mb-3">
      <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">{title}</div>
      {children}
    </div>
  );
}

function ReviewDialog(props: { note: Note | null; busy: boolean; onClose: () => void; onDecide: (decision: "ENDORSE" | "REQUEST_REVISION", comment: string) => void }) {
  if (!props.note) return null;
  return <ReviewDialogForm key={props.note.id} note={props.note} busy={props.busy} onClose={props.onClose} onDecide={props.onDecide} />;
}

function ReviewDialogForm({ note, busy, onClose, onDecide }: {
  note: Note; busy: boolean; onClose: () => void; onDecide: (decision: "ENDORSE" | "REQUEST_REVISION", comment: string) => void;
}) {
  const [comment, setComment] = useState("");
  return (
    <Dialog open onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><FileCheck2 className="size-4.5" /> Review — {note.title}</DialogTitle>
          <DialogDescription>{note.staff.fullName} · {note.classroom.name} · {note.subject.name} · Week {note.weekNumber} · v{note.version}</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="rounded-lg border p-3 max-h-56 overflow-y-auto text-sm">
            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Objectives</div>
            <p className="whitespace-pre-line mb-2">{note.objectives || "—"}</p>
            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Activities</div>
            <p className="whitespace-pre-line mb-2">{note.activities || "—"}</p>
            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Content</div>
            <p className="whitespace-pre-line">{note.content || "—"}</p>
          </div>
          <FormField label="Feedback to the teacher" hint="Required when returning a note for revision">
            <Textarea rows={3} value={comment} onChange={(e) => setComment(e.target.value)} placeholder="e.g. Add differentiated activities for lower-ability group…" />
          </FormField>
        </div>
        <DialogFooter className="gap-2 sm:justify-end">
          <Button variant="outline" disabled={busy} onClick={onClose}>Cancel</Button>
          <Button variant="destructive" disabled={busy} onClick={() => onDecide("REQUEST_REVISION", comment)}><Undo2 className="size-4" /> Return for revision</Button>
          <Button className="bg-emerald-600 hover:bg-emerald-700" disabled={busy} onClick={() => onDecide("ENDORSE", comment)}><CheckCircle2 className="size-4" /> Endorse</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function TemplateDialog(props: { data: Overview; form: Partial<Template> | null; busy: boolean; onClose: () => void; onSave: (payload: Record<string, unknown>) => void }) {
  if (!props.form) return null;
  return <TemplateDialogForm key={props.form.id ?? "new"} data={props.data} form={props.form} busy={props.busy} onClose={props.onClose} onSave={props.onSave} />;
}

function TemplateDialogForm({ data, form, busy, onClose, onSave }: {
  data: Overview; form: Partial<Template>; busy: boolean; onClose: () => void; onSave: (payload: Record<string, unknown>) => void;
}) {
  const [f, setF] = useState<Record<string, any>>(() => ({
    name: form.name ?? "", subjectId: form.subjectId ?? "any", classroomId: form.classroomId ?? "any",
    strand: form.strand ?? "", subStrand: form.subStrand ?? "", contentStandard: form.contentStandard ?? "",
    description: form.description ?? "", objectives: form.objectives ?? "", activities: form.activities ?? "", content: form.content ?? "",
  }));
  return (
    <Dialog open onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><BookMarked className="size-4.5" /> {form.id ? "Edit template" : "New lesson-note template"}</DialogTitle>
          <DialogDescription>The reusable GES skeleton — prefill weekly notes with strand, sub-strand, content standard, objectives and activities.</DialogDescription>
        </DialogHeader>
        <FormGrid cols={3}>
          <FormField label="Template name" required span={2}>
            <Input value={f.name} onChange={(e) => setF({ ...f, name: e.target.value })} placeholder="e.g. B4 English — Word work & reading" />
          </FormField>
          <FormField label="Subject">
            <Select value={f.subjectId} onValueChange={(v) => setF({ ...f, subjectId: v })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent><SelectItem value="any">Any subject</SelectItem>{data.subjects.map((s) => <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>)}</SelectContent>
            </Select>
          </FormField>
          <FormField label="Class (optional)">
            <Select value={f.classroomId} onValueChange={(v) => setF({ ...f, classroomId: v })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent><SelectItem value="any">Any class</SelectItem>{data.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent>
            </Select>
          </FormField>
          <FormField label="Strand"><Input value={f.strand} onChange={(e) => setF({ ...f, strand: e.target.value })} /></FormField>
          <FormField label="Sub-strand"><Input value={f.subStrand} onChange={(e) => setF({ ...f, subStrand: e.target.value })} /></FormField>
          <FormField label="Content standard" span={3}><Input value={f.contentStandard} onChange={(e) => setF({ ...f, contentStandard: e.target.value })} /></FormField>
          <FormField label="Description" span={3}><Input value={f.description} onChange={(e) => setF({ ...f, description: e.target.value })} /></FormField>
          <FormField label="Objectives (prefill)" span={3}><Textarea rows={3} value={f.objectives} onChange={(e) => setF({ ...f, objectives: e.target.value })} /></FormField>
          <FormField label="Activities (prefill)" span={3}><Textarea rows={4} value={f.activities} onChange={(e) => setF({ ...f, activities: e.target.value })} /></FormField>
          <FormField label="Content / core points (prefill)" span={3}><Textarea rows={4} value={f.content} onChange={(e) => setF({ ...f, content: e.target.value })} /></FormField>
        </FormGrid>
        <DialogFooter>
          <Button variant="outline" disabled={busy} onClick={onClose}>Cancel</Button>
          <Button disabled={busy} onClick={() => onSave({
            name: f.name, subjectId: f.subjectId === "any" ? null : f.subjectId, classroomId: f.classroomId === "any" ? null : f.classroomId,
            strand: f.strand || null, subStrand: f.subStrand || null, contentStandard: f.contentStandard || null,
            description: f.description || null, objectives: f.objectives, activities: f.activities, content: f.content,
          })}>Save template</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function TopicDialog(props: { data: Overview; form: (Partial<Topic> & { classroomId?: string }) | null; busy: boolean; onClose: () => void; onSave: (payload: Record<string, unknown>) => void }) {
  if (!props.form) return null;
  return <TopicDialogForm key={props.form.id ?? `new-w${props.form.weekNumber ?? ""}`} data={props.data} form={props.form} busy={props.busy} onClose={props.onClose} onSave={props.onSave} />;
}

function TopicDialogForm({ data, form, busy, onClose, onSave }: {
  data: Overview; form: Partial<Topic> & { classroomId?: string }; busy: boolean; onClose: () => void; onSave: (payload: Record<string, unknown>) => void;
}) {
  const [f, setF] = useState<Record<string, any>>(() => ({
    subjectId: form.subjectId ?? "", weekNumber: form.weekNumber ?? data.term?.weeksElapsed ?? 1,
    strand: form.strand ?? "", subStrand: form.subStrand ?? "", contentStandard: form.contentStandard ?? "",
    indicator: form.indicator ?? "", topic: form.topic ?? "", completed: form.completed ?? false,
  }));
  return (
    <Dialog open onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><ListChecks className="size-4.5" /> {form.id ? "Edit topic" : "Add scheme-of-learning topic"}</DialogTitle>
          <DialogDescription>Week-by-week GES scheme — tick topics off as they are taught to track coverage.</DialogDescription>
        </DialogHeader>
        <FormGrid cols={3}>
          <FormField label="Subject" required>
            <Select value={f.subjectId} onValueChange={(v) => setF({ ...f, subjectId: v })}>
              <SelectTrigger><SelectValue placeholder="Choose subject" /></SelectTrigger>
              <SelectContent>{data.subjects.map((s) => <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>)}</SelectContent>
            </Select>
          </FormField>
          <FormField label="Week of term" required>
            <Select value={String(f.weekNumber)} onValueChange={(v) => setF({ ...f, weekNumber: Number(v) })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>{WEEKS.map((w) => <SelectItem key={w} value={String(w)}>Week {w}</SelectItem>)}</SelectContent>
            </Select>
          </FormField>
          <FormField label="Already taught?">
            <div className="flex items-center gap-2 h-9">
              <Switch checked={!!f.completed} onCheckedChange={(v) => setF({ ...f, completed: v })} />
              <span className="text-xs text-muted-foreground">{f.completed ? "Taught" : "Not yet"}</span>
            </div>
          </FormField>
          <FormField label="Strand"><Input value={f.strand} onChange={(e) => setF({ ...f, strand: e.target.value })} /></FormField>
          <FormField label="Sub-strand"><Input value={f.subStrand} onChange={(e) => setF({ ...f, subStrand: e.target.value })} /></FormField>
          <FormField label="Content standard"><Input value={f.contentStandard} onChange={(e) => setF({ ...f, contentStandard: e.target.value })} /></FormField>
          <FormField label="Topic" required span={3}><Input value={f.topic} onChange={(e) => setF({ ...f, topic: e.target.value })} placeholder="e.g. Writing: composing simple sentences" /></FormField>
          <FormField label="Learning indicator" span={3}><Input value={f.indicator} onChange={(e) => setF({ ...f, indicator: e.target.value })} placeholder="B4.1.2.1.1 — …" /></FormField>
        </FormGrid>
        <DialogFooter>
          <Button variant="outline" disabled={busy} onClick={onClose}>Cancel</Button>
          <Button disabled={busy} onClick={() => onSave({
            subjectId: f.subjectId, weekNumber: f.weekNumber, topic: f.topic, strand: f.strand || null,
            subStrand: f.subStrand || null, contentStandard: f.contentStandard || null, indicator: f.indicator || null, completed: !!f.completed,
          })}>Save topic</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function MaterialDialog(props: { data: Overview; form: Partial<Material> | null; busy: boolean; onClose: () => void; onSave: (payload: Record<string, unknown>) => void }) {
  if (!props.form) return null;
  return <MaterialDialogForm key={props.form.id ?? "new"} data={props.data} form={props.form} busy={props.busy} onClose={props.onClose} onSave={props.onSave} />;
}

function MaterialDialogForm({ data, form, busy, onClose, onSave }: {
  data: Overview; form: Partial<Material>; busy: boolean; onClose: () => void; onSave: (payload: Record<string, unknown>) => void;
}) {
  const [f, setF] = useState<Record<string, any>>(() => ({
    classroomId: form.classroomId ?? "", subjectId: form.subjectId ?? "any", title: form.title ?? "",
    description: form.description ?? "", linkUrl: form.linkUrl ?? "", body: form.body ?? "",
  }));
  return (
    <Dialog open onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><LibraryBig className="size-4.5" /> {form.id ? "Edit study material" : "Share study material"}</DialogTitle>
          <DialogDescription>Share revision notes, past questions or reading links with a class (optional subject tag).</DialogDescription>
        </DialogHeader>
        <FormGrid cols={3}>
          <FormField label="Class" required>
            <Select value={f.classroomId} onValueChange={(v) => setF({ ...f, classroomId: v })}>
              <SelectTrigger><SelectValue placeholder="Choose class" /></SelectTrigger>
              <SelectContent>{data.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent>
            </Select>
          </FormField>
          <FormField label="Subject (optional)">
            <Select value={f.subjectId} onValueChange={(v) => setF({ ...f, subjectId: v })}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent><SelectItem value="any">General</SelectItem>{data.subjects.map((s) => <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>)}</SelectContent>
            </Select>
          </FormField>
          <FormField label="Title" required>
            <Input value={f.title} onChange={(e) => setF({ ...f, title: e.target.value })} placeholder="e.g. B4 Maths revision set 2" />
          </FormField>
          <FormField label="Description" span={3}><Input value={f.description} onChange={(e) => setF({ ...f, description: e.target.value })} /></FormField>
          <FormField label="Link (shared drive / website)" span={3}><Input value={f.linkUrl} onChange={(e) => setF({ ...f, linkUrl: e.target.value })} placeholder="https://…" /></FormField>
          <FormField label="Or inline notes" span={3} hint="Provide a link, inline notes, or both">
            <Textarea rows={5} value={f.body} onChange={(e) => setF({ ...f, body: e.target.value })} />
          </FormField>
        </FormGrid>
        <DialogFooter>
          <Button variant="outline" disabled={busy} onClick={onClose}>Cancel</Button>
          <Button disabled={busy} onClick={() => onSave({
            classroomId: f.classroomId, subjectId: f.subjectId === "any" ? null : f.subjectId,
            title: f.title, description: f.description || null, linkUrl: f.linkUrl || null, body: f.body || null,
          })}>Share material</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function Tile({ icon, label, value, sub, tone }: { icon: React.ReactNode; label: string; value: React.ReactNode; sub?: string; tone?: "good" | "warn" }) {
  return (
    <div className="rounded-xl border bg-card p-4">
      <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</span>{icon}</div>
      <div className={`mt-1.5 text-xl font-bold tabular-nums ${tone === "good" ? "text-emerald-600 dark:text-emerald-400" : tone === "warn" ? "text-amber-600 dark:text-amber-400" : ""}`}>{value}</div>
      <div className="text-[11px] text-muted-foreground truncate">{sub}</div>
    </div>
  );
}

// ══════════════════════════════════════════════════════════════
// Framework manager — enhanced CI3 curriculum_strands.php /
// curriculum_sub_strands.php / curriculum_content_standards.php /
// curriculum_learning_indicators.php / curriculum_import.php
// (four admin pages → one searchable tree + CSV import)
// ══════════════════════════════════════════════════════════════

const IMPORT_TEMPLATE = `curriculum,subjectName,gradeLevel,strand,strandCode,subStrand,subStrandCode,standard,standardCode,indicator,indicatorCode
GES,Mathematics,B4,Number,B4.1,"Number Operations (Addition, Subtraction, Multiplication and Division)",B4.1.2,Demonstrate understanding of addition and subtraction of whole numbers,B4.1.2.1,Demonstrate ability to add four-digit numbers with and without regrouping,B4.1.2.1.1
GES,Science,B5,Systems,B5.3,The human body systems,B5.3.1,Identify the parts of the human digestive system and their functions,B5.3.1.1,Draw and label the human digestive system,B5.3.1.1.1
CAIE,Mathematics,Stage 4,Number,,Numbers and the number system,,Read and order numbers up to 10000,,Count on and back in steps of different sizes,`;

function FrameworkTab({ schoolId, data, canEdit }: { schoolId: string; data: Overview; canEdit: boolean }) {
  const { toast } = useToast();
  const [meta, setMeta] = useState<FrameworkMeta | null>(null);
  const [curriculum, setCurriculum] = useState<string>("");
  const [subject, setSubject] = useState<string>("");
  const [grade, setGrade] = useState<string>("");
  const [tree, setTree] = useState<FrameworkNode[] | null>(null);
  const [busy, setBusy] = useState(false);
  const [edit, setEdit] = useState<{ level: "strand" | "sub" | "standard" | "indicator"; parentIds: Record<string, string | null>; row: any } | null>(null);
  const [importOpen, setImportOpen] = useState(false);

  // grade options = distinct classroom grade levels + whatever the library covers for this curriculum/subject
  const gradeOptions = useMemo(() => {
    const set = new Set<string>(data.classrooms.map((c) => c.gradeLevel.trim()).filter(Boolean));
    for (const c of meta?.coverage ?? []) if (c.curriculum === curriculum && (!subject || c.subjectName === subject)) set.add(c.gradeLevel);
    return [...set].sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));
  }, [data.classrooms, meta, curriculum, subject]);
  const subjectOptions = useMemo(() => {
    const names = new Set<string>(data.subjects.map((s) => s.name));
    for (const c of meta?.coverage ?? []) if (c.curriculum === curriculum) names.add(c.subjectName);
    return [...names].sort((a, b) => a.localeCompare(b));
  }, [data.subjects, meta, curriculum]);

  useEffect(() => {
    apiGet<FrameworkMeta>(`/api/school/${schoolId}/ops/curriculum-framework?meta=1`)
      .then((m) => {
        setMeta(m);
        setCurriculum((c) => c || m.primary || m.enabled[0] || "GES");
      })
      .catch((e) => toast({ title: "Could not load framework metadata", description: e.message, variant: "destructive" }));
  }, [schoolId, toast]);

  const loadTree = useCallback(() => {
    if (!curriculum || !subject || !grade) { setTree(null); return; }
    setTree(null);
    apiGet<{ strands: FrameworkNode[] }>(`/api/school/${schoolId}/ops/curriculum-framework?curriculum=${encodeURIComponent(curriculum)}&subjectName=${encodeURIComponent(subject)}&gradeLevel=${encodeURIComponent(grade)}`)
      .then((d) => setTree(d.strands))
      .catch((e) => { toast({ title: "Could not load framework", description: e.message, variant: "destructive" }); setTree([]); });
  }, [schoolId, curriculum, subject, grade, toast]);
  useEffect(() => { loadTree(); }, [loadTree]);

  async function fwPost(payload: Record<string, unknown>, okMsg: string) {
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}/ops/curriculum-framework`, payload);
      toast({ title: okMsg });
      loadTree();
      return true;
    } catch (e: any) {
      toast({ title: "Framework action failed", description: e.message, variant: "destructive" });
      return false;
    } finally { setBusy(false); }
  }

  const counts = useMemo(() => {
    let subs = 0, standards = 0, indicators = 0;
    for (const s of tree ?? []) {
      subs += (s.subStrands ?? []).length;
      for (const b of s.subStrands ?? []) {
        standards += (b.standards ?? []).length;
        for (const st of b.standards ?? []) indicators += (st.indicators ?? []).length;
      }
    }
    return { strands: (tree ?? []).length, subs, standards, indicators };
  }, [tree]);

  return (
    <TabsContent value="framework" className="space-y-4">
      <Card>
        <CardHeader className="pb-2">
          <CardTitle className="text-base flex flex-wrap items-center gap-2">
            <BookMarked className="size-4" /> Curriculum framework library
            <span className="text-xs font-normal text-muted-foreground">
              NaCCA strands · content standards · indicators — shared by all schools, extended here
            </span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid sm:grid-cols-3 gap-3 mb-3">
            <FormField label="Curriculum">
              <Select value={curriculum} onValueChange={(v) => { setCurriculum(v); }}>
                <SelectTrigger><SelectValue placeholder="Curriculum" /></SelectTrigger>
                <SelectContent>
                  {(meta?.enabled ?? []).map((c) => <SelectItem key={c} value={c}>{CURRICULA_MAP[c] ?? c}</SelectItem>)}
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="Subject">
              <Select value={subject} onValueChange={setSubject}>
                <SelectTrigger><SelectValue placeholder="Choose subject" /></SelectTrigger>
                <SelectContent>{subjectOptions.map((s) => <SelectItem key={s} value={s}>{s}</SelectItem>)}</SelectContent>
              </Select>
            </FormField>
            <FormField label="Grade / stage">
              <Select value={grade} onValueChange={setGrade}>
                <SelectTrigger><SelectValue placeholder="Choose grade" /></SelectTrigger>
                <SelectContent>{gradeOptions.map((g) => <SelectItem key={g} value={g}>{g}</SelectItem>)}</SelectContent>
              </Select>
            </FormField>
          </div>

          {canEdit && (
            <div className="flex flex-wrap gap-2 mb-3">
              <Button size="sm" variant="outline" className="gap-1.5" disabled={!curriculum || !subject || !grade || busy} onClick={() => setEdit({ level: "strand", parentIds: {}, row: null })}>
                <Plus className="size-4" /> Add strand
              </Button>
              <Button size="sm" variant="outline" className="gap-1.5" onClick={() => setImportOpen(true)}>
                <FileText className="size-4" /> Import CSV
              </Button>
              <Button size="sm" variant="outline" className="gap-1.5" disabled={busy} onClick={async () => {
                await navigator.clipboard.writeText(IMPORT_TEMPLATE).catch(() => {});
                toast({ title: "Import template copied", description: "Paste it into the CSV import dialog to see the shape." });
              }}>
                <Copy className="size-4" /> Copy CSV template
              </Button>
            </div>
          )}

          {!subject || !grade ? (
            <EmptyState icon={<BookMarked className="size-8" />} title="Pick a subject and grade" hint="The framework is organised per curriculum, subject and grade — e.g. GES Mathematics B4." />
          ) : tree === null ? (
            <LoadingBlock label="Loading framework…" />
          ) : tree.length === 0 ? (
            <EmptyState icon={<BookMarked className="size-8" />} title={`No strands for ${subject} · ${grade} (${CURRICULA_MAP[curriculum] ?? curriculum})`} hint={canEdit ? "Add the strands manually or import them from a NaCCA / Cambridge export." : "Ask your curriculum lead to add the strands, or import them from CSV."} />
          ) : (
            <>
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                <Tile icon={<BookMarked className="size-4" />} label="Strands" value={counts.strands} />
                <Tile icon={<ListChecks className="size-4" />} label="Sub-strands" value={counts.subs} />
                <Tile icon={<FileText className="size-4" />} label="Content standards" value={counts.standards} />
                <Tile icon={<CheckCircle2 className="size-4" />} label="Indicators" value={counts.indicators} />
              </div>
              <div className="rounded-xl border divide-y">
                {tree.map((s) => (
                  <div key={s.id} className="p-3">
                    <div className="flex items-start justify-between gap-2">
                      <div>
                        <div className="font-semibold text-sm">{s.code ? <span className="text-muted-foreground mr-1.5 tabular-nums">{s.code}</span> : null}{s.name}</div>
                        <div className="text-[11px] text-muted-foreground">{(s.subStrands ?? []).length} sub-strand{(s.subStrands ?? []).length === 1 ? "" : "s"}</div>
                      </div>
                      <div className="flex gap-1 shrink-0">
                        {canEdit && (
                          <>
                            <Button size="sm" variant="ghost" className="h-7 gap-1 text-xs" onClick={() => setEdit({ level: "sub", parentIds: { strandId: s.id }, row: null })}><Plus className="size-3.5" /> Sub-strand</Button>
                            <Button size="sm" variant="ghost" className="h-7 px-1.5" onClick={() => setEdit({ level: "strand", parentIds: {}, row: s })}><Pencil className="size-3.5" /></Button>
                            <Button size="sm" variant="ghost" className="h-7 px-1.5 text-destructive" onClick={() => { if (confirm(`Delete strand “${s.name}” and everything under it?`)) fwPost({ action: "strand.delete", id: s.id }, "Strand deleted"); }}><Trash2 className="size-3.5" /></Button>
                          </>
                        )}
                      </div>
                    </div>
                    {(s.subStrands ?? []).length > 0 && (
                      <div className="mt-2 ml-4 border-l pl-3 space-y-2">
                        {(s.subStrands ?? []).map((b) => (
                          <div key={b.id}>
                            <div className="flex items-start justify-between gap-2">
                              <div className="text-sm">
                                <span className="font-medium">{b.code ? <span className="text-muted-foreground mr-1.5 tabular-nums">{b.code}</span> : null}{b.name}</span>
                                <span className="text-[11px] text-muted-foreground ml-2">{(b.standards ?? []).length} standard{(b.standards ?? []).length === 1 ? "" : "s"}</span>
                              </div>
                              <div className="flex gap-1 shrink-0">
                                {canEdit && (
                                  <>
                                    <Button size="sm" variant="ghost" className="h-6 gap-1 text-[11px] px-1.5" onClick={() => setEdit({ level: "standard", parentIds: { strandId: s.id, subStrandId: b.id }, row: null })}><Plus className="size-3" /> Standard</Button>
                                    <Button size="sm" variant="ghost" className="h-6 px-1" onClick={() => setEdit({ level: "sub", parentIds: { strandId: s.id }, row: b })}><Pencil className="size-3" /></Button>
                                    <Button size="sm" variant="ghost" className="h-6 px-1 text-destructive" onClick={() => { if (confirm(`Delete sub-strand “${b.name}”?`)) fwPost({ action: "sub.delete", id: b.id }, "Sub-strand deleted"); }}><Trash2 className="size-3" /></Button>
                                  </>
                                )}
                              </div>
                            </div>
                            {(b.standards ?? []).length > 0 && (
                              <div className="mt-1.5 ml-3 border-l pl-3 space-y-1.5">
                                {(b.standards ?? []).map((st) => (
                                  <div key={st.id}>
                                    <div className="flex items-start justify-between gap-2">
                                      <div className="text-[13px]">
                                        {st.code ? <span className="text-muted-foreground mr-1.5 tabular-nums">{st.code}</span> : null}{st.statement}
                                      </div>
                                      <div className="flex gap-1 shrink-0">
                                        {canEdit && (
                                          <>
                                            <Button size="sm" variant="ghost" className="h-6 gap-1 text-[11px] px-1.5" onClick={() => setEdit({ level: "indicator", parentIds: { strandId: s.id, subStrandId: b.id, standardId: st.id }, row: null })}><Plus className="size-3" /> Indicator</Button>
                                            <Button size="sm" variant="ghost" className="h-6 px-1" onClick={() => setEdit({ level: "standard", parentIds: { subStrandId: b.id }, row: st })}><Pencil className="size-3" /></Button>
                                            <Button size="sm" variant="ghost" className="h-6 px-1 text-destructive" onClick={() => { if (confirm("Delete this content standard and its indicators?")) fwPost({ action: "standard.delete", id: st.id }, "Content standard deleted"); }}><Trash2 className="size-3" /></Button>
                                          </>
                                        )}
                                      </div>
                                    </div>
                                    {(st.indicators ?? []).length > 0 && (
                                      <ul className="mt-1 ml-4 border-l pl-3 space-y-1">
                                        {(st.indicators ?? []).map((ind) => (
                                          <li key={ind.id} className="text-xs flex items-start justify-between gap-2">
                                            <span>{ind.code ? <span className="text-muted-foreground mr-1.5 tabular-nums">{ind.code}</span> : null}{ind.statement}</span>
                                            {canEdit && (
                                              <span className="flex gap-1 shrink-0">
                                                <Button size="sm" variant="ghost" className="h-5 w-5 p-0" onClick={() => setEdit({ level: "indicator", parentIds: { standardId: st.id }, row: ind })}><Pencil className="size-3" /></Button>
                                                <Button size="sm" variant="ghost" className="h-5 w-5 p-0 text-destructive" onClick={() => fwPost({ action: "indicator.delete", id: ind.id }, "Indicator deleted")}><Trash2 className="size-3" /></Button>
                                              </span>
                                            )}
                                          </li>
                                        ))}
                                      </ul>
                                    )}
                                  </div>
                                ))}
                              </div>
                            )}
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </>
          )}
        </CardContent>
      </Card>

      {/* ── strand / sub / standard / indicator editor ── */}
      {edit && (
        <FrameworkEditDialog
          edit={edit} busy={busy} curriculum={curriculum} subjectName={subject} gradeLevel={grade}
          onClose={() => setEdit(null)}
          onSave={async (payload, level) => {
            const ok = await fwPost({ action: `${level}.save`, ...payload }, "Saved to framework");
            if (ok) setEdit(null);
          }}
        />
      )}

      {/* ── CSV import ── */}
      <Dialog open={importOpen} onOpenChange={(o) => !o && setImportOpen(false)}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><FileText className="size-4.5" /> Import framework (CSV)</DialogTitle>
            <DialogDescription>
              One row per line, upserted into the shared library — blank lower levels are skipped, so strand-only rows, standard-only rows and full indicator rows all import.
            </DialogDescription>
          </DialogHeader>
          <Textarea rows={8} defaultValue={IMPORT_TEMPLATE} className="font-mono text-xs" id="fw-import-csv" />
          <p className="text-xs text-muted-foreground">
            Columns: curriculum, subjectName, gradeLevel, strand, strandCode, subStrand, subStrandCode, standard, standardCode, indicator, indicatorCode
          </p>
          <DialogFooter>
            <Button variant="outline" onClick={() => setImportOpen(false)}>Cancel</Button>
            <Button disabled={busy} onClick={async () => {
              const csv = (document.getElementById("fw-import-csv") as HTMLTextAreaElement)?.value ?? "";
              setBusy(true);
              try {
                const r = await apiPost<{ imported: number; skipped: number }>(`/api/school/${schoolId}/ops/curriculum-framework`, { action: "framework.import", csv });
                toast({ title: `Imported ${r.imported} row${r.imported === 1 ? "" : "s"}`, description: r.skipped ? `${r.skipped} skipped (missing curriculum/subject/grade/strand)` : undefined });
                setImportOpen(false);
                loadTree();
              } catch (e: any) {
                toast({ title: "Import failed", description: e.message, variant: "destructive" });
              } finally { setBusy(false); }
            }}>Import rows</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </TabsContent>
  );
}

const CURRICULA_MAP: Record<string, string> = { GES: "GES — NaCCA SBC/CCP", CAIE: "Cambridge — CAIE", IB: "IB — PYP/MYP/DP" };

function FrameworkEditDialog({ edit, busy, curriculum, subjectName, gradeLevel, onClose, onSave }: {
  edit: { level: "strand" | "sub" | "standard" | "indicator"; parentIds: Record<string, string | null>; row: any };
  busy: boolean; curriculum: string; subjectName: string; gradeLevel: string;
  onClose: () => void; onSave: (payload: Record<string, unknown>, level: string) => Promise<void>;
}) {
  const { level, row, parentIds } = edit;
  const [f, setF] = useState<Record<string, any>>({
    name: row?.name ?? "", code: row?.code ?? "", statement: row?.statement ?? "",
  });
  const label = level === "strand" ? "Strand" : level === "sub" ? "Sub-strand" : level === "standard" ? "Content standard" : "Learning indicator";
  return (
    <Dialog open onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-w-xl">
        <DialogHeader>
          <DialogTitle>{row ? `Edit ${label.toLowerCase()}` : `Add ${label.toLowerCase()}`}</DialogTitle>
          <DialogDescription>{CURRICULA_MAP[curriculum] ?? curriculum} · {subjectName} · {gradeLevel}</DialogDescription>
        </DialogHeader>
        <FormGrid cols={2}>
          {level === "standard" || level === "indicator" ? (
            <FormField label={label} required span={2} hint={level === "standard" ? "What learners should know / be able to do" : "A measurable step towards the standard"}>
              <Textarea rows={3} value={f.statement} onChange={(e) => setF({ ...f, statement: e.target.value })} />
            </FormField>
          ) : (
            <FormField label={label} required span={2}>
              <Input value={f.name} onChange={(e) => setF({ ...f, name: e.target.value })} />
            </FormField>
          )}
          <FormField label="Reference code" hint="e.g. B4.1.2.1 — NaCCA / Cambridge reference number">
            <Input value={f.code ?? ""} onChange={(e) => setF({ ...f, code: e.target.value })} />
          </FormField>
        </FormGrid>
        <DialogFooter>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button disabled={busy} onClick={() => onSave({
            ...(row?.id ? { id: row.id } : {}),
            ...parentIds,
            curriculum, subjectName, gradeLevel,
            name: f.name, statement: f.statement, code: f.code,
          }, level)}>{row ? "Save changes" : `Add ${label.toLowerCase()}`}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

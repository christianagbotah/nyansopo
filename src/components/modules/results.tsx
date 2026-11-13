"use client";
import { useEffect, useMemo, useState } from "react";
import { useStore, type NavTarget } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { canAny } from "@/lib/rbac";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import {
  AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription,
  AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Checkbox } from "@/components/ui/checkbox";
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid, Cell } from "recharts";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Skeleton } from "@/components/ui/skeleton";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard, StatusBadge } from "@/components/ui-helpers";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  Award, Calculator, Eye, GraduationCap, Printer, Users, BookOpen, TrendingUp, ClipboardCheck,
  Percent, Layers, RefreshCcw, ScrollText, Send, Undo2, Megaphone, BarChart3, Mail, Phone, PhoneOff,
  MailWarning, Gauge, ChevronDown, ChevronUp, BadgeCheck, AlertTriangle,
} from "lucide-react";

/* ══════════════════════════════════════════════════════════════
   Results & Report Cards — wave 20 + wave 21 (Ghana BECE style).

   One screen for the super-admin / principal:
   • pick a term + class and read the whole results ledger as a
     student × subject grid — every cell graded on the WAEC BECE
     band with the red/amber/green doctrine (green ≥65, amber
     50–64, red <50);
   • "Compute term results" rolls the Gradebook marks up into the
     TermResult ledger (class 30% + exam 70%), competition-ranks
     every subject and the class, compiles report cards with the
     best-6 BECE aggregate, attendance % and remarks;
   • publish / recall (wave 21) — flip the whole class-term to
     published once Results Control has approved it, so the
     parent & learner portals show it; recall pulls it back;
   • every learner's Ghana-format terminal report prints straight
     from the browser — school header, grades table, remarks and
     signature lines — one sheet or the whole class set.
   ══════════════════════════════════════════════════════════════ */

type SchoolInfo = {
  id: string; name: string; code: string; motto: string | null; town: string; region: string;
  district: string; gpsAddress: string | null; phone: string | null; email: string | null;
  logoColor: string; curriculum: string;
};
type TermLite = { id: string; name: string; sequence: number; yearName: string; isCurrent: boolean };
type ClassLite = { id: string; name: string; gradeLevel: string };
type StudentLite = { id: string; name: string; indexNo: string; gender: string };
type CardLite = {
  id: string; studentId: string; studentName: string; indexNo: string; classroomName: string;
  totalScore: number | null; average: number | null; classPosition: number | null; classSize: number | null;
  aggregate: number | null; attendancePct: number | null; promoted: boolean | null; published: boolean;
  teacherRemark: string | null; headRemark: string | null;
};
type RowLite = {
  id: string; studentId: string; subjectId: string; subjectName: string; subjectCode: string;
  classScore: number | null; examScore: number | null; total: number | null; grade: string | null;
  position: number | null; remarks: string | null; published: boolean;
};
type ListPayload = {
  school: SchoolInfo; terms: TermLite[]; classrooms: ClassLite[]; students: StudentLite[];
  termId: string | null; classId: string | null; cards: CardLite[]; results: RowLite[];
};

type DetailRow = {
  id: string; subjectName: string; subjectCode: string | null; classScore: number | null;
  examScore: number | null; total: number | null; grade: string | null; position: number | null; remarks: string | null;
};
type DetailPayload = {
  school: SchoolInfo;
  classTeacher: string | null;
  classroom: { id: string; name: string } | null;
  card: {
    id: string; classroomName: string;
    term: { id: string; name: string; sequence: number; yearName: string };
    published: boolean; publishedAt: string | null;
    totalScore: number | null; average: number | null; classPosition: number | null; classSize: number | null;
    aggregate: number | null; attendance: string | null; attendancePct: number | null;
    conduct: string | null; interest: string | null;
    teacherRemark: string | null; headRemark: string | null;
    promotion: string | null; promoted: boolean | null;
  };
  student: {
    id: string; firstName: string; lastName: string; otherNames: string | null; nyansapoIndex: string;
    gender: string; dateOfBirth: string | null; guardianName: string; guardianPhone: string;
  };
  rows: DetailRow[];
  legacyResults: { subjectName: string; classScore: number | null; examScore: number | null; total: number | null; grade: string | null; remark: string | null; position: number | null }[];
};

/* wave 21 — publish + transcript payloads */
type PublishPayload = {
  ok: true; publish: boolean; results: number; cards: number;
  term: { name: string; yearName: string };
  classroom: { name: string };
};
type TranscriptTerm = {
  termId: string; termName: string; yearName: string; classroomName: string;
  cardId: string | null;
  totalScore: number | null; average: number | null;
  classPosition: number | null; classSize: number | null;
  aggregate: number | null; attendancePct: number | null;
  promoted: boolean | null; published: boolean;
  teacherRemark: string | null; headRemark: string | null;
  rows: { subjectName: string; subjectCode: string | null; classScore: number | null; examScore: number | null; total: number | null; grade: string | null; position: number | null; remark: string | null }[];
};
type TranscriptPayload = {
  school: SchoolInfo;
  student: { id: string; firstName: string; lastName: string; otherNames: string | null; nyansapoIndex: string; gender: string | null; dateOfBirth: string | null };
  terms: TranscriptTerm[];
  summary: { terms: number; bestAggregate: number | null; overallAverage: number | null };
};

/* ── wave 22 — term analytics payload (/api/results/analytics) ── */
type GradeCount = { grade: string; count: number; tone: string };
type SubjectAnalytics = {
  subjectId: string; name: string; code: string;
  entries: number;
  average: number | null;
  passRate: number | null;
  grades: GradeCount[];
  top: { name: string; total: number } | null;
  weak: { name: string; total: number } | null;
};
type AnalyticsPayload = {
  school: { id: string; name: string; code: string };
  term: { id: string; name: string; yearName: string };
  classroom: { id: string; name: string };
  subjects: SubjectAnalytics[];
  summary: {
    learners: number; published: number; subjects: number; entries: number;
    average: number | null; passRate: number | null;
    distinctions: number; belowBar: number;
    grades: GradeCount[];
    top: { name: string; average: number | null; position: number | null }[];
    bottom: { name: string; average: number | null; position: number | null }[];
    boys: { count: number; average: number | null };
    girls: { count: number; average: number | null };
  };
  weakSubjects: { name: string; passRate: number }[];
};

/* ── wave 22 — results release notifications (/api/results/notify) ── */
type NotifyRow = {
  studentId: string; studentName: string; indexNo: string;
  guardianName: string; guardianPhone: string; guardianEmail: string | null;
  average: number | null; classPosition: number | null; classSize: number | null;
  aggregate: number | null; attendancePct: number | null; promoted: boolean | null;
  message: string;
};
type NotifyPreview = {
  preview: true;
  rows: NotifyRow[];
  draftCount: number;
  rule: { id: string; name: string; body: string } | null;
  withPhone: number; withoutPhone: number;
  withEmail: number; withoutEmail: number;
  channel: "SMS" | "EMAIL" | "BOTH";
  sms: { provider: string; credits: number; senderId: string | null };
  email: { enabled: boolean; host: string | null; fromName: string | null };
};
type NotifyResult = {
  ok: true; channel: string; learners: number; draftCount: number;
  sms: { sent: number; failed: number; noPhone: number };
  email: { sent: number; failed: number; simulated: number; noAddress: number };
  rule: { name: string } | null;
};

/* ── colour doctrine: green ≥65 · amber 50–64 · red <50 ── */
const bandTone = (total: number | null | undefined) =>
  total === null || total === undefined
    ? { cell: "text-muted-foreground", badge: "bg-secondary text-secondary-foreground border-transparent" }
    : total >= 65
      ? { cell: "text-emerald-700 dark:text-emerald-300", badge: "bg-emerald-100 text-emerald-800 border-transparent dark:bg-emerald-950 dark:text-emerald-300" }
      : total >= 50
        ? { cell: "text-amber-700 dark:text-amber-300", badge: "bg-amber-100 text-amber-800 border-transparent dark:bg-amber-950 dark:text-amber-300" }
        : { cell: "text-red-700 dark:text-red-300", badge: "bg-red-100 text-red-800 border-transparent dark:bg-red-950 dark:text-red-300" };

const ordinal = (n: number | null | undefined) => {
  if (n === null || n === undefined) return "—";
  const s = ["th", "st", "nd", "rd"];
  const v = n % 100;
  return `${n}${s[(v - 20) % 10] ?? s[v] ?? s[0]}`;
};
const fmt1 = (n: number | null | undefined) => (n === null || n === undefined ? "—" : (Math.round(n * 10) / 10).toFixed(1).replace(/\.0$/, ""));
const initials = (name: string) => name.split(/\s+/).map((w) => w[0]).slice(0, 2).join("").toUpperCase();

export function Results() {
  const { currentSchoolId, session, navTarget } = useStore();
  const canPublish = canAny(session?.permissions, ["reports.publish"]);
  const canNotify = canAny(session?.permissions, ["reports.publish", "sms.send"]);

  const [meta, setMeta] = useState<{ school: SchoolInfo; terms: TermLite[]; classrooms: ClassLite[] } | null>(null);
  const [data, setData] = useState<ListPayload | null>(null);
  const [termId, setTermId] = useState<string | null>(null);
  const [classId, setClassId] = useState<string | null>(null);
  const [refresh, setRefresh] = useState(0);
  const [gridLoading, setGridLoading] = useState(false);
  const [busy, setBusy] = useState(false);
  const [detail, setDetail] = useState<DetailPayload | null>(null);
  const [detailOpen, setDetailOpen] = useState(false);
  const [bulkOpen, setBulkOpen] = useState(false);
  /* wave 21 — publish / recall + transcript */
  const [approvalStatus, setApprovalStatus] = useState<string>("DRAFT");
  const [pubBusy, setPubBusy] = useState(false);
  const [confirmPub, setConfirmPub] = useState<null | "publish" | "recall">(null);
  const [transcript, setTranscript] = useState<TranscriptPayload | null>(null);
  const [transcriptOpen, setTranscriptOpen] = useState(false);
  const [transcriptLoading, setTranscriptLoading] = useState(false);
  /* wave 22 — analytics tab + results release notifications */
  const [tab, setTab] = useState("grid");
  const [analytics, setAnalytics] = useState<AnalyticsPayload | null>(null);
  const [analyticsLoading, setAnalyticsLoading] = useState(false);
  const [notifyOpen, setNotifyOpen] = useState(false);
  const [notifyChannel, setNotifyChannel] = useState<"SMS" | "EMAIL" | "BOTH">("SMS");
  const [notifyPrev, setNotifyPrev] = useState<NotifyPreview | null>(null);
  const [notifyBusy, setNotifyBusy] = useState(false);
  const [pubNotify, setPubNotify] = useState(false);

  /* iEduPro nested-nav parity: deep-link the analytics tab */
  const [appliedTarget, setAppliedTarget] = useState<NavTarget | null>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab === "analytics") setTab("analytics");
  }

  /* bootstrap — resolve the school's terms/classes and default selectors
     (current term; the JHS 3 demo class when present, else the first) */
  useEffect(() => {
    if (!currentSchoolId) return;
    apiGet<ListPayload>(`/api/results/report-cards?schoolId=${currentSchoolId}`)
      .then((d) => {
        setMeta({ school: d.school, terms: d.terms, classrooms: d.classrooms });
        setTermId(d.termId);
        setClassId(d.classrooms.find((x) => x.name.includes("JHS 3"))?.id ?? d.classrooms[0]?.id ?? null);
      })
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  }, [currentSchoolId]);

  /* grid — refetches whenever the selectors change or a compile completes */
  useEffect(() => {
    if (!currentSchoolId || !termId || !classId) return;
    let alive = true;
    setGridLoading(true);
    apiGet<ListPayload>(`/api/results/report-cards?schoolId=${currentSchoolId}&termId=${termId}&classId=${classId}`)
      .then((d) => { if (alive) { setData(d); setGridLoading(false); } })
      .catch((e) => {
        if (alive) {
          setGridLoading(false);
          toast({ title: "Load failed", description: e.message, variant: "destructive" });
        }
      });
    return () => { alive = false; };
  }, [currentSchoolId, termId, classId, refresh]);

  /** Compile term results — rolls Gradebook marks into the TermResult
   *  ledger, ranks the class and compiles report cards. */
  async function compute() {
    if (!currentSchoolId || !termId || !classId) { toast({ title: "Choose a term and a class first" }); return; }
    setBusy(true);
    toast({ title: "Compiling term results…", description: "Rolling up class scores, exams, ranks, aggregates and report cards." });
    try {
      const res = await apiPost<{ students: number; subjects: number; results: number; cards: number; aggregates: number; classroom: { name: string }; term: { name: string; yearName: string } }>(
        "/api/results/compute",
        { schoolId: currentSchoolId, termId, classId },
      );
      toast({
        title: "Results compiled",
        description: `${res.classroom.name} · ${res.term.name} (${res.term.yearName}): ${res.students} learners, ${res.subjects} subjects, ${res.results} subject results, ${res.cards} report cards${res.aggregates ? `, ${res.aggregates} BECE aggregates` : ""}.`,
      });
      setRefresh((r) => r + 1);
    } catch (e: any) {
      toast({ title: "Compute failed", description: e.message, variant: "destructive" });
    } finally {
      setBusy(false);
    }
  }

  async function openCard(id: string) {
    if (!currentSchoolId) return;
    setDetail(null);
    setDetailOpen(true);
    try {
      const d = await apiGet<DetailPayload>(`/api/results/report-cards/${id}?schoolId=${currentSchoolId}`);
      setDetail(d);
    } catch (e: any) {
      toast({ title: "Could not open report card", description: e.message, variant: "destructive" });
      setDetailOpen(false);
    }
  }

  /* wave 21 — Results Control status of the selected class-term.
     Publishing requires APPROVED or LOCKED; DRAFT/SUBMITTED classes
     are refused, so the chip + button hint keep the flow honest. */
  useEffect(() => {
    if (!currentSchoolId || !termId) return;
    let alive = true;
    apiGet<{ rows: { classroomId: string; status: string }[] }>(`/api/school/${currentSchoolId}/ops/academic-control?termId=${termId}`)
      .then((d) => { if (alive) setApprovalStatus(d.rows.find((r) => r.classroomId === classId)?.status ?? "DRAFT"); })
      .catch(() => { if (alive) setApprovalStatus("DRAFT"); });
    return () => { alive = false; };
  }, [currentSchoolId, termId, classId, refresh]);

  /** Publish / recall the whole class-term (wave 21). */
  async function publishAction(kind: "publish" | "recall") {
    if (!currentSchoolId || !termId || !classId) { toast({ title: "Choose a term and a class first" }); return; }
    setPubBusy(true);
    try {
      const res = await apiPost<PublishPayload & { notified: { gate: "ON" | "OFF"; sent: number; failed: number; skipped: number } | null }>("/api/results/publish", {
        schoolId: currentSchoolId, termId, classId, publish: kind === "publish",
        notify: kind === "publish" ? pubNotify : undefined,
      });
      toast({
        title: kind === "publish" ? "Results published" : "Results recalled to draft",
        description: kind === "publish"
          ? `${res.classroom.name} · ${res.term.name} (${res.term.yearName}): ${res.cards} report card${res.cards === 1 ? "" : "s"} and ${res.results} subject result${res.results === 1 ? "" : "s"} are now visible to learners and parents.`
            + (res.notified
              ? res.notified.gate === "OFF"
                ? " Guardian SMS automation is switched off in Settings → Notifications — nothing was texted (use “Notify parents” for a manual blast)."
                : ` Guardian SMS: ${res.notified.sent} sent${res.notified.failed ? `, ${res.notified.failed} failed` : ""}${res.notified.skipped ? `, ${res.notified.skipped} without a phone` : ""}.`
              : "")
          : `${res.classroom.name} · ${res.term.name}: ${res.cards} report card${res.cards === 1 ? "" : "s"} pulled back to draft — learners and parents no longer see them.`,
      });
      setConfirmPub(null);
      setPubNotify(false);
      setRefresh((r) => r + 1);
    } catch (e: any) {
      toast({ title: kind === "publish" ? "Publish failed" : "Recall failed", description: e.message, variant: "destructive" });
    } finally {
      setPubBusy(false);
    }
  }

  /* wave 22 — results release notifications: preview the exact message
     each guardian will receive, then blast through the school's channels. */
  async function openNotify() {
    if (!currentSchoolId || !termId || !classId) { toast({ title: "Choose a term and a class first" }); return; }
    setNotifyOpen(true);
    setNotifyPrev(null);
    try {
      const d = await apiPost<NotifyPreview>("/api/results/notify", {
        schoolId: currentSchoolId, termId, classId, channel: notifyChannel, preview: true,
      });
      setNotifyPrev(d);
    } catch (e: any) {
      setNotifyOpen(false);
      toast({ title: "Could not draft notifications", description: e.message, variant: "destructive" });
    }
  }

  async function sendNotify() {
    if (!currentSchoolId || !termId || !classId) return;
    setNotifyBusy(true);
    try {
      const res = await apiPost<NotifyResult>("/api/results/notify", {
        schoolId: currentSchoolId, termId, classId, channel: notifyChannel,
      });
      const bits: string[] = [];
      if (res.channel !== "EMAIL") bits.push(`SMS: ${res.sms.sent} sent${res.sms.failed ? `, ${res.sms.failed} failed` : ""}${res.sms.noPhone ? `, ${res.sms.noPhone} without a phone` : ""}`);
      if (res.channel !== "SMS") bits.push(`Email: ${res.email.sent ? `${res.email.sent} sent` : ""}${res.email.simulated ? `${res.email.sent ? ", " : ""}${res.email.simulated} simulated (SMTP off — logged)` : ""}${res.email.failed ? `, ${res.email.failed} failed` : ""}${res.email.noAddress ? `, ${res.email.noAddress} without an address` : ""}`.replace(/^Email: ,/, "Email: none sent"));
      toast({
        title: `Results release notifications — ${res.learners} learner${res.learners === 1 ? "" : "s"}`,
        description: (bits.join(" · ") || "Nothing was sent — check guardian contacts.") + (res.rule ? ` Template: “${res.rule.name}”.` : ""),
      });
      setNotifyOpen(false);
      setNotifyPrev(null);
    } catch (e: any) {
      toast({ title: "Notify failed", description: e.message, variant: "destructive" });
    } finally {
      setNotifyBusy(false);
    }
  }

  /** Open the printable multi-term transcript (wave 21). */
  async function openTranscript(studentId: string) {
    if (!currentSchoolId) return;
    setTranscript(null);
    setTranscriptLoading(true);
    setTranscriptOpen(true);
    try {
      const d = await apiGet<TranscriptPayload>(`/api/results/transcript?schoolId=${currentSchoolId}&studentId=${studentId}`);
      setTranscript(d);
    } catch (e: any) {
      toast({ title: "Could not open transcript", description: e.message, variant: "destructive" });
      setTranscriptOpen(false);
    } finally {
      setTranscriptLoading(false);
    }
  }

  /* wave 22 — term analytics payload, fetched when the analytics tab is
     open for the selected class-term (recomputed results refresh it) */
  useEffect(() => {
    if (!currentSchoolId || !termId || !classId || tab !== "analytics") return;
    let alive = true;
    setAnalyticsLoading(true);
    apiGet<AnalyticsPayload>(`/api/results/analytics?schoolId=${currentSchoolId}&termId=${termId}&classId=${classId}`)
      .then((d) => { if (alive) { setAnalytics(d); setAnalyticsLoading(false); } })
      .catch((e) => {
        if (alive) {
          setAnalyticsLoading(false);
          toast({ title: "Could not load analytics", description: e.message, variant: "destructive" });
        }
      });
    return () => { alive = false; };
  }, [currentSchoolId, termId, classId, tab, refresh]);

  /* ── derived grid data ── */
  const subjects = useMemo(() => {
    const map = new Map<string, { id: string; name: string; code: string }>();
    for (const r of data?.results ?? []) if (!map.has(r.subjectId)) map.set(r.subjectId, { id: r.subjectId, name: r.subjectName, code: r.subjectCode });
    return [...map.values()].sort((a, b) => a.name.localeCompare(b.name));
  }, [data]);

  const students = useMemo(() => data?.students ?? [], [data]);

  const cellByKey = useMemo(() => {
    const m = new Map<string, RowLite>();
    for (const r of data?.results ?? []) m.set(`${r.studentId}:${r.subjectId}`, r);
    return m;
  }, [data]);

  const cardByStudent = useMemo(() => {
    const m = new Map<string, CardLite>();
    for (const c of data?.cards ?? []) m.set(c.studentId, c);
    return m;
  }, [data]);

  const kpis = useMemo(() => {
    const cards = data?.cards ?? [];
    const averages = cards.map((c) => c.average).filter((a): a is number => a !== null);
    const classAvg = averages.length ? averages.reduce((s, a) => s + a, 0) / averages.length : null;
    return {
      learners: students.length,
      subjects: subjects.length,
      results: data?.results.length ?? 0,
      classAvg,
      compiled: cards.length,
      published: cards.filter((c) => c.published).length,
    };
  }, [data, subjects, students]);

  /* wave 21 — publication gate: publishing needs Results Control approval */
  const gateOpen = approvalStatus === "APPROVED" || approvalStatus === "LOCKED";

  if (!meta) return <LoadingBlock label="Loading results…" />;

  const termLabel = meta.terms.find((t) => t.id === termId);
  const hasData = (data?.cards.length ?? 0) > 0 || (data?.results.length ?? 0) > 0;

  return (
    <div className="space-y-4">
      <ModuleHeader
        title="Results & Report Cards"
        description="Compile term results Ghana-style — class assessment 30% + exam 70%, WAEC BECE grade bands, competition ranking and the best-6 aggregate — then print terminal report sheets for one learner or the whole class."
        actions={
          <div className="flex flex-wrap gap-2">
            <Button size="sm" variant="outline" className="gap-1.5" disabled={!data?.cards.length} onClick={() => setBulkOpen(true)}>
              <Layers className="size-4" /> Print class set
            </Button>
            <Button size="sm" className="gap-1.5" onClick={compute} disabled={!canPublish || busy || !termId || !classId}
              title={canPublish ? "Compile the term results ledger for this class" : "You need the generate & publish reports permission"}>
              <Calculator className={cn("size-4", busy && "animate-pulse")} /> {busy ? "Compiling…" : "Compute term results"}
            </Button>
            {/* wave 21 — publication, gated on Results Control approval */}
            {canPublish && kpis.compiled > 0 && kpis.published < kpis.compiled && (
              <Button size="sm" className="gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white" disabled={pubBusy || !gateOpen}
                onClick={() => setConfirmPub("publish")}
                title={gateOpen
                  ? `Publish ${kpis.compiled} report card${kpis.compiled === 1 ? "" : "s"} to learners and parents`
                  : `Results are ${approvalStatus.toLowerCase()} — submit and approve them in Results Control (Exams & Reports) first`}>
                <Send className={cn("size-4", pubBusy && "animate-pulse")} /> {pubBusy ? "Working…" : "Publish results"}
              </Button>
            )}
            {canPublish && kpis.published > 0 && (
              <Button size="sm" variant="outline" className="gap-1.5 border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-950"
                disabled={pubBusy} onClick={() => setConfirmPub("recall")}
                title={`Pull ${kpis.published} published card${kpis.published === 1 ? "" : "s"} back to draft`}>
                <Undo2 className="size-4" /> Recall to draft
              </Button>
            )}
            {/* wave 22 — manual guardian notification blast (CI3 exam_marks_sms
                modernised): preview first, then SMS/email the terminal summary */}
            {canNotify && kpis.published > 0 && (
              <Button size="sm" variant="outline" className="gap-1.5" onClick={openNotify}
                title={`Text/email the ${kpis.published} published learner${kpis.published === 1 ? "" : "s"}'s guardians with their terminal summary`}>
                <Megaphone className="size-4" /> Notify parents
              </Button>
            )}
          </div>
        }
      />

      {/* selectors */}
      <div className="flex flex-wrap items-center gap-2">
        <Select value={termId ?? ""} onValueChange={setTermId}>
          <SelectTrigger className="w-56" aria-label="Term"><SelectValue placeholder="Term" /></SelectTrigger>
          <SelectContent>
            {meta.terms.map((t) => (
              <SelectItem key={t.id} value={t.id}>{t.name} · {t.yearName}{t.isCurrent ? " (current)" : ""}</SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Select value={classId ?? ""} onValueChange={setClassId}>
          <SelectTrigger className="w-52" aria-label="Class"><SelectValue placeholder="Class" /></SelectTrigger>
          <SelectContent>
            {meta.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
          </SelectContent>
        </Select>
        {termLabel && <Badge variant="outline" className="text-[11px]">{termLabel.name} · {termLabel.yearName}</Badge>}
        {/* wave 21 — Results Control state of this class-term */}
        {hasData && (
          <span className="inline-flex items-center gap-1.5 text-[11px] text-muted-foreground">
            Results control:
            <StatusBadge status={approvalStatus} />
          </span>
        )}
        <span className="ml-auto hidden lg:flex items-center gap-2 text-[11px] text-muted-foreground">
          <span className="inline-flex items-center gap-1"><span className="size-2 rounded-full bg-emerald-500" /> ≥65</span>
          <span className="inline-flex items-center gap-1"><span className="size-2 rounded-full bg-amber-500" /> 50–64</span>
          <span className="inline-flex items-center gap-1"><span className="size-2 rounded-full bg-red-500" /> &lt;50</span>
        </span>
      </div>

      {/* KPIs */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <StatCard icon={<Users className="size-4" />} label="Learners" value={kpis.learners} sub={`${kpis.compiled} report card${kpis.compiled === 1 ? "" : "s"} compiled`} />
        <StatCard icon={<BookOpen className="size-4" />} label="Subjects graded" value={kpis.subjects} sub={`${kpis.results} subject results on the ledger`} />
        <StatCard icon={<TrendingUp className="size-4" />} label="Class average" value={kpis.classAvg !== null ? fmt1(kpis.classAvg) : "—"} sub="mean of every learner's term average" tone="primary" />
        <StatCard icon={<ClipboardCheck className="size-4" />} label="Published" value={kpis.published} sub={`${Math.max(0, kpis.compiled - kpis.published)} still draft`} tone="gold" />
      </div>

      {/* wave 22 — broadsheet + analytics tabs around the same ledger */}
      <Tabs value={tab} onValueChange={setTab}>
        <TabsList>
          <TabsTrigger value="grid" className="gap-1.5"><Award className="size-4" /> Broadsheet</TabsTrigger>
          <TabsTrigger value="analytics" className="gap-1.5"><BarChart3 className="size-4" /> Analytics</TabsTrigger>
        </TabsList>

        <TabsContent value="grid" className="mt-4">
      {/* results grid */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-base">
            <Award className="size-4" /> Results grid — {meta.classrooms.find((c) => c.id === classId)?.name ?? "class"}
            {gridLoading && <RefreshCcw className="size-3.5 animate-spin text-muted-foreground" aria-label="Loading grid" />}
          </CardTitle>
        </CardHeader>
        <CardContent>
          {!hasData ? (
            <EmptyState
              icon={<Calculator className="size-8" />}
              title="No results compiled for this term yet"
              hint={canPublish
                ? "Run “Compute term results” to roll the Gradebook marks up into subject results (30% class + 70% exam), rank the class and compile report cards."
                : "Ask the head teacher to run “Compute term results” for this class."}
            />
          ) : (
            <div className="overflow-auto nice-scroll max-h-[65vh] border rounded-lg">
              <Table className="min-w-max">
                <TableHeader className="sticky top-0 z-20 bg-secondary/95 backdrop-blur">
                  <TableRow>
                    <TableHead className="sticky left-0 z-30 bg-secondary/95 backdrop-blur min-w-[190px]">Learner</TableHead>
                    {subjects.map((s) => (
                      <TableHead key={s.id} className="text-center min-w-[76px]" title={s.name}>{s.code}</TableHead>
                    ))}
                    <TableHead className="text-center">Avg</TableHead>
                    <TableHead className="text-center">Pos</TableHead>
                    <TableHead className="text-center" title="Best-6 BECE aggregate (lower is better)">Agg</TableHead>
                    <TableHead className="text-center" title="Attendance">Att %</TableHead>
                    <TableHead className="text-center" title="Terminal report & transcript">Sheets</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {students.map((st) => {
                    const card = cardByStudent.get(st.id);
                    return (
                      <TableRow key={st.id} className="hover:bg-secondary/40">
                        <TableCell className="sticky left-0 z-10 bg-card font-medium">
                          <div className="leading-tight">{st.name}</div>
                          <div className="text-[10.5px] text-muted-foreground font-normal">{st.indexNo}</div>
                        </TableCell>
                        {subjects.map((s) => {
                          const cell = cellByKey.get(`${st.id}:${s.id}`);
                          const tone = bandTone(cell?.total ?? null);
                          return (
                            <TableCell key={s.id} className="text-center px-1.5">
                              {cell ? (
                                <div className="inline-flex flex-col items-center gap-0.5">
                                  <span className={cn("text-[13px] font-semibold tabular-nums", tone.cell)}>{fmt1(cell.total)}</span>
                                  <Badge variant="outline" className={cn("text-[9.5px] px-1 py-0 h-4", tone.badge)} title={cell.remarks ?? undefined}>
                                    {cell.grade ?? "—"}{cell.position ? ` · ${ordinal(cell.position)}` : ""}
                                  </Badge>
                                </div>
                              ) : (
                                <span className="text-muted-foreground/50">—</span>
                              )}
                            </TableCell>
                          );
                        })}
                        <TableCell className="text-center font-semibold tabular-nums">{fmt1(card?.average ?? null)}</TableCell>
                        <TableCell className="text-center tabular-nums">{card?.classPosition ? `${ordinal(card.classPosition)} / ${card.classSize ?? "—"}` : "—"}</TableCell>
                        <TableCell className="text-center tabular-nums">{card?.aggregate ?? "—"}</TableCell>
                        <TableCell className="text-center tabular-nums">
                          {card?.attendancePct !== null && card?.attendancePct !== undefined ? (
                            <span className={cn("inline-flex items-center gap-1", card.attendancePct >= 90 ? "text-emerald-600 dark:text-emerald-400" : card.attendancePct >= 75 ? "text-amber-600 dark:text-amber-400" : "text-red-600 dark:text-red-400")}>
                              <Percent className="size-3" />{card.attendancePct}
                            </span>
                          ) : "—"}
                        </TableCell>
                        <TableCell className="text-center px-1.5">
                          <div className="inline-flex items-center gap-1">
                            {card ? (
                              <Button size="sm" variant="outline" className="size-7 p-0" title={`Open ${st.name}'s report card`} onClick={() => openCard(card.id)}>
                                <Eye className="size-3.5" />
                              </Button>
                            ) : null}
                            {/* wave 21 — printable multi-term transcript */}
                            <Button size="sm" variant="outline" className="size-7 p-0" title={`Open ${st.name}'s academic transcript`} onClick={() => openTranscript(st.id)}>
                              <ScrollText className="size-3.5" />
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            </div>
          )}
          {!canPublish && hasData && (
            <p className="mt-3 text-xs text-muted-foreground">You can view and print results; computing and re-ranking needs the generate &amp; publish permission.</p>
          )}
        </CardContent>
      </Card>
        </TabsContent>

        <TabsContent value="analytics" className="mt-4">
          <AnalyticsPanel
            payload={analytics}
            loading={analyticsLoading}
            className={meta.classrooms.find((c) => c.id === classId)?.name ?? "class"}
            termLabel={termLabel ? `${termLabel.name} · ${termLabel.yearName}` : ""}
          />
        </TabsContent>
      </Tabs>

      {/* ── single printable report card ── */}
      <Dialog open={detailOpen} onOpenChange={setDetailOpen}>
        <DialogContent className="max-w-4xl max-h-[92vh] overflow-y-auto nice-scroll">
          <DialogHeader className="no-print">
            <DialogTitle className="flex items-center gap-2"><GraduationCap className="size-4" /> Terminal report</DialogTitle>
            <DialogDescription>Print on A4 — the sheet carries the school header, grades, remarks and signature lines.</DialogDescription>
            <div className="flex gap-2 pt-1">
              <Button size="sm" className="gap-1.5" onClick={() => window.print()} disabled={!detail}><Printer className="size-4" /> Print</Button>
              {/* wave 21 — jump from the terminal report to the full transcript */}
              {detail && (
                <Button size="sm" variant="outline" className="gap-1.5" onClick={() => { setDetailOpen(false); openTranscript(detail.student.id); }}>
                  <ScrollText className="size-4" /> Full transcript
                </Button>
              )}
            </div>
          </DialogHeader>
          {detail ? (
            <div className="print-area">
              <ReportSheet
                school={detail.school}
                studentName={[detail.student.lastName, detail.student.firstName, detail.student.otherNames].filter(Boolean).join(" ")}
                indexNo={detail.student.nyansapoIndex}
                gender={detail.student.gender}
                className={detail.classroom?.name ?? detail.card.classroomName}
                termName={detail.card.term.name}
                yearName={detail.card.term.yearName}
                position={detail.card.classPosition}
                classSize={detail.card.classSize}
                aggregate={detail.card.aggregate}
                attendancePct={detail.card.attendancePct}
                average={detail.card.average}
                totalScore={detail.card.totalScore}
                promoted={detail.card.promoted}
                teacherRemark={detail.card.teacherRemark}
                headRemark={detail.card.headRemark}
                rows={detail.rows.length ? detail.rows.map((r) => ({
                  subjectName: r.subjectName, classScore: r.classScore, examScore: r.examScore,
                  total: r.total, grade: r.grade, position: r.position, remark: r.remarks,
                })) : detail.legacyResults.map((r) => ({
                  subjectName: r.subjectName, classScore: r.classScore, examScore: r.examScore,
                  total: r.total, grade: r.grade, position: r.position, remark: r.remark,
                }))}
                classTeacher={detail.classTeacher}
                showGhanaColumns={detail.rows.length > 0}
              />
            </div>
          ) : (
            <div className="space-y-3 pb-4">
              <Skeleton className="h-16 w-full" />
              <Skeleton className="h-48 w-full" />
              <Skeleton className="h-24 w-2/3" />
            </div>
          )}
        </DialogContent>
      </Dialog>

      {/* ── whole-class print set ── */}
      <Dialog open={bulkOpen} onOpenChange={setBulkOpen}>
        <DialogContent className="max-w-4xl max-h-[92vh] overflow-y-auto nice-scroll">
          <DialogHeader className="no-print">
            <DialogTitle className="flex items-center gap-2"><Layers className="size-4" /> Class report set</DialogTitle>
            <DialogDescription>{data?.cards.length ?? 0} sheet{(data?.cards.length ?? 0) === 1 ? "" : "s"} — each prints on its own page.</DialogDescription>
            <div className="flex gap-2 pt-1">
              <Button size="sm" className="gap-1.5" onClick={() => window.print()} disabled={!data?.cards.length}><Printer className="size-4" /> Print all {data?.cards.length ?? 0}</Button>
            </div>
          </DialogHeader>
          <div className="print-area space-y-5">
            {(data?.cards ?? []).map((c) => (
              <ReportSheet
                key={c.id}
                school={data!.school}
                studentName={c.studentName}
                indexNo={c.indexNo}
                gender={null}
                className={c.classroomName}
                termName={termLabel?.name ?? ""}
                yearName={termLabel?.yearName ?? ""}
                position={c.classPosition}
                classSize={c.classSize}
                aggregate={c.aggregate}
                attendancePct={c.attendancePct}
                average={c.average}
                totalScore={c.totalScore}
                promoted={c.promoted}
                teacherRemark={c.teacherRemark}
                headRemark={c.headRemark}
                rows={(data?.results ?? []).filter((r) => r.studentId === c.studentId).map((r) => ({
                  subjectName: r.subjectName, classScore: r.classScore, examScore: r.examScore,
                  total: r.total, grade: r.grade, position: r.position, remark: r.remarks,
                }))}
                classTeacher={null}
                showGhanaColumns
              />
            ))}
          </div>
        </DialogContent>
      </Dialog>

      {/* ── wave 21 — publish / recall confirmation (wave 22 adds the
          notify-on-publish checkbox) ── */}
      <AlertDialog open={confirmPub !== null} onOpenChange={(o) => !o && setConfirmPub(null)}>
        <AlertDialogContent>
          {confirmPub === "publish" ? (
            <>
              <AlertDialogHeader>
                <AlertDialogTitle>Publish {kpis.compiled} report card{kpis.compiled === 1 ? "" : "s"} to learners and parents?</AlertDialogTitle>
                <AlertDialogDescription>
                  The whole class-term goes live in the learner &amp; parent portals — every subject result, position,
                  aggregate and remark on the ledger. The Results Control status is <b>{approvalStatus.toLowerCase()}</b>.
                  You can pull everything back to draft at any time with “Recall to draft”.
                </AlertDialogDescription>
              </AlertDialogHeader>
              {/* wave 22 — fire the RESULTS_PUBLISHED guardian SMS automation with
                  the publication (gated on the school's per-event toggle) */}
              <label className="flex items-start gap-2.5 rounded-lg border bg-secondary/40 p-3 cursor-pointer select-none">
                <Checkbox checked={pubNotify} onCheckedChange={(v) => setPubNotify(v === true)} className="mt-0.5" aria-label="Also notify guardians by SMS" />
                <span className="text-sm leading-snug">
                  <span className="font-medium flex items-center gap-1.5"><Megaphone className="size-4" /> Also text guardians</span>
                  <span className="text-muted-foreground text-xs block mt-0.5">
                    Fires the “Results published” SMS automation — average, position, best-6 aggregate — to every guardian with a
                    phone on file (school's per-event toggle in Settings → Notifications decides; SIMULATED delivery costs 1 credit each).
                  </span>
                </span>
              </label>
              <AlertDialogFooter>
                <AlertDialogCancel disabled={pubBusy}>Cancel</AlertDialogCancel>
                <AlertDialogAction
                  className="bg-emerald-600 hover:bg-emerald-700 text-white"
                  disabled={pubBusy}
                  onClick={(e) => { e.preventDefault(); publishAction("publish"); }}>
                  <Send className="size-4 mr-1.5" /> {pubBusy ? "Publishing…" : "Publish now"}
                </AlertDialogAction>
              </AlertDialogFooter>
            </>
          ) : (
            <>
              <AlertDialogHeader>
                <AlertDialogTitle>Recall {kpis.published} published card{kpis.published === 1 ? "" : "s"} to draft?</AlertDialogTitle>
                <AlertDialogDescription>
                  Learners and parents will immediately stop seeing these results in their portals. Nothing is deleted —
                  the ledger stays intact and you can re-publish after corrections.
                </AlertDialogDescription>
              </AlertDialogHeader>
              <AlertDialogFooter>
                <AlertDialogCancel disabled={pubBusy}>Cancel</AlertDialogCancel>
                <AlertDialogAction
                  className="bg-red-600 hover:bg-red-700 text-white"
                  disabled={pubBusy}
                  onClick={(e) => { e.preventDefault(); publishAction("recall"); }}>
                  <Undo2 className="size-4 mr-1.5" /> {pubBusy ? "Recalling…" : "Recall to draft"}
                </AlertDialogAction>
              </AlertDialogFooter>
            </>
          )}
        </AlertDialogContent>
      </AlertDialog>

      {/* ── wave 21 — printable multi-term academic transcript ── */}
      <Dialog open={transcriptOpen} onOpenChange={setTranscriptOpen}>
        <DialogContent className="max-w-4xl max-h-[92vh] overflow-y-auto nice-scroll">
          <DialogHeader className="no-print">
            <DialogTitle className="flex items-center gap-2"><ScrollText className="size-4" /> Academic transcript</DialogTitle>
            <DialogDescription>Every term on record — print on A4; each term section keeps its own grades table.</DialogDescription>
            <div className="flex gap-2 pt-1">
              <Button size="sm" className="gap-1.5" onClick={() => window.print()} disabled={!transcript}><Printer className="size-4" /> Print</Button>
            </div>
          </DialogHeader>
          {transcript ? (
            <div className="print-area">
              <TranscriptSheet payload={transcript} />
            </div>
          ) : (
            <div className="space-y-3 pb-4">
              <Skeleton className="h-16 w-full" />
              <Skeleton className="h-48 w-full" />
              <Skeleton className="h-24 w-2/3" />
            </div>
          )}
        </DialogContent>
      </Dialog>

      {/* ── wave 22 — results release notifications (CI3 exam_marks_sms
          modernised): draft every guardian message, preview it, send ── */}
      <Dialog open={notifyOpen} onOpenChange={(o) => { setNotifyOpen(o); if (!o) setNotifyPrev(null); }}>
        <DialogContent className="max-w-2xl max-h-[92vh] overflow-y-auto nice-scroll">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><Megaphone className="size-4" /> Notify guardians — results are live</DialogTitle>
            <DialogDescription>
              Every published learner&apos;s terminal summary (average, position, best-6 aggregate, attendance) — SMS through the
              school gateway, email through the school SMTP channel. Draft cards are never messaged.
            </DialogDescription>
          </DialogHeader>

          {/* channel picker */}
          <div className="grid sm:grid-cols-3 gap-2">
            {([["SMS", "SMS only"], ["EMAIL", "Email only"], ["BOTH", "SMS + Email"]] as const).map(([value, label]) => (
              <button
                key={value}
                type="button"
                onClick={() => setNotifyChannel(value)}
                className={cn(
                  "rounded-lg border p-3 text-left transition-colors",
                  notifyChannel === value ? "border-primary bg-primary/10" : "hover:bg-secondary/60",
                )}
                aria-pressed={notifyChannel === value}
              >
                <span className="text-sm font-medium flex items-center gap-1.5">
                  {value === "SMS" ? <Phone className="size-4" /> : value === "EMAIL" ? <Mail className="size-4" /> : <Send className="size-4" />} {label}
                </span>
                {notifyPrev && (
                  <span className="block text-[11px] text-muted-foreground mt-0.5">
                    {value === "SMS" ? `${notifyPrev.withPhone} sendable · ${notifyPrev.sms.provider} · ${notifyPrev.sms.credits} credits` : value === "EMAIL" ? `${notifyPrev.withEmail} with an address · ${notifyPrev.email.enabled ? "SMTP live" : "SIMULATED (SMTP off)"}` : `${notifyPrev.withPhone} SMS · ${notifyPrev.withEmail} email`}
                  </span>
                )}
              </button>
            ))}
          </div>

          {notifyPrev ? (
            <div className="space-y-3">
              {/* channel state + counts */}
              <div className="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs">
                <span className="inline-flex items-center gap-1.5"><BadgeCheck className="size-3.5 text-emerald-600" /> {notifyPrev.rows.length} drafted</span>
                <span className={cn("inline-flex items-center gap-1.5", notifyPrev.withoutPhone && notifyChannel !== "EMAIL" ? "text-amber-600" : "text-muted-foreground")}>
                  {notifyPrev.withoutPhone && notifyChannel !== "EMAIL" ? <PhoneOff className="size-3.5" /> : <Phone className="size-3.5" />} {notifyPrev.withoutPhone} without a phone
                </span>
                {notifyChannel !== "SMS" && (
                  <span className={cn("inline-flex items-center gap-1.5", notifyPrev.withoutEmail ? "text-amber-600" : "text-muted-foreground")}>
                    {notifyPrev.withoutEmail ? <MailWarning className="size-3.5" /> : <Mail className="size-3.5" />} {notifyPrev.withoutEmail} without an email
                  </span>
                )}
                {notifyPrev.draftCount > 0 && (
                  <span className="text-muted-foreground">{notifyPrev.draftCount} draft card{notifyPrev.draftCount === 1 ? "" : "s"} skipped</span>
                )}
                {notifyPrev.rule && (
                  <Badge variant="outline" className="text-[10.5px] gap-1" title={notifyPrev.rule.body}><Gauge className="size-3" /> template: {notifyPrev.rule.name}</Badge>
                )}
              </div>

              {/* per-learner drafts */}
              <div className="overflow-auto nice-scroll max-h-[46vh] border rounded-lg">
                <Table>
                  <TableHeader className="sticky top-0 bg-secondary/95 backdrop-blur">
                    <TableRow>
                      <TableHead className="min-w-[150px]">Guardian of</TableHead>
                      <TableHead className="min-w-[130px]">Destination</TableHead>
                      <TableHead className="min-w-[260px]">Message</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {notifyPrev.rows.map((r) => (
                      <TableRow key={r.studentId} className="hover:bg-secondary/40">
                        <TableCell>
                          <div className="leading-tight font-medium">{r.studentName}</div>
                          <div className="text-[10.5px] text-muted-foreground font-normal">{r.guardianName} · {r.indexNo}</div>
                        </TableCell>
                        <TableCell className="text-xs">
                          <div className="flex flex-col gap-0.5">
                            {notifyChannel !== "EMAIL" && (
                              <span className={cn("inline-flex items-center gap-1", r.guardianPhone.trim() ? "" : "text-destructive")}>
                                <Phone className="size-3" /> {r.guardianPhone.trim() || "no phone"}
                              </span>
                            )}
                            {notifyChannel !== "SMS" && (
                              <span className={cn("inline-flex items-center gap-1 truncate max-w-[180px]", (r.guardianEmail ?? "").trim() ? "" : "text-destructive")}>
                                <Mail className="size-3 shrink-0" /> {(r.guardianEmail ?? "").trim() || "no email"}
                              </span>
                            )}
                          </div>
                        </TableCell>
                        <TableCell className="text-[11.5px] leading-snug text-muted-foreground">{r.message}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>

              <div className="flex items-center justify-between gap-3 border-t pt-3">
                <p className="text-xs text-muted-foreground">
                  {notifyChannel !== "EMAIL"
                    ? `${notifyPrev.withPhone} credit${notifyPrev.withPhone === 1 ? "" : "s"} will be debited (${notifyPrev.sms.credits} on the wallet)${notifyPrev.sms.provider === "SIMULATED" ? " in SIMULATED delivery" : ` through ${notifyPrev.sms.provider}`}.`
                    : notifyPrev.email.enabled
                      ? `Delivery through ${notifyPrev.email.host}.`
                      : "SMTP is not configured — every email is logged as SIMULATED (Settings → Notifications → Email channel)."}
                </p>
                <Button size="sm" className="gap-1.5" onClick={sendNotify} disabled={notifyBusy || (notifyChannel !== "EMAIL" ? notifyPrev.withPhone === 0 : false) || (notifyChannel === "EMAIL" ? notifyPrev.withEmail === 0 : false)}>
                  <Send className={cn("size-4", notifyBusy && "animate-pulse")} /> {notifyBusy ? "Sending…" : `Send to ${notifyPrev.rows.length} guardian${notifyPrev.rows.length === 1 ? "" : "s"}`}
                </Button>
              </div>
            </div>
          ) : (
            <div className="space-y-3 pb-4">
              <Skeleton className="h-10 w-full" />
              <Skeleton className="h-40 w-full" />
              <Skeleton className="h-16 w-2/3" />
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}

/* ══════════════════════════════════════════════════════════════
   Wave 22 — term analytics panel. The same ledger the broadsheet
   grid prints, read as WAEC charts: class summary stats, the
   A1..F9 grade distribution wall, per-subject pass rates with
   top/weakest learners, and the gender split.
   ══════════════════════════════════════════════════════════════ */
function AnalyticsPanel({ payload, loading, className, termLabel }: {
  payload: AnalyticsPayload | null;
  loading: boolean;
  className: string;
  termLabel: string;
}) {
  if (loading && !payload) return <LoadingBlock label="Compiling term analytics…" />;
  if (!payload) return null;

  const { summary, subjects, weakSubjects } = payload;
  if (!summary.entries && !subjects.length) {
    return (
      <Card><CardContent>
        <EmptyState
          icon={<BarChart3 className="size-8" />}
          title="No results on the ledger yet"
          hint="Analytics appear for the same class-term as the broadsheet — run “Compute term results” first."
        />
      </CardContent></Card>
    );
  }

  const gradeChartData = summary.grades.map((g) => ({ grade: g.grade, count: g.count, tone: g.tone }));
  const toneColor = (tone: string) => (tone === "emerald" ? "#059669" : tone === "amber" ? "#d97706" : "#dc2626");

  return (
    <div className="space-y-4">
      {/* class summary */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <StatCard icon={<TrendingUp className="size-4" />} label="Term average" value={fmt1(summary.average)} sub={`${summary.entries} graded subject results`} tone="primary" />
        <StatCard icon={<BadgeCheck className="size-4" />} label="Pass rate" value={summary.passRate !== null ? `${fmt1(summary.passRate)}%` : "—"} sub="entries scoring 50 or better" />
        <StatCard icon={<Award className="size-4" />} label="Distinctions" value={summary.distinctions} sub={`averages ≥65 — of ${summary.learners} learners`} tone="gold" />
        <StatCard icon={<AlertTriangle className="size-4" />} label="Below the bar" value={summary.belowBar} sub="averages under 50 — flag for support" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* WAEC grade distribution */}
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-base flex items-center gap-2">
              <BarChart3 className="size-4" /> WAEC grade distribution — {className}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={gradeChartData} margin={{ top: 4, right: 8, left: -18, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" vertical={false} />
                  <XAxis dataKey="grade" tick={{ fontSize: 11 }} stroke="#888" interval={0} />
                  <YAxis tick={{ fontSize: 11 }} stroke="#888" allowDecimals={false} />
                  <Tooltip formatter={(v: any) => [`${v} result${v === 1 ? "" : "s"}`, "Count"]} contentStyle={{ borderRadius: 10 }} />
                  <Bar dataKey="count" radius={[4, 4, 0, 0]}>
                    {gradeChartData.map((g) => <Cell key={g.grade} fill={toneColor(g.tone)} />)}
                  </Bar>
                </BarChart>
              </ResponsiveContainer>
            </div>
            <p className="text-xs text-muted-foreground mt-1">
              Every graded subject result of the term on the BECE scale — A1 excellent … F9 fail. {termLabel}
            </p>
          </CardContent>
        </Card>

        {/* top & bottom performers + gender split */}
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Users className="size-4" /> Standing</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            <div className="grid grid-cols-2 gap-3">
              <div>
                <div className="text-[11px] font-medium text-muted-foreground uppercase tracking-wide mb-1.5 flex items-center gap-1"><ChevronUp className="size-3.5" /> Top of the class</div>
                <ol className="space-y-1.5">
                  {summary.top.map((t, i) => (
                    <li key={`${t.name}-${i}`} className="flex items-center justify-between gap-2 text-sm">
                      <span className="truncate">{i + 1}. {t.name}</span>
                      <span className="font-semibold tabular-nums text-emerald-700 dark:text-emerald-400">{fmt1(t.average)}</span>
                    </li>
                  ))}
                  {!summary.top.length && <li className="text-sm text-muted-foreground">No averages compiled.</li>}
                </ol>
              </div>
              <div>
                <div className="text-[11px] font-medium text-muted-foreground uppercase tracking-wide mb-1.5 flex items-center gap-1"><ChevronDown className="size-3.5" /> Needs support</div>
                <ol className="space-y-1.5">
                  {summary.bottom.map((t, i) => (
                    <li key={`${t.name}-${i}`} className="flex items-center justify-between gap-2 text-sm">
                      <span className="truncate">{i + 1}. {t.name}</span>
                      <span className="font-semibold tabular-nums text-red-700 dark:text-red-400">{fmt1(t.average)}</span>
                    </li>
                  ))}
                  {!summary.bottom.length && <li className="text-sm text-muted-foreground">Class too small for a tail.</li>}
                </ol>
              </div>
            </div>
            <div className="border-t pt-3 flex flex-wrap items-center gap-3 text-sm">
              <span className="text-muted-foreground text-xs uppercase tracking-wide font-medium">Gender split</span>
              <span className="inline-flex items-center gap-1.5">
                <span className="size-2 rounded-full bg-sky-500" /> Boys ({summary.boys.count}) <b className="tabular-nums">{fmt1(summary.boys.average)}</b>
              </span>
              <span className="inline-flex items-center gap-1.5">
                <span className="size-2 rounded-full bg-rose-500" /> Girls ({summary.girls.count}) <b className="tabular-nums">{fmt1(summary.girls.average)}</b>
              </span>
              <span className="ml-auto text-xs text-muted-foreground">{summary.learners} learners · {summary.published} published</span>
            </div>
            {weakSubjects.length > 0 && (
              <div className="rounded-lg border border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950 p-3 text-xs leading-relaxed">
                <b className="flex items-center gap-1.5 text-amber-700 dark:text-amber-300"><AlertTriangle className="size-3.5" /> Weak subjects (pass rate under 50%)</b>
                <span className="text-amber-700/90 dark:text-amber-300/90">
                  {" "}{weakSubjects.map((w) => `${w.name} (${fmt1(w.passRate)}%)`).join(" · ")} — schedule remedials before the next terminal exams.
                </span>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* per-subject performance */}
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base flex items-center gap-2"><BookOpen className="size-4" /> Subject performance</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-auto nice-scroll max-h-[60vh] border rounded-lg">
            <Table className="min-w-[640px]">
              <TableHeader className="sticky top-0 z-10 bg-secondary/95 backdrop-blur">
                <TableRow>
                  <TableHead className="min-w-[150px]">Subject</TableHead>
                  <TableHead className="text-center">Entries</TableHead>
                  <TableHead className="text-center">Average</TableHead>
                  <TableHead className="min-w-[140px]">Pass rate</TableHead>
                  <TableHead className="min-w-[230px]">Grades (A1 → F9)</TableHead>
                  <TableHead className="min-w-[170px]">Top / weakest</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {subjects.map((s) => (
                  <TableRow key={s.subjectId} className="hover:bg-secondary/40">
                    <TableCell>
                      <div className="leading-tight font-medium">{s.name}</div>
                      <div className="text-[10.5px] text-muted-foreground font-normal">{s.code}</div>
                    </TableCell>
                    <TableCell className="text-center tabular-nums">{s.entries}</TableCell>
                    <TableCell className="text-center font-semibold tabular-nums">
                      <span className={s.average !== null ? bandTone(s.average).cell : ""}>{fmt1(s.average)}</span>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <div className="h-1.5 flex-1 rounded-full bg-secondary overflow-hidden" role="img" aria-label={`Pass rate ${fmt1(s.passRate)}%`}>
                          <div
                            className={cn("h-full rounded-full", (s.passRate ?? 0) >= 65 ? "bg-emerald-500" : (s.passRate ?? 0) >= 50 ? "bg-amber-500" : "bg-red-500")}
                            style={{ width: `${Math.max(2, Math.min(100, s.passRate ?? 0))}%` }}
                          />
                        </div>
                        <span className="text-xs tabular-nums text-muted-foreground">{s.passRate !== null ? `${fmt1(s.passRate)}%` : "—"}</span>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="flex flex-wrap gap-1">
                        {s.grades.filter((g) => g.count > 0).map((g) => (
                          <Badge key={g.grade} variant="outline" className={cn("text-[9.5px] px-1.5 h-4 tabular-nums", bandTone(g.grade === "A1" || g.grade === "B2" || g.grade === "B3" ? 70 : g.grade === "C4" || g.grade === "C5" || g.grade === "C6" ? 55 : 40).badge)}>
                            {g.grade}·{g.count}
                          </Badge>
                        ))}
                        {s.grades.every((g) => g.count === 0) && <span className="text-xs text-muted-foreground">no grades</span>}
                      </div>
                    </TableCell>
                    <TableCell className="text-xs">
                      {s.top && <div className="truncate"><b className="text-muted-foreground">↑</b> {s.top.name} · {fmt1(s.top.total)}</div>}
                      {s.weak && s.weak.name !== s.top?.name && <div className="truncate"><b className="text-muted-foreground">↓</b> {s.weak.name} · {fmt1(s.weak.total)}</div>}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
          <p className="mt-3 text-xs text-muted-foreground">
            Pass rate counts subject results scoring 50 or better. Averages and distributions derive from the same compiled ledger the broadsheet prints — analytics can never disagree with the report cards.
          </p>
        </CardContent>
      </Card>
    </div>
  );
}

/* ══════════════════════════════════════════════════════════════
   Ghana-format terminal report sheet — the printable paper.
   Serif face, boxed particulars, the classic
   Class(30) / Exam(70) / Total(100) / Grade / Position / Remark
   table, remarks block and two signature lines.
   ══════════════════════════════════════════════════════════════ */
type SheetRow = { subjectName: string; classScore: number | null; examScore: number | null; total: number | null; grade: string | null; position: number | null; remark: string | null };

function ReportSheet(props: {
  school: SchoolInfo;
  studentName: string; indexNo: string; gender: string | null; className: string;
  termName: string; yearName: string;
  position: number | null; classSize: number | null;
  aggregate: number | null; attendancePct: number | null;
  average: number | null; totalScore: number | null;
  promoted: boolean | null;
  teacherRemark: string | null; headRemark: string | null;
  rows: SheetRow[];
  classTeacher: string | null;
  showGhanaColumns: boolean;
}) {
  const p = props;
  return (
    <div className="print-sheet bg-white text-neutral-900 font-serif text-[12.5px] leading-snug border border-neutral-300 shadow-sm rounded-md p-5 md:p-7 max-w-[820px] mx-auto">
      {/* school header */}
      <div className="flex items-center gap-4 border-b-2 border-neutral-800 pb-3">
        <div className="grid place-items-center size-14 shrink-0 rounded-full text-white font-bold text-lg" style={{ background: p.school.logoColor }} aria-hidden>
          {initials(p.school.name)}
        </div>
        <div className="min-w-0">
          <div className="text-xl md:text-2xl font-bold tracking-wide uppercase">{p.school.name}</div>
          <div className="text-[11.5px] text-neutral-600">
            {[p.school.gpsAddress, p.school.town, p.school.region].filter(Boolean).join(" · ")}
            {p.school.phone ? ` · Tel: ${p.school.phone}` : ""}
            {p.school.email ? ` · ${p.school.email}` : ""}
          </div>
          {p.school.motto && <div className="text-[11.5px] italic text-neutral-700 mt-0.5">“{p.school.motto}”</div>}
        </div>
        <div className="ml-auto text-right text-[10.5px] text-neutral-500 hidden md:block">
          <div>EMIS code</div>
          <div className="font-semibold text-neutral-700">{p.school.code}</div>
        </div>
      </div>

      {/* title */}
      <div className="text-center my-3">
        <div className="text-base md:text-lg font-bold tracking-[0.2em] uppercase">Terminal Report</div>
        <div className="text-[12px] text-neutral-600">{p.termName} · {p.yearName} Academic Year</div>
      </div>

      {/* particulars */}
      <div className="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-1 border border-neutral-400 rounded-sm px-4 py-2.5 mb-3">
        <div><span className="text-neutral-500">Name: </span><span className="font-semibold">{p.studentName}</span></div>
        <div><span className="text-neutral-500">Index No: </span><span className="font-semibold">{p.indexNo}</span></div>
        <div><span className="text-neutral-500">Class: </span><span className="font-semibold">{p.className}</span></div>
        <div><span className="text-neutral-500">Gender: </span><span className="font-semibold">{p.gender ?? "—"}</span></div>
        <div><span className="text-neutral-500">Position: </span><span className="font-semibold">{ordinal(p.position)}{p.classSize ? ` of ${p.classSize}` : ""}</span></div>
        <div><span className="text-neutral-500">Attendance: </span><span className="font-semibold">{p.attendancePct !== null && p.attendancePct !== undefined ? `${p.attendancePct}%` : "—"}</span></div>
      </div>

      {/* grades table */}
      <table className="w-full border-collapse text-[12px]">
        <thead>
          <tr className="bg-neutral-100 text-left">
            <th className="border border-neutral-400 px-2 py-1.5">Subject</th>
            <th className="border border-neutral-400 px-2 py-1.5 text-center w-16">Class <span className="text-[10px]">(30)</span></th>
            <th className="border border-neutral-400 px-2 py-1.5 text-center w-16">Exam <span className="text-[10px]">(70)</span></th>
            <th className="border border-neutral-400 px-2 py-1.5 text-center w-16">Total <span className="text-[10px]">(100)</span></th>
            <th className="border border-neutral-400 px-2 py-1.5 text-center w-14">Grade</th>
            <th className="border border-neutral-400 px-2 py-1.5 text-center w-14">Pos.</th>
            <th className="border border-neutral-400 px-2 py-1.5">Remarks</th>
          </tr>
        </thead>
        <tbody>
          {p.rows.length ? p.rows.map((r, i) => (
            <tr key={i}>
              <td className="border border-neutral-400 px-2 py-1">{r.subjectName}</td>
              <td className="border border-neutral-400 px-2 py-1 text-center tabular-nums">{p.showGhanaColumns ? (r.classScore ?? "—") : "—"}</td>
              <td className="border border-neutral-400 px-2 py-1 text-center tabular-nums">{p.showGhanaColumns ? (r.examScore ?? "—") : "—"}</td>
              <td className="border border-neutral-400 px-2 py-1 text-center font-semibold tabular-nums">{r.total ?? "—"}</td>
              <td className="border border-neutral-400 px-2 py-1 text-center font-semibold">{r.grade ?? "—"}</td>
              <td className="border border-neutral-400 px-2 py-1 text-center tabular-nums">{r.position ? ordinal(r.position) : "—"}</td>
              <td className="border border-neutral-400 px-2 py-1">{r.remark ?? ""}</td>
            </tr>
          )) : (
            <tr><td colSpan={7} className="border border-neutral-400 px-2 py-3 text-center text-neutral-500">No subject results recorded.</td></tr>
          )}
        </tbody>
      </table>

      {/* summary strip */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-1 border border-neutral-400 border-t-0 rounded-b-sm px-4 py-2 mb-4 text-[12px]">
        <div><span className="text-neutral-500">Total: </span><span className="font-semibold tabular-nums">{p.totalScore ?? "—"}</span></div>
        <div><span className="text-neutral-500">Average: </span><span className="font-semibold tabular-nums">{p.average !== null ? fmt1(p.average) : "—"}</span></div>
        <div><span className="text-neutral-500">Aggregate (best 6): </span><span className="font-semibold tabular-nums">{p.aggregate ?? "—"}</span></div>
        <div><span className="text-neutral-500">Promotion: </span><span className="font-semibold">{p.promoted === null || p.promoted === undefined ? "—" : p.promoted ? "Promoted" : "Not yet"}</span></div>
      </div>

      {/* remarks */}
      <div className="space-y-2 mb-6">
        <div><span className="font-semibold">Class teacher's remarks: </span><span className="border-b border-dotted border-neutral-400">{p.teacherRemark ?? "…"}</span></div>
        <div><span className="font-semibold">Headteacher's remarks: </span><span className="border-b border-dotted border-neutral-400">{p.headRemark ?? "…"}</span></div>
      </div>

      {/* signature lines */}
      <div className="grid grid-cols-2 gap-10 pt-2 text-[12px]">
        <div>
          <div className="border-t border-neutral-800 w-3/4" />
          <div className="mt-1 text-neutral-600">Class Teacher{p.classTeacher ? ` — ${p.classTeacher}` : ""}</div>
          <div className="mt-3 text-neutral-600">Date: ..............................</div>
        </div>
        <div>
          <div className="border-t border-neutral-800 w-3/4" />
          <div className="mt-1 text-neutral-600">Headteacher</div>
          <div className="mt-3 text-neutral-600">Date: ..............................</div>
        </div>
      </div>
    </div>
  );
}

/* ══════════════════════════════════════════════════════════════
   Academic transcript — wave 21 printable paper.
   The learner's whole history on one continuous sheet: school
   header, particulars, one grades table per term (oldest first),
   the aggregate progression strip and a single headteacher
   signature line at the end.
   ══════════════════════════════════════════════════════════════ */
function TranscriptSheet({ payload }: { payload: TranscriptPayload }) {
  const p = payload;
  const studentName = [p.student.lastName, p.student.firstName, p.student.otherNames].filter(Boolean).join(" ");
  return (
    <div className="print-sheet bg-white text-neutral-900 font-serif text-[12.5px] leading-snug border border-neutral-300 shadow-sm rounded-md p-5 md:p-7 max-w-[820px] mx-auto">
      {/* school header */}
      <div className="flex items-center gap-4 border-b-2 border-neutral-800 pb-3">
        <div className="grid place-items-center size-14 shrink-0 rounded-full text-white font-bold text-lg" style={{ background: p.school.logoColor }} aria-hidden>
          {initials(p.school.name)}
        </div>
        <div className="min-w-0">
          <div className="text-xl md:text-2xl font-bold tracking-wide uppercase">{p.school.name}</div>
          <div className="text-[11.5px] text-neutral-600">
            {[p.school.gpsAddress, p.school.town, p.school.region].filter(Boolean).join(" · ")}
            {p.school.phone ? ` · Tel: ${p.school.phone}` : ""}
            {p.school.email ? ` · ${p.school.email}` : ""}
          </div>
          {p.school.motto && <div className="text-[11.5px] italic text-neutral-700 mt-0.5">“{p.school.motto}”</div>}
        </div>
        <div className="ml-auto text-right text-[10.5px] text-neutral-500 hidden md:block">
          <div>EMIS code</div>
          <div className="font-semibold text-neutral-700">{p.school.code}</div>
        </div>
      </div>

      {/* title */}
      <div className="text-center my-3">
        <div className="text-base md:text-lg font-bold tracking-[0.2em] uppercase">Academic Transcript</div>
        <div className="text-[12px] text-neutral-600">Complete terminal-results record · issued {new Date().getFullYear()}</div>
      </div>

      {/* particulars */}
      <div className="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-1 border border-neutral-400 rounded-sm px-4 py-2.5 mb-4">
        <div><span className="text-neutral-500">Name: </span><span className="font-semibold">{studentName}</span></div>
        <div><span className="text-neutral-500">Index No: </span><span className="font-semibold">{p.student.nyansapoIndex}</span></div>
        <div><span className="text-neutral-500">Gender: </span><span className="font-semibold">{p.student.gender ?? "—"}</span></div>
        <div><span className="text-neutral-500">Date of birth: </span><span className="font-semibold">{p.student.dateOfBirth ? String(p.student.dateOfBirth).slice(0, 10) : "—"}</span></div>
        <div><span className="text-neutral-500">Terms on record: </span><span className="font-semibold">{p.summary.terms}</span></div>
        <div><span className="text-neutral-500">Best aggregate: </span><span className="font-semibold tabular-nums">{p.summary.bestAggregate ?? "—"}</span></div>
      </div>

      {/* one section per term, oldest first */}
      {p.terms.length ? p.terms.map((t, idx) => (
        <section key={t.termId} className={cn("mb-5", idx > 0 && "pt-3 border-t border-dashed border-neutral-300")}>
          <div className="flex flex-wrap items-baseline justify-between gap-2 mb-1.5">
            <div className="text-[13.5px] font-bold tracking-wide uppercase">{t.termName} · {t.yearName}</div>
            <div className="text-[11.5px] text-neutral-600">Class: {t.classroomName || "—"}</div>
          </div>
          <table className="w-full border-collapse text-[11.5px]">
            <thead>
              <tr className="bg-neutral-100 text-left">
                <th className="border border-neutral-400 px-2 py-1">Subject</th>
                <th className="border border-neutral-400 px-2 py-1 text-center w-14">Class <span className="text-[9.5px]">(30)</span></th>
                <th className="border border-neutral-400 px-2 py-1 text-center w-14">Exam <span className="text-[9.5px]">(70)</span></th>
                <th className="border border-neutral-400 px-2 py-1 text-center w-14">Total <span className="text-[9.5px]">(100)</span></th>
                <th className="border border-neutral-400 px-2 py-1 text-center w-12">Grade</th>
                <th className="border border-neutral-400 px-2 py-1 text-center w-12">Pos.</th>
                <th className="border border-neutral-400 px-2 py-1">Remark</th>
              </tr>
            </thead>
            <tbody>
              {t.rows.length ? t.rows.map((r, i) => (
                <tr key={i}>
                  <td className="border border-neutral-400 px-2 py-0.5">{r.subjectName}</td>
                  <td className="border border-neutral-400 px-2 py-0.5 text-center tabular-nums">{r.classScore ?? "—"}</td>
                  <td className="border border-neutral-400 px-2 py-0.5 text-center tabular-nums">{r.examScore ?? "—"}</td>
                  <td className="border border-neutral-400 px-2 py-0.5 text-center font-semibold tabular-nums">{r.total ?? "—"}</td>
                  <td className="border border-neutral-400 px-2 py-0.5 text-center font-semibold">{r.grade ?? "—"}</td>
                  <td className="border border-neutral-400 px-2 py-0.5 text-center tabular-nums">{r.position ? ordinal(r.position) : "—"}</td>
                  <td className="border border-neutral-400 px-2 py-0.5">{r.remark ?? ""}</td>
                </tr>
              )) : (
                <tr><td colSpan={7} className="border border-neutral-400 px-2 py-2 text-center text-neutral-500">No subject results recorded this term.</td></tr>
              )}
            </tbody>
          </table>
          <div className="grid grid-cols-2 md:grid-cols-5 gap-x-4 gap-y-1 border border-neutral-400 border-t-0 rounded-b-sm px-3 py-1.5 text-[11px]">
            <div><span className="text-neutral-500">Average: </span><span className="font-semibold tabular-nums">{t.average !== null ? fmt1(t.average) : "—"}</span></div>
            <div><span className="text-neutral-500">Position: </span><span className="font-semibold">{ordinal(t.classPosition)}{t.classSize ? ` of ${t.classSize}` : ""}</span></div>
            <div><span className="text-neutral-500">Aggregate: </span><span className="font-semibold tabular-nums">{t.aggregate ?? "—"}</span></div>
            <div><span className="text-neutral-500">Attendance: </span><span className="font-semibold">{t.attendancePct !== null && t.attendancePct !== undefined ? `${t.attendancePct}%` : "—"}</span></div>
            <div><span className="text-neutral-500">Promotion: </span><span className="font-semibold">{t.promoted === null || t.promoted === undefined ? "—" : t.promoted ? "Promoted" : "Not yet"}</span></div>
          </div>
        </section>
      )) : (
        <div className="border border-neutral-400 px-3 py-6 text-center text-neutral-500 mb-5">No terminal results on record yet for this learner.</div>
      )}

      {/* aggregate progression strip */}
      {p.terms.length > 1 && (
        <div className="flex flex-wrap items-center gap-2 mb-6 text-[11px]">
          <span className="text-neutral-500 font-semibold uppercase tracking-wide">Aggregate progression:</span>
          {p.terms.map((t) => (
            <span key={t.termId} className="border border-neutral-400 rounded-sm px-2 py-0.5 bg-neutral-50">
              {t.termName.replace(" Term", "")} <b className="tabular-nums">{t.aggregate ?? "—"}</b>
            </span>
          ))}
        </div>
      )}

      {/* overall + signature */}
      <div className="mb-6 text-[12px]">
        <span className="text-neutral-500">Overall average across {p.summary.terms} term{p.summary.terms === 1 ? "" : "s"}: </span>
        <span className="font-semibold tabular-nums">{p.summary.overallAverage !== null ? `${fmt1(p.summary.overallAverage)}%` : "—"}</span>
      </div>
      <div className="grid grid-cols-2 gap-10 pt-2 text-[12px]">
        <div>
          <div className="border-t border-neutral-800 w-3/4" />
          <div className="mt-1 text-neutral-600">Headteacher</div>
          <div className="mt-3 text-neutral-600">Date: ..............................</div>
        </div>
        <div className="text-right self-end text-[11px] text-neutral-500">— END OF TRANSCRIPT —</div>
      </div>
    </div>
  );
}

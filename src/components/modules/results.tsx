"use client";
import { Fragment, useEffect, useMemo, useState } from "react";
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
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid, Cell } from "recharts";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Skeleton } from "@/components/ui/skeleton";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard, StatusBadge } from "@/components/ui-helpers";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  MarksheetSheet, MarksheetStylePicker,
  type MarksheetPayload, type MarksheetStyle, type CrecheGridData, type MarksheetBilling,
} from "@/components/marksheet-sheets";
import {
  Award, Calculator, Eye, GraduationCap, Printer, Users, BookOpen, TrendingUp, ClipboardCheck,
  Percent, Layers, RefreshCcw, ScrollText, Send, Undo2, Megaphone, BarChart3, Mail, Phone, PhoneOff,
  MailWarning, Gauge, ChevronDown, ChevronUp, BadgeCheck, AlertTriangle,
  UsersRound, MoveUpRight, ListChecks, Save, ClipboardList, Sparkles, SquareCheck,
  Table2, Download, BookCopy,
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

/* ── wave 35 — interactive student marksheet (/api/results/marksheet) ──
   Legacy student_marksheet.php parity: the per-learner terminal sheet
   the class teacher annotates — subject marks with positions, the 15-slot
   conduct grid, one interest pick, teacher + head-teacher remarks and
   the days-opened / days-present attendance line (aggregation table).
   Wave 46 — payload + print templates now live in marksheet-sheets.tsx
   (student_marksheet_print_view{,_2,_3,_creche,_creche_2} parity). */

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

/* ── wave 33 — exam tabulation sheet (/api/results/tabulation) ──
   The exams officer's master sheet: one row per learner, one
   CLASS / EXAM / TOTAL column group per subject, sub-totals, the
   grand total and a competition-ranked position, plus the
   pencilled-margin stats (high / low / mean / pass / distinctions)
   for every subject. */
type TabCell = {
  classScore: number | null; examScore: number | null; total: number | null;
  grade: string | null; position: number | null; published: boolean;
};
type TabRow = {
  studentId: string; name: string; indexNo: string; gender: string;
  cells: Record<string, TabCell>;
  classTotal: number | null; examTotal: number | null; grandTotal: number | null;
  position: number | null; compiled: boolean; published: boolean;
};
type TabSubjectStat = {
  subjectId: string; entries: number;
  highest: number | null; lowest: number | null; average: number | null;
  passRate: number | null; distinctions: number;
};
type TabulationPayload = {
  school: { id: string; name: string; code: string; logoColor: string };
  term: { id: string; name: string; yearName: string };
  classroom: { id: string; name: string };
  subjects: { id: string; name: string; code: string }[];
  rows: TabRow[];
  subjectStats: TabSubjectStat[];
  summary: {
    onRoll: number; compiled: number; published: number; subjects: number;
    entries: number; grandAvg: number | null; passRate: number | null;
  };
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

/* ── wave 23 — end-of-year promotion board (/api/results/promotion-board) ── */
type PromotionRow = {
  studentId: string; name: string; indexNo: string; gender: string;
  cardId: string | null;
  average: number | null; aggregate: number | null; attendancePct: number | null;
  classPosition: number | null; classSize: number | null;
  cardPromoted: boolean | null; savedDecision: string | null; cardPublished: boolean;
  proposal: "PROMOTED" | "PROBATION" | "UNDECIDED";
  autoDestinationId: string | null;
  graduating: boolean;
};
type PromotionBoard = {
  school: { id: string };
  term: { id: string; name: string; yearName: string };
  classroom: { id: string; name: string; gradeLevel: string };
  nextGradeLevel: string | null;
  destinationClassroom: { id: string; name: string; gradeLevel: string } | null;
  classrooms: { id: string; name: string; gradeLevel: string }[];
  rows: PromotionRow[];
  summary: { onRoll: number; withCards: number; promoted: number; probation: number; undecided: number; graduating: number };
};
type RolloverResult = {
  ok: true;
  moved: { name: string; to: string }[];
  graduated: { name: string }[];
  stamped: { name: string; decision: string }[];
  skipped: { name: string; reason: string }[];
  onRoll: number;
  classroom: { name: string };
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
  /* wave 23 — promotion board */
  const [board, setBoard] = useState<PromotionBoard | null>(null);
  const [boardLoading, setBoardLoading] = useState(false);
  const [decisions, setDecisions] = useState<Record<string, string>>({});
  const [destinations, setDestinations] = useState<Record<string, string>>({});
  const [savingDecisions, setSavingDecisions] = useState(false);
  const [confirmRoll, setConfirmRoll] = useState(false);
  const [rollBusy, setRollBusy] = useState(false);
  const [printRoll, setPrintRoll] = useState(false);
  /* wave 33 — exam tabulation sheet + bulk cumulative pack */
  const [tabDensity, setTabDensity] = useState<"summary" | "raw">("summary");
  const [tabData, setTabData] = useState<TabulationPayload | null>(null);
  const [tabLoading, setTabLoading] = useState(false);
  const [packOpen, setPackOpen] = useState(false);
  const [packBusy, setPackBusy] = useState(false);
  const [packProgress, setPackProgress] = useState(0);
  const [pack, setPack] = useState<TranscriptPayload[]>([]);
  /* wave 35 — interactive student marksheet (legacy student_marksheet parity) */
  const [sheetStudent, setSheetStudent] = useState("");
  const [sheet, setSheet] = useState<MarksheetPayload | null>(null);
  const [sheetLoading, setSheetLoading] = useState(false);
  const [sheetSave, setSheetSave] = useState(false);
  const [sheetPrint, setSheetPrint] = useState(false);
  /* wave 46 — print-template family + billing + creche grid + bulk set */
  const [sheetStyle, setSheetStyle] = useState<MarksheetStyle>("classic");
  const [sheetBilling, setSheetBilling] = useState<MarksheetBilling | null>(null);
  const [crecheGrid, setCrecheGrid] = useState<CrecheGridData | null>(null);
  const [bulkSheets, setBulkSheets] = useState<{
    busy: boolean; done: number; total: number; sheets: MarksheetPayload[];
    billings: Record<string, MarksheetBilling | null>; grid: CrecheGridData | null; mode: "academic" | "kg" | "early";
  } | null>(null);

  /* iEduPro nested-nav parity: deep-link the analytics tab */
  const [appliedTarget, setAppliedTarget] = useState<NavTarget | null>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab === "analytics") setTab("analytics");
    if (navTarget?.tab === "promotion") setTab("promotion");
    if (navTarget?.tab === "tabulation") setTab("tabulation");
    if (navTarget?.tab === "marksheet") setTab("marksheet");
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

  /* wave 35 — the marksheet roster follows the class selector */
  useEffect(() => { setSheetStudent(""); setSheet(null); }, [classId]);

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

  /* wave 35 — load one learner's interactive marksheet. The roster the
     picker reads from is the class-term grid already loaded above. */
  useEffect(() => {
    if (!currentSchoolId || !sheetStudent || !termId) return;
    let alive = true;
    setSheetLoading(true);
    apiGet<MarksheetPayload>(`/api/results/marksheet?schoolId=${currentSchoolId}&studentId=${sheetStudent}&termId=${termId}`)
      .then((d) => { if (alive) { setSheet(d); setSheetLoading(false); } })
      .catch((e) => {
        if (alive) {
          setSheetLoading(false);
          setSheet(null);
          toast({ title: "Could not open the marksheet", description: e.message, variant: "destructive" });
        }
      });
    return () => { alive = false; };
  }, [currentSchoolId, sheetStudent, termId, refresh]);

  /* wave 35 — conducts_update parity: persist the annotated sheet. */
  async function saveSheet(draft: {
    conductIds: string[]; interestId: string;
    teacherRemark: string; remarkTemplateId: string;
    headRemark: string; headTemplateId: string;
    daysOpened: string; daysPresent: string;
  }) {
    if (!currentSchoolId || !sheet) return;
    setSheetSave(true);
    try {
      const res = await apiPost<{
        ok: true; cardId: string; studentName: string; termName: string;
        saved: { conductCount: number; attendance: string; remarkSource: string | null; headSource: string | null };
      }>("/api/results/marksheet", {
        action: "save", schoolId: currentSchoolId,
        studentId: sheet.student.id, termId: sheet.term.id,
        conductIds: draft.conductIds, interestId: draft.interestId || undefined,
        teacherRemark: draft.teacherRemark, remarkTemplateId: draft.remarkTemplateId || undefined,
        headRemark: draft.headRemark, headTemplateId: draft.headTemplateId || undefined,
        daysOpened: draft.daysOpened === "" ? undefined : Number(draft.daysOpened),
        daysPresent: draft.daysPresent === "" ? undefined : Number(draft.daysPresent),
      });
      toast({
        title: "Conducts & remarks saved",
        description: `${res.studentName} · ${res.termName}: ${res.saved.conductCount} conduct trait${res.saved.conductCount === 1 ? "" : "s"}, attendance "${res.saved.attendance ?? ""}".`,
      });
      setRefresh((r) => r + 1);
      const d = await apiGet<MarksheetPayload>(`/api/results/marksheet?schoolId=${currentSchoolId}&studentId=${sheet.student.id}&termId=${sheet.term.id}`);
      setSheet(d);
    } catch (e: any) {
      toast({ title: "Save refused", description: e.message, variant: "destructive" });
    } finally {
      setSheetSave(false);
    }
  }

  /* ── wave 46 — print-template family ────────────────────────────
     Early-years classrooms (creche / nursery / playgroup) print the
     developmental-domain sheet; KG classes offer all four templates;
     academic classes get classic / modern / compact. Billing splits the
     learner's invoice balances into previous arrears vs this-term bills
     (legacy accounts/arrears_tb parity); the creche-ratings grid feeds
     the per-skill rating codes of the early-years sheet. */
  const sheetClassInfo = useMemo(() => {
    const rooms = meta?.classrooms ?? [];
    const room = sheet?.classroom
      ? rooms.find((c) => c.id === sheet!.classroom!.id) ?? rooms.find((c) => c.name === sheet!.classroom!.name)
      : null;
    return room ?? null;
  }, [meta, sheet]);
  const sheetMode: "academic" | "kg" | "early" = useMemo(() => {
    if (!sheetClassInfo) return "academic";
    const label = `${sheetClassInfo.gradeLevel} ${sheetClassInfo.name}`;
    if (/CRECHE|NURS|PLAY/i.test(label)) return "early";
    if (/KG/i.test(sheetClassInfo.gradeLevel)) return "kg";
    return "academic";
  }, [sheetClassInfo]);
  const sheetEffectiveStyle: MarksheetStyle = sheetMode === "early" ? "creche" : sheetStyle;

  /* billing for the accounts block — fetched while the print dialog is open */
  useEffect(() => {
    if (!sheetPrint || !sheet) { setSheetBilling(null); return; }
    let alive = true;
    apiGet<{ invoices: { invoiceNo: string; term: string; balance: number; status: string }[] }>(`/api/student/${sheet.student.id}`)
      .then((full) => {
        if (!alive) return;
        const thisTerm = `${sheet.term.name} ${sheet.term.yearName}`;
        const invs = (full.invoices ?? []).map((i) => ({ ...i, balanceCents: i.balance }));
        const arrearsCents = invs.filter((i) => i.term !== thisTerm).reduce((s, i) => s + Math.max(0, i.balanceCents), 0);
        const nextTermCents = invs.filter((i) => i.term === thisTerm).reduce((s, i) => s + Math.max(0, i.balanceCents), 0);
        setSheetBilling({
          arrearsCents, nextTermCents,
          lines: invs.filter((i) => i.balanceCents > 0).slice(0, 6).map((i) => ({ invoiceNo: i.invoiceNo, term: i.term, balanceCents: i.balanceCents, status: i.status })),
        });
      })
      .catch(() => { if (alive) setSheetBilling(null); });
    return () => { alive = false; };
  }, [sheetPrint, sheet]);

  /* creche-ratings grid — only when the early-years sheet is in play */
  useEffect(() => {
    if (!sheetPrint || !sheet || !sheet.classroom || sheetEffectiveStyle !== "creche") { setCrecheGrid(null); return; }
    let alive = true;
    apiGet<CrecheGridData>(`/api/school/${currentSchoolId}/ops/academics?action=creche-ratings&classroomId=${sheet.classroom.id}&termId=${sheet.term.id}`)
      .then((g) => { if (alive) setCrecheGrid(g); })
      .catch(() => { if (alive) setCrecheGrid(null); });
    return () => { alive = false; };
  }, [sheetPrint, sheet, sheetEffectiveStyle, currentSchoolId]);

  /* ── wave 46 — bulk class marksheet set (legacy
     student_marksheet_bulk_print_view{,_creche} parity): the whole class
     in one printable set — one sheet per learner, each on its own page. */
  async function openBulkSheets() {
    if (!currentSchoolId || !termId || !classId || !data?.students.length || !meta) return;
    const cls = meta.classrooms.find((c) => c.id === classId);
    const mode: "academic" | "kg" | "early" = cls
      ? (/CRECHE|NURS|PLAY/i.test(`${cls.gradeLevel} ${cls.name}`) ? "early" : /KG/i.test(cls.gradeLevel) ? "kg" : "academic")
      : "academic";
    setBulkSheets({ busy: true, done: 0, total: data.students.length, sheets: [], billings: {}, grid: null, mode });
    try {
      let grid: CrecheGridData | null = null;
      if (mode !== "academic") {
        grid = await apiGet<CrecheGridData>(`/api/school/${currentSchoolId}/ops/academics?action=creche-ratings&classroomId=${classId}&termId=${termId}`).catch(() => null);
      }
      const sheets: MarksheetPayload[] = [];
      const billings: Record<string, MarksheetBilling | null> = {};
      for (const st of data.students) {
        const d = await apiGet<MarksheetPayload>(`/api/results/marksheet?schoolId=${currentSchoolId}&studentId=${st.id}&termId=${termId}`);
        sheets.push(d);
        const full = await apiGet<{ invoices: { invoiceNo: string; term: string; balance: number; status: string }[] }>(`/api/student/${st.id}`).catch(() => null);
        if (full) {
          const thisTerm = `${d.term.name} ${d.term.yearName}`;
          const invs = (full.invoices ?? []).map((i) => ({ ...i, balanceCents: i.balance }));
          billings[st.id] = {
            arrearsCents: invs.filter((i) => i.term !== thisTerm).reduce((s, i) => s + Math.max(0, i.balanceCents), 0),
            nextTermCents: invs.filter((i) => i.term === thisTerm).reduce((s, i) => s + Math.max(0, i.balanceCents), 0),
            lines: invs.filter((i) => i.balanceCents > 0).slice(0, 6).map((i) => ({ invoiceNo: i.invoiceNo, term: i.term, balanceCents: i.balanceCents, status: i.status })),
          };
        } else billings[st.id] = null;
        setBulkSheets((b) => (b ? { ...b, done: b.done + 1, sheets: [...sheets], billings: { ...billings } } : b));
      }
      setBulkSheets({ busy: false, done: sheets.length, total: sheets.length, sheets, billings, grid, mode });
      toast({
        title: "Class marksheet set ready",
        description: `${sheets.length} sheet${sheets.length === 1 ? "" : "s"} assembled${mode === "early" ? " — early-years developmental records" : ""}. Print the whole set.`,
      });
    } catch (e: any) {
      toast({ title: "Bulk print failed", description: e.message, variant: "destructive" });
      setBulkSheets(null);
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

  /* wave 33 — tabulation sheet, fetched while the Tabulation tab is open;
     recomputed compiles refresh it like the rest of the module. */
  useEffect(() => {
    if (!currentSchoolId || !termId || !classId || tab !== "tabulation") return;
    let alive = true;
    setTabLoading(true);
    apiGet<TabulationPayload>(`/api/results/tabulation?schoolId=${currentSchoolId}&termId=${termId}&classId=${classId}`)
      .then((d) => { if (alive) { setTabData(d); setTabLoading(false); } })
      .catch((e) => {
        if (alive) {
          setTabLoading(false);
          toast({ title: "Could not load the tabulation sheet", description: e.message, variant: "destructive" });
        }
      });
    return () => { alive = false; };
  }, [currentSchoolId, termId, classId, tab, refresh]);

  /** Export the tabulation matrix as CSV (exams-office hand-off). */
  function exportTabulation() {
    if (!tabData) return;
    const esc = (v: string | number | null | undefined) => {
      const s = v === null || v === undefined ? "" : String(v);
      return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
    };
    const head = ["#", "Learner", "Index No."];
    for (const s of tabData.subjects) head.push(`${s.name} CA`, `${s.name} Exam`, `${s.name} Total`, `${s.name} Grade`);
    head.push("CA 30 Total", "Exam 70 Total", "Grand Total", "Position");
    const lines = [head.map(esc).join(",")];
    tabData.rows.forEach((r, i) => {
      const cells: (string | number | null)[] = [i + 1, r.name, r.indexNo];
      for (const s of tabData.subjects) {
        const c = r.cells[s.id];
        cells.push(c?.classScore ?? "", c?.examScore ?? "", c?.total ?? "", c?.grade ?? "");
      }
      cells.push(r.classTotal ?? "", r.examTotal ?? "", r.grandTotal ?? "", r.position ?? "");
      lines.push(cells.map(esc).join(","));
    });
    const blob = new Blob([lines.join("\n")], { type: "text/csv;charset=utf-8" });
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = `tabulation-${tabData.classroom.name.replace(/\s+/g, "-")}-${tabData.term.name.replace(/\s+/g, "-")}.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
    toast({ title: "CSV exported", description: `${tabData.rows.length} learners × ${tabData.subjects.length} subjects — opens in Excel or Google Sheets.` });
  }

  /** Bulk cumulative pack — every learner's full academic transcript,
   *  fetched one by one and printed as a single class set (CI3
   *  cummulative_reports_bulk parity). */
  async function openPack() {
    if (!currentSchoolId || !data?.students.length) return;
    setPackOpen(true);
    setPackBusy(true);
    setPack([]);
    setPackProgress(0);
    try {
      const sheets: TranscriptPayload[] = [];
      for (const st of data.students) {
        const d = await apiGet<TranscriptPayload>(`/api/results/transcript?schoolId=${currentSchoolId}&studentId=${st.id}`);
        sheets.push(d);
        setPackProgress((p) => p + 1);
      }
      setPack(sheets);
      toast({ title: "Cumulative pack ready", description: `${sheets.length} learner transcript${sheets.length === 1 ? "" : "s"} assembled — print the whole set.` });
    } catch (e: any) {
      toast({ title: "Pack failed", description: e.message, variant: "destructive" });
      setPackOpen(false);
    } finally {
      setPackBusy(false);
    }
  }

  /* wave 23 — promotion board, fetched while the Promotion tab is open;
     seeded with the ledger's saved decision (or the auto proposal) and
     the auto destination from the grade ladder, then fully editable. */
  useEffect(() => {
    if (!currentSchoolId || !termId || !classId || tab !== "promotion") return;
    let alive = true;
    setBoardLoading(true);
    apiGet<PromotionBoard>(`/api/results/promotion-board?schoolId=${currentSchoolId}&termId=${termId}&classId=${classId}`)
      .then((d) => {
        if (!alive) return;
        setBoard(d);
        const dec: Record<string, string> = {};
        const dst: Record<string, string> = {};
        for (const r of d.rows) {
          dec[r.studentId] = r.savedDecision ?? (r.proposal === "UNDECIDED" ? "UNDECIDED" : r.proposal);
          dst[r.studentId] = r.autoDestinationId ?? (r.graduating ? "GRADUATE" : "");
        }
        setDecisions(dec);
        setDestinations(dst);
        setBoardLoading(false);
      })
      .catch((e) => {
        if (alive) {
          setBoardLoading(false);
          toast({ title: "Could not load the promotion board", description: e.message, variant: "destructive" });
        }
      });
    return () => { alive = false; };
  }, [currentSchoolId, termId, classId, tab, refresh]);

  /* wave 23 — effective (editable) decision & destination per learner */
  const effDecision = (r: PromotionRow) =>
    decisions[r.studentId] ?? (r.savedDecision ?? (r.proposal === "UNDECIDED" ? "UNDECIDED" : r.proposal));
  const effDestination = (r: PromotionRow) =>
    destinations[r.studentId] ?? (r.autoDestinationId ?? (r.graduating ? "GRADUATE" : ""));

  /* counts + move plan, recomputed as the head teacher edits the board */
  const rollPlan = useMemo(() => {
    let promoted = 0, graduate = 0, stay = 0, undecided = 0;
    const moves: { studentId: string; decision: string; toClassroomId?: string; name: string; to: string }[] = [];
    for (const r of board?.rows ?? []) {
      const d = decisions[r.studentId] ?? (r.savedDecision ?? (r.proposal === "UNDECIDED" ? "UNDECIDED" : r.proposal));
      if (!r.cardId || d === "UNDECIDED") { undecided++; continue; }
      if (d === "PROMOTED") {
        const dest = destinations[r.studentId] ?? (r.autoDestinationId ?? (r.graduating ? "GRADUATE" : ""));
        if (dest === "GRADUATE") { graduate++; moves.push({ studentId: r.studentId, decision: d, name: r.name, to: "Graduate" }); }
        else {
          promoted++;
          moves.push({ studentId: r.studentId, decision: d, toClassroomId: dest || undefined, name: r.name, to: board?.classrooms.find((c) => c.id === dest)?.name ?? "No class picked" });
        }
      } else {
        stay++;
        moves.push({ studentId: r.studentId, decision: d, name: r.name, to: "Stays put" });
      }
    }
    return { promoted, graduate, stay, undecided, decided: promoted + graduate + stay, moves };
  }, [board, decisions, destinations]);

  /* decision rows that differ from the ledger — powers the Save button */
  const dirtyDecisions = useMemo(
    () => (board?.rows ?? []).filter((r) => {
      const eff = decisions[r.studentId] ?? (r.savedDecision ?? (r.proposal === "UNDECIDED" ? "UNDECIDED" : r.proposal));
      return !!r.cardId && eff !== "UNDECIDED" && eff !== r.savedDecision;
    }).length,
    [board, decisions],
  );

  function acceptProposals() {
    if (!board) return;
    const dec: Record<string, string> = { ...decisions };
    for (const r of board.rows) if (r.proposal !== "UNDECIDED") dec[r.studentId] = r.proposal;
    setDecisions(dec);
    toast({ title: "Proposals accepted", description: "Every learner now carries the ledger's proposal — overrule anyone, then save or execute." });
  }

  async function saveDecisions() {
    if (!currentSchoolId || !termId || !classId || !board) return;
    const groups = new Map<string, string[]>();
    for (const r of board.rows) {
      if (!r.cardId) continue;
      const eff = effDecision(r);
      if (eff !== "UNDECIDED" && eff !== r.savedDecision) {
        const list = groups.get(eff) ?? [];
        list.push(r.studentId);
        groups.set(eff, list);
      }
    }
    if (!groups.size) { toast({ title: "Nothing to save", description: "Every decision already matches the ledger." }); return; }
    setSavingDecisions(true);
    try {
      let n = 0;
      for (const [decision, studentIds] of groups) {
        const res = await apiPost<{ decided: number }>("/api/results/promotion-board", {
          schoolId: currentSchoolId, termId, classId, action: "decide", decision, studentIds,
        });
        n += res.decided;
      }
      toast({ title: "Decisions saved", description: `${n} report card${n === 1 ? "" : "s"} updated on the ledger.` });
      setRefresh((r) => r + 1);
    } catch (e: any) {
      toast({ title: "Save failed", description: e.message, variant: "destructive" });
    } finally {
      setSavingDecisions(false);
    }
  }

  async function executeRollover() {
    if (!currentSchoolId || !termId || !classId || !rollPlan.moves.length) return;
    setRollBusy(true);
    try {
      const res = await apiPost<RolloverResult>("/api/results/promotion-board", {
        schoolId: currentSchoolId, termId, classId, action: "rollover",
        moves: rollPlan.moves.map((m) => ({ studentId: m.studentId, decision: m.decision, toClassroomId: m.toClassroomId })),
      });
      const bits: string[] = [];
      if (res.moved.length) bits.push(`${res.moved.length} moved to the next class`);
      if (res.graduated.length) bits.push(`${res.graduated.length} graduated`);
      if (res.stamped.length) bits.push(`${res.stamped.length} staying (card stamped)`);
      if (res.skipped.length) bits.push(`${res.skipped.length} skipped`);
      toast({ title: `Rollover complete — ${res.classroom.name}`, description: bits.join(" · ") || "Nothing to move." });
      setConfirmRoll(false);
      setRefresh((r) => r + 1);
    } catch (e: any) {
      toast({ title: "Rollover failed", description: e.message, variant: "destructive" });
    } finally {
      setRollBusy(false);
    }
  }

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
          <TabsTrigger value="tabulation" className="gap-1.5"><Table2 className="size-4" /> Tabulation</TabsTrigger>
          <TabsTrigger value="marksheet" className="gap-1.5"><ClipboardList className="size-4" /> Marksheets</TabsTrigger>
          <TabsTrigger value="analytics" className="gap-1.5"><BarChart3 className="size-4" /> Analytics</TabsTrigger>
          <TabsTrigger value="promotion" className="gap-1.5"><UsersRound className="size-4" /> Promotion</TabsTrigger>
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

        {/* ── wave 35 — interactive student marksheet (legacy
            student_marksheet / conducts_update parity): the per-learner
            terminal sheet with the 15-slot conduct grid, interest pick,
            teacher + head-teacher remarks and the attendance line ── */}
        <TabsContent value="marksheet" className="mt-4">
          <div className="flex flex-wrap items-center gap-2 mb-3">
            <Select value={sheetStudent} onValueChange={setSheetStudent}>
              <SelectTrigger className="w-full sm:w-80" aria-label="Learner"><SelectValue placeholder="Pick a learner to open their marksheet" /></SelectTrigger>
              <SelectContent className="max-h-96 overflow-y-auto nice-scroll">
                {(data?.students ?? []).map((st) => (
                  <SelectItem key={st.id} value={st.id}>
                    {st.name} · {st.indexNo}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {sheetStudent && data?.students.length ? (
              <Badge variant="outline" className="text-[11px]">
                {(data.students.find((s) => s.id === sheetStudent)?.name ?? "").split(" ").slice(-2).join(" ")}
              </Badge>
            ) : null}
            {sheet?.card?.published ? <Badge className="bg-emerald-100 text-emerald-800 border-transparent dark:bg-emerald-950 dark:text-emerald-300">Published</Badge> : null}
            <Button size="sm" variant="outline" className="gap-1.5" disabled={!data?.students.length || bulkSheets?.busy} onClick={openBulkSheets}
              title="student_marksheet_bulk_print_view parity — the whole class in one printable set, early-years classes get the developmental-domain sheets">
              <Layers className={cn("size-4", bulkSheets?.busy && "animate-pulse")} /> {bulkSheets?.busy ? `Ruling ${bulkSheets.done}/${bulkSheets.total}…` : "Bulk marksheet print"}
            </Button>
            <span className="ml-auto text-[11px] text-muted-foreground">
              {data?.students.length ?? 0} learner{(data?.students.length ?? 0) === 1 ? "" : "s"} on roll · {termLabel?.name ?? ""}
            </span>
          </div>
          {sheetLoading ? (
            <LoadingBlock label="Ruling the marksheet…" />
          ) : !sheetStudent ? (
            <Card>
              <CardContent className="py-10">
                <EmptyState
                  icon={<ClipboardList className="size-8" />}
                  title="Pick a learner above"
                  hint="Their terminal marksheet opens here — subject marks with class positions, the conduct &amp; interest grid, teacher and head-teacher remarks, and the term's attendance line, exactly like the classic result sheet."
                />
              </CardContent>
            </Card>
          ) : sheet ? (
            <MarksheetPanel
              payload={sheet}
              saving={sheetSave}
              onSave={saveSheet}
              onPrint={() => setSheetPrint(true)}
              canPublish={canPublish}
            />
          ) : null}
        </TabsContent>

        <TabsContent value="analytics" className="mt-4">
          <AnalyticsPanel
            payload={analytics}
            loading={analyticsLoading}
            className={meta.classrooms.find((c) => c.id === classId)?.name ?? "class"}
            termLabel={termLabel ? `${termLabel.name} · ${termLabel.yearName}` : ""}
          />
        </TabsContent>

        {/* ── wave 33 — exam tabulation sheet (CI3 tabulation_sheet parity) ── */}
        <TabsContent value="tabulation" className="mt-4">
          {!tabData ? (
            tabLoading ? <LoadingBlock label="Ruling the tabulation sheet…" /> : (
              <Card>
                <CardContent className="py-8">
                  <EmptyState
                    icon={<Table2 className="size-8" />}
                    title="No tabulation sheet for this term yet"
                    hint="Run “Compute term results” on the Broadsheet tab first — the sheet reads the same ledger the report cards print from."
                  />
                </CardContent>
              </Card>
            )
          ) : (
            <div className="space-y-3">
              {/* summary chips + actions */}
              <div className="flex flex-wrap items-center gap-2 no-print">
                <Badge variant="outline" className="gap-1"><Users className="size-3" /> {tabData.summary.onRoll} on roll</Badge>
                <Badge variant="outline" className="gap-1"><BookOpen className="size-3" /> {tabData.summary.subjects} subjects · {tabData.summary.entries} entries</Badge>
                <Badge variant="outline" className="gap-1"><TrendingUp className="size-3" /> Class grand average {tabData.summary.grandAvg !== null ? `${fmt1(tabData.summary.grandAvg)}%` : "—"}</Badge>
                <Badge variant="outline" className="gap-1"><Percent className="size-3" /> Pass rate {tabData.summary.passRate !== null ? `${tabData.summary.passRate}%` : "—"}</Badge>
                <div className="ml-auto flex flex-wrap gap-2">
                  <div className="flex items-center rounded-md border p-0.5 text-[11px]">
                    <Button size="sm" variant={tabDensity === "summary" ? "secondary" : "ghost"} className="h-6 px-2" onClick={() => setTabDensity("summary")}>Summary</Button>
                    <Button size="sm" variant={tabDensity === "raw" ? "secondary" : "ghost"} className="h-6 px-2" onClick={() => setTabDensity("raw")}>Raw CA/Exam</Button>
                  </div>
                  <Button size="sm" variant="outline" className="gap-1.5" onClick={exportTabulation}>
                    <Download className="size-4" /> Export CSV
                  </Button>
                  <Button size="sm" variant="outline" className="gap-1.5" onClick={() => openPack()} disabled={packBusy || !data?.students.length}>
                    <BookCopy className="size-4" /> {packBusy ? `Assembling ${packProgress}/${data?.students.length ?? 0}…` : "Cumulative pack"}
                  </Button>
                  <Button size="sm" className="gap-1.5" onClick={() => window.print()}>
                    <Printer className="size-4" /> Print sheet
                  </Button>
                </div>
              </div>
              {!tabData.rows.some((r) => r.grandTotal !== null) && (
                <div className="no-print">
                  <EmptyState
                    icon={<Calculator className="size-8" />}
                    title="Nothing compiled for this class-term"
                    hint="The sheet prints empty until marks are computed — run “Compute term results” on the Broadsheet tab."
                  />
                </div>
              )}
              <div className="print-area">
                {tabDensity === "raw" && <style>{"@media print { @page { size: A4 landscape; margin: 8mm } }"}</style>}
                <TabulationSheet payload={tabData} density={tabDensity} />
              </div>
            </div>
          )}
        </TabsContent>

        {/* wave 23 — end-of-year promotion board */}
        <TabsContent value="promotion" className="mt-4">
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="flex flex-wrap items-center gap-2 text-base">
                <UsersRound className="size-4" /> Promotion board — {meta.classrooms.find((c) => c.id === classId)?.name ?? "class"}
                {boardLoading && <RefreshCcw className="size-3.5 animate-spin text-muted-foreground" aria-label="Loading board" />}
                {board && (
                  <Badge variant="outline" className="text-[11px] gap-1">
                    <MoveUpRight className="size-3" />
                    {board.nextGradeLevel
                      ? `Next: ${board.nextGradeLevel}${board.destinationClassroom ? ` · ${board.destinationClassroom.name}` : " · no class here"}`
                      : "Exit grade — finalists graduate"}
                  </Badge>
                )}
              </CardTitle>
            </CardHeader>
            <CardContent>
              {!board ? (
                boardLoading ? <LoadingBlock label="Reading the promotion ledger…" /> : (
                  <EmptyState icon={<UsersRound className="size-8" />} title="No board loaded" hint="Pick a term and a class to assemble the promotion board." />
                )
              ) : (
                <div className="space-y-3">
                  {/* summary chips */}
                  <div className="flex flex-wrap items-center gap-1.5 text-[11px]">
                    <Badge variant="outline" className="gap-1"><Users className="size-3" /> {board.summary.onRoll} on roll</Badge>
                    <Badge variant="outline" className="gap-1 bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-900"><MoveUpRight className="size-3" /> {rollPlan.promoted} moving up</Badge>
                    <Badge variant="outline" className="gap-1 bg-violet-50 text-violet-800 border-violet-200 dark:bg-violet-950 dark:text-violet-300 dark:border-violet-900"><GraduationCap className="size-3" /> {rollPlan.graduate} graduating</Badge>
                    <Badge variant="outline" className="gap-1 bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-900"><Gauge className="size-3" /> {rollPlan.stay} staying</Badge>
                    {rollPlan.undecided > 0 && (
                      <Badge variant="outline" className="gap-1"><AlertTriangle className="size-3" /> {rollPlan.undecided} without a card</Badge>
                    )}
                    <span className="ml-auto hidden md:block text-muted-foreground">{board.summary.withCards} of {board.summary.onRoll} compiled report card{board.summary.withCards === 1 ? "" : "s"} drive the proposals</span>
                  </div>

                  {/* actions */}
                  {canPublish ? (
                    <div className="flex flex-wrap gap-2">
                      <Button size="sm" variant="outline" className="gap-1.5" onClick={acceptProposals} disabled={!board.summary.withCards}>
                        <ListChecks className="size-4" /> Accept all proposals
                      </Button>
                      <Button size="sm" variant="outline" className="gap-1.5" onClick={saveDecisions} disabled={savingDecisions || dirtyDecisions === 0}>
                        <Save className={cn("size-4", savingDecisions && "animate-pulse")} /> {savingDecisions ? "Saving…" : `Save decisions${dirtyDecisions ? ` (${dirtyDecisions})` : ""}`}
                      </Button>
                      <Button size="sm" className="gap-1.5" onClick={() => setConfirmRoll(true)} disabled={rollBusy || rollPlan.decided === 0}>
                        <MoveUpRight className="size-4" /> Execute rollover
                      </Button>
                      <Button size="sm" variant="outline" className="gap-1.5 ml-auto" onClick={() => setPrintRoll(true)} disabled={!board.rows.length}>
                        <Printer className="size-4" /> Print list
                      </Button>
                    </div>
                  ) : (
                    <p className="text-xs text-muted-foreground">View only — saving decisions and running the rollover needs the generate &amp; publish reports permission.</p>
                  )}

                  {/* the board */}
                  <div className="rounded-xl border overflow-x-auto">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead className="min-w-[180px]">Learner</TableHead>
                          <TableHead className="text-right">Avg</TableHead>
                          <TableHead className="text-right">Agg</TableHead>
                          <TableHead className="text-right">Attend</TableHead>
                          <TableHead>Card</TableHead>
                          <TableHead>Proposal</TableHead>
                          <TableHead>Decision</TableHead>
                          <TableHead>Placement</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {board.rows.map((r) => {
                          const eff = effDecision(r);
                          const dest = effDestination(r);
                          return (
                            <TableRow key={r.studentId}>
                              <TableCell className="py-2">
                                <div className="font-medium leading-tight">{r.name}</div>
                                <div className="text-[11px] text-muted-foreground">{r.indexNo}{r.classPosition ? ` · ${ordinal(r.classPosition)} in class` : ""}</div>
                              </TableCell>
                              <TableCell className={cn("text-right tabular-nums font-semibold", bandTone(r.average).cell)}>{fmt1(r.average)}</TableCell>
                              <TableCell className="text-right tabular-nums">{r.aggregate ?? "—"}</TableCell>
                              <TableCell className="text-right tabular-nums">{r.attendancePct !== null ? `${r.attendancePct}%` : "—"}</TableCell>
                              <TableCell>
                                <Badge variant={r.cardPublished ? "default" : "outline"} className="text-[10px]">
                                  {r.cardId ? (r.cardPublished ? "Published" : "Draft") : "No card"}
                                </Badge>
                              </TableCell>
                              <TableCell><ProposalBadge proposal={r.proposal} /></TableCell>
                              <TableCell>
                                <Select value={eff} onValueChange={(v) => setDecisions((p) => ({ ...p, [r.studentId]: v }))} disabled={!canPublish || !r.cardId}>
                                  <SelectTrigger className="h-8 w-[122px] text-xs" aria-label={`Decision for ${r.name}`}><SelectValue /></SelectTrigger>
                                  <SelectContent>
                                    <SelectItem value="UNDECIDED">Undecided</SelectItem>
                                    <SelectItem value="PROMOTED">Promoted</SelectItem>
                                    <SelectItem value="PROBATION">Probation</SelectItem>
                                    <SelectItem value="REPEAT">Repeat</SelectItem>
                                  </SelectContent>
                                </Select>
                              </TableCell>
                              <TableCell>
                                <Select value={dest || "NONE"} onValueChange={(v) => setDestinations((p) => ({ ...p, [r.studentId]: v === "NONE" ? "" : v }))} disabled={!canPublish || eff !== "PROMOTED"}>
                                  <SelectTrigger className="h-8 w-[150px] text-xs" aria-label={`Placement for ${r.name}`}><SelectValue /></SelectTrigger>
                                  <SelectContent>
                                    <SelectItem value="GRADUATE">Graduate</SelectItem>
                                    <SelectItem value="NONE">Not set — stays put</SelectItem>
                                    {board.classrooms.filter((c) => c.id !== classId).map((c) => (
                                      <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>
                                    ))}
                                  </SelectContent>
                                </Select>
                              </TableCell>
                            </TableRow>
                          );
                        })}
                        {!board.rows.length && (
                          <TableRow><TableCell colSpan={8} className="py-8 text-center text-sm text-muted-foreground">No learners on roll in this class.</TableCell></TableRow>
                        )}
                      </TableBody>
                    </Table>
                  </div>
                  <p className="text-xs text-muted-foreground">
                    Proposals come from the compiled report cards (average ≥ 40 → promoted, below → probation). Overrule freely — decisions are stamped on the cards, and "Execute rollover" actually moves placements (promoted-from trail) or graduates the finalists.
                  </p>
                </div>
              )}
            </CardContent>
          </Card>
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

      {/* ── wave 46 — printable marksheet template family (legacy
          student_marksheet_print_view{,_2,_3,_creche,_creche_2} parity):
          Classic GES / Modern chart / Compact A5 — creche, nursery and
          playgroup classes automatically print the early-years sheet. ── */}
      <Dialog open={sheetPrint} onOpenChange={setSheetPrint}>
        <DialogContent className="max-w-3xl max-h-[92vh] overflow-y-auto nice-scroll">
          <DialogHeader className="no-print">
            <DialogTitle className="flex items-center gap-2"><Printer className="size-4" /> Terminal marksheet</DialogTitle>
            <DialogDescription>
              {sheetMode === "early"
                ? "Early-years progress record — grading key, developmental domains with per-skill rating codes, physical development, attendance and the accounts block."
                : sheetMode === "kg"
                  ? "Pick the template — the three terminal designs, or the early-years developmental sheet for this KG class."
                  : "Pick the template — Classic GES broadsheet with grading key and arrears, Modern with the subject chart, or the Compact A5 booklet."}
            </DialogDescription>
            <div className="flex flex-wrap items-center gap-2 pt-1">
              <MarksheetStylePicker value={sheetEffectiveStyle} set={setSheetStyle} mode={sheetMode} />
              {sheetBilling ? (
                <Badge variant="outline" className="text-[10.5px] font-mono" title="Previous arrears + this-term bills feeding the accounts block">
                  arrears GH¢ {((sheetBilling.arrearsCents ?? 0) / 100).toFixed(2)} · bills GH¢ {((sheetBilling.nextTermCents ?? 0) / 100).toFixed(2)}
                </Badge>
              ) : null}
              <Button size="sm" className="gap-1.5 ml-auto" onClick={() => window.print()}><Printer className="size-4" /> Print</Button>
            </div>
          </DialogHeader>
          {sheet ? (
            <div className="print-area">
              <MarksheetSheet payload={sheet} style={sheetEffectiveStyle} billing={sheetBilling} grid={crecheGrid} />
            </div>
          ) : null}
        </DialogContent>
      </Dialog>

      {/* ── wave 46 — bulk class marksheet set (legacy
          student_marksheet_bulk_print_view{,_2,_3,_creche,_creche_2}
          parity): one printable sheet per learner, each on its own page. ── */}
      <Dialog open={!!bulkSheets} onOpenChange={(o) => !o && setBulkSheets(null)}>
        <DialogContent className="max-w-3xl max-h-[92vh] overflow-y-auto nice-scroll">
          <DialogHeader className="no-print">
            <DialogTitle className="flex items-center gap-2"><Layers className="size-4" /> Class marksheet set</DialogTitle>
            <DialogDescription>
              {bulkSheets ? `${bulkSheets.sheets.length} of ${bulkSheets.total} sheet${bulkSheets.total === 1 ? "" : "s"} assembled — each prints on its own page.` : ""}
              {bulkSheets?.mode === "early" ? " Early-years developmental-domain records." : ""}
            </DialogDescription>
            <div className="flex flex-wrap items-center gap-2 pt-1">
              {bulkSheets && !bulkSheets.busy ? (
                <MarksheetStylePicker value={bulkSheets.mode === "early" ? "creche" : sheetStyle} set={setSheetStyle} mode={bulkSheets.mode} />
              ) : null}
              <Button size="sm" className="gap-1.5 ml-auto" onClick={() => window.print()} disabled={!bulkSheets?.sheets.length}>
                <Printer className="size-4" /> Print all {bulkSheets?.sheets.length ?? 0}
              </Button>
            </div>
          </DialogHeader>
          {bulkSheets?.busy ? (
            <LoadingBlock label={`Ruling the class set… ${bulkSheets.done}/${bulkSheets.total}`} />
          ) : (
            <div className="print-area space-y-5">
              {(bulkSheets?.sheets ?? []).map((s) => (
                <MarksheetSheet
                  key={s.student.id}
                  payload={s}
                  style={bulkSheets?.mode === "early" ? "creche" : sheetStyle}
                  billing={bulkSheets?.billings?.[s.student.id] ?? null}
                  grid={bulkSheets?.grid ?? null}
                />
              ))}
            </div>
          )}
        </DialogContent>
      </Dialog>

      {/* ── wave 23 — rollover confirmation: what actually happens to
          each placement the moment the head teacher confirms ── */}
      <AlertDialog open={confirmRoll} onOpenChange={setConfirmRoll}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle className="flex items-center gap-2"><UsersRound className="size-4" /> Execute the rollover?</AlertDialogTitle>
            <AlertDialogDescription>
              {rollPlan.promoted} learner{rollPlan.promoted === 1 ? "" : "s"} move into their destination class, {rollPlan.graduate} graduate{rollPlan.graduate === 1 ? "" : "s"} off the roll and {rollPlan.stay} stay{rollPlan.stay === 1 ? "" : ""} put with their card stamped. Placements carry the promoted-from trail.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <div className="max-h-48 overflow-y-auto nice-scroll rounded-xl border divide-y text-sm">
            {rollPlan.moves.map((m) => (
              <div key={m.studentId} className="flex items-center gap-2 px-3 py-1.5">
                <span className="flex-1 truncate">{m.name}</span>
                <span className={cn(
                  "text-[11px] font-medium",
                  m.decision === "PROMOTED" ? "text-emerald-700 dark:text-emerald-300" : m.decision === "REPEAT" ? "text-red-700 dark:text-red-300" : "text-amber-700 dark:text-amber-300",
                )}>
                  {m.decision === "PROMOTED" ? (m.to === "Graduate" ? "graduates" : `→ ${m.to}`) : m.decision === "REPEAT" ? "repeats" : "probation"}
                </span>
              </div>
            ))}
          </div>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={executeRollover} disabled={rollBusy}>
              {rollBusy ? "Working…" : "Execute rollover"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* ── wave 23 — printable promotion list ── */}
      <Dialog open={printRoll} onOpenChange={setPrintRoll}>
        <DialogContent className="max-w-4xl max-h-[92vh] overflow-y-auto nice-scroll">
          <DialogHeader className="no-print">
            <DialogTitle className="flex items-center gap-2"><Printer className="size-4" /> Promotion list</DialogTitle>
            <DialogDescription>Print on A4 — decisions, averages and placements with the headteacher&apos;s signature block.</DialogDescription>
            <div className="flex gap-2 pt-1">
              <Button size="sm" className="gap-1.5" onClick={() => window.print()} disabled={!board}><Printer className="size-4" /> Print</Button>
            </div>
          </DialogHeader>
          <div className="print-area">
            {board && meta && (
              <PromotionSheet
                school={meta.school}
                termLabel={termLabel ? `${termLabel.name} · ${termLabel.yearName}` : ""}
                className={meta.classrooms.find((c) => c.id === classId)?.name ?? ""}
                rows={board.rows.map((r) => {
                  const dest = effDestination(r);
                  return {
                    name: r.name, indexNo: r.indexNo, average: r.average, aggregate: r.aggregate,
                    attendancePct: r.attendancePct, decision: effDecision(r),
                    destination: dest === "GRADUATE" ? "Graduates" : (board.classrooms.find((c) => c.id === dest)?.name ?? "—"),
                  };
                })}
              />
            )}
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

      {/* ── wave 33 — bulk cumulative pack (CI3 cummulative_reports_bulk
          parity): every learner on roll, their whole academic history,
          one print job. Sheets use the standard transcript layout with
          a page break between learners. ── */}
      <Dialog open={packOpen} onOpenChange={(o) => { if (!packBusy) setPackOpen(o); }}>
        <DialogContent className="max-w-4xl max-h-[92vh] overflow-y-auto nice-scroll">
          <DialogHeader className="no-print">
            <DialogTitle className="flex items-center gap-2"><BookCopy className="size-4" /> Class cumulative pack</DialogTitle>
            <DialogDescription>
              {packBusy
                ? "Fetching every learner's transcript…"
                : `${pack.length} transcript${pack.length === 1 ? "" : "s"} — one A4 page set per learner. Print on A4 portrait.`}
            </DialogDescription>
            {packBusy && (
              <div className="pt-1">
                <div className="h-1.5 w-full rounded-full bg-secondary overflow-hidden">
                  <div className="h-full bg-primary transition-all" style={{ width: `${data?.students.length ? Math.round((packProgress / data.students.length) * 100) : 0}%` }} />
                </div>
                <p className="text-[11px] text-muted-foreground mt-1">{packProgress} of {data?.students.length ?? 0} learners fetched</p>
              </div>
            )}
            {!packBusy && pack.length > 0 && (
              <div className="flex gap-2 pt-1">
                <Button size="sm" className="gap-1.5" onClick={() => window.print()}><Printer className="size-4" /> Print all {pack.length}</Button>
              </div>
            )}
          </DialogHeader>
          {!packBusy && pack.length > 0 && (
            <div className="print-area">
              {pack.map((t) => (
                <div key={t.student.id} className="print-sheet mb-6">
                  <TranscriptSheet payload={t} />
                </div>
              ))}
            </div>
          )}
          {packBusy && (
            <div className="space-y-3 pb-4">
              <Skeleton className="h-16 w-full" />
              <Skeleton className="h-48 w-full" />
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
   Wave 35 — interactive student marksheet (legacy student_marksheet
   .php parity, modernised). The class teacher's working sheet:
   subject marks with positions, the 15-slot conduct grid, one
   interest pick, teacher + head-teacher remarks (template-first,
   custom text allowed — legacy select2-with-tags parity) and the
   days-opened / days-present attendance line.
   ══════════════════════════════════════════════════════════════ */
type MarksheetDraft = {
  conductIds: string[]; interestId: string;
  teacherRemark: string; remarkTemplateId: string; teacherCustom: boolean;
  headRemark: string; headTemplateId: string; headCustom: boolean;
  daysOpened: string; daysPresent: string;
};

/** Seed the annotation draft from the saved aggregation — the saved
 *  templates are matched by text so a template remark keeps its
 *  provenance, custom remarks open the free-text box, and the head
 *  remark falls back to the percentage-band auto-suggestion. */
function computeMarksheetDraft(p: MarksheetPayload): MarksheetDraft {
  const savedTeacherTpl = p.teacherTemplates.find((t) => t.text === p.card?.teacherRemark);
  const savedHeadTpl = p.headBands.find((b) => b.text === p.card?.headRemark);
  return {
    conductIds: p.card?.conductIds ?? [],
    interestId: p.card?.interestId ?? "",
    teacherRemark: p.card?.teacherRemark ?? "",
    remarkTemplateId: savedTeacherTpl?.id ?? "",
    teacherCustom: !savedTeacherTpl && !!p.card?.teacherRemark,
    headRemark: p.card?.headRemark ?? p.suggestedHead ?? "",
    headTemplateId: savedHeadTpl?.id ?? (p.card?.headRemark ? "" : p.headBands.find((b) => b.text === p.suggestedHead)?.id ?? ""),
    headCustom: !savedHeadTpl && !!p.card?.headRemark,
    daysOpened: p.card?.daysOpened !== null && p.card?.daysOpened !== undefined ? String(p.card.daysOpened) : p.attendance.daysTaken ? String(p.attendance.daysTaken) : "",
    daysPresent: p.card?.daysPresent !== null && p.card?.daysPresent !== undefined ? String(p.card.daysPresent) : p.attendance.daysAttended ? String(p.attendance.daysAttended) : "",
  };
}

function MarksheetPanel(props: {
  payload: MarksheetPayload;
  saving: boolean;
  onSave: (draft: {
    conductIds: string[]; interestId: string;
    teacherRemark: string; remarkTemplateId: string;
    headRemark: string; headTemplateId: string;
    daysOpened: string; daysPresent: string;
  }) => void;
  onPrint: () => void;
  canPublish: boolean;
}) {
  const { payload: p } = props;
  const studentName = [p.student.lastName, p.student.firstName, p.student.otherNames].filter(Boolean).join(" ");
  const conducts = p.conductItems.filter((c) => c.kind === "CONDUCT");
  const interests = p.conductItems.filter((c) => c.kind === "INTEREST");
  const cardKey = `${p.student.id}:${p.card?.id ?? "no-card"}`;

  /* draft state — reset when the underlying card changes (render-time
     derived-state reset, the sanctioned React pattern for prop-driven
     re-initialisation without an effect) */
  const [draft, setDraft] = useState(() => computeMarksheetDraft(p));
  const [draftKey, setDraftKey] = useState(cardKey);
  if (cardKey !== draftKey) {
    setDraftKey(cardKey);
    setDraft(computeMarksheetDraft(p));
  }

  const toggleConduct = (id: string, checked: boolean) => {
    setDraft((d) => {
      const next = checked ? [...d.conductIds, id] : d.conductIds.filter((x) => x !== id);
      if (next.length > 15) return d; // legacy c1..c15 cap
      return { ...d, conductIds: next };
    });
  };
  const pickTeacherTemplate = (id: string) => {
    if (id === "custom") { setDraft((d) => ({ ...d, remarkTemplateId: "", teacherCustom: true })); return; }
    const t = p.teacherTemplates.find((x) => x.id === id);
    setDraft((d) => ({ ...d, remarkTemplateId: id, teacherCustom: false, teacherRemark: t?.text ?? d.teacherRemark }));
  };
  const pickHeadTemplate = (id: string) => {
    if (id === "custom") { setDraft((d) => ({ ...d, headTemplateId: "", headCustom: true })); return; }
    const b = p.headBands.find((x) => x.id === id);
    setDraft((d) => ({ ...d, headTemplateId: id, headCustom: false, headRemark: b?.text ?? d.headRemark }));
  };
  const attendanceLine = draft.daysOpened !== "" && draft.daysPresent !== ""
    ? `Present ${draft.daysPresent} of ${draft.daysOpened} day${draft.daysOpened === "1" ? "" : "s"}`
    : null;
  const attendancePct = attendanceLine && Number(draft.daysOpened) > 0
    ? Math.round((Number(draft.daysPresent) / Number(draft.daysOpened)) * 100)
    : null;

  return (
    <div className="space-y-4">
      {/* learner identity — the purple header band of the classic sheet */}
      <Card className="overflow-hidden">
        <div className="bg-gradient-to-r from-violet-600 to-purple-700 dark:from-violet-700 dark:to-purple-900 px-5 py-4 text-white">
          <div className="flex flex-wrap items-center gap-x-6 gap-y-2">
            <div>
              <p className="text-[11px] uppercase tracking-widest opacity-80">Terminal marksheet · {p.term.name} ({p.term.yearName})</p>
              <h3 className="text-lg font-semibold leading-tight">{studentName}</h3>
              <p className="text-xs opacity-90">
                {p.classroom?.name ?? "—"} · Index {p.student.nyansapoIndex || "—"} · {p.student.gender === "M" ? "Male" : p.student.gender === "F" ? "Female" : p.student.gender || "—"}
              </p>
            </div>
            <div className="ml-auto flex flex-wrap gap-2 text-[11px]">
              {p.card?.classPosition ? <Badge className="bg-white/15 text-white border-white/25">Class position {ordinal(p.card.classPosition)} of {p.card.classSize ?? "—"}</Badge> : null}
              {p.card?.aggregate !== null && p.card?.aggregate !== undefined ? <Badge className="bg-white/15 text-white border-white/25">BECE aggregate {fmt1(p.card.aggregate)}</Badge> : null}
              {p.card?.published ? <Badge className="bg-emerald-400/90 text-emerald-950 border-transparent">Published</Badge> : <Badge className="bg-white/15 text-white border-white/25">Draft</Badge>}
            </div>
          </div>
        </div>
        <CardContent className="pt-4">
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <StatCard icon={<Gauge className="size-4" />} label="Term average" value={fmt1(p.card?.average)} sub="mean of the 30/70 subject totals" tone="primary" />
            <StatCard icon={<BookOpen className="size-4" />} label="Total marks" value={fmt1(p.totals.total)} sub={`${p.totals.subjects} subjects · class 30% + exam 70%`} />
            <StatCard icon={<ClipboardCheck className="size-4" />} label="Attendance" value={attendancePct !== null ? `${attendancePct}%` : p.card?.attendancePct !== null && p.card?.attendancePct !== undefined ? `${fmt1(p.card.attendancePct)}%` : "—"} sub={attendanceLine ?? p.card?.attendance ?? "compiled from the register"} />
            <StatCard icon={<SquareCheck className="size-4" />} label="Conduct traits" value={draft.conductIds.length} sub={`${conducts.length} on the school's grid · cap 15`} tone="gold" />
          </div>
        </CardContent>
      </Card>

      {/* marks table — S/N | subject | class 30 | exam 70 | total | grade | remark | position */}
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base flex items-center gap-2">
            <ClipboardList className="size-4" /> Marks — {p.term.name}
            {p.classTeacher ? <span className="ml-2 text-xs font-normal text-muted-foreground">class teacher: {p.classTeacher}</span> : null}
          </CardTitle>
        </CardHeader>
        <CardContent className="px-0 sm:px-6">
          <div className="rounded-xl border overflow-x-auto nice-scroll">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-10 text-center">S/N</TableHead>
                  <TableHead>Subject</TableHead>
                  <TableHead className="text-center">Class score (30%)</TableHead>
                  <TableHead className="text-center">Exam score (70%)</TableHead>
                  <TableHead className="text-center">Total</TableHead>
                  <TableHead className="text-center">Grade</TableHead>
                  <TableHead className="hidden sm:table-cell">Remark</TableHead>
                  <TableHead className="text-center">Position</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {p.rows.map((r, i) => {
                  const tone = bandTone(r.total);
                  return (
                    <TableRow key={r.subjectId}>
                      <TableCell className="text-center text-xs text-muted-foreground">{i + 1}</TableCell>
                      <TableCell className="font-medium">
                        {r.subjectName}
                        {r.subjectCode ? <span className="block text-[10px] text-muted-foreground">{r.subjectCode}</span> : null}
                      </TableCell>
                      <TableCell className="text-center tabular-nums">{r.classScore ?? "—"}</TableCell>
                      <TableCell className="text-center tabular-nums">{r.examScore ?? "—"}</TableCell>
                      <TableCell className={cn("text-center tabular-nums font-semibold", tone.cell)}>{r.total ?? "—"}</TableCell>
                      <TableCell className="text-center">
                        {r.grade ? <span className={cn("inline-flex min-w-8 justify-center rounded-md border px-1.5 py-0.5 text-xs font-semibold", tone.badge)}>{r.grade}</span> : "—"}
                      </TableCell>
                      <TableCell className="hidden sm:table-cell text-xs text-muted-foreground">{r.remark ?? "—"}</TableCell>
                      <TableCell className="text-center text-xs">{r.position ? ordinal(r.position) : "—"}</TableCell>
                    </TableRow>
                  );
                })}
                {!p.rows.length && (
                  <TableRow>
                    <TableCell colSpan={8} className="py-8 text-center text-sm text-muted-foreground">
                      No subject marks on the ledger for this learner yet — compute this class&apos;s term results first.
                    </TableCell>
                  </TableRow>
                )}
                {p.rows.length > 0 && (
                  <TableRow className="bg-muted/50 font-semibold">
                    <TableCell colSpan={2} className="text-right uppercase text-xs tracking-wide">Total</TableCell>
                    <TableCell className="text-center tabular-nums">{fmt1(p.totals.classScore)}</TableCell>
                    <TableCell className="text-center tabular-nums">{fmt1(p.totals.examScore)}</TableCell>
                    <TableCell className="text-center tabular-nums">{fmt1(p.totals.total)}</TableCell>
                    <TableCell colSpan={3} />
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      {/* conduct · interest · remarks · attendance — the aggregation block */}
      <div className="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-base flex items-center gap-2">
              <SquareCheck className="size-4" /> Conduct &amp; interest
              <span className="ml-auto text-xs font-normal text-muted-foreground">{draft.conductIds.length}/15 selected</span>
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {conducts.length ? (
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2">
                {conducts.map((c) => (
                  <label key={c.id} className="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm cursor-pointer transition-colors hover:bg-accent/60 has-[button[data-state=checked]]:bg-accent">
                    <Checkbox
                      checked={draft.conductIds.includes(c.id)}
                      onCheckedChange={(v) => toggleConduct(c.id, v === true)}
                      aria-label={c.name}
                    />
                    <span className="select-none">{c.name}</span>
                  </label>
                ))}
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">No conduct traits configured — add them in Settings → Report library.</p>
            )}
            {draft.conductIds.length >= 15 && (
              <p className="text-xs text-amber-600 dark:text-amber-400">The classic sheet holds fifteen conduct slots — picks beyond fifteen are ignored.</p>
            )}
            <div>
              <p className="mb-1.5 text-sm font-medium">Interest</p>
              <Select value={draft.interestId || "none"} onValueChange={(v) => setDraft((d) => ({ ...d, interestId: v === "none" ? "" : v }))}>
                <SelectTrigger className="w-full" aria-label="Interest"><SelectValue placeholder="One interest" /></SelectTrigger>
                <SelectContent className="max-h-60 overflow-y-auto nice-scroll">
                  <SelectItem value="none">— none —</SelectItem>
                  {interests.map((it) => <SelectItem key={it.id} value={it.id}>{it.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-base flex items-center gap-2">
              <Sparkles className="size-4" /> Remarks &amp; attendance
              {p.card?.remarkSource ? <Badge variant="outline" className="text-[10px] font-normal ml-1">teacher: {p.card.remarkSource}</Badge> : null}
              {p.card?.headSource ? <Badge variant="outline" className="text-[10px] font-normal">head: {p.card.headSource}</Badge> : null}
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-2 gap-3">
              <div>
                <p className="mb-1.5 text-sm font-medium">Days school opened</p>
                <Input
                  type="number" min={0} inputMode="numeric"
                  value={draft.daysOpened}
                  onChange={(e) => setDraft((d) => ({ ...d, daysOpened: e.target.value.replace(/[^0-9]/g, "") }))}
                  placeholder={p.attendance.daysTaken ? String(p.attendance.daysTaken) : "e.g. 42"}
                  aria-label="Days school opened"
                />
              </div>
              <div>
                <p className="mb-1.5 text-sm font-medium">Days present</p>
                <Input
                  type="number" min={0} inputMode="numeric"
                  value={draft.daysPresent}
                  onChange={(e) => setDraft((d) => ({ ...d, daysPresent: e.target.value.replace(/[^0-9]/g, "") }))}
                  placeholder={p.attendance.daysAttended ? String(p.attendance.daysAttended) : "e.g. 38"}
                  aria-label="Days present"
                />
              </div>
            </div>
            {attendanceLine && (
              <p className="text-xs text-muted-foreground">
                Attendance line on the printed sheet: “{attendanceLine}”{attendancePct !== null ? ` (${attendancePct}%)` : ""}
                {p.attendance.daysTaken ? ` · register says ${p.attendance.daysAttended} of ${p.attendance.daysTaken} sessions` : ""}
              </p>
            )}
            <div>
              <p className="mb-1.5 text-sm font-medium">Class teacher&apos;s remarks</p>
              <Select value={draft.teacherCustom ? "custom" : draft.remarkTemplateId || "none"} onValueChange={pickTeacherTemplate}>
                <SelectTrigger className="w-full" aria-label="Class teacher remark"><SelectValue placeholder="Pick a remark template" /></SelectTrigger>
                <SelectContent className="max-h-60 overflow-y-auto nice-scroll">
                  <SelectItem value="none">— none —</SelectItem>
                  {[["TEACHER_POSITIVE", "Positive"], ["TEACHER_NEUTRAL", "Neutral"], ["TEACHER_NEGATIVE", "Needs work"]].map(([cat, label]) => {
                    const group = p.teacherTemplates.filter((t) => t.category === cat);
                    return group.length ? (
                      <Fragment key={cat}>
                        <p className="px-2 pt-1.5 pb-0.5 text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</p>
                        {group.map((t) => <SelectItem key={t.id} value={t.id}>{t.text}</SelectItem>)}
                      </Fragment>
                    ) : null;
                  })}
                  <SelectItem value="custom">Custom remark…</SelectItem>
                </SelectContent>
              </Select>
              {(draft.teacherCustom || !draft.remarkTemplateId) && (
                <Textarea
                  className="mt-2 min-h-16 text-sm"
                  value={draft.teacherRemark}
                  onChange={(e) => setDraft((d) => ({ ...d, teacherRemark: e.target.value.slice(0, 600) }))}
                  placeholder="Or type the class teacher's own words (600 characters)…"
                  aria-label="Class teacher custom remark"
                />
              )}
            </div>
            <div>
              <p className="mb-1.5 flex items-center gap-1.5 text-sm font-medium">
                Head teacher&apos;s remarks
                {p.suggestedHead && draft.headRemark === p.suggestedHead && (
                  <Badge variant="outline" className="text-[10px] font-normal">auto by {fmt1(p.card?.average)}%</Badge>
                )}
              </p>
              <Select value={draft.headCustom ? "custom" : draft.headTemplateId || "none"} onValueChange={pickHeadTemplate}>
                <SelectTrigger className="w-full" aria-label="Head teacher remark"><SelectValue placeholder="Pick a remark band" /></SelectTrigger>
                <SelectContent className="max-h-60 overflow-y-auto nice-scroll">
                  <SelectItem value="none">— none —</SelectItem>
                  {p.headBands.map((b) => (
                    <SelectItem key={b.id} value={b.id}>
                      {b.minPercent}–{b.maxPercent}% · {b.text}
                    </SelectItem>
                  ))}
                  <SelectItem value="custom">Custom remark…</SelectItem>
                </SelectContent>
              </Select>
              {(draft.headCustom || !draft.headTemplateId) && (
                <Textarea
                  className="mt-2 min-h-16 text-sm"
                  value={draft.headRemark}
                  onChange={(e) => setDraft((d) => ({ ...d, headRemark: e.target.value.slice(0, 600) }))}
                  placeholder="Or type the head teacher's own words (600 characters)…"
                  aria-label="Head teacher custom remark"
                />
              )}
              {draft.headRemark === p.suggestedHead && p.suggestedHead ? (
                <p className="mt-1 text-xs text-muted-foreground">Auto-selected from the school&apos;s percentage bands — override freely.</p>
              ) : null}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* save bar — conducts_update parity */}
      <div className="flex flex-wrap items-center gap-2 rounded-xl border bg-card p-3">
        {p.card ? (
          <>
            <Button
              className="gap-1.5"
              disabled={props.saving || !props.canPublish}
              onClick={() => props.onSave({
                conductIds: draft.conductIds,
                interestId: draft.interestId,
                teacherRemark: draft.teacherRemark.trim(),
                remarkTemplateId: draft.remarkTemplateId,
                headRemark: draft.headRemark.trim(),
                headTemplateId: draft.headTemplateId,
                daysOpened: draft.daysOpened,
                daysPresent: draft.daysPresent,
              })}
            >
              <Save className="size-4" /> {props.saving ? "Saving…" : "Save conducts & remarks"}
            </Button>
            <Button variant="outline" className="gap-1.5" onClick={props.onPrint}>
              <Printer className="size-4" /> Print marksheet
            </Button>
            {!props.canPublish && <span className="text-xs text-muted-foreground">Saving needs the generate &amp; publish permission.</span>}
            <span className="ml-auto text-[11px] text-muted-foreground">
              Saving stamps the learner&apos;s report card — the printed terminal report picks it up immediately.
            </span>
          </>
        ) : (
          <p className="text-sm text-muted-foreground">
            No compiled report card for this learner in {p.term.name} — enter marks in the Gradebook and compute the class&apos;s term results first
            (<strong className="font-medium text-foreground">no mark</strong>, as the classic sheet put it).
          </p>
        )}
      </div>
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

/* ══════════════════════════════════════════════════════════════
   Wave 23 — printable end-of-year promotion list: school header,
   per-learner verdicts & placements, signature block.
   ══════════════════════════════════════════════════════════════ */
function ProposalBadge({ proposal }: { proposal: string }) {
  const m: Record<string, { label: string; className: string }> = {
    PROMOTED: { label: "Promoted", className: "bg-emerald-600 text-white" },
    PROBATION: { label: "Probation", className: "bg-amber-500 text-white" },
    UNDECIDED: { label: "No card", className: "bg-secondary text-secondary-foreground" },
  };
  const v = m[proposal] ?? { label: proposal, className: "" };
  return <Badge className={cn("text-[10px] gap-1", v.className)}>{v.label}</Badge>;
}

function PromotionSheet(props: {
  school: SchoolInfo;
  termLabel: string;
  className: string;
  rows: { name: string; indexNo: string; average: number | null; aggregate: number | null; attendancePct: number | null; decision: string; destination: string }[];
}) {
  const p = props;
  const label = (d: string) => (d === "PROMOTED" ? "Promoted" : d === "PROBATION" ? "Probation" : d === "REPEAT" ? "Repeat" : "Undecided");
  const counts = p.rows.reduce<Record<string, number>>((acc, r) => { acc[r.decision] = (acc[r.decision] ?? 0) + 1; return acc; }, {});
  return (
    <div className="print-sheet bg-white text-neutral-900 font-serif text-[12px] leading-snug border border-neutral-300 shadow-sm rounded-md p-5 md:p-7 max-w-[820px] mx-auto">
      {/* school header — same doctrine as the report card */}
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

      <div className="text-center my-3">
        <div className="text-base md:text-lg font-bold tracking-[0.2em] uppercase">End-of-Year Promotion List</div>
        <div className="text-[12px] text-neutral-600">{p.termLabel} · {p.className}</div>
      </div>

      <table className="w-full border-collapse text-[11.5px]">
        <thead>
          <tr className="bg-neutral-100">
            <th className="border border-neutral-400 px-2 py-1 text-center w-8">#</th>
            <th className="border border-neutral-400 px-2 py-1 text-left">Learner</th>
            <th className="border border-neutral-400 px-2 py-1 text-center">Index No.</th>
            <th className="border border-neutral-400 px-2 py-1 text-center">Avg</th>
            <th className="border border-neutral-400 px-2 py-1 text-center">Agg</th>
            <th className="border border-neutral-400 px-2 py-1 text-center">Attend.</th>
            <th className="border border-neutral-400 px-2 py-1 text-center">Decision</th>
            <th className="border border-neutral-400 px-2 py-1 text-center">Placement</th>
          </tr>
        </thead>
        <tbody>
          {p.rows.map((r, i) => (
            <tr key={r.indexNo}>
              <td className="border border-neutral-400 px-2 py-0.5 text-center tabular-nums">{i + 1}</td>
              <td className="border border-neutral-400 px-2 py-0.5">{r.name}</td>
              <td className="border border-neutral-400 px-2 py-0.5 text-center tabular-nums">{r.indexNo}</td>
              <td className="border border-neutral-400 px-2 py-0.5 text-center tabular-nums font-semibold">{r.average !== null ? fmt1(r.average) : "—"}</td>
              <td className="border border-neutral-400 px-2 py-0.5 text-center tabular-nums">{r.aggregate ?? "—"}</td>
              <td className="border border-neutral-400 px-2 py-0.5 text-center tabular-nums">{r.attendancePct !== null ? `${r.attendancePct}%` : "—"}</td>
              <td className="border border-neutral-400 px-2 py-0.5 text-center font-semibold">{label(r.decision)}</td>
              <td className="border border-neutral-400 px-2 py-0.5 text-center">{r.destination}</td>
            </tr>
          ))}
          {!p.rows.length && (
            <tr><td colSpan={8} className="border border-neutral-400 px-2 py-3 text-center text-neutral-500">No learners on roll.</td></tr>
          )}
        </tbody>
      </table>

      <div className="flex flex-wrap gap-x-5 gap-y-1 mt-2 text-[11px] text-neutral-600">
        <span>Promoted: <b className="tabular-nums">{counts.PROMOTED ?? 0}</b></span>
        <span>Probation: <b className="tabular-nums">{counts.PROBATION ?? 0}</b></span>
        <span>Repeat: <b className="tabular-nums">{counts.REPEAT ?? 0}</b></span>
        <span>Undecided: <b className="tabular-nums">{counts.UNDECIDED ?? 0}</b></span>
      </div>

      <div className="grid grid-cols-2 gap-10 pt-8 text-[12px]">
        <div>
          <div className="border-t border-neutral-800 w-3/4" />
          <div className="mt-1 text-neutral-600">Class Teacher</div>
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
   Wave 33 — printable exam tabulation sheet: the exams officer's
   master sheet in landscape. Learner rows, one CA / Exam / Total
   column group per subject, sub-totals, grand total and position,
   then the pencilled-margin stats (high / low / mean / pass /
   distinctions) as footer rows under every subject group.
   ══════════════════════════════════════════════════════════════ */
function TabulationSheet({ payload, density = "summary" }: { payload: TabulationPayload; density?: "summary" | "raw" }) {
  const p = payload;
  const raw = density === "raw";
  /* summary prints portrait (one Tot column per subject); raw prints
     landscape (the CA / Exam / Tot trio per subject) — the matching
     @page rule is injected by the tab, never globally. */
  return (
    <div className="print-sheet bg-white text-neutral-900 font-serif text-[11px] leading-snug border border-neutral-300 shadow-sm rounded-md p-4 md:p-6">
      {/* school header — same doctrine as the report card */}
      <div className="flex items-center gap-4 border-b-2 border-neutral-800 pb-3">
        <div className="grid place-items-center size-12 shrink-0 rounded-full text-white font-bold text-base" style={{ background: p.school.logoColor || "#7c3aed" }} aria-hidden>
          {initials(p.school.name)}
        </div>
        <div className="min-w-0">
          <div className="text-lg md:text-xl font-bold tracking-wide uppercase">{p.school.name}</div>
          <div className="text-[10.5px] text-neutral-600">
            Examination tabulation sheet{raw ? " — raw scores" : ""} · {p.term.name} ({p.term.yearName}) · {p.classroom.name}
          </div>
        </div>
        <div className="ml-auto text-right text-[10px] text-neutral-500 hidden md:block">
          <div>EMIS code</div>
          <div className="font-semibold text-neutral-700">{p.school.code}</div>
        </div>
      </div>

      <div className="overflow-x-auto nice-scroll print:overflow-visible">
        <table className={cn("w-full border-collapse mt-3 text-[10.5px]", raw ? "min-w-[1100px]" : "min-w-[640px]")}>
          <thead>
            <tr>
              <th rowSpan={2} className="border border-neutral-400 bg-neutral-100 px-2 py-1 text-left align-bottom min-w-[120px]">Learner</th>
              {p.subjects.map((s) => (
                raw ? (
                  <th key={s.id} colSpan={3} className="border border-neutral-400 bg-neutral-100 px-1.5 py-1 text-center" title={s.name}>
                    {s.code || s.name.slice(0, 3).toUpperCase()}
                    <span className="block font-normal text-[8.5px] text-neutral-500">{s.name}</span>
                  </th>
                ) : (
                  <th key={s.id} className="border border-neutral-400 bg-neutral-100 px-1 py-1 text-center min-w-[44px]" title={s.name}>
                    {s.code || s.name.slice(0, 3).toUpperCase()}
                    <span className="block font-normal text-[8px] text-neutral-500">/100</span>
                  </th>
                )
              ))}
              {raw && <th colSpan={2} className="border border-neutral-400 bg-neutral-200 px-1.5 py-1 text-center">Sub-totals</th>}
              <th rowSpan={2} className="border border-neutral-400 bg-neutral-200 px-1.5 py-1 text-center align-bottom">Total</th>
              {raw && <th rowSpan={2} className="border border-neutral-400 bg-neutral-200 px-1.5 py-1 text-center align-bottom">Pos</th>}
            </tr>
            {raw && (
              <tr>
                {p.subjects.map((s) => (
                  <Fragment key={s.id}>
                    <th className="border border-neutral-400 bg-neutral-50 px-1 py-0.5 text-center text-[8.5px] font-semibold" title="Class assessment (30)">CA</th>
                    <th className="border border-neutral-400 bg-neutral-50 px-1 py-0.5 text-center text-[8.5px] font-semibold" title="End-of-term exam (70)">Exam</th>
                    <th className="border border-neutral-400 bg-neutral-50 px-1 py-0.5 text-center text-[8.5px] font-semibold">Tot</th>
                  </Fragment>
                ))}
                <th className="border border-neutral-400 bg-neutral-50 px-1 py-0.5 text-center text-[8.5px] font-semibold">CA /30</th>
                <th className="border border-neutral-400 bg-neutral-50 px-1 py-0.5 text-center text-[8.5px] font-semibold">Exam /70</th>
              </tr>
            )}
          </thead>
          <tbody>
            {p.rows.map((r, i) => (
              <tr key={r.studentId} className={i % 2 ? "bg-neutral-50/60" : ""}>
                <td className="border border-neutral-400 px-2 py-0.5 sticky left-0 bg-inherit">
                  <span className="font-medium">{r.name}</span>
                  {r.indexNo ? <span className="block text-[8.5px] text-neutral-500 tabular-nums">{r.indexNo}</span> : null}
                </td>
                {p.subjects.map((s) => {
                  const c = r.cells[s.id];
                  const tone = (c?.total ?? null) === null ? "text-neutral-400"
                    : (c.total as number) >= 65 ? "text-emerald-700 font-semibold"
                    : (c.total as number) >= 50 ? "text-amber-700 font-semibold"
                    : "text-red-700 font-semibold";
                  return raw ? (
                    <Fragment key={s.id}>
                      <td className="border border-neutral-400 px-1 py-0.5 text-center tabular-nums">{c?.classScore ?? "—"}</td>
                      <td className="border border-neutral-400 px-1 py-0.5 text-center tabular-nums">{c?.examScore ?? "—"}</td>
                      <td className={cn("border border-neutral-400 px-1 py-0.5 text-center tabular-nums", tone)}>{c?.total ?? "—"}</td>
                    </Fragment>
                  ) : (
                    <td key={s.id} className={cn("border border-neutral-400 px-1 py-0.5 text-center tabular-nums", tone)}>{c?.total ?? "—"}</td>
                  );
                })}
                {raw && (
                  <>
                    <td className="border border-neutral-400 px-1 py-0.5 text-center tabular-nums font-medium">{r.classTotal ?? "—"}</td>
                    <td className="border border-neutral-400 px-1 py-0.5 text-center tabular-nums font-medium">{r.examTotal ?? "—"}</td>
                  </>
                )}
                <td className="border border-neutral-400 px-1 py-0.5 text-center tabular-nums font-bold">{r.grandTotal ?? "—"}</td>
                {raw && <td className="border border-neutral-400 px-1 py-0.5 text-center tabular-nums font-bold">{r.position ? ordinal(r.position) : "—"}</td>}
              </tr>
            ))}
            {!p.rows.length && (
              <tr><td colSpan={p.subjects.length * (raw ? 3 : 1) + (raw ? 4 : 1)} className="border border-neutral-400 px-2 py-3 text-center text-neutral-500">No learners on roll.</td></tr>
            )}
          </tbody>
          <tfoot>
            {([
              ["Highest", "highest"], ["Lowest", "lowest"], ["Mean", "average"],
            ] as const).map(([label, key]) => (
              <tr key={key}>
                <td className="border border-neutral-400 bg-neutral-100 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide">{label}</td>
                {p.subjects.map((s) => {
                  const st = p.subjectStats.find((x) => x.subjectId === s.id);
                  return (
                    <Fragment key={s.id}>
                      {raw && <td colSpan={2} className="border border-neutral-400 bg-neutral-50 px-1 py-0.5 text-center text-[9px]"></td>}
                      <td className="border border-neutral-400 bg-neutral-50 px-1 py-0.5 text-center text-[9px] tabular-nums">{st ? fmt1(st[key]) : "—"}</td>
                    </Fragment>
                  );
                })}
                <td colSpan={raw ? 3 : 1} className="border border-neutral-400 bg-neutral-50"></td>
              </tr>
            ))}
            <tr>
              <td className="border border-neutral-400 bg-neutral-100 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide">Pass ≥50</td>
              {p.subjects.map((s) => {
                const st = p.subjectStats.find((x) => x.subjectId === s.id);
                return (
                  <Fragment key={s.id}>
                    {raw && <td colSpan={2} className="border border-neutral-400 bg-neutral-50 px-1 py-0.5 text-center text-[9px]"></td>}
                    <td className="border border-neutral-400 bg-neutral-50 px-1 py-0.5 text-center text-[9px] tabular-nums">{st?.passRate !== null && st?.passRate !== undefined ? `${st.passRate}%` : "—"}</td>
                  </Fragment>
                );
              })}
              <td colSpan={raw ? 3 : 1} className="border border-neutral-400 bg-neutral-50"></td>
            </tr>
            <tr>
              <td className="border border-neutral-400 bg-neutral-100 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide">B3+ (65↑)</td>
              {p.subjects.map((s) => {
                const st = p.subjectStats.find((x) => x.subjectId === s.id);
                return (
                  <Fragment key={s.id}>
                    {raw && <td colSpan={2} className="border border-neutral-400 bg-neutral-50 px-1 py-0.5 text-center text-[9px]"></td>}
                    <td className="border border-neutral-400 bg-neutral-50 px-1 py-0.5 text-center text-[9px] tabular-nums">{st?.distinctions ?? 0}</td>
                  </Fragment>
                );
              })}
              <td colSpan={raw ? 3 : 1} className="border border-neutral-400 bg-neutral-50"></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div className="grid grid-cols-2 gap-10 pt-6 text-[11px]">
        <div>
          <div className="border-t border-neutral-800 w-3/4" />
          <div className="mt-1 text-neutral-600">Exams Officer</div>
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

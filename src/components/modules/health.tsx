"use client";
// ── Health & Wellness Clinic (wave 26 — sick-bay register & SHEP screenings) ──
// The infirmary ledger: clinic visits with triage severity, temperature,
// treatment and disposition (returned to class / sent home / referred /
// follow-up), the OPEN → CLOSED workflow, guardian SMS notices, whole-school
// screening events with BMI auto-computed per learner, printable health
// profiles and wellness analytics.
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard, FormGrid, FormField } from "@/components/ui-helpers";
import { DateField } from "@/components/date-field";
import { fmtDateShort, initials, avatarColor } from "@/lib/format";
import { canAny } from "@/lib/rbac";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  HeartPulse, Plus, Search, Filter, BellRing, Printer, BarChart3, ChevronRight, ChevronLeft,
  Stethoscope, Thermometer, Home, Hospital, CalendarClock, CircleCheck, RotateCcw, Phone,
  Ruler, Eye, Ear, ClipboardPlus, Activity, Users, FileText, ClipboardList,
} from "lucide-react";
import {
  ResponsiveContainer, AreaChart, Area, BarChart, Bar, XAxis, YAxis, Tooltip,
} from "recharts";

const OPS = "/ops/health";

type VisitRow = {
  id: string; studentId: string; studentName: string; nyansapoIndex: string; classroomName: string | null;
  date: string; category: string; severity: string; complaint: string; temperature: number | null;
  treatment: string | null; medication: string | null; disposition: string; status: string;
  outcomeNote: string | null; closedAt: string | null; recordedByName: string | null;
  closedByName: string | null; parentNotifiedAt: string | null;
};

type BoardData = {
  classrooms: { id: string; name: string }[];
  stat: { visits: number; minor: number; moderate: number; major: number; open: number; sentHome: number; referred: number; notified: number };
  recent: VisitRow[];
  openVisits: VisitRow[];
  screening: { events: number; latest: { title: string; screenDate: string; status: string; recorded: number } | null };
  categories: { complaints: string[] };
};

type RosterRow = { id: string; name: string; rollNo: number | null; nyansapoIndex: string };

type ProfileData = {
  student: {
    id: string; name: string; nyansapoIndex: string; gender: string; classroomId: string; classroomName: string | null;
    guardianName: string | null; guardianPhone: string | null; bloodGroup: string | null; allergies: string | null;
    medicalConditions: string | null; emergencyContact: string | null; dietNote: string | null;
  };
  visits: VisitRow[];
  screenings: { id: string; screeningId: string; title: string; screenDate: string; heightCm: number | null; weightKg: number | null; bmi: number | null; vision: string | null; hearing: string | null; finding: string | null; referred: boolean; note: string | null }[];
  summary: { visits: number; open: number; sentHome: number; referred: number; major: number; lastVisitAt: string | null; screeningRefs: number };
};

type AnalyticsData = {
  window: { since: string };
  totals: { visits: number; sentHome: number; referred: number; notified: number; topComplaint: string | null };
  complaints: { name: string; count: number }[];
  trend: { month: string; visits: number; sentHome: number; referred: number }[];
  frequent: { id: string; name: string; classroomName: string | null; visits: number; lastAt: string | null }[];
  classDistribution: { name: string; visits: number; learners: number; per100: number }[];
  disposition: Record<string, number>;
  severity: Record<string, number>;
  latestScreening: { title: string; screenDate: string; status: string; recorded: number; coverage: number } | null;
};

type ScreeningEvent = {
  id: string; title: string; screenDate: string; status: string; notes: string | null;
  createdByName: string | null; recorded: number; coverage: number; referred: number;
};

type ScreeningDetail = {
  event: { id: string; title: string; screenDate: string; status: string; notes: string | null; createdByName: string | null };
  rows: { id: string; studentId: string; studentName: string; nyansapoIndex: string; classroomName: string | null; heightCm: number | null; weightKg: number | null; bmi: number | null; vision: string | null; hearing: string | null; finding: string | null; referred: boolean; note: string | null }[];
  pending: { id: string; name: string; rollNo: number | null; nyansapoIndex: string; classroomName: string | null }[];
};

const SEVERITY_UI: Record<string, { label: string; cls: string }> = {
  MINOR: { label: "Minor", cls: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300" },
  MODERATE: { label: "Moderate", cls: "bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300" },
  MAJOR: { label: "Major", cls: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300" },
};
const DISPOSITION_UI: Record<string, { label: string; cls: string; icon: any }> = {
  RETURNED_CLASS: { label: "Returned to class", cls: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300", icon: Stethoscope },
  SENT_HOME: { label: "Sent home", cls: "bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300", icon: Home },
  REFERRED: { label: "Referred", cls: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300", icon: Hospital },
  FOLLOWUP: { label: "Follow-up", cls: "bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300", icon: CalendarClock },
};
const TREATMENTS = [
  "Rested under observation; water given",
  "Wound cleaned with antiseptic and dressed",
  "Cold compress applied",
  "ORS sips; monitored 30 minutes",
  "R.I.C.E applied; strapped with crepe bandage",
  "Inhaler administered under supervision",
  "Glucose water; laid flat until revived",
  "Saline rinse; dental referral note issued",
  "Guardian referral note issued",
];
const MEDICATIONS = [
  "Paracetamol 500mg × 1",
  "Paracetamol 250mg × 1",
  "ORS sachet",
  "Antiseptic + plaster",
  "Crepe bandage + cold pack",
  "Glucose water",
  "None given",
];

function VisitBadges({ row }: { row: VisitRow }) {
  const sev = SEVERITY_UI[row.severity] ?? SEVERITY_UI.MINOR;
  const disp = DISPOSITION_UI[row.disposition] ?? DISPOSITION_UI.RETURNED_CLASS;
  const DispIcon = disp.icon;
  return (
    <span className="inline-flex items-center gap-1.5 flex-wrap">
      <Badge className="border-none bg-secondary text-secondary-foreground">{row.category}</Badge>
      <Badge className={cn("border-none", sev.cls)}>{sev.label}</Badge>
      <Badge className={cn("border-none", disp.cls)}><DispIcon className="size-3 mr-1" />{disp.label}</Badge>
      {row.status === "OPEN" && (
        <Badge className="border-none bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"><CalendarClock className="size-3 mr-1" />Follow-up pending</Badge>
      )}
      {row.parentNotifiedAt && (
        <Badge className="border-none bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300"><Phone className="size-3 mr-1" />Guardian informed</Badge>
      )}
    </span>
  );
}

export function Health() {
  const { currentSchoolId, session, navTarget } = useStore();
  const canManage = canAny(session?.permissions, ["health.manage"]);
  const [tab, setTab] = useState("sickbay");
  const [board, setBoard] = useState<BoardData | null>(null);
  const [loadErr, setLoadErr] = useState<string | null>(null);

  /* ── sick-bay filters + list ── */
  const [fSeverity, setFSeverity] = useState("all");
  const [fDisposition, setFDisposition] = useState("all");
  const [fStatus, setFStatus] = useState("all");
  const [fClass, setFClass] = useState("all");
  const [fFrom, setFFrom] = useState<string | null>(null);
  const [fTo, setFToF] = useState<string | null>(null);
  const [fQ, setFQ] = useState("");
  const [rows, setRows] = useState<VisitRow[] | null>(null);
  const [total, setTotal] = useState(0);

  /* ── new visit dialog ── */
  const [visitOpen, setVisitOpen] = useState(false);
  const [vClass, setVClass] = useState<string>("");
  const [roster, setRoster] = useState<RosterRow[]>([]);
  const [vStudent, setVStudent] = useState<string>("");
  const [vCategory, setVCategory] = useState<string>("");
  const [vSeverity, setVSeverity] = useState<string>("MINOR");
  const [vComplaint, setVComplaint] = useState("");
  const [vTemp, setVTemp] = useState("");
  const [vTreatment, setVTreatment] = useState<string>("");
  const [vMedication, setVMedication] = useState<string>("");
  const [vDisposition, setVDisposition] = useState<string>("RETURNED_CLASS");
  const [vDate, setVDate] = useState<string | null>(null);

  /* ── close / notify dialogs ── */
  const [closeRow, setCloseRow] = useState<VisitRow | null>(null);
  const [outcomeNote, setOutcomeNote] = useState("");
  const [notifyRow, setNotifyRow] = useState<VisitRow | null>(null);
  const [notifyMsg, setNotifyMsg] = useState("");

  /* ── profiles ── */
  const [pClass, setPClass] = useState<string>("");
  const [pRoster, setPRoster] = useState<RosterRow[]>([]);
  const [pStudent, setPStudent] = useState<string>("");
  const [profile, setProfile] = useState<ProfileData | null>(null);

  /* ── screenings ── */
  const [screenEvents, setScreenEvents] = useState<ScreeningEvent[] | null>(null);
  const [screenLearners, setScreenLearners] = useState(0);
  const [openScreenId, setOpenScreenId] = useState<string | null>(null);
  const [screenDetail, setScreenDetail] = useState<ScreeningDetail | null>(null);
  const [createScrOpen, setCreateScrOpen] = useState(false);
  const [scrTitle, setScrTitle] = useState("");
  const [scrDate, setScrDate] = useState<string | null>(null);
  const [scrNotes, setScrNotes] = useState("");
  const [measureRow, setMeasureRow] = useState<ScreeningDetail["rows"][number] | null>(null);
  const [measurePending, setMeasurePending] = useState<ScreeningDetail["pending"][number] | null>(null);
  const [mHeight, setMHeight] = useState("");
  const [mWeight, setMWeight] = useState("");
  const [mVision, setMVision] = useState<string>("PASS");
  const [mHearing, setMHearing] = useState<string>("PASS");
  const [mFinding, setMFinding] = useState("");
  const [mReferred, setMReferred] = useState(false);

  /* ── analytics ── */
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);

  const base = currentSchoolId ? `/api/school/${currentSchoolId}${OPS}` : "";
  const classrooms = board?.classrooms ?? [];
  const complaints = board?.categories.complaints ?? [];

  /* deep-link tab (sidebar children / metro / Start search) */
  useEffect(() => {
    if (navTarget?.tab && ["sickbay", "screenings", "profiles", "analytics"].includes(navTarget.tab)) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setTab(navTarget.tab);
    }
  }, [navTarget]);

  /* board load */
  const loadBoard = () => {
    if (!base) return;
    apiGet<BoardData>(base).then(setBoard).catch((e: Error) => setLoadErr(e.message));
  };
  useEffect(loadBoard, [base]);

  /* sick-bay list load */
  const loadList = () => {
    if (!base) return;
    const sp = new URLSearchParams({ action: "list", take: "80" });
    if (fSeverity !== "all") sp.set("severity", fSeverity);
    if (fDisposition !== "all") sp.set("disposition", fDisposition);
    if (fStatus !== "all") sp.set("status", fStatus);
    if (fClass !== "all") sp.set("classroomId", fClass);
    if (fFrom) sp.set("from", fFrom);
    if (fTo) sp.set("to", fTo);
    if (fQ.trim()) sp.set("q", fQ.trim());
    setRows(null);
    apiGet<{ rows: VisitRow[]; total: number }>(`${base}?${sp}`)
      .then((r) => { setRows(r.rows); setTotal(r.total); })
      .catch((e: Error) => { setRows([]); toast({ title: "Could not load the sick-bay register", description: e.message, variant: "destructive" }); });
  };
  useEffect(loadList, [base, fSeverity, fDisposition, fStatus, fClass, fFrom, fTo]); // eslint-disable-line react-hooks/set-state-in-effect

  /* roster for the visit dialog */
  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (!base || !vClass) { setRoster([]); return; }
    apiGet<{ rows: RosterRow[] }>(`${base}?action=roster&classroomId=${vClass}`)
      .then((r) => setRoster(r.rows))
      .catch(() => setRoster([]));
  }, [base, vClass]);

  /* profile roster */
  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (!base || !pClass) { setPRoster([]); return; }
    apiGet<{ rows: RosterRow[] }>(`${base}?action=roster&classroomId=${pClass}`)
      .then((r) => setPRoster(r.rows))
      .catch(() => setPRoster([]));
  }, [base, pClass]);

  /* profile load */
  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (!base || !pStudent) { setProfile(null); return; }
    setProfile(null);
    apiGet<ProfileData>(`${base}?action=profile&studentId=${pStudent}`)
      .then(setProfile)
      .catch((e: Error) => toast({ title: "Could not load the health profile", description: e.message, variant: "destructive" }));
  }, [base, pStudent]);

  /* screenings list load */
  const loadScreenings = () => {
    if (!base) return;
    apiGet<{ events: ScreeningEvent[]; learners: number }>(`${base}?action=screenings`)
      .then((r) => { setScreenEvents(r.events); setScreenLearners(r.learners); })
      .catch((e: Error) => toast({ title: "Could not load screenings", description: e.message, variant: "destructive" }));
  };
  useEffect(() => {
    if (base && tab === "screenings" && screenEvents === null) loadScreenings();
  }, [base, tab]);

  /* screening detail load */
  const loadScreenDetail = (id: string) => {
    if (!base) return;
    setScreenDetail(null);
    apiGet<ScreeningDetail>(`${base}?action=screening&screeningId=${id}`)
      .then(setScreenDetail)
      .catch((e: Error) => toast({ title: "Could not load the screening", description: e.message, variant: "destructive" }));
  };
  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (base && openScreenId) loadScreenDetail(openScreenId);
  }, [base, openScreenId]);

  /* analytics load */
  useEffect(() => {
    if (base && tab === "analytics" && !analytics) {
      apiGet<AnalyticsData>(`${base}?action=analytics`)
        .then(setAnalytics)
        .catch((e: Error) => toast({ title: "Could not load wellness analytics", description: e.message, variant: "destructive" }));
    }
  }, [base, tab, analytics]);

  /* ── create visit ── */
  const submitVisit = async () => {
    if (!vStudent) return toast({ title: "Pick the learner first" });
    if (!vCategory.trim()) return toast({ title: "Complaint category is required" });
    if (!vComplaint.trim()) return toast({ title: "Describe the complaint" });
    try {
      const r = await apiPost<{ row: VisitRow }>(base, {
        action: "create",
        studentId: vStudent,
        date: vDate ?? undefined,
        category: vCategory.trim(),
        severity: vSeverity,
        complaint: vComplaint.trim(),
        temperature: vTemp.trim() || undefined,
        treatment: vTreatment || undefined,
        medication: vMedication || undefined,
        disposition: vDisposition,
      });
      toast({ title: "Clinic visit recorded", description: `${r.row.studentName} — ${r.row.category}` });
      setVisitOpen(false);
      setVStudent(""); setVCategory(""); setVComplaint(""); setVTemp(""); setVTreatment(""); setVMedication(""); setVSeverity("MINOR"); setVDisposition("RETURNED_CLASS"); setVDate(null);
      loadBoard(); loadList();
    } catch (e: any) {
      toast({ title: "Could not record the visit", description: e.message, variant: "destructive" });
    }
  };

  /* ── close / reopen visit ── */
  const submitClose = async () => {
    if (!closeRow) return;
    try {
      await apiPost(base, { action: "update", id: closeRow.id, status: closeRow.status === "OPEN" ? "CLOSED" : "OPEN", outcomeNote: closeRow.status === "OPEN" ? (outcomeNote.trim() || undefined) : undefined });
      toast({ title: closeRow.status === "OPEN" ? "Follow-up closed" : "Visit reopened", description: closeRow.studentName });
      setCloseRow(null); setOutcomeNote("");
      loadBoard(); loadList();
      if (pStudent) setProfile(null); // reload profile on next effect tick
    } catch (e: any) {
      toast({ title: "Could not update the visit", description: e.message, variant: "destructive" });
    }
  };

  /* ── guardian SMS notice ── */
  const submitNotify = async () => {
    if (!notifyRow) return;
    try {
      const r = await apiPost<{ sms: { status: string; provider: string } }>(base, {
        action: "notify", id: notifyRow.id, message: notifyMsg.trim() || undefined,
      });
      toast({ title: `Guardian notified via SMS (${r.sms.provider})`, description: notifyRow.studentName });
      setNotifyRow(null); setNotifyMsg("");
      loadBoard(); loadList();
    } catch (e: any) {
      toast({ title: "SMS not sent", description: e.message, variant: "destructive" });
    }
  };

  /* ── create screening event ── */
  const submitScreening = async () => {
    if (!scrTitle.trim()) return toast({ title: "Screening title is required" });
    try {
      const r = await apiPost<{ event: { id: string } }>(base, {
        action: "screening-create", title: scrTitle.trim(), screenDate: scrDate ?? undefined, notes: scrNotes.trim() || undefined,
      });
      toast({ title: "Screening event created", description: scrTitle });
      setCreateScrOpen(false); setScrTitle(""); setScrDate(null); setScrNotes("");
      loadScreenings();
      setOpenScreenId(r.event.id);
    } catch (e: any) {
      toast({ title: "Could not create the screening", description: e.message, variant: "destructive" });
    }
  };

  /* ── record a measurement ── */
  const openMeasure = (target: ScreeningDetail["rows"][number] | ScreeningDetail["pending"][number], isPending: boolean) => {
    if (isPending) { setMeasurePending(target as ScreeningDetail["pending"][number]); setMeasureRow(null); }
    else {
      const r = target as ScreeningDetail["rows"][number];
      setMeasureRow(r); setMeasurePending(null);
      setMHeight(r.heightCm != null ? `${r.heightCm}` : "");
      setMWeight(r.weightKg != null ? `${r.weightKg}` : "");
      setMVision(r.vision ?? "PASS");
      setMHearing(r.hearing ?? "PASS");
      setMFinding(r.finding ?? "");
      setMReferred(r.referred);
      return;
    }
    setMHeight(""); setMWeight(""); setMVision("PASS"); setMHearing("PASS"); setMFinding(""); setMReferred(false);
  };
  const submitMeasure = async () => {
    if (!openScreenId) return;
    const target = measureRow ?? measurePending;
    if (!target) return;
    try {
      await apiPost(base, {
        action: "screening-record",
        screeningId: openScreenId,
        studentId: target.id,
        heightCm: mHeight.trim() || undefined,
        weightKg: mWeight.trim() || undefined,
        vision: mVision || undefined,
        hearing: mHearing || undefined,
        finding: mFinding.trim() || undefined,
        referred: mReferred,
      });
      toast({ title: "Measurements saved", description: `${"name" in target ? target.name : target.studentName}${mHeight && mWeight ? " — BMI computed on save" : ""}` });
      setMeasureRow(null); setMeasurePending(null);
      setMHeight(""); setMWeight(""); setMVision("PASS"); setMHearing("PASS"); setMFinding(""); setMReferred(false);
      loadScreenDetail(openScreenId); loadScreenings();
    } catch (e: any) {
      toast({ title: "Could not save the measurements", description: e.message, variant: "destructive" });
    }
  };

  /* ── complete / reopen screening ── */
  const flipScreening = async (id: string, closing: boolean) => {
    try {
      await apiPost(base, { action: closing ? "screening-complete" : "screening-reopen", screeningId: id });
      toast({ title: closing ? "Screening completed" : "Screening reopened" });
      loadScreenings();
      if (openScreenId === id) loadScreenDetail(id);
    } catch (e: any) {
      toast({ title: "Could not update the screening", description: e.message, variant: "destructive" });
    }
  };

  /* live BMI preview in the measure dialog */
  const bmiPreview = useMemo(() => {
    const h = Number(mHeight);
    const w = Number(mWeight);
    if (!mHeight || !mWeight || !Number.isFinite(h) || !Number.isFinite(w) || h <= 0) return null;
    return Math.round((w / Math.pow(h / 100, 2)) * 10) / 10;
  }, [mHeight, mWeight]);

  if (loadErr) {
    return (
      <Card><CardContent>
        <EmptyState icon={<HeartPulse className="size-6" />} title="Sick bay unavailable" hint={loadErr} />
      </CardContent></Card>
    );
  }

  return (
    <div className="space-y-4">
      <ModuleHeader
        title="Health & Wellness Clinic"
        description="The sick-bay register, SHEP screenings, learner health profiles and wellness analytics."
        actions={
          <div className="flex flex-wrap gap-2">
            {canManage && (
              <Button size="sm" className="gap-1.5" onClick={() => setVisitOpen(true)}>
                <Plus className="size-4" /> Record clinic visit
              </Button>
            )}
          </div>
        }
      />

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList>
          <TabsTrigger value="sickbay" className="gap-1.5"><Stethoscope className="size-4" />Sick Bay</TabsTrigger>
          <TabsTrigger value="screenings" className="gap-1.5"><Ruler className="size-4" />Screenings</TabsTrigger>
          <TabsTrigger value="profiles" className="gap-1.5"><FileText className="size-4" />Health Profiles</TabsTrigger>
          <TabsTrigger value="analytics" className="gap-1.5"><BarChart3 className="size-4" />Analytics</TabsTrigger>
        </TabsList>

        {/* ── SICK BAY REGISTER ── */}
        <TabsContent value="sickbay" className="space-y-4">
          {!board ? <LoadingBlock label="Loading the sick bay…" /> : (
            <>
              {/* stat row */}
              <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <StatCard icon={<Stethoscope className="size-4" />} label="Visits · 6 months" value={board.stat.visits} sub={`${board.stat.minor} minor · ${board.stat.moderate} moderate · ${board.stat.major} major`} />
                <StatCard icon={<CalendarClock className="size-4" />} label="Follow-ups open" value={board.stat.open} tone={board.stat.open > 0 ? "gold" : "default"} sub="pending closure" />
                <StatCard icon={<Home className="size-4" />} label="Sent home" value={board.stat.sentHome} sub="guardian pickup" />
                <StatCard icon={<Hospital className="size-4" />} label="Referrals" value={board.stat.referred} tone={board.stat.referred > 0 ? "danger" : "default"} sub="to clinic / hospital" />
                <StatCard icon={<Phone className="size-4" />} label="Guardians notified" value={board.stat.notified} sub="via SMS centre" />
                <StatCard icon={<Ruler className="size-4" />} label="Screenings" value={board.screening.events} sub={board.screening.latest ? `latest: ${fmtDateShort(board.screening.latest.screenDate)}` : "none yet"} />
              </div>

              {/* open follow-ups needing the nurse */}
              {board.openVisits.length > 0 && (
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><CalendarClock className="size-4 text-amber-600" />Follow-ups on the bench ({board.openVisits.length})</CardTitle></CardHeader>
                  <CardContent className="space-y-1.5">
                    {board.openVisits.slice(0, 4).map((v) => (
                      <div key={v.id} className="flex flex-wrap items-center justify-between gap-2 text-sm rounded-md border p-2">
                        <button className="flex items-center gap-2 text-left hover:underline min-w-0"
                          onClick={() => { setTab("profiles"); setPStudent(v.studentId); }}>
                          <Avatar className="size-6"><AvatarFallback className={cn("text-[10px] text-white", avatarColor(v.studentName))}>{initials(v.studentName)}</AvatarFallback></Avatar>
                          <span className="truncate"><span className="font-medium">{v.studentName}</span> <span className="text-muted-foreground">· {v.classroomName ?? "—"} · {v.category}</span></span>
                        </button>
                        <span className="flex items-center gap-2">
                          <Badge className={cn("border-none", (DISPOSITION_UI[v.disposition] ?? DISPOSITION_UI.FOLLOWUP).cls)}>{(DISPOSITION_UI[v.disposition] ?? DISPOSITION_UI.FOLLOWUP).label}</Badge>
                          {canManage && (
                            <Button size="sm" variant="outline" className="gap-1"
                              onClick={() => { setCloseRow(v); setOutcomeNote(""); }}>
                              <CircleCheck className="size-3.5" /> Close
                            </Button>
                          )}
                        </span>
                      </div>
                    ))}
                    {board.openVisits.length > 4 && <p className="text-xs text-muted-foreground">+ {board.openVisits.length - 4} more in the register below — filter by status “Open”.</p>}
                  </CardContent>
                </Card>
              )}

              {/* filters */}
              <Card>
                <CardContent className="p-3 sm:p-4 flex flex-wrap items-end gap-2">
                  <div className="w-full sm:w-40">
                    <Label className="text-xs">Severity</Label>
                    <Select value={fSeverity} onValueChange={setFSeverity}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All severities</SelectItem>
                        <SelectItem value="MINOR">Minor</SelectItem>
                        <SelectItem value="MODERATE">Moderate</SelectItem>
                        <SelectItem value="MAJOR">Major</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="w-full sm:w-44">
                    <Label className="text-xs">Disposition</Label>
                    <Select value={fDisposition} onValueChange={setFDisposition}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All dispositions</SelectItem>
                        <SelectItem value="RETURNED_CLASS">Returned to class</SelectItem>
                        <SelectItem value="SENT_HOME">Sent home</SelectItem>
                        <SelectItem value="REFERRED">Referred</SelectItem>
                        <SelectItem value="FOLLOWUP">Follow-up</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="w-full sm:w-32">
                    <Label className="text-xs">Status</Label>
                    <Select value={fStatus} onValueChange={setFStatus}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All</SelectItem>
                        <SelectItem value="OPEN">Open</SelectItem>
                        <SelectItem value="CLOSED">Closed</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="w-full sm:w-40">
                    <Label className="text-xs">Class</Label>
                    <Select value={fClass} onValueChange={setFClass}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All classes</SelectItem>
                        {classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="w-full sm:w-36">
                    <Label className="text-xs">From</Label>
                    <DateField value={fFrom} onChange={setFFrom} />
                  </div>
                  <div className="w-full sm:w-36">
                    <Label className="text-xs">To</Label>
                    <DateField value={fTo} onChange={setFToF} />
                  </div>
                  <div className="flex-1 min-w-[180px]">
                    <Label className="text-xs">Search learner</Label>
                    <div className="relative">
                      <Search className="size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
                      <Input className="pl-8" placeholder="Name or index…" value={fQ}
                        onChange={(e) => setFQ(e.target.value)}
                        onKeyDown={(e) => e.key === "Enter" && loadList()} />
                    </div>
                  </div>
                  <Button variant="outline" className="gap-1.5" onClick={loadList}>
                    <Filter className="size-4" /> Apply{total ? ` · ${total}` : ""}
                  </Button>
                </CardContent>
              </Card>

              {rows === null ? <LoadingBlock label="Loading the register…" /> :
                !rows.length ? (
                  <Card><CardContent>
                    <EmptyState icon={<Stethoscope className="size-6" />} title="No visits match these filters"
                      hint="Record the first clinic visit or clear the filters — every sick-bay visit from any period appears here." />
                  </CardContent></Card>
                ) : (
                  <>
                    {/* desktop table */}
                    <Card className="hidden md:block">
                      <CardContent className="p-0">
                        <table className="w-full text-sm">
                          <thead>
                            <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                              <th className="py-2.5 px-3">Date</th>
                              <th className="py-2.5 px-3">Learner</th>
                              <th className="py-2.5 px-3">Visit</th>
                              <th className="py-2.5 px-3">Triage & treatment</th>
                              <th className="py-2.5 px-3 text-right">Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            {rows.map((r) => (
                              <tr key={r.id} className="border-b last:border-0 align-top hover:bg-muted/40">
                                <td className="py-2.5 px-3 whitespace-nowrap">{fmtDateShort(r.date)}</td>
                                <td className="py-2.5 px-3">
                                  <button className="flex items-center gap-2 text-left hover:underline"
                                    onClick={() => { setTab("profiles"); setPStudent(r.studentId); }}>
                                    <Avatar className="size-7"><AvatarFallback className={cn("text-[10px] text-white", avatarColor(r.studentName))}>{initials(r.studentName)}</AvatarFallback></Avatar>
                                    <span>
                                      <span className="font-medium block leading-tight">{r.studentName}</span>
                                      <span className="text-xs text-muted-foreground">{r.classroomName ?? "—"} · {r.nyansapoIndex}</span>
                                    </span>
                                  </button>
                                </td>
                                <td className="py-2.5 px-3"><VisitBadges row={r} /></td>
                                <td className="py-2.5 px-3 max-w-sm">
                                  <p className="leading-snug">{r.complaint}</p>
                                  {r.temperature != null && (
                                    <p className="text-xs text-muted-foreground mt-0.5 inline-flex items-center gap-1">
                                      <Thermometer className={cn("size-3", r.temperature >= 37.5 ? "text-red-600" : "text-muted-foreground")} />
                                      {r.temperature.toFixed(1)} °C{r.temperature >= 37.5 ? " · fever range" : ""}
                                    </p>
                                  )}
                                  {r.treatment && <p className="text-xs text-muted-foreground mt-0.5">Treatment: {r.treatment}</p>}
                                  {r.medication && <p className="text-xs text-muted-foreground mt-0.5">Medication: {r.medication}</p>}
                                  {r.outcomeNote && <p className="text-xs text-muted-foreground mt-0.5">Outcome: {r.outcomeNote}</p>}
                                  <p className="text-xs text-muted-foreground mt-0.5">
                                    {r.recordedByName ? `Seen by ${r.recordedByName}` : ""}
                                    {r.closedByName ? ` · Closed by ${r.closedByName}` : ""}
                                  </p>
                                </td>
                                <td className="py-2.5 px-3 text-right whitespace-nowrap">
                                  {canManage && (
                                    <span className="inline-flex gap-1.5">
                                      <Button size="sm" variant="outline" className="gap-1"
                                        onClick={() => { setCloseRow(r); setOutcomeNote(r.outcomeNote ?? ""); }}>
                                        {r.status === "OPEN" ? <CircleCheck className="size-3.5" /> : <RotateCcw className="size-3.5" />}
                                        {r.status === "OPEN" ? "Close" : "Reopen"}
                                      </Button>
                                      {!r.parentNotifiedAt && (
                                        <Button size="sm" variant="outline" className="gap-1"
                                          onClick={() => { setNotifyRow(r); setNotifyMsg(""); }}>
                                          <BellRing className="size-3.5" /> Notify
                                        </Button>
                                      )}
                                    </span>
                                  )}
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </CardContent>
                    </Card>

                    {/* mobile cards */}
                    <div className="md:hidden space-y-2 max-h-[70vh] overflow-y-auto">
                      {rows.map((r) => (
                        <Card key={r.id}><CardContent className="p-3">
                          <div className="flex items-start justify-between gap-2">
                            <button className="flex items-center gap-2 text-left"
                              onClick={() => { setTab("profiles"); setPStudent(r.studentId); }}>
                              <Avatar className="size-8"><AvatarFallback className={cn("text-[10px] text-white", avatarColor(r.studentName))}>{initials(r.studentName)}</AvatarFallback></Avatar>
                              <span>
                                <span className="font-medium block leading-tight">{r.studentName}</span>
                                <span className="text-xs text-muted-foreground">{r.classroomName ?? "—"} · {fmtDateShort(r.date)}</span>
                              </span>
                            </button>
                            <ChevronRight className="size-4 text-muted-foreground mt-1" />
                          </div>
                          <div className="mt-2"><VisitBadges row={r} /></div>
                          <p className="text-sm mt-2 leading-snug">{r.complaint}</p>
                          {r.temperature != null && <p className="text-xs text-muted-foreground mt-1">Temp: {r.temperature.toFixed(1)} °C</p>}
                          {r.treatment && <p className="text-xs text-muted-foreground mt-0.5">Treatment: {r.treatment}</p>}
                          {r.medication && <p className="text-xs text-muted-foreground mt-0.5">Medication: {r.medication}</p>}
                          {canManage && (
                            <div className="flex gap-2 mt-2.5">
                              <Button size="sm" variant="outline" className="gap-1 flex-1"
                                onClick={() => { setCloseRow(r); setOutcomeNote(r.outcomeNote ?? ""); }}>
                                <CircleCheck className="size-3.5" /> {r.status === "OPEN" ? "Close" : "Reopen"}
                              </Button>
                              {!r.parentNotifiedAt && (
                                <Button size="sm" variant="outline" className="gap-1 flex-1"
                                  onClick={() => { setNotifyRow(r); setNotifyMsg(""); }}>
                                  <BellRing className="size-3.5" /> Notify
                                </Button>
                              )}
                            </div>
                          )}
                        </CardContent></Card>
                      ))}
                    </div>
                  </>
                )}
            </>
          )}
        </TabsContent>

        {/* ── SCREENINGS ── */}
        <TabsContent value="screenings" className="space-y-4">
          <div className="flex items-center justify-between gap-2 flex-wrap no-print">
            <p className="text-sm text-muted-foreground">
              SHEP screening events — height, weight, BMI, vision and hearing checks for the whole school.
            </p>
            {canManage && (
              <Button size="sm" className="gap-1.5" onClick={() => setCreateScrOpen(true)}>
                <Plus className="size-4" /> New screening
              </Button>
            )}
          </div>

          {!openScreenId ? (
            screenEvents === null ? <LoadingBlock label="Loading screenings…" /> :
            !screenEvents.length ? (
              <Card><CardContent>
                <EmptyState icon={<Ruler className="size-6" />} title="No screening events yet"
                  hint="Create the first whole-school screening — each learner gets a height/weight/BMI, vision and hearing record." />
              </CardContent></Card>
            ) : (
              <div className="grid gap-3 md:grid-cols-2">
                {screenEvents.map((e) => (
                  <Card key={e.id} className="hover:shadow-sm transition-shadow cursor-pointer"
                    onClick={() => setOpenScreenId(e.id)}>
                    <CardContent className="p-4">
                      <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0">
                          <CardTitle className="text-sm leading-snug">{e.title}</CardTitle>
                          <p className="text-xs text-muted-foreground mt-0.5">{fmtDateShort(e.screenDate)}{e.createdByName ? ` · by ${e.createdByName}` : ""}</p>
                        </div>
                        <Badge className={cn("border-none", e.status === "COMPLETED"
                          ? "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                          : "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300")}>
                          {e.status === "COMPLETED" ? "Completed" : "Recording"}
                        </Badge>
                      </div>
                      <div className="grid grid-cols-3 gap-2 mt-3 text-center">
                        <div className="rounded-md bg-muted/60 p-2">
                          <p className="text-lg font-semibold leading-none">{e.recorded}</p>
                          <p className="text-[10px] uppercase tracking-wide text-muted-foreground mt-1">recorded</p>
                        </div>
                        <div className="rounded-md bg-muted/60 p-2">
                          <p className="text-lg font-semibold leading-none">{e.coverage}%</p>
                          <p className="text-[10px] uppercase tracking-wide text-muted-foreground mt-1">of {screenLearners} learners</p>
                        </div>
                        <div className="rounded-md bg-muted/60 p-2">
                          <p className="text-lg font-semibold leading-none text-red-600">{e.referred}</p>
                          <p className="text-[10px] uppercase tracking-wide text-muted-foreground mt-1">referred</p>
                        </div>
                      </div>
                      {e.notes && <p className="text-xs text-muted-foreground mt-2 line-clamp-2">{e.notes}</p>}
                      <p className="text-xs text-primary mt-2 inline-flex items-center gap-1">Open screening <ChevronRight className="size-3" /></p>
                    </CardContent>
                  </Card>
                ))}
              </div>
            )
          ) : (
            /* one screening detail */
            !screenDetail ? <LoadingBlock label="Loading the screening…" /> : (
              <div className="space-y-4">
                <Card>
                  <CardContent className="p-4 flex flex-wrap items-start justify-between gap-3">
                    <div className="flex items-start gap-3 min-w-0">
                      <Button size="sm" variant="ghost" className="gap-1 px-2" onClick={() => { setOpenScreenId(null); setScreenDetail(null); }}>
                        <ChevronLeft className="size-4" /> All screenings
                      </Button>
                      <div className="min-w-0">
                        <CardTitle className="text-base leading-snug">{screenDetail.event.title}</CardTitle>
                        <p className="text-xs text-muted-foreground mt-0.5">
                          {fmtDateShort(screenDetail.event.screenDate)} · {screenDetail.rows.length} recorded · {screenDetail.pending.length} pending
                          {screenDetail.event.createdByName ? ` · by ${screenDetail.event.createdByName}` : ""}
                        </p>
                        {screenDetail.event.notes && <p className="text-xs text-muted-foreground mt-1 max-w-2xl">{screenDetail.event.notes}</p>}
                      </div>
                    </div>
                    {canManage && (
                      <div className="flex gap-2">
                        <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()}>
                          <Printer className="size-4" /> Print
                        </Button>
                        {screenDetail.event.status === "OPEN" ? (
                          <Button size="sm" className="gap-1.5" onClick={() => flipScreening(screenDetail.event.id, true)}>
                            <CircleCheck className="size-4" /> Complete
                          </Button>
                        ) : (
                          <Button size="sm" variant="outline" className="gap-1.5" onClick={() => flipScreening(screenDetail.event.id, false)}>
                            <RotateCcw className="size-4" /> Reopen
                          </Button>
                        )}
                      </div>
                    )}
                  </CardContent>
                </Card>

                {/* pending learners — quick record */}
                {canManage && screenDetail.pending.length > 0 && (
                  <Card>
                    <CardHeader className="pb-2">
                      <CardTitle className="text-sm flex items-center gap-2">
                        <ClipboardPlus className="size-4 text-primary" />Awaiting measurement ({screenDetail.pending.length})
                      </CardTitle>
                    </CardHeader>
                    <CardContent>
                      <div className="flex flex-wrap gap-1.5 max-h-40 overflow-y-auto">
                        {screenDetail.pending.map((p) => (
                          <Button key={p.id} size="sm" variant="outline" className="gap-1 h-7"
                            onClick={() => openMeasure(p, true)}>
                            <Plus className="size-3" />{p.name}{p.classroomName ? ` · ${p.classroomName}` : ""}
                          </Button>
                        ))}
                      </div>
                    </CardContent>
                  </Card>
                )}

                {/* records table */}
                {!screenDetail.rows.length ? (
                  <Card><CardContent>
                    <EmptyState icon={<Ruler className="size-6" />} title="No measurements yet"
                      hint="Use the buttons above to record each learner's height, weight, vision and hearing." />
                  </CardContent></Card>
                ) : (
                  <Card>
                    <CardContent className="p-0">
                      <div className="md:hidden overflow-x-auto">
                        <table className="w-full text-sm">
                          <thead>
                            <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                              <th className="py-2.5 px-3">Learner</th>
                              <th className="py-2.5 px-3">H (cm)</th>
                              <th className="py-2.5 px-3">W (kg)</th>
                              <th className="py-2.5 px-3">BMI</th>
                              <th className="py-2.5 px-3">Vision</th>
                              <th className="py-2.5 px-3">Hearing</th>
                              <th className="py-2.5 px-3"></th>
                            </tr>
                          </thead>
                          <tbody>
                            {screenDetail.rows.map((r) => (
                              <tr key={r.id} className="border-b last:border-0 align-middle hover:bg-muted/40">
                                <td className="py-2 px-3">
                                  <button className="text-left hover:underline"
                                    onClick={() => { setTab("profiles"); setPStudent(r.studentId); }}>
                                    <span className="font-medium block leading-tight">{r.studentName}</span>
                                    <span className="text-xs text-muted-foreground">{r.classroomName ?? "—"}</span>
                                  </button>
                                </td>
                                <td className="py-2 px-3">{r.heightCm ?? "—"}</td>
                                <td className="py-2 px-3">{r.weightKg ?? "—"}</td>
                                <td className="py-2 px-3">{r.bmi != null ? r.bmi.toFixed(1) : "—"}</td>
                                <td className="py-2 px-3">{r.vision === "PASS" ? "✓" : r.vision === "REFER" ? "Refer" : (r.vision ?? "—")}</td>
                                <td className="py-2 px-3">{r.hearing === "PASS" ? "✓" : r.hearing === "REFER" ? "Refer" : (r.hearing ?? "—")}</td>
                                <td className="py-2 px-3 text-right">
                                  {r.referred && <Badge className="border-none bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300 mr-1.5">Referred</Badge>}
                                  {canManage && (
                                    <Button size="sm" variant="ghost" className="h-7 px-2" onClick={() => openMeasure(r, false)}>Edit</Button>
                                  )}
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                      {/* stacked mobile cards */}
                      <div className="hidden md:block overflow-x-auto">
                        <table className="w-full text-sm">
                          <thead>
                            <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                              <th className="py-2.5 px-3">Learner</th>
                              <th className="py-2.5 px-3">Height</th>
                              <th className="py-2.5 px-3">Weight</th>
                              <th className="py-2.5 px-3">BMI</th>
                              <th className="py-2.5 px-3">Vision</th>
                              <th className="py-2.5 px-3">Hearing</th>
                              <th className="py-2.5 px-3">Finding</th>
                              <th className="py-2.5 px-3 text-right"></th>
                            </tr>
                          </thead>
                          <tbody>
                            {screenDetail.rows.map((r) => (
                              <tr key={r.id} className="border-b last:border-0 align-middle hover:bg-muted/40">
                                <td className="py-2.5 px-3">
                                  <button className="text-left hover:underline"
                                    onClick={() => { setTab("profiles"); setPStudent(r.studentId); }}>
                                    <span className="font-medium block leading-tight">{r.studentName}</span>
                                    <span className="text-xs text-muted-foreground">{r.classroomName ?? "—"} · {r.nyansapoIndex}</span>
                                  </button>
                                </td>
                                <td className="py-2.5 px-3 whitespace-nowrap">{r.heightCm != null ? `${r.heightCm} cm` : "—"}</td>
                                <td className="py-2.5 px-3 whitespace-nowrap">{r.weightKg != null ? `${r.weightKg} kg` : "—"}</td>
                                <td className="py-2.5 px-3 whitespace-nowrap">{r.bmi != null ? r.bmi.toFixed(1) : "—"}</td>
                                <td className="py-2.5 px-3">{r.vision === "PASS" ? <span className="text-emerald-600">Pass</span> : r.vision === "REFER" ? <span className="text-red-600">Refer</span> : (r.vision ?? "—")}</td>
                                <td className="py-2.5 px-3">{r.hearing === "PASS" ? <span className="text-emerald-600">Pass</span> : r.hearing === "REFER" ? <span className="text-red-600">Refer</span> : (r.hearing ?? "—")}</td>
                                <td className="py-2.5 px-3 max-w-xs">
                                  {r.referred && <Badge className="border-none bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300 mr-1.5">Referred</Badge>}
                                  {r.finding && <span className="text-xs text-muted-foreground">{r.finding}</span>}
                                </td>
                                <td className="py-2.5 px-3 text-right">
                                  {canManage && (
                                    <Button size="sm" variant="ghost" className="h-7 px-2" onClick={() => openMeasure(r, false)}>Edit</Button>
                                  )}
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    </CardContent>
                  </Card>
                )}
              </div>
            )
          )}
        </TabsContent>

        {/* ── HEALTH PROFILES ── */}
        <TabsContent value="profiles" className="space-y-4">
          <Card className="no-print">
            <CardContent className="p-3 sm:p-4 flex flex-wrap items-end gap-2">
              <div className="w-full sm:w-48">
                <Label className="text-xs">Class</Label>
                <Select value={pClass} onValueChange={(v) => { setPClass(v); setPStudent(""); }}>
                  <SelectTrigger><SelectValue placeholder="Choose class" /></SelectTrigger>
                  <SelectContent>
                    {classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
              <div className="w-full sm:w-56">
                <Label className="text-xs">Learner</Label>
                <Select value={pStudent} onValueChange={setPStudent} disabled={!pClass}>
                  <SelectTrigger><SelectValue placeholder={pClass ? "Choose learner" : "Pick a class first"} /></SelectTrigger>
                  <SelectContent>
                    {pRoster.map((s) => (
                      <SelectItem key={s.id} value={s.id}>
                        {s.rollNo != null ? `#${s.rollNo} · ` : ""}{s.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </CardContent>
          </Card>

          {!pStudent ? (
            <Card><CardContent>
              <EmptyState icon={<FileText className="size-6" />} title="Pick a learner"
                hint="Choose a class, then a learner — the medical background, sick-bay timeline, screening history and printable health report appear here." />
            </CardContent></Card>
          ) : !profile ? <LoadingBlock label="Loading health profile…" /> : (
            <>
              {/* head card + background */}
              <Card>
                <CardContent className="p-4">
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3 min-w-0">
                      <Avatar className="size-12"><AvatarFallback className={cn("text-sm text-white", avatarColor(profile.student.name))}>{initials(profile.student.name)}</AvatarFallback></Avatar>
                      <div className="min-w-0">
                        <CardTitle className="text-base leading-snug">{profile.student.name}</CardTitle>
                        <p className="text-xs text-muted-foreground">
                          {profile.student.nyansapoIndex} · {profile.student.classroomName ?? "—"} · {profile.student.gender === "M" ? "Male" : profile.student.gender === "F" ? "Female" : profile.student.gender}
                        </p>
                      </div>
                    </div>
                    <div className="flex flex-wrap gap-2 no-print">
                      {canManage && (
                        <Button size="sm" className="gap-1.5" onClick={() => setVisitOpen(true)}>
                          <Plus className="size-4" /> Record visit
                        </Button>
                      )}
                      <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()}>
                        <Printer className="size-4" /> Print health report
                      </Button>
                    </div>
                  </div>

                  {/* medical background */}
                  <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-2 mt-4">
                    {[
                      { label: "Blood group", value: profile.student.bloodGroup, icon: Activity },
                      { label: "Allergies", value: profile.student.allergies, icon: HeartPulse },
                      { label: "Medical conditions", value: profile.student.medicalConditions, icon: Stethoscope },
                      { label: "Emergency contact", value: profile.student.emergencyContact ?? profile.student.guardianPhone, icon: Phone },
                      { label: "Guardian", value: [profile.student.guardianName, profile.student.guardianPhone].filter(Boolean).join(" · "), icon: Users },
                      { label: "Diet note", value: profile.student.dietNote, icon: ClipboardList },
                    ].map((f) => (
                      <div key={f.label} className={cn("rounded-md border p-2.5", f.value ? "bg-card" : "bg-muted/30")}>
                        <p className="text-[10px] uppercase tracking-wide text-muted-foreground flex items-center gap-1"><f.icon className="size-3" />{f.label}</p>
                        <p className={cn("text-sm mt-0.5 leading-snug", !f.value && "text-muted-foreground italic")}>{f.value ?? "Not on file"}</p>
                      </div>
                    ))}
                  </div>

                  {/* summary strip */}
                  <div className="grid grid-cols-3 sm:grid-cols-6 gap-2 mt-3 text-center">
                    {[
                      { label: "Visits", value: profile.summary.visits },
                      { label: "Open", value: profile.summary.open },
                      { label: "Sent home", value: profile.summary.sentHome },
                      { label: "Referrals", value: profile.summary.referred },
                      { label: "Major", value: profile.summary.major },
                      { label: "Screening refs", value: profile.summary.screeningRefs },
                    ].map((s) => (
                      <div key={s.label} className="rounded-md bg-muted/60 p-2">
                        <p className="text-lg font-semibold leading-none">{s.value}</p>
                        <p className="text-[10px] uppercase tracking-wide text-muted-foreground mt-1">{s.label}</p>
                      </div>
                    ))}
                  </div>
                  {profile.summary.lastVisitAt && (
                    <p className="text-xs text-muted-foreground mt-2">Last sick-bay visit: {fmtDateShort(profile.summary.lastVisitAt)}</p>
                  )}
                </CardContent>
              </Card>

              {/* screening history */}
              {profile.screenings.length > 0 && (
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><Ruler className="size-4 text-primary" />Screening history</CardTitle></CardHeader>
                  <CardContent className="p-0">
                    <div className="overflow-x-auto">
                      <table className="w-full text-sm">
                        <thead>
                          <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <th className="py-2.5 px-3">Screening</th>
                            <th className="py-2.5 px-3">Date</th>
                            <th className="py-2.5 px-3">Height</th>
                            <th className="py-2.5 px-3">Weight</th>
                            <th className="py-2.5 px-3">BMI</th>
                            <th className="py-2.5 px-3">Vision</th>
                            <th className="py-2.5 px-3">Hearing</th>
                            <th className="py-2.5 px-3">Finding</th>
                          </tr>
                        </thead>
                        <tbody>
                          {profile.screenings.map((s) => (
                            <tr key={s.id} className="border-b last:border-0 align-middle hover:bg-muted/40">
                              <td className="py-2.5 px-3">{s.title}</td>
                              <td className="py-2.5 px-3 whitespace-nowrap">{fmtDateShort(s.screenDate)}</td>
                              <td className="py-2.5 px-3">{s.heightCm != null ? `${s.heightCm} cm` : "—"}</td>
                              <td className="py-2.5 px-3">{s.weightKg != null ? `${s.weightKg} kg` : "—"}</td>
                              <td className="py-2.5 px-3">{s.bmi != null ? s.bmi.toFixed(1) : "—"}</td>
                              <td className="py-2.5 px-3">{s.vision === "PASS" ? <span className="text-emerald-600">Pass</span> : s.vision === "REFER" ? <span className="text-red-600">Refer</span> : (s.vision ?? "—")}</td>
                              <td className="py-2.5 px-3">{s.hearing === "PASS" ? <span className="text-emerald-600">Pass</span> : s.hearing === "REFER" ? <span className="text-red-600">Refer</span> : (s.hearing ?? "—")}</td>
                              <td className="py-2.5 px-3 max-w-xs">
                                {s.referred && <Badge className="border-none bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300 mr-1.5">Referred</Badge>}
                                {s.finding && <span className="text-xs text-muted-foreground">{s.finding}</span>}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </CardContent>
                </Card>
              )}

              {/* sick-bay timeline */}
              <Card>
                <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><Stethoscope className="size-4 text-primary" />Sick-bay timeline ({profile.visits.length})</CardTitle></CardHeader>
                <CardContent className="space-y-2">
                  {!profile.visits.length && (
                    <EmptyState icon={<CircleCheck className="size-6" />} title="No sick-bay visits"
                      hint="This learner has a clean sick-bay record." />
                  )}
                  {profile.visits.map((v) => (
                    <div key={v.id} className="rounded-md border p-3">
                      <div className="flex flex-wrap items-center justify-between gap-2">
                        <p className="text-sm font-medium">{fmtDateShort(v.date)} · {v.category}</p>
                        <VisitBadges row={v} />
                      </div>
                      <p className="text-sm mt-1.5 leading-snug">{v.complaint}</p>
                      {v.temperature != null && (
                        <p className="text-xs text-muted-foreground mt-1 inline-flex items-center gap-1">
                          <Thermometer className={cn("size-3", v.temperature >= 37.5 ? "text-red-600" : "text-muted-foreground")} />
                          Temp {v.temperature.toFixed(1)} °C{v.temperature >= 37.5 ? " · fever range" : ""}
                        </p>
                      )}
                      {v.treatment && <p className="text-xs text-muted-foreground mt-1">Treatment: {v.treatment}</p>}
                      {v.medication && <p className="text-xs text-muted-foreground">Medication: {v.medication}</p>}
                      {v.outcomeNote && <p className="text-xs text-muted-foreground">Outcome: {v.outcomeNote}</p>}
                      <p className="text-xs text-muted-foreground mt-1">
                        {v.recordedByName ? `Seen by ${v.recordedByName}` : ""}
                        {v.closedByName ? ` · Closed by ${v.closedByName}` : ""}
                        {v.status === "OPEN" ? " · follow-up pending" : ""}
                      </p>
                    </div>
                  ))}
                </CardContent>
              </Card>
            </>
          )}
        </TabsContent>

        {/* ── ANALYTICS ── */}
        <TabsContent value="analytics" className="space-y-4">
          {!analytics ? <LoadingBlock label="Loading wellness analytics…" /> : (
            <>
              <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                <StatCard icon={<Stethoscope className="size-4" />} label="Visits · 6 months" value={analytics.totals.visits} sub={`since ${analytics.window.since}`} />
                <StatCard icon={<Home className="size-4" />} label="Sent home" value={analytics.totals.sentHome} sub="guardian pickups" />
                <StatCard icon={<Hospital className="size-4" />} label="Referrals" value={analytics.totals.referred} tone={analytics.totals.referred > 0 ? "danger" : "default"} sub="clinic / hospital" />
                <StatCard icon={<Phone className="size-4" />} label="Guardians notified" value={analytics.totals.notified} sub="SMS centre" />
                <StatCard icon={<Activity className="size-4" />} label="Top complaint" value={<span className="text-sm">{analytics.totals.topComplaint ?? "—"}</span>} sub="most frequent" />
              </div>

              {/* trend */}
              <Card>
                <CardHeader className="pb-2"><CardTitle className="text-sm">Sick-bay traffic — 6 months</CardTitle></CardHeader>
                <CardContent>
                  <div className="h-56">
                    <ResponsiveContainer width="100%" height="100%">
                      <AreaChart data={analytics.trend} margin={{ top: 4, right: 8, bottom: 0, left: -18 }}>
                        <defs>
                          <linearGradient id="ghVisits" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="5%" stopColor="#0d9488" stopOpacity={0.35} />
                            <stop offset="95%" stopColor="#0d9488" stopOpacity={0.02} />
                          </linearGradient>
                        </defs>
                        <XAxis dataKey="month" tick={{ fontSize: 11 }} />
                        <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                        <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8 }} />
                        <Area type="monotone" dataKey="visits" name="All visits" stroke="#0d9488" strokeWidth={2} fill="url(#ghVisits)" />
                        <Area type="monotone" dataKey="sentHome" name="Sent home" stroke="#ea580c" strokeWidth={1.5} fillOpacity={0} />
                        <Area type="monotone" dataKey="referred" name="Referred" stroke="#dc2626" strokeWidth={1.5} fillOpacity={0} />
                      </AreaChart>
                    </ResponsiveContainer>
                  </div>
                </CardContent>
              </Card>

              <div className="grid lg:grid-cols-2 gap-4">
                {/* top complaints */}
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm">Top complaints</CardTitle></CardHeader>
                  <CardContent>
                    {analytics.complaints.length ? (
                      <div className="space-y-2">
                        {analytics.complaints.map((c, i) => (
                          <div key={c.name} className="flex items-center gap-2">
                            <span className="text-xs text-muted-foreground w-4 shrink-0">{i + 1}</span>
                            <div className="flex-1 min-w-0">
                              <div className="flex items-baseline justify-between gap-2">
                                <p className="text-sm truncate">{c.name}</p>
                                <p className="text-xs text-muted-foreground shrink-0">{c.count}</p>
                              </div>
                              <div className="h-1.5 rounded-full bg-muted mt-1 overflow-hidden">
                                <div className="h-full rounded-full bg-teal-600" style={{ width: `${(c.count / analytics.complaints[0].count) * 100}%` }} />
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    ) : <EmptyState title="No visits in the window" />}
                  </CardContent>
                </Card>

                {/* disposition + severity mix */}
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm">Disposition & severity mix</CardTitle></CardHeader>
                  <CardContent className="space-y-3">
                    <div className="grid grid-cols-4 gap-2 text-center">
                      {Object.entries(analytics.disposition).map(([k, v]) => {
                        const d = DISPOSITION_UI[k] ?? DISPOSITION_UI.RETURNED_CLASS;
                        return (
                          <div key={k} className="rounded-md bg-muted/60 p-2">
                            <p className="text-lg font-semibold leading-none">{v}</p>
                            <p className="text-[10px] uppercase tracking-wide text-muted-foreground mt-1">{d.label}</p>
                          </div>
                        );
                      })}
                    </div>
                    <div className="grid grid-cols-3 gap-2 text-center">
                      {Object.entries(analytics.severity).map(([k, v]) => (
                        <div key={k} className="rounded-md border p-2">
                          <p className="text-lg font-semibold leading-none">{v}</p>
                          <p className="text-[10px] uppercase tracking-wide text-muted-foreground mt-1">{SEVERITY_UI[k]?.label ?? k}</p>
                        </div>
                      ))}
                    </div>
                    {analytics.latestScreening && (
                      <div className="rounded-md border p-3 text-sm">
                        <p className="flex items-center gap-1.5 font-medium"><Ruler className="size-4 text-primary" />Latest screening</p>
                        <p className="text-xs text-muted-foreground mt-1">
                          {analytics.latestScreening.title} · {fmtDateShort(analytics.latestScreening.screenDate)} · {analytics.latestScreening.status === "COMPLETED" ? "completed" : "recording"}
                        </p>
                        <p className="text-xs mt-1">{analytics.latestScreening.recorded} learners measured — {analytics.latestScreening.coverage}% coverage</p>
                      </div>
                    )}
                  </CardContent>
                </Card>
              </div>

              <div className="grid lg:grid-cols-2 gap-4">
                {/* frequent visitors */}
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm">Frequent sick-bay visitors (3+)</CardTitle></CardHeader>
                  <CardContent className="p-0">
                    {analytics.frequent.length ? (
                      <div className="max-h-72 overflow-y-auto">
                        <table className="w-full text-sm">
                          <tbody>
                            {analytics.frequent.map((f) => (
                              <tr key={f.id} className="border-b last:border-0 hover:bg-muted/40">
                                <td className="py-2 px-3">
                                  <button className="text-left hover:underline"
                                    onClick={() => { setTab("profiles"); setPStudent(f.id); }}>
                                    <span className="font-medium block leading-tight">{f.name}</span>
                                    <span className="text-xs text-muted-foreground">{f.classroomName ?? "—"} · last {f.lastAt ? fmtDateShort(f.lastAt) : "—"}</span>
                                  </button>
                                </td>
                                <td className="py-2 px-3 text-right">
                                  <Badge className="border-none bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300">{f.visits} visits</Badge>
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    ) : <div className="p-4"><EmptyState title="No frequent visitors" hint="No learner has 3+ visits in the window." /></div>}
                  </CardContent>
                </Card>

                {/* class distribution */}
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm">Sick-bay load by class</CardTitle></CardHeader>
                  <CardContent>
                    {analytics.classDistribution.length ? (
                      <div className="h-64">
                        <ResponsiveContainer width="100%" height="100%">
                          <BarChart data={analytics.classDistribution} margin={{ top: 4, right: 8, bottom: 0, left: -18 }}>
                            <XAxis dataKey="name" tick={{ fontSize: 10 }} interval={0} angle={-25} textAnchor="end" height={52} />
                            <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                            <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8 }} />
                            <Bar dataKey="visits" name="Visits" fill="#0d9488" radius={[4, 4, 0, 0]} />
                          </BarChart>
                        </ResponsiveContainer>
                      </div>
                    ) : <EmptyState title="No visits in the window" />}
                  </CardContent>
                </Card>
              </div>
            </>
          )}
        </TabsContent>
      </Tabs>

      {/* ── record clinic visit dialog ── */}
      <Dialog open={visitOpen} onOpenChange={setVisitOpen}>
        <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><Stethoscope className="size-5 text-primary" />Record clinic visit</DialogTitle>
            <DialogDescription>Sick-bay attendance with triage, treatment and disposition.</DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <FormGrid cols={2}>
              <FormField label="Class" required>
                <Select value={vClass} onValueChange={(x) => { setVClass(x); setVStudent(""); }}>
                  <SelectTrigger><SelectValue placeholder="Choose class" /></SelectTrigger>
                  <SelectContent>
                    {classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Learner" required>
                <Select value={vStudent} onValueChange={setVStudent} disabled={!vClass}>
                  <SelectTrigger><SelectValue placeholder={vClass ? "Choose learner" : "Pick a class first"} /></SelectTrigger>
                  <SelectContent>
                    {roster.map((s) => (
                      <SelectItem key={s.id} value={s.id}>{s.rollNo != null ? `#${s.rollNo} · ` : ""}{s.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Date" hint="defaults to today">
                <DateField value={vDate} onChange={setVDate} />
              </FormField>
              <FormField label="Temperature (°C)" hint="30–45 range">
                <Input inputMode="decimal" placeholder="36.8" value={vTemp} onChange={(e) => setVTemp(e.target.value)} />
              </FormField>
              <FormField label="Complaint category" required>
                <Input list="health-complaints" placeholder="Fever / malaria suspicion…" value={vCategory} onChange={(e) => setVCategory(e.target.value)} />
                <datalist id="health-complaints">
                  {complaints.map((c) => <option key={c} value={c} />)}
                </datalist>
              </FormField>
              <FormField label="Severity" required>
                <Select value={vSeverity} onValueChange={setVSeverity}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="MINOR">Minor</SelectItem>
                    <SelectItem value="MODERATE">Moderate</SelectItem>
                    <SelectItem value="MAJOR">Major</SelectItem>
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Treatment given" span={2}>
                <Input list="health-treatments" placeholder="What was done…" value={vTreatment} onChange={(e) => setVTreatment(e.target.value)} />
                <datalist id="health-treatments">
                  {TREATMENTS.map((t) => <option key={t} value={t} />)}
                </datalist>
              </FormField>
              <FormField label="Medication" span={2}>
                <Input list="health-meds" placeholder="Paracetamol 500mg × 1" value={vMedication} onChange={(e) => setVMedication(e.target.value)} />
                <datalist id="health-meds">
                  {MEDICATIONS.map((m) => <option key={m} value={m} />)}
                </datalist>
              </FormField>
              <FormField label="Disposition" required span={2} hint="Follow-ups & referrals stay open until closed with an outcome">
                <Select value={vDisposition} onValueChange={setVDisposition}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="RETURNED_CLASS">Returned to class</SelectItem>
                    <SelectItem value="SENT_HOME">Sent home</SelectItem>
                    <SelectItem value="REFERRED">Referred (clinic / hospital)</SelectItem>
                    <SelectItem value="FOLLOWUP">Placed on follow-up</SelectItem>
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Complaint / notes" required span={2}>
                <Textarea rows={2} placeholder="What the learner reported and what was observed…" value={vComplaint} onChange={(e) => setVComplaint(e.target.value)} />
              </FormField>
            </FormGrid>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setVisitOpen(false)}>Cancel</Button>
            <Button onClick={submitVisit}><Plus className="size-4 mr-1.5" />Record visit</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── close / reopen dialog ── */}
      <Dialog open={!!closeRow} onOpenChange={(o) => !o && setCloseRow(null)}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              {closeRow?.status === "OPEN" ? <CircleCheck className="size-5 text-emerald-600" /> : <RotateCcw className="size-5" />}
              {closeRow?.status === "OPEN" ? "Close the follow-up" : "Reopen this visit"}
            </DialogTitle>
            <DialogDescription>
              {closeRow?.studentName} · {closeRow?.category} · {closeRow ? fmtDateShort(closeRow.date) : ""}
            </DialogDescription>
          </DialogHeader>
          {closeRow?.status === "OPEN" ? (
            <div className="space-y-2">
              <Label className="text-xs">Outcome note</Label>
              <Textarea rows={3} placeholder="How the follow-up ended — recovery, guardian completed treatment, referral closed…"
                value={outcomeNote} onChange={(e) => setOutcomeNote(e.target.value)} />
            </div>
          ) : (
            <p className="text-sm text-muted-foreground">Reopening puts this visit back on the follow-up bench.</p>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setCloseRow(null)}>Cancel</Button>
            <Button onClick={submitClose}>{closeRow?.status === "OPEN" ? "Close follow-up" : "Reopen"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── guardian SMS notice dialog ── */}
      <Dialog open={!!notifyRow} onOpenChange={(o) => !o && setNotifyRow(null)}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><BellRing className="size-5 text-primary" />Notify the guardian</DialogTitle>
            <DialogDescription>
              SMS to {notifyRow?.studentName}'s guardian — sent through the SMS centre (1 credit).
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-2">
            <Label className="text-xs">Message</Label>
            <Textarea rows={4} placeholder="Leave blank to use the standard health notice…" value={notifyMsg} onChange={(e) => setNotifyMsg(e.target.value)} />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setNotifyRow(null)}>Cancel</Button>
            <Button onClick={submitNotify}><BellRing className="size-4 mr-1.5" />Send SMS</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── new screening dialog ── */}
      <Dialog open={createScrOpen} onOpenChange={setCreateScrOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><Ruler className="size-5 text-primary" />New screening event</DialogTitle>
            <DialogDescription>A whole-school height/weight/BMI, vision and hearing exercise.</DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <FormField label="Title" required>
              <Input placeholder="Term 2 Whole-School Health Screening" value={scrTitle} onChange={(e) => setScrTitle(e.target.value)} />
            </FormField>
            <FormField label="Date" hint="defaults to today">
              <DateField value={scrDate} onChange={setScrDate} />
            </FormField>
            <FormField label="Notes">
              <Textarea rows={2} placeholder="SHEP team, station arrangement, classes order…" value={scrNotes} onChange={(e) => setScrNotes(e.target.value)} />
            </FormField>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCreateScrOpen(false)}>Cancel</Button>
            <Button onClick={submitScreening}><Plus className="size-4 mr-1.5" />Create screening</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── measurement dialog ── */}
      <Dialog open={!!(measureRow || measurePending)} onOpenChange={(o) => { if (!o) { setMeasureRow(null); setMeasurePending(null); } }}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><Ruler className="size-5 text-primary" />Screening measurement</DialogTitle>
            <DialogDescription>
              {(measureRow?.studentName ?? measurePending?.name ?? "")}{(measureRow ?? measurePending)?.classroomName ? ` · ${(measureRow ?? measurePending)!.classroomName}` : ""}
              {measureRow ? " — editing the existing record" : ""}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <FormGrid cols={2}>
              <FormField label="Height (cm)" hint="50–220">
                <Input inputMode="decimal" placeholder="142" value={mHeight} onChange={(e) => setMHeight(e.target.value)} />
              </FormField>
              <FormField label="Weight (kg)" hint="10–150">
                <Input inputMode="decimal" placeholder="34.5" value={mWeight} onChange={(e) => setMWeight(e.target.value)} />
              </FormField>
              <FormField label="Vision">
                <Select value={mVision} onValueChange={setMVision}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="PASS">Pass</SelectItem>
                    <SelectItem value="REFER">Refer</SelectItem>
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Hearing">
                <Select value={mHearing} onValueChange={setMHearing}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="PASS">Pass</SelectItem>
                    <SelectItem value="REFER">Refer</SelectItem>
                  </SelectContent>
                </Select>
              </FormField>
            </FormGrid>
            {bmiPreview != null && (
              <div className="rounded-md bg-muted/60 p-2 text-sm flex items-center gap-2">
                <Activity className="size-4 text-primary" />
                BMI: <span className="font-semibold">{bmiPreview.toFixed(1)}</span>
                <span className="text-xs text-muted-foreground">(computed on save)</span>
              </div>
            )}
            <FormField label="Finding / observation">
              <Textarea rows={2} placeholder="Healthy; within expected growth band…" value={mFinding} onChange={(e) => setMFinding(e.target.value)} />
            </FormField>
            <div className="flex items-center justify-between rounded-md border p-3">
              <div>
                <p className="text-sm font-medium flex items-center gap-1.5"><Hospital className="size-4 text-red-600" />Refer for follow-up</p>
                <p className="text-xs text-muted-foreground">Issue a guardian referral note</p>
              </div>
              <Switch checked={mReferred} onCheckedChange={setMReferred} aria-label="Refer for follow-up" />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => { setMeasureRow(null); setMeasurePending(null); }}>Cancel</Button>
            <Button onClick={submitMeasure}><ClipboardPlus className="size-4 mr-1.5" />Save measurement</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

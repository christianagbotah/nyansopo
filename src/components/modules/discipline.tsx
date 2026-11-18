"use client";
// ── Discipline & Conduct (wave 25 — iEduPro discipline parity) ──
// The day-to-day conduct register: incidents with a severity ladder and an
// OPEN → RESOLVED / ESCALATED workflow, the commendations wall, per-learner
// conduct profiles (printable) and behaviour analytics. Guardian SMS notices
// ride the existing SMS centre (credits, gateway, message log).
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { DateField } from "@/components/date-field";
import { fmtDateShort, initials, avatarColor } from "@/lib/format";
import { canAny } from "@/lib/rbac";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  Gavel, Award, Plus, Search, Filter, BellRing, Printer, BarChart3, ChevronRight,
  ShieldAlert, CircleCheck, CircleAlert, OctagonAlert, Phone, FileText, RotateCcw, Trophy, Users,
} from "lucide-react";
import {
  ResponsiveContainer, BarChart, Bar, AreaChart, Area, XAxis, YAxis, Tooltip,
} from "recharts";

const OPS = "/ops/discipline";

type CaseRow = {
  id: string; studentId: string; studentName: string; nyansapoIndex: string; classroomName: string | null;
  kind: string; date: string; category: string; severity: string | null; description: string;
  actionTaken: string | null; status: string; resolutionNote: string | null; resolvedAt: string | null;
  recordedByName: string | null; resolvedByName: string | null; parentNotifiedAt: string | null;
};

type BoardData = {
  classrooms: { id: string; name: string }[];
  stat: { incidents: number; commendations: number; open: number; resolved: number; escalated: number; notified: number };
  recent: CaseRow[];
  openCases: CaseRow[];
  categories: { offences: string[]; commendations: string[] };
};

type RosterRow = { id: string; name: string; rollNo: number | null; nyansapoIndex: string };

type ProfileData = {
  student: { id: string; name: string; nyansapoIndex: string; gender: string; classroomId: string; classroomName: string | null; rollNo: number | null; guardianName: string; guardianPhone: string };
  timeline: CaseRow[];
  summary: { incidents: number; commendations: number; open: number; escalated: number; major: number; lastIncidentAt: string | null; notified: number };
};

type AnalyticsData = {
  window: { since: string };
  totals: { incidents: number; commendations: number; notified: number; topCategory: string | null };
  categories: { name: string; count: number }[];
  trend: { month: string; incidents: number; commendations: number }[];
  repeatOffenders: { id: string; name: string; classroomName: string | null; incidents: number; commendations: number }[];
  leaderboard: { id: string; name: string; classroomName: string | null; commendations: number }[];
  classRanking: { name: string; incidents: number; learners: number; perLearner: number }[];
  severity: Record<string, number>;
  statusSplit: Record<string, number>;
};

const SEVERITY_UI: Record<string, { label: string; cls: string }> = {
  MINOR: { label: "Minor", cls: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300" },
  MODERATE: { label: "Moderate", cls: "bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300" },
  MAJOR: { label: "Major", cls: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300" },
};
const STATUS_UI: Record<string, { label: string; cls: string; icon: any }> = {
  OPEN: { label: "Open", cls: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300", icon: CircleAlert },
  RESOLVED: { label: "Resolved", cls: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300", icon: CircleCheck },
  ESCALATED: { label: "Escalated", cls: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300", icon: OctagonAlert },
};
const ACTIONS_TAKEN = [
  "Verbal warning", "Written warning", "Counselled by head", "Parent called",
  "Sanctioned (supervised task)", "Referral to counsellor", "Restitution", "Suspension",
];

function CaseBadge({ row }: { row: CaseRow }) {
  if (row.kind === "COMMENDATION") {
    return <Badge className="bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300 border-none"><Award className="size-3 mr-1" />{row.category}</Badge>;
  }
  const sev = row.severity ? SEVERITY_UI[row.severity] : null;
  const st = STATUS_UI[row.status] ?? STATUS_UI.OPEN;
  return (
    <span className="inline-flex items-center gap-1.5 flex-wrap">
      <Badge className="border-none bg-secondary text-secondary-foreground">{row.category}</Badge>
      {sev && <Badge className={cn("border-none", sev.cls)}>{sev.label}</Badge>}
      <Badge className={cn("border-none", st.cls)}>{st.label}</Badge>
      {row.parentNotifiedAt && (
        <Badge className="border-none bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300"><Phone className="size-3 mr-1" />Guardian informed</Badge>
      )}
    </span>
  );
}

export function Discipline() {
  const { currentSchoolId, session, navTarget, online } = useStore();
  const canManage = canAny(session?.permissions, ["discipline.manage"]);
  const [tab, setTab] = useState("register");
  const [board, setBoard] = useState<BoardData | null>(null);
  const [loadErr, setLoadErr] = useState<string | null>(null);

  /* ── register filters + list ── */
  const [fKind, setFKind] = useState("all");
  const [fStatus, setFStatus] = useState("all");
  const [fClass, setFClass] = useState("all");
  const [fFrom, setFFrom] = useState<string | null>(null);
  const [fTo, setFToF] = useState<string | null>(null);
  const [fQ, setFQ] = useState("");
  const [rows, setRows] = useState<CaseRow[] | null>(null);
  const [total, setTotal] = useState(0);
  const [busy, setBusy] = useState(false);

  /* ── new case dialog ── */
  const [caseOpen, setCaseOpen] = useState(false);
  const [caseKind, setCaseKind] = useState<"INCIDENT" | "COMMENDATION">("INCIDENT");
  const [cClass, setCClass] = useState<string>("");
  const [roster, setRoster] = useState<RosterRow[]>([]);
  const [cStudent, setCStudent] = useState<string>("");
  const [cCategory, setCCategory] = useState<string>("");
  const [cSeverity, setCSeverity] = useState<string>("MINOR");
  const [cDesc, setCDesc] = useState("");
  const [cAction, setCAction] = useState<string>("");

  /* ── resolve / notify dialogs ── */
  const [resolveRow, setResolveRow] = useState<CaseRow | null>(null);
  const [resolveStatus, setResolveStatus] = useState("RESOLVED");
  const [resolveNote, setResolveNote] = useState("");
  const [notifyRow, setNotifyRow] = useState<CaseRow | null>(null);
  const [notifyMsg, setNotifyMsg] = useState("");

  /* ── profiles ── */
  const [pClass, setPClass] = useState<string>("");
  const [pRoster, setPRoster] = useState<RosterRow[]>([]);
  const [pStudent, setPStudent] = useState<string>("");
  const [profile, setProfile] = useState<ProfileData | null>(null);

  /* ── analytics ── */
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);

  const base = currentSchoolId ? `/api/school/${currentSchoolId}${OPS}` : "";

  /* deep-link tab (sidebar children / metro / Start search) */
  useEffect(() => {
    if (navTarget?.tab && ["register", "commendations", "profiles", "analytics"].includes(navTarget.tab)) {
      setTab(navTarget.tab);
    }
  }, [navTarget]);

  /* board load */
  const loadBoard = () => {
    if (!base) return;
    apiGet<BoardData>(base).then(setBoard).catch((e: Error) => setLoadErr(e.message));
  };
  useEffect(loadBoard, [base]);

  /* register list load */
  const loadList = () => {
    if (!base) return;
    const sp = new URLSearchParams({ action: "list", take: "80" });
    if (fKind !== "all") sp.set("kind", fKind);
    if (fStatus !== "all") sp.set("status", fStatus);
    if (fClass !== "all") sp.set("classroomId", fClass);
    if (fFrom) sp.set("from", fFrom);
    if (fTo) sp.set("to", fTo);
    if (fQ.trim()) sp.set("q", fQ.trim());
    setRows(null);
    apiGet<{ rows: CaseRow[]; total: number }>(`${base}?${sp}`)
      .then((r) => { setRows(r.rows); setTotal(r.total); })
      .catch((e: Error) => { setRows([]); toast({ title: "Could not load the register", description: e.message, variant: "destructive" }); });
  };
  useEffect(loadList, [base, fKind, fStatus, fClass, fFrom, fTo]);

  /* roster loads (new-case dialog + profile picker) */
  useEffect(() => {
    if (!base || !cClass) { setRoster([]); return; }
    apiGet<{ rows: RosterRow[] }>(`${base}?action=roster&classroomId=${cClass}`)
      .then((r) => setRoster(r.rows))
      .catch(() => setRoster([]));
  }, [base, cClass]);
  useEffect(() => {
    if (!base || !pClass) { setPRoster([]); return; }
    apiGet<{ rows: RosterRow[] }>(`${base}?action=roster&classroomId=${pClass}`)
      .then((r) => setPRoster(r.rows))
      .catch(() => setPRoster([]));
  }, [base, pClass]);

  /* profile load */
  useEffect(() => {
    if (!base || !pStudent) { setProfile(null); return; }
    setProfile(null);
    apiGet<ProfileData>(`${base}?action=profile&studentId=${pStudent}`)
      .then(setProfile)
      .catch((e: Error) => toast({ title: "Could not load profile", description: e.message, variant: "destructive" }));
  }, [base, pStudent]);

  /* analytics load (tab-lazy) */
  useEffect(() => {
    if (tab !== "analytics" || !base || analytics) return;
    apiGet<AnalyticsData>(`${base}?action=analytics`).then(setAnalytics)
      .catch((e: Error) => toast({ title: "Could not load analytics", description: e.message, variant: "destructive" }));
  }, [tab, base, analytics]);

  /* ── actions ── */
  async function createCase() {
    if (!base || !cStudent || !cCategory.trim() || !cDesc.trim()) return;
    setBusy(true);
    try {
      await apiPost(base, {
        action: "create", studentId: cStudent, kind: caseKind,
        category: cCategory.trim(), severity: caseKind === "INCIDENT" ? cSeverity : undefined,
        description: cDesc.trim(), actionTaken: cAction || undefined,
      });
      toast({
        title: caseKind === "INCIDENT" ? "Incident recorded" : "Commendation recorded",
        description: `${cCategory} — added to the conduct register.`,
      });
      setCaseOpen(false);
      setCCategory(""); setCDesc(""); setCAction(""); setCStudent("");
      loadBoard(); loadList(); setAnalytics(null);
      if (pStudent === cStudent) setProfile(null); // refetch via effect
      setPStudent(pStudent); // trigger reload when same learner reselected
    } catch (e: any) {
      toast({ title: "Could not record", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  async function saveResolve() {
    if (!base || !resolveRow) return;
    setBusy(true);
    try {
      await apiPost(base, { action: "update", id: resolveRow.id, status: resolveStatus, resolutionNote: resolveNote || undefined });
      toast({
        title: resolveStatus === "RESOLVED" ? "Case resolved" : resolveStatus === "ESCALATED" ? "Case escalated to the head" : "Case reopened",
        description: resolveStatus === "RESOLVED" ? resolveNote || "Resolution recorded." : undefined,
      });
      setResolveRow(null); setResolveNote("");
      loadBoard(); loadList(); setAnalytics(null);
      if (pStudent === resolveRow.studentId) { setProfile(null); setPStudent(pStudent); }
    } catch (e: any) {
      toast({ title: "Could not update", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  async function sendNotify() {
    if (!base || !notifyRow) return;
    setBusy(true);
    try {
      const r = await apiPost<{ sms: { status: string; provider: string } }>(base, {
        action: "notify", id: notifyRow.id, message: notifyMsg || undefined,
      });
      toast({
        title: "Guardian notified",
        description: `SMS ${r.sms.status.toLowerCase()} via ${r.sms.provider} — logged in the SMS centre.`,
      });
      setNotifyRow(null); setNotifyMsg("");
      loadBoard(); loadList();
    } catch (e: any) {
      toast({ title: "Notice not sent", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  const classrooms = board?.classrooms ?? [];
  const offenceList = board?.categories.offences ?? [];
  const commendationList = board?.categories.commendations ?? [];
  const categoryList = caseKind === "INCIDENT" ? offenceList : commendationList;

  const statChips = useMemo(() => {
    const s = board?.stat;
    return [
      { label: "Incidents (6 mo)", value: s?.incidents ?? 0, icon: Gavel, cls: "text-red-600" },
      { label: "Open", value: s?.open ?? 0, icon: CircleAlert, cls: "text-amber-600" },
      { label: "Escalated", value: s?.escalated ?? 0, icon: OctagonAlert, cls: "text-red-600" },
      { label: "Resolved", value: s?.resolved ?? 0, icon: CircleCheck, cls: "text-emerald-600" },
      { label: "Commendations", value: s?.commendations ?? 0, icon: Award, cls: "text-violet-600" },
      { label: "Guardians informed", value: s?.notified ?? 0, icon: Phone, cls: "text-cyan-600" },
    ];
  }, [board]);

  /* printable conduct report body */
  const profilePrintable = profile && (
    <div className="hidden print:block">
      <div className="text-center">
        <div className="text-lg font-bold uppercase tracking-wide">Conduct Report</div>
        <div className="text-sm">{profile.student.name} · {profile.student.classroomName} · Index {profile.student.nyansapoIndex}</div>
      </div>
      <table className="w-full text-[10px] mt-3 border-collapse">
        <thead>
          <tr className="border-b">
            <th className="text-left py-1">Date</th><th className="text-left py-1">Kind</th>
            <th className="text-left py-1">Category</th><th className="text-left py-1">Severity</th>
            <th className="text-left py-1">Status</th><th className="text-left py-1">Summary</th>
          </tr>
        </thead>
        <tbody>
          {profile.timeline.map((c) => (
            <tr key={c.id} className="border-b">
              <td className="py-1">{fmtDateShort(c.date)}</td>
              <td className="py-1">{c.kind === "INCIDENT" ? "Incident" : "Commend."}</td>
              <td className="py-1">{c.category}</td>
              <td className="py-1">{c.severity ?? "—"}</td>
              <td className="py-1">{c.status}</td>
              <td className="py-1">{c.description}</td>
            </tr>
          ))}
        </tbody>
      </table>
      <div className="text-[10px] mt-2">
        Summary: {profile.summary.incidents} incident(s) · {profile.summary.commendations} commendation(s) ·
        {profile.summary.open} open · {profile.summary.major} major · guardian notified on {profile.summary.notified}.
      </div>
    </div>
  );

  if (!currentSchoolId) return <LoadingBlock />;

  return (
    <div className="max-w-7xl mx-auto w-full px-3 sm:px-6 py-4 sm:py-6 print-area">
      <ModuleHeader
        title="Discipline & Conduct"
        description="The conduct register — incidents, commendations, learner profiles and guardian notices."
        actions={canManage ? (
          <Button className="gap-1.5 no-print" onClick={() => setCaseOpen(true)}>
            <Plus className="size-4" /> Record case
          </Button>
        ) : undefined}
      />

      {loadErr && (
        <Card className="mb-4 border-destructive"><CardContent className="py-3 text-sm text-destructive">{loadErr}</CardContent></Card>
      )}

      {/* stat chips */}
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 mb-4 no-print">
        {statChips.map((c) => (
          <Card key={c.label} className="py-0 overflow-hidden">
            <CardContent className="p-3 flex items-center gap-2.5">
              <c.icon className={cn("size-5 shrink-0", c.cls)} />
              <div className="min-w-0">
                <div className="text-lg font-bold leading-none">{c.value}</div>
                <div className="text-[11px] text-muted-foreground truncate">{c.label}</div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="mb-4 no-print">
          <TabsTrigger value="register"><Gavel className="size-4 mr-1.5" />Register</TabsTrigger>
          <TabsTrigger value="commendations"><Award className="size-4 mr-1.5" />Commendations</TabsTrigger>
          <TabsTrigger value="profiles"><Users className="size-4 mr-1.5" />Learner profiles</TabsTrigger>
          <TabsTrigger value="analytics"><BarChart3 className="size-4 mr-1.5" />Analytics</TabsTrigger>
        </TabsList>

        {/* ── REGISTER ── */}
        <TabsContent value="register" className="space-y-4">
          <Card className="no-print">
            <CardContent className="p-3 sm:p-4 flex flex-wrap items-end gap-2">
              <div className="w-full sm:w-36">
                <Label className="text-xs">Kind</Label>
                <Select value={fKind} onValueChange={setFKind}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All kinds</SelectItem>
                    <SelectItem value="INCIDENT">Incidents</SelectItem>
                    <SelectItem value="COMMENDATION">Commendations</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="w-full sm:w-32">
                <Label className="text-xs">Status</Label>
                <Select value={fStatus} onValueChange={setFStatus}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All statuses</SelectItem>
                    <SelectItem value="OPEN">Open</SelectItem>
                    <SelectItem value="ESCALATED">Escalated</SelectItem>
                    <SelectItem value="RESOLVED">Resolved</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="w-full sm:w-44">
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
                <EmptyState icon={<Gavel className="size-6" />} title="No cases match these filters"
                  hint="Record the first incident or clear the filters — the register shows everything from the last 6 months by default filters." />
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
                          <th className="py-2.5 px-3">Case</th>
                          <th className="py-2.5 px-3">Details</th>
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
                            <td className="py-2.5 px-3"><CaseBadge row={r} /></td>
                            <td className="py-2.5 px-3 max-w-sm">
                              <p className="leading-snug">{r.description}</p>
                              {r.actionTaken && <p className="text-xs text-muted-foreground mt-0.5">Action: {r.actionTaken}</p>}
                              {r.resolutionNote && <p className="text-xs text-muted-foreground mt-0.5">Resolution: {r.resolutionNote}</p>}
                              <p className="text-xs text-muted-foreground mt-0.5">
                                {r.recordedByName ? `Recorded by ${r.recordedByName}` : ""}
                                {r.resolvedByName ? ` · Resolved by ${r.resolvedByName}` : ""}
                              </p>
                            </td>
                            <td className="py-2.5 px-3 text-right whitespace-nowrap">
                              {canManage && r.kind === "INCIDENT" && (
                                <span className="inline-flex gap-1.5">
                                  <Button size="sm" variant="outline" className="gap-1"
                                    onClick={() => { setResolveRow(r); setResolveStatus(r.status === "OPEN" ? "RESOLVED" : r.status); setResolveNote(r.resolutionNote ?? ""); }}>
                                    {r.status === "OPEN" ? <CircleCheck className="size-3.5" /> : <RotateCcw className="size-3.5" />}
                                    {r.status === "OPEN" ? "Resolve" : r.status === "ESCALATED" ? "Resolve" : "Reopen"}
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
                      <div className="mt-2"><CaseBadge row={r} /></div>
                      <p className="text-sm mt-2 leading-snug">{r.description}</p>
                      {r.actionTaken && <p className="text-xs text-muted-foreground mt-1">Action: {r.actionTaken}</p>}
                      {r.resolutionNote && <p className="text-xs text-muted-foreground mt-0.5">Resolution: {r.resolutionNote}</p>}
                      {canManage && r.kind === "INCIDENT" && (
                        <div className="flex gap-2 mt-2.5">
                          <Button size="sm" variant="outline" className="gap-1 flex-1"
                            onClick={() => { setResolveRow(r); setResolveStatus(r.status === "OPEN" ? "RESOLVED" : r.status); setResolveNote(r.resolutionNote ?? ""); }}>
                            <CircleCheck className="size-3.5" /> {r.status === "OPEN" ? "Resolve" : r.status === "ESCALATED" ? "Resolve" : "Reopen"}
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
        </TabsContent>

        {/* ── COMMENDATIONS ── */}
        <TabsContent value="commendations" className="space-y-4">
          <div className="flex items-center justify-between gap-2 no-print">
            <p className="text-sm text-muted-foreground">
              Positive conduct records — the merit side of the register, from the last 6 months.
            </p>
            {canManage && (
              <Button size="sm" className="gap-1.5" onClick={() => { setCaseKind("COMMENDATION"); setCaseOpen(true); }}>
                <Plus className="size-4" /> Commend learner
              </Button>
            )}
          </div>
          <CommendationsWall base={base} canManage={canManage}
            onKind={() => { setCaseKind("COMMENDATION"); setCaseOpen(true); }}
            onOpenProfile={(id) => { setTab("profiles"); setPStudent(id); }} />
        </TabsContent>

        {/* ── LEARNER PROFILES ── */}
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
              <EmptyState icon={<Users className="size-6" />} title="Pick a learner"
                hint="Choose a class, then a learner — the full conduct timeline, summary and printable conduct report appear here." />
            </CardContent></Card>
          ) : !profile ? <LoadingBlock label="Loading conduct profile…" /> : (
            <>
              <Card className="no-print">
                <CardContent className="p-4">
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3 min-w-0">
                      <Avatar className="size-12"><AvatarFallback className={cn("text-sm text-white", avatarColor(profile.student.name))}>{initials(profile.student.name)}</AvatarFallback></Avatar>
                      <div className="min-w-0">
                        <div className="font-semibold truncate">{profile.student.name}</div>
                        <div className="text-xs text-muted-foreground">
                          {profile.student.classroomName} · {profile.student.nyansapoIndex} · Guardian: {profile.student.guardianName} ({profile.student.guardianPhone})
                        </div>
                      </div>
                    </div>
                    <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()}>
                      <Printer className="size-4" /> Print conduct report
                    </Button>
                  </div>
                  <div className="grid grid-cols-3 sm:grid-cols-6 gap-2 mt-4">
                    {[
                      { label: "Incidents", value: profile.summary.incidents },
                      { label: "Open", value: profile.summary.open },
                      { label: "Escalated", value: profile.summary.escalated },
                      { label: "Major", value: profile.summary.major },
                      { label: "Commendations", value: profile.summary.commendations },
                      { label: "Guardian informed", value: profile.summary.notified },
                    ].map((c) => (
                      <div key={c.label} className="rounded-lg border bg-card p-2.5 text-center">
                        <div className="text-lg font-bold leading-none">{c.value}</div>
                        <div className="text-[10px] text-muted-foreground mt-1">{c.label}</div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>

              <Card className="no-print">
                <CardHeader className="pb-3"><CardTitle className="text-base">Conduct timeline</CardTitle></CardHeader>
                <CardContent className="p-0">
                  {!profile.timeline.length ? (
                    <EmptyState icon={<ShieldAlert className="size-6" />} title="A clean sheet"
                      hint="No incidents or commendations on record for this learner." />
                  ) : (
                    <div className="max-h-[60vh] overflow-y-auto divide-y">
                      {profile.timeline.map((c) => (
                        <div key={c.id} className="p-3 sm:px-4 flex gap-3">
                          <div className={cn("mt-0.5 grid place-items-center size-8 rounded-full shrink-0",
                            c.kind === "INCIDENT" ? "bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300" : "bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300")}>
                            {c.kind === "INCIDENT" ? <Gavel className="size-4" /> : <Award className="size-4" />}
                          </div>
                          <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                              <span className="font-medium text-sm">{c.category}</span>
                              <CaseBadge row={c} />
                              <span className="text-xs text-muted-foreground">{fmtDateShort(c.date)}</span>
                            </div>
                            <p className="text-sm mt-1 leading-snug">{c.description}</p>
                            {c.actionTaken && <p className="text-xs text-muted-foreground mt-0.5">Action: {c.actionTaken}</p>}
                            {c.resolutionNote && <p className="text-xs text-muted-foreground mt-0.5">Resolution: {c.resolutionNote}</p>}
                            {c.parentNotifiedAt && <p className="text-xs text-cyan-600 dark:text-cyan-400 mt-0.5">Guardian informed {fmtDateShort(c.parentNotifiedAt)}</p>}
                            {c.recordedByName && <p className="text-xs text-muted-foreground mt-0.5">Recorded by {c.recordedByName}</p>}
                            {canManage && c.kind === "INCIDENT" && (
                              <div className="flex gap-2 mt-2">
                                <Button size="sm" variant="outline" className="h-7 gap-1"
                                  onClick={() => { setResolveRow(c); setResolveStatus(c.status === "OPEN" ? "RESOLVED" : c.status); setResolveNote(c.resolutionNote ?? ""); }}>
                                  {c.status === "RESOLVED" ? <RotateCcw className="size-3" /> : <CircleCheck className="size-3" />}
                                  {c.status === "RESOLVED" ? "Reopen" : "Resolve / escalate"}
                                </Button>
                                {!c.parentNotifiedAt && (
                                  <Button size="sm" variant="outline" className="h-7 gap-1"
                                    onClick={() => { setNotifyRow(c); setNotifyMsg(""); }}>
                                    <BellRing className="size-3" /> Notify guardian
                                  </Button>
                                )}
                              </div>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </CardContent>
              </Card>
              {profilePrintable}
            </>
          )}
        </TabsContent>

        {/* ── ANALYTICS ── */}
        <TabsContent value="analytics" className="space-y-4">
          {!analytics ? <LoadingBlock label="Loading conduct analytics…" /> : (
            <>
              <div className="grid grid-cols-2 lg:grid-cols-4 gap-2">
                {[
                  { label: "Incidents · 6 months", value: analytics.totals.incidents },
                  { label: "Commendations", value: analytics.totals.commendations },
                  { label: "Guardians informed", value: analytics.totals.notified },
                  { label: "Top offence", value: analytics.totals.topCategory ?? "—" },
                ].map((c) => (
                  <Card key={c.label} className="py-0"><CardContent className="p-3">
                    <div className="text-lg font-bold leading-none truncate">{c.value}</div>
                    <div className="text-[11px] text-muted-foreground mt-1">{c.label}</div>
                  </CardContent></Card>
                ))}
              </div>

              <div className="grid lg:grid-cols-2 gap-4">
                <Card>
                  <CardHeader className="pb-3"><CardTitle className="text-base">Incidents by category</CardTitle></CardHeader>
                  <CardContent className="h-64">
                    {analytics.categories.length ? (
                      <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={analytics.categories} layout="vertical" margin={{ left: 8, right: 16 }}>
                          <XAxis type="number" allowDecimals={false} />
                          <YAxis type="category" dataKey="name" width={120} tick={{ fontSize: 11 }} />
                          <Tooltip cursor={{ fillOpacity: 0.1 }} />
                          <Bar dataKey="count" name="Incidents" fill="var(--theme-primary)" radius={[0, 4, 4, 0]} />
                        </BarChart>
                      </ResponsiveContainer>
                    ) : <EmptyState title="No incidents in the window" />}
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader className="pb-3"><CardTitle className="text-base">6-month trend</CardTitle></CardHeader>
                  <CardContent className="h-64">
                    <ResponsiveContainer width="100%" height="100%">
                      <AreaChart data={analytics.trend} margin={{ left: -16, right: 8 }}>
                        <defs>
                          <linearGradient id="discInc" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stopColor="var(--theme-primary)" stopOpacity={0.5} />
                            <stop offset="100%" stopColor="var(--theme-primary)" stopOpacity={0.05} />
                          </linearGradient>
                          <linearGradient id="discCom" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stopColor="#8b5cf6" stopOpacity={0.5} />
                            <stop offset="100%" stopColor="#8b5cf6" stopOpacity={0.05} />
                          </linearGradient>
                        </defs>
                        <XAxis dataKey="month" tick={{ fontSize: 11 }} />
                        <YAxis allowDecimals={false} />
                        <Tooltip />
                        <Area type="monotone" dataKey="incidents" name="Incidents" stroke="var(--theme-primary)" fill="url(#discInc)" strokeWidth={2} />
                        <Area type="monotone" dataKey="commendations" name="Commendations" stroke="#8b5cf6" fill="url(#discCom)" strokeWidth={2} />
                      </AreaChart>
                    </ResponsiveContainer>
                  </CardContent>
                </Card>
              </div>

              <div className="grid lg:grid-cols-2 gap-4">
                <Card>
                  <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><ShieldAlert className="size-4" /> Repeat offenders</CardTitle></CardHeader>
                  <CardContent className="p-0">
                    {!analytics.repeatOffenders.length ? <EmptyState title="No repeat offenders" /> : (
                      <div className="max-h-80 overflow-y-auto overflow-x-auto">
                        <table className="w-full text-sm">
                          <thead><tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <th className="py-2 px-3">Learner</th><th className="py-2 px-3">Class</th>
                            <th className="py-2 px-3 text-right">Incidents</th><th className="py-2 px-3 text-right">Commendations</th>
                          </tr></thead>
                          <tbody>
                            {analytics.repeatOffenders.map((s) => (
                              <tr key={s.id} className="border-b last:border-0 hover:bg-muted/40">
                                <td className="py-2 px-3">
                                  <button className="font-medium hover:underline"
                                    onClick={() => { setTab("profiles"); setPStudent(s.id); }}>{s.name}</button>
                                </td>
                                <td className="py-2 px-3 text-muted-foreground">{s.classroomName ?? "—"}</td>
                                <td className="py-2 px-3 text-right font-semibold">{s.incidents}</td>
                                <td className="py-2 px-3 text-right">{s.commendations}</td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    )}
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Trophy className="size-4" /> Commendation leaderboard</CardTitle></CardHeader>
                  <CardContent className="p-0">
                    {!analytics.leaderboard.length ? <EmptyState title="No commendations yet" /> : (
                      <div className="max-h-80 overflow-y-auto overflow-x-auto">
                        <table className="w-full text-sm">
                          <thead><tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <th className="py-2 px-3">Learner</th><th className="py-2 px-3">Class</th>
                            <th className="py-2 px-3 text-right">Commendations</th>
                          </tr></thead>
                          <tbody>
                            {analytics.leaderboard.map((s) => (
                              <tr key={s.id} className="border-b last:border-0 hover:bg-muted/40">
                                <td className="py-2 px-3">
                                  <button className="font-medium hover:underline"
                                    onClick={() => { setTab("profiles"); setPStudent(s.id); }}>{s.name}</button>
                                </td>
                                <td className="py-2 px-3 text-muted-foreground">{s.classroomName ?? "—"}</td>
                                <td className="py-2 px-3 text-right font-semibold">{s.commendations}</td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    )}
                  </CardContent>
                </Card>
              </div>

              <Card>
                <CardHeader className="pb-3"><CardTitle className="text-base">Class conduct ranking — incidents per learner</CardTitle></CardHeader>
                <CardContent className="p-0">
                  {!analytics.classRanking.length ? <EmptyState title="No incidents recorded in the window" /> : (
                    <div className="overflow-x-auto">
                      <table className="w-full text-sm">
                        <thead><tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                          <th className="py-2 px-3">Class</th><th className="py-2 px-3 text-right">Incidents</th>
                          <th className="py-2 px-3 text-right">On roll</th><th className="py-2 px-3 text-right">Per learner</th>
                          <th className="py-2 px-3 w-40">Level</th>
                        </tr></thead>
                        <tbody>
                          {analytics.classRanking.map((c) => (
                            <tr key={c.name} className="border-b last:border-0 hover:bg-muted/40">
                              <td className="py-2 px-3 font-medium">{c.name}</td>
                              <td className="py-2 px-3 text-right">{c.incidents}</td>
                              <td className="py-2 px-3 text-right text-muted-foreground">{c.learners}</td>
                              <td className="py-2 px-3 text-right font-semibold">{c.perLearner}</td>
                              <td className="py-2 px-3">
                                <div className="h-2 rounded-full bg-secondary overflow-hidden">
                                  <div className={cn("h-full rounded-full",
                                    c.perLearner >= 1 ? "bg-red-500" : c.perLearner >= 0.5 ? "bg-amber-500" : "bg-emerald-500")}
                                    style={{ width: `${Math.min(100, c.perLearner * 50)}%` }} />
                                </div>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}
                  <p className="text-xs text-muted-foreground px-3 py-2.5">
                    Severity split: {Object.entries(analytics.severity).filter(([, n]) => n > 0).map(([k, n]) => `${n} ${k.toLowerCase()}`).join(" · ") || "none"} —
                    status: {Object.entries(analytics.statusSplit).filter(([, n]) => n > 0).map(([k, n]) => `${n} ${k.toLowerCase()}`).join(" · ") || "none"}.
                    Window: since {analytics.window.since}.
                  </p>
                </CardContent>
              </Card>
            </>
          )}
        </TabsContent>
      </Tabs>

      {/* ── new case dialog ── */}
      <Dialog open={caseOpen} onOpenChange={setCaseOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Record {caseKind === "INCIDENT" ? "an incident" : "a commendation"}</DialogTitle>
            <DialogDescription>
              {caseKind === "INCIDENT"
                ? "Log an offence with its severity — the case opens on the head's desk until resolved."
                : "Record positive conduct — it lands on the commendations wall and the learner's profile."}
            </DialogDescription>
          </DialogHeader>
          <div className="grid gap-3.5">
            <div className="grid grid-cols-2 gap-2">
              {(["INCIDENT", "COMMENDATION"] as const).map((k) => (
                <button key={k} type="button"
                  className={cn("rounded-lg border p-2.5 text-sm font-medium text-left transition-colors",
                    caseKind === k ? "border-primary bg-primary/10" : "hover:bg-muted/50")}
                  onClick={() => { setCaseKind(k); setCCategory(""); setCSeverity(k === "INCIDENT" ? "MINOR" : ""); }}>
                  {k === "INCIDENT" ? <Gavel className="size-4 inline mr-1.5" /> : <Award className="size-4 inline mr-1.5" />}
                  {k === "INCIDENT" ? "Incident" : "Commendation"}
                </button>
              ))}
            </div>
            <div className="grid grid-cols-2 gap-2">
              <div>
                <Label>Class</Label>
                <Select value={cClass} onValueChange={(v) => { setCClass(v); setCStudent(""); }}>
                  <SelectTrigger><SelectValue placeholder="Choose class" /></SelectTrigger>
                  <SelectContent>{classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent>
                </Select>
              </div>
              <div>
                <Label>Learner</Label>
                <Select value={cStudent} onValueChange={setCStudent} disabled={!cClass}>
                  <SelectTrigger><SelectValue placeholder={cClass ? "Choose learner" : "Class first"} /></SelectTrigger>
                  <SelectContent>
                    {roster.map((s) => <SelectItem key={s.id} value={s.id}>{s.rollNo != null ? `#${s.rollNo} · ` : ""}{s.name}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="grid grid-cols-2 gap-2">
              <div>
                <Label>Category</Label>
                <Input list="disc-categories" placeholder="Pick or type…" value={cCategory} onChange={(e) => setCCategory(e.target.value)} />
                <datalist id="disc-categories">
                  {categoryList.map((c) => <option key={c} value={c} />)}
                </datalist>
              </div>
              {caseKind === "INCIDENT" && (
                <div>
                  <Label>Severity</Label>
                  <Select value={cSeverity} onValueChange={setCSeverity}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      {Object.entries(SEVERITY_UI).map(([k, v]) => <SelectItem key={k} value={k}>{v.label}</SelectItem>)}
                    </SelectContent>
                  </Select>
                </div>
              )}
            </div>
            <div>
              <Label>What happened</Label>
              <Textarea rows={3} placeholder={caseKind === "INCIDENT"
                ? "e.g. Reported 25 minutes after the second bell, third time this term…"
                : "e.g. Returned a lost wallet to the office and waited for the owner…"}
                value={cDesc} onChange={(e) => setCDesc(e.target.value)} />
            </div>
            {caseKind === "INCIDENT" && (
              <div>
                <Label>Action taken</Label>
                <Input list="disc-actions" placeholder="Optional — pick or type…" value={cAction} onChange={(e) => setCAction(e.target.value)} />
                <datalist id="disc-actions">
                  {ACTIONS_TAKEN.map((a) => <option key={a} value={a} />)}
                </datalist>
              </div>
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCaseOpen(false)}>Cancel</Button>
            <Button onClick={createCase} disabled={busy || !cStudent || !cCategory.trim() || !cDesc.trim()}>
              {busy ? "Saving…" : "Record"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── resolve / escalate dialog ── */}
      <Dialog open={!!resolveRow} onOpenChange={(o) => !o && setResolveRow(null)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>{resolveRow?.status === "RESOLVED" ? "Reopen case" : "Resolve or escalate case"}</DialogTitle>
            <DialogDescription>
              {resolveRow && `${resolveRow.studentName} — ${resolveRow.category} (${fmtDateShort(resolveRow.date)}).`}
            </DialogDescription>
          </DialogHeader>
          <div className="grid gap-3.5">
            <div>
              <Label>Decision</Label>
              <Select value={resolveStatus} onValueChange={setResolveStatus}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="RESOLVED">Resolved — closed with a note</SelectItem>
                  <SelectItem value="ESCALATED">Escalated — to the head teacher's desk</SelectItem>
                  <SelectItem value="OPEN">Reopen — back on the register</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label>Resolution note</Label>
              <Textarea rows={3} placeholder="What closed the case — counselled, parent came in, sanction completed…"
                value={resolveNote} onChange={(e) => setResolveNote(e.target.value)} />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setResolveRow(null)}>Cancel</Button>
            <Button onClick={saveResolve} disabled={busy}>{busy ? "Saving…" : "Save decision"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── notify guardian dialog ── */}
      <Dialog open={!!notifyRow} onOpenChange={(o) => !o && setNotifyRow(null)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Notify the guardian</DialogTitle>
            <DialogDescription>
              {notifyRow && `${notifyRow.studentName} — ${notifyRow.category}. Sent through the SMS centre (1 credit).`}
            </DialogDescription>
          </DialogHeader>
          <div>
            <Label>Message (editable)</Label>
            <Textarea rows={4} value={notifyMsg} onChange={(e) => setNotifyMsg(e.target.value)}
              placeholder="Leave blank to use the standard head's-office notice…" />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setNotifyRow(null)}>Cancel</Button>
            <Button onClick={sendNotify} disabled={busy} className="gap-1.5">
              <BellRing className="size-4" /> {busy ? "Sending…" : "Send SMS"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

/* ── Commendations wall — a lazy sub-fetch so the register tab stays light ── */
function CommendationsWall({ base, canManage, onKind, onOpenProfile }: {
  base: string; canManage: boolean; onKind: () => void; onOpenProfile: (studentId: string) => void;
}) {
  const [rows, setRows] = useState<CaseRow[] | null>(null);
  useEffect(() => {
    if (!base) return;
    apiGet<{ rows: CaseRow[] }>(`${base}?action=list&kind=COMMENDATION&take=90`)
      .then((r) => setRows(r.rows))
      .catch(() => setRows([]));
  }, [base]);

  if (rows === null) return <LoadingBlock label="Loading commendations…" />;
  if (!rows.length) {
    return (
      <Card><CardContent>
        <EmptyState icon={<Award className="size-6" />} title="No commendations yet"
          hint={canManage ? "Celebrate the first learner — record a commendation." : "Nothing recorded in the last 6 months."} />
      </CardContent></Card>
    );
  }
  return (
    <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-[70vh] overflow-y-auto">
      {rows.map((c) => (
        <Card key={c.id} className="flex flex-col">
          <CardContent className="p-4 flex flex-col h-full">
            <div className="flex items-start gap-2.5">
              <div className="grid place-items-center size-9 rounded-full bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300 shrink-0">
                <Award className="size-4" />
              </div>
              <div className="min-w-0 flex-1">
                <button className="font-semibold text-sm hover:underline text-left" onClick={() => onOpenProfile(c.studentId)}>
                  {c.studentName}
                </button>
                <div className="text-xs text-muted-foreground">{c.classroomName ?? "—"} · {fmtDateShort(c.date)}</div>
              </div>
            </div>
            <Badge className="mt-3 w-fit border-none bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300">{c.category}</Badge>
            <p className="text-sm mt-2 leading-snug flex-1">{c.description}</p>
            <p className="text-xs text-muted-foreground mt-2">{c.recordedByName ? `Recorded by ${c.recordedByName}` : ""}</p>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

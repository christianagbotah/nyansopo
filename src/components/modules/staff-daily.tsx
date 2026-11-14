"use client";
// ── Staff Attendance & Duty (wave 28 — the staff movement book) ──
// The staff-side registers every Ghanaian school keeps beside the learner
// ones: the daily staff attendance book (clock-in/out with the GES 08:00
// punctuality rule), the noticeboard duty roster (one holder per duty slot
// per weekday), the staff movement book (sign-out / sign-in with
// destination & purpose), the leave ledger (annual / sick / maternity /
// paternity / study / compassionate — Ghana Labour Act caps, approval
// stamps the register) and the substitution cover log for absent teachers.
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
import { ModuleHeader, LoadingBlock, EmptyState, StatCard, FormGrid, FormField } from "@/components/ui-helpers";
import { DateField } from "@/components/date-field";
import { fmtDateShort, initials, avatarColor } from "@/lib/format";
import { canAny } from "@/lib/rbac";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  CalendarCheck, CalendarX, ClipboardList, Clock, CircleCheck, CircleDashed, DoorOpen,
  LogIn, LogOut, MapPin, Percent, Plus, Printer, Repeat, Timer, UserCheck, UserX, Users, X,
  BarChart3,
} from "lucide-react";
import {
  ResponsiveContainer, BarChart, Bar, XAxis, YAxis, Tooltip,
} from "recharts";

const OPS = "/ops/staffduty";

const WEEKDAY_NAMES = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

type RegRow = {
  staffId: string; name: string; role: string | null; title: string | null;
  category: string | null; position: string | null; staffCode: string | null;
  status: string | null; clockIn: string | null; clockOut: string | null;
  note: string | null; duty?: string[]; markedByName?: string | null;
};

type RegisterData = {
  date: string; weekday: string;
  stats: { marked: number; present: number; late: number; absent: number; leave: number; rate: number | null; punctuality: number | null; headcount: number; unmarked: number };
  register: RegRow[];
};

type BoardData = {
  date: string; weekday: string;
  stats: { marked: number; present: number; late: number; absent: number; leave: number; rate: number | null; punctuality: number | null; headcount: number; unmarked: number; outNow: number; dutyFilled: number; pendingLeaves: number };
  register: RegRow[];
  openMovements: { id: string; staffId: string; name: string; destination: string; purpose: string | null; expectedReturn: string | null; signedOutAt: string | null }[];
  recentSubstitutions: { id: string; date: string | null; absent: string; substitute: string; classRef: string | null; periods: string | null }[];
  pendingLeaveList: { id: string; name: string; type: string; startsAt: string | null; endsAt: string | null; days: number; reason: string | null }[];
  dutyToday: { id: string; duty: string; staffId: string | null; name: string; note: string | null }[];
};

type LeaveRequest = {
  id: string; staffId: string; name: string; role: string | null; type: string;
  startsAt: string | null; endsAt: string | null; days: number; status: string;
  reason: string | null; requestedByName: string | null; decidedByName: string | null;
  decidedAt: string | null; createdAt: string | null;
};

type LeaveBalance = { type: string; entitlement: number | null; used: number; remaining: number | null };

type LeavesData = {
  requests: LeaveRequest[];
  staff: { staffId: string; name: string; balances: LeaveBalance[] }[];
  entitlements: Record<string, number | null>;
};

type DutyData = {
  weekdays: string[];
  grid: { duty: string; label: string; slots: { id: string | null; weekday: string; staffId: string | null; name: string | null; note: string | null }[] }[];
  load: { staffId: string; name: string; slots: number; duties: string[] }[];
  staff: { staffId: string; name: string }[];
};

type MovementRow = {
  id: string; staffId: string; name: string; role: string | null; destination: string;
  purpose: string | null; signedOutAt: string | null; signedInAt: string | null;
  expectedReturn: string | null; status: string; recordedByName: string | null;
};

type SubRow = { date: string | null; absent: string; substitute: string; classRef: string | null; periods: string | null };

type AnalyticsData = {
  windowDays: number;
  overall: { marked: number; present: number; late: number; absent: number; leave: number; rate: number | null; punctuality: number | null };
  perStaff: { staffId: string; name: string; role: string | null; marked: number; present: number; late: number; absent: number; leave: number; rate: number | null; lateClockIns: number; coversMade: number }[];
  weekdayHeat: { weekday: string; marked: number; present: number; late: number; absent: number; leave: number; rate: number | null }[];
  leaveByType: { type: string; approvedDays: number; pending: number }[];
  pendingLeaves: number;
  substitutions: SubRow[];
};

/* register status palette — the five marks the staff register carries */
const STATUS_KEYS = ["PRESENT", "LATE", "ABSENT", "LEAVE", "OFF"] as const;
type StaffStatus = (typeof STATUS_KEYS)[number];
const STATUS_UI: Record<StaffStatus, { label: string; short: string; chip: string; btn: string }> = {
  PRESENT: { label: "Present", short: "P", chip: "bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30", btn: "bg-emerald-600 text-white" },
  LATE: { label: "Late", short: "L", chip: "bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30", btn: "bg-amber-500 text-white" },
  ABSENT: { label: "Absent", short: "A", chip: "bg-red-500/15 text-red-700 dark:text-red-300 border-red-500/30", btn: "bg-red-600 text-white" },
  LEAVE: { label: "Leave", short: "LV", chip: "bg-violet-500/15 text-violet-700 dark:text-violet-300 border-violet-500/30", btn: "bg-violet-600 text-white" },
  OFF: { label: "Off", short: "OFF", chip: "bg-slate-500/15 text-slate-600 dark:text-slate-300 border-slate-500/30", btn: "bg-slate-500 text-white" },
};

/* leave ledger (Ghana Labour Act types) */
const LEAVE_TYPE_LABEL: Record<string, string> = {
  ANNUAL: "Annual", SICK: "Sick", MATERNITY: "Maternity", PATERNITY: "Paternity",
  STUDY: "Study", COMPASSIONATE: "Compassionate", OTHER: "Other",
};
const LEAVE_TYPE_CHIP: Record<string, string> = {
  ANNUAL: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  SICK: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300",
  MATERNITY: "bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300",
  PATERNITY: "bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300",
  STUDY: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
  COMPASSIONATE: "bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300",
  OTHER: "bg-secondary text-secondary-foreground",
};
const LEAVE_STATUS_CHIP: Record<string, string> = {
  PENDING: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
  APPROVED: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  DECLINED: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300",
  CANCELLED: "bg-secondary text-secondary-foreground",
};
const MOVE_STATUS_CHIP: Record<string, string> = {
  OUT: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
  BACK: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
};

type SheetCell = { status: string; note: string };

const today = () => new Date().toISOString().slice(0, 10);
const dutyLabel = (d: string) => d.charAt(0) + d.slice(1).toLowerCase();
const fmtClock = (isoStr: string | null | undefined) =>
  isoStr ? new Date(isoStr).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }) : "—";
/** "1h 42m" elapsed span — open entries run to now (or the passed clock). */
const fmtDur = (fromIso: string | null | undefined, toIso: string | null | undefined, nowMs?: number) => {
  if (!fromIso) return "—";
  const end = toIso ? new Date(toIso).getTime() : (nowMs ?? Date.now());
  const mins = Math.max(0, Math.round((end - new Date(fromIso).getTime()) / 60000));
  if (mins < 1) return "<1m";
  if (mins < 60) return `${mins}m`;
  return `${Math.floor(mins / 60)}h ${mins % 60}m`;
};
function rateTone(rate: number | null | undefined) {
  if (rate == null) return "text-muted-foreground";
  if (rate >= 85) return "text-emerald-600 dark:text-emerald-400";
  if (rate >= 70) return "text-amber-600 dark:text-amber-400";
  return "text-red-600 dark:text-red-400";
}
const isWeekendDate = (date: string) => {
  const d = new Date(`${date}T00:00:00`);
  return d.getDay() === 0 || d.getDay() === 6;
};

export function StaffDaily() {
  const { currentSchoolId, session, navTarget, schools } = useStore();
  const canManage = canAny(session?.permissions, ["staffduty.manage"]);
  const [tab, setTab] = useState("register");
  const [board, setBoard] = useState<BoardData | null>(null);
  const [loadErr, setLoadErr] = useState<string | null>(null);
  const schoolName = schools.find((s) => s.id === currentSchoolId)?.name ?? "School";

  /* ── register tab ── */
  const [regDate, setRegDate] = useState<string | null>(today());
  const effDate = regDate ?? today();
  const [regData, setRegData] = useState<RegisterData | null>(null);
  const [regErr, setRegErr] = useState<string | null>(null);
  const [sheet, setSheet] = useState<Record<string, SheetCell>>({});

  /* ── leave tab ── */
  const [leaves, setLeaves] = useState<LeavesData | null>(null);
  const [fLeaveStatus, setFLeaveStatus] = useState("all");
  const [leaveOpen, setLeaveOpen] = useState(false);
  const [lvStaff, setLvStaff] = useState("");
  const [lvType, setLvType] = useState("ANNUAL");
  const [lvStart, setLvStart] = useState<string | null>(null);
  const [lvEnd, setLvEnd] = useState<string | null>(null);
  const [lvReason, setLvReason] = useState("");

  /* ── roster tab ── */
  const [duty, setDuty] = useState<DutyData | null>(null);
  const [assignCell, setAssignCell] = useState<{ weekday: string; duty: string } | null>(null);
  const [aStaff, setAStaff] = useState("");
  const [aNote, setANote] = useState("");

  /* ── movement tab ── */
  const [movements, setMovements] = useState<MovementRow[] | null>(null);
  const [now, setNow] = useState(() => Date.now());
  const [signOutOpen, setSignOutOpen] = useState(false);
  const [moStaff, setMoStaff] = useState("");
  const [moDestination, setMoDestination] = useState("");
  const [moPurpose, setMoPurpose] = useState("");

  /* ── cover tab ── */
  const [coverOpen, setCoverOpen] = useState(false);
  const [cvAbsent, setCvAbsent] = useState("");
  const [cvSub, setCvSub] = useState("");
  const [cvDate, setCvDate] = useState<string | null>(today());
  const [cvClass, setCvClass] = useState("");
  const [cvPeriods, setCvPeriods] = useState("");
  const [cvNote, setCvNote] = useState("");

  /* ── analytics ── */
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);

  const base = currentSchoolId ? `/api/school/${currentSchoolId}${OPS}` : "";

  /* deep-link tab (sidebar children / metro / Start search) */
  useEffect(() => {
    if (navTarget?.tab && ["register", "leave", "roster", "movement", "cover", "analytics"].includes(navTarget.tab)) {
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

  /* register load — one date at a time */
  const loadRegister = () => {
    if (!base) return;
    setRegData(null);
    setRegErr(null);
    apiGet<RegisterData>(`${base}?action=register&date=${effDate}`)
      .then((r) => {
        setRegData(r);
        setSheet(Object.fromEntries(r.register.map((row) => [row.staffId, { status: row.status ?? "", note: row.note ?? "" }])));
      })
      .catch((e: Error) => setRegErr(e.message));
  };
  useEffect(loadRegister, [base, effDate]); // eslint-disable-line react-hooks/set-state-in-effect

  /* leave ledger load */
  const loadLeaves = () => {
    if (!base) return;
    setLeaves(null);
    apiGet<LeavesData>(`${base}?action=leaves`)
      .then(setLeaves)
      .catch(() => setLeaves({ requests: [], staff: [], entitlements: {} }));
  };
  useEffect(loadLeaves, [base]); // eslint-disable-line react-hooks/set-state-in-effect

  /* duty roster load */
  useEffect(() => {
    if (!base || tab !== "roster" || duty) return;
    apiGet<DutyData>(`${base}?action=duty`)
      .then(setDuty)
      .catch((e: Error) => toast({ title: "Could not load the duty roster", description: e.message, variant: "destructive" }));
  }, [base, tab, duty]);

  /* movement book load */
  useEffect(() => {
    if (!base || tab !== "movement" || movements) return;
    apiGet<{ movements: MovementRow[] }>(`${base}?action=movements`)
      .then((r) => setMovements(r.movements))
      .catch((e: Error) => { setMovements([]); toast({ title: "Could not load the movement book", description: e.message, variant: "destructive" }); });
  }, [base, tab, movements]);

  /* elapsed-time ticker while the movement book is open */
  useEffect(() => {
    if (tab !== "movement") return;
    const t = setInterval(() => setNow(Date.now()), 30000);
    return () => clearInterval(t);
  }, [tab]);

  /* analytics load — shared by the cover & analytics tabs */
  useEffect(() => {
    if (!base || (tab !== "analytics" && tab !== "cover") || analytics) return;
    apiGet<AnalyticsData>(`${base}?action=analytics`)
      .then(setAnalytics)
      .catch((e: Error) => toast({ title: "Could not load staff analytics", description: e.message, variant: "destructive" }));
  }, [base, tab, analytics]);

  /* ── derived ── */
  const regRows = regData?.register ?? [];
  const dutyByStaff = useMemo(() => {
    const m = new Map<string, string[]>();
    for (const d of board?.dutyToday ?? []) {
      if (!d.staffId) continue;
      m.set(d.staffId, [...(m.get(d.staffId) ?? []), d.duty]);
    }
    return m;
  }, [board]);
  const changedRows = useMemo(() => {
    if (!regData) return [];
    return regData.register.flatMap((row) => {
      const cell = sheet[row.staffId];
      const status = cell?.status ?? row.status ?? "";
      const note = cell?.note ?? row.note ?? "";
      if (status === (row.status ?? "") && note === (row.note ?? "")) return [];
      if (!status) return []; // unmarked rows are never posted
      return [{ staffId: row.staffId, status, note: note.trim() || undefined }];
    });
  }, [regData, sheet]);
  const leaveRows = useMemo(() => {
    const all = leaves?.requests ?? [];
    return fLeaveStatus === "all" ? all : all.filter((r) => r.status === fLeaveStatus);
  }, [leaves, fLeaveStatus]);
  const pendingQueue = board?.pendingLeaveList ?? [];
  const openMoves = board?.openMovements ?? [];
  const coverRows: SubRow[] = analytics?.substitutions ?? board?.recentSubstitutions ?? [];
  const lateTotal = (analytics?.perStaff ?? []).reduce((s, p) => s + p.lateClockIns, 0);
  const leaveDaysTotal = (analytics?.leaveByType ?? []).reduce((s, t) => s + t.approvedDays, 0);
  const chartRows = (analytics?.perStaff ?? []).filter((p) => p.marked > 0)
    .map((p) => ({ ...p, label: p.name.length > 18 ? p.name.slice(0, 16) + "…" : p.name }));
  const attentionRows = (analytics?.perStaff ?? []).filter((p) => p.marked > 0 && (p.lateClockIns > 0 || p.coversMade > 0))
    .sort((a, b) => (b.lateClockIns + b.coversMade) - (a.lateClockIns + a.coversMade));

  /* ── mark every unmarked staff present (local, until Save) ── */
  const markAllPresent = () => {
    const next = { ...sheet };
    let n = 0;
    for (const row of regRows) {
      const cur = next[row.staffId]?.status ?? row.status ?? "";
      if (!cur) { next[row.staffId] = { status: "PRESENT", note: next[row.staffId]?.note ?? "" }; n += 1; }
    }
    setSheet(next);
    toast({ title: n ? `${n} staff set to Present` : "Everyone is already marked", description: n ? "Review the register, then Save register." : undefined });
  };

  /* ── save the register (changed rows only) ── */
  const saveRegister = async () => {
    if (!changedRows.length) return toast({ title: "Nothing to save", description: "Mark a few staff first — Present, Late, Absent, Leave or Off." });
    try {
      const r = await apiPost<{ ok: boolean; saved: number; skipped: number }>(base, { action: "register-save", date: effDate, rows: changedRows });
      toast({ title: "Register saved", description: `${r.saved} of ${regRows.length} staff marked for ${fmtDateShort(effDate)}${r.skipped ? ` · ${r.skipped} skipped` : ""}` });
      loadRegister();
      loadBoard();
    } catch (e: any) {
      toast({ title: "Could not save the register", description: e.message, variant: "destructive" });
    }
  };

  /* ── quick clock-in / clock-out (GES 08:00 rule) ── */
  const clock = async (row: RegRow, kind: "IN" | "OUT") => {
    try {
      const r = await apiPost<{ ok: boolean; late?: boolean }>(base, { action: "clock", staffId: row.staffId, kind, date: effDate });
      toast({
        title: kind === "IN" ? `${row.name} clocked in` : `${row.name} clocked out`,
        description: kind === "IN" && r.late ? "After 08:00 — the register marks them LATE" : `At ${fmtClock(new Date().toISOString())}`,
      });
      loadRegister();
      loadBoard();
    } catch (e: any) {
      toast({ title: kind === "IN" ? "Could not clock in" : "Could not clock out", description: e.message, variant: "destructive" });
    }
  };

  /* ── file a leave request ── */
  const submitLeave = async () => {
    if (!lvStaff) return toast({ title: "Pick the staff member" });
    if (!lvStart || !lvEnd) return toast({ title: "Start and end dates are required" });
    if (lvEnd < lvStart) return toast({ title: "End date is before the start date" });
    try {
      const r = await apiPost<{ ok: boolean; request: { id: string; days: number; status: string } }>(base, {
        action: "leave-create", staffId: lvStaff, type: lvType, startsAt: lvStart, endsAt: lvEnd, reason: lvReason.trim() || undefined,
      });
      toast({ title: "Leave request filed", description: `${leaves?.staff.find((s) => s.staffId === lvStaff)?.name ?? "Staff"} · ${r.request.days} day${r.request.days === 1 ? "" : "s"} — awaiting approval` });
      setLeaveOpen(false);
      setLvStaff(""); setLvType("ANNUAL"); setLvStart(null); setLvEnd(null); setLvReason("");
      loadLeaves();
      loadBoard();
    } catch (e: any) {
      toast({ title: "Could not file the leave request", description: e.message, variant: "destructive" });
    }
  };

  /* ── approve / decline — approval stamps the register ── */
  const decideLeave = async (requestId: string, decision: "APPROVED" | "DECLINED", name: string) => {
    try {
      const r = await apiPost<{ ok: boolean; status: string; stamped: number }>(base, { action: "leave-decide", requestId, decision });
      toast({
        title: decision === "APPROVED" ? `Leave approved — ${name}` : `Leave declined — ${name}`,
        description: decision === "APPROVED" ? `${r.stamped} working day${r.stamped === 1 ? "" : "s"} stamped LEAVE on the register` : "The ledger keeps the request, unapproved.",
      });
      loadLeaves();
      loadBoard();
    } catch (e: any) {
      toast({ title: "Could not record the decision", description: e.message, variant: "destructive" });
    }
  };

  /* ── duty roster: assign / clear a slot ── */
  const submitAssign = async () => {
    if (!assignCell) return;
    if (!aStaff) return toast({ title: "Pick the staff member for this duty slot" });
    try {
      await apiPost(base, { action: "duty-set", weekday: assignCell.weekday, duty: assignCell.duty, staffId: aStaff, note: aNote.trim() || undefined });
      toast({ title: "Duty assigned", description: `${duty?.staff.find((s) => s.staffId === aStaff)?.name ?? "Staff"} — ${dutyLabel(assignCell.duty)} on ${assignCell.weekday}` });
      setAssignCell(null); setAStaff(""); setANote("");
      setDuty(null);
      loadBoard();
    } catch (e: any) {
      toast({ title: "Could not assign the duty", description: e.message, variant: "destructive" });
    }
  };
  const clearDuty = async (weekday: string, dutyKey: string) => {
    try {
      await apiPost(base, { action: "duty-clear", weekday, duty: dutyKey });
      toast({ title: "Duty slot cleared", description: `${dutyLabel(dutyKey)} · ${weekday}` });
      setDuty(null);
      loadBoard();
    } catch (e: any) {
      toast({ title: "Could not clear the slot", description: e.message, variant: "destructive" });
    }
  };

  /* ── movement book: sign out / sign back in ── */
  const submitSignOut = async () => {
    if (!moStaff) return toast({ title: "Pick the staff member" });
    if (!moDestination.trim()) return toast({ title: "Destination is required", description: "The movement book must show where the staff went." });
    try {
      await apiPost(base, { action: "movement-out", staffId: moStaff, destination: moDestination.trim(), purpose: moPurpose.trim() || undefined });
      toast({ title: "Signed out", description: `${board?.register.find((r) => r.staffId === moStaff)?.name ?? "Staff"} → ${moDestination.trim()}` });
      setSignOutOpen(false);
      setMoStaff(""); setMoDestination(""); setMoPurpose("");
      setMovements(null);
      loadBoard();
    } catch (e: any) {
      toast({ title: "Could not sign out", description: e.message, variant: "destructive" });
    }
  };
  const signIn = async (movementId: string, name: string) => {
    try {
      await apiPost(base, { action: "movement-in", movementId });
      toast({ title: "Signed back in", description: `${name} is back on the premises` });
      setMovements(null);
      loadBoard();
    } catch (e: any) {
      toast({ title: "Could not sign back in", description: e.message, variant: "destructive" });
    }
  };

  /* ── cover: record a substitution ── */
  const submitCover = async () => {
    if (!cvAbsent || !cvSub) return toast({ title: "Pick the absent teacher and the substitute" });
    if (cvAbsent === cvSub) return toast({ title: "The substitute must differ from the absent staff" });
    if (!cvDate) return toast({ title: "Cover date is required" });
    try {
      await apiPost(base, {
        action: "substitute-create", absentStaffId: cvAbsent, substituteStaffId: cvSub, date: cvDate,
        classRef: cvClass.trim() || undefined, periods: cvPeriods.trim() || undefined, note: cvNote.trim() || undefined,
      });
      toast({ title: "Cover recorded", description: `${regRows.find((r) => r.staffId === cvSub)?.name ?? "Substitute"} takes ${cvClass.trim() || "the class"} for ${regRows.find((r) => r.staffId === cvAbsent)?.name ?? "the absent teacher"}` });
      setCoverOpen(false);
      setCvAbsent(""); setCvSub(""); setCvDate(today()); setCvClass(""); setCvPeriods(""); setCvNote("");
      setAnalytics(null);
      loadBoard();
    } catch (e: any) {
      toast({ title: "Could not record the cover", description: e.message, variant: "destructive" });
    }
  };

  if (loadErr && !board) {
    return (
      <Card><CardContent>
        <EmptyState icon={<CalendarCheck className="size-6" />} title="Staff attendance & duty unavailable" hint={loadErr} />
      </CardContent></Card>
    );
  }

  const todayWd = board?.weekday ?? WEEKDAY_NAMES[new Date().getDay()];

  return (
    <div className="space-y-4">
      <ModuleHeader
        title="Staff Attendance & Duty"
        description="The staff register — attendance book, duty roster, movement book & leave ledger"
      />

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex-wrap h-auto">
          <TabsTrigger value="register" className="gap-1.5"><CalendarCheck className="size-4" />Register</TabsTrigger>
          <TabsTrigger value="leave" className="gap-1.5"><CalendarX className="size-4" />Leave</TabsTrigger>
          <TabsTrigger value="roster" className="gap-1.5"><ClipboardList className="size-4" />Duty roster</TabsTrigger>
          <TabsTrigger value="movement" className="gap-1.5"><DoorOpen className="size-4" />Movement</TabsTrigger>
          <TabsTrigger value="cover" className="gap-1.5"><Repeat className="size-4" />Cover</TabsTrigger>
          <TabsTrigger value="analytics" className="gap-1.5"><BarChart3 className="size-4" />Analytics</TabsTrigger>
        </TabsList>

        {/* ── STAFF REGISTER ── */}
        <TabsContent value="register" className="space-y-4">
          {!regData && !regErr ? <LoadingBlock label="Loading the staff register…" /> : regErr ? (
            <Card><CardContent>
              <EmptyState icon={<CalendarCheck className="size-6" />} title="Could not load the register" hint={regErr} />
            </CardContent></Card>
          ) : regData ? (
            <>
              {/* date + bulk toolbar */}
              <Card className="no-print">
                <CardContent className="p-3 sm:p-4 flex flex-wrap items-end gap-3">
                  <div className="w-full sm:w-44">
                    <Label className="text-xs">Register date</Label>
                    <DateField value={regDate} onChange={setRegDate} />
                  </div>
                  <Badge variant="secondary" className="mb-1.5">{regData.weekday} · {fmtDateShort(effDate)}</Badge>
                  {isWeekendDate(effDate) && (
                    <Badge className="mb-1.5 border-none bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300">Weekend — register usually not kept</Badge>
                  )}
                  {canManage && (
                    <div className="ml-auto flex flex-wrap items-center gap-2">
                      <Button size="sm" variant="outline" className="gap-1.5" onClick={markAllPresent}>
                        <UserCheck className="size-4" /> Mark all present
                      </Button>
                      <Button size="sm" className="gap-1.5" onClick={saveRegister} disabled={!changedRows.length}>
                        <CircleCheck className="size-4" /> Save register{changedRows.length ? ` (${changedRows.length})` : ""}
                      </Button>
                    </div>
                  )}
                </CardContent>
              </Card>

              {/* stat row */}
              <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <StatCard icon={<UserCheck className="size-4" />} label="At post" value={regData.stats.present + regData.stats.late} sub={`${regData.stats.headcount} on strength`} />
                <StatCard icon={<Clock className="size-4" />} label="Late" value={regData.stats.late} sub="after 08:00" tone="gold" />
                <StatCard icon={<UserX className="size-4" />} label="Absent" value={regData.stats.absent} sub="unexcused" tone={regData.stats.absent ? "danger" : "default"} />
                <StatCard icon={<CalendarX className="size-4" />} label="On leave" value={regData.stats.leave} sub="approved absence" />
                <StatCard icon={<CircleDashed className="size-4" />} label="Unmarked" value={regData.stats.unmarked} sub="awaiting the clerk" />
                <StatCard icon={<Percent className="size-4" />} label="Rate" value={regData.stats.rate != null ? `${regData.stats.rate}%` : "—"} sub={`${regData.stats.punctuality ?? 0}% punctual`} tone={regData.stats.rate != null && regData.stats.rate < 70 ? "danger" : "primary"} />
              </div>

              {/* the register itself */}
              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm flex items-center gap-2">
                    <CalendarCheck className="size-4 text-primary" />Staff register — {fmtDateShort(effDate)}
                    {regData.stats.marked ? <span className="text-xs font-normal text-muted-foreground">{regData.stats.marked}/{regData.stats.headcount} marked</span> : null}
                  </CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                  <div className="max-h-[520px] overflow-auto">
                    <table className="w-full min-w-[860px] text-sm">
                      <thead className="sticky top-0 bg-card z-10">
                        <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                          <th className="py-2.5 px-3">Staff</th>
                          <th className="py-2.5 px-3">Duty today</th>
                          <th className="py-2.5 px-3 text-center no-print">Mark</th>
                          <th className="py-2.5 px-3 text-center">Status</th>
                          <th className="py-2.5 px-3">Clocked</th>
                          <th className="py-2.5 px-3">Note</th>
                          {canManage && <th className="py-2.5 px-3 no-print">Clock in / out</th>}
                        </tr>
                      </thead>
                      <tbody>
                        {regRows.map((row) => {
                          const cell = sheet[row.staffId];
                          const status = cell?.status ?? row.status ?? "";
                          const note = cell?.note ?? "";
                          const duties = dutyByStaff.get(row.staffId) ?? [];
                          const noteNeeded = status === "ABSENT" || status === "LATE" || status === "LEAVE";
                          return (
                            <tr key={row.staffId} className="border-b last:border-0 align-middle hover:bg-muted/40">
                              <td className="py-2.5 px-3">
                                <div className="flex items-center gap-2 min-w-0">
                                  <Avatar className="size-7"><AvatarFallback className={cn("text-[10px] text-white", avatarColor(row.name))}>{initials(row.name)}</AvatarFallback></Avatar>
                                  <div className="min-w-0">
                                    <p className="truncate font-medium">{row.name}</p>
                                    <p className="text-[11px] text-muted-foreground truncate">
                                      {row.title ?? row.role ?? "Staff"}{row.staffCode ? ` · ${row.staffCode}` : ""}
                                    </p>
                                  </div>
                                </div>
                              </td>
                              <td className="py-2.5 px-3">
                                <div className="flex flex-wrap gap-1">
                                  {duties.length ? duties.map((d) => (
                                    <Badge key={d} variant="secondary" className="text-[10px]">{dutyLabel(d)}</Badge>
                                  )) : <span className="text-xs text-muted-foreground">—</span>}
                                </div>
                              </td>
                              <td className="py-2.5 px-3 no-print">
                                <div className="inline-flex rounded-lg overflow-hidden border">
                                  {STATUS_KEYS.map((k) => (
                                    <button key={k}
                                      className={cn("px-2 py-1 text-[11px] font-medium transition-colors", status === k ? STATUS_UI[k].btn : "bg-background hover:bg-muted")}
                                      disabled={!canManage}
                                      onClick={() => setSheet((s) => ({ ...s, [row.staffId]: { status: k, note: s[row.staffId]?.note ?? "" } }))}
                                    >
                                      {STATUS_UI[k].short}
                                    </button>
                                  ))}
                                </div>
                              </td>
                              <td className="py-2.5 px-3 text-center">
                                <Badge className={cn("border", status ? STATUS_UI[status as StaffStatus]?.chip ?? "" : "border-transparent bg-secondary text-secondary-foreground")}>
                                  {status ? STATUS_UI[status as StaffStatus]?.label ?? status : "Not marked"}
                                </Badge>
                              </td>
                              <td className="py-2.5 px-3 whitespace-nowrap text-xs tabular-nums">
                                <span className={cn(row.clockIn ? "text-emerald-600 dark:text-emerald-400" : "text-muted-foreground")}>in {fmtClock(row.clockIn)}</span>
                                <span className="text-muted-foreground"> · out {fmtClock(row.clockOut)}</span>
                              </td>
                              <td className="py-2.5 px-3 no-print">
                                {noteNeeded && canManage ? (
                                  <Input
                                    value={note}
                                    onChange={(e) => setSheet((s) => ({ ...s, [row.staffId]: { status: s[row.staffId]?.status ?? "", note: e.target.value } }))}
                                    placeholder="Reason…" className="h-7 w-36 text-xs"
                                  />
                                ) : (
                                  <span className="text-xs text-muted-foreground line-clamp-1 max-w-36">{row.note ?? "—"}</span>
                                )}
                              </td>
                              {canManage && (
                                <td className="py-2.5 px-3 no-print">
                                  <div className="flex gap-1">
                                    <Button size="sm" variant="outline" className="h-7 px-2" title="Clock in" onClick={() => clock(row, "IN")}><LogIn className="size-3.5" /></Button>
                                    <Button size="sm" variant="outline" className="h-7 px-2" title="Clock out" onClick={() => clock(row, "OUT")}><LogOut className="size-3.5" /></Button>
                                  </div>
                                </td>
                              )}
                            </tr>
                          );
                        })}
                        {!regRows.length && (
                          <tr><td colSpan={canManage ? 7 : 6} className="py-6 text-center text-muted-foreground">No active staff on the strength yet.</td></tr>
                        )}
                      </tbody>
                    </table>
                  </div>
                </CardContent>
              </Card>
            </>
          ) : null}
        </TabsContent>

        {/* ── LEAVE LEDGER ── */}
        <TabsContent value="leave" className="space-y-4">
          {!leaves ? <LoadingBlock label="Loading the leave ledger…" /> : (
            <>
              {/* pending queue */}
              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm flex items-center gap-2">
                    <CalendarX className="size-4 text-primary" />Pending requests
                    {pendingQueue.length ? <Badge className="border-none bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">{pendingQueue.length}</Badge> : null}
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-1.5">
                  {pendingQueue.length ? pendingQueue.map((l) => (
                    <div key={l.id} className="flex flex-wrap items-center justify-between gap-2 rounded-md border p-2 text-sm">
                      <div className="min-w-0">
                        <p className="font-medium truncate">
                          {l.name}
                          <span className="text-xs text-muted-foreground"> · {fmtDateShort(l.startsAt)} → {fmtDateShort(l.endsAt)} · {l.days} day{l.days === 1 ? "" : "s"}</span>
                        </p>
                        <p className="text-xs text-muted-foreground truncate">{l.reason ?? "No reason given"}</p>
                      </div>
                      <div className="flex items-center gap-1.5 shrink-0">
                        <Badge className={cn("border-none", LEAVE_TYPE_CHIP[l.type] ?? LEAVE_TYPE_CHIP.OTHER)}>{LEAVE_TYPE_LABEL[l.type] ?? l.type}</Badge>
                        {canManage && (
                          <>
                            <Button size="sm" className="h-7 gap-1" onClick={() => decideLeave(l.id, "APPROVED", l.name)}>
                              <CircleCheck className="size-3.5" /> Approve
                            </Button>
                            <Button size="sm" variant="outline" className="h-7 gap-1" onClick={() => decideLeave(l.id, "DECLINED", l.name)}>
                              <X className="size-3.5" /> Decline
                            </Button>
                          </>
                        )}
                      </div>
                    </div>
                  )) : <p className="text-sm text-muted-foreground">No leave requests awaiting a decision.</p>}
                </CardContent>
              </Card>

              {/* filter chips + new request */}
              <div className="flex flex-wrap items-center gap-2 no-print">
                {["all", "PENDING", "APPROVED", "DECLINED", "CANCELLED"].map((k) => (
                  <button key={k} onClick={() => setFLeaveStatus(k)}
                    className={cn("h-7 rounded-full border px-3 text-xs font-medium transition-colors",
                      fLeaveStatus === k ? "border-primary bg-primary text-primary-foreground" : "bg-background hover:bg-muted")}>
                    {k === "all" ? "All" : k.charAt(0) + k.slice(1).toLowerCase()}
                  </button>
                ))}
                {canManage && (
                  <Button size="sm" className="ml-auto gap-1.5" onClick={() => { setLvStaff(""); setLvType("ANNUAL"); setLvStart(null); setLvEnd(null); setLvReason(""); setLeaveOpen(true); }}>
                    <Plus className="size-4" /> New request
                  </Button>
                )}
              </div>

              {/* requests table */}
              <Card>
                <CardHeader className="pb-2"><CardTitle className="text-sm">Leave ledger — {leaveRows.length} request{leaveRows.length === 1 ? "" : "s"}</CardTitle></CardHeader>
                <CardContent className="p-0">
                  {leaveRows.length ? (
                    <div className="overflow-x-auto">
                      <table className="w-full min-w-[760px] text-sm">
                        <thead>
                          <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <th className="py-2.5 px-3">Staff</th>
                            <th className="py-2.5 px-3">Type</th>
                            <th className="py-2.5 px-3">Dates</th>
                            <th className="py-2.5 px-3 text-center">Days</th>
                            <th className="py-2.5 px-3">Reason</th>
                            <th className="py-2.5 px-3">Status</th>
                            <th className="py-2.5 px-3">Decided by</th>
                          </tr>
                        </thead>
                        <tbody>
                          {leaveRows.map((l) => (
                            <tr key={l.id} className="border-b last:border-0 align-middle hover:bg-muted/40">
                              <td className="py-2.5 px-3">
                                <div className="flex items-center gap-2 min-w-0">
                                  <Avatar className="size-7"><AvatarFallback className={cn("text-[10px] text-white", avatarColor(l.name))}>{initials(l.name)}</AvatarFallback></Avatar>
                                  <div className="min-w-0">
                                    <p className="truncate font-medium">{l.name}</p>
                                    <p className="text-[11px] text-muted-foreground truncate">filed by {l.requestedByName ?? "—"} · {fmtDateShort(l.createdAt)}</p>
                                  </div>
                                </div>
                              </td>
                              <td className="py-2.5 px-3">
                                <Badge className={cn("border-none", LEAVE_TYPE_CHIP[l.type] ?? LEAVE_TYPE_CHIP.OTHER)}>{LEAVE_TYPE_LABEL[l.type] ?? l.type}</Badge>
                              </td>
                              <td className="py-2.5 px-3 whitespace-nowrap">{fmtDateShort(l.startsAt)} → {fmtDateShort(l.endsAt)}</td>
                              <td className="py-2.5 px-3 text-center tabular-nums">{l.days}</td>
                              <td className="py-2.5 px-3 max-w-52"><span className="line-clamp-1 text-xs text-muted-foreground">{l.reason ?? "—"}</span></td>
                              <td className="py-2.5 px-3">
                                <Badge className={cn("border-none", LEAVE_STATUS_CHIP[l.status] ?? "bg-secondary text-secondary-foreground")}>{l.status.charAt(0) + l.status.slice(1).toLowerCase()}</Badge>
                              </td>
                              <td className="py-2.5 px-3 text-xs text-muted-foreground">
                                {l.decidedByName ? `${l.decidedByName} · ${fmtDateShort(l.decidedAt)}` : "—"}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  ) : (
                    <div className="py-2">
                      <EmptyState icon={<CalendarX className="size-5" />} title="No leave requests here" hint="Change the filter chips, or file a new request." />
                    </div>
                  )}
                </CardContent>
              </Card>

              {/* balances summary */}
              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm flex items-center gap-2"><Users className="size-4 text-primary" />Leave balances</CardTitle>
                  <p className="text-xs text-muted-foreground font-normal">Approved days used against the entitlement caps — null cap means no fixed limit.</p>
                </CardHeader>
                <CardContent>
                  {leaves.staff.length ? (
                    <div className="grid md:grid-cols-2 xl:grid-cols-3 gap-3 max-h-[420px] overflow-y-auto">
                      {leaves.staff.map((s) => (
                        <Card key={s.staffId}>
                          <CardContent className="p-3.5">
                            <div className="flex items-center gap-2">
                              <Avatar className="size-7"><AvatarFallback className={cn("text-[10px] text-white", avatarColor(s.name))}>{initials(s.name)}</AvatarFallback></Avatar>
                              <p className="font-medium truncate text-sm">{s.name}</p>
                            </div>
                            {(["ANNUAL", "SICK"] as const).map((t) => {
                              const b = s.balances.find((x) => x.type === t);
                              const pct = b?.entitlement ? Math.min(100, (b.used / b.entitlement) * 100) : b?.used ? 100 : 0;
                              return (
                                <div key={t} className="mt-2.5">
                                  <div className="flex items-baseline justify-between gap-2 text-xs">
                                    <span className="font-medium">{LEAVE_TYPE_LABEL[t]}</span>
                                    <span className="text-muted-foreground tabular-nums">
                                      {b?.entitlement != null ? `${b.used}/${b.entitlement} used · ${b.remaining} left` : `${b?.used ?? 0} used · no fixed cap`}
                                    </span>
                                  </div>
                                  <div className="h-1.5 rounded-full bg-muted mt-1 overflow-hidden">
                                    <div className={cn("h-full rounded-full", pct >= 100 ? "bg-red-600" : pct >= 75 ? "bg-amber-500" : "bg-emerald-600")} style={{ width: `${pct}%` }} />
                                  </div>
                                </div>
                              );
                            })}
                          </CardContent>
                        </Card>
                      ))}
                    </div>
                  ) : <EmptyState icon={<Users className="size-5" />} title="No staff on the strength" />}
                </CardContent>
              </Card>
            </>
          )}
        </TabsContent>

        {/* ── DUTY ROSTER ── */}
        <TabsContent value="roster" className="space-y-4">
          {!duty ? <LoadingBlock label="Loading the duty roster…" /> : (
            <>
              <div className="flex flex-wrap items-center justify-between gap-2 no-print">
                <p className="text-sm text-muted-foreground">The noticeboard roster — one holder per duty slot per weekday. Today is {todayWd}.</p>
                <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()}>
                  <Printer className="size-4" /> Print roster
                </Button>
              </div>

              {/* printable roster sheet */}
              <Card className="print-area">
                <CardContent className="p-4">
                  <div className="pb-3 mb-3 border-b">
                    <p className="font-bold text-lg uppercase tracking-tight">{schoolName}</p>
                    <p className="text-xs text-muted-foreground">Duty roster · week of {fmtDateShort(board?.date ?? null)} · printed {fmtDateShort(today())}</p>
                  </div>
                  <div className="overflow-x-auto">
                    <table className="w-full min-w-[680px] text-sm">
                      <thead>
                        <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                          <th className="py-2.5 px-3">Duty</th>
                          {duty.weekdays.map((wd) => (
                            <th key={wd} className={cn("py-2.5 px-3 text-center", wd === todayWd && "text-primary")}>
                              {wd}
                              {wd === todayWd && <span className="block text-[9px] font-medium normal-case">today</span>}
                            </th>
                          ))}
                        </tr>
                      </thead>
                      <tbody>
                        {duty.grid.map((g) => (
                          <tr key={g.duty} className="border-b last:border-0 align-middle hover:bg-muted/40">
                            <td className="py-2 px-3 font-medium whitespace-nowrap">{g.label}</td>
                            {g.slots.map((slot) => (
                              <td key={slot.weekday} className="py-2 px-2">
                                {slot.name ? (
                                  <div className={cn("flex items-center justify-between gap-1 rounded-md border px-2 py-1.5",
                                    slot.weekday === todayWd && "border-primary/40 bg-primary/5")}>
                                    <span className="truncate text-xs font-medium" title={slot.note ?? undefined}>{slot.name}</span>
                                    {canManage && (
                                      <button aria-label="Clear slot"
                                        className="shrink-0 rounded-sm p-0.5 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                        onClick={() => clearDuty(slot.weekday, g.duty)}>
                                        <X className="size-3.5" />
                                      </button>
                                    )}
                                  </div>
                                ) : canManage ? (
                                  <button className="flex h-9 w-full items-center justify-center gap-1 rounded-md border border-dashed text-xs text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                    onClick={() => { setAssignCell({ weekday: slot.weekday, duty: g.duty }); setAStaff(""); setANote(""); }}>
                                    <Plus className="size-3.5" /> Assign
                                  </button>
                                ) : (
                                  <span className="block text-center text-xs text-muted-foreground">—</span>
                                )}
                              </td>
                            ))}
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </CardContent>
              </Card>

              {/* duty load */}
              <Card className="no-print">
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm flex items-center gap-2"><Users className="size-4 text-primary" />Duty load — who carries the week</CardTitle>
                </CardHeader>
                <CardContent className="space-y-1.5 max-h-80 overflow-y-auto">
                  {duty.load.filter((s) => s.slots > 0).length ? duty.load.filter((s) => s.slots > 0).map((s) => (
                    <div key={s.staffId} className="flex flex-wrap items-center justify-between gap-2 rounded-md border p-2 text-sm">
                      <span className="font-medium truncate">{s.name}</span>
                      <span className="flex items-center gap-1.5 flex-wrap">
                        <Badge variant="secondary" className="shrink-0">{s.slots} slot{s.slots === 1 ? "" : "s"}</Badge>
                        {s.duties.slice(0, 6).map((d) => <Badge key={d} variant="outline" className="text-[10px] capitalize">{d}</Badge>)}
                      </span>
                    </div>
                  )) : (
                    <EmptyState icon={<ClipboardList className="size-5" />} title="No duty assigned yet" hint={canManage ? "Assign staff to the grid above — one holder per slot." : "Ask the administrator to publish the roster."} />
                  )}
                </CardContent>
              </Card>
            </>
          )}
        </TabsContent>

        {/* ── MOVEMENT BOOK ── */}
        <TabsContent value="movement" className="space-y-4">
          {movements === null ? <LoadingBlock label="Opening the movement book…" /> : (
            <>
              <div className="flex flex-wrap items-center justify-between gap-2 no-print">
                <p className="text-sm text-muted-foreground">The GES staff movement book — every off-premises errand is signed out here and signed back in.</p>
                <div className="flex flex-wrap gap-2">
                  {canManage && (
                    <Button size="sm" className="gap-1.5" onClick={() => { setMoStaff(""); setMoDestination(""); setMoPurpose(""); setSignOutOpen(true); }}>
                      <LogOut className="size-4" /> Sign out staff
                    </Button>
                  )}
                  <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()}>
                    <Printer className="size-4" /> Print book
                  </Button>
                </div>
              </div>

              <div className="print-area space-y-4">
                {/* currently out */}
                <Card>
                  <CardHeader className="pb-2">
                    <CardTitle className="text-sm flex items-center gap-2">
                      <DoorOpen className="size-4 text-primary" />Currently out
                      <Badge variant="secondary">{openMoves.length}</Badge>
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    {openMoves.length ? (
                      <div className="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
                        {openMoves.map((m) => (
                          <div key={m.id} className="rounded-lg border p-3 space-y-1.5">
                            <div className="flex items-center gap-2">
                              <Avatar className="size-7"><AvatarFallback className={cn("text-[10px] text-white", avatarColor(m.name))}>{initials(m.name)}</AvatarFallback></Avatar>
                              <p className="font-medium truncate text-sm">{m.name}</p>
                            </div>
                            <p className="text-xs flex items-center gap-1"><MapPin className="size-3.5 text-muted-foreground shrink-0" />{m.destination}</p>
                            {m.purpose && <p className="text-xs text-muted-foreground">{m.purpose}</p>}
                            <div className="flex flex-wrap items-center justify-between gap-2 pt-1">
                              <span className="text-[11px] text-muted-foreground">
                                out {fmtClock(m.signedOutAt)} · <Timer className="inline size-3" /> {fmtDur(m.signedOutAt, null, now)}
                              </span>
                              {canManage && (
                                <Button size="sm" variant="outline" className="h-7 gap-1" onClick={() => signIn(m.id, m.name)}>
                                  <LogIn className="size-3.5" /> Back in
                                </Button>
                              )}
                            </div>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <EmptyState icon={<DoorOpen className="size-5" />} title="Everybody is on the premises" hint="Signed-out staff appear here with their destination and elapsed time." />
                    )}
                  </CardContent>
                </Card>

                {/* history */}
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm">Movement book — history</CardTitle></CardHeader>
                  <CardContent className="p-0">
                    {movements.length ? (
                      <div className="overflow-x-auto">
                        <table className="w-full min-w-[760px] text-sm">
                          <thead>
                            <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                              <th className="py-2.5 px-3">Staff</th>
                              <th className="py-2.5 px-3">Destination</th>
                              <th className="py-2.5 px-3">Purpose</th>
                              <th className="py-2.5 px-3">Out at</th>
                              <th className="py-2.5 px-3">In at</th>
                              <th className="py-2.5 px-3">Duration</th>
                              <th className="py-2.5 px-3">Recorded by</th>
                            </tr>
                          </thead>
                          <tbody>
                            {movements.map((m) => (
                              <tr key={m.id} className="border-b last:border-0 align-middle hover:bg-muted/40">
                                <td className="py-2.5 px-3">
                                  <div className="flex items-center gap-2 min-w-0">
                                    <span className="truncate font-medium">{m.name}</span>
                                    <Badge className={cn("border-none shrink-0", MOVE_STATUS_CHIP[m.status] ?? "bg-secondary text-secondary-foreground")}>{m.status}</Badge>
                                  </div>
                                </td>
                                <td className="py-2.5 px-3">{m.destination}</td>
                                <td className="py-2.5 px-3 max-w-44"><span className="line-clamp-1 text-xs text-muted-foreground">{m.purpose ?? "—"}</span></td>
                                <td className="py-2.5 px-3 whitespace-nowrap text-xs tabular-nums">{fmtClock(m.signedOutAt)}</td>
                                <td className="py-2.5 px-3 whitespace-nowrap text-xs tabular-nums">{fmtClock(m.signedInAt)}</td>
                                <td className="py-2.5 px-3 whitespace-nowrap text-xs tabular-nums">{fmtDur(m.signedOutAt, m.signedInAt)}</td>
                                <td className="py-2.5 px-3 text-xs text-muted-foreground">{m.recordedByName ?? "—"}</td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    ) : (
                      <div className="py-2">
                        <EmptyState icon={<DoorOpen className="size-5" />} title="The movement book is empty" hint="Sign out the first staff errand." />
                      </div>
                    )}
                  </CardContent>
                </Card>
              </div>
            </>
          )}
        </TabsContent>

        {/* ── COVER (SUBSTITUTIONS) ── */}
        <TabsContent value="cover" className="space-y-4">
          {!board ? <LoadingBlock label="Loading the cover log…" /> : (
            <>
              <div className="flex flex-wrap items-center justify-between gap-2 no-print">
                <p className="text-sm text-muted-foreground">The substitution log — who covered whose class while a teacher was away.</p>
                {canManage && (
                  <Button size="sm" className="gap-1.5" onClick={() => { setCvAbsent(""); setCvSub(""); setCvDate(today()); setCvClass(""); setCvPeriods(""); setCvNote(""); setCoverOpen(true); }}>
                    <Plus className="size-4" /> Record cover
                  </Button>
                )}
              </div>

              {/* absent-soon hints */}
              {pendingQueue.length > 0 && (
                <Card className="no-print">
                  <CardHeader className="pb-2">
                    <CardTitle className="text-sm flex items-center gap-2"><CalendarX className="size-4 text-amber-600" />Absent soon — leave awaiting approval</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-1.5">
                    {pendingQueue.map((l) => (
                      <div key={l.id} className="flex flex-wrap items-center justify-between gap-2 rounded-md border p-2 text-sm">
                        <span className="font-medium truncate">{l.name}</span>
                        <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
                          <Badge className={cn("border-none", LEAVE_TYPE_CHIP[l.type] ?? LEAVE_TYPE_CHIP.OTHER)}>{LEAVE_TYPE_LABEL[l.type] ?? l.type}</Badge>
                          {fmtDateShort(l.startsAt)} → {fmtDateShort(l.endsAt)} · {l.days} day{l.days === 1 ? "" : "s"}
                        </span>
                      </div>
                    ))}
                    <p className="text-xs text-muted-foreground">Approve on the Leave tab and the register stamps itself.</p>
                  </CardContent>
                </Card>
              )}

              {/* recent covers */}
              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm flex items-center gap-2"><Repeat className="size-4 text-primary" />Recent covers</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                  {coverRows.length ? (
                    <div className="overflow-x-auto">
                      <table className="w-full min-w-[560px] text-sm">
                        <thead>
                          <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <th className="py-2.5 px-3">Date</th>
                            <th className="py-2.5 px-3">Absent</th>
                            <th className="py-2.5 px-3">Substitute</th>
                            <th className="py-2.5 px-3">Class</th>
                            <th className="py-2.5 px-3">Periods</th>
                          </tr>
                        </thead>
                        <tbody>
                          {coverRows.map((s, i) => (
                            <tr key={`${s.date}-${i}`} className="border-b last:border-0 align-middle hover:bg-muted/40">
                              <td className="py-2.5 px-3 whitespace-nowrap">{fmtDateShort(s.date)}</td>
                              <td className="py-2.5 px-3 font-medium">{s.absent}</td>
                              <td className="py-2.5 px-3">{s.substitute}</td>
                              <td className="py-2.5 px-3">{s.classRef ?? "—"}</td>
                              <td className="py-2.5 px-3 text-xs text-muted-foreground">{s.periods ?? "—"}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  ) : (
                    <div className="py-2">
                      <EmptyState icon={<Repeat className="size-5" />} title="No covers recorded" hint="Record cover when a teacher takes another teacher's class." />
                    </div>
                  )}
                </CardContent>
              </Card>
            </>
          )}
        </TabsContent>

        {/* ── ANALYTICS ── */}
        <TabsContent value="analytics" className="space-y-4">
          {!analytics ? <LoadingBlock label="Loading staff analytics…" /> : (
            <>
              <div className="flex flex-wrap items-center justify-between gap-2 no-print">
                <Badge variant="secondary">Last {analytics.windowDays} days</Badge>
                <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()}>
                  <Printer className="size-4" /> Print sheet
                </Button>
              </div>

              <div className="print-area space-y-4">
                {/* stat row */}
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                  <StatCard icon={<Percent className="size-4" />} label="Attendance rate" value={analytics.overall.rate != null ? `${analytics.overall.rate}%` : "—"} sub={`${analytics.overall.marked} marks recorded`} tone={(analytics.overall.rate ?? 100) < 70 ? "danger" : "primary"} />
                  <StatCard icon={<UserCheck className="size-4" />} label="Punctuality" value={analytics.overall.punctuality != null ? `${analytics.overall.punctuality}%` : "—"} sub="at post by 08:00" />
                  <StatCard icon={<Clock className="size-4" />} label="Late clock-ins" value={lateTotal} sub="GES rule breaches" tone="gold" />
                  <StatCard icon={<CalendarX className="size-4" />} label="Leave days approved" value={leaveDaysTotal} sub={`${analytics.pendingLeaves} request${analytics.pendingLeaves === 1 ? "" : "s"} pending`} />
                  <StatCard icon={<Repeat className="size-4" />} label="Covers recorded" value={analytics.substitutions.length} sub="substitutions · 30 days" />
                </div>

                <div className="grid lg:grid-cols-2 gap-4">
                  {/* per-staff rate */}
                  <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-sm">Attendance rate by staff</CardTitle></CardHeader>
                    <CardContent>
                      {chartRows.length ? (
                        <div className="h-64">
                          <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={chartRows} layout="vertical" margin={{ top: 0, right: 12, bottom: 0, left: 8 }}>
                              <XAxis type="number" domain={[0, 100]} tick={{ fontSize: 11 }} />
                              <YAxis type="category" dataKey="label" width={130} tick={{ fontSize: 10 }} />
                              <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8 }} />
                              <Bar dataKey="rate" name="Rate %" fill="#0d9488" radius={[0, 4, 4, 0]} />
                            </BarChart>
                          </ResponsiveContainer>
                        </div>
                      ) : <EmptyState icon={<Percent className="size-5" />} title="No marks in the window" hint="The register has not been kept in the last 30 days." />}
                    </CardContent>
                  </Card>

                  {/* late + covers chips */}
                  <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-sm">Late clock-ins & cover duty</CardTitle></CardHeader>
                    <CardContent className="space-y-1.5 max-h-64 overflow-y-auto">
                      {attentionRows.length ? attentionRows.map((s) => (
                        <div key={s.staffId} className="flex flex-wrap items-center justify-between gap-2 rounded-md border p-2 text-sm">
                          <span className="font-medium truncate">{s.name}</span>
                          <span className="flex items-center gap-1.5 shrink-0">
                            {s.lateClockIns > 0 && <Badge className="border-none bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">{s.lateClockIns} late</Badge>}
                            {s.coversMade > 0 && <Badge className="border-none bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300">{s.coversMade} cover{s.coversMade === 1 ? "" : "s"}</Badge>}
                            {s.lateClockIns === 0 && s.coversMade === 0 && <span className="text-xs text-muted-foreground">clean record</span>}
                          </span>
                        </div>
                      )) : <EmptyState icon={<CircleCheck className="size-5" />} title="Clean sheet — nobody late, nobody covering" />}
                    </CardContent>
                  </Card>
                </div>

                {/* weekday heat */}
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm">Weekday heat — where the register thins out</CardTitle></CardHeader>
                  <CardContent className="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    {analytics.weekdayHeat.map((w) => (
                      <div key={w.weekday} className={cn("rounded-lg border p-3 text-center", (w.rate ?? 100) < 85 && "border-red-500/40 bg-red-500/5")}>
                        <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{w.weekday}</p>
                        <p className={cn("text-xl font-bold mt-1", rateTone(w.rate))}>{w.rate != null ? `${w.rate}%` : "—"}</p>
                        <p className="text-[11px] text-muted-foreground mt-0.5">{w.absent} absent · {w.marked} marked</p>
                      </div>
                    ))}
                  </CardContent>
                </Card>

                <div className="grid lg:grid-cols-2 gap-4">
                  {/* leave usage */}
                  <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-sm">Leave usage by type</CardTitle></CardHeader>
                    <CardContent className="p-0">
                      <table className="w-full text-sm">
                        <thead>
                          <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <th className="py-2 px-3">Type</th>
                            <th className="py-2 px-3 text-right">Approved days</th>
                            <th className="py-2 px-3 text-right">Pending</th>
                          </tr>
                        </thead>
                        <tbody>
                          {analytics.leaveByType.map((t) => (
                            <tr key={t.type} className="border-b last:border-0">
                              <td className="py-2 px-3"><Badge className={cn("border-none", LEAVE_TYPE_CHIP[t.type] ?? LEAVE_TYPE_CHIP.OTHER)}>{LEAVE_TYPE_LABEL[t.type] ?? t.type}</Badge></td>
                              <td className="py-2 px-3 text-right tabular-nums">{t.approvedDays}</td>
                              <td className="py-2 px-3 text-right tabular-nums">{t.pending}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </CardContent>
                  </Card>

                  {/* substitutions log */}
                  <Card>
                    <CardHeader className="pb-2"><CardTitle className="text-sm">Substitution log</CardTitle></CardHeader>
                    <CardContent className="space-y-1.5 max-h-72 overflow-y-auto">
                      {analytics.substitutions.length ? analytics.substitutions.map((s, i) => (
                        <div key={`${s.date}-${i}`} className="flex flex-wrap items-center justify-between gap-2 rounded-md border p-2 text-sm">
                          <span className="min-w-0"><span className="font-medium">{s.absent}</span> <span className="text-muted-foreground">→ {s.substitute}</span></span>
                          <span className="text-xs text-muted-foreground">
                            {fmtDateShort(s.date)}{s.classRef ? ` · ${s.classRef}` : ""}{s.periods ? ` · ${s.periods}` : ""}
                          </span>
                        </div>
                      )) : <EmptyState icon={<Repeat className="size-5" />} title="No substitutions in the window" />}
                    </CardContent>
                  </Card>
                </div>
              </div>
            </>
          )}
        </TabsContent>
      </Tabs>

      {/* ── new leave request dialog ── */}
      <Dialog open={leaveOpen} onOpenChange={setLeaveOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>File a leave request</DialogTitle>
            <DialogDescription>Ghana Labour Act leave types — approval stamps LEAVE onto the staff register for the working days.</DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <FormGrid cols={2}>
              <FormField label="Staff member" required>
                <Select value={lvStaff} onValueChange={setLvStaff}>
                  <SelectTrigger><SelectValue placeholder="Choose staff…" /></SelectTrigger>
                  <SelectContent>
                    {(leaves?.staff ?? []).map((s) => <SelectItem key={s.staffId} value={s.staffId}>{s.name}</SelectItem>)}
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Leave type" required>
                <Select value={lvType} onValueChange={setLvType}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    {Object.entries(LEAVE_TYPE_LABEL).map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="First day" required>
                <DateField value={lvStart} onChange={setLvStart} />
              </FormField>
              <FormField label="Last day" required>
                <DateField value={lvEnd} onChange={setLvEnd} />
              </FormField>
            </FormGrid>
            <FormField label="Reason" hint="Optional — appears on the leave ledger.">
              <Textarea rows={2} value={lvReason} onChange={(e) => setLvReason(e.target.value)} placeholder="e.g. referred to the district hospital for review" />
            </FormField>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setLeaveOpen(false)}>Cancel</Button>
            <Button onClick={submitLeave} disabled={!lvStaff || !lvStart || !lvEnd}>File request</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── assign duty dialog ── */}
      <Dialog open={!!assignCell} onOpenChange={(o) => !o && setAssignCell(null)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Assign duty</DialogTitle>
            <DialogDescription>
              {assignCell ? `${dutyLabel(assignCell.duty)} · ${assignCell.weekday} — one holder per slot; assigning replaces the current one.` : ""}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <FormField label="Staff member" required>
              <Select value={aStaff} onValueChange={setAStaff}>
                <SelectTrigger><SelectValue placeholder="Choose staff…" /></SelectTrigger>
                <SelectContent>
                  {(duty?.staff ?? []).map((s) => <SelectItem key={s.staffId} value={s.staffId}>{s.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="Note" hint="Optional — e.g. swaps with Mr. Mensah this term.">
              <Input value={aNote} onChange={(e) => setANote(e.target.value)} />
            </FormField>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setAssignCell(null)}>Cancel</Button>
            <Button onClick={submitAssign} disabled={!aStaff}>Assign</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── sign out staff dialog ── */}
      <Dialog open={signOutOpen} onOpenChange={setSignOutOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Sign out staff</DialogTitle>
            <DialogDescription>Enter the movement book — destination is required; the entry stays open until they sign back in.</DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <FormField label="Staff member" required>
              <Select value={moStaff} onValueChange={setMoStaff}>
                <SelectTrigger><SelectValue placeholder="Choose staff…" /></SelectTrigger>
                <SelectContent>
                  {(board?.register ?? []).map((r) => <SelectItem key={r.staffId} value={r.staffId}>{r.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="Destination" required>
              <Input value={moDestination} onChange={(e) => setMoDestination(e.target.value)} placeholder="e.g. GES district office, Accra" />
            </FormField>
            <FormField label="Purpose">
              <Input value={moPurpose} onChange={(e) => setMoPurpose(e.target.value)} placeholder="e.g. collect workshop materials" />
            </FormField>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setSignOutOpen(false)}>Cancel</Button>
            <Button onClick={submitSignOut} disabled={!moStaff || !moDestination.trim()} className="gap-1.5"><LogOut className="size-4" /> Sign out</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── record cover dialog ── */}
      <Dialog open={coverOpen} onOpenChange={setCoverOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Record cover</DialogTitle>
            <DialogDescription>The substitution log — who took whose class, and which periods.</DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <FormGrid cols={2}>
              <FormField label="Absent staff" required>
                <Select value={cvAbsent} onValueChange={setCvAbsent}>
                  <SelectTrigger><SelectValue placeholder="Choose staff…" /></SelectTrigger>
                  <SelectContent>
                    {(board?.register ?? []).map((r) => <SelectItem key={r.staffId} value={r.staffId}>{r.name}</SelectItem>)}
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Substitute" required hint="Must differ from the absent staff.">
                <Select value={cvSub} onValueChange={setCvSub}>
                  <SelectTrigger><SelectValue placeholder="Choose staff…" /></SelectTrigger>
                  <SelectContent>
                    {(board?.register ?? []).filter((r) => r.staffId !== cvAbsent).map((r) => <SelectItem key={r.staffId} value={r.staffId}>{r.name}</SelectItem>)}
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Date" required>
                <DateField value={cvDate} onChange={setCvDate} />
              </FormField>
              <FormField label="Class" hint="e.g. JHS 3 B9">
                <Input value={cvClass} onChange={(e) => setCvClass(e.target.value)} />
              </FormField>
              <FormField label="Periods" hint="e.g. 1-3 or Maths, English" span={2}>
                <Input value={cvPeriods} onChange={(e) => setCvPeriods(e.target.value)} />
              </FormField>
            </FormGrid>
            <FormField label="Note">
              <Input value={cvNote} onChange={(e) => setCvNote(e.target.value)} />
            </FormField>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCoverOpen(false)}>Cancel</Button>
            <Button onClick={submitCover} disabled={!cvAbsent || !cvSub || !cvDate}>Record cover</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

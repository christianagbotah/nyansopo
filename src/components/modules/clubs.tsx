"use client";
// ── Clubs & Societies (wave 27 — the co-curricular register) ──
// The club ledger: club register with patron & meeting venue, membership
// rosters with executive offices, the meeting register with attendance
// (present / absent / excused), inter-schools & competition events with
// outcomes and placements, printable learner co-curricular profiles and
// participation analytics.
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
  Trophy, Plus, Search, BellRing, Printer, BarChart3, ChevronLeft, ChevronRight,
  Users, BookUser, ClipboardList, Medal, UserPlus, Crown, LogOut, RotateCcw,
  CalendarDays, CircleCheck, Megaphone, Pencil, Landmark, HandHeart, Palette, Trophy as TrophyIcon, Library, Sparkles,
} from "lucide-react";
import {
  ResponsiveContainer, AreaChart, Area, BarChart, Bar, XAxis, YAxis, Tooltip,
} from "recharts";

const OPS = "/ops/clubs";

type ClubRow = {
  id: string; name: string; category: string; description: string | null;
  patronName: string | null; meetingDay: string | null; venue: string | null;
  status: string; foundedOn: string | null;
  members: number; meetings: number; events: number; lastMetAt: string | null;
};

type BoardData = {
  classrooms: { id: string; name: string }[];
  stat: { clubs: number; activeClubs: number; members: number; officers: number; meetings90d: number; attRate90d: number | null; events: number; upcoming: number };
  clubs: ClubRow[];
  recentMeetings: MeetingRow[];
  upcomingEvents: EventRow[];
  catalogues: { categories: string[]; weekdays: string[]; offices: string[] };
};

type MemberRow = {
  id: string; studentId: string; studentName: string; nyansapoIndex: string;
  classroomId: string | null; classroomName: string | null;
  guardianName: string | null; guardianPhone: string | null;
  office: string | null; joinedOn: string; status: string;
  leftOn: string | null; leftReason: string | null;
  meetings: number; present: number; excused: number; attRate: number | null;
};

type MeetingRow = {
  id: string; clubId: string; clubName: string | null; date: string;
  topic: string | null; minutes: string | null; takenByName: string | null;
  guardiansNotifiedAt: string | null;
  expected: number; present: number; excused: number; absent: number; rate: number | null;
};

type EventRow = {
  id: string; clubId: string | null; clubName: string | null; title: string;
  date: string; level: string; outcome: string; placement: string | null;
  participants: string | null; note: string | null; recordedByName: string | null;
};

type ClubDetail = {
  club: Omit<ClubRow, "members" | "meetings" | "events" | "lastMetAt"> & { memberCount: number; createdByName: string | null };
  members: MemberRow[];
  meetings: MeetingRow[];
  events: EventRow[];
};

type MeetingDetail = {
  meeting: { id: string; clubId: string; clubName: string; date: string; topic: string | null; minutes: string | null; takenByName: string | null; guardiansNotifiedAt: string | null };
  rows: { attendanceId: string; membershipId: string; status: string; note: string | null; member: MemberRow }[];
};

type ProfileData = {
  student: { id: string; name: string; nyansapoIndex: string; gender: string; classroomId: string; classroomName: string | null; guardianName: string | null; guardianPhone: string | null };
  memberships: { id: string; clubName: string; clubCategory: string; patronName: string | null; clubStatus: string; office: string | null; joinedOn: string; status: string; leftOn: string | null; leftReason: string | null; meetings: number; present: number; excused: number; rate: number | null }[];
  events: EventRow[];
  summary: { clubs: number; active: number; offices: number; meetings: number; attRate: number | null; represented: number };
};

type AnalyticsData = {
  window: { since: string };
  totals: { clubs: number; activeClubs: number; members: number; offices: number; meetings90d: number; attRate90d: number | null; events: number; upcoming: number };
  categoryMix: { name: string; clubs: number; members: number }[];
  clubRanking: { name: string; members: number; meetings: number; rate: number | null }[];
  weeks: { week: string; meetings: number; rate: number }[];
  classParticipation: { name: string; members: number; learners: number; pct: number }[];
  officeHolders: { office: string; name: string }[];
  recentEvents: EventRow[];
  upcomingEvents: EventRow[];
};

type RosterRow = { id: string; name: string; rollNo: number | null; nyansapoIndex: string };

const CATEGORY_UI: Record<string, { label: string; cls: string; icon: any }> = {
  ACADEMIC: { label: "Academic", cls: "bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300", icon: Library },
  SERVICE: { label: "Service", cls: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300", icon: HandHeart },
  FAITH: { label: "Faith", cls: "bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300", icon: Landmark },
  CULTURAL: { label: "Cultural", cls: "bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300", icon: Palette },
  SPORTS: { label: "Sports", cls: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300", icon: TrophyIcon },
  GENERAL: { label: "General", cls: "bg-secondary text-secondary-foreground", icon: Sparkles },
};
const CLUB_STATUS_UI: Record<string, { label: string; cls: string }> = {
  ACTIVE: { label: "Active", cls: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300" },
  DORMANT: { label: "Dormant", cls: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300" },
  ARCHIVED: { label: "Archived", cls: "bg-secondary text-secondary-foreground" },
};
const LEVEL_UI: Record<string, { label: string; cls: string }> = {
  SCHOOL: { label: "School", cls: "bg-secondary text-secondary-foreground" },
  ZONAL: { label: "Zonal", cls: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300" },
  DISTRICT: { label: "District", cls: "bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300" },
  REGIONAL: { label: "Regional", cls: "bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300" },
  NATIONAL: { label: "National", cls: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300" },
};
const OFFICE_UI: Record<string, string> = {
  PRESIDENT: "President",
  VICE_PRESIDENT: "Vice-President",
  SECRETARY: "Secretary",
  TREASURER: "Treasurer",
  ORGANISER: "Organiser",
};
const ATT_UI: Record<string, { label: string; btn: string }> = {
  PRESENT: { label: "Present", btn: "bg-emerald-600 text-white" },
  ABSENT: { label: "Absent", btn: "bg-red-600 text-white" },
  EXCUSED: { label: "Excused", btn: "bg-amber-600 text-white" },
};

function catUI(c: string) { return CATEGORY_UI[c] ?? CATEGORY_UI.GENERAL; }
function rateTone(rate: number | null | undefined) {
  if (rate == null) return "text-muted-foreground";
  if (rate >= 85) return "text-emerald-600 dark:text-emerald-400";
  if (rate >= 70) return "text-amber-600 dark:text-amber-400";
  return "text-red-600 dark:text-red-400";
}

export function Clubs() {
  const { currentSchoolId, session, navTarget } = useStore();
  const canManage = canAny(session?.permissions, ["clubs.manage"]);
  const [tab, setTab] = useState("register");
  const [board, setBoard] = useState<BoardData | null>(null);
  const [loadErr, setLoadErr] = useState<string | null>(null);

  /* ── register tab: club detail panel ── */
  const [openClubId, setOpenClubId] = useState<string | null>(null);
  const [clubDetail, setClubDetail] = useState<ClubDetail | null>(null);
  const [clubDetailTick, setClubDetailTick] = useState(0);
  const [clubSearch, setClubSearch] = useState("");
  const [fCategory, setFCategory] = useState("all");
  const [fStatus, setFStatus] = useState("all");

  /* ── club create / edit dialog ── */
  const [clubOpen, setClubOpen] = useState(false);
  const [editClub, setEditClub] = useState<ClubRow | ClubDetail["club"] | null>(null);
  const [cName, setCName] = useState("");
  const [cCategory, setCCategory] = useState("GENERAL");
  const [cDescription, setCDescription] = useState("");
  const [cPatron, setCPatron] = useState("");
  const [cDay, setCDay] = useState("none");
  const [cVenue, setCVenue] = useState("");
  const [cFounded, setCFounded] = useState<string | null>(null);
  const [cStatus, setCStatus] = useState("ACTIVE");

  /* ── member add dialog (shared by club detail) ── */
  const [memberOpen, setMemberOpen] = useState(false);
  const [mClass, setMClass] = useState("");
  const [mRoster, setMRoster] = useState<RosterRow[]>([]);
  const [mStudent, setMStudent] = useState("");
  const [mOffice, setMOffice] = useState("none");

  /* ── office transfer / leave dialogs ── */
  const [officeRow, setOfficeRow] = useState<MemberRow | null>(null);
  const [newOffice, setNewOffice] = useState("none");
  const [leaveRow, setLeaveRow] = useState<MemberRow | null>(null);
  const [leaveReason, setLeaveReason] = useState("");

  /* ── meetings tab ── */
  const [fClub, setFClub] = useState("all");
  const [mSearch, setMSearch] = useState("");
  const [meetings, setMeetings] = useState<MeetingRow[] | null>(null);
  const [createMeetingOpen, setCreateMeetingOpen] = useState(false);
  const [mtClub, setMtClub] = useState("");
  const [mtDate, setMtDate] = useState<string | null>(null);
  const [mtTopic, setMtTopic] = useState("");
  const [openMeetingId, setOpenMeetingId] = useState<string | null>(null);
  const [meetingDetail, setMeetingDetail] = useState<MeetingDetail | null>(null);
  const [meetingTick, setMeetingTick] = useState(0);
  const [sheet, setSheet] = useState<Record<string, string>>({});
  const [minutesRow, setMinutesRow] = useState<MeetingRow | null>(null);
  const [minutesText, setMinutesText] = useState("");
  const [notifyRow, setNotifyRow] = useState<MeetingRow | null>(null);
  const [notifyMsg, setNotifyMsg] = useState("");

  /* ── events tab ── */
  const [events, setEvents] = useState<EventRow[] | null>(null);
  const [eventOpen, setEventOpen] = useState(false);
  const [editEvent, setEditEvent] = useState<EventRow | null>(null);
  const [eTitle, setETitle] = useState("");
  const [eClub, setEClub] = useState("none");
  const [eDate, setEDate] = useState<string | null>(null);
  const [eLevel, setELever] = useState("SCHOOL");
  const [eOutcome, setEOutcome] = useState("UPCOMING");
  const [ePlacement, setEPlacement] = useState("");
  const [eParticipants, setEParticipants] = useState("");
  const [eNote, setENote] = useState("");

  /* ── profiles tab ── */
  const [pClass, setPClass] = useState("");
  const [pRoster, setPRoster] = useState<RosterRow[]>([]);
  const [pStudent, setPStudent] = useState("");
  const [profile, setProfile] = useState<ProfileData | null>(null);

  /* ── analytics ── */
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);

  const base = currentSchoolId ? `/api/school/${currentSchoolId}${OPS}` : "";
  const classrooms = board?.classrooms ?? [];
  const catalogues = board?.catalogues;

  /* deep-link tab */
  useEffect(() => {
    if (navTarget?.tab && ["register", "meetings", "events", "profiles", "analytics"].includes(navTarget.tab)) {
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

  /* club detail load */
  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (!base || !openClubId) { setClubDetail(null); return; }
    setClubDetail(null);
    apiGet<ClubDetail>(`${base}?action=club&clubId=${openClubId}`)
      .then(setClubDetail)
      .catch((e: Error) => toast({ title: "Could not load the club", description: e.message, variant: "destructive" }));
  }, [base, openClubId, clubDetailTick]);

  /* member-add roster */
  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (!base || !mClass) { setMRoster([]); return; }
    apiGet<{ rows: RosterRow[] }>(`${base}?action=roster&classroomId=${mClass}`)
      .then((r) => setMRoster(r.rows))
      .catch(() => setMRoster([]));
  }, [base, mClass]);

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
      .catch((e: Error) => toast({ title: "Could not load the co-curricular profile", description: e.message, variant: "destructive" }));
  }, [base, pStudent]);

  /* meetings list load */
  const loadMeetings = () => {
    if (!base) return;
    setMeetings(null);
    apiGet<{ meetings: MeetingRow[] }>(`${base}?action=meetings`)
      .then((r) => setMeetings(r.meetings))
      .catch(() => setMeetings([]));
  };
  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (base && tab === "meetings" && meetings === null) loadMeetings();
  }, [base, tab, meetings]);

  /* events list load */
  useEffect(() => {
    if (!base || tab !== "events" || events !== null) return;
    apiGet<{ events: EventRow[] }>(`${base}?action=events`)
      .then((r) => setEvents(r.events))
      .catch((e: Error) => toast({ title: "Could not load events", description: e.message, variant: "destructive" }));
  }, [base, tab, events]);

  /* analytics load */
  useEffect(() => {
    if (!base || tab !== "analytics" || analytics) return;
    apiGet<AnalyticsData>(`${base}?action=analytics`)
      .then(setAnalytics)
      .catch((e: Error) => toast({ title: "Could not load participation analytics", description: e.message, variant: "destructive" }));
  }, [base, tab, analytics]);

  /* meeting sheet load */
  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    if (!base || !openMeetingId) { setMeetingDetail(null); return; }
    setMeetingDetail(null);
    apiGet<MeetingDetail>(`${base}?action=meeting&meetingId=${openMeetingId}`)
      .then((r) => { setMeetingDetail(r); setSheet(Object.fromEntries(r.rows.map((row) => [row.membershipId, row.status]))); })
      .catch((e: Error) => toast({ title: "Could not load the meeting register", description: e.message, variant: "destructive" }));
  }, [base, openMeetingId, meetingTick]);

  const reloadClubDetail = () => { setClubDetail(null); setClubDetailTick((t) => t + 1); loadBoard(); };
  const reloadMeeting = () => { setMeetingTick((t) => t + 1); };

  /* ── create / edit club ── */
  const openClubDialog = (club: ClubRow | ClubDetail["club"] | null) => {
    setEditClub(club);
    setCName(club?.name ?? "");
    setCCategory(club?.category ?? "GENERAL");
    setCDescription(club?.description ?? "");
    setCPatron(club?.patronName ?? "");
    setCDay(club?.meetingDay ?? "none");
    setCVenue(club?.venue ?? "");
    setCFounded(club?.foundedOn?.slice(0, 10) ?? null);
    setCStatus((club as ClubRow | null)?.status ?? "ACTIVE");
    setClubOpen(true);
  };
  const submitClub = async () => {
    if (!cName.trim()) return toast({ title: "Club name is required" });
    try {
      await apiPost(base, {
        action: editClub ? "club-update" : "club-create",
        ...(editClub ? { id: editClub.id } : {}),
        name: cName.trim(),
        category: cCategory,
        description: cDescription.trim() || undefined,
        patronName: cPatron.trim() || undefined,
        meetingDay: cDay === "none" ? null : cDay,
        venue: cVenue.trim() || undefined,
        foundedOn: cFounded ?? undefined,
        status: cStatus,
      });
      toast({ title: editClub ? "Club updated" : "Club registered", description: cName.trim() });
      setClubOpen(false);
      loadBoard();
      if (openClubId) reloadClubDetail();
    } catch (e: any) {
      toast({ title: "Could not save the club", description: e.message, variant: "destructive" });
    }
  };

  /* ── add member ── */
  const submitMember = async () => {
    if (!openClubId) return;
    if (!mStudent) return toast({ title: "Pick the learner first" });
    try {
      await apiPost(base, {
        action: "membership-add",
        clubId: openClubId,
        studentId: mStudent,
        office: mOffice === "none" ? null : mOffice,
      });
      toast({ title: "Member enrolled", description: `${mRoster.find((r) => r.id === mStudent)?.name ?? "Learner"} joined the club` });
      setMemberOpen(false);
      setMStudent(""); setMOffice("none");
      reloadClubDetail();
    } catch (e: any) {
      toast({ title: "Could not enrol the member", description: e.message, variant: "destructive" });
    }
  };

  /* ── transfer office ── */
  const submitOffice = async () => {
    if (!officeRow) return;
    try {
      await apiPost(base, { action: "membership-update", id: officeRow.id, office: newOffice === "none" ? null : newOffice });
      toast({ title: newOffice === "none" ? "Office vacated" : "Office assigned", description: `${officeRow.studentName} — ${newOffice === "none" ? "member" : OFFICE_UI[newOffice]}` });
      setOfficeRow(null); setNewOffice("none");
      reloadClubDetail();
    } catch (e: any) {
      toast({ title: "Could not update the office", description: e.message, variant: "destructive" });
    }
  };

  /* ── leave / rejoin ── */
  const submitLeave = async () => {
    if (!leaveRow) return;
    try {
      await apiPost(base, {
        action: "membership-update", id: leaveRow.id,
        status: leaveRow.status === "ACTIVE" ? "LEFT" : "ACTIVE",
        ...(leaveRow.status === "ACTIVE" ? { leftReason: leaveReason.trim() || undefined } : {}),
      });
      toast({ title: leaveRow.status === "ACTIVE" ? "Membership ended" : "Member re-joined", description: leaveRow.studentName });
      setLeaveRow(null); setLeaveReason("");
      reloadClubDetail();
    } catch (e: any) {
      toast({ title: "Could not update the membership", description: e.message, variant: "destructive" });
    }
  };

  /* ── create meeting ── */
  const submitMeeting = async () => {
    if (!mtClub) return toast({ title: "Pick the club first" });
    if (!mtDate) return toast({ title: "Meeting date is required" });
    try {
      await apiPost(base, { action: "meeting-create", clubId: mtClub, date: mtDate, topic: mtTopic.trim() || undefined });
      toast({ title: "Meeting scheduled", description: `${board?.clubs.find((c) => c.id === mtClub)?.name ?? "Club"} — register seeded for the roster` });
      setCreateMeetingOpen(false);
      setMtClub(""); setMtDate(null); setMtTopic("");
      setMeetings(null); loadBoard();
    } catch (e: any) {
      toast({ title: "Could not schedule the meeting", description: e.message, variant: "destructive" });
    }
  };

  /* ── save attendance ── */
  const submitAttendance = async () => {
    if (!openMeetingId || !meetingDetail) return;
    const rows = meetingDetail.rows.map((r) => ({ membershipId: r.membershipId, status: sheet[r.membershipId] ?? "ABSENT" }));
    try {
      const r = await apiPost<{ summary: { expected: number; present: number; rate: number | null } }>(base, {
        action: "attendance-save", meetingId: openMeetingId, rows,
      });
      toast({ title: "Register saved", description: `${r.summary.present}/${r.summary.expected} present · ${r.summary.rate ?? 0}% rate` });
      setMeetingDetail(null);
      setMeetings(null);
      if (openClubId) reloadClubDetail();
      loadBoard();
    } catch (e: any) {
      toast({ title: "Could not save the register", description: e.message, variant: "destructive" });
    }
  };

  /* ── save minutes ── */
  const submitMinutes = async () => {
    if (!minutesRow) return;
    try {
      await apiPost(base, { action: "meeting-update", id: minutesRow.id, minutes: minutesText.trim() || null });
      toast({ title: "Minutes saved", description: minutesRow.clubName });
      setMinutesRow(null); setMinutesText("");
      setMeetings(null);
      reloadMeeting();
    } catch (e: any) {
      toast({ title: "Could not save the minutes", description: e.message, variant: "destructive" });
    }
  };

  /* ── guardian notice ── */
  const submitNotify = async () => {
    if (!notifyRow) return;
    try {
      const r = await apiPost<{ notified: number; failed: number }>(base, {
        action: "notify", meetingId: notifyRow.id, message: notifyMsg.trim() || undefined,
      });
      toast({ title: `Guardians notified (${r.notified} SMS)`, description: r.failed ? `${r.failed} failed — check SMS credits` : notifyRow.clubName ?? undefined });
      setNotifyRow(null); setNotifyMsg("");
      setMeetings(null);
    } catch (e: any) {
      toast({ title: "SMS not sent", description: e.message, variant: "destructive" });
    }
  };

  /* ── create / edit event ── */
  const openEventDialog = (ev: EventRow | null) => {
    setEditEvent(ev);
    setETitle(ev?.title ?? "");
    setEClub(ev?.clubId ?? "none");
    setEDate(ev?.date.slice(0, 10) ?? null);
    setELever(ev?.level ?? "SCHOOL");
    setEOutcome(ev?.outcome ?? "UPCOMING");
    setEPlacement(ev?.placement ?? "");
    setEParticipants(ev?.participants ?? "");
    setENote(ev?.note ?? "");
    setEventOpen(true);
  };
  const submitEvent = async () => {
    if (!eTitle.trim()) return toast({ title: "Event title is required" });
    if (!eDate) return toast({ title: "Event date is required" });
    try {
      await apiPost(base, {
        action: editEvent ? "event-update" : "event-create",
        ...(editEvent ? { id: editEvent.id } : {}),
        title: eTitle.trim(),
        clubId: eClub === "none" ? null : eClub,
        date: eDate,
        level: eLevel,
        outcome: eOutcome,
        placement: ePlacement.trim() || undefined,
        participants: eParticipants.trim() || undefined,
        note: eNote.trim() || undefined,
      });
      toast({ title: editEvent ? "Event updated" : "Event recorded", description: eTitle.trim() });
      setEventOpen(false);
      setEvents(null);
      loadBoard();
    } catch (e: any) {
      toast({ title: "Could not save the event", description: e.message, variant: "destructive" });
    }
  };

  /* filtered club cards */
  const clubCards = useMemo(() => {
    if (!board) return [];
    const q = clubSearch.trim().toLowerCase();
    return board.clubs.filter((c) =>
      (fCategory === "all" || c.category === fCategory) &&
      (fStatus === "all" || c.status === fStatus) &&
      (!q || c.name.toLowerCase().includes(q) || (c.patronName ?? "").toLowerCase().includes(q)),
    );
  }, [board, clubSearch, fCategory, fStatus]);

  /* filtered meetings */
  const meetingRows = useMemo(() => {
    const all = meetings ?? board?.recentMeetings ?? [];
    const q = mSearch.trim().toLowerCase();
    return all.filter((m) =>
      (fClub === "all" || m.clubId === fClub) &&
      (!q || (m.clubName ?? "").toLowerCase().includes(q) || (m.topic ?? "").toLowerCase().includes(q)),
    );
  }, [meetings, board, fClub, mSearch]);

  /* filtered events */
  const eventRows = events ?? [];

  if (loadErr && !board) {
    return (
      <Card><CardContent>
        <EmptyState icon={<Trophy className="size-6" />} title="Clubs & societies unavailable" hint={loadErr} />
      </CardContent></Card>
    );
  }

  return (
    <div className="space-y-4">
      <ModuleHeader
        title="Clubs & Societies"
        description="The co-curricular register — clubs with patrons and rosters, the meeting register, inter-schools events, learner profiles and participation analytics."
        actions={
          <div className="flex flex-wrap gap-2 no-print">
            {canManage && (
              <Button size="sm" className="gap-1.5" onClick={() => openClubDialog(null)}>
                <Plus className="size-4" /> New club
              </Button>
            )}
          </div>
        }
      />

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList>
          <TabsTrigger value="register" className="gap-1.5"><Trophy className="size-4" />Clubs</TabsTrigger>
          <TabsTrigger value="meetings" className="gap-1.5"><ClipboardList className="size-4" />Meetings</TabsTrigger>
          <TabsTrigger value="events" className="gap-1.5"><Medal className="size-4" />Events</TabsTrigger>
          <TabsTrigger value="profiles" className="gap-1.5"><BookUser className="size-4" />Profiles</TabsTrigger>
          <TabsTrigger value="analytics" className="gap-1.5"><BarChart3 className="size-4" />Analytics</TabsTrigger>
        </TabsList>

        {/* ── CLUB REGISTER ── */}
        <TabsContent value="register" className="space-y-4">
          {!board ? <LoadingBlock label="Loading the club register…" /> : openClubId ? (clubDetail ? (() => {
            const club = clubDetail.club;
            const activeMembers = clubDetail.members.filter((m) => m.status === "ACTIVE");
            const CatIcon = catUI(club.category).icon;
            return (
              <>
                <div className="flex flex-wrap items-center justify-between gap-3 no-print">
                  <Button size="sm" variant="outline" className="gap-1.5" onClick={() => setOpenClubId(null)}>
                    <ChevronLeft className="size-4" /> All clubs
                  </Button>
                  <div className="flex flex-wrap gap-2">
                    {canManage && (
                      <Button size="sm" variant="outline" className="gap-1.5" onClick={() => openClubDialog(club)}>
                        <Pencil className="size-4" /> Edit club
                      </Button>
                    )}
                    <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()}>
                      <Printer className="size-4" /> Print roster
                    </Button>
                  </div>
                </div>

                {/* printable club card */}
                <Card>
                  <CardContent className="p-4 sm:p-6">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                      <div className="min-w-0">
                        <CardTitle className="text-lg leading-snug flex items-center gap-2">
                          <CatIcon className="size-5 text-primary" /> {club.name}
                        </CardTitle>
                        <p className="text-xs text-muted-foreground mt-1">
                          {catUI(club.category).label}
                          {club.patronName ? ` · Patron: ${club.patronName}` : ""}
                          {club.meetingDay ? ` · Meets ${club.meetingDay}` : ""}
                          {club.venue ? ` · ${club.venue}` : ""}
                          {club.foundedOn ? ` · Founded ${fmtDateShort(club.foundedOn)}` : ""}
                        </p>
                        {club.description && <p className="text-sm mt-2 max-w-2xl">{club.description}</p>}
                      </div>
                      <div className="flex flex-col items-end gap-1.5">
                        <Badge className={cn("border-none", CLUB_STATUS_UI[club.status]?.cls ?? CLUB_STATUS_UI.ACTIVE.cls)}>{CLUB_STATUS_UI[club.status]?.label ?? club.status}</Badge>
                        <p className="text-2xl font-semibold leading-none">{activeMembers.length}</p>
                        <p className="text-[10px] uppercase tracking-wide text-muted-foreground">members</p>
                      </div>
                    </div>

                    {/* members roster table (printable) */}
                    <div className="mt-4 overflow-x-auto">
                      <table className="w-full text-sm">
                        <thead>
                          <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <th className="py-2.5 px-3">#</th>
                            <th className="py-2.5 px-3">Member</th>
                            <th className="py-2.5 px-3">Class</th>
                            <th className="py-2.5 px-3">Office</th>
                            <th className="py-2.5 px-3">Joined</th>
                            <th className="py-2.5 px-3 text-right">Attendance</th>
                            <th className="py-2.5 px-3">Guardian</th>
                            {canManage && <th className="py-2.5 px-3 no-print">Actions</th>}
                          </tr>
                        </thead>
                        <tbody>
                          {clubDetail.members.map((m, i) => (
                            <tr key={m.id} className={cn("border-b last:border-0 align-middle hover:bg-muted/40", m.status === "LEFT" && "opacity-60")}>
                              <td className="py-2.5 px-3 text-muted-foreground">{i + 1}</td>
                              <td className="py-2.5 px-3">
                                <div className="flex items-center gap-2 min-w-0">
                                  <Avatar className="size-7"><AvatarFallback className={cn("text-[10px] text-white", avatarColor(m.studentName))}>{initials(m.studentName)}</AvatarFallback></Avatar>
                                  <div className="min-w-0">
                                    <p className="truncate font-medium">{m.studentName}</p>
                                    <p className="text-[11px] text-muted-foreground">{m.nyansapoIndex}</p>
                                  </div>
                                </div>
                              </td>
                              <td className="py-2.5 px-3 whitespace-nowrap">{m.classroomName ?? "—"}</td>
                              <td className="py-2.5 px-3">
                                {m.office ? (
                                  <Badge className="border-none bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">{OFFICE_UI[m.office] ?? m.office}</Badge>
                                ) : m.status === "LEFT" ? (
                                  <span className="text-xs text-muted-foreground">left {m.leftOn ? fmtDateShort(m.leftOn) : ""}</span>
                                ) : "Member"}
                              </td>
                              <td className="py-2.5 px-3 whitespace-nowrap">{fmtDateShort(m.joinedOn)}</td>
                              <td className="py-2.5 px-3 text-right whitespace-nowrap">
                                <span className={cn("font-medium", rateTone(m.attRate))}>
                                  {m.attRate != null ? `${m.attRate}%` : "—"}
                                </span>
                                <span className="text-xs text-muted-foreground ml-1">({m.present}/{m.meetings})</span>
                              </td>
                              <td className="py-2.5 px-3 text-xs text-muted-foreground">
                                {m.guardianName ?? "—"}
                                {m.guardianPhone ? ` · ${m.guardianPhone}` : ""}
                              </td>
                              {canManage && (
                                <td className="py-2.5 px-3 no-print">
                                  <div className="flex flex-wrap gap-1.5">
                                    <Button size="sm" variant="outline" className="h-7 px-2 gap-1" onClick={() => { setOfficeRow(m); setNewOffice(m.office ?? "none"); }}>
                                      <Crown className="size-3.5" /> Office
                                    </Button>
                                    <Button size="sm" variant="outline" className="h-7 px-2 gap-1" onClick={() => { setLeaveRow(m); setLeaveReason(""); }}>
                                      {m.status === "ACTIVE" ? <><LogOut className="size-3.5" /> Leave</> : <><RotateCcw className="size-3.5" /> Re-join</>}
                                    </Button>
                                  </div>
                                </td>
                              )}
                            </tr>
                          ))}
                          {!clubDetail.members.length && (
                            <tr><td colSpan={canManage ? 8 : 7} className="py-6 text-center text-muted-foreground">No members yet — enrol learners from the roster.</td></tr>
                          )}
                        </tbody>
                      </table>
                    </div>

                    {canManage && (
                      <div className="mt-4 flex flex-wrap gap-2 no-print">
                        <Button size="sm" className="gap-1.5" onClick={() => { setMemberOpen(true); setMClass(""); setMStudent(""); setMOffice("none"); }}>
                          <UserPlus className="size-4" /> Enrol member
                        </Button>
                        <Button size="sm" variant="outline" className="gap-1.5" onClick={() => { setCreateMeetingOpen(true); setMtClub(club.id); }}>
                          <CalendarDays className="size-4" /> Schedule meeting
                        </Button>
                      </div>
                    )}
                  </CardContent>
                </Card>

                {/* meetings + events side by side */}
                <div className="grid lg:grid-cols-2 gap-4 no-print">
                  <Card className="no-print">
                    <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><ClipboardList className="size-4 text-primary" />Meeting register</CardTitle></CardHeader>
                    <CardContent className="space-y-1.5 max-h-96 overflow-y-auto">
                      {clubDetail.meetings.length ? clubDetail.meetings.map((m) => (
                        <button key={m.id} className="w-full flex flex-wrap items-center justify-between gap-2 text-sm rounded-md border p-2 text-left hover:bg-muted/40"
                          onClick={() => { setTab("meetings"); setOpenMeetingId(m.id); }}>
                          <span className="min-w-0">
                            <span className="font-medium">{fmtDateShort(m.date)}</span>
                            <span className="text-muted-foreground"> · {m.topic ?? "general meeting"}</span>
                          </span>
                          <span className={cn("text-xs font-medium", rateTone(m.rate))}>{m.present}/{m.expected} · {m.rate ?? 0}%</span>
                        </button>
                      )) : <EmptyState title="No meetings recorded" hint="Schedule the first meeting." />}
                    </CardContent>
                  </Card>
                  <Card className="no-print">
                    <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><Medal className="size-4 text-primary" />Events & competitions</CardTitle></CardHeader>
                    <CardContent className="space-y-1.5 max-h-96 overflow-y-auto">
                      {clubDetail.events.length ? clubDetail.events.map((ev) => (
                        <div key={ev.id} className="flex flex-wrap items-center justify-between gap-2 text-sm rounded-md border p-2">
                          <span className="min-w-0">
                            <span className="font-medium">{ev.title}</span>
                            <span className="text-muted-foreground"> · {fmtDateShort(ev.date)}</span>
                          </span>
                          <span className="flex items-center gap-1.5 flex-wrap">
                            <Badge className={cn("border-none", (LEVEL_UI[ev.level] ?? LEVEL_UI.SCHOOL).cls)}>{(LEVEL_UI[ev.level] ?? LEVEL_UI.SCHOOL).label}</Badge>
                            {ev.placement && <Badge className="border-none bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">{ev.placement}</Badge>}
                          </span>
                        </div>
                      )) : <EmptyState title="No events yet" />}
                    </CardContent>
                  </Card>
                </div>
              </>
            );
          })() : <LoadingBlock label="Loading the club…" />) : (
            <>
              {/* stat row */}
              <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <StatCard icon={<Trophy className="size-4" />} label="Active clubs" value={board.stat.activeClubs} sub={`${board.stat.clubs} registered`} />
                <StatCard icon={<Users className="size-4" />} label="Members" value={board.stat.members} sub="active enrolment" />
                <StatCard icon={<Crown className="size-4" />} label="Executive officers" value={board.stat.officers} sub="elected / appointed" tone="gold" />
                <StatCard icon={<ClipboardList className="size-4" />} label="Meetings · 90 days" value={board.stat.meetings90d} sub="club sessions" />
                <StatCard icon={<CircleCheck className="size-4" />} label="Attendance rate" value={board.stat.attRate90d != null ? `${board.stat.attRate90d}%` : "—"} sub="present + excused" tone={board.stat.attRate90d != null && board.stat.attRate90d < 70 ? "danger" : "primary"} />
                <StatCard icon={<Medal className="size-4" />} label="Upcoming events" value={board.stat.upcoming} sub={`${board.stat.events} all-time`} />
              </div>

              {/* filters */}
              <Card className="no-print">
                <CardContent className="p-3 sm:p-4 flex flex-wrap items-end gap-2">
                  <div className="w-full sm:w-44">
                    <Label className="text-xs">Category</Label>
                    <Select value={fCategory} onValueChange={setFCategory}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All categories</SelectItem>
                        {Object.entries(CATEGORY_UI).map(([k, v]) => <SelectItem key={k} value={k}>{v.label}</SelectItem>)}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="w-full sm:w-40">
                    <Label className="text-xs">Status</Label>
                    <Select value={fStatus} onValueChange={setFStatus}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All statuses</SelectItem>
                        <SelectItem value="ACTIVE">Active</SelectItem>
                        <SelectItem value="DORMANT">Dormant</SelectItem>
                        <SelectItem value="ARCHIVED">Archived</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="flex-1 min-w-40">
                    <Label className="text-xs">Search</Label>
                    <div className="relative">
                      <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                      <Input value={clubSearch} onChange={(e) => setClubSearch(e.target.value)} placeholder="Club or patron…" className="pl-8" />
                    </div>
                  </div>
                </CardContent>
              </Card>

              {/* club cards */}
              {clubCards.length ? (
                <div className="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                  {clubCards.map((c) => {
                    const CIcon = catUI(c.category).icon;
                    return (
                      <Card key={c.id} className="hover:shadow-md transition-shadow">
                        <CardContent className="p-4">
                          <div className="flex items-start justify-between gap-2">
                            <div className="min-w-0">
                              <p className="font-medium leading-snug flex items-center gap-1.5">
                                <CIcon className="size-4 text-primary shrink-0" /> {c.name}
                              </p>
                              <p className="text-xs text-muted-foreground mt-1 line-clamp-2">
                                {c.patronName ? `Patron: ${c.patronName} · ` : ""}
                                {c.meetingDay ? `${c.meetingDay}s` : "no fixed day"}
                                {c.venue ? ` · ${c.venue}` : ""}
                              </p>
                            </div>
                            <Badge className={cn("border-none shrink-0", catUI(c.category).cls)}>{catUI(c.category).label}</Badge>
                          </div>
                          <div className="grid grid-cols-4 gap-2 mt-3 text-center">
                            <div className="rounded-md bg-muted/60 p-1.5">
                              <p className="text-base font-semibold leading-none">{c.members}</p>
                              <p className="text-[9px] uppercase tracking-wide text-muted-foreground mt-0.5">members</p>
                            </div>
                            <div className="rounded-md bg-muted/60 p-1.5">
                              <p className="text-base font-semibold leading-none">{c.meetings}</p>
                              <p className="text-[9px] uppercase tracking-wide text-muted-foreground mt-0.5">meetings</p>
                            </div>
                            <div className="rounded-md bg-muted/60 p-1.5">
                              <p className="text-base font-semibold leading-none">{c.events}</p>
                              <p className="text-[9px] uppercase tracking-wide text-muted-foreground mt-0.5">events</p>
                            </div>
                            <div className="rounded-md bg-muted/60 p-1.5">
                              <p className="text-base font-semibold leading-none">{c.lastMetAt ? fmtDateShort(c.lastMetAt) : "—"}</p>
                              <p className="text-[9px] uppercase tracking-wide text-muted-foreground mt-0.5">last met</p>
                            </div>
                          </div>
                          {c.status !== "ACTIVE" && (
                            <Badge className={cn("border-none mt-2", CLUB_STATUS_UI[c.status]?.cls)}>{CLUB_STATUS_UI[c.status]?.label ?? c.status}</Badge>
                          )}
                          <Button size="sm" variant="outline" className="w-full mt-3 gap-1.5" onClick={() => setOpenClubId(c.id)}>
                            Open club <ChevronRight className="size-4" />
                          </Button>
                        </CardContent>
                      </Card>
                    );
                  })}
                </div>
              ) : (
                <Card><CardContent>
                  <EmptyState icon={<Trophy className="size-6" />} title={board.clubs.length ? "No clubs match the filters" : "No clubs registered yet"} hint={board.clubs.length ? "Loosen the category / status filters." : canManage ? "Register the first club — Science Club, Cadet Corps, Scripture Union…" : "Ask the administrator to register clubs."} />
                </CardContent></Card>
              )}
            </>
          )}
        </TabsContent>

        {/* ── MEETINGS REGISTER ── */}
        <TabsContent value="meetings" className="space-y-4">
          {!board ? <LoadingBlock label="Loading meetings…" /> : openMeetingId && meetingDetail ? (
            <>
              <div className="flex flex-wrap items-center justify-between gap-3 no-print">
                <Button size="sm" variant="outline" className="gap-1.5" onClick={() => setOpenMeetingId(null)}>
                  <ChevronLeft className="size-4" /> All meetings
                </Button>
                <div className="flex flex-wrap gap-2">
                  {canManage && (
                    <>
                      <Button size="sm" variant="outline" className="gap-1.5" onClick={() => reloadMeeting()}>
                        <RotateCcw className="size-4" /> Reset sheet
                      </Button>
                      <Button size="sm" className="gap-1.5" onClick={submitAttendance}>
                        <CircleCheck className="size-4" /> Save register
                      </Button>
                    </>
                  )}
                  <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()}>
                    <Printer className="size-4" /> Print register
                  </Button>
                </div>
              </div>

              {/* printable attendance sheet */}
              <Card>
                <CardContent className="p-4 sm:p-6">
                  <div className="flex flex-wrap items-start justify-between gap-2">
                    <div>
                      <CardTitle className="text-lg">{meetingDetail.meeting.clubName} — meeting register</CardTitle>
                      <p className="text-xs text-muted-foreground mt-1">
                        {fmtDateShort(meetingDetail.meeting.date)}
                        {meetingDetail.meeting.topic ? ` · ${meetingDetail.meeting.topic}` : ""}
                        {meetingDetail.meeting.takenByName ? ` · taken by ${meetingDetail.meeting.takenByName}` : ""}
                      </p>
                    </div>
                    <div className="text-right">
                      <p className="text-2xl font-semibold leading-none">
                        {meetingDetail.rows.filter((r) => (sheet[r.membershipId] ?? "ABSENT") !== "ABSENT").length}/{meetingDetail.rows.length}
                      </p>
                      <p className="text-[10px] uppercase tracking-wide text-muted-foreground">present / expected</p>
                    </div>
                  </div>

                  <div className="mt-4 overflow-x-auto">
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                          <th className="py-2.5 px-3">#</th>
                          <th className="py-2.5 px-3">Member</th>
                          <th className="py-2.5 px-3">Class</th>
                          <th className="py-2.5 px-3">Office</th>
                          <th className="py-2.5 px-3 text-center no-print">Attendance</th>
                          <th className="py-2.5 px-3 text-center">Mark</th>
                          <th className="py-2.5 px-3">Note</th>
                        </tr>
                      </thead>
                      <tbody>
                        {meetingDetail.rows.map((row, i) => {
                          const st = sheet[row.membershipId] ?? "ABSENT";
                          return (
                            <tr key={row.membershipId} className="border-b last:border-0 align-middle hover:bg-muted/40">
                              <td className="py-2.5 px-3 text-muted-foreground">{i + 1}</td>
                              <td className="py-2.5 px-3">
                                <div className="flex items-center gap-2 min-w-0">
                                  <Avatar className="size-7"><AvatarFallback className={cn("text-[10px] text-white", avatarColor(row.member.studentName))}>{initials(row.member.studentName)}</AvatarFallback></Avatar>
                                  <span className="truncate font-medium">{row.member.studentName}</span>
                                </div>
                              </td>
                              <td className="py-2.5 px-3 whitespace-nowrap">{row.member.classroomName ?? "—"}</td>
                              <td className="py-2.5 px-3">{row.member.office ? <Badge className="border-none bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">{OFFICE_UI[row.member.office] ?? row.member.office}</Badge> : "—"}</td>
                              <td className="py-2.5 px-3 text-center no-print">
                                <div className="inline-flex rounded-lg overflow-hidden border">
                                  {(["PRESENT", "ABSENT", "EXCUSED"] as const).map((k) => (
                                    <button key={k}
                                      className={cn("px-2.5 py-1 text-xs font-medium transition-colors", st === k ? ATT_UI[k].btn : "bg-background hover:bg-muted")}
                                      disabled={!canManage}
                                      onClick={() => setSheet((s) => ({ ...s, [row.membershipId]: k }))}
                                    >
                                      {ATT_UI[k].label[0]}
                                    </button>
                                  ))}
                                </div>
                              </td>
                              <td className="py-2.5 px-3 text-center">
                                <Badge className={cn("border-none", st === "PRESENT" ? "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300" : st === "EXCUSED" ? "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300" : "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300")}>
                                  {ATT_UI[st]?.label ?? st}
                                </Badge>
                              </td>
                              <td className="py-2.5 px-3 text-xs text-muted-foreground">{row.note ?? ""}</td>
                            </tr>
                          );
                        })}
                        {!meetingDetail.rows.length && (
                          <tr><td colSpan={7} className="py-6 text-center text-muted-foreground">No members on the roster — enrol members first.</td></tr>
                        )}
                      </tbody>
                    </table>
                  </div>
                </CardContent>
              </Card>
            </>
          ) : (
            <>
              {/* recent meetings table */}
              <Card className="no-print">
                <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><ClipboardList className="size-4 text-primary" />Meeting register</CardTitle></CardHeader>
                <CardContent className="p-3 sm:p-4 space-y-3">
                  <div className="flex flex-wrap items-end gap-2">
                    <div className="w-full sm:w-52">
                      <Label className="text-xs">Club</Label>
                      <Select value={fClub} onValueChange={setFClub}>
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                          <SelectItem value="all">All clubs</SelectItem>
                          {board.clubs.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="flex-1 min-w-40">
                      <Label className="text-xs">Search</Label>
                      <div className="relative">
                        <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                        <Input value={mSearch} onChange={(e) => setMSearch(e.target.value)} placeholder="Club or topic…" className="pl-8" />
                      </div>
                    </div>
                    {canManage && (
                      <Button size="sm" className="gap-1.5" onClick={() => { setCreateMeetingOpen(true); setMtClub(board.clubs[0]?.id ?? ""); setMtDate(null); setMtTopic(""); }}>
                        <Plus className="size-4" /> Schedule meeting
                      </Button>
                    )}
                  </div>
                  {meetings === null ? <LoadingBlock label="Loading meetings…" /> : meetingRows.length ? (
                    <div className="overflow-x-auto">
                      <table className="w-full text-sm">
                        <thead>
                          <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <th className="py-2.5 px-3">Date</th>
                            <th className="py-2.5 px-3">Club</th>
                            <th className="py-2.5 px-3">Topic</th>
                            <th className="py-2.5 px-3 text-center">Present</th>
                            <th className="py-2.5 px-3 text-center">Rate</th>
                            <th className="py-2.5 px-3">Guardians</th>
                            <th className="py-2.5 px-3 no-print">Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          {meetingRows.map((m) => (
                            <tr key={m.id} className="border-b last:border-0 align-middle hover:bg-muted/40">
                              <td className="py-2.5 px-3 whitespace-nowrap">{fmtDateShort(m.date)}</td>
                              <td className="py-2.5 px-3 font-medium">{m.clubName ?? "—"}</td>
                              <td className="py-2.5 px-3 max-w-xs"><span className="line-clamp-1">{m.topic ?? "general meeting"}</span></td>
                              <td className="py-2.5 px-3 text-center whitespace-nowrap">{m.present}/{m.expected}</td>
                              <td className={cn("py-2.5 px-3 text-center font-medium", rateTone(m.rate))}>{m.rate != null ? `${m.rate}%` : "—"}</td>
                              <td className="py-2.5 px-3 text-xs">
                                {m.guardiansNotifiedAt
                                  ? <span className="text-emerald-600 dark:text-emerald-400 flex items-center gap-1"><BellRing className="size-3.5" />{fmtDateShort(m.guardiansNotifiedAt)}</span>
                                  : <span className="text-muted-foreground">—</span>}
                              </td>
                              <td className="py-2.5 px-3 no-print">
                                <div className="flex flex-wrap gap-1.5">
                                  <Button size="sm" variant="outline" className="h-7 px-2 gap-1" onClick={() => setOpenMeetingId(m.id)}>
                                    <ClipboardList className="size-3.5" /> {m.rate != null ? "Register" : "Take"}
                                  </Button>
                                  {canManage && (
                                    <>
                                      <Button size="sm" variant="outline" className="h-7 px-2 gap-1" onClick={() => { setMinutesRow(m); setMinutesText(m.minutes ?? ""); }}>
                                        <Pencil className="size-3.5" /> Minutes
                                      </Button>
                                      <Button size="sm" variant="outline" className="h-7 px-2 gap-1" onClick={() => { setNotifyRow(m); setNotifyMsg(""); }}>
                                        <Megaphone className="size-3.5" /> Notify
                                      </Button>
                                    </>
                                  )}
                                </div>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  ) : <EmptyState title="No meetings recorded yet" hint="Schedule a meeting — the attendance register is seeded for the roster." />}
                  <p className="text-xs text-muted-foreground">
                    {meetings !== null && `${meetingRows.length} meeting${meetingRows.length === 1 ? "" : "s"} in the register — open a club for its full history.`}
                  </p>
                </CardContent>
              </Card>
            </>
          )}
        </TabsContent>

        {/* ── EVENTS & COMPETITIONS ── */}
        <TabsContent value="events" className="space-y-4">
          {!board ? <LoadingBlock label="Loading events…" /> : (
            <>
              <div className="flex items-center justify-between gap-2 flex-wrap no-print">
                <p className="text-sm text-muted-foreground">Inter-schools & school competitions, festivals and parades — with outcomes and placements.</p>
                {canManage && (
                  <Button size="sm" className="gap-1.5" onClick={() => openEventDialog(null)}>
                    <Plus className="size-4" /> Record event
                  </Button>
                )}
              </div>
              <Card>
                <CardContent className="p-0">
                  {events === null ? <div className="p-4"><LoadingBlock label="Loading events…" /></div> : eventRows.length ? (
                    <div className="overflow-x-auto">
                      <table className="w-full text-sm">
                        <thead>
                          <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <th className="py-2.5 px-3">Date</th>
                            <th className="py-2.5 px-3">Event</th>
                            <th className="py-2.5 px-3">Club</th>
                            <th className="py-2.5 px-3">Level</th>
                            <th className="py-2.5 px-3">Outcome</th>
                            <th className="py-2.5 px-3">Represented by</th>
                            <th className="py-2.5 px-3 no-print">Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          {eventRows.map((ev) => (
                            <tr key={ev.id} className="border-b last:border-0 align-middle hover:bg-muted/40">
                              <td className="py-2.5 px-3 whitespace-nowrap">{fmtDateShort(ev.date)}</td>
                              <td className="py-2.5 px-3 font-medium max-w-xs"><span className="line-clamp-1">{ev.title}</span></td>
                              <td className="py-2.5 px-3 whitespace-nowrap">{ev.clubName ?? "School-wide"}</td>
                              <td className="py-2.5 px-3"><Badge className={cn("border-none", (LEVEL_UI[ev.level] ?? LEVEL_UI.SCHOOL).cls)}>{(LEVEL_UI[ev.level] ?? LEVEL_UI.SCHOOL).label}</Badge></td>
                              <td className="py-2.5 px-3">
                                {ev.outcome === "COMPLETED"
                                  ? (ev.placement ? <Badge className="border-none bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">{ev.placement}</Badge> : <span className="text-xs text-muted-foreground">Completed</span>)
                                  : <Badge className="border-none bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">Upcoming</Badge>}
                              </td>
                              <td className="py-2.5 px-3 text-xs text-muted-foreground max-w-xs"><span className="line-clamp-1">{ev.participants ?? "—"}</span></td>
                              <td className="py-2.5 px-3 no-print">
                                {canManage && (
                                  <Button size="sm" variant="outline" className="h-7 px-2 gap-1" onClick={() => openEventDialog(ev)}>
                                    <Pencil className="size-3.5" /> Edit
                                  </Button>
                                )}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  ) : (
                    <div className="p-6"><EmptyState icon={<Medal className="size-6" />} title="No events recorded" hint="Quiz competitions, cultural festivals, sports, cadet parades…" /></div>
                  )}
                </CardContent>
              </Card>
            </>
          )}
        </TabsContent>

        {/* ── LEARNER CO-CURRICULAR PROFILES ── */}
        <TabsContent value="profiles" className="space-y-4">
          <Card className="no-print">
            <CardContent className="p-3 sm:p-4">
              <FormGrid cols={2}>
                <FormField label="Class">
                  <Select value={pClass} onValueChange={setPClass}>
                    <SelectTrigger><SelectValue placeholder="Choose a class…" /></SelectTrigger>
                    <SelectContent>
                      {classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
                    </SelectContent>
                  </Select>
                </FormField>
                <FormField label="Learner">
                  <Select value={pStudent} onValueChange={setPStudent} disabled={!pClass}>
                    <SelectTrigger><SelectValue placeholder={pClass ? "Choose a learner…" : "Pick the class first"} /></SelectTrigger>
                    <SelectContent>
                      {pRoster.map((r) => <SelectItem key={r.id} value={r.id}>{r.name}</SelectItem>)}
                    </SelectContent>
                  </Select>
                </FormField>
              </FormGrid>
            </CardContent>
          </Card>

          {!pStudent ? (
            <Card><CardContent>
              <EmptyState icon={<BookUser className="size-6" />} title="Co-curricular profiles" hint="Choose a class, then a learner — clubs, offices, attendance, events and the printable co-curricular report appear here." />
            </CardContent></Card>
          ) : !profile ? (
            <LoadingBlock label="Loading the co-curricular profile…" />
          ) : (
            <>
              <Card>
                <CardContent className="p-4 sm:p-6">
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
                    <Button size="sm" variant="outline" className="gap-1.5 no-print" onClick={() => window.print()}>
                      <Printer className="size-4" /> Print co-curricular report
                    </Button>
                  </div>

                  {/* summary strip */}
                  <div className="grid grid-cols-3 sm:grid-cols-6 gap-2 mt-4 text-center">
                    {[
                      { label: "Clubs", value: profile.summary.active },
                      { label: "Offices", value: profile.summary.offices },
                      { label: "Meetings", value: profile.summary.meetings },
                      { label: "Attendance", value: profile.summary.attRate != null ? `${profile.summary.attRate}%` : "—" },
                      { label: "Represented", value: profile.summary.represented },
                      { label: "All-time clubs", value: profile.summary.clubs },
                    ].map((s) => (
                      <div key={s.label} className="rounded-md bg-muted/60 p-2">
                        <p className="text-lg font-semibold leading-none">{s.value}</p>
                        <p className="text-[10px] uppercase tracking-wide text-muted-foreground mt-1">{s.label}</p>
                      </div>
                    ))}
                  </div>
                  <p className="text-xs text-muted-foreground mt-2">Guardian: {profile.student.guardianName ?? "—"}{profile.student.guardianPhone ? ` · ${profile.student.guardianPhone}` : ""}</p>
                </CardContent>
              </Card>

              {/* clubs & offices */}
              <Card>
                <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><Trophy className="size-4 text-primary" />Clubs & offices</CardTitle></CardHeader>
                <CardContent className="p-0">
                  {profile.memberships.length ? (
                    <div className="overflow-x-auto">
                      <table className="w-full text-sm">
                        <thead>
                          <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <th className="py-2.5 px-3">Club</th>
                            <th className="py-2.5 px-3">Category</th>
                            <th className="py-2.5 px-3">Office</th>
                            <th className="py-2.5 px-3">Joined</th>
                            <th className="py-2.5 px-3 text-center">Attendance</th>
                            <th className="py-2.5 px-3">Patron</th>
                          </tr>
                        </thead>
                        <tbody>
                          {profile.memberships.map((m) => (
                            <tr key={m.id} className={cn("border-b last:border-0 align-middle hover:bg-muted/40", m.status === "LEFT" && "opacity-60")}>
                              <td className="py-2.5 px-3 font-medium">{m.clubName}{m.status === "LEFT" && <span className="text-xs text-muted-foreground"> (left {m.leftOn ? fmtDateShort(m.leftOn) : ""})</span>}</td>
                              <td className="py-2.5 px-3"><Badge className={cn("border-none", catUI(m.clubCategory).cls)}>{catUI(m.clubCategory).label}</Badge></td>
                              <td className="py-2.5 px-3">{m.office ? <Badge className="border-none bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">{OFFICE_UI[m.office] ?? m.office}</Badge> : "Member"}</td>
                              <td className="py-2.5 px-3 whitespace-nowrap">{fmtDateShort(m.joinedOn)}</td>
                              <td className={cn("py-2.5 px-3 text-center font-medium", rateTone(m.rate))}>
                                {m.rate != null ? `${m.rate}%` : "—"}
                                <span className="text-xs text-muted-foreground ml-1">({m.present}/{m.meetings})</span>
                              </td>
                              <td className="py-2.5 px-3 text-xs text-muted-foreground">{m.patronName ?? "—"}</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  ) : <div className="p-6"><EmptyState title="Not yet a member of any club" hint="Enrol this learner from a club's roster." /></div>}
                </CardContent>
              </Card>

              {/* club events timeline */}
              {profile.events.length > 0 && (
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><Medal className="size-4 text-primary" />Club events during membership</CardTitle></CardHeader>
                  <CardContent className="p-0">
                    <div className="overflow-x-auto">
                      <table className="w-full text-sm">
                        <thead>
                          <tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <th className="py-2.5 px-3">Date</th>
                            <th className="py-2.5 px-3">Event</th>
                            <th className="py-2.5 px-3">Club</th>
                            <th className="py-2.5 px-3">Level</th>
                            <th className="py-2.5 px-3">Outcome</th>
                          </tr>
                        </thead>
                        <tbody>
                          {profile.events.map((ev) => (
                            <tr key={ev.id} className="border-b last:border-0 align-middle hover:bg-muted/40">
                              <td className="py-2.5 px-3 whitespace-nowrap">{fmtDateShort(ev.date)}</td>
                              <td className="py-2.5 px-3">{ev.title}</td>
                              <td className="py-2.5 px-3">{ev.clubName ?? "—"}</td>
                              <td className="py-2.5 px-3"><Badge className={cn("border-none", (LEVEL_UI[ev.level] ?? LEVEL_UI.SCHOOL).cls)}>{(LEVEL_UI[ev.level] ?? LEVEL_UI.SCHOOL).label}</Badge></td>
                              <td className="py-2.5 px-3">
                                {ev.outcome === "COMPLETED"
                                  ? (ev.placement ? <Badge className="border-none bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">{ev.placement}</Badge> : <span className="text-xs text-muted-foreground">Completed</span>)
                                  : <Badge className="border-none bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">Upcoming</Badge>}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </CardContent>
                </Card>
              )}
            </>
          )}
        </TabsContent>

        {/* ── PARTICIPATION ANALYTICS ── */}
        <TabsContent value="analytics" className="space-y-4">
          {!analytics ? <LoadingBlock label="Loading participation analytics…" /> : (
            <>
              <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                <StatCard icon={<Trophy className="size-4" />} label="Active clubs" value={analytics.totals.activeClubs} sub={`${analytics.totals.clubs} registered`} />
                <StatCard icon={<Users className="size-4" />} label="Members" value={analytics.totals.members} sub={`since ${analytics.window.since}`} />
                <StatCard icon={<Crown className="size-4" />} label="Officers" value={analytics.totals.offices} tone="gold" sub="elected / appointed" />
                <StatCard icon={<ClipboardList className="size-4" />} label="Meetings · 90 days" value={analytics.totals.meetings90d} sub={analytics.totals.attRate90d != null ? `${analytics.totals.attRate90d}% attendance` : ""} />
                <StatCard icon={<Medal className="size-4" />} label="Events" value={analytics.totals.events} sub={`${analytics.totals.upcoming} upcoming`} />
              </div>

              {/* weekly trend */}
              <Card>
                <CardHeader className="pb-2"><CardTitle className="text-sm">Club meetings & attendance — last 8 weeks</CardTitle></CardHeader>
                <CardContent>
                  <div className="h-56">
                    <ResponsiveContainer width="100%" height="100%">
                      <AreaChart data={analytics.weeks} margin={{ top: 4, right: 8, bottom: 0, left: -18 }}>
                        <defs>
                          <linearGradient id="clubMeetings" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="5%" stopColor="#0d9488" stopOpacity={0.35} />
                            <stop offset="95%" stopColor="#0d9488" stopOpacity={0.02} />
                          </linearGradient>
                        </defs>
                        <XAxis dataKey="week" tick={{ fontSize: 11 }} />
                        <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                        <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8 }} />
                        <Area type="monotone" dataKey="meetings" name="Meetings" stroke="#0d9488" strokeWidth={2} fill="url(#clubMeetings)" />
                        <Area type="monotone" dataKey="rate" name="Attendance %" stroke="#ea580c" strokeWidth={1.5} fillOpacity={0} />
                      </AreaChart>
                    </ResponsiveContainer>
                  </div>
                </CardContent>
              </Card>

              <div className="grid lg:grid-cols-2 gap-4">
                {/* category mix */}
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm">Category mix</CardTitle></CardHeader>
                  <CardContent>
                    {analytics.categoryMix.length ? (
                      <div className="space-y-2">
                        {analytics.categoryMix.map((c) => (
                          <div key={c.name} className="flex items-center gap-2">
                            <Badge className={cn("border-none shrink-0", catUI(c.name).cls)}>{catUI(c.name).label}</Badge>
                            <div className="flex-1 min-w-0">
                              <div className="flex items-baseline justify-between gap-2">
                                <p className="text-sm">{c.clubs} clubs</p>
                                <p className="text-xs text-muted-foreground shrink-0">{c.members} members</p>
                              </div>
                              <div className="h-1.5 rounded-full bg-muted mt-1 overflow-hidden">
                                <div className="h-full rounded-full bg-teal-600" style={{ width: `${(c.members / Math.max(1, analytics.categoryMix[0].members)) * 100}%` }} />
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    ) : <EmptyState title="No clubs yet" />}
                  </CardContent>
                </Card>

                {/* club ranking */}
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm">Membership by club</CardTitle></CardHeader>
                  <CardContent>
                    {analytics.clubRanking.length ? (
                      <div className="h-64">
                        <ResponsiveContainer width="100%" height="100%">
                          <BarChart data={analytics.clubRanking.map((c) => ({ ...c, label: c.name.length > 18 ? c.name.slice(0, 16) + "…" : c.name }))} layout="vertical" margin={{ top: 0, right: 12, bottom: 0, left: 8 }}>
                            <XAxis type="number" allowDecimals={false} tick={{ fontSize: 11 }} />
                            <YAxis type="category" dataKey="label" width={130} tick={{ fontSize: 10 }} />
                            <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8 }} />
                            <Bar dataKey="members" name="Members" fill="#0d9488" radius={[0, 4, 4, 0]} />
                          </BarChart>
                        </ResponsiveContainer>
                      </div>
                    ) : <EmptyState title="No clubs yet" />}
                  </CardContent>
                </Card>
              </div>

              <div className="grid lg:grid-cols-2 gap-4">
                {/* class participation */}
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm">Class participation</CardTitle></CardHeader>
                  <CardContent>
                    {analytics.classParticipation.length ? (
                      <div className="h-64">
                        <ResponsiveContainer width="100%" height="100%">
                          <BarChart data={analytics.classParticipation.map((c) => ({ ...c, label: c.name.length > 14 ? c.name.slice(0, 12) + "…" : c.name }))} margin={{ top: 4, right: 8, bottom: 0, left: -18 }}>
                            <XAxis dataKey="label" tick={{ fontSize: 10 }} interval={0} angle={-20} textAnchor="end" height={54} />
                            <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                            <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8 }} />
                            <Bar dataKey="pct" name="Members (%)" fill="#d97706" radius={[4, 4, 0, 0]} />
                          </BarChart>
                        </ResponsiveContainer>
                      </div>
                    ) : <EmptyState title="No members yet" />}
                  </CardContent>
                </Card>

                {/* office holders + upcoming events */}
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="text-sm">Executive officers & upcoming events</CardTitle></CardHeader>
                  <CardContent className="space-y-3">
                    {analytics.officeHolders.length ? (
                      <div className="space-y-1.5 max-h-40 overflow-y-auto">
                        {analytics.officeHolders.map((o, i) => (
                          <div key={`${o.office}-${i}`} className="flex items-center justify-between gap-2 text-sm rounded-md border p-2">
                            <span className="font-medium truncate">{o.name}</span>
                            <Badge className="border-none bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 shrink-0">{OFFICE_UI[o.office] ?? o.office}</Badge>
                          </div>
                        ))}
                      </div>
                    ) : <p className="text-sm text-muted-foreground">No offices assigned yet.</p>}
                    {analytics.upcomingEvents.length > 0 && (
                      <div className="space-y-1.5 max-h-40 overflow-y-auto">
                        {analytics.upcomingEvents.slice(0, 6).map((ev) => (
                          <div key={ev.id} className="flex items-center justify-between gap-2 text-sm rounded-md border p-2">
                            <span className="truncate"><span className="font-medium">{ev.title}</span> <span className="text-xs text-muted-foreground">· {ev.clubName ?? "school-wide"}</span></span>
                            <span className="text-xs text-muted-foreground shrink-0">{fmtDateShort(ev.date)}</span>
                          </div>
                        ))}
                      </div>
                    )}
                  </CardContent>
                </Card>
              </div>
            </>
          )}
        </TabsContent>
      </Tabs>

      {/* ── club create / edit dialog ── */}
      <Dialog open={clubOpen} onOpenChange={setClubOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>{editClub ? `Edit ${editClub.name}` : "Register a club"}</DialogTitle>
            <DialogDescription>
              {editClub ? "Update the club's details, patron, meeting day or status." : "Clubs carry a patron, a weekly meeting day and venue — memberships come next."}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <FormField label="Club name" required>
              <Input value={cName} onChange={(e) => setCName(e.target.value)} placeholder="e.g. Science & Math Club" />
            </FormField>
            <FormGrid cols={2}>
              <FormField label="Category">
                <Select value={cCategory} onValueChange={setCCategory}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    {Object.entries(CATEGORY_UI).map(([k, v]) => <SelectItem key={k} value={k}>{v.label}</SelectItem>)}
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Status">
                <Select value={cStatus} onValueChange={setCStatus}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="ACTIVE">Active</SelectItem>
                    <SelectItem value="DORMANT">Dormant</SelectItem>
                    <SelectItem value="ARCHIVED">Archived</SelectItem>
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Patron">
                <Input value={cPatron} onChange={(e) => setCPatron(e.target.value)} placeholder="e.g. Mr. Kwabena Osei" />
              </FormField>
              <FormField label="Meeting day">
                <Select value={cDay} onValueChange={setCDay}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">No fixed day</SelectItem>
                    {(catalogues?.weekdays ?? ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]).map((d) => <SelectItem key={d} value={d}>{d}</SelectItem>)}
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Venue">
                <Input value={cVenue} onChange={(e) => setCVenue(e.target.value)} placeholder="e.g. Science Lab 1" />
              </FormField>
              <FormField label="Founded on">
                <DateField value={cFounded} onChange={setCFounded} />
              </FormField>
            </FormGrid>
            <FormField label="Description" hint="What the club does — appears on the printable roster.">
              <Textarea rows={2} value={cDescription} onChange={(e) => setCDescription(e.target.value)} />
            </FormField>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setClubOpen(false)}>Cancel</Button>
            <Button onClick={submitClub}>{editClub ? "Save changes" : "Register club"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── member add dialog ── */}
      <Dialog open={memberOpen} onOpenChange={setMemberOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Enrol a member</DialogTitle>
            <DialogDescription>Pick the learner's class, then the learner. Optionally assign an executive office — one holder per office.</DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <FormField label="Class" required>
              <Select value={mClass} onValueChange={setMClass}>
                <SelectTrigger><SelectValue placeholder="Choose a class…" /></SelectTrigger>
                <SelectContent>
                  {classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="Learner" required>
              <Select value={mStudent} onValueChange={setMStudent} disabled={!mClass}>
                <SelectTrigger><SelectValue placeholder={mClass ? "Choose a learner…" : "Pick the class first"} /></SelectTrigger>
                <SelectContent>
                  {mRoster.map((r) => <SelectItem key={r.id} value={r.id}>{r.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="Executive office">
              <Select value={mOffice} onValueChange={setMOffice}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">Member (no office)</SelectItem>
                  {Object.entries(OFFICE_UI).map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}
                </SelectContent>
              </Select>
            </FormField>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setMemberOpen(false)}>Cancel</Button>
            <Button onClick={submitMember} disabled={!mStudent}>Enrol member</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── office transfer dialog ── */}
      <Dialog open={!!officeRow} onOpenChange={(o) => !o && setOfficeRow(null)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Assign an office</DialogTitle>
            <DialogDescription>
              {officeRow?.studentName} — assigning an office held by another member transfers it to them.
            </DialogDescription>
          </DialogHeader>
          <FormField label="Office">
            <Select value={newOffice} onValueChange={setNewOffice}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">Member (no office)</SelectItem>
                {Object.entries(OFFICE_UI).map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}
              </SelectContent>
            </Select>
          </FormField>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOfficeRow(null)}>Cancel</Button>
            <Button onClick={submitOffice}>Save office</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── leave / rejoin dialog ── */}
      <Dialog open={!!leaveRow} onOpenChange={(o) => !o && setLeaveRow(null)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>{leaveRow?.status === "ACTIVE" ? "End membership" : "Re-join the club"}</DialogTitle>
            <DialogDescription>
              {leaveRow?.studentName} · {leaveRow?.classroomName ?? "—"} · joined {leaveRow ? fmtDateShort(leaveRow.joinedOn) : ""}
            </DialogDescription>
          </DialogHeader>
          {leaveRow?.status === "ACTIVE" && (
            <FormField label="Reason for leaving" hint="Optional — recorded on the roster trail.">
              <Input value={leaveReason} onChange={(e) => setLeaveReason(e.target.value)} placeholder="e.g. transferred to another school" />
            </FormField>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setLeaveRow(null)}>Cancel</Button>
            <Button onClick={submitLeave}>{leaveRow?.status === "ACTIVE" ? "End membership" : "Re-join"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── schedule meeting dialog ── */}
      <Dialog open={createMeetingOpen} onOpenChange={setCreateMeetingOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Schedule a meeting</DialogTitle>
            <DialogDescription>The attendance register is seeded for the whole roster — default ABSENT, the patron marks attendance.</DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <FormField label="Club" required>
              <Select value={mtClub} onValueChange={setMtClub}>
                <SelectTrigger><SelectValue placeholder="Choose a club…" /></SelectTrigger>
                <SelectContent>
                  {(board?.clubs ?? []).map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="Date" required>
              <DateField value={mtDate} onChange={setMtDate} />
            </FormField>
            <FormField label="Topic / agenda">
              <Input value={mtTopic} onChange={(e) => setMtTopic(e.target.value)} placeholder="e.g. Experiment prep for the zonal quiz" />
            </FormField>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCreateMeetingOpen(false)}>Cancel</Button>
            <Button onClick={submitMeeting} disabled={!mtClub || !mtDate}>Schedule meeting</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── minutes dialog ── */}
      <Dialog open={!!minutesRow} onOpenChange={(o) => !o && setMinutesRow(null)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Meeting minutes</DialogTitle>
            <DialogDescription>
              {minutesRow?.clubName} · {minutesRow ? fmtDateShort(minutesRow.date) : ""} · {minutesRow?.present}/{minutesRow?.expected} present
            </DialogDescription>
          </DialogHeader>
          <FormField label="Minutes">
            <Textarea rows={4} value={minutesText} onChange={(e) => setMinutesText(e.target.value)} placeholder="Key decisions, announcements, tasks given…" />
          </FormField>
          <DialogFooter>
            <Button variant="outline" onClick={() => setMinutesRow(null)}>Cancel</Button>
            <Button onClick={submitMinutes}>Save minutes</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── guardian notice dialog ── */}
      <Dialog open={!!notifyRow} onOpenChange={(o) => !o && setNotifyRow(null)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Notify guardians by SMS</DialogTitle>
            <DialogDescription>
              Sends to the guardian of every active member of {notifyRow?.clubName ?? "the club"} through the SMS centre.
            </DialogDescription>
          </DialogHeader>
          <FormField label="Message" hint="Leave blank for the standard meeting-notice template.">
            <Textarea rows={3} value={notifyMsg} onChange={(e) => setNotifyMsg(e.target.value)} />
          </FormField>
          <DialogFooter>
            <Button variant="outline" onClick={() => setNotifyRow(null)}>Cancel</Button>
            <Button onClick={submitNotify} className="gap-1.5"><Megaphone className="size-4" /> Send notices</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── event create / edit dialog ── */}
      <Dialog open={eventOpen} onOpenChange={setEventOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>{editEvent ? `Edit ${editEvent.title}` : "Record an event"}</DialogTitle>
            <DialogDescription>Quiz competitions, festivals, sports and parades — with the level, outcome and placement.</DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <FormField label="Event title" required>
              <Input value={eTitle} onChange={(e) => setETitle(e.target.value)} placeholder="e.g. Zonal Science & Maths Quiz" />
            </FormField>
            <FormGrid cols={2}>
              <FormField label="Club">
                <Select value={eClub} onValueChange={setEClub}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">School-wide (no club)</SelectItem>
                    {(board?.clubs ?? []).map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Date" required>
                <DateField value={eDate} onChange={setEDate} />
              </FormField>
              <FormField label="Level">
                <Select value={eLevel} onValueChange={setELever}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    {Object.entries(LEVEL_UI).map(([k, v]) => <SelectItem key={k} value={k}>{v.label}</SelectItem>)}
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Outcome">
                <Select value={eOutcome} onValueChange={setEOutcome}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="UPCOMING">Upcoming</SelectItem>
                    <SelectItem value="COMPLETED">Completed</SelectItem>
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Placement" hint="1st, Runner-up, 3rd…">
                <Input value={ePlacement} onChange={(e) => setEPlacement(e.target.value)} placeholder="e.g. 2nd" />
              </FormField>
              <FormField label="Represented by">
                <Input value={eParticipants} onChange={(e) => setEParticipants(e.target.value)} placeholder="Names of the learners" />
              </FormField>
            </FormGrid>
            <FormField label="Note">
              <Textarea rows={2} value={eNote} onChange={(e) => setENote(e.target.value)} />
            </FormField>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setEventOpen(false)}>Cancel</Button>
            <Button onClick={submitEvent} disabled={!eTitle.trim() || !eDate}>{editEvent ? "Save event" : "Record event"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

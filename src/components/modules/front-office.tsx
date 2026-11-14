"use client";
// ── Front Office (wave 29 — the front desk books) ──
// The desk beside the gate: the Front Desk Board (who is on site right
// now, today's meetings, open follow-ups, upcoming events), the Visitors
// Book (pass numbers, time in/out, check-out), the Appointments Book
// (SCHEDULED → CONFIRMED → COMPLETED / CANCELLED / NO_SHOW), the Phone
// Call Log (incoming/outgoing with follow-up flags), the Postal Register
// (incoming parcels collected, outgoing letters dispatched) and the
// School Events Calendar (PTA meetings, open day, sports, prize-giving).
import { useCallback, useEffect, useMemo, useState } from "react";
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
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard, FormGrid, FormField } from "@/components/ui-helpers";
import { DateField } from "@/components/date-field";
import { fmtDateShort, fmtDateTime, initials, avatarColor } from "@/lib/format";
import { canAny } from "@/lib/rbac";
import { useToast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  BellRing, DoorOpen, CalendarClock, PhoneCall, Mailbox, CalendarRange, Plus, Search, LogOut,
  ClipboardCheck, ArrowUpRight, ArrowDownLeft, PackageOpen, Send, PartyPopper, Trash2, Stamp,
} from "lucide-react";
import {
  ResponsiveContainer, BarChart, Bar, XAxis, YAxis, Tooltip, CartesianGrid,
} from "recharts";

const OPS = "/ops/frontoffice";

type VisitorRow = {
  id: string; name: string; phone: string | null; organisation: string | null;
  category: string; purpose: string; personToSee: string | null; passNo: string | null;
  timeIn: string | null; timeOut: string | null; minutesOnSite: number | null;
  status: string; badgeIssued: boolean; notes: string | null; recordedByName: string | null;
};
type AppointmentRow = {
  id: string; title: string; withName: string; withRole: string; personToSee: string | null;
  scheduledAt: string | null; durationMin: number | null; location: string | null;
  purpose: string | null; status: string; outcome: string | null; notes: string | null; createdByName: string | null;
};
type CallRow = {
  id: string; direction: string; contactName: string; phone: string | null;
  regarding: string; handledByName: string | null; followUp: boolean; followUpDone: boolean;
  calledAt: string | null; notes: string | null;
};
type PostalRow = {
  id: string; direction: string; type: string; refNo: string | null; fromParty: string;
  toParty: string; receivedAt: string | null; dispatchedAt: string | null;
  collectedBy: string | null; status: string; notes: string | null; recordedByName: string | null;
};
type EventRow = {
  id: string; termId: string | null; termName: string | null; title: string; category: string;
  startsAt: string | null; endsAt: string | null; location: string | null;
  description: string | null; audience: string; createdByName: string | null;
};
type BoardData = {
  date: string;
  stats: { onSite: number; visitorsToday: number; appointmentsToday: number; followUpsOpen: number; postalPending: number; upcomingEvents: number };
  onSite: VisitorRow[];
  todayAppointments: AppointmentRow[];
  nextAppointment: AppointmentRow | null;
  upcomingEvents: EventRow[];
};
type AnalyticsData = {
  windowDays: number;
  visitors: { total: number; onSiteNow: number; avgStayMin: number | null; categoryMix: { category: string; count: number }[] };
  traffic: { date: string; visitors: number }[];
  peakHours: { hour: string; arrivals: number }[];
  appointments: { total: number; mix: { status: string; count: number }[] };
  calls: { total: number; incoming: number; outgoing: number; followUpsOpen: number };
  postal: { total: number; mix: { status: string; count: number }[] };
  upcomingEvents: EventRow[];
};

const CAT_STYLES: Record<string, string> = {
  PARENT: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  VENDOR: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
  GOVT: "bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300",
  ALUMNI: "bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300",
  OTHER: "bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300",
};
const APPT_STYLES: Record<string, string> = {
  SCHEDULED: "bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300",
  CONFIRMED: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  COMPLETED: "bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300",
  CANCELLED: "bg-stone-200 text-stone-500 line-through dark:bg-stone-800 dark:text-stone-400",
  NO_SHOW: "bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300",
};
const EVENT_STYLES: Record<string, string> = {
  PTA_MEETING: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  OPEN_DAY: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
  SPORTS: "bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300",
  PRIZE_GIVING: "bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-300",
  EXAMINATION: "bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300",
  HOLIDAY: "bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300",
  OTHER: "bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300",
};
const EVENT_LABELS: Record<string, string> = {
  PTA_MEETING: "PTA meeting", OPEN_DAY: "Open day", SPORTS: "Sports", PRIZE_GIVING: "Prize-giving day",
  EXAMINATION: "Examination", HOLIDAY: "Holiday", OTHER: "Event",
};
const CAT_LABELS: Record<string, string> = {
  PARENT: "Parent", VENDOR: "Vendor", GOVT: "Gov't", ALUMNI: "Alumna/us", OTHER: "Other",
};
const ROLE_LABELS: Record<string, string> = { PARENT: "Parent", STAFF: "Staff", VENDOR: "Vendor", GOVT: "Gov't", OTHER: "Other" };
const POSTAL_TYPE_LABELS: Record<string, string> = { LETTER: "Letter", PARCEL: "Parcel", DOCUMENT: "Document", COURIER: "Courier" };

const titleCase = (s: string) => s.charAt(0) + s.slice(1).toLowerCase();
const hhmm = (isoStr: string | null) => {
  if (!isoStr) return "—";
  const d = new Date(isoStr);
  return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
};
const stayLabel = (mins: number | null) => {
  if (mins == null) return null;
  if (mins < 60) return `${mins} min on site`;
  return `${Math.floor(mins / 60)}h ${mins % 60}m on site`;
};

export function FrontOffice() {
  const { currentSchoolId, session, navTarget } = useStore();
  const { toast } = useToast();
  const schoolId = currentSchoolId ?? "";
  const canManage = canAny(session?.permissions, ["frontoffice.manage"]);

  const [tab, setTab] = useState("board");
  const [board, setBoard] = useState<BoardData | null>(null);
  const [loading, setLoading] = useState(true);

  // visitors
  const [visitors, setVisitors] = useState<VisitorRow[]>([]);
  const [visOnSite, setVisOnSite] = useState(0);
  const [visDate, setVisDate] = useState("");
  const [visOpenOnly, setVisOpenOnly] = useState(false);
  const [visSearch, setVisSearch] = useState("");
  const [visDialog, setVisDialog] = useState(false);
  const [visForm, setVisForm] = useState({ name: "", phone: "", organisation: "", category: "PARENT", purpose: "", personToSee: "", notes: "" });
  const [visBusy, setVisBusy] = useState(false);

  // appointments
  const [appointments, setAppointments] = useState<AppointmentRow[]>([]);
  const [apptCounts, setApptCounts] = useState<Record<string, number>>({});
  const [apptScope, setApptScope] = useState("upcoming");
  const [apptStatus, setApptStatus] = useState("all");
  const [apptDialog, setApptDialog] = useState(false);
  const [apptForm, setApptForm] = useState({ title: "", withName: "", withRole: "PARENT", personToSee: "", date: "", time: "09:00", durationMin: "30", location: "", purpose: "", notes: "" });
  const [apptBusy, setApptBusy] = useState(false);

  // calls
  const [calls, setCalls] = useState<CallRow[]>([]);
  const [callFollowUpOnly, setCallFollowUpOnly] = useState(false);
  const [callDirection, setCallDirection] = useState("all");
  const [callDialog, setCallDialog] = useState(false);
  const [callForm, setCallForm] = useState({ direction: "INCOMING", contactName: "", phone: "", regarding: "", followUp: false, notes: "" });
  const [callBusy, setCallBusy] = useState(false);

  // postal
  const [postal, setPostal] = useState<PostalRow[]>([]);
  const [postalDirection, setPostalDirection] = useState("all");
  const [postalStatus, setPostalStatus] = useState("all");
  const [postalDialog, setPostalDialog] = useState(false);
  const [postalForm, setPostalForm] = useState({ direction: "INCOMING", type: "LETTER", fromParty: "", toParty: "", refNo: "", receivedAt: "", notes: "" });
  const [postalBusy, setPostalBusy] = useState(false);
  const [collectItem, setCollectItem] = useState<PostalRow | null>(null);
  const [collectedBy, setCollectedBy] = useState("");

  // events
  const [events, setEvents] = useState<EventRow[]>([]);
  const [terms, setTerms] = useState<{ id: string; name: string; isCurrent: boolean; startsAt: string | null }[]>([]);
  const [eventTerm, setEventTerm] = useState("all");
  const [eventUpcoming, setEventUpcoming] = useState(false);
  const [eventDialog, setEventDialog] = useState(false);
  const [eventForm, setEventForm] = useState({ title: "", category: "PTA_MEETING", startsAt: "", endsAt: "", location: "", audience: "ALL", description: "", termId: "none" });
  const [eventBusy, setEventBusy] = useState(false);

  // analytics
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);

  // refresh ticks — mutation handlers bump these to re-run the load effects
  const [boardTick, setBoardTick] = useState(0);
  const [visitorsTick, setVisitorsTick] = useState(0);
  const [appointmentsTick, setAppointmentsTick] = useState(0);
  const [callsTick, setCallsTick] = useState(0);
  const [postalTick, setPostalTick] = useState(0);
  const [eventsTick, setEventsTick] = useState(0);

  const bumpBoard = () => setBoardTick((t) => t + 1);
  const bumpVisitors = () => setVisitorsTick((t) => t + 1);
  const bumpAppointments = () => setAppointmentsTick((t) => t + 1);
  const bumpCalls = () => setCallsTick((t) => t + 1);
  const bumpPostal = () => setPostalTick((t) => t + 1);
  const bumpEvents = () => setEventsTick((t) => t + 1);

  // initial load + tab deep-link
  useEffect(() => {
    if (!schoolId) return;
    if (navTarget?.tab && ["board", "visitors", "appointments", "calls", "postal", "events"].includes(navTarget.tab)) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setTab(navTarget.tab);
    }
  }, [navTarget, schoolId]);

  // board load (board + analytics together)
  useEffect(() => {
    if (!schoolId) return;
    Promise.all([
      apiGet<BoardData>(`/api/school/${schoolId}${OPS}?action=board`),
      apiGet<AnalyticsData>(`/api/school/${schoolId}${OPS}?action=analytics`),
    ])
      .then(([b, a]) => { setBoard(b); setAnalytics(a); })
      .catch((e: Error) => toast({ title: "Could not load the front desk board", description: e.message, variant: "destructive" }))
      .finally(() => setLoading(false));
  }, [schoolId, boardTick]);

  // per-tab loads — fetch chains, filters re-fetch via their deps
  useEffect(() => {
    if (!schoolId || tab !== "visitors") return;
    const p = new URLSearchParams({ action: "visitors" });
    if (visDate) p.set("date", visDate);
    if (visOpenOnly) p.set("open", "1");
    if (visSearch.trim()) p.set("search", visSearch.trim());
    apiGet<{ onSite: number; visitors: VisitorRow[] }>(`/api/school/${schoolId}${OPS}?${p}`)
      .then((r) => { setVisitors(r.visitors); setVisOnSite(r.onSite); })
      .catch((e: Error) => toast({ title: "Could not load the visitors book", description: e.message, variant: "destructive" }));
  }, [schoolId, tab, visDate, visOpenOnly, visSearch, visitorsTick]);

  useEffect(() => {
    if (!schoolId || tab !== "appointments") return;
    const p = new URLSearchParams({ action: "appointments", scope: apptScope });
    if (apptStatus !== "all") p.set("status", apptStatus);
    apiGet<{ counts: Record<string, number>; appointments: AppointmentRow[] }>(`/api/school/${schoolId}${OPS}?${p}`)
      .then((r) => { setAppointments(r.appointments); setApptCounts(r.counts ?? {}); })
      .catch((e: Error) => toast({ title: "Could not load the appointments book", description: e.message, variant: "destructive" }));
  }, [schoolId, tab, apptScope, apptStatus, appointmentsTick]);

  useEffect(() => {
    if (!schoolId || tab !== "calls") return;
    const p = new URLSearchParams({ action: "calls" });
    if (callFollowUpOnly) p.set("followUp", "1");
    if (callDirection !== "all") p.set("direction", callDirection);
    apiGet<{ calls: CallRow[] }>(`/api/school/${schoolId}${OPS}?${p}`)
      .then((r) => setCalls(r.calls))
      .catch((e: Error) => toast({ title: "Could not load the call log", description: e.message, variant: "destructive" }));
  }, [schoolId, tab, callFollowUpOnly, callDirection, callsTick]);

  useEffect(() => {
    if (!schoolId || tab !== "postal") return;
    const p = new URLSearchParams({ action: "postal" });
    if (postalDirection !== "all") p.set("direction", postalDirection);
    if (postalStatus !== "all") p.set("status", postalStatus);
    apiGet<{ items: PostalRow[] }>(`/api/school/${schoolId}${OPS}?${p}`)
      .then((r) => setPostal(r.items))
      .catch((e: Error) => toast({ title: "Could not load the postal register", description: e.message, variant: "destructive" }));
  }, [schoolId, tab, postalDirection, postalStatus, postalTick]);

  useEffect(() => {
    if (!schoolId || tab !== "events") return;
    const p = new URLSearchParams({ action: "events" });
    if (eventTerm !== "all") p.set("termId", eventTerm);
    if (eventUpcoming) p.set("upcoming", "1");
    apiGet<{ terms: typeof terms; events: EventRow[] }>(`/api/school/${schoolId}${OPS}?${p}`)
      .then((r) => { setEvents(r.events); setTerms(r.terms ?? []); })
      .catch((e: Error) => toast({ title: "Could not load the events calendar", description: e.message, variant: "destructive" }));
  }, [schoolId, tab, eventTerm, eventUpcoming, eventsTick]);

  /* ── mutations ── */
  const post = async (body: any, okTitle: string, after: () => void) => {
    try {
      await apiPost(`/api/school/${schoolId}${OPS}`, body);
      toast({ title: okTitle });
      after();
    } catch (e: any) {
      toast({ title: "Action failed", description: e.message, variant: "destructive" });
    }
  };

  const submitVisitor = async () => {
    if (!visForm.name.trim() || !visForm.purpose.trim()) {
      toast({ title: "Name and purpose are required", variant: "destructive" });
      return;
    }
    setVisBusy(true);
    await post({ action: "visitor-in", ...visForm }, "Visitor booked in — pass issued", () => {
      setVisDialog(false);
      setVisForm({ name: "", phone: "", organisation: "", category: "PARENT", purpose: "", personToSee: "", notes: "" });
      bumpVisitors();
      bumpBoard();
    });
    setVisBusy(false);
  };

  const submitAppointment = async () => {
    if (!apptForm.title.trim() || !apptForm.withName.trim() || !apptForm.date) {
      toast({ title: "Title, who it's with and the date are required", variant: "destructive" });
      return;
    }
    setApptBusy(true);
    const scheduledAt = new Date(`${apptForm.date}T${apptForm.time || "09:00"}:00`).toISOString();
    await post({
      action: "appointment-create", ...apptForm, scheduledAt,
      durationMin: Number(apptForm.durationMin) || undefined,
    }, "Appointment booked", () => {
      setApptDialog(false);
      setApptForm({ title: "", withName: "", withRole: "PARENT", personToSee: "", date: "", time: "09:00", durationMin: "30", location: "", purpose: "", notes: "" });
      bumpAppointments();
      bumpBoard();
    });
    setApptBusy(false);
  };

  const submitCall = async () => {
    if (!callForm.contactName.trim() || !callForm.regarding.trim()) {
      toast({ title: "Contact name and what the call was about are required", variant: "destructive" });
      return;
    }
    setCallBusy(true);
    await post({ action: "call-log", ...callForm }, "Call logged", () => {
      setCallDialog(false);
      setCallForm({ direction: "INCOMING", contactName: "", phone: "", regarding: "", followUp: false, notes: "" });
      bumpCalls();
      bumpBoard();
    });
    setCallBusy(false);
  };

  const submitPostal = async () => {
    if (!postalForm.fromParty.trim() || !postalForm.toParty.trim()) {
      toast({ title: "From and To are required", variant: "destructive" });
      return;
    }
    setPostalBusy(true);
    await post({ action: "postal-create", ...postalForm }, "Item registered", () => {
      setPostalDialog(false);
      setPostalForm({ direction: "INCOMING", type: "LETTER", fromParty: "", toParty: "", refNo: "", receivedAt: "", notes: "" });
      bumpPostal();
      bumpBoard();
    });
    setPostalBusy(false);
  };

  const submitEvent = async () => {
    if (!eventForm.title.trim() || !eventForm.startsAt) {
      toast({ title: "Title and start date are required", variant: "destructive" });
      return;
    }
    setEventBusy(true);
    await post({ action: "event-create", ...eventForm, termId: eventForm.termId && eventForm.termId !== "none" ? eventForm.termId : undefined }, "Event added to the calendar", () => {
      setEventDialog(false);
      setEventForm({ title: "", category: "PTA_MEETING", startsAt: "", endsAt: "", location: "", audience: "ALL", description: "", termId: "none" });
      bumpEvents();
      bumpBoard();
    });
    setEventBusy(false);
  };

  const currentTermId = useMemo(() => terms.find((t) => t.isCurrent)?.id ?? "", [terms]);

  /* ── render ── */
  return (
    <div className="space-y-4">
      <ModuleHeader
        title="Front Office"
        description="The front desk books — visitors, appointments, calls, mail & the school calendar."
        actions={canManage ? (
          <>
            <Button size="sm" onClick={() => setVisDialog(true)}><Plus /> Book visitor</Button>
            <Button size="sm" variant="outline" onClick={() => setApptDialog(true)}><CalendarClock /> Book appointment</Button>
          </>
        ) : undefined}
      />

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex flex-wrap h-auto gap-1">
          <TabsTrigger value="board" className="gap-1"><BellRing className="size-3.5" /> Board</TabsTrigger>
          <TabsTrigger value="visitors" className="gap-1"><DoorOpen className="size-3.5" /> Visitors</TabsTrigger>
          <TabsTrigger value="appointments" className="gap-1"><CalendarClock className="size-3.5" /> Appointments</TabsTrigger>
          <TabsTrigger value="calls" className="gap-1"><PhoneCall className="size-3.5" /> Calls</TabsTrigger>
          <TabsTrigger value="postal" className="gap-1"><Mailbox className="size-3.5" /> Postal</TabsTrigger>
          <TabsTrigger value="events" className="gap-1"><CalendarRange className="size-3.5" /> Events</TabsTrigger>
        </TabsList>

        {/* ── BOARD ── */}
        <TabsContent value="board" className="space-y-4">
          {loading && !board ? <LoadingBlock label="Loading the front desk…" /> : !board ? (
            <EmptyState title="No board data" hint="Pick a school with front-office access." />
          ) : (
            <>
              <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <StatCard icon={<DoorOpen className="size-4" />} label="On site now" value={board.stats.onSite} tone={board.stats.onSite ? "primary" : "default"} />
                <StatCard icon={<DoorOpen className="size-4" />} label="Visitors today" value={board.stats.visitorsToday} />
                <StatCard icon={<CalendarClock className="size-4" />} label="Meetings today" value={board.stats.appointmentsToday} />
                <StatCard icon={<PhoneCall className="size-4" />} label="Follow-ups open" value={board.stats.followUpsOpen} tone={board.stats.followUpsOpen ? "danger" : "default"} />
                <StatCard icon={<Mailbox className="size-4" />} label="Mail pending" value={board.stats.postalPending} />
                <StatCard icon={<CalendarRange className="size-4" />} label="Upcoming events" value={board.stats.upcomingEvents} />
              </div>

              <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Card>
                  <CardHeader className="pb-2"><CardTitle className="flex items-center gap-2 text-sm"><DoorOpen className="size-4" /> On site now</CardTitle></CardHeader>
                  <CardContent>
                    {board.onSite.length === 0 ? (
                      <EmptyState title="Nobody on site" hint="Visitors appear here from check-in until check-out." />
                    ) : (
                      <ul className="max-h-72 space-y-2 overflow-y-auto pr-1">
                        {board.onSite.map((v) => (
                          <li key={v.id} className="flex items-center justify-between gap-2 rounded-lg border p-2">
                            <div className="flex min-w-0 items-center gap-2">
                              <span className={cn("flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold", avatarColor(v.name))}>{initials(v.name)}</span>
                              <div className="min-w-0">
                                <p className="truncate text-sm font-medium">{v.name}</p>
                                <p className="truncate text-xs text-muted-foreground">
                                  {v.passNo ? `Pass ${v.passNo} · ` : ""}in {hhmm(v.timeIn)}{v.personToSee ? ` · to see ${v.personToSee}` : ""}
                                </p>
                              </div>
                            </div>
                            <div className="flex shrink-0 items-center gap-1.5">
                              <span className="hidden text-xs text-muted-foreground sm:inline">{stayLabel(v.minutesOnSite)}</span>
                              {canManage && (
                                <Button size="sm" variant="outline" onClick={() => post({ action: "visitor-out", visitorId: v.id }, `${v.name} checked out`, bumpBoard)}>
                                  <LogOut /> Out
                                </Button>
                              )}
                            </div>
                          </li>
                        ))}
                      </ul>
                    )}
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader className="pb-2"><CardTitle className="flex items-center gap-2 text-sm"><CalendarClock className="size-4" /> Today's appointments</CardTitle></CardHeader>
                  <CardContent>
                    {board.todayAppointments.length === 0 ? (
                      <EmptyState title="No meetings today" hint={board.nextAppointment ? "The next scheduled meeting appears below." : "Book one from the Appointments tab."} />
                    ) : (
                      <ul className="max-h-72 space-y-2 overflow-y-auto pr-1">
                        {board.todayAppointments.map((a) => (
                          <li key={a.id} className="flex items-center justify-between gap-2 rounded-lg border p-2">
                            <div className="min-w-0">
                              <p className="truncate text-sm font-medium">{a.title}</p>
                              <p className="truncate text-xs text-muted-foreground">{hhmm(a.scheduledAt)} · {a.withName} ({ROLE_LABELS[a.withRole] ?? a.withRole}){a.location ? ` · ${a.location}` : ""}</p>
                            </div>
                            <Badge variant="outline" className={cn("shrink-0", APPT_STYLES[a.status])}>{titleCase(a.status)}</Badge>
                          </li>
                        ))}
                      </ul>
                    )}
                    {board.nextAppointment && (
                      <div className="mt-3 rounded-lg bg-muted/60 p-3 text-sm">
                        <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Next up</p>
                        <p className="mt-0.5 font-medium">{board.nextAppointment.title} — {fmtDateTime(board.nextAppointment.scheduledAt)}</p>
                        <p className="text-xs text-muted-foreground">With {board.nextAppointment.withName}{board.nextAppointment.personToSee ? ` · meeting ${board.nextAppointment.personToSee}` : ""}</p>
                      </div>
                    )}
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader className="pb-2"><CardTitle className="flex items-center gap-2 text-sm"><CalendarRange className="size-4" /> Upcoming events</CardTitle></CardHeader>
                  <CardContent>
                    {board.upcomingEvents.length === 0 ? (
                      <EmptyState title="Nothing on the calendar" hint="Add PTA meetings, open days and prize-giving from the Events tab." />
                    ) : (
                      <ul className="max-h-72 space-y-2 overflow-y-auto pr-1">
                        {board.upcomingEvents.map((e) => (
                          <li key={e.id} className="flex items-center justify-between gap-2 rounded-lg border p-2">
                            <div className="min-w-0">
                              <p className="truncate text-sm font-medium">{e.title}</p>
                              <p className="truncate text-xs text-muted-foreground">{fmtDateShort(e.startsAt)}{e.location ? ` · ${e.location}` : ""}{e.termName ? ` · ${e.termName}` : ""}</p>
                            </div>
                            <Badge variant="outline" className={cn("shrink-0", EVENT_STYLES[e.category])}>{EVENT_LABELS[e.category] ?? titleCase(e.category)}</Badge>
                          </li>
                        ))}
                      </ul>
                    )}
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader className="pb-2"><CardTitle className="flex items-center gap-2 text-sm"><Stamp className="size-4" /> Desk reminders</CardTitle></CardHeader>
                  <CardContent className="space-y-2 text-sm">
                    <div className="flex items-center justify-between rounded-lg border p-2">
                      <span>Open call follow-ups</span>
                      <Badge variant={board.stats.followUpsOpen ? "destructive" : "outline"}>{board.stats.followUpsOpen}</Badge>
                    </div>
                    <div className="flex items-center justify-between rounded-lg border p-2">
                      <span>Mail & parcels pending</span>
                      <Badge variant={board.stats.postalPending ? "secondary" : "outline"}>{board.stats.postalPending}</Badge>
                    </div>
                    {analytics?.visitors.avgStayMin != null && (
                      <div className="flex items-center justify-between rounded-lg border p-2">
                        <span>Average visit length (30d)</span>
                        <Badge variant="outline">{stayLabel(analytics.visitors.avgStayMin)}</Badge>
                      </div>
                    )}
                    <p className="pt-1 text-xs text-muted-foreground">
                      The gate book, call log and postal register are kept at the front desk — this board mirrors them live.
                    </p>
                  </CardContent>
                </Card>
              </div>

              {analytics && <FrontOfficeAnalyticsInline data={analytics} />}
            </>
          )}
        </TabsContent>

        {/* ── VISITORS ── */}
        <TabsContent value="visitors" className="space-y-4">
          <div className="flex flex-wrap items-end gap-2">
            <div className="w-40">
              <Label className="mb-1 text-xs text-muted-foreground">Day</Label>
              <DateField mode="date" value={visDate} onChange={setVisDate} placeholder="All days" />
            </div>
            <Button size="sm" variant={visOpenOnly ? "default" : "outline"} onClick={() => setVisOpenOnly(!visOpenOnly)}>
              <DoorOpen /> On site only
            </Button>
            <div className="relative min-w-48 flex-1">
              <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
              <Input className="pl-8" placeholder="Search name, purpose, person…" value={visSearch} onChange={(e) => setVisSearch(e.target.value)} />
            </div>
            <Badge variant="secondary">{visOnSite} on site</Badge>
            {canManage && <Button size="sm" onClick={() => setVisDialog(true)}><Plus /> Book visitor</Button>}
          </div>

          {!visitors.length ? (
            <EmptyState icon={<DoorOpen className="size-8" />} title="No visitors recorded" hint="Book the first visitor with the button above." />
          ) : (
            <Card>
              <CardContent className="p-0">
                <div className="max-h-[32rem] overflow-y-auto">
                  <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-muted/95 text-left text-xs uppercase tracking-wide text-muted-foreground backdrop-blur">
                      <tr>
                        <th className="px-3 py-2 font-medium">Visitor</th>
                        <th className="hidden px-3 py-2 font-medium sm:table-cell">Category</th>
                        <th className="hidden px-3 py-2 font-medium md:table-cell">Pass</th>
                        <th className="px-3 py-2 font-medium">Purpose / to see</th>
                        <th className="px-3 py-2 font-medium">In → Out</th>
                        <th className="px-3 py-2 font-medium">Status</th>
                        <th className="px-3 py-2" />
                      </tr>
                    </thead>
                    <tbody>
                      {visitors.map((v) => (
                        <tr key={v.id} className="border-t align-middle">
                          <td className="px-3 py-2">
                            <div className="flex items-center gap-2">
                              <span className={cn("flex size-7 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold", avatarColor(v.name))}>{initials(v.name)}</span>
                              <div className="min-w-0">
                                <p className="truncate font-medium">{v.name}</p>
                                {v.organisation && <p className="truncate text-xs text-muted-foreground">{v.organisation}</p>}
                              </div>
                            </div>
                          </td>
                          <td className="hidden px-3 py-2 sm:table-cell">
                            <Badge variant="outline" className={cn("whitespace-nowrap", CAT_STYLES[v.category])}>{CAT_LABELS[v.category] ?? v.category}</Badge>
                          </td>
                          <td className="hidden px-3 py-2 font-mono text-xs md:table-cell">{v.passNo ?? "—"}</td>
                          <td className="px-3 py-2">
                            <p className="max-w-56 truncate">{v.purpose}</p>
                            {v.personToSee && <p className="text-xs text-muted-foreground">to see {v.personToSee}</p>}
                          </td>
                          <td className="px-3 py-2 whitespace-nowrap text-xs">
                            {hhmm(v.timeIn)} → {v.timeOut ? hhmm(v.timeOut) : "—"}
                            {v.status === "ON_SITE" && v.minutesOnSite != null && (
                              <span className="block text-muted-foreground">{stayLabel(v.minutesOnSite)}</span>
                            )}
                          </td>
                          <td className="px-3 py-2">
                            <Badge variant="outline" className={v.status === "ON_SITE" ? "border-emerald-300 bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300" : ""}>
                              {v.status === "ON_SITE" ? "On site" : "Departed"}
                            </Badge>
                          </td>
                          <td className="px-3 py-2 text-right">
                            {canManage && v.status === "ON_SITE" && (
                              <Button size="sm" variant="outline" onClick={() => post({ action: "visitor-out", visitorId: v.id }, `${v.name} checked out`, bumpVisitors)}>
                                <LogOut /> Check out
                              </Button>
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
        </TabsContent>

        {/* ── APPOINTMENTS ── */}
        <TabsContent value="appointments" className="space-y-4">
          <div className="flex flex-wrap items-center gap-2">
            <Select value={apptScope} onValueChange={setApptScope}>
              <SelectTrigger className="w-36"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="upcoming">Upcoming</SelectItem>
                <SelectItem value="today">Today</SelectItem>
                <SelectItem value="past">Past</SelectItem>
              </SelectContent>
            </Select>
            <Select value={apptStatus} onValueChange={setApptStatus}>
              <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All statuses</SelectItem>
                {Object.keys(APPT_STYLES).map((s) => <SelectItem key={s} value={s}>{titleCase(s)}</SelectItem>)}
              </SelectContent>
            </Select>
            <div className="flex flex-wrap gap-1.5">
              {Object.entries(apptCounts).map(([s, n]) => (
                <Badge key={s} variant="outline" className={cn("cursor-default", APPT_STYLES[s])}>{titleCase(s)} {n}</Badge>
              ))}
            </div>
            <div className="ml-auto">
              {canManage && <Button size="sm" onClick={() => setApptDialog(true)}><Plus /> Book appointment</Button>}
            </div>
          </div>

          {!appointments.length ? (
            <EmptyState icon={<CalendarClock className="size-8" />} title="No appointments here" hint="Try another scope, or book a meeting." />
          ) : (
            <div className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
              {appointments.map((a) => (
                <Card key={a.id} className={cn(a.status === "CANCELLED" && "opacity-60")}>
                  <CardHeader className="pb-1.5">
                    <div className="flex items-start justify-between gap-2">
                      <CardTitle className="text-sm leading-snug">{a.title}</CardTitle>
                      <Badge variant="outline" className={cn("shrink-0", APPT_STYLES[a.status])}>{titleCase(a.status)}</Badge>
                    </div>
                  </CardHeader>
                  <CardContent className="space-y-1.5 text-sm">
                    <p className="text-xs text-muted-foreground">{fmtDateTime(a.scheduledAt)}{a.durationMin ? ` · ${a.durationMin} min` : ""}</p>
                    <p><span className="text-muted-foreground">With:</span> {a.withName} <span className="text-xs text-muted-foreground">({ROLE_LABELS[a.withRole] ?? a.withRole})</span></p>
                    {a.personToSee && <p><span className="text-muted-foreground">Meeting:</span> {a.personToSee}</p>}
                    {a.location && <p><span className="text-muted-foreground">Where:</span> {a.location}</p>}
                    {a.purpose && <p className="text-xs text-muted-foreground">{a.purpose}</p>}
                    {a.outcome && <p className="rounded bg-muted/60 p-2 text-xs">{a.outcome}</p>}
                    {canManage && ["SCHEDULED", "CONFIRMED"].includes(a.status) && (
                      <div className="flex flex-wrap gap-1.5 pt-1.5">
                        {a.status === "SCHEDULED" && (
                          <Button size="sm" variant="outline" onClick={() => post({ action: "appointment-status", appointmentId: a.id, status: "CONFIRMED" }, "Appointment confirmed", bumpAppointments)}>
                            <ClipboardCheck /> Confirm
                          </Button>
                        )}
                        <Button size="sm" variant="outline" onClick={() => post({ action: "appointment-status", appointmentId: a.id, status: "COMPLETED" }, "Marked completed", bumpAppointments)}>
                          <ClipboardCheck /> Done
                        </Button>
                        <Button size="sm" variant="ghost" onClick={() => post({ action: "appointment-status", appointmentId: a.id, status: "NO_SHOW" }, "Marked no-show", bumpAppointments)}>
                          No-show
                        </Button>
                        <Button size="sm" variant="ghost" className="text-destructive" onClick={() => post({ action: "appointment-status", appointmentId: a.id, status: "CANCELLED" }, "Appointment cancelled", bumpAppointments)}>
                          Cancel
                        </Button>
                      </div>
                    )}
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </TabsContent>

        {/* ── CALLS ── */}
        <TabsContent value="calls" className="space-y-4">
          <div className="flex flex-wrap items-center gap-2">
            <Button size="sm" variant={callFollowUpOnly ? "default" : "outline"} onClick={() => setCallFollowUpOnly(!callFollowUpOnly)}>
              <PhoneCall /> Open follow-ups
            </Button>
            <Select value={callDirection} onValueChange={setCallDirection}>
              <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All calls</SelectItem>
                <SelectItem value="INCOMING">Incoming</SelectItem>
                <SelectItem value="OUTGOING">Outgoing</SelectItem>
              </SelectContent>
            </Select>
            <div className="ml-auto">
              {canManage && <Button size="sm" onClick={() => setCallDialog(true)}><Plus /> Log call</Button>}
            </div>
          </div>

          {!calls.length ? (
            <EmptyState icon={<PhoneCall className="size-8" />} title="No calls logged" hint="Record front-desk calls so nothing is lost between hands." />
          ) : (
            <Card>
              <CardContent className="p-0">
                <div className="max-h-[32rem] overflow-y-auto">
                  <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-muted/95 text-left text-xs uppercase tracking-wide text-muted-foreground backdrop-blur">
                      <tr>
                        <th className="px-3 py-2 font-medium">Call</th>
                        <th className="px-3 py-2 font-medium">Contact</th>
                        <th className="hidden px-3 py-2 font-medium md:table-cell">Regarding</th>
                        <th className="hidden px-3 py-2 font-medium lg:table-cell">Taken by</th>
                        <th className="px-3 py-2 font-medium">When</th>
                        <th className="px-3 py-2" />
                      </tr>
                    </thead>
                    <tbody>
                      {calls.map((c) => (
                        <tr key={c.id} className={cn("border-t", c.followUp && !c.followUpDone && "bg-amber-50/60 dark:bg-amber-950/20")}>
                          <td className="px-3 py-2">
                            <span className={cn("inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium", c.direction === "INCOMING" ? "bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300" : "bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300")}>
                              {c.direction === "INCOMING" ? <ArrowDownLeft className="size-3" /> : <ArrowUpRight className="size-3" />}
                              {titleCase(c.direction)}
                            </span>
                          </td>
                          <td className="px-3 py-2">
                            <p className="font-medium">{c.contactName}</p>
                            {c.phone && <p className="text-xs text-muted-foreground">{c.phone}</p>}
                          </td>
                          <td className="hidden max-w-64 truncate px-3 py-2 md:table-cell">
                            {c.regarding}
                            {c.notes && <span className="block truncate text-xs text-muted-foreground">{c.notes}</span>}
                          </td>
                          <td className="hidden px-3 py-2 text-xs lg:table-cell">{c.handledByName ?? "—"}</td>
                          <td className="px-3 py-2 text-xs whitespace-nowrap">{fmtDateTime(c.calledAt)}</td>
                          <td className="px-3 py-2 text-right">
                            {c.followUp && !c.followUpDone && (
                              <Badge variant="destructive" className="mr-2">Follow up</Badge>
                            )}
                            {canManage && c.followUp && !c.followUpDone && (
                              <Button size="sm" variant="outline" onClick={() => post({ action: "call-followup", callId: c.id, done: true }, "Follow-up closed", bumpCalls)}>
                                <ClipboardCheck /> Done
                              </Button>
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
        </TabsContent>

        {/* ── POSTAL ── */}
        <TabsContent value="postal" className="space-y-4">
          <div className="flex flex-wrap items-center gap-2">
            <Select value={postalDirection} onValueChange={setPostalDirection}>
              <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">In & out</SelectItem>
                <SelectItem value="INCOMING">Incoming</SelectItem>
                <SelectItem value="OUTGOING">Outgoing</SelectItem>
              </SelectContent>
            </Select>
            <Select value={postalStatus} onValueChange={setPostalStatus}>
              <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All statuses</SelectItem>
                <SelectItem value="PENDING">Pending</SelectItem>
                <SelectItem value="DISPATCHED">Dispatched</SelectItem>
                <SelectItem value="COLLECTED">Collected</SelectItem>
              </SelectContent>
            </Select>
            <div className="ml-auto">
              {canManage && <Button size="sm" onClick={() => setPostalDialog(true)}><Plus /> Register item</Button>}
            </div>
          </div>

          {!postal.length ? (
            <EmptyState icon={<Mailbox className="size-8" />} title="The register is empty" hint="Log incoming letters & parcels, and outgoing school mail." />
          ) : (
            <div className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
              {postal.map((p) => (
                <Card key={p.id}>
                  <CardHeader className="pb-1.5">
                    <div className="flex items-start justify-between gap-2">
                      <CardTitle className="flex items-center gap-1.5 text-sm">
                        {p.direction === "INCOMING" ? <ArrowDownLeft className="size-4 text-teal-600" /> : <ArrowUpRight className="size-4 text-orange-600" />}
                        {POSTAL_TYPE_LABELS[p.type] ?? titleCase(p.type)}
                      </CardTitle>
                      <Badge variant="outline" className={p.status === "PENDING" ? "border-amber-300 bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-300" : ""}>
                        {titleCase(p.status)}
                      </Badge>
                    </div>
                  </CardHeader>
                  <CardContent className="space-y-1.5 text-sm">
                    <p><span className="text-muted-foreground">From:</span> {p.fromParty}</p>
                    <p><span className="text-muted-foreground">To:</span> {p.toParty}</p>
                    {p.refNo && <p className="font-mono text-xs text-muted-foreground">Ref {p.refNo}</p>}
                    <p className="text-xs text-muted-foreground">{p.direction === "INCOMING" ? "Received" : "Logged"} {fmtDateTime(p.receivedAt)}</p>
                    {p.dispatchedAt && <p className="text-xs text-muted-foreground">{p.status === "COLLECTED" ? `Collected by ${p.collectedBy}` : "Dispatched"} · {fmtDateTime(p.dispatchedAt)}</p>}
                    {canManage && p.status === "PENDING" && (
                      <div className="flex gap-1.5 pt-1.5">
                        {p.direction === "OUTGOING" ? (
                          <Button size="sm" variant="outline" onClick={() => post({ action: "postal-status", itemId: p.id, status: "DISPATCHED" }, "Marked dispatched", bumpPostal)}>
                            <Send /> Dispatch
                          </Button>
                        ) : (
                          <Button size="sm" variant="outline" onClick={() => { setCollectItem(p); setCollectedBy(""); }}>
                            <PackageOpen /> Collect…
                          </Button>
                        )}
                      </div>
                    )}
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </TabsContent>

        {/* ── EVENTS ── */}
        <TabsContent value="events" className="space-y-4">
          <div className="flex flex-wrap items-center gap-2">
            <Select value={eventTerm} onValueChange={setEventTerm}>
              <SelectTrigger className="w-48"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All terms</SelectItem>
                {terms.map((t) => <SelectItem key={t.id} value={t.id}>{t.name}{t.isCurrent ? " (current)" : ""}</SelectItem>)}
              </SelectContent>
            </Select>
            <Button size="sm" variant={eventUpcoming ? "default" : "outline"} onClick={() => setEventUpcoming(!eventUpcoming)}>
              <CalendarRange /> Upcoming only
            </Button>
            <div className="ml-auto">
              {canManage && <Button size="sm" onClick={() => { setEventForm((f) => ({ ...f, termId: currentTermId || "none" })); setEventDialog(true); }}><Plus /> Add event</Button>}
            </div>
          </div>

          {!events.length ? (
            <EmptyState icon={<PartyPopper className="size-8" />} title="Nothing on the calendar yet" hint="Add the PTA meeting, open day, sports & prize-giving day." />
          ) : (
            <div className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
              {events.map((e) => (
                <Card key={e.id}>
                  <CardHeader className="pb-1.5">
                    <div className="flex items-start justify-between gap-2">
                      <CardTitle className="text-sm leading-snug">{e.title}</CardTitle>
                      <Badge variant="outline" className={cn("shrink-0", EVENT_STYLES[e.category])}>{EVENT_LABELS[e.category] ?? titleCase(e.category)}</Badge>
                    </div>
                  </CardHeader>
                  <CardContent className="space-y-1.5 text-sm">
                    <p className="font-medium">
                      {fmtDateShort(e.startsAt)}{e.endsAt && e.endsAt !== e.startsAt ? ` – ${fmtDateShort(e.endsAt)}` : ""}
                    </p>
                    {e.location && <p className="text-muted-foreground">{e.location}</p>}
                    <p className="text-xs text-muted-foreground">For: {titleCase(e.audience.toLowerCase())}{e.termName ? ` · ${e.termName}` : ""}</p>
                    {e.description && <p className="text-xs text-muted-foreground">{e.description}</p>}
                    {canManage && (
                      <div className="pt-1.5">
                        <Button size="sm" variant="ghost" className="text-destructive" onClick={() => post({ action: "event-delete", eventId: e.id }, "Event removed", bumpEvents)}>
                          <Trash2 /> Remove
                        </Button>
                      </div>
                    )}
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </TabsContent>
      </Tabs>

      {/* ── Book visitor dialog ── */}
      <Dialog open={visDialog} onOpenChange={setVisDialog}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Book a visitor in</DialogTitle>
            <DialogDescription>Entry for the Visitors Book — a pass number is issued automatically.</DialogDescription>
          </DialogHeader>
          <FormGrid cols={2}>
            <FormField label="Full name" required span={2}>
              <Input value={visForm.name} onChange={(e) => setVisForm({ ...visForm, name: e.target.value })} placeholder="e.g. Mrs. Akosua Mensah" />
            </FormField>
            <FormField label="Phone">
              <Input value={visForm.phone} onChange={(e) => setVisForm({ ...visForm, phone: e.target.value })} placeholder="024…" />
            </FormField>
            <FormField label="Organisation">
              <Input value={visForm.organisation} onChange={(e) => setVisForm({ ...visForm, organisation: e.target.value })} placeholder="e.g. GES Circuit Office" />
            </FormField>
            <FormField label="Category">
              <Select value={visForm.category} onValueChange={(v) => setVisForm({ ...visForm, category: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{Object.keys(CAT_LABELS).map((c) => <SelectItem key={c} value={c}>{CAT_LABELS[c]}</SelectItem>)}</SelectContent>
              </Select>
            </FormField>
            <FormField label="To see">
              <Input value={visForm.personToSee} onChange={(e) => setVisForm({ ...visForm, personToSee: e.target.value })} placeholder="e.g. The Headmistress" />
            </FormField>
            <FormField label="Purpose of visit" required span={2}>
              <Input value={visForm.purpose} onChange={(e) => setVisForm({ ...visForm, purpose: e.target.value })} placeholder="e.g. Ward's pick-up / textbook delivery" />
            </FormField>
            <FormField label="Notes" span={2}>
              <Textarea rows={2} value={visForm.notes} onChange={(e) => setVisForm({ ...visForm, notes: e.target.value })} placeholder="Anything the desk should remember" />
            </FormField>
          </FormGrid>
          <DialogFooter>
            <Button variant="outline" onClick={() => setVisDialog(false)}>Cancel</Button>
            <Button onClick={submitVisitor} disabled={visBusy}>{visBusy ? "Booking…" : "Book in"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── Book appointment dialog ── */}
      <Dialog open={apptDialog} onOpenChange={setApptDialog}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Book an appointment</DialogTitle>
            <DialogDescription>Scheduled meeting with a parent, staff member or visitor.</DialogDescription>
          </DialogHeader>
          <FormGrid cols={2}>
            <FormField label="Title" required span={2}>
              <Input value={apptForm.title} onChange={(e) => setApptForm({ ...apptForm, title: e.target.value })} placeholder="e.g. Discuss JHS 3 fees balance" />
            </FormField>
            <FormField label="With (name)" required>
              <Input value={apptForm.withName} onChange={(e) => setApptForm({ ...apptForm, withName: e.target.value })} placeholder="e.g. Mr. Kwame Osei" />
            </FormField>
            <FormField label="They are a">
              <Select value={apptForm.withRole} onValueChange={(v) => setApptForm({ ...apptForm, withRole: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{Object.keys(ROLE_LABELS).map((r) => <SelectItem key={r} value={r}>{ROLE_LABELS[r]}</SelectItem>)}</SelectContent>
              </Select>
            </FormField>
            <FormField label="Date" required>
              <DateField mode="date" value={apptForm.date} onChange={(v) => setApptForm({ ...apptForm, date: v })} />
            </FormField>
            <FormField label="Time">
              <Input type="time" value={apptForm.time} onChange={(e) => setApptForm({ ...apptForm, time: e.target.value })} />
            </FormField>
            <FormField label="Meeting with (staff)">
              <Input value={apptForm.personToSee} onChange={(e) => setApptForm({ ...apptForm, personToSee: e.target.value })} placeholder="e.g. Headmistress" />
            </FormField>
            <FormField label="Duration (min)">
              <Input type="number" min={5} max={600} value={apptForm.durationMin} onChange={(e) => setApptForm({ ...apptForm, durationMin: e.target.value })} />
            </FormField>
            <FormField label="Location" span={2}>
              <Input value={apptForm.location} onChange={(e) => setApptForm({ ...apptForm, location: e.target.value })} placeholder="e.g. Head's office" />
            </FormField>
            <FormField label="Purpose" span={2}>
              <Textarea rows={2} value={apptForm.purpose} onChange={(e) => setApptForm({ ...apptForm, purpose: e.target.value })} />
            </FormField>
          </FormGrid>
          <DialogFooter>
            <Button variant="outline" onClick={() => setApptDialog(false)}>Cancel</Button>
            <Button onClick={submitAppointment} disabled={apptBusy}>{apptBusy ? "Booking…" : "Book"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── Log call dialog ── */}
      <Dialog open={callDialog} onOpenChange={setCallDialog}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Log a phone call</DialogTitle>
            <DialogDescription>The desk copy of every call worth remembering.</DialogDescription>
          </DialogHeader>
          <FormGrid cols={2}>
            <FormField label="Direction">
              <Select value={callForm.direction} onValueChange={(v) => setCallForm({ ...callForm, direction: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="INCOMING">Incoming</SelectItem>
                  <SelectItem value="OUTGOING">Outgoing</SelectItem>
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="Phone">
              <Input value={callForm.phone} onChange={(e) => setCallForm({ ...callForm, phone: e.target.value })} placeholder="024…" />
            </FormField>
            <FormField label="Contact name" required span={2}>
              <Input value={callForm.contactName} onChange={(e) => setCallForm({ ...callForm, contactName: e.target.value })} placeholder="Who called / was called" />
            </FormField>
            <FormField label="Regarding" required span={2}>
              <Input value={callForm.regarding} onChange={(e) => setCallForm({ ...callForm, regarding: e.target.value })} placeholder="e.g. Asked about mid-term break dates" />
            </FormField>
            <FormField label="Notes" span={2}>
              <Textarea rows={2} value={callForm.notes} onChange={(e) => setCallForm({ ...callForm, notes: e.target.value })} />
            </FormField>
            <FormField span={2}>
              <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" className="size-4 accent-amber-600" checked={callForm.followUp} onChange={(e) => setCallForm({ ...callForm, followUp: e.target.checked })} />
                Needs a follow-up
              </label>
            </FormField>
          </FormGrid>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCallDialog(false)}>Cancel</Button>
            <Button onClick={submitCall} disabled={callBusy}>{callBusy ? "Logging…" : "Log call"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── Register postal item dialog ── */}
      <Dialog open={postalDialog} onOpenChange={setPostalDialog}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Register mail or parcel</DialogTitle>
            <DialogDescription>Incoming deliveries and outgoing school mail, in one register.</DialogDescription>
          </DialogHeader>
          <FormGrid cols={2}>
            <FormField label="Direction">
              <Select value={postalForm.direction} onValueChange={(v) => setPostalForm({ ...postalForm, direction: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="INCOMING">Incoming</SelectItem>
                  <SelectItem value="OUTGOING">Outgoing</SelectItem>
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="Type">
              <Select value={postalForm.type} onValueChange={(v) => setPostalForm({ ...postalForm, type: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{Object.keys(POSTAL_TYPE_LABELS).map((t) => <SelectItem key={t} value={t}>{POSTAL_TYPE_LABELS[t]}</SelectItem>)}</SelectContent>
              </Select>
            </FormField>
            <FormField label="From" required>
              <Input value={postalForm.fromParty} onChange={(e) => setPostalForm({ ...postalForm, fromParty: e.target.value })} placeholder="e.g. WAEC Accra" />
            </FormField>
            <FormField label="To" required>
              <Input value={postalForm.toParty} onChange={(e) => setPostalForm({ ...postalForm, toParty: e.target.value })} placeholder="e.g. The Headmistress" />
            </FormField>
            <FormField label="Reference no.">
              <Input value={postalForm.refNo} onChange={(e) => setPostalForm({ ...postalForm, refNo: e.target.value })} placeholder="Courier / registered no." />
            </FormField>
            <FormField label="Received / logged on">
              <DateField mode="date" value={postalForm.receivedAt} onChange={(v) => setPostalForm({ ...postalForm, receivedAt: v })} placeholder="Today" />
            </FormField>
            <FormField label="Notes" span={2}>
              <Textarea rows={2} value={postalForm.notes} onChange={(e) => setPostalForm({ ...postalForm, notes: e.target.value })} />
            </FormField>
          </FormGrid>
          <DialogFooter>
            <Button variant="outline" onClick={() => setPostalDialog(false)}>Cancel</Button>
            <Button onClick={submitPostal} disabled={postalBusy}>{postalBusy ? "Registering…" : "Register"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── Collect postal item dialog ── */}
      <Dialog open={!!collectItem} onOpenChange={(o) => !o && setCollectItem(null)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Record collection</DialogTitle>
            <DialogDescription>{collectItem ? `${collectItem.type.toLowerCase()} from ${collectItem.fromParty} for ${collectItem.toParty}` : ""}</DialogDescription>
          </DialogHeader>
          <FormField label="Collected by" required>
            <Input value={collectedBy} onChange={(e) => setCollectedBy(e.target.value)} placeholder="Name & signature reference" />
          </FormField>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCollectItem(null)}>Cancel</Button>
            <Button
              disabled={!collectedBy.trim()}
              onClick={async () => {
                if (!collectItem) return;
                await post({ action: "postal-status", itemId: collectItem.id, status: "COLLECTED", collectedBy }, "Collection recorded", () => {
                  setCollectItem(null);
                  bumpPostal();
                  bumpBoard();
                });
              }}
            >
              <PackageOpen /> Record
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── Add event dialog ── */}
      <Dialog open={eventDialog} onOpenChange={setEventDialog}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Add a school event</DialogTitle>
            <DialogDescription>PTA meetings, open days, sports, prize-giving — the noticeboard calendar.</DialogDescription>
          </DialogHeader>
          <FormGrid cols={2}>
            <FormField label="Title" required span={2}>
              <Input value={eventForm.title} onChange={(e) => setEventForm({ ...eventForm, title: e.target.value })} placeholder="e.g. Term 1 PTA general meeting" />
            </FormField>
            <FormField label="Category">
              <Select value={eventForm.category} onValueChange={(v) => setEventForm({ ...eventForm, category: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{Object.keys(EVENT_LABELS).map((c) => <SelectItem key={c} value={c}>{EVENT_LABELS[c]}</SelectItem>)}</SelectContent>
              </Select>
            </FormField>
            <FormField label="Audience">
              <Select value={eventForm.audience} onValueChange={(v) => setEventForm({ ...eventForm, audience: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="ALL">Everyone</SelectItem>
                  <SelectItem value="STAFF">Staff</SelectItem>
                  <SelectItem value="PARENTS">Parents</SelectItem>
                  <SelectItem value="STUDENTS">Learners</SelectItem>
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="Starts" required>
              <DateField mode="date" value={eventForm.startsAt} onChange={(v) => setEventForm({ ...eventForm, startsAt: v })} />
            </FormField>
            <FormField label="Ends (optional)">
              <DateField mode="date" value={eventForm.endsAt} onChange={(v) => setEventForm({ ...eventForm, endsAt: v })} placeholder="Same day" />
            </FormField>
            <FormField label="Term">
              <Select value={eventForm.termId} onValueChange={(v) => setEventForm({ ...eventForm, termId: v })}>
                <SelectTrigger><SelectValue placeholder="Not tied to a term" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">Not tied to a term</SelectItem>
                  {terms.map((t) => <SelectItem key={t.id} value={t.id}>{t.name}{t.isCurrent ? " (current)" : ""}</SelectItem>)}
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="Location">
              <Input value={eventForm.location} onChange={(e) => setEventForm({ ...eventForm, location: e.target.value })} placeholder="e.g. School assembly grounds" />
            </FormField>
            <FormField label="Details" span={2}>
              <Textarea rows={2} value={eventForm.description} onChange={(e) => setEventForm({ ...eventForm, description: e.target.value })} />
            </FormField>
          </FormGrid>
          <DialogFooter>
            <Button variant="outline" onClick={() => setEventDialog(false)}>Cancel</Button>
            <Button onClick={submitEvent} disabled={eventBusy}>{eventBusy ? "Adding…" : "Add event"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

/* analytics sub-view is available through the Board tab's reminders; kept
   lightweight here so the wave stays focused on the desk books themselves. */
export function FrontOfficeAnalyticsInline({ data }: { data: AnalyticsData }) {
  return (
    <div className="space-y-4">
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm">Visitor traffic — last 14 days</CardTitle></CardHeader>
          <CardContent className="h-56">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={data.traffic}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                <XAxis dataKey="date" tick={{ fontSize: 10 }} tickFormatter={(d: string) => d.slice(5)} />
                <YAxis allowDecimals={false} tick={{ fontSize: 10 }} />
                <Tooltip />
                <Bar dataKey="visitors" fill="#b45309" radius={[3, 3, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-sm">Arrivals by hour (30-day)</CardTitle></CardHeader>
          <CardContent className="h-56">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={data.peakHours}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                <XAxis dataKey="hour" tick={{ fontSize: 10 }} />
                <YAxis allowDecimals={false} tick={{ fontSize: 10 }} />
                <Tooltip />
                <Bar dataKey="arrivals" fill="#0f766e" radius={[3, 3, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

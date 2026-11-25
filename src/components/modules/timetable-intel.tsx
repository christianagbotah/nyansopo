"use client";
/* Nyansapo — Timetable Intelligence (wave 65).
   The planning brain on top of the weekly routine:
   • Teacher grids — one teacher's whole week across every class, with load
     stats and the school-wide clash report (double-booked periods).
   • Cover planner — when the staff attendance book marks a teacher ABSENT or
     LEAVE, every affected lesson is listed with RANKED substitute suggestions
     (teaches the subject → free that period → lightest load). One tap assigns
     the cover onto the exact timetable slot and fires SMS + email + in-app
     alerts to the covering teacher — no more shouting across the compound. */
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  CalendarClock, TriangleAlert, Users, Clock3, CalendarDays, RefreshCcw, UserCheck, UserX,
  BellRing, Mail, Smartphone, X, Layers, Sparkles, CircleCheck, Inbox, Info,
} from "lucide-react";

/* ── API shapes ── */
type Slot = {
  id: string; classroomId: string; classroomName: string; subjectId: string | null;
  subjectName: string; staffId: string | null; staffName: string | null;
  dayOfWeek: number; startMinute: number; endMinute: number; notes: string | null;
};
type StaffRow = {
  id: string; name: string; role: string; category: string; position: string | null;
  staffCode: string | null; subjects: string[]; hasPhone: boolean; hasEmail: boolean; smsNotify: boolean;
};
type Clash = { staffId: string; staffName: string; day: number; a: Slot; b: Slot };
type Absentee = {
  staffId: string; name: string; status: string; note: string | null;
  affected: Slot[];
};
type Cover = {
  id: string; slotId: string | null; absent: string; substitute: string; substituteStaffId: string;
  classRef: string | null; periods: string | null; note: string | null;
  slot: { id: string; subjectName: string; startMinute: number; endMinute: number; dayOfWeek: number } | null;
};
type Suggestion = { id: string; name: string; subjectMatch: boolean; loadThatDay: number; weeklyLoad: number; reasons: string[] };
type Data = {
  date: string; weekday: number; slots: Slot[]; staff: StaffRow[]; clashes: Clash[];
  absentees: Absentee[]; covers: Cover[]; suggestions: Record<string, Suggestion[]>;
  attendanceMarked: boolean;
};
type AlertResult = { staff: string; sms: string; email: string; inApp: boolean };

const DAYS = [
  { n: 1, label: "Monday" }, { n: 2, label: "Tuesday" }, { n: 3, label: "Wednesday" },
  { n: 4, label: "Thursday" }, { n: 5, label: "Friday" },
];
const hhmm = (m: number) => `${String(Math.floor(m / 60)).padStart(2, "0")}:${String(m % 60).padStart(2, "0")}`;
const todayIso = () => new Date().toISOString().slice(0, 10);

export function TimetableIntel() {
  const { currentSchoolId, navTarget } = useStore();
  const [tab, setTab] = useState("teachers");
  const [data, setData] = useState<Data | null>(null);
  const [date, setDate] = useState(todayIso());
  const [busy, setBusy] = useState(false);
  const [alertOut, setAlertOut] = useState<AlertResult | null>(null);
  const [fallback, setFallback] = useState<{ lessonId: string; staffId: string } | null>(null);

  /* teacher grid */
  const [teacherId, setTeacherId] = useState<string>("");

  useEffect(() => {
    if (navTarget?.tab && ["teachers", "cover"].includes(navTarget.tab)) setTab(navTarget.tab);
  }, [navTarget]);

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<Data>(`/api/school/${currentSchoolId}/ops/timetable?date=${date}`).then((d) => {
      setData(d);
      setTeacherId((t) => (t && d.staff.some((s) => s.id === t) ? t : d.staff.find((s) => s.category === "TEACHING")?.id ?? ""));
    }).catch((e: Error) => { toast({ title: "Could not load timetable", description: e.message, variant: "destructive" }); setData(null); });
  };
  useEffect(load, [currentSchoolId, date]);

  const teachers = useMemo(() => data?.staff.filter((s) => s.category === "TEACHING") ?? [], [data]);
  const teacher = useMemo(() => data?.staff.find((s) => s.id === teacherId) ?? null, [data, teacherId]);
  const teacherSlots = useMemo(
    () => (data?.slots ?? []).filter((s) => s.staffId === teacherId).sort((a, b) => a.dayOfWeek - b.dayOfWeek || a.startMinute - b.startMinute),
    [data, teacherId],
  );
  const gridStarts = useMemo(() => {
    const set = new Set<number>();
    teacherSlots.forEach((s) => set.add(s.startMinute));
    return [...set].sort((a, b) => a - b);
  }, [teacherSlots]);
  const myClashes = useMemo(() => (data?.clashes ?? []).filter((c) => c.staffId === teacherId), [data, teacherId]);

  /* free staff fallback picker per lesson (beyond the top suggestions) */
  const freeStaffFor = (lesson: Slot): StaffRow[] => {
    if (!data) return [];
    const window = { dayOfWeek: lesson.dayOfWeek, startMinute: lesson.startMinute, endMinute: lesson.endMinute };
    const ov = (x: { dayOfWeek: number; startMinute: number; endMinute: number }) => x.dayOfWeek === window.dayOfWeek && x.startMinute < window.endMinute && window.startMinute < x.endMinute;
    const coveredSlots = new Set(data.covers.filter((c) => c.slot).map((c) => c.slot!.id));
    const busyCover = new Map<string, { dayOfWeek: number; startMinute: number; endMinute: number }[]>();
    data.covers.forEach((c) => { if (c.slot) busyCover.set(c.substituteStaffId, [...(busyCover.get(c.substituteStaffId) ?? []), c.slot]); });
    const absentIds = new Set(data.absentees.map((a) => a.staffId));
    return teachers.filter((s) =>
      s.id !== lesson.staffId &&
      !absentIds.has(s.id) &&
      !coveredSlots.has(lesson.id) &&
      !data.slots.some((x) => x.id !== lesson.id && x.staffId === s.id && ov(x)) &&
      !(busyCover.get(s.id) ?? []).some((x) => ov(x)),
    );
  };

  async function assignCover(lesson: Slot, absentee: Absentee, substituteStaffId: string) {
    if (!currentSchoolId || !substituteStaffId) return;
    setBusy(true);
    try {
      const d = await apiPost<{ ok: boolean; alert: AlertResult }>(`/api/school/${currentSchoolId}/ops/timetable`, {
        action: "assign-cover",
        slotId: lesson.id,
        date,
        absentStaffId: absentee.staffId,
        substituteStaffId,
      });
      setAlertOut(d.alert);
      const via = [d.alert.sms !== "SKIPPED" ? `SMS ${d.alert.sms}` : null, d.alert.email !== "SKIPPED" ? `email ${d.alert.email}` : null, "in-app bell"].filter(Boolean).join(" · ");
      toast({ title: `Cover locked — ${d.alert.staff} takes ${lesson.subjectName}`, description: `Alerted via ${via}.` });
      load();
    } catch (e: any) {
      toast({ title: "Could not assign cover", description: e.message, variant: "destructive" });
    } finally { setBusy(false); setFallback(null); }
  }

  async function cancelCover(id: string) {
    if (!currentSchoolId) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/timetable`, { action: "cancel-cover", id });
      toast({ title: "Cover removed" });
      load();
    } catch (e: any) {
      toast({ title: "Could not remove cover", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  if (!data) return <LoadingBlock label="Loading timetable intelligence…" />;

  const dayName = DAYS.find((d) => d.n === data.weekday)?.label ?? "";

  return (
    <div className="space-y-5">
      <ModuleHeader
        title="Timetable Intelligence"
        description="The planning brain on top of the weekly routine — teacher grids, clash detection and one-tap cover for absent teachers."
        actions={
          <div className="flex items-center gap-2">
            <Input type="date" value={date} onChange={(e) => setDate(e.target.value || todayIso())} className="h-9 w-40" aria-label="Planning date" />
            <Button size="sm" variant="outline" className="h-9 gap-1.5" onClick={load}><RefreshCcw className="size-3.5" /> Refresh</Button>
          </div>
        }
      />

      {/* headline stats */}
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <Card className="border-l-4 border-l-indigo-500">
          <CardContent className="pt-4">
            <div className="text-2xl font-bold">{teachers.length}</div>
            <p className="text-xs text-muted-foreground flex items-center gap-1"><Users className="size-3" /> teaching staff on the grid</p>
          </CardContent>
        </Card>
        <Card className={cn("border-l-4", data.clashes.length ? "border-l-amber-500" : "border-l-emerald-500")}>
          <CardContent className="pt-4">
            <div className="text-2xl font-bold">{data.clashes.length}</div>
            <p className="text-xs text-muted-foreground flex items-center gap-1"><TriangleAlert className="size-3" /> double-booked periods this week</p>
          </CardContent>
        </Card>
        <Card className={cn("border-l-4", data.absentees.length ? "border-l-rose-500" : "border-l-emerald-500")}>
          <CardContent className="pt-4">
            <div className="text-2xl font-bold">{data.absentees.length}</div>
            <p className="text-xs text-muted-foreground flex items-center gap-1"><UserX className="size-3" /> absent / on leave on {dayName}</p>
          </CardContent>
        </Card>
        <Card className="border-l-4 border-l-teal-500">
          <CardContent className="pt-4">
            <div className="text-2xl font-bold">{data.covers.length}</div>
            <p className="text-xs text-muted-foreground flex items-center gap-1"><UserCheck className="size-3" /> covers locked for {dayName}</p>
          </CardContent>
        </Card>
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="grid w-full grid-cols-2 sm:w-96">
          <TabsTrigger value="teachers" className="gap-1.5"><CalendarClock className="size-3.5" /> Teacher grids</TabsTrigger>
          <TabsTrigger value="cover" className="gap-1.5"><UserCheck className="size-3.5" /> Cover planner{data.absentees.length ? ` (${data.absentees.length})` : ""}</TabsTrigger>
        </TabsList>

        {/* ── TEACHER GRIDS ── */}
        <TabsContent value="teachers" className="space-y-4 mt-4">
          <Card>
            <CardContent className="pt-4 flex flex-wrap items-center gap-3">
              <Select value={teacherId} onValueChange={setTeacherId}>
                <SelectTrigger className="w-64 h-9" aria-label="Pick a teacher"><SelectValue placeholder="Pick a teacher" /></SelectTrigger>
                <SelectContent>
                  {teachers.map((s) => (
                    <SelectItem key={s.id} value={s.id}>{s.name}{s.subjects.length ? ` · ${s.subjects.slice(0, 2).join(", ")}` : ""}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {teacher && (
                <div className="flex flex-wrap gap-2 text-xs">
                  <Badge variant="outline" className="gap-1"><Layers className="size-3" /> {teacherSlots.length} periods / week</Badge>
                  <Badge variant="outline" className="gap-1"><Layers className="size-3" /> {new Set(teacherSlots.map((s) => s.classroomId)).size} classes</Badge>
                  <Badge variant="outline" className="gap-1"><CalendarDays className="size-3" /> free days: {5 - new Set(teacherSlots.map((s) => s.dayOfWeek)).size}</Badge>
                  {teacher.subjects.length > 0 && <Badge variant="outline" className="gap-1"><Sparkles className="size-3" /> {teacher.subjects.join(", ")}</Badge>}
                </div>
              )}
            </CardContent>
          </Card>

          {myClashes.length > 0 && (
            <Card className="border-amber-300 bg-amber-50/60 dark:bg-amber-950/20">
              <CardContent className="pt-4 space-y-2">
                <div className="font-semibold text-sm flex items-center gap-1.5 text-amber-700 dark:text-amber-400"><TriangleAlert className="size-4" /> {teacher?.name} is double-booked in {myClashes.length} period{myClashes.length > 1 ? "s" : ""}</div>
                {myClashes.map((c, i) => (
                  <p key={i} className="text-xs text-amber-700 dark:text-amber-400">
                    {DAYS.find((d) => d.n === c.day)?.label} {hhmm(c.a.startMinute)}–{hhmm(c.a.endMinute)}: {c.a.classroomName} ({c.a.subjectName}) ↔ {c.b.classroomName} ({c.b.subjectName})
                  </p>
                ))}
                <p className="text-[11px] text-muted-foreground">Fix the routine in Classes & Timetable — one of the two lessons needs another teacher or another period.</p>
              </CardContent>
            </Card>
          )}

          {teacher ? (
            <Card>
              <CardContent className="pt-4 overflow-x-auto">
                <p className="text-xs text-muted-foreground mb-3">{teacher.name}&apos;s week — every lesson across every class</p>
                {gridStarts.length === 0 ? (
                  <EmptyState icon={<CalendarClock className="size-5" />} title="No lessons on this teacher's grid" hint="Assign periods in Classes & Timetable to build the routine." />
                ) : (
                  <table className="w-full min-w-[640px] border-collapse text-xs">
                    <thead>
                      <tr>
                        <th className="p-2 text-left text-muted-foreground font-medium w-24">Period</th>
                        {DAYS.map((d) => <th key={d.n} className="p-2 text-left text-muted-foreground font-medium">{d.label}</th>)}
                      </tr>
                    </thead>
                    <tbody>
                      {gridStarts.map((start) => {
                        const rowSlot = teacherSlots.find((s) => s.startMinute === start);
                        const clashHere = myClashes.filter((c) => c.a.startMinute === start || c.b.startMinute === start);
                        return (
                          <tr key={start} className="border-t">
                            <td className="p-2 whitespace-nowrap text-muted-foreground">{hhmm(start)}</td>
                            {DAYS.map((d) => {
                              const slot = teacherSlots.find((s) => s.dayOfWeek === d.n && s.startMinute === start);
                              const clashing = clashHere.some((c) => (c.a.dayOfWeek === d.n && c.a.startMinute === start) || (c.b.dayOfWeek === d.n && c.b.startMinute === start));
                              return (
                                <td key={d.n} className="p-1.5">
                                  {slot ? (
                                    <div className={cn("rounded-md border px-2.5 py-2", clashing ? "border-amber-400 bg-amber-50 dark:bg-amber-950/40" : "border-indigo-200 bg-indigo-50/70 dark:border-indigo-900 dark:bg-indigo-950/40")}>
                                      <div className="font-semibold leading-tight">{slot.subjectName}</div>
                                      <div className="text-[11px] text-muted-foreground">{slot.classroomName} · {hhmm(slot.startMinute)}–{hhmm(slot.endMinute)}</div>
                                    </div>
                                  ) : (
                                    <div className="rounded-md border border-dashed px-2.5 py-2 text-[11px] text-muted-foreground/60">—</div>
                                  )}
                                </td>
                              );
                            })}
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                )}
              </CardContent>
            </Card>
          ) : (
            <EmptyState icon={<Users className="size-5" />} title="No teaching staff yet" hint="Add teaching staff in Settings → Staff & Users Setup." />
          )}

          {/* school-wide clash report */}
          <Card>
            <CardContent className="pt-4 space-y-3">
              <div className="font-semibold text-sm flex items-center gap-1.5"><TriangleAlert className={cn("size-4", data.clashes.length ? "text-amber-500" : "text-emerald-500")} /> Clash report — whole school, whole week</div>
              {data.clashes.length === 0 ? (
                <p className="text-xs text-muted-foreground">No double-booked periods anywhere on the grid. The routine is clean.</p>
              ) : (
                <div className="space-y-2">
                  {data.clashes.map((c, i) => (
                    <div key={i} className="flex flex-wrap items-center gap-2 text-xs rounded-md border border-amber-200 bg-amber-50/60 dark:border-amber-900 dark:bg-amber-950/20 px-3 py-2">
                      <Badge variant="outline" className="text-amber-700 dark:text-amber-400">{DAYS.find((d) => d.n === c.day)?.label}</Badge>
                      <span className="font-medium">{c.staffName}</span>
                      <span className="text-muted-foreground">{hhmm(c.a.startMinute)}–{hhmm(c.a.endMinute)} · {c.a.classroomName} ({c.a.subjectName}) ↔ {c.b.classroomName} ({c.b.subjectName})</span>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── COVER PLANNER ── */}
        <TabsContent value="cover" className="space-y-4 mt-4">
          {alertOut && (
            <Card className="border-teal-300 bg-teal-50/60 dark:bg-teal-950/20">
              <CardContent className="pt-4 flex items-start gap-2">
                <BellRing className="size-4 text-teal-600 mt-0.5" />
                <div className="text-sm flex-1">
                  <span className="font-semibold">{alertOut.staff} has been alerted</span>
                  <span className="text-muted-foreground"> — the cover rides their timetable for the day.</span>
                  <div className="mt-1.5 flex flex-wrap gap-1.5 text-[11px]">
                    {alertOut.sms !== "SKIPPED" && <Badge variant="outline" className="gap-1"><Smartphone className="size-3" /> SMS {alertOut.sms}</Badge>}
                    {alertOut.email !== "SKIPPED" && <Badge variant="outline" className="gap-1"><Mail className="size-3" /> Email {alertOut.email}</Badge>}
                    <Badge variant="outline" className="gap-1"><Inbox className="size-3" /> In-app bell ON</Badge>
                  </div>
                </div>
                <Button size="icon" variant="ghost" className="size-6" aria-label="Dismiss" onClick={() => setAlertOut(null)}><X className="size-3.5" /></Button>
              </CardContent>
            </Card>
          )}

          {!data.attendanceMarked && (
            <Card className="border-dashed">
              <CardContent className="pt-4 flex items-center gap-2 text-sm text-muted-foreground">
                <Info className="size-4 shrink-0" />
                No staff attendance has been marked for {dayName} yet — the planner reads the Staff Attendance book. Mark it in <span className="font-medium text-foreground">Staff Attendance &amp; Duty → Daily staff register</span> and this page fills itself in.
              </CardContent>
            </Card>
          )}

          {data.absentees.length === 0 && data.attendanceMarked && (
            <EmptyState icon={<CircleCheck className="size-5" />} title="Nobody absent — every class has its teacher" hint={`The ${dayName} attendance book shows no ABSENT or LEAVE rows.`} />
          )}

          {data.absentees.map((ab) => (
            <Card key={ab.staffId}>
              <CardContent className="pt-4 space-y-3">
                <div className="flex flex-wrap items-center gap-2">
                  <div className="font-semibold text-sm">{ab.name}</div>
                  <Badge variant={ab.status === "LEAVE" ? "outline" : "destructive"} className="text-[10px]">{ab.status === "LEAVE" ? "ON LEAVE" : "ABSENT"}</Badge>
                  {ab.note && <span className="text-xs text-muted-foreground">“{ab.note}”</span>}
                  <Badge variant="outline" className="text-[10px] gap-1"><Clock3 className="size-3" /> {ab.affected.length} lesson{ab.affected.length === 1 ? "" : "s"} on {dayName}</Badge>
                </div>

                {ab.affected.length === 0 && (
                  <p className="text-xs text-muted-foreground">No timetabled lessons on {dayName} — nothing to cover.</p>
                )}

                <div className="space-y-3">
                  {ab.affected.map((lesson) => {
                    const cover = data.covers.find((c) => c.slotId === lesson.id);
                    const sugg = data.suggestions[lesson.id] ?? [];
                    const extras = freeStaffFor(lesson).filter((s) => !sugg.some((g) => g.id === s.id));
                    return (
                      <div key={lesson.id} className={cn("rounded-lg border p-3", cover ? "border-emerald-200 bg-emerald-50/50 dark:border-emerald-900 dark:bg-emerald-950/20" : "")}>
                        <div className="flex flex-wrap items-center gap-2 text-sm">
                          <Badge variant="outline" className="font-mono text-[10px]">{hhmm(lesson.startMinute)}–{hhmm(lesson.endMinute)}</Badge>
                          <span className="font-medium">{lesson.subjectName}</span>
                          <span className="text-xs text-muted-foreground">{lesson.classroomName}</span>
                          {cover ? (
                            <Badge className="bg-emerald-600 text-[10px] gap-1"><UserCheck className="size-3" /> covered by {cover.substitute}</Badge>
                          ) : (
                            <Badge variant="destructive" className="text-[10px]">needs cover</Badge>
                          )}
                        </div>

                        {cover ? (
                          <div className="mt-2 flex items-center justify-between">
                            <p className="text-xs text-muted-foreground">{cover.periods} · logged by the cover planner{cover.note ? ` · ${cover.note}` : ""}</p>
                            <Button size="sm" variant="ghost" className="h-7 text-xs text-rose-600" disabled={busy} onClick={() => cancelCover(cover.id)}><X className="size-3" /> Remove cover</Button>
                          </div>
                        ) : (
                          <div className="mt-2.5 space-y-2">
                            {sugg.length > 0 ? (
                              <div className="flex flex-wrap gap-2">
                                {sugg.map((g, gi) => (
                                  <div key={g.id} className={cn("flex items-center gap-2 rounded-md border px-2.5 py-1.5", gi === 0 ? "border-indigo-300 bg-indigo-50/70 dark:border-indigo-800 dark:bg-indigo-950/40" : "")}>
                                    <div className="text-xs">
                                      <div className="font-medium leading-tight">{g.name}{gi === 0 && <span className="ml-1 text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold">best match</span>}</div>
                                      <div className="text-[10px] text-muted-foreground">{g.reasons.join(" · ") || `${g.loadThatDay} lesson${g.loadThatDay === 1 ? "" : "s"} that day`}</div>
                                    </div>
                                    <Button size="sm" className="h-7 px-2.5 text-xs gap-1" disabled={busy} onClick={() => assignCover(lesson, ab, g.id)}><UserCheck className="size-3.5" /> Assign</Button>
                                  </div>
                                ))}
                              </div>
                            ) : (
                              <p className="text-xs text-amber-700 dark:text-amber-400">Every teacher is booked in that period — assembly, duty or a double lesson. Consider merging classes or scheduling the lesson elsewhere.</p>
                            )}
                            {extras.length > 0 && (
                              <div className="flex items-center gap-2">
                                <Select value={fallback?.lessonId === lesson.id ? fallback.staffId : ""} onValueChange={(v) => setFallback({ lessonId: lesson.id, staffId: v })}>
                                  <SelectTrigger className="w-56 h-8 text-xs" aria-label="Pick another teacher"><SelectValue placeholder="Or pick any other teacher…" /></SelectTrigger>
                                  <SelectContent>
                                    {extras.map((s) => <SelectItem key={s.id} value={s.id} className="text-xs">{s.name}{s.subjects.length ? ` · ${s.subjects.slice(0, 2).join(", ")}` : ""}</SelectItem>)}
                                  </SelectContent>
                                </Select>
                                <Button size="sm" variant="outline" className="h-8 text-xs gap-1" disabled={busy || fallback?.lessonId !== lesson.id || !fallback.staffId}
                                  onClick={() => fallback && assignCover(lesson, ab, fallback.staffId)}><UserCheck className="size-3.5" /> Assign anyway</Button>
                              </div>
                            )}
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
              </CardContent>
            </Card>
          ))}

          {data.covers.length > 0 && (
            <Card>
              <CardContent className="pt-4 space-y-2">
                <div className="font-semibold text-sm flex items-center gap-1.5"><Inbox className="size-4 text-teal-500" /> Cover sheet — {dayName} {date}</div>
                <table className="w-full text-xs">
                  <thead>
                    <tr className="border-b text-left text-muted-foreground">
                      <th className="py-1.5 pr-3 font-medium">Lesson</th>
                      <th className="py-1.5 pr-3 font-medium">Class</th>
                      <th className="py-1.5 pr-3 font-medium">Time</th>
                      <th className="py-1.5 pr-3 font-medium">Absent</th>
                      <th className="py-1.5 pr-3 font-medium">Covered by</th>
                      <th className="py-1.5 font-medium"></th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.covers.map((c) => (
                      <tr key={c.id} className="border-b last:border-0">
                        <td className="py-1.5 pr-3 font-medium">{c.slot?.subjectName ?? c.classRef}</td>
                        <td className="py-1.5 pr-3">{c.classRef?.split(" · ")[0]}</td>
                        <td className="py-1.5 pr-3 font-mono">{c.periods}</td>
                        <td className="py-1.5 pr-3">{c.absent}</td>
                        <td className="py-1.5 pr-3 font-medium text-emerald-700 dark:text-emerald-400">{c.substitute}</td>
                        <td className="py-1.5 text-right">
                          {c.slotId && <Button size="sm" variant="ghost" className="h-6 px-1.5 text-[11px] text-rose-600" disabled={busy} onClick={() => cancelCover(c.id)}><X className="size-3" /></Button>}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                <p className="text-[11px] text-muted-foreground">Print this as the day&apos;s cover sheet for the staff room noticeboard — every substitution is also logged in Staff Attendance &amp; Duty.</p>
              </CardContent>
            </Card>
          )}
        </TabsContent>
      </Tabs>
    </div>
  );
}

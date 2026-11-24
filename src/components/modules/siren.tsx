"use client";
// ── Siren & Bells (wave 55 — the electronic school bell) ──
// Ghanaian schools run on bells: assembly, change of lesson, break, closing —
// and an emergency siren. This module replaces the hand bell with a
// synthesised, logged, schedule-driven system:
//   • Live console — big clock, next-ring countdown, one-tap sound board
//   • Schedule — timetabled rings with day-of-week, sound & duration
//   • History — every firing (manual / scheduled / test) with operator trail
// A global <SirenTicker /> keeps timetabled rings firing from any open
// console (front-desk machine), with server-side per-minute de-duplication.
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard, FormGrid, FormField } from "@/components/ui-helpers";
import { fmtDateTime } from "@/lib/format";
import { useToast } from "@/hooks/use-toast";
import { DAY_NAMES, SIREN_SOUNDS, playSirenSound, sirenSoundLabel, type SirenSound } from "@/lib/siren-sounds";
import { cn } from "@/lib/utils";
import {
  AlarmClock, Bell, Siren as SirenIcon, History, Play, Square, Plus, Pencil, Trash2, Zap,
  Clock3, CalendarDays, Volume2, TriangleAlert, CircleCheck, Timer,
} from "lucide-react";

const OPS = "/ops/siren";
const KIND_LABEL: Record<string, string> = { MANUAL: "Manual", SCHEDULED: "Timetable", TEST: "Sound test" };

type Schedule = {
  id: string; label: string; time: string; days: number[]; sound: string;
  durationSec: number; enabled: boolean; note: string | null;
};
type SirenEventRow = {
  id: string; scheduleId: string | null; label: string; sound: string; durationSec: number;
  kind: string; byName: string; byRole: string | null; note: string | null; createdAt: string;
};
type SirenData = {
  ok: boolean;
  schedules: Schedule[];
  events: SirenEventRow[];
  stats: { totalSchedules: number; enabledSchedules: number; nextRing: { id: string; label: string; time: string; sound: string } | null; firedToday: number; firedTotal: number };
};

const DURATIONS = [5, 10, 15, 20, 30, 60];
const timeToMins = (t: string) => Number(t.slice(0, 2)) * 60 + Number(t.slice(3, 5));
const minsToClock = (m: number) => `${String(Math.floor(m / 60) % 24).padStart(2, "0")}:${String(m % 60).padStart(2, "0")}`;

/** "Normal Ghanaian school day" starter timetable (wave 55 preset). */
const PRESETS: { label: string; time: string; days: number[]; sound: SirenSound; durationSec: number }[] = [
  { label: "Morning assembly", time: "07:45", days: [1, 2, 3, 4, 5], sound: "CHIME", durationSec: 20 },
  { label: "Assembly ends — line up", time: "08:00", days: [1, 2, 3, 4, 5], sound: "BELL", durationSec: 10 },
  { label: "Mid-morning break", time: "10:00", days: [1, 2, 3, 4, 5], sound: "BELL", durationSec: 15 },
  { label: "Break ends", time: "10:20", days: [1, 2, 3, 4, 5], sound: "BELL", durationSec: 10 },
  { label: "Lunch", time: "12:30", days: [1, 2, 3, 4, 5], sound: "BELL", durationSec: 15 },
  { label: "Closing time", time: "15:00", days: [1, 2, 3, 4, 5], sound: "SIREN", durationSec: 20 },
];

const emptyForm = { id: "", label: "", time: "07:45", days: [1, 2, 3, 4, 5] as number[], sound: "BELL" as SirenSound, durationSec: 10, note: "", enabled: true };

export function Siren() {
  const { currentSchoolId, navTarget } = useStore();
  const { toast } = useToast();
  const schoolId = currentSchoolId ?? "";
  const [data, setData] = useState<SirenData | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [tab, setTab] = useState("live");
  const [now, setNow] = useState(new Date());
  const [playing, setPlaying] = useState<SirenSound | null>(null);
  const [playKind, setPlayKind] = useState<"MANUAL" | "TEST">("MANUAL");
  const [duration, setDuration] = useState(10);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [form, setForm] = useState(emptyForm);
  const [confirmDelete, setConfirmDelete] = useState<Schedule | null>(null);
  const [confirmClear, setConfirmClear] = useState(false);
  const [kindFilter, setKindFilter] = useState("ALL");
  const stopRef = useRef<(() => void) | null>(null);

  useEffect(() => {
    if (navTarget?.tab) setTab(navTarget.tab);
  }, [navTarget]);

  const load = useCallback(async () => {
    if (!schoolId) return;
    setLoading(true);
    try { setData(await apiGet<SirenData>(`/api/school/${schoolId}${OPS}`)); }
    catch (e: any) { toast({ title: "Could not load siren data", description: e.message, variant: "destructive" }); }
    finally { setLoading(false); }
  }, [schoolId, toast]);

  useEffect(() => { load(); }, [load]);
  useEffect(() => {
    const t = window.setInterval(() => setNow(new Date()), 1000);
    return () => window.clearInterval(t);
  }, []);

  const act = useCallback(async (body: Record<string, unknown>, successMsg: string) => {
    if (!schoolId) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}${OPS}`, body);
      toast({ title: successMsg });
      await load();
    } catch (e: any) {
      toast({ title: "Action failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }, [schoolId, load, toast]);

  /** Play a sound through the speaker and log the firing. */
  const ring = useCallback(async (sound: SirenSound, dur: number, kind: "MANUAL" | "TEST", label?: string) => {
    if (playing) return;
    setPlaying(sound);
    setPlayKind(kind);
    stopRef.current = playSirenSound(sound, dur);
    window.setTimeout(() => { setPlaying(null); stopRef.current = null; }, dur * 1000);
    if (!schoolId) return;
    try { await apiPost(`/api/school/${schoolId}${OPS}`, { action: "ring", sound, durationSec: dur, kind, label }); await load(); }
    catch { /* sound still played; log failed silently */ }
  }, [playing, schoolId, load]);

  const stopAll = useCallback(() => {
    stopRef.current?.();
    stopRef.current = null;
    setPlaying(null);
  }, []);

  // ── in-console timetable arming: rings due schedules while this page is open
  const schedules = data?.schedules ?? [];
  useEffect(() => {
    if (!schoolId || !schedules.length) return;
    const tick = () => {
      const d = new Date();
      const hhmm = `${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
      const keyBase = `${d.getFullYear()}${d.getMonth()}${d.getDate()}`;
      for (const s of schedules) {
        if (!s.enabled || s.time !== hhmm || !s.days.includes(d.getDay())) continue;
        const guardKey = `siren-fired-${s.id}-${keyBase}-${hhmm}`;
        if (localStorage.getItem(guardKey)) continue;
        localStorage.setItem(guardKey, "1");
        playSirenSound(s.sound as SirenSound, s.durationSec);
        void apiPost(`/api/school/${schoolId}${OPS}`, { action: "ring", scheduleId: s.id, sound: s.sound, durationSec: s.durationSec, kind: "SCHEDULED", label: s.label }).catch(() => {});
      }
    };
    tick();
    const t = window.setInterval(tick, 15_000);
    return () => window.clearInterval(t);
  }, [schoolId, schedules]);

  const openNew = () => { setForm(emptyForm); setDialogOpen(true); };
  const openEdit = (s: Schedule) => {
    setForm({ id: s.id, label: s.label, time: s.time, days: s.days, sound: s.sound as SirenSound, durationSec: s.durationSec, note: s.note ?? "", enabled: s.enabled });
    setDialogOpen(true);
  };
  const saveSchedule = async () => {
    if (!form.label.trim()) { toast({ title: "Label is required", variant: "destructive" }); return; }
    await act(
      { action: "schedule.save", id: form.id || undefined, label: form.label, time: form.time, days: form.days, sound: form.sound, durationSec: form.durationSec, note: form.note, enabled: form.enabled },
      form.id ? "Schedule updated" : "Schedule created",
    );
    setDialogOpen(false);
  };
  const applyPreset = async () => {
    if (!schoolId) return;
    setBusy(true);
    try {
      for (const p of PRESETS) {
        await apiPost(`/api/school/${schoolId}${OPS}`, { action: "schedule.save", label: p.label, time: p.time, days: p.days, sound: p.sound, durationSec: p.durationSec, enabled: true });
      }
      toast({ title: "Normal school-day timetable applied", description: `${PRESETS.length} rings created` });
      await load();
    } catch (e: any) {
      toast({ title: "Could not apply preset", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  };

  const clock = `${String(now.getHours()).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}:${String(now.getSeconds()).padStart(2, "0")}`;
  const todaySchedule = useMemo(() => {
    const idx = now.getDay();
    return schedules.filter((s) => s.enabled && s.days.includes(idx)).sort((a, b) => timeToMins(a.time) - timeToMins(b.time));
  }, [schedules, now]);
  const nowMins = now.getHours() * 60 + now.getMinutes();
  const nextLocal = todaySchedule.find((s) => timeToMins(s.time) >= nowMins);
  const events = (data?.events ?? []).filter((e) => kindFilter === "ALL" || e.kind === kindFilter);

  if (loading && !data) return <LoadingBlock label="Loading siren console…" />;
  if (!data) return <EmptyState icon={<AlarmClock />} title="Siren console unavailable" hint="Try reloading the module." />;

  return (
    <div className="space-y-4">
      <ModuleHeader
        title="Siren &amp; Bells"
        description="The electronic school bell — timetabled rings, a live sound console and an emergency siren, with a full firing history."
        actions={
          <div className="flex items-center gap-2">
            <Badge className={cn("gap-1 text-white", playing ? "bg-amber-600" : "bg-emerald-600")}>
              {playing ? <Volume2 className="size-3 animate-pulse" /> : <CircleCheck className="size-3" />}
              {playing ? `Ringing — ${sirenSoundLabel(playing)}` : "Console armed"}
            </Badge>
            {playing && (
              <Button size="sm" variant="destructive" onClick={stopAll}><Square className="size-3 mr-1" /> Stop sound</Button>
            )}
          </div>
        }
      />

      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <StatCard icon={<CalendarDays className="size-4" />} label="Timetable rings" value={`${data.stats.enabledSchedules}/${data.stats.totalSchedules}`} sub="enabled of scheduled" />
        <StatCard icon={<Clock3 className="size-4" />} label="Next ring today" value={nextLocal?.time ?? "—"} sub={nextLocal?.label ?? "no more rings today"} tone={nextLocal ? "primary" : "default"} />
        <StatCard icon={<Zap className="size-4" />} label="Fired today" value={data.stats.firedToday} sub="rings & sirens" />
        <StatCard icon={<History className="size-4" />} label="Total firings" value={data.stats.firedTotal} sub="last 120 shown" />
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex flex-wrap h-auto gap-1">
          <TabsTrigger value="live">Live console</TabsTrigger>
          <TabsTrigger value="schedule">Timetable ({schedules.length})</TabsTrigger>
          <TabsTrigger value="history">History ({data.stats.firedTotal})</TabsTrigger>
        </TabsList>

        {/* ── LIVE CONSOLE ── */}
        <TabsContent value="live" className="space-y-4 mt-4">
          <div className="grid gap-4 lg:grid-cols-3">
            <Card>
              <CardContent className="p-6 flex flex-col items-center justify-center gap-2">
                <div className="text-4xl font-bold tabular-nums tracking-tight">{clock}</div>
                <div className="text-sm text-muted-foreground">
                  {now.toLocaleDateString(undefined, { weekday: "long", day: "numeric", month: "long", year: "numeric" })}
                </div>
                {nextLocal ? (
                  <div className="mt-2 text-center">
                    <div className="text-xs uppercase tracking-wide text-muted-foreground">Next ring</div>
                    <div className="text-lg font-semibold">{nextLocal.time} — {nextLocal.label}</div>
                    <div className="text-xs text-muted-foreground">
                      in {Math.max(0, timeToMins(nextLocal.time) - nowMins)} min · {sirenSoundLabel(nextLocal.sound)}
                    </div>
                  </div>
                ) : (
                  <div className="mt-2 text-sm text-muted-foreground">No more rings scheduled today</div>
                )}
              </CardContent>
            </Card>

            <Card className="lg:col-span-2 border-destructive/40">
              <CardContent className="p-6 space-y-3">
                <div className="flex items-center gap-2">
                  <TriangleAlert className="size-4 text-destructive" />
                  <div className="font-semibold">Emergency siren</div>
                  <Badge variant="outline" className="ml-auto text-xs">rings instantly &amp; is logged</Badge>
                </div>
                <p className="text-sm text-muted-foreground">
                  Fire-and-forget: every open console speaker sounds the wail for the full duration. Use for
                  emergencies, fire drills and closing time.
                </p>
                <div className="flex flex-wrap items-center gap-2">
                  <Button variant="destructive" size="lg" className="h-12 px-6 text-base" disabled={!!playing}
                    onClick={() => ring("SIREN", 30, "MANUAL", "Emergency siren")}>
                    <SirenIcon className="size-5 mr-2" /> Sound the siren (30s)
                  </Button>
                  <Button variant="outline" size="lg" className="h-12" disabled={!!playing}
                    onClick={() => ring("KLAXON", 15, "MANUAL", "Attention klaxon")}>
                    Klaxon (15s)
                  </Button>
                </div>
              </CardContent>
            </Card>
          </div>

          <Card>
            <CardContent className="p-6 space-y-4">
              <div className="flex flex-wrap items-center gap-3">
                <div className="font-semibold flex items-center gap-2"><Play className="size-4" /> Sound board</div>
                <div className="flex items-center gap-2 ml-auto">
                  <Label className="text-xs text-muted-foreground">Duration</Label>
                  <Select value={String(duration)} onValueChange={(v) => setDuration(Number(v))}>
                    <SelectTrigger className="w-24 h-8"><SelectValue /></SelectTrigger>
                    <SelectContent>{DURATIONS.map((d) => <SelectItem key={d} value={String(d)}>{d}s</SelectItem>)}</SelectContent>
                  </Select>
                  <div className="flex items-center gap-1.5 text-xs">
                    <Switch checked={playKind === "TEST"} onCheckedChange={(v) => setPlayKind(v ? "TEST" : "MANUAL")} id="test-mode" />
                    <Label htmlFor="test-mode" className="text-muted-foreground">log as sound test</Label>
                  </div>
                </div>
              </div>
              <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                {SIREN_SOUNDS.map((s) => (
                  <button
                    key={s.id}
                    type="button"
                    disabled={!!playing}
                    onClick={() => ring(s.id, duration, playKind)}
                    className={cn(
                      "group rounded-lg border p-4 text-left transition-all hover:shadow-md hover:border-primary/40 disabled:opacity-50 focus-visible:outline-2 focus-visible:outline-primary",
                      playing === s.id && "border-primary bg-primary/5 shadow-md",
                    )}
                  >
                    <div className="flex items-center gap-2">
                      <span className="inline-flex size-9 items-center justify-center rounded-full bg-primary/10 text-primary">
                        {s.id === "SIREN" ? <SirenIcon className="size-4" /> : s.id === "KLAXON" ? <TriangleAlert className="size-4" /> : <Bell className="size-4" />}
                      </span>
                      <div className="font-medium text-sm">{s.label}</div>
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground leading-relaxed">{s.hint}</p>
                    <div className="mt-3 flex items-center gap-1 text-xs text-primary font-medium">
                      <Play className="size-3" /> Ring {duration}s
                    </div>
                  </button>
                ))}
              </div>
              {playing && (
                <div className="flex items-center gap-2 rounded-md bg-amber-500/10 border border-amber-500/30 px-3 py-2 text-sm">
                  <Volume2 className="size-4 text-amber-600 animate-pulse" />
                  Ringing <b>{sirenSoundLabel(playing)}</b> as {KIND_LABEL[playKind]} — <button className="underline" onClick={stopAll}>stop now</button>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── TIMETABLE ── */}
        <TabsContent value="schedule" className="space-y-4 mt-4">
          <div className="flex flex-wrap items-center gap-2">
            <Button onClick={openNew}><Plus className="size-4 mr-1" /> Add ring</Button>
            <Button variant="outline" onClick={applyPreset} disabled={busy}>
              <Timer className="size-4 mr-1" /> Apply normal school day (6 rings)
            </Button>
            <span className="text-xs text-muted-foreground ml-auto">Rings fire from any open console — front desk, staff room or office.</span>
          </div>

          {schedules.length === 0 ? (
            <EmptyState
              icon={<CalendarDays />}
              title="No rings on the timetable"
              hint="Add a ring, or apply the 'normal school day' preset to get a full Ghanaian school-day timetable in one tap."
            />
          ) : (
            <Card>
              <CardContent className="p-0">
                <div className="max-h-[540px] overflow-y-auto">
                  <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-muted/90 backdrop-blur">
                      <tr className="text-left text-muted-foreground">
                        <th className="px-4 py-2.5 font-medium">Ring</th>
                        <th className="px-4 py-2.5 font-medium">Time</th>
                        <th className="px-4 py-2.5 font-medium">Days</th>
                        <th className="px-4 py-2.5 font-medium">Sound</th>
                        <th className="px-4 py-2.5 font-medium">On?</th>
                        <th className="px-4 py-2.5 font-medium text-right">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {schedules.map((s) => (
                        <tr key={s.id} className={cn("border-t", !s.enabled && "opacity-55")}>
                          <td className="px-4 py-2.5">
                            <div className="font-medium">{s.label}</div>
                            {s.note && <div className="text-xs text-muted-foreground">{s.note}</div>}
                          </td>
                          <td className="px-4 py-2.5 tabular-nums font-semibold">{s.time}</td>
                          <td className="px-4 py-2.5">
                            <div className="flex flex-wrap gap-0.5">
                              {s.days.map((d) => <Badge key={d} variant="outline" className="text-[10px] px-1.5">{DAY_NAMES[d]}</Badge>)}
                            </div>
                          </td>
                          <td className="px-4 py-2.5">{sirenSoundLabel(s.sound)}<span className="text-xs text-muted-foreground"> · {s.durationSec}s</span></td>
                          <td className="px-4 py-2.5">
                            <Switch checked={s.enabled} onCheckedChange={(v) => act({ action: "schedule.toggle", id: s.id, enabled: v }, v ? "Ring enabled" : "Ring disabled")} />
                          </td>
                          <td className="px-4 py-2.5 text-right">
                            <div className="flex justify-end gap-1">
                              <Button variant="ghost" size="icon" className="size-7" onClick={() => openEdit(s)} aria-label={`Edit ${s.label}`}><Pencil className="size-3.5" /></Button>
                              <Button variant="ghost" size="icon" className="size-7 text-destructive" onClick={() => setConfirmDelete(s)} aria-label={`Delete ${s.label}`}><Trash2 className="size-3.5" /></Button>
                            </div>
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

        {/* ── HISTORY ── */}
        <TabsContent value="history" className="space-y-4 mt-4">
          <div className="flex flex-wrap items-center gap-2">
            <Select value={kindFilter} onValueChange={setKindFilter}>
              <SelectTrigger className="w-44 h-9"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="ALL">All firings</SelectItem>
                <SelectItem value="SCHEDULED">Timetable rings</SelectItem>
                <SelectItem value="MANUAL">Manual rings</SelectItem>
                <SelectItem value="TEST">Sound tests</SelectItem>
              </SelectContent>
            </Select>
            <Button variant="outline" className="text-destructive" onClick={() => setConfirmClear(true)}>
              <Trash2 className="size-4 mr-1" /> Clear history
            </Button>
            <span className="text-xs text-muted-foreground ml-auto">Newest first · last 120 firings</span>
          </div>

          {events.length === 0 ? (
            <EmptyState icon={<History />} title="No firings yet" hint="Manual rings, timetable rings and sound tests all land here." />
          ) : (
            <Card>
              <CardContent className="p-0">
                <div className="max-h-[540px] overflow-y-auto">
                  <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-muted/90 backdrop-blur">
                      <tr className="text-left text-muted-foreground">
                        <th className="px-4 py-2.5 font-medium">When</th>
                        <th className="px-4 py-2.5 font-medium">Ring</th>
                        <th className="px-4 py-2.5 font-medium">Sound</th>
                        <th className="px-4 py-2.5 font-medium">Kind</th>
                        <th className="px-4 py-2.5 font-medium">By</th>
                      </tr>
                    </thead>
                    <tbody>
                      {events.map((e) => (
                        <tr key={e.id} className="border-t">
                          <td className="px-4 py-2.5 whitespace-nowrap text-muted-foreground">{fmtDateTime(e.createdAt)}</td>
                          <td className="px-4 py-2.5 font-medium">
                            {e.label}
                            {e.note && <div className="text-xs text-muted-foreground">{e.note}</div>}
                          </td>
                          <td className="px-4 py-2.5">{sirenSoundLabel(e.sound)}<span className="text-xs text-muted-foreground"> · {e.durationSec}s</span></td>
                          <td className="px-4 py-2.5">
                            <Badge variant="outline" className={cn(
                              "text-[10px]",
                              e.kind === "SCHEDULED" && "border-emerald-300 text-emerald-700",
                              e.kind === "MANUAL" && "border-amber-300 text-amber-700",
                              e.kind === "TEST" && "border-slate-300 text-slate-600",
                            )}>
                              {KIND_LABEL[e.kind] ?? e.kind}
                            </Badge>
                          </td>
                          <td className="px-4 py-2.5">{e.byName}<span className="text-xs text-muted-foreground"> · {e.byRole?.replace(/_/g, " ").toLowerCase()}</span></td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>
      </Tabs>

      {/* ── add / edit ring dialog ── */}
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>{form.id ? "Edit ring" : "Add a ring"}</DialogTitle>
            <DialogDescription>Timetabled rings fire automatically from any open console on the stroke of the minute.</DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-1">
            <FormGrid cols={2}>
              <FormField label="Label" required>
                <Input value={form.label} onChange={(e) => setForm({ ...form, label: e.target.value })} placeholder="e.g. Morning assembly" maxLength={80} />
              </FormField>
              <FormField label="Time (24-hour)" required>
                <Input type="time" value={form.time} onChange={(e) => setForm({ ...form, time: e.target.value })} />
              </FormField>
              <FormField label="Sound">
                <Select value={form.sound} onValueChange={(v) => setForm({ ...form, sound: v as SirenSound })}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>{SIREN_SOUNDS.map((s) => <SelectItem key={s.id} value={s.id}>{s.label}</SelectItem>)}</SelectContent>
                </Select>
              </FormField>
              <FormField label="Duration">
                <Select value={String(form.durationSec)} onValueChange={(v) => setForm({ ...form, durationSec: Number(v) })}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>{[5, 10, 15, 20, 30, 45, 60].map((d) => <SelectItem key={d} value={String(d)}>{d} seconds</SelectItem>)}</SelectContent>
                </Select>
              </FormField>
            </FormGrid>
            <FormField label="Days">
              <div className="flex flex-wrap gap-1.5">
                {DAY_NAMES.map((d, i) => (
                  <button
                    key={d} type="button"
                    onClick={() => setForm((f) => ({ ...f, days: f.days.includes(i) ? f.days.filter((x) => x !== i) : [...f.days, i].sort((a, b) => a - b) }))}
                    className={cn(
                      "rounded-md border px-3 py-1.5 text-xs font-medium transition-colors",
                      form.days.includes(i) ? "bg-primary text-primary-foreground border-primary" : "bg-background hover:bg-muted",
                    )}
                  >
                    {d}
                  </button>
                ))}
                <button type="button" className="rounded-md border px-2 py-1.5 text-xs text-muted-foreground hover:bg-muted"
                  onClick={() => setForm((f) => ({ ...f, days: [1, 2, 3, 4, 5] }))}>Mon–Fri</button>
                <button type="button" className="rounded-md border px-2 py-1.5 text-xs text-muted-foreground hover:bg-muted"
                  onClick={() => setForm((f) => ({ ...f, days: [0, 1, 2, 3, 4, 5, 6] }))}>Every day</button>
              </div>
            </FormField>
            <FormField label="Note (optional)">
              <Textarea rows={2} value={form.note} onChange={(e) => setForm({ ...form, note: e.target.value })} placeholder="e.g. Ring twice during WASSCE weeks" maxLength={300} />
            </FormField>
            <div className="flex items-center gap-2">
              <Switch id="sched-enabled" checked={form.enabled} onCheckedChange={(v) => setForm({ ...form, enabled: v })} />
              <Label htmlFor="sched-enabled">Enabled</Label>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
            <Button onClick={saveSchedule} disabled={busy}>{form.id ? "Save changes" : "Add to timetable"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── delete confirm ── */}
      <AlertDialog open={!!confirmDelete} onOpenChange={(v) => !v && setConfirmDelete(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete this ring?</AlertDialogTitle>
            <AlertDialogDescription>
              {confirmDelete?.label} at {confirmDelete?.time} will be removed from the timetable. This cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Keep ring</AlertDialogCancel>
            <AlertDialogAction
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
              onClick={() => { if (confirmDelete) act({ action: "schedule.delete", id: confirmDelete.id }, "Ring deleted"); setConfirmDelete(null); }}
            >
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* ── clear history confirm ── */}
      <AlertDialog open={confirmClear} onOpenChange={setConfirmClear}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Clear the firing history?</AlertDialogTitle>
            <AlertDialogDescription>All {data.stats.firedTotal} firing records will be permanently removed. Timetabled rings are not affected.</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
              onClick={() => { act({ action: "history.clear" }, "History cleared"); setConfirmClear(false); }}
            >
              Clear history
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}

/* ─────────────────────────────────────────────────────────────
   Global ticker — fires timetabled rings from ANY page while a
   siren-enabled user is signed in (the whole machine becomes the
   bell). Server de-duplicates per schedule+minute, localStorage
   guards repeat fires on this machine. */
export function SirenTicker({ schoolId }: { schoolId: string | null | undefined }) {
  useEffect(() => {
    if (!schoolId) return;
    let alive = true;
    const fire = async () => {
      let data: SirenData;
      try { data = await apiGet<SirenData>(`/api/school/${schoolId}${OPS}`); } catch { return; }
      if (!alive || !data?.schedules?.length) return;
      const d = new Date();
      const hhmm = `${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
      const keyBase = `${d.getFullYear()}${d.getMonth()}${d.getDate()}`;
      for (const s of data.schedules) {
        if (!s.enabled || s.time !== hhmm || !s.days.includes(d.getDay())) continue;
        const guardKey = `siren-fired-${s.id}-${keyBase}-${hhmm}`;
        if (localStorage.getItem(guardKey)) continue;
        localStorage.setItem(guardKey, "1");
        playSirenSound(s.sound as SirenSound, s.durationSec);
        void apiPost(`/api/school/${schoolId}${OPS}`, { action: "ring", scheduleId: s.id, sound: s.sound, durationSec: s.durationSec, kind: "SCHEDULED", label: s.label }).catch(() => {});
      }
    };
    void fire();
    const t = window.setInterval(fire, 20_000);
    return () => { alive = false; window.clearInterval(t); };
  }, [schoolId]);
  return null;
}

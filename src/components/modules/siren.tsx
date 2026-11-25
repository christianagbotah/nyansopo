"use client";
// ── Siren Pro (wave 55 · 60 custom tones · 61 the full bell system) ──
// Ghanaian schools run on bells: assembly, change of lesson, break, closing —
// and an emergency siren. This module replaces the hand bell AND the
// standalone bell boxes schools buy, with everything they sell and more:
//   • Live console — big clock, next-ring countdown, one-tap sound board,
//     an exam & period timer that rings itself, and spoken PA announcements
//   • Day profiles — Normal / Exam / Event day timetables with one-tap switch
//   • Campus zones — tag rings to blocks; each console device picks its zone
//   • Period bells — one row repeats every N minutes (lesson changes)
//   • Per-ring volume + fade-in, vacation awareness, uploaded school anthems
//   • AI — describe the day in English and it builds the timetable; polish
//     PA announcements; a 7-day reliability report with missed-ring analysis
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
import { Slider } from "@/components/ui/slider";
import { Progress } from "@/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard, FormGrid, FormField } from "@/components/ui-helpers";
import { fmtDateTime } from "@/lib/format";
import { useToast } from "@/hooks/use-toast";
import { DAY_NAMES, SIREN_SOUNDS, playSchoolSound, sirenSoundLabel, CUSTOM_PREFIX, type SirenSound } from "@/lib/siren-sounds";
import { cn } from "@/lib/utils";
import { getHeaders } from "@/lib/api";
import {
  AlarmClock, Bell, Siren as SirenIcon, History, Play, Square, Plus, Pencil, Trash2, Zap,
  Clock3, CalendarDays, Volume2, TriangleAlert, CircleCheck, Timer, Music, Upload, FileAudio, Loader2,
  Sparkles, Megaphone, Hourglass, MapPin, Pause, RotateCcw, Check, X,
} from "lucide-react";

const OPS = "/ops/siren";
const KIND_LABEL: Record<string, string> = { MANUAL: "Manual", SCHEDULED: "Timetable", TEST: "Sound test", ANNOUNCEMENT: "PA announcement" };
const fmtMB = (b: number) => `${(b / 1024 / 1024).toFixed(1)} MB`;
const DEVICE_ZONE_KEY = "siren-device-zone";

/** fetch a custom tone's bytes for playback (session header + school scope). */
const toneFetcher = (schoolId: string) => async (toneId: string): Promise<ArrayBuffer> => {
  const res = await fetch(`/api/school/${schoolId}${OPS}?tone=${toneId}`, { headers: { ...getHeaders() }, cache: "force-cache" });
  if (!res.ok) throw new Error("tone unavailable");
  return res.arrayBuffer();
};

type Schedule = {
  id: string; label: string; time: string; days: number[]; sound: string;
  durationSec: number; enabled: boolean; note: string | null;
  profile: string; zone: string; volume: number; fadeIn: number;
  repeatEveryMin: number | null; endTime: string | null; skipVacation: boolean;
};
type Tone = {
  id: string; label: string; mimeType: string; sizeBytes: number;
  durationSec: number; createdByName: string; createdAt: string;
};
type SirenEventRow = {
  id: string; scheduleId: string | null; label: string; sound: string; durationSec: number;
  kind: string; byName: string; byRole: string | null; note: string | null; createdAt: string;
};
type SirenData = {
  ok: boolean;
  schedules: Schedule[];
  events: SirenEventRow[];
  tones: Tone[];
  activeProfile: string;
  profiles: string[];
  zones: string[];
  term: { inTerm: boolean; name: string | null };
  stats: { totalSchedules: number; enabledSchedules: number; nextRing: { id: string; label: string; time: string; sound: string } | null; firedToday: number; firedTotal: number };
};
type AiRing = {
  label: string; time: string; sound: string; durationSec: number; days: number[];
  note: string | null; repeatEveryMin: number | null; endTime: string | null;
  volume: number; fadeIn: number;
};
type Insights = {
  expected: number; fired: number; missedCount: number;
  missed: { date: string; time: string; label: string }[];
  narrative: string; tips: string[];
};

const DURATIONS = [5, 10, 15, 20, 30, 60];
const timeToMins = (t: string) => Number(t.slice(0, 2)) * 60 + Number(t.slice(3, 5));
const minsToClock = (m: number) => `${String(Math.floor(m / 60) % 24).padStart(2, "0")}:${String(m % 60).padStart(2, "0")}`;

/** Expand a ring into its occurrence minutes (period bells repeat every N min). */
function occurrencesOf(s: { time: string; repeatEveryMin: number | null; endTime: string | null }): number[] {
  const start = timeToMins(s.time);
  if (!s.repeatEveryMin) return [start];
  const limit = s.endTime ? timeToMins(s.endTime) : 24 * 60 - 1;
  const out: number[] = [];
  for (let m = start; m <= limit && out.length < 60; m += s.repeatEveryMin) out.push(m);
  return out;
}

/** zone routing — a device rings for campus-wide rings plus its own zone. */
const zoneMatches = (ringZone: string, deviceZone: string) =>
  ringZone === "All Campus" || deviceZone === "All Campus" || ringZone === deviceZone;

const getDeviceZone = (): string => {
  try { return localStorage.getItem(DEVICE_ZONE_KEY) || "All Campus"; } catch { return "All Campus"; }
};
const setDeviceZone = (z: string) => {
  try { localStorage.setItem(DEVICE_ZONE_KEY, z); } catch { /* private mode */ }
};

/** Rings due at `hhmm` today for this device (active profile, zone, vacation aware). */
function dueAt(data: SirenData, hhmm: string, dayIdx: number, deviceZone: string): { s: Schedule; at: string }[] {
  const out: { s: Schedule; at: string }[] = [];
  for (const s of data.schedules) {
    if (!s.enabled || s.profile !== (data.activeProfile ?? "Normal Day")) continue;
    if (!s.days.includes(dayIdx)) continue;
    if (s.skipVacation && data.term && !data.term.inTerm) continue;
    if (!zoneMatches(s.zone, deviceZone)) continue;
    for (const m of occurrencesOf(s)) {
      const at = minsToClock(m);
      if (at === hhmm) out.push({ s, at });
    }
  }
  return out;
}

/** "Normal Ghanaian school day" starter timetable (wave 55 preset). */
const PRESETS: { label: string; time: string; days: number[]; sound: SirenSound; durationSec: number }[] = [
  { label: "Morning assembly", time: "07:45", days: [1, 2, 3, 4, 5], sound: "CHIME", durationSec: 20 },
  { label: "Assembly ends — line up", time: "08:00", days: [1, 2, 3, 4, 5], sound: "BELL", durationSec: 10 },
  { label: "Mid-morning break", time: "10:00", days: [1, 2, 3, 4, 5], sound: "BELL", durationSec: 15 },
  { label: "Break ends", time: "10:20", days: [1, 2, 3, 4, 5], sound: "BELL", durationSec: 10 },
  { label: "Lunch", time: "12:30", days: [1, 2, 3, 4, 5], sound: "BELL", durationSec: 15 },
  { label: "Closing time", time: "15:00", days: [1, 2, 3, 4, 5], sound: "SIREN", durationSec: 20 },
];

const emptyForm = {
  id: "", label: "", time: "07:45", days: [1, 2, 3, 4, 5] as number[], sound: "BELL", durationSec: 10, note: "",
  enabled: true, profile: "Normal Day", zone: "All Campus", volume: 100, fadeIn: 0,
  repeatEveryMin: 0, endTime: "", skipVacation: true,
};

const PA_OCCASIONS = [
  { value: "early closing", label: "Early closing" },
  { value: "visitor", label: "Visitor announcement" },
  { value: "emergency drill", label: "Emergency drill" },
  { value: "exam instructions", label: "Exam instructions" },
  { value: "general notice", label: "General notice" },
  { value: "custom", label: "Custom message" },
];

const AI_EXAMPLES = [
  "Standard day: assembly 7:30, lessons of 40 minutes from 8:00 to 14:20, mid-morning break 10:20, lunch 12:30 to 13:10, closing 14:30.",
  "BECE exam day: candidates assemble 7:30, each paper runs 2 hours with a 30-minute break between papers, closing 15:00.",
  "Sports day: whole school gathers 8:00, events change every 45 minutes, lunch 12:00, closing 13:30.",
];

const fmtExam = (sec: number) => {
  const h = Math.floor(sec / 3600), m = Math.floor((sec % 3600) / 60), s = sec % 60;
  return h > 0 ? `${h}:${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}` : `${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
};

export function Siren() {
  const { currentSchoolId, navTarget } = useStore();
  const { toast } = useToast();
  const schoolId = currentSchoolId ?? "";
  const [data, setData] = useState<SirenData | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [tab, setTab] = useState("live");
  const [now, setNow] = useState(new Date());
  const [playing, setPlaying] = useState<string | null>(null);
  const [playKind, setPlayKind] = useState<"MANUAL" | "TEST">("MANUAL");
  const [duration, setDuration] = useState(10);
  const [consoleVolume, setConsoleVolume] = useState(100);
  const [consoleFade, setConsoleFade] = useState(false);
  const [deviceZone, setDeviceZoneState] = useState("All Campus");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [form, setForm] = useState(emptyForm);
  const [confirmDelete, setConfirmDelete] = useState<Schedule | null>(null);
  const [confirmClear, setConfirmClear] = useState(false);
  const [kindFilter, setKindFilter] = useState("ALL");
  const [toneLabel, setToneLabel] = useState("");
  const [toneFile, setToneFile] = useState<File | null>(null);
  const [toneDuration, setToneDuration] = useState(15);
  const [toneUploading, setToneUploading] = useState(false);
  const [toneEdit, setToneEdit] = useState<Tone | null>(null);
  const [toneEditLabel, setToneEditLabel] = useState("");
  const [toneEditDur, setToneEditDur] = useState(15);
  const [confirmToneDelete, setConfirmToneDelete] = useState<Tone | null>(null);
  const stopRef = useRef<(() => void) | null>(null);

  // ── Siren Pro: day profiles ──
  const [viewProfile, setViewProfile] = useState<string | null>(null);
  const [newProfileOpen, setNewProfileOpen] = useState(false);
  const [newProfileName, setNewProfileName] = useState("");

  // ── Siren Pro: AI timetable builder ──
  const [aiPrompt, setAiPrompt] = useState("");
  const [aiBusy, setAiBusy] = useState(false);
  const [aiRings, setAiRings] = useState<AiRing[] | null>(null);
  const [aiReplace, setAiReplace] = useState(false);

  // ── Siren Pro: zones ──
  const [zoneDrafts, setZoneDrafts] = useState<string[] | null>(null);
  const [zoneDraft, setZoneDraft] = useState("");

  // ── Siren Pro: exam & period timer ──
  const [examTotal, setExamTotal] = useState(0);
  const [examLeft, setExamLeft] = useState(0);
  const [examRunning, setExamRunning] = useState(false);
  const [examChime, setExamChime] = useState(true);

  // ── Siren Pro: PA announcements ──
  const [paOccasion, setPaOccasion] = useState("general notice");
  const [paDetails, setPaDetails] = useState("");
  const [paText, setPaText] = useState("");
  const [paBusy, setPaBusy] = useState(false);
  const [speaking, setSpeaking] = useState(false);

  // ── Siren Pro: AI reliability report ──
  const [insBusy, setInsBusy] = useState(false);
  const [insights, setInsights] = useState<Insights | null>(null);

  const tones = data?.tones ?? [];
  const label = (sound: string) => (sound === "VOICE" ? "Spoken announcement" : sirenSoundLabel(sound, tones));
  const activeProfile = data?.activeProfile ?? "Normal Day";
  const profiles = data?.profiles ?? ["Normal Day", "Exam Day"];
  const zones = zoneDrafts ?? data?.zones ?? ["All Campus"];
  const shownProfile = viewProfile ?? activeProfile;

  useEffect(() => {
    if (navTarget?.tab) setTab(navTarget.tab);
  }, [navTarget]);
  useEffect(() => { setDeviceZoneState(getDeviceZone()); }, []);
  useEffect(() => {
    if (data && viewProfile === null) setViewProfile(data.activeProfile);
  }, [data, viewProfile]);

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

  /** Play a sound (built-in or uploaded tone) with console volume/fade and log the firing. */
  const ring = useCallback(async (sound: string, dur: number, kind: "MANUAL" | "TEST", labelOverride?: string, volOverride?: number) => {
    if (playing) return;
    setPlaying(sound);
    setPlayKind(kind);
    const vol = volOverride ?? consoleVolume;
    stopRef.current = await playSchoolSound(sound, dur, schoolId ? toneFetcher(schoolId) : undefined, { volume: vol, fadeInSec: consoleFade && kind === "MANUAL" ? 3 : 0 });
    window.setTimeout(() => { setPlaying(null); stopRef.current = null; }, dur * 1000);
    if (!schoolId) return;
    try { await apiPost(`/api/school/${schoolId}${OPS}`, { action: "ring", sound, durationSec: dur, kind, label: labelOverride }); await load(); }
    catch { /* sound still played; log failed silently */ }
  }, [playing, schoolId, load, consoleVolume, consoleFade]);

  const stopAll = useCallback(() => {
    stopRef.current?.();
    stopRef.current = null;
    setPlaying(null);
  }, []);

  // ── in-console timetable arming: rings due schedules while this page is open
  const schedules = data?.schedules ?? [];
  useEffect(() => {
    if (!schoolId || !data || !schedules.length) return;
    const fetchBytes = toneFetcher(schoolId);
    const tick = () => {
      const d = new Date();
      const hhmm = `${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
      const keyBase = `${d.getFullYear()}${d.getMonth()}${d.getDate()}`;
      for (const { s, at } of dueAt(data, hhmm, d.getDay(), getDeviceZone())) {
        const guardKey = `siren-fired-${s.id}-${keyBase}-${at}`;
        if (localStorage.getItem(guardKey)) continue;
        localStorage.setItem(guardKey, "1");
        void playSchoolSound(s.sound, s.durationSec, fetchBytes, { volume: s.volume, fadeInSec: s.fadeIn });
        void apiPost(`/api/school/${schoolId}${OPS}`, { action: "ring", scheduleId: s.id, sound: s.sound, durationSec: s.durationSec, kind: "SCHEDULED", label: s.label }).catch(() => {});
      }
    };
    tick();
    const t = window.setInterval(tick, 15_000);
    return () => window.clearInterval(t);
  }, [schoolId, schedules]);

  // ── exam & period timer: count down, ring the bell at zero ──
  useEffect(() => {
    if (!examRunning) return;
    const t = window.setInterval(() => setExamLeft((l) => Math.max(0, l - 1)), 1000);
    return () => window.clearInterval(t);
  }, [examRunning]);
  useEffect(() => {
    if (examTotal > 0 && examLeft === 0 && examRunning) {
      setExamRunning(false);
      toast({ title: "Time is up!", description: examChime ? "Ringing the exam bell now." : "The timer has ended." });
      if (examChime) void ring("CHIME", 15, "TEST", "Exam timer ended", 100);
    }
  }, [examLeft, examRunning]);

  const openNew = () => { setForm({ ...emptyForm, profile: shownProfile }); setDialogOpen(true); };
  const openEdit = (s: Schedule) => {
    setForm({
      id: s.id, label: s.label, time: s.time, days: s.days, sound: s.sound, durationSec: s.durationSec, note: s.note ?? "",
      enabled: s.enabled, profile: s.profile, zone: s.zone, volume: s.volume, fadeIn: s.fadeIn,
      repeatEveryMin: s.repeatEveryMin ?? 0, endTime: s.endTime ?? "", skipVacation: s.skipVacation,
    });
    setDialogOpen(true);
  };
  const saveSchedule = async () => {
    if (!form.label.trim()) { toast({ title: "Label is required", variant: "destructive" }); return; }
    if (form.repeatEveryMin > 0 && form.endTime && form.endTime <= form.time) {
      toast({ title: "The repeat end time must be after the start time", variant: "destructive" });
      return;
    }
    await act(
      {
        action: "schedule.save", id: form.id || undefined, label: form.label, time: form.time, days: form.days,
        sound: form.sound, durationSec: form.durationSec, note: form.note, enabled: form.enabled,
        profile: form.profile, zone: form.zone, volume: form.volume, fadeIn: form.fadeIn,
        repeatEveryMin: form.repeatEveryMin || null, endTime: form.repeatEveryMin > 0 && form.endTime ? form.endTime : null,
        skipVacation: form.skipVacation,
      },
      form.id ? "Schedule updated" : "Schedule created",
    );
    setDialogOpen(false);
  };
  const applyPreset = async () => {
    if (!schoolId) return;
    setBusy(true);
    try {
      for (const p of PRESETS) {
        await apiPost(`/api/school/${schoolId}${OPS}`, { action: "schedule.save", label: p.label, time: p.time, days: p.days, sound: p.sound, durationSec: p.durationSec, enabled: true, profile: shownProfile });
      }
      toast({ title: "Normal school-day timetable applied", description: `${PRESETS.length} rings created on "${shownProfile}"` });
      await load();
    } catch (e: any) {
      toast({ title: "Could not apply preset", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  };

  /** Upload a custom tone (multipart — the song / sound file itself). */
  const uploadTone = async () => {
    if (!schoolId) return;
    if (!toneFile) { toast({ title: "Choose an audio file first", variant: "destructive" }); return; }
    setToneUploading(true);
    try {
      const fd = new FormData();
      fd.append("action", "tone.upload");
      fd.append("label", toneLabel.trim() || toneFile.name.replace(/\.[^.]+$/, ""));
      fd.append("durationSec", String(toneDuration));
      fd.append("file", toneFile);
      const res = await fetch(`/api/school/${schoolId}${OPS}`, { method: "POST", headers: { ...getHeaders() }, body: fd });
      const d = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(d.error ?? `Upload failed (${res.status})`);
      toast({ title: "Tone uploaded", description: `“${d.tone.label}” is ready to ring from the console or timetable.` });
      setToneFile(null); setToneLabel("");
      await load();
    } catch (e: any) {
      toast({ title: "Upload failed", description: e.message, variant: "destructive" });
    } finally { setToneUploading(false); }
  };

  // ── AI timetable builder ──
  const generateTimetable = async () => {
    if (!schoolId) return;
    if (!aiPrompt.trim()) { toast({ title: "Describe the school day first", variant: "destructive" }); return; }
    setAiBusy(true);
    try {
      const d = await apiPost<{ rings: AiRing[] }>(`/api/school/${schoolId}${OPS}`, { action: "ai.timetable", prompt: aiPrompt, profile: shownProfile });
      setAiRings(d.rings);
      toast({ title: `${d.rings.length} rings drafted`, description: "Check them below, then apply them to the timetable." });
    } catch (e: any) {
      toast({ title: "The AI could not build that", description: e.message, variant: "destructive" });
    } finally { setAiBusy(false); }
  };
  const applyAiTimetable = async () => {
    if (!aiRings?.length) return;
    setBusy(true);
    try {
      const d = await apiPost<{ created: number }>(`/api/school/${schoolId}${OPS}`, { action: "bulk.apply", profile: shownProfile, replace: aiReplace, rings: aiRings });
      toast({ title: "Timetable applied", description: `${d.created} rings on "${shownProfile}"${aiReplace ? " (existing rings replaced)" : ""}.` });
      setAiRings(null); setAiPrompt("");
      await load();
    } catch (e: any) {
      toast({ title: "Could not apply", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  };

  // ── zones ──
  const saveZones = async () => {
    if (!zoneDrafts) return;
    await act({ action: "zones.save", zones: zoneDrafts.filter(Boolean) }, "Campus zones saved");
    setZoneDrafts(null);
  };
  const changeDeviceZone = (z: string) => {
    setDeviceZone(z);
    setDeviceZoneState(z);
    toast({ title: `This device now rings for "${z}"`, description: z === "All Campus" ? "It will sound every ring, including rings targeted at other zones." : "It will sound campus-wide rings plus rings targeted at this zone." });
  };

  // ── profiles ──
  const createProfile = async () => {
    const name = newProfileName.trim();
    if (!name) { toast({ title: "Give the profile a name", variant: "destructive" }); return; }
    try {
      await apiPost(`/api/school/${schoolId}${OPS}`, { action: "profile.create", profile: name });
      toast({ title: `Profile "${name}" created`, description: "Add rings to it, then activate it when that kind of day comes." });
      setNewProfileOpen(false); setNewProfileName("");
      setViewProfile(name);
      await load();
    } catch (e: any) {
      toast({ title: "Could not create profile", description: e.message, variant: "destructive" });
    }
  };
  const deleteProfile = async () => {
    if (["Normal Day", "Exam Day"].includes(shownProfile)) return;
    try {
      await apiPost(`/api/school/${schoolId}${OPS}`, { action: "profile.remove", profile: shownProfile });
      toast({ title: `Profile "${shownProfile}" removed` });
      setViewProfile("Normal Day");
      await load();
    } catch (e: any) {
      toast({ title: "Could not remove profile", description: e.message, variant: "destructive" });
    }
  };

  // ── PA announcements ──
  const draftAnnouncement = async () => {
    if (!schoolId) return;
    setPaBusy(true);
    try {
      const d = await apiPost<{ text: string }>(`/api/school/${schoolId}${OPS}`, { action: "ai.announce", occasion: paOccasion, details: paDetails });
      setPaText(d.text);
      toast({ title: "Draft ready", description: "Edit it if you like, then speak it over the PA." });
    } catch (e: any) {
      toast({ title: "The AI could not draft that", description: e.message, variant: "destructive" });
    } finally { setPaBusy(false); }
  };
  const speakNow = async () => {
    const text = paText.trim();
    if (!text) { toast({ title: "Write or draft the announcement first", variant: "destructive" }); return; }
    if (typeof window === "undefined" || !("speechSynthesis" in window)) {
      toast({ title: "This browser cannot speak", description: "Use Chrome, Edge or Safari for spoken announcements.", variant: "destructive" });
      return;
    }
    stopAll();
    window.speechSynthesis.cancel();
    const u = new SpeechSynthesisUtterance(text);
    u.rate = 0.95;
    u.onend = () => setSpeaking(false);
    u.onerror = () => setSpeaking(false);
    setSpeaking(true);
    window.speechSynthesis.speak(u);
    if (!schoolId) return;
    try { await apiPost(`/api/school/${schoolId}${OPS}`, { action: "announce", text }); await load(); }
    catch { /* speech still played; log failed silently */ }
  };
  const stopSpeaking = () => {
    if (typeof window !== "undefined" && "speechSynthesis" in window) window.speechSynthesis.cancel();
    setSpeaking(false);
  };

  // ── AI reliability report ──
  const runInsights = async () => {
    if (!schoolId) return;
    setInsBusy(true);
    try { setInsights(await apiPost<Insights>(`/api/school/${schoolId}${OPS}`, { action: "ai.insights" })); }
    catch (e: any) { toast({ title: "Could not analyse", description: e.message, variant: "destructive" }); }
    finally { setInsBusy(false); }
  };

  const clock = `${String(now.getHours()).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}:${String(now.getSeconds()).padStart(2, "0")}`;
  const todayKey = now.toDateString();
  const todayOccurrences = useMemo(() => {
    if (!data) return [] as { s: Schedule; mins: number }[];
    const idx = new Date(todayKey).getDay();
    const out: { s: Schedule; mins: number }[] = [];
    for (const s of schedules) {
      if (!s.enabled || s.profile !== activeProfile || !s.days.includes(idx)) continue;
      if (s.skipVacation && data.term && !data.term.inTerm) continue;
      if (!zoneMatches(s.zone, deviceZone)) continue;
      for (const m of occurrencesOf(s)) out.push({ s, mins: m });
    }
    return out.sort((a, b) => a.mins - b.mins);
  }, [schedules, activeProfile, data?.term?.inTerm, deviceZone, todayKey]);
  const nowMins = now.getHours() * 60 + now.getMinutes();
  const nextOcc = todayOccurrences.find((o) => o.mins >= nowMins);
  const nextLocal = nextOcc ? { time: minsToClock(nextOcc.mins), label: nextOcc.s.label, sound: nextOcc.s.sound } : null;
  const events = (data?.events ?? []).filter((e) => kindFilter === "ALL" || e.kind === kindFilter);
  const profileSchedules = schedules.filter((s) => s.profile === shownProfile).sort((a, b) => timeToMins(a.time) - timeToMins(b.time));
  const zonesDirty = !!zoneDrafts && JSON.stringify(zoneDrafts) !== JSON.stringify(data?.zones ?? []);
  const examPct = examTotal > 0 ? ((examTotal - examLeft) / examTotal) * 100 : 0;

  if (loading && !data) return <LoadingBlock label="Loading siren console…" />;
  if (!data) return <EmptyState icon={<AlarmClock />} title="Siren console unavailable" hint="Try reloading the module." />;

  return (
    <div className="space-y-4">
      <ModuleHeader
        title="Siren &amp; Bells"
        description="The complete electronic school bell — day profiles, campus zones, period bells, spoken PA announcements and an AI that builds the timetable from a plain-English description."
        actions={
          <div className="flex items-center gap-2">
            <Badge variant="outline" className="gap-1"><MapPin className="size-3" /> This device: {deviceZone}</Badge>
            <Badge variant="outline" className="gap-1">Day: {activeProfile}{data.term?.name ? ` · ${data.term.name}` : ""}</Badge>
            <Badge className={cn("gap-1 text-white", playing ? "bg-amber-600" : "bg-emerald-600")}>
              {playing ? <Volume2 className="size-3 animate-pulse" /> : <CircleCheck className="size-3" />}
              {playing ? `Ringing — ${label(playing)}` : "Console armed"}
            </Badge>
            {(playing || speaking) && (
              <Button size="sm" variant="destructive" onClick={() => { stopAll(); stopSpeaking(); }}><Square className="size-3 mr-1" /> Stop</Button>
            )}
          </div>
        }
      />

      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <StatCard icon={<CalendarDays className="size-4" />} label="Timetable rings" value={`${data.stats.enabledSchedules}/${data.stats.totalSchedules}`} sub={`on ${data.profiles.length} day profiles`} />
        <StatCard icon={<Clock3 className="size-4" />} label="Next ring today" value={nextLocal?.time ?? "—"} sub={nextLocal?.label ?? "no more rings today"} tone={nextLocal ? "primary" : "default"} />
        <StatCard icon={<Zap className="size-4" />} label="Fired today" value={data.stats.firedToday} sub="rings, sirens & announcements" />
        <StatCard icon={<History className="size-4" />} label="Total firings" value={data.stats.firedTotal} sub="last 120 shown" />
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex flex-wrap h-auto gap-1">
          <TabsTrigger value="live">Live console</TabsTrigger>
          <TabsTrigger value="schedule">Timetable ({schedules.length})</TabsTrigger>
          <TabsTrigger value="tones">My tones ({tones.length})</TabsTrigger>
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
                {data.term && !data.term.inTerm && (
                  <div className="mt-1 rounded-md bg-amber-500/10 border border-amber-500/30 px-2.5 py-1.5 text-xs text-amber-700 dark:text-amber-400">
                    Outside the active term — vacation-aware rings are paused
                  </div>
                )}
                {nextOcc ? (
                  <div className="mt-2 text-center">
                    <div className="text-xs uppercase tracking-wide text-muted-foreground">Next ring</div>
                    <div className="text-lg font-semibold">{minsToClock(nextOcc.mins)} — {nextOcc.s.label}</div>
                    <div className="text-xs text-muted-foreground">
                      in {Math.max(0, nextOcc.mins - nowMins)} min · {label(nextOcc.s.sound)} · {nextOcc.s.zone}
                    </div>
                  </div>
                ) : (
                  <div className="mt-2 text-sm text-muted-foreground">No more rings scheduled today</div>
                )}
              </CardContent>
            </Card>

            {/* exam & period timer — rings itself when time is up */}
            <Card>
              <CardContent className="p-6 space-y-3">
                <div className="flex items-center gap-2 font-semibold"><Hourglass className="size-4 text-primary" /> Exam &amp; period timer</div>
                <div className="text-center py-1">
                  <div className={cn("text-4xl font-bold tabular-nums tracking-tight", examLeft === 0 && examTotal > 0 && "text-destructive")}>
                    {fmtExam(examLeft)}
                  </div>
                  {examTotal > 0 && (
                    <>
                      <Progress value={examPct} className="mt-3 h-2" />
                      <div className="text-xs text-muted-foreground mt-1">{fmtExam(examTotal)} paper · {Math.round(examPct)}% elapsed</div>
                    </>
                  )}
                </div>
                <div className="flex flex-wrap justify-center gap-1.5">
                  {[30, 45, 60, 90, 120, 180].map((m) => (
                    <button key={m} type="button"
                      onClick={() => { setExamTotal(m * 60); setExamLeft(m * 60); setExamRunning(false); }}
                      className={cn(
                        "rounded-md border px-2.5 py-1 text-xs font-medium transition-colors",
                        examTotal === m * 60 ? "bg-primary text-primary-foreground border-primary" : "bg-background hover:bg-muted",
                      )}
                    >
                      {m < 60 ? `${m}m` : `${m / 60}h`}
                    </button>
                  ))}
                </div>
                <div className="flex flex-wrap items-center justify-center gap-2">
                  {examLeft > 0 && (
                    <Button size="sm" onClick={() => setExamRunning((r) => !r)}>
                      {examRunning ? <><Pause className="size-3.5 mr-1" /> Pause</> : <><Play className="size-3.5 mr-1" /> Start</>}
                    </Button>
                  )}
                  <Button size="sm" variant="outline" disabled={examTotal === 0} onClick={() => { setExamLeft((l) => l + 300); setExamTotal((t) => t + 300); }}>
                    <Plus className="size-3.5 mr-1" /> Extra 5 min
                  </Button>
                  <Button size="sm" variant="ghost" disabled={examTotal === 0} onClick={() => { setExamTotal(0); setExamLeft(0); setExamRunning(false); }}>
                    <RotateCcw className="size-3.5 mr-1" /> Reset
                  </Button>
                </div>
                <div className="flex items-center justify-center gap-2 text-xs">
                  <Switch id="exam-chime" checked={examChime} onCheckedChange={setExamChime} />
                  <Label htmlFor="exam-chime" className="text-muted-foreground">Ring the bell when time is up</Label>
                </div>
              </CardContent>
            </Card>

            <Card className="border-destructive/40">
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
                    onClick={() => ring("SIREN", 30, "MANUAL", "Emergency siren", 100)}>
                    <SirenIcon className="size-5 mr-2" /> Sound the siren (30s)
                  </Button>
                  <Button variant="outline" size="lg" className="h-12" disabled={!!playing}
                    onClick={() => ring("KLAXON", 15, "MANUAL", "Attention klaxon", 100)}>
                    Klaxon (15s)
                  </Button>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* PA announcements — type it, AI-polish it, speak it */}
          <Card>
            <CardContent className="p-6 space-y-4">
              <div className="flex flex-wrap items-center gap-2">
                <div className="font-semibold flex items-center gap-2"><Megaphone className="size-4 text-primary" /> PA announcements</div>
                {speaking && <Badge className="gap-1 bg-amber-600 text-white"><Volume2 className="size-3 animate-pulse" /> Speaking…</Badge>}
                <span className="text-xs text-muted-foreground ml-auto">spoken from this console&apos;s speakers and logged in the history</span>
              </div>
              <div className="grid gap-3 sm:grid-cols-2">
                <FormField label="Occasion">
                  <Select value={paOccasion} onValueChange={setPaOccasion}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>{PA_OCCASIONS.map((o) => <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>)}</SelectContent>
                  </Select>
                </FormField>
                <FormField label="What should it say? (the AI fills in the details)">
                  <Input value={paDetails} onChange={(e) => setPaDetails(e.target.value)} placeholder="e.g. school closes at 12 today; PTA meeting Friday 4pm" maxLength={600} />
                </FormField>
              </div>
              <div className="flex flex-wrap gap-2">
                <Button variant="outline" onClick={draftAnnouncement} disabled={paBusy}>
                  {paBusy ? <><Loader2 className="size-4 mr-1 animate-spin" /> Drafting…</> : <><Sparkles className="size-4 mr-1" /> Draft with AI</>}
                </Button>
                {(paOccasion === "early closing" || paOccasion === "emergency drill") && !paText && (
                  <Button variant="ghost" size="sm"
                    onClick={() => setPaDetails((d) => d || (paOccasion === "early closing" ? "school closes at 12:00 today, buses leave at 12:30" : "fire drill now — teachers lead learners to the assembly point"))}>
                    Fill an example
                  </Button>
                )}
              </div>
              <FormField label="Announcement to speak">
                <Textarea rows={3} value={paText} onChange={(e) => setPaText(e.target.value)} placeholder="Write it yourself, or let the AI draft it above." maxLength={600} />
              </FormField>
              <div className="flex flex-wrap items-center gap-2">
                <Button onClick={speakNow} disabled={speaking}><Megaphone className="size-4 mr-1" /> Speak over the PA</Button>
                {speaking && <Button variant="destructive" onClick={stopSpeaking}><Square className="size-4 mr-1" /> Stop speaking</Button>}
              </div>
            </CardContent>
          </Card>

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
              <div className="flex flex-wrap items-center gap-4">
                <div className="flex items-center gap-3 min-w-56">
                  <Volume2 className="size-4 shrink-0 text-muted-foreground" />
                  <Slider value={[consoleVolume]} onValueChange={(v) => setConsoleVolume(v[0])} min={10} max={100} step={5} className="flex-1" aria-label="Console volume" />
                  <span className="text-xs tabular-nums text-muted-foreground w-9 text-right">{consoleVolume}%</span>
                </div>
                <div className="flex items-center gap-1.5 text-xs">
                  <Switch id="fade-in" checked={consoleFade} onCheckedChange={setConsoleFade} />
                  <Label htmlFor="fade-in" className="text-muted-foreground">gentle start (3s fade-in)</Label>
                </div>
              </div>
              <div className={cn("grid gap-3", tones.length ? "sm:grid-cols-2 lg:grid-cols-3" : "sm:grid-cols-2 lg:grid-cols-5")}>
                {SIREN_SOUNDS.map((s) => (
                  <button
                    key={s.id}
                    type="button"
                    disabled={!!playing}
                    onClick={() => ring(s.id, duration, playKind)}
                    className={cn(
                      "group rounded-lg border p-4 text-left transition-all hover:shadow-md hover:border-primary/40 hover:-translate-y-0.5 disabled:opacity-50 focus-visible:outline-2 focus-visible:outline-primary",
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
                {tones.map((t) => (
                  <button
                    key={t.id}
                    type="button"
                    disabled={!!playing}
                    onClick={() => ring(`${CUSTOM_PREFIX}${t.id}`, duration, playKind)}
                    className={cn(
                      "group rounded-lg border p-4 text-left transition-all hover:shadow-md hover:border-primary/40 hover:-translate-y-0.5 disabled:opacity-50 focus-visible:outline-2 focus-visible:outline-primary",
                      playing === `${CUSTOM_PREFIX}${t.id}` && "border-primary bg-primary/5 shadow-md",
                    )}
                  >
                    <div className="flex items-center gap-2">
                      <span className="inline-flex size-9 items-center justify-center rounded-full bg-amber-500/15 text-amber-600">
                        <Music className="size-4" />
                      </span>
                      <div className="min-w-0">
                        <div className="truncate font-medium text-sm">{t.label}</div>
                        <div className="text-[10px] text-muted-foreground">uploaded · {fmtMB(t.sizeBytes)}</div>
                      </div>
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground leading-relaxed">Your own song or sound — loops over the speakers for the whole ring.</p>
                    <div className="mt-3 flex items-center gap-1 text-xs text-primary font-medium">
                      <Play className="size-3" /> Ring {duration}s
                    </div>
                  </button>
                ))}
              </div>
              {tones.length > 0 && (
                <p className="text-xs text-muted-foreground">Uploaded tones loop until the ring ends — manage them on the <button className="underline" onClick={() => setTab("tones")}>My tones</button> tab.</p>
              )}
              {playing && (
                <div className="flex items-center gap-2 rounded-md bg-amber-500/10 border border-amber-500/30 px-3 py-2 text-sm">
                  <Volume2 className="size-4 text-amber-600 animate-pulse" />
                  Ringing <b>{label(playing)}</b> as {KIND_LABEL[playKind]} — <button className="underline" onClick={stopAll}>stop now</button>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── TIMETABLE ── */}
        <TabsContent value="schedule" className="space-y-4 mt-4">
          {/* day profiles — one-tap switch of the running timetable */}
          <Card>
            <CardContent className="p-4 sm:p-6 space-y-3">
              <div className="flex flex-wrap items-center gap-2">
                <div className="font-semibold flex items-center gap-2"><Timer className="size-4 text-primary" /> Day profiles</div>
                <span className="text-xs text-muted-foreground">the active profile is the timetable that actually rings today</span>
              </div>
              <div className="flex flex-wrap items-center gap-2">
                {profiles.map((p) => (
                  <button
                    key={p} type="button"
                    onClick={() => setViewProfile(p)}
                    className={cn(
                      "inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-all hover:-translate-y-0.5",
                      p === shownProfile ? "border-primary bg-primary text-primary-foreground shadow-sm" : "bg-background hover:bg-muted",
                    )}
                  >
                    {p}
                    {p === activeProfile && (
                      <span className={cn("inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[10px] font-semibold", p === shownProfile ? "bg-white/20" : "bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300")}>
                        <CircleCheck className="size-2.5" /> Active
                      </span>
                    )}
                  </button>
                ))}
                <Button size="sm" variant="outline" onClick={() => setNewProfileOpen(true)}><Plus className="size-3.5 mr-1" /> New profile</Button>
              </div>
              <div className="flex flex-wrap items-center gap-2">
                {shownProfile !== activeProfile ? (
                  <Button size="sm" onClick={() => act({ action: "profile.activate", profile: shownProfile }, `"${shownProfile}" is now the active day`)}
                    disabled={busy || !profileSchedules.length}>
                    <Zap className="size-3.5 mr-1" /> Activate &quot;{shownProfile}&quot;
                  </Button>
                ) : (
                  <Badge variant="outline" className="gap-1 text-emerald-700 dark:text-emerald-400"><CircleCheck className="size-3" /> {shownProfile} is ringing today</Badge>
                )}
                {!["Normal Day", "Exam Day"].includes(shownProfile) && !profileSchedules.length && (
                  <Button size="sm" variant="ghost" className="text-destructive" onClick={deleteProfile}><Trash2 className="size-3.5 mr-1" /> Delete profile</Button>
                )}
                <span className="text-xs text-muted-foreground ml-auto">
                  {profileSchedules.length} ring{profileSchedules.length === 1 ? "" : "s"} on {shownProfile}
                  {shownProfile === "Exam Day" && !profileSchedules.length && " — add longer, calmer rings for papers"}
                </span>
              </div>
            </CardContent>
          </Card>

          {/* AI timetable builder */}
          <Card className="border-primary/30 bg-gradient-to-br from-primary/5 via-transparent to-transparent">
            <CardContent className="p-4 sm:p-6 space-y-4">
              <div className="flex flex-wrap items-center gap-2">
                <div className="font-semibold flex items-center gap-2"><Sparkles className="size-4 text-primary" /> AI timetable builder</div>
                <span className="text-xs text-muted-foreground ml-auto">builds into &quot;{shownProfile}&quot;</span>
              </div>
              <p className="text-sm text-muted-foreground leading-relaxed">
                Describe the school day in plain English — assembly times, lesson lengths, breaks, closing — and the AI
                lays out the rings, including period bells that repeat every lesson length.
              </p>
              <Textarea
                rows={3}
                value={aiPrompt}
                onChange={(e) => setAiPrompt(e.target.value)}
                placeholder={AI_EXAMPLES[0]}
                maxLength={1200}
              />
              <div className="flex flex-wrap gap-1.5">
                {AI_EXAMPLES.map((ex, i) => (
                  <button key={i} type="button" onClick={() => setAiPrompt(ex)}
                    className="rounded-full border bg-background px-3 py-1 text-xs text-muted-foreground transition-colors hover:bg-muted hover:text-foreground">
                    {["Standard day", "BECE exam day", "Sports day"][i]}
                  </button>
                ))}
              </div>
              <div className="flex flex-wrap items-center gap-4">
                <Button onClick={generateTimetable} disabled={aiBusy}>
                  {aiBusy ? <><Loader2 className="size-4 mr-1 animate-spin" /> Building…</> : <><Sparkles className="size-4 mr-1" /> Build the timetable</>}
                </Button>
                <div className="flex items-center gap-1.5 text-xs">
                  <Switch id="ai-replace" checked={aiReplace} onCheckedChange={setAiReplace} />
                  <Label htmlFor="ai-replace" className="text-muted-foreground">replace the rings already on &quot;{shownProfile}&quot;</Label>
                </div>
              </div>
              {aiRings && (
                <div className="rounded-lg border bg-background overflow-hidden">
                  <div className="max-h-72 overflow-y-auto nice-scroll">
                    <table className="w-full text-sm">
                      <thead className="sticky top-0 bg-muted/90 backdrop-blur">
                        <tr className="text-left text-muted-foreground">
                          <th className="px-3 py-2 font-medium">Time</th>
                          <th className="px-3 py-2 font-medium">Ring</th>
                          <th className="px-3 py-2 font-medium">Sound</th>
                          <th className="px-3 py-2 font-medium">Details</th>
                        </tr>
                      </thead>
                      <tbody>
                        {aiRings.map((r, i) => (
                          <tr key={i} className="border-t">
                            <td className="px-3 py-2 tabular-nums font-semibold whitespace-nowrap">{r.time}</td>
                            <td className="px-3 py-2 font-medium">{r.label}</td>
                            <td className="px-3 py-2">{label(r.sound)}<span className="text-xs text-muted-foreground"> · {r.durationSec}s</span></td>
                            <td className="px-3 py-2 text-xs text-muted-foreground">
                              {r.repeatEveryMin ? `every ${r.repeatEveryMin}m until ${r.endTime ?? "—"} · ` : ""}
                              {DAY_NAMES[r.days[0]] ?? ""}{r.days.length > 1 ? ` +${r.days.length - 1}` : ""}
                              {r.volume < 100 ? ` · ${r.volume}%` : ""}{r.fadeIn > 0 ? ` · gentle start` : ""}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                  <div className="flex flex-wrap items-center gap-2 border-t bg-muted/40 px-3 py-2.5">
                    <span className="text-xs text-muted-foreground">{aiRings.length} proposed rings — apply them or discard.</span>
                    <div className="ml-auto flex gap-2">
                      <Button size="sm" variant="ghost" onClick={() => setAiRings(null)}>Discard</Button>
                      <Button size="sm" onClick={applyAiTimetable} disabled={busy}><Check className="size-3.5 mr-1" /> Apply to &quot;{shownProfile}&quot;</Button>
                    </div>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>

          <div className="flex flex-wrap items-center gap-2">
            <Button onClick={openNew}><Plus className="size-4 mr-1" /> Add ring to &quot;{shownProfile}&quot;</Button>
            <Button variant="outline" onClick={applyPreset} disabled={busy}>
              <Timer className="size-4 mr-1" /> Apply normal school day (6 rings)
            </Button>
            <span className="text-xs text-muted-foreground ml-auto">Rings fire from any open console — front desk, staff room or office.</span>
          </div>

          {profileSchedules.length === 0 ? (
            <EmptyState
              icon={<CalendarDays />}
              title={`No rings on "${shownProfile}" yet`}
              hint="Add a ring, ask the AI to build the day, or apply the 'normal school day' preset."
            />
          ) : (
            <Card>
              <CardContent className="p-0">
                <div className="max-h-[540px] overflow-y-auto nice-scroll">
                  <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-muted/90 backdrop-blur">
                      <tr className="text-left text-muted-foreground">
                        <th className="px-4 py-2.5 font-medium">Ring</th>
                        <th className="px-4 py-2.5 font-medium">Time</th>
                        <th className="px-4 py-2.5 font-medium">Days</th>
                        <th className="px-4 py-2.5 font-medium">Sound</th>
                        <th className="px-4 py-2.5 font-medium">Zone</th>
                        <th className="px-4 py-2.5 font-medium">On?</th>
                        <th className="px-4 py-2.5 font-medium text-right">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {profileSchedules.map((s) => (
                        <tr key={s.id} className={cn("border-t", !s.enabled && "opacity-55")}>
                          <td className="px-4 py-2.5">
                            <div className="font-medium">{s.label}</div>
                            <div className="text-xs text-muted-foreground flex flex-wrap items-center gap-x-1.5">
                              {s.note && <span>{s.note} ·</span>}
                              {s.repeatEveryMin ? <span className="text-primary font-medium">periods every {s.repeatEveryMin}m → {s.endTime ?? "—"}</span> : null}
                              {s.volume < 100 && <span>· {s.volume}% vol</span>}
                              {s.fadeIn > 0 && <span>· gentle start</span>}
                              {s.skipVacation && <span>· term-time only</span>}
                            </div>
                          </td>
                          <td className="px-4 py-2.5 tabular-nums font-semibold">{s.time}</td>
                          <td className="px-4 py-2.5">
                            <div className="flex flex-wrap gap-0.5">
                              {s.days.map((d) => <Badge key={d} variant="outline" className="text-[10px] px-1.5">{DAY_NAMES[d]}</Badge>)}
                            </div>
                          </td>
                          <td className="px-4 py-2.5">{label(s.sound)}<span className="text-xs text-muted-foreground"> · {s.durationSec}s</span></td>
                          <td className="px-4 py-2.5">
                            <Badge variant="outline" className={cn("text-[10px] gap-1", s.zone === "All Campus" ? "text-muted-foreground" : "border-primary/40 text-primary")}>
                              <MapPin className="size-2.5" /> {s.zone}
                            </Badge>
                          </td>
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

          {/* campus zones — speaker routing */}
          <Card>
            <CardContent className="p-4 sm:p-6 space-y-4">
              <div className="flex flex-wrap items-center gap-2">
                <div className="font-semibold flex items-center gap-2"><MapPin className="size-4 text-primary" /> Campus zones</div>
                <span className="text-xs text-muted-foreground ml-auto">tag rings to a block; only consoles serving that zone sound them</span>
              </div>
              <div className="flex flex-wrap items-center gap-1.5">
                {zones.map((z) => (
                  <span key={z} className="inline-flex items-center gap-1 rounded-full border bg-background px-3 py-1 text-sm">
                    {z}
                    {z !== "All Campus" && (
                      <button type="button" aria-label={`Remove zone ${z}`} className="text-muted-foreground hover:text-destructive"
                        onClick={() => setZoneDrafts(zones.filter((x) => x !== z))}>
                        <X className="size-3.5" />
                      </button>
                    )}
                  </span>
                ))}
              </div>
              <div className="flex flex-wrap items-center gap-2">
                <Input value={zoneDraft} onChange={(e) => setZoneDraft(e.target.value)} placeholder="e.g. Science block, Dining hall" className="w-56 h-9" maxLength={40}
                  onKeyDown={(e) => {
                    if (e.key === "Enter" && zoneDraft.trim()) {
                      e.preventDefault();
                      if (!zones.includes(zoneDraft.trim())) setZoneDrafts([...zones, zoneDraft.trim()]);
                      setZoneDraft("");
                    }
                  }} />
                <Button variant="outline" size="sm" disabled={!zoneDraft.trim() || zones.includes(zoneDraft.trim())}
                  onClick={() => { if (zoneDraft.trim() && !zones.includes(zoneDraft.trim())) { setZoneDrafts([...zones, zoneDraft.trim()]); setZoneDraft(""); } }}>
                  <Plus className="size-3.5 mr-1" /> Add zone
                </Button>
                {zonesDirty && <Button size="sm" onClick={saveZones} disabled={busy}>Save zones</Button>}
                {zonesDirty && <Button size="sm" variant="ghost" onClick={() => setZoneDrafts(null)}>Undo</Button>}
              </div>
              <div className="flex flex-wrap items-center gap-2 border-t pt-4">
                <FormField label="This device rings for">
                  <Select value={deviceZone} onValueChange={changeDeviceZone}>
                    <SelectTrigger className="w-56"><SelectValue /></SelectTrigger>
                    <SelectContent>{zones.map((z) => <SelectItem key={z} value={z}>{z}</SelectItem>)}</SelectContent>
                  </Select>
                </FormField>
                <p className="text-xs text-muted-foreground flex-1 min-w-52">
                  Put a console (an old laptop or mini-PC with speakers) in each block and assign it here —
                  &quot;All Campus&quot; devices sound every ring, zone devices sound their own block&apos;s rings plus campus-wide ones.
                </p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── MY TONES (uploaded songs / sounds) ── */}
        <TabsContent value="tones" className="space-y-4 mt-4">
          <div className="grid gap-4 lg:grid-cols-3">
            <Card className="lg:col-span-1">
              <CardContent className="p-6 space-y-4">
                <div className="flex items-center gap-2 font-semibold">
                  <Upload className="size-4 text-primary" /> Upload a song or sound
                </div>
                <p className="text-sm text-muted-foreground leading-relaxed">
                  Your school song, anthem, a recorded hand bell, a gospel track — anything the school should
                  hear at assembly or closing. It rings from the console and can be hung on the timetable like
                  any built-in sound.
                </p>
                <FormField label="Tone name">
                  <Input value={toneLabel} onChange={(e) => setToneLabel(e.target.value)} placeholder="e.g. School anthem, Closing song" maxLength={80} />
                </FormField>
                <FormField label="Audio file (MP3, WAV, OGG, M4A — max 12 MB)">
                  <label className="flex cursor-pointer items-center gap-3 rounded-lg border border-dashed p-4 transition-colors hover:border-primary/50 hover:bg-primary/5">
                    <FileAudio className="size-8 shrink-0 text-primary" />
                    <div className="min-w-0 flex-1 text-sm">
                      {toneFile ? (
                        <>
                          <div className="truncate font-medium">{toneFile.name}</div>
                          <div className="text-xs text-muted-foreground">{fmtMB(toneFile.size)}</div>
                        </>
                      ) : (
                        <div className="text-muted-foreground">Click to choose an audio file from this machine</div>
                      )}
                    </div>
                    <input
                      type="file" accept="audio/*,.mp3,.wav,.ogg,.m4a,.aac,.webm" className="hidden"
                      onChange={(e) => setToneFile(e.target.files?.[0] ?? null)}
                    />
                  </label>
                </FormField>
                <FormField label="Default ring length">
                  <Select value={String(toneDuration)} onValueChange={(v) => setToneDuration(Number(v))}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>{[10, 15, 20, 30, 45, 60, 90, 120].map((d) => <SelectItem key={d} value={String(d)}>{d} seconds</SelectItem>)}</SelectContent>
                  </Select>
                </FormField>
                <Button className="w-full" onClick={uploadTone} disabled={toneUploading || !toneFile}>
                  {toneUploading ? <><Loader2 className="size-4 mr-1 animate-spin" /> Uploading…</> : <><Upload className="size-4 mr-1" /> Upload tone</>}
                </Button>
              </CardContent>
            </Card>

            <div className="space-y-3 lg:col-span-2">
              {tones.length === 0 ? (
                <EmptyState
                  icon={<Music />}
                  title="No uploaded tones yet"
                  hint="Upload the school anthem or any song — it becomes a first-class sound on the console and the timetable."
                />
              ) : (
                <Card>
                  <CardContent className="p-0">
                    <div className="max-h-[540px] overflow-y-auto nice-scroll">
                      <table className="w-full text-sm">
                        <thead className="sticky top-0 bg-muted/90 backdrop-blur">
                          <tr className="text-left text-muted-foreground">
                            <th className="px-4 py-2.5 font-medium">Tone</th>
                            <th className="px-4 py-2.5 font-medium">Size</th>
                            <th className="px-4 py-2.5 font-medium">Default</th>
                            <th className="px-4 py-2.5 font-medium">Uploaded</th>
                            <th className="px-4 py-2.5 font-medium text-right">Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          {tones.map((t) => (
                            <tr key={t.id} className="border-t">
                              <td className="px-4 py-2.5">
                                <div className="flex items-center gap-2">
                                  <span className="inline-flex size-8 items-center justify-center rounded-full bg-amber-500/15 text-amber-600"><Music className="size-4" /></span>
                                  <div>
                                    <div className="font-medium">{t.label}</div>
                                    <div className="text-xs text-muted-foreground">{t.mimeType.replace("audio/", "").toUpperCase()}</div>
                                  </div>
                                </div>
                              </td>
                              <td className="px-4 py-2.5 text-muted-foreground">{fmtMB(t.sizeBytes)}</td>
                              <td className="px-4 py-2.5 tabular-nums">{t.durationSec}s</td>
                              <td className="px-4 py-2.5 text-muted-foreground">{fmtDateTime(t.createdAt)}<div className="text-xs">by {t.createdByName}</div></td>
                              <td className="px-4 py-2.5 text-right">
                                <div className="flex justify-end gap-1">
                                  <Button variant="ghost" size="icon" className="size-7" disabled={!!playing} onClick={() => ring(`${CUSTOM_PREFIX}${t.id}`, t.durationSec, "TEST")} aria-label={`Test ${t.label}`}>
                                    <Play className="size-3.5" />
                                  </Button>
                                  <Button variant="ghost" size="icon" className="size-7" onClick={() => { setToneEdit(t); setToneEditLabel(t.label); setToneEditDur(t.durationSec); }} aria-label={`Edit ${t.label}`}>
                                    <Pencil className="size-3.5" />
                                  </Button>
                                  <Button variant="ghost" size="icon" className="size-7 text-destructive" onClick={() => setConfirmToneDelete(t)} aria-label={`Delete ${t.label}`}>
                                    <Trash2 className="size-3.5" />
                                  </Button>
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
            </div>
          </div>
        </TabsContent>

        {/* ── HISTORY ── */}
        <TabsContent value="history" className="space-y-4 mt-4">
          {/* AI reliability report */}
          <Card className="border-primary/30 bg-gradient-to-br from-primary/5 via-transparent to-transparent">
            <CardContent className="p-4 sm:p-6 space-y-4">
              <div className="flex flex-wrap items-center gap-2">
                <div className="font-semibold flex items-center gap-2"><Sparkles className="size-4 text-primary" /> Reliability report — last 7 days</div>
                <Button size="sm" variant="outline" className="ml-auto" onClick={runInsights} disabled={insBusy}>
                  {insBusy ? <><Loader2 className="size-3.5 mr-1 animate-spin" /> Analysing…</> : <><Sparkles className="size-3.5 mr-1" /> Analyse with AI</>}
                </Button>
              </div>
              {insights ? (
                <div className="space-y-4">
                  <div className="grid grid-cols-3 gap-3 max-w-lg">
                    <div className="rounded-lg border bg-background p-3 text-center">
                      <div className="text-2xl font-bold tabular-nums">{insights.expected}</div>
                      <div className="text-[11px] text-muted-foreground">rings expected</div>
                    </div>
                    <div className="rounded-lg border bg-background p-3 text-center">
                      <div className="text-2xl font-bold tabular-nums text-emerald-600 dark:text-emerald-400">{insights.fired}</div>
                      <div className="text-[11px] text-muted-foreground">rang on time</div>
                    </div>
                    <div className="rounded-lg border bg-background p-3 text-center">
                      <div className={cn("text-2xl font-bold tabular-nums", insights.missedCount ? "text-destructive" : "text-emerald-600 dark:text-emerald-400")}>{insights.missedCount}</div>
                      <div className="text-[11px] text-muted-foreground">missed</div>
                    </div>
                  </div>
                  {insights.missed.length > 0 && (
                    <div className="rounded-lg border bg-background p-3">
                      <div className="text-xs font-medium text-muted-foreground mb-2">Missed rings (keep a console open at the front desk):</div>
                      <div className="flex flex-wrap gap-1.5">
                        {insights.missed.map((m, i) => (
                          <span key={i} className="rounded-full bg-destructive/10 text-destructive px-2.5 py-0.5 text-xs tabular-nums">
                            {m.time} · {m.label}
                          </span>
                        ))}
                        {insights.missedCount > insights.missed.length && <span className="text-xs text-muted-foreground self-center">+{insights.missedCount - insights.missed.length} more</span>}
                      </div>
                    </div>
                  )}
                  {insights.narrative && <p className="text-sm leading-relaxed">{insights.narrative}</p>}
                  {insights.tips.length > 0 && (
                    <ul className="space-y-1.5">
                      {insights.tips.map((t, i) => (
                        <li key={i} className="flex items-start gap-2 text-sm text-muted-foreground">
                          <CircleCheck className="size-3.5 mt-0.5 shrink-0 text-primary" /> {t}
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground">
                  Checks the last 7 days of timetable rings against what actually fired — missed rings usually mean no
                  console was open at that minute — then advises.
                </p>
              )}
            </CardContent>
          </Card>

          <div className="flex flex-wrap items-center gap-2">
            <Select value={kindFilter} onValueChange={setKindFilter}>
              <SelectTrigger className="w-44 h-9"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="ALL">All firings</SelectItem>
                <SelectItem value="SCHEDULED">Timetable rings</SelectItem>
                <SelectItem value="MANUAL">Manual rings</SelectItem>
                <SelectItem value="ANNOUNCEMENT">PA announcements</SelectItem>
                <SelectItem value="TEST">Sound tests</SelectItem>
              </SelectContent>
            </Select>
            <Button variant="outline" className="text-destructive" onClick={() => setConfirmClear(true)}>
              <Trash2 className="size-4 mr-1" /> Clear history
            </Button>
            <span className="text-xs text-muted-foreground ml-auto">Newest first · last 120 firings</span>
          </div>

          {events.length === 0 ? (
            <EmptyState icon={<History />} title="No firings yet" hint="Manual rings, timetable rings, announcements and sound tests all land here." />
          ) : (
            <Card>
              <CardContent className="p-0">
                <div className="max-h-[540px] overflow-y-auto nice-scroll">
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
                          <td className="px-4 py-2.5">{label(e.sound)}<span className="text-xs text-muted-foreground"> · {e.durationSec}s</span></td>
                          <td className="px-4 py-2.5">
                            <Badge variant="outline" className={cn(
                              "text-[10px]",
                              e.kind === "SCHEDULED" && "border-emerald-300 text-emerald-700",
                              e.kind === "MANUAL" && "border-amber-300 text-amber-700",
                              e.kind === "TEST" && "border-slate-300 text-slate-600",
                              e.kind === "ANNOUNCEMENT" && "border-purple-300 text-purple-700 dark:border-purple-800 dark:text-purple-300",
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
        <DialogContent className="sm:max-w-lg max-h-[90vh] overflow-y-auto nice-scroll">
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
                <Select value={form.sound} onValueChange={(v) => setForm({ ...form, sound: v })}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    {SIREN_SOUNDS.map((s) => <SelectItem key={s.id} value={s.id}>{s.label}</SelectItem>)}
                    {tones.length > 0 && (
                      <SelectItem value="__tones__" disabled className="text-[10px] uppercase tracking-wide text-muted-foreground">— uploaded tones —</SelectItem>
                    )}
                    {tones.map((t) => (
                      <SelectItem key={t.id} value={`${CUSTOM_PREFIX}${t.id}`}>
                        <span className="inline-flex items-center gap-1.5"><Music className="size-3 text-amber-500" /> {t.label}</span>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </FormField>
              <FormField label="Duration">
                <Select value={String(form.durationSec)} onValueChange={(v) => setForm({ ...form, durationSec: Number(v) })}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>{[5, 10, 15, 20, 30, 45, 60].map((d) => <SelectItem key={d} value={String(d)}>{d} seconds</SelectItem>)}</SelectContent>
                </Select>
              </FormField>
              <FormField label="Day profile">
                <Select value={form.profile} onValueChange={(v) => setForm({ ...form, profile: v })}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>{profiles.map((p) => <SelectItem key={p} value={p}>{p}</SelectItem>)}</SelectContent>
                </Select>
              </FormField>
              <FormField label="Campus zone" hint="Only consoles serving this zone (plus All-Campus devices) will ring it.">
                <Select value={form.zone} onValueChange={(v) => setForm({ ...form, zone: v })}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>{zones.map((z) => <SelectItem key={z} value={z}>{z}</SelectItem>)}</SelectContent>
                </Select>
              </FormField>
              <FormField label="Period bell — repeat every" hint="One row rings each lesson change instead of many rows." span={2}>
                <div className="flex flex-wrap items-center gap-2">
                  <Select value={String(form.repeatEveryMin)} onValueChange={(v) => setForm({ ...form, repeatEveryMin: Number(v) })}>
                    <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="0">No repeat</SelectItem>
                      {[30, 35, 40, 45, 60, 80, 90, 120].map((m) => <SelectItem key={m} value={m}>every {m} minutes</SelectItem>)}
                    </SelectContent>
                  </Select>
                  {form.repeatEveryMin > 0 && (
                    <div className="flex items-center gap-1.5 text-sm">
                      <span className="text-xs text-muted-foreground">until</span>
                      <Input type="time" value={form.endTime} onChange={(e) => setForm({ ...form, endTime: e.target.value })} className="w-32" />
                    </div>
                  )}
                </div>
              </FormField>
            </FormGrid>
            <FormField label="Ring volume" hint="Lower for assemblies; closing sirens usually run at 100%.">
              <div className="flex items-center gap-3">
                <Volume2 className="size-4 shrink-0 text-muted-foreground" />
                <Slider value={[form.volume]} onValueChange={(v) => setForm({ ...form, volume: v[0] })} min={10} max={100} step={5} className="flex-1" aria-label="Ring volume" />
                <span className="text-xs tabular-nums text-muted-foreground w-9 text-right">{form.volume}%</span>
              </div>
            </FormField>
            <FormField label="Gentle start (fade-in)">
              <Select value={String(form.fadeIn)} onValueChange={(v) => setForm({ ...form, fadeIn: Number(v) })}>
                <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
                <SelectContent>{[0, 2, 3, 5].map((f) => <SelectItem key={f} value={String(f)}>{f === 0 ? "Start immediately" : `${f} second${f > 1 ? "s" : ""} fade-in`}</SelectItem>)}</SelectContent>
              </Select>
            </FormField>
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
            <div className="flex flex-wrap items-center gap-4">
              <div className="flex items-center gap-2">
                <Switch id="sched-enabled" checked={form.enabled} onCheckedChange={(v) => setForm({ ...form, enabled: v })} />
                <Label htmlFor="sched-enabled">Enabled</Label>
              </div>
              <div className="flex items-center gap-2">
                <Switch id="sched-vacation" checked={form.skipVacation} onCheckedChange={(v) => setForm({ ...form, skipVacation: v })} />
                <Label htmlFor="sched-vacation" className="text-muted-foreground">term-time only (skip vacations)</Label>
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
            <Button onClick={saveSchedule} disabled={busy}>{form.id ? "Save changes" : "Add to timetable"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── new profile dialog ── */}
      <Dialog open={newProfileOpen} onOpenChange={setNewProfileOpen}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader>
            <DialogTitle>New day profile</DialogTitle>
            <DialogDescription>Profiles are separate timetables — e.g. &quot;Exam week&quot;, &quot;Friday club day&quot;, &quot;Vacation classes&quot;. Activate one and the whole school switches.</DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-1">
            <FormField label="Profile name" required>
              <Input value={newProfileName} onChange={(e) => setNewProfileName(e.target.value)} placeholder="e.g. Exam week, Sports day" maxLength={40} onKeyDown={(e) => { if (e.key === "Enter") void createProfile(); }} />
            </FormField>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setNewProfileOpen(false)}>Cancel</Button>
            <Button onClick={createProfile} disabled={busy}>Create profile</Button>
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

      {/* ── tone edit dialog ── */}
      <Dialog open={!!toneEdit} onOpenChange={(v) => !v && setToneEdit(null)}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader><DialogTitle>Edit tone</DialogTitle><DialogDescription>Rename the tone or change how long it rings by default.</DialogDescription></DialogHeader>
          <div className="space-y-4 py-1">
            <FormField label="Tone name" required>
              <Input value={toneEditLabel} onChange={(e) => setToneEditLabel(e.target.value)} maxLength={80} />
            </FormField>
            <FormField label="Default ring length">
              <Select value={String(toneEditDur)} onValueChange={(v) => setToneEditDur(Number(v))}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{[10, 15, 20, 30, 45, 60, 90, 120].map((d) => <SelectItem key={d} value={String(d)}>{d} seconds</SelectItem>)}</SelectContent>
              </Select>
            </FormField>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setToneEdit(null)}>Cancel</Button>
            <Button onClick={() => { if (toneEdit && toneEditLabel.trim()) act({ action: "tone.update", id: toneEdit.id, label: toneEditLabel, durationSec: toneEditDur }, "Tone updated"); setToneEdit(null); }} disabled={busy}>Save</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── tone delete confirm ── */}
      <AlertDialog open={!!confirmToneDelete} onOpenChange={(v) => !v && setConfirmToneDelete(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete this tone?</AlertDialogTitle>
            <AlertDialogDescription>
              “{confirmToneDelete?.label}” will be removed. Rings and timetabled schedules using it will fall back to the classic school bell.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Keep tone</AlertDialogCancel>
            <AlertDialogAction
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
              onClick={() => { if (confirmToneDelete) act({ action: "tone.delete", id: confirmToneDelete.id }, "Tone deleted"); setConfirmToneDelete(null); }}
            >
              Delete tone
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
   bell). Honors the active day profile, this device's campus
   zone, vacation awareness and period-bell repeats; the server
   de-duplicates per schedule+minute, localStorage guards repeat
   fires on this machine. */
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
      for (const { s, at } of dueAt(data, hhmm, d.getDay(), getDeviceZone())) {
        const guardKey = `siren-fired-${s.id}-${keyBase}-${at}`;
        if (localStorage.getItem(guardKey)) continue;
        localStorage.setItem(guardKey, "1");
        void playSchoolSound(s.sound, s.durationSec, toneFetcher(schoolId), { volume: s.volume, fadeInSec: s.fadeIn });
        void apiPost(`/api/school/${schoolId}${OPS}`, { action: "ring", scheduleId: s.id, sound: s.sound, durationSec: s.durationSec, kind: "SCHEDULED", label: s.label }).catch(() => {});
      }
    };
    void fire();
    const t = window.setInterval(fire, 20_000);
    return () => { alive = false; window.clearInterval(t); };
  }, [schoolId]);
  return null;
}

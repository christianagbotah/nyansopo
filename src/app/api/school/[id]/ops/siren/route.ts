import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";
import { sendSms } from "@/lib/sms";

// ── School siren & bell management (wave 55 · 60 custom tones · 61 Siren Pro) ──
// Timetabled rings (assembly, change of lesson, break, closing) plus a live
// console for manual / emergency rings, with a full firing history. Built-in
// sounds are synthesised in the browser (Web Audio); wave 60 added UPLOADED
// TONES ("CUSTOM:<toneId>"). wave 61 rounds it into a system schools will
// drop a standalone bell box for:
//   • day PROFILES — Normal / Exam / Event day timetables, one-tap activate
//   • ZONES — tag rings to campus areas; each device picks the zone it serves
//   • per-ring VOLUME + FADE-IN, PERIOD BELLS (repeat every N minutes)
//   • vacation awareness — rings can auto-pause outside the active term
//   • PA announcements (spoken + logged) and AI helpers: build a timetable
//     from a plain-English description, polish announcements, ring insights
//
// Scheduled rings are fired by any open console client (front-desk machine);
// the server de-duplicates SCHEDULED fires per schedule+minute so several
// open tabs (or the whole staff room) cannot double-log a ring.

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });

const SOUNDS = ["BELL", "SIREN", "CHIME", "KLAXON", "BUGLE"] as const;
const isSound = (v: unknown): v is (typeof SOUNDS)[number] => typeof v === "string" && (SOUNDS as readonly string[]).includes(v);

/** "CUSTOM:<cuid>" — an uploaded tone id (existence checked where needed). */
const customToneId = (v: unknown): string | null => {
  if (typeof v !== "string" || !v.startsWith("CUSTOM:")) return null;
  const id = v.slice(7);
  return /^[A-Za-z0-9]{20,}$/.test(id) ? id : null;
};

/** Any playable sound id — built-in or uploaded tone. */
const isAnySound = (v: unknown): boolean => isSound(v) || customToneId(v) !== null;

/** Resolve a sound id to a printable label ("School bell" / tone label). */
async function soundLabel(schoolId: string, sound: string): Promise<string> {
  const toneId = customToneId(sound);
  if (!toneId) return sound;
  const tone = await db.sirenTone.findFirst({ where: { id: toneId, schoolId }, select: { label: true } });
  return tone?.label ?? "Custom tone";
}

const clampDuration = (v: unknown): number => {
  const n = Math.round(Number(v));
  if (!isFinite(n)) return 10;
  return Math.min(300, Math.max(2, n));
};

/** "HH:MM" 24-hour — strict. */
const isTime = (v: unknown): v is string =>
  typeof v === "string" && /^([01]\d|2[0-3]):([0-5]\d)$/.test(v);

/** weekday indices 0=Sun..6=Sat — at least one day, each 0..6, deduped, sorted. */
const parseDays = (v: unknown): number[] => {
  const arr = Array.isArray(v) ? v : [];
  const days = [...new Set(arr.map((x) => Number(x)).filter((n) => Number.isInteger(n) && n >= 0 && n <= 6))].sort((a, b) => a - b);
  return days.length ? days : [1, 2, 3, 4, 5];
};

const optStr = (v: unknown, max = 300): string | null => {
  if (typeof v !== "string") return null;
  const t = v.trim();
  return t.length ? t.slice(0, max) : null;
};

/** profile / zone names — printable, short, single-line. */
const name40 = (v: unknown, fallback: string): string => optStr(v, 40)?.replace(/[\r\n]/g, " ") ?? fallback;

const clampVolume = (v: unknown): number => {
  const n = Math.round(Number(v));
  return Number.isFinite(n) ? Math.min(100, Math.max(10, n)) : 100;
};
const clampFadeIn = (v: unknown): number => {
  const n = Math.round(Number(v));
  return Number.isFinite(n) ? Math.min(5, Math.max(0, n)) : 0;
};
/** period-bell repeat interval — 5..120 minutes or null. */
const parseRepeat = (v: unknown): number | null => {
  const n = Math.round(Number(v));
  return Number.isFinite(n) && n >= 5 && n <= 120 ? n : null;
};

const TONE_COLS = { id: true, label: true, mimeType: true, sizeBytes: true, durationSec: true, createdByName: true, createdAt: true };
const SETTING_PROFILE = "siren.activeProfile";
const SETTING_PROFILES = "siren.profiles";
const SETTING_ZONES = "siren.zones";
const DEFAULT_ZONES = ["All Campus", "KG & Creche Block", "Primary Block", "JHS Block", "Administration"];

/** KV read/write for siren settings (active profile, campus zones). */
async function getSetting(schoolId: string, key: string): Promise<string | null> {
  const row = await db.schoolSetting.findUnique({ where: { schoolId_key: { schoolId, key } }, select: { value: true } });
  return row?.value ?? null;
}
async function putSetting(schoolId: string, key: string, value: string) {
  await db.schoolSetting.upsert({
    where: { schoolId_key: { schoolId, key } },
    create: { schoolId, key, value },
    update: { value },
  });
}

async function readZones(schoolId: string): Promise<string[]> {
  try {
    const raw = await getSetting(schoolId, SETTING_ZONES);
    const arr = raw ? (JSON.parse(raw) as unknown) : null;
    if (Array.isArray(arr)) {
      const zones = arr.map((z) => String(z).trim().slice(0, 40)).filter(Boolean);
      if (zones.length) return ["All Campus", ...zones.filter((z) => z !== "All Campus")];
    }
  } catch { /* fall through to defaults */ }
  return DEFAULT_ZONES;
}

async function readActiveProfile(schoolId: string, schedules: { profile: string }[]): Promise<string> {
  const saved = optStr(await getSetting(schoolId, SETTING_PROFILE), 40);
  if (saved) return saved;
  return schedules[0]?.profile ?? "Normal Day";
}

/** Saved custom day-profiles (persisted even while empty) + defaults + any profile in use. */
async function readProfiles(schoolId: string, schedules: { profile: string }[]): Promise<string[]> {
  const raw = await getSetting(schoolId, SETTING_PROFILES);
  let saved: string[] = [];
  try { const arr = raw ? JSON.parse(raw) : []; if (Array.isArray(arr)) saved = arr.map((p) => name40(p, "")).filter(Boolean); } catch { /* defaults */ }
  return [...new Set(["Normal Day", "Exam Day", ...saved, ...schedules.map((s) => s.profile)])];
}
async function putProfiles(schoolId: string, profiles: string[]) {
  await putSetting(schoolId, SETTING_PROFILES, JSON.stringify([...new Set(profiles)].slice(0, 12)));
}

/** Today inside the active term? (vacation awareness for skipVacation rings). */
async function termStatus(schoolId: string): Promise<{ inTerm: boolean; termName: string | null }> {
  const term = await db.term.findFirst({
    where: { isCurrent: true, academicYear: { schoolId } },
    select: { name: true, startsAt: true, endsAt: true },
  });
  if (!term) return { inTerm: false, termName: null };
  const now = new Date();
  return { inTerm: now >= term.startsAt && now <= term.endsAt, termName: term.name };
}

/** Expand a schedule into its occurrence minutes for one day (period bells). */
function occurrencesOf(s: { time: string; repeatEveryMin: number | null; endTime: string | null }): number[] {
  const start = Number(s.time.slice(0, 2)) * 60 + Number(s.time.slice(3, 5));
  const rep = s.repeatEveryMin;
  if (!rep) return [start];
  const limit = s.endTime && isTime(s.endTime) ? Number(s.endTime.slice(0, 2)) * 60 + Number(s.endTime.slice(3, 5)) : 24 * 60 - 1;
  const out: number[] = [];
  for (let m = start; m <= limit && out.length < 60; m += rep) out.push(m);
  return out;
}

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["siren.manage"] });
  if (!auth.ok) return auth.res;

  // ── stream an uploaded tone's bytes (console / ticker playback) ──
  const toneId = new URL(req.url).searchParams.get("tone");
  if (toneId) {
    if (!/^[A-Za-z0-9]{20,}$/.test(toneId)) return bad("Not found", 404);
    const tone = await db.sirenTone.findFirst({ where: { id: toneId, schoolId } });
    if (!tone) return bad("Tone not found", 404);
    const bytes = new Uint8Array(tone.data);
    return new NextResponse(bytes, {
      status: 200,
      headers: {
        "content-type": tone.mimeType,
        "content-length": String(bytes.byteLength),
        "cache-control": "private, max-age=86400",
      },
    });
  }

  const [schedules, events, tones, zones, term] = await Promise.all([
    db.sirenSchedule.findMany({ where: { schoolId }, orderBy: { time: "asc" } }),
    db.sirenEvent.findMany({ where: { schoolId }, orderBy: { createdAt: "desc" }, take: 120 }),
    db.sirenTone.findMany({ where: { schoolId }, orderBy: { createdAt: "desc" }, select: TONE_COLS }),
    readZones(schoolId),
    termStatus(schoolId),
  ]);
  const activeProfile = await readActiveProfile(schoolId, schedules);
  const profiles = await readProfiles(schoolId, schedules);

  const now = new Date();
  const minuteNow = now.getHours() * 60 + now.getMinutes();
  const todayIdx = now.getDay();
  const upcoming = schedules
    .filter((s) => {
      if (!s.enabled || s.profile !== activeProfile) return false;
      try { return (JSON.parse(s.daysJson) as number[]).includes(todayIdx); } catch { return false; }
    })
    .flatMap((s) => occurrencesOf(s).map((m) => ({ id: s.id, label: s.label, time: s.time, sound: s.sound, mins: m })))
    .filter((s) => s.mins >= minuteNow)
    .sort((a, b) => a.mins - b.mins);

  return NextResponse.json({
    ok: true,
    schedules: schedules.map((s) => ({ ...s, days: parseDays(s.daysJson), daysJson: undefined })),
    events,
    tones,
    activeProfile,
    profiles,
    zones,
    term: { inTerm: term.inTerm, name: term.termName },
    stats: {
      totalSchedules: schedules.length,
      enabledSchedules: schedules.filter((s) => s.enabled).length,
      nextRing: upcoming[0] ?? null,
      firedToday: events.filter((e) => new Date(e.createdAt).toDateString() === now.toDateString()).length,
      firedTotal: events.length,
    },
  });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["siren.manage"] });
  if (!auth.ok) return auth.res;
  const actor = { id: auth.user.id, fullName: auth.user.fullName ?? "Siren operator", role: auth.user.role };

  /* ── multipart branch — tone.upload (the song / sound file itself) ── */
  const contentType = req.headers.get("content-type") ?? "";
  if (contentType.includes("multipart/form-data")) {
    const form = await req.formData().catch(() => null);
    if (!form) return bad("Invalid upload");
    if (form.get("action") !== "tone.upload") return bad("Unknown upload action");

    const label = optStr(form.get("label"), 80) ?? "Custom tone";
    const durationSec = clampDuration(form.get("durationSec"));
    const file = form.get("file");
    if (!(file instanceof File)) return bad("An audio file is required");
    const MAX_TONE = 12 * 1024 * 1024; // 12 MB per tone
    if (file.size === 0) return bad("The file is empty");
    if (file.size > MAX_TONE) return bad("Audio too large — keep tones under 12 MB (trim the song first)");
    const mime = file.type || "audio/mpeg";
    if (!/^(audio|video)\//.test(mime) && !/\.(mp3|wav|ogg|m4a|aac|webm|opus)$/i.test(file.name))
      return bad("That doesn't look like an audio file — upload MP3, WAV, OGG, M4A or WebM");

    const bytes = new Uint8Array(await file.arrayBuffer());
    const row = await db.sirenTone.create({
      data: { schoolId, label, mimeType: mime, sizeBytes: bytes.byteLength, durationSec, data: bytes, createdByName: actor.fullName },
    });
    await audit({ schoolId, actor, action: "CREATE", entityType: "SirenTone", entityId: row.id, entityLabel: label, summary: `Siren tone uploaded: ${label} (${(bytes.byteLength / 1024 / 1024).toFixed(1)} MB, ${durationSec}s default ring)`, req });
    return NextResponse.json({ ok: true, tone: { id: row.id, label: row.label, mimeType: row.mimeType, sizeBytes: row.sizeBytes, durationSec: row.durationSec, createdByName: row.createdByName, createdAt: row.createdAt } });
  }

  let body: any;
  try { body = await req.json(); } catch { return bad("Invalid JSON body"); }
  const action = body?.action;
  const actorName = actor.fullName;

  // ── tone.update ── rename a tone / change its default ring length
  if (action === "tone.update") {
    const tone = await db.sirenTone.findFirst({ where: { id: String(body?.id ?? ""), schoolId } });
    if (!tone) return bad("Tone not found", 404);
    const label = optStr(body.label, 80) ?? tone.label;
    const durationSec = clampDuration(body.durationSec ?? tone.durationSec);
    const row = await db.sirenTone.update({ where: { id: tone.id }, data: { label, durationSec } });
    await audit({ schoolId, actor, action: "UPDATE", entityType: "SirenTone", entityId: row.id, entityLabel: label, summary: `Siren tone updated: ${label} (default ${durationSec}s)`, req });
    return NextResponse.json({ ok: true, tone: { id: row.id, label: row.label, durationSec: row.durationSec } });
  }

  // ── tone.delete ── remove a tone; schedules still pointing at it fall back
  // to the classic bell at ring time (client-side) and show "missing tone"
  if (action === "tone.delete") {
    const tone = await db.sirenTone.findFirst({ where: { id: String(body?.id ?? ""), schoolId } });
    if (!tone) return bad("Tone not found", 404);
    await db.sirenTone.delete({ where: { id: tone.id } });
    await audit({ schoolId, actor, action: "DELETE", entityType: "SirenTone", entityId: tone.id, entityLabel: tone.label, summary: `Siren tone deleted: ${tone.label} (schedules using it fall back to the school bell)`, req });
    return NextResponse.json({ ok: true });
  }

  // ── zones.save ── manage the campus zone list (speaker groups)
  if (action === "zones.save") {
    const arr = Array.isArray(body.zones) ? body.zones : [];
    const zones = [...new Set(arr.map((z: unknown) => name40(z, "")).filter(Boolean))].slice(0, 12) as string[];
    if (!zones.length) return bad("Give the campus at least one zone");
    const list = ["All Campus", ...zones.filter((z) => z !== "All Campus")];
    await putSetting(schoolId, SETTING_ZONES, JSON.stringify(list));
    await audit({ schoolId, actor, action: "UPDATE", entityType: "SchoolSetting", entityId: SETTING_ZONES, entityLabel: "Siren zones", summary: `Siren campus zones updated: ${list.join(", ")}`, req });
    return NextResponse.json({ ok: true, zones: list });
  }

  // ── profile.activate ── one-tap switch of the running day profile
  if (action === "profile.activate") {
    const profile = name40(body.profile, "");
    if (!profile) return bad("A profile name is required");
    await putSetting(schoolId, SETTING_PROFILE, profile);
    await audit({ schoolId, actor, action: "UPDATE", entityType: "SchoolSetting", entityId: SETTING_PROFILE, entityLabel: "Siren active profile", summary: `Siren day profile activated: ${profile}`, req });
    return NextResponse.json({ ok: true, activeProfile: profile });
  }

  // ── profile.create / profile.remove ── persist custom day-profiles
  if (action === "profile.create" || action === "profile.remove") {
    const schedules = await db.sirenSchedule.findMany({ where: { schoolId }, select: { profile: true } });
    const current = await readProfiles(schoolId, schedules);
    if (action === "profile.create") {
      const profile = name40(body.profile, "");
      if (!profile) return bad("A profile name is required");
      if (["Normal Day", "Exam Day"].includes(profile) || current.includes(profile)) return bad("That profile already exists");
      await putProfiles(schoolId, [...current, profile]);
      await audit({ schoolId, actor, action: "CREATE", entityType: "SchoolSetting", entityId: SETTING_PROFILES, entityLabel: profile, summary: `Siren day profile created: ${profile}`, req });
      return NextResponse.json({ ok: true, profile });
    }
    const profile = name40(body.profile, "");
    if (["Normal Day", "Exam Day"].includes(profile)) return bad("The built-in profiles cannot be removed");
    if (schedules.some((s) => s.profile === profile)) return bad("That profile still has rings on it — delete or move them first");
    const saved = current.filter((p) => p !== profile);
    await putProfiles(schoolId, saved);
    const active = await readActiveProfile(schoolId, schedules);
    if (active === profile) await putSetting(schoolId, SETTING_PROFILE, "Normal Day");
    await audit({ schoolId, actor, action: "DELETE", entityType: "SchoolSetting", entityId: SETTING_PROFILES, entityLabel: profile, summary: `Siren day profile removed: ${profile}`, req });
    return NextResponse.json({ ok: true });
  }

  // ── schedule.save ── create or update a ring on the timetable
  if (action === "schedule.save") {
    const label = optStr(body.label, 80);
    if (!label) return bad("A label is required (e.g. 'Morning assembly')");
    if (!isTime(body.time)) return bad("Time must be HH:MM (24-hour), e.g. 07:45");
    if (!isAnySound(body.sound)) return bad("Unknown sound");
    if (customToneId(body.sound)) {
      const tone = await db.sirenTone.findFirst({ where: { id: customToneId(body.sound)!, schoolId } });
      if (!tone) return bad("That uploaded tone no longer exists — pick another sound");
    }
    const sound = String(body.sound);
    const durationSec = clampDuration(body.durationSec);
    const days = parseDays(body.days);
    const note = optStr(body.note, 300);
    const profile = name40(body.profile, "Normal Day");
    const zones = await readZones(schoolId);
    const zone = zones.includes(String(body.zone)) ? String(body.zone) : "All Campus";
    const volume = clampVolume(body.volume);
    const fadeIn = clampFadeIn(body.fadeIn);
    const repeatEveryMin = parseRepeat(body.repeatEveryMin);
    const endTime = isTime(body.endTime) ? (body.endTime as string) : null;
    const skipVacation = body.skipVacation === false ? false : true;

    if (body.id) {
      const existing = await db.sirenSchedule.findFirst({ where: { id: String(body.id), schoolId } });
      if (!existing) return bad("Schedule not found", 404);
      const row = await db.sirenSchedule.update({
        where: { id: existing.id },
        data: { label, time: body.time, sound, durationSec, daysJson: JSON.stringify(days), note, profile, zone, volume, fadeIn, repeatEveryMin, endTime, skipVacation, enabled: body.enabled === false ? false : true },
      });
      await audit({ schoolId, actor, action: "UPDATE", entityType: "SirenSchedule", entityId: row.id, entityLabel: label, summary: `Siren schedule updated: ${label} at ${row.time} (${profile})`, req });
      return NextResponse.json({ ok: true, schedule: row });
    }
    const row = await db.sirenSchedule.create({
      data: { schoolId, label, time: body.time, sound, durationSec, daysJson: JSON.stringify(days), note, profile, zone, volume, fadeIn, repeatEveryMin, endTime, skipVacation, enabled: body.enabled === false ? false : true },
    });
    await audit({ schoolId, actor, action: "CREATE", entityType: "SirenSchedule", entityId: row.id, entityLabel: label, summary: `Siren schedule created: ${label} at ${row.time} (${profile}, ${await soundLabel(schoolId, sound)})`, req });
    return NextResponse.json({ ok: true, schedule: row });
  }

  // ── bulk.apply ── create many rings at once (AI timetable builder, presets).
  // { rings: [{label,time,sound,durationSec,days,note?,repeatEveryMin?,endTime?,volume?,fadeIn?}], profile, replace? }
  if (action === "bulk.apply") {
    const profile = name40(body.profile, "Normal Day");
    const rings = Array.isArray(body.rings) ? body.rings.slice(0, 40) : [];
    if (!rings.length) return bad("No rings to add");
    const tones = await db.sirenTone.findMany({ where: { schoolId }, select: { id: true } });
    const toneIds = new Set(tones.map((t) => t.id));
    const clean = rings
      .map((r: any) => ({
        label: optStr(r?.label, 80),
        time: isTime(r?.time) ? r.time : null,
        sound: isSound(r?.sound) ? r.sound : customToneId(r?.sound) && toneIds.has(customToneId(r?.sound)!) ? r.sound : null,
        durationSec: clampDuration(r?.durationSec),
        days: parseDays(r?.days),
        note: optStr(r?.note, 300),
        repeatEveryMin: parseRepeat(r?.repeatEveryMin),
        endTime: isTime(r?.endTime) ? r.endTime : null,
        volume: clampVolume(r?.volume),
        fadeIn: clampFadeIn(r?.fadeIn),
      }))
      .filter((r: any) => r.label && r.time && r.sound);
    if (!clean.length) return bad("None of the rings were valid — check the times and sounds");

    const created = await db.$transaction(async (tx) => {
      if (body.replace) await tx.sirenSchedule.deleteMany({ where: { schoolId, profile } });
      const rows = [];
      for (const r of clean) {
        rows.push(await tx.sirenSchedule.create({
          data: { schoolId, profile, label: r.label!, time: r.time!, sound: r.sound!, durationSec: r.durationSec, daysJson: JSON.stringify(r.days), note: r.note, repeatEveryMin: r.repeatEveryMin, endTime: r.endTime, volume: r.volume, fadeIn: r.fadeIn },
        }));
      }
      return rows.length;
    });
    await audit({ schoolId, actor, action: "CREATE", entityType: "SirenSchedule", entityId: "bulk", entityLabel: profile, summary: `Siren timetable applied: ${created} rings on "${profile}"${body.replace ? " (replaced)" : ""}`, req });
    return NextResponse.json({ ok: true, created });
  }

  // ── schedule.toggle ── enable/disable without editing
  if (action === "schedule.toggle") {
    const existing = await db.sirenSchedule.findFirst({ where: { id: String(body?.id ?? ""), schoolId } });
    if (!existing) return bad("Schedule not found", 404);
    const row = await db.sirenSchedule.update({ where: { id: existing.id }, data: { enabled: !!body.enabled } });
    await audit({ schoolId, actor, action: "UPDATE", entityType: "SirenSchedule", entityId: row.id, entityLabel: row.label, summary: `Siren schedule ${row.enabled ? "enabled" : "disabled"}: ${row.label} at ${row.time}`, req });
    return NextResponse.json({ ok: true, schedule: row });
  }

  // ── schedule.delete ──
  if (action === "schedule.delete") {
    const existing = await db.sirenSchedule.findFirst({ where: { id: String(body?.id ?? ""), schoolId } });
    if (!existing) return bad("Schedule not found", 404);
    await db.sirenSchedule.delete({ where: { id: existing.id } });
    await audit({ schoolId, actor, action: "DELETE", entityType: "SirenSchedule", entityId: existing.id, entityLabel: existing.label, summary: `Siren schedule deleted: ${existing.label} at ${existing.time}`, req });
    return NextResponse.json({ ok: true });
  }

  // ── ring ── record a manual / scheduled / test firing (the browser plays the sound)
  if (action === "ring") {
    const sound = isAnySound(body.sound) ? String(body.sound) : "BELL";
    if (customToneId(sound)) {
      const tone = await db.sirenTone.findFirst({ where: { id: customToneId(sound)!, schoolId } });
      if (!tone) return bad("That uploaded tone no longer exists", 404);
    }
    const durationSec = clampDuration(body.durationSec);
    const kind = body.kind === "SCHEDULED" || body.kind === "TEST" ? body.kind : "MANUAL";
    let label = optStr(body.label, 80);
    let scheduleId: string | null = null;

    if (kind === "SCHEDULED") {
      const sched = await db.sirenSchedule.findFirst({ where: { id: String(body.scheduleId ?? ""), schoolId } });
      if (!sched) return bad("Schedule not found", 404);
      scheduleId = sched.id;
      label = label ?? sched.label;
      // de-duplicate: one SCHEDULED log per schedule per minute
      const minuteAgo = new Date(Date.now() - 60_000);
      const dup = await db.sirenEvent.findFirst({
        where: { schoolId, scheduleId, kind: "SCHEDULED", createdAt: { gte: minuteAgo } },
      });
      if (dup) return NextResponse.json({ ok: true, duplicate: true, event: dup });
    }
    const soundName = await soundLabel(schoolId, sound);
    if (!label) label = sound === "SIREN" ? "Emergency siren" : kind === "TEST" ? `Sound test — ${soundName}` : `Manual ring — ${soundName}`;

    const row = await db.sirenEvent.create({
      data: { schoolId, scheduleId, label, sound, durationSec, kind, byName: actorName, byRole: actor.role, note: optStr(body.note, 300) },
    });
    await audit({
      schoolId, actor, action: "CREATE", entityType: "SirenEvent", entityId: row.id, entityLabel: label,
      summary: kind === "SCHEDULED" ? `Siren rang on schedule: ${label} (${soundName} ${durationSec}s)` : `Siren ring (${kind.toLowerCase()}): ${label} (${soundName} ${durationSec}s)`,
      req,
    });
    return NextResponse.json({ ok: true, event: row });
  }

  // ── announce ── log a spoken PA announcement (the browser speaks it)
  if (action === "announce") {
    const text = optStr(body.text, 600);
    if (!text) return bad("Announcement text is required");
    const row = await db.sirenEvent.create({
      data: { schoolId, label: "PA announcement", sound: "VOICE", durationSec: Math.min(120, Math.max(5, Math.round(text.split(/\s+/).length / 2.2))), kind: "ANNOUNCEMENT", byName: actorName, byRole: actor.role, note: text },
    });
    await audit({ schoolId, actor, action: "CREATE", entityType: "SirenEvent", entityId: row.id, entityLabel: "PA announcement", summary: `PA announcement broadcast: "${text.slice(0, 120)}"`, req });
    return NextResponse.json({ ok: true, event: row });
  }

  /* ── wave 62 · AI EMERGENCY COMMAND ─────────────────────────────────────
     One confirmed tap escalates a real emergency the way no standalone bell
     box can: the wail sounds from every open console, the incident is logged
     as kind=EMERGENCY, an announcement text is drafted (AI when available)
     and EVERY staff member with a phone on file is alerted by SMS at once —
     emergencies override the per-staff quiet-hours opt-out. In-app, the
     notification bell picks the live emergency up for the whole school. */
  if (action === "emergency") {
    const KINDS: Record<string, { label: string; text: string }> = {
      FIRE: { label: "Fire", text: "FIRE! Please evacuate the buildings calmly and move to the assembly point. Teachers lead your class and take the register." },
      EVACUATION: { label: "Evacuation", text: "Everyone should move out of the buildings now and gather at the assembly point, following your teachers." },
      LOCKDOWN: { label: "Lockdown", text: "Lockdown! Teachers should secure their classrooms, keep everyone inside and away from doors and windows until further notice." },
      MEDICAL: { label: "Medical emergency", text: "There is a medical emergency on the compound. Any staff member with first-aid training, please report to the front desk now." },
      CUSTOM: { label: "Emergency", text: "Attention please — this is an emergency announcement. Follow the directions of your teachers immediately." },
    };
    const kind = typeof body?.kind === "string" && KINDS[body.kind] ? body.kind : "CUSTOM";
    const note = optStr(body.note, 300);
    const durationSec = clampDuration(body.durationSec ?? 60);
    const meta = KINDS[kind];
    const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true, town: true } });

    // 1 — the announcement text: deterministic template first (fast + reliable),
    //     then the AI rewrites it into a calm, clear compound announcement that
    //     includes the operator's note. If the AI is unreachable the template stands.
    let message = meta.text;
    if (note) message = `${message} (${note})`;
    try {
      const { default: ZAI } = await import("z-ai-web-dev-sdk");
      const client = await ZAI.create();
      const completion = await client.chat.completions.create({
        messages: [
          {
            role: "system",
            content: `You draft EMERGENCY announcements for a Ghanaian basic school. They are spoken over the compound PA while the siren is sounding. 2-3 short sentences, calm and commanding, simple English, no headings, no lists, no emoji, no quotes. Occasion: ${meta.label}. Include the operator's details if given. Keep under 55 words. Return STRICT JSON only: {"text":"..."}.`,
          },
          { role: "user", content: `School: ${school?.name ?? "the school"}${school?.town ? ` (${school.town})` : ""}. Incident: ${meta.label}. Details from the operator: ${note ?? "(none)"}` },
        ],
        thinking: { type: "disabled" },
      });
      const raw = (completion.choices[0]?.message?.content ?? "").replace(/```json|```/gi, "").trim();
      let parsed: any = {};
      try { parsed = JSON.parse(raw); } catch {
        const first = raw.indexOf("{"), last = raw.lastIndexOf("}");
        try { parsed = JSON.parse(raw.slice(first, last + 1)); } catch { /* keep template */ }
      }
      if (typeof parsed?.text === "string" && parsed.text.trim().length > 10) message = parsed.text.trim().slice(0, 500);
    } catch { /* AI optional — template stands */ }

    // 2 — log the emergency as a first-class siren event (drives the in-app alert)
    const label = `EMERGENCY — ${meta.label}`;
    const event = await db.sirenEvent.create({
      data: { schoolId, label, sound: "SIREN", durationSec, kind: "EMERGENCY", byName: actorName, byRole: actor.role, note: `${message}${note ? ` — ${note}` : ""}` },
    });
    await audit({ schoolId, actor, action: "CREATE", entityType: "SirenEvent", entityId: event.id, entityLabel: label, summary: `EMERGENCY declared (${meta.label}) — siren ${durationSec}s, staff alerted: "${message.slice(0, 120)}"`, req });

    // 3 — SMS fan-out to EVERY staff member with a phone (emergency overrides opt-out)
    const staff = await db.staff.findMany({
      where: { schoolId, phone: { not: null } },
      select: { fullName: true, phone: true, role: true },
      orderBy: { fullName: "asc" },
    });
    const smsBody = `EMERGENCY at ${school?.name ?? "school"}: ${message} — declared by ${actorName}.`.slice(0, 480);
    let sent = 0, failed = 0;
    for (const s of staff) {
      try {
        const msg = await sendSms({
          db, schoolId,
          recipientName: s.fullName, recipientPhone: s.phone!,
          body: smsBody, category: "EMERGENCY", sentByName: actorName,
        });
        if (msg.status === "SENT" || msg.status === "SIMULATED") sent++; else failed++;
      } catch { failed++; }
    }

    return NextResponse.json({
      ok: true, event, message, label,
      staffAlerted: { total: staff.length, smsSent: sent, smsFailed: failed },
    });
  }

  /* ── wave 62 · AI PA TRANSLATION — compound announcements in the languages
     the school community actually speaks (Twi, Ewe, Ga, Hausa, Dagbani, Pidgin). ── */
  if (action === "ai.translate") {
    const text = optStr(body.text, 600);
    if (!text) return bad("Write the announcement first");
    const LANGS: Record<string, string> = {
      twi: "Ghanaian Twi (Asante Twi, written in the standard orthography, also give a common English-lite reading if a word is untranslatable)",
      ewe: "Ewe (written in the standard orthography)",
      ga: "Ga (written in the standard orthography)",
      hausa: "Hausa (written in standard Hausa orthography, Boko script)",
      dagbani: "Dagbani (written in the standard orthography)",
      pidgin: "Ghanaian Pidgin English (the everyday spoken variety)",
    };
    const lang = typeof body?.language === "string" && LANGS[body.language] ? body.language : "twi";
    const { default: ZAI } = await import("z-ai-web-dev-sdk");
    const client = await ZAI.create();
    const completion = await client.chat.completions.create({
      messages: [
        {
          role: "system",
          content: `You translate school PA announcements for Ghanaian schools. Translate the announcement into ${LANGS[lang]}. Keep it spoken-word friendly: same meaning, natural and warm, no headings, no notes, no quotes, no explanations — ONLY the translated announcement text. Return STRICT JSON: {"text":"..."}.`,
        },
        { role: "user", content: text },
      ],
      thinking: { type: "disabled" },
    });
    const raw = (completion.choices[0]?.message?.content ?? "").replace(/```json|```/gi, "").trim();
    let out = "";
    try { out = String(JSON.parse(raw).text ?? ""); } catch {
      const first = raw.indexOf("{"), last = raw.lastIndexOf("}");
      try { out = String(JSON.parse(raw.slice(first, last + 1)).text ?? ""); } catch { out = ""; }
    }
    if (!out.trim()) return bad("The AI could not translate that — try simpler English", 502);
    return NextResponse.json({ ok: true, text: out.trim().slice(0, 600), language: lang });
  }

  // ── history.clear ── wipe the firing history (keeps schedules)
  if (action === "history.clear") {
    const gone = await db.sirenEvent.deleteMany({ where: { schoolId } });
    await audit({ schoolId, actor, action: "DELETE", entityType: "SirenEvent", entityId: "bulk", entityLabel: "Siren history", summary: `Siren firing history cleared (${gone.count} rows)`, req });
    return NextResponse.json({ ok: true, removed: gone.count });
  }

  /* ── AI: build a ring timetable from a plain-English description.
     { prompt, profile? } → proposed rings for preview (nothing saved). ── */
  if (action === "ai.timetable") {
    const prompt = optStr(body.prompt, 1200);
    if (!prompt) return bad("Describe the school day first (e.g. 'assembly 7:30, lessons 40 minutes, lunch 12:30, closing 14:30')");
    const profile = name40(body.profile, "Normal Day");
    const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true } });
    const { default: ZAI } = await import("z-ai-web-dev-sdk");
    const client = await ZAI.create();
    const completion = await client.chat.completions.create({
      messages: [
        {
          role: "system",
          content: `You design bell timetables for Ghanaian basic schools. Convert the headteacher's description into ring rows. Sounds: BELL (change of lesson, breaks), CHIME (assembly, devotion), SIREN (closing, emergencies), KLAXON (attention), BUGLE (sports, cadets). Rules: times are "HH:MM" 24-hour; days are weekday numbers (0=Sun..6=Sat, school days usually [1,2,3,4,5]); durationSec 5..60; for repeating lesson periods emit ONE row with repeatEveryMin (5..120) and endTime "HH:MM" instead of many rows; volume 10..100 ( assemblies lower, closing full); fadeIn 0..5 (gentle start for assemblies/chapel). Keep labels short and Ghanaian ("Assembly", "Break", "Change of lesson", "Lunch break", "Closing"). Return STRICT JSON only: {"rings":[{"label":"","time":"","sound":"BELL","durationSec":10,"days":[1,2,3,4,5],"repeatEveryMin":null,"endTime":null,"volume":100,"fadeIn":0,"note":""}]} — no markdown fences.`,
        },
        { role: "user", content: `School: ${school?.name ?? "the school"}. Day profile: "${profile}". Description: ${prompt}` },
      ],
      thinking: { type: "disabled" },
    });
    let parsed: any = {};
    const raw = (completion.choices[0]?.message?.content ?? "").replace(/```json|```/gi, "").trim();
    try { parsed = JSON.parse(raw); } catch {
      const first = raw.indexOf("{"), last = raw.lastIndexOf("}");
      try { parsed = JSON.parse(raw.slice(first, last + 1)); } catch { parsed = {}; }
    }
    const rings = Array.isArray(parsed?.rings)
      ? parsed.rings
          .filter((r: any) => r && typeof r === "object" && isTime(r.time) && isAnySound(r.sound))
          .slice(0, 40)
          .map((r: any) => ({
            label: optStr(r.label, 80) ?? "Ring",
            time: r.time,
            sound: String(r.sound),
            durationSec: clampDuration(r.durationSec),
            days: parseDays(r.days),
            note: optStr(r.note, 300),
            repeatEveryMin: parseRepeat(r.repeatEveryMin),
            endTime: isTime(r.endTime) ? r.endTime : null,
            volume: clampVolume(r.volume),
            fadeIn: clampFadeIn(r.fadeIn),
          }))
      : [];
    if (!rings.length) return bad("The AI could not read that description — try naming the times explicitly, e.g. 'assembly 7:30, break 10:00, closing 14:30'", 502);
    return NextResponse.json({ ok: true, rings, profile });
  }

  /* ── AI: polish a PA announcement. { occasion, details } → { text } ── */
  if (action === "ai.announce") {
    const occasion = optStr(body.occasion, 60) ?? "general";
    const details = optStr(body.details, 600) ?? "";
    const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true } });
    const { default: ZAI } = await import("z-ai-web-dev-sdk");
    const client = await ZAI.create();
    const completion = await client.chat.completions.create({
      messages: [
        {
          role: "system",
          content: `You write public-address (PA) announcements for a Ghanaian basic school. They are SPOKEN over the compound speakers by the siren console — so: 2-4 short sentences, simple warm English a child follows, no headings, no lists, no emoji, no quotes around the whole text. Address "teachers and learners" or "the whole school". Occasion: ${occasion}. Keep under 60 words. Return STRICT JSON only: {"text":"..."}.`,
        },
        { role: "user", content: `School: ${school?.name ?? "the school"}. Details to include: ${details || "(none — draft a suitable announcement)"}` },
      ],
      thinking: { type: "disabled" },
    });
    const raw = (completion.choices[0]?.message?.content ?? "").replace(/```json|```/gi, "").trim();
    let text = "";
    try { text = String(JSON.parse(raw).text ?? ""); } catch {
      const first = raw.indexOf("{"), last = raw.lastIndexOf("}");
      try { text = String(JSON.parse(raw.slice(first, last + 1)).text ?? ""); } catch { text = ""; }
    }
    if (!text.trim()) return bad("The AI could not draft that announcement — try again with a few more details", 502);
    return NextResponse.json({ ok: true, text: text.trim().slice(0, 600) });
  }

  /* ── AI: ring reliability insights — computes missed rings for the last 7
     days, then narrates. { } → { expected, fired, missed[], narrative, tips } ── */
  if (action === "ai.insights") {
    const since = new Date(Date.now() - 7 * 24 * 3600_000);
    const [schedules, events] = await Promise.all([
      db.sirenSchedule.findMany({ where: { schoolId, createdAt: { lte: new Date() } } }),
      db.sirenEvent.findMany({ where: { schoolId, kind: "SCHEDULED", createdAt: { gte: since } }, select: { scheduleId: true, createdAt: true } }),
    ]);
    const firedByKey = new Set(events.map((e) => `${e.scheduleId}:${new Date(e.createdAt).toDateString()}:${Math.floor(new Date(e.createdAt).getHours() * 60 + new Date(e.createdAt).getMinutes() / 10)}`));
    const missed: { date: string; time: string; label: string }[] = [];
    let expected = 0, fired = 0;
    for (let d = 0; d < 7; d++) {
      const day = new Date(Date.now() - d * 24 * 3600_000);
      const dayIdx = day.getDay();
      for (const s of schedules) {
        if (!s.enabled || s.skipVacation) continue; // vacation-skipped rings aren't "missed"
        try { if (!(JSON.parse(s.daysJson) as number[]).includes(dayIdx)) continue; } catch { continue; }
        for (const m of occurrencesOf(s)) {
          expected++;
          const hh = String(Math.floor(m / 60)).padStart(2, "0"), mm = String(m % 60).padStart(2, "0");
          const key = `${s.id}:${day.toDateString()}:${Math.floor(m / 10)}`;
          if (firedByKey.has(key)) fired++;
          else if (d > 0 || m <= new Date().getHours() * 60 + new Date().getMinutes())
            missed.push({ date: day.toISOString().slice(0, 10), time: `${hh}:${mm}`, label: s.label });
        }
      }
    }
    const missedTop = missed.slice(0, 12);
    let narrative = "";
    let tips: string[] = [];
    try {
      const { default: ZAI } = await import("z-ai-web-dev-sdk");
      const client = await ZAI.create();
      const completion = await client.chat.completions.create({
        messages: [
          {
            role: "system",
          content: `You are a school-operations advisor for Ghanaian basic schools. Given siren reliability data for the last 7 days, write a 2-3 sentence plain assessment of how the bell system is doing, then 2-4 short practical tips (keep a console open at the front desk, activate the right day profile, check zones, enable vacation skip…). Missed rings usually mean no console (front-desk machine) was open at that minute. Return STRICT JSON only: {"narrative":"...","tips":["...","..."]}.`,
          },
          { role: "user", content: JSON.stringify({ expected, fired, missedCount: missed.length, missed: missedTop }) },
        ],
        thinking: { type: "disabled" },
      });
      const raw = (completion.choices[0]?.message?.content ?? "").replace(/```json|```/gi, "").trim();
      let parsed: any = {};
      try { parsed = JSON.parse(raw); } catch {
        const first = raw.indexOf("{"), last = raw.lastIndexOf("}");
        try { parsed = JSON.parse(raw.slice(first, last + 1)); } catch { parsed = {}; }
      }
      narrative = typeof parsed.narrative === "string" ? parsed.narrative.slice(0, 500) : "";
      if (Array.isArray(parsed.tips)) tips = parsed.tips.filter((t: unknown) => typeof t === "string" && t).slice(0, 4).map((t: string) => t.slice(0, 160));
    } catch { /* narrative optional — computed stats still returned */ }
    return NextResponse.json({ ok: true, expected, fired, missedCount: missed.length, missed: missedTop, narrative, tips });
  }

  return bad("Unknown action");
}

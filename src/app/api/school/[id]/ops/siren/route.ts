import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";

// ── School siren & bell management (wave 55 · wave 60 custom tones) ──
// Timetabled rings (assembly, change of lesson, break, closing) plus a live
// console for manual / emergency rings, with a full firing history. Built-in
// sounds are synthesised in the browser (Web Audio); wave 60 adds UPLOADED
// TONES — the school's own songs / sounds, stored as bytes and referenced
// with the "CUSTOM:<toneId>" sound id from the console, the timetable and
// the ticker.
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

const TONE_COLS = { id: true, label: true, mimeType: true, sizeBytes: true, durationSec: true, createdByName: true, createdAt: true };

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

  const [schedules, events, tones] = await Promise.all([
    db.sirenSchedule.findMany({ where: { schoolId }, orderBy: { time: "asc" } }),
    db.sirenEvent.findMany({ where: { schoolId }, orderBy: { createdAt: "desc" }, take: 120 }),
    db.sirenTone.findMany({ where: { schoolId }, orderBy: { createdAt: "desc" }, select: TONE_COLS }),
  ]);

  const now = new Date();
  const minuteNow = now.getHours() * 60 + now.getMinutes();
  const todayIdx = now.getDay();
  const upcoming = schedules
    .filter((s) => {
      if (!s.enabled) return false;
      try { return (JSON.parse(s.daysJson) as number[]).includes(todayIdx); } catch { return false; }
    })
    .map((s) => ({ id: s.id, label: s.label, time: s.time, sound: s.sound, mins: Number(s.time.slice(0, 2)) * 60 + Number(s.time.slice(3, 5)) }))
    .filter((s) => s.mins >= minuteNow)
    .sort((a, b) => a.mins - b.mins);

  return NextResponse.json({
    ok: true,
    schedules: schedules.map((s) => ({ ...s, days: parseDays(s.daysJson), daysJson: undefined })),
    events,
    tones,
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

    if (body.id) {
      const existing = await db.sirenSchedule.findFirst({ where: { id: String(body.id), schoolId } });
      if (!existing) return bad("Schedule not found", 404);
      const row = await db.sirenSchedule.update({
        where: { id: existing.id },
        data: { label, time: body.time, sound, durationSec, daysJson: JSON.stringify(days), note, enabled: body.enabled === false ? false : true },
      });
      await audit({ schoolId, actor, action: "UPDATE", entityType: "SirenSchedule", entityId: row.id, entityLabel: label, summary: `Siren schedule updated: ${label} at ${row.time}`, req });
      return NextResponse.json({ ok: true, schedule: row });
    }
    const row = await db.sirenSchedule.create({
      data: { schoolId, label, time: body.time, sound, durationSec, daysJson: JSON.stringify(days), note, enabled: body.enabled === false ? false : true },
    });
    await audit({ schoolId, actor, action: "CREATE", entityType: "SirenSchedule", entityId: row.id, entityLabel: label, summary: `Siren schedule created: ${label} at ${row.time} (${await soundLabel(schoolId, sound)})`, req });
    return NextResponse.json({ ok: true, schedule: row });
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

  // ── history.clear ── wipe the firing history (keeps schedules)
  if (action === "history.clear") {
    const gone = await db.sirenEvent.deleteMany({ where: { schoolId } });
    await audit({ schoolId, actor, action: "DELETE", entityType: "SirenEvent", entityId: "bulk", entityLabel: "Siren history", summary: `Siren firing history cleared (${gone.count} rows)`, req });
    return NextResponse.json({ ok: true, removed: gone.count });
  }

  return bad("Unknown action");
}

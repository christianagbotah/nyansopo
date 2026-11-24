import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";

// ── School siren & bell management (wave 55) ──
// Timetabled rings (assembly, change of lesson, break, closing) plus a live
// console for manual / emergency rings, with a full firing history. Sounds
// are synthesised in the browser (Web Audio) — no audio files needed.
//
// Scheduled rings are fired by any open console client (front-desk machine);
// the server de-duplicates SCHEDULED fires per schedule+minute so several
// open tabs (or the whole staff room) cannot double-log a ring.

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });

const SOUNDS = ["BELL", "SIREN", "CHIME", "KLAXON", "BUGLE"] as const;
const isSound = (v: unknown): v is (typeof SOUNDS)[number] => typeof v === "string" && (SOUNDS as readonly string[]).includes(v);
const clampDuration = (v: unknown): number => {
  const n = Math.round(Number(v));
  if (!isFinite(n)) return 10;
  return Math.min(120, Math.max(2, n));
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

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["siren.manage"] });
  if (!auth.ok) return auth.res;

  const [schedules, events] = await Promise.all([
    db.sirenSchedule.findMany({ where: { schoolId }, orderBy: { time: "asc" } }),
    db.sirenEvent.findMany({ where: { schoolId }, orderBy: { createdAt: "desc" }, take: 120 }),
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

  let body: any;
  try { body = await req.json(); } catch { return bad("Invalid JSON body"); }
  const action = body?.action;
  const actorName = actor.fullName;

  // ── schedule.save ── create or update a ring on the timetable
  if (action === "schedule.save") {
    const label = optStr(body.label, 80);
    if (!label) return bad("A label is required (e.g. 'Morning assembly')");
    if (!isTime(body.time)) return bad("Time must be HH:MM (24-hour), e.g. 07:45");
    const sound = isSound(body.sound) ? body.sound : "BELL";
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
    await audit({ schoolId, actor, action: "CREATE", entityType: "SirenSchedule", entityId: row.id, entityLabel: label, summary: `Siren schedule created: ${label} at ${row.time} (${sound})`, req });
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
    const sound = isSound(body.sound) ? body.sound : "BELL";
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
    if (!label) label = sound === "SIREN" ? "Emergency siren" : kind === "TEST" ? `Sound test — ${sound}` : `Manual ring — ${sound}`;

    const row = await db.sirenEvent.create({
      data: { schoolId, scheduleId, label, sound, durationSec, kind, byName: actorName, byRole: actor.role, note: optStr(body.note, 300) },
    });
    await audit({
      schoolId, actor, action: "CREATE", entityType: "SirenEvent", entityId: row.id, entityLabel: label,
      summary: kind === "SCHEDULED" ? `Siren rang on schedule: ${label} (${sound} ${durationSec}s)` : `Siren ring (${kind.toLowerCase()}): ${label} (${sound} ${durationSec}s)`,
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

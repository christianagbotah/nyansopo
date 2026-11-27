import { PrismaClient } from "@prisma/client";
import { randomBytes } from "crypto";

// ── wave 72 — appointment helpers shared by the front-desk ops route and
// the public /appoint/{code} decision link ────────────────────────────────

const DAY_NAMES = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
const hhmm = (min: number) => `${String(Math.floor(min / 60)).padStart(2, "0")}:${String(min % 60).padStart(2, "0")}`;

/** Statuses where a visitor is still expected (live board, countdowns, T-2 alarms). */
export const APPT_ACTIVE = ["SCHEDULED", "CONFIRMED", "RESCHEDULED"];

// School hours — Mon–Fri 07:00–17:00 (Ghana runs on GMT: UTC == wall time).
// Outside them only ADMINISTRATION staff may host a visitor.
const SCHOOL_HOURS = { start: 7, end: 17 };
const schoolHoursLabel = `Mon–Fri ${String(SCHOOL_HOURS.start).padStart(2, "0")}:00–${String(SCHOOL_HOURS.end).padStart(2, "0")}:00`;

/**
 * The full booking guard for a proposed time, combining both legs of the
 * wave-72 rule — school hours (non-administration staff are school-hours
 * only) and class hours (the timetable is the truth for teachers).
 * Returns a user-safe refusal message, or null when the slot is fine.
 */
export async function oddHoursGuard(db: PrismaClient, staff: { id: string; fullName: string; category: string } | null | undefined, at: Date): Promise<string | null> {
  if (!staff) return null;
  const weekend = at.getUTCDay() === 0 || at.getUTCDay() === 6;
  const outOfHours = weekend || at.getUTCHours() < SCHOOL_HOURS.start || at.getUTCHours() >= SCHOOL_HOURS.end;
  if (staff.category !== "ADMINISTRATION" && outOfHours) {
    return `Odd hours — visitors cannot see ${staff.fullName} then. ${staff.category === "TEACHING" ? "Teachers" : "Non-teaching staff"} take visitors ${schoolHoursLabel} only; only administration staff (head, bursar …) can host a visitor at odd hours.`;
  }
  const clash = await teachingClash(db, staff, at);
  if (clash) return `Class clash — ${clash.detail}`;
  return null;
}

export function newDecisionToken(): string {
  return randomBytes(16).toString("hex");
}

/** APT-YYYY-NNNN — per school+year sequence (the desk books serially; good enough). */
export async function genApptCode(db: PrismaClient, schoolId: string): Promise<string> {
  const year = new Date().getFullYear();
  const prefix = `APT-${year}-`;
  const lastCode = (await db.appointment.findFirst({
    where: { schoolId, code: { startsWith: prefix } },
    orderBy: { code: "desc" },
    select: { code: true },
  }))?.code;
  const n = lastCode ? parseInt(lastCode.slice(prefix.length), 10) + 1 : 1;
  return `${prefix}${String(n).padStart(4, "0")}`;
}

/** The staff member's decision link (public page; the token IS the authorization). */
export function apptDecisionLink(origin: string, code: string | null | undefined, token: string | null | undefined): string | null {
  if (!code || !token) return null;
  const base = process.env.PUBLIC_BASE_URL || process.env.HUBTEL_CALLBACK_BASE || origin;
  return `${base.replace(/\/$/, "")}/appoint/${code}?t=${token}`;
}

/**
 * Class-hours rule — a visitor may not pull a TEACHING staff member out of
 * a lesson; the timetable is the truth (administration & non-teaching staff
 * are checked against school hours by the route's oddHoursError instead).
 * Returns { slotId, detail } for the clashing slot, or null when free.
 */
export async function teachingClash(db: PrismaClient, staff: { id: string; category: string; fullName: string }, at: Date) {
  if (staff.category !== "TEACHING") return null;
  const jsDay = at.getUTCDay(); // 0 Sun … 6 Sat (slot minutes are UTC-based app-wide)
  const minutes = at.getUTCHours() * 60 + at.getUTCMinutes();
  const slots = await db.timetableSlot.findMany({
    where: { staffId: staff.id, dayOfWeek: jsDay },
    include: { classroom: { select: { name: true } } },
  });
  const clash = slots.find((s) => minutes >= s.startMinute && minutes < s.endMinute);
  if (!clash) return null;
  return {
    slotId: clash.id,
    detail: `${staff.fullName} is teaching ${clash.subjectName} (${clash.classroom.name}) ${hhmm(clash.startMinute)}–${hhmm(clash.endMinute)} on ${DAY_NAMES[jsDay]}s — pick a free period or a school-holiday hour`,
  };
}

// ── wave 73 — free/break windows: the break-hours rule made explicit ────
// A TEACHING staff member takes visitors in the gaps between their lessons
// (breaks, before the first lesson, after school) inside school hours; a
// NON_TEACHING staff member is free across the whole school day; and
// ADMINISTRATION any hour — the head's door never closes. The timetable is
// still the truth: a window is only "free" when no lesson is scheduled in it.

export type FreeWindow = { start: string; end: string; minutes: number; label: string };
export type FreeWindows = { anytime: boolean; wholeSchoolDay: boolean; windows: FreeWindow[] };

const DAY_OPEN = SCHOOL_HOURS.start * 60; // 420 — 07:00
const DAY_CLOSE = SCHOOL_HOURS.end * 60; // 1020 — 17:00

const mkWindow = (s: number, e: number, hasLessons: boolean): FreeWindow => {
  const minutes = e - s;
  let label = "Free period";
  if (!hasLessons) label = "No lessons — the whole day is free";
  else if (s === DAY_OPEN && e < DAY_CLOSE) label = "Before first lesson";
  else if (e === DAY_CLOSE && s > DAY_OPEN) label = "After school";
  else if (minutes <= 40) label = "Break";
  return { start: hhmm(s), end: hhmm(e), minutes, label };
};

/**
 * The open doors of a staff member on a date ("YYYY-MM-DD" or ISO): the
 * windows a visitor can be booked into. TEACHING staff get the complement
 * of their timetable slots within school hours (gaps ≥ 15 min, labelled
 * break / free period / before first lesson / after school); NON_TEACHING
 * get the whole school day; ADMINISTRATION reports anytime.
 */
export async function staffFreeWindows(
  db: PrismaClient,
  staff: { id: string; category: string } | null | undefined,
  dateISO: string,
): Promise<FreeWindows> {
  const none: FreeWindows = { anytime: false, wholeSchoolDay: false, windows: [] };
  if (!staff) return none;
  const day = new Date(`${dateISO.slice(0, 10)}T00:00:00.000Z`);
  if (isNaN(day.getTime())) return none;
  if (staff.category === "ADMINISTRATION") return { anytime: true, wholeSchoolDay: false, windows: [] };
  if (staff.category !== "TEACHING") {
    return { anytime: false, wholeSchoolDay: true, windows: [{ start: hhmm(DAY_OPEN), end: hhmm(DAY_CLOSE), minutes: DAY_CLOSE - DAY_OPEN, label: "School hours" }] };
  }
  const slots = await db.timetableSlot.findMany({
    where: { staffId: staff.id, dayOfWeek: day.getUTCDay() },
    orderBy: { startMinute: "asc" },
    select: { startMinute: true, endMinute: true },
  });
  const busy: { s: number; e: number }[] = [];
  for (const sl of slots) {
    const last = busy[busy.length - 1];
    if (last && sl.startMinute <= last.e) last.e = Math.max(last.e, sl.endMinute);
    else busy.push({ s: sl.startMinute, e: sl.endMinute });
  }
  const windows: FreeWindow[] = [];
  let cursor = DAY_OPEN;
  for (const b of busy) {
    if (b.s - cursor >= 15) windows.push(mkWindow(cursor, b.s, true));
    cursor = Math.max(cursor, b.e);
  }
  if (DAY_CLOSE - cursor >= 15) windows.push(mkWindow(cursor, DAY_CLOSE, true));
  return { anytime: false, wholeSchoolDay: false, windows };
}

/**
 * For a refused slot: a human hint pointing the desk (or the staff member)
 * at the next open door that day — "Their next free slot that day is
 * 10:00–10:20 (break)." Empty string when there is nothing to point at.
 */
export async function nextFreeHint(
  db: PrismaClient,
  staff: { id: string; category: string } | null | undefined,
  at: Date,
): Promise<string> {
  if (!staff || staff.category !== "TEACHING") return "";
  try {
    const { windows } = await staffFreeWindows(db, staff, at.toISOString());
    const minutes = at.getUTCHours() * 60 + at.getUTCMinutes();
    const next = windows.find((w) => {
      const [h, m] = w.start.split(":").map(Number);
      return h * 60 + m > minutes;
    });
    if (!next) return "No free slot left that day — try another day.";
    return `Their next free slot that day is ${next.start}–${next.end} (${next.label.toLowerCase()}).`;
  } catch {
    return "";
  }
}

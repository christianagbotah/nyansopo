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

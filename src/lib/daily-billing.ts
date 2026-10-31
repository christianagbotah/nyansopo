import { PrismaClient, Prisma } from "@prisma/client";
import type { DailyCharge } from "@prisma/client";

// ── Daily-service billing helpers (shared by API + offline sync) ──

/** Apply a POS payment to a learner's daily ledger: oldest OWED charges first (FIFO).
 *  `onlyCategory` restricts settlement to one service category — used when paying
 *  from the transport wallet, which may only clear transport-fare charges. */
export async function settleDailyCharges(db: PrismaClient, paymentId: string, studentId: string, amountCents: number, onlyCategory?: string) {
  let remaining = amountCents;
  let owed: DailyCharge[];
  if (onlyCategory) {
    const catCharges = await db.dailyCharge.findMany({
      where: { studentId, status: "OWED", service: { category: onlyCategory } },
      orderBy: { date: "asc" },
    });
    owed = catCharges;
  } else {
    owed = await db.dailyCharge.findMany({
      where: { studentId, status: "OWED" },
      orderBy: { date: "asc" },
    });
  }
  for (const c of owed) {
    if (remaining <= 0) break;
    if (remaining >= c.amountCents) {
      await db.dailyCharge.update({ where: { id: c.id }, data: { status: "PAID", paymentId } });
      remaining -= c.amountCents;
    } else {
      // partial: split the charge into a paid part and keep the remainder owed
      const paidPart = remaining;
      await db.dailyCharge.update({ where: { id: c.id }, data: { status: "PAID", paymentId, amountCents: paidPart } });
      await db.dailyCharge.create({
        data: { serviceId: c.serviceId, studentId, schoolId: c.schoolId, date: c.date, amountCents: c.amountCents - paidPart, status: "OWED", usage: c.usage },
      });
      remaining = 0;
    }
  }
  return amountCents - remaining; // actually applied
}

/** Effective price for a learner on a service: override → subsidised override → standard price. */
export function effectivePrice(svc: { priceCents: number }, enrollment?: { priceCentsOverride: number | null } | null) {
  return enrollment?.priceCentsOverride ?? svc.priceCents;
}

/** Generate charges for one school day for all active enrolments whose service runs that weekday. */
export async function generateDayCharges(db: PrismaClient, schoolId: string, date: Date) {
  const weekday = date.getDay(); // 0 Sun … 6 Sat
  const services = await db.dailyService.findMany({ where: { schoolId, active: true } });
  let created = 0, skipped = 0;
  for (const svc of services) {
    const days = svc.weekdays.split(",").map(Number);
    if (!days.includes(weekday)) continue;
    const enrollments = await db.dailyServiceEnrollment.findMany({ where: { serviceId: svc.id, active: true } });
    for (const en of enrollments) {
      // non-unique index (partial-payment splits): any existing row for the day means the learner was already billed
      const existing = await db.dailyCharge.findFirst({ where: { serviceId: svc.id, studentId: en.studentId, date } });
      if (existing) { skipped++; continue; }
      await db.dailyCharge.create({ data: { serviceId: svc.id, studentId: en.studentId, schoolId, date, amountCents: effectivePrice(svc, en) } });
      created++;
    }
  }
  return { created, skipped };
}

// ── Transport-fare usage (iedupro §1): picked at collection time for the day's
//    transport charge. MORNING/AFTERNOON halve the fare, BOTH keeps it whole,
//    NOT_USING waives it, BOARDED keeps it whole (trip already recorded). */
export const TRANSPORT_USAGES = ["BOTH", "MORNING", "AFTERNOON", "NOT_USING", "BOARDED"] as const;
export type TransportUsage = (typeof TRANSPORT_USAGES)[number];

/** Apply a usage choice to a learner's transport-fare charge on a given day.
 *  Returns the updated charge, or null if no transport charge exists that day. */
export async function applyTransportUsage(db: PrismaClient, schoolId: string, studentId: string, date: Date, usage: TransportUsage) {
  const dStart = new Date(date); dStart.setHours(0, 0, 0, 0);
  const dEnd = new Date(date); dEnd.setHours(23, 59, 59, 999);
  const charge = await db.dailyCharge.findFirst({
    where: { schoolId, studentId, date: { gte: dStart, lte: dEnd }, service: { category: "TRANSPORT" } },
    include: { service: true },
  });
  if (!charge) return null;
  const base = charge.amountCents;
  let amount = base;
  let status = charge.status;
  if (usage === "NOT_USING") {
    status = "WAIVED";
    amount = base; // keep the original fare on record; waived means nothing to collect
  } else if (usage === "MORNING" || usage === "AFTERNOON") {
    amount = Math.round(base / 2);
  } // BOTH / BOARDED keep the full fare
  if (status === "PAID") {
    // already settled — only stamp the usage marker
    return db.dailyCharge.update({ where: { id: charge.id }, data: { usage } });
  }
  if (status === "WAIVED" && usage !== "NOT_USING") status = "OWED"; // re-instating a waived charge
  return db.dailyCharge.update({ where: { id: charge.id }, data: { usage, amountCents: amount, status } });
}

// ── Daily-fee collection policy (iedupro §1) ──
export type CollectionMode = "CLASSROOM" | "CASHIER" | "HYBRID";
export type TeacherPerms = { mode: "ALL" | "NONE" | "SELECTIVE"; classroomIds: string[]; serviceIds: string[] };

export const DEFAULT_TEACHER_PERMS: TeacherPerms = { mode: "ALL", classroomIds: [], serviceIds: [] };

export function parseTeacherPerms(raw: string | null | undefined): TeacherPerms {
  if (!raw) return { ...DEFAULT_TEACHER_PERMS };
  try {
    const p = JSON.parse(raw);
    if (p && typeof p === "object" && ["ALL", "NONE", "SELECTIVE"].includes(p.mode)) {
      return { mode: p.mode, classroomIds: Array.isArray(p.classroomIds) ? p.classroomIds : [], serviceIds: Array.isArray(p.serviceIds) ? p.serviceIds : [] };
    }
  } catch { /* fallthrough */ }
  return { ...DEFAULT_TEACHER_PERMS };
}

/** Can this user collect a daily payment for this student (classroom + services scope)?
 *  Admins always may. CLASSROOM mode → teachers only; CASHIER mode → bursars/admins only;
  HYBRID → both. Teachers are additionally bound by the permission matrix. */
export function collectionAllowed(
  role: string,
  mode: CollectionMode,
  perms: TeacherPerms,
  ctx: { classroomId: string | null; serviceIds: string[] },
): { ok: boolean; reason?: string } {
  const admin = role === "SCHOOL_ADMIN" || role === "GROUP_OWNER";
  if (admin) return { ok: true };
  const isTeacher = role === "TEACHER";
  const isBursar = role === "ACCOUNTANT";
  if (mode === "CLASSROOM" && !isTeacher) return { ok: false, reason: "Collection is classroom-only — ask the class teacher to collect." };
  if (mode === "CASHIER" && !isBursar) return { ok: false, reason: "Collection is cashier-only — take payments to the bursar's counter." };
  if (isTeacher) {
    if (perms.mode === "NONE") return { ok: false, reason: "You have no daily-fee collection permission." };
    if (perms.mode === "SELECTIVE") {
      if (!ctx.classroomId || !perms.classroomIds.includes(ctx.classroomId))
        return { ok: false, reason: "You may not collect for this class." };
      const outside = ctx.serviceIds.filter((s) => !perms.serviceIds.includes(s));
      if (outside.length)
        return { ok: false, reason: "You may not collect some of these fee types." };
    }
  }
  return { ok: true };
}

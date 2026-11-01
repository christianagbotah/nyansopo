import { PrismaClient, Prisma } from "@prisma/client";
import type { DailyCharge } from "@prisma/client";

// ── Daily-service billing helpers (shared by API + offline sync) ──
//  iEduPro CI3 parity (Daily_fee_model):
//   • route fare is ONE-WAY — 'in'/'out' charge 1×, 'both' 2×, 'none' 0
//     (e.g. route fare GH₵8 → morning only GH₵8, both ways GH₵16)
//   • transport day-charges materialise lazily — only when a direction is
//     picked at the counter or the conductor marks boarding (never auto-billed)
//   • WEEKLY services (water) bill once per ISO week, on the first school day
//   • rates resolve per learner: enrolment override → classroom rate → standard

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

/** Effective price for a learner on a service: enrolment override → classroom
 *  rate (iedupro per-class rate matrix) → standard price. */
export function effectivePrice(
  svc: { priceCents: number },
  enrollment?: { priceCentsOverride: number | null } | null,
  classRateCents?: number | null,
) {
  return enrollment?.priceCentsOverride ?? classRateCents ?? svc.priceCents;
}

// ── Transport fare resolution (CI3 §record_transport_decision / mark_boarding) ──
// one-way fare for a learner = stop-level override (pickup/drop stop) → route per-trip fare
export type TransportFareInfo = {
  onewayCents: number;
  routeId: string;
  routeName: string;
  stopName: string | null; // the stop that set the fare (when a per-stop override applies)
};

export async function resolveTransportFare(db: PrismaClient, studentId: string): Promise<TransportFareInfo | null> {
  const assignment = await db.transportAssignment.findFirst({
    where: { studentId, active: true },
    include: { route: { include: { stopRecords: true } } },
  });
  if (!assignment) return null;
  const route = assignment.route;
  // per-stop override: the learner's pickup/drop stop may carry its own one-way fare
  const stopName = assignment.pickupStop ?? assignment.dropStop;
  let stopFare: number | null = null;
  if (stopName) {
    const stop = route.stopRecords.find((s) => s.name.trim().toLowerCase() === stopName.trim().toLowerCase());
    if (stop && stop.fareCents != null) stopFare = stop.fareCents;
  }
  return {
    onewayCents: stopFare ?? route.farePerTripCents ?? 0,
    routeId: route.id,
    routeName: route.name,
    stopName: stopFare != null ? stopName : null,
  };
}

/** Generate charges for one school day for all active enrolments whose service runs that weekday.
 *  TRANSPORT-category services are skipped here — iEduPro bills a transport day-charge
 *  only when a direction is confirmed (counter usage pick or conductor boarding mark). */
export async function generateDayCharges(db: PrismaClient, schoolId: string, date: Date) {
  const day = new Date(date); day.setHours(0, 0, 0, 0);
  const weekday = day.getDay(); // 0 Sun … 6 Sat
  const services = await db.dailyService.findMany({ where: { schoolId, active: true }, include: { classRates: true } });
  // classroom rate map per service
  const rateByServiceClass = new Map<string, Map<string, number>>();
  for (const svc of services) {
    const m = new Map<string, number>();
    svc.classRates.forEach((r) => m.set(r.classroomId, r.priceCents));
    rateByServiceClass.set(svc.id, m);
  }
  // active enrolments + classroom per learner (for rate resolution + weekly checks)
  const enrollments = await db.dailyServiceEnrollment.findMany({
    where: { active: true, service: { schoolId } },
    include: { service: true },
  });
  const activeByStudent = await db.enrollment.findMany({
    where: { schoolId, status: "ACTIVE" },
    select: { studentId: true, classroomId: true },
  });
  const classroomByStudent = new Map<string, string>();
  activeByStudent.forEach((e) => classroomByStudent.set(e.studentId, e.classroomId));
  // weekly services: charge only on the learner's first charge day inside the ISO week
  const weekStart = new Date(day); weekStart.setDate(day.getDate() - ((day.getDay() + 6) % 7)); weekStart.setHours(0, 0, 0, 0);
  const weekEnd = new Date(weekStart); weekEnd.setDate(weekStart.getDate() + 7); weekEnd.setMilliseconds(-1);
  const weeklyChargesThisWeek = await db.dailyCharge.findMany({
    where: { schoolId, date: { gte: weekStart, lte: weekEnd }, service: { chargeMode: "WEEKLY" } },
    select: { serviceId: true, studentId: true },
  });
  const weeklySeen = new Set(weeklyChargesThisWeek.map((c) => `${c.serviceId}:${c.studentId}`));

  let created = 0, skipped = 0, transportSkipped = 0;
  for (const svc of services) {
    if (svc.category === "TRANSPORT") { transportSkipped += enrollments.filter((e) => e.serviceId === svc.id).length; continue; }
    const days = svc.weekdays.split(",").map(Number);
    if (!days.includes(weekday)) continue;
    const rates = rateByServiceClass.get(svc.id)!;
    for (const en of enrollments.filter((e) => e.serviceId === svc.id)) {
      if (svc.chargeMode === "WEEKLY" && weeklySeen.has(`${svc.id}:${en.studentId}`)) { skipped++; continue; }
      // non-unique index (partial-payment splits): any existing row for the day means the learner was already billed
      const existing = await db.dailyCharge.findFirst({ where: { serviceId: svc.id, studentId: en.studentId, date: day } });
      if (existing) { skipped++; continue; }
      const classRate = classroomByStudent.get(en.studentId) ? rates.get(classroomByStudent.get(en.studentId)!) ?? null : null;
      const amount = effectivePrice(svc, en, classRate);
      if (amount <= 0) { skipped++; continue; }
      await db.dailyCharge.create({ data: { serviceId: svc.id, studentId: en.studentId, schoolId, date: day, amountCents: amount } });
      created++;
      if (svc.chargeMode === "WEEKLY") weeklySeen.add(`${svc.id}:${en.studentId}`);
    }
  }
  return { created, skipped, transportSkipped };
}

// ── Transport usage / boarding direction (iedupro §1 — one-way fare model) ──
//  MORNING (in) → 1× one-way · AFTERNOON (out) → 1× one-way · BOTH → 2× one-way
//  NOT_USING (none) → waived, GH₵0 · BOARDED → the fare recorded on the boarding mark
export const TRANSPORT_USAGES = ["BOTH", "MORNING", "AFTERNOON", "NOT_USING", "BOARDED"] as const;
export type TransportUsage = (typeof TRANSPORT_USAGES)[number];

/** Fare amounts for each direction option, given a learner's one-way route fare.
 *  Used by the collection UI to preview (iedupro shows the live total as the
 *  direction is picked: one-way 8 → both ways 16). */
export function usageFare(onewayCents: number, boardingFareCents?: number | null): Record<TransportUsage, number> {
  return {
    MORNING: onewayCents,
    AFTERNOON: onewayCents,
    BOTH: onewayCents * 2,
    NOT_USING: 0,
    BOARDED: boardingFareCents ?? onewayCents * 2,
  };
}

/** Apply a usage choice to a learner's transport-fare charge on a given day.
 *  iEduPro parity: the charge is created lazily here (a direction pick at the
 *  counter materialises the day's transport bill). Returns the charge, or null
 *  when the learner has no active route assignment. */
export async function applyTransportUsage(db: PrismaClient, schoolId: string, studentId: string, date: Date, usage: TransportUsage) {
  const fare = await resolveTransportFare(db, studentId);
  if (!fare) return null; // CI3: "Student not assigned to any route"
  const dStart = new Date(date); dStart.setHours(0, 0, 0, 0);
  const dEnd = new Date(date); dEnd.setHours(23, 59, 59, 999);
  const svc = await db.dailyService.findFirst({ where: { schoolId, category: "TRANSPORT" }, orderBy: { name: "asc" } });
  if (!svc) return null;

  // the day's boarding mark (if the conductor already recorded it) drives BOARDED
  const boarding = await db.transportBoarding.findFirst({
    where: { assignment: { studentId, active: true }, date: { gte: dStart, lte: dEnd } },
  });
  const options = usageFare(fare.onewayCents, boarding?.fareCents ?? null);
  const amount = options[usage];

  const charge = await db.dailyCharge.findFirst({
    where: { schoolId, studentId, date: { gte: dStart, lte: dEnd }, service: { category: "TRANSPORT" } },
    include: { service: true },
  });
  if (!charge) {
    return db.dailyCharge.create({
      data: {
        serviceId: svc.id, studentId, schoolId, date: dStart,
        amountCents: amount, usage,
        status: usage === "NOT_USING" ? "WAIVED" : "OWED",
      },
    });
  }
  if (charge.status === "PAID") {
    // already settled — only stamp the usage marker
    return db.dailyCharge.update({ where: { id: charge.id }, data: { usage } });
  }
  let status = charge.status;
  if (usage === "NOT_USING") status = "WAIVED";
  else if (status === "WAIVED") status = "OWED"; // re-instating a waived charge
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
 *  HYBRID → both. Teachers are additionally bound by the permission matrix. */
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

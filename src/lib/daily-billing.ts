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
 *  from the transport wallet, which may only clear transport-fare charges.
 *  `serviceId` restricts settlement to ONE fee item — the per-item payment entry
 *  (iEduPro §apply_payment: each item's amount clears that item's arrears FIFO,
 *  any excess is returned to the caller to hold as item advance). */
export async function settleDailyCharges(db: PrismaClient, paymentId: string, studentId: string, amountCents: number, onlyCategory?: string, serviceId?: string) {
  let remaining = amountCents;
  let owed: DailyCharge[];
  if (serviceId) {
    owed = await db.dailyCharge.findMany({
      where: { studentId, status: "OWED", serviceId },
      orderBy: { date: "asc" },
    });
  } else if (onlyCategory) {
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

// ── Per-item prepaid balances (iEduPro daily_fee_wallet parity) ──
//  Each fee item carries its own prepaid credit. Charges AUTO-DEDUCT from it
//  at generation time (CI3 apply_charge: balance → 0, remainder to arrears);
//  excess counter payments land back on it as advance (CI3 apply_payment).
export async function itemBalanceCents(db: PrismaClient, studentId: string, serviceId: string): Promise<number> {
  const b = await db.serviceBalance.findUnique({ where: { studentId_serviceId: { studentId, serviceId } } });
  return b?.prepaidCents ?? 0;
}

/** Credit a learner's per-item balance (top-up or payment advance). Writes the
 *  WalletTxn trail. Returns the new balance. */
export async function creditItemBalance(db: PrismaClient, opts: {
  schoolId: string; studentId: string; serviceId: string; amountCents: number;
  kind?: "TOPUP" | "ADJUST"; note?: string | null; byName?: string;
}): Promise<number> {
  const b = await db.serviceBalance.upsert({
    where: { studentId_serviceId: { studentId: opts.studentId, serviceId: opts.serviceId } },
    update: { prepaidCents: { increment: opts.amountCents } },
    create: { schoolId: opts.schoolId, studentId: opts.studentId, serviceId: opts.serviceId, prepaidCents: opts.amountCents },
  });
  await db.walletTxn.create({
    data: {
      schoolId: opts.schoolId, studentId: opts.studentId, wallet: "ITEM", kind: opts.kind ?? "TOPUP",
      amountCents: opts.amountCents, balanceAfter: b.prepaidCents, note: opts.note ?? null,
      byName: opts.byName ?? null, serviceId: opts.serviceId,
    },
  });
  return b.prepaidCents;
}

/** Auto-deduct a fresh charge from the learner's per-item prepaid balance
 *  (CI3 apply_charge parity): settles the charge fully or partially from the
 *  item balance, leaving the remainder owed. Writes the WalletTxn PAY trail.
 *  Returns the amount covered by the balance. */
export async function autoDeductFromItemBalance(db: PrismaClient, charge: { id: string; serviceId: string; studentId: string; schoolId: string; amountCents: number; date: Date }, opts?: { byName?: string }): Promise<number> {
  const bal = await itemBalanceCents(db, charge.studentId, charge.serviceId);
  if (bal <= 0) return 0;
  const covered = Math.min(bal, charge.amountCents);
  if (covered >= charge.amountCents) {
    await db.dailyCharge.update({ where: { id: charge.id }, data: { status: "PAID" } });
  } else {
    // partial: paid part keeps the charge row, the remainder re-arms as OWED
    await db.dailyCharge.update({ where: { id: charge.id }, data: { status: "PAID", amountCents: covered } });
    await db.dailyCharge.create({
      data: { serviceId: charge.serviceId, studentId: charge.studentId, schoolId: charge.schoolId, date: charge.date, amountCents: charge.amountCents - covered, status: "OWED", usage: null },
    });
  }
  await db.serviceBalance.update({ where: { studentId_serviceId: { studentId: charge.studentId, serviceId: charge.serviceId } }, data: { prepaidCents: { decrement: covered } } });
  await db.walletTxn.create({
    data: {
      schoolId: charge.schoolId, studentId: charge.studentId, wallet: "ITEM", kind: "PAY",
      amountCents: -covered, balanceAfter: bal - covered, note: "auto-deduct from item balance",
      byName: opts?.byName ?? null, serviceId: charge.serviceId,
    },
  });
  return covered;
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

// ── Daily-fee audit log (iEduPro daily_fee_audit_log parity) ──
//  Every wallet/charge event writes an immutable audit row: action type, the
//  service it relates to, amount, wallet balance before/after, arrears
//  before/after, who performed it, reference and notes. Writes are
//  best-effort — an audit failure must never break a payment or charge.
export type DailyFeeAuditAction =
  | "CHARGE" | "CHARGE_REVERSAL" | "PAYMENT" | "PAYMENT_REVERSAL"
  | "TOPUP" | "ADJUSTMENT" | "RECONCILIATION";

export type DailyFeeAuditInput = {
  schoolId: string;
  studentId: string;
  actionType: DailyFeeAuditAction;
  /** the service the event relates to (service name, wallet name, or MULTIPLE) */
  serviceCode: string;
  amountCents: number;
  /** wallet balance (prepaid + transport) before/after — derived from current state when omitted */
  balanceBeforeCents?: number;
  balanceAfterCents?: number;
  /** arrears (sum of OWED daily charges) before/after — derived from current state when omitted */
  arrearsBeforeCents?: number;
  arrearsAfterCents?: number;
  /** the acting user; falls back to "System" when the lib doesn't know the actor */
  performedByName?: string;
  performedByRole?: string;
  reference?: string | null;
  notes?: string | null;
};

/** Write one DailyFeeAudit row (iEduPro daily_fee_audit_log). Any before/after
 *  figure not supplied is derived from the current ledger state. Never throws. */
export async function logDailyFeeAudit(db: PrismaClient, input: DailyFeeAuditInput): Promise<void> {
  try {
    let balB = input.balanceBeforeCents;
    let balA = input.balanceAfterCents;
    let arrB = input.arrearsBeforeCents;
    let arrA = input.arrearsAfterCents;
    if (balB == null || balA == null) {
      const w = await db.studentWallet.findUnique({ where: { studentId: input.studentId } });
      const total = (w?.prepaidCents ?? 0) + (w?.transportCents ?? 0);
      if (balB == null) balB = total;
      if (balA == null) balA = total;
    }
    if (arrB == null || arrA == null) {
      const owed = await db.dailyCharge.aggregate({ where: { studentId: input.studentId, status: "OWED" }, _sum: { amountCents: true } });
      const total = owed._sum.amountCents ?? 0;
      if (arrB == null) arrB = total;
      if (arrA == null) arrA = total;
    }
    await db.dailyFeeAudit.create({
      data: {
        schoolId: input.schoolId,
        studentId: input.studentId,
        actionType: input.actionType,
        serviceCode: input.serviceCode,
        amountCents: Math.round(input.amountCents || 0),
        balanceBeforeCents: balB ?? 0,
        balanceAfterCents: balA ?? 0,
        arrearsBeforeCents: arrB ?? 0,
        arrearsAfterCents: arrA ?? 0,
        performedByName: input.performedByName?.trim() || "System",
        performedByRole: input.performedByRole?.trim() || "SYSTEM",
        reference: input.reference ?? null,
        notes: input.notes ?? null,
      },
    });
  } catch {
    // best-effort: an audit failure must never break the billing flow
  }
}

/** Total wallet credit for a learner (prepaid + transport), 0 when no wallet. */
export async function walletTotalCents(db: PrismaClient, studentId: string): Promise<number> {
  const w = await db.studentWallet.findUnique({ where: { studentId } });
  return (w?.prepaidCents ?? 0) + (w?.transportCents ?? 0);
}

/** Current daily-fee arrears for a learner (sum of OWED charges). */
export async function arrearsTotalCents(db: PrismaClient, studentId: string): Promise<number> {
  const owed = await db.dailyCharge.aggregate({ where: { studentId, status: "OWED" }, _sum: { amountCents: true } });
  return owed._sum.amountCents ?? 0;
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
 *  only when a direction is confirmed (counter usage pick or conductor boarding mark).
 *  Each created charge writes a DailyFeeAudit row (iEduPro daily_fee_audit_log);
 *  pass the acting user so the trail shows who generated the day. */
export async function generateDayCharges(
  db: PrismaClient,
  schoolId: string,
  date: Date,
  opts?: { performedByName?: string; performedByRole?: string },
) {
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

  // audit-trail baselines (iEduPro daily_fee_audit_log): per-learner arrears + wallet totals
  const owedAgg = await db.dailyCharge.groupBy({ by: ["studentId"], where: { schoolId, status: "OWED" }, _sum: { amountCents: true } });
  const arrearsByStudent = new Map(owedAgg.map((g) => [g.studentId, g._sum.amountCents ?? 0]));
  const walletRows = await db.studentWallet.findMany({ where: { schoolId }, select: { studentId: true, prepaidCents: true, transportCents: true } });
  const walletByStudent = new Map(walletRows.map((w) => [w.studentId, w.prepaidCents + w.transportCents]));

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
      const charge = await db.dailyCharge.create({ data: { serviceId: svc.id, studentId: en.studentId, schoolId, date: day, amountCents: amount } });
      created++;
      // iEduPro apply_charge parity: auto-deduct from the item's prepaid balance
      // (balance → 0, remainder stays owed as arrears)
      const covered = await autoDeductFromItemBalance(db, charge, { byName: opts?.performedByName });
      // iEduPro daily_fee_audit_log: a CHARGE row per successful daily charge creation
      const arrearsBefore = arrearsByStudent.get(en.studentId) ?? 0;
      arrearsByStudent.set(en.studentId, arrearsBefore + (covered >= amount ? 0 : amount - covered));
      const walletTotal = walletByStudent.get(en.studentId) ?? 0;
      await logDailyFeeAudit(db, {
        schoolId, studentId: en.studentId, actionType: "CHARGE", serviceCode: svc.name, amountCents: amount,
        balanceBeforeCents: walletTotal, balanceAfterCents: walletTotal,
        arrearsBeforeCents: arrearsBefore, arrearsAfterCents: arrearsBefore + (covered >= amount ? 0 : amount - covered),
        performedByName: opts?.performedByName, performedByRole: opts?.performedByRole,
        reference: charge.id,
        notes: `day charge · ${svc.name} · ${day.toISOString().slice(0, 10)}${covered > 0 ? ` · GH₵${(covered / 100).toFixed(2)} auto-deducted from item balance` : ""}`,
      });
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
 *  when the learner has no active route assignment. Charge creations and fare
 *  adjustments are audit-logged (iEduPro daily_fee_audit_log). */
export async function applyTransportUsage(
  db: PrismaClient,
  schoolId: string,
  studentId: string,
  date: Date,
  usage: TransportUsage,
  opts?: { performedByName?: string; performedByRole?: string },
) {
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
    const createdCharge = await db.dailyCharge.create({
      data: {
        serviceId: svc.id, studentId, schoolId, date: dStart,
        amountCents: amount, usage,
        status: usage === "NOT_USING" ? "WAIVED" : "OWED",
      },
    });
    // iEduPro apply_charge parity: settle from the transport item's prepaid balance
    let covered = 0;
    if (createdCharge.status === "OWED" && amount > 0) {
      covered = await autoDeductFromItemBalance(db, createdCharge, { byName: opts?.performedByName });
    }
    if (amount > 0) {
      // iEduPro daily_fee_audit_log: the direction pick materialised the day's fare
      const owedAfter = amount - covered; // what actually landed in arrears
      const arrearsAfter = await arrearsTotalCents(db, studentId);
      await logDailyFeeAudit(db, {
        schoolId, studentId, actionType: "CHARGE", serviceCode: svc.name, amountCents: amount,
        arrearsBeforeCents: arrearsAfter - owedAfter, arrearsAfterCents: arrearsAfter,
        performedByName: opts?.performedByName, performedByRole: opts?.performedByRole,
        reference: createdCharge.id,
        notes: `transport fare · ${usage.toLowerCase()} · ${dStart.toISOString().slice(0, 10)}${covered > 0 ? ` · GH₵${(covered / 100).toFixed(2)} auto-deducted from item balance` : ""}`,
      });
    }
    return createdCharge;
  }
  if (charge.status === "PAID") {
    // already settled — only stamp the usage marker
    return db.dailyCharge.update({ where: { id: charge.id }, data: { usage } });
  }
  let status = charge.status;
  if (usage === "NOT_USING") status = "WAIVED";
  else if (status === "WAIVED") status = "OWED"; // re-instating a waived charge
  const updated = await db.dailyCharge.update({ where: { id: charge.id }, data: { usage, amountCents: amount, status } });
  // audit the fare change when it moves what the learner owes (amount change or waive/re-instate)
  if (charge.status === "OWED" && status === "OWED" && amount !== charge.amountCents) {
    const delta = amount - charge.amountCents;
    const arrearsAfter = await arrearsTotalCents(db, studentId);
    await logDailyFeeAudit(db, {
      schoolId, studentId, actionType: "CHARGE", serviceCode: charge.service.name, amountCents: delta,
      arrearsBeforeCents: arrearsAfter - delta, arrearsAfterCents: arrearsAfter,
      performedByName: opts?.performedByName, performedByRole: opts?.performedByRole,
      reference: charge.id,
      notes: `transport fare adjusted · ${usage.toLowerCase()} · ${dStart.toISOString().slice(0, 10)}`,
    });
  } else if (charge.status === "OWED" && status === "WAIVED") {
    const arrearsAfter = await arrearsTotalCents(db, studentId);
    await logDailyFeeAudit(db, {
      schoolId, studentId, actionType: "ADJUSTMENT", serviceCode: charge.service.name, amountCents: charge.amountCents,
      arrearsBeforeCents: arrearsAfter + charge.amountCents, arrearsAfterCents: arrearsAfter,
      performedByName: opts?.performedByName, performedByRole: opts?.performedByRole,
      reference: charge.id,
      notes: `transport fare waived · not using · ${dStart.toISOString().slice(0, 10)}`,
    });
  } else if (charge.status === "WAIVED" && status === "OWED") {
    const arrearsAfter = await arrearsTotalCents(db, studentId);
    await logDailyFeeAudit(db, {
      schoolId, studentId, actionType: "CHARGE", serviceCode: charge.service.name, amountCents: amount,
      arrearsBeforeCents: arrearsAfter - amount, arrearsAfterCents: arrearsAfter,
      performedByName: opts?.performedByName, performedByRole: opts?.performedByRole,
      reference: charge.id,
      notes: `transport fare re-instated · ${usage.toLowerCase()} · ${dStart.toISOString().slice(0, 10)}`,
    });
  }
  return updated;
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

import { db } from "@/lib/db";
import { logDailyFeeAudit, resolveTransportFare } from "@/lib/daily-billing";

/* Discount engine helpers shared by the discounts API and billing flows.
   iedupro parity: profiles (named rule-sets with per-class overrides) +
   an approval workflow (PENDING → APPROVED/ACTIVE, REJECTED, REVOKED).

   v2 — daily-fee discount profiles scope to a SET of services:
   · "*"                       → every daily service (lunch, breakfast, classes, …)
   · "svcA,svcB"               → only those services (e.g. lunch-only support)
   · PERCENT 1–100             → part discount, 100 = full (learner pays nothing)
   · FIXED pesewas             → flat amount off each covered service's price
   Multiple active applications may cover the same service — the most
   generous resulting rate wins, recomputed by recalcDailySubsidies(). */

export type DiscountSource =
  | { kind: "discount"; id: string; name: string; type: string; value: number; scope: string }
  | { kind: "profile"; id: string; name: string; type: string; value: number; category: string };

/** Parse a profile's serviceIds column → "*" or an explicit id list. */
export function parseServiceIds(raw: string | null | undefined): { all: boolean; ids: string[] } {
  if (!raw || raw.trim() === "*" || !raw.trim()) return { all: true, ids: [] };
  const ids = raw.split(",").map((s) => s.trim()).filter(Boolean);
  return { all: false, ids };
}

/** Effective percent/pesewa value for a learner, resolving profile per-class overrides. */
export async function effectiveValue(
  src: DiscountSource,
  studentId: string,
  classroomName?: string | null,
): Promise<{ type: string; value: number }> {
  if (src.kind === "discount") return { type: src.type, value: src.value };
  // profile: most specific rule wins — classroom+item > classroom > profile default
  const student = await db.student.findUnique({ where: { id: studentId }, include: { enrollments: { where: { status: "ACTIVE" }, include: { classroom: true }, take: 1 } } });
  const cls = classroomName ?? student?.enrollments[0]?.classroom?.name ?? null;
  const rules = await db.discountRule.findMany({ where: { profileId: src.id } });
  const byClass = rules.filter((r) => r.classroomName === cls);
  const anyClass = rules.filter((r) => !r.classroomName);
  const rule = byClass[byClass.length - 1] ?? anyClass[anyClass.length - 1];
  return { type: src.type, value: rule?.value ?? src.value };
}

/** Subsidised price for one service given a discount's type/value. */
export function subsidisedPrice(priceCents: number, type: string, value: number): number {
  if (type === "PERCENT") return Math.max(0, Math.round(priceCents * (1 - Math.min(100, value) / 100)));
  return Math.max(0, priceCents - Math.max(0, value));
}

type ActiveApp = {
  id: string;
  valueOverride: number | null;
  validFrom: Date | null;
  validTo: Date | null;
  discount: { scope: string; type: string; value: number } | null;
  profile: { id: string; category: string; method: string; value: number; serviceIds: string } | null;
  serviceId: string | null;
};

/** Is the application currently within its validity window? */
function inWindow(a: ActiveApp, now = new Date()): boolean {
  if (a.validFrom && a.validFrom > now) return false;
  if (a.validTo && a.validTo < now) return false;
  return true;
}

/** One resolved application: which services it covers and at what terms. */
type ResolvedApp = { appId: string; coversAll: boolean; covers: Set<string>; type: string; value: number };

/** Recompute a learner's subsidised daily rates from every ACTIVE daily-fee
 *  discount/profile application that covers each service. The most generous
 *  rate wins; services with no coverage revert to the standard rate.
 *  Writes an audit ADJUSTMENT row whenever a learner's effective rate changes. */
export async function recalcDailySubsidies(
  studentId: string,
  opts?: { performedByName?: string; performedByRole?: string; reason?: string },
): Promise<{ services: { serviceId: string; name: string; from: number | null; to: number | null }[] }> {
  const student = await db.student.findUnique({
    where: { id: studentId },
    include: { enrollments: { orderBy: { admissionDate: "desc" } } },
  });
  if (!student) return { services: [] };
  // learners link to a school through their enrolment (ACTIVE first, else most recent)
  const schoolId = student.enrollments.find((e) => e.status === "ACTIVE")?.schoolId
    ?? student.enrollments[0]?.schoolId;
  if (!schoolId) return { services: [] };

  const [services, appsRaw, activeEnrollment] = await Promise.all([
    db.dailyService.findMany({ where: { schoolId, active: true }, orderBy: { name: "asc" }, include: { classRates: true } }),
    db.discountApplication.findMany({
      where: { studentId, status: "ACTIVE" },
      include: { discount: true, profile: true },
    }) as unknown as Promise<ActiveApp[]>,
    db.enrollment.findFirst({ where: { studentId, status: "ACTIVE" }, include: { classroom: true } }),
  ]);
  const clsName = activeEnrollment?.classroom?.name ?? null;
  const clsId = activeEnrollment?.classroom?.id ?? null;

  // resolve each application once (profile per-class rules, coverage set)
  const resolved: ResolvedApp[] = [];
  for (const a of appsRaw) {
    const isDaily = a.discount ? a.discount.scope === "DAILY_SERVICE" : a.profile?.category === "DAILY_SERVICE";
    if (!isDaily || !inWindow(a)) continue;
    if (a.profile) {
      const scope = parseServiceIds(a.profile.serviceIds);
      let value = a.profile.value;
      if (!a.valueOverride) {
        const rules = await db.discountRule.findMany({ where: { profileId: a.profile.id } });
        const byClass = rules.filter((r) => r.classroomName === clsName);
        const anyClass = rules.filter((r) => !r.classroomName);
        const rule = byClass[byClass.length - 1] ?? anyClass[anyClass.length - 1];
        if (rule) value = rule.value;
      } else {
        value = a.valueOverride;
      }
      resolved.push({ appId: a.id, coversAll: scope.all, covers: new Set(scope.ids), type: a.profile.method, value });
    } else if (a.discount) {
      resolved.push({
        appId: a.id, coversAll: false, covers: new Set(a.serviceId ? [a.serviceId] : []),
        type: a.discount.type, value: a.valueOverride ?? a.discount.value,
      });
    }
  }

  const changes: { serviceId: string; name: string; from: number | null; to: number | null }[] = [];

  for (const svc of services) {
    /* CI3 parity: the discount base is the per-class rate when the learner's
       classroom has one (daily_fee_rates is per class), else the catalog price.
       A 50% profile on a ₵3 class rate must net ₵1.50 — not 50% of the ₵8
       catalog price, which could land ABOVE the class rate. */
    const classRate = clsId ? svc.classRates.find((r) => r.classroomId === clsId)?.priceCents ?? null : null;
    const base = classRate ?? svc.priceCents;
    // candidates: every resolved application covering this service
    let best: number | null = null;
    let bestAppId: string | null = null;
    for (const r of resolved) {
      if (!(r.coversAll || r.covers.has(svc.id))) continue;
      const price = subsidisedPrice(base, r.type, r.value);
      if (price < base && (best === null || price < best)) { best = price; bestAppId = r.appId; }
    }

    const current = await db.dailyServiceEnrollment.findUnique({ where: { serviceId_studentId: { serviceId: svc.id, studentId } } });
    const cur = current?.priceCentsOverride ?? null;
    const next = best !== null && best < base ? best : null;

    if (cur === next) continue; // nothing changed for this service
    if (current) {
      await db.dailyServiceEnrollment.update({ where: { id: current.id }, data: { priceCentsOverride: next } });
    } else if (next !== null) {
      // a discount creates the subsidised enrolment so the learner can be billed the reduced rate
      await db.dailyServiceEnrollment.create({ data: { serviceId: svc.id, studentId, priceCentsOverride: next } });
    }
    changes.push({ serviceId: svc.id, name: svc.name, from: cur, to: next });
    // iEduPro daily_fee_audit_log — rate adjustments are auditable events
    if (cur !== null || next !== null) {
      const delta = (next ?? base) - (cur ?? base); // negative = rate reduced
      await logDailyFeeAudit(db, {
        schoolId, studentId, actionType: "ADJUSTMENT", serviceCode: svc.name,
        amountCents: delta, performedByName: opts?.performedByName, performedByRole: opts?.performedByRole,
        reference: bestAppId, notes: opts?.reason ?? "discount recalculation — effective daily rate updated",
      });
    }
  }
  return { services: changes };
}

/** Apply a DAILY_SERVICE subsidy: set the learner's subsidised rate on their enrolment.
 *  Kept for compatibility — the recalc engine now owns the arithmetic. */
export async function applyDailySubsidy(serviceId: string, studentId: string, _type: string, _value: number) {
  const svc = await db.dailyService.findUnique({ where: { id: serviceId } });
  if (!svc) return;
  await recalcDailySubsidies(studentId);
}

/** Remove a DAILY_SERVICE subsidy — recalculates from remaining active applications. */
export async function removeDailySubsidy(serviceId: string, studentId: string) {
  const svc = await db.dailyService.findUnique({ where: { id: serviceId } });
  if (!svc) return;
  await recalcDailySubsidies(studentId);
}

/** Recalculate daily subsidies for every learner holding an ACTIVE application
 *  of the given profile (used when a profile's scope/value changes or it is paused). */
export async function recalcProfileApplications(profileId: string, opts?: { performedByName?: string; performedByRole?: string; reason?: string }) {
  const apps = await db.discountApplication.findMany({ where: { profileId, status: "ACTIVE" }, select: { studentId: true } });
  const seen = new Set<string>();
  for (const a of apps) {
    if (seen.has(a.studentId)) continue;
    seen.add(a.studentId);
    await recalcDailySubsidies(a.studentId, opts);
  }
  return seen.size;
}

// ── CI3 mark_attendance / fee_collection_portal discount display parity ────
//  The legacy register resolves every learner's per-service discount for the
//  day (Discount_model::get_student_discount_for_date) and shows:
//   · a green "Discount Applied" badge block  (Feeding: 50% | ₵5.00 (FREE))
//   · a 100% hero card when has_full_discount_on_all_daily_fees
//   · per-row "Discount: ₵X off" strips, rows hidden when discount ≥ rate
//  Here the effect materialises through the enrolment price override the
//  recalc engine writes; this resolver derives the DISPLAY metadata from the
//  same ACTIVE applications so the two can never disagree.

export type DailySvcDiscountMeta = {
  serviceId: string;
  name: string;
  /** class rate ?? standard rate — the price BEFORE any discount */
  listPriceCents: number;
  /** what charging actually uses (override ?? list) */
  effectiveCents: number;
  /** list − effective, clamped ≥ 0 */
  offCents: number;
  /** a priced service brought to ₵0 by the discount */
  fullDiscount: boolean;
  /** the winning discount/profile application's display name */
  sourceName: string | null;
  method: string | null; // PERCENT | FIXED
  value: number | null;
};

export type DailyBadgeMeta = {
  serviceName: string;
  name: string;
  method: string;
  value: number;
  offCents: number;
  netCents: number;
  free: boolean;
};

export type DailyDiscountSummary = {
  hasDiscount: boolean;
  /** CI3 has_full_discount_on_all_daily_fees: every enrolled priced daily
      service nets ₵0 (and the bus fare, when the learner rides, nets ₵0) */
  fullDiscountAllFees: boolean;
  badges: DailyBadgeMeta[];
  /** keyed by serviceId — only services with discount metadata attached */
  services: Record<string, DailySvcDiscountMeta>;
  transport: { fareCents: number; netFareCents: number; offCents: number; fullFareDiscount: boolean } | null;
};

type WindowApp = {
  id: string;
  studentId: string;
  valueOverride: number | null;
  validFrom: Date | null;
  validTo: Date | null;
  serviceId: string | null;
  discount: { name: string; type: string; value: number; scope: string } | null;
  profile: { id: string; name: string; category: string; method: string; value: number; serviceIds: string; rules: { classroomName: string | null; value: number }[] } | null;
};

/** Resolve the daily discount DISPLAY metadata for many learners in one go.
 *  Read-only — charging itself rides the enrolment override the engine wrote.
 *  `date` defaults to today (validity windows are checked against it). */
export async function resolveDailyDiscounts(
  schoolId: string,
  studentIds: string[],
  opts?: { date?: Date },
): Promise<Map<string, DailyDiscountSummary>> {
  const out = new Map<string, DailyDiscountSummary>();
  if (!studentIds.length) return out;
  const now = opts?.date ?? new Date();

  const [students, services, enrollRowsRaw, appsRaw, fares] = await Promise.all([
    db.student.findMany({
      where: { id: { in: studentIds } },
      select: { id: true, enrollments: { where: { status: "ACTIVE", classroom: { schoolId } }, include: { classroom: { select: { name: true, id: true } } } } },
    }),
    db.dailyService.findMany({ where: { schoolId, active: true }, include: { classRates: true } }),
    db.dailyServiceEnrollment.findMany({
      where: { studentId: { in: studentIds }, active: true, service: { schoolId, active: true } },
      select: { studentId: true, serviceId: true, priceCentsOverride: true },
    }),
    db.discountApplication.findMany({
      where: { schoolId, studentId: { in: studentIds }, status: "ACTIVE" },
      include: {
        discount: { select: { name: true, type: true, value: true, scope: true } },
        profile: { select: { id: true, name: true, category: true, method: true, value: true, serviceIds: true, rules: { select: { classroomName: true, value: true } } } },
      },
    }) as unknown as Promise<WindowApp[]>,
    Promise.all(studentIds.map((sid) => resolveTransportFare(db, sid))),
  ]);

  const svcById = new Map(services.map((s) => [s.id, s]));
  const enrollByStudent = new Map<string, Map<string, number | null>>();
  for (const r of enrollRowsRaw) {
    const m = enrollByStudent.get(r.studentId) ?? new Map<string, number | null>();
    m.set(r.serviceId, r.priceCentsOverride);
    enrollByStudent.set(r.studentId, m);
  }
  const classIdByStudent = new Map(students.map((s) => [s.id, s.enrollments[0]?.classroom?.id ?? null]));
  const appsByStudent = new Map<string, WindowApp[]>();
  for (const a of appsRaw) {
    // validity window (Discount_temporal_model lifecycle parity)
    if (a.validFrom && a.validFrom > now) continue;
    if (a.validTo && a.validTo < now) continue;
    const isDaily = a.discount ? a.discount.scope === "DAILY_SERVICE" : a.profile?.category === "DAILY_SERVICE";
    if (!isDaily) continue;
    const list = appsByStudent.get(a.studentId) ?? [];
    list.push(a);
    appsByStudent.set(a.studentId, list);
  }
  const fareByStudent = new Map(studentIds.map((sid, i) => [sid, fares[i]]));

  /** resolve one application's effective terms for this learner */
  const termsOf = (a: WindowApp, clsId: string | null): { name: string; method: string; value: number } | null => {
    if (a.profile) {
      // per-class rule wins over the profile default (same as the recalc engine)
      let value = a.valueOverride ?? a.profile.value;
      if (!a.valueOverride && a.profile.rules.length) {
        const byClass = a.profile.rules.filter((r) => r.classroomName && clsId && r.classroomName === clsId);
        const anyClass = a.profile.rules.filter((r) => !r.classroomName);
        const rule = byClass[byClass.length - 1] ?? anyClass[anyClass.length - 1];
        if (rule) value = rule.value;
      }
      return { name: a.profile.name, method: a.profile.method, value };
    }
    if (a.discount) return { name: a.discount.name, method: a.discount.type, value: a.valueOverride ?? a.discount.value };
    return null;
  };
  /** does the application cover this service? */
  const covers = (a: WindowApp, serviceId: string): boolean => {
    if (a.profile) {
      const scope = parseServiceIds(a.profile.serviceIds);
      return scope.all || scope.ids.includes(serviceId);
    }
    if (a.discount) return a.serviceId === null || a.serviceId === serviceId;
    return false;
  };

  for (const sid of studentIds) {
    const overrides = enrollByStudent.get(sid) ?? new Map<string, number | null>();
    const clsId = classIdByStudent.get(sid) ?? null;
    const apps = appsByStudent.get(sid) ?? [];
    const summary: DailyDiscountSummary = { hasDiscount: false, fullDiscountAllFees: false, badges: [], services: {}, transport: null };

    // per enrolled service metadata (transport is handled with the fare below)
    let allZero = apps.length > 0;
    let pricedCount = 0;
    for (const [serviceId, override] of overrides) {
      const svc = svcById.get(serviceId);
      if (!svc || svc.category === "TRANSPORT") continue;
      const classRate = clsId ? svc.classRates.find((r) => r.classroomId === clsId)?.priceCents ?? null : null;
      const list = classRate ?? svc.priceCents;
      if (list <= 0) continue; // free service — nothing to discount
      pricedCount++;
      const effective = override ?? list;
      const covering = apps.filter((a) => covers(a, serviceId));
      const winTerms = covering
        .map((a) => ({ app: a, t: termsOf(a, clsId) }))
        .filter((x): x is { app: WindowApp; t: { name: string; method: string; value: number } } => !!x.t)
        .sort((x, y) => subsidisedPrice(list, x.t.method, x.t.value) - subsidisedPrice(list, y.t.method, y.t.value))[0]?.t ?? null;
      const off = Math.max(0, list - effective);
      const full = effective === 0;
      if (off > 0 || (winTerms && full)) {
        summary.services[serviceId] = {
          serviceId, name: svc.name, listPriceCents: list, effectiveCents: effective, offCents: off,
          fullDiscount: full, sourceName: winTerms?.name ?? null, method: winTerms?.method ?? null, value: winTerms?.value ?? null,
        };
        summary.badges.push({ serviceName: svc.name, name: winTerms?.name ?? "Discount", method: winTerms?.method ?? "PERCENT", value: winTerms?.value ?? 0, offCents: off, netCents: effective, free: full });
        summary.hasDiscount = true;
      }
      if (!(full)) allZero = false;
    }

    // transport fare discount (CI3 charges the bus through the same resolver)
    const fare = fareByStudent.get(sid);
    if (fare) {
      const transportSvc = services.find((s) => s.category === "TRANSPORT");
      const covering = transportSvc ? apps.filter((a) => covers(a, transportSvc.id)) : [];
      const winTerms = covering
        .map((a) => ({ t: termsOf(a, clsId) }))
        .filter((x): x is { t: { name: string; method: string; value: number } } => !!x.t)
        .sort((x, y) => subsidisedPrice(fare.onewayCents, x.t.method, x.t.value) - subsidisedPrice(fare.onewayCents, y.t.method, y.t.value))[0]?.t ?? null;
      const netFare = winTerms ? subsidisedPrice(fare.onewayCents, winTerms.method, winTerms.value) : fare.onewayCents;
      if (netFare < fare.onewayCents) {
        summary.transport = { fareCents: fare.onewayCents, netFareCents: netFare, offCents: fare.onewayCents - netFare, fullFareDiscount: netFare === 0 };
        summary.badges.push({ serviceName: "Transport", name: winTerms?.name ?? "Discount", method: winTerms?.method ?? "PERCENT", value: winTerms?.value ?? 0, offCents: fare.onewayCents - netFare, netCents: netFare, free: netFare === 0 });
        summary.hasDiscount = true;
      }
      if (fare.onewayCents > 0 && netFare !== 0) allZero = false;
    } else {
      // no route assignment — the bus cannot block a full discount
    }

    if (pricedCount === 0) allZero = false; // nothing enrolled → not "all fees covered"
    summary.fullDiscountAllFees = allZero && pricedCount > 0 && summary.hasDiscount;
    // a learner whose every priced service nets ₵0 but has no bus and no apps
    // recorded (manual override) still counts — CI3 detects via the assignment,
    // we detect via the materialised rates + at least one application
    if (!summary.fullDiscountAllFees && pricedCount > 0 && apps.length > 0) {
      const allCoveredZero = Array.from(overrides.keys()).every((serviceId) => {
        const svc = svcById.get(serviceId);
        if (!svc || svc.category === "TRANSPORT") return true;
        const classRate = clsId ? svc.classRates.find((r) => r.classroomId === clsId)?.priceCents ?? null : null;
        const list = classRate ?? svc.priceCents;
        if (list <= 0) return true;
        return (overrides.get(serviceId) ?? list) === 0;
      });
      if (allCoveredZero) summary.fullDiscountAllFees = true;
    }
    out.set(sid, summary);
  }
  return out;
}

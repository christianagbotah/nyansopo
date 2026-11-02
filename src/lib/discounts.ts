import { db } from "@/lib/db";
import { logDailyFeeAudit } from "@/lib/daily-billing";

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
    db.dailyService.findMany({ where: { schoolId, active: true }, orderBy: { name: "asc" } }),
    db.discountApplication.findMany({
      where: { studentId, status: "ACTIVE" },
      include: { discount: true, profile: true },
    }) as unknown as Promise<ActiveApp[]>,
    db.enrollment.findFirst({ where: { studentId, status: "ACTIVE" }, include: { classroom: true } }),
  ]);
  const clsName = activeEnrollment?.classroom?.name ?? null;

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
    // candidates: every resolved application covering this service
    let best: number | null = null;
    let bestAppId: string | null = null;
    for (const r of resolved) {
      if (!(r.coversAll || r.covers.has(svc.id))) continue;
      const price = subsidisedPrice(svc.priceCents, r.type, r.value);
      if (price < svc.priceCents && (best === null || price < best)) { best = price; bestAppId = r.appId; }
    }

    const current = await db.dailyServiceEnrollment.findUnique({ where: { serviceId_studentId: { serviceId: svc.id, studentId } } });
    const cur = current?.priceCentsOverride ?? null;
    const next = best !== null && best < svc.priceCents ? best : null;

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
      const delta = (next ?? svc.priceCents) - (cur ?? svc.priceCents); // negative = rate reduced
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

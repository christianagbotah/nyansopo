import { db } from "@/lib/db";

/* Discount engine helpers shared by the discounts API and billing flows.
   iedupro parity: profiles (named rule-sets with per-class overrides) +
   an approval workflow (PENDING → APPROVED/ACTIVE, REJECTED, REVOKED). */

export type DiscountSource =
  | { kind: "discount"; id: string; name: string; type: string; value: number; scope: string }
  | { kind: "profile"; id: string; name: string; type: string; value: number; category: string };

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

/** Apply a DAILY_SERVICE subsidy: set the learner's subsidised rate on their enrolment. */
export async function applyDailySubsidy(serviceId: string, studentId: string, type: string, value: number) {
  const svc = await db.dailyService.findUnique({ where: { id: serviceId } });
  if (!svc) return;
  const pct = type === "PERCENT" ? value / 100 : Math.min(1, value / svc.priceCents);
  const override = Math.max(0, Math.round(svc.priceCents * (1 - pct)));
  await db.dailyServiceEnrollment.upsert({
    where: { serviceId_studentId: { serviceId, studentId } },
    update: { active: true, priceCentsOverride: override },
    create: { serviceId, studentId, priceCentsOverride: override },
  });
}

/** Remove a DAILY_SERVICE subsidy when no other active application covers it. */
export async function removeDailySubsidy(serviceId: string, studentId: string) {
  const en = await db.dailyServiceEnrollment.findUnique({ where: { serviceId_studentId: { serviceId, studentId } } });
  if (!en) return;
  const others = await db.discountApplication.count({ where: { studentId, serviceId, status: "ACTIVE" } });
  if (others === 0) await db.dailyServiceEnrollment.update({ where: { id: en.id }, data: { priceCentsOverride: null } });
}

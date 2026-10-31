import { PrismaClient, Prisma } from "@prisma/client";

// ── Daily-service billing helpers (shared by API + offline sync) ──

/** Apply a POS payment to a learner's daily ledger: oldest OWED charges first (FIFO). */
export async function settleDailyCharges(db: PrismaClient, paymentId: string, studentId: string, amountCents: number) {
  let remaining = amountCents;
  const owed = await db.dailyCharge.findMany({
    where: { studentId, status: "OWED" },
    orderBy: { date: "asc" },
  });
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
        data: { serviceId: c.serviceId, studentId, schoolId: c.schoolId, date: c.date, amountCents: c.amountCents - paidPart, status: "OWED" },
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
      const existing = await db.dailyCharge.findUnique({ where: { serviceId_studentId_date: { serviceId: svc.id, studentId: en.studentId, date } } });
      if (existing) { skipped++; continue; }
      await db.dailyCharge.create({ data: { serviceId: svc.id, studentId: en.studentId, schoolId, date, amountCents: effectivePrice(svc, en) } });
      created++;
    }
  }
  return { created, skipped };
}

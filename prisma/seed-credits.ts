/* Nyansapo — seed student credit wallets (prepaid credits from overpayment,
   CI3 parity) for the demo school Golden Excellence Academy (code GEA).
   Gives 3 learners small balances: one overpayment credit of ₵40 (of which
   ₵15 is transferred to a sibling, leaving ₵25), one manual ₵10 credit,
   and one transfer-in of ₵15 — a genuine TRANSFER_OUT/TRANSFER_IN pair.
   Idempotent — skips when StudentCredit rows already exist for the school.
   Standalone run: bun prisma/seed-credits.ts  (also chainable from seed.ts). */
import { PrismaClient } from "@prisma/client";
import { nextReceiptNo } from "../src/lib/codes";

const db = new PrismaClient();
const C = (cedis: number) => Math.round(cedis * 100);
const daysAgo = (n: number, h = 10) => {
  const d = new Date();
  d.setDate(d.getDate() - n);
  d.setHours(h, 15, 0, 0);
  return d;
};

export async function seedCredits() {
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!school) { console.log("seed-credits: GEA school not found — skipping"); return; }
  const existing = await db.studentCredit.count({ where: { schoolId: school.id } });
  if (existing > 0) { console.log(`seed-credits: ${existing} credit rows already exist — skipping`); return; }

  // three learners with active enrolments (stable order for a repeatable story)
  const enrolled = await db.enrollment.findMany({
    where: { schoolId: school.id, status: "ACTIVE" },
    include: { student: true },
    orderBy: [{ classroomId: "asc" }, { studentId: "asc" }],
    take: 3,
  });
  if (enrolled.length < 3) { console.log("seed-credits: fewer than 3 active learners — skipping"); return; }
  const [l1, l2, l3] = enrolled.map((e) => e.student);
  const bursar = "Mr. Patrick Quaye";
  const head = "Madam Grace Mensah";

  // 1) overpayment: the guardian paid ₵40 beyond the balance at the counter —
  //    a manual payment receipt created the credit (sourcePaymentId link)
  const payment = await db.payment.create({
    data: {
      receiptNo: await nextReceiptNo(),
      studentId: l1.id, schoolId: school.id, category: "INVOICE",
      amountCents: C(40), method: "CASH", paymentType: "PAYMENT",
      reference: "SEED-CREDIT-OVERPAYMENT",
      receivedByName: bursar, paidAt: daysAgo(4, 9),
    },
  });
  await db.studentCredit.create({
    data: {
      schoolId: school.id, studentId: l1.id, kind: "CREDIT",
      amountCents: C(40), balanceAfter: C(40), sourcePaymentId: payment.id,
      note: `Overpayment at the counter — carried forward to future bills (${payment.receiptNo})`,
      byName: bursar, at: daysAgo(4, 9),
    },
  });
  // ₵15 of it moves to a sibling — the TRANSFER_OUT half of the pair
  await db.studentCredit.create({
    data: {
      schoolId: school.id, studentId: l1.id, kind: "TRANSFER_OUT",
      amountCents: -C(15), balanceAfter: C(25),
      note: `Transferred to ${l3.firstName} (sibling) — family consolidation`,
      byName: bursar, at: daysAgo(4, 10),
    },
  });

  // 2) manual credit for a second learner (PTA welfare support)
  await db.studentCredit.create({
    data: {
      schoolId: school.id, studentId: l2.id, kind: "CREDIT",
      amountCents: C(10), balanceAfter: C(10),
      note: "Manual credit — PTA welfare support for the family",
      byName: head, at: daysAgo(2, 11),
    },
  });

  // 3) the TRANSFER_IN half of the pair lands on the sibling
  await db.studentCredit.create({
    data: {
      schoolId: school.id, studentId: l3.id, kind: "TRANSFER_IN",
      amountCents: C(15), balanceAfter: C(15),
      note: `Transfer from ${l1.firstName} (sibling) — family consolidation`,
      byName: bursar, at: daysAgo(4, 10),
    },
  });

  console.log(`seed-credits: 3 learners credited at ${school.name} — ${l1.firstName} ₵25 (overpayment net of transfer), ${l2.firstName} ₵10 (manual), ${l3.firstName} ₵15 (transfer-in)`);
}

// standalone run: DATABASE_URL=postgresql://… bun prisma/seed-credits.ts
if (process.argv[1]?.endsWith("seed-credits.ts")) {
  seedCredits()
    .catch((e) => { console.error(e); process.exit(1); })
    .finally(() => db.$disconnect());
}

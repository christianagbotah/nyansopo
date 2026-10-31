/* Seed iedupro-parity starter data for the new modules:
   · Discount profiles (with per-class rules) + a couple of pending applications
   · SMS automation rules (payment receipt / absence / bill reminder)
   · Pension providers (licensed Ghana Tier-2/3 managers)
   Idempotent: skips rows that already exist. Run on PostgreSQL. */
import { PrismaClient } from "@prisma/client";
const db = new PrismaClient();

const schools = await db.school.findMany({ select: { id: true, code: true, name: true } });
console.log(`schools: ${schools.map((s) => s.code).join(", ")}`);

for (const s of schools) {
  // ── discount profiles (a representative set for every school) ──
  const profileSpecs = [
    { name: "Staff ward tuition waiver", category: "INVOICE", method: "PERCENT", value: 100, description: "Teaching & non-teaching staff children — verified by HR register" },
    { name: "PTA executive tuition discount", category: "INVOICE", method: "PERCENT", value: 50, description: "Serving PTA executives, renewed each academic year" },
    { name: "Sibling discount (3+ children)", category: "INVOICE", method: "PERCENT", value: 10, description: "Third and subsequent siblings enrolled simultaneously" },
    { name: "Needy-family support (feeding)", category: "DAILY_SERVICE", method: "PERCENT", value: 100, description: "Full feeding subsidy — GNACOPS district welfare referrals" },
  ];
  for (const spec of profileSpecs) {
    const existing = await db.discountProfile.findFirst({ where: { schoolId: s.id, name: spec.name } });
    if (existing) continue;
    const p = await db.discountProfile.create({ data: { ...spec, schoolId: s.id } });
    // per-class override on the PTA profile: kindergarten classes get a deeper cut
    if (spec.name.startsWith("PTA")) {
      const kg = await db.classroom.findMany({ where: { schoolId: s.id, name: { contains: "KG" } }, take: 2 });
      for (const c of kg) {
        await db.discountRule.create({ data: { profileId: p.id, classroomName: c.name, value: 60 } });
      }
    }
  }

  // ── pension providers (Ghana licensed managers) ──
  const providerSpecs = [
    { name: "Petra Trust", code: `PTRA-${s.code}`, contactPhone: "+233 30 274 1440" },
    { name: "Enterprise Trustees", code: `ENTR-${s.code}`, contactPhone: "+233 30 225 6789" },
    { name: "Sigma Pensions", code: `SGMA-${s.code}`, contactPhone: "+233 30 740 2000" },
  ];
  for (const spec of providerSpecs) {
    const existing = await db.pensionProvider.findFirst({ where: { schoolId: s.id, name: spec.name } });
    if (existing) continue;
    await db.pensionProvider.create({ data: { ...spec, schoolId: s.id } });
  }

  // ── SMS automation rules ──
  const automationSpecs = [
    {
      name: "Payment receipt", trigger: "PAYMENT_RECEIVED", recipients: "GUARDIANS", offsetDays: 0,
      body: "We have received {amount} for {student} (receipt {receipt}). Balance: {balance}. Thank you — {school}.",
    },
    {
      name: "Absence alert", trigger: "ABSENCE_MARKED", recipients: "GUARDIANS", offsetDays: 0,
      body: "{student} was marked ABSENT today ({date}). Please contact the school if this is unexpected. — {school}",
    },
    {
      name: "Fee reminder — 3 days ahead", trigger: "BILL_REMINDER", recipients: "GUARDIANS", offsetDays: 3,
      body: "Friendly reminder: {student}'s term balance is {balance} for {term}. Kindly settle at the office or via MoMo. Thank you — {school}.",
    },
  ];
  for (const spec of automationSpecs) {
    const existing = await db.smsAutomation.findFirst({ where: { schoolId: s.id, name: spec.name } });
    if (existing) continue;
    await db.smsAutomation.create({ data: { ...spec, schoolId: s.id } });
  }
}

// ── demo approval-queue items for the flagship school (GEA) ──
const gea = schools.find((s) => s.code === "GEA");
if (gea) {
  const student = await db.student.findFirst({ where: { enrollments: { some: { schoolId: gea.id, status: "ACTIVE" } } }, orderBy: { firstName: "asc" } });
  const profile = await db.discountProfile.findFirst({ where: { schoolId: gea.id, name: { contains: "Needy" } } });
  const tuition = await db.discountProfile.findFirst({ where: { schoolId: gea.id, name: { contains: "Sibling" } } });
  if (student && profile && tuition) {
    const already = await db.discountApplication.count({ where: { schoolId: gea.id, status: "PENDING" } });
    if (already === 0) {
      await db.discountApplication.create({ data: { profileId: profile.id, studentId: student.id, schoolId: gea.id, reason: "District welfare referral — verify with zonal rep", appliedByName: "Madam Grace Mensah", status: "PENDING" } });
      const second = await db.student.findFirst({ where: { enrollments: { some: { schoolId: gea.id, status: "ACTIVE" } }, id: { not: student.id } }, orderBy: { firstName: "desc" } });
      if (second) await db.discountApplication.create({ data: { profileId: tuition.id, studentId: second.id, schoolId: gea.id, reason: "Third sibling — family enrolled since 2022", appliedByName: "Mr. Patrick Quaye", status: "PENDING" } });
      console.log("seeded 2 pending discount approvals for GEA");
    }
  }
}

const counts = {
  profiles: await db.discountProfile.count(),
  rules: await db.discountRule.count(),
  providers: await db.pensionProvider.count(),
  automations: await db.smsAutomation.count(),
  pending: await db.discountApplication.count({ where: { status: "PENDING" } }),
};
console.log("seeded:", JSON.stringify(counts));
await db.$disconnect();

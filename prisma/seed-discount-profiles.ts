/* Seed example daily-fee discount profiles for every school with daily services.
   Idempotent — skips names that already exist. Run: bun prisma/seed-discount-profiles.ts */
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

async function main() {
  const schools = await db.school.findMany({ include: { _count: { select: { dailyServices: true } } } });
  let created = 0, skipped = 0;
  for (const school of schools) {
    if (!school._count.dailyServices) continue;
    const services = await db.dailyService.findMany({ where: { schoolId: school.id, active: true }, orderBy: { name: "asc" } });
    const meal = services.find((s) => s.category === "MEAL") ?? services[0];
    const klass = services.find((s) => s.category === "CLASS");
    const drink = services.find((s) => s.category === "DRINK");

    // a curated starter set per school (user's scenario: lunch-only part/full, classes-only, all daily fees)
    const wanted = [
      { name: "Full Lunch Support (needy families)", category: "DAILY_SERVICE", method: "PERCENT", value: 100, serviceIds: meal.id, description: "Learner eats free — verified hardship cases approved by headteacher." },
      { name: "Half-Price Lunch (staff children)", category: "DAILY_SERVICE", method: "PERCENT", value: 50, serviceIds: meal.id, description: "50% off lunch only for staff children." },
      { name: "Half All Daily Fees (siblings)", category: "DAILY_SERVICE", method: "PERCENT", value: 50, serviceIds: "*", description: "Second+ sibling in school — 50% off every daily fee." },
      ...(klass ? [{ name: "Free Extra Classes (scholarship)", category: "DAILY_SERVICE", method: "PERCENT", value: 100, serviceIds: klass.id, description: "Remedial classes waived for academic scholars." }] : []),
      ...(drink ? [{ name: "Free Sachet Water", category: "DAILY_SERVICE", method: "FIXED", value: drink.priceCents, serviceIds: drink.id, description: "Water covered fully — flat amount off." }] : []),
      { name: "Tuition Support (PTA executives)", category: "INVOICE", method: "PERCENT", value: 10, serviceIds: "*", description: "10% off termly invoices for PTA executive parents." },
    ];
    for (const w of wanted) {
      const exists = await db.discountProfile.findFirst({ where: { schoolId: school.id, name: w.name } });
      if (exists) { skipped++; continue; }
      await db.discountProfile.create({
        data: { schoolId: school.id, name: w.name, category: w.category, method: w.method, value: w.value, feeItemName: w.category === "INVOICE" ? "Tuition" : "*", serviceIds: w.serviceIds, description: w.description },
      });
      created++;
    }
  }
  console.log(`discount profiles: ${created} created, ${skipped} already present`);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

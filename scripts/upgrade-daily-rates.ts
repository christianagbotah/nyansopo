/** One-time upgrade for existing installs → wave-4 daily-fee model.
 *
 *  (a) Transport routes gain a default one-way fare (farePerTripCents) and
 *      structured stop records with per-stop fares — before this, fares fell
 *      back to 0 for stops without records.
 *  (b) `--demo` additionally seeds a handful of per-item prepaid balances so
 *      the new wallet cards have data to show (fresh installs get these from
 *      the seed chain instead).
 *
 *  Idempotent: routes that already carry stop records / a per-trip fare are
 *  left untouched. Usage: bun scripts/upgrade-daily-rates.ts [--demo]
 */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const demo = process.argv.includes("--demo");
const C = (cedis: number) => Math.round(cedis * 100);

// graduated per-stop fares per route name (seed table); fallback derives from
// the termly fare (≈ one-way ≈ term fare ÷ 55 school-days, min GH₵2)
const FARE_TABLE: Record<string, { perTrip: number; stops: Record<string, number> }> = {
  "Adenta Loop": { perTrip: 8, stops: { "Adenta Market": 6, "Community 25": 8, "Frafraha Junction": 9, Ogbojo: 10 } },
  "Madina–Atomic": { perTrip: 7, stops: { "Madina Market": 5, "Zongo Junction": 6, "Atomic Roundabout": 7, "Legon Hall": 9 } },
  "Ashaley Botwe Express": { perTrip: 6, stops: { "Ashaley Botwe": 4, "Sakora Junction": 5, Adjiriganor: 6 } },
  "Adenta North Loop": { perTrip: 7, stops: { Kweiman: 5, "Adenta SSNIT": 6, Amrahia: 8 } },
  "Oyarifa Line": { perTrip: 8, stops: { "Oyarifa Station": 7, Ahyiresu: 8, Kweiman: 10 } },
  "Cantonments–Labone": { perTrip: 18, stops: { "Cantonments City": 15, "Labone Secondary": 17, Kpeshie: 20 } },
};

async function main() {
  const routes = await db.transportRoute.findMany({ include: { stopRecords: true } });
  let upgraded = 0, skipped = 0;
  for (const r of routes) {
    if (r.stopRecords.length && r.farePerTripCents > 0) { skipped++; continue; }
    const table = FARE_TABLE[r.name];
    // when the demo fare table defines this route, align the route default to it
    // (stops graduated around it); otherwise keep any existing per-trip fare
    // and only fill what's missing.
    const perTrip = table?.perTrip ?? (r.farePerTripCents > 0 ? r.farePerTripCents / 100 : Math.max(2, Math.round(r.farePerTermCents / 100 / 55)));
    if (table || r.farePerTripCents <= 0) await db.transportRoute.update({ where: { id: r.id }, data: { farePerTripCents: C(perTrip) } });
    if (!r.stopRecords.length) {
      const names = r.stops.split(",").map((s) => s.trim()).filter(Boolean);
      for (const [i, name] of names.entries()) {
        const fare = table?.stops[name] ?? Math.max(1, perTrip - 1 + i); // gentle graduation from the route default
        await db.transportStop.create({ data: { routeId: r.id, name, fareCents: C(fare), sortOrder: i } });
      }
    }
    upgraded++;
  }
  console.log(`routes: ${upgraded} upgraded (one-way fare + per-stop records), ${skipped} already current`);

  if (demo) {
    // a handful of per-item balances per school (mirrors seed-daily-ext)
    const schools = await db.school.findMany();
    let items = 0;
    for (const school of schools) {
      const svcRows = await db.dailyService.findMany({ where: { schoolId: school.id, active: true } });
      const meal = svcRows.find((s) => s.category === "MEAL");
      const drink = svcRows.find((s) => s.category === "DRINK");
      if (!meal && !drink) continue;
      const students = await db.enrollment.findMany({ where: { schoolId: school.id, status: "ACTIVE" }, take: 40, orderBy: { studentId: "asc" } });
      for (const [i, e] of students.entries()) {
        const svc = i % 7 === 0 ? drink : i % 4 === 0 ? meal : null;
        if (!svc) continue;
        const existing = await db.serviceBalance.findUnique({ where: { studentId_serviceId: { studentId: e.studentId, serviceId: svc.id } } });
        if (existing) continue;
        const amt = C(5 + ((i * 13) % 25));
        await db.serviceBalance.create({ data: { schoolId: school.id, studentId: e.studentId, serviceId: svc.id, prepaidCents: amt } });
        await db.walletTxn.create({ data: { schoolId: school.id, studentId: e.studentId, wallet: "ITEM", kind: "TOPUP", amountCents: amt, balanceAfter: amt, note: i % 2 ? "Item credit for lunch" : "Water card top-up", byName: "Upgrade script", serviceId: svc.id } });
        items++;
      }
    }
    console.log(`demo: ${items} per-item balances created`);
  }
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

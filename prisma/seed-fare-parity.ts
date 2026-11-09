/* Fare-parity seed — iEduPro CI3 one-way route fare model.
   1. every route gets a per-trip (one-way) fare — the source of truth for the
      daily transport charge: morning OR afternoon = 1×, both ways = 2×
   2. existing transport daily-charges are recomputed to that model (legacy rows
      had single-direction fares HALVED from a both-ways base)
   3. per-classroom rate overrides (iedupro daily_fee_rates) for the demo schools
   Idempotent — safe to re-run. Run: bun prisma/seed-fare-parity.ts */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const C = (cedis: number) => Math.round(cedis * 100);

async function main() {
  const schools = await db.school.findMany({ select: { id: true, code: true } });
  console.log(`🚌 fare-parity seed for ${schools.length} schools`);

  // ── 1. one-way fares on every route (GHC values; ×2 = both ways) ──
  const FARES: Record<string, number> = {
    "Adenta Loop": C(3.5),
    "Madina–Atomic": C(4.0),
    "Ashaley Botwe Express": C(3.5),
    "Adenta North Loop": C(8.0),      // user's example: 8 one-way → 16 both ways
    "Oyarifa Line": C(6.5),
    "Cantonments–Labone": C(12.0),
  };
  let fixed = 0;
  const routes = await db.transportRoute.findMany({ include: { assignments: { where: { active: true } } } });
  for (const r of routes) {
    const target = FARES[r.name] ?? (r.farePerTripCents > 0 ? r.farePerTripCents : C(4));
    if (r.farePerTripCents !== target) {
      await db.transportRoute.update({ where: { id: r.id }, data: { farePerTripCents: target } });
      fixed++;
    }
  }
  console.log(`✅ one-way fares set on ${routes.length} routes (${fixed} corrected)`);

  // ── 2. recompute legacy transport daily-charges to the one-way model ──
  const tRoutes = await db.transportRoute.findMany();
  const routeById = new Map(tRoutes.map((r) => [r.id, r]));
  const assignments = await db.transportAssignment.findMany({ where: { active: true } });
  const onewayByStudent = new Map<string, { oneway: number; route: string }>();
  for (const a of assignments) {
    if (!onewayByStudent.has(a.studentId)) {
      const r = routeById.get(a.routeId);
      onewayByStudent.set(a.studentId, { oneway: r?.farePerTripCents ?? 0, route: r?.name ?? "" });
    }
  }
  const tCharges = await db.dailyCharge.findMany({ where: { service: { category: "TRANSPORT" } }, include: { service: true } });
  let recomputed = 0, waived = 0;
  for (const c of tCharges) {
    const info = onewayByStudent.get(c.studentId);
    if (!info) continue;
    let amount: number;
    switch (c.usage ?? "BOTH") {
      case "MORNING": case "AFTERNOON": amount = info.oneway; break;          // 1× one-way
      case "BOTH": case "BOARDED": amount = info.oneway * 2; break;          // 2× one-way
      case "NOT_USING": amount = 0; waived++; break;                          // not riding — ₵0
      default: amount = info.oneway * 2;
    }
    if (c.amountCents !== amount) {
      await db.dailyCharge.update({ where: { id: c.id }, data: { amountCents: amount } });
      recomputed++;
    }
  }
  console.log(`✅ ${tCharges.length} transport charges checked · ${recomputed} recomputed to one-way model (${waived} waived at ₵0)`);

  // ── 3. transport service price = median one-way (nominal standard rate) ──
  const median = Object.values(FARES).sort((a, b) => a - b)[Math.floor(Object.keys(FARES).length / 2)] ?? C(4);
  const tSvcs = await db.dailyService.findMany({ where: { category: "TRANSPORT" } });
  for (const s of tSvcs) {
    if (s.priceCents !== median) await db.dailyService.update({ where: { id: s.id }, data: { priceCents: median } });
  }
  console.log(`✅ transport service standard rate = ₵${(median / 100).toFixed(2)} one-way`);

  // ── 4. per-classroom rate overrides (iedupro daily_fee_rates parity) ──
  //      demo: GEA — KG feeding cheaper, JHS feeding higher; water class rates vary
  const byCode = new Map(schools.map((s) => [s.code, s.id]));
  const gea = byCode.get("GEA");
  let rates = 0;
  if (gea) {
    const classes = await db.classroom.findMany({ where: { schoolId: gea }, orderBy: { name: "asc" } });
    const meals = await db.dailyService.findMany({ where: { schoolId: gea, category: { in: ["MEAL", "DRINK"] } } });
    const lunch = meals.find((m) => /lunch|feeding/i.test(m.name));
    const water = meals.find((m) => /water/i.test(m.name));
    for (const cls of classes) {
      const isKG = /KG|Creche|Nursery/i.test(cls.name);
      const isJHS = /JHS|B[789]/i.test(cls.name);
      if (lunch) {
        // standard ₵4 feeding: KG ₵3, JHS ₵5 (class-level rate matrix)
        const price = isKG ? C(3) : isJHS ? C(5) : null;
        if (price) { await db.dailyServiceRate.upsert({ where: { serviceId_classroomId: { serviceId: lunch.id, classroomId: cls.id } }, update: { priceCents: price }, create: { serviceId: lunch.id, classroomId: cls.id, priceCents: price } }); rates++; }
      }
      if (water && /Basic [456]|JHS/i.test(cls.name)) {
        // senior classes on the weekly water service pay a higher class rate
        const price = C(6);
        await db.dailyServiceRate.upsert({ where: { serviceId_classroomId: { serviceId: water.id, classroomId: cls.id } }, update: { priceCents: price }, create: { serviceId: water.id, classroomId: cls.id, priceCents: price } });
        rates++;
      }
    }
  }
  console.log(`✅ ${rates} per-class rate overrides seeded (GEA rate matrix)`);

  console.log("🎉 fare-parity seed done.");
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

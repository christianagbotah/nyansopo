/* Incremental seed for the daily-fees deep-parity layer (iedupro §1):
   transport-fare daily service, learner wallets + top-ups, transport-usage
   history, collection-mode settings defaults. Idempotent — safe to re-run.
   Run: bun prisma/seed-daily-ext.ts */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const C = (cedis: number) => Math.round(cedis * 100);
const pick = <T,>(arr: T[], i: number) => arr[i % arr.length];

async function ensureTransportService(schoolId: string) {
  const existing = await db.dailyService.findFirst({ where: { schoolId, category: "TRANSPORT" } });
  if (existing) return existing;
  return db.dailyService.create({
    data: { schoolId, name: "Transport Fare (bus)", category: "TRANSPORT", priceCents: C(4), chargeMode: "DAILY", weekdays: "1,2,3,4,5" },
  });
}

async function main() {
  const schools = await db.school.findMany({ select: { id: true, code: true, name: true } });
  const byCode: Record<string, string> = {};
  schools.forEach((s) => (byCode[s.code] = s.id));
  console.log(`🎓 daily-ext seed for ${schools.length} schools`);

  // ── 1. collection policy defaults (do not override existing choices) ──
  for (const s of schools) {
    for (const [key, value] of [
      ["DAILY_COLLECTION_MODE", "HYBRID"],
      ["DAILY_TEACHER_PERMS", JSON.stringify({ mode: "ALL", classroomIds: [], serviceIds: [] })],
    ] as [string, string][]) {
      await db.opsSetting.upsert({ where: { schoolId_key: { schoolId: s.id, key } }, update: {}, create: { schoolId: s.id, key, value } });
    }
  }
  console.log("✅ collection-mode defaults (HYBRID · teachers: all)");

  // ── 2. transport-fare service + enrolment from transport assignments ──
  for (const s of schools) {
    const assignments = await db.transportAssignment.findMany({ where: { active: true, route: { schoolId: s.id } }, include: { route: true } });
    if (!assignments.length) continue;
    const svc = await ensureTransportService(s.id);
    let enrolled = 0;
    for (const a of assignments) {
      await db.dailyServiceEnrollment.upsert({
        where: { serviceId_studentId: { serviceId: svc.id, studentId: a.studentId } },
        update: {},
        create: { serviceId: svc.id, studentId: a.studentId, priceCentsOverride: a.fareOverrideCents ?? null },
      });
      enrolled++;
    }
    console.log(`✅ ${s.code}: transport-fare service · ${enrolled} riders enrolled`);

    // ── 3. transport-usage history (last 6 school days) if none exists ──
    const existingUsage = await db.dailyCharge.count({ where: { schoolId: s.id, service: { category: "TRANSPORT" } } });
    if (existingUsage === 0) {
      // dedupe by student (a learner may ride multiple routes)
      const seen = new Set<string>();
      const riders = assignments.filter((a) => (seen.has(a.studentId) ? false : (seen.add(a.studentId), true))).slice(0, 14);
      const usages = ["BOTH", "BOTH", "MORNING", "AFTERNOON", "BOTH", "NOT_USING", "BOARDED"];
      const days: Date[] = [];
      const cursor = new Date(); cursor.setHours(0, 0, 0, 0);
      for (let d = 0; days.length < 6 && d < 14; d++) {
        const day = new Date(cursor.getTime() - d * 864e5);
        if (day.getDay() >= 1 && day.getDay() <= 5) days.push(day);
      }
      let made = 0;
      for (const [di, day] of days.entries()) {
        for (const [ri, r] of riders.entries()) {
          const usage = usages[(di * 3 + ri) % usages.length];
          // iEduPro one-way model: the route fare captures a single directional
          // charge — MORNING/AFTERNOON 1×, BOTH/BOARDED 2×, NOT_USING GH₵0
          const oneway = r.route.farePerTripCents || C(4);
          let amount = oneway * 2; let status = "OWED";
          if (usage === "NOT_USING") { status = "WAIVED"; amount = 0; }
          else if (usage === "MORNING" || usage === "AFTERNOON") amount = oneway;
          if (di < 4 && ri % 3 === 0) status = "PAID"; // some settled history
          await db.dailyCharge.create({ data: { serviceId: svc.id, studentId: r.studentId, schoolId: s.id, date: day, amountCents: amount, status, usage } });
          made++;
        }
      }
      console.log(`  + ${s.code}: ${made} transport-usage charges (${riders.length} riders × ${days.length} days)`);
    }
  }

  // ── 4. wallets + top-up audit trail (GEA & GKA demo schools) ──
  const demoCodes = ["GEA", "GKA"].filter((c) => byCode[c]);
  for (const code of demoCodes) {
    const schoolId = byCode[code];
    const students = await db.enrollment.findMany({
      where: { schoolId, status: "ACTIVE" },
      include: { student: true, classroom: true },
      orderBy: { student: { lastName: "asc" } },
      take: 40,
    });
    if (!students.length) continue;
    const existingWallets = await db.studentWallet.count({ where: { schoolId } });
    if (existingWallets > 0) { console.log(`⏭️  ${code}: wallets already seeded`); continue; }

    const prepaidNames = ["Monday top-up from guardian", "Start-of-term advance", "Lunch credit — mum", "PTA welfare credit"];
    const busNames = ["Bus pass top-up — term 2", "Transport advance — dad", "Route credit (Adenta Loop)"];
    let topups = 0, bus = 0;
    for (let i = 0; i < students.length; i++) {
      const st = students[i];
      // every 3rd learner gets a prepaid wallet, every 5th a transport wallet
      if (i % 3 === 0) {
        const amt = C(10 + ((i * 7) % 30)); // GH₵10–40 spread
        await db.studentWallet.upsert({
          where: { studentId: st.studentId },
          update: { prepaidCents: { increment: amt } },
          create: { schoolId, studentId: st.studentId, prepaidCents: amt },
        });
        await db.walletTxn.create({ data: { schoolId, studentId: st.studentId, wallet: "PREPAID", kind: "TOPUP", amountCents: amt, balanceAfter: amt, note: pick(prepaidNames, i), byName: "Madam Grace Mensah" } });
        topups++;
      }
      if (i % 5 === 0) {
        const amt = C(20 + ((i * 11) % 40)); // GH₵20–60 spread
        const w = await db.studentWallet.upsert({
          where: { studentId: st.studentId },
          update: { transportCents: { increment: amt } },
          create: { schoolId, studentId: st.studentId, transportCents: amt },
        });
        await db.walletTxn.create({ data: { schoolId, studentId: st.studentId, wallet: "TRANSPORT", kind: "TOPUP", amountCents: amt, balanceAfter: w.transportCents, note: pick(busNames, i), byName: "Madam Grace Mensah" } });
        bus++;
      }
    }
    // ── per-item prepaid balances (iEduPro daily_fee_wallet parity): prepaid
    //    credit tied to ONE fee item — its day charges auto-deduct from it ──
    const svcRows = await db.dailyService.findMany({ where: { schoolId, active: true }, orderBy: { name: "asc" } });
    const mealSvc = svcRows.find((s) => s.category === "MEAL");
    const drinkSvc = svcRows.find((s) => s.category === "DRINK");
    const itemNames = ["Weekly lunch advance — grandma", "Item credit for lunch", "Water card top-up", "Feeding balance carried from last term"];
    let items = 0;
    for (let i = 0; i < students.length; i++) {
      const st = students[i];
      const svc = i % 7 === 0 ? drinkSvc : i % 4 === 0 ? mealSvc : null;
      if (!svc) continue;
      const amt = C(5 + ((i * 13) % 25)); // GH₵5–30 spread
      await db.serviceBalance.upsert({
        where: { studentId_serviceId: { studentId: st.studentId, serviceId: svc.id } },
        update: { prepaidCents: { increment: amt } },
        create: { schoolId, studentId: st.studentId, serviceId: svc.id, prepaidCents: amt },
      });
      await db.walletTxn.create({ data: { schoolId, studentId: st.studentId, wallet: "ITEM", kind: "TOPUP", amountCents: amt, balanceAfter: amt, note: pick(itemNames, i), byName: "Madam Grace Mensah", serviceId: svc.id } });
      items++;
    }
    console.log(`✅ ${code}: ${topups} prepaid wallets · ${bus} transport wallets · ${items} per-item balances`);
  }

  console.log("🎉 Daily-ext seed done.");
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

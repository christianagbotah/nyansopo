/* One-off residue cleanup after a crashed smoke-daily-sync run (fixed
   clientIds syncsmk-1..N leave SYNC payments, test services, transport
   fixtures and audits when the suite exits before its own cleanup).
   Mirrors the suite's own cleanup section. Run with DATABASE_URL set. */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

async function main() {
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!school) { console.log("no GEA"); return; }
  const schoolId = school.id;

  // payments minted by the crashed run
  const pays = await db.payment.findMany({ where: { reference: { startsWith: "SYNC:syncsmk-" } }, select: { id: true } });
  let n = 0;
  for (const p of pays) {
    await db.dailyCharge.updateMany({ where: { paymentId: p.id }, data: { paymentId: null, status: "OWED" } }).catch(() => null);
    await db.payment.delete({ where: { id: p.id } }).catch(() => null);
    n++;
  }
  const YEAR = new Date().getFullYear();
  // self-heal: park the counter just past the highest existing receipt so the
  // next mint can never collide (crashed runs + cleanups drift it otherwise)
  const [maxRow] = await db.$queryRaw<{ max: number | null }[]>`SELECT COALESCE(max((regexp_match("receiptNo", '[0-9]+$'))[1])::int, 0) AS max FROM "Payment" WHERE "receiptNo" LIKE ${`RCP-${YEAR}-%`}`;
  if (maxRow?.max) {
    await db.codeCounter.upsert({
      where: { scope: `RCP:${YEAR}` },
      update: { next: maxRow.max + 1 },
      create: { scope: `RCP:${YEAR}`, next: maxRow.max + 1 },
    });
  }

  // test services + their ledger rows
  const svcs = await db.dailyService.findMany({ where: { schoolId, name: { startsWith: "SYNCSMK " } }, select: { id: true } });
  const svcIds = svcs.map((s) => s.id);
  if (svcIds.length) {
    await db.dailyCharge.deleteMany({ where: { serviceId: { in: svcIds } } }).catch(() => null);
    await db.serviceBalance.deleteMany({ where: { serviceId: { in: svcIds } } }).catch(() => null);
    await db.walletTxn.deleteMany({ where: { serviceId: { in: svcIds } } }).catch(() => null);
    await db.dailyService.deleteMany({ where: { id: { in: svcIds } } }).catch(() => null);
  }

  // audits + SMS from the crashed run (fixture student = first GEA enrolment, same pick as the suite)
  const en = await db.enrollment.findFirst({ where: { schoolId, status: "ACTIVE" }, orderBy: { studentId: "asc" } });
  if (en) {
    await db.dailyFeeAudit.deleteMany({ where: { studentId: en.studentId, note: { contains: "SYNCSMK" } } }).catch(() => null);
  }

  // transport fixtures (route named with the same tag)
  const routes = await db.transportRoute.findMany({ where: { schoolId, name: { startsWith: "SYNCSMK" } }, select: { id: true } });
  for (const r of routes) {
    await db.transportBoarding.deleteMany({ where: { routeId: r.id } }).catch(() => null);
    await db.transportAssignment.deleteMany({ where: { routeId: r.id } }).catch(() => null);
    await db.transportStop.deleteMany({ where: { routeId: r.id } }).catch(() => null);
    await db.transportRoute.delete({ where: { id: r.id } }).catch(() => null);
  }

  console.log(`cleaned: ${n} SYNC payments, ${svcIds.length} services, ${routes.length} routes`);
  console.log("remaining sync payments:", await db.payment.count({ where: { reference: { startsWith: "SYNC:syncsmk-" } } }));
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

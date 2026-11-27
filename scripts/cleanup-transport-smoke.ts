/* eslint-disable @typescript-eslint/no-require-imports */
// ── wave 75 cleanup: remove everything the transport smoke created —
//    the billed invoice line (+ total restore), the collected payment,
//    the boarding + synced daily charge, the trip, the assignment, the
//    route (+ its stops) and the fleet rows. The route/vehicle/driver
//    carry the TAG; the payment and charge are targeted by id/student. ──
const { PrismaClient } = require("@prisma/client");
const db = new PrismaClient();

(async () => {
  const tag = process.argv[2];
  const paymentId = process.argv[3];
  const studentId = process.argv[4];
  if (!tag) { console.error("usage: cleanup-transport-smoke.ts <tag> [paymentId] [studentId]"); process.exit(1); }

  // 1 — revert the bill-term invoice line (same delta math bill-term used)
  const items = await db.invoiceItem.findMany({ where: { name: `Transportation — ${tag} Madina Loop` }, include: { invoice: true } });
  for (const item of items) {
    const inv = item.invoice;
    await db.invoiceItem.delete({ where: { id: item.id } });
    const total = inv.totalCents - item.amountCents;
    const status = inv.paidCents >= total - inv.discountCents ? "PAID" : inv.paidCents > 0 ? "PARTIAL" : "UNPAID";
    await db.invoice.update({ where: { id: inv.id }, data: { totalCents: total, status } });
  }

  // 2 — the counter payment from collect-fare
  let payments = 0;
  if (paymentId) {
    const del = await db.payment.deleteMany({ where: { id: paymentId, category: "TRANSPORT" } });
    payments = del.count;
  }

  // 3 — the route (boardings → assignments → trips → stops → route)
  const routes = await db.transportRoute.findMany({ where: { name: { contains: tag } }, include: { assignments: true } });
  let assignments = 0;
  for (const r of routes) {
    for (const a of r.assignments) {
      await db.transportBoarding.deleteMany({ where: { assignmentId: a.id } });
      await db.transportAssignment.delete({ where: { id: a.id } });
      assignments++;
    }
    await db.transportTrip.deleteMany({ where: { routeId: r.id } });
    await db.transportStop.deleteMany({ where: { routeId: r.id } });
    await db.transportRoute.delete({ where: { id: r.id } });
  }

  // 4 — the boarding's synced daily charge (this student, the TRANSPORT service, today)
  let charges = 0;
  if (studentId) {
    const svc = await db.dailyService.findFirst({ where: { category: "TRANSPORT" } });
    if (svc) {
      const day = new Date(new Date().toISOString().slice(0, 10));
      const del = await db.dailyCharge.deleteMany({ where: { serviceId: svc.id, studentId, date: day } });
      charges = del.count;
    }
  }

  // 5 — fleet rows (regNumber / name carry the tag)
  const vehicles = await db.vehicle.deleteMany({ where: { regNumber: { contains: tag } } });
  const drivers = await db.driverRecord.deleteMany({ where: { name: { contains: tag } } });

  console.log(`cleaned: ${items.length} invoice line(s), ${payments} payment(s), ${routes.length} route(s), ${assignments} assignment(s), ${charges} charge(s), ${vehicles.count} vehicle(s), ${drivers.count} driver(s)`);
  process.exit(0);
})().catch((e) => { console.error(e.message); process.exit(1); });

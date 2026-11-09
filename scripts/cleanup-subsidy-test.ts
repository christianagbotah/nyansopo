import { PrismaClient } from "@prisma/client";
const db = new PrismaClient();
const app = await db.discountApplication.findFirst({ where: { reason: "subsidy test" } });
if (app) {
  await db.discountApplication.delete({ where: { id: app.id } });
  if (app.serviceId) {
    await db.dailyServiceEnrollment.updateMany({ where: { studentId: app.studentId, serviceId: app.serviceId }, data: { priceCentsOverride: null } });
  }
  console.log("cleaned", app.id);
}
await db.$disconnect();

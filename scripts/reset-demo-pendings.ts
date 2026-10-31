import { PrismaClient } from "@prisma/client";
const db = new PrismaClient();
const gea = await db.school.findFirst({ where: { code: "GEA" } });
if (!gea) process.exit(0);
// the two test-decided ones → back to PENDING, with a service linked for the feeding profile
const svc = await db.dailyService.findFirst({ where: { schoolId: gea.id }, orderBy: { name: "asc" } });
const decided = await db.discountApplication.findMany({ where: { schoolId: gea.id, status: { in: ["ACTIVE", "REJECTED"] }, decidedAt: { not: null } }, orderBy: { decidedAt: "desc" }, take: 2 });
for (const a of decided) {
  await db.discountApplication.update({
    where: { id: a.id },
    data: { status: "PENDING", approvedByName: null, decidedAt: null, ...(a.serviceId === null && svc ? { serviceId: svc.id } : {}) },
  });
  console.log("reset", a.id);
}
console.log("pending now:", await db.discountApplication.count({ where: { schoolId: gea.id, status: "PENDING" } }));
await db.$disconnect();

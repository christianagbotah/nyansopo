import { PrismaClient } from "@prisma/client";
const db = new PrismaClient();
const en = await db.dailyServiceEnrollment.findMany({ where: { studentId: process.argv[2] }, include: { service: true } });
console.log(en.map(e => `${e.service.name}: override=${e.priceCentsOverride}`));
await db.$disconnect();

import { PrismaClient } from "@prisma/client";
const db = new PrismaClient();
const r = await db.discountApplication.deleteMany({ where: { reason: "test" } });
console.log("deleted", r.count);
await db.$disconnect();

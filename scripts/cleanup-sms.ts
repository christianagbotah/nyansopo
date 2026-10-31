import { PrismaClient } from "@prisma/client";
const pg = new PrismaClient();
const del = await pg.smsMessage.deleteMany({ where: { body: { contains: "Test Migrationson" } } });
console.log(`deleted ${del.count} smoke-test SMS`);
await pg.$disconnect();

import { PrismaClient } from "@prisma/client";
const pg = new PrismaClient();
const del = await pg.smsMessage.deleteMany({ where: { recipientName: "Test Guardian" } });
console.log(`deleted ${del.count}`);
await pg.$disconnect();

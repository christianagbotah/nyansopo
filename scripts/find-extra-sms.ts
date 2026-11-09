import { PrismaClient } from "@prisma/client";
const pg = new PrismaClient();
const msgs = await pg.smsMessage.findMany({ orderBy: { sentAt: "desc" }, take: 3, select: { id: true, body: true, sentAt: true, recipientName: true } });
console.log(JSON.stringify(msgs, null, 1));
await pg.$disconnect();

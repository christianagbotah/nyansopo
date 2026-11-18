process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres") ? process.env.DATABASE_URL : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";
const db = new PrismaClient();
const st = await db.student.groupBy({ by: ["status"], _count: true });
console.log("studentStatus:", JSON.stringify(st));
const inv = await db.invoice.findFirst({ where: { writtenOff: false, status: { in: ["UNPAID","PARTIAL","OVERDUE"] } }, select: { studentId: true } });
console.log("sampleInvoice:", inv);
if (inv) { const s = await db.student.findUnique({ where: { id: inv.studentId }, select: { status: true } }); console.log("sampleStudent:", s); }
const enr = await db.enrollment.groupBy({ by: ["status"], _count: true });
console.log("enrollStatus:", JSON.stringify(enr));
await db.$disconnect();

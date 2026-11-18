process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres") ? process.env.DATABASE_URL : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";
const db = new PrismaClient();
const schoolId = "cmwmaupxg000zq2x0tyj1k2pg";
const invoices = await db.invoice.findMany({
  where: { schoolId, writtenOff: false, status: { in: ["UNPAID", "PARTIAL", "OVERDUE"] } },
  select: { studentId: true, totalCents: true, discountCents: true, paidCents: true, createdAt: true },
});
console.log("invoices:", invoices.length);
const byStudent = new Map<string, { owingCents: number; oldest: Date }>();
for (const i of invoices) {
  const owe = i.totalCents - i.discountCents - i.paidCents;
  if (owe <= 0) continue;
  const cur = byStudent.get(i.studentId) ?? { owingCents: 0, oldest: i.createdAt };
  cur.owingCents += owe;
  if (i.createdAt < cur.oldest) cur.oldest = i.createdAt;
  byStudent.set(i.studentId, cur);
}
console.log("byStudent:", byStudent.size);
const ids = [...byStudent.keys()];
const students = ids.length ? await db.student.findMany({ where: { id: { in: ids }, status: "ACTIVE" }, select: { id: true } }) : [];
console.log("students:", students.length);
await db.$disconnect();

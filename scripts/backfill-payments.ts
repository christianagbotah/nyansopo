import { PrismaClient } from "@prisma/client";
const db = new PrismaClient();
async function main() {
  // payments linked to an invoice but missing studentId
  const orphan = await db.payment.findMany({ where: { studentId: null, invoiceId: { not: null } }, select: { id: true, invoiceId: true } });
  let fixed = 0;
  for (const p of orphan) {
    const inv = await db.invoice.findUnique({ where: { id: p.invoiceId! }, select: { studentId: true } });
    if (inv) { await db.payment.update({ where: { id: p.id }, data: { studentId: inv.studentId } }); fixed++; }
  }
  console.log(`fixed ${fixed} of ${orphan.length} payments`);
}
main().finally(() => db.$disconnect());

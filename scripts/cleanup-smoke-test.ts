/* Remove the migration smoke-test student (Test Migrationson) and its rows. */
import { PrismaClient } from "@prisma/client";
const pg = new PrismaClient();
const st = await pg.student.findFirst({ where: { lastName: "Migrationson" }, include: { invoices: { include: { items: true, payments: true } }, enrollments: true } });
if (!st) { console.log("already clean"); await pg.$disconnect(); process.exit(0); }
console.log(`removing ${st.nyansapoIndex} …`);
for (const inv of st.invoices) {
  await pg.invoiceItem.deleteMany({ where: { invoiceId: inv.id } });
  await pg.payment.deleteMany({ where: { invoiceId: inv.id } });
  await pg.invoice.delete({ where: { id: inv.id } });
}
await pg.enrollment.deleteMany({ where: { studentId: st.id } });
await pg.student.delete({ where: { id: st.id } });
console.log("done");
await pg.$disconnect();

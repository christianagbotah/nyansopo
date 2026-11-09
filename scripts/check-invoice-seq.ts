/* Check invoice numbering per school to size the collision bug. */
import { PrismaClient } from "@prisma/client";
const pg = new PrismaClient();
const schools = await pg.school.findMany({ select: { id: true, name: true, code: true } });
for (const s of schools) {
  const invoices = await pg.invoice.findMany({
    where: { schoolId: s.id },
    select: { invoiceNo: true },
    orderBy: { invoiceNo: "asc" },
  });
  const nums = invoices.map(i => i.invoiceNo);
  const count = nums.length;
  const maxSeq = Math.max(0, ...nums.map(n => parseInt((n.match(/(\d{4})$/) || [0, "0"])[1], 10)));
  const mismatch = count !== maxSeq ? " ← MISMATCH (count+1 collides)" : "";
  console.log(`${s.code}: count=${count} maxSeq=${maxSeq} first=${nums[0] ?? "-"} last=${nums[nums.length - 1] ?? "-"}${mismatch}`);
}
await pg.$disconnect();

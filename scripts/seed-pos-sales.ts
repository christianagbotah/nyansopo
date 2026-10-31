// Seed demo counter sales (POS) for inventory, aligned with iEduPro-style POS data.
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

async function main() {
  const school = await db.school.findFirst({ where: { name: { contains: "Nyansapo" } } }) ?? await db.school.findFirst();
  if (!school) throw new Error("No school found");
  const items = await db.inventoryItem.findMany({ where: { schoolId: school.id, sellPriceCents: { not: null } } });
  if (!items.length) throw new Error("No sellable items");
  const students = await db.enrollment.findMany({ where: { schoolId: school.id, status: "ACTIVE" }, include: { student: true }, take: 40 });

  const existing = await db.stockMovement.count({ where: { schoolId: school.id, saleNo: { not: null } } });
  if (existing > 0) { console.log(`already seeded: ${existing} sale lines`); return; }

  const sales: { buyer: string; studentId?: string; method: string; daysAgo: number; picks: number[] }[] = [
    { buyer: "Walk-in — Auntie Adjoa", method: "CASH", daysAgo: 9, picks: [0, 1] },
    { buyer: "", studentId: students[3]?.studentId, method: "MOMO_MTN", daysAgo: 8, picks: [2] },
    { buyer: "Walk-in — Mr. Kwesi", method: "CASH", daysAgo: 7, picks: [3, 4] },
    { buyer: "", studentId: students[7]?.studentId, method: "MOMO_VODAFONE", daysAgo: 5, picks: [5, 0] },
    { buyer: "Walk-in — Madam Efua", method: "CASH", daysAgo: 4, picks: [1, 6] },
    { buyer: "", studentId: students[11]?.studentId, method: "CASH", daysAgo: 3, picks: [7] },
    { buyer: "Walk-in — Yaw (parent)", method: "MOMO_MTN", daysAgo: 2, picks: [0, 2, 4] },
    { buyer: "", studentId: students[15]?.studentId, method: "MOMO_AIRTEL", daysAgo: 1, picks: [3] },
    { buyer: "Walk-in — Auntie Adjoa", method: "CASH", daysAgo: 0, picks: [5, 1] },
  ];

  let seq = 1000;
  for (const s of sales) {
    // filter to valid picks (in range, sellable, enough stock)
    const picked = s.picks.map((p) => items[p % items.length]).filter(Boolean);
    if (!picked.length) continue;
    let ok = true;
    for (const it of picked) {
      const avail = it.stockQty - 2;
      if (avail < 1) ok = false;
    }
    if (!ok) continue;
    seq++;
    const saleNo = `SLR-${String(seq).padStart(4, "0")}`;
    const at = new Date(Date.now() - s.daysAgo * 864e5 - Math.floor(Math.random() * 4) * 36e5);
    let buyer = s.buyer;
    if (s.studentId) {
      const e = students.find((x) => x.studentId === s.studentId);
      if (e) buyer = `${e.student.firstName} ${e.student.lastName}`;
    }
    if (!buyer) buyer = "Walk-in customer";
    for (const it of picked) {
      const qty = 1 + Math.floor(Math.random() * 2);
      if (it.stockQty < qty) continue;
      await db.stockMovement.create({
        data: {
          itemId: it.id, schoolId: school.id, type: "ISSUE", qty: -qty, unitCostCents: it.sellPriceCents,
          partyType: "SALE", partyName: buyer, studentId: s.studentId ?? null,
          saleNo, amountCents: (it.sellPriceCents ?? 0) * qty, note: `METHOD:${s.method}`, createdAt: at,
        },
      });
      await db.inventoryItem.update({ where: { id: it.id }, data: { stockQty: { decrement: qty } } });
    }
    console.log(`seeded ${saleNo} — ${buyer} (${s.method})`);
  }
  console.log("done");
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

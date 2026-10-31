/* Incremental seed for expenses, purchase orders & statutory settings (idempotent). */
import { PrismaClient } from "@prisma/client";
const db = new PrismaClient();
const C = (cedis: number) => Math.round(cedis * 100);

async function main() {
  const schools = await db.school.findMany({ select: { id: true, code: true } });
  const byCode: Record<string, string> = {};
  schools.forEach((s) => (byCode[s.code] = s.id));
  const GEA = byCode["GEA"], GKA = byCode["GKA"];

  // statutory defaults
  for (const s of schools) {
    const defaults: [string, string][] = [
      ["SSNIT_TIER1_EMPLOYEE", "5"], ["SSNIT_TIER1_EMPLOYER", "13.5"], ["SSNIT_TIER2", "5"],
      ["GETFUND", "2.5"], ["NHIL", "2.5"],
    ];
    for (const [key, value] of defaults) {
      await db.opsSetting.upsert({ where: { schoolId_key: { schoolId: s.id, key } }, update: {}, create: { schoolId: s.id, key, value } });
    }
  }
  console.log("✅ statutory settings");

  if (GEA) {
    const existingExpenses = await db.expense.count({ where: { schoolId: GEA } });
    if (existingExpenses === 0) {
      const expenses: [string, string, number, string][] = [
        ["SALARIES", "Monthly staff salaries — paid via MoMo batch", 12400, "BANK"],
        ["UTILITIES", "ECG postpaid electricity — September", 842.5, "MOMO_MTN"],
        ["UTILITIES", "Ghana Water — 3rd quarter", 265, "BANK"],
        ["UTILITIES", "Fibre broadband (Surfline) — monthly", 320, "MOMO_AT"],
        ["MAINTENANCE", "Roof repair — Basic 5 block", 1350, "CASH"],
        ["TRANSPORT_FUEL", "Diesel — school bus (200L)", 1580, "CASH"],
        ["TEACHING_MATERIALS", "NaCCA approved textbooks — Basic 4-6", 2260, "BANK"],
        ["TEACHING_MATERIALS", "Exercise books & chalk bulk purchase", 740, "CASH"],
        ["EVENTS", "Inter-house sports festival", 680, "MOMO_MTN"],
        ["ADMIN", "Printer toner & office stationery", 385, "CASH"],
        ["OTHER", "Fumigation & pest control", 450, "MOMO_MTN"],
      ];
      let expDate = new Date(); expDate.setMonth(expDate.getMonth() - 4);
      for (const [cat, desc, amt, method] of expenses) {
        await db.expense.create({ data: { schoolId: GEA, category: cat, description: desc, amountCents: C(amt), method, expenseDate: expDate, recordedByName: "Mr. Samuel Osei" } });
        expDate = new Date(expDate.getTime() + 11 * 864e5);
      }
      console.log("✅ GEA expenses");
    }
    const existingPOs = await db.purchaseOrder.count({ where: { schoolId: GEA } });
    if (existingPOs === 0) {
      const items = await db.inventoryItem.findMany({ where: { schoolId: GEA }, take: 4 });
      if (items.length >= 2) {
        const sup = await db.supplier.findFirst({ where: { schoolId: GEA } });
        const poLines = items.slice(0, 3).map((i, idx) => ({ itemId: i.id, name: i.name, qty: [60, 120, 40][idx] ?? 50, unitCostCents: i.unitCostCents }));
        await db.purchaseOrder.create({
          data: { schoolId: GEA, poNo: "PO-0101", supplierId: sup?.id ?? null, linesJson: JSON.stringify(poLines), totalCents: poLines.reduce((s, l) => s + l.qty * l.unitCostCents, 0), status: "PENDING", expectedDate: new Date(Date.now() + 7 * 864e5), createdByName: "Mr. Samuel Osei", note: "Restock before mid-term — deliver to the bursar's office." },
        });
        const receivedLines = items.slice(0, 2).map((i, idx) => ({ itemId: i.id, name: i.name, qty: 50 + idx * 30, unitCostCents: i.unitCostCents }));
        await db.purchaseOrder.create({
          data: { schoolId: GEA, poNo: "PO-0102", supplierId: sup?.id ?? null, linesJson: JSON.stringify(receivedLines), totalCents: receivedLines.reduce((s, l) => s + l.qty * l.unitCostCents, 0), status: "RECEIVED", receivedAt: new Date(Date.now() - 5 * 864e5), createdByName: "Mr. Samuel Osei" },
        });
        console.log("✅ GEA purchase orders");
      }
    }
  }
  if (GKA) {
    if ((await db.expense.count({ where: { schoolId: GKA } })) === 0) {
      await db.expense.create({ data: { schoolId: GKA, category: "UTILITIES", description: "ECG prepaid — tokens", amountCents: C(410), method: "MOMO_MTN", recordedByName: "Mrs. Josephine Agyemang" } });
      await db.expense.create({ data: { schoolId: GKA, category: "MAINTENANCE", description: "Blackboard resurfacing — lower primary", amountCents: C(520), method: "CASH", recordedByName: "Mrs. Josephine Agyemang" } });
      console.log("✅ GKA expenses");
    }
  }
  console.log("🎉 Incremental ops seed done.");
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

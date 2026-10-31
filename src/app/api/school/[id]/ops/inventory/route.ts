import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

// ── GET /api/school/[id]/ops/inventory — items, movements, suppliers ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET");
  if (!auth.ok) return auth.res;

  const items = await db.inventoryItem.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
  const movements = await db.stockMovement.findMany({
    where: { schoolId },
    orderBy: { createdAt: "desc" },
    take: 80,
    include: { item: true, student: true },
  });
  const suppliers = await db.supplier.findMany({ where: { schoolId }, orderBy: { name: "asc" } });

  const stockValue = items.reduce((s, i) => s + i.stockQty * i.unitCostCents, 0);
  const lowStock = items.filter((i) => i.stockQty < i.reorderLevel);
  const students = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, include: { student: true, classroom: true }, orderBy: { classroom: { name: "asc" } } });

  // counter sales (POS): group ISSUE movements carrying a saleNo
  const saleLines = await db.stockMovement.findMany({
    where: { schoolId, saleNo: { not: null } },
    orderBy: { createdAt: "desc" },
    take: 240,
    include: { item: true },
  });
  const saleMap: Record<string, { saleNo: string; buyer: string; method: string; total: number; at: string; lines: { item: string; qty: number; amount: number }[] }> = {};
  for (const l of saleLines) {
    const no = l.saleNo!;
    saleMap[no] ??= {
      saleNo: no, buyer: l.partyName ?? "Walk-in", method: "",
      total: 0, at: l.createdAt.toISOString(), lines: [],
    };
    saleMap[no].total += l.amountCents ?? 0;
    saleMap[no].lines.push({ item: l.item.name, qty: Math.abs(l.qty), amount: l.amountCents ?? 0 });
    if (l.note?.startsWith("METHOD:")) saleMap[no].method = l.note.slice(7);
  }
  const sales = Object.values(saleMap).sort((a, b) => (a.at < b.at ? 1 : -1)).slice(0, 24);
  const monthStart = new Date(); monthStart.setDate(1); monthStart.setHours(0, 0, 0, 0);
  const salesMonth = saleLines.filter((l) => l.createdAt >= monthStart).reduce((s, l) => s + (l.amountCents ?? 0), 0);

  return NextResponse.json({
    items: items.map((i) => ({ id: i.id, name: i.name, sku: i.sku, category: i.category, unit: i.unit, unitCost: i.unitCostCents, sellPrice: i.sellPriceCents, stockQty: i.stockQty, reorderLevel: i.reorderLevel, location: i.location })),
    movements: movements.map((m) => ({
      id: m.id, item: m.item.name, itemId: m.itemId, type: m.type, qty: m.qty,
      unitCost: m.unitCostCents, partyType: m.partyType, partyName: m.partyName,
      student: m.student ? `${m.student.firstName} ${m.student.lastName}` : null,
      chargedToInvoice: m.chargedToInvoice, note: m.note, createdAt: m.createdAt,
    })),
    suppliers: suppliers.map((s) => ({ id: s.id, name: s.name, phone: s.phone, email: s.email, itemsSupplied: s.itemsSupplied, notes: s.notes })),
    stats: { stockValue, lowStock: lowStock.length, items: items.length, movements: movements.length, salesMonth, salesCount: sales.length },
    lowStockItems: lowStock.map((i) => ({ id: i.id, name: i.name, stockQty: i.stockQty, reorderLevel: i.reorderLevel, unit: i.unit })),
    students: students.map((e) => ({ id: e.studentId, name: `${e.student.firstName} ${e.student.lastName}`, classroom: e.classroom.name })),
    sales,
  });
}

// ── POST /api/school/[id]/ops/inventory ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST");
  if (!auth.ok) return auth.res;
  const user = auth.user;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;

  if (action === "save-item") {
    const { id, name, sku, category, unit, unitCostCents, sellPriceCents, reorderLevel, location, initialQty } = body;
    if (!name) return NextResponse.json({ error: "Item name is required" }, { status: 400 });
    const data = {
      name, sku: sku || null, category: category ?? "OTHER", unit: unit || "pcs",
      unitCostCents: Number(unitCostCents) || 0, sellPriceCents: sellPriceCents ? Number(sellPriceCents) : null,
      reorderLevel: Number(reorderLevel) || 10, location: location || null,
    };
    const item = id
      ? await db.inventoryItem.update({ where: { id }, data })
      : await db.inventoryItem.create({ data: { ...data, schoolId, stockQty: Number(initialQty) || 0 } });
    return NextResponse.json({ item });
  }

  if (action === "movement") {
    const { itemId, type, qty, unitCostCents, partyType, partyName, studentId, chargedToInvoice, note } = body;
    const item = await db.inventoryItem.findUnique({ where: { id: itemId } });
    if (!item || item.schoolId !== schoolId) return NextResponse.json({ error: "Item not found" }, { status: 404 });
    const q = Number(qty);
    if (!q) return NextResponse.json({ error: "Quantity is required" }, { status: 400 });
    const signed = type === "ISSUE" ? -Math.abs(q) : Math.abs(q);
    if (item.stockQty + signed < 0) return NextResponse.json({ error: `Only ${item.stockQty} ${item.unit} in stock` }, { status: 400 });
    const mv = await db.stockMovement.create({
      data: { itemId, schoolId, type, qty: signed, unitCostCents: unitCostCents ? Number(unitCostCents) : item.unitCostCents, partyType: partyType ?? null, partyName: partyName ?? null, studentId: studentId ?? null, chargedToInvoice: !!chargedToInvoice, note: note ?? null },
    });
    const updated = await db.inventoryItem.update({ where: { id: itemId }, data: { stockQty: { increment: signed } } });

    // charge the issue to the learner's current-term invoice
    let invoiceLine: { id: string; invoiceId: string; name: string; amountCents: number } | null = null;
    if (type === "ISSUE" && chargedToInvoice && studentId) {
      const price = item.sellPriceCents ?? item.unitCostCents;
      const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
      const invoice = term ? await db.invoice.findFirst({ where: { schoolId, termId: term.id, studentId } }) : null;
      if (invoice) {
        const lineName = `${item.name}${Math.abs(signed) > 1 ? ` ×${Math.abs(signed)}` : ""}`;
        invoiceLine = await db.invoiceItem.create({ data: { invoiceId: invoice.id, name: lineName, amountCents: price * Math.abs(signed) } });
        const total = invoice.totalCents + price * Math.abs(signed);
        await db.invoice.update({
          where: { id: invoice.id },
          data: { totalCents: total, status: invoice.paidCents >= total - invoice.discountCents ? "PAID" : invoice.paidCents > 0 ? "PARTIAL" : "UNPAID" },
        });
      }
    }
    void user;
    return NextResponse.json({ movement: mv, item: updated, invoiceLine });
  }

  // ── counter sale (POS): multi-item cart, cash or MoMo, stock decremented ──
  if (action === "sale") {
    const { lines, buyerName, studentId, method } = body; // lines: [{ itemId, qty }]
    if (!Array.isArray(lines) || lines.length === 0) return NextResponse.json({ error: "Cart is empty" }, { status: 400 });
    let buyer = (buyerName ?? "").toString().trim();
    let student: { id: string; firstName: string; lastName: string } | null = null;
    if (studentId) {
      student = await db.student.findUnique({ where: { id: studentId } });
      if (!student) return NextResponse.json({ error: "Learner not found" }, { status: 404 });
      buyer = `${student.firstName} ${student.lastName}`;
    }
    if (!buyer) buyer = "Walk-in customer";
    // validate all lines first
    const prepared: { item: { id: string; name: string; sellPriceCents: number | null; unitCostCents: number; stockQty: number; unit: string }; qty: number }[] = [];
    for (const l of lines) {
      const item = await db.inventoryItem.findUnique({ where: { id: l.itemId } });
      if (!item || item.schoolId !== schoolId) return NextResponse.json({ error: "Item not found" }, { status: 404 });
      const qty = Math.floor(Number(l.qty));
      if (!qty || qty <= 0) return NextResponse.json({ error: `Invalid quantity for ${item.name}` }, { status: 400 });
      if (item.sellPriceCents === null) return NextResponse.json({ error: `${item.name} is not for sale (internal use only)` }, { status: 400 });
      if (item.stockQty < qty) return NextResponse.json({ error: `Only ${item.stockQty} ${item.unit} of ${item.name} left` }, { status: 400 });
      prepared.push({ item, qty });
    }
    const total = prepared.reduce((s, p) => s + (p.item.sellPriceCents ?? 0) * p.qty, 0);
    const seq = (await db.stockMovement.count({ where: { schoolId, saleNo: { not: null } } })) + 1001;
    const saleNo = `SLR-${String(seq).padStart(4, "0")}`;
    const payMethod = method ?? "CASH";
    for (const p of prepared) {
      const mv = await db.stockMovement.create({
        data: {
          itemId: p.item.id, schoolId, type: "ISSUE", qty: -p.qty, unitCostCents: p.item.sellPriceCents,
          partyType: "SALE", partyName: buyer, studentId: student?.id ?? null,
          saleNo, amountCents: (p.item.sellPriceCents ?? 0) * p.qty, note: `METHOD:${payMethod}`,
        },
      });
      await db.inventoryItem.update({ where: { id: p.item.id }, data: { stockQty: { decrement: p.qty } } });
      void mv;
    }
    return NextResponse.json({
      sale: { saleNo, buyer, method: payMethod, total, lines: prepared.map((p) => ({ item: p.item.name, qty: p.qty, amount: (p.item.sellPriceCents ?? 0) * p.qty })) },
    });
  }

  if (action === "save-supplier") {
    const { id, name, phone, email, itemsSupplied, notes } = body;
    if (!name) return NextResponse.json({ error: "Supplier name is required" }, { status: 400 });
    const data = { name, phone: phone || null, email: email || null, itemsSupplied: itemsSupplied || null, notes: notes || null };
    const sup = id ? await db.supplier.update({ where: { id }, data }) : await db.supplier.create({ data: { ...data, schoolId } });
    return NextResponse.json({ supplier: sup });
  }

  if (action === "delete-supplier") {
    await db.supplier.delete({ where: { id: body.id } }).catch(() => null);
    return NextResponse.json({ ok: true });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

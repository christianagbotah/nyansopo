import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendSms } from "@/lib/sms";
import { nextReceiptNo } from "@/lib/codes";
import { audit } from "@/lib/audit";

// ── wave 77 — SCHOOL SHOP console (shop attendant / bursar / cashier) ──
// The catalog the parent portal sells from, and the order queue:
//   PENDING_PAYMENT → PAID (online via the payments engine, or COUNTER here)
//   PAID → READY (packed) → DELIVERED (handed to the learner)
//   PENDING_PAYMENT → CANCELLED (parent or shop)
// Every consequential transition alerts the guardian (gated on
// SmsSetting.evtShopOrders; delivery rides the SMS provider chosen in
// Settings — SIMULATED / ARKESEL / TERMII / HUBTEL).

export const SHOP_CATEGORIES = ["STATIONERY", "UNIFORM", "BOOKS", "SUPPLIES", "OTHER"];

/** Guardian SMS for an order event — never throws, respects the school gate. */
async function orderSms(orderId: string, body: string) {
  const order = await db.shopOrder.findUnique({
    where: { id: orderId },
    include: { student: { select: { id: true, firstName: true, guardianPhone: true, guardianName: true } } },
  });
  if (!order?.student) return;
  const gate = await db.smsSetting.findUnique({ where: { schoolId: order.schoolId }, select: { evtShopOrders: true } });
  if (!gate?.evtShopOrders) return;
  await sendSms({
    db, schoolId: order.schoolId, studentId: order.student.id,
    recipientName: order.parentName || order.student.guardianName,
    recipientPhone: order.student.guardianPhone,
    body, category: "SHOP", sentByName: "School shop",
  }).catch(() => {});
}

/** Stock decrement for lines that track stock; tolerates a concurrent sale. */
async function drawStock(orderId: string): Promise<number> {
  const lines = await db.shopOrderItem.findMany({ where: { orderId, itemId: { not: null } } });
  let misses = 0;
  for (const line of lines) {
    const dec = await db.shopItem.updateMany({
      where: { id: line.itemId!, stock: { not: null, gte: line.qty } },
      data: { stock: { decrement: line.qty } },
    });
    if (!dec.count) misses++;
  }
  return misses;
}

// ── GET — catalog + order queue + counters ─────────────────────
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["shop.view"] });
  if (!auth.ok) return auth.res;

  const items = await db.shopItem.findMany({ where: { schoolId }, orderBy: [{ category: "asc" }, { name: "asc" }] });
  const orders = await db.shopOrder.findMany({
    where: { schoolId },
    include: {
      items: true,
      student: { include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: { select: { name: true } } } } } },
      paymentRequests: { select: { id: true, clientReference: true, status: true, checkoutUrl: true } },
    },
    orderBy: { createdAt: "desc" },
    take: 300,
  });
  const counts = {
    pendingPayment: orders.filter((o) => o.status === "PENDING_PAYMENT").length,
    paid: orders.filter((o) => o.status === "PAID").length,
    ready: orders.filter((o) => o.status === "READY").length,
    deliveredToday: orders.filter((o) => o.status === "DELIVERED" && o.deliveredAt && Date.now() - o.deliveredAt.getTime() < 864e5).length,
  };
  const lowStock = items.filter((i) => i.active && i.stock !== null && i.stock <= i.lowStockAt);
  return NextResponse.json({ items, orders, counts, lowStock, categories: SHOP_CATEGORIES });
}

// ── POST — catalog CRUD + order lifecycle ──────────────────────
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["shop.manage"] });
  if (!auth.ok) return auth.res;
  const { user } = auth;
  const body = await req.json().catch(() => ({}));

  // ── catalog ──────────────────────────────────────────────────
  if (body.action === "item.create" || body.action === "item.update") {
    const name = String(body.name ?? "").trim();
    const priceCents = Math.round(Number(body.priceCents));
    if (name.length < 2) return NextResponse.json({ error: "Item name is too short" }, { status: 400 });
    if (!Number.isFinite(priceCents) || priceCents <= 0) return NextResponse.json({ error: "Enter a price greater than zero" }, { status: 400 });
    const category = SHOP_CATEGORIES.includes(body.category) ? body.category : "STATIONERY";
    const stock = body.stock === null || body.stock === "" || body.stock === undefined ? null : Math.max(0, Math.round(Number(body.stock)));
    if (stock !== null && !Number.isFinite(stock)) return NextResponse.json({ error: "Stock must be a number or blank (untracked)" }, { status: 400 });
    const lowStockAt = Math.max(0, Math.round(Number(body.lowStockAt) || 5));

    if (body.action === "item.create") {
      const item = await db.shopItem.create({
        data: { schoolId, name, category, priceCents, stock, lowStockAt, active: true },
      });
      await audit({ schoolId, actor: user, action: "CREATE", entityType: "ShopItem", entityId: item.id, req, summary: `Shop catalog: added "${name}" (${category}) at GHS ${(priceCents / 100).toFixed(2)}${stock !== null ? `, ${stock} in stock` : ", untracked"}` });
      return NextResponse.json({ item });
    }
    const existing = await db.shopItem.findFirst({ where: { id: String(body.itemId), schoolId } });
    if (!existing) return NextResponse.json({ error: "Item not found" }, { status: 404 });
    const item = await db.shopItem.update({ where: { id: existing.id }, data: { name, category, priceCents, stock, lowStockAt } });
    await audit({ schoolId, actor: user, action: "UPDATE", entityType: "ShopItem", entityId: item.id, req, summary: `Shop catalog: updated "${name}"` });
    return NextResponse.json({ item });
  }

  if (body.action === "item.toggle") {
    const item = await db.shopItem.findFirst({ where: { id: String(body.itemId), schoolId } });
    if (!item) return NextResponse.json({ error: "Item not found" }, { status: 404 });
    const upd = await db.shopItem.update({ where: { id: item.id }, data: { active: !item.active } });
    await audit({ schoolId, actor: user, action: "UPDATE", entityType: "ShopItem", entityId: item.id, req, summary: `${upd.active ? "Re-listed" : "Delisted"} "${item.name}" ${upd.active ? "on" : "from"} the parent shop` });
    return NextResponse.json({ item: upd });
  }

  if (body.action === "item.stock") {
    const item = await db.shopItem.findFirst({ where: { id: String(body.itemId), schoolId } });
    if (!item) return NextResponse.json({ error: "Item not found" }, { status: 404 });
    const delta = Math.round(Number(body.delta));
    if (!Number.isFinite(delta) || delta === 0) return NextResponse.json({ error: "Enter a stock movement (+/-)" }, { status: 400 });
    const upd = await db.shopItem.update({ where: { id: item.id }, data: { stock: Math.max(0, (item.stock ?? 0) + delta) } });
    await audit({ schoolId, actor: user, action: "UPDATE", entityType: "ShopItem", entityId: item.id, req, summary: `Stock movement for "${item.name}": ${delta > 0 ? "+" : ""}${delta} → ${upd.stock}` });
    return NextResponse.json({ item: upd });
  }

  if (body.action === "item.delete") {
    const item = await db.shopItem.findFirst({
      where: { id: String(body.itemId), schoolId },
      include: { _count: { select: { orderItems: true } } },
    });
    if (!item) return NextResponse.json({ error: "Item not found" }, { status: 404 });
    if (item._count.orderItems > 0) return NextResponse.json({ error: "This item has been ordered before — delist it instead of deleting (order history must stay intact)" }, { status: 409 });
    await db.shopItem.delete({ where: { id: item.id } });
    await audit({ schoolId, actor: user, action: "DELETE", entityType: "ShopItem", entityId: item.id, req, summary: `Shop catalog: deleted "${item.name}"` });
    return NextResponse.json({ ok: true });
  }

  // ── order lifecycle ─────────────────────────────────────────
  const loadOrder = () =>
    db.shopOrder.findFirst({
      where: { id: String(body.orderId), schoolId },
      include: {
        items: true,
        student: { include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: { select: { name: true } } } } } },
        paymentRequests: { select: { id: true, clientReference: true, status: true, checkoutUrl: true } },
      },
    });

  if (body.action === "order.mark-paid") {
    const order = await loadOrder();
    if (!order) return NextResponse.json({ error: "Order not found" }, { status: 404 });
    if (order.status !== "PENDING_PAYMENT") return NextResponse.json({ error: order.status === "CANCELLED" ? "This order was cancelled" : "This order is already settled" }, { status: 409 });
    const method = ["CASH", "MOMO_MTN", "MOMO_TELECEL", "MOMO_AT", "BANK", "CHEQUE", "CARD"].includes(body.method) ? body.method : "CASH";
    const receiptNo = await nextReceiptNo();
    // receipt first (money in), then CAS the order — a retried click re-reads
    // PENDING_PAYMENT and is refused, so a double click can never double-charge
    const payment = await db.payment.create({
      data: { receiptNo, studentId: order.studentId, category: "SHOP", schoolId, amountCents: order.totalCents, method, reference: `SHOP ${order.orderNo}`, receivedByName: user.fullName, paidAt: new Date() },
    });
    const claim = await db.shopOrder.updateMany({
      where: { id: order.id, status: "PENDING_PAYMENT" },
      data: { status: "PAID", paymentMethod: "COUNTER", paidAt: new Date(), paidByName: user.fullName },
    });
    if (!claim.count) {
      await db.payment.delete({ where: { id: payment.id } }).catch(() => {});
      return NextResponse.json({ error: "This order is already settled" }, { status: 409 });
    }
    await drawStock(order.id);
    await audit({ schoolId, actor: user, action: "CREATE", entityType: "Payment", entityId: payment.id, req, summary: `Shop order ${order.orderNo} paid at the counter — GHS ${(order.totalCents / 100).toFixed(2)} (${method}), receipt ${receiptNo}` });
    await orderSms(order.id, `Shop order ${order.orderNo} paid at the school office — GHS ${(order.totalCents / 100).toFixed(2)}, receipt ${receiptNo}. The items will be delivered to ${order.student.firstName} in class. Thank you.`);
    return NextResponse.json({ ok: true, receiptNo });
  }

  if (body.action === "order.ready") {
    const order = await loadOrder();
    if (!order) return NextResponse.json({ error: "Order not found" }, { status: 404 });
    if (order.status !== "PAID") return NextResponse.json({ error: "Only paid orders can be packed" }, { status: 409 });
    await db.shopOrder.updateMany({ where: { id: order.id, status: "PAID" }, data: { status: "READY", readyAt: new Date(), readyByName: user.fullName } });
    await audit({ schoolId, actor: user, action: "UPDATE", entityType: "ShopOrder", entityId: order.id, req, summary: `Shop order ${order.orderNo} packed & ready for delivery to ${order.student.firstName}` });
    await orderSms(order.id, `Good news — shop order ${order.orderNo} (${order.items.map((i) => `${i.qty}× ${i.name}`).join(", ")}) is packed and will be delivered to ${order.student.firstName} today.`);
    return NextResponse.json({ ok: true });
  }

  if (body.action === "order.deliver") {
    const order = await loadOrder();
    if (!order) return NextResponse.json({ error: "Order not found" }, { status: 404 });
    if (order.status !== "READY") return NextResponse.json({ error: "Pack the order (mark it ready) before delivering" }, { status: 409 });
    await db.shopOrder.updateMany({ where: { id: order.id, status: "READY" }, data: { status: "DELIVERED", deliveredAt: new Date(), deliveredByName: user.fullName } });
    await audit({ schoolId, actor: user, action: "UPDATE", entityType: "ShopOrder", entityId: order.id, req, summary: `Shop order ${order.orderNo} delivered to ${order.student.firstName} (${order.student.enrollments[0]?.classroom?.name ?? "class n/a"})` });
    await orderSms(order.id, `Shop order ${order.orderNo} has been delivered to ${order.student.firstName} at school. Medaase for shopping with us.`);
    return NextResponse.json({ ok: true });
  }

  if (body.action === "order.cancel") {
    const order = await loadOrder();
    if (!order) return NextResponse.json({ error: "Order not found" }, { status: 404 });
    if (!["PENDING_PAYMENT", "PAID"].includes(order.status)) return NextResponse.json({ error: `A ${order.status.toLowerCase()} order cannot be cancelled` }, { status: 409 });
    const reason = String(body.reason ?? "").trim().slice(0, 300) || "Cancelled by the school";
    await db.shopOrder.updateMany({ where: { id: order.id, status: { in: ["PENDING_PAYMENT", "PAID"] } }, data: { status: "CANCELLED", cancelReason: reason } });
    await audit({ schoolId, actor: user, action: "UPDATE", entityType: "ShopOrder", entityId: order.id, req, summary: `Shop order ${order.orderNo} cancelled — ${reason}${order.status === "PAID" ? " (paid — arrange refund/credit with the cashier)" : ""}` });
    await orderSms(order.id, `Shop order ${order.orderNo} has been cancelled by the school (${reason}).${order.status === "PAID" ? " Please pass by the office to collect a refund or credit the amount toward fees." : ""}`);
    return NextResponse.json({ ok: true });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

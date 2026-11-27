/* eslint-disable @typescript-eslint/no-require-imports */
// ── post-rollback repair: the wave-78 providers smoke + E2E ran on 2026-11-27
//    21:08-21:11, but the sandbox rolled back before its cleanup finished.
//    The safety-net then restored a cluster snapshot taken mid-test, so the
//    demo DB carries smoke residue: PSK-/FLW-261127- payment requests, order
//    SO-00004, "Wave 78 smoke notebook" / "Exercise book (200 pages)" items,
//    their receipts, wallet top-ups, one partial invoice payment and the SMS
//    trail. This script maps every row, surgically removes it, restores the
//    balances/invoice/stock it moved, and re-seeds the GEA 10-item starter
//    catalog (seed-ops skips it while leftovers exist). Idempotent: safe to
//    re-run. usage: bun scripts/cleanup-wave78-leftovers.ts
const { PrismaClient } = require("@prisma/client");
const db = new PrismaClient();

const GEA_ID = "cmwq8hof4000zmoe1gbjv3rr1";
const C = (ghs: number) => Math.round(ghs * 100);

(async () => {
  console.log("── wave-78 leftover cleanup (post-rollback repair) ──");

  // ── 1. map the smoke payment requests (strict: 261127 refs only) ──
  const reqs = await db.paymentRequest.findMany({
    where: {
      createdAt: { gte: new Date("2026-11-27T00:00:00Z") },
      OR: [{ clientReference: { startsWith: "PSK-261127-" } }, { clientReference: { startsWith: "FLW-261127-" } }],
    },
    include: { appliedPayment: true, shopOrder: { include: { items: true } } },
  });
  console.log(`1. payment requests: ${reqs.length}`);
  if (!reqs.length) console.log("   (nothing to do — already clean)");
  const reqIds = reqs.map((r) => r.id);
  const payments = reqs.map((r) => r.appliedPayment).filter(Boolean);

  // ── 2. restore wallet balances (PREPAID|TRANSPORT credit from top-ups) ──
  let walletFixed = 0;
  for (const r of reqs.filter((x) => x.purpose === "WALLET")) {
    const txn = await db.walletTxn.findFirst({
      where: { studentId: r.studentId!, kind: "TOPUP", amountCents: r.amountCents, at: { gte: new Date("2026-11-27T21:00:00Z") } },
    });
    if (!txn) { console.log(`   ?? no wallet txn for ${r.clientReference}`); continue; }
    const kind = txn.wallet === "TRANSPORT" ? "TRANSPORT" : "PREPAID";
    await db.studentWallet.update({
      where: { studentId: r.studentId! },
      data: kind === "PREPAID" ? { prepaidCents: { decrement: txn.amountCents } } : { transportCents: { decrement: txn.amountCents } },
    });
    await db.walletTxn.delete({ where: { id: txn.id } });
    walletFixed++;
    console.log(`   wallet ${r.clientReference}: −${txn.amountCents} ${kind}, txn removed`);
  }

  // ── 3. restore the partially-paid invoice (PSK 5000 = ₵50) — idempotent ──
  for (const r of reqs.filter((x) => x.purpose === "INVOICE" && x.invoiceId)) {
    const inv = await db.invoice.findUnique({ where: { id: r.invoiceId! } });
    if (!inv) continue;
    const rowsSum = (await db.payment.findMany({ where: { invoiceId: inv.id }, select: { amountCents: true } })).reduce((a, x) => a + x.amountCents, 0);
    if (inv.paidCents !== rowsSum) { console.log(`   invoice ${inv.invoiceNo}: already restored (paid ${inv.paidCents} vs rows ${rowsSum})`); continue; }
    const paid = Math.max(0, inv.paidCents - r.amountCents);
    const status = paid <= 0 ? "UNPAID" : paid >= inv.totalCents ? "PAID" : "PARTIAL";
    await db.invoice.update({ where: { id: inv.id }, data: { paidCents: paid, status } });
    console.log(`   invoice ${inv.invoiceNo}: paid ${inv.paidCents}→${paid}, status ${inv.status}→${status}`);
  }

  // ── 4. restore stock drawn by the smoke order, then delete its rows ──
  const orders = await db.shopOrder.findMany({
    where: { createdAt: { gte: new Date("2026-11-27T00:00:00Z") } },
    include: { items: true, parent: true },
  });
  for (const o of orders) {
    for (const it of o.items) {
      if (it.itemId) await db.shopItem.update({ where: { id: it.itemId }, data: { stock: { increment: it.qty } } }).catch(() => {});
    }
    console.log(`   order ${o.orderNo} (${o.status}, ${o.totalCents} by ${o.parentName}) + ${o.items.length} line(s) removed`);
    await db.shopOrder.delete({ where: { id: o.id } }); // items cascade
  }

  // ── 5. delete events → requests → payments (FK order) ──
  if (reqIds.length) {
    await db.paymentEvent.deleteMany({ where: { requestId: { in: reqIds } } });
    await db.paymentRequest.deleteMany({ where: { id: { in: reqIds } } });
  }
  for (const p of payments) {
    await db.payment.delete({ where: { id: p!.id } });
    console.log(`   receipt ${p!.receiptNo} (${p!.amountCents} ${p!.category}) removed`);
  }

  // ── 6. SMS trail ──
  const smss = await db.smsMessage.findMany({
    where: { sentAt: { gte: new Date("2026-11-27T20:00:00Z") }, OR: [
      { body: { contains: "SO-00004" } }, { body: { contains: "PSK-261127" } }, { body: { contains: "FLW-261127" } },
    ] },
  });
  for (const s of smss) { console.log(`   sms → ${s.recipientPhone}: ${s.body.slice(0, 60)}…`); await db.smsMessage.delete({ where: { id: s.id } }); }

  // ── 7. leftover catalog items (both non-seed, created Nov 27) ──
  const leftover = await db.shopItem.findMany({ where: { schoolId: GEA_ID, createdAt: { gte: new Date("2026-11-27T00:00:00Z") } } });
  for (const it of leftover) { console.log(`   item "${it.name}" removed`); await db.shopItem.delete({ where: { id: it.id } }); }

  // ── 8. re-seed the GEA starter catalog (exact seed-ops.ts block) ──
  const exists = await db.shopItem.count({ where: { schoolId: GEA_ID } });
  if (!exists) {
    const shopItems: [string, string, number, number | null][] = [
      ["Exercise Book (40 leaves)", "STATIONERY", C(3), 120],
      ["Exercise Book (80 leaves)", "STATIONERY", C(5), 90],
      ["My First Copybook (A-Z)", "STATIONERY", C(4), 60],
      ["School Pen — blue ink", "STATIONERY", C(2), 200],
      ["Pencil HB + eraser", "STATIONERY", C(2.5), 150],
      ["Mathematical set", "STATIONERY", C(18), 40],
      ["School uniform — full set", "UNIFORM", C(120), null],
      ["PE kit (shirt & shorts)", "UNIFORM", C(65), null],
      ["School branded water bottle", "SUPPLIES", C(20), 45],
      ["GES approved reader — Basic 4", "BOOKS", C(28), 35],
    ];
    for (const [name, category, priceCents, stock] of shopItems) {
      await db.shopItem.create({ data: { schoolId: GEA_ID, name, category, priceCents, stock, lowStockAt: 10, active: true } });
    }
    console.log("   GEA starter catalog re-seeded: 10 items");
  }

  // ── 9. verify ──
  const [reqs2, orders2, items, pays2] = await Promise.all([
    db.paymentRequest.count({ where: { OR: [{ clientReference: { startsWith: "PSK-261127-" } }, { clientReference: { startsWith: "FLW-261127-" } }] } }),
    db.shopOrder.count({ where: { createdAt: { gte: new Date("2026-11-27T00:00:00Z") } } }),
    db.shopItem.count({ where: { schoolId: GEA_ID } }),
    db.payment.count({ where: { reference: { in: reqs.map((r) => r.clientReference) } } }),
  ]);
  console.log(`verify: smokeReqs=${reqs2} (want 0) smokeOrders=${orders2} (want 0) paymentsOrphan=${pays2} (want 0) catalog=${items} (want 10)`);
  console.log("cleanup complete — demo ledger restored to seed baseline");
  await db.$disconnect();
  process.exit(reqs2 === 0 && orders2 === 0 && pays2 === 0 && items === 10 ? 0 : 1);
})().catch(async (e) => { console.error("FAILED:", e.message); await db.$disconnect(); process.exit(1); });

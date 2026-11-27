/* eslint-disable @typescript-eslint/no-require-imports */
// ── wave 78 verify + cleanup: check the providers smoke left the right
//    ledger traces (Paystack/Flutterwave-labelled payments, wallet txns,
//    invoice delta, stock drawn, SMS trail), then restore everything.
//    usage: bun scripts/cleanup-providers-smoke.ts /tmp/smoke-providers-state.json --verify
const { PrismaClient } = require("@prisma/client");
const db = new PrismaClient();

(async () => {
  const statePath = process.argv[2];
  const doVerify = process.argv.includes("--verify");
  if (!statePath) { console.error("usage: cleanup-providers-smoke.ts <state.json> [--verify]"); process.exit(1); }
  const s = JSON.parse(require("fs").readFileSync(statePath, "utf8"));
  let bad = 0;
  const check = (name, cond, detail) => {
    console.log(`   ${cond ? "ok" : "XX"} ${name}${detail ? ` — ${detail}` : ""}`);
    if (!cond) bad++;
  };

  // ── collect the money rows BEFORE deleting anything ──
  const payments = await db.payment.findMany({ where: { schoolId: s.schoolId, reference: { in: s.refs } } });
  const receiptNos = payments.map((p) => p.receiptNo);
  const refs = [...s.refs, s.pendingRef].filter(Boolean);

  // ── verification (pre-cleanup) ──
  if (doVerify) {
    // 1. three completed payments: wallet-paystack, wallet-flutterwave, fees-paystack, shop-flutterwave
    check("money rows", payments.length === 4, `${payments.length} Payment rows (2 wallet + 1 fees + 1 shop) — ${receiptNos.join(", ")}`);
    check("receipt numbers", payments.every((p) => /^RCP-\d{4}-\d{5}$/.test(p.receiptNo)), "RCP-YYYY-NNNNN shape");
    // 2. provider-labelled references (PSK-/FLW- prefixes rode the requests)
    check("gateway references", payments.every((p) => /^PSK-|^FLW-/.test(p.reference ?? "")), payments.map((p) => p.reference).join(", "));

    // 3. wallet ledger moved twice (both top-ups)
    const wtxns = await db.walletTxn.findMany({ where: { studentId: s.wallet.studentId, kind: "TOPUP", OR: s.wallet.refs.map((r) => ({ note: { contains: r } })) } });
    const wsum = wtxns.reduce((x, t) => x + t.amountCents, 0);
    check("wallet txns", wtxns.length === 2 && wsum === 2000, `${wtxns.length} rows, +${wsum} pesewas`);

    // 4. invoice payment landed on the right invoice
    const feesPay = payments.find((p) => p.invoiceId === s.invoice.id);
    check("fees payment", !!feesPay, feesPay ? `${feesPay.receiptNo} on ${s.invoice.invoiceNo}` : "missing");

    // 5. stock drawn by one
    const item = await db.shopItem.findUnique({ where: { id: s.shop.itemId } });
    check("stock drawn", item && item.stock === s.shop.stockBefore - 1, `stock=${item?.stock} (${s.shop.stockBefore} − 1)`);

    // 6. guardian SMS trail: PAYMENT receipt automation + SHOP order confirmation
    const smsPay = await db.smsMessage.count({ where: { schoolId: s.schoolId, category: "PAYMENT", OR: receiptNos.map((r) => ({ body: { contains: r } })) } });
    const smsShop = await db.smsMessage.count({ where: { schoolId: s.schoolId, category: "SHOP", body: { contains: s.shop.orderNo } } });
    check("receipt SMS", smsPay >= 3, `${smsPay} PAYMENT rows naming our receipts`);
    check("shop order SMS", smsShop >= 1, `${smsShop} SHOP rows for ${s.shop.orderNo}`);

    if (bad) { console.error(`VERIFY FAILED (${bad} check(s)) — aborting cleanup so the state can be inspected`); process.exit(1); }
    console.log("   verify: all ledger checks green");
  }

  // ── cleanup (order matters: SMS → requests → payments → wallet → invoice → order → stock) ──
  const delSms = await db.smsMessage.deleteMany({
    where: {
      schoolId: s.schoolId,
      OR: [
        { body: { contains: s.shop.orderNo } },
        ...receiptNos.map((r) => ({ body: { contains: r } })),
        ...refs.map((r) => ({ body: { contains: r } })),
      ],
    },
  });
  console.log(`   removed ${delSms.count} SMS row(s)`);

  for (const id of s.requestIds) await db.paymentRequest.deleteMany({ where: { id } });
  console.log(`   removed ${s.requestIds.length} payment request(s)`);

  for (const p of payments) await db.payment.delete({ where: { id: p.id } }).catch(() => {});
  console.log(`   removed ${payments.length} payment row(s)`);

  const wtxns = await db.walletTxn.findMany({ where: { studentId: s.wallet.studentId, OR: s.wallet.refs.map((r) => ({ note: { contains: r } })) } });
  for (const t of wtxns) await db.walletTxn.delete({ where: { id: t.id } });
  if (wtxns.length) {
    const total = wtxns.reduce((x, t) => x + Math.max(0, t.amountCents), 0);
    const w = await db.studentWallet.findUnique({ where: { studentId: s.wallet.studentId } });
    // clamp at the pre-smoke balance in case anything else touched it
    const target = Math.max(s.wallet.before, (w?.prepaidCents ?? 0) - total);
    await db.studentWallet.update({ where: { studentId: s.wallet.studentId }, data: { prepaidCents: target } });
  }
  console.log(`   removed ${wtxns.length} wallet txn(s), wallet restored to ${s.wallet.before}`);

  const feesPay = payments.find((p) => p.invoiceId === s.invoice.id);
  if (feesPay) {
    const inv = await db.invoice.findUnique({ where: { id: s.invoice.id } });
    const paid = Math.max(0, inv.paidCents - feesPay.amountCents);
    const balance = Math.max(0, inv.totalCents - inv.discountCents - paid);
    const status = balance === 0 ? "PAID" : paid > 0 ? "PARTIAL" : "UNPAID";
    await db.invoice.update({ where: { id: s.invoice.id }, data: { paidCents: paid, status } });
    console.log(`   invoice ${s.invoice.invoiceNo} restored to paid=${paid} (${status})`);
  }

  await db.shopOrder.deleteMany({ where: { id: s.shop.orderId } });
  await db.shopItem.update({ where: { id: s.shop.itemId }, data: { stock: s.shop.stockBefore } });
  console.log(`   removed order ${s.shop.orderNo}, stock restored to ${s.shop.stockBefore}`);

  console.log("cleanup complete — providers smoke state fully restored");
  process.exit(0);
})().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

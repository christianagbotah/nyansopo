/* eslint-disable @typescript-eslint/no-require-imports */
// ── wave 77 verify + cleanup: check the shop smoke left the right ledger
//    traces in the DB (shop-category receipts, guardian SMS trail, wallet
//    txn, stock drawn, invoice delta), then restore everything to the
//    pre-smoke state.
//    usage: bun scripts/cleanup-shop-smoke.ts /tmp/smoke-shop-state.json --verify
const { PrismaClient } = require("@prisma/client");
const db = new PrismaClient();

(async () => {
  const statePath = process.argv[2];
  const doVerify = process.argv.includes("--verify");
  if (!statePath) { console.error("usage: cleanup-shop-smoke.ts <state.json> [--verify]"); process.exit(1); }
  const s = JSON.parse(require("fs").readFileSync(statePath, "utf8"));
  const refs = [s.wallet.refs[0], ...(s.invoice?.refs ?? []), ...(s.shopRefs ?? [])]; // online request references
  let bad = 0;
  const check = (name, cond, detail) => {
    console.log(`   ${cond ? "ok" : "XX"} ${name}${detail ? ` — ${detail}` : ""}`);
    if (!cond) bad++;
  };

  // ── collect the money rows BEFORE deleting anything ──
  const payments = await db.payment.findMany({
    where: {
      schoolId: s.schoolId,
      OR: [
        ...refs.map((r) => ({ reference: r })),
        ...s.orderNos.map((no) => ({ reference: { contains: no } })),
      ],
    },
  });
  const receiptNos = [...new Set([...payments.map((p) => p.receiptNo), ...s.receiptNos])];

  // ── verification (pre-cleanup) ──
  if (doVerify) {
    // 1. three payments: shop-online, shop-counter, wallet topup, invoice partial = 4 rows
    check("money rows", payments.length === 4, `${payments.length} Payment rows (2 shop + 1 wallet + 1 fees) — ${payments.map((p) => p.receiptNo).join(", ")}`);
    check("receipt numbers", payments.every((p) => /^RCP-\d{4}-\d{5}$/.test(p.receiptNo)), "RCP-YYYY-NNNNN shape");

    // 2. guardian SMS trail: paid (online + counter), packed, delivered
    const smsO1 = await db.smsMessage.count({ where: { schoolId: s.schoolId, category: "SHOP", body: { contains: s.orderNos[0] } } });
    const smsO2 = await db.smsMessage.count({ where: { schoolId: s.schoolId, category: "SHOP", body: { contains: s.orderNos[1] } } });
    check("order SMS trail", smsO1 >= 1 && smsO2 >= 3, `order1=${smsO1} (paid) · order2=${smsO2} (paid/packed/delivered)`);

    // 3. payment receipt automation SMS name the receipt numbers — three rows:
    //    shop-online + wallet + fees (the COUNTER receipt is receipted by the
    //    shop order SMS itself, not the MoMo automation)
    const smsPay = await db.smsMessage.count({ where: { schoolId: s.schoolId, category: "PAYMENT", OR: receiptNos.map((r) => ({ body: { contains: r } })) } });
    check("receipt SMS", smsPay >= 3, `${smsPay} PAYMENT rows naming our receipts`);

    // 4. wallet ledger moved
    const wt = await db.walletTxn.findFirst({ where: { studentId: s.wallet.studentId, kind: "TOPUP", note: { contains: s.wallet.refs[0] } } });
    check("wallet txn row", !!wt, wt ? `+${wt.amountCents} pesewas · balance after ${wt.balanceAfter}` : "missing");

    // 5. stock drawn = 3 (2 online + 1 counter)
    const item = await db.shopItem.findUnique({ where: { id: s.itemIds[0] } });
    check("stock drawn", item && item.stock === 7, `stock=${item?.stock} (10 − 3)`);

    if (bad) { console.error(`VERIFY FAILED (${bad} check(s)) — aborting cleanup so the state can be inspected`); process.exit(1); }
    console.log("   verify: all ledger checks green");
  }

  // ── cleanup (order matters: SMS → requests → payments → wallet → invoice → orders → items) ──
  const delSms = await db.smsMessage.deleteMany({
    where: {
      schoolId: s.schoolId,
      OR: [
        ...s.orderNos.map((no) => ({ body: { contains: no } })),
        ...receiptNos.map((r) => ({ body: { contains: r } })),
      ],
    },
  });
  console.log(`   removed ${delSms.count} SMS row(s)`);

  for (const id of s.requestIds) await db.paymentRequest.deleteMany({ where: { id } });
  console.log(`   removed ${s.requestIds.length} payment request(s)`);

  for (const p of payments) await db.payment.delete({ where: { id: p.id } }).catch(() => {});
  console.log(`   removed ${payments.length} payment row(s)`);

  const wtxns = await db.walletTxn.findMany({ where: { studentId: s.wallet.studentId, note: { contains: s.wallet.refs[0] } } });
  for (const t of wtxns) await db.walletTxn.delete({ where: { id: t.id } });
  if (wtxns.length) {
    const total = wtxns.reduce((x, t) => x + Math.max(0, t.amountCents), 0);
    await db.studentWallet.update({
      where: { studentId: s.wallet.studentId },
      data: s.wallet.kind === "TRANSPORT" ? { transportCents: { decrement: total } } : { prepaidCents: { decrement: total } },
    });
  }
  console.log(`   removed ${wtxns.length} wallet txn(s), wallet balance restored`);

  if (s.invoice?.id) {
    await db.invoice.update({ where: { id: s.invoice.id }, data: { paidCents: s.invoice.origPaidCents, status: s.invoice.origStatus } });
    console.log(`   invoice restored to paid=${s.invoice.origPaidCents} (${s.invoice.origStatus})`);
  }

  for (const id of s.orderIds) await db.shopOrder.deleteMany({ where: { id } });
  for (const id of s.itemIds) await db.shopItem.deleteMany({ where: { id } });
  console.log(`   removed ${s.orderIds.length} order(s), ${s.itemIds.length} item(s)`);

  console.log("cleanup complete — shop smoke state fully restored");
  process.exit(0);
})().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

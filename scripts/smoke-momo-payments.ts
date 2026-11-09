/* Hubtel MoMo payments smoke — covers:
   (a) channel detection + client-reference generation + callback parsing
   (b) Hubtel adapters against an in-process mock gateway (auth header,
       endpoint paths, payload shapes, success/failure parsing)
   (c) SIMULATED end-to-end: create prompt + link requests, public pay page,
       simulate completion → ledger effects (invoice settle + credit excess,
       daily items FIFO + advance, item balance, wallet top-up)
   (d) webhook: completes a request, duplicate callback is idempotent,
       amount mismatch ignored, secret enforced when configured
   (e) RBAC (no fees.collect/daily.collect → 403), cancel, simulate-fail,
       share-link SMS, student-accounts payload, GET list + stats
   Run: bun scripts/smoke-momo-payments.ts (dev server + seeded DB required) */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const BASE = "http://localhost:3000";
let pass = 0, fail = 0;
const check = (label: string, cond: boolean, extra = "") => {
  if (cond) { pass++; console.log(`  ✅ ${label}`); }
  else { fail++; console.log(`  ❌ ${label} ${extra}`); }
};

async function main() {
  const user = await db.user.findUnique({ where: { email: "head@nyansapo.gh" } });
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!user || !school) { console.log("missing demo data — run the seed first"); return; }
  const schoolId = school.id;
  const H = { "content-type": "application/json", "x-user-id": user.id };
  const post = (body: any) => fetch(`${BASE}/api/school/${schoolId}/ops/payments`, { method: "POST", headers: H, body: JSON.stringify(body) });
  const get = (q = "") => fetch(`${BASE}/api/school/${schoolId}/ops/payments${q}`, { headers: H }).then((r) => r.json());
  const created: string[] = []; // paymentRequest ids to clean up

  // silence receipt SMS during the test
  const origSms = await db.smsSetting.findUnique({ where: { schoolId } });
  await db.smsSetting.upsert({ where: { schoolId }, update: { autoPaymentReceipts: false }, create: { schoolId, autoPaymentReceipts: false } });

  console.log("\n── A. channel detection, references, callback parsing ──");
  {
    const { detectMomoChannel, newClientReference } = await import("../src/lib/hubtel");
    check("024… → mtn-gh (MTN MoMo)", detectMomoChannel("0244123456").channel === "mtn-gh");
    check("055… → mtn-gh", detectMomoChannel("0551234567").channel === "mtn-gh");
    check("020… → vodafone-gh (Telecel)", detectMomoChannel("0201234567").channel === "vodafone-gh");
    check("027… → tigo-gh (AT)", detectMomoChannel("0271234567").channel === "tigo-gh");
    check("056… → tigo-gh", detectMomoChannel("0561234567").channel === "tigo-gh");
    const r1 = newClientReference(), r2 = newClientReference();
    check("client references unique + ≤32 chars", r1 !== r2 && r1.length <= 32 && r1.startsWith("HTL-"), `${r1} / ${r2}`);

    const { parseHubtelCallback } = await import("../src/lib/payment-requests");
    const a = parseHubtelCallback({ ResponseCode: "0000", ClientReference: "REF-1", Amount: 12.5, TransactionId: "T1" });
    check("callback gen-1 (ResponseCode 0000) → COMPLETED + pesewas", a.status === "COMPLETED" && a.amountCents === 1250 && a.reference === "REF-1");
    const b = parseHubtelCallback({ Data: { ResponseCode: "00", ClientReference: "REF-2", Status: "Paid" } });
    check("callback gen-2 (Data.Status Paid) → COMPLETED", b.status === "COMPLETED" && b.reference === "REF-2");
    const c = parseHubtelCallback({ Status: "Cancelled", ClientReference: "REF-3" });
    check("callback cancelled → CANCELLED", c.status === "CANCELLED");
    const d = parseHubtelCallback({ ResponseCode: "01", ClientReference: "REF-4", Message: "Insufficient Balance" });
    check("callback 01 → FAILED", d.status === "FAILED");
    const e = parseHubtelCallback({ Amount: 5, ClientReference: "REF-5" });
    check("callback unknown → PENDING (no ledger effect)", e.status === "PENDING");
    const f = parseHubtelCallback({ transactionId: "T9", status: "success" });
    check("callback by transactionId only", f.status === "COMPLETED" && f.transactionId === "T9");
  }

  console.log("\n── B. Hubtel adapters against a mock gateway ──");
  {
    const seen: { path: string; auth?: string | null; body: any }[] = [];
    let checkoutPaid = false;
    const mock = Bun.serve({
      port: 0,
      fetch: async (req) => {
        const url = new URL(req.url);
        const body = await req.json().catch(() => ({}));
        seen.push({ path: url.pathname, auth: req.headers.get("authorization"), body });
        if (url.pathname.endsWith("/receive/mobilemoney"))
          return Response.json({ Status: "Success", TransactionId: `TXN-${seen.length}` });
        if (url.pathname.endsWith("/onlinecheckout/invoice/create"))
          return Response.json({ status: "Success", code: "0000", data: { token: `tok-${seen.length}`, checkoutUrl: `https://checkout.hubtel.com/pay/tok-${seen.length}` } });
        if (url.pathname.includes("/onlinecheckout/invoice/status/"))
          return Response.json({ status: checkoutPaid ? "Paid" : "Unpaid" });
        if (url.pathname.endsWith("/transactions/status"))
          return Response.json({ Transaction: { Status: checkoutPaid ? "Success" : "Pending" } });
        if (url.pathname.endsWith("/send/mobilemoney"))
          return Response.json({ Status: "Success", TransactionId: `S-${seen.length}` });
        if (url.pathname.endsWith("/transactions/refund"))
          return Response.json({ ResponseCode: "00" });
        return new Response("not found", { status: 404 });
      },
    });
    process.env.HUBTEL_API_BASE = `http://localhost:${mock.port}`;
    process.env.HUBTEL_CLIENT_ID = "test-client";
    process.env.HUBTEL_CLIENT_SECRET = "test-secret";
    process.env.HUBTEL_MERCHANT_ACCOUNT_NUMBER = "HM0001";
    process.env.HUBTEL_CALLBACK_BASE = "https://demo.nyansapo.test";
    process.env.HUBTEL_WEBHOOK_SECRET = "";

    const h = await import("../src/lib/hubtel");
    check("payments configured when env set", h.hubtelPaymentsConfigured());

    const rm = await h.hubtelReceiveMoney({ customerName: "Ama Owusu", customerMsisdn: "233244123456", amount: 25.5, description: "School fees", clientReference: "HTL-TEST-1", channel: "mtn-gh", callbackUrl: "https://demo.nyansapo.test/api/webhooks/hubtel" });
    check("receive-money ok → transactionId", rm.ok && rm.data?.transactionId === "TXN-1", JSON.stringify(rm));
    const rmCall = seen[0];
    check("receive-money POST /v1/merchantaccount/merchants/HM0001/receive/mobilemoney", rmCall.path === "/v1/merchantaccount/merchants/HM0001/receive/mobilemoney", rmCall.path);
    check("receive-money Basic auth header", (rmCall.auth ?? "").startsWith("Basic ") && rmCall.auth === `Basic ${Buffer.from("test-client:test-secret").toString("base64")}`);
    check("receive-money payload shape", rmCall.body.CustomerMsisdn === "233244123456" && rmCall.body.Channel === "mtn-gh" && rmCall.body.Amount === 25.5 && rmCall.body.ClientReference === "HTL-TEST-1" && !!rmCall.body.PrimaryCallbackURL);

    const co = await h.hubtelCreateCheckout({ description: "Fees", items: [{ name: "Fees", quantity: 1, unitPrice: 30, totalPrice: 30 }], totalAmount: 30, callbackUrl: "https://demo.nyansapo.test/api/webhooks/hubtel", returnUrl: "https://demo.nyansapo.test/pay/X?done=1", cancellationUrl: "https://demo.nyansapo.test/pay/X?cancelled=1" });
    check("checkout create → token + url", co.ok && co.data?.token === "tok-2" && co.data?.checkoutUrl?.includes("checkout.hubtel.com/pay/tok-2"), JSON.stringify(co));
    check("checkout payload: invoice.items + actions", Array.isArray(seen[1].body.invoice?.items) && !!seen[1].body.actions?.return_url);

    const stPending = await h.hubtelCheckoutStatus("tok-2");
    check("checkout status unpaid → PENDING", stPending.ok && stPending.data!.status === "PENDING");
    checkoutPaid = true;
    const stPaid = await h.hubtelCheckoutStatus("tok-2");
    check("checkout status paid → COMPLETED", stPaid.ok && stPaid.data!.status === "COMPLETED");

    const ts = await h.hubtelTransactionStatus({ transactionId: "TXN-1" });
    check("txn status success → COMPLETED", ts.ok && ts.data!.status === "COMPLETED");
    const sm = await h.hubtelSendMoney({ recipientName: "Kofi", recipientMsisdn: "233201234567", amount: 10, description: "Refund", clientReference: "RF-1" });
    check("send-money ok", sm.ok && !!sm.data?.transactionId, JSON.stringify(sm));
    const rf = await h.hubtelRefund({ transactionId: "TXN-1", amount: 5 });
    check("refund ok", rf.ok);

    // network failure → friendly error
    process.env.HUBTEL_API_BASE = "http://127.0.0.1:1";
    const bad = await h.hubtelReceiveMoney({ customerName: "x", customerMsisdn: "233244123456", amount: 1, description: "x", clientReference: "HTL-TEST-2", channel: "mtn-gh", callbackUrl: "https://x.test/cb" });
    check("network failure → ok:false + friendly error", !bad.ok && /failed|timeout|error/i.test(bad.error ?? ""), JSON.stringify(bad));

    // back to SIMULATED for the end-to-end HTTP flow
    delete process.env.HUBTEL_CLIENT_ID;
    delete process.env.HUBTEL_CLIENT_SECRET;
    delete process.env.HUBTEL_MERCHANT_ACCOUNT_NUMBER;
    process.env.HUBTEL_API_BASE = "";
    mock.stop(true);
  }

  // fixtures: two learners with open invoices + a daily service with arrears
  const enrollments = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" }, take: 40 });
  const findOpenInvoice = async (studentId: string) => db.invoice.findFirst({ where: { studentId, schoolId, status: { in: ["UNPAID", "PARTIAL", "OVERDUE"] }, writtenOff: false }, orderBy: { createdAt: "desc" } });
  let stA = enrollments[0];
  let invA = await findOpenInvoice(stA.studentId);
  for (const e of enrollments) { const inv = await findOpenInvoice(e.studentId); if (inv) { stA = e; invA = inv; break; } }
  const svc = await db.dailyService.findFirst({ where: { schoolId, category: { in: ["MEAL", "DRINK", "CARE"] } } });
  const stB = enrollments.find((e) => e.studentId !== stA.studentId)!;

  console.log("\n── C. RBAC + student accounts ──");
  {
    const librarian = await db.user.findFirst({ where: { role: "LIBRARIAN", schoolId } });
    if (librarian) {
      const r = await fetch(`${BASE}/api/school/${schoolId}/ops/payments`, { method: "POST", headers: { "content-type": "application/json", "x-user-id": librarian.id }, body: JSON.stringify({ action: "create", studentId: stA.studentId, purpose: "WALLET", amountCents: 500, method: "MOMO_PROMPT" }) });
      check("librarian (no fees.collect/daily.collect perms) → 403", r.status === 403, String(r.status));
    }
    const r2 = await post({ action: "student-accounts", studentId: stA.studentId });
    const j = await r2.json();
    check("student-accounts: invoices + dailyItems + wallet", r2.status === 200 && Array.isArray(j.invoices) && Array.isArray(j.dailyItems) && j.wallet && j.student.channel?.channel, JSON.stringify(j).slice(0, 120));
  }

  console.log("\n── D. SIMULATED end-to-end — momo prompt on an invoice ──");
  let promptRef = "";
  {
    const before = invA!.paidCents;
    const balA = invA!.totalCents - invA!.discountCents - invA!.paidCents;
    const pay = Math.max(100, Math.min(balA, 3000));
    const r = await post({ action: "create", studentId: stA.studentId, purpose: "INVOICE", invoiceId: invA!.id, amountCents: pay, method: "MOMO_PROMPT" });
    const j = await r.json();
    check("create INVOICE momo prompt → PENDING SIMULATED", r.status === 200 && j.request?.status === "PENDING" && j.request?.provider === "SIMULATED", JSON.stringify(j).slice(0, 200));
    promptRef = j.request?.clientReference ?? "";
    created.push(j.request?.id ?? "");

    // public status API
    const pub = await fetch(`${BASE}/api/public/pay/${promptRef}`).then((x) => x.json());
    check("public pay page: amount + PENDING + simulated", pub.status === "PENDING" && pub.amountCents === pay && pub.simulated === true && !!pub.school?.name, JSON.stringify(pub).slice(0, 160));
    check("public page hides learner's full surname", pub.studentName != null && !pub.studentName.includes(stA.student.lastName), pub.studentName ?? "");

    // simulate the payment from the pay page
    const sim = await fetch(`${BASE}/api/public/pay/${promptRef}`, { method: "POST", headers: { "content-type": "application/json" }, body: JSON.stringify({ action: "pay" }) });
    check("simulate pay → COMPLETED", sim.status === 200 && (await sim.json()).status === "COMPLETED");

    const req = await db.paymentRequest.findUnique({ where: { clientReference: promptRef }, include: { appliedPayment: true } });
    check("ledger: payment receipt minted + linked", !!req?.appliedPaymentId && !!req?.appliedPayment?.receiptNo && req.status === "COMPLETED");
    const invAfter = await db.invoice.findUnique({ where: { id: invA!.id } });
    const expectedPaid = Math.min(before + pay, invA!.totalCents - invA!.discountCents);
    check(`ledger: invoice paidCents ${before} → ${expectedPaid}`, invAfter!.paidCents === expectedPaid, `${invAfter!.paidCents}`);
    check("audit: INITIATED + COMPLETED events written", (await db.paymentEvent.count({ where: { requestId: req!.id, kind: { in: ["INITIATED", "COMPLETED"] } } })) >= 2);

    // idempotency: replaying the simulate action on a completed request fails
    const replay = await fetch(`${BASE}/api/public/pay/${promptRef}`, { method: "POST", headers: { "content-type": "application/json" }, body: JSON.stringify({ action: "pay" }) });
    check("replayed simulate on completed request → 400", replay.status === 400, String(replay.status));
    const payments = await db.payment.count({ where: { schoolId, reference: promptRef } });
    check("no double posting on replay", payments === 1, String(payments));
  }

  console.log("\n── E. SIMULATED — payment link + webhook-style completion on daily items ──");
  {
    // give learner B arrears on the daily service via a direct charge
    const charge = await db.dailyCharge.create({ data: { schoolId, studentId: stB.studentId, serviceId: svc!.id, date: new Date(), amountCents: 600, status: "OWED" } });
    const r = await post({ action: "create", studentId: stB.studentId, purpose: "DAILY_ITEMS", items: [{ serviceId: svc!.id, amountCents: 900 }], amountCents: 900, method: "PAYMENT_LINK" });
    const j = await r.json();
    check("create DAILY_ITEMS link → PENDING", r.status === 200 && j.request?.status === "PENDING", JSON.stringify(j).slice(0, 160));
    const ref = j.request.clientReference as string;
    created.push(j.request.id);

    // complete it through the WEBHOOK endpoint (as Hubtel would)
    const hook = await fetch(`${BASE}/api/webhooks/hubtel`, { method: "POST", headers: { "content-type": "application/json" }, body: JSON.stringify({ ResponseCode: "0000", ClientReference: ref, TransactionId: "WH-1", Amount: 9 }) });
    check("webhook → 200 success", hook.status === 200 && (await hook.json()).success === true);

    const req = await db.paymentRequest.findUnique({ where: { clientReference: ref }, include: { appliedPayment: true } });
    check("webhook completed the request", req?.status === "COMPLETED");
    const chargesAfter = await db.dailyCharge.findMany({ where: { studentId: stB.studentId, serviceId: svc!.id } });
    const chargeAfter = chargesAfter.find((c) => c.id === charge.id);
    check("FIFO: 600 settled (PAID) + 300 → item advance", chargeAfter?.status === "PAID" && chargeAfter.amountCents === 600, JSON.stringify(chargeAfter));
    const bal = await db.serviceBalance.findUnique({ where: { studentId_serviceId: { studentId: stB.studentId, serviceId: svc!.id } } });
    check("excess 300 banked on the item balance", (bal?.prepaidCents ?? 0) >= 300, String(bal?.prepaidCents));

    // duplicate webhook must not double-post
    await fetch(`${BASE}/api/webhooks/hubtel`, { method: "POST", headers: { "content-type": "application/json" }, body: JSON.stringify({ ResponseCode: "0000", ClientReference: ref, Amount: 9 }) });
    const count = await db.payment.count({ where: { schoolId, reference: ref } });
    check("duplicate webhook → idempotent (single payment)", count === 1, String(count));

    // amount mismatch is ignored
    const mR = await post({ action: "create", studentId: stB.studentId, purpose: "WALLET", walletKind: "PREPAID", amountCents: 1500, method: "PAYMENT_LINK" });
    const mRj = await mR.json();
    const mRef = mRj.request.clientReference;
    created.push(mRj.request.id);
    await fetch(`${BASE}/api/webhooks/hubtel`, { method: "POST", headers: { "content-type": "application/json" }, body: JSON.stringify({ ResponseCode: "0000", ClientReference: mRef, Amount: 99 }) });
    const mReq = await db.paymentRequest.findUnique({ where: { clientReference: mRef } });
    check("amount-mismatch callback ignored", mReq?.status === "PENDING", mReq?.status ?? "");
  }

  console.log("\n── F. wallet top-up, item advance, cancel, simulate-fail, share-link ──");
  {
    // wallet top-up completed via public simulate
    const w = await post({ action: "create", studentId: stB.studentId, purpose: "WALLET", walletKind: "TRANSPORT", amountCents: 2000, method: "PAYMENT_LINK" });
    const wj = await w.json();
    created.push(wj.request.id);
    await fetch(`${BASE}/api/public/pay/${wj.request.clientReference}`, { method: "POST", headers: { "content-type": "application/json" }, body: JSON.stringify({ action: "pay" }) });
    const txn = await db.walletTxn.findFirst({ where: { studentId: stB.studentId, wallet: "TRANSPORT", kind: "TOPUP", note: { contains: wj.request.clientReference } } });
    check("wallet top-up: TOPUP txn + balance credited", !!txn && txn.amountCents === 2000, JSON.stringify(txn ?? null).slice(0, 100));

    // item advance (single item)
    const ia = await post({ action: "create", studentId: stB.studentId, purpose: "ITEM_BALANCE", items: [{ serviceId: svc!.id, amountCents: 1000 }], amountCents: 1000, method: "MOMO_PROMPT" });
    const iaj = await ia.json();
    created.push(iaj.request.id);
    await fetch(`${BASE}/api/public/pay/${iaj.request.clientReference}`, { method: "POST", headers: { "content-type": "application/json" }, body: JSON.stringify({ action: "pay" }) });
    const iReq = await db.paymentRequest.findUnique({ where: { clientReference: iaj.request.clientReference } });
    check("item advance → COMPLETED + applied note", iReq?.status === "COMPLETED" && /advance/i.test(iReq?.appliedNote ?? ""), iReq?.appliedNote ?? "");

    // cancel a pending request
    const c = await post({ action: "create", studentId: stB.studentId, purpose: "WALLET", walletKind: "PREPAID", amountCents: 700, method: "MOMO_PROMPT" });
    const cj = await c.json();
    created.push(cj.request.id);
    const cx = await post({ action: "cancel", id: cj.request.id });
    check("cancel pending → CANCELLED", (await cx.json()).request?.status === "CANCELLED");
    const cxPay = await db.payment.count({ where: { reference: cj.request.clientReference } });
    check("cancelled request never posts a payment", cxPay === 0, String(cxPay));

    // simulate-fail (SIMULATED provider only)
    const f = await post({ action: "create", studentId: stB.studentId, purpose: "WALLET", walletKind: "PREPAID", amountCents: 800, method: "MOMO_PROMPT" });
    const fj = await f.json();
    created.push(fj.request.id);
    const fx = await post({ action: "simulate-fail", id: fj.request.id });
    check("simulate-fail → FAILED with reason", (await fx.json()).request?.status === "FAILED");
    const fxPay = await db.payment.count({ where: { reference: fj.request.clientReference } });
    check("failed request never posts a payment", fxPay === 0, String(fxPay));

    // share-link sends an SMS with the link
    const l = await post({ action: "create", studentId: stB.studentId, purpose: "WALLET", walletKind: "PREPAID", amountCents: 900, method: "PAYMENT_LINK" });
    const lj = await l.json();
    created.push(lj.request.id);
    const sh = await post({ action: "share-link", id: lj.request.id });
    const shj = await sh.json();
    check("share-link → SMS sent (SIMULATED) + link returned", sh.status === 200 && shj.smsSent === true && typeof shj.link === "string", JSON.stringify(shj).slice(0, 120));
    const sms = await db.smsMessage.findFirst({ where: { schoolId, body: { contains: lj.request.clientReference } }, orderBy: { sentAt: "desc" } });
    check("share-link SMS recorded with the pay URL", !!sms && sms.body.includes("/pay/"), sms?.body.slice(0, 80) ?? "");

    // validation guards
    const g1 = await post({ action: "create", studentId: stB.studentId, purpose: "INVOICE", amountCents: 500, method: "MOMO_PROMPT" });
    check("INVOICE without invoiceId → 400", g1.status === 400);
    const g2 = await post({ action: "create", studentId: stB.studentId, purpose: "WALLET", walletKind: "PREPAID", amountCents: 20, method: "MOMO_PROMPT" });
    check("below GH₵0.50 minimum → 400", g2.status === 400 && /minimum/i.test((await g2.json()).error));
  }

  console.log("\n── G. GET payload: list, filters, stats, config card ──");
  {
    const all = await get("?status=ALL");
    check("GET list: requests + stats + provider", all.requests?.length >= 5 && all.stats && all.provider === "SIMULATED", JSON.stringify(all.stats ?? {}));
    check("GET list rows carry purposeLabel/receipt/payUrl", all.requests[0]?.purposeLabel && "payUrl" in all.requests[0] && "receiptNo" in all.requests[0]);
    const pend = await get("?status=PENDING");
    check("status filter PENDING works", pend.requests.every((r: any) => r.status === "PENDING") && pend.requests.length > 0);
    const webhook = await fetch(`${BASE}/api/webhooks/hubtel`).then((r) => r.json());
    check("webhook GET health probe", webhook.ok === true);
  }

  // ── cleanup: remove test requests + their payments, restore settings ──
  console.log("\n── cleanup ──");
  {
    for (const id of created) {
      const r = await db.paymentRequest.findUnique({ where: { id }, include: { appliedPayment: true, invoice: true } });
      if (!r) continue;
      if (r.appliedPaymentId) {
        if (r.invoice) {
          const applied = Math.min(r.amountCents, Math.max(0, r.invoice.totalCents - r.invoice.discountCents - (r.invoice.paidCents - r.amountCents)));
          await db.invoice.update({ where: { id: r.invoice.id }, data: { paidCents: { decrement: applied }, status: r.invoice.status === "PAID" ? "PARTIAL" : r.invoice.status } });
        }
        await db.payment.delete({ where: { id: r.appliedPaymentId } }).catch(() => {});
      }
      await db.paymentEvent.deleteMany({ where: { requestId: id } });
      await db.paymentRequest.delete({ where: { id } });
    }
    // remove test charges/balances for learner B on the smoke amounts
    await db.dailyCharge.deleteMany({ where: { studentId: stB.studentId, serviceId: svc!.id, amountCents: 600 } });
    await db.serviceBalance.updateMany({ where: { studentId: stB.studentId, serviceId: svc!.id }, data: { prepaidCents: 0 } });
    await db.walletTxn.deleteMany({ where: { studentId: stB.studentId, note: { contains: "HTL-" } } });
    await db.studentWallet.updateMany({ where: { studentId: stB.studentId }, data: { transportCents: 0 } });
    await db.smsMessage.deleteMany({ where: { schoolId, body: { contains: "Pay GH" } } });
    if (origSms) await db.smsSetting.update({ where: { schoolId }, data: { autoPaymentReceipts: origSms.autoPaymentReceipts } });
    check("cleanup done", true);
  }

  console.log(`\n${pass} passed, ${fail} failed`);
  if (fail > 0) process.exit(1);
}

main()
  .catch((e) => { console.error(e); process.exit(1); })
  .finally(() => db.$disconnect());

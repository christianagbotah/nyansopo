/* Offline sync × per-item daily collection (wave 5) — the POS dialog queues
   `items[]` per fee item; /api/sync must replay them through the SAME core as
   the online counter: per-item FIFO settlement, excess → item advance,
   payment_type arrears/advance/mixed, transport usage + mark-present replay,
   auto receipt SMS at flush, idempotent re-flush, legacy single-amount fallback.
   Run: bun scripts/smoke-daily-sync.ts (dev server + seeded DB required) */
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
  const testStart = new Date();

  // one learner with an active enrolment (classroom needed for mark-present)
  const en = await db.enrollment.findFirstOrThrow({ where: { schoolId, status: "ACTIVE" }, include: { student: true }, orderBy: { studentId: "asc" } });
  const st = en.studentId;
  console.log(`fixture: ${en.student.firstName} (${en.classroomId})`);

  // silence receipt SMS during the numeric sections (restored in cleanup)
  const origSms = await db.smsSetting.findUnique({ where: { schoolId } });
  await db.smsSetting.update({ where: { schoolId }, data: { autoPaymentReceipts: false } });

  // three test services + direct OWED charges (older dates → FIFO determinism)
  const tag = Date.now();
  const svcMeal = await db.dailyService.create({ data: { schoolId, name: `SYNCSMK Meal ${tag}`, category: "MEAL", priceCents: 300, chargeMode: "DAILY", weekdays: "1,2,3,4,5" } });
  const svcWater = await db.dailyService.create({ data: { schoolId, name: `SYNCSMK Water ${tag}`, category: "DRINK", priceCents: 500, chargeMode: "WEEKLY", weekdays: "1" } });
  const svcSnack = await db.dailyService.create({ data: { schoolId, name: `SYNCSMK Snack ${tag}`, category: "MEAL", priceCents: 200, chargeMode: "DAILY", weekdays: "1,2,3,4,5" } });
  const svcIds = [svcMeal.id, svcWater.id, svcSnack.id];
  const d2 = new Date(Date.now() - 2 * 864e5); d2.setHours(0, 0, 0, 0);
  const d1 = new Date(Date.now() - 1 * 864e5); d1.setHours(0, 0, 0, 0);
  await db.dailyCharge.create({ data: { schoolId, studentId: st, serviceId: svcMeal.id, date: d2, amountCents: 300, status: "OWED" } });
  await db.dailyCharge.create({ data: { schoolId, studentId: st, serviceId: svcMeal.id, date: d1, amountCents: 300, status: "OWED" } });
  await db.dailyCharge.create({ data: { schoolId, studentId: st, serviceId: svcWater.id, date: d1, amountCents: 500, status: "OWED" } });

  // transport fixture: stop fare 5.00 (route default 8.00) — stop wins
  const tDay = (plus: number) => { const x = new Date(Date.now() + plus * 864e5); x.setHours(0, 0, 0, 0); return x; };
  const route = await db.transportRoute.create({ data: { schoolId, name: `SYNCSMK Route ${tag}`, vehicleReg: "SYNC-1", driverName: "Sync Driver", driverPhone: "0240000000", capacity: 10, farePerTermCents: 40000, farePerTripCents: 800, stops: "Sync Stop" } });
  const stop = await db.transportStop.create({ data: { routeId: route.id, name: "Sync Stop", fareCents: 500, sortOrder: 0 } });
  void stop;
  const asg = await db.transportAssignment.create({ data: { routeId: route.id, studentId: st, pickupStop: "Sync Stop", dropStop: "Sync Stop" } });
  const transportSvc = await db.dailyService.findFirstOrThrow({ where: { schoolId, category: "TRANSPORT" } });

  const flush = (ops: any[]) =>
    fetch(`${BASE}/api/sync`, { method: "POST", headers: H, body: JSON.stringify({ ops }) }).then((r) => r.json());
  const op = (clientId: string, payload: any) => ({ clientId, kind: "payment", payload: { category: "DAILY", schoolId, studentId: st, method: "CASH", ...payload } });
  const iso = (d: Date) => d.toISOString().slice(0, 10);
  const owedFor = async (serviceId: string) =>
    (await db.dailyCharge.findMany({ where: { studentId: st, serviceId, status: "OWED" } })).reduce((a, c) => a + c.amountCents, 0);

  console.log("\n── A. per-item flush: each item settles only its own arrears (FIFO) ──");
  {
    const res = await flush([op("syncsmk-1", { items: [{ serviceId: svcMeal.id, amountCents: 400 }], paidAt: iso(d1), date: iso(d1) })]);
    const r0 = res.results?.[0] ?? {};
    check("flush ok", r0.ok === true, JSON.stringify(r0));
    check("detail names the receipt + payment type", /daily receipt .+\(arrears\)/.test(r0.detail ?? ""), r0.detail);
    const pay = await db.payment.findFirst({ where: { reference: "SYNC:syncsmk-1" } });
    check("payment row: offlineQueued + syncedAt + (offline) by-line", !!pay && pay.offlineQueued === true && !!pay.syncedAt && /offline/.test(pay.receivedByName ?? ""), JSON.stringify({ q: pay?.offlineQueued, by: pay?.receivedByName }));
    check("receipt minted for the exact per-item total", pay?.amountCents === 400, `${pay?.amountCents}`);
    const mealRows = await db.dailyCharge.findMany({ where: { studentId: st, serviceId: svcMeal.id }, orderBy: { date: "asc" } });
    check("oldest meal charge PAID in full", mealRows[0]?.status === "PAID" && mealRows[0]?.paymentId === pay?.id, `${mealRows[0]?.status}`);
    check("newer meal charge split 100 PAID + 200 OWED", mealRows.filter((c) => c.date.getTime() === d1.getTime() && c.status === "PAID").length === 1 && mealRows.some((c) => c.date.getTime() === d1.getTime() && c.status === "OWED" && c.amountCents === 200), mealRows.filter((c) => c.date.getTime() === d1.getTime()).map((c) => `${c.amountCents}/${c.status}`).join(","));
    check("meal arrears now 2.00, water arrears untouched at 5.00 (isolation)", (await owedFor(svcMeal.id)) === 200 && (await owedFor(svcWater.id)) === 500, `${await owedFor(svcMeal.id)}/${await owedFor(svcWater.id)}`);
    const audit = await db.dailyFeeAudit.findFirst({ where: { studentId: st, actionType: "PAYMENT", createdAt: { gte: testStart } }, orderBy: { createdAt: "desc" } });
    // the learner also carries seeded arrears — assert the DELTA, not absolute totals
    check("audit row written with the breakdown note", !!audit && /SYNCSMK Meal/.test(audit.notes ?? "") && audit!.arrearsBeforeCents - audit!.arrearsAfterCents === 400, JSON.stringify({ b: audit?.arrearsBeforeCents, a: audit?.arrearsAfterCents }));
  }

  console.log("\n── B. excess banks on the item as advance (mixed) ──");
  {
    const res = await flush([op("syncsmk-2", { items: [{ serviceId: svcMeal.id, amountCents: 900 }], paidAt: iso(d1), date: iso(d1) })]);
    check("mixed payment type in the detail", /\(mixed\)/.test(res.results?.[0]?.detail ?? ""), res.results?.[0]?.detail);
    check("meal arrears cleared to 0", (await owedFor(svcMeal.id)) === 0);
    const bal = await db.serviceBalance.findUnique({ where: { studentId_serviceId: { studentId: st, serviceId: svcMeal.id } } });
    check("700 excess landed on the meal item balance", bal?.prepaidCents === 700, `${bal?.prepaidCents}`);
    const txn = await db.walletTxn.findFirst({ where: { studentId: st, wallet: "ITEM", serviceId: svcMeal.id, kind: "TOPUP" }, orderBy: { at: "desc" } });
    check("ITEM TOPUP txn notes the receipt (advance — RCT)", txn?.amountCents === 700 && /advance/.test(txn?.note ?? ""), JSON.stringify(txn?.note));
    const audit = await db.dailyFeeAudit.findFirst({ where: { studentId: st, actionType: "PAYMENT", createdAt: { gte: testStart } }, orderBy: { createdAt: "desc" } });
    check("audit notes the advance (+GH₵7.00)", /\(\+GH₵7\.00 advance\)/.test(audit?.notes ?? ""), audit?.notes ?? "");
  }

  console.log("\n── C. pure advance on an item with no charges ──");
  {
    const res = await flush([op("syncsmk-3", { items: [{ serviceId: svcSnack.id, amountCents: 250 }], paidAt: iso(d1), date: iso(d1) })]);
    check("advance payment type in the detail", /\(advance\)/.test(res.results?.[0]?.detail ?? ""), res.results?.[0]?.detail);
    const bal = await db.serviceBalance.findUnique({ where: { studentId_serviceId: { studentId: st, serviceId: svcSnack.id } } });
    check("snack item balance = 2.50", bal?.prepaidCents === 250, `${bal?.prepaidCents}`);
    check("no OWED rows created by an advance", (await owedFor(svcSnack.id)) === 0);
  }

  console.log("\n── D. idempotent re-flush of the same clientId ──");
  {
    const before = await db.payment.count({ where: { reference: { startsWith: "SYNC:syncsmk-" } } });
    const res = await flush([op("syncsmk-1", { items: [{ serviceId: svcMeal.id, amountCents: 400 }], paidAt: iso(d1), date: iso(d1) })]);
    check("re-flush reports already synced", res.results?.[0]?.detail === "already synced", res.results?.[0]?.detail);
    const after = await db.payment.count({ where: { reference: { startsWith: "SYNC:syncsmk-" } } });
    check("no duplicate payment minted", before === after, `${before}→${after}`);
  }

  console.log("\n── E. transport usage replay: BOTH materialises the day's stop fare ──");
  {
    const D1 = tDay(40);
    const res = await flush([op("syncsmk-4", { transportUsage: "BOTH", items: [{ serviceId: transportSvc.id, amountCents: 1000 }], paidAt: iso(D1), date: iso(D1) })]);
    check("flush ok", res.results?.[0]?.ok === true, JSON.stringify(res.results?.[0]));
    const tCharge = await db.dailyCharge.findFirst({ where: { studentId: st, date: D1, service: { category: "TRANSPORT" } } });
    check("charge created at 2 × stop fare 5.00 = 10.00 with usage BOTH", tCharge?.amountCents === 1000 && tCharge?.usage === "BOTH", `${tCharge?.amountCents}/${tCharge?.usage}`);
    check("charge settled within the same flush (PAID + paymentId)", tCharge?.status === "PAID" && !!tCharge?.paymentId, `${tCharge?.status}`);
    const chargeAudit = await db.dailyFeeAudit.findFirst({ where: { studentId: st, actionType: "CHARGE", createdAt: { gte: testStart } }, orderBy: { createdAt: "desc" } });
    check("usage pick wrote its CHARGE audit row", /transport fare/.test(chargeAudit?.notes ?? ""), chargeAudit?.notes ?? "");
  }

  console.log("\n── F. NOT_USING waives the day's fare ──");
  {
    const D2 = tDay(41);
    const res = await flush([op("syncsmk-5", { transportUsage: "NOT_USING", items: [{ serviceId: svcSnack.id, amountCents: 100 }], paidAt: iso(D2), date: iso(D2) })]);
    check("flush ok", res.results?.[0]?.ok === true, JSON.stringify(res.results?.[0]));
    const tCharge = await db.dailyCharge.findFirst({ where: { studentId: st, date: D2, service: { category: "TRANSPORT" } } });
    check("charge WAIVED at 0.00", tCharge?.status === "WAIVED" && tCharge?.amountCents === 0, `${tCharge?.status}/${tCharge?.amountCents}`);
    check("snack advance still processed (balance 3.50)", (await db.serviceBalance.findUnique({ where: { studentId_serviceId: { studentId: st, serviceId: svcSnack.id } } }))?.prepaidCents === 350);
  }

  console.log("\n── G. mark-present replays onto the day's register ──");
  {
    const D3 = tDay(42);
    const term = await db.term.findFirstOrThrow({ where: { isCurrent: true, academicYear: { schoolId } } });
    const session = await db.attendanceSession.create({ data: { classroomId: en.classroomId, schoolId, termId: term.id, date: D3, takenByName: "SYNCSMK" } });
    const res = await flush([op("syncsmk-6", { markPresent: true, items: [{ serviceId: svcSnack.id, amountCents: 100 }], paidAt: iso(D3), date: iso(D3) })]);
    check("detail reports the present replay", /present marked/.test(res.results?.[0]?.detail ?? ""), res.results?.[0]?.detail);
    const rec = await db.attendanceRecord.findFirst({ where: { sessionId: session.id, studentId: st } });
    check("attendance record PRESENT created", rec?.status === "PRESENT", `${rec?.status}`);
    await db.attendanceRecord.deleteMany({ where: { sessionId: session.id } });
    await db.attendanceSession.delete({ where: { id: session.id } });
  }

  console.log("\n── H. legacy single-amount entries keep the old FIFO path ──");
  {
    const res = await flush([{ clientId: "syncsmk-7", kind: "payment", payload: { category: "DAILY", schoolId, studentId: st, amountCents: 200, method: "CASH", paidAt: iso(d1) } }]);
    check("legacy flush ok", res.results?.[0]?.ok === true && /daily receipt issued/.test(res.results?.[0]?.detail ?? ""), JSON.stringify(res.results?.[0]));
    const pay = await db.payment.findFirst({ where: { reference: "SYNC:syncsmk-7" } });
    check("legacy payment amount capped to 2.00", pay?.amountCents === 200, `${pay?.amountCents}`);
    // legacy settles the OLDEST open charges regardless of service (FIFO across
    // items) — with seeded arrears older than the test charges, that's the seeded
    // set, so assert on the attachment, not on any one service
    const attached = await db.dailyCharge.count({ where: { paymentId: pay?.id } });
    check("legacy FIFO attached the settlement to the oldest open charges", attached >= 1, `${attached}`);
    check("legacy payment flagged offline + synced", pay?.offlineQueued === true && !!pay?.syncedAt, `${pay?.offlineQueued}/${pay?.syncedAt}`);
  }

  console.log("\n── I. auto receipt SMS fires when the queue flushes ──");
  {
    const origPhone = en.student.guardianPhone;
    const creditsBefore = (await db.smsSetting.findUnique({ where: { schoolId } }))!.credits;
    await db.student.update({ where: { id: st }, data: { guardianPhone: "0244332211" } });
    await db.smsSetting.update({ where: { schoolId }, data: { autoPaymentReceipts: true, credits: 5 } });
    await flush([op("syncsmk-8", { items: [{ serviceId: svcSnack.id, amountCents: 100 }], paidAt: iso(d1), date: iso(d1) })]);
    const msg = await db.smsMessage.findFirst({ where: { studentId: st, category: "PAYMENT", sentAt: { gte: testStart } }, orderBy: { sentAt: "desc" } });
    check("receipt SMS recorded", !!msg && /received/.test(msg.body ?? ""), JSON.stringify(msg?.body).slice(0, 90));
    const creditsAfter = (await db.smsSetting.findUnique({ where: { schoolId } }))!.credits;
    check("SENT (Ghana number) and debited exactly 1 credit", msg?.status === "SENT" && creditsAfter === 4, `${msg?.status} ${creditsBefore}→${creditsAfter}`);
    check("sentByName carries (offline)", /\(offline\)/.test(msg?.sentByName ?? ""), msg?.sentByName ?? "");
    // restore
    await db.student.update({ where: { id: st }, data: { guardianPhone: origPhone } });
    if (msg) await db.smsMessage.delete({ where: { id: msg.id } }).catch(() => null);
    await db.smsSetting.update({ where: { schoolId }, data: { autoPaymentReceipts: false, credits: creditsBefore } });
  }

  console.log("\n── J. malformed payloads fail the op, not the flush ──");
  {
    const res = await flush([
      op("syncsmk-9", { items: [{ serviceId: "does-not-exist", amountCents: 100 }], paidAt: iso(d1), date: iso(d1) }),
      op("syncsmk-10", { items: [{ serviceId: svcSnack.id, amountCents: 0 }], paidAt: iso(d1), date: iso(d1) }),
    ]);
    check("unknown service id → op failed", res.results?.[0]?.ok === false && /does not belong/.test(res.results?.[0]?.detail ?? ""), JSON.stringify(res.results?.[0]));
    check("zero amounts filtered → op failed", res.results?.[1]?.ok === false && /at least one fee item/.test(res.results?.[1]?.detail ?? ""), JSON.stringify(res.results?.[1]));
    check("no payment rows for the failed ops", (await db.payment.count({ where: { reference: { in: ["SYNC:syncsmk-9", "SYNC:syncsmk-10"] } } })) === 0);
  }

  console.log("\n── cleanup ──");
  {
    // payments minted during the run → restore the receipt counter, then delete
    // (keyed by the SYNC reference prefix — paidAt is backdated and would miss rows)
    const pays = await db.payment.findMany({ where: { reference: { startsWith: "SYNC:syncsmk-" } }, select: { id: true, receiptNo: true } });
    for (const p of pays) {
      await db.dailyCharge.updateMany({ where: { paymentId: p.id }, data: { paymentId: null, status: "OWED" } }).catch(() => null);
      await db.payment.delete({ where: { id: p.id } }).catch(() => null);
    }
    const YEAR = new Date().getFullYear();
    const rc = await db.codeCounter.findUnique({ where: { scope: `RCP:${YEAR}` } });
    if (rc) await db.codeCounter.update({ where: { scope: `RCP:${YEAR}` }, data: { next: Math.max(0, rc.next - pays.length) } });
    // ledger rows for the test services + transport days
    await db.dailyCharge.deleteMany({ where: { serviceId: { in: svcIds } } });
    await db.dailyCharge.deleteMany({ where: { studentId: st, service: { category: "TRANSPORT" }, date: { gte: tDay(39) } } });
    // wallets + balances + txns + audits touched by the run
    await db.serviceBalance.deleteMany({ where: { serviceId: { in: svcIds } } });
    await db.walletTxn.deleteMany({ where: { studentId: st, serviceId: { in: svcIds } } });
    await db.dailyFeeAudit.deleteMany({ where: { studentId: st, createdAt: { gte: testStart } } });
    await db.smsMessage.deleteMany({ where: { studentId: st, sentAt: { gte: testStart } } });
    await db.dailyService.deleteMany({ where: { id: { in: svcIds } } });
    // transport fixtures
    await db.transportBoarding.deleteMany({ where: { routeId: route.id } });
    await db.transportAssignment.deleteMany({ where: { id: asg.id } });
    await db.transportStop.deleteMany({ where: { routeId: route.id } });
    await db.transportRoute.delete({ where: { id: route.id } });
    // restore SMS receipts setting
    if (origSms) await db.smsSetting.update({ where: { schoolId }, data: { autoPaymentReceipts: origSms.autoPaymentReceipts } });
    check("test services removed", (await db.dailyService.count({ where: { id: { in: svcIds } } })) === 0);
    check("test route removed", (await db.transportRoute.count({ where: { id: route.id } })) === 0);
    check("no SYNC:syncsmk payments left", (await db.payment.count({ where: { reference: { startsWith: "SYNC:syncsmk-" } } })) === 0);
  }

  console.log(`\n${pass}/${pass + fail} checks passed${fail ? " — FIX THE FAILURES" : ""}`);
  if (fail) process.exit(1);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

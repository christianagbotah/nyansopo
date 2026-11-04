/* Daily-fee rates smoke — CI3 Daily_fee_model parity wave:
   (a) transport fare per pickup stop (route-based, same class → different fares)
   (b) per-item payment entry (each fee item's amount settles its own arrears;
       excess becomes per-item advance)
   (c) per-item prepaid balances with auto-deduct at charge generation
   (d) weekly water: billed once per ISO week; once paid, no rebill that week
   (e) transport class-rate guard (fare is route-based, never class-based)
   Run: bun scripts/smoke-daily-rates.ts (dev server + seeded DB required) */
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
  const post = (body: any) => fetch(`${BASE}/api/school/${schoolId}/ops/daily`, { method: "POST", headers: H, body: JSON.stringify(body) });
  const get = (q = "") => fetch(`${BASE}/api/school/${schoolId}/ops/daily${q}`, { headers: H }).then((r) => r.json());
  const testStart = new Date();

  // two learners in the SAME classroom (to prove transport fares diverge by stop)
  const pair = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, include: { student: true }, orderBy: { studentId: "asc" } });
  const byClass = new Map<string, typeof pair>();
  pair.forEach((e) => { (byClass.get(e.classroomId) ?? byClass.set(e.classroomId, []).get(e.classroomId)!).push(e); });
  const cls = [...byClass.values()].find((arr) => arr.length >= 2)!;
  const [s1, s2] = cls; // s1, s2 share a classroom
  const st1 = s1.studentId, st2 = s2.studentId;
  console.log(`fixtures: ${s1.student.firstName} & ${s2.student.firstName} (${s1.classroomId})`);

  // determinism: suspend any pre-existing route assignments for the fixture
  // learners — seeded demo routes would otherwise race the test's own route in
  // resolveTransportFare's unordered findFirst (restored in cleanup)
  const priorAssignments = await db.transportAssignment.findMany({ where: { studentId: { in: [st1, st2] }, active: true } });
  await db.transportAssignment.updateMany({ where: { id: { in: priorAssignments.map((a) => a.id) } }, data: { active: false } });

  // silence receipt SMS during the test (restored in cleanup)
  const origSms = await db.smsSetting.findUnique({ where: { schoolId } });
  await db.smsSetting.upsert({ where: { schoolId }, update: { autoPaymentReceipts: false }, create: { schoolId, autoPaymentReceipts: false } });

  // ── fixtures: two test services + a route with per-stop fares ──
  const today = new Date(); today.setHours(0, 0, 0, 0);
  const dow = today.getDay(); // 0 Sun … 6 Sat
  const nextWeek = new Date(today.getTime() + 7 * 864e5);
  const svcMeal = await db.dailyService.create({ data: { schoolId, name: `SMOKE Lunch ${Date.now()}`, category: "MEAL", priceCents: 800, chargeMode: "DAILY", weekdays: `${dow}` } });
  const svcWater = await db.dailyService.create({ data: { schoolId, name: `SMOKE Water ${Date.now()}`, category: "DRINK", priceCents: 500, chargeMode: "WEEKLY", weekdays: `${dow},${(dow + 1) % 7}` } });
  await post({ action: "enroll", serviceId: svcMeal.id, studentIds: [st1, st2] });
  await post({ action: "enroll", serviceId: svcWater.id, studentIds: [st1, st2] });
  const route = await db.transportRoute.create({ data: { schoolId, name: `SMOKE Route ${Date.now()}`, vehicleReg: "SMOKE-1", driverName: "Smoke Driver", driverPhone: "0240000000", capacity: 10, farePerTermCents: 40000, farePerTripCents: 500, stops: "Stop A, Stop B" } });
  const stopA = await db.transportStop.create({ data: { routeId: route.id, name: "Stop A", fareCents: 300, sortOrder: 0 } });
  const stopB = await db.transportStop.create({ data: { routeId: route.id, name: "Stop B", fareCents: 700, sortOrder: 1 } });
  void stopA; void stopB;
  const asg1 = await db.transportAssignment.create({ data: { routeId: route.id, studentId: st1, pickupStop: "Stop A", dropStop: "Stop A" } });
  const asg2 = await db.transportAssignment.create({ data: { routeId: route.id, studentId: st2, pickupStop: "Stop B", dropStop: "Stop B" } });
  const transportSvc = await db.dailyService.findFirst({ where: { schoolId, category: "TRANSPORT" } });
  if (!transportSvc) { console.log("no TRANSPORT-category daily service — seed provides one"); return; }

  const chargesFor = async (studentId: string, serviceId: string) =>
    db.dailyCharge.findMany({ where: { studentId, serviceId }, orderBy: { date: "asc" } });

  console.log("\n── A. transport fare follows the pickup stop (route-based, not class-based) ──");
  {
    const d = today.toISOString().slice(0, 10);
    const r1 = await post({ action: "pos-collect", studentId: st1, date: d, transportUsage: "BOTH", items: [{ serviceId: svcMeal.id, amountCents: 1 }] });
    check("stop A rider: pos-collect accepted (meal 1GHP placeholder)", r1.status === 200, String(r1.status));
    const r2 = await post({ action: "pos-collect", studentId: st2, date: d, transportUsage: "MORNING", items: [{ serviceId: svcMeal.id, amountCents: 1 }] });
    check("stop B rider: pos-collect accepted", r2.status === 200, String(r2.status));
    const t1 = await db.dailyCharge.findFirst({ where: { studentId: st1, service: { category: "TRANSPORT" }, date: today } });
    const t2 = await db.dailyCharge.findFirst({ where: { studentId: st2, service: { category: "TRANSPORT" }, date: today } });
    check("Stop A both-ways = 2 × GH₵3.00 = 6.00", t1?.amountCents === 600, `${t1?.amountCents}`);
    check("Stop B morning-only = 1 × GH₵7.00 = 7.00", t2?.amountCents === 700, `${t2?.amountCents}`);
    check("same class, different fares by pickup point", t1?.amountCents !== t2?.amountCents);
    // GET payload exposes the resolved fare with the stop that set it
    const data = await get(`?date=${d}`);
    const ps1 = data.posStudents.find((p: any) => p.id === st1);
    const ps2 = data.posStudents.find((p: any) => p.id === st2);
    check("GET carries per-learner fare (stop A → 3.00, stop B → 7.00)", ps1?.transportFare?.oneway === 300 && ps2?.transportFare?.oneway === 700, `${ps1?.transportFare?.oneway}/${ps2?.transportFare?.oneway}`);
    check("stop fare names the pickup point", ps1?.transportFare?.stopName === "Stop A" && ps2?.transportFare?.stopName === "Stop B");
  }

  console.log("\n── B. weekly water: billed once per ISO week, rebilled the next ──");
  {
    // s1 paid this week (per-item entry below covers it) — first generate the day
    const g1 = await post({ action: "generate-day", date: today.toISOString().slice(0, 10) });
    const gen1 = await g1.json();
    check("day generated", g1.status === 200 && gen1.created > 0, JSON.stringify(gen1).slice(0, 80));
    const w1 = await chargesFor(st1, svcWater.id);
    check("water charged once this week (GH₵5.00)", w1.length === 1 && w1[0].amountCents === 500, `${w1.length}×${w1[0]?.amountCents}`);
    // regenerate same day → idempotent
    const g2 = await (await post({ action: "generate-day", date: today.toISOString().slice(0, 10) })).json();
    const w2 = await chargesFor(st1, svcWater.id);
    check("regenerate same day → still one charge", w2.length === 1, `${w2.length}`);
    // pay the water item in full → no further billing this week
    const pay = await post({ action: "pos-collect", studentId: st1, items: [{ serviceId: svcWater.id, amountCents: 500 }] });
    check("water paid via per-item entry", pay.status === 200, String(pay.status));
    const g3 = await (await post({ action: "generate-day", date: today.toISOString().slice(0, 10) })).json();
    const w3 = await chargesFor(st1, svcWater.id);
    check("paid this week → NO rebill", w3.length === 1 && w3[0].status === "PAID", `${w3.length}×${w3[0]?.status}`);
    // next week: fresh charge (once per ISO week, not once ever)
    await post({ action: "generate-day", date: nextWeek.toISOString().slice(0, 10) });
    const w4 = await chargesFor(st1, svcWater.id);
    check("following week → billed again (once per week)", w4.length === 2 && w4[1].status === "OWED", `${w4.length}×${w4.map((c) => c.status).join(",")}`);
    // GET exposes weekly state
    const data = await get(`?date=${today.toISOString().slice(0, 10)}`);
    const item = data.posStudents.find((p: any) => p.id === st1)?.items.find((i: any) => i.serviceId === svcWater.id);
    check("GET item card shows weekly + billed-this-week", item?.weekly === true && item?.weeklyBilledThisWeek === true, JSON.stringify(item?.weeklyBilledThisWeek));
  }

  console.log("\n── C. per-item payment entry: item amounts settle their own arrears ──");
  {
    // s2 owes: meal 7.99 (today, after a 1p auto-deduct) + 8.00 (next week) +
    // water 5.00 (today) + 5.00 (next week) — paying meal GH₵20 settles GH₵15.99
    // of meal arrears oldest-first and banks GH₵4.01 as meal advance; water untouched
    const mealOwed = (await chargesFor(st2, svcMeal.id)).filter((c) => c.status === "OWED").reduce((a, c) => a + c.amountCents, 0);
    const waterOwed = (await chargesFor(st2, svcWater.id)).filter((c) => c.status === "OWED").reduce((a, c) => a + c.amountCents, 0);
    check("s2 owes meal 15.99 + water 10.00 before the per-item payment", mealOwed === 1599 && waterOwed === 1000, `${mealOwed}/${waterOwed}`);
    const res = await post({ action: "pos-collect", studentId: st2, method: "CASH", items: [{ serviceId: svcMeal.id, amountCents: 2000 }] });
    const body = await res.json().catch(() => ({}));
    check("per-item payment accepted", res.status === 200, `${res.status} ${JSON.stringify(body).slice(0, 120)}`);
    check("paymentType = mixed (15.99 settled + 4.01 advance)", body.payment?.paymentType === "mixed", body.payment?.paymentType);
    check("breakdown shows meal settled 15.99 + advance 4.01", body.payment?.breakdown?.[0]?.paid === 1599 && body.payment?.breakdown?.[0]?.advance === 401, JSON.stringify(body.payment?.breakdown));
    const bal = await db.serviceBalance.findUnique({ where: { studentId_serviceId: { studentId: st2, serviceId: svcMeal.id } } });
    check("excess landed on the meal item balance", bal?.prepaidCents === 401, `${bal?.prepaidCents}`);
    const mealAfter = (await chargesFor(st2, svcMeal.id)).filter((c) => c.status === "OWED").reduce((a, c) => a + c.amountCents, 0);
    const waterAfter = (await chargesFor(st2, svcWater.id)).filter((c) => c.status === "OWED").reduce((a, c) => a + c.amountCents, 0);
    check("meal arrears cleared, water arrears untouched (per-item isolation)", mealAfter === 0 && waterAfter === 1000, `${mealAfter}/${waterAfter}`);
    const txn = await db.walletTxn.findFirst({ where: { studentId: st2, wallet: "ITEM", serviceId: svcMeal.id, kind: "TOPUP" }, orderBy: { at: "desc" } });
    check("advance wrote an ITEM wallet txn", txn?.amountCents === 401 && !!txn.note?.includes("advance"), JSON.stringify(txn?.amountCents));
  }

  console.log("\n── D. per-item auto-deduct at charge generation (CI3 apply_charge) ──");
  {
    // s2 holds GH₵4.01 meal credit; the week-3 meal charge auto-settles from it
    const week3 = new Date(today.getTime() + 14 * 864e5);
    await post({ action: "generate-day", date: week3.toISOString().slice(0, 10) });
    const w3rows = await db.dailyCharge.findMany({ where: { studentId: st2, serviceId: svcMeal.id, date: week3 }, orderBy: { amountCents: "desc" } });
    const p3 = w3rows.filter((c) => c.status === "PAID").reduce((a, c) => a + c.amountCents, 0);
    const o3 = w3rows.filter((c) => c.status === "OWED").reduce((a, c) => a + c.amountCents, 0);
    check("week-3 meal charge auto-deducted 4.01 from item balance", p3 === 401 && o3 === 399, `paid=${p3} owed=${o3} rows=${w3rows.map((c) => `${c.amountCents}/${c.status}`).join(",")}`);
    check("item balance now zero", (await db.serviceBalance.findUnique({ where: { studentId_serviceId: { studentId: st2, serviceId: svcMeal.id } } }))?.prepaidCents === 0);
    const payTxn = await db.walletTxn.findFirst({ where: { studentId: st2, wallet: "ITEM", serviceId: svcMeal.id, kind: "PAY" }, orderBy: { at: "desc" } });
    check("auto-deduct wrote an ITEM PAY txn", payTxn?.amountCents === -401, JSON.stringify(payTxn?.amountCents));
    // partial balance case: set balance to 3.00, generate week 4 → 3 settled + 5 owed
    await db.serviceBalance.update({ where: { studentId_serviceId: { studentId: st2, serviceId: svcMeal.id } }, data: { prepaidCents: 300 } });
    const week4 = new Date(today.getTime() + 21 * 864e5);
    await post({ action: "generate-day", date: week4.toISOString().slice(0, 10) });
    const w4rows = await db.dailyCharge.findMany({ where: { studentId: st2, serviceId: svcMeal.id, date: week4 }, orderBy: { amountCents: "desc" } });
    const p4 = w4rows.filter((c) => c.status === "PAID").reduce((a, c) => a + c.amountCents, 0);
    const o4 = w4rows.filter((c) => c.status === "OWED").reduce((a, c) => a + c.amountCents, 0);
    check("partial balance: 3.00 auto-deducted, 5.00 remains owed", p4 === 300 && o4 === 500, `paid=${p4} owed=${o4} rows=${w4rows.map((c) => `${c.amountCents}/${c.status}`).join(",")}`);
  }

  console.log("\n── E. per-item top-up + correction + transport class-rate guard ──");
  {
    const top = await post({ action: "wallet-topup", studentId: st1, serviceId: svcWater.id, amountCents: 1000, note: "smoke item topup" });
    check("per-item top-up accepted", top.status === 200, String(top.status));
    check("water item balance = 10.00", (await db.serviceBalance.findUnique({ where: { studentId_serviceId: { studentId: st1, serviceId: svcWater.id } } }))?.prepaidCents === 1000);
    const adj = await post({ action: "wallet-adjust", studentId: st1, serviceId: svcWater.id, amountCents: 250 });
    check("per-item correction sets exact balance", adj.status === 200 && (await db.serviceBalance.findUnique({ where: { studentId_serviceId: { studentId: st1, serviceId: svcWater.id } } }))?.prepaidCents === 250, String(adj.status));
    const bad = await post({ action: "rate-save", serviceId: transportSvc.id, classroomId: s1.classroomId, priceCents: 500 });
    check("transport class-rate rejected (fare is route-based)", bad.status === 400, String(bad.status));
    const guard = await bad.json().catch(() => ({}));
    check("guard message points to Transportation", /route/i.test(guard?.error ?? ""), guard?.error);
    // GET payload: per-item cards for every learner
    const data = await get(`?date=${today.toISOString().slice(0, 10)}`);
    const items1 = data.posStudents.find((p: any) => p.id === st1)?.items ?? [];
    check("GET items[] carries per-item arrears + balance", items1.some((i: any) => i.serviceId === svcWater.id && i.balance === 250), JSON.stringify(items1.find((i: any) => i.serviceId === svcWater.id)));
    check("wallets payload carries per-item breakdown", (data.wallets.find((w: any) => w.studentId === st1)?.items ?? []).some((i: any) => i.serviceId === svcWater.id && i.balance === 250));
  }

  console.log("\n── cleanup ──");
  {
    const svcIds = [svcMeal.id, svcWater.id];
    // payments minted during the run → restore the receipt counter, then delete
    const pays = await db.payment.findMany({ where: { schoolId, category: "DAILY", paidAt: { gte: testStart }, studentId: { in: [st1, st2] } }, select: { id: true, receiptNo: true } });
    for (const p of pays) {
      await db.dailyCharge.updateMany({ where: { paymentId: p.id }, data: { paymentId: null, status: "OWED" } }).catch(() => null);
      await db.payment.delete({ where: { id: p.id } }).catch(() => null);
    }
    const YEAR = new Date().getFullYear();
    const rc = await db.codeCounter.findUnique({ where: { scope: `RCP:${YEAR}` } });
    if (rc) await db.codeCounter.update({ where: { scope: `RCP:${YEAR}` }, data: { next: Math.max(0, rc.next - pays.length) } });
    // ledger rows for the test services
    await db.dailyCharge.deleteMany({ where: { serviceId: { in: svcIds } } });
    // transport charges created via usage picks today
    await db.dailyCharge.deleteMany({ where: { schoolId, studentId: { in: [st1, st2] }, service: { category: "TRANSPORT" }, date: { gte: today } } });
    // wallets + balances + txns + audits + sms touched by the run
    await db.serviceBalance.deleteMany({ where: { serviceId: { in: svcIds } } });
    await db.walletTxn.deleteMany({ where: { studentId: { in: [st1, st2] }, serviceId: { in: svcIds } } });
    await db.dailyFeeAudit.deleteMany({ where: { studentId: { in: [st1, st2] }, createdAt: { gte: testStart } } });
    await db.smsMessage.deleteMany({ where: { studentId: { in: [st1, st2] }, sentAt: { gte: testStart } } });
    await db.dailyServiceEnrollment.deleteMany({ where: { serviceId: { in: svcIds } } });
    await db.dailyService.deleteMany({ where: { id: { in: svcIds } } });
    // transport fixtures
    await db.transportBoarding.deleteMany({ where: { routeId: route.id } });
    await db.transportAssignment.deleteMany({ where: { id: { in: [asg1.id, asg2.id] } } });
    await db.transportStop.deleteMany({ where: { routeId: route.id } });
    await db.transportRoute.delete({ where: { id: route.id } });
    // restore the learners' pre-existing route assignments
    await db.transportAssignment.updateMany({ where: { id: { in: priorAssignments.map((a) => a.id) } }, data: { active: true } });
    // restore SMS receipts setting
    if (origSms) await db.smsSetting.update({ where: { schoolId }, data: { autoPaymentReceipts: origSms.autoPaymentReceipts } });
    check("test services removed", (await db.dailyService.count({ where: { id: { in: svcIds } } })) === 0);
    check("test route removed", (await db.transportRoute.count({ where: { id: route.id } })) === 0);
  }

  console.log(`\n${pass}/${pass + fail} checks passed${fail ? " — FIX THE FAILURES" : ""}`);
  if (fail) process.exit(1);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

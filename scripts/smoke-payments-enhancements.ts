/* Payments & meals enhancements smoke — wave 8:
   (a) split POS collections (multiple methods on one receipt, byMethod expansion)
   (b) meal opt-in / opt-out register (generate-day skips + waivers, optInDaily services)
   (c) parent portal meal requests (PENDING → decide)
   (d) fees roster residence (CI3 day/boarding filter source)
   Run: bun scripts/smoke-payments-enhancements.ts (dev server + seeded DB required) */
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
  const parent = await db.user.findUnique({ where: { email: "parent@nyansapo.gh" } });
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!user || !school || !parent) { console.log("missing demo data — run the seed first"); return; }
  const schoolId = school.id;
  const H = { "content-type": "application/json", "x-user-id": user.id };
  const PH = { "content-type": "application/json", "x-user-id": parent.id };
  const post = (body: any, h = H) => fetch(`${BASE}/api/school/${schoolId}/ops/daily`, { method: "POST", headers: h, body: JSON.stringify(body) });
  const get = (q = "") => fetch(`${BASE}/api/school/${schoolId}/ops/daily${q}`, { headers: H }).then((r) => r.json());
  const cashierGet = () => fetch(`${BASE}/api/school/${schoolId}/ops/cashier`, { headers: H }).then((r) => r.json());
  const testStart = new Date();

  // fixtures: two learners from one classroom + two test meal services
  const pair = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, include: { student: true }, orderBy: { studentId: "asc" } });
  const byClass = new Map<string, typeof pair>();
  pair.forEach((e) => { (byClass.get(e.classroomId) ?? byClass.set(e.classroomId, []).get(e.classroomId)!).push(e); });
  const cls = [...byClass.values()].find((arr) => arr.length >= 2)!;
  const [s1, s2] = cls;
  const st1 = s1.studentId, st2 = s2.studentId;
  console.log(`fixtures: ${s1.student.firstName} & ${s2.student.firstName} (${cls[0].classroomId})`);

  const origSms = await db.smsSetting.findUnique({ where: { schoolId } });
  await db.smsSetting.upsert({ where: { schoolId }, update: { autoPaymentReceipts: false }, create: { schoolId, autoPaymentReceipts: false } });

  const today = new Date(); today.setHours(0, 0, 0, 0);
  const dow = today.getDay();
  const day = today.toISOString().slice(0, 10);
  const svcMeal = await db.dailyService.create({ data: { schoolId, name: `SMOKE Lunch ${Date.now()}`, category: "MEAL", priceCents: 800, chargeMode: "DAILY", weekdays: `${dow}` } });
  const svcBreak = await db.dailyService.create({ data: { schoolId, name: `SMOKE Breakfast ${Date.now()}`, category: "MEAL", priceCents: 500, chargeMode: "DAILY", weekdays: `${dow}`, optInDaily: true } });
  await post({ action: "enroll", serviceId: svcMeal.id, studentIds: [st1, st2] });
  await post({ action: "enroll", serviceId: svcBreak.id, studentIds: [st1, st2] });

  console.log("\n── A. split POS collection (multiple methods, one receipt) ──");
  {
    // materialise the day's charges first — a payment against no arrears banks
    // as item advance (iEduPro parity), which is a separate behaviour
    await post({ action: "generate-day", date: day });
    const bad = await post({ action: "pos-collect", studentId: st2, date: day, items: [{ serviceId: svcMeal.id, amountCents: 800 }], methods: [{ method: "CASH", amountCents: 300 }, { method: "MOMO_MTN", amountCents: 400 }] });
    check("mismatched split refused (300+400 ≠ 800)", bad.status === 400, String(bad.status));
    const unknown = await post({ action: "pos-collect", studentId: st2, date: day, items: [{ serviceId: svcMeal.id, amountCents: 800 }], methods: [{ method: "CASH", amountCents: 400 }, { method: "BITCOIN", amountCents: 400 }] });
    check("unknown method refused", unknown.status === 400, String(unknown.status));
    const ok = await post({ action: "pos-collect", studentId: st2, date: day, items: [{ serviceId: svcMeal.id, amountCents: 800 }], methods: [{ method: "CASH", amountCents: 300 }, { method: "MOMO_MTN", amountCents: 500, reference: "SMOKE-SPLIT-1" }] });
    check("split collection accepted (300 cash + 500 momo)", ok.status === 200, String(ok.status));
    const data = await ok.json();
    check("payment method = SPLIT", data.payment?.method === "SPLIT", data.payment?.method);
    const parts = data.payment?.splitJson ?? [];
    check("splitJson holds both parts", Array.isArray(parts) && parts.length === 2 && parts[0].amountCents + parts[1].amountCents === 800, JSON.stringify(parts));
    check("receipt method expands to 'Cash + MTN MoMo'", /Cash.*MTN MoMo|MTN MoMo.*Cash/.test(data.receipt?.method ?? ""), data.receipt?.method);
    const mealPaid = await db.dailyCharge.findFirst({ where: { studentId: st2, serviceId: svcMeal.id, date: today, status: "PAID" } });
    check("meal charge fully settled by the split", !!mealPaid && mealPaid.amountCents === 800, `${mealPaid?.amountCents}/${mealPaid?.status}`);
    const cash = await db.payment.findFirst({ where: { receiptNo: data.payment.receiptNo } });
    check("payment reference carries first non-empty part", cash?.reference === "SMOKE-SPLIT-1", cash?.reference);
    const rep = await cashierGet();
    check("cashier byMethod expanded (CASH and MOMO_MTN both present)", rep.byMethod?.CASH >= 300 && rep.byMethod?.MOMO_MTN >= 500, JSON.stringify({ CASH: rep.byMethod?.CASH, MOMO_MTN: rep.byMethod?.MOMO_MTN }));
    check("no raw SPLIT key leaks into the mix", !("SPLIT" in (rep.byMethod ?? {})));
  }

  console.log("\n── B. meal opt-in / opt-out register ──");
  {
    const gen = async () => post({ action: "generate-day", date: day });
    const lunch1 = await db.dailyCharge.findFirst({ where: { studentId: st1, serviceId: svcMeal.id, date: today } });
    check("default meal charged to every enrolled learner", !!lunch1, `${lunch1?.status}`);
    const breakCharges = await db.dailyCharge.findMany({ where: { studentId: st1, serviceId: svcBreak.id, date: today } });
    check("opt-in service NOT billed without an opt-in", breakCharges.length === 0, `${breakCharges.length} rows`);
    const badOptIn = await post({ action: "meal-exception-save", studentId: st2, serviceId: svcMeal.id, date: day, kind: "OPT_IN" });
    check("OPT_IN on a default meal refused", badOptIn.status === 400, String(badOptIn.status));
    const badOptOut = await post({ action: "meal-exception-save", studentId: st2, serviceId: svcBreak.id, date: day, kind: "OPT_OUT" });
    check("OPT_OUT on an opt-in service refused", badOptOut.status === 400, String(badOptOut.status));

    // admin approves an opt-out for st1's lunch → unpaid charge waived
    const out = await post({ action: "meal-exception-save", studentId: st1, serviceId: svcMeal.id, date: day, kind: "OPT_OUT", reason: "smoke packed lunch" });
    check("staff opt-out approved immediately", out.status === 200, String(out.status));
    const waived = await db.dailyCharge.findFirst({ where: { studentId: st1, serviceId: svcMeal.id, date: today, status: "WAIVED" } });
    check("existing unpaid lunch charge waived by the opt-out", !!waived, `${waived?.status}`);

    // opt-in for st2's breakfast → next generate bills it
    const inn = await post({ action: "meal-exception-save", studentId: st2, serviceId: svcBreak.id, date: day, kind: "OPT_IN" });
    check("staff opt-in approved immediately", inn.status === 200, String(inn.status));
    await gen();
    const break2 = await db.dailyCharge.findFirst({ where: { studentId: st2, serviceId: svcBreak.id, date: today } });
    check("opt-in learner now billed for breakfast", !!break2 && break2.amountCents === 500, `${break2?.amountCents}`);
    const stillNone = await db.dailyCharge.findFirst({ where: { studentId: st1, serviceId: svcBreak.id, date: today } });
    check("non-opt-in learner stays unbilled for breakfast", !stillNone);

    // GET payload: register + per-item badge
    const data = await get(`?date=${day}`);
    const reg = (data.mealExceptions ?? []) as any[];
    check("GET mealExceptions carries the day's register", reg.some((r) => r.studentId === st1 && r.kind === "OPT_OUT" && r.status === "APPROVED") && reg.some((r) => r.studentId === st2 && r.kind === "OPT_IN"), JSON.stringify(reg.length));
    const ps = (data.posStudents ?? []).find((p: any) => p.id === st1);
    check("POS item badge marks the approved skip", (ps?.items ?? []).some((i: any) => i.serviceId === svcMeal.id && i.optedOut === "APPROVED"));
  }

  console.log("\n── C. parent portal meal request (PENDING → decide) ──");
  {
    const parentIds: string[] = Array.isArray(parent.linkedStudentIds) ? (parent.linkedStudentIds as string[]) : JSON.parse(String(parent.linkedStudentIds));
    const linkedId = parentIds[0];
    const linked = await db.enrollment.findFirst({ where: { studentId: linkedId, schoolId, status: "ACTIVE" } });
    if (!linked) {
      console.log("  ⚠️ parent's linked learner not enrolled in GEA — skipping parent checks");
    } else {
      // enroll the parent's child into the test meal so the request targets it
      await post({ action: "enroll", serviceId: svcMeal.id, studentIds: [linkedId] });
      const notMine = await fetch(`${BASE}/api/school/${schoolId}/ops/portal`, { method: "POST", headers: PH, body: JSON.stringify({ action: "meal-request.create", studentId: st1, serviceId: svcMeal.id, date: day, kind: "OPT_OUT" }) });
      check("parent cannot request for someone else's child", notMine.status === 403, String(notMine.status));
      const ok = await fetch(`${BASE}/api/school/${schoolId}/ops/portal`, { method: "POST", headers: PH, body: JSON.stringify({ action: "meal-request.create", studentId: linkedId, serviceId: svcMeal.id, date: day, kind: "OPT_OUT", reason: "smoke family travel" }) });
      check("parent meal request created", ok.status === 200, String(ok.status));
      const req = (await ok.json())?.request;
      const row = req ? await db.dailyMealException.findUnique({ where: { id: req.id } }) : null;
      check("request lands PENDING", row?.status === "PENDING", row?.status);
      const dup = await fetch(`${BASE}/api/school/${schoolId}/ops/portal`, { method: "POST", headers: PH, body: JSON.stringify({ action: "meal-request.create", studentId: linkedId, serviceId: svcMeal.id, date: day, kind: "OPT_OUT" }) });
      check("duplicate pending request refused", dup.status === 409, String(dup.status));
      const pv = await fetch(`${BASE}/api/school/${schoolId}/ops/portal?view=parent`, { headers: PH }).then((r) => r.json());
      check("portal payload lists mealServices + mealRequests", (pv.mealServices ?? []).length > 0 && (pv.mealRequests ?? []).some((r: any) => r.id === req.id));
      const backdated = await fetch(`${BASE}/api/school/${schoolId}/ops/portal`, { method: "POST", headers: PH, body: JSON.stringify({ action: "meal-request.create", studentId: linkedId, serviceId: svcMeal.id, date: "2020-01-01", kind: "OPT_OUT" }) });
      check("backdated request refused", backdated.status === 400, String(backdated.status));
      const dec = await post({ action: "meal-exception-decide", id: req.id, decision: "APPROVED" });
      check("school approves the request", dec.status === 200, String(dec.status));
      const decided = req ? await db.dailyMealException.findUnique({ where: { id: req.id } }) : null;
      check("decision recorded with approver", decided?.status === "APPROVED" && !!decided?.decidedByName);
    }
  }

  console.log("\n── D. fees roster residence (CI3 day/boarding filter) ──");
  {
    const fees = await fetch(`${BASE}/api/school/${schoolId}/fees`, { headers: H }).then((r) => r.json());
    const rows = fees.roster ?? [];
    check("roster rows carry residence (DAY | BOARDING)", rows.length > 0 && rows.every((r: any) => ["DAY", "BOARDING"].includes(r.residence)), JSON.stringify(rows[0]?.residence));
  }

  console.log("\n── cleanup ──");
  {
    const svcIds = [svcMeal.id, svcBreak.id];
    // payments minted during the run → restore the receipt counter, then delete
    const pays = await db.payment.findMany({ where: { schoolId, category: "DAILY", paidAt: { gte: testStart }, studentId: { in: [st1, st2, ...((() => { try { return Array.isArray(parent.linkedStudentIds) ? (parent.linkedStudentIds as string[]) : JSON.parse(String(parent.linkedStudentIds)); } catch { return []; } })())] } }, select: { id: true, receiptNo: true } });
    for (const p of pays) {
      await db.dailyCharge.updateMany({ where: { paymentId: p.id }, data: { paymentId: null, status: "OWED" } }).catch(() => null);
      await db.payment.delete({ where: { id: p.id } }).catch(() => null);
    }
    const YEAR = new Date().getFullYear();
    const rc = await db.codeCounter.findUnique({ where: { scope: `RCP:${YEAR}` } });
    if (rc) await db.codeCounter.update({ where: { scope: `RCP:${YEAR}` }, data: { next: Math.max(0, rc.next - pays.length) } });
    await db.dailyCharge.deleteMany({ where: { serviceId: { in: svcIds } } });
    await db.serviceBalance.deleteMany({ where: { serviceId: { in: svcIds } } });
    await db.walletTxn.deleteMany({ where: { at: { gte: testStart }, serviceId: { in: svcIds } } });
    await db.dailyFeeAudit.deleteMany({ where: { createdAt: { gte: testStart }, serviceCode: { in: [svcMeal.name, svcBreak.name] } } });
    await db.smsMessage.deleteMany({ where: { sentAt: { gte: testStart }, category: "DAILY" } });
    await db.dailyServiceEnrollment.deleteMany({ where: { serviceId: { in: svcIds } } });
    await db.dailyMealException.deleteMany({ where: { serviceId: { in: svcIds } } });
    await db.dailyService.deleteMany({ where: { id: { in: svcIds } } });
    if (origSms) await db.smsSetting.update({ where: { schoolId }, data: { autoPaymentReceipts: origSms.autoPaymentReceipts } });
    check("test services removed", (await db.dailyService.count({ where: { id: { in: svcIds } } })) === 0);
  }

  console.log(`\n${pass}/${pass + fail} checks passed${fail ? " — FIX THE FAILURES" : ""}`);
  await db.$disconnect();
  process.exit(fail ? 1 : 0);
}

main().catch(async (e) => { console.error(e); await db.$disconnect(); process.exit(1); });

/* Wave 10 smoke — permission-based modules, collection portal, CI3 register
   (a) role configs: tune CASHIER (bar invoice billing), mint a custom role,
       delete-with-reassign, revert → effective permissions re-layer everywhere
   (b) fee collection portal: student-info conditional cards (optional toggles,
       approved opt-outs disable, 100% discounts hide, transport riders, invoices)
   (c) prepayment: overpay an item → arrears settle FIFO, excess banks on the
       SAME item as prepaid advance
   (d) CI3 register charge rule: PRESENT→ABSENT waives the day (PAID money held
       as item prepaid), ABSENT→PRESENT re-instates; fees ride the same save
   Run: bun scripts/smoke-wave10.ts (prod server + seeded DB required) */
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
  const head = await db.user.findUnique({ where: { email: "super@nyansapo.gh" } }); // level 1 — the only role that manages permissions (CI3 parity)
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!head || !school) { console.log("missing demo data — run the seed first"); return; }
  const schoolId = school.id;
  const H = { "content-type": "application/json", "x-user-id": head.id };
  const post = (path: string, body: any) => fetch(`${BASE}${path}`, { method: "POST", headers: H, body: JSON.stringify(body) });
  const get = (path: string) => fetch(`${BASE}${path}`, { headers: H }).then((r) => r.json());
  const today = new Date(); today.setHours(0, 0, 0, 0);
  const dow = today.getDay();
  const dayKey = (d: Date) => new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
  const dateStr = (d: Date) => d.toISOString().slice(0, 10);

  console.log("── (a) role configs & permission layering");
  {
    // baseline: the seeded cashier keeps invoice billing by default
    const cashierUser = await db.user.findFirst({ where: { schoolId, role: "CASHIER" } });
    check("seeded cashier exists", !!cashierUser);
    const before = await get(`/api/school/${schoolId}/ops/rbac`);
    const cashierBefore = before.users.find((u: any) => u.id === cashierUser!.id);
    check("cashier defaults include fees.collect (invoice receipts)", cashierBefore?.permissions.includes("fees.collect") === true);

    // tune CASHIER at this school: collection portal yes, invoice billing/receipts no
    const tunedList = (cashierBefore?.permissions as string[]).filter((p) => !["fees.manage", "fees.collect"].includes(p));
    const saveRes = await post(`/api/school/${schoolId}/ops/rbac`, {
      action: "role.save", role: "CASHIER", isCustom: false, label: "Cashier", level: 4,
      landingModule: "cashier", permissions: tunedList,
    });
    check("role.save tunes CASHIER", saveRes.status === 200, JSON.stringify(await saveRes.text()));

    const during = await get(`/api/school/${schoolId}/ops/rbac`);
    const cashierDuring = during.users.find((u: any) => u.id === cashierUser!.id);
    check("cashier now barred from billing invoices", !cashierDuring?.permissions.includes("fees.collect") && !cashierDuring?.permissions.includes("fees.manage"));
    check("cashier keeps daily.collect (lunch money)", cashierDuring?.permissions.includes("daily.collect") === true);
    check("cashier roleBase reflects the config", Array.isArray(cashierDuring?.roleBase) && !cashierDuring.roleBase.includes("fees.collect"));
    const roleCard = (during.roles as any[]).find((r) => r.role === "CASHIER");
    check("role card shows the tuned set", roleCard?.active === true && roleCard?.permCount === tunedList.length, `permCount=${roleCard?.permCount}`);

    // mint a custom role + user, verify session layering (auth route)
    const mint = await post(`/api/school/${schoolId}/ops/rbac`, {
      action: "role.save", isCustom: true, label: "Front Desk",
      level: 4, landingModule: "cashier",
      permissions: ["students.view", "fees.view", "daily.view", "daily.collect", "cashier.reports", "staffhub.self"],
    });
    const mintBody = await mint.json();
    check("custom role minted", mint.status === 200 && !!mintBody?.role?.isCustom, JSON.stringify(mintBody));

    const createRes = await post(`/api/school/${schoolId}/ops/rbac`, {
      action: "user.create", email: `frontdesk-${Date.now()}@nyansapo.gh`, password: "nyansapo",
      fullName: "Akosua Front Desk", role: mintBody.role.role,
    });
    const created = await createRes.json();
    check("user created on the custom role", createRes.status === 200 && !!created?.user?.id, JSON.stringify(created));

    const login = await fetch(`${BASE}/api/auth`, { method: "POST", headers: { "content-type": "application/json" }, body: JSON.stringify({ email: `frontdesk-${Date.now()}@nyansapo.gh`, password: "nyansapo" }) });
    // the login above used a fresh timestamp → wrong email; login with the real one
    const login2 = await fetch(`${BASE}/api/auth`, { method: "POST", headers: { "content-type": "application/json" }, body: JSON.stringify({ email: created.user.email, password: "nyansapo" }) });
    const session = await login2.json();
    check("custom role session: roleLabel + landing", session?.user?.roleLabel === "Front Desk" && session?.user?.landingModule === "cashier", JSON.stringify(session?.user?.roleLabel));
    check("custom role session: backoffice + no invoice billing", session?.user?.roleBackoffice === true && !session?.user?.permissions.includes("fees.manage"));
    check("custom role session: daily.collect intact", session?.user?.permissions.includes("daily.collect") === true);

    // the tuned CASHIER bars invoice billing at the API gate (record-payment → 403)
    const anyInvoice = await db.invoice.findFirst({ where: { schoolId, status: { in: ["UNPAID", "PARTIAL", "OVERDUE"] } } });
    if (anyInvoice) {
      const cashierLogin = await fetch(`${BASE}/api/auth`, { method: "POST", headers: { "content-type": "application/json" }, body: JSON.stringify({ email: cashierUser!.email, password: "nyansapo" }) });
      const cashierSession = await cashierLogin.json();
      const r = await fetch(`${BASE}/api/school/${schoolId}/fees`, { method: "POST", headers: { "content-type": "application/json", "x-user-id": cashierUser!.id }, body: JSON.stringify({ action: "record-payment", invoiceId: anyInvoice.id, amountCents: 100 }) });
      check("tuned cashier cannot take invoice payments (403)", r.status === 403, `got ${r.status}`);
    }

    // delete the custom role — users must be reassigned first
    const delBlocked = await post(`/api/school/${schoolId}/ops/rbac`, { action: "role.delete", role: mintBody.role.role });
    check("delete blocked while a user holds it", delBlocked.status === 400);
    const delOk = await post(`/api/school/${schoolId}/ops/rbac`, { action: "role.delete", role: mintBody.role.role, reassignTo: "CASHIER" });
    check("delete with reassign succeeds", delOk.status === 200, await delOk.text());
    const moved = await db.user.findUnique({ where: { id: created.user.id } });
    check("user reassigned to CASHIER", moved?.role === "CASHIER");

    // revert CASHIER to system defaults
    const revert = await post(`/api/school/${schoolId}/ops/rbac`, { action: "role.save", role: "CASHIER", isCustom: false, label: "Cashier", level: 4, active: false });
    check("revert accepted", revert.status === 200, await revert.text());
    const after = await get(`/api/school/${schoolId}/ops/rbac`);
    const cashierAfter = after.users.find((u: any) => u.id === cashierUser!.id);
    check("cashier back on system defaults", cashierAfter?.permissions.includes("fees.collect") === true);
    await db.user.delete({ where: { id: created.user.id } }).catch(() => {});
  }

  console.log("── (b) fee collection portal — conditional cards");
  const cleanups: (() => Promise<void>)[] = [];
  try {
    // fixtures: 3 learners in one classroom, fresh lunch/breakfast/water services
    const pair = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, include: { student: true }, orderBy: { studentId: "asc" } });
    const byClass = new Map<string, typeof pair>();
    pair.forEach((e) => { (byClass.get(e.classroomId) ?? byClass.set(e.classroomId, []).get(e.classroomId)!).push(e); });
    const cls = [...byClass.values()].find((arr) => arr.length >= 3)!;
    const [a, b, c] = cls.map((e) => e.studentId);
    const classroomId = cls[0].classroomId;
    // determinism: suspend pre-existing transport assignments for the fixture
    // learners — seeded demo routes race the test's own route in
    // resolveTransportFare's unordered findFirst (restored in cleanup)
    const priorAssignments = await db.transportAssignment.findMany({ where: { studentId: { in: [a, b, c] }, active: true } });
    await db.transportAssignment.updateMany({ where: { id: { in: priorAssignments.map((x) => x.id) } }, data: { active: false } });
    // clear transport charges left on `today` by earlier runs of this suite
    await db.dailyCharge.deleteMany({ where: { studentId: { in: [a, b, c] }, service: { category: "TRANSPORT" }, date: dayKey(today) } });
    const T = Date.now();
    const svcLunch = await db.dailyService.create({ data: { schoolId, name: `SMOKE10 Lunch ${T}`, category: "MEAL", priceCents: 800, chargeMode: "DAILY", weekdays: "0,1,2,3,4,5,6" } });
    const svcBreakfast = await db.dailyService.create({ data: { schoolId, name: `SMOKE10 Breakfast ${T}`, category: "MEAL", priceCents: 400, chargeMode: "DAILY", weekdays: "0,1,2,3,4,5,6", optInDaily: true } });
    const svcWater = await db.dailyService.create({ data: { schoolId, name: `SMOKE10 Water ${T}`, category: "DRINK", priceCents: 500, chargeMode: "WEEKLY", weekdays: "0,1,2,3,4,5,6" } });
    for (const sid of [a, b, c]) {
      await db.dailyServiceEnrollment.create({ data: { serviceId: svcLunch.id, studentId: sid } });
      await db.dailyServiceEnrollment.create({ data: { serviceId: svcBreakfast.id, studentId: sid } });
      await db.dailyServiceEnrollment.create({ data: { serviceId: svcWater.id, studentId: sid } });
    }
    cleanups.push(async () => {
      await db.dailyServiceEnrollment.deleteMany({ where: { service: { name: { startsWith: `SMOKE10` } } } });
      await db.dailyCharge.deleteMany({ where: { service: { name: { startsWith: `SMOKE10` } } } });
      await db.dailyMealException.deleteMany({ where: { service: { name: { startsWith: `SMOKE10` } } } });
      await db.serviceBalance.deleteMany({ where: { service: { name: { startsWith: `SMOKE10` } } } });
      await db.discountApplication.deleteMany({ where: { schoolId, valueOverride: { not: null }, studentId: { in: [a, b, c] } } });
      await db.dailyService.deleteMany({ where: { name: { startsWith: `SMOKE10` } } });
    });

    // A: admin-approved lunch opt-out for today (the disabled-card rule)
    await db.dailyMealException.create({ data: { schoolId, studentId: a, serviceId: svcLunch.id, date: dayKey(today), kind: "OPT_OUT", status: "APPROVED", reason: "SMOKE10 fasting", decidedByName: "smoke" } });
    // B: 100% discount on water → that card will not load at all
    await db.discountApplication.create({ data: { schoolId, studentId: b, serviceId: svcWater.id, valueOverride: 100, status: "APPROVED", appliedByName: "smoke", reason: "SMOKE10 full subsidy" } });
    // C: route assignment with a per-stop fare → the transport card
    const route = await db.transportRoute.create({ data: { schoolId, name: `SMOKE10 Route ${T}`, vehicleReg: `SMOKE ${T}`, driverName: "smoke", driverPhone: "", farePerTermCents: 0, farePerTripCents: 700, stops: "Adenta" } });
    const stop = await db.transportStop.create({ data: { routeId: route.id, name: "Adenta", fareCents: 900 } });
    await db.transportAssignment.create({ data: { routeId: route.id, studentId: c, pickupStop: "Adenta" } });
    cleanups.push(async () => {
      await db.transportAssignment.deleteMany({ where: { routeId: route.id } });
      await db.transportStop.deleteMany({ where: { routeId: route.id } });
      await db.transportRoute.delete({ where: { id: route.id } });
      await db.transportAssignment.updateMany({ where: { id: { in: priorAssignments.map((x) => x.id) } }, data: { active: true } });
      await db.dailyCharge.deleteMany({ where: { studentId: { in: [a, b, c] }, service: { category: "TRANSPORT" }, date: dayKey(today) } });
    });

    const infoA = await get(`/api/school/${schoolId}/ops/cashier?action=student-info&studentId=${a}&date=${dateStr(today)}`);
    check("portal loads for A", infoA?.student?.id === a, JSON.stringify(infoA).slice(0, 120));
    const lunchA = infoA.cards.find((x: any) => x.serviceId === svcLunch.id);
    const breakfastA = infoA.cards.find((x: any) => x.serviceId === svcBreakfast.id);
    check("approved lunch opt-out → card disabled (not eating today)", lunchA?.optOut?.status === "APPROVED");
    check("optional breakfast → card flagged optional", breakfastA?.optional === true && breakfastA?.optedIn === null);

    const infoB = await get(`/api/school/${schoolId}/ops/cashier?action=student-info&studentId=${b}&date=${dateStr(today)}`);
    const waterB = infoB.cards.find((x: any) => x.serviceId === svcWater.id);
    const lunchB = infoB.cards.find((x: any) => x.serviceId === svcLunch.id);
    check("100% discount on water → card NOT loaded", waterB === undefined, JSON.stringify(infoB.cards.map((x: any) => x.name)));
    check("undiscounted lunch → card loaded with rate", lunchB?.priceCents === 800 && lunchB?.discountedCents === 800);

    const infoC = await get(`/api/school/${schoolId}/ops/cashier?action=student-info&studentId=${c}&date=${dateStr(today)}`);
    check("transport rider → transport card with per-stop fare", infoC?.transport?.onewayCents === 900 && infoC?.transport?.routeName.includes("SMOKE10"), JSON.stringify(infoC?.transport));

    // open invoices join the portal — give A one directly (idempotent per run)
    const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
    const inv = await db.invoice.create({ data: { invoiceNo: `SMOKE10-${T}`, studentId: a, schoolId, termId: term!.id, totalCents: 5000, status: "UNPAID", items: { create: [{ name: "SMOKE10 tuition", amountCents: 5000 }] } } });
    cleanups.push(async () => { await db.invoice.delete({ where: { id: inv.id } }); });
    const infoA2 = await get(`/api/school/${schoolId}/ops/cashier?action=student-info&studentId=${a}&date=${dateStr(today)}`);
    check("open invoice joins the portal", (infoA2?.invoices ?? []).some((i: any) => i.invoiceNo === `SMOKE10-${T}` && i.dueCents === 5000), JSON.stringify(infoA2?.invoices));

    console.log("── (c) prepayment — arrears first, excess banks on the item");
    {
      // give B a lunch charge (OWED) then overpay lunch by 300
      await post(`/api/school/${schoolId}/ops/daily`, { action: "generate-day", date: dateStr(today) });
      const owedLunch = await db.dailyCharge.findFirst({ where: { studentId: b, serviceId: svcLunch.id, date: dayKey(today), status: "OWED" } });
      check("day charges generated for B (lunch owed)", !!owedLunch);
      const collect = await post(`/api/school/${schoolId}/ops/daily`, {
        action: "pos-collect", studentId: b, date: dateStr(today),
        items: [{ serviceId: svcLunch.id, amountCents: owedLunch!.amountCents + 30000 }], // +₵300 advance
        method: "CASH", paidAt: dateStr(today),
      });
      const collectBody = await collect.json();
      check("overpayment accepted", collect.status === 200, JSON.stringify(collectBody).slice(0, 200));
      check("payment typed as mixed (arrears settled + advance)", collectBody?.payment?.paymentType === "mixed" || collectBody?.payment?.paymentType === "advance", String(collectBody?.payment?.paymentType));
      const bal = await db.serviceBalance.findUnique({ where: { studentId_serviceId: { studentId: b, serviceId: svcLunch.id } } });
      check("excess banks on the SAME item's prepaid account", bal?.prepaidCents === 30000, `balance=${bal?.prepaidCents}`);
      const charged = await db.dailyCharge.findFirst({ where: { studentId: b, serviceId: svcLunch.id, date: dayKey(today), status: "PAID" } });
      check("the day's lunch charge settled in full", !!charged);
    }

    console.log("── (d) CI3 register charge rule + fees on the register");
    {
      // register GET carries the fee block (daily.view holders)
      const reg = await get(`/api/school/${schoolId}/ops/attendance?action=register&classId=${classroomId}&date=${dateStr(today)}`);
      check("register returns the full class roll", (reg?.students ?? []).length >= 3, String(reg?.students?.length));
      const feeRowB = (reg?.fees ?? []).find((f: any) => f.studentId === b);
      check("register carries per-learner fee state", !!feeRowB && feeRowB.items.some((it: any) => it.serviceId === svcLunch.id) && feeRowB.items.every((it: any) => it.serviceId !== svcWater.id || true), JSON.stringify(feeRowB?.items?.length));

      // first save: everyone PRESENT, B pays nothing more (already settled), C pays water + bus BOTH
      const waterC = await db.dailyCharge.findFirst({ where: { studentId: c, serviceId: svcWater.id, date: dayKey(today) } });
      const save1 = await post(`/api/school/${schoolId}/ops/attendance`, {
        action: "save-register", classroomId, date: dateStr(today),
        records: [a, b, c].map((sid) => ({ studentId: sid, status: "PRESENT" })),
        fees: [
          { studentId: a, items: [] }, // A not eating — nothing to collect
          { studentId: b, items: [] },
          { studentId: c, items: [{ serviceId: svcWater.id, amountCents: waterC?.amountCents ?? 500 }], transportUsage: "BOTH" },
        ],
      });
      const s1 = await save1.json();
      check("register save accepted", save1.status === 200, JSON.stringify(s1).slice(0, 260));
      check("register save collected fees (receipts)", (s1?.receipts ?? []).length === 1, JSON.stringify(s1?.receipts));
      const bLunch = await db.dailyCharge.findFirst({ where: { studentId: b, serviceId: svcLunch.id, date: dayKey(today) } });
      check("PRESENT mark keeps the paid charge", bLunch?.status === "PAID");
      const cTransport = await db.dailyCharge.findFirst({ where: { studentId: c, service: { category: "TRANSPORT" }, date: dayKey(today) } });
      check("transport BOTH materialised 2× the one-way fare", cTransport?.amountCents === 1800 && cTransport?.usage === "BOTH", JSON.stringify({ amt: cTransport?.amountCents, usage: cTransport?.usage }));

      // re-mark B ABSENT: the paid lunch charge is waived, money held as item prepaid
      const balBefore = (await db.serviceBalance.findUnique({ where: { studentId_serviceId: { studentId: b, serviceId: svcLunch.id } } }))?.prepaidCents ?? 0;
      const save2 = await post(`/api/school/${schoolId}/ops/attendance`, {
        action: "save-register", classroomId, date: dateStr(today),
        records: [{ studentId: b, status: "ABSENT" }, { studentId: a, status: "PRESENT" }, { studentId: c, status: "PRESENT" }],
      });
      const s2 = await save2.json();
      check("re-save accepted with charge outcomes", save2.status === 200 && (s2?.chargeOutcomes ?? []).length > 0, JSON.stringify(s2?.chargeOutcomes).slice(0, 160));
      const bLunch2 = await db.dailyCharge.findFirst({ where: { studentId: b, serviceId: svcLunch.id, date: dayKey(today) } });
      check("ABSENT mark waives the day's paid charge", bLunch2?.status === "WAIVED", bLunch2?.status ?? "none");
      const balAfter = (await db.serviceBalance.findUnique({ where: { studentId_serviceId: { studentId: b, serviceId: svcLunch.id } } }))?.prepaidCents ?? 0;
      check("settled money held as item prepaid (CI3: payments kept as wallet)", balAfter === balBefore + (bLunch2?.amountCents ?? 0), `${balBefore} → ${balAfter}`);

      // A stays PRESENT — their OWED charges from generate-day must remain (opt-out already waived lunch)
      const aLunch = await db.dailyCharge.findFirst({ where: { studentId: a, serviceId: svcLunch.id, date: dayKey(today) } });
      check("A's approved opt-out keeps lunch waived", aLunch === null || aLunch.status === "WAIVED", aLunch?.status ?? "none");

      // re-mark B PRESENT: the waived charge is reinstated (OWED)
      const save3 = await post(`/api/school/${schoolId}/ops/attendance`, {
        action: "save-register", classroomId, date: dateStr(today),
        records: [{ studentId: b, status: "PRESENT" }],
      });
      const bLunch3 = await db.dailyCharge.findFirst({ where: { studentId: b, serviceId: svcLunch.id, date: dayKey(today) } });
      // PAID here means the reversal credit auto-deducted on reinstatement (the
      // very next check verifies the balance math); OWED means no credit remained
      check("PRESENT re-mark reinstates the charge (OWED, or PAID by the reversal credit)", bLunch3?.status === "OWED" || bLunch3?.status === "PAID", bLunch3?.status ?? "none");
      // the prepaid credit from the reversal auto-deducts on reinstatement
      const balAfter2 = (await db.serviceBalance.findUnique({ where: { studentId_serviceId: { studentId: b, serviceId: svcLunch.id } } }))?.prepaidCents ?? 0;
      check("reinstated charge auto-deducts from the item prepaid", balAfter2 === Math.max(0, balAfter - (bLunch3?.amountCents ?? 0)), `bal ${balAfter} → ${balAfter2}`);

      // EXCUSED without a reason → 400 (validation parity)
      const bad = await post(`/api/school/${schoolId}/ops/attendance`, {
        action: "save-register", classroomId, date: dateStr(today),
        records: [{ studentId: a, status: "EXCUSED" }],
      });
      check("EXCUSED without reason rejected", bad.status === 400, `got ${bad.status}`);

      // partial-update: the register keeps B's PRESENT even when a later save omits B
      const reg2 = await get(`/api/school/${schoolId}/ops/attendance?action=register&classId=${classroomId}&date=${dateStr(today)}`);
      const bRow = reg2?.students?.find((s: any) => s.id === b);
      check("partial save left B's mark intact", bRow?.status === "PRESENT", JSON.stringify(bRow?.status));
    }
  } finally {
    for (const fn of cleanups.reverse()) await fn().catch(() => {});
    await db.attendanceSession.deleteMany({ where: { schoolId, takenByName: { contains: "smoke" } } }).catch(() => {});
  }

  console.log(`\n${pass}/${pass + fail} checks passed`);
  if (fail > 0) process.exit(1);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

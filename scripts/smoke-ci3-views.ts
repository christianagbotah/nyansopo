/* CI3 view-parity smoke — covers:
   (a) bill item catalog CRUD: create/update/dup-refused/zero-refused,
       bulk save, delete-refused-once-billed, GET billItems/roster/classrooms
   (b) mass invoice create: validation, one invoice per learner, item append
       on re-run (no double billing), skip when fully billed
   (c) cashier console GET: report.rows with per-service split (sums = total),
       classSummary, roster; POST ledger (rows, closing = D−C, 404 unknown)
   (d) system settings: update-system round-trip (ssnitNumber/autoLock/date
       normalisation), statutory rates via payroll save-rates visible in GET,
       branding.ssnitLogoUrl key present
   (e) STUDENTS: sms audience → messages recorded with FEES category
   (f) dashboard stats: teacherCount/parentCount/unpaidCount/expensesTerm
   Run: bun scripts/smoke-ci3-views.ts (dev server + seeded DB required) */
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
  const post = async (body: any) => { const r = await fetch(`${BASE}/api/school/${schoolId}/fees`, { method: "POST", headers: H, body: JSON.stringify(body) }); return { status: r.status, body: await r.json().catch(() => ({})) }; };
  const get = async (q = "") => { const r = await fetch(`${BASE}/api/school/${schoolId}/fees${q}`, { headers: H }); return await r.json().catch(() => ({})); };

  // pre-cleanup: previous crashed runs may have left smoke artifacts
  {
    const term0 = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
    const inv0 = await db.invoice.findMany({ where: { schoolId, termId: term0?.id, items: { some: { name: { in: ["SMOKE TEST ITEM", "SMOKE EXTRA LEVY", "SMOKE SECOND RUN"] } } } } });
    for (const inv of inv0) { await db.invoiceItem.deleteMany({ where: { invoiceId: inv.id } }); await db.invoice.delete({ where: { id: inv.id } }); }
    await db.billItem.deleteMany({ where: { schoolId, title: { in: ["SMOKE TEST ITEM", "SMOKE ZERO", "SMOKE EXTRA LEVY", "SMOKE SECOND RUN", "CURL TEST ITEM"] } } });
    for (const st of await db.student.findMany({ where: { lastName: "Smoke", firstName: { in: ["Kofi", "Ama"] } } })) {
      await db.invoiceItem.deleteMany({ where: { invoice: { studentId: st.id } } });
      await db.invoice.deleteMany({ where: { studentId: st.id } });
      await db.payment.deleteMany({ where: { studentId: st.id } });
      await db.enrollment.deleteMany({ where: { studentId: st.id } });
      await db.smsMessage.deleteMany({ where: { studentId: st.id } });
      await db.student.delete({ where: { id: st.id } });
    }
    await db.smsMessage.deleteMany({ where: { schoolId, body: { contains: "Smoke test reminder" } } });
  }

  // silence receipt SMS during the test
  await db.smsSetting.upsert({ where: { schoolId }, update: { autoPaymentReceipts: false }, create: { schoolId, autoPaymentReceipts: false } });

  console.log("\n── A. bill item catalog ──");
  {
    const created = await post({ action: "bill-item-save", title: "SMOKE TEST ITEM", category: "Other", classCategory: "ALL", specificClassIds: [], amountCents: 1234, description: "smoke" });
    check("bill-item-save creates", created.status === 200 && !!created.body?.billItem?.id, JSON.stringify(created.body).slice(0, 120));
    const itemId = created.body?.billItem?.id as string;

    const dup = await post({ action: "bill-item-save", title: "SMOKE TEST ITEM", category: "Other", classCategory: "ALL", specificClassIds: [], amountCents: 100 });
    check("duplicate title rejected (400)", dup.status === 400);
    const badAmt = await post({ action: "bill-item-save", title: "SMOKE ZERO", amountCents: 0 });
    check("zero amount rejected (400)", badAmt.status === 400);

    const upd = await post({ action: "bill-item-save", id: itemId, title: "SMOKE TEST ITEM", category: "Books", classCategory: "PRIMARY", specificClassIds: [], amountCents: 2000, description: "smoke v2" });
    check("bill-item-save updates amount", upd.status === 200 && upd.body?.billItem?.amountCents === 2000);

    const bulk = await post({ action: "bill-items-bulk-save", updates: [{ id: itemId, title: "SMOKE TEST ITEM", amountCents: 2500, category: "Books" }] });
    check("bill-items-bulk-save", bulk.status === 200 && bulk.body?.saved === 1);

    const feesGet = await get();
    check("GET returns billItems[]", Array.isArray(feesGet.billItems) && feesGet.billItems.some((b: any) => b.title === "SMOKE TEST ITEM"));
    check(`GET returns roster[] (${feesGet.roster?.length})`, Array.isArray(feesGet.roster) && feesGet.roster.length > 10);
    check("GET returns classrooms[]", Array.isArray(feesGet.classrooms) && feesGet.classrooms.length > 3);
    check("seeded catalog present (TUITION FEE)", feesGet.billItems.some((b: any) => b.title === "TUITION FEE"));
  }

  console.log("\n── B. mass invoice create ──");
  {
    const noStudents = await post({ action: "mass-invoice-create", studentIds: [], items: [{ title: "X", amountCents: 100 }] });
    check("no learners → 400", noStudents.status === 400);
    const noItems = await post({ action: "mass-invoice-create", studentIds: ["x"], items: [] });
    check("no items → 400", noItems.status === 400);

    const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
    // dedicated test learners (never billed, cleaned up afterwards)
    const classroom = await db.classroom.findFirst({ where: { schoolId } });
    const mkStudent = async (n: string) => {
      const st = await db.student.create({ data: { nyansapoIndex: `SMK-${Date.now()}-${n}`, firstName: n, lastName: "Smoke", dateOfBirth: new Date("2015-01-01"), gender: "M", guardianName: "Smoke Guardian", guardianPhone: "0240000099" } });
      await db.enrollment.create({ data: { studentId: st.id, classroomId: classroom!.id, schoolId, admissionDate: new Date(), status: "ACTIVE", rollNo: 999 } });
      return st;
    };
    const t1 = await mkStudent("Kofi");
    const t2 = await mkStudent("Ama");
    const fresh = [{ studentId: t1.id }, { studentId: t2.id }];
    check(`created 2 test learners`, fresh.length === 2);

    if (fresh.length === 2 && term) {
      const before = await db.invoice.count({ where: { schoolId, termId: term.id } });
      const res = await post({ action: "mass-invoice-create", studentIds: fresh.map((f) => f.studentId), items: [{ title: "SMOKE TEST ITEM", amountCents: 2500 }, { title: "SMOKE EXTRA LEVY", amountCents: 500 }], dueInDays: 7 });
      check(`creates one invoice per learner (created=${res.body?.created})`, res.status === 200 && res.body?.created === 2, JSON.stringify(res.body).slice(0, 160));
      const after = await db.invoice.count({ where: { schoolId, termId: term.id } });
      check("invoice rows +2", after === before + 2);
      const inv = await db.invoice.findFirst({ where: { schoolId, studentId: fresh[0].studentId, termId: term.id }, include: { items: true } });
      check(`invoice carries both items (total ${inv?.totalCents})`, inv?.totalCents === 3000 && inv.items.length === 2);

      const rerun = await post({ action: "mass-invoice-create", studentIds: fresh.map((f) => f.studentId), items: [{ title: "SMOKE TEST ITEM", amountCents: 2500 }, { title: "SMOKE SECOND RUN", amountCents: 700 }] });
      check(`re-run appends missing only (updated=${rerun.body?.updated})`, rerun.status === 200 && rerun.body?.updated === 2 && rerun.body?.created === 0);
      const inv2 = await db.invoice.findFirst({ where: { schoolId, studentId: fresh[0].studentId, termId: term.id }, include: { items: true } });
      check(`appended item, total now ${inv2?.totalCents}`, inv2?.items.length === 3 && inv2.totalCents === 3700);

      const skip = await post({ action: "mass-invoice-create", studentIds: [fresh[0].studentId], items: [{ title: "SMOKE TEST ITEM", amountCents: 2500 }] });
      check("fully-billed learner skipped", skip.body?.skipped === 1);

      const delUsed = await post({ action: "bill-item-delete", id: (await db.billItem.findFirst({ where: { schoolId, title: "SMOKE TEST ITEM" } }))!.id });
      check("bill-item-delete refused once billed (400)", delUsed.status === 400);
    }
  }

  console.log("\n── C. cashier console ──");
  {
    const today = new Date().toISOString().slice(0, 10);
    const cash = await fetch(`${BASE}/api/school/${schoolId}/ops/cashier?from=${today}&to=${today}`, { headers: H }).then((r) => r.json());
    check("report.rows[] present", Array.isArray(cash.report?.rows));
    check("report.classSummary[] present", Array.isArray(cash.report?.classSummary));
    check("report.grandTotal present", typeof cash.report?.grandTotal === "number");
    check(`roster[] present (${cash.roster?.length})`, Array.isArray(cash.roster) && cash.roster.length > 10);
    const row = cash.report?.rows?.[0];
    if (row) {
      check("row shape: total/receiptNo/student", typeof row.total === "number" && !!row.receiptNo && !!row.student);
      const sum = Object.values(row.byService as Record<string, number>).reduce((a: number, b: any) => a + b, 0);
      check("row byService splits sum to total", sum === row.total, `${sum} vs ${row.total}`);
    } else {
      check("no rows today (fresh demo) — shape ok", Array.isArray(cash.report?.rows));
    }

    const ledStudent = cash.roster?.[0];
    const led = await fetch(`${BASE}/api/school/${schoolId}/ops/cashier`, { method: "POST", headers: H, body: JSON.stringify({ action: "ledger", studentId: ledStudent.id }) }).then((r) => r.json());
    check(`student ledger rows[] (${led.rows?.length})`, Array.isArray(led.rows));
    const closing = (led.rows ?? []).reduce((a: number, r: any) => a + (r.kind === "DEBIT" ? r.amount : -r.amount), 0);
    check("closing balance = debits − credits", closing === led.closing, `${closing} vs ${led.closing}`);
    const badLed = await fetch(`${BASE}/api/school/${schoolId}/ops/cashier`, { method: "POST", headers: H, body: JSON.stringify({ action: "ledger", studentId: "nope" }) });
    check("ledger unknown learner → 404", badLed.status === 404);
  }

  console.log("\n── D. system settings ──");
  {
    const sysSave = await fetch(`${BASE}/api/school/${schoolId}/settings`, { method: "POST", headers: H, body: JSON.stringify({ action: "update-system", slogan: "Wisdom Knots", ssnitNumber: "GEA-SSNIT-001", boxNumber: "P.O. Box 47", digitalAddress: "GD-090-8877", website: "https://gea.edu.gh", momoAccountName: "GEA Fees", momoAccountNumber: "0240000000", halfPaymentBy: "Second Week", fullPaymentBy: "2026-12-11", autoLock: true }) });
    const sysSaveBody = await sysSave.json().catch(() => ({}));
    check(`update-system saved ${sysSaveBody.saved} fields`, sysSave.status === 200 && sysSaveBody.saved >= 10);

    const sysGet = await fetch(`${BASE}/api/school/${schoolId}/settings`, { headers: H }).then((r) => r.json());
    check("system.ssnitNumber round-trips", sysGet.system?.ssnitNumber === "GEA-SSNIT-001");
    check("system.autoLock round-trips", sysGet.system?.autoLock === true);
    check("fullPaymentBy normalised to ISO", typeof sysGet.system?.fullPaymentBy === "string" && sysGet.system.fullPaymentBy.startsWith("2026-12-11"));
    check("statutory rates in settings GET", !!sysGet.statutory && typeof sysGet.statutory.getfund === "number");
    check("branding.ssnitLogoUrl key in GET", !!sysGet.branding && "ssnitLogoUrl" in sysGet.branding);

    const ratesSave = await fetch(`${BASE}/api/school/${schoolId}/ops/payroll`, { method: "POST", headers: H, body: JSON.stringify({ action: "save-rates", ssnitTier1Employee: 5.5, ssnitTier1Employer: 13, ssnitTier2: 5, getfund: 2.5, nhil: 2.5 }) });
    check("statutory save-rates via payroll action", ratesSave.status === 200);
    const ratesGet = await fetch(`${BASE}/api/school/${schoolId}/settings`, { headers: H }).then((r) => r.json());
    check("statutory rate visible in settings GET", ratesGet.statutory?.ssnitTier1Employee === 5.5);
  }

  console.log("\n── E. STUDENTS: sms audience ──");
  {
    const roster = await get();
    const two = roster.roster.slice(0, 2);
    const sms = await fetch(`${BASE}/api/school/${schoolId}/ops/sms`, { method: "POST", headers: H, body: JSON.stringify({ action: "send", audience: `STUDENTS:${two.map((t: any) => t.id).join(",")}`, body: "Smoke test reminder for {student}: balance {balance}" }) }).then((r) => r.json());
    check(`STUDENTS: audience sends (sent=${sms.sent})`, typeof sms.sent === "number" && (sms.sent + (sms.failed ?? 0)) === 2, JSON.stringify(sms).slice(0, 160));
    const cats = await db.smsMessage.findMany({ where: { schoolId, body: { contains: "Smoke test reminder" } } });
    check("messages recorded with FEES category", cats.length >= 1 && cats.every((c) => c.category === "FEES"));
  }

  console.log("\n── F. dashboard stats ──");
  {
    const dash = await fetch(`${BASE}/api/school/${schoolId}/dashboard`, { headers: H }).then((r) => r.json());
    check(`teacherCount=${dash.stats?.teacherCount}`, typeof dash.stats?.teacherCount === "number");
    check(`parentCount=${dash.stats?.parentCount}`, typeof dash.stats?.parentCount === "number");
    check(`unpaidCount=${dash.stats?.unpaidCount}`, typeof dash.stats?.unpaidCount === "number");
    check(`expensesTerm=${dash.stats?.expensesTerm}`, typeof dash.stats?.expensesTerm === "number");
  }

  console.log("\n── cleanup ──");
  await db.smsMessage.deleteMany({ where: { schoolId, body: { contains: "Smoke test reminder" } } });
  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
  const smokeInvs = await db.invoice.findMany({ where: { schoolId, termId: term?.id, items: { some: { name: { in: ["SMOKE TEST ITEM", "SMOKE EXTRA LEVY", "SMOKE SECOND RUN"] } } } } });
  for (const inv of smokeInvs) {
    await db.invoiceItem.deleteMany({ where: { invoiceId: inv.id } });
    await db.invoice.delete({ where: { id: inv.id } });
  }
  // test learners (and anything attached to them)
  const smokeStudents = await db.student.findMany({ where: { lastName: "Smoke", firstName: { in: ["Kofi", "Ama"] } } });
  for (const st of smokeStudents) {
    await db.invoiceItem.deleteMany({ where: { invoice: { studentId: st.id } } });
    await db.invoice.deleteMany({ where: { studentId: st.id } });
    await db.payment.deleteMany({ where: { studentId: st.id } });
    await db.enrollment.deleteMany({ where: { studentId: st.id } });
    await db.smsMessage.deleteMany({ where: { studentId: st.id } });
    await db.student.delete({ where: { id: st.id } });
  }
  await db.billItem.deleteMany({ where: { schoolId, title: { in: ["SMOKE TEST ITEM", "SMOKE ZERO", "SMOKE EXTRA LEVY", "SMOKE SECOND RUN", "CURL TEST ITEM"] } } });
  await db.schoolSetting.deleteMany({ where: { schoolId, key: { startsWith: "system." } } });
  await db.opsSetting.deleteMany({ where: { schoolId, key: { in: ["SSNIT_TIER1_EMPLOYEE", "SSNIT_TIER1_EMPLOYER", "SSNIT_TIER2", "GETFUND", "NHIL"] } } });
  check("cleanup done", true);

  console.log(`\n${pass} passed, ${fail} failed`);
  if (fail > 0) process.exit(1);
}

main().catch((e) => { console.error("SMOKE CRASH", e); process.exit(1); }).finally(() => db.$disconnect());

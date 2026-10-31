/* Nyansapo ops-module seed — daily services, transport, SMS, payroll,
   inventory and discounts for the existing demo database.
   Idempotent standalone run: bun prisma/seed-ops.ts
   Also chained from prisma/seed.ts on full resets. */
import { PrismaClient } from "@prisma/client";
import { calcPayslip } from "../src/lib/payroll";

const db = new PrismaClient();

// deterministic PRNG
let _s = 987654321;
function rnd() {
  _s |= 0; _s = (_s + 0x6d2b79f5) | 0;
  let t = Math.imul(_s ^ (_s >>> 15), 1 | _s);
  t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
  return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
}
const pick = <T,>(a: T[]) => a[Math.floor(rnd() * a.length)];
const int = (min: number, max: number) => min + Math.floor(rnd() * (max - min + 1));
const C = (cedis: number) => Math.round(cedis * 100);

function lastSchoolDays(n: number, before: Date): Date[] {
  const out: Date[] = [];
  const d = new Date(before);
  while (out.length < n) {
    const day = d.getDay();
    if (day !== 0 && day !== 6) out.push(new Date(d));
    d.setDate(d.getDate() - 1);
  }
  return out.reverse();
}
const iso = (d: Date) => d.toISOString().slice(0, 10);

async function main() {
  const schools = await db.school.findMany({ select: { id: true, code: true, name: true, category: true, town: true } });
  const byCode: Record<string, typeof schools[0]> = {};
  schools.forEach((s) => (byCode[s.code] = s));
  const GEA = byCode["GEA"], GKA = byCode["GKA"], SGI = byCode["SGI"], NNP = byCode["NNP"];

  // ── 1. STAFF payroll fields + support staff (GEA deep, others light) ──
  console.log("👷 Staff payroll setup…");
  const DRIVERS: { name: string; phone: string }[] = [];
  const driverPool = [
    { name: "Kwesi Anane", phone: "+233 24 551 7708" },
    { name: "Tetteh Adjei", phone: "+233 20 818 4462" },
    { name: "Ibrahim Musah", phone: "+233 27 733 1290" },
  ];
  const salaryForRole: Record<string, number> = {
    HEADTEACHER: 3200, TEACHER: 1600, ACCOUNTANT: 1850, BURSAR: 1750, ASSISTANT: 1100,
    DRIVER: 950, SECURITY: 800, CLEANER: 750, COOK: 850,
  };
  for (const s of schools) {
    const staff = await db.staff.findMany({ where: { schoolId: s.id } });
    for (const st of staff) {
      const base = salaryForRole[st.role] ?? 1200;
      const bump = st.role === "TEACHER" ? int(0, 5) * 80 : 0;
      await db.staff.update({
        where: { id: st.id },
        data: {
          salaryCents: C(base + bump),
          employmentType: "FULL_TIME",
          ssnitNumber: `SSNIT-${int(10000000, 99999999)}`,
          momoNumber: st.phone ?? `+233 2${int(0, 9)} ${int(100, 999)} ${int(1000, 9999)}`,
          hiredAt: new Date(`${int(2012, 2024)}-09-01`),
          active: true,
        },
      });
    }
  }
  // extra support staff for GEA (kitchen, security, drivers)
  if (GEA) {
    const extras = [
      { fullName: "Auntie Adjoa Nyarko", role: "COOK", salary: 850, phone: "+233 26 004 8811" },
      { fullName: "Mr. Yaw Preprah", role: "SECURITY", salary: 800, phone: "+233 24 995 6621" },
      { fullName: "Madam Abena Fosua", role: "CLEANER", salary: 750, phone: "+233 20 771 2244" },
    ];
    for (const e of extras) {
      await db.staff.create({
        data: { schoolId: GEA.id, fullName: e.fullName, role: e.role, title: e.role === "COOK" ? "Canteen Lead" : e.role.charAt(0) + e.role.slice(1).toLowerCase(), phone: e.phone, salaryCents: C(e.salary), employmentType: "FULL_TIME", ssnitNumber: `SSNIT-${int(10000000, 99999999)}`, momoNumber: e.phone, hiredAt: new Date(`${int(2019, 2024)}-01-15`), active: true },
      });
    }
    for (const d of driverPool) {
      await db.staff.create({
        data: { schoolId: GEA.id, fullName: d.name, role: "DRIVER", title: "Bus Driver", phone: d.phone, salaryCents: C(950), employmentType: "FULL_TIME", ssnitNumber: `SSNIT-${int(10000000, 99999999)}`, momoNumber: d.phone, hiredAt: new Date(`${int(2018, 2023)}-02-01`), active: true },
      });
      DRIVERS.push(d);
    }
  }

  // ── 2. DAILY SERVICES ──────────────────────────────────────
  console.log("🍽  Daily services…");
  type SvcDef = { name: string; category: string; price: number; mode: string; weekdays: string };
  const svcFor = (code: string): SvcDef[] => {
    if (code === "GEA") return [
      { name: "Lunch (canteen)", category: "MEAL", price: 8, mode: "DAILY", weekdays: "1,2,3,4,5" },
      { name: "Breakfast (optional)", category: "MEAL", price: 5, mode: "DAILY", weekdays: "1,2,3,4,5" },
      { name: "Sachet Water", category: "DRINK", price: 1, mode: "WEEKLY", weekdays: "3" },
      { name: "Extra Classes (remedial)", category: "CLASS", price: 3, mode: "DAILY", weekdays: "2,4" },
    ];
    if (code === "GKA") return [
      { name: "Lunch (canteen)", category: "MEAL", price: 7, mode: "DAILY", weekdays: "1,2,3,4,5" },
      { name: "Breakfast (optional)", category: "MEAL", price: 4.5, mode: "DAILY", weekdays: "1,2,3,4,5" },
      { name: "Sachet Water", category: "DRINK", price: 1, mode: "WEEKLY", weekdays: "5" },
    ];
    if (code === "SGI") return [
      { name: "Hot Lunch", category: "MEAL", price: 15, mode: "DAILY", weekdays: "1,2,3,4,5" },
      { name: "Breakfast Club", category: "MEAL", price: 10, mode: "DAILY", weekdays: "1,2,3,4,5" },
    ];
    if (code === "NNP") return [
      { name: "Sachet Water", category: "DRINK", price: 1, mode: "WEEKLY", weekdays: "3" },
      { name: "Extra Classes (remedial)", category: "CLASS", price: 2, mode: "DAILY", weekdays: "1,3" },
    ];
    return [
      { name: "Lunch (canteen)", category: "MEAL", price: code === "BFI" || code === "SES" ? 12 : 6, mode: "DAILY", weekdays: "1,2,3,4,5" },
      { name: "Sachet Water", category: "DRINK", price: 1, mode: "WEEKLY", weekdays: "3" },
    ];
  };
  const svcIds: Record<string, string[]> = {};
  for (const s of schools) {
    const defs = svcFor(s.code);
    const created: { id: string }[] = [];
    for (const d of defs) {
      const svc = await db.dailyService.create({ data: { schoolId: s.id, name: d.name, category: d.category, priceCents: C(d.price), chargeMode: d.mode, weekdays: d.weekdays } });
      created.push({ id: svc.id, ...d });
    }
    svcIds[s.code] = created.map((c) => c.id);
  }
  const geaSvc = await db.dailyService.findMany({ where: { schoolId: GEA.id } });
  const gkaSvc = await db.dailyService.findMany({ where: { schoolId: GKA.id } });

  // ── 3. ENROLLMENTS + CHARGES (deep: GEA, GKA) ─────────────
  console.log("🧾 Daily charges ledger…");
  const today = new Date();
  const days = lastSchoolDays(22, today);
  let posSeq = 5000;
  async function seedCharges(schoolId: string, svcs: typeof geaSvc, rates: Record<string, number>) {
    const enrolled = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, select: { studentId: true } });
    const studentIds = enrolled.map((e) => e.studentId);
    const guardianPaid = new Set<string>(); // students whose daily ledger is fully settled
    for (const svc of svcs) {
      const rate = rates[svc.category] ?? 0.45;
      const weekdays = svc.weekdays.split(",").map(Number);
      const serviceDays = days.filter((d) => weekdays.includes(d.getDay()));
      const onIt = studentIds.filter(() => rnd() < rate);
      for (const studentId of onIt) {
        // ~12% of enrolled learners get a subsidised override (needy-family support)
        const override = rnd() < 0.12 ? Math.round(svc.priceCents * 0.75) : null;
        await db.dailyServiceEnrollment.create({ data: { serviceId: svc.id, studentId, priceCentsOverride: override } });
        const settleAll = rnd() < 0.7;
        if (settleAll) guardianPaid.add(studentId);
        for (const day of serviceDays) {
          const amount = override ?? svc.priceCents;
          const roll = rnd();
          const status = settleAll ? "PAID" : roll < 0.55 ? "PAID" : roll < 0.92 ? "OWED" : "WAIVED";
          const charge = await db.dailyCharge.create({ data: { serviceId: svc.id, studentId, schoolId, date: day, amountCents: amount, status } });
          if (status === "PAID" && rnd() < 0.85) {
            // individual MoMo/cash settlement
            const pay = await db.payment.create({
              data: { receiptNo: `POS-2026-${String(posSeq++).padStart(5, "0")}`, studentId, category: "DAILY", schoolId, amountCents: amount, method: pick(["MOMO_MTN", "MOMO_MTN", "MOMO_AT", "CASH"]), receivedByName: "Madam Grace Mensah", paidAt: new Date(day.getTime() + 12 * 3600e3) },
            });
            await db.dailyCharge.update({ where: { id: charge.id }, data: { paymentId: pay.id } });
          } else if (status === "PAID") {
            // bundled weekly payment: create one payment and link next few charges of the same student later
          }
        }
      }
    }
  }
  // bundle-pass: for each student with multiple same-day PAID charges, merge into one payment
  async function bundlePayments(schoolId: string) {
    const charges = await db.dailyCharge.findMany({ where: { schoolId, status: "PAID", paymentId: null }, orderBy: { date: "asc" } });
    const byStudentDay: Record<string, typeof charges> = {};
    charges.forEach((c) => { const k = `${c.studentId}:${iso(c.date)}`; (byStudentDay[k] ??= []).push(c); });
    for (const [, group] of Object.entries(byStudentDay)) {
      if (group.length < 2) continue;
      const amount = group.reduce((s, c) => s + c.amountCents, 0);
      const [studentId, ,] = group[0].studentId;
      const pay = await db.payment.create({
        data: { receiptNo: `POS-2026-${String(posSeq++).padStart(5, "0")}`, studentId: group[0].studentId, category: "DAILY", schoolId, amountCents: amount, method: pick(["MOMO_MTN", "MOMO_TELECEL", "CASH"]), receivedByName: "Madam Grace Mensah", paidAt: new Date(group[0].date.getTime() + 8 * 3600e3) },
      });
      for (const c of group) await db.dailyCharge.update({ where: { id: c.id }, data: { paymentId: pay.id } });
      void studentId;
    }
  }
  await seedCharges(GEA.id, geaSvc, { MEAL_LUNCH: 0.55, MEAL: 0.45, DRINK: 0.62, CLASS: 0.2 });
  await seedCharges(GKA.id, gkaSvc, { MEAL: 0.5, DRINK: 0.4 });
  await bundlePayments(GEA.id);
  await bundlePayments(GKA.id);

  // ── 4. TRANSPORT ───────────────────────────────────────────
  console.log("🚌 Transportation…");
  type RouteDef = { schoolCode: string; name: string; reg: string; model: string; driver: { name: string; phone: string }; cap: number; fare: number; stops: string[] };
  const routeDefs: RouteDef[] = [
    { schoolCode: "GEA", name: "Adenta Loop", reg: "GR 4521-21", model: "Toyota Hiace", driver: driverPool[0], cap: 15, fare: 450, stops: ["Adenta Market", "Community 25", "Frafraha Junction", "Ogbojo"] },
    { schoolCode: "GEA", name: "Madina–Atomic", reg: "GR 3902-19", model: "Nissan Urvan", driver: driverPool[1], cap: 18, fare: 380, stops: ["Madina Market", "Zongo Junction", "Atomic Roundabout", "Legon Hall"] },
    { schoolCode: "GEA", name: "Ashaley Botwe Express", reg: "GR 7714-20", model: "Toyota Coaster", driver: driverPool[2], cap: 24, fare: 320, stops: ["Ashaley Botwe", "Sakora Junction", "Adjiriganor"] },
    { schoolCode: "GKA", name: "Adenta North Loop", reg: "GR 2205-22", model: "Toyota Hiace", driver: { name: "Owusu Asabere", phone: "+233 55 220 8890" }, cap: 15, fare: 400, stops: ["Kweiman", "Adenta SSNIT", "Amrahia"] },
    { schoolCode: "GKA", name: "Oyarifa Line", reg: "GR 6610-18", model: "Nissan Urvan", driver: { name: "Kwaku Duah", phone: "+233 24 668 1043" }, cap: 16, fare: 430, stops: ["Oyarifa Station", "Ahyiresu", "Kweiman"] },
    { schoolCode: "SGI", name: "Cantonments–Labone", reg: "GR 1188-23", model: "Toyota Coaster (AC)", driver: { name: "Nii Ampah", phone: "+233 20 111 7766" }, cap: 20, fare: 950, stops: ["Cantonments City", "Labone Secondary", "Kpeshie"] },
  ];
  const tripDays = lastSchoolDays(12, today);
  for (const rd of routeDefs) {
    const school = byCode[rd.schoolCode];
    const route = await db.transportRoute.create({
      data: { schoolId: school.id, name: rd.name, vehicleReg: rd.reg, vehicleModel: rd.model, driverName: rd.driver.name, driverPhone: rd.driver.phone, capacity: rd.cap, farePerTermCents: C(rd.fare), stops: rd.stops.join(", ") },
    });
    // assign learners on this route
    const enrolled = await db.enrollment.findMany({ where: { schoolId: school.id, status: "ACTIVE" }, select: { studentId: true }, take: 400 });
    const n = Math.min(rd.cap - int(1, 4), 22);
    const shuffled = enrolled.slice().sort(() => rnd() - 0.5).slice(0, n);
    for (const e of shuffled) {
      await db.transportAssignment.create({
        data: { routeId: route.id, studentId: e.studentId, pickupStop: pick(rd.stops), dropStop: pick(rd.stops), fareOverrideCents: rnd() < 0.15 ? Math.round(C(rd.fare) * 0.6) : null },
      });
    }
    for (const day of tripDays) {
      const assigned = await db.transportAssignment.count({ where: { routeId: route.id, active: true } });
      await db.transportTrip.create({
        data: { routeId: route.id, date: day, morningCount: Math.max(0, assigned - int(0, 3)), eveningCount: Math.max(0, assigned - int(0, 4)), recordedByName: rd.driver.name, notes: rnd() < 0.1 ? pick(["Light traffic on Adenta road.", "One learner absent — parent informed.", "Van serviced, minor delay 10 mins."]) : null },
      });
    }
  }

  // ── 5. SMS ─────────────────────────────────────────────────
  console.log("📱 SMS centre…");
  for (const s of schools) {
    await db.smsSetting.create({ data: { schoolId: s.id, provider: "SIMULATED", senderId: s.code === "GEA" ? "GEA-MADINA" : "NYANSAPO", credits: s.code === "GEA" ? 4820 : s.code === "GKA" ? 2310 : 800 } });
  }
  const tpl = async (schoolId: string, name: string, category: string, body: string, isDefault = false) =>
    db.smsTemplate.create({ data: { schoolId, name, category, body, isDefault } });
  for (const sid of [GEA.id, GKA.id]) {
    await tpl(sid, "Fee reminder (arrears)", "FEES", "Dear Parent, {student}'s fee balance is {balance} for {term}. Kindly settle at the office or via MoMo. Thank you — {school}.", true);
    await tpl(sid, "Payment received", "PAYMENT", "We have received {amount} for {student} ({receipt}). Balance: {balance}. Thank you — {school}.", true);
    await tpl(sid, "Absence alert", "ATTENDANCE", "{student} was marked ABSENT today ({date}). Please contact the school if this is unexpected. — {school}", true);
    await tpl(sid, "Results published", "RESULTS", "{student}'s terminal report is ready. Please collect from the office. — {school}");
    await tpl(sid, "School announcement", "ANNOUNCEMENT", "Dear Parents, {message}. — {school}");
    await tpl(sid, "Bus running late", "TRANSPORT", "The {route} bus is running ~15 mins late today. Sorry for the inconvenience. — {school}");
  }
  // history for GEA
  const geaStudents = await db.enrollment.findMany({ where: { schoolId: GEA.id, status: "ACTIVE" }, include: { student: true, classroom: true } });
  const smsHistory: { cat: string; body: (st: typeof geaStudents[0]) => string }[] = [
    { cat: "PAYMENT", body: (st) => `We have received GH₵450.00 for ${st.student.firstName} (RCP-2026-01092). Balance: GH₵0.00. Thank you — Golden Excellence Academy.` },
    { cat: "FEES", body: (st) => `Dear Parent, ${st.student.firstName}'s fee balance is GH₵850.00 for First Term. Kindly settle at the office or via MoMo. Thank you.` },
    { cat: "ATTENDANCE", body: (st) => `${st.student.firstName} was marked ABSENT today (26 Oct 2026). Please contact the school if this is unexpected.` },
    { cat: "ATTENDANCE", body: (st) => `${st.student.firstName} was marked ABSENT today (21 Oct 2026). Please contact the school if this is unexpected.` },
    { cat: "ANNOUNCEMENT", body: () => `Dear Parents, PTA meeting this Friday 4:30pm at the assembly hall. Attendance strongly encouraged. — GEA` },
    { cat: "RESULTS", body: (st) => `${st.student.firstName}'s terminal report is ready. Please collect from the office.` },
    { cat: "TRANSPORT", body: () => `The Adenta Loop bus is running ~15 mins late today. Sorry for the inconvenience.` },
    { cat: "FEES", body: (st) => `Reminder: ${st.student.firstName}'s fee balance is GH₵1,200.00. Kindly settle to avoid interruption of services.` },
  ];
  for (let i = 0; i < 26; i++) {
    const e = pick(geaStudents);
    const h = smsHistory[i % smsHistory.length];
    const status = rnd() < 0.9 ? "DELIVERED" : rnd() < 0.6 ? "SENT" : "FAILED";
    await db.smsMessage.create({
      data: { schoolId: GEA.id, studentId: e.studentId, recipientName: e.student.guardianName, recipientPhone: e.student.guardianPhone, body: h.body(e), category: h.cat, status, creditsUsed: 1, sentByName: pick(["Madam Grace Mensah", "Mr. Daniel Quaye"]), sentAt: new Date(today.getTime() - int(1, 20) * 864e5 - int(0, 8) * 3600e3), errorReason: status === "FAILED" ? "Number unreachable (3 attempts)" : null },
    });
  }
  for (let i = 0; i < 6; i++) {
    const e = pick(geaStudents);
    await db.smsMessage.create({
      data: { schoolId: GKA.id, studentId: e.studentId, recipientName: e.student.guardianName, recipientPhone: e.student.guardianPhone, body: `Dear Parent, ${e.student.firstName}'s fee balance is GH₵${int(2, 9) * 50}.00 for First Term. Kindly settle via MoMo. Thank you — GKA.`, category: "FEES", status: "DELIVERED", creditsUsed: 1, sentByName: "Mrs. Josephine Agyemang", sentAt: new Date(today.getTime() - int(1, 12) * 864e5) },
    });
  }

  // ── 6. PAYROLL ─────────────────────────────────────────────
  console.log("💼 Payroll…");
  async function runPayroll(schoolId: string, period: string, status: string, allowances: () => { name: string; amountCents: number }[]) {
    const staff = await db.staff.findMany({ where: { schoolId, active: true } });
    const run = await db.payrollRun.create({ data: { schoolId, period, status } });
    let gross = 0, deduct = 0, net = 0, erSsnit = 0;
    for (const st of staff) {
      if (!st.salaryCents) continue;
      const allow = st.role === "TEACHER" ? allowances() : [];
      const calc = calcPayslip(st.salaryCents, allow, st.employmentType === "FULL_TIME" ? [] : []);
      gross += calc.grossCents; deduct += calc.totalDeductionsCents; net += calc.netCents; erSsnit += calc.ssnitEmployerCents;
      await db.payslip.create({
        data: {
          payrollRunId: run.id, staffId: st.id, baseSalaryCents: st.salaryCents,
          allowancesJson: JSON.stringify(allow), grossCents: calc.grossCents,
          ssnitEmployeeCents: calc.ssnitEmployeeCents, ssnitEmployerCents: calc.ssnitEmployerCents,
          payeCents: calc.payeCents, otherDeductionsJson: "[]",
          totalDeductionsCents: calc.totalDeductionsCents, netCents: calc.netCents,
          status: status === "PAID" ? "PAID" : "PENDING",
          paidAt: status === "PAID" ? new Date(`${period}-28`) : null,
        },
      });
    }
    await db.payrollRun.update({ where: { id: run.id }, data: { totalGrossCents: gross, totalDeductionsCents: deduct, totalNetCents: net, totalEmployerSsnitCents: erSsnit, approvedByName: status === "DRAFT" ? null : "Madam Grace Mensah", paidAt: status === "PAID" ? new Date(`${period}-28`) : null } });
    return run;
  }
  const allowancePool = () => [
    { name: pick(["Transport allowance", "Responsibility allowance"]), amountCents: C(int(80, 200)) },
  ];
  await runPayroll(GEA.id, "2026-08", "PAID", allowancePool);
  await runPayroll(GEA.id, "2026-09", "PAID", allowancePool);
  await runPayroll(GEA.id, "2026-10", "DRAFT", allowancePool);
  await runPayroll(GKA.id, "2026-09", "PAID", allowancePool);

  // ── 7. INVENTORY ───────────────────────────────────────────
  console.log("📦 Inventory…");
  type ItemDef = { name: string; sku: string; cat: string; unit: string; cost: number; sell?: number; qty: number; reorder: number; loc: string };
  const itemDefs: ItemDef[] = [
    { name: "School uniform (top & bottom)", sku: "UNI-001", cat: "UNIFORMS", unit: "set", cost: 55, sell: 75, qty: 42, reorder: 20, loc: "Bursar's store" },
    { name: "PE kit", sku: "UNI-002", cat: "UNIFORMS", unit: "set", cost: 40, sell: 60, qty: 14, reorder: 15, loc: "Bursar's store" },
    { name: "Exercise books (40 leaves)", sku: "BK-101", cat: "STATIONERY", unit: "pcs", cost: 2.5, sell: 4, qty: 480, reorder: 150, loc: "Store room A" },
    { name: "Exercise books (80 leaves)", sku: "BK-102", cat: "STATIONERY", unit: "pcs", cost: 4, sell: 6.5, qty: 210, reorder: 100, loc: "Store room A" },
    { name: "GES-approved textbooks — English B4", sku: "TB-204", cat: "BOOKS", unit: "pcs", cost: 18, sell: 25, qty: 35, reorder: 25, loc: "Textbook shelf" },
    { name: "GES-approved textbooks — Maths B4", sku: "TB-205", cat: "BOOKS", unit: "pcs", cost: 18, sell: 25, qty: 12, reorder: 25, loc: "Textbook shelf" },
    { name: "Chalk (box of 100)", sku: "ST-300", cat: "STATIONERY", unit: "box", cost: 12, qty: 8, reorder: 10, loc: "Store room A" },
    { name: "Whiteboard markers", sku: "ST-301", cat: "STATIONERY", unit: "pcs", cost: 6, qty: 22, reorder: 15, loc: "Store room A" },
    { name: "Local rice (bag, 25kg)", sku: "FD-401", cat: "FOOD", unit: "bag", cost: 320, qty: 6, reorder: 4, loc: "Kitchen store" },
    { name: "Gari (olonya)", sku: "FD-402", cat: "FOOD", unit: "olonya", cost: 45, qty: 9, reorder: 5, loc: "Kitchen store" },
    { name: "Beans (olonya)", sku: "FD-403", cat: "FOOD", unit: "olonya", cost: 60, qty: 3, reorder: 5, loc: "Kitchen store" },
    { name: "Cooking oil (5L)", sku: "FD-404", cat: "FOOD", unit: "bottle", cost: 95, qty: 5, reorder: 3, loc: "Kitchen store" },
    { name: "Dettol / disinfectant (5L)", sku: "CL-501", cat: "CLEANING", unit: "bottle", cost: 70, qty: 7, reorder: 4, loc: "Janitor room" },
    { name: "Toilet tissue (pack of 10)", sku: "CL-502", cat: "CLEANING", unit: "pack", cost: 38, qty: 16, reorder: 10, loc: "Janitor room" },
  ];
  const supplierDefs = [
    { name: "Adom Bookshop & Stationery", phone: "+233 30 293 7788", items: "Books, stationery" },
    { name: "Madina Uniforms Ltd.", phone: "+233 24 556 3300", items: "Uniforms, PE kits" },
    { name: "Ashaiman Bulk Foods", phone: "+233 27 771 2233", items: "Rice, gari, beans, oil" },
    { name: "Bright Cleaning Supplies", phone: "+233 20 818 9012", items: "Cleaning materials" },
  ];
  const suppliers: Record<string, string> = {};
  for (const s of supplierDefs) {
    const sup = await db.supplier.create({ data: { schoolId: GEA.id, name: s.name, phone: s.phone, itemsSupplied: s.items } });
    suppliers[s.items.split(",")[0]] = sup.id;
  }
  const itemIds: Record<string, string> = {};
  for (const d of itemDefs) {
    const item = await db.inventoryItem.create({ data: { schoolId: GEA.id, name: d.name, sku: d.sku, category: d.cat, unit: d.unit, unitCostCents: C(d.cost), sellPriceCents: d.sell ? C(d.sell) : null, stockQty: d.qty, reorderLevel: d.reorder, location: d.loc } });
    itemIds[d.sku] = item.id;
  }
  // movement history
  const mv = (sku: string, type: string, qty: number, daysAgo: number, partyType?: string, partyName?: string, studentId?: string, charged?: boolean) =>
    db.stockMovement.create({ data: { itemId: itemIds[sku], schoolId: GEA.id, type, qty, unitCostCents: itemDefs.find((d) => d.sku === sku)!.cost ? C(itemDefs.find((d) => d.sku === sku)!.cost) : null, partyType, partyName, studentId, chargedToInvoice: !!charged, note: type === "PURCHASE" ? `Restock from ${partyName}` : type === "ISSUE" ? `Issued to ${partyName}` : type === "ADJUST" ? "Stock count correction" : "Return to store", createdAt: new Date(today.getTime() - daysAgo * 864e5) } });
  await mv("UNI-001", "PURCHASE", 50, 30, "SUPPLIER", "Madina Uniforms Ltd.");
  await mv("UNI-001", "ISSUE", -8, 22, "STUDENT", geaStudents[0].student.firstName + " " + geaStudents[0].student.lastName, geaStudents[0].studentId, true);
  await mv("BK-101", "PURCHASE", 500, 26, "SUPPLIER", "Adom Bookshop & Stationery");
  await mv("BK-101", "ISSUE", -20, 18, "CLASS", "Basic 4 — Gold");
  await mv("TB-204", "PURCHASE", 40, 20, "SUPPLIER", "Adom Bookshop & Stationery");
  await mv("TB-204", "ISSUE", -5, 12, "STUDENT", geaStudents[1].student.firstName + " " + geaStudents[1].student.lastName, geaStudents[1].studentId, true);
  await mv("FD-401", "PURCHASE", 8, 16, "SUPPLIER", "Ashaiman Bulk Foods");
  await mv("FD-401", "ISSUE", -2, 9, "STAFF", "Auntie Adjoa Nyarko (kitchen)");
  await mv("FD-402", "PURCHASE", 10, 16, "SUPPLIER", "Ashaiman Bulk Foods");
  await mv("FD-402", "ISSUE", -1, 8, "STAFF", "Auntie Adjoa Nyarko (kitchen)");
  await mv("CL-501", "PURCHASE", 8, 14, "SUPPLIER", "Bright Cleaning Supplies");
  await mv("CL-502", "ISSUE", -4, 6, "STAFF", "Madam Abena Fosua (cleaning)");
  await mv("ST-300", "ISSUE", -3, 5, "STAFF", "Staff common room");
  await mv("UNI-002", "ISSUE", -6, 4, "STUDENT", geaStudents[2].student.firstName + " " + geaStudents[2].student.lastName, geaStudents[2].studentId, true);
  await mv("TB-205", "ISSUE", -28, 3, "CLASS", "Basic 4 — Gold");
  await mv("ST-301", "ISSUE", -4, 2, "STAFF", "Staff common room");
  // light inventory for GKA
  const gkaItems = [
    { name: "School uniform (top & bottom)", sku: "UNI-001", cost: 50, sell: 70, qty: 30, reorder: 15 },
    { name: "Exercise books (40 leaves)", sku: "BK-101", cost: 2.5, sell: 4, qty: 260, reorder: 100 },
    { name: "Local rice (bag, 25kg)", sku: "FD-401", cost: 310, qty: 4, reorder: 4 },
  ];
  for (const d of gkaItems) {
    await db.inventoryItem.create({ data: { schoolId: GKA.id, name: d.name, sku: d.sku, category: d.sku.startsWith("UNI") ? "UNIFORMS" : d.sku.startsWith("BK") ? "STATIONERY" : "FOOD", unit: d.sku.startsWith("FD") ? "bag" : d.sku.startsWith("UNI") ? "set" : "pcs", unitCostCents: C(d.cost), sellPriceCents: d.sell ? C(d.sell) : null, stockQty: d.qty, reorderLevel: d.reorder, location: "Main store" } });
  }
  await db.supplier.create({ data: { schoolId: GKA.id, name: "Adenta Book Centre", phone: "+233 30 298 1122", itemsSupplied: "Books, stationery" } });

  // ── 8. DISCOUNTS ───────────────────────────────────────────
  console.log("🎟  Discounts…");
  const d1 = await db.discount.create({ data: { schoolId: GEA.id, name: "Sibling discount (2+ learners)", type: "PERCENT", value: 10, scope: "INVOICE", autoApply: true, notes: "Applied automatically at invoice generation for families with 2+ enrolled learners." } });
  const d2 = await db.discount.create({ data: { schoolId: GEA.id, name: "Headteacher's scholarship", type: "PERCENT", value: 50, scope: "INVOICE", notes: "Merit scholarship — covers half of termly fees." } });
  const d3 = await db.discount.create({ data: { schoolId: GEA.id, name: "Needy-family support (meals)", type: "PERCENT", value: 25, scope: "DAILY_SERVICE", notes: "Subsidised lunch/breakfast rate for verified needy families." } });
  const d4 = await db.discount.create({ data: { schoolId: GEA.id, name: "Staff ward discount", type: "PERCENT", value: 30, scope: "INVOICE", notes: "Children of teaching staff." } });
  const gkaD = await db.discount.create({ data: { schoolId: GKA.id, name: "Sibling discount (2+ learners)", type: "PERCENT", value: 10, scope: "INVOICE", autoApply: true } });
  const shuffledStudents = geaStudents.slice().sort(() => rnd() - 0.5);
  const apply = (discountId: string, e: typeof geaStudents[0], reason: string, serviceId?: string) =>
    db.discountApplication.create({ data: { discountId, studentId: e.studentId, schoolId: GEA.id, serviceId: serviceId ?? null, reason, appliedByName: "Madam Grace Mensah" } });
  await apply(d1.id, shuffledStudents[0], "Two siblings in Basic 2 and Basic 4.");
  await apply(d1.id, shuffledStudents[1], "Twins in JHS 1.");
  await apply(d1.id, shuffledStudents[2], "Three siblings enrolled.");
  await apply(d2.id, shuffledStudents[3], "Top of class, Basic 6 — merit scholarship (PTA sponsored).");
  await apply(d2.id, shuffledStudents[4], "Excellence in BECE trial results.");
  const lunchSvc = geaSvc.find((s) => s.name.startsWith("Lunch"))!;
  const bfastSvc = geaSvc.find((s) => s.name.startsWith("Breakfast"))!;
  await apply(d3.id, shuffledStudents[5], "Father deceased; guardian petty trader — PTA welfare verified.", lunchSvc.id);
  await apply(d3.id, shuffledStudents[6], "Single-parent household, zonal rep verified.", bfastSvc.id);
  await apply(d4.id, shuffledStudents[7], "Daughter of Mr. Daniel Quaye (English teacher).");
  await db.discountApplication.create({ data: { discountId: gkaD.id, studentId: (await db.enrollment.findFirst({ where: { schoolId: GKA.id, status: "ACTIVE" } }))!.studentId, schoolId: GKA.id, reason: "Two siblings enrolled at Adenta main.", appliedByName: "Mrs. Josephine Agyemang" } });

  // ── 9. update the two needy-family enrolments to use the subsidised rate ──
  const needy = [shuffledStudents[5].studentId, shuffledStudents[6].studentId];
  for (const sid of needy) {
    for (const svc of [lunchSvc, bfastSvc]) {
      const en = await db.dailyServiceEnrollment.findUnique({ where: { serviceId_studentId: { serviceId: svc.id, studentId: sid } } });
      if (en) await db.dailyServiceEnrollment.update({ where: { id: en.id }, data: { priceCentsOverride: Math.round(svc.priceCents * 0.75) } });
    }
  }

  console.log("🎉 Ops seed complete.");
}

export { main as seedOps };

// standalone entry — skips if ops data already present
if (process.argv[1]?.includes("seed-ops")) {
  (async () => {
    const existing = await db.dailyService.count();
    if (existing > 0) {
      console.log("✅ Ops data already seeded — nothing to do.");
    } else {
      await main();
    }
  })()
    .catch((e) => { console.error(e); process.exit(1); })
    .finally(() => db.$disconnect());
}

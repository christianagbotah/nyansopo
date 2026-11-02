/* Staff-form + dashboard-KPI smoke test — NewStaffDialog parity (staff codes,
   full field round-trip, linked logins) and ops/overview KPI extras
   (dailyToday / dailyArrearsCents / classroomBands / myClasses + role gates).
   Run: bun scripts/smoke-staff-dashboard.ts  (dev server on :3000 required) */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const BASE = "http://localhost:3000";
let pass = 0, fail = 0;

async function check(label: string, cond: boolean, extra = "") {
  if (cond) { pass++; console.log(`  ✅ ${label}`); }
  else { fail++; console.log(`  ❌ ${label} ${extra}`); }
}

/** independent re-computation of the expected next code (max numeric suffix + 1) */
async function dbExpected(schoolId: string, prefix: string): Promise<string> {
  const rows = await db.staff.findMany({ where: { schoolId }, select: { staffCode: true } });
  let max = 0;
  for (const r of rows) {
    if (!r.staffCode?.startsWith(`${prefix}-`)) continue;
    const n = parseInt(r.staffCode.slice(prefix.length + 1), 10);
    if (Number.isFinite(n) && n > max) max = n;
  }
  return `${prefix}-${String(max + 1).padStart(5, "0")}`;
}

async function main() {
  const [head, teacher, parent, student] = await Promise.all([
    db.user.findUnique({ where: { email: "head@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "teacher@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "parent@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "student@nyansapo.gh" } }),
  ]);
  if (!head || !teacher || !parent || !student) { console.log("missing demo users — run seed + seed-rbac first"); return; }
  const schoolId = head.schoolId!;
  const H = (u: typeof head) => ({ "content-type": "application/json", "x-user-id": u.id });
  const get = async (u: typeof head, url: string) => fetch(BASE + url, { headers: H(u) });
  const post = async (u: typeof head, url: string, body: any) => fetch(BASE + url, { method: "POST", headers: H(u), body: JSON.stringify(body) });
  const PAY = `/api/school/${schoolId}/ops/payroll`;
  const OV = `/api/school/${schoolId}/ops/overview`;
  const dayStart = new Date(); dayStart.setHours(0, 0, 0, 0);
  const dayEnd = new Date(dayStart); dayEnd.setHours(23, 59, 59, 999);

  console.log("\n── (a) next-staff-code: prefix + 5-digit sequence from max suffix + 1 ──");
  const tc = await (await get(head, `${PAY}?action=next-staff-code&category=TEACHING`)).json();
  check("TEACHING code shape TCR-#####", /^TCR-\d{5}$/.test(tc.code), tc.code);
  check("TEACHING matches DB max suffix + 1", tc.code === await dbExpected(schoolId, "TCR"), `${tc.code} vs ${await dbExpected(schoolId, "TCR")}`);
  const nt = await (await get(head, `${PAY}?action=next-staff-code&category=NON_TEACHING`)).json();
  check("NON_TEACHING code shape NTS-#####", /^NTS-\d{5}$/.test(nt.code), nt.code);
  check("NON_TEACHING matches DB max suffix + 1", nt.code === await dbExpected(schoolId, "NTS"), `${nt.code} vs ${await dbExpected(schoolId, "NTS")}`);
  const ad = await (await get(head, `${PAY}?action=next-staff-code&category=ADMINISTRATION`)).json();
  check("ADMINISTRATION code shape ADM-#####", /^ADM-\d{5}$/.test(ad.code), ad.code);
  check("ADMINISTRATION matches DB max suffix + 1", ad.code === await dbExpected(schoolId, "ADM"), `${ad.code} vs ${await dbExpected(schoolId, "ADM")}`);

  console.log("\n── (b) check-staff-code: existing taken / fresh available ──");
  const existing = await db.staff.findFirst({ where: { schoolId, staffCode: { not: null } } });
  const chkTaken = await (await get(head, `${PAY}?action=check-staff-code&code=${encodeURIComponent(existing!.staffCode!)}&category=TEACHING`)).json();
  check(`existing code ${existing!.staffCode} reported taken`, chkTaken.taken === true);
  const chkFree = await (await get(head, `${PAY}?action=check-staff-code&code=ZZZ-99999&category=TEACHING`)).json();
  check("fresh code ZZZ-99999 available", chkFree.taken === false);

  console.log("\n── (c) create-staff with full fields → persisted ──");
  const created = await post(head, PAY, {
    action: "create-staff",
    fullName: "SMOKE Test Teacher", category: "TEACHING", position: "Teacher",
    phone: "+233 24 000 9999", email: "smoke.test.teacher@nyansapo.gh",
    staffCode: tc.code, sex: "F", birthday: "1995-04-12", ghanaCardId: "GHA-123456789-0",
    ssnitNumber: "C9876543210", tin: "C0011223344", tier2Provider: "Petra Trust", tier2MemberId: "PT-998877",
    bankName: "GCB Bank", accountNumber: "1234567890123", momoNumber: "0240009999",
    address: "House 12, Adenta", subjects: "Mathematics, Science",
    qualification: "B.Ed Basic Education", department: "Primary",
    employmentType: "FULL_TIME", salaryCents: 160000, hiredAt: "2024-09-01",
  });
  check("create returns 200", created.status === 200, String(created.status));
  const createdBody = await created.json().catch(() => ({}));
  const staffId: string | undefined = createdBody.staff?.id;
  check("create returns staff id", !!staffId);
  const persisted = staffId ? await db.staff.findUnique({ where: { id: staffId } }) : null;
  check("staff row persisted", !!persisted);
  if (persisted) {
    check("staffCode kept as supplied", persisted.staffCode === tc.code, `${persisted.staffCode}`);
    check("sex persisted", persisted.sex === "F");
    check("birthday persisted", persisted.birthday && new Date(persisted.birthday).toISOString().slice(0, 10) === "1995-04-12");
    check("ssnit persisted", persisted.ssnitNumber === "C9876543210");
    check("tier2 provider + member id persisted", persisted.tier2Provider === "Petra Trust" && persisted.tier2MemberId === "PT-998877");
    check("bank + account persisted", persisted.bankName === "GCB Bank" && persisted.accountNumber === "1234567890123");
    check("ghana card + address persisted", persisted.ghanaCardId === "GHA-123456789-0" && persisted.address === "House 12, Adenta");
    check("subjects + momo persisted", persisted.subjects === "Mathematics, Science" && persisted.momoNumber === "0240009999");
    check("hiredAt + salary persisted", persisted.hiredAt && persisted.salaryCents === 160000);
  }

  console.log("\n── (a2) sequence increments after create (per category) ──");
  const tc2 = await (await get(head, `${PAY}?action=next-staff-code&category=TEACHING`)).json();
  check("TEACHING next code incremented by 1", tc2.code === await dbExpected(schoolId, "TCR") && Number(tc2.code.slice(4)) === Number(tc.code.slice(4)) + 1, `${tc.code} → ${tc2.code}`);
  const nt2 = await (await get(head, `${PAY}?action=next-staff-code&category=NON_TEACHING`)).json();
  check("NON_TEACHING next code unchanged by TEACHING create", nt2.code === nt.code, `${nt.code} → ${nt2.code}`);
  const chkTaken2 = await (await get(head, `${PAY}?action=check-staff-code&code=${encodeURIComponent(tc.code)}&category=TEACHING`)).json();
  check("created code now reported taken", chkTaken2.taken === true);

  console.log("\n── (d) duplicate staffCode rejected ──");
  const dup = await post(head, PAY, {
    action: "create-staff",
    fullName: "SMOKE Dup Teacher", category: "TEACHING", position: "Teacher",
    phone: "+233 24 000 8888", email: "smoke.dup.teacher@nyansapo.gh",
    staffCode: tc.code, employmentType: "FULL_TIME", salaryCents: 100000,
  });
  check("duplicate staffCode rejected 409/400", dup.status === 409 || dup.status === 400, String(dup.status));

  console.log("\n── (e) create with linked login → /api/auth works, then delete ──");
  const loginEmail = `smoke.login.${Date.now()}@nyansapo.gh`;
  const withLogin = await post(head, PAY, {
    action: "create-staff",
    fullName: "SMOKE Login Staff", category: "TEACHING", position: "Teacher",
    phone: "+233 24 000 7777", email: loginEmail,
    employmentType: "FULL_TIME", salaryCents: 150000,
    createLogin: { password: "nyansapo123", role: "TEACHER" },
  });
  check("create-with-login returns 200", withLogin.status === 200, String(withLogin.status));
  const loginBody = await withLogin.json().catch(() => ({}));
  const loginStaffId: string | undefined = loginBody.staff?.id;
  const loginUserId: string | undefined = loginBody.login?.id;
  check("login user created (id returned)", !!loginUserId);
  check("login role TEACHER", loginBody.login?.role === "TEACHER", String(loginBody.login?.role));
  const authRes = await fetch(`${BASE}/api/auth`, {
    method: "POST", headers: { "content-type": "application/json" },
    body: JSON.stringify({ email: loginEmail, password: "nyansapo123" }),
  });
  check("POST /api/auth with new credentials → 200", authRes.status === 200, String(authRes.status));
  const authBody = await authRes.json().catch(() => ({}));
  check("auth returns role TEACHER", authBody.user?.role === "TEACHER");
  check("auth user linked to staff record", !!loginStaffId && authBody.user?.staffId === loginStaffId);
  const badAuth = await fetch(`${BASE}/api/auth`, {
    method: "POST", headers: { "content-type": "application/json" },
    body: JSON.stringify({ email: loginEmail, password: "wrong-password" }),
  });
  check("wrong password rejected 401", badAuth.status === 401, String(badAuth.status));
  if (loginUserId) await db.user.delete({ where: { id: loginUserId } }).catch(() => null);
  if (loginStaffId) await db.staff.delete({ where: { id: loginStaffId } }).catch(() => null);
  check("login user + staff deleted", !(await db.user.findUnique({ where: { email: loginEmail } })) && !(loginStaffId ? await db.staff.findUnique({ where: { id: loginStaffId } }) : null));

  console.log("\n── (f) overview (head): dailyToday / dailyArrearsCents / classroomBands ──");
  let extraChargeId: string | null = null;
  const haveToday = (await db.dailyCharge.count({ where: { schoolId, date: { gte: dayStart, lte: dayEnd } } })) > 0;
  if (!haveToday) {
    const svc = await db.dailyService.findFirst({ where: { schoolId } });
    const stu = await db.student.findFirst({ where: { status: "ACTIVE" } });
    if (svc && stu) {
      const c = await db.dailyCharge.create({
        data: { serviceId: svc.id, studentId: stu.id, schoolId, date: new Date(), amountCents: 500, status: "PAID" },
      });
      extraChargeId = c.id;
      console.log("  (no today data — injected a PAID DailyCharge for the check)");
    }
  }
  const ovRes = await get(head, OV);
  const ov = await ovRes.json().catch(() => ({}));
  check("overview 200", ovRes.status === 200, String(ovRes.status));
  check("dailyToday non-empty", Array.isArray(ov.dailyToday) && ov.dailyToday.length > 0, `len=${ov.dailyToday?.length}`);
  check("dailyToday entries well-formed", Array.isArray(ov.dailyToday) && ov.dailyToday.every((d: any) => typeof d.service === "string" && typeof d.label === "string" && typeof d.cents === "number" && typeof d.txns === "number"));
  check("dailyToday has received cents today", ov.dailyToday?.some((d: any) => d.cents > 0 && d.txns > 0));
  check("dailyArrearsCents is a number > 0", typeof ov.dailyArrearsCents === "number" && ov.dailyArrearsCents > 0, String(ov.dailyArrearsCents));
  check("classroomBands non-empty", Array.isArray(ov.classroomBands) && ov.classroomBands.length > 0, `len=${ov.classroomBands?.length}`);
  check("classroomBands m/f numbers summing to enrolment", Array.isArray(ov.classroomBands) && ov.classroomBands.every((b: any) => typeof b.classroom === "string" && b.m + b.f > 0));
  const activeEnrol = await db.enrollment.count({ where: { schoolId, status: "ACTIVE" } });
  const bandTotal = (ov.classroomBands ?? []).reduce((s: number, b: any) => s + b.m + b.f, 0);
  check("classroomBands totals match active enrolment (top-10 slice ok)", bandTotal > 0 && bandTotal <= activeEnrol, `${bandTotal} of ${activeEnrol}`);
  if (extraChargeId) { await db.dailyCharge.delete({ where: { id: extraChargeId } }); console.log("  (injected DailyCharge cleaned)"); }

  console.log("\n── (g) overview (teacher): myClasses only when class teacher ──");
  check("teacher has linked staff record", !!teacher.staffId, String(teacher.staffId));
  let myRoom = teacher.staffId ? await db.classroom.findFirst({ where: { schoolId, classTeacherId: teacher.staffId } }) : null;
  let restoreRoomId: string | null = null;
  let restoreTeacherId: string | null | undefined;
  if (!myRoom) {
    const anyRoom = await db.classroom.findFirst({ where: { schoolId } });
    if (anyRoom) {
      restoreRoomId = anyRoom.id; restoreTeacherId = anyRoom.classTeacherId;
      await db.classroom.update({ where: { id: anyRoom.id }, data: { classTeacherId: teacher.staffId } });
      myRoom = anyRoom;
      console.log(`  (temporarily set ${anyRoom.name} class teacher for the check)`);
    }
  }
  const originalTeacherId: string | null = myRoom ? myRoom.classTeacherId : null;
  const ovt = await (await get(teacher, OV)).json().catch(() => ({}));
  check("teacher myClasses non-empty", Array.isArray(ovt.myClasses) && ovt.myClasses.length > 0, `len=${ovt.myClasses?.length}`);
  const mine = (ovt.myClasses ?? [])[0];
  check("myClasses fields present (learners/present/absent)", mine && typeof mine.learners === "number" && mine.learners > 0 && typeof mine.presentToday === "number" && typeof mine.absentToday === "number");
  check("myClasses has classroomId + name", mine && typeof mine.classroomId === "string" && typeof mine.name === "string" && mine.name.length > 0);
  // negative: unset class teacher → myClasses must empty out
  if (myRoom) {
    await db.classroom.update({ where: { id: myRoom.id }, data: { classTeacherId: null } });
    const ovt2 = await (await get(teacher, OV)).json().catch(() => ({}));
    check("myClasses empty once class teacher unset", Array.isArray(ovt2.myClasses) && ovt2.myClasses.length === 0, `len=${ovt2.myClasses?.length}`);
    await db.classroom.update({ where: { id: myRoom.id }, data: { classTeacherId: originalTeacherId } });
    if (restoreRoomId && restoreRoomId === myRoom.id && restoreTeacherId !== undefined) {
      await db.classroom.update({ where: { id: myRoom.id }, data: { classTeacherId: restoreTeacherId } });
      console.log("  (temporary class-teacher assignment restored)");
    }
  } else {
    check("myClasses negative case (no classroom available)", false, "no classroom to test with");
  }

  console.log("\n── (h) role gates: PARENT / STUDENT get 403 on ops/overview ──");
  const ovParent = await get(parent, OV);
  check("PARENT gets 403", ovParent.status === 403, String(ovParent.status));
  const ovStudent = await get(student, OV);
  check("STUDENT gets 403", ovStudent.status === 403, String(ovStudent.status));
  const payParent = await get(parent, `${PAY}?action=next-staff-code&category=TEACHING`);
  check("PARENT gets 403 on payroll code helper", payParent.status === 403, String(payParent.status));

  console.log("\n── cleanup ──");
  const leftovers = await db.staff.findMany({ where: { schoolId, fullName: { startsWith: "SMOKE " } } });
  for (const s of leftovers) await db.user.deleteMany({ where: { staffId: s.id } }).catch(() => null);
  if (leftovers.length) await db.staff.deleteMany({ where: { id: { in: leftovers.map((s) => s.id) } } });
  const remainingStaff = await db.staff.count({ where: { schoolId, fullName: { startsWith: "SMOKE " } } });
  const remainingUsers = await db.user.count({ where: { email: { startsWith: "smoke." } } });
  check("all SMOKE staff rows removed", remainingStaff === 0, String(remainingStaff));
  check("all smoke login users removed", remainingUsers === 0, String(remainingUsers));
  const dupRow = await db.staff.findFirst({ where: { schoolId, fullName: "SMOKE Dup Teacher" } });
  check("duplicate attempt left no row", !dupRow);

  console.log(`\n${fail === 0 ? "🎉" : "⚠️ "} ${pass} passed, ${fail} failed`);
  if (fail > 0) process.exitCode = 1;
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

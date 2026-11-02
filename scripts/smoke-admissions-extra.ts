/* Admission wizard parity smoke — the iEduPro student_add_new fields the
   wizard gained: father/mother split, medical block, portrait data-URL,
   optional boarding bed allocation at intake.
   Creates its own data and CLEANS UP afterwards.
   Run: DATABASE_URL=postgresql://… bun scripts/smoke-admissions-extra.ts
   (needs `bun run dev` on :3000) */
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

const TINY_PORTRAIT =
  "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAMCAgICAgMCAgIDAwMDBAYEBAQEBAgGBgUGCQgKCgkICAgKDA8MCgsOCwkIDRENDg4RDxAQCgsSExIRExSQEBD/wAALCABAAEABAREA/8QAFAABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AJgA/9k=";

async function main() {
  const user = await db.user.findUnique({ where: { email: "head@nyansapo.gh" } });
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!user || !school) { console.log("missing demo data — run the seed first"); return; }
  const H = { "content-type": "application/json", "x-user-id": user.id };
  const post = (body: any) => fetch(`${BASE}/api/school/${school.id}/ops/admissions`, { method: "POST", headers: H, body: JSON.stringify(body) });

  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId: school.id } } });
  const classroom = term
    ? await db.classroom.findFirst({ where: { schoolId: school.id, feeStructures: { some: { termId: term.id } } } })
    : await db.classroom.findFirst({ where: { schoolId: school.id } });
  if (!classroom) { console.log("no classroom — aborting"); return; }

  console.log("── (a) GET payload exposes boarding availability ──");
  const getRes = await fetch(`${BASE}/api/school/${school.id}/ops/admissions`, { headers: H });
  const wiz = await getRes.json();
  check("GET 200 with classrooms + discounts", getRes.ok && Array.isArray(wiz.classrooms) && Array.isArray(wiz.discounts));
  check("GET payload has boarding array (may be empty)", Array.isArray(wiz.boarding));

  // pick an available bed (any house) to exercise allocation; fall back to no-bed run
  const bed = await db.bed.findFirst({
    where: { schoolId: school.id, status: "AVAILABLE", studentId: null, dormitory: { status: { not: "MAINTENANCE" }, house: { status: { not: "MAINTENANCE" } } } },
    include: { dormitory: { include: { house: true } } },
  });
  const preBedState = bed ? { status: bed.status, studentId: bed.studentId } : null;

  console.log("\n── (b) admit with full parity payload ──");
  const preCounters = await db.codeCounter.findMany({ where: { scope: { in: ["INV:" + new Date().getFullYear(), "NYIDX:" + new Date().getFullYear()] } } });
  const preStudents = await db.student.count();
  const preSchool = await db.school.findUnique({ where: { id: school.id }, select: { studentsOnRoll: true } });

  const payload: any = {
    action: "admit",
    firstName: "Parity", lastName: "Probe", gender: "F", dateOfBirth: "2015-03-02",
    classroomId: classroom.id,
    guardianName: "Probe Guardian", guardianPhone: "0249990001", relationship: "Mother",
    fatherName: "Kofi Probe", fatherPhone: "0249990002", fatherOccupation: "Mason",
    motherName: "Ama Probe", motherPhone: "0249990003", motherOccupation: "Trader",
    nationality: "Ghanaian", religion: "Christianity",
    bloodGroup: "O+", allergies: "Groundnuts", medicalConditions: "Mild asthma", emergencyContact: "Auntie Adjoa · 0201112222", dietNote: "No groundnuts",
    photoUrl: TINY_PORTRAIT,
    billItems: [{ name: "Term fee", amountCents: 100000 }],
    initialPaymentCents: 0,
  };
  if (bed) payload.bedId = bed.id;
  const res = await post(payload);
  const body = await res.json();
  check("admit 200", res.ok, JSON.stringify(body).slice(0, 140));
  const probe = body.student ? await db.student.findUnique({ where: { id: body.student.id } }) : null;
  check("student persisted with father/mother split", !!probe?.fatherName && probe.fatherName === "Kofi Probe" && probe.motherName === "Ama Probe");
  check("medical block persisted", probe?.bloodGroup === "O+" && probe?.allergies === "Groundnuts" && probe?.medicalConditions === "Mild asthma" && probe?.emergencyContact === "Auntie Adjoa · 0201112222" && probe?.dietNote === "No groundnuts");
  check("nationality/religion persisted", probe?.nationality === "Ghanaian" && probe?.religion === "Christianity");
  check("portrait stored as data-URL", !!probe?.photoUrl?.startsWith("data:image/"));
  if (bed) {
    const afterBed = await db.bed.findUnique({ where: { id: bed.id } });
    check(`bed ${bed.code} now ASSIGNED to the new learner`, afterBed?.studentId === body.student.id && afterBed?.status === "ASSIGNED" && !!afterBed?.assignedAt);
  } else {
    console.log("  ⚠️ no available bed in demo data — allocation path skipped");
  }
  check("response reports the bed assignment", bed ? (body.bed?.code === bed.code && body.bed.house === bed.dormitory?.house?.name || body.bed?.code === bed.code) : body.bed == null || body.bed === null);

  console.log("\n── (c) bed race: another admit targeting the same bed 409s ──");
  if (bed) {
    const res2 = await post({ ...payload, firstName: "Race", lastName: "Probe", bedId: bed.id });
    check("second claim on the taken bed → 409", res2.status === 409, `got ${res2.status}`);
    const raceStudent = await db.student.findFirst({ where: { firstName: "Race", lastName: "Probe" } });
    check("no student row created by the losing claim", !raceStudent);
  }

  console.log("\n── (d) oversize / junk photo rejected silently ──");
  const res3 = await post({ ...payload, firstName: "Photo", lastName: "Probe", bedId: null, photoUrl: "javascript:alert(1)" });
  const body3 = await res3.json();
  const probe3 = body3.student ? await db.student.findUnique({ where: { id: body3.student.id } }) : null;
  check("non-image photoUrl ignored (row has none)", res3.ok && !probe3?.photoUrl);

  console.log("\n── cleanup ──");
  for (const p of [probe, probe3]) {
    if (!p) continue;
    await db.invoiceItem.deleteMany({ where: { invoice: { studentId: p.id } } });
    await db.invoice.deleteMany({ where: { studentId: p.id } });
    await db.payment.deleteMany({ where: { studentId: p.id } });
    await db.discountApplication.deleteMany({ where: { studentId: p.id } });
    await db.smsMessage.deleteMany({ where: { studentId: p.id } }).catch(() => {});
    await db.enrollment.deleteMany({ where: { studentId: p.id } });
    await db.bed.updateMany({ where: { studentId: p.id }, data: { studentId: null, status: "AVAILABLE", assignedAt: null } });
    await db.student.delete({ where: { id: p.id } });
  }
  if (bed && preBedState) await db.bed.update({ where: { id: bed.id }, data: preBedState });
  const postCounters = await db.codeCounter.findMany({ where: { scope: { in: ["INV:" + new Date().getFullYear(), "NYIDX:" + new Date().getFullYear()] } } });
  for (const pre of preCounters) {
    const nowRow = postCounters.find((c) => c.scope === pre.scope);
    if (nowRow) await db.codeCounter.update({ where: { scope: pre.scope }, data: { next: pre.next } });
  }
  const postStudents = await db.student.count();
  check("student count restored", postStudents === preStudents, `${preStudents}→${postStudents}`);
  const postSchool = await db.school.findUnique({ where: { id: school.id }, select: { studentsOnRoll: true } });
  check("studentsOnRoll restored", postSchool!.studentsOnRoll === preSchool!.studentsOnRoll);

  console.log(`\n${pass} passed, ${fail} failed`);
  process.exit(fail ? 1 : 0);
}
main();

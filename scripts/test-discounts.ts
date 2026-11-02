/* Runtime verification of the daily-fee discount profile engine (v2).
   Scenario: full lunch-only support + half all-daily-fees overlap, most-generous-wins,
   then step-down revocations. Run: bun scripts/test-discounts.ts */
import { PrismaClient } from "@prisma/client";
import { recalcDailySubsidies } from "../src/lib/discounts";

const db = new PrismaClient();
const ok = (name: string, cond: boolean, detail?: string) => {
  console.log(`${cond ? "✅" : "❌"} ${name}${detail ? ` — ${detail}` : ""}`);
  if (!cond) process.exitCode = 1;
};

async function main() {
  // 1. pick a school with multiple daily services
  const school = (await db.school.findMany()).find(async () => true) ?? null;
  const target = (await db.dailyService.findMany({ where: { schoolId: school!.id }, orderBy: { name: "asc" } }));
  const svcSchool = target.length >= 3 ? school! : (await db.school.findMany())[0];
  const services = target.length >= 3 ? target : await db.dailyService.findMany({ where: { schoolId: svcSchool.id }, orderBy: { name: "asc" } });
  if (services.length < 2) { console.log("⚠️ need a school with ≥2 daily services"); return; }
  console.log(`School: ${svcSchool.name}`);
  console.log("Services:", services.map((s) => `${s.name}=${s.priceCents}`).join(" · "));

  const lunch = services[0]; // arbitrary first service = "target" service
  const other = services.find((s) => s.id !== lunch.id)!;

  // 2. a learner actively enrolled there
  const enrollment = await db.enrollment.findFirst({ where: { schoolId: svcSchool.id, status: "ACTIVE" } });
  if (!enrollment) { console.log("⚠️ no active learner at this school"); return; }
  const studentId = enrollment.studentId;
  const learner = await db.student.findUnique({ where: { id: studentId } });
  console.log(`Learner: ${learner!.firstName} ${learner!.lastName} (${learner!.nyansapoIndex})\n`);

  // 3. two profiles: FULL discount on the one service, HALF on all daily fees
  const pFull = await db.discountProfile.create({ data: { schoolId: svcSchool.id, name: "TEST — Full lunch support", category: "DAILY_SERVICE", method: "PERCENT", value: 100, serviceIds: lunch.id } });
  const pHalf = await db.discountProfile.create({ data: { schoolId: svcSchool.id, name: "TEST — Half all daily fees", category: "DAILY_SERVICE", method: "PERCENT", value: 50, serviceIds: "*" } });

  const overrideOf = async (serviceId: string) =>
    (await db.dailyServiceEnrollment.findUnique({ where: { serviceId_studentId: { serviceId, studentId } } }))?.priceCentsOverride ?? null;

  // clean slate
  await db.dailyServiceEnrollment.deleteMany({ where: { studentId } });
  await db.dailyCharge.deleteMany({ where: { studentId } }); // avoid settlement interference (test learner only)

  // ── A. apply FULL lunch-only → that service 0, others untouched
  await db.discountApplication.create({ data: { profileId: pFull.id, studentId, schoolId: svcSchool.id, appliedByName: "test", status: "ACTIVE" } });
  await recalcDailySubsidies(studentId, { performedByName: "test", reason: "test A" });
  ok("A: full lunch support → target service free (0)", (await overrideOf(lunch.id)) === 0, `override=${await overrideOf(lunch.id)}`);
  ok("A: other service untouched", (await overrideOf(other.id)) === null, `override=${await overrideOf(other.id)}`);

  // ── B. add HALF all-daily → most generous wins on target (0), other service halved
  await db.discountApplication.create({ data: { profileId: pHalf.id, studentId, schoolId: svcSchool.id, appliedByName: "test", status: "ACTIVE" } });
  await recalcDailySubsidies(studentId, { performedByName: "test", reason: "test B" });
  ok("B: overlap keeps the free rate (most generous)", (await overrideOf(lunch.id)) === 0, `override=${await overrideOf(lunch.id)}`);
  const halfExpected = Math.round(other.priceCents * 0.5);
  ok("B: other service halved", (await overrideOf(other.id)) === halfExpected, `override=${await overrideOf(other.id)} expected=${halfExpected}`);

  // ── C. revoke the FULL lunch profile → falls back to half
  await db.discountApplication.updateMany({ where: { profileId: pFull.id, studentId }, data: { status: "REVOKED" } });
  await recalcDailySubsidies(studentId, { performedByName: "test", reason: "test C" });
  const lunchHalf = Math.round(lunch.priceCents * 0.5);
  ok("C: after revoke, target falls back to half", (await overrideOf(lunch.id)) === lunchHalf, `override=${await overrideOf(lunch.id)} expected=${lunchHalf}`);

  // ── D. revoke everything → standard rates restored
  await db.discountApplication.updateMany({ where: { studentId, schoolId: svcSchool.id }, data: { status: "REVOKED" } });
  await recalcDailySubsidies(studentId, { performedByName: "test", reason: "test D" });
  ok("D: all revoked → standard rate (null override)", (await overrideOf(lunch.id)) === null && (await overrideOf(other.id)) === null, `lunch=${await overrideOf(lunch.id)} other=${await overrideOf(other.id)}`);

  // ── E. audit trail written for the rate changes
  const audits = await db.dailyFeeAudit.count({ where: { studentId, actionType: "ADJUSTMENT" } });
  ok("E: audit ADJUSTMENT rows written", audits >= 4, `rows=${audits}`);

  // cleanup test artefacts
  await db.discountApplication.deleteMany({ where: { studentId, schoolId: svcSchool.id, appliedByName: "test" } });
  await db.discountProfile.deleteMany({ where: { id: { in: [pFull.id, pHalf.id] } } });
  await db.dailyFeeAudit.deleteMany({ where: { studentId, reference: null } });
  console.log("\n🧹 test artefacts cleaned");
}

main().catch((e) => { console.error(e); process.exitCode = 1; }).finally(() => db.$disconnect());

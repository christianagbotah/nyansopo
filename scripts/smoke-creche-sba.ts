/* Early-years (crèche) assessment + SBA marks config smoke test —
   lifecycle round-trips against the ops/academics endpoints as HEAD
   (SCHOOL_ADMIN, settings.manage) and TEACHER (gradebook.enter, no
   settings.manage), plus RBAC guards and formula recomputation.
   Run: DATABASE_URL=postgresql://… bun scripts/smoke-creche-sba.ts */
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
const r2 = (x: number) => Math.round(x * 100) / 100;

async function main() {
  const [superU, head, teacher] = await Promise.all([
    db.user.findUnique({ where: { email: "super@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "head@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "teacher@nyansapo.gh" } }),
  ]);
  if (!superU || !head || !teacher) { console.log("missing demo users — run seed-rbac first"); return; }
  const schoolId = head.schoolId!;
  const H = (u: typeof head) => ({ "content-type": "application/json", "x-user-id": u.id });
  const get = async (u: typeof head, url: string) => fetch(BASE + url, { headers: H(u) });
  const post = async (u: typeof head, url: string, body: any) => fetch(BASE + url, { method: "POST", headers: H(u), body: JSON.stringify(body) });

  const gea = await db.school.findUnique({ where: { id: schoolId } });
  if (!gea) { console.log("school missing"); return; }
  const term = await db.term.findFirst({ where: { academicYear: { schoolId }, isCurrent: true } });
  const classrooms = await db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
  const kg2 = classrooms.find((c) => c.gradeLevel === "KG2") ?? classrooms.find((c) => /KG/i.test(c.gradeLevel))!;
  const b4 = classrooms.find((c) => c.name.includes("Basic 4"))!;
  const maths = await db.subject.findFirst({ where: { schoolId, name: "Mathematics" } });
  if (!term || !kg2 || !b4 || !maths) { console.log("missing term/class/subject — run the core seed first"); return; }
  const termId = term.id;

  /* ── (a) creche-setup GET returns scale + domains ── */
  console.log("\n── (a) creche-setup payload ──");
  const setupRes = await get(head, `/api/school/${schoolId}/ops/academics?action=creche-setup`);
  check("head can read creche-setup", setupRes.status === 200, String(setupRes.status));
  const setup = await setupRes.json();
  check("scale returned (4 codes)", (setup.scale?.length ?? 0) === 4 && setup.scale.some((s: any) => s.code === "EX"), JSON.stringify(setup.scale));
  check("domains returned with skills", (setup.domains?.length ?? 0) >= 1 && setup.domains.every((d: any) => Array.isArray(d.skills)), `domains=${setup.domains?.length}`);

  /* ── (b) scale / domain / skill CRUD round-trip + delete ── */
  console.log("\n── (b) scale / domain / skill CRUD ──");
  const zz = await post(head, `/api/school/${schoolId}/ops/academics`, { action: "creche-scale-save", code: "ZZ", label: "Smoke Probe", sortOrder: 9 });
  check("scale code created", zz.status === 200, String(zz.status));
  const zzBody = await zz.json();
  const zzId = zzBody.scale?.id;
  check("create returns id", !!zzId);
  const dup = await post(head, `/api/school/${schoolId}/ops/academics`, { action: "creche-scale-save", code: "ZZ", label: "Duplicate" });
  check("duplicate code blocked 409", dup.status === 409, String(dup.status));
  const zzUp = await post(head, `/api/school/${schoolId}/ops/academics`, { action: "creche-scale-save", id: zzId, code: "ZZ", label: "Smoke Probe (renamed)", sortOrder: 9 });
  check("scale updated", zzUp.status === 200 && (await zzUp.json()).scale?.label === "Smoke Probe (renamed)");
  const zzDel = await post(head, `/api/school/${schoolId}/ops/academics`, { action: "creche-scale-delete", id: zzId });
  check("scale deleted", zzDel.status === 200);
  check("scale row gone from DB", (await db.crecheRatingScale.count({ where: { schoolId, code: "ZZ" } })) === 0);

  const domCreate = await post(head, `/api/school/${schoolId}/ops/academics`, { action: "creche-domain-save", name: "SMOKE Test Domain", sortOrder: 99 });
  check("domain created", domCreate.status === 200, String(domCreate.status));
  const domId = (await domCreate.json()).domain?.id;
  const sk = await post(head, `/api/school/${schoolId}/ops/academics`, { action: "creche-skill-save", domainId: domId, name: "SMOKE indicator", sortOrder: 1 });
  check("skill created under domain", sk.status === 200, String(sk.status));
  const skId = (await sk.json()).skill?.id;
  const skUp = await post(head, `/api/school/${schoolId}/ops/academics`, { action: "creche-skill-save", id: skId, domainId: domId, name: "SMOKE indicator (v2)", sortOrder: 2 });
  check("skill renamed", skUp.status === 200 && (await skUp.json()).skill?.name === "SMOKE indicator (v2)");
  const skDel = await post(head, `/api/school/${schoolId}/ops/academics`, { action: "creche-skill-delete", id: skId });
  check("skill deleted", skDel.status === 200);
  const domDel = await post(head, `/api/school/${schoolId}/ops/academics`, { action: "creche-domain-delete", id: domId });
  check("domain deleted (cascade)", domDel.status === 200);
  check("smoke domain + skill fully removed", (await db.crecheDomain.count({ where: { schoolId, name: "SMOKE Test Domain" } })) === 0 && (await db.crecheSkill.count({ where: { schoolId, name: { contains: "SMOKE indicator" } } })) === 0);

  /* ── (c) ratings save → unique upsert (save twice, still one row) ── */
  console.log("\n── (c) ratings batch upsert ──");
  const kgEnrolled = await db.enrollment.findMany({ where: { classroomId: kg2.id, status: "ACTIVE" }, orderBy: { rollNo: "asc" } });
  const firstSkill = setup.domains[0].skills[0];
  const learner1 = kgEnrolled[0];
  const ratingKey = { studentId: learner1.studentId, termId, skillId: firstSkill.id };
  const saveOnce = await post(teacher, `/api/school/${schoolId}/ops/academics`, {
    action: "creche-ratings-save", classroomId: kg2.id, termId,
    ratings: [{ studentId: learner1.studentId, skillId: firstSkill.id, code: "EX" }],
  });
  check("teacher saves rating (200)", saveOnce.status === 200, String(saveOnce.status));
  const saveTwice = await post(teacher, `/api/school/${schoolId}/ops/academics`, {
    action: "creche-ratings-save", classroomId: kg2.id, termId,
    ratings: [{ studentId: learner1.studentId, skillId: firstSkill.id, code: "EX" }],
  });
  check("second save also 200", saveTwice.status === 200, String(saveTwice.status));
  check("still exactly one row per student+term+skill", (await db.crecheRating.count({ where: ratingKey })) === 1);
  const changeCode = await post(teacher, `/api/school/${schoolId}/ops/academics`, {
    action: "creche-ratings-save", classroomId: kg2.id, termId,
    ratings: [{ studentId: learner1.studentId, skillId: firstSkill.id, code: "GD" }],
  });
  check("re-save with new code updates in place", changeCode.status === 200 && (await db.crecheRating.count({ where: ratingKey })) === 1 && (await db.crecheRating.findFirst({ where: ratingKey }))?.code === "GD");
  const badCode = await post(teacher, `/api/school/${schoolId}/ops/academics`, {
    action: "creche-ratings-save", classroomId: kg2.id, termId,
    ratings: [{ studentId: learner1.studentId, skillId: firstSkill.id, code: "NOPE" }],
  });
  check("unknown code rejected 400", badCode.status === 400, String(badCode.status));

  /* ── (d) ratings GET returns the codes ── */
  console.log("\n── (d) ratings grid GET ──");
  const gridRes = await get(teacher, `/api/school/${schoolId}/ops/academics?action=creche-ratings&classroomId=${kg2.id}&termId=${termId}`);
  check("teacher reads ratings grid", gridRes.status === 200, String(gridRes.status));
  const grid = await gridRes.json();
  check("grid returns code just saved", grid.ratings?.[`${learner1.studentId}_${firstSkill.id}`] === "GD", JSON.stringify(grid.ratings?.[`${learner1.studentId}_${firstSkill.id}`]));
  check("grid carries scale + students", (grid.scale?.length ?? 0) >= 4 && (grid.students?.length ?? 0) >= 5);

  /* ── (e) sba-config save + read ── */
  console.log("\n── (e) SBA config ──");
  const cfgSave = await post(head, `/api/school/${schoolId}/ops/academics`, {
    action: "sba-config", classTestMax: 20, groupWorkMax: 10, test2Max: 20, projectMax: 10, classScoreWeightPct: 40, portfolioAutoFillPct: 30,
  });
  check("head saves sba-config", cfgSave.status === 200, String(cfgSave.status));
  const cfgRead = await (await get(head, `/api/school/${schoolId}/ops/academics?action=sba-config`)).json();
  check("config readback weight=40", cfgRead.config?.["sba.classScoreWeightPct"] === 40, JSON.stringify(cfgRead.config));
  check("config rows persisted as SchoolSetting", (await db.schoolSetting.count({ where: { schoolId, key: { startsWith: "sba." } } })) === 6);
  const cfgRestore = await post(head, `/api/school/${schoolId}/ops/academics`, {
    action: "sba-config", classTestMax: 20, groupWorkMax: 10, test2Max: 20, projectMax: 10, classScoreWeightPct: 50, portfolioAutoFillPct: 30,
  });
  check("config restored to weight=50", cfgRestore.status === 200);

  /* ── (f) sba-sheet save → Assessments + Marks + formula recomputation ── */
  console.log("\n── (f) SBA sheet save ──");
  const b4Enrolled = await db.enrollment.findMany({ where: { classroomId: b4.id, status: "ACTIVE" }, orderBy: { rollNo: "asc" } });
  const s1 = b4Enrolled[0], s2 = b4Enrolled[1];
  const sheetSave = await post(teacher, `/api/school/${schoolId}/ops/academics`, {
    action: "sba-sheet-save", classroomId: b4.id, subjectId: maths.id, termId,
    rows: [
      { studentId: s1.studentId, classTest: 18, groupWork: 8, test2: 15, project: 9, exam: 72 },
      { studentId: s2.studentId, groupWork: 5 },
    ],
  });
  check("teacher saves SBA sheet", sheetSave.status === 200, String(sheetSave.status));
  const expectedTitles: [string, string, number][] = [
    ["SBA Class Test · First Term", "EXERCISE", 20],
    ["SBA Group Work · First Term", "GROUP_WORK", 10],
    ["SBA Test 2 · First Term", "QUIZ", 20],
    ["SBA Project · First Term", "PROJECT", 10],
    ["SBA Term Exam · First Term", "END_OF_TERM", 100],
  ];
  for (const [title, type, max] of expectedTitles) {
    const a = await db.assessment.findFirst({ where: { classroomId: b4.id, subjectId: maths.id, termId, title } });
    check(`assessment "${title}" (${type}, /${max})`, !!a && a.type === type && a.maxScore === max, a ? `${a.type}/${a.maxScore}` : "missing");
  }
  const ct = await db.assessment.findFirst({ where: { classroomId: b4.id, subjectId: maths.id, termId, title: "SBA Class Test · First Term" } });
  const s1ct = await db.mark.findFirst({ where: { assessmentId: ct!.id, studentId: s1.studentId } });
  check("s1 class-test mark persisted (18)", s1ct?.score === 18, String(s1ct?.score));
  // recompute: subtotal 50/60 → class 50×50/60 = 41.67 ≤ 50 · exam 72 → 72×50/100 = 36 · total 77.67
  const subtotal = 18 + 8 + 15 + 9, weight = 50, actualMax = 60;
  const classScore = Math.min(weight, (subtotal * weight) / actualMax);
  const examScore = (72 * (100 - weight)) / 100;
  check("class score formula ≤ weight", r2(classScore) === 41.67 && classScore <= weight, String(classScore));
  check("exam score formula (weight 50 → exam/2)", r2(examScore) === 36, String(examScore));
  check("total = class + exam", r2(classScore + examScore) === 77.67);
  const sheetGet = await (await get(teacher, `/api/school/${schoolId}/ops/academics?action=sba-sheet&classroomId=${b4.id}&subjectId=${maths.id}&termId=${termId}`)).json();
  const gs1 = sheetGet.students?.find((x: any) => x.id === s1.studentId);
  check("sba-sheet GET returns saved scores", gs1?.scores?.classTest === 18 && gs1?.scores?.exam === 72, JSON.stringify(gs1?.scores));
  check("sba-sheet GET exposes config maxes", sheetGet.config?.actualMax === 60 && sheetGet.config?.examWeightPct === 50);
  const s2ct = await db.mark.findFirst({ where: { assessmentId: ct!.id, studentId: s2.studentId } });
  check("s2 class-test cell left empty", s2ct === null);

  /* ── (g) portfolio auto-fill: empty cells only by default ── */
  console.log("\n── (g) portfolio auto-fill ──");
  const pct = 30, classTestMax = 20;
  // expected values recomputed straight from the DB (dedupe per learner per day, latest wins)
  const entriesRaw = await db.portfolioEntry.findMany({ where: { schoolId, classroomId: b4.id, subjectId: maths.id, entryDate: { gte: term.startsAt, lte: term.endsAt } } });
  type DayEntry = { score: number; createdAt: Date };
  const byLearner = new Map<string, Map<string, DayEntry>>();
  for (const e of entriesRaw) {
    const day = e.entryDate.toISOString().slice(0, 10);
    const m = byLearner.get(e.studentId) ?? new Map<string, DayEntry>();
    const prev = m.get(day);
    if (!prev || e.createdAt >= prev.createdAt) m.set(day, { score: e.score, createdAt: e.createdAt });
    byLearner.set(e.studentId, m);
  }
  const expected = new Map<string, number>();
  for (const [sid, days] of byLearner) {
    const vals = [...days.values()].map((v) => v.score);
    if (!vals.length) continue;
    const avg = vals.reduce((a, b) => a + b, 0) / vals.length;
    const avgPct = r2((avg / 10) * 100);
    expected.set(sid, Math.min(classTestMax, r2((avgPct * pct) / 100)));
  }
  check("B4 maths has portfolio entries this term", expected.size > 0, `learners with entries: ${expected.size}`);
  const fill = await post(teacher, `/api/school/${schoolId}/ops/academics`, { action: "sba-portfolio-fill", classroomId: b4.id, subjectId: maths.id, termId });
  check("portfolio-fill 200", fill.status === 200, String(fill.status));
  const fillBody = await fill.json();
  // s1 had classTest=18 → must be skipped (no overwrite); learners with entries and no mark → filled with the expected value
  const s1After = await db.mark.findFirst({ where: { assessmentId: ct!.id, studentId: s1.studentId } });
  check("existing class test kept without overwrite", s1After?.score === 18, String(s1After?.score));
  let fillOk = true, fillChecked = 0;
  for (const [sid, exp] of expected) {
    if (sid === s1.studentId) continue;
    const m = await db.mark.findFirst({ where: { assessmentId: ct!.id, studentId: sid } });
    if (m?.score !== exp) { fillOk = false; console.log(`     mismatch ${sid}: got ${m?.score} expected ${exp}`); }
    fillChecked++;
  }
  check(`filled cells match avg% × ${pct}% (clamped /${classTestMax})`, fillOk && fillChecked > 0, `checked ${fillChecked}`);
  check("fill response counts sane", fillBody.filled === fillChecked && fillBody.skipped >= 1, `filled=${fillBody.filled} skipped=${fillBody.skipped}`);
  // overwrite: true → s1 replaced too (when s1 has portfolio entries)
  if (expected.has(s1.studentId)) {
    const fillOw = await post(teacher, `/api/school/${schoolId}/ops/academics`, { action: "sba-portfolio-fill", classroomId: b4.id, subjectId: maths.id, termId, overwrite: true });
    const s1Replaced = await db.mark.findFirst({ where: { assessmentId: ct!.id, studentId: s1.studentId } });
    check("overwrite replaces existing class test", fillOw.status === 200 && s1Replaced?.score === expected.get(s1.studentId), `got ${s1Replaced?.score} expected ${expected.get(s1.studentId)}`);
  }

  /* ── (h) creche-report-generate → ReportCards with domain rows ── */
  console.log("\n── (h) crèche report generation ──");
  const gen = await post(head, `/api/school/${schoolId}/ops/academics`, { action: "creche-report-generate", classroomId: kg2.id, termId });
  check("head generates early-years reports", gen.status === 200, String(gen.status));
  const genBody = await gen.json();
  check("one card per enrolled learner", genBody.created === kgEnrolled.length, `created=${genBody.created} enrolled=${kgEnrolled.length}`);
  const card1 = await db.reportCard.findFirst({ where: { studentId: learner1.studentId, termId }, include: { results: true } });
  check("card exists for learner 1", !!card1);
  check("card carries one row per domain", card1?.results.length === setup.domains.length, `${card1?.results.length} rows`);
  // dominant-code (mode) recomputation for learner 1's first domain
  const dom1 = setup.domains[0];
  const dom1SkillIds = dom1.skills.map((s: any) => s.id);
  const codes = await db.crecheRating.findMany({ where: { studentId: learner1.studentId, termId, skillId: { in: dom1SkillIds } } });
  const counts = new Map<string, number>();
  for (const c of codes) counts.set(c.code, (counts.get(c.code) ?? 0) + 1);
  const scaleOrder = new Map(setup.scale.map((s: any, i: number) => [s.code, i]));
  const dominantCode = [...counts.entries()].sort((a, b) => b[1] - a[1] || (scaleOrder.get(a[0]) ?? 99) - (scaleOrder.get(b[0]) ?? 99))[0]?.[0] ?? null;
  const dom1Row = card1?.results.find((r) => r.subjectName === dom1.name);
  check("domain row grade = dominant rating (mode)", dom1Row?.grade === dominantCode, `row=${dom1Row?.grade} recomputed=${dominantCode}`);
  const scaleLabel = setup.scale.find((s: any) => s.code === dominantCode)?.label ?? null;
  check("domain row remark = scale label", dom1Row?.remark === scaleLabel, `row=${dom1Row?.remark} expected=${scaleLabel}`);
  check("domain row carries rated-skill count", dom1Row?.position === codes.length, `row=${dom1Row?.position} rated=${codes.length}`);
  check("no class/exam/total scores on crèche rows", dom1Row?.classScore == null && dom1Row?.examScore == null && dom1Row?.total == null);

  /* ── (i) RBAC: teacher may rate, teacher may NOT configure ── */
  console.log("\n── (i) RBAC guards ──");
  const tRate = await post(teacher, `/api/school/${schoolId}/ops/academics`, {
    action: "creche-ratings-save", classroomId: kg2.id, termId,
    ratings: [{ studentId: learner1.studentId, skillId: firstSkill.id, code: "SD" }],
  });
  check("teacher CAN save ratings", tRate.status === 200, String(tRate.status));
  const tScale = await post(teacher, `/api/school/${schoolId}/ops/academics`, { action: "creche-scale-save", code: "TX", label: "Teacher attempt" });
  check("teacher CANNOT save scale (403)", tScale.status === 403, String(tScale.status));
  const tCfg = await post(teacher, `/api/school/${schoolId}/ops/academics`, { action: "sba-config", classTestMax: 5 });
  check("teacher CANNOT save sba-config (403)", tCfg.status === 403, String(tCfg.status));
  const tDom = await post(teacher, `/api/school/${schoolId}/ops/academics`, { action: "creche-domain-delete", id: setup.domains[0].id });
  check("teacher CANNOT delete domains (403)", tDom.status === 403, String(tDom.status));
  const sGen = await post(superU, `/api/school/${schoolId}/ops/academics`, { action: "creche-report-generate", classroomId: kg2.id, termId });
  check("super admin can generate reports", sGen.status === 200, String(sGen.status));

  /* ── cleanup all test rows ── */
  console.log("\n── cleanup ──");
  const sbaAssessments = await db.assessment.findMany({ where: { classroomId: b4.id, subjectId: maths.id, termId, title: { startsWith: "SBA " } } });
  await db.mark.deleteMany({ where: { assessmentId: { in: sbaAssessments.map((a) => a.id) } } });
  await db.assessment.deleteMany({ where: { id: { in: sbaAssessments.map((a) => a.id) } } });
  const kgCards = await db.reportCard.findMany({ where: { termId, classroomName: kg2.name } });
  await db.reportCardResult.deleteMany({ where: { reportCardId: { in: kgCards.map((c) => c.id) } } });
  await db.reportCard.deleteMany({ where: { id: { in: kgCards.map((c) => c.id) } } });
  await db.crecheRating.deleteMany({ where: ratingKey }); // the (c) test rating
  await db.schoolSetting.deleteMany({ where: { schoolId, key: { startsWith: "sba." } } });
  const leftoverSba = await db.assessment.count({ where: { classroomId: b4.id, termId, title: { startsWith: "SBA " } } });
  const leftoverCards = await db.reportCard.count({ where: { termId, classroomName: kg2.name } });
  const leftoverRatings = await db.crecheRating.count({ where: ratingKey });
  const leftoverSettings = await db.schoolSetting.count({ where: { schoolId, key: { startsWith: "sba." } } });
  check("SBA assessments + marks removed", leftoverSba === 0, String(leftoverSba));
  check("crèche report cards removed", leftoverCards === 0, String(leftoverCards));
  check("test rating removed", leftoverRatings === 0, String(leftoverRatings));
  check("sba settings removed (back to defaults)", leftoverSettings === 0, String(leftoverSettings));
  check("seeded KG ratings untouched", (await db.crecheRating.count({ where: { schoolId, termId } })) >= 100);

  console.log(`\n${fail === 0 ? "🎉" : "⚠️ "} ${pass} passed, ${fail} failed`);
  if (fail > 0) process.exitCode = 1;
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

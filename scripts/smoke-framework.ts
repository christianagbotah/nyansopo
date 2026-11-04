/**
 * Smoke — curriculum framework library + framework-aligned lesson notes.
 * Uses the head teacher demo account against GEA (GES school).
 * Run: DATABASE_URL=... bun scripts/smoke-framework.ts
 */
import { db } from "../src/lib/db";

const BASE = process.env.SMOKE_BASE ?? "http://localhost:3000";
let pass = 0, fail = 0;
function ok(name: string, cond: boolean, extra = "") {
  if (cond) { pass++; console.log(`  ✅ ${name}`); }
  else { fail++; console.log(`  ❌ ${name}${extra ? ` — ${extra}` : ""}`); }
}

async function main() {
  const school = await db.school.findUniqueOrThrow({ where: { code: "GEA" } });
  const head = await db.user.findFirstOrThrow({ where: { email: "head@nyansapo.gh" } });
  const H = { "content-type": "application/json", "x-user-id": head.id };
  ok("head@nyansapo.gh resolved", !!head.id);

  // ── framework meta ──
  const meta = await (await fetch(`${BASE}/api/school/${school.id}/ops/curriculum-framework?meta=1`, { headers: H })).json();
  ok("meta: enabled curricula", Array.isArray(meta.enabled) && meta.enabled.includes("GES"), JSON.stringify(meta.enabled));
  ok("meta: coverage has GES Mathematics B4", (meta.coverage ?? []).some((c: any) => c.curriculum === "GES" && c.subjectName === "Mathematics" && c.gradeLevel === "B4"));

  // ── framework tree ──
  const tree = await (await fetch(`${BASE}/api/school/${school.id}/ops/curriculum-framework?curriculum=GES&subjectName=Mathematics&gradeLevel=B4`, { headers: H })).json();
  const number = (tree.strands ?? []).find((s: any) => s.name === "Number");
  ok("GES Maths B4: 4 strands", (tree.strands ?? []).length === 4, `got ${(tree.strands ?? []).length}`);
  ok("Number strand has sub-strands", (number?.subStrands ?? []).length >= 3);
  const counting = (number?.subStrands ?? []).find((b: any) => b.name.startsWith("Counting"));
  ok("Counting sub-strand has B4.1.1.1 standard", (counting?.standards ?? []).some((st: any) => st.code === "B4.1.1.1"), JSON.stringify(counting?.standards?.map((s: any) => s.code)));

  // ── framework CRUD (school-scoped addition) ──
  const created = await (await fetch(`${BASE}/api/school/${school.id}/ops/curriculum-framework`, {
    method: "POST", headers: H, body: JSON.stringify({ action: "indicator.save", standardId: counting?.standards?.[0]?.id, statement: `SMOKE indicator ${Date.now()}` }),
  })).json();
  ok("indicator.create", !!created?.indicator?.id);
  if (created?.indicator?.id) {
    const del = await fetch(`${BASE}/api/school/${school.id}/ops/curriculum-framework`, { method: "POST", headers: H, body: JSON.stringify({ action: "indicator.delete", id: created.indicator.id }) });
    ok("indicator.delete", del.ok);
  }

  // ── CSV import ──
  const csv = `curriculum,subjectName,gradeLevel,strand,strandCode,subStrand,subStrandCode,standard,standardCode,indicator,indicatorCode
GES,Science,B4,Diversity of Matter,B4.1,Living and non-living things,B4.1.1,SMOKE standard,B4.1.1.9,SMOKE import indicator,B4.1.1.9.1`;
  const imp = await (await fetch(`${BASE}/api/school/${school.id}/ops/curriculum-framework`, { method: "POST", headers: H, body: JSON.stringify({ action: "framework.import", csv }) })).json();
  ok("framework.import 1 row", imp.imported === 1 && imp.skipped === 0, JSON.stringify(imp));

  // ── lesson note with alignment (head is staff on GEA) ──
  const staff = await db.staff.findFirstOrThrow({ where: { schoolId: school.id, email: head.email } });
  const classroom = await db.classroom.findFirstOrThrow({ where: { schoolId: school.id, gradeLevel: { in: ["B4", "B5", "B6"] } } });
  const subject = await db.subject.findFirstOrThrow({ where: { schoolId: school.id, name: { in: ["Mathematics", "Maths"] } } });
  const term = await db.term.findFirstOrThrow({ where: { academicYear: { schoolId: school.id }, isCurrent: true } });
  const std = counting?.standards?.[0];
  const noteRes = await fetch(`${BASE}/api/school/${school.id}/ops/curriculum`, {
    method: "POST", headers: H,
    body: JSON.stringify({
      action: "note.save", classroomId: classroom.id, subjectId: subject.id, termId: term.id,
      weekNumber: 9, lessonDate: new Date().toISOString().slice(0, 10), title: `SMOKE aligned note ${Date.now()}`,
      objectives: "x", activities: "y", content: "z", status: "DRAFT",
      strandId: number.id, subStrandId: counting?.id, contentStandardId: std?.id,
      indicatorIds: std?.indicators?.map((i: any) => i.id) ?? [],
      coreCompetencies: "Critical Thinking and Problem Solving", tlm: "Bundle of sticks", assessment: "Exercise 4",
    }),
  });
  const noteJson = await noteRes.json();
  ok("note.save with alignment", noteRes.ok && !!noteJson.note?.id, JSON.stringify(noteJson).slice(0, 200));
  ok("note linked strand + standard", noteJson.note?.strandId === number.id && noteJson.note?.contentStandardId === std?.id);
  if (noteJson.note?.id) {
    const mismatch = await fetch(`${BASE}/api/school/${school.id}/ops/curriculum`, {
      method: "POST", headers: H,
      body: JSON.stringify({
        action: "note.save", id: noteJson.note.id, classroomId: classroom.id, subjectId: subject.id, termId: term.id,
        weekNumber: 9, lessonDate: new Date().toISOString().slice(0, 10), title: "SMOKE bad chain", status: "DRAFT",
        strandId: number.id, subStrandId: "not-a-real-sub",
      }),
    });
    const mj = await mismatch.json();
    ok("mismatched sub-strand rejected", mismatch.status === 400 && /sub-strand/i.test(mj.error ?? ""), JSON.stringify(mj));
    const del = await fetch(`${BASE}/api/school/${school.id}/ops/curriculum`, { method: "POST", headers: H, body: JSON.stringify({ action: "note.delete", id: noteJson.note.id }) });
    ok("note.delete cleanup", del.ok);
  }

  // cleanup import artifacts
  const smokeSubStrand = await db.curriculumSubStrand.findFirst({ where: { name: "Living and non-living things", strand: { subjectName: "Science", gradeLevel: "B4", curriculum: "GES" } }, include: { standards: true } });
  const smokeStd = smokeSubStrand?.standards.find((st) => st.statement === "SMOKE standard");
  if (smokeStd) await db.curriculumContentStandard.delete({ where: { id: smokeStd.id } });
  console.log(`\n${pass} passed, ${fail} failed`);
  if (fail) process.exit(1);
  await db.$disconnect();
}

main().catch((e) => { console.error(e); process.exit(1); });

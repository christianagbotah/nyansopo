/* Bulk CSV admission smoke test — template → validate (row errors, nothing
   created) → guardian-match warning → 409 without confirm → import with
   confirmGuardianReuse (rollNo sequence, NY-index format, guardian reuse) →
   duplicate re-import guard → RBAC. Cleans up everything it creates.
   Run: DATABASE_URL=postgresql://… bun scripts/smoke-bulk-admission.ts */
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
const normPhone = (p: string) => p.replace(/\s+/g, "");

async function main() {
  const [head, teacher] = await Promise.all([
    db.user.findUnique({ where: { email: "head@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "teacher@nyansapo.gh" } }),
  ]);
  if (!head || !teacher) { console.log("missing demo users — run the seed first"); return; }
  const schoolId = head.schoolId!;
  const H = (u: typeof head) => ({ "content-type": "application/json", "x-user-id": u.id });
  const get = async (u: typeof head, url: string) => fetch(BASE + url, { headers: H(u) });
  const post = async (u: typeof head, url: string, body: any) => fetch(BASE + url, { method: "POST", headers: H(u), body: JSON.stringify(body) });

  const classroom = await db.classroom.findFirst({ where: { schoolId } });
  if (!classroom) { console.log("no classroom in school"); return; }

  // a learner already on this school's roll — their guardian will be "reused"
  const existing = await db.student.findFirst({
    where: { enrollments: { some: { schoolId } }, guardianPhone: { not: "" } },
    orderBy: { nyansapoIndex: "asc" },
  });
  if (!existing) { console.log("no enrolled student to test guardian reuse"); return; }

  // pre-clean residue from any earlier crashed run of this script
  const residue = await db.student.findMany({
    where: { OR: [{ guardianPhone: "0550000011" }, { AND: [{ firstName: "Sibling" }, { lastName: "Test" }] }] },
    select: { id: true },
  });
  if (residue.length) {
    await db.enrollment.deleteMany({ where: { studentId: { in: residue.map((s) => s.id) } } });
    await db.student.deleteMany({ where: { id: { in: residue.map((s) => s.id) } } });
  }

  const baseStudentCount = await db.student.count();
  const baseRollCount = await db.enrollment.count({ where: { classroomId: classroom.id, status: "ACTIVE" } });
  console.log(`\nSchool ${schoolId} · class "${classroom.name}" · ${baseStudentCount} students · ${baseRollCount} on roll · reuse guardian of ${existing.firstName} (${existing.guardianPhone})`);

  const HEADER = "firstName,lastName,otherNames,gender,dateOfBirth,guardianName,guardianPhone,guardianAltPhone,guardianEmail,guardianOccupation,relationship,hometown,previousSchool";
  const rowNewGuardian = "Smoke,BulkOne,,M,2016-02-10,Row Guardian One,0550000011,,,Fisherman,Father,Tema,"; // valid
  const rowSibling = `Sibling,Test,,Female,2017-01-05,Row Guardian,"${existing.guardianPhone}",,row.guardian@example.com,Row Occupation,Mother,Accra,`; // valid + guardian reuse

  // ── (a) template ──
  console.log("\n── (a) CSV template download ──");
  const tplRes = await get(head, `/api/school/${schoolId}/ops/admissions?action=bulk-template&classroomId=${classroom.id}`);
  check("template returns 200", tplRes.status === 200, String(tplRes.status));
  check("content-type is text/csv", (tplRes.headers.get("content-type") ?? "").includes("text/csv"), tplRes.headers.get("content-type") ?? "");
  const disposition = tplRes.headers.get("content-disposition") ?? "";
  check("filename nyansapo-admissions-<class>.csv", disposition.includes("nyansapo-admissions-"), disposition);
  const tplText = await tplRes.text();
  const tplLines = tplText.split(/\r?\n/).filter((l) => l.trim() !== "");
  check("header row exact", tplLines[0] === HEADER, tplLines[0]);
  check("one example row, clearly marked", tplLines.length === 2 && tplLines[1].startsWith("Example"), tplLines[1] ?? "(none)");

  // ── (b) validate: per-row errors, nothing created ──
  console.log("\n── (b) validate 3 rows (1 valid, 1 missing lastName, 1 bad gender) ──");
  const csvB = [HEADER, rowNewGuardian, "Smoke,,Kesi,F,2016-03-11,Row Guardian Two,0550000012,,,Trader,Mother,Tema,", "Smoke,BadGender,,Boy,2016-04-12,Row Guardian Three,0550000013,,,Trader,Mother,Tema,"].join("\n");
  const valB = await post(head, `/api/school/${schoolId}/ops/admissions`, { action: "bulk-validate", classroomId: classroom.id, csv: csvB });
  check("validate returns 200", valB.status === 200, String(valB.status));
  const valBBody = await valB.json().catch(() => ({}));
  check("3 rows reported", valBBody.rows?.length === 3, JSON.stringify(valBBody.rows?.length));
  check("1 valid / 2 invalid", valBBody.validCount === 1 && valBBody.invalidCount === 2, `${valBBody.validCount}/${valBBody.invalidCount}`);
  const rowOk = valBBody.rows?.find((r: any) => r.name.includes("BulkOne"));
  const rowNoLast = valBBody.rows?.find((r: any) => r.line === 3);
  const rowBadGender = valBBody.rows?.find((r: any) => r.line === 4);
  check("valid row is ok with no errors", rowOk?.ok === true && rowOk.errors.length === 0, JSON.stringify(rowOk));
  check("missing lastName flagged", rowNoLast?.ok === false && rowNoLast.errors.some((e: string) => /last name/i.test(e)), JSON.stringify(rowNoLast?.errors));
  check("bad gender flagged", rowBadGender?.ok === false && rowBadGender.errors.some((e: string) => /gender/i.test(e)), JSON.stringify(rowBadGender?.errors));
  check("validate created nothing", (await db.student.count()) === baseStudentCount);

  // ── (c) validate: existing guardian phone → warning + guardianMatch ──
  console.log("\n── (c) validate row whose guardianPhone matches an existing learner ──");
  const csvC = [HEADER, tplLines[1], rowSibling].join("\n"); // example row must be skipped
  const valC = await post(head, `/api/school/${schoolId}/ops/admissions`, { action: "bulk-validate", classroomId: classroom.id, csv: csvC });
  check("validate returns 200", valC.status === 200, String(valC.status));
  const valCBody = await valC.json().catch(() => ({}));
  check("template example row skipped", valCBody.rows?.length === 1, JSON.stringify(valCBody.rows?.map((r: any) => r.line)));
  const sibRow = valCBody.rows?.[0];
  check("sibling row valid (warning only)", sibRow?.ok === true && valCBody.validCount === 1, JSON.stringify(sibRow));
  check("warning present", (sibRow?.warnings?.length ?? 0) >= 1, JSON.stringify(sibRow?.warnings));
  check("guardianMatch name+phone", sibRow?.guardianMatch?.name === existing.guardianName && sibRow?.guardianMatch?.phone === normPhone(existing.guardianPhone), JSON.stringify(sibRow?.guardianMatch));
  check("validate still created nothing", (await db.student.count()) === baseStudentCount);

  // ── (d) import without confirm → 409, nothing created ──
  console.log("\n── (d) import without confirmGuardianReuse → 409 ──");
  const impD = await post(head, `/api/school/${schoolId}/ops/admissions`, { action: "bulk-import", classroomId: classroom.id, csv: csvC });
  check("409 conflict", impD.status === 409, String(impD.status));
  const impDBody = await impD.json().catch(() => ({}));
  check("matches payload lists the guardian", impDBody.matches?.length === 1 && impDBody.matches[0]?.guardianMatch?.name === existing.guardianName, JSON.stringify(impDBody.matches));
  check("nothing created by the 409", (await db.student.count()) === baseStudentCount);

  // ── (e) import with confirmGuardianReuse → students + enrollments ──
  console.log("\n── (e) import with confirmGuardianReuse ──");
  const csvE = [HEADER, rowNewGuardian, rowSibling].join("\n");
  const impE = await post(head, `/api/school/${schoolId}/ops/admissions`, { action: "bulk-import", classroomId: classroom.id, csv: csvE, confirmGuardianReuse: true });
  check("import returns 200", impE.status === 200, String(impE.status));
  const impEBody = await impE.json().catch(() => ({}));
  check("2 created, 0 failed", impEBody.created === 2 && impEBody.failed?.length === 0, JSON.stringify({ created: impEBody.created, failed: impEBody.failed }));
  check("2 indexes returned", impEBody.indexes?.length === 2, JSON.stringify(impEBody.indexes?.length));
  const idxRe = /^NY-\d{4}-\d{6}$/;
  check("index format NY-YYYY-NNNNNN", impEBody.indexes?.every((x: any) => idxRe.test(x.index)), JSON.stringify(impEBody.indexes?.map((x: any) => x.index)));
  check("indexes unique", new Set(impEBody.indexes?.map((x: any) => x.index)).size === 2);
  check("student count +2", (await db.student.count()) === baseStudentCount + 2);

  const oneIdx = impEBody.indexes.find((x: any) => x.name.includes("BulkOne"));
  const sibIdx = impEBody.indexes.find((x: any) => x.name.includes("Sibling"));
  const one = oneIdx ? await db.student.findUnique({ where: { nyansapoIndex: oneIdx.index } }) : null;
  const sib = sibIdx ? await db.student.findUnique({ where: { nyansapoIndex: sibIdx.index } }) : null;
  check("both learners in db", !!one && !!sib, `${oneIdx?.index} / ${sibIdx?.index}`);
  if (one && sib) {
    check("index year = birth year (registry semantics)", one.nyansapoIndex.startsWith("NY-2016-") && sib.nyansapoIndex.startsWith("NY-2017-"), `${one.nyansapoIndex} / ${sib.nyansapoIndex}`);
    check("gender normalised (M / Female→F)", one.gender === "M" && sib.gender === "F", `${one.gender}/${sib.gender}`);
    check("new guardian uses row values", one.guardianName === "Row Guardian One" && one.guardianOccupation === "Fisherman" && one.guardianPhone === "0550000011", JSON.stringify({ n: one.guardianName, o: one.guardianOccupation, p: one.guardianPhone }));
    check("sibling reuses guardian record (name/occupation/email/address/altPhone)",
      sib.guardianName === existing.guardianName && sib.guardianOccupation === existing.guardianOccupation &&
      sib.guardianEmail === existing.guardianEmail && sib.guardianAddress === existing.guardianAddress && sib.guardianAltPhone === existing.guardianAltPhone,
      JSON.stringify({ n: sib.guardianName, o: sib.guardianOccupation, e: sib.guardianEmail, a: sib.guardianAddress, alt: sib.guardianAltPhone }));
    check("sibling keeps row relationship (Mother)", sib.relationship === "Mother", sib.relationship);
    check("row hometown/previousSchool captured", sib.hometown === "Accra" && one.hometown === "Tema", `${one.hometown}/${sib.hometown}`);

    const enrOne = await db.enrollment.findFirst({ where: { studentId: one.id }, orderBy: { admissionDate: "desc" } });
    const enrSib = await db.enrollment.findFirst({ where: { studentId: sib.id }, orderBy: { admissionDate: "desc" } });
    check("enrollments ACTIVE in the chosen class", enrOne?.status === "ACTIVE" && enrOne.classroomId === classroom.id && enrSib?.classroomId === classroom.id && enrSib.status === "ACTIVE");
    const rolls = [enrOne?.rollNo, enrSib?.rollNo].filter((r) => r != null).sort((a, b) => a - b);
    check("rollNos sequential & above baseline", rolls.length === 2 && rolls[0] > baseRollCount && rolls[1] - rolls[0] === 1, `rolls=${JSON.stringify(rolls)} base=${baseRollCount}`);
    check("rollNos match response", enrOne?.rollNo === oneIdx.rollNo && enrSib?.rollNo === sibIdx.rollNo, `${enrOne?.rollNo}/${enrSib?.rollNo} vs ${oneIdx.rollNo}/${sibIdx.rollNo}`);
  }

  // ── (f) re-import same CSV → all rows invalid as duplicates ──
  console.log("\n── (f) duplicate re-import guard ──");
  const impF = await post(head, `/api/school/${schoolId}/ops/admissions`, { action: "bulk-import", classroomId: classroom.id, csv: csvE, confirmGuardianReuse: true });
  check("re-import blocked 400", impF.status === 400, String(impF.status));
  const impFBody = await impF.json().catch(() => ({}));
  check("both rows invalid", impFBody.invalidCount === 2, JSON.stringify(impFBody.invalidCount));
  check("errors flag them as already on roll", (impFBody.rows ?? []).every((r: any) => r.errors.some((e: string) => /already enrolled/i.test(e))), JSON.stringify(impFBody.rows?.map((r: any) => r.errors)));
  check("no extra students created", (await db.student.count()) === baseStudentCount + 2);

  // ── (g) teacher without admissions.manage → 403 ──
  console.log("\n── (g) RBAC — teacher blocked ──");
  const tplT = await get(teacher, `/api/school/${schoolId}/ops/admissions?action=bulk-template&classroomId=${classroom.id}`);
  check("template GET 403", tplT.status === 403, String(tplT.status));
  const valT = await post(teacher, `/api/school/${schoolId}/ops/admissions`, { action: "bulk-validate", classroomId: classroom.id, csv: csvE });
  check("bulk-validate 403", valT.status === 403, String(valT.status));
  const impT = await post(teacher, `/api/school/${schoolId}/ops/admissions`, { action: "bulk-import", classroomId: classroom.id, csv: csvE, confirmGuardianReuse: true });
  check("bulk-import 403", impT.status === 403, String(impT.status));

  // ── cleanup: remove everything this script created ──
  console.log("\n── cleanup ──");
  const createdIds: string[] = (impEBody.indexes ?? []).map((x: any) => x.studentId);
  const delEnrollments = await db.enrollment.deleteMany({ where: { studentId: { in: createdIds } } });
  const delStudents = await db.student.deleteMany({ where: { id: { in: createdIds } } });
  check("enrollments removed", delEnrollments.count === 2, String(delEnrollments.count));
  check("students removed", delStudents.count === 2, String(delStudents.count));
  check("student count back to baseline", (await db.student.count()) === baseStudentCount);
  check("class roll back to baseline", (await db.enrollment.count({ where: { classroomId: classroom.id, status: "ACTIVE" } })) === baseRollCount);

  console.log(`\n${fail === 0 ? "🎉" : "⚠️ "} ${pass} passed, ${fail} failed`);
  if (fail > 0) process.exitCode = 1;
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

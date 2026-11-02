/* Curriculum module API smoke test — lifecycle round-trip as TEACHER (author)
   and SCHOOL_ADMIN (reviewer), plus RBAC guard checks.
   Run: DATABASE_URL=postgresql://… bun scripts/smoke-curriculum.ts */
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

  console.log("\n── overview GET (admin sees all, teacher sees own) ──");
  const adm = await (await get(head, `/api/school/${schoolId}/ops/curriculum`)).json();
  check("admin canReview=true", adm.canReview === true);
  check(`admin sees all 12 notes`, adm.notes?.length === 12, `(got ${adm.notes?.length})`);
  check("admin has review queue", typeof adm.pendingReview === "number" && adm.pendingReview >= 3, `(pending=${adm.pendingReview})`);
  check("completion stats present", (adm.staffStats?.length ?? 0) >= 2, `(staff=${adm.staffStats?.length})`);
  const isaac = adm.staffStats.find((s: any) => s.name.includes("Isaac"));
  check("Isaac compliance computed", isaac && isaac.weeksElapsed >= 7 && isaac.compliancePct > 0, JSON.stringify(isaac));

  const tch = await (await get(teacher, `/api/school/${schoolId}/ops/curriculum`)).json();
  check("teacher canReview=false", tch.canReview === false);
  check("teacher sees own notes only", tch.notes?.length === 9, `(got ${tch.notes?.length})`);
  check("teacher notes all Isaac's", tch.notes.every((n: any) => n.staff.fullName.includes("Isaac")));

  console.log("\n── syllabus + materials tabs ──");
  const b4 = tch.classrooms.find((c: any) => c.name.includes("Basic 4"));
  const syl = await (await get(head, `/api/school/${schoolId}/ops/curriculum?tab=syllabus&classroomId=${b4.id}`)).json();
  check("syllabus topics load", syl.topics?.length === 30, `(got ${syl.topics?.length})`);
  check("coverage computed", syl.coverage?.total === 30 && syl.coverage.completed === 18, JSON.stringify(syl.coverage));
  const mats = await (await get(head, `/api/school/${schoolId}/ops/curriculum?tab=materials`)).json();
  check("materials load", mats.materials?.length === 4, `(got ${mats.materials?.length})`);

  console.log("\n── teacher lifecycle: create → duplicate guard → resubmit flow ──");
  const MATHS = tch.subjects.find((s: any) => s.name === "Mathematics");
  const termId = adm.terms.find((t: any) => t.isCurrent)?.id ?? adm.terms[0].id;
  const created = await post(teacher, `/api/school/${schoolId}/ops/curriculum`, {
    action: "note.save", classroomId: b4.id, subjectId: MATHS.id, termId, weekNumber: 13,
    lessonDate: "2026-11-30", title: "SMOKE — division as sharing", objectives: "test", activities: "test", content: "test",
  });
  check("teacher creates note (submitted)", created.status === 200, String(created.status));
  const createdBody = await created.json().catch(() => ({}));
  const noteId = createdBody.note?.id;
  check("create returns note id", !!noteId);

  const dup = await post(teacher, `/api/school/${schoolId}/ops/curriculum`, {
    action: "note.save", classroomId: b4.id, subjectId: MATHS.id, termId, weekNumber: 13,
    lessonDate: "2026-11-30", title: "SMOKE — duplicate week", objectives: "", activities: "", content: "",
  });
  check("duplicate week blocked 409", dup.status === 409, String(dup.status));

  const badWeek = await post(teacher, `/api/school/${schoolId}/ops/curriculum`, {
    action: "note.save", classroomId: b4.id, subjectId: MATHS.id, termId, weekNumber: 16,
    lessonDate: "2026-11-30", title: "bad week",
  });
  check("week 16 rejected 400", badWeek.status === 400, String(badWeek.status));

  console.log("\n── review flow: return → teacher revises → endorse ──");
  const ret = await post(head, `/api/school/${schoolId}/ops/curriculum`, {
    action: "note.review", id: noteId, decision: "REQUEST_REVISION", comment: "SMOKE: add differentiation",
  });
  check("reviewer returns note", ret.status === 200, String(ret.status));

  const noComment = await post(head, `/api/school/${schoolId}/ops/curriculum`, {
    action: "note.review", id: noteId, decision: "REQUEST_REVISION", comment: "",
  });
  check("return without comment blocked", noComment.status === 400 || noComment.status === 409, String(noComment.status));

  const teacherReview = await post(teacher, `/api/school/${schoolId}/ops/curriculum`, {
    action: "note.review", id: noteId, decision: "ENDORSE",
  });
  check("teacher cannot review (403)", teacherReview.status === 403, String(teacherReview.status));

  const revise = await post(teacher, `/api/school/${schoolId}/ops/curriculum`, {
    action: "note.save", id: noteId, classroomId: b4.id, subjectId: MATHS.id, termId, weekNumber: 13,
    lessonDate: "2026-11-30", title: "SMOKE — division as sharing (revised)", objectives: "v2", activities: "v2 + support group", content: "v2",
  });
  check("teacher revises returned note", revise.status === 200, String(revise.status));

  const endorse = await post(head, `/api/school/${schoolId}/ops/curriculum`, {
    action: "note.review", id: noteId, decision: "ENDORSE",
  });
  check("reviewer endorses", endorse.status === 200, String(endorse.status));

  const immutable = await post(teacher, `/api/school/${schoolId}/ops/curriculum`, {
    action: "note.save", id: noteId, classroomId: b4.id, subjectId: MATHS.id, termId, weekNumber: 13,
    lessonDate: "2026-12-01", title: "SMOKE — should not save",
  });
  check("endorsed note immutable (409)", immutable.status === 409, String(immutable.status));

  console.log("\n── syllabus toggle + topic CRUD ──");
  const topic = syl.topics.find((t: any) => !t.completed);
  const toggle = await post(head, `/api/school/${schoolId}/ops/curriculum`, {
    action: "syllabus.save", id: topic.id, classroomId: b4.id, subjectId: topic.subjectId, weekNumber: topic.weekNumber, topic: topic.topic, completed: true,
  });
  check("topic marked taught", toggle.status === 200, String(toggle.status));
  const syl2 = await (await get(head, `/api/school/${schoolId}/ops/curriculum?tab=syllabus&classroomId=${b4.id}`)).json();
  check("coverage +1 after toggle", syl2.coverage.completed === syl.coverage.completed + 1, `${syl.coverage.completed} → ${syl2.coverage.completed}`);
  const back = await post(head, `/api/school/${schoolId}/ops/curriculum`, {
    action: "syllabus.save", id: topic.id, classroomId: b4.id, subjectId: topic.subjectId, weekNumber: topic.weekNumber, topic: topic.topic, completed: false,
  });
  check("topic reopened", back.status === 200);

  console.log("\n── materials + templates CRUD, ownership guards ──");
  const mat = await post(teacher, `/api/school/${schoolId}/ops/curriculum`, {
    action: "material.save", classroomId: b4.id, subjectId: MATHS.id, title: "SMOKE material", body: "inline notes",
  });
  check("teacher shares material", mat.status === 200, String(mat.status));
  const matBody = await mat.json().catch(() => ({}));
  const matDel = await post(head, `/api/school/${schoolId}/ops/curriculum`, { action: "material.delete", id: matBody.material?.id });
  check("reviewer can remove material", matDel.status === 200, String(matDel.status));

  const tpl = await post(head, `/api/school/${schoolId}/ops/curriculum`, {
    action: "template.save", name: "SMOKE template", objectives: "o", activities: "a", content: "c",
  });
  const tplBody = await tpl.json().catch(() => ({}));
  check("template created", tpl.status === 200 && !!tplBody.template?.id);
  const tplDel = await post(head, `/api/school/${schoolId}/ops/curriculum`, { action: "template.delete", id: tplBody.template?.id });
  check("template deleted", tplDel.status === 200);

  console.log("\n── cleanup smoke note ──");
  const del = await post(head, `/api/school/${schoolId}/ops/curriculum`, { action: "note.delete", id: noteId });
  check("smoke note removed", del.status === 200, String(del.status));

  console.log(`\n${fail === 0 ? "🎉" : "⚠️ "} ${pass} passed, ${fail} failed`);
  if (fail > 0) process.exitCode = 1;
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

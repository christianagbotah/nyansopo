/* Lesson-note SMS notifications smoke test — teacher-directed triggers
   (LESSON_NOTE_RETURNED / LESSON_NOTE_ENDORSED), gating (school toggle ×
   per-teacher opt-out × credits), rule overrides, portal self-service,
   and the record-request notification deep-link.
   Run: DATABASE_URL=postgresql://… bun scripts/smoke-lesson-note-sms.ts */
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
  const testStart = new Date();
  const [superU, head, teacher] = await Promise.all([
    db.user.findUnique({ where: { email: "super@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "head@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "teacher@nyansapo.gh" }, include: { staff: true } }),
  ]);
  if (!superU || !head || !teacher) { console.log("missing demo users — run seed-rbac first"); return; }
  const schoolId = head.schoolId!;
  const staffId = teacher.staffId!;
  const H = (u: typeof head) => ({ "content-type": "application/json", "x-user-id": u.id });
  const get = async (u: typeof head, url: string) => fetch(BASE + url, { headers: H(u) });
  const post = async (u: typeof head, url: string, body: any) => fetch(BASE + url, { method: "POST", headers: H(u), body: JSON.stringify(body) });

  // ── fixture: teacher staff record has a valid Ghana phone, settings sane ──
  const origStaff = { phone: teacher.staff?.phone ?? null, smsNotify: teacher.staff?.smsNotify ?? true };
  await db.staff.update({ where: { id: staffId }, data: { phone: "0244555666", smsNotify: true } });
  await db.smsSetting.upsert({ where: { schoolId }, update: { autoLessonNoteAlerts: true, credits: 500 }, create: { schoolId, autoLessonNoteAlerts: true, credits: 500 } });

  const staffMsgs = async () => db.smsMessage.findMany({
    where: { schoolId, category: "STAFF", sentAt: { gte: testStart } }, orderBy: { sentAt: "asc" },
  });

  // create a fresh PENDING note (week 13 slot reused: each note is deleted after its scenario)
  async function makeNote(title: string) {
    const tch = await (await get(teacher, `/api/school/${schoolId}/ops/curriculum`)).json();
    const b4 = tch.classrooms.find((c: any) => c.name.includes("Basic 4"));
    const MATHS = tch.subjects.find((s: any) => s.name === "Mathematics");
    const termId = tch.terms.find((t: any) => t.isCurrent)?.id ?? tch.terms[0].id;
    const res = await post(teacher, `/api/school/${schoolId}/ops/curriculum`, {
      action: "note.save", classroomId: b4.id, subjectId: MATHS.id, termId, weekNumber: 13,
      lessonDate: "2026-11-30", title, objectives: "o", activities: "a", content: "c",
    });
    const body = await res.json().catch(() => ({}));
    return { res, noteId: body.note?.id as string | undefined, classroom: b4.name as string, subject: MATHS.name as string };
  }
  const dropNote = async (id: string) => { await post(head, `/api/school/${schoolId}/ops/curriculum`, { action: "note.delete", id }); };

  console.log("\n── A. return-for-revision sends the default teacher SMS ──");
  {
    const { res, noteId } = await makeNote("SMOKE-SMS — fractions of a group");
    check("note created (PENDING)", res.status === 200 && !!noteId, String(res.status));
    const ret = await post(head, `/api/school/${schoolId}/ops/curriculum`, { action: "note.review", id: noteId, decision: "REQUEST_REVISION", comment: "SMOKE: add a concrete example" });
    check("reviewer returns note", ret.status === 200, String(ret.status));
    const msgs = await staffMsgs();
    check("exactly one STAFF sms", msgs.length === 1, `(got ${msgs.length})`);
    const m = msgs[0];
    check("recipient is the teacher", m?.recipientName?.includes("Isaac") && m.recipientPhone === "0244555666", `${m?.recipientName} ${m?.recipientPhone}`);
    check("status SENT (valid Ghana number)", m?.status === "SENT", m?.status);
    check("body has note + comment + teacher", !!m && m.body.includes("fractions of a group") && m.body.includes("add a concrete example") && m.body.includes("Isaac"), m?.body);
    check("sentByName is the reviewer", m?.sentByName === head.fullName, `${m?.sentByName} ≠ ${head.fullName}`);
    check("credits debited", m?.creditsUsed === 1, String(m?.creditsUsed));
    check("no student linked (teacher-directed)", m?.studentId === null || m?.studentId === undefined, String(m?.studentId));
    if (noteId) await dropNote(noteId);
  }

  console.log("\n── B. active rule overrides the returned-note body ──");
  {
    const rule = await db.smsAutomation.create({ data: { schoolId, name: "SMOKE returned rule", trigger: "LESSON_NOTE_RETURNED", recipients: "TEACHERS", body: "{teacher}: fix week {week} {subject} — \"{comment}\" ({reviewer})" } });
    const { noteId } = await makeNote("SMOKE-SMS — place value chart");
    await post(head, `/api/school/${schoolId}/ops/curriculum`, { action: "note.review", id: noteId, decision: "REQUEST_REVISION", comment: "SMOKE: enlarge the chart" });
    const msgs = await staffMsgs();
    const m = msgs[msgs.length - 1];
    check("rule body used with placeholders", !!m && m.body === 'Mr. Isaac Boateng: fix week 13 Mathematics — "SMOKE: enlarge the chart" (Madam Grace Mensah)', m?.body);
    check("rule sentCount incremented", (await db.smsAutomation.findUnique({ where: { id: rule.id } }))?.sentCount === 1);
    await db.smsAutomation.delete({ where: { id: rule.id } });
    if (noteId) await dropNote(noteId);
  }

  console.log("\n── C. endorsement is opt-in: no rule → no SMS ──");
  {
    const { noteId } = await makeNote("SMOKE-SMS — reading comprehension");
    const before = (await staffMsgs()).length;
    const end = await post(head, `/api/school/${schoolId}/ops/curriculum`, { action: "note.review", id: noteId, decision: "ENDORSE" });
    check("reviewer endorses", end.status === 200, String(end.status));
    check("no endorsement sms without a rule", (await staffMsgs()).length === before, `before=${before} after=${(await staffMsgs()).length}`);
    if (noteId) await dropNote(noteId);
  }

  console.log("\n── D. endorsement with an active rule sends it ──");
  {
    const rule = await db.smsAutomation.create({ data: { schoolId, name: "SMOKE endorsed rule", trigger: "LESSON_NOTE_ENDORSED", recipients: "TEACHERS", body: "Well done {teacher} — {note} endorsed by {reviewer}." } });
    const { noteId } = await makeNote("SMOKE-SMS — weather charts");
    await post(head, `/api/school/${schoolId}/ops/curriculum`, { action: "note.review", id: noteId, decision: "ENDORSE" });
    const msgs = await staffMsgs();
    const m = msgs[msgs.length - 1];
    check("endorsement sms sent via rule", !!m && m.body === `Well done Mr. Isaac Boateng — SMOKE-SMS — weather charts endorsed by ${head.fullName}.`, m?.body);
    check("endorse rule counted", (await db.smsAutomation.findUnique({ where: { id: rule.id } }))?.sentCount === 1);
    await db.smsAutomation.delete({ where: { id: rule.id } });
    if (noteId) await dropNote(noteId);
  }

  console.log("\n── E. per-teacher opt-out (save-staff) + portal self-service ──");
  {
    const off = await post(head, `/api/school/${schoolId}/ops/payroll`, { action: "save-staff", staffId, smsNotify: false });
    check("admin opts teacher out (save-staff)", off.status === 200, String(off.status));
    const { noteId } = await makeNote("SMOKE-SMS — silent return");
    const before = (await staffMsgs()).length;
    await post(head, `/api/school/${schoolId}/ops/curriculum`, { action: "note.review", id: noteId, decision: "REQUEST_REVISION", comment: "SMOKE: should not text" });
    check("opted-out teacher gets no sms", (await staffMsgs()).length === before);
    if (noteId) await dropNote(noteId);

    const reOn = await post(teacher, `/api/school/${schoolId}/ops/portal`, { action: "staff.set-sms-preference", enabled: true });
    const reOnBody = await reOn.json().catch(() => ({}));
    check("teacher re-enables via portal", reOn.status === 200 && reOnBody.smsNotify === true, `${reOn.status} ${JSON.stringify(reOnBody)}`);
    const me = await (await get(teacher, `/api/school/${schoolId}/ops/portal?view=staff`)).json();
    check("portal me payload exposes smsNotify", me.me?.smsNotify === true, String(me.me?.smsNotify));

    await db.staff.update({ where: { id: staffId }, data: { phone: null } });
    const noPhone = await post(teacher, `/api/school/${schoolId}/ops/portal`, { action: "staff.set-sms-preference", enabled: true });
    check("enable without phone blocked (400)", noPhone.status === 400, String(noPhone.status));
    await db.staff.update({ where: { id: staffId }, data: { phone: "0244555666", smsNotify: true } });
  }

  console.log("\n── F. school-wide kill switch (SmsSetting.autoLessonNoteAlerts) ──");
  {
    const save = await post(head, `/api/school/${schoolId}/ops/sms`, { action: "save-settings", provider: "SIMULATED", senderId: "NYANSAPO", autoFeeReminders: true, autoAbsenceAlerts: true, autoPaymentReceipts: true, autoLessonNoteAlerts: false });
    const saveBody = await save.json().catch(() => ({}));
    check("toggle saved via API", save.status === 200 && saveBody.setting?.autoLessonNoteAlerts === false, `${save.status} ${JSON.stringify(saveBody.setting)}`);
    const { noteId } = await makeNote("SMOKE-SMS — muted return");
    const before = (await staffMsgs()).length;
    await post(head, `/api/school/${schoolId}/ops/curriculum`, { action: "note.review", id: noteId, decision: "REQUEST_REVISION", comment: "SMOKE: school muted" });
    check("no sms while school toggle off", (await staffMsgs()).length === before);
    if (noteId) await dropNote(noteId);
    await post(head, `/api/school/${schoolId}/ops/sms`, { action: "save-settings", provider: "SIMULATED", senderId: "NYANSAPO", autoFeeReminders: true, autoAbsenceAlerts: true, autoPaymentReceipts: true, autoLessonNoteAlerts: true });
    const smsGet = await (await get(head, `/api/school/${schoolId}/ops/sms`)).json();
    check("toggle restored & exposed in GET", smsGet.setting?.autoLessonNoteAlerts === true, JSON.stringify(smsGet.setting?.autoLessonNoteAlerts));
  }

  console.log("\n── G. out of credits: review still succeeds, sms logged FAILED ──");
  {
    await db.smsSetting.update({ where: { schoolId }, data: { credits: 0 } });
    const { noteId } = await makeNote("SMOKE-SMS — broke return");
    const ret = await post(head, `/api/school/${schoolId}/ops/curriculum`, { action: "note.review", id: noteId, decision: "REQUEST_REVISION", comment: "SMOKE: no credits" });
    check("review unaffected by sms failure", ret.status === 200, String(ret.status));
    const msgs = await staffMsgs();
    const m = msgs[msgs.length - 1];
    check("sms recorded FAILED with reason", m?.status === "FAILED" && /credits/i.test(m?.errorReason ?? ""), `${m?.status} ${m?.errorReason}`);
    check("no credits used", m?.creditsUsed === 0, String(m?.creditsUsed));
    if (noteId) await dropNote(noteId);
    await db.smsSetting.update({ where: { schoolId }, data: { credits: 500 } });
  }

  console.log("\n── H. record-request notification deep-links to Admissions › Record requests ──");
  {
    // ensure a pending incoming request exists for head's school
    const otherSchool = await db.school.findFirst({ where: { id: { not: schoolId } } });
    const student = await db.student.findFirst({ where: { enrollments: { some: { schoolId: otherSchool!.id, status: "ACTIVE" } } } });
    let rr = await db.recordRequest.findFirst({ where: { targetSchoolId: schoolId, status: "PENDING" } });
    let created: string | null = null;
    if (!rr && student) {
      const res = await post(superU, `/api/admissions/requests`, { action: "create", studentId: student.id, requestingSchoolId: otherSchool!.id, targetSchoolId: schoolId, purpose: "TRANSFER", notes: "SMOKE: deep-link fixture" });
      const body = await res.json().catch(() => ({}));
      created = body.request?.id ?? null;
      rr = await db.recordRequest.findFirst({ where: { targetSchoolId: schoolId, status: "PENDING" } });
    }
    check("pending incoming request exists", !!rr);
    const notif = await (await get(head, `/api/notifications?schoolId=${schoolId}`)).json();
    const item = notif.items?.find((i: any) => i.id === "record-requests");
    check("notification present", !!item);
    check("deep-links to admissions/requests", item?.module === "admissions" && item?.tab === "requests", `${item?.module}/${item?.tab}`);
    // admissions GET inbox still serves it
    const inbox = await (await get(head, `/api/admissions/requests?schoolId=${schoolId}`)).json();
    check("admissions inbox lists the request", (inbox.inbox ?? []).some((r: any) => r.id === rr?.id));
    if (created) await db.recordRequest.delete({ where: { id: created } }).catch(() => {});
  }

  console.log("\n── cleanup ──");
  const delMsgs = await db.smsMessage.deleteMany({ where: { schoolId, category: "STAFF", sentAt: { gte: testStart } } });
  check("smoke STAFF sms removed", delMsgs.count >= 1 && (await db.smsMessage.count({ where: { schoolId, category: "STAFF", sentAt: { gte: testStart } } })) === 0);
  await db.smsAutomation.deleteMany({ where: { schoolId, name: { startsWith: "SMOKE" } } });
  await db.staff.update({ where: { id: staffId }, data: { phone: origStaff.phone, smsNotify: origStaff.smsNotify } });
  await db.smsSetting.update({ where: { schoolId }, data: { credits: 500, autoLessonNoteAlerts: true } });
  const leftoverNotes = await db.lessonNote.findMany({ where: { schoolId, title: { contains: "SMOKE-SMS" } } });
  for (const n of leftoverNotes) await db.lessonNote.delete({ where: { id: n.id } }).catch(() => {});
  check("smoke notes removed", (await db.lessonNote.count({ where: { schoolId, title: { contains: "SMOKE-SMS" } } })) === 0);

  console.log(`\n${fail === 0 ? "🎉" : "⚠️ "} ${pass} passed, ${fail} failed`);
  if (fail > 0) process.exitCode = 1;
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

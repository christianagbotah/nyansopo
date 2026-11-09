/* Attendance ops API smoke test — save-register lifecycle (extended CI3
   statuses, EXCUSED-reason guard, offline-queue idempotency), the analytics
   aggregation (verified against direct DB counts), the monthly day-grid, and
   RBAC guard checks. Cleans up every row it creates.
   Run: DATABASE_URL=postgresql://… bun scripts/smoke-attendance-analytics.ts */
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
  const [head, teacher, parent] = await Promise.all([
    db.user.findUnique({ where: { email: "head@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "teacher@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "parent@nyansapo.gh" } }),
  ]);
  if (!head || !teacher) { console.log("missing demo users — run seed + seed-rbac first"); return; }
  const schoolId = head.schoolId!;
  const OPS = `/api/school/${schoolId}/ops/attendance`;
  const H = (u: NonNullable<typeof head>) => ({ "content-type": "application/json", "x-user-id": u.id });
  const get = async (u: NonNullable<typeof head>, url: string) => fetch(BASE + url, { headers: H(u) });
  const post = async (u: NonNullable<typeof head>, url: string, body: any) => fetch(BASE + url, { method: "POST", headers: H(u), body: JSON.stringify(body) });

  // Basic 4 — Gold: the teacher demo class (NOT one of the seed-attendance-extra classes)
  const b4 = await db.classroom.findFirst({ where: { schoolId, name: { contains: "Basic 4" } } });
  if (!b4) { console.log("Basic 4 classroom missing — run the base seed first"); return; }
  const students = (await db.enrollment.findMany({ where: { classroomId: b4.id, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" } })).map((e) => e.student);
  if (students.length < 8) { console.log("Basic 4 has too few learners for the mixed-status save"); return; }

  // smoke dates: Saturdays inside the current term — guaranteed session-free
  const D1 = "2026-12-05", D2 = "2026-12-12";
  const smokeStart = new Date();

  // defensive: clear any leftovers from a previous smoke run
  const stale = await db.attendanceSession.findMany({ where: { classroomId: b4.id, date: { in: [new Date(D1), new Date(D2)] } }, select: { id: true } });
  for (const s of stale) await db.attendanceRecord.deleteMany({ where: { sessionId: s.id } });
  if (stale.length) await db.attendanceSession.deleteMany({ where: { id: { in: stale.map((s) => s.id) } } });

  console.log("\n── (a) save-register: mixed statuses incl. EXCUSED with reason (TEACHER) ──");
  const recs = students.map((s, i) => {
    const st = ["PRESENT", "LATE", "ABSENT", "SICK_HOME", "SICK_CLINIC", "BUSY"][i] ?? "PRESENT";
    return i === 6 ? { studentId: s.id, status: "EXCUSED", excusedReason: "SMOKE: family funeral in Kumasi" } : { studentId: s.id, status: st };
  });
  const saveA = await post(teacher, OPS, { action: "save-register", classroomId: b4.id, date: D1, records: recs });
  check("teacher saves mixed statuses (200)", saveA.status === 200, String(saveA.status));
  const bodyA = await saveA.json().catch(() => ({}));
  check("save returns session id + saved count", !!bodyA.session?.id && bodyA.saved === students.length, JSON.stringify(bodyA));
  const sessionA = await db.attendanceSession.findFirst({ where: { classroomId: b4.id, date: new Date(D1) }, include: { records: true } });
  check("session created, taken by teacher", !!sessionA && sessionA.takenByName === teacher.fullName, sessionA?.takenByName ?? "none");
  check("every learner recorded", sessionA?.records.length === students.length, `${sessionA?.records.length} vs ${students.length}`);
  const recBy = new Map((sessionA?.records ?? []).map((r) => [r.studentId, r]));
  check(
    "extended statuses persisted (LATE/SICK_HOME/SICK_CLINIC/BUSY/EXCUSED)",
    recBy.get(students[1].id)?.status === "LATE" &&
    recBy.get(students[3].id)?.status === "SICK_HOME" &&
    recBy.get(students[4].id)?.status === "SICK_CLINIC" &&
    recBy.get(students[5].id)?.status === "BUSY" &&
    recBy.get(students[6].id)?.status === "EXCUSED",
  );
  check("EXCUSED reason persisted", recBy.get(students[6].id)?.excusedReason === "SMOKE: family funeral in Kumasi", recBy.get(students[6].id)?.excusedReason ?? "none");
  check("non-excused reason stays null", recBy.get(students[0].id)?.excusedReason === null);

  const reg = await (await get(head, `${OPS}?action=register&classId=${b4.id}&date=${D1}`)).json();
  const regExcused = reg.students?.find((s: any) => s.id === students[6].id);
  check("register GET round-trips marks + reason", reg.students?.length === students.length && regExcused?.status === "EXCUSED" && regExcused?.excusedReason === "SMOKE: family funeral in Kumasi", JSON.stringify(regExcused));

  console.log("\n── (b) validation: EXCUSED without reason / unknown status ──");
  const saveB = await post(teacher, OPS, { action: "save-register", classroomId: b4.id, date: D1, records: [{ studentId: students[0].id, status: "EXCUSED" }] });
  check("EXCUSED without reason blocked (400)", saveB.status === 400, String(saveB.status));
  const saveBad = await post(teacher, OPS, { action: "save-register", classroomId: b4.id, date: D1, records: [{ studentId: students[0].id, status: "VACATION" }] });
  check("unknown status blocked (400)", saveBad.status === 400, String(saveBad.status));
  const afterB = await db.attendanceRecord.count({ where: { sessionId: sessionA!.id } });
  check("rejected saves left the register untouched", afterB === students.length, `${afterB} vs ${students.length}`);

  console.log("\n── (c) offline-queue idempotency: same clientId twice ──");
  const recsC = students.slice(0, 3).map((s, i) => ({ studentId: s.id, status: ["PRESENT", "ABSENT", "LATE"][i] }));
  const c1 = await post(teacher, OPS, { action: "save-register", classroomId: b4.id, date: D2, records: recsC, clientId: "SMOKE-IDEM-1", offline: true });
  check("first clientId save (200)", c1.status === 200, String(c1.status));
  const c2 = await post(teacher, OPS, { action: "save-register", classroomId: b4.id, date: D2, records: recsC, clientId: "SMOKE-IDEM-1", offline: true });
  const bodyC2 = await c2.json().catch(() => ({}));
  check("replay flagged duplicate", c2.status === 200 && bodyC2.duplicate === true, JSON.stringify(bodyC2));
  const sessionsD2 = await db.attendanceSession.findMany({ where: { classroomId: b4.id, date: new Date(D2) }, include: { records: true } });
  check("exactly one session for (class, date)", sessionsD2.length === 1, String(sessionsD2.length));
  check("records not duplicated", sessionsD2[0]?.records.length === 3, String(sessionsD2[0]?.records.length));
  check("SYNC marker stamped on taker", sessionsD2[0]?.takenByName.includes("SYNC:SMOKE-IDEM-1") === true, sessionsD2[0]?.takenByName ?? "none");
  check("offline marks flagged markedOffline", sessionsD2[0]?.records.every((r) => r.markedOffline === true) === true);

  console.log("\n── (d) analytics: per-student counts for a known seeded range ──");
  const b6 = await db.classroom.findFirst({ where: { schoolId, name: { contains: "Basic 6" } } });
  const from = "2026-10-13", to = "2026-11-02"; // the seed-attendance-extra window
  const dbSessions = await db.attendanceSession.findMany({
    where: { schoolId, classroomId: b6!.id, date: { gte: new Date(from), lte: new Date(`${to}T23:59:59.999Z`) } },
    include: { records: true },
  });
  const exp = new Map<string, { present: number; late: number; absent: number; sickHome: number; sickClinic: number; busy: number; excused: number; marked: number }>();
  for (const s of dbSessions) for (const r of s.records) {
    const e = exp.get(r.studentId) ?? { present: 0, late: 0, absent: 0, sickHome: 0, sickClinic: 0, busy: 0, excused: 0, marked: 0 };
    e.marked++;
    if (r.status === "PRESENT") e.present++;
    else if (r.status === "LATE") e.late++;
    else if (r.status === "ABSENT") e.absent++;
    else if (r.status === "SICK_HOME") e.sickHome++;
    else if (r.status === "SICK_CLINIC") e.sickClinic++;
    else if (r.status === "BUSY") e.busy++;
    else if (r.status === "EXCUSED") e.excused++;
    exp.set(r.studentId, e);
  }
  const an = await (await get(head, `${OPS}?action=analytics&classroomId=${b6!.id}&from=${from}&to=${to}`)).json();
  check("session count matches DB", an.summary?.sessions === dbSessions.length, `${an.summary?.sessions} vs ${dbSessions.length}`);
  check("total marks match DB", an.summary?.marked === [...exp.values()].reduce((s, v) => s + v.marked, 0), String(an.summary?.marked));
  const sample = [...exp.entries()].find(([, v]) => v.marked > 5) ?? [...exp.entries()][0];
  if (sample) {
    const [sid, v] = sample;
    const row = an.rows?.find((r: any) => r.studentId === sid);
    check(
      "per-student counts match DB",
      !!row && row.present === v.present && row.late === v.late && row.absent === v.absent && row.marked === v.marked && row.sickHome === v.sickHome && row.sickClinic === v.sickClinic && row.busy === v.busy && row.excused === v.excused,
      JSON.stringify({ db: v, api: row }),
    );
    const rate = v.marked ? Math.round(((v.present + v.late) / v.marked) * 100) : null;
    check("rate = (present + late) / marked", row?.rate === rate, `${row?.rate} vs ${rate}`);
  }
  check("trend covers the school days in range", (an.trend?.length ?? 0) >= 10, String(an.trend?.length));

  console.log("\n── (e) monthly grid: day-status map (+ EXCUSED reason) ──");
  const mo = await (await get(head, `${OPS}?action=monthly&month=2026-10&classroomId=${b6!.id}`)).json();
  check("daysInMonth for October", mo.daysInMonth === 31, String(mo.daysInMonth));
  const octExpected: Record<string, Record<string, string>> = {};
  for (const s of dbSessions.filter((s) => s.date.toISOString().slice(0, 7) === "2026-10")) {
    const day = String(s.date.getUTCDate());
    for (const r of s.records) (octExpected[r.studentId] ??= {})[day] = r.status;
  }
  let moOk = true, moCount = 0;
  for (const [sid, days] of Object.entries(octExpected)) {
    const stu = mo.students?.find((x: any) => x.id === sid);
    if (!stu) { moOk = false; break; }
    for (const [day, status] of Object.entries(days)) {
      moCount++;
      if (stu.days?.[day]?.status !== status) moOk = false;
    }
  }
  check("day-status map matches DB", moOk && moCount >= 20, `checked ${moCount} day-marks`);
  const exc = await db.attendanceRecord.findFirst({
    where: { status: "EXCUSED", session: { classroomId: b6!.id, date: { gte: new Date("2026-10-01"), lt: new Date("2026-11-01") } } },
    include: { session: true },
  });
  if (exc) {
    const day = String(exc.session.date.getUTCDate());
    const stu = mo.students?.find((x: any) => x.id === exc.studentId);
    check("EXCUSED reason surfaced in the grid payload", stu?.days?.[day]?.reason === exc.excusedReason, JSON.stringify(stu?.days?.[day]));
  } else {
    check("EXCUSED record exists for B6 October (seed)", false, "none found — re-run prisma/seed-attendance-extra.ts");
  }

  console.log("\n── (f) RBAC: teacher can save, parent cannot ──");
  const parentSave = parent
    ? await post(parent, OPS, { action: "save-register", classroomId: b4.id, date: D1, records: [{ studentId: students[0].id, status: "PRESENT" }] })
    : null;
  check("parent (no attendance perms) blocked (403)", parentSave?.status === 403, String(parentSave?.status));
  const unknown = await post(head, OPS, { action: "nonsense" });
  check("unknown action (404)", unknown.status === 404, String(unknown.status));
  console.log("  ℹ️  (g) mark-all buttons are UI-only — verified by tsc/lint of attendance.tsx (no API surface)");

  console.log("\n── cleanup: remove everything this smoke created ──");
  const created = await db.attendanceSession.findMany({ where: { classroomId: b4.id, date: { in: [new Date(D1), new Date(D2)] } }, select: { id: true } });
  for (const s of created) await db.attendanceRecord.deleteMany({ where: { sessionId: s.id } });
  if (created.length) await db.attendanceSession.deleteMany({ where: { id: { in: created.map((s) => s.id) } } });
  // absence-alert SMS fired by the online save in (a) + credit refund
  const smokeSms = await db.smsMessage.findMany({ where: { schoolId, category: "ATTENDANCE", sentAt: { gte: smokeStart } } });
  const credits = smokeSms.filter((m) => m.status === "SENT").length;
  for (const m of smokeSms) await db.smsMessage.delete({ where: { id: m.id } });
  if (credits > 0) await db.smsSetting.update({ where: { schoolId }, data: { credits: { increment: credits } } });
  check("smoke sessions removed", (await db.attendanceSession.count({ where: { classroomId: b4.id, date: { in: [new Date(D1), new Date(D2)] } } })) === 0);
  check("smoke SMS removed", (await db.smsMessage.count({ where: { schoolId, category: "ATTENDANCE", sentAt: { gte: smokeStart } } })) === 0);

  console.log(`\n${fail === 0 ? "🎉" : "⚠️ "} ${pass} passed, ${fail} failed`);
  if (fail > 0) process.exitCode = 1;
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

/* Wave 39 — Sync Center demo data (CI3 sync_devices / sync_conflicts /
   sync_audit_log / sync_metrics parity).

   Seeds the Sync Center with a lived-in state so the dashboard, devices,
   conflicts, journal and policy tabs have something real to render:
     · 4 devices for Golden Excellence Academy (POS tablets, a scanner,
       the front-desk workstation) with flush history
     · ~40 flush/registration journal events over the last 14 days, with
       plausible synced/failed/conflict counts and durations
     · 2 PENDING conflicts — a marks collision and an attendance collision,
       both built from REAL assessment/classroom/learner rows so the
       side-by-side diff renders actual names and scores
     · 3 resolved conflicts with resolution history

   Idempotent: skips when devices already exist.
   Run: DATABASE_URL=... bun prisma/seed-sync.ts */
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

async function main() {
  const school = await db.school.findFirst({ where: { name: "Golden Excellence Academy" } });
  if (!school) throw new Error("Golden Excellence Academy not found — run the base seed chain first");

  const existing = await db.syncDevice.count({ where: { schoolId: school.id } });
  if (existing > 0) {
    console.log(`Sync Center seed skipped — ${existing} devices already exist`);
    return;
  }

  // ── devices ──
  const devices = [
    { code: "DEV-0001", name: "Canteen POS tablet", kind: "POS_TABLET", notes: "Front counter, right till", opsSynced: 412, opsFailed: 3 },
    { code: "DEV-0002", name: "Gate attendance scanner", kind: "SCANNER", notes: "Main gate barcode station", opsSynced: 968, opsFailed: 0 },
    { code: "DEV-0003", name: "Front desk workstation", kind: "FRONT_DESK", notes: "Reception Chrome box", opsSynced: 156, opsFailed: 1 },
    { code: "DEV-0004", name: "Bursar's Chrome browser", kind: "BROWSER", notes: null, opsSynced: 89, opsFailed: 0 },
  ] as const;
  const created: { id: string; code: string }[] = [];
  for (let i = 0; i < devices.length; i++) {
    const d = devices[i];
    const dev = await db.syncDevice.create({
      data: {
        schoolId: school.id, code: d.code, name: d.name, kind: d.kind, notes: d.notes,
        status: "ACTIVE",
        opsSynced: d.opsSynced, opsFailed: d.opsFailed,
        lastSyncAt: new Date(Date.now() - (i + 1) * 42 * 60_000),
        lastSeenAt: new Date(Date.now() - (i + 1) * 42 * 60_000),
        lastIP: `10.0.${4 + i}.${20 + i * 9}`,
        lastUser: ["Mrs. Gifty Owusu (Cashier)", "Mr. Kwabena Mensah (Conductor)", "Ms. Abena Danso (Front Desk)", "Mr. Isaac Boateng (Bursar)"][i],
        createdAt: new Date(Date.now() - (14 - i) * 864e5),
      },
    });
    created.push({ id: dev.id, code: dev.code });
  }

  // ── journal: registrations + ~14 days of flushes ──
  const users = [
    { name: "Mrs. Gifty Owusu", detail: (n: number) => `daily receipt RCP-… (${n} ops) · receipt issued · student enrolled` },
    { name: "Mr. Kwabena Mensah", detail: (n: number) => `${n} marked · daily receipt issued` },
    { name: "Ms. Abena Danso", detail: (n: number) => `receipt issued · ${n} ops` },
    { name: "Mr. Isaac Boateng", detail: (n: number) => `marks saved · ${n} ops` },
  ];
  for (let i = 0; i < created.length; i++) {
    await db.syncEvent.create({
      data: {
        schoolId: school.id, deviceCode: created[i].code, userName: users[i].name, kind: "REGISTER",
        detail: `${devices[i].name} (${devices[i].kind}) registered`,
        createdAt: new Date(Date.now() - (14 - i) * 864e5),
      },
    });
  }
  let seeded = 0;
  for (let day = 13; day >= 0; day--) {
    const flushes = 1 + (day % 3 === 0 ? 2 : 0); // heavier on some days
    for (let f = 0; f < flushes; f++) {
      const dev = created[(day + f) % created.length];
      const u = users[(day + f) % users.length];
      const ok = 4 + ((day * 7 + f * 13) % 23);
      const failed = (day + f) % 11 === 0 ? 1 : 0;
      const conflicts = (day + f) % 7 === 3 ? 1 : 0;
      await db.syncEvent.create({
        data: {
          schoolId: school.id, deviceCode: dev.code, userName: u.name, kind: "FLUSH",
          opsSynced: ok, opsFailed: failed, conflicts,
          durationMs: 140 + ((day * 37 + f * 53) % 800),
          detail: u.detail(ok),
          createdAt: new Date(Date.now() - day * 864e5 - f * 3.5 * 3_600_000),
        },
      });
      seeded += ok;
    }
  }

  // ── pending conflicts built from REAL rows ──
  // 1) marks collision: an assessment with server marks
  const assessment = await db.assessment.findFirst({
    where: { schoolId: school.id },
    orderBy: { date: "desc" },
    include: { marks: { orderBy: { studentId: "asc" }, take: 5 } },
  });
  let pendingMarks = 0;
  if (assessment && assessment.marks.length >= 3) {
    const serverMarks = assessment.marks.slice(0, 5);
    const localMarks = serverMarks.map((m, i) => ({ studentId: m.studentId, score: (m.score ?? 6) + (i === 0 ? 2 : i === 2 ? -3 : 1) }));
    await db.syncConflict.create({
      data: {
        schoolId: school.id, deviceCode: created[3].code, localUser: "Mr. Isaac Boateng",
        table: "marks", recordKey: `Marks — ${assessment.title}`,
        localState: JSON.stringify({ schoolId: school.id, assessmentId: assessment.id, marks: localMarks }),
        remoteState: JSON.stringify({ marks: serverMarks.map((m) => ({ studentId: m.studentId, score: m.score, enteredOffline: m.enteredOffline })) }),
        localAt: new Date(Date.now() - 26 * 3_600_000),
        remoteAt: new Date(Date.now() - 8 * 3_600_000),
        status: "PENDING",
      },
    });
    pendingMarks = 1;
  }

  // 2) attendance collision: a classroom + today-ish session
  const session = await db.attendanceSession.findFirst({
    where: { schoolId: school.id },
    orderBy: { date: "desc" },
    include: { records: true, classroom: true },
  });
  let pendingAttendance = 0;
  if (session && session.records.length >= 3) {
    const localRecords = session.records.slice(0, 6).map((r, i) => ({
      studentId: r.studentId,
      status: i === 1 ? "ABSENT" : i === 4 ? "LATE" : r.status,
      excusedReason: null,
    }));
    await db.syncConflict.create({
      data: {
        schoolId: school.id, deviceCode: created[1].code, localUser: "Mr. Kwabena Mensah",
        table: "attendance", recordKey: `${session.classroom.name} · ${session.date.toDateString()}`,
        localState: JSON.stringify({ classroomId: session.classroomId, date: session.date, records: localRecords }),
        remoteState: JSON.stringify({ sessionId: session.id, takenByName: session.takenByName, records: session.records.slice(0, 6).map((r) => ({ studentId: r.studentId, status: r.status, excusedReason: r.excusedReason })) }),
        localAt: new Date(Date.now() - 5 * 3_600_000),
        remoteAt: session.date,
        status: "PENDING",
      },
    });
    pendingAttendance = 1;
  }

  // ── resolved conflict history ──
  const resolvedSeeds = [
    { table: "marks", recordKey: "Marks — Class test 2 (English)", resolution: "LOCAL_WINS", note: "Teacher's offline scores were the marked scripts — server rows were a stale auto-fill.", by: "Mr. Isaac Boateng (Bursar)", hoursAgo: 50 },
    { table: "attendance", recordKey: "Basic 4 Gold · register", resolution: "REMOTE_WINS", note: "Class teacher's register was authoritative; the queued scanner replay had a check-in for a learner who left early.", by: "Mrs. Grace Ampofo (Head)", hoursAgo: 74 },
    { table: "marks", recordKey: "Marks — SBA week 9", resolution: "IGNORED", note: "Both sides held the same scores after review — kept on file for the record.", by: "Mrs. Grace Ampofo (Head)", hoursAgo: 120 },
  ];
  for (const r of resolvedSeeds) {
    await db.syncConflict.create({
      data: {
        schoolId: school.id, deviceCode: created[2].code, localUser: "Ms. Abena Danso",
        table: r.table, recordKey: r.recordKey,
        localState: JSON.stringify({ note: "demo record — payloads elided" }),
        remoteState: JSON.stringify({ note: "demo record — payloads elided" }),
        localAt: new Date(Date.now() - (r.hoursAgo + 6) * 3_600_000),
        remoteAt: new Date(Date.now() - r.hoursAgo * 3_600_000),
        status: r.resolution === "IGNORED" ? "IGNORED" : "RESOLVED",
        resolution: r.resolution, resolvedBy: r.by, resolvedAt: new Date(Date.now() - (r.hoursAgo - 4) * 3_600_000),
        resolveNote: r.note,
        createdAt: new Date(Date.now() - (r.hoursAgo + 6) * 3_600_000),
      },
    });
    await db.syncEvent.create({
      data: {
        schoolId: school.id, deviceCode: created[2].code, userName: r.by.split(" (")[0], kind: "RESOLVE",
        conflicts: 1, detail: `${r.table} · ${r.recordKey} → ${r.resolution.replace("_", " ").toLowerCase()}`,
        createdAt: new Date(Date.now() - (r.hoursAgo - 4) * 3_600_000),
      },
    });
  }

  console.log(`Sync Center seed complete — ${created.length} devices, ~${seeded} ops across 14 days of flushes, ${pendingMarks + pendingAttendance} pending conflicts (${pendingMarks} marks · ${pendingAttendance} attendance), 3 resolved for Golden Excellence Academy`);
}

main()
  .catch((e) => { console.error(e); process.exit(1); })
  .finally(() => db.$disconnect());

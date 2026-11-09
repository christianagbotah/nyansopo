/* Incremental attendance seed — backfills ~3 weeks of register data for the
   two largest GEA classrooms with the extended CI3 status mix (SICK_HOME /
   SICK_CLINIC / BUSY / EXCUSED-with-reason), which the base seed.ts does not
   cover (it only writes PRESENT/ABSENT/LATE/SICK).

   Idempotent: for every school day in the window the seed skips any
   (classroom, date) that already has an AttendanceSession — so re-running is a
   no-op and existing sessions (base seed, live registers, offline syncs) are
   never touched or duplicated.

   Run: DATABASE_URL=postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public bun prisma/seed-attendance-extra.ts */
import { PrismaClient } from "@prisma/client";
const db = new PrismaClient();

/* deterministic PRNG so repeat runs on a fresh DB produce the same story */
function mulberry32(seed: number) {
  return () => {
    seed |= 0; seed = (seed + 0x6d2b79f5) | 0;
    let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}
const rnd = mulberry32(20261102);

const EXCUSE_REASONS = [
  "Family funeral in Kumasi",
  "Medical appointment — Ridge Hospital",
  "GPRTU drivers' strike — no transport",
  "Out of town with parents for the week",
  "Eid observance with family",
  "Dental emergency — tooth extraction",
  "Attending a cousin's outdooring ceremony",
  "Hospital referral — Korle Bu follow-up",
];

/** School days (Mon–Fri, UTC-midnight like every other session write) in [start, end]. */
function schoolDays(start: Date, end: Date): Date[] {
  const out: Date[] = [];
  for (let d = new Date(start); d <= end; d = new Date(d.getTime() + 864e5)) {
    const dow = d.getUTCDay();
    if (dow !== 0 && dow !== 6) out.push(new Date(d));
  }
  return out;
}

async function main() {
  const gea = await db.school.findFirst({ where: { code: "GEA" } });
  if (!gea) { console.log("GEA school not found — run the base seed first."); return; }

  // term-aware: current term of the current academic year, else the latest term
  const term =
    (await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId: gea.id } } })) ??
    (await db.term.findFirst({ where: { academicYear: { schoolId: gea.id } }, orderBy: { endsAt: "desc" } }));
  if (!term) { console.log("No term for GEA — run the base seed first."); return; }

  // the two classrooms with the largest active roll (tie-break by name)
  const classrooms = await db.classroom.findMany({
    where: { schoolId: gea.id },
    include: { enrollments: { where: { status: "ACTIVE" }, include: { student: true } }, classTeacher: true },
  });
  classrooms.sort((a, b) => b.enrollments.length - a.enrollments.length || a.name.localeCompare(b.name));
  const picked = classrooms.slice(0, 2);
  if (!picked.length || !picked.some((c) => c.enrollments.length)) { console.log("No classrooms with learners — nothing to backfill."); return; }

  // ~3-week window of school days ending today
  const end = new Date(new Date().toISOString().slice(0, 10)); // UTC midnight today
  const start = new Date(end.getTime() - 20 * 864e5);
  const days = schoolDays(start, end);

  let createdSessions = 0, createdRecords = 0, skipped = 0;
  for (const cls of picked) {
    const learners = cls.enrollments
      .slice()
      .sort((a, b) => (a.rollNo ?? 999) - (b.rollNo ?? 999))
      .map((e) => e.student);
    if (!learners.length) continue;
    const takenBy = cls.classTeacher?.fullName ?? "Class Teacher";

    let firstDayForClass = true; // guarantee the new statuses appear in the backfill
    for (const day of days) {
      // per-day guard: never duplicate an existing session for (classroom, date)
      const existing = await db.attendanceSession.findFirst({ where: { classroomId: cls.id, date: day } });
      if (existing) { skipped++; continue; }

      const session = await db.attendanceSession.create({
        data: { classroomId: cls.id, schoolId: gea.id, termId: term.id, date: day, takenByName: takenBy },
      });
      // everyone starts present, then sprinkle the realistic mix
      const marks = learners.map((st) => ({ st, status: "PRESENT", excusedReason: null as string | null }));
      for (const m of marks) {
        const r = rnd();
        if (r < 0.05) m.status = "LATE";
        else if (r < 0.09) m.status = "ABSENT";
        else if (r < 0.11) m.status = "SICK_HOME";
        else if (r < 0.12) m.status = "SICK_CLINIC";
        else if (r < 0.125) m.status = "SICK"; // legacy sick-bay mark
        else if (r < 0.13) m.status = "BUSY";
        else if (r < 0.14) { m.status = "EXCUSED"; m.excusedReason = EXCUSE_REASONS[Math.floor(rnd() * EXCUSE_REASONS.length)]; }
      }
      // first backfilled day per class: force one of each extended status so
      // the analytics demo shows the full CI3 mix regardless of the RNG draw
      if (firstDayForClass && learners.length >= 8) {
        const featured: [string, number][] = [["EXCUSED", 0], ["SICK_HOME", 1], ["SICK_CLINIC", 2], ["BUSY", 3], ["LATE", 4], ["ABSENT", 5]];
        for (const [status, idx] of featured) {
          marks[idx].status = status;
          marks[idx].excusedReason = status === "EXCUSED" ? EXCUSE_REASONS[Math.floor(rnd() * EXCUSE_REASONS.length)] : null;
        }
        firstDayForClass = false;
      }
      for (const m of marks) {
        await db.attendanceRecord.create({ data: { sessionId: session.id, studentId: m.st.id, status: m.status, excusedReason: m.excusedReason } });
        createdRecords++;
      }
      createdSessions++;
    }
  }

  console.log(`✅ attendance backfill — ${picked.map((c) => `${c.name} (${c.enrollments.length} learners)`).join(" · ")}`);
  console.log(`   window ${start.toISOString().slice(0, 10)} → ${end.toISOString().slice(0, 10)} (${days.length} school days, term: ${term.name})`);
  console.log(`   created ${createdSessions} sessions / ${createdRecords} records · ${skipped} days already had sessions (skipped)`);

  // guarantee the smoke-analytics precondition: at least one EXCUSED (with a
  // reason) inside the B6 October window — when seed.ts already covered those
  // days the featured-mark pass above never runs for them, so flip one ABSENT
  const b6 = picked.find((c) => c.name.includes("Basic 6"));
  if (b6) {
    const oct = await db.attendanceRecord.findFirst({
      where: {
        status: "ABSENT",
        session: { classroomId: b6.id, date: { gte: new Date("2026-10-01"), lt: new Date("2026-11-01") } },
      },
      orderBy: { id: "asc" },
    });
    if (oct) {
      await db.attendanceRecord.update({ where: { id: oct.id }, data: { status: "EXCUSED", excusedReason: EXCUSE_REASONS[0] } });
      console.log("   ensured one EXCUSED B6 October record for analytics parity");
    }
  }
}

export { main as seedAttendanceExtra };

if (process.argv[1]?.includes("seed-attendance-extra")) {
  main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());
}

/* Wave 28 — staff attendance & duty demo data for Golden Excellence Academy.

   Lays down the staff-side registers every Ghanaian school keeps beside the
   learner ones: the daily Staff Attendance Book over the past ~4 working
   weeks (clock-in/out with the GES 08:00 punctuality rule — PRESENT with
   07:1x–08:0x clock-ins, LATE with 08:1x+ clock-ins, ABSENT, LEAVE, OFF),
   the leave ledger (an approved annual trip, an approved sick week that
   stamps LEAVE onto the register, a pending annual request and a declined
   study leave), the noticeboard duty roster (7 duty slots × 5 weekdays,
   all filled), the staff movement book (two staff currently OUT — a circuit
   office trip and a bank run — plus closed history) and substitution cover
   records for the absent teacher's classes.

   Idempotent: skips when the staff register already holds rows.
   Run: DATABASE_URL=... bun prisma/seed-staff-daily.ts */
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();

let _s = 20261128;
function rnd() {
  _s |= 0; _s = (_s + 0x6d2b79f5) | 0;
  let t = Math.imul(_s ^ (_s >>> 15), 1 | _s);
  t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
  return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
}
const int = (min: number, max: number) => min + Math.floor(rnd() * (max - min + 1));
const pick = <T,>(a: T[]): T => a[Math.floor(rnd() * a.length)];

const dayStr = (d: Date) => d.toISOString().slice(0, 10);
const dayAt = (d: Date, h: number, m: number) => {
  const x = new Date(d);
  x.setUTCHours(h, m, 0, 0);
  return x;
};

/** Working-day (Mon–Fri) dates going back `count` days, oldest first. */
function workingDays(count: number, endOn: Date = new Date()): Date[] {
  const days: Date[] = [];
  const cursor = new Date(endOn);
  while (days.length < count) {
    const wd = cursor.getUTCDay();
    if (wd !== 0 && wd !== 6) days.unshift(new Date(cursor));
    cursor.setUTCDate(cursor.getUTCDate() - 1);
  }
  return days;
}

const DESTINATIONS = [
  { destination: "Adenta Circuit Office — GES", purpose: "Termly returns submission" },
  { destination: "Apex Bank, Adenta Branch", purpose: "School fees banking" },
  { destination: "District Education Office", purpose: "BECE registration workshop" },
  { destination: "Home", purpose: "Felt unwell after break" },
  { destination: "Supplies market, Madina", purpose: "Tuck-shop restocking with storekeeper" },
];

async function main() {
  const school = await db.school.findFirst({ where: { name: { contains: "Golden Excellence" } } });
  if (!school) throw new Error("Golden Excellence Academy not found — run prisma/seed.ts first");
  const schoolId = school.id;

  const existing = await db.staffAttendance.findFirst({ where: { schoolId } });
  if (existing) {
    console.log("staff-daily seed skipped — register already holds rows");
    return;
  }

  const staff = await db.staff.findMany({ where: { schoolId, active: true }, orderBy: { fullName: "asc" } });
  if (staff.length < 4) throw new Error("Not enough staff rows at GEA — run prisma/seed.ts first");
  const teacher = staff.find((s) => s.role === "TEACHER")!;
  const head = staff.find((s) => s.role === "HEADTEACHER") ?? staff[0];
  const otherTeachers = staff.filter((s) => s.role === "TEACHER" && s.id !== teacher.id);
  const bursar = staff.find((s) => s.role === "BURSAR") ?? staff[1];

  // ── 1. Attendance book: past 20 working days ──
  const days = workingDays(20);
  let attendanceRows = 0;
  for (const day of days) {
    for (const s of staff) {
      const roll = rnd();
      let status = "PRESENT";
      let clockIn: Date | null = null;
      let clockOut: Date | null = null;
      let note: string | null = null;
      if (roll < 0.04) {
        status = "ABSENT";
        note = pick(["No report — follow-up SMS sent", "Unwell, guardian of situation", "Family engagement — no notice"]);
      } else if (roll < 0.10) {
        status = "LATE";
        clockIn = dayAt(day, int(8, 9), int(5, 45));
        clockOut = dayAt(day, int(15, 17), int(0, 59));
        note = pick(["Traffic on Adenta road", "Overslept — cautioned", "Delayed by school run"]);
      } else if (roll < 0.12) {
        status = "OFF";
        note = "Off-duty day";
      } else if (roll < 0.14) {
        status = "LEAVE";
        note = "Approved leave";
      } else {
        status = "PRESENT";
        clockIn = dayAt(day, 7, int(5, 59));
        clockOut = dayAt(day, int(15, 17), int(0, 59));
      }
      await db.staffAttendance.create({
        data: { schoolId, staffId: s.id, date: day, status, clockIn, clockOut, note, markedByName: "Madam Grace Mensah" },
      });
      attendanceRows++;
    }
  }

  // ── 2. Leave ledger ──
  const pastStart = new Date(days[1]);
  const pastEnd = new Date(days[3]);
  await db.leaveRequest.create({
    data: {
      schoolId, staffId: otherTeachers[0]?.id ?? teacher.id, type: "ANNUAL",
      startsAt: pastStart, endsAt: pastEnd, days: 3, status: "APPROVED",
      reason: "Family engagement at Kumasi", requestedByName: (otherTeachers[0] ?? teacher).fullName,
      decidedByName: "Madam Grace Mensah", decidedAt: new Date(days[0]),
    },
  });
  const sickStart = new Date(days[days.length - 4]);
  const sickEnd = new Date(days[days.length - 2]);
  await db.leaveRequest.create({
    data: {
      schoolId, staffId: bursar.id, type: "SICK",
      startsAt: sickStart, endsAt: sickEnd, days: 3, status: "APPROVED",
      reason: "Malaria treatment — Ridge Hospital chit attached", requestedByName: bursar.fullName,
      decidedByName: "Madam Grace Mensah", decidedAt: sickStart,
    },
  });
  const nextMonday = new Date(days[days.length - 1]);
  nextMonday.setUTCDate(nextMonday.getUTCDate() + ((8 - nextMonday.getUTCDay()) % 7 || 7));
  const nextFriday = new Date(nextMonday);
  nextFriday.setUTCDate(nextFriday.getUTCDate() + 4);
  await db.leaveRequest.create({
    data: {
      schoolId, staffId: teacher.id, type: "ANNUAL",
      startsAt: nextMonday, endsAt: nextFriday, days: 5, status: "PENDING",
      reason: "Traditional marriage ceremonies — notice given a week ahead", requestedByName: teacher.fullName,
    },
  });
  await db.leaveRequest.create({
    data: {
      schoolId, staffId: otherTeachers[1]?.id ?? teacher.id, type: "STUDY",
      startsAt: new Date(days[6]), endsAt: new Date(days[9]), days: 4, status: "DECLINED",
      reason: "UEW residential exams block — request falls in revision week",
      requestedByName: (otherTeachers[1] ?? teacher).fullName,
      decidedByName: "Madam Grace Mensah", decidedAt: new Date(days[5]),
    },
  });

  // ── 3. Duty roster: every duty slot filled, evenly spread ──
  const WEEKDAYS = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
  const DUTIES = ["ASSEMBLY", "BREAK", "CLOSING", "GATE", "LIBRARY", "EXAMS", "OTHER"];
  const DUTY_NOTES: Record<string, string | null> = {
    ASSEMBLY: "Lead morning assembly & devotion",
    BREAK: "Supervise the canteen & compound",
    CLOSING: "Dismiss queues & gate check",
    GATE: "Learners' arrival screening",
    LIBRARY: "Open & man the library at break",
    EXAMS: "Exam logistics when in season",
    OTHER: "Runs & errands as assigned",
  };
  let rr = 0;
  for (const duty of DUTIES) {
    for (const weekday of WEEKDAYS) {
      await db.dutyAssignment.create({
        data: { schoolId, staffId: staff[rr % staff.length].id, weekday, duty, note: DUTY_NOTES[duty] },
      });
      rr++;
    }
  }

  // ── 4. Staff movement book: two currently OUT + closed history ──
  const now = new Date();
  await db.staffMovement.create({
    data: {
      schoolId, staffId: head.id, destination: DESTINATIONS[0].destination, purpose: DESTINATIONS[0].purpose,
      signedOutAt: new Date(now.getTime() - 2 * 3600_000), status: "OUT", recordedByName: "Front desk",
    },
  });
  await db.staffMovement.create({
    data: {
      schoolId, staffId: bursar.id, destination: DESTINATIONS[1].destination, purpose: DESTINATIONS[1].purpose,
      signedOutAt: new Date(now.getTime() - 40 * 60_000), status: "OUT", recordedByName: "Front desk",
    },
  });
  for (let i = 0; i < 6; i++) {
    const s = pick(staff);
    const out = new Date(now.getTime() - (i + 2) * 26 * 3600_000);
    const back = new Date(out.getTime() + int(1, 4) * 3600_000);
    const d = pick(DESTINATIONS);
    await db.staffMovement.create({
      data: { schoolId, staffId: s.id, destination: d.destination, purpose: d.purpose, signedOutAt: out, signedInAt: back, status: "BACK", recordedByName: "Front desk" },
    });
  }

  // ── 5. Substitution cover ──
  const absentTeacher = otherTeachers[0] ?? teacher;
  const coverTeacher = otherTeachers[1] ?? teacher;
  await db.substitutionLog.create({
    data: {
      schoolId, absentStaffId: absentTeacher.id, substituteStaffId: coverTeacher.id,
      date: days[days.length - 1], classRef: "Basic 6 Gold", periods: "1-3",
      note: "Maths + Science covered from the lesson notes on file",
      recordedByName: "Madam Grace Mensah",
    },
  });
  await db.substitutionLog.create({
    data: {
      schoolId, absentStaffId: coverTeacher.id, substituteStaffId: absentTeacher.id,
      date: days[days.length - 2], classRef: "JHS 2 (B8)", periods: "English, OWOP",
      note: "Joint-marking exercise done in place of class teaching",
      recordedByName: "Madam Grace Mensah",
    },
  });

  console.log(`✅ Staff attendance & duty seed — ${attendanceRows} register rows over 20 working days for ${staff.length} staff, 4 leave requests, ${DUTIES.length * WEEKDAYS.length} duty slots, 2 staff OUT + 6 closed movements, 2 substitution covers`);
}

main().catch((e) => { console.error(e); process.exit(1); }).finally(() => db.$disconnect());

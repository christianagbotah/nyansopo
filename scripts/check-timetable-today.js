const { PrismaClient } = require("@prisma/client");
const db = new PrismaClient();
(async () => {
  const today = new Date(`${new Date().toISOString().slice(0, 10)}T00:00:00.000Z`);
  const wd = ((today.getUTCDay() + 6) % 7) + 1;
  console.log("weekday:", wd);
  const slots = await db.timetableSlot.findMany({ where: { schoolId: (await db.school.findFirstOrThrow()).id, dayOfWeek: wd }, include: { staff: { select: { fullName: true } }, classroom: { select: { name: true } } }, orderBy: { startMinute: "asc" } });
  console.log("TODAY SLOTS:", JSON.stringify(slots.map((s) => ({ cls: s.classroom.name, sub: s.subjectName, staff: s.staff?.fullName, t: `${Math.floor(s.startMinute / 60)}:${String(s.startMinute % 60).padStart(2, "0")}` }))));
  const att = await db.staffAttendance.findMany({ where: { date: today }, include: { staff: { select: { fullName: true } } } });
  console.log("ATTENDANCE:", JSON.stringify(att.map((a) => ({ staff: a.staff.fullName, status: a.status }))));
  const staff = await db.staff.findMany({ where: { active: true, category: "TEACHING" }, select: { id: true, fullName: true, subjects: true } });
  console.log("TEACHERS:", JSON.stringify(staff.map((s) => ({ n: s.fullName, subj: s.subjects }))));
  process.exit(0);
})().catch((e) => { console.error(e.message); process.exit(1); });

/* Backfill: staff categories/positions/statutory fields + demo weekly timetables.
   Idempotent — safe to run repeatedly. */
import { PrismaClient } from "@prisma/client";
const db = new PrismaClient();

const POSITIONS_NON_TEACHING = [
  { name: "Mr. Yaw Anane", position: "Driver", dept: "Transport", salary: 1400_00 },
  { name: "Madam Akosua Dede", position: "Cook", dept: "Kitchen", salary: 1100_00 },
  { name: "Mr. Samuel Tetteh", position: "Security Guard", dept: "Security", salary: 950_00 },
  { name: "Madam Efua Badu", position: "Librarian", dept: "Administration", salary: 1350_00 },
  { name: "Mr. Daniel Owusu", position: "IT Technician", dept: "Administration", salary: 1800_00 },
  { name: "Madam Vida Appiah", position: "Cleaner", dept: "Maintenance", salary: 900_00 },
];

const TIMETABLES: Record<string, { subject: string; day: number; start: string; end: string; teacher?: string }[]> = {
  "Basic 4 Gold": [
    { subject: "Mathematics", day: 1, start: "08:00", end: "08:40", teacher: "Isaac Boateng" },
    { subject: "English Language", day: 1, start: "08:40", end: "09:20", teacher: "Abena Osei" },
    { subject: "Science", day: 1, start: "09:20", end: "10:00", teacher: "Isaac Boateng" },
    { subject: "OWOP", day: 1, start: "10:20", end: "11:00", teacher: "Abena Osei" },
    { subject: "Ghanaian Language", day: 1, start: "11:00", end: "11:40" },
    { subject: "Mathematics", day: 2, start: "08:00", end: "08:40", teacher: "Isaac Boateng" },
    { subject: "Creative Arts", day: 2, start: "08:40", end: "09:20" },
    { subject: "English Language", day: 2, start: "09:20", end: "10:00", teacher: "Abena Osei" },
    { subject: "Physical Education", day: 2, start: "10:20", end: "11:00" },
    { subject: "Science", day: 2, start: "11:00", end: "11:40", teacher: "Isaac Boateng" },
    { subject: "RME", day: 3, start: "08:00", end: "08:40" },
    { subject: "Mathematics", day: 3, start: "08:40", end: "09:20", teacher: "Isaac Boateng" },
    { subject: "English Language", day: 3, start: "09:20", end: "10:00", teacher: "Abena Osei" },
    { subject: "ICT", day: 3, start: "10:20", end: "11:00" },
    { subject: "Science", day: 3, start: "11:00", end: "11:40", teacher: "Isaac Boateng" },
    { subject: "English Language", day: 4, start: "08:00", end: "08:40", teacher: "Abena Osei" },
    { subject: "Mathematics", day: 4, start: "08:40", end: "09:20", teacher: "Isaac Boateng" },
    { subject: "OWOP", day: 4, start: "09:20", end: "10:00", teacher: "Abena Osei" },
    { subject: "French", day: 4, start: "10:20", end: "11:00" },
    { subject: "Creative Arts", day: 4, start: "11:00", end: "11:40" },
    { subject: "Science", day: 5, start: "08:00", end: "08:40", teacher: "Isaac Boateng" },
    { subject: "Mathematics", day: 5, start: "08:40", end: "09:20", teacher: "Isaac Boateng" },
    { subject: "English Language", day: 5, start: "09:20", end: "10:00", teacher: "Abena Osei" },
    { subject: "Library Period", day: 5, start: "10:20", end: "11:00" },
  ],
  "JHS 3 (B9)": [
    { subject: "Mathematics", day: 1, start: "08:00", end: "09:00", teacher: "Kwame Duah" },
    { subject: "English Language", day: 1, start: "09:00", end: "10:00" },
    { subject: "Integrated Science", day: 1, start: "10:20", end: "11:20" },
    { subject: "Social Studies", day: 1, start: "11:20", end: "12:20" },
    { subject: "Career Technology", day: 2, start: "08:00", end: "09:00", teacher: "Kwame Duah" },
    { subject: "Mathematics", day: 2, start: "09:00", end: "10:00", teacher: "Kwame Duah" },
    { subject: "RME", day: 2, start: "10:20", end: "11:20" },
    { subject: "English Language", day: 2, start: "11:20", end: "12:20" },
    { subject: "Integrated Science", day: 3, start: "08:00", end: "09:00" },
    { subject: "Social Studies", day: 3, start: "09:00", end: "10:00" },
    { subject: "Mathematics", day: 3, start: "10:20", end: "11:20", teacher: "Kwame Duah" },
    { subject: "French", day: 3, start: "11:20", end: "12:20" },
    { subject: "English Language", day: 4, start: "08:00", end: "09:00" },
    { subject: "Career Technology", day: 4, start: "09:00", end: "10:00", teacher: "Kwame Duah" },
    { subject: "Mathematics", day: 4, start: "10:20", end: "11:20", teacher: "Kwame Duah" },
    { subject: "ICT", day: 4, start: "11:20", end: "12:20" },
    { subject: "Integrated Science", day: 5, start: "08:00", end: "09:00" },
    { subject: "Mathematics", day: 5, start: "09:00", end: "10:00", teacher: "Kwame Duah" },
    { subject: "Social Studies", day: 5, start: "10:20", end: "11:20" },
  ],
};

const toMin = (t: string) => { const [h, m] = t.split(":").map(Number); return h * 60 + m; };

async function main() {
  // ── 1. staff categories & statutory backfill ──
  const teaching = await db.staff.findMany({ where: { role: { in: ["HEADTEACHER", "TEACHER"] } } });
  for (const s of teaching) {
    await db.staff.update({
      where: { id: s.id },
      data: {
        category: s.role === "HEADTEACHER" ? "ADMINISTRATION" : "TEACHING",
        position: s.role === "HEADTEACHER" ? "Headteacher" : "Teacher",
        qualification: s.role === "HEADTEACHER" ? "M.Ed Educational Leadership" : "B.Ed Basic Education",
        staffCode: (s.role === "HEADTEACHER" ? "ADM-" : "TCR-") + s.id.slice(-5).toUpperCase(),
        ssnitNumber: ssnit(),
        tin: tin(),
        tier2Provider: "Petra Trust",
        accountNumber: acc(),
        bankName: "GCB Bank",
        salaryCents: s.salaryCents || (s.role === "HEADTEACHER" ? 4200_00 : 2600_00),
      },
    });
  }
  await db.staff.updateMany({ where: { role: { in: ["BURSAR", "ACCOUNTANT", "ASSISTANT"] } }, data: { category: "ADMINISTRATION", position: "Bursar", qualification: "HND Accounting", tier2Provider: "Enterprise Trustees", bankName: "Ecobank" } });
  console.log(`✅ categorised ${teaching.length} existing staff`);

  // non-teaching staff for the first school (skip if already present)
  const anySchool = await db.school.findFirst({ orderBy: { createdAt: "asc" } });
  const existing = await db.staff.count({ where: { category: "NON_TEACHING" } });
  if (existing === 0 && anySchool) {
    for (const [i, p] of POSITIONS_NON_TEACHING.entries()) {
      await db.staff.create({
        data: {
          schoolId: anySchool.id, fullName: p.name, role: "ASSISTANT",
          category: "NON_TEACHING", position: p.position, department: p.dept,
          qualification: "WASSCE", staffCode: `NTS-${String(i + 1).padStart(5, "0")}`,
          employmentType: i === 5 ? "CASUAL" : "FULL_TIME",
          salaryCents: p.salary, ssnitNumber: ssnit(), tin: tin(),
          accountNumber: acc(), bankName: "ADB",
          momoNumber: `024${String(1000000 + Math.floor(Math.random() * 8999999))}`,
        },
      });
    }
    console.log(`✅ added ${POSITIONS_NON_TEACHING.length} non-teaching staff`);
  }

  // ── 2. timetables ──
  const staffByName = await db.staff.findMany();
  const byName = (n?: string) => (n ? staffByName.find((s) => s.fullName.includes(n))?.id ?? null : null);
  for (const [className, slots] of Object.entries(TIMETABLES)) {
    const room = await db.classroom.findFirst({ where: { name: { contains: className.split(" ")[1] === "3" ? "B9" : className.split(" ")[2] ?? className } } });
    if (!room) { console.log(`⚠️  classroom not found for ${className}`); continue; }
    const already = await db.timetableSlot.count({ where: { classroomId: room.id } });
    if (already > 0) { console.log(`↷ ${className} already has ${already} periods`); continue; }
    for (const s of slots) {
      await db.timetableSlot.create({
        data: {
          schoolId: room.schoolId, classroomId: room.id,
          subjectName: s.subject, subjectId: null,
          staffId: byName(s.teacher),
          dayOfWeek: s.day, startMinute: toMin(s.start), endMinute: toMin(s.end),
        },
      });
    }
    console.log(`✅ ${room.name}: ${slots.length} periods`);
  }
}

const ssnit = () => `C0${Math.floor(Math.random() * 9)}${Math.floor(100000000 + Math.random() * 8999999)}`;
const tin = () => `C00${Math.floor(1000000 + Math.random() * 8999999)}`;
const acc = () => `${Math.floor(10000000 + Math.random() * 89999999)}${Math.floor(10 + Math.random() * 89)}`;

main().then(() => db.$disconnect()).catch((e) => { console.error(e); db.$disconnect(); process.exit(1); });

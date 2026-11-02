import { PrismaClient } from "@prisma/client";
const db = new PrismaClient();
(async () => {
  const schools = await db.school.findMany({ select: { id: true, code: true, name: true } });
  console.log("SCHOOLS:", JSON.stringify(schools));
  for (const s of schools.slice(0, 2)) {
    const cls = await db.classroom.findMany({ where: { schoolId: s.id }, select: { id: true, name: true, gradeLevel: true }, orderBy: { name: "asc" }, take: 30 });
    console.log(`CLASSES ${s.code}:`, JSON.stringify(cls));
    const subs = await db.subject.findMany({ where: { schoolId: s.id }, select: { id: true, name: true }, orderBy: { name: "asc" } });
    console.log(`SUBJECTS ${s.code}:`, JSON.stringify(subs));
    const term = await db.term.findFirst({ where: { academicYear: { schoolId: s.id }, isCurrent: true }, select: { id: true, name: true, startsAt: true, endsAt: true, academicYear: { select: { name: true } } } });
    console.log(`TERM ${s.code}:`, JSON.stringify(term));
    const staff = await db.staff.findMany({ where: { schoolId: s.id, category: "TEACHING" }, select: { id: true, fullName: true, staffCode: true, subjects: true }, take: 12 });
    console.log(`STAFF ${s.code}:`, JSON.stringify(staff));
  }
  const teacherUser = await db.user.findFirst({ where: { email: "teacher@nyansapo.gh" }, select: { id: true, email: true, staffId: true, fullName: true } });
  console.log("TEACHER USER:", JSON.stringify(teacherUser));
  await db.$disconnect();
})();

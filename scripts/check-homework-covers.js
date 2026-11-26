/* eslint-disable @typescript-eslint/no-require-imports */
const { PrismaClient } = require("@prisma/client");
const db = new PrismaClient();
(async () => {
  const hw = await db.homework.findMany({ orderBy: { createdAt: "desc" }, take: 3, include: { _count: { select: { submissions: true } } } });
  console.log("HOMEWORK:", JSON.stringify(hw.map((h) => ({ title: h.title, due: h.dueDate.toISOString().slice(0, 10), subs: h._count.submissions }))));
  const subs = await db.homeworkSubmission.findMany({ where: { status: { in: ["SUBMITTED", "GRADED", "LATE"] } }, orderBy: { createdAt: "desc" }, take: 3 });
  console.log("MARKED:", JSON.stringify(subs.map((s) => ({ status: s.status, score: s.score, gradedBy: s.gradedByName }))));
  const marks = await db.mark.findMany({ orderBy: { score: "desc" }, take: 3, include: { assessment: true } });
  console.log("GRADEBOOK MARKS:", JSON.stringify(marks.map((m) => ({ score: m.score, type: m.assessment?.type, title: m.assessment?.title?.slice(0, 44) }))));
  const sms = await db.smsMessage.findMany({ orderBy: { sentAt: "desc" }, take: 2, select: { category: true, status: true, recipientName: true } });
  console.log("SMS:", JSON.stringify(sms));
  const covers = await db.substitutionLog.findMany({ where: { slotId: { not: null } }, take: 5, include: { absentStaff: { select: { fullName: true } }, substituteStaff: { select: { fullName: true } }, slot: { select: { subjectName: true } } } });
  console.log("COVERS:", JSON.stringify(covers.map((c) => ({ absent: c.absentStaff.fullName, sub: c.substituteStaff.fullName, slot: c.slot?.subjectName, when: c.periods, date: c.date.toISOString().slice(0, 10) }))));
  process.exit(0);
})().catch((e) => { console.error(e.message); process.exit(1); });

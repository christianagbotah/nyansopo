import { PrismaClient } from "@prisma/client";
import { renderTemplate, sendAutomated } from "@/lib/sms";
import { sendEmail, resultsEmailHtml } from "@/lib/email";

/* ══════════════════════════════════════════════════════════════
   Wave 22 — results release notifications.

   CI3 exam_marks_sms parity modernised for the wave-20/21 results
   lifecycle: when end-of-term results go live, guardians get the
   learner's terminal summary (average, class position, best-6
   aggregate, attendance) instead of a raw per-subject score dump.

   Two paths share one composer:
   • Manual — "Notify parents" in Results & Report Cards (preview
     first, then direct sends through the school's SMS gateway +
     email channel; an active RESULTS_PUBLISHED automation rule
     overrides the default body).
   • Automatic — publish with notify:true fires the same composer
     through sendAutomated, gated on SmsSetting.evtResultsPublished
     (the school's per-event toggle; safe default is off → silent).

   Placeholders available to rule bodies:
   {school} {learner} {class} {term} {year} {average} {position}
   {size} {aggregate} {attendance} {promotion}
   ══════════════════════════════════════════════════════════════ */

export const RESULTS_NOTIFY_DEFAULT_BODY =
  "{school}: {learner}'s {term} results are now live — average {average}%, position {position} of {size}, best-6 aggregate {aggregate}, attendance {attendance}%. {promotion}. View the full report in your parent portal.";

/** Every message (default or rule body) is rendered with these vars. */
export function resultsNotifyVars(row: {
  schoolName: string;
  studentName: string;
  classroomName: string;
  termName: string;
  yearName: string;
  average: number | null;
  classPosition: number | null;
  classSize: number | null;
  aggregate: number | null;
  attendancePct: number | null;
  promoted: boolean | null;
}): Record<string, string> {
  const fmt1 = (n: number | null) => (n === null ? "—" : (Math.round(n * 10) / 10).toFixed(1).replace(/\.0$/, ""));
  return {
    school: row.schoolName,
    learner: row.studentName,
    class: row.classroomName,
    term: row.termName,
    year: row.yearName,
    average: fmt1(row.average),
    position: row.classPosition === null ? "—" : String(row.classPosition),
    size: row.classSize === null ? "—" : String(row.classSize),
    aggregate: row.aggregate === null ? "—" : String(Math.round(row.aggregate)),
    // raw number — templates append the % sign themselves
    attendance: row.attendancePct === null ? "—" : String(row.attendancePct),
    promotion: row.promoted === null ? "" : row.promoted ? "Promoted" : "Promotion at risk",
  };
}

export type ResultsNotifyRow = {
  studentId: string;
  studentName: string;
  indexNo: string;
  guardianName: string;
  guardianPhone: string;
  guardianEmail: string | null;
  classroomName: string;
  termName: string;
  yearName: string;
  average: number | null;
  classPosition: number | null;
  classSize: number | null;
  aggregate: number | null;
  attendancePct: number | null;
  promoted: boolean | null;
  message: string;
  emailSubject: string;
};

export type BuiltResultsNotify = {
  rows: ResultsNotifyRow[];
  draftCount: number; // learners with a card still in draft (skipped)
  rule: { id: string; name: string; body: string } | null;
};

/** Load the published cards for one class-term and compose one message
 *  per learner (rule body when an automation is active, default body
 *  otherwise). Draft cards are counted and skipped — guardians are only
 *  ever told about results they can actually open in the portal. */
export async function buildResultsNotify(
  db: PrismaClient,
  schoolId: string,
  termId: string,
  classId: string,
): Promise<BuiltResultsNotify> {
  const [school, term, classroom, enrolled, ruleRow] = await Promise.all([
    db.school.findUnique({ where: { id: schoolId }, select: { id: true, name: true } }),
    db.term.findFirst({ where: { id: termId, academicYear: { schoolId } }, include: { academicYear: true } }),
    db.classroom.findFirst({ where: { id: classId, schoolId }, select: { id: true, name: true } }),
    db.enrollment.findMany({
      where: { classroomId: classId, status: "ACTIVE" },
      select: { studentId: true },
    }),
    db.smsAutomation.findFirst({ where: { schoolId, trigger: "RESULTS_PUBLISHED", active: true }, orderBy: { createdAt: "asc" } }),
  ]);
  if (!school) throw new Error("School not found");
  if (!term) throw new Error("Term not found in this school");
  if (!classroom) throw new Error("Class not found in this school");
  const studentIds = enrolled.map((e) => e.studentId);
  if (!studentIds.length) throw new Error("No learners are actively enrolled in this class");

  const cards = await db.reportCard.findMany({
    where: { termId, studentId: { in: studentIds } },
    include: { student: { select: { firstName: true, lastName: true, otherNames: true, nyansapoIndex: true, guardianName: true, guardianPhone: true, guardianEmail: true } } },
    orderBy: { classPosition: "asc" },
  });
  const published = cards.filter((c) => c.published);
  const draftCount = cards.length - published.length;

  const body = ruleRow ? ruleRow.body : RESULTS_NOTIFY_DEFAULT_BODY;
  const rows: ResultsNotifyRow[] = published.map((c) => {
    const studentName = [c.student.lastName, c.student.firstName, c.student.otherNames].filter(Boolean).join(" ");
    const vars = resultsNotifyVars({
      schoolName: school.name,
      studentName,
      classroomName: classroom.name,
      termName: term.name,
      yearName: term.academicYear.name,
      average: c.average,
      classPosition: c.classPosition,
      classSize: c.classSize,
      aggregate: c.aggregate,
      attendancePct: c.attendancePct,
      promoted: c.promoted,
    });
    return {
      studentId: c.studentId,
      studentName,
      indexNo: c.student.nyansapoIndex,
      guardianName: c.student.guardianName,
      guardianPhone: c.student.guardianPhone,
      guardianEmail: c.student.guardianEmail,
      classroomName: classroom.name,
      termName: term.name,
      yearName: term.academicYear.name,
      average: c.average,
      classPosition: c.classPosition,
      classSize: c.classSize,
      aggregate: c.aggregate,
      attendancePct: c.attendancePct,
      promoted: c.promoted,
      message: renderTemplate(body, vars),
      emailSubject: `${term.name} ${term.academicYear.name} results — ${studentName} (${classroom.name})`,
    };
  });
  return { rows, draftCount, rule: ruleRow ? { id: ruleRow.id, name: ruleRow.name, body: ruleRow.body } : null };
}

export type NotifyChannel = "SMS" | "EMAIL" | "BOTH";

/** Channel legs each notify run performs. Returns per-leg tallies so the
 *  UI can show an honest breakdown (sent / failed / skipped reasons). */
export async function sendResultsNotify(opts: {
  db: PrismaClient;
  schoolId: string;
  rows: ResultsNotifyRow[];
  channel: NotifyChannel;
  sentByName: string;
  rule: { id: string; name: string; body: string } | null;
}) {
  const { db, schoolId, rows, channel, sentByName, rule } = opts;
  const sms: { sent: number; failed: number; noPhone: number } = { sent: 0, failed: 0, noPhone: 0 };
  const email: { sent: number; failed: number; simulated: number; noAddress: number } = { sent: 0, failed: 0, simulated: 0, noAddress: 0 };
  const schoolInfo = await db.school.findUnique({ where: { id: schoolId }, select: { name: true, town: true, motto: true } });

  for (const r of rows) {
    // SMS leg — through sendAutomated so the RESULTS_PUBLISHED rule
    // (if any) shapes the body and its counters advance. sendSms's own
    // gateway/credits doctrine decides the outcome row.
    if (channel === "SMS" || channel === "BOTH") {
      if (!r.guardianPhone.trim()) { sms.noPhone++; continue; }
      const vars = resultsNotifyVars({ ...r, schoolName: schoolInfo?.name ?? "" });
      const msg = await sendAutomated({
        db, schoolId, trigger: "RESULTS_PUBLISHED",
        defaultBody: RESULTS_NOTIFY_DEFAULT_BODY, vars,
        studentId: r.studentId,
        recipientName: r.guardianName, recipientPhone: r.guardianPhone,
        sentByName: sentByName || undefined,
        rule,
      });
      if (msg.status === "FAILED") sms.failed++; else sms.sent++;
    }
    // email leg — the school's SMTP channel; no per-event gate (the
    // channel itself is opt-in via Settings → Email), outcomes logged
    if (channel === "EMAIL" || channel === "BOTH") {
      const address = r.guardianEmail?.trim();
      if (!address) { email.noAddress++; continue; }
      const msg = await sendEmail({
        db, schoolId, studentId: r.studentId,
        to: address, toName: r.guardianName,
        subject: r.emailSubject,
        html: resultsEmailHtml({
          school: { name: schoolInfo?.name ?? "the school", town: schoolInfo?.town ?? null, motto: schoolInfo?.motto ?? null },
          term: `${r.termName} ${r.yearName}`,
          row: r,
        }),
        category: "RESULTS", sentByName,
      });
      if (msg.status === "SENT") email.sent++;
      else if (msg.status === "SIMULATED") email.simulated++;
      else email.failed++;
    }
  }
  return { sms, email };
}

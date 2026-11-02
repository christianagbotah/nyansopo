import { PrismaClient } from "@prisma/client";

// ── SMS sending helper (gateway-agnostic) ───────────────────
// In SIMULATED mode (demo / offline-first demo deployments) messages are
// recorded with realistic statuses and credits are debited. To go live,
// set provider to ARKESEL or TERMII and wire the provider call below.

export type SmsSendArgs = {
  db: PrismaClient;
  schoolId: string;
  studentId?: string | null;
  recipientName: string;
  recipientPhone: string;
  body: string;
  category: string;
  sentByName?: string;
};

const GHANA_PHONE_OK = (p: string) => /^\+?233\d{9}$|^0\d{9}$/.test(p.replace(/[\s-]/g, ""));

export async function sendSms({ db, schoolId, studentId, recipientName, recipientPhone, body, category, sentByName }: SmsSendArgs) {
  const setting = await db.smsSetting.findUnique({ where: { schoolId } });
  if (!setting || setting.credits <= 0) {
    return await db.smsMessage.create({
      data: { schoolId, studentId: studentId ?? null, recipientName, recipientPhone, body, category, status: "FAILED", creditsUsed: 0, sentByName: sentByName ?? null, errorReason: "No SMS credits — top up in Settings." },
    });
  }
  // provider dispatch (SIMULATED here; ARKESEL/TERMII hook-in point)
  const ok = setting.provider === "SIMULATED" ? GHANA_PHONE_OK(recipientPhone) : true;
  await db.smsSetting.update({ where: { schoolId }, data: { credits: { decrement: 1 } } });
  return await db.smsMessage.create({
    data: { schoolId, studentId: studentId ?? null, recipientName, recipientPhone, body, category, status: ok ? "SENT" : "FAILED", creditsUsed: ok ? 1 : 0, sentByName: sentByName ?? null, errorReason: ok ? null : "Invalid Ghana number format" },
  });
}

/** Substitute {placeholders} with per-recipient values. */
export function renderTemplate(body: string, vars: Record<string, string>) {
  return body.replace(/\{(\w+)\}/g, (_, k) => vars[k] ?? `{${k}}`);
}

/* ── Automation rules (iedupro parity) ───────────────────────
   An active SmsAutomation for a trigger overrides the built-in default
   message body; counts and last-run are tracked on the rule itself.
   LESSON_NOTE_* triggers are teacher-directed (recipient = the note's
   staff record, not a guardian) — gated by SmsSetting.autoLessonNoteAlerts
   + Staff.smsNotify (iedupro user_notification_preferences parity). */
export type AutomationTrigger = "PAYMENT_RECEIVED" | "INVOICE_ISSUED" | "ABSENCE_MARKED" | "RESULTS_PUBLISHED" | "BILL_REMINDER" | "LESSON_NOTE_RETURNED" | "LESSON_NOTE_ENDORSED";

export async function sendAutomated(opts: {
  db: PrismaClient; schoolId: string; trigger: AutomationTrigger;
  defaultBody: string; vars: Record<string, string>;
  studentId?: string | null; recipientName: string; recipientPhone: string; sentByName?: string;
  rule?: { id: string; name: string; body: string } | null;
}) {
  const rule = opts.rule !== undefined
    ? opts.rule
    : await opts.db.smsAutomation.findFirst({ where: { schoolId: opts.schoolId, trigger: opts.trigger, active: true }, orderBy: { createdAt: "asc" } });
  // both rule bodies and default bodies support {placeholders} — vars that are
  // missing are left verbatim so the sender can spot them
  const body = renderTemplate(rule ? rule.body : opts.defaultBody, opts.vars);
  const msg = await sendSms({
    db: opts.db, schoolId: opts.schoolId, studentId: opts.studentId ?? null,
    recipientName: opts.recipientName, recipientPhone: opts.recipientPhone,
    body, category: opts.trigger === "BILL_REMINDER" ? "FEES" : opts.trigger === "PAYMENT_RECEIVED" ? "PAYMENT" : opts.trigger === "ABSENCE_MARKED" ? "ATTENDANCE" : opts.trigger.startsWith("LESSON_NOTE_") ? "STAFF" : "ANNOUNCEMENT",
    sentByName: opts.sentByName ?? (rule ? `automation: ${rule.name}` : undefined),
  });
  if (rule && msg.status !== "FAILED") {
    await opts.db.smsAutomation.update({ where: { id: rule.id }, data: { sentCount: { increment: 1 }, lastRunAt: new Date() } });
  }
  return msg;
}

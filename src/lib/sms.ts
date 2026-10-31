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

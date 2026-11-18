import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { buildResultsNotify, sendResultsNotify, type NotifyChannel } from "@/lib/results-notify";

/* ─────────────────────────────────────────────────────────────
   POST /api/results/notify — wave 22 results release notifications
   (CI3 exam_marks_sms parity, on the wave-20/21 results lifecycle).

   Body: { schoolId, termId, classId, channel: "SMS"|"EMAIL"|"BOTH",
           preview?: boolean, learnerIds?: string[] }

   Perms: reports.publish OR sms.send (the head teacher releasing
   results, or the bursar/SMS desk, may both run the blast).

   • preview=true — draft every message from the PUBLISHED report
     cards (draft cards are counted and skipped) plus the school's
     channel state (credits, provider, SMTP); nothing is sent.
   • preview=false — send: SMS leg through sendAutomated (the
     RESULTS_PUBLISHED automation rule overrides the default body
     when one is active), email leg through the school SMTP channel
     with the results-release email. Every outcome is logged
     (SmsMessage / EmailMessage rows).
   ───────────────────────────────────────────────────────────── */

export async function POST(req: NextRequest) {
  const body = await req.json().catch(() => ({}));
  const S = (v: unknown) => (typeof v === "string" ? v.trim() : "");
  const schoolId = S(body.schoolId);
  const termId = S(body.termId);
  const classId = S(body.classId);
  const channelRaw = S(body.channel).toUpperCase();
  const channel: NotifyChannel = channelRaw === "EMAIL" ? "EMAIL" : channelRaw === "BOTH" ? "BOTH" : "SMS";
  const preview = body.preview === true;
  const learnerIds = Array.isArray(body.learnerIds) ? body.learnerIds.map((x: unknown) => String(x)) : null;
  if (!schoolId || !termId || !classId) {
    return NextResponse.json({ error: "schoolId, termId and classId are required" }, { status: 400 });
  }

  const auth = await opsAuth(req, schoolId, "POST", { perms: ["reports.publish", "sms.send"] });
  if (!auth.ok) return auth.res;
  const user = auth.user;

  let built;
  try {
    built = await buildResultsNotify(db, schoolId, termId, classId);
  } catch (e: any) {
    return NextResponse.json({ error: e.message || "Could not load the class-term results" }, { status: 400 });
  }
  const rows = learnerIds ? built.rows.filter((r) => learnerIds.includes(r.studentId)) : built.rows;
  if (!rows.length) {
    return NextResponse.json(
      { error: built.draftCount ? `All ${built.draftCount} report card${built.draftCount === 1 ? " is" : "s are"} still in draft — publish the results before notifying guardians.` : "No published report cards for this class-term." },
      { status: 400 },
    );
  }

  const wantsSms = channel === "SMS" || channel === "BOTH";
  const withPhone = rows.filter((r) => r.guardianPhone.trim()).length;
  const withEmail = rows.filter((r) => (r.guardianEmail ?? "").trim()).length;

  /* preview — channel state + drafted messages, nothing sent */
  if (preview) {
    const [smsSetting, emailSetting] = await Promise.all([
      db.smsSetting.findUnique({ where: { schoolId }, select: { provider: true, credits: true, senderId: true } }),
      db.emailSetting.findUnique({ where: { schoolId }, select: { enabled: true, host: true, fromName: true } }),
    ]);
    return NextResponse.json({
      preview: true,
      rows,
      draftCount: built.draftCount,
      rule: built.rule,
      withPhone,
      withoutPhone: rows.length - withPhone,
      withEmail,
      withoutEmail: rows.length - withEmail,
      channel,
      sms: { provider: smsSetting?.provider ?? "SIMULATED", credits: smsSetting?.credits ?? 0, senderId: smsSetting?.senderId ?? null },
      email: { enabled: !!(emailSetting?.enabled && emailSetting?.host), host: emailSetting?.host ?? null, fromName: emailSetting?.fromName ?? null },
    });
  }

  /* send — credits are pre-checked for the SMS leg (fail fast, CI3
     marks-sms doctrine); the email leg logs SIMULATED when SMTP is
     off and never blocks the workflow. */
  if (wantsSms) {
    if (!withPhone) {
      return NextResponse.json({ error: "None of these guardians have a phone number on file — nothing to send by SMS." }, { status: 400 });
    }
    const setting = await db.smsSetting.findUnique({ where: { schoolId }, select: { credits: true } });
    if (setting && setting.credits < withPhone) {
      return NextResponse.json({ error: `Not enough SMS credits (${setting.credits} left, ${withPhone} needed) — top up in Settings → Notifications.` }, { status: 400 });
    }
  }

  const outcome = await sendResultsNotify({
    db, schoolId, rows, channel, sentByName: user.fullName, rule: built.rule,
  });

  return NextResponse.json({
    ok: true, channel, learners: rows.length,
    sms: outcome.sms, email: outcome.email,
    draftCount: built.draftCount,
    rule: built.rule ? { name: built.rule.name } : null,
  });
}

import { PrismaClient, type EmailMessage, type EmailSetting } from "@prisma/client";
import nodemailer, { type Transporter } from "nodemailer";

// ── Email channel (CI3 Invoice_email.php + Email_model.php parity) ──
// Per-school SMTP settings (EmailSetting, replaces CI3's get_settings
// smtp_host/smtp_port/smtp_user/smtp_pass) + an append-only EmailMessage log
// (SmsMessage pattern). When SMTP is not configured / not enabled the send is
// recorded as SIMULATED — the same offline-first philosophy as the SMS
// gateway's no-key behaviour: log the intent, never block the workflow.
// The stored password never leaves this module (never logged, never returned).

const EMAIL_OK = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/** lazily-created pooled transporters, one per school — swapped when settings change */
const transporters = new Map<string, { sig: string; transport: Transporter }>();

/** Pooled SMTP transporter for a school's saved settings — null unless the
 *  channel is enabled AND a host is configured (lazy: nothing connects until
 *  the first send). A settings change swaps the cached transporter and closes
 *  the old pool. */
export function getEmailTransport(setting: EmailSetting | null | undefined): Transporter | null {
  if (!setting?.enabled || !setting.host) return null;
  // in-memory signature only — lets us detect settings changes without
  // persisting or logging credentials anywhere else
  const sig = JSON.stringify([setting.schoolId, setting.host, setting.port, setting.secure, setting.username, setting.password]);
  const cached = transporters.get(setting.schoolId);
  if (cached && cached.sig === sig) return cached.transport;
  if (cached) cached.transport.close();
  const transport = nodemailer.createTransport({
    host: setting.host,
    port: setting.port,
    secure: setting.secure,
    pool: true,
    maxConnections: 3,
    ...(setting.username ? { auth: { user: setting.username, pass: setting.password ?? "" } } : {}),
  });
  transporters.set(setting.schoolId, { sig, transport });
  return transport;
}

export type EmailSendArgs = {
  db: PrismaClient;
  schoolId: string;
  to: string;
  toName?: string | null;
  subject: string;
  html: string;
  category: string;
  sentByName?: string;
  studentId?: string | null;
};

/** Send one email, outcome always logged (CI3 Email_model::do_email parity,
 *  with the SMS channel's outcome-row pattern). Throws never. */
export async function sendEmail({ db, schoolId, to, toName, subject, html, category, sentByName, studentId }: EmailSendArgs): Promise<EmailMessage> {
  const address = String(to ?? "").trim().toLowerCase();
  const base = {
    schoolId,
    toAddress: address,
    toName: toName ?? null,
    subject,
    body: html,
    category,
    sentByName: sentByName ?? null,
    studentId: studentId ?? null,
  };
  if (!EMAIL_OK.test(address)) {
    return db.emailMessage.create({
      data: { ...base, toAddress: address || "(no address)", status: "FAILED", errorReason: "Invalid email address" },
    });
  }
  const setting = await db.emailSetting.findUnique({ where: { schoolId } });
  const transport = getEmailTransport(setting);
  if (!transport) {
    // SIMULATED — no SMTP configured/enabled: the intent is logged, the
    // calling workflow (invoice issuing, bulk send…) never fails on it
    return db.emailMessage.create({ data: { ...base, status: "SIMULATED" } });
  }
  const fromName = setting?.fromName?.trim() || setting?.username || "Nyansapo";
  const fromAddress = setting?.fromAddress?.trim().toLowerCase() || setting?.username || "noreply@nyansapo.app";
  try {
    await transport.sendMail({
      from: { name: fromName, address: fromAddress },
      to: toName ? { name: toName, address } : address,
      subject,
      html,
    });
    return db.emailMessage.create({ data: { ...base, status: "SENT" } });
  } catch (e: any) {
    return db.emailMessage.create({ data: { ...base, status: "FAILED", errorReason: e?.message || "SMTP send failed" } });
  }
}

/* ── HTML rendering (CI3 email_templates/invoice_email view parity) ── */

/** minimal HTML-escape for every interpolated string */
const esc = (s: unknown) =>
  String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c] as string);

/** ₵ money formatting — en-GH grouping, 2 decimals */
const cedi = (cents: number) => `₵${(cents / 100).toLocaleString("en-GH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const day = (d?: Date | null) =>
  d ? new Date(d).toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric" }) : "—";

export type InvoiceEmailInput = {
  school: { name: string; town: string; phone?: string | null } | null;
  invoice: {
    invoiceNo: string;
    createdAt?: Date | null;
    dueDate?: Date | null;
    totalCents: number;
    discountCents: number;
    paidCents: number;
  };
  student: { firstName: string; lastName: string; classroom?: string | null };
  term: { name: string } | null;
  items: { name: string; amountCents: number }[];
};

/** Clean inline-styled invoice email — Ghana-appropriate, no external images,
 *  everything escaped. Letterhead, line items, totals with a bold OUTSTANDING,
 *  a MoMo payment hint and the Powered-by-Nyansapo footer. */
export function invoiceEmailHtml({ school, invoice, student, term, items }: InvoiceEmailInput): string {
  const schoolName = school?.name ?? "the school";
  const outstanding = invoice.totalCents - invoice.discountCents - invoice.paidCents;
  const momoHint = school?.phone
    ? `Pay via MTN MoMo to ${esc(school.phone)} (${esc(schoolName)}) — quote invoice ${esc(invoice.invoiceNo)} as the reference.`
    : "Kindly settle at the school office or via mobile money, quoting the invoice number above.";
  const rows = items.length
    ? items
        .map(
          (it) => `<tr>
<td style="padding:8px 12px;border-bottom:1px solid #edf1ee;color:#2c382f;">${esc(it.name)}</td>
<td align="right" style="padding:8px 12px;border-bottom:1px solid #edf1ee;color:#2c382f;white-space:nowrap;">${esc(cedi(it.amountCents))}</td>
</tr>`,
        )
        .join("")
    : `<tr><td colspan="2" style="padding:8px 12px;color:#8a948c;">No billing items recorded on this invoice.</td></tr>`;

  return `<!doctype html>
<html><body style="margin:0;padding:0;background-color:#f4f6f5;">
<div style="font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;background-color:#f4f6f5;padding:20px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e3e8e5;">
<tr><td style="background-color:#0e5f3f;padding:20px 24px;color:#ffffff;">
<div style="font-size:19px;font-weight:700;letter-spacing:0.2px;">${esc(schoolName)}</div>
<div style="font-size:12px;opacity:0.85;margin-top:2px;">${esc(school?.town ?? "")}${school?.town ? ", Ghana" : "Ghana"}</div>
</td></tr>
<tr><td style="padding:22px 24px 8px 24px;">
<div style="font-size:15px;font-weight:600;color:#1a2b22;margin-bottom:12px;">Fee invoice — ${esc(term?.name ?? "current term")}</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:13px;color:#3d4a42;">
<tr><td style="padding:3px 0;width:110px;color:#7a857d;">Invoice</td><td style="padding:3px 0;font-weight:600;color:#1a2b22;">${esc(invoice.invoiceNo)}</td></tr>
<tr><td style="padding:3px 0;color:#7a857d;">Issued</td><td style="padding:3px 0;">${esc(day(invoice.createdAt))}</td></tr>
<tr><td style="padding:3px 0;color:#7a857d;">Payment due</td><td style="padding:3px 0;">${esc(day(invoice.dueDate))}</td></tr>
<tr><td style="padding:3px 0;color:#7a857d;">Learner</td><td style="padding:3px 0;font-weight:600;color:#1a2b22;">${esc(`${student.firstName} ${student.lastName}`)}</td></tr>
${student.classroom ? `<tr><td style="padding:3px 0;color:#7a857d;">Class</td><td style="padding:3px 0;">${esc(student.classroom)}</td></tr>` : ""}
</table>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:16px;font-size:13px;border-collapse:collapse;">
<thead>
<tr style="background-color:#eef3ef;">
<th align="left" style="padding:8px 12px;color:#0e5f3f;font-size:11px;text-transform:uppercase;letter-spacing:0.4px;">Item</th>
<th align="right" style="padding:8px 12px;color:#0e5f3f;font-size:11px;text-transform:uppercase;letter-spacing:0.4px;">Amount</th>
</tr>
</thead>
<tbody>${rows}</tbody>
</table>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:14px;font-size:13px;color:#3d4a42;">
<tr><td style="padding:3px 0;color:#7a857d;">Gross total</td><td align="right" style="padding:3px 0;white-space:nowrap;">${esc(cedi(invoice.totalCents))}</td></tr>
${invoice.discountCents > 0 ? `<tr><td style="padding:3px 0;color:#7a857d;">Discount</td><td align="right" style="padding:3px 0;color:#0e5f3f;white-space:nowrap;">−${esc(cedi(invoice.discountCents))}</td></tr>` : ""}
<tr><td style="padding:3px 0;color:#7a857d;">Paid to date</td><td align="right" style="padding:3px 0;color:#0e5f3f;white-space:nowrap;">${esc(cedi(invoice.paidCents))}</td></tr>
<tr>
<td style="padding:9px 0 2px 0;font-weight:700;color:#0e5f3f;font-size:15px;border-top:2px solid #0e5f3f;">Outstanding</td>
<td align="right" style="padding:9px 0 2px 0;font-weight:700;color:#0e5f3f;font-size:15px;border-top:2px solid #0e5f3f;white-space:nowrap;">${esc(cedi(outstanding))}</td>
</tr>
</table>
<p style="margin:16px 0 0 0;font-size:12.5px;line-height:1.55;color:#3d4a42;background:#f4faf6;border:1px solid #d7e8dc;border-radius:8px;padding:10px 12px;">${momoHint}</p>
<p style="margin:14px 0 0 0;font-size:12.5px;line-height:1.55;color:#3d4a42;">Dear guardian, kindly keep this invoice for your records. For questions about this bill please contact the school office.</p>
</td></tr>
<tr><td style="padding:14px 24px;background:#fafbfa;border-top:1px solid #edf1ee;font-size:11px;color:#8a948c;text-align:center;">
This invoice was generated by ${esc(schoolName)}. Powered by Nyansapo.
</td></tr>
</table>
</div>
</body></html>`;
}

/** Short body for the channel's own test send. */
export function testEmailHtml(schoolName: string): string {
  return `<!doctype html>
<html><body style="margin:0;padding:0;background-color:#f4f6f5;">
<div style="font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;background-color:#f4f6f5;padding:20px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e3e8e5;">
<tr><td style="background-color:#0e5f3f;padding:16px 24px;color:#ffffff;font-size:16px;font-weight:700;">Nyansapo email channel</td></tr>
<tr><td style="padding:20px 24px;font-size:13.5px;line-height:1.6;color:#3d4a42;">
This is a test message from ${esc(schoolName)}.<br /><br />
If you can read this in your inbox, SMTP delivery for the school's email channel is working.
</td></tr>
<tr><td style="padding:12px 24px;background:#fafbfa;border-top:1px solid #edf1ee;font-size:11px;color:#8a948c;text-align:center;">Powered by Nyansapo</td></tr>
</table>
</div>
</body></html>`;
}

/* ── Results release email (wave 22 — CI3 exam_marks_sms email leg) ── */

export type ResultsEmailInput = {
  school: { name: string; town?: string | null; motto?: string | null };
  term: string;
  row: {
    studentName: string;
    indexNo: string;
    classroomName: string;
    guardianName: string;
    average: number | null;
    classPosition: number | null;
    classSize: number | null;
    aggregate: number | null;
    attendancePct: number | null;
    promoted: boolean | null;
  };
};

/** Terminal-results release email — a clean one-screen summary card for
 *  the guardian: header strip, particulars, average / position / best-6
 *  aggregate / attendance stat grid, promotion line and a portal hint.
 *  Same visual language as the invoice email, everything escaped. */
export function resultsEmailHtml({ school, term, row }: ResultsEmailInput): string {
  const pct = (n: number | null) => (n === null ? "—" : `${(Math.round(n * 10) / 10).toFixed(1).replace(/\.0$/, "")}%`);
  const pos = row.classPosition === null ? "—" : `${row.classPosition}${row.classSize ? ` of ${row.classSize}` : ""}`;
  const tone = row.average === null ? "#3d4a42" : row.average >= 65 ? "#0e5f3f" : row.average >= 50 ? "#b45309" : "#b91c1c";
  return `<!doctype html>
<html><body style="margin:0;padding:0;background-color:#f4f6f5;">
<div style="font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;background-color:#f4f6f5;padding:20px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e3e8e5;">
<tr><td style="background-color:#0e5f3f;padding:20px 24px;color:#ffffff;">
<div style="font-size:19px;font-weight:700;letter-spacing:0.2px;">${esc(school.name)}</div>
${school.motto ? `<div style="font-size:12px;opacity:0.85;margin-top:2px;font-style:italic;">${esc(school.motto)}</div>` : ""}
<div style="font-size:12px;opacity:0.85;margin-top:4px;">${esc(school.town ?? "")}${school.town ? ", Ghana" : "Ghana"}</div>
</td></tr>
<tr><td style="padding:22px 24px 8px 24px;">
<div style="font-size:15px;font-weight:600;color:#1a2b22;margin-bottom:10px;">${esc(term)} terminal results are live</div>
<p style="margin:0 0 14px 0;font-size:13px;line-height:1.6;color:#3d4a42;">Dear ${esc(row.guardianName)}, ${esc(row.studentName)}'s end-of-term results have been released by the school. Here is the summary:</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:13px;color:#3d4a42;">
<tr>
<td style="padding:3px 0;width:130px;color:#7a857d;">Learner</td>
<td style="padding:3px 0;font-weight:600;color:#1a2b22;">${esc(row.studentName)}</td>
</tr>
<tr><td style="padding:3px 0;color:#7a857d;">Index</td><td style="padding:3px 0;">${esc(row.indexNo)}</td></tr>
<tr><td style="padding:3px 0;color:#7a857d;">Class</td><td style="padding:3px 0;">${esc(row.classroomName)}</td></tr>
</table>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:16px;border-collapse:separate;border-spacing:6px 0;">
<tr>
<td align="center" style="width:25%;background:#f4faf6;border:1px solid #d7e8dc;border-radius:8px;padding:10px 4px;">
<div style="font-size:18px;font-weight:700;color:${tone};">${pct(row.average)}</div>
<div style="font-size:10px;color:#7a857d;text-transform:uppercase;letter-spacing:0.4px;margin-top:2px;">Average</div>
</td>
<td align="center" style="width:25%;background:#f4faf6;border:1px solid #d7e8dc;border-radius:8px;padding:10px 4px;">
<div style="font-size:18px;font-weight:700;color:#1a2b22;">${esc(pos)}</div>
<div style="font-size:10px;color:#7a857d;text-transform:uppercase;letter-spacing:0.4px;margin-top:2px;">Position</div>
</td>
<td align="center" style="width:25%;background:#f4faf6;border:1px solid #d7e8dc;border-radius:8px;padding:10px 4px;">
<div style="font-size:18px;font-weight:700;color:#1a2b22;">${row.aggregate === null ? "—" : esc(String(Math.round(row.aggregate)))}</div>
<div style="font-size:10px;color:#7a857d;text-transform:uppercase;letter-spacing:0.4px;margin-top:2px;">Best-6 aggregate</div>
</td>
<td align="center" style="width:25%;background:#f4faf6;border:1px solid #d7e8dc;border-radius:8px;padding:10px 4px;">
<div style="font-size:18px;font-weight:700;color:#1a2b22;">${row.attendancePct === null ? "—" : esc(`${row.attendancePct}%`)}</div>
<div style="font-size:10px;color:#7a857d;text-transform:uppercase;letter-spacing:0.4px;margin-top:2px;">Attendance</div>
</td>
</tr>
</table>
${row.promoted !== null ? `<p style="margin:14px 0 0 0;font-size:12.5px;line-height:1.55;color:#3d4a42;background:#f4faf6;border:1px solid #d7e8dc;border-radius:8px;padding:10px 12px;">${row.promoted ? "Promotion: the school recommends promotion to the next class." : "Promotion: performance is below the promotion bar — the school will be in touch about support and next steps."}</p>` : ""}
<p style="margin:14px 0 0 0;font-size:12.5px;line-height:1.55;color:#3d4a42;">The full terminal report — every subject's class score, exam score and WAEC grade — is waiting in your Nyansapo parent portal.</p>
</td></tr>
<tr><td style="padding:14px 24px;background:#fafbfa;border-top:1px solid #edf1ee;font-size:11px;color:#8a948c;text-align:center;">
These results were released by ${esc(school.name)}. Powered by Nyansapo.
</td></tr>
</table>
</div>
</body></html>`;
}

import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendSms, sendAutomated, renderTemplate } from "@/lib/sms";
import { gatewayConfigStatus } from "@/lib/sms-gateway";

// ── GET /api/school/[id]/ops/sms — settings, templates, history, audiences ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["sms.view"] });
  if (!auth.ok) return auth.res;
  const school = await db.school.findUnique({ where: { id: schoolId } });
  const curTerm = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
  const termName = curTerm?.name ?? "the term";
  const schoolName = school?.name ?? "the school";

  const url = new URL(req.url);
  const category = url.searchParams.get("category") ?? undefined;

  const setting = await db.smsSetting.findUnique({ where: { schoolId } });
  const templates = await db.smsTemplate.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
  const messages = await db.smsMessage.findMany({
    where: { schoolId, ...(category && category !== "all" ? { category } : {}) },
    orderBy: { sentAt: "desc" },
    take: 60,
  });
  const total = await db.smsMessage.count({ where: { schoolId } });
  const delivered = await db.smsMessage.count({ where: { schoolId, status: "DELIVERED" } });

  // audiences: classes for targeted sending
  const classrooms = await db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
  const enrolled = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, include: { student: true, classroom: true } });
  const automations = await db.smsAutomation.findMany({ where: { schoolId }, orderBy: { createdAt: "asc" } });

  // arrears audience (current term)
  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
  let arrearsCount = 0;
  if (term) {
    const overdueInvoices = await db.invoice.findMany({ where: { schoolId, termId: term.id } });
    arrearsCount = overdueInvoices.filter((i) => i.totalCents - i.discountCents - i.paidCents > 0).length;
  }

  return NextResponse.json({
    gateway: gatewayConfigStatus(),
    setting: setting ? { provider: setting.provider, senderId: setting.senderId, credits: setting.credits, autoFeeReminders: setting.autoFeeReminders, autoAbsenceAlerts: setting.autoAbsenceAlerts, autoPaymentReceipts: setting.autoPaymentReceipts, autoLessonNoteAlerts: setting.autoLessonNoteAlerts } : null,
    templates: templates.map((t) => ({ id: t.id, name: t.name, category: t.category, body: t.body, isDefault: t.isDefault })),
    messages: messages.map((m) => ({ id: m.id, recipientName: m.recipientName, recipientPhone: m.recipientPhone, body: m.body, category: m.category, status: m.status, sentByName: m.sentByName, sentAt: m.sentAt, errorReason: m.errorReason })),
    stats: { total, delivered, deliveredRate: total ? Math.round((delivered / total) * 100) : 0, credits: setting?.credits ?? 0 },
    classes: classrooms.map((c) => ({ id: c.id, name: c.name, learners: enrolled.filter((e) => e.classroomId === c.id).length })),
    guardians: enrolled.length,
    arrearsCount,
    automations: automations.map((a) => ({ id: a.id, name: a.name, trigger: a.trigger, recipients: a.recipients, body: a.body, offsetDays: a.offsetDays, active: a.active, lastRunAt: a.lastRunAt, sentCount: a.sentCount })),
  });
}

// ── POST /api/school/[id]/ops/sms ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["sms.send"] });
  if (!auth.ok) return auth.res;
  const user = auth.user;

  if (action === "save-settings") {
    const { provider, senderId, autoFeeReminders, autoAbsenceAlerts, autoPaymentReceipts, autoLessonNoteAlerts } = body;
    const existing = await db.smsSetting.findUnique({ where: { schoolId } });
    const data = {
      provider: ["ARKESEL", "TERMII", "HUBTEL"].includes(provider) ? provider : "SIMULATED", senderId: senderId ?? "NYANSAPO",
      autoFeeReminders: !!autoFeeReminders, autoAbsenceAlerts: !!autoAbsenceAlerts, autoPaymentReceipts: !!autoPaymentReceipts,
      // lesson-note toggle: preserve the stored value when the caller omits it
      ...(autoLessonNoteAlerts !== undefined ? { autoLessonNoteAlerts: !!autoLessonNoteAlerts } : existing ? {} : { autoLessonNoteAlerts: true }),
    };
    const setting = existing ? await db.smsSetting.update({ where: { schoolId }, data }) : await db.smsSetting.create({ data: { ...data, schoolId } });
    return NextResponse.json({ setting });
  }

  if (action === "topup") {
    const setting = await db.smsSetting.upsert({ where: { schoolId }, update: { credits: { increment: Number(body.credits) || 500 } }, create: { schoolId, credits: Number(body.credits) || 500 } });
    return NextResponse.json({ credits: setting.credits });
  }

  if (action === "save-template") {
    const { id, name, category, body: tplBody } = body;
    if (!name || !tplBody) return NextResponse.json({ error: "Name and body are required" }, { status: 400 });
    const data = { name, category: category ?? "GENERAL", body: tplBody };
    const tpl = id ? await db.smsTemplate.update({ where: { id }, data }) : await db.smsTemplate.create({ data: { ...data, schoolId } });
    return NextResponse.json({ template: tpl });
  }

  if (action === "delete-template") {
    await db.smsTemplate.delete({ where: { id: body.id } }).catch(() => null);
    return NextResponse.json({ ok: true });
  }

  // ── automation rules (iedupro parity) ──────────────────
  const TRIGGERS = ["PAYMENT_RECEIVED", "INVOICE_ISSUED", "ABSENCE_MARKED", "RESULTS_PUBLISHED", "BILL_REMINDER"];
  if (action === "save-automation") {
    const { id, name, trigger, recipients, body: ruleBody, offsetDays } = body;
    if (!name?.trim() || !ruleBody?.trim()) return NextResponse.json({ error: "Rule name and message body are required" }, { status: 400 });
    if (!TRIGGERS.includes(trigger)) return NextResponse.json({ error: "Unknown trigger" }, { status: 400 });
    const data = {
      name: String(name).trim(), trigger, recipients: recipients ?? "GUARDIANS",
      body: String(ruleBody), offsetDays: Number.isFinite(Number(offsetDays)) ? Number(offsetDays) : 0,
    };
    const a = id ? await db.smsAutomation.update({ where: { id }, data }) : await db.smsAutomation.create({ data: { ...data, schoolId } });
    return NextResponse.json({ automation: a });
  }

  if (action === "toggle-automation") {
    const a = await db.smsAutomation.findUnique({ where: { id: body.id } });
    if (!a || a.schoolId !== schoolId) return NextResponse.json({ error: "Rule not found" }, { status: 404 });
    const updated = await db.smsAutomation.update({ where: { id: a.id }, data: { active: !a.active } });
    return NextResponse.json({ automation: updated });
  }

  if (action === "delete-automation") {
    const a = await db.smsAutomation.findUnique({ where: { id: body.id } });
    if (!a || a.schoolId !== schoolId) return NextResponse.json({ error: "Rule not found" }, { status: 404 });
    await db.smsAutomation.delete({ where: { id: a.id } });
    return NextResponse.json({ ok: true });
  }

  // run a BILL_REMINDER rule now: unpaid current-term invoices due within |offsetDays|
  if (action === "run-automation") {
    const a = await db.smsAutomation.findUnique({ where: { id: body.id } });
    if (!a || a.schoolId !== schoolId) return NextResponse.json({ error: "Rule not found" }, { status: 404 });
    if (a.trigger !== "BILL_REMINDER") return NextResponse.json({ error: "Only bill-reminder rules can be run manually — others fire on their events" }, { status: 400 });
    const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
    if (!term) return NextResponse.json({ error: "No current term" }, { status: 400 });
    const school = await db.school.findUnique({ where: { id: schoolId } });
    const invoices = await db.invoice.findMany({ where: { schoolId, termId: term.id, status: { in: ["UNPAID", "PARTIAL", "OVERDUE"] } }, include: { student: true } });
    const horizon = Date.now() + Math.max(0, a.offsetDays) * 864e5;
    const due = invoices.filter((i) => {
      const bal = i.totalCents - i.discountCents - i.paidCents;
      return bal > 0 && (!i.dueDate || i.dueDate.getTime() <= horizon);
    });
    if (!due.length) return NextResponse.json({ sent: 0, failed: 0, message: "No invoices due within the rule window — nothing to send." });
    const setting = await db.smsSetting.findUnique({ where: { schoolId } });
    if (setting && setting.credits < due.length) return NextResponse.json({ error: `Not enough credits (${setting.credits} left, ${due.length} needed)` }, { status: 400 });
    let sent = 0, failed = 0;
    for (const inv of due) {
      const s = inv.student;
      const msg = await sendAutomated({
        db, schoolId, trigger: "BILL_REMINDER", rule: a,
        defaultBody: `Friendly reminder: {student}'s term balance is {balance}. Kindly settle at the office or via MoMo. Thank you — {school}.`,
        vars: {
          student: `${s.firstName} ${s.lastName}`, school: school?.name ?? "the school",
          balance: `GH₵${((inv.totalCents - inv.discountCents - inv.paidCents) / 100).toFixed(2)}`,
          date: new Date().toLocaleDateString("en-GB"), term: term.name,
        },
        studentId: s.id, recipientName: s.guardianName, recipientPhone: s.guardianPhone, sentByName: user.fullName,
      });
      if (msg.status === "FAILED") failed++; else sent++;
    }
    await db.smsAutomation.update({ where: { id: a.id }, data: { lastRunAt: new Date() } });
    return NextResponse.json({ sent, failed });
  }

  if (action === "send") {
    const { audience, body: message, templateId } = body; // audience: ALL | ARREARS | CLASS:<id> | CUSTOM
    let text = message;
    if (!text && templateId) {
      const tpl = await db.smsTemplate.findUnique({ where: { id: templateId } });
      if (tpl) text = tpl.body;
    }
    if (!text) return NextResponse.json({ error: "Message body is required" }, { status: 400 });

    // resolve recipients
    let recipients: { studentId: string; name: string; guardianName: string; guardianPhone: string; classroom: string; balanceCents?: number }[] = [];
    const enrolled = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, include: { student: true, classroom: true } });
    if (audience === "ALL") {
      recipients = enrolled.map((e) => ({ studentId: e.studentId, name: `${e.student.firstName} ${e.student.lastName}`, guardianName: e.student.guardianName, guardianPhone: e.student.guardianPhone, classroom: e.classroom.name }));
    } else if (audience?.startsWith("CLASS:")) {
      const clsId = audience.slice(6);
      recipients = enrolled.filter((e) => e.classroomId === clsId).map((e) => ({ studentId: e.studentId, name: `${e.student.firstName} ${e.student.lastName}`, guardianName: e.student.guardianName, guardianPhone: e.student.guardianPhone, classroom: e.classroom.name }));
    } else if (audience === "ARREARS") {
      const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
      if (!term) return NextResponse.json({ error: "No current term" }, { status: 400 });
      const invoices = await db.invoice.findMany({ where: { schoolId, termId: term.id }, include: { student: true } });
      const classByStudent: Record<string, string> = {};
      enrolled.forEach((e) => (classByStudent[e.studentId] = e.classroom.name));
      recipients = invoices
        .filter((i) => i.totalCents - i.discountCents - i.paidCents > 0)
        .map((i) => ({ studentId: i.studentId, name: `${i.student.firstName} ${i.student.lastName}`, guardianName: i.student.guardianName, guardianPhone: i.student.guardianPhone, classroom: classByStudent[i.studentId] ?? "", balanceCents: i.totalCents - i.discountCents - i.paidCents }));
    } else {
      return NextResponse.json({ error: "Unknown audience" }, { status: 400 });
    }
    if (!recipients.length) return NextResponse.json({ error: "No recipients found for this audience" }, { status: 400 });

    const school = await db.school.findUnique({ where: { id: schoolId } });
    const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
    const schoolName = school?.name ?? "the school";
    const termName = term?.name ?? "the term";

    const setting = await db.smsSetting.findUnique({ where: { schoolId } });
    if (setting && setting.credits < recipients.length) {
      return NextResponse.json({ error: `Not enough credits (${setting.credits} left, ${recipients.length} needed) — top up first.` }, { status: 400 });
    }

    let sent = 0, failed = 0;
    for (const r of recipients) {
      const rendered = renderTemplate(text, {
        student: r.name, school: schoolName, class: r.classroom, guardian: r.guardianName,
        balance: r.balanceCents !== undefined ? `GH₵${(r.balanceCents / 100).toFixed(2)}` : "",
        amount: r.balanceCents !== undefined ? `GH₵${(r.balanceCents / 100).toFixed(2)}` : "",
        term: termName, date: new Date().toLocaleDateString("en-GB"), message: "",
      });
      const msg = await sendSms({ db, schoolId, studentId: r.studentId, recipientName: r.guardianName, recipientPhone: r.guardianPhone, body: rendered, category: "ANNOUNCEMENT", sentByName: user.fullName });
      // promote FEES reminders to the right category when triggered from arrears
      if (audience === "ARREARS" && msg.status !== "FAILED") {
        await db.smsMessage.update({ where: { id: msg.id }, data: { category: "FEES" } });
      }
      if (msg.status === "FAILED") failed++; else sent++;
    }
    return NextResponse.json({ sent, failed });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

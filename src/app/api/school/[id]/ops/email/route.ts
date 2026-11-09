import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendEmail, invoiceEmailHtml, testEmailHtml } from "@/lib/email";

// ── GET /api/school/[id]/ops/email — settings (never the password), log,
// stats and current-term bulk counts (CI3 Invoice_email parity channel) ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["email.send"], strict: true });
  if (!auth.ok) return auth.res;

  const setting = await db.emailSetting.findUnique({ where: { schoolId } });
  const messages = await db.emailMessage.findMany({
    where: { schoolId },
    orderBy: { sentAt: "desc" },
    take: 60,
    select: { id: true, toAddress: true, toName: true, subject: true, category: true, status: true, errorReason: true, sentByName: true, sentAt: true },
  });
  const total = await db.emailMessage.count({ where: { schoolId } });
  const sent = await db.emailMessage.count({ where: { schoolId, status: "SENT" } });
  const failed = await db.emailMessage.count({ where: { schoolId, status: "FAILED" } });
  const simulated = await db.emailMessage.count({ where: { schoolId, status: "SIMULATED" } });

  // current-term invoices with / without a guardian email (bulk-send counts)
  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
  let emailable = 0, withoutEmail = 0;
  if (term) {
    const invoices = await db.invoice.findMany({
      where: { schoolId, termId: term.id },
      include: { student: { select: { guardianEmail: true } } },
    });
    for (const i of invoices) {
      if (i.student.guardianEmail) emailable++;
      else withoutEmail++;
    }
  }

  return NextResponse.json({
    setting: setting ? {
      enabled: setting.enabled, host: setting.host, port: setting.port, secure: setting.secure,
      username: setting.username, fromName: setting.fromName, fromAddress: setting.fromAddress,
      hasPassword: !!setting.password, // the password itself never leaves the server
    } : null,
    messages,
    stats: { total, sent, failed, simulated },
    invoiceCounts: { emailable, withoutEmail },
    termName: term?.name ?? null,
  });
}

// ── POST /api/school/[id]/ops/email ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["email.send"] });
  if (!auth.ok) return auth.res;
  const user = auth.user;

  if (action === "save-settings") {
    const { enabled, host, port, secure, username, password, fromName, fromAddress } = body;
    const enabledBool = !!enabled;
    const hostStr = String(host ?? "").trim();
    if (enabledBool && !hostStr) return NextResponse.json({ error: "Host is required when the email channel is enabled" }, { status: 400 });
    const portNum = port === undefined || port === null || port === "" ? 587 : Number(port);
    if (!Number.isInteger(portNum) || portNum < 1 || portNum > 65535) return NextResponse.json({ error: "Port must be a number between 1 and 65535" }, { status: 400 });
    const existing = await db.emailSetting.findUnique({ where: { schoolId } });
    // blank/omitted password keeps the stored one (sms save-settings pattern)
    const data = {
      enabled: enabledBool,
      host: hostStr || null,
      port: portNum,
      secure: !!secure,
      username: username ? String(username).trim() : null,
      ...(password ? { password: String(password) } : existing?.password ? {} : { password: null }),
      fromName: fromName ? String(fromName).trim() : null,
      fromAddress: fromAddress ? String(fromAddress).trim().toLowerCase() : null,
    };
    const setting = existing
      ? await db.emailSetting.update({ where: { schoolId }, data })
      : await db.emailSetting.create({ data: { ...data, schoolId } });
    return NextResponse.json({
      setting: {
        enabled: setting.enabled, host: setting.host, port: setting.port, secure: setting.secure,
        username: setting.username, fromName: setting.fromName, fromAddress: setting.fromAddress,
        hasPassword: !!setting.password,
      },
    });
  }

  if (action === "test-send") {
    const to = body.to ? String(body.to).trim().toLowerCase() : user.email;
    if (!to) return NextResponse.json({ error: "No destination address — set one in your profile first" }, { status: 400 });
    const school = await db.school.findUnique({ where: { id: schoolId } });
    const row = await sendEmail({
      db, schoolId, to,
      subject: "Nyansapo email channel test",
      html: testEmailHtml(school?.name ?? "your school"),
      category: "TEST", sentByName: user.fullName,
    });
    return NextResponse.json({ message: { id: row.id, toAddress: row.toAddress, toName: row.toName, subject: row.subject, category: row.category, status: row.status, errorReason: row.errorReason, sentByName: row.sentByName, sentAt: row.sentAt } });
  }

  if (action === "send-invoice") {
    const invoiceId = String(body.invoiceId ?? "");
    if (!invoiceId) return NextResponse.json({ error: "invoiceId is required" }, { status: 400 });
    const invoice = await db.invoice.findUnique({
      where: { id: invoiceId },
      include: { student: true, term: true, items: true },
    });
    if (!invoice || invoice.schoolId !== schoolId) return NextResponse.json({ error: "Invoice not found" }, { status: 404 });
    const guardianEmail = invoice.student.guardianEmail?.trim().toLowerCase();
    if (!guardianEmail) return NextResponse.json({ error: "This learner's guardian has no email address on file" }, { status: 400 });
    const school = await db.school.findUnique({ where: { id: schoolId } });
    let classroomName = invoice.classroomName;
    if (!classroomName) {
      const enr = await db.enrollment.findFirst({
        where: { studentId: invoice.studentId, schoolId, status: "ACTIVE" },
        include: { classroom: true },
      });
      classroomName = enr?.classroom.name ?? null;
    }
    const html = invoiceEmailHtml({
      school, invoice,
      student: { ...invoice.student, classroom: classroomName },
      term: invoice.term, items: invoice.items,
    });
    const row = await sendEmail({
      db, schoolId, to: guardianEmail, toName: invoice.student.guardianName,
      subject: `Invoice ${invoice.invoiceNo} — ${school?.name ?? "school"}`,
      html, category: "INVOICE", sentByName: user.fullName, studentId: invoice.studentId,
    });
    return NextResponse.json({ message: { id: row.id, toAddress: row.toAddress, toName: row.toName, subject: row.subject, category: row.category, status: row.status, errorReason: row.errorReason, sentByName: row.sentByName, studentId: row.studentId, sentAt: row.sentAt } });
  }

  if (action === "bulk-send-invoices") {
    const scope = String(body.scope ?? "");
    if (scope !== "ALL" && scope !== "UNPAID" && !scope.startsWith("CLASS:")) {
      return NextResponse.json({ error: "Unknown scope — use ALL, UNPAID or CLASS:<classroomId>" }, { status: 400 });
    }
    const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
    if (!term) return NextResponse.json({ error: "No current term" }, { status: 400 });
    const invoices = await db.invoice.findMany({
      where: { schoolId, termId: term.id },
      include: { student: true, term: true, items: true },
      orderBy: { invoiceNo: "asc" },
    });
    let targets = invoices;
    if (scope === "UNPAID") {
      targets = invoices.filter((i) => i.totalCents - i.discountCents - i.paidCents > 0);
    } else if (scope.startsWith("CLASS:")) {
      const classroomId = scope.slice(6);
      const enrolled = await db.enrollment.findMany({ where: { schoolId, classroomId, status: "ACTIVE" }, select: { studentId: true } });
      const ids = new Set(enrolled.map((e) => e.studentId));
      targets = invoices.filter((i) => ids.has(i.studentId));
    }
    if (targets.length > 200) {
      return NextResponse.json({ error: `Too many invoices in this scope (${targets.length}) — narrow it down to at most 200 per run` }, { status: 400 });
    }
    if (!targets.length) {
      return NextResponse.json({ sent: 0, skipped: 0, failed: 0, message: "No current-term invoices in this scope — nothing to send." });
    }
    const school = await db.school.findUnique({ where: { id: schoolId } });
    const enrolled = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, include: { classroom: true } });
    const classByStudent = new Map(enrolled.map((e) => [e.studentId, e.classroom.name]));
    let sent = 0, skipped = 0, failed = 0;
    for (const inv of targets) {
      const email = inv.student.guardianEmail?.trim().toLowerCase();
      if (!email) { skipped++; continue; } // guardians without email are skipped, never block the run
      const html = invoiceEmailHtml({
        school, invoice: inv,
        student: { ...inv.student, classroom: inv.classroomName ?? classByStudent.get(inv.studentId) ?? null },
        term: inv.term, items: inv.items,
      });
      const row = await sendEmail({
        db, schoolId, to: email, toName: inv.student.guardianName,
        subject: `Invoice ${inv.invoiceNo} — ${school?.name ?? "school"}`,
        html, category: "INVOICE", sentByName: user.fullName, studentId: inv.studentId,
      });
      if (row.status === "FAILED") failed++; else sent++;
    }
    return NextResponse.json({ sent, skipped, failed });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

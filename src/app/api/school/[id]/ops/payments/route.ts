import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import {
  createPaymentRequest, checkPaymentRequest, failRequest, cancelRequest,
  paymentsProvider, publicPayUrl, PURPOSE_LABELS, MOMO_LABEL, PAYMENT_PURPOSES, type PaymentPurpose,
  logEvent,
} from "@/lib/payment-requests";
import { hubtelPaymentsConfigured, hubtelWebhookUrl, detectMomoChannel } from "@/lib/hubtel";
import { sendSms } from "@/lib/sms";
import { toGhanaIntl } from "@/lib/phone";

const fmt = (cents: number) => `₵${(cents / 100).toFixed(2)}`;

/** Absolute pay link for a request — HUBTEL_CALLBACK_BASE when set, else the
 *  origin this API was called on (works for the school's own deployment and
 *  for demo/preview hosts without extra config). */
function absoluteLink(req: NextRequest, request: { checkoutUrl: string | null; clientReference: string }): string | null {
  if (request.checkoutUrl?.startsWith("http")) return request.checkoutUrl;
  const path = request.checkoutUrl?.startsWith("/") ? request.checkoutUrl : `/pay/${request.clientReference}`;
  const base = process.env.HUBTEL_CALLBACK_BASE || process.env.PUBLIC_APP_BASE_URL || req.headers.get("origin") || `${req.nextUrl.protocol}//${req.nextUrl.host}`;
  return base ? `${base.replace(/\/$/, "")}${path}` : null;
}

// ── GET /api/school/[id]/ops/payments — module payload ─────────
// requests list (with learner + applied receipt), 7-day stats, provider
// status, and the Hubtel setup card values.
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["fees.view", "fees.collect"] });
  if (!auth.ok) return auth.res;

  const url = new URL(req.url);
  const search = (url.searchParams.get("search") ?? "").trim().toLowerCase();
  const statusFilter = url.searchParams.get("status") ?? "ALL";

  const where: any = { schoolId };
  if (["PENDING", "COMPLETED", "FAILED", "CANCELLED"].includes(statusFilter)) where.status = statusFilter;

  let requests = await db.paymentRequest.findMany({
    where,
    orderBy: { createdAt: "desc" },
    take: 150,
    include: {
      student: { select: { id: true, firstName: true, lastName: true, nyansapoIndex: true } },
      appliedPayment: { select: { id: true, receiptNo: true } },
      invoice: { select: { id: true, invoiceNo: true } },
    },
  });

  // in-memory search across learner / reference / payer
  if (search) {
    requests = requests.filter((r) =>
      `${r.student?.firstName ?? ""} ${r.student?.lastName ?? ""}`.toLowerCase().includes(search) ||
      r.clientReference.toLowerCase().includes(search) ||
      r.payerMsisdn.includes(search) ||
      (r.appliedPayment?.receiptNo ?? "").toLowerCase().includes(search));
  }

  const weekStart = new Date(Date.now() - 7 * 864e5);
  const [pending, completedWeek, collectedWeek, failedWeek] = await Promise.all([
    db.paymentRequest.count({ where: { schoolId, status: "PENDING" } }),
    db.paymentRequest.count({ where: { schoolId, status: "COMPLETED", completedAt: { gte: weekStart } } }),
    db.paymentRequest.aggregate({ where: { schoolId, status: "COMPLETED", completedAt: { gte: weekStart } }, _sum: { amountCents: true } }),
    db.paymentRequest.count({ where: { schoolId, status: "FAILED", updatedAt: { gte: weekStart } } }),
  ]);

  return NextResponse.json({
    provider: paymentsProvider(),
    configured: hubtelPaymentsConfigured(),
    webhookUrl: hubtelWebhookUrl() || null,
    callbackBase: process.env.HUBTEL_CALLBACK_BASE || process.env.PUBLIC_APP_BASE_URL || null,
    purposes: PAYMENT_PURPOSES.map((p) => ({ id: p, label: PURPOSE_LABELS[p] })),
    stats: {
      pending,
      completedWeek,
      collectedWeekCents: collectedWeek._sum.amountCents ?? 0,
      failedWeek,
    },
    requests: requests.map((r) => ({
      id: r.id, studentId: r.studentId,
      student: r.student ? `${r.student.firstName} ${r.student.lastName}` : null,
      index: r.student?.nyansapoIndex ?? null,
      purpose: r.purpose, purposeLabel: PURPOSE_LABELS[r.purpose] ?? r.purpose,
      invoiceNo: r.invoice?.invoiceNo ?? null,
      amountCents: r.amountCents, method: r.method,
      channel: r.channel, channelLabel: MOMO_LABEL[r.channel ?? ""] ?? null,
      payerName: r.payerName, payerMsisdn: r.payerMsisdn,
      status: r.status, provider: r.provider,
      clientReference: r.clientReference,
      checkoutUrl: r.checkoutUrl, payUrl: publicPayUrl(r),
      receiptNo: r.appliedPayment?.receiptNo ?? null,
      appliedNote: r.appliedNote, failureReason: r.failureReason,
      createdByName: r.createdByName,
      createdAt: r.createdAt, completedAt: r.completedAt, lastCheckedAt: r.lastCheckedAt,
    })),
  });
}

// ── POST — actions ─────────────────────────────────────────────
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["fees.collect", "daily.collect"] });
  if (!auth.ok) return auth.res;
  const user = auth.user;
  const body = await req.json().catch(() => ({}));
  const action = String(body.action ?? "");

  // one learner's payable accounts (for the New Request dialog): open
  // invoices + per-item daily arrears/balances + wallet balances
  if (action === "student-accounts") {
    const studentId = String(body.studentId ?? "");
    const student = await db.student.findFirst({
      where: { id: studentId, enrollments: { some: { schoolId } } },
      include: { wallet: true },
    });
    if (!student) return NextResponse.json({ error: "Learner not found" }, { status: 404 });

    const invoices = await db.invoice.findMany({
      where: { studentId, schoolId, status: { in: ["UNPAID", "PARTIAL", "OVERDUE"] }, writtenOff: false },
      orderBy: { createdAt: "desc" }, take: 12,
      select: { id: true, invoiceNo: true, totalCents: true, discountCents: true, paidCents: true, dueDate: true },
    });

    const services = await db.dailyService.findMany({ where: { schoolId }, orderBy: { name: "asc" }, select: { id: true, name: true, category: true, chargeMode: true, priceCents: true } });
    const charges = await db.dailyCharge.findMany({ where: { studentId, status: "OWED" }, select: { serviceId: true, amountCents: true } });
    const balances = await db.serviceBalance.findMany({ where: { studentId }, select: { serviceId: true, prepaidCents: true } });

    const arrearsBy: Record<string, number> = {};
    charges.forEach((c) => (arrearsBy[c.serviceId] = (arrearsBy[c.serviceId] ?? 0) + c.amountCents));
    const balBy: Record<string, number> = {};
    balances.forEach((b) => (balBy[b.serviceId] = b.prepaidCents));

    const dailyItems = services
      .filter((s) => s.category !== "TRANSPORT" || arrearsBy[s.id] > 0)
      .map((s) => ({
        serviceId: s.id, name: s.name, category: s.category, chargeMode: s.chargeMode,
        rateCents: s.priceCents ?? 0,
        arrearsCents: arrearsBy[s.id] ?? 0,
        balanceCents: balBy[s.id] ?? 0,
      }));

    return NextResponse.json({
      student: {
        id: student.id, name: `${student.firstName} ${student.lastName}`,
        guardianName: student.guardianName, guardianPhone: student.guardianPhone,
        intlPhone: toGhanaIntl(student.guardianPhone),
        channel: detectMomoChannel(student.guardianPhone),
      },
      invoices: invoices.map((i) => ({
        id: i.id, invoiceNo: i.invoiceNo,
        totalCents: i.totalCents, discountCents: i.discountCents, paidCents: i.paidCents,
        balanceCents: Math.max(0, i.totalCents - i.discountCents - i.paidCents),
        dueDate: i.dueDate,
      })),
      dailyItems,
      wallet: { prepaidCents: student.wallet?.prepaidCents ?? 0, transportCents: student.wallet?.transportCents ?? 0 },
    });
  }

  if (action === "create") {
    const { studentId, purpose, invoiceId, items, walletKind, amountCents, method, payerMsisdn, channel, sendLinkSms } = body;
    if (!PAYMENT_PURPOSES.includes(purpose)) return NextResponse.json({ error: "Unknown payment purpose" }, { status: 400 });
    const r = await createPaymentRequest(db, {
      schoolId, studentId: String(studentId ?? ""), purpose: purpose as PaymentPurpose,
      invoiceId: invoiceId ?? null,
      items: Array.isArray(items) ? items.map((i: any) => ({ serviceId: String(i.serviceId ?? ""), amountCents: Math.max(0, Math.round(Number(i.amountCents) || 0)) })) : undefined,
      walletKind: walletKind ?? null,
      amountCents: Math.round(Number(amountCents) || 0),
      method: method === "PAYMENT_LINK" ? "PAYMENT_LINK" : "MOMO_PROMPT",
      payerMsisdn: payerMsisdn || undefined, channel: channel ?? null,
      createdByName: user.fullName,
    });
    if (!r.ok) return NextResponse.json({ error: r.error }, { status: 400 });

    // optionally SMS the checkout link to the guardian right away
    let smsSent = false;
    if (sendLinkSms && r.request.method === "PAYMENT_LINK") {
      const link = absoluteLink(req, r.request);
      const student = await db.student.findUnique({ where: { id: r.request.studentId! } });
      if (link && student) {
        const msg = await sendSms({
          db, schoolId, studentId: student.id, recipientName: student.guardianName, recipientPhone: r.request.payerMsisdn,
          body: `Pay ${fmt(r.request.amountCents)} for ${student.firstName}: ${link} — ${PURPOSE_LABELS[r.request.purpose]}. Thank you.`,
          category: "PAYMENT", sentByName: user.fullName,
        });
        smsSent = msg.status === "SENT";
        await logEvent(db, r.request.id, "LINK_SHARED", { to: r.request.payerMsisdn }, smsSent ? "link sent by SMS" : `SMS failed: ${msg.errorReason ?? ""}`);
      }
    }
    return NextResponse.json({
      request: { ...r.request, payUrl: publicPayUrl(r.request) },
      smsSent,
    });
  }

  if (action === "share-link") {
    const request = await db.paymentRequest.findFirst({ where: { id: String(body.id ?? ""), schoolId } });
    if (!request) return NextResponse.json({ error: "Request not found" }, { status: 404 });
    if (request.method !== "PAYMENT_LINK" || request.status !== "PENDING")
      return NextResponse.json({ error: "Only pending payment links can be shared" }, { status: 400 });
    const link = absoluteLink(req, request);
    const student = request.studentId ? await db.student.findUnique({ where: { id: request.studentId } }) : null;
    if (!link || !student) return NextResponse.json({ error: "No link available" }, { status: 400 });
    const msg = await sendSms({
      db, schoolId, studentId: student.id, recipientName: request.payerName, recipientPhone: request.payerMsisdn,
      body: `Pay ${fmt(request.amountCents)} for ${student.firstName}: ${link} — ${PURPOSE_LABELS[request.purpose] ?? "School payment"}. Thank you.`,
      category: "PAYMENT", sentByName: user.fullName,
    });
    const smsSent = msg.status === "SENT";
    await logEvent(db, request.id, "LINK_SHARED", { to: request.payerMsisdn }, smsSent ? "link re-sent by SMS" : `SMS failed: ${msg.errorReason ?? ""}`);
    return NextResponse.json({ smsSent, link });
  }

  if (action === "check") {
    const request = await db.paymentRequest.findFirst({ where: { id: String(body.id ?? ""), schoolId } });
    if (!request) return NextResponse.json({ error: "Request not found" }, { status: 404 });
    const updated = await checkPaymentRequest(db, request);
    return NextResponse.json({ request: { ...updated, payUrl: publicPayUrl(updated) } });
  }

  if (action === "cancel") {
    const request = await db.paymentRequest.findFirst({ where: { id: String(body.id ?? ""), schoolId } });
    if (!request) return NextResponse.json({ error: "Request not found" }, { status: 404 });
    if (request.status !== "PENDING") return NextResponse.json({ error: "Only pending requests can be cancelled" }, { status: 400 });
    const updated = await cancelRequest(db, request.id, `Cancelled by ${user.fullName}`);
    return NextResponse.json({ request: updated });
  }

  if (action === "simulate-fail") {
    const request = await db.paymentRequest.findFirst({ where: { id: String(body.id ?? ""), schoolId } });
    if (!request) return NextResponse.json({ error: "Request not found" }, { status: 404 });
    if (request.provider !== "SIMULATED" || request.status !== "PENDING")
      return NextResponse.json({ error: "Only simulated pending requests can be failed" }, { status: 400 });
    const updated = await failRequest(db, request.id, "Simulated failure (test)");
    return NextResponse.json({ request: updated });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

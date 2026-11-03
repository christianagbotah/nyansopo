import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { applyCompletion, failRequest, logEvent, PURPOSE_LABELS, MOMO_LABEL, paymentsProvider } from "@/lib/payment-requests";

/* ── Public payment status (the /pay/{ref} parent page) ────────
   Unauthenticated by design — a parent opens the link on their phone. Only
   minimal, safe fields are exposed: school name, learner first name +
   initial, amount, purpose and status. The SIMULATED-mode "pay" action is
   only accepted while the deployment runs without Hubtel credentials. */

const STATUS_LABEL: Record<string, string> = {
  PENDING: "Awaiting payment",
  COMPLETED: "Paid — thank you!",
  FAILED: "Payment failed",
  CANCELLED: "Cancelled",
};

export async function GET(_req: NextRequest, { params }: { params: Promise<{ ref: string }> }) {
  const { ref } = await params;
  const request = await db.paymentRequest.findUnique({
    where: { clientReference: ref.toUpperCase() },
    include: { school: { select: { name: true, logoColor: true } }, appliedPayment: { select: { receiptNo: true } } },
  });
  if (!request) return NextResponse.json({ error: "Payment reference not found" }, { status: 404 });

  let studentName: string | null = null;
  if (request.studentId) {
    const st = await db.student.findUnique({ where: { id: request.studentId }, select: { firstName: true, lastName: true } });
    studentName = st ? `${st.firstName} ${st.lastName.slice(0, 1)}.` : null;
  }

  return NextResponse.json({
    status: request.status,
    statusLabel: STATUS_LABEL[request.status] ?? request.status,
    purposeLabel: PURPOSE_LABELS[request.purpose] ?? "School payment",
    amountCents: request.amountCents,
    school: { name: request.school.name, logoColor: request.school.logoColor },
    studentName,
    receiptNo: request.appliedPayment?.receiptNo ?? null,
    appliedNote: request.appliedNote,
    failureReason: request.failureReason,
    method: request.method,
    channelLabel: MOMO_LABEL[request.channel ?? ""] ?? null,
    checkoutUrl: request.status === "PENDING" && request.method === "PAYMENT_LINK" ? request.checkoutUrl : null,
    provider: request.provider,
    simulated: paymentsProvider() === "SIMULATED",
    createdAt: request.createdAt,
  });
}

/** SIMULATED mode only — the public page's "I have paid — simulate" button. */
export async function POST(req: NextRequest, { params }: { params: Promise<{ ref: string }> }) {
  const { ref } = await params;
  if (paymentsProvider() !== "SIMULATED")
    return NextResponse.json({ error: "Simulation disabled — live Hubtel credentials are active" }, { status: 400 });
  const body = await req.json().catch(() => ({}));
  const request = await db.paymentRequest.findUnique({ where: { clientReference: ref.toUpperCase() } });
  if (!request) return NextResponse.json({ error: "Payment reference not found" }, { status: 404 });
  if (request.status !== "PENDING") return NextResponse.json({ error: "This request is no longer pending" }, { status: 400 });

  if (body.action === "fail") {
    await failRequest(db, request.id, "Simulated failure (from pay page)");
    return NextResponse.json({ status: "FAILED" });
  }
  await logEvent(db, request.id, "COMPLETED", { source: "simulate" }, "simulated payment from public page");
  const updated = await applyCompletion(db, request.id, "simulate");
  return NextResponse.json({ status: updated.status, receiptNo: updated.appliedPaymentId });
}

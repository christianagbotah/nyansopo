import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { applyCompletion, failRequest, cancelRequest, parseHubtelCallback, logEvent } from "@/lib/payment-requests";

/* ── Hubtel payment webhook ────────────────────────────────────
   Hubtel POSTs the outcome of a momo prompt / checkout here (the
   PrimaryCallbackURL we pass on every request). Payload generations vary
   (ResponseCode 0000/00, Status "Paid"/"Success", form-encoded or JSON) —
   parseHubtelCallback normalises them all.

   Idempotency: the completion claim inside applyCompletion is an atomic
   compare-and-set, so webhook retries never double-post a payment.

   Optional shared secret: when HUBTEL_WEBHOOK_SECRET is set the callback URL
   carries ?secret=… and mismatches are rejected with 401. */

export async function POST(req: NextRequest) {
  const url = new URL(req.url);

  const secret = process.env.HUBTEL_WEBHOOK_SECRET;
  if (secret && url.searchParams.get("secret") !== secret)
    return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const contentType = req.headers.get("content-type") ?? "";
  let payload: any = {};
  if (contentType.includes("application/json")) {
    payload = await req.json().catch(() => ({}));
  } else {
    // form-encoded callbacks
    const form = await req.formData().catch(() => null);
    if (form) {
      payload = {};
      form.forEach((v, k) => { payload[k] = typeof v === "string" ? v : v.name; });
    }
  }

  const parsed = parseHubtelCallback(payload);
  if (!parsed.reference && !parsed.transactionId) {
    // still log it against nothing — audit trail of stray callbacks
    return NextResponse.json({ success: true, ignored: "no reference" });
  }

  const request = await db.paymentRequest.findFirst({
    where: parsed.reference
      ? { clientReference: parsed.reference }
      : { providerTxnId: parsed.transactionId! },
  });
  if (!request) return NextResponse.json({ success: true, ignored: "unknown reference" });

  // reject amount mismatch on completion (defence against tampered callbacks)
  if (parsed.status === "COMPLETED" && parsed.amountCents != null && parsed.amountCents !== request.amountCents) {
    await logEvent(db, request.id, "ERROR", payload, `amount mismatch: expected ${request.amountCents}, callback said ${parsed.amountCents}`);
    return NextResponse.json({ success: true, ignored: "amount mismatch" });
  }

  await db.paymentRequest.update({
    where: { id: request.id },
    data: { providerTxnId: parsed.transactionId ?? request.providerTxnId },
  }).catch(() => {});
  await logEvent(db, request.id, "CALLBACK", payload, `webhook → ${parsed.status}`);

  try {
    if (parsed.status === "COMPLETED") {
      await applyCompletion(db, request.id, "webhook");
    } else if (parsed.status === "FAILED") {
      await failRequest(db, request.id, payload?.Message || payload?.ResponseMessage || "Failed at the mobile-money network");
    } else if (parsed.status === "CANCELLED") {
      await cancelRequest(db, request.id, "Cancelled by the payer (webhook)");
    }
  } catch (e: any) {
    // 500 makes Hubtel retry — correct when our ledger apply failed
    return NextResponse.json({ success: false, error: "apply failed" }, { status: 500 });
  }

  return NextResponse.json({ success: true });
}

export async function GET() {
  // health probe for the callback URL configuration card
  return NextResponse.json({ ok: true, endpoint: "hubtel webhook" });
}

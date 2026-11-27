import { NextRequest, NextResponse } from "next/server";
import { createHmac } from "crypto";
import { db } from "@/lib/db";
import { applyCompletion, failRequest, logEvent } from "@/lib/payment-requests";
import { parsePaystackEvent } from "@/lib/paystack";

/* ── Paystack payment webhook ─────────────────────────────────
   Point the Paystack dashboard (Settings → API Keys & Webhooks) at
   /api/webhooks/paystack. Every callback carries
   x-paystack-signature = HMAC-SHA512(rawBody, PAYSTACK_SECRET_KEY) which is
   verified here before the ledgers are touched.

   Idempotency: the completion claim inside applyCompletion is an atomic
   compare-and-set, so webhook retries never double-post a payment.

   Sandbox behaviour: with no PAYSTACK_SECRET_KEY on the deployment only
   unsigned callbacks whose reference belongs to a sandbox-minted request
   (PSK- prefix) are accepted, so smoke tests can rehearse the webhook path;
   anything else is rejected 401. */

export async function POST(req: NextRequest) {
  const raw = await req.text();
  const signature = req.headers.get("x-paystack-signature") ?? "";
  const secret = process.env.PAYSTACK_SECRET_KEY ?? "";

  let payload: any;
  try {
    payload = JSON.parse(raw);
  } catch {
    return NextResponse.json({ error: "Bad payload" }, { status: 400 });
  }

  if (secret) {
    const expected = createHmac("sha512", secret).update(raw).digest("hex");
    if (signature !== expected)
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  } else {
    // sandbox deployment — only clearly-sandbox references may pass unsigned
    const ref = String(payload?.data?.reference ?? "");
    if (!ref.startsWith("PSK-"))
      return NextResponse.json({ error: "Unauthorized (no PAYSTACK_SECRET_KEY configured)" }, { status: 401 });
  }

  const parsed = parsePaystackEvent(payload);
  if (parsed.event && !["charge.success", "charge.failed"].includes(parsed.event))
    return NextResponse.json({ success: true, ignored: parsed.event });

  if (!parsed.reference)
    return NextResponse.json({ success: true, ignored: "no reference" });

  const request = await db.paymentRequest.findFirst({
    where: { clientReference: parsed.reference, provider: "PAYSTACK" },
  });
  if (!request) return NextResponse.json({ success: true, ignored: "unknown reference" });

  // reject amount mismatch on completion (Paystack amounts are pesewas —
  // defence against tampered callbacks)
  if (parsed.status === "COMPLETED" && parsed.amountCents != null && parsed.amountCents !== request.amountCents) {
    await logEvent(db, request.id, "ERROR", payload, `amount mismatch: expected ${request.amountCents}, Paystack said ${parsed.amountCents}`);
    return NextResponse.json({ success: true, ignored: "amount mismatch" });
  }

  await logEvent(db, request.id, "CALLBACK", payload, `Paystack webhook → ${parsed.status}`);

  try {
    if (parsed.status === "COMPLETED") {
      await applyCompletion(db, request.id, "webhook");
    } else if (parsed.status === "FAILED") {
      await failRequest(db, request.id, String(payload?.data?.gateway_response ?? "Failed on Paystack"));
    }
  } catch {
    // 500 makes Paystack retry — correct when our ledger apply failed
    return NextResponse.json({ success: false, error: "apply failed" }, { status: 500 });
  }

  return NextResponse.json({ success: true });
}

export async function GET() {
  // health probe for the callback URL configuration card
  return NextResponse.json({ ok: true, endpoint: "paystack webhook" });
}

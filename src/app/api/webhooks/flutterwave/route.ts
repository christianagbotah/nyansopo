import { NextRequest, NextResponse } from "next/server";
import { createHash } from "crypto";
import { db } from "@/lib/db";
import { applyCompletion, failRequest, logEvent } from "@/lib/payment-requests";
import { parseFlwEvent } from "@/lib/flutterwave";

/* ── Flutterwave payment webhook ───────────────────────────────
   Point the Flutterwave dashboard (Settings → Webhooks) at
   /api/webhooks/flutterwave. Every callback carries
   verif-hash = SHA-256(FLUTTERWAVE_SECRET_KEY) which is verified here before
   the ledgers are touched (header-based verification — the body is only
   parsed for the reference and the amount defence).

   Idempotency: the completion claim inside applyCompletion is an atomic
   compare-and-set, so webhook retries never double-post a payment.

   Sandbox behaviour: with no FLUTTERWAVE_SECRET_KEY on the deployment only
   unsigned callbacks whose tx_ref belongs to a sandbox-minted request
   (FLW- prefix) are accepted, so smoke tests can rehearse the webhook path;
   anything else is rejected 401. */

export async function POST(req: NextRequest) {
  const raw = await req.text();
  const hash = req.headers.get("verif-hash") ?? "";
  const secret = process.env.FLUTTERWAVE_SECRET_KEY ?? "";

  let payload: any;
  try {
    payload = JSON.parse(raw);
  } catch {
    return NextResponse.json({ error: "Bad payload" }, { status: 400 });
  }

  if (secret) {
    const expected = createHash("sha256").update(secret).digest("hex");
    if (hash !== expected)
      return NextResponse.json({ error: "Unauthorized" }, { status: 401 });
  } else {
    // sandbox deployment — only clearly-sandbox references may pass unsigned
    const txRef = String(payload?.data?.tx_ref ?? "");
    if (!txRef.startsWith("FLW-"))
      return NextResponse.json({ error: "Unauthorized (no FLUTTERWAVE_SECRET_KEY configured)" }, { status: 401 });
  }

  const parsed = parseFlwEvent(payload);
  if (parsed.event && parsed.event !== "charge.completed")
    return NextResponse.json({ success: true, ignored: parsed.event });

  if (!parsed.txRef)
    return NextResponse.json({ success: true, ignored: "no reference" });

  const request = await db.paymentRequest.findFirst({
    where: { clientReference: parsed.txRef, provider: "FLUTTERWAVE" },
  });
  if (!request) return NextResponse.json({ success: true, ignored: "unknown reference" });

  // reject amount mismatch on completion (Flutterwave amounts are decimal
  // GHS — parseFlwEvent converts to pesewas; defence against tampered callbacks)
  if (parsed.status === "COMPLETED" && parsed.amountCents != null && parsed.amountCents !== request.amountCents) {
    await logEvent(db, request.id, "ERROR", payload, `amount mismatch: expected ${request.amountCents}, Flutterwave said ${parsed.amountCents}`);
    return NextResponse.json({ success: true, ignored: "amount mismatch" });
  }

  await logEvent(db, request.id, "CALLBACK", payload, `Flutterwave webhook → ${parsed.status}`);

  try {
    if (parsed.status === "COMPLETED") {
      await applyCompletion(db, request.id, "webhook");
    } else if (parsed.status === "FAILED") {
      await failRequest(db, request.id, "Failed on Flutterwave");
    }
  } catch {
    // 500 makes Flutterwave retry — correct when our ledger apply failed
    return NextResponse.json({ success: false, error: "apply failed" }, { status: 500 });
  }

  return NextResponse.json({ success: true });
}

export async function GET() {
  // health probe for the callback URL configuration card
  return NextResponse.json({ ok: true, endpoint: "flutterwave webhook" });
}

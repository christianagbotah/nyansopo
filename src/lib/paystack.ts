// ── Paystack integration client (payments) ────────────────────
// Paystack is one of Ghana's leading card/mobile-money gateways (card, bank
// transfer, GT/MTN/Telecel/AirtelTigo MoMo on one hosted checkout).
// Authentication is a Bearer secret key; keys live ONLY in deployment env:
//
//   PAYSTACK_SECRET_KEY   — sk_test_… / sk_live_… from the Paystack dashboard
//   PAYSTACK_API_BASE     (default https://api.paystack.co)
//
// Money is expressed in PPEWAS (GHS × 100) on the wire — the same integer
// cents the rest of Nyansapo already uses, so no conversions are lost.
//
// Webhook: the dashboard is pointed at /api/webhooks/paystack; every callback
// carries x-paystack-signature = HMAC-SHA512(rawBody, secretKey) which the
// route verifies before touching the ledgers. When the key is absent the
// payments engine runs this provider in SIMULATED mode (labelled sandbox).
//
// Endpoints used (verified against Paystack's published API reference):
//   Initialize  POST /transaction/initialize          → authorization_url
//   Verify      GET  /transaction/verify/{reference}  → success|failed|abandoned
//   Webhook     POST (dashboard-configured)           → charge.success|charge.failed

export type PaystackResult<T = Record<string, unknown>> = {
  ok: boolean;
  data?: T;
  error?: string;
};

const TIMEOUT_MS = 20_000;

export function paystackConfigured(): boolean {
  return !!process.env.PAYSTACK_SECRET_KEY;
}

const apiBase = () => (process.env.PAYSTACK_API_BASE || "https://api.paystack.co").replace(/\/$/, "");

function authHeaders(): Record<string, string> {
  return {
    authorization: `Bearer ${process.env.PAYSTACK_SECRET_KEY}`,
    "content-type": "application/json",
    accept: "application/json",
  };
}

async function paystackFetch(url: string, init: RequestInit): Promise<{ status: number; json: any }> {
  const res = await fetch(url, { ...init, signal: AbortSignal.timeout(TIMEOUT_MS) });
  const json = await res.json().catch(() => ({}));
  return { status: res.status, json };
}

// ── Hosted checkout (initialize) ───────────────────────────────
export type PaystackInitArgs = {
  email: string; // payer e-mail (required by Paystack)
  amountCents: number; // GHS pesewas — integer
  reference: string; // our client reference (unique, ≤ 100 chars)
  callbackUrl: string; // browser return URL after payment
  description?: string;
};

/** POST /transaction/initialize → { authorization_url, access_code, reference } */
export async function paystackInitTransaction(args: PaystackInitArgs): Promise<PaystackResult<{ authorizationUrl: string; accessCode: string | null; reference: string }>> {
  try {
    const { status, json } = await paystackFetch(`${apiBase()}/transaction/initialize`, {
      method: "POST",
      headers: authHeaders(),
      body: JSON.stringify({
        email: args.email,
        amount: args.amountCents,
        currency: "GHS",
        reference: args.reference,
        callback_url: args.callbackUrl,
        metadata: { description: args.description ?? "School payment" },
      }),
    });
    const data = json?.data ?? {};
    if (status < 400 && json?.status === true && data?.authorization_url)
      return {
        ok: true,
        data: {
          authorizationUrl: String(data.authorization_url),
          accessCode: data.access_code ? String(data.access_code) : null,
          reference: String(data.reference ?? args.reference),
        },
      };
    return { ok: false, error: json?.message || `Paystack HTTP ${status}` };
  } catch (e: any) {
    return { ok: false, error: `Paystack request failed: ${e?.name === "TimeoutError" ? "timeout" : e?.message || "network error"}` };
  }
}

// ── Verify (status polling) ────────────────────────────────────
/** GET /transaction/verify/{reference} → normalised PENDING | COMPLETED | FAILED.
 *  "abandoned" (payer closed the page without paying) deliberately stays
 *  PENDING on our side — the payer can come back and complete. */
export async function paystackVerify(reference: string): Promise<PaystackResult<{ status: "PENDING" | "COMPLETED" | "FAILED"; raw: string }>> {
  try {
    const { status, json } = await paystackFetch(
      `${apiBase()}/transaction/verify/${encodeURIComponent(reference)}`,
      { method: "GET", headers: authHeaders() },
    );
    if (status >= 400 || json?.status !== true) return { ok: false, error: json?.message || `Paystack HTTP ${status}` };
    const st = String(json?.data?.status ?? "").toLowerCase();
    if (st === "success") return { ok: true, data: { status: "COMPLETED", raw: st } };
    if (["failed", "reversed"].includes(st)) return { ok: true, data: { status: "FAILED", raw: st } };
    return { ok: true, data: { status: "PENDING", raw: st || "ongoing" } }; // abandoned/ongoing
  } catch (e: any) {
    return { ok: false, error: `Paystack request failed: ${e?.name === "TimeoutError" ? "timeout" : e?.message || "network error"}` };
  }
}

/** Normalise a Paystack webhook event into our callback shape. */
export function parsePaystackEvent(payload: any): { event: string; reference: string | null; status: "COMPLETED" | "FAILED" | "PENDING"; amountCents: number | null } {
  const p = payload ?? {};
  const data = p.data ?? {};
  const event = String(p.event ?? "");
  const st = String(data.status ?? "").toLowerCase();
  const amount = Number(data.amount);
  const status: "COMPLETED" | "FAILED" | "PENDING" =
    event === "charge.success" || st === "success" ? "COMPLETED"
      : event === "charge.failed" || ["failed", "reversed"].includes(st) ? "FAILED"
        : "PENDING";
  return {
    event,
    reference: data.reference ? String(data.reference) : null,
    status,
    amountCents: Number.isFinite(amount) && amount > 0 ? Math.round(amount) : null,
  };
}

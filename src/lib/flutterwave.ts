// ── Flutterwave integration client (payments) ─────────────────
// Flutterwave is West-Africa's pan-African gateway (card, bank transfer,
// MTN/Telecel/AirtelTigo MoMo, USSD on one hosted checkout). Authentication
// is a Bearer secret key; keys live ONLY in deployment env:
//
//   FLUTTERWAVE_SECRET_KEY  — FLWSECK-… from the Flutterwave dashboard
//   FLW_API_BASE            (default https://api.flutterwave.com)
//
// Money is expressed in DECIMAL GHS on the wire (Nyansapo's integer cents ÷
// 100) — converted at the boundary here so no float math leaks into ledgers.
//
// Webhook: the dashboard is pointed at /api/webhooks/flutterwave; every
// callback carries verif-hash = SHA-256(secretKey) which the route verifies.
// When the key is absent the payments engine runs this provider in SIMULATED
// mode (labelled sandbox).
//
// Endpoints used (Flutterwave v3, verified against their published docs):
//   Initialize  POST /v3/payments                          → data.link
//   Verify      GET  /v3/transactions/verify_by_reference  → successful|failed
//   Webhook     POST (dashboard-configured)                → charge.completed

export type FlutterwaveResult<T = Record<string, unknown>> = {
  ok: boolean;
  data?: T;
  error?: string;
};

const TIMEOUT_MS = 20_000;

export function flutterwaveConfigured(): boolean {
  return !!process.env.FLUTTERWAVE_SECRET_KEY;
}

const apiBase = () => (process.env.FLW_API_BASE || "https://api.flutterwave.com").replace(/\/$/, "");

function authHeaders(): Record<string, string> {
  return {
    authorization: `Bearer ${process.env.FLUTTERWAVE_SECRET_KEY}`,
    "content-type": "application/json",
    accept: "application/json",
  };
}

async function flwFetch(url: string, init: RequestInit): Promise<{ status: number; json: any }> {
  const res = await fetch(url, { ...init, signal: AbortSignal.timeout(TIMEOUT_MS) });
  const json = await res.json().catch(() => ({}));
  return { status: res.status, json };
}

// ── Hosted checkout (initialize) ───────────────────────────────
export type FlwInitArgs = {
  txRef: string; // our client reference (unique)
  amountCents: number; // GHS integer cents → decimal on the wire
  redirectUrl: string; // browser return URL after payment
  payerName?: string;
  payerEmail?: string;
  payerPhone?: string;
  description?: string;
};

/** POST /v3/payments → { status: "success", data: { link } } */
export async function flwInitPayment(args: FlwInitArgs): Promise<FlutterwaveResult<{ link: string }>> {
  try {
    const { status, json } = await flwFetch(`${apiBase()}/v3/payments`, {
      method: "POST",
      headers: authHeaders(),
      body: JSON.stringify({
        tx_ref: args.txRef,
        amount: Number((args.amountCents / 100).toFixed(2)),
        currency: "GHS",
        payment_options: "card,mobilemoneyghana,banktransfer,ussd",
        redirect_url: args.redirectUrl,
        customer: {
          email: args.payerEmail || "guardian@nyansapo.invalid",
          name: args.payerName || "Guardian",
          ...(args.payerPhone ? { phonenumber: args.payerPhone } : {}),
        },
        customizations: {
          title: "School payment",
          description: (args.description ?? "School fees & services").slice(0, 100),
        },
      }),
    });
    const data = json?.data ?? {};
    if (status < 400 && json?.status === "success" && data?.link)
      return { ok: true, data: { link: String(data.link) } };
    return { ok: false, error: json?.message || `Flutterwave HTTP ${status}` };
  } catch (e: any) {
    return { ok: false, error: `Flutterwave request failed: ${e?.name === "TimeoutError" ? "timeout" : e?.message || "network error"}` };
  }
}

// ── Verify (status polling) ────────────────────────────────────
/** GET /v3/transactions/verify_by_reference?tx_ref=… → normalised
 *  PENDING | COMPLETED | FAILED (Flutterwave says "successful"). */
export async function flwVerify(txRef: string): Promise<FlutterwaveResult<{ status: "PENDING" | "COMPLETED" | "FAILED"; raw: string }>> {
  try {
    const { status, json } = await flwFetch(
      `${apiBase()}/v3/transactions/verify_by_reference?tx_ref=${encodeURIComponent(txRef)}`,
      { method: "GET", headers: authHeaders() },
    );
    if (status >= 400 || json?.status !== "success") return { ok: false, error: json?.message || `Flutterwave HTTP ${status}` };
    const st = String(json?.data?.status ?? "").toLowerCase();
    if (st === "successful" || st === "successful ") return { ok: true, data: { status: "COMPLETED", raw: "successful" } };
    if (st === "failed") return { ok: true, data: { status: "FAILED", raw: st } };
    return { ok: true, data: { status: "PENDING", raw: st || "pending" } };
  } catch (e: any) {
    return { ok: false, error: `Flutterwave request failed: ${e?.name === "TimeoutError" ? "timeout" : e?.message || "network error"}` };
  }
}

/** Normalise a Flutterwave webhook event into our callback shape. */
export function parseFlwEvent(payload: any): { event: string; txRef: string | null; status: "COMPLETED" | "FAILED" | "PENDING"; amountCents: number | null } {
  const p = payload ?? {};
  const data = p.data ?? {};
  const event = String(p.event ?? "");
  const st = String(data.status ?? "").toLowerCase().trim();
  const amount = Number(data.amount);
  const status: "COMPLETED" | "FAILED" | "PENDING" =
    st === "successful" ? "COMPLETED" : st === "failed" ? "FAILED" : "PENDING";
  return {
    event,
    txRef: data.tx_ref ? String(data.tx_ref) : null,
    status,
    amountCents: Number.isFinite(amount) && amount > 0 ? Math.round(amount * 100) : null,
  };
}

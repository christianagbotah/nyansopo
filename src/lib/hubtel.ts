// ── Hubtel integration client (payments + SMS) ────────────────
// Hubtel is Ghana's unified mobile-money / payments / SMS provider.
// Everything authenticates with HTTP Basic (clientId:clientSecret); keys
// come from the Hubtel portals and live ONLY in deployment env vars:
//
//   HUBTEL_CLIENT_ID              — Sales/Payments API client id
//   HUBTEL_CLIENT_SECRET          — Sales/Payments API client secret
//   HUBTEL_MERCHANT_ACCOUNT_NUMBER — merchant account (receive/send/refund)
//   SMS_HUBTEL_CLIENT_ID / SMS_HUBTEL_CLIENT_SECRET — Programmable-SMS keys
//                                   (fall back to the shared HUBTEL_* pair)
//   HUBTEL_API_BASE               (default https://api.hubtel.com)
//   HUBTEL_SMS_BASE               (default https://smsc.hubtel.com)
//   HUBTEL_CALLBACK_BASE          — public base URL of THIS deployment, used
//                                   to build webhook / return / cancel URLs
//   HUBTEL_WEBHOOK_SECRET         — optional shared secret appended to the
//                                   callback URL (?secret=…)
//
// Endpoints (Hubtel Merchant Account / Sales API, verified against Hubtel's
// published PDFs, SDKs and production integrations):
//   SMS            POST {sms}/v1/messages/send                  {From,To,Content}
//   Receive money  POST {api}/v1/merchantaccount/merchants/{acc}/receive/mobilemoney
//   Send money     POST {api}/v1/merchantaccount/merchants/{acc}/send/mobilemoney
//   Refund         POST {api}/v1/merchantaccount/merchants/{acc}/transactions/refund
//   Txn status     GET  {api}/v1/merchantaccount/merchants/{acc}/transactions/status
//   Checkout       POST {api}/v1/merchantaccount/onlinecheckout/invoice/create
//   Checkout st.   GET  {api}/v1/merchantaccount/onlinecheckout/invoice/status/{token}
//
// When the credentials are absent the payments module falls back to
// SIMULATED mode (mirrors the SMS gateway approach) so schools can try the
// whole flow before their Hubtel account is provisioned.

import { toGhanaIntl } from "@/lib/phone";

export type HubtelResult<T = Record<string, unknown>> = {
  ok: boolean;
  /** normalised payload on success */
  data?: T;
  /** human-readable failure reason */
  error?: string;
};

const TIMEOUT_MS = 20_000;

export function hubtelPaymentsConfigured(): boolean {
  return !!(process.env.HUBTEL_CLIENT_ID && process.env.HUBTEL_CLIENT_SECRET && process.env.HUBTEL_MERCHANT_ACCOUNT_NUMBER);
}

export function hubtelSmsConfigured(): boolean {
  return !!(process.env.SMS_HUBTEL_CLIENT_ID || process.env.HUBTEL_CLIENT_ID) &&
    !!(process.env.SMS_HUBTEL_CLIENT_SECRET || process.env.HUBTEL_CLIENT_SECRET);
}

function basicAuth(clientId: string, clientSecret: string) {
  return `Basic ${Buffer.from(`${clientId}:${clientSecret}`).toString("base64")}`;
}

/** Ghana MSISDN → Hubtel mobile-money channel (merchant API channel names). */
export function detectMomoChannel(msisdn: string): { channel: string; label: string } {
  const intl = toGhanaIntl(msisdn) ?? msisdn.replace(/\D/g, "");
  const local = intl.startsWith("233") ? `0${intl.slice(3)}` : intl;
  const p = local.slice(0, 3);
  if (["024", "054", "055", "059", "025", "053"].includes(p)) return { channel: "mtn-gh", label: "MTN MoMo" };
  if (["020", "050"].includes(p)) return { channel: "vodafone-gh", label: "Telecel Cash" };
  if (["026", "027", "056", "057"].includes(p)) return { channel: "tigo-gh", label: "AT Money" };
  return { channel: "mtn-gh", label: "MoMo" }; // safest default for unknown prefixes
}

async function hubtelFetch(url: string, init: RequestInit): Promise<{ status: number; json: any }> {
  const res = await fetch(url, { ...init, signal: AbortSignal.timeout(TIMEOUT_MS) });
  const json = await res.json().catch(() => ({}));
  return { status: res.status, json };
}

function authHeaders(): Record<string, string> {
  return {
    authorization: basicAuth(process.env.HUBTEL_CLIENT_ID!, process.env.HUBTEL_CLIENT_SECRET!),
    "content-type": "application/json",
    accept: "application/json",
  };
}

const apiBase = () => (process.env.HUBTEL_API_BASE || "https://api.hubtel.com").replace(/\/$/, "");
const merchantUrl = (path: string) =>
  `${apiBase()}/v1/merchantaccount/merchants/${process.env.HUBTEL_MERCHANT_ACCOUNT_NUMBER}${path}`;

/** Hubtel success markers across API generations: ResponseCode "00"/"0000",
 *  status "Success", code "0000" or an explicit TransactionId/checkout token. */
function isSuccess(status: number, json: any): boolean {
  if (status >= 400) return false;
  const rc = String(json?.ResponseCode ?? json?.responseCode ?? json?.code ?? "");
  const st = String(json?.status ?? json?.Status ?? json?.ResponseMessage ?? "").toLowerCase();
  return rc === "00" || rc === "0000" || st.includes("success") || !!json?.MessageId;
}

// ── SMS ────────────────────────────────────────────────────────
/** Programmable SMS — POST {HUBTEL_SMS_BASE}/v1/messages/send with Basic auth
 *  (client id + secret from Messaging → Manage → Programmable SMS).
 *  Body {From, To, Content}; success → {MessageId, Status:"Sent"}. */
export async function hubtelSmsSend(opts: { senderId: string; recipient: string; content: string }): Promise<HubtelResult<{ messageId: string }>> {
  const clientId = process.env.SMS_HUBTEL_CLIENT_ID || process.env.HUBTEL_CLIENT_ID;
  const clientSecret = process.env.SMS_HUBTEL_CLIENT_SECRET || process.env.HUBTEL_CLIENT_SECRET;
  const base = (process.env.HUBTEL_SMS_BASE || "https://smsc.hubtel.com").replace(/\/$/, "");
  try {
    const { status, json } = await hubtelFetch(`${base}/v1/messages/send`, {
      method: "POST",
      headers: { authorization: basicAuth(clientId!, clientSecret!), "content-type": "application/json" },
      body: JSON.stringify({ From: opts.senderId, To: opts.recipient, Content: opts.content }),
    });
    if (status === 200 && (json?.MessageId || String(json?.Status ?? "").toLowerCase() === "sent"))
      return { ok: true, data: { messageId: String(json?.MessageId ?? "") } };
    if (isSuccess(status, json)) return { ok: true, data: { messageId: String(json?.MessageId ?? json?.messageId ?? "") } };
    return { ok: false, error: json?.Message || json?.message || json?.ResponseMessage || `Hubtel SMS HTTP ${status}` };
  } catch (e: any) {
    return { ok: false, error: `Hubtel SMS request failed: ${e?.name === "TimeoutError" ? "timeout" : e?.message || "network error"}` };
  }
}

// ── Receive money (USSD push / direct debit) ───────────────────
export type ReceiveMoneyArgs = {
  customerName: string;
  customerMsisdn: string; // 233…
  amount: number; // GHS decimal (not pesewas)
  description: string;
  clientReference: string; // ≤ 32 chars, unique
  channel: string; // mtn-gh | vodafone-gh | tigo-gh
  callbackUrl: string;
  secondaryCallbackUrl?: string;
};

export async function hubtelReceiveMoney(args: ReceiveMoneyArgs): Promise<HubtelResult<{ transactionId: string | null }>> {
  try {
    const { status, json } = await hubtelFetch(merchantUrl("/receive/mobilemoney"), {
      method: "POST",
      headers: authHeaders(),
      body: JSON.stringify({
        CustomerName: args.customerName,
        CustomerEmail: "none@hubtel.invalid", // required by some builds; opaque to the payer
        CustomerMsisdn: args.customerMsisdn,
        Amount: Number(args.amount.toFixed(2)),
        Channel: args.channel,
        Description: args.description,
        ClientReference: args.clientReference,
        PrimaryCallbackURL: args.callbackUrl,
        ...(args.secondaryCallbackUrl ? { SecondaryCallbackURL: args.secondaryCallbackUrl } : {}),
      }),
    });
    if (isSuccess(status, json))
      return { ok: true, data: { transactionId: json?.TransactionId ?? json?.data?.TransactionId ?? null } };
    return { ok: false, error: json?.Message || json?.ResponseMessage || json?.message || `Hubtel HTTP ${status}` };
  } catch (e: any) {
    return { ok: false, error: `Hubtel request failed: ${e?.name === "TimeoutError" ? "timeout" : e?.message || "network error"}` };
  }
}

// ── Online checkout (hosted payment page → shareable link) ─────
export type CheckoutItem = { name: string; quantity: number; unitPrice: number; totalPrice: number };

export async function hubtelCreateCheckout(opts: {
  description: string;
  items: CheckoutItem[];
  totalAmount: number;
  callbackUrl: string;
  returnUrl: string;
  cancellationUrl: string;
  payeeName?: string;
  payeeMsisdn?: string;
  clientReference?: string;
}): Promise<HubtelResult<{ token: string | null; checkoutUrl: string | null }>> {
  try {
    const { status, json } = await hubtelFetch(`${apiBase()}/v1/merchantaccount/onlinecheckout/invoice/create`, {
      method: "POST",
      headers: authHeaders(),
      body: JSON.stringify({
        invoice: {
          items: opts.items.map((i) => ({ name: i.name, quantity: i.quantity, unitPrice: i.unitPrice, totalPrice: i.totalPrice })),
          totalAmount: Number(opts.totalAmount.toFixed(2)),
          description: opts.description,
        },
        store: { name: "School Fees", ...(opts.payeeMsisdn ? { phone1: opts.payeeMsisdn } : {}) },
        actions: {
          cancel_url: opts.cancellationUrl,
          return_url: opts.returnUrl,
          callback_url: opts.callbackUrl,
        },
        custom_data: { clientReference: opts.clientReference ?? "" },
      }),
    });
    const data = json?.data ?? json?.Data ?? {};
    const token = data?.token ?? data?.Token ?? data?.checkoutId ?? null;
    const url = data?.checkoutUrl ?? data?.url ?? data?.Url ?? (token ? `https://checkout.hubtel.com/pay/${token}` : null);
    if (isSuccess(status, json) && (token || url)) return { ok: true, data: { token: token ? String(token) : null, checkoutUrl: url ? String(url) : null } };
    return { ok: false, error: json?.message || json?.Message || json?.ResponseMessage || `Hubtel HTTP ${status}` };
  } catch (e: any) {
    return { ok: false, error: `Hubtel request failed: ${e?.name === "TimeoutError" ? "timeout" : e?.message || "network error"}` };
  }
}

/** Checkout invoice status → normalised PENDING | COMPLETED | CANCELLED. */
export async function hubtelCheckoutStatus(token: string): Promise<HubtelResult<{ status: "PENDING" | "COMPLETED" | "CANCELLED"; raw: string }>> {
  try {
    const { status, json } = await hubtelFetch(
      `${apiBase()}/v1/merchantaccount/onlinecheckout/invoice/status/${encodeURIComponent(token)}`,
      { method: "GET", headers: authHeaders() },
    );
    if (status >= 400) return { ok: false, error: `Hubtel HTTP ${status}` };
    const st = String(json?.status ?? json?.Status ?? json?.data?.status ?? json?.Data?.Status ?? "").toLowerCase();
    const data = json?.data ?? json?.Data ?? {};
    if (st === "paid" || String(data?.amountPaid ?? "") !== "" && Number(data?.amountPaid) > 0) return { ok: true, data: { status: "COMPLETED", raw: st || "paid" } };
    if (st.includes("cancel")) return { ok: true, data: { status: "CANCELLED", raw: st } };
    return { ok: true, data: { status: "PENDING", raw: st || "unpaid" } };
  } catch (e: any) {
    return { ok: false, error: `Hubtel request failed: ${e?.name === "TimeoutError" ? "timeout" : e?.message || "network error"}` };
  }
}

/** Transaction status (receive-money / send-money) —
 *  GET …/transactions/status?TransactionId=…&ClientReference=… */
export async function hubtelTransactionStatus(opts: { transactionId?: string | null; clientReference?: string | null }): Promise<HubtelResult<{ status: "PENDING" | "COMPLETED" | "FAILED"; raw: string }>> {
  try {
    const qs = new URLSearchParams();
    if (opts.transactionId) qs.set("TransactionId", opts.transactionId);
    if (opts.clientReference) qs.set("ClientReference", opts.clientReference);
    const { status, json } = await hubtelFetch(`${merchantUrl("/transactions/status")}?${qs.toString()}`, {
      method: "GET",
      headers: authHeaders(),
    });
    if (status >= 400) return { ok: false, error: `Hubtel HTTP ${status}` };
    // common shapes: {Transaction:{Status}} | {data:{Status}} | {Status}
    const t = json?.Transaction ?? json?.transaction ?? json?.data ?? json?.Data ?? json ?? {};
    const st = String(t?.Status ?? t?.status ?? "").toLowerCase();
    const rc = String(json?.ResponseCode ?? t?.ResponseCode ?? "");
    if (rc === "0000" || rc === "00" || st === "success" || st.includes("paid")) return { ok: true, data: { status: "COMPLETED", raw: st || rc } };
    if (["failed", "error", "declined", "insufficient", "timeout", "expired", "reversed"].some((k) => st.includes(k)) || ["01", "02", "05"].includes(rc))
      return { ok: true, data: { status: "FAILED", raw: st || rc } };
    return { ok: true, data: { status: "PENDING", raw: st || "pending" } };
  } catch (e: any) {
    return { ok: false, error: `Hubtel request failed: ${e?.name === "TimeoutError" ? "timeout" : e?.message || "network error"}` };
  }
}

// ── Send money (disbursement) & refund — reserved for future waves ──
export async function hubtelSendMoney(opts: {
  recipientName: string; recipientMsisdn: string; amount: number; description: string; clientReference: string; callbackUrl?: string;
}): Promise<HubtelResult<{ transactionId: string | null }>> {
  try {
    const { status, json } = await hubtelFetch(merchantUrl("/send/mobilemoney"), {
      method: "POST",
      headers: authHeaders(),
      body: JSON.stringify({
        RecipientName: opts.recipientName,
        RecipientMsisdn: opts.recipientMsisdn,
        Amount: Number(opts.amount.toFixed(2)),
        Description: opts.description,
        ClientReference: opts.clientReference,
        ...(opts.callbackUrl ? { PrimaryCallbackURL: opts.callbackUrl } : {}),
      }),
    });
    if (isSuccess(status, json)) return { ok: true, data: { transactionId: json?.TransactionId ?? json?.data?.TransactionId ?? null } };
    return { ok: false, error: json?.Message || json?.message || `Hubtel HTTP ${status}` };
  } catch (e: any) {
    return { ok: false, error: `Hubtel request failed: ${e?.name === "TimeoutError" ? "timeout" : e?.message || "network error"}` };
  }
}

export async function hubtelRefund(opts: { transactionId: string; amount: number; description?: string; clientReference?: string }): Promise<HubtelResult> {
  try {
    const { status, json } = await hubtelFetch(merchantUrl("/transactions/refund"), {
      method: "POST",
      headers: authHeaders(),
      body: JSON.stringify({
        TransactionId: opts.transactionId,
        Amount: Number(opts.amount.toFixed(2)),
        Description: opts.description ?? "Refund",
        ClientReference: opts.clientReference ?? `RF-${Date.now()}`,
      }),
    });
    if (isSuccess(status, json)) return { ok: true, data: json };
    return { ok: false, error: json?.Message || json?.message || `Hubtel HTTP ${status}` };
  } catch (e: any) {
    return { ok: false, error: `Hubtel request failed: ${e?.name === "TimeoutError" ? "timeout" : e?.message || "network error"}` };
  }
}

// ── URL builders for webhooks / redirects ──────────────────────
export function hubtelCallbackBase(): string {
  return (process.env.HUBTEL_CALLBACK_BASE || process.env.PUBLIC_APP_BASE_URL || "").replace(/\/$/, "");
}

export function hubtelWebhookUrl(): string {
  const base = hubtelCallbackBase();
  const secret = process.env.HUBTEL_WEBHOOK_SECRET;
  return base ? `${base}/api/webhooks/hubtel${secret ? `?secret=${encodeURIComponent(secret)}` : ""}` : "";
}

/** A short, unique, Hubtel-safe client reference (≤ 32 chars). */
export function newClientReference(): string {
  const d = new Date();
  const stamp = `${d.getFullYear().toString().slice(2)}${String(d.getMonth() + 1).padStart(2, "0")}${String(d.getDate()).padStart(2, "0")}`;
  const rand = Math.random().toString(36).slice(2, 8).toUpperCase();
  return `HTL-${stamp}-${rand}`; // 4+1+6+1+6 = 18 chars
}

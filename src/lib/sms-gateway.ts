// ── SMS gateway clients (ARKESEL / TERMII) ────────────────────
// Real-delivery adapters behind the provider-agnostic sendSms helper.
// Credentials are deployment-level env vars (never stored in the DB):
//   SMS_ARKESEL_API_KEY  — Arkesel v2 API key   (https://arkesel.com)
//   SMS_TERMII_API_KEY   — Termii API key       (https://termii.com)
// Optional base-URL overrides exist for integration testing:
//   SMS_ARKESEL_BASE_URL / SMS_TERMII_BASE_URL
// Credit accounting stays in-app: one successful send debits one credit.

export type GatewaySendResult = {
  ok: boolean;
  /** gateway message id, when the provider returns one */
  ref?: string | null;
  /** human-readable failure reason */
  error?: string;
};

/** Normalise a Ghana phone number to international format without '+'.
 *  "0244123456" | "+233244123456" | "233244123456" → "233244123456".
 *  Returns null when the number is not a valid Ghana MSISDN. */
export function toGhanaIntl(phone: string): string | null {
  const p = (phone ?? "").replace(/[\s-]/g, "");
  if (/^0\d{9}$/.test(p)) return `233${p.slice(1)}`;
  if (/^\+233\d{9}$/.test(p)) return p.slice(1);
  if (/^233\d{9}$/.test(p)) return p;
  return null;
}

const TIMEOUT_MS = 15_000;

async function postJson(url: string, headers: Record<string, string>, body: unknown): Promise<{ status: number; json: any }> {
  const res = await fetch(url, {
    method: "POST",
    headers: { "content-type": "application/json", ...headers },
    body: JSON.stringify(body),
    signal: AbortSignal.timeout(TIMEOUT_MS),
  });
  const json = await res.json().catch(() => ({}));
  return { status: res.status, json };
}

/** Arkesel v2 — POST {BASE}/api/v2/sms/send, header `api-key`.
 *  Body: { sender, message, recipients: ["233…"] }.
 *  Success: HTTP 200 with status "success" (docs variants: "ok"), data[0].id. */
export async function arkeselSend(opts: { apiKey: string; senderId: string; message: string; recipient: string }): Promise<GatewaySendResult> {
  const base = (process.env.SMS_ARKESEL_BASE_URL || "https://sms.arkesel.com").replace(/\/$/, "");
  try {
    const { status, json } = await postJson(`${base}/api/v2/sms/send`, { "api-key": opts.apiKey }, {
      sender: opts.senderId,
      message: opts.message,
      recipients: [opts.recipient],
    });
    const okStatus = json?.status === "success" || json?.code === "ok" || String(json?.message ?? "").toLowerCase().includes("success");
    if (status === 200 && okStatus) return { ok: true, ref: json?.data?.[0]?.id ?? json?.data?.[0]?.message_id ?? null };
    return { ok: false, error: json?.message || json?.error || `Arkesel HTTP ${status}` };
  } catch (e: any) {
    return { ok: false, error: `Arkesel request failed: ${e?.name === "TimeoutError" ? "timeout" : e?.message || "network error"}` };
  }
}

/** Termii — POST {BASE}/api/sms/send, api_key in the body.
 *  Body: { api_key, to, from, sms, type: "plain", channel: "generic" }.
 *  Success: HTTP 200 with message_id / "Successfully Sent". */
export async function termiiSend(opts: { apiKey: string; senderId: string; message: string; recipient: string }): Promise<GatewaySendResult> {
  const base = (process.env.SMS_TERMII_BASE_URL || "https://api.ng.termii.com").replace(/\/$/, "");
  try {
    const { status, json } = await postJson(`${base}/api/sms/send`, {}, {
      api_key: opts.apiKey,
      to: opts.recipient,
      from: opts.senderId,
      sms: opts.message,
      type: "plain",
      channel: "generic",
    });
    const okStatus = !!json?.message_id || String(json?.message ?? "").toLowerCase().includes("success");
    if (status === 200 && okStatus) return { ok: true, ref: json?.message_id ?? null };
    return { ok: false, error: json?.message || `Termii HTTP ${status}` };
  } catch (e: any) {
    return { ok: false, error: `Termii request failed: ${e?.name === "TimeoutError" ? "timeout" : e?.message || "network error"}` };
  }
}

/** Which gateways have credentials configured on this deployment (for UI status). */
export function gatewayConfigStatus() {
  return {
    arkesel: !!process.env.SMS_ARKESEL_API_KEY,
    termii: !!process.env.SMS_TERMII_API_KEY,
  };
}

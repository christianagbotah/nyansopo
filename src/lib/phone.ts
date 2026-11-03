// ── Ghana phone-number helpers (shared by SMS gateways & Hubtel) ──

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

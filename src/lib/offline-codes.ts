"use client";

/* ── Offline code reserve — the client half of the receipt lease (wave 41) ──
 *
 * While online, this workstation RESERVES a block of real receipt numbers
 * (POST /api/sync {action:"lease"}) — the server's atomic RCP counter jumps
 * past the whole block, so those numbers belong to this device alone: no
 * online clerk and no other device can ever mint one of them. While OFFLINE,
 * a collection takes the next number from the reserve and prints the final
 * RCP-YYYY-NNNNN code on the parent's receipt at once. The queued op carries
 * the preassigned code + lease tag; at flush the server validates the number
 * really fell inside a block granted to THIS device before adopting it (a
 * unique receiptNo constraint is the final backstop, so duplication is
 * impossible even in the pathological cases).
 *
 * The reserve is per-browser (localStorage), named to the device the same
 * way the flush identifies itself. When the block runs dry (or the calendar
 * year rolls over) the POS falls back to the queue-then-mint flow: the
 * receipt prints an OFFLINE-xx provisional label and the server mints the
 * permanent code at flush — still never a duplicate, just not pre-printed.
 */

const KEY = "nyansapo.lease";

export type ReceiptLease = { tag: string; kind: "RCP"; year: number; from: number; to: number; size?: number };
type StoredLease = ReceiptLease & { cursor: number }; // cursor = next unspent sequence number

function read(): StoredLease | null {
  if (typeof window === "undefined") return null;
  try {
    const raw = localStorage.getItem(KEY);
    if (!raw) return null;
    const l = JSON.parse(raw) as StoredLease;
    if (!l || typeof l.tag !== "string" || typeof l.from !== "number" || typeof l.to !== "number" || typeof l.cursor !== "number") return null;
    return l;
  } catch {
    return null;
  }
}

function write(l: StoredLease | null): void {
  if (typeof window === "undefined") return;
  try {
    if (l) localStorage.setItem(KEY, JSON.stringify(l));
    else localStorage.removeItem(KEY);
  } catch { /* the reserve is best-effort storage */ }
}

/** Adopt a freshly granted block (flush replenishment or an explicit
 *  reserve) — replaces the current reserve; spending starts at `from`. */
export function adoptLease(lease: ReceiptLease): void {
  write({ ...lease, cursor: lease.from });
}

export function leaseInfo(): (ReceiptLease & { cursor: number; remaining: number }) | null {
  const l = read();
  if (!l) return null;
  if (l.year !== new Date().getFullYear()) {
    write(null); // stale across new-year — receipts restart at 0001 per year
    return null;
  }
  return { ...l, remaining: Math.max(0, l.to - l.cursor + 1) };
}

/** Take the next REAL receipt number from this device's reserve — the
 *  caller prints it immediately and queues it with the op. Returns null
 *  when there is no reserve (or it is exhausted / expired): the caller
 *  falls back to the provisional OFFLINE-xx label. Numbers burn in order;
 *  an abandoned number is a harmless gap, never a duplicate. */
export function nextOfflineReceiptNo(): { code: string; tag: string; seq: number; year: number } | null {
  const l = read();
  if (!l) return null;
  if (l.year !== new Date().getFullYear()) {
    write(null);
    return null;
  }
  if (l.cursor > l.to) return null;
  const seq = l.cursor;
  write({ ...l, cursor: seq + 1 });
  return { code: `RCP-${l.year}-${String(seq).padStart(5, "0")}`, tag: l.tag, seq, year: l.year };
}

/** Reserve a block of real receipt numbers for this device (Sync Center
 *  "Reserve numbers", or ahead of a network-dead field trip / sports day).
 *  Server-side the atomic counter jumps past the whole block — see
 *  leaseSeqBlock in src/lib/codes.ts. Returns the adopted lease or null. */
export async function reserveReceiptBlock(size = 50): Promise<ReceiptLease | null> {
  try {
    const { useStore } = await import("@/lib/store");
    const uid = useStore.getState().session?.id;
    let deviceCode: string | undefined;
    try { deviceCode = JSON.parse(localStorage.getItem("nyansapo.device") ?? "{}").code || undefined; } catch { /* ignore */ }
    const res = await fetch("/api/sync", {
      method: "POST",
      headers: { "content-type": "application/json", ...(uid ? { "x-user-id": uid } : {}) },
      body: JSON.stringify({
        action: "lease",
        schoolId: useStore.getState().currentSchoolId ?? useStore.getState().session?.schoolId ?? undefined,
        size,
        device: { code: deviceCode, name: `${navigator.userAgent.includes("Android") ? "Android" : "Workstation"} browser`, kind: "BROWSER" },
      }),
    });
    const body = await res.json().catch(() => ({}));
    if (!res.ok || !body?.lease) return null;
    const lease = body.lease as ReceiptLease;
    if (body.deviceCode && body.deviceCode !== deviceCode) {
      try { localStorage.setItem("nyansapo.device", JSON.stringify({ code: body.deviceCode })); } catch { /* ignore */ }
    }
    adoptLease(lease);
    return lease;
  } catch {
    return null;
  }
}

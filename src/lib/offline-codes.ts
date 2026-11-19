"use client";

/* ── Offline code reserve — the client half of the code lease (waves 41+42) ──
 *
 * While online, this workstation RESERVES a block of real codes — receipt
 * numbers (RCP), invoice numbers (INV) and national student indexes (NYIDX,
 * per birth-year cohort) — via POST /api/sync {action:"lease"}. The server's
 * atomic counter for that kind jumps past the whole block, so those numbers
 * belong to this device alone: no online clerk and no other device can ever
 * mint one of them. While OFFLINE, a collection or enrolment takes the next
 * number from the matching reserve and prints the FINAL code at once — the
 * parent's receipt, the bill, the learner's index card. The queued op carries
 * the preassigned code + lease tag; at flush the server validates the code
 * really fell inside a block granted to THIS device (in the code's own year)
 * before adopting it — a unique constraint per code column is the final
 * backstop, so duplication is impossible even in the pathological cases.
 *
 * The reserves are per-browser (localStorage), one slot per kind, named to
 * the device the same way the flush identifies itself. When a block runs dry
 * (or the calendar year rolls over) the screen falls back to the queue-then-
 * mint flow: the document shows a provisional OFFLINE-xx label and the server
 * mints the permanent code at flush — still never a duplicate, just not
 * pre-printed.
 */

export type LeaseKind = "RCP" | "INV" | "NYIDX";
export const LEASE_KINDS: LeaseKind[] = ["RCP", "INV", "NYIDX"];

export type CodeLease = { tag: string; kind: LeaseKind; year: number; from: number; to: number; size?: number };
type StoredLease = CodeLease & { cursor: number }; // cursor = next unspent sequence number

/** Display metadata for each reserve kind — used by the Sync Center tab. */
export const LEASE_KIND_META: Record<LeaseKind, { label: string; sample: string; hint: string }> = {
  RCP: { label: "Receipt numbers", sample: "RCP-2026-00749", hint: "Payments collected offline — canteen, fees, transport, POS." },
  INV: { label: "Invoice numbers", sample: "INV-2026-0006", hint: "Bills raised offline — first-term billing at enrolment." },
  NYIDX: { label: "Student index cohorts", sample: "NY-2016-000123", hint: "Learners enrolled offline — leased per birth-year cohort." },
};

const KEY = (kind: LeaseKind) => `nyansapo.lease.${kind}`;
const LEGACY_KEY = "nyansapo.lease"; // wave-41 single-receipt slot — migrated on first read

/** Canonical string form of a sequence number for each kind — mirrors
 *  formatCode() in /api/sync and the mint helpers in src/lib/codes.ts. */
export function formatOfflineCode(kind: LeaseKind, year: number, seq: number): string {
  if (kind === "INV") return `INV-${year}-${String(seq).padStart(4, "0")}`;
  if (kind === "NYIDX") return `NY-${year}-${String(seq).padStart(6, "0")}`;
  return `RCP-${year}-${String(seq).padStart(5, "0")}`;
}

function read(kind: LeaseKind): StoredLease | null {
  if (typeof window === "undefined") return null;
  try {
    let raw = localStorage.getItem(KEY(kind));
    if (!raw && kind === "RCP") {
      // wave 41 → 42 migration: the old single-receipt slot becomes the RCP slot
      raw = localStorage.getItem(LEGACY_KEY);
      if (raw) {
        try { localStorage.setItem(KEY("RCP"), raw); localStorage.removeItem(LEGACY_KEY); } catch { /* best-effort */ }
      }
    }
    if (!raw) return null;
    const l = JSON.parse(raw) as StoredLease;
    if (!l || typeof l.tag !== "string" || typeof l.from !== "number" || typeof l.to !== "number" || typeof l.cursor !== "number") return null;
    return l;
  } catch {
    return null;
  }
}

function write(kind: LeaseKind, l: StoredLease | null): void {
  if (typeof window === "undefined") return;
  try {
    if (l) localStorage.setItem(KEY(kind), JSON.stringify(l));
    else localStorage.removeItem(KEY(kind));
  } catch { /* the reserve is best-effort storage */ }
}

/** Adopt a freshly granted block (flush replenishment or an explicit
 *  reserve) — replaces the current block for that kind; spending starts
 *  at `from`. Unknown kinds are ignored (older server). */
export function adoptLease(lease: CodeLease): void {
  if (!lease || !LEASE_KINDS.includes(lease.kind)) return;
  write(lease.kind, { ...lease, cursor: lease.from });
}

export function leaseInfo(kind: LeaseKind = "RCP"): (CodeLease & { cursor: number; remaining: number }) | null {
  const l = read(kind);
  if (!l) return null;
  if (l.year !== new Date().getFullYear() && kind !== "NYIDX") {
    write(kind, null); // stale across new-year — codes restart at 0001 per year
    return null;
  }
  return { ...l, remaining: Math.max(0, l.to - l.cursor + 1) };
}

/** Take the next REAL code from this device's reserve of `kind` — the caller
 *  prints it immediately and queues it with the op. Returns null when there
 *  is no reserve (or it is exhausted / expired): the caller falls back to the
 *  provisional OFFLINE-xx label. Numbers burn in order; an abandoned number
 *  is a harmless gap, never a duplicate. NYIDX reserves are per birth-year
 *  cohort — pass `year` to draw from a specific cohort's block. */
export function nextOfflineCode(kind: LeaseKind, year?: number): { code: string; tag: string; seq: number; year: number } | null {
  const l = read(kind);
  if (!l) return null;
  if (year !== undefined && l.year !== year) return null; // wrong cohort / scope
  if (kind !== "NYIDX" && l.year !== new Date().getFullYear()) {
    write(kind, null);
    return null;
  }
  if (l.cursor > l.to) return null;
  const seq = l.cursor;
  write(kind, { ...l, cursor: seq + 1 });
  return { code: formatOfflineCode(kind, l.year, seq), tag: l.tag, seq, year: l.year };
}

/** Next real receipt number (RCP-YYYY-NNNNN) from this device's reserve. */
export function nextOfflineReceiptNo(): { code: string; tag: string; seq: number; year: number } | null {
  return nextOfflineCode("RCP");
}

/** Next real invoice number (INV-YYYY-NNNN) from this device's reserve. */
export function nextOfflineInvoiceNo(): { code: string; tag: string; seq: number; year: number } | null {
  return nextOfflineCode("INV");
}

/** Next real student index (NY-<birthYear>-NNNNNN) from this device's
 *  cohort reserve — null unless the device holds a block for that cohort. */
export function nextOfflineStudentIndex(birthYear: number): { code: string; tag: string; seq: number; year: number } | null {
  return nextOfflineCode("NYIDX", birthYear);
}

/** Reserve a block of real codes for this device (Sync Center "Reserve
 *  numbers", or ahead of a network-dead field trip / sports day). Kind picks
 *  the counter: receipts, invoices or student-index cohorts (NYIDX takes a
 *  birth-year `year`; the default is the current calendar year). Server-side
 *  the atomic counter jumps past the whole block — see leaseSeqBlock in
 *  src/lib/codes.ts. Returns the adopted lease or null. */
export async function reserveCodeBlock(kind: LeaseKind = "RCP", size = 50, year?: number): Promise<CodeLease | null> {
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
        kind,
        ...(kind === "NYIDX" && year ? { year } : {}),
        schoolId: useStore.getState().currentSchoolId ?? useStore.getState().session?.schoolId ?? undefined,
        size,
        device: { code: deviceCode, name: `${navigator.userAgent.includes("Android") ? "Android" : "Workstation"} browser`, kind: "BROWSER" },
      }),
    });
    const body = await res.json().catch(() => ({}));
    if (!res.ok || !body?.lease) return null;
    const lease = body.lease as CodeLease;
    if (body.deviceCode && body.deviceCode !== deviceCode) {
      try { localStorage.setItem("nyansapo.device", JSON.stringify({ code: body.deviceCode })); } catch { /* ignore */ }
    }
    adoptLease(lease);
    return lease;
  } catch {
    return null;
  }
}

/** Wave-41 compatibility alias — reserve receipt numbers. */
export async function reserveReceiptBlock(size = 50): Promise<CodeLease | null> {
  return reserveCodeBlock("RCP", size);
}

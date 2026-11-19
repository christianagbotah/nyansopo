"use client";

/* ── Offline Data Pack — the read side of Nyansapo sync (wave 40) ────────────
 *
 * Own architecture. The legacy CI3 family synced READS with full-table
 * dumps between WAMP servers and the cloud (location_registry +
 * sync_table_management row shipping). That model doesn't fit a PWA whose
 * offline truth is already the localStorage action queue: what an offline
 * device actually needs is the *last server response* of the screens it
 * works from. So the pack is a per-user snapshot of exactly those GET
 * responses:
 *
 *   · every cacheable screen fetch stores its response under the full
 *     request path (paths carry the school id → per-school isolation)
 *   · the pack is namespaced to the signed-in user — a shared workstation
 *     never serves one user's data to another
 *   · when a fetch cannot run (offline), the pack serves the newest
 *     section; for date-parameterised screens (attendance register, POS
 *     daily) a missing date falls back to the newest sibling section of
 *     the same screen — the roster survives, balances/mark statuses are
 *     the last-seen ones
 *   · reconnecting refreshes every section (warm) and journals a PULL
 *     SyncEvent — the read-side twin of the flush journal
 *
 * Writes never touch the pack: they queue in the action queue and flush
 * through /api/sync exactly as before. The pack is best-effort storage —
 * LRU-capped, quota-pruned, and never allowed to break the caller.
 */

import { useStore } from "@/lib/store";

const KEY = "nyansapo.pack";
const MAX_SECTIONS = 60; // LRU cap — a class register per day adds up

export type PackEntry = { at: number; data: any };
type PackFile = { v: 1; uid: string | null; sections: Record<string, PackEntry> };

const EMPTY: PackFile = { v: 1, uid: null, sections: {} };

/** Load the pack, resetting it when another user signed in on this browser. */
function readPack(): PackFile {
  if (typeof window === "undefined") return { ...EMPTY, sections: {} };
  const uid = useStore.getState().session?.id ?? null;
  try {
    const raw = localStorage.getItem(KEY);
    if (!raw) return { v: 1, uid, sections: {} };
    const parsed = JSON.parse(raw) as PackFile;
    if (!parsed || parsed.v !== 1 || typeof parsed.sections !== "object") return { v: 1, uid, sections: {} };
    if (parsed.uid !== uid) return { v: 1, uid, sections: {} }; // other user — start fresh
    return parsed;
  } catch {
    return { v: 1, uid, sections: {} };
  }
}

/** Persist with an LRU cap and a quota escape: drop oldest thirds until it fits. */
function persist(pack: PackFile): boolean {
  if (typeof window === "undefined") return false;
  const keys = Object.keys(pack.sections).sort((a, b) => pack.sections[a].at - pack.sections[b].at);
  while (keys.length > MAX_SECTIONS) {
    delete pack.sections[keys.shift() as string];
  }
  try {
    localStorage.setItem(KEY, JSON.stringify(pack));
    return true;
  } catch {
    while (keys.length) {
      keys.splice(0, Math.max(1, Math.floor(keys.length / 3))).forEach((k) => delete pack.sections[k]);
      try {
        localStorage.setItem(KEY, JSON.stringify(pack));
        return true;
      } catch { /* keep pruning */ }
    }
    return false;
  }
}

/** Store a successful GET response. Returns the timestamp it was written at. */
export function packWrite(path: string, data: any): number | null {
  const at = Date.now();
  try {
    const pack = readPack();
    pack.sections[path] = { at, data };
    persist(pack);
    return at;
  } catch {
    return null; // the pack is best-effort — never break the caller
  }
}

/** Normalise a path for sibling matching: same pathname + same query minus `date=`. */
function normSibling(path: string): string {
  const i = path.indexOf("?");
  if (i < 0) return path;
  const pathname = path.slice(0, i);
  const rest = path
    .slice(i + 1)
    .split("&")
    .filter((kv) => kv && !kv.startsWith("date="))
    .sort()
    .join("&");
  return pathname + (rest ? `?${rest}` : "");
}

/**
 * Serve a path from the pack: exact hit first; for date-parameterised screens
 * the newest sibling section (same screen, different day) when today has
 * never been fetched — an offline device opening a fresh day still gets the
 * roster it worked from yesterday.
 */
export function packRead(path: string): PackEntry | null {
  try {
    const pack = readPack();
    const exact = pack.sections[path];
    if (exact) return exact;
    if (!path.includes("date=")) return null;
    const wanted = normSibling(path);
    let best: PackEntry | null = null;
    for (const [k, v] of Object.entries(pack.sections)) {
      if (!k.includes("date=") || normSibling(k) !== wanted) continue;
      if (!best || v.at > best.at) best = v;
    }
    return best;
  } catch {
    return null;
  }
}

/** All cached section paths for the current user (empty for a fresh pack). */
export function packPaths(): string[] {
  try {
    return Object.keys(readPack().sections);
  } catch {
    return [];
  }
}

export type PackSectionInfo = { path: string; label: string; at: number; bytes: number };
export type PackInfo = { sections: PackSectionInfo[]; bytes: number; freshestAt: number | null; oldestAt: number | null };

/** Friendly labels for the screens the pack knows about (Sync Center table). */
const LABELS: [RegExp, string][] = [
  [/\/ops\/attendance\?action=register/, "Attendance register"],
  [/\/ops\/scanner/, "Gate scanner"],
  [/\/ops\/daily/, "POS · daily services"],
  [/\/api\/school\/[^/]+\/students\?/, "Learner roster"],
  [/\/api\/school\/[^/]+\/settings/, "Classrooms & settings"],
  [/\/gradebook/, "Gradebook"],
];

function labelOf(path: string): string {
  for (const [re, label] of LABELS) if (re.test(path)) return label;
  const i = path.indexOf("?");
  const base = (i < 0 ? path : path.slice(0, i)).split("/").filter(Boolean).slice(-2).join("/");
  return base || path;
}

/** Introspection for the Sync Center's Data pack tab (local browser state). */
export function packInfo(): PackInfo {
  try {
    const pack = readPack();
    const sections = Object.entries(pack.sections).map(([path, v]) => ({
      path,
      label: labelOf(path),
      at: v.at,
      bytes: JSON.stringify(v.data ?? null).length,
    }));
    sections.sort((a, b) => b.at - a.at);
    return {
      sections,
      bytes: sections.reduce((s, x) => s + x.bytes, 0),
      freshestAt: sections.length ? sections[0].at : null,
      oldestAt: sections.length ? sections[sections.length - 1].at : null,
    };
  } catch {
    return { sections: [], bytes: 0, freshestAt: null, oldestAt: null };
  }
}

/** Drop the whole pack (Sync Center purge / sign-out on a shared workstation). */
export function packPurge(): void {
  try { localStorage.removeItem(KEY); } catch { /* ignore */ }
}

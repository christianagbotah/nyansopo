"use client";
// ── Offline-aware API client ──────────────────────────────────
import { useStore } from "@/lib/store";
import { packRead, packWrite, packPaths } from "@/lib/offline-pack";

export function getHeaders(): Record<string, string> {
  const uid = useStore.getState().session?.id;
  return { "content-type": "application/json", ...(uid ? { "x-user-id": uid } : {}) };
}

export async function apiGet<T>(path: string): Promise<T> {
  const res = await fetch(path, { headers: getHeaders(), cache: "no-store" });
  if (!res.ok) {
    const body = await res.json().catch(() => ({}));
    throw new Error(body.error ?? `Request failed (${res.status})`);
  }
  return res.json();
}

/** GET whose response feeds the offline data pack (wave 40 — read-side sync).
 *  Online: fetch + store the response for the offline screens. Offline: serve
 *  the newest packed section (same screen, newest date when today was never
 *  fetched). The swap is transparent — call sites treat it as apiGet. */
export async function apiGetCacheable<T>(path: string): Promise<T> {
  const { online } = useStore.getState();
  if (!online) {
    const hit = packRead(path);
    if (hit) {
      useStore.getState().setPackAt(hit.at);
      return hit.data as T;
    }
    throw new Error("offline — this screen has no cached data yet (open it once while online)");
  }
  const data = await apiGet<T>(path);
  const at = packWrite(path, data);
  if (at) useStore.getState().setPackAt(at);
  return data;
}

/** Refresh every packed section while online (reconnect / Sync Center warm).
 *  The read-side twin of flushQueue: writes go up (flush) then data comes
 *  down (warm). Journals a PULL SyncEvent with the device identity so the
 *  Sync Center journal shows both directions of the pipeline. */
export async function warmPack(): Promise<{ warmed: number; durationMs: number } | null> {
  const store = useStore.getState();
  if (!store.online) return null;
  const paths = packPaths();
  if (!paths.length) return null;
  const started = Date.now();
  const settled = await Promise.allSettled(
    paths.map((p) => apiGet(p).then((data) => { packWrite(p, data); }))
  );
  const warmed = settled.filter((r) => r.status === "fulfilled").length;
  const durationMs = Date.now() - started;
  useStore.getState().setPackAt(Date.now());
  // journal the pull (best-effort — a journaling failure never fails the warm)
  try {
    let deviceCode: string | undefined;
    try { deviceCode = JSON.parse(localStorage.getItem("nyansapo.device") ?? "{}").code || undefined; } catch { /* ignore */ }
    await fetch("/api/sync", {
      method: "POST",
      headers: getHeaders(),
      body: JSON.stringify({
        action: "pull",
        schoolId: store.currentSchoolId ?? store.session?.schoolId ?? undefined,
        device: { code: deviceCode, name: deviceName(), kind: "BROWSER" },
        sections: warmed,
        durationMs,
      }),
    });
  } catch { /* ignore */ }
  return { warmed, durationMs };
}

function deviceName(): string {
  if (typeof navigator === "undefined") return "Browser";
  const ua = navigator.userAgent;
  return /Android/i.test(ua) ? "Android browser" : /iPhone|iPad/i.test(ua) ? "iOS browser" : /Firefox/i.test(ua) ? "Firefox" : /Edg\//i.test(ua) ? "Edge" : /Chrome\//i.test(ua) ? "Chrome" : "Browser";
}

export type QueueableKind = "payment" | "attendance" | "marks" | "student";

/** POST that enqueues the op when offline and syncs later. */
export async function apiPostQueueable<T = any>(
  path: string,
  kind: QueueableKind,
  payload: any,
  label: string,
  syncPayload: (payload: any, clientId: string) => any
): Promise<{ queued?: boolean; data?: T }> {
  const { online, enqueue } = useStore.getState();
  if (!online) {
    const clientId = `q-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    enqueue({ clientId, kind, payload: syncPayload(payload, clientId), label, queuedAt: Date.now() });
    return { queued: true };
  }
  const res = await fetch(path, { method: "POST", headers: getHeaders(), body: JSON.stringify(payload) });
  const body = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(body.error ?? `Request failed (${res.status})`);
  return { data: body };
}

export async function apiPost<T = any>(path: string, bodyData?: any): Promise<T> {
  const res = await fetch(path, { method: "POST", headers: getHeaders(), body: JSON.stringify(bodyData ?? {}) });
  const body = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(body.error ?? `Request failed (${res.status})`);
  return body;
}

/** Flush queued offline ops to /api/sync. */
export async function flushQueue(): Promise<{ synced: number; failed: number; conflicts: number; messages: string[] }> {
  const store = useStore.getState();
  const ops = store.queue;
  if (!ops.length || !store.online) return { synced: 0, failed: 0, conflicts: 0, messages: [] };
  store.setSyncing(true);
  try {
    // wave 39: identify this browser as a sync device — the server mints a
    // DEV-0001-style code on the first flush (CI3 register_device parity) and
    // we remember it so the Sync Center can revoke this device later.
    let deviceCode: string | undefined;
    try { deviceCode = JSON.parse(localStorage.getItem("nyansapo.device") ?? "{}").code || undefined; } catch { /* ignore */ }
    const res = await fetch("/api/sync", {
      method: "POST",
      headers: getHeaders(),
      body: JSON.stringify({ ops, device: { code: deviceCode, name: `${deviceName()} — this workstation`, kind: "BROWSER" } }),
    });
    if (res.status === 403) {
      const body = await res.json().catch(() => ({}));
      return { synced: 0, failed: ops.length, conflicts: 0, messages: [body.error ?? "Flush rejected — this device is revoked"] };
    }
    const body = await res.json();
    if (body.deviceCode && body.deviceCode !== deviceCode) {
      try { localStorage.setItem("nyansapo.device", JSON.stringify({ code: body.deviceCode })); } catch { /* ignore */ }
    }
    const okIds = (body.results ?? []).filter((r: any) => r.ok).map((r: any) => r.clientId);
    const failed = (body.results ?? []).filter((r: any) => !r.ok);
    okIds.forEach((id: string) => store.removeFromQueue(id));
    if (!useStore.getState().queue.length) useStore.setState({ lastSyncAt: Date.now() });
    return {
      synced: okIds.length,
      failed: failed.length,
      conflicts: body.conflicts ?? 0,
      messages: failed.map((f: any) => `${f.clientId}: ${f.detail}`),
    };
  } finally {
    useStore.getState().setSyncing(false);
  }
}

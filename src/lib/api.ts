"use client";
// ── Offline-aware API client ──────────────────────────────────
import { useStore } from "@/lib/store";

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
export async function flushQueue(): Promise<{ synced: number; failed: number; messages: string[] }> {
  const store = useStore.getState();
  const ops = store.queue;
  if (!ops.length || !store.online) return { synced: 0, failed: 0, messages: [] };
  store.setSyncing(true);
  try {
    const res = await fetch("/api/sync", { method: "POST", headers: getHeaders(), body: JSON.stringify({ ops }) });
    const body = await res.json();
    const okIds = (body.results ?? []).filter((r: any) => r.ok).map((r: any) => r.clientId);
    const failed = (body.results ?? []).filter((r: any) => !r.ok);
    okIds.forEach((id: string) => store.removeFromQueue(id));
    if (!useStore.getState().queue.length) useStore.setState({ lastSyncAt: Date.now() });
    return {
      synced: okIds.length,
      failed: failed.length,
      messages: failed.map((f: any) => `${f.clientId}: ${f.detail}`),
    };
  } finally {
    useStore.getState().setSyncing(false);
  }
}

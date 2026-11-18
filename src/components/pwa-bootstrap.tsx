"use client";
// Registers the service worker, tracks online/offline, flushes the sync queue,
// refreshes the offline data pack, and surfaces install/offline UI globally.
import { useEffect } from "react";
import { useStore } from "@/lib/store";
import { flushQueue, warmPack } from "@/lib/api";
import { toast } from "@/hooks/use-toast";

export function PwaBootstrap() {
  const session = useStore((s) => s.session);
  const setOnline = useStore((s) => s.setOnline);
  const queue = useStore((s) => s.queue);

  useEffect(() => {
    setOnline(navigator.onLine);
    const onOnline = async () => {
      setOnline(true);
      const st = useStore.getState();
      if (st.session && st.queue.length) {
        const res = await flushQueue();
        if (res.synced) toast({ title: `⟳ Synced ${res.synced} offline action${res.synced > 1 ? "s" : ""}`, description: "Network restored — your queue has been uploaded to the cloud." });
        if (res.conflicts) toast({ title: `${res.conflicts} conflict(s) held for review`, description: "Newer server data exists — an administrator resolves them in the Sync Center." });
        if (res.failed) toast({ title: `${res.failed} action(s) could not sync`, description: res.messages.join(" · "), variant: "destructive" });
      } else {
        toast({ title: "Back online", description: "Nyansapo is connected again." });
      }
      // read-side sync (wave 40): writes went up — now bring the data back
      // down. Warming refreshes every packed section (attendance registers,
      // POS rosters, gradebook sheets) and journals a PULL entry, so the
      // device's offline pack is fresh for the next blackout. Silent: no toast.
      if (useStore.getState().session) warmPack().catch(() => {});
    };
    const onOffline = () => {
      setOnline(false);
      toast({ title: "You are offline", description: "Keep working — attendance, payments, marks and enrolments queue, and the last-loaded rosters and sheets stay readable from the data pack." });
    };
    window.addEventListener("online", onOnline);
    window.addEventListener("offline", onOffline);
    return () => {
      window.removeEventListener("online", onOnline);
      window.removeEventListener("offline", onOffline);
    };
  }, [setOnline]);

  // register service worker (production builds only). In dev the SW's
  // /_next/static/ caching would serve STALE Turbopack chunks — filenames are
  // reused while their content changes on every edit, so an old chunk (e.g.
  // pre-currency-sweep "GH₵" text) hydrates against fresh SSR HTML and crashes
  // with a hydration mismatch. Through the https preview gateway the browser
  // is NOT on localhost, so dev must not rely on the SW's own IS_DEV guard:
  // we skip registration AND heal any SW an earlier visit left behind.
  useEffect(() => {
    if (!("serviceWorker" in navigator)) return;
    if (process.env.NODE_ENV === "production") {
      navigator.serviceWorker.register("/sw.js").catch(() => {});
      return;
    }
    navigator.serviceWorker.getRegistrations().then((rs) => rs.forEach((r) => r.unregister())).catch(() => {});
    if (window.caches) window.caches.keys().then((ks) => ks.forEach((k) => window.caches.delete(k))).catch(() => {});
  }, []);

  // try flushing on load if online with pending ops
  useEffect(() => {
    if (session && navigator.onLine && queue.length) flushQueue();
  }, [session]);

  return null;
}

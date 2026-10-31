"use client";
// Registers the service worker, tracks online/offline, flushes the sync queue,
// and surfaces install/offline UI globally.
import { useEffect } from "react";
import { useStore } from "@/lib/store";
import { flushQueue } from "@/lib/api";
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
        if (res.failed) toast({ title: `${res.failed} action(s) could not sync`, description: res.messages.join(" · "), variant: "destructive" });
      } else {
        toast({ title: "Back online", description: "Nyansapo is connected again." });
      }
    };
    const onOffline = () => {
      setOnline(false);
      toast({ title: "You are offline", description: "Keep working — attendance, payments, marks and enrolments will queue and sync automatically." });
    };
    window.addEventListener("online", onOnline);
    window.addEventListener("offline", onOffline);
    return () => {
      window.removeEventListener("online", onOnline);
      window.removeEventListener("offline", onOffline);
    };
  }, [setOnline]);

  // register service worker (production-safe: network-first, so no stale UI)
  useEffect(() => {
    if ("serviceWorker" in navigator && window.location.protocol === "https:") {
      navigator.serviceWorker.register("/sw.js").catch(() => {});
    } else if ("serviceWorker" in navigator && ["localhost", "127.0.0.1"].includes(window.location.hostname)) {
      navigator.serviceWorker.register("/sw.js").catch(() => {});
    }
  }, []);

  // try flushing on load if online with pending ops
  useEffect(() => {
    if (session && navigator.onLine && queue.length) flushQueue();
  }, [session]);

  return null;
}

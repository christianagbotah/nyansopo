/* Nyansapo service worker — offline-first shell + GET response cache.
   Strategy:
   - Navigation & GET API: network-first, fall back to cache when offline.
   - Static assets: stale-while-revalidate.
   - Writes (POST) are queued client-side (lib/api.ts) and flushed via /api/sync.
*/
const VERSION = "nyansapo-v5";
const SHELL = ["/", "/manifest.webmanifest", "/icon-192.png", "/icon-512.png", "/icon-maskable-512.png", "/apple-touch-icon.png"];

self.addEventListener("install", (e) => {
  e.waitUntil(caches.open(VERSION).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener("activate", (e) => {
  e.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k)))).then(() => self.clients.claim())
  );
});

self.addEventListener("fetch", (event) => {
  const req = event.request;
  if (req.method !== "GET") return; // writes handled by the client sync queue
  const url = new URL(req.url);
  if (url.origin !== location.origin) return;

  // skip Next.js dev assets / HMR
  if (url.pathname.startsWith("/_next/webpack-hmr") || url.pathname.startsWith("/_next/static/")) {
    // static build assets: cache-first (immutable)
    if (url.pathname.startsWith("/_next/static/")) {
      event.respondWith(
        caches.match(req).then((hit) =>
          hit ??
          fetch(req).then((res) => {
            const copy = res.clone();
            caches.open(VERSION).then((c) => c.put(req, copy));
            return res;
          })
        )
      );
    }
    return;
  }

  // navigations & API GETs: network-first with cache fallback
  event.respondWith(
    fetch(req)
      .then((res) => {
        if (res.ok) {
          const copy = res.clone();
          caches.open(VERSION).then((c) => c.put(req, copy));
        }
        return res;
      })
      .catch(() =>
        caches.match(req).then((hit) => {
          if (hit) return hit;
          if (req.mode === "navigate") return caches.match("/");
          return new Response(JSON.stringify({ error: "offline" }), { status: 503, headers: { "content-type": "application/json" } });
        })
      )
  );
});

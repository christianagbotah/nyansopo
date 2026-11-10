/* Nyansapo service worker — offline-first shell + GET response cache.
   Strategy:
   - Navigation & GET API: network-first, fall back to cache when offline.
   - Build assets (/_next/static/): network-first with cache fallback —
     never cache-first (Turbopack reuses dev chunk names; stale chunks
     caused hydration mismatches).
   - Writes (POST) are queued client-side (lib/api.ts) and flushed via /api/sync.
   Registration: production builds only (pwa-bootstrap.tsx unregisters in dev).
*/
const VERSION = "nyansapo-v6";
const SHELL = ["/", "/manifest.webmanifest", "/icon-192.png", "/icon-512.png", "/icon-maskable-512.png", "/apple-touch-icon.png"];

self.addEventListener("install", (e) => {
  e.waitUntil(caches.open(VERSION).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener("activate", (e) => {
  e.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k)))).then(() => self.clients.claim())
  );
});

const IS_DEV = location.hostname === "localhost" || location.hostname === "127.0.0.1";

self.addEventListener("fetch", (event) => {
  const req = event.request;
  if (req.method !== "GET") return; // writes handled by the client sync queue
  const url = new URL(req.url);
  if (url.origin !== location.origin) return;

  // Dev server: never serve from cache — Next.js/Turbopack reuses chunk
  // filenames whose content changes on every edit, so a cached chunk would
  // run STALE CODE (this caused phantom bugs during development).
  if (IS_DEV) return;

  // skip Next.js dev assets / HMR
  if (url.pathname.startsWith("/_next/webpack-hmr")) return;
  if (url.pathname.startsWith("/_next/static/")) {
    // build assets: network-first with cache fallback. In dev, Turbopack reuses
    // chunk filenames whose content changes on every edit; cache-first would
    // run STALE CODE against fresh SSR HTML (hydration mismatches — seen
    // through the https preview gateway where the IS_DEV guard doesn't fire).
    // Production chunks are content-hashed and immutable, so network-first
    // remains correct there (online = one fresh check, offline = cache hit).
    event.respondWith(
      fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(VERSION).then((c) => c.put(req, copy));
          return res;
        })
        .catch(() => caches.match(req))
    );
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

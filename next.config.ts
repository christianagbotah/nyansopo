import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "standalone",
  /* config options here */
  typescript: {
    ignoreBuildErrors: true,
  },
  reactStrictMode: false,
  /* sandbox has 4 GB total RAM — cap Turbopack's heap so the module-graph
     compile never OOM-kills next-server while the verification browser is
     resident alongside it. Verified empirically: at 1024 the first compile
     of the big /api/sync route pushed next-server RSS to ~2.1 GB and the
     kernel OOM-killer SIGKILLed it mid-request (dmesg 2026-05-28). 768
     caps the compiler heap while still fitting the whole route graph;
     keep Postgres + Caddy + control plane headroom above all. */
  experimental: {
    turbopackMemoryLimit: 768,
  },
};

export default nextConfig;

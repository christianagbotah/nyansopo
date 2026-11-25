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
     resident alongside it. Wave 65 raised 768 → 1280: the app-shell grew
     (homework module + portals) and the first compile of / was killed
     repeatedly at 768 (three silent deaths mid-"Compiling /"), while 1280
     completes with ~1 GB still free for Postgres + Caddy + the browser. */
  experimental: {
    turbopackMemoryLimit: 1280,
  },
};

export default nextConfig;

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
     resident alongside it. 1024 keeps next-server near ~1.5 GB RSS, leaving
     ~2 GB of headroom for Postgres, Caddy and the control plane — the OOM
     killer otherwise picks next-server (the largest RSS) at call spikes. */
  experimental: {
    turbopackMemoryLimit: 1024,
  },
};

export default nextConfig;

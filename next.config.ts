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
     resident alongside it */
  experimental: {
    turbopackMemoryLimit: 1792,
  },
};

export default nextConfig;

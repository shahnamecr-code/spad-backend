const withPWA = require("next-pwa");

/** @type {import('next').NextConfig} */

const nextConfig = withPWA({
  pwa: {
    dest: "public",
    // disable: true,
    // process.env.NODE_ENV === "development"
  },
  reactStrictMode: false,
  images: {
    domains: [
      "sitralweb.com",
      "tthost.ir",
      "melketabriz.ir",
      "shahremelk1400.com",
      "lara-melk.ir",
      "panel.lara-melk.ir",
      "127.0.0.1",
      "panel.moshaveranmelk.ir",
    ],
  },
  output: "standalone"
});

module.exports = nextConfig;

/* eslint-disable @typescript-eslint/no-require-imports */
// Generate Nyansapo PWA icons with sharp
const sharp = require("sharp");
const path = require("path");

async function makeIcon(size, file, maskable = false) {
  const pad = maskable ? Math.round(size * 0.18) : 0;
  const inner = size - pad * 2;
  const svg = `
  <svg width="${size}" height="${size}" xmlns="http://www.w3.org/2000/svg">
    <defs>
      <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0" stop-color="#0e5f3f"/>
        <stop offset="1" stop-color="#0a3f2a"/>
      </linearGradient>
    </defs>
    <rect width="${size}" height="${size}" rx="${maskable ? 0 : Math.round(size * 0.16)}" fill="url(#bg)"/>
    ${maskable ? "" : `<rect x="2" y="2" width="${size - 4}" height="${size - 4}" rx="${Math.round(size * 0.14)}" fill="none" stroke="#d9a521" stroke-width="${Math.max(2, size * 0.02)}"/>`}
    <g transform="translate(${pad} ${pad})">
      <path d="M ${inner * 0.5} ${inner * 0.1} C ${inner * 0.18} ${inner * 0.28}, ${inner * 0.18} ${inner * 0.72}, ${inner * 0.5} ${inner * 0.9} C ${inner * 0.82} ${inner * 0.72}, ${inner * 0.82} ${inner * 0.28}, ${inner * 0.5} ${inner * 0.1} Z"
        fill="none" stroke="#d9a521" stroke-width="${Math.max(3, inner * 0.055)}" stroke-linecap="round"/>
      <path d="M ${inner * 0.5} ${inner * 0.26} C ${inner * 0.34} ${inner * 0.38}, ${inner * 0.34} ${inner * 0.62}, ${inner * 0.5} ${inner * 0.74} C ${inner * 0.66} ${inner * 0.62}, ${inner * 0.66} ${inner * 0.38}, ${inner * 0.5} ${inner * 0.26} Z"
        fill="none" stroke="#f5e6c4" stroke-width="${Math.max(2.5, inner * 0.045)}" stroke-linecap="round"/>
      <circle cx="${inner * 0.5}" cy="${inner * 0.5}" r="${inner * 0.07}" fill="#d9a521"/>
    </g>
  </svg>`;
  await sharp(Buffer.from(svg)).png().toFile(path.join(__dirname, "..", "public", file));
  console.log("✓", file);
}

(async () => {
  await makeIcon(192, "icon-192.png");
  await makeIcon(512, "icon-512.png");
  await makeIcon(512, "icon-maskable-512.png", true);
  await makeIcon(180, "apple-touch-icon.png");
})();

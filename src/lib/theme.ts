/* ── School theming (CI3 app_theme / theme_settings parity) ──
   iEduPro stores theme_primary / theme_secondary / theme_accent in the
   settings table and injects a <style> block that paints the top bar,
   sidebar, panel headings, modal headers and buttons with
   linear-gradient(135deg, primary → secondary) plus accent hovers
   (Admin.php theme_settings, header.php 166–232).

   Next.js port: the school's theme lives in SchoolSetting["theme"] as
   { name, primary, secondary, accent } and is applied by setting CSS
   custom properties on <html> — the shadcn token bridge then re-themes
   buttons, rings, charts, sidebar and the gradient headers defined in
   globals.css. No reload needed (CI3 needed one). ── */

export type SchoolTheme = {
  name: string;
  primary: string;
  secondary: string;
  accent: string;
};

/** CI3 theme_settings.php presets (names + colours copied 1:1) plus the
    Nyansapo brand as the factory default. */
export const THEME_PRESETS: { key: string; name: string; primary: string; secondary: string; accent: string }[] = [
  { key: "nyansapo", name: "Nyansapo Green", primary: "#0e5f3f", secondary: "#1b4d38", accent: "#d9a521" },
  { key: "default", name: "Default Blue", primary: "#667eea", secondary: "#764ba2", accent: "#f093fb" },
  { key: "ocean", name: "Ocean Blue", primary: "#2E3192", secondary: "#1BFFFF", accent: "#00d4ff" },
  { key: "sunset", name: "Sunset Orange", primary: "#f12711", secondary: "#f5af19", accent: "#ff6b6b" },
  { key: "forest", name: "Forest Green", primary: "#134E5E", secondary: "#71B280", accent: "#38ef7d" },
  { key: "purple", name: "Royal Purple", primary: "#5f27cd", secondary: "#341f97", accent: "#a29bfe" },
  { key: "crimson", name: "Crimson Red", primary: "#c0392b", secondary: "#e74c3c", accent: "#ff7979" },
  { key: "teal", name: "Teal Mint", primary: "#16a085", secondary: "#1abc9c", accent: "#48c9b0" },
  { key: "midnight", name: "Midnight Blue", primary: "#2c3e50", secondary: "#34495e", accent: "#3498db" },
];

export const DEFAULT_THEME: SchoolTheme = THEME_PRESETS[0];

const HEX_RE = /^#([0-9a-f]{6})$/i;

export function isHexColor(v: unknown): v is string {
  return typeof v === "string" && HEX_RE.test(v.trim());
}

/** hex → [h(0-360), s%, l%] for the shadcn HSL token bridge */
export function hexToHsl(hex: string): [number, number, number] {
  let r = parseInt(hex.slice(1, 3), 16) / 255;
  let g = parseInt(hex.slice(3, 5), 16) / 255;
  let b = parseInt(hex.slice(5, 7), 16) / 255;
  const max = Math.max(r, g, b), min = Math.min(r, g, b);
  const l = (max + min) / 2;
  let h = 0, s = 0;
  if (max !== min) {
    const d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    if (max === r) h = ((g - b) / d + (g < b ? 6 : 0)) * 60;
    else if (max === g) h = ((b - r) / d + 2) * 60;
    else h = ((r - g) / d + 4) * 60;
  }
  return [Math.round(h), Math.round(s * 100), Math.round(l * 100)];
}

/** relative luminance — picks readable foreground (CI3 uses white on gradients) */
function luminance(hex: string): number {
  const c = [1, 3, 5].map((i) => {
    const v = parseInt(hex.slice(i, i + 2), 16) / 255;
    return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
  });
  return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
}

export function readableOn(hex: string): string {
  return luminance(hex) > 0.45 ? "#20180a" : "#fffdf4";
}

export function normalizeTheme(raw: unknown): SchoolTheme | null {
  const t = raw as Partial<SchoolTheme> | null | undefined;
  if (!t || !isHexColor(t.primary) || !isHexColor(t.secondary) || !isHexColor(t.accent)) return null;
  return { name: typeof t.name === "string" && t.name.trim() ? t.name.trim() : "Custom", primary: t.primary.trim(), secondary: t.secondary.trim(), accent: t.accent.trim() };
}

/** Push the school's colours into the CSS custom properties the whole app reads. */
export function applyThemeToDOM(theme: SchoolTheme) {
  const root = document.documentElement;
  const [h, s, l] = hexToHsl(theme.primary);
  const [ah, as_, al] = hexToHsl(theme.accent);
  const fg = readableOn(theme.primary);
  // raw gradient colours (globals.css paints topbar, sidebar, modal headers)
  root.style.setProperty("--theme-primary", theme.primary);
  root.style.setProperty("--theme-secondary", theme.secondary);
  root.style.setProperty("--theme-accent", theme.accent);
  // shadcn token bridge — branding is deliberately identical in light & dark
  root.style.setProperty("--primary", `${h} ${s}% ${l}%`);
  root.style.setProperty("--primary-foreground", fg === "#fffdf4" ? "0 0% 100%" : "36 33% 7%");
  root.style.setProperty("--ring", `${h} ${s}% ${Math.max(l, 40)}%`);
  root.style.setProperty("--chart-1", theme.primary);
  root.style.setProperty("--chart-2", theme.accent);
  root.style.setProperty("--chart-3", theme.secondary);
  root.style.setProperty("--chart-4", readableOn(theme.secondary) === "#fffdf4" ? theme.accent : theme.primary);
  root.style.setProperty("--chart-5", theme.accent);
  // keep the kente sidebar accents on-brand
  root.style.setProperty("--sidebar-primary", `${ah} ${as_}% ${al}%`);
  root.style.setProperty("--sidebar-ring", `${ah} ${as_}% ${al}%`);
  // browser chrome hint
  const meta = document.querySelector('meta[name="theme-color"]');
  if (meta) meta.setAttribute("content", theme.primary);
}

/** Mutually-resolvable theme for a raw SchoolSetting value. */
export function themeFromSetting(value: string | null | undefined): SchoolTheme {
  if (value) {
    try {
      const t = normalizeTheme(JSON.parse(value));
      if (t) return t;
    } catch { /* legacy raw hex */ }
  }
  return DEFAULT_THEME;
}

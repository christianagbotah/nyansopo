# Wave 11 — CI3 theming engine, card uniformity, modal/page colouring

## User asks (this wave)
1. Top & bottom dashboard cards repeat the same info (students ×2, attendance ×2) — fix the duplication
2. Top cards are too large — make ALL cards system-wide the compact size of the dashboard's second
   row, with "a touch of coloured right border"
3. Add proper colouring to modals and other cards/pages as in the CI3 project
4. CI3 theming: the user sets the colour scheme (primary/secondary/accent) for the whole system —
   reference the CI3 theme settings page

## What shipped

### 11.1 School theming engine (CI3 Admin.php theme_settings parity)
- `src/lib/theme.ts` — 9 presets: Nyansapo Green (factory default, keeps the brand) + the 8 CI3
  presets copied 1:1 (Default Blue #667eea/#764ba2/#f093fb, Ocean, Sunset, Forest, Royal Purple,
  Crimson, Teal Mint, Midnight). `applyThemeToDOM()` paints `--theme-primary/-secondary/-accent`
  plus the shadcn token bridge (`--primary`, `--ring`, `--primary-foreground` by luminance,
  `--chart-1..5`, `--sidebar-primary/ring`, meta theme-color) — branding identical in light & dark.
- `ops/theme` API: GET for every member (they need it to paint the shell), POST preset/custom gated
  `settings.manage`; stores `SchoolSetting["theme"]` JSON `{name, primary, secondary, accent}`;
  invalid hex / missing colours → 400; settings GET payload now carries `theme`.
- app-shell loads the theme on school switch and live-paints — no reload (CI3 needed one).
- Settings → **Theme** tab (CI3 theme_settings.php parity): preset swatch cards with gradient
  preview + accent strip + active check, custom pickers with live preview, Apply custom; deep-link
  target `{tab:"theme"}` and PAGE_INDEX entry "Theme & Colours" (metro search finds it).
- Browser-verified: Ocean Blue preset recoloured topbar/sidebar/buttons/rails/charts instantly,
  toast confirmed, custom "Datus Maroon" persisted via smoke; theme reset to brand default after.

### 11.2 CI3 colour surfaces (header.php dynamic block parity)
- Top bar + sidebar wear `linear-gradient(135deg/160deg, primary → secondary)` with light ink
  (`.theme-topbar` / `.theme-sidebar`), accent-tinted border under the bar.
- Every dialog / alert-dialog / sheet header wears the gradient (`.modern-modal-header` parity) —
  titles/descriptions/close button flip to light; `:has()`-scoped so headerless dialogs are safe.
- Card headers get a gentle theme tint + primary-coloured title (`.panel-primary` parity);
  ModuleHeader gains a slim primary→accent gradient rail under the page title.
- Dashboard charts now read `--chart-1/2` so pies/areas follow the school theme.

### 11.3 Card uniformity + coloured right rail
- `StatCard` (ui-helpers, used in 10 modules) rebuilt as the CI3 stat tile: compact body
  (~103px, was ~190px), value text-2xl, `border-r-4` rail coloured by tone (primary = school theme
  primary, gold, danger red, default = theme-primary blend).
- Dashboard second row de-duplicated: "Total students" and "Present today" dropped (they repeat the
  top row) → now **Active teachers · Registered parents · Cash today (counter+wallets) · Classes**,
  each with the coloured right rail; rails flipped from left to right per the user's ask.
- Cashier per-fee-type summary cards and report-control status cards flipped to `border-r-4`.

## Verification
- **smoke-theme: 13/13** — default fallback, member-wide GET, ocean preset persists, custom
  colours persist, 400s (invalid preset / missing / non-hex), teacher 403, settings payload theme.
- **Full regression: 23/23 suites** (smoke-daily-sync cold-compile flake passed 40/40 on re-run).
- Browser (prod build, head admin): compact right-railed KPI rows, deduped second row, gradient
  topbar/sidebar, Ocean Blue live re-skin, gradient modal header (New admission inquiry), themed
  card headers + fee rails in Fees & Billing. Screenshots shot-theme-{dashboard,tab,ocean,modal,fees}.png.

## 11.4 Follow-up — theme is the Super Admin's call (user clarification)
The user refined the ask: "it is going to be customizable by the school's super admin. to select
his school's color scheme." The original wave shipped the Theme tab gated by `settings.manage`,
which SCHOOL_ADMIN and ACCOUNTANT also hold — they could re-skin the whole school.
- New **`settings.theme`** permission ("Set the school colour scheme (Theme tab)", System group).
  Default holders: **SUPER_ADMIN + GROUP_OWNER only** (SCHOOL_ADMIN now excludes it via minus();
  ACCOUNTANT's explicit list never had it). A custom role can still be granted it in the RBAC matrix.
- `ops/theme` POST now requires `settings.theme` (403 for head/bursar/teacher); GET stays
  member-wide (everyone needs the colours to paint the shell).
- Settings → Theme tab (trigger, content AND the deep-link tab list) renders only when the session
  holds `settings.theme`; other admins keep every other tab. Deep-linking `?tab=theme` without the
  permission safely falls back to the profile tab (no blank panel).
- PAGE_INDEX "Theme & Colours" re-gated → hidden from the Start-screen search for non-super-admins
  (head@ sees 92 pages / "Nothing matches theme"; super@ sees 95 + the entry).
- smoke-theme now 15/15 (added school-admin + accountant 403 negatives; positive POSTs moved to
  super@nyansapo.gh). Full regression 23/23 after re-provisioning the recycled sandbox (fresh
  cluster needed the seed-bill-items / seed-curriculum / seed-framework / seed-creche /
  seed-credits / seed-momo / seed-discount-profiles / seed-attendance-extra / seed-parity-extras
  chain — now recorded in scripts/pg/README.md so future recycles include them).
- Browser: super@ — Theme tab renders, Ocean Blue applies live (toast + CSS vars verified), search
  finds "Theme & Colours"; head@ — no Theme tab, no search hit, deep-link falls back to profile.
  Screenshots shot-theme-super-{ocean,search}.png + shot-theme-head-hidden.png.

## Commits
feat(theme+cards): CI3 theming engine, compact right-railed cards, gradient surfaces (this commit)
feat(theme): colour scheme restricted to the school's super admin — settings.theme permission (follow-up)

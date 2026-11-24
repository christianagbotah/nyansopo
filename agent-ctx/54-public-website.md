# Task 54 — Public school website (work record)

**Parity target:** CI3 `frontend_pages` / `Frontend_model` /
`frontend_events` / `frontend_news` / `frontend_gallery` /
`frontend_slider` / `frontend_general_settings` (iEduPro public site,
`views/frontend/default` theme) — the one major CI3 surface the app had
not yet covered. The gap was found by diffing all 115 legacy controllers
against the module map; everything else (exam-marks SMS, interest items,
insurance, coupons, portfolio, alumni, gender reports, handovers…) was
already ported.

## What was built

**Data (Prisma):** `SiteSetting` (published flag, 3-slide `sliderJson`,
about/terms/privacy, contact email+phone), `SiteNews`, `SiteGalleryAlbum`
(showOnWebsite + cover), `SiteGalleryImage` (cascade delete),
`SiteContactMessage` (handled flag), `Staff.showOnWebsite`,
`MediaAsset.publicToken @unique`.

**Public assets:** uploads arrive as client-resized data-URLs (mirrors
settings branding) and are stored as `kind=SITE` MediaAsset rows carrying
a `publicToken`; `GET /api/media/{id}?t={token}` streams unauthenticated
(`public, immutable` cache). Access matrix verified: no token → 401,
correct token → 200, wrong token → 401. The public page resolves every
referenced asset id → tokenised URL server-side.

**Admin Website module** (`website` in nav, section Support, perm
`website.manage` in group System → auto-granted to SCHOOL_ADMIN ladder):
tabs Publish & link · Hero slider (3 slots, CI3 `slider_images` JSON
parity) · Events (read-only view of Front-Office `SchoolEvent audience=ALL`
+ deep link) · News CRUD · Gallery album CRUD + ≤10-photo batch upload +
hide/show + cover · Pages (about + image, terms, privacy, contact) ·
Staff showcase toggles · Contact inbox (handled/delete). Every mutation
audited. 8 page-index entries with search keywords.

**Public site** `/site/{code}` (mirrors the CI3 default theme, styled
with the school's `logoColor`): sticky header + mobile nav, auto-rotating
hero slider (fallback deck when empty), upcoming events, latest news,
about, events/news/gallery pages (lightbox), staff page, contact form
(→ `POST /api/site/{code}/contact`, rejected while unpublished),
footer with terms/privacy. Unpublished or unknown codes render a
friendly "not published yet" page; `generateMetadata` carries the school
name + motto.

## Verified end-to-end in browser

Publish toggle → slider (2 slides saved, KPI 2/3) → news post published
("Golden Excellence wins regional science fair") → album created → 3
staff toggled on → public site renders hero + events + news → staff page
shows the 3 toggled staff → contact form submit → "Message sent" →
message lands in the admin inbox with all fields. Zero console/page
errors; lint clean.

## Two fixes during verification

1. `page.tsx` select omitted `code` → contact POST hit
   `/api/site/undefined/contact` (404). Added to the select.
2. `SliderTab` seeded slides in an effect → lint
   `set-state-in-effect` + empty first render; converted to the
   adjust-to-props render pattern with a `seedSlides` initialiser.

## State after the wave

- Pushed `c902461` (main == origin/main), tree clean.
- Golden Excellence Academy (code `GEA`) has a live published demo site
  at `/site/GEA`.
- Known follow-on (not blocking): CI3's per-album multi-image captions
  are single-caption here; frontend themes picker (CI3 `frontend_themes`)
  intentionally replaced by the school's own `logoColor` accent.

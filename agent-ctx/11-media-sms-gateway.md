# Task 11 — Media blob storage + real SMS gateways (work record)

Closed the last two pre-rollout items from
`docs/ci3-priority-view-review.md` §"Still open": image data-URLs in hot
rows → binary blob storage, and the SIMULATED-only SMS path → real
ARKESEL/TERMII gateway dispatch.

## 1. MediaAsset blob storage (portraits + branding)
- Schema: new `MediaAsset` model (kind PORTRAIT|LOGO|SIGNATURE, mimeType,
  sizeBytes, `data Bytes`, school-scoped) + `School.mediaAssets`.
- `src/lib/media.ts`: `decodeImageDataUrl` (png/jpeg/webp, ≤700KB), `storeMedia`
  (data-URL → asset → `/api/media/{id}` reference URL).
- `GET /api/media/[id]`: streams bytes; auth + school scope (staff of the
  school or association officers); `cache-control: private, max-age=1y,
  immutable` (each upload mints a new id); junk ids 404; traversal rejected.
- Write paths rewired server-side — client contracts unchanged:
  - admissions `admit`: portrait data-URL → PORTRAIT asset; junk/oversized
    degrade to no photo (photoSeed fallback) exactly as before.
  - settings `update-preferences`: logo/signature data-URLs → LOGO/SIGNATURE
    assets; explicit `null` clears; re-saving a `/api/media/…` value (what
    the client round-trips) stores it verbatim — no duplicate assets.
- `scripts/backfill-media.ts` — idempotent migration of legacy data-URL
  values (Student.photoUrl via enrollment-resolved school, branding
  settings); `--dry` supported. Current demo DB had none to convert.
- Receipts/settings UIs unchanged: they render whatever URL the setting
  carries (media or legacy data-URL).

## 2. Real SMS gateways (ARKESEL + TERMII)
- `src/lib/sms-gateway.ts`:
  - `toGhanaIntl` — 0…/+233…/233… → `233\d{9}`, spaces/dashes stripped;
    foreign/short numbers → null (rejected before any HTTP call).
  - `arkeselSend` — POST `{base}/api/v2/sms/send`, `api-key` header,
    `{sender, message, recipients:[…]}`; success parses `status:"success"`
    (+ `code:"ok"` / message variants) and `data[0].id` as ref.
  - `termiiSend` — POST `{base}/api/sms/send`, `{api_key, to, from, sms,
    type:"plain", channel:"generic"}`; success parses `message_id`.
    (Shapes verified against both providers' published docs.)
  - 15s timeout; friendly network/timeout errors; `SMS_*_BASE_URL` overrides
    for integration testing.
- `sendSms` rework (`src/lib/sms.ts`): provider dispatch ARKESEL|TERMII|
  SIMULATED; **credits now debit only on success** (previously a FAILED
  simulated send still debited — regression fixed); missing env key →
  FAILED with setup hint, free of charge; `SmsMessage` gains `provider` +
  `providerRef` columns for delivery follow-up.
- Keys are deployment env (`SMS_ARKESEL_API_KEY` / `SMS_TERMII_API_KEY`,
  documented in `.env.example`) — never in the DB; the school's provider
  choice in SMS Centre decides which is used.
- SMS Centre: GET payload gains `gateway: {arkesel, termii}` config status;
  Settings tab + compose summary explain demo vs live + missing-key states.

## Verification
- New suites: `smoke-media` **27/27** (admit→asset, auth/scoped streaming,
  junk/oversized rejection, branding round-trip incl. no-duplicate re-save,
  null-clears, orphan-sweep stability), `smoke-sms-gateway` **39/39**
  (normalisation ×8, both adapters vs an in-process mock gateway incl.
  failure + network-error paths, full sendSms dispatch per provider incl.
  debit-on-success-only + missing-key + invalid-phone-never-called +
  zero-credits, gateway status API, provider round-trip) — the mock also
  asserts the exact wire format of both gateways.
- Updated for the new contracts: `smoke-admissions-extra` (portrait is now a
  media reference + asset cleanup), `smoke-preferences` (media assertions +
  asset cleanup).
- Full regression: **416 checks, 0 failures** across 14 suites + portals
  (codes 23, credits 40, curriculum 29, attendance-analytics 29,
  bulk-admission 48, creche-SBA 64, staff-dashboard 51, admissions-extra 14,
  fees-parity 9, lesson-note-sms 32, preferences 11, media 27,
  sms-gateway 39, portals 24+24). `tsc --noEmit` 0; eslint 0 errors.
- Sandbox state repairs this session (documented for future agents):
  a crashed smoke run had left a probe student (broke the recompute-based
  `studentsOnRoll` check in `smoke-codes`) and the task-10 browser check had
  consumed the seeded week-6 PENDING lesson note (`smoke-curriculum` expects
  ≥3). `scripts/smoke-media` now pre-sweeps orphan branding assets so
  crashed runs can't poison the count check.
- NOTE: after `prisma db push` + `generate`, the dev server must be
  RESTARTED — the running process keeps the old client (`db.mediaAsset`
  undefined → 500s). Restart: kill next, `setsid bash -c 'nohup bun run dev
  > /dev/null 2>&1 < /dev/null &'`, wait ~20s.

## Notes for later agents
- To move blobs to S3/CDN later: change `storeMedia` + `GET /api/media/[id]`
  only — every consumer already handles plain URLs.
- Gateway response parsing is deliberately tolerant (status/code/message
  variants) — if a provider changes its payload, update `arkeselSend` /
  `termiiSend` and the mock in `scripts/smoke-sms-gateway.ts` together.
- `smoke-portals.ts` needs `DATABASE_URL` in the env (it has no inline
  default, unlike the other suites).

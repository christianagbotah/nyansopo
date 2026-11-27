# Wave 75 — Transport verification & hardening

## Why
The roadmap named Library (wave 74, done) then Transport — but a survey showed
Transport was already fully built across earlier waves (routes + structured
per-stop fares, vehicle/driver registries with compliance ageing, assignments,
daily boardings + wallet settlement, trips, bill-term, collect-fare, fare
report). What was missing: an end-to-end smoke for the *management + money*
story (the old `scripts/test-fare-engine.sh` covers only conductor boarding /
wallet), plus post-recycle browser verification.

## Delivered
- `scripts/smoke-transport.sh` — 12 checks, self-cleaning:
  RBAC (teacher GET 403 · conductor conduct-only, save-route 403) → fleet
  registries with compliance ageing (EXPIRED / OK / DUE_SOON) → route with
  priced stops + registry links → assignment resolving the STOP fare →
  conductor record-trip → cash boarding (charge PAID) → fare report row →
  bill-term → collect-fare → money view → cleanup.
- `scripts/cleanup-transport-smoke.ts <tag> <paymentId> <studentId>` — reverts
  the invoice line (+ total), deletes payment, boarding, charge, trip,
  assignment, route+stops, vehicle, driver.
- API hardening: `bill-term` accepts optional `routeId` — a scoped pass bills
  exactly one route. The global pass (UI button unchanged) stays idempotent.

## Gotcha found & fixed
The first unscoped `bill-term` in the smoke billed all 49 seeded riders'
invoices (seed state = fares collected at counter, no invoice lines). Reverted
all 49 lines with a one-off script; the smoke now passes routeId so future
runs never touch seed data.

## Verified live (browser)
- Head · Transportation: Routes tab (priced stop chips, per-rider Collect with
  outstanding amounts), Fleet dialog → vehicle row aged to "EXPIRED · 15 Sept
  2026", Fare report (294 boardings, ₵202.00 owing, CSV export).
  Screenshot `agent-ctx/w75-fleet-compliance.png`.
- Conductor · Portal: marked Adwoa AM → "PICKED UP 1/13", "₵9.00 marked ·
  ₵9.00 owing", trip count fed from marks and saved into Recent trips.
  Screenshot `agent-ctx/w75-conductor-manifest.png`.
- `codeCounter.create` P2002 in dev.log is the *handled* first-allocation race
  in `nextSeq` (create → catch → atomic increment) — noise, not a bug.

## Ops
- Safety-net cron fired mid-session (7a07bb5) — committed + pushed the wave-75
  code before the manual commit; screenshots went out in 38c32c7.
- All smoke/E2E rows reverted; `SMK-TR-*` / `SMK-E2E-*` residue = 0.

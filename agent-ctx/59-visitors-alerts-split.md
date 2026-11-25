# Wave 59 — Visitors book staff alerts + full split payments + 502 root cause

Date: 2026-11-25 (Atlantic/Reykjavik) · commit "wave 59 — …"

## 1 · The 502 on split payment — root cause & fix
- **Symptom**: split payment on the fee collection portal returned 502.
- **Cause**: the dev server had been **OOM-killed** (dmesg: `Out of memory:
  Killed process … next-server` at ~2.3 GB RSS). Every request through the
  preview gateway then answered 502 — split was simply the flow in use when
  it died. The endpoint itself is healthy (direct POST → 200).
- **Fixes**:
  - `src/lib/db.ts` — dropped Prisma `log: ['query']` (the top log/memory
    driver); now `log: ['error', 'warn']`.
  - `package.json` dev script — `NODE_OPTIONS='--max-old-space-size=1536'`
    caps V8 growth; Turbopack already capped at 768 by next.config (w55).
  - Operational rule: never run `tsc --noEmit` beside the dev server (the
    compiler spike is what pushes the box over 4 GB).
  - Safety-net (`bun run dev`) picks the cap up automatically.

## 2 · Visitors book — selectable "To see who" + instant staff alerts
User request: *"'To see who' should be selectable so that as the staff is
selected and the process completes, quickly he receives an alert via
sms/email/in-app immediately."*
- **Schema**: `Visitor.personToSeeStaffId` → Staff (relation
  `VisitorSeesStaff`) + `alertedSms/alertedEmail/alertedInApp` flags;
  `SmsSetting.autoVisitorAlerts` master switch (default on).
- **Backend** (`ops/frontoffice`):
  - GET board/visitors now carry `staff[]` options (active staff, role/
    position label, phone/email availability).
  - POST `visitor-in` accepts `personToSeeStaffId`; the moment the row lands
    the linked staff member is alerted on every channel:
    - **SMS** — `sendSms` (gated by `autoVisitorAlerts` + `Staff.smsNotify`
      + phone on file), category FRONT_OFFICE, full context in the body.
    - **Email** — `sendEmail` when the school has SMTP enabled + staff email
      (branded visitor card HTML).
    - **In-app** — derived bell item (below); clears itself at check-out.
  - Response returns the alert outcome so the desk sees what fired.
- **Notifications API**: new derived item — any signed-in user whose
  `staffId` matches an ON_SITE visitor (≤12 h) gets "A visitor is waiting to
  see you … pass P-xxx · at the desk since HH:MM", deep-linking to
  frontoffice › visitors.
- **RBAC**: TEACHER granted `frontoffice.view` (the alert must be
  actionable by the teacher it targets).
- **UI** (`front-office.tsx`): "To see (staff alert)" Select — staff list +
  "Other — type a name…" fallback; teal explainer when a staff member is
  picked; success toast reports channels ("SMS sent · in-app alert");
  visitor rows carry a bell badge when alerts fired.
- **Seed**: the two on-site demo visitors are now staff-linked (head with
  SMS + in-app, teacher with in-app).
- **Verified in browser**: booked Nana Yaa Oforiwaa → Grace; toast "SMS
  sent · in-app alert"; signed in as teacher@nyansapo.gh → bell shows the
  waiting visitor; deep-link opens the visitors book (teacher can view).

## 3 · Split payments — invoice bills, fully (CI3 daily_fee_payment_splits parity)
User request: *"we need to implement the split payment method there
[invoice bill payment] as well fully."*
- **Backend**:
  - `/fees record-payment` accepts `methods[]` (sanitised against the
    requested amount; parts must balance) → ONE Payment row with
    `method="SPLIT"` + `splitJson`; per-part references ride the split.
    Overpayment-credit, invoice status and receipt SMS untouched.
  - `/api/sync` invoice flush replays `methods[]` the same way (offline
    split invoice payments sync as one receipt).
  - `invoicePaymentReceipt` now expands SPLIT via `paymentMethodLabel`
    → receipt shows "Cash + MTN MoMo".
- **Fees dialog** (`fees.tsx`): the legacy two-receipt "SPLIT" was replaced
  by the real editor — N parts (method + amount + reference), add/remove
  (2–4), fill-remaining, cheque parts demand bank + cheque no.; submits ONE
  `record-payment` with `methods[]` (online and offline queue alike).
- **Fee collection portal**: split now covers the WHOLE basket — parts must
  sum to daily items + invoice bills; an allocation walk fills the daily
  receipt first, then each invoice receipt, dividing a part across the
  boundary (e.g. CASH 500 over lunch ₵8 + invoice ₵1460 → daily receipt
  CASH 8, invoice receipt CASH 492 + MoMo 968). Toggle enabled whenever the
  grand total > 0.
- **Verified in browser**:
  - Portal: Dzifa B. — lunch ₵8 + INV-2026-0002 ₵1460, split CASH 500 +
    MoMo 968 (ref) → RCP-2026-00503 (daily ₵8) + RCP-2026-00504 (invoice
    ₵1460 SPLIT, CASH 492 + MoMo 968), invoice PAID, receipt dialog shown.
  - Fees dialog: Perpetual B. INV-2026-0006 ₵2460 split CASH 1000 + MoMo
    1460 (ref) → RCP-2026-00505 SPLIT, invoice PAID, official receipt.
  - Direct API: pos-collect split (₵3 + ₵5) 200, void 200 (advance
    reversed), invoice split 200 with receipt "Cash + MTN MoMo".

## Verification trail
- agent-ctx/w59-bell-visitor-alert.png — teacher bell with the waiting visitor
- agent-ctx/w59-portal-split-done.png / w59-portal-receipt.png — portal split
- agent-ctx/w59-invoice-split-receipt.png — fees dialog split receipt
- agent-ctx/w59-visitors-mobile.png — 390 px responsive check
- Zero console errors; lint clean; tsc clean on all touched files
  (pre-existing waec/site/app-shell type noise unchanged).

## Demo state left behind (intentional)
- RCP-2026-00502/504/505 split receipts on INV-2026-0002 & 0006 (both PAID).
- Visitor P-084 (Mr. Samuel Otu → Mr. Isaac Boateng) ON SITE with SMS +
  in-app alerts fired — teacher@nyansapo.gh demo shows the live bell item.

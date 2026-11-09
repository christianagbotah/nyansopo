# Task 10 — Lesson-note SMS notifications (work record)

Follow-on from task 9's suggested next step: "SMS notifications to teachers
when lesson notes are returned for revision" + the record-request deep-link
fix. All six open items of `docs/ci3-priority-view-review.md` were already
closed by wave 1 (`dded0fa`); this wave implements the remaining follow-ons.

## 1. Teacher SMS on lesson-note review (the requested feature)
- New automation triggers (iedupro `Sms_model`/`sms_automation` parity):
  `LESSON_NOTE_RETURNED` (sends by default — reviewer feedback is
  time-critical) and `LESSON_NOTE_ENDORSED` (opt-in — only fires while an
  active rule exists, saves credits). New message category `STAFF`
  (teacher-directed, no Student link).
- Hook in `ops/curriculum` `note.review`: after the transaction, SMS the
  note's author via `sendAutomated`. Placeholders `{teacher} {note} {class}
  {subject} {week} {reviewer} {comment} {school} {date}`. SMS failures can
  never break the review (try/catch; out-of-credits logs a FAILED row).
- **Three layers of gating** (school × teacher × wallet):
  1. `SmsSetting.autoLessonNoteAlerts` (school kill switch, default ON —
     SMS Centre → Settings; preserved when older callers omit it)
  2. `Staff.smsNotify` (per-teacher opt-out, default ON — admin staff form
     checkbox + teacher self-service, see §2)
  3. `sendSms` credits (FAILED + reason when the wallet is empty)
- `sendAutomated` now renders `{placeholders}` in default bodies too
  (previously only rule bodies were templated — the built-in bodies of the
  existing callers are plain strings, so no behaviour change for them).

## 2. Per-teacher preference (iedupro `user_notification_preferences` parity)
- Schema: `Staff.smsNotify Boolean @default(true)` (CI3 defaulted SMS off
  for payroll alerts; lesson-note alerts default ON because that is the
  feature's purpose — opt-out is one click either way).
- Admin side: staff record dialog (HR & Payroll) gains "SMS lesson-note
  alerts to this phone" next to the phone field; persisted via
  `save-staff` (`smsNotify` boolean).
- Teacher side (self-service, like CI3's own-preference controller):
  `ops/portal` GET `view=staff` returns `me.smsNotify`, and a new POST
  action `staff.set-sms-preference` toggles it. Enabling without a phone on
  the staff record is rejected 400 (CI3 behaviour). The Staff Portal
  profile tab gains an "SMS lesson-note alerts" card with a live switch.

## 3. SMS Centre UI
- Trigger list: "Lesson note returned" / "Lesson note endorsed"; picking
  one auto-selects Teachers recipients, swaps the placeholder help to the
  teacher set, pre-fills a default body, and explains the endorsement
  opt-in. `STAFF` badge colour + History category filter added.
- Settings tab + compose-page summary gain the "Lesson-note alerts" row.

## 4. Record-request notification deep-link (wave-1 bug fix)
- The bell item existed but (a) deep-linked to the Students roster and
  (b) was gated on `approvals.manage`, which SCHOOL_ADMIN is explicitly
  denied — heads never saw it. Now gated on `admissions.manage` (the same
  permission that allows responding; also the Admissions module's sidebar
  gate) and deep-links to `admissions › requests`.
- `Admissions` module consumes `navTarget.tab` (same pattern wave 1 added
  to Inventory) so the bell lands on the Record requests tab.

## Verification
- New `scripts/smoke-lesson-note-sms.ts` — **32/32**: default return SMS
  (recipient/body/status/credits/no-student-link), rule override +
  sentCount, endorse without rule (silent) and with rule (sends),
  per-teacher opt-out (save-staff), portal re-enable + no-phone 400,
  school kill switch round-trip, out-of-credits (review still 200, FAILED
  logged), deep-link module/tab, admissions inbox, self-cleaning.
- Full regression: **350 checks, 0 failures** across 13 suites
  (admissions-extra 14, attendance-analytics 29, bulk-admission 48, codes
  23, creche-SBA 64, credits 40, curriculum 29, fees-parity 9,
  lesson-note-sms 32, preferences 11, staff-dashboard 51, portals).
  `smoke-curriculum` now also cleans up the STAFF SMS its lifecycle test
  triggers.
- `tsc --noEmit` 0; eslint 0 errors on all changed files.
- Browser (head + teacher accounts): bell "1 learner record request …
  opens admissions › requests" → lands on Record requests tab with inbox;
  SMS Settings shows Lesson-note alerts ON; automation dialog lists both
  new triggers with teacher defaults; returning a note produced the STAFF
  SMS in History ("Hi Mr. Isaac Boateng — … week 6 … Reviewer:
  'BROWSER-CHECK: …' — Golden Excellence Academy"); Staff Portal card +
  self-service switch round-trips off/on. Zero page errors.

## Sandbox recovery (again)
The recycle wiped the untracked PG data cluster AND `node_modules`.
Recovery: `initdb` per `scripts/pg/README.md` → start → `bun install` →
`prisma db push` (creates the DB too) → **`scripts/reseed-chain.sh`** (new;
runs all 18 seed scripts in dependency order, idempotent) → dev server.
Restart `bun run dev` AFTER `db push`.

## Git hygiene
- `.env` is no longer tracked (it had been since the initial commit and
  the stray `45b13ba` even committed a *stale SQLite URL* into it).
  `.gitignore` gains `!.env.example`; `.env.example` documents the
  embedded-PG URL. Local `.env` corrected to the postgres URL.
- The stray UUID-named commit `45b13ba` (pg binaries + .env tweak) is
  already on origin/main; left as-is — rewriting pushed history is riskier
  than the noise. Its content is benign (gitignore deliberately tracks
  the embedded-PG binaries so recycled sandboxes can restart the server).

## Notes for later agents
- Endorsement SMS is deliberately opt-in via an active
  `LESSON_NOTE_ENDORSED` rule — mention this in user docs.
- When a real gateway (ARKESEL/TERMII) is wired into `sendSms`, the
  teacher SMS path needs no changes — it already flows through the
  provider-agnostic helper.
- Admission portraits & branding images are still data-URLs in Postgres
  (wave-1 note) — blob storage remains the pre-rollout item.

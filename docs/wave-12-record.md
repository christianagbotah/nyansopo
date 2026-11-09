# Wave 12 — backlog E/D/B/A: backups, email channel, internal messaging, double-entry accounts

Fresh-session record: restored from origin (4307dce pushed with the owner's
PAT — stored in `~/.git-credentials`, gitignored `GIT_REMOTE.md` note),
restarted PG + servers. This wave cleared the four remaining "deliberate
CI3 gaps" from `agent-ctx/17-backoffice-parity.md`.

## Shared groundwork (orchestrator, before subagents ran)
- **Schema** (`prisma/schema.prisma`): DbBackup, EmailSetting + EmailMessage,
  MessageThread + Message (User↔User, "MsgSender"/"MsgRecipient" relations),
  ChartAccount (self-tree "AccTree") + JournalEntry + JournalEntryLine +
  BankAccount + BankTxn + Reconciliation + ReconciliationItem; School/User
  backrelations.
- **RBAC**: new permissions `email.send`, `messages.use` (Communication);
  `accounts.view`/`accounts.manage` (Fees & Finance); `backup.manage`
  (System — **SUPER_ADMIN + GROUP_OWNER only**, SCHOOL_ADMIN excluded like
  settings.theme). Role defaults updated for all backoffice roles; teachers/
  librarians/non-teaching get messages.use. MODULE_PERMS + nav + PAGE_INDEX
  entries + deep-links + stub components, all pre-wired so the four feature
  agents worked on fully disjoint files.

## E — Database backup / restore (CI3 `admin/backup` parity)
`ops/backup` + Settings › Backups tab (gated `backup.manage`). pg_dump
(portable PG at `/home/z/pgsql/bin`, trust auth) → `db/backups/`
(gitignored) with create/download/delete/restore/cleanup; restore demands
typing RESTORE; dumps use `--clean --if-exists` (CI3 add_drop parity) so
restores truly replay. **Path lesson**: the standalone server chdirs to
`.next/standalone` (which *mirrors* the project, incl. a stale `.git`) —
cwd-relative paths silently land in the ephemeral build folder. The route
resolves the root by the deterministic shape `cwd === <root>/.next/standalone`
(+ `NYANSAPO_BACKUP_DIR` override). Smoke 24/24.

## D — Email channel (CI3 `Invoice_email`/`Email_model` parity)
`lib/email.ts` (nodemailer, lazy pooled transport) + `ops/email` + SMS
Centre › "Email channel" tab + per-invoice Mail button in Fees › Invoices
(guardianEmail now rides the invoices payload). SMTP settings with
password-preserve-on-blank + `hasPassword` (never echoed), test send,
send-invoice (400 when the guardian has no email), bulk ALL/UNPAID/CLASS
(cap 200). Unconfigured → **SIMULATED** log rows (SMS-gateway philosophy:
workflows never stall). Smoke 25/25.

## B — Internal messaging (CI3 `message_thread`/`message` parity)
`ops/messages` + Messages module (two-pane mail client; single pane + back
nav on mobile). One thread per unordered staff pair (find-or-create checks
both directions — CI3 semantics), replies, ≤4MB attachments (MediaAsset
kind ATTACHMENT via an upload-attachment action; `/api/media/[id]` needed no
whitelist change), mark-read on open, delete-thread, 45s polling. Notification
bell gained an unread-messages item. Parents/students excluded (portal-side
messaging = follow-up). Smoke 41/41.

## A — Double-entry accounts + bank reconciliation (CI3 `Accounts` parity)
`ops/accounts` (701 lines) + Accounts & Ledger module (6 tabs) +
`prisma/seed-accounts.ts` (31-account Ghana chart, 13 journals, 2 banks,
14 txns, 1 in-progress reconciliation — idempotent).
- Chart of accounts: type filters, parent/child guards (no cycles),
  delete-409 when referenced; isBank flags feed the bank pickers.
- Journal: DRAFT→POSTED→VOIDED lifecycle; Σdebit=Σcredit enforced at save
  AND post (totals in the 400 message); posted entries immutable; JE-numbers
  sync-safe sequential. **Balances are computed live from POSTED lines only**
  (VOIDED is inert) — no cached balance column to drift.
- Bank: accounts CRUD + txn feed (DEPOSIT/WITHDRAWAL); bank txns are the
  operational feed for reconciliation and are deliberately NOT auto-posted
  to the ledger (CI3 links them; auto-posting risks double-counting manual
  journals — documented deviation).
- Reconcile: one open reconciliation per bank account (409), statement items
  (signed amounts), match/unmatch/auto-match (|amount| + ±3 days, one pass),
  complete requires all matched → stamps reconciledAt on the linked txns.
- Reports: trial balance (proves ΣDr=ΣCr), balance sheet (with the
  balanced-check row: assets − liabilities − equity − netIncome = 0), income
  statement (TOTAL REVENUE / TOTAL EXPENSES / SURPLUS-(DEFICIT)), general
  ledger (opening → running → closing per account); date ranges + CSV + print.
- Deliberate CI3 cuts: budgets, fiscal years, audit-trail page, cash-flow
  statement. Smoke 70/70 (run 3×; the implementing agent timed out after
  shipping route+UI+seed — a follow-up agent wrote the suite, zero product
  bugs found).

## Cross-cutting fixes this wave
- `bun run lint` repo-wide is back to **0 errors**: app-shell's `show`
  useCallback → plain function (React compiler auto-memoizes;
  preserve-manual-memoization), cashier-console setState-in-effect redirects
  got the repo's established eslint-disable comments (conductor-portal
  pattern). All 3 errors were pre-existing at HEAD — proven by linting the
  HEAD blobs.
- Mobile 390px: the topbar "Install app" button is now icon-only below sm —
  its label pushed the avatar button 43px off-screen (found during the
  wave-12 mobile audit; pre-existing since the PWA install prompt).
- Dev-server OOM: `next dev` gets OOM-killed by the 4GB sandbox during
  heavy route compilation (next-server ~1.9GB anon-rss in dmesg). The
  standalone production build is the stable verification target — build with
  `bun run build`, start with the documented env command.

## Verification
- **27/27 TS smoke suites green** (23 regression + 4 new: backup 24, email
  25, messages 41, accounts 70) + legacy shell harness (fare 14, students
  15, parity2 41, verify-rbac) against the standalone production build.
  `tsc --noEmit` 0; `bun run lint` 0 errors (12 pre-existing-style warnings).
- Browser (agent-browser, production build): super@ — Accounts tabs render
  with seeded data; **live auto-match 0/6 → 4/6** in the reconcile
  workspace; Reports TOTAL row; Messages compose → thread + conversation
  + reply box; SMS Centre Email tab (3 emailable / 129 no-email counts,
  test send → SIMULATED row in the log); Settings › Backups created a real
  3.3MB dump from the UI with note + row actions. Zero console/page errors.
  Mobile 390px: no horizontal overflow across messages/sms/accounts
  (chart/journal/reconcile/reports)/settings-backup. Desktop 1280px clean.

## Ops notes
- `smoke-backup`'s restore check replays the schema (DROP/CREATE) — run it
  LAST in any batch: pooled connections in the running server briefly
  invalidate and can flake *subsequent* suites (observed once with
  messages/momo; consecutive standalone runs green).
- The seeded reconciliation was left at 4/6 matched after the live browser
  demo — realistic mid-reconciliation state.
- Backups live in `db/backups/` (gitignored); a demo snapshot
  ("Pre-migration snapshot") exists from the browser session.

## Commits
(this wave) — see git log: schema+shared groundwork, E, D, B, A, lint +
mobile fixes, docs.

# Task 8 — Sync-safe code generation (work record)

Closed out the "critical design question" from the migration review: sequential
document codes (`INV-2026-006`, `RCP-…`, `NY-…`) colliding during online/offline
sync. The hybrid design was already implemented (`src/lib/codes.ts` +
`/api/sync`); this pass **hardened it, proved it, and documented it**.

## Design (docs/sync-safe-code-generation.md — the user-facing answer)

- Row identity = `cuid()` PKs (UUID-class, zero coordination).
- Human codes = server-side only, minted from a `CodeCounter` row
  (`scope` = kind:year) via one atomic `UPDATE … RETURNING` — no
  read-then-write window. First allocation seeds from the highest existing
  code of the kind; year scopes restart at 0001.
- Offline devices never mint final codes: ops queue in localStorage with a
  collision-proof `clientId`; the UI promises "receipt issued on reconnect";
  `/api/sync` mints the permanent code at flush.
- Every op kind has a durable idempotency key (re-flush after a crash is a
  no-op): payment → `reference = SYNC:{clientId}`; student →
  `Student.syncClientId @unique`; attendance → marker in `takenByName`;
  marks → replace-by-value.

The CI3 legacy scheme (`finance_helper.php generate_invoice_code()` =
`count_all_results() + 1`) is documented as the anti-pattern: duplicates under
concurrency, code reuse after deletes, and per-device counts offline.

## Bugs found & fixed this pass

1. **Offline student re-flush duplicate** — dup-check keyed on a temporary
   `SYNC-{clientId}` student index that was immediately upgraded to `NY-…`,
   so a second flush created a duplicate learner. Fix: permanent
   `Student.syncClientId` column (`@unique`, schema push), P2002 race catch,
   real `NY-` index minted directly at create.
2. **Offline enrolment didn't bill** — online path auto-bills the first-term
   invoice from the class fee structure; the flush path didn't. Now parity:
   invoice + non-optional items + `studentsOnRoll` update at flush.
3. **Weak clientId** in the students enrolment dialog (`q-{ts}` only) → now
   `q-{ts}-{rand}` like `apiPostQueueable`.
4. `GIT_REMOTE.md` (holds credentials) was untracked but not ignored → added
   to `.gitignore`.

## Files changed

- `prisma/schema.prisma` — `Student.syncClientId String? @unique`
- `src/app/api/sync/route.ts` — student op rewrite (durable guard, direct
  index mint, first-term billing, on-roll update)
- `src/components/modules/students.tsx` — collision-proof offline clientId
- `src/lib/codes.ts` — doc pointer in header
- `scripts/smoke-codes.ts` — NEW 23-check proof suite (self-cleaning)
- `docs/sync-safe-code-generation.md` — NEW design + verification doc
- `.gitignore` — credential note excluded

## Verification

- `scripts/smoke-codes.ts` **23/23**: 25-way in-process concurrent minting ×3
  kinds (unique + contiguous), 3-process × 10 cross-connection minting
  (unique + contiguous), year-scope isolation (INV:2027 → 1), payment
  double-flush (one receipt, invoice credited once), student double-flush
  (one student, NY- index, first-term invoice, on-roll consistent), full
  cleanup to pre-test state.
- Regression suites after the schema push: credits 40/40 · portals 24+24 ·
  curriculum 29/29 · attendance-analytics 29/29 · bulk-admission 48/48 ·
  creche-SBA 64/64 · staff-dashboard 51/51.
- `tsc --noEmit` exit 0; eslint 0 errors on changed files (2 pre-existing
  warnings in untouched students.tsx lines).
- agent-browser: landing → role picker (all 5 ladder levels) → **Accountant
  L3** session shows money-side modules only (no RBAC console, no academics),
  "Online" sync badge present, students list renders with NY- indexes, zero
  page errors.

## Notes for later agents

- Sandbox recycle recovery used this session: `initdb` per `scripts/pg/README.md`
  → start → `CREATE DATABASE nyansapo` → `bun install` → `prisma db push` →
  full seed chain (15 scripts) → restart `bun run dev` AFTER `db push`
  (in-memory Prisma client predates new columns otherwise).
- The smoke-codes worker mode filters its stdout for the JSON line because
  `src/lib/db.ts` hardcodes `log: ['query']` (query logs pollute stdout).
- If multi-primary writes ever arrive (regional offline servers), sequential
  codes need per-site prefixes or a UUIDv7 + display-number map — see §6 of
  the design doc; `cuid` row IDs already merge safely.

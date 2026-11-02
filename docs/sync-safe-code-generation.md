# Sync-Safe Code Generation — Design & Verification

**The question this document answers:** *"Invoice/receipt/student codes like `INV-2026-006` will conflict during online/offline syncing — if an offline device regenerates from `INV-2026-001`, duplicates appear. Shouldn't we use UUIDs, or some other sync-safe strategy?"*

Short answer: **we split identity from numbering.** Row identity is UUID-class (`cuid()`) and needs no coordination; human-readable sequential codes are minted **server-side only**, from an atomic counter, at the moment an offline queue flushes — never on the device. Duplicates are structurally impossible, and receipts stay parent-readable (`RCP-2026-00042`), not `a3f9c2e1-…`.

---

## 1. Why this matters — the legacy scheme was already unsafe *online*

The CI3 system (iEduPro) generates invoice codes with a count-then-pad helper
(`application/helpers/finance_helper.php`):

```php
$count = $CI->db->where('year', $year)->where('term', $term)->count_all_results('invoice');
return 'INV-' . str_replace('-', '', $year) . '-T' . $term . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
```

This has three failure modes **before offline ever enters the picture**:

| # | Failure | Mechanism |
|---|---------|-----------|
| 1 | Duplicate code | Two clerks create invoices in the same instant → both read the same `count` → both mint `…-00407` |
| 2 | Code reuse | An invoice is voided/deleted → `count` drops → the next invoice re-issues a number that already appeared on a parent's receipt (audit violation) |
| 3 | Offline catastrophe | Every device counts only its **local** rows → an offline phone happily re-mints `INV-2026-00001…` that the server issued weeks ago — exactly the collision flagged in the question |

So the migration could not carry the scheme forward even as-is; it needed a
redesign.

## 2. The design — hybrid identity

| Concern | Mechanism | Why |
|---|---|---|
| Row identity (joins, FKs, sync) | `cuid()` primary keys (`@id @default(cuid())` on every model) | Globally unique with zero coordination; two devices can create rows independently and merge later without any key collision |
| Human-facing codes (`INV-`, `RCP-`, `NY-`) | Server-side minting from a `CodeCounter` row | Parents, cashiers, auditors and GES/GRA practice expect short sequential document numbers; UUIDs are unreadable on a thermal receipt |
| Offline devices | **Never** mint final codes. Actions are queued with a collision-proof `clientId`; the UI shows a provisional "queued" state | The only writer of sequential numbers is the server, so the sequence is single-sourced by construction |
| Re-flush safety | Every op kind carries an idempotency key; the server recognises its own previous work | A flush that crashes halfway, or a user hammering "Sync now", must not double-charge anyone |

### 2.1 The `CodeCounter` table

```prisma
model CodeCounter {
  scope     String   @id   // "INV:2026" | "RCP:2026" | "NYIDX:2016"
  next      Int      @default(0)  // LAST ALLOCATED number
  updatedAt DateTime @updatedAt
}
```

One row per **code kind × year scope**. Allocation (`src/lib/codes.ts`) is a
single atomic statement — no read-then-write window:

```sql
UPDATE "CodeCounter" SET "next" = "next" + 1 WHERE "scope" = 'INV:2026' RETURNING "next";
```

The returned value *is* the number just allocated; two concurrent minters get
two different rows back. First-ever allocation seeds the counter from the
**highest existing code of that kind** (legacy/seeded data keeps its numbers,
the sequence simply continues past it); the seed create races safely on the
primary key (loser catches `P2002` and falls through to the increment).

Scopes restart at `0001` each calendar year (`INV:2027` starts fresh), and the
student registry scope is the learner's **birth year** (`NY-2016-000123`), which
keeps the national-index semantics from CI3.

### 2.2 The offline path, end to end

```
device (offline)                        server (online)
───────────────                        ───────────────
take payment ──► apiPostQueueable()
                  clientId = q-{ts}-{rand}     (collision-proof, random suffix)
                  queue persists in localStorage (zustand)
                  UI: "Receipt will be issued when you reconnect"
                  NO code minted on device           │
                                                       │ reconnect
flushQueue() ── POST /api/sync {ops:[…]} ────────────►│ for each op:
                                                       │  idempotency check (see §2.3)
                                                       │  mint RCP-… server-side
                                                       │  apply business effects
                  ok ops removed from queue ◄─────────┘
```

### 2.3 Idempotency key per op kind (`/api/sync`)

| Op kind | Durable key | Mechanism |
|---|---|---|
| `payment` | `reference = "SYNC:{clientId}"` | Looked up before insert; a re-flush returns `already synced` without touching money |
| `student` | `Student.syncClientId` (`@unique`) | **Hardened in this pass** — previously the dup-check keyed on a temporary `SYNC-…` student index that was immediately upgraded to `NY-…`, so a re-flush could create a duplicate learner; now the clientId lives permanently on the row and even a racing double-flush loses on the unique index |
| `attendance` | `SYNC:{clientId}` embedded in the session's `takenByName` | Session lookup before insert |
| `marks` | (none needed) | Replace-by-value: the flush deletes and re-creates the assessment's marks — naturally idempotent, last write wins |

The offline student enrolment now also reaches full **online parity**: it
auto-bills the first-term invoice from the class fee structure (previously the
online path billed but the offline flush silently didn't).

## 3. Verification — `scripts/smoke-codes.ts` (23 checks, all green)

| Part | Claim proven |
|---|---|
| 1. In-process concurrency | 25 simultaneous `nextInvoiceNo()` + 25 `nextReceiptNo()` + 25 `nextStudentIndex()` → **zero duplicates, zero gaps**, sequence continues from the pre-existing counter |
| 2. Cross-process concurrency | 3 worker processes (own DB connections, like 3 devices flushing at once) mint 10 invoice numbers each → 30 unique, contiguous |
| 3. Year-scope isolation | `nextSeq("INV", 2027)` starts at 1 — new year, clean slate |
| 4. Payment re-flush | Same op posted twice → one receipt, invoice credited **once**, demo data untouched after cleanup |
| 5. Student re-flush | Same enrolment posted twice → one student (with real `NY-` index), first-term invoice generated at flush, `studentsOnRoll` consistent |

Run it with:

```bash
DATABASE_URL=postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public \
  bun scripts/smoke-codes.ts     # needs `bun run dev` on :3000 for parts 4–5
```

The script is self-cleaning: it snapshots and restores counters, and removes
every row it creates, so the demo database is bit-identical afterwards.

## 4. Bugs found & fixed while proving the design

1. **Offline student re-flush duplicate** (§2.3) — the old `SYNC-{clientId}`
   index was upgraded in-place, breaking the dedupe key on re-flush. Fixed with
   the permanent `syncClientId` column + unique index + `P2002` race catch.
2. **Weak clientId in the offline enrolment dialog** (`students.tsx`) used
   `q-{timestamp}` without a random suffix — two enrolments queued in the same
   millisecond would share an id. Now matches the `apiPostQueueable` scheme.
3. **Offline enrolment didn't bill** — first-term invoice now minted at flush,
   same as the online path.

## 5. Migrating CI3 legacy numbers

Because the counter **seeds from the highest existing code of its kind**, a
data import needs no special handling: load CI3 invoices/receipts with their
original codes, and the first modern-app mint simply continues past the maximum.
Two practical notes:

- The legacy format differs (`INV-{year}T{term}-…` vs `INV-{year}-NNNN`). The
  seed scan matches on the modern prefix, so imported legacy codes that don't
  share the prefix won't seed the counter — after a legacy import, create one
  modern code and check the counter, or insert the `CodeCounter` row manually
  at the legacy max.
- Codes are `@unique`, never reused (deleting an invoice does not rewind the
  counter), which fixes legacy failure mode #2 permanently.

## 6. Guarantees & known limits

**Guaranteed:** no duplicate sequential codes under any combination of
concurrent clerks, simultaneous offline flushes, crashed-and-retried flushes,
or year rollover — enforced by the atomic `UPDATE … RETURNING`, the unique
indexes, and the per-kind idempotency keys, and proven by the smoke suite.

**Assumptions / limits (deliberate):**

- **Single-writer topology.** All code minting happens on the primary app
  server (or whichever server holds the DB primary). If Nyansapo ever moves to
  multi-primary writes (e.g. regional offline servers with deferred merge),
  sequential codes must be **prefixed per site** (e.g. `INV-2026-TMA-…`) or
  replaced by UUIDv7 with a display-number mapping table. The `cuid` row IDs
  already merge safely; only the pretty numbers would need scoping.
- **Counter row contention** serialises minting per kind-year (~one row). At
  school scale (hundreds of invoices/minute worst case) this is irrelevant;
  it would only matter at platform scale.
- **Server clock** defines the year scope. A clock skew across New Year's Eve
  could in theory mint a January code into the old year's sequence —
  operationally harmless (codes stay unique), just cosmetically odd.
- **Gapless-ness** is *nearly* guaranteed: numbers are contiguous unless a
  transaction that minted a code rolls back for another reason (e.g. fee
  structure missing). For GRA-style strict gapless requirements, wrap mint +
  document insert in one DB transaction and file any rollback as a
  reconciliation event.

## 7. File map

| File | Role |
|---|---|
| `src/lib/codes.ts` | `nextInvoiceNo` / `nextReceiptNo` / `nextStudentIndex` + the atomic `nextSeq` |
| `prisma/schema.prisma` | `CodeCounter` model; `Student.syncClientId @unique` |
| `src/app/api/sync/route.ts` | Offline flush — per-kind idempotency, server-side code minting |
| `src/lib/api.ts` | Client queue plumbing (`apiPostQueueable`, `flushQueue`) |
| `src/lib/store.ts` | Persisted offline queue (localStorage via zustand) |
| `scripts/smoke-codes.ts` | The 23-check proof suite (§3) |

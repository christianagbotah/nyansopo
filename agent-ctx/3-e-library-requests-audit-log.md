# Task 3-e — Library book requests + daily-fee audit log (work record)

## Scope delivered
iEduPro-parity **book-request flow** (student/parent → librarian queue → accept/reject) and the
**daily_fee_audit_log** (every charge/payment/top-up/adjustment leaves an immutable trail with
before/after balances) in the Nyansapo Next.js 16 + TS + Prisma/PostgreSQL app at `/home/z/my-project`.

## Files changed (only these eight)
1. `src/app/api/school/[id]/ops/library/route.ts` — GET `requests` + `pendingRequests`; POST `request.accept` / `request.reject`
2. `src/app/api/school/[id]/ops/portal/route.ts` — POST `book-request.create`; GET `bookRequests` + `libraryCatalogue` (+ per-child `library` loans)
3. `src/lib/daily-billing.ts` — `logDailyFeeAudit()` helper + `walletTotalCents`/`arrearsTotalCents`; audit calls in `generateDayCharges` & `applyTransportUsage`
4. `src/app/api/school/[id]/ops/daily/route.ts` — GET `audit` payload (+`?auditStudentId=`); audit calls in pos-collect / wallet-topup / wallet-adjust / waive-charge / waive-student-day / generate-day actor pass-through
5. `src/components/modules/library-portal.tsx` — "Requests" tab (pending queue w/ Accept/Reject + decision history)
6. `src/components/modules/student-portal.tsx` — "Request a book" dialog + "My requests" card (+ fixed pre-existing broken `@/components/progress` import → `@/components/ui/progress`; it 500'd the whole app)
7. `src/components/modules/parent-portal.tsx` — new Library tab (books out + requests per child, request-on-behalf dialog)
8. `src/components/modules/daily-services.tsx` — "Daily-fee audit log" card inside the Reconciliation tab (latest 100, learner filter, before→after columns)

## API design

### Library requests
- **GET** library (perms `library.view`): `requests` = pending queue first (oldest-first), then latest
  50 handled; each row: book/student/classroom/startAt/endAt/status/handledAt/handledByName/loanId/
  createdAt/`overdue` (= ISSUED && today > endAt). Plus `pendingRequests` count (also shown as the tab badge).
- **POST `request.accept`** `{id}` (perms `library.issue`|`library.manage`): 404 unknown / 400
  already-handled / **409 "No copy of this book is available"** (checked before AND re-checked inside
  the `$transaction`); transaction creates `LibraryLoan` (issuedAt = requested startAt, or *now* when
  startAt is past; dueAt = endAt; issuedByName = acting user), decrements `book.available`, stamps
  request ISSUED + handledAt + handledByName + loanId.
- **POST `request.reject`** `{id}`: REJECTED + handledAt/handledByName (PENDING-only, else 400).

### Portal book requests
- **POST** portal `book-request.create` `{bookId, studentId, startAt, endAt}` — same session-scoping as
  the portal GET (getUser + school membership; no opsAuth — parents/students can't operate): student may
  only pass their own `studentId`, parent only a linked child → else **403**; book must be of the school
  (404) with `available > 0` → else friendly **409**; dates required with end > start (400); duplicate
  PENDING request for the same book+learner → 409.
- **GET** portal: parent view gains per-child `library` (active loans) + `bookRequests` (status badge
  data + overdue flag) and top-level `libraryCatalogue` (200 titles w/ availability — feeds the dialog);
  student view gains `me.bookRequests` + `libraryCatalogue`.

### Daily-fee audit log (DailyFeeAudit)
- `logDailyFeeAudit(db, input)` in daily-billing.ts — never throws (best-effort try/catch); derives any
  omitted before/after from current state (wallet total = prepaid + transport; arrears = Σ OWED
  DailyCharges); performedByName/Role fall back to `System`/`SYSTEM`. Actions: CHARGE | CHARGE_REVERSAL |
  PAYMENT | PAYMENT_REVERSAL | TOPUP | ADJUSTMENT | RECONCILIATION; serviceCode = service name / wallet
  name / `MULTIPLE`.
- Write points: `generateDayCharges` (per created charge; running per-learner arrears map so before/after
  are exact; actor passed from the route), `applyTransportUsage` (fare materialised / adjusted / waived /
  re-instated), `pos-collect` (PAYMENT — arrears before = pre-settlement owed, after = re-aggregated;
  wallet payments carry wallet balance before/after; serviceCode from the actually-settled charges,
  `MULTIPLE` when >1; reference = receipt no), `wallet-topup` (TOPUP), `wallet-adjust` (ADJUSTMENT,
  signed delta, reason in notes), `waive-charge` + `waive-student-day` (ADJUSTMENT, arrears reduction).
- **GET** daily (perms unchanged — `daily.view`): `audit` = latest 100 rows incl. student + classroom
  names, optional `?auditStudentId=` filter.

## UI notes
- Library "Requests" tab: queue table (book w/ shelf availability, learner+class, requested dates,
  requested at) with Accept (disabled + tooltip when 0 copies) / Reject (confirm); history table with
  ISSUED/REJECTED badges, overdue flag, handled-by.
- Student portal Library tab: two cards (books out / my requests w/ amber "waiting", emerald "issued",
  destructive "declined" + overdue badges) + `BookRequestDialog` (catalogue Select showing "N on shelf",
  0-copy titles disabled; issue/return date inputs defaulting to today/+14d; POST → toast → reload).
- Parent portal: new **Library** tab, same pattern per selected child, dialog submits for `child.id`.
- Daily-services Reconciliation tab: "Daily-fee audit log" card — learner `Select` (all learners →
  refetch with `auditStudentId`), table (when, learner+class, action pill, service, amount, wallet
  before→after, arrears before→after, by+role, notes/reference) in `max-h-[520px] nice-scroll`,
  responsive column hiding, action-coloured amounts/pills.
- Conventions: apiGet/apiPost, ModuleHeader/LoadingBlock/EmptyState/StatCard, shadcn, toast,
  fmtCedi/fmtDate/fmtDateTime, useStore, no blue/indigo, amber/emerald/destructive accents only.

## Verification (curl, dev server http://127.0.0.1:3000, school GEA `cmvycnn80000zowqnvv4wfnq9`)
Logins via `POST /api/auth` (student/parent/librarian/super @nyansapo.gh : nyansapo) → `x-user-id` header.
- `book-request.create` as student → 200 PENDING; duplicate → 409; **other learner's id as student AND
  as parent → 403**; 0-available book → friendly 409.
- Librarian GET shows queue (pendingRequests count, book/learner/class/dates); `request.accept` → loan
  created (issuedAt = future requested start), `available` 2→1, request ISSUED + loanId + handledBy
  "Efe Dzide"; `request.reject` → REJECTED + handledAt/Name; accept with 0 copies → **409 "No copy of
  this book is available"**; accept twice → 400 already handled; past-window request accepted →
  `overdue: true`.
- Audit: `generate-day` 2026-11-02 → 127 CHARGE rows w/ exact running arrears (e.g. Adwoa Sarpong
  9.00 → 14.00) + actor name/role; `pos-collect` cash → PAYMENT arrears 28.00→23.00; `wallet-topup`
  → TOPUP bal 0→20.00; `wallet-adjust` → ADJUSTMENT bal 20.00→15.00 (amount −5.00, reason in notes);
  wallet `pos-collect` → PAYMENT bal 15.00→12.00 + arrears 23.00→20.00; `?auditStudentId=` filter OK.
- Clean-up: removed throwaway test book + 127 future-dated (2026-12-07) test charges + their CHARGE
  audit rows (kept the 4 legitimate Elikem Okai TOPUP/ADJUSTMENT/PAYMENT×2 rows + generated real
  Monday 2026-11-02 charges as demo data).
- `npx tsc --noEmit` → **exit 0**; `bun run lint` → all 8 files clean (remaining repo problems are
  pre-existing in other batches' files: upload/ci3* legacy JS parse errors, reports/sms/students warnings).
- NOTE for later agents: a parallel batch was mid-edit on `reports.tsx` during this task (transient
  syntax error, since fixed by them); also student-portal.tsx had a broken `@/components/progress`
  import (pre-dating this batch) that 500'd every page — fixed here to `@/components/ui/progress`.

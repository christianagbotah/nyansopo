# Task 7 — Early-Years (Crèche) Assessment System + SBA Marks Config (work record)

## Scope delivered
GES NaCCA early-years developmental-domain assessment (rating scale, domains & skills,
per-learner rating entry, crèche report generation + print sheet) and the SBA marks
config parity batch (four fixed SBA columns + weights + portfolio auto-fill) in the
Nyansapo Next.js 16 + TS + Prisma/PostgreSQL app at `/home/z/my-project`.

## Files changed/created (only these)
1. `src/app/api/school/[id]/ops/academics/route.ts` (extended — all existing
   portfolio/rawgrades/cumulative/papers/marks-sms actions untouched)
2. `src/components/modules/gradebook.tsx` (added "SBA sheet" + "Crèche ratings" tabs)
3. `src/components/modules/settings.tsx` (added "Early years" + "SBA marks config" tabs)
4. `src/components/modules/reports.tsx` (Report type toggle Academic|Early-years +
   early-years generate/print flow; academic flow untouched)
5. `src/components/report-sheets.tsx` (added CrecheSheet print component)
6. `prisma/seed-creche.ts` (NEW — seed.ts untouched)
7. `scripts/smoke-creche-sba.ts` (NEW)

## API design (ops/academics — GET accepts `?action=` or `?tab=`)
- **GET `action=creche-setup`** → `{ scale, domains(with skills) }`; default-seeds
  EX/GD/SD/NA + the five NaCCA domains (Communication & Language, Physical & Motor,
  Social & Emotional, Cognitive / Discovery, Creative Arts — 24 skills) when empty.
- **GET `action=creche-ratings&classroomId&termId`** → `{ scale, domains, students, ratings{"studentId_skillId": code} }`.
- **GET `action=sba-config`** → `{ config }` (defaults 20/10/20/10 + 50% + 30%).
- **GET `action=sba-sheet&classroomId&subjectId&termId`** → grid: students with existing
  scores per SBA column + exam, matched by title convention `SBA Class Test · <term name>`.
- **POST per-action perms**: `creche-scale-save/-delete`, `creche-domain-save/-delete`,
  `creche-skill-save/-delete`, `sba-config` → `settings.manage`; `creche-ratings-save`,
  `sba-sheet-save`, `sba-portfolio-fill` → `gradebook.enter`; `creche-report-generate`
  → `reports.publish` (same gate as the academic generator).
- `creche-ratings-save` batch-upserts by the `studentId+termId+skillId` unique
  (`code:null` clears/deletes); validates code ∈ scale, learner actively enrolled.
- `creche-report-generate` deletes+recreates ReportCards (like the academic generator):
  one ReportCardResult per active domain — `subjectName`=domain, `grade`=dominant code
  (mode, tie → better scale position), `remark`=scale label, `position`=rated skill
  count, class/exam/total null; classSize set, teacher/head remarks by overall dominant.
- `sba-config` upserts six SchoolSetting rows (`sba.classTestMax` etc., JSON-encoded).
- `sba-sheet-save` find-or-creates 5 assessments (Class Test→EXERCISE/20, Group
  Work→GROUP_WORK/10, Test 2→QUIZ/20, Project→PROJECT/10, SBA Term Exam→END_OF_TERM/100,
  titles suffixed `· <term name>`) then upserts Marks (clamped to max; null clears).
- `sba-portfolio-fill` computes per-learner portfolio term average % (entries inside
  term.startsAt..endsAt, deduped per learner per day by latest createdAt) ×
  `sba.portfolioAutoFillPct` / 100, clamped to classTestMax; persists into the Class
  Test assessment; skips non-empty cells unless `overwrite: true`.

## UI
- **Settings → Early years**: rating-scale rows (inline label/order edit, add, delete,
  dup-code 409) + domain list (expandable, active checkbox, rename dialog, delete with
  cascade) with per-domain skill CRUD. Loads via creche-setup (auto-seeds first time).
- **Settings → SBA marks config**: four numeric maxima with live sub-total badge
  (green at 60, amber warn + explicit "proceed" checkbox otherwise), class-score weight
  %, portfolio auto-fill weight %, effective-split preview, save via sba-config.
- **Gradebook → Crèche ratings**: domain selector (one domain at a time — narrow
  tables, no horizontal scroll), per-cell rating Select (scale codes + "—"), rated-x/y
  counter, per-learner Save + Save all (dirty tracking + saved badge), early-years
  classroom hint (name pattern; no hard filter). Term = current gradebook term.
- **Gradebook → SBA sheet**: subject select; columns Class Test/Group Work/Test 2/
  Project (clamped inputs) + auto Sub-total, Class score (= subtotal × weight ÷
  actualMax, capped), Exam raw /100, Exam score (= exam × (100−weight)/100), Total;
  "Auto-fill class test from portfolio" + overwrite checkbox; Save persists as
  ordinary assessments (also visible under the Assessments tab).
- **Reports**: "Report type: Academic | Early-years" toggle in the cards toolbar.
  Early-years generate calls `creche-report-generate`; single-card viewer and
  whole-class bulk print render the CrecheSheet (per-skill codes from the ratings
  grid, domain summaries + remarks from the stored card); remark/conduct editor and
  publish buttons reused. Academic flow byte-identical in behaviour.
- **CrecheSheet** (report-sheets.tsx): school letterhead, learner bio strip, proficiency
  legend (code → label), one bordered table per domain (skill rows + code badge chips,
  header shows dominant code + n-of-m rated), conduct, teacher/head remarks,
  attendance, triple signature lines, OFFICIAL/PROVISIONAL status, Kente strip.

## Seed — `prisma/seed-creche.ts` (idempotent)
GEA: upserts the 4-code scale, creates the 5 domains/24 skills when none exist, and
seeds deterministic ratings (biases 0.72/0.55/0.62/0.38/0.48 over EX/GD/SD/NA) for the
first 5 learners of KG 2 — Rose for the current term — **120 ratings**. Re-run skips.
Applied; verified idempotent (re-run after full reset rebuilt exactly 120).

## Smoke results — `scripts/smoke-creche-sba.ts`
**64 passed, 0 failed** (head = SCHOOL_ADMIN, teacher = TEACHER, super = SUPER_ADMIN):
(a) creche-setup payload; (b) scale/domain/skill CRUD round-trip + dup-code 409 +
delete + DB absence; (c) rating saved twice → exactly one row per student+term+skill,
code change updates in place, unknown code 400; (d) grid GET returns saved code;
(e) sba-config save/read-back (weight 40 → restored 50) + 6 SchoolSetting rows;
(f) sheet save creates the 5 assessments with exact types/maxScores (EXERCISE/20,
GROUP_WORK/10, QUIZ/20, PROJECT/10, END_OF_TERM/100) + marks; formulas recomputed
(subtotal 50/60 → class 41.67 ≤ 50, exam 72 → 36 = exam/2 at weight 50, total 77.67);
sheet GET round-trip; (g) portfolio fill against DB-recomputed expectations
(day-deduped avg% × 30% clamped /20), empty-cells-only default + overwrite replaces;
(h) report generate: one card per enrolled learner, one row per domain, grade = mode
(recomputed from DB), remark = scale label, position = rated count, no scores;
(i) teacher CAN rate, teacher CANNOT scale/domain/sba-config (403), super can generate.
Cleanup removes: SBA assessments+marks, KG report cards+results, test rating,
sba SchoolSettings — seeded ratings re-restored to 120 afterwards.

## Verification
- `npx tsc --noEmit` → 0 errors project-wide (a transient `@lib/format` typo from the
  concurrent payroll agent briefly 500'd all routes; waited per protocol — resolved
  by that agent at 06:49; dev server also died once and was restarted with
  `nohup bun run dev > dev.log 2>&1 &` per instructions).
- `bun run lint` → no issues in owned files (remaining errors are pre-existing legacy
  CI3 JS under upload/ & scripts/ci3app).
- Dev server :3000 healthy (`/` 200, `/api/context` 401 unauthenticated).

## Divergences + known gaps
- GET `creche-setup` seeds defaults on first load (task asked for upsert-on-first-load);
  the seed script does the same so either entry point works.
- The rated-skill count on crèche report rows is stored in `ReportCardResult.position`
  (an Int field) rather than a new column — no schema changes allowed; remark stays
  the pure scale label as specified.
- `creche-report-generate` gates on `reports.publish` (TEACHER has it, matching the
  existing academic generator's gate) rather than settings.manage.
- SBA column → Assessment type mapping chosen as Class Test=EXERCISE, Group
  Work=GROUP_WORK, Test 2=QUIZ, Project=PROJECT, exam=END_OF_TERM (task fixed only
  the exam type); documented in code and verified by the smoke test.
- The academic terminal-report generator (`[resource]/route.ts`, not owned) sums ALL
  non-END_OF_TERM assessments as class work at 30/70, so SBA sheet rows feed those
  totals too — consistent, but a school mixing SBA sheets with ad-hoc exercises will
  see both in the 30% bucket. Not changed (out of scope).
- Exam-score formula generalised as `exam × (100 − weightPct) / 100` (task directive),
  i.e. legacy weight 50 → exam/2. The SBA sheet's live computed columns match the API.
- Crèche ratings tab lists every classroom (hint badge for KG/creche-style names) —
  no hard filter, per spec.
- Reports "Early-years" mode shows cards for whichever class is filtered; domain rows
  render only for classrooms whose crèche ratings/grid can be fetched (else skipped).

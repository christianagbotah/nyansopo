# Task 3-c — Academic parity batch (work record)

## Scope delivered
Portfolio assessment (weekly GES SBA grid), cumulative multi-term reports, WAEC raw-score
grading (A1–F9 CRUD), question-paper bank and exam-marks SMS blast — iEduPro/CI3 parity —
in the Nyansapo Next.js 16 + TS + Prisma/PostgreSQL app at `/home/z/my-project`.

## Files changed (only these five)
1. `src/app/api/school/[id]/ops/academics/route.ts` — full API (replaced stub)
2. `src/components/modules/gradebook.tsx` — added "Portfolio (weekly SBA)" tab (module
   restructured into Tabs; existing assessments flow byte-identical in behaviour)
3. `src/components/modules/settings.tsx` — added "WAEC grading" tab (settings converted to
   controlled tabs; all existing tabs untouched)
4. `src/components/modules/reports.tsx` — added "Cumulative" + "Question papers" tabs
   (report-card actions moved inside the cards tab; viewer dialogs & bulk print unchanged)
5. `src/components/modules/sms-center.tsx` — added "Marks SMS" tab (all existing tabs intact)

## API design (ops/academics)
- **GET** perms any-of `["gradebook.view","reports.view","settings.manage","sms.view"]`.
  - default → `{ classrooms, subjects, currentTerm{id,name,yearName}, portfolioEnabled, grades }`
  - `?tab=portfolio&classroomId&subjectId&week` → 5 weekday columns (date/label/strandCode/past),
    per-learner rows with `{date: score}` map, `portfolioEnabled` (OpsSetting `PORTFOLIO_ASSESSMENT === "1"`).
  - `?tab=rawgrades` → grades + CI3 stats (total / highest= max markUpto / lowest = min markFrom /
    **passMark = min markFrom among grades with markFrom ≥ 50, fallback 50** — raw_score_grade.php parity).
  - `?tab=cumulative&classroomId[&studentId]` → without studentId: `{learners}`; with: one column-group
    per term (every term that has assessments for the class + always the current term, ordered by
    year.startsAt then sequence), per-subject **total = the reports module's exact 30/70 composition**
    (class work 30 + END_OF_TERM 70; single-component fallback), grade from RawScoreGrade
    (fallback `gradeFor` curriculum scale), `numeric` (gradePointNumeric) per subject, term average +
    averageGrade, `cumulative {average, grade, termsCounted}`, and `waecAggregate` = Σ gradePointNumeric
    over the CURRENT term's subjects (BECE rule: red ≥ 15, else green).
  - `?tab=papers` → papers (title/class/exam/author/updatedAt/body) + classrooms.
  - `?tab=marks-sms&classroomId[&assessmentId][&receiver=guardian|learner]` → exam select
    (MID_TERM/END_OF_TERM of the current term, **fallback: all assessments**, labels
    `title · type — subject`), per-learner previews, withPhone/withoutPhone counts.
- **POST** per-action perms (any-of): `portfolio.save`/`portfolio.aggregate` → `["gradebook.enter"]`;
  `rawgrade.*` → `["settings.manage"]`; `paper.*` → `["classes.manage","reports.publish"]`;
  `marks-sms.send` → `["sms.send"]`. Unknown action → 400.
  - `portfolio.save` {classroomId, subjectId, week, days[{date,strandCode}], rows[{studentId,
    scores{date}}]} — upserts PortfolioEntry per learner×day, **matched on the whole calendar day**
    (seeded rows carry a time-of-day in entryDate, so exact-timestamp upsert would duplicate).
    Empty/invalid score → 0 (CI3 keeps every cell). Caps 7 days × 200 learners.
  - `portfolio.aggregate` {classroomId, subjectId, week} — CI3 `test1 = round(((total×10)/numRows)×0.2, 2)`
    over days with score>0; create-or-update Assessment type PROJECT, title `Portfolio SBA {week}`,
    maxScore 20, current term, date = Monday of the week; Mark per learner `min(20, test1)`
    (deleteMany+create, so re-running recomputes). Returns computed rows. Duplicate same-day entries
    deduped by latest createdAt.
  - `rawgrade.create/update/delete` — validation: name+gradePoint required, numeric marks,
    markFrom ≤ markUpto (400 otherwise); school-scope 404s.
  - `paper.create/update/delete` — title+classroomId required; authorName defaults to the operator.
  - `marks-sms.send` — reuses `sendSms` from `src/lib/sms` (same gateway/credits/status mechanism as
    the sms-center), category **RESULTS**, upfront credit check like `ops/sms send`.
    Receiver `guardian` (recipient = guardian) | `learner` (message addressed to the learner,
    delivered to the phone on file — Student model has no own phone field). Returns
    `{sent, failed, skipped, total}`.
- **CI3 message format** (Admin.php exam_marks_sms, exact):
  `Year|Term: {yearName}|{termName} Exam Scores of {learner}:\n` then
  `{Subject} {total}% {gradePoint} {TitleCase(remark)}` joined with ", " — including CI3's subject
  abbreviations (Mathematics→Maths, Integrated/Natural→Science, Religious→R.M.E, Information→ICT,
  Our→O.W.O.P, Creative→C.A, Health→H&Safety, Citizenship→C.E, Physical→P.E). Grade from
  RawScoreGrade `markFrom ≤ total ≤ markUpto`. Specific assessmentId → single subject line
  (score/maxScore %); no assessmentId → composite 30/70 term totals per subject.

## Notable implementation decisions
- **Week-label vs dates**: the parity-2 seed stamps week `2026-W42` on Oct 19–23 (naive week-number
  formula), while strict ISO W42 = Oct 12–16. The portfolio GET anchors the grid on the RECORDED
  entries' Monday when they differ from the strict ISO Monday (data wins), else strict ISO. The UI's
  default week = most recent completed week via the same naive formula, so it opens straight onto the
  seeded data.
- Gradebook module keeps a single class select in the header; the Portfolio tab reuses it (plus its
  own subject + `input type="week"`), scores 0–10 step 0.5, per-day strand inputs **readonly for past
  days**, live avg /10 column, aggregate results table after running the aggregation.
- All new tabs honour `navTarget?.tab`: `portfolio` (gradebook), `waec` (settings),
  `cumulative`/`papers` (reports), `marks` (sms) — same render-time-adjust pattern as attendance.
- Cumulative sheet + question-paper view are wrapped in `.print-area` / `.print-sheet` (global print
  CSS prints only them); bulk class print fetches every learner's payload and stacks sheets.
- Settings: WAEC tab = 4 StatCards (CI3 stats) + CRUD table + add/edit dialog + AlertDialog delete.
- No schema/rbac/store/app-shell/package.json changes.

## Verification (all passed — curl + headless browser)
Login `super@nyansapo.gh` (GEA school `cmvycnn80000zowqnvv4wfnq9`):
- Bootstrap GET: 11 classrooms, 12 subjects, currentTerm First Term 2026/2027, portfolioEnabled true, 9 grades.
- **Portfolio GET** Basic 4 — Gold · Mathematics · 2026-W42: 12 rows, 10 scored learners, 50 scores,
  days Oct 19–23, strands B4.1.1.1…B4.2.1.2, all past (locked).
- **portfolio.save** round-trip: updated a legacy-timestamp row (Yao Mon 4→8 → GET shows 8) and
  created entries for an unscored learner (nulls stored as 0); duplicate-row bug from exact-timestamp
  upsert found & fixed (day-range match), test duplicates cleaned, state restored (50 entries).
- **portfolio.aggregate**: created PROJECT `Portfolio SBA 2026-W42` max 20 with 11→ marks; Yao
  avg 7.8 → class score **15.6/20** = ((39×10)/5)×0.2 exactly; second run updates (no duplicate).
  After data restore: 10 marked, Yao 14/20 — and the assessment feeds the reports 30/70 totals
  (Maths total for Yao changed accordingly).
- **rawgrades**: 9 seeded grades + stats {total 9, highest 100, lowest 0, passMark 50}; create →
  stats total 10; update (A+ → A*, 95–100) ok; `markFrom > markUpto` → 400; delete → back to 9.
- **cumulative** (Yao Asante): 4 subjects (English 62 C4, Science 77 B2, Maths A1/81 pre-restore,
  OWOP 77 B2), term average 74 B2, cumulative 74 (1 term), waecAggregate 9 (green).
- **papers**: 3 seeded; create (Basic 4 test paper) → 4 listed first; update changed title+body;
  delete → 3. TEACHER (has classes.manage) can create; then deleted.
- **marks-sms**: exam select falls back to all 17 assessments (no MID/END for Basic 4); composite
  guardian preview = `Year|Term: 2026/2027|First Term Exam Scores of Yao Asante:\nEnglish 62% C4
  Credit, Science 77% B2 Very Good, Maths 81% A1 Excellent, O.W.O.P 77% B2 Very Good`; single-exam +
  learner receiver preview verified (`Maths 78% B2 Very Good`); **send → `{sent:12, failed:0,
  skipped:0}`** with 12 `SmsMessage` rows created (category RESULTS, status SENT, gateway credits
  debited 4820→~4796).
- Negative: no auth header → 401; unknown action → 400 `{"error":"Unknown action"}`; TEACHER
  rawgrade.create → 403 missing permission; CASHIER GET → 403; CASHIER marks-sms.send → 403.
- Browser (agent-browser): Gradebook → Portfolio tab renders the seeded grid (strand inputs locked,
  SBA-enabled badge, week defaults to 2026-W42); Assessments tab shows `Portfolio SBA 2026-W42`;
  Settings → WAEC grading renders stats + A1…F9 table; Reports → Cumulative renders Yao's sheet
  (term column group + WAEC column + footers) and Question papers lists the 3 seeded papers with a
  working view dialog (SECTION A visible, Print paper button); SMS → Marks SMS preview shows the
  first 3 CI3 messages. Zero page errors; dev.log all 200s.
- `npx tsc --noEmit --incremental false` → **exit 0**. `eslint` on the five changed files →
  **0 errors** (1 pre-existing unused-disable warning in sms-center.tsx's untouched NoticeboardTab).
  Repo-wide lint failures are the pre-existing `upload/`/`scripts/ci3app` legacy PHP-app JS parse
  errors.

## Demo data state after testing
- Portfolio maths W42 back to the seeded 50 entries (Yao Monday restored to 4).
- `Portfolio SBA 2026-W42` PROJECT assessment (max 20, 10 marks) kept — coherent demo of the
  aggregation feeding the gradebook/reports.
- 12 RESULTS SmsMessage rows kept (realistic blast history); rawgrades back to 9; papers back to 3.

## Notes for later agents
- `PortfolioEntry.entryDate` may carry a time-of-day (seed artefact). Any new write path must match
  on the calendar day (gte/lte), never on an exact timestamp, or the compound unique will duplicate.
- The week label convention is the seed's naive formula, not strict ISO — the GET self-heals by
  anchoring on recorded data; if you re-seed, keep the formula in `seed-parity2.ts` as-is.
- Marks-SMS "learner" receiver uses `guardianPhone` (Student has no own phone field); recipientName
  is the learner. If a `Student.phone` column ever lands, swap the delivery number.
- GET `?tab=cumulative` returns `{learners}` without studentId — used by the class/bulk select.

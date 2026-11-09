# Wave 9 — Metro sizing, GES curriculum framework, multi-curricula, lesson-note alignment

## User asks (this wave)
1. Metro menu cards too big → reduce to CI3 sizes
2. Implement admin/teacher views as in CI3 with enhanced features
3. Lesson notes in GES curriculum
4. Multi-curriculum support (Datus International, Tema International etc. run several curricula)

## What shipped

### 9.1 Metro tile sizing — CI3-exact (d643c6f)
CI3 `metro_menu.php`: `.metro-tile { width:150px; height:150px; padding:15px }`, icon 60px,
label 12px; `@media 768px` 120px/45px/10px; group grid `repeat(2,150px)` gap 10;
content grid `repeat(auto-fit,minmax(320px,1fr))` gap 30.
Our tiles were fluid `aspect-square w-full` (~190px in 4-col sections). Now fixed
`size-[150px]` (`max-md:size-[120px]`), icon `size-[60px]` (45 mobile), label `text-xs`
(10 mobile), group grid `repeat(2,150px)`, sections `repeat(auto-fill,minmax(320px,1fr))`.
**Browser-verified: tile bounding box exactly 150×150, icon 60px, 42 tiles render.**

### 9.2 Curriculum framework library (schema 332da5b, seed 9878b3b, API a61efc2, UI 3eebad4)
- `CurriculumStrand → CurriculumSubStrand → CurriculumContentStandard → CurriculumIndicator`
  shared library keyed by (curriculum, subjectName, gradeLevel) — replaces CI3's four
  separate admin pages (curriculum_strands/sub_strands/content_standards/learning_indicators)
  with ONE Framework tab + CSV import (curriculum_import.php parity, quoted-cell aware).
- Seed `prisma/seed-framework.ts` — REAL verified taxonomy:
  - GES Maths B1–B9 (4 strands; B7–B9 "Data & Probability"), English (Oral Language,
    Reading, Grammar Usage, Writing, Literature), Science (5 strands, verified
    sub-strands), Computing (4), RME (4), Ghanaian Language, Creative Arts & Design
  - Content standard exemplar B4.1.1.1 + indicator B4.1.1.1.1 (NaCCA, place value ≤ 100,000)
  - Cambridge Primary Maths/English/Science Stage 1–6 (Number/Geometry/Measure/
    Handling data/Problem solving…), IB PYP Unit of Inquiry PYP1–6 (6 transdisciplinary
    themes × 7 key concepts)
  - 291 strands · 168 sub-strands · 51 standards · 9 indicators · 63 subject×grade frameworks
- API `ops/curriculum-framework`: GET ?meta=1 (enabled curricula + coverage),
  GET tree, POST strand/sub/standard/indicator .save/.delete, framework.import (CSV upsert).
  Subject names canonicalised case-insensitively against the school's subjects.

### 9.3 Multi-curricula schools (c15bdf0)
- `School.curricula` JSON array (primary = first, synced into `curriculum`).
- Settings → Profile: GES/Cambridge/IB tap-cards with Primary badge; validation
  server-side (`update-school` accepts curricula, filters via isCurriculumKey).
- Demo: SGI ["CAIE","GES"], SES ["IB","GES"], BFI ["CAIE"], GEA ["GES"] — matches
  Datus/Tema International pattern (national track alongside Cambridge/IB).
- `src/lib/curricula.ts`: labels, stage bands, NaCCA core competencies (6),
  Cambridge learner attributes (5), IB learner profile (10), frameworkGrade() mapping.

### 9.4 Framework-aligned lesson notes (f3d5fcf)
- LessonNote + strandId/subStrandId/contentStandardId (+relations), indicatorIds JSON,
  coreCompetencies, tlm, assessment. note.copy carries alignment.
- Note editor: "Curriculum alignment" block — curriculum select (school's curricula),
  strand → sub-strand → content standard cascading selects, indicator checkboxes,
  **"Use as objectives"** pours `B4.1.1.1.1 — …` lines into the objectives box.
- Competency chips per curriculum + custom competencies + TLM + assessment fields.
- Server validates the whole chain (sub-strand must belong to strand etc. — mismatch → 400).
- Print view header shows curriculum · strand › sub-strand · standard code; Competencies/
  TLMs/Assessment panels print when present.

### 9.5 Verification
- `scripts/smoke-framework.ts` — 13 checks: meta, tree, CRUD, CSV import, aligned note
  save, chain-mismatch rejection, cleanup. **13/13.**
- Full regression: **20/20 smoke suites, 0 failures.**
- Browser (prod build, head teacher @ GEA): metro 150×150/60px icon; Framework tab tree
  (4 strands/8 sub-strands/B4.1.1.1 chain) with Add strand/Import CSV/Copy template;
  note dialog cascading pickers → indicator tick → "Use as objectives" fills objectives;
  competency chip toggles; Settings curricula chips toggle + save + persist (GEA
  round-trip Cambridge on→off verified in DB).
- Screenshots: shot-framework-tree.png, shot-note-alignment.png, shot-settings-multicurricula.png

## Commits
d643c6f metro sizing · 332da5b schema · 9878b3b seed · a61efc2 API · 3eebad4 Framework tab ·
f3d5fcf lesson-note alignment · c15bdf0 settings multi-curricula · c7d21ac smoke suite

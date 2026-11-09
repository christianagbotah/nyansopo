# Task 4 — Curriculum & lesson notes (work record)

Implemented the iEduPro teacher-portal curriculum layer (lesson notes lifecycle,
scheme of learning, study material, teacher completion dashboard, assessment
graphs) in the Nyansapo Next.js 16 + TS + Prisma/PostgreSQL app at
`/home/z/my-project`. Commits `2a0e8c9` (backend) + `3490050` (UI/seed/tests).

## Scope delivered (CI3 parity references)
- `Teacher.php` lesson_note_* (create/edit/copy/templates/progress/print),
  `Hod.php` review flow (endorse / request_revision / history),
  `Teacher_completion.php`, `academic_syllabus`, `study_material`,
  admin `lesson_notes_pending` / `lesson_notes_compliance`, `assessment_graph`.

## Files changed
1. `prisma/schema.prisma` — 5 new models: `LessonNoteTemplate`, `LessonNote`
   (+`LessonNoteReview` log, cascade), `StudyMaterial`, `SyllabusTopic`;
   back-relations on School/Classroom/Subject/Staff/Term.
   LessonNote unique: `[staffId, classroomId, subjectId, termId, weekNumber]`.
2. `src/lib/rbac.ts` — perms `curriculum.view|manage|review` (Academic group),
   implies manage/review→view; TEACHER defaults += view+manage; MODULE_PERMS
   `curriculum`. `src/lib/store.ts` — ModuleId += "curriculum".
3. `src/app/api/school/[id]/ops/curriculum/route.ts` — GET overview/syllabus/
   materials + POST note.save|delete|copy|review, template.save|delete,
   syllabus.save|delete, material.save|delete.
4. `src/components/modules/curriculum.tsx` — the module (6 tabs).
5. `src/components/modules/gradebook.tsx` — Analytics tab (recharts).
6. `src/components/app-shell.tsx` — nav entry + 5 children + render case.
7. `prisma/seed-curriculum.ts`, `scripts/smoke-curriculum.ts` (29 checks),
   `scripts/smoke-portals.ts` += curriculum endpoint.

## Design decisions
- **Review workflow** mirrors CI3: teacher submits (PENDING) → reviewer with
  `curriculum.review` (SCHOOL_ADMIN/SUPER_ADMIN/GROUP_OWNER via "*" defaults)
  endorses (ENDORSED, immutable — edit returns 409 "copy it instead") or returns
  with mandatory feedback (REVISION_REQUIRED) → teacher's next save flips it
  back to PENDING as RESUBMIT with version+1. Every transition writes a
  `LessonNoteReview` row (CREATE/SUBMIT/UPDATE/RESUBMIT/ENDORSE/REQUEST_REVISION).
- **Ownership**: note.save/update/delete require `staffId === user.staffId`
  (except reviewers may delete); materials edit/delete are author-or-reviewer.
  Review action requires the `curriculum.review` perm — teachers get 403.
- **GET overview scoping**: reviewers/admins see ALL notes; plain teachers only
  their own (`staffId: myStaffId ?? "none"` guard). `canReview` flag drives the
  Review-queue tab. Badges: DRAFT/PENDING/ENDORSED/REVISION_REQUIRED.
- **Completion stats** (Teacher_completion parity): per teaching staff —
  class-subject pairs taught (from this term's notes), notes by status,
  distinct weeks with a note vs `weeksElapsed(term)` → compliance %,
  syllabus coverage % over topics in their class-subject pairs. CSV export.
- **Syllabus topics**: unique `[classroomId, subjectId, termId, weekNumber, topic]`;
  the taught-toggle POSTs with `id` only → plain update (an upsert's create
  branch would demand termId and Prisma rejects `undefined` — bug caught in
  smoke, fixed by splitting update/upsert paths).
- **Assessment graphs** are pure client-side derivations from the existing
  gradebook payload (subject averages, distribution bands 0–20…91–100,
  per-learner trend, top/needs-support lists) — no API change.
- Dialogs use wrapper+key+`useState(initializer)` instead of effect-setState
  (repo eslint rule `react-hooks/set-state-in-effect` is an error).

## Verification (all passed)
- `scripts/smoke-curriculum.ts` — 29/29: admin sees 12 notes + queue, teacher
  sees own 9 only; create→duplicate 409→week-16 400→return (no-comment 400)→
  teacher-review 403→revise→endorse→edit-endorsed 409; syllabus toggle ±1
  coverage; material/template CRUD + ownership; smoke rows cleaned up.
- `scripts/smoke-portals.ts` — SCHOOL_ADMIN 24 ok / TEACHER 24 ok (curriculum
  200 for both — teachers legitimately view it, so NOT in ADMIN_ONLY).
- `npx tsc --noEmit` exit 0; eslint clean on all changed files.
- agent-browser: head (SCHOOL_ADMIN) — nav group + 5 children render, notes
  table (12 rows), Review queue (3 cards w/ Endorse), Scheme of learning
  (Basic 4 — Gold: 18/30 taught, 30 switches, live toggle 18→17→18), Completion
  (Isaac 9 notes · 7/8 wk 88% · 12/20 syllabus); teacher — no Review-queue tab,
  no Reviewer badge, only own notes. Zero page errors.
- Demo data restored to seeded baseline after testing (12 notes, 52 topics /
  32 taught, 4 materials, 6 templates, no SMOKE leftovers).

## Notes for later agents
- `weeksElapsed()` clamps to the term length and 1-indexes — week 8 of a
  14-week Sep–Dec term at the demo "today".
- Endorsed notes are immutable BY DESIGN (GES audit trail); the copy action
  seeds a DRAFT for the next week with `sourceNoteId` lineage.
- The lesson-note print view uses the repo's `.print-area` CSS (globals.css
  @media print) inside the view dialog — only the note area prints.
- If `.env` or the shell `DATABASE_URL` regresses to SQLite after a sandbox
  recycle, re-run: `scripts/pg/start-postgres.sh` path initdb →
  `DATABASE_URL=postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public`
  → `bunx prisma db push` → seeds (seed.ts chains seed-ops; then classes,
  daily-ext, fare-parity, exams, parity2, discount-profiles, ops-extra, rbac,
  curriculum). Restart `bun run dev` AFTER rbac/perm changes — dev-mode chunk
  caching can serve stale permission catalogues (cost us a phantom 403).

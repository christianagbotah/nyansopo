# wave 66 — AI Exam Paper Studio

The headline feature a spreadsheet-era school cannot copy: the teacher types
subject + class + topics, and the AI drafts a COMPLETE Ghanaian exam paper —
NaCCA SBC style, Ghanaian context — with a marking scheme. One generation,
two destinations:

- **Save printable paper** → QuestionPaper row (school header, SECTION A
  objectives with A–D + answer key, SECTION B theory with mark allocations,
  examiner answer guides) — view/print from Exams & Reports → Question papers.
- **Save as online exam** → OnlineExam DRAFT carrying Section A as auto-marked
  MCQs (theory stays in the printed paper) — publish-ready for CBT.

## Verified live (Golden Excellence Academy, head teacher)

- Input: Mathematics, Mock BECE, topics = fractions, ratio & proportion,
  simple interest in cedis, angles on a straight line.
- Draft: 10 objectives (10 marks) + 2 theory (15 marks) = 25 marks / 45 min.
- Real AI quality: "Kwame added 2/5 and 3/7. What is the sum?" MCQs; a
  ₵4,800 shared-inheritance word problem with an 8-mark allocation guide;
  angles-on-a-straight-line equation solved stepwise in the answer guide.
- QuestionPaper row saved: 4.3KB HTML with MARKING SCHEME section, author
  Madam Grace Mensah.
- OnlineExam DRAFT saved: 10 MCQs, 10 total marks, ready to publish.
- Honest failure mode: when the AI service is unreachable the studio returns
  a clear retry message instead of a junk template paper.

## Code

- `src/app/api/school/[id]/ops/exam-studio/route.ts` — generate (strict-JSON
  AI draft with schema validation + repair) and save-paper (A4 HTML render,
  audit trail).
- `src/components/modules/online-exams.tsx` — "AI Paper Studio" tab: form,
  draft review (correct answers highlighted, marks per question), include-
  scheme switch, both save actions.
- page-index entry: "AI Exam Paper Studio" (module exams, tab studio).

## Ops notes for the day

- Sandbox recycled again at ~23:30 — safety-net + `scripts/pg/restore-cluster.sh`
  restored the 10-school cluster; `prisma db push` re-synced Homework /
  HomeworkSubmission / SubstitutionLog.slotId after the snapshot (taken at
  16:00) predates them.
- turbopackMemoryLimit raised 768 → 1280: three silent mid-compile deaths of
  `next dev` at 768 after the app-shell grew; 1280 completes with headroom.
- git history diverged during the recycle (local rewound to wave 62 while
  origin carried waves 63/64): resolved by adopting origin/main and re-applying
  the working tree as wave 65 (`e0b08a3`).

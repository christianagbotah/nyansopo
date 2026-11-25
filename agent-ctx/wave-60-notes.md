# Wave 60 — Siren custom tones · Office downloads · Professional suite · AI-assisted writing

**Date:** 25 Nov 2026 · **School:** Golden Excellence Academy (demo)

## What the user asked for
1. Siren: users should be able to **upload their own songs or sounds**.
2. Office suite: a **download button for each file**.
3. **Professional suite** for Word documents — ruler, header, footer, page
   numbers — and other advanced features for all three editors.
4. **Fully AI-assisted writing and corrections**.

## What shipped

### 1. Siren — uploaded tones (own songs / sounds)
- New `SirenTone` Prisma model: label, mime, size, default ring length, bytes
  in Postgres (12 MB cap). Sound ids are `CUSTOM:<toneId>` and work
  everywhere a built-in sound does.
- `POST /ops/siren` multipart `tone.upload` (+ `tone.update`, `tone.delete`),
  `GET ?tone=<id>` streams the audio (session + school scoped, cacheable).
- `siren-sounds.ts`: `playToneBuffer()` decodes + loops uploaded audio with a
  fade-out tail; `playSchoolSound()` is the one entry point — custom tones
  fall back to the classic bell if missing so a timetabled ring never fails.
- Siren module: new **My tones** tab (upload card + management table with
  test-ring / rename / delete), custom tone cards in the live sound board,
  uploaded tones selectable in the add/edit ring dialog, and the global
  `SirenTicker` plays them from any page.
- Verified: WAV uploaded → streamed (audio/wav, 32 KB) → hung on the 07:50
  timetable ("School anthem (test) · 15s") → TEST ring logged.

### 2. Office — a download button for every file
- Each file card has a hover download button + a dropdown "Download (…)" item
  — no need to open the file. Formats a school computer can actually open:
  - DOC → **Word .doc** (real Word HTML with mso header/footer + PAGE/NUMPAGES
    fields) and standalone .html from the editor toolbar
  - SHEET → .csv (computed values)
  - SLIDE → printable deck .html (one page per slide, speaker notes included)

### 3. Professional suite (Word)
- **Ruler** — A4-width ruler above the page, ticks every 0.5 cm, numbered
  each cm, draggable margin markers that live-drive the page padding.
- **Header & footer** — text zones on the page (click to edit), repeat on
  every printed page; stored in contentJson `layout`.
- **Page numbers** — none / "Page n" / "Page n of N"; real live Word fields in
  the .doc export ("Page 3 of 5" updates itself), fixed-position repeat in
  browser print.
- Find & replace (text-node walk — formatting preserved), insert table,
  page break, text colour + highlight, sub/superscript, insert date, status
  bar (words · characters · paragraphs · ≈printed pages · margins).
- **Sheets:** insert/delete row & column at the cursor with full cell-key
  renumbering. **Slides:** export deck button.

### 4. Fully AI-assisted writing & corrections
- New `POST /api/ai/office-assist` (school-scoped, z-ai-web-dev-sdk):
  - DOC: continue · improve · summarize · **fix grammar & spelling**
    (returns targeted changes, applied via text-node replacement so
    formatting survives) · custom instruction
  - SHEET: natural language → formula (SUM/AVERAGE/MIN/MAX/COUNT arithmetic
    only, grounded in the active column context) · explain-my-formula
  - SLIDE: topic + audience + count → full deck with Ghanaian-classroom
    speaker notes (cocoa & cassava examples out of the box)
- In-editor panels: **AI assist** (doc), **AI formula** (sheet), **AI deck**
  (slide) — preview, apply/insert/replace, copy.
- Verified live: "writting/upcomming/atend" → 3 corrections applied to the
  open document; "average of the scores in column B rows 2 to 4" →
  `=AVERAGE(B2:B4)` placed in B6 → computed 75; 6-slide Photosynthesis deck
  generated and replaced into the editor.

## Bug found & fixed during verification
- **Autosave stale-closure**: the 2.5 s debounce timer captured the render's
  `doSave`/`build`, so the very state change that scheduled a save (e.g.
  picking page numbers) could be persisted stale. The timer now calls
  `latest.current.doSave()`. Re-verified: pageNum `xofy`, header/footer and
  slide-title edits all persist after the fix.

## Ops — the recurring "preview not working" root cause
The Turbopack dev server grows to ~2.2 GB and the 4 GB sandbox OOM-kills it
every ~30–40 min of active compilation (5 kills logged today). Mitigations:
- `.zscripts/watchdog-loop.sh` — persistent 2-minute watchdog that revives
  the server (started by safety-net + on demand); survives the next-server
  OOM kills themselves.
- safety-net.sh now starts the loop on every run (8 h cron backstop).
- Note: running `bun run lint` while dev + browser are up can sweep both —
  lint before heavy browsing or accept the ~2 min watchdog revival.

## Verification (agent-browser, screenshots in agent-ctx/)
- wave60-siren-live.png · wave60-siren-tones.png · wave60-siren-timetable.png
- wave60-office-grid.png · wave60-doc-editor.png · wave60-doc-ai-fix.png
- wave60-doc-headerfooter.png · wave60-sheet-ai-formula.png
- wave60-slide-ai-deck.png · wave60-slide-editor.png
- wave60-siren-final.png · wave60-office-final.png · wave60-final-smoke.png
- Zero console errors (only pre-existing benign DialogDescription warnings,
  new dialogs all carry descriptions).

# Wave 63 — Ask Nyansapo: the school-wide AI copilot + Headteacher's Morning Brief

Date: 2026-11-25 · School verified: Golden Excellence Academy (head@nyansapo.gh / nyansapo)

## What shipped

### Backend — `ops/assistant` is now the school-wide copilot (was finance-only)
- **Domain retrievers** (`src/app/api/school/[id]/ops/assistant/route.ts`): each returns a verified
  `Section { title, lines[], module? }` grounded in live DB figures —
  - `attendanceSection` — registers taken today, rate, per-class present/absent, absentee NAMES
    (up to 10), 5-school-day trend line
  - `learnersSection` — roll count, boys/girls split, per-class counts (top 10)
  - `disciplineSection` — 30-day incidents (open/escalated), commendations, recent case names
  - `visitorsSection` — today's visits + who is on site right now (organisation, purpose, since when)
  - `staffSection` — headcount by category + birthdays this month
  - `sirenSection` — enabled rings, weekday-aware NEXT RING (today/offset days), repeating period bells
  - `termSection` — active term, week number, days to end
- **Headteacher's Morning Brief** (`buildBrief`) — all 8 sections in one Promise.all; triggered by
  brief/digest/sitrep/overview language, bare "morning" (only when not a finance question), or the
  literal prompts "brief" / "morning brief" / "good morning".
- Detection is word-boundary safe (`\bring\b` doesn't match "during"; finance still wins for money
  questions). Data union extended with `kind: "domain" | "brief"`; finance branch untouched.
- LLM system prompts per kind (brief composer with Follow-up rules ≤200 words; domain grounding
  ≤150 words; finance prompt preserved; general persona renamed to Ask Nyansapo). Deterministic
  fallback answers (`sectionsFallback`) if the AI service is unreachable — verified figures always
  render even when GLM is down.

### Frontend — global copilot consuming the dormant `aiOpen` store flag
- `owner-assistant.tsx` rewritten as `AiCopilot` (`OwnerAssistant` kept as alias):
  - Mounted ONCE in AppShell → available on every page; open state lives in the store (`aiOpen`),
    previously set-but-never-consumed.
  - Top-bar Sparkles button summons it (next to the AI Assistant module button); Esc closes;
    `aria-live="polite"` message stream; dialog role; `aria-expanded` on the FAB.
  - Empty state: hero "Compose today's Headteacher's Morning Brief" card + 8 school-wide
    suggestion chips (attendance, absentees, income, learners, visitors, discipline, siren).
  - DataChips v2: finance keeps receipt-figure chips; brief/domain answers render verified
    sections (3 lines + overflow count) with an "Open {Module} →" deep-link chip that closes the
    panel and navigates via `setModule` (attendance → Attendance register, siren → Siren, …).
  - Voice input kept (en-GH, auto-submit on end); messages persist across module navigation
    (mounted outside PageTransition).
- `dashboard.tsx` no longer mounts a dashboard-local assistant (global now); AppShell imports
  `AiCopilot` dynamically (ssr:false) and a top-bar button wires `setAiOpen(true)`.

## Ops notes
- Fresh session env: PG + dev were down (processes don't persist across sessions) — safety-net
  restored both (HTTP 200).
- `bun run db:push` re-synced `SirenSchedule.profile` (snapshot-restore drift) — same fix noted in
  wave 62; P2022 errors in dev.log are pre-fix history.

## Browser-verified (agent-browser, zero console errors)
- Signed in as head@nyansapo.gh → top-bar Sparkles opens the panel with hero brief card + 8 chips.
- **Morning brief composed live**: First Term week 12 · 22 days to end; 126 learners on roll,
  no registers yet, trend 88/85/96/88%; ₵4,928.00 today from 4 receipts (SPLIT ₵4,920 + Cash ₵8 —
  wave-59 split receipts visible in the brief); billed ₵354,524 / collected ₵225,275.04 (64%) /
  arrears ₵129,248.96; 3 visitors on site with purposes; 22 incidents (6 open, 3 escalated);
  19 staff (9 teaching / 4 admin / 6 non-teaching); 6 rings, next at 15:00 today; AI follow-ups
  cite real cases (Joshua Lawson uniform OPEN).
- **Domain Q&A**: "Who is absent today?" → grounded "No absentees recorded today" + attendance
  section + "Open Attendance →" deep link → Attendance Register module (panel closed itself).
- **Finance Q&A**: "Total income today?" → grounded ₵ answer (curl, Great Kings & Queens).
- curl-verified all three kinds (brief/domain/finance) HTTP 200 pre-browser.
- Mobile (390×844): panel 94vw, input row adapts, FAB↔X swap. Desktop 1440×900 clean.
- Screenshots: agent-ctx/w63-copilot-panel.png · w63-copilot-mobile.png · w63-copilot-deeplink-attendance.png

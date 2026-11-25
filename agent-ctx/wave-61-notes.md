# Wave 61 — Siren Pro + app-wide UI/UX modernization

**Goal:** make the siren system something schools drop a standalone bell box for, and lift the
whole app's feel (motion, transitions, modern components, accessibility) so schools already
running another school-management system want to switch to Nyansapo on sight.

## Siren Pro (the competitive bell system)

**Schema** (`prisma/schema.prisma`): `SirenSchedule` += `profile` (default "Normal Day"),
`zone` ("All Campus"), `volume` (10–100), `fadeIn` (0–5s), `repeatEveryMin`?, `endTime`?,
`skipVacation` (default true). `SirenEvent.kind` gains `ANNOUNCEMENT`. Pushed with `bun run db:push`.

**Sound engine** (`src/lib/siren-sounds.ts`): every play entry point takes
`{ volume, fadeInSec }` — gain envelope ramps in for gentle assembly starts; uploaded tones
keep the click-free tail.

**API** (`ops/siren/route.ts`):
- GET now returns `activeProfile`, `profiles` (persisted custom list in `SchoolSetting`
  `siren.profiles` + defaults + in-use), `zones` (`siren.zones`, defaults: All Campus / KG &
  Creche / Primary / JHS / Administration), `term: { inTerm, name }` (vacation awareness via
  the current Term's startsAt/endsAt). `stats.nextRing` is occurrence-aware (period bells).
- New actions: `zones.save`, `profile.activate`, `profile.create` / `profile.remove`
  (guarded — built-ins immutable, non-empty profiles refused), `bulk.apply` (AI builder +
  presets, `replace` option, transactional, 40-ring cap), `announce` (logs kind
  ANNOUNCEMENT, duration estimated from word count), `ai.timetable` (plain-English → ring
  rows incl. repeatEveryMin/volume/fadeIn, strict JSON, validated), `ai.announce`
  (occasion + details → spoken-style PA text ≤60 words), `ai.insights` (7-day expected vs
  fired vs missed computation — matches SCHEDULED events per schedule/10-min-bucket/day —
  plus LLM narrative + tips).
- `schedule.save` accepts all new fields (zone validated against the campus list).
- **Bug fixed:** `ZAI.create()` returns a Promise — all three AI actions now `await` it
  (was 500ing with "Cannot read properties of undefined (reading 'completions')").

**UI** (`src/components/modules/siren.tsx`, full Siren Pro rewrite):
- *Live console:* clock + occurrence-aware next ring; **exam & period timer** (30m–3h
  presets, start/pause, +5 min extra time, progress bar, "ring the bell when time is up"
  chime); **PA announcements** (occasion select, details, *Draft with AI*, editable text,
  Speak over the PA via SpeechSynthesis with stop, logged); emergency siren/klaxon;
  sound board with **console volume slider + 3s gentle-start switch**.
- *Timetable:* **day-profile chips** (view/activate/create/delete, Active badge);
  **AI timetable builder** (example chips: standard day / BECE exam day / sports day →
  preview table → apply with optional replace); rings table with zone/volume/repeat/
  term-time badges; add-ring dialog with profile, zone, period-bell repeat+until, volume
  slider, fade-in, skip-vacation; **campus zones manager** (add/remove zones, per-device
  "this device rings for" select — localStorage `siren-device-zone`).
- *Tones:* unchanged (wave 60).
- *History:* ANNOUNCEMENT kind filter + purple badge; **AI reliability report** card
  (expected / rang on time / missed chips, missed-ring list, narrative, tips).
- `SirenTicker` + in-console arming upgraded: `dueAt()` honours active profile, device
  zone, vacation skip and period-bell expansion; guard key includes the occurrence minute;
  per-ring volume/fade passed to the engine.

**Discovery:** `page-index.ts` adds "Siren — PA Announcements" + keywords (exam timer, day
profile, zones, reliability); `DEEPLINK_MODULES` in `src/app/page.tsx` now covers every
module (siren, office, waec, staffcpd, emis, health, clubs, …).

## App-wide UI/UX modernization

- `src/components/motion.tsx` (new): `PageTransition` (fade+rise on module switch,
  reduced-motion aware) + `CountUp` (rAF-eased number count-up).
- `app-shell.tsx`: module content wrapped in `PageTransition`; **skip-to-main-content**
  link (`#main-content`); sidebar NavNode active rail (Kente-gradient 3px bar), hover
  translate-x, icon scale on hover.
- `ui-helpers.tsx` StatCard: numeric values count up; hover lift via globals.
- `globals.css`: stat-card hover -2px lift + deeper shadow; smooth scrolling; consistent
  `:focus-visible` ring; tactile button press (scale 0.975); table-row hover tint;
  `prefers-reduced-motion` escape hatch for all CSS motion.
- `landing.tsx` + `login.tsx` (subagent task 5-a): framer-motion `MotionConfig
  reducedMotion="user"`; staged hero entrance, staggered whileInView feature cards with
  hover lift + icon nudge, floating decorative card, login card/logo springs, staggered
  demo groups. No copy/flows changed.

## Ops note (this session)

The Bash tool now kills backgrounded children at call end (setsid/nohup don't survive) —
the dev server + watchdog kept dying. **Working pattern: `( cmd & )` double-fork subshell**
orphaning the child; used to start `bun run dev` + `watchdog-loop.sh`. The cron service
clock is ~5h ahead of the sandbox clock (one_time jobs must be scheduled far future).

## Browser verification (all passed, zero console/page errors)

- Exam timer counts down (30:00 → 29:50, Pause state, 1% elapsed).
- PA: occasion Early closing + details → **Draft with AI** produced "Teachers and
  learners, please note that school will close early today at 12:30 pm. All learners
  should proceed home immediately after closing. Teachers are to supervise the dismissal
  process. Thank you for your cooperation." → Speak over the PA → POST 200 → History row
  (kind PA announcement, purple badge).
- AI builder: BECE example → 7 proposed rings (assembly at 70% vol + gentle start) →
  applied to "Exam Day" → activated → rings table shows all 7.
- Period bell: "Change of lesson" every 40m until 14:00 created (badge "periods every
  40m → 14:00"), then deleted.
- Zones: added "Science block", saved; device zone select works (reset to All Campus).
- Profiles: created "Vacation classes" (Activate button appeared) → deleted.
- History: AI reliability report → expected/fired/missed chips + narrative ("functioning
  perfectly…") + tips.
- Deep link `/?module=siren&tab=history` lands on Siren › History (active tab).
- Metro Start search "announcement" → "Siren — PA Announcements".
- Dashboard: StatCards count up (126 learners), page transitions animate, skip-link
  present. Landing + login motion verified (screenshots 01, 11).
- `bun run lint` clean.

## Screenshots

w61-01-landing · 02-dashboard · 03-siren-live (full) · 04-siren-history ·
05-siren-timetable · 06-siren-zones · 07-siren-normal-active · 08-dashboard-modern ·
09-siren-deeplink · 10-metro-search · 11-login-polish

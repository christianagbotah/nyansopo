# Wave 58 — NTC CPD & Licensure + GES EMIS Annual Return

## Why
Two deeply Ghana-specific gaps remained after the CI3-parity waves closed:
1. **NTC licensure** — Ghanaian teachers hold a renewable National Teaching
   Council licence; renewal requires CPD points across NTC's programme
   categories. Schools need the register + verification trail.
2. **EMIS** — every school files an annual GES-style census return
   (enrolment by class & gender, staffing, facilities, attendance, finance).

## Schema (db push, non-destructive)
- `Staff` += `ntcLicenceNo`, `ntcCategory`, `ntcIssued`, `ntcExpiry`
- `CpdSetting` (schoolId unique) — cycle targets: 20 mandatory / 40 rank /
  40 recommended over 3 years (NTC defaults), cycleYears for the rolling window
- `CpdActivity` — staffId, title, provider, category (MANDATORY|RANK|
  RECOMMENDED), activityDate, points, hours, evidence, status
  (LOGGED|VERIFIED|REJECTED), verifiedByName/At
- `EmisReturn` — schoolId+year unique, dataJson snapshot, filedByName, note

## APIs (`ops/staffcpd`, `ops/emis`)
- staffcpd GET: staff with NTC fields + activities + setting + per-staff
  summary (verified points per category inside each teacher's licence cycle,
  or the rolling window when licence dates are absent)
- staffcpd POST: save-licence · log-activity · verify · reject ·
  delete-activity · save-targets (all staffcpd.manage, audited)
- emis GET: live computeCensus (identity, enrolment byClass+bands, staffing
  byCategory+qualifications+STR+SSNIT, facilities incl. boarding & library,
  term attendance via AttendanceSession/Record groupBy, current-term finance,
  WAEC candidacy) + filed archive; ?year= returns the filed snapshot
- emis POST: file (upsert per year, snapshot at filing time) · unfile

## UI
- `staff-cpd.tsx` (People › NTC Licences & CPD): Licence register (6 stat
  cards, status filter incl. Expiring ≤90d / Critical ≤30d / Expired, CSV,
  A4 print), CPD log (filters by staff/category/status, verify/reject/delete,
  CSV), Points board (per-category pts vs targets, progress bars, targets
  editor dialog)
- `emis.tsx` (Support › EMIS Returns): census preview (stat cards, identity
  sheet, level bands + by-class tables with fill %, staffing with STR badge,
  facilities, WAEC), filed archive with snapshot viewer + withdrawal +
  window.print() census form, enrolment CSV export
- RBAC: `staffcpd.view` / `staffcpd.manage` (People) + `emis.manage` (System);
  SCHOOL_ADMIN ladder gets them via the minus-list, manage implies view
- Notification centre #7: NTC renewal alert (danger when expired/critical,
  warning when expiring ≤90d) → deep-link staffcpd › licences
- Page index: NTC Licences / CPD Log / CPD Points Board / EMIS Census
  Preview / EMIS Archive; sidebar children; metro tiles (NTC & CPD in People,
  EMIS Census in Academics)

## Concurrency note
The midnight safety-net agentTurn (new 8h cron, first firing 00:00:19)
interleaved with this wave: it committed the WIP (2a5e9ff), fixed the
phantom-import/Target/female TDZ crashes, wrote its own emis.tsx, filed the
2026 return as Madam Grace Mensah, and committed 21ce404 + 7780d2b. The
final tree merged both tracks; this session then browser-verified the
staffcpd golden path (untouched by the agent) and pushed 03c219d.

## Verified in-browser (agent-browser, super admin)
- licences: Adjoa Nyarko PT/0412875 Professional 2024-03-15→2027-03-14
  (Valid·108d), Ibrahim Musah PT/0399241 (Expiring·66d), Kwesi Anane
  PT/0288113 (Expired) — stat cards 1/1/0/1/6, filters + CSV live
- CPD log: 3 activities created via dialog (staff picker, category select,
  date/points/hours/evidence) → LOGGED → Verify by Madam Akosua Danso;
  provider·hours·evidence sub-lines render
- board: Adjoa 4/20 M + 6/40 R + 0/40 Rec = 10/100 (10%), Ibrahim 2/100,
  licence-cycle windows vs rolling 3-year window correct
- notifications: danger item "1 NTC licence expired — e.g. Kwesi Anane"
  leads the list; click deep-links to staffcpd › licences (tab selected)
- EMIS: census preview matches dashboard (126 learners · 66/60 · STR 14 ·
  91.3% · 62.1% · ₵220k/₵355k); archive shows 2026 filed return
  ("Submitted to Ga East district office"); snapshot viewer + back-to-live
- audit rows on every action; zero console errors

## Ops learnings (sandbox)
- next-server cold-compile RSS is now ~2.4 GB (module graph grew through
  waves 54–57): THREE fresh OOM kills tonight. Rule that held: close the
  verification browser, start dev, curl-warm homepage + per-module API
  entries, THEN open Chrome. Warm .next cache restarts are cheap.
- `rg -rn` is `--replace n`, not recursive — it disguises identifiers and
  caused a false "file flip-flopping" theory during the concurrent-agent
  investigation.
- Safety-net cron now runs 3×/day (00:00/08:00/16:00 Reykjavik, job 547321);
  the dead 20:00 job from the lost chat (516260) was deleted.

Screenshots: agent-ctx/w58-*.png (licences, cpd-log, cpd-board,
ntc-notification, emis-verified, emis-snapshot-view)

import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { PAYMENT_LABELS } from "@/lib/format";

// ── POST /api/school/[id]/ops/assistant ──────────────────────────────────
// "Ask Nyansapo" — the school-wide AI copilot (wave 63; finance core was the
// iEduPro → Nyansapo port, task 9). Asking a question is a read-only action,
// so it uses the same access level as the ops overview dashboard feed:
// school staff may operate, association officers keep their read-only
// oversight.
//
// Pipeline (the LLM is optional — `data` and a deterministic fallback
// answer are returned even when the AI service is unreachable):
//   1. deterministic date-intent parsing (Ghana = UTC+0 → UTC days ARE
//      Accra calendar days, so dates are treated literally)
//   2. domain retrieval — real DB figures (integer cents → GHS) for the
//      matched domains: finance (receipts, daily fees, expenses, arrears,
//      credit) PLUS wave-63 domains (attendance, learners, discipline,
//      visitors, staff, siren, term calendar) and the cross-domain
//      "morning brief" digest
//   3. LLM answer constrained to those VERIFIED numbers
//
// Body: { question: string, history?: {role, content}[] (last ~6),
//         dateContext?: { from?: "YYYY-MM-DD", to?: "YYYY-MM-DD" } }

type ChatTurn = { role: "user" | "assistant"; content: string };

const MONTHS = ["january", "february", "march", "april", "may", "june", "july", "august", "september", "october", "november", "december"];
const MONTHS_SHORT = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

type DateRange = { from: Date; to: Date; label: string };
type DateIntent = { kind: "range"; range: DateRange } | { kind: "term"; offset: "current" | "previous" } | null;

// ═════════════════════════════════════════════════════════════════════════
// DATE INTENT PARSER (deterministic — runs before any LLM call)
// ═════════════════════════════════════════════════════════════════════════

/** UTC day bounds — Ghana is UTC+0, so UTC calendar days are Accra days. */
const utcDay = (y: number, m: number, d: number, endOfDay = false) =>
  endOfDay ? new Date(Date.UTC(y, m, d, 23, 59, 59, 999)) : new Date(Date.UTC(y, m, d));
const isoDay = (d: Date) => d.toISOString().slice(0, 10);
const monthLabel = (m: number, y: number) => `${MONTHS[m][0].toUpperCase()}${MONTHS[m].slice(1)} ${y}`;

/** "jan"…january / "sept"…september — unique at ≥3 chars. */
function monthFromToken(tok: string): number | null {
  if (tok.length < 3) return null;
  const hits = MONTHS.filter((m) => m.startsWith(tok));
  return hits.length === 1 ? MONTHS.indexOf(hits[0]) : null;
}

function spanLabel(a: Date, b: Date): string {
  const A = { d: a.getUTCDate(), m: a.getUTCMonth(), y: a.getUTCFullYear() };
  const B = { d: b.getUTCDate(), m: b.getUTCMonth(), y: b.getUTCFullYear() };
  if (A.y === B.y && A.m === B.m && A.d === B.d) return `${A.d} ${MONTHS_SHORT[A.m]} ${A.y}`;
  if (A.y === B.y && A.m === B.m) return `${A.d}–${B.d} ${MONTHS_SHORT[A.m]} ${A.y}`;
  if (A.y === B.y) return `${A.d} ${MONTHS_SHORT[A.m]} – ${B.d} ${MONTHS_SHORT[B.m]} ${A.y}`;
  return `${A.d} ${MONTHS_SHORT[A.m]} ${A.y} – ${B.d} ${MONTHS_SHORT[B.m]} ${B.y}`;
}

/** Single calendar day, with sanity guards (rejects 31 Feb etc.). */
function dayFragRange(d: number, mo: number, y: number): DateRange | null {
  if (d < 1 || d > 31 || mo < 0 || mo > 11 || y < 2000 || y > 2100) return null;
  const f = utcDay(y, mo, d);
  if (f.getUTCDate() !== d || f.getUTCMonth() !== mo) return null;
  return { from: f, to: utcDay(y, mo, d, true), label: spanLabel(f, f) };
}

type Frag = { d?: number; m?: number; y?: number };

/** One endpoint of a "from X to Y" pair: "10 Jan 2026", "January", "2025", "5". */
function parseFragment(raw: string): Frag | null {
  const s = raw.trim().toLowerCase().replace(/\s+/g, " ").replace(/^(?:the|on|in|of)\s+/, "");
  if (!s) return null;
  let m: RegExpMatchArray | null;
  // "12 Jan 2026" / "12th January" / "12 Jan, 2026" / "12 of March"
  if ((m = s.match(/^(\d{1,2})(?:st|nd|rd|th)?\s+(?:of\s+)?([a-z]{3,9})\.?(?:\s*,?\s*(\d{4}))?$/))) {
    const mo = monthFromToken(m[2]);
    if (mo !== null) return { d: +m[1], m: mo, y: m[3] ? +m[3] : undefined };
  }
  // "Jan 12 2026" / "January 12th, 2026"
  if ((m = s.match(/^([a-z]{3,9})\.?\s+(\d{1,2})(?:st|nd|rd|th)?(?:\s*,?\s*(\d{4}))?$/))) {
    const mo = monthFromToken(m[1]);
    if (mo !== null) return { d: +m[2], m: mo, y: m[3] ? +m[3] : undefined };
  }
  // "12/01/2026", "12-01-26", "12.01.2026" — Ghana writes day-first (DD/MM/YYYY)
  if ((m = s.match(/^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{2,4})$/))) {
    let d = +m[1], mo = +m[2], y = +m[3];
    if (y < 100) y += 2000;
    if (d <= 12 && mo > 12) { const t = d; d = mo; mo = t; } // clearly month-first → normalise
    return { d, m: mo - 1, y };
  }
  // bare day — only meaningful inside a pair ("between 5 and 10 Jan")
  if ((m = s.match(/^(\d{1,2})(?:st|nd|rd|th)?$/))) return { d: +m[1] };
  // bare year
  if ((m = s.match(/^(20\d{2})$/))) return { y: +m[1] };
  // bare month name
  const mo = monthFromToken(s.replace(/[^a-z]/g, ""));
  if (mo !== null) return { m: mo };
  return null;
}

/** Fragment → full range (month fragment = whole month, year = whole year). */
function fragToRange(f: Frag, nowY: number): DateRange | null {
  if (f.d !== undefined && f.m !== undefined) return dayFragRange(f.d, f.m, f.y ?? nowY);
  if (f.m !== undefined) {
    const y = f.y ?? nowY;
    return { from: utcDay(y, f.m, 1), to: utcDay(y, f.m + 1, 0, true), label: monthLabel(f.m, y) };
  }
  if (f.y !== undefined) return { from: utcDay(f.y, 0, 1), to: utcDay(f.y, 11, 31, true), label: `${f.y}` };
  return null;
}

function relativeRange(
  word: "today" | "yesterday" | "this-week" | "last-week" | "this-month" | "last-month" | "this-year" | "last-year",
  Y: number, M: number, D: number
): DateRange {
  switch (word) {
    case "today":
      return { from: utcDay(Y, M, D), to: utcDay(Y, M, D, true), label: `today · ${D} ${MONTHS_SHORT[M]} ${Y}` };
    case "yesterday": {
      const f = utcDay(Y, M, D - 1);
      return { from: f, to: utcDay(f.getUTCFullYear(), f.getUTCMonth(), f.getUTCDate(), true), label: `yesterday · ${spanLabel(f, f)}` };
    }
    case "this-week":
    case "last-week": {
      const dow = (new Date(Date.UTC(Y, M, D)).getUTCDay() + 6) % 7; // Monday = 0 (ISO week)
      const shift = word === "last-week" ? 7 : 0;
      const mon = utcDay(Y, M, D - dow - shift);
      const sun = utcDay(Y, M, D - dow - shift + 6, true);
      return { from: mon, to: sun, label: `${word === "last-week" ? "last" : "this"} week · ${spanLabel(mon, sun)}` };
    }
    case "this-month":
      return { from: utcDay(Y, M, 1), to: utcDay(Y, M + 1, 0, true), label: `this month · ${monthLabel(M, Y)}` };
    case "last-month":
      return { from: utcDay(Y, M - 1, 1), to: utcDay(Y, M, 0, true), label: `last month · ${monthLabel((M + 11) % 12, M === 0 ? Y - 1 : Y)}` };
    case "this-year":
      return { from: utcDay(Y, 0, 1), to: utcDay(Y, 11, 31, true), label: `this year · ${Y}` };
    case "last-year":
      return { from: utcDay(Y - 1, 0, 1), to: utcDay(Y - 1, 11, 31, true), label: `last year · ${Y - 1}` };
  }
}

/** Parse a free-text question into a concrete UTC date range / term intent. */
function parseDateIntent(q: string, Y: number, M: number, D: number): DateIntent {
  const s = q.toLowerCase();
  let m: RegExpMatchArray | null;

  // ── explicit pair: "between 5 and 10 Jan", "from 5 Jan to 10 Feb 2026" ──
  const pair = s.match(/\b(?:between|from)\s+([^.,;?!]+?)\s+(?:and|to|until|till)\s+([^.,;?!]+)/);
  if (pair) {
    const a = parseFragment(pair[1]);
    const b = parseFragment(pair[2]);
    if (a && b) {
      // endpoint inheritance: "between 5 and 10 Jan" / "from 5 Jan to 10" /
      // "Jan 5 to Feb 10 2026" (a bare-day endpoint inherits month/year)
      const fa: Frag = a.d !== undefined && a.m === undefined && b.m !== undefined ? { ...a, m: b.m, y: a.y ?? b.y } : a;
      const fb: Frag = b.d !== undefined && b.m === undefined && a.m !== undefined ? { ...b, m: a.m, y: b.y ?? a.y } : b;
      const fa2: Frag = fa.m !== undefined && fa.y === undefined && b.y !== undefined ? { ...fa, y: b.y } : fa;
      const ra = fragToRange(fa2, Y);
      const rb = fragToRange(fb, Y);
      if (ra && rb) {
        const [lo, hi] = ra.from <= rb.from ? [ra, rb] : [rb, ra];
        return { kind: "range", range: { from: lo.from, to: hi.to, label: spanLabel(lo.from, hi.to) } };
      }
    }
  }

  // ── relative words ──
  if (/\btoday\b|\bthis morning\b|\bso far today\b/.test(s)) return { kind: "range", range: relativeRange("today", Y, M, D) };
  if (/\byesterday\b/.test(s)) return { kind: "range", range: relativeRange("yesterday", Y, M, D) };
  if (/\b(?:this|current) week\b/.test(s)) return { kind: "range", range: relativeRange("this-week", Y, M, D) };
  if (/\blast week\b/.test(s)) return { kind: "range", range: relativeRange("last-week", Y, M, D) };
  if (/\b(?:this|current) month\b/.test(s)) return { kind: "range", range: relativeRange("this-month", Y, M, D) };
  if (/\blast month\b/.test(s)) return { kind: "range", range: relativeRange("last-month", Y, M, D) };
  if (/\b(?:this|current) term\b/.test(s)) return { kind: "term", offset: "current" };
  if (/\blast term\b/.test(s)) return { kind: "term", offset: "previous" };
  if (/\b(?:this|current) year\b/.test(s)) return { kind: "range", range: relativeRange("this-year", Y, M, D) };
  if (/\blast year\b/.test(s)) return { kind: "range", range: relativeRange("last-year", Y, M, D) };

  // ── single full dates anywhere in the sentence ──
  for (m of s.matchAll(/\b(\d{1,2})(?:st|nd|rd|th)?\s+(?:of\s+)?([a-z]{3,9})\b(?:\s*,?\s*(20\d{2}))?/g)) {
    const mo = monthFromToken(m[2]);
    if (mo !== null) { const r = dayFragRange(+m[1], mo, m[3] ? +m[3] : Y); if (r) return { kind: "range", range: r }; }
  }
  for (m of s.matchAll(/\b([a-z]{3,9})\s+(\d{1,2})(?:st|nd|rd|th)?(?!\d)(?:\s*,?\s*(20\d{2}))?/g)) {
    const mo = monthFromToken(m[1]);
    if (mo !== null) { const r = dayFragRange(+m[2], mo, m[3] ? +m[3] : Y); if (r) return { kind: "range", range: r }; }
  }
  for (m of s.matchAll(/\b(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{2,4})\b/g)) {
    let d = +m[1], mo = +m[2], y = +m[3];
    if (y < 100) y += 2000;
    if (d <= 12 && mo > 12) { const t = d; d = mo; mo = t; } // clearly month-first → normalise
    const r = dayFragRange(d, mo - 1, y);
    if (r) return { kind: "range", range: r };
  }
  // month + year: "January 2026"
  for (m of s.matchAll(/\b([a-z]{3,9})\b\s*,?\s*(20\d{2})\b/g)) {
    const mo = monthFromToken(m[1]);
    if (mo !== null) return { kind: "range", range: { from: utcDay(+m[2], mo, 1), to: utcDay(+m[2], mo + 1, 0, true), label: monthLabel(mo, +m[2]) } };
  }
  // bare year: "in 2025"
  if ((m = s.match(/\b(20\d{2})\b/))) return { kind: "range", range: { from: utcDay(+m[1], 0, 1), to: utcDay(+m[1], 11, 31, true), label: m[1] } };
  // bare month name: "in January"
  for (m of s.matchAll(/\b([a-z]{3,9})\b/g)) {
    const mo = monthFromToken(m[1]);
    if (mo !== null) return { kind: "range", range: { from: utcDay(Y, mo, 1), to: utcDay(Y, mo + 1, 0, true), label: monthLabel(mo, Y) } };
  }
  return null;
}

/** "YYYY-MM-DD…" → UTC day start (dateContext override). */
function parseIsoDay(s: unknown): Date | null {
  if (typeof s !== "string") return null;
  const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (!m) return null;
  const d = new Date(Date.UTC(+m[1], +m[2] - 1, +m[3]));
  return isNaN(d.getTime()) ? null : d;
}

/** Current/previous term as a date range (resolved from the DB). */
async function termRange(schoolId: string, offset: "current" | "previous"): Promise<DateRange | null> {
  const terms = await db.term.findMany({ where: { academicYear: { schoolId } }, orderBy: { startsAt: "asc" } });
  const curIdx = terms.findIndex((t) => t.isCurrent);
  const idx = offset === "current" ? curIdx : curIdx - 1;
  const t = idx >= 0 ? terms[idx] : null;
  if (!t) return null;
  const to = utcDay(t.endsAt.getUTCFullYear(), t.endsAt.getUTCMonth(), t.endsAt.getUTCDate(), true);
  return { from: t.startsAt, to, label: `${t.name} · ${spanLabel(t.startsAt, to)}` };
}

// ═════════════════════════════════════════════════════════════════════════
// WAVE 63 DOMAIN RETRIEVERS — attendance · learners · discipline · visitors
// · staff · siren · term — each returns a verified Section for the chips
// and the grounded LLM answer. All read-only, all real figures.
// ═════════════════════════════════════════════════════════════════════════

const ATT_STATUS_IN = (s: string) => s === "PRESENT" || s === "LATE";

/** Today's registers: overall rate, per-class table, absentee names + a
 *  5-school-day trend line for "how is attendance trending" questions. */
async function attendanceSection(schoolId: string, now: Date): Promise<Section> {
  const Y = now.getUTCFullYear(), M = now.getUTCMonth(), D = now.getUTCDate();
  const from = utcDay(Y, M, D);
  const to = utcDay(Y, M, D, true);
  const sessions = await db.attendanceSession.findMany({
    where: { schoolId, date: { gte: from, lte: to } },
    include: {
      classroom: { select: { name: true } },
      records: { include: { student: { select: { firstName: true, lastName: true } } } },
    },
    orderBy: { date: "asc" },
  });
  let present = 0, late = 0, absent = 0, other = 0;
  const absentees: string[] = [];
  const perClass: { name: string; present: number; absent: number }[] = [];
  for (const s of sessions) {
    let p = 0, a = 0;
    for (const r of s.records) {
      if (ATT_STATUS_IN(r.status)) { present++; p++; if (r.status === "LATE") late++; }
      else if (r.status === "ABSENT") { absent++; a++; absentees.push(`${`${r.student.firstName} ${r.student.lastName}`.trim()} (${s.classroom.name})`); }
      else other++;
    }
    perClass.push({ name: s.classroom.name, present: p, absent: a });
  }
  const total = present + late + absent + other;
  const rate = total ? Math.round(((present + late) / total) * 100) : null;
  const enrolled = await db.enrollment.count({ where: { schoolId, status: "ACTIVE" } });

  // last 5 school days with registers (excludes today) → trend
  const week = await db.attendanceSession.findMany({
    where: { schoolId, date: { gte: new Date(from.getTime() - 8 * 86400000), lt: from } },
    select: { date: true, records: { select: { status: true } } },
    orderBy: { date: "asc" },
  });
  const byDay = new Map<string, { in: number; total: number }>();
  for (const s of week) {
    const k = s.date.toISOString().slice(0, 10);
    const e = byDay.get(k) ?? { in: 0, total: 0 };
    e.total += s.records.length;
    e.in += s.records.filter((r) => ATT_STATUS_IN(r.status)).length;
    byDay.set(k, e);
  }
  const trend = [...byDay.entries()].sort().slice(-5).map(([k, v]) => `${k.slice(5)}: ${v.total ? Math.round((v.in / v.total) * 100) : 0}%`);

  const lines: string[] = [];
  lines.push(
    sessions.length
      ? `${sessions.length} register(s) taken today — ${rate}% present (present ${present} + late ${late}, absent ${absent}, excused/other ${other}) of ${enrolled} on roll.`
      : `No registers taken yet today. ${enrolled} learners on roll.`
  );
  for (const c of perClass.slice(0, 8)) lines.push(`${c.name}: ${c.present} present, ${c.absent} absent`);
  lines.push(absentees.length ? `Absent: ${absentees.slice(0, 10).join(", ")}${absentees.length > 10 ? ` (+${absentees.length - 10} more)` : ""}` : "No absentees recorded today.");
  if (trend.length) lines.push(`Previous days — ${trend.join(" · ")}`);
  return { title: "Attendance today", lines, module: "attendance" };
}

/** Active enrolment: total, gender split, per-class counts. */
async function learnersSection(schoolId: string): Promise<Section> {
  const rows = await db.enrollment.findMany({
    where: { schoolId, status: "ACTIVE" },
    select: { classroom: { select: { name: true } }, student: { select: { gender: true } } },
  });
  const boys = rows.filter((r) => r.student.gender === "M").length;
  const girls = rows.filter((r) => r.student.gender === "F").length;
  const byClass = new Map<string, number>();
  for (const r of rows) byClass.set(r.classroom.name, (byClass.get(r.classroom.name) ?? 0) + 1);
  const lines = [
    `${rows.length} learners on roll — ${boys} boys, ${girls} girls.`,
    ...[...byClass.entries()].sort((a, b) => b[1] - a[1]).slice(0, 10).map(([name, n]) => `${name}: ${n}`),
  ];
  return { title: "Learners on roll", lines, module: "students" };
}

/** Last 30 days of discipline: incident/commendation counts, open + escalated,
 *  recent case names. */
async function disciplineSection(schoolId: string, now: Date): Promise<Section> {
  const since = new Date(now.getTime() - 30 * 86400000);
  const cases = await db.disciplineCase.findMany({
    where: { schoolId, date: { gte: since } },
    orderBy: { date: "desc" },
    include: { student: { select: { firstName: true, lastName: true } } },
    take: 60,
  });
  const incidents = cases.filter((c) => c.kind === "INCIDENT");
  const commendations = cases.length - incidents.length;
  const open = incidents.filter((c) => c.status === "OPEN");
  const escalated = incidents.filter((c) => c.status === "ESCALATED");
  const lines = [
    `${incidents.length} incident(s) in the last 30 days — ${open.length} open, ${escalated.length} escalated — and ${commendations} commendation(s).`,
    ...incidents.slice(0, 6).map((c) => `${`${c.student.firstName} ${c.student.lastName}`.trim()} — ${c.category}${c.severity ? ` (${c.severity})` : ""} · ${c.status} · ${c.date.toISOString().slice(0, 10)}`),
  ];
  return { title: "Discipline (30 days)", lines, module: "discipline" };
}

/** Front desk: who is on site right now + today's visit count. */
async function visitorsSection(schoolId: string, now: Date): Promise<Section> {
  const Y = now.getUTCFullYear(), M = now.getUTCMonth(), D = now.getUTCDate();
  const onSite = await db.visitor.findMany({
    where: { schoolId, status: "ON_SITE" },
    orderBy: { timeIn: "desc" },
    take: 10,
  });
  const todayCount = await db.visitor.count({
    where: { schoolId, timeIn: { gte: utcDay(Y, M, D), lte: utcDay(Y, M, D, true) } },
  });
  const lines = [
    `${todayCount} visit(s) today — ${onSite.length} currently on site.`,
    ...onSite.map((v) => `${v.name}${v.organisation ? ` (${v.organisation})` : ""} — ${v.purpose}${v.personToSee ? `, to see ${v.personToSee}` : ""} since ${v.timeIn.toISOString().slice(11, 16)}`),
  ];
  return { title: "Visitors", lines, module: "frontoffice" };
}

/** Staff register: headcount by category + birthdays this month. */
async function staffSection(schoolId: string, now: Date): Promise<Section> {
  const staff = await db.staff.findMany({
    where: { schoolId },
    select: { fullName: true, category: true, birthday: true },
  });
  const byCat = new Map<string, number>();
  for (const s of staff) byCat.set(s.category, (byCat.get(s.category) ?? 0) + 1);
  const bdays = staff
    .filter((s) => s.birthday && s.birthday.getUTCMonth() === now.getUTCMonth())
    .sort((a, b) => (a.birthday?.getUTCDate() ?? 0) - (b.birthday?.getUTCDate() ?? 0))
    .slice(0, 6);
  const lines = [
    `${staff.length} staff — ${[...byCat.entries()].map(([k, n]) => `${k.replace("_", " ").toLowerCase()} ${n}`).join(", ")}.`,
    ...(bdays.length ? [`Birthdays this month: ${bdays.map((s) => `${s.fullName} (${s.birthday!.getUTCDate()} ${MONTHS_SHORT[s.birthday!.getUTCMonth()]})`).join(", ")}`] : []),
  ];
  return { title: "Staff", lines, module: "staffhub" };
}

/** The bell: next scheduled ring (weekday-aware) + enabled count. */
async function sirenSection(schoolId: string, now: Date): Promise<Section> {
  const schedules = await db.sirenSchedule.findMany({
    where: { schoolId, enabled: true },
    orderBy: { time: "asc" },
  });
  const wd = now.getUTCDay();
  const nowMin = now.getUTCHours() * 60 + now.getUTCMinutes();
  let next: string | null = null;
  outer: for (let off = 0; off < 8 && !next; off++) {
    const day = (wd + off) % 7;
    for (const s of schedules) {
      let days: number[] = [1, 2, 3, 4, 5];
      try { days = JSON.parse(s.daysJson); } catch { /* default weekdays */ }
      if (!days.includes(day)) continue;
      const [hh, mm] = s.time.split(":").map(Number);
      const t = (hh || 0) * 60 + (mm || 0);
      if (off === 0 && t <= nowMin) continue;
      next = `${s.label} at ${s.time} (${s.zone} · ${s.profile})${off ? ` in ${off} day${off > 1 ? "s" : ""}` : " today"}`;
      break outer;
    }
  }
  const lines = [
    `${schedules.length} enabled ring(s) on the timetable.`,
    next ? `Next ring: ${next}.` : "No upcoming ring scheduled.",
    ...schedules.filter((s) => s.repeatEveryMin).slice(0, 3).map((s) => `${s.label} repeats every ${s.repeatEveryMin} min until ${s.endTime ?? "end of day"}`),
  ];
  return { title: "Siren & bells", lines, module: "siren" };
}

/** Active term: week number + days to end. */
async function termSection(schoolId: string, now: Date): Promise<Section> {
  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
  const lines: string[] = [];
  if (term) {
    const week = Math.max(1, Math.floor((now.getTime() - term.startsAt.getTime()) / (7 * 86400000)) + 1);
    const daysLeft = Math.max(0, Math.ceil((term.endsAt.getTime() - now.getTime()) / 86400000));
    lines.push(`${term.name} — week ${week}, ${daysLeft} day(s) to end (${term.startsAt.toISOString().slice(0, 10)} – ${term.endsAt.toISOString().slice(0, 10)}).`);
  } else {
    lines.push("No active term is configured.");
  }
  return { title: "Term & calendar", lines, module: "dashboard" };
}

/** Money today (compact) — used by the morning brief. */
async function moneyTodaySection(schoolId: string, now: Date): Promise<Section> {
  const Y = now.getUTCFullYear(), M = now.getUTCMonth(), D = now.getUTCDate();
  const payments = await db.payment.findMany({
    where: { schoolId, voided: false, paymentType: { not: "REFUND" }, paidAt: { gte: utcDay(Y, M, D), lte: utcDay(Y, M, D, true) } },
    select: { amountCents: true, method: true, category: true },
  });
  const totalCents = payments.reduce((s, p) => s + p.amountCents, 0);
  const invoiceCents = payments.filter((p) => p.category === "INVOICE").reduce((s, p) => s + p.amountCents, 0);
  const dailyCents = payments.filter((p) => p.category === "DAILY").reduce((s, p) => s + p.amountCents, 0);
  const methodMap = new Map<string, number>();
  for (const p of payments) methodMap.set(p.method, (methodMap.get(p.method) ?? 0) + p.amountCents);
  const lines = [
    `${ghs(+(totalCents / 100).toFixed(2))} from ${payments.length} receipt(s) — invoices ${ghs(+(invoiceCents / 100).toFixed(2))}, daily fees ${ghs(+(dailyCents / 100).toFixed(2))}.`,
    [...methodMap.entries()].sort((a, b) => b[1] - a[1]).slice(0, 4).map(([m, c]) => `${PAYMENT_LABELS[m] ?? m} ${ghs(+(c / 100).toFixed(2))}`).join(", ") || "No payments yet today.",
  ];
  return { title: "Money today", lines, module: "cashier" };
}

/** Term arrears (compact) — used by the morning brief. */
async function arrearsBriefSection(schoolId: string): Promise<Section> {
  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
  const invoices = term ? await db.invoice.findMany({ where: { schoolId, termId: term.id }, select: { totalCents: true, discountCents: true, paidCents: true } }) : [];
  const billed = invoices.reduce((s, i) => s + i.totalCents - i.discountCents, 0);
  const collected = invoices.reduce((s, i) => s + i.paidCents, 0);
  const dailyArrearsCents = await db.dailyCharge.aggregate({ where: { schoolId, status: "OWED" }, _sum: { amountCents: true } }).then((r) => r._sum.amountCents ?? 0);
  const rate = billed ? Math.round((collected / billed) * 100) : 0;
  return {
    title: "Fees & arrears",
    lines: [
      `${term?.name ?? "Current term"}: billed ${ghs(+(billed / 100).toFixed(2))}, collected ${ghs(+(collected / 100).toFixed(2))} (${rate}%), arrears ${ghs(+((billed - collected) / 100).toFixed(2))}.`,
      `Daily-fee arrears across the school: ${ghs(+(dailyArrearsCents / 100).toFixed(2))}.`,
    ],
    module: "cashier",
  };
}

/** The cross-domain Headteacher's Morning Brief — everything worth knowing
 *  before the first bell, each section verified from the live database. */
async function buildBrief(schoolId: string, now: Date): Promise<Section[]> {
  const sections: Section[] = await Promise.all([
    termSection(schoolId, now),
    attendanceSection(schoolId, now),
    moneyTodaySection(schoolId, now),
    arrearsBriefSection(schoolId),
    visitorsSection(schoolId, now),
    disciplineSection(schoolId, now),
    staffSection(schoolId, now),
    sirenSection(schoolId, now),
  ]);
  return sections;
}

/** Match the question to wave-63 domains and retrieve each matched one. */
async function domainSections(schoolId: string, question: string, now: Date): Promise<{ sections: Section[]; modules: string[] }> {
  const sections: Section[] = [];
  const modules: string[] = [];
  const push = async (match: boolean, fn: () => Promise<Section>, mod: string) => {
    if (!match) return;
    sections.push(await fn());
    modules.push(mod);
  };
  await push(TERM_RE.test(question), () => termSection(schoolId, now), "dashboard");
  await push(ATTENDANCE_RE.test(question), () => attendanceSection(schoolId, now), "attendance");
  await push(LEARNERS_RE.test(question), () => learnersSection(schoolId), "students");
  await push(DISCIPLINE_RE.test(question), () => disciplineSection(schoolId, now), "discipline");
  await push(VISITOR_RE.test(question), () => visitorsSection(schoolId, now), "frontoffice");
  await push(STAFF_RE.test(question), () => staffSection(schoolId, now), "staffhub");
  await push(SIREN_RE.test(question), () => sirenSection(schoolId, now), "siren");
  return { sections, modules };
}

/** Deterministic answer for domain/brief data (LLM-down fallback). */
function sectionsFallback(sections: Section[]): string {
  return sections.map((s) => `${s.title} — ${s.lines.join(" ")}`).join("\n");
}

// ═════════════════════════════════════════════════════════════════════════
// FINANCE RETRIEVAL (real numbers, cents → GHS) — kept verbatim as one
// branch of the copilot; see domain retrievers above for wave-63 domains.
// ═════════════════════════════════════════════════════════════════════════

const FINANCE_RE = /(income|revenue|fee|collection|collected|collect|payment|paid|momo|cash|cheque|bank|daily|lunch|breakfast|feeding|fare|credit|arrear|balance|owe|owed|expenditure|expense|spend|spent|sales|money|cedi|ghc|ghs|amount|received|takings|turnover|wallet|refund|float|profit|surplus|deficit)/i;
const ITEMIZE_RE = /(detail|breakdown|itemi[sz]e|list|receipt|transaction|who paid|show)/i;
const CREDIT_RE = /\bcredits?\b/i;
const EXPENSE_RE = /(expenditure|expenses?|spent|spending|costs?)/i;
const ARREARS_RE = /(arrears?|owing|owed|outstanding|defaulters?|debts?)/i;

// ── wave 63: school-wide copilot — domain detection beyond finance ──
const ATTENDANCE_RE = /\b(attendance|absent|absentees?|present|registers?|truanc\w*|punctual\w*|late com\w*|turned up|showed up)\b/i;
const LEARNERS_RE = /\b(learners?|students?|pupils?|enrol\w*|admissions?|population|boys?|girls?|gender)\b/i;
const DISCIPLINE_RE = /\b(discipline|incidents?|misconduct|sanctions?|detentions?|commendations?|suspensions?|cases)\b/i;
const VISITOR_RE = /\b(visitors?|guests?|front desk|front office|reception|on site|on-site)\b/i;
const STAFF_RE = /\b(staff|teachers?|employees?|headcount|workers?|non-teaching|administrators?|birthdays?)\b/i;
const SIREN_RE = /\b(sirens?|bells?|rings?|alarms?|assembly)\b/i;
const TERM_RE = /\b(terms?|semester|vacation|calendar|holiday|week \d|weeks left)\b/i;
const BRIEF_CORE_RE = /(brief|digest|sitrep|head.?start|overview|summary|state of the school|school (now|status|report))/i;

/** A verified-data block the answer (and its chips) are grounded in. */
type Section = { title: string; lines: string[]; module?: string };

const ghs = (n: number) => `₵${n.toLocaleString("en-GH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

type AssistantData =
  | {
      kind: "finance";
      rangeLabel: string | null;
      totalGHS: number;
      count: number;
      byMethod: { method: string; amountGHS: number }[];
      items: { receiptNo: string; student: string; amountGHS: number; method: string; paidAt: string }[];
      dailyFeesGHS: number;
      dailyByService: { service: string; amountGHS: number; txns: number }[];
      creditGHS?: number;
      creditStudents?: number;
      expensesGHS?: number;
      expensesByCategory?: { category: string; amountGHS: number }[];
      arrears?: { term: string | null; billedGHS: number; collectedGHS: number; arrearsGHS: number; collectionRate: number; dailyArrearsGHS: number };
      note?: string | null;
    }
  | { kind: "domain"; domain: string; sections: Section[]; note?: string | null }
  | { kind: "brief"; sections: Section[]; note?: string | null }
  | { kind: "general" };

type FinanceData = Extract<AssistantData, { kind: "finance" }>;

/** Deterministic answer built from the retrieved figures (LLM-down fallback). */
function fallbackAnswer(range: DateRange | null, data: FinanceData): string {
  const label = range?.label ?? "the requested period";
  const lines: string[] = [];
  if (!data.count) lines.push(`No payments were recorded for ${label}.`);
  else {
    lines.push(`Income for ${label}: ${ghs(data.totalGHS)} across ${data.count} payment${data.count === 1 ? "" : "s"}.`);
    if (data.byMethod.length) {
      lines.push(`By method — ${data.byMethod.map((b) => `${PAYMENT_LABELS[b.method] ?? b.method} ${ghs(b.amountGHS)}`).join(", ")}.`);
    }
    if (data.dailyFeesGHS > 0) {
      const svc = data.dailyByService.length ? ` (${data.dailyByService.map((s) => `${s.service} ${ghs(s.amountGHS)}`).join(", ")})` : "";
      lines.push(`Daily fees collected: ${ghs(data.dailyFeesGHS)}${svc}.`);
    }
    if (typeof data.expensesGHS === "number") lines.push(`Expenses: ${ghs(data.expensesGHS)}.`);
    if (typeof data.creditGHS === "number") {
      lines.push(`Learner credit balance: ${ghs(data.creditGHS)} across ${data.creditStudents ?? 0} learner${(data.creditStudents ?? 0) === 1 ? "" : "s"}.`);
    }
    if (data.arrears) {
      lines.push(
        `${data.arrears.term ? `${data.arrears.term}: billed` : "Current term: billed"} ${ghs(data.arrears.billedGHS)}, collected ${ghs(data.arrears.collectedGHS)}, arrears ${ghs(data.arrears.arrearsGHS)} (${data.arrears.collectionRate}% collection rate). Daily-fee arrears: ${ghs(data.arrears.dailyArrearsGHS)}.`
      );
    }
  }
  return lines.join(" ");
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  // read-only question → overview-level access (school staff / association oversight)
  const auth = await opsAuth(req, schoolId, "GET");
  if (!auth.ok) return auth.res;

  const body = await req.json().catch(() => null);
  const question = typeof body?.question === "string" ? body.question.trim() : "";
  if (!question) return NextResponse.json({ error: "A question is required" }, { status: 400 });
  if (question.length > 600) return NextResponse.json({ error: "Question too long (max 600 characters)" }, { status: 400 });

  const history: ChatTurn[] = Array.isArray(body?.history)
    ? (body.history as any[])
        .filter((t) => (t?.role === "user" || t?.role === "assistant") && typeof t?.content === "string" && t.content.trim())
        .slice(-6)
        .map((t) => ({ role: t.role as "user" | "assistant", content: t.content.trim().slice(0, 800) }))
    : [];

  // ── 1. date intent (deterministic, before any LLM call) ──
  const now = new Date();
  const Y = now.getUTCFullYear(), M = now.getUTCMonth(), D = now.getUTCDate();
  const notes: string[] = [];

  let range: DateRange | null = null;
  const ctxFrom = parseIsoDay(body?.dateContext?.from);
  const ctxTo = parseIsoDay(body?.dateContext?.to);
  if (ctxFrom || ctxTo) {
    const f = ctxFrom ?? ctxTo!;
    const t = ctxTo ?? ctxFrom!;
    const [lo, hi] = f <= t ? [f, t] : [t, f];
    range = { from: utcDay(lo.getUTCFullYear(), lo.getUTCMonth(), lo.getUTCDate()), to: utcDay(hi.getUTCFullYear(), hi.getUTCMonth(), hi.getUTCDate(), true), label: spanLabel(lo, hi) };
  }
  if (!range) {
    const lastUserTurn = [...history].reverse().find((t) => t.role === "user")?.content ?? "";
    const intent = parseDateIntent(question, Y, M, D) ?? (lastUserTurn ? parseDateIntent(lastUserTurn, Y, M, D) : null);
    if (intent?.kind === "term") {
      range = await termRange(schoolId, intent.offset);
      if (!range) notes.push(intent.offset === "current" ? "No current term is configured for this school." : "No previous term was found for this school.");
    } else if (intent?.kind === "range") {
      range = intent.range;
    }
  }

  // ── 2. retrieval — brief → finance → wave-63 domains (real figures) ──
  const recentUserText = history.filter((t) => t.role === "user").slice(-2).map((t) => t.content).join(" ");
  const finance = FINANCE_RE.test(question) || FINANCE_RE.test(recentUserText);
  // the morning brief wins over a bare "this morning" finance phrase unless
  // the user explicitly asked a money question with a short question
  const wantsBrief = BRIEF_CORE_RE.test(question) || (/\bmorning\b/i.test(question) && !finance) || /^\s*(brief|morning brief|good morning)\s*[?!.]*\s*$/i.test(question);
  let data: AssistantData = { kind: "general" };

  if (wantsBrief) {
    const sections = await buildBrief(schoolId, now);
    data = { kind: "brief", sections, note: notes.length ? notes.join(" ") : null };
  } else if (finance) {
    if (!range) {
      range = relativeRange("today", Y, M, D);
      notes.push("No dates were mentioned in the question, so the figures below default to today.");
    }
    const itemize = ITEMIZE_RE.test(question);
    const wantsArrears = ARREARS_RE.test(question) || ARREARS_RE.test(recentUserText);

    // counter receipts in range (voided and refunds excluded from income)
    const payments = await db.payment.findMany({
      where: { schoolId, voided: false, paymentType: { not: "REFUND" }, paidAt: { gte: range.from, lte: range.to } },
      include: { student: { select: { firstName: true, lastName: true } } },
      orderBy: { paidAt: "desc" },
    });
    const totalCents = payments.reduce((s, p) => s + p.amountCents, 0);
    const methodMap = new Map<string, number>();
    for (const p of payments) methodMap.set(p.method, (methodMap.get(p.method) ?? 0) + p.amountCents);
    const byMethod = [...methodMap.entries()].sort((a, b) => b[1] - a[1]).map(([method, cents]) => ({ method, amountGHS: +(cents / 100).toFixed(2) }));

    // daily fees: counter receipts (Payment.category DAILY) + per-service PAID charges
    const dailyCents = payments.filter((p) => p.category === "DAILY").reduce((s, p) => s + p.amountCents, 0);
    const dailyCharges = await db.dailyCharge.findMany({
      where: { schoolId, status: "PAID", date: { gte: range.from, lte: range.to } },
      include: { service: { select: { name: true } } },
    });
    const svcMap = new Map<string, { cents: number; txns: number }>();
    for (const c of dailyCharges) {
      const e = svcMap.get(c.service.name) ?? { cents: 0, txns: 0 };
      e.cents += c.amountCents; e.txns += 1;
      svcMap.set(c.service.name, e);
    }
    const dailyByService = [...svcMap.entries()].sort((a, b) => b[1].cents - a[1].cents).map(([service, e]) => ({ service, amountGHS: +(e.cents / 100).toFixed(2), txns: e.txns }));

    // learner credit balances (all-time net ledger)
    let creditGHS: number | undefined;
    let creditStudents: number | undefined;
    if (CREDIT_RE.test(question) || CREDIT_RE.test(recentUserText)) {
      const creditRows = await db.studentCredit.groupBy({ by: ["studentId"], where: { schoolId }, _sum: { amountCents: true } });
      creditGHS = +(creditRows.reduce((s, r) => s + (r._sum.amountCents ?? 0), 0) / 100).toFixed(2);
      creditStudents = creditRows.filter((r) => (r._sum.amountCents ?? 0) > 0).length;
    }

    // expenses in range
    let expensesGHS: number | undefined;
    let expensesByCategory: { category: string; amountGHS: number }[] | undefined;
    if (EXPENSE_RE.test(question) || EXPENSE_RE.test(recentUserText)) {
      const expenses = await db.expense.findMany({ where: { schoolId, expenseDate: { gte: range.from, lte: range.to } }, orderBy: { expenseDate: "desc" } });
      expensesGHS = +(expenses.reduce((s, e) => s + e.amountCents, 0) / 100).toFixed(2);
      const catMap = new Map<string, number>();
      for (const e of expenses) catMap.set(e.category, (catMap.get(e.category) ?? 0) + e.amountCents);
      expensesByCategory = [...catMap.entries()].sort((a, b) => b[1] - a[1]).map(([category, cents]) => ({ category, amountGHS: +(cents / 100).toFixed(2) }));
    }

    // term arrears (billed vs collected + daily-fee arrears)
    let arrears: FinanceData["arrears"];
    if (wantsArrears) {
      const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
      const invoices = term ? await db.invoice.findMany({ where: { schoolId, termId: term.id } }) : [];
      const billed = invoices.reduce((s, i) => s + i.totalCents - i.discountCents, 0);
      const collected = invoices.reduce((s, i) => s + i.paidCents, 0);
      const dailyArrearsCents = await db.dailyCharge.aggregate({ where: { schoolId, status: "OWED" }, _sum: { amountCents: true } }).then((r) => r._sum.amountCents ?? 0);
      arrears = {
        term: term?.name ?? null,
        billedGHS: +(billed / 100).toFixed(2),
        collectedGHS: +(collected / 100).toFixed(2),
        arrearsGHS: +((billed - collected) / 100).toFixed(2),
        collectionRate: billed ? Math.round((collected / billed) * 100) : 0,
        dailyArrearsGHS: +(dailyArrearsCents / 100).toFixed(2),
      };
    }

    data = {
      kind: "finance",
      rangeLabel: range.label,
      totalGHS: +(totalCents / 100).toFixed(2),
      count: payments.length,
      byMethod,
      items: payments.slice(0, itemize ? 20 : 8).map((p) => ({
        receiptNo: p.receiptNo,
        student: p.student ? `${p.student.firstName} ${p.student.lastName}`.trim() || "—" : "—",
        amountGHS: +(p.amountCents / 100).toFixed(2),
        method: p.method,
        paidAt: p.paidAt.toISOString(),
      })),
      dailyFeesGHS: +(dailyCents / 100).toFixed(2),
      dailyByService,
      ...(creditGHS !== undefined ? { creditGHS, creditStudents } : {}),
      ...(expensesGHS !== undefined ? { expensesGHS, expensesByCategory } : {}),
      ...(arrears ? { arrears } : {}),
      note: notes.length ? notes.join(" ") : null,
    };
  } else {
    // ── wave-63 domains: attendance / learners / discipline / visitors /
    //    staff / siren / term — each matched domain adds a verified section
    const dom = await domainSections(schoolId, question, now);
    if (dom.sections.length) {
      data = { kind: "domain", domain: dom.modules[0] ?? "dashboard", sections: dom.sections, note: notes.length ? notes.join(" ") : null };
    }
  }

  // ── 3. LLM answer over the VERIFIED figures ──
  const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true } });
  const sys = data.kind === "brief"
    ? `You are Ask Nyansapo — the AI copilot for the headteacher/proprietor of a Ghanaian private school (${school?.name ?? "the school"}). Compose today's HEADTEACHER'S MORNING BRIEF from the VERIFIED data sections (JSON) computed live from the school's database.

Rules:
- Ground every figure in the VERIFIED data only — never invent, estimate or recall numbers.
- Format money as ₵ with thousands separators and two decimals, e.g. ₵12,450.00.
- Open with a warm one-line greeting plus the term status, then one short line per section in order (term, attendance, money today, fees & arrears, visitors, discipline, staff, siren).
- End with at most 3 practical "Follow up" suggestions drawn from the data (e.g. call the parents of today's absentees, chase the biggest arrears class).
- If a section shows no data, say so briefly — do not fabricate.
- At most 200 words; plain text with section labels; no markdown tables.

VERIFIED DATA:
${JSON.stringify(data)}`
    : data.kind === "domain"
    ? `You are Ask Nyansapo — the AI copilot for staff of a Ghanaian private school (${school?.name ?? "the school"}). Answer the question using ONLY the VERIFIED data sections (JSON) from the school's live database.

Rules:
- Ground every claim and figure in the VERIFIED data — never invent, estimate or recall numbers.
- Format money as ₵12,450.00; mention the date the data covers (usually today).
- If the data does not answer the question, say plainly what you do have instead of guessing.
- Keep it concise (at most 150 words). Warm, professional Ghanaian tone; no markdown tables.

VERIFIED DATA:
${JSON.stringify(data)}`
    : finance
    ? `You are the Nyansapo Owner Assistant — a finance aide for the proprietor of a Ghanaian private school (${school?.name ?? "the school"}). You answer questions about school money: fees collection, cash and MoMo takings, daily service fees (lunch, transport, water), expenses, arrears and learner credit balances.

You are given VERIFIED figures (JSON) computed from the school's live database for a specific date range. Rules:
- Ground every financial claim in those VERIFIED figures only — never invent, estimate or recall numbers.
- Format money as ₵ with thousands separators and two decimals, e.g. ₵12,450.00.
- Always mention the date range the figures cover.
- If asked for details, a breakdown or a list, itemise the receipts, methods or categories provided in the data.
- If the data does not cover what was asked, say plainly what the figures do cover instead of guessing.
- If the question is not about money at all, answer it briefly and helpfully as a school-management assistant.
- Keep answers concise: at most 150 words unless the owner asked you to itemise.
- Warm, respectful Ghanaian business tone; no markdown tables.

VERIFIED DATA (range: ${range ? range.label : "not resolved"}):
${JSON.stringify(data)}`
    : `You are Ask Nyansapo, the AI copilot for a Ghanaian private school (${school?.name ?? "the school"}). Answer school-management questions briefly, practically and warmly (at most 150 words). Use ₵ for money. You do not have live figures for this question — do not invent any numbers.`;

  let answer = "";
  try {
    const { default: ZAI } = await import("z-ai-web-dev-sdk");
    const client = await ZAI.create();
    const completion = await client.chat.completions.create({
      messages: [{ role: "system", content: sys }, ...history, { role: "user", content: question }],
      thinking: { type: "disabled" },
    });
    answer = (completion.choices[0]?.message?.content ?? "").trim();
  } catch {
    answer = "";
  }
  if (!answer) {
    if (data.kind === "brief" || data.kind === "domain") answer = sectionsFallback(data.sections);
    else if (data.kind === "finance") answer = fallbackAnswer(range, data as FinanceData);
    else answer = "I could not reach the AI service just now — please try again in a moment.";
  }

  return NextResponse.json({
    answer,
    range: range ? { from: isoDay(range.from), to: isoDay(range.to), label: range.label } : null,
    data,
  });
}

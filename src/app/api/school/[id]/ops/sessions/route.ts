import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

/* ─────────────────────────────────────────────────────────────
   Wave 24 — Session Setup: academic years & terms management.

   GET /api/school/[id]/ops/sessions  (perm: settings.manage)
     Every academic year with its terms, per-term usage counts
     (results, cards, invoices, assessments, attendance, fee
     structures, lesson notes, syllabus topics, approvals) and the
     "days opened" map used for attendance-rate maths.

   POST /api/school/[id]/ops/sessions  (perm: settings.manage)
     action:"create-year"      { name, startsAt, endsAt, autoTerms? }
     action:"update-year"      { yearId, name?, startsAt?, endsAt? }
     action:"delete-year"      { yearId }        — blocked while in use / current
     action:"create-term"      { yearId, name?, sequence, startsAt?, endsAt? }
     action:"update-term"      { termId, name?, sequence?, startsAt?, endsAt? }
     action:"delete-term"      { termId }        — blocked while the term is in use / live
     action:"set-current-year" { yearId }        — single current year, auto-pins a term
     action:"set-current-term" { termId }        — single current term (pins its year too)

   Guards: every mutation is school-scoped through opsAuth +
   ownership checks; sequences stay unique inside a year; year
   names are unique per school; the whole "one current year / one
   current term" invariant is flipped inside a transaction because
   dashboards, registers and fee rails all resolve "the live term"
   through isCurrent flags.
   ───────────────────────────────────────────────────────────── */

/** "yyyy-MM-dd" → local midnight Date (no TZ drift). */
function parseDate(v: unknown): Date | null {
  if (typeof v !== "string") return null;
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(v.trim());
  if (!m) return null;
  const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
  return Number.isNaN(d.getTime()) ? null : d;
}

const DAY = 86_400_000;
const TERM_NAMES = ["First Term", "Second Term", "Third Term", "Fourth Term"];
const termNameFor = (seq: number) => TERM_NAMES[seq - 1] ?? `Term ${seq}`;

type Usage = {
  results: number; cards: number; invoices: number; assessments: number;
  attendanceSessions: number; feeStructures: number; lessonNotes: number;
  syllabusTopics: number; resultApprovals: number;
};
type UsageMap = Map<string, Usage>;
const emptyUsage = (): Usage => ({
  results: 0, cards: 0, invoices: 0, assessments: 0,
  attendanceSessions: 0, feeStructures: 0, lessonNotes: 0,
  syllabusTopics: 0, resultApprovals: 0,
});
const usageTotal = (u: Usage) => Object.values(u).reduce((s, n) => s + n, 0);
const usageSummary = (u: Usage) =>
  [
    u.results && `${u.results} result${u.results > 1 ? "s" : ""}`,
    u.cards && `${u.cards} report card${u.cards > 1 ? "s" : ""}`,
    u.invoices && `${u.invoices} invoice${u.invoices > 1 ? "s" : ""}`,
    u.assessments && `${u.assessments} assessment${u.assessments > 1 ? "s" : ""}`,
    u.attendanceSessions && `${u.attendanceSessions} attendance register${u.attendanceSessions > 1 ? "s" : ""}`,
    u.feeStructures && `${u.feeStructures} fee structure${u.feeStructures > 1 ? "s" : ""}`,
    u.lessonNotes && `${u.lessonNotes} lesson note${u.lessonNotes > 1 ? "s" : ""}`,
    u.syllabusTopics && `${u.syllabusTopics} syllabus topic${u.syllabusTopics > 1 ? "s" : ""}`,
    u.resultApprovals && `${u.resultApprovals} approval record${u.resultApprovals > 1 ? "s" : ""}`,
  ].filter(Boolean).join(", ");

async function loadUsage(schoolId: string): Promise<UsageMap> {
  const years = await db.academicYear.findMany({ where: { schoolId }, select: { id: true } });
  if (!years.length) return new Map();
  const termIds = (
    await db.term.findMany({ where: { academicYearId: { in: years.map((y) => y.id) } }, select: { id: true } })
  ).map((t) => t.id);
  if (!termIds.length) return new Map();
  const inTerm = { termId: { in: termIds } };
  const count = { _all: true } as const;
  const [results, cards, invoices, assessments, attendanceSessions, feeStructures, lessonNotes, syllabusTopics, resultApprovals] =
    await Promise.all([
      db.termResult.groupBy({ by: ["termId"], _count: count, where: inTerm }),
      db.reportCard.groupBy({ by: ["termId"], _count: count, where: inTerm }),
      db.invoice.groupBy({ by: ["termId"], _count: count, where: inTerm }),
      db.assessment.groupBy({ by: ["termId"], _count: count, where: inTerm }),
      db.attendanceSession.groupBy({ by: ["termId"], _count: count, where: inTerm }),
      db.feeStructure.groupBy({ by: ["termId"], _count: count, where: inTerm }),
      db.lessonNote.groupBy({ by: ["termId"], _count: count, where: inTerm }),
      db.syllabusTopic.groupBy({ by: ["termId"], _count: count, where: inTerm }),
      db.resultApproval.groupBy({ by: ["termId"], _count: count, where: inTerm }),
    ]);
  const map: UsageMap = new Map();
  const add = (rows: { termId: string; _count: { _all: number } }[], key: keyof Usage) => {
    for (const r of rows) {
      const u = map.get(r.termId) ?? emptyUsage();
      u[key] = r._count._all;
      map.set(r.termId, u);
    }
  };
  add(results, "results"); add(cards, "cards"); add(invoices, "invoices");
  add(assessments, "assessments"); add(attendanceSessions, "attendanceSessions");
  add(feeStructures, "feeStructures"); add(lessonNotes, "lessonNotes");
  add(syllabusTopics, "syllabusTopics"); add(resultApprovals, "resultApprovals");
  return map;
}

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  if (!schoolId) return NextResponse.json({ error: "schoolId is required" }, { status: 400 });

  const auth = await opsAuth(req, schoolId, "GET", { perms: ["settings.manage"] });
  if (!auth.ok) return auth.res;

  const [years, daysOpenedRows] = await Promise.all([
    db.academicYear.findMany({
      where: { schoolId },
      orderBy: [{ startsAt: "desc" }, { name: "asc" }],
      include: { terms: { orderBy: { sequence: "asc" } } },
    }),
    db.schoolSetting.findMany({ where: { schoolId, key: { startsWith: "term.daysOpened:" } } }),
  ]);
  const usage = await loadUsage(schoolId);

  const daysOpened: Record<string, number> = {};
  for (const d of daysOpenedRows) {
    const v = Number(JSON.parse(d.value));
    if (Number.isFinite(v)) daysOpened[d.key.slice("term.daysOpened:".length)] = v;
  }

  const curYear = years.find((y) => y.isCurrent) ?? null;
  const curTerm = curYear?.terms.find((t) => t.isCurrent) ?? null;

  return NextResponse.json({
    years: years.map((y) => ({
      id: y.id, name: y.name, isCurrent: y.isCurrent, startsAt: y.startsAt, endsAt: y.endsAt,
      terms: y.terms.map((t) => ({
        id: t.id, name: t.name, sequence: t.sequence, startsAt: t.startsAt, endsAt: t.endsAt,
        isCurrent: t.isCurrent, usage: usage.get(t.id) ?? emptyUsage(),
      })),
    })),
    daysOpened,
    current: { yearId: curYear?.id ?? null, termId: curTerm?.id ?? null },
  });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: pathSchoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const S = (v: unknown) => (typeof v === "string" ? v.trim() : "");
  const schoolId = pathSchoolId || S(body.schoolId);
  const action = S(body.action);
  if (!schoolId || !action) {
    return NextResponse.json({ error: "schoolId and action are required" }, { status: 400 });
  }

  const auth = await opsAuth(req, schoolId, "POST", { perms: ["settings.manage"] });
  if (!auth.ok) return auth.res;

  /* ── create-year ── */
  if (action === "create-year") {
    const name = S(body.name);
    const startsAt = parseDate(body.startsAt);
    const endsAt = parseDate(body.endsAt);
    const autoTerms = body.autoTerms !== false; // default: create First/Second/Third Term
    if (!name) return NextResponse.json({ error: "Year name is required (e.g. 2027/2028)" }, { status: 400 });
    if (name.length > 60) return NextResponse.json({ error: "Year name must be 60 characters or fewer" }, { status: 400 });
    if (!startsAt || !endsAt) return NextResponse.json({ error: "Start and end dates are required" }, { status: 400 });
    if (endsAt.getTime() <= startsAt.getTime()) return NextResponse.json({ error: "The year must end after it starts" }, { status: 400 });

    const clash = await db.academicYear.findFirst({
      where: { schoolId, name: { equals: name, mode: "insensitive" } },
      select: { id: true },
    });
    if (clash) return NextResponse.json({ error: `An academic year named “${name}” already exists` }, { status: 409 });

    const existingCount = await db.academicYear.count({ where: { schoolId } });

    const year = await db.$transaction(async (tx) => {
      const y = await tx.academicYear.create({
        data: { schoolId, name, startsAt, endsAt, isCurrent: existingCount === 0 },
      });
      if (autoTerms) {
        const span = endsAt.getTime() - startsAt.getTime();
        const len = span / 3;
        for (let i = 0; i < 3; i++) {
          const tStart = new Date(startsAt.getTime() + i * len);
          const tEnd = i === 2 ? endsAt : new Date(startsAt.getTime() + (i + 1) * len - DAY);
          await tx.term.create({
            data: {
              academicYearId: y.id,
              name: termNameFor(i + 1),
              sequence: i + 1,
              startsAt: tStart,
              endsAt: tEnd,
              // the school's very first year: pin Term 1 as the live term
              isCurrent: existingCount === 0 && i === 0,
            },
          });
        }
      }
      return y;
    });

    return NextResponse.json({
      ok: true,
      yearId: year.id,
      message: existingCount === 0
        ? `${name} created and set as the current year${autoTerms ? " with three terms" : ""}`
        : `${name} created${autoTerms ? " with three terms" : ""} — use “Set current” when the session begins`,
    });
  }

  /* ── update-year ── */
  if (action === "update-year") {
    const yearId = S(body.yearId);
    const year = await db.academicYear.findFirst({ where: { id: yearId, schoolId } });
    if (!year) return NextResponse.json({ error: "Academic year not found" }, { status: 404 });

    const data: { name?: string; startsAt?: Date; endsAt?: Date } = {};
    if (body.name !== undefined) {
      const name = S(body.name);
      if (!name) return NextResponse.json({ error: "Year name is required" }, { status: 400 });
      if (name.length > 60) return NextResponse.json({ error: "Year name must be 60 characters or fewer" }, { status: 400 });
      const clash = await db.academicYear.findFirst({
        where: { schoolId, name: { equals: name, mode: "insensitive" }, id: { not: yearId } },
        select: { id: true },
      });
      if (clash) return NextResponse.json({ error: `An academic year named “${name}” already exists` }, { status: 409 });
      data.name = name;
    }
    if (body.startsAt !== undefined || body.endsAt !== undefined) {
      const startsAt = body.startsAt !== undefined ? parseDate(body.startsAt) : year.startsAt;
      const endsAt = body.endsAt !== undefined ? parseDate(body.endsAt) : year.endsAt;
      if (!startsAt || !endsAt) return NextResponse.json({ error: "Invalid dates" }, { status: 400 });
      if (endsAt.getTime() <= startsAt.getTime()) return NextResponse.json({ error: "The year must end after it starts" }, { status: 400 });
      data.startsAt = startsAt;
      data.endsAt = endsAt;
    }
    await db.academicYear.update({ where: { id: yearId }, data });
    return NextResponse.json({ ok: true, message: `${data.name ?? year.name} updated` });
  }

  /* ── delete-year ── */
  if (action === "delete-year") {
    const yearId = S(body.yearId);
    const year = await db.academicYear.findFirst({ where: { id: yearId, schoolId }, include: { terms: { select: { id: true } } } });
    if (!year) return NextResponse.json({ error: "Academic year not found" }, { status: 404 });
    if (year.isCurrent) {
      return NextResponse.json({ error: `Cannot delete ${year.name} — it is the current year. Switch to another year first.` }, { status: 409 });
    }

    const usage = await loadUsage(schoolId);
    const used = year.terms
      .map((t) => ({ name: t.id, u: usage.get(t.id) ?? emptyUsage() }))
      .filter((x) => usageTotal(x.u) > 0)
      .sort((a, b) => usageTotal(b.u) - usageTotal(a.u));
    if (used.length) {
      const termName = await db.term.findUnique({ where: { id: used[0].name }, select: { name: true } });
      return NextResponse.json(
        { error: `Cannot delete ${year.name} — ${termName?.name ?? "a term"} still holds ${usageSummary(used[0].u)}` },
        { status: 409 },
      );
    }

    await db.$transaction(async (tx) => {
      await tx.term.deleteMany({ where: { academicYearId: yearId } });
      await tx.academicYear.delete({ where: { id: yearId } });
    });
    return NextResponse.json({ ok: true, message: `${year.name} and its empty terms removed` });
  }

  /* ── create-term ── */
  if (action === "create-term") {
    const yearId = S(body.yearId);
    const year = await db.academicYear.findFirst({ where: { id: yearId, schoolId }, include: { terms: { select: { sequence: true } } } });
    if (!year) return NextResponse.json({ error: "Academic year not found" }, { status: 404 });

    const sequence = Number(body.sequence);
    if (!Number.isInteger(sequence) || sequence < 1 || sequence > 12) {
      return NextResponse.json({ error: "Sequence must be a whole number between 1 and 12" }, { status: 400 });
    }
    if (year.terms.some((t) => t.sequence === sequence)) {
      return NextResponse.json({ error: `${year.name} already has a term #${sequence}` }, { status: 409 });
    }
    const name = S(body.name) || termNameFor(sequence);
    if (name.length > 80) return NextResponse.json({ error: "Term name must be 80 characters or fewer" }, { status: 400 });
    const startsAt = body.startsAt !== undefined && body.startsAt !== null && body.startsAt !== "" ? parseDate(body.startsAt) : year.startsAt;
    const endsAt = body.endsAt !== undefined && body.endsAt !== null && body.endsAt !== "" ? parseDate(body.endsAt) : year.endsAt;
    if (!startsAt || !endsAt) return NextResponse.json({ error: "Invalid dates" }, { status: 400 });
    if (endsAt.getTime() <= startsAt.getTime()) return NextResponse.json({ error: "The term must end after it starts" }, { status: 400 });

    const term = await db.term.create({
      data: { academicYearId: yearId, name, sequence, startsAt, endsAt },
    });
    return NextResponse.json({ ok: true, termId: term.id, message: `${name} added to ${year.name}` });
  }

  /* ── update-term ── */
  if (action === "update-term") {
    const termId = S(body.termId);
    const term = await db.term.findFirst({
      where: { id: termId, academicYear: { schoolId } },
      include: { academicYear: { include: { terms: { select: { id: true, sequence: true } } } } },
    });
    if (!term) return NextResponse.json({ error: "Term not found" }, { status: 404 });

    const data: { name?: string; sequence?: number; startsAt?: Date; endsAt?: Date } = {};
    if (body.name !== undefined) {
      const name = S(body.name);
      if (!name) return NextResponse.json({ error: "Term name is required" }, { status: 400 });
      if (name.length > 80) return NextResponse.json({ error: "Term name must be 80 characters or fewer" }, { status: 400 });
      data.name = name;
    }
    if (body.sequence !== undefined) {
      const sequence = Number(body.sequence);
      if (!Number.isInteger(sequence) || sequence < 1 || sequence > 12) {
        return NextResponse.json({ error: "Sequence must be a whole number between 1 and 12" }, { status: 400 });
      }
      if (term.academicYear.terms.some((t) => t.sequence === sequence && t.id !== termId)) {
        return NextResponse.json({ error: `${term.academicYear.name} already has a term #${sequence}` }, { status: 409 });
      }
      data.sequence = sequence;
    }
    const startsAt = body.startsAt !== undefined && body.startsAt !== null && body.startsAt !== "" ? parseDate(body.startsAt) : term.startsAt;
    const endsAt = body.endsAt !== undefined && body.endsAt !== null && body.endsAt !== "" ? parseDate(body.endsAt) : term.endsAt;
    if (!startsAt || !endsAt) return NextResponse.json({ error: "Invalid dates" }, { status: 400 });
    if (endsAt.getTime() <= startsAt.getTime()) return NextResponse.json({ error: "The term must end after it starts" }, { status: 400 });
    data.startsAt = startsAt;
    data.endsAt = endsAt;

    await db.term.update({ where: { id: termId }, data });
    return NextResponse.json({ ok: true, message: `${data.name ?? term.name} updated` });
  }

  /* ── delete-term ── */
  if (action === "delete-term") {
    const termId = S(body.termId);
    const term = await db.term.findFirst({ where: { id: termId, academicYear: { schoolId } }, include: { academicYear: { select: { name: true } } } });
    if (!term) return NextResponse.json({ error: "Term not found" }, { status: 404 });

    const usage = await loadUsage(schoolId);
    const u = usage.get(termId) ?? emptyUsage();
    if (usageTotal(u) > 0) {
      return NextResponse.json({ error: `Cannot delete ${term.name} — it still holds ${usageSummary(u)}` }, { status: 409 });
    }
    if (term.isCurrent) {
      return NextResponse.json({ error: `Cannot delete ${term.name} — it is the live term. Switch terms first.` }, { status: 409 });
    }

    await db.term.delete({ where: { id: termId } });
    return NextResponse.json({ ok: true, message: `${term.name} removed from ${term.academicYear.name}` });
  }

  /* ── set-current-year ── */
  if (action === "set-current-year") {
    const yearId = S(body.yearId);
    const year = await db.academicYear.findFirst({ where: { id: yearId, schoolId }, include: { terms: { orderBy: { sequence: "asc" } } } });
    if (!year) return NextResponse.json({ error: "Academic year not found" }, { status: 404 });
    if (!year.terms.length) {
      return NextResponse.json({ error: `${year.name} has no terms — add at least one term before switching` }, { status: 409 });
    }

    const now = new Date();
    const pick = year.terms.find((t) => t.startsAt <= now && now <= t.endsAt) ?? year.terms[0];
    await db.$transaction(async (tx) => {
      await tx.academicYear.updateMany({ where: { schoolId, NOT: { id: yearId } }, data: { isCurrent: false } });
      await tx.academicYear.update({ where: { id: yearId }, data: { isCurrent: true } });
      await tx.term.updateMany({ where: { academicYearId: yearId, NOT: { id: pick.id } }, data: { isCurrent: false } });
      await tx.term.update({ where: { id: pick.id }, data: { isCurrent: true } });
    });

    const autoNote = pick.startsAt <= now && now <= pick.endsAt
      ? `${pick.name} is live (today falls inside it)`
      : `${pick.name} pinned as the live term — switch when it actually begins`;
    return NextResponse.json({ ok: true, message: `${year.name} is now the current year · ${autoNote}` });
  }

  /* ── set-current-term ── */
  if (action === "set-current-term") {
    const termId = S(body.termId);
    const term = await db.term.findFirst({ where: { id: termId, academicYear: { schoolId } }, include: { academicYear: { select: { id: true, name: true } } } });
    if (!term) return NextResponse.json({ error: "Term not found" }, { status: 404 });

    await db.$transaction(async (tx) => {
      await tx.term.updateMany({ where: { academicYear: { schoolId }, NOT: { id: termId } }, data: { isCurrent: false } });
      await tx.term.update({ where: { id: termId }, data: { isCurrent: true } });
      await tx.academicYear.updateMany({ where: { schoolId, NOT: { id: term.academicYearId } }, data: { isCurrent: false } });
      await tx.academicYear.update({ where: { id: term.academicYearId }, data: { isCurrent: true } });
    });

    return NextResponse.json({ ok: true, message: `${term.name} · ${term.academicYear.name} is now the live session` });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

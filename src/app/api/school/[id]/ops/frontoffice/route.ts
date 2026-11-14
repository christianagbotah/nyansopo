import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

// ── Front Office (wave 29 — the front desk books) ──
// The desk beside the gate every Ghanaian school keeps: the Visitors
// Book (who came, to see whom, pass number, time in/out), the
// Appointments Book (scheduled meetings with parents, staff & vendors),
// the Phone Call Log (incoming/outgoing with follow-up flags), the
// Postal Register (incoming/outgoing letters, parcels & dispatch) and
// the School Events Calendar (PTA meetings, open day, sports,
// speech & prize-giving day, vacations).

const VISITOR_CATEGORIES = ["PARENT", "VENDOR", "GOVT", "ALUMNI", "OTHER"];
const APPT_ROLES = ["PARENT", "STAFF", "VENDOR", "GOVT", "OTHER"];
const APPT_STATUSES = ["SCHEDULED", "CONFIRMED", "COMPLETED", "CANCELLED", "NO_SHOW"];
const CALL_DIRECTIONS = ["INCOMING", "OUTGOING"];
const POSTAL_DIRECTIONS = ["INCOMING", "OUTGOING"];
const POSTAL_TYPES = ["LETTER", "PARCEL", "DOCUMENT", "COURIER"];
const POSTAL_STATUSES = ["PENDING", "DISPATCHED", "COLLECTED"];
const EVENT_CATEGORIES = ["PTA_MEETING", "OPEN_DAY", "SPORTS", "PRIZE_GIVING", "EXAMINATION", "HOLIDAY", "OTHER"];
const EVENT_AUDIENCES = ["ALL", "STAFF", "PARENTS", "STUDENTS"];

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });
const optStr = (v: unknown): string | null => {
  if (typeof v !== "string") return null;
  const t = v.trim();
  return t.length ? t : null;
};
const enumVal = (v: unknown, allowed: string[]): string | null => {
  if (typeof v !== "string" || !v.trim()) return null;
  const u = v.trim().toUpperCase();
  const hit = allowed.find((a) => a.toUpperCase() === u);
  return hit ?? null;
};
/** Parse a YYYY-MM-DD string to an exact UTC midnight Date (date-only semantics). */
const dayStart = (v: unknown): Date | null => {
  if (typeof v !== "string" || !/^\d{4}-\d{2}-\d{2}/.test(String(v).trim())) return null;
  const d = new Date(`${String(v).trim().slice(0, 10)}T00:00:00.000Z`);
  return isNaN(d.getTime()) ? null : d;
};
const isoDay = (d: Date | string | null | undefined): string | null => {
  if (!d) return null;
  const date = d instanceof Date ? d : new Date(d);
  return isNaN(date.getTime()) ? null : date.toISOString().slice(0, 10);
};
const iso = (d: Date | string | null | undefined): string | null => {
  if (!d) return null;
  const date = d instanceof Date ? d : new Date(d);
  return isNaN(date.getTime()) ? null : date.toISOString();
};
/** Parse an ISO datetime-ish string; null when invalid. */
const dateTime = (v: unknown): Date | null => {
  if (typeof v !== "string" || !v.trim()) return null;
  const d = new Date(v.trim());
  return isNaN(d.getTime()) ? null : d;
};
const minutesAgo = (d: Date | string | null | undefined): number | null => {
  if (!d) return null;
  const date = d instanceof Date ? d : new Date(d);
  return isNaN(date.getTime()) ? null : Math.max(0, Math.round((Date.now() - date.getTime()) / 60_000));
};

/** Next sequential visitor pass number for the school (P-001, P-002, …). */
async function nextPassNo(schoolId: string): Promise<string> {
  const rows = await db.visitor.findMany({
    where: { schoolId, passNo: { not: null } },
    select: { passNo: true },
  });
  const max = rows.reduce((m, r) => Math.max(m, parseInt(r.passNo?.replace(/\D/g, "") ?? "0", 10) || 0), 0);
  return `P-${String(max + 1).padStart(3, "0")}`;
}

const toVisitor = (v: any) => ({
  id: v.id,
  name: v.name,
  phone: v.phone ?? null,
  organisation: v.organisation ?? null,
  category: v.category,
  purpose: v.purpose,
  personToSee: v.personToSee ?? null,
  passNo: v.passNo ?? null,
  timeIn: iso(v.timeIn),
  timeOut: iso(v.timeOut),
  minutesOnSite: v.timeOut ? null : minutesAgo(v.timeIn),
  status: v.status,
  badgeIssued: v.badgeIssued,
  notes: v.notes ?? null,
  recordedByName: v.recordedByName ?? null,
});

const toAppt = (a: any) => ({
  id: a.id,
  title: a.title,
  withName: a.withName,
  withRole: a.withRole,
  personToSee: a.personToSee ?? null,
  scheduledAt: iso(a.scheduledAt),
  durationMin: a.durationMin ?? null,
  location: a.location ?? null,
  purpose: a.purpose ?? null,
  status: a.status,
  outcome: a.outcome ?? null,
  notes: a.notes ?? null,
  createdByName: a.createdByName ?? null,
});

const toCall = (c: any) => ({
  id: c.id,
  direction: c.direction,
  contactName: c.contactName,
  phone: c.phone ?? null,
  regarding: c.regarding,
  handledByName: c.handledByName ?? null,
  followUp: c.followUp,
  followUpDone: c.followUpDone,
  calledAt: iso(c.calledAt),
  notes: c.notes ?? null,
});

const toPostal = (p: any) => ({
  id: p.id,
  direction: p.direction,
  type: p.type,
  refNo: p.refNo ?? null,
  fromParty: p.fromParty,
  toParty: p.toParty,
  receivedAt: iso(p.receivedAt),
  dispatchedAt: iso(p.dispatchedAt),
  collectedBy: p.collectedBy ?? null,
  status: p.status,
  notes: p.notes ?? null,
  recordedByName: p.recordedByName ?? null,
});

const toEvent = (e: any) => ({
  id: e.id,
  termId: e.termId ?? null,
  termName: e.term?.name ?? null,
  title: e.title,
  category: e.category,
  startsAt: isoDay(e.startsAt),
  endsAt: isoDay(e.endsAt),
  location: e.location ?? null,
  description: e.description ?? null,
  audience: e.audience,
  createdByName: e.createdByName ?? null,
});

// ── GET /api/school/[id]/ops/frontoffice — board | visitors | appointments | calls | postal | events | analytics ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["frontoffice.view"] });
  if (!auth.ok) return auth.res;

  const sp = req.nextUrl.searchParams;
  const action = sp.get("action") ?? "board";
  const today = isoDay(new Date())!;
  const dayZero = dayStart(today)!;
  const dayEnd = new Date(dayZero);
  dayEnd.setUTCDate(dayEnd.getUTCDate() + 1);

  /* ── board: the front desk at a glance ── */
  if (action === "board") {
    const [onSite, todayVisitors, todayAppts, openFollowUps, postalPending, upcomingEvents, nextAppt] = await Promise.all([
      db.visitor.findMany({ where: { schoolId, status: "ON_SITE" }, orderBy: { timeIn: "asc" } }),
      db.visitor.count({ where: { schoolId, timeIn: { gte: dayZero, lt: dayEnd } } }),
      db.appointment.findMany({
        where: { schoolId, scheduledAt: { gte: dayZero, lt: dayEnd }, status: { in: ["SCHEDULED", "CONFIRMED"] } },
        orderBy: { scheduledAt: "asc" },
      }),
      db.phoneCall.count({ where: { schoolId, followUp: true, followUpDone: false } }),
      db.postalItem.count({ where: { schoolId, status: "PENDING" } }),
      db.schoolEvent.findMany({ where: { schoolId, startsAt: { gte: dayZero } }, orderBy: { startsAt: "asc" }, take: 6 }),
      db.appointment.findFirst({
        where: { schoolId, scheduledAt: { gte: new Date() }, status: { in: ["SCHEDULED", "CONFIRMED"] } },
        orderBy: { scheduledAt: "asc" },
      }),
    ]);
    return NextResponse.json({
      date: today,
      stats: {
        onSite: onSite.length,
        visitorsToday: todayVisitors,
        appointmentsToday: todayAppts.length,
        followUpsOpen: openFollowUps,
        postalPending,
        upcomingEvents: upcomingEvents.length,
      },
      onSite: onSite.map(toVisitor),
      todayAppointments: todayAppts.map(toAppt),
      nextAppointment: nextAppt ? toAppt(nextAppt) : null,
      upcomingEvents: upcomingEvents.map(toEvent),
    });
  }

  /* ── visitors: the visitors book ── */
  if (action === "visitors") {
    const date = dayStart(sp.get("date"));
    const open = sp.get("open") === "1";
    const search = optStr(sp.get("search"))?.toLowerCase() ?? null;
    const rows = await db.visitor.findMany({
      where: {
        schoolId,
        ...(date ? { timeIn: { gte: date, lt: new Date(date.getTime() + 86_400_000) } } : {}),
        ...(open ? { status: "ON_SITE" } : {}),
      },
      orderBy: { timeIn: "desc" },
      take: 300,
    });
    const filtered = search
      ? rows.filter((r) =>
          [r.name, r.organisation, r.purpose, r.personToSee].some((f) => (f ?? "").toLowerCase().includes(search)),
        )
      : rows;
    const onSite = rows.filter((r) => r.status === "ON_SITE").length;
    return NextResponse.json({
      date: isoDay(date) ?? "all",
      onSite,
      visitors: filtered.map(toVisitor),
    });
  }

  /* ── appointments: the appointments book ── */
  if (action === "appointments") {
    const status = enumVal(sp.get("status"), APPT_STATUSES);
    const scope = sp.get("scope") ?? "upcoming"; // upcoming | past | today
    let where: any = { schoolId };
    if (scope === "today") where.scheduledAt = { gte: dayZero, lt: dayEnd };
    else if (scope === "upcoming") where.scheduledAt = { gte: new Date() };
    else if (scope === "past") where.scheduledAt = { lt: dayZero };
    if (status) where.status = status;
    const rows = await db.appointment.findMany({ where, orderBy: { scheduledAt: scope === "past" ? "desc" : "asc" }, take: 200 });
    const counts = await db.appointment.groupBy({ by: ["status"], where: { schoolId }, _count: true });
    const byStatus: Record<string, number> = {};
    for (const c of counts) byStatus[c.status] = c._count;
    return NextResponse.json({
      scope,
      counts: byStatus,
      appointments: rows.map(toAppt),
    });
  }

  /* ── calls: the phone call log ── */
  if (action === "calls") {
    const followUpOnly = sp.get("followUp") === "1";
    const direction = enumVal(sp.get("direction"), CALL_DIRECTIONS);
    const rows = await db.phoneCall.findMany({
      where: { schoolId, ...(followUpOnly ? { followUp: true, followUpDone: false } : {}), ...(direction ? { direction } : {}) },
      orderBy: { calledAt: "desc" },
      take: 200,
    });
    return NextResponse.json({
      openFollowUps: rows.filter((r) => r.followUp && !r.followUpDone).length,
      calls: rows.map(toCall),
    });
  }

  /* ── postal: the postal register ── */
  if (action === "postal") {
    const direction = enumVal(sp.get("direction"), POSTAL_DIRECTIONS);
    const status = enumVal(sp.get("status"), POSTAL_STATUSES);
    const rows = await db.postalItem.findMany({
      where: { schoolId, ...(direction ? { direction } : {}), ...(status ? { status } : {}) },
      orderBy: { receivedAt: "desc" },
      take: 200,
    });
    return NextResponse.json({
      pending: rows.filter((r) => r.status === "PENDING").length,
      items: rows.map(toPostal),
    });
  }

  /* ── events: the school events calendar ── */
  if (action === "events") {
    const termId = optStr(sp.get("termId"));
    const upcoming = sp.get("upcoming") === "1";
    const rows = await db.schoolEvent.findMany({
      where: {
        schoolId,
        ...(termId ? { termId } : {}),
        ...(upcoming ? { startsAt: { gte: dayZero } } : {}),
      },
      include: { term: { select: { name: true, isCurrent: true } } },
      orderBy: { startsAt: "asc" },
      take: 300,
    });
    const terms = await db.term.findMany({
      where: { academicYear: { schoolId } },
      select: { id: true, name: true, isCurrent: true, startsAt: true },
      orderBy: [{ startsAt: "asc" }],
    });
    return NextResponse.json({
      today,
      terms: terms.map((t) => ({ id: t.id, name: t.name, isCurrent: t.isCurrent, startsAt: isoDay(t.startsAt) })),
      events: rows.map(toEvent),
    });
  }

  /* ── analytics: 30-day front desk traffic & mixes ── */
  if (action === "analytics") {
    const since = new Date();
    since.setUTCDate(since.getUTCDate() - 30);
    const [visitors, appts, calls, postal, events] = await Promise.all([
      db.visitor.findMany({ where: { schoolId, timeIn: { gte: since } }, select: { timeIn: true, category: true, status: true, timeOut: true } }),
      db.appointment.findMany({ where: { schoolId, scheduledAt: { gte: since } }, select: { status: true, scheduledAt: true } }),
      db.phoneCall.findMany({ where: { schoolId, calledAt: { gte: since } }, select: { direction: true, followUp: true, followUpDone: true, calledAt: true } }),
      db.postalItem.findMany({ where: { schoolId, receivedAt: { gte: since } }, select: { direction: true, type: true, status: true } }),
      db.schoolEvent.findMany({ where: { schoolId, startsAt: { gte: dayZero } }, orderBy: { startsAt: "asc" }, take: 30 }),
    ]);

    // visitor traffic by day (14 days, newest last)
    const traffic: { date: string; visitors: number }[] = [];
    for (let i = 13; i >= 0; i--) {
      const d = new Date(dayZero);
      d.setUTCDate(d.getUTCDate() - i);
      const next = new Date(d);
      next.setUTCDate(next.getUTCDate() + 1);
      traffic.push({ date: isoDay(d)!, visitors: visitors.filter((v) => v.timeIn >= d && v.timeIn < next).length });
    }

    const categoryMix = VISITOR_CATEGORIES.map((c) => ({
      category: c,
      count: visitors.filter((v) => v.category === c).length,
    })).filter((x) => x.count > 0);

    const peakHours = Array.from({ length: 12 }, (_, i) => {
      const hour = i + 7; // 07:00 – 18:00 window
      return {
        hour: `${String(hour).padStart(2, "0")}:00`,
        arrivals: visitors.filter((v) => new Date(v.timeIn).getUTCHours() === hour).length,
      };
    });

    const apptMix = APPT_STATUSES.map((s) => ({ status: s, count: appts.filter((a) => a.status === s).length })).filter((x) => x.count > 0);
    const callSplit = {
      incoming: calls.filter((c) => c.direction === "INCOMING").length,
      outgoing: calls.filter((c) => c.direction === "OUTGOING").length,
      followUpsOpen: calls.filter((c) => c.followUp && !c.followUpDone).length,
    };
    const postalMix = POSTAL_STATUSES.map((s) => ({ status: s, count: postal.filter((p) => p.status === s).length })).filter((x) => x.count > 0);

    const avgStay = visitors
      .filter((v) => v.timeOut)
      .map((v) => (new Date(v.timeOut!).getTime() - new Date(v.timeIn).getTime()) / 60_000);
    const stillOnSite = visitors.filter((v) => v.status === "ON_SITE").length;

    return NextResponse.json({
      windowDays: 30,
      visitors: {
        total: visitors.length,
        onSiteNow: stillOnSite,
        avgStayMin: avgStay.length ? Math.round(avgStay.reduce((s, m) => s + m, 0) / avgStay.length) : null,
        categoryMix,
      },
      traffic,
      peakHours,
      appointments: { total: appts.length, mix: apptMix },
      calls: { total: calls.length, ...callSplit },
      postal: { total: postal.length, mix: postalMix },
      upcomingEvents: events.map(toEvent),
    });
  }

  return bad("Unknown action", 400);
}

// ── POST /api/school/[id]/ops/frontoffice ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["frontoffice.manage"] });
  if (!auth.ok) return auth.res;
  const actorName = auth.user.fullName ?? "Front desk";

  let body: any;
  try { body = await req.json(); } catch { return bad("Invalid JSON body"); }
  const action = optStr(body?.action);

  /* ── visitor-in: book a visitor into the Visitors Book ── */
  if (action === "visitor-in") {
    const name = optStr(body?.name);
    const purpose = optStr(body?.purpose);
    if (!name) return bad("Visitor name is required");
    if (!purpose) return bad("Purpose of visit is required");
    const category = enumVal(body?.category, VISITOR_CATEGORIES) ?? "OTHER";
    const passNo = await nextPassNo(schoolId);
    const row = await db.visitor.create({
      data: {
        schoolId, name, purpose, category,
        phone: optStr(body?.phone),
        organisation: optStr(body?.organisation),
        personToSee: optStr(body?.personToSee),
        passNo, badgeIssued: body?.badgeIssued === false ? false : true,
        notes: optStr(body?.notes),
        recordedByName: actorName,
      },
    });
    return NextResponse.json({ ok: true, visitor: toVisitor(row) });
  }

  /* ── visitor-out: check a visitor out of the book ── */
  if (action === "visitor-out") {
    const id = optStr(body?.visitorId);
    if (!id) return bad("visitorId is required");
    const v = await db.visitor.findUnique({ where: { id } });
    if (!v || v.schoolId !== schoolId) return bad("Visitor not found", 404);
    if (v.status === "DEPARTED") return bad("Visitor already checked out");
    const row = await db.visitor.update({ where: { id }, data: { status: "DEPARTED", timeOut: new Date() } });
    return NextResponse.json({ ok: true, visitor: toVisitor(row) });
  }

  /* ── appointment-create: book a meeting ── */
  if (action === "appointment-create") {
    const title = optStr(body?.title);
    const withName = optStr(body?.withName);
    const scheduledAt = dateTime(body?.scheduledAt);
    if (!title) return bad("Title is required");
    if (!withName) return bad("withName (who the meeting is with) is required");
    if (!scheduledAt) return bad("scheduledAt is required (ISO datetime)");
    const row = await db.appointment.create({
      data: {
        schoolId, title, withName, scheduledAt,
        withRole: enumVal(body?.withRole, APPT_ROLES) ?? "PARENT",
        personToSee: optStr(body?.personToSee),
        durationMin: typeof body?.durationMin === "number" && body.durationMin > 0 && body.durationMin <= 600 ? Math.round(body.durationMin) : null,
        location: optStr(body?.location),
        purpose: optStr(body?.purpose),
        notes: optStr(body?.notes),
        status: "SCHEDULED",
        createdByName: actorName,
      },
    });
    return NextResponse.json({ ok: true, appointment: toAppt(row) });
  }

  /* ── appointment-status: move a meeting through its workflow ── */
  if (action === "appointment-status") {
    const id = optStr(body?.appointmentId);
    const status = enumVal(body?.status, APPT_STATUSES);
    if (!id || !status) return bad("appointmentId and status are required");
    const a = await db.appointment.findUnique({ where: { id } });
    if (!a || a.schoolId !== schoolId) return bad("Appointment not found", 404);
    const row = await db.appointment.update({
      where: { id },
      data: { status, outcome: optStr(body?.outcome) ?? a.outcome },
    });
    return NextResponse.json({ ok: true, appointment: toAppt(row) });
  }

  /* ── call-log: record a phone call ── */
  if (action === "call-log") {
    const direction = enumVal(body?.direction, CALL_DIRECTIONS);
    const contactName = optStr(body?.contactName);
    const regarding = optStr(body?.regarding);
    if (!direction) return bad("direction must be INCOMING or OUTGOING");
    if (!contactName) return bad("contactName is required");
    if (!regarding) return bad("regarding is required");
    const row = await db.phoneCall.create({
      data: {
        schoolId, direction, contactName, regarding,
        phone: optStr(body?.phone),
        handledByName: optStr(body?.handledByName) ?? actorName,
        followUp: body?.followUp === true,
        calledAt: dateTime(body?.calledAt) ?? new Date(),
        notes: optStr(body?.notes),
      },
    });
    return NextResponse.json({ ok: true, call: toCall(row) });
  }

  /* ── call-followup: close (or reopen) a call's follow-up ── */
  if (action === "call-followup") {
    const id = optStr(body?.callId);
    const done = body?.done !== false; // default: mark done
    if (!id) return bad("callId is required");
    const c = await db.phoneCall.findUnique({ where: { id } });
    if (!c || c.schoolId !== schoolId) return bad("Call not found", 404);
    if (!c.followUp) return bad("Call is not flagged for follow-up");
    const row = await db.phoneCall.update({ where: { id }, data: { followUpDone: done } });
    return NextResponse.json({ ok: true, call: toCall(row) });
  }

  /* ── postal-create: register mail / parcel ── */
  if (action === "postal-create") {
    const direction = enumVal(body?.direction, POSTAL_DIRECTIONS);
    const fromParty = optStr(body?.fromParty);
    const toParty = optStr(body?.toParty);
    if (!direction) return bad("direction must be INCOMING or OUTGOING");
    if (!fromParty) return bad("fromParty is required");
    if (!toParty) return bad("toParty is required");
    const row = await db.postalItem.create({
      data: {
        schoolId, direction, fromParty, toParty,
        type: enumVal(body?.type, POSTAL_TYPES) ?? "LETTER",
        refNo: optStr(body?.refNo),
        receivedAt: dateTime(body?.receivedAt) ?? new Date(),
        notes: optStr(body?.notes),
        status: "PENDING",
        recordedByName: actorName,
      },
    });
    return NextResponse.json({ ok: true, item: toPostal(row) });
  }

  /* ── postal-status: dispatch outgoing / collect incoming ── */
  if (action === "postal-status") {
    const id = optStr(body?.itemId);
    const status = enumVal(body?.status, POSTAL_STATUSES);
    if (!id || !status) return bad("itemId and status are required");
    const item = await db.postalItem.findUnique({ where: { id } });
    if (!item || item.schoolId !== schoolId) return bad("Postal item not found", 404);
    const data: any = { status };
    if (status === "DISPATCHED") {
      if (item.direction !== "OUTGOING") return bad("Only outgoing items can be dispatched");
      if (!item.dispatchedAt) data.dispatchedAt = new Date();
    }
    if (status === "COLLECTED") {
      const collectedBy = optStr(body?.collectedBy);
      if (!collectedBy) return bad("collectedBy is required when collecting");
      if (item.direction !== "INCOMING") return bad("Only incoming items are collected");
      data.collectedBy = collectedBy;
      if (!item.dispatchedAt) data.dispatchedAt = new Date();
    }
    const row = await db.postalItem.update({ where: { id }, data });
    return NextResponse.json({ ok: true, item: toPostal(row) });
  }

  /* ── event-create: put an event on the school calendar ── */
  if (action === "event-create") {
    const title = optStr(body?.title);
    const startsAt = dayStart(body?.startsAt);
    if (!title) return bad("Title is required");
    if (!startsAt) return bad("startsAt is required (YYYY-MM-DD)");
    const endsAt = dayStart(body?.endsAt);
    if (endsAt && endsAt < startsAt) return bad("endsAt is before startsAt");
    const termId = optStr(body?.termId);
    if (termId) {
      const term = await db.term.findUnique({ where: { id: termId }, select: { id: true, academicYear: { select: { schoolId: true } } } });
      if (!term || term.academicYear.schoolId !== schoolId) return bad("Term not found for this school", 404);
    }
    const row = await db.schoolEvent.create({
      data: {
        schoolId, title, startsAt, endsAt,
        termId: termId ?? null,
        category: enumVal(body?.category, EVENT_CATEGORIES) ?? "OTHER",
        location: optStr(body?.location),
        description: optStr(body?.description),
        audience: enumVal(body?.audience, EVENT_AUDIENCES) ?? "ALL",
        createdByName: actorName,
      },
    });
    return NextResponse.json({ ok: true, event: toEvent(row) });
  }

  /* ── event-delete: take an event off the calendar ── */
  if (action === "event-delete") {
    const id = optStr(body?.eventId);
    if (!id) return bad("eventId is required");
    const e = await db.schoolEvent.findUnique({ where: { id } });
    if (!e || e.schoolId !== schoolId) return bad("Event not found", 404);
    await db.schoolEvent.delete({ where: { id } });
    return NextResponse.json({ ok: true });
  }

  return bad("Unknown action", 400);
}

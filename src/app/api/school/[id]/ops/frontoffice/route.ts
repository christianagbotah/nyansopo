import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { getUser, canOperateSchool } from "@/lib/session";
import { canAny } from "@/lib/rbac";
import { sendSms } from "@/lib/sms";
import { sendEmail } from "@/lib/email";
import { genApptCode, newDecisionToken, apptDecisionLink, teachingClash } from "@/lib/appointments";

// ── Front Office (wave 29 — the front desk books; wave 72 — the booking ──
// ── workflow: staff-linked appointments with live response & T-2 alarms) ──
// The desk beside the gate every Ghanaian school keeps: the Visitors
// Book (who came, to see whom, pass number, time in/out), the
// Appointments Book (scheduled meetings with parents, staff & vendors),
// the Phone Call Log (incoming/outgoing with follow-up flags), the
// Postal Register (incoming/outgoing letters, parcels & dispatch) and
// the School Events Calendar (PTA meetings, open day, sports,
// speech & prize-giving day, vacations).
//
// wave 72 — the front desk books the visitor in to see a SELECTED staff
// member; the request alerts that staff member instantly (SMS + email +
// in-app bell) and they ACCEPT / DECLINE / RESCHEDULE from their Home;
// responses land on the desk board in real time, and a T-2-minute alarm
// rings at the desk (chime + banner) while the visited staff gets an SMS
// reminder. Odd-hours rule: visitors cannot see teachers at odd hours —
// only administration staff (head, bursar …) may be booked any time.

const VISITOR_CATEGORIES = ["PARENT", "VENDOR", "GOVT", "ALUMNI", "OTHER"];
const APPT_ROLES = ["PARENT", "STAFF", "VENDOR", "GOVT", "OTHER"];
const APPT_STATUSES = ["SCHEDULED", "CONFIRMED", "DECLINED", "RESCHEDULED", "COMPLETED", "CANCELLED", "NO_SHOW"];
// wave 72 — statuses that still have a visitor coming in (live board +
// countdowns + T-2 alarms); DECLINED/CANCELLED/COMPLETED/NO_SHOW do not
const APPT_ACTIVE = ["SCHEDULED", "CONFIRMED", "RESCHEDULED"];
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

// ── wave 72 — odd-hours rule ──
// School hours: Mon–Fri 07:00–17:00 (Ghana runs on GMT, so UTC == local
// wall time — the rest of this module already relies on that). Outside
// those hours (and weekends), a visitor may only be booked to see
// ADMINISTRATION staff: teachers and other hourly staff cannot host
// visitors at odd hours, but the head's door stays open.
const SCHOOL_HOURS = { start: 7, end: 17 };
const hh = (n: number) => String(n).padStart(2, "0");
const schoolHoursLabel = `Mon–Fri ${hh(SCHOOL_HOURS.start)}:00–${hh(SCHOOL_HOURS.end)}:00`;
const isOddHours = (d: Date) =>
  d.getUTCDay() === 0 || d.getUTCDay() === 6 || d.getUTCHours() < SCHOOL_HOURS.start || d.getUTCHours() >= SCHOOL_HOURS.end;
/** The odd-hours rejection message for a staff member at a time, or null when the slot is fine. */
const oddHoursError = (staff: { fullName: string; category: string } | null, at: Date): string | null =>
  staff && staff.category !== "ADMINISTRATION" && isOddHours(at)
    ? `Odd hours: visitors cannot see ${staff.fullName} at ${at.toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" })} on ${at.toLocaleDateString("en-GB", { weekday: "long" })} — ${staff.category === "TEACHING" ? "teachers" : "non-teaching staff"} are bookable ${schoolHoursLabel} only. Only administration staff (head, bursar …) can take visitors at odd hours — pick a school-hours slot or choose an administrator.`
    : null;
const trunc = (s: string, n: number) => (s.length > n ? `${s.slice(0, n - 1)}…` : s);

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
  personToSeeStaffId: v.personToSeeStaffId ?? null,
  alertedSms: v.alertedSms ?? false,
  alertedEmail: v.alertedEmail ?? false,
  alertedInApp: v.alertedInApp ?? false,
  passNo: v.passNo ?? null,
  timeIn: iso(v.timeIn),
  timeOut: iso(v.timeOut),
  minutesOnSite: v.timeOut ? null : minutesAgo(v.timeIn),
  status: v.status,
  badgeIssued: v.badgeIssued,
  notes: v.notes ?? null,
  recordedByName: v.recordedByName ?? null,
});

/** Active staff options for the "To see who" picker (wave 59) — the front
 *  desk selects the staff member, who is then alerted instantly on arrival. */
const staffOptions = async (schoolId: string) =>
  (await db.staff.findMany({
    where: { schoolId, active: true },
    select: { id: true, fullName: true, role: true, title: true, category: true, position: true, phone: true, email: true },
    orderBy: [{ category: "asc" }, { fullName: "asc" }],
  })).map((s) => ({
    id: s.id, fullName: s.fullName, role: s.role, title: s.title ?? null,
    category: s.category, position: s.position ?? null,
    hasPhone: !!s.phone, hasEmail: !!s.email,
  }));

const toAppt = (a: any) => ({
  id: a.id,
  title: a.title,
  withName: a.withName,
  withRole: a.withRole,
  visitorPhone: a.visitorPhone ?? null,
  visitorOrg: a.visitorOrg ?? null,
  personToSee: a.personToSee ?? null,
  staffId: a.staffId ?? null,
  staffName: a.staff?.fullName ?? null,
  staffCategory: a.staff?.category ?? null,
  scheduledAt: iso(a.scheduledAt),
  durationMin: a.durationMin ?? null,
  location: a.location ?? null,
  purpose: a.purpose ?? null,
  status: a.status,
  outcome: a.outcome ?? null,
  notes: a.notes ?? null,
  createdByName: a.createdByName ?? null,
  code: a.code ?? null,
  // wave 72 — response trail + alert/reminder state
  respondedAt: iso(a.respondedAt),
  respondedByName: a.respondedByName ?? null,
  responseNote: a.responseNote ?? null,
  rescheduledFrom: iso(a.rescheduledFrom),
  relayedAt: iso(a.relayedAt),
  alertedSms: a.alertedSms ?? false,
  alertedEmail: a.alertedEmail ?? false,
  alertedInApp: a.alertedInApp ?? false,
  minutesUntil: a.scheduledAt ? Math.round((new Date(a.scheduledAt).getTime() - Date.now()) / 60_000) : null,
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

// ── GET /api/school/[id]/ops/frontoffice — my | board | live | visitors | appointments | calls | postal | events | analytics ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const sp = req.nextUrl.searchParams;
  const action = sp.get("action") ?? "board";
  const today = isoDay(new Date())!;
  const dayZero = dayStart(today)!;
  const dayEnd = new Date(dayZero);
  dayEnd.setUTCDate(dayEnd.getUTCDate() + 1);

  /* ── my (wave 72): staff self-service — NO front-office permission needed.
     The signed-in staff member sees the appointment requests awaiting
     their response and their own upcoming meetings (the Home card + the
     bell deep-link land here). Also runs the T-2-min reminder engine for
     their own starting-soon meeting — any signed-in staff session drives
     its own reminder; reminderSentAt dedupes across pollers. */
  if (action === "my") {
    const user = await getUser(req);
    if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
    if (!canOperateSchool(user, schoolId)) return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    if (!user.staffId) return NextResponse.json({ staffLinked: false, requests: [], upcoming: [] });
    const now = new Date();
    const rows = await db.appointment.findMany({
      where: { schoolId, staffId: user.staffId, scheduledAt: { gte: new Date(now.getTime() - 45 * 60_000) } },
      include: { staff: true },
      orderBy: { scheduledAt: "asc" },
      take: 40,
    });
    // T-2-min reminder engine (my own starting-soon meeting)
    const horizon = new Date(now.getTime() + 2 * 60_000);
    const grace = new Date(now.getTime() - 60_000);
    const smsSetting = await db.smsSetting.findUnique({ where: { schoolId } });
    let remindedMe = false;
    for (const a of rows) {
      if (!APPT_ACTIVE.includes(a.status) || a.reminderSentAt) continue;
      if (a.scheduledAt > horizon || a.scheduledAt < grace) continue;
      const st = a.staff;
      if (st && smsSetting?.autoApptAlerts && st.smsNotify && st.phone) {
        const t = new Date(a.scheduledAt).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" });
        await sendSms({
          db, schoolId, recipientName: st.fullName, recipientPhone: st.phone,
          body: `MEETING IN 2 MINUTES: "${trunc(a.title, 40)}" with ${a.withName} at ${t}${a.location ? ` — ${a.location}` : ""}. The front desk will show them in.`,
          category: "FRONT_OFFICE", sentByName: "Front desk",
        });
        remindedMe = true;
      }
      await db.appointment.update({ where: { id: a.id }, data: { reminderSentAt: now } });
    }
    return NextResponse.json({
      staffLinked: true,
      requests: rows.filter((a) => a.status === "SCHEDULED").map(toAppt),
      upcoming: rows.filter((a) => ["CONFIRMED", "RESCHEDULED"].includes(a.status)).map(toAppt),
      remindedMe,
    });
  }

  const auth = await opsAuth(req, schoolId, "GET", { perms: ["frontoffice.view"] });
  if (!auth.ok) return auth.res;

  /* ── live (wave 72): the front-desk real-time feed — today's active
     appointments with their staff link + response trail at a polling
     cadence. Also drives the T-2-min staff SMS reminders from any live
     desk session (reminderSentAt dedupes across pollers). */
  if (action === "live") {
    const now = new Date();
    const [rows, smsSetting, staff] = await Promise.all([
      db.appointment.findMany({
        where: { schoolId, scheduledAt: { gte: dayZero, lt: dayEnd }, status: { in: APPT_ACTIVE } },
        include: { staff: true },
        orderBy: { scheduledAt: "asc" },
      }),
      db.smsSetting.findUnique({ where: { schoolId } }),
      staffOptions(schoolId),
    ]);
    const horizon = new Date(now.getTime() + 2 * 60_000);
    const grace = new Date(now.getTime() - 60_000);
    const reminded: { id: string; staff: string; status: string }[] = [];
    for (const a of rows) {
      if (!a.staffId || a.reminderSentAt) continue;
      if (a.scheduledAt > horizon || a.scheduledAt < grace) continue;
      const st = a.staff!;
      let sms = "SKIPPED";
      if (smsSetting?.autoApptAlerts && st.smsNotify && st.phone) {
        const t = new Date(a.scheduledAt).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" });
        const msg = await sendSms({
          db, schoolId, recipientName: st.fullName, recipientPhone: st.phone,
          body: `MEETING IN 2 MINUTES: "${trunc(a.title, 40)}" with ${a.withName} at ${t}${a.location ? ` — ${a.location}` : ""}. The front desk will show them in.`,
          category: "FRONT_OFFICE", sentByName: "Front desk",
        });
        sms = msg.status;
      }
      reminded.push({ id: a.id, staff: st.fullName, status: sms });
      await db.appointment.update({ where: { id: a.id }, data: { reminderSentAt: now } });
    }
    return NextResponse.json({ now: now.toISOString(), appointments: rows.map(toAppt), staff, reminded });
  }

  /* ── board: the front desk at a glance ── */
  if (action === "board") {
    const [onSite, todayVisitors, todayAppts, openFollowUps, postalPending, upcomingEvents, nextAppt, staff] = await Promise.all([
      db.visitor.findMany({ where: { schoolId, status: "ON_SITE" }, orderBy: { timeIn: "asc" } }),
      db.visitor.count({ where: { schoolId, timeIn: { gte: dayZero, lt: dayEnd } } }),
      db.appointment.findMany({
        where: { schoolId, scheduledAt: { gte: dayZero, lt: dayEnd }, status: { in: APPT_ACTIVE } },
        include: { staff: true },
        orderBy: { scheduledAt: "asc" },
      }),
      db.phoneCall.count({ where: { schoolId, followUp: true, followUpDone: false } }),
      db.postalItem.count({ where: { schoolId, status: "PENDING" } }),
      db.schoolEvent.findMany({ where: { schoolId, startsAt: { gte: dayZero } }, orderBy: { startsAt: "asc" }, take: 6 }),
      db.appointment.findFirst({
        where: { schoolId, scheduledAt: { gte: new Date() }, status: { in: APPT_ACTIVE } },
        include: { staff: true },
        orderBy: { scheduledAt: "asc" },
      }),
      staffOptions(schoolId),
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
      staff,
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
      staff: await staffOptions(schoolId),
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
    const rows = await db.appointment.findMany({ where, include: { staff: true }, orderBy: { scheduledAt: scope === "past" ? "desc" : "asc" }, take: 200 });
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

  let body: any;
  try { body = await req.json(); } catch { return bad("Invalid JSON body"); }
  const action = optStr(body?.action);

  /* ── appointment-respond (wave 72): staff self-service. The linked staff
     member answers their own request from the Home card — ACCEPT, DECLINE or
     RESCHEDULE (a new date & time) — and the response trail + live board
     carry it to the front desk instantly. Being the booked staff member is
     the auth (no front-office permission needed — teachers respond too);
     the front desk may also record a verbal response. */
  if (action === "appointment-respond") {
    const user = await getUser(req);
    if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
    if (!canOperateSchool(user, schoolId)) return NextResponse.json({ error: "Forbidden" }, { status: 403 });
    const id = optStr(body?.appointmentId);
    const decision = String(body?.decision ?? "").toUpperCase();
    if (!id) return bad("appointmentId is required");
    if (!["ACCEPT", "DECLINE", "RESCHEDULE"].includes(decision)) return bad("decision must be ACCEPT, DECLINE or RESCHEDULE");
    const a = await db.appointment.findUnique({ where: { id }, include: { staff: true } });
    if (!a || a.schoolId !== schoolId) return bad("Appointment not found", 404);
    const isTarget = !!user.staffId && user.staffId === a.staffId;
    const isDesk = canAny(user.permissions, ["frontoffice.manage"]);
    if (!isTarget && !isDesk) return bad("Only the staff member being visited (or the front desk) can respond", 403);
    if (!APPT_ACTIVE.includes(a.status)) return bad(`This appointment is already ${a.status.toLowerCase()} — nothing to respond to`);

    const data: any = { respondedAt: new Date(), respondedByName: user.fullName ?? "Staff", respondedByUserId: user.id };
    if (optStr(body?.note)) data.responseNote = optStr(body?.note);

    if (decision === "ACCEPT") {
      data.status = "CONFIRMED";
    } else if (decision === "DECLINE") {
      data.status = "DECLINED";
    } else {
      const newAt = dateTime(body?.scheduledAt);
      if (!newAt) return bad("scheduledAt is required for a reschedule (ISO datetime)");
      // the odd-hours rule follows the visitor, not the desk: even a staff
      // member's own reschedule cannot put a teacher in front of a visitor
      // outside school hours (administration may pick any time)
      const odd = oddHoursError(a.staff, newAt);
      if (odd) return bad(odd);
      if (a.staff) {
        const clash = await teachingClash(db, a.staff, newAt);
        if (clash) return bad(`Class clash: ${clash.detail}`);
      }
      data.scheduledAt = newAt;
      data.rescheduledFrom = a.scheduledAt;
      data.status = "RESCHEDULED";
      data.reminderSentAt = null; // re-arm the T-2-min reminder at the new time
    }
    const row = await db.appointment.update({ where: { id }, data, include: { staff: true } });
    return NextResponse.json({ ok: true, appointment: toAppt(row) });
  }

  const auth = await opsAuth(req, schoolId, "POST", { perms: ["frontoffice.manage"] });
  if (!auth.ok) return auth.res;
  const actorName = auth.user.fullName ?? "Front desk";

  /* ── visitor-in: book a visitor into the Visitors Book ──
     wave 59: "to see who" is selectable from the staff register — the moment
     the booking completes, the linked staff member is alerted through every
     live channel: SMS (SmsSetting.autoVisitorAlerts + Staff.smsNotify gate),
     email (EmailSetting.enabled gate) and the in-app notification bell
     (derived from the ON_SITE visitor row, so it clears itself at check-out). */
  if (action === "visitor-in") {
    const name = optStr(body?.name);
    const purpose = optStr(body?.purpose);
    if (!name) return bad("Visitor name is required");
    if (!purpose) return bad("Purpose of visit is required");
    const category = enumVal(body?.category, VISITOR_CATEGORIES) ?? "OTHER";
    const personToSeeStaffId = optStr(body?.personToSeeStaffId);
    // resolve the staff member (school-scoped; silent fall-through to free text)
    const staff = personToSeeStaffId
      ? await db.staff.findFirst({ where: { id: personToSeeStaffId, schoolId, active: true } })
      : null;
    const passNo = await nextPassNo(schoolId);
    const row = await db.visitor.create({
      data: {
        schoolId, name, purpose, category,
        phone: optStr(body?.phone),
        organisation: optStr(body?.organisation),
        personToSee: staff?.fullName ?? optStr(body?.personToSee),
        ...(staff ? { personToSeeStaffId: staff.id } : {}),
        passNo, badgeIssued: body?.badgeIssued === false ? false : true,
        notes: optStr(body?.notes),
        recordedByName: actorName,
      },
    });

    /* ── instant arrival alerts to the selected staff member ── */
    const alert = { sms: "SKIPPED" as string, email: "SKIPPED" as string, inApp: false, staff: staff?.fullName ?? null };
    if (staff) {
      const catLabel = { PARENT: "a parent", VENDOR: "a vendor", GOVT: "a government official", ALUMNI: "an alumna/us", OTHER: "a visitor" }[category] ?? "a visitor";
      const when = new Date().toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" });
      const smsBody = `VISITOR ALERT: ${name}${optStr(body?.organisation) ? ` (${optStr(body?.organisation)})` : ""} — ${catLabel} — is at the front desk to see you. Purpose: ${purpose}. Pass ${passNo}. Booked ${when} by ${actorName}.`;

      // SMS — school master switch (autoVisitorAlerts) + the staff member's own
      // opt-in (smsNotify) + a phone on file. Outcome lands on the row either way.
      const [smsSetting, emailSetting] = await Promise.all([
        db.smsSetting.findUnique({ where: { schoolId } }),
        db.emailSetting.findUnique({ where: { schoolId } }),
      ]);
      if (smsSetting?.autoVisitorAlerts && staff.smsNotify && staff.phone) {
        const msg = await sendSms({
          db, schoolId, recipientName: staff.fullName, recipientPhone: staff.phone,
          body: smsBody, category: "FRONT_OFFICE", sentByName: actorName,
        });
        alert.sms = msg.status; // SENT | SIMULATED | FAILED
      }
      // email — only when the school has SMTP configured & enabled
      if (emailSetting?.enabled && staff.email) {
        const esc = (s: string) => s.replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[c]!);
        const msg = await sendEmail({
          db, schoolId, to: staff.email, toName: staff.fullName,
          subject: `Visitor waiting: ${name} (pass ${passNo})`,
          category: "FRONT_OFFICE", sentByName: actorName,
          html: `<div style="font-family:system-ui,Segoe UI,Arial,sans-serif;max-width:520px;margin:0 auto;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
  <div style="background:#0d9488;color:#fff;padding:14px 20px;font-size:15px;font-weight:700">Visitor arrival — front desk</div>
  <div style="padding:20px">
    <p style="margin:0 0 12px;font-size:15px"><b style="font-size:18px">${esc(name)}</b>${optStr(body?.organisation) ? ` · ${esc(optStr(body?.organisation)!)}` : ""} is at the front desk to see <b>you</b>.</p>
    <table style="width:100%;border-collapse:collapse;font-size:13px;color:#374151">
      <tr><td style="padding:6px 0;color:#6b7280;width:120px">Category</td><td style="padding:6px 0">${catLabel.replace(/^an? /, "").replace(/^./, (c) => c.toUpperCase())}</td></tr>
      <tr><td style="padding:6px 0;color:#6b7280">Purpose</td><td style="padding:6px 0">${esc(purpose)}</td></tr>
      <tr><td style="padding:6px 0;color:#6b7280">Pass number</td><td style="padding:6px 0;font-weight:700">${esc(passNo)}</td></tr>
      <tr><td style="padding:6px 0;color:#6b7280">Arrived</td><td style="padding:6px 0">${when}</td></tr>
      <tr><td style="padding:6px 0;color:#6b7280">Booked by</td><td style="padding:6px 0">${esc(actorName)}</td></tr>
      ${optStr(body?.phone) ? `<tr><td style="padding:6px 0;color:#6b7280">Visitor phone</td><td style="padding:6px 0">${esc(optStr(body?.phone)!)}</td></tr>` : ""}
    </table>
    <p style="margin:16px 0 0;font-size:12px;color:#6b7280">Please come to the front desk, or call the desk to ask the visitor to be seated.</p>
  </div>
</div>`,
        });
        alert.email = msg.status; // SENT | SIMULATED | FAILED
      }
      // in-app — the bell derives the "visitor waiting for you" item from this
      // ON_SITE row (see /api/notifications); the flag records that it fired
      alert.inApp = true;
      await db.visitor.update({
        where: { id: row.id },
        data: { alertedSms: alert.sms !== "FAILED" && alert.sms !== "SKIPPED", alertedEmail: alert.email !== "FAILED" && alert.email !== "SKIPPED", alertedInApp: true },
      });
    }
    return NextResponse.json({ ok: true, visitor: toVisitor(row), alert });
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

  /* ── appointment-create (wave 72 rework): the front desk books a visitor
     in to see a SELECTED staff member — the request alerts that staff
     member instantly through every live channel (SMS + email + in-app
     bell) with Accept / Decline / Reschedule actions on their Home.
     Odd-hours rule: teachers & non-teaching staff are bookable within
     school hours only; administration (head, bursar …) any hour. */
  if (action === "appointment-create") {
    const title = optStr(body?.title);
    const withName = optStr(body?.withName);
    const scheduledAt = dateTime(body?.scheduledAt);
    if (!title) return bad("Title is required");
    if (!withName) return bad("withName (who the meeting is with) is required");
    if (!scheduledAt) return bad("scheduledAt is required (ISO datetime)");
    const staffId = optStr(body?.staffId);
    const staff = staffId ? await db.staff.findFirst({ where: { id: staffId, schoolId, active: true } }) : null;
    if (staffId && !staff) return bad("Staff member not found for this school", 404);
    const odd = oddHoursError(staff, scheduledAt);
    if (odd) return bad(odd);
    // class-hours rule — the timetable is the truth for teachers: a visitor
    // cannot pull them out of a lesson even inside school hours
    if (staff) {
      const clash = await teachingClash(db, staff, scheduledAt);
      if (clash) return bad(`Class clash: ${clash.detail}`);
    }
    // staff-linked bookings get a reference code + the one-time decision link
    const code = staff ? await genApptCode(db, schoolId) : null;
    const token = staff ? newDecisionToken() : null;
    const row = await db.appointment.create({
      data: {
        schoolId, title, withName, scheduledAt,
        withRole: enumVal(body?.withRole, APPT_ROLES) ?? "PARENT",
        visitorPhone: optStr(body?.visitorPhone),
        visitorOrg: optStr(body?.visitorOrg),
        personToSee: staff?.fullName ?? optStr(body?.personToSee),
        ...(staff ? { staffId: staff.id, code, decisionToken: token } : {}),
        durationMin: typeof body?.durationMin === "number" && body.durationMin > 0 && body.durationMin <= 600 ? Math.round(body.durationMin) : null,
        location: optStr(body?.location),
        purpose: optStr(body?.purpose),
        notes: optStr(body?.notes),
        status: "SCHEDULED",
        createdByName: actorName,
        ...(auth.user.staffId ? { createdByStaffId: auth.user.staffId } : {}),
      },
      include: { staff: true },
    });

    /* ── instant request alerts to the selected staff member (same gating
       as the wave-59 visitor alerts: school master switch + the staff
       member's own opt-in + a channel on file) ── */
    const alert = { sms: "SKIPPED" as string, email: "SKIPPED" as string, inApp: false, staff: staff?.fullName ?? null, code: row.code ?? null, decisionUrl: apptDecisionLink(req.nextUrl.origin, row.code, row.decisionToken) };
    if (staff) {
      const whenLabel = `${new Date(scheduledAt).toLocaleDateString("en-GB", { weekday: "short", day: "numeric", month: "short" })} ${new Date(scheduledAt).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" })}${row.durationMin ? ` (${row.durationMin} min)` : ""}`;
      const linkLine = alert.decisionUrl ? ` Answer: ${alert.decisionUrl}` : " Open Nyansapo → Home to answer.";
      const smsBody = `${row.code ?? "APPOINTMENT"}: ${withName}${row.visitorOrg ? ` (${row.visitorOrg})` : ""} — "${trunc(title, 44)}" — ${whenLabel}${row.location ? `, ${row.location}` : ""}.${linkLine}`;
      const [smsSetting, emailSetting] = await Promise.all([
        db.smsSetting.findUnique({ where: { schoolId } }),
        db.emailSetting.findUnique({ where: { schoolId } }),
      ]);
      if (smsSetting?.autoApptAlerts && staff.smsNotify && staff.phone) {
        const msg = await sendSms({
          db, schoolId, recipientName: staff.fullName, recipientPhone: staff.phone,
          body: smsBody, category: "FRONT_OFFICE", sentByName: actorName,
        });
        alert.sms = msg.status;
      }
      if (emailSetting?.enabled && staff.email) {
        const esc = (s: string) => s.replace(/[&<>"/]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "/": "&#47;" }[c]!));
        const actionUrl = alert.decisionUrl ?? req.nextUrl.origin;
        const msg = await sendEmail({
          db, schoolId, to: staff.email, toName: staff.fullName,
          subject: `Appointment request: ${withName} — ${whenLabel}`,
          category: "FRONT_OFFICE", sentByName: actorName,
          html: `<div style="font-family:system-ui,Segoe UI,Arial,sans-serif;max-width:540px;margin:0 auto;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
  <div style="background:#0d9488;color:#fff;padding:14px 20px;font-size:15px;font-weight:700">Appointment request — front desk</div>
  <div style="padding:20px">
    <p style="margin:0 0 12px;font-size:15px"><b style="font-size:18px">${esc(withName)}</b>${row.visitorOrg ? ` · ${esc(row.visitorOrg)}` : ""} requests a meeting with <b>you</b>.</p>
    <table style="width:100%;border-collapse:collapse;font-size:13px;color:#374151">
      <tr><td style="padding:6px 0;color:#6b7280;width:120px">Subject</td><td style="padding:6px 0;font-weight:600">${esc(title)}</td></tr>
      <tr><td style="padding:6px 0;color:#6b7280">When</td><td style="padding:6px 0;font-weight:700">${esc(whenLabel)}</td></tr>
      ${row.location ? `<tr><td style="padding:6px 0;color:#6b7280">Where</td><td style="padding:6px 0">${esc(row.location)}</td></tr>` : ""}
      ${row.purpose ? `<tr><td style="padding:6px 0;color:#6b7280">Purpose</td><td style="padding:6px 0">${esc(row.purpose)}</td></tr>` : ""}
      ${row.visitorPhone ? `<tr><td style="padding:6px 0;color:#6b7280">Visitor phone</td><td style="padding:6px 0">${esc(row.visitorPhone)}</td></tr>` : ""}
      <tr><td style="padding:6px 0;color:#6b7280">Booked by</td><td style="padding:6px 0">${esc(actorName)} (front desk)</td></tr>
    </table>
    <p style="margin:16px 0 12px"><a href="${actionUrl}" style="display:inline-block;background:#0d9488;color:#fff;text-decoration:none;font-weight:700;font-size:14px;padding:10px 22px;border-radius:8px">Accept · Decline · Reschedule</a></p>
    <p style="margin:0;font-size:12px;color:#6b7280">The button opens your one-tap answer page (${esc(row.code ?? "")}) — accept the slot, decline with a reason, or propose a new date &amp; time. The front desk sees your answer immediately and relays it to the visitor. You can also answer from Nyansapo on your Home.</p>
  </div>
</div>`,
        });
        alert.email = msg.status;
      }
      // in-app — the bell derives "appointment requests awaiting your
      // response" from this SCHEDULED row (see /api/notifications)
      alert.inApp = true;
      await db.appointment.update({
        where: { id: row.id },
        data: { alertedSms: alert.sms !== "FAILED" && alert.sms !== "SKIPPED", alertedEmail: alert.email !== "FAILED" && alert.email !== "SKIPPED", alertedInApp: true },
      });
    }
    return NextResponse.json({ ok: true, appointment: toAppt(row), alert });
  }

  /* ── appointment-reschedule (wave 72): the front desk moves a meeting —
     the visitor relay runs through the desk; the trail keeps was → now. */
  if (action === "appointment-reschedule") {
    const id = optStr(body?.appointmentId);
    const newAt = dateTime(body?.scheduledAt);
    if (!id) return bad("appointmentId is required");
    if (!newAt) return bad("scheduledAt is required (ISO datetime)");
    const a = await db.appointment.findUnique({ where: { id }, include: { staff: true } });
    if (!a || a.schoolId !== schoolId) return bad("Appointment not found", 404);
    if (!APPT_ACTIVE.includes(a.status)) return bad("Only scheduled, confirmed or rescheduled appointments can be moved");
    const odd = oddHoursError(a.staff, newAt);
    if (odd) return bad(odd);
    if (a.staff) {
      const clash = await teachingClash(db, a.staff, newAt);
      if (clash) return bad(`Class clash: ${clash.detail}`);
    }
    const row = await db.appointment.update({
      where: { id },
      include: { staff: true },
      data: {
        scheduledAt: newAt,
        rescheduledFrom: a.scheduledAt,
        reminderSentAt: null, // re-arm the T-2-min reminder
        respondedAt: new Date(),
        respondedByName: actorName,
        respondedByUserId: auth.user.id,
        ...(optStr(body?.note) ? { responseNote: optStr(body?.note) } : {}),
      },
    });
    return NextResponse.json({ ok: true, appointment: toAppt(row) });
  }

  /* ── appointment-relay (wave 72): the desk has relayed the staff member's
     answer to the visitor — the acknowledgement that closes the loop (and
     confirms a rescheduled meeting: the visitor accepted the new time). */
  if (action === "appointment-relay") {
    const id = optStr(body?.appointmentId);
    if (!id) return bad("appointmentId is required");
    const a = await db.appointment.findUnique({ where: { id }, include: { staff: true } });
    if (!a || a.schoolId !== schoolId) return bad("Appointment not found", 404);
    if (!["CONFIRMED", "DECLINED", "RESCHEDULED"].includes(a.status)) return bad("Nothing to relay yet — the staff member has not answered");
    if (a.relayedAt) return bad("Already relayed to the visitor");
    const row = await db.appointment.update({
      where: { id },
      include: { staff: true },
      data: {
        relayedAt: new Date(),
        // a relayed reschedule means the visitor accepted the proposed time
        ...(a.status === "RESCHEDULED" ? { status: "CONFIRMED" } : {}),
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

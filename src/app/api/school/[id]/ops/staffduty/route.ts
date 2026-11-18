import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { attStats, leaveBalances, LEAVE_ENTITLEMENTS } from "@/lib/staff-duty-core";

// ── Staff Attendance & Duty (wave 28 — the staff movement book) ──
// The staff-side registers every Ghanaian school keeps beside the learner
// ones: the daily Staff Attendance Book (clock-in/out, GES 08:00
// punctuality rule), the Staff Movement Book (sign-out / sign-in with
// destination & purpose), the noticeboard duty roster (one holder per duty
// slot per weekday), the leave ledger (annual / sick / maternity /
// paternity / study / compassionate — Ghana Labour Act) and the
// substitution cover log for absent teachers.

const STATUSES = ["PRESENT", "LATE", "ABSENT", "LEAVE", "OFF"];
const LEAVE_TYPES = ["ANNUAL", "SICK", "MATERNITY", "PATERNITY", "STUDY", "COMPASSIONATE", "OTHER"];
const LEAVE_DECISIONS = ["APPROVED", "DECLINED"];
const WEEKDAYS = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
const DUTIES = ["ASSEMBLY", "BREAK", "CLOSING", "GATE", "LIBRARY", "EXAMS", "OTHER"];
// GES punctuality rule: staff should be at post by 08:00.
const LATE_AFTER_HOUR = 8;

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
/** Calendar days between two date-only days, inclusive. */
const daysBetween = (a: Date, b: Date) =>
  Math.floor((b.getTime() - a.getTime()) / 86_400_000) + 1;
const WEEKDAY_NAMES = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

const STAFF_SELECT = {
  id: true, fullName: true, role: true, title: true, category: true, position: true,
  staffCode: true, department: true, phone: true, email: true, active: true,
} as const;

/** Map an attendance row (+ staff include) to the wire register row. */
function toRow(r: any) {
  return {
    id: r.id,
    staffId: r.staffId,
    name: r.staff?.fullName ?? "",
    role: r.staff?.role ?? null,
    title: r.staff?.title ?? null,
    category: r.staff?.category ?? null,
    position: r.staff?.position ?? null,
    staffCode: r.staff?.staffCode ?? null,
    status: r.status,
    clockIn: iso(r.clockIn),
    clockOut: iso(r.clockOut),
    note: r.note ?? null,
    markedByName: r.markedByName ?? null,
    date: isoDay(r.date),
  };
}

/** Verify staff belongs to the school; returns the row or null. */
async function staffAtSchool(schoolId: string, staffId: string | null) {
  if (!staffId) return null;
  const staff = await db.staff.findUnique({ where: { id: staffId }, select: { ...STAFF_SELECT, schoolId: true } });
  return staff && staff.schoolId === schoolId ? staff : null;
}

// ── GET /api/school/[id]/ops/staffduty — board | register | leaves | duty | movements | analytics | profile ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["staffduty.view"] });
  if (!auth.ok) return auth.res;

  const sp = req.nextUrl.searchParams;
  const action = sp.get("action") ?? "board";

  /* ── board: today at a glance ── */
  if (action === "board") {
    const today = isoDay(new Date())!;
    const day = dayStart(today)!;
    const [staffList, rows, weekDuty, openMoves, recentSubs, pendingLeaves] = await Promise.all([
      db.staff.findMany({ where: { schoolId, active: true }, select: STAFF_SELECT, orderBy: { fullName: "asc" } }),
      db.staffAttendance.findMany({
        where: { schoolId, date: day },
        include: { staff: { select: STAFF_SELECT } },
      }),
      db.dutyAssignment.findMany({
        where: { schoolId },
        include: { staff: { select: STAFF_SELECT } },
        orderBy: [{ duty: "asc" }, { weekday: "asc" }],
      }),
      db.staffMovement.findMany({
        where: { schoolId, status: "OUT" },
        include: { staff: { select: STAFF_SELECT } },
        orderBy: { signedOutAt: "desc" },
      }),
      db.substitutionLog.findMany({
        where: { schoolId },
        include: {
          absentStaff: { select: { fullName: true } },
          substituteStaff: { select: { fullName: true } },
        },
        orderBy: { date: "desc" },
        take: 6,
      }),
      db.leaveRequest.findMany({
        where: { schoolId, status: "PENDING" },
        include: { staff: { select: { fullName: true } } },
        orderBy: { createdAt: "desc" },
      }),
    ]);
    const rowByStaff = new Map(rows.map((r) => [r.staffId, r]));
    const dutyToday = weekDuty.filter((d) => d.weekday === WEEKDAY_NAMES[day.getUTCDay()]);
    const register = staffList.map((s) => {
      const row = rowByStaff.get(s.id);
      return {
        staffId: s.id, name: s.fullName, role: s.role, title: s.title, category: s.category,
        position: s.position, staffCode: s.staffCode,
        status: row?.status ?? null,
        clockIn: iso(row?.clockIn), clockOut: iso(row?.clockOut),
        note: row?.note ?? null,
        duty: dutyToday.filter((d) => d.staffId === s.id).map((d) => d.duty),
      };
    });
    const stats = attStats(rows.map((r) => ({ status: r.status })));
    return NextResponse.json({
      date: today,
      weekday: WEEKDAY_NAMES[day.getUTCDay()],
      stats: { ...stats, headcount: staffList.length, unmarked: staffList.length - rows.length, outNow: openMoves.length, dutyFilled: dutyToday.length, pendingLeaves: pendingLeaves.length },
      register,
      openMovements: openMoves.map((m) => ({
        id: m.id, staffId: m.staffId, name: m.staff?.fullName ?? "", destination: m.destination,
        purpose: m.purpose, expectedReturn: iso(m.expectedReturn), signedOutAt: iso(m.signedOutAt),
      })),
      recentSubstitutions: recentSubs.map((s) => ({
        id: s.id, date: isoDay(s.date), absent: s.absentStaff?.fullName ?? "", substitute: s.substituteStaff?.fullName ?? "",
        classRef: s.classRef, periods: s.periods,
      })),
      pendingLeaveList: pendingLeaves.map((l) => ({
        id: l.id, name: l.staff?.fullName ?? "", type: l.type, startsAt: isoDay(l.startsAt), endsAt: isoDay(l.endsAt), days: l.days, reason: l.reason,
      })),
      dutyToday: dutyToday.map((d) => ({ id: d.id, duty: d.duty, staffId: d.staffId, name: d.staff?.fullName ?? "", note: d.note })),
    });
  }

  /* ── register: full register for one date ── */
  if (action === "register") {
    const date = isoDay(dayStart(sp.get("date") ?? "") ?? new Date())!;
    const day = dayStart(date)!;
    const [staffList, rows] = await Promise.all([
      db.staff.findMany({ where: { schoolId, active: true }, select: STAFF_SELECT, orderBy: { fullName: "asc" } }),
      db.staffAttendance.findMany({
        where: { schoolId, date: day },
        include: { staff: { select: STAFF_SELECT } },
      }),
    ]);
    const rowByStaff = new Map(rows.map((r) => [r.staffId, r]));
    const register = staffList.map((s) => {
      const row = rowByStaff.get(s.id);
      return {
        staffId: s.id, name: s.fullName, role: s.role, title: s.title, category: s.category,
        position: s.position, staffCode: s.staffCode,
        status: row?.status ?? null,
        clockIn: iso(row?.clockIn), clockOut: iso(row?.clockOut),
        note: row?.note ?? null, markedByName: row?.markedByName ?? null,
      };
    });
    const stats = attStats(rows.map((r) => ({ status: r.status })));
    return NextResponse.json({
      date, weekday: WEEKDAY_NAMES[day.getUTCDay()],
      stats: { ...stats, headcount: staffList.length, unmarked: staffList.length - rows.length },
      register,
    });
  }

  /* ── leaves: the leave ledger (+ per-staff balances) ── */
  if (action === "leaves") {
    const status = enumVal(sp.get("status"), ["PENDING", "APPROVED", "DECLINED", "CANCELLED"]);
    const staffId = optStr(sp.get("staffId"));
    const [rows, staffList] = await Promise.all([
      db.leaveRequest.findMany({
        where: { schoolId, ...(status ? { status } : {}), ...(staffId ? { staffId } : {}) },
        include: { staff: { select: STAFF_SELECT } },
        orderBy: [{ createdAt: "desc" }],
        take: 200,
      }),
      db.staff.findMany({ where: { schoolId, active: true }, select: STAFF_SELECT, orderBy: { fullName: "asc" } }),
    ]);
    const balancesFor = (sid: string) => leaveBalances(rows.filter((r) => r.staffId === sid).map((r) => ({ type: r.type, status: r.status, days: r.days })));
    return NextResponse.json({
      requests: rows.map((l) => ({
        id: l.id, staffId: l.staffId, name: l.staff?.fullName ?? "", role: l.staff?.role ?? null,
        type: l.type, startsAt: isoDay(l.startsAt), endsAt: isoDay(l.endsAt), days: l.days,
        status: l.status, reason: l.reason, requestedByName: l.requestedByName,
        decidedByName: l.decidedByName, decidedAt: iso(l.decidedAt), createdAt: iso(l.createdAt),
      })),
      staff: staffList.map((s) => ({ staffId: s.id, name: s.fullName, balances: balancesFor(s.id) })),
      entitlements: LEAVE_ENTITLEMENTS,
    });
  }

  /* ── duty: the noticeboard duty roster ── */
  if (action === "duty") {
    const [slots, staffList] = await Promise.all([
      db.dutyAssignment.findMany({
        where: { schoolId },
        include: { staff: { select: STAFF_SELECT } },
        orderBy: [{ duty: "asc" }, { weekday: "asc" }],
      }),
      db.staff.findMany({ where: { schoolId, active: true }, select: STAFF_SELECT, orderBy: { fullName: "asc" } }),
    ]);
    const grid = DUTIES.map((duty) => ({
      duty,
      label: duty.charAt(0) + duty.slice(1).toLowerCase().replace("_", " "),
      slots: WEEKDAYS.map((wd) => {
        const s = slots.find((x) => x.duty === duty && x.weekday === wd);
        return s ? { id: s.id, weekday: wd, staffId: s.staffId, name: s.staff?.fullName ?? "", note: s.note ?? null } : { id: null, weekday: wd, staffId: null, name: null, note: null };
      }),
    }));
    const load = staffList.map((s) => ({
      staffId: s.id, name: s.fullName,
      slots: slots.filter((x) => x.staffId === s.id).length,
      duties: slots.filter((x) => x.staffId === s.id).map((x) => `${x.weekday.slice(0, 3)} ${x.duty.toLowerCase()}`),
    })).sort((a, b) => b.slots - a.slots);
    return NextResponse.json({ weekdays: WEEKDAYS, grid, load, staff: staffList.map((s) => ({ staffId: s.id, name: s.fullName })) });
  }

  /* ── movements: the staff movement book ── */
  if (action === "movements") {
    const open = sp.get("open") === "1";
    const staffId = optStr(sp.get("staffId"));
    const rows = await db.staffMovement.findMany({
      where: { schoolId, ...(open ? { status: "OUT" } : {}), ...(staffId ? { staffId } : {}) },
      include: { staff: { select: STAFF_SELECT } },
      orderBy: { signedOutAt: "desc" },
      take: 200,
    });
    return NextResponse.json({
      movements: rows.map((m) => ({
        id: m.id, staffId: m.staffId, name: m.staff?.fullName ?? "", role: m.staff?.role ?? null,
        destination: m.destination, purpose: m.purpose,
        signedOutAt: iso(m.signedOutAt), signedInAt: iso(m.signedInAt),
        expectedReturn: iso(m.expectedReturn), status: m.status, recordedByName: m.recordedByName,
      })),
    });
  }

  /* ── analytics: 30-day attendance, punctuality, leave usage, cover ── */
  if (action === "analytics") {
    const since = new Date();
    since.setUTCDate(since.getUTCDate() - 30);
    const [rows, leaves, subs, staffList] = await Promise.all([
      db.staffAttendance.findMany({
        where: { schoolId, date: { gte: since } },
        select: { staffId: true, date: true, status: true, clockIn: true },
      }),
      db.leaveRequest.findMany({
        where: { schoolId, status: { in: ["APPROVED", "PENDING"] } },
        include: { staff: { select: { fullName: true } } },
        orderBy: { createdAt: "desc" },
        take: 100,
      }),
      db.substitutionLog.findMany({
        where: { schoolId, date: { gte: since } },
        include: { absentStaff: { select: { fullName: true } }, substituteStaff: { select: { fullName: true } } },
      }),
      db.staff.findMany({ where: { schoolId, active: true }, select: STAFF_SELECT, orderBy: { fullName: "asc" } }),
    ]);
    const perStaff = staffList.map((s) => {
      const mine = rows.filter((r) => r.staffId === s.id);
      const stats = attStats(mine);
      const lateClockIns = mine.filter((r) => r.clockIn && r.status === "LATE").length;
      return {
        staffId: s.id, name: s.fullName, role: s.role,
        marked: stats.marked, present: stats.present, late: stats.late, absent: stats.absent, leave: stats.leave,
        rate: stats.rate, lateClockIns,
        coversMade: subs.filter((x) => x.substituteStaffId === s.id).length,
      };
    }).sort((a, b) => (b.rate ?? 0) - (a.rate ?? 0));
    const weekdayHeat = WEEKDAYS.map((wd) => {
      const dayRows = rows.filter((r) => WEEKDAY_NAMES[new Date(r.date).getUTCDay()] === wd);
      return { weekday: wd, ...attStats(dayRows) };
    });
    const leaveByType = LEAVE_TYPES.map((t) => ({
      type: t,
      approvedDays: leaves.filter((l) => l.type === t && l.status === "APPROVED").reduce((s, l) => s + l.days, 0),
      pending: leaves.filter((l) => l.type === t && l.status === "PENDING").length,
    }));
    return NextResponse.json({
      windowDays: 30,
      overall: attStats(rows),
      perStaff,
      weekdayHeat,
      leaveByType,
      pendingLeaves: leaves.filter((l) => l.status === "PENDING").length,
      substitutions: subs.map((x) => ({
        date: isoDay(x.date), absent: x.absentStaff?.fullName ?? "", substitute: x.substituteStaff?.fullName ?? "",
        classRef: x.classRef, periods: x.periods,
      })),
    });
  }

  /* ── profile: one staff member's full duty record ── */
  if (action === "profile") {
    const staff = await staffAtSchool(schoolId, optStr(sp.get("staffId")));
    if (!staff) return bad("Staff not found", 404);
    const since = new Date();
    since.setUTCDate(since.getUTCDate() - 30);
    const [rows, leaves, duty, movements, subsMade, subsFor] = await Promise.all([
      db.staffAttendance.findMany({ where: { staffId: staff.id, date: { gte: since } }, orderBy: { date: "desc" } }),
      db.leaveRequest.findMany({ where: { staffId: staff.id }, orderBy: { createdAt: "desc" } }),
      db.dutyAssignment.findMany({ where: { staffId: staff.id }, orderBy: { weekday: "asc" } }),
      db.staffMovement.findMany({ where: { staffId: staff.id }, orderBy: { signedOutAt: "desc" }, take: 30 }),
      db.substitutionLog.findMany({ where: { substituteStaffId: staff.id }, include: { absentStaff: { select: { fullName: true } } }, orderBy: { date: "desc" }, take: 20 }),
      db.substitutionLog.findMany({ where: { absentStaffId: staff.id }, include: { substituteStaff: { select: { fullName: true } } }, orderBy: { date: "desc" }, take: 20 }),
    ]);
    return NextResponse.json({
      staff: { staffId: staff.id, name: staff.fullName, role: staff.role, title: staff.title, category: staff.category, position: staff.position, staffCode: staff.staffCode },
      attendance: rows.map(toRow),
      stats: attStats(rows),
      balances: leaveBalances(leaves.map((l) => ({ type: l.type, status: l.status, days: l.days }))),
      leaves: leaves.map((l) => ({ id: l.id, type: l.type, startsAt: isoDay(l.startsAt), endsAt: isoDay(l.endsAt), days: l.days, status: l.status, reason: l.reason })),
      duty: duty.map((d) => ({ id: d.id, weekday: d.weekday, duty: d.duty, note: d.note })),
      movements: movements.map((m) => ({ id: m.id, destination: m.destination, purpose: m.purpose, signedOutAt: iso(m.signedOutAt), signedInAt: iso(m.signedInAt), status: m.status })),
      coversMade: subsMade.map((x) => ({ date: isoDay(x.date), absent: x.absentStaff?.fullName ?? "", classRef: x.classRef, periods: x.periods })),
      coveredFor: subsFor.map((x) => ({ date: isoDay(x.date), substitute: x.substituteStaff?.fullName ?? "", classRef: x.classRef, periods: x.periods })),
    });
  }

  return bad("Unknown action", 400);
}

// ── POST /api/school/[id]/ops/staffduty ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["staffduty.manage"] });
  if (!auth.ok) return auth.res;
  const actorName = auth.user.fullName ?? "Staff";

  let body: any;
  try { body = await req.json(); } catch { return bad("Invalid JSON body"); }
  const action = optStr(body?.action);

  /* ── register-save: bulk mark the daily register ── */
  if (action === "register-save") {
    const day = dayStart(body?.date) ?? dayStart(isoDay(new Date()));
    if (!day) return bad("Invalid date");
    const list = Array.isArray(body?.rows) ? body.rows : null;
    if (!list?.length) return bad("No rows to save");
    const valid: any[] = [];
    for (const r of list) {
      const status = enumVal(r?.status, STATUSES);
      const staff = status ? await staffAtSchool(schoolId, optStr(r?.staffId)) : null;
      if (status && staff) valid.push({ staffId: staff.id, status, note: optStr(r?.note) });
    }
    if (!valid.length) return bad("No valid rows — check staffIds & statuses");
    const saved = await db.$transaction(
      valid.map((r) =>
        db.staffAttendance.upsert({
          where: { staffId_date: { staffId: r.staffId, date: day } },
          create: { schoolId, staffId: r.staffId, date: day, status: r.status, note: r.note, markedByName: actorName },
          update: { status: r.status, note: r.note, markedByName: actorName },
        }),
      ),
    );
    return NextResponse.json({ ok: true, saved: saved.length, skipped: list.length - valid.length, date: isoDay(day) });
  }

  /* ── clock: clock-in / clock-out (GES 08:00 punctuality rule) ── */
  if (action === "clock") {
    const staff = await staffAtSchool(schoolId, optStr(body?.staffId));
    if (!staff) return bad("Staff not found", 404);
    const kind = enumVal(body?.kind, ["IN", "OUT"]);
    if (!kind) return bad("kind must be IN or OUT");
    const day = dayStart(body?.date) ?? dayStart(isoDay(new Date()));
    const now = new Date();
    if (kind === "IN") {
      const late = now.getUTCHours() >= LATE_AFTER_HOUR;
      const row = await db.staffAttendance.upsert({
        where: { staffId_date: { staffId: staff.id, date: day! } },
        create: { schoolId, staffId: staff.id, date: day!, status: late ? "LATE" : "PRESENT", clockIn: now, markedByName: actorName },
        update: { clockIn: now, status: late ? "LATE" : "PRESENT", markedByName: actorName },
      });
      return NextResponse.json({ ok: true, kind, late, row: toRow(row) });
    }
    const existing = await db.staffAttendance.findUnique({ where: { staffId_date: { staffId: staff.id, date: day! } } });
    if (!existing) return bad("No register row for today — clock in first");
    const row = await db.staffAttendance.update({ where: { id: existing.id }, data: { clockOut: now }, include: { staff: { select: STAFF_SELECT } } });
    return NextResponse.json({ ok: true, kind, row: toRow(row) });
  }

  /* ── leave-create: file a leave request ── */
  if (action === "leave-create") {
    const staff = await staffAtSchool(schoolId, optStr(body?.staffId));
    if (!staff) return bad("Staff not found", 404);
    const type = enumVal(body?.type, LEAVE_TYPES);
    if (!type) return bad("Invalid leave type");
    const start = dayStart(body?.startsAt);
    const end = dayStart(body?.endsAt);
    if (!start || !end) return bad("startsAt and endsAt are required (YYYY-MM-DD)");
    if (end < start) return bad("endsAt is before startsAt");
    const span = daysBetween(start, end);
    if (span > 365) return bad("Leave span exceeds one year");
    const row = await db.leaveRequest.create({
      data: {
        schoolId, staffId: staff.id, type, startsAt: start, endsAt: end, days: span,
        status: "PENDING", reason: optStr(body?.reason), requestedByName: actorName,
      },
    });
    return NextResponse.json({ ok: true, request: { id: row.id, days: row.days, status: row.status } });
  }

  /* ── leave-decide: approve / decline — approval stamps the register ── */
  if (action === "leave-decide") {
    const id = optStr(body?.requestId);
    const decision = enumVal(body?.decision, LEAVE_DECISIONS);
    if (!id || !decision) return bad("requestId and decision (APPROVED|DECLINED) are required");
    const request = await db.leaveRequest.findUnique({ where: { id } });
    if (!request || request.schoolId !== schoolId) return bad("Leave request not found", 404);
    if (request.status !== "PENDING") return bad(`Request already ${request.status.toLowerCase()}`);
    const updated = await db.leaveRequest.update({
      where: { id },
      data: { status: decision, decidedByName: actorName, decidedAt: new Date() },
    });
    let stamped = 0;
    if (decision === "APPROVED") {
      const span = Math.min(daysBetween(request.startsAt, request.endsAt), 90);
      const days: Date[] = [];
      for (let i = 0; i < span; i++) {
        const d = new Date(request.startsAt);
        d.setUTCDate(d.getUTCDate() + i);
        const wd = d.getUTCDay();
        if (wd !== 0 && wd !== 6) days.push(d); // working days only
      }
      if (days.length) {
        const res = await db.$transaction(
          days.map((d) =>
            db.staffAttendance.upsert({
              where: { staffId_date: { staffId: request.staffId, date: d } },
              create: { schoolId, staffId: request.staffId, date: d, status: "LEAVE", note: `Approved ${request.type.toLowerCase()} leave`, markedByName: actorName },
              update: { status: "LEAVE", note: `Approved ${request.type.toLowerCase()} leave`, markedByName: actorName },
            }),
          ),
        );
        stamped = res.length;
      }
    }
    return NextResponse.json({ ok: true, status: updated.status, stamped });
  }

  /* ── duty-set / duty-clear: the noticeboard roster ── */
  if (action === "duty-set") {
    const weekday = enumVal(body?.weekday, WEEKDAYS);
    const duty = enumVal(body?.duty, DUTIES);
    if (!weekday || !duty) return bad("weekday (Monday..Friday) and duty are required");
    const staff = await staffAtSchool(schoolId, optStr(body?.staffId));
    if (!staff) return bad("Staff not found", 404);
    const row = await db.dutyAssignment.upsert({
      where: { schoolId_weekday_duty: { schoolId, weekday, duty } },
      create: { schoolId, staffId: staff.id, weekday, duty, note: optStr(body?.note) },
      update: { staffId: staff.id, note: optStr(body?.note) },
      include: { staff: { select: { fullName: true } } },
    });
    return NextResponse.json({ ok: true, slot: { duty, weekday, staffId: row.staffId, name: row.staff?.fullName ?? "" } });
  }
  if (action === "duty-clear") {
    const weekday = enumVal(body?.weekday, WEEKDAYS);
    const duty = enumVal(body?.duty, DUTIES);
    if (!weekday || !duty) return bad("weekday and duty are required");
    await db.dutyAssignment.deleteMany({ where: { schoolId, weekday, duty } });
    return NextResponse.json({ ok: true });
  }

  /* ── movement-out / movement-in: the staff movement book ── */
  if (action === "movement-out") {
    const staff = await staffAtSchool(schoolId, optStr(body?.staffId));
    if (!staff) return bad("Staff not found", 404);
    const destination = optStr(body?.destination);
    if (!destination) return bad("destination is required");
    const open = await db.staffMovement.findFirst({ where: { staffId: staff.id, status: "OUT" } });
    if (open) return bad(`${staff.fullName} is already signed out`, 409);
    const row = await db.staffMovement.create({
      data: {
        schoolId, staffId: staff.id, destination, purpose: optStr(body?.purpose),
        recordedByName: actorName,
      },
    });
    return NextResponse.json({ ok: true, movement: { id: row.id, status: row.status, signedOutAt: iso(row.signedOutAt) } });
  }
  if (action === "movement-in") {
    const staffId = optStr(body?.staffId);
    const movementId = optStr(body?.movementId);
    const row = movementId
      ? await db.staffMovement.findUnique({ where: { id: movementId } })
      : await db.staffMovement.findFirst({ where: { schoolId, staffId: staffId ?? "", status: "OUT" }, orderBy: { signedOutAt: "desc" } });
    if (!row || row.schoolId !== schoolId) return bad("Movement entry not found", 404);
    if (row.status === "BACK") return bad("Already signed back in");
    const updated = await db.staffMovement.update({ where: { id: row.id }, data: { signedInAt: new Date(), status: "BACK" } });
    return NextResponse.json({ ok: true, movement: { id: updated.id, status: updated.status, signedInAt: iso(updated.signedInAt) } });
  }

  /* ── substitute-create: record cover for an absent teacher ── */
  if (action === "substitute-create") {
    const absent = await staffAtSchool(schoolId, optStr(body?.absentStaffId));
    const substitute = await staffAtSchool(schoolId, optStr(body?.substituteStaffId));
    if (!absent || !substitute) return bad("Absent & substitute staff are required");
    if (absent.id === substitute.id) return bad("Substitute must differ from the absent staff");
    const day = dayStart(body?.date) ?? dayStart(isoDay(new Date()));
    if (!day) return bad("Invalid date");
    const row = await db.substitutionLog.create({
      data: {
        schoolId, absentStaffId: absent.id, substituteStaffId: substitute.id, date: day,
        classRef: optStr(body?.classRef), periods: optStr(body?.periods), note: optStr(body?.note),
        recordedByName: actorName,
      },
    });
    return NextResponse.json({ ok: true, substitution: { id: row.id, date: isoDay(row.date) } });
  }

  return bad("Unknown action", 400);
}

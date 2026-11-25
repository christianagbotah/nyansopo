import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendSms } from "@/lib/sms";
import { sendEmail } from "@/lib/email";

// ── Timetable Intelligence (wave 65) ──────────────────────────
// The planning brain that sits on top of the weekly routine:
//   GET  ?date=YYYY-MM-DD        — everything the intelligence needs in one
//     payload: all slots (class + teacher), active staff, teacher clashes
//     (double-booked periods across classes), today's absentees from the
//     staff attendance book, the cover log for the date and — per absent
//     teacher — the affected lessons with RANKED cover suggestions
//     (subject match → free at that period → lightest load that day).
//   POST action=assign-cover     — lock a substitution onto the exact slot:
//     SubstitutionLog row (slot-accurate) + instant SMS/email to the covering
//     teacher; the in-app bell derives the "you are covering…" item live.
//   POST action=cancel-cover     — remove a planner-created cover row.

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });
const dayStart = (v: unknown): Date | null => {
  if (typeof v !== "string" || !/^\d{4}-\d{2}-\d{2}/.test(String(v).trim())) return null;
  const d = new Date(`${String(v).trim().slice(0, 10)}T00:00:00.000Z`);
  return isNaN(d.getTime()) ? null : d;
};
const dayStartOrToday = (v: unknown): Date => dayStart(v) ?? new Date(`${new Date().toISOString().slice(0, 10)}T00:00:00.000Z`);
const isoDay = (d: Date | string | null | undefined): string | null => {
  if (!d) return null;
  const dt = typeof d === "string" ? new Date(d) : d;
  return isNaN(dt.getTime()) ? null : dt.toISOString().slice(0, 10);
};
const hhmm = (m: number) => `${String(Math.floor(m / 60)).padStart(2, "0")}:${String(m % 60).padStart(2, "0")}`;
/** TimetableSlot.dayOfWeek (1=Mon…5=Fri) from a UTC-midnight date. */
const slotDayOf = (d: Date) => ((d.getUTCDay() + 6) % 7) + 1;
/** Slot-shaped overlap (dayOfWeek/startMinute/endMinute — SlotRow or busy marker). */
type SlotWindow = { dayOfWeek: number; startMinute: number; endMinute: number };
const overlaps = (a: SlotWindow, b: SlotWindow) =>
  a.dayOfWeek === b.dayOfWeek && a.startMinute < b.endMinute && b.startMinute < a.endMinute;

type SlotRow = {
  id: string; classroomId: string; classroomName: string; subjectId: string | null;
  subjectName: string; staffId: string | null; staffName: string | null;
  dayOfWeek: number; startMinute: number; endMinute: number; notes: string | null;
};

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["classes.view"] });
  if (!auth.ok) return auth.res;

  const url = new URL(req.url);
  const date = dayStartOrToday(url.searchParams.get("date"));

  const [slotRows, staffRows, attendance, coversRaw] = await Promise.all([
    db.timetableSlot.findMany({
      where: { schoolId },
      include: { classroom: { select: { name: true } }, staff: { select: { fullName: true } } },
      orderBy: [{ dayOfWeek: "asc" }, { startMinute: "asc" }],
    }),
    db.staff.findMany({
      where: { schoolId, active: true },
      select: { id: true, fullName: true, role: true, category: true, position: true, subjects: true, phone: true, email: true, smsNotify: true, staffCode: true },
      orderBy: { fullName: "asc" },
    }),
    db.staffAttendance.findMany({ where: { schoolId, date }, include: { staff: { select: { fullName: true } } } }),
    db.substitutionLog.findMany({
      where: { schoolId, date },
      include: {
        absentStaff: { select: { fullName: true } },
        substituteStaff: { select: { fullName: true } },
        slot: { select: { id: true, subjectName: true, startMinute: true, endMinute: true, dayOfWeek: true } },
      },
      orderBy: { createdAt: "asc" },
    }),
  ]);

  const slots: SlotRow[] = slotRows.map((s) => ({
    id: s.id,
    classroomId: s.classroomId,
    classroomName: s.classroom.name,
    subjectId: s.subjectId,
    subjectName: s.subjectName,
    staffId: s.staffId,
    staffName: s.staff?.fullName ?? null,
    dayOfWeek: s.dayOfWeek,
    startMinute: s.startMinute,
    endMinute: s.endMinute,
    notes: s.notes,
  }));

  const staff = staffRows.map((s) => ({
    id: s.id,
    name: s.fullName,
    role: s.role,
    category: s.category,
    position: s.position,
    staffCode: s.staffCode,
    subjects: s.subjects ? s.subjects.split(",").map((x) => x.trim()).filter(Boolean) : [],
    hasPhone: !!s.phone,
    hasEmail: !!s.email,
    smsNotify: s.smsNotify,
  }));
  const staffById = new Map(staffRows.map((s) => [s.id, s]));

  // ── clash detection: one teacher, same time, two lessons ──
  const booked = slots.filter((s) => s.staffId);
  const byStaff = new Map<string, SlotRow[]>();
  booked.forEach((s) => {
    const arr = byStaff.get(s.staffId!) ?? [];
    arr.push(s);
    byStaff.set(s.staffId!, arr);
  });
  const clashes: { staffId: string; staffName: string; day: number; a: SlotRow; b: SlotRow }[] = [];
  byStaff.forEach((rows, staffId) => {
    for (let i = 0; i < rows.length; i++) {
      for (let j = i + 1; j < rows.length; j++) {
        const a = rows[i], b = rows[j];
        if (overlaps(a, b) && a.classroomId !== b.classroomId) {
          clashes.push({ staffId, staffName: a.staffName ?? "", day: a.dayOfWeek, a, b });
        }
      }
    }
  });
  clashes.sort((x, y) => x.day - y.day || x.a.startMinute - y.a.startMinute);

  // ── absentees from the staff attendance book ──
  const absentIds = new Set(
    attendance.filter((r) => ["ABSENT", "LEAVE"].includes(r.status)).map((r) => r.staffId),
  );
  const absentees = attendance
    .filter((r) => ["ABSENT", "LEAVE"].includes(r.status))
    .map((r) => {
      const st = staffById.get(r.staffId);
      return {
        staffId: r.staffId,
        name: st?.fullName ?? r.staff?.fullName ?? "",
        status: r.status,
        note: r.note,
        affected: slots
          .filter((s) => s.staffId === r.staffId && s.dayOfWeek === slotDayOf(date))
          .sort((a, b) => a.startMinute - b.startMinute),
      };
    });

  // ── cover suggestions per affected lesson ──
  // busy maps: timetable bookings (all staff) + slot-accurate covers already
  // logged for the date (the substitute is busy for the covered period).
  const covers = coversRaw.map((c) => ({
    id: c.id,
    slotId: c.slotId,
    absent: c.absentStaff.fullName,
    substitute: c.substituteStaff.fullName,
    substituteStaffId: c.substituteStaffId,
    classRef: c.classRef,
    periods: c.periods,
    note: c.note,
    slot: c.slot ? { id: c.slot.id, subjectName: c.slot.subjectName, startMinute: c.slot.startMinute, endMinute: c.slot.endMinute, dayOfWeek: c.slot.dayOfWeek } : null,
  }));
  const busyExtra = new Map<string, SlotWindow[]>();
  covers.forEach((c) => {
    if (!c.slot) return;
    const arr = busyExtra.get(c.substituteStaffId) ?? [];
    arr.push({ dayOfWeek: c.slot.dayOfWeek, startMinute: c.slot.startMinute, endMinute: c.slot.endMinute });
    busyExtra.set(c.substituteStaffId, arr);
  });

  const weekday = slotDayOf(date);
  const teaching = staff.filter((s) => s.category === "TEACHING");
  const lightest = teaching.length
    ? Math.min(...teaching.map((t) => (byStaff.get(t.id) ?? []).length || 0))
    : 0;
  const suggestions = new Map<string, { id: string; name: string; subjectMatch: boolean; loadThatDay: number; weeklyLoad: number; reasons: string[] }[]>();

  for (const ab of absentees) {
    for (const lesson of ab.affected) {
      const window = { dayOfWeek: lesson.dayOfWeek, startMinute: lesson.startMinute, endMinute: lesson.endMinute };
      const subject = lesson.subjectName.toLowerCase();
      const candidates = teaching
        .filter((s) => s.id !== ab.staffId && !absentIds.has(s.id))
        .filter((s) => {
          const own = (byStaff.get(s.id) ?? []).filter((x) => x.id !== lesson.id);
          if (own.some((x) => overlaps(x, window))) return false;
          if ((busyExtra.get(s.id) ?? []).some((x) => overlaps(x, window))) return false;
          return true;
        })
        .map((s) => {
          const own = byStaff.get(s.id) ?? [];
          const subjectMatch = s.subjects.some((x) => subject.includes(x.toLowerCase()) || x.toLowerCase().includes(subject));
          const loadThatDay = own.filter((x) => x.dayOfWeek === weekday).length + (busyExtra.get(s.id) ?? []).filter((x) => x.dayOfWeek === weekday).length;
          const weeklyLoad = own.length;
          const reasons: string[] = [];
          if (subjectMatch) reasons.push("teaches this subject");
          if (loadThatDay === 0) reasons.push("free all day");
          else if (loadThatDay <= 1) reasons.push("light day");
          if (weeklyLoad === lightest) reasons.push("lightest timetable");
          return { id: s.id, name: s.name, subjectMatch, loadThatDay, weeklyLoad, reasons };
        });
      candidates.sort((a, b) =>
        (b.subjectMatch ? 1 : 0) - (a.subjectMatch ? 1 : 0) || a.loadThatDay - b.loadThatDay || a.weeklyLoad - b.weeklyLoad || a.name.localeCompare(b.name),
      );
      suggestions.set(lesson.id, candidates.slice(0, 4));
    }
  }

  return NextResponse.json({
    date: isoDay(date),
    weekday,
    slots,
    staff,
    clashes,
    absentees,
    covers,
    suggestions: Object.fromEntries(suggestions),
    attendanceMarked: attendance.length > 0,
  });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["classes.manage"] });
  if (!auth.ok) return auth.res;
  const user = auth.user;
  const body = (await req.json().catch(() => ({}))) as Record<string, unknown>;
  const action = String(body.action ?? "");
  const optStr = (v: unknown): string | null => {
    if (typeof v !== "string") return null;
    const t = v.trim();
    return t.length ? t : null;
  };

  /* ── assign-cover: lock a substitution onto the exact slot ── */
  if (action === "assign-cover") {
    const slotId = optStr(body.slotId);
    const substituteStaffId = optStr(body.substituteStaffId);
    const absentStaffId = optStr(body.absentStaffId);
    const date = dayStartOrToday(body.date);
    if (!slotId || !substituteStaffId || !absentStaffId || !date) return bad("slotId, date, absentStaffId and substituteStaffId are required");

    const slot = await db.timetableSlot.findFirst({
      where: { id: slotId, schoolId },
      include: { classroom: { select: { name: true } } },
    });
    if (!slot) return bad("Timetable slot not found", 404);
    const [absent, sub] = await Promise.all([
      db.staff.findFirst({ where: { id: absentStaffId, schoolId } }),
      db.staff.findFirst({ where: { id: substituteStaffId, schoolId, active: true } }),
    ]);
    if (!absent || !sub) return bad("Staff record not found", 404);
    if (sub.id === absent.id) return bad("The covering teacher must differ from the absent teacher");

    // guard: the substitute must genuinely be free at this period
    const clashSlots = await db.timetableSlot.findMany({
      where: {
        schoolId,
        staffId: sub.id,
        dayOfWeek: slot.dayOfWeek,
        startMinute: { lt: slot.endMinute },
        endMinute: { gt: slot.startMinute },
      },
    });
    const clashCovers = await db.substitutionLog.findMany({
      where: { schoolId, date, substituteStaffId: sub.id, slotId: { not: null } },
      include: { slot: { select: { dayOfWeek: true, startMinute: true, endMinute: true } } },
    });
    const busyCover = clashCovers.some((c) => c.slot && c.slot.dayOfWeek === slot.dayOfWeek && c.slot.startMinute < slot.endMinute && slot.startMinute < c.slot.endMinute);
    if (clashSlots.length || busyCover) return bad(`${sub.fullName} is already booked in that period — pick another suggestion`);

    const already = await db.substitutionLog.findFirst({ where: { schoolId, date, slotId: slot.id } });
    if (already) return bad("This lesson is already covered for that date — cancel the existing cover first");

    const when = `${hhmm(slot.startMinute)}–${hhmm(slot.endMinute)}`;
    const classRef = `${slot.classroom.name} · ${slot.subjectName}`;
    const cover = await db.substitutionLog.create({
      data: {
        schoolId,
        absentStaffId: absent.id,
        substituteStaffId: sub.id,
        date,
        slotId: slot.id,
        classRef,
        periods: when,
        note: optStr(body.note),
        recordedByName: user.fullName,
      },
    });

    // instant alerts to the covering teacher (SMS + email; the in-app bell
    // derives the "you are covering" item from this row at request time)
    const alert = { sms: "SKIPPED", email: "SKIPPED", inApp: true, staff: sub.fullName };
    if (sub.smsNotify && sub.phone) {
      const msg = await sendSms({
        db, schoolId, studentId: null,
        recipientName: sub.fullName, recipientPhone: sub.phone,
        body: `COVER DUTY: ${sub.fullName}, you are covering ${slot.subjectName} for ${absent.fullName} in ${slot.classroom.name} on ${isoDay(date)} at ${when}. Logged by ${user.fullName}.`,
        category: "STAFF", sentByName: user.fullName,
      });
      alert.sms = msg.status;
    }
    if (sub.email) {
      const msg = await sendEmail({
        db, schoolId, to: sub.email, toName: sub.fullName,
        subject: `Cover duty: ${slot.subjectName} — ${slot.classroom.name} (${isoDay(date)} ${when})`,
        category: "STAFF", sentByName: user.fullName,
        html: `<div style="font-family:system-ui,Segoe UI,Arial,sans-serif;max-width:520px;margin:0 auto;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
  <div style="background:#4f46e5;color:#fff;padding:14px 20px;font-size:15px;font-weight:700">Cover duty assigned — timetable</div>
  <div style="padding:20px">
    <p style="margin:0 0 12px;font-size:15px"><b>${sub.fullName}</b>, you are covering <b>${slot.subjectName}</b> for <b>${absent.fullName}</b>.</p>
    <table style="width:100%;border-collapse:collapse;font-size:13px;color:#374151">
      <tr><td style="padding:6px 0;color:#6b7280;width:120px">Class</td><td style="padding:6px 0;font-weight:700">${slot.classroom.name}</td></tr>
      <tr><td style="padding:6px 0;color:#6b7280">Date</td><td style="padding:6px 0">${isoDay(date)}</td></tr>
      <tr><td style="padding:6px 0;color:#6b7280">Time</td><td style="padding:6px 0">${when}</td></tr>
      <tr><td style="padding:6px 0;color:#6b7280">Absent teacher</td><td style="padding:6px 0">${absent.fullName}</td></tr>
      <tr><td style="padding:6px 0;color:#6b7280">Logged by</td><td style="padding:6px 0">${user.fullName}</td></tr>
    </table>
    <p style="margin:16px 0 0;font-size:12px;color:#6b7280">The lesson is on your timetable for that day — see Timetable Intelligence.</p>
  </div>
</div>`,
      });
      alert.email = msg.status;
    }

    return NextResponse.json({ ok: true, cover: { ...cover, date: isoDay(cover.date) }, alert });
  }

  /* ── cancel-cover: remove a planner-created cover row ── */
  if (action === "cancel-cover") {
    const id = optStr(body.id);
    if (!id) return bad("id is required");
    const row = await db.substitutionLog.findFirst({ where: { id, schoolId } });
    if (!row) return bad("Cover entry not found", 404);
    if (!row.slotId) return bad("This entry was logged manually in Staff Attendance & Duty — manage it there");
    await db.substitutionLog.delete({ where: { id } });
    return NextResponse.json({ ok: true });
  }

  return bad("Unknown action");
}

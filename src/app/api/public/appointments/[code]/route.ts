import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { oddHoursGuard, APPT_ACTIVE } from "@/lib/appointments";
import { sendSms } from "@/lib/sms";

/* ── Public appointment decision endpoint (/appoint/{code}) ─────
   Unauthenticated by design — the staff member opens the one-tap answer
   link from the request SMS/email on their phone. The 128-bit token in
   the link IS the authorization: it is unguessable, scoped to a single
   appointment and never expires (the page simply refuses actions once
   the request is answered/cancelled). Only minimal, safe fields are
   exposed — never other visitors' data, never staff personal data
   beyond name/title/role. */

export async function GET(req: NextRequest, { params }: { params: Promise<{ code: string }> }) {
  const { code } = await params;
  const token = req.nextUrl.searchParams.get("t") ?? "";
  const row = await db.appointment.findUnique({
    where: { code: code.toUpperCase() },
    include: { school: { select: { name: true, logoColor: true } }, staff: { select: { fullName: true, title: true, role: true, category: true } } },
  });
  if (!row || !row.decisionToken || row.decisionToken !== token) {
    return NextResponse.json({ error: "This appointment link is not valid" }, { status: 404 });
  }
  const canRespond = row.staffId != null && APPT_ACTIVE.includes(row.status);
  return NextResponse.json({
    code: row.code,
    status: row.status,
    canRespond,
    title: row.title,
    visitorName: row.withName,
    visitorOrg: row.visitorOrg ?? null,
    scheduledAt: row.scheduledAt,
    durationMin: row.durationMin ?? null,
    location: row.location ?? null,
    purpose: row.purpose ?? null,
    host: row.staff ? { name: row.staff.fullName, title: row.staff.title, role: row.staff.role, category: row.staff.category } : null,
    response: {
      at: row.respondedAt,
      byName: row.respondedByName,
      note: row.responseNote ?? null,
      movedFrom: row.rescheduledFrom,
    },
    school: row.school,
    schoolHoursNote:
      row.staff?.category === "ADMINISTRATION"
        ? null
        : "Teaching & non-teaching staff take visitors Mon–Fri 07:00–17:00 only; proposed times outside those hours are refused.",
  });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ code: string }> }) {
  const { code } = await params;
  const body = await req.json().catch(() => ({} as any));
  const token = String(body?.t ?? "");
  const decision = String(body?.decision ?? "").toUpperCase();
  const row = await db.appointment.findUnique({
    where: { code: code.toUpperCase() },
    include: { staff: true, school: { select: { name: true } } },
  });
  if (!row || !row.decisionToken || row.decisionToken !== token) {
    return NextResponse.json({ error: "This appointment link is not valid" }, { status: 404 });
  }
  if (!row.staffId) return NextResponse.json({ error: "This appointment has no staff member to respond" }, { status: 400 });
  if (!["ACCEPT", "DECLINE", "RESCHEDULE"].includes(decision)) {
    return NextResponse.json({ error: "decision must be ACCEPT, DECLINE or RESCHEDULE" }, { status: 400 });
  }
  if (!APPT_ACTIVE.includes(row.status)) {
    return NextResponse.json({ error: `This request was already answered (${row.status.toLowerCase()}) — call the front desk for changes` }, { status: 400 });
  }

  const now = new Date();
  const hostName = row.staff?.fullName ?? "Staff";
  const data: any = { respondedAt: now, respondedByName: hostName, respondedByUserId: null };
  if (typeof body?.note === "string" && body.note.trim()) data.responseNote = body.note.trim().slice(0, 300);

  if (decision === "ACCEPT") {
    data.status = "CONFIRMED";
  } else if (decision === "DECLINE") {
    data.status = "DECLINED";
  } else {
    const newAt = typeof body?.scheduledAt === "string" ? new Date(body.scheduledAt) : null;
    if (!newAt || isNaN(newAt.getTime())) {
      return NextResponse.json({ error: "Pick the new date and time to propose" }, { status: 400 });
    }
    if (newAt.getTime() < Date.now() - 60_000) {
      return NextResponse.json({ error: "That time is in the past — pick a moment ahead" }, { status: 400 });
    }
    const refusal = await oddHoursGuard(db, row.staff, newAt);
    if (refusal) return NextResponse.json({ error: refusal }, { status: 400 });
    data.status = "RESCHEDULED";
    data.scheduledAt = newAt;
    data.rescheduledFrom = row.scheduledAt;
    data.reminderSentAt = null; // re-arm the T-2-min reminder at the new time
    data.relayedAt = null; // the desk must relay the NEW time to the visitor
  }

  const updated = await db.appointment.update({ where: { id: row.id }, data, include: { staff: true } });

  // courtesy copy to the desk officer who booked it (so the desk hears the
  // answer even with the live board closed) — best-effort, never blocks
  const officer = updated.createdByStaffId
    ? await db.staff.findUnique({ where: { id: updated.createdByStaffId }, select: { fullName: true, phone: true, smsNotify: true } })
    : null;
  let deskSms = "SKIPPED";
  const smsSetting = await db.smsSetting.findUnique({ where: { schoolId: row.schoolId } });
  if (officer?.phone && officer.smsNotify && smsSetting?.autoApptAlerts && row.staff) {
    const t = new Date(updated.scheduledAt).toLocaleString("en-GB", { weekday: "short", day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" });
    const verb = updated.status === "CONFIRMED" ? "ACCEPTED" : updated.status === "DECLINED" ? "DECLINED" : "MOVED";
    const note = updated.responseNote ? ` — "${updated.responseNote}"` : "";
    const msg = await sendSms({
      db, schoolId: row.schoolId, recipientName: officer.fullName, recipientPhone: officer.phone,
      body: `${updated.code ?? "Appointment"}: ${row.staff.fullName} ${verb} the meeting with ${row.withName} (${t})${note}. Tell the visitor.`,
      category: "FRONT_OFFICE", sentByName: "Appointment link",
    });
    deskSms = msg.status;
  }

  return NextResponse.json({ ok: true, status: updated.status, scheduledAt: updated.scheduledAt, deskSms });
}

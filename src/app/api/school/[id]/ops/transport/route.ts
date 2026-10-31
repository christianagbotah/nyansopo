import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendSms } from "@/lib/sms";
import { transportFareReceipt } from "@/lib/receipt-payload";

// ── GET /api/school/[id]/ops/transport — routes, assignments, trips ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["transport.view"] });
  if (!auth.ok) return auth.res;

  const routes = await db.transportRoute.findMany({
    where: { schoolId },
    include: { assignments: { where: { active: true }, include: { student: { include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: true } } } } } } },
    orderBy: { name: "asc" },
  });

  const trips = await db.transportTrip.findMany({
    where: { route: { schoolId } },
    orderBy: { date: "desc" },
    take: 90,
  });
  const tripByRoute: Record<string, typeof trips> = {};
  trips.forEach((t) => (tripByRoute[t.routeId] ??= []).push(t));

  // students not yet on any route (for assignment picker)
  const assigned = new Set(routes.flatMap((r) => r.assignments.map((a) => a.studentId)));
  const enrolled = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, include: { student: true, classroom: true }, orderBy: { classroom: { name: "asc" } } });
  const unassigned = enrolled.filter((e) => !assigned.has(e.studentId));

  // current-term transport payments → per-student fare collected
  const year = await db.academicYear.findFirst({ where: { schoolId, isCurrent: true }, include: { terms: true } });
  const currentTerm = year?.terms.find((t) => t.isCurrent) ?? year?.terms[0];
  const termStart = currentTerm?.startsAt ?? new Date(new Date().getFullYear(), 0, 1);
  const transportPayments = await db.payment.findMany({
    where: { schoolId, category: "TRANSPORT", paidAt: { gte: termStart } },
    select: { studentId: true, amountCents: true },
  });
  const paidByStudent: Record<string, number> = {};
  transportPayments.forEach((p) => { if (p.studentId) paidByStudent[p.studentId] = (paidByStudent[p.studentId] ?? 0) + p.amountCents; });

  // recent boardings (last 14 days) — conductor manifest marking
  const boardings = await db.transportBoarding.findMany({
    where: { route: { schoolId }, date: { gte: new Date(Date.now() - 14 * 864e5) } },
    orderBy: { date: "desc" },
  });
  const boardingByAssignment: Record<string, { date: string; direction: string; fareCents: number; paidCents: number; recordedByName: string | null }[]> = {};
  boardings.forEach((b) => {
    const d = b.date.toISOString().slice(0, 10);
    (boardingByAssignment[b.assignmentId] ??= []).push({ date: d, direction: b.direction, fareCents: b.fareCents, paidCents: b.paidCents, recordedByName: b.recordedByName });
  });

  return NextResponse.json({
    termLabel: currentTerm ? `${currentTerm.name} · ${year!.name}` : "current term",
    routes: routes.map((r) => ({
      id: r.id, name: r.name, vehicleReg: r.vehicleReg, vehicleModel: r.vehicleModel,
      driverName: r.driverName, driverPhone: r.driverPhone, capacity: r.capacity,
      fare: r.farePerTermCents, farePerTrip: r.farePerTripCents, stops: r.stops.split(",").map((s) => s.trim()), active: r.active,
      riders: r.assignments.map((a) => ({
        id: a.id, studentId: a.studentId, student: `${a.student.firstName} ${a.student.lastName}`,
        classroom: a.student.enrollments[0]?.classroom?.name ?? null,
        pickupStop: a.pickupStop, dropStop: a.dropStop, fareOverride: a.fareOverrideCents,
        guardianPhone: a.student.guardianPhone,
        farePaid: paidByStudent[a.studentId] ?? 0,
        boardings: boardingByAssignment[a.id] ?? [],
      })),
      utilisation: Math.round((r.assignments.length / Math.max(1, r.capacity)) * 100),
      fareRevenue: r.assignments.reduce((s, a) => s + (a.fareOverrideCents ?? r.farePerTermCents), 0),
      fareCollected: r.assignments.reduce((s, a) => s + Math.min(paidByStudent[a.studentId] ?? 0, a.fareOverrideCents ?? r.farePerTermCents), 0),
      trips: (tripByRoute[r.id] ?? []).slice(0, 10).map((t) => ({ id: t.id, date: t.date, morning: t.morningCount, evening: t.eveningCount, notes: t.notes, recordedByName: t.recordedByName })),
    })),
    unassigned: unassigned.map((e) => ({ id: e.studentId, name: `${e.student.firstName} ${e.student.lastName}`, classroom: e.classroom.name, guardianPhone: e.student.guardianPhone })),
    summary: {
      routes: routes.length,
      learners: routes.reduce((s, r) => s + r.assignments.length, 0),
      capacity: routes.reduce((s, r) => s + r.capacity, 0),
      fareRevenue: routes.reduce((s, r) => s + r.assignments.reduce((x, a) => x + (a.fareOverrideCents ?? r.farePerTermCents), 0), 0),
      fareCollected: routes.reduce((s, r) => s + r.assignments.reduce((x, a) => x + Math.min(paidByStudent[a.studentId] ?? 0, a.fareOverrideCents ?? r.farePerTermCents), 0), 0),
    },
  });
}

// ── POST /api/school/[id]/ops/transport ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  // conduct-level actions (bus conductor) vs route management (transport manager)
  const CONDUCT = ["transport.conduct", "transport.manage"];
  const auth = await opsAuth(req, schoolId, "POST", {
    perms: ["record-trip", "boarding-save", "collect-fare"].includes(action) ? CONDUCT : ["transport.manage"],
  });
  if (!auth.ok) return auth.res;

  if (action === "save-route") {
    const { id, name, vehicleReg, vehicleModel, driverName, driverPhone, capacity, farePerTermCents, stops } = body;
    if (!name || !driverName) return NextResponse.json({ error: "Route name and driver are required" }, { status: 400 });
    const data = {
      name, vehicleReg: vehicleReg ?? "", vehicleModel: vehicleModel ?? null, driverName, driverPhone: driverPhone ?? "",
      capacity: Number(capacity) || 15, farePerTermCents: Number(farePerTermCents) || 0,
      stops: Array.isArray(stops) ? stops.join(", ") : (stops ?? ""),
    };
    const route = id ? await db.transportRoute.update({ where: { id }, data }) : await db.transportRoute.create({ data: { ...data, schoolId } });
    return NextResponse.json({ route });
  }

  if (action === "toggle-route") {
    const r = await db.transportRoute.findUnique({ where: { id: body.id } });
    if (!r || r.schoolId !== schoolId) return NextResponse.json({ error: "Route not found" }, { status: 404 });
    const updated = await db.transportRoute.update({ where: { id: body.id }, data: { active: !r.active } });
    return NextResponse.json({ route: updated });
  }

  if (action === "assign") {
    const { routeId, studentId, pickupStop, dropStop, fareOverrideCents } = body;
    if (!routeId || !studentId) return NextResponse.json({ error: "routeId and studentId required" }, { status: 400 });
    const a = await db.transportAssignment.upsert({
      where: { routeId_studentId: { routeId, studentId } },
      update: { pickupStop: pickupStop ?? null, dropStop: dropStop ?? null, fareOverrideCents: fareOverrideCents ? Number(fareOverrideCents) : null, active: true },
      create: { routeId, studentId, pickupStop: pickupStop ?? null, dropStop: dropStop ?? null, fareOverrideCents: fareOverrideCents ? Number(fareOverrideCents) : null },
    });
    return NextResponse.json({ assignment: a });
  }

  if (action === "unassign") {
    await db.transportAssignment.update({ where: { id: body.assignmentId }, data: { active: false } }).catch(() => null);
    return NextResponse.json({ ok: true });
  }

  if (action === "record-trip") {
    const { routeId, date, morningCount, eveningCount, notes } = body;
    if (!routeId || !date) return NextResponse.json({ error: "routeId and date required" }, { status: 400 });
    const trip = await db.transportTrip.upsert({
      where: { routeId_date: { routeId, date: new Date(date) } },
      update: { morningCount: morningCount == null ? null : Number(morningCount), eveningCount: eveningCount == null ? null : Number(eveningCount), notes: notes ?? null, recordedByName: auth.user.fullName },
      create: { routeId, date: new Date(date), morningCount: morningCount == null ? null : Number(morningCount), eveningCount: eveningCount == null ? null : Number(eveningCount), notes: notes ?? null, recordedByName: auth.user.fullName },
    });
    return NextResponse.json({ trip });
  }

  // conductor marks a learner's boarding for the day (IN / OUT / BOTH / NONE) with optional cash fare
  if (action === "boarding-save") {
    const { assignmentId, date, direction, paidCents } = body;
    if (!assignmentId || !date || !direction) return NextResponse.json({ error: "assignmentId, date and direction are required" }, { status: 400 });
    const a = await db.transportAssignment.findUnique({ where: { id: assignmentId }, include: { route: true } });
    if (!a || a.route.schoolId !== schoolId) return NextResponse.json({ error: "Assignment not found" }, { status: 404 });
    const routeFare = a.route.farePerTripCents || 0;
    const fare = direction === "BOTH" ? routeFare * 2 : direction === "NONE" ? 0 : routeFare;
    const boarding = await db.transportBoarding.upsert({
      where: { assignmentId_date: { assignmentId, date: new Date(date) } },
      update: { direction, fareCents: fare, paidCents: Number(paidCents) || 0, recordedByName: auth.user.fullName },
      create: { assignmentId, routeId: a.routeId, date: new Date(date), direction, fareCents: fare, paidCents: Number(paidCents) || 0, recordedByName: auth.user.fullName },
    });
    return NextResponse.json({ boarding });
  }

  // bill transport fares onto current-term invoices for assigned learners
  if (action === "bill-term") {
    const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
    if (!term) return NextResponse.json({ error: "No current term" }, { status: 400 });
    const routes = await db.transportRoute.findMany({ where: { schoolId, active: true }, include: { assignments: { where: { active: true } } } });
    const invoices = await db.invoice.findMany({ where: { schoolId, termId: term.id }, include: { items: true } });
    const invByStudent: Record<string, typeof invoices[0]> = {};
    invoices.forEach((i) => (invByStudent[i.studentId] = i));
    let billed = 0, skipped = 0, noInvoice = 0;
    for (const route of routes) {
      for (const a of route.assignments) {
        const inv = invByStudent[a.studentId];
        if (!inv) { noInvoice++; continue; }
        if (inv.items.some((it) => it.name.includes(route.name))) { skipped++; continue; }
        const amount = a.fareOverrideCents ?? route.farePerTermCents;
        await db.invoiceItem.create({ data: { invoiceId: inv.id, name: `Transportation — ${route.name}`, amountCents: amount } });
        const total = inv.totalCents + amount;
        await db.invoice.update({ where: { id: inv.id }, data: { totalCents: total, status: inv.paidCents >= total - inv.discountCents ? "PAID" : inv.paidCents > 0 ? "PARTIAL" : "UNPAID" } });
        billed++;
      }
    }
    return NextResponse.json({ billed, skipped, noInvoice });
  }

  // collect a transport fare directly at the counter (receipt + optional SMS)
  if (action === "collect-fare") {
    const { assignmentId, method, reference } = body;
    const a = await db.transportAssignment.findUnique({ where: { id: assignmentId }, include: { route: true, student: true } });
    if (!a || a.route.schoolId !== schoolId) return NextResponse.json({ error: "Assignment not found" }, { status: 404 });
    const year2 = await db.academicYear.findFirst({ where: { schoolId, isCurrent: true }, include: { terms: true } });
    const term2 = year2?.terms.find((t) => t.isCurrent) ?? year2?.terms[0];
    const termLabel = term2 ? `${term2.name} · ${year2!.name}` : "current term";
    const termStart = term2?.startsAt ?? new Date(new Date().getFullYear(), 0, 1);
    const already = await db.payment.aggregate({
      where: { schoolId, category: "TRANSPORT", studentId: a.studentId, paidAt: { gte: termStart } },
      _sum: { amountCents: true },
    });
    const fare = a.fareOverrideCents ?? a.route.farePerTermCents;
    const outstanding = Math.max(0, fare - (already._sum.amountCents ?? 0));
    if (outstanding <= 0) return NextResponse.json({ error: "This learner's fare is already settled for the term" }, { status: 400 });
    const seq = (await db.payment.count({ where: { schoolId } })) + 1001;
    const payment = await db.payment.create({
      data: {
        receiptNo: `RCP-2026-${String(seq).padStart(5, "0")}`, studentId: a.studentId, category: "TRANSPORT",
        schoolId, amountCents: outstanding, method: method ?? "CASH", reference: reference ?? null,
        receivedByName: auth.user.fullName, paidAt: new Date(),
      },
    });
    const setting = await db.smsSetting.findUnique({ where: { schoolId } });
    if (setting?.autoPaymentReceipts) {
      await sendSms({
        db, schoolId, studentId: a.studentId, recipientName: a.student.guardianName, recipientPhone: a.student.guardianPhone,
        body: `Transport fare of GH₵${(outstanding / 100).toFixed(2)} received for ${a.student.firstName} — route ${a.route.name} (${payment.receiptNo}). Thank you.`,
        category: "TRANSPORT", sentByName: auth.user.fullName,
      });
    }
    const receipt = await transportFareReceipt(db, payment.id, { name: a.route.name, vehicleReg: a.route.vehicleReg }, termLabel, fare);
    return NextResponse.json({ payment, receipt });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

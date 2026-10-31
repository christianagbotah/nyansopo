import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

// ── GET /api/school/[id]/ops/transport — routes, assignments, trips ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET");
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

  return NextResponse.json({
    routes: routes.map((r) => ({
      id: r.id, name: r.name, vehicleReg: r.vehicleReg, vehicleModel: r.vehicleModel,
      driverName: r.driverName, driverPhone: r.driverPhone, capacity: r.capacity,
      fare: r.farePerTermCents, stops: r.stops.split(",").map((s) => s.trim()), active: r.active,
      riders: r.assignments.map((a) => ({
        id: a.id, studentId: a.studentId, student: `${a.student.firstName} ${a.student.lastName}`,
        classroom: a.student.enrollments[0]?.classroom?.name ?? null,
        pickupStop: a.pickupStop, dropStop: a.dropStop, fareOverride: a.fareOverrideCents,
        guardianPhone: a.student.guardianPhone,
      })),
      utilisation: Math.round((r.assignments.length / Math.max(1, r.capacity)) * 100),
      fareRevenue: r.assignments.reduce((s, a) => s + (a.fareOverrideCents ?? r.farePerTermCents), 0),
      trips: (tripByRoute[r.id] ?? []).slice(0, 10).map((t) => ({ id: t.id, date: t.date, morning: t.morningCount, evening: t.eveningCount, notes: t.notes, recordedByName: t.recordedByName })),
    })),
    unassigned: unassigned.map((e) => ({ id: e.studentId, name: `${e.student.firstName} ${e.student.lastName}`, classroom: e.classroom.name, guardianPhone: e.student.guardianPhone })),
    summary: {
      routes: routes.length,
      learners: routes.reduce((s, r) => s + r.assignments.length, 0),
      capacity: routes.reduce((s, r) => s + r.capacity, 0),
      fareRevenue: routes.reduce((s, r) => s + r.assignments.reduce((x, a) => x + (a.fareOverrideCents ?? r.farePerTermCents), 0), 0),
    },
  });
}

// ── POST /api/school/[id]/ops/transport ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST");
  if (!auth.ok) return auth.res;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;

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

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

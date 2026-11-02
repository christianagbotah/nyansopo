import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendSms } from "@/lib/sms";
import { transportFareReceipt } from "@/lib/receipt-payload";
import { nextReceiptNo } from "@/lib/codes";

// ── GET /api/school/[id]/ops/transport — routes, assignments, trips ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["transport.view"] });
  if (!auth.ok) return auth.res;

  const routes = await db.transportRoute.findMany({
    where: { schoolId },
    include: { assignments: { where: { active: true }, include: { student: { include: { enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: true } } } } } }, stopRecords: { orderBy: { sortOrder: "asc" } } },
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
    routes: routes.map((r) => {
      // stops as structured objects (iEduPro transport_stops): each pickup point
      // may carry its own one-way fare — learners on the same route (or class!)
      // pay different fares depending on where they board
      const stopList = r.stopRecords.length
        ? r.stopRecords.map((s) => ({ name: s.name, fareCents: s.fareCents }))
        : r.stops.split(",").map((s) => s.trim()).filter(Boolean).map((name) => ({ name, fareCents: null as number | null }));
      return {
      id: r.id, name: r.name, vehicleReg: r.vehicleReg, vehicleModel: r.vehicleModel,
      driverName: r.driverName, driverPhone: r.driverPhone, capacity: r.capacity,
      fare: r.farePerTermCents, farePerTrip: r.farePerTripCents, stops: stopList.map((s) => s.name), stopRecords: stopList, active: r.active,
      riders: r.assignments.map((a) => {
        // resolved one-way fare for this rider: stop fare → route fare (iEduPro §resolve)
        const stopName = a.pickupStop ?? a.dropStop;
        const stop = stopName ? r.stopRecords.find((s) => s.name.trim().toLowerCase() === stopName.trim().toLowerCase() && s.fareCents != null) : undefined;
        return {
        id: a.id, studentId: a.studentId, student: `${a.student.firstName} ${a.student.lastName}`,
        classroom: a.student.enrollments[0]?.classroom?.name ?? null,
        pickupStop: a.pickupStop, dropStop: a.dropStop, fareOverride: a.fareOverrideCents,
        onewayFare: stop?.fareCents ?? r.farePerTripCents ?? 0, stopFare: stop?.fareCents ?? null,
        guardianPhone: a.student.guardianPhone,
        farePaid: paidByStudent[a.studentId] ?? 0,
        boardings: boardingByAssignment[a.id] ?? [],
        };
      }),
      utilisation: Math.round((r.assignments.length / Math.max(1, r.capacity)) * 100),
      fareRevenue: r.assignments.reduce((s, a) => s + (a.fareOverrideCents ?? r.farePerTermCents), 0),
      fareCollected: r.assignments.reduce((s, a) => s + Math.min(paidByStudent[a.studentId] ?? 0, a.fareOverrideCents ?? r.farePerTermCents), 0),
      trips: (tripByRoute[r.id] ?? []).slice(0, 10).map((t) => ({ id: t.id, date: t.date, morning: t.morningCount, evening: t.eveningCount, notes: t.notes, recordedByName: t.recordedByName })),
      };
    }),
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
    const { id, name, vehicleReg, vehicleModel, driverName, driverPhone, capacity, farePerTermCents, farePerTripCents, stops, stopRecords } = body;
    if (!name || !driverName) return NextResponse.json({ error: "Route name and driver are required" }, { status: 400 });
    // stops arrive either as plain names (legacy string/array) or structured
    // records [{ name, fareCents }] — per-stop one-way fares (iEduPro
    // transport_stops.fare_from_stop: the pickup point sets the fare)
    const parsedStops: { name: string; fareCents: number | null }[] = Array.isArray(stopRecords) && stopRecords.length
      ? stopRecords.map((s: any) => ({ name: String(s?.name ?? "").trim(), fareCents: s?.fareCents == null || s.fareCents === "" ? null : Math.max(0, Math.round(Number(s.fareCents))) })).filter((s: any) => s.name)
      : (Array.isArray(stops) ? stops : String(stops ?? "").split(",")).map((s: any) => ({ name: String(s).trim(), fareCents: null })).filter((s) => s.name);
    const data = {
      name, vehicleReg: vehicleReg ?? "", vehicleModel: vehicleModel ?? null, driverName, driverPhone: driverPhone ?? "",
      capacity: Number(capacity) || 15, farePerTermCents: Number(farePerTermCents) || 0,
      farePerTripCents: Math.max(0, Math.round(Number(farePerTripCents) || 0)),
      stops: parsedStops.map((s) => s.name).join(", "),
    };
    const route = id ? await db.transportRoute.update({ where: { id }, data }) : await db.transportRoute.create({ data: { ...data, schoolId } });
    // sync the structured stop records (replace-all keeps sortOrder canonical)
    await db.transportStop.deleteMany({ where: { routeId: route.id } });
    for (const [i, s] of parsedStops.entries()) {
      await db.transportStop.create({ data: { routeId: route.id, name: s.name, fareCents: s.fareCents, sortOrder: i } });
    }
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

  // conductor marks a learner's boarding for the day (IN / OUT / BOTH / NONE).
  // iEduPro parity (Daily_transport::mark_boarding + Daily_fee_model::mark_student_boarded):
  //   • fare = one-way route fare × direction (in 1× · out 1× · both 2× · none 0)
  //   • on confirmation, the fare is auto-deducted from the transport wallet when
  //     it holds enough; otherwise the unpaid leg is billed as owing (arrears)
  //   • the day's transport daily-charge is kept in sync (usage + amount)
  if (action === "boarding-save") {
    const { assignmentId, date, direction, paidCents, cashCents } = body;
    if (!assignmentId || !date || !direction) return NextResponse.json({ error: "assignmentId, date and direction are required" }, { status: 400 });
    if (!["IN", "OUT", "BOTH", "NONE"].includes(direction)) return NextResponse.json({ error: "direction must be IN, OUT, BOTH or NONE" }, { status: 400 });
    const a = await db.transportAssignment.findUnique({ where: { id: assignmentId }, include: { route: { include: { stopRecords: true } }, student: true } });
    if (!a || a.route.schoolId !== schoolId) return NextResponse.json({ error: "Assignment not found" }, { status: 404 });
    // one-way fare: per-stop override (pickup/drop) → route per-trip fare
    const stopName = a.pickupStop ?? a.dropStop;
    const stop = stopName ? a.route.stopRecords.find((s) => s.name.trim().toLowerCase() === stopName.trim().toLowerCase() && s.fareCents != null) : undefined;
    const oneway = stop?.fareCents ?? a.route.farePerTripCents ?? 0;
    const fare = direction === "BOTH" ? oneway * 2 : direction === "NONE" ? 0 : oneway;

    // existing boarding for the day (leg-aware update: an unpaid OUT after a paid IN
    // must only bill the afternoon leg — iEduPro "boarded OUT without morning payment")
    const day = new Date(date); day.setHours(0, 0, 0, 0);
    const prev = await db.transportBoarding.findFirst({ where: { assignmentId, date: day } });
    let inFare = 0, outFare = 0, dir = direction as string;
    if (direction === "NONE") {
      // not riding today — clears the day's fare (iEduPro 'none' → GH₵0)
      dir = "NONE";
    } else if (prev) {
      inFare = direction === "IN" || direction === "BOTH" ? oneway : prev.direction === "IN" || prev.direction === "BOTH" ? oneway : 0;
      outFare = direction === "OUT" || direction === "BOTH" ? oneway : prev.direction === "OUT" || prev.direction === "BOTH" ? oneway : 0;
      if (direction === "IN") { inFare = oneway; outFare = prev.direction === "OUT" || prev.direction === "BOTH" ? oneway : 0; }
      if (direction === "OUT") { outFare = oneway; inFare = prev.direction === "IN" || prev.direction === "BOTH" ? oneway : 0; }
      if (direction === "BOTH") { inFare = oneway; outFare = oneway; }
      dir = inFare > 0 && outFare > 0 ? "BOTH" : inFare > 0 ? "IN" : outFare > 0 ? "OUT" : "NONE";
    } else {
      inFare = direction === "IN" || direction === "BOTH" ? oneway : 0;
      outFare = direction === "OUT" || direction === "BOTH" ? oneway : 0;
    }
    const totalFare = inFare + outFare;

    // settle: explicit cash at the counter, else auto-deduct from the transport wallet
    let paid = Number(cashCents ?? paidCents) || 0;
    let walletDeduct = 0;
    let arrearsAdded = 0;
    if (totalFare > 0 && paid < totalFare) {
      const w = await db.studentWallet.findUnique({ where: { studentId: a.studentId } });
      const avail = w?.transportCents ?? 0;
      const need = totalFare - paid;
      if (avail >= need) {
        walletDeduct = need;
        await db.studentWallet.update({ where: { studentId: a.studentId }, data: { transportCents: { decrement: need } } });
        await db.walletTxn.create({
          data: { schoolId, studentId: a.studentId, wallet: "TRANSPORT", kind: "PAY", amountCents: -need, balanceAfter: avail - need, note: `Boarding ${dir.toLowerCase()} — ${a.route.name}`, byName: auth.user.fullName },
        });
        paid = totalFare;
      } else if (avail > 0) {
        walletDeduct = avail;
        await db.studentWallet.update({ where: { studentId: a.studentId }, data: { transportCents: { decrement: avail } } });
        await db.walletTxn.create({
          data: { schoolId, studentId: a.studentId, wallet: "TRANSPORT", kind: "PAY", amountCents: -avail, balanceAfter: 0, note: `Boarding ${dir.toLowerCase()} (part) — ${a.route.name}`, byName: auth.user.fullName },
        });
        paid += avail;
        arrearsAdded = totalFare - paid; // remainder bills as owing
      } else {
        arrearsAdded = need; // nothing on the wallet — full leg(s) owed
      }
    }

    const boarding = await db.transportBoarding.upsert({
      where: { assignmentId_date: { assignmentId, date: day } },
      update: { direction: dir, fareCents: totalFare, paidCents: paid, recordedByName: auth.user.fullName },
      create: { assignmentId, routeId: a.routeId, date: day, direction: dir, fareCents: totalFare, paidCents: paid, recordedByName: auth.user.fullName },
    });

    // keep the transport daily-charge in sync with the boarding mark
    let charge = null as any;
    try {
      const usage = dir === "BOTH" ? "BOTH" : dir === "IN" ? "MORNING" : dir === "OUT" ? "AFTERNOON" : "NOT_USING";
      const svc = await db.dailyService.findFirst({ where: { schoolId, category: "TRANSPORT" }, orderBy: { name: "asc" } });
      if (svc) {
        const dStart = new Date(day); const dEnd = new Date(day); dEnd.setHours(23, 59, 59, 999);
        const existing = await db.dailyCharge.findFirst({ where: { schoolId, studentId: a.studentId, date: { gte: dStart, lte: dEnd }, service: { category: "TRANSPORT" } } });
        if (existing) {
          charge = await db.dailyCharge.update({
            where: { id: existing.id },
            data: { usage, amountCents: totalFare, status: dir === "NONE" ? "WAIVED" : paid >= totalFare ? "PAID" : "OWED" },
          });
        } else if (dir !== "NONE") {
          charge = await db.dailyCharge.create({
            data: { serviceId: svc.id, studentId: a.studentId, schoolId, date: day, amountCents: totalFare, usage, status: paid >= totalFare ? "PAID" : "OWED" },
          });
        }
      }
    } catch { /* daily-service layer absent — boarding alone is fine */ }

    return NextResponse.json({
      boarding,
      charge,
      fare: { oneway, inFare, outFare, total: totalFare },
      settlement: { paid, walletDeduct, arrearsAdded },
      message: arrearsAdded > 0
        ? `Boarded marked — GH₵${(arrearsAdded / 100).toFixed(2)} billed as owing (transport wallet short)`
        : walletDeduct > 0 ? `Boarded marked — GH₵${(walletDeduct / 100).toFixed(2)} deducted from the transport wallet`
        : "Boarding marked",
    });
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
    const payment = await db.payment.create({
      data: {
        receiptNo: await nextReceiptNo(), studentId: a.studentId, category: "TRANSPORT",
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

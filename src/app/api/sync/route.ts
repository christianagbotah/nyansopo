import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser } from "@/lib/session";

// POST /api/sync — flush the offline action queue (idempotent per clientId)
export async function POST(req: NextRequest) {
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
  const { ops } = (await req.json()) as { ops: { clientId: string; kind: string; payload: any }[] };
  if (!Array.isArray(ops)) return NextResponse.json({ error: "ops[] required" }, { status: 400 });

  const results: { clientId: string; ok: boolean; detail?: string }[] = [];
  for (const op of ops) {
    try {
      if (op.kind === "payment") {
        const { schoolId, invoiceId, amountCents, method, reference, paidAt, category, studentId } = op.payload;
        if (!user.schoolIds.includes(schoolId)) throw new Error("forbidden");
        const dup = await db.payment.findFirst({ where: { reference: `SYNC:${op.clientId}` } });
        if (dup) { results.push({ clientId: op.clientId, ok: true, detail: "already synced" }); continue; }

        if (category === "DAILY") {
          // POS collection on daily services (canteen, water, classes)
          const owed = await db.dailyCharge.aggregate({ where: { studentId, status: "OWED" }, _sum: { amountCents: true } });
          const owedCents = owed._sum.amountCents ?? 0;
          if (owedCents <= 0) throw new Error("no daily balance");
          const amt = Math.max(0, Math.min(Number(amountCents), owedCents));
          const seq = (await db.payment.count({ where: { schoolId } })) + 1001;
          const payment = await db.payment.create({ data: { receiptNo: `RCP-2026-${String(seq).padStart(5, "0")}`, studentId, category: "DAILY", schoolId, amountCents: amt, method, reference: `SYNC:${op.clientId}`, receivedByName: `${user.fullName} (offline)`, paidAt: paidAt ? new Date(paidAt) : new Date(), offlineQueued: true, syncedAt: new Date() } });
          let remaining = amt;
          const owedCharges = await db.dailyCharge.findMany({ where: { studentId, status: "OWED" }, orderBy: { date: "asc" } });
          for (const c of owedCharges) {
            if (remaining <= 0) break;
            if (remaining >= c.amountCents) {
              await db.dailyCharge.update({ where: { id: c.id }, data: { status: "PAID", paymentId: payment.id } });
              remaining -= c.amountCents;
            } else {
              const paidPart = remaining;
              await db.dailyCharge.update({ where: { id: c.id }, data: { status: "PAID", paymentId: payment.id, amountCents: paidPart } });
              await db.dailyCharge.create({ data: { serviceId: c.serviceId, studentId, schoolId, date: c.date, amountCents: c.amountCents - paidPart, status: "OWED" } });
              remaining = 0;
            }
          }
          results.push({ clientId: op.clientId, ok: true, detail: "daily receipt issued" });
          continue;
        }

        const inv = await db.invoice.findUnique({ where: { id: invoiceId } });
        if (!inv) throw new Error("invoice missing");
        const amt = Math.max(0, Math.min(Number(amountCents), inv.totalCents - inv.discountCents - inv.paidCents));
        const seq = (await db.payment.count({ where: { schoolId } })) + 1001;
        await db.payment.create({ data: { receiptNo: `RCP-2026-${String(seq).padStart(5, "0")}`, invoiceId, schoolId, amountCents: amt, method, reference: `SYNC:${op.clientId}`, receivedByName: `${user.fullName} (offline)`, paidAt: paidAt ? new Date(paidAt) : new Date(), offlineQueued: true, syncedAt: new Date() } });
        const paid = inv.paidCents + amt;
        await db.invoice.update({ where: { id: invoiceId }, data: { paidCents: paid, status: paid >= inv.totalCents - inv.discountCents ? "PAID" : "PARTIAL" } });
        results.push({ clientId: op.clientId, ok: true, detail: `receipt issued` });
      } else if (op.kind === "attendance") {
        const { schoolId, classroomId, date, records } = op.payload;
        if (!user.schoolIds.includes(schoolId)) throw new Error("forbidden");
        const dup = await db.attendanceSession.findFirst({ where: { classroomId, date: new Date(date), takenByName: { contains: `SYNC:${op.clientId}` } } });
        if (dup) { results.push({ clientId: op.clientId, ok: true, detail: "already synced" }); continue; }
        const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
        const session = await db.attendanceSession.create({ data: { classroomId, schoolId, termId: term!.id, date: new Date(date), takenByName: `${user.fullName} (SYNC:${op.clientId})` } });
        for (const r of records) await db.attendanceRecord.create({ data: { sessionId: session.id, studentId: r.studentId, status: r.status, markedOffline: true } });
        results.push({ clientId: op.clientId, ok: true, detail: `${records.length} marked` });
      } else if (op.kind === "marks") {
        const { schoolId, assessmentId, marks } = op.payload;
        if (!user.schoolIds.includes(schoolId)) throw new Error("forbidden");
        await db.mark.deleteMany({ where: { assessmentId } });
        for (const m of marks) if (m.score !== null && m.score !== "") await db.mark.create({ data: { assessmentId, studentId: m.studentId, score: Number(m.score), enteredOffline: true } });
        results.push({ clientId: op.clientId, ok: true, detail: "marks saved" });
      } else if (op.kind === "student") {
        // enrollment captured offline — replayed through the same logic as the online path
        const p = op.payload;
        if (!user.schoolIds.includes(p.schoolId)) throw new Error("forbidden");
        const dup = await db.enrollment.findFirst({ where: { student: { nyansapoIndex: `SYNC-${op.clientId}` } } });
        if (dup) { results.push({ clientId: op.clientId, ok: true, detail: "already synced" }); continue; }
        const count = await db.student.count();
        const student = await db.student.create({ data: { nyansapoIndex: `SYNC-${op.clientId}`, firstName: p.firstName, lastName: p.lastName, gender: p.gender ?? "M", guardianName: p.guardianName, guardianPhone: p.guardianPhone, status: "ACTIVE", previousSchool: "Registered offline" } });
        // upgrade index now that we are online
        const year = p.dateOfBirth ? new Date(p.dateOfBirth).getFullYear() : 2016;
        await db.student.update({ where: { id: student.id }, data: { nyansapoIndex: `NY-${year}-${String(100000 + count + 1).slice(1)}` } });
        const roll = (await db.enrollment.count({ where: { classroomId: p.classroomId } })) + 1;
        await db.enrollment.create({ data: { studentId: student.id, classroomId: p.classroomId, schoolId: p.schoolId, rollNo: roll, admissionDate: new Date(), status: "ACTIVE" } });
        results.push({ clientId: op.clientId, ok: true, detail: "student enrolled" });
      } else {
        throw new Error(`unknown op kind ${op.kind}`);
      }
    } catch (e: any) {
      results.push({ clientId: op.clientId, ok: false, detail: e?.message ?? "failed" });
    }
  }
  return NextResponse.json({ results, syncedAt: new Date().toISOString() });
}

import { NextRequest, NextResponse } from "next/server";
import { Prisma } from "@prisma/client";
import { db } from "@/lib/db";
import { getUser } from "@/lib/session";
import { nextInvoiceNo, nextReceiptNo, nextStudentIndex } from "@/lib/codes";

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
          const payment = await db.payment.create({ data: { receiptNo: await nextReceiptNo(), studentId, category: "DAILY", schoolId, amountCents: amt, method, reference: `SYNC:${op.clientId}`, receivedByName: `${user.fullName} (offline)`, paidAt: paidAt ? new Date(paidAt) : new Date(), offlineQueued: true, syncedAt: new Date() } });
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
        await db.payment.create({ data: { receiptNo: await nextReceiptNo(), invoiceId, studentId: inv.studentId, schoolId, amountCents: amt, method, reference: `SYNC:${op.clientId}`, receivedByName: `${user.fullName} (offline)`, paidAt: paidAt ? new Date(paidAt) : new Date(), offlineQueued: true, syncedAt: new Date() } });
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
        for (const r of records) await db.attendanceRecord.create({ data: { sessionId: session.id, studentId: r.studentId, status: r.status, excusedReason: r.excusedReason ?? null, markedOffline: true } });
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
        // durable idempotency: the clientId lives on the student row itself, so
        // a re-flush (or two flushes racing) can never create a duplicate — the
        // earlier SYNC-<clientId> index trick broke the moment the index was
        // upgraded to NY-…, letting a re-flush mint a second student.
        const dup = await db.student.findFirst({ where: { syncClientId: op.clientId } });
        if (dup) { results.push({ clientId: op.clientId, ok: true, detail: "already synced" }); continue; }
        const birthYear = p.dateOfBirth ? new Date(p.dateOfBirth).getFullYear() : 2016;
        let student;
        try {
          student = await db.student.create({ data: { nyansapoIndex: await nextStudentIndex(birthYear), syncClientId: op.clientId, firstName: p.firstName, lastName: p.lastName, gender: p.gender ?? "M", dateOfBirth: p.dateOfBirth ? new Date(p.dateOfBirth) : null, guardianName: p.guardianName, guardianPhone: p.guardianPhone, status: "ACTIVE", previousSchool: p.previousSchool ?? "Registered offline" } });
        } catch (e) {
          if (e instanceof Prisma.PrismaClientKnownRequestError && e.code === "P2002") { results.push({ clientId: op.clientId, ok: true, detail: "already synced" }); continue; }
          throw e;
        }
        const roll = (await db.enrollment.count({ where: { classroomId: p.classroomId } })) + 1;
        await db.enrollment.create({ data: { studentId: student.id, classroomId: p.classroomId, schoolId: p.schoolId, rollNo: roll, admissionDate: new Date(), status: "ACTIVE" } });
        // auto-bill for the current term — same as the online enrolment path
        const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId: p.schoolId } } });
        let billed: string | null = null;
        if (term) {
          const fs = await db.feeStructure.findFirst({ where: { classroomId: p.classroomId, termId: term.id }, include: { items: true } });
          if (fs) {
            const inv = await db.invoice.create({ data: { invoiceNo: await nextInvoiceNo(), studentId: student.id, schoolId: p.schoolId, termId: term.id, classroomName: p.classroomName ?? "", totalCents: fs.totalCents, status: "UNPAID", dueDate: new Date(Date.now() + 14 * 864e5) } });
            for (const it of fs.items.filter((i) => !i.isOptional)) await db.invoiceItem.create({ data: { invoiceId: inv.id, name: it.name, amountCents: it.amountCents } });
            billed = inv.invoiceNo;
          }
        }
        const onRoll = await db.enrollment.count({ where: { schoolId: p.schoolId, status: "ACTIVE" } });
        await db.school.update({ where: { id: p.schoolId }, data: { studentsOnRoll: onRoll } });
        results.push({ clientId: op.clientId, ok: true, detail: billed ? `student enrolled · billed ${billed}` : "student enrolled" });
      } else {
        throw new Error(`unknown op kind ${op.kind}`);
      }
    } catch (e: any) {
      results.push({ clientId: op.clientId, ok: false, detail: e?.message ?? "failed" });
    }
  }
  return NextResponse.json({ results, syncedAt: new Date().toISOString() });
}

import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendSms } from "@/lib/sms";
import { invoicePaymentReceipt } from "@/lib/receipt-payload";
import { nextInvoiceNo } from "@/lib/invoice-no";

// ── GET /api/school/[id]/ops/admissions — admission workspace ──
// classrooms with capacity/fees, invoice discounts, recent admissions
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET");
  if (!auth.ok) return auth.res;

  const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } }, include: { academicYear: true } });

  const classrooms = await db.classroom.findMany({
    where: { schoolId },
    include: {
      classTeacher: true,
      _count: { select: { enrollments: { where: { status: "ACTIVE" } } } },
      feeStructures: { where: { termId: term?.id }, include: { items: true }, orderBy: { id: "desc" }, take: 1 },
    },
    orderBy: [{ gradeLevel: "asc" }, { name: "asc" }],
  });

  const discounts = await db.discount.findMany({
    where: { schoolId, scope: "INVOICE", active: true },
    orderBy: { name: "asc" },
  });

  const recent = await db.enrollment.findMany({
    where: { schoolId, admissionDate: { gte: new Date(Date.now() - 120 * 864e5) } },
    include: { student: true, classroom: true },
    orderBy: { admissionDate: "desc" },
    take: 12,
  });

  const studentsTotal = await db.student.count();

  return NextResponse.json({
    term: term ? { id: term.id, name: term.name, year: term.academicYear.name } : null,
    classrooms: classrooms.map((c) => ({
      id: c.id,
      name: c.name,
      gradeLevel: c.gradeLevel,
      stageLabel: c.stageLabel,
      capacity: c.capacity,
      classTeacher: c.classTeacher?.fullName ?? null,
      enrolled: c._count.enrollments,
      feeItems: c.feeStructures[0]?.items.map((i) => ({ name: i.name, amountCents: i.amountCents, isOptional: i.isOptional })) ?? [],
      feeTotal: c.feeStructures[0]?.totalCents ?? 0,
    })),
    discounts: discounts.map((d) => ({ id: d.id, name: d.name, type: d.type, value: d.value, notes: d.notes })),
    recent: recent.map((e) => ({
      id: e.id,
      student: `${e.student.firstName} ${e.student.lastName}`,
      nyansapoIndex: e.student.nyansapoIndex,
      classroom: e.classroom.name,
      guardianPhone: e.student.guardianPhone,
      admittedAt: e.admissionDate,
      status: e.status,
    })),
    indexSeq: studentsTotal + 1,
  });
}

// ── POST /api/school/[id]/ops/admissions ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST");
  if (!auth.ok) return auth.res;
  const user = auth.user;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;

  if (action === "admit") {
    const {
      firstName, lastName, otherNames, gender, dateOfBirth,
      classroomId, guardianName, guardianPhone, guardianAltPhone, guardianEmail, guardianOccupation, guardianAddress,
      relationship, hometown, previousSchool,
      billItems, discountId, initialPaymentCents, initialPaymentMethod,
    } = body;

    // ── validation ──
    if (!firstName?.trim() || !lastName?.trim()) return NextResponse.json({ error: "First and last name are required" }, { status: 400 });
    if (!gender) return NextResponse.json({ error: "Gender is required" }, { status: 400 });
    if (!classroomId) return NextResponse.json({ error: "Choose a class to enrol in" }, { status: 400 });
    if (!guardianName?.trim() || !guardianPhone?.trim()) return NextResponse.json({ error: "Guardian name and phone are required" }, { status: 400 });
    const classroom = await db.classroom.findUnique({ where: { id: classroomId } });
    if (!classroom || classroom.schoolId !== schoolId) return NextResponse.json({ error: "Class not found" }, { status: 404 });

    const items: { name: string; amountCents: number }[] = (billItems ?? [])
      .filter((i: any) => i.name?.trim() && Number(i.amountCents) > 0)
      .map((i: any) => ({ name: String(i.name).trim(), amountCents: Math.round(Number(i.amountCents)) }));
    const gross = items.reduce((s, i) => s + i.amountCents, 0);

    // ── student + national index ──
    const year = new Date().getFullYear();
    const count = await db.student.count();
    const nyansapoIndex = `NY-${year}-${String(100000 + count + 1).slice(1)}`;

    const student = await db.student.create({
      data: {
        nyansapoIndex,
        firstName: String(firstName).trim(),
        lastName: String(lastName).trim(),
        otherNames: otherNames?.trim() || null,
        gender: gender === "F" ? "F" : "M",
        dateOfBirth: dateOfBirth ? new Date(dateOfBirth) : null,
        guardianName: String(guardianName).trim(),
        guardianPhone: String(guardianPhone).trim(),
        guardianAltPhone: guardianAltPhone?.trim() || null,
        guardianEmail: guardianEmail?.trim() || null,
        guardianOccupation: guardianOccupation?.trim() || null,
        guardianAddress: guardianAddress?.trim() || null,
        relationship: relationship || "Parent",
        hometown: hometown?.trim() || null,
        previousSchool: previousSchool?.trim() || null,
        status: "ACTIVE",
      },
    });

    // ── enrollment with roll number ──
    const rollCount = await db.enrollment.count({ where: { classroomId, status: "ACTIVE" } });
    const enrollment = await db.enrollment.create({
      data: {
        studentId: student.id, classroomId, schoolId,
        rollNo: rollCount + 1, admissionDate: new Date(), status: "ACTIVE",
      },
    });

    // ── invoice (term bill) ──
    const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
    let invoice: any = null;
    let discountCents = 0;
    let discountName: string | null = null;
    if (items.length && term) {
      let discount: any = null;
      if (discountId) {
        discount = await db.discount.findUnique({ where: { id: discountId } });
        if (!discount || discount.schoolId !== schoolId || discount.scope !== "INVOICE") discount = null;
      }
      if (discount) {
        discountCents = discount.type === "PERCENT" ? Math.round((gross * discount.value) / 100) : Math.min(discount.value, gross);
        discountName = discount.name;
        // record the application for the audit trail
        await db.discountApplication.create({
          data: { discountId: discount.id, studentId: student.id, schoolId, reason: `Admission bill — ${discount.name}`, appliedByName: user.fullName },
        });
      }
      invoice = await db.invoice.create({
        data: {
          invoiceNo: await nextInvoiceNo(),
          studentId: student.id, schoolId, termId: term.id, classroomName: classroom.name,
          totalCents: gross, discountCents, discountReason: discountName, paidCents: 0, status: "UNPAID",
          dueDate: new Date(Date.now() + 14 * 864e5),
        },
      });
      for (const it of items) await db.invoiceItem.create({ data: { invoiceId: invoice.id, name: it.name, amountCents: it.amountCents } });
    }

    // ── optional first payment at the counter ──
    let receipt: any = null;
    const pay = Math.round(Number(initialPaymentCents) || 0);
    if (pay > 0 && invoice) {
      const payable = invoice.totalCents - discountCents;
      const amt = Math.min(pay, payable);
      const seq = (await db.payment.count({ where: { schoolId } })) + 1001;
      const payment = await db.payment.create({
        data: {
          receiptNo: `RCP-${year}-${String(seq).padStart(5, "0")}`,
          invoiceId: invoice.id, studentId: student.id, schoolId, amountCents: amt,
          method: initialPaymentMethod ?? "CASH", receivedByName: user.fullName, paidAt: new Date(),
        },
      });
      await db.invoice.update({
        where: { id: invoice.id },
        data: { paidCents: amt, status: amt >= payable ? "PAID" : "PARTIAL" },
      });
      receipt = await invoicePaymentReceipt(db, payment.id);
    }

    // ── welcome SMS (failures never block admission) ──
    try {
      await sendSms({
        db, schoolId, studentId: student.id, recipientName: guardianName, recipientPhone: student.guardianPhone,
        body: `Akwaaba! ${firstName} has been admitted to ${classroom.name}. Nyansapo Index: ${nyansapoIndex}. ${invoice ? `Term bill: GH₵${((gross - discountCents) / 100).toFixed(2)}.` : ""}`,
        category: "ADMISSION", sentByName: user.fullName,
      });
    } catch { /* ignore SMS errors */ }

    return NextResponse.json({
      student: { id: student.id, name: `${student.firstName} ${student.lastName}`, nyansapoIndex },
      enrollment: { id: enrollment.id, rollNo: enrollment.rollNo, classroom: classroom.name },
      invoice: invoice ? { id: invoice.id, invoiceNo: invoice.invoiceNo, total: gross, discount: discountCents, payable: gross - discountCents } : null,
      receipt,
    });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

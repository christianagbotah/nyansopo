import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser } from "@/lib/session";

// ── GET /api/school/[id]/ops/portal?view=parent|student|staff
//    Self-service portal data — strictly scoped to the signed-in account:
//    parents see only their linked children, students only their own record,
//    staff only their own profile & payslips. ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
  if (user.schoolId !== schoolId && !(user.schoolIds ?? []).includes(schoolId)) {
    return NextResponse.json({ error: "Forbidden" }, { status: 403 });
  }
  const view = new URL(req.url).searchParams.get("view") ?? "parent";
  const now = new Date();

  // ─────────────────────────── PARENT ───────────────────────────
  if (view === "parent") {
    if (user.role !== "PARENT" || !user.linkedStudentIds.length) {
      return NextResponse.json({ error: "No children linked to this account" }, { status: 403 });
    }
    const year = await db.academicYear.findFirst({ where: { schoolId, isCurrent: true }, include: { terms: true } });
    const term = year?.terms.find((t) => t.isCurrent) ?? null;

    const children: any[] = [];
    for (const sid of user.linkedStudentIds) {
      const s = await db.student.findUnique({
        where: { id: sid },
        include: {
          enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: { include: { classTeacher: true } } } },
          invoices: { where: { ...(year ? { term: { academicYearId: year.id } } : {}), schoolId }, include: { items: true, payments: true }, orderBy: { createdAt: "desc" } },
          attendanceRecords: { orderBy: { session: { date: "desc" } }, take: 40, include: { session: true } },
          reportCards: { orderBy: { createdAt: "desc" }, take: 3, include: { term: { include: { academicYear: true } }, results: true } },
          transportAssignments: { where: { active: true }, include: { route: true } },
          wallet: true,
        },
      });
      if (!s) continue;
      const att = s.attendanceRecords;
      const presentish = att.filter((r) => r.status === "PRESENT" || r.status === "LATE").length;
      const activeAssignment = s.transportAssignments[0] ?? null;
      const boardings = activeAssignment
        ? await db.transportBoarding.findMany({ where: { assignmentId: activeAssignment.id }, orderBy: { date: "desc" }, take: 6 })
        : [];
      children.push({
        id: s.id, name: `${s.firstName} ${s.lastName}`, otherNames: s.otherNames, nyansapoIndex: s.nyansapoIndex,
        gender: s.gender, status: s.status, guardianPhone: s.guardianPhone,
        classroom: s.enrollments[0]?.classroom?.name ?? null,
        classTeacher: s.enrollments[0]?.classroom?.classTeacher?.fullName ?? null,
        invoices: (s.invoices ?? []).map((i) => ({
          id: i.id, invoiceNo: i.invoiceNo, status: i.status, dueDate: i.dueDate, createdAt: i.createdAt,
          total: i.totalCents, discount: i.discountCents, paid: i.paidCents,
          balance: i.totalCents - i.discountCents - i.paidCents,
          items: i.items.map((it) => ({ name: it.name, amount: it.amountCents })),
          term: i.termId === term?.id ? term.name : null,
        })),
        payments: (s.invoices ?? []).flatMap((i) => i.payments.map((p) => ({ receiptNo: p.receiptNo, amount: p.amountCents, method: p.method, paidAt: p.paidAt, invoiceNo: i.invoiceNo })))
          .sort((a, b) => new Date(b.paidAt).getTime() - new Date(a.paidAt).getTime()).slice(0, 12),
        attendance: {
          total: att.length, present: att.filter((r) => r.status === "PRESENT").length,
          late: att.filter((r) => r.status === "LATE").length, absent: att.filter((r) => r.status === "ABSENT").length,
          rate: att.length ? Math.round((presentish / att.length) * 100) : null,
          recent: att.slice(0, 10).map((r) => ({ date: r.session.date, status: r.status })),
        },
        reportCards: s.reportCards.map((rc) => ({
          id: rc.id, term: `${rc.term.name} · ${rc.term.academicYear.name}`, classroom: rc.classroomName,
          average: rc.average, position: rc.classPosition, classSize: rc.classSize, conduct: rc.conduct,
          interest: rc.interest, attendance: rc.attendance, teacherRemark: rc.teacherRemark, headRemark: rc.headRemark,
          promotion: rc.promotion, published: rc.published,
          results: rc.results.map((r) => ({ subject: r.subjectName, isCore: r.isCore, total: r.total, grade: r.grade, remark: r.remark, position: r.position })),
        })),
        transport: activeAssignment ? {
          route: activeAssignment.route.name, vehicleReg: activeAssignment.route.vehicleReg,
          driverName: activeAssignment.route.driverName, driverPhone: activeAssignment.route.driverPhone,
          pickupStop: activeAssignment.pickupStop, dropStop: activeAssignment.dropStop,
          farePerTerm: activeAssignment.fareOverrideCents ?? activeAssignment.route.farePerTermCents,
          farePerTrip: activeAssignment.route.farePerTripCents,
          recentBoardings: boardings.map((b) => ({ date: b.date, direction: b.direction, fare: b.fareCents, paid: b.paidCents })),
        } : null,
        wallet: s.wallet ? { prepaid: s.wallet.prepaidCents, transport: s.wallet.transportCents } : null,
      });
    }

    const announcements = await db.announcement.findMany({
      where: { OR: [{ schoolId }, { schoolId: null, audience: "PARENTS" }] },
      orderBy: { postedAt: "desc" }, take: 6,
    });
    const messages = await db.smsMessage.findMany({
      where: { schoolId, studentId: { in: user.linkedStudentIds } },
      orderBy: { sentAt: "desc" }, take: 12,
    });
    const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true, motto: true, phone: true, town: true, region: true } });
    return NextResponse.json({
      role: "PARENT", school, year: year?.name ?? null, term: term?.name ?? null, children, announcements, messages,
      messagesNote: "Messages sent by the school about your children",
    });
  }

  // ─────────────────────────── STUDENT ───────────────────────────
  if (view === "student") {
    if (user.role !== "STUDENT" || !user.studentId) {
      return NextResponse.json({ error: "This account is not linked to a learner record" }, { status: 403 });
    }
    const s = await db.student.findUnique({
      where: { id: user.studentId },
      include: {
        enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: { include: { classTeacher: true } } } },
        attendanceRecords: { orderBy: { session: { date: "desc" } }, take: 40, include: { session: true } },
        reportCards: { orderBy: { createdAt: "desc" }, take: 4, include: { term: { include: { academicYear: true } }, results: true } },
        marks: { include: { assessment: { include: { subject: true, term: true } } } },
        libraryLoans: { where: { returnedAt: null }, include: { book: true }, orderBy: { dueAt: "asc" } },
        wallet: true,
        invoices: { where: { schoolId }, include: { items: true } },
      },
    });
    if (!s) return NextResponse.json({ error: "Learner record not found" }, { status: 404 });
    const classroom = s.enrollments[0]?.classroom ?? null;
    const timetable = classroom
      ? await db.timetableSlot.findMany({ where: { classroomId: classroom.id }, include: { subject: true, staff: true }, orderBy: [{ dayOfWeek: "asc" }, { startMinute: "asc" }] })
      : [];
    const att = s.attendanceRecords;
    const presentish = att.filter((r) => r.status === "PRESENT" || r.status === "LATE").length;
    const year = await db.academicYear.findFirst({ where: { schoolId, isCurrent: true }, include: { terms: true } });
    const term = year?.terms.find((t) => t.isCurrent) ?? null;
    const balance = s.invoices.reduce((sum, i) => sum + i.totalCents - i.discountCents - i.paidCents, 0);

    const announcements = await db.announcement.findMany({ where: { schoolId }, orderBy: { postedAt: "desc" }, take: 6 });
    const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true, motto: true, town: true, region: true } });
    return NextResponse.json({
      role: "STUDENT", school, year: year?.name ?? null, term: term?.name ?? null,
      me: {
        id: s.id, name: `${s.firstName} ${s.lastName}`, nyansapoIndex: s.nyansapoIndex, gender: s.gender,
        classroom: classroom?.name ?? null, classroomId: classroom?.id ?? null, classTeacher: classroom?.classTeacher?.fullName ?? null,
        attendance: {
          total: att.length, present: att.filter((r) => r.status === "PRESENT").length,
          late: att.filter((r) => r.status === "LATE").length, absent: att.filter((r) => r.status === "ABSENT").length,
          rate: att.length ? Math.round((presentish / att.length) * 100) : null,
          recent: att.slice(0, 10).map((r) => ({ date: r.session.date, status: r.status })),
        },
        timetable: timetable.map((t) => ({
          day: t.dayOfWeek, start: t.startMinute, end: t.endMinute,
          subject: t.subject?.name ?? t.subjectName, teacher: t.staff?.fullName ?? null,
        })),
        marks: s.marks.filter((m) => m.score !== null && term && m.assessment.termId === term.id).map((m) => ({
          subject: m.assessment.subject.name, assessment: m.assessment.title, type: m.assessment.type, score: m.score, max: m.assessment.maxScore,
        })),
        reportCards: s.reportCards.map((rc) => ({
          id: rc.id, term: `${rc.term.name} · ${rc.term.academicYear.name}`, classroom: rc.classroomName,
          average: rc.average, position: rc.classPosition, classSize: rc.classSize, conduct: rc.conduct,
          interest: rc.interest, attendance: rc.attendance, teacherRemark: rc.teacherRemark, headRemark: rc.headRemark,
          promotion: rc.promotion, published: rc.published,
          results: rc.results.map((r) => ({ subject: r.subjectName, isCore: r.isCore, total: r.total, grade: r.grade, remark: r.remark, position: r.position })),
        })),
        library: s.libraryLoans.map((l) => ({ book: l.book.title, dueAt: l.dueAt, overdue: l.dueAt < now })),
        wallet: s.wallet ? { prepaid: s.wallet.prepaidCents, transport: s.wallet.transportCents } : null,
        feesBalance: balance,
      },
      announcements,
    });
  }

  // ─────────────────────────── STAFF (self-service) ───────────────────────────
  if (view === "staff") {
    if (!user.staffId) return NextResponse.json({ error: "This account is not linked to a staff record" }, { status: 403 });
    const st = await db.staff.findUnique({
      where: { id: user.staffId },
      include: {
        payslips: { orderBy: { payrollRun: { period: "desc" } }, take: 12, include: { payrollRun: { select: { period: true, status: true, paidAt: true } } } },
        classrooms: true,
        timetableSlots: { include: { subject: true, classroom: true }, orderBy: [{ dayOfWeek: "asc" }, { startMinute: "asc" }] },
      },
    });
    if (!st || st.schoolId !== schoolId) return NextResponse.json({ error: "Staff record not found" }, { status: 404 });
    const announcements = await db.announcement.findMany({ where: { schoolId }, orderBy: { postedAt: "desc" }, take: 6 });
    const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true, motto: true, town: true, region: true, phone: true } });
    const mask = (v?: string | null) => (v && v.length > 5 ? `${v.slice(0, 3)}${"•".repeat(v.length - 5)}${v.slice(-2)}` : v ? "••••" : null);
    return NextResponse.json({
      role: "STAFF", school, announcements,
      me: {
        fullName: st.fullName, title: st.title, position: st.position, category: st.category,
        department: st.department, staffCode: st.staffCode, qualification: st.qualification,
        employmentType: st.employmentType, hiredAt: st.hiredAt, phone: st.phone, email: st.email,
        ssnitNumber: mask(st.ssnitNumber), accountNumber: mask(st.accountNumber), momoNumber: mask(st.momoNumber),
        subjects: st.subjects?.split(",").map((x) => x.trim()).filter(Boolean) ?? [],
        classrooms: st.classrooms.map((c) => c.name),
        timetable: st.timetableSlots.map((t) => ({
          day: t.dayOfWeek, start: t.startMinute, end: t.endMinute,
          subject: t.subject?.name ?? t.subjectName, classroom: t.classroom.name,
        })),
        payslips: st.payslips.map((p) => ({
          id: p.id, period: p.payrollRun.period, runStatus: p.payrollRun.status, paidAt: p.paidAt ?? p.payrollRun.paidAt,
          reference: p.reference, status: p.status,
          base: p.baseSalaryCents, allowances: JSON.parse(p.allowancesJson), gross: p.grossCents,
          ssnit: p.ssnitEmployeeCents, paye: p.payeCents, otherDeductions: JSON.parse(p.otherDeductionsJson),
          deductions: p.totalDeductionsCents, net: p.netCents, workingDays: p.workingDays, daysPresent: p.daysPresent,
        })),
      },
    });
  }

  return NextResponse.json({ error: "Unknown view" }, { status: 400 });
}

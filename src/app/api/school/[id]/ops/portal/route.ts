import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser } from "@/lib/session";
import { attStats, leaveBalances } from "@/lib/staff-duty-core";

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

  // ── shared: library catalogue for the "Request a book" dialog (iEduPro parity) ──
  const loadCatalogue = () => db.libraryBook.findMany({
    where: { schoolId },
    orderBy: { title: "asc" },
    take: 200,
    select: { id: true, title: true, author: true, category: true, available: true },
  });
  const mapBookRequest = (r: { id: string; book: { title: string }; startAt: Date; endAt: Date; status: string; handledAt: Date | null; handledByName: string | null; createdAt: Date }) => ({
    id: r.id, book: r.book.title, startAt: r.startAt, endAt: r.endAt, status: r.status,
    handledAt: r.handledAt, handledByName: r.handledByName, createdAt: r.createdAt,
    overdue: r.status === "ISSUED" && r.endAt < now, // issued and the requested return date has passed
  });

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
          libraryLoans: { where: { returnedAt: null }, include: { book: true }, orderBy: { dueAt: "asc" } },
          bookRequests: { include: { book: true }, orderBy: { createdAt: "desc" }, take: 30 },
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
        library: (s.libraryLoans ?? []).map((l) => ({ book: l.book.title, dueAt: l.dueAt, overdue: l.dueAt < now })),
        bookRequests: (s.bookRequests ?? []).map(mapBookRequest),
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
    // meal opt-in / opt-out: the school's meal services + this family's requests
    const mealServices = await db.dailyService.findMany({
      where: { schoolId, active: true, category: { in: ["MEAL", "DRINK"] } },
      orderBy: { name: "asc" },
      select: { id: true, name: true, category: true, optInDaily: true, weekdays: true },
    });
    const mealRequests = await db.dailyMealException.findMany({
      where: { schoolId, studentId: { in: user.linkedStudentIds } },
      include: { student: { select: { firstName: true, lastName: true } }, service: { select: { name: true } } },
      orderBy: { createdAt: "desc" }, take: 20,
    });
    const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true, motto: true, phone: true, town: true, region: true } });
    return NextResponse.json({
      role: "PARENT", school, year: year?.name ?? null, term: term?.name ?? null, children, announcements, messages,
      messagesNote: "Messages sent by the school about your children",
      libraryCatalogue: await loadCatalogue(),
      mealServices: mealServices.map((s) => ({ id: s.id, name: s.name, category: s.category, optInDaily: s.optInDaily, weekdays: s.weekdays })),
      mealRequests: mealRequests.map((r) => ({
        id: r.id, studentId: r.studentId, student: `${r.student.firstName} ${r.student.lastName}`,
        service: r.service.name, date: r.date, kind: r.kind, status: r.status,
        reason: r.reason, decidedByName: r.decidedByName, createdAt: r.createdAt,
      })),
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
        bookRequests: { include: { book: true }, orderBy: { createdAt: "desc" }, take: 30 },
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
        bookRequests: s.bookRequests.map(mapBookRequest),
        wallet: s.wallet ? { prepaid: s.wallet.prepaidCents, transport: s.wallet.transportCents } : null,
        feesBalance: balance,
      },
      announcements,
      libraryCatalogue: await loadCatalogue(),
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
    // wave 28 — the staff member's own attendance & duty record (read-only)
    const dutySince = new Date();
    dutySince.setUTCDate(dutySince.getUTCDate() - 30);
    const [myAttRows, myDutySlots, myLeaves] = await Promise.all([
      db.staffAttendance.findMany({ where: { staffId: st.id, date: { gte: dutySince } }, orderBy: { date: "desc" } }),
      db.dutyAssignment.findMany({ where: { staffId: st.id }, orderBy: { weekday: "asc" } }),
      db.leaveRequest.findMany({ where: { staffId: st.id } }),
    ]);
    const announcements = await db.announcement.findMany({ where: { schoolId }, orderBy: { postedAt: "desc" }, take: 6 });
    const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true, motto: true, town: true, region: true, phone: true } });
    const mask = (v?: string | null) => (v && v.length > 5 ? `${v.slice(0, 3)}${"•".repeat(v.length - 5)}${v.slice(-2)}` : v ? "••••" : null);
    return NextResponse.json({
      role: "STAFF", school, announcements,
      staffduty: {
        recent: myAttRows.slice(0, 14).map((r) => ({
          date: r.date.toISOString().slice(0, 10), weekday: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"][r.date.getUTCDay()],
          status: r.status, clockIn: r.clockIn?.toISOString() ?? null, clockOut: r.clockOut?.toISOString() ?? null, note: r.note,
        })),
        stats: attStats(myAttRows),
        duty: myDutySlots.map((d) => ({ weekday: d.weekday, duty: d.duty, note: d.note })),
        balances: leaveBalances(myLeaves.map((l) => ({ type: l.type, status: l.status, days: l.days }))),
      },
      me: {
        fullName: st.fullName, title: st.title, position: st.position, category: st.category,
        department: st.department, staffCode: st.staffCode, qualification: st.qualification,
        employmentType: st.employmentType, hiredAt: st.hiredAt, phone: st.phone, email: st.email,
        ssnitNumber: mask(st.ssnitNumber), accountNumber: mask(st.accountNumber), momoNumber: mask(st.momoNumber),
        smsNotify: st.smsNotify, // lesson-note SMS opt-out (self-service below)
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

// ── POST /api/school/[id]/ops/portal — self-service actions (book requests) ──
//    Learners and parents request library books for the learner's own record:
//    a student may only request for themself, a parent only for their linked
//    children. The librarian then accepts/rejects from the library module. ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
  if (user.schoolId !== schoolId && !(user.schoolIds ?? []).includes(schoolId)) {
    return NextResponse.json({ error: "Forbidden" }, { status: 403 });
  }
  const body = await req.json().catch(() => ({}));

  if (body.action === "book-request.create") {
    const { bookId, studentId, startAt, endAt } = body;
    // scoping: the requesting user may only request for their own learner record / their child
    const allowedStudentIds = user.role === "STUDENT" && user.studentId
      ? [user.studentId]
      : user.role === "PARENT"
        ? user.linkedStudentIds
        : [];
    if (!studentId || !allowedStudentIds.includes(studentId)) {
      return NextResponse.json({ error: "You can only request books for yourself or your own children" }, { status: 403 });
    }
    if (!bookId) return NextResponse.json({ error: "Choose a book from the catalogue" }, { status: 400 });
    const book = await db.libraryBook.findUnique({ where: { id: bookId } });
    if (!book || book.schoolId !== schoolId) return NextResponse.json({ error: "Book not found" }, { status: 404 });
    if (book.available < 1) {
      return NextResponse.json({ error: `No copies of "${book.title}" are on the shelf right now — pick another title or check back soon.` }, { status: 409 });
    }
    const start = new Date(startAt);
    const end = new Date(endAt);
    if (isNaN(start.getTime()) || isNaN(end.getTime())) return NextResponse.json({ error: "Pick both an issue date and a return date" }, { status: 400 });
    if (end <= start) return NextResponse.json({ error: "The return date must be after the issue date" }, { status: 400 });
    const duplicate = await db.bookRequest.findFirst({ where: { bookId, studentId, status: "PENDING" } });
    if (duplicate) return NextResponse.json({ error: `There is already a pending request for "${book.title}" — wait for the librarian's decision.` }, { status: 409 });
    const request = await db.bookRequest.create({ data: { schoolId, bookId, studentId, startAt: start, endAt: end } });
    return NextResponse.json({ request: { id: request.id, status: request.status } });
  }

  // ── meal opt-in / opt-out request from the parent portal (dining parity):
  //    a parent asks for their child to skip a meal (e.g. lunch) or join the
  //    optional breakfast on a given day — the school approves or declines. ──
  if (body.action === "meal-request.create") {
    if (user.role !== "PARENT") return NextResponse.json({ error: "Only parent accounts can raise meal requests" }, { status: 403 });
    const { studentId, serviceId, date, kind, reason } = body;
    if (!user.linkedStudentIds.includes(studentId)) {
      return NextResponse.json({ error: "You can only request meal changes for your own children" }, { status: 403 });
    }
    if (!serviceId || !date || !"OPT_OUT OPT_IN".includes(kind ?? "")) {
      return NextResponse.json({ error: "Choose the meal, the day and the request type" }, { status: 400 });
    }
    const svc = await db.dailyService.findUnique({ where: { id: serviceId } });
    if (!svc || svc.schoolId !== schoolId || !svc.active) return NextResponse.json({ error: "Meal service not found" }, { status: 404 });
    const kindValue = kind as string;
    if (kindValue === "OPT_IN" && !svc.optInDaily) {
      return NextResponse.json({ error: `${svc.name} is served to every enrolled learner by default — no opt-in needed.` }, { status: 400 });
    }
    if (kindValue === "OPT_OUT" && svc.optInDaily) {
      return NextResponse.json({ error: `${svc.name} is optional — your child is only billed when you opt in, so there is nothing to skip.` }, { status: 400 });
    }
    const d = new Date(date); d.setHours(0, 0, 0, 0);
    if (isNaN(d.getTime()) || d < new Date(new Date().toDateString())) {
      return NextResponse.json({ error: "Pick today or a future date" }, { status: 400 });
    }
    const dup = await db.dailyMealException.findFirst({ where: { studentId, serviceId, date: d, kind: kindValue, status: { in: ["PENDING", "APPROVED"] } } });
    if (dup) return NextResponse.json({ error: `There is already a ${dup.status.toLowerCase()} request for ${svc.name} on that day.` }, { status: 409 });
    const parent = await db.user.findUnique({ where: { id: user.id }, select: { fullName: true } });
    const request = await db.dailyMealException.create({
      data: { schoolId, studentId, serviceId, date: d, kind: kindValue, reason: reason ? String(reason).slice(0, 300) : null, requestedByName: parent?.fullName ?? "Parent" },
    });
    return NextResponse.json({ request: { id: request.id, status: request.status } });
  }

  // ── staff self-service: SMS lesson-note alert opt-in/opt-out
  //    (iedupro user_notification_preferences parity — a teacher manages their
  //    own preference; enabling requires a phone on the staff record) ──
  if (body.action === "staff.set-sms-preference") {
    if (!user.staffId) return NextResponse.json({ error: "This account is not linked to a staff record" }, { status: 403 });
    const st = await db.staff.findUnique({ where: { id: user.staffId } });
    if (!st || st.schoolId !== schoolId) return NextResponse.json({ error: "Staff record not found" }, { status: 404 });
    const enabled = !!body.enabled;
    if (enabled && !st.phone) {
      return NextResponse.json({ error: "No phone number on your staff record yet — ask the administrator to add one before enabling SMS alerts." }, { status: 400 });
    }
    const updated = await db.staff.update({ where: { id: st.id }, data: { smsNotify: enabled } });
    return NextResponse.json({ smsNotify: updated.smsNotify });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

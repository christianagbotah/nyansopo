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

    // wave 65 — homework for every linked child (the parent sees what was posted,
    // when it is due, and whether the learner has submitted / been graded).
    // Queried BEFORE the children loop — the loop maps these rows per learner.
    const homeworkRows = await db.homeworkSubmission.findMany({
      where: { studentId: { in: user.linkedStudentIds } },
      include: { homework: { include: { classroom: { select: { name: true } } } } },
      orderBy: { homework: { dueDate: "desc" } },
      take: 60,
    });
    const mapHw = (r: (typeof homeworkRows)[number]) => ({
      id: r.homework.id, title: r.homework.title, subject: r.homework.subjectName,
      classroom: r.homework.classroom.name, instructions: r.homework.instructions,
      dueDate: r.homework.dueDate, overdue: r.homework.dueDate < now, status: r.homework.status,
      maxScore: r.homework.maxScore, postedBy: r.homework.createdByName,
      myStatus: r.status, myNote: r.note, myScore: r.score, myFeedback: r.feedback,
    });

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
        homework: homeworkRows.filter((r) => r.studentId === s.id).map(mapHw),
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
        homework: (
          await db.homeworkSubmission.findMany({
            where: { studentId: s.id },
            include: { homework: { include: { classroom: { select: { name: true } } } } },
            orderBy: { homework: { dueDate: "desc" } },
            take: 40,
          })
        ).map((r) => ({
          id: r.homework.id, title: r.homework.title, subject: r.homework.subjectName,
          classroom: r.homework.classroom.name, instructions: r.homework.instructions,
          dueDate: r.homework.dueDate, overdue: r.homework.dueDate < now, status: r.homework.status,
          maxScore: r.homework.maxScore, postedBy: r.homework.createdByName,
          myStatus: r.status, myNote: r.note, myScore: r.score, myFeedback: r.feedback,
        })),
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

  // ─────────────────────────── MESSAGES (wave 40 — parent/learner side) ───────────────────────────
  //    Two-way conversations with the school office over the same MessageThread
  //    / Message tables the staff inbox uses — the bursar or head replies from
  //    the staff Messages module, the parent replies from here. Recipients are
  //    CURATED (head/bursar/class teachers of their children) — a parent never
  //    browses the staff directory, and learners reach their class teacher +
  //    the head only.
  if (view === "messages") {
    if (user.role !== "PARENT" && user.role !== "STUDENT") {
      return NextResponse.json({ error: "Portal messaging is for parent and learner accounts" }, { status: 403 });
    }
    const contacts = await allowedMessagingContacts(schoolId, user);
    const threads = await db.messageThread.findMany({
      where: { schoolId, OR: [{ senderId: user.id }, { recipientId: user.id }] },
      orderBy: { lastMessageAt: "desc" },
      include: { sender: true, recipient: true, messages: { orderBy: { createdAt: "desc" }, take: 1 } },
    });
    const ids = threads.map((t) => t.id);
    const unread = ids.length
      ? await db.message.groupBy({ by: ["threadId"], where: { threadId: { in: ids }, senderId: { not: user.id }, readAt: null }, _count: { _all: true } })
      : [];
    const unreadByThread = new Map(unread.map((u) => [u.threadId, u._count._all]));
    const shape = (u: { id: string; fullName: string; role: string; title: string | null }) => ({ id: u.id, fullName: u.fullName, role: u.role, title: u.title });
    const threadItems = threads.map((t) => {
      const counterpart = t.senderId === user.id ? t.recipient : t.sender;
      const last = t.messages[0] ?? null;
      return {
        id: t.id, subject: t.subject, counterpart: shape(counterpart),
        lastMessage: last ? { body: last.body.length > 90 ? `${last.body.slice(0, 90)}…` : last.body, senderId: last.senderId, createdAt: last.createdAt } : null,
        lastMessageAt: t.lastMessageAt, unreadCount: unreadByThread.get(t.id) ?? 0,
      };
    });

    // ?thread=<id> — the conversation (participants only, else 404)
    const threadParam = new URL(req.url).searchParams.get("thread");
    let thread: { id: string; subject: string; counterpart: ReturnType<typeof shape>; messages: { id: string; senderId: string; mine: boolean; senderName: string; body: string; readAt: Date | null; createdAt: Date }[] } | null = null;
    if (threadParam) {
      const t = await db.messageThread.findUnique({
        where: { id: threadParam },
        include: { sender: true, recipient: true, messages: { orderBy: { createdAt: "asc" } } },
      });
      if (t && t.schoolId === schoolId && (t.senderId === user.id || t.recipientId === user.id)) {
        const counterpart = t.senderId === user.id ? t.recipient : t.sender;
        const nameById: Record<string, string> = { [t.senderId]: t.sender.fullName, [t.recipientId]: t.recipient.fullName };
        thread = {
          id: t.id, subject: t.subject, counterpart: shape(counterpart),
          messages: t.messages.map((m) => ({ id: m.id, senderId: m.senderId, mine: m.senderId === user.id, senderName: m.senderId === user.id ? "You" : nameById[m.senderId] ?? "School", body: m.body, readAt: m.readAt, createdAt: m.createdAt })),
        };
      } else {
        return NextResponse.json({ error: "Thread not found" }, { status: 404 });
      }
    }

    return NextResponse.json({
      me: { id: user.id, fullName: user.fullName },
      contacts,
      threads: threadItems,
      unreadTotal: threadItems.reduce((n, t) => n + t.unreadCount, 0),
      ...(thread ? { thread } : {}),
    });
  }

  return NextResponse.json({ error: "Unknown view" }, { status: 400 });
}

/** The staff a parent/learner may message (wave 40): the head (SCHOOL_ADMIN
 *  with messages.use), the bursar (ACCOUNTANT), and — for parents — the class
 *  teachers of their linked children; for learners, their own class teacher.
 *  Curated on purpose: no staff-directory browsing from the portal. */
async function allowedMessagingContacts(schoolId: string, user: { role: string; linkedStudentIds: string[]; studentId: string | null }) {
  const staffUsers = await db.user.findMany({
    where: { schoolId, status: "ACTIVE", role: { in: ["SCHOOL_ADMIN", "ACCOUNTANT", "TEACHER"] } },
    select: { id: true, fullName: true, role: true, title: true, staffId: true },
  });
  const office = staffUsers.filter((u) => u.role !== "TEACHER"); // head + bursar — messaging staff
  // class-teacher picks: classrooms of the account's children (or own classroom)
  const learnerIds = user.role === "STUDENT" && user.studentId ? [user.studentId] : user.role === "PARENT" ? user.linkedStudentIds : [];
  const teacherIds: string[] = [];
  if (learnerIds.length) {
    const classrooms = await db.classroom.findMany({
      where: { schoolId, enrollments: { some: { studentId: { in: learnerIds }, status: "ACTIVE" } } },
      select: { classTeacherId: true },
    });
    const ctIds = [...new Set(classrooms.map((c) => c.classTeacherId).filter((x): x is string => !!x))];
    const teacherUsers = staffUsers.filter((u) => u.role === "TEACHER" && u.staffId && ctIds.includes(u.staffId));
    teacherIds.push(...teacherUsers.map((u) => u.id));
  }
  return staffUsers
    .filter((u) => office.some((o) => o.id === u.id) || teacherIds.includes(u.id))
    .map((u) => ({ id: u.id, fullName: u.fullName, role: u.role, title: u.title }));
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

  // ── wave 65 — homework self-submit from the learner/parent portal: the
  //    learner (or their parent) confirms the homework is done, with an
  //    optional note for the teacher. Late auto-flags against the due date. ──
  if (body.action === "homework.self-submit") {
    if (user.role !== "PARENT" && user.role !== "STUDENT") {
      return NextResponse.json({ error: "Only learner and parent accounts submit homework here" }, { status: 403 });
    }
    const allowedStudentIds = user.role === "STUDENT" && user.studentId ? [user.studentId] : user.role === "PARENT" ? user.linkedStudentIds : [];
    const { homeworkId, studentId, note } = body;
    if (!homeworkId || !studentId || !allowedStudentIds.includes(studentId)) {
      return NextResponse.json({ error: "You can only submit homework for yourself or your own children" }, { status: 403 });
    }
    const hw = await db.homework.findUnique({ where: { id: homeworkId } });
    if (!hw || hw.schoolId !== schoolId) return NextResponse.json({ error: "Assignment not found" }, { status: 404 });
    if (hw.status === "CLOSED") return NextResponse.json({ error: "This assignment is closed — talk to your teacher." }, { status: 409 });
    const sub = await db.homeworkSubmission.findUnique({ where: { homeworkId_studentId: { homeworkId, studentId } } });
    if (!sub) return NextResponse.json({ error: "No submission slot for this learner on this assignment" }, { status: 404 });
    if (sub.status === "GRADED") return NextResponse.json({ error: "Already graded — no further submission needed." }, { status: 409 });
    const late = new Date() > hw.dueDate;
    const updated = await db.homeworkSubmission.update({
      where: { id: sub.id },
      data: {
        status: late ? "LATE" : "SUBMITTED",
        submittedAt: new Date(),
        note: typeof note === "string" && note.trim() ? note.trim().slice(0, 500) : null,
      },
    });
    return NextResponse.json({ status: updated.status, submittedAt: updated.submittedAt });
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

  // ── wave 40 — portal messaging (parent/learner ⇄ school office) ──
  //    msg.send / msg.reply / msg.mark-read over the SAME MessageThread/Message
  //    tables the staff inbox uses, with the recipient list curated (see
  //    allowedMessagingContacts) so a parent can never address arbitrary staff.
  if (body.action === "msg.send" || body.action === "msg.reply" || body.action === "msg.mark-read") {
    if (user.role !== "PARENT" && user.role !== "STUDENT") {
      return NextResponse.json({ error: "Portal messaging is for parent and learner accounts" }, { status: 403 });
    }
    const MAX_SUBJECT = 120, MAX_BODY = 5000;

    if (body.action === "msg.send") {
      const { to, subject, body: text } = body;
      const contacts = await allowedMessagingContacts(schoolId, user);
      if (!contacts.some((c) => c.id === to)) {
        return NextResponse.json({ error: "That contact is not on your school-contact list" }, { status: 403 });
      }
      const subjectTrim = typeof subject === "string" ? subject.trim() : "";
      if (!subjectTrim || subjectTrim.length > MAX_SUBJECT) {
        return NextResponse.json({ error: `Subject is required (1–${MAX_SUBJECT} characters)` }, { status: 400 });
      }
      const textTrim = typeof text === "string" ? text.trim() : "";
      if (!textTrim || textTrim.length > MAX_BODY) {
        return NextResponse.json({ error: `Message body is required (1–${MAX_BODY} characters)` }, { status: 400 });
      }
      // one thread per unordered pair — either direction reuses the same thread
      let thread = await db.messageThread.findFirst({
        where: { schoolId, OR: [{ senderId: user.id, recipientId: to }, { senderId: to, recipientId: user.id }] },
      });
      if (!thread) {
        thread = await db.messageThread.create({
          data: { schoolId, subject: subjectTrim, senderId: user.id, recipientId: to, lastMessageAt: new Date() },
        });
      }
      const message = await db.message.create({ data: { threadId: thread.id, senderId: user.id, body: textTrim } });
      await db.messageThread.update({ where: { id: thread.id }, data: { lastMessageAt: new Date() } });
      return NextResponse.json({ threadId: thread.id, messageId: message.id });
    }

    if (body.action === "msg.reply") {
      const thread = typeof body.threadId === "string" ? await db.messageThread.findUnique({ where: { id: body.threadId } }) : null;
      if (!thread || thread.schoolId !== schoolId || (thread.senderId !== user.id && thread.recipientId !== user.id)) {
        return NextResponse.json({ error: "Thread not found" }, { status: 404 });
      }
      const textTrim = typeof body.body === "string" ? body.body.trim() : "";
      if (!textTrim || textTrim.length > MAX_BODY) {
        return NextResponse.json({ error: `Message body is required (1–${MAX_BODY} characters)` }, { status: 400 });
      }
      const message = await db.message.create({ data: { threadId: thread.id, senderId: user.id, body: textTrim } });
      await db.messageThread.update({ where: { id: thread.id }, data: { lastMessageAt: new Date() } });
      return NextResponse.json({ messageId: message.id });
    }

    // msg.mark-read
    const thread = typeof body.threadId === "string" ? await db.messageThread.findUnique({ where: { id: body.threadId } }) : null;
    if (!thread || thread.schoolId !== schoolId || (thread.senderId !== user.id && thread.recipientId !== user.id)) {
      return NextResponse.json({ error: "Thread not found" }, { status: 404 });
    }
    const r = await db.message.updateMany({
      where: { threadId: thread.id, senderId: { not: user.id }, readAt: null },
      data: { readAt: new Date() },
    });
    return NextResponse.json({ updated: r.count });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

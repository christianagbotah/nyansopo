import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser, canOperateSchool } from "@/lib/session";
import { canAny } from "@/lib/rbac";
import { gradeFor } from "@/lib/grading";
import { sendSms, sendAutomated } from "@/lib/sms";
import { invoicePaymentReceipt } from "@/lib/receipt-payload";
import { nextInvoiceNo, nextReceiptNo, nextStudentIndex } from "@/lib/codes";
import { effectiveValue } from "@/lib/discounts";
import { storeMedia } from "@/lib/media";
import { isCurriculumKey } from "@/lib/curricula";
import { assertResultsComputable, assertResultsEditable } from "@/lib/result-approval";

const C = (cedis: number) => Math.round(cedis * 100);

// ── GET /api/school/[id]/[resource] ───────────────────────────
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string; resource: string }> }) {
  const { id: schoolId, resource } = await params;
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
  const school = await db.school.findUnique({ where: { id: schoolId }, include: { associationNode: true, group: true } });
  if (!school) return NextResponse.json({ error: "School not found" }, { status: 404 });
  const allowed = canOperateSchool(user, schoolId) || user.nodeIds.includes(school.associationNodeId);
  if (!allowed) return NextResponse.json({ error: "Forbidden" }, { status: 403 });

  const url = new URL(req.url);
  const q = (k: string) => url.searchParams.get(k) ?? undefined;

  switch (resource) {
    case "dashboard": {
      const year = await db.academicYear.findFirst({ where: { schoolId, isCurrent: true }, include: { terms: true } });
      const term = year?.terms.find((t) => t.isCurrent) ?? null;
      const enrollments = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, include: { classroom: true, student: true } });
      const invoices = await db.invoice.findMany({ where: { schoolId, termId: term?.id } });
      const billed = invoices.reduce((s, i) => s + i.totalCents - i.discountCents, 0);
      const collected = invoices.reduce((s, i) => s + i.paidCents, 0);
      const arrears = billed - collected;
      const flags = await db.studentFlag.findMany({ where: { schoolId, status: "ACTIVE" }, include: { student: true } });
      const recentPayments = await db.payment.findMany({ where: { schoolId, voided: false }, orderBy: { paidAt: "desc" }, take: 8, include: { invoice: { include: { student: true } }, student: true } });
      const pendingRequests = await db.recordRequest.count({ where: { targetSchoolId: schoolId, status: "PENDING" } });
      const inquiries = await db.admissionInquiry.findMany({ where: { schoolId }, orderBy: { createdAt: "desc" }, take: 6 });
      // attendance trend (last 8 sessions)
      const sessions = await db.attendanceSession.findMany({ where: { schoolId }, orderBy: { date: "desc" }, take: 12, include: { records: true } });
      const trend = sessions.slice(0, 8).reverse().map((s) => {
        const present = s.records.filter((r) => r.status === "PRESENT" || r.status === "LATE").length;
        return { date: s.date.toISOString().slice(0, 10), rate: s.records.length ? Math.round((present / s.records.length) * 100) : 0 };
      });
      // arrears by class
      const byClass: Record<string, { billed: number; collected: number; arrears: number; students: number }> = {};
      for (const e of enrollments) {
        const cn = e.classroom.name;
        byClass[cn] ??= { billed: 0, collected: 0, arrears: 0, students: 0 };
        byClass[cn].students++;
      }
      for (const inv of invoices) {
        const cn = inv.classroomName ?? "—";
        byClass[cn] ??= { billed: 0, collected: 0, arrears: 0, students: 0 };
        byClass[cn].billed += inv.totalCents - inv.discountCents;
        byClass[cn].collected += inv.paidCents;
      }
      Object.values(byClass).forEach((c) => (c.arrears = c.billed - c.collected));
      const riskHigh = await db.riskScore.count({ where: { schoolId, level: { in: ["HIGH", "CRITICAL"] } } });
      const staffCount = await db.staff.count({ where: { schoolId } });
      /* CI3 dashboard parity — key-metric links + financial summary numbers */
      const teacherCount = await db.staff.count({ where: { schoolId, category: "TEACHING" } });
      const parentCount = await db.user.count({ where: { schoolId, role: "PARENT" } });
      const unpaidCount = invoices.filter((i) => i.status === "UNPAID" || i.status === "OVERDUE" || i.status === "PARTIAL").length;
      const expensesTerm = term ? await db.expense.aggregate({ where: { schoolId, expenseDate: { gte: term.startsAt, lte: term.endsAt } }, _sum: { amountCents: true } }) : null;
      const gender = { M: enrollments.filter((e) => e.student.gender === "M").length, F: enrollments.filter((e) => e.student.gender === "F").length };
      return NextResponse.json({
        school: { ...school, group: school.group ? { id: school.group.id, name: school.group.name } : null },
        year: year ? { id: year.id, name: year.name } : null,
        term: term ? { id: term.id, name: term.name, startsAt: term.startsAt, endsAt: term.endsAt } : null,
        stats: {
          students: enrollments.length, gender, staffCount, classes: Object.keys(byClass).length,
          billed, collected, arrears,
          collectionRate: billed ? Math.round((collected / billed) * 100) : 0,
          flagged: flags.length, riskHigh, pendingRequests,
          attendanceRate: trend.length ? Math.round(trend.reduce((s, t) => s + t.rate, 0) / trend.length) : null,
          teacherCount, parentCount, unpaidCount, expensesTerm: expensesTerm?._sum?.amountCents ?? 0,
        },
        recentPayments: recentPayments.map((p) => ({ id: p.id, receiptNo: p.receiptNo, amount: p.amountCents, method: p.method, paidAt: p.paidAt, category: p.category, student: p.invoice?.student ? `${p.invoice.student.firstName} ${p.invoice.student.lastName}` : p.student ? `${p.student.firstName} ${p.student.lastName}` : "—" })),
        arrearsByClass: Object.entries(byClass).map(([name, v]) => ({ name, ...v })).sort((a, b) => b.arrears - a.arrears),
        attendanceTrend: trend,
        flags: flags.map((f) => ({ id: f.id, type: f.type, severity: f.severity, status: f.status, amountOwed: f.amountOwedCents, student: `${f.student.firstName} ${f.student.lastName}`, raisedAt: f.raisedAt, description: f.description })),
        inquiries,
      });
    }

    case "students": {
      const search = (q("search") ?? "").toLowerCase();
      const classId = q("classId");
      const enrollments = await db.enrollment.findMany({
        where: { schoolId, status: q("status") === "all" ? undefined : q("status") ?? "ACTIVE", ...(classId ? { classroomId: classId } : {}) },
        include: { student: { include: { flags: { where: { status: { in: ["ACTIVE", "CONTESTED"] } } }, invoices: { where: { termId: q("termId") ?? undefined } }, riskScores: { orderBy: { computedAt: "desc" }, take: 1 } } }, classroom: true },
        orderBy: { rollNo: "asc" },
      });
      const termId = q("termId");
      const curTerm = termId ? null : await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
      const tid = termId ?? curTerm?.id;
      let list = enrollments;
      if (search) list = list.filter((e) => `${e.student.firstName} ${e.student.lastName} ${e.student.otherNames ?? ""} ${e.student.nyansapoIndex} ${e.student.guardianName} ${e.student.guardianPhone}`.toLowerCase().includes(search));
      const invoicesAll = await db.invoice.findMany({ where: { schoolId, termId: tid } });
      const invByStudent: Record<string, { billed: number; paid: number; status: string; invoiceNo: string; id: string }> = {};
      invoicesAll.forEach((i) => (invByStudent[i.studentId] = { billed: i.totalCents - i.discountCents, paid: i.paidCents, status: i.status, invoiceNo: i.invoiceNo, id: i.id }));
      return NextResponse.json({
        termId: tid,
        students: list.map((e) => {
          const inv = invByStudent[e.studentId];
          return {
            id: e.student.id, nyansapoIndex: e.student.nyansapoIndex, name: `${e.student.firstName} ${e.student.lastName}`,
            otherNames: e.student.otherNames, gender: e.student.gender, dob: e.student.dateOfBirth,
            classroom: e.classroom.name, classroomId: e.classroomId, rollNo: e.rollNo,
            guardianName: e.student.guardianName, guardianPhone: e.student.guardianPhone, relationship: e.student.relationship,
            admissionDate: e.admissionDate, status: e.status,
            dietNote: e.student.dietNote,
            promotedFrom: e.promotedFromName, promotedAt: e.promotedAt,
            invoice: inv ?? null,
            flags: e.student.flags.map((f) => ({ id: f.id, type: f.type, severity: f.severity, status: f.status, amountOwed: f.amountOwedCents, raisedAt: f.raisedAt, description: f.description, raisedByName: f.raisedByName, schoolId: f.schoolId })),
            risk: e.student.riskScores[0] ? { score: e.student.riskScores[0].score, level: e.student.riskScores[0].level } : null,
          };
        }),
      });
    }

    case "fees": {
      // defense in depth: the fee ledger is money-side — classroom roles (teachers)
      // never see it even if the request is hand-crafted
      if (!canAny(user.permissions, ["fees.view", "discounts.manage"]))
        return NextResponse.json({ error: "Forbidden" }, { status: 403 });
      const year = await db.academicYear.findFirst({ where: { schoolId, isCurrent: true }, include: { terms: { include: { academicYear: true } } } });
      const allYears = await db.academicYear.findMany({ where: { schoolId }, include: { terms: true } });
      const termId = q("termId") ?? year?.terms.find((t) => t.isCurrent)?.id;
      const structures = await db.feeStructure.findMany({ where: { schoolId, termId }, include: { classroom: true, items: true } });
      const invoices = await db.invoice.findMany({
        where: { schoolId, termId },
        include: { student: true, payments: { where: { voided: false }, orderBy: { paidAt: "desc" } } },
        orderBy: { invoiceNo: "asc" },
      });
      const billed = invoices.reduce((s, i) => s + i.totalCents - i.discountCents, 0);
      const collected = invoices.reduce((s, i) => s + i.paidCents, 0);
      /* CI3 bill_item catalog (student_payment.php “Add Billing Item”) + the
         class roster that feeds the Create-Invoice wizard's student picker */
      const billItems = await db.billItem.findMany({ where: { schoolId }, orderBy: [{ sortOrder: "asc" }, { title: "asc" }] });
      const classrooms = await db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" }, select: { id: true, name: true, gradeLevel: true, stageLabel: true } });
      const roster = await db.enrollment.findMany({
        where: { schoolId, status: "ACTIVE" },
        include: { student: { select: { id: true, firstName: true, lastName: true, nyansapoIndex: true, guardianName: true, guardianPhone: true } }, classroom: { select: { id: true, name: true, gradeLevel: true } } },
        orderBy: { rollNo: "asc" },
      });
      return NextResponse.json({
        terms: allYears.flatMap((y) => y.terms.map((t) => ({ id: t.id, name: `${t.name} ${y.name}`, isCurrent: t.isCurrent }))),
        termId,
        classrooms,
        billItems: billItems.map((b) => ({ id: b.id, title: b.title, category: b.category, classCategory: b.classCategory, specificClassIds: (b.specificClassIds as string[] | null) ?? [], amountCents: b.amountCents, description: b.description, active: b.active, sortOrder: b.sortOrder })),
        roster: roster.map((e) => ({ id: e.student.id, name: `${e.student.firstName} ${e.student.lastName}`, index: e.student.nyansapoIndex, classroomId: e.classroomId, classroom: e.classroom.name, grade: e.classroom.gradeLevel, guardian: e.student.guardianName, phone: e.student.guardianPhone, residence: e.residenceType, muted: false })),
        structures: structures.map((s) => ({ id: s.id, classroom: s.classroom.name, classroomId: s.classroomId, total: s.totalCents, items: s.items.map((i) => ({ name: i.name, amount: i.amountCents, optional: i.isOptional })) })),
        invoices: invoices.map((i) => ({
          id: i.id, invoiceNo: i.invoiceNo, student: `${i.student.firstName} ${i.student.lastName}`, studentId: i.studentId,
          classroom: i.classroomName, total: i.totalCents, discount: i.discountCents, discountReason: i.discountReason,
          paid: i.paidCents, balance: i.totalCents - i.discountCents - i.paidCents, status: i.status, dueDate: i.dueDate,
          guardianPhone: i.student.guardianPhone,
          payments: i.payments.map((p) => ({ id: p.id, receiptNo: p.receiptNo, amount: p.amountCents, method: p.method, paidAt: p.paidAt, reference: p.reference })),
        })),
        stats: { billed, collected, arrears: billed - collected, count: invoices.length, paid: invoices.filter((i) => i.status === "PAID").length, partial: invoices.filter((i) => i.status === "PARTIAL").length, unpaid: invoices.filter((i) => i.status === "UNPAID" || i.status === "OVERDUE").length },
      });
    }

    case "attendance": {
      const classId = q("classId");
      const dateStr = q("date") ?? new Date().toISOString().slice(0, 10);
      const date = new Date(dateStr);
      const classrooms = await db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
      if (!classId) return NextResponse.json({ classrooms: classrooms.map((c) => ({ id: c.id, name: c.name, grade: c.gradeLevel, stage: c.stageLabel })), session: null, date: dateStr, termId: null, students: [] });
      const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
      let session = await db.attendanceSession.findFirst({ where: { classroomId: classId, date }, include: { records: { include: { student: true } } } });
      if (!session) {
        const enrolled = await db.enrollment.findMany({ where: { classroomId: classId, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" } });
        return NextResponse.json({
          classrooms: classrooms.map((c) => ({ id: c.id, name: c.name, grade: c.gradeLevel, stage: c.stageLabel })),
          session: null, date: dateStr, termId: term?.id,
          students: enrolled.map((e) => ({ id: e.student.id, name: `${e.student.firstName} ${e.student.lastName}`, rollNo: e.rollNo, status: null })),
        });
      }
      return NextResponse.json({
        classrooms: classrooms.map((c) => ({ id: c.id, name: c.name, grade: c.gradeLevel, stage: c.stageLabel })),
        session: { id: session.id, takenByName: session.takenByName },
        date: dateStr, termId: session.termId,
        students: session.records.map((r) => ({ id: r.student.id, name: `${r.student.firstName} ${r.student.lastName}`, status: r.status, markedOffline: r.markedOffline })).sort((a, b) => a.name.localeCompare(b.name)),
      });
    }

    case "gradebook": {
      const classId = q("classId");
      const classrooms = await db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
      const subjects = await db.subject.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
      const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
      if (!classId) return NextResponse.json({ classrooms: classrooms.map((c) => ({ id: c.id, name: c.name })), subjects: subjects.map((s) => ({ id: s.id, name: s.name, code: s.code })), termId: term?.id, classroom: null, students: [], assessments: [] });
      const cls = classrooms.find((c) => c.id === classId);
      const enrolled = await db.enrollment.findMany({ where: { classroomId: classId, status: "ACTIVE" }, include: { student: true }, orderBy: { rollNo: "asc" } });
      const assessments = await db.assessment.findMany({ where: { classroomId: classId, termId: term?.id }, include: { subject: true, marks: true }, orderBy: [{ date: "asc" }] });
      return NextResponse.json({
        classrooms: classrooms.map((c) => ({ id: c.id, name: c.name })),
        classroom: cls ? { id: cls.id, name: cls.name, stage: cls.stageLabel } : null,
        subjects: subjects.map((s) => ({ id: s.id, name: s.name, code: s.code })),
        termId: term?.id, termName: term?.name,
        students: enrolled.map((e) => ({ id: e.student.id, name: `${e.student.firstName} ${e.student.lastName}`, rollNo: e.rollNo })),
        assessments: assessments.map((a) => ({
          id: a.id, title: a.title, type: a.type, maxScore: a.maxScore, date: a.date,
          subject: a.subject.name, subjectId: a.subjectId,
          marks: Object.fromEntries(a.marks.map((m) => [m.studentId, m.score])),
        })),
      });
    }

    case "reports": {
      const termId = q("termId");
      const years = await db.academicYear.findMany({ where: { schoolId }, include: { terms: true } }, );
      const allTerms = years.flatMap((y) => y.terms.map((t) => ({ id: t.id, name: `${t.name} — ${y.name}`, isCurrent: t.isCurrent })));
      const tid = termId ?? allTerms.find((t) => t.isCurrent)?.id;
      const classrooms = await db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
      const cards = await db.reportCard.findMany({ where: { termId: tid }, include: { student: true, results: true }, orderBy: { classPosition: "asc" } });
      return NextResponse.json({
        terms: allTerms, termId: tid,
        classrooms: classrooms.map((c) => ({ id: c.id, name: c.name })),
        reportCards: cards.map((rc) => ({
          id: rc.id, student: `${rc.student.firstName} ${rc.student.lastName}`, studentId: rc.studentId,
          classroom: rc.classroomName, average: rc.average, position: rc.classPosition, classSize: rc.classSize,
          conduct: rc.conduct, interest: rc.interest, promotion: rc.promotion, published: rc.published,
          teacherRemark: rc.teacherRemark, headRemark: rc.headRemark, aiGenerated: rc.aiGenerated,
          attendance: rc.attendance, subjectCount: rc.results.length,
        })),
      });
    }

    case "settings": {
      const years = await db.academicYear.findMany({ where: { schoolId }, include: { terms: true } });
      const classrooms = await db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" } });
      const subjects = await db.subject.findMany({ where: { schoolId } });
      const staff = await db.staff.findMany({ where: { schoolId } });
      const remarkTemplates = await db.remarkTemplate.findMany({ where: { schoolId }, orderBy: [{ category: "asc" }, { minPercent: "asc" }] });
      const conductItems = await db.conductItem.findMany({ where: { schoolId }, orderBy: [{ kind: "asc" }, { sortOrder: "asc" }] });
      // branding & receipts preferences (SchoolSetting JSON values)
      const prefSettings = await db.schoolSetting.findMany({ where: { schoolId, key: { in: ["branding.logoUrl", "branding.signatureUrl", "branding.ssnitLogoUrl", "receipts.style"] } } });
      const daysOpened = await db.schoolSetting.findMany({ where: { schoolId, key: { startsWith: "term.daysOpened:" } } });
      // CI3 system_settings_modern General/Payment/Security tabs (system.* KV)
      const systemSettings = await db.schoolSetting.findMany({ where: { schoolId, key: { startsWith: "system." } } });
      // CI3 app_theme / theme_primary parity (school branding colours)
      const themeRow = await db.schoolSetting.findUnique({ where: { schoolId_key: { schoolId, key: "theme" } } });
      let theme: unknown = null;
      try { theme = themeRow ? JSON.parse(themeRow.value) : null; } catch { theme = null; }
      const sys: Record<string, any> = {};
      for (const row of systemSettings) {
        try { sys[row.key.slice("system.".length)] = JSON.parse(row.value); } catch { sys[row.key.slice("system.".length)] = row.value; }
      }
      // statutory rates (OpsSetting — shared with payroll, CI3 payroll_statutory_settings)
      const rateRows = await db.opsSetting.findMany({ where: { schoolId, key: { in: ["SSNIT_TIER1_EMPLOYEE", "SSNIT_TIER1_EMPLOYER", "SSNIT_TIER2", "GETFUND", "NHIL"] } } });
      const rateMap = Object.fromEntries(rateRows.map((r) => [r.key, parseFloat(r.value)]));
      const pref = (k: string) => {
        const raw = prefSettings.find((r) => r.key === k)?.value;
        try { return raw ? JSON.parse(raw) : null; } catch { return null; }
      };
      const daysMap: Record<string, number> = {};
      for (const d of daysOpened) {
        const v = Number(JSON.parse(d.value ?? "0"));
        if (Number.isFinite(v)) daysMap[d.key.slice("term.daysOpened:".length)] = v;
      }
      return NextResponse.json({
        school: { ...school, group: school.group ? { id: school.group.id, name: school.group.name, proprietorName: school.group.proprietorName } : null },
        years: years.map((y) => ({ id: y.id, name: y.name, isCurrent: y.isCurrent, terms: y.terms.map((t) => ({ id: t.id, name: t.name, startsAt: t.startsAt, endsAt: t.endsAt, isCurrent: t.isCurrent })) })),
        classrooms: classrooms.map((c) => ({ id: c.id, name: c.name, grade: c.gradeLevel, stage: c.stageLabel, capacity: c.capacity })),
        subjects: subjects.map((s) => ({ id: s.id, name: s.name, code: s.code })),
        staff: staff.map((s) => ({ id: s.id, fullName: s.fullName, role: s.role, title: s.title })),
        remarkTemplates: remarkTemplates.map((t) => ({ id: t.id, category: t.category, minPercent: t.minPercent, maxPercent: t.maxPercent, text: t.text, active: t.active })),
        conductItems: conductItems.map((c) => ({ id: c.id, name: c.name, kind: c.kind, active: c.active })),
        branding: { logoUrl: pref("branding.logoUrl"), signatureUrl: pref("branding.signatureUrl"), ssnitLogoUrl: pref("branding.ssnitLogoUrl"), receiptStyle: pref("receipts.style") ?? "A5" },
        theme,
        daysOpened: daysMap,
        system: sys,
        statutory: {
          ssnitTier1Employee: rateMap.SSNIT_TIER1_EMPLOYEE ?? 5,
          ssnitTier1Employer: rateMap.SSNIT_TIER1_EMPLOYER ?? 13,
          ssnitTier2: rateMap.SSNIT_TIER2 ?? 5,
          getfund: rateMap.GETFUND ?? 2.5,
          nhil: rateMap.NHIL ?? 2.5,
        },
      });
    }

    case "announcements": {
      const ann = await db.announcement.findMany({
        where: { OR: [{ schoolId }, { associationNodeId: { in: [school.associationNodeId, ...(await siblingNodeIds(school.associationNodeId))] } }] },
        orderBy: { postedAt: "desc" }, take: 20, include: { school: true, associationNode: true },
      });
      return NextResponse.json({ announcements: ann.map((a) => ({ id: a.id, title: a.title, body: a.body, audience: a.audience, category: a.category, priority: a.priority, postedByName: a.postedByName, postedAt: a.postedAt, source: a.school?.name ?? a.associationNode?.name ?? "Association" })) });
    }

    default:
      return NextResponse.json({ error: "Unknown resource" }, { status: 404 });
  }
}

async function siblingNodeIds(nodeId: string): Promise<string[]> {
  const all = await db.associationNode.findMany();
  const out: string[] = [];
  let cur = all.find((n) => n.id === nodeId);
  while (cur?.parentId) {
    out.push(cur.parentId);
    cur = all.find((n) => n.id === cur!.parentId);
  }
  return out;
}

// ── POST /api/school/[id]/[resource] ──────────────────────────
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string; resource: string }> }) {
  const { id: schoolId, resource } = await params;
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
  if (!canOperateSchool(user, schoolId)) return NextResponse.json({ error: "Forbidden — you cannot operate this school" }, { status: 403 });
  const school = await db.school.findUnique({ where: { id: schoolId } });
  if (!school) return NextResponse.json({ error: "School not found" }, { status: 404 });
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;

  // ── RBAC per-resource permission gate (any-of) ──
  const GATE: Record<string, string[]> = {
    fees: ["record-payment", "receipt-payloads"].includes(action) ? ["fees.collect", "fees.manage"] : ["fees.manage"],
    attendance: ["attendance.take"],
    gradebook: ["gradebook.enter"],
    reports: ["reports.publish"],
    students: ["students.manage"],
    settings: ["settings.manage"],
    announcements: ["sms.send"],
  };
  const gate = GATE[resource];
  if (gate && !user.permissions.some((p) => gate.includes(p))) {
    return NextResponse.json({ error: "Forbidden — your role does not include permission for this action" }, { status: 403 });
  }

  switch (resource) {
    // ── FEES ──────────────────────────────────────────────
    case "fees": {
      // ── BILL ITEM CATALOG (CI3 student_payment.php — Add Billing Item tab) ──
      // single create/update; title unique per school; amount in pesewas
      if (action === "bill-item-save") {
        const { id, title, category, classCategory, specificClassIds, amountCents, description } = body as {
          id?: string; title?: string; category?: string; classCategory?: string; specificClassIds?: string[]; amountCents?: number; description?: string;
        };
        const t = (title ?? "").trim();
        const amt = Math.round(Number(amountCents));
        if (!t) return NextResponse.json({ error: "Bill item title is required" }, { status: 400 });
        if (!Number.isFinite(amt) || amt <= 0) return NextResponse.json({ error: "Amount must be greater than zero" }, { status: 400 });
        const dup = await db.billItem.findFirst({ where: { schoolId, title: t, ...(id ? { NOT: { id } } : {}) } });
        if (dup) return NextResponse.json({ error: `A bill item titled “${t}” already exists` }, { status: 400 });
        const data = {
          title: t.toUpperCase(),
          category: (category ?? "General").trim() || "General",
          classCategory: classCategory ?? "ALL",
          specificClassIds: Array.isArray(specificClassIds) ? specificClassIds.filter(Boolean) : [],
          amountCents: amt,
          description: (description ?? "").trim() || null,
        };
        const row = id
          ? await db.billItem.update({ where: { id }, data })
          : await db.billItem.create({ data: { ...data, schoolId } });
        return NextResponse.json({ billItem: row, updated: !!id });
      }
      // bulk inline edit (CI3 bulk_save_bill_items): updates[] carries every edited row
      if (action === "bill-items-bulk-save") {
        const updates: any[] = Array.isArray(body.updates) ? body.updates : [];
        if (!updates.length) return NextResponse.json({ error: "No changes to save" }, { status: 400 });
        let saved = 0;
        for (const u of updates) {
          const id = String(u.id ?? "");
          const amt = Math.round(Number(u.amountCents));
          if (!id || !Number.isFinite(amt) || amt <= 0) continue;
          await db.billItem.update({
            where: { id },
            data: {
              ...(u.title ? { title: String(u.title).trim().toUpperCase() } : {}),
              ...(u.category ? { category: String(u.category) } : {}),
              ...(u.classCategory ? { classCategory: String(u.classCategory) } : {}),
              specificClassIds: Array.isArray(u.specificClassIds) ? u.specificClassIds.filter(Boolean) : [],
              amountCents: amt,
              ...(u.description !== undefined ? { description: String(u.description).trim() || null } : {}),
            },
          }).catch(() => null);
          saved++;
        }
        return NextResponse.json({ saved });
      }
      // delete one / many — refuses rows already billed onto invoices (CI3 parity)
      if (action === "bill-item-delete" || action === "bill-items-bulk-delete") {
        const ids: string[] = action === "bill-item-delete" ? [String(body.id ?? "")] : (Array.isArray(body.ids) ? body.ids.map(String) : []);
        if (!ids.length || ids.includes("")) return NextResponse.json({ error: "Select the bill item(s) to delete" }, { status: 400 });
        const items = await db.billItem.findMany({ where: { schoolId, id: { in: ids } } });
        if (!items.length) return NextResponse.json({ error: "Bill item not found" }, { status: 404 });
        const titles = items.map((i) => i.title);
        const used = await db.invoiceItem.count({ where: { invoice: { schoolId }, name: { in: titles } } });
        if (used > 0) return NextResponse.json({ error: `${used} invoice line${used > 1 ? "s" : ""} reference ${titles.length > 1 ? "these items" : `“${titles[0]}”`} — invoices keep the amount they were billed with, so the item cannot be deleted` }, { status: 400 });
        await db.billItem.deleteMany({ where: { schoolId, id: { in: ids } } });
        return NextResponse.json({ deleted: ids.length });
      }
      // ── MASS INVOICE CREATE (CI3 mass_invoice_create — Create Invoice tab) ──
      // one invoice per student for the chosen items; discounts + prepaid
      // credits auto-apply exactly like generate-invoices. Students that
      // already hold an invoice this term get the missing items appended
      // (CI3 updates the existing bill instead of double-billing).
      if (action === "mass-invoice-create") {
        const { studentIds, items, termId, dueInDays } = body as { studentIds?: string[]; items?: { title: string; amountCents: number; description?: string }[]; termId?: string; dueInDays?: number };
        const ids = Array.isArray(studentIds) ? [...new Set(studentIds.map(String))] : [];
        const rows = (Array.isArray(items) ? items : []).filter((r) => r && String(r.title ?? "").trim() && Number.isFinite(Number(r.amountCents)) && Number(r.amountCents) > 0);
        if (!ids.length) return NextResponse.json({ error: "Select at least one learner" }, { status: 400 });
        if (!rows.length) return NextResponse.json({ error: "Add at least one billing item with an amount" }, { status: 400 });
        const term = termId
          ? await db.term.findFirst({ where: { id: termId, academicYear: { schoolId } } })
          : await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
        if (!term) return NextResponse.json({ error: "No billing term found" }, { status: 400 });
        const enrollments = await db.enrollment.findMany({
          where: { schoolId, studentId: { in: ids }, status: "ACTIVE" },
          include: { classroom: { select: { name: true } }, student: { select: { id: true } } },
        });
        if (!enrollments.length) return NextResponse.json({ error: "None of the selected learners are actively enrolled" }, { status: 400 });
        const existingInvoices = await db.invoice.findMany({ where: { schoolId, termId: term.id, studentId: { in: enrollments.map((e) => e.studentId) } }, include: { items: true } });
        const byStudent = new Map(existingInvoices.map((i) => [i.studentId, i]));
        // active invoice-scope discount applications (auto-apply catalog + approved profiles)
        const autoApps = await db.discountApplication.findMany({
          where: {
            schoolId, status: "ACTIVE",
            OR: [
              { discount: { scope: "INVOICE", autoApply: true, active: true } },
              { profile: { category: "INVOICE", active: true } },
            ],
          },
          include: { discount: true, profile: { include: { rules: true } } },
        });
        const appByStudent: Record<string, typeof autoApps> = {};
        autoApps.forEach((a) => (appByStudent[a.studentId] ??= []).push(a));
        const dueDate = new Date(Date.now() + Math.max(0, Math.min(120, Number(dueInDays ?? 14))) * 864e5);
        let created = 0, updated = 0, skipped = 0, discounted = 0, creditsApplied = 0;
        let totalBilled = 0;
        for (const e of enrollments) {
          const have = byStudent.get(e.studentId);
          // applicability filter: items may be class-scoped via the catalog, but
          // the wizard already filtered; here rows are explicit user choices
          if (have) {
            const existingTitles = new Set(have.items.map((i) => i.name));
            const missing = rows.filter((r) => !existingTitles.has(r.title.toUpperCase()));
            if (!missing.length) { skipped++; continue; }
            for (const r of missing) await db.invoiceItem.create({ data: { invoiceId: have.id, name: r.title.toUpperCase(), amountCents: Math.round(Number(r.amountCents)) } });
            const added = missing.reduce((s, r) => s + Math.round(Number(r.amountCents)), 0);
            await db.invoice.update({ where: { id: have.id }, data: { totalCents: have.totalCents + added, status: have.paidCents >= have.totalCents + added - have.discountCents ? have.status : have.status === "PAID" ? "PARTIAL" : have.status } });
            updated++;
            totalBilled += added;
            continue;
          }
          const total = rows.reduce((s, r) => s + Math.round(Number(r.amountCents)), 0);
          const inv = await db.invoice.create({ data: { invoiceNo: await nextInvoiceNo(), studentId: e.studentId, schoolId, termId: term.id, classroomName: e.classroom.name, totalCents: total, status: "UNPAID", dueDate } });
          for (const r of rows) await db.invoiceItem.create({ data: { invoiceId: inv.id, name: r.title.toUpperCase(), amountCents: Math.round(Number(r.amountCents)) } });
          // discounts: percentages stack (floor 100%), fixed amounts subtract
          const apps = appByStudent[e.studentId] ?? [];
          let discountTotal = 0;
          if (apps.length) {
            let pct = 0, fixed = 0;
            const names: string[] = [];
            for (const a of apps) {
              const src = a.discountId
                ? { kind: "discount" as const, id: a.discountId, name: a.discount!.name, type: a.discount!.type, value: a.discount!.value, scope: a.discount!.scope }
                : { kind: "profile" as const, id: a.profileId!, name: a.profile!.name, type: a.profile!.method, value: a.profile!.value, category: a.profile!.category };
              const eff = await effectiveValue(src, e.studentId, e.classroom.name);
              const v = a.valueOverride ?? eff.value;
              if ((a.valueOverride ? src.type === "PERCENT" : eff.type === "PERCENT")) pct += v;
              else fixed += v;
              names.push(src.name);
            }
            const amount = Math.min(total, Math.round((total * Math.min(100, pct)) / 100) + Math.min(total, fixed));
            if (amount > 0) {
              await db.invoice.update({ where: { id: inv.id }, data: { discountCents: amount, discountReason: names.join(" + ") } });
              discountTotal = amount;
              discounted++;
            }
          }
          // prepaid credit auto-apply (CI3 parity at billing time)
          const creditAgg = await db.studentCredit.aggregate({ where: { schoolId, studentId: e.studentId }, _sum: { amountCents: true } });
          const creditBal = creditAgg._sum.amountCents ?? 0;
          if (creditBal > 0) {
            const due = total - discountTotal;
            const applied = Math.min(creditBal, due);
            if (applied > 0) {
              await db.studentCredit.create({ data: { schoolId, studentId: e.studentId, kind: "APPLY", amountCents: -applied, balanceAfter: creditBal - applied, invoiceId: inv.id, note: `Auto-applied to ${inv.invoiceNo} at billing`, byName: user.fullName } });
              await db.invoice.update({ where: { id: inv.id }, data: { paidCents: applied, status: applied >= due ? "PAID" : "PARTIAL" } });
              creditsApplied++;
            }
          }
          created++;
          totalBilled += total;
        }
        return NextResponse.json({ created, updated, skipped, discounted, creditsApplied, totalBilled, term: term.name });
      }
      // ── clone previous term's fee structures into the current term ──
      // (iEduPro parity: one click re-bills every class that had a structure
      // last term; classes already billed this term are left untouched)
      if (action === "clone-structures") {
        const { sourceTermId } = body;
        const source = await db.term.findUnique({ where: { id: sourceTermId }, include: { academicYear: true } });
        if (!source || source.academicYear.schoolId !== schoolId) return NextResponse.json({ error: "Source term not found" }, { status: 404 });
        const currentTerm = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
        if (!currentTerm) return NextResponse.json({ error: "No current term" }, { status: 400 });
        if (source.id === currentTerm.id) return NextResponse.json({ error: "Pick a different term to clone from" }, { status: 400 });
        const sourceStructures = await db.feeStructure.findMany({ where: { schoolId, termId: source.id }, include: { items: true } });
        const currentStructures = await db.feeStructure.findMany({ where: { schoolId, termId: currentTerm.id }, select: { classroomId: true } });
        const have = new Set(currentStructures.map((s) => s.classroomId));
        const todo = sourceStructures.filter((s) => !have.has(s.classroomId));
        let cloned = 0;
        for (const src of todo) {
          const created = await db.feeStructure.create({
            data: { schoolId, termId: currentTerm.id, classroomId: src.classroomId, totalCents: src.totalCents },
          });
          for (const it of src.items) {
            await db.feeItem.create({ data: { feeStructureId: created.id, name: it.name, amountCents: it.amountCents, isOptional: it.isOptional } });
          }
          cloned++;
        }
        return NextResponse.json({ cloned, skipped: sourceStructures.length - cloned, term: currentTerm.name });
      }
      // ── bulk arrears CSV import (legacy balances / catch-up billing) ──
      // rows: studentIndex,description,amount — one arrears invoice per row,
      // created OVERDUE in the current term so they land on the chase-list.
      if (action === "import-arrears") {
        const csv: string = typeof body.csv === "string" ? body.csv : "";
        const rows = csv.replace(/^\uFEFF/, "").trim().split(/\r?\n/).map((l) => l.split(",").map((c) => c.trim()));
        if (rows.length < 2) return NextResponse.json({ error: "The file needs a header row plus at least one learner row" }, { status: 400 });
        const header = rows[0].map((h) => h.toLowerCase());
        const idxCol = header.indexOf("studentindex"), descCol = header.indexOf("description"), amtCol = header.indexOf("amount");
        if (idxCol === -1 || amtCol === -1) return NextResponse.json({ error: "Header must include at least: studentIndex,amount (description optional)" }, { status: 400 });
        const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
        if (!term) return NextResponse.json({ error: "No current term" }, { status: 400 });
        const results: { line: number; index: string; ok: boolean; error?: string; duplicate?: boolean }[] = [];
        let imported = 0, importedCents = 0;
        for (let i = 1; i < rows.length; i++) {
          const cells = rows[i];
          const index = cells[idxCol] ?? "";
          const description = (descCol >= 0 ? cells[descCol] : "") || "Arrears carried forward";
          const amount = Math.round(Number((cells[amtCol] ?? "").replace(/[^0-9.]/g, "")) * 100);
          const line = i + 1;
          if (!index || !Number.isFinite(amount) || amount <= 0) {
            results.push({ line, index, ok: false, error: "Needs a student index and an amount > 0" });
            continue;
          }
          const student = await db.student.findFirst({
            where: { nyansapoIndex: index, enrollments: { some: { schoolId, status: "ACTIVE" } } },
          });
          if (!student) { results.push({ line, index, ok: false, error: "No active learner with this index in this school" }); continue; }
          const dupe = await db.invoice.findFirst({ where: { schoolId, studentId: student.id, termId: term.id, items: { some: { name: `Arrears — ${description}` } } } });
          if (dupe) { results.push({ line, index, ok: true, duplicate: true }); continue; }
          const invoice = await db.invoice.create({
            data: {
              invoiceNo: await nextInvoiceNo(), studentId: student.id, schoolId, termId: term.id,
              classroomName: (await db.enrollment.findFirst({ where: { studentId: student.id, schoolId, status: "ACTIVE" }, include: { classroom: true } }))?.classroom.name ?? "",
              totalCents: amount, status: "OVERDUE", dueDate: new Date(Date.now() - 864e5),
            },
          });
          await db.invoiceItem.create({ data: { invoiceId: invoice.id, name: `Arrears — ${description}`, amountCents: amount } });
          results.push({ line, index, ok: true });
          imported++; importedCents += amount;
        }
        return NextResponse.json({ results, imported, importedCents, failed: results.filter((r) => !r.ok).length });
      }
      // bulk receipt payloads for reprinting issued receipts
      if (action === "receipt-payloads") {
        const ids: string[] = Array.isArray(body.paymentIds) ? body.paymentIds : [];
        if (!ids.length) return NextResponse.json({ error: "No payments selected" }, { status: 400 });
        if (ids.length > 50) return NextResponse.json({ error: "Maximum 50 receipts per batch" }, { status: 400 });
        const payloads: Awaited<ReturnType<typeof invoicePaymentReceipt>>[] = [];
        for (const id of ids) {
          const p = await db.payment.findFirst({ where: { id, schoolId } });
          if (!p) continue;
          try { payloads.push(await invoicePaymentReceipt(db, p.id)); } catch { /* skip broken rows */ }
        }
        return NextResponse.json({ receipts: payloads });
      }
      if (action === "record-payment") {
        const { invoiceId, amountCents, method, reference, bankName, chequeNo, offline, clientId } = body;
        if (clientId) {
          const existing = await db.payment.findFirst({ where: { reference: `SYNC:${clientId}` } });
          if (existing) return NextResponse.json({ payment: existing, duplicate: true });
        }
        const inv = await db.invoice.findUnique({ where: { id: invoiceId }, include: { student: true } });
        if (!inv) return NextResponse.json({ error: "Invoice not found" }, { status: 404 });
        // CI3 prepaid credits: the amount received may EXCEED the balance —
        // min(amount, balance) settles the invoice, the excess is banked as the
        // learner's credit (StudentCredit kind CREDIT, receipt-linked) and is
        // auto-applied to future bills / convertible to the daily wallet.
        const requested = Math.round(Number(amountCents) || 0);
        if (requested <= 0) return NextResponse.json({ error: "Amount is invalid" }, { status: 400 });
        const invBalance = Math.max(0, inv.totalCents - inv.discountCents - inv.paidCents);
        const amt = Math.min(requested, invBalance);
        const excess = requested - amt;
        const payment = await db.payment.create({
          data: {
            receiptNo: await nextReceiptNo(), invoiceId, studentId: inv.studentId, schoolId, amountCents: requested,
            method: method ?? "CASH", reference: clientId ? `SYNC:${clientId}` : reference ?? null,
            bankName: bankName ?? null, chequeNo: chequeNo ?? null,
            receivedByName: user.fullName, paidAt: new Date(body.paidAt ?? Date.now()),
            offlineQueued: !!offline, syncedAt: offline ? new Date() : null,
          },
        });
        const paid = inv.paidCents + amt;
        await db.invoice.update({ where: { id: invoiceId }, data: { paidCents: paid, status: paid >= inv.totalCents - inv.discountCents ? "PAID" : "PARTIAL" } });
        // bank the overpayment as prepaid credit (running balance = Σ signed rows)
        if (excess > 0) {
          const agg = await db.studentCredit.aggregate({ where: { schoolId, studentId: inv.studentId }, _sum: { amountCents: true } });
          const creditBal = agg._sum.amountCents ?? 0;
          await db.studentCredit.create({
            data: {
              schoolId, studentId: inv.studentId, kind: "CREDIT", amountCents: excess,
              balanceAfter: creditBal + excess, sourcePaymentId: payment.id,
              note: `Overpayment on ${inv.invoiceNo} — carried forward to future bills`, byName: user.fullName,
            },
          });
        }
        // auto receipt SMS to the guardian
        const smsSetting = await db.smsSetting.findUnique({ where: { schoolId } });
        if (smsSetting?.autoPaymentReceipts) {
          const balanceAfter = inv.totalCents - inv.discountCents - paid;
          await sendAutomated({
            db, schoolId, trigger: "PAYMENT_RECEIVED",
            defaultBody: `We have received GH₵${(requested / 100).toFixed(2)} for ${inv.student.firstName} (${payment.receiptNo}). Balance: GH₵${(Math.max(0, balanceAfter) / 100).toFixed(2)}${excess > 0 ? `. Credit: GH₵${(excess / 100).toFixed(2)}` : ""}. Thank you.`,
            vars: {
              student: inv.student.firstName, amount: `GH₵${(requested / 100).toFixed(2)}`,
              balance: `GH₵${(Math.max(0, balanceAfter) / 100).toFixed(2)}`, receipt: payment.receiptNo,
              date: new Date().toLocaleDateString("en-GB"),
            },
            studentId: inv.studentId, recipientName: inv.student.guardianName, recipientPhone: inv.student.guardianPhone, sentByName: user.fullName,
          });
        }
        const receipt = await invoicePaymentReceipt(db, payment.id);
        return NextResponse.json({
          payment: { ...payment, student: `${inv.student.firstName} ${inv.student.lastName}` },
          receipt: excess > 0 ? { ...receipt, creditCreated: excess } : receipt,
          creditCreated: excess > 0 ? excess : undefined,
        });
      }
      if (action === "generate-invoices") {
        const { classroomId } = body;
        const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
        if (!term) return NextResponse.json({ error: "No current term" }, { status: 400 });
        const fs = await db.feeStructure.findFirst({ where: { classroomId, termId: term.id }, include: { items: true } });
        if (!fs) return NextResponse.json({ error: "No fee structure for this class — set one up first" }, { status: 400 });
        const enrolled = await db.enrollment.findMany({ where: { classroomId, status: "ACTIVE" } });
        const existing = await db.invoice.findMany({ where: { termId: term.id, schoolId }, select: { studentId: true } });
        const have = new Set(existing.map((e) => e.studentId));
        let created = 0;
        let discounted = 0;
        let creditsApplied = 0;
        // active invoice-scope discount applications: auto-apply catalog discounts
        // AND approved profile assignments (iedupro parity — profiles carry
        // per-class rule overrides resolved at billing time)
        const autoApps = await db.discountApplication.findMany({
          where: {
            schoolId, status: "ACTIVE",
            OR: [
              { discount: { scope: "INVOICE", autoApply: true, active: true } },
              { profile: { category: "INVOICE", active: true } },
            ],
          },
          include: { discount: true, profile: { include: { rules: true } } },
        });
        const appByStudent: Record<string, typeof autoApps> = {};
        autoApps.forEach((a) => (appByStudent[a.studentId] ??= []).push(a));
        for (const e of enrolled) {
          if (have.has(e.studentId)) continue;
          const classroomName = fs.classroomId ? (await db.classroom.findUnique({ where: { id: fs.classroomId } }))?.name ?? "" : "";
          const inv = await db.invoice.create({ data: { invoiceNo: await nextInvoiceNo(), studentId: e.studentId, schoolId, termId: term.id, classroomName, totalCents: fs.totalCents, status: "UNPAID", dueDate: new Date(Date.now() + 14 * 864e5) } });
          for (const it of fs.items.filter((i) => !i.isOptional)) await db.invoiceItem.create({ data: { invoiceId: inv.id, name: it.name, amountCents: it.amountCents } });
          // apply discounts: percentages stack (floor at 100%), fixed amounts subtract
          const apps = appByStudent[e.studentId] ?? [];
          let discountTotal = 0;
          if (apps.length) {
            let pct = 0, fixed = 0;
            const names: string[] = [];
            for (const a of apps) {
              const src = a.discountId
                ? { kind: "discount" as const, id: a.discountId, name: a.discount!.name, type: a.discount!.type, value: a.discount!.value, scope: a.discount!.scope }
                : { kind: "profile" as const, id: a.profileId!, name: a.profile!.name, type: a.profile!.method, value: a.profile!.value, category: a.profile!.category };
              const eff = await effectiveValue(src, e.studentId, classroomName);
              const v = a.valueOverride ?? eff.value;
              if ((a.valueOverride ? src.type === "PERCENT" : eff.type === "PERCENT")) pct += v;
              else fixed += v;
              names.push(src.name);
            }
            const amount = Math.min(fs.totalCents, Math.round((fs.totalCents * Math.min(100, pct)) / 100) + Math.min(fs.totalCents, fixed));
            if (amount > 0) {
              await db.invoice.update({ where: { id: inv.id }, data: { discountCents: amount, discountReason: names.join(" + ") } });
              discountTotal = amount;
              discounted++;
            }
          }
          // CI3 prepaid credit: auto-apply the learner's credit balance to the
          // fresh bill (up to the amount due) — ledger row kind APPLY, invoice paidCents
          const creditAgg = await db.studentCredit.aggregate({ where: { schoolId, studentId: e.studentId }, _sum: { amountCents: true } });
          const creditBal = creditAgg._sum.amountCents ?? 0;
          if (creditBal > 0) {
            const due = inv.totalCents - discountTotal;
            const applied = Math.min(creditBal, due);
            if (applied > 0) {
              await db.studentCredit.create({
                data: { schoolId, studentId: e.studentId, kind: "APPLY", amountCents: -applied, balanceAfter: creditBal - applied, invoiceId: inv.id, note: `Auto-applied to ${inv.invoiceNo} at billing`, byName: user.fullName },
              });
              await db.invoice.update({ where: { id: inv.id }, data: { paidCents: applied, status: applied >= due ? "PAID" : "PARTIAL" } });
              creditsApplied++;
            }
          }
          created++;
        }
        // invoice-issued automation: notify guardians of freshly generated bills
        const issuedSetting = await db.smsSetting.findUnique({ where: { schoolId } });
        if (created > 0 && issuedSetting?.autoFeeReminders) {
          const newly = await db.invoice.findMany({ where: { termId: term.id, schoolId }, include: { student: true }, orderBy: { createdAt: "desc" }, take: created });
          for (const inv of newly) {
            const payable = inv.totalCents - inv.discountCents;
            await sendAutomated({
              db, schoolId, trigger: "INVOICE_ISSUED",
              defaultBody: `${inv.student.firstName}'s term bill for ${term.name} is GH₵${(payable / 100).toFixed(2)}. Due ${inv.dueDate ? new Date(inv.dueDate).toLocaleDateString("en-GB") : "soon"}. Thank you.`,
              vars: { student: inv.student.firstName, amount: `GH₵${(payable / 100).toFixed(2)}`, term: term.name, date: inv.dueDate ? new Date(inv.dueDate).toLocaleDateString("en-GB") : "" },
              studentId: inv.studentId, recipientName: inv.student.guardianName, recipientPhone: inv.student.guardianPhone, sentByName: user.fullName,
            });
          }
        }
        return NextResponse.json({ created, discounted, creditsApplied });
      }
      if (action === "flag-defaulter") {
        const { studentId, amountCents, description, type = "FEE_ARREARS", severity = "HIGH" } = body;
        const flag = await db.studentFlag.create({ data: { studentId, schoolId, raisedByName: user.fullName, type, severity, status: "ACTIVE", amountOwedCents: Number(amountCents) || null, description: description ?? "Outstanding fees after repeated reminders." } });
        return NextResponse.json({ flag });
      }
      if (action === "resolve-flag") {
        const { flagId, note } = body;
        const f = await db.studentFlag.update({ where: { id: flagId }, data: { status: "RESOLVED", resolvedAt: new Date(), resolutionNote: note ?? "Resolved.", resolvedByName: user.fullName } });
        return NextResponse.json({ flag: f });
      }
    }

    // ── ATTENDANCE ────────────────────────────────────────
    case "attendance": {
      if (action === "save") {
        const { classroomId, date, records, offline, clientId } = body;
        if (clientId) {
          const existing = await db.attendanceSession.findFirst({ where: { classroomId, date: new Date(date), takenByName: { contains: `SYNC:${clientId}` } } });
          if (existing) return NextResponse.json({ session: existing, duplicate: true });
        }
        const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
        let session = await db.attendanceSession.findFirst({ where: { classroomId, date: new Date(date) } });
        if (session) await db.attendanceRecord.deleteMany({ where: { sessionId: session.id } });
        else session = await db.attendanceSession.create({ data: { classroomId, schoolId, termId: term!.id, date: new Date(date), takenByName: `${user.fullName}${clientId ? ` (SYNC:${clientId})` : ""}` } });
        for (const r of records) {
          await db.attendanceRecord.create({ data: { sessionId: session.id, studentId: r.studentId, status: r.status, markedOffline: !!offline } });
        }
        // auto absence alerts to guardians (deduplicated per learner + date)
        const smsSetting = await db.smsSetting.findUnique({ where: { schoolId } });
        if (smsSetting?.autoAbsenceAlerts && !offline) {
          const absent = records.filter((r: any) => r.status === "ABSENT");
          for (const r of absent) {
            const already = await db.smsMessage.findFirst({ where: { schoolId, studentId: r.studentId, category: "ATTENDANCE", sentAt: { gte: new Date(new Date(date).setHours(0, 0, 0, 0)) } } });
            if (already) continue;
            const st = await db.student.findUnique({ where: { id: r.studentId } });
            if (!st) continue;
            await sendAutomated({
              db, schoolId, trigger: "ABSENCE_MARKED",
              defaultBody: `${st.firstName} was marked ABSENT today (${new Date(date).toLocaleDateString("en-GB")}). Please contact the school if this is unexpected.`,
              vars: { student: st.firstName, date: new Date(date).toLocaleDateString("en-GB") },
              studentId: st.id, recipientName: st.guardianName, recipientPhone: st.guardianPhone, sentByName: user.fullName,
            });
          }
        }
        return NextResponse.json({ session: { id: session.id }, saved: records.length });
      }
    }

    // ── GRADEBOOK ─────────────────────────────────────────
    case "gradebook": {
      if (action === "create-assessment") {
        const { classroomId, subjectId, title, type, maxScore, date } = body;
        const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
        const gate = await assertResultsEditable(schoolId, classroomId, term?.id);
        if (gate.blocked) return NextResponse.json({ error: gate.error }, { status: 423 });
        const a = await db.assessment.create({ data: { classroomId, schoolId, termId: term!.id, subjectId, title, type: type ?? "EXERCISE", maxScore: Number(maxScore) || 100, date: date ? new Date(date) : new Date() } });
        return NextResponse.json({ assessment: a });
      }
      if (action === "save-marks") {
        const { assessmentId, marks, offline } = body;
        const a = await db.assessment.findUnique({ where: { id: assessmentId } });
        if (!a) return NextResponse.json({ error: "Assessment not found" }, { status: 404 });
        const gate = await assertResultsEditable(schoolId, a.classroomId, a.termId);
        if (gate.blocked) return NextResponse.json({ error: gate.error }, { status: 423 });
        await db.mark.deleteMany({ where: { assessmentId } });
        for (const m of marks) {
          if (m.score !== null && m.score !== undefined && m.score !== "") {
            await db.mark.create({ data: { assessmentId, studentId: m.studentId, score: Number(m.score), enteredOffline: !!offline } });
          }
        }
        return NextResponse.json({ saved: marks.length });
      }
      if (action === "delete-assessment") {
        const a = await db.assessment.findUnique({ where: { id: body.assessmentId } });
        if (a) {
          const gate = await assertResultsEditable(schoolId, a.classroomId, a.termId);
          if (gate.blocked) return NextResponse.json({ error: gate.error }, { status: 423 });
        }
        await db.mark.deleteMany({ where: { assessmentId: body.assessmentId } });
        await db.assessment.delete({ where: { id: body.assessmentId } });
        return NextResponse.json({ ok: true });
      }
    }

    // ── REPORT CARDS ──────────────────────────────────────
    case "reports": {
      if (action === "generate") {
        const { classroomId, termId } = body;
        const term = await db.term.findUnique({ where: { id: termId }, include: { academicYear: true } });
        if (!term) return NextResponse.json({ error: "Term not found" }, { status: 404 });
        const gate = await assertResultsComputable(schoolId, classroomId, termId);
        if (gate.blocked) return NextResponse.json({ error: gate.error }, { status: 423 });
        const classroom = await db.classroom.findUnique({ where: { id: classroomId } });
        const enrolled = await db.enrollment.findMany({ where: { classroomId, status: "ACTIVE" }, include: { student: true } });
        const assessments = await db.assessment.findMany({ where: { classroomId, termId }, include: { subject: true, marks: true } });
        if (!assessments.length) return NextResponse.json({ error: "No assessments recorded for this class and term yet — enter marks in the Gradebook first." }, { status: 400 });
        // remark template library (iedupro parity) — falls back to band heuristics
        const templates = await db.remarkTemplate.findMany({ where: { schoolId, active: true } });
        const pickRemark = (pct: number, kind: "TEACHER" | "HEAD"): string => {
          const cat = kind === "TEACHER" ? (pct >= 68 ? "TEACHER_POSITIVE" : pct >= 40 ? "TEACHER_NEUTRAL" : "TEACHER_NEGATIVE") : "HEAD_RANGE";
          const pool = cat === "HEAD_RANGE" ? templates.filter((t) => t.category === "HEAD_RANGE" && pct >= (t.minPercent ?? 0) && pct <= (t.maxPercent ?? 100)) : templates.filter((t) => t.category === cat);
          if (!pool.length) {
            if (kind === "HEAD") return pct >= 80 ? "An excellent result — a model learner for the school." : pct >= 55 ? "A good result. Keep striving for excellence." : "Needs closer support at home and at school.";
            return pct >= 68 ? "Commendable" : pct >= 55 ? "Good effort" : pct >= 40 ? "Fair — needs practice" : "Needs close support";
          }
          return pool[Math.floor(Math.random() * pool.length)].text;
        };
        // group by subject, splitting class work (30) from end-of-term exam (70) — Ghana standard
        const bySubject: Record<string, { subjectId: string; name: string; code: string; isCore: boolean; classGot: Record<string, number>; classMax: number; examGot: Record<string, number>; examMax: number }> = {};
        for (const a of assessments) {
          const key = a.subject.name;
          bySubject[key] ??= { subjectId: a.subjectId, name: key, code: a.subject.code, isCore: a.subject.isCore, classGot: {}, classMax: 0, examGot: {}, examMax: 0 };
          const isExam = a.type === "END_OF_TERM";
          const got = isExam ? bySubject[key].examGot : bySubject[key].classGot;
          for (const m of a.marks) {
            if (m.score === null) continue;
            got[m.studentId] = (got[m.studentId] ?? 0) + m.score;
          }
          if (isExam) bySubject[key].examMax += a.maxScore; else bySubject[key].classMax += a.maxScore;
        }
        // per-student subject rows with 30/70 composition
        const subjRows = Object.values(bySubject);
        const results: { studentId: string; subjects: { name: string; code: string; isCore: boolean; classScore: number | null; examScore: number | null; total: number }[] }[] = enrolled.map((e) => ({
          studentId: e.student.id,
          subjects: subjRows.map((s) => {
            const classPct = s.classMax > 0 && s.classGot[e.student.id] !== undefined ? s.classGot[e.student.id] / s.classMax : null;
            const examPct = s.examMax > 0 && s.examGot[e.student.id] !== undefined ? s.examGot[e.student.id] / s.examMax : null;
            if (classPct !== null && examPct !== null) {
              const cs = Math.round(classPct * 30), es = Math.round(examPct * 70);
              return { name: s.name, code: s.code, isCore: s.isCore, classScore: cs, examScore: es, total: cs + es };
            }
            // single-component subject: grade on what exists, no artificial split
            const pct = classPct ?? examPct ?? 0;
            return { name: s.name, code: s.code, isCore: s.isCore, classScore: null, examScore: null, total: Math.round(pct * 100) };
          }),
        }));
        // subject positions: rank within each subject across the class
        const subjNames = subjRows.map((s) => s.name);
        for (const name of subjNames) {
          const ranked = [...results].sort((a, b) => (b.subjects.find((s) => s.name === name)?.total ?? -1) - (a.subjects.find((s) => s.name === name)?.total ?? -1));
          ranked.forEach((r, i) => { const row = r.subjects.find((s) => s.name === name); if (row) (row as any).position = i + 1; });
        }
        const withAvg = results.map((r) => ({ ...r, avg: r.subjects.length ? r.subjects.reduce((s, x) => s + x.total, 0) / r.subjects.length : 0 })).sort((a, b) => b.avg - a.avg);
        let created = 0;
        for (let i = 0; i < withAvg.length; i++) {
          const r = withAvg[i];
          const existing = await db.reportCard.findFirst({ where: { studentId: r.studentId, termId } });
          if (existing) { await db.reportCardResult.deleteMany({ where: { reportCardId: existing.id } }); await db.reportCard.delete({ where: { id: existing.id } }); }
          const rc = await db.reportCard.create({ data: { studentId: r.studentId, termId, classroomName: classroom?.name ?? "", totalScore: Math.round(r.avg * r.subjects.length), average: Math.round(r.avg), classPosition: i + 1, classSize: withAvg.length, promotion: r.avg >= 40 ? "PROMOTED" : "PROBATION", published: false, teacherRemark: pickRemark(Math.round(r.avg), "TEACHER"), headRemark: pickRemark(Math.round(r.avg), "HEAD") } });
          for (const s of r.subjects) {
            const g = gradeFor(school.curriculum, s.total);
            await db.reportCardResult.create({ data: { reportCardId: rc.id, subjectName: s.name, subjectCode: s.code, isCore: s.isCore, classScore: s.classScore, examScore: s.examScore, total: s.total, grade: g.grade, position: (s as any).position ?? null, remark: pickRemark(s.total, "TEACHER") } });
          }
          created++;
        }
        return NextResponse.json({ created });
      }
      if (action === "publish") {
        await db.reportCard.update({ where: { id: body.reportCardId }, data: { published: true, publishedAt: new Date() } });
        return NextResponse.json({ ok: true });
      }
      if (action === "publish-all") {
        await db.reportCard.updateMany({ where: { termId: body.termId }, data: { published: true, publishedAt: new Date() } });
        return NextResponse.json({ ok: true });
      }
      if (action === "update-remarks") {
        const { reportCardId, teacherRemark, headRemark, conduct, promotion } = body;
        const rc = await db.reportCard.update({ where: { id: reportCardId }, data: { teacherRemark, headRemark, conduct, promotion } });
        return NextResponse.json({ reportCard: rc });
      }
    }

    // ── STUDENTS ──────────────────────────────────────────
    case "students": {
      // ── iEduPro student management actions (promotion / mute / diet) ──
      if (action === "promote") {
        // promotion wizard: move selected learners up a class (or graduate them).
        // The active placement row is reassigned and stamped with its trail.
        const { studentIds, toClassroomId, graduateInstead } = body as { studentIds: string[]; toClassroomId?: string; graduateInstead?: boolean };
        if (!Array.isArray(studentIds) || !studentIds.length) return NextResponse.json({ error: "studentIds[] required" }, { status: 400 });
        let moved = 0, graduated = 0;
        for (const studentId of studentIds) {
          const en = await db.enrollment.findFirst({ where: { studentId, schoolId, status: "ACTIVE" }, include: { classroom: true } });
          if (!en) continue;
          if (graduateInstead) {
            await db.enrollment.update({
              where: { id: en.id },
              data: { status: "GRADUATED", exitDate: new Date(), exitReason: `Graduated from ${en.classroom.name} (promotion cycle)`, promotedFromName: en.classroom.name, promotedAt: new Date() },
            });
            graduated++;
          } else {
            const to = toClassroomId ? await db.classroom.findUnique({ where: { id: toClassroomId } }) : null;
            if (!to || to.schoolId !== schoolId) continue;
            await db.enrollment.update({
              where: { id: en.id },
              data: { classroomId: to.id, promotedFromName: en.classroom.name, promotedAt: new Date() },
            });
            moved++;
          }
        }
        const n = await db.enrollment.count({ where: { schoolId, status: "ACTIVE" } });
        await db.school.update({ where: { id: schoolId }, data: { studentsOnRoll: n } });
        return NextResponse.json({ moved, graduated, onRoll: n });
      }
      if (action === "mute") {
        const { studentId, reason } = body;
        const en = await db.enrollment.findFirst({ where: { studentId, schoolId, status: "ACTIVE" } });
        if (!en) return NextResponse.json({ error: "No active placement" }, { status: 404 });
        await db.enrollment.update({ where: { id: en.id }, data: { status: "WITHDRAWN", exitDate: new Date(), exitReason: reason ?? "Muted (temporarily off roll)" } });
        const n = await db.enrollment.count({ where: { schoolId, status: "ACTIVE" } });
        await db.school.update({ where: { id: schoolId }, data: { studentsOnRoll: n } });
        return NextResponse.json({ muted: true, onRoll: n });
      }
      if (action === "unmute") {
        const { studentId } = body;
        const en = await db.enrollment.findFirst({ where: { studentId, schoolId, status: "WITHDRAWN" }, orderBy: { exitDate: "desc" } });
        if (!en) return NextResponse.json({ error: "No muted placement found" }, { status: 404 });
        await db.enrollment.update({ where: { id: en.id }, data: { status: "ACTIVE", exitDate: null, exitReason: null } });
        const n = await db.enrollment.count({ where: { schoolId, status: "ACTIVE" } });
        await db.school.update({ where: { id: schoolId }, data: { studentsOnRoll: n } });
        return NextResponse.json({ unmuted: true, onRoll: n });
      }
      if (action === "set-diet") {
        const { studentId, dietNote } = body;
        const st = await db.student.update({ where: { id: studentId }, data: { dietNote: dietNote?.trim() ? dietNote.trim() : null } });
        return NextResponse.json({ student: { id: st.id, dietNote: st.dietNote } });
      }
      if (action === "create") {
        const { firstName, lastName, otherNames, gender, dateOfBirth, guardianName, guardianPhone, guardianOccupation, relationship, classroomId, previousSchool, inquiryId } = body;
        if (!firstName || !lastName || !guardianName || !guardianPhone || !classroomId) return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
        const classroom = await db.classroom.findUnique({ where: { id: classroomId } });
        if (!classroom) return NextResponse.json({ error: "Classroom not found" }, { status: 404 });
        const birthYear = dateOfBirth ? new Date(dateOfBirth).getFullYear() : 2016;
        const student = await db.student.create({ data: { nyansapoIndex: await nextStudentIndex(birthYear), firstName, lastName, otherNames, gender, dateOfBirth: dateOfBirth ? new Date(dateOfBirth) : null, guardianName, guardianPhone, guardianOccupation, relationship: relationship ?? "Parent", previousSchool, status: "ACTIVE" } });
        const roll = (await db.enrollment.count({ where: { classroomId } })) + 1;
        await db.enrollment.create({ data: { studentId: student.id, classroomId, schoolId, rollNo: roll, admissionDate: new Date(), status: "ACTIVE" } });
        // auto-bill for current term
        const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
        if (term) {
          const fs = await db.feeStructure.findFirst({ where: { classroomId, termId: term.id }, include: { items: true } });
          if (fs) {
            const inv = await db.invoice.create({ data: { invoiceNo: await nextInvoiceNo(), studentId: student.id, schoolId, termId: term.id, classroomName: classroom.name, totalCents: fs.totalCents, status: "UNPAID", dueDate: new Date(Date.now() + 14 * 864e5) } });
            for (const it of fs.items.filter((i) => !i.isOptional)) await db.invoiceItem.create({ data: { invoiceId: inv.id, name: it.name, amountCents: it.amountCents } });
          }
        }
        if (inquiryId) await db.admissionInquiry.update({ where: { id: inquiryId }, data: { status: "ENROLLED", handledByName: user.fullName, matchedStudentId: student.id } });
        const n = await db.enrollment.count({ where: { schoolId, status: "ACTIVE" } });
        await db.school.update({ where: { id: schoolId }, data: { studentsOnRoll: n } });
        return NextResponse.json({ student, enrolled: true });
      }
    }

    // ── SETTINGS ──────────────────────────────────────────
    case "settings": {
      if (action === "update-school") {
        const { curriculum, curricula, motto, phone, email, nasiaStatus, gnacopsStatus } = body;
        // multi-curricula (international schools run GES + Cambridge/IB in parallel):
        // validate the array; primary curriculum = first entry (kept in sync)
        let curriculaJson: string | null | undefined = undefined;
        if (Array.isArray(curricula)) {
          const valid = curricula.filter((c: unknown): c is string => typeof c === "string" && isCurriculumKey(c));
          if (!valid.length) return NextResponse.json({ error: "Pick at least one curriculum" }, { status: 400 });
          curriculaJson = JSON.stringify([...new Set(valid)]);
        }
        const s = await db.school.update({
          where: { id: schoolId },
          data: {
            curriculum: (Array.isArray(curricula) ? (curriculaJson ? JSON.parse(curriculaJson)[0] : undefined) : curriculum) ?? school.curriculum,
            ...(curriculaJson !== undefined ? { curricula: curriculaJson } : {}),
            motto: motto ?? school.motto, phone: phone ?? school.phone, email: email ?? school.email,
            nasiaStatus: nasiaStatus ?? school.nasiaStatus, gnacopsStatus: gnacopsStatus ?? school.gnacopsStatus,
          },
        });
        return NextResponse.json({ school: s });
      }
      if (action === "update-preferences") {
        // branding uploads + default receipt style + per-term "days opened"
        // (CI3 system_settings_modern parity). Branding images are stored as
        // binary MediaAssets; the setting value keeps only the short
        // /api/media/{id} URL (legacy data-URL values still render).
        const { logoUrl, signatureUrl, ssnitLogoUrl, receiptStyle, daysOpened } = body as {
          logoUrl?: string | null; signatureUrl?: string | null; ssnitLogoUrl?: string | null; receiptStyle?: string; daysOpened?: Record<string, number>;
        };
        const entries: [string, string][] = [];
        // valid data-URL → media asset; explicit null clears; anything else keeps the stored value
        const saneImage = (v: any) => (v === null ? "null" : typeof v === "string" && v.startsWith("data:image/") && v.length < 700_000 ? "upload" : null);
        const logoKind = saneImage(logoUrl);
        if (logoKind) entries.push(["branding.logoUrl", logoKind === "null" ? JSON.stringify(null) : JSON.stringify(await storeMedia({ db, schoolId, kind: "LOGO", dataUrl: logoUrl as string }))]);
        const sigKind = saneImage(signatureUrl);
        if (sigKind) entries.push(["branding.signatureUrl", sigKind === "null" ? JSON.stringify(null) : JSON.stringify(await storeMedia({ db, schoolId, kind: "SIGNATURE", dataUrl: signatureUrl as string }))]);
        const ssnitKind = saneImage(ssnitLogoUrl);
        if (ssnitKind) entries.push(["branding.ssnitLogoUrl", ssnitKind === "null" ? JSON.stringify(null) : JSON.stringify(await storeMedia({ db, schoolId, kind: "LOGO", dataUrl: ssnitLogoUrl as string }))]);
        if (receiptStyle != null) {
          if (!["A5", "FORMAL", "MODERN", "THERMAL"].includes(receiptStyle)) return NextResponse.json({ error: "Unknown receipt style" }, { status: 400 });
          entries.push(["receipts.style", JSON.stringify(receiptStyle)]);
        }
        for (const [termId, days] of Object.entries(daysOpened ?? {})) {
          const term = await db.term.findUnique({ where: { id: termId }, include: { academicYear: true } });
          if (!term || term.academicYear.schoolId !== schoolId) return NextResponse.json({ error: "Term not found" }, { status: 404 });
          const n = Number(days);
          if (!Number.isFinite(n) || n < 0 || n > 400) return NextResponse.json({ error: "Days opened must be between 0 and 400" }, { status: 400 });
          entries.push([`term.daysOpened:${termId}`, JSON.stringify(Math.round(n))]);
        }
        for (const [key, value] of entries) {
          await db.schoolSetting.upsert({ where: { schoolId_key: { schoolId, key } }, create: { schoolId, key, value }, update: { value } });
        }
        return NextResponse.json({ ok: true });
      }
      // ── CI3 system_settings General / Payment / Security tabs (system.* KV) ──
      if (action === "update-system") {
        const allowed = ["slogan", "address", "boxNumber", "digitalAddress", "website", "ssnitNumber", "currency", "halfPaymentBy", "fullPaymentBy", "momoAccountName", "momoAccountNumber", "autoLock"];
        const entries: [string, string][] = [];
        for (const key of allowed) {
          if (!(key in body)) continue;
          const v = body[key];
          if (key === "autoLock") { entries.push(["system.autoLock", JSON.stringify(!!v)]); continue; }
          if (key === "fullPaymentBy") {
            const d = v ? new Date(v) : null;
            if (d && isNaN(d.getTime())) return NextResponse.json({ error: "Full-payment date is not a valid date" }, { status: 400 });
            entries.push(["system.fullPaymentBy", JSON.stringify(d && !isNaN(d.getTime()) ? d.toISOString() : null)]);
            continue;
          }
          if (typeof v !== "string" && v !== null) continue;
          entries.push([`system.${key}`, JSON.stringify(typeof v === "string" ? v.trim() : null)]);
        }
        for (const [key, value] of entries) {
          await db.schoolSetting.upsert({ where: { schoolId_key: { schoolId, key } }, create: { schoolId, key, value }, update: { value } });
        }
        return NextResponse.json({ ok: true, saved: entries.length });
      }
      if (action === "create-classroom") {
        const { name, grade, stage, capacity } = body;
        const c = await db.classroom.create({ data: { schoolId, name, gradeLevel: grade, stageLabel: stage, capacity: Number(capacity) || 30 } });
        return NextResponse.json({ classroom: c });
      }
      if (action === "create-subject") {
        const { name, code } = body;
        const s = await db.subject.create({ data: { schoolId, name, code: (code ?? name.slice(0, 3)).toUpperCase(), curriculum: school.curriculum } });
        return NextResponse.json({ subject: s });
      }
      // ── report sheet library: remark templates & conduct traits ──
      if (action === "save-remark-template") {
        const { id, category, minPercent, maxPercent, text } = body;
        if (!text?.trim()) return NextResponse.json({ error: "Remark text is required" }, { status: 400 });
        const data = { category: category ?? "TEACHER_POSITIVE", minPercent: category === "HEAD_RANGE" ? Number(minPercent) || 0 : null, maxPercent: category === "HEAD_RANGE" ? Number(maxPercent) || 100 : null, text: text.trim() };
        const t = id ? await db.remarkTemplate.update({ where: { id }, data }) : await db.remarkTemplate.create({ data: { ...data, schoolId } });
        return NextResponse.json({ template: t });
      }
      if (action === "toggle-remark-template") {
        const t = await db.remarkTemplate.update({ where: { id: body.id }, data: { active: !!body.active } });
        return NextResponse.json({ template: t });
      }
      if (action === "delete-remark-template") {
        await db.remarkTemplate.delete({ where: { id: body.id } });
        return NextResponse.json({ ok: true });
      }
      if (action === "seed-remark-templates") {
        // starter library (Ghana classroom idiom) when the school has none yet
        const existing = await db.remarkTemplate.count({ where: { schoolId } });
        if (existing) return NextResponse.json({ created: 0, message: "Library already has entries" });
        const starters: { category: string; minPercent?: number; maxPercent?: number; text: string }[] = [
          { category: "TEACHER_POSITIVE", text: "An outstanding term — consistently thorough work and excellent participation." },
          { category: "TEACHER_POSITIVE", text: "A bright and eager learner who contributes confidently to class discussion." },
          { category: "TEACHER_POSITIVE", text: "Very hardworking; accuracy and presentation keep improving each term." },
          { category: "TEACHER_NEUTRAL", text: "A steady effort. With more practice at home the result will improve further." },
          { category: "TEACHER_NEUTRAL", text: "Capable of much more; needs to complete class exercises on time." },
          { category: "TEACHER_NEGATIVE", text: "Performance is below expectation; please give closer supervision at home." },
          { category: "TEACHER_NEGATIVE", text: "Frequent lateness with assignments affected this term's scores." },
          { category: "HEAD_RANGE", minPercent: 80, maxPercent: 100, text: "An excellent result. A model learner for the school — keep it up." },
          { category: "HEAD_RANGE", minPercent: 68, maxPercent: 79, text: "A very good result. Aim for distinction next term." },
          { category: "HEAD_RANGE", minPercent: 55, maxPercent: 67, text: "A good result. With sustained effort the learner will excel." },
          { category: "HEAD_RANGE", minPercent: 40, maxPercent: 54, text: "A fair result. The learner needs closer support at home and at school." },
          { category: "HEAD_RANGE", minPercent: 0, maxPercent: 39, text: "This result calls for urgent joint effort between home and school." },
        ];
        for (const s of starters) await db.remarkTemplate.create({ data: { schoolId, category: s.category, minPercent: s.minPercent ?? null, maxPercent: s.maxPercent ?? null, text: s.text } });
        return NextResponse.json({ created: starters.length });
      }
      if (action === "save-conduct-item") {
        const { name, kind } = body;
        if (!name?.trim()) return NextResponse.json({ error: "Name is required" }, { status: 400 });
        const c = await db.conductItem.create({ data: { schoolId, name: name.trim(), kind: kind === "INTEREST" ? "INTEREST" : "CONDUCT", sortOrder: 0 } });
        return NextResponse.json({ item: c });
      }
      if (action === "delete-conduct-item") {
        await db.conductItem.delete({ where: { id: body.id } });
        return NextResponse.json({ ok: true });
      }
    }

    // ── ANNOUNCEMENTS ─────────────────────────────────────
    case "announcements": {
      if (action === "create") {
        const { title, body: bodyText, audience, category, priority } = body;
        const a = await db.announcement.create({ data: { schoolId, title, body: bodyText, audience: audience ?? "PARENTS", category: category ?? "GENERAL", priority: priority ?? "NORMAL", postedByName: user.fullName } });
        return NextResponse.json({ announcement: a });
      }
    }
  }
  return NextResponse.json({ error: `Unknown action ${resource}:${action}` }, { status: 400 });
}

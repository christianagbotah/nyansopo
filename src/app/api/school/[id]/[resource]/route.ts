import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser, canOperateSchool } from "@/lib/session";
import { gradeFor } from "@/lib/grading";
import { sendSms, sendAutomated } from "@/lib/sms";
import { invoicePaymentReceipt } from "@/lib/receipt-payload";
import { nextInvoiceNo } from "@/lib/invoice-no";
import { effectiveValue } from "@/lib/discounts";

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
            invoice: inv ?? null,
            flags: e.student.flags.map((f) => ({ id: f.id, type: f.type, severity: f.severity, status: f.status, amountOwed: f.amountOwedCents, raisedAt: f.raisedAt, description: f.description, raisedByName: f.raisedByName, schoolId: f.schoolId })),
            risk: e.student.riskScores[0] ? { score: e.student.riskScores[0].score, level: e.student.riskScores[0].level } : null,
          };
        }),
      });
    }

    case "fees": {
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
      return NextResponse.json({
        terms: allYears.flatMap((y) => y.terms.map((t) => ({ id: t.id, name: `${t.name} ${y.name}`, isCurrent: t.isCurrent }))),
        termId,
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
      return NextResponse.json({
        school: { ...school, group: school.group ? { id: school.group.id, name: school.group.name, proprietorName: school.group.proprietorName } : null },
        years: years.map((y) => ({ id: y.id, name: y.name, isCurrent: y.isCurrent, terms: y.terms.map((t) => ({ id: t.id, name: t.name, startsAt: t.startsAt, endsAt: t.endsAt, isCurrent: t.isCurrent })) })),
        classrooms: classrooms.map((c) => ({ id: c.id, name: c.name, grade: c.gradeLevel, stage: c.stageLabel, capacity: c.capacity })),
        subjects: subjects.map((s) => ({ id: s.id, name: s.name, code: s.code })),
        staff: staff.map((s) => ({ id: s.id, fullName: s.fullName, role: s.role, title: s.title })),
        remarkTemplates: remarkTemplates.map((t) => ({ id: t.id, category: t.category, minPercent: t.minPercent, maxPercent: t.maxPercent, text: t.text, active: t.active })),
        conductItems: conductItems.map((c) => ({ id: c.id, name: c.name, kind: c.kind, active: c.active })),
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

  switch (resource) {
    // ── FEES ──────────────────────────────────────────────
    case "fees": {
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
        const { invoiceId, amountCents, method, reference, offline, clientId } = body;
        if (clientId) {
          const existing = await db.payment.findFirst({ where: { reference: `SYNC:${clientId}` } });
          if (existing) return NextResponse.json({ payment: existing, duplicate: true });
        }
        const inv = await db.invoice.findUnique({ where: { id: invoiceId }, include: { student: true } });
        if (!inv) return NextResponse.json({ error: "Invoice not found" }, { status: 404 });
        const amt = Math.max(0, Math.min(Number(amountCents), inv.totalCents - inv.discountCents - inv.paidCents));
        if (amt <= 0) return NextResponse.json({ error: "Amount exceeds balance or is invalid" }, { status: 400 });
        const seq = (await db.payment.count({ where: { schoolId } })) + 1001;
        const payment = await db.payment.create({
          data: {
            receiptNo: `RCP-2026-${String(seq).padStart(5, "0")}`, invoiceId, studentId: inv.studentId, schoolId, amountCents: amt,
            method: method ?? "CASH", reference: clientId ? `SYNC:${clientId}` : reference ?? null,
            receivedByName: user.fullName, paidAt: new Date(body.paidAt ?? Date.now()),
            offlineQueued: !!offline, syncedAt: offline ? new Date() : null,
          },
        });
        const paid = inv.paidCents + amt;
        await db.invoice.update({ where: { id: invoiceId }, data: { paidCents: paid, status: paid >= inv.totalCents - inv.discountCents ? "PAID" : "PARTIAL" } });
        // auto receipt SMS to the guardian
        const smsSetting = await db.smsSetting.findUnique({ where: { schoolId } });
        if (smsSetting?.autoPaymentReceipts) {
          const balanceAfter = inv.totalCents - inv.discountCents - paid;
          await sendAutomated({
            db, schoolId, trigger: "PAYMENT_RECEIVED",
            defaultBody: `We have received GH₵${(amt / 100).toFixed(2)} for ${inv.student.firstName} (${payment.receiptNo}). Balance: GH₵${(Math.max(0, balanceAfter) / 100).toFixed(2)}. Thank you.`,
            vars: {
              student: inv.student.firstName, amount: `GH₵${(amt / 100).toFixed(2)}`,
              balance: `GH₵${(Math.max(0, balanceAfter) / 100).toFixed(2)}`, receipt: payment.receiptNo,
              date: new Date().toLocaleDateString("en-GB"),
            },
            studentId: inv.studentId, recipientName: inv.student.guardianName, recipientPhone: inv.student.guardianPhone, sentByName: user.fullName,
          });
        }
        const receipt = await invoicePaymentReceipt(db, payment.id);
        return NextResponse.json({ payment: { ...payment, student: `${inv.student.firstName} ${inv.student.lastName}` }, receipt });
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
              discounted++;
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
        return NextResponse.json({ created, discounted });
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
        const a = await db.assessment.create({ data: { classroomId, schoolId, termId: term!.id, subjectId, title, type: type ?? "EXERCISE", maxScore: Number(maxScore) || 100, date: date ? new Date(date) : new Date() } });
        return NextResponse.json({ assessment: a });
      }
      if (action === "save-marks") {
        const { assessmentId, marks, offline } = body;
        const a = await db.assessment.findUnique({ where: { id: assessmentId } });
        if (!a) return NextResponse.json({ error: "Assessment not found" }, { status: 404 });
        await db.mark.deleteMany({ where: { assessmentId } });
        for (const m of marks) {
          if (m.score !== null && m.score !== undefined && m.score !== "") {
            await db.mark.create({ data: { assessmentId, studentId: m.studentId, score: Number(m.score), enteredOffline: !!offline } });
          }
        }
        return NextResponse.json({ saved: marks.length });
      }
      if (action === "delete-assessment") {
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
      if (action === "create") {
        const { firstName, lastName, otherNames, gender, dateOfBirth, guardianName, guardianPhone, guardianOccupation, relationship, classroomId, previousSchool, inquiryId } = body;
        if (!firstName || !lastName || !guardianName || !guardianPhone || !classroomId) return NextResponse.json({ error: "Missing required fields" }, { status: 400 });
        const classroom = await db.classroom.findUnique({ where: { id: classroomId } });
        if (!classroom) return NextResponse.json({ error: "Classroom not found" }, { status: 404 });
        const count = await db.student.count();
        const birthYear = dateOfBirth ? new Date(dateOfBirth).getFullYear() : 2016;
        const student = await db.student.create({ data: { nyansapoIndex: `NY-${birthYear}-${String(100000 + count + 1).slice(1)}`, firstName, lastName, otherNames, gender, dateOfBirth: dateOfBirth ? new Date(dateOfBirth) : null, guardianName, guardianPhone, guardianOccupation, relationship: relationship ?? "Parent", previousSchool, status: "ACTIVE" } });
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
        const { curriculum, motto, phone, email, nasiaStatus, gnacopsStatus } = body;
        const s = await db.school.update({ where: { id: schoolId }, data: { curriculum: curriculum ?? school.curriculum, motto: motto ?? school.motto, phone: phone ?? school.phone, email: email ?? school.email, nasiaStatus: nasiaStatus ?? school.nasiaStatus, gnacopsStatus: gnacopsStatus ?? school.gnacopsStatus } });
        return NextResponse.json({ school: s });
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

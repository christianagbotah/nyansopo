import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";
import { nextInvoiceNo } from "@/lib/codes";

// ── WAEC CANDIDACY (wave 57 — BECE / WASSCE registration & mocks) ──
// GET  — centres (with candidacies + subject entries + mock results),
//        classroom roster helper for the bulk-register picker, and the
//        school's subject list / RawScoreGrade bands for auto-grading.
// POST — save-centre · delete-centre · register-bulk · withdraw ·
//        reinstate · set-status · assign-indexes · set-index ·
//        save-subjects · copy-class-offering · invoice-fees ·
//        save-mock · delete-mock
//
// Index numbers follow the WAEC convention: centre number + serial padded
// to 10 characters total (e.g. centre 5021406 + serial 7 → 5021406007).
// Mock papers auto-grade on the school's RawScoreGrade bands (markFrom ≤
// pct ≤ markUpto), carrying the BECE aggregate weight as gradePoint.

const EXAM_TYPES = ["BECE", "WASSCE", "NOVDEC"] as const;
const CANDIDACY_STATUSES = ["REGISTERED", "INDEXED", "SUBMITTED", "WITHDRAWN"] as const;

const num = (v: unknown, d = 0) => (Number.isFinite(Number(v)) ? Number(v) : d);
const pct = (score: number, max: number) => (max > 0 ? (score / max) * 100 : 0);

/** Build the WAEC index for a serial: centreNumber + zero-padded serial
 *  such that the whole string is 10 characters (short centres pad more). */
function buildIndex(centreNumber: string, serialNo: number): string {
  const pad = Math.max(1, 10 - centreNumber.length);
  return `${centreNumber}${String(serialNo).padStart(pad, "0")}`;
}

/** grade a percentage against the school's RawScoreGrade bands */
function gradeFor(
  bands: { gradePoint: string; gradePointNumeric: number; markFrom: number; markUpto: number }[],
  percentage: number,
): { grade: string; gradePoint: number } | null {
  const band = bands.find((b) => percentage >= b.markFrom && percentage <= b.markUpto);
  if (band) return { grade: band.gradePoint, gradePoint: band.gradePointNumeric };
  // above every band → cap at the best one; below all → the last resort band
  const sorted = [...bands].sort((a, b) => b.markFrom - a.markFrom);
  return sorted[0] ? { grade: sorted[0].gradePoint, gradePoint: sorted[0].gradePointNumeric } : null;
}

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["exams.waec"] });
  if (!auth.ok) return auth.res;

  const centres = await db.waecCentre.findMany({
    where: { schoolId },
    orderBy: [{ examYear: "desc" }, { examType: "asc" }],
    include: {
      candidacies: {
        orderBy: { serialNo: "asc" },
        include: {
          student: { select: { id: true, firstName: true, lastName: true, otherNames: true, nyansapoIndex: true, gender: true } },
          subjects: { orderBy: { sortOrder: "asc" } },
          mocks: true,
        },
      },
    },
  });

  const [classrooms, subjects, bands] = await Promise.all([
    db.classroom.findMany({
      where: { schoolId },
      orderBy: { name: "asc" },
      select: { id: true, name: true, gradeLevel: true, _count: { select: { enrollments: { where: { status: "ACTIVE" } } } } },
    }),
    db.subject.findMany({ where: { schoolId }, orderBy: { name: "asc" }, select: { id: true, name: true, code: true, isCore: true } }),
    db.rawScoreGrade.findMany({ where: { schoolId }, orderBy: { sortOrder: "asc" } }),
  ]);

  // per-class enrolment roster for the register picker (?roster=<classroomId>&centreId=…)
  const url = new URL(req.url);
  const rosterId = url.searchParams.get("roster");
  const centreId = url.searchParams.get("centreId");
  let roster: { id: string; name: string; gender: string; already: boolean }[] | null = null;
  if (rosterId && centreId) {
    const centre = await db.waecCentre.findFirst({ where: { id: centreId, schoolId }, select: { id: true } });
    if (centre) {
      const rows = await db.enrollment.findMany({
        where: { classroomId: rosterId, status: "ACTIVE", schoolId },
        include: { student: { select: { id: true, firstName: true, lastName: true, otherNames: true, gender: true } } },
        orderBy: { student: { lastName: "asc" } },
      });
      const existing = new Set(
        (await db.waecCandidacy.findMany({ where: { centreId }, select: { studentId: true } })).map((c) => c.studentId),
      );
      roster = rows.map((r) => ({
        id: r.student.id,
        name: `${r.student.lastName} ${r.student.firstName}${r.student.otherNames ? " " + r.student.otherNames : ""}`.trim(),
        gender: r.student.gender,
        already: existing.has(r.student.id),
      }));
    }
  }

  return NextResponse.json({ centres, classrooms, subjects, bands, roster });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["exams.waec"] });
  if (!auth.ok) return auth.res;
  const actor = { id: auth.user!.id, fullName: auth.user!.fullName ?? "WAEC officer", role: auth.user!.role };
  const body = await req.json().catch(() => ({}));
  const action = String(body.action ?? "");

  // ── centre setup ────────────────────────────────────────────────
  if (action === "save-centre") {
    const examType = EXAM_TYPES.includes(body.examType) ? body.examType : "BECE";
    const examYear = Math.round(num(body.examYear, new Date().getFullYear() + 1));
    const centreNumber = String(body.centreNumber ?? "").replace(/\D/g, "");
    const centreName = String(body.centreName ?? "").trim();
    if (!centreNumber) return NextResponse.json({ error: "Centre number is required (digits only)" }, { status: 400 });
    if (!centreName) return NextResponse.json({ error: "Centre name is required" }, { status: 400 });
    const data = {
      examType, examYear, centreNumber, centreName,
      regFeeCents: Math.max(0, Math.round(num(body.regFeeCents))),
      note: body.note ? String(body.note) : null,
    };
    const row = body.id
      ? await db.waecCentre.update({ where: { id: String(body.id) }, data }).catch(() => null)
      : await db.waecCentre.upsert({ where: { schoolId_examType_examYear: { schoolId, examType, examYear } }, create: { ...data, schoolId }, update: data }).catch(() => null);
    if (!row) return NextResponse.json({ error: "Could not save — check the exam type + year combination" }, { status: 400 });
    await audit({ schoolId, actor, action: body.id ? "UPDATE" : "CREATE", entityType: "WaecCentre", entityId: row.id, entityLabel: `${row.examType} ${row.examYear} · ${row.centreNumber}`, summary: `WAEC centre saved: ${row.examType} ${row.examYear} at ${row.centreName} (${row.centreNumber})`, req });
    return NextResponse.json({ centre: row });
  }

  if (action === "delete-centre") {
    const row = await db.waecCentre.findFirst({ where: { id: String(body.id ?? ""), schoolId }, include: { _count: { select: { candidacies: true } } } });
    if (!row) return NextResponse.json({ error: "Centre not found" }, { status: 404 });
    await db.waecCentre.delete({ where: { id: row.id } });
    await audit({ schoolId, actor, action: "DELETE", entityType: "WaecCentre", entityId: row.id, entityLabel: `${row.examType} ${row.examYear}`, summary: `WAEC centre deleted: ${row.examType} ${row.examYear} (${row._count.candidacies} candidacies removed)`, req });
    return NextResponse.json({ ok: true });
  }

  // ── candidacy lifecycle ─────────────────────────────────────────
  const centre = body.centreId ? await db.waecCentre.findFirst({ where: { id: String(body.centreId), schoolId } }) : null;
  if (!centre && ["register-bulk", "assign-indexes", "invoice-fees"].includes(action)) {
    return NextResponse.json({ error: "Centre not found" }, { status: 404 });
  }

  if (action === "register-bulk") {
    const studentIds = [...new Set((Array.isArray(body.studentIds) ? body.studentIds : []).map(String))].filter(Boolean);
    if (!studentIds.length) return NextResponse.json({ error: "Select at least one learner" }, { status: 400 });
    const enrolled = await db.enrollment.findMany({
      where: { schoolId, studentId: { in: studentIds }, status: "ACTIVE" },
      include: { classroom: { select: { name: true } }, student: { select: { id: true, firstName: true, lastName: true } } },
    });
    if (!enrolled.length) return NextResponse.json({ error: "None of the selected learners are actively enrolled" }, { status: 400 });
    const existing = new Set((await db.waecCandidacy.findMany({ where: { centreId: centre!.id }, select: { studentId: true } })).map((c) => c.studentId));
    let serial = (await db.waecCandidacy.aggregate({ where: { centreId: centre!.id }, _max: { serialNo: true } }))._max.serialNo ?? 0;
    let created = 0, skipped = 0;
    for (const e of enrolled) {
      if (existing.has(e.student.id)) { skipped++; continue; }
      serial += 1;
      await db.waecCandidacy.create({ data: { centreId: centre!.id, studentId: e.student.id, serialNo: serial } });
      created++;
    }
    await audit({ schoolId, actor, action: "CREATE", entityType: "WaecCandidacy", entityId: centre!.id, entityLabel: `${centre!.examType} ${centre!.examYear}`, summary: `Registered ${created} WAEC candidate(s) for ${centre!.examType} ${centre!.examYear}${skipped ? `, ${skipped} already on the roll` : ""}`, req });
    return NextResponse.json({ created, skipped });
  }

  if (action === "set-status" || action === "withdraw" || action === "reinstate") {
    const row = await db.waecCandidacy.findFirst({ where: { id: String(body.id ?? ""), centre: { schoolId } }, include: { student: { select: { firstName: true, lastName: true } } } });
    if (!row) return NextResponse.json({ error: "Candidacy not found" }, { status: 404 });
    const next = action === "withdraw" ? "WITHDRAWN" : action === "reinstate" ? (row.indexNumber ? "INDEXED" : "REGISTERED") : CANDIDACY_STATUSES.includes(body.status) ? body.status : row.status;
    await db.waecCandidacy.update({ where: { id: row.id }, data: { status: next } });
    await audit({ schoolId, actor, action: "UPDATE", entityType: "WaecCandidacy", entityId: row.id, entityLabel: `${row.student.lastName} ${row.student.firstName}`, summary: `WAEC candidacy status → ${next} (${row.student.lastName} ${row.student.firstName})`, req });
    return NextResponse.json({ ok: true });
  }

  if (action === "assign-indexes") {
    const rows = await db.waecCandidacy.findMany({ where: { centreId: centre!.id, status: { not: "WITHDRAWN" }, OR: [{ indexNumber: null }, { indexNumber: "" }] }, orderBy: { serialNo: "asc" } });
    // a manually-typed index must never be re-minted by the auto-assigner
    const taken = new Set((await db.waecCandidacy.findMany({ where: { centreId: centre!.id, indexNumber: { not: null } }, select: { indexNumber: true } })).map((r) => r.indexNumber!));
    let assigned = 0, clashed = 0;
    for (const r of rows) {
      const idx = buildIndex(centre!.centreNumber, r.serialNo);
      if (taken.has(idx)) { clashed++; continue; }
      taken.add(idx);
      await db.waecCandidacy.update({ where: { id: r.id }, data: { indexNumber: idx, status: r.status === "REGISTERED" ? "INDEXED" : r.status } });
      assigned++;
    }
    await audit({ schoolId, actor, action: "UPDATE", entityType: "WaecCandidacy", entityId: centre!.id, entityLabel: `${centre!.examType} ${centre!.examYear}`, summary: `Assigned ${assigned} WAEC index number(s) under centre ${centre!.centreNumber}${clashed ? `, ${clashed} skipped (manual clash)` : ""}`, req });
    return NextResponse.json({ assigned, clashed });
  }

  if (action === "set-index") {
    const row = await db.waecCandidacy.findFirst({ where: { id: String(body.id ?? ""), centre: { schoolId } }, include: { student: { select: { firstName: true, lastName: true } } } });
    if (!row) return NextResponse.json({ error: "Candidacy not found" }, { status: 404 });
    const idx = String(body.indexNumber ?? "").replace(/\s/g, "");
    if (!idx) return NextResponse.json({ error: "Index number cannot be empty" }, { status: 400 });
    const clash = await db.waecCandidacy.findFirst({ where: { centreId: row.centreId, indexNumber: idx, id: { not: row.id } } });
    if (clash) return NextResponse.json({ error: "That index number already belongs to another candidate" }, { status: 400 });
    await db.waecCandidacy.update({ where: { id: row.id }, data: { indexNumber: idx, status: row.status === "REGISTERED" ? "INDEXED" : row.status } });
    await audit({ schoolId, actor, action: "UPDATE", entityType: "WaecCandidacy", entityId: row.id, entityLabel: `${row.student.lastName} ${row.student.firstName}`, summary: `WAEC index set to ${idx} (${row.student.lastName} ${row.student.firstName})`, req });
    return NextResponse.json({ ok: true });
  }

  // ── subject entries ─────────────────────────────────────────────
  if (action === "save-subjects") {
    const row = await db.waecCandidacy.findFirst({ where: { id: String(body.id ?? ""), centre: { schoolId } }, include: { student: { select: { firstName: true, lastName: true } } } });
    if (!row) return NextResponse.json({ error: "Candidacy not found" }, { status: 404 });
    const list = (Array.isArray(body.subjects) ? body.subjects : [])
      .map((s: any, i: number) => ({ subjectName: String(s?.subjectName ?? "").trim(), subjectCode: s?.subjectCode ? String(s.subjectCode) : null, isCore: Boolean(s?.isCore), sortOrder: i }))
      .filter((s: any) => s.subjectName);
    const names = new Set(list.map((s: any) => s.subjectName.toUpperCase()));
    if (names.size !== list.length) return NextResponse.json({ error: "Duplicate subject names in the entry" }, { status: 400 });
    await db.waecSubjectEntry.deleteMany({ where: { candidacyId: row.id } });
    for (const s of list) await db.waecSubjectEntry.create({ data: { candidacyId: row.id, ...s } });
    await audit({ schoolId, actor, action: "UPDATE", entityType: "WaecCandidacy", entityId: row.id, entityLabel: `${row.student.lastName} ${row.student.firstName}`, summary: `Subject entries saved: ${list.length} subject(s) for ${row.student.lastName} ${row.student.firstName}`, req });
    return NextResponse.json({ count: list.length });
  }

  if (action === "copy-class-offering") {
    const row = await db.waecCandidacy.findFirst({
      where: { id: String(body.id ?? ""), centre: { schoolId } },
      include: { student: { select: { firstName: true, lastName: true, enrollments: { where: { status: "ACTIVE" }, take: 1, select: { classroomId: true } } } } },
    });
    if (!row) return NextResponse.json({ error: "Candidacy not found" }, { status: 404 });
    const classroomId = row.student.enrollments[0]?.classroomId ?? null;
    // source the class offering: timetable slots → class assessments →
    // school core subjects (a fresh term's timetable grid may be empty)
    let offering = classroomId
      ? await db.timetableSlot.findMany({ where: { classroomId, subjectId: { not: null } }, select: { subject: { select: { name: true, code: true, isCore: true } } }, distinct: ["subjectId"] })
      : [];
    if (!offering.length && classroomId) offering = await db.assessment.findMany({ where: { classroomId }, select: { subject: { select: { name: true, code: true, isCore: true } } }, distinct: ["subjectId"] });
    if (!offering.length) offering = (await db.subject.findMany({ where: { schoolId, isCore: true }, orderBy: { name: "asc" }, take: 10, select: { name: true, code: true, isCore: true } })).map((s) => ({ subject: s }));
    if (!offering.length) return NextResponse.json({ error: "No subject offering found for that class yet — add subjects manually" }, { status: 400 });
    const seen = new Set<string>();
    const list = offering.map((o) => o.subject).filter((s) => (seen.has(s.name.toUpperCase()) ? false : seen.add(s.name.toUpperCase())));
    await db.waecSubjectEntry.deleteMany({ where: { candidacyId: row.id } });
    for (const [i, s] of list.entries()) await db.waecSubjectEntry.create({ data: { candidacyId: row.id, subjectName: s.name, subjectCode: s.code, isCore: s.isCore, sortOrder: i } });
    await audit({ schoolId, actor, action: "UPDATE", entityType: "WaecCandidacy", entityId: row.id, entityLabel: `${row.student.lastName} ${row.student.firstName}`, summary: `Copied ${list.length} class subject(s) into WAEC entries for ${row.student.lastName} ${row.student.firstName}`, req });
    return NextResponse.json({ count: list.length, subjects: list.map((s) => ({ subjectName: s.name, subjectCode: s.code, isCore: s.isCore })) });
  }

  // ── registration-fee invoicing (bulk, current term) ─────────────
  if (action === "invoice-fees") {
    if (centre!.regFeeCents <= 0) return NextResponse.json({ error: "Set a registration fee amount on the centre first" }, { status: 400 });
    const title = String(body.itemTitle ?? `${centre!.examType} ${centre!.examYear} registration fee`).toUpperCase();
    const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } } });
    if (!term) return NextResponse.json({ error: "No current billing term found" }, { status: 400 });
    const cands = await db.waecCandidacy.findMany({
      where: { centreId: centre!.id, feeInvoiced: false, status: { in: ["REGISTERED", "INDEXED", "SUBMITTED"] } },
      include: { student: { select: { id: true } } },
    });
    if (!cands.length) return NextResponse.json({ error: "Every active candidate is already billed" }, { status: 400 });
    const enrollments = await db.enrollment.findMany({
      where: { schoolId, studentId: { in: cands.map((c) => c.studentId) }, status: "ACTIVE" },
      include: { classroom: { select: { name: true } } },
    });
    const classByStudent = new Map(enrollments.map((e) => [e.studentId, e.classroom.name]));
    let created = 0, updated = 0;
    for (const c of cands) {
      const className = classByStudent.get(c.studentId) ?? "—";
      const existing = await db.invoice.findFirst({
        where: { schoolId, termId: term.id, studentId: c.studentId },
        include: { items: true },
      });
      if (existing) {
        if (existing.items.some((i) => i.name === title)) { await db.waecCandidacy.update({ where: { id: c.id }, data: { feeInvoiced: true } }); updated++; continue; }
        await db.invoiceItem.create({ data: { invoiceId: existing.id, name: title, amountCents: centre!.regFeeCents } });
        const total = existing.totalCents + centre!.regFeeCents;
        await db.invoice.update({
          where: { id: existing.id },
          data: { totalCents: total, status: existing.paidCents >= total - existing.discountCents ? existing.status : existing.status === "PAID" ? "PARTIAL" : existing.status },
        });
        updated++;
      } else {
        const inv = await db.invoice.create({
          data: { invoiceNo: await nextInvoiceNo(), studentId: c.studentId, schoolId, termId: term.id, classroomName: className, totalCents: centre!.regFeeCents, status: "UNPAID", dueDate: new Date(Date.now() + 14 * 864e5) },
        });
        await db.invoiceItem.create({ data: { invoiceId: inv.id, name: title, amountCents: centre!.regFeeCents } });
        created++;
      }
      await db.waecCandidacy.update({ where: { id: c.id }, data: { feeInvoiced: true } });
    }
    await audit({ schoolId, actor, action: "CREATE", entityType: "Invoice", entityId: centre!.id, entityLabel: `${centre!.examType} ${centre!.examYear}`, summary: `WAEC registration fees billed: ${created} invoice(s) created, ${updated} amended (${title})`, req });
    return NextResponse.json({ created, updated, title, term: term.name });
  }

  // ── mock results ────────────────────────────────────────────────
  if (action === "save-mock") {
    const row = await db.waecCandidacy.findFirst({
      where: { id: String(body.candidacyId ?? ""), centre: { schoolId } },
      include: { student: { select: { firstName: true, lastName: true } } },
    });
    if (!row) return NextResponse.json({ error: "Candidacy not found" }, { status: 404 });
    const mockNo = Math.min(3, Math.max(1, Math.round(num(body.mockNo, 1))));
    const bands = await db.rawScoreGrade.findMany({ where: { schoolId }, orderBy: { sortOrder: "asc" } });
    const rows = (Array.isArray(body.rows) ? body.rows : [])
      .map((r: any) => {
        const objective = r.objective === "" || r.objective == null ? null : Math.max(0, num(r.objective));
        const theory = r.theory === "" || r.theory == null ? null : Math.max(0, num(r.theory));
        const practical = r.practical === "" || r.practical == null ? null : Math.max(0, num(r.practical));
        const maxScore = Math.max(1, num(r.maxScore, 100));
        const totalRaw = (objective ?? 0) + (theory ?? 0) + (practical ?? 0);
        const hasAny = objective != null || theory != null || practical != null;
        const total = hasAny ? Math.round(totalRaw * 100) / 100 : null;
        const g = total != null ? gradeFor(bands, pct(total, maxScore)) : null;
        return { subjectName: String(r.subjectName ?? "").trim(), objective, theory, practical, maxScore, total, grade: g?.grade ?? null, gradePoint: g?.gradePoint ?? null };
      })
      .filter((r: any) => r.subjectName);
    if (!rows.length) return NextResponse.json({ error: "No subject rows to save" }, { status: 400 });
    for (const r of rows) {
      await db.waecMockResult.upsert({
        where: { candidacyId_subjectName_mockNo: { candidacyId: row.id, subjectName: r.subjectName, mockNo } },
        create: { candidacyId: row.id, mockNo, ...r },
        update: r,
      });
    }
    await audit({ schoolId, actor, action: "UPDATE", entityType: "WaecMockResult", entityId: row.id, entityLabel: `${row.student.lastName} ${row.student.firstName} · Mock ${mockNo}`, summary: `Mock ${mockNo} saved: ${rows.length} subject(s) for ${row.student.lastName} ${row.student.firstName}`, req });
    return NextResponse.json({ count: rows.length });
  }

  if (action === "delete-mock") {
    const row = await db.waecCandidacy.findFirst({ where: { id: String(body.candidacyId ?? ""), centre: { schoolId } }, include: { student: { select: { firstName: true, lastName: true } } } });
    if (!row) return NextResponse.json({ error: "Candidacy not found" }, { status: 404 });
    const mockNo = Math.min(3, Math.max(1, Math.round(num(body.mockNo, 1))));
    await db.waecMockResult.deleteMany({ where: { candidacyId: row.id, mockNo } });
    await audit({ schoolId, actor, action: "DELETE", entityType: "WaecMockResult", entityId: row.id, entityLabel: `${row.student.lastName} ${row.student.firstName} · Mock ${mockNo}`, summary: `Mock ${mockNo} cleared for ${row.student.lastName} ${row.student.firstName}`, req });
    return NextResponse.json({ ok: true });
  }

  return NextResponse.json({ error: `Unknown action: ${action}` }, { status: 400 });
}

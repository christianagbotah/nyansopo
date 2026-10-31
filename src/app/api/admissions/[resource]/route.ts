import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser, canOperateSchool } from "@/lib/session";
import { phoneVariants } from "../../registry/route";

// GET /api/admissions/[resource]?schoolId=...
export async function GET(req: NextRequest, { params }: { params: Promise<{ resource: string }> }) {
  const { resource } = await params;
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
  const schoolId = new URL(req.url).searchParams.get("schoolId") ?? user.schoolIds[0];
  if (!schoolId) return NextResponse.json({ error: "No school in scope" }, { status: 400 });

  if (resource === "inquiries") {
    const inquiries = await db.admissionInquiry.findMany({ where: { schoolId }, orderBy: { createdAt: "desc" }, include: { matchedStudent: { include: { flags: true } } } });
    return NextResponse.json({
      inquiries: inquiries.map((i) => ({
        id: i.id, studentName: i.studentName, gender: i.gender, dob: i.dateOfBirth,
        guardianName: i.guardianName, guardianPhone: i.guardianPhone, classAppliedFor: i.classAppliedFor,
        status: i.status, createdAt: i.createdAt, handledByName: i.handledByName, notes: i.notes,
        registryCheckedAt: i.registryCheckedAt, registrySummary: i.registrySummary ? JSON.parse(i.registrySummary) : null,
        matchedStudentId: i.matchedStudentId,
        matchedStudentName: i.matchedStudent ? `${i.matchedStudent.firstName} ${i.matchedStudent.lastName}` : null,
        matchedFlags: i.matchedStudent?.flags.filter((f) => f.status === "ACTIVE" || f.status === "CONTESTED").map((f) => ({ type: f.type, severity: f.severity, status: f.status, amountOwed: f.amountOwedCents })) ?? [],
      })),
    });
  }

  if (resource === "requests") {
    const inbox = await db.recordRequest.findMany({ where: { targetSchoolId: schoolId }, include: { student: true, requestingSchool: { select: { id: true, name: true, town: true } }, messages: true }, orderBy: { requestedAt: "desc" } });
    const outbox = await db.recordRequest.findMany({ where: { requestingSchoolId: schoolId }, include: { student: true, targetSchool: { select: { id: true, name: true, town: true } }, messages: true }, orderBy: { requestedAt: "desc" } });
    const fmt = (r: any, dir: "in" | "out") => ({
      id: r.id, direction: dir, student: `${r.student.firstName} ${r.student.lastName}`, studentId: r.studentId,
      school: dir === "in" ? r.requestingSchool : r.targetSchool, status: r.status, purpose: r.purpose,
      requestedAt: r.requestedAt, requestedByName: r.requestedByName, notes: r.notes,
      respondedByName: r.respondedByName, respondedAt: r.respondedAt,
      messages: r.messages.map((m: any) => ({ senderName: m.senderName, senderTitle: m.senderTitle, body: m.body, sentAt: m.sentAt })),
    });
    return NextResponse.json({ inbox: inbox.map((r) => fmt(r, "in")), outbox: outbox.map((r) => fmt(r, "out")) });
  }

  if (resource === "transfers") {
    const out = await db.transferRecord.findMany({ where: { fromSchoolId: schoolId }, include: { student: true, toSchool: { select: { id: true, name: true } } }, orderBy: { date: "desc" } });
    const inn = await db.transferRecord.findMany({ where: { toSchoolId: schoolId }, include: { student: true, fromSchool: { select: { id: true, name: true } } }, orderBy: { date: "desc" } });
    return NextResponse.json({
      outgoing: out.map((t) => ({ id: t.id, student: `${t.student.firstName} ${t.student.lastName}`, studentId: t.studentId, to: t.toSchool, date: t.date, status: t.status, outstanding: t.outstandingCents, reason: t.reason, documentsReleased: t.documentsReleased, notes: t.notes })),
      incoming: inn.map((t) => ({ id: t.id, student: `${t.student.firstName} ${t.student.lastName}`, studentId: t.studentId, from: t.fromSchool, date: t.date, status: t.status, outstanding: t.outstandingCents, reason: t.reason })),
    });
  }
  return NextResponse.json({ error: "Unknown resource" }, { status: 404 });
}

// POST /api/admissions/[resource]
export async function POST(req: NextRequest, { params }: { params: Promise<{ resource: string }> }) {
  const { resource } = await params;
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
  const body = await req.json();

  if (resource === "inquiries") {
    if (body.action === "create") {
      const schoolId = body.schoolId ?? user.schoolIds[0];
      if (!schoolId || !canOperateSchool(user, schoolId)) return NextResponse.json({ error: "Forbidden" }, { status: 403 });
      // auto registry check
      const q = (body.guardianPhone ?? "").replace(/\s+/g, "");
      const names = (body.studentName ?? "").toLowerCase().split(" ").filter(Boolean);
      let match: any = null;
      const vars = phoneVariants(body.guardianPhone ?? "");
      if (vars.length) {
        match = await db.student.findFirst({ where: { OR: vars.map((v) => ({ guardianPhone: { contains: v } })) }, include: { flags: true, enrollments: { include: { school: true } } } });
      }
      if (!match && names.length >= 2) {
        match = await db.student.findFirst({ where: { AND: [{ firstName: { contains: cap(names[0]) } }, { lastName: { contains: cap(names[names.length - 1]) } }] }, include: { flags: true, enrollments: { include: { school: true } } } });
      }
      const activeFlags = match?.flags.filter((f) => f.status === "ACTIVE" || f.status === "CONTESTED") ?? [];
      const status = match ? (activeFlags.length ? "FLAGGED" : "CLEARED") : "NEW";
      const summary = match
        ? { matched: true, nyansapoIndex: match.nyansapoIndex, priorSchools: match.enrollments.length, activeFlags: activeFlags.length, flagSummary: activeFlags.map((f: any) => `${f.type} — GH₵${((f.amountOwedCents ?? 0) / 100).toFixed(2)} (${f.severity})`).join("; ") || "None" }
        : { matched: false };
      const inq = await db.admissionInquiry.create({
        data: { schoolId, studentName: body.studentName, gender: body.gender, dateOfBirth: body.dateOfBirth ? new Date(body.dateOfBirth) : null, guardianName: body.guardianName, guardianPhone: body.guardianPhone, guardianEmail: body.guardianEmail, classAppliedFor: body.classAppliedFor, matchedStudentId: match?.id ?? null, registryCheckedAt: new Date(), registrySummary: JSON.stringify(summary), status, handledByName: user.fullName },
      });
      return NextResponse.json({ inquiry: inq, match: match ? { id: match.id, name: `${match.firstName} ${match.lastName}`, nyansapoIndex: match.nyansapoIndex, activeFlags } : null });
    }
    if (body.action === "decline") {
      const inq = await db.admissionInquiry.update({ where: { id: body.inquiryId }, data: { status: "DECLINED", notes: body.note ?? "Application declined." } });
      return NextResponse.json({ inquiry: inq });
    }
  }

  if (resource === "requests") {
    if (body.action === "create") {
      const { studentId, targetSchoolId, purpose, notes } = body;
      const requestingSchoolId = body.requestingSchoolId ?? user.schoolIds[0];
      if (!requestingSchoolId || !canOperateSchool(user, requestingSchoolId)) return NextResponse.json({ error: "Forbidden" }, { status: 403 });
      const student = await db.student.findUnique({ where: { id: studentId } });
      if (!student) return NextResponse.json({ error: "Student not found" }, { status: 404 });
      const dup = await db.recordRequest.findFirst({ where: { studentId, targetSchoolId, status: "PENDING" } });
      if (dup) return NextResponse.json({ request: dup, duplicate: true });
      const rr = await db.recordRequest.create({ data: { studentId, requestingSchoolId, targetSchoolId, requestedByName: `${user.fullName} (${user.title ?? "Staff"})`, purpose: purpose ?? "ADMISSION", notes } });
      await db.requestMessage.create({ data: { requestId: rr.id, senderName: user.fullName, senderTitle: user.title ?? "Staff", body: notes ?? `Requesting academic records and fee position for ${student.firstName} ${student.lastName}.` } });
      return NextResponse.json({ request: rr });
    }
    if (body.action === "respond") {
      const { requestId, decision, note } = body; // decision: APPROVE | DECLINE | FULFILLED
      const rr = await db.recordRequest.findUnique({ where: { id: requestId } });
      if (!rr) return NextResponse.json({ error: "Request not found" }, { status: 404 });
      if (!canOperateSchool(user, rr.targetSchoolId)) return NextResponse.json({ error: "Forbidden — only the target school can respond" }, { status: 403 });
      await db.recordRequest.update({ where: { id: requestId }, data: { status: decision, respondedAt: new Date(), respondedByName: user.fullName } });
      await db.requestMessage.create({ data: { requestId, senderName: user.fullName, senderTitle: user.title ?? "Staff", body: note ?? (decision === "DECLINE" ? "Request declined." : "Records released to the requesting school.") } });
      return NextResponse.json({ ok: true });
    }
  }
  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

function cap(s: string) { return s.charAt(0).toUpperCase() + s.slice(1); }

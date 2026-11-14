import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

// ── Certificates & Documents (wave 30 — the headteacher's signature drawer) ──
// Serial-numbered official documents: School Leaving Certificate (SLC),
// Testimonial (TSM), Admission Letter (ADL) and Introductory Letter (INT).
// Particulars (name / index / class / session / DOB) are snapshotted at issue
// time so a printed document never changes after rollover or graduation.
// Serials run per school + type + issue year: SLC-2026-0001, TSM-2026-0007…

const DOC_TYPES = ["LEAVING_CERT", "TESTIMONIAL", "ADMISSION_LETTER", "INTRO_LETTER"];
const TYPE_PREFIX: Record<string, string> = {
  LEAVING_CERT: "SLC",
  TESTIMONIAL: "TSM",
  ADMISSION_LETTER: "ADL",
  INTRO_LETTER: "INT",
};
const DOC_STATUSES = ["ISSUED", "REVOKED"];

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });
const optStr = (v: unknown): string | null => {
  if (typeof v !== "string") return null;
  const t = v.trim();
  return t.length ? t : null;
};
const enumVal = (v: unknown, allowed: string[]): string | null => {
  if (typeof v !== "string" || !v.trim()) return null;
  const u = v.trim().toUpperCase();
  const hit = allowed.find((a) => a.toUpperCase() === u);
  return hit ?? null;
};
const dayStart = (v: unknown): Date | null => {
  if (typeof v !== "string" || !/^\d{4}-\d{2}-\d{2}/.test(String(v).trim())) return null;
  const d = new Date(`${String(v).trim().slice(0, 10)}T00:00:00.000Z`);
  return isNaN(d.getTime()) ? null : d;
};
const isoDay = (d: Date | string | null | undefined): string | null => {
  if (!d) return null;
  const date = d instanceof Date ? d : new Date(d);
  return isNaN(date.getTime()) ? null : date.toISOString().slice(0, 10);
};

const fullName = (s: { firstName: string; lastName: string; otherNames: string | null }) =>
  [s.lastName, s.firstName, s.otherNames].filter(Boolean).join(" ").replace(/\s+/g, " ").trim();

const toDoc = (d: any) => ({
  id: d.id,
  type: d.type,
  serialNo: d.serialNo,
  issueDate: isoDay(d.issueDate),
  studentId: d.studentId,
  studentName: d.studentName,
  studentIndex: d.studentIndex,
  className: d.className,
  sessionYear: d.sessionYear,
  dateOfBirth: isoDay(d.dateOfBirth),
  reason: d.reason ?? null,
  remarks: d.remarks ?? null,
  status: d.status,
  issuedByName: d.issuedByName ?? null,
  revokedByName: d.revokedByName ?? null,
  revokedAt: d.revokedAt ? d.revokedAt.toISOString() : null,
  createdAt: d.createdAt.toISOString(),
});

/** Next sequential serial for this school + type + issue year (prefix-YYYY-####). */
async function nextSerial(schoolId: string, type: string, issueDate: Date): Promise<string> {
  const prefix = TYPE_PREFIX[type] ?? "DOC";
  const year = issueDate.getUTCFullYear();
  const rows = await db.issuedDocument.findMany({
    where: { schoolId, type, serialNo: { startsWith: `${prefix}-${year}-` } },
    select: { serialNo: true },
  });
  const max = rows.reduce((m, r) => Math.max(m, parseInt(r.serialNo.split("-").pop() ?? "0", 10) || 0), 0);
  return `${prefix}-${year}-${String(max + 1).padStart(4, "0")}`;
}

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["certificates.view"] });
  if (!auth.ok) return auth.res;

  const url = new URL(req.url);
  const type = enumVal(url.searchParams.get("type"), DOC_TYPES);
  const status = enumVal(url.searchParams.get("status"), DOC_STATUSES);
  const q = optStr(url.searchParams.get("q"))?.toLowerCase() ?? null;
  const limit = Math.min(parseInt(url.searchParams.get("limit") ?? "200", 10) || 200, 500);

  const where: any = { schoolId };
  if (type) where.type = type;
  if (status) where.status = status;

  const [rows, school, students, classrooms, years, all, issuedThisYear] = await Promise.all([
    db.issuedDocument.findMany({
      where,
      orderBy: [{ issueDate: "desc" }, { createdAt: "desc" }],
      take: limit,
    }),
    db.school.findUnique({
      where: { id: schoolId },
      select: { name: true, code: true, motto: true, region: true, district: true, town: true, logoColor: true, establishedYear: true, gnacopsStatus: true, nasiaRegNo: true },
    }),
    db.student.findMany({
      where: { status: "ACTIVE", enrollments: { some: { schoolId, status: "ACTIVE" } } },
      include: { enrollments: { where: { status: "ACTIVE", schoolId }, take: 1, include: { classroom: true } } },
      orderBy: [{ firstName: "asc" }, { lastName: "asc" }],
      take: 600,
    }),
    db.classroom.findMany({ where: { schoolId }, orderBy: [{ gradeLevel: "asc" }, { name: "asc" }], select: { id: true, name: true, gradeLevel: true } }),
    db.academicYear.findMany({ where: { schoolId }, orderBy: { startsAt: "desc" }, select: { id: true, name: true, isCurrent: true } }),
    db.issuedDocument.groupBy({ by: ["type", "status"], where: { schoolId }, _count: { _all: true } }),
    db.issuedDocument.count({
      where: { schoolId, issueDate: { gte: new Date(Date.UTC(new Date().getUTCFullYear(), 0, 1)) } },
    }),
  ]);

  // type + status mixes for the register's filter chips & stat cards
  const byType = DOC_TYPES.map((t) => {
    const issued = all.find((r) => r.type === t && r.status === "ISSUED")?._count._all ?? 0;
    const revoked = all.find((r) => r.type === t && r.status === "REVOKED")?._count._all ?? 0;
    return { type: t, issued, revoked, total: issued + revoked };
  });

  const docs = rows.filter((r) => {
    if (!q) return true;
    return (
      r.studentName.toLowerCase().includes(q) ||
      r.serialNo.toLowerCase().includes(q) ||
      r.className.toLowerCase().includes(q) ||
      (r.reason ?? "").toLowerCase().includes(q) ||
      (r.remarks ?? "").toLowerCase().includes(q)
    );
  });

  return NextResponse.json({
    school,
    docs: docs.map(toDoc),
    stats: {
      total: byType.reduce((n, t) => n + t.total, 0),
      issued: byType.reduce((n, t) => n + t.issued, 0),
      revoked: byType.reduce((n, t) => n + t.revoked, 0),
      issuedThisYear,
      byType,
    },
    students: students.map((s) => ({
      id: s.id,
      name: fullName(s),
      index: s.nyansapoIndex,
      gender: s.gender,
      dateOfBirth: isoDay(s.dateOfBirth),
      guardianName: s.guardianName,
      className: s.enrollments[0]?.classroom?.name ?? null,
      classroomId: s.enrollments[0]?.classroomId ?? null,
      rollNo: s.enrollments[0]?.rollNo ?? null,
      status: s.status,
    })),
    classrooms,
    years: years.map((y) => ({ id: y.id, name: y.name, isCurrent: y.isCurrent })),
  });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["certificates.manage"] });
  if (!auth.ok) return auth.res;
  const actorName = auth.user.fullName;
  const body = await req.json().catch(() => null);
  const action = typeof body?.action === "string" ? body.action.toUpperCase() : null;

  /* ── issue: create a document with the next serial for its type ── */
  if (action === "ISSUE") {
    const type = enumVal(body?.type, DOC_TYPES);
    const studentId = optStr(body?.studentId);
    const issueDate = dayStart(body?.issueDate) ?? new Date(`${new Date().toISOString().slice(0, 10)}T00:00:00.000Z`);
    const className = optStr(body?.className);
    const sessionYear = optStr(body?.sessionYear);
    if (!type) return bad("type is required (LEAVING_CERT / TESTIMONIAL / ADMISSION_LETTER / INTRO_LETTER)");
    if (!studentId) return bad("studentId is required");
    if (!className) return bad("className is required");
    if (!sessionYear) return bad("sessionYear is required");

    const student = await db.student.findUnique({
      where: { id: studentId },
      select: {
        id: true, firstName: true, lastName: true, otherNames: true, nyansapoIndex: true, dateOfBirth: true,
        enrollments: { where: { schoolId, status: "ACTIVE" }, select: { id: true }, take: 1 },
      },
    });
    if (!student || !student.enrollments.length) return bad("Student not found for this school", 404);

    const serialNo = await nextSerial(schoolId, type, issueDate);
    try {
      const row = await db.issuedDocument.create({
        data: {
          schoolId,
          studentId,
          type,
          serialNo,
          issueDate,
          studentName: fullName(student),
          studentIndex: student.nyansapoIndex,
          className,
          sessionYear,
          dateOfBirth: student.dateOfBirth,
          reason: optStr(body?.reason),
          remarks: optStr(body?.remarks),
          issuedByName: actorName,
        },
      });
      return NextResponse.json({ ok: true, doc: toDoc(row) });
    } catch (e: any) {
      if (e?.code === "P2002") return bad(`Serial ${serialNo} already exists — retry to draw the next number`, 409);
      throw e;
    }
  }

  /* ── revoke / reinstate / delete / update operate on an existing row ── */
  const docId = optStr(body?.docId);
  if (!docId) return bad("docId is required");
  const doc = await db.issuedDocument.findUnique({ where: { id: docId } });
  if (!doc || doc.schoolId !== schoolId) return bad("Document not found", 404);

  if (action === "REVOKE") {
    if (doc.status === "REVOKED") return bad("Document is already revoked", 409);
    const row = await db.issuedDocument.update({
      where: { id: docId },
      data: { status: "REVOKED", revokedByName: actorName, revokedAt: new Date() },
    });
    return NextResponse.json({ ok: true, doc: toDoc(row) });
  }

  if (action === "REINSTATE") {
    if (doc.status === "ISSUED") return bad("Document is already active", 409);
    const row = await db.issuedDocument.update({
      where: { id: docId },
      data: { status: "ISSUED", revokedByName: null, revokedAt: null },
    });
    return NextResponse.json({ ok: true, doc: toDoc(row) });
  }

  if (action === "DELETE") {
    await db.issuedDocument.delete({ where: { id: docId } });
    return NextResponse.json({ ok: true });
  }

  if (action === "UPDATE") {
    const data: any = {};
    const issueDate = dayStart(body?.issueDate);
    if (issueDate) data.issueDate = issueDate;
    const className = optStr(body?.className);
    if (className) data.className = className;
    const sessionYear = optStr(body?.sessionYear);
    if (sessionYear) data.sessionYear = sessionYear;
    if ("reason" in (body ?? {})) data.reason = optStr(body?.reason);
    if ("remarks" in (body ?? {})) data.remarks = optStr(body?.remarks);
    if (!Object.keys(data).length) return bad("Nothing to update");
    const row = await db.issuedDocument.update({ where: { id: docId }, data });
    return NextResponse.json({ ok: true, doc: toDoc(row) });
  }

  return bad("Unknown action", 400);
}

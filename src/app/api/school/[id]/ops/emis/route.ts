import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { audit } from "@/lib/audit";

// ── GES EMIS ANNUAL RETURN (wave 58 — the school census) ──
// GET  ?year= — computed census preview (live figures) + the archive of
//               filed returns; ?year=YYYY returns that filed snapshot
//               instead of the live computation.
// POST file · unfile
//
// The census mirrors the GES EMIS school-data form: identity, enrolment
// by class & gender, staffing, facilities, attendance, finance and the
// WAEC candidacy picture. Filing snapshots everything into EmisReturn
// (unique per school + census year) so history stays immutable.

/** Level band for an EMIS rollup (KG → Primary → JHS → Senior → other). */
function bandOf(grade: string): string {
  const g = grade.trim().toUpperCase();
  if (/^(CRECHE|NURSERY|CR|PLAY)/.test(g) || /^B0/.test(g)) return "Creche & Nursery";
  if (/KG/.test(g)) return "Kindergarten (KG)";
  if (/^B[1-6]$/.test(g) || /^YEAR [1-6]$/.test(g) || /^Y[1-6]$/.test(g) || /^PYP [1-6]$/.test(g)) return "Primary (B1–B6)";
  if (/^B[7-9]$/.test(g) || /^JHS/.test(g)) return "Junior High (B7–B9)";
  if (/^YEAR 1[0-3]$/.test(g) || /^Y1[0-3]$/.test(g) || /MYP [45]/.test(g)) return "Senior (SHS)";
  return "Other classes";
}

/** Compute the live census snapshot for the school. */
async function computeCensus(schoolId: string) {
  const school = await db.school.findUnique({ where: { id: schoolId } });
  if (!school) return null;

  const [classrooms, enrollments, staff, term, year, beds, books, houses, subjects, invoices, waecCentres] = await Promise.all([
    db.classroom.findMany({ where: { schoolId }, orderBy: { name: "asc" }, select: { id: true, name: true, gradeLevel: true, stageLabel: true, capacity: true } }),
    db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, include: { student: { select: { gender: true } }, classroom: { select: { name: true, gradeLevel: true } } } }),
    db.staff.findMany({ where: { schoolId }, select: { category: true, sex: true, qualification: true, ssnitNumber: true, active: true } }),
    db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId } }, include: { academicYear: true } }),
    db.academicYear.findFirst({ where: { schoolId, isCurrent: true } }),
    db.bed.count({ where: { schoolId } }),
    db.libraryBook.aggregate({ where: { schoolId }, _sum: { copies: true }, _count: true }),
    db.boardingHouse.findMany({ where: { schoolId }, select: { id: true, name: true, capacity: true } }),
    db.subject.count({ where: { schoolId } }),
    db.invoice.aggregate({ where: { schoolId, term: { isCurrent: true, academicYear: { schoolId } } }, _sum: { totalCents: true, paidCents: true }, _count: true }),
    db.waecCentre.findMany({ where: { schoolId }, include: { _count: { select: { candidacies: true } } } }),
  ]);

  // ── enrolment by class & gender ──
  const byClass = classrooms.map((c) => {
    const rows = enrollments.filter((e) => e.classroomId === c.id);
    const boys = rows.filter((e) => e.student.gender === "M").length;
    return { classroom: c.name, gradeLevel: c.gradeLevel, band: bandOf(c.gradeLevel), capacity: c.capacity, boys, girls: rows.length - boys, total: rows.length };
  }).filter((r) => r.total > 0 || r.capacity > 0);
  const boys = enrollments.filter((e) => e.student.gender === "M").length;
  const bands = [...new Set(byClass.map((r) => r.band))].map((band) => {
    const rows = byClass.filter((r) => r.band === band);
    return { band, boys: rows.reduce((s, r) => s + r.boys, 0), girls: rows.reduce((s, r) => s + r.girls, 0), total: rows.reduce((s, r) => s + r.total, 0) };
  });

  // ── staffing ──
  const staffByCategory = ["TEACHING", "NON_TEACHING", "ADMINISTRATION"].map((cat) => {
    const rows = staff.filter((s) => s.category === cat);
    const male = rows.filter((s) => s.sex === "M").length;
    const female = rows.filter((s) => s.sex === "F").length;
    return { category: cat, male, female, other: rows.length - male - female, total: rows.length };
  });
  const qualifications = Object.entries(
    staff.filter((s) => s.category === "TEACHING").reduce<Record<string, number>>((acc, s) => {
      const q = (s.qualification ?? "Not captured").trim() || "Not captured";
      acc[q] = (acc[q] ?? 0) + 1;
      return acc;
    }, {}),
  ).sort((a, b) => b[1] - a[1]);
  const teachingCount = staffByCategory.find((c) => c.category === "TEACHING")?.total ?? 0;

  // ── attendance over the current term ──
  let attendance: { rate: number | null; marked: number; presentIsh: number; sessions: number } = { rate: null, marked: 0, presentIsh: 0, sessions: 0 };
  if (term) {
    const sessions = await db.attendanceSession.findMany({ where: { termId: term.id, schoolId }, select: { id: true } });
    const recs = sessions.length
      ? await db.attendanceRecord.groupBy({ by: ["status"], where: { sessionId: { in: sessions.map((s) => s.id) } }, _count: true })
      : [];
    const marked = recs.reduce((s, r) => s + r._count, 0);
    const presentIsh = recs.filter((r) => r.status === "PRESENT" || r.status === "LATE").reduce((s, r) => s + r._count, 0);
    attendance = { rate: marked ? Math.round((presentIsh / marked) * 1000) / 10 : null, marked, presentIsh, sessions: sessions.length };
  }

  const billed = invoices._sum.totalCents ?? 0;
  const collected = invoices._sum.paidCents ?? 0;

  return {
    identity: {
      name: school.name, code: school.code, motto: school.motto, category: school.category,
      curriculum: school.curricula ?? school.curriculum, region: school.region, district: school.district,
      town: school.town, gpsAddress: school.gpsAddress, phone: school.phone, email: school.email,
      establishedYear: school.establishedYear,
    },
    calendar: {
      academicYear: year?.name ?? term?.academicYear.name ?? null, currentTerm: term?.name ?? null,
      termWindow: term ? { startsAt: term.startsAt, endsAt: term.endsAt } : null,
    },
    enrolment: {
      byClass, bands,
      totals: { boys, girls: enrollments.length - boys, total: enrollments.length },
      classes: byClass.length,
    },
    staffing: {
      byCategory: staffByCategory, qualifications,
      total: staff.length,
      teaching: teachingCount,
      ssnitEnrolled: staff.filter((s) => !!s.ssnitNumber).length,
      studentTeacherRatio: teachingCount ? Math.round((enrollments.length / teachingCount) * 10) / 10 : null,
    },
    facilities: {
      classrooms: classrooms.length,
      classroomsWithCapacity: classrooms.filter((c) => c.capacity > 0).length,
      totalCapacity: classrooms.reduce((s, c) => s + c.capacity, 0),
      subjectsOffered: subjects,
      boardingHouses: houses.length,
      boardingBeds: beds,
      boardingCapacity: houses.reduce((s, h) => s + h.capacity, 0),
      libraryTitles: books._count,
      libraryCopies: books._sum.copies ?? 0,
    },
    attendance,
    finance: {
      currentTerm: term?.name ?? null,
      invoices: invoices._count,
      billedCents: billed,
      collectedCents: collected,
      collectionRate: billed ? Math.round((collected / billed) * 1000) / 10 : null,
    },
    waec: waecCentres.map((c) => ({ examType: c.examType, examYear: c.examYear, centreNumber: c.centreNumber, candidates: c._count.candidacies })),
    generatedAt: new Date().toISOString(),
  };
}

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["emis.manage"] });
  if (!auth.ok) return auth.res;

  const url = new URL(req.url);
  const yearParam = url.searchParams.get("year");

  const filed = await db.emisReturn.findMany({ where: { schoolId }, orderBy: { year: "desc" }, select: { id: true, year: true, filedByName: true, note: true, createdAt: true } });

  if (yearParam) {
    const row = await db.emisReturn.findUnique({ where: { schoolId_year: { schoolId, year: Math.round(Number(yearParam)) } } });
    if (!row) return NextResponse.json({ error: "No return filed for that year" }, { status: 404 });
    return NextResponse.json({ filed, snapshot: { ...JSON.parse(row.dataJson), filedAt: row.createdAt, filedByName: row.filedByName, note: row.note, year: row.year } });
  }

  const census = await computeCensus(schoolId);
  if (!census) return NextResponse.json({ error: "School not found" }, { status: 404 });
  return NextResponse.json({ filed, census });
}

export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["emis.manage"] });
  if (!auth.ok) return auth.res;
  const actor = { id: auth.user!.id, fullName: auth.user!.fullName ?? "EMIS officer", role: auth.user!.role };
  const body = await req.json().catch(() => ({}));
  const action = String(body.action ?? "");

  if (action === "file") {
    const year = Math.round(Number(body.year));
    if (!Number.isFinite(year) || year < 2000 || year > 2100) return NextResponse.json({ error: "Give a census year between 2000 and 2100" }, { status: 400 });
    const census = await computeCensus(schoolId);
    if (!census) return NextResponse.json({ error: "School not found" }, { status: 404 });
    const dataJson = JSON.stringify(census);
    const row = await db.emisReturn.upsert({
      where: { schoolId_year: { schoolId, year } },
      create: { schoolId, year, dataJson, filedByName: actor.fullName, note: body.note ? String(body.note).trim() : null },
      update: { dataJson, filedByName: actor.fullName, note: body.note ? String(body.note).trim() : null },
    });
    await audit({
      schoolId, actor, action: "CREATE", entityType: "EmisReturn", entityId: row.id, entityLabel: `EMIS ${year}`,
      summary: `EMIS annual return filed for census year ${year} — ${census.enrolment.totals.total} learners, ${census.staffing.total} staff`, req,
    });
    return NextResponse.json({ ok: true, year });
  }

  if (action === "unfile") {
    const row = await db.emisReturn.findFirst({ where: { id: String(body.id ?? ""), schoolId } });
    if (!row) return NextResponse.json({ error: "Filed return not found" }, { status: 404 });
    await db.emisReturn.delete({ where: { id: row.id } });
    await audit({
      schoolId, actor, action: "DELETE", entityType: "EmisReturn", entityId: row.id, entityLabel: `EMIS ${row.year}`,
      summary: `EMIS return for census year ${row.year} withdrawn from the archive`, req,
    });
    return NextResponse.json({ ok: true });
  }

  return NextResponse.json({ error: `Unknown action: ${action}` }, { status: 400 });
}

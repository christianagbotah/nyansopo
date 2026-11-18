import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendSms } from "@/lib/sms";

// ── Health & Wellness Clinic (wave 26 — sick-bay register & SHEP screenings) ──
// The infirmary ledger: clinic visits with triage severity, vitals, treatment
// and disposition (RETURNED_CLASS / SENT_HOME / REFERRED / FOLLOWUP), the
// OPEN → CLOSED follow-up workflow, guardian SMS notices, whole-school
// screening events (height/weight/BMI/vision/hearing) and per-learner
// health profiles that surface the medical background on the Student row.

const SEVERITIES = ["MINOR", "MODERATE", "MAJOR"];
const DISPOSITIONS = ["RETURNED_CLASS", "SENT_HOME", "REFERRED", "FOLLOWUP"];

/** Ghanaian sick-bay complaint catalogue (also accepts free-typed categories). */
const COMPLAINT_CATEGORIES = [
  "Fever / malaria suspicion",
  "Headache",
  "Stomach ache / upset",
  "Cough / cold / catarrh",
  "Injury (fall / bruise)",
  "Sports injury",
  "Toothache",
  "Skin rash / ringworm",
  "Eye irritation",
  "Ear ache",
  "Nose bleed",
  "Asthma / breathing",
  "Fainting / dizziness",
  "Menstrual cramps",
  "Wound dressing",
];

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });
const optStr = (v: unknown): string | null => {
  if (typeof v !== "string") return null;
  const t = v.trim();
  return t.length ? t : null;
};
const enumVal = (v: unknown, allowed: string[]): string | null => {
  if (typeof v !== "string" || !v.trim()) return null;
  const u = v.trim().toUpperCase();
  return allowed.includes(u) ? u : "…invalid…";
};
/** parse an optional number; returns the sentinel "invalid" for junk input */
const optNum = (v: unknown): number | null | "invalid" => {
  if (v == null || v === "") return null;
  const n = Number(v);
  return Number.isFinite(n) ? n : "invalid";
};

type VisitRow = {
  id: string;
  studentId: string;
  studentName: string;
  nyansapoIndex: string;
  classroomName: string | null;
  date: string;
  category: string;
  severity: string;
  complaint: string;
  temperature: number | null;
  treatment: string | null;
  medication: string | null;
  disposition: string;
  status: string;
  outcomeNote: string | null;
  closedAt: string | null;
  recordedByName: string | null;
  closedByName: string | null;
  parentNotifiedAt: string | null;
};

/** Map a prisma visit (+ student/class includes) to the wire row. */
function toRow(v: any): VisitRow {
  const active = (v.student?.enrollments ?? []).find((e: any) => e.status === "ACTIVE");
  return {
    id: v.id,
    studentId: v.studentId,
    studentName: `${v.student?.firstName ?? ""} ${v.student?.lastName ?? ""}`.trim(),
    nyansapoIndex: v.student?.nyansapoIndex ?? "",
    classroomName: active?.classroom?.name ?? null,
    date: v.date?.toISOString?.() ?? new Date(v.date).toISOString(),
    category: v.category,
    severity: v.severity ?? "MINOR",
    complaint: v.complaint,
    temperature: v.temperature ?? null,
    treatment: v.treatment ?? null,
    medication: v.medication ?? null,
    disposition: v.disposition,
    status: v.status,
    outcomeNote: v.outcomeNote ?? null,
    closedAt: v.closedAt?.toISOString?.() ?? null,
    recordedByName: v.recordedByName ?? null,
    closedByName: v.closedByName ?? null,
    parentNotifiedAt: v.parentNotifiedAt?.toISOString?.() ?? null,
  };
}

const VISIT_INCLUDE = {
  student: {
    select: {
      id: true, firstName: true, lastName: true, nyansapoIndex: true,
      guardianName: true, guardianPhone: true,
      enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: { select: { id: true, name: true } } } },
    },
  },
} as const;

/** Start of the month n months back, at midnight. */
function monthsAgo(n: number): Date {
  const d = new Date();
  d.setMonth(d.getMonth() - n);
  d.setDate(1);
  d.setHours(0, 0, 0, 0);
  return d;
}

// ── GET /api/school/[id]/ops/health — board | list | roster | profile | screenings | screening | analytics ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["health.view"] });
  if (!auth.ok) return auth.res;

  const sp = req.nextUrl.searchParams;
  const action = sp.get("action") ?? "board";

  /* ── list: filtered sick-bay register ── */
  if (action === "list") {
    const severity = enumVal(sp.get("severity"), SEVERITIES);
    if (sp.get("severity") && !severity) return bad("severity must be MINOR, MODERATE or MAJOR");
    const disposition = enumVal(sp.get("disposition"), DISPOSITIONS);
    if (sp.get("disposition") && !disposition) return bad("disposition must be RETURNED_CLASS, SENT_HOME, REFERRED or FOLLOWUP");
    const status = optStr(sp.get("status"));
    if (status && !["OPEN", "CLOSED"].includes(status)) return bad("status must be OPEN or CLOSED");
    const classroomId = optStr(sp.get("classroomId"));
    const from = optStr(sp.get("from"));
    const to = optStr(sp.get("to"));
    const q = optStr(sp.get("q"));
    const take = Math.min(200, Math.max(10, Math.round(Number(sp.get("take")) || 60)));
    const skip = Math.max(0, Math.round(Number(sp.get("skip")) || 0));

    const where: any = { schoolId };
    if (severity) where.severity = severity;
    if (disposition) where.disposition = disposition;
    if (status) where.status = status;
    if (from || to) {
      where.date = {};
      if (from) where.date.gte = new Date(`${from}T00:00:00`);
      if (to) where.date.lte = new Date(`${to}T23:59:59`);
    }
    if (q) where.student = { OR: [
      { firstName: { contains: q, mode: "insensitive" } },
      { lastName: { contains: q, mode: "insensitive" } },
      { nyansapoIndex: { contains: q, mode: "insensitive" } },
    ] };
    if (classroomId) where.student = { ...(where.student ?? {}), enrollments: { some: { classroomId, status: "ACTIVE" } } };

    const [rows, total] = await Promise.all([
      db.healthVisit.findMany({
        where, include: VISIT_INCLUDE,
        orderBy: [{ date: "desc" }, { createdAt: "desc" }],
        take, skip,
      }),
      db.healthVisit.count({ where }),
    ]);
    return NextResponse.json({ rows: rows.map(toRow), total, take, skip });
  }

  /* ── profile: one learner's health background + timelines ── */
  if (action === "profile") {
    const studentId = optStr(sp.get("studentId"));
    if (!studentId) return bad("studentId is required");
    const student = await db.student.findUnique({
      where: { id: studentId },
      select: {
        id: true, firstName: true, lastName: true, nyansapoIndex: true, gender: true,
        bloodGroup: true, allergies: true, medicalConditions: true, emergencyContact: true, dietNote: true,
        guardianName: true, guardianPhone: true,
        enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: { select: { id: true, name: true } } } },
      },
    });
    const active = student?.enrollments.find((e) => e.status === "ACTIVE");
    if (!student || !active) return bad("Learner has no active enrolment at this school", 404);
    const [visits, screeningRecords] = await Promise.all([
      db.healthVisit.findMany({
        where: { schoolId, studentId },
        include: VISIT_INCLUDE,
        orderBy: [{ date: "desc" }, { createdAt: "desc" }],
      }),
      db.healthScreeningRecord.findMany({
        where: { studentId, screening: { schoolId } },
        include: { screening: { select: { id: true, title: true, screenDate: true, status: true } } },
        orderBy: { screening: { screenDate: "desc" } },
      }),
    ]);
    const referredScreenings = screeningRecords.filter((r) => r.referred);
    return NextResponse.json({
      student: {
        id: student.id,
        name: `${student.firstName} ${student.lastName}`.trim(),
        nyansapoIndex: student.nyansapoIndex,
        gender: student.gender,
        classroomId: active.classroomId,
        classroomName: active.classroom?.name ?? null,
        guardianName: student.guardianName,
        guardianPhone: student.guardianPhone,
        bloodGroup: student.bloodGroup,
        allergies: student.allergies,
        medicalConditions: student.medicalConditions,
        emergencyContact: student.emergencyContact,
        dietNote: student.dietNote,
      },
      visits: visits.map(toRow),
      screenings: screeningRecords.map((r) => ({
        id: r.id,
        screeningId: r.screening.id,
        title: r.screening.title,
        screenDate: r.screening.screenDate.toISOString(),
        heightCm: r.heightCm,
        weightKg: r.weightKg,
        bmi: r.bmi,
        vision: r.vision,
        hearing: r.hearing,
        finding: r.finding,
        referred: r.referred,
        note: r.note,
      })),
      summary: {
        visits: visits.length,
        open: visits.filter((v) => v.status === "OPEN").length,
        sentHome: visits.filter((v) => v.disposition === "SENT_HOME").length,
        referred: visits.filter((v) => v.disposition === "REFERRED").length,
        major: visits.filter((v) => v.severity === "MAJOR").length,
        lastVisitAt: visits[0]?.date?.toISOString?.() ?? null,
        screeningRefs: referredScreenings.length,
      },
    });
  }

  /* ── screenings: event list with coverage counts ── */
  if (action === "screenings") {
    const [events, activeLearners, referred] = await Promise.all([
      db.healthScreening.findMany({
        where: { schoolId },
        orderBy: { screenDate: "desc" },
        include: { records: { select: { referred: true } } },
      }),
      db.enrollment.count({ where: { schoolId, status: "ACTIVE" } }),
      db.healthScreeningRecord.groupBy({ by: ["screeningId"], where: { screening: { schoolId }, referred: true }, _count: { _all: true } }),
    ]);
    const refsByEvent = new Map(referred.map((g) => [g.screeningId, g._count._all]));
    return NextResponse.json({
      events: events.map((e) => ({
        id: e.id,
        title: e.title,
        screenDate: e.screenDate.toISOString(),
        status: e.status,
        notes: e.notes,
        createdByName: e.createdByName,
        recorded: e.records.length,
        coverage: activeLearners ? Math.round((e.records.length / activeLearners) * 100) : 0,
        referred: refsByEvent.get(e.id) ?? 0,
      })),
      learners: activeLearners,
    });
  }

  /* ── screening: one event, its records + the pending roster ── */
  if (action === "screening") {
    const screeningId = optStr(sp.get("screeningId"));
    if (!screeningId) return bad("screeningId is required");
    const event = await db.healthScreening.findUnique({
      where: { id: screeningId },
      include: {
        records: {
          include: {
            student: {
              select: {
                id: true, firstName: true, lastName: true, nyansapoIndex: true,
                enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: { select: { id: true, name: true } } } },
              },
            },
          },
        },
      },
    });
    if (!event || event.schoolId !== schoolId) return bad("Screening event not found", 404);
    const rows = event.records.map((r) => {
      const active = r.student.enrollments.find((e) => e.status === "ACTIVE");
      return {
        id: r.id,
        studentId: r.studentId,
        studentName: `${r.student.firstName} ${r.student.lastName}`.trim(),
        nyansapoIndex: r.student.nyansapoIndex,
        classroomName: active?.classroom?.name ?? null,
        heightCm: r.heightCm,
        weightKg: r.weightKg,
        bmi: r.bmi,
        vision: r.vision,
        hearing: r.hearing,
        finding: r.finding,
        referred: r.referred,
        note: r.note,
      };
    });
    const recordedIds = rows.map((r) => r.studentId);
    const pending = await db.enrollment.findMany({
      where: { schoolId, status: "ACTIVE", ...(recordedIds.length ? { studentId: { notIn: recordedIds } } : {}) },
      include: { student: { select: { id: true, firstName: true, lastName: true, nyansapoIndex: true } }, classroom: { select: { name: true } } },
      orderBy: [{ classroom: { name: "asc" } }, { rollNo: "asc" }],
      take: 500,
    });
    return NextResponse.json({
      event: {
        id: event.id, title: event.title, screenDate: event.screenDate.toISOString(),
        status: event.status, notes: event.notes, createdByName: event.createdByName,
      },
      rows,
      pending: pending.map((e) => ({
        id: e.student.id,
        name: `${e.student.firstName} ${e.student.lastName}`.trim(),
        rollNo: e.rollNo ?? null,
        nyansapoIndex: e.student.nyansapoIndex,
        classroomName: e.classroom?.name ?? null,
      })),
    });
  }

  /* ── analytics: trend, top complaints, disposition mix, frequent visitors ── */
  if (action === "analytics") {
    const since = monthsAgo(6);
    const [visits, classrooms, activeEns] = await Promise.all([
      db.healthVisit.findMany({
        where: { schoolId, date: { gte: since } },
        include: VISIT_INCLUDE,
        orderBy: { date: "desc" },
      }),
      db.classroom.findMany({ where: { schoolId }, select: { id: true, name: true }, orderBy: { name: "asc" } }),
      db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, select: { classroomId: true } }),
    ]);

    // top complaints
    const byCategory = new Map<string, number>();
    for (const v of visits) byCategory.set(v.category, (byCategory.get(v.category) ?? 0) + 1);
    const complaints = [...byCategory.entries()]
      .map(([name, count]) => ({ name, count }))
      .sort((a, b) => b.count - a.count)
      .slice(0, 10);

    // 6-month trend (calendar buckets)
    const buckets: { month: string; visits: number; sentHome: number; referred: number }[] = [];
    for (let i = 5; i >= 0; i--) {
      const start = monthsAgo(i);
      const end = monthsAgo(i - 1);
      const inRange = (d: Date) => d >= start && d < end;
      const inB = visits.filter((v) => inRange(new Date(v.date)));
      buckets.push({
        month: start.toLocaleDateString("en-GB", { month: "short", year: "2-digit" }),
        visits: inB.length,
        sentHome: inB.filter((v) => v.disposition === "SENT_HOME").length,
        referred: inB.filter((v) => v.disposition === "REFERRED").length,
      });
    }

    // frequent visitors (3+ visits in window)
    const perStudent = new Map<string, { name: string; classroomName: string | null; visits: number; lastAt: string | null }>();
    for (const v of visits) {
      const active = (v.student?.enrollments ?? []).find((e: any) => e.status === "ACTIVE");
      const iso = v.date?.toISOString?.() ?? new Date(v.date).toISOString();
      const cur = perStudent.get(v.studentId) ?? {
        name: `${v.student?.firstName ?? ""} ${v.student?.lastName ?? ""}`.trim(),
        classroomName: active?.classroom?.name ?? null,
        visits: 0, lastAt: null as string | null,
      };
      cur.visits++;
      if (!cur.lastAt || iso > cur.lastAt) cur.lastAt = iso;
      perStudent.set(v.studentId, cur);
    }
    const frequent = [...perStudent.entries()]
      .filter(([, v]) => v.visits >= 3)
      .map(([id, v]) => ({ id, ...v }))
      .sort((a, b) => b.visits - a.visits || a.name.localeCompare(b.name))
      .slice(0, 10);

    // class distribution: visits per 100 learners
    const classSizes = new Map<string, number>();
    for (const e of activeEns) classSizes.set(e.classroomId, (classSizes.get(e.classroomId) ?? 0) + 1);
    const classById = new Map(classrooms.map((c) => [c.id, c]));
    const perClass = new Map<string, { name: string; visits: number }>();
    for (const v of visits) {
      const active = (v.student?.enrollments ?? []).find((e: any) => e.status === "ACTIVE");
      const cls = active ? classById.get(active.classroomId) : undefined;
      const key = cls?.id ?? "none";
      const cur = perClass.get(key) ?? { name: cls?.name ?? "Unassigned", visits: 0 };
      cur.visits++;
      perClass.set(key, cur);
    }
    const classDistribution = [...perClass.values()]
      .map((v) => {
        const size = [...classSizes.entries()].find(([k]) => classById.get(k)?.name === v.name)?.[1] ?? 0;
        return { name: v.name, visits: v.visits, learners: size, per100: size ? Math.round((v.visits / size) * 100) : 0 };
      })
      .sort((a, b) => b.visits - a.visits);

    // disposition + severity mix
    const disposition: Record<string, number> = { RETURNED_CLASS: 0, SENT_HOME: 0, REFERRED: 0, FOLLOWUP: 0 };
    for (const v of visits) disposition[v.disposition] = (disposition[v.disposition] ?? 0) + 1;
    const severity: Record<string, number> = { MINOR: 0, MODERATE: 0, MAJOR: 0 };
    for (const v of visits) severity[v.severity] = (severity[v.severity] ?? 0) + 1;

    // screening coverage snapshot (latest event)
    const latest = await db.healthScreening.findFirst({
      where: { schoolId },
      orderBy: { screenDate: "desc" },
      include: { _count: { select: { records: true } } },
    });

    return NextResponse.json({
      window: { since: since.toISOString().slice(0, 10) },
      totals: {
        visits: visits.length,
        sentHome: visits.filter((v) => v.disposition === "SENT_HOME").length,
        referred: visits.filter((v) => v.disposition === "REFERRED").length,
        notified: visits.filter((v) => v.parentNotifiedAt).length,
        topComplaint: complaints[0]?.name ?? null,
      },
      complaints, trend: buckets, frequent, classDistribution, disposition, severity,
      latestScreening: latest ? {
        title: latest.title, screenDate: latest.screenDate.toISOString(),
        status: latest.status, recorded: latest._count.records,
        coverage: activeEns.length ? Math.round((latest._count.records / activeEns.length) * 100) : 0,
      } : null,
    });
  }

  /* ── roster: active learners of one classroom (learner pickers) ── */
  if (action === "roster") {
    const classroomId = optStr(sp.get("classroomId"));
    if (!classroomId) return bad("classroomId is required");
    const ens = await db.enrollment.findMany({
      where: { schoolId, classroomId, status: "ACTIVE" },
      include: { student: { select: { id: true, firstName: true, lastName: true, nyansapoIndex: true } } },
      orderBy: [{ rollNo: "asc" }, { student: { lastName: "asc" } }],
      take: 300,
    });
    return NextResponse.json({
      rows: ens.map((e) => ({
        id: e.student.id,
        name: `${e.student.firstName} ${e.student.lastName}`.trim(),
        rollNo: e.rollNo ?? null,
        nyansapoIndex: e.student.nyansapoIndex,
      })),
    });
  }

  /* ── board (default): stat row + recent visits + open follow-ups ── */
  const since = monthsAgo(6);
  const where6m = { schoolId, date: { gte: since } };
  const [classrooms, recent, openFollowUps, counts, sentHome, referred, notified, screenCount, latestScreening, openCount] = await Promise.all([
    db.classroom.findMany({ where: { schoolId }, select: { id: true, name: true }, orderBy: { name: "asc" } }),
    db.healthVisit.findMany({
      where: { schoolId }, include: VISIT_INCLUDE,
      orderBy: [{ date: "desc" }, { createdAt: "desc" }], take: 12,
    }),
    db.healthVisit.findMany({
      where: { schoolId, status: "OPEN" },
      include: VISIT_INCLUDE,
      orderBy: [{ date: "desc" }], take: 12,
    }),
    db.healthVisit.groupBy({ by: ["severity"], where: where6m, _count: { _all: true } }),
    db.healthVisit.count({ where: { ...where6m, disposition: "SENT_HOME" } }),
    db.healthVisit.count({ where: { ...where6m, disposition: "REFERRED" } }),
    db.healthVisit.count({ where: { ...where6m, parentNotifiedAt: { not: null } } }),
    db.healthScreening.count({ where: { schoolId } }),
    db.healthScreening.findFirst({ where: { schoolId }, orderBy: { screenDate: "desc" }, include: { _count: { select: { records: true } } } }),
    db.healthVisit.count({ where: { schoolId, status: "OPEN" } }),
  ]);
  const stat = { visits: 0, minor: 0, moderate: 0, major: 0, open: openCount, sentHome, referred, notified };
  for (const g of counts) {
    const n = g._count._all;
    stat.visits += n;
    if (g.severity === "MINOR") stat.minor += n;
    if (g.severity === "MODERATE") stat.moderate += n;
    if (g.severity === "MAJOR") stat.major += n;
  }

  return NextResponse.json({
    classrooms,
    stat,
    recent: recent.map(toRow),
    openVisits: openFollowUps.map(toRow),
    screening: {
      events: screenCount,
      latest: latestScreening ? {
        title: latestScreening.title,
        screenDate: latestScreening.screenDate.toISOString(),
        status: latestScreening.status,
        recorded: latestScreening._count.records,
      } : null,
    },
    categories: { complaints: COMPLAINT_CATEGORIES },
  });
}

// ── POST /api/school/[id]/ops/health — create | update | notify | screening-create | screening-record | screening-complete | screening-reopen ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["health.manage"] });
  if (!auth.ok) return auth.res;
  const sentByName = auth.user.fullName || auth.user.email;

  /* ── create: a clinic visit ── */
  if (action === "create") {
    const { studentId, date, category, severity, complaint, temperature, treatment, medication, disposition } = body;
    const cat = optStr(category);
    if (!cat) return bad("Complaint category is required");
    const comp = optStr(complaint);
    if (!comp) return bad("Complaint description is required");
    let sev = "MINOR";
    if (severity != null && `${severity}`.trim() !== "") {
      const v = enumVal(severity, SEVERITIES);
      if (!v || v === "…invalid…") return bad("Severity must be MINOR, MODERATE or MAJOR");
      sev = v;
    }
    let disp = "RETURNED_CLASS";
    if (disposition != null && `${disposition}`.trim() !== "") {
      const v = enumVal(disposition, DISPOSITIONS);
      if (!v || v === "…invalid…") return bad("Disposition must be RETURNED_CLASS, SENT_HOME, REFERRED or FOLLOWUP");
      disp = v;
    }
    const temp = optNum(temperature);
    if (temp === "invalid") return bad("Temperature must be a number (°C)");
    if (typeof temp === "number" && (temp < 30 || temp > 45)) return bad("Temperature looks off — enter a °C value between 30 and 45");
    const student = await db.student.findUnique({
      where: { id: optStr(studentId) ?? "" },
      select: { id: true, enrollments: { where: { status: "ACTIVE" }, take: 1, select: { classroom: { select: { schoolId: true } } } } },
    });
    if (!student || !student.enrollments.length || student.enrollments[0].classroom.schoolId !== schoolId) {
      return bad("Learner not found at this school", 404);
    }
    let when: Date | null = null;
    if (typeof date === "string" && date.trim()) {
      const d = new Date(date);
      if (isNaN(d.getTime())) return bad("Invalid date");
      if (d.getTime() > Date.now() + 86400000) return bad("Date cannot be in the future");
      when = d;
    }
    /* a follow-up or referral stays OPEN until the nurse closes it with an
       outcome; a returned-to-class / sent-home visit is complete on arrival */
    const staysOpen = disp === "FOLLOWUP" || disp === "REFERRED";
    const created = await db.healthVisit.create({
      data: {
        schoolId, studentId: student.id,
        date: when ?? new Date(),
        category: cat,
        severity: sev,
        complaint: comp,
        temperature: typeof temp === "number" ? temp : null,
        treatment: optStr(treatment),
        medication: optStr(medication),
        disposition: disp,
        status: staysOpen ? "OPEN" : "CLOSED",
        closedAt: staysOpen ? null : new Date(),
        closedByName: staysOpen ? null : sentByName,
        recordedByName: sentByName,
      },
      include: VISIT_INCLUDE,
    });
    return NextResponse.json({ ok: true, row: toRow(created) });
  }

  /* ── update: close with outcome / reopen / edit details ── */
  if (action === "update") {
    const { id, status, outcomeNote, severity, disposition, treatment, medication } = body;
    const existing = await db.healthVisit.findUnique({ where: { id: optStr(id) ?? "" } });
    if (!existing || existing.schoolId !== schoolId) return bad("Visit not found", 404);

    const data: any = {};
    if (severity != null) {
      const v = enumVal(severity, SEVERITIES);
      if (!v || v === "…invalid…") return bad("Severity must be MINOR, MODERATE or MAJOR");
      data.severity = v;
    }
    if (disposition != null) {
      const v = enumVal(disposition, DISPOSITIONS);
      if (!v || v === "…invalid…") return bad("Disposition must be RETURNED_CLASS, SENT_HOME, REFERRED or FOLLOWUP");
      data.disposition = v;
    }
    if (treatment != null) data.treatment = optStr(treatment);
    if (medication != null) data.medication = optStr(medication);
    if (status != null) {
      const st = optStr(status);
      if (!st || !["OPEN", "CLOSED"].includes(st)) return bad("Status must be OPEN or CLOSED");
      if (st === "CLOSED") {
        data.status = "CLOSED";
        data.closedAt = new Date();
        data.closedByName = sentByName;
        const note = optStr(outcomeNote);
        if (note) data.outcomeNote = note;
      } else {
        data.status = "OPEN";
        data.closedAt = null;
        data.closedByName = null;
      }
    } else if (outcomeNote != null) {
      data.outcomeNote = optStr(outcomeNote);
    }

    const updated = await db.healthVisit.update({ where: { id: existing.id }, data, include: VISIT_INCLUDE });
    return NextResponse.json({ ok: true, row: toRow(updated) });
  }

  /* ── notify: SMS the guardian about a visit, stamp parentNotifiedAt ── */
  if (action === "notify") {
    const { id, message } = body;
    const existing = await db.healthVisit.findUnique({
      where: { id: optStr(id) ?? "" },
      include: VISIT_INCLUDE,
    });
    if (!existing || existing.schoolId !== schoolId) return bad("Visit not found", 404);
    const student = existing.student;
    const guardianPhone = (student?.guardianPhone ?? "").trim();
    if (!guardianPhone) return bad("This learner has no guardian phone on file");

    const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true } });
    const dateStr = new Date(existing.date).toLocaleDateString("en-GB", { day: "numeric", month: "short" });
    const DISP_TEXT: Record<string, string> = {
      RETURNED_CLASS: "seen at the sick bay and returned to class",
      SENT_HOME: "seen at the sick bay and sent home",
      REFERRED: "referred for further medical attention",
      FOLLOWUP: "placed on sick-bay follow-up",
    };
    const bodyText = optStr(message) ?? `[${school?.name ?? "School"}] Health notice — ${student?.firstName ?? ""} ${student?.lastName ?? ""} was ${DISP_TEXT[existing.disposition] ?? "seen at the sick bay"} on ${dateStr} (${existing.category}). ${existing.medication ? `Medication given: ${existing.medication}. ` : ""}Kindly monitor your ward. — Sick bay`;

    const msg = await sendSms({
      db, schoolId,
      studentId: existing.studentId,
      recipientName: student?.guardianName ?? "Guardian",
      recipientPhone: guardianPhone,
      body: bodyText,
      category: "HEALTH",
      sentByName,
    });
    if (msg.status === "FAILED") {
      return NextResponse.json({ error: msg.errorReason ?? "SMS failed — check SMS credits in Settings" }, { status: 409 });
    }
    const updated = await db.healthVisit.update({
      where: { id: existing.id },
      data: { parentNotifiedAt: new Date() },
      include: VISIT_INCLUDE,
    });
    return NextResponse.json({ ok: true, row: toRow(updated), sms: { status: msg.status, provider: msg.provider, creditsUsed: msg.creditsUsed } });
  }

  /* ── screening-create: a new screening event ── */
  if (action === "screening-create") {
    const { title, screenDate, notes } = body;
    const t = optStr(title);
    if (!t) return bad("Screening title is required");
    let when = new Date();
    if (typeof screenDate === "string" && screenDate.trim()) {
      const d = new Date(screenDate);
      if (isNaN(d.getTime())) return bad("Invalid screening date");
      when = d;
    }
    const created = await db.healthScreening.create({
      data: {
        schoolId, title: t, screenDate: when,
        notes: optStr(notes),
        createdByName: sentByName,
      },
    });
    return NextResponse.json({ ok: true, event: { id: created.id, title: created.title, screenDate: created.screenDate.toISOString(), status: created.status } });
  }

  /* ── screening-record: upsert one learner's measurements (BMI computed) ── */
  if (action === "screening-record") {
    const { screeningId, studentId, heightCm, weightKg, vision, hearing, finding, referred, note } = body;
    const event = await db.healthScreening.findUnique({ where: { id: optStr(screeningId) ?? "" } });
    if (!event || event.schoolId !== schoolId) return bad("Screening event not found", 404);
    if (event.status === "COMPLETED") return bad("This screening is completed — reopen it before recording more measurements", 409);
    const student = await db.student.findUnique({
      where: { id: optStr(studentId) ?? "" },
      select: { id: true, enrollments: { where: { status: "ACTIVE" }, take: 1, select: { classroom: { select: { schoolId: true } } } } },
    });
    if (!student || !student.enrollments.length || student.enrollments[0].classroom.schoolId !== schoolId) {
      return bad("Learner not found at this school", 404);
    }
    const h = optNum(heightCm);
    const w = optNum(weightKg);
    if (h === "invalid") return bad("Height must be a number in cm");
    if (w === "invalid") return bad("Weight must be a number in kg");
    if (typeof h === "number" && (h < 50 || h > 220)) return bad("Height looks off — enter a cm value between 50 and 220");
    if (typeof w === "number" && (w < 10 || w > 150)) return bad("Weight looks off — enter a kg value between 10 and 150");
    const bmi = typeof h === "number" && typeof w === "number" && h > 0 ? Math.round((w / Math.pow(h / 100, 2)) * 10) / 10 : null;

    const row = await db.healthScreeningRecord.upsert({
      where: { screeningId_studentId: { screeningId: event.id, studentId: student.id } },
      update: {
        heightCm: typeof h === "number" ? h : null,
        weightKg: typeof w === "number" ? w : null,
        bmi,
        vision: optStr(vision),
        hearing: optStr(hearing),
        finding: optStr(finding),
        referred: referred === true,
        note: optStr(note),
        recordedByName: sentByName,
      },
      create: {
        screeningId: event.id, studentId: student.id,
        heightCm: typeof h === "number" ? h : null,
        weightKg: typeof w === "number" ? w : null,
        bmi,
        vision: optStr(vision),
        hearing: optStr(hearing),
        finding: optStr(finding),
        referred: referred === true,
        note: optStr(note),
        recordedByName: sentByName,
      },
    });
    return NextResponse.json({ ok: true, record: { id: row.id, studentId: row.studentId, bmi: row.bmi, referred: row.referred } });
  }

  /* ── screening-complete / screening-reopen: flip the event status ── */
  if (action === "screening-complete" || action === "screening-reopen") {
    const { screeningId } = body;
    const event = await db.healthScreening.findUnique({ where: { id: optStr(screeningId) ?? "" } });
    if (!event || event.schoolId !== schoolId) return bad("Screening event not found", 404);
    const closing = action === "screening-complete";
    if (closing && event.status === "COMPLETED") return bad("Screening is already completed", 409);
    if (!closing && event.status === "OPEN") return bad("Screening is still open", 409);
    const updated = await db.healthScreening.update({
      where: { id: event.id },
      data: { status: closing ? "COMPLETED" : "OPEN" },
    });
    return NextResponse.json({ ok: true, event: { id: updated.id, status: updated.status } });
  }

  return bad("Unknown action");
}

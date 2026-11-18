import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendSms } from "@/lib/sms";

// ── Discipline & Conduct (wave 25 — iEduPro discipline parity) ──
// The day-to-day conduct register: incidents (offences with a severity ladder
// and an OPEN → RESOLVED / ESCALATED workflow) + commendations (positive
// records), learner conduct profiles, analytics, and guardian SMS notices.

const KINDS = ["INCIDENT", "COMMENDATION"];
const SEVERITIES = ["MINOR", "MODERATE", "MAJOR"];
const STATUSES = ["OPEN", "RESOLVED", "ESCALATED"];

/** Ghanaian-JHS offence catalogue (also accepts free-typed categories). */
const OFFENCE_CATEGORIES = [
  "Late coming",
  "Truancy",
  "Improper uniform",
  "Classroom disruption",
  "Disrespect to staff",
  "Bullying / fighting",
  "Exam malpractice",
  "Phone / gadget",
  "Property damage",
  "Theft",
  "Substance abuse",
];

/** Positive-conduct catalogue. */
const COMMENDATION_CATEGORIES = [
  "Academic excellence",
  "Academic improvement",
  "Leadership",
  "Sportsmanship",
  "Neatness",
  "Good deed",
  "Service to school",
  "Perfect score",
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

type CaseRow = {
  id: string;
  studentId: string;
  studentName: string;
  nyansapoIndex: string;
  classroomName: string | null;
  kind: string;
  date: string;
  category: string;
  severity: string | null;
  description: string;
  actionTaken: string | null;
  status: string;
  resolutionNote: string | null;
  resolvedAt: string | null;
  recordedByName: string | null;
  resolvedByName: string | null;
  parentNotifiedAt: string | null;
};

/** Map a prisma case (+ student/class includes) to the wire row. */
function toRow(c: any): CaseRow {
  const active = (c.student?.enrollments ?? []).find((e: any) => e.status === "ACTIVE");
  return {
    id: c.id,
    studentId: c.studentId,
    studentName: `${c.student?.firstName ?? ""} ${c.student?.lastName ?? ""}`.trim(),
    nyansapoIndex: c.student?.nyansapoIndex ?? "",
    classroomName: active?.classroom?.name ?? null,
    kind: c.kind,
    date: c.date?.toISOString?.() ?? new Date(c.date).toISOString(),
    category: c.category,
    severity: c.severity ?? null,
    description: c.description,
    actionTaken: c.actionTaken ?? null,
    status: c.status,
    resolutionNote: c.resolutionNote ?? null,
    resolvedAt: c.resolvedAt?.toISOString?.() ?? null,
    recordedByName: c.recordedByName ?? null,
    resolvedByName: c.resolvedByName ?? null,
    parentNotifiedAt: c.parentNotifiedAt?.toISOString?.() ?? null,
  };
}

const CASE_INCLUDE = {
  student: {
    select: {
      id: true, firstName: true, lastName: true, nyansapoIndex: true,
      guardianName: true, guardianPhone: true, guardianAltPhone: true,
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

// ── GET /api/school/[id]/ops/discipline — board | list | profile | analytics ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["discipline.view"] });
  if (!auth.ok) return auth.res;

  const sp = req.nextUrl.searchParams;
  const action = sp.get("action") ?? "board";

  /* ── list: filtered register ── */
  if (action === "list") {
    const kind = enumVal(sp.get("kind"), KINDS);
    if (sp.get("kind") && !kind) return bad("kind must be INCIDENT or COMMENDATION");
    const status = enumVal(sp.get("status"), STATUSES);
    if (sp.get("status") && !status) return bad("status must be OPEN, RESOLVED or ESCALATED");
    const severity = enumVal(sp.get("severity"), SEVERITIES);
    if (sp.get("severity") && !severity) return bad("severity must be MINOR, MODERATE or MAJOR");
    const classroomId = optStr(sp.get("classroomId"));
    const from = optStr(sp.get("from"));
    const to = optStr(sp.get("to"));
    const q = optStr(sp.get("q"));
    const take = Math.min(200, Math.max(10, Math.round(Number(sp.get("take")) || 60)));
    const skip = Math.max(0, Math.round(Number(sp.get("skip")) || 0));

    const where: any = { schoolId };
    if (kind) where.kind = kind;
    if (status) where.status = status;
    if (severity) where.severity = severity;
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
      db.disciplineCase.findMany({
        where, include: CASE_INCLUDE,
        orderBy: [{ date: "desc" }, { createdAt: "desc" }],
        take, skip,
      }),
      db.disciplineCase.count({ where }),
    ]);
    return NextResponse.json({ rows: rows.map(toRow), total, take, skip });
  }

  /* ── profile: one learner's conduct timeline + summary ── */
  if (action === "profile") {
    const studentId = optStr(sp.get("studentId"));
    if (!studentId) return bad("studentId is required");
    const student = await db.student.findUnique({
      where: { id: studentId },
      select: {
        id: true, firstName: true, lastName: true, nyansapoIndex: true, gender: true,
        guardianName: true, guardianPhone: true,
        enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: { select: { id: true, name: true } } } },
      },
    });
    const active = student?.enrollments.find((e) => e.status === "ACTIVE");
    if (!student || !active) return bad("Learner has no active enrolment at this school", 404);
    const cases = await db.disciplineCase.findMany({
      where: { schoolId, studentId },
      include: CASE_INCLUDE,
      orderBy: [{ date: "desc" }, { createdAt: "desc" }],
    });
    const incidents = cases.filter((c) => c.kind === "INCIDENT");
    const commendations = cases.filter((c) => c.kind === "COMMENDATION");
    return NextResponse.json({
      student: {
        id: student.id,
        name: `${student.firstName} ${student.lastName}`.trim(),
        nyansapoIndex: student.nyansapoIndex,
        gender: student.gender,
        classroomId: active.classroomId,
        classroomName: active.classroom?.name ?? null,
        rollNo: active.rollNo ?? null,
        guardianName: student.guardianName,
        guardianPhone: student.guardianPhone,
      },
      timeline: cases.map(toRow),
      summary: {
        incidents: incidents.length,
        commendations: commendations.length,
        open: incidents.filter((c) => c.status === "OPEN").length,
        escalated: incidents.filter((c) => c.status === "ESCALATED").length,
        major: incidents.filter((c) => c.severity === "MAJOR").length,
        lastIncidentAt: incidents[0]?.date?.toISOString?.() ?? null,
        notified: incidents.filter((c) => c.parentNotifiedAt).length,
      },
    });
  }

  /* ── analytics: category mix, trend, repeat offenders, class ranking ── */
  if (action === "analytics") {
    const since = monthsAgo(6);
    const [all, classrooms] = await Promise.all([
      db.disciplineCase.findMany({
        where: { schoolId, date: { gte: since } },
        include: CASE_INCLUDE,
        orderBy: { date: "desc" },
      }),
      db.classroom.findMany({ where: { schoolId }, select: { id: true, name: true }, orderBy: { name: "asc" } }),
    ]);
    const incidents = all.filter((c) => c.kind === "INCIDENT");
    const commendations = all.filter((c) => c.kind === "COMMENDATION");

    // category mix
    const byCategory = new Map<string, number>();
    for (const c of incidents) byCategory.set(c.category, (byCategory.get(c.category) ?? 0) + 1);
    const categories = [...byCategory.entries()]
      .map(([name, count]) => ({ name, count }))
      .sort((a, b) => b.count - a.count);

    // 6-month trend (calendar buckets)
    const buckets: { month: string; incidents: number; commendations: number }[] = [];
    for (let i = 5; i >= 0; i--) {
      const start = monthsAgo(i);
      const end = monthsAgo(i - 1);
      const inRange = (d: Date) => d >= start && d < end;
      buckets.push({
        month: start.toLocaleDateString("en-GB", { month: "short", year: "2-digit" }),
        incidents: incidents.filter((c) => inRange(new Date(c.date))).length,
        commendations: commendations.filter((c) => inRange(new Date(c.date))).length,
      });
    }

    // repeat offenders + commendation leaderboard (last 6 months)
    const perStudent = new Map<string, { name: string; classroomName: string | null; incidents: number; commendations: number }>();
    for (const c of all) {
      const active = (c.student?.enrollments ?? []).find((e: any) => e.status === "ACTIVE");
      const key = c.studentId;
      const cur = perStudent.get(key) ?? {
        name: `${c.student?.firstName ?? ""} ${c.student?.lastName ?? ""}`.trim(),
        classroomName: active?.classroom?.name ?? null,
        incidents: 0, commendations: 0,
      };
      if (c.kind === "INCIDENT") cur.incidents++; else cur.commendations++;
      perStudent.set(key, cur);
    }
    const repeatOffenders = [...perStudent.entries()]
      .filter(([, v]) => v.incidents > 0)
      .map(([id, v]) => ({ id, ...v }))
      .sort((a, b) => b.incidents - a.incidents || a.name.localeCompare(b.name))
      .slice(0, 10);
    const leaderboard = [...perStudent.entries()]
      .filter(([, v]) => v.commendations > 0)
      .map(([id, v]) => ({ id, ...v }))
      .sort((a, b) => b.commendations - a.commendations || a.name.localeCompare(b.name))
      .slice(0, 10);

    // class conduct ranking: incidents per learner in the window
    const activeEns = await db.enrollment.findMany({ where: { schoolId, status: "ACTIVE" }, select: { classroomId: true } });
    const classSizes = new Map<string, number>();
    for (const e of activeEns) classSizes.set(e.classroomId, (classSizes.get(e.classroomId) ?? 0) + 1);
    const classById = new Map(classrooms.map((c) => [c.id, c]));
    const perClass = new Map<string, { name: string; incidents: number; learners: Set<string> }>();
    for (const c of incidents) {
      const active = (c.student?.enrollments ?? []).find((e: any) => e.status === "ACTIVE");
      const cls = active ? classById.get(active.classroomId) : undefined;
      const key = cls?.id ?? "none";
      const cur = perClass.get(key) ?? { name: cls?.name ?? "Unassigned", incidents: 0, learners: new Set<string>() };
      cur.incidents++;
      cur.learners.add(c.studentId);
      perClass.set(key, cur);
    }
    const classRanking = [...perClass.values()]
      .map((v) => {
        const size = [...classSizes.entries()].find(([k]) => {
          const cl = classById.get(k);
          return cl?.name === v.name;
        })?.[1] ?? v.learners.size;
        return { name: v.name, incidents: v.incidents, learners: size, perLearner: size ? Math.round((v.incidents / size) * 10) / 10 : 0 };
      })
      .sort((a, b) => b.perLearner - a.perLearner);

    // severity & status split
    const severity: Record<string, number> = { MINOR: 0, MODERATE: 0, MAJOR: 0 };
    for (const c of incidents) if (c.severity) severity[c.severity] = (severity[c.severity] ?? 0) + 1;
    const statusSplit: Record<string, number> = { OPEN: 0, RESOLVED: 0, ESCALATED: 0 };
    for (const c of incidents) statusSplit[c.status] = (statusSplit[c.status] ?? 0) + 1;

    return NextResponse.json({
      window: { since: since.toISOString().slice(0, 10) },
      totals: {
        incidents: incidents.length,
        commendations: commendations.length,
        notified: incidents.filter((c) => c.parentNotifiedAt).length,
        topCategory: categories[0]?.name ?? null,
      },
      categories, trend: buckets, repeatOffenders, leaderboard, classRanking, severity, statusSplit,
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

  /* ── board (default): stat row + recent cases + open escalations ── */
  const since = monthsAgo(6);
  const where6m = { schoolId, date: { gte: since } };
  const [classrooms, recent, openIncidents, counts] = await Promise.all([
    db.classroom.findMany({ where: { schoolId }, select: { id: true, name: true }, orderBy: { name: "asc" } }),
    db.disciplineCase.findMany({
      where: { schoolId }, include: CASE_INCLUDE,
      orderBy: [{ date: "desc" }, { createdAt: "desc" }], take: 12,
    }),
    db.disciplineCase.findMany({
      where: { schoolId, kind: "INCIDENT", status: { in: ["OPEN", "ESCALATED"] } },
      include: CASE_INCLUDE,
      orderBy: [{ date: "desc" }], take: 12,
    }),
    db.disciplineCase.groupBy({ by: ["kind", "status"], where: where6m, _count: { _all: true } }),
  ]);
  const stat = { incidents: 0, commendations: 0, open: 0, resolved: 0, escalated: 0, notified: 0 };
  for (const g of counts) {
    const n = g._count._all;
    if (g.kind === "INCIDENT") {
      stat.incidents += n;
      if (g.status === "OPEN") stat.open += n;
      if (g.status === "RESOLVED") stat.resolved += n;
      if (g.status === "ESCALATED") stat.escalated += n;
    } else stat.commendations += n;
  }
  stat.notified = await db.disciplineCase.count({ where: { ...where6m, kind: "INCIDENT", parentNotifiedAt: { not: null } } });

  return NextResponse.json({
    classrooms,
    stat,
    recent: recent.map(toRow),
    openCases: openIncidents.map(toRow),
    categories: { offences: OFFENCE_CATEGORIES, commendations: COMMENDATION_CATEGORIES },
  });
}

// ── POST /api/school/[id]/ops/discipline — create | update | notify ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["discipline.manage"] });
  if (!auth.ok) return auth.res;
  const sentByName = auth.user.fullName || auth.user.email;

  /* ── create: an incident or commendation ── */
  if (action === "create") {
    const { studentId, kind, category, severity, description, actionTaken, date } = body;
    const k = enumVal(kind, KINDS);
    if (!k || k === "…invalid…") return bad("kind must be INCIDENT or COMMENDATION");
    const cat = optStr(category);
    if (!cat) return bad("Category is required");
    const desc = optStr(description);
    if (!desc) return bad("Description is required");
    let sev: string | null = null;
    if (k === "INCIDENT" && severity != null && `${severity}`.trim() !== "") {
      const v = enumVal(severity, SEVERITIES);
      if (!v || v === "…invalid…") return bad("Severity must be MINOR, MODERATE or MAJOR");
      sev = v;
    }
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
    const created = await db.disciplineCase.create({
      data: {
        schoolId, studentId: student.id,
        kind: k,
        date: when ?? new Date(),
        category: cat,
        severity: k === "INCIDENT" ? (sev ?? "MINOR") : null,
        description: desc,
        actionTaken: optStr(actionTaken),
        status: "OPEN",
        recordedByName: sentByName,
      },
      include: CASE_INCLUDE,
    });
    return NextResponse.json({ ok: true, row: toRow(created) });
  }

  /* ── update: resolve / escalate / reopen / edit details ── */
  if (action === "update") {
    const { id, status, resolutionNote, category, severity, description, actionTaken } = body;
    const existing = await db.disciplineCase.findUnique({ where: { id: optStr(id) ?? "" } });
    if (!existing || existing.schoolId !== schoolId) return bad("Case not found", 404);

    const data: any = {};
    if (category != null) {
      const cat = optStr(category);
      if (!cat) return bad("Category cannot be empty");
      data.category = cat;
    }
    if (description != null) {
      const desc = optStr(description);
      if (!desc) return bad("Description cannot be empty");
      data.description = desc;
    }
    if (actionTaken != null) data.actionTaken = optStr(actionTaken);
    if (severity != null) {
      if (existing.kind !== "INCIDENT") return bad("Only incidents carry a severity");
      if (`${severity}`.trim() === "") data.severity = null;
      else {
        const v = enumVal(severity, SEVERITIES);
        if (!v || v === "…invalid…") return bad("Severity must be MINOR, MODERATE or MAJOR");
        data.severity = v;
      }
    }
    if (status != null) {
      const st = enumVal(status, STATUSES);
      if (!st || st === "…invalid…") return bad("Status must be OPEN, RESOLVED or ESCALATED");
      if (existing.kind === "COMMENDATION" && st !== "RESOLVED") return bad("Commendations have no case workflow — they are closed records");
      if (st === "RESOLVED") {
        data.status = "RESOLVED";
        data.resolvedAt = new Date();
        data.resolvedByName = sentByName;
        const note = optStr(resolutionNote);
        if (note) data.resolutionNote = note;
      } else if (st === "ESCALATED") {
        data.status = "ESCALATED";
        data.resolvedAt = null;
        data.resolvedByName = null;
      } else {
        data.status = "OPEN";
        data.resolvedAt = null;
        data.resolvedByName = null;
        const note = optStr(resolutionNote);
        if (note) data.resolutionNote = note;
      }
    } else if (resolutionNote != null) {
      data.resolutionNote = optStr(resolutionNote);
    }

    const updated = await db.disciplineCase.update({ where: { id: existing.id }, data, include: CASE_INCLUDE });
    return NextResponse.json({ ok: true, row: toRow(updated) });
  }

  /* ── notify: SMS the guardian about a case, stamp parentNotifiedAt ── */
  if (action === "notify") {
    const { id, message } = body;
    const existing = await db.disciplineCase.findUnique({
      where: { id: optStr(id) ?? "" },
      include: CASE_INCLUDE,
    });
    if (!existing || existing.schoolId !== schoolId) return bad("Case not found", 404);
    if (existing.kind !== "INCIDENT") return bad("Only incidents can raise a guardian notice");
    const student = existing.student;
    const guardianPhone = (student?.guardianPhone ?? "").trim();
    if (!guardianPhone) return bad("This learner has no guardian phone on file");

    const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true } });
    const dateStr = new Date(existing.date).toLocaleDateString("en-GB", { day: "numeric", month: "short" });
    const bodyText = optStr(message) ?? `[${school?.name ?? "School"}] Conduct notice — ${student?.firstName ?? ""} ${student?.lastName ?? ""}: ${existing.category} on ${dateStr}. ${existing.actionTaken ? `Action: ${existing.actionTaken}. ` : ""}Kindly discuss this with your ward. — Head's office`;

    const msg = await sendSms({
      db, schoolId,
      studentId: existing.studentId,
      recipientName: student?.guardianName ?? "Guardian",
      recipientPhone: guardianPhone,
      body: bodyText,
      category: "DISCIPLINE",
      sentByName,
    });
    if (msg.status === "FAILED") {
      return NextResponse.json({ error: msg.errorReason ?? "SMS failed — check SMS credits in Settings" }, { status: 409 });
    }
    const updated = await db.disciplineCase.update({
      where: { id: existing.id },
      data: { parentNotifiedAt: new Date() },
      include: CASE_INCLUDE,
    });
    return NextResponse.json({ ok: true, row: toRow(updated), sms: { status: msg.status, provider: msg.provider, creditsUsed: msg.creditsUsed } });
  }

  return bad("Unknown action");
}

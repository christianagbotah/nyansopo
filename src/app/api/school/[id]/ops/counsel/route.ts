import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { sendSms } from "@/lib/sms";
import { audit, auditSnapshot } from "@/lib/audit";
import { canAny } from "@/lib/rbac";

// ── Guidance & Counselling unit (wave 71) ─────────────────────
// The confidential case file every GES school must keep, separate from the
// discipline register: a case is a concern about ONE learner; sessions log
// the counselling conversations; follow-ups track the promises made.
// Confidentiality: a CONFIDENTIAL case is visible only to its assigned
// counsellor, its opener, and counsel.manage holders (head / G&C coordinator).
// ABUSE_REPORT cases are always confidential + at least HIGH severity, and
// flag the assigned counsellor by SMS the moment they are opened.

const CATEGORIES = ["ACADEMIC", "BEHAVIOURAL", "FAMILY", "HEALTH", "CAREER", "PEER", "ATTENDANCE", "FINANCIAL", "ABUSE_REPORT", "OTHER"];
const SEVERITIES = ["LOW", "MEDIUM", "HIGH", "CRITICAL"];
const STATUSES = ["OPEN", "IN_PROGRESS", "MONITORING", "RESOLVED", "REFERRED"];
const SOURCES = ["TEACHER_REFERRAL", "SELF", "PARENT", "DISCIPLINE", "ATTENDANCE", "HEALTH", "ANON"];
const MODES = ["FACE_TO_FACE", "GROUP", "PHONE", "HOME_VISIT"];
const OUTCOMES = ["IMPROVED", "NO_CHANGE", "WORSE", "ESCALATED"];

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

const CASE_SELECT = {
  id: true, caseNo: true, category: true, severity: true, status: true, source: true,
  title: true, summary: true, confidential: true, referredExternal: true,
  assignedToId: true, openedByStaffId: true, // raw scalars — the confidentiality gate reads them
  openedByName: true, closedAt: true, closedNote: true, createdAt: true, updatedAt: true,
  student: {
    select: {
      id: true, firstName: true, lastName: true, nyansapoIndex: true, gender: true,
      guardianName: true, guardianPhone: true,
      enrollments: { where: { status: "ACTIVE" }, take: 1, include: { classroom: { select: { id: true, name: true } } } },
    },
  },
  assignedTo: { select: { id: true, fullName: true, phone: true } },
  _count: { select: { sessions: true, followUps: { where: { doneAt: null } } } },
} as const;

type CaseRow = {
  id: string; caseNo: string; category: string; severity: string; status: string; source: string;
  title: string; summary: string | null; confidential: boolean; referredExternal: string | null;
  openedByName: string; closedAt: string | null; closedNote: string | null;
  createdAt: string; updatedAt: string;
  studentId: string; studentName: string; nyansapoIndex: string;
  classroomName: string | null; guardianName: string | null;
  assignedToId: string | null; assignedToName: string | null;
  sessionsCount: number; openFollowUps: number;
};

function toRow(c: any): CaseRow {
  const active = (c.student?.enrollments ?? []).find((e: any) => e.status === "ACTIVE");
  return {
    id: c.id, caseNo: c.caseNo, category: c.category, severity: c.severity, status: c.status, source: c.source,
    title: c.title, summary: c.summary ?? null, confidential: !!c.confidential,
    referredExternal: c.referredExternal ?? null,
    openedByName: c.openedByName, closedAt: c.closedAt?.toISOString?.() ?? null, closedNote: c.closedNote ?? null,
    createdAt: c.createdAt?.toISOString?.() ?? new Date(c.createdAt).toISOString(),
    updatedAt: c.updatedAt?.toISOString?.() ?? new Date(c.updatedAt).toISOString(),
    studentId: c.studentId,
    studentName: `${c.student?.firstName ?? ""} ${c.student?.lastName ?? ""}`.trim(),
    nyansapoIndex: c.student?.nyansapoIndex ?? "",
    classroomName: active?.classroom?.name ?? null,
    guardianName: c.student?.guardianName ?? null,
    assignedToId: c.assignedToId ?? null,
    assignedToName: c.assignedTo?.fullName ?? null,
    sessionsCount: c._count?.sessions ?? 0,
    openFollowUps: c._count?.followUps ?? 0,
  };
}

/** Confidentiality WHERE for list queries: with counsel.manage the staff member
 *  sees the whole board; otherwise only PUBLIC cases plus the ones they were
 *  assigned or opened themselves. */
function confidentialWhere(schoolId: string, canManage: boolean, staffId: string | null) {
  if (canManage || !staffId) return { schoolId };
  return {
    schoolId,
    OR: [{ confidential: false }, { assignedToId: staffId }, { openedByStaffId: staffId }],
  };
}

/** Single-case visibility check — mirrors the list filter. */
function canSeeCase(c: { confidential: boolean; assignedToId: string | null; openedByStaffId: string | null }, canManage: boolean, staffId: string | null): boolean {
  if (canManage || !c.confidential) return true;
  if (!staffId) return false;
  return c.assignedToId === staffId || c.openedByStaffId === staffId;
}

/** GC-{year}-{NNN} — per school per calendar year. */
async function nextCaseNo(schoolId: string): Promise<string> {
  const year = new Date().getFullYear();
  const count = await db.counselCase.count({
    where: { schoolId, caseNo: { startsWith: `GC-${year}-` } },
  });
  return `GC-${year}-${String(count + 1).padStart(3, "0")}`;
}

/** Start of the month n months back, at midnight. */
function monthsAgo(n: number): Date {
  const d = new Date();
  d.setMonth(d.getMonth() - n);
  d.setDate(1);
  d.setHours(0, 0, 0, 0);
  return d;
}

// ── GET /api/school/[id]/ops/counsel — board | cases | case | followups | analytics | search ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["counsel.view"] });
  if (!auth.ok) return auth.res;
  const staffId = auth.user.staffId ?? null;
  const canManage = canAny(auth.user.permissions, ["counsel.manage"]);
  const sp = req.nextUrl.searchParams;
  const action = sp.get("action") ?? "board";

  /* ── cases: filtered case list (confidentiality-scoped) ── */
  if (action === "cases") {
    const status = enumVal(sp.get("status"), STATUSES);
    if (sp.get("status") && !status) return bad("status must be OPEN, IN_PROGRESS, MONITORING, RESOLVED or REFERRED");
    const category = enumVal(sp.get("category"), CATEGORIES);
    if (sp.get("category") && !category) return bad("Unknown category");
    const severity = enumVal(sp.get("severity"), SEVERITIES);
    if (sp.get("severity") && !severity) return bad("Unknown severity");
    const classroomId = optStr(sp.get("classroomId"));
    const studentId = optStr(sp.get("studentId"));
    const mine = sp.get("mine") === "1";
    const q = optStr(sp.get("q"));
    const take = Math.min(200, Math.max(10, Math.round(Number(sp.get("take")) || 60)));
    const skip = Math.max(0, Math.round(Number(sp.get("skip")) || 0));

    const where: any = confidentialWhere(schoolId, canManage, staffId);
    if (status) where.status = status;
    if (category) where.category = category;
    if (severity) where.severity = severity;
    if (studentId) where.studentId = studentId;
    if (mine && staffId) where.OR = [{ assignedToId: staffId }, { openedByStaffId: staffId }];
    if (classroomId) where.student = { enrollments: { some: { classroomId, status: "ACTIVE" } } };
    if (q) where.student = {
      ...(where.student ?? {}),
      OR: [
        { firstName: { contains: q, mode: "insensitive" } },
        { lastName: { contains: q, mode: "insensitive" } },
        { nyansapoIndex: { contains: q, mode: "insensitive" } },
      ],
    };

    const [rows, total] = await Promise.all([
      db.counselCase.findMany({ where, select: CASE_SELECT, orderBy: [{ updatedAt: "desc" }], take, skip }),
      db.counselCase.count({ where }),
    ]);
    return NextResponse.json({ rows: rows.map(toRow), total, take, skip, canManage });
  }

  /* ── case: one case file — sessions + follow-ups (confidentiality-checked) ── */
  if (action === "case") {
    const caseId = optStr(sp.get("caseId"));
    if (!caseId) return bad("caseId is required");
    const c = await db.counselCase.findFirst({ where: { id: caseId, schoolId }, select: CASE_SELECT });
    if (!c) return bad("Case not found", 404);
    if (!canSeeCase(c as any, canManage, staffId)) return bad("This case is confidential — it is assigned to another counsellor", 403);
    const [sessions, followUps] = await Promise.all([
      db.counselSession.findMany({ where: { caseId }, orderBy: { heldAt: "desc" }, take: 60 }),
      db.counselFollowUp.findMany({ where: { caseId }, orderBy: { dueAt: "asc" } }),
    ]);
    return NextResponse.json({
      row: toRow(c),
      sessions: sessions.map((s) => ({
        id: s.id, heldAt: s.heldAt.toISOString(), mode: s.mode, notes: s.notes,
        aiSummary: s.aiSummary ?? null, outcome: s.outcome ?? null,
        attendees: s.attendees ?? null, loggedByName: s.loggedByName,
      })),
      followUps: followUps.map((f) => ({
        id: f.id, dueAt: f.dueAt.toISOString(), note: f.note,
        doneAt: f.doneAt?.toISOString() ?? null, doneNote: f.doneNote ?? null,
        overdue: !f.doneAt && f.dueAt < new Date(),
        remindedAt: f.remindedAt?.toISOString() ?? null,
      })),
      canManage,
    });
  }

  /* ── followups: the counsellor's "who did I promise to check on" list ── */
  if (action === "followups") {
    const scope = sp.get("scope") ?? "week"; // week | all | overdue
    const horizon = new Date();
    if (scope === "week") horizon.setDate(horizon.getDate() + 7);
    const where: any = {
      case: confidentialWhere(schoolId, canManage, staffId),
      doneAt: null,
      ...(scope === "overdue" ? { dueAt: { lt: new Date() } } : scope === "week" ? { dueAt: { lte: horizon } } : {}),
    };
    const rows = await db.counselFollowUp.findMany({
      where,
      include: { case: { select: CASE_SELECT } },
      orderBy: { dueAt: "asc" },
      take: 100,
    });
    return NextResponse.json({
      rows: rows.map((f) => ({
        id: f.id, dueAt: f.dueAt.toISOString(), note: f.note,
        overdue: f.dueAt < new Date(), remindedAt: f.remindedAt?.toISOString() ?? null,
        case: toRow(f.case as any),
      })),
    });
  }

  /* ── analytics: the termly G&C report the head files with the district ── */
  if (action === "analytics") {
    const since = monthsAgo(6);
    const cases = await db.counselCase.findMany({
      where: { ...confidentialWhere(schoolId, canManage, staffId), createdAt: { gte: since } },
      select: CASE_SELECT,
      orderBy: { createdAt: "desc" },
    });
    const byCategory = new Map<string, number>();
    const bySeverity: Record<string, number> = { LOW: 0, MEDIUM: 0, HIGH: 0, CRITICAL: 0 };
    const byStatus: Record<string, number> = { OPEN: 0, IN_PROGRESS: 0, MONITORING: 0, RESOLVED: 0, REFERRED: 0 };
    for (const c of cases) {
      byCategory.set(c.category, (byCategory.get(c.category) ?? 0) + 1);
      bySeverity[c.severity] = (bySeverity[c.severity] ?? 0) + 1;
      byStatus[c.status] = (byStatus[c.status] ?? 0) + 1;
    }
    const categories = [...byCategory.entries()].map(([name, count]) => ({ name, count })).sort((a, b) => b.count - a.count);

    // monthly opened vs resolved (6 buckets)
    const buckets: { month: string; opened: number; resolved: number }[] = [];
    for (let i = 5; i >= 0; i--) {
      const start = monthsAgo(i);
      const end = monthsAgo(i - 1);
      buckets.push({
        month: start.toLocaleDateString("en-GB", { month: "short", year: "2-digit" }),
        opened: cases.filter((c) => new Date(c.createdAt) >= start && new Date(c.createdAt) < end).length,
        resolved: cases.filter((c) => c.closedAt && new Date(c.closedAt) >= start && new Date(c.closedAt) < end).length,
      });
    }

    const closed = cases.filter((c) => c.closedAt);
    const avgDays = closed.length
      ? Math.round(closed.reduce((s, c) => s + (new Date(c.closedAt!).getTime() - new Date(c.createdAt).getTime()) / 86400000, 0) / closed.length)
      : null;
    const referredOut = cases.filter((c) => c.status === "REFERRED" || c.referredExternal);

    // busiest counsellors (sessions logged in window)
    const sess = await db.counselSession.findMany({
      where: { case: { schoolId, createdAt: { gte: since } } },
      select: { loggedByName: true },
    });
    const perCounsellor = new Map<string, number>();
    for (const s of sess) perCounsellor.set(s.loggedByName, (perCounsellor.get(s.loggedByName) ?? 0) + 1);

    return NextResponse.json({
      window: { since: since.toISOString().slice(0, 10) },
      totals: {
        cases: cases.length,
        open: byStatus.OPEN + byStatus.IN_PROGRESS + byStatus.MONITORING,
        resolved: byStatus.RESOLVED, referred: byStatus.REFERRED,
        criticalOpen: cases.filter((c) => c.severity === "CRITICAL" && c.status !== "RESOLVED").length,
        sessions: sess.length, avgDaysToResolve: avgDays,
      },
      categories, bySeverity, byStatus, trend: buckets,
      referredOut: referredOut.slice(0, 10).map((c) => ({ caseNo: c.caseNo, title: c.title, to: c.referredExternal ?? "—", studentName: c.studentName, at: c.updatedAt })),
      counsellors: [...perCounsellor.entries()].map(([name, count]) => ({ name, count })).sort((a, b) => b.count - a.count).slice(0, 8),
    });
  }

  /* ── search: student picker (roster-style, guarded to this school) ── */
  if (action === "search") {
    const q = optStr(sp.get("q")) ?? "";
    const classroomId = optStr(sp.get("classroomId"));
    const ens = await db.enrollment.findMany({
      where: {
        schoolId, status: "ACTIVE",
        ...(classroomId ? { classroomId } : {}),
        ...(q ? { student: { OR: [
          { firstName: { contains: q, mode: "insensitive" } },
          { lastName: { contains: q, mode: "insensitive" } },
          { nyansapoIndex: { contains: q, mode: "insensitive" } },
        ] } } : {}),
      },
      include: { student: { select: { id: true, firstName: true, lastName: true, nyansapoIndex: true } }, classroom: { select: { name: true } } },
      orderBy: [{ classroom: { name: "asc" } }, { rollNo: "asc" }],
      take: 40,
    });
    return NextResponse.json({
      rows: ens.map((e) => ({
        id: e.student.id,
        name: `${e.student.firstName} ${e.student.lastName}`.trim(),
        nyansapoIndex: e.student.nyansapoIndex,
        classroomName: e.classroom?.name ?? null,
      })),
    });
  }

  /* ── board (default): stat row + recent + open cases + catalogues + staff ── */
  const [recent, openCases, staff, classrooms, counts] = await Promise.all([
    db.counselCase.findMany({ where: confidentialWhere(schoolId, canManage, staffId), select: CASE_SELECT, orderBy: { updatedAt: "desc" }, take: 8 }),
    db.counselCase.findMany({
      where: { ...confidentialWhere(schoolId, canManage, staffId), status: { in: ["OPEN", "IN_PROGRESS", "MONITORING"] } },
      select: CASE_SELECT, orderBy: [{ updatedAt: "desc" }], take: 10,
    }),
    db.staff.findMany({ where: { schoolId, active: true }, select: { id: true, fullName: true, role: true, phone: true, smsNotify: true }, orderBy: { fullName: "asc" }, take: 200 }),
    db.classroom.findMany({ where: { schoolId }, select: { id: true, name: true }, orderBy: { name: "asc" } }),
    db.counselCase.groupBy({ by: ["status"], where: confidentialWhere(schoolId, canManage, staffId), _count: { _all: true } }),
  ]);
  const stat: Record<string, number> = { OPEN: 0, IN_PROGRESS: 0, MONITORING: 0, RESOLVED: 0, REFERRED: 0 };
  for (const g of counts) stat[g.status] = (stat[g.status] ?? 0) + g._count._all;
  const now = new Date();
  const [criticalOpen, overdueFollowUps] = await Promise.all([
    db.counselCase.count({ where: { ...confidentialWhere(schoolId, canManage, staffId), severity: { in: ["HIGH", "CRITICAL"] }, status: { in: ["OPEN", "IN_PROGRESS", "MONITORING"] } } }),
    db.counselFollowUp.count({ where: { doneAt: null, dueAt: { lt: now }, case: confidentialWhere(schoolId, canManage, staffId) } }),
  ]);

  return NextResponse.json({
    stat, criticalOpen, overdueFollowUps,
    recent: recent.map(toRow),
    openCases: openCases.map(toRow),
    staff, classrooms,
    catalogues: { categories: CATEGORIES, severities: SEVERITIES, statuses: STATUSES, sources: SOURCES, modes: MODES, outcomes: OUTCOMES },
    canManage, meStaffId: staffId,
  });
}

// ── POST /api/school/[id]/ops/counsel ─────────────────────────
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;

  // Referring a case is every staff member's right (counsel.view);
  // running the unit (assign, refer out, close, log sessions, follow-ups) needs counsel.manage.
  const isCreate = action === "case.create";
  const auth = await opsAuth(req, schoolId, "POST", { perms: [isCreate ? "counsel.view" : "counsel.manage"] });
  if (!auth.ok) return auth.res;
  const actor = auth.user;
  const actorName = actor.fullName || actor.email;
  const canManage = canAny(actor.permissions, ["counsel.manage"]);

  /* ── case.create — open a new G&C case ── */
  if (isCreate) {
    const { studentId, category, severity, title, summary, source, confidential, assignedToId, followUpAt, followUpNote } = body;
    const cat = enumVal(category, CATEGORIES);
    if (!cat || cat === "…invalid…") return bad("Choose a valid category");
    let sev = enumVal(severity, SEVERITIES) ?? "MEDIUM";
    if (severity && sev === "…invalid…") return bad("Severity must be LOW, MEDIUM, HIGH or CRITICAL");
    const t = optStr(title);
    if (!t) return bad("A short title is required");
    const src = enumVal(source, SOURCES) ?? "TEACHER_REFERRAL";
    // ABUSE_REPORT is never low-severity and never public
    if (cat === "ABUSE_REPORT") {
      sev = ["HIGH", "CRITICAL"].includes(sev) ? sev : "HIGH";
    }
    const student = await db.student.findUnique({
      where: { id: optStr(studentId) ?? "" },
      select: { id: true, enrollments: { where: { status: "ACTIVE" }, take: 1, select: { classroom: { select: { schoolId: true } } } } },
    });
    if (!student || !student.enrollments.length || student.enrollments[0].classroom.schoolId !== schoolId) {
      return bad("Learner not found at this school", 404);
    }
    // assignee: explicit → opener's staff record → null (head assigns later)
    let assigneeId = optStr(assignedToId);
    if (assigneeId) {
      const st = await db.staff.findUnique({ where: { id: assigneeId }, select: { id: true, schoolId: true } });
      if (!st || st.schoolId !== schoolId) return bad("Assigned staff member not found at this school", 404);
    } else {
      assigneeId = actor.staffId ?? null;
    }
    const caseNo = await nextCaseNo(schoolId);
    const created = await db.counselCase.create({
      data: {
        schoolId,
        studentId: student.id,
        caseNo,
        category: cat,
        severity: sev,
        status: "OPEN",
        source: src,
        title: t.slice(0, 160),
        summary: optStr(summary)?.slice(0, 4000) ?? null,
        confidential: cat === "ABUSE_REPORT" ? true : confidential === false ? false : true,
        assignedToId: assigneeId,
        openedByStaffId: actor.staffId ?? null,
        openedByName: actorName,
      },
      select: CASE_SELECT,
    });
    // optional first follow-up opened together with the case
    if (followUpAt && optStr(followUpNote)) {
      const d = new Date(followUpAt);
      if (!isNaN(d.getTime())) {
        await db.counselFollowUp.create({ data: { caseId: created.id, dueAt: d, note: optStr(followUpNote)!.slice(0, 300) } });
      }
    }
    audit({
      schoolId, actor: { id: actor.id, fullName: actorName, role: actor.role },
      action: "CREATE", entityType: "CounselCase", entityId: created.id, entityLabel: caseNo,
      summary: `Opened G&C case ${caseNo} — ${cat} (${sev}) for ${created.student.firstName} ${created.student.lastName}${created.confidential ? " · confidential" : ""}`,
      after: auditSnapshot(created as any), req,
    });
    // HIGH/CRITICAL: text the assigned counsellor right away — duty calls
    let sms: { status: string; provider?: string } | null = null;
    if (["HIGH", "CRITICAL"].includes(sev) && created.assignedTo) {
      const st = await db.staff.findUnique({ where: { id: created.assignedTo.id }, select: { fullName: true, phone: true, smsNotify: true } });
      if (st?.phone && st.smsNotify) {
        const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true } });
        const msg = await sendSms({
          db, schoolId,
          studentId: student.id,
          recipientName: st.fullName,
          recipientPhone: st.phone,
          body: `[${school?.name ?? "School"}] G&C ${caseNo} (${sev}): ${t} — learner ${created.student.firstName} ${created.student.lastName}. You are the assigned counsellor. Please arrange the first session. — Head's office`,
          category: "COUNSEL",
          sentByName: actorName,
        });
        sms = { status: msg.status, provider: msg.provider };
      }
    }
    return NextResponse.json({ ok: true, row: toRow(created), sms });
  }

  // every remaining action runs the unit → counsel.manage
  if (action === "case.update") {
    const { id, status, assignedToId, referredExternal, title, summary, category, severity, confidential, closedNote } = body;
    const existing = await db.counselCase.findUnique({ where: { id: optStr(id) ?? "" } });
    if (!existing || existing.schoolId !== schoolId) return bad("Case not found", 404);
    if (!canSeeCase(existing, canManage, actor.staffId ?? null)) return bad("This case is confidential — it is assigned to another counsellor", 403);

    const data: any = {};
    const before = auditSnapshot(existing);
    if (title != null) {
      const t = optStr(title);
      if (!t) return bad("Title cannot be empty");
      data.title = t.slice(0, 160);
    }
    if (summary != null) data.summary = optStr(summary)?.slice(0, 4000) ?? null;
    if (category != null) {
      const cat = enumVal(category, CATEGORIES);
      if (!cat || cat === "…invalid…") return bad("Choose a valid category");
      if (cat === "ABUSE_REPORT") data.confidential = true;
      data.category = cat;
    }
    if (severity != null) {
      const sev = enumVal(severity, SEVERITIES);
      if (!sev || sev === "…invalid…") return bad("Severity must be LOW, MEDIUM, HIGH or CRITICAL");
      if (existing.category === "ABUSE_REPORT" && !["HIGH", "CRITICAL"].includes(sev)) return bad("Abuse-report cases stay HIGH or CRITICAL");
      data.severity = sev;
    }
    if (assignedToId !== undefined) {
      const aid = optStr(assignedToId);
      if (aid) {
        const st = await db.staff.findUnique({ where: { id: aid }, select: { id: true, schoolId: true } });
        if (!st || st.schoolId !== schoolId) return bad("Assigned staff member not found at this school", 404);
      }
      data.assignedToId = aid;
    }
    if (confidential != null && existing.category !== "ABUSE_REPORT") data.confidential = !!confidential;
    if (referredExternal !== undefined) data.referredExternal = optStr(referredExternal)?.slice(0, 200) ?? null;

    if (status != null) {
      const st = enumVal(status, STATUSES);
      if (!st || st === "…invalid…") return bad("status must be OPEN, IN_PROGRESS, MONITORING, RESOLVED or REFERRED");
      data.status = st;
      if (st === "RESOLVED") {
        data.closedAt = new Date();
        if (optStr(closedNote)) data.closedNote = optStr(closedNote)!.slice(0, 1000);
      } else {
        data.closedAt = null;
        if (st === "REFERRED" && !optStr(referredExternal) && !existing.referredExternal) {
          return bad("Name the external service (DOVVSU, Social Welfare, hospital…) before marking the case REFERRED");
        }
      }
    } else if (optStr(closedNote) && existing.closedAt) {
      data.closedNote = optStr(closedNote)!.slice(0, 1000);
    }

    const updated = await db.counselCase.update({ where: { id: existing.id }, data, select: CASE_SELECT });
    audit({
      schoolId, actor: { id: actor.id, fullName: actorName, role: actor.role },
      action: "UPDATE", entityType: "CounselCase", entityId: existing.id, entityLabel: existing.caseNo,
      summary: `Updated G&C case ${existing.caseNo}${data.status ? ` → ${data.status}` : ""}${data.assignedToId !== undefined ? " · re-assigned" : ""}${data.referredExternal ? ` · referred to ${data.referredExternal}` : ""}`,
      before, after: auditSnapshot(updated as any), req,
    });
    return NextResponse.json({ ok: true, row: toRow(updated) });
  }

  if (action === "session.add") {
    const { caseId, heldAt, mode, notes, outcome, attendees, aiSummary } = body;
    const c = await db.counselCase.findUnique({ where: { id: optStr(caseId) ?? "" } });
    if (!c || c.schoolId !== schoolId) return bad("Case not found", 404);
    if (!canSeeCase(c, canManage, actor.staffId ?? null)) return bad("This case is confidential — it is assigned to another counsellor", 403);
    const raw = optStr(notes);
    if (!raw) return bad("Session notes are required — write what was discussed");
    const m = enumVal(mode, MODES) ?? "FACE_TO_FACE";
    if (mode && m === "…invalid…") return bad("Unknown session mode");
    let oc: string | null = null;
    if (outcome != null && optStr(outcome)) {
      oc = enumVal(outcome, OUTCOMES);
      if (!oc || oc === "…invalid…") return bad("Unknown outcome");
    }
    let held: Date = new Date();
    if (optStr(heldAt)) {
      held = new Date(heldAt);
      if (isNaN(held.getTime())) return bad("Invalid session date");
      if (held.getTime() > Date.now() + 86400000) return bad("Session date cannot be in the future");
    }
    const s = await db.counselSession.create({
      data: {
        caseId: c.id, heldAt: held, mode: m,
        notes: raw.slice(0, 20000),
        aiSummary: optStr(aiSummary)?.slice(0, 6000) ?? null,
        outcome: oc,
        attendees: optStr(attendees)?.slice(0, 300) ?? null,
        loggedByName: actorName,
      },
    });
    // a logged session means the work has started
    if (c.status === "OPEN") {
      await db.counselCase.update({ where: { id: c.id }, data: { status: "IN_PROGRESS" } });
    }
    audit({
      schoolId, actor: { id: actor.id, fullName: actorName, role: actor.role },
      action: "CREATE", entityType: "CounselSession", entityId: s.id, entityLabel: `${c.caseNo} session`,
      summary: `Logged a ${m.toLowerCase().replace(/_/g, " ")} session on G&C case ${c.caseNo}${oc ? ` · outcome ${oc}` : ""}`,
      req,
    });
    return NextResponse.json({ ok: true, session: { id: s.id, heldAt: s.heldAt.toISOString(), mode: s.mode, notes: s.notes, aiSummary: s.aiSummary, outcome: s.outcome, attendees: s.attendees, loggedByName: s.loggedByName } });
  }

  /* ── session.ai-draft — turn raw notes into a professional confidential
         summary. Grounded: only rephrases what the counsellor wrote; the
         deterministic fallback tidies the raw text when AI is down. ── */
  if (action === "session.ai-draft") {
    const raw = optStr(body.notes);
    if (!raw) return bad("Write the raw session notes first");
    const c = await db.counselCase.findUnique({
      where: { id: optStr(body.caseId) ?? "" },
      include: { student: { select: { firstName: true } } },
    });
    if (!c || c.schoolId !== schoolId) return bad("Case not found", 404);
    if (!canSeeCase(c, canManage, actor.staffId ?? null)) return bad("This case is confidential — it is assigned to another counsellor", 403);
    const firstName = c.student.firstName;
    const fallback = () => {
      const sentences = raw.replace(/\s+/g, " ").split(/(?<=[.!?])\s+/).filter(Boolean);
      const head = `Session held regarding ${c.title.toLowerCase()} (case ${c.caseNo}). `;
      const bodyText = sentences.slice(0, 6).join(" ");
      return `${head}${bodyText}`.slice(0, 2000);
    };
    try {
      const { default: ZAI } = await import("z-ai-web-dev-sdk");
      const client = await ZAI.create();
      const completion = await client.chat.completions.create({
        messages: [
          {
            role: "system",
            content: `You are a school Guidance & Counselling coordinator in Ghana. Rewrite the counsellor's raw session notes into a concise professional summary (2-4 sentences) suitable for the confidential case file. Third person, past tense, refer to the learner by first name (${firstName}). NEVER invent facts, names or events that are not in the notes. Keep the tone factual and compassionate. Output ONLY the summary text, no headings, no quotes.`,
          },
          { role: "user", content: `Case: ${c.title} (category ${c.category}). Raw notes:\n${raw.slice(0, 4000)}` },
        ],
        thinking: { type: "disabled" },
      });
      const text = (completion.choices[0]?.message?.content ?? "").trim();
      return NextResponse.json({ summary: text.length > 20 ? text.slice(0, 4000) : fallback() });
    } catch {
      return NextResponse.json({ summary: fallback(), fallback: true });
    }
  }

  if (action === "followup.create") {
    const { caseId, dueAt, note } = body;
    const c = await db.counselCase.findUnique({ where: { id: optStr(caseId) ?? "" } });
    if (!c || c.schoolId !== schoolId) return bad("Case not found", 404);
    if (!canSeeCase(c, canManage, actor.staffId ?? null)) return bad("This case is confidential — it is assigned to another counsellor", 403);
    const n = optStr(note);
    if (!n) return bad("What should be checked on?");
    const d = new Date(dueAt);
    if (isNaN(d.getTime())) return bad("Pick a valid due date");
    const f = await db.counselFollowUp.create({ data: { caseId: c.id, dueAt: d, note: n.slice(0, 300) } });
    audit({
      schoolId, actor: { id: actor.id, fullName: actorName, role: actor.role },
      action: "CREATE", entityType: "CounselFollowUp", entityId: f.id, entityLabel: c.caseNo,
      summary: `Follow-up on G&C case ${c.caseNo} due ${d.toLocaleDateString("en-GB")} — ${n.slice(0, 120)}`,
      req,
    });
    return NextResponse.json({ ok: true, followUp: { id: f.id, dueAt: f.dueAt.toISOString(), note: f.note, doneAt: null, overdue: false, remindedAt: null } });
  }

  if (action === "followup.complete") {
    const { id, doneNote } = body;
    const f = await db.counselFollowUp.findUnique({ where: { id: optStr(id) ?? "" }, include: { case: true } });
    if (!f || f.case.schoolId !== schoolId) return bad("Follow-up not found", 404);
    if (!canSeeCase(f.case, canManage, actor.staffId ?? null)) return bad("This case is confidential — it is assigned to another counsellor", 403);
    if (f.doneAt) return bad("Already completed");
    await db.counselFollowUp.update({ where: { id: f.id }, data: { doneAt: new Date(), doneNote: optStr(doneNote)?.slice(0, 500) ?? null } });
    audit({
      schoolId, actor: { id: actor.id, fullName: actorName, role: actor.role },
      action: "UPDATE", entityType: "CounselFollowUp", entityId: f.id, entityLabel: f.case.caseNo,
      summary: `Follow-up completed on G&C case ${f.case.caseNo}`,
      req,
    });
    return NextResponse.json({ ok: true });
  }

  /* ── followup.remind — SMS the assigned counsellor: "you promised to check
         on this learner". No guardian is ever texted about a case. ── */
  if (action === "followup.remind") {
    const { id } = body;
    const f = await db.counselFollowUp.findUnique({
      where: { id: optStr(id) ?? "" },
      include: { case: { include: { assignedTo: { select: { id: true, fullName: true, phone: true, smsNotify: true } }, student: { select: { firstName: true, lastName: true } } } } },
    });
    if (!f || f.case.schoolId !== schoolId) return bad("Follow-up not found", 404);
    if (!canSeeCase(f.case, canManage, actor.staffId ?? null)) return bad("This case is confidential — it is assigned to another counsellor", 403);
    if (f.doneAt) return bad("This follow-up is already done");
    const st = f.case.assignedTo;
    if (!st?.phone) return bad("The case has no assigned counsellor with a phone number");
    if (!st.smsNotify) return bad(`${st.fullName} has opted out of SMS alerts — remind them in person`);
    const school = await db.school.findUnique({ where: { id: schoolId }, select: { name: true } });
    const due = new Date(f.dueAt).toLocaleDateString("en-GB", { day: "numeric", month: "short" });
    const msg = await sendSms({
      db, schoolId,
      studentId: f.case.studentId,
      recipientName: st.fullName,
      recipientPhone: st.phone,
      body: `[${school?.name ?? "School"}] G&C reminder — ${f.case.caseNo} (${f.case.student.firstName} ${f.case.student.lastName}): "${f.note}" due ${due}. — Head's office`,
      category: "COUNSEL",
      sentByName: actorName,
    });
    if (msg.status === "FAILED") {
      return NextResponse.json({ error: msg.errorReason ?? "SMS failed — check SMS credits in Settings" }, { status: 409 });
    }
    await db.counselFollowUp.update({ where: { id: f.id }, data: { remindedAt: new Date() } });
    return NextResponse.json({ ok: true, sms: { status: msg.status, provider: msg.provider } });
  }

  return bad("Unknown action");
}

import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";

/* ── Wave 31 · Audit trail — the compliance book (iEduPro audit_log_viewer parity) ──
 * GET /ops/audit — the register: paginated timeline with actor / action /
 * entity / date-range / keyword filters, stat cards (today · 7d · 30d),
 * per-action and per-actor mixes, the actors & entity-type catalogues for
 * the filter dropdowns, and a CSV export (?format=csv) for the auditors.
 * Writes never happen here — modules post audit lines through lib/audit.
 */

const ACTIONS = [
  "CREATE", "UPDATE", "DELETE", "ISSUE", "REVOKE", "REINSTATE",
  "PUBLISH", "UNPUBLISH", "APPROVE", "REJECT", "LOCK", "UNLOCK",
  "VOID", "EXPORT", "ADJUST", "TRANSFER", "LOGIN",
];

const bad = (error: string, status = 400) => NextResponse.json({ error }, { status });
const optStr = (v: unknown): string | null => {
  if (typeof v !== "string") return null;
  const t = v.trim();
  return t.length ? t : null;
};
const dayStart = (v: unknown): Date | null => {
  if (typeof v !== "string" || !/^\d{4}-\d{2}-\d{2}/.test(String(v).trim())) return null;
  const d = new Date(`${String(v).trim().slice(0, 10)}T00:00:00.000Z`);
  return isNaN(d.getTime()) ? null : d;
};
const dayEnd = (v: unknown): Date | null => {
  const d = dayStart(v);
  return d ? new Date(d.getTime() + 24 * 3600 * 1000 - 1) : null;
};

export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["audit.view"] });
  if (!auth.ok) return auth.res;

  const url = new URL(req.url);
  const action = optStr(url.searchParams.get("action"));
  const entityType = optStr(url.searchParams.get("entityType"));
  const actorId = optStr(url.searchParams.get("actorId"));
  const q = optStr(url.searchParams.get("q"))?.toLowerCase() ?? null;
  const from = dayStart(url.searchParams.get("from"));
  const to = dayEnd(url.searchParams.get("to"));
  const page = Math.max(parseInt(url.searchParams.get("page") ?? "1", 10) || 1, 1);
  const limit = Math.min(parseInt(url.searchParams.get("limit") ?? "60", 10) || 60, 200);
  const format = optStr(url.searchParams.get("format"));

  const where: any = { schoolId };
  if (action && ACTIONS.includes(action)) where.action = action;
  if (entityType) where.entityType = entityType;
  if (actorId) where.actorId = actorId;
  if (from || to) {
    where.createdAt = {};
    if (from) where.createdAt.gte = from;
    if (to) where.createdAt.lte = to;
  }

  // keyword: actor, entity label, summary
  if (q) {
    where.OR = [
      { actorName: { contains: q, mode: "insensitive" } },
      { entityLabel: { contains: q, mode: "insensitive" } },
      { summary: { contains: q, mode: "insensitive" } },
      { entityType: { contains: q, mode: "insensitive" } },
    ];
  }

  /* ── CSV export for the auditors ── */
  if (format === "csv") {
    const rows = await db.auditLog.findMany({ where, orderBy: { createdAt: "desc" }, take: 5000 });
    const esc = (s: unknown) => `"${String(s ?? "").replace(/"/g, '""')}"`;
    const header = "When,Actor,Role,Action,Entity,Reference,Summary,IP";
    const lines = rows.map((r) =>
      [r.createdAt.toISOString(), r.actorName, r.actorRole ?? "", r.action, r.entityType, r.entityLabel ?? "", r.summary, r.ip ?? ""]
        .map(esc).join(","));
    return new NextResponse([header, ...lines].join("\n"), {
      headers: {
        "content-type": "text/csv; charset=utf-8",
        "content-disposition": `attachment; filename="audit-trail-${new Date().toISOString().slice(0, 10)}.csv"`,
      },
    });
  }

  const [rows, total, byAction, topActors, today, week, month, actors, entityTypes] = await Promise.all([
    db.auditLog.findMany({ where, orderBy: { createdAt: "desc" }, skip: (page - 1) * limit, take: limit }),
    db.auditLog.count({ where }),
    db.auditLog.groupBy({ by: ["action"], where: { schoolId }, _count: { _all: true } }),
    db.auditLog.groupBy({ by: ["actorName"], where: { schoolId }, _count: { _all: true }, orderBy: { _count: { actorName: "desc" } }, take: 8 }),
    db.auditLog.count({ where: { schoolId, createdAt: { gte: new Date(new Date().toISOString().slice(0, 10) + "T00:00:00.000Z") } } }),
    db.auditLog.count({ where: { schoolId, createdAt: { gte: new Date(Date.now() - 7 * 24 * 3600 * 1000) } } }),
    db.auditLog.count({ where: { schoolId, createdAt: { gte: new Date(Date.now() - 30 * 24 * 3600 * 1000) } } }),
    db.user.findMany({
      where: { OR: [{ schoolId }, { role: { in: ["SUPER_ADMIN", "GROUP_OWNER"] } }] },
      select: { id: true, fullName: true, role: true },
      take: 80,
    }),
    db.auditLog.groupBy({ by: ["entityType"], where: { schoolId }, _count: { _all: true }, orderBy: { _count: { entityType: "desc" } } }),
  ]);

  return NextResponse.json({
    rows: rows.map((r) => ({
      id: r.id,
      actorId: r.actorId,
      actorName: r.actorName,
      actorRole: r.actorRole,
      action: r.action,
      entityType: r.entityType,
      entityId: r.entityId,
      entityLabel: r.entityLabel,
      summary: r.summary,
      before: r.beforeJson ?? null,
      after: r.afterJson ?? null,
      ip: r.ip,
      createdAt: r.createdAt.toISOString(),
    })),
    total,
    page,
    pages: Math.max(Math.ceil(total / limit), 1),
    stats: {
      today,
      week,
      month,
      byAction: byAction.map((a) => ({ action: a.action, count: a._count._all })).sort((a, b) => b.count - a.count),
      byEntity: entityTypes.map((e) => ({ entityType: e.entityType, count: e._count._all })).sort((a, b) => b.count - a.count),
      topActors: topActors.map((a) => ({ name: a.actorName, count: a._count._all })),
    },
    actors: actors.map((u) => ({ id: u.id, name: u.fullName, role: u.role })),
    entityTypes: entityTypes.map((e) => e.entityType),
    actions: ACTIONS,
  });
}

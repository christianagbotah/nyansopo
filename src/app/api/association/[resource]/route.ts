import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser, isAssociation } from "@/lib/session";

// GET /api/association/[resource]
export async function GET(req: NextRequest, { params }: { params: Promise<{ resource: string }> }) {
  const { resource } = await params;
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
  if (!isAssociation(user.role)) return NextResponse.json({ error: "Association access only" }, { status: 403 });

  switch (resource) {
    case "overview": {
      const schools = await db.school.findMany({ where: user.nodeIds.length ? { associationNodeId: { in: user.nodeIds } } : undefined, include: { associationNode: true } });
      const node = user.associationNodeId ? await db.associationNode.findUnique({ where: { id: user.associationNodeId } }) : null;
      const flags = await db.studentFlag.findMany({ where: { school: { associationNodeId: { in: user.nodeIds } } }, include: { student: true, school: { select: { id: true, name: true, town: true } }, verifications: true }, orderBy: { raisedAt: "desc" } });
      const transfers = await db.transferRecord.findMany({ where: { OR: [{ fromSchool: { associationNodeId: { in: user.nodeIds } } }, { toSchool: { associationNodeId: { in: user.nodeIds } } }] }, include: { student: true, fromSchool: { select: { id: true, name: true, town: true } }, toSchool: { select: { id: true, name: true, town: true } } }, orderBy: { date: "desc" }, take: 30 });
      const students = await db.enrollment.findMany({ where: { schoolId: { in: schools.map((s) => s.id) }, status: "ACTIVE" } });
      const byRegion: Record<string, number> = {};
      schools.forEach((s) => (byRegion[s.region] = (byRegion[s.region] ?? 0) + 1));
      const districts = await db.associationNode.findMany({ where: { level: "DISTRICT", OR: [{ id: { in: user.nodeIds } }, { parentId: { in: user.nodeIds } }] } });
      const schoolCounts = await Promise.all(
        (node?.level === "NATIONAL" || node?.level === "REGIONAL" ? districts : [node!]).filter(Boolean).map(async (d) => {
          const cnt = await db.school.count({ where: { associationNodeId: { in: (await db.associationNode.findMany({ where: { OR: [{ id: d.id }, { parentId: d.id }] } })).map((z) => z.id) } } });
          return { name: d.name, schools: cnt, id: d.id };
        })
      );
      const announcements = await db.announcement.findMany({ where: { associationNodeId: { in: user.nodeIds } }, orderBy: { postedAt: "desc" }, take: 10, include: { associationNode: true } });
      return NextResponse.json({
        scope: { node: node ? { id: node.id, name: node.name, level: node.level, leadName: node.leadName, leadTitle: node.leadTitle } : null, schoolCount: schools.length, studentCount: students.length },
        stats: {
          schools: schools.length,
          licensed: schools.filter((s) => s.nasiaStatus === "LICENSED").length,
          provisional: schools.filter((s) => s.nasiaStatus === "PROVISIONAL").length,
          pendingNasia: schools.filter((s) => s.nasiaStatus === "PENDING" || s.nasiaStatus === "EXPIRED").length,
          verified: schools.filter((s) => s.gnacopsStatus === "VERIFIED").length,
          pendingGnacops: schools.filter((s) => s.gnacopsStatus !== "VERIFIED").length,
          activeFlags: flags.filter((f) => f.status === "ACTIVE").length,
          criticalFlags: flags.filter((f) => f.status === "ACTIVE" && f.severity === "CRITICAL").length,
          contestedFlags: flags.filter((f) => f.status === "CONTESTED").length,
          pendingReleases: transfers.filter((t) => t.status === "PENDING_RELEASE").length,
          byRegion,
        },
        schoolCounts,
        flags: flags.slice(0, 50).map((f) => ({
          id: f.id, student: `${f.student.firstName} ${f.student.lastName}`, studentId: f.studentId, nyansapoIndex: f.student.nyansapoIndex,
          school: f.school, type: f.type, severity: f.severity, status: f.status, amountOwed: f.amountOwedCents,
          description: f.description, raisedAt: f.raisedAt, raisedByName: f.raisedByName,
          verifications: f.verifications.map((v) => ({ officerName: v.officerName, officerTitle: v.officerTitle, action: v.action, note: v.note, verifiedAt: v.verifiedAt })),
        })),
        transfers: transfers.map((t) => ({ id: t.id, student: `${t.student.firstName} ${t.student.lastName}`, studentId: t.studentId, from: t.fromSchool, to: t.toSchool, date: t.date, status: t.status, outstanding: t.outstandingCents, reason: t.reason })),
        announcements: announcements.map((a) => ({ id: a.id, title: a.title, body: a.body, category: a.category, priority: a.priority, postedByName: a.postedByName, postedAt: a.postedAt, source: a.associationNode?.name })),
      });
    }

    case "schools": {
      const schools = await db.school.findMany({ where: user.nodeIds.length ? { associationNodeId: { in: user.nodeIds } } : undefined, include: { associationNode: true, group: true } });
      const enrollCounts = await db.enrollment.groupBy({ by: ["schoolId"], where: { status: "ACTIVE", schoolId: { in: schools.map((s) => s.id) } }, _count: true });
      const flagCounts = await db.studentFlag.groupBy({ by: ["schoolId"], where: { status: "ACTIVE", schoolId: { in: schools.map((s) => s.id) } }, _count: true });
      const ec = new Map(enrollCounts.map((e) => [e.schoolId, e._count]));
      const fc = new Map(flagCounts.map((f) => [f.schoolId, f._count]));
      return NextResponse.json({
        schools: schools.map((s) => ({
          id: s.id, name: s.name, code: s.code, town: s.town, region: s.region, curriculum: s.curriculum, category: s.category,
          zone: s.associationNode.name, zoneCode: s.associationNode.code,
          group: s.group?.name ?? null, isBranchMain: s.isBranchMain,
          nasiaStatus: s.nasiaStatus, nasiaRegNo: s.nasiaRegNo, gnacopsStatus: s.gnacopsStatus,
          studentsOnRoll: ec.get(s.id) ?? 0, activeFlags: fc.get(s.id) ?? 0,
          phone: s.phone, email: s.email, establishedYear: s.establishedYear,
        })),
      });
    }

    default:
      return NextResponse.json({ error: "Unknown resource" }, { status: 404 });
  }
}

// POST /api/association/[resource] — validate school, verify flag, announce
export async function POST(req: NextRequest, { params }: { params: Promise<{ resource: string }> }) {
  const { resource } = await params;
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
  if (!isAssociation(user.role)) return NextResponse.json({ error: "Association access only" }, { status: 403 });
  const body = await req.json();

  if (resource === "validate") {
    const { schoolId, nasiaStatus, gnacopsStatus } = body;
    const s = await db.school.update({ where: { id: schoolId }, data: { nasiaStatus: nasiaStatus ?? undefined, gnacopsStatus: gnacopsStatus ?? undefined, gnacopsMemberSince: gnacopsStatus === "VERIFIED" ? new Date() : undefined } });
    return NextResponse.json({ school: { id: s.id, nasiaStatus: s.nasiaStatus, gnacopsStatus: s.gnacopsStatus } });
  }
  if (resource === "verify-flag") {
    const { flagId, action, note } = body;
    const v = await db.flagVerification.create({ data: { flagId, officerName: user.fullName, officerTitle: user.title ?? "Association Officer", action, note: note ?? null } });
    return NextResponse.json({ verification: v });
  }
  if (resource === "announce") {
    const { title, body: bodyText, audience, category, priority } = body;
    const a = await db.announcement.create({ data: { associationNodeId: user.associationNodeId, title, body: bodyText, audience, category: category ?? "GENERAL", priority: priority ?? "NORMAL", postedByName: user.fullName } });
    return NextResponse.json({ announcement: a });
  }
  return NextResponse.json({ error: "Unknown resource" }, { status: 404 });
}

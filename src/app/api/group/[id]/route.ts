import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser } from "@/lib/session";

// GET /api/group/[id] — multi-branch console for proprietors
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
  const group = await db.schoolGroup.findUnique({ where: { id }, include: { schools: { include: { associationNode: true } } } });
  if (!group) return NextResponse.json({ error: "Group not found" }, { status: 404 });
  if (!user.schoolIds.some((sid) => group.schools.some((s) => s.id === sid))) return NextResponse.json({ error: "Forbidden" }, { status: 403 });

  const branches = [];
  for (const s of group.schools) {
    const term = await db.term.findFirst({ where: { isCurrent: true, academicYear: { schoolId: s.id } } });
    const invoices = await db.invoice.findMany({ where: { schoolId: s.id, termId: term?.id } });
    const billed = invoices.reduce((a, i) => a + i.totalCents - i.discountCents, 0);
    const collected = invoices.reduce((a, i) => a + i.paidCents, 0);
    const students = await db.enrollment.count({ where: { schoolId: s.id, status: "ACTIVE" } });
    const flags = await db.studentFlag.count({ where: { schoolId: s.id, status: "ACTIVE" } });
    const staff = await db.staff.count({ where: { schoolId: s.id } });
    const sessions = await db.attendanceSession.findMany({ where: { schoolId: s.id }, orderBy: { date: "desc" }, take: 5, include: { records: true } });
    const attRate = sessions.length ? Math.round((sessions.reduce((a, s2) => a + s2.records.filter((r) => r.status === "PRESENT" || r.status === "LATE").length, 0) / sessions.reduce((a, s2) => a + s2.records.length, 0)) * 100) : null;
    branches.push({
      id: s.id, name: s.name, code: s.code, town: s.town, isMain: s.isBranchMain, zone: s.associationNode.name,
      nasiaStatus: s.nasiaStatus, gnacopsStatus: s.gnacopsStatus, curriculum: s.curriculum,
      students, staff, flags, attendanceRate: attRate,
      billed, collected, arrears: billed - collected,
      collectionRate: billed ? Math.round((collected / billed) * 100) : 0,
    });
  }
  return NextResponse.json({ group: { id: group.id, name: group.name, proprietorName: group.proprietorName }, branches });
}

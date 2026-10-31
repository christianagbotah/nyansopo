import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser } from "@/lib/session";

export async function GET(req: NextRequest) {
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });

  const nodes = await db.associationNode.findMany({ orderBy: { name: "asc" } });
  const schools = await db.school.findMany({
    where: user.schoolIds.length ? { id: { in: user.schoolIds } } : undefined,
    orderBy: { name: "asc" },
  });

  return NextResponse.json({
    user: {
      id: user.id, email: user.email, fullName: user.fullName, phone: user.phone,
      role: user.role, title: user.title,
      associationNodeId: user.associationNodeId,
      schoolGroupId: user.schoolGroupId, schoolId: user.schoolId,
    },
    schools: schools.map((s) => ({
      id: s.id, name: s.name, code: s.code, curriculum: s.curriculum, category: s.category,
      region: s.region, town: s.town, groupId: s.groupId, isBranchMain: s.isBranchMain,
      nasiaStatus: s.nasiaStatus, gnacopsStatus: s.gnacopsStatus, studentsOnRoll: s.studentsOnRoll,
      logoColor: s.logoColor, phone: s.phone, email: s.email,
    })),
    associationTree: nodes.map((n) => ({ id: n.id, code: n.code, name: n.name, level: n.level, parentId: n.parentId, leadName: n.leadName, leadTitle: n.leadTitle, region: n.region })),
  });
}

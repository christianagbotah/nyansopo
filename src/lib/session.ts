import { db } from "@/lib/db";
import { NextRequest } from "next/server";

export type SessionUser = {
  id: string; email: string; fullName: string; phone: string | null;
  role: string; title: string | null;
  associationNodeId: string | null; schoolGroupId: string | null; schoolId: string | null;
};

export async function getUser(req: NextRequest): Promise<(SessionUser & { nodeIds: string[]; schoolIds: string[] }) | null> {
  const userId = req.headers.get("x-user-id");
  if (!userId) return null;
  const u = await db.user.findUnique({
    where: { id: userId },
    include: { associationNode: true, school: true, schoolGroup: { include: { schools: true } } },
  });
  if (!u) return null;

  // scope: node ids this user can see (self + descendants)
  let nodeIds: string[] = [];
  if (u.associationNodeId) {
    const all = await db.associationNode.findMany();
    const collect = (id: string) => {
      nodeIds.push(id);
      all.filter((n) => n.parentId === id).forEach((c) => collect(c.id));
    };
    collect(u.associationNodeId);
  }
  // scope: schools this user can operate
  let schoolIds: string[] = [];
  if (u.role === "GROUP_OWNER" && u.schoolGroup) schoolIds = u.schoolGroup.schools.map((s) => s.id);
  else if (u.schoolId) schoolIds = [u.schoolId];
  else if (nodeIds.length) {
    const s = await db.school.findMany({ where: { associationNodeId: { in: nodeIds } } });
    schoolIds = s.map((x) => x.id);
  }

  return {
    id: u.id, email: u.email, fullName: u.fullName, phone: u.phone, role: u.role, title: u.title,
    associationNodeId: u.associationNodeId, schoolGroupId: u.schoolGroupId, schoolId: u.schoolId,
    nodeIds, schoolIds,
  };
}

export const canOperateSchool = (user: { schoolIds: string[] }, schoolId: string) => user.schoolIds.includes(schoolId);
export const isAssociation = (role: string) => role.startsWith("ASSOCIATION_") || role === "SYSTEM_ADMIN";
export const isSchoolStaff = (role: string) => ["SCHOOL_ADMIN", "TEACHER", "ACCOUNTANT", "GROUP_OWNER"].includes(role);

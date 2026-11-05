import { db } from "@/lib/db";
import { NextRequest } from "next/server";
import { effectivePermissions, isBackofficeRole, parseRolePerms, landingFor, ROLE_LEVELS, LANDING_MODULE } from "@/lib/rbac";

export type SessionUser = {
  id: string; email: string; fullName: string; phone: string | null;
  role: string; title: string | null;
  associationNodeId: string | null; schoolGroupId: string | null; schoolId: string | null;
  permissions: string[];
  staffId: string | null; // STAFF portal accounts: own staff record
  studentId: string | null; // STUDENT portal accounts: own record
  linkedStudentIds: string[]; // PARENT portal accounts: own children
  // ── role-config layer (wave 10): filled when the school has an active
  //    RoleConfig for this role (custom roles / tuned system roles) ──
  roleLabel?: string | null; // display label from the config (custom roles)
  roleLevel?: number | null; // admin-ladder level (config or static)
  roleBackoffice?: boolean; // true for custom backoffice roles (module gating)
  landingModule?: string; // where this user lands after login
};

/** Load the school's active RoleConfig for a role (null = none → code defaults). */
export async function loadRoleConfig(schoolId: string | null | undefined, role: string) {
  if (!schoolId) return null;
  return db.roleConfig.findFirst({ where: { schoolId, role, active: true } });
}

export async function getUser(req: NextRequest): Promise<(SessionUser & { nodeIds: string[]; schoolIds: string[] }) | null> {
  const userId = req.headers.get("x-user-id");
  if (!userId) return null;
  const u = await db.user.findUnique({
    where: { id: userId },
    include: { associationNode: true, school: true, schoolGroup: { include: { schools: true } } },
  });
  if (!u) return null;
  if (u.status === "SUSPENDED") return null; // suspended accounts cannot authenticate

  // role configuration for this school (wave 10): tuned system roles + custom roles
  const cfg = u.schoolId ? await loadRoleConfig(u.schoolId, u.role) : null;
  const configPerms = cfg ? parseRolePerms(cfg.permissions) : null;

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
    permissions: effectivePermissions(u.role, u.permissionsJson, configPerms),
    staffId: u.staffId ?? null,
    studentId: u.studentId ?? null,
    linkedStudentIds: parseLinked(u.linkedStudentIds),
    roleLabel: cfg?.label ?? null,
    roleLevel: cfg?.level ?? ROLE_LEVELS[u.role] ?? null,
    roleBackoffice: cfg?.isCustom ? true : undefined,
    landingModule: landingFor(u.role, cfg?.landingModule ?? null),
  };
}

function parseLinked(json?: string | null): string[] {
  try { const v = JSON.parse(json ?? "[]"); return Array.isArray(v) ? v : []; } catch { return []; }
}

/**
 * A user may OPERATE (write) on a school only if they are back-office staff
 * (not a parent / student portal account, not an association officer) attached
 * to that school — association officers keep their read-only oversight view.
 * Custom roles (wave 10) are backoffice by construction.
 */
export const canOperateSchool = (user: { schoolIds: string[]; role: string; roleBackoffice?: boolean }, schoolId: string) =>
  (isBackofficeRole(user.role) || user.roleBackoffice === true) && !user.role.startsWith("ASSOCIATION_") && user.schoolIds.includes(schoolId);

export const isAssociation = (role: string) => role.startsWith("ASSOCIATION_") || role === "SYSTEM_ADMIN";
export const isSchoolStaff = (role: string) => ["SUPER_ADMIN", "GROUP_OWNER", "SCHOOL_ADMIN", "TEACHER", "ACCOUNTANT", "CASHIER", "BUS_CONDUCTOR", "LIBRARIAN", "NON_TEACHING"].includes(role);

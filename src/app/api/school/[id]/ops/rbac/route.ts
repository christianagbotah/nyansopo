import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { ROLE_DEFAULTS, effectivePermissions, ALL_PERM_IDS } from "@/lib/rbac";

// Roles that can be assigned from the school RBAC console (network roles excluded)
export const ASSIGNABLE_ROLES = [
  "SUPER_ADMIN", "SCHOOL_ADMIN", "ACCOUNTANT", "CASHIER", "BUS_CONDUCTOR",
  "TEACHER", "NON_TEACHING", "LIBRARIAN", "PARENT", "STUDENT",
];

const PROTECTED_ROLES = ["GROUP_OWNER", "ASSOCIATION_NATIONAL", "ASSOCIATION_REGIONAL", "ASSOCIATION_DISTRICT", "ASSOCIATION_ZONAL"];

// ── GET /api/school/[id]/ops/rbac — user accounts, roles & permission overrides ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const auth = await opsAuth(req, schoolId, "GET", { perms: ["rbac.manage"] });
  if (!auth.ok) return auth.res;

  const users = await db.user.findMany({
    where: { schoolId },
    include: { staff: { select: { id: true, fullName: true, position: true, category: true } } },
    orderBy: [{ role: "asc" }, { fullName: "asc" }],
  });
  const staff = await db.staff.findMany({
    where: { schoolId, active: true },
    select: { id: true, fullName: true, position: true, category: true, role: true },
    orderBy: { fullName: "asc" },
  });

  return NextResponse.json({
    users: users.map((u) => ({
      id: u.id, email: u.email, fullName: u.fullName, phone: u.phone, role: u.role, title: u.title,
      status: u.status, lastLoginAt: u.lastLoginAt,
      permissions: effectivePermissions(u.role, u.permissionsJson),
      overrides: u.permissionsJson ? JSON.parse(u.permissionsJson) : {},
      staffId: u.staffId, studentId: u.studentId,
      linkedStudentIds: u.linkedStudentIds ? JSON.parse(u.linkedStudentIds) : [],
      staffName: u.staff?.fullName ?? null,
      createdAt: u.createdAt,
    })),
    staff,
    assignableRoles: ASSIGNABLE_ROLES,
    protectedRoles: PROTECTED_ROLES,
  });
}

// ── POST /api/school/[id]/ops/rbac — create / update accounts, permissions, status ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: ["rbac.manage"] });
  if (!auth.ok) return auth.res;
  const me = auth.user;

  const findTarget = async (id: string) => {
    const u = await db.user.findUnique({ where: { id } });
    if (!u || u.schoolId !== schoolId) return null;
    if (PROTECTED_ROLES.includes(u.role)) return "protected" as const;
    return u;
  };

  if (action === "user.create") {
    const { email, password, fullName, phone, role, title, staffId, studentId, linkedStudentIds } = body;
    if (!email || !password || !fullName || !role) return NextResponse.json({ error: "Email, password, full name and role are required" }, { status: 400 });
    if (!ASSIGNABLE_ROLES.includes(role)) return NextResponse.json({ error: "This role is managed at network level and cannot be assigned here" }, { status: 400 });
    const exists = await db.user.findUnique({ where: { email: String(email).trim().toLowerCase() } });
    if (exists) return NextResponse.json({ error: "A user with this email already exists" }, { status: 400 });
    const user = await db.user.create({
      data: {
        email: String(email).trim().toLowerCase(), password: String(password), fullName, phone: phone ?? null,
        role, title: title ?? null, schoolId,
        staffId: staffId || null, studentId: studentId || null,
        linkedStudentIds: Array.isArray(linkedStudentIds) && linkedStudentIds.length ? JSON.stringify(linkedStudentIds) : null,
      },
    });
    return NextResponse.json({ user: { id: user.id, email: user.email, fullName: user.fullName, role: user.role } });
  }

  if (action === "user.update") {
    const target = await findTarget(body.id);
    if (!target) return NextResponse.json({ error: "User not found in this school" }, { status: 404 });
    if (target === "protected") return NextResponse.json({ error: "Network accounts (proprietor / association) are managed at group level" }, { status: 403 });
    if (target.id === me.id && body.role && body.role !== target.role) {
      return NextResponse.json({ error: "You cannot change your own role" }, { status: 400 });
    }
    if (target.id === me.id && body.status === "SUSPENDED") {
      return NextResponse.json({ error: "You cannot suspend your own account" }, { status: 400 });
    }
    if (body.role && !ASSIGNABLE_ROLES.includes(body.role)) {
      return NextResponse.json({ error: "This role is managed at network level and cannot be assigned here" }, { status: 400 });
    }
    const data: Record<string, any> = {};
    for (const k of ["fullName", "phone", "title", "role", "status"]) if (body[k] !== undefined) data[k] = body[k];
    if (body.staffId !== undefined) data.staffId = body.staffId || null;
    if (body.studentId !== undefined) data.studentId = body.studentId || null;
    if (body.linkedStudentIds !== undefined) data.linkedStudentIds = Array.isArray(body.linkedStudentIds) && body.linkedStudentIds.length ? JSON.stringify(body.linkedStudentIds) : null;
    const user = await db.user.update({ where: { id: target.id }, data });
    return NextResponse.json({ user: { id: user.id, role: user.role, status: user.status } });
  }

  if (action === "user.perms") {
    const target = await findTarget(body.id);
    if (!target) return NextResponse.json({ error: "User not found in this school" }, { status: 404 });
    if (target === "protected") return NextResponse.json({ error: "Network accounts are managed at group level" }, { status: 403 });
    const extra: string[] = Array.isArray(body.extra) ? body.extra.filter((p: string) => ALL_PERM_IDS.includes(p)) : [];
    const denied: string[] = Array.isArray(body.denied) ? body.denied.filter((p: string) => ALL_PERM_IDS.includes(p)) : [];
    // guard: cannot strip rbac.manage from yourself
    if (target.id === me.id && denied.includes("rbac.manage")) {
      return NextResponse.json({ error: "You cannot remove your own user-management permission" }, { status: 400 });
    }
    const json = extra.length || denied.length ? JSON.stringify({ extra, denied }) : null;
    await db.user.update({ where: { id: target.id }, data: { permissionsJson: json } });
    return NextResponse.json({ permissions: effectivePermissions(target.role, json), overrides: json ? { extra, denied } : {} });
  }

  if (action === "user.password") {
    const target = await findTarget(body.id);
    if (!target) return NextResponse.json({ error: "User not found in this school" }, { status: 404 });
    if (target === "protected") return NextResponse.json({ error: "Network accounts are managed at group level" }, { status: 403 });
    if (!body.password || String(body.password).length < 4) return NextResponse.json({ error: "Password must be at least 4 characters" }, { status: 400 });
    await db.user.update({ where: { id: target.id }, data: { password: String(body.password) } });
    return NextResponse.json({ ok: true });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

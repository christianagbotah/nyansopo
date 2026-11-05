import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import {
  ROLE_DEFAULTS, ROLE_LEVELS, effectivePermissions, roleBasePerms, parseRolePerms, landingFor,
  ALL_PERM_IDS, PERMISSIONS,
} from "@/lib/rbac";

// Roles that can be assigned from the school RBAC console (network roles excluded)
export const ASSIGNABLE_ROLES = [
  "SUPER_ADMIN", "SCHOOL_ADMIN", "ACCOUNTANT", "CASHIER", "BUS_CONDUCTOR",
  "TEACHER", "NON_TEACHING", "LIBRARIAN", "PARENT", "STUDENT",
];

const PROTECTED_ROLES = ["GROUP_OWNER", "ASSOCIATION_NATIONAL", "ASSOCIATION_REGIONAL", "ASSOCIATION_DISTRICT", "ASSOCIATION_ZONAL"];

// backoffice system roles whose permission set the school may tune
const TUNABLE_ROLES = ["SUPER_ADMIN", "SCHOOL_ADMIN", "ACCOUNTANT", "CASHIER", "BUS_CONDUCTOR", "TEACHER", "NON_TEACHING", "LIBRARIAN"];

const ROLE_LABELS: Record<string, string> = {
  SUPER_ADMIN: "Super Admin", SCHOOL_ADMIN: "Administrator", ACCOUNTANT: "Accountant", CASHIER: "Cashier",
  BUS_CONDUCTOR: "Bus Conductor", TEACHER: "Teacher", NON_TEACHING: "Non-Teaching", LIBRARIAN: "Librarian",
};

const slugifyRole = (label: string) =>
  label.trim().toUpperCase().replace(/[^A-Z0-9]+/g, "_").replace(/^_+|_+$/g, "").slice(0, 32) || "CUSTOM_ROLE";

// ── GET /api/school/[id]/ops/rbac — users, roles & permission state ──
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
  const configs = await db.roleConfig.findMany({ where: { schoolId } });
  const configByRole = new Map(configs.map((c) => [c.role, c]));

  // every assignable role + custom roles, with their effective base at this school
  const counts = new Map<string, number>();
  for (const u of users) counts.set(u.role, (counts.get(u.role) ?? 0) + 1);

  type RoleCardRow = {
    role: string; isCustom: boolean; system: boolean; label: string; level: number | null;
    configured: boolean; active: boolean; userCount: number; permCount: number;
    basePerms: string[]; landingModule: string; note: string | null; updatedByName: string | null;
  };
  const roleCards: RoleCardRow[] = TUNABLE_ROLES.map((role): RoleCardRow => {
    const cfg = configByRole.get(role);
    const active = cfg?.active ? parseRolePerms(cfg.permissions) : null;
    const base = roleBasePerms(role, active);
    return {
      role, isCustom: false, system: true,
      label: cfg?.label ?? ROLE_LABELS[role] ?? role,
      level: cfg?.level ?? ROLE_LEVELS[role] ?? null,
      configured: !!cfg?.active,
      active: cfg?.active ?? false,
      userCount: counts.get(role) ?? 0,
      permCount: base.length,
      basePerms: base,
      landingModule: landingFor(role, cfg?.landingModule ?? null),
      note: cfg?.note ?? null,
      updatedByName: cfg?.updatedByName ?? null,
    };
  });
  for (const c of configs.filter((x) => x.isCustom)) {
    const base = c.active ? (parseRolePerms(c.permissions) ?? []) : [];
    roleCards.push({
      role: c.role, isCustom: true, system: false,
      label: c.label,
      level: c.level ?? null,
      configured: c.active,
      active: c.active,
      userCount: counts.get(c.role) ?? 0,
      permCount: base.length,
      basePerms: base,
      landingModule: landingFor(c.role, c.landingModule ?? null),
      note: c.note ?? null,
      updatedByName: c.updatedByName ?? null,
    });
  }
  roleCards.sort((a, b) => (a.level ?? 99) - (b.level ?? 99) || a.label.localeCompare(b.label));

  return NextResponse.json({
    users: users.map((u) => {
      const cfg = configByRole.get(u.role);
      const activePerms = cfg?.active ? parseRolePerms(cfg.permissions) : null;
      return {
        id: u.id, email: u.email, fullName: u.fullName, phone: u.phone, role: u.role, title: u.title,
        status: u.status, lastLoginAt: u.lastLoginAt,
        permissions: effectivePermissions(u.role, u.permissionsJson, activePerms),
        roleBase: roleBasePerms(u.role, activePerms),
        roleLabel: cfg?.isCustom ? cfg.label : (cfg?.label && cfg.active ? cfg.label : null),
        overrides: u.permissionsJson ? JSON.parse(u.permissionsJson) : {},
        staffId: u.staffId, studentId: u.studentId,
        linkedStudentIds: u.linkedStudentIds ? JSON.parse(u.linkedStudentIds) : [],
        staffName: u.staff?.fullName ?? null,
        createdAt: u.createdAt,
      };
    }),
    staff,
    roles: roleCards,
    assignableRoles: ASSIGNABLE_ROLES,
    protectedRoles: PROTECTED_ROLES,
  });
}

// ── POST /api/school/[id]/ops/rbac — accounts, per-user perms, role configs ──
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

  const customRolesAt = async () => {
    const rows = await db.roleConfig.findMany({ where: { schoolId, isCustom: true }, select: { role: true } });
    return rows.map((r) => r.role);
  };
  const assignableNow = async () => [...ASSIGNABLE_ROLES, ...(await customRolesAt())];

  if (action === "user.create") {
    const { email, password, fullName, phone, role, title, staffId, studentId, linkedStudentIds } = body;
    if (!email || !password || !fullName || !role) return NextResponse.json({ error: "Email, password, full name and role are required" }, { status: 400 });
    if (!(await assignableNow()).includes(role)) return NextResponse.json({ error: "This role is managed at network level and cannot be assigned here" }, { status: 400 });
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
    if (body.role && !(await assignableNow()).includes(body.role)) {
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
    const cfg = await db.roleConfig.findFirst({ where: { schoolId, role: target.role } });
    const activePerms = cfg?.active ? parseRolePerms(cfg.permissions) : null;
    return NextResponse.json({ permissions: effectivePermissions(target.role, json, activePerms), overrides: json ? { extra, denied } : {} });
  }

  if (action === "user.password") {
    const target = await findTarget(body.id);
    if (!target) return NextResponse.json({ error: "User not found in this school" }, { status: 404 });
    if (target === "protected") return NextResponse.json({ error: "Network accounts are managed at group level" }, { status: 403 });
    if (!body.password || String(body.password).length < 4) return NextResponse.json({ error: "Password must be at least 4 characters" }, { status: 400 });
    await db.user.update({ where: { id: target.id }, data: { password: String(body.password) } });
    return NextResponse.json({ ok: true });
  }

  /* ── role configuration (wave 10) ─────────────────────────────
     role.save   — upsert a role's permission set at this school.
                   System roles: tune the ladder role (isCustom stays false).
                   Custom roles: mint new roles (isCustom true) or edit them.
     role.delete — custom roles only; users must be reassigned first.
  ──────────────────────────────────────────────────────────────── */
  if (action === "role.save") {
    const isCustom = !!body.isCustom;
    let role: string = String(body.role ?? "");
    if (isCustom) {
      // mint or edit a custom role — slug comes from the key or is derived from the label
      if (!role) role = slugifyRole(String(body.label ?? ""));
      if (!/^[A-Z][A-Z0-9_]{2,32}$/.test(role)) return NextResponse.json({ error: "Custom role key must be 3–32 chars (A–Z, 0–9, _)" }, { status: 400 });
      if (ASSIGNABLE_ROLES.includes(role) || PROTECTED_ROLES.includes(role) || role === "SYSTEM_ADMIN") {
        return NextResponse.json({ error: "That role key is reserved" }, { status: 400 });
      }
    } else if (!TUNABLE_ROLES.includes(role)) {
      return NextResponse.json({ error: "That role cannot be configured here" }, { status: 400 });
    }

    const label = String(body.label ?? "").trim();
    if (!label) return NextResponse.json({ error: "Role label is required" }, { status: 400 });
    const level = body.level === null || body.level === undefined ? null : Math.max(0, Math.min(5, Number(body.level) || 0));
    const landingModule = typeof body.landingModule === "string" && body.landingModule ? body.landingModule : null;

    const active = body.active === undefined ? true : !!body.active;
    let perms: string[] = [];
    if (active) {
      perms = Array.isArray(body.permissions) ? body.permissions.filter((p: string) => ALL_PERM_IDS.includes(p)) : [];
      // guard: saving your own role without rbac.manage would lock you out
      if (role === me.role && !perms.includes("rbac.manage")) {
        return NextResponse.json({ error: "This is your own role — keep “Manage users, roles & permissions” ticked, or hand the console to another admin first." }, { status: 400 });
      }
    }

    const existing = await db.roleConfig.findUnique({ where: { schoolId_role: { schoolId, role } } });
    if (existing && existing.isCustom !== isCustom) {
      return NextResponse.json({ error: "Role key already used — pick another label" }, { status: 400 });
    }

    const saved = await db.roleConfig.upsert({
      where: { schoolId_role: { schoolId, role } },
      update: { label, level, landingModule, note: body.note ?? null, updatedByName: me.fullName, ...(active ? { active: true, permissions: JSON.stringify(perms) } : { active: false }) },
      create: {
        schoolId, role, label, level, landingModule,
        permissions: JSON.stringify(active ? perms : (existing ? JSON.parse(existing.permissions) : [])),
        isCustom, active, note: body.note ?? null, updatedByName: me.fullName,
      },
    });
    return NextResponse.json({ role: { role: saved.role, label: saved.label, active: saved.active, isCustom: saved.isCustom, permCount: active ? perms.length : undefined } });
  }

  if (action === "role.delete") {
    const role: string = String(body.role ?? "");
    const cfg = await db.roleConfig.findUnique({ where: { schoolId_role: { schoolId, role } } });
    if (!cfg || !cfg.isCustom) return NextResponse.json({ error: "Only custom roles can be deleted" }, { status: 400 });
    const holders = await db.user.count({ where: { schoolId, role } });
    if (holders > 0) {
      const reassignTo = String(body.reassignTo ?? "");
      const assignable = await assignableNow();
      if (!reassignTo || !assignable.includes(reassignTo) || reassignTo === role) {
        return NextResponse.json({ error: `${holders} user(s) still hold this role — pass reassignTo (an assignable role) to move them first`, holders }, { status: 400 });
      }
      await db.user.updateMany({ where: { schoolId, role }, data: { role: reassignTo } });
    }
    await db.roleConfig.delete({ where: { id: cfg.id } });
    return NextResponse.json({ ok: true, reassigned: holders > 0 ? holders : 0 });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { effectivePermissions, parseRolePerms, landingFor, ROLE_LEVELS } from "@/lib/rbac";

export async function POST(req: NextRequest) {
  try {
    const { email, password } = await req.json();
    const user = await db.user.findUnique({ where: { email: (email || "").trim().toLowerCase() }, include: { associationNode: true, school: true, schoolGroup: { include: { schools: true } } } });
    if (!user || user.password !== password) {
      return NextResponse.json({ error: "Invalid email or password" }, { status: 401 });
    }
    if (user.status === "SUSPENDED") {
      return NextResponse.json({ error: "This account is suspended. Contact your administrator." }, { status: 403 });
    }
    await db.user.update({ where: { id: user.id }, data: { lastLoginAt: new Date() } });

    // role configuration for this school (wave 10): tuned system roles + custom roles
    const cfg = user.schoolId ? await db.roleConfig.findFirst({ where: { schoolId: user.schoolId, role: user.role, active: true } }) : null;
    const configPerms = cfg ? parseRolePerms(cfg.permissions) : null;

    return NextResponse.json({
      user: {
        id: user.id, email: user.email, fullName: user.fullName, phone: user.phone,
        role: user.role, title: user.title,
        associationNodeId: user.associationNodeId,
        schoolGroupId: user.schoolGroupId, schoolId: user.schoolId,
        associationNodeName: user.associationNode?.name ?? null,
        schoolName: user.school?.name ?? null,
        groupName: user.schoolGroup?.name ?? null,
        permissions: effectivePermissions(user.role, user.permissionsJson, configPerms),
        roleLabel: cfg?.label ?? null,
        roleLevel: cfg?.level ?? ROLE_LEVELS[user.role] ?? null,
        roleBackoffice: cfg?.isCustom ? true : undefined,
        landingModule: landingFor(user.role, cfg?.landingModule ?? null),
        staffId: user.staffId ?? null,
        studentId: user.studentId ?? null,
      },
    });
  } catch {
    return NextResponse.json({ error: "Bad request" }, { status: 400 });
  }
}

import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";

export async function POST(req: NextRequest) {
  try {
    const { email, password } = await req.json();
    const user = await db.user.findUnique({ where: { email: (email || "").trim().toLowerCase() }, include: { associationNode: true, school: true, schoolGroup: { include: { schools: true } } } });
    if (!user || user.password !== password) {
      return NextResponse.json({ error: "Invalid email or password" }, { status: 401 });
    }
    return NextResponse.json({
      user: {
        id: user.id, email: user.email, fullName: user.fullName, phone: user.phone,
        role: user.role, title: user.title,
        associationNodeId: user.associationNodeId,
        schoolGroupId: user.schoolGroupId, schoolId: user.schoolId,
        associationNodeName: user.associationNode?.name ?? null,
        schoolName: user.school?.name ?? null,
        groupName: user.schoolGroup?.name ?? null,
      },
    });
  } catch {
    return NextResponse.json({ error: "Bad request" }, { status: 400 });
  }
}

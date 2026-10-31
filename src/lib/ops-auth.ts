import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser, canOperateSchool } from "@/lib/session";

export type OpsAuth =
  | { ok: true; user: NonNullable<Awaited<ReturnType<typeof getUser>>>; schoolId: string }
  | { ok: false; res: NextResponse };

/** Auth for ops module routes: association officers may VIEW (GET), school staff may OPERATE (GET+POST). */
export async function opsAuth(req: NextRequest, schoolId: string, mode: "GET" | "POST"): Promise<OpsAuth> {
  const user = await getUser(req);
  if (!user) return { ok: false, res: NextResponse.json({ error: "Not authenticated" }, { status: 401 }) };
  const school = await db.school.findUnique({ where: { id: schoolId } });
  if (!school) return { ok: false, res: NextResponse.json({ error: "School not found" }, { status: 404 }) };
  const canOperate = canOperateSchool(user, schoolId);
  const canView = canOperate || user.nodeIds.includes(school.associationNodeId);
  if (mode === "POST" && !canOperate) return { ok: false, res: NextResponse.json({ error: "Forbidden — you cannot operate this school" }, { status: 403 }) };
  if (!canView) return { ok: false, res: NextResponse.json({ error: "Forbidden" }, { status: 403 }) };
  return { ok: true, user, schoolId };
}

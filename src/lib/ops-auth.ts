import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser, canOperateSchool } from "@/lib/session";
import { canAny } from "@/lib/rbac";

export type OpsAuth =
  | { ok: true; user: NonNullable<Awaited<ReturnType<typeof getUser>>>; schoolId: string }
  | { ok: false; res: NextResponse };

export type OpsAuthOpts = {
  /** any-of permission list. POST: required (when given) on top of school-operate rights.
   *  GET: restricts school-side viewers; association officers keep their read-only
   *  oversight unless `strict` is set. */
  perms?: string[];
  /** when true, GET also requires the permission (association view is not enough). */
  strict?: boolean;
};

/** Auth for ops module routes: association officers may VIEW (GET), school staff may OPERATE (GET+POST),
 *  gated by RBAC permissions (iEduPro admin levels & custom grants). */
export async function opsAuth(req: NextRequest, schoolId: string, mode: "GET" | "POST", opts?: OpsAuthOpts): Promise<OpsAuth> {
  const user = await getUser(req);
  if (!user) return { ok: false, res: NextResponse.json({ error: "Not authenticated" }, { status: 401 }) };
  const school = await db.school.findUnique({ where: { id: schoolId } });
  if (!school) return { ok: false, res: NextResponse.json({ error: "School not found" }, { status: 404 }) };
  const canOperate = canOperateSchool(user, schoolId);
  const nodeView = user.nodeIds.includes(school.associationNodeId);
  const hasPerms = !opts?.perms?.length || canAny(user.permissions, opts.perms);

  if (mode === "POST") {
    if (!canOperate) return { ok: false, res: NextResponse.json({ error: "Forbidden — you cannot operate this school" }, { status: 403 }) };
    if (!hasPerms) return { ok: false, res: NextResponse.json({ error: "Forbidden — missing permission for this action" }, { status: 403 }) };
    return { ok: true, user, schoolId };
  }

  const viewOk = opts?.strict
    ? canOperate && hasPerms
    : (canOperate && hasPerms) || nodeView;
  if (!viewOk) return { ok: false, res: NextResponse.json({ error: "Forbidden" }, { status: 403 }) };
  return { ok: true, user, schoolId };
}

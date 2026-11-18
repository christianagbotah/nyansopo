import { NextRequest } from "next/server";
import { db } from "@/lib/db";

/* ── Wave 31 · Audit trail writer — the compliance book ────────────────
 * One line per consequential act: who, what entity, before/after JSON.
 * Fire-and-forget — an audit write must never break the business action,
 * so failures are swallowed (logged to stderr only).
 *
 * iEduPro parity: the legacy system wrote audit_trail / audit_logs rows
 * from every money and academic action and rendered them in
 * admin/audit_log_viewer.php with Before Data / After Data panes.
 * ──────────────────────────────────────────────────────────────────── */

export type AuditAction =
  | "CREATE" | "UPDATE" | "DELETE" | "ISSUE" | "REVOKE" | "REINSTATE"
  | "PUBLISH" | "UNPUBLISH" | "APPROVE" | "REJECT" | "LOCK" | "UNLOCK"
  | "VOID" | "EXPORT" | "ADJUST" | "TRANSFER" | "LOGIN";

export type AuditActor = {
  id?: string | null;
  fullName: string;
  role?: string | null;
};

export type AuditInput = {
  schoolId: string;
  actor: AuditActor;
  action: AuditAction;
  entityType: string;
  entityId?: string | null;
  entityLabel?: string | null;
  summary: string;
  before?: Record<string, unknown> | null;
  after?: Record<string, unknown> | null;
  req?: NextRequest | null;
};

/** Client IP behind the sandbox gateway: x-forwarded-for first hop. */
function clientIp(req: NextRequest | null | undefined): string | null {
  if (!req) return null;
  const fwd = req.headers.get("x-forwarded-for");
  if (fwd) return fwd.split(",")[0].trim();
  return req.headers.get("x-real-ip") ?? null;
}

function briefUserAgent(req: NextRequest | null | undefined): string | null {
  if (!req) return null;
  const ua = req.headers.get("user-agent");
  if (!ua) return null;
  // keep it short — the first 120 chars identify the browser well enough
  return ua.slice(0, 120);
}

/** Fire-and-forget audit write. Never throws. */
export async function audit(input: AuditInput): Promise<void> {
  try {
    await db.auditLog.create({
      data: {
        schoolId: input.schoolId,
        actorId: input.actor.id ?? null,
        actorName: input.actor.fullName,
        actorRole: input.actor.role ?? null,
        action: input.action,
        entityType: input.entityType,
        entityId: input.entityId ?? null,
        entityLabel: input.entityLabel ?? null,
        summary: input.summary.slice(0, 500),
        beforeJson: (input.before ?? undefined) as never,
        afterJson: (input.after ?? undefined) as never,
        ip: clientIp(input.req),
        userAgent: briefUserAgent(input.req),
      },
    });
  } catch (e) {
    console.error("[audit] write failed", e);
  }
}

/** Shape any prisma row for the JSON diff panes: dates → ISO strings. */
export function auditSnapshot(row: Record<string, unknown>): Record<string, unknown> {
  const out: Record<string, unknown> = {};
  for (const [k, v] of Object.entries(row)) {
    out[k] = v instanceof Date ? v.toISOString() : v;
  }
  return out;
}

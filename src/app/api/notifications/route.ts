import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getUser, canOperateSchool } from "@/lib/session";
import { canAny } from "@/lib/rbac";

export const dynamic = "force-dynamic";

// ── GET /api/notifications?schoolId=… — role-scoped notification centre ──
// Derived at request time from the live tables (no read-state on the server;
// the client keeps a per-user "last read" timestamp in localStorage).
// Each item is permission-gated so teachers, bursars and admins only ever see
// what their role can act on — clicking deep-links into the owning module.
export async function GET(req: NextRequest) {
  const user = await getUser(req);
  if (!user) return NextResponse.json({ error: "Not authenticated" }, { status: 401 });
  const schoolId = req.nextUrl.searchParams.get("schoolId") ?? "";
  if (!schoolId) return NextResponse.json({ error: "schoolId required" }, { status: 400 });
  if (!canOperateSchool(user, schoolId)) return NextResponse.json({ error: "Forbidden" }, { status: 403 });

  const perms = user.permissions; // effective RBAC permissions (computed in session)
  const items: any[] = [];
  const can = (p: string) => canAny(perms, [p]);

  // 1 — receipt / invoice modification queue (level-1 adjudication)
  if (can("approvals.manage")) {
    const [modLatest, modCount] = await Promise.all([
      db.modificationRequest.findMany({ where: { schoolId, status: "PENDING" }, orderBy: { createdAt: "desc" }, take: 1 }),
      db.modificationRequest.count({ where: { schoolId, status: "PENDING" } }),
    ]);
    if (modLatest.length) {
      items.push({
        id: "mod-requests", kind: "approvals", severity: "warning", count: modCount,
        title: `${modCount} receipt change${modCount > 1 ? "s" : ""} awaiting approval`,
        detail: `e.g. ${modLatest[0].requestType} on ${modLatest[0].recordCode} — ${modLatest[0].requestedByName}`,
        module: "fees", tab: "approvals", at: modLatest[0].createdAt,
      });
    }
  }

  // 1b — incoming cross-school record requests. Gated by admissions.manage —
  //      the same permission that allows responding in the Admissions desk —
  //      so school admins (and above) see them; approvals.manage alone is
  //      reserved for the level-1 receipt-modification queue.
  if (can("admissions.manage")) {
    const recLatest = await db.recordRequest.findMany({
      where: { targetSchoolId: schoolId, status: "PENDING" }, orderBy: { requestedAt: "desc" }, take: 1,
      include: { student: true },
    });
    if (recLatest.length) {
      const cnt = await db.recordRequest.count({ where: { targetSchoolId: schoolId, status: "PENDING" } });
      items.push({
        id: "record-requests", kind: "records", severity: "info", count: cnt,
        title: `${cnt} learner record request${cnt > 1 ? "s" : ""} from other schools`,
        detail: `${recLatest[0].student.firstName} ${recLatest[0].student.lastName} · ${recLatest[0].purpose.toLowerCase()} · by ${recLatest[0].requestedByName}`,
        module: "admissions", tab: "requests", at: recLatest[0].requestedAt,
      });
    }
  }

  // 2 — discount applications pending adjudication
  if (can("discounts.manage")) {
    const latest = await db.discountApplication.findMany({
      where: { schoolId, status: "PENDING" }, orderBy: { createdAt: "desc" }, take: 1,
    });
    if (latest.length) {
      const cnt = await db.discountApplication.count({ where: { schoolId, status: "PENDING" } });
      items.push({
        id: "discount-apps", kind: "discounts", severity: "info", count: cnt,
        title: `${cnt} discount application${cnt > 1 ? "s" : ""} to review`,
        detail: latest[0].reason ? `“${latest[0].reason}” — ${latest[0].appliedByName}` : latest[0].appliedByName,
        module: "fees", tab: "discounts", at: latest[0].createdAt,
      });
    }
  }

  // 3 — lesson notes workflow (reviewer queue / my returned notes)
  if (can("curriculum.review")) {
    const latest = await db.lessonNote.findMany({
      where: { status: "PENDING", term: { academicYear: { schoolId } } },
      orderBy: { updatedAt: "desc" }, take: 1, include: { staff: true },
    });
    if (latest.length) {
      const cnt = await db.lessonNote.count({ where: { status: "PENDING", term: { academicYear: { schoolId } } } });
      items.push({
        id: "notes-review", kind: "notes", severity: "info", count: cnt,
        title: `${cnt} lesson note${cnt > 1 ? "s" : ""} awaiting endorsement`,
        detail: `e.g. ${latest[0].title} — ${latest[0].staff?.fullName ?? "staff"}`,
        module: "curriculum", tab: "review", at: latest[0].updatedAt,
      });
    }
  } else if (can("curriculum.manage") && user.staffId) {
    const returned = await db.lessonNote.findMany({
      where: { staffId: user.staffId, status: "REVISION_REQUIRED", term: { academicYear: { schoolId } } },
      orderBy: { updatedAt: "desc" }, take: 1,
    });
    if (returned.length) {
      const cnt = await db.lessonNote.count({ where: { staffId: user.staffId, status: "REVISION_REQUIRED", term: { academicYear: { schoolId } } } });
      items.push({
        id: "notes-returned", kind: "notes", severity: "warning", count: cnt,
        title: `${cnt} lesson note${cnt > 1 ? "s" : ""} returned for revision`,
        detail: `e.g. “${returned[0].title}” — reviewer feedback waiting`,
        module: "curriculum", tab: "notes", at: returned[0].updatedAt,
      });
    }
  }

  // 4 — low / out-of-stock inventory
  if (can("inventory.view")) {
    const all = await db.inventoryItem.findMany({ where: { schoolId } });
    const lowItems = all.filter((i) => i.stockQty <= i.reorderLevel);
    if (lowItems.length) {
      const out = lowItems.filter((i) => i.stockQty <= 0);
      items.push({
        id: "low-stock", kind: "stock", severity: out.length ? "danger" : "warning", count: lowItems.length,
        title: out.length ? `${out.length} item${out.length > 1 ? "s" : ""} out of stock` : `${lowItems.length} item${lowItems.length > 1 ? "s" : ""} at reorder level`,
        detail: `e.g. ${lowItems[0].name} — ${lowItems[0].stockQty} ${lowItems[0].unit} left`,
        module: "inventory", tab: "low", at: new Date(),
      });
    }
  }

  // 5 — overdue fee balances (chase-list nudge)
  if (can("fees.view")) {
    const overdue = await db.invoice.aggregate({
      where: { schoolId, status: "OVERDUE" }, _count: { id: true },
      _sum: { totalCents: true, discountCents: true, paidCents: true },
    });
    if (overdue._count.id) {
      const outstanding = (overdue._sum.totalCents ?? 0) - (overdue._sum.discountCents ?? 0) - (overdue._sum.paidCents ?? 0);
      items.push({
        id: "overdue", kind: "arrears", severity: "warning", count: overdue._count.id,
        title: `${overdue._count.id} invoice${overdue._count.id > 1 ? "s" : ""} overdue`,
        detail: `₵${(outstanding / 100).toLocaleString("en-GH", { maximumFractionDigits: 2 })} outstanding — send reminders`,
        module: "fees", tab: "arrears", at: new Date(),
      });
    }
  }

  // 6 — unread internal messages (staff inbox, wave 12 messaging)
  if (can("messages.use")) {
    const inMyThreads = { schoolId, OR: [{ senderId: user.id }, { recipientId: user.id }] };
    const unreadMsgs = await db.message.findMany({
      where: { readAt: null, senderId: { not: user.id }, thread: inMyThreads },
      orderBy: { createdAt: "desc" }, take: 1,
      include: { thread: { include: { sender: true, recipient: true } } },
    });
    if (unreadMsgs.length) {
      const cnt = await db.message.count({
        where: { readAt: null, senderId: { not: user.id }, thread: inMyThreads },
      });
      const latest = unreadMsgs[0];
      const counterpart = latest.thread.senderId === user.id ? latest.thread.recipient : latest.thread.sender;
      items.push({
        id: "messages-unread", kind: "messages", severity: "info", count: cnt,
        title: `${cnt} unread message${cnt > 1 ? "s" : ""}`,
        detail: `from ${counterpart.fullName} — ${latest.thread.subject}`,
        module: "messages", at: latest.createdAt,
      });
    }
  }

  // most recent first; severities weighted so danger leads ties
  const rank: Record<string, number> = { danger: 0, warning: 1, info: 2 };
  items.sort((a, b) => (rank[a.severity] - rank[b.severity]) || ((b.at?.getTime?.() ?? 0) - (a.at?.getTime?.() ?? 0)));
  return NextResponse.json({ items });
}

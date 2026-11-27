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

  // 3b — homework (wave 65): my open assignments due within 2 days, or overdue
  //      with unmarked work — the daily "what must I chase tonight" nudge
  if (can("homework.view") && user.staffId) {
    const now = new Date();
    const soon = new Date(now.getTime() + 2 * 86400000);
    const myOpen = await db.homework.findMany({
      where: { schoolId, staffId: user.staffId, status: "OPEN", dueDate: { lte: soon } },
      orderBy: { dueDate: "asc" }, take: 1,
      include: { _count: { select: { submissions: { where: { status: { in: ["PENDING"] } } } } } },
    });
    if (myOpen.length) {
      const hw = myOpen[0];
      const overdueOrSoon = hw.dueDate < now;
      items.push({
        id: "homework-due", kind: "homework", severity: overdueOrSoon ? "warning" : "info",
        count: hw._count.submissions,
        title: `${hw._count.submissions} learner${hw._count.submissions === 1 ? "" : "s"} yet to hand in "${hw.title}"`,
        detail: `${overdueOrSoon ? "Overdue" : "Due soon"} · ${new Date(hw.dueDate).toLocaleDateString("en-GB", { day: "numeric", month: "short" })} — remind parents or mark what has come in`,
        module: "homework", at: hw.dueDate,
      });
    }
  }

  // 3c — installment plans coming due (wave 69 Payment Plans): installments
  //      due within 3 days (or overdue) on active plans — the bursar's
  //      "who do I chase today" nudge, deep-linked into the plans tab.
  if (can("fees.view")) {
    const today = new Date(`${new Date().toISOString().slice(0, 10)}T00:00:00.000Z`);
    const horizon = new Date(today.getTime() + 3 * 86400000);
    const dueInsts = await db.paymentPlanInstallment.findMany({
      where: { status: "OPEN", dueDate: { lte: horizon }, plan: { schoolId, status: "ACTIVE" } },
      include: { plan: { include: { invoice: { select: { invoiceNo: true, student: { select: { firstName: true, lastName: true } } } } } } },
      orderBy: { dueDate: "asc" },
      take: 1,
    });
    if (dueInsts.length) {
      const first = dueInsts[0];
      const overdue = first.dueDate < today;
      items.push({
        id: "payment-plans-due", kind: "fees", severity: overdue ? "warning" : "info", count: 1,
        title: `${first.plan.invoice.student.firstName}'s installment ${overdue ? "is overdue" : "is due within 3 days"}`,
        detail: `${first.plan.invoice.invoiceNo} — installment ${first.sequence} · ${first.label ?? "part payment"}. Open Payment plans to remind or settle.`,
        module: "fees", tab: "plans", at: first.dueDate,
      });
    }
  }

  // 3d — Guidance & Counselling (wave 71): open HIGH/CRITICAL cases on the
  //      viewer's board (confidentiality-scoped — a plain teacher only counts
  //      the cases assigned to or opened by them) + my overdue follow-ups.
  if (can("counsel.view")) {
    const canManageCounsel = can("counsel.manage");
    const staffId = user.staffId ?? null;
    const scope = canManageCounsel || !staffId
      ? { schoolId }
      : { schoolId, OR: [{ confidential: false }, { assignedToId: staffId }, { openedByStaffId: staffId }] };
    const critical = await db.counselCase.findFirst({
      where: { ...scope, severity: { in: ["HIGH", "CRITICAL"] }, status: { in: ["OPEN", "IN_PROGRESS", "MONITORING"] } },
      orderBy: { updatedAt: "desc" },
      select: { caseNo: true, title: true, severity: true, updatedAt: true },
    });
    if (critical) {
      const cnt = await db.counselCase.count({
        where: { ...scope, severity: { in: ["HIGH", "CRITICAL"] }, status: { in: ["OPEN", "IN_PROGRESS", "MONITORING"] } },
      });
      items.push({
        id: "counsel-critical", kind: "counsel", severity: critical.severity === "CRITICAL" ? "danger" : "warning", count: cnt,
        title: `${cnt} G&C case${cnt > 1 ? "s" : ""} at ${critical.severity.toLowerCase()} severity`,
        detail: `e.g. ${critical.caseNo} — ${critical.title}. Open the case board.`,
        module: "counsel", at: critical.updatedAt,
      });
    }
    if (staffId) {
      const overdueFu = await db.counselFollowUp.findFirst({
        where: { doneAt: null, dueAt: { lt: new Date() }, case: { schoolId, assignedToId: staffId } },
        orderBy: { dueAt: "asc" },
        select: { dueAt: true, note: true, case: { select: { caseNo: true } } },
      });
      if (overdueFu) {
        const cnt = await db.counselFollowUp.count({
          where: { doneAt: null, dueAt: { lt: new Date() }, case: { schoolId, assignedToId: staffId } },
        });
        items.push({
          id: "counsel-followup", kind: "counsel", severity: "warning", count: cnt,
          title: `${cnt} counselling follow-up${cnt > 1 ? "s" : ""} overdue`,
          detail: `${overdueFu.case.caseNo} — "${overdueFu.note}". You promised to check on this.`,
          module: "counsel", tab: "followups", at: overdueFu.dueAt,
        });
      }
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

  // 7 — NTC licence renewal alerts (wave 58 — expiring / expired licences
  //     among active teaching staff, for whoever keeps the register)
  if (can("staffcpd.view")) {
    const DAY = 864e5;
    const teachers = await db.staff.findMany({
      where: { schoolId, active: true, category: "TEACHING", ntcExpiry: { not: null } },
      select: { fullName: true, ntcExpiry: true },
    });
    const now = Date.now();
    const expired = teachers.filter((t) => new Date(t.ntcExpiry!).getTime() < now);
    const critical = teachers.filter((t) => {
      const days = Math.floor((new Date(t.ntcExpiry!).getTime() - now) / DAY);
      return days >= 0 && days <= 30;
    });
    const expiring = teachers.filter((t) => {
      const days = Math.floor((new Date(t.ntcExpiry!).getTime() - now) / DAY);
      return days > 30 && days <= 90;
    });
    if (expired.length || critical.length || expiring.length) {
      items.push({
        id: "ntc-licences", kind: "ntc", severity: expired.length || critical.length ? "danger" : "warning",
        count: expired.length + critical.length + expiring.length,
        title: expired.length
          ? `${expired.length} NTC licence${expired.length > 1 ? "s" : ""} expired`
          : critical.length
            ? `${critical.length} NTC licence${critical.length > 1 ? "s" : ""} expiring within 30 days`
            : `${expiring.length} NTC licence${expiring.length > 1 ? "s" : ""} expiring this term`,
        detail: `e.g. ${expired[0]?.fullName ?? critical[0]?.fullName ?? expiring[0]?.fullName} — renewals needed`,
        module: "staffcpd", tab: "licences", at: new Date(),
      });
    }
  }

  // 8 — visitor waiting to see YOU (wave 59 front-desk alerts). Derived from
  //     the ON_SITE visitor row linked to this user's staff record — fires the
  //     moment the desk books the visitor in and clears itself at check-out
  //     (or after 12h, so yesterday's stragglers never haunt the bell).
  if (user.staffId) {
    const cutoff = new Date(Date.now() - 12 * 3600_000);
    const waiting = await db.visitor.findMany({
      where: { schoolId, status: "ON_SITE", personToSeeStaffId: user.staffId, timeIn: { gte: cutoff } },
      orderBy: { timeIn: "desc" },
      take: 1,
    });
    if (waiting.length) {
      const cnt = await db.visitor.count({
        where: { schoolId, status: "ON_SITE", personToSeeStaffId: user.staffId, timeIn: { gte: cutoff } },
      });
      const v = waiting[0];
      const since = new Date(v.timeIn).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" });
      items.push({
        id: "visitor-waiting", kind: "visitors", severity: "info", count: cnt,
        title: cnt > 1 ? `${cnt} visitors are waiting to see you` : "A visitor is waiting to see you",
        detail: `${v.name}${v.organisation ? ` (${v.organisation})` : ""} · ${v.purpose}${v.passNo ? ` · pass ${v.passNo}` : ""} — at the desk since ${since}`,
        module: "frontoffice", tab: "visitors", at: v.timeIn,
      });
    }
  }

  // 8b — you are COVERING a lesson today (wave 65 Timetable Intelligence).
  //      Derived from the slot-accurate substitution rows the cover planner
  //      creates — the covering teacher sees exactly which lesson, class and
  //      period without anyone having to shout across the compound.
  if (user.staffId) {
    const today = new Date(`${new Date().toISOString().slice(0, 10)}T00:00:00.000Z`);
    const coversToday = await db.substitutionLog.findMany({
      where: { schoolId, substituteStaffId: user.staffId, date: today, slotId: { not: null } },
      include: { absentStaff: { select: { fullName: true } }, slot: { select: { subjectName: true, startMinute: true } } },
      orderBy: { createdAt: "asc" },
    });
    if (coversToday.length) {
      const first = coversToday[0];
      const at = first.slot ? new Date(first.slot.startMinute * 60_000).toISOString().slice(11, 16) : "";
      items.push({
        id: "cover-duty", kind: "timetable", severity: "info", count: coversToday.length,
        title: coversToday.length > 1 ? `You are covering ${coversToday.length} lessons today` : `You are covering ${first.slot?.subjectName ?? "a lesson"} today`,
        detail: coversToday.length > 1
          ? `First up ${first.slot?.subjectName ?? ""} ${at}${first.absentStaff.fullName ? ` · for ${first.absentStaff.fullName}` : ""} — open the cover planner for the full list`
          : `${first.classRef ?? ""}${at ? ` at ${at}` : ""}${first.absentStaff.fullName ? ` · for ${first.absentStaff.fullName}` : ""}`,
        module: "timetable", tab: "cover", at: first.createdAt,
      });
    }
  }

  // 8c — appointment requests awaiting YOUR response (wave 72 front-desk
  //      bookings). Derived from the SCHEDULED rows linked to this user's
  //      staff record — fires the moment the desk books a visitor in to see
  //      them; deep-links Home, where the Accept / Decline / Reschedule card
  //      lives (no front-office permission needed to answer your own door).
  if (user.staffId) {
    const pending = await db.appointment.findMany({
      where: { schoolId, staffId: user.staffId, status: "SCHEDULED", scheduledAt: { gte: new Date(Date.now() - 30 * 60_000) } },
      orderBy: { scheduledAt: "asc" },
      take: 1,
    });
    if (pending.length) {
      const cnt = await db.appointment.count({
        where: { schoolId, staffId: user.staffId, status: "SCHEDULED", scheduledAt: { gte: new Date(Date.now() - 30 * 60_000) } },
      });
      const ap = pending[0];
      items.push({
        id: "appt-requests", kind: "appointments", severity: "info", count: cnt,
        title: cnt > 1 ? `${cnt} visitors request meetings with you` : "A visitor requests a meeting with you",
        detail: `${ap.withName}${ap.visitorOrg ? ` (${ap.visitorOrg})` : ""} — “${ap.title}” · ${new Date(ap.scheduledAt).toLocaleString("en-GB", { weekday: "short", day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" })}${ap.location ? ` · ${ap.location}` : ""}. Accept, decline or reschedule from your Home.`,
        module: "dashboard", at: ap.createdAt,
      });
    }
  }

  // 8d — your meeting starts within 2 minutes (wave 72 T-2 alarm). The
  //      in-app leg of the desk chime + the staff SMS reminder — the visited
  //      staff member gets their "they are on the way" nudge.
  if (user.staffId) {
    const now = Date.now();
    const soon = await db.appointment.findFirst({
      where: {
        schoolId, staffId: user.staffId, status: { in: ["SCHEDULED", "CONFIRMED", "RESCHEDULED"] },
        scheduledAt: { gte: new Date(now - 60_000), lte: new Date(now + 2 * 60_000) },
      },
      orderBy: { scheduledAt: "asc" },
    });
    if (soon) {
      items.push({
        id: "appt-soon", kind: "appointments", severity: "warning", count: 1,
        title: `Your ${new Date(soon.scheduledAt).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" })} meeting starts in 2 minutes`,
        detail: `${soon.withName} — “${soon.title}”${soon.location ? ` · ${soon.location}` : ""}. The front desk is showing the visitor in.`,
        module: "dashboard", at: soon.scheduledAt,
      });
    }
  }

  // 8e — front-desk feed: staff responses landed (wave 72). Whoever runs the
  //      desk sees the accepted / declined / rescheduled answers as they
  //      arrive, until relayed to the visitor; your own responses are
  //      excluded so answering never pings you.
  if (can("frontoffice.view")) {
    const since = new Date(Date.now() - 24 * 3600_000);
    const whereFeed = { schoolId, status: { in: ["CONFIRMED", "DECLINED", "RESCHEDULED"] }, respondedAt: { gte: since }, relayedAt: null, respondedByUserId: { not: user.id } };
    const latest = await db.appointment.findMany({ where: whereFeed, orderBy: { respondedAt: "desc" }, take: 1 });
    if (latest.length) {
      const cnt = await db.appointment.count({ where: whereFeed });
      const a = latest[0];
      const verb = a.status === "CONFIRMED" ? "confirmed" : a.status === "DECLINED" ? "declined" : "rescheduled";
      const when = new Date(a.scheduledAt).toLocaleString("en-GB", { weekday: "short", day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" });
      items.push({
        id: "appt-updates", kind: "appointments", severity: "info", count: cnt,
        title: cnt > 1 ? `${cnt} appointment responses to relay to visitors` : "An appointment response to relay to the visitor",
        detail: `${a.respondedByName ?? "Staff"} ${verb} the ${when} meeting with ${a.withName}${a.status === "RESCHEDULED" ? " — new time proposed, confirm it for the visitor" : ""}.`,
        module: "frontoffice", tab: "appointments", at: a.respondedAt,
      });
    }
  }

  // 9 — LIVE EMERGENCY (wave 62 AI Emergency Command). Any EMERGENCY siren
  //     event in the last 20 minutes shows a danger item to EVERY user of the
  //     school — the in-app leg of the escalation alongside the SMS fan-out.
  {
    const since = new Date(Date.now() - 20 * 60_000);
    const live = await db.sirenEvent.findFirst({
      where: { schoolId, kind: "EMERGENCY", createdAt: { gte: since } },
      orderBy: { createdAt: "desc" },
    });
    if (live) {
      const minsAgo = Math.max(0, Math.round((Date.now() - new Date(live.createdAt).getTime()) / 60_000));
      items.push({
        id: "live-emergency", kind: "siren", severity: "danger", count: 1,
        title: `EMERGENCY sounded ${minsAgo === 0 ? "just now" : `${minsAgo} min ago`} — ${live.label.replace("EMERGENCY — ", "")}`,
        detail: `${live.note ?? "Follow the directions of your teachers immediately."} — declared by ${live.byName}.`,
        module: "siren", tab: "live", at: live.createdAt,
      });
    }
  }

  // most recent first; severities weighted so danger leads ties
  const rank: Record<string, number> = { danger: 0, warning: 1, info: 2 };
  items.sort((a, b) => (rank[a.severity] - rank[b.severity]) || ((b.at?.getTime?.() ?? 0) - (a.at?.getTime?.() ?? 0)));
  return NextResponse.json({ items });
}

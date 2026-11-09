import { NextRequest, NextResponse } from "next/server";
import { db } from "@/lib/db";
import { opsAuth } from "@/lib/ops-auth";
import { canAny, effectivePermissions, parseRolePerms } from "@/lib/rbac";
import { loadRoleConfig } from "@/lib/session";

// ── Internal messaging (CI3 message_thread / message parity, iEduPro) ──
// Two-pane staff inbox: threads keyed by unordered sender↔recipient pairs
// (either direction reuses the same thread — CI3 send_new_private_message
// semantics), messages with ≤4MB MediaAsset attachments, mark-read on open,
// delete-thread (messages cascade). This wave is staff messaging only —
// PARENT/STUDENT accounts have no messages.use and are excluded.

const PERM = "messages.use";
const MAX_SUBJECT = 120;
const MAX_BODY = 5000;
const MAX_ATTACHMENT_BYTES = 4 * 1024 * 1024; // CI3 parity limit
const ATTACHMENT_MIME = new Set(["image/png", "image/jpeg", "image/webp", "application/pdf"]);
const STAFF_ONLY_ROLES = ["PARENT", "STUDENT"];

/** Effective messages.use permission for an arbitrary school user
 *  (role config layer + per-user overrides — same resolution as the session). */
async function hasMessagesPerm(schoolId: string, u: { role: string; permissionsJson?: string | null }): Promise<boolean> {
  const cfg = await loadRoleConfig(schoolId, u.role);
  const configPerms = cfg ? parseRolePerms(cfg.permissions) : null;
  return canAny(effectivePermissions(u.role, u.permissionsJson ?? null, configPerms), [PERM]);
}

/** Validate an attachmentId references an ATTACHMENT MediaAsset of this school. */
async function validAttachment(schoolId: string, attachmentId: unknown): Promise<string | null> {
  if (attachmentId === undefined || attachmentId === null || attachmentId === "") return null; // optional
  if (typeof attachmentId !== "string") return "INVALID";
  const asset = await db.mediaAsset.findUnique({ where: { id: attachmentId } });
  if (!asset || asset.schoolId !== schoolId || asset.kind !== "ATTACHMENT") return "INVALID";
  return attachmentId;
}

const trunc = (s: string, n: number) => (s.length > n ? `${s.slice(0, n)}…` : s);

// ── GET /api/school/[id]/ops/messages — inbox, recipients, ?thread=<id> ──
export async function GET(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  // strict: messaging is personal — association officers keep oversight views
  // elsewhere, but a thread list is meaningless for a non-participant.
  const auth = await opsAuth(req, schoolId, "GET", { perms: [PERM], strict: true });
  if (!auth.ok) return auth.res;
  const user = auth.user;
  const me = { id: user.id, fullName: user.fullName };

  const threads = await db.messageThread.findMany({
    where: { schoolId, OR: [{ senderId: user.id }, { recipientId: user.id }] },
    orderBy: { lastMessageAt: "desc" },
    include: {
      sender: true, recipient: true,
      messages: { orderBy: { createdAt: "desc" }, take: 1 },
    },
  });

  const ids = threads.map((t) => t.id);
  const unread = ids.length
    ? await db.message.groupBy({
        by: ["threadId"],
        where: { threadId: { in: ids }, senderId: { not: user.id }, readAt: null },
        _count: { _all: true },
      })
    : [];
  const unreadByThread = new Map(unread.map((u) => [u.threadId, u._count._all]));

  const shape = (u: { id: string; fullName: string; role: string; title: string | null; avatarSeed: string | null }) =>
    ({ id: u.id, fullName: u.fullName, role: u.role, title: u.title, avatarSeed: u.avatarSeed });

  const threadItems = threads.map((t) => {
    const counterpart = t.senderId === user.id ? t.recipient : t.sender;
    const last = t.messages[0] ?? null;
    return {
      id: t.id,
      subject: t.subject,
      counterpart: shape(counterpart),
      lastMessage: last ? { body: trunc(last.body, 90), senderId: last.senderId, createdAt: last.createdAt } : null,
      lastMessageAt: t.lastMessageAt,
      createdAt: t.createdAt,
      unreadCount: unreadByThread.get(t.id) ?? 0,
    };
  });

  // compose picker: active staff of this school, excluding self & portal roles
  const recipients = await db.user.findMany({
    where: { schoolId, status: "ACTIVE", id: { not: user.id }, role: { notIn: STAFF_ONLY_ROLES } },
    orderBy: { fullName: "asc" },
    select: { id: true, fullName: true, role: true, title: true },
  });

  // ?thread=<id> — full conversation (participants only, else 404)
  const url = new URL(req.url);
  const threadParam = url.searchParams.get("thread");
  let thread: {
    id: string; subject: string; counterpart: ReturnType<typeof shape>;
    messages: { id: string; senderId: string; senderName: string; body: string; attachmentId: string | null; readAt: Date | null; createdAt: Date }[];
  } | null = null;
  if (threadParam) {
    const t = await db.messageThread.findUnique({
      where: { id: threadParam },
      include: { sender: true, recipient: true, messages: { orderBy: { createdAt: "asc" } } },
    });
    if (t && t.schoolId === schoolId && (t.senderId === user.id || t.recipientId === user.id)) {
      const counterpart = t.senderId === user.id ? t.recipient : t.sender;
      const nameById: Record<string, string> = { [t.senderId]: t.sender.fullName, [t.recipientId]: t.recipient.fullName };
      thread = {
        id: t.id,
        subject: t.subject,
        counterpart: shape(counterpart),
        messages: t.messages.map((m) => ({
          id: m.id, senderId: m.senderId, senderName: nameById[m.senderId] ?? "Unknown",
          body: m.body, attachmentId: m.attachmentId, readAt: m.readAt, createdAt: m.createdAt,
        })),
      };
    } else {
      return NextResponse.json({ error: "Thread not found" }, { status: 404 });
    }
  }

  return NextResponse.json({
    me,
    threads: threadItems,
    unreadTotal: threadItems.reduce((n, t) => n + t.unreadCount, 0),
    recipients,
    ...(thread ? { thread } : {}),
  });
}

// ── POST /api/school/[id]/ops/messages — action dispatch ──
export async function POST(req: NextRequest, { params }: { params: Promise<{ id: string }> }) {
  const { id: schoolId } = await params;
  const body = await req.json().catch(() => ({}));
  const action: string = body.action;
  const auth = await opsAuth(req, schoolId, "POST", { perms: [PERM] });
  if (!auth.ok) return auth.res;
  const user = auth.user;

  // ── send: find-or-create the thread for the pair (CI3 send_new_private_message) ──
  if (action === "send") {
    const { to, subject, body: text, attachmentId } = body;
    if (typeof to !== "string" || !to.trim()) return NextResponse.json({ error: "Recipient is required" }, { status: 400 });
    if (to === user.id) return NextResponse.json({ error: "You cannot send a message to yourself" }, { status: 400 });

    const recipient = await db.user.findUnique({ where: { id: to } });
    if (!recipient || recipient.schoolId !== schoolId) {
      return NextResponse.json({ error: "Recipient not found at this school" }, { status: 400 });
    }
    if (recipient.status !== "ACTIVE") {
      return NextResponse.json({ error: "Recipient account is not active" }, { status: 400 });
    }
    if (!(await hasMessagesPerm(schoolId, recipient))) {
      return NextResponse.json({ error: "Recipient does not have access to internal messaging" }, { status: 400 });
    }

    const subjectTrim = typeof subject === "string" ? subject.trim() : "";
    if (!subjectTrim || subjectTrim.length > MAX_SUBJECT) {
      return NextResponse.json({ error: `Subject is required (1–${MAX_SUBJECT} characters)` }, { status: 400 });
    }
    const textTrim = typeof text === "string" ? text.trim() : "";
    if (!textTrim || textTrim.length > MAX_BODY) {
      return NextResponse.json({ error: `Message body is required (1–${MAX_BODY} characters)` }, { status: 400 });
    }
    const att = await validAttachment(schoolId, attachmentId);
    if (att === "INVALID") return NextResponse.json({ error: "Attachment not found" }, { status: 400 });

    // one thread per unordered pair — check BOTH directions before creating
    let thread = await db.messageThread.findFirst({
      where: { schoolId, OR: [{ senderId: user.id, recipientId: to }, { senderId: to, recipientId: user.id }] },
    });
    if (!thread) {
      thread = await db.messageThread.create({
        data: { schoolId, subject: subjectTrim, senderId: user.id, recipientId: to, lastMessageAt: new Date() },
      });
    }
    const message = await db.message.create({
      data: { threadId: thread.id, senderId: user.id, body: textTrim, attachmentId: att },
    });
    await db.messageThread.update({ where: { id: thread.id }, data: { lastMessageAt: new Date() } });
    return NextResponse.json({ threadId: thread.id, messageId: message.id });
  }

  // ── reply: append to a thread the user participates in ──
  if (action === "reply") {
    const { threadId, body: text, attachmentId } = body;
    const thread = typeof threadId === "string"
      ? await db.messageThread.findUnique({ where: { id: threadId } })
      : null;
    if (!thread || thread.schoolId !== schoolId || (thread.senderId !== user.id && thread.recipientId !== user.id)) {
      return NextResponse.json({ error: "Thread not found" }, { status: 404 });
    }
    const textTrim = typeof text === "string" ? text.trim() : "";
    if (!textTrim || textTrim.length > MAX_BODY) {
      return NextResponse.json({ error: `Message body is required (1–${MAX_BODY} characters)` }, { status: 400 });
    }
    const att = await validAttachment(schoolId, attachmentId);
    if (att === "INVALID") return NextResponse.json({ error: "Attachment not found" }, { status: 400 });

    const message = await db.message.create({
      data: { threadId: thread.id, senderId: user.id, body: textTrim, attachmentId: att },
    });
    await db.messageThread.update({ where: { id: thread.id }, data: { lastMessageAt: new Date() } });
    return NextResponse.json({ messageId: message.id });
  }

  // ── mark-read: flag the counterpart's unread messages (CI3 message_read) ──
  if (action === "mark-read") {
    const thread = typeof body.threadId === "string"
      ? await db.messageThread.findUnique({ where: { id: body.threadId } })
      : null;
    if (!thread || thread.schoolId !== schoolId || (thread.senderId !== user.id && thread.recipientId !== user.id)) {
      return NextResponse.json({ error: "Thread not found" }, { status: 404 });
    }
    const r = await db.message.updateMany({
      where: { threadId: thread.id, senderId: { not: user.id }, readAt: null },
      data: { readAt: new Date() },
    });
    return NextResponse.json({ updated: r.count });
  }

  // ── delete-thread: removes the thread and (cascade) its messages ──
  if (action === "delete-thread") {
    const thread = typeof body.threadId === "string"
      ? await db.messageThread.findUnique({ where: { id: body.threadId } })
      : null;
    if (!thread || thread.schoolId !== schoolId || (thread.senderId !== user.id && thread.recipientId !== user.id)) {
      return NextResponse.json({ error: "Thread not found" }, { status: 404 });
    }
    await db.messageThread.delete({ where: { id: thread.id } }); // messages cascade
    return NextResponse.json({ ok: true });
  }

  // ── upload-attachment: data-URL → MediaAsset kind ATTACHMENT (≤4MB) ──
  if (action === "upload-attachment") {
    const { dataUrl } = body;
    if (typeof dataUrl !== "string" || !dataUrl.startsWith("data:")) {
      return NextResponse.json({ error: "A base64 data-URL is required" }, { status: 400 });
    }
    const m = /^data:([a-z]+\/[a-z0-9.+-]+);base64,([\s\S]+)$/i.exec(dataUrl);
    if (!m) return NextResponse.json({ error: "Malformed data-URL" }, { status: 400 });
    const [, mimeType, b64] = m;
    if (!ATTACHMENT_MIME.has(mimeType.toLowerCase())) {
      return NextResponse.json({ error: "Attachments must be PNG, JPEG, WebP or PDF" }, { status: 400 });
    }
    let bytes: Buffer;
    try { bytes = Buffer.from(b64, "base64"); } catch { return NextResponse.json({ error: "Malformed data-URL" }, { status: 400 }); }
    if (!bytes.length) return NextResponse.json({ error: "Attachment is empty" }, { status: 400 });
    if (bytes.length > MAX_ATTACHMENT_BYTES) {
      return NextResponse.json({ error: "Attachment exceeds the 4MB limit" }, { status: 400 });
    }
    const asset = await db.mediaAsset.create({
      data: { schoolId, kind: "ATTACHMENT", mimeType: mimeType.toLowerCase(), sizeBytes: bytes.length, data: new Uint8Array(bytes) },
    });
    return NextResponse.json({ attachmentId: asset.id, sizeBytes: asset.sizeBytes, mimeType: asset.mimeType });
  }

  return NextResponse.json({ error: "Unknown action" }, { status: 400 });
}

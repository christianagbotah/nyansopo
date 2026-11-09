/* Internal messaging (CI3 message_thread/message parity) smoke test —
   (a) parent GET/POST 403, (b) super GET shape, (c) send → thread created,
   visible both sides, unread=1 for recipient, (d) reply reuses the SAME
   thread (either-direction pair), (e) same-direction send still same thread,
   (f) mark-read clears recipient unread + total, (g) ?thread= full message
   list, (h) non-participant ?thread= 404, (i) send validation, (j) attachment
   upload/limits/send, (k) delete-thread by participant, (l) delete by
   non-participant 404. Creates its own threads + MediaAssets and CLEANS UP.
   Run: DATABASE_URL=postgresql://… bun scripts/smoke-messages.ts */
process.env.DATABASE_URL = process.env.DATABASE_URL?.startsWith("postgres")
  ? process.env.DATABASE_URL
  : "postgresql://nyansapo@127.0.0.1:5433/nyansapo?schema=public";
import { PrismaClient } from "@prisma/client";

const db = new PrismaClient();
const BASE = "http://localhost:3000";
let pass = 0, fail = 0;

async function check(label: string, cond: boolean, extra = "") {
  if (cond) { pass++; console.log(`  ✅ ${label}`); }
  else { fail++; console.log(`  ❌ ${label} ${extra}`); }
}

const SUBJECT_MAIN = "Smoke: term planning";
const SUBJECT_ATT = "Smoke: attachment";
const TINY_PNG = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==";

async function main() {
  const [superAdmin, head, cashier, parent, teacher] = await Promise.all([
    db.user.findUnique({ where: { email: "super@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "head@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "cashier@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "parent@nyansapo.gh" } }),
    db.user.findUnique({ where: { email: "teacher@nyansapo.gh" } }),
  ]);
  if (!superAdmin || !head || !cashier || !parent || !teacher) { console.log("missing demo users — run the seed first"); process.exit(1); }
  const school = await db.school.findUnique({ where: { code: "GEA" } });
  if (!school) { console.log("GEA school missing"); process.exit(1); }
  const schoolId = school.id;
  const URL = `/api/school/${schoolId}/ops/messages`;
  const H = (u: typeof superAdmin) => ({ "content-type": "application/json", "x-user-id": u.id });
  const get = async (u: typeof superAdmin, url: string) => fetch(BASE + url, { headers: H(u) });
  const post = async (u: typeof superAdmin, url: string, body: any) => fetch(BASE + url, { method: "POST", headers: H(u), body: JSON.stringify(body) });

  // pre-clean any half-finished previous run
  await db.message.deleteMany({ where: { thread: { subject: { in: [SUBJECT_MAIN, SUBJECT_ATT] } } } });
  await db.messageThread.deleteMany({ where: { subject: { in: [SUBJECT_MAIN, SUBJECT_ATT] } } });

  const createdThreadIds: string[] = [];
  const createdAssetIds: string[] = [];

  try {
    // ── (a) portal accounts have no messages.use — GET and POST both 403 ──
    const pa = await get(parent, URL);
    check("parent GET blocked (403)", pa.status === 403, `got ${pa.status}`);
    const pb = await post(parent, URL, { action: "send", to: head.id, subject: "x", body: "x" });
    check("parent POST blocked (403)", pb.status === 403, `got ${pb.status}`);

    // ── (b) super GET 200 with the inbox shape ──
    const gb = await get(superAdmin, URL);
    const jb = await gb.json();
    check("super GET 200", gb.status === 200, `got ${gb.status}`);
    check("GET shape: threads[] + unreadTotal + recipients[] + me", Array.isArray(jb.threads) && typeof jb.unreadTotal === "number" && Array.isArray(jb.recipients) && !!jb.me?.id);
    check("compose picker excludes self & PARENT/STUDENT roles", !jb.recipients.some((r: any) => r.id === superAdmin.id) && !jb.recipients.some((r: any) => ["PARENT", "STUDENT"].includes(r.role)));

    const headUnreadBaseline = (await (await get(head, URL)).json()).unreadTotal ?? 0;

    // ── (c) send super → head: creates thread + message ──
    const sc = await post(superAdmin, URL, { action: "send", to: head.id, subject: SUBJECT_MAIN, body: "First message from the smoke suite." });
    const jc = await sc.json();
    check("send → 200 with threadId + messageId", sc.status === 200 && !!jc.threadId && !!jc.messageId, JSON.stringify(jc));
    createdThreadIds.push(jc.threadId);
    const sendThreadId = jc.threadId as string;

    const inboxSuper = (await (await get(superAdmin, URL)).json());
    const inboxHead = (await (await get(head, URL)).json());
    const tSuper = inboxSuper.threads.find((t: any) => t.id === sendThreadId);
    const tHead = inboxHead.threads.find((t: any) => t.id === sendThreadId);
    check("thread visible on the sender side (counterpart = head)", !!tSuper && tSuper.counterpart.id === head.id);
    check("thread visible on the recipient side (counterpart = super)", !!tHead && tHead.counterpart.id === superAdmin.id);
    check("unreadCount = 1 for head, 0 for super", tHead?.unreadCount === 1 && tSuper?.unreadCount === 0, `head=${tHead?.unreadCount} super=${tSuper?.unreadCount}`);
    check("lastMessage preview present", !!tSuper?.lastMessage?.body && tSuper.lastMessage.senderId === superAdmin.id);
    check("head unreadTotal rose by 1", inboxHead.unreadTotal === headUnreadBaseline + 1, `${inboxHead.unreadTotal} vs baseline ${headUnreadBaseline}`);

    // ── (d) head replies → SAME thread (CI3 either-direction pair reuse) ──
    const sd = await post(head, URL, { action: "reply", threadId: sendThreadId, body: "Reply from head — same thread expected." });
    check("reply → 200 with messageId", sd.status === 200 && !!(await sd.json()).messageId, `got ${sd.status}`);
    const pairCount = await db.messageThread.count({ where: { schoolId, OR: [{ senderId: superAdmin.id, recipientId: head.id }, { senderId: head.id, recipientId: superAdmin.id }] } });
    check("reverse-direction reply reused the SAME thread (no second thread)", pairCount === 1, `pair thread count = ${pairCount}`);

    // ── (e) same-direction send again → still the same thread ──
    const se = await post(superAdmin, URL, { action: "send", to: head.id, subject: "Different subject must NOT fork a thread", body: "Second send — same thread expected." });
    const je = await se.json();
    check("same-direction send → 200", se.status === 200);
    check("still the same threadId", je.threadId === sendThreadId, `got ${je.threadId}`);
    const pairCount2 = await db.messageThread.count({ where: { schoolId, OR: [{ senderId: superAdmin.id, recipientId: head.id }, { senderId: head.id, recipientId: superAdmin.id }] } });
    check("still exactly one thread for the pair", pairCount2 === 1, `pair thread count = ${pairCount2}`);
    check("thread subject set on creation only (unchanged by later sends)", (await db.messageThread.findUnique({ where: { id: sendThreadId } }))?.subject === SUBJECT_MAIN);

    // ── (f) mark-read as head clears the recipient's unread ──
    const mf = await post(head, URL, { action: "mark-read", threadId: sendThreadId });
    const jf = await mf.json();
    check("mark-read → 200 with count", mf.status === 200 && typeof jf.updated === "number", JSON.stringify(jf));
    check("marked the counterpart's 2 unread messages", jf.updated === 2, `updated = ${jf.updated}`);
    const inboxHead2 = (await (await get(head, URL)).json());
    const tHead2 = inboxHead2.threads.find((t: any) => t.id === sendThreadId);
    check("head unreadCount now 0", tHead2?.unreadCount === 0);
    check("head unreadTotal dropped back to baseline", inboxHead2.unreadTotal === headUnreadBaseline, `${inboxHead2.unreadTotal} vs ${headUnreadBaseline}`);

    // ── (g) GET ?thread= as super → full message list ──
    const gg = await get(superAdmin, `${URL}?thread=${sendThreadId}`);
    const jg = await gg.json();
    check("GET ?thread= 200 for participant", gg.status === 200, `got ${gg.status}`);
    check("thread carries 3 messages with sender names", jg.thread?.messages?.length === 3 && jg.thread.messages.every((m: any) => !!m.senderName && !!m.body));
    check("messages ordered oldest → newest", jg.thread.messages[0].body.includes("First message") && jg.thread.messages[2].body.includes("Second send"));

    // ── (h) GET ?thread= as a non-participant → 404 ──
    const gh = await get(cashier, `${URL}?thread=${sendThreadId}`);
    check("GET ?thread= 404 for non-participant", gh.status === 404, `got ${gh.status}`);

    // ── (i) send validation ──
    const v1 = await post(superAdmin, URL, { action: "send", to: head.id, subject: "", body: "x" });
    check("empty subject rejected (400)", v1.status === 400, `got ${v1.status}`);
    const v2 = await post(superAdmin, URL, { action: "send", to: head.id, subject: "s", body: "   " });
    check("empty body rejected (400)", v2.status === 400, `got ${v2.status}`);
    const v3 = await post(superAdmin, URL, { action: "send", to: "clw0000000000000000000000", subject: "s", body: "x" });
    check("unknown recipient rejected (400)", v3.status === 400, `got ${v3.status}`);
    const v4 = await post(superAdmin, URL, { action: "send", to: parent.id, subject: "s", body: "x" });
    check("recipient without messages.use rejected (400/403)", v4.status === 400 || v4.status === 403, `got ${v4.status}`);
    const v5 = await post(superAdmin, URL, { action: "send", to: superAdmin.id, subject: "s", body: "x" });
    check("self-send rejected (400)", v5.status === 400, `got ${v5.status}`);
    const v6 = await post(cashier, URL, { action: "reply", threadId: sendThreadId, body: "not my thread" });
    check("reply to a non-participant thread rejected (404)", v6.status === 404, `got ${v6.status}`);

    // ── (j) attachments ──
    const ua = await post(superAdmin, URL, { action: "upload-attachment", dataUrl: TINY_PNG });
    const ja = await ua.json();
    check("upload tiny PNG → 200 with attachmentId", ua.status === 200 && !!ja.attachmentId, JSON.stringify(ja));
    createdAssetIds.push(ja.attachmentId);
    const media = await get(superAdmin, `/api/media/${ja.attachmentId}`);
    check("attachment served from /api/media/[id] (200, image/png)", media.status === 200 && media.headers.get("content-type") === "image/png", `got ${media.status}`);
    const sa = await post(superAdmin, URL, { action: "send", to: teacher.id, subject: SUBJECT_ATT, body: "Message with an attachment.", attachmentId: ja.attachmentId });
    const jaa = await sa.json();
    check("send with attachment → 200", sa.status === 200, JSON.stringify(jaa));
    createdThreadIds.push(jaa.threadId);
    const ga = await get(superAdmin, `${URL}?thread=${jaa.threadId}`);
    const jga = await ga.json();
    check("message carries the attachmentId", jga.thread?.messages?.[0]?.attachmentId === ja.attachmentId, JSON.stringify(jga.thread?.messages?.[0]));
    const oversize = "data:image/png;base64," + "A".repeat(6_000_000); // ~4.5MB decoded
    const uo = await post(superAdmin, URL, { action: "upload-attachment", dataUrl: oversize });
    check("oversize attachment (>4MB) rejected (400)", uo.status === 400, `got ${uo.status}`);
    const um = await post(superAdmin, URL, { action: "upload-attachment", dataUrl: "data:text/plain;base64,SGVsbG8=" });
    check("wrong mime (text/plain) rejected (400)", um.status === 400, `got ${um.status}`);

    // ── (l) delete-thread by a non-participant → 404 (before the real delete) ──
    const dl = await post(cashier, URL, { action: "delete-thread", threadId: sendThreadId });
    check("delete-thread by non-participant rejected (404)", dl.status === 404, `got ${dl.status}`);

    // ── (k) delete-thread by an involved user → thread + messages gone ──
    const dk = await post(head, URL, { action: "delete-thread", threadId: sendThreadId });
    check("delete-thread by participant → 200 ok", dk.status === 200 && (await dk.json()).ok === true, `got ${dk.status}`);
    const threadGone = await db.messageThread.findUnique({ where: { id: sendThreadId } });
    const messagesGone = await db.message.count({ where: { threadId: sendThreadId } });
    check("thread + messages cascade-deleted in the DB", !threadGone && messagesGone === 0);
    const gk = await get(head, `${URL}?thread=${sendThreadId}`);
    check("GET ?thread= after delete → 404", gk.status === 404, `got ${gk.status}`);
  } finally {
    // ── cleanup: every thread / message / MediaAsset this suite created ──
    await db.message.deleteMany({ where: { thread: { subject: { in: [SUBJECT_MAIN, SUBJECT_ATT] } } } });
    await db.messageThread.deleteMany({ where: { subject: { in: [SUBJECT_MAIN, SUBJECT_ATT] } } });
    if (createdThreadIds.length) await db.messageThread.deleteMany({ where: { id: { in: createdThreadIds } } }).catch(() => null);
    if (createdAssetIds.length) await db.mediaAsset.deleteMany({ where: { id: { in: createdAssetIds } } }).catch(() => null);
    await db.$disconnect();
  }

  console.log(`\nmessages smoke: ${pass} passed, ${fail} failed`);
  process.exit(fail ? 1 : 0);
}

main().catch((e) => { console.error("smoke crashed:", e); process.exit(1); });

"use client";

/* ── wave 40 — Portal messenger (parent/learner ⇄ school office) ────────────
 *
 * The two-way conversation surface embedded in the Parent and Learner
 * portals: the same MessageThread/Message tables the staff Messages module
 * reads, but self-scoped — the parent (or learner) sees only their own
 * threads, and can start conversations only with their curated school
 * contacts (the head, the bursar, and their children's class teachers).
 *
 * CI3 group_message parity: the legacy parent view had message read/reply;
 * the modern staff inbox already replies to these threads from its own
 * two-pane client — this completes the loop.
 * ──────────────────────────────────────────────────────────────────────── */

import { useCallback, useEffect, useRef, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { initials, avatarColor } from "@/lib/format";
import { cn } from "@/lib/utils";
import { toast } from "@/hooks/use-toast";
import { ArrowLeft, Inbox, Loader2, MessageSquare, Plus, Send } from "lucide-react";

type Contact = { id: string; fullName: string; role: string; title: string | null };
type ThreadItem = {
  id: string; subject: string; counterpart: Contact;
  lastMessage: { body: string; senderId: string; createdAt: string } | null;
  lastMessageAt: string; unreadCount: number;
};
type MsgRow = { id: string; senderId: string; mine: boolean; senderName: string; body: string; readAt: string | null; createdAt: string };
type Conversation = { id: string; subject: string; counterpart: Contact; messages: MsgRow[] };
type MessengerData = {
  me: { id: string; fullName: string };
  contacts: Contact[];
  threads: ThreadItem[];
  unreadTotal: number;
  thread?: Conversation;
};

const relTime = (iso?: string) => {
  if (!iso) return "";
  const s = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000);
  if (s < 60) return "just now";
  if (s < 3600) return `${Math.floor(s / 60)} min ago`;
  if (s < 86400) return `${Math.floor(s / 3600)} h ago`;
  return `${Math.floor(s / 86400)} d ago`;
};
const stamp = (iso: string) => new Date(iso).toLocaleString("en-GB", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" });

function SeedAvatar({ user, className }: { user: Contact; className?: string }) {
  return (
    <Avatar className={className} aria-hidden="true">
      <AvatarFallback className={cn("text-[10px] font-bold", avatarColor(user.fullName))}>{initials(user.fullName)}</AvatarFallback>
    </Avatar>
  );
}

export function PortalMessenger() {
  const { currentSchoolId, session } = useStore();
  const schoolId = currentSchoolId ?? "";
  const isLearner = session?.role === "STUDENT";

  const [data, setData] = useState<MessengerData | null>(null);
  const [loading, setLoading] = useState(true);
  const [openThreadId, setOpenThreadId] = useState<string | null>(null);
  const [reply, setReply] = useState("");
  const [busy, setBusy] = useState(false);
  const [compose, setCompose] = useState(false);
  const [to, setTo] = useState("");
  const [subject, setSubject] = useState("");
  const [text, setText] = useState("");
  const scrollRef = useRef<HTMLDivElement>(null);

  const load = useCallback(async (threadId?: string | null) => {
    if (!schoolId) return;
    try {
      const res = await apiGet<MessengerData>(`/api/school/${schoolId}/ops/portal?view=messages${threadId ? `&thread=${threadId}` : ""}`);
      setData(res);
      if (threadId && res.thread) {
        // mark the counterpart's messages read (fire-and-forget)
        apiPost(`/api/school/${schoolId}/ops/portal`, { action: "msg.mark-read", threadId }).catch(() => {});
        setData((d) => d && { ...d, thread: res.thread, threads: d.threads.map((t) => (t.id === threadId ? { ...t, unreadCount: 0 } : t)) });
      }
    } catch {
      setData((d) => (threadId ? d : null));
    }
  }, [schoolId]);

  useEffect(() => { setLoading(true); load(null).finally(() => setLoading(false)); }, [load]);
  useEffect(() => { if (openThreadId) load(openThreadId); }, [openThreadId, load]);
  useEffect(() => { scrollRef.current?.scrollTo({ top: scrollRef.current.scrollHeight }); }, [data?.thread?.messages?.length]);

  async function sendFirst() {
    if (!schoolId || !to) return;
    setBusy(true);
    try {
      const res = await apiPost<{ threadId: string }>(`/api/school/${schoolId}/ops/portal`, { action: "msg.send", to, subject, body: text });
      toast({ title: "Message sent", description: "The office will reply right here in this conversation." });
      setCompose(false); setTo(""); setSubject(""); setText("");
      setOpenThreadId(res.threadId);
    } catch (e: any) {
      toast({ title: "Could not send", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  async function sendReply() {
    if (!schoolId || !openThreadId || !reply.trim()) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}/ops/portal`, { action: "msg.reply", threadId: openThreadId, body: reply });
      setReply("");
      await load(openThreadId);
    } catch (e: any) {
      toast({ title: "Could not send", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  if (loading) return <LoadingBlock label="Loading your conversations…" />;
  const conversation = data?.thread ?? null;

  /* ── conversation view (desktop + mobile back) ── */
  if (openThreadId && conversation) {
    return (
      <Card>
        <CardHeader className="pb-2">
          <div className="flex items-start justify-between gap-2">
            <div className="flex items-center gap-2.5 min-w-0">
              <Button variant="ghost" size="icon" className="size-8 -ml-2" onClick={() => { setOpenThreadId(null); load(null); }} aria-label="Back to inbox">
                <ArrowLeft className="size-4" />
              </Button>
              <SeedAvatar user={conversation.counterpart} className="size-9" />
              <div className="min-w-0">
                <CardTitle className="text-base truncate">{conversation.subject}</CardTitle>
                <p className="text-xs text-muted-foreground truncate">
                  with {conversation.counterpart.fullName}{conversation.counterpart.title ? ` · ${conversation.counterpart.title}` : ""}
                </p>
              </div>
            </div>
          </div>
        </CardHeader>
        <CardContent className="space-y-3">
          <div ref={scrollRef} className="space-y-2.5 max-h-80 overflow-y-auto nice-scroll pr-1">
            {conversation.messages.map((m) => (
              <div key={m.id} className={cn("flex", m.mine ? "justify-end" : "justify-start")}>
                <div className={cn(
                  "max-w-[80%] rounded-2xl px-3.5 py-2 text-sm",
                  m.mine ? "bg-primary text-primary-foreground rounded-br-sm" : "bg-muted rounded-bl-sm",
                )}>
                  <p className="whitespace-pre-wrap break-words">{m.body}</p>
                  <p className={cn("text-[10px] mt-1", m.mine ? "text-primary-foreground/70" : "text-muted-foreground")}>
                    {m.senderName} · {stamp(m.createdAt)}{m.mine && m.readAt ? " · read" : ""}
                  </p>
                </div>
              </div>
            ))}
          </div>
          <div className="flex gap-2">
            <Textarea rows={2} value={reply} onChange={(e) => setReply(e.target.value)} placeholder="Write your reply…" onKeyDown={(e) => { if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); sendReply(); } }} />
            <Button size="sm" className="gap-1.5 self-end" disabled={busy || !reply.trim()} onClick={sendReply}>
              {busy ? <Loader2 className="size-4 animate-spin" /> : <Send className="size-4" />} Send
            </Button>
          </div>
        </CardContent>
      </Card>
    );
  }

  /* ── inbox list ── */
  return (
    <>
      <Card>
        <CardHeader className="pb-2">
          <CardTitle className="text-base flex items-center justify-between gap-2">
            <span className="flex items-center gap-2"><MessageSquare className="size-4" /> Conversations with the school</span>
            <Button size="sm" className="gap-1.5" onClick={() => setCompose(true)}>
              <Plus className="size-4" /> New message
            </Button>
          </CardTitle>
          <p className="text-xs text-muted-foreground">Ask the office about fees, attendance, results or anything {isLearner ? "about your schoolwork" : "about your child"} — replies land here.</p>
        </CardHeader>
        <CardContent>
          {(data?.threads ?? []).length ? (
            <div className="space-y-2 max-h-96 overflow-y-auto nice-scroll">
              {data!.threads.map((t) => (
                <button key={t.id} className="w-full text-left rounded-lg border p-3 hover:bg-muted/60 transition-colors flex items-center gap-3" onClick={() => setOpenThreadId(t.id)}>
                  <SeedAvatar user={t.counterpart} className="size-10 shrink-0" />
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <span className="font-medium text-sm truncate">{t.counterpart.fullName}</span>
                      {t.unreadCount > 0 && <Badge className="h-5 px-1.5 bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300">{t.unreadCount} new</Badge>}
                    </div>
                    <div className="text-xs text-muted-foreground truncate">{t.subject}</div>
                    <p className="text-xs text-muted-foreground truncate mt-0.5">{t.lastMessage ? t.lastMessage.body : "—"}</p>
                  </div>
                  <div className="text-[11px] text-muted-foreground shrink-0">{relTime(t.lastMessageAt)}</div>
                </button>
              ))}
            </div>
          ) : (
            <EmptyState icon={<Inbox className="size-5" />} title="No conversations yet" hint={`Start one with the head, the bursar${isLearner ? " or your class teacher" : " or your child's class teacher"} — they reply from the school's staff inbox.`} />
          )}
        </CardContent>
      </Card>

      {/* compose dialog */}
      <Dialog open={compose} onOpenChange={(o) => !o && setCompose(false)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>New message to the school</DialogTitle>
            <DialogDescription>Pick a contact — the head, the bursar, {isLearner ? "or your class teacher" : "or your child's class teacher"}.</DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <div className="space-y-1.5">
              <Label>To</Label>
              <Select value={to} onValueChange={setTo}>
                <SelectTrigger><SelectValue placeholder="Choose a school contact" /></SelectTrigger>
                <SelectContent>
                  {(data?.contacts ?? []).map((c) => (
                    <SelectItem key={c.id} value={c.id}>{c.fullName}{c.title ? ` — ${c.title}` : ` (${c.role.toLowerCase()})`}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {(data?.contacts ?? []).length === 0 && <p className="text-xs text-muted-foreground">No messaging contacts are available for your account yet.</p>}
            </div>
            <div className="space-y-1.5">
              <Label>Subject</Label>
              <Input value={subject} onChange={(e) => setSubject(e.target.value)} placeholder="e.g. Fee balance enquiry for JHS 3" maxLength={120} />
            </div>
            <div className="space-y-1.5">
              <Label>Message</Label>
              <Textarea rows={5} value={text} onChange={(e) => setText(e.target.value)} placeholder="Write your message…" maxLength={5000} />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCompose(false)}>Cancel</Button>
            <Button className="gap-1.5" disabled={busy || !to || !subject.trim() || !text.trim()} onClick={sendFirst}>
              {busy ? <Loader2 className="size-4 animate-spin" /> : <Send className="size-4" />} Send message
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}

"use client";
/* Internal messaging (CI3 message_thread/message parity, iEduPro private
   messaging): two-pane staff inbox — thread list ↔ conversation, compose
   dialog with recipient picker + ≤4MB attachments, mark-read on open,
   delete-thread. Mobile stacks: list → tap → conversation with back. */
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useStore, ROLE_LABELS } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from "@/components/ui/select";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { initials, avatarColor } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import { Mail, MessageSquare, Send, Paperclip, Trash2, ArrowLeft, Loader2, X, Download, Inbox } from "lucide-react";

// ── payload types (mirrors GET /api/school/[id]/ops/messages) ──
type Counterpart = { id: string; fullName: string; role: string; title?: string | null; avatarSeed?: string | null };
type ThreadItem = {
  id: string; subject: string; counterpart: Counterpart;
  lastMessage: { body: string; senderId: string; createdAt: string } | null;
  lastMessageAt: string; createdAt: string; unreadCount: number;
};
type MsgRow = { id: string; senderId: string; senderName: string; body: string; attachmentId: string | null; readAt: string | null; createdAt: string };
type Recipient = { id: string; fullName: string; role: string; title: string | null };
type Conversation = { id: string; subject: string; counterpart: Counterpart; messages: MsgRow[] };
type MsgData = {
  me: { id: string; fullName: string };
  threads: ThreadItem[];
  unreadTotal: number;
  recipients: Recipient[];
  thread?: Conversation;
};

const MAX_ATTACHMENT_BYTES = 4 * 1024 * 1024; // CI3 parity limit
const ATTACHMENT_MIME = new Set(["image/png", "image/jpeg", "image/webp", "application/pdf"]);

function relTime(iso?: string): string {
  if (!iso) return "";
  const s = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000);
  if (s < 60) return "just now";
  if (s < 3600) return `${Math.floor(s / 60)} min ago`;
  if (s < 86400) return `${Math.floor(s / 3600)} h ago`;
  return `${Math.floor(s / 86400)} d ago`;
}
const stamp = (iso: string) =>
  new Date(iso).toLocaleString("en-GB", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" });

/** initials avatar with the project's seed palette (avatarSeed, else name) */
function SeedAvatar({ user, className }: { user: Counterpart; className?: string }) {
  return (
    <Avatar className={className} aria-hidden="true">
      <AvatarFallback style={{ background: avatarColor(user.avatarSeed || user.fullName) }} className="text-white font-semibold">
        {initials(user.fullName)}
      </AvatarFallback>
    </Avatar>
  );
}

function RoleChip({ role }: { role: string }) {
  return <Badge variant="outline" className="text-[10px] px-1.5 py-0 font-medium text-muted-foreground">{ROLE_LABELS[role] ?? role}</Badge>;
}

type Attachment = { id: string; name: string };

export function Messages() {
  const { currentSchoolId, session } = useStore();
  const meId = session?.id ?? "";
  const base = currentSchoolId ? `/api/school/${currentSchoolId}/ops/messages` : "";

  const [data, setData] = useState<MsgData | null>(null);
  const [conversation, setConversation] = useState<Conversation | null>(null);
  const [convLoading, setConvLoading] = useState(false);
  const [mobilePane, setMobilePane] = useState<"list" | "conversation">("list");
  const [composeOpen, setComposeOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);

  // compose form
  const [cTo, setCTo] = useState("");
  const [cSubject, setCSubject] = useState("");
  const [cBody, setCBody] = useState("");
  const [cAttach, setCAttach] = useState<Attachment | null>(null);
  const [attaching, setAttaching] = useState(false);
  const [sending, setSending] = useState(false);

  // reply box
  const [rBody, setRBody] = useState("");
  const [rAttach, setRAttach] = useState<Attachment | null>(null);
  const [replying, setReplying] = useState(false);

  const messagesRef = useRef<HTMLDivElement | null>(null);
  const composeFileRef = useRef<HTMLInputElement | null>(null);
  const replyFileRef = useRef<HTMLInputElement | null>(null);

  const recipients = data?.recipients ?? [];
  const byRole = useMemo(() => {
    const groups = new Map<string, Recipient[]>();
    for (const r of recipients) {
      const key = ROLE_LABELS[r.role] ?? r.role;
      if (!groups.has(key)) groups.set(key, []);
      groups.get(key)!.push(r);
    }
    return [...groups.entries()].sort(([a], [b]) => a.localeCompare(b));
  }, [recipients]);

  // ── data loading ──
  const load = useCallback(() => {
    if (!base) return;
    apiGet<MsgData>(base)
      .then((r) => setData(r))
      .catch((e) => toast({ title: "Could not load messages", description: e?.message, variant: "destructive" }));
  }, [base]);

  const loadConversation = useCallback(async (threadId: string, markRead = true) => {
    if (!base) return null;
    setConvLoading(true);
    try {
      const r = await apiGet<MsgData>(`${base}?thread=${threadId}`);
      setConversation(r.thread ?? null);
      // CI3 parity: opening a conversation marks the counterpart's messages read
      if (markRead && r.thread) {
        await apiPost(base, { action: "mark-read", threadId }).catch(() => null);
        load(); // refresh unread counts in the list
      }
      setMobilePane("conversation");
      return r.thread ?? null;
    } catch (e: any) {
      toast({ title: "Could not open conversation", description: e?.message, variant: "destructive" });
      return null;
    } finally {
      setConvLoading(false);
    }
  }, [base, load]);

  useEffect(() => { load(); }, [load]);
  // reset when switching schools
  useEffect(() => { setConversation(null); setMobilePane("list"); setData(null); }, [currentSchoolId]);

  // ── polling: lightweight refresh every 45s + on window focus (bell pattern) ──
  useEffect(() => {
    const t = setInterval(() => {
      load();
      if (conversation) apiGet<MsgData>(`${base}?thread=${conversation.id}`)
        .then((r) => setConversation(r.thread ?? conversation))
        .catch(() => {/* transient — next tick retries */});
    }, 45_000);
    const onFocus = () => load();
    window.addEventListener("focus", onFocus);
    return () => { clearInterval(t); window.removeEventListener("focus", onFocus); };
  }, [load, base, conversation]);

  // keep the conversation pinned to the latest message (container scroll only —
  // scrollIntoView would drag the whole page)
  useEffect(() => {
    const el = messagesRef.current;
    if (el) el.scrollTop = el.scrollHeight;
  }, [conversation]);

  // ── attachments (compose + reply share the validation + upload path) ──
  const uploadAttachment = async (file: File): Promise<Attachment | null> => {
    if (!ATTACHMENT_MIME.has(file.type)) {
      toast({ title: "Attachment skipped", description: "PNG, JPEG, WebP or PDF only.", variant: "destructive" });
      return null;
    }
    if (file.size > MAX_ATTACHMENT_BYTES) {
      toast({ title: "Attachment skipped", description: "Attachments cannot be larger than 4MB.", variant: "destructive" });
      return null;
    }
    const dataUrl = await new Promise<string>((resolve, reject) => {
      const r = new FileReader();
      r.onload = () => resolve(String(r.result));
      r.onerror = () => reject(new Error("Could not read the file"));
      r.readAsDataURL(file);
    });
    setAttaching(true);
    try {
      const r = await apiPost<{ attachmentId: string }>(base, { action: "upload-attachment", dataUrl });
      return { id: r.attachmentId, name: file.name };
    } catch (e: any) {
      toast({ title: "Attachment upload failed", description: e?.message, variant: "destructive" });
      return null;
    } finally {
      setAttaching(false);
    }
  };

  const onPickAttachment = async (e: React.ChangeEvent<HTMLInputElement>, which: "compose" | "reply") => {
    const file = e.target.files?.[0];
    e.target.value = ""; // allow re-picking the same file after remove
    if (!file) return;
    const att = await uploadAttachment(file);
    if (att) (which === "compose" ? setCAttach : setRAttach)(att);
  };

  // ── actions ──
  const sendCompose = async () => {
    if (sending) return;
    if (!cTo) { toast({ title: "Pick a recipient first" }); return; }
    if (!cSubject.trim()) { toast({ title: "Subject is required" }); return; }
    if (!cBody.trim()) { toast({ title: "Message body is required" }); return; }
    setSending(true);
    try {
      const r = await apiPost<{ threadId: string }>(base, { action: "send", to: cTo, subject: cSubject.trim(), body: cBody.trim(), attachmentId: cAttach?.id });
      toast({ title: "Message sent", description: `To ${recipients.find((x) => x.id === cTo)?.fullName ?? "recipient"}` });
      setComposeOpen(false);
      setCTo(""); setCSubject(""); setCBody(""); setCAttach(null);
      await loadConversation(r.threadId, false);
      load();
    } catch (e: any) {
      toast({ title: "Message not sent", description: e?.message, variant: "destructive" });
    } finally {
      setSending(false);
    }
  };

  const sendReply = async () => {
    if (!conversation || replying) return;
    if (!rBody.trim()) { toast({ title: "Type a reply first" }); return; }
    setReplying(true);
    try {
      await apiPost(base, { action: "reply", threadId: conversation.id, body: rBody.trim(), attachmentId: rAttach?.id });
      setRBody(""); setRAttach(null);
      await loadConversation(conversation.id, false);
      load(); // refresh the inbox preview + unread counts immediately
    } catch (e: any) {
      toast({ title: "Reply not sent", description: e?.message, variant: "destructive" });
    } finally {
      setReplying(false);
    }
  };

  const deleteThread = async () => {
    if (!conversation) return;
    try {
      await apiPost(base, { action: "delete-thread", threadId: conversation.id });
      toast({ title: "Conversation deleted" });
      setConversation(null);
      setMobilePane("list");
      load();
    } catch (e: any) {
      toast({ title: "Could not delete conversation", description: e?.message, variant: "destructive" });
    }
  };

  const openThread = (t: ThreadItem) => { loadConversation(t.id); };

  const activeThread = conversation ? data?.threads.find((t) => t.id === conversation.id) : undefined;

  // ── render ──
  if (!currentSchoolId) return null;

  const threadList = (
    <div className={cn("flex flex-col min-h-0", mobilePane === "conversation" && "hidden md:flex")}>
      <Card className="flex flex-col min-h-0 overflow-hidden">
        <CardContent className="p-0 flex flex-col min-h-0">
          <div className="px-4 py-3 border-b border-border flex items-center justify-between">
            <div className="text-sm font-semibold">Inbox</div>
            {(data?.threads.length ?? 0) > 0 && (
              <span className="text-xs text-muted-foreground">{data!.threads.length} conversation{data!.threads.length > 1 ? "s" : ""}</span>
            )}
          </div>
          <div className="max-h-[60vh] md:max-h-none md:flex-1 overflow-y-auto nice-scroll">
            {data === null ? (
              <LoadingBlock label="Loading inbox…" />
            ) : data.threads.length === 0 ? (
              <EmptyState icon={<Inbox className="size-5" />} title="No messages yet" hint="Start a conversation with the Compose button." />
            ) : (
              data.threads.map((t) => {
                const active = conversation?.id === t.id;
                return (
                  <button
                    key={t.id}
                    onClick={() => openThread(t)}
                    aria-current={active ? "true" : undefined}
                    aria-label={`Conversation with ${t.counterpart.fullName}: ${t.subject}`}
                    className={cn(
                      "w-full text-left px-4 py-3 flex gap-3 border-b border-border/60 last:border-b-0 min-h-[64px] transition-colors hover:bg-secondary/60 focus-visible:bg-secondary/60 outline-none focus-visible:ring-1 focus-visible:ring-ring",
                      active && "bg-primary/[0.06]",
                      t.unreadCount > 0 && !active && "bg-primary/[0.03]",
                    )}
                  >
                    <SeedAvatar user={t.counterpart} className="size-10 shrink-0" />
                    <div className="min-w-0 flex-1">
                      <div className="flex items-center gap-2">
                        <span className={cn("text-sm truncate", t.unreadCount > 0 ? "font-semibold" : "font-medium")}>{t.counterpart.fullName}</span>
                        <span className="ml-auto text-[10px] text-muted-foreground shrink-0">{relTime(t.lastMessageAt)}</span>
                      </div>
                      <div className="flex items-center gap-1.5 mt-0.5 min-w-0">
                        <span className="text-xs text-muted-foreground truncate">{t.subject}</span>
                        {t.unreadCount > 0 && (
                          <span className="ml-auto shrink-0 min-w-5 h-5 px-1.5 rounded-full bg-primary text-primary-foreground text-[10px] font-bold flex items-center justify-center" aria-label={`${t.unreadCount} unread`}>
                            {t.unreadCount > 9 ? "9+" : t.unreadCount}
                          </span>
                        )}
                      </div>
                      <div className="text-xs text-muted-foreground/80 mt-1 line-clamp-1">
                        {t.lastMessage ? `${t.lastMessage.senderId === meId ? "You: " : ""}${t.lastMessage.body}` : "No messages yet"}
                      </div>
                    </div>
                  </button>
                );
              })
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );

  const conversationPane = (
    <Card className="flex flex-col min-h-0 overflow-hidden">
      <CardContent className="p-0 flex flex-col min-h-0 h-full">
        {convLoading && !conversation ? (
          <LoadingBlock label="Opening conversation…" />
        ) : !conversation ? (
          <div className="flex-1 grid place-items-center">
            <EmptyState icon={<MessageSquare className="size-5" />} title="Select a conversation" hint="Choose a thread from your inbox to read and reply." />
          </div>
        ) : (
          <>
            {/* conversation header */}
            <div className="px-4 py-3 border-b border-border flex items-center gap-3">
              <Button variant="ghost" size="sm" className="md:hidden size-10 md:size-9 p-0" onClick={() => setMobilePane("list")} aria-label="Back to inbox">
                <ArrowLeft className="size-4.5" />
              </Button>
              <SeedAvatar user={conversation.counterpart} className="size-10 shrink-0" />
              <div className="min-w-0 flex-1">
                <div className="text-sm font-semibold truncate">{conversation.counterpart.fullName}</div>
                <div className="flex items-center gap-1.5 min-w-0 mt-0.5">
                  <RoleChip role={conversation.counterpart.role} />
                  <span className="text-xs text-muted-foreground truncate hidden sm:block">{conversation.subject}</span>
                </div>
              </div>
              <Button variant="ghost" size="sm" className="size-10 md:size-9 p-0 text-muted-foreground hover:text-destructive" onClick={() => setDeleteOpen(true)} aria-label="Delete conversation" title="Delete conversation">
                <Trash2 className="size-4" />
              </Button>
            </div>

            {/* messages */}
            <div ref={messagesRef} className="flex-1 min-h-0 overflow-y-auto nice-scroll px-4 py-4 space-y-3">
              {conversation.messages.length === 0 && (
                <p className="text-center text-sm text-muted-foreground py-8">No messages in this thread.</p>
              )}
              {conversation.messages.map((m) => {
                const own = m.senderId === meId;
                return (
                  <div key={m.id} className={cn("flex flex-col max-w-[85%] sm:max-w-[75%]", own ? "ml-auto items-end" : "items-start")}>
                    <div className={cn(
                      "rounded-2xl px-3.5 py-2.5 text-sm whitespace-pre-wrap break-words",
                      own ? "bg-primary text-primary-foreground rounded-br-sm" : "bg-secondary rounded-bl-sm",
                    )}>
                      {m.body}
                      {m.attachmentId && (
                        <div className={cn("mt-2 flex", own ? "justify-end" : "justify-start")}>
                          {/* secondary bubble stays readable on both bubble tints */}
                          <a href={`/api/media/${m.attachmentId}`} download className="inline-flex items-center gap-1.5 rounded-md bg-black/10 dark:bg-white/10 px-2 py-1 text-xs hover:bg-black/20 dark:hover:bg-white/20 transition-colors" aria-label="Download attachment">
                            <Paperclip className="size-3.5 shrink-0" />
                            <span className="max-w-40 truncate">Attachment</span>
                            <Download className="size-3.5 shrink-0" />
                          </a>
                        </div>
                      )}
                    </div>
                    <div className="text-[10px] text-muted-foreground mt-1 px-1">
                      <span className="font-medium">{own ? "You" : m.senderName}</span> · {stamp(m.createdAt)}
                    </div>
                  </div>
                );
              })}
            </div>

            {/* reply box — CI3 msg-compose-area */}
            <div className="border-t border-border p-3 space-y-2">
              {rAttach && (
                <div className="flex items-center gap-2">
                  <Badge variant="secondary" className="max-w-full gap-1 py-1 pl-2">
                    <Paperclip className="size-3" />
                    <span className="max-w-48 truncate font-normal">{rAttach.name}</span>
                    <button onClick={() => setRAttach(null)} className="ml-0.5 rounded-full hover:bg-secondary-foreground/15 p-0.5" aria-label="Remove attachment">
                      <X className="size-3" />
                    </button>
                  </Badge>
                </div>
              )}
              <Textarea
                value={rBody}
                onChange={(e) => setRBody(e.target.value)}
                onKeyDown={(e) => { if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); sendReply(); } }}
                placeholder="Type your reply… (Enter to send, Shift+Enter for a new line)"
                aria-label="Reply message"
                className="min-h-[72px] max-h-48 resize-none text-sm"
              />
              <div className="flex items-center gap-2">
                <input
                  ref={replyFileRef}
                  type="file"
                  accept=".png,.jpg,.jpeg,.webp,.pdf,image/png,image/jpeg,image/webp,application/pdf"
                  className="hidden"
                  aria-label="Attach a file to the reply"
                  onChange={(e) => onPickAttachment(e, "reply")}
                />
                <Button variant="outline" size="sm" className="h-9" onClick={() => replyFileRef.current?.click()} disabled={attaching} aria-label="Attach a file">
                  {attaching ? <Loader2 className="size-4 animate-spin" /> : <Paperclip className="size-4" />}
                  <span className="hidden sm:inline">Attach</span>
                </Button>
                <span className="text-[11px] text-muted-foreground hidden md:block">Max 4MB · PNG, JPEG, WebP, PDF</span>
                <Button size="sm" className="ml-auto h-9" onClick={sendReply} disabled={replying || !rBody.trim()} aria-label="Send reply">
                  {replying ? <Loader2 className="size-4 animate-spin" /> : <Send className="size-4" />}
                  Send
                </Button>
              </div>
            </div>
          </>
        )}
      </CardContent>
    </Card>
  );

  return (
    <div className="p-4 md:p-6 space-y-4">
      <ModuleHeader
        title="Messages"
        description="Internal staff messaging — conversations, replies & attachments between school staff."
        actions={
          <>
            {(data?.unreadTotal ?? 0) > 0 && (
              <Badge className="bg-primary hover:bg-primary text-primary-foreground gap-1">
                <span className="sr-only">Unread messages:</span>
                {data!.unreadTotal} unread
              </Badge>
            )}
            <Button onClick={() => setComposeOpen(true)} aria-label="Compose a new message">
              <Mail className="size-4" /> Compose
            </Button>
          </>
        }
      />

      {/* two-pane mail client — mobile stacks list → conversation */}
      <div className="grid md:grid-cols-[minmax(280px,340px)_1fr] gap-4 md:h-[calc(100vh-13rem)] min-h-[480px]">
        {threadList}
        <div className={cn("flex flex-col min-h-0", mobilePane === "list" && "hidden md:flex")}>
          {conversationPane}
        </div>
      </div>

      {/* compose dialog — CI3 message_new parity */}
      <Dialog open={composeOpen} onOpenChange={(o) => { setComposeOpen(o); if (!o) { setCTo(""); setCSubject(""); setCBody(""); setCAttach(null); } }}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Write a new message</DialogTitle>
            <DialogDescription>Goes straight to the recipient&apos;s inbox — one conversation per person.</DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="msg-recipient">Recipient</Label>
              <Select value={cTo} onValueChange={setCTo}>
                <SelectTrigger id="msg-recipient" aria-label="Select recipient">
                  <SelectValue placeholder="Select a staff member…" />
                </SelectTrigger>
                <SelectContent>
                  {byRole.map(([roleLabel, users]) => (
                    <SelectGroup key={roleLabel}>
                      <SelectLabel>{roleLabel}</SelectLabel>
                      {users.map((u) => (
                        <SelectItem key={u.id} value={u.id}>
                          {u.fullName}{u.title ? ` — ${u.title}` : ""}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  ))}
                  {recipients.length === 0 && <div className="px-3 py-6 text-center text-sm text-muted-foreground">No staff members available yet.</div>}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="msg-subject">Subject</Label>
              <Input id="msg-subject" value={cSubject} onChange={(e) => setCSubject(e.target.value)} maxLength={120} placeholder="e.g. Term 2 scheme of work review" />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="msg-body">Message</Label>
              <Textarea id="msg-body" value={cBody} onChange={(e) => setCBody(e.target.value)} maxLength={5000} placeholder="Write your message…" className="min-h-32" />
            </div>
            <div className="space-y-1.5">
              <Label className="flex items-center gap-1.5">
                <Paperclip className="size-3.5" /> Attachment
                <span className="font-normal text-muted-foreground text-xs">(optional, max 4MB — PNG, JPEG, WebP, PDF)</span>
              </Label>
              <div className="flex items-center gap-2 flex-wrap">
                <input
                  ref={composeFileRef}
                  type="file"
                  accept=".png,.jpg,.jpeg,.webp,.pdf,image/png,image/jpeg,image/webp,application/pdf"
                  className="hidden"
                  aria-label="Choose an attachment"
                  onChange={(e) => onPickAttachment(e, "compose")}
                />
                <Button type="button" variant="outline" size="sm" className="h-9" onClick={() => composeFileRef.current?.click()} disabled={attaching}>
                  {attaching ? <Loader2 className="size-4 animate-spin" /> : <Paperclip className="size-4" />}
                  {cAttach ? "Replace file" : "Choose file"}
                </Button>
                {cAttach && (
                  <Badge variant="secondary" className="max-w-full gap-1 py-1 pl-2">
                    <Paperclip className="size-3" />
                    <span className="max-w-48 truncate font-normal">{cAttach.name}</span>
                    <button type="button" onClick={() => setCAttach(null)} className="ml-0.5 rounded-full hover:bg-secondary-foreground/15 p-0.5" aria-label="Remove attachment">
                      <X className="size-3" />
                    </button>
                  </Badge>
                )}
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setComposeOpen(false)}>Cancel</Button>
            <Button onClick={sendCompose} disabled={sending || !cTo || !cSubject.trim() || !cBody.trim()}>
              {sending ? <Loader2 className="size-4 animate-spin" /> : <Send className="size-4" />}
              Send message
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* delete-thread confirm — CI3 parity */}
      <AlertDialog open={deleteOpen} onOpenChange={setDeleteOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete this conversation?</AlertDialogTitle>
            <AlertDialogDescription>
              {activeThread ? `The thread with ${activeThread.counterpart.fullName} — “${activeThread.subject}” — and all its messages will be removed for both of you. This cannot be undone.` : "The thread and all its messages will be removed for both sides. This cannot be undone."}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction className="bg-destructive text-destructive-foreground hover:bg-destructive/90" onClick={deleteThread}>
              Delete conversation
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}

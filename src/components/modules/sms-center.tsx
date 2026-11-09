"use client";
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Switch } from "@/components/ui/switch";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { StatCard, ModuleHeader, LoadingBlock, EmptyState, FilterBar, FilterSelect, FilterSearch } from "@/components/ui-helpers";
import { fmtDateTime } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { Smartphone, Send, FileText, History, Settings2, Plus, CheckCheck, XCircle, AlertTriangle, Bell, Wallet, Zap, Megaphone, ClipboardCheck } from "lucide-react";

type Template = { id: string; name: string; category: string; body: string; isDefault: boolean };
type Automation = { id: string; name: string; trigger: string; recipients: string; body: string; offsetDays: number; active: boolean; lastRunAt: string | null; sentCount: number };
type Message = { id: string; recipientName: string; recipientPhone: string; body: string; category: string; status: string; sentByName: string | null; sentAt: string; errorReason: string | null };

/* ── Per-event notification gates (iEduPro parity) ──
   One switch per automated trigger, persisted on SmsSetting.evt*.
   Defaults mirror the Prisma schema — present-marking is OFF (volume). */
type SmsEventToggles = {
  evtInvoicePaid: boolean; evtDailyFeesPaid: boolean; evtLearnerBilled: boolean; evtLearnerPresent: boolean;
  evtLearnerAbsent: boolean; evtResultsPublished: boolean; evtPaymentReceipt: boolean; evtOverpaymentCredit: boolean;
};
const EVT_DEFAULTS: SmsEventToggles = {
  evtInvoicePaid: true, evtDailyFeesPaid: true, evtLearnerBilled: true, evtLearnerPresent: false,
  evtLearnerAbsent: true, evtResultsPublished: true, evtPaymentReceipt: true, evtOverpaymentCredit: true,
};
const EVENT_TOGGLES: { field: keyof SmsEventToggles; label: string; desc: string }[] = [
  { field: "evtInvoicePaid", label: "Invoice paid", desc: "Billed invoice paid in full" },
  { field: "evtDailyFeesPaid", label: "Daily fees paid", desc: "Daily fee payment made (lunch, bus…)" },
  { field: "evtLearnerBilled", label: "Learner billed", desc: "Learner billed / invoice issued" },
  { field: "evtLearnerPresent", label: "Learner present", desc: "Learner marked present — high volume, off by default" },
  { field: "evtLearnerAbsent", label: "Learner absent", desc: "Learner marked absent" },
  { field: "evtResultsPublished", label: "Results published", desc: "End-of-term results released" },
  { field: "evtPaymentReceipt", label: "Payment receipt", desc: "Receipt confirmation for any fee payment" },
  { field: "evtOverpaymentCredit", label: "Overpayment credit", desc: "Overpayment banked as prepaid credit" },
];
type SmsData = {
  setting: ({ provider: string; senderId: string; credits: number; autoFeeReminders: boolean; autoAbsenceAlerts: boolean; autoPaymentReceipts: boolean; autoLessonNoteAlerts: boolean } & SmsEventToggles) | null;
  gateway?: { arkesel: boolean; termii: boolean; hubtel?: boolean };
  templates: Template[];
  messages: Message[];
  stats: { total: number; delivered: number; deliveredRate: number; credits: number };
  classes: { id: string; name: string; learners: number }[];
  guardians: number;
  arrearsCount: number;
  automations: Automation[];
};

const TRIGGER_LABELS: Record<string, string> = {
  PAYMENT_RECEIVED: "Payment received",
  INVOICE_ISSUED: "Invoice issued",
  ABSENCE_MARKED: "Absence marked",
  RESULTS_PUBLISHED: "Results published",
  BILL_REMINDER: "Bill reminder",
  LESSON_NOTE_RETURNED: "Lesson note returned",
  LESSON_NOTE_ENDORSED: "Lesson note endorsed",
};

// teacher-directed triggers: recipient is the note's author (Staff record),
// placeholders differ from the guardian-oriented ones
const LESSON_NOTE_TRIGGERS = ["LESSON_NOTE_RETURNED", "LESSON_NOTE_ENDORSED"];
const LESSON_NOTE_BODY: Record<string, string> = {
  LESSON_NOTE_RETURNED: 'Hi {teacher} — your lesson note "{note}" ({class} · {subject}, week {week}) was returned for revision. Reviewer: "{comment}". Please revise and resubmit. — {school}',
  LESSON_NOTE_ENDORSED: 'Good news {teacher} — your lesson note "{note}" ({class} · {subject}, week {week}) was endorsed by {reviewer}. — {school}',
};

const CAT_COLORS: Record<string, string> = {
  FEES: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300",
  ATTENDANCE: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
  PAYMENT: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  RESULTS: "bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300",
  ANNOUNCEMENT: "bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300",
  TRANSPORT: "bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300",
  STAFF: "bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300",
  GENERAL: "bg-secondary text-secondary-foreground",
};

export function SmsCenter() {
  const { currentSchoolId, navTarget } = useStore();
  const [data, setData] = useState<SmsData | null>(null);
  const [tab, setTab] = useState("compose");
  const [tplDialog, setTplDialog] = useState<Template | "new" | null>(null);
  const [category, setCategory] = useState("all");
  const [smsStatus, setSmsStatus] = useState("all");
  const [smsSearch, setSmsSearch] = useState("");
  // client-side status + search on top of the category filter (which reloads from the API)
  const visibleMessages = useMemo(() => (data?.messages ?? []).filter((m) =>
    (smsStatus === "all" || m.status === smsStatus) &&
    (!smsSearch.trim() || `${m.recipientName} ${m.recipientPhone} ${m.body}`.toLowerCase().includes(smsSearch.toLowerCase()))
  ), [data, smsStatus, smsSearch]);

  /* iEduPro nested nav: preselect the Marks SMS tab on deep link */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab === "marks") setTab("marks");
  }

  const load = (c?: string) => {
    if (!currentSchoolId) return;
    apiGet<SmsData>(`/api/school/${currentSchoolId}/ops/sms${c && c !== "all" ? `?category=${c}` : ""}`).then(setData).catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(() => { load(category); }, [currentSchoolId]);

  if (!data) return <LoadingBlock label="Loading SMS centre…" />;

  return (
    <div>
      <ModuleHeader
        title="SMS Centre"
        description="Fee reminders, absence alerts, payment receipts and announcements straight to guardians' phones — with auto-alerts and template placeholders."
        actions={
          <Badge className="gap-1.5 bg-primary hover:bg-primary text-primary-foreground h-8 px-3">
            <Wallet className="size-3.5" /> {data.stats.credits.toLocaleString()} credits
          </Badge>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<Smartphone className="size-4" />} label="Credits left" value={data.stats.credits.toLocaleString()} tone="primary" sub="1 credit = 1 message" />
        <StatCard icon={<CheckCheck className="size-4" />} label="Delivered rate" value={`${data.stats.deliveredRate}%`} tone="gold" sub={`${data.stats.delivered} of ${data.stats.total} all-time`} />
        <StatCard icon={<Send className="size-4" />} label="Guardians reachable" value={data.guardians} sub={`${data.classes.length} classes`} />
        <StatCard icon={<AlertTriangle className="size-4" />} label="Fee arrears" value={data.arrearsCount} tone={data.arrearsCount > 0 ? "danger" : "default"} sub="one tap to remind all" />
      </div>

      <Tabs value={tab} onValueChange={setTab} className="mt-5">
        <TabsList className="flex-wrap h-auto">
          <TabsTrigger value="compose">Compose & send</TabsTrigger>
          <TabsTrigger value="marks" className="gap-1.5"><ClipboardCheck className="size-4" /> Marks SMS</TabsTrigger>
          <TabsTrigger value="automations">Automations ({data.automations.length})</TabsTrigger>
          <TabsTrigger value="templates">Templates ({data.templates.length})</TabsTrigger>
          <TabsTrigger value="history">History</TabsTrigger>
          <TabsTrigger value="noticeboard">Noticeboard</TabsTrigger>
          <TabsTrigger value="settings">Settings</TabsTrigger>
        </TabsList>

        <TabsContent value="compose">
          <ComposeTab data={data} onSent={() => { load(category); setTab("history"); }} />
        </TabsContent>

        {/* ── EXAM-MARKS SMS (iEduPro exam_marks_sms parity) — CI3
            “Year|Term: … Exam Scores of …” blast per learner ── */}
        <TabsContent value="marks">
          <MarksSmsTab data={data} onSent={() => { load(category); setTab("history"); }} />
        </TabsContent>

        {/* ── NOTICEBOARD (iEduPro noticeboard parity) — school notices for
            parents/staff, shown in the parent & student portals ── */}
        <TabsContent value="noticeboard">
          <NoticeboardTab />
        </TabsContent>

        <TabsContent value="automations">
          <AutomationsTab data={data} onRefresh={() => load(category)} />
        </TabsContent>

        <TabsContent value="templates">
          <div className="flex justify-end mb-3">
            <Button size="sm" className="gap-1.5" onClick={() => setTplDialog("new")}><Plus className="size-4" /> New template</Button>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            {data.templates.map((t) => (
              <Card key={t.id} className="flex flex-col">
                <CardHeader className="pb-2 flex-row items-center justify-between space-y-0">
                  <CardTitle className="text-sm flex items-center gap-2"><FileText className="size-4 text-primary" /> {t.name}</CardTitle>
                  <span className={`text-[10px] px-2 py-0.5 rounded-full font-semibold ${CAT_COLORS[t.category] ?? CAT_COLORS.GENERAL}`}>{t.category}</span>
                </CardHeader>
                <CardContent className="flex-1 space-y-3">
                  <p className="text-xs text-muted-foreground leading-relaxed border-l-2 border-primary/30 pl-2.5 italic">{t.body}</p>
                  <div className="flex gap-2 mt-auto">
                    <Button size="sm" variant="outline" className="h-7 flex-1" onClick={() => setTplDialog(t)}>Edit</Button>
                    <Button size="sm" variant="ghost" className="h-7 text-destructive gap-1"
                      onClick={async () => { await apiPost(`/api/school/${currentSchoolId}/ops/sms`, { action: "delete-template", id: t.id }); toast({ title: "Template deleted" }); load(category); }}>
                      <XCircle className="size-3.5" /> Delete
                    </Button>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </TabsContent>

        <TabsContent value="history">
          <div className="space-y-3">
            <FilterBar active={smsStatus !== "all" || !!smsSearch.trim()} onClear={() => { setSmsStatus("all"); setSmsSearch(""); }} count={visibleMessages.length} total={data.messages.length} label="Message filters">
              <FilterSearch value={smsSearch} onChange={setSmsSearch} placeholder="Recipient, phone, text…" />
              <FilterSelect label="Status" value={smsStatus} onChange={setSmsStatus} options={[
                { value: "all", label: "All statuses" }, { value: "DELIVERED", label: "Delivered" }, { value: "SENT", label: "Sent" }, { value: "FAILED", label: "Failed" },
              ]} />
            </FilterBar>
            <Card>
            <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
              <CardTitle className="text-base flex items-center gap-2"><History className="size-4" /> Message log</CardTitle>
              <Select value={category} onValueChange={(v) => { setCategory(v); load(v); }}>
                <SelectTrigger className="w-44 h-8"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All categories</SelectItem>
                  {Object.keys(CAT_COLORS).map((c) => <SelectItem key={c} value={c}>{c.charAt(0) + c.slice(1).toLowerCase()}</SelectItem>)}
                </SelectContent>
              </Select>
            </CardHeader>
            <CardContent className="p-0">
              <div className="max-h-[520px] overflow-y-auto nice-scroll">
                {visibleMessages.length === 0 && <EmptyState title={smsStatus !== "all" || smsSearch ? "No messages match" : "No messages yet"} hint={smsStatus !== "all" || smsSearch ? "Adjust the status or search filter." : "Compose your first broadcast."} />}
                {visibleMessages.map((m) => (
                  <div key={m.id} className="px-4 py-3 border-b last:border-0 hover:bg-secondary/30">
                    <div className="flex items-center justify-between gap-2 flex-wrap">
                      <div className="flex items-center gap-2 min-w-0">
                        <span className={`text-[10px] px-2 py-0.5 rounded-full font-semibold shrink-0 ${CAT_COLORS[m.category] ?? CAT_COLORS.GENERAL}`}>{m.category}</span>
                        <span className="font-medium text-sm truncate">{m.recipientName}</span>
                        <span className="text-xs text-muted-foreground font-mono">{m.recipientPhone}</span>
                      </div>
                      <div className="flex items-center gap-2 text-xs text-muted-foreground">
                        {m.status === "DELIVERED" ? <Badge className="gap-1 bg-emerald-600 text-[10px]"><CheckCheck className="size-3" /> Delivered</Badge>
                          : m.status === "SENT" ? <Badge variant="secondary" className="text-[10px]">Sent</Badge>
                          : <Badge variant="destructive" className="gap-1 text-[10px]"><XCircle className="size-3" /> Failed</Badge>}
                        <span className="hidden sm:inline">{fmtDateTime(m.sentAt)}</span>
                      </div>
                    </div>
                    <p className="text-sm mt-1.5 text-muted-foreground">{m.body}</p>
                    {m.errorReason && <p className="text-xs text-destructive mt-1">{m.errorReason}</p>}
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
          </div>
        </TabsContent>

        <TabsContent value="settings">
          <SettingsTab data={data} onSaved={() => load(category)} />
        </TabsContent>
      </Tabs>

      <TemplateDialog template={tplDialog} onOpenChange={(o) => !o && setTplDialog(null)} onDone={() => { setTplDialog(null); load(category); }} />
    </div>
  );
}

function ComposeTab({ data, onSent }: { data: SmsData; onSent: () => void }) {
  const { currentSchoolId } = useStore();
  const [audience, setAudience] = useState("ALL");
  const [templateId, setTemplateId] = useState("");
  const [body, setBody] = useState("");
  const [busy, setBusy] = useState(false);
  const [confirm, setConfirm] = useState(false);

  const recipientCount = useMemo(() => {
    if (audience === "ALL") return data.guardians;
    if (audience === "ARREARS") return data.arrearsCount;
    if (audience.startsWith("CLASS:")) return data.classes.find((c) => c.id === audience.slice(6))?.learners ?? 0;
    return 0;
  }, [audience, data]);

  useEffect(() => {
    if (templateId) {
      const t = data.templates.find((x) => x.id === templateId);
      if (t) setBody(t.body);
    }
  }, [templateId]);

  async function send() {
    if (!body.trim()) { toast({ title: "Write a message first" }); return; }
    setBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/ops/sms`, { action: "send", audience, body, templateId: templateId || undefined });
      toast({ title: `Sent to ${res.sent} guardian${res.sent === 1 ? "" : "s"}`, description: res.failed ? `${res.failed} failed — check history.` : "All delivered to the gateway." });
      setBody(""); setTemplateId(""); setConfirm(false);
      onSent();
    } catch (e: any) { toast({ title: "Send failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Card className="lg:col-span-2">
        <CardHeader className="pb-3"><CardTitle className="text-base">New broadcast</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <div className="grid sm:grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>Audience</Label>
              <Select value={audience} onValueChange={setAudience}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="ALL">All guardians ({data.guardians})</SelectItem>
                  <SelectItem value="ARREARS">Guardians with arrears ({data.arrearsCount})</SelectItem>
                  {data.classes.map((c) => <SelectItem key={c.id} value={`CLASS:${c.id}`}>{c.name} ({c.learners})</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Start from template</Label>
              <Select value={templateId} onValueChange={setTemplateId}>
                <SelectTrigger><SelectValue placeholder="Blank message" /></SelectTrigger>
                <SelectContent>
                  {data.templates.map((t) => <SelectItem key={t.id} value={t.id}>{t.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Message</Label>
            <Textarea rows={4} value={body} onChange={(e) => setBody(e.target.value)} placeholder="Dear Parent, {student}'s fee balance is {balance}…" />
            <div className="flex justify-between text-xs text-muted-foreground">
              <span>Placeholders: {"{student} {class} {balance} {date}"} — filled per learner</span>
              <span className={body.length > 320 ? "text-destructive" : ""}>{body.length}/320 · {Math.max(1, Math.ceil(body.length / 160))} credit{Math.ceil(body.length / 160) > 1 ? "s" : ""}/msg</span>
            </div>
          </div>
          <div className="flex items-center justify-between border-t pt-3">
            <div className="text-sm">
              <span className="font-semibold">{recipientCount}</span> recipient{recipientCount === 1 ? "" : "s"} ·
              <span className="text-muted-foreground"> needs {recipientCount * Math.max(1, Math.ceil(body.length / 160) || 1)} credits</span>
            </div>
            <Button className="gap-1.5" disabled={!body.trim() || recipientCount === 0} onClick={() => setConfirm(true)}><Send className="size-4" /> Send broadcast</Button>
          </div>
        </CardContent>
      </Card>
      <div className="space-y-4">
        <Card className="bg-primary/5 border-primary/20">
          <CardContent className="pt-4 space-y-3">
            <div className="font-semibold text-sm flex items-center gap-1.5"><Zap className="size-4 text-primary" /> Automatic alerts</div>
            {[
              { on: data.setting?.autoPaymentReceipts, label: "Payment receipts", desc: "Every fee & tuck payment" },
              { on: data.setting?.autoAbsenceAlerts, label: "Absence alerts", desc: "When a learner is marked absent" },
              { on: data.setting?.autoFeeReminders, label: "Fee reminders", desc: "Scheduled arrears chasing" },
              { on: data.setting?.autoLessonNoteAlerts, label: "Lesson-note alerts", desc: "Teachers notified when notes are returned / endorsed" },
            ].map((x, i) => (
              <div key={i} className="flex items-center justify-between gap-2 text-sm">
                <div>
                  <div className="font-medium">{x.label}</div>
                  <div className="text-xs text-muted-foreground">{x.desc}</div>
                </div>
                <Badge variant={x.on ? "default" : "outline"} className="shrink-0">{x.on ? "ON" : "OFF"}</Badge>
              </div>
            ))}
            <p className="text-xs text-muted-foreground">Change these in Settings.</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-4 text-xs text-muted-foreground leading-relaxed">
            <div className="font-semibold text-sm text-foreground mb-1.5 flex items-center gap-1.5"><Smartphone className="size-4 text-primary" /> Gateway</div>
            Provider <b className="text-foreground">{data.setting?.provider ?? "—"}</b> · Sender ID <b className="text-foreground">{data.setting?.senderId ?? "—"}</b>.
            {data.setting?.provider === "SIMULATED"
              ? " Demo mode — messages are logged with realistic statuses instead of hitting a real gateway. Switch to Arkesel, Termii or Hubtel in Settings for live delivery."
              : data.setting?.provider === "ARKESEL"
                ? (data.gateway?.arkesel
                    ? " Arkesel is configured — messages go out live."
                    : " Arkesel selected but no API key on the server (SMS_ARKESEL_API_KEY) — messages will be recorded as FAILED without using credits.")
                : data.setting?.provider === "TERMII"
                  ? (data.gateway?.termii
                      ? " Termii is configured — messages go out live."
                      : " Termii selected but no API key on the server (SMS_TERMII_API_KEY) — messages will be recorded as FAILED without using credits.")
                  : data.setting?.provider === "HUBTEL"
                    ? (data.gateway?.hubtel
                        ? " Hubtel is configured — messages go out live via smsc.hubtel.com."
                        : " Hubtel selected but no keys on the server (SMS_HUBTEL_CLIENT_ID / SMS_HUBTEL_CLIENT_SECRET) — messages will be recorded as FAILED without using credits.")
                    : " Live gateway — configure the API key during deployment."}
          </CardContent>
        </Card>
      </div>

      <Dialog open={confirm} onOpenChange={setConfirm}>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle>Send to {recipientCount} guardians?</DialogTitle>
            <DialogDescription>This uses {recipientCount * Math.max(1, Math.ceil(body.length / 160) || 1)} of your {data.stats.credits} credits.</DialogDescription>
          </DialogHeader>
          <div className="rounded-lg bg-secondary/60 p-3 text-sm max-h-40 overflow-y-auto nice-scroll">{body}</div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setConfirm(false)}>Cancel</Button>
            <Button onClick={send} disabled={busy} className="gap-1.5">{busy ? "Sending…" : <><Send className="size-4" /> Send now</>}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

function SettingsTab({ data, onSaved }: { data: SmsData; onSaved: () => void }) {
  const { currentSchoolId } = useStore();
  const [provider, setProvider] = useState(data.setting?.provider ?? "SIMULATED");
  const [senderId, setSenderId] = useState(data.setting?.senderId ?? "NYANSAPO");
  const [fee, setFee] = useState(!!data.setting?.autoFeeReminders);
  const [absence, setAbsence] = useState(!!data.setting?.autoAbsenceAlerts);
  const [receipts, setReceipts] = useState(!!data.setting?.autoPaymentReceipts);
  const [lessonNotes, setLessonNotes] = useState(data.setting?.autoLessonNoteAlerts !== false);
  const [evt, setEvt] = useState<SmsEventToggles>(() => ({
    evtInvoicePaid: data.setting?.evtInvoicePaid ?? EVT_DEFAULTS.evtInvoicePaid,
    evtDailyFeesPaid: data.setting?.evtDailyFeesPaid ?? EVT_DEFAULTS.evtDailyFeesPaid,
    evtLearnerBilled: data.setting?.evtLearnerBilled ?? EVT_DEFAULTS.evtLearnerBilled,
    evtLearnerPresent: data.setting?.evtLearnerPresent ?? EVT_DEFAULTS.evtLearnerPresent,
    evtLearnerAbsent: data.setting?.evtLearnerAbsent ?? EVT_DEFAULTS.evtLearnerAbsent,
    evtResultsPublished: data.setting?.evtResultsPublished ?? EVT_DEFAULTS.evtResultsPublished,
    evtPaymentReceipt: data.setting?.evtPaymentReceipt ?? EVT_DEFAULTS.evtPaymentReceipt,
    evtOverpaymentCredit: data.setting?.evtOverpaymentCredit ?? EVT_DEFAULTS.evtOverpaymentCredit,
  }));
  const [topup, setTopup] = useState("500");
  const [busy, setBusy] = useState(false);

  async function save() {
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/sms`, { action: "save-settings", provider, senderId, autoFeeReminders: fee, autoAbsenceAlerts: absence, autoPaymentReceipts: receipts, autoLessonNoteAlerts: lessonNotes, ...evt });
      toast({ title: "Settings saved" });
      onSaved();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  async function doTopup() {
    setBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/ops/sms`, { action: "topup", credits: Number(topup) });
      toast({ title: "Credits topped up", description: `Balance: ${res.credits.toLocaleString()} credits.` });
      onSaved();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Settings2 className="size-4" /> Gateway & sender</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-1.5">
            <Label>Provider</Label>
            <Select value={provider} onValueChange={setProvider}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="SIMULATED">Demo (simulated delivery)</SelectItem>
                <SelectItem value="ARKESEL">Arkesel (Ghana)</SelectItem>
                <SelectItem value="TERMII">Termii (Africa)</SelectItem>
                <SelectItem value="HUBTEL">Hubtel (Ghana)</SelectItem>
              </SelectContent>
            </Select>
            <p className="text-xs text-muted-foreground">
              {provider === "ARKESEL" ? (data.gateway?.arkesel ? "Arkesel API key detected on the server — live delivery active." : "No Arkesel API key on the server yet — set SMS_ARKESEL_API_KEY during deployment; sends stay free until then.")
                : provider === "TERMII" ? (data.gateway?.termii ? "Termii API key detected on the server — live delivery active." : "No Termii API key on the server yet — set SMS_TERMII_API_KEY during deployment; sends stay free until then.")
                : provider === "HUBTEL" ? (data.gateway?.hubtel ? "Hubtel SMS keys detected on the server — live delivery via smsc.hubtel.com." : "No Hubtel keys on the server yet — set SMS_HUBTEL_CLIENT_ID / SMS_HUBTEL_CLIENT_SECRET (Messaging → Manage → Programmable SMS); sends stay free until then.")
                : "Demo mode records messages locally with realistic statuses — no real SMS is sent."}
            </p>
          </div>
          <div className="space-y-1.5">
            <Label>Sender ID</Label>
            <Input value={senderId} onChange={(e) => setSenderId(e.target.value.toUpperCase().slice(0, 11))} placeholder="GEA-MADINA" />
            <p className="text-xs text-muted-foreground">Shown to parents as the sender — max 11 characters, register it with your provider.</p>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base">Automatic alerts</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          {[
            { label: "Payment receipts", desc: "SMS receipt for every fee and daily-service payment", val: receipts, set: setReceipts },
            { label: "Absence alerts", desc: "Notify guardians when a learner is marked absent", val: absence, set: setAbsence },
            { label: "Fee reminders", desc: "Include guardians in scheduled arrears reminders", val: fee, set: setFee },
            { label: "Lesson-note alerts", desc: "Text the teacher when their GES note is returned for revision (or endorsed, with a rule)", val: lessonNotes, set: setLessonNotes },
          ].map((x) => (
            <div key={x.label} className="flex items-center justify-between gap-3">
              <div>
                <div className="text-sm font-medium">{x.label}</div>
                <div className="text-xs text-muted-foreground">{x.desc}</div>
              </div>
              <Switch checked={x.val} onCheckedChange={x.set} />
            </div>
          ))}
        </CardContent>
      </Card>
      <Card className="lg:col-span-2">
        <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><Bell className="size-4" /> Per-event notifications</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <p className="text-xs text-muted-foreground">One master switch per automated event — a trigger only texts guardians when its switch is on, keeping credit spend predictable.</p>
          <div className="grid gap-4 sm:grid-cols-2">
            {EVENT_TOGGLES.map((e) => (
              <div key={e.field} className="flex items-center justify-between gap-3">
                <div>
                  <div className="text-sm font-medium">{e.label}</div>
                  <div className="text-xs text-muted-foreground">{e.desc}</div>
                </div>
                <Switch checked={evt[e.field]} onCheckedChange={(v) => setEvt({ ...evt, [e.field]: v } as SmsEventToggles)} />
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base">Top up credits</CardTitle></CardHeader>
        <CardContent className="flex items-end gap-3">
          <div className="space-y-1.5 flex-1">
            <Label>Credits to add</Label>
            <Input type="number" value={topup} onChange={(e) => setTopup(e.target.value)} />
          </div>
          <Button variant="outline" onClick={doTopup} disabled={busy} className="gap-1.5"><Wallet className="size-4" /> Top up</Button>
          <p className="text-xs text-muted-foreground">Current balance: <b>{data.stats.credits.toLocaleString()}</b></p>
        </CardContent>
      </Card>
      <div className="flex items-end">
        <Button onClick={save} disabled={busy} className="gap-1.5">{busy ? "Saving…" : <><CheckCheck className="size-4" /> Save settings</>}</Button>
      </div>
    </div>
  );
}

function TemplateDialog({ template, onOpenChange, onDone }: { template: Template | "new" | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [name, setName] = useState("");
  const [category, setCategory] = useState("GENERAL");
  const [body, setBody] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (template && template !== "new") { setName(template.name); setCategory(template.category); setBody(template.body); }
    else if (template === "new") { setName(""); setCategory("GENERAL"); setBody(""); }
  }, [template]);

  async function submit() {
    if (!name || !body) { toast({ title: "Name and body are required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/sms`, { action: "save-template", id: template && template !== "new" ? template.id : undefined, name, category, body });
      toast({ title: template === "new" ? "Template created" : "Template updated" });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!template} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{template === "new" ? "New template" : "Edit template"}</DialogTitle>
          <DialogDescription>Use placeholders that fill per learner: {"{student} {class} {balance} {amount} {date} {school}"}</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5"><Label>Name *</Label><Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Fee reminder" /></div>
            <div className="space-y-1.5">
              <Label>Category</Label>
              <Select value={category} onValueChange={setCategory}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {Object.keys(CAT_COLORS).map((c) => <SelectItem key={c} value={c}>{c.charAt(0) + c.slice(1).toLowerCase()}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Body *</Label>
            <Textarea rows={4} value={body} onChange={(e) => setBody(e.target.value)} placeholder="Dear Parent, {student}'s fee balance is {balance}…" />
            <div className="text-xs text-muted-foreground">{body.length}/160 characters per credit</div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : "Save template"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── AUTOMATIONS (iedupro parity) — event-driven SMS rules ── */
function AutomationsTab({ data, onRefresh }: { data: SmsData; onRefresh: () => void }) {
  const { currentSchoolId } = useStore();
  const [edit, setEdit] = useState<Automation | "new" | null>(null);
  const [runningId, setRunningId] = useState<string | null>(null);

  async function toggle(a: Automation) {
    await apiPost(`/api/school/${currentSchoolId}/ops/sms`, { action: "toggle-automation", id: a.id });
    toast({ title: a.active ? "Rule paused" : "Rule activated" });
    onRefresh();
  }

  async function remove(a: Automation) {
    await apiPost(`/api/school/${currentSchoolId}/ops/sms`, { action: "delete-automation", id: a.id });
    toast({ title: "Rule deleted" });
    onRefresh();
  }

  async function run(a: Automation) {
    setRunningId(a.id);
    try {
      const res = await apiPost<{ sent: number; failed: number; message?: string }>(`/api/school/${currentSchoolId}/ops/sms`, { action: "run-automation", id: a.id });
      toast({ title: res.message ?? `Reminders sent — ${res.sent} delivered, ${res.failed} failed` });
      onRefresh();
    } catch (e: any) { toast({ title: "Run failed", description: e.message, variant: "destructive" }); } finally { setRunningId(null); }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <p className="text-sm text-muted-foreground max-w-2xl">
          Event-driven messaging — when a payment is received, an invoice is issued, a learner is marked absent or term bills fall due,
          the active rule&apos;s message (with placeholders like {"{student}"} and {"{balance}"}) is sent to guardians automatically.
        </p>
        <Button size="sm" className="gap-1.5 shrink-0" onClick={() => setEdit("new")}><Plus className="size-4" /> New rule</Button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {data.automations.map((a) => (
          <Card key={a.id} className={a.active ? "" : "opacity-60"}>
            <CardHeader className="pb-2 flex-row items-center justify-between space-y-0">
              <CardTitle className="text-sm flex items-center gap-2"><Zap className={`size-4 ${a.active ? "text-amber-500" : "text-muted-foreground"}`} /> {a.name}</CardTitle>
              <Badge variant="outline" className="text-[10px]">{TRIGGER_LABELS[a.trigger] ?? a.trigger}</Badge>
            </CardHeader>
            <CardContent className="space-y-3">
              <p className="text-xs text-muted-foreground leading-relaxed border-l-2 border-primary/30 pl-2.5 italic">{a.body}</p>
              <div className="flex flex-wrap gap-1.5">
                <Badge variant="secondary" className="text-[10px]">to {a.recipients === "GUARDIANS" ? "guardians" : a.recipients === "TEACHERS" ? "teachers" : "everyone"}</Badge>
                {a.trigger === "BILL_REMINDER" && <Badge variant="secondary" className="text-[10px]">due ≤ {a.offsetDays}d ahead</Badge>}
                <Badge variant="secondary" className="text-[10px]">{a.sentCount} sent</Badge>
                {a.lastRunAt && <Badge variant="outline" className="text-[10px]">last {fmtDateTime(a.lastRunAt)}</Badge>}
              </div>
              <div className="flex flex-wrap gap-2 pt-1">
                {a.trigger === "BILL_REMINDER" && (
                  <Button size="sm" className="h-7 gap-1" disabled={runningId === a.id} onClick={() => run(a)}>
                    <Send className="size-3.5" /> {runningId === a.id ? "Sending…" : "Run now"}
                  </Button>
                )}
                <Button size="sm" variant="outline" className="h-7" onClick={() => setEdit(a)}>Edit</Button>
                <Button size="sm" variant="ghost" className="h-7 gap-1" onClick={() => toggle(a)}>{a.active ? "Pause" : "Activate"}</Button>
                <Button size="sm" variant="ghost" className="h-7 text-destructive gap-1" onClick={() => remove(a)}><XCircle className="size-3.5" /></Button>
              </div>
            </CardContent>
          </Card>
        ))}
        {data.automations.length === 0 && (
          <Card className="md:col-span-2"><CardContent><EmptyState icon={<Zap className="size-5" />} title="No automation rules yet" hint="e.g. 'Fee reminder' — when bills fall due within 3 days, text guardians the balance." /></CardContent></Card>
        )}
      </div>

      <AutomationDialog rule={edit} onOpenChange={(o) => !o && setEdit(null)} onDone={() => { setEdit(null); onRefresh(); }} />
    </div>
  );
}

function AutomationDialog({ rule, onOpenChange, onDone }: { rule: Automation | "new" | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const isNew = rule === "new";
  const [name, setName] = useState("");
  const [trigger, setTrigger] = useState("BILL_REMINDER");
  const [recipients, setRecipients] = useState("GUARDIANS");
  const [body, setBody] = useState("");
  const [offsetDays, setOffsetDays] = useState("3");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (rule && rule !== "new") {
      setName(rule.name); setTrigger(rule.trigger); setRecipients(rule.recipients); setBody(rule.body); setOffsetDays(String(rule.offsetDays));
    } else { setName(""); setTrigger("BILL_REMINDER"); setRecipients("GUARDIANS"); setBody("Friendly reminder: {student}'s term balance is {balance}. Kindly settle at the office or via MoMo. Thank you — {school}."); setOffsetDays("3"); }
  }, [rule]);

  // switching to a lesson-note trigger: teacher-directed defaults (recipients + body)
  function onTriggerChange(v: string) {
    setTrigger(v);
    if (LESSON_NOTE_TRIGGERS.includes(v)) {
      setRecipients("TEACHERS");
      if (!body.trim() || Object.values(LESSON_NOTE_BODY).includes(body) || body.startsWith("Friendly reminder:")) setBody(LESSON_NOTE_BODY[v]);
    }
  }

  async function submit() {
    if (!name.trim() || !body.trim()) { toast({ title: "Rule name and message are required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/sms`, {
        action: "save-automation", id: isNew ? undefined : (rule as Automation).id,
        name, trigger, recipients, body, offsetDays: Number(offsetDays) || 0,
      });
      toast({ title: isNew ? "Rule created" : "Rule updated" });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!rule} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Zap className="size-4 text-amber-500" /> {isNew ? "New automation rule" : "Edit automation rule"}</DialogTitle>
          <DialogDescription>
            {LESSON_NOTE_TRIGGERS.includes(trigger)
              ? <>Sent to the note's teacher automatically. Placeholders: {"{teacher}"} {"{note}"} {"{class}"} {"{subject}"} {"{week}"} {"{reviewer}"} {"{comment}"} {"{school}"}</>
              : <>Message sent automatically when the event fires. Placeholders: {"{student}"} {"{amount}"} {"{balance}"} {"{receipt}"} {"{term}"} {"{date}"} {"{school}"} {"{class}"}</>}
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="space-y-1.5"><Label>Rule name</Label><Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Fee reminder — 3 days before due" /></div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Trigger</Label>
              <Select value={trigger} onValueChange={onTriggerChange}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {Object.entries(TRIGGER_LABELS).map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Recipients</Label>
              <Select value={recipients} onValueChange={setRecipients}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="GUARDIANS">Guardians</SelectItem>
                  <SelectItem value="TEACHERS">Teachers</SelectItem>
                  <SelectItem value="ALL">Everyone</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          {trigger === "BILL_REMINDER" && (
            <div className="space-y-1.5">
              <Label>Days before due date</Label>
              <Input type="number" value={offsetDays} onChange={(e) => setOffsetDays(e.target.value)} placeholder="3" />
              <p className="text-xs text-muted-foreground">Reminders cover unpaid invoices due within this many days (and anything overdue). Run manually from the rule card.</p>
            </div>
          )}
          {trigger === "LESSON_NOTE_ENDORSED" && (
            <p className="text-xs text-muted-foreground rounded-md bg-secondary/60 px-3 py-2">Endorsement texts are opt-in — they only send while this rule is active. Returns-for-revision text by default even without a rule.</p>
          )}
          <div className="space-y-1.5">
            <Label>Message</Label>
            <Textarea rows={4} value={body} onChange={(e) => setBody(e.target.value)} />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : isNew ? "Create rule" : "Save rule"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── Noticeboard tab (iEduPro noticeboard parity): school notices for
    parents & staff — surfaced in the parent and student portals ── */
type NoticeRow = { id: string; title: string; body: string; audience: string; category: string; priority: string; postedByName: string; postedAt: string; source: string };

function NoticeboardTab() {
  const { currentSchoolId } = useStore();
  const [notices, setNotices] = useState<NoticeRow[] | null>(null);
  const [title, setTitle] = useState("");
  const [bodyText, setBodyText] = useState("");
  const [audience, setAudience] = useState("PARENTS");
  const [priority, setPriority] = useState("NORMAL");
  const [busy, setBusy] = useState(false);

  const load = () => {
    apiGet<any>(`/api/school/${currentSchoolId}/announcements`)
      .then((d) => setNotices(d.announcements ?? []))
      .catch(() => setNotices([]));
  };
  useEffect(() => { load(); }, [currentSchoolId]);  

  async function post() {
    if (!title.trim() || !bodyText.trim()) { toast({ title: "Title and body are required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/announcements`, { action: "create", title, body: bodyText, audience, priority });
      toast({ title: "Notice posted", description: "It appears in the parent & student portals straight away." });
      setTitle(""); setBodyText(""); setPriority("NORMAL");
      load();
    } catch (e: any) { toast({ title: "Could not post", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <Card className="lg:col-span-2">
        <CardHeader className="pb-3"><CardTitle className="text-base">Notices</CardTitle></CardHeader>
        <CardContent className="space-y-2 max-h-[560px] overflow-y-auto nice-scroll">
          {notices === null ? <LoadingBlock label="Loading…" /> : notices.length ? notices.map((n) => (
            <div key={n.id} className="rounded-xl border p-3">
              <div className="flex items-start justify-between gap-2">
                <div className="font-medium">{n.title}</div>
                <div className="flex gap-1 shrink-0">
                  {n.priority === "HIGH" && <Badge className="bg-red-600 text-[9px]">urgent</Badge>}
                  <Badge variant="outline" className="text-[9px]">{n.audience}</Badge>
                </div>
              </div>
              <p className="text-sm text-muted-foreground mt-1">{n.body}</p>
              <div className="text-[11px] text-muted-foreground mt-2">{n.source} · {n.postedByName} · {new Date(n.postedAt).toLocaleString("en-GB")}</div>
            </div>
          )) : <EmptyState title="No notices yet" hint="Post the first one — it shows in the parent & student portals." />}
        </CardContent>
      </Card>
      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base">Post a notice</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          <div className="space-y-1.5">
            <Label className="text-xs">Title</Label>
            <Input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="e.g. PTA meeting — Friday 4pm" />
          </div>
          <div className="space-y-1.5">
            <Label className="text-xs">Body</Label>
            <Input value={bodyText} onChange={(e) => setBodyText(e.target.value)} placeholder="What should families know?" />
          </div>
          <div className="grid grid-cols-2 gap-2">
            <div className="space-y-1.5">
              <Label className="text-xs">Audience</Label>
              <Select value={audience} onValueChange={setAudience}>
                <SelectTrigger className="h-8"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="PARENTS">Parents</SelectItem>
                  <SelectItem value="SCHOOLS">Staff & schools</SelectItem>
                  <SelectItem value="ZONAL">Zone</SelectItem>
                  <SelectItem value="DISTRICT">District</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label className="text-xs">Priority</Label>
              <Select value={priority} onValueChange={setPriority}>
                <SelectTrigger className="h-8"><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="NORMAL">Normal</SelectItem>
                  <SelectItem value="HIGH">High</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <Button className="w-full gap-1.5" onClick={post} disabled={busy}>{busy ? "Posting…" : <><Megaphone className="size-4" /> Post notice</>}</Button>
          <p className="text-[11px] text-muted-foreground">Notices appear instantly in the Parent Portal and My Learning.</p>
        </CardContent>
      </Card>
    </div>
  );
}

/* ═══════════════════════════════════════════════════════════════
   EXAM-MARKS SMS (CI3 exam_marks_sms parity) — pick class, exam and
   receiver, preview the exact CI3 message per learner, then blast:
     Year|Term: {year}|{term} Exam Scores of {learner}:
     {Subject} {total}% {grade} {remark}, …
   Grades come from the WAEC raw-score table; delivery uses the same
   gateway + credits flow as every other broadcast.                     */
type MarksSmsPreview = {
  assessments: { id: string; label: string }[];
  yearName: string;
  termName: string;
  receiver: string;
  previews: { studentId: string; student: string; guardianName: string; phone: string | null; message: string }[];
  withPhone: number;
  withoutPhone: number;
};

function MarksSmsTab({ data, onSent }: { data: SmsData; onSent: () => void }) {
  const { currentSchoolId } = useStore();
  const [classId, setClassId] = useState<string | null>(null);
  const [assessmentId, setAssessmentId] = useState("all");
  const [receiver, setReceiver] = useState("guardian");
  const [examList, setExamList] = useState<{ id: string; label: string }[] | null>(null);
  const [preview, setPreview] = useState<MarksSmsPreview | null>(null);
  const [busy, setBusy] = useState(false);
  const [confirm, setConfirm] = useState(false);

  useEffect(() => {
    if (!currentSchoolId || !classId) { setExamList(null); setPreview(null); return; }
    setAssessmentId("all");
    setPreview(null);
    apiGet<any>(`/api/school/${currentSchoolId}/ops/academics?tab=marks-sms&classroomId=${classId}`)
      .then((d) => setExamList(d.assessments ?? []))
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  }, [currentSchoolId, classId]);

  async function runPreview() {
    if (!currentSchoolId || !classId) { toast({ title: "Choose a class first" }); return; }
    setBusy(true);
    try {
      const qs = `tab=marks-sms&classroomId=${classId}&receiver=${receiver}${assessmentId && assessmentId !== "all" ? `&assessmentId=${assessmentId}` : ""}`;
      const d = await apiGet<MarksSmsPreview>(`/api/school/${currentSchoolId}/ops/academics?${qs}`);
      setPreview(d);
      toast({ title: `${d.previews.length} messages drafted`, description: `${d.withPhone} with a phone on file · ${d.withoutPhone} would be skipped.` });
    } catch (e: any) {
      toast({ title: "Preview failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  async function send() {
    if (!currentSchoolId || !classId) return;
    setBusy(true);
    try {
      const res = await apiPost<{ sent: number; failed: number; skipped: number }>(`/api/school/${currentSchoolId}/ops/academics`, {
        action: "marks-sms.send", classroomId: classId,
        assessmentId: assessmentId && assessmentId !== "all" ? assessmentId : undefined,
        receiver,
      });
      toast({
        title: `Marks SMS sent to ${res.sent} guardian${res.sent === 1 ? "" : "s"}`,
        description: [res.skipped ? `${res.skipped} skipped (no phone)` : null, res.failed ? `${res.failed} failed — check history` : null].filter(Boolean).join(" · ") || "All delivered to the gateway.",
      });
      setConfirm(false);
      setPreview(null);
      onSent();
    } catch (e: any) {
      toast({ title: "Send failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><ClipboardCheck className="size-4" /> Exam-marks blast</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <div className="grid sm:grid-cols-3 gap-3">
            <div className="space-y-1.5">
              <Label>Class</Label>
              <Select value={classId ?? ""} onValueChange={setClassId}>
                <SelectTrigger><SelectValue placeholder="Select class" /></SelectTrigger>
                <SelectContent>{data.classes.map((c) => <SelectItem key={c.id} value={c.id}>{c.name} ({c.learners})</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Exam / assessment</Label>
              <Select value={assessmentId} onValueChange={setAssessmentId} disabled={!classId || !examList?.length}>
                <SelectTrigger><SelectValue placeholder={classId ? "Select exam" : "Pick a class first"} /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All assessments — term totals (30/70)</SelectItem>
                  {(examList ?? []).map((a) => <SelectItem key={a.id} value={a.id}>{a.label}</SelectItem>)}
                </SelectContent>
              </Select>
              {examList && examList.length === 0 && <p className="text-xs text-muted-foreground">No assessments recorded for this class this term.</p>}
            </div>
            <div className="space-y-1.5">
              <Label>Receiver</Label>
              <Select value={receiver} onValueChange={(v) => { setReceiver(v); setPreview(null); }}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="guardian">Guardians</SelectItem>
                  <SelectItem value="learner">Learners (addressed to the learner)</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <p className="text-xs text-muted-foreground">
            Each guardian receives one message listing every subject score for the term — or just the chosen exam paper — graded on the WAEC raw-score
            scale (A1 EXCELLENT … F9 FAIL). Messages are addressed to {receiver === "learner" ? "the learner" : "the guardian"} and delivered to the phone number on file.
          </p>
          <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-3">
            <div className="text-sm">
              {preview ? (
                <>
                  <span className="font-semibold">{preview.previews.length}</span> message{preview.previews.length === 1 ? "" : "s"} drafted ·{" "}
                  <span className="font-semibold">{preview.withPhone}</span> sendable ·{" "}
                  <span className={preview.withoutPhone ? "text-destructive font-semibold" : "text-muted-foreground"}>{preview.withoutPhone}</span> without a phone
                </>
              ) : (
                <span className="text-muted-foreground">Preview first — nothing is sent until you confirm.</span>
              )}
            </div>
            <div className="flex gap-2">
              <Button size="sm" variant="outline" className="gap-1.5" onClick={runPreview} disabled={!classId || busy}>Preview messages</Button>
              <Button size="sm" className="gap-1.5" onClick={() => (preview ? setConfirm(true) : runPreview())} disabled={!classId || busy || !preview?.withPhone}>
                <Send className="size-4" /> {busy ? "Working…" : "Send marks SMS"}
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {preview && (
        <Card>
          <CardHeader className="pb-3 flex-row flex-wrap items-center justify-between gap-2 space-y-0">
            <CardTitle className="text-base">Message preview — first 3 of {preview.previews.length}</CardTitle>
            <Badge variant="secondary">{preview.yearName} · {preview.termName}</Badge>
          </CardHeader>
          <CardContent className="space-y-3">
            {preview.previews.slice(0, 3).map((p) => (
              <div key={p.studentId} className="rounded-lg border p-3">
                <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                  <span className="font-medium text-foreground">{p.student}</span>
                  <span className="font-mono">{p.phone ?? "no phone on file"}</span>
                </div>
                <pre className="mt-2 text-xs whitespace-pre-wrap font-mono bg-secondary/50 rounded-md p-2.5 leading-relaxed">{p.message}</pre>
              </div>
            ))}
            {preview.previews.length > 3 && <p className="text-xs text-muted-foreground text-center">…and {preview.previews.length - 3} more in the same format.</p>}
            {preview.previews.length === 0 && <EmptyState title="No learners in this class" />}
          </CardContent>
        </Card>
      )}

      <AlertDialog open={confirm} onOpenChange={setConfirm}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Send exam scores to {preview?.withPhone ?? 0} recipients?</AlertDialogTitle>
            <AlertDialogDescription>
              Each message costs 1 credit ({data.stats.credits} available). {receiver === "learner" ? "Messages are addressed to the learners" : "Messages are addressed to the guardians"}
              {" "}of {preview?.previews.length ?? 0} learners in {data.classes.find((c) => c.id === classId)?.name}.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction className="gap-1.5" disabled={busy} onClick={send}><Send className="size-3.5" /> {busy ? "Sending…" : "Send now"}</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}

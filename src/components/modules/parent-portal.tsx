"use client";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, PAYMENT_LABELS } from "@/lib/format";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { DateField } from "@/components/date-field";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "@/hooks/use-toast";
import { initials, avatarColor } from "@/lib/format";
import { cn } from "@/lib/utils";
import { Home, Wallet, CalendarCheck, GraduationCap, Bus, MessageSquareText, Megaphone, Phone, ArrowUpRight, CheckCircle2, AlertTriangle, Receipt, BookOpen, BookPlus, Hourglass, XCircle, Send, UtensilsCrossed, Clock, Loader2, Store, ShoppingCart, Trash2, ExternalLink, Package } from "lucide-react";
import { PortalMessenger } from "@/components/portal-messenger";

type Invoice = {
  id: string; invoiceNo: string; status: string; dueDate: string | null; createdAt: string;
  total: number; discount: number; paid: number; balance: number;
  items: { name: string; amount: number }[]; term: string | null;
};
type ReportCard = {
  id: string; term: string; classroom: string; average: number | null; position: number | null; classSize: number | null;
  conduct: string | null; interest: string | null; attendance: string | null; teacherRemark: string | null; headRemark: string | null;
  promotion: string | null; published: boolean;
  results: { subject: string; isCore: boolean; total: number | null; grade: string | null; remark: string | null; position: number | null }[];
};
type Child = {
  id: string; name: string; otherNames: string | null; nyansapoIndex: string; gender: string; status: string; guardianPhone: string;
  classroom: string | null; classTeacher: string | null;
  invoices: Invoice[];
  payments: { receiptNo: string; amount: number; method: string; paidAt: string; invoiceNo: string }[];
  attendance: { total: number; present: number; late: number; absent: number; rate: number | null; recent: { date: string; status: string }[] };
  reportCards: ReportCard[];
  transport: null | {
    route: string; vehicleReg: string; driverName: string; driverPhone: string; pickupStop: string | null; dropStop: string | null;
    farePerTerm: number; farePerTrip: number;
    recentBoardings: { date: string; direction: string; fare: number; paid: number }[];
  };
  wallet: null | { prepaid: number; transport: number };
  // iEduPro parity: library loans + book requests per child
  library: { book: string; dueAt: string; overdue: boolean }[];
  bookRequests: BookRequestLite[];
  homework: HwLite[];
};
// iEduPro parity: parents request books on behalf of their children
type BookRequestLite = {
  id: string; book: string; startAt: string; endAt: string; status: string;
  handledAt: string | null; handledByName: string | null; createdAt: string; overdue: boolean;
};
type CatalogueBook = { id: string; title: string; author: string | null; category: string; available: number };
// wave 65 — homework for the selected child
 type HwLite = {
  id: string; title: string; subject: string; classroom: string; instructions: string;
  dueDate: string; overdue: boolean; status: string; maxScore: number; postedBy: string;
  myStatus: string; myNote: string | null; myScore: number | null; myFeedback: string | null;
};
// meal opt-in / opt-out (dining register): the school's meal services + this family's requests
type MealServiceLite = { id: string; name: string; category: string; optInDaily: boolean; weekdays: string };
type MealRequestLite = { id: string; studentId: string; student: string; service: string; date: string; kind: "OPT_OUT" | "OPT_IN"; status: "PENDING" | "APPROVED" | "DECLINED"; reason: string | null; decidedByName: string | null; createdAt: string };
const MEAL_REQ_STATUS: Record<string, { label: string; className: string }> = {
  PENDING: { label: "Waiting for the school", className: "bg-amber-600" },
  APPROVED: { label: "Approved", className: "bg-emerald-600" },
  DECLINED: { label: "Declined", className: "bg-destructive" },
};
const REQ_STATUS: Record<string, { label: string; className: string; icon: React.ReactNode }> = {
  PENDING: { label: "Waiting for librarian", className: "bg-amber-600", icon: <Hourglass className="size-3" /> },
  ISSUED: { label: "Issued", className: "bg-emerald-600", icon: <CheckCircle2 className="size-3" /> },
  REJECTED: { label: "Declined", className: "bg-destructive", icon: <XCircle className="size-3" /> },
};
// wave 77 — school shop + online payments (Hubtel MoMo prompts / hosted checkout links)
type ShopItem = { id: string; name: string; category: string; price: number; stock: number | null };
type ShopOrderLine = { name: string; price: number; qty: number };
type ShopOrder = {
  id: string; orderNo: string; status: string; total: number; paymentMethod: string | null;
  deliveryNote: string | null; cancelReason: string | null; createdAt: string; paidAt: string | null;
  deliveredAt: string | null; studentId: string; student: string; items: ShopOrderLine[];
  payRequest: { id: string; clientReference: string; status: string; checkoutUrl: string | null } | null;
};
type PayRequest = { id: string; clientReference: string; status: string; checkoutUrl: string | null; provider?: string; amount: number };
// newest status of a tracked payment, as returned by the payment.check action
type CheckedRequest = {
  id: string; clientReference: string; status: string; checkoutUrl: string | null; amount: number;
  appliedNote: string | null; failureReason: string | null;
};
type PaymentRequestRow = {
  id: string; purpose: string; amount: number; status: string; method: string | null; channel: string | null; provider: string;
  clientReference: string; checkoutUrl: string | null; appliedNote: string | null; failureReason: string | null;
  createdAt: string; completedAt: string | null; shopOrderId: string | null; invoiceId: string | null; walletKind: string | null;
};
// what the shared PayDialog is paying for (fee invoice / wallet top-up / shop order)
type PayTarget =
  | { kind: "fees"; invoiceId: string; invoiceNo: string; balance: number; studentId: string; studentName: string; phone: string }
  | { kind: "wallet"; walletKind: "PREPAID" | "TRANSPORT"; studentId: string; studentName: string; phone: string }
  | { kind: "shop"; orderId: string; orderNo: string; total: number; studentName: string; phone: string };

type ParentData = {
  role: string; school: { name: string; motto: string | null; phone: string | null; town: string; region: string } | null;
  year: string | null; term: string | null;
  children: Child[];
  announcements: { id: string; title: string; body: string; postedByName: string; postedAt: string; category: string; priority: string }[];
  messages: { id: string; recipientName: string; recipientPhone: string; body: string; sentAt: string; status: string }[];
  libraryCatalogue: CatalogueBook[];
  mealServices?: MealServiceLite[];
  mealRequests?: MealRequestLite[];
  // wave 77 — school shop catalog, the family's orders and recent online payment attempts
  paymentsMode?: "HUBTEL" | "SIMULATED";
  paymentsProviders?: string[]; // wave 78 — gateways with live keys on this deployment
  shopItems?: ShopItem[];
  shopOrders?: ShopOrder[];
  paymentRequests?: PaymentRequestRow[];
};

const ATT_COLOR: Record<string, string> = { PRESENT: "bg-emerald-600", LATE: "bg-amber-500", ABSENT: "bg-destructive", SICK: "bg-sky-500", EXCUSED: "bg-zinc-400" };
const DIR_LABEL: Record<string, string> = { IN: "Morning pick-up", OUT: "Afternoon drop-off", BOTH: "Both ways", NONE: "Not riding" };
// wave 77 — shop categories, order statuses, payment-request statuses & purposes
const SHOP_CATS: Record<string, string> = { STATIONERY: "Stationery", UNIFORM: "Uniform", BOOKS: "Books", SUPPLIES: "Supplies", OTHER: "Other" };
const PAY_STATUS: Record<string, { label: string; className: string }> = {
  PENDING: { label: "Awaiting payment", className: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300" },
  COMPLETED: { label: "Paid", className: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300" },
  FAILED: { label: "Failed", className: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300" },
  CANCELLED: { label: "Cancelled", className: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300" },
};
const ORDER_STATUS: Record<string, { label: string; className: string }> = {
  PENDING_PAYMENT: { label: "Awaiting payment", className: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300" },
  PAID: { label: "Paid — being packed", className: "bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300" },
  READY: { label: "Packed — out for delivery", className: "bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300" },
  DELIVERED: { label: "Delivered", className: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300" },
  CANCELLED: { label: "Cancelled", className: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300" },
};
const ORDER_PAYMENT: Record<string, string> = { ONLINE: "paid online", COUNTER: "counter receipt" };
const PURPOSE_LABEL: Record<string, string> = { INVOICE: "School fees", WALLET: "Wallet top-up", SHOP_ORDER: "Shop order", DAILY_ITEMS: "Daily fee items", ITEM_BALANCE: "Fee-item advance" };
// wave 78 — Ghana payment gateways the school can collect through
const PROVIDER_NAMES: Record<string, string> = { HUBTEL: "Hubtel", PAYSTACK: "Paystack", FLUTTERWAVE: "Flutterwave" };

/** wave 78 — gateway chips: pick which gateway collects this payment.
 *  "live" = credentials on the deployment; "sandbox" = no keys at all, every
 *  gateway runs the rehearse flow (pay page simulate button). */
function GatewayPicker({ providers, value, onChange }: { providers: string[]; value: string; onChange: (v: string) => void }) {
  const sandbox = providers.length === 0;
  return (
    <div className="space-y-1.5">
      <Label>Gateway</Label>
      <div className="grid grid-cols-3 gap-2">
        {(["HUBTEL", "PAYSTACK", "FLUTTERWAVE"] as const).map((p) => {
          const live = providers.includes(p);
          const on = value === p;
          return (
            <button key={p} type="button" onClick={() => onChange(p)} aria-pressed={on}
              className={`rounded-lg border px-2 py-2 text-center transition-colors ${on ? "border-primary bg-primary/5" : "hover:bg-muted/50"}`}>
              <div className="text-sm font-medium leading-tight">{PROVIDER_NAMES[p]}</div>
              <div className={`text-[10px] leading-tight ${live ? "text-emerald-600" : sandbox ? "text-amber-600" : "text-muted-foreground"}`}>
                {live ? "live" : sandbox ? "sandbox" : "not set up"}
              </div>
            </button>
          );
        })}
      </div>
    </div>
  );
}

export function ParentPortal() {
  const { currentSchoolId, session } = useStore();
  const [data, setData] = useState<ParentData | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [childIdx, setChildIdx] = useState(0);
  const [requestOpen, setRequestOpen] = useState(false);
  // wave 77 — the shared online-payment dialog target (fee invoice / wallet top-up)
  const [payTarget, setPayTarget] = useState<PayTarget | null>(null);
  // meal opt-in / opt-out form
  const [mealDate, setMealDate] = useState(new Date().toISOString().slice(0, 10));
  const [mealService, setMealService] = useState("");
  const [mealReason, setMealReason] = useState("");
  const [mealBusy, setMealBusy] = useState(false);

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<ParentData>(`/api/school/${currentSchoolId}/ops/portal?view=parent`)
      .then((d) => { setData(d); setChildIdx((i) => Math.min(i, Math.max(0, d.children.length - 1))); })
      .catch((e) => setErr(e.message));
  }, [currentSchoolId]);
  useEffect(() => { load(); }, [load]);

  if (err) return <div className="text-destructive text-sm">{err}</div>;
  if (!data) return <LoadingBlock label="Fetching your children…" />;
  if (!data.children.length) return <EmptyState icon={<Home className="size-8" />} title="No children linked" hint="Ask the school office to link your children to this account." />;

  const child = data.children[Math.min(childIdx, data.children.length - 1)];
  const balance = child.invoices.reduce((s, i) => s + i.balance, 0);
  /* wave 21 — only published terminal reports reach parents */
  const pubCards = child.reportCards.filter((rc) => rc.published);

  return (
    <div>
      <ModuleHeader
        title={`Akwaaba, ${session?.fullName?.split(" ").slice(-1)[0]}`}
        description={`${data.school?.name ?? "Your school"} · ${data.term ?? "Term"} ${data.year ? `· ${data.year}` : ""} — everything about your children in one place.`}
        actions={data.school?.phone ? <Badge variant="outline" className="gap-1.5"><Phone className="size-3.5" /> {data.school.phone}</Badge> : null}
      />

      {/* child switcher */}
      <div className="flex flex-wrap gap-2 mb-4">
        {data.children.map((c, i) => (
          <button key={c.id} onClick={() => setChildIdx(i)}
            className={`flex items-center gap-2.5 rounded-xl border px-3 py-2 transition-colors ${i === childIdx ? "border-primary bg-primary/5" : "hover:bg-secondary/50"}`}>
            <span className={`flex size-8 items-center justify-center rounded-full text-xs font-bold text-white`} style={{ background: avatarColor(c.id) }}>{initials(c.name)}</span>
            <span className="text-left">
              <span className="block text-sm font-medium leading-tight">{c.name}</span>
              <span className="block text-[11px] text-muted-foreground">{c.classroom ?? "—"}</span>
            </span>
          </button>
        ))}
      </div>

      {/* quick stats */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <StatTile icon={<Wallet className="size-4" />} label="Fee balance" value={fmtCedi(balance, { compact: true })} sub={balance > 0 ? "across all bills" : "fully settled — medaase!"} tone={balance > 0 ? "danger" : "good"} />
        <StatTile icon={<CalendarCheck className="size-4" />} label="Attendance" value={child.attendance.rate !== null ? `${child.attendance.rate}%` : "—"} sub={`${child.attendance.present} present · ${child.attendance.absent} absent`} />
        <StatTile icon={<GraduationCap className="size-4" />} label="Latest average" value={pubCards[0]?.average != null ? `${pubCards[0].average}%` : "—"} sub={pubCards[0]?.term ?? "no published reports yet"} />
        <StatTile icon={<Bus className="size-4" />} label={child.transport ? "Transport" : "Transport"} value={child.transport ? child.transport.route : "Not enrolled"} sub={child.transport ? `${child.transport.pickupStop ?? "—"} → school` : "no route assigned"} />
      </div>

      <Tabs defaultValue="overview">
        <TabsList className="flex-wrap h-auto">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="homework" className="gap-1.5"><BookPlus className="size-4" /> Homework</TabsTrigger>
          <TabsTrigger value="fees">Fees & Payments</TabsTrigger>
          <TabsTrigger value="shop" className="gap-1.5"><Store className="size-4" /> Shop</TabsTrigger>
          <TabsTrigger value="attendance">Attendance</TabsTrigger>
          <TabsTrigger value="reports">Reports</TabsTrigger>
          <TabsTrigger value="library">Library</TabsTrigger>
          {child.transport && <TabsTrigger value="transport">Transport</TabsTrigger>}
          <TabsTrigger value="messages">Messages</TabsTrigger>
        </TabsList>

        <TabsContent value="overview" className="mt-3 space-y-4">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <Card className="lg:col-span-2">
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Receipt className="size-4" /> Bills this year</CardTitle></CardHeader>
              <CardContent>
                {child.invoices.length ? (
                  <div className="space-y-2">
                    {child.invoices.slice(0, 4).map((inv) => (
                      <div key={inv.id} className="flex items-center justify-between rounded-lg border px-3 py-2">
                        <div className="min-w-0">
                          <div className="text-sm font-medium truncate">{inv.invoiceNo} {inv.term && <span className="text-xs text-muted-foreground">· {inv.term}</span>}</div>
                          <div className="text-xs text-muted-foreground">{inv.items.length} item{inv.items.length > 1 ? "s" : ""}{inv.dueDate ? ` · due ${fmtDate(inv.dueDate)}` : ""}</div>
                        </div>
                        <div className="text-right shrink-0">
                          <div className="text-sm font-semibold tabular-nums">{fmtCedi(inv.balance)}</div>
                          <Badge className={inv.status === "PAID" ? "bg-emerald-600" : inv.status === "PARTIAL" ? "bg-amber-600" : "bg-destructive"}>{inv.status}</Badge>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : <EmptyState title="No bills yet" hint="Invoices will appear here when the school issues them." />}
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Megaphone className="size-4" /> School notices</CardTitle></CardHeader>
              <CardContent className="space-y-2.5 max-h-72 overflow-y-auto nice-scroll">
                {data.announcements.length ? data.announcements.map((a) => (
                  <div key={a.id} className="rounded-lg border p-2.5">
                    <div className="flex items-center gap-1.5">
                      {a.priority === "HIGH" && <Badge className="bg-destructive text-[9px]">urgent</Badge>}
                      <span className="text-sm font-medium">{a.title}</span>
                    </div>
                    <p className="text-xs text-muted-foreground mt-1 line-clamp-3">{a.body}</p>
                    <div className="text-[10px] text-muted-foreground mt-1">{a.postedByName} · {fmtDate(a.postedAt)}</div>
                  </div>
                )) : <EmptyState title="No notices" />}
              </CardContent>
            </Card>
          </div>

          {child.wallet && (child.wallet.prepaid > 0 || child.wallet.transport > 0) && (
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Wallet className="size-4" /> Prepaid wallets</CardTitle></CardHeader>
              <CardContent className="grid gap-3 sm:grid-cols-2">
                <div className="rounded-lg border p-3 flex items-start justify-between gap-2">
                  <div>
                    <div className="text-xs text-muted-foreground">Tuck / meals wallet</div>
                    <div className="text-xl font-bold tabular-nums">{fmtCedi(child.wallet.prepaid)}</div>
                  </div>
                  <Button size="sm" variant="outline" className="h-8 gap-1.5 shrink-0" onClick={() => setPayTarget({ kind: "wallet", walletKind: "PREPAID", studentId: child.id, studentName: child.name, phone: child.guardianPhone })}>
                    <Wallet className="size-3.5" /> Top up
                  </Button>
                </div>
                <div className="rounded-lg border p-3 flex items-start justify-between gap-2">
                  <div>
                    <div className="text-xs text-muted-foreground">Transport wallet</div>
                    <div className="text-xl font-bold tabular-nums">{fmtCedi(child.wallet.transport)}</div>
                  </div>
                  <Button size="sm" variant="outline" className="h-8 gap-1.5 shrink-0" onClick={() => setPayTarget({ kind: "wallet", walletKind: "TRANSPORT", studentId: child.id, studentName: child.name, phone: child.guardianPhone })}>
                    <Wallet className="size-3.5" /> Top up
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}

          {/* meal opt-in / opt-out — skip lunch on a day or join optional breakfast */}
          {!!data.mealServices?.length && (
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-base flex items-center gap-2"><UtensilsCrossed className="size-4" /> Meals &amp; dining — {child.name}</CardTitle>
              </CardHeader>
              <CardContent className="grid gap-4 lg:grid-cols-2">
                <div className="space-y-3">
                  <p className="text-xs text-muted-foreground leading-relaxed">
                    Lunch is served to every learner by default — ask the school to skip it for a day and no charge is made.
                    Breakfast-style meals marked <b className="text-foreground">opt-in</b> only bill on days you join.
                  </p>
                  <div className="grid gap-3 sm:grid-cols-2">
                    <div className="space-y-1.5">
                      <Label>Day</Label>
                      <DateField min={new Date().toISOString().slice(0, 10)} value={mealDate} onChange={(v) => setMealDate(v ?? "")} />
                    </div>
                    <div className="space-y-1.5">
                      <Label>Meal</Label>
                      <Select value={mealService} onValueChange={setMealService}>
                        <SelectTrigger><SelectValue placeholder="Pick a meal" /></SelectTrigger>
                        <SelectContent>
                          {data.mealServices!.map((s) => (
                            <SelectItem key={s.id} value={s.id}>{s.name}{s.optInDaily ? " — opt-in" : ""}</SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                  <div className="space-y-1.5">
                    <Label>Reason <span className="text-muted-foreground font-normal">(optional)</span></Label>
                    <Input value={mealReason} onChange={(e) => setMealReason(e.target.value)} placeholder="e.g. Packed lunch from home that day" />
                  </div>
                  {(() => {
                    const svc = data.mealServices!.find((s) => s.id === mealService);
                    const isOptIn = !!svc?.optInDaily;
                    return (
                      <Button
                        disabled={mealBusy || !mealService}
                        className="gap-1.5"
                        onClick={async () => {
                          if (!currentSchoolId || !mealService) return;
                          setMealBusy(true);
                          try {
                            await apiPost(`/api/school/${currentSchoolId}/ops/portal`, {
                              action: "meal-request.create",
                              studentId: child.id, serviceId: mealService, date: mealDate,
                              kind: isOptIn ? "OPT_IN" : "OPT_OUT", reason: mealReason.trim() || undefined,
                            });
                            toast({ title: "Request sent to the school", description: `${svc?.name} · ${fmtDate(mealDate)} — you'll see the decision here.` });
                            setMealReason(""); setMealService("");
                            load();
                          } catch (e: any) {
                            toast({ title: "Could not send request", description: e.message, variant: "destructive" });
                          } finally { setMealBusy(false); }
                        }}
                      >
                        <Send className="size-4" />
                        {isOptIn ? `Join ${svc?.name ?? "the meal"} for ${fmtDate(mealDate)}` : `Skip ${svc?.name ?? "the meal"} on ${fmtDate(mealDate)}`}
                      </Button>
                    );
                  })()}
                </div>
                <div className="space-y-2">
                  <div className="text-xs font-medium text-muted-foreground">Your meal requests</div>
                  {(data.mealRequests ?? []).filter((r) => r.studentId === child.id).slice(0, 6).map((r) => (
                    <div key={r.id} className="rounded-lg border px-3 py-2 flex items-center justify-between gap-2">
                      <div className="min-w-0">
                        <div className="text-sm font-medium truncate">{r.kind === "OPT_OUT" ? "Skip" : "Join"} {r.service} · {fmtDate(r.date)}</div>
                        {r.reason && <div className="text-[11px] text-muted-foreground truncate">{r.reason}</div>}
                      </div>
                      <Badge className={MEAL_REQ_STATUS[r.status]?.className ?? "bg-secondary"}>
                        {r.status === "PENDING" && <Clock className="size-3 mr-1" />}
                        {MEAL_REQ_STATUS[r.status]?.label ?? r.status}
                      </Badge>
                    </div>
                  ))}
                  {!(data.mealRequests ?? []).filter((r) => r.studentId === child.id).length && (
                    <div className="rounded-lg border border-dashed px-3 py-6 text-center text-xs text-muted-foreground">No meal requests yet.</div>
                  )}
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        {/* wave 65 — Homework: what the teachers posted for this child, with a
            one-tap "submit" the parent can do on the learner's behalf */}
        <TabsContent value="homework" className="mt-3">
          <ParentHomework child={child} onChanged={load} />
        </TabsContent>

        <TabsContent value="fees" className="mt-3 space-y-4">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-base">Bills & balances — {child.name}</CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Bill</TableHead>
                    <TableHead className="hidden md:table-cell">Items</TableHead>
                    <TableHead>Total</TableHead>
                    <TableHead>Paid</TableHead>
                    <TableHead>Balance</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead />
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {child.invoices.map((inv) => (
                    <TableRow key={inv.id}>
                      <TableCell>
                        <div className="font-medium">{inv.invoiceNo}</div>
                        <div className="text-xs text-muted-foreground">{inv.term ?? fmtDate(inv.createdAt)}</div>
                      </TableCell>
                      <TableCell className="hidden md:table-cell text-xs text-muted-foreground">{inv.items.map((i) => i.name).join(" · ")}</TableCell>
                      <TableCell className="tabular-nums">{fmtCedi(inv.total - inv.discount)}</TableCell>
                      <TableCell className="tabular-nums text-emerald-600 dark:text-emerald-400">{fmtCedi(inv.paid)}</TableCell>
                      <TableCell className="tabular-nums font-semibold">{fmtCedi(inv.balance)}</TableCell>
                      <TableCell><Badge className={inv.status === "PAID" ? "bg-emerald-600" : inv.status === "PARTIAL" ? "bg-amber-600" : "bg-destructive"}>{inv.status}</Badge></TableCell>
                      <TableCell>
                        <div className="flex items-center justify-end gap-1">
                          {inv.balance > 0 && (
                            <Button size="sm" className="h-7 gap-1 text-xs" onClick={() => setPayTarget({ kind: "fees", invoiceId: inv.id, invoiceNo: inv.invoiceNo, balance: inv.balance, studentId: child.id, studentName: child.name, phone: child.guardianPhone })}>
                              <Wallet className="size-3" /> Pay online
                            </Button>
                          )}
                          <Button size="sm" variant="ghost" className="h-7 gap-1 text-xs" onClick={() => window.open(`/invoice/${inv.id}`, "_blank")}>
                            Statement <ArrowUpRight className="size-3" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
              {!child.invoices.length && <EmptyState title="No bills this year" />}
            </CardContent>
          </Card>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base">Payment history</CardTitle></CardHeader>
              <CardContent>
                {child.payments.length ? (
                  <div className="space-y-1.5 max-h-64 overflow-y-auto nice-scroll">
                    {child.payments.map((p, i) => (
                      <div key={i} className="flex items-center justify-between rounded-lg border px-3 py-2">
                        <div>
                          <div className="text-sm font-medium">{fmtCedi(p.amount)}</div>
                          <div className="text-xs text-muted-foreground">{fmtDate(p.paidAt)} · {PAYMENT_LABELS[p.method] ?? p.method}</div>
                        </div>
                        <span className="text-[10px] font-mono text-muted-foreground">{p.receiptNo}</span>
                      </div>
                    ))}
                  </div>
                ) : <EmptyState title="No payments recorded yet" />}
              </CardContent>
            </Card>

            <Card className="bg-secondary/30">
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Phone className="size-4" /> How to pay</CardTitle></CardHeader>
              <CardContent className="text-sm space-y-2.5 text-muted-foreground">
                <p><b className="text-foreground">Mobile Money</b> — the school&apos;s MoMo number is on every statement. Use your child&apos;s <b className="text-foreground">{child.nyansapoIndex}</b> as the reference so the payment lands on the right bill.</p>
                <p><b className="text-foreground">At the school office</b> — cash, cheque and card are accepted at the front desk during school hours; you get a printed receipt every time.</p>
                <p><b className="text-foreground">Bank transfer</b> — request the school&apos;s account details from the bursar&apos;s office and forward the slip on WhatsApp.</p>
                <p className="text-xs">Every payment is receipted. If a payment does not reflect within 24 hours, call the office with your receipt number.</p>
              </CardContent>
            </Card>
          </div>

          {/* wave 77 — recent online payment attempts (fees, wallets, shop) with their live status */}
          {!!(data.paymentRequests ?? []).length && (
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base">Online payments</CardTitle></CardHeader>
              <CardContent>
                <div className="space-y-1.5 max-h-64 overflow-y-auto nice-scroll">
                  {(data.paymentRequests ?? []).map((r) => {
                    const st = PAY_STATUS[r.status] ?? PAY_STATUS.PENDING;
                    const label = r.purpose === "WALLET"
                      ? (r.walletKind === "TRANSPORT" ? "Transport wallet top-up" : "Tuck / meals wallet top-up")
                      : PURPOSE_LABEL[r.purpose] ?? r.purpose;
                    return (
                      <div key={r.id} className="flex items-center justify-between gap-2 rounded-lg border px-3 py-2">
                        <div className="min-w-0">
                          <div className="text-sm font-medium">{fmtCedi(r.amount)} <span className="font-normal text-muted-foreground">· {label}</span> <span className="text-[10px] font-normal text-muted-foreground">via {PROVIDER_NAMES[r.provider] ?? r.provider}</span></div>
                          <div className="text-[11px] text-muted-foreground truncate">
                            {fmtDate(r.createdAt)}{r.appliedNote ? ` · ${r.appliedNote}` : ""}{r.failureReason ? ` · ${r.failureReason}` : ""}
                          </div>
                        </div>
                        <Badge className={cn("shrink-0", st.className)}>{st.label}</Badge>
                      </div>
                    );
                  })}
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        {/* wave 77 — school shop: catalog + basket + checkout (online or at the counter) */}
        <TabsContent value="shop" className="mt-3">
          <ParentShop data={data} schoolId={currentSchoolId!} defaultStudentId={child.id} onChanged={load} />
        </TabsContent>

        <TabsContent value="attendance" className="mt-3 space-y-4">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base">Attendance — {child.name}</CardTitle></CardHeader>
            <CardContent>
              {child.attendance.total ? (
                <>
                  <div className="grid grid-cols-4 gap-3 mb-4">
                    <MiniStat label="Present" value={child.attendance.present} tone="good" />
                    <MiniStat label="Late" value={child.attendance.late} tone="warn" />
                    <MiniStat label="Absent" value={child.attendance.absent} tone="bad" />
                    <MiniStat label="Sessions" value={child.attendance.total} />
                  </div>
                  <div className="mb-1 flex justify-between text-xs text-muted-foreground"><span>Attendance rate</span><span className="font-semibold">{child.attendance.rate}%</span></div>
                  <Progress value={child.attendance.rate ?? 0} className="h-2" />
                  <div className="mt-4">
                    <div className="text-xs font-medium uppercase tracking-wide text-muted-foreground mb-2">Last 10 sessions (most recent first)</div>
                    <div className="flex gap-1.5 flex-wrap">
                      {child.attendance.recent.map((r, i) => (
                        <div key={i} className="w-16 rounded-lg border overflow-hidden">
                          <div className={`h-1.5 ${ATT_COLOR[r.status] ?? "bg-zinc-300"}`} />
                          <div className="px-1.5 py-1">
                            <div className="text-[10px] font-medium">{r.status}</div>
                            <div className="text-[9px] text-muted-foreground">{fmtDate(r.date).slice(0, 6)}</div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                </>
              ) : <EmptyState title="No attendance recorded yet" />}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="reports" className="mt-3 space-y-4">
          {/* wave 21 — parents see only PUBLISHED results; the school
              publishes (and can recall) from Results & Report Cards */}
          {pubCards.length ? pubCards.map((rc) => (
            <Card key={rc.id}>
              <CardHeader className="pb-2">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <CardTitle className="text-base">{rc.term} — {rc.classroom}</CardTitle>
                  <div className="flex items-center gap-2">
                    {rc.position != null && <Badge variant="secondary">{rc.position} of {rc.classSize}</Badge>}
                    {rc.promotion && <Badge className={rc.promotion === "PROMOTED" ? "bg-emerald-600" : "bg-amber-600"}>{rc.promotion}</Badge>}
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-3 gap-3 mb-3">
                  <MiniStat label="Average" value={rc.average != null ? `${rc.average}%` : "—"} tone="good" />
                  <MiniStat label="Conduct" value={rc.conduct ?? "—"} />
                  <MiniStat label="Attendance" value={rc.attendance ?? "—"} />
                </div>
                <Table>
                  <TableHeader>
                    <TableRow><TableHead>Subject</TableHead><TableHead>Score</TableHead><TableHead>Grade</TableHead><TableHead className="hidden sm:table-cell">Remark</TableHead></TableRow>
                  </TableHeader>
                  <TableBody>
                    {rc.results.map((r, i) => (
                      <TableRow key={i}>
                        <TableCell className="font-medium">{r.subject}{r.isCore && <Badge variant="outline" className="ml-1.5 text-[9px]">core</Badge>}</TableCell>
                        <TableCell className="tabular-nums">{r.total != null ? `${r.total}%` : "—"}</TableCell>
                        <TableCell>{r.grade ? <Badge variant="secondary">{r.grade}</Badge> : "—"}</TableCell>
                        <TableCell className="hidden sm:table-cell text-xs text-muted-foreground">{r.remark ?? "—"}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
                {rc.teacherRemark && <p className="mt-3 text-sm rounded-lg bg-secondary/50 p-3"><b>Class teacher:</b> {rc.teacherRemark}</p>}
                {rc.headRemark && <p className="mt-2 text-sm rounded-lg bg-secondary/50 p-3"><b>Head of school:</b> {rc.headRemark}</p>}
              </CardContent>
            </Card>
          )) : <EmptyState icon={<GraduationCap className="size-8" />} title={child.reportCards.length ? "Results not published yet" : "No reports yet"} hint={child.reportCards.length ? "The school has compiled results for this term but hasn't published them — check back after the head teacher releases them." : "Terminal reports appear here once the school publishes them."} />}
        </TabsContent>

        {/* ── LIBRARY (iEduPro parity): books out + request on behalf of the child ── */}
        <TabsContent value="library" className="mt-3 space-y-4">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-2 flex-row items-center justify-between space-y-0">
                <CardTitle className="text-base flex items-center gap-2"><BookOpen className="size-4" /> Books out — {child.name}</CardTitle>
                <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={() => setRequestOpen(true)}>
                  <BookPlus className="size-3.5" /> Request a book
                </Button>
              </CardHeader>
              <CardContent>
                {child.library.length ? (
                  <div className="space-y-2">
                    {child.library.map((l, i) => (
                      <div key={i} className={`flex items-center justify-between rounded-lg border px-3 py-2.5 ${l.overdue ? "border-destructive/40 bg-destructive/5" : ""}`}>
                        <div className="flex items-center gap-2.5 min-w-0">
                          <span className="flex size-8 shrink-0 items-center justify-center rounded-full text-white" style={{ background: avatarColor(l.book) }}><BookOpen className="size-4" /></span>
                          <div className="min-w-0">
                            <div className="text-sm font-medium truncate">{l.book}</div>
                            <div className="text-xs text-muted-foreground">due {fmtDate(l.dueAt)}</div>
                          </div>
                        </div>
                        {l.overdue
                          ? <Badge className="bg-destructive gap-1"><AlertTriangle className="size-3" /> overdue</Badge>
                          : <Badge variant="secondary" className="gap-1"><CheckCircle2 className="size-3" /> on time</Badge>}
                      </div>
                    ))}
                  </div>
                ) : <EmptyState title="No books out" hint="Request a book on the right — the librarian will issue it." />}
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-base flex items-center gap-2">
                  <BookPlus className="size-4" /> Book requests — {child.name}
                  {child.bookRequests.some((r) => r.status === "PENDING") && <Badge className="bg-amber-600 text-[10px]">waiting</Badge>}
                </CardTitle>
              </CardHeader>
              <CardContent>
                {child.bookRequests.length ? (
                  <div className="space-y-2 max-h-96 overflow-y-auto nice-scroll">
                    {child.bookRequests.map((r) => {
                      const s = REQ_STATUS[r.status] ?? REQ_STATUS.PENDING;
                      return (
                        <div key={r.id} className={`flex items-center justify-between gap-2 rounded-lg border px-3 py-2.5 ${r.overdue ? "border-destructive/40 bg-destructive/5" : ""}`}>
                          <div className="min-w-0">
                            <div className="text-sm font-medium truncate">{r.book}</div>
                            <div className="text-xs text-muted-foreground">
                              {fmtDate(r.startAt)} → {fmtDate(r.endAt)}
                              {r.status !== "PENDING" && r.handledByName ? ` · by ${r.handledByName}` : ""}
                            </div>
                          </div>
                          <div className="flex flex-col items-end gap-1 shrink-0">
                            <Badge className={cn("gap-1", s.className)}>{s.icon} {s.label}</Badge>
                            {r.overdue && <Badge className="bg-destructive gap-1"><AlertTriangle className="size-3" /> overdue</Badge>}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                ) : <EmptyState title="No requests yet" hint={`Pick a book from the catalogue and ${child.name.split(" ")[0]} gets it once the librarian confirms.`} />}
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {child.transport && (
          <TabsContent value="transport" className="mt-3 space-y-4">
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Bus className="size-4" /> {child.transport.route}</CardTitle></CardHeader>
              <CardContent>
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                  <MiniStat label="Vehicle" value={child.transport.vehicleReg} />
                  <MiniStat label="Driver" value={child.transport.driverName} />
                  <MiniStat label="Term fare" value={fmtCedi(child.transport.farePerTerm, { compact: true })} />
                  <MiniStat label="Per trip" value={fmtCedi(child.transport.farePerTrip, { compact: true })} />
                </div>
                <div className="text-xs font-medium uppercase tracking-wide text-muted-foreground mb-2">Recent boardings</div>
                {child.transport.recentBoardings.length ? (
                  <div className="space-y-1.5">
                    {child.transport.recentBoardings.map((b, i) => (
                      <div key={i} className="flex items-center justify-between rounded-lg border px-3 py-2">
                        <div className="text-sm">{fmtDate(b.date)}</div>
                        <div className="flex items-center gap-2">
                          <Badge variant="outline">{DIR_LABEL[b.direction] ?? b.direction}</Badge>
                          <span className="text-sm tabular-nums">{fmtCedi(b.fare)}</span>
                          <Badge className={b.paid >= b.fare ? "bg-emerald-600" : "bg-amber-600"}>{b.paid >= b.fare ? <CheckCircle2 className="size-3" /> : <AlertTriangle className="size-3" />} {b.paid >= b.fare ? "paid" : "owing"}</Badge>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : <EmptyState title="No boardings recorded yet" />}
                {child.transport.driverPhone && <p className="mt-3 text-xs text-muted-foreground">Driver contact: <b>{child.transport.driverPhone}</b></p>}
              </CardContent>
            </Card>
          </TabsContent>
        )}

        <TabsContent value="messages" className="mt-3 space-y-4">
          {/* wave 40 — two-way conversations with the school office (the staff
              Messages module replies from its own two-pane inbox) */}
          <PortalMessenger />
          <Card>
            <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><MessageSquareText className="size-4" /> Messages from the school</CardTitle></CardHeader>
            <CardContent>
              {data.messages.length ? (
                <div className="space-y-2 max-h-96 overflow-y-auto nice-scroll">
                  {data.messages.map((m) => (
                    <div key={m.id} className="rounded-lg border p-3">
                      <div className="flex items-center justify-between text-xs text-muted-foreground">
                        <span>to {m.recipientName} · {m.recipientPhone}</span>
                        <span>{fmtDate(m.sentAt)}</span>
                      </div>
                      <p className="text-sm mt-1">{m.body}</p>
                    </div>
                  ))}
                </div>
              ) : <EmptyState title="No messages yet" hint="SMS alerts about attendance, fees and results will appear here." />}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* iEduPro parity: request a book on behalf of the selected child */}
      <BookRequestDialog
        open={requestOpen}
        onOpenChange={setRequestOpen}
        catalogue={data.libraryCatalogue ?? []}
        studentId={child.id}
        studentName={child.name}
        schoolId={currentSchoolId!}
        onDone={load}
      />

      {/* wave 77 — online payment dialog (fees invoice / wallet top-up) */}
      <PayDialog
        open={!!payTarget}
        onOpenChange={(o) => { if (!o) setPayTarget(null); }}
        target={payTarget}
        schoolId={currentSchoolId!}
        providers={data.paymentsProviders ?? []}
        onDone={load}
      />
    </div>
  );
}

/* ── Request-a-book dialog (iEduPro parity) — the parent picks a title from the
    catalogue and the dates on behalf of their child; the librarian confirms. ── */
function BookRequestDialog({ open, onOpenChange, catalogue, studentId, studentName, schoolId, onDone }: {
  open: boolean; onOpenChange: (o: boolean) => void;
  catalogue: CatalogueBook[]; studentId: string; studentName: string; schoolId: string; onDone: () => void;
}) {
  const today = new Date().toISOString().slice(0, 10);
  const defaultEnd = new Date(Date.now() + 14 * 864e5).toISOString().slice(0, 10);
  const [bookId, setBookId] = useState("");
  const [startAt, setStartAt] = useState(today);
  const [endAt, setEndAt] = useState(defaultEnd);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (open) { setBookId(""); setStartAt(today); setEndAt(defaultEnd); }
  }, [open]);

  const book = catalogue.find((b) => b.id === bookId) ?? null;

  async function submit() {
    if (!bookId) { toast({ title: "Choose a book" }); return; }
    if (!startAt || !endAt || endAt <= startAt) { toast({ title: "Check your dates", description: "The return date must be after the issue date." }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}/ops/portal`, { action: "book-request.create", bookId, studentId, startAt, endAt });
      toast({ title: "Request sent", description: `"${book?.title}" for ${studentName} — the librarian will confirm shortly.` });
      onOpenChange(false);
      onDone();
    } catch (e: any) {
      toast({ title: "Request failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><BookPlus className="size-4.5" /> Request a book for {studentName}</DialogTitle>
          <DialogDescription>Pick a title and the dates — the librarian issues it if a copy is on the shelf.</DialogDescription>
        </DialogHeader>
        <div className="grid gap-3">
          <div className="space-y-1.5">
            <Label>Book *</Label>
            <Select value={bookId} onValueChange={setBookId}>
              <SelectTrigger><SelectValue placeholder="Choose from the catalogue…" /></SelectTrigger>
              <SelectContent className="max-h-64">
                {catalogue.map((b) => (
                  <SelectItem key={b.id} value={b.id} disabled={b.available < 1}>
                    {b.title} — {b.available < 1 ? "all copies out" : `${b.available} on shelf`}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {book && <p className="text-[11px] text-muted-foreground">{book.author ?? "—"} · {book.category}</p>}
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Issue date</Label>
              <DateField value={startAt} onChange={(v) => setStartAt(v ?? "")} />
            </div>
            <div className="space-y-1.5">
              <Label>Return by</Label>
              <DateField value={endAt} onChange={(v) => setEndAt(v ?? "")} />
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button disabled={busy || !bookId} onClick={submit} className="gap-1.5">
            {busy ? "Sending…" : <><Send className="size-4" /> Send request</>}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function StatTile({ icon, label, value, sub, tone }: { icon: React.ReactNode; label: string; value: React.ReactNode; sub?: string; tone?: "good" | "danger" }) {
  return (
    <div className={`rounded-xl border bg-card p-4 ${tone === "danger" ? "border-destructive/30" : ""}`}>
      <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</span>{icon}</div>
      <div className={`mt-1.5 text-xl font-bold tabular-nums ${tone === "danger" ? "text-destructive" : tone === "good" ? "text-emerald-600 dark:text-emerald-400" : ""}`}>{value}</div>
      <div className="text-[11px] text-muted-foreground truncate">{sub}</div>
    </div>
  );
}

function MiniStat({ label, value, tone }: { label: string; value: React.ReactNode; tone?: "good" | "warn" | "bad" }) {
  return (
    <div className="rounded-lg border p-2.5 text-center">
      <div className="text-[10px] uppercase tracking-wide text-muted-foreground">{label}</div>
      <div className={`text-lg font-bold tabular-nums ${tone === "good" ? "text-emerald-600 dark:text-emerald-400" : tone === "bad" ? "text-destructive" : tone === "warn" ? "text-amber-600" : ""}`}>{value}</div>
    </div>
  );
}

/* ── wave 65 — Homework tab (parent view): every assignment posted to the
    selected child's class, with submission state, teacher feedback and a
    one-tap submit for the family on the go. ── */
function ParentHomework({ child, onChanged }: { child: Child; onChanged: () => void }) {
  const { currentSchoolId } = useStore();
  const [submitting, setSubmitting] = useState<HwLite | null>(null);
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);

  const hw = child.homework ?? [];
  const open = hw.filter((h) => h.status === "OPEN");
  const outstanding = open.filter((h) => h.myStatus === "PENDING");
  const HW_CHIP: Record<string, string> = {
    PENDING: "bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30",
    SUBMITTED: "bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30",
    LATE: "bg-orange-500/15 text-orange-700 dark:text-orange-300 border-orange-500/30",
    GRADED: "bg-primary/15 text-primary border-primary/30",
    EXCUSED: "bg-zinc-500/15 text-zinc-600 dark:text-zinc-300 border-zinc-500/30",
  };

  async function submitHw() {
    if (!submitting) return;
    setBusy(true);
    try {
      const r = await apiPost<{ status: string }>(`/api/school/${currentSchoolId}/ops/portal`, {
        action: "homework.self-submit", homeworkId: submitting.id, studentId: child.id, note,
      });
      toast({
        title: r.status === "LATE" ? "Submitted — recorded as late" : "Homework submitted",
        description: r.status === "LATE"
          ? `The due date for "${submitting.title}" has passed — the teacher will still see it.`
          : `${child.name}'s teacher has been notified.`,
      });
      setSubmitting(null); setNote(""); onChanged();
    } catch (e: any) {
      toast({ title: "Could not submit", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader className="pb-2">
          <CardTitle className="text-base flex items-center gap-2"><BookPlus className="size-4" /> Homework — {child.name}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <div className="grid grid-cols-3 gap-3">
            <MiniStat label="Outstanding" value={outstanding.length} tone={outstanding.length ? "warn" : "good"} />
            <MiniStat label="Handed in" value={hw.filter((h) => h.myStatus === "SUBMITTED" || h.myStatus === "LATE").length} tone="good" />
            <MiniStat label="Marked" value={hw.filter((h) => h.myStatus === "GRADED").length} />
          </div>

          {!hw.length && (
            <EmptyState icon={<BookPlus className="size-8" />} title="No homework posted yet" hint="When a teacher posts an assignment for the class, it appears here — and you get an SMS." />
          )}

          {open.map((h) => {
            const dueIn = Math.ceil((new Date(h.dueDate).getTime() - Date.now()) / 86400000);
            return (
              <div key={h.id} className="rounded-xl border p-3.5">
                <div className="flex items-start justify-between gap-2">
                  <div className="min-w-0">
                    <div className="flex items-center gap-1.5 flex-wrap">
                      <Badge variant="secondary" className="text-[10px]">{h.subject}</Badge>
                      <span className={cn("inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium", HW_CHIP[h.myStatus] ?? "")}>{h.myStatus.toLowerCase()}</span>
                    </div>
                    <div className="font-semibold leading-snug mt-1.5">{h.title}</div>
                    <div className="text-xs text-muted-foreground mt-0.5">by {h.postedBy} · max {h.maxScore} marks</div>
                  </div>
                  <div className="text-right shrink-0">
                    <div className={cn("text-[11px] font-semibold", h.overdue ? "text-destructive" : dueIn <= 1 ? "text-amber-600 dark:text-amber-400" : "text-muted-foreground")}>
                      {h.overdue ? "overdue" : dueIn <= 0 ? "due today" : dueIn === 1 ? "due tomorrow" : `due in ${dueIn}d`}
                    </div>
                    <div className="text-[10px] text-muted-foreground">{fmtDate(h.dueDate)}</div>
                  </div>
                </div>
                <p className="text-sm mt-2 rounded-lg bg-secondary/40 border p-2.5 whitespace-pre-wrap leading-relaxed max-h-36 overflow-y-auto">{h.instructions}</p>
                {h.myFeedback && (
                  <div className="mt-2 rounded-lg border border-primary/30 bg-primary/5 p-2.5 text-sm">
                    <span className="font-medium">Teacher: </span>{h.myFeedback}
                    {h.myScore != null && <Badge className="ml-2 bg-primary">{h.myScore}/{h.maxScore}</Badge>}
                  </div>
                )}
                {h.myStatus === "PENDING" && (
                  <Button size="sm" className="mt-2.5 gap-1.5" onClick={() => { setSubmitting(h); setNote(""); }}>
                    <CheckCircle2 className="size-3.5" /> Submit for {child.name.split(" ")[0]}
                  </Button>
                )}
                {h.myNote && <div className="text-[11px] text-muted-foreground mt-2">Your note: &ldquo;{h.myNote}&rdquo;</div>}
              </div>
            );
          })}

          {hw.filter((h) => h.status === "CLOSED" || h.myStatus === "GRADED").length > 0 && (
            <div className="space-y-2 pt-1">
              <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Earlier work</div>
              {hw.filter((h) => h.status === "CLOSED" || h.myStatus === "GRADED").map((h) => (
                <div key={h.id} className="flex items-center justify-between gap-3 rounded-lg border p-2.5">
                  <div className="min-w-0">
                    <div className="text-sm font-medium truncate">{h.title}</div>
                    <div className="text-[11px] text-muted-foreground">{h.subject} · due {fmtDate(h.dueDate)}</div>
                  </div>
                  <div className="flex items-center gap-2 shrink-0">
                    <span className={cn("inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium", HW_CHIP[h.myStatus] ?? "")}>{h.myStatus.toLowerCase()}</span>
                    {h.myScore != null && <span className="text-sm font-bold tabular-nums">{h.myScore}/{h.maxScore}</span>}
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      <Dialog open={!!submitting} onOpenChange={(o) => !o && setSubmitting(null)}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><CheckCircle2 className="size-4.5" /> Submit — {submitting?.title}</DialogTitle>
            <DialogDescription>Confirm {child.name}&apos;s homework is done. Late submissions after the due date are flagged for the teacher.</DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <Label htmlFor="phw-note">Note for the teacher (optional)</Label>
            <Textarea id="phw-note" value={note} onChange={(e) => setNote(e.target.value)} rows={3} placeholder="e.g. Done — exercise book will be brought to school tomorrow." />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setSubmitting(null)}>Not yet</Button>
            <Button onClick={submitHw} disabled={busy} className="gap-1.5">
              {busy ? <Loader2 className="size-4 animate-spin" /> : <CheckCircle2 className="size-4" />} Submit homework
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

/* ── wave 77 — online payments & school shop ───────────────────────────── */

/** Poll `payment.check` every 3 seconds while a request id is set — the
 *  interval is cleared when the id clears (close / completion / failure) or
 *  when the dialog unmounts. Each result is tagged with the request id it
 *  belongs to so a stale poll from a previous payment is never applied. */
function usePaymentPoll(schoolId: string, requestId: string | null) {
  const [checked, setChecked] = useState<PolledRequest | null>(null);
  useEffect(() => {
    if (!requestId) return;
    let alive = true;
    const check = async () => {
      try {
        const res = await apiPost<{ request: CheckedRequest }>(`/api/school/${schoolId}/ops/portal`, { action: "payment.check", requestId });
        if (alive) setChecked({ id: requestId, data: res.request });
      } catch { /* transient — the next tick retries */ }
    };
    const t = setInterval(check, 3000);
    return () => { alive = false; clearInterval(t); };
  }, [schoolId, requestId]);
  return checked;
}

type PayLiveRequest = {
  clientReference: string; status: string; checkoutUrl: string | null; amount: number;
  appliedNote?: string | null; failureReason?: string | null;
};
type PolledRequest = { id: string; data: CheckedRequest };

/* Shared tracking panel inside the payment dialogs once a request has been
   created: reference, amount, status badge, then mode-specific instructions
   (sandbox pay page / MoMo prompt / hosted checkout) and the terminal
   success / failure states. */
function PaymentTrackingPanel({ request, payUrl, method, phone, sandbox }: {
  request: PayLiveRequest; payUrl: string | null;
  method: "MOMO_PROMPT" | "PAYMENT_LINK"; phone: string; sandbox: boolean;
}) {
  const st = PAY_STATUS[request.status] ?? PAY_STATUS.PENDING;
  const payPage = payUrl || `/pay/${request.clientReference}`;
  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between gap-3 rounded-lg border px-3 py-2.5">
        <div className="min-w-0">
          <div className="text-[10px] uppercase tracking-wide text-muted-foreground">Reference</div>
          <div className="font-mono text-sm truncate">{request.clientReference}</div>
        </div>
        <div className="text-right shrink-0">
          <div className="text-base font-bold tabular-nums">{fmtCedi(request.amount)}</div>
          <Badge className={st.className}>{st.label}</Badge>
        </div>
      </div>
      {request.status === "PENDING" && (
        <>
          {sandbox && (
            <div className="rounded-lg border border-amber-500/40 bg-amber-500/10 p-3 flex items-start gap-2">
              <AlertTriangle className="size-4 text-amber-600 shrink-0 mt-0.5" />
              <div className="flex-1 space-y-2">
                <p className="text-sm">Sandbox: no {PROVIDER_NAMES[request.provider] ?? "gateway"} keys configured — complete the payment on the payment page.</p>
                <a href={payPage} target="_blank" rel="noreferrer">
                  <Button size="sm" variant="outline" className="gap-1.5"><ExternalLink className="size-3.5" /> Open payment page</Button>
                </a>
              </div>
            </div>
          )}
          {!sandbox && method === "MOMO_PROMPT" && (
            <p className="text-sm text-muted-foreground">Approve the MoMo prompt on <b className="text-foreground">{phone || "your phone"}</b> — this page updates automatically.</p>
          )}
          {!sandbox && method === "PAYMENT_LINK" && (
            <div className="flex items-center justify-between gap-2 rounded-lg border p-3">
              <span className="text-sm text-muted-foreground">A secure {PROVIDER_NAMES[request.provider] ?? ""} checkout page is ready.</span>
              {request.checkoutUrl
                ? <a href={request.checkoutUrl} target="_blank" rel="noreferrer"><Button size="sm" className="gap-1.5 shrink-0"><ExternalLink className="size-3.5" /> Open secure checkout</Button></a>
                : <a href={payPage} target="_blank" rel="noreferrer"><Button size="sm" variant="outline" className="gap-1.5 shrink-0"><ExternalLink className="size-3.5" /> Open payment page</Button></a>}
            </div>
          )}
          <p className="flex items-center gap-1.5 text-xs text-muted-foreground"><Loader2 className="size-3 animate-spin" /> Checking every few seconds — you can close this and come back later.</p>
        </>
      )}
      {request.status === "COMPLETED" && (
        <div className="rounded-lg border border-emerald-500/40 bg-emerald-500/10 p-3 flex items-start gap-2">
          <CheckCircle2 className="size-4 text-emerald-600 shrink-0 mt-0.5" />
          <div>
            <div className="text-sm font-medium">Payment received — thank you.</div>
            {request.appliedNote && <div className="text-xs text-muted-foreground mt-0.5">{request.appliedNote}</div>}
          </div>
        </div>
      )}
      {(request.status === "FAILED" || request.status === "CANCELLED") && (
        <div className="rounded-lg border border-destructive/40 bg-destructive/10 p-3 flex items-start gap-2">
          <AlertTriangle className="size-4 text-destructive shrink-0 mt-0.5" />
          <div>
            <div className="text-sm font-medium">{request.status === "FAILED" ? "The payment did not go through." : "The payment was cancelled."}</div>
            {request.failureReason && <div className="text-xs text-muted-foreground mt-0.5">{request.failureReason}</div>}
          </div>
        </div>
      )}
    </div>
  );
}

/* Shared online-payment dialog (fees invoice / wallet top-up / shop order).
   Form → create the payment request → tracking panel with the 3-second poll.
   Money enters as GHS text and leaves as integer cents (Math.round). */
function PayDialog({ open, onOpenChange, target, schoolId, providers, onDone }: {
  open: boolean; onOpenChange: (o: boolean) => void;
  target: PayTarget | null; schoolId: string;
  providers: string[]; onDone: () => void;
}) {
  const sandbox = providers.length === 0;
  const [provider, setProvider] = useState("HUBTEL");
  const [method, setMethod] = useState<"MOMO_PROMPT" | "PAYMENT_LINK">("MOMO_PROMPT");
  const [phone, setPhone] = useState("");
  const [amountGhs, setAmountGhs] = useState("");
  const [busy, setBusy] = useState(false);
  const [active, setActive] = useState<{ request: PayRequest; payUrl: string | null } | null>(null);
  const [pollId, setPollId] = useState<string | null>(null);
  const [result, setResult] = useState<CheckedRequest | null>(null);
  const settledRef = useRef(false);
  const checked = usePaymentPoll(schoolId, pollId);

  // fresh form each time the dialog opens; stop the poll when it closes
  useEffect(() => {
    if (open && target) {
      settledRef.current = false;
      setProvider(providers[0] ?? "HUBTEL");
      setMethod("MOMO_PROMPT");
      setPhone(target.phone ?? "");
      setAmountGhs(target.kind === "fees" ? (target.balance / 100).toFixed(2) : "");
      setBusy(false);
      setActive(null); setPollId(null); setResult(null);
    } else if (!open) {
      setActive(null); setPollId(null); setResult(null);
    }
  }, [open, target]);

  // react to poll results — any terminal status stops the interval
  useEffect(() => {
    if (!checked || checked.id !== pollId) return;
    const c = checked.data;
    if (c.status === "COMPLETED" || c.status === "FAILED" || c.status === "CANCELLED") {
      setResult(c);
      setPollId(null);
      if (c.status === "COMPLETED" && !settledRef.current) {
        settledRef.current = true;
        toast({ title: "Payment received — thank you", description: c.appliedNote ?? undefined });
        onDone();
      }
    }
  }, [checked, pollId, onDone]);

  async function submit() {
    if (!target) return;
    let amountCents: number | undefined;
    if (target.kind === "fees") {
      amountCents = Math.round(Number(amountGhs) * 100);
      if (!Number.isFinite(amountCents) || amountCents <= 0) { toast({ title: "Enter an amount", description: "How much of the balance are you paying?" }); return; }
      if (amountCents > target.balance) { toast({ title: "Amount too high", description: `The balance on ${target.invoiceNo} is ${fmtCedi(target.balance)}.` }); return; }
    } else if (target.kind === "wallet") {
      amountCents = Math.round(Number(amountGhs) * 100);
      if (!Number.isFinite(amountCents) || amountCents < 50) { toast({ title: "Minimum ₵0.50", description: "Top-ups start at fifty pesewas." }); return; }
    }
    setBusy(true);
    try {
      // Paystack & Flutterwave collect on their hosted checkouts (links)
      const effMethod = provider === "HUBTEL" ? method : "PAYMENT_LINK";
      const body = target.kind === "fees"
        ? { action: "fees.pay-online", studentId: target.studentId, invoiceId: target.invoiceId, amountCents, method: effMethod, provider, payerMsisdn: phone || undefined }
        : target.kind === "wallet"
          ? { action: "wallet.topup", studentId: target.studentId, walletKind: target.walletKind, amountCents, method: effMethod, provider, payerMsisdn: phone || undefined }
          : { action: "shop.order-pay", orderId: target.orderId, method: effMethod, provider, payerMsisdn: phone || undefined };
      const res = await apiPost<{ ok: boolean; request: PayRequest; payUrl: string | null }>(`/api/school/${schoolId}/ops/portal`, body);
      settledRef.current = false;
      setResult(null);
      setActive({ request: res.request, payUrl: res.payUrl ?? null });
      setPollId(res.request.id);
    } catch (e: any) {
      toast({ title: "Could not start the payment", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  if (!target) return null;
  const title = target.kind === "fees" ? "Pay school fees online"
    : target.kind === "wallet" ? (target.walletKind === "TRANSPORT" ? "Top up transport wallet" : "Top up tuck / meals wallet")
    : `Pay for order ${target.orderNo}`;

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Wallet className="size-4.5" /> {title}</DialogTitle>
          <DialogDescription>
            {target.kind === "fees" ? `${target.studentName} · invoice ${target.invoiceNo}` : target.studentName}
          </DialogDescription>
        </DialogHeader>
        {active ? (
          <>
            <PaymentTrackingPanel request={result ?? active.request} payUrl={active.payUrl} method={provider === "HUBTEL" ? method : "PAYMENT_LINK"} phone={phone} sandbox={sandbox} />
            <DialogFooter>
              {(result?.status === "FAILED" || result?.status === "CANCELLED") && (
                <Button variant="outline" onClick={() => { setActive(null); setResult(null); settledRef.current = false; }}>Try again</Button>
              )}
              <Button variant="outline" onClick={() => onOpenChange(false)}>Close</Button>
            </DialogFooter>
          </>
        ) : (
          <>
            <div className="grid gap-3">
              <GatewayPicker providers={providers} value={provider} onChange={(v) => { setProvider(v); if (v !== "HUBTEL") setMethod("PAYMENT_LINK"); }} />
              {target.kind === "shop" ? (
                <div className="flex items-center justify-between gap-2 rounded-lg border px-3 py-2.5">
                  <div className="min-w-0">
                    <div className="text-sm font-medium">Order {target.orderNo}</div>
                    <div className="text-xs text-muted-foreground">{target.studentName}</div>
                  </div>
                  <div className="text-base font-bold tabular-nums shrink-0">{fmtCedi(target.total)}</div>
                </div>
              ) : (
                <div className="space-y-1.5">
                  <Label>Amount (GHS)</Label>
                  <Input type="number" inputMode="decimal" min={0.5} step="0.01" value={amountGhs} onChange={(e) => setAmountGhs(e.target.value)} placeholder={target.kind === "wallet" ? "e.g. 20.00" : undefined} />
                  <p className="text-[11px] text-muted-foreground">
                    {target.kind === "fees" ? `Balance ${fmtCedi(target.balance)} — partial payments are fine.` : "Minimum ₵0.50 per transaction."}
                  </p>
                </div>
              )}
              <div className="space-y-1.5">
                <Label>Pay with</Label>
                {provider === "HUBTEL" ? (
                  <Select value={method} onValueChange={(v) => setMethod(v === "PAYMENT_LINK" ? "PAYMENT_LINK" : "MOMO_PROMPT")}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="MOMO_PROMPT">MoMo prompt — approve on your phone</SelectItem>
                      <SelectItem value="PAYMENT_LINK">Payment link — open a checkout page</SelectItem>
                    </SelectContent>
                  </Select>
                ) : (
                  <p className="text-xs text-muted-foreground rounded-lg border px-3 py-2">Secure {PROVIDER_NAMES[provider]} checkout — card, bank transfer &amp; mobile money on one page.</p>
                )}
              </div>
              <div className="space-y-1.5">
                <Label>Mobile-money number</Label>
                <Input value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="e.g. 024 123 4567" />
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
              <Button disabled={busy} onClick={submit} className="gap-1.5">
                {busy ? <Loader2 className="size-4 animate-spin" /> : <Wallet className="size-4" />} Pay now
              </Button>
            </DialogFooter>
          </>
        )}
      </DialogContent>
    </Dialog>
  );
}

/* ── wave 77 — School shop (parent view): browse the catalog, fill a basket,
   then check out — pay online now or at the school counter. Recent orders
   with live statuses sit underneath. ── */
function ParentShop({ data, schoolId, defaultStudentId, onChanged }: {
  data: ParentData; schoolId: string; defaultStudentId: string; onChanged: () => void;
}) {
  const items = data.shopItems ?? [];
  const orders = data.shopOrders ?? [];
  const [cat, setCat] = useState("ALL");
  const [qtys, setQtys] = useState<Record<string, string>>({});
  const [cart, setCart] = useState<{ itemId: string; qty: number }[]>([]);
  const [checkoutOpen, setCheckoutOpen] = useState(false);
  const [payTarget, setPayTarget] = useState<PayTarget | null>(null);
  const [cancelling, setCancelling] = useState<string | null>(null);

  const shown = cat === "ALL" ? items : items.filter((i) => i.category === cat);
  const lines = cart
    .map((c) => ({ qty: c.qty, item: items.find((i) => i.id === c.itemId) }))
    .filter((l): l is { qty: number; item: ShopItem } => !!l.item);
  const total = lines.reduce((s, l) => s + l.item.price * l.qty, 0);

  function addToCart(item: ShopItem) {
    if (item.stock === 0) return;
    const want = Math.max(1, Math.round(Number(qtys[item.id]) || 1));
    const add = item.stock == null ? want : Math.min(want, item.stock);
    setCart((c) => {
      const cur = c.find((x) => x.itemId === item.id);
      if (!cur) return [...c, { itemId: item.id, qty: add }];
      const next = item.stock == null ? cur.qty + add : Math.min(cur.qty + add, item.stock);
      return c.map((x) => (x.itemId === item.id ? { ...x, qty: next } : x));
    });
    toast({ title: "Added to your basket", description: `${item.name} × ${add}` });
  }

  async function cancelOrder(orderId: string) {
    setCancelling(orderId);
    try {
      await apiPost(`/api/school/${schoolId}/ops/portal`, { action: "shop.order-cancel", orderId });
      toast({ title: "Order cancelled", description: "Nothing was charged — the items are back in the shop." });
      onChanged();
    } catch (e: any) {
      toast({ title: "Could not cancel the order", description: e.message, variant: "destructive" });
    } finally { setCancelling(null); }
  }

  if (!items.length && !orders.length) {
    return <EmptyState icon={<Store className="size-8" />} title="The shop is quiet" hint="Uniforms, stationery and books the school sells will appear here." />;
  }

  return (
    <div className="space-y-4">
      {/* category filter + payments-mode chip */}
      <div className="flex flex-wrap items-center justify-between gap-2">
        <Select value={cat} onValueChange={setCat}>
          <SelectTrigger className="w-48"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="ALL">All items</SelectItem>
            {Object.entries(SHOP_CATS).map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}
          </SelectContent>
        </Select>
        {data.paymentsMode === "HUBTEL"
          ? <Badge className="gap-1.5 bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"><CheckCircle2 className="size-3" /> Hubtel live</Badge>
          : <Badge className="gap-1.5 bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"><AlertTriangle className="size-3" /> Sandbox mode</Badge>}
      </div>

      {/* basket — a drawer-lite card at the top of the tab */}
      {lines.length > 0 && (
        <Card>
          <CardHeader className="pb-2 flex-row items-center justify-between space-y-0">
            <CardTitle className="text-base flex items-center gap-2"><ShoppingCart className="size-4" /> Your basket</CardTitle>
            <div className="text-base font-bold tabular-nums">{fmtCedi(total)}</div>
          </CardHeader>
          <CardContent className="space-y-2">
            {lines.map((l) => (
              <div key={l.item.id} className="flex items-center justify-between gap-2 rounded-lg border px-3 py-2">
                <div className="min-w-0">
                  <div className="text-sm font-medium truncate">{l.item.name}</div>
                  <div className="text-xs text-muted-foreground">{l.qty} × {fmtCedi(l.item.price)}</div>
                </div>
                <Button size="sm" variant="ghost" className="h-8 gap-1 text-xs shrink-0" onClick={() => setCart((c) => c.filter((x) => x.itemId !== l.item.id))}>
                  <Trash2 className="size-3" /> Remove
                </Button>
              </div>
            ))}
            <Button className="w-full gap-1.5" onClick={() => setCheckoutOpen(true)}>
              <Send className="size-4" /> Checkout — {fmtCedi(total)}
            </Button>
          </CardContent>
        </Card>
      )}

      {/* catalog */}
      {items.length > 0 && (
        shown.length ? (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            {shown.map((item) => (
              <Card key={item.id} className="flex flex-col">
                <CardContent className="p-3.5 flex flex-col gap-2.5 flex-1">
                  <div className="flex items-start justify-between gap-2">
                    <div className="text-sm font-medium leading-snug">{item.name}</div>
                    <Badge variant="outline" className="text-[9px] shrink-0">{SHOP_CATS[item.category] ?? item.category}</Badge>
                  </div>
                  <div className="mt-auto flex items-baseline justify-between gap-2">
                    <div className="text-lg font-bold tabular-nums">{fmtCedi(item.price)}</div>
                    {item.stock != null && (
                      <div className={cn("text-[11px]", item.stock === 0 ? "text-destructive font-medium" : "text-muted-foreground")}>
                        {item.stock === 0 ? "Out of stock" : `${item.stock} in stock`}
                      </div>
                    )}
                  </div>
                  <div className="flex items-center gap-2">
                    <Input
                      type="number" inputMode="numeric" min={1} max={item.stock ?? undefined}
                      value={qtys[item.id] ?? "1"}
                      onChange={(e) => setQtys((q) => ({ ...q, [item.id]: e.target.value }))}
                      className="h-9 w-16 shrink-0" aria-label={`Quantity — ${item.name}`}
                    />
                    <Button className="flex-1 gap-1.5" disabled={item.stock === 0} onClick={() => addToCart(item)}>
                      <ShoppingCart className="size-4" /> Add
                    </Button>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        ) : <EmptyState title="Nothing in this category yet" hint="Try another category — the school restocks through the term." />
      )}

      {/* my orders */}
      <Card>
        <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><Package className="size-4" /> My orders</CardTitle></CardHeader>
        <CardContent>
          {orders.length ? (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Order</TableHead>
                  <TableHead>For</TableHead>
                  <TableHead className="hidden md:table-cell">Items</TableHead>
                  <TableHead>Total</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead />
                </TableRow>
              </TableHeader>
              <TableBody>
                {orders.map((o) => {
                  const st = ORDER_STATUS[o.status] ?? ORDER_STATUS.PENDING_PAYMENT;
                  const guardianPhone = data.children.find((c) => c.id === o.studentId)?.guardianPhone ?? "";
                  return (
                    <TableRow key={o.id}>
                      <TableCell>
                        <div className="font-medium">{o.orderNo}</div>
                        <div className="text-xs text-muted-foreground">{fmtDate(o.createdAt)}{o.paymentMethod ? ` · ${ORDER_PAYMENT[o.paymentMethod] ?? o.paymentMethod}` : ""}</div>
                        {o.deliveryNote && <div className="text-[11px] text-muted-foreground mt-0.5 max-w-56">Note: {o.deliveryNote}</div>}
                        {o.cancelReason && <div className="text-[11px] text-destructive mt-0.5 max-w-56">{o.cancelReason}</div>}
                      </TableCell>
                      <TableCell className="text-sm">{o.student}</TableCell>
                      <TableCell className="hidden md:table-cell text-xs text-muted-foreground">{o.items.map((i) => `${i.qty}× ${i.name}`).join(" · ")}</TableCell>
                      <TableCell className="tabular-nums font-semibold">{fmtCedi(o.total)}</TableCell>
                      <TableCell><Badge className={st.className}>{st.label}</Badge></TableCell>
                      <TableCell>
                        {o.status === "PENDING_PAYMENT" && (
                          <div className="flex flex-col items-stretch gap-1 min-w-24">
                            <Button size="sm" className="h-7 gap-1 text-xs" onClick={() => setPayTarget({ kind: "shop", orderId: o.id, orderNo: o.orderNo, total: o.total, studentName: o.student, phone: guardianPhone })}>
                              <Wallet className="size-3" /> Pay online
                            </Button>
                            <Button size="sm" variant="ghost" className="h-7 gap-1 text-xs text-destructive" disabled={cancelling === o.id} onClick={() => cancelOrder(o.id)}>
                              {cancelling === o.id && <Loader2 className="size-3 animate-spin" />} Cancel
                            </Button>
                          </div>
                        )}
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          ) : <EmptyState title="No orders yet" hint="Add items to your basket and check out — the shop delivers to the child at school." />}
        </CardContent>
      </Card>

      <ShopCheckoutDialog
        open={checkoutOpen}
        onOpenChange={setCheckoutOpen}
        lines={lines}
        total={total}
        kids={data.children}
        defaultStudentId={defaultStudentId}
        schoolId={schoolId}
        providers={data.paymentsProviders ?? []}
        onPlaced={() => { setCart([]); onChanged(); }}
      />

      {/* pay an unpaid order straight from the list */}
      <PayDialog
        open={!!payTarget}
        onOpenChange={(o) => { if (!o) setPayTarget(null); }}
        target={payTarget}
        schoolId={schoolId}
        providers={data.paymentsProviders ?? []}
        onDone={onChanged}
      />
    </div>
  );
}

/* Checkout dialog for the basket: choose the ward, add a delivery note, then
   either pay online now (order → payment request → tracking) or place the
   order and pay at the school counter. */
function ShopCheckoutDialog({ open, onOpenChange, lines, total, kids, defaultStudentId, schoolId, providers, onPlaced }: {
  open: boolean; onOpenChange: (o: boolean) => void;
  lines: { item: ShopItem; qty: number }[]; total: number;
  kids: Child[]; defaultStudentId: string; schoolId: string;
  providers: string[];
  onPlaced: () => void;
}) {
  const sandbox = providers.length === 0;
  const [studentId, setStudentId] = useState(defaultStudentId);
  const [deliveryNote, setDeliveryNote] = useState("");
  const [payHow, setPayHow] = useState<"ONLINE" | "COUNTER">("ONLINE");
  const [provider, setProvider] = useState("HUBTEL");
  const [method, setMethod] = useState<"MOMO_PROMPT" | "PAYMENT_LINK">("MOMO_PROMPT");
  const [phone, setPhone] = useState("");
  const [busy, setBusy] = useState(false);
  const [placed, setPlaced] = useState<{ orderNo: string; student: string } | null>(null);
  const [orderTotal, setOrderTotal] = useState<number | null>(null);
  const [active, setActive] = useState<{ request: PayRequest; payUrl: string | null } | null>(null);
  const [pollId, setPollId] = useState<string | null>(null);
  const [result, setResult] = useState<CheckedRequest | null>(null);
  const settledRef = useRef(false);
  const checked = usePaymentPoll(schoolId, pollId);

  // fresh form each time the dialog opens; stop the poll when it closes
  useEffect(() => {
    if (open) {
      setStudentId(defaultStudentId);
      setDeliveryNote("");
      setPayHow("ONLINE");
      setProvider(providers[0] ?? "HUBTEL");
      setMethod("MOMO_PROMPT");
      setPhone(kids.find((c) => c.id === defaultStudentId)?.guardianPhone ?? kids[0]?.guardianPhone ?? "");
      setBusy(false);
      setPlaced(null);
      setOrderTotal(null);
      setActive(null); setPollId(null); setResult(null);
      settledRef.current = false;
    } else {
      setActive(null); setPollId(null); setResult(null);
    }
  }, [open]);

  // react to poll results — any terminal status stops the interval
  useEffect(() => {
    if (!checked || checked.id !== pollId) return;
    const c = checked.data;
    if (c.status === "COMPLETED" || c.status === "FAILED" || c.status === "CANCELLED") {
      setResult(c);
      setPollId(null);
      if (c.status === "COMPLETED" && !settledRef.current) {
        settledRef.current = true;
        toast({ title: "Payment received — thank you", description: c.appliedNote ?? undefined });
        onPlaced();
      }
    }
  }, [checked, pollId, onPlaced]);

  async function submit() {
    const kid = kids.find((c) => c.id === studentId);
    if (!kid) { toast({ title: "Choose a child", description: "Pick which child the order is for." }); return; }
    setBusy(true);
    try {
      const res = await apiPost<{ ok: boolean; order: { id: string; orderNo: string; total: number; items: { name: string; qty: number; price: number }[] } }>(
        `/api/school/${schoolId}/ops/portal`,
        {
          action: "shop.order-create", studentId,
          items: lines.map((l) => ({ itemId: l.item.id, qty: l.qty })),
          deliveryNote: deliveryNote.trim() || undefined,
        },
      );
      setOrderTotal(res.order.total);
      if (payHow === "COUNTER") {
        setPlaced({ orderNo: res.order.orderNo, student: kid.name });
        onPlaced();
      } else {
        let payFailed = false;
        try {
          const pr = await apiPost<{ ok: boolean; request: PayRequest; payUrl: string | null }>(
            `/api/school/${schoolId}/ops/portal`,
            { action: "shop.order-pay", orderId: res.order.id, method: provider === "HUBTEL" ? method : "PAYMENT_LINK", provider, payerMsisdn: phone || undefined },
          );
          settledRef.current = false;
          setActive({ request: pr.request, payUrl: pr.payUrl ?? null });
          setPollId(pr.request.id);
        } catch (e: any) {
          payFailed = true;
          toast({ title: "Order placed — but the payment could not start", description: `${e.message} You can pay for it under My orders.`, variant: "destructive" });
        }
        onPlaced(); // the order exists now — clear the basket & refresh either way
        if (payFailed) onOpenChange(false);
      }
    } catch (e: any) {
      toast({ title: "Could not place the order", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><ShoppingCart className="size-4.5" /> Checkout — {fmtCedi(orderTotal ?? total)}</DialogTitle>
          <DialogDescription>The shop packs the order and hands it to the child at school.</DialogDescription>
        </DialogHeader>
        {placed ? (
          <>
            <div className="rounded-lg border border-emerald-500/40 bg-emerald-500/10 p-3 flex items-start gap-2">
              <CheckCircle2 className="size-4 text-emerald-600 shrink-0 mt-0.5" />
              <div>
                <div className="text-sm font-medium">Order {placed.orderNo} placed — pay at the school office; the shop will deliver to {placed.student}.</div>
                <div className="text-xs text-muted-foreground mt-0.5">Keep the order number handy when you come in.</div>
              </div>
            </div>
            <DialogFooter>
              <Button onClick={() => onOpenChange(false)}>Done</Button>
            </DialogFooter>
          </>
        ) : active ? (
          <>
            <PaymentTrackingPanel request={result ?? active.request} payUrl={active.payUrl} method={provider === "HUBTEL" ? method : "PAYMENT_LINK"} phone={phone} sandbox={sandbox} />
            <DialogFooter>
              {(result?.status === "FAILED" || result?.status === "CANCELLED") && (
                <Button variant="outline" onClick={() => { setActive(null); setResult(null); settledRef.current = false; }}>Try again</Button>
              )}
              <Button variant="outline" onClick={() => onOpenChange(false)}>Close</Button>
            </DialogFooter>
          </>
        ) : (
          <>
            <div className="grid gap-3">
              <div className="space-y-1.5">
                <Label>For which child</Label>
                <Select value={studentId} onValueChange={setStudentId}>
                  <SelectTrigger><SelectValue placeholder="Choose a child" /></SelectTrigger>
                  <SelectContent>
                    {kids.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}{c.classroom ? ` — ${c.classroom}` : ""}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5">
                <Label>Delivery note <span className="text-muted-foreground font-normal">(optional)</span></Label>
                <Input value={deliveryNote} onChange={(e) => setDeliveryNote(e.target.value)} placeholder="e.g. leave with the class teacher of Basic 4 Gold" />
              </div>
              <div className="space-y-1.5">
                <Label>Payment</Label>
                <Select value={payHow} onValueChange={(v) => setPayHow(v === "COUNTER" ? "COUNTER" : "ONLINE")}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="ONLINE">Pay online now (MoMo)</SelectItem>
                    <SelectItem value="COUNTER">Pay at the school counter</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              {payHow === "ONLINE" && (
                <>
                  <GatewayPicker providers={providers} value={provider} onChange={(v) => { setProvider(v); if (v !== "HUBTEL") setMethod("PAYMENT_LINK"); }} />
                  <div className="space-y-1.5">
                    <Label>Pay with</Label>
                    {provider === "HUBTEL" ? (
                      <Select value={method} onValueChange={(v) => setMethod(v === "PAYMENT_LINK" ? "PAYMENT_LINK" : "MOMO_PROMPT")}>
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                          <SelectItem value="MOMO_PROMPT">MoMo prompt — approve on your phone</SelectItem>
                          <SelectItem value="PAYMENT_LINK">Payment link — open a checkout page</SelectItem>
                        </SelectContent>
                      </Select>
                    ) : (
                      <p className="text-xs text-muted-foreground rounded-lg border px-3 py-2">Secure {PROVIDER_NAMES[provider]} checkout — card, bank transfer &amp; mobile money on one page.</p>
                    )}
                  </div>
                  <div className="space-y-1.5">
                    <Label>Mobile-money number</Label>
                    <Input value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="e.g. 024 123 4567" />
                  </div>
                </>
              )}
              <div className="flex items-center justify-between gap-3 rounded-lg border border-primary/30 bg-primary/5 px-3 py-2.5">
                <div className="text-xs text-muted-foreground min-w-0 truncate">{lines.map((l) => `${l.qty}× ${l.item.name}`).join(" · ")}</div>
                <div className="text-base font-bold tabular-nums shrink-0">{fmtCedi(orderTotal ?? total)}</div>
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
              <Button disabled={busy || !studentId || !lines.length} onClick={submit} className="gap-1.5">
                {busy ? <Loader2 className="size-4 animate-spin" /> : <Send className="size-4" />}
                {payHow === "ONLINE" ? "Place order & pay" : "Place order"}
              </Button>
            </DialogFooter>
          </>
        )}
      </DialogContent>
    </Dialog>
  );
}

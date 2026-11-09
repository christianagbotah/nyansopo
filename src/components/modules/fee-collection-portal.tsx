"use client";
/* ── Fee Collection Portal — CI3 fee_collection_portal.php parity ──────────
   The full-screen counter view: KPI strip (today's takings per fee type +
   total), learner search, prepaid/arrears wallet cards with per-item
   breakdown, gradient fee cards (one per fee module, per-item prepaid and
   arrears chips, weekly water state, opt-in breakfasts, approved opt-outs,
   route-based transport with direction + already-boarded), tendered/change
   rail, held baskets, printable dining coupons and receipt printing. ── */
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost, apiPostQueueable } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { DateField } from "@/components/date-field";
import { EmptyState, LoadingBlock } from "@/components/ui-helpers";
import { CEDI, fmtCedi, fmtDate, fmtDateTime, PAYMENT_LABELS } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import { ReceiptDialog } from "@/components/modules/daily-services";
import {
  UtensilsCrossed, Droplets, BookOpenCheck, Coffee, Bus, Wallet, Search, Printer, UserCheck,
  PauseCircle, PlayCircle, Trash2, Ticket, TrendingUp, HandCoins, AlertTriangle, CheckCircle2,
  GraduationCap, Percent, X, Banknote, CircleDollarSign,
} from "lucide-react";

/* ── types ── */
type ServiceKpi = {
  id: string; name: string; category: string; chargeMode: string; active: boolean; price: number;
  today: { billed: number; collected: number; count: number; owedCount: number };
};
type KpiData = {
  date: string;
  services: ServiceKpi[];
  today: { charged: number; collected: number; owed: number; count: number };
  posPayments: { id: string; receiptNo: string; amount: number; method: string; paidAt: string; student: string }[];
  settings: { classrooms: { id: string; name: string }[] };
};
type PortalCard = {
  kind: "DAILY"; serviceId: string; name: string; category: string;
  priceCents: number; discountedCents: number; discountCents: number; discountLabel: string | null;
  hidden: boolean; optional: boolean;
  optOut: { status: string; reason: string | null } | null;
  optedIn: { status: string; reason: string | null } | null;
  arrearsCents: number; prepaidCents: number;
  weekly: boolean; weeklyBilledThisWeek: boolean; weekdays: string;
  today: { amountCents: number; status: string; usage: string | null; paymentId: string | null } | null;
  chargeMode: string;
};
type PortalInfo = {
  date: string;
  student: { id: string; name: string; index: string; classroom: string; classroomId: string; guardianName: string | null; guardianPhone: string | null };
  attendance: { status: string; excusedReason: string | null; takenBy: string | null } | null;
  wallet: { prepaidCents: number; transportCents: number; itemPrepaidTotal: number; arrearsTotal: number };
  discounts: { name: string; method: string; value: number; scoped: boolean }[];
  cards: PortalCard[];
  transport: { routeName: string; stopName: string | null; onewayCents: number; bothCents: number; arrearsCents: number; transportWalletCents: number; todayUsage: string | null } | null;
  invoices: { invoiceId: string; invoiceNo: string; term: string | null; totalCents: number; paidCents: number; dueCents: number; dueDate: string | null; status: string; items: { name: string; amountCents: number }[] }[];
  recentPayments: { id: string; receiptNo: string; amountCents: number; method: string; category: string; paidAt: string }[];
};
type CouponRow = { student: string; studentCode: string; classroom: string | null; amountCents: number; paymentType: string };
type HeldBasket = {
  key: string; studentId: string; studentName: string; classroom: string;
  amounts: Record<string, string>; invoiceAmounts?: Record<string, string>;
  date: string; method: string; usage: string; boarded: boolean; at: string;
};

/* ── legacy CI3 metro gradients — one vivid card per fee module ── */
const CAT_GRADIENT: Record<string, string> = {
  MEAL: "bg-gradient-to-br from-pink-500 to-rose-600",
  DRINK: "bg-gradient-to-br from-cyan-500 to-sky-600",
  CLASS: "bg-gradient-to-br from-violet-500 to-purple-700",
  CARE: "bg-gradient-to-br from-fuchsia-500 to-pink-600",
  TRANSPORT: "bg-gradient-to-br from-indigo-500 to-purple-600",
  OTHER: "bg-gradient-to-br from-slate-500 to-slate-700",
};
const CAT_ICON: Record<string, any> = { MEAL: UtensilsCrossed, DRINK: Droplets, CLASS: BookOpenCheck, CARE: Coffee, TRANSPORT: Bus, OTHER: Wallet };
const CAT_ORDER: Record<string, number> = { MEAL: 0, DRINK: 1, CLASS: 2, CARE: 3, TRANSPORT: 4, OTHER: 5 };

const todayStr = () => new Date().toISOString().slice(0, 10);
const refHint = (m: string) => (m === "CHEQUE" ? "Cheque number" : m.startsWith("MOMO") ? "MoMo transaction ID — from the parent's wallet SMS" : "Reference printed on the receipt");

const USE_OPTS = [
  { v: "BOTH", label: "Both ways" },
  { v: "MORNING", label: "Morning only" },
  { v: "AFTERNOON", label: "Afternoon only" },
  { v: "NOT_USING", label: "Not using bus" },
];

export function FeeCollectionPortal({ online, canInvoice, onDone }: { online: boolean; canInvoice: boolean; onDone?: () => void }) {
  const { currentSchoolId } = useStore();
  const S = currentSchoolId ?? "";

  /* ── KPI strip data (today's takings per fee type) ── */
  const [kpi, setKpi] = useState<KpiData | null>(null);
  const loadKpi = useCallback(() => {
    if (!S) return;
    apiGet<KpiData>(`/api/school/${S}/ops/daily?date=${todayStr()}`)
      .then(setKpi)
      .catch(() => setKpi(null));
  }, [S]);
  useEffect(() => { loadKpi(); }, [loadKpi]);

  /* ── learner picker (server search, select2 parity) ── */
  const [query, setQuery] = useState("");
  const [matches, setMatches] = useState<{ id: string; name: string; nyansapoIndex: string; classroom: string }[]>([]);
  const [searchOpen, setSearchOpen] = useState(false);
  const searchRef = useRef<HTMLInputElement | null>(null);
  useEffect(() => {
    if (!S || !query.trim()) { setMatches([]); return; }
    const t = setTimeout(() => {
      apiGet<{ students: { id: string; name: string; nyansapoIndex: string; classroom: string }[] }>(`/api/school/${S}/students?search=${encodeURIComponent(query.trim())}`)
        .then((d) => { setMatches((d.students ?? []).slice(0, 8)); setSearchOpen(true); })
        .catch(() => setMatches([]));
    }, 200);
    return () => clearTimeout(t);
  }, [query, S]);

  /* ── the loaded learner + portal payload ── */
  const [date, setDate] = useState(todayStr());
  const [info, setInfo] = useState<PortalInfo | null>(null);
  const [infoLoading, setInfoLoading] = useState(false);
  const loadInfo = useCallback((studentId: string, on: string) => {
    if (!S) return;
    setInfoLoading(true);
    apiGet<PortalInfo>(`/api/school/${S}/ops/cashier?action=student-info&studentId=${studentId}&date=${on}`)
      .then((d) => {
        setInfo(d);
        // prefill: arrears first (CI3 apply_payment), discounted rate otherwise
        const pre: Record<string, string> = {};
        const on_: Record<string, boolean> = {};
        d.cards.forEach((c) => {
          if (c.optOut?.status === "APPROVED") return;
          if (c.weekly && c.weeklyBilledThisWeek) return; // water already paid this week
          if (c.optional && !(c.optedIn?.status === "APPROVED")) return; // breakfast off unless opted in
          if (c.arrearsCents > 0 || c.discountedCents > 0) {
            pre[c.serviceId] = ((c.arrearsCents > 0 ? c.arrearsCents : c.discountedCents) / 100).toFixed(2);
            on_[c.serviceId] = true;
          }
        });
        setAmounts(pre);
        setCollectOn(on_);
        const invPre: Record<string, string> = {};
        if (canInvoice) d.invoices.forEach((i) => { if (i.dueCents > 0) invPre[i.invoiceId] = (i.dueCents / 100).toFixed(2); });
        setInvoiceAmts(invPre);
        // transport: prefill today's usage when one already exists
        const tCard = d.cards.find((c) => c.category === "TRANSPORT");
        setUsage(tCard?.today?.usage && tCard.today.usage !== "NOT_USING" ? tCard.today.usage : "");
        setBoarded(false);
        setTendered("");
      })
      .catch((e) => toast({ title: "Could not load the learner", description: e.message, variant: "destructive" }))
      .finally(() => setInfoLoading(false));
  }, [S, canInvoice]);

  /* ── collection form state ── */
  const [amounts, setAmounts] = useState<Record<string, string>>({});
  const [collectOn, setCollectOn] = useState<Record<string, boolean>>({});
  const [invoiceAmts, setInvoiceAmts] = useState<Record<string, string>>({});
  const [usage, setUsage] = useState("");
  const [boarded, setBoarded] = useState(false);
  const [method, setMethod] = useState("CASH");
  const [reference, setReference] = useState("");
  const [tendered, setTendered] = useState("");
  const [markPresent, setMarkPresent] = useState(true);
  const [autoReceipt, setAutoReceipt] = useState(true);
  const [busy, setBusy] = useState(false);
  const [receipt, setReceipt] = useState<any>(null);
  const [heldOpen, setHeldOpen] = useState(false);
  const [held, setHeld] = useState<HeldBasket[]>([]);
  const [breakdown, setBreakdown] = useState<"prepaid" | "arrears" | null>(null);
  const [reportSvc, setReportSvc] = useState<ServiceKpi | null>(null);
  const [reportRows, setReportRows] = useState<CouponRow[] | null>(null);
  const [couponOpen, setCouponOpen] = useState(false);

  const fmt = fmtCedi;
  const cent = (s: string) => Math.round(parseFloat(s || "0") * 100) || 0;

  /* held baskets persist per school */
  useEffect(() => {
    if (!S) return;
    try { setHeld(JSON.parse(localStorage.getItem(`nyansapo-held-${S}`) ?? "[]")); } catch { setHeld([]); }
  }, [S]);
  const persistHeld = (rows: HeldBasket[]) => {
    setHeld(rows);
    if (S) localStorage.setItem(`nyansapo-held-${S}`, JSON.stringify(rows));
  };

  /* card eligibility mirrors CI3 toggleFee: hidden cards, approved opt-outs,
     weekly services already billed this week and optional (opt-in) modules */
  const cards = info?.cards.filter((c) => !c.hidden) ?? [];
  const activeCards = cards.filter((c) => c.optOut?.status !== "APPROVED");
  const offCards = cards.filter((c) => c.optOut?.status === "APPROVED");
  const cardPayable = (c: PortalCard) => !!collectOn[c.serviceId] && !(c.weekly && c.weeklyBilledThisWeek);
  const itemTotal = activeCards.reduce((s, c) => s + (cardPayable(c) ? cent(amounts[c.serviceId] ?? "") : 0), 0);
  const invoiceTotal = canInvoice ? (info?.invoices ?? []).reduce((s, i) => s + (i.dueCents > 0 ? cent(invoiceAmts[i.invoiceId] ?? "") : 0), 0) : 0;
  const grandTotal = itemTotal + invoiceTotal;
  const change = Math.max(0, cent(tendered) - grandTotal);
  const short = Math.max(0, grandTotal - cent(tendered));
  const backdated = date && date < todayStr();

  /* prepaid / arrears wallet totals for the learner (per-item parity) */
  const prepaidTotal = info ? info.wallet.itemPrepaidTotal + info.wallet.prepaidCents + info.wallet.transportCents : 0;
  const arrearsTotal = info ? info.wallet.arrearsTotal : 0;

  /* transport direction → fare preview (route-based, never class-based) */
  const transportCard = activeCards.find((c) => c.category === "TRANSPORT") ?? null;
  const usageFare = () => {
    if (!info?.transport) return 0;
    if (usage === "MORNING" || usage === "AFTERNOON") return info.transport.onewayCents;
    if (usage === "BOTH") return info.transport.bothCents || info.transport.onewayCents * 2;
    return 0;
  };
  const pickUsage = (v: string) => {
    setUsage(v);
    setBoarded(false);
    if (transportCard && v !== "NOT_USING") {
      const fare = v === "BOTH" ? (info?.transport?.bothCents || (info?.transport?.onewayCents ?? 0) * 2) : (info?.transport?.onewayCents ?? 0);
      const owed = transportCard.arrearsCents;
      setAmounts((a) => ({ ...a, [transportCard.serviceId]: ((owed > 0 ? owed : fare) / 100).toFixed(2) }));
      setCollectOn((o) => ({ ...o, [transportCard.serviceId]: true }));
    } else if (transportCard) {
      setAmounts((a) => { const n = { ...a }; delete n[transportCard.serviceId]; return n; });
      setCollectOn((o) => ({ ...o, [transportCard.serviceId]: false }));
    }
  };

  const toggleCard = (c: PortalCard, on: boolean) => {
    setCollectOn((o) => ({ ...o, [c.serviceId]: on }));
    if (on) {
      const owed = c.arrearsCents;
      setAmounts((a) => ({ ...a, [c.serviceId]: a[c.serviceId] || ((owed > 0 ? owed : c.discountedCents) / 100).toFixed(2) }));
    } else {
      setAmounts((a) => { const n = { ...a }; delete n[c.serviceId]; return n; });
    }
  };

  const pickStudent = (m: { id: string; name: string; classroom: string }) => {
    setSearchOpen(false);
    setQuery("");
    setDate(todayStr());
    loadInfo(m.id, todayStr());
  };

  const resetForm = () => {
    setInfo(null);
    setAmounts({});
    setCollectOn({});
    setInvoiceAmts({});
    setUsage("");
    setBoarded(false);
    setTendered("");
    setReference("");
    setDate(todayStr());
    setMarkPresent(true);
    searchRef.current?.focus();
  };

  /* ── submit — one POS collection (arrears-first per item, excess banks on the item) ── */
  async function submit() {
    if (!info || !S) return;
    const items = activeCards
      .filter((c) => cardPayable(c) && cent(amounts[c.serviceId] ?? "") > 0)
      .map((c) => ({ serviceId: c.serviceId, amountCents: cent(amounts[c.serviceId] ?? "") }));
    const invoiceItems = canInvoice ? (info.invoices ?? [])
      .filter((i) => i.dueCents > 0 && cent(invoiceAmts[i.invoiceId] ?? "") > 0)
      .map((i) => ({ invoiceId: i.invoiceId, amountCents: cent(invoiceAmts[i.invoiceId] ?? "") })) : [];
    const transportOnly = !items.length && !!usage && usage !== "NOT_USING";
    if (!items.length && !invoiceItems.length && !transportOnly && !markPresent) {
      toast({ title: "Nothing to collect", description: "Tick Collect on at least one fee card, or pick a transport direction." });
      return;
    }
    setBusy(true);
    let firstReceipt: any = null;
    let queued = false;
    try {
      if (items.length) {
        const res = await apiPostQueueable<any>(
          `/api/school/${S}/ops/daily`, "payment",
          {
            action: "pos-collect", studentId: info.student.id, items,
            method, reference: reference.trim() || undefined,
            paidAt: date,
            transportUsage: usage || undefined,
            markPresent, date,
          },
          `Daily fees ${fmt(itemTotal)} — ${info.student.name}`,
          (p, clientId) => ({ ...p, category: "DAILY", schoolId: S, studentId: info.student.id, amountCents: itemTotal, method, paidAt: date, clientId }),
        );
        if (res.queued) queued = true;
        else firstReceipt = res.data?.receipt ?? { ...res.data?.payment, student: info.student.name };
      } else if (usage || markPresent) {
        // no money moving — record the boarding direction / presence only
        await apiPost(`/api/school/${S}/ops/daily`, {
          action: "record-presence", studentId: info.student.id,
          transportUsage: usage || undefined, markPresent, date,
        });
      }
      for (const inv of invoiceItems) {
        const r = await apiPost<any>(`/api/school/${S}/fees`, {
          action: "record-payment", invoiceId: inv.invoiceId, amountCents: inv.amountCents,
          method, reference: reference.trim() || undefined,
        });
        firstReceipt = firstReceipt ?? r?.receipt ?? r?.payment;
      }
      if (queued) toast({ title: "Queued offline", description: "Receipt will be issued when you reconnect." });
      else toast({ title: "Collection recorded", description: `${fmt(grandTotal)} from ${info.student.name}.` });
      if (firstReceipt && autoReceipt && !queued) setReceipt(firstReceipt);
      // remove any held basket for this learner
      persistHeld(held.filter((h) => h.studentId !== info.student.id));
      onDone?.();
      loadKpi();
      loadInfo(info.student.id, date);
    } catch (e: any) {
      toast({ title: "Collection failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  /* ── hold / load held ── */
  const holdCurrent = () => {
    if (!info) return;
    const basket: HeldBasket = {
      key: `h${Date.now()}`, studentId: info.student.id, studentName: info.student.name, classroom: info.student.classroom,
      amounts, invoiceAmounts: invoiceAmts, date, method, usage, boarded, at: new Date().toISOString(),
    };
    persistHeld([basket, ...held.filter((h) => h.studentId !== info.student.id)].slice(0, 12));
    toast({ title: "Held", description: `${info.student.name} parked — load them from the Held tray.` });
    resetForm();
  };
  const loadHeld = (h: HeldBasket) => {
    setDate(h.date);
    setMethod(h.method);
    setUsage(h.usage);
    setBoarded(h.boarded);
    loadInfo(h.studentId, h.date);
    // amounts restored after info arrives
    setTimeout(() => { setAmounts(h.amounts); setCollectOn(Object.fromEntries(Object.keys(h.amounts).map((k) => [k, true]))); setInvoiceAmts(h.invoiceAmounts ?? {}); }, 400);
    setHeldOpen(false);
  };

  /* ── fee report / coupon rows (dining-coupon endpoint) ── */
  const fetchRows = (serviceId: string, classroomId: string | null, on: string) =>
    apiGet<{ rows: CouponRow[] }>(`/api/school/${S}/ops/daily?tab=coupons&serviceId=${serviceId}${classroomId && classroomId !== "all" ? `&classroomId=${classroomId}` : ""}&date=${on}`)
      .then((d) => d.rows ?? []);

  const openReport = (svc: ServiceKpi) => {
    setReportSvc(svc);
    setReportRows(null);
    fetchRows(svc.id, "all", todayStr()).then(setReportRows).catch(() => setReportRows([]));
  };

  /* ── print coupon (CI3 generateCashierCoupon parity) ── */
  const [couponSvc, setCouponSvc] = useState("all");
  const [couponClass, setCouponClass] = useState("all");
  const [couponDate, setCouponDate] = useState(todayStr());
  const printCoupon = async () => {
    if (couponSvc === "all") { toast({ title: "Pick a fee item", description: "Coupons print per fee type — choose e.g. Lunch." }); return; }
    const svc = kpi?.services.find((s) => s.id === couponSvc);
    if (!svc) return;
    const rows = await fetchRows(couponSvc, couponClass, couponDate).catch(() => []);
    if (!rows.length) { toast({ title: "Nobody to print", description: `No ${svc.name} charges on ${fmtDate(couponDate)} for that filter.` }); return; }
    const win = window.open("", "_blank", "width=900,height=680");
    if (!win) { toast({ title: "Pop-up blocked", description: "Allow pop-ups to print coupons.", variant: "destructive" }); return; }
    const slips = rows.map((r, i) => `
      <div style="border:1.5px dashed #999;border-radius:10px;padding:10px 12px;break-inside:avoid;">
        <div style="display:flex;justify-content:space-between;font-size:10px;color:#555;"><span>${fmtDate(couponDate)}</span><span>#${i + 1}</span></div>
        <div style="font-weight:800;font-size:13px;margin:2px 0;">${svc.name.toUpperCase()} COUPON</div>
        <div style="font-size:15px;font-weight:700;">${r.student}</div>
        <div style="font-size:11px;color:#444;">${r.studentCode}${r.classroom ? ` · ${r.classroom}` : ""}</div>
        <div style="display:flex;justify-content:space-between;align-items:baseline;margin-top:6px;">
          <span style="font-size:11px;color:#555;">${r.paymentType}</span>
          <span style="font-size:17px;font-weight:800;">${CEDI}${(r.amountCents / 100).toFixed(2)}</span>
        </div>
        <div style="margin-top:8px;border-top:1px solid #ddd;padding-top:4px;font-size:10px;color:#777;">Signed: ______________________</div>
      </div>`).join("");
    win.document.write(`<!doctype html><html><head><title>${svc.name} coupons — ${fmtDate(couponDate)}</title>
      <style>body{font-family:Arial,Helvetica,sans-serif;padding:14px;}
      h1{font-size:15px;margin:0 0 2px;} .sub{font-size:11px;color:#555;margin-bottom:12px;}
      .grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;}
      @media print{@page{margin:10mm;}}</style></head><body>
      <h1>${svc.name} coupons — ${fmtDate(couponDate)}</h1>
      <div class="sub">${rows.length} learners · cut along the dashed lines</div>
      <div class="grid">${slips}</div>
      <script>setTimeout(function(){window.print()},400)</script></body></html>`);
    win.document.close();
  };

  /* ── render ── */
  const kpiServices = useMemo(() => {
    if (!kpi) return [];
    return kpi.services.filter((s) => s.active).sort((a, b) => (CAT_ORDER[a.category] ?? 9) - (CAT_ORDER[b.category] ?? 9) || a.name.localeCompare(b.name));
  }, [kpi]);

  return (
    <div className="space-y-4">
      {/* ── KPI strip — today's takings per fee type + total (CI3 dashboardCards) ── */}
      {!kpi && <LoadingBlock label="Counting today's takings…" />}
      {kpi && (
        <div className="sticky top-0 z-10 -mx-1 px-1 pt-1 pb-2 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
          <div className="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-2.5">
            {kpiServices.map((s) => {
              const Icon = CAT_ICON[s.category] ?? Wallet;
              return (
                <button key={s.id} onClick={() => openReport(s)}
                  className={cn("rounded-xl p-3 text-left text-white shadow-md transition hover:scale-[1.02] hover:shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2", CAT_GRADIENT[s.category] ?? CAT_GRADIENT.OTHER)}
                  title={`Today's ${s.name} collections — click for the register`}>
                  <div className="flex items-center justify-between gap-1 text-[11px] font-semibold opacity-90">
                    <span className="truncate flex items-center gap-1"><Icon className="size-3.5" /> {s.name}</span>
                    {s.chargeMode === "WEEKLY" && <span className="shrink-0 rounded-full bg-white/20 px-1.5 text-[9px]">WEEKLY</span>}
                  </div>
                  <div className="mt-1 text-xl font-extrabold tabular-nums" style={{ textShadow: "0 2px 6px rgba(0,0,0,0.35)" }}>{fmt(s.today.collected, { compact: true })}</div>
                  <div className="text-[11px] opacity-90">{s.today.count} charged{s.today.owedCount > 0 ? ` · ${s.today.owedCount} owing` : ""}</div>
                </button>
              );
            })}
            <div className="rounded-xl p-3 text-white shadow-md bg-gradient-to-br from-emerald-600 to-green-500">
              <div className="flex items-center gap-1 text-[11px] font-semibold opacity-95"><Banknote className="size-3.5" /> TOTAL TODAY</div>
              <div className="mt-1 text-xl font-extrabold tabular-nums" style={{ textShadow: "0 2px 6px rgba(0,0,0,0.35)" }}>{fmt(kpi.today.collected, { compact: true })}</div>
              <div className="text-[11px] opacity-90">{kpi.today.count} charges{kpi.today.owed > 0 ? ` · ${fmt(kpi.today.owed, { compact: true })} owing` : ""}</div>
            </div>
          </div>
        </div>
      )}

      {/* ── toolbar: search + held + coupon ── */}
      <div className="rounded-xl border bg-card p-3 flex flex-wrap items-center gap-2">
        <div className="relative flex-1 min-w-[240px]">
          <Search className="size-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <Input
            ref={searchRef}
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === "Enter" && matches.length) pickStudent(matches[0]);
              if (e.key === "Escape") { setQuery(""); setSearchOpen(false); }
            }}
            onFocus={() => matches.length && setSearchOpen(true)}
            onBlur={() => setTimeout(() => setSearchOpen(false), 150)}
            placeholder="Search learner by name, index or class…  (Esc to reset)"
            className="pl-9 h-10 text-base font-medium"
            aria-label="Search learner"
          />
          {searchOpen && query && (
            <div className="absolute z-30 mt-1 w-full rounded-lg border bg-popover p-1 shadow-lg max-h-72 overflow-y-auto nice-scroll">
              {matches.length ? matches.map((m) => (
                <button key={m.id} className="w-full flex items-center justify-between gap-2 rounded-md px-2.5 py-2 text-left text-sm hover:bg-accent"
                  onMouseDown={(e) => { e.preventDefault(); pickStudent(m); }}>
                  <span className="font-medium truncate">{m.name}</span>
                  <span className="text-xs text-muted-foreground shrink-0">{m.nyansapoIndex} · {m.classroom}</span>
                </button>
              )) : <div className="px-3 py-2 text-sm text-muted-foreground">No learners found</div>}
            </div>
          )}
        </div>
        {info && (
          <Button variant="outline" size="sm" className="gap-1.5 h-9" onClick={resetForm}><X className="size-3.5" /> Change learner</Button>
        )}
        <Button variant="outline" size="sm" className="gap-1.5 h-9 relative" onClick={() => setHeldOpen(true)}>
          <PauseCircle className="size-4 text-amber-500" /> Held
          {held.length > 0 && <span className="absolute -top-1.5 -right-1.5 size-4.5 min-w-4.5 h-4.5 px-1 rounded-full bg-amber-500 text-white text-[10px] font-bold grid place-items-center">{held.length}</span>}
        </Button>
        <Button variant="outline" size="sm" className="gap-1.5 h-9" onClick={() => setCouponOpen(true)}>
          <Ticket className="size-4 text-emerald-600" /> Print coupon
        </Button>
      </div>

      {/* ── empty state ── */}
      {!info && !infoLoading && (
        <EmptyState
          icon={<CircleDollarSign className="size-8" />}
          title="Pick a learner to start collecting"
          hint="Every fee module loads as a card with its own rate, prepaid balance and arrears — take one payment and each item settles oldest-first, banking any excess as prepaid credit."
        />
      )}
      {infoLoading && !info && <LoadingBlock label="Loading fee modules…" />}

      {info && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
          {/* ── LEFT RAIL: learner card, wallet, recent takings ── */}
          <div className="lg:col-span-1 space-y-3 lg:sticky lg:top-28">
            <div className="rounded-xl border bg-card p-4">
              <div className="flex items-start gap-3">
                <div className="size-11 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-white grid place-items-center font-bold text-sm shrink-0">
                  {info.student.name.split(" ").map((p) => p[0]).slice(0, 2).join("")}
                </div>
                <div className="min-w-0 flex-1">
                  <div className="font-bold text-sm leading-tight">{info.student.name}</div>
                  <div className="text-xs text-muted-foreground">{info.student.index}</div>
                  <div className="text-xs text-muted-foreground flex items-center gap-1 mt-0.5"><GraduationCap className="size-3" /> {info.student.classroom}</div>
                </div>
                {info.attendance ? (
                  <Badge variant="outline" className={cn("text-[10px] shrink-0", info.attendance.status === "PRESENT" || info.attendance.status === "LATE" ? "text-emerald-700 dark:text-emerald-300 border-emerald-400/50" : "text-amber-700 dark:text-amber-300 border-amber-400/50")}>
                    {info.attendance.status.toLowerCase()}
                  </Badge>
                ) : <Badge variant="outline" className="text-[10px] text-muted-foreground shrink-0">not marked</Badge>}
              </div>
              <div className="flex flex-wrap gap-1.5 mt-2.5">
                {info.discounts.map((d, i) => (
                  <Badge key={i} variant="outline" className="gap-1 text-[10px] text-violet-700 dark:text-violet-300 border-violet-400/50">
                    <Percent className="size-2.5" /> {d.name} {d.method === "PERCENT" ? `${d.value}%` : fmt(d.value)}
                  </Badge>
                ))}
                {info.transport && <Badge variant="outline" className="gap-1 text-[10px] text-indigo-700 dark:text-indigo-300 border-indigo-400/50"><Bus className="size-2.5" /> {info.transport.routeName}</Badge>}
              </div>
              {info.student.guardianPhone && <div className="text-[11px] text-muted-foreground mt-2">Guardian: {info.student.guardianName ?? "—"} · {info.student.guardianPhone}</div>}
            </div>

            {/* prepaid + arrears wallet cards — click for the per-item breakdown (CI3 showBalanceBreakdown) */}
            <div className="grid grid-cols-2 gap-3">
              <button onClick={() => setBreakdown("prepaid")} className="rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 p-3 text-left transition hover:shadow-md" title="Per-item prepaid breakdown">
                <div className="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 flex items-center gap-1"><Wallet className="size-3" /> PREPAID</div>
                <div className="text-lg font-bold text-emerald-700 dark:text-emerald-300 tabular-nums">{fmt(prepaidTotal)}</div>
                <div className="text-[10px] text-emerald-600/80 dark:text-emerald-400/80">tap to break down</div>
              </button>
              <button onClick={() => setBreakdown("arrears")} className="rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 p-3 text-left transition hover:shadow-md" title="Per-item arrears breakdown">
                <div className="text-[11px] font-semibold text-rose-700 dark:text-rose-300 flex items-center gap-1"><HandCoins className="size-3" /> ARREARS</div>
                <div className="text-lg font-bold text-rose-700 dark:text-rose-300 tabular-nums">{fmt(arrearsTotal)}</div>
                <div className="text-[10px] text-rose-600/80 dark:text-rose-400/80">tap to break down</div>
              </button>
            </div>

            {/* recent takings at the counter */}
            <div className="rounded-xl border bg-card">
              <div className="px-3 py-2 border-b text-xs font-semibold flex items-center gap-1.5"><TrendingUp className="size-3.5 text-primary" /> Recent collections</div>
              <div className="max-h-64 overflow-y-auto nice-scroll p-2 space-y-1.5">
                {(info.recentPayments ?? []).length ? info.recentPayments.map((p) => (
                  <div key={p.id} className="flex items-center justify-between gap-2 rounded-lg bg-secondary/50 px-2.5 py-1.5">
                    <div className="min-w-0">
                      <div className="text-xs font-medium truncate">{PAYMENT_LABELS[p.method] ?? p.method}{p.category !== "DAILY" ? " · invoice" : ""}</div>
                      <div className="text-[10px] text-muted-foreground">{fmtDateTime(p.paidAt)}</div>
                    </div>
                    <div className="text-right shrink-0">
                      <div className="text-xs font-bold tabular-nums">{fmt(p.amountCents)}</div>
                      <div className="text-[10px] text-muted-foreground font-mono">{p.receiptNo}</div>
                    </div>
                  </div>
                )) : <div className="text-xs text-muted-foreground text-center py-4">No collections yet for this learner</div>}
              </div>
            </div>
          </div>

          {/* ── MAIN: fee cards + transaction rail ── */}
          <div className="lg:col-span-2 space-y-4">
            {/* fee cards grid — CI3 metro cards (one per fee module) */}
            {activeCards.length === 0 && !info.transport && !(canInvoice && info.invoices.some((i) => i.dueCents > 0)) && (
              <EmptyState title="No fee modules for this learner" hint="No active enrolments, transport assignment or open invoices for the date." />
            )}
            <div className="grid gap-3 sm:grid-cols-2 2xl:grid-cols-3">
              {activeCards.map((c) => {
                const Icon = CAT_ICON[c.category] ?? Wallet;
                const paid = cardPayable(c) ? cent(amounts[c.serviceId] ?? "") : 0;
                const waterDone = c.weekly && c.weeklyBilledThisWeek;
                const dim = (c.optional && !collectOn[c.serviceId]) || waterDone;
                const settle = Math.min(paid, c.arrearsCents);
                const advance = paid - settle;
                return (
                  <div key={c.serviceId}
                    className={cn("relative rounded-xl p-4 text-white overflow-hidden shadow-lg transition-all", CAT_GRADIENT[c.category] ?? CAT_GRADIENT.OTHER, dim ? "opacity-55" : collectOn[c.serviceId] ? "ring-2 ring-white/70 scale-[1.01]" : "hover:scale-[1.015]")}>
                    <div className="absolute -top-1/2 -right-1/2 w-[200%] h-[200%] bg-white/10 rotate-45 pointer-events-none" aria-hidden />
                    <div className="absolute top-2 right-2 rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-semibold">
                      {c.weekly ? "WEEKLY • REQUIRED" : c.optional ? "DAILY • OPTIONAL" : "DAILY • REQUIRED"}
                    </div>
                    {c.today && (
                      <div className={cn("absolute top-2 left-2 rounded-full px-2 py-0.5 text-[10px] font-semibold", c.today.status === "PAID" ? "bg-emerald-400/90 text-emerald-950" : "bg-amber-300/90 text-amber-950")}>
                        charged {fmt(c.today.amountCents, { compact: true })} · {c.today.status.toLowerCase()}
                      </div>
                    )}
                    <div className="mt-6 flex items-center gap-2">
                      <Icon className="size-8 drop-shadow" />
                      <div className="min-w-0">
                        <div className="font-bold leading-tight truncate">{c.name}</div>
                        <div className="text-sm font-extrabold tabular-nums" style={{ textShadow: "0 2px 6px rgba(0,0,0,0.35)" }}>
                          {fmt(c.discountedCents)}<span className="text-[11px] font-medium opacity-80">{c.weekly ? " /wk" : " /day"}</span>
                          {c.discountCents > 0 && <span className="ml-1.5 text-[11px] line-through opacity-60 font-normal">{fmt(c.priceCents)}</span>}
                        </div>
                      </div>
                    </div>
                    {/* per-item prepaid / arrears chips (CI3 daily_fee_wallet parity) */}
                    <div className="mt-2 flex flex-wrap gap-1.5 text-[10px] font-semibold">
                      {c.prepaidCents > 0 && <span className="rounded-full bg-white/25 px-2 py-0.5">prepaid {fmt(c.prepaidCents)}</span>}
                      {c.arrearsCents > 0 && <span className="rounded-full bg-black/30 px-2 py-0.5">arrears {fmt(c.arrearsCents)}</span>}
                      {c.discountLabel && <span className="rounded-full bg-white/25 px-2 py-0.5">{c.discountLabel}</span>}
                      {waterDone && <span className="rounded-full bg-emerald-300 text-emerald-950 px-2 py-0.5 flex items-center gap-1"><CheckCircle2 className="size-3" /> PAID THIS WEEK</span>}
                    </div>
                    {!waterDone && (
                      <label className="mt-3 flex items-center gap-2 rounded-xl bg-white/20 px-3 py-2 cursor-pointer hover:bg-white/30 transition shadow-sm">
                        <input type="checkbox" className="peer sr-only" checked={!!collectOn[c.serviceId]} onChange={(e) => toggleCard(c, e.target.checked)} />
                        <span className="size-5 rounded-md border-2 border-white/60 bg-white/20 grid place-items-center text-emerald-600 peer-checked:bg-white peer-checked:border-white transition">
                          {collectOn[c.serviceId] ? <CheckCircle2 className="size-4" /> : null}
                        </span>
                        <span className="text-sm font-bold">Collect{c.optional ? " (opt in)" : ""}</span>
                      </label>
                    )}
                    {!waterDone && collectOn[c.serviceId] && (
                      <Input type="number" step="0.5" min="0" placeholder="0.00" inputMode="decimal"
                        value={amounts[c.serviceId] ?? ""}
                        onChange={(e) => setAmounts((a) => ({ ...a, [c.serviceId]: e.target.value }))}
                        className="mt-2 bg-white/90 text-slate-900 font-bold text-lg text-right border-white/40" aria-label={`${c.name} amount`} />
                    )}
                    {paid > 0 && (
                      <div className="mt-1.5 text-[11px] font-medium opacity-95">
                        settles <b>{fmt(settle)}</b>{advance > 0 && <> · banks <b>{fmt(advance)}</b> advance</>}
                      </div>
                    )}
                  </div>
                );
              })}
            </div>

            {/* approved opt-outs (CI3: the cards that never load) */}
            {offCards.length > 0 && (
              <div className="rounded-xl border border-amber-300/60 dark:border-amber-800 bg-amber-50/60 dark:bg-amber-950/30 p-3">
                <div className="text-[11px] font-medium text-amber-800 dark:text-amber-200 mb-1.5 flex items-center gap-1.5">
                  <AlertTriangle className="size-3.5" /> Approved opt-outs — these cards are disabled today (not charged):
                </div>
                <div className="flex flex-wrap gap-1.5">
                  {offCards.map((c) => (
                    <span key={c.serviceId} className="inline-flex items-center gap-1 rounded-md border border-amber-400/50 bg-card px-2 py-1 text-xs line-through opacity-70">
                      {c.name} {fmt(c.discountedCents)}{c.optOut?.reason ? <span className="not-italic opacity-70">· {c.optOut.reason}</span> : null}
                    </span>
                  ))}
                </div>
              </div>
            )}

            {/* transport direction — route-based fare (CI3 transport_direction) */}
            {transportCard && info.transport && (
              <div className="rounded-xl border bg-card p-3">
                <div className="flex flex-wrap items-center gap-2">
                  <Bus className="size-4 text-indigo-600 dark:text-indigo-400" />
                  <span className="text-sm font-semibold">{info.transport.routeName}</span>
                  <span className="text-xs text-muted-foreground">one-way {fmt(info.transport.onewayCents)}{info.transport.stopName ? ` from ${info.transport.stopName}` : ""} · both ways {fmt(info.transport.bothCents || info.transport.onewayCents * 2)}</span>
                  <div className="flex-1" />
                  <Select value={usage || "NOT_USING"} onValueChange={pickUsage}>
                    <SelectTrigger className="w-[190px] h-9"><SelectValue placeholder="Not using bus" /></SelectTrigger>
                    <SelectContent>
                      {USE_OPTS.map((o) => <SelectItem key={o.v} value={o.v}>{o.label}{o.v !== "NOT_USING" ? ` — ${fmt(o.v === "BOTH" ? (info.transport!.bothCents || info.transport!.onewayCents * 2) : info.transport!.onewayCents)}` : ""}</SelectItem>)}
                    </SelectContent>
                  </Select>
                  <label className="flex items-center gap-1.5 text-xs cursor-pointer select-none">
                    <input type="checkbox" className="accent-primary size-4" checked={boarded} onChange={(e) => setBoarded(e.target.checked)} />
                    already boarded
                  </label>
                </div>
                {info.transport.todayUsage && <div className="text-[11px] text-muted-foreground mt-1.5">Today's usage: {info.transport.todayUsage.toLowerCase()}</div>}
              </div>
            )}

            {/* term invoices (cashiers with fees.collect) */}
            {canInvoice && info.invoices.some((i) => i.dueCents > 0) && (
              <div className="rounded-xl border bg-card">
                <div className="px-3 py-2 border-b text-xs font-semibold flex items-center gap-1.5"><Banknote className="size-3.5 text-primary" /> Term invoices — take at the same time</div>
                <div className="p-2 space-y-1.5 max-h-48 overflow-y-auto nice-scroll">
                  {info.invoices.filter((i) => i.dueCents > 0).map((i) => (
                    <div key={i.invoiceId} className="flex flex-wrap items-center gap-2 rounded-lg bg-secondary/50 px-2.5 py-1.5">
                      <div className="min-w-0 flex-1">
                        <div className="text-xs font-medium">{i.invoiceNo}{i.term ? ` · ${i.term}` : ""}</div>
                        <div className="text-[10px] text-muted-foreground">due {fmt(i.dueCents)} of {fmt(i.totalCents)}</div>
                      </div>
                      <Input type="number" step="0.5" min="0" placeholder="0.00" inputMode="decimal" className="w-24 h-8 text-right tabular-nums text-xs font-semibold"
                        value={invoiceAmts[i.invoiceId] ?? ""} onChange={(e) => setInvoiceAmts((a) => ({ ...a, [i.invoiceId]: e.target.value }))} aria-label={`Amount for ${i.invoiceNo}`} />
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* ── transaction rail (CI3 payment_date / method / total / tendered / change) ── */}
            <div className="rounded-2xl border bg-card shadow-sm p-4">
              <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div className="space-y-1">
                  <Label className="text-xs">Payment date</Label>
                  <DateField value={date} max={todayStr()} onChange={(v) => { setDate(v ?? ""); if (info) loadInfo(info.student.id, v ?? todayStr()); }} />
                  {backdated && (
                    <p className="text-[10px] text-amber-700 dark:text-amber-300 flex items-start gap-1 bg-amber-50 dark:bg-amber-950/40 rounded-md px-1.5 py-1">
                      <AlertTriangle className="size-3 shrink-0 mt-0.5" /> Backdated — wallet recalculates from this date forward
                    </p>
                  )}
                </div>
                <div className="space-y-1">
                  <Label className="text-xs">Method</Label>
                  <Select value={method} onValueChange={(v) => { setMethod(v); if (v === "CASH") setReference(""); }}>
                    <SelectTrigger className="h-9"><SelectValue /></SelectTrigger>
                    <SelectContent>
                      {Object.entries(PAYMENT_LABELS).filter(([k]) => k !== "WALLET" && k !== "TRANSPORT_WALLET").map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}
                    </SelectContent>
                  </Select>
                  {method !== "CASH" && <Input placeholder={refHint(method)} value={reference} onChange={(e) => setReference(e.target.value)} className="h-8 text-xs" />}
                </div>
                <div className="space-y-1">
                  <Label className="text-xs">Total</Label>
                  <div className="h-9 rounded-md border border-pink-200 dark:border-pink-900 bg-gradient-to-br from-pink-50 to-rose-50 dark:from-pink-950/40 dark:to-rose-950/40 flex items-center px-3 text-lg font-bold tabular-nums text-pink-600 dark:text-pink-400">{fmt(grandTotal)}</div>
                </div>
                <div className="space-y-1">
                  <Label className="text-xs">Tendered ({CEDI})</Label>
                  <Input type="number" step="0.5" min="0" inputMode="decimal" placeholder="0.00" value={tendered} onChange={(e) => setTendered(e.target.value)} className="h-9 text-center font-bold tabular-nums" aria-label="Amount tendered" />
                </div>
                <div className="space-y-1">
                  <Label className="text-xs">Change</Label>
                  <div className={cn("h-9 rounded-md border flex items-center justify-center px-3 text-lg font-bold tabular-nums",
                    cent(tendered) === 0 ? "border-border bg-secondary/40 text-muted-foreground"
                    : short > 0 ? "border-destructive/40 bg-destructive/10 text-destructive"
                    : "border-emerald-200 dark:border-emerald-900 bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-950/40 dark:to-green-950/40 text-emerald-600 dark:text-emerald-400")}>
                    {cent(tendered) === 0 ? fmt(0) : short > 0 ? `-${fmt(short)}` : fmt(change)}
                  </div>
                </div>
              </div>

              {/* action bar (CI3 mark present / receipt / hold / collect) */}
              <div className="mt-4 flex flex-wrap items-center justify-end gap-2.5">
                <label className="flex items-center gap-2 rounded-xl border-2 border-emerald-500/60 bg-emerald-50 dark:bg-emerald-950/40 px-3 py-2 cursor-pointer select-none">
                  <input type="checkbox" className="accent-emerald-600 size-4" checked={markPresent} onChange={(e) => setMarkPresent(e.target.checked)} />
                  <UserCheck className="size-4 text-emerald-700 dark:text-emerald-300" />
                  <span className="text-xs font-bold text-emerald-700 dark:text-emerald-300">Mark present</span>
                </label>
                <label className="flex items-center gap-2 rounded-xl border bg-secondary/50 px-3 py-2 cursor-pointer select-none">
                  <input type="checkbox" className="accent-primary size-4" checked={autoReceipt} onChange={(e) => setAutoReceipt(e.target.checked)} />
                  <Printer className="size-4 text-muted-foreground" />
                  <span className="text-xs font-semibold">Receipt</span>
                </label>
                <Button variant="outline" className="gap-1.5" onClick={holdCurrent} disabled={!info}>
                  <PauseCircle className="size-4 text-amber-500" /> Hold
                </Button>
                <Button
                  className="gap-2 h-12 px-8 text-base font-bold bg-gradient-to-r from-emerald-600 to-green-500 hover:from-emerald-700 hover:to-green-600 text-white shadow-lg"
                  onClick={submit}
                  disabled={busy || grandTotal <= 0 && !usage && !markPresent}>
                  {busy ? "Recording…" : <><CheckCircle2 className="size-5" /> Collect payment — {fmt(grandTotal)}</>}
                </Button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ── held baskets tray ── */}
      <Dialog open={heldOpen} onOpenChange={setHeldOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader><DialogTitle className="flex items-center gap-2"><PauseCircle className="size-4 text-amber-500" /> Held baskets</DialogTitle>
            <DialogDescription>Parked learners — load one back onto the counter to finish the collection.</DialogDescription></DialogHeader>
          <div className="max-h-80 overflow-y-auto nice-scroll space-y-2">
            {held.length ? held.map((h) => (
              <div key={h.key} className="flex items-center justify-between gap-2 rounded-lg border p-2.5">
                <div className="min-w-0">
                  <div className="text-sm font-medium truncate">{h.studentName}</div>
                  <div className="text-[11px] text-muted-foreground">{h.classroom} · held {fmtDateTime(h.at)} · {Object.values(h.amounts).length} item(s)</div>
                </div>
                <div className="flex gap-1 shrink-0">
                  <Button size="sm" variant="outline" className="gap-1 h-8" onClick={() => loadHeld(h)}><PlayCircle className="size-3.5" /> Load</Button>
                  <Button size="sm" variant="ghost" className="size-8 p-0 text-destructive hover:text-destructive" onClick={() => persistHeld(held.filter((x) => x.key !== h.key))} title="Discard"><Trash2 className="size-3.5" /></Button>
                </div>
              </div>
            )) : <div className="text-sm text-muted-foreground text-center py-6">Nothing held — use <b>Hold</b> to park a learner mid-collection.</div>}
          </div>
        </DialogContent>
      </Dialog>

      {/* ── per-item wallet breakdown (CI3 showBalanceBreakdown / showArrearsBreakdown) ── */}
      <Dialog open={!!breakdown} onOpenChange={(o) => !o && setBreakdown(null)}>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              {breakdown === "prepaid" ? <><Wallet className="size-4 text-emerald-600" /> Prepaid breakdown</> : <><HandCoins className="size-4 text-rose-600" /> Arrears breakdown</>}
            </DialogTitle>
            <DialogDescription>{info?.student.name} · {info?.student.classroom}</DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5">
            {breakdown === "prepaid" ? (
              <>
                {(info?.cards ?? []).filter((c) => c.prepaidCents > 0).map((c) => (
                  <div key={c.serviceId} className="flex items-center justify-between rounded-lg bg-emerald-50 dark:bg-emerald-950/40 px-3 py-2 text-sm">
                    <span>{c.name}</span><b className="tabular-nums text-emerald-700 dark:text-emerald-300">{fmt(c.prepaidCents)}</b>
                  </div>
                ))}
                {(info?.wallet.prepaidCents ?? 0) > 0 && (
                  <div className="flex items-center justify-between rounded-lg bg-emerald-50 dark:bg-emerald-950/40 px-3 py-2 text-sm">
                    <span>General wallet</span><b className="tabular-nums text-emerald-700 dark:text-emerald-300">{fmt(info!.wallet.prepaidCents)}</b>
                  </div>
                )}
                {(info?.wallet.transportCents ?? 0) > 0 && (
                  <div className="flex items-center justify-between rounded-lg bg-emerald-50 dark:bg-emerald-950/40 px-3 py-2 text-sm">
                    <span>Transport wallet</span><b className="tabular-nums text-emerald-700 dark:text-emerald-300">{fmt(info!.wallet.transportCents)}</b>
                  </div>
                )}
                {prepaidTotal === 0 && <div className="text-sm text-muted-foreground text-center py-4">No prepaid credit on any item.</div>}
              </>
            ) : (
              <>
                {(info?.cards ?? []).filter((c) => c.arrearsCents > 0).map((c) => (
                  <div key={c.serviceId} className="flex items-center justify-between rounded-lg bg-rose-50 dark:bg-rose-950/40 px-3 py-2 text-sm">
                    <span>{c.name}</span><b className="tabular-nums text-rose-700 dark:text-rose-300">{fmt(c.arrearsCents)}</b>
                  </div>
                ))}
                {info?.transport && info.transport.arrearsCents > 0 && (
                  <div className="flex items-center justify-between rounded-lg bg-rose-50 dark:bg-rose-950/40 px-3 py-2 text-sm">
                    <span>Transport (route)</span><b className="tabular-nums text-rose-700 dark:text-rose-300">{fmt(info.transport.arrearsCents)}</b>
                  </div>
                )}
                {arrearsTotal === 0 && <div className="text-sm text-muted-foreground text-center py-4">Nothing owed — fully settled. 🎉</div>}
              </>
            )}
          </div>
        </DialogContent>
      </Dialog>

      {/* ── fee report (KPI card click — today's register for that fee type) ── */}
      <Dialog open={!!reportSvc} onOpenChange={(o) => !o && setReportSvc(null)}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">{reportSvc && (() => { const I = CAT_ICON[reportSvc.category] ?? Wallet; return <I className="size-4" /> })()} {reportSvc?.name} — today</DialogTitle>
            <DialogDescription>{reportRows?.length ?? 0} learners charged · collected {reportSvc ? fmt(reportSvc.today.collected) : ""}{reportSvc && reportSvc.today.owedCount > 0 ? ` · ${reportSvc.today.owedCount} owing` : ""}</DialogDescription>
          </DialogHeader>
          <div className="max-h-80 overflow-y-auto nice-scroll space-y-1.5">
            {!reportRows && <LoadingBlock label="Loading register…" />}
            {reportRows && !reportRows.length && <div className="text-sm text-muted-foreground text-center py-6">No charges for this fee type today.</div>}
            {reportRows?.map((r, i) => (
              <div key={i} className="flex items-center justify-between gap-2 rounded-lg border px-3 py-2">
                <div className="min-w-0">
                  <div className="text-sm font-medium truncate">{r.student}</div>
                  <div className="text-[11px] text-muted-foreground">{r.studentCode}{r.classroom ? ` · ${r.classroom}` : ""} · {r.paymentType}</div>
                </div>
                <b className="text-sm tabular-nums shrink-0">{fmt(r.amountCents)}</b>
              </div>
            ))}
          </div>
        </DialogContent>
      </Dialog>

      {/* ── print coupon (CI3 showCouponModal) ── */}
      <Dialog open={couponOpen} onOpenChange={setCouponOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><Ticket className="size-4 text-emerald-600" /> Print coupon</DialogTitle>
            <DialogDescription>Printable slips for every learner charged a fee item on the date — cut along the dashed lines.</DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <div className="space-y-1.5">
              <Label>Fee item</Label>
              <Select value={couponSvc} onValueChange={setCouponSvc}>
                <SelectTrigger><SelectValue placeholder="Pick a fee item" /></SelectTrigger>
                <SelectContent>
                  {kpiServices.map((s) => <SelectItem key={s.id} value={s.id}>{s.name}{s.chargeMode === "WEEKLY" ? " (weekly)" : ""}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Class filter</Label>
              <Select value={couponClass} onValueChange={setCouponClass}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All classes</SelectItem>
                  {(kpi?.settings.classrooms ?? []).map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Date</Label>
              <DateField value={couponDate} max={todayStr()} onChange={(v) => setCouponDate(v ?? todayStr())} />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCouponOpen(false)}>Cancel</Button>
            <Button className="gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white" onClick={printCoupon}><Printer className="size-4" /> Generate &amp; print</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <ReceiptDialog receipt={receipt} onOpenChange={(o) => !o && setReceipt(null)} />
    </div>
  );
}

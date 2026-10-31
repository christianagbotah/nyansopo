"use client";
/* Nyansapo — professional payment receipts.
   Four print styles × four receipt types (iedupro parity):
   INVOICE_PAYMENT · DAILY_SERVICES · POS_SALE · TRANSPORT_FARE
   Styles: A5 standard · FORMAL (dotted-field bursar sheet) ·
           MODERN (gradient, total-bills section) · THERMAL (80mm) */
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { LogoMark, KenteStrip } from "@/components/brand";
import { fmtCedi, fmtDateTime, PAYMENT_LABELS } from "@/lib/format";
import { Printer, Copy, ReceiptText } from "lucide-react";

// ── amount in words (Ghana style) ─────────────────────────────
const ONES = ["zero", "one", "two", "three", "four", "five", "six", "seven", "eight", "nine", "ten", "eleven", "twelve", "thirteen", "fourteen", "fifteen", "sixteen", "seventeen", "eighteen", "nineteen"];
const TENS = ["", "", "twenty", "thirty", "forty", "fifty", "sixty", "seventy", "eighty", "ninety"];
const cap = (s: string) => s.charAt(0).toUpperCase() + s.slice(1);

function triplettWords(n: number): string {
  if (n < 20) return ONES[n];
  if (n < 100) return `${TENS[Math.floor(n / 10)]}${n % 10 ? "-" + ONES[n % 10] : ""}`;
  return `${ONES[Math.floor(n / 100)]} hundred${n % 100 ? " and " + triplettWords(n % 100) : ""}`;
}
export function amountInWords(cents: number): string {
  const cedis = Math.floor(Math.abs(cents) / 100);
  const pesewas = Math.abs(cents) % 100;
  if (cedis === 0 && pesewas === 0) return "Zero cedis only";
  const parts: string[] = [];
  if (cedis > 0) {
    const millions = Math.floor(cedis / 1_000_000);
    const thousands = Math.floor((cedis % 1_000_000) / 1000);
    const rest = cedis % 1000;
    if (millions) parts.push(`${triplettWords(millions)} million`);
    if (thousands) parts.push(`${triplettWords(thousands)} thousand`);
    if (rest) parts.push(triplettWords(rest));
  }
  let out = "";
  if (parts.length) out += cap(parts.join(" ")) + " cedis";
  if (pesewas) out += `${out ? ", " : ""}${cap(triplettWords(pesewas))} pesewas`;
  return out + " only";
}

// ── receipt payload contract ──────────────────────────────────
export type ReceiptSchool = { name: string; motto?: string | null; town?: string | null; region?: string | null; phone?: string | null; email?: string | null };
export type ReceiptData = {
  type: "INVOICE_PAYMENT" | "DAILY_SERVICES" | "POS_SALE" | "TRANSPORT_FARE";
  receiptNo: string;
  date: string;
  method: string;
  reference?: string | null;
  cashier?: string | null;
  student?: { name: string; index?: string | null; classroom?: string | null; guardian?: string | null };
  buyer?: string | null;
  // invoice type
  invoice?: { no: string; term: string; items: { name: string; amount: number }[]; total: number; discount: number; paid: number; balance: number };
  // daily type
  services?: { name: string; count: number; amount: number }[];
  // POS type
  lines?: { item: string; qty: number; amount: number }[];
  // transport type
  transport?: { route: string; vehicle?: string | null; term: string; fare: number };
  amount: number;
  school: ReceiptSchool;
  // print styling
  style?: "A5" | "FORMAL" | "MODERN" | "THERMAL"; // THERMAL = 80mm roll
  multiInvoice?: boolean; // watermark: payment spans several invoices
  arrears?: boolean; // watermark: paying a previous term's bill
  creditCreated?: number; // overpayment converted to credit
  paymentType?: "PAYMENT" | "REFUND" | "CREDIT"; // refund receipts get distinct banner
  bankName?: string | null;
  chequeNo?: string | null;
};
const TYPE_TITLES: Record<ReceiptData["type"], string> = {
  INVOICE_PAYMENT: "Official Fees Receipt",
  DAILY_SERVICES: "Counter Services Receipt",
  POS_SALE: "Store Sale Receipt",
  TRANSPORT_FARE: "Transport Fare Receipt",
};

export const RECEIPT_STYLES = [
  { value: "A5", label: "Standard A5", hint: "Kente-accented sheet, itemised table" },
  { value: "FORMAL", label: "Formal Bursar A5", hint: "Serif, dotted fields, amount in words" },
  { value: "MODERN", label: "Modern Gradient", hint: "Gradient header, total-bills section" },
  { value: "THERMAL", label: "Thermal 80mm", hint: "Counter roll printer" },
] as const;
export type ReceiptStyle = (typeof RECEIPT_STYLES)[number]["value"];

/** rows for the active receipt type (shared by all sheet styles) */
function receiptRows(data: ReceiptData): { label: string; amount: number; sub?: string }[] {
  if (data.type === "INVOICE_PAYMENT" && data.invoice) return data.invoice.items.map((it) => ({ label: it.name, amount: it.amount }));
  if (data.type === "DAILY_SERVICES" && data.services) return data.services.map((s) => ({ label: s.name, amount: s.amount, sub: s.count > 1 ? `${s.count} units` : undefined }));
  if (data.type === "POS_SALE" && data.lines) return data.lines.map((l) => ({ label: l.item, amount: l.amount, sub: `×${l.qty}` }));
  if (data.type === "TRANSPORT_FARE" && data.transport) return [{ label: `${data.transport.route} fare`, amount: data.transport.fare }];
  return [];
}

// ── watermarks (iedupro parity: MULTI-INVOICE / ARREARS) ──
function ReceiptWatermark({ data }: { data: ReceiptData }) {
  const mark = data.multiInvoice ? "MULTI-INVOICE" : data.arrears ? "ARREARS" : null;
  if (!mark) return null;
  return (
    <div className="pointer-events-none absolute inset-0 flex items-center justify-center overflow-hidden">
      <span className="text-5xl font-black tracking-widest text-destructive/10 dark:text-destructive/20 -rotate-[24deg] select-none">{mark}</span>
    </div>
  );
}

// ── 80mm thermal receipt (counter printers) ────────────────
export function ThermalReceipt({ data }: { data: ReceiptData }) {
  const school = data.school;
  return (
    <div className="print-sheet thermal-sheet mx-auto bg-card text-card-foreground border shadow-sm overflow-hidden" style={{ width: "302px" }}>
      <div className="px-4 py-3 font-mono text-[11px] leading-snug relative">
        <ReceiptWatermark data={data} />
        <div className="text-center">
          <div className="font-bold text-[13px] uppercase tracking-wide">{school.name}</div>
          <div className="text-[10px]">{[school.town, school.region].filter(Boolean).join(", ")}</div>
          {school.phone && <div className="text-[10px]">{school.phone}</div>}
          <div className="my-1.5 font-bold text-[11px] border-y border-dashed py-1">{TYPE_TITLES[data.type]}</div>
        </div>
        <div className="flex justify-between"><span>No:</span><span className="font-bold">{data.receiptNo}</span></div>
        <div className="flex justify-between"><span>Date:</span><span>{fmtDateTime(data.date)}</span></div>
        {data.student ? (
          <>
            <div className="flex justify-between"><span>Student:</span><span className="font-semibold text-right">{data.student.name}</span></div>
            <div className="flex justify-between"><span>Class:</span><span>{data.student.classroom ?? "—"}</span></div>
          </>
        ) : (
          <div className="flex justify-between"><span>Customer:</span><span className="font-semibold">{data.buyer ?? "Walk-in"}</span></div>
        )}
        <div className="my-1.5 border-t border-dashed" />
        <table className="w-full">
          <tbody>
            {(data.type === "INVOICE_PAYMENT" && data.invoice ? data.invoice.items.map((it, i) => (
              <tr key={i}><td className="pr-2">{it.name}</td><td className="text-right tabular-nums">{fmtCedi(it.amount)}</td></tr>
            )) : data.type === "DAILY_SERVICES" && data.services ? data.services.map((s, i) => (
              <tr key={i}><td className="pr-2">{s.name}{s.count > 1 ? ` ×${s.count}` : ""}</td><td className="text-right tabular-nums">{fmtCedi(s.amount)}</td></tr>
            )) : data.type === "POS_SALE" && data.lines ? data.lines.map((l, i) => (
              <tr key={i}><td className="pr-2">{l.item} ×{l.qty}</td><td className="text-right tabular-nums">{fmtCedi(l.amount)}</td></tr>
            )) : data.type === "TRANSPORT_FARE" && data.transport ? (
              <tr><td className="pr-2">{data.transport.route} fare</td><td className="text-right tabular-nums">{fmtCedi(data.transport.fare)}</td></tr>
            ) : null)}
            {data.invoice && data.invoice.discount > 0 && (
              <tr><td className="pr-2">Discount</td><td className="text-right tabular-nums">−{fmtCedi(data.invoice.discount)}</td></tr>
            )}
          </tbody>
        </table>
        <div className="my-1.5 border-t border-dashed" />
        <div className="flex justify-between font-bold text-[13px]"><span>PAID</span><span className="tabular-nums">{fmtCedi(data.amount)}</span></div>
        {data.invoice && (
          <div className="flex justify-between font-semibold">
            <span>Balance</span><span className="tabular-nums">{fmtCedi(data.invoice.balance)}</span>
          </div>
        )}
        <div className="flex justify-between"><span>Method</span><span>{PAYMENT_LABELS[data.method] ?? data.method}</span></div>
        {data.cashier && <div className="flex justify-between"><span>Cashier</span><span>{data.cashier}</span></div>}
        {data.creditCreated ? (
          <div className="my-1 text-center border border-dashed py-0.5 text-[10px]">CREDIT: {fmtCedi(data.creditCreated)} — applied to future bills</div>
        ) : null}
        <div className="mt-2 text-center text-[10px]">
          <div className="font-semibold">Thank you!</div>
          <div>Computer-generated receipt · {new Date().toLocaleDateString()}</div>
        </div>
      </div>
    </div>
  );
}

// ── FORMAL BURSAR SHEET (A5 serif, dotted fields — iedupro style_2) ──
export function FormalReceipt({ data }: { data: ReceiptData }) {
  const school = data.school;
  const rows = receiptRows(data);
  const discount = data.invoice?.discount ?? 0;
  const isRefund = data.paymentType === "REFUND";
  return (
    <div className="print-sheet a5-sheet mx-auto bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden relative">
      <ReceiptWatermark data={data} />
      <div className="px-7 py-6 font-serif">
        {/* 3-column letterhead */}
        <div className="grid grid-cols-[1fr_1.4fr_1fr] items-start gap-3">
          <LogoMark size={52} />
          <div className="text-center leading-tight">
            <div className="font-bold text-lg uppercase tracking-wide">{school.name}</div>
            <div className="text-[11px]">{[school.town, school.region].filter(Boolean).join(", ")}</div>
            <div className="text-[10px]">{school.phone ?? ""}{school.email ? ` · ${school.email}` : ""}</div>
            <div className="mt-2 inline-block rounded-md bg-foreground px-3 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-background">
              {isRefund ? "Official Refund Voucher" : "Official Receipt"}
            </div>
          </div>
          <table className="w-full text-[10px]">
            <tbody>
              <tr><td className="text-muted-foreground py-0.5">Receipt No.</td><td className="font-mono font-bold text-right">{data.receiptNo}</td></tr>
              <tr><td className="text-muted-foreground py-0.5">Date</td><td className="text-right">{fmtDateTime(data.date)}</td></tr>
              {data.invoice && (
                <tr><td className="text-muted-foreground py-0.5">Invoice</td><td className="font-mono text-right">{data.invoice.no}</td></tr>
              )}
            </tbody>
          </table>
        </div>

        <div className="my-4 border-t-2 border-foreground/70" />

        {/* dotted form fields */}
        <div className="space-y-3.5 text-[13px]">
          <div className="flex items-baseline gap-2">
            <span className="font-semibold uppercase text-[11px] tracking-wide">Received from</span>
            <span className="flex-1 border-b border-dotted border-foreground/50 pb-0.5 font-semibold">
              {data.student ? (data.student.guardian ?? "Guardian") : (data.buyer ?? "Walk-in customer")}
              {data.student && <span className="font-normal text-muted-foreground"> — guardian of {data.student.name}</span>}
            </span>
          </div>
          <div className="flex items-baseline gap-2">
            <span className="font-semibold uppercase text-[11px] tracking-wide">The sum of</span>
            <span className="font-bold tabular-nums border-b border-dotted border-foreground/50 pb-0.5">GH₵ {(data.amount / 100).toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
            <span className="flex-1 border-b border-dotted border-foreground/50 pb-0.5 italic text-[12px] text-muted-foreground text-right">({amountInWords(data.amount)})</span>
          </div>
          <div className="flex items-baseline gap-2">
            <span className="font-semibold uppercase text-[11px] tracking-wide">Being</span>
            <span className="flex-1 border-b border-dotted border-foreground/50 pb-0.5">
              {isRefund ? "refund of overpayment on school fees" : data.invoice ? `${data.invoice.balance > 0 ? "part payment" : "full payment"} of school fees — ${data.invoice.term}` : `payment — ${TYPE_TITLES[data.type].replace(" Receipt", "")}`}
            </span>
          </div>
          {(data.method === "CHEQUE" || data.method === "BANK") && (
            <div className="grid grid-cols-2 gap-4">
              <div className="flex items-baseline gap-2">
                <span className="font-semibold uppercase text-[11px] tracking-wide">{data.method === "CHEQUE" ? "Cheque No." : "Bank"}</span>
                <span className="flex-1 border-b border-dotted border-foreground/50 pb-0.5 font-mono text-[12px]">{data.method === "CHEQUE" ? (data.chequeNo ?? data.reference ?? "—") : (data.bankName ?? "—")}</span>
              </div>
              <div className="flex items-baseline gap-2">
                <span className="font-semibold uppercase text-[11px] tracking-wide">Reference</span>
                <span className="flex-1 border-b border-dotted border-foreground/50 pb-0.5 font-mono text-[12px]">{data.reference ?? "—"}</span>
              </div>
            </div>
          )}
          {data.method.startsWith("MOMO") && data.reference && (
            <div className="flex items-baseline gap-2">
              <span className="font-semibold uppercase text-[11px] tracking-wide">MoMo Transaction ID</span>
              <span className="flex-1 border-b border-dotted border-foreground/50 pb-0.5 font-mono text-[12px]">{data.reference}</span>
            </div>
          )}
        </div>

        {/* items */}
        <table className="w-full mt-4 text-[12.5px]">
          <thead>
            <tr className="border-y border-foreground/40 text-left text-[9.5px] uppercase tracking-wider">
              <th className="py-1.5 font-semibold">Particulars</th>
              <th className="py-1.5 font-semibold text-center w-14">Qty</th>
              <th className="py-1.5 font-semibold text-right">Amount (GH₵)</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r, i) => (
              <tr key={i} className="border-b border-dotted border-foreground/25">
                <td className="py-1.5">{r.label}</td>
                <td className="py-1.5 text-center tabular-nums text-muted-foreground">{r.sub?.replace("×", "") ?? "1"}</td>
                <td className="py-1.5 text-right tabular-nums">{(r.amount / 100).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
              </tr>
            ))}
            {discount > 0 && (
              <tr className="border-b border-dotted border-foreground/25 text-emerald-700 dark:text-emerald-400">
                <td className="py-1.5" colSpan={2}>Discount applied</td>
                <td className="py-1.5 text-right tabular-nums">−{(discount / 100).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
              </tr>
            )}
          </tbody>
        </table>

        {/* totals */}
        <div className="mt-3 grid grid-cols-3 gap-3">
          <div className="border border-foreground/40 rounded p-2 text-center">
            <div className="text-[9px] uppercase tracking-widest text-muted-foreground">{isRefund ? "Refunded" : "Amount Paid"}</div>
            <div className="font-bold text-[15px] tabular-nums">GH₵ {(data.amount / 100).toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
          </div>
          <div className="border border-foreground/40 rounded p-2 text-center">
            <div className="text-[9px] uppercase tracking-widest text-muted-foreground">Balance Due</div>
            <div className={`font-bold text-[15px] tabular-nums ${(data.invoice?.balance ?? 0) > 0 ? "text-destructive" : "text-emerald-600"}`}>GH₵ {((data.invoice?.balance ?? 0) / 100).toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
          </div>
          <div className="border border-foreground/40 rounded p-2 text-center">
            <div className="text-[9px] uppercase tracking-widest text-muted-foreground">Method</div>
            <div className="font-semibold text-[13px] mt-0.5">{PAYMENT_LABELS[data.method] ?? data.method}</div>
          </div>
        </div>

        {data.creditCreated ? (
          <div className="mt-3 rounded border border-emerald-500/60 bg-emerald-50 dark:bg-emerald-950/40 px-3 py-1.5 text-center text-[11.5px] font-semibold text-emerald-700 dark:text-emerald-300">
            CREDIT CREATED — GH₵ {(data.creditCreated / 100).toLocaleString(undefined, { minimumFractionDigits: 2 })} carried forward to future bills
          </div>
        ) : null}

        {/* signatures */}
        <div className="mt-8 grid grid-cols-2 gap-10 text-[10.5px]">
          <div className="border-t border-foreground/60 pt-1">Bursar&apos;s Signature</div>
          <div className="border-t border-foreground/60 pt-1 text-right">Issued by: {data.cashier ?? "Front Desk"}</div>
        </div>
        <div className="mt-3 text-center text-[10px] italic text-muted-foreground">Thank you for your payment · Computer-generated receipt</div>
      </div>
      <KenteStrip />
    </div>
  );
}

// ── MODERN GRADIENT SHEET (A5, total-bills section — iedupro style_3) ──
export function ModernReceipt({ data }: { data: ReceiptData }) {
  const school = data.school;
  const rows = receiptRows(data);
  const discount = data.invoice?.discount ?? 0;
  const grossOriginal = rows.reduce((s, r) => s + r.amount, 0);
  const afterDiscount = grossOriginal - discount;
  const isRefund = data.paymentType === "REFUND";
  return (
    <div className="print-sheet a5-sheet mx-auto bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden relative">
      <ReceiptWatermark data={data} />
      {/* gradient header */}
      <div className="px-6 py-4 bg-gradient-to-br from-slate-800 via-slate-700 to-emerald-900 text-white relative">
        <div className="flex items-start justify-between gap-3">
          <div>
            <div className="font-bold text-lg tracking-tight leading-tight">{school.name}</div>
            <div className="text-[10.5px] text-white/70">{[school.town, school.region].filter(Boolean).join(", ")}{school.phone ? ` · ${school.phone}` : ""}</div>
            {school.motto && <div className="text-[10px] italic text-white/60 mt-0.5">{school.motto}</div>}
          </div>
          <div className="flex items-center gap-2">
            <div className="size-10 rounded-lg bg-white/95 grid place-items-center"><LogoMark size={30} /></div>
          </div>
        </div>
        <div className="mt-3 flex items-center justify-between">
          <span className="inline-block rounded-full bg-white/15 backdrop-blur px-3 py-0.5 text-[10px] font-bold uppercase tracking-[0.16em]">{isRefund ? "Refund Voucher" : TYPE_TITLES[data.type]}</span>
          <span className="font-mono text-sm font-bold">{data.receiptNo}</span>
        </div>
      </div>

      <div className="px-6 py-5 text-[12.5px]">
        {/* total bills section — original amounts, discount, after discount */}
        <div className="rounded-lg border overflow-hidden">
          <div className="bg-secondary/70 px-3 py-1.5 text-[9.5px] font-bold uppercase tracking-widest text-muted-foreground">Total Bills</div>
          <table className="w-full">
            <tbody>
              {rows.map((r, i) => (
                <tr key={i} className="border-t">
                  <td className="px-3 py-1.5">{r.label}{r.sub ? <span className="text-muted-foreground"> ({r.sub})</span> : ""}</td>
                  <td className="px-3 py-1.5 text-right tabular-nums">{fmtCedi(r.amount)}</td>
                </tr>
              ))}
              {discount > 0 && (
                <>
                  <tr className="border-t bg-secondary/30"><td className="px-3 py-1.5 font-semibold">Total Amount</td><td className="px-3 py-1.5 text-right font-semibold tabular-nums">{fmtCedi(grossOriginal)}</td></tr>
                  <tr><td className="px-3 py-1.5 text-emerald-700 dark:text-emerald-400">Discount Applied</td><td className="px-3 py-1.5 text-right tabular-nums text-emerald-700 dark:text-emerald-400">−{fmtCedi(discount)}</td></tr>
                  <tr className="border-t bg-secondary/30"><td className="px-3 py-1.5 font-semibold">Amount After Discount</td><td className="px-3 py-1.5 text-right font-semibold tabular-nums">{fmtCedi(afterDiscount)}</td></tr>
                </>
              )}
            </tbody>
          </table>
        </div>

        {/* receipt details grid */}
        <div className="mt-4 grid grid-cols-2 gap-x-6 gap-y-2">
          <div><span className="text-[10px] uppercase tracking-wider text-muted-foreground">Received from </span><span className="font-semibold">{data.student ? `${data.student.guardian ?? "Guardian"} (${data.student.name})` : (data.buyer ?? "Walk-in")}</span></div>
          <div><span className="text-[10px] uppercase tracking-wider text-muted-foreground">Class </span><span className="font-semibold">{data.student?.classroom ?? "—"}</span></div>
          <div><span className="text-[10px] uppercase tracking-wider text-muted-foreground">Paid on </span><span className="font-semibold">{fmtDateTime(data.date)}</span></div>
          <div><span className="text-[10px] uppercase tracking-wider text-muted-foreground">Method </span><span className="font-semibold">{PAYMENT_LABELS[data.method] ?? data.method}</span></div>
          {data.invoice && <div className="col-span-2"><span className="text-[10px] uppercase tracking-wider text-muted-foreground">Invoice </span><span className="font-mono font-semibold">{data.invoice.no}</span><span className="text-muted-foreground"> · {data.invoice.term}</span></div>}
          <div className="col-span-2"><span className="text-[10px] uppercase tracking-wider text-muted-foreground">The sum of </span><span className="italic">{amountInWords(data.amount)}</span></div>
        </div>

        {/* amount paid banner */}
        <div className="mt-4 rounded-lg bg-gradient-to-r from-emerald-600 to-emerald-500 text-white px-4 py-3 flex items-center justify-between">
          <div>
            <div className="text-[10px] uppercase tracking-widest text-white/80">{isRefund ? "Refunded" : "Amount Paid"}</div>
            <div className="text-[10px] text-white/70">{data.invoice?.balance > 0 ? `Part payment · balance ${fmtCedi(data.invoice.balance)}` : "Full settlement"}</div>
          </div>
          <div className="text-2xl font-black tabular-nums">{fmtCedi(data.amount)}</div>
        </div>

        {data.creditCreated ? (
          <div className="mt-3 rounded-lg border border-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-3 py-1.5 text-center text-[12px] font-semibold text-emerald-700 dark:text-emerald-300">
            Credit created — {fmtCedi(data.creditCreated)} applied to future bills
          </div>
        ) : null}

        {/* signatures */}
        <div className="mt-8 grid grid-cols-2 gap-10 text-[10px] text-muted-foreground">
          <div className="border-t pt-1">Cashier — {data.cashier ?? "Front Desk"}</div>
          <div className="border-t pt-1 text-right">School stamp</div>
        </div>
      </div>
      <div className="bg-secondary/50 px-6 py-2 text-center text-[10px] text-muted-foreground">{school.name} · Computer-generated receipt · {new Date().toLocaleDateString()}</div>
    </div>
  );
}

// ── the document ──────────────────────────────────────────────
export function ReceiptDocument({ data, copy = "ORIGINAL" }: { data: ReceiptData; copy?: string }) {
  if (data.style === "THERMAL") return <ThermalReceipt data={data} />;
  if (data.style === "FORMAL") return <FormalReceipt data={data} />;
  if (data.style === "MODERN") return <ModernReceipt data={data} />;
  const school = data.school;
  return (
    <div className="print-sheet a5-sheet mx-auto bg-card text-card-foreground rounded-xl border shadow-sm overflow-hidden relative">
      <KenteStrip />
      <ReceiptWatermark data={data} />
      <div className="px-6 py-5">
        {/* letterhead */}
        <div className="flex items-start justify-between gap-3">
          <div className="flex items-center gap-2.5">
            <LogoMark size={44} />
            <div className="leading-tight">
              <div className="font-bold text-[17px] tracking-tight">{school.name}</div>
              {school.motto && <div className="text-[10px] italic text-muted-foreground">{school.motto}</div>}
              <div className="text-[10px] text-muted-foreground mt-0.5">
                {[school.town, school.region].filter(Boolean).join(", ")}
                {school.phone ? ` · ${school.phone}` : ""}
              </div>
              {school.email && <div className="text-[10px] text-muted-foreground">{school.email}</div>}
            </div>
          </div>
          <div className="text-right shrink-0">
            <div className="inline-block rounded-md bg-primary/10 text-primary px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest">{TYPE_TITLES[data.type]}</div>
            <div className="font-mono text-sm font-bold mt-1.5">{data.receiptNo}</div>
            <div className="text-[10px] text-muted-foreground">{fmtDateTime(data.date)}</div>
          </div>
        </div>

        <div className="border-t border-dashed border-border my-4" />

        {/* payer / subject */}
        <div className="grid grid-cols-2 gap-3 text-[13px]">
          {data.student ? (
            <>
              <div>
                <div className="text-[9px] uppercase tracking-wider text-muted-foreground">Received from</div>
                <div className="font-semibold">{data.student.guardian ?? "Guardian"}</div>
                <div className="text-[11px] text-muted-foreground">parent/guardian of <span className="font-medium text-foreground">{data.student.name}</span></div>
              </div>
              <div className="text-right">
                <div className="text-[9px] uppercase tracking-wider text-muted-foreground">Learner</div>
                <div className="font-semibold">{data.student.name}</div>
                <div className="text-[11px] text-muted-foreground">
                  {data.student.classroom ?? "—"}
                  {data.student.index ? ` · ${data.student.index}` : ""}
                </div>
              </div>
            </>
          ) : (
            <div className="col-span-2">
              <div className="text-[9px] uppercase tracking-wider text-muted-foreground">Received from</div>
              <div className="font-semibold">{data.buyer ?? "Walk-in customer"}</div>
            </div>
          )}
        </div>

        {/* body per type */}
        <div className="mt-4 space-y-4">
          {data.type === "INVOICE_PAYMENT" && data.invoice && (
            <>
              <div className="flex items-center justify-between text-[11px] text-muted-foreground">
                <span>Invoice <span className="font-mono font-semibold text-foreground">{data.invoice.no}</span></span>
                <span>{data.invoice.term}</span>
              </div>
              <table className="w-full text-[12.5px]">
                <thead>
                  <tr className="border-b text-left text-[9px] uppercase tracking-wider text-muted-foreground">
                    <th className="py-1.5 font-medium">Bill item</th>
                    <th className="py-1.5 font-medium text-right">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  {data.invoice.items.map((it, i) => (
                    <tr key={i} className="border-b border-dashed">
                      <td className="py-1.5">{it.name}</td>
                      <td className="py-1.5 text-right tabular-nums">{fmtCedi(it.amount)}</td>
                    </tr>
                  ))}
                  {data.invoice.discount > 0 && (
                    <tr className="border-b border-dashed text-emerald-700 dark:text-emerald-400">
                      <td className="py-1.5">Discount applied</td>
                      <td className="py-1.5 text-right tabular-nums">−{fmtCedi(data.invoice.discount)}</td>
                    </tr>
                  )}
                  <tr className="border-b">
                    <td className="py-1.5 font-semibold">Invoice total</td>
                    <td className="py-1.5 text-right font-semibold tabular-nums">{fmtCedi(data.invoice.total - data.invoice.discount)}</td>
                  </tr>
                </tbody>
              </table>
              <div className="grid grid-cols-3 gap-2 text-center">
                <div className="rounded-lg bg-secondary/60 p-2">
                  <div className="text-[9px] uppercase tracking-wider text-muted-foreground">Total billed</div>
                  <div className="font-bold text-sm tabular-nums">{fmtCedi(data.invoice.total - data.invoice.discount)}</div>
                </div>
                <div className="rounded-lg bg-primary/10 p-2">
                  <div className="text-[9px] uppercase tracking-wider text-primary/80">Amount paid</div>
                  <div className="font-bold text-sm tabular-nums text-primary">{fmtCedi(data.amount)}</div>
                </div>
                <div className="rounded-lg bg-secondary/60 p-2">
                  <div className="text-[9px] uppercase tracking-wider text-muted-foreground">Balance</div>
                  <div className={`font-bold text-sm tabular-nums ${data.invoice.balance > 0 ? "text-destructive" : "text-emerald-600"}`}>{fmtCedi(data.invoice.balance)}</div>
                </div>
              </div>
            </>
          )}

          {data.type === "DAILY_SERVICES" && data.services && (
            <table className="w-full text-[12.5px]">
              <thead>
                <tr className="border-b text-left text-[9px] uppercase tracking-wider text-muted-foreground">
                  <th className="py-1.5 font-medium">Service</th>
                  <th className="py-1.5 font-medium text-center">Days / units</th>
                  <th className="py-1.5 font-medium text-right">Amount</th>
                </tr>
              </thead>
              <tbody>
                {data.services.map((s, i) => (
                  <tr key={i} className="border-b border-dashed">
                    <td className="py-1.5">{s.name}</td>
                    <td className="py-1.5 text-center tabular-nums">{s.count}</td>
                    <td className="py-1.5 text-right tabular-nums">{fmtCedi(s.amount)}</td>
                  </tr>
                ))}
                <tr className="border-b">
                  <td className="py-1.5 font-semibold" colSpan={2}>Total settled</td>
                  <td className="py-1.5 text-right font-semibold tabular-nums">{fmtCedi(data.amount)}</td>
                </tr>
              </tbody>
            </table>
          )}

          {data.type === "POS_SALE" && data.lines && (
            <table className="w-full text-[12.5px]">
              <thead>
                <tr className="border-b text-left text-[9px] uppercase tracking-wider text-muted-foreground">
                  <th className="py-1.5 font-medium">Item</th>
                  <th className="py-1.5 font-medium text-center">Qty</th>
                  <th className="py-1.5 font-medium text-right">Amount</th>
                </tr>
              </thead>
              <tbody>
                {data.lines.map((l, i) => (
                  <tr key={i} className="border-b border-dashed">
                    <td className="py-1.5">{l.item}</td>
                    <td className="py-1.5 text-center tabular-nums">{l.qty}</td>
                    <td className="py-1.5 text-right tabular-nums">{fmtCedi(l.amount)}</td>
                  </tr>
                ))}
                <tr className="border-b">
                  <td className="py-1.5 font-semibold" colSpan={2}>Sale total</td>
                  <td className="py-1.5 text-right font-semibold tabular-nums">{fmtCedi(data.amount)}</td>
                </tr>
              </tbody>
            </table>
          )}

          {data.type === "TRANSPORT_FARE" && data.transport && (
            <div className="grid grid-cols-2 gap-2 text-[13px]">
              <div className="rounded-lg bg-secondary/60 p-2.5">
                <div className="text-[9px] uppercase tracking-wider text-muted-foreground">Route</div>
                <div className="font-semibold">{data.transport.route}</div>
              </div>
              <div className="rounded-lg bg-secondary/60 p-2.5">
                <div className="text-[9px] uppercase tracking-wider text-muted-foreground">Vehicle</div>
                <div className="font-semibold">{data.transport.vehicle ?? "—"}</div>
              </div>
              <div className="rounded-lg bg-secondary/60 p-2.5">
                <div className="text-[9px] uppercase tracking-wider text-muted-foreground">Term</div>
                <div className="font-semibold">{data.transport.term}</div>
              </div>
              <div className="rounded-lg bg-secondary/60 p-2.5">
                <div className="text-[9px] uppercase tracking-wider text-muted-foreground">Fare</div>
                <div className="font-semibold tabular-nums">{fmtCedi(data.transport.fare)}</div>
              </div>
            </div>
          )}

          {/* payment meta */}
          <div className="rounded-lg border bg-secondary/30 px-3 py-2.5 text-[12px] space-y-1">
            <div className="flex justify-between">
              <span className="text-muted-foreground">Mode of payment</span>
              <span className="font-semibold">{PAYMENT_LABELS[data.method] ?? data.method}{data.reference ? ` · Ref ${data.reference}` : ""}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">Amount in words</span>
              <span className="font-medium italic text-right max-w-[62%]">{amountInWords(data.amount)}</span>
            </div>
            {data.cashier && (
              <div className="flex justify-between">
                <span className="text-muted-foreground">Received by</span>
                <span className="font-semibold">{data.cashier}</span>
              </div>
            )}
          </div>

          {data.creditCreated ? (
            <div className="rounded-lg border border-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 dark:border-emerald-900 px-3 py-2 text-[12px] text-emerald-800 dark:text-emerald-300 text-center">
              Credit created: <b>{fmtCedi(data.creditCreated)}</b> — will be applied to future bills
            </div>
          ) : null}

          {/* signatures */}
          <div className="grid grid-cols-2 gap-8 pt-5 text-[10px] text-muted-foreground">
            <div className="border-t border-border pt-1">Cashier / Bursar signature</div>
            <div className="border-t border-border pt-1 text-right">School stamp</div>
          </div>
        </div>
      </div>
      <div className="bg-secondary/60 px-6 py-2 text-center text-[10px] text-muted-foreground flex items-center justify-between">
        <span>Keep this receipt safe — required for any refund or query.</span>
        <span className="font-semibold tracking-wider">{copy}</span>
      </div>
      <KenteStrip />
    </div>
  );
}

// ── dialog wrapper ────────────────────────────────────────────
export function ReceiptDialog({ data, open, onOpenChange }: { data: ReceiptData | null; open: boolean; onOpenChange: (o: boolean) => void }) {
  if (!data) return null;
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className={data.style === "THERMAL" ? "max-w-[360px]" : "max-w-[580px]"}>
        <DialogHeader className="no-print">
          <DialogTitle className="flex items-center gap-2 text-base">
            <ReceiptText className="size-4" /> Receipt issued
            <span className="font-mono text-xs text-muted-foreground">{data.receiptNo}</span>
          </DialogTitle>
        </DialogHeader>
        <div className="max-h-[68vh] overflow-y-auto nice-scroll">
          <div className="print-area">
            <ReceiptDocument data={data} />
          </div>
        </div>
        <div className="no-print flex justify-end gap-2">
          <Button size="sm" variant="outline" onClick={() => window.print()}>
            <Printer className="size-4" /> Print
          </Button>
          <Button size="sm" variant="outline" onClick={() => window.print()}>
            <Copy className="size-4" /> Print copy
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}

// ── bulk receipts (multiple payments, one per page) ────────
export function BulkReceiptsDocument({ receipts, onDone }: { receipts: ReceiptData[]; onDone: () => void }) {
  return (
    <Dialog open onOpenChange={(o) => !o && onDone()}>
      <DialogContent className="max-w-[620px] max-h-[92vh] overflow-y-auto nice-scroll">
        <DialogHeader className="no-print">
          <DialogTitle className="text-base">{receipts.length} receipts — bulk print</DialogTitle>
        </DialogHeader>
        <div className="print-area space-y-4">
          {receipts.map((r, i) => (
            <div key={i} className={i < receipts.length - 1 ? "print-break-after" : ""}>
              <ReceiptDocument data={r} />
            </div>
          ))}
        </div>
        <div className="no-print flex justify-end gap-2">
          <Button size="sm" variant="outline" onClick={onDone}>Close</Button>
          <Button size="sm" onClick={() => window.print()}><Printer className="size-4" /> Print all</Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}

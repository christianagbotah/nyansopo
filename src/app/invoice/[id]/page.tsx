/* Public invoice view — iedupro Public_invoice parity.
   A shareable, no-login link (/{invoiceId}) schools send to guardians:
   statement of the term's bill, payments received, running balance and
   how-to-pay instructions. Unguessable cuid acts as the access token. */
import { db as prisma } from "@/lib/db";
import { notFound } from "next/navigation";
import Link from "next/link";

export const dynamic = "force-dynamic";

const PAY_LABELS: Record<string, string> = {
  CASH: "Cash", MOMO_MTN: "MTN MoMo", MOMO_AT: "AirtelTigo Money", MOMO_TELECEL: "Telecel Cash",
  BANK: "Bank transfer", CHEQUE: "Cheque", CARD: "Card",
};
const cedis = (c: number) => `GH₵ ${(c / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default async function PublicInvoicePage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const inv = await prisma.invoice.findUnique({
    where: { id },
    include: {
      items: true,
      payments: { orderBy: { paidAt: "asc" } },
      student: true,
      term: { include: { academicYear: true } },
      school: true,
    },
  });
  if (!inv) notFound();

  const net = inv.totalCents - inv.discountCents;
  const balance = net - inv.paidCents;
  const statusLabel = balance <= 0 ? "SETTLED" : inv.status === "OVERDUE" ? "OVERDUE" : inv.paidCents > 0 ? "PART PAYMENT" : "AWAITING PAYMENT";

  return (
    <div className="min-h-screen bg-secondary/40 py-6 px-3 sm:py-10" style={{ fontFamily: "system-ui, -apple-system, sans-serif" }}>
      <div className="max-w-2xl mx-auto bg-white text-neutral-900 rounded-2xl shadow-xl overflow-hidden">
        {/* letterhead */}
        <div className="px-6 sm:px-8 pt-7 pb-5 border-b-2 border-neutral-800">
          <div className="flex items-center justify-between gap-3">
            <div>
              <div className="text-xl font-extrabold uppercase tracking-tight leading-tight">{inv.school.name}</div>
              <div className="text-[11px] text-neutral-500 mt-0.5">
                {[inv.school.town, inv.school.region].filter(Boolean).join(", ")}{inv.school.phone ? ` · ${inv.school.phone}` : ""}
              </div>
            </div>
            <span className={`shrink-0 rounded-full px-3 py-1 text-[10px] font-bold tracking-widest ${balance <= 0 ? "bg-emerald-100 text-emerald-800" : inv.status === "OVERDUE" ? "bg-red-100 text-red-800" : "bg-amber-100 text-amber-800"}`}>
              {statusLabel}
            </span>
          </div>
          <div className="mt-4 inline-block border-2 border-neutral-800 px-4 py-0.5 font-bold tracking-[0.18em] text-[12px] uppercase">
            School Fees Statement
          </div>
        </div>

        <div className="px-6 sm:px-8 py-5 space-y-5">
          {/* meta */}
          <div className="grid grid-cols-2 gap-y-2 text-[13px]">
            <div className="col-span-2"><span className="text-neutral-500">Learner: </span><b className="uppercase">{inv.student.firstName} {inv.student.lastName}</b></div>
            <div><span className="text-neutral-500">Class: </span><b>{inv.classroomName || "—"}</b></div>
            <div><span className="text-neutral-500">Learner ID: </span><b className="font-mono">{inv.student.nyansapoIndex}</b></div>
            <div><span className="text-neutral-500">Invoice: </span><b className="font-mono">{inv.invoiceNo}</b></div>
            <div><span className="text-neutral-500">Term: </span><b>{inv.term.name} · {inv.term.academicYear.name}</b></div>
            {inv.dueDate && <div className="col-span-2"><span className="text-neutral-500">Due date: </span><b>{new Date(inv.dueDate).toLocaleDateString(undefined, { day: "numeric", month: "long", year: "numeric" })}</b></div>}
          </div>

          {/* bill items */}
          <table className="w-full text-[13px] border border-neutral-300">
            <thead>
              <tr className="bg-neutral-800 text-white text-left text-[10px] uppercase tracking-wider">
                <th className="px-3 py-2 font-semibold">Fee item</th>
                <th className="px-3 py-2 font-semibold text-right">Amount</th>
              </tr>
            </thead>
            <tbody>
              {inv.items.map((it) => (
                <tr key={it.id} className="border-t border-neutral-200">
                  <td className="px-3 py-2">{it.name}</td>
                  <td className="px-3 py-2 text-right tabular-nums">{cedis(it.amountCents)}</td>
                </tr>
              ))}
              <tr className="border-t-2 border-neutral-400 font-semibold bg-neutral-50">
                <td className="px-3 py-2">Total billed</td>
                <td className="px-3 py-2 text-right tabular-nums">{cedis(inv.totalCents)}</td>
              </tr>
              {inv.discountCents > 0 && (
                <tr className="text-emerald-700 bg-emerald-50">
                  <td className="px-3 py-2">Discount {inv.discountReason ? `— ${inv.discountReason}` : ""}</td>
                  <td className="px-3 py-2 text-right tabular-nums">−{cedis(inv.discountCents)}</td>
                </tr>
              )}
              <tr className="border-t border-neutral-300 font-bold">
                <td className="px-3 py-2">Net fee</td>
                <td className="px-3 py-2 text-right tabular-nums">{cedis(net)}</td>
              </tr>
            </tbody>
          </table>

          {/* payments */}
          {inv.payments.length > 0 && (
            <div>
              <div className="text-[10px] font-bold uppercase tracking-widest text-neutral-500 mb-1.5">Payments received</div>
              <table className="w-full text-[12.5px]">
                <tbody>
                  {inv.payments.map((p) => (
                    <tr key={p.id} className="border-t border-neutral-100">
                      <td className="py-1.5 font-mono text-[11px] text-neutral-500">{p.receiptNo}</td>
                      <td className="py-1.5 text-neutral-600">{new Date(p.paidAt).toLocaleDateString(undefined, { day: "numeric", month: "short", year: "numeric" })}</td>
                      <td className="py-1.5 text-neutral-600">{PAY_LABELS[p.method] ?? p.method}</td>
                      <td className="py-1.5 text-right tabular-nums font-semibold text-emerald-700">{cedis(p.amountCents)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {/* balance */}
          <div className={`rounded-xl px-5 py-4 flex items-center justify-between ${balance <= 0 ? "bg-emerald-50 border border-emerald-200" : "bg-neutral-900 text-white"}`}>
            <div>
              <div className={`text-[10px] uppercase tracking-widest ${balance <= 0 ? "text-emerald-700" : "text-white/60"}`}>{balance <= 0 ? "Fully settled — medaase!" : "Outstanding balance"}</div>
              <div className="text-[11px] mt-0.5 opacity-70">Paid so far: {cedis(inv.paidCents)} of {cedis(net)}</div>
            </div>
            <div className="text-2xl font-black tabular-nums">{cedis(Math.max(0, balance))}</div>
          </div>

          {/* how to pay */}
          {balance > 0 && (
            <div className="rounded-xl border border-neutral-200 bg-neutral-50 px-5 py-4 text-[12.5px] space-y-1.5">
              <div className="text-[10px] font-bold uppercase tracking-widest text-neutral-500">How to pay</div>
              <p>Send via <b>MoMo</b> to the school&apos;s merchant line, then text the transaction ID to the front desk — a stamped receipt is issued the same day.</p>
              {inv.school.phone && <p>School line: <b>{inv.school.phone}</b>{inv.student.guardianPhone ? ` (reference: ${inv.student.firstName}'s learner ID ${inv.student.nyansapoIndex})` : ""}</p>}
              <p className="text-neutral-500 text-[11.5px]">Instalment plans are available — speak with the bursar&apos;s office.</p>
            </div>
          )}

          <div className="text-[10.5px] text-neutral-400 text-center pb-1">
            Statement generated by Nyansapo — The Wisdom Knot Cloud · For questions quote invoice <span className="font-mono">{inv.invoiceNo}</span>
          </div>
        </div>
      </div>
      <div className="max-w-2xl mx-auto mt-4 text-center">
        <Link href="/" className="text-xs text-neutral-500 hover:text-neutral-800 underline underline-offset-2">Powered by Nyansapo — school management for Ghana</Link>
      </div>
    </div>
  );
}

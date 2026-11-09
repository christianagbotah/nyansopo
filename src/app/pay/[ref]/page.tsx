"use client";

import { useCallback, useEffect, useState } from "react";
import { useParams, useSearchParams } from "next/navigation";

/* ── Public payment page (/pay/{ref}) ──────────────────────────
   The page a parent lands on when the school shares a payment link —
   and the return URL after the Hubtel hosted checkout. Shows the amount,
   purpose and live status; polls while pending; in SIMULATED mode it
   offers "I have paid — simulate" so schools can rehearse the flow before
   their Hubtel account is provisioned. */

type PayStatus = {
  status: string;
  statusLabel: string;
  purposeLabel: string;
  amountCents: number;
  school: { name: string; logoColor: string };
  studentName: string | null;
  receiptNo: string | null;
  appliedNote: string | null;
  failureReason: string | null;
  method: string;
  channelLabel: string | null;
  checkoutUrl: string | null;
  provider: string;
  simulated: boolean;
};

const fmt = (cents: number) => `₵${(cents / 100).toFixed(2)}`;

export default function PublicPayPage() {
  const params = useParams<{ ref: string }>();
  const search = useSearchParams();
  const ref = params?.ref ?? "";
  const [data, setData] = useState<PayStatus | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const justReturned = search.get("done") === "1";

  const load = useCallback(async () => {
    try {
      const r = await fetch(`/api/public/pay/${encodeURIComponent(ref)}`, { cache: "no-store" });
      const j = await r.json();
      if (!r.ok) throw new Error(j.error ?? "Not found");
      setData(j);
      setError(null);
    } catch (e: any) {
      setError(e?.message ?? "Could not load this payment link");
    }
  }, [ref]);

  useEffect(() => { load(); }, [load]);

  // poll while pending (checkout takes 30–120s on the momo networks)
  useEffect(() => {
    if (data?.status !== "PENDING") return;
    const t = setInterval(load, 5000);
    return () => clearInterval(t);
  }, [data?.status, load]);

  async function simulate(action: "pay" | "fail") {
    setBusy(true);
    try {
      await fetch(`/api/public/pay/${encodeURIComponent(ref)}`, {
        method: "POST", headers: { "content-type": "application/json" }, body: JSON.stringify({ action }),
      });
      await load();
    } finally {
      setBusy(false);
    }
  }

  const accent = data?.school?.logoColor ?? "#0F6B47";
  const status = data?.status ?? "PENDING";

  return (
    <div className="min-h-screen flex flex-col items-center justify-center p-4" style={{ background: `linear-gradient(160deg, ${accent}14, transparent 60%)` }}>
      <div className="w-full max-w-md">
        {/* school header */}
        <div className="rounded-t-2xl px-6 py-5 flex items-center gap-3 text-white" style={{ background: accent }}>
          <div className="w-10 h-10 rounded-xl bg-white/20 grid place-items-center font-bold">₵</div>
          <div>
            <div className="font-semibold leading-tight">{data?.school.name ?? "School"}</div>
            <div className="text-xs text-white/80">Mobile-money payment</div>
          </div>
        </div>

        <div className="bg-card border border-border rounded-b-2xl shadow-sm p-6 space-y-5">
          {error ? (
            <div className="text-center space-y-2">
              <div className="text-4xl">🔗</div>
              <p className="font-semibold">This payment link is not available</p>
              <p className="text-sm text-muted-foreground">{error}</p>
            </div>
          ) : !data ? (
            <div className="py-10 text-center text-sm text-muted-foreground animate-pulse">Loading…</div>
          ) : (
            <>
              {/* status banner */}
              <div className={`rounded-xl p-4 text-center ${status === "COMPLETED" ? "bg-emerald-500/10 text-emerald-700 dark:text-emerald-400" : status === "FAILED" ? "bg-red-500/10 text-red-700 dark:text-red-400" : status === "CANCELLED" ? "bg-amber-500/10 text-amber-700 dark:text-amber-400" : "bg-sky-500/10 text-sky-700 dark:text-sky-400"}`}>
                <div className="text-3xl mb-1">{status === "COMPLETED" ? "✅" : status === "FAILED" ? "⚠️" : status === "CANCELLED" ? "🚫" : "⏳"}</div>
                <div className="font-semibold">{data.statusLabel}{justReturned && status === "PENDING" ? " — confirming…" : ""}</div>
                {status === "FAILED" && data.failureReason ? <div className="text-xs mt-1 opacity-80">{data.failureReason}</div> : null}
              </div>

              {/* details */}
              <div className="space-y-2 text-sm">
                <div className="flex justify-between"><span className="text-muted-foreground">Amount</span><span className="font-semibold">{fmt(data.amountCents)}</span></div>
                <div className="flex justify-between"><span className="text-muted-foreground">For</span><span>{data.purposeLabel}</span></div>
                {data.studentName ? <div className="flex justify-between"><span className="text-muted-foreground">Learner</span><span>{data.studentName}</span></div> : null}
                {data.receiptNo ? <div className="flex justify-between"><span className="text-muted-foreground">Receipt</span><span className="font-mono">{data.receiptNo}</span></div> : null}
                {data.appliedNote ? <div className="flex justify-between gap-4"><span className="text-muted-foreground">Applied</span><span className="text-right">{data.appliedNote}</span></div> : null}
                <div className="flex justify-between"><span className="text-muted-foreground">Reference</span><span className="font-mono text-xs">{ref}</span></div>
              </div>

              {/* actions */}
              {status === "PENDING" && data.method === "PAYMENT_LINK" && data.checkoutUrl?.startsWith("http") && (
                <a href={data.checkoutUrl} className="block w-full text-center rounded-xl px-4 py-3 font-semibold text-white" style={{ background: accent }}>
                  Pay {fmt(data.amountCents)} now
                </a>
              )}
              {status === "PENDING" && data.method === "MOMO_PROMPT" && (
                <div className="rounded-xl bg-muted p-3 text-xs text-muted-foreground text-center">
                  A prompt has been sent to {data.channelLabel ?? "your phone"} — enter your PIN to approve.
                </div>
              )}
              {status === "PENDING" && data.simulated && (
                <div className="space-y-2 pt-2 border-t border-border">
                  <p className="text-[11px] text-muted-foreground text-center">Demo mode (no Hubtel credentials on this deployment) — rehearse the flow:</p>
                  <div className="grid grid-cols-2 gap-2">
                    <button disabled={busy} onClick={() => simulate("pay")} className="rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 text-sm font-medium disabled:opacity-50">I have paid</button>
                    <button disabled={busy} onClick={() => simulate("fail")} className="rounded-lg bg-muted hover:bg-muted/70 px-3 py-2 text-sm font-medium disabled:opacity-50">Simulate failure</button>
                  </div>
                </div>
              )}
              {status === "COMPLETED" && (
                <div className="text-center text-xs text-muted-foreground">Keep this page as proof of payment. The school&apos;s ledger has been updated automatically.</div>
              )}
            </>
          )}
        </div>
        <p className="text-center text-[11px] text-muted-foreground mt-4">Secured by Hubtel · Nyansapo School Management</p>
      </div>
    </div>
  );
}

"use client";
// ── Owner AI assistant — floating chat panel (typed + voice input) ─────────
// Task 9 of the iEduPro → Nyansapo port. Ask the school's money questions;
// answers (and the compact breakdown chips under them) are grounded in the
// real figures returned by /api/school/[id]/ops/assistant. Mount once on
// the dashboard: <OwnerAssistant /> — no props required.
import { useEffect, useRef, useState } from "react";
import { Mic, Send, Sparkles, X } from "lucide-react";
import { useStore } from "@/lib/store";
import { apiPost } from "@/lib/api";
import { toast } from "@/hooks/use-toast";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";
import { fmtCedi, PAYMENT_LABELS } from "@/lib/format";
import { cn } from "@/lib/utils";

// ── minimal local SpeechRecognition typings (not in the TS DOM lib) ──
interface SpeechAltLike {
  transcript: string;
}
interface SpeechResultLike {
  isFinal: boolean;
  [index: number]: SpeechAltLike;
}
interface SpeechResultsLike {
  length: number;
  [index: number]: SpeechResultLike;
}
interface SpeechEventLike {
  resultIndex: number;
  results: SpeechResultsLike;
}
interface SpeechRecognitionLike {
  lang: string;
  interimResults: boolean;
  continuous: boolean;
  onresult: ((e: SpeechEventLike) => void) | null;
  onend: (() => void) | null;
  onerror: ((e: { error?: string }) => void) | null;
  start(): void;
  stop(): void;
  abort(): void;
}
type SpeechRecognitionCtor = new () => SpeechRecognitionLike;

// ── API contract (mirror of the route's response) ──
type AssistantData = {
  kind: "finance" | "general";
  rangeLabel?: string | null;
  totalGHS?: number;
  count?: number;
  byMethod?: { method: string; amountGHS: number }[];
  items?: { receiptNo: string; student: string; amountGHS: number; method: string; paidAt: string }[];
  dailyFeesGHS?: number;
  dailyByService?: { service: string; amountGHS: number; txns: number }[];
  expensesGHS?: number;
  expensesByCategory?: { category: string; amountGHS: number }[];
  creditGHS?: number;
  creditStudents?: number;
  note?: string | null;
};

type AssistantReply = {
  answer: string;
  range: { from: string; to: string; label: string } | null;
  data: AssistantData;
};

type Msg = { role: "user" | "assistant"; content: string; data?: AssistantData; rangeLabel?: string | null };

const SUGGESTIONS = [
  "Total income today?",
  "Income this week by payment method",
  "How much did we collect this month?",
  "Daily fees collected today",
];

/** API returns GHS decimals; fmtCedi expects integer cents. */
const cedi = (ghs: number | undefined | null) => fmtCedi(Math.round((ghs ?? 0) * 100));

export function OwnerAssistant() {
  const { currentSchoolId } = useStore();
  const [open, setOpen] = useState(false);
  const [msgs, setMsgs] = useState<Msg[]>([]);
  const [input, setInput] = useState("");
  const [busy, setBusy] = useState(false);
  const [listening, setListening] = useState(false);
  const [srSupported, setSrSupported] = useState<boolean | null>(null);

  const scrollRef = useRef<HTMLDivElement>(null);
  const recogRef = useRef<SpeechRecognitionLike | null>(null);
  const finalTextRef = useRef("");
  const sendRef = useRef<(text?: string) => void>(() => {});

  // feature-detect Web Speech after mount (never during SSR)
  useEffect(() => {
    const w = window as any;
    setSrSupported(!!(w.SpeechRecognition || w.webkitSpeechRecognition));
  }, []);

  // release the microphone on unmount
  useEffect(() => () => {
    try { recogRef.current?.abort(); } catch { /* ignore */ }
    recogRef.current = null;
  }, []);

  // keep the messages list pinned to the newest turn
  useEffect(() => {
    const el = scrollRef.current;
    if (el) el.scrollTop = el.scrollHeight;
  }, [msgs, busy, open]);

  async function send(textArg?: string) {
    const text = (textArg ?? input).trim();
    if (!text || busy || !currentSchoolId) return;
    setInput("");
    const history = msgs.slice(-6).map((m) => ({ role: m.role, content: m.content }));
    setMsgs((ms) => [...ms, { role: "user", content: text }]);
    setBusy(true);
    try {
      const res = await apiPost<AssistantReply>(`/api/school/${currentSchoolId}/ops/assistant`, { question: text, history });
      setMsgs((ms) => [...ms, { role: "assistant", content: res.answer, data: res.data, rangeLabel: res.range?.label ?? res.data?.rangeLabel ?? null }]);
    } catch (e: any) {
      toast({ title: "Assistant unavailable", description: e?.message ?? "Please try again.", variant: "destructive" });
      setMsgs((ms) => [...ms, { role: "assistant", content: "Sorry — I couldn't reach the assistant just now. Please try again." }]);
    } finally {
      setBusy(false);
    }
  }

  // latest send for the voice auto-submit (recognition callbacks close over this ref)
  useEffect(() => { sendRef.current = send; });

  function toggleMic() {
    if (listening) {
      try { recogRef.current?.stop(); } catch { /* ignore */ }
      return;
    }
    const w = window as any;
    const SR: SpeechRecognitionCtor | undefined = w.SpeechRecognition || w.webkitSpeechRecognition;
    if (!SR) return;
    let rec: SpeechRecognitionLike;
    try { rec = new SR(); } catch { return; }
    recogRef.current = rec;
    finalTextRef.current = "";
    rec.lang = "en-GH";
    rec.interimResults = true;
    rec.continuous = false;
    rec.onresult = (e) => {
      let interim = "";
      for (let i = e.resultIndex; i < e.results.length; i++) {
        const r = e.results[i];
        const t = r[0]?.transcript ?? "";
        if (r.isFinal) finalTextRef.current = `${finalTextRef.current} ${t}`.trim();
        else interim += t;
      }
      setInput(`${finalTextRef.current} ${interim}`.trim());
    };
    rec.onerror = () => setListening(false);
    rec.onend = () => {
      setListening(false);
      const finalText = finalTextRef.current.trim();
      if (finalText) sendRef.current(finalText); // auto-submit the spoken question
    };
    setListening(true);
    try { rec.start(); } catch { setListening(false); }
  }

  if (!currentSchoolId) return null;

  return (
    <div className="fixed bottom-20 right-4 z-50 flex flex-col items-end sm:right-6 print:hidden">
      {open && (
        <div className="mb-3 flex h-[480px] max-h-[70vh] w-[380px] max-w-[92vw] flex-col overflow-hidden rounded-xl border bg-card shadow-xl">
          {/* header */}
          <div className="flex items-start justify-between gap-2 border-b px-3.5 py-2.5">
            <div className="min-w-0">
              <div className="flex items-center gap-1.5 text-sm font-semibold">
                <Sparkles className="size-3.5 shrink-0 text-primary" />
                Assistant
              </div>
              <div className="truncate text-[11px] text-muted-foreground">Ask your school's money questions — typed or spoken</div>
            </div>
            <Button variant="ghost" size="icon" className="size-7 shrink-0" onClick={() => setOpen(false)} aria-label="Close assistant">
              <X className="size-4" />
            </Button>
          </div>

          {/* messages */}
          <div ref={scrollRef} className="nice-scroll flex-1 space-y-3 overflow-y-auto px-3 py-3">
            {msgs.length === 0 && !busy && (
              <div className="space-y-2.5 pt-1">
                <p className="px-1 text-xs leading-relaxed text-muted-foreground">
                  Akwaaba! I answer with your school's <span className="font-medium text-foreground">live figures</span> — income, payment
                  methods, daily fees, expenses and arrears. Try:
                </p>
                <div className="flex flex-col gap-1.5">
                  {SUGGESTIONS.map((q) => (
                    <button
                      key={q}
                      type="button"
                      onClick={() => send(q)}
                      className="rounded-full border bg-background px-3 py-1.5 text-left text-xs text-foreground/90 transition-colors hover:bg-secondary"
                    >
                      {q}
                    </button>
                  ))}
                </div>
              </div>
            )}
            {msgs.map((m, i) => (
              <div key={i} className={cn("flex", m.role === "user" ? "justify-end" : "justify-start")}>
                <div className={cn("max-w-[88%] rounded-lg px-3 py-2 text-sm leading-relaxed", m.role === "user" ? "bg-primary text-primary-foreground" : "bg-secondary")}>
                  <div className="whitespace-pre-wrap">{m.content}</div>
                  {m.role === "assistant" && m.data?.kind === "finance" && <DataChips data={m.data} />}
                </div>
              </div>
            ))}
            {busy && (
              <div className="flex justify-start">
                <div className="flex items-center gap-1.5 rounded-lg bg-secondary px-4 py-3" aria-label="Assistant is typing">
                  <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground" />
                  <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground" style={{ animationDelay: "120ms" }} />
                  <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground" style={{ animationDelay: "240ms" }} />
                </div>
              </div>
            )}
          </div>

          {/* input row */}
          <form className="flex items-center gap-1.5 border-t px-2.5 py-2.5" onSubmit={(e) => { e.preventDefault(); send(); }}>
            <Input
              value={input}
              onChange={(e) => setInput(e.target.value)}
              placeholder={listening ? "Listening…" : "Ask about income, fees, expenses…"}
              className="h-8 text-sm"
              maxLength={600}
              disabled={busy}
              aria-label="Ask the assistant"
            />
            <Tooltip>
              <TooltipTrigger asChild>
                <span className="inline-flex">
                  <Button
                    type="button"
                    size="icon"
                    variant={listening ? "destructive" : "outline"}
                    className={cn("size-8 shrink-0", listening && "animate-pulse")}
                    disabled={srSupported !== true || busy}
                    onClick={toggleMic}
                    aria-label={listening ? "Stop voice input" : "Speak your question"}
                  >
                    <Mic className="size-4" />
                  </Button>
                </span>
              </TooltipTrigger>
              {srSupported === false && <TooltipContent>Voice input not supported in this browser</TooltipContent>}
            </Tooltip>
            <Button type="submit" size="icon" className="size-8 shrink-0" disabled={busy || !input.trim()} aria-label="Send question">
              <Send className="size-4" />
            </Button>
          </form>
        </div>
      )}

      {/* floating launcher */}
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="relative inline-flex size-12 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform hover:scale-105 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
        aria-label={open ? "Close assistant" : "Open AI assistant"}
      >
        {open ? <X className="size-5" /> : <Sparkles className="size-5" />}
        {!open && (
          <span className="absolute -right-0.5 -top-0.5 rounded-full border bg-background px-1 py-px text-[9px] font-bold leading-none text-primary">AI</span>
        )}
      </button>
    </div>
  );
}

/** Compact verified-figures chip block under a finance answer (subtle, text-xs). */
function DataChips({ data }: { data: AssistantData }) {
  const lines: string[] = [];
  if (typeof data.totalGHS === "number") lines.push(`Total ${cedi(data.totalGHS)} · ${data.count ?? 0} payments`);
  for (const m of data.byMethod?.slice(0, 5) ?? []) lines.push(`${PAYMENT_LABELS[m.method] ?? m.method} ${cedi(m.amountGHS)}`);
  if (typeof data.dailyFeesGHS === "number" && data.dailyFeesGHS > 0) lines.push(`Daily fees ${cedi(data.dailyFeesGHS)}`);
  if (typeof data.expensesGHS === "number") lines.push(`Expenses ${cedi(data.expensesGHS)}`);
  if (data.items?.length) lines.push(`${data.items.length} recent receipts`);
  if (!lines.length) return null;
  return (
    <div className="mt-2 rounded-md border bg-background/60 px-2.5 py-1.5 text-[11px] leading-relaxed text-muted-foreground">
      {data.rangeLabel && <div className="mb-0.5 font-medium text-foreground/70">{data.rangeLabel}</div>}
      {lines.map((l, i) => (
        <div key={i}>{l}</div>
      ))}
    </div>
  );
}

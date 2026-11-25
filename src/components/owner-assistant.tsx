"use client";
// ── Ask Nyansapo — the school-wide AI copilot (wave 63) ───────────────────
// Floating copilot available on EVERY page (mounted once in AppShell; open
// state lives in the store so any surface — FAB, top bar, palette — can
// summon it). Answers are grounded in the school's LIVE figures returned by
// /api/school/[id]/ops/assistant: morning brief, attendance, learners,
// money, discipline, visitors, staff and the siren — typed or spoken.
// Finance answers keep their verified-figures chips; wave-63 answers render
// section chips with a deep link into the matching module.
import { useEffect, useRef, useState } from "react";
import { Mic, Send, Sparkles, X, SunMedium } from "lucide-react";
import { useStore, type ModuleId } from "@/lib/store";
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
type Section = { title: string; lines: string[]; module?: string };
type AssistantData = {
  kind: "finance" | "domain" | "brief" | "general";
  domain?: string;
  sections?: Section[];
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
  "Today's morning brief",
  "Attendance today by class",
  "Who is absent today?",
  "Total income today?",
  "Learners per class",
  "Visitors on site",
  "Open discipline cases",
  "When is the next siren ring?",
];

/** Deep-link labels for section chips (ModuleId values from the store). */
const MODULE_LABELS: Record<string, string> = {
  attendance: "Attendance",
  students: "Students",
  discipline: "Discipline",
  frontoffice: "Front office",
  staffhub: "Staff hub",
  siren: "Siren",
  cashier: "Fees & billing",
  dashboard: "Dashboard",
};

/** API returns GHS decimals; fmtCedi expects integer cents. */
const cedi = (ghs: number | undefined | null) => fmtCedi(Math.round((ghs ?? 0) * 100));

export function AiCopilot() {
  const { currentSchoolId, aiOpen, setAiOpen, setModule } = useStore();
  const [msgs, setMsgs] = useState<Msg[]>([]);
  const [input, setInput] = useState("");
  const [busy, setBusy] = useState(false);
  const [listening, setListening] = useState(false);
  const [srSupported, setSrSupported] = useState<boolean | null>(null);

  const scrollRef = useRef<HTMLDivElement>(null);
  const recogRef = useRef<SpeechRecognitionLike | null>(null);
  const finalTextRef = useRef("");
  const sendRef = useRef<(text?: string) => void>(() => {});
  const open = aiOpen;

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

  // Esc closes the copilot (keyboard a11y)
  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => { if (e.key === "Escape") setAiOpen(false); };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [open, setAiOpen]);

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
        <div
          className="mb-3 flex h-[520px] max-h-[75vh] w-[400px] max-w-[94vw] flex-col overflow-hidden rounded-xl border bg-card shadow-xl motion-safe:animate-in motion-safe:fade-in-0 motion-safe:zoom-in-95 motion-safe:duration-150"
          role="dialog"
          aria-label="Ask Nyansapo AI copilot"
        >
          {/* header */}
          <div className="flex items-start justify-between gap-2 border-b px-3.5 py-2.5">
            <div className="min-w-0">
              <div className="flex items-center gap-1.5 text-sm font-semibold">
                <Sparkles className="size-3.5 shrink-0 text-primary" />
                Ask Nyansapo
              </div>
              <div className="truncate text-[11px] text-muted-foreground">Your school's live figures — brief, attendance, money, discipline, visitors</div>
            </div>
            <Button variant="ghost" size="icon" className="size-7 shrink-0" onClick={() => setAiOpen(false)} aria-label="Close copilot">
              <X className="size-4" />
            </Button>
          </div>

          {/* messages */}
          <div ref={scrollRef} className="nice-scroll flex-1 space-y-3 overflow-y-auto px-3 py-3" aria-live="polite">
            {msgs.length === 0 && !busy && (
              <div className="space-y-2.5 pt-1">
                <button
                  type="button"
                  onClick={() => send("Give me today's morning brief")}
                  className="flex w-full items-center gap-2 rounded-lg border border-primary/30 bg-primary/5 px-3 py-2.5 text-left text-xs font-medium text-foreground transition-colors hover:bg-primary/10"
                >
                  <SunMedium className="size-4 shrink-0 text-primary" />
                  <span>
                    Compose today's <span className="font-semibold">Headteacher's Morning Brief</span>
                    <span className="block text-[11px] font-normal text-muted-foreground">Attendance, money, arrears, visitors, discipline & the bell — verified live</span>
                  </span>
                </button>
                <p className="px-1 text-xs leading-relaxed text-muted-foreground">
                  Akwaaba! I answer with your school's <span className="font-medium text-foreground">live figures</span> — try one of these, or ask anything:
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
                  {m.role === "assistant" && m.data && <DataChips data={m.data} rangeLabel={m.rangeLabel} onOpen={(mod) => { setAiOpen(false); setModule(mod as ModuleId); }} />}
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
              placeholder={listening ? "Listening…" : "Ask about attendance, fees, visitors…"}
              className="h-8 text-sm"
              maxLength={600}
              disabled={busy}
              aria-label="Ask the copilot"
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
        onClick={() => setAiOpen(!open)}
        className="relative inline-flex size-12 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform hover:scale-105 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
        aria-label={open ? "Close Ask Nyansapo copilot" : "Open Ask Nyansapo copilot"}
        aria-expanded={open}
      >
        {open ? <X className="size-5" /> : <Sparkles className="size-5" />}
        {!open && (
          <span className="absolute -right-0.5 -top-0.5 rounded-full border bg-background px-1 py-px text-[9px] font-bold leading-none text-primary">AI</span>
        )}
      </button>
    </div>
  );
}

/** Compact verified-figures chip block under an answer — finance keeps the
 *  receipt figures; wave-63 answers render verified sections with a deep
 *  link into the matching module. */
function DataChips({ data, rangeLabel, onOpen }: { data: AssistantData; rangeLabel?: string | null; onOpen: (module: string) => void }) {
  if (data.kind === "finance") {
    const lines: string[] = [];
    if (typeof data.totalGHS === "number") lines.push(`Total ${cedi(data.totalGHS)} · ${data.count ?? 0} payments`);
    for (const m of data.byMethod?.slice(0, 5) ?? []) lines.push(`${PAYMENT_LABELS[m.method] ?? m.method} ${cedi(m.amountGHS)}`);
    if (typeof data.dailyFeesGHS === "number" && data.dailyFeesGHS > 0) lines.push(`Daily fees ${cedi(data.dailyFeesGHS)}`);
    if (typeof data.expensesGHS === "number") lines.push(`Expenses ${cedi(data.expensesGHS)}`);
    if (data.items?.length) lines.push(`${data.items.length} recent receipts`);
    if (!lines.length) return null;
    return (
      <div className="mt-2 rounded-md border bg-background/60 px-2.5 py-1.5 text-[11px] leading-relaxed text-muted-foreground">
        {(rangeLabel || data.rangeLabel) && <div className="mb-0.5 font-medium text-foreground/70">{rangeLabel ?? data.rangeLabel}</div>}
        {lines.map((l, i) => (
          <div key={i}>{l}</div>
        ))}
      </div>
    );
  }

  if (data.kind === "brief" || data.kind === "domain") {
    const sections = data.sections ?? [];
    if (!sections.length) return null;
    const openModule = data.kind === "brief" ? null : data.domain;
    return (
      <div className="mt-2 space-y-1.5 rounded-md border bg-background/60 px-2.5 py-2 text-[11px] leading-relaxed text-muted-foreground">
        {sections.map((s) => (
          <div key={s.title}>
            <div className="font-medium text-foreground/80">{s.title}</div>
            {s.lines.slice(0, 3).map((l, i) => (
              <div key={i} className="truncate" title={l}>{l}</div>
            ))}
            {s.lines.length > 3 && <div className="text-[10px]">+{s.lines.length - 3} more lines</div>}
          </div>
        ))}
        {openModule && MODULE_LABELS[openModule] && (
          <button
            type="button"
            onClick={() => onOpen(openModule)}
            className="mt-1 rounded-full border border-primary/40 bg-primary/10 px-2.5 py-1 font-medium text-primary transition-colors hover:bg-primary/20"
          >
            Open {MODULE_LABELS[openModule]} →
          </button>
        )}
      </div>
    );
  }

  return null;
}

/** Back-compat alias (the dashboard used to mount this as OwnerAssistant). */
export const OwnerAssistant = AiCopilot;

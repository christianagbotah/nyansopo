"use client";
import { useCallback, useEffect, useRef, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ModuleHeader, LoadingBlock, EmptyState, RiskPill } from "@/components/ui-helpers";
import { fmtCedi } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { BrainCircuit, Send, Sparkles, AlertTriangle, Wand2, Loader2 } from "lucide-react";
import { cn } from "@/lib/utils";

type Insight = { severity: string; title: string; detail: string };

export function AiCenter() {
  const { currentSchoolId } = useStore();
  const [tab, setTab] = useState("chat");
  return (
    <div>
      <ModuleHeader
        title="AI Assistant"
        description="Nyansapo AI sees your live school data — ask anything, get risk predictions, anomaly alerts and drafted remarks."
        actions={<Badge variant="secondary" className="gap-1.5"><Sparkles className="size-3.5" /> powered by GLM</Badge>}
      />
      <Tabs value={tab} onValueChange={setTab}>
        <TabsList>
          <TabsTrigger value="chat">Assistant</TabsTrigger>
          <TabsTrigger value="insights">Insights & risk</TabsTrigger>
        </TabsList>
        <TabsContent value="chat"><ChatPanel schoolId={currentSchoolId} /></TabsContent>
        <TabsContent value="insights"><InsightsPanel schoolId={currentSchoolId} /></TabsContent>
      </Tabs>
    </div>
  );
}

function ChatPanel({ schoolId }: { schoolId: string | null }) {
  const [messages, setMessages] = useState<{ role: string; content: string }[]>([
    { role: "assistant", content: "Akwaaba! I'm the Nyansapo AI assistant. I can see your school's live snapshot — enrolment, fee collection, flags and attendance. Try:\n• \"How is our fee collection this term?\"\n• \"Which learners should I follow up on?\"\n• \"Draft a message to parents about mid-term break\"" },
  ]);
  const [input, setInput] = useState("");
  const [busy, setBusy] = useState(false);
  const endRef = useRef<HTMLDivElement>(null);

  useEffect(() => { endRef.current?.scrollIntoView({ behavior: "smooth" }); }, [messages, busy]);

  async function send() {
    const text = input.trim();
    if (!text || busy) return;
    setInput("");
    const next = [...messages, { role: "user", content: text }];
    setMessages(next);
    setBusy(true);
    try {
      const res = await apiPost<{ reply: string }>("/api/ai/chat", { schoolId, messages: next.filter((m) => m.role !== "assistant" || m.content.length < 800).slice(-10) });
      setMessages((m) => [...m, { role: "assistant", content: res.reply }]);
    } catch (e: any) {
      setMessages((m) => [...m, { role: "assistant", content: `Sorry — I couldn't reach the AI service (${e.message}). Please try again.` }]);
    } finally { setBusy(false); }
  }

  return (
    <Card>
      <CardContent className="p-0">
        <div className="h-[440px] overflow-y-auto nice-scroll p-4 space-y-3">
          {messages.map((m, i) => (
            <div key={i} className={cn("flex gap-2.5", m.role === "user" && "justify-end")}>
              {m.role === "assistant" && <div className="size-8 shrink-0 rounded-full bg-primary grid place-items-center text-primary-foreground"><BrainCircuit className="size-4" /></div>}
              <div className={cn("rounded-xl px-3.5 py-2.5 max-w-[85%] text-sm whitespace-pre-wrap leading-relaxed", m.role === "user" ? "bg-primary text-primary-foreground" : "bg-secondary")}>
                {m.content}
              </div>
            </div>
          ))}
          {busy && (
            <div className="flex gap-2.5">
              <div className="size-8 shrink-0 rounded-full bg-primary grid place-items-center text-primary-foreground"><BrainCircuit className="size-4" /></div>
              <div className="rounded-xl bg-secondary px-4 py-3 flex items-center gap-1.5">
                <span className="size-1.5 rounded-full bg-muted-foreground animate-bounce" style={{ animationDelay: "0ms" }} />
                <span className="size-1.5 rounded-full bg-muted-foreground animate-bounce" style={{ animationDelay: "120ms" }} />
                <span className="size-1.5 rounded-full bg-muted-foreground animate-bounce" style={{ animationDelay: "240ms" }} />
              </div>
            </div>
          )}
          <div ref={endRef} />
        </div>
        <div className="border-t p-3 flex gap-2">
          <input
            className="flex-1 rounded-lg border bg-card px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-ring"
            placeholder="Ask about fees, defaulters, attendance, GES policy…"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyDown={(e) => e.key === "Enter" && send()}
            disabled={busy}
          />
          <Button className="gap-1.5" onClick={send} disabled={busy || !input.trim()}><Send className="size-4" /> Send</Button>
        </div>
      </CardContent>
    </Card>
  );
}

function InsightsPanel({ schoolId }: { schoolId: string | null }) {
  const [data, setData] = useState<{ insights: Insight[]; narrative: string; snapshot: any } | null>(null);
  const [busy, setBusy] = useState(false);
  const [risks, setRisks] = useState<any[] | null>(null);

  const fetchAll = useCallback(async () => {
    if (!schoolId) return;
    try {
      const d = await apiPost<any>("/api/ai/insights", { schoolId });
      setData(d);
    } catch (e: any) {
      toast({ title: "Insights failed", description: e.message, variant: "destructive" });
    }
    apiGet<any>(`/api/school/${schoolId}/students`).then((d) => {
      setRisks(d.students.filter((s: any) => s.risk && s.risk.level !== "LOW").sort((a: any, b: any) => b.risk.score - a.risk.score));
    }).catch(() => setRisks([]));
  }, [schoolId]);

  useEffect(() => {
    let stale = false;
    const t = setTimeout(() => { if (!stale) fetchAll(); }, 0);
    return () => { stale = true; clearTimeout(t); };
  }, [fetchAll]);

  const refresh = async () => {
    setBusy(true);
    await fetchAll();
    setBusy(false);
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <Card>
        <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
          <CardTitle className="text-base flex items-center gap-2"><AlertTriangle className="size-4 text-orange-500" /> Anomaly alerts</CardTitle>
          <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={refresh} disabled={busy}>{busy ? <Loader2 className="size-3.5 animate-spin" /> : <Wand2 className="size-3.5" />} Refresh</Button>
        </CardHeader>
        <CardContent className="space-y-3 max-h-[420px] overflow-y-auto nice-scroll">
          {!data && <LoadingBlock label="Analysing school data…" />}
          {data?.insights.map((ins, i) => (
            <div key={i} className={cn("rounded-lg border p-3",
              ins.severity === "high" ? "border-red-300 bg-red-50 dark:bg-red-950/30 dark:border-red-900" :
              ins.severity === "medium" ? "border-amber-300 bg-amber-50/60 dark:bg-amber-950/20 dark:border-amber-800" : "border-border")}>
              <div className="font-medium text-sm flex items-center gap-2">
                {ins.severity === "high" && <AlertTriangle className="size-4 text-destructive" />}
                {ins.title}
              </div>
              <p className="text-xs mt-1 text-muted-foreground leading-relaxed">{ins.detail}</p>
            </div>
          ))}
          {data?.narrative && (
            <div className="rounded-lg border-2 border-dashed p-3">
              <div className="text-xs font-semibold uppercase text-muted-foreground flex items-center gap-1.5 mb-1.5"><Sparkles className="size-3.5 text-primary" /> AI advisory note</div>
              <p className="text-sm leading-relaxed">{data.narrative}</p>
            </div>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><BrainCircuit className="size-4" /> Fee-default & disengagement risk</CardTitle></CardHeader>
        <CardContent className="space-y-2 max-h-[420px] overflow-y-auto nice-scroll">
          {!risks && <LoadingBlock label="Scoring learners…" />}
          {risks?.length === 0 && <EmptyState title="No elevated risk learners" hint="Everyone is LOW risk — great stewardship." />}
          {risks?.map((r) => (
            <div key={r.id} className="rounded-lg border p-3 flex items-center justify-between gap-3">
              <div>
                <div className="text-sm font-medium">{r.name}</div>
                <div className="text-xs text-muted-foreground">{r.classroom} · balance {fmtCedi((r.invoice?.billed ?? 0) - (r.invoice?.paid ?? 0))} · {r.flags.length} flag(s)</div>
              </div>
              <RiskPill level={r.risk.level} score={r.risk.score} />
            </div>
          ))}
          <p className="text-[11px] text-muted-foreground pt-2 border-t">
            Risk model: arrears ratio (55%) + attendance (25%) + active flags + academic signal — explainable and auditable per learner.
          </p>
        </CardContent>
      </Card>
    </div>
  );
}

"use client";

import { useCallback, useEffect, useState } from "react";
import { useParams, useSearchParams } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Check, X, CalendarPlus, Clock, MapPin, Phone, ShieldAlert } from "lucide-react";

/* ── Public appointment answer page (/appoint/{code}?t=…) ──────
   The one-tap page the front-desk request SMS/email points the visited
   staff member at: see who is waiting and when, then ACCEPT the slot,
   DECLINE with a reason, or RESCHEDULE by proposing a new date & time —
   including the quick "in 30 minutes" option every Ghanaian office runs
   on. The token in the link authorizes the actions; the front desk sees
   the answer land in real time and relays it to the visitor. */

type ApptView = {
  code: string | null;
  status: string;
  canRespond: boolean;
  title: string;
  visitorName: string;
  visitorOrg: string | null;
  scheduledAt: string;
  durationMin: number | null;
  location: string | null;
  purpose: string | null;
  host: { name: string; title: string | null; role: string; category: string } | null;
  response: { at: string | null; byName: string | null; note: string | null; movedFrom: string | null };
  school: { name: string; logoColor: string };
  schoolHoursNote: string | null;
};

const STATUS_LABEL: Record<string, string> = {
  SCHEDULED: "Awaiting your response",
  CONFIRMED: "Accepted — the meeting stands",
  DECLINED: "Declined",
  RESCHEDULED: "Moved — the desk is informing the visitor",
  COMPLETED: "Completed",
  CANCELLED: "Cancelled by the desk",
  NO_SHOW: "Marked as no-show",
};

const dt = (iso: string) =>
  new Date(iso).toLocaleString("en-GB", { weekday: "long", day: "numeric", month: "long", hour: "2-digit", minute: "2-digit" });

export default function PublicAppointmentPage() {
  const params = useParams<{ code: string }>();
  const search = useSearchParams();
  const code = params?.code ?? "";
  const token = search.get("t") ?? "";
  const [data, setData] = useState<ApptView | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [note, setNote] = useState("");
  const [showReschedule, setShowReschedule] = useState(false);
  const [newDate, setNewDate] = useState("");
  const [newTime, setNewTime] = useState("");

  const load = useCallback(async () => {
    try {
      const r = await fetch(`/api/public/appointments/${encodeURIComponent(code)}?t=${encodeURIComponent(token)}`, { cache: "no-store" });
      const j = await r.json();
      if (!r.ok) throw new Error(j.error ?? "Not found");
      setData(j);
      setError(null);
    } catch (e: any) {
      setError(e?.message ?? "Could not load this appointment link");
    }
  }, [code, token]);

  useEffect(() => { load(); }, [load]);

  async function decide(decision: "ACCEPT" | "DECLINE" | "RESCHEDULE", scheduledAt?: string) {
    setBusy(true);
    try {
      const r = await fetch(`/api/public/appointments/${encodeURIComponent(code)}`, {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({ t: token, decision, note: note || undefined, scheduledAt }),
      });
      const j = await r.json();
      if (!r.ok) throw new Error(j.error ?? "Could not record your answer");
      setShowReschedule(false);
      await load();
    } catch (e: any) {
      setError(e?.message ?? "Could not record your answer");
    }
    setBusy(false);
  }

  const in30 = () => {
    const d = new Date(Date.now() + 30 * 60_000);
    setNewDate(d.toISOString().slice(0, 10));
    setNewTime(d.toISOString().slice(11, 16));
  };
  const in60 = () => {
    const d = new Date(Date.now() + 60 * 60_000);
    setNewDate(d.toISOString().slice(0, 10));
    setNewTime(d.toISOString().slice(11, 16));
  };
  const submitReschedule = () => {
    if (!newDate || !newTime) return;
    decide("RESCHEDULE", new Date(`${newDate}T${newTime}:00`).toISOString());
  };

  const accent = data?.school?.logoColor ?? "#0F6B47";
  const live = data?.canRespond ?? false;

  return (
    <div className="min-h-screen flex flex-col items-center justify-center p-4" style={{ background: `linear-gradient(160deg, ${accent}14, transparent 60%)` }}>
      <div className="w-full max-w-md">
        <div className="rounded-t-2xl px-6 py-5 flex items-center gap-3 text-white" style={{ background: accent }}>
          <div className="w-10 h-10 rounded-xl bg-white/20 grid place-items-center font-bold text-lg">A</div>
          <div>
            <div className="font-semibold leading-tight">{data?.school.name ?? "School"}</div>
            <div className="text-xs text-white/80">Appointment request {data?.code ? `· ${data.code}` : ""}</div>
          </div>
        </div>

        <div className="bg-card border border-border rounded-b-2xl shadow-sm p-6 space-y-5">
          {error ? (
            <div className="text-center space-y-2">
              <ShieldAlert className="mx-auto size-8 text-muted-foreground" />
              <p className="font-semibold">This appointment link is not available</p>
              <p className="text-sm text-muted-foreground">{error}</p>
            </div>
          ) : !data ? (
            <div className="py-10 text-center text-sm text-muted-foreground animate-pulse">Loading…</div>
          ) : (
            <>
              <div className="rounded-xl border p-4 space-y-2">
                <p className="text-sm text-muted-foreground">
                  <span className="font-semibold text-foreground">{data.visitorName}</span>
                  {data.visitorOrg ? ` · ${data.visitorOrg}` : ""} is at the front desk to see
                </p>
                <p className="text-lg font-bold leading-tight">
                  {data.host?.name ?? "the front desk"}
                  {data.host?.title ? <span className="block text-xs font-medium text-muted-foreground">{data.host.title}</span> : null}
                </p>
                <p className="text-sm font-medium">{data.title}</p>
                {data.purpose ? <p className="text-xs text-muted-foreground">{data.purpose}</p> : null}
                <div className="flex flex-wrap gap-x-4 gap-y-1 pt-1 text-sm">
                  <span className="inline-flex items-center gap-1.5"><Clock className="size-3.5 text-muted-foreground" /> {dt(data.scheduledAt)}{data.durationMin ? ` · ${data.durationMin} min` : ""}</span>
                  {data.location ? <span className="inline-flex items-center gap-1.5"><MapPin className="size-3.5 text-muted-foreground" /> {data.location}</span> : null}
                </div>
                {data.schoolHoursNote ? <p className="text-[11px] text-muted-foreground">{data.schoolHoursNote}</p> : null}
              </div>

              <div className="rounded-xl px-4 py-3 text-sm font-semibold" style={{ background: `${accent}18`, color: accent }}>
                {STATUS_LABEL[data.status] ?? data.status}
                {data.response.at && data.response.byName ? (
                  <span className="block text-xs font-normal text-muted-foreground">
                    Answered by {data.response.byName}{data.response.note ? ` — "${data.response.note}"` : ""}
                    {data.response.movedFrom ? ` · moved from ${dt(data.response.movedFrom)}` : ""}
                  </span>
                ) : null}
              </div>

              {live ? (
                <>
                  {showReschedule ? (
                    <div className="space-y-3 rounded-xl border p-4">
                      <p className="text-sm font-semibold">Propose a new time</p>
                      <div className="flex flex-wrap gap-2">
                        <Button type="button" size="sm" variant="outline" onClick={in30}>In 30 minutes</Button>
                        <Button type="button" size="sm" variant="outline" onClick={in60}>In 1 hour</Button>
                      </div>
                      <div className="grid grid-cols-2 gap-2">
                        <Input type="date" value={newDate} onChange={(e) => setNewDate(e.target.value)} />
                        <Input type="time" value={newTime} onChange={(e) => setNewTime(e.target.value)} />
                      </div>
                      <Input value={note} onChange={(e) => setNote(e.target.value)} placeholder="Note to the front desk (optional)" />
                      <div className="flex gap-2">
                        <Button size="sm" onClick={submitReschedule} disabled={busy || !newDate || !newTime}>
                          <CalendarPlus className="size-4" /> {busy ? "Sending…" : "Send new time"}
                        </Button>
                        <Button size="sm" variant="ghost" onClick={() => setShowReschedule(false)}>Back</Button>
                      </div>
                    </div>
                  ) : (
                    <div className="space-y-2">
                      <div className="grid grid-cols-3 gap-2">
                        <Button className="h-14 flex-col gap-1" onClick={() => decide("ACCEPT")} disabled={busy}>
                          <Check className="size-5" /><span className="text-xs font-semibold">Accept</span>
                        </Button>
                        <Button className="h-14 flex-col gap-1" variant="outline" onClick={() => setShowReschedule(true)} disabled={busy}>
                          <CalendarPlus className="size-5" /><span className="text-xs font-semibold">Reschedule</span>
                        </Button>
                        <Button className="h-14 flex-col gap-1" variant="outline" onClick={() => decide("DECLINE")} disabled={busy}>
                          <X className="size-5" /><span className="text-xs font-semibold">Decline</span>
                        </Button>
                      </div>
                      <Input value={note} onChange={(e) => setNote(e.target.value)} placeholder="Note to the front desk (optional — reason, alternative …)" />
                      <p className="text-xs text-muted-foreground">
                        Your answer reaches the front desk instantly and they relay it to {data.visitorName}.
                        Rescheduling accepts the visit at the new time — pick anything outside your teaching slots.
                      </p>
                    </div>
                  )}
                </>
              ) : (
                <p className="text-center text-xs text-muted-foreground">
                  This request has been answered — call the front desk for any change.
                </p>
              )}

              <p className="text-center text-[11px] text-muted-foreground flex items-center justify-center gap-1">
                <Phone className="size-3" /> Nyansapo school office · powered by the front desk
              </p>
            </>
          )}
        </div>
      </div>
    </div>
  );
}

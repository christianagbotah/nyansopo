"use client";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate } from "@/lib/format";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { DateField } from "@/components/date-field";
import { Label } from "@/components/ui/label";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { toast } from "@/hooks/use-toast";
import { Bus, Route as RouteIcon, Users, Phone, MapPin, ArrowRightLeft, Wallet, ClipboardCheck, Save, CheckCircle2, AlertTriangle } from "lucide-react";

type Rider = {
  id: string; studentId: string; student: string; classroom: string | null;
  pickupStop: string | null; dropStop: string | null; guardianPhone: string;
  farePaid: number; fareOverride: number | null;
  boardings: { date: string; direction: string; fareCents: number; paidCents: number }[];
};
type TRoute = {
  id: string; name: string; vehicleReg: string; vehicleModel: string | null; driverName: string; driverPhone: string;
  capacity: number; fare: number; farePerTrip: number; stops: string[]; active: boolean;
  riders: Rider[]; utilisation: number; fareRevenue: number; fareCollected: number;
  trips: { id: string; date: string; morning: number | null; evening: number | null; notes: string | null }[];
};
type TransportData = {
  termLabel: string;
  routes: TRoute[];
  summary: { routes: number; learners: number; capacity: number; fareRevenue: number; fareCollected: number };
};

const todayStr = () => new Date().toISOString().slice(0, 10);
const DIR_LABEL: Record<string, string> = { IN: "Picked up", OUT: "Dropped", BOTH: "Both ways", NONE: "Not riding" };
const DIR_CLASS: Record<string, string> = { IN: "bg-sky-600", OUT: "bg-violet-600", BOTH: "bg-emerald-600", NONE: "bg-zinc-500" };

export function ConductorPortal() {
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<TransportData | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const [routeIdx, setRouteIdx] = useState(0);
  const [date, setDate] = useState(todayStr());
  const [morning, setMorning] = useState("");
  const [evening, setEvening] = useState("");

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<TransportData>(`/api/school/${currentSchoolId}/ops/transport`)
      .then((d) => { setData(d); setRouteIdx((i) => Math.min(i, Math.max(0, d.routes.length - 1))); })
      .catch((e) => setErr(e.message));
  }, [currentSchoolId]);
  useEffect(() => { load(); }, [load]); // eslint-disable-line react-hooks/set-state-in-effect

  const route = data?.routes[routeIdx];

  const todayBoardings = useMemo(() => {
    const m: Record<string, Rider["boardings"][number]> = {};
    (route?.riders ?? []).forEach((r) => {
      const b = r.boardings.find((x) => x.date === date);
      if (b) m[r.id] = b;
    });
    return m;
  }, [route, date]);

  useEffect(() => {
    const trip = route?.trips.find((t) => new Date(t.date).toISOString().slice(0, 10) === date);
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setMorning(trip?.morning != null ? String(trip.morning) : "");
    setEvening(trip?.evening != null ? String(trip.evening) : "");
  }, [route, date]);

  async function saveBoarding(assignmentId: string, direction: string) {
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/ops/transport`, { action: "boarding-save", assignmentId, date, direction });
      toast({
        title: `${DIR_LABEL[res.boarding?.direction ?? direction] ?? DIR_LABEL[direction]} marked`,
        description: res.message ?? `Recorded for ${fmtDate(date)}`,
      });
      load();
    } catch (e: any) {
      toast({ title: "Could not save boarding", description: e.message, variant: "destructive" });
    }
  }

  async function saveTrip() {
    if (!route) return;
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/transport`, {
        action: "record-trip", routeId: route.id, date,
        morningCount: morning === "" ? null : Number(morning),
        eveningCount: evening === "" ? null : Number(evening),
      });
      toast({ title: "Trip recorded", description: `${route.name} · ${fmtDate(date)}` });
      load();
    } catch (e: any) {
      toast({ title: "Could not record trip", description: e.message, variant: "destructive" });
    }
  }

  if (err) return <div className="text-destructive text-sm">{err}</div>;
  if (!data) return <LoadingBlock label="Loading the bus…" />;
  if (!data.routes.length) return <EmptyState icon={<Bus className="size-8" />} title="No active routes" hint="Routes are set up by the transport manager." />;

  const boardedIn = Object.values(todayBoardings).filter((b) => b.direction === "IN" || b.direction === "BOTH").length;
  const boardedOut = Object.values(todayBoardings).filter((b) => b.direction === "OUT" || b.direction === "BOTH").length;
  // iEduPro one-way model: what the day's marked boardings add up to (in 1× · out 1× · both 2×)
  const faresDueToday = Object.values(todayBoardings).reduce((s, b) => s + b.fareCents, 0);
  const faresPaidToday = Object.values(todayBoardings).reduce((s, b) => s + b.paidCents, 0);
  const faresOwingToday = faresDueToday - faresPaidToday;

  return (
    <div>
      <ModuleHeader
        title="Conductor Portal"
        description={`${data.termLabel} — daily manifests, boarding marks and trip counts for your routes.`}
        actions={
          <div className="flex items-center gap-2">
            <Label className="text-xs text-muted-foreground">Date</Label>
            <DateField value={date} max={todayStr()} onChange={(v) => setDate(v || todayStr())} className="h-8 w-40" />
          </div>
        }
      />

      {/* route picker */}
      <div className="flex flex-wrap gap-2 mb-4">
        {data.routes.map((r, i) => (
          <button key={r.id} onClick={() => setRouteIdx(i)}
            className={`flex items-start gap-2.5 rounded-xl border px-3 py-2 text-left transition-colors ${i === routeIdx ? "border-primary bg-primary/5" : "hover:bg-secondary/50"}`}>
            <span className="mt-0.5 flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary"><Bus className="size-4" /></span>
            <span>
              <span className="block text-sm font-medium leading-tight">{r.name}</span>
              <span className="block text-[11px] text-muted-foreground">{r.vehicleReg} · {r.riders.length} riders · {r.driverName}</span>
            </span>
          </button>
        ))}
      </div>

      {route && (
        <>
          {/* today's stats */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            <Tile icon={<Users className="size-4" />} label="Riders on manifest" value={route.riders.length} sub={`${Math.round((route.riders.length / Math.max(1, route.capacity)) * 100)}% of ${route.capacity} seats`} />
            <Tile icon={<MapPin className="size-4" />} label="Picked up (AM)" value={`${boardedIn}/${route.riders.length}`} sub="boarded this morning" tone="good" />
            <Tile icon={<ArrowRightLeft className="size-4" />} label="Dropped (PM)" value={`${boardedOut}/${route.riders.length}`} sub="dropped this afternoon" />
            <Tile icon={<Wallet className="size-4" />} label="Fares settled today" value={fmtCedi(faresPaidToday, { compact: true })} sub={`of ${fmtCedi(faresDueToday, { compact: true })} marked${faresOwingToday > 0 ? ` · ${fmtCedi(faresOwingToday, { compact: true })} owing` : " — all settled"}`} />
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {/* manifest */}
            <Card className="lg:col-span-2">
              <CardHeader className="pb-2">
                <CardTitle className="text-base flex items-center gap-2"><ClipboardCheck className="size-4" /> Manifest — {route.name} · {fmtDate(date)}</CardTitle>
              </CardHeader>
              <CardContent>
                {route.riders.length ? (
                  <div className="max-h-[26rem] overflow-y-auto nice-scroll">
                    <Table>
                      <TableHeader className="sticky top-0 bg-card">
                        <TableRow>
                          <TableHead>Learner</TableHead>
                          <TableHead className="hidden md:table-cell">Stops</TableHead>
                          <TableHead>
                            <div className="flex flex-col gap-0.5">
                              Boarding
                              <span className="text-[10px] font-normal normal-case text-muted-foreground">tap to mark today</span>
                            </div>
                          </TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {route.riders.map((r) => {
                          const b = todayBoardings[r.id];
                          return (
                            <TableRow key={r.id}>
                              <TableCell>
                                <div className="font-medium">{r.student}</div>
                                <div className="text-xs text-muted-foreground">{r.classroom ?? "—"} · {r.guardianPhone}</div>
                              </TableCell>
                              <TableCell className="hidden md:table-cell text-xs text-muted-foreground">
                                <div className="flex items-center gap-1"><MapPin className="size-3" /> {r.pickupStop ?? "—"}</div>
                                <div className="flex items-center gap-1 opacity-60"><ArrowRightLeft className="size-3" /> {r.dropStop ?? "—"}</div>
                              </TableCell>
                              <TableCell>
                                <div className="inline-flex rounded-lg border bg-muted/40 p-0.5" role="group" aria-label="Mark boarding">
                                  {(["IN", "BOTH", "OUT", "NONE"] as const).map((d) => (
                                    <button key={d} onClick={() => saveBoarding(r.id, d)} title={d === "IN" ? "Morning only" : d === "BOTH" ? "Morning & afternoon" : d === "OUT" ? "Afternoon only" : "Not riding today"}
                                      aria-pressed={b?.direction === d}
                                      className={`rounded-md px-2 py-1 text-[10px] font-semibold transition-all ${b?.direction === d ? `${DIR_CLASS[d]} text-white shadow-sm` : "text-muted-foreground hover:bg-background hover:text-foreground hover:shadow-sm"}`}>
                                      {d === "IN" ? "AM" : d === "BOTH" ? "AM+PM" : d === "OUT" ? "PM" : "None"}
                                    </button>
                                  ))}
                                </div>
                                {b && b.paidCents < b.fareCents && b.direction !== "NONE" && (
                                  <span className="mt-1 inline-flex items-center gap-1 text-[10px] text-amber-600"><AlertTriangle className="size-3" /> {fmtCedi(b.fareCents - b.paidCents)} fare owing</span>
                                )}
                              </TableCell>
                            </TableRow>
                          );
                        })}
                      </TableBody>
                    </Table>
                  </div>
                ) : <EmptyState title="No learners assigned to this route" hint="The transport manager assigns riders." />}
              </CardContent>
            </Card>

            {/* trip recorder + route info */}
            <div className="space-y-4">
              <Card>
                <CardHeader className="pb-2"><CardTitle className="text-base flex items-center gap-2"><RouteIcon className="size-4" /> Record trip</CardTitle></CardHeader>
                <CardContent className="space-y-3">
                  <div className="grid grid-cols-2 gap-2">
                    <div className="space-y-1">
                      <Label className="text-xs">Morning count</Label>
                      <Input type="number" min={0} value={morning} onChange={(e) => setMorning(e.target.value)} placeholder={`e.g. ${boardedIn || 12}`} />
                      <button className="text-[10px] text-primary hover:underline" onClick={() => setMorning(String(boardedIn))}>use {boardedIn} AM mark{boardedIn === 1 ? "" : "s"}</button>
                    </div>
                    <div className="space-y-1">
                      <Label className="text-xs">Afternoon count</Label>
                      <Input type="number" min={0} value={evening} onChange={(e) => setEvening(e.target.value)} placeholder={`e.g. ${boardedOut || 12}`} />
                      <button className="text-[10px] text-primary hover:underline" onClick={() => setEvening(String(boardedOut))}>use {boardedOut} PM mark{boardedOut === 1 ? "" : "s"}</button>
                    </div>
                  </div>
                  {(Number(morning) > 0 || Number(evening) > 0) && (Number(morning) !== boardedIn || Number(evening) !== boardedOut) && (
                    <p className="text-[11px] text-amber-600 flex items-center gap-1"><AlertTriangle className="size-3" /> Counts differ from boarding marks (AM {boardedIn} / PM {boardedOut}) — fine if extra riders joined the run.</p>
                  )}
                  <Button size="sm" className="w-full gap-1.5" onClick={saveTrip}><Save className="size-3.5" /> Save trip for {fmtDate(date)}</Button>
                  <p className="text-[11px] text-muted-foreground">Counts are compared against boarding marks — record them at the end of each run.</p>
                </CardContent>
              </Card>

              <Card>
                <CardHeader className="pb-2"><CardTitle className="text-base">Route & driver</CardTitle></CardHeader>
                <CardContent className="text-sm space-y-1.5">
                  <div className="flex justify-between"><span className="text-muted-foreground">Vehicle</span><b>{route.vehicleReg}</b></div>
                  <div className="flex justify-between"><span className="text-muted-foreground">Model</span><span>{route.vehicleModel ?? "—"}</span></div>
                  <div className="flex justify-between"><span className="text-muted-foreground">Driver</span><span>{route.driverName}</span></div>
                  <div className="flex items-center justify-between"><span className="text-muted-foreground">Driver phone</span><a className="flex items-center gap-1 text-primary" href={`tel:${route.driverPhone}`}><Phone className="size-3" /> {route.driverPhone}</a></div>
                  <div className="flex justify-between"><span className="text-muted-foreground">One-way fare</span><b className="tabular-nums">{fmtCedi(route.farePerTrip)}</b></div>
                  <div className="flex justify-between"><span className="text-muted-foreground">Both ways (×2)</span><span className="tabular-nums">{fmtCedi(route.farePerTrip * 2)}</span></div>
                  <div className="flex justify-between"><span className="text-muted-foreground">Term fare</span><span className="tabular-nums">{fmtCedi(route.fare)}</span></div>
                  {!!route.stops.length && (
                    <div className="pt-2">
                      <div className="text-xs text-muted-foreground mb-1">Stops</div>
                      <div className="flex flex-wrap gap-1">{route.stops.map((s) => <Badge key={s} variant="outline" className="text-[10px]">{s}</Badge>)}</div>
                    </div>
                  )}
                </CardContent>
              </Card>

              <Card>
                <CardHeader className="pb-2"><CardTitle className="text-base">Recent trips</CardTitle></CardHeader>
                <CardContent className="space-y-1.5 max-h-48 overflow-y-auto nice-scroll">
                  {route.trips.length ? route.trips.slice(0, 6).map((t) => (
                    <div key={t.id} className="flex items-center justify-between rounded-lg border px-2.5 py-1.5 text-sm">
                      <span className="text-xs text-muted-foreground">{fmtDate(t.date)}</span>
                      <span className="flex items-center gap-2">
                        {t.morning != null && <Badge variant="secondary" className="text-[10px]">AM {t.morning}</Badge>}
                        {t.evening != null && <Badge variant="secondary" className="text-[10px]">PM {t.evening}</Badge>}
                      </span>
                    </div>
                  )) : <EmptyState title="No trips recorded yet" />}
                </CardContent>
              </Card>
            </div>
          </div>
        </>
      )}
    </div>
  );
}

function Tile({ icon, label, value, sub, tone }: { icon: React.ReactNode; label: string; value: React.ReactNode; sub?: string; tone?: string }) {
  return (
    <div className="rounded-xl border bg-card p-4">
      <div className="flex items-center justify-between"><span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</span>{icon}</div>
      <div className={`mt-1.5 text-xl font-bold tabular-nums ${tone === "good" ? "text-emerald-600 dark:text-emerald-400" : ""}`}>{value}</div>
      <div className="text-[11px] text-muted-foreground">{sub}</div>
    </div>
  );
}

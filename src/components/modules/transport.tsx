"use client";
import { useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { StatCard, ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, PAYMENT_LABELS } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { Bus, Plus, Phone, MapPin, UserPlus, ClipboardCheck, Banknote, Users, Pencil, Trash2, ArrowRight, HandCoins, CheckCircle2, Printer } from "lucide-react";
import { ReceiptDocument } from "@/components/receipts";

type Rider = { id: string; studentId: string; student: string; classroom: string | null; pickupStop: string | null; dropStop: string | null; fareOverride: number | null; guardianPhone: string; farePaid: number };
type Trip = { id: string; date: string; morning: number | null; evening: number | null; notes: string | null; recordedByName: string | null };
type Route = {
  id: string; name: string; vehicleReg: string; vehicleModel: string | null; driverName: string; driverPhone: string;
  capacity: number; fare: number; stops: string[]; active: boolean; riders: Rider[]; utilisation: number; fareRevenue: number; fareCollected: number; trips: Trip[];
};
type TransportData = {
  termLabel: string;
  routes: Route[];
  unassigned: { id: string; name: string; classroom: string; guardianPhone: string }[];
  summary: { routes: number; learners: number; capacity: number; fareRevenue: number; fareCollected: number };
};

export function Transportation() {
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<TransportData | null>(null);
  const [routeDialog, setRouteDialog] = useState<Route | "new" | null>(null);
  const [assignFor, setAssignFor] = useState<Route | null>(null);
  const [tripFor, setTripFor] = useState<Route | null>(null);
  const [fareFor, setFareFor] = useState<{ rider: Rider; route: Route } | null>(null);
  const [receipt, setReceipt] = useState<any | null>(null);
  const [busy, setBusy] = useState(false);

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<TransportData>(`/api/school/${currentSchoolId}/ops/transport`).then(setData).catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(() => { load(); }, [currentSchoolId]);

  async function billTerm() {
    if (!currentSchoolId) return;
    setBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/ops/transport`, { action: "bill-term" });
      toast({
        title: "Transport fares billed",
        description: `${res.billed} invoices updated${res.skipped ? ` · ${res.skipped} already had the fare` : ""}${res.noInvoice ? ` · ${res.noInvoice} learners without current-term invoices` : ""}.`,
      });
      load();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  if (!data) return <LoadingBlock label="Loading transportation…" />;

  return (
    <div>
      <ModuleHeader
        title="Transportation"
        description="Bus routes, vehicles and drivers — assign learners to stops, record daily trips and bill the termly fare straight onto invoices."
        actions={
          <div className="flex gap-2 flex-wrap">
            <Button size="sm" variant="outline" className="gap-1.5" onClick={billTerm} disabled={busy}><Banknote className="size-4" /> Bill term fares</Button>
            <Button size="sm" className="gap-1.5" onClick={() => setRouteDialog("new")}><Plus className="size-4" /> New route</Button>
          </div>
        }
      />

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <StatCard icon={<Bus className="size-4" />} label="Active routes" value={data.summary.routes} sub={`${data.summary.capacity} seats total`} />
        <StatCard icon={<Users className="size-4" />} label="Learners on buses" value={data.summary.learners} tone="primary" sub={`${Math.round((data.summary.learners / Math.max(1, data.summary.capacity)) * 100)}% capacity`} />
        <StatCard icon={<Banknote className="size-4" />} label="Fare collected" value={fmtCedi(data.summary.fareCollected, { compact: true })} tone="gold" sub={`${fmtCedi(data.summary.fareRevenue, { compact: true })} billed · ${data.termLabel}`} />
        <StatCard icon={<ClipboardCheck className="size-4" />} label="Trips logged" value={data.routes.reduce((a, r) => a + r.trips.length, 0)} sub="recent runs" />
      </div>

      {data.routes.length === 0 && <Card className="mt-5"><CardContent><EmptyState icon={<Bus className="size-5" />} title="No routes yet" hint="Create your first bus route to start assigning learners." /></CardContent></Card>}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-5">
        {data.routes.map((r) => (
          <Card key={r.id} className={r.active ? "" : "opacity-60"}>
            <CardHeader className="pb-3 flex-row items-start justify-between space-y-0">
              <div>
                <CardTitle className="text-base flex items-center gap-2"><Bus className="size-4 text-primary" /> {r.name}</CardTitle>
                <div className="text-xs text-muted-foreground mt-1">
                  {r.vehicleReg}{r.vehicleModel ? ` · ${r.vehicleModel}` : ""} · Driver: <b>{r.driverName}</b> <a href={`tel:${r.driverPhone}`} className="text-primary hover:underline">{r.driverPhone}</a>
                </div>
              </div>
              <div className="flex items-center gap-1.5">
                {!r.active && <Badge variant="outline">paused</Badge>}
                <Badge variant="secondary">{fmtCedi(r.fare)}/term</Badge>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-1">
                <div className="flex justify-between text-xs"><span className="text-muted-foreground">Capacity</span><span className="tabular-nums">{r.riders.length}/{r.capacity} · {r.utilisation}%</span></div>
                <Progress value={r.utilisation} className="h-1.5" />
              </div>
              <div className="flex flex-wrap gap-1">
                {r.stops.map((s) => <Badge key={s} variant="outline" className="text-[10px] gap-1 font-normal"><MapPin className="size-2.5" />{s}</Badge>)}
              </div>
              <div>
                <div className="flex items-center justify-between mb-1.5">
                  <span className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Riders ({r.riders.length})</span>
                  <Button size="sm" variant="ghost" className="h-6 gap-1 text-xs" onClick={() => setAssignFor(r)}><UserPlus className="size-3" /> Assign</Button>
                </div>
                <div className="space-y-1 max-h-44 overflow-y-auto nice-scroll pr-1">
                  {r.riders.slice(0, 50).map((x) => {
                    const fare = x.fareOverride ?? r.fare;
                    const settled = x.farePaid >= fare;
                    return (
                    <div key={x.id} className="flex items-center justify-between gap-2 text-sm border-b last:border-0 py-1">
                      <div className="min-w-0">
                        <span className="font-medium truncate">{x.student}</span>
                        <span className="text-xs text-muted-foreground"> · {x.classroom ?? "—"}</span>
                        <div className="text-[10px] text-muted-foreground flex items-center gap-1">
                          <MapPin className="size-2.5" />{x.pickupStop ?? "—"} <ArrowRight className="size-2.5" /> {x.dropStop ?? x.pickupStop ?? "—"}
                        </div>
                      </div>
                      <div className="flex items-center gap-1 shrink-0">
                        {x.fareOverride && <Badge variant="outline" className="text-[9px]">{fmtCedi(x.fareOverride)}</Badge>}
                        {settled ? (
                          <Badge className="text-[9px] gap-1" variant="secondary"><CheckCircle2 className="size-2.5" /> fare paid</Badge>
                        ) : (
                          <Button size="sm" variant="outline" className="h-6 gap-1 text-[11px] px-2" onClick={() => setFareFor({ rider: x, route: r })}>
                            <HandCoins className="size-3" /> Collect {fmtCedi(fare - x.farePaid, { compact: true })}
                          </Button>
                        )}
                        <button className="text-muted-foreground hover:text-destructive" title="Remove from route"
                          onClick={async () => { await apiPost(`/api/school/${currentSchoolId}/ops/transport`, { action: "unassign", assignmentId: x.id }); toast({ title: "Removed from route" }); load(); }}>
                          <Trash2 className="size-3.5" />
                        </button>
                      </div>
                    </div>
                    );
                  })}
                </div>
              </div>
              <div className="border-t pt-3">
                <div className="flex items-center justify-between mb-1.5">
                  <span className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Recent trips</span>
                  <Button size="sm" variant="ghost" className="h-6 gap-1 text-xs" onClick={() => setTripFor(r)}><ClipboardCheck className="size-3" /> Log trip</Button>
                </div>
                <div className="space-y-1">
                  {r.trips.slice(0, 3).map((t) => (
                    <div key={t.id} className="flex items-center justify-between text-xs text-muted-foreground">
                      <span>{fmtDate(t.date)}</span>
                      <span className="tabular-nums">AM {t.morning ?? "—"} · PM {t.evening ?? "—"}</span>
                    </div>
                  ))}
                  {r.trips.length === 0 && <div className="text-xs text-muted-foreground">No trips logged yet.</div>}
                </div>
              </div>
              <div className="flex gap-2 pt-1">
                <Button size="sm" variant="outline" className="h-7 flex-1 gap-1" onClick={() => setTripFor(r)}><ClipboardCheck className="size-3.5" /> Record today&apos;s trip</Button>
                <Button size="sm" variant="ghost" className="h-7" onClick={() => setRouteDialog(r)}><Pencil className="size-3.5" /> Edit</Button>
                <Button size="sm" variant="ghost" className="h-7"
                  onClick={async () => { await apiPost(`/api/school/${currentSchoolId}/ops/transport`, { action: "toggle-route", id: r.id }); toast({ title: r.active ? "Route paused" : "Route resumed" }); load(); }}>
                  {r.active ? "Pause" : "Resume"}
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <RouteDialog route={routeDialog} onOpenChange={(o) => !o && setRouteDialog(null)} onDone={() => { setRouteDialog(null); load(); }} />
      <AssignDialog route={assignFor} unassigned={data.unassigned} onOpenChange={(o) => !o && setAssignFor(null)} onDone={() => { setAssignFor(null); load(); }} />
      <TripDialog route={tripFor} onOpenChange={(o) => !o && setTripFor(null)} onDone={() => { setTripFor(null); load(); }} />
      <FareDialog ctx={fareFor} termLabel={data.termLabel} onOpenChange={(o) => !o && setFareFor(null)} onDone={(rc) => { setFareFor(null); if (rc) setReceipt(rc); load(); }} />
      {receipt && (
        <Dialog open onOpenChange={(o) => !o && setReceipt(null)}>
          <DialogContent className="max-w-[560px] max-h-[92vh] overflow-y-auto nice-scroll">
            <DialogHeader className="no-print"><DialogTitle className="text-base">Fare receipt issued</DialogTitle></DialogHeader>
            <div className="print-area"><ReceiptDocument data={receipt} /></div>
            <div className="no-print flex justify-end gap-2">
              <Button size="sm" variant="outline" onClick={() => window.print()}><Printer className="size-4" /> Print</Button>
              <Button size="sm" onClick={() => setReceipt(null)}>Done</Button>
            </div>
          </DialogContent>
        </Dialog>
      )}
    </div>
  );
}

function RouteDialog({ route, onOpenChange, onDone }: { route: Route | "new" | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [name, setName] = useState("");
  const [vehicleReg, setVehicleReg] = useState("");
  const [vehicleModel, setVehicleModel] = useState("");
  const [driverName, setDriverName] = useState("");
  const [driverPhone, setDriverPhone] = useState("");
  const [capacity, setCapacity] = useState("15");
  const [fare, setFare] = useState("");
  const [stops, setStops] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (route && route !== "new") {
      setName(route.name); setVehicleReg(route.vehicleReg); setVehicleModel(route.vehicleModel ?? "");
      setDriverName(route.driverName); setDriverPhone(route.driverPhone);
      setCapacity(String(route.capacity)); setFare((route.fare / 100).toFixed(0)); setStops(route.stops.join(", "));
    } else if (route === "new") {
      setName(""); setVehicleReg(""); setVehicleModel(""); setDriverName(""); setDriverPhone(""); setCapacity("15"); setFare(""); setStops("");
    }
  }, [route]);

  async function submit() {
    if (!name || !driverName || !fare) { toast({ title: "Route name, driver and fare are required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/transport`, {
        action: "save-route",
        id: route && route !== "new" ? route.id : undefined,
        name, vehicleReg, vehicleModel, driverName, driverPhone,
        capacity: Number(capacity), farePerTermCents: Math.round(parseFloat(fare) * 100),
        stops: stops.split(",").map((s) => s.trim()).filter(Boolean),
      });
      toast({ title: route === "new" ? "Route created" : "Route updated" });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!route} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{route === "new" ? "New bus route" : "Edit route"}</DialogTitle>
          <DialogDescription>A route carries its vehicle, driver, stops and the termly fare billed to riders.</DialogDescription>
        </DialogHeader>
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-1.5 sm:col-span-2"><Label>Route name *</Label><Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Adenta Loop" /></div>
          <div className="space-y-1.5"><Label>Vehicle reg</Label><Input value={vehicleReg} onChange={(e) => setVehicleReg(e.target.value)} placeholder="GR 4521-21" /></div>
          <div className="space-y-1.5"><Label>Vehicle model</Label><Input value={vehicleModel} onChange={(e) => setVehicleModel(e.target.value)} placeholder="Toyota Hiace" /></div>
          <div className="space-y-1.5"><Label>Driver *</Label><Input value={driverName} onChange={(e) => setDriverName(e.target.value)} placeholder="Kwesi Anane" /></div>
          <div className="space-y-1.5"><Label>Driver phone</Label><Input value={driverPhone} onChange={(e) => setDriverPhone(e.target.value)} placeholder="+233 24 551 7708" /></div>
          <div className="space-y-1.5"><Label>Capacity (seats)</Label><Input type="number" value={capacity} onChange={(e) => setCapacity(e.target.value)} /></div>
          <div className="space-y-1.5"><Label>Fare per term (GH₵) *</Label><Input type="number" value={fare} onChange={(e) => setFare(e.target.value)} placeholder="450" /></div>
          <div className="space-y-1.5 sm:col-span-2"><Label>Stops (comma-separated)</Label><Input value={stops} onChange={(e) => setStops(e.target.value)} placeholder="Adenta Market, Community 25, Frafraha Junction" /></div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : "Save route"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function AssignDialog({ route, unassigned, onOpenChange, onDone }: { route: Route | null; unassigned: TransportData["unassigned"]; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [studentId, setStudentId] = useState("");
  const [pickupStop, setPickupStop] = useState("");
  const [dropStop, setDropStop] = useState("");
  const [fareOverride, setFareOverride] = useState("");
  const [search, setSearch] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => { setStudentId(""); setPickupStop(""); setDropStop(""); setFareOverride(""); setSearch(""); }, [route]);

  const list = unassigned.filter((s) => !search || s.name.toLowerCase().includes(search) || s.classroom.toLowerCase().includes(search));

  async function submit() {
    if (!route || !studentId) { toast({ title: "Choose a learner" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/transport`, {
        action: "assign", routeId: route.id, studentId,
        pickupStop: pickupStop || null, dropStop: dropStop || pickupStop || null,
        fareOverrideCents: fareOverride ? Math.round(parseFloat(fareOverride) * 100) : null,
      });
      toast({ title: "Learner assigned", description: `${route.name}${fareOverride ? ` at GH₵${fareOverride}/term` : ""}.` });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!route} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle>Assign learner — {route?.name}</DialogTitle>
          <DialogDescription>Pick the learner, their stops and optionally a reduced fare (e.g. half-route).</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <Input placeholder="Search learner…" value={search} onChange={(e) => setSearch(e.target.value)} />
          <div className="border rounded-lg max-h-48 overflow-y-auto nice-scroll">
            {list.map((s) => (
              <button key={s.id} type="button"
                onClick={() => setStudentId(s.id)}
                className={`w-full flex items-center justify-between px-3 py-2 border-b last:border-0 text-sm text-left transition-colors ${studentId === s.id ? "bg-primary text-primary-foreground" : "hover:bg-secondary/50"}`}>
                <span>{s.name}</span><span className="text-xs opacity-70">{s.classroom}</span>
              </button>
            ))}
            {list.length === 0 && <div className="p-3 text-sm text-muted-foreground text-center">All learners are already on a route.</div>}
          </div>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Pickup stop</Label>
              <StopPicker value={pickupStop} onChange={setPickupStop} options={route?.stops ?? []} placeholder="Choose stop" />
            </div>
            <div className="space-y-1.5">
              <Label>Drop-off stop</Label>
              <StopPicker value={dropStop} onChange={setDropStop} options={route?.stops ?? []} placeholder="Same as pickup" />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Fare override (GH₵/term) — optional</Label>
            <Input type="number" value={fareOverride} onChange={(e) => setFareOverride(e.target.value)} placeholder={`Standard ${route ? fmtCedi(route.fare) : ""}`} />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || !studentId}>{busy ? "Assigning…" : "Assign to route"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function StopPicker({ value, onChange, options, placeholder }: { value: string; onChange: (v: string) => void; options: string[]; placeholder: string }) {
  return (
    <div className="flex gap-1.5 flex-wrap">
      {options.map((o) => (
        <button key={o} type="button" onClick={() => onChange(o)}
          className={`px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-colors ${value === o ? "bg-primary text-primary-foreground border-primary" : "hover:bg-secondary"}`}>
          {o}
        </button>
      ))}
      {options.length === 0 && <span className="text-xs text-muted-foreground">{placeholder}</span>}
    </div>
  );
}

function TripDialog({ route, onOpenChange, onDone }: { route: Route | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [date, setDate] = useState(new Date().toISOString().slice(0, 10));
  const [morning, setMorning] = useState("");
  const [evening, setEvening] = useState("");
  const [notes, setNotes] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => { if (route) { setDate(new Date().toISOString().slice(0, 10)); setMorning(String(route.riders.length)); setEvening(String(route.riders.length)); setNotes(""); } }, [route]);

  async function submit() {
    if (!route) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/transport`, {
        action: "record-trip", routeId: route.id, date,
        morningCount: morning === "" ? null : Number(morning), eveningCount: evening === "" ? null : Number(evening), notes: notes || null,
      });
      toast({ title: "Trip recorded", description: `${route.name} · ${fmtDate(date)}` });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!route} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Log trip — {route?.name}</DialogTitle>
          <DialogDescription>Boarding counts keep parents and the association confident in the service.</DialogDescription>
        </DialogHeader>
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-1.5"><Label>Date</Label><Input type="date" value={date} onChange={(e) => setDate(e.target.value)} /></div>
          <div className="space-y-1.5"><Label>Onboard (AM)</Label><Input type="number" value={morning} onChange={(e) => setMorning(e.target.value)} placeholder={String(route?.riders.length ?? 0)} /></div>
          <div className="space-y-1.5"><Label>Onboard (PM)</Label><Input type="number" value={evening} onChange={(e) => setEvening(e.target.value)} placeholder={String(route?.riders.length ?? 0)} /></div>
          <div className="space-y-1.5 sm:col-span-2"><Label>Notes</Label><Input value={notes} onChange={(e) => setNotes(e.target.value)} placeholder="e.g. Light traffic, 10 mins late" /></div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : "Record trip"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── fare collection dialog (counter payment for one rider's term fare) ──
function FareDialog({ ctx, termLabel, onOpenChange, onDone }: { ctx: { rider: Rider; route: Route } | null; termLabel: string; onOpenChange: (o: boolean) => void; onDone: (receipt: any) => void }) {
  const { currentSchoolId } = useStore();
  const [method, setMethod] = useState("CASH");
  const [reference, setReference] = useState("");
  const [busy, setBusy] = useState(false);

  const fare = ctx ? (ctx.rider.fareOverride ?? ctx.route.fare) : 0;
  const outstanding = ctx ? Math.max(0, fare - ctx.rider.farePaid) : 0;

  async function submit() {
    if (!ctx || !currentSchoolId) return;
    setBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/ops/transport`, {
        action: "collect-fare", assignmentId: ctx.rider.id, method, reference: reference || undefined,
      });
      toast({ title: "Fare collected", description: `${ctx.rider.student} · ${termLabel}` });
      onDone(res.receipt ?? null);
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!ctx} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><HandCoins className="size-4" /> Collect bus fare — {ctx?.rider.student}</DialogTitle>
          <DialogDescription>
            {ctx?.route.name} · {termLabel} · fare <b>{ctx ? fmtCedi(fare) : ""}</b>
            {ctx && ctx.rider.farePaid > 0 && <> · already paid <b className="text-emerald-600">{fmtCedi(ctx.rider.farePaid)}</b></>}
          </DialogDescription>
        </DialogHeader>
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="rounded-lg bg-primary/10 p-3 text-center">
            <div className="text-[10px] uppercase tracking-wider text-primary/80">Collecting now</div>
            <div className="text-2xl font-bold text-primary tabular-nums">{fmtCedi(outstanding)}</div>
          </div>
          <div className="space-y-1.5">
            <Label>Method</Label>
            <Select value={method} onValueChange={setMethod}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                {Object.entries(PAYMENT_LABELS).map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5 sm:col-span-2">
            <Label>MoMo / reference</Label>
            <Input placeholder="e.g. MP2401.5533.77" value={reference} onChange={(e) => setReference(e.target.value)} />
          </div>
        </div>
        <p className="text-xs text-muted-foreground">A receipt is issued instantly and an SMS goes to the guardian.</p>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || outstanding <= 0} className="gap-1.5">{busy ? "Collecting…" : <><HandCoins className="size-4" /> Collect fare</>}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

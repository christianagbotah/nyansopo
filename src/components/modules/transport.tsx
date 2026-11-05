"use client";
import { useCallback, useEffect, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { DateField } from "@/components/date-field";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { StatCard, ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, PAYMENT_LABELS } from "@/lib/format";
import { cn } from "@/lib/utils";
import { toast } from "@/hooks/use-toast";
import { Bus, Plus, Phone, MapPin, UserPlus, ClipboardCheck, Banknote, Users, Pencil, Trash2, ArrowRight, HandCoins, CheckCircle2, Printer, Car, FileSpreadsheet, Download, IdCard, ShieldCheck, AlertTriangle } from "lucide-react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { ReceiptDocument } from "@/components/receipts";

type Rider = { id: string; studentId: string; student: string; classroom: string | null; pickupStop: string | null; dropStop: string | null; fareOverride: number | null; onewayFare?: number; stopFare?: number | null; guardianPhone: string; farePaid: number };
type Trip = { id: string; date: string; morning: number | null; evening: number | null; notes: string | null; recordedByName: string | null };
type StopRec = { name: string; fareCents: number | null };
type Compliance = "OK" | "DUE_SOON" | "EXPIRED" | "UNKNOWN";
type DriverRec = { id: string; name: string; phone: string | null; licenseNo: string | null; licenseExpiry: string | null; status: string; routes: string[]; licenseCompliance: Compliance };
type VehicleRec = { id: string; regNumber: string; model: string | null; capacity: number; insuranceExpiry: string | null; roadworthyExpiry: string | null; status: string; notes: string | null; routes: string[]; insuranceCompliance: Compliance; roadworthyCompliance: Compliance };
type Route = {
  id: string; name: string; vehicleReg: string; vehicleModel: string | null; driverName: string; driverPhone: string;
  driverId?: string | null; vehicleId?: string | null;
  linkedDriver?: { id: string; name: string; phone: string | null; licenseExpiry: string | null; licenseCompliance: Compliance } | null;
  linkedVehicle?: { id: string; regNumber: string; model: string | null; insuranceExpiry: string | null; roadworthyExpiry: string | null; insuranceCompliance: Compliance; roadworthyCompliance: Compliance } | null;
  capacity: number; fare: number; farePerTrip?: number; stops: string[]; stopRecords?: StopRec[]; active: boolean; riders: Rider[]; utilisation: number; fareRevenue: number; fareCollected: number; trips: Trip[];
};
type TransportData = {
  termLabel: string;
  routes: Route[];
  drivers: DriverRec[];
  vehicles: VehicleRec[];
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
  const [tab, setTab] = useState("routes");

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

      <Tabs value={tab} onValueChange={setTab} className="mt-5">
        <TabsList className="flex-wrap">
          <TabsTrigger value="routes">Routes</TabsTrigger>
          <TabsTrigger value="fleet" className="gap-1.5"><Car className="size-4" /> Fleet &amp; compliance</TabsTrigger>
          <TabsTrigger value="fares" className="gap-1.5"><FileSpreadsheet className="size-4" /> Fare report</TabsTrigger>
        </TabsList>

        <TabsContent value="routes">
      {data.routes.length === 0 && <Card className="mt-0"><CardContent><EmptyState icon={<Bus className="size-5" />} title="No routes yet" hint="Create your first bus route to start assigning learners." /></CardContent></Card>}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-1">
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
                {(r.stopRecords ?? r.stops.map((name) => ({ name, fareCents: null as number | null }))).map((s) => (
                  <Badge key={s.name} variant="outline" className="text-[10px] gap-1 font-normal" title={s.fareCents != null ? `${s.name} — one-way fare ${fmtCedi(s.fareCents)}` : s.fareCents === null && r.farePerTrip ? `${s.name} — route fare ${fmtCedi(r.farePerTrip)}` : s.name}>
                    <MapPin className="size-2.5" />{s.name}{s.fareCents != null && <b className="text-primary"> {fmtCedi(s.fareCents, { compact: true })}</b>}
                  </Badge>
                ))}
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
                          {x.onewayFare != null && x.onewayFare > 0 && <span className="text-primary font-medium">· {fmtCedi(x.onewayFare, { compact: true })}/trip{x.stopFare != null ? " (stop fare)" : ""}</span>}
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
        </TabsContent>

        <TabsContent value="fleet">
          <FleetTab data={data} onRefresh={load} />
        </TabsContent>
        <TabsContent value="fares">
          <FareReportTab routes={data.routes} />
        </TabsContent>
      </Tabs>

      <RouteDialog route={routeDialog} drivers={data.drivers} vehicles={data.vehicles} onOpenChange={(o) => !o && setRouteDialog(null)} onDone={() => { setRouteDialog(null); load(); }} />
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

function RouteDialog({ route, drivers, vehicles, onOpenChange, onDone }: { route: Route | "new" | null; drivers: DriverRec[]; vehicles: VehicleRec[]; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [name, setName] = useState("");
  const [vehicleReg, setVehicleReg] = useState("");
  const [vehicleModel, setVehicleModel] = useState("");
  const [driverName, setDriverName] = useState("");
  const [driverPhone, setDriverPhone] = useState("");
  const [driverId, setDriverId] = useState("none");
  const [vehicleId, setVehicleId] = useState("none");
  const [capacity, setCapacity] = useState("15");
  const [fare, setFare] = useState("");
  const [farePerTrip, setFarePerTrip] = useState("");
  const [stops, setStops] = useState<{ name: string; fare: string }[]>([{ name: "", fare: "" }]);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (route && route !== "new") {
      setName(route.name); setVehicleReg(route.vehicleReg); setVehicleModel(route.vehicleModel ?? "");
      setDriverName(route.driverName); setDriverPhone(route.driverPhone);
      setDriverId(route.driverId ?? "none"); setVehicleId(route.vehicleId ?? "none");
      setCapacity(String(route.capacity)); setFare((route.fare / 100).toFixed(0)); setFarePerTrip(route.farePerTrip ? (route.farePerTrip / 100).toFixed(2) : "");
      const recs = route.stopRecords ?? route.stops.map((s) => ({ name: s, fareCents: null as number | null }));
      setStops(recs.length ? recs.map((s) => ({ name: s.name, fare: s.fareCents != null ? (s.fareCents / 100).toFixed(2) : "" })) : [{ name: "", fare: "" }]);
    } else if (route === "new") {
      setName(""); setVehicleReg(""); setVehicleModel(""); setDriverName(""); setDriverPhone(""); setDriverId("none"); setVehicleId("none"); setCapacity("15"); setFare(""); setFarePerTrip(""); setStops([{ name: "", fare: "" }]);
    }
  }, [route]);

  const setStop = (i: number, key: "name" | "fare", v: string) => setStops(stops.map((s, j) => (i === j ? { ...s, [key]: v } : s)));

  async function submit() {
    if (!name || !driverName || !fare) { toast({ title: "Route name, driver and fare are required" }); return; }
    setBusy(true);
    try {
      const stopRecords = stops.filter((s) => s.name.trim()).map((s) => ({ name: s.name.trim(), fareCents: s.fare === "" ? null : Math.round(parseFloat(s.fare) * 100) }));
      await apiPost(`/api/school/${currentSchoolId}/ops/transport`, {
        action: "save-route",
        id: route && route !== "new" ? route.id : undefined,
        name, vehicleReg, vehicleModel, driverName, driverPhone,
        driverId: driverId === "none" ? null : driverId, vehicleId: vehicleId === "none" ? null : vehicleId,
        capacity: Number(capacity), farePerTermCents: Math.round(parseFloat(fare) * 100),
        farePerTripCents: farePerTrip === "" ? 0 : Math.round(parseFloat(farePerTrip) * 100),
        stopRecords, stops: stopRecords.map((s) => s.name),
      });
      toast({ title: route === "new" ? "Route created" : "Route updated" });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!route} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle>{route === "new" ? "New bus route" : "Edit route"}</DialogTitle>
          <DialogDescription>
            A route carries its vehicle, driver and stops. Transport fare is <b>route-based, never class-based</b> — each pickup stop may carry its own one-way fare, so learners on the same route pay according to where they board.
          </DialogDescription>
        </DialogHeader>
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-1.5 sm:col-span-2"><Label>Route name *</Label><Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Adenta Loop" /></div>
          <div className="space-y-1.5"><Label>Vehicle reg</Label><Input value={vehicleReg} onChange={(e) => setVehicleReg(e.target.value)} placeholder="GR 4521-21" /></div>
          <div className="space-y-1.5"><Label>Vehicle model</Label><Input value={vehicleModel} onChange={(e) => setVehicleModel(e.target.value)} placeholder="Toyota Hiace" /></div>
          <div className="space-y-1.5"><Label>Driver *</Label><Input value={driverName} onChange={(e) => setDriverName(e.target.value)} placeholder="Kwesi Anane" /></div>
          <div className="space-y-1.5"><Label>Driver phone</Label><Input value={driverPhone} onChange={(e) => setDriverPhone(e.target.value)} placeholder="+233 24 551 7708" /></div>
          <div className="space-y-1.5">
            <Label>Link driver record</Label>
            <Select value={driverId} onValueChange={setDriverId}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">Not linked</SelectItem>
                {drivers.map((d) => <SelectItem key={d.id} value={d.id}>{d.name}{d.licenseNo ? ` · ${d.licenseNo}` : ""}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Link vehicle record</Label>
            <Select value={vehicleId} onValueChange={setVehicleId}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">Not linked</SelectItem>
                {vehicles.map((v) => <SelectItem key={v.id} value={v.id}>{v.regNumber}{v.model ? ` · ${v.model}` : ""}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5"><Label>Capacity (seats)</Label><Input type="number" value={capacity} onChange={(e) => setCapacity(e.target.value)} /></div>
          <div className="space-y-1.5"><Label>Fare per term (GH₵) *</Label><Input type="number" value={fare} onChange={(e) => setFare(e.target.value)} placeholder="450" /></div>
          <div className="space-y-1.5 sm:col-span-2">
            <Label>Default one-way fare (GH₵) — used when a stop has no fare of its own</Label>
            <Input type="number" step="0.5" value={farePerTrip} onChange={(e) => setFarePerTrip(e.target.value)} placeholder="e.g. 8.00 — both ways 16.00" />
          </div>
          <div className="space-y-1.5 sm:col-span-2">
            <div className="flex items-center justify-between">
              <Label>Pickup stops & fares</Label>
              <Button type="button" size="sm" variant="outline" className="h-7 gap-1 text-xs" onClick={() => setStops([...stops, { name: "", fare: "" }])}><Plus className="size-3" /> Add stop</Button>
            </div>
            <div className="grid grid-cols-[1fr_110px_32px] gap-2 items-center">
              <span className="text-[11px] text-muted-foreground -mb-1">Stop name</span>
              <span className="text-[11px] text-muted-foreground -mb-1">One-way fare (GH₵)</span>
              <span />
              {stops.map((s, i) => (
                <div key={i} className="contents">
                  <Input value={s.name} onChange={(e) => setStop(i, "name", e.target.value)} placeholder={i === 0 ? "Adenta Market" : "Stop name"} />
                  <Input type="number" step="0.5" value={s.fare} onChange={(e) => setStop(i, "fare", e.target.value)} placeholder="route default" />
                  <Button type="button" size="sm" variant="ghost" className="size-8 p-0 text-muted-foreground hover:text-destructive" disabled={stops.length === 1}
                    onClick={() => setStops(stops.filter((_, j) => j !== i))}><Trash2 className="size-3.5" /></Button>
                </div>
              ))}
            </div>
            <p className="text-[11px] text-muted-foreground">Leave a fare blank to use the route default. Morning or afternoon rides charge 1× the one-way fare; both ways 2×.</p>
          </div>
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
          {(() => {
            // live fare preview: the pickup point sets the one-way fare (stop fare → route default)
            const rec = route?.stopRecords?.find((s) => s.name === (pickupStop || dropStop));
            const stopFare = rec?.fareCents ?? null;
            const oneway = stopFare ?? route?.farePerTrip ?? 0;
            if (!oneway) return null;
            return (
              <div className="rounded-lg border border-primary/30 bg-primary/5 px-3 py-2 text-xs flex items-center gap-2">
                <MapPin className="size-3.5 text-primary shrink-0" />
                <span>
                  One-way fare <b className="text-primary">{fmtCedi(oneway)}</b>{stopFare != null ? ` from ${pickupStop || dropStop} (stop fare)` : " (route default)"}
                  {" "}· both ways <b>{fmtCedi(oneway * 2)}</b>
                </span>
              </div>
            );
          })()}
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
          <div className="space-y-1.5"><Label>Date</Label><DateField value={date} onChange={(v) => setDate(v ?? "")} /></div>
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

/* ── Fleet & compliance (CI3 drivers / vehicles / insurance parity) ──
    Driver and vehicle registries with DVLA license, insurance and
    roadworthy expiry tracking (expired / due-in-30-days / ok). */
const COMPLIANCE_CHIP: Record<Compliance, { cls: string; label: string }> = {
  OK: { cls: "bg-emerald-100 text-emerald-700", label: "valid" },
  DUE_SOON: { cls: "bg-amber-100 text-amber-700", label: "due <30d" },
  EXPIRED: { cls: "bg-red-100 text-red-700", label: "EXPIRED" },
  UNKNOWN: { cls: "bg-zinc-100 text-zinc-500", label: "not recorded" },
};
function ComplianceChip({ c, expiry }: { c: Compliance; expiry: string | null }) {
  const chip = COMPLIANCE_CHIP[c];
  return (
    <span className={cn("inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5 rounded-full", chip.cls)} title={expiry ? `expires ${fmtDate(expiry)}` : "no expiry recorded"}>
      {c === "EXPIRED" && <AlertTriangle className="size-2.5" />}
      {chip.label}{expiry ? ` · ${fmtDate(expiry)}` : ""}
    </span>
  );
}

function FleetTab({ data, onRefresh }: { data: TransportData; onRefresh: () => void }) {
  const { currentSchoolId } = useStore();
  const [driverDlg, setDriverDlg] = useState<DriverRec | "new" | null>(null);
  const [vehicleDlg, setVehicleDlg] = useState<VehicleRec | "new" | null>(null);

  async function act(body: any, ok: string) {
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/transport`, body);
      toast({ title: ok });
      onRefresh();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
  }

  const expiredCount = (arr: Compliance[]) => arr.filter((c) => c === "EXPIRED").length;
  const dueSoonCount = (arr: Compliance[]) => arr.filter((c) => c === "DUE_SOON").length;
  const driverCompliance = data.drivers.map((d) => d.licenseCompliance);
  const vehicleCompliance = data.vehicles.flatMap((v) => [v.insuranceCompliance, v.roadworthyCompliance]);

  return (
    <div className="space-y-4 mt-1">
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <StatCard icon={<IdCard className="size-4" />} label="Drivers" value={data.drivers.length} sub={`${data.drivers.filter((d) => d.status === "ACTIVE").length} active`} />
        <StatCard icon={<Car className="size-4" />} label="Vehicles" value={data.vehicles.length} sub={`${data.vehicles.filter((v) => v.status === "ACTIVE").length} on the road`} tone="primary" />
        <StatCard icon={<AlertTriangle className="size-4" />} label="Expired documents" value={expiredCount([...driverCompliance, ...vehicleCompliance])} tone={expiredCount([...driverCompliance, ...vehicleCompliance]) ? "danger" : "default"} sub="license / insurance / roadworthy" />
        <StatCard icon={<ShieldCheck className="size-4" />} label="Due within 30 days" value={dueSoonCount([...driverCompliance, ...vehicleCompliance])} tone="gold" sub="renew before they lapse" />
      </div>

      <Card>
        <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
          <CardTitle className="text-base flex items-center gap-2"><IdCard className="size-4 text-primary" /> Drivers</CardTitle>
          <Button size="sm" className="h-8 gap-1.5" onClick={() => setDriverDlg("new")}><Plus className="size-3.5" /> Add driver</Button>
        </CardHeader>
        <CardContent>
          {data.drivers.length ? (
            <Table>
              <TableHeader><TableRow>
                <TableHead>Driver</TableHead><TableHead>Phone</TableHead><TableHead>License</TableHead>
                <TableHead className="hidden md:table-cell">Routes</TableHead><TableHead>Status</TableHead><TableHead className="text-right">Actions</TableHead>
              </TableRow></TableHeader>
              <TableBody>
                {data.drivers.map((d) => (
                  <TableRow key={d.id} className={d.status !== "ACTIVE" ? "opacity-60" : ""}>
                    <TableCell className="font-medium">{d.name}</TableCell>
                    <TableCell>{d.phone ? <a className="text-primary hover:underline" href={`tel:${d.phone}`}>{d.phone}</a> : "—"}</TableCell>
                    <TableCell>
                      <div className="text-xs">{d.licenseNo ?? "—"}</div>
                      <ComplianceChip c={d.licenseCompliance} expiry={d.licenseExpiry} />
                    </TableCell>
                    <TableCell className="hidden md:table-cell text-xs text-muted-foreground">{d.routes.length ? d.routes.join(", ") : "—"}</TableCell>
                    <TableCell><Badge variant={d.status === "ACTIVE" ? "secondary" : "outline"} className="text-[10px]">{d.status.toLowerCase()}</Badge></TableCell>
                    <TableCell className="text-right">
                      <Button size="sm" variant="ghost" className="h-7" onClick={() => setDriverDlg(d)}><Pencil className="size-3.5" /></Button>
                      <Button size="sm" variant="ghost" className="h-7 text-destructive"
                        onClick={() => act({ action: "driver-delete", id: d.id }, "Driver removed")}><Trash2 className="size-3.5" /></Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          ) : <EmptyState title="No drivers registered" hint="Keep DVLA license numbers and expiries here — routes can link to a driver record." />}
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
          <CardTitle className="text-base flex items-center gap-2"><Car className="size-4 text-primary" /> Vehicles</CardTitle>
          <Button size="sm" className="h-8 gap-1.5" onClick={() => setVehicleDlg("new")}><Plus className="size-3.5" /> Add vehicle</Button>
        </CardHeader>
        <CardContent>
          {data.vehicles.length ? (
            <Table>
              <TableHeader><TableRow>
                <TableHead>Registration</TableHead><TableHead>Model</TableHead><TableHead>Insurance</TableHead>
                <TableHead>Roadworthy</TableHead><TableHead className="hidden md:table-cell">Routes</TableHead><TableHead>Status</TableHead><TableHead className="text-right">Actions</TableHead>
              </TableRow></TableHeader>
              <TableBody>
                {data.vehicles.map((v) => (
                  <TableRow key={v.id} className={v.status !== "ACTIVE" ? "opacity-60" : ""}>
                    <TableCell className="font-mono font-semibold text-xs">{v.regNumber}</TableCell>
                    <TableCell className="text-sm">{v.model ?? "—"} <span className="text-muted-foreground">· {v.capacity} seats</span></TableCell>
                    <TableCell><ComplianceChip c={v.insuranceCompliance} expiry={v.insuranceExpiry} /></TableCell>
                    <TableCell><ComplianceChip c={v.roadworthyCompliance} expiry={v.roadworthyExpiry} /></TableCell>
                    <TableCell className="hidden md:table-cell text-xs text-muted-foreground">{v.routes.length ? v.routes.join(", ") : "—"}</TableCell>
                    <TableCell><Badge variant={v.status === "ACTIVE" ? "secondary" : "outline"} className="text-[10px]">{v.status.toLowerCase()}</Badge></TableCell>
                    <TableCell className="text-right">
                      <Button size="sm" variant="ghost" className="h-7" onClick={() => setVehicleDlg(v)}><Pencil className="size-3.5" /></Button>
                      <Button size="sm" variant="ghost" className="h-7 text-destructive"
                        onClick={() => act({ action: "vehicle-delete", id: v.id }, "Vehicle removed")}><Trash2 className="size-3.5" /></Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          ) : <EmptyState title="No vehicles registered" hint="Track insurance and roadworthy expiries per bus — routes can link to a vehicle record." />}
        </CardContent>
      </Card>

      <DriverDialog driver={driverDlg} onOpenChange={(o) => !o && setDriverDlg(null)} onDone={() => { setDriverDlg(null); onRefresh(); }} />
      <VehicleDialog vehicle={vehicleDlg} onOpenChange={(o) => !o && setVehicleDlg(null)} onDone={() => { setVehicleDlg(null); onRefresh(); }} />
    </div>
  );
}

function DriverDialog({ driver, onOpenChange, onDone }: { driver: DriverRec | "new" | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [licenseNo, setLicenseNo] = useState("");
  const [licenseExpiry, setLicenseExpiry] = useState("");
  const [status, setStatus] = useState("ACTIVE");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (driver && driver !== "new") {
      setName(driver.name); setPhone(driver.phone ?? ""); setLicenseNo(driver.licenseNo ?? "");
      setLicenseExpiry(driver.licenseExpiry ? driver.licenseExpiry.slice(0, 10) : ""); setStatus(driver.status);
    } else if (driver === "new") { setName(""); setPhone(""); setLicenseNo(""); setLicenseExpiry(""); setStatus("ACTIVE"); }
  }, [driver]);

  async function submit() {
    if (!name.trim()) { toast({ title: "Driver name is required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/transport`, {
        action: "driver-save", id: driver && driver !== "new" ? driver.id : undefined,
        name, phone, licenseNo, licenseExpiry: licenseExpiry || null, status,
      });
      toast({ title: driver === "new" ? "Driver added" : "Driver updated" });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!driver} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader><DialogTitle>{driver === "new" ? "Add driver" : "Edit driver"}</DialogTitle>
          <DialogDescription>DVLA license tracking keeps you ahead of renewals.</DialogDescription></DialogHeader>
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-1.5 sm:col-span-2"><Label>Full name *</Label><Input value={name} onChange={(e) => setName(e.target.value)} placeholder="Kwesi Anane" /></div>
          <div className="space-y-1.5"><Label>Phone</Label><Input value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="+233 24 551 7708" /></div>
          <div className="space-y-1.5"><Label>License number</Label><Input value={licenseNo} onChange={(e) => setLicenseNo(e.target.value)} placeholder="DVLA-000000" /></div>
          <div className="space-y-1.5"><Label>License expiry</Label><DateField value={licenseExpiry} onChange={(v) => setLicenseExpiry(v ?? "")} /></div>
          <div className="space-y-1.5"><Label>Status</Label>
            <Select value={status} onValueChange={setStatus}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>{["ACTIVE", "SUSPENDED", "RETIRED"].map((x) => <SelectItem key={x} value={x}>{x.toLowerCase()}</SelectItem>)}</SelectContent>
            </Select>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : "Save driver"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function VehicleDialog({ vehicle, onOpenChange, onDone }: { vehicle: VehicleRec | "new" | null; onOpenChange: (o: boolean) => void; onDone: () => void }) {
  const { currentSchoolId } = useStore();
  const [regNumber, setRegNumber] = useState("");
  const [model, setModel] = useState("");
  const [capacity, setCapacity] = useState("15");
  const [insuranceExpiry, setInsuranceExpiry] = useState("");
  const [roadworthyExpiry, setRoadworthyExpiry] = useState("");
  const [status, setStatus] = useState("ACTIVE");
  const [notes, setNotes] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (vehicle && vehicle !== "new") {
      setRegNumber(vehicle.regNumber); setModel(vehicle.model ?? ""); setCapacity(String(vehicle.capacity));
      setInsuranceExpiry(vehicle.insuranceExpiry ? vehicle.insuranceExpiry.slice(0, 10) : "");
      setRoadworthyExpiry(vehicle.roadworthyExpiry ? vehicle.roadworthyExpiry.slice(0, 10) : "");
      setStatus(vehicle.status); setNotes(vehicle.notes ?? "");
    } else if (vehicle === "new") { setRegNumber(""); setModel(""); setCapacity("15"); setInsuranceExpiry(""); setRoadworthyExpiry(""); setStatus("ACTIVE"); setNotes(""); }
  }, [vehicle]);

  async function submit() {
    if (!regNumber.trim()) { toast({ title: "Registration number is required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/transport`, {
        action: "vehicle-save", id: vehicle && vehicle !== "new" ? vehicle.id : undefined,
        regNumber, model, capacity: Number(capacity), insuranceExpiry: insuranceExpiry || null,
        roadworthyExpiry: roadworthyExpiry || null, status, notes,
      });
      toast({ title: vehicle === "new" ? "Vehicle added" : "Vehicle updated" });
      onDone();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!vehicle} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-md">
        <DialogHeader><DialogTitle>{vehicle === "new" ? "Add vehicle" : "Edit vehicle"}</DialogTitle>
          <DialogDescription>Insurance and roadworthy expiries flag themselves before they lapse.</DialogDescription></DialogHeader>
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="space-y-1.5"><Label>Registration *</Label><Input value={regNumber} onChange={(e) => setRegNumber(e.target.value)} placeholder="GR 4521-21" /></div>
          <div className="space-y-1.5"><Label>Model</Label><Input value={model} onChange={(e) => setModel(e.target.value)} placeholder="Toyota Hiace" /></div>
          <div className="space-y-1.5"><Label>Capacity (seats)</Label><Input type="number" value={capacity} onChange={(e) => setCapacity(e.target.value)} /></div>
          <div className="space-y-1.5"><Label>Status</Label>
            <Select value={status} onValueChange={setStatus}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>{["ACTIVE", "MAINTENANCE", "RETIRED"].map((x) => <SelectItem key={x} value={x}>{x.toLowerCase()}</SelectItem>)}</SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5"><Label>Insurance expiry</Label><DateField value={insuranceExpiry} onChange={(v) => setInsuranceExpiry(v ?? "")} /></div>
          <div className="space-y-1.5"><Label>Roadworthy expiry</Label><DateField value={roadworthyExpiry} onChange={(v) => setRoadworthyExpiry(v ?? "")} /></div>
          <div className="space-y-1.5 sm:col-span-2"><Label>Notes</Label><Input value={notes} onChange={(e) => setNotes(e.target.value)} placeholder="Optional — e.g. serviced Sept 2026" /></div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{busy ? "Saving…" : "Save vehicle"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

/* ── Fare report (CI3 transport_fare_report parity) ──
    Boarding-based collections over a date range with route filter, stats
    and CSV export. */
type FareReport = {
  range: { from: string; to: string };
  rows: { date: string; student: string; classroom: string | null; route: string; direction: string; fareCents: number; paidCents: number; recordedByName: string | null }[];
  stats: { count: number; totalFareCents: number; totalPaidCents: number; outstandingCents: number; bothWays: number; oneWay: number };
};

function FareReportTab({ routes }: { routes: Route[] }) {
  const { currentSchoolId } = useStore();
  const [from, setFrom] = useState(() => new Date(Date.now() - 30 * 864e5).toISOString().slice(0, 10));
  const [to, setTo] = useState(() => new Date().toISOString().slice(0, 10));
  const [routeId, setRouteId] = useState("all");
  const [data, setData] = useState<FareReport | null>(null);

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<FareReport>(`/api/school/${currentSchoolId}/ops/transport?tab=fares&from=${from}&to=${to}${routeId !== "all" ? `&routeId=${routeId}` : ""}`)
      .then(setData)
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  }, [currentSchoolId, from, to, routeId]);
  useEffect(() => { load(); }, [load]);

  const DIR_LABEL: Record<string, string> = { IN: "Morning", OUT: "Afternoon", BOTH: "Both ways", NONE: "Not riding" };

  return (
    <div className="space-y-4 mt-1">
      <Card>
        <CardContent className="pt-4 flex flex-wrap gap-2 items-end">
          <div className="space-y-1"><Label className="text-xs">From</Label><DateField value={from} onChange={(v) => setFrom(v ?? "")} className="h-9 w-40" /></div>
          <div className="space-y-1"><Label className="text-xs">To</Label><DateField value={to} onChange={(v) => setTo(v ?? "")} className="h-9 w-40" /></div>
          <div className="space-y-1">
            <Label className="text-xs">Route</Label>
            <Select value={routeId} onValueChange={setRouteId}>
              <SelectTrigger className="h-9 w-52"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All routes</SelectItem>
                {routes.map((r) => <SelectItem key={r.id} value={r.id}>{r.name}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <Button size="sm" className="h-9" onClick={load}>Apply</Button>
          <Button size="sm" variant="outline" className="h-9 gap-1.5" disabled={!data?.rows.length}
            onClick={() => {
              const esc = (v: string | number) => `"${String(v).replace(/"/g, '""')}"`;
              const csv = [["Date", "Student", "Class", "Route", "Direction", "Fare (GHS)", "Paid (GHS)", "Recorded by"], ...data!.rows.map((r) => [
                r.date.slice(0, 10), r.student, r.classroom ?? "", r.route, DIR_LABEL[r.direction] ?? r.direction,
                (r.fareCents / 100).toFixed(2), (r.paidCents / 100).toFixed(2), r.recordedByName ?? "",
              ])].map((r) => r.map(esc).join(",")).join("\n");
              const url = URL.createObjectURL(new Blob(["\uFEFF" + csv], { type: "text/csv;charset=utf-8" }));
              const a = document.createElement("a"); a.href = url; a.download = `transport_fare_report_${from}_${to}.csv`; a.click();
              URL.revokeObjectURL(url);
            }}><Download className="size-3.5" /> Export Excel (CSV)</Button>
        </CardContent>
      </Card>

      {data ? (
        <>
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <StatCard icon={<ClipboardCheck className="size-4" />} label="Boardings" value={data.stats.count} sub={`${data.stats.bothWays} both ways · ${data.stats.oneWay} one way`} />
            <StatCard icon={<Banknote className="size-4" />} label="Fares charged" value={fmtCedi(data.stats.totalFareCents, { compact: true })} sub="over the range" />
            <StatCard icon={<HandCoins className="size-4" />} label="Collected" value={fmtCedi(data.stats.totalPaidCents, { compact: true })} tone="primary" sub="cash + wallet" />
            <StatCard icon={<AlertTriangle className="size-4" />} label="Owing" value={fmtCedi(data.stats.outstandingCents, { compact: true })} tone={data.stats.outstandingCents > 0 ? "danger" : "default"} sub="charged but unpaid" />
          </div>
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base">Collections</CardTitle></CardHeader>
            <CardContent>
              {data.rows.length ? (
                <div className="max-h-[480px] overflow-y-auto nice-scroll">
                  <Table>
                    <TableHeader><TableRow>
                      <TableHead>Date</TableHead><TableHead>Learner</TableHead><TableHead className="hidden md:table-cell">Route</TableHead>
                      <TableHead>Direction</TableHead><TableHead className="text-right">Fare</TableHead><TableHead className="text-right">Paid</TableHead>
                    </TableRow></TableHeader>
                    <TableBody>
                      {data.rows.map((r, i) => (
                        <TableRow key={i}>
                          <TableCell className="text-xs">{fmtDate(r.date)}</TableCell>
                          <TableCell className="font-medium text-sm">{r.student}<span className="text-xs text-muted-foreground"> · {r.classroom ?? "—"}</span></TableCell>
                          <TableCell className="hidden md:table-cell text-xs">{r.route}</TableCell>
                          <TableCell><Badge variant="outline" className="text-[10px]">{DIR_LABEL[r.direction] ?? r.direction}</Badge></TableCell>
                          <TableCell className="text-right tabular-nums text-sm">{fmtCedi(r.fareCents)}</TableCell>
                          <TableCell className={cn("text-right tabular-nums text-sm font-medium", r.paidCents >= r.fareCents ? "text-emerald-600" : "text-red-600")}>{fmtCedi(r.paidCents)}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              ) : <EmptyState title="No boardings in this range" hint="Conductor boardings and counter collections appear here." />}
            </CardContent>
          </Card>
        </>
      ) : <LoadingBlock label="Loading fare report…" />}
    </div>
  );
}

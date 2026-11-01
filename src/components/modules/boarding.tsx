"use client";
/* Boarding management (iEduPro boarding_system parity): houses → dormitories →
   beds → boarder assignment → occupancy reports. */
import { Fragment, useEffect, useState } from "react";
import { useStore, type NavTarget } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { Checkbox } from "@/components/ui/checkbox";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { StatCard, ModuleHeader, LoadingBlock, EmptyState, StatusBadge } from "@/components/ui-helpers";
import { fmtCedi, fmtDate } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { Home, Building2, BedDouble, BedSingle, Layers, Users, Plus, Pencil, Trash2, Printer, UserPlus, LogOut } from "lucide-react";

// ── payload types (mirrors GET /api/school/[id]/ops/boarding) ──
type DormRow = {
  id: string; houseId: string; houseName: string; name: string; capacity: number; type: string | null;
  floor: string | null; description: string | null; prefectId: string | null; prefectName: string | null;
  status: string; bedCount: number; occupied: number; availableBeds: number;
};
type HouseRow = {
  id: string; name: string; description: string | null; houseMasterId: string | null; houseMasterName: string | null;
  prefectId: string | null; prefectName: string | null; capacity: number; yearEstablished: string | null;
  gpsCode: string | null; termlyFeeCents: number; status: string; dormCount: number; bedCount: number; occupied: number;
  dormitories: DormRow[];
};
type BedRow = {
  id: string; code: string; dormitoryId: string; dormName: string; houseId: string; houseName: string;
  bedType: string; status: string; studentId: string | null; studentName: string | null; assignedAt: string | null;
};
type Learner = { id: string; name: string; classroom: string | null; gender: string; residenceType: string; bedCode: string | null };
type BoardingData = {
  houses: HouseRow[];
  dorms: DormRow[];
  beds: BedRow[];
  stats: { houses: number; dorms: number; beds: number; occupied: number; available: number; maintenance: number; occupancyRate: number; boarders: number; unassigned: number };
  unassignedBoarders: { id: string; name: string; classroom: string | null; gender: string }[];
  occupants: { bedId: string; code: string; dormName: string; houseName: string; studentId: string; studentName: string | null; classroom: string | null; assignedAt: string | null }[];
  activeLearners: Learner[];
  teachingStaff: { id: string; fullName: string; role: string }[];
};

const TABS = ["houses", "dorms", "beds", "assign", "reports"];

function OccupancyBadge({ rate }: { rate: number }) {
  const pct = Math.round(rate);
  if (pct > 80) return <Badge variant="destructive">{pct}% · near full</Badge>;
  if (pct > 50) return <Badge className="bg-amber-500 hover:bg-amber-500 text-white">{pct}%</Badge>;
  return <Badge className="bg-emerald-600 hover:bg-emerald-600 text-white">{pct}%</Badge>;
}

/** AVAILABLE / ASSIGNED / MAINTENANCE pills with distinct tones (fallback: StatusBadge) */
function BoardingStatus({ status }: { status: string }) {
  if (status === "AVAILABLE") return <Badge className="bg-emerald-600 hover:bg-emerald-600 text-white">Available</Badge>;
  if (status === "ASSIGNED") return <Badge variant="secondary">Assigned</Badge>;
  if (status === "MAINTENANCE") return <Badge variant="outline" className="text-amber-700 border-amber-300 dark:text-amber-300 dark:border-amber-800">Maintenance</Badge>;
  return <StatusBadge status={status} />;
}

function GenderBadge({ gender }: { gender: string }) {
  return gender === "F" ? (
    <Badge variant="outline" className="text-rose-700 border-rose-200 dark:text-rose-300 dark:border-rose-900">F</Badge>
  ) : (
    <Badge variant="outline" className="text-emerald-700 border-emerald-200 dark:text-emerald-300 dark:border-emerald-900">M</Badge>
  );
}

export function Boarding() {
  const { currentSchoolId, navTarget, schools } = useStore();
  const schoolName = schools.find((s) => s.id === currentSchoolId)?.name ?? "School";
  const [data, setData] = useState<BoardingData | null>(null);
  const [tab, setTab] = useState("houses");
  const [appliedTarget, setAppliedTarget] = useState<NavTarget>(null);

  const [houseDlg, setHouseDlg] = useState<HouseRow | "new" | null>(null);
  const [dormDlg, setDormDlg] = useState<DormRow | "new" | null>(null);
  const [bedDlg, setBedDlg] = useState<BedRow | "new" | null>(null);
  const [bulkOpen, setBulkOpen] = useState(false);
  const [convertOpen, setConvertOpen] = useState(false);
  const [busyId, setBusyId] = useState<string | null>(null);

  // honour sidebar deep-links (navTarget.tab preselects the tab)
  useEffect(() => {
    if (navTarget !== appliedTarget) {
      setAppliedTarget(navTarget);
      if (navTarget?.tab && TABS.includes(navTarget.tab)) setTab(navTarget.tab);
    }
  }, [navTarget, appliedTarget]);

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<BoardingData>(`/api/school/${currentSchoolId}/ops/boarding`)
      .then(setData)
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(() => {
    load();
  }, [currentSchoolId]);

  async function act(body: Record<string, unknown>, okTitle?: string, okDesc?: string, busyKey?: string): Promise<boolean> {
    setBusyId(busyKey ?? String(body.id ?? body.bedId ?? body.studentId ?? "op"));
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/boarding`, body);
      if (okTitle) toast({ title: okTitle, description: okDesc });
      load();
      return true;
    } catch (e: unknown) {
      toast({ title: "Failed", description: (e as Error).message, variant: "destructive" });
      return false;
    } finally {
      setBusyId(null);
    }
  }

  if (!data) return <LoadingBlock label="Loading boarding…" />;

  const availableBeds = data.beds.filter((b) => b.status === "AVAILABLE" && !b.studentId);
  const dayLearners = data.activeLearners.filter((l) => l.residenceType === "DAY" && !l.bedCode);

  return (
    <div>
      <ModuleHeader
        title="Boarding Management"
        description="Houses, dormitories and beds — assign boarders, track occupancy and keep the compound accounted for."
        actions={
          <div className="flex gap-2 flex-wrap no-print">
            {tab === "houses" && <Button size="sm" className="gap-1.5" onClick={() => setHouseDlg("new")}><Plus className="size-4" /> New house</Button>}
            {tab === "dorms" && <Button size="sm" className="gap-1.5" onClick={() => setDormDlg("new")}><Plus className="size-4" /> New dormitory</Button>}
            {tab === "beds" && (
              <>
                <Button size="sm" variant="outline" className="gap-1.5" onClick={() => setBulkOpen(true)}><Layers className="size-4" /> Bulk add beds</Button>
                <Button size="sm" className="gap-1.5" onClick={() => setBedDlg("new")}><BedSingle className="size-4" /> Add bed</Button>
              </>
            )}
            {tab === "assign" && <Button size="sm" variant="outline" className="gap-1.5" onClick={() => setConvertOpen(true)}><UserPlus className="size-4" /> Convert day learner</Button>}
          </div>
        }
      />

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="w-full flex-wrap h-auto justify-start gap-1 no-print">
          <TabsTrigger value="houses">Houses ({data.stats.houses})</TabsTrigger>
          <TabsTrigger value="dorms">Dormitories ({data.stats.dorms})</TabsTrigger>
          <TabsTrigger value="beds">Beds ({data.stats.beds})</TabsTrigger>
          <TabsTrigger value="assign">Assignment ({data.stats.unassigned ? `${data.stats.unassigned} waiting` : "ok"})</TabsTrigger>
          <TabsTrigger value="reports">Reports</TabsTrigger>
        </TabsList>

        {/* ── HOUSES ─────────────────────────────────────────── */}
        <TabsContent value="houses" className="mt-4">
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-base flex items-center gap-2"><Home className="size-4 text-primary" /> Boarding houses</CardTitle>
            </CardHeader>
            <CardContent>
              {data.houses.length === 0 ? (
                <EmptyState icon={<Home className="size-5" />} title="No boarding houses yet" hint="Create your first house to start organising dormitories and beds." />
              ) : (
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>House</TableHead>
                        <TableHead>House master</TableHead>
                        <TableHead>Prefect</TableHead>
                        <TableHead className="text-right">Capacity</TableHead>
                        <TableHead>Year</TableHead>
                        <TableHead className="text-right">Termly fee</TableHead>
                        <TableHead className="text-center">Dorms</TableHead>
                        <TableHead>Occupancy</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead className="w-[80px] text-right">Actions</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {data.houses.map((h) => (
                        <TableRow key={h.id}>
                          <TableCell>
                            <div className="font-medium">{h.name}</div>
                            {h.description && <div className="text-xs text-muted-foreground max-w-[220px] truncate">{h.description}</div>}
                          </TableCell>
                          <TableCell className="text-sm">{h.houseMasterName ?? <span className="text-muted-foreground">—</span>}</TableCell>
                          <TableCell className="text-sm">{h.prefectName ?? <span className="text-muted-foreground">—</span>}</TableCell>
                          <TableCell className="text-right tabular-nums">{h.capacity}</TableCell>
                          <TableCell className="text-sm tabular-nums">{h.yearEstablished ?? "—"}</TableCell>
                          <TableCell className="text-right tabular-nums">{fmtCedi(h.termlyFeeCents, { compact: true })}</TableCell>
                          <TableCell className="text-center tabular-nums">{h.dormCount}</TableCell>
                          <TableCell>
                            <div className="min-w-[110px]">
                              <div className="flex justify-between text-xs mb-1">
                                <span className="tabular-nums">{h.occupied}/{h.bedCount}</span>
                                <span className="text-muted-foreground tabular-nums">{h.bedCount ? Math.round((h.occupied / h.bedCount) * 100) : 0}%</span>
                              </div>
                              <Progress value={h.bedCount ? (h.occupied / h.bedCount) * 100 : 0} className="h-1.5" />
                            </div>
                          </TableCell>
                          <TableCell><BoardingStatus status={h.status} /></TableCell>
                          <TableCell className="text-right">
                            <div className="flex justify-end gap-1">
                              <Button size="sm" variant="ghost" className="size-8 p-0" aria-label={`Edit ${h.name}`} onClick={() => setHouseDlg(h)}><Pencil className="size-3.5" /></Button>
                              <Button size="sm" variant="ghost" className="size-8 p-0 text-muted-foreground hover:text-destructive" aria-label={`Delete ${h.name}`} disabled={busyId === h.id}
                                onClick={() => act({ action: "house.delete", id: h.id }, "House deleted")}>
                                <Trash2 className="size-3.5" />
                              </Button>
                            </div>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── DORMITORIES ────────────────────────────────────── */}
        <TabsContent value="dorms" className="mt-4">
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-base flex items-center gap-2"><Building2 className="size-4 text-primary" /> Dormitories</CardTitle>
            </CardHeader>
            <CardContent>
              {data.dorms.length === 0 ? (
                <EmptyState icon={<Building2 className="size-5" />} title="No dormitories yet" hint="Add dormitories to your boarding houses, then lay out the beds." />
              ) : (
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Dormitory</TableHead>
                        <TableHead>House</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Floor</TableHead>
                        <TableHead className="text-right">Capacity</TableHead>
                        <TableHead className="text-center">Beds</TableHead>
                        <TableHead className="text-center">Available</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead className="w-[80px] text-right">Actions</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {data.dorms.map((d) => (
                        <TableRow key={d.id}>
                          <TableCell>
                            <div className="font-medium">{d.name}</div>
                            {d.prefectName && <div className="text-xs text-muted-foreground">Prefect: {d.prefectName}</div>}
                          </TableCell>
                          <TableCell className="text-sm">{d.houseName}</TableCell>
                          <TableCell>
                            {d.type ? (
                              <Badge variant="outline" className={d.type === "Male" ? "text-emerald-700 border-emerald-200 dark:text-emerald-300 dark:border-emerald-900" : "text-rose-700 border-rose-200 dark:text-rose-300 dark:border-rose-900"}>{d.type}</Badge>
                            ) : (<span className="text-muted-foreground text-sm">Mixed</span>)}
                          </TableCell>
                          <TableCell className="text-sm">{d.floor ?? "—"}</TableCell>
                          <TableCell className="text-right tabular-nums">{d.capacity}</TableCell>
                          <TableCell className="text-center tabular-nums">{d.bedCount}</TableCell>
                          <TableCell className="text-center tabular-nums">{d.availableBeds}</TableCell>
                          <TableCell><BoardingStatus status={d.status} /></TableCell>
                          <TableCell className="text-right">
                            <div className="flex justify-end gap-1">
                              <Button size="sm" variant="ghost" className="size-8 p-0" aria-label={`Edit ${d.name}`} onClick={() => setDormDlg(d)}><Pencil className="size-3.5" /></Button>
                              <Button size="sm" variant="ghost" className="size-8 p-0 text-muted-foreground hover:text-destructive" aria-label={`Delete ${d.name}`} disabled={busyId === d.id}
                                onClick={() => act({ action: "dorm.delete", id: d.id }, "Dormitory deleted")}>
                                <Trash2 className="size-3.5" />
                              </Button>
                            </div>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── BEDS ───────────────────────────────────────────── */}
        <TabsContent value="beds" className="mt-4">
          <Card>
            <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
              <CardTitle className="text-base flex items-center gap-2"><BedDouble className="size-4 text-primary" /> Beds</CardTitle>
              <div className="text-xs text-muted-foreground">{data.stats.occupied} occupied · {data.stats.available} available{data.stats.maintenance ? ` · ${data.stats.maintenance} maintenance` : ""}</div>
            </CardHeader>
            <CardContent>
              {data.beds.length === 0 ? (
                <EmptyState icon={<BedDouble className="size-5" />} title="No beds yet" hint="Add beds one at a time or in bulk with a code prefix (e.g. AGA-001…)." />
              ) : (
                <div className="max-h-[560px] overflow-y-auto nice-scroll">
                  <Table>
                    <TableHeader className="sticky top-0 bg-background z-10">
                      <TableRow>
                        <TableHead>Code</TableHead>
                        <TableHead>Dormitory</TableHead>
                        <TableHead>House</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Occupant</TableHead>
                        <TableHead className="w-[80px] text-right">Actions</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {data.beds.map((b) => (
                        <TableRow key={b.id}>
                          <TableCell className="font-mono text-sm font-medium">{b.code}</TableCell>
                          <TableCell className="text-sm">{b.dormName}</TableCell>
                          <TableCell className="text-sm">{b.houseName}</TableCell>
                          <TableCell><Badge variant="secondary" className="text-[11px]">{b.bedType}</Badge></TableCell>
                          <TableCell><BoardingStatus status={b.status} /></TableCell>
                          <TableCell className="text-sm">{b.studentName ?? <span className="text-muted-foreground">—</span>}</TableCell>
                          <TableCell className="text-right">
                            <div className="flex justify-end gap-1">
                              <Button size="sm" variant="ghost" className="size-8 p-0" aria-label={`Edit ${b.code}`} onClick={() => setBedDlg(b)}><Pencil className="size-3.5" /></Button>
                              <Button size="sm" variant="ghost" className="size-8 p-0 text-muted-foreground hover:text-destructive" aria-label={`Delete ${b.code}`} disabled={busyId === b.id}
                                onClick={() => act({ action: "bed.delete", id: b.id }, "Bed deleted")}>
                                <Trash2 className="size-3.5" />
                              </Button>
                            </div>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── STUDENT ASSIGNMENT ─────────────────────────────── */}
        <TabsContent value="assign" className="mt-4">
          <div className="grid lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base flex items-center gap-2">
                  <UserPlus className="size-4 text-primary" /> Unassigned boarders ({data.unassignedBoarders.length})
                </CardTitle>
              </CardHeader>
              <CardContent>
                {data.unassignedBoarders.length === 0 ? (
                  <EmptyState icon={<Users className="size-5" />} title="Every boarder has a bed" hint="Convert a day learner to boarding to place them in a house." />
                ) : (
                  <div className="space-y-2 max-h-96 overflow-y-auto nice-scroll pr-1">
                    {data.unassignedBoarders.map((s) => (
                      <AssignRow key={s.id} student={s} availableBeds={availableBeds} houses={data.houses} busy={busyId === s.id}
                        onAssign={async (bedId) => { await act({ action: "bed.assign", bedId, studentId: s.id }, "Boarder assigned", `${s.name} now has a bed.`, s.id); }} />
                    ))}
                  </div>
                )}
                <div className="border-t mt-4 pt-3">
                  <Button size="sm" variant="outline" className="gap-1.5 w-full" onClick={() => setConvertOpen(true)} disabled={dayLearners.length === 0 || availableBeds.length === 0}>
                    <UserPlus className="size-4" /> Convert a day learner to boarder
                  </Button>
                  {availableBeds.length === 0 && <p className="text-xs text-muted-foreground mt-1.5">No free beds — add beds first.</p>}
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base flex items-center gap-2">
                  <BedDouble className="size-4 text-primary" /> Current occupants ({data.occupants.length})
                </CardTitle>
              </CardHeader>
              <CardContent>
                {data.occupants.length === 0 ? (
                  <EmptyState icon={<BedDouble className="size-5" />} title="No boarders in residence yet" hint="Assign learners to beds from the pane on the left." />
                ) : (
                  <div className="space-y-1.5 max-h-96 overflow-y-auto nice-scroll pr-1">
                    {data.occupants.map((o) => (
                      <div key={o.bedId} className="flex items-center justify-between gap-2 border-b last:border-0 py-1.5">
                        <div className="min-w-0">
                          <div className="text-sm font-medium truncate">{o.studentName}</div>
                          <div className="text-xs text-muted-foreground">
                            <span className="font-mono">{o.code}</span> · {o.dormName}, {o.houseName}{o.classroom ? ` · ${o.classroom}` : ""}
                          </div>
                          <div className="text-[10px] text-muted-foreground">since {fmtDate(o.assignedAt)}</div>
                        </div>
                        <Button size="sm" variant="outline" className="h-7 gap-1 text-xs shrink-0" disabled={busyId === o.bedId}
                          onClick={() => act({ action: "bed.release", bedId: o.bedId }, "Boarder released", `${o.studentName} is back on day status.`)}>
                          <LogOut className="size-3" /> Release
                        </Button>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* ── REPORTS ────────────────────────────────────────── */}
        <TabsContent value="reports" className="mt-4">
          <div className="print-area space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 className="text-lg font-bold">{schoolName} · Boarding Occupancy Report</h2>
                <p className="text-xs text-muted-foreground">Generated {fmtDate(new Date())} — {data.stats.houses} houses · {data.stats.dorms} dormitories · {data.stats.beds} beds</p>
              </div>
              <Button size="sm" variant="outline" className="gap-1.5 no-print" onClick={() => window.print()}><Printer className="size-4" /> Print report</Button>
            </div>

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
              <StatCard icon={<Home className="size-4" />} label="Houses" value={data.stats.houses} sub={`${data.stats.dorms} dormitories`} />
              <StatCard icon={<Building2 className="size-4" />} label="Dormitories" value={data.stats.dorms} sub={`${data.stats.beds} beds laid out`} />
              <StatCard icon={<BedDouble className="size-4" />} label="Beds occupied" value={`${data.stats.occupied}/${data.stats.beds}`} tone="primary" sub={`${data.stats.available} available${data.stats.maintenance ? ` · ${data.stats.maintenance} in maintenance` : ""}`} />
              <StatCard icon={<Users className="size-4" />} label="Occupancy" value={`${data.stats.occupancyRate}%`} tone="gold" sub={`${data.stats.boarders} boarders · ${data.stats.unassigned} awaiting a bed`} />
            </div>

            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base">Occupancy by house</CardTitle>
              </CardHeader>
              <CardContent>
                {data.houses.length === 0 ? (
                  <EmptyState icon={<Home className="size-5" />} title="Nothing to report yet" hint="Set up houses, dormitories and beds first." />
                ) : (
                  <div className="overflow-x-auto">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>House / Dormitory</TableHead>
                          <TableHead className="text-right">Beds</TableHead>
                          <TableHead className="text-right">Occupied</TableHead>
                          <TableHead className="text-right">Available</TableHead>
                          <TableHead className="w-[150px]">Occupancy rate</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {data.houses.map((h) => (
                          <Fragment key={h.id}>
                            <TableRow className="bg-muted/40">
                              <TableCell className="font-medium">{h.name}{h.houseMasterName ? <span className="text-xs text-muted-foreground font-normal"> · {h.houseMasterName}</span> : null}</TableCell>
                              <TableCell className="text-right tabular-nums font-medium">{h.bedCount}</TableCell>
                              <TableCell className="text-right tabular-nums font-medium">{h.occupied}</TableCell>
                              <TableCell className="text-right tabular-nums font-medium">{h.bedCount - h.occupied}</TableCell>
                              <TableCell>
                                <div className="flex items-center gap-2">
                                  <Progress value={h.bedCount ? (h.occupied / h.bedCount) * 100 : 0} className="h-1.5 flex-1" />
                                  <OccupancyBadge rate={h.bedCount ? (h.occupied / h.bedCount) * 100 : 0} />
                                </div>
                              </TableCell>
                            </TableRow>
                            {h.dormitories.map((d) => (
                              <TableRow key={d.id}>
                                <TableCell className="pl-6 text-sm text-muted-foreground">
                                  ↳ {d.name}{d.type ? ` (${d.type})` : ""}{d.floor ? ` · ${d.floor} floor` : ""}
                                </TableCell>
                                <TableCell className="text-right tabular-nums text-sm">{d.bedCount}</TableCell>
                                <TableCell className="text-right tabular-nums text-sm">{d.occupied}</TableCell>
                                <TableCell className="text-right tabular-nums text-sm">{d.bedCount - d.occupied}</TableCell>
                                <TableCell>
                                  <div className="flex items-center gap-2">
                                    <Progress value={d.bedCount ? (d.occupied / d.bedCount) * 100 : 0} className="h-1.5 flex-1" />
                                    <OccupancyBadge rate={d.bedCount ? (d.occupied / d.bedCount) * 100 : 0} />
                                  </div>
                                </TableCell>
                              </TableRow>
                            ))}
                          </Fragment>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base">Boarder directory ({data.occupants.length})</CardTitle>
              </CardHeader>
              <CardContent>
                {data.occupants.length === 0 ? (
                  <EmptyState icon={<Users className="size-5" />} title="No boarders in residence" hint="Assign learners to beds to build the directory." />
                ) : (
                  <div className="max-h-96 overflow-y-auto nice-scroll">
                    <Table>
                      <TableHeader className="sticky top-0 bg-background z-10">
                        <TableRow>
                          <TableHead>Learner</TableHead>
                          <TableHead>Class</TableHead>
                          <TableHead>House</TableHead>
                          <TableHead>Dormitory</TableHead>
                          <TableHead>Bed</TableHead>
                          <TableHead>Since</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {[...data.occupants]
                          .sort((a, b) => a.houseName.localeCompare(b.houseName) || a.code.localeCompare(b.code))
                          .map((o) => (
                            <TableRow key={o.bedId}>
                              <TableCell className="font-medium">{o.studentName}</TableCell>
                              <TableCell className="text-sm">{o.classroom ?? "—"}</TableCell>
                              <TableCell className="text-sm">{o.houseName}</TableCell>
                              <TableCell className="text-sm">{o.dormName}</TableCell>
                              <TableCell className="font-mono text-sm">{o.code}</TableCell>
                              <TableCell className="text-sm">{fmtDate(o.assignedAt)}</TableCell>
                            </TableRow>
                          ))}
                      </TableBody>
                    </Table>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>

      {/* ── dialogs ── */}
      <HouseDialog target={houseDlg} staff={data.teachingStaff} learners={data.activeLearners} onOpenChange={(o) => !o && setHouseDlg(null)} onDone={() => { setHouseDlg(null); load(); }} />
      <DormDialog target={dormDlg} houses={data.houses} learners={data.activeLearners} onOpenChange={(o) => !o && setDormDlg(null)} onDone={() => { setDormDlg(null); load(); }} />
      <BedDialog target={bedDlg} dorms={data.dorms} onOpenChange={(o) => !o && setBedDlg(null)} onDone={() => { setBedDlg(null); load(); }} />
      <BulkBedDialog open={bulkOpen} dorms={data.dorms} onOpenChange={setBulkOpen} onDone={() => { setBulkOpen(false); load(); }} />
      <ConvertDialog open={convertOpen} dayLearners={dayLearners} availableBeds={availableBeds} houses={data.houses} onOpenChange={setConvertOpen} onDone={() => { setConvertOpen(false); load(); }} />
    </div>
  );
}

// ── one unassigned boarder row with a grouped bed picker ──
function AssignRow({ student, availableBeds, houses, busy, onAssign }: {
  student: { id: string; name: string; classroom: string | null; gender: string };
  availableBeds: BedRow[];
  houses: HouseRow[];
  busy: boolean;
  onAssign: (bedId: string) => Promise<void>;
}) {
  const [bedId, setBedId] = useState("");
  return (
    <div className="flex items-center gap-2 border rounded-lg p-2">
      <div className="min-w-0 flex-1">
        <div className="text-sm font-medium truncate flex items-center gap-1.5">
          <GenderBadge gender={student.gender} /> {student.name}
        </div>
        <div className="text-xs text-muted-foreground">{student.classroom ?? "—"}</div>
      </div>
      <BedPicker value={bedId} onValueChange={setBedId} availableBeds={availableBeds} houses={houses} className="w-[190px] shrink-0" />
      <Button size="sm" className="h-8 shrink-0" disabled={!bedId || busy} onClick={() => onAssign(bedId)}>Assign</Button>
    </div>
  );
}

/** available beds grouped by house → dormitory */
function BedPicker({ value, onValueChange, availableBeds, houses, className }: {
  value: string; onValueChange: (v: string) => void; availableBeds: BedRow[]; houses: HouseRow[]; className?: string;
}) {
  return (
    <Select value={value || undefined} onValueChange={onValueChange}>
      <SelectTrigger className={className} aria-label="Choose an available bed">
        <SelectValue placeholder="Choose a bed…" />
      </SelectTrigger>
      <SelectContent className="max-h-72">
        {houses.map((h) => {
          const beds = availableBeds.filter((b) => b.houseId === h.id);
          if (!beds.length) return null;
          return (
            <SelectGroup key={h.id}>
              <SelectLabel>{h.name}</SelectLabel>
              {beds.map((b) => (
                <SelectItem key={b.id} value={b.id}>
                  <span className="font-mono text-xs">{b.code}</span> · {b.dormName}
                </SelectItem>
              ))}
            </SelectGroup>
          );
        })}
        {availableBeds.length === 0 && <div className="px-3 py-2 text-sm text-muted-foreground">No free beds</div>}
      </SelectContent>
    </Select>
  );
}

// ── house create/edit dialog ──
function HouseDialog({ target, staff, learners, onOpenChange, onDone }: {
  target: HouseRow | "new" | null; staff: { id: string; fullName: string; role: string }[]; learners: Learner[];
  onOpenChange: (o: boolean) => void; onDone: () => void;
}) {
  const { currentSchoolId } = useStore();
  const row = target && target !== "new" ? target : null;
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [houseMasterId, setHouseMasterId] = useState("none");
  const [prefectId, setPrefectId] = useState("none");
  const [capacity, setCapacity] = useState("40");
  const [year, setYear] = useState("");
  const [gps, setGps] = useState("");
  const [fee, setFee] = useState("");
  const [status, setStatus] = useState("AVAILABLE");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (row) {
      setName(row.name); setDescription(row.description ?? "");
      setHouseMasterId(row.houseMasterId ?? "none"); setPrefectId(row.prefectId ?? "none");
      setCapacity(String(row.capacity)); setYear(row.yearEstablished ?? "");
      setGps(row.gpsCode ?? ""); setFee(row.termlyFeeCents ? String(row.termlyFeeCents / 100) : "");
      setStatus(row.status);
    } else if (target === "new") {
      setName(""); setDescription(""); setHouseMasterId("none"); setPrefectId("none");
      setCapacity("40"); setYear(""); setGps(""); setFee(""); setStatus("AVAILABLE");
    }
  }, [target]);

  async function submit() {
    if (!name.trim()) { toast({ title: "House name is required" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/boarding`, {
        action: row ? "house.update" : "house.create",
        id: row?.id,
        name, description, houseMasterId, prefectId,
        capacity: Number(capacity) || 0, yearEstablished: year, gpsCode: gps,
        termlyFeeCents: Math.round((parseFloat(fee) || 0) * 100), status,
      });
      toast({ title: row ? "House updated" : "House created" });
      onDone();
    } catch (e: unknown) { toast({ title: "Failed", description: (e as Error).message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!target} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[92vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle>{row ? "Edit boarding house" : "New boarding house"}</DialogTitle>
          <DialogDescription>A house groups dormitories under a house master, prefect and termly boarding fee.</DialogDescription>
        </DialogHeader>
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1.5 col-span-2"><Label>House name *</Label><Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Agyei House" /></div>
          <div className="space-y-1.5 col-span-2"><Label>Description</Label><Textarea rows={2} value={description} onChange={(e) => setDescription(e.target.value)} placeholder="Optional notes — wing, character, tradition…" /></div>
          <div className="space-y-1.5">
            <Label>House master</Label>
            <Select value={houseMasterId} onValueChange={setHouseMasterId}>
              <SelectTrigger><SelectValue placeholder="Choose teaching staff" /></SelectTrigger>
              <SelectContent className="max-h-64">
                <SelectItem value="none">— none —</SelectItem>
                {staff.map((s) => <SelectItem key={s.id} value={s.id}>{s.fullName}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>House prefect</Label>
            <Select value={prefectId} onValueChange={setPrefectId}>
              <SelectTrigger><SelectValue placeholder="Choose a learner" /></SelectTrigger>
              <SelectContent className="max-h-64">
                <SelectItem value="none">— none —</SelectItem>
                {learners.map((l) => <SelectItem key={l.id} value={l.id}>{l.name}{l.classroom ? ` · ${l.classroom}` : ""}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5"><Label>Capacity</Label><Input type="number" min={0} value={capacity} onChange={(e) => setCapacity(e.target.value)} /></div>
          <div className="space-y-1.5"><Label>Year established</Label><Input value={year} onChange={(e) => setYear(e.target.value)} placeholder="e.g. 2012" /></div>
          <div className="space-y-1.5"><Label>GPS code</Label><Input value={gps} onChange={(e) => setGps(e.target.value)} placeholder="e.g. GH-545-231" /></div>
          <div className="space-y-1.5"><Label>Termly boarding fee (GH₵)</Label><Input type="number" min={0} step="0.01" value={fee} onChange={(e) => setFee(e.target.value)} placeholder="e.g. 450" /></div>
          <div className="space-y-1.5 col-span-2">
            <Label>Status</Label>
            <Select value={status} onValueChange={setStatus}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="AVAILABLE">Available</SelectItem>
                <SelectItem value="ASSIGNED">Assigned</SelectItem>
                <SelectItem value="MAINTENANCE">Maintenance</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{row ? "Save changes" : "Create house"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── dormitory create/edit dialog ──
function DormDialog({ target, houses, learners, onOpenChange, onDone }: {
  target: DormRow | "new" | null; houses: HouseRow[]; learners: Learner[];
  onOpenChange: (o: boolean) => void; onDone: () => void;
}) {
  const { currentSchoolId } = useStore();
  const row = target && target !== "new" ? target : null;
  const [houseId, setHouseId] = useState("");
  const [name, setName] = useState("");
  const [type, setType] = useState("none");
  const [floor, setFloor] = useState("");
  const [capacity, setCapacity] = useState("10");
  const [description, setDescription] = useState("");
  const [prefectId, setPrefectId] = useState("none");
  const [status, setStatus] = useState("AVAILABLE");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (row) {
      setHouseId(row.houseId); setName(row.name); setType(row.type ?? "none");
      setFloor(row.floor ?? ""); setCapacity(String(row.capacity));
      setDescription(row.description ?? ""); setPrefectId(row.prefectId ?? "none"); setStatus(row.status);
    } else if (target === "new") {
      setHouseId(houses[0]?.id ?? ""); setName(""); setType("none"); setFloor(""); setCapacity("10");
      setDescription(""); setPrefectId("none"); setStatus("AVAILABLE");
    }
  }, [target, houses]);

  async function submit() {
    if (!name.trim()) { toast({ title: "Dormitory name is required" }); return; }
    if (!houseId) { toast({ title: "Choose a boarding house" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/boarding`, {
        action: row ? "dorm.update" : "dorm.create",
        id: row?.id,
        houseId, name, type: type === "none" ? null : type, floor, capacity: Number(capacity) || 0,
        description, prefectId, status,
      });
      toast({ title: row ? "Dormitory updated" : "Dormitory created" });
      onDone();
    } catch (e: unknown) { toast({ title: "Failed", description: (e as Error).message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!target} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[92vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle>{row ? "Edit dormitory" : "New dormitory"}</DialogTitle>
          <DialogDescription>A dormitory belongs to a house and holds the beds your boarders sleep in.</DialogDescription>
        </DialogHeader>
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1.5 col-span-2">
            <Label>Boarding house *</Label>
            <Select value={houseId || undefined} onValueChange={setHouseId}>
              <SelectTrigger><SelectValue placeholder="Choose a house" /></SelectTrigger>
              <SelectContent>
                {houses.map((h) => <SelectItem key={h.id} value={h.id}>{h.name}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5 col-span-2"><Label>Dormitory name *</Label><Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Agyei Block A" /></div>
          <div className="space-y-1.5">
            <Label>Type</Label>
            <Select value={type} onValueChange={setType}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">Mixed / not set</SelectItem>
                <SelectItem value="Male">Male</SelectItem>
                <SelectItem value="Female">Female</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5"><Label>Floor</Label><Input value={floor} onChange={(e) => setFloor(e.target.value)} placeholder="e.g. Ground" /></div>
          <div className="space-y-1.5"><Label>Capacity</Label><Input type="number" min={0} value={capacity} onChange={(e) => setCapacity(e.target.value)} /></div>
          <div className="space-y-1.5">
            <Label>Dormitory prefect</Label>
            <Select value={prefectId} onValueChange={setPrefectId}>
              <SelectTrigger><SelectValue placeholder="Choose a learner" /></SelectTrigger>
              <SelectContent className="max-h-64">
                <SelectItem value="none">— none —</SelectItem>
                {learners.map((l) => <SelectItem key={l.id} value={l.id}>{l.name}{l.classroom ? ` · ${l.classroom}` : ""}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5 col-span-2"><Label>Description</Label><Textarea rows={2} value={description} onChange={(e) => setDescription(e.target.value)} placeholder="Optional notes" /></div>
          <div className="space-y-1.5 col-span-2">
            <Label>Status</Label>
            <Select value={status} onValueChange={setStatus}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="AVAILABLE">Available</SelectItem>
                <SelectItem value="ASSIGNED">Assigned</SelectItem>
                <SelectItem value="MAINTENANCE">Maintenance</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{row ? "Save changes" : "Create dormitory"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── single bed create/edit dialog ──
function BedDialog({ target, dorms, onOpenChange, onDone }: {
  target: BedRow | "new" | null; dorms: DormRow[];
  onOpenChange: (o: boolean) => void; onDone: () => void;
}) {
  const { currentSchoolId } = useStore();
  const row = target && target !== "new" ? target : null;
  const [code, setCode] = useState("");
  const [dormitoryId, setDormitoryId] = useState("");
  const [bedType, setBedType] = useState("SINGLE");
  const [status, setStatus] = useState("AVAILABLE");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (row) {
      setCode(row.code); setDormitoryId(row.dormitoryId); setBedType(row.bedType); setStatus(row.status);
    } else if (target === "new") {
      setCode(""); setDormitoryId(dorms[0]?.id ?? ""); setBedType("SINGLE"); setStatus("AVAILABLE");
    }
  }, [target, dorms]);

  async function submit() {
    if (!code.trim()) { toast({ title: "Bed code is required" }); return; }
    if (!dormitoryId) { toast({ title: "Choose a dormitory" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/boarding`, {
        action: row ? "bed.update" : "bed.create",
        id: row?.id,
        code, dormitoryId, bedType, status,
      });
      toast({ title: row ? "Bed updated" : "Bed added" });
      onDone();
    } catch (e: unknown) { toast({ title: "Failed", description: (e as Error).message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={!!target} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{row ? `Edit bed ${row.code}` : "Add a bed"}</DialogTitle>
          <DialogDescription>Bed codes are unique across the school, e.g. AGA-007.</DialogDescription>
        </DialogHeader>
        {row?.studentName && (
          <div className="rounded-md border border-amber-200 bg-amber-50 dark:bg-amber-950 dark:border-amber-900 px-3 py-2 text-sm text-amber-800 dark:text-amber-200">
            Occupied by <b>{row.studentName}</b> — release the boarder before changing status or dormitory.
          </div>
        )}
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1.5"><Label>Bed code *</Label><Input value={code} onChange={(e) => setCode(e.target.value.toUpperCase())} placeholder="e.g. AGA-007" className="font-mono" /></div>
          <div className="space-y-1.5">
            <Label>Bed type</Label>
            <Select value={bedType} onValueChange={setBedType}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="SINGLE">Single</SelectItem>
                <SelectItem value="BUNK">Bunk</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5 col-span-2">
            <Label>Dormitory *</Label>
            <Select value={dormitoryId || undefined} onValueChange={setDormitoryId} disabled={!!row?.studentId}>
              <SelectTrigger><SelectValue placeholder="Choose a dormitory" /></SelectTrigger>
              <SelectContent className="max-h-64">
                {dorms.map((d) => <SelectItem key={d.id} value={d.id}>{d.name} · {d.houseName}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          {row && (
            <div className="space-y-1.5 col-span-2">
              <Label>Status</Label>
              <Select value={status} onValueChange={setStatus} disabled={!!row.studentId}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="AVAILABLE">Available</SelectItem>
                  <SelectItem value="ASSIGNED">Assigned</SelectItem>
                  <SelectItem value="MAINTENANCE">Maintenance</SelectItem>
                </SelectContent>
              </Select>
            </div>
          )}
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy}>{row ? "Save changes" : "Add bed"}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── bulk bed dialog (PREFIX-001 … PREFIX-N, max 100, confirm > 50) ──
function BulkBedDialog({ open, dorms, onOpenChange, onDone }: {
  open: boolean; dorms: DormRow[]; onOpenChange: (o: boolean) => void; onDone: (created?: number, skipped?: number) => void;
}) {
  const { currentSchoolId } = useStore();
  const [dormitoryId, setDormitoryId] = useState("");
  const [prefix, setPrefix] = useState("");
  const [count, setCount] = useState("10");
  const [bedType, setBedType] = useState("SINGLE");
  const [confirmed, setConfirmed] = useState(false);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (open) { setDormitoryId(dorms[0]?.id ?? ""); setPrefix(""); setCount("10"); setBedType("SINGLE"); setConfirmed(false); }
  }, [open, dorms]);

  const n = Math.round(Number(count) || 0);
  const needsConfirm = n > 50;

  async function submit() {
    if (!prefix.trim()) { toast({ title: "A bed code prefix is required" }); return; }
    if (!dormitoryId) { toast({ title: "Choose a dormitory" }); return; }
    if (n < 1 || n > 100) { toast({ title: "Count must be between 1 and 100" }); return; }
    if (needsConfirm && !confirmed) return;
    setBusy(true);
    try {
      const res = await apiPost<{ created: number; skipped: number }>(`/api/school/${currentSchoolId}/ops/boarding`, {
        action: "bed.bulk", dormitoryId, prefix, count: n, bedType,
      });
      toast({
        title: `${res.created} bed${res.created === 1 ? "" : "s"} added`,
        description: res.skipped ? `${res.skipped} code(s) already existed and were skipped.` : undefined,
      });
      onDone(res.created, res.skipped);
    } catch (e: unknown) { toast({ title: "Failed", description: (e as Error).message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><Layers className="size-4 text-primary" /> Bulk add beds</DialogTitle>
          <DialogDescription>Generates codes <span className="font-mono">PREFIX-001</span> … <span className="font-mono">PREFIX-{String(Math.min(n, 100)).padStart(3, "0")}</span> in one dormitory (existing codes are skipped).</DialogDescription>
        </DialogHeader>
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1.5 col-span-2">
            <Label>Dormitory *</Label>
            <Select value={dormitoryId || undefined} onValueChange={setDormitoryId}>
              <SelectTrigger><SelectValue placeholder="Choose a dormitory" /></SelectTrigger>
              <SelectContent className="max-h-64">
                {dorms.map((d) => <SelectItem key={d.id} value={d.id}>{d.name} · {d.houseName}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5"><Label>Code prefix *</Label><Input value={prefix} onChange={(e) => setPrefix(e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, ""))} placeholder="e.g. AGA" className="font-mono" /></div>
          <div className="space-y-1.5"><Label>How many beds (1–100)</Label><Input type="number" min={1} max={100} value={count} onChange={(e) => { setCount(e.target.value); setConfirmed(false); }} /></div>
          <div className="space-y-1.5 col-span-2">
            <Label>Bed type</Label>
            <Select value={bedType} onValueChange={setBedType}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="SINGLE">Single</SelectItem>
                <SelectItem value="BUNK">Bunk</SelectItem>
              </SelectContent>
            </Select>
          </div>
          {needsConfirm && (
            <div className="col-span-2 rounded-md border border-amber-300 bg-amber-50 dark:bg-amber-950 dark:border-amber-800 px-3 py-2.5 space-y-2">
              <p className="text-sm text-amber-800 dark:text-amber-200">
                You are about to create <b>{n} beds</b> at once. Are you sure?
              </p>
              <label className="flex items-center gap-2 text-sm text-amber-900 dark:text-amber-100">
                <Checkbox checked={confirmed} onCheckedChange={(v) => setConfirmed(v === true)} />
                Yes, create all {n} beds
              </label>
            </div>
          )}
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || (needsConfirm && !confirmed)}>Add {n > 0 ? n : ""} beds</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── convert a day learner to boarder (learner + bed → assign) ──
function ConvertDialog({ open, dayLearners, availableBeds, houses, onOpenChange, onDone }: {
  open: boolean; dayLearners: Learner[]; availableBeds: BedRow[]; houses: HouseRow[];
  onOpenChange: (o: boolean) => void; onDone: () => void;
}) {
  const { currentSchoolId } = useStore();
  const [studentId, setStudentId] = useState("");
  const [bedId, setBedId] = useState("");
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (open) { setStudentId(""); setBedId(""); }
  }, [open]);

  async function submit() {
    if (!studentId || !bedId) { toast({ title: "Choose a learner and a bed" }); return; }
    setBusy(true);
    try {
      const learner = dayLearners.find((l) => l.id === studentId);
      await apiPost(`/api/school/${currentSchoolId}/ops/boarding`, { action: "bed.assign", bedId, studentId });
      toast({ title: "Learner converted to boarder", description: `${learner?.name ?? "Learner"} now boards — residence set to BOARDING.` });
      onDone();
    } catch (e: unknown) { toast({ title: "Failed", description: (e as Error).message, variant: "destructive" }); } finally { setBusy(false); }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2"><UserPlus className="size-4 text-primary" /> Convert a day learner to boarder</DialogTitle>
          <DialogDescription>Pick an active day learner and a free bed — the learner&apos;s residence becomes BOARDING and the bed is theirs.</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="space-y-1.5">
            <Label>Day learner *</Label>
            <Select value={studentId || undefined} onValueChange={setStudentId}>
              <SelectTrigger><SelectValue placeholder="Choose a learner" /></SelectTrigger>
              <SelectContent className="max-h-64">
                {dayLearners.map((l) => (
                  <SelectItem key={l.id} value={l.id}>
                    <span className="flex items-center gap-1.5"><GenderBadge gender={l.gender} /> {l.name}{l.classroom ? ` · ${l.classroom}` : ""}</span>
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Assign bed *</Label>
            <BedPicker value={bedId} onValueChange={setBedId} availableBeds={availableBeds} houses={houses} className="w-full" />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={submit} disabled={busy || !studentId || !bedId}>Convert &amp; assign</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

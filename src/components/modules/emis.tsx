"use client";
// ── GES EMIS Annual Return (wave 58 — the school census) ──
// Every Ghanaian school files an EMIS (Education Management Information
// System) return with the Ghana Education Service each year: identity,
// enrolment by class & gender, staffing, facilities, attendance and the
// WAEC candidacy picture. This module computes the census live from the
// school's own records, lets the head file it as an immutable per-year
// snapshot, and keeps the archive of past returns.
//   • Census preview — the live figures, ready to print for the district
//   • Filed returns — the archive; view any year exactly as it was filed
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Textarea } from "@/components/ui/textarea";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard, downloadCsv } from "@/components/ui-helpers";
import { fmtCedi, fmtDate, fmtDateTime } from "@/lib/format";
import { useToast } from "@/hooks/use-toast";
import {
  FileSpreadsheet, Printer, Download, Archive, Eye, Trash2, Users, GraduationCap, Landmark, ClipboardCheck, Undo2,
} from "lucide-react";

const OPS = "/ops/emis";

type ByClassRow = { classroom: string; gradeLevel: string; band: string; capacity: number; boys: number; girls: number; total: number };
type BandRow = { band: string; boys: number; girls: number; total: number };
type StaffCatRow = { category: string; male: number; female: number; other: number; total: number };
type WaecRow = { examType: string; examYear: number; centreNumber: string; candidates: number };
type Census = {
  identity: { name: string; code: string; motto: string | null; category: string | null; curriculum: string | null; region: string | null; district: string | null; town: string | null; gpsAddress: string | null; phone: string | null; email: string | null; establishedYear: number | null };
  calendar: { academicYear: string | null; currentTerm: string | null; termWindow: { startsAt: string; endsAt: string } | null };
  enrolment: { byClass: ByClassRow[]; bands: BandRow[]; totals: { boys: number; girls: number; total: number }; classes: number };
  staffing: { byCategory: StaffCatRow[]; qualifications: [string, number][]; total: number; teaching: number; ssnitEnrolled: number; studentTeacherRatio: number | null };
  facilities: { classrooms: number; classroomsWithCapacity: number; totalCapacity: number; subjectsOffered: number; boardingHouses: number; boardingBeds: number; boardingCapacity: number; libraryTitles: number; libraryCopies: number };
  attendance: { rate: number | null; marked: number; presentIsh: number; sessions: number };
  finance: { currentTerm: string | null; invoices: number; billedCents: number; collectedCents: number; collectionRate: number | null };
  waec: WaecRow[];
  generatedAt: string;
};
type CensusSnapshot = Census & { filedAt: string; filedByName: string; note: string | null; year: number };
type FiledRow = { id: string; year: number; filedByName: string; note: string | null; createdAt: string };
type EmisData = { filed: FiledRow[]; census: Census };

const CAT_LABEL: Record<string, string> = { TEACHING: "Teaching staff", NON_TEACHING: "Non-teaching", ADMINISTRATION: "Administration" };
const pct = (v: number | null | undefined) => (v == null ? "—" : `${v}%`);
const fillPct = (r: ByClassRow) => (r.capacity > 0 ? `${Math.round((r.total / r.capacity) * 100)}%` : "—");
/** curricula is stored as a JSON array string (e.g. '["GES"]') — show it as a list. */
const fmtCurricula = (v: string | null | undefined) => {
  if (!v) return null;
  try { const p = JSON.parse(v); return Array.isArray(p) ? p.join(" · ") : String(p); } catch { return v; }
};

export function Emis() {
  const { currentSchoolId, navTarget } = useStore();
  const { toast } = useToast();
  const schoolId = currentSchoolId ?? "";
  const [data, setData] = useState<EmisData | null>(null);
  const [tab, setTab] = useState("preview");
  const [viewing, setViewing] = useState<CensusSnapshot | null>(null);
  const [viewingYear, setViewingYear] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);
  const [fileDialog, setFileDialog] = useState(false);
  const [fileForm, setFileForm] = useState({ year: new Date().getFullYear(), note: "" });

  useEffect(() => { if (navTarget?.tab) setTab(navTarget.tab); }, [navTarget]);

  const load = useCallback(async () => {
    if (!schoolId) return;
    try { setData(await apiGet<EmisData>(`/api/school/${schoolId}${OPS}`)); }
    catch (e: any) { toast({ title: "Could not load the census", description: e.message, variant: "destructive" }); }
  }, [schoolId, toast]);
  useEffect(() => { load(); }, [load]);

  const act = useCallback(async (body: Record<string, unknown>, successMsg: string) => {
    if (!schoolId) return false;
    setBusy(true);
    try {
      await apiPost<Record<string, unknown>>(`/api/school/${schoolId}${OPS}`, body);
      toast({ title: successMsg });
      await load();
      return true;
    } catch (e: any) {
      toast({ title: "Action failed", description: e.message, variant: "destructive" });
      return false;
    } finally { setBusy(false); }
  }, [schoolId, load, toast]);

  const openFiled = useCallback(async (year: number) => {
    if (!schoolId) return;
    setBusy(true);
    try {
      const res = await apiGet<{ snapshot: CensusSnapshot }>(`/api/school/${schoolId}${OPS}?year=${year}`);
      setViewing(res.snapshot); setViewingYear(year);
    } catch (e: any) {
      toast({ title: `Could not open the ${year} return`, description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }, [schoolId, toast]);

  const exportEnrolment = useCallback((c: Census) => {
    downloadCsv(
      `emis-enrolment-${schoolId.slice(0, 6)}.csv`,
      ["Class", "Level", "Capacity", "Boys", "Girls", "Total", "Fill"],
      c.enrolment.byClass.map((r) => [r.classroom, r.gradeLevel, r.capacity, r.boys, r.girls, r.total, fillPct(r)]),
    );
  }, [schoolId]);

  const fileReturn = useCallback(async () => {
    const ok = await act({ action: "file", year: fileForm.year, note: fileForm.note }, `EMIS return filed for census year ${fileForm.year}`);
    if (ok) { setFileDialog(false); setFileForm({ year: new Date().getFullYear(), note: "" }); }
  }, [act, fileForm]);

  const unfile = useCallback(async (row: FiledRow) => {
    const ok = await act({ action: "unfile", id: row.id }, `Return for ${row.year} withdrawn from the archive`);
    if (ok && viewingYear === row.year) { setViewing(null); setViewingYear(null); }
  }, [act, viewingYear]);

  const live = data?.census;
  const shown = viewing ?? live ?? null;

  if (!data && !live) return <LoadingBlock label="Computing the census…" />;

  return (
    <div>
      <ModuleHeader
        title="EMIS Returns"
        description="The GES annual school census — one authoritative snapshot of enrolment, staffing, facilities, attendance and finance, computed from the school's own records and filed per census year."
        actions={
          viewing ? (
            <Button variant="outline" onClick={() => { setViewing(null); setViewingYear(null); }}><Undo2 /> Back to live figures</Button>
          ) : (
            <>
              <Button variant="outline" onClick={() => window.print()}><Printer /> Print census form</Button>
              <Button onClick={() => setFileDialog(true)} disabled={!live}><FileSpreadsheet /> File return</Button>
            </>
          )
        }
      />

      {viewing && (
        <Card className="mb-4 border-primary/40 bg-primary/5">
          <CardContent className="py-3 text-sm">
            <span className="font-semibold">Filed snapshot · census year {viewing.year}</span> — filed by {viewing.filedByName} on {fmtDate(viewing.filedAt)}
            {viewing.note && <span className="text-muted-foreground"> · “{viewing.note}”</span>}
          </CardContent>
        </Card>
      )}

      {!shown ? (
        <EmptyState icon={<ClipboardCheck className="size-5" />} title="Nothing to show yet" hint="The census computes from the school's records — open the live preview to see it." />
      ) : (
        <Tabs value={viewing ? "snapshot" : tab} onValueChange={setTab}>
          {!viewing && (
            <TabsList className="mb-4">
              <TabsTrigger value="preview">Census preview</TabsTrigger>
              <TabsTrigger value="archive">Filed returns ({data!.filed.length})</TabsTrigger>
            </TabsList>
          )}

          <TabsContent value="preview" className="mt-0"><CensusBody census={shown} onExport={exportEnrolment} live /></TabsContent>
          <TabsContent value="snapshot" className="mt-0"><CensusBody census={shown} onExport={exportEnrolment} /></TabsContent>

          <TabsContent value="archive" className="mt-0">
            {data!.filed.length === 0 ? (
              <EmptyState icon={<Archive className="size-5" />} title="No returns filed yet" hint="Use “File return” on the census preview to archive this year's figures." />
            ) : (
              <Card>
                <CardHeader><CardTitle className="text-base">Filed EMIS returns</CardTitle></CardHeader>
                <CardContent>
                  <Table>
                    <TableHeader><TableRow><TableHead>Census year</TableHead><TableHead>Filed by</TableHead><TableHead>Filed on</TableHead><TableHead>Note</TableHead><TableHead className="text-right">Actions</TableHead></TableRow></TableHeader>
                    <TableBody>
                      {data!.filed.map((row) => (
                        <TableRow key={row.id}>
                          <TableCell className="font-semibold">{row.year}</TableCell>
                          <TableCell>{row.filedByName}</TableCell>
                          <TableCell className="text-muted-foreground">{fmtDateTime(row.createdAt)}</TableCell>
                          <TableCell className="text-muted-foreground max-w-72 truncate">{row.note ?? "—"}</TableCell>
                          <TableCell className="text-right">
                            <div className="inline-flex gap-1">
                              <Button variant="outline" size="sm" onClick={() => openFiled(row.year)}><Eye /> View</Button>
                              <Button variant="ghost" size="sm" className="text-destructive hover:text-destructive" disabled={busy} onClick={() => unfile(row)}><Trash2 /> Unfile</Button>
                            </div>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </CardContent>
              </Card>
            )}
          </TabsContent>
        </Tabs>
      )}

      {/* File-return dialog */}
      <Dialog open={fileDialog} onOpenChange={setFileDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>File the EMIS return</DialogTitle>
            <DialogDescription>Freezes today's live census into an immutable snapshot for the chosen census year. The GES year runs with the academic year — file under the year the return is due.</DialogDescription>
          </DialogHeader>
          <div className="space-y-3">
            <div className="space-y-1.5">
              <Label htmlFor="emis-year">Census year</Label>
              <Input id="emis-year" type="number" min={2000} max={2100} value={fileForm.year} onChange={(e) => setFileForm({ ...fileForm, year: Number(e.target.value) })} />
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="emis-note">Note (optional)</Label>
              <Textarea id="emis-note" placeholder="e.g. Submitted to Ga East district office" value={fileForm.note} onChange={(e) => setFileForm({ ...fileForm, note: e.target.value })} />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setFileDialog(false)}>Cancel</Button>
            <Button onClick={fileReturn} disabled={busy || !Number.isFinite(fileForm.year)}><FileSpreadsheet /> File return</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

/** The census sheet itself — shared by the live preview and filed snapshots. */
function CensusBody({ census: c, onExport, live }: { census: Census; onExport: (c: Census) => void; live?: boolean }) {
  const e = c.enrolment, s = c.staffing, f = c.facilities, a = c.attendance, fin = c.finance, id = c.identity;
  return (
    <div className="space-y-4">
      {!live && (
        <div className="text-xs text-muted-foreground">
          Snapshot generated {fmtDateTime(c.generatedAt)}
          {c.calendar.academicYear && ` · academic year ${c.calendar.academicYear}`}
          {c.calendar.currentTerm && ` · ${c.calendar.currentTerm}`}
        </div>
      )}

      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard icon={<Users className="size-4" />} label="Learners enrolled" value={e.totals.total} sub={`${e.totals.boys} boys · ${e.totals.girls} girls · ${e.classes} classes`} tone="primary" />
        <StatCard icon={<GraduationCap className="size-4" />} label="Teaching staff" value={s.teaching} sub={`${s.total} total staff${s.studentTeacherRatio ? ` · STR ${s.studentTeacherRatio}` : ""}`} />
        <StatCard icon={<ClipboardCheck className="size-4" />} label="Attendance (term)" value={pct(a.rate)} sub={a.marked ? `${a.marked.toLocaleString()} marks · ${a.sessions} sessions` : "No sessions marked yet"} />
        <StatCard icon={<Landmark className="size-4" />} label="Fee collection" value={pct(fin.collectionRate)} sub={`${fmtCedi(fin.collectedCents, { compact: true })} of ${fmtCedi(fin.billedCents, { compact: true })} billed`} tone="gold" />
      </div>

      {/* Identity */}
      <Card>
        <CardHeader><CardTitle className="text-base">School identity</CardTitle></CardHeader>
        <CardContent>
          <div className="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <Field label="School" value={id.name} />
            <Field label="GES code" value={id.code} />
            <Field label="Motto" value={id.motto} />
            <Field label="Category" value={id.category} />
            <Field label="Curriculum" value={fmtCurricula(id.curriculum)} />
            <Field label="Established" value={id.establishedYear} />
            <Field label="Region" value={id.region} />
            <Field label="District" value={id.district} />
            <Field label="Town" value={id.town} />
            <Field label="GPS address" value={id.gpsAddress} />
            <Field label="Phone" value={id.phone} />
            <Field label="Email" value={id.email} />
          </div>
        </CardContent>
      </Card>

      {/* Enrolment by level + facilities */}
      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle className="text-base">Enrolment by level</CardTitle>
            <Badge variant="secondary">{e.totals.total} learners</Badge>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader><TableRow><TableHead>Level band</TableHead><TableHead className="text-right">Boys</TableHead><TableHead className="text-right">Girls</TableHead><TableHead className="text-right">Total</TableHead></TableRow></TableHeader>
              <TableBody>
                {e.bands.map((b) => (
                  <TableRow key={b.band}>
                    <TableCell className="font-medium">{b.band}</TableCell>
                    <TableCell className="text-right tabular-nums">{b.boys}</TableCell>
                    <TableCell className="text-right tabular-nums">{b.girls}</TableCell>
                    <TableCell className="text-right font-semibold tabular-nums">{b.total}</TableCell>
                  </TableRow>
                ))}
                <TableRow className="bg-secondary/50">
                  <TableCell className="font-bold">All bands</TableCell>
                  <TableCell className="text-right font-bold tabular-nums">{e.totals.boys}</TableCell>
                  <TableCell className="text-right font-bold tabular-nums">{e.totals.girls}</TableCell>
                  <TableCell className="text-right font-bold tabular-nums">{e.totals.total}</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle className="text-base">Facilities</CardTitle></CardHeader>
          <CardContent>
            <div className="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
              <Field label="Classrooms" value={`${f.classrooms}${f.classroomsWithCapacity ? ` (${f.totalCapacity} seats)` : ""}`} />
              <Field label="Subjects offered" value={f.subjectsOffered} />
              <Field label="Boarding houses" value={f.boardingHouses ? `${f.boardingHouses} · ${Math.max(f.boardingCapacity, f.boardingBeds)} beds` : "Day school"} />
              <Field label="Library" value={f.libraryTitles ? `${f.libraryTitles} titles · ${f.libraryCopies} copies` : "No library records"} />
              <Field label="Attendance marks (term)" value={a.marked ? `${a.marked.toLocaleString()} across ${a.sessions} sessions` : "None yet"} />
              <Field label="Invoices (current term)" value={fin.invoices} />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Enrolment by class */}
      <Card>
        <CardHeader className="flex-row items-center justify-between">
          <CardTitle className="text-base">Enrolment by class</CardTitle>
          <Button variant="outline" size="sm" onClick={() => onExport(c)}><Download /> CSV</Button>
        </CardHeader>
        <CardContent>
          {e.byClass.length === 0 ? (
            <EmptyState title="No classes with enrolment" hint="Classes appear once learners are enrolled or seats are set." />
          ) : (
            <Table>
              <TableHeader><TableRow><TableHead>Class</TableHead><TableHead>Level</TableHead><TableHead className="text-right">Capacity</TableHead><TableHead className="text-right">Boys</TableHead><TableHead className="text-right">Girls</TableHead><TableHead className="text-right">Total</TableHead><TableHead className="text-right">Fill</TableHead></TableRow></TableHeader>
              <TableBody>
                {e.byClass.map((r) => (
                  <TableRow key={r.classroom}>
                    <TableCell className="font-medium">{r.classroom}</TableCell>
                    <TableCell className="text-muted-foreground">{r.gradeLevel || "—"}</TableCell>
                    <TableCell className="text-right tabular-nums">{r.capacity || "—"}</TableCell>
                    <TableCell className="text-right tabular-nums">{r.boys}</TableCell>
                    <TableCell className="text-right tabular-nums">{r.girls}</TableCell>
                    <TableCell className="text-right font-semibold tabular-nums">{r.total}</TableCell>
                    <TableCell className="text-right tabular-nums text-muted-foreground">{fillPct(r)}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Staffing + WAEC */}
      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle className="text-base">Staffing</CardTitle>
            <Badge variant="secondary">STR {s.studentTeacherRatio ?? "—"}</Badge>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader><TableRow><TableHead>Category</TableHead><TableHead className="text-right">Male</TableHead><TableHead className="text-right">Female</TableHead><TableHead className="text-right">Total</TableHead></TableRow></TableHeader>
              <TableBody>
                {s.byCategory.map((cat) => (
                  <TableRow key={cat.category}>
                    <TableCell className="font-medium">{CAT_LABEL[cat.category] ?? cat.category}</TableCell>
                    <TableCell className="text-right tabular-nums">{cat.male}</TableCell>
                    <TableCell className="text-right tabular-nums">{cat.female}</TableCell>
                    <TableCell className="text-right font-semibold tabular-nums">{cat.total}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
            <div className="mt-3 text-sm text-muted-foreground">
              SSNIT enrolled: <span className="font-medium text-foreground">{s.ssnitEnrolled} of {s.total}</span>
              {s.qualifications.length > 0 && (
                <span> · Teacher qualifications: {s.qualifications.slice(0, 4).map(([q, n]) => `${q} (${n})`).join(", ")}{s.qualifications.length > 4 ? "…" : ""}</span>
              )}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle className="text-base">WAEC candidacy</CardTitle>
            <Badge variant="secondary">{c.waec.reduce((n, w) => n + w.candidates, 0)} candidates</Badge>
          </CardHeader>
          <CardContent>
            {c.waec.length === 0 ? (
              <EmptyState icon={<GraduationCap className="size-4" />} title="No WAEC centres registered" hint="BECE / WASSCE / NOV-DEC centres appear once candidacy is set up." />
            ) : (
              <Table>
                <TableHeader><TableRow><TableHead>Exam</TableHead><TableHead className="text-right">Year</TableHead><TableHead>Centre no.</TableHead><TableHead className="text-right">Candidates</TableHead></TableRow></TableHeader>
                <TableBody>
                  {c.waec.map((w, i) => (
                    <TableRow key={`${w.examType}-${w.examYear}-${i}`}>
                      <TableCell className="font-medium">{w.examType}</TableCell>
                      <TableCell className="text-right tabular-nums">{w.examYear}</TableCell>
                      <TableCell className="tabular-nums">{w.centreNumber}</TableCell>
                      <TableCell className="text-right font-semibold tabular-nums">{w.candidates}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

function Field({ label, value }: { label: string; value: string | number | null | undefined }) {
  return (
    <div className="flex items-baseline justify-between gap-3 border-b border-dashed pb-1">
      <span className="text-xs uppercase tracking-wide text-muted-foreground whitespace-nowrap">{label}</span>
      <span className="font-medium text-right truncate">{value == null || value === "" ? "—" : value}</span>
    </div>
  );
}

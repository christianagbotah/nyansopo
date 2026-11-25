"use client";
// ── WAEC Candidacy (wave 57 — BECE / WASSCE registration & mocks) ──
// Every Ghanaian JHS3 / SHS3 year runs through WAEC: the school registers
// candidates under its centre number, WAEC returns index numbers, subjects
// are entered per candidate, registration fees are billed, and mocks are
// sat (and re-sat) before the real thing. This module runs that pipeline:
//   • Candidacies — centre setup, bulk registration from a class roll,
//     auto index numbers (centre + padded serial → 10 chars), subject
//     entries (copy class offering or pick per candidate), registration
//     fee invoicing onto the current term, withdrawal / reinstatement,
//     candidate slip + entry-summary printouts
//   • Mock results — objective / theory / practical per subject, auto-graded
//     on the school's RawScoreGrade bands (A1..F9 + aggregate weight)
//   • Analysis — best-6 aggregate board, subject pass rates, mock-over-mock
//     progression, CSV export of the broadsheet
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard, FormGrid, FormField, FilterBar, downloadCsv } from "@/components/ui-helpers";
import { fmtCedi } from "@/lib/format";
import { useToast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  IdCard, Plus, Users, Trash2, Pencil, CopyPlus, Banknote, Printer, Download,
  BadgeCheck, Ban, Undo2, Search, GraduationCap, TrendingUp, ListChecks,
} from "lucide-react";

const OPS = "/ops/waec";

type SubjectEntry = { id: string; subjectName: string; subjectCode: string | null; isCore: boolean; sortOrder: number };
type MockRow = { id: string; subjectName: string; mockNo: number; objective: number | null; theory: number | null; practical: number | null; maxScore: number; total: number | null; grade: string | null; gradePoint: number | null };
type Candidacy = {
  id: string; serialNo: number; indexNumber: string | null; status: string; feeInvoiced: boolean; note: string | null;
  student: { id: string; firstName: string; lastName: string; otherNames: string | null; nyansapoIndex: string; gender: string };
  subjects: SubjectEntry[]; mocks: MockRow[];
};
type Centre = {
  id: string; examType: string; examYear: number; centreNumber: string; centreName: string;
  regFeeCents: number; status: string; note: string | null; candidacies: Candidacy[];
};
type ClassroomLite = { id: string; name: string; gradeLevel: string; _count: { enrollments: number } };
type SubjectLite = { id: string; name: string; code: string; isCore: boolean };
type WaecData = { centres: Centre[]; classrooms: ClassroomLite[]; subjects: SubjectLite[]; bands: { id: string; name: string; gradePoint: string; gradePointNumeric: number; markFrom: number; markUpto: number }[] };
type RosterRow = { id: string; name: string; gender: string; already: boolean };

const STATUS_STYLE: Record<string, string> = {
  REGISTERED: "bg-zinc-500", INDEXED: "bg-emerald-600", SUBMITTED: "bg-teal-600", WITHDRAWN: "bg-rose-600",
};
const EXAM_LABEL: Record<string, string> = { BECE: "BECE (JHS)", WASSCE: "WASSCE (SHS)", NOVDEC: "NOV-DEC (Private)" };
const nameOf = (s: Candidacy["student"]) => `${s.lastName} ${s.firstName}${s.otherNames ? " " + s.otherNames : ""}`.trim();

const emptyCentre = { id: "", examType: "BECE", examYear: new Date().getFullYear() + 1, centreNumber: "", centreName: "", regFee: 350, note: "" };

/** BECE aggregate: best six gradePoint values, core first (lower = better). */
function bestSix(c: Candidacy, mockNo?: number): number | null {
  const rows = mockNo
    ? c.mocks.filter((m) => m.mockNo === mockNo && m.gradePoint != null)
    : c.mocks.filter((m) => m.gradePoint != null && m.mockNo === Math.max(...c.mocks.map((x) => x.mockNo), 0));
  if (!rows.length) return null;
  const bySubject = new Map<string, number>();
  for (const r of rows) bySubject.set(r.subjectName, Math.min(bySubject.get(r.subjectName) ?? 99, r.gradePoint!));
  return [...bySubject.values()].sort((a, b) => a - b).slice(0, 6).reduce((s, v) => s + v, 0);
}

export function Waec() {
  const { currentSchoolId, navTarget } = useStore();
  const { toast } = useToast();
  const schoolId = currentSchoolId ?? "";
  const [data, setData] = useState<WaecData | null>(null);
  const [tab, setTab] = useState("candidates");
  const [busy, setBusy] = useState(false);
  const [centreId, setCentreId] = useState<string>("");
  const [centreDialog, setCentreDialog] = useState(false);
  const [form, setForm] = useState(emptyCentre);
  const [regOpen, setRegOpen] = useState(false);
  const [rosterClass, setRosterClass] = useState("");
  const [roster, setRoster] = useState<RosterRow[] | null>(null);
  const [picked, setPicked] = useState<Set<string>>(new Set());
  const [subjectFor, setSubjectFor] = useState<Candidacy | null>(null);
  const [subjectPick, setSubjectPick] = useState<{ subjectName: string; subjectCode: string | null; isCore: boolean }[]>([]);
  const [mockFor, setMockFor] = useState<Candidacy | null>(null);
  const [mockNo, setMockNo] = useState(1);
  const [mockRows, setMockRows] = useState<{ subjectName: string; objective: string; theory: string; practical: string; maxScore: string }[]>([]);
  const [slipFor, setSlipFor] = useState<Candidacy | null>(null);
  const [search, setSearch] = useState("");

  useEffect(() => { if (navTarget?.tab) setTab(navTarget.tab); }, [navTarget]);

  const load = useCallback(async () => {
    if (!schoolId) return;
    try { setData(await apiGet<WaecData>(`/api/school/${schoolId}${OPS}`)); }
    catch (e: any) { toast({ title: "Could not load WAEC data", description: e.message, variant: "destructive" }); }
  }, [schoolId, toast]);
  useEffect(() => { load(); }, [load]);

  const act = useCallback(async (body: Record<string, unknown>, successMsg: string, opts?: { soft?: boolean }) => {
    if (!schoolId) return null;
    setBusy(true);
    try {
      const res = await apiPost<Record<string, unknown>>(`/api/school/${schoolId}${OPS}`, body);
      if (!opts?.soft) toast({ title: successMsg });
      await load();
      return res;
    } catch (e: any) {
      toast({ title: "Action failed", description: e.message, variant: "destructive" });
      return null;
    } finally { setBusy(false); }
  }, [schoolId, load, toast]);

  const centre = useMemo(() => data?.centres.find((c) => c.id === centreId) ?? data?.centres[0] ?? null, [data, centreId]);
  useEffect(() => { if (centre && centreId !== centre.id) setCentreId(centre.id); }, [centre, centreId]);

  const candidates = useMemo(() => {
    const list = centre?.candidacies ?? [];
    const q = search.trim().toLowerCase();
    return q ? list.filter((c) => nameOf(c.student).toLowerCase().includes(q) || (c.indexNumber ?? "").includes(q) || String(c.serialNo) === q) : list;
  }, [centre, search]);

  /** mock-analysis computations (analysis tab) */
  const analysis = useMemo(() => {
    const cs = (centre?.candidacies ?? []).filter((c) => c.status !== "WITHDRAWN");
    const mockNos = [...new Set(cs.flatMap((c) => c.mocks.map((m) => m.mockNo)))].sort();
    const perCandidate = cs.map((c) => ({ c, aggregates: mockNos.map((n) => bestSix(c, n)) }));
    // subject pass rates per mock: gradePoint ≤ 6 counts as credit/pass band proxy via bands
    const subjectNames = [...new Set(cs.flatMap((c) => c.subjects.map((s) => s.subjectName)))].sort();
    const passRates = mockNos.map((n) => subjectNames.map((sn) => {
      const rows = cs.flatMap((c) => c.mocks.filter((m) => m.mockNo === n && m.subjectName === sn && m.gradePoint != null));
      const pass = rows.filter((r) => (r.gradePoint ?? 9) <= 6).length; // credit-level or better
      return { subjectName: sn, sat: rows.length, pass, rate: rows.length ? Math.round((pass / rows.length) * 100) : null };
    }));
    return { mockNos, perCandidate, subjectNames, passRates };
  }, [centre]);

  if (!data) return <LoadingBlock label="Loading WAEC candidacy…" />;

  const totalCandidates = data.centres.reduce((s, c) => s + c.candidacies.length, 0);
  const indexed = (centre?.candidacies ?? []).filter((c) => c.indexNumber).length;
  const billed = (centre?.candidacies ?? []).filter((c) => c.feeInvoiced).length;
  const active = (centre?.candidacies ?? []).filter((c) => c.status !== "WITHDRAWN");

  // ── candidate slip printout (A5) ────────────────────────────────
  function printSlip(c: Candidacy) {
    const centre2 = centre!;
    const w = window.open("", "_blank", "width=800,height=900");
    if (!w) return;
    w.document.write(`<!doctype html><html><head><title>WAEC candidate slip — ${nameOf(c.student)}</title>
      <style>
        body{font-family:Georgia,'Times New Roman',serif;padding:28px;color:#111}
        .head{display:flex;justify-content:space-between;border-bottom:3px double #111;padding-bottom:10px}
        .head h1{font-size:18px;margin:0}.head .motto{font-size:11px;color:#444}
        .type{margin:14px 0 4px;font-weight:bold;letter-spacing:.5px;font-size:15px}
        table{width:100%;border-collapse:collapse;margin-top:12px}
        td{padding:7px 6px;border-bottom:1px solid #ddd;font-size:13px}
        td.k{width:38%;font-weight:bold;background:#f6f6f4}
        .idx{font-family:monospace;font-size:20px;letter-spacing:2px;font-weight:bold}
        .foot{margin-top:26px;font-size:11px;color:#555;display:flex;justify-content:space-between}
        @media print{@page{size:A5;margin:12mm}}
      </style></head><body>
      <div class="head"><div><h1>${centre2.centreName}</h1><div class="motto">WAEC Examination Entry Slip · ${EXAM_LABEL[centre2.examType] ?? centre2.examType}</div></div><div style="text-align:right"><div>Centre №</div><div class="idx">${centre2.centreNumber}</div></div></div>
      <div class="type">${centre2.examType} ${centre2.examYear} — CANDIDATE ENTRY SLIP</div>
      <table>
        <tr><td class="k">Candidate name</td><td>${nameOf(c.student)}</td></tr>
        <tr><td class="k">Gender</td><td>${c.student.gender === "M" ? "Male" : "Female"}</td></tr>
        <tr><td class="k">Candidate serial</td><td>${c.serialNo}</td></tr>
        <tr><td class="k">Index number</td><td>${c.indexNumber ?? "— pending assignment —"}</td></tr>
        <tr><td class="k">Nyansapo index</td><td>${c.student.nyansapoIndex}</td></tr>
        <tr><td class="k">Subjects entered (${c.subjects.length})</td><td>${c.subjects.map((s) => `${s.subjectName}${s.isCore ? " (core)" : ""}`).join(" · ") || "— none entered —"}</td></tr>
        <tr><td class="k">Registration fee</td><td>${fmtCedi(centre2.regFeeCents)}${c.feeInvoiced ? " — billed" : " — not yet billed"}</td></tr>
        <tr><td class="k">Status</td><td>${c.status}</td></tr>
      </table>
      <div class="foot"><span>Exams officer signature: ______________________</span><span>Date: ${new Date().toLocaleDateString("en-GB")}</span></div>
      </body></html>`);
    w.document.close();
    setTimeout(() => w.print(), 350);
  }

  // ── class entry summary printout (A4 landscape) ────────────────
  function printSummary() {
    if (!centre) return;
    const w = window.open("", "_blank", "width=1100,height=800");
    if (!w) return;
    w.document.write(`<!doctype html><html><head><title>${centre.examType} ${centre.examYear} entry summary</title>
      <style>
        body{font-family:Arial,Helvetica,sans-serif;padding:20px;font-size:12px}
        h1{font-size:16px;margin:0}h2{font-size:12px;font-weight:normal;color:#444;margin:2px 0 14px}
        table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #999;padding:4px 5px;text-align:left}
        th{background:#eee}
        @media print{@page{size:A4 landscape;margin:10mm}}
      </style></head><body>
      <h1>${centre.centreName} — ${centre.examType} ${centre.examYear} candidate entry summary</h1>
      <h2>Centre № ${centre.centreNumber} · ${centre.candidacies.length} candidate(s) · generated ${new Date().toLocaleString("en-GB")}</h2>
      <table><tr><th>#</th><th>Index number</th><th>Candidate name</th><th>Gender</th><th>Subjects</th><th>Fee billed</th><th>Status</th></tr>
      ${centre.candidacies.map((c, i) => `<tr><td>${i + 1}</td><td style="font-family:monospace">${c.indexNumber ?? "—"}</td><td>${nameOf(c.student)}</td><td>${c.student.gender}</td><td>${c.subjects.map((s) => s.subjectName).join(", ") || "—"}</td><td>${c.feeInvoiced ? "Yes" : "No"}</td><td>${c.status}</td></tr>`).join("")}
      </table></body></html>`);
    w.document.close();
    setTimeout(() => w.print(), 350);
  }

  function openMock(c: Candidacy) {
    setMockFor(c);
    setMockNo(1);
    const base = c.subjects.length ? c.subjects : [];
    setMockRows(base.map((s) => {
      const prev = c.mocks.find((m) => m.subjectName === s.subjectName && m.mockNo === 1);
      return { subjectName: s.subjectName, objective: prev?.objective?.toString() ?? "", theory: prev?.theory?.toString() ?? "", practical: prev?.practical?.toString() ?? "", maxScore: prev?.maxScore?.toString() ?? "100" };
    }));
  }

  function switchMockNo(n: number) {
    if (!mockFor) return;
    setMockNo(n);
    const base = mockFor.subjects.length ? mockFor.subjects : [];
    setMockRows(base.map((s) => {
      const prev = mockFor.mocks.find((m) => m.subjectName === s.subjectName && m.mockNo === n);
      return { subjectName: s.subjectName, objective: prev?.objective?.toString() ?? "", theory: prev?.theory?.toString() ?? "", practical: prev?.practical?.toString() ?? "", maxScore: prev?.maxScore?.toString() ?? "100" };
    }));
  }

  return (
    <div>
      <ModuleHeader
        title="WAEC Candidacy"
        description="Register BECE / WASSCE candidates under your WAEC centre, assign index numbers, enter subjects, bill registration fees and run graded mock exams — the Ghanaian exam-season pipeline."
        actions={
          <div className="flex items-center gap-2">
            <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={() => { setForm({ ...emptyCentre, centreName: form.centreName }); setCentreDialog(true); }}>
              <Plus className="size-4" /> Exam centre
            </Button>
            {centre && <Button size="sm" className="h-8 gap-1.5" onClick={() => setRegOpen(true)}><Users className="size-4" /> Register candidates</Button>}
          </div>
        }
      />

      {/* centre strip */}
      <div className="flex flex-wrap items-center gap-2 mb-4">
        {data.centres.length > 0 ? (
          <>
            <Select value={centre?.id ?? ""} onValueChange={setCentreId}>
              <SelectTrigger className="w-[300px] h-9 bg-background"><SelectValue placeholder="Choose exam centre" /></SelectTrigger>
              <SelectContent>
                {data.centres.map((c) => (
                  <SelectItem key={c.id} value={c.id}>{c.examType} {c.examYear} — {c.centreName} ({c.centreNumber})</SelectItem>
                ))}
              </SelectContent>
            </Select>
            {centre && (
              <>
                <Badge variant="outline" className="font-mono">Centre № {centre.centreNumber}</Badge>
                <Badge className={centre.status === "OPEN" ? "bg-emerald-600" : "bg-zinc-500"}>{centre.status === "OPEN" ? "Open for entries" : "Closed"}</Badge>
                <Badge variant="outline">{fmtCedi(centre.regFeeCents)} / candidate</Badge>
                <Button size="sm" variant="ghost" className="h-8 gap-1.5" onClick={() => { setForm({ id: centre.id, examType: centre.examType, examYear: centre.examYear, centreNumber: centre.centreNumber, centreName: centre.centreName, regFee: centre.regFeeCents / 100, note: centre.note ?? "" }); setCentreDialog(true); }}><Pencil className="size-3.5" /> Edit</Button>
              </>
            )}
          </>
        ) : null}
      </div>

      {/* stat cards */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <StatCard icon={<IdCard className="size-4" />} label="Candidates" value={centre ? centre.candidacies.length : totalCandidates} sub={centre ? `${active.length} active · ${centre.candidacies.length - active.length} withdrawn` : `${data.centres.length} centres`} tone="primary" />
        <StatCard icon={<BadgeCheck className="size-4" />} label="Indexed" value={centre ? `${indexed}/${centre.candidacies.length || 0}` : "—"} sub="index numbers assigned" />
        <StatCard icon={<Banknote className="size-4" />} label="Fees billed" value={centre ? `${billed}/${centre.candidacies.length || 0}` : "—"} sub={centre ? `${fmtCedi(centre.regFeeCents * billed)} invoiced` : ""} tone="gold" />
        <StatCard icon={<ListChecks className="size-4" />} label="Mock grades" value={centre ? centre.candidacies.reduce((s, c) => s + c.mocks.filter((m) => m.grade).length, 0) : 0} sub="auto-graded entries" />
      </div>

      {!data.centres.length ? (
        <Card><CardContent className="py-14">
          <EmptyState icon={<IdCard className="size-10" />} title="No WAEC exam centre yet" hint="Create your BECE / WASSCE centre — WAEC's centre number, the registration fee and the exam year — then register the candidate class." />
        </CardContent></Card>
      ) : (
        <Tabs value={tab} onValueChange={setTab}>
          <TabsList className="mb-4">
            <TabsTrigger value="candidates">Candidacies</TabsTrigger>
            <TabsTrigger value="mocks">Mock results</TabsTrigger>
            <TabsTrigger value="analysis">Analysis</TabsTrigger>
          </TabsList>

          {/* ── CANDIDACIES ─────────────────────────────────────── */}
          <TabsContent value="candidates" className="space-y-4">
            {centre && (
              <div className="flex flex-wrap items-center gap-2">
                <Button size="sm" variant="outline" className="h-8 gap-1.5" disabled={!active.some((c) => !c.indexNumber) || busy} onClick={() => act({ action: "assign-indexes", centreId: centre.id }, "Index numbers assigned")}>
                  <BadgeCheck className="size-4" /> Assign index numbers
                </Button>
                <Button size="sm" variant="outline" className="h-8 gap-1.5" disabled={billed >= centre.candidacies.length || busy || !centre.regFeeCents} onClick={() => act({ action: "invoice-fees", centreId: centre.id }, "Registration fees billed")}>
                  <Banknote className="size-4" /> Bill registration fees
                </Button>
                <Button size="sm" variant="outline" className="h-8 gap-1.5" disabled={!centre.candidacies.length} onClick={printSummary}><Printer className="size-4" /> Entry summary</Button>
                <div className="relative ml-auto">
                  <Search className="size-4 absolute left-2.5 top-2.5 text-muted-foreground" />
                  <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search name / index / serial" className="pl-8 w-64 h-9" />
                </div>
              </div>
            )}
            <Card>
              <CardContent className="p-0">
                {candidates.length ? (
                  <div className="max-h-[520px] overflow-y-auto">
                    <Table>
                      <TableHeader><TableRow>
                        <TableHead className="w-12">#</TableHead><TableHead>Candidate</TableHead><TableHead>Index number</TableHead>
                        <TableHead className="hidden md:table-cell">Subjects</TableHead><TableHead>Fee</TableHead><TableHead>Status</TableHead><TableHead className="text-right">Actions</TableHead>
                      </TableRow></TableHeader>
                      <TableBody>
                        {candidates.map((c) => (
                          <TableRow key={c.id} className={c.status === "WITHDRAWN" ? "opacity-50" : ""}>
                            <TableCell className="text-muted-foreground">{c.serialNo}</TableCell>
                            <TableCell>
                              <div className="font-medium">{nameOf(c.student)}</div>
                              <div className="text-xs text-muted-foreground">{c.student.nyansapoIndex} · {c.student.gender === "M" ? "M" : "F"}</div>
                            </TableCell>
                            <TableCell>
                              {c.indexNumber ? <span className="font-mono font-medium">{c.indexNumber}</span> : <span className="text-xs text-muted-foreground">—</span>}
                            </TableCell>
                            <TableCell className="hidden md:table-cell">
                              {c.subjects.length ? <span className="text-xs">{c.subjects.length} entered</span> : <span className="text-xs text-amber-600 dark:text-amber-400">none yet</span>}
                            </TableCell>
                            <TableCell>{c.feeInvoiced ? <Badge className="bg-emerald-600 text-[11px]">billed</Badge> : <Badge variant="outline" className="text-[11px]">unbilled</Badge>}</TableCell>
                            <TableCell><span className={cn("inline-block size-2 rounded-full mr-1.5", STATUS_STYLE[c.status] ?? "bg-zinc-400")} />{c.status}</TableCell>
                            <TableCell className="text-right">
                              <div className="flex justify-end gap-1">
                                <Button size="sm" variant="ghost" className="h-7 px-2" title="Subject entries" onClick={() => { setSubjectFor(c); setSubjectPick(c.subjects.map((s) => ({ subjectName: s.subjectName, subjectCode: s.subjectCode, isCore: s.isCore }))); }}>Subjects</Button>
                                <Button size="sm" variant="ghost" className="h-7 px-2" title="Candidate slip" onClick={() => printSlip(c)}><Printer className="size-3.5" /></Button>
                                {c.status === "WITHDRAWN" ? (
                                  <Button size="sm" variant="ghost" className="h-7 px-2" title="Reinstate" disabled={busy} onClick={() => act({ action: "reinstate", id: c.id }, "Candidacy reinstated")}><Undo2 className="size-3.5" /></Button>
                                ) : (
                                  <Button size="sm" variant="ghost" className="h-7 px-2 text-rose-600" title="Withdraw" disabled={busy} onClick={() => act({ action: "withdraw", id: c.id }, "Candidacy withdrawn")}><Ban className="size-3.5" /></Button>
                                )}
                              </div>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                ) : (
                  <div className="py-12"><EmptyState icon={<Users className="size-8" />} title={search ? "No candidates match the search" : "No candidates registered yet"} hint={search ? "Try a different name or index number." : "Use “Register candidates” to pull the JHS3 / SHS3 class roll into this centre."} /></div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* ── MOCK RESULTS ────────────────────────────────────── */}
          <TabsContent value="mocks" className="space-y-4">
            <Card>
              <CardHeader className="pb-3"><CardTitle className="text-sm">Mock examination entry</CardTitle></CardHeader>
              <CardContent className="text-sm text-muted-foreground">
                Pick a candidate to enter mock paper scores — objective, theory and optional practical per subject. Totals auto-grade on the school's raw-score bands (Settings → Grading scale) and feed the best-6 aggregate board.
                {data.bands.length > 0 && <span className="ml-1">Bands: {data.bands.map((b) => `${b.gradePoint} (${b.markFrom}–${b.markUpto})`).join(" · ")}</span>}
              </CardContent>
            </Card>
            <Card>
              <CardContent className="p-0">
                {active.length ? (
                  <div className="max-h-[520px] overflow-y-auto">
                    <Table>
                      <TableHeader><TableRow>
                        <TableHead>Candidate</TableHead><TableHead>Index</TableHead>
                        <TableHead>Mock 1</TableHead><TableHead>Mock 2</TableHead><TableHead>Mock 3</TableHead><TableHead>Latest aggregate</TableHead><TableHead className="text-right">Actions</TableHead>
                      </TableRow></TableHeader>
                      <TableBody>
                        {active.map((c) => {
                          const counts = [1, 2, 3].map((n) => c.mocks.filter((m) => m.mockNo === n && m.total != null).length);
                          const agg = bestSix(c);
                          return (
                            <TableRow key={c.id}>
                              <TableCell className="font-medium">{nameOf(c.student)}</TableCell>
                              <TableCell className="font-mono text-xs">{c.indexNumber ?? "—"}</TableCell>
                              {[0, 1, 2].map((i) => (
                                <TableCell key={i}>{counts[i] ? <Badge variant="outline">{counts[i]} subj.</Badge> : <span className="text-xs text-muted-foreground">—</span>}</TableCell>
                              ))}
                              <TableCell>{agg != null ? <Badge className={agg <= 12 ? "bg-emerald-600" : agg <= 24 ? "bg-amber-600" : "bg-rose-600"}>{agg}</Badge> : <span className="text-xs text-muted-foreground">—</span>}</TableCell>
                              <TableCell className="text-right"><Button size="sm" variant="outline" className="h-7" onClick={() => openMock(c)}>Enter scores</Button></TableCell>
                            </TableRow>
                          );
                        })}
                      </TableBody>
                    </Table>
                  </div>
                ) : (
                  <div className="py-12"><EmptyState icon={<GraduationCap className="size-8" />} title="No active candidates to examine" hint="Register candidates first — mocks are entered per candidate." /></div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* ── ANALYSIS ────────────────────────────────────────── */}
          <TabsContent value="analysis" className="space-y-4">
            {analysis.mockNos.length ? (
              <>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                  {analysis.mockNos.map((n) => {
                    const aggs = analysis.perCandidate.map((p) => p.aggregates[analysis.mockNos.indexOf(n)]).filter((a): a is number => a != null);
                    const avg = aggs.length ? (aggs.reduce((s, a) => s + a, 0) / aggs.length) : null;
                    const best = aggs.length ? Math.min(...aggs) : null;
                    return (
                      <Card key={n}>
                        <CardHeader className="pb-2"><CardTitle className="text-sm flex items-center gap-2"><TrendingUp className="size-4 text-emerald-600" /> Mock {n} aggregate board</CardTitle></CardHeader>
                        <CardContent className="grid grid-cols-2 gap-3 text-sm">
                          <div><div className="text-2xl font-bold">{avg != null ? avg.toFixed(1) : "—"}</div><div className="text-xs text-muted-foreground">class average (best-6)</div></div>
                          <div><div className="text-2xl font-bold text-emerald-600">{best ?? "—"}</div><div className="text-xs text-muted-foreground">best aggregate</div></div>
                        </CardContent>
                      </Card>
                    );
                  })}
                </div>

                {/* subject pass rates per mock */}
                {analysis.mockNos.map((n) => (
                  <Card key={n}>
                    <CardHeader className="pb-2"><CardTitle className="text-sm">Mock {n} — subject credit rates (grade ≤ credit band)</CardTitle></CardHeader>
                    <CardContent className="p-0">
                      <Table>
                        <TableHeader><TableRow><TableHead>Subject</TableHead><TableHead>Sat</TableHead><TableHead>Credit+</TableHead><TableHead>Rate</TableHead><TableHead className="w-1/3">Spread</TableHead></TableRow></TableHeader>
                        <TableBody>
                          {analysis.passRates[analysis.mockNos.indexOf(n)].map((r) => (
                            <TableRow key={r.subjectName}>
                              <TableCell className="font-medium">{r.subjectName}</TableCell>
                              <TableCell>{r.sat}</TableCell>
                              <TableCell>{r.pass}</TableCell>
                              <TableCell>{r.rate != null ? `${r.rate}%` : "—"}</TableCell>
                              <TableCell><div className="h-2 rounded-full bg-muted overflow-hidden"><div className={cn("h-full rounded-full", (r.rate ?? 0) >= 70 ? "bg-emerald-600" : (r.rate ?? 0) >= 40 ? "bg-amber-600" : "bg-rose-600")} style={{ width: `${r.rate ?? 0}%` }} /></div></TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </CardContent>
                  </Card>
                ))}

                {/* broadsheet + CSV */}
                <Card>
                  <CardHeader className="pb-2 flex-row items-center justify-between">
                    <CardTitle className="text-sm">Candidate broadsheet</CardTitle>
                    <Button size="sm" variant="outline" className="h-7 gap-1.5" onClick={() => downloadCsv(
                      `waec-${centre?.examType ?? "exam"}-${centre?.examYear ?? ""}-broadsheet.csv`,
                      ["Serial", "Index", "Candidate", ...analysis.mockNos.flatMap((n) => [`Mock ${n} aggregate`, ...analysis.subjectNames.map((s) => `M${n} ${s}`)])],
                      analysis.perCandidate.map((p) => [
                        p.c.serialNo, p.c.indexNumber ?? "", nameOf(p.c.student),
                        ...analysis.mockNos.flatMap((n, ni) => [
                          p.aggregates[ni] ?? "",
                          ...analysis.subjectNames.map((sn) => {
                            const row = p.c.mocks.find((m) => m.mockNo === n && m.subjectName === sn);
                            return row?.grade ?? "";
                          }),
                        ]),
                      ]),
                    )}><Download className="size-3.5" /> CSV</Button>
                  </CardHeader>
                  <CardContent className="p-0">
                    <div className="max-h-[420px] overflow-auto">
                      <Table>
                        <TableHeader><TableRow>
                          <TableHead className="sticky left-0 bg-background">#</TableHead><TableHead className="sticky left-12 bg-background">Candidate</TableHead>
                          {analysis.mockNos.map((n) => <TableHead key={n}>Mock {n}</TableHead>)}
                        </TableRow></TableHeader>
                        <TableBody>
                          {analysis.perCandidate.map((p) => (
                            <TableRow key={p.c.id}>
                              <TableCell className="sticky left-0 bg-background text-muted-foreground">{p.c.serialNo}</TableCell>
                              <TableCell className="sticky left-12 bg-background font-medium">{nameOf(p.c.student)}</TableCell>
                              {p.aggregates.map((a, i) => (
                                <TableCell key={i}>{a != null ? <Badge className={a <= 12 ? "bg-emerald-600" : a <= 24 ? "bg-amber-600" : "bg-rose-600"}>{a}</Badge> : <span className="text-xs text-muted-foreground">—</span>}</TableCell>
                              ))}
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </div>
                  </CardContent>
                </Card>
              </>
            ) : (
              <Card><CardContent className="py-12"><EmptyState icon={<TrendingUp className="size-8" />} title="No mock results yet" hint="Enter scores on the Mock results tab — aggregates and subject rates appear here." /></CardContent></Card>
            )}
          </TabsContent>
        </Tabs>
      )}

      {/* ── centre dialog ─────────────────────────────────────── */}
      <Dialog open={centreDialog} onOpenChange={setCentreDialog}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader><DialogTitle>{form.id ? "Edit exam centre" : "New WAEC exam centre"}</DialogTitle>
            <DialogDescription>The centre WAEC assigned to your school — index numbers are minted from it.</DialogDescription></DialogHeader>
          <FormGrid cols={2}>
            <FormField label="Exam type" required>
              <Select value={form.examType} onValueChange={(v) => setForm({ ...form, examType: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{Object.entries(EXAM_LABEL).map(([k, v]) => <SelectItem key={k} value={k}>{v}</SelectItem>)}</SelectContent>
              </Select>
            </FormField>
            <FormField label="Exam year" required><Input type="number" value={form.examYear} onChange={(e) => setForm({ ...form, examYear: Number(e.target.value) })} /></FormField>
            <FormField label="Centre number (digits)" required hint="From your WAEC registration"><Input value={form.centreNumber} onChange={(e) => setForm({ ...form, centreNumber: e.target.value.replace(/\D/g, "") })} placeholder="5021406" inputMode="numeric" /></FormField>
            <FormField label="Centre name" required><Input value={form.centreName} onChange={(e) => setForm({ ...form, centreName: e.target.value })} placeholder="Golden Excellence Academy Basic School" /></FormField>
            <FormField label="Registration fee (GH₵)" hint="Billed per candidate on demand"><Input type="number" min={0} step="0.01" value={form.regFee} onChange={(e) => setForm({ ...form, regFee: Number(e.target.value) })} /></FormField>
            <FormField label="Note" span><Input value={form.note} onChange={(e) => setForm({ ...form, note: e.target.value })} placeholder="e.g. entries close 30 November" /></FormField>
          </FormGrid>
          <DialogFooter>
            {form.id && (
              <Button variant="ghost" className="text-rose-600" disabled={busy} onClick={async () => { const ok = await act({ action: "delete-centre", id: form.id }, "Centre deleted"); if (ok) setCentreDialog(false); }}>
                <Trash2 className="size-4 mr-1.5" /> Delete centre
              </Button>
            )}
            <Button variant="outline" onClick={() => setCentreDialog(false)}>Cancel</Button>
            <Button disabled={busy || !form.centreNumber || !form.centreName} onClick={async () => {
              const ok = await act({ action: "save-centre", ...form, regFeeCents: Math.round(form.regFee * 100) }, "Exam centre saved");
              if (ok) setCentreDialog(false);
            }}>{form.id ? "Save changes" : "Create centre"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── bulk register dialog ──────────────────────────────── */}
      <Dialog open={regOpen} onOpenChange={setRegOpen}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader><DialogTitle>Register candidates — {centre?.examType} {centre?.examYear}</DialogTitle>
            <DialogDescription>Pick the exam class; the roll loads below. Serials are assigned in order, skipping learners already on the roll.</DialogDescription></DialogHeader>
          <div className="space-y-3">
            <div className="flex items-center gap-2">
              <Select value={rosterClass} onValueChange={setRosterClass}>
                <SelectTrigger className="flex-1"><SelectValue placeholder="Choose class (e.g. JHS 3)" /></SelectTrigger>
                <SelectContent>
                  {data.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name} · {c.gradeLevel} ({c._count.enrollments} learners)</SelectItem>)}
                </SelectContent>
              </Select>
              <Button variant="outline" className="h-9" disabled={!rosterClass || busy} onClick={async () => {
                setRoster(null);
                try {
                  const res = await apiGet<{ roster: RosterRow[] }>(`/api/school/${schoolId}${OPS}?roster=${rosterClass}&centreId=${centre!.id}`);
                  setRoster(res.roster);
                  setPicked(new Set(res.roster.filter((r) => !r.already).map((r) => r.id)));
                } catch (e: any) { toast({ title: "Could not load class roll", description: e.message, variant: "destructive" }); }
              }}>Load roll</Button>
            </div>
            {roster && (
              <div className="border rounded-lg max-h-72 overflow-y-auto">
                {roster.map((r) => (
                  <label key={r.id} className={cn("flex items-center gap-3 px-3 py-2 border-b last:border-0 cursor-pointer", r.already && "opacity-50")}>
                    <Checkbox checked={picked.has(r.id)} disabled={r.already} onCheckedChange={(v) => setPicked((prev) => { const n = new Set(prev); if (v) n.add(r.id); else n.delete(r.id); return n; })} />
                    <span className="text-sm flex-1">{r.name}</span>
                    <span className="text-xs text-muted-foreground">{r.gender === "M" ? "Male" : "Female"}</span>
                    {r.already && <Badge variant="outline" className="text-[10px]">on roll</Badge>}
                  </label>
                ))}
              </div>
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setRegOpen(false)}>Cancel</Button>
            <Button disabled={!picked.size || busy} onClick={async () => {
              const res = await act({ action: "register-bulk", centreId: centre!.id, studentIds: [...picked] }, "Candidates registered", { soft: true });
              if (res) {
                toast({ title: `Registered ${res.created} candidate(s)${res.skipped ? ` · ${res.skipped} already on roll` : ""}` });
                setRegOpen(false);
                setRoster(null);
                setRosterClass("");
              }
            }}>Register {picked.size || ""} candidate{picked.size === 1 ? "" : "s"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── subject entries dialog ────────────────────────────── */}
      <Dialog open={!!subjectFor} onOpenChange={(v) => !v && setSubjectFor(null)}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader><DialogTitle>Subject entries — {subjectFor ? nameOf(subjectFor.student) : ""}</DialogTitle>
            <DialogDescription>What this candidate sits at {centre?.examType} {centre?.examYear}. Core subjects drive the best-6 aggregate.</DialogDescription></DialogHeader>
          <div className="space-y-2">
            <div className="flex flex-wrap gap-2">
              <Button size="sm" variant="outline" className="h-8 gap-1.5" disabled={busy} onClick={async () => {
                const res = await act({ action: "copy-class-offering", id: subjectFor!.id }, "Class offering copied", { soft: true });
                if (res && Array.isArray(res.subjects)) {
                  setSubjectPick((res.subjects as { subjectName: string; subjectCode: string | null; isCore: boolean }[]).map((s) => ({ subjectName: s.subjectName, subjectCode: s.subjectCode, isCore: s.isCore })));
                  toast({ title: `Copied ${res.count} subject(s) from the class offering` });
                }
              }}><CopyPlus className="size-3.5" /> Copy class offering</Button>
              <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={() => setSubjectPick([...subjectPick, { subjectName: "", subjectCode: null, isCore: false }])}><Plus className="size-3.5" /> Add row</Button>
            </div>
            <div className="border rounded-lg max-h-80 overflow-y-auto divide-y">
              {subjectPick.length ? subjectPick.map((s, i) => (
                <div key={i} className="flex items-center gap-2 px-3 py-2">
                  <Select value={s.subjectName} onValueChange={(v) => {
                    const subj = data.subjects.find((x) => x.name === v);
                    setSubjectPick(subjectPick.map((row, j) => j === i ? { subjectName: v, subjectCode: subj?.code ?? row.subjectCode, isCore: subj?.isCore ?? row.isCore } : row));
                  }}>
                    <SelectTrigger className="flex-1 h-8"><SelectValue placeholder="Choose subject" /></SelectTrigger>
                    <SelectContent>{data.subjects.map((x) => <SelectItem key={x.id} value={x.name}>{x.name}</SelectItem>)}</SelectContent>
                  </Select>
                  <label className="flex items-center gap-1.5 text-xs whitespace-nowrap cursor-pointer">
                    <Checkbox checked={s.isCore} onCheckedChange={(v) => setSubjectPick(subjectPick.map((row, j) => j === i ? { ...row, isCore: Boolean(v) } : row))} /> core
                  </label>
                  <Button size="sm" variant="ghost" className="h-8 px-2 text-rose-600" onClick={() => setSubjectPick(subjectPick.filter((_, j) => j !== i))}><Trash2 className="size-3.5" /></Button>
                </div>
              )) : <div className="px-3 py-6 text-sm text-muted-foreground text-center">No subjects yet — copy the class offering or add rows.</div>}
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setSubjectFor(null)}>Cancel</Button>
            <Button disabled={busy || !subjectPick.filter((s) => s.subjectName).length} onClick={async () => {
              const res = await act({ action: "save-subjects", id: subjectFor!.id, subjects: subjectPick.filter((s) => s.subjectName) }, "Subject entries saved", { soft: true });
              if (res) { toast({ title: `${res.count} subject entry(ies) saved` }); setSubjectFor(null); }
            }}>Save entries</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── mock score dialog ─────────────────────────────────── */}
      <Dialog open={!!mockFor} onOpenChange={(v) => !v && setMockFor(null)}>
        <DialogContent className="sm:max-w-2xl">
          <DialogHeader><DialogTitle>Mock scores — {mockFor ? nameOf(mockFor.student) : ""}</DialogTitle>
            <DialogDescription>Enter paper scores per subject; totals grade automatically on the school's raw-score bands.</DialogDescription></DialogHeader>
          <div className="flex items-center gap-2 mb-2">
            {[1, 2, 3].map((n) => (
              <Button key={n} size="sm" variant={mockNo === n ? "default" : "outline"} className="h-8" onClick={() => switchMockNo(n)}>Mock {n}</Button>
            ))}
            <div className="ml-auto">
              <Button size="sm" variant="ghost" className="h-8 text-rose-600" disabled={busy} onClick={async () => { await act({ action: "delete-mock", candidacyId: mockFor!.id, mockNo }, `Mock ${mockNo} cleared`); switchMockNo(mockNo); }}>Clear mock {mockNo}</Button>
            </div>
          </div>
          <div className="border rounded-lg max-h-80 overflow-y-auto">
            {mockRows.length ? (
              <Table>
                <TableHeader><TableRow><TableHead>Subject</TableHead><TableHead className="w-24">Objective</TableHead><TableHead className="w-24">Theory</TableHead><TableHead className="w-24">Practical</TableHead><TableHead className="w-24">Max</TableHead></TableRow></TableHeader>
                <TableBody>
                  {mockRows.map((r, i) => (
                    <TableRow key={i}>
                      <TableCell className="font-medium">{r.subjectName}</TableCell>
                      {(["objective", "theory", "practical", "maxScore"] as const).map((k) => (
                        <TableCell key={k}><Input type="number" min={0} value={r[k]} placeholder="—" className="h-8" onChange={(e) => setMockRows(mockRows.map((row, j) => j === i ? { ...row, [k]: e.target.value } : row))} /></TableCell>
                      ))}
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            ) : <div className="px-3 py-6 text-sm text-muted-foreground text-center">No subject entries for this candidate — add subjects on the Candidacies tab first.</div>}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setMockFor(null)}>Cancel</Button>
            <Button disabled={busy || !mockRows.length} onClick={async () => {
              const res = await act({ action: "save-mock", candidacyId: mockFor!.id, mockNo, rows: mockRows }, "Mock scores saved", { soft: true });
              if (res) { toast({ title: `Mock ${mockNo}: ${res.count} subject(s) graded & saved` }); setMockFor(null); }
            }}>Save mock {mockNo}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

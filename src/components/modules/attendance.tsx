"use client";
import { useEffect, useMemo, useRef, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost, apiPostQueueable } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtDateShort, initials, avatarColor, fmtCedi } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import { Area, AreaChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";
import { CalendarCheck, ChevronLeft, ChevronRight, CloudOff, Save, Users, ScanLine, LogIn, LogOut, Ban, CircleCheck, CircleAlert, OctagonX, Undo2, BarChart3, CalendarDays, CheckCheck, UserX, Download, Printer, ChevronDown, ChevronUp, Search } from "lucide-react";

type AttData = {
  classrooms: { id: string; name: string; grade: string; stage?: string }[];
  session: { id: string; takenByName: string } | null;
  students: { id: string; name: string; rollNo: number | null; status: string | null; markedOffline?: boolean; excusedReason?: string | null }[];
  termId: string;
};

/* iEduPro attendance statuses — SICK kept for backwards compat (legacy sick-bay
   marks); SICK_HOME / SICK_CLINIC / BUSY / EXCUSED are the CI3 register set. */
const STATUSES = [
  { key: "PRESENT", label: "Present", cls: "bg-emerald-600 text-white", selectCls: "border-emerald-300 bg-emerald-50 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200", letter: "P" },
  { key: "LATE", label: "Late", cls: "bg-amber-500 text-white", selectCls: "border-amber-300 bg-amber-50 text-amber-900 dark:bg-amber-950 dark:text-amber-200", letter: "L" },
  { key: "ABSENT", label: "Absent", cls: "bg-red-600 text-white", selectCls: "border-red-300 bg-red-50 text-red-900 dark:bg-red-950 dark:text-red-200", letter: "A" },
  { key: "SICK", label: "Sick", cls: "bg-orange-400 text-white", selectCls: "border-orange-300 bg-orange-50 text-orange-900 dark:bg-orange-950 dark:text-orange-200", letter: "S" },
  { key: "SICK_HOME", label: "Sick @ home", cls: "bg-orange-600 text-white", selectCls: "border-orange-400 bg-orange-100 text-orange-900 dark:bg-orange-950 dark:text-orange-200", letter: "SH" },
  { key: "SICK_CLINIC", label: "Sick @ clinic", cls: "bg-orange-800 text-white", selectCls: "border-orange-500 bg-orange-200 text-orange-900 dark:bg-orange-900 dark:text-orange-100", letter: "SC" },
  { key: "BUSY", label: "Busy", cls: "bg-violet-500 text-white", selectCls: "border-violet-300 bg-violet-50 text-violet-900 dark:bg-violet-950 dark:text-violet-200", letter: "B" },
  { key: "EXCUSED", label: "Excused", cls: "bg-gray-400 text-white", selectCls: "border-pink-300 bg-pink-50 text-pink-900 dark:bg-pink-950 dark:text-pink-200", letter: "E" },
];
const STATUS_BY_KEY: Record<string, (typeof STATUSES)[number]> = Object.fromEntries(STATUSES.map((s) => [s.key, s]));

const OPS = "/ops/attendance"; // attendance ops endpoint (register save + analytics + monthly)

export function Attendance() {
  const { currentSchoolId, online, navTarget } = useStore();
  const [tab, setTab] = useState("register");
  const [data, setData] = useState<AttData | null>(null);
  const [classId, setClassId] = useState<string | null>(null);
  const [date, setDate] = useState(() => new Date().toISOString().slice(0, 10));
  const [marks, setMarks] = useState<Record<string, string>>({});
  const [reasons, setReasons] = useState<Record<string, string>>({});
  const [busy, setBusy] = useState(false);
  /* CI3 mark_attendance partial update: on an already-saved day, each learner
     card carries an "update" checkbox — uncheck to leave specific learners out */
  const [updatePicks, setUpdatePicks] = useState<Record<string, boolean>>({});
  const [cardSearch, setCardSearch] = useState("");

  /* iEduPro nested nav: preselect the Scanner tab when arriving from a
     Attendance → Scanner deep link, and preselect the classroom when a deep
     link (dashboard "My classes", nav class bands) carries one
     (state adjusted during render) */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab === "scanner") setTab("scanner");
    if (navTarget?.classroomId) {
      setClassId(navTarget.classroomId);
      setTab("register");
    }
  }

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<AttData>(`${OPS_BASE(currentSchoolId)}?action=register&${classId ? `classId=${classId}&` : ""}date=${date}`)
      .then((d) => {
        setData(d);
        if (!classId && d.classrooms.length) return; // first load: wait for class pick
        setMarks(Object.fromEntries(d.students.map((s) => [s.id, s.status ?? "PRESENT"])));
        setReasons(Object.fromEntries(d.students.map((s) => [s.id, s.excusedReason ?? ""])));
        setUpdatePicks(Object.fromEntries(d.students.map((s) => [s.id, true])));
      })
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(() => { load(); }, [currentSchoolId, classId, date]);

  const stats = useMemo(() => {
    const vals = Object.values(marks);
    return {
      present: vals.filter((v) => v === "PRESENT").length,
      late: vals.filter((v) => v === "LATE").length,
      absent: vals.filter((v) => v === "ABSENT").length,
      sick: vals.filter((v) => v === "SICK" || v === "SICK_HOME" || v === "SICK_CLINIC").length,
      excused: vals.filter((v) => v === "EXCUSED").length,
      total: vals.length,
    };
  }, [marks]);

  function shiftDate(days: number) {
    const d = new Date(date);
    d.setDate(d.getDate() + days);
    setDate(d.toISOString().slice(0, 10));
  }

  function markAll(status: string) {
    setMarks(Object.fromEntries(students.map((s) => [s.id, status])));
  }

  async function save() {
    if (!currentSchoolId || !classId) return;
    // EXCUSED requires a captured reason — block the save until captured
    const missing = students.filter((s) => marks[s.id] === "EXCUSED" && !(reasons[s.id] ?? "").trim());
    if (missing.length) {
      toast({
        title: "Excuse reason required",
        description: `${missing.length} learner${missing.length > 1 ? "s" : ""} marked Excused without a reason: ${missing.slice(0, 3).map((s) => s.name).join(", ")}${missing.length > 3 ? "…" : ""}. Capture why each is excused before saving.`,
        variant: "destructive",
      });
      return;
    }
    setBusy(true);
    try {
      const picked = students.filter((s) => !data?.session || updatePicks[s.id] !== false);
      if (!picked.length) { toast({ title: "Please select at least one learner", variant: "destructive" }); return; }
      const records = picked.map((s) => ({
        studentId: s.id,
        status: marks[s.id] ?? "PRESENT",
        ...(marks[s.id] === "EXCUSED" ? { excusedReason: (reasons[s.id] ?? "").trim() } : {}),
      }));
      const payload = { action: "save-register", classroomId: classId, date, records };
      const res = await apiPostQueueable<any>(
        `${OPS_BASE(currentSchoolId)}`, "attendance", payload,
        `Attendance ${data?.classrooms.find((c) => c.id === classId)?.name} · ${date}`,
        (p, clientId) => ({ schoolId: currentSchoolId, classroomId: classId, date, records, clientId })
      );
      if (res.queued) toast({ title: "Saved offline", description: "Register stored on this device — will sync automatically." });
      else {
        toast({ title: data?.session ? "Register updated" : "Register saved", description: `${records.length} learner${records.length !== 1 ? "s" : ""} marked for ${new Date(date).toLocaleDateString("en-GB")}.` });
        load(); // refresh the "Taken by" badge + persisted statuses
      }
    } catch (e: any) {
      toast({ title: "Save failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  const students: AttData["students"] = data?.students ?? [];
  if (!data) return <LoadingBlock label="Loading register…" />;
  const registerReady = !!classId && Array.isArray(data.students) && (data.termId || data.session);

  return (
    <div>
      <Tabs value={tab} onValueChange={setTab}>
        <TabsList>
          <TabsTrigger value="register" className="gap-1.5"><CalendarCheck className="size-4" /> Register</TabsTrigger>
          <TabsTrigger value="analytics" className="gap-1.5"><BarChart3 className="size-4" /> Analytics</TabsTrigger>
          <TabsTrigger value="monthly" className="gap-1.5"><CalendarDays className="size-4" /> Monthly</TabsTrigger>
          <TabsTrigger value="scanner" className="gap-1.5"><ScanLine className="size-4" /> Scanner</TabsTrigger>
        </TabsList>

        {/* ── classic daily register ── */}
        <TabsContent value="register" className="mt-4">
          <ModuleHeader
            title="Attendance Register"
            description="Daily class register — works fully offline; sessions sync when your data returns."
            actions={
              <div className="flex items-center gap-2">
                <Button size="sm" variant="outline" className="size-8 p-0" onClick={() => shiftDate(-1)}><ChevronLeft className="size-4" /></Button>
                <Input type="date" value={date} onChange={(e) => setDate(e.target.value)} className="w-36" />
                <Button size="sm" variant="outline" className="size-8 p-0" onClick={() => shiftDate(1)}><ChevronRight className="size-4" /></Button>
              </div>
            }
          />

          <div className="flex flex-wrap items-center gap-2 mb-4">
            <Select value={classId ?? ""} onValueChange={setClassId}>
              <SelectTrigger className="w-64"><SelectValue placeholder="Select a class" /></SelectTrigger>
              <SelectContent>{data.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent>
            </Select>
            {!online && <Badge className="gap-1.5 bg-amber-500 text-white"><CloudOff className="size-3.5" /> Offline — marks will queue</Badge>}
            {data.session && <Badge variant="secondary">Taken by {data.session.takenByName}</Badge>}
          </div>

          {!registerReady ? (
            <Card>
              <CardContent>
                <EmptyState
                  icon={<Users className="size-5" />}
                  title={classId ? "Loading register…" : "Choose a class to open the register"}
                  hint={classId ? undefined : "Select any class above to mark today's attendance."}
                />
              </CardContent>
            </Card>
          ) : (
            <>
              <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
                <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Present</div><div className="text-2xl font-bold text-emerald-600">{stats.present}</div></CardContent></Card>
                <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Late</div><div className="text-2xl font-bold text-amber-500">{stats.late}</div></CardContent></Card>
                <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Absent</div><div className="text-2xl font-bold text-destructive">{stats.absent}</div></CardContent></Card>
                <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Sick</div><div className="text-2xl font-bold text-orange-500">{stats.sick}</div></CardContent></Card>
                <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Excused</div><div className="text-2xl font-bold text-muted-foreground">{stats.excused}</div></CardContent></Card>
                <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Rate</div><div className="text-2xl font-bold text-primary">{stats.total ? Math.round(((stats.present + stats.late) / stats.total) * 100) : 0}%</div></CardContent></Card>
              </div>

              {/* CI3 mark_attendance card grid — sticky filter bar + bulk-select +
                colour-coded status select per learner, cards dim when excluded */}
              <Card className="mb-3">
                <CardContent className="p-3 flex flex-wrap items-center gap-2">
                  <Select value={classId ?? ""} onValueChange={setClassId}>
                    <SelectTrigger className="w-56 h-9"><SelectValue placeholder="Select class" /></SelectTrigger>
                    <SelectContent>{data.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent>
                  </Select>
                  <Input type="date" value={date} onChange={(e) => setDate(e.target.value)} className="w-36 h-9" />
                  <div className="relative flex-1 min-w-40">
                    <Search className="size-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
                    <Input value={cardSearch} onChange={(e) => setCardSearch(e.target.value)} placeholder="Type Student Name..." className="h-9 pl-8" />
                  </div>
                  {!online && <Badge className="gap-1.5 bg-amber-500 text-white"><CloudOff className="size-3.5" /> Offline — marks will queue</Badge>}
                  {data.session && <Badge variant="secondary">Taken by {data.session.takenByName}</Badge>}
                </CardContent>
              </Card>

              {/* bulk select bar (CI3 amber) — only when records exist */}
              {data.session && (
                <div className="mb-3 flex flex-wrap items-center gap-2 rounded-xl border border-amber-300 bg-gradient-to-r from-amber-50 to-amber-100 dark:from-amber-950/50 dark:to-amber-900/30 px-3 py-2">
                  <Checkbox
                    checked={students.every((s) => updatePicks[s.id] !== false)}
                    onCheckedChange={(v) => setUpdatePicks(Object.fromEntries(students.map((s) => [s.id, !!v])))}
                    aria-label="Select all students"
                  />
                  <span className="text-sm font-medium text-amber-900 dark:text-amber-200">Select All Students</span>
                  <span className="text-xs text-amber-700 dark:text-amber-300/80">(uncheck to update specific students only)</span>
                  <span className="ml-auto text-xs font-semibold text-amber-800 dark:text-amber-200">{students.filter((s) => updatePicks[s.id] !== false).length}/{students.length} included in this save</span>
                </div>
              )}

              <Card>
                <CardHeader className="pb-3 flex-row items-center justify-between space-y-0 flex-wrap gap-2">
                  <CardTitle className="text-base">{data.classrooms.find((c) => c.id === classId)?.name} · {new Date(date).toLocaleDateString("en-GB", { weekday: "long", day: "numeric", month: "long" })}</CardTitle>
                  <div className="flex flex-wrap items-center gap-2">
                    <Button size="sm" variant="outline" className="gap-1.5" onClick={() => markAll("PRESENT")} title="Mark every learner Present">
                      <CheckCheck className="size-4" /> All present
                    </Button>
                    <Button size="sm" variant="outline" className="gap-1.5 text-destructive hover:text-destructive" onClick={() => markAll("ABSENT")} title="Mark every learner Absent">
                      <UserX className="size-4" /> All absent
                    </Button>
                  </div>
                </CardHeader>
                <CardContent className="pb-4">
                  <div className="grid gap-3 [grid-template-columns:repeat(auto-fill,minmax(240px,1fr))]">
                    {students.filter((s) => !cardSearch.trim() || s.name.toLowerCase().includes(cardSearch.trim().toLowerCase())).map((s, i) => {
                      const st = STATUS_BY_KEY[marks[s.id] ?? "PRESENT"] ?? STATUSES[0];
                      const excluded = data.session && updatePicks[s.id] === false;
                      return (
                        <div key={s.id} className={cn("rounded-xl border-2 bg-card p-3 transition-all hover:border-primary/50", excluded && "opacity-50", excluded ? "border-transparent" : "border-transparent")}>
                          <div className="flex items-start justify-between gap-2">
                            <div className="min-w-0">
                              <div className="text-sm font-semibold truncate" title={s.name}>{s.name}</div>
                              <div className="text-[11px] text-muted-foreground">#{s.rollNo ?? i + 1}{s.markedOffline ? " · offline mark" : ""}</div>
                            </div>
                            {data.session && (
                              <Checkbox
                                checked={updatePicks[s.id] !== false}
                                onCheckedChange={(v) => setUpdatePicks((p) => ({ ...p, [s.id]: !!v }))}
                                aria-label={`Update ${s.name}`}
                                title="Include in this save"
                              />
                            )}
                          </div>
                          <label className="mt-2 block">
                            <span className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">Attendance status</span>
                            <select
                              value={marks[s.id] ?? "PRESENT"}
                              onChange={(e) => setMarks((m) => ({ ...m, [s.id]: e.target.value }))}
                              className={cn("mt-1 w-full rounded-lg border-2 px-2.5 py-1.5 text-sm font-semibold outline-none transition-colors", st.selectCls)}
                              aria-label={`Attendance status for ${s.name}`}
                            >
                              {STATUSES.map((x) => <option key={x.key} value={x.key}>{x.key === "PRESENT" ? "Present (P)" : x.key === "ABSENT" ? "Absent (A)" : x.key === "LATE" ? "Late (L)" : x.key === "SICK_HOME" ? "Sick-Home (S)" : x.key === "SICK_CLINIC" ? "Sick-Clinic (C)" : x.key === "EXCUSED" ? "Excused (E)" : x.label}</option>)}
                            </select>
                          </label>
                          {(marks[s.id] ?? "PRESENT") === "EXCUSED" && (
                            <div className="mt-2">
                              <Textarea
                                rows={2}
                                value={reasons[s.id] ?? ""}
                                onChange={(e) => setReasons((r) => ({ ...r, [s.id]: e.target.value }))}
                                placeholder="Reason for excused absence (required)"
                                className="text-xs"
                                aria-label={`Reason for ${s.name}`}
                              />
                              <p className="mt-0.5 text-[10px] text-muted-foreground">E.g. medical appointment, family emergency.</p>
                            </div>
                          )}
                        </div>
                      );
                    })}
                    {students.length > 0 && !students.filter((s) => !cardSearch.trim() || s.name.toLowerCase().includes(cardSearch.trim().toLowerCase())).length && (
                      <div className="col-span-full py-8 text-center text-sm text-muted-foreground">No learner matches “{cardSearch}”.</div>
                    )}
                  </div>
                </CardContent>
              </Card>

              {/* CI3 save FAB — fixed bottom-right, expands on hover */}
              <div className="fixed bottom-6 right-6 z-30 group no-print">
                <button
                  onClick={save}
                  disabled={busy || !registerReady}
                  title="Save attendance"
                  className="flex items-center gap-2 rounded-full bg-gradient-to-br from-violet-600 to-purple-700 p-4 text-white shadow-xl transition-all hover:pr-5 hover:scale-105 disabled:opacity-50"
                >
                  <Save className="size-5" />
                  <span className="max-w-0 overflow-hidden whitespace-nowrap text-sm font-semibold transition-all group-hover:ml-0.5 group-hover:max-w-32">{busy ? "Saving…" : "Save attendance"}</span>
                </button>
              </div>
            </>
          )}
        </TabsContent>

        {/* ── attendance analytics (per-learner aggregates over a date range) ── */}
        <TabsContent value="analytics" className="mt-4">
          {currentSchoolId ? <AnalyticsPanel schoolId={currentSchoolId} /> : <EmptyState title="No school selected" />}
        </TabsContent>

        {/* ── monthly grid (day-by-day register, printable) ── */}
        <TabsContent value="monthly" className="mt-4">
          {currentSchoolId ? <MonthlyPanel schoolId={currentSchoolId} /> : <EmptyState title="No school selected" />}
        </TabsContent>

        {/* ── barcode scanner gate console (iEduPro admin/barcode_scanner) ── */}
        <TabsContent value="scanner" className="mt-4">
          {currentSchoolId ? <ScannerPanel schoolId={currentSchoolId} /> : <EmptyState title="No school selected" />}
        </TabsContent>
      </Tabs>
    </div>
  );
}

const OPS_BASE = (schoolId: string) => `/api/school/${schoolId}${OPS}`;

// ── Analytics ─────────────────────────────────────────────────
type AnalyticsRow = {
  studentId: string; name: string; classroom: string;
  present: number; absent: number; late: number;
  sickHome: number; sickClinic: number; sickBay: number; busy: number; excused: number;
  marked: number; rate: number | null;
};
type AnalyticsData = {
  classrooms: { id: string; name: string }[];
  range: { from: string; to: string };
  summary: {
    sessions: number; marked: number;
    present: number; presentPct: number;
    absent: number; absentPct: number;
    late: number; sick: number; busy: number; excused: number;
    attendanceRate: number;
  };
  rows: AnalyticsRow[];
  trend: { date: string; rate: number }[];
};

function RateBadge({ rate }: { rate: number | null }) {
  if (rate === null) return <Badge variant="secondary">no marks</Badge>;
  const cls = rate >= 90
    ? "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
    : rate >= 75
      ? "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"
      : "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300";
  return <span className={cn("text-[11px] font-semibold px-2 py-0.5 rounded-full tabular-nums", cls)}>{rate}%</span>;
}

function AnalyticsPanel({ schoolId }: { schoolId: string }) {
  const [classId, setClassId] = useState("ALL");
  const [from, setFrom] = useState(() => new Date(Date.now() - 29 * 864e5).toISOString().slice(0, 10));
  const [to, setTo] = useState(() => new Date().toISOString().slice(0, 10));
  const [data, setData] = useState<AnalyticsData | null>(null);
  const [sortDir, setSortDir] = useState<"desc" | "asc">("desc");

  const load = () => {
    apiGet<AnalyticsData>(`${OPS_BASE(schoolId)}?action=analytics&from=${from}&to=${to}${classId !== "ALL" ? `&classroomId=${classId}` : ""}`)
      .then(setData)
      .catch((e) => toast({ title: "Analytics failed to load", description: e.message, variant: "destructive" }));
  };
  useEffect(load, [schoolId, from, to, classId]);

  const sorted = useMemo(() => {
    const rows = [...(data?.rows ?? [])];
    rows.sort((a, b) => (sortDir === "desc" ? (b.rate ?? -1) - (a.rate ?? -1) : (a.rate ?? 9999) - (b.rate ?? 9999)));
    return rows;
  }, [data, sortDir]);

  const exportCsv = () => {
    if (!sorted.length) return;
    const header = ["Name", "Class", "Present", "Absent", "Late", "Sick @ home", "Sick @ clinic", "Excused", "Marked", "Attendance rate %"];
    const lines = sorted.map((r) => [r.name, r.classroom, r.present, r.absent, r.late, r.sickHome, r.sickClinic, r.excused, r.marked, r.rate ?? "—"]);
    const csv = [header, ...lines].map((l) => l.map((c) => `"${String(c).replace(/"/g, '""')}"`).join(",")).join("\n");
    const url = URL.createObjectURL(new Blob([csv], { type: "text/csv" }));
    const a = document.createElement("a"); a.href = url; a.download = `attendance-analytics-${from}-to-${to}.csv`; a.click(); URL.revokeObjectURL(url);
  };

  if (!data) return <LoadingBlock label="Loading analytics…" />;
  const s = data.summary;
  const chartData = data.trend.map((t) => ({ ...t, label: new Date(t.date).toLocaleDateString("en-GB", { day: "numeric", month: "short" }) }));

  return (
    <div>
      <ModuleHeader
        title="Attendance Analytics"
        description="Attendance aggregates across every register session — per-learner rates, absence mix and the daily trend."
        actions={
          <Button size="sm" variant="outline" className="gap-1.5" onClick={exportCsv} disabled={!sorted.length}>
            <Download className="size-4" /> Export CSV
          </Button>
        }
      />

      <div className="flex flex-wrap items-center gap-2 mb-4">
        <Select value={classId} onValueChange={setClassId}>
          <SelectTrigger className="w-56"><SelectValue placeholder="All classes" /></SelectTrigger>
          <SelectContent>
            <SelectItem value="ALL">All classes</SelectItem>
            {data.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
          </SelectContent>
        </Select>
        <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="w-36" aria-label="From date" />
        <span className="text-xs text-muted-foreground">to</span>
        <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} className="w-36" aria-label="To date" />
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-7 gap-3 mb-4">
        <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Sessions</div><div className="text-2xl font-bold">{s.sessions}</div></CardContent></Card>
        <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Present</div><div className="text-2xl font-bold text-emerald-600">{s.present}</div><div className="text-[11px] text-muted-foreground">{s.presentPct}% of marks</div></CardContent></Card>
        <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Absent</div><div className="text-2xl font-bold text-destructive">{s.absent}</div><div className="text-[11px] text-muted-foreground">{s.absentPct}% of marks</div></CardContent></Card>
        <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Late</div><div className="text-2xl font-bold text-amber-500">{s.late}</div></CardContent></Card>
        <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Sick (all)</div><div className="text-2xl font-bold text-orange-500">{s.sick}</div><div className="text-[11px] text-muted-foreground">home · clinic · bay</div></CardContent></Card>
        <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Excused</div><div className="text-2xl font-bold text-muted-foreground">{s.excused}</div></CardContent></Card>
        <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Attendance rate</div><div className="text-2xl font-bold text-primary">{s.attendanceRate}%</div><div className="text-[11px] text-muted-foreground">present + late</div></CardContent></Card>
      </div>

      {chartData.length > 1 && (
        <Card className="mb-4">
          <CardHeader className="pb-2"><CardTitle className="text-base">Daily trend</CardTitle></CardHeader>
          <CardContent className="h-44">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={chartData} margin={{ top: 4, right: 8, left: -18, bottom: 0 }}>
                <defs>
                  <linearGradient id="attAnalyticsGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#0e5f3f" stopOpacity={0.35} />
                    <stop offset="100%" stopColor="#0e5f3f" stopOpacity={0.02} />
                  </linearGradient>
                </defs>
                <XAxis dataKey="label" tick={{ fontSize: 11 }} stroke="#888" />
                <YAxis domain={[0, 100]} tick={{ fontSize: 11 }} stroke="#888" unit="%" />
                <Tooltip formatter={(v: any) => [`${v}%`, "Attended"]} contentStyle={{ borderRadius: 10, border: "1px solid #e7ddc8" }} />
                <Area type="monotone" dataKey="rate" stroke="#0e5f3f" strokeWidth={2.2} fill="url(#attAnalyticsGrad)" />
              </AreaChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base">Per-learner attendance · {data.classrooms.find((c) => c.id === classId)?.name ?? "All classes"}</CardTitle>
        </CardHeader>
        <CardContent className="p-0">
          {!sorted.length ? (
            <EmptyState icon={<BarChart3 className="size-5" />} title="No marked learners in this range" hint="Choose a wider date range or take a register first." />
          ) : (
            <div className="overflow-x-auto nice-scroll max-h-[520px]">
              <table className="w-full text-sm">
                <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                  <tr className="text-left border-b">
                    <th className="px-3 py-2.5 font-medium">Learner</th>
                    <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Class</th>
                    <th className="px-3 py-2.5 font-medium text-center">Present</th>
                    <th className="px-3 py-2.5 font-medium text-center">Absent</th>
                    <th className="px-3 py-2.5 font-medium text-center">Late</th>
                    <th className="px-3 py-2.5 font-medium text-center">Sick @ home</th>
                    <th className="px-3 py-2.5 font-medium text-center">Sick @ clinic</th>
                    <th className="px-3 py-2.5 font-medium text-center">Excused</th>
                    <th className="px-3 py-2.5 font-medium text-center">
                      <button
                        onClick={() => setSortDir((d) => (d === "desc" ? "asc" : "desc"))}
                        className="inline-flex items-center gap-1 hover:text-foreground"
                        title="Sort by attendance rate"
                      >
                        Attendance rate {sortDir === "desc" ? <ChevronDown className="size-3.5" /> : <ChevronUp className="size-3.5" />}
                      </button>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {sorted.map((r) => (
                    <tr key={r.studentId} className="border-b last:border-0 hover:bg-secondary/30">
                      <td className="px-3 py-2 font-medium">{r.name}</td>
                      <td className="px-3 py-2 text-muted-foreground hidden sm:table-cell">{r.classroom}</td>
                      <td className="px-3 py-2 text-center tabular-nums">{r.present}</td>
                      <td className="px-3 py-2 text-center tabular-nums">{r.absent}</td>
                      <td className="px-3 py-2 text-center tabular-nums">{r.late}</td>
                      <td className="px-3 py-2 text-center tabular-nums">{r.sickHome}</td>
                      <td className="px-3 py-2 text-center tabular-nums">{r.sickClinic}</td>
                      <td className="px-3 py-2 text-center tabular-nums">{r.excused}</td>
                      <td className="px-3 py-2 text-center"><RateBadge rate={r.rate} /></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

// ── Monthly grid ──────────────────────────────────────────────
type MonthlyData = {
  classrooms: { id: string; name: string }[];
  month: string;
  daysInMonth: number;
  classroomId: string | null;
  classroomName: string | null;
  students: { id: string; name: string; rollNo: number | null; days: Record<string, { status: string; reason?: string }> }[];
};

function MonthlyPanel({ schoolId }: { schoolId: string }) {
  // wide day-by-day grid prints best in landscape (toggle preserved for
  // portrait register folders); the injected @page rule only applies at print
  const [landscape, setLandscape] = useState(true);
  const [month, setMonth] = useState(() => new Date().toISOString().slice(0, 7));
  const [classId, setClassId] = useState<string | null>(null);
  const [data, setData] = useState<MonthlyData | null>(null);

  const load = () => {
    apiGet<MonthlyData>(`${OPS_BASE(schoolId)}?action=monthly&month=${month}${classId ? `&classroomId=${classId}` : ""}`)
      .then((d) => {
        setData(d);
        if (!classId && d.classrooms.length) setClassId(d.classrooms[0].id); // auto-open the first class
      })
      .catch((e) => toast({ title: "Monthly grid failed to load", description: e.message, variant: "destructive" }));
  };
  useEffect(load, [schoolId, month, classId]);

  if (!data) return <LoadingBlock label="Loading monthly grid…" />;
  const [y, m] = month.split("-").map(Number);
  const days = Array.from({ length: data.daysInMonth }, (_, i) => i + 1);
  const isWeekend = (d: number) => [0, 6].includes(new Date(Date.UTC(y, m - 1, d)).getUTCDay());
  const monthLabel = new Date(Date.UTC(y, m - 1, 1)).toLocaleDateString("en-GB", { month: "long", year: "numeric", timeZone: "UTC" });
  const markedCount = data.students.reduce((n, s) => n + Object.keys(s.days).length, 0);

  return (
    <div>
      <ModuleHeader
        title="Monthly Attendance Grid"
        description="The whole month at a glance — one row per learner, one column per day. Print it for the class register folder."
      />

      <div className="flex flex-wrap items-center gap-2 mb-4">
        <Input type="month" value={month} onChange={(e) => setMonth(e.target.value)} className="w-40" aria-label="Month" />
        <Select value={classId ?? ""} onValueChange={setClassId}>
          <SelectTrigger className="w-64"><SelectValue placeholder="Select a class" /></SelectTrigger>
          <SelectContent>{data.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent>
        </Select>
        <div className="ml-auto flex items-center gap-2">
          <div className="flex rounded-lg border overflow-hidden">
            <button onClick={() => setLandscape(false)} className={cn("px-3 py-1.5 text-xs font-medium", !landscape ? "bg-primary text-primary-foreground" : "hover:bg-secondary/60")}>Portrait</button>
            <button onClick={() => setLandscape(true)} className={cn("px-3 py-1.5 text-xs font-medium", landscape ? "bg-primary text-primary-foreground" : "hover:bg-secondary/60")}>Landscape</button>
          </div>
          <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()}>
            <Printer className="size-4" /> Print grid
          </Button>
        </div>
      </div>
      {landscape && <style>{"@media print { @page { size: A4 landscape; margin: 8mm; } }"}</style>}

      <Card className="print-area">
        <CardHeader className="pb-3">
          <CardTitle className="text-base">{data.classroomName ?? "No class selected"} · {monthLabel}</CardTitle>
          <div className="text-xs text-muted-foreground font-normal">{data.students.length} learners on roll · {markedCount} day-marks recorded</div>
        </CardHeader>
        <CardContent className="p-0">
          {!data.classroomId || !data.students.length ? (
            <EmptyState icon={<CalendarDays className="size-5" />} title={data.classroomId ? "No learners on roll" : "Choose a class"} hint={data.classroomId ? undefined : "Select a class above to open its monthly grid."} />
          ) : (
            <div className="overflow-auto nice-scroll max-h-[560px]">
              <table className="w-full text-sm border-separate border-spacing-0">
                <thead>
                  <tr className="text-left">
                    <th className="px-3 py-2.5 font-medium sticky left-0 bg-secondary z-10 min-w-44">Learner</th>
                    {days.map((d) => (
                      <th key={d} className={cn("px-1 py-2.5 font-medium text-center text-xs w-8", isWeekend(d) && "text-muted-foreground/60")}>
                        {d}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {data.students.map((s) => (
                    <tr key={s.id} className="hover:bg-secondary/20">
                      <td className="px-3 py-1.5 font-medium sticky left-0 bg-card z-10 whitespace-nowrap">
                        <span className="text-muted-foreground mr-1.5 tabular-nums text-xs">{s.rollNo ?? "·"}</span>{s.name}
                      </td>
                      {days.map((d) => {
                        const cell = s.days[String(d)];
                        if (!cell) return <td key={d} className={cn("px-1 py-1.5 text-center text-muted-foreground/30", isWeekend(d) && "bg-muted/30")}>·</td>;
                        const st = STATUS_BY_KEY[cell.status] ?? { label: cell.status, cls: "bg-gray-300 text-gray-800", letter: cell.status.slice(0, 2) };
                        return (
                          <td key={d} className={cn("px-1 py-1.5 text-center", isWeekend(d) && "bg-muted/30")}>
                            <span
                              title={`${d} ${monthLabel}: ${st.label}${cell.reason ? ` — ${cell.reason}` : ""}`}
                              className={cn("inline-flex min-w-6 justify-center rounded px-1 py-0.5 text-[10px] font-bold", st.cls)}
                            >
                              {st.letter}
                            </span>
                          </td>
                        );
                      })}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
          <div className="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 border-t text-xs text-muted-foreground">
            {STATUSES.map((s) => (
              <span key={s.key} className="inline-flex items-center gap-1.5">
                <span className={cn("inline-flex min-w-6 justify-center rounded px-1 py-0.5 text-[10px] font-bold", s.cls)}>{s.letter}</span>
                {s.label}{s.key === "SICK" && " (legacy)"}
              </span>
            ))}
            <span>· empty cell = not marked</span>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

// ── Scanner console ───────────────────────────────────────────
type ScanLearner = {
  id: string; name: string; nyansapoIndex: string; gender?: string;
  guardianName?: string; guardianPhone?: string; photoSeed?: string | null;
};
type ScanClassroom = { id: string; name: string };
type ScanResult = {
  found?: boolean; granted?: boolean; approved?: boolean; denied?: boolean; duplicate?: boolean; checkedOut?: boolean;
  message?: string; error?: string; amountOwedCents?: number;
  learner?: ScanLearner; classroom?: ScanClassroom;
};
type ScanEvent = {
  id: string; at: number; mode: "CHECK_IN" | "CHECK_OUT"; outcome: string;
  studentId: string | null; learnerName: string | null; nyansapoIndex: string | null;
  classroomName: string | null; amountOwedCents?: number; byName: string;
};
type ScanData = {
  date: string;
  summary: { checkedIn: number; present: number; absent: number; late: number };
  sessions: { id: string; classroomId: string; classroom: string; takenByName: string | null; records: { id: string; studentId: string; name: string; nyansapoIndex: string; status: string }[] }[];
  recentScans: ScanEvent[];
};

const OUTCOME_STYLES: Record<string, { label: string; cls: string }> = {
  GRANTED: { label: "Checked in", cls: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300" },
  APPROVED: { label: "Admitted (owing)", cls: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300" },
  DENIED: { label: "Denied — arrears", cls: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300" },
  DUPLICATE: { label: "Duplicate scan", cls: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300" },
  UNKNOWN: { label: "Unknown code", cls: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300" },
  CHECKED_OUT: { label: "Checked out", cls: "bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300" },
  NOT_CHECKED_IN: { label: "No check-in", cls: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300" },
  CANCELLED: { label: "Cancelled", cls: "bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300" },
};

function ScannerPanel({ schoolId }: { schoolId: string }) {
  const today = new Date().toISOString().slice(0, 10);
  const [date, setDate] = useState(today);
  const [mode, setMode] = useState<"CHECK_IN" | "CHECK_OUT">("CHECK_IN");
  const [inputMode, setInputMode] = useState<"AUTO" | "MANUAL">("AUTO");
  const [code, setCode] = useState("");
  const [approve, setApprove] = useState(false);
  const [busy, setBusy] = useState(false);
  const [data, setData] = useState<ScanData | null>(null);
  const [last, setLast] = useState<(ScanResult & { at?: number; mode?: string }) | null>(null);
  const [lastLearner, setLastLearner] = useState<{ learner: ScanLearner; classroom?: ScanClassroom } | null>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  const load = () => {
    apiGet<ScanData>(`/api/school/${schoolId}/ops/scanner?date=${date}`)
      .then(setData)
      .catch((e) => toast({ title: "Scanner state failed to load", description: e.message, variant: "destructive" }));
  };
  useEffect(load, [schoolId, date]);

  useEffect(() => { inputRef.current?.focus(); }, [mode, inputMode]);

  async function submitScan(e?: React.FormEvent) {
    e?.preventDefault();
    const value = code.trim();
    if (!value || busy) return;
    setBusy(true);
    try {
      const res = await apiPost<ScanResult>(`/api/school/${schoolId}/ops/scanner`, {
        action: "scan", code: value, mode, approve: approve || undefined, date,
      });
      setLast({ ...res, at: Date.now(), mode });
      if (res.learner) setLastLearner({ learner: res.learner, classroom: res.classroom });
      load();
    } catch (err: any) {
      setLast({ error: err.message ?? "Scan failed", at: Date.now(), mode });
    } finally {
      setCode("");
      setApprove(false); // safety: never carry an override into the next scan
      setBusy(false);
      inputRef.current?.focus();
    }
  }

  async function cancelCheckIn(ev: ScanEvent) {
    if (!ev.studentId) return;
    try {
      await apiPost(`/api/school/${schoolId}/ops/scanner`, { action: "cancel", studentId: ev.studentId, date });
      toast({ title: "Check-in cancelled", description: `${ev.learnerName} — scanner records for today were removed.` });
      load();
    } catch (e: any) {
      toast({ title: "Cancel failed", description: e.message, variant: "destructive" });
    }
  }

  const denyActive = last?.denied === true;
  const summary = data?.summary;

  return (
    <div>
      <ModuleHeader
        title="Barcode Scanner"
        description="Gate console — scan a learner's Nyansapo Index to check them in or out. Owing learners are denied unless approved; duplicate scans are rejected."
        actions={<Badge variant="secondary" className="gap-1.5"><ScanLine className="size-3.5" /> Administrators only (level ≤ 2)</Badge>}
      />

      <div className="grid gap-4 lg:grid-cols-5">
        {/* ── scan card ── */}
        <Card className="lg:col-span-3">
          <CardContent className="pt-5 space-y-4">
            {/* mode toggle */}
            <div className="inline-flex rounded-lg border bg-muted/40 p-0.5" role="group" aria-label="Scan mode">
              {([
                ["CHECK_IN", "Check in", LogIn],
                ["CHECK_OUT", "Check out", LogOut],
              ] as const).map(([k, label, Icon]) => (
                <button
                  key={k}
                  onClick={() => setMode(k)}
                  aria-pressed={mode === k}
                  className={cn(
                    "flex items-center gap-1.5 rounded-md px-4 py-1.5 text-xs font-semibold transition-all",
                    mode === k ? "bg-primary text-primary-foreground shadow-sm" : "text-muted-foreground hover:bg-background hover:text-foreground",
                  )}
                >
                  <Icon className="size-3.5" /> {label}
                </button>
              ))}
            </div>

            {/* AUTO / MANUAL note */}
            <div className="flex flex-wrap items-center justify-between gap-2">
              <div className="inline-flex rounded-lg border bg-muted/40 p-0.5" role="group" aria-label="Input mode">
                {(["AUTO", "MANUAL"] as const).map((k) => (
                  <button
                    key={k}
                    onClick={() => setInputMode(k)}
                    aria-pressed={inputMode === k}
                    className={cn(
                      "rounded-md px-3 py-1 text-[11px] font-semibold transition-all",
                      inputMode === k ? "bg-foreground text-background" : "text-muted-foreground hover:bg-background hover:text-foreground",
                    )}
                  >{k}</button>
                ))}
              </div>
              <p className="text-xs text-muted-foreground">
                {inputMode === "AUTO"
                  ? "Hardware scanner ready — scans fire automatically (each scan sends Enter)."
                  : "Manual mode — type the index (e.g. NY-2019-000123) and press Enter."}
              </p>
            </div>

            {/* the scan field */}
            <form onSubmit={submitScan} className="space-y-2">
              <Label htmlFor="scan-code" className="sr-only">Learner code — Nyansapo Index</Label>
              <div className="relative">
                <ScanLine className="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-muted-foreground" />
                <Input
                  id="scan-code"
                  ref={inputRef}
                  autoFocus
                  autoComplete="off"
                  value={code}
                  onChange={(e) => setCode(e.target.value)}
                  placeholder="Scan or type learner index…"
                  className="h-14 pl-11 text-lg font-mono tracking-wide"
                  aria-label="Learner code — Nyansapo Index"
                />
              </div>
              <div className="flex items-center justify-between gap-2">
                <p className="text-xs text-muted-foreground">
                  {mode === "CHECK_IN" ? "Checks the learner in and marks the day PRESENT." : "Logs the learner's exit against today's check-in."}
                </p>
                <Button type="submit" size="sm" disabled={busy || !code.trim()} className="gap-1.5">
                  {busy ? "Scanning…" : mode === "CHECK_IN" ? "Check in" : "Check out"}
                </Button>
              </div>
            </form>

            {/* operator override */}
            <div className={cn(
              "flex items-start gap-2.5 rounded-lg border p-3 transition-colors",
              denyActive ? "border-amber-400 bg-amber-50 dark:bg-amber-950/40" : "border-border bg-muted/30",
            )}>
              <Checkbox id="approve-owing" checked={approve} onCheckedChange={(v) => setApprove(v === true)} className="mt-0.5" />
              <Label htmlFor="approve-owing" className="font-normal cursor-pointer text-sm">
                <b>Approve owing learner</b>
                <span className="block text-xs text-muted-foreground mt-0.5">
                  Operator override — admit a learner with fee arrears despite the denial. Resets after every scan.
                </span>
              </Label>
            </div>

            {/* result banner */}
            {last && (
              <div role="status" aria-live="polite">
                {last.granted && (
                  <div className="rounded-lg border border-emerald-300 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/50 p-3.5 flex items-start gap-2.5">
                    <CircleCheck className="size-5 text-emerald-600 shrink-0 mt-0.5" />
                    <div className="text-sm">
                      <div className="font-semibold text-emerald-800 dark:text-emerald-300">
                        Welcome — {last.learner?.name ?? "learner"} checked in
                      </div>
                      <div className="text-xs text-muted-foreground mt-0.5">
                        {last.approved
                          ? `Admitted with operator approval — still owes ${fmtCedi(last.amountOwedCents ?? 0)}.`
                          : `${last.classroom?.name ?? ""} · marked PRESENT for ${fmtDateShort(date)}.`}
                      </div>
                    </div>
                  </div>
                )}
                {last.checkedOut && (
                  <div className="rounded-lg border border-emerald-300 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/50 p-3.5 flex items-start gap-2.5">
                    <CircleCheck className="size-5 text-emerald-600 shrink-0 mt-0.5" />
                    <div className="text-sm">
                      <div className="font-semibold text-emerald-800 dark:text-emerald-300">Checked out — {last.learner?.name ?? "learner"}</div>
                      <div className="text-xs text-muted-foreground mt-0.5">Exit logged at {new Date(last.at ?? Date.now()).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" })}.</div>
                    </div>
                  </div>
                )}
                {last.denied && (
                  <div className="rounded-lg border border-amber-400 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/50 p-3.5 flex items-start gap-2.5">
                    <CircleAlert className="size-5 text-amber-600 shrink-0 mt-0.5" />
                    <div className="text-sm">
                      <div className="font-semibold text-amber-800 dark:text-amber-300">
                        Denied — {last.learner?.name ?? "learner"} owes {fmtCedi(last.amountOwedCents ?? 0)} (tick Approve to admit)
                      </div>
                      <div className="text-xs text-muted-foreground mt-0.5">Entry refused without marking attendance. Tick “Approve owing learner” and scan again to override.</div>
                    </div>
                  </div>
                )}
                {(last.duplicate || last.found === false || last.error) && (
                  <div className="rounded-lg border border-destructive/50 bg-destructive/10 p-3.5 flex items-start gap-2.5">
                    <OctagonX className="size-5 text-destructive shrink-0 mt-0.5" />
                    <div className="text-sm">
                      <div className="font-semibold text-destructive">
                        {last.duplicate ? "Duplicate scan" : last.found === false ? "Unknown learner code" : "Scan rejected"}
                      </div>
                      <div className="text-xs text-muted-foreground mt-0.5">{last.message ?? last.error}</div>
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* learner feedback panel */}
            {lastLearner && (
              <div className="rounded-lg border bg-muted/30 p-3.5 flex items-center gap-3.5">
                <Avatar className="size-14">
                  <AvatarFallback style={{ background: avatarColor(lastLearner.learner.name) }} className="text-lg font-semibold text-white">
                    {initials(lastLearner.learner.name)}
                  </AvatarFallback>
                </Avatar>
                <div className="min-w-0">
                  <div className="font-semibold truncate">{lastLearner.learner.name}</div>
                  <div className="text-sm text-muted-foreground">{lastLearner.classroom?.name ?? "—"}</div>
                  <div className="text-xs font-mono text-muted-foreground mt-0.5">{lastLearner.learner.nyansapoIndex}</div>
                  {lastLearner.learner.guardianPhone && <div className="text-xs text-muted-foreground">Guardian: {lastLearner.learner.guardianPhone}</div>}
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        {/* ── today's summary ── */}
        <Card className="lg:col-span-2 h-fit">
          <CardHeader className="pb-3"><CardTitle className="text-base">{fmtDateShort(date)} summary</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            <div className="flex flex-wrap gap-2">
              <Badge className="bg-emerald-600 text-white">Checked in {summary?.checkedIn ?? 0}</Badge>
              <Badge variant="secondary">Present {summary?.present ?? 0}</Badge>
              <Badge variant="secondary">Late {summary?.late ?? 0}</Badge>
              <Badge variant="destructive">Absent {summary?.absent ?? 0}</Badge>
            </div>
            <div className="text-xs text-muted-foreground">
              Across {data?.sessions?.length ?? 0} register {data?.sessions?.length === 1 ? "session" : "sessions"} today
              {data?.sessions?.some((s) => s.takenByName?.startsWith("Scanner")) ? " (incl. scanner-created)" : ""}.
            </div>
          </CardContent>
        </Card>
      </div>

      {/* ── today's scan log ── */}
      <Card className="mt-4">
        <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
          <CardTitle className="text-base">Today's scans</CardTitle>
          <Input type="date" value={date} onChange={(e) => setDate(e.target.value)} className="w-36" aria-label="Scan log date" />
        </CardHeader>
        <CardContent className="p-0">
          {!data?.recentScans?.length ? (
            <EmptyState icon={<ScanLine className="size-5" />} title="No scans yet" hint="Scans from today will appear here — newest first." />
          ) : (
            <div className="overflow-x-auto nice-scroll max-h-96">
              <table className="w-full text-sm">
                <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                  <tr className="text-left border-b">
                    <th className="px-3 py-2.5 font-medium w-20">Time</th>
                    <th className="px-3 py-2.5 font-medium">Learner</th>
                    <th className="px-3 py-2.5 font-medium hidden sm:table-cell">Class</th>
                    <th className="px-3 py-2.5 font-medium">Status</th>
                    <th className="px-3 py-2.5 font-medium text-right">Action</th>
                  </tr>
                </thead>
                <tbody>
                  {data.recentScans.map((ev) => {
                    const style = OUTCOME_STYLES[ev.outcome] ?? { label: ev.outcome, cls: "bg-secondary" };
                    const cancellable = ev.outcome === "GRANTED" || ev.outcome === "APPROVED";
                    return (
                      <tr key={ev.id} className="border-b last:border-0 hover:bg-secondary/30">
                        <td className="px-3 py-2 text-muted-foreground tabular-nums">
                          {new Date(ev.at).toLocaleTimeString("en-GB", { hour: "2-digit", minute: "2-digit" })}
                        </td>
                        <td className="px-3 py-2">
                          {ev.learnerName ? (
                            <div>
                              <div className="font-medium">{ev.learnerName}</div>
                              <div className="text-xs font-mono text-muted-foreground">{ev.nyansapoIndex}</div>
                            </div>
                          ) : (
                            <span className="font-mono text-xs">{ev.nyansapoIndex ?? "—"}</span>
                          )}
                        </td>
                        <td className="px-3 py-2 text-muted-foreground hidden sm:table-cell">{ev.classroomName ?? "—"}</td>
                        <td className="px-3 py-2"><span className={cn("text-[11px] font-semibold px-2 py-0.5 rounded-full", style.cls)}>{style.label}</span></td>
                        <td className="px-3 py-2 text-right">
                          {cancellable ? (
                            <Button size="sm" variant="ghost" className="h-7 gap-1.5 text-destructive" onClick={() => cancelCheckIn(ev)} title="Cancel this check-in">
                              <Ban className="size-3.5" /> Cancel
                            </Button>
                          ) : ev.outcome === "CANCELLED" ? (
                            <span className="inline-flex items-center gap-1 text-xs text-muted-foreground"><Undo2 className="size-3.5" /> undone</span>
                          ) : (
                            <span className="text-muted-foreground text-xs">—</span>
                          )}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

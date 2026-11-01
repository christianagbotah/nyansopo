"use client";
import { useEffect, useMemo, useRef, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost, apiPostQueueable } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtDateShort, initials, avatarColor, fmtCedi } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import { CalendarCheck, ChevronLeft, ChevronRight, CloudOff, Save, Users, ScanLine, LogIn, LogOut, Ban, CircleCheck, CircleAlert, OctagonX, Undo2 } from "lucide-react";

type AttData = {
  classrooms: { id: string; name: string; grade: string; stage?: string }[];
  session: { id: string; takenByName: string } | null;
  students: { id: string; name: string; rollNo: number | null; status: string | null }[];
  termId: string;
};

const STATUSES = [
  { key: "PRESENT", label: "Present", cls: "bg-emerald-600 text-white" },
  { key: "LATE", label: "Late", cls: "bg-amber-500 text-white" },
  { key: "ABSENT", label: "Absent", cls: "bg-red-600 text-white" },
  { key: "SICK", label: "Sick bay", cls: "bg-orange-400 text-white" },
  { key: "EXCUSED", label: "Excused", cls: "bg-gray-400 text-white" },
];

export function Attendance() {
  const { currentSchoolId, online, navTarget } = useStore();
  const [tab, setTab] = useState("register");
  const [data, setData] = useState<AttData | null>(null);
  const [classId, setClassId] = useState<string | null>(null);
  const [date, setDate] = useState(() => new Date().toISOString().slice(0, 10));
  const [marks, setMarks] = useState<Record<string, string>>({});
  const [busy, setBusy] = useState(false);

  /* iEduPro nested nav: preselect the Scanner tab when arriving from a
     Attendance → Scanner deep link (state adjusted during render) */
  const [appliedTarget, setAppliedTarget] = useState<any>(null);
  if (navTarget !== appliedTarget) {
    setAppliedTarget(navTarget);
    if (navTarget?.tab === "scanner") setTab("scanner");
  }

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<AttData>(`/api/school/${currentSchoolId}/attendance?${classId ? `classId=${classId}&` : ""}date=${date}`)
      .then((d) => {
        setData(d);
        if (!classId && d.classrooms.length) return; // first load: wait for class pick
        setMarks(Object.fromEntries(d.students.map((s) => [s.id, s.status ?? "PRESENT"])));
      })
      .catch((e) => toast({ title: "Load failed", description: e.message, variant: "destructive" }));
  };
  useEffect(() => { load();}, [currentSchoolId, classId, date]);

  const stats = useMemo(() => {
    const vals = Object.values(marks);
    return {
      present: vals.filter((v) => v === "PRESENT").length,
      late: vals.filter((v) => v === "LATE").length,
      absent: vals.filter((v) => v === "ABSENT").length,
      other: vals.filter((v) => v === "SICK" || v === "EXCUSED").length,
      total: vals.length,
    };
  }, [marks]);

  function shiftDate(days: number) {
    const d = new Date(date);
    d.setDate(d.getDate() + days);
    setDate(d.toISOString().slice(0, 10));
  }

  async function save() {
    if (!currentSchoolId || !classId) return;
    setBusy(true);
    try {
      const records = (data?.students ?? []).map((s) => ({ studentId: s.id, status: marks[s.id] ?? "PRESENT" }));
      const payload = { action: "save", classroomId: classId, date, records };
      const res = await apiPostQueueable<any>(
        `/api/school/${currentSchoolId}/attendance`, "attendance", payload,
        `Attendance ${data?.classrooms.find((c) => c.id === classId)?.name} · ${date}`,
        (p, clientId) => ({ schoolId: currentSchoolId, classroomId: classId, date, records, clientId })
      );
      if (res.queued) toast({ title: "Saved offline", description: "Register stored on this device — will sync automatically." });
      else toast({ title: "Register saved", description: `${records.length} learners marked for ${new Date(date).toLocaleDateString("en-GB")}.` });
    } catch (e: any) {
      toast({ title: "Save failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }

  if (!data) return <LoadingBlock label="Loading register…" />;
  const students = data.students ?? [];
  const registerReady = !!classId && Array.isArray(data.students) && (data.termId || data.session);

  return (
    <div>
      <Tabs value={tab} onValueChange={setTab}>
        <TabsList>
          <TabsTrigger value="register" className="gap-1.5"><CalendarCheck className="size-4" /> Register</TabsTrigger>
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
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Present</div><div className="text-2xl font-bold text-emerald-600">{stats.present}</div></CardContent></Card>
                <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Late</div><div className="text-2xl font-bold text-amber-500">{stats.late}</div></CardContent></Card>
                <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Absent</div><div className="text-2xl font-bold text-destructive">{stats.absent}</div></CardContent></Card>
                <Card><CardContent className="pt-5 pb-4"><div className="text-xs text-muted-foreground uppercase">Rate</div><div className="text-2xl font-bold text-primary">{stats.total ? Math.round(((stats.present + stats.late) / stats.total) * 100) : 0}%</div></CardContent></Card>
              </div>

              <Card>
                <CardHeader className="pb-3 flex-row items-center justify-between space-y-0">
                  <CardTitle className="text-base">{data.classrooms.find((c) => c.id === classId)?.name} · {new Date(date).toLocaleDateString("en-GB", { weekday: "long", day: "numeric", month: "long" })}</CardTitle>              <Button size="sm" className="gap-1.5" onClick={save} disabled={busy}><Save className="size-4" /> {busy ? "Saving…" : online ? "Save register" : "Save offline"}</Button>
                </CardHeader>
                <CardContent className="p-0">
                  <div className="overflow-x-auto nice-scroll max-h-[480px]">
                    <table className="w-full text-sm">
                      <thead className="sticky top-0 bg-secondary/70 backdrop-blur">
                        <tr className="text-left border-b">
                          <th className="px-3 py-2.5 font-medium w-14">#</th>
                          <th className="px-3 py-2.5 font-medium">Learner</th>
                          {STATUSES.map((s) => <th key={s.key} className="px-3 py-2.5 font-medium text-center w-24">{s.label}</th>)}
                        </tr>
                      </thead>
                      <tbody>
                        {students.map((s, i) => (
                          <tr key={s.id} className="border-b last:border-0">
                            <td className="px-3 py-2 text-muted-foreground">{s.rollNo ?? i + 1}</td>
                            <td className="px-3 py-2 font-medium">{s.name}</td>
                            {STATUSES.map((st) => (
                              <td key={st.key} className="px-3 py-2 text-center">
                                <button
                                  onClick={() => setMarks((m) => ({ ...m, [s.id]: st.key }))}
                                  className={cn("w-5 h-5 rounded-full border-2 transition-all", marks[s.id] === st.key ? `${st.cls} border-transparent scale-110` : "border-border hover:border-muted-foreground")}
                                  title={st.label}
                                  aria-label={`${s.name} ${st.label}`}
                                />
                              </td>
                            ))}
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </CardContent>
              </Card>
            </>
          )}
        </TabsContent>

        {/* ── barcode scanner gate console (iEduPro admin/barcode_scanner) ── */}
        <TabsContent value="scanner" className="mt-4">
          {currentSchoolId ? <ScannerPanel schoolId={currentSchoolId} /> : <EmptyState title="No school selected" />}
        </TabsContent>
      </Tabs>
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

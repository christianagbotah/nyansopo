"use client";
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPostQueueable } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { fmtDateShort } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import { CalendarCheck, ChevronLeft, ChevronRight, CloudOff, Save, Users } from "lucide-react";

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
  const { currentSchoolId, online } = useStore();
  const [data, setData] = useState<AttData | null>(null);
  const [classId, setClassId] = useState<string | null>(null);
  const [date, setDate] = useState(() => new Date().toISOString().slice(0, 10));
  const [marks, setMarks] = useState<Record<string, string>>({});
  const [busy, setBusy] = useState(false);

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
    </div>
  );
}

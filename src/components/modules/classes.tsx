"use client";
/* Nyansapo — Classes & Timetable (iedupro class routine parity, modernised).
   Left: class cards with teacher/capacity/enrolment. Right: weekly grid editor. */
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { ModuleHeader, LoadingBlock, EmptyState } from "@/components/ui-helpers";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import {
  CalendarDays, Plus, Pencil, Trash2, Users, Printer, GraduationCap, Clock, X, LayoutGrid,
} from "lucide-react";

type Slot = {
  id: string; subjectId: string | null; subjectName: string; subjectCode?: string | null;
  staffId: string | null; staffName: string | null; dayOfWeek: number; startMinute: number; endMinute: number; notes?: string | null;
};
type ClassRow = {
  id: string; name: string; gradeLevel: string; stageLabel?: string | null; capacity: number;
  classTeacherId: string | null; classTeacher: string | null; enrolled: number; slots: Slot[];
};
type StaffRow = { id: string; name: string; category: string; position: string | null; role: string; subjects: string[] };
type SubjectRow = { id: string; name: string; code: string; isCore: boolean };
type Data = { classrooms: ClassRow[]; staff: StaffRow[]; subjects: SubjectRow[] };

const DAYS = [
  { n: 1, label: "Mon" }, { n: 2, label: "Tue" }, { n: 3, label: "Wed" }, { n: 4, label: "Thu" }, { n: 5, label: "Fri" },
];
const hhmm = (m: number) => `${String(Math.floor(m / 60)).padStart(2, "0")}:${String(m % 60).padStart(2, "0")}`;
const toMin = (t: string) => { const [h, m] = t.split(":").map(Number); return h * 60 + (m || 0); };

export function Classes() {
  const { currentSchoolId } = useStore();
  const [data, setData] = useState<Data | null>(null);
  const [selected, setSelected] = useState<string | null>(null);

  // class form
  const [classFormOpen, setClassFormOpen] = useState(false);
  const [editClass, setEditClass] = useState<Partial<ClassRow> | null>(null);

  // slot form
  const [slotForm, setSlotForm] = useState<null | { id?: string; subjectId: string; subjectName: string; staffId: string; day: string; start: string; end: string }>(null);

  const load = () => {
    if (!currentSchoolId) return;
    apiGet<Data>(`/api/school/${currentSchoolId}/ops/classes`).then((d) => {
      setData(d);
      setSelected((s) => s && d.classrooms.some((c) => c.id === s) ? s : d.classrooms[0]?.id ?? null);
    }).catch(() => setData({ classrooms: [], staff: [], subjects: [] }));
  };
  useEffect(load, [currentSchoolId]);

  const cls = useMemo(() => data?.classrooms.find((c) => c.id === selected) ?? null, [data, selected]);

  // periods: derive the union of distinct start minutes for this class, else default periods
  const periodStarts = useMemo(() => {
    if (!cls) return [];
    const set = new Set<number>();
    cls.slots.forEach((s) => { set.add(s.startMinute); set.add(s.endMinute); });
    const points = [...set].sort((a, b) => a - b);
    if (points.length < 2) return [480, 540, 600, 660, 730, 790, 840, 900].map((s, i) => ({ i, start: s, end: [540, 600, 660, 730, 790, 840, 900, 950][i] }));
    // build intervals between consecutive boundary points that actually have lessons
    const used: { i: number; start: number; end: number }[] = [];
    for (let i = 0; i < points.length - 1; i++) {
      const start = points[i], end = points[i + 1];
      if (cls.slots.some((s) => s.startMinute < end && s.endMinute > start)) used.push({ i: used.length, start, end });
    }
    return used;
  }, [cls]);

  async function saveClass() {
    if (!editClass) return;
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/classes`, {
        action: "save-classroom",
        id: editClass.id ?? null,
        name: editClass.name, gradeLevel: editClass.gradeLevel, stageLabel: editClass.stageLabel,
        capacity: editClass.capacity ?? 30, classTeacherId: editClass.classTeacherId ?? "",
      });
      toast({ title: editClass.id ? "Class updated" : "Class created" });
      setClassFormOpen(false); setEditClass(null); load();
    } catch (e: any) { toast({ title: "Save failed", description: e.message, variant: "destructive" }); }
  }

  async function deleteClass(c: ClassRow) {
    if (!confirm(`Delete ${c.name}? This removes its timetable. Enrolled learners block deletion.`)) return;
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/classes`, { action: "delete-classroom", id: c.id });
      toast({ title: "Class deleted" }); load();
    } catch (e: any) { toast({ title: "Cannot delete", description: e.message, variant: "destructive" }); }
  }

  async function saveSlot() {
    if (!slotForm || !cls) return;
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/classes`, {
        action: "save-slot",
        id: slotForm.id ?? null,
        classroomId: cls.id,
        subjectId: slotForm.subjectId === "custom" ? "" : slotForm.subjectId,
        subjectName: slotForm.subjectName,
        staffId: slotForm.staffId,
        dayOfWeek: Number(slotForm.day),
        startMinute: toMin(slotForm.start),
        endMinute: toMin(slotForm.end),
      });
      toast({ title: slotForm.id ? "Period updated" : "Period added" });
      setSlotForm(null); load();
    } catch (e: any) { toast({ title: "Save failed", description: e.message, variant: "destructive" }); }
  }

  async function deleteSlot(id: string) {
    try {
      await apiPost(`/api/school/${currentSchoolId}/ops/classes`, { action: "delete-slot", id });
      load();
    } catch (e: any) { toast({ title: "Delete failed", description: e.message, variant: "destructive" }); }
  }

  if (!data) return <LoadingBlock label="Loading classes & timetable…" />;

  return (
    <div>
      <ModuleHeader
        title="Classes & Timetable"
        description="Manage classes, class teachers and the weekly routine — subjects, periods and subject teachers per class."
        actions={
          <>
            <Button size="sm" variant="outline" className="gap-1.5" onClick={() => window.print()}>
              <Printer className="size-4" /> Print routine
            </Button>
            <Button size="sm" className="gap-1.5" onClick={() => { setEditClass({ capacity: 30, gradeLevel: "B4" }); setClassFormOpen(true); }}>
              <Plus className="size-4" /> New class
            </Button>
          </>
        }
      />

      <div className="grid grid-cols-1 lg:grid-cols-[300px_minmax(0,1fr)] gap-5 items-start">
        {/* ── class list ── */}
        <div className="space-y-2.5">
          {data.classrooms.map((c) => (
            <button key={c.id} onClick={() => setSelected(c.id)}
              className={cn("w-full text-left rounded-xl border p-3.5 transition-colors",
                c.id === selected ? "border-primary bg-primary/5 ring-1 ring-primary" : "border-border hover:border-primary/40 hover:bg-secondary/40")}>
              <div className="flex items-center justify-between gap-2">
                <span className="font-semibold text-sm">{c.name}</span>
                {c.id === selected && <Badge className="bg-primary text-[10px]">open</Badge>}
              </div>
              <div className="text-[11px] text-muted-foreground mt-0.5">{c.stageLabel ?? c.gradeLevel}</div>
              <div className="flex items-center gap-2 mt-2 flex-wrap">
                <Badge variant={c.enrolled >= c.capacity ? "destructive" : "secondary"} className="text-[10px] gap-1">
                  <Users className="size-2.5" /> {c.enrolled}/{c.capacity}
                </Badge>
                {c.slots.length > 0 && (
                  <Badge variant="outline" className="text-[10px] gap-1"><CalendarDays className="size-2.5" /> {c.slots.length} periods</Badge>
                )}
              </div>
              {c.classTeacher && <div className="text-[11px] text-muted-foreground mt-1.5 truncate">👨‍🏫 {c.classTeacher}</div>}
            </button>
          ))}
          {data.classrooms.length === 0 && (
            <EmptyState icon={<GraduationCap className="size-5" />} title="No classes yet" hint="Create your first class to start building timetables." />
          )}
          <div className="flex gap-2">
            <Button size="sm" variant="outline" className="flex-1 gap-1" onClick={() => { setEditClass({ capacity: 30, gradeLevel: "B4" }); setClassFormOpen(true); }}>
              <Plus className="size-3.5" /> Add class
            </Button>
            {cls && (
              <>
                <Button size="sm" variant="outline" onClick={() => { setEditClass(cls); setClassFormOpen(true); }}><Pencil className="size-3.5" /></Button>
                <Button size="sm" variant="outline" className="text-destructive hover:text-destructive" onClick={() => deleteClass(cls)}><Trash2 className="size-3.5" /></Button>
              </>
            )}
          </div>
        </div>

        {/* ── timetable ── */}
        <Card className="print-area">
          <CardContent className="pt-5 pb-5">
            {!cls ? (
              <EmptyState icon={<LayoutGrid className="size-5" />} title="Select a class" hint="Pick a class on the left to view and edit its weekly routine." />
            ) : (
              <>
                <div className="flex flex-wrap items-center justify-between gap-2 mb-4 no-print">
                  <div>
                    <div className="font-bold text-lg">{cls.name} — weekly routine</div>
                    <div className="text-xs text-muted-foreground">
                      {cls.stageLabel ?? cls.gradeLevel} · {cls.classTeacher ? `Class teacher: ${cls.classTeacher}` : "No class teacher assigned"}
                    </div>
                  </div>
                  <Button size="sm" className="gap-1.5" onClick={() => setSlotForm({ subjectId: "", subjectName: "", staffId: "", day: "1", start: "08:00", end: "08:40" })}>
                    <Plus className="size-4" /> Add period
                  </Button>
                </div>

                <div className="overflow-x-auto">
                  <table className="w-full border-collapse text-[12px] min-w-[720px]">
                    <thead>
                      <tr>
                        <th className="border border-border bg-secondary/60 px-2 py-2 text-left font-semibold w-24">
                          <div className="flex items-center gap-1 text-[10px] uppercase tracking-wider text-muted-foreground"><Clock className="size-3" /> Time</div>
                        </th>
                        {DAYS.map((d) => (
                          <th key={d.n} className="border border-border bg-secondary/60 px-2 py-2 text-center font-semibold text-[11px] uppercase tracking-wider">{d.label}</th>
                        ))}
                      </tr>
                    </thead>
                    <tbody>
                      {periodStarts.map((p) => (
                        <tr key={p.i} className="print-avoid-break">
                          <td className="border border-border px-2 py-1.5 text-[10px] font-mono text-muted-foreground whitespace-nowrap align-middle">
                            {hhmm(p.start)}–{hhmm(p.end)}
                          </td>
                          {DAYS.map((d) => {
                            const slot = cls.slots.find((s) => s.dayOfWeek === d.n && s.startMinute < p.end && s.endMinute > p.start);
                            return (
                              <td key={d.n} className={cn("border border-border p-1 align-top", !slot && "bg-secondary/20")}>
                                {slot ? (
                                  <div className="group relative rounded-md px-2 py-1.5 bg-primary/5 border border-primary/20">
                                    <div className="font-semibold text-[11.5px] leading-tight">{slot.subjectName}</div>
                                    {slot.staffName && <div className="text-[10px] text-muted-foreground mt-0.5 truncate">{slot.staffName}</div>}
                                    <div className="absolute top-1 right-1 hidden group-hover:flex gap-0.5 no-print">
                                      <button className="rounded p-0.5 hover:bg-secondary" title="Edit"
                                        onClick={() => setSlotForm({
                                          id: slot.id,
                                          subjectId: slot.subjectId ?? "custom",
                                          subjectName: slot.subjectName,
                                          staffId: slot.staffId ?? "",
                                          day: String(slot.dayOfWeek),
                                          start: hhmm(slot.startMinute), end: hhmm(slot.endMinute),
                                        })}>
                                        <Pencil className="size-3" />
                                      </button>
                                      <button className="rounded p-0.5 hover:bg-destructive/10 text-destructive" title="Remove" onClick={() => deleteSlot(slot.id)}>
                                        <Trash2 className="size-3" />
                                      </button>
                                    </div>
                                  </div>
                                ) : (
                                  <button className="w-full h-9 flex items-center justify-center rounded-md text-muted-foreground/50 hover:bg-primary/5 hover:text-primary transition-colors no-print"
                                    onClick={() => setSlotForm({ subjectId: "", subjectName: "", staffId: "", day: String(d.n), start: hhmm(p.start), end: hhmm(p.end) })}>
                                    <Plus className="size-3.5" />
                                  </button>
                                )}
                              </td>
                            );
                          })}
                        </tr>
                      ))}
                      {periodStarts.length === 0 && (
                        <tr>
                          <td colSpan={6} className="border border-border px-3 py-8 text-center text-muted-foreground text-xs">
                            No periods yet — click “Add period” to build {cls.name}&apos;s routine.
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>

                {/* teacher load summary */}
                {cls.slots.length > 0 && (
                  <div className="mt-4 no-print">
                    <div className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground mb-2">Subject teachers on this routine</div>
                    <div className="flex flex-wrap gap-1.5">
                      {[...new Map(cls.slots.filter((s) => s.staffName).map((s) => [s.staffName, s])).values()].map((s: any) => (
                        <Badge key={s.id} variant="outline" className="gap-1 text-[11px]">
                          {s.subjectName} · {s.staffName}
                        </Badge>
                      ))}
                      {cls.slots.every((s) => !s.staffName) && <span className="text-xs text-muted-foreground">No subject teachers assigned yet — edit periods to assign them.</span>}
                    </div>
                  </div>
                )}
              </>
            )}
          </CardContent>
        </Card>
      </div>

      {/* ── class form dialog ── */}
      <Dialog open={classFormOpen} onOpenChange={(o) => { setClassFormOpen(o); if (!o) setEditClass(null); }}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader><DialogTitle>{editClass?.id ? "Edit class" : "New class"}</DialogTitle></DialogHeader>
          <div className="grid gap-3 sm:grid-cols-2">
            <div className="space-y-1.5 sm:col-span-2">
              <Label>Class name *</Label>
              <Input value={editClass?.name ?? ""} placeholder="Basic 4 Gold"
                onChange={(e) => setEditClass({ ...editClass, name: e.target.value })} />
            </div>
            <div className="space-y-1.5">
              <Label>Grade level *</Label>
              <Input value={editClass?.gradeLevel ?? ""} placeholder="B4 / KG2 / Y7"
                onChange={(e) => setEditClass({ ...editClass, gradeLevel: e.target.value })} />
            </div>
            <div className="space-y-1.5">
              <Label>Capacity</Label>
              <Input type="number" min={1} value={editClass?.capacity ?? 30}
                onChange={(e) => setEditClass({ ...editClass, capacity: Number(e.target.value) })} />
            </div>
            <div className="space-y-1.5 sm:col-span-2">
              <Label>Stage label</Label>
              <Input value={editClass?.stageLabel ?? ""} placeholder="Primary (SBC) · Cambridge Lower Secondary"
                onChange={(e) => setEditClass({ ...editClass, stageLabel: e.target.value })} />
            </div>
            <div className="space-y-1.5 sm:col-span-2">
              <Label>Class teacher (form master)</Label>
              <Select value={editClass?.classTeacherId ?? ""} onValueChange={(v) => setEditClass({ ...editClass, classTeacherId: v === "none" ? "" : v })}>
                <SelectTrigger><SelectValue placeholder="Not assigned" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">Not assigned</SelectItem>
                  {data.staff.map((s) => <SelectItem key={s.id} value={s.id}>{s.name}{s.position ? ` — ${s.position}` : ""}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => { setClassFormOpen(false); setEditClass(null); }}>Cancel</Button>
            <Button onClick={saveClass} disabled={!editClass?.name?.trim() || !editClass?.gradeLevel?.trim()}>
              {editClass?.id ? "Save changes" : "Create class"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── slot form dialog ── */}
      <Dialog open={!!slotForm} onOpenChange={(o) => !o && setSlotForm(null)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              {slotForm?.id ? "Edit period" : "Add period"}
              {cls && <Badge variant="secondary" className="font-normal">{cls.name}</Badge>}
            </DialogTitle>
          </DialogHeader>
          {slotForm && (
            <div className="grid gap-3 sm:grid-cols-2">
              <div className="space-y-1.5 sm:col-span-2">
                <Label>Subject *</Label>
                <Select value={slotForm.subjectId || undefined}
                  onValueChange={(v) => setSlotForm({
                    ...slotForm, subjectId: v,
                    subjectName: v === "custom" ? "" : data.subjects.find((s) => s.id === v)?.name ?? "",
                  })}>
                  <SelectTrigger><SelectValue placeholder="Choose subject" /></SelectTrigger>
                  <SelectContent>
                    {data.subjects.map((s) => <SelectItem key={s.id} value={s.id}>{s.name} {!s.isCore && "(elective)"}</SelectItem>)}
                    <SelectItem value="custom">Other / custom lesson…</SelectItem>
                  </SelectContent>
                </Select>
                {slotForm.subjectId === "custom" && (
                  <Input className="mt-1.5" value={slotForm.subjectName} placeholder="Lesson name e.g. Library period"
                    onChange={(e) => setSlotForm({ ...slotForm, subjectName: e.target.value })} />
                )}
              </div>
              <div className="space-y-1.5 sm:col-span-2">
                <Label>Subject teacher</Label>
                <Select value={slotForm.staffId || undefined} onValueChange={(v) => setSlotForm({ ...slotForm, staffId: v === "none" ? "" : v })}>
                  <SelectTrigger><SelectValue placeholder="Not assigned" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">Not assigned</SelectItem>
                    {data.staff.map((s) => <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-1.5">
                <Label>Day *</Label>
                <Select value={slotForm.day} onValueChange={(v) => setSlotForm({ ...slotForm, day: v })}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    {DAYS.map((d) => <SelectItem key={d.n} value={String(d.n)}>{d.label}day</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
              <div className="grid grid-cols-2 gap-2">
                <div className="space-y-1.5">
                  <Label>Start</Label>
                  <Input type="time" value={slotForm.start} onChange={(e) => setSlotForm({ ...slotForm, start: e.target.value })} />
                </div>
                <div className="space-y-1.5">
                  <Label>End</Label>
                  <Input type="time" value={slotForm.end} onChange={(e) => setSlotForm({ ...slotForm, end: e.target.value })} />
                </div>
              </div>
            </div>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setSlotForm(null)}>Cancel</Button>
            <Button onClick={saveSlot} disabled={!slotForm || (!slotForm.subjectId && !slotForm.subjectName.trim()) || toMin(slotForm.end) <= toMin(slotForm.start)}>
              {slotForm?.id ? "Save period" : "Add to routine"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

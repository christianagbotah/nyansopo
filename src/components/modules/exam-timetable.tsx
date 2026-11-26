"use client";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { EmptyState, LoadingBlock } from "@/components/ui-helpers";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Switch } from "@/components/ui/switch";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { toast } from "@/hooks/use-toast";
import { CalendarClock, Plus, Trash2, Printer, Send, Sparkles, Loader2, Users, ListChecks } from "lucide-react";

type TtEntry = {
  id: string; examName: string; classroomId: string; classroomName: string;
  subjectName: string; date: string; startMinute: number; durationMin: number;
  venue: string | null; invigilatorIds: string[]; invigilators: string[]; createdBy: string;
};
type StaffLite = { id: string; name: string; phone: boolean; smsNotify: boolean; category: string };
type TtData = {
  entries: TtEntry[];
  classrooms: { id: string; name: string }[];
  staff: StaffLite[];
  examNames: string[];
  subjects: string[];
};

const hhmm = (m: number) => `${String(Math.floor(m / 60)).padStart(2, "0")}:${String(m % 60).padStart(2, "0")}`;
const DAYS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
const dayLabel = (iso: string) => {
  const d = new Date(`${iso}T00:00:00.000Z`);
  return `${DAYS[d.getUTCDay()]}, ${d.getUTCDate()} ${["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"][d.getUTCMonth()]}`;
};
const START_TIMES = [7 * 60, 8 * 60, 9 * 60, 10 * 60, 11 * 60, 11 * 60 + 30, 12 * 60, 13 * 60, 13 * 60 + 30, 14 * 60, 15 * 60];
const DURATIONS = [45, 60, 90, 105, 120, 180];

export function ExamTimetable() {
  const { currentSchoolId, schools } = useStore();
  const [data, setData] = useState<TtData | null>(null);
  const [examFilter, setExamFilter] = useState("all");
  const [view, setView] = useState<"docket" | "roster">("docket");
  const [addOpen, setAddOpen] = useState(false);
  const [draftOpen, setDraftOpen] = useState(false);
  const [printOpen, setPrintOpen] = useState(false);
  const [busy, setBusy] = useState(false);

  const load = useCallback(() => {
    if (!currentSchoolId) return;
    apiGet<TtData>(`/api/school/${currentSchoolId}/ops/exam-timetable`)
      .then(setData)
      .catch((e) => toast({ title: "Could not load exam timetable", description: e.message, variant: "destructive" }));
  }, [currentSchoolId]);
  useEffect(load, [load]);

  const school = schools.find((s) => s.id === currentSchoolId);
  const entries = useMemo(() => (data?.entries ?? []).filter((e) => examFilter === "all" || e.examName === examFilter), [data, examFilter]);
  const byDay = useMemo(() => {
    const m = new Map<string, TtEntry[]>();
    for (const e of entries) {
      if (!m.has(e.date)) m.set(e.date, []);
      m.get(e.date)!.push(e);
    }
    return [...m.entries()].sort((a, b) => a[0].localeCompare(b[0]));
  }, [entries]);
  const roster = useMemo(() => {
    const m = new Map<string, { name: string; papers: TtEntry[] }>();
    for (const e of entries) {
      for (let i = 0; i < e.invigilatorIds.length; i++) {
        const id = e.invigilatorIds[i];
        const name = e.invigilators[i] ?? "Unknown staff";
        if (!m.has(id)) m.set(id, { name, papers: [] });
        m.get(id)!.papers.push(e);
      }
    }
    return [...m.values()].sort((a, b) => b.papers.length - a.papers.length || a.name.localeCompare(b.name));
  }, [entries]);
  const unstaffed = entries.filter((e) => !e.invigilatorIds.length).length;

  async function act(body: Record<string, unknown>, okMsg: string, close?: () => void) {
    setBusy(true);
    try {
      const d = await apiPost<{ entries?: TtEntry[]; results?: { staff: string; sms: string }[]; created?: number }>(`/api/school/${currentSchoolId}/ops/exam-timetable`, body);
      if (d.entries) setData((prev) => (prev ? { ...prev, entries: d.entries! } : prev));
      toast({ title: okMsg, description: d.created ? `${d.created} papers scheduled` : d.results ? `SMS: ${d.results.filter((r) => r.sms === "SENT").length} sent of ${d.results.length}` : undefined });
      close?.();
      return true;
    } catch (e: unknown) {
      toast({ title: "Failed", description: e instanceof Error ? e.message : String(e), variant: "destructive" });
      return false;
    } finally { setBusy(false); }
  }

  if (!data) return <LoadingBlock label="Loading exam timetable…" />;

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center gap-2">
        <Select value={examFilter} onValueChange={setExamFilter}>
          <SelectTrigger className="h-9 w-[220px]"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All exams ({data.entries.length} papers)</SelectItem>
            {data.examNames.map((n) => (
              <SelectItem key={n} value={n}>{n} ({data.entries.filter((e) => e.examName === n).length})</SelectItem>
            ))}
          </SelectContent>
        </Select>
        <div className="ml-auto flex flex-wrap gap-2">
          <Button size="sm" variant="outline" className="gap-1.5 h-9" onClick={() => setPrintOpen(true)} disabled={!entries.length}><Printer className="size-3.5" /> Print docket</Button>
          <Button size="sm" variant="outline" className="gap-1.5 h-9" disabled={!entries.some((e) => e.invigilatorIds.length)} onClick={() => act({ action: "notify.invigilators", examName: examFilter === "all" ? null : examFilter }, "Invigilation duty SMS queued")}><Send className="size-3.5" /> SMS invigilators</Button>
          <Button size="sm" variant="outline" className="gap-1.5 h-9" onClick={() => setDraftOpen(true)}><Sparkles className="size-3.5" /> Auto-draft</Button>
          <Button size="sm" className="gap-1.5 h-9" onClick={() => setAddOpen(true)}><Plus className="size-3.5" /> Add paper</Button>
        </div>
      </div>

      <div className="flex gap-1.5">
        <Button size="sm" variant={view === "docket" ? "secondary" : "ghost"} className="h-8 gap-1.5" onClick={() => setView("docket")}><CalendarClock className="size-3.5" /> Docket ({entries.length})</Button>
        <Button size="sm" variant={view === "roster" ? "secondary" : "ghost"} className="h-8 gap-1.5" onClick={() => setView("roster")}><Users className="size-3.5" /> Invigilation roster ({roster.length})</Button>
        {unstaffed > 0 && <Badge variant="outline" className="text-[10px] self-center ml-1 border-amber-400 text-amber-600">{unstaffed} paper{unstaffed > 1 ? "s" : ""} without invigilators</Badge>}
      </div>

      {view === "docket" ? (
        entries.length === 0 ? (
          <EmptyState icon={<CalendarClock className="size-5" />} title="No papers on the docket" hint="Add a paper by hand, or auto-draft the whole exam from the live timetable." />
        ) : (
          <div className="space-y-4">
            {byDay.map(([day, papers]) => (
              <Card key={day}>
                <CardHeader className="pb-2">
                  <CardTitle className="text-base">{dayLabel(day)} <span className="text-xs font-normal text-muted-foreground">· {papers.length} paper{papers.length > 1 ? "s" : ""}</span></CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead className="w-[110px]">Time</TableHead>
                        <TableHead>Class</TableHead>
                        <TableHead>Subject</TableHead>
                        <TableHead className="hidden sm:table-cell">Venue</TableHead>
                        <TableHead className="hidden lg:table-cell">Invigilators</TableHead>
                        <TableHead className="text-right">Actions</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {papers.map((p) => (
                        <PaperRow key={p.id} p={p} staff={data.staff} busy={busy} act={act} onEdit={() => setAddOpen(true)} />
                      ))}
                    </TableBody>
                  </Table>
                </CardContent>
              </Card>
            ))}
          </div>
        )
      ) : (
        <Card>
          <CardHeader className="pb-2"><CardTitle className="text-base">Who invigilates what</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {roster.length === 0 ? (
              <EmptyState icon={<Users className="size-5" />} title="No invigilators assigned" hint="Open a paper row and pick the staff on duty." />
            ) : roster.map((r) => (
              <div key={r.name} className="rounded-lg border px-3 py-2.5">
                <div className="flex items-center gap-2">
                  <ListChecks className="size-4 text-muted-foreground" />
                  <span className="font-medium text-sm">{r.name}</span>
                  <Badge variant="outline" className="text-[10px]">{r.papers.length} paper{r.papers.length > 1 ? "s" : ""}</Badge>
                </div>
                <div className="text-xs text-muted-foreground mt-1.5 leading-relaxed">
                  {r.papers.map((p) => `${dayLabel(p.date)} ${hhmm(p.startMinute)} ${p.subjectName} (${p.classroomName})`).join(" · ")}
                </div>
              </div>
            ))}
          </CardContent>
        </Card>
      )}

      <AddPaperDialog open={addOpen} onOpenChange={setAddOpen} data={data} busy={busy} act={act} />
      <DraftDialog open={draftOpen} onOpenChange={setDraftOpen} data={data} busy={busy} act={act} />
      <PrintDocket open={printOpen} onOpenChange={setPrintOpen} entries={entries} schoolName={school?.name ?? "School"} filter={examFilter === "all" ? null : examFilter} />
    </div>
  );
}

function PaperRow({ p, staff, busy, act, onEdit }: {
  p: TtEntry; staff: StaffLite[]; busy: boolean; onEdit: () => void;
  act: (body: Record<string, unknown>, okMsg: string, close?: () => void) => Promise<boolean>;
}) {
  const [invOpen, setInvOpen] = useState(false);
  const [picked, setPicked] = useState<string[]>(p.invigilatorIds);
  return (
    <TableRow>
      <TableCell className="font-mono text-xs whitespace-nowrap">{hhmm(p.startMinute)}–{hhmm(p.startMinute + p.durationMin)}</TableCell>
      <TableCell className="font-medium">{p.classroomName}</TableCell>
      <TableCell>{p.subjectName}</TableCell>
      <TableCell className="hidden sm:table-cell text-muted-foreground text-xs">{p.venue ?? "—"}</TableCell>
      <TableCell className="hidden lg:table-cell">
        {p.invigilators.length ? (
          <div className="flex flex-wrap gap-1">{p.invigilators.map((n) => <Badge key={n} variant="outline" className="text-[10px]">{n}</Badge>)}</div>
        ) : <span className="text-xs text-amber-600">none yet</span>}
      </TableCell>
      <TableCell className="text-right whitespace-nowrap">
        <Button size="sm" variant="outline" className="h-8 gap-1" onClick={() => { setPicked(p.invigilatorIds); setInvOpen(true); }}><Users className="size-3.5" /> Invigilators</Button>
        <Button size="sm" variant="ghost" className="h-8 size-8 p-0 text-destructive ml-1" aria-label={`Delete ${p.subjectName} for ${p.classroomName}`} disabled={busy}
          onClick={() => { if (confirm(`Remove ${p.subjectName} for ${p.classroomName} on ${p.date}?`)) act({ action: "entry.delete", id: p.id }, "Paper removed"); }}>
          <Trash2 className="size-4" />
        </Button>
        <Dialog open={invOpen} onOpenChange={setInvOpen}>
          <DialogContent className="max-w-md">
            <DialogHeader>
              <DialogTitle className="text-base">Invigilators — {p.subjectName}</DialogTitle>
              <DialogDescription>{p.classroomName} · {dayLabel(p.date)} · {hhmm(p.startMinute)}–{hhmm(p.startMinute + p.durationMin)}. A staff member can't be on two overlapping papers.</DialogDescription>
            </DialogHeader>
            <div className="max-h-[46vh] overflow-y-auto nice-scroll space-y-1.5 pr-1">
              {staff.map((s) => (
                <div key={s.id} className="flex items-center justify-between rounded-lg border px-3 py-2">
                  <div className="min-w-0">
                    <div className="text-sm font-medium truncate">{s.name}</div>
                    <div className="text-[10px] text-muted-foreground">{s.category.toLowerCase().replace("_", " ")}{s.phone ? "" : " · no phone"}{s.phone && !s.smsNotify ? " · SMS off" : ""}</div>
                  </div>
                  <Switch checked={picked.includes(s.id)} onCheckedChange={(v) => setPicked((prev) => (v ? [...prev, s.id] : prev.filter((x) => x !== s.id)))} />
                </div>
              ))}
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setInvOpen(false)}>Cancel</Button>
              <Button disabled={busy} onClick={async () => { const ok = await act({ action: "entry.invigilator", id: p.id, invigilatorIds: picked }, "Invigilators saved"); if (ok) setInvOpen(false); }}>
                {busy && <Loader2 className="size-4 animate-spin" />} Save
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </TableCell>
    </TableRow>
  );
}

function AddPaperDialog({ open, onOpenChange, data, busy, act }: {
  open: boolean; onOpenChange: (o: boolean) => void; data: TtData; busy: boolean;
  act: (body: Record<string, unknown>, okMsg: string, close?: () => void) => Promise<boolean>;
}) {
  const today = new Date().toISOString().slice(0, 10);
  const [form, setForm] = useState({ examName: "", classroomId: "", subjectName: "", date: today, start: "2", duration: "90", venue: "" });
  const [picked, setPicked] = useState<string[]>([]);

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg max-h-[92vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle className="text-base">Schedule a paper</DialogTitle>
          <DialogDescription>Clash-guarded: the class, the venue and every invigilator must be free at that hour.</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="space-y-1.5">
            <Label>Exam name</Label>
            <Input value={form.examName} placeholder="End of Term 1 Exams" onChange={(e) => setForm((f) => ({ ...f, examName: e.target.value }))} />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>Class</Label>
              <Select value={form.classroomId} onValueChange={(v) => setForm((f) => ({ ...f, classroomId: v }))}>
                <SelectTrigger><SelectValue placeholder="Pick class" /></SelectTrigger>
                <SelectContent>{data.classrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Subject</Label>
              <Input list="tt-subjects" value={form.subjectName} placeholder="Mathematics" onChange={(e) => setForm((f) => ({ ...f, subjectName: e.target.value }))} />
              <datalist id="tt-subjects">{data.subjects.map((s) => <option key={s} value={s} />)}</datalist>
            </div>
            <div className="space-y-1.5">
              <Label>Date</Label>
              <Input type="date" value={form.date} onChange={(e) => setForm((f) => ({ ...f, date: e.target.value }))} />
            </div>
            <div className="space-y-1.5">
              <Label>Start</Label>
              <Select value={form.start} onValueChange={(v) => setForm((f) => ({ ...f, start: v }))}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {START_TIMES.map((m, i) => <SelectItem key={m} value={String(i)}>{hhmm(m)}</SelectItem>)}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Duration</Label>
              <Select value={form.duration} onValueChange={(v) => setForm((f) => ({ ...f, duration: v }))}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{DURATIONS.map((d) => <SelectItem key={d} value={String(d)}>{d} minutes</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Venue (optional)</Label>
              <Input value={form.venue} placeholder="Main hall" onChange={(e) => setForm((f) => ({ ...f, venue: e.target.value }))} />
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Invigilators (optional)</Label>
            <div className="max-h-32 overflow-y-auto nice-scroll rounded-lg border p-2 space-y-1">
              {data.staff.map((s) => (
                <label key={s.id} className="flex items-center justify-between text-xs px-1.5 py-1 rounded hover:bg-secondary/50 cursor-pointer">
                  <span className="truncate">{s.name}{s.phone ? "" : " · no phone"}</span>
                  <input type="checkbox" className="accent-primary" checked={picked.includes(s.id)} onChange={(e) => setPicked((prev) => (e.target.checked ? [...prev, s.id] : prev.filter((x) => x !== s.id)))} />
                </label>
              ))}
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button disabled={busy} onClick={async () => {
            const startIdx = Number(form.start);
            const sm = Number.isFinite(startIdx) ? START_TIMES[startIdx] ?? 540 : 540;
            const ok = await act({
              action: "entry.add", examName: form.examName, classroomId: form.classroomId,
              subjectName: form.subjectName, date: form.date, startMinute: sm,
              durationMin: Number(form.duration), venue: form.venue || null, invigilatorIds: picked,
            }, "Paper scheduled", () => onOpenChange(false));
            if (ok) { setPicked([]); }
          }}>
            {busy && <Loader2 className="size-4 animate-spin" />} Schedule paper
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function DraftDialog({ open, onOpenChange, data, busy, act }: {
  open: boolean; onOpenChange: (o: boolean) => void; data: TtData; busy: boolean;
  act: (body: Record<string, unknown>, okMsg: string, close?: () => void) => Promise<boolean>;
}) {
  const today = new Date().toISOString().slice(0, 10);
  const [form, setForm] = useState({ examName: "", classroomIds: [] as string[], startDate: today, days: "5", perDay: "2", duration: "90", venue: "" });

  function toggleClass(id: string) {
    setForm((f) => ({ ...f, classroomIds: f.classroomIds.includes(id) ? f.classroomIds.filter((x) => x !== id) : [...f.classroomIds, id] }));
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg max-h-[92vh] overflow-y-auto nice-scroll">
        <DialogHeader>
          <DialogTitle className="text-base">Auto-draft the docket</DialogTitle>
          <DialogDescription>Spreads each class's subjects (from the live timetable) across days and morning/late-morning windows, round-robin — clash-free by construction. Re-drafting replaces that exam's papers.</DialogDescription>
        </DialogHeader>
        <div className="space-y-3">
          <div className="space-y-1.5">
            <Label>Exam name</Label>
            <Input value={form.examName} placeholder="End of Term 1 Exams" onChange={(e) => setForm((f) => ({ ...f, examName: e.target.value }))} />
          </div>
          <div className="space-y-1.5">
            <Label>Classes ({form.classroomIds.length} picked)</Label>
            <div className="max-h-36 overflow-y-auto nice-scroll rounded-lg border p-2 space-y-1">
              {data.classrooms.map((c) => (
                <label key={c.id} className="flex items-center justify-between text-xs px-1.5 py-1 rounded hover:bg-secondary/50 cursor-pointer">
                  <span>{c.name}</span>
                  <input type="checkbox" className="accent-primary" checked={form.classroomIds.includes(c.id)} onChange={() => toggleClass(c.id)} />
                </label>
              ))}
            </div>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label>First day</Label>
              <Input type="date" value={form.startDate} onChange={(e) => setForm((f) => ({ ...f, startDate: e.target.value }))} />
            </div>
            <div className="space-y-1.5">
              <Label>Days</Label>
              <Select value={form.days} onValueChange={(v) => setForm((f) => ({ ...f, days: v }))}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{[1, 2, 3, 4, 5, 6, 7, 8, 10].map((n) => <SelectItem key={n} value={String(n)}>{n} day{n > 1 ? "s" : ""}</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Papers per day</Label>
              <Select value={form.perDay} onValueChange={(v) => setForm((f) => ({ ...f, perDay: v }))}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{[1, 2, 3, 4].map((n) => <SelectItem key={n} value={String(n)}>{n} paper{n > 1 ? "s" : ""} / day</SelectItem>)}</SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
              <Label>Paper duration</Label>
              <Select value={form.duration} onValueChange={(v) => setForm((f) => ({ ...f, duration: v }))}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>{DURATIONS.map((d) => <SelectItem key={d} value={String(d)}>{d} minutes</SelectItem>)}</SelectContent>
              </Select>
            </div>
          </div>
          <div className="space-y-1.5">
            <Label>Venue (optional, all papers)</Label>
            <Input value={form.venue} placeholder="Main hall" onChange={(e) => setForm((f) => ({ ...f, venue: e.target.value }))} />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button disabled={busy} onClick={() => act({
            action: "draft.build", examName: form.examName, classroomIds: form.classroomIds,
            startDate: form.startDate, days: Number(form.days), perDay: Number(form.perDay),
            durationMin: Number(form.duration), venue: form.venue || null,
          }, "Docket drafted", () => onOpenChange(false))}>
            {busy && <Loader2 className="size-4 animate-spin" />} Build draft
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function PrintDocket({ open, onOpenChange, entries, schoolName, filter }: {
  open: boolean; onOpenChange: (o: boolean) => void; entries: TtEntry[]; schoolName: string; filter: string | null;
}) {
  const byDay = useMemo(() => {
    const m = new Map<string, TtEntry[]>();
    for (const e of entries) {
      if (!m.has(e.date)) m.set(e.date, []);
      m.get(e.date)!.push(e);
    }
    return [...m.entries()].sort((a, b) => a[0].localeCompare(b[0]));
  }, [entries]);
  const examName = filter ?? entries[0]?.examName ?? "Examination";
  const hasVenue = entries.some((e) => e.venue);
  const hasInv = entries.some((e) => e.invigilators.length);

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-4xl max-h-[92vh] overflow-y-auto nice-scroll">
        <DialogHeader className="no-print">
          <DialogTitle className="text-base flex items-center gap-2"><Printer className="size-4" /> Exam docket — print</DialogTitle>
          <DialogDescription>One sheet per day · {entries.length} papers{filter ? ` · ${filter}` : ""}</DialogDescription>
        </DialogHeader>
        <div className="print-area">
          {byDay.map(([day, papers]) => (
            <div key={day} className="print-sheet rounded-xl border bg-card p-8 mb-4">
              <div className="text-center border-b-2 border-foreground/80 pb-4">
                <div className="font-bold text-lg uppercase tracking-tight">{schoolName}</div>
                <div className="text-sm font-semibold mt-1">{examName}</div>
                <div className="mt-2 inline-block border-2 border-foreground px-4 py-0.5 font-bold tracking-[0.15em] text-xs uppercase">{dayLabel(day)} — examination docket</div>
                <div className="text-[11px] text-muted-foreground mt-1.5">{papers.length} paper{papers.length > 1 ? "s" : ""} · candidates report 15 minutes before the starting time</div>
              </div>
              <table className="w-full mt-5 text-[13px]" style={{ borderCollapse: "collapse" }}>
                <thead>
                  <tr className="border-b-2 border-foreground/60 text-left">
                    <th className="py-2 pr-2 w-16">No.</th>
                    <th className="py-2 pr-2 w-32">Time</th>
                    <th className="py-2 pr-2">Class</th>
                    <th className="py-2 pr-2">Subject</th>
                    {hasVenue && <th className="py-2 pr-2 w-28">Venue</th>}
                    {hasInv && <th className="py-2">Invigilators</th>}
                  </tr>
                </thead>
                <tbody>
                  {papers.map((p, i) => (
                    <tr key={p.id} className="border-b border-foreground/15">
                      <td className="py-2.5 pr-2 font-mono">{i + 1}</td>
                      <td className="py-2.5 pr-2 font-semibold whitespace-nowrap">{hhmm(p.startMinute)}–{hhmm(p.startMinute + p.durationMin)}</td>
                      <td className="py-2.5 pr-2">{p.classroomName}</td>
                      <td className="py-2.5 pr-2 font-medium">{p.subjectName}</td>
                      {hasVenue && <td className="py-2.5 pr-2">{p.venue ?? "—"}</td>}
                      {hasInv && <td className="py-2.5">{p.invigilators.join(", ") || "To be assigned"}</td>}
                    </tr>
                  ))}
                </tbody>
              </table>
              <p className="text-[11px] text-muted-foreground mt-6 text-center">Silence must be kept in and around the examination hall. Any irregularity must be reported to the chief invigilator immediately.</p>
            </div>
          ))}
        </div>
        <div className="flex justify-end gap-2 mt-3 no-print">
          <Button variant="outline" onClick={() => onOpenChange(false)}>Close</Button>
          <Button className="gap-1.5" onClick={() => window.print()}><Printer className="size-4" /> Print docket</Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}

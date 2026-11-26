"use client";

/* ── wave 38 — Receivables aging + reminder letters (CI3 parity) ────────────
 * aging_report.php        → the four age buckets (Current 0-30 / 31-60 /
 *                           61-90 / Over 90) over every invoice balance, with
 *                           the per-invoice breakdown table and CSV export.
 * drivelist_reminder_templates + print_payment_reminders.php → the reminder
 *                           letter book: {{…}} placeholder templates, a debtor
 *                           run filtered by class and expected-payment date,
 *                           printed 8 letters per A4 landscape sheet exactly
 *                           like the legacy cards.
 * ──────────────────────────────────────────────────────────────────────── */

import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { StatCard, EmptyState, LoadingBlock, FilterBar, FilterSelect, FilterSearch, downloadCsv } from "@/components/ui-helpers";
import { fmtCedi, fmtDate } from "@/lib/format";
import { cn } from "@/lib/utils";
import { toast } from "@/hooks/use-toast";
import { AlarmClock, CalendarClock, FileText, PencilLine, Plus, Printer, ScrollText, Trash2, TrendingUp } from "lucide-react";

/* ── shared types ── */
type Bucket = { key: string; label: string; range: string; amountCents: number; invoices: number; learners: number };
type AgingRow = {
  invoiceNo: string; studentId: string; student: string; classroom: string | null;
  raisedAt: string; days: number; bucket: number; bucketLabel: string; balanceCents: number; status: string;
};
type AgingData = { buckets: Bucket[]; rows: AgingRow[]; totals: { owingCents: number; invoices: number; learners: number } };
type Template = { id: string; name: string; content: string; isDefault: boolean; createdByName: string; updatedAt: string };
type Recipient = {
  studentId: string; name: string; indexNo: string; classroom: string; classroomId: string | null;
  guardianName: string; guardianPhone: string; owingCents: number; oldestDays: number;
};

const PLACEHOLDERS = ["{{student}}", "{{student_id}}", "{{parent_name}}", "{{class_name}}", "{{amount_owe}}", "{{expected_date}}", "{{school_name}}", "{{current_date}}"] as const;
const BUCKET_TONES: Record<number, string> = {
  0: "text-emerald-700 dark:text-emerald-400",
  1: "text-amber-700 dark:text-amber-400",
  2: "text-orange-700 dark:text-orange-400",
  3: "text-red-700 dark:text-red-400",
};
const BUCKET_CHIP: Record<number, string> = {
  0: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200 dark:border-emerald-900",
  1: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-900",
  2: "bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300 border-orange-200 dark:border-orange-900",
  3: "bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300 border-red-200 dark:border-red-900",
};

const ordinal = (d: Date) => d.toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric" });

/* ══════════════════════════════ AGING TAB ══════════════════════════════ */
export function AgingTab({ schoolId, onReminders }: { schoolId: string; onReminders?: () => void }) {
  const [data, setData] = useState<AgingData | null>(null);
  const [search, setSearch] = useState("");
  const [bucket, setBucket] = useState("all");
  const [cls, setCls] = useState("all");

  const load = useCallback(() => {
    apiGet<AgingData>(`/api/school/${schoolId}/ops/receivables?action=aging`)
      .then(setData)
      .catch((e) => toast({ title: "Could not load the aging report", description: e.message, variant: "destructive" }));
  }, [schoolId]);
  useEffect(() => { load(); }, [load]);

  const classrooms = useMemo(() => [...new Set((data?.rows ?? []).map((r) => r.classroom).filter(Boolean) as string[])].sort(), [data]);

  const rows = useMemo(() => (data?.rows ?? []).filter((r) => {
    if (bucket !== "all" && String(r.bucket) !== bucket) return false;
    if (cls !== "all" && r.classroom !== cls) return false;
    if (search.trim()) {
      const q = search.toLowerCase();
      if (!`${r.student} ${r.invoiceNo} ${r.classroom ?? ""}`.toLowerCase().includes(q)) return false;
    }
    return true;
  }), [data, bucket, cls, search]);

  const filtersActive = bucket !== "all" || cls !== "all" || !!search.trim();

  const exportCsv = () => downloadCsv(
    "receivables-aging.csv",
    ["Learner", "Class", "Invoice", "Raised", "Days outstanding", "Age category", "Balance (GHS)", "Status"],
    rows.map((r) => [r.student, r.classroom ?? "", r.invoiceNo, fmtDate(r.raisedAt), r.days, r.bucketLabel, r.balanceCents / 100, r.status]),
  );

  if (!data) return <LoadingBlock label="Totting up the aging book…" />;

  return (
    <div className="space-y-4">
      {/* the four buckets (CI3 aging_report stat grid) */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {data.buckets.map((b, i) => (
          <StatCard
            key={b.key}
            icon={<AlarmClock className="size-4" />}
            label={b.label}
            value={<span className={BUCKET_TONES[i]}>{fmtCedi(b.amountCents, { compact: true })}</span>}
            sub={<span>{b.invoices} invoice{b.invoices === 1 ? "" : "s"} · {b.learners} learner{b.learners === 1 ? "" : "s"}</span>}
            tone={i === 0 ? "default" : i === 1 ? "gold" : "danger"}
          />
        ))}
      </div>

      {/* breakdown table (CI3 detailed breakdown) */}
      <Card>
        <CardContent className="p-0">
          <div className="flex flex-wrap items-center justify-between gap-2 px-4 pt-4">
            <div className="flex items-center gap-2">
              <TrendingUp className="size-4 text-muted-foreground" />
              <h3 className="text-sm font-semibold">Detailed breakdown</h3>
              <Badge variant="secondary" className="text-[10px]">{rows.length} of {data.rows.length}</Badge>
            </div>
            <div className="flex items-center gap-2">
              {onReminders && (
                <Button size="sm" className="gap-1.5" onClick={onReminders}>
                  <FileText className="size-3.5" /> Reminder letters
                </Button>
              )}
              <Button size="sm" variant="outline" className="gap-1.5" onClick={exportCsv} disabled={!rows.length}>
                <Printer className="size-3.5" /> Export CSV
              </Button>
            </div>
          </div>
          <FilterBar active={filtersActive} onClear={() => { setBucket("all"); setCls("all"); setSearch(""); }} count={rows.length} total={data.rows.length}>
            <FilterSearch value={search} onChange={setSearch} placeholder="Learner, invoice no…" />
            <FilterSelect label="Class" value={cls} onChange={setCls} options={[{ value: "all", label: "All" }, ...classrooms.map((c) => ({ value: c, label: c }))]} />
            <FilterSelect label="Age" value={bucket} onChange={setBucket} options={[{ value: "all", label: "All buckets" }, ...data.buckets.map((b, i) => ({ value: String(i), label: b.label }))]} />
          </FilterBar>
          {rows.length === 0 ? (
            <EmptyState icon={<AlarmClock className="size-8" />} title="Nothing in this bucket" hint={data.rows.length ? "Adjust the filters — every filtered-out invoice is still on the books." : "No learner owes anything on invoices right now — the aging book is clean."} />
          ) : (
            <div className="max-h-[420px] overflow-y-auto nice-scroll border-t">
              <Table>
                <TableHeader className="sticky top-0 bg-card z-10">
                  <TableRow>
                    <TableHead>Learner</TableHead>
                    <TableHead className="hidden md:table-cell">Class</TableHead>
                    <TableHead className="hidden sm:table-cell">Invoice</TableHead>
                    <TableHead className="hidden lg:table-cell">Raised</TableHead>
                    <TableHead className="text-right">Days</TableHead>
                    <TableHead className="text-right">Balance</TableHead>
                    <TableHead>Age</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {rows.map((r) => (
                    <TableRow key={r.invoiceNo}>
                      <TableCell className="font-medium">{r.student}</TableCell>
                      <TableCell className="hidden md:table-cell text-muted-foreground">{r.classroom ?? "—"}</TableCell>
                      <TableCell className="hidden sm:table-cell font-mono text-xs">{r.invoiceNo}</TableCell>
                      <TableCell className="hidden lg:table-cell text-muted-foreground text-xs">{fmtDate(r.raisedAt)}</TableCell>
                      <TableCell className="text-right tabular-nums">{r.days}</TableCell>
                      <TableCell className="text-right font-semibold tabular-nums">{fmtCedi(r.balanceCents)}</TableCell>
                      <TableCell><Badge variant="outline" className={cn("text-[10px]", BUCKET_CHIP[r.bucket])}>{r.bucketLabel}</Badge></TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
          <div className="flex items-center justify-between px-4 py-3 border-t bg-secondary/40 text-xs">
            <span className="text-muted-foreground">Owing across the book — {data.totals.invoices} invoices · {data.totals.learners} learners</span>
            <span className="font-bold">{fmtCedi(data.totals.owingCents)}</span>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

/* ═══════════════════ REMINDER LETTERS (print run) ═══════════════════ */
export function ReminderLettersDialog({ open, onOpenChange, schoolId }: { open: boolean; onOpenChange: (o: boolean) => void; schoolId: string }) {
  const { schools } = useStore();
  const school = schools.find((s) => s.id === schoolId);
  const [templates, setTemplates] = useState<Template[] | null>(null);
  const [recipients, setRecipients] = useState<Recipient[] | null>(null);
  const [recClassrooms, setRecClassrooms] = useState<{ id: string; name: string }[]>([]);
  const [templateId, setTemplateId] = useState<string>("");
  const [classroom, setClassroom] = useState("all");
  const [expectedDate, setExpectedDate] = useState(() => {
    const d = new Date(); d.setDate(d.getDate() + 14);
    return d.toISOString().slice(0, 10);
  });
  const [manageOpen, setManageOpen] = useState(false);

  const load = useCallback(() => {
    apiGet<{ templates: Template[] }>(`/api/school/${schoolId}/ops/receivables?action=templates`)
      .then((d) => {
        setTemplates(d.templates);
        setTemplateId((prev) => (d.templates.some((t) => t.id === prev) ? prev : d.templates[0]?.id ?? ""));
      })
      .catch((e) => toast({ title: "Could not load templates", description: e.message, variant: "destructive" }));
    apiGet<{ rows: Recipient[]; classrooms: { id: string; name: string }[] }>(`/api/school/${schoolId}/ops/receivables?action=recipients`)
      .then((d) => { setRecipients(d.rows); setRecClassrooms(d.classrooms); })
      .catch((e) => toast({ title: "Could not load debtors", description: e.message, variant: "destructive" }));
  }, [schoolId]);
  useEffect(() => { if (open) load(); }, [open, load]);

  const runRecipients = useMemo(() => (recipients ?? []).filter((r) => classroom === "all" || r.classroomId === classroom), [recipients, classroom]);
  const template = templates?.find((t) => t.id === templateId) ?? templates?.find((t) => t.isDefault) ?? templates?.[0];

  const pages: Recipient[][] = [];
  for (let i = 0; i < runRecipients.length; i += 8) pages.push(runRecipients.slice(i, i + 8));
  const today = ordinal(new Date());
  const expected = expectedDate ? ordinal(new Date(expectedDate + "T12:00:00")) : "";

  const renderLetter = (r: Recipient) => {
    const map: Record<string, string> = {
      "{{student}}": r.name,
      "{{student_id}}": r.indexNo,
      "{{parent_name}}": r.guardianName || "Parent / Guardian",
      "{{class_name}}": r.classroom,
      "{{amount_owe}}": fmtCedi(r.owingCents),
      "{{expected_date}}": expected,
      "{{school_name}}": school?.name ?? "",
      "{{current_date}}": today,
    };
    let body = template?.content ?? "";
    for (const [k, v] of Object.entries(map)) body = body.split(k).join(v);
    return body;
  };

  return (
    <>
      <Dialog open={open} onOpenChange={onOpenChange}>
        <DialogContent className="max-w-[560px]">
          <DialogHeader className="no-print">
            <DialogTitle className="flex items-center gap-2 text-base"><FileText className="size-4" /> Reminder letters</DialogTitle>
            <DialogDescription>
              Pick a template, an expected-payment date and (optionally) one class — the debtors get an A4 landscape
              print run, 8 letters per sheet, exactly like the legacy reminder cards.
            </DialogDescription>
          </DialogHeader>

          <div className="no-print space-y-3">
            <div className="space-y-1.5">
              <Label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Letter template</Label>
              <div className="flex gap-2">
                <Select value={templateId} onValueChange={setTemplateId}>
                  <SelectTrigger className="flex-1"><SelectValue placeholder={templates?.length ? undefined : "No templates yet"} /></SelectTrigger>
                  <SelectContent>
                    {(templates ?? []).map((t) => (
                      <SelectItem key={t.id} value={t.id}>{t.name}{t.isDefault ? " · default" : ""}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <Button variant="outline" size="icon" aria-label="Manage templates" onClick={() => setManageOpen(true)}><PencilLine className="size-4" /></Button>
              </div>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Expected payment date</Label>
                <Input type="date" value={expectedDate} onChange={(e) => setExpectedDate(e.target.value)} />
              </div>
              <div className="space-y-1.5">
                <Label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Class</Label>
                <Select value={classroom} onValueChange={setClassroom}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All classes</SelectItem>
                    {recClassrooms.map((c) => <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>)}
                  </SelectContent>
                </Select>
              </div>
            </div>

            {recipients === null ? (
              <LoadingBlock label="Loading the debtors…" />
            ) : runRecipients.length === 0 ? (
              <EmptyState title="Nobody to remind" hint="Every learner in this filter has settled their invoices — the letter run is empty." />
            ) : (
              <div className="rounded-lg border bg-secondary/40 px-3 py-2 text-xs flex items-center justify-between">
                <span><b>{runRecipients.length}</b> letter{runRecipients.length === 1 ? "" : "s"} · {Math.ceil(runRecipients.length / 8)} sheet{runRecipients.length > 8 ? "s" : ""} of 8 (A4 landscape)</span>
                <span className="font-semibold">{fmtCedi(runRecipients.reduce((a, r) => a + r.owingCents, 0))}</span>
              </div>
            )}
          </div>

          <DialogFooter className="no-print gap-2">
            <Button variant="outline" onClick={() => onOpenChange(false)}>Close</Button>
            <Button disabled={!runRecipients.length || !template} onClick={() => window.print()}>
              <Printer className="size-4" /> Print {runRecipients.length || ""} letters
            </Button>
          </DialogFooter>

          {/* ── printable run: A4 landscape, 8 letters per sheet — screen keeps
                 the summary, the paper gets the letters (print-only root) ── */}
          {template && runRecipients.length > 0 && (
            <div className="print-area hidden print-only-root">
              <style>{"@media print { @page { size: A4 landscape; margin: 8mm; } }"}</style>
              {pages.map((page, pi) => (
                <div key={pi} className="print-sheet grid grid-cols-2 grid-rows-4 gap-2" style={{ width: "272mm", minHeight: "180mm" }}>
                  {page.map((r) => (
                    <div key={r.studentId} className="border-2 border-neutral-800 rounded-sm p-2 flex flex-col text-[9.5px] leading-snug overflow-hidden">
                      <div className="flex items-start justify-between border-b-2 border-neutral-800 pb-1 mb-1.5">
                        <div>
                          <div className="font-bold text-[11px] uppercase tracking-wide">{school?.name}</div>
                          <div className="text-[8px] text-neutral-600">{[school?.town, school?.region].filter(Boolean).join(", ")}{school?.phone ? ` · ${school.phone}` : ""}</div>
                        </div>
                        <div className="text-right text-[8px] text-neutral-600">
                          <div className="font-bold text-neutral-800">FEE REMINDER</div>
                          <div>{today}</div>
                        </div>
                      </div>
                      <div className="flex-1 whitespace-pre-wrap">{renderLetter(r)}</div>
                      <div className="flex items-end justify-between border-t pt-1 mt-1.5 text-[8px] text-neutral-600">
                        <div className="border-t border-neutral-800 w-32 text-center pt-0.5">Bursar / Accounts</div>
                        <div>Balance: <b className="text-neutral-900">{fmtCedi(r.owingCents)}</b> · Oldest invoice {r.oldestDays} days</div>
                      </div>
                    </div>
                  ))}
                </div>
              ))}
            </div>
          )}
        </DialogContent>
      </Dialog>

      <TemplateManager open={manageOpen} onOpenChange={setManageOpen} schoolId={schoolId} onSaved={load} defaultContent={template?.content} />
    </>
  );
}

/* ═══════════════════ TEMPLATE MANAGER (drivelist_reminder_templates CRUD) ═══════════════════ */
function TemplateManager({ open, onOpenChange, schoolId, onSaved, defaultContent }: {
  open: boolean; onOpenChange: (o: boolean) => void; schoolId: string; onSaved: () => void; defaultContent?: string;
}) {
  const [templates, setTemplates] = useState<Template[] | null>(null);
  const [editing, setEditing] = useState<Template | "new" | null>(null);
  const [name, setName] = useState("");
  const [content, setContent] = useState("");
  const [isDefault, setIsDefault] = useState(false);
  const [busy, setBusy] = useState(false);

  const load = useCallback(() => {
    apiGet<{ templates: Template[] }>(`/api/school/${schoolId}/ops/receivables?action=templates`).then((d) => setTemplates(d.templates)).catch(() => setTemplates([]));
  }, [schoolId]);
  useEffect(() => { if (open) { load(); setEditing(null); } }, [open, load]);

  const startEdit = (t: Template | "new") => {
    setEditing(t);
    if (t === "new") {
      setName("");
      setContent(`Dear {{parent_name}},\n\nRE: OUTSTANDING FEES — {{student}} ({{student_id}}, {{class_name}})\n\n`);
      setIsDefault(false);
    } else {
      setName(t.name); setContent(t.content); setIsDefault(t.isDefault);
    }
  };

  const save = async () => {
    if (!name.trim() || !content.trim()) { toast({ title: "Name and letter body are required", variant: "destructive" }); return; }
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}/ops/receivables`, {
        action: "template-save",
        id: editing !== "new" && editing ? editing.id : undefined,
        name, content, isDefault,
      });
      toast({ title: editing === "new" ? "Template created" : "Template saved" });
      setEditing(null); load(); onSaved();
    } catch (e: any) { toast({ title: "Save failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  };

  const remove = async (t: Template) => {
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}/ops/receivables`, { action: "template-delete", id: t.id });
      toast({ title: "Template deleted" });
      load(); onSaved();
    } catch (e: any) { toast({ title: "Delete failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  };

  const setDefault = async (t: Template) => {
    try {
      await apiPost(`/api/school/${schoolId}/ops/receivables`, { action: "template-set-default", id: t.id });
      toast({ title: `${t.name} is now the default` });
      load(); onSaved();
    } catch (e: any) { toast({ title: "Failed", description: e.message, variant: "destructive" }); }
  };

  return (
    <Dialog open={open} onOpenChange={(o) => { onOpenChange(o); if (!o) setEditing(null); }}>
      <DialogContent className="max-w-[600px] max-h-[88vh] overflow-y-auto nice-scroll">
        <DialogHeader className="no-print">
          <DialogTitle className="flex items-center gap-2 text-base"><ScrollText className="size-4" /> Reminder templates</DialogTitle>
          <DialogDescription>The letters every debtor receives. Tap a placeholder to insert it at the cursor.</DialogDescription>
        </DialogHeader>

        {editing ? (
          <div className="no-print space-y-3">
            <div className="space-y-1.5">
              <Label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Template name</Label>
              <Input value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Term 2 final demand" />
            </div>
            <div className="space-y-1.5">
              <Label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Letter body</Label>
              <Textarea value={content} onChange={(e) => setContent(e.target.value)} rows={10} className="font-mono text-xs" />
              <div className="flex flex-wrap gap-1.5 pt-1">
                {PLACEHOLDERS.map((p) => (
                  <button
                    key={p}
                    type="button"
                    className="rounded border bg-secondary px-1.5 py-0.5 font-mono text-[10px] hover:bg-secondary/70"
                    onClick={() => setContent((c) => c + p)}
                  >
                    {p.replace(/\{\{|\}\}/g, "")}
                  </button>
                ))}
              </div>
            </div>
            <label className="flex items-center gap-2 text-xs">
              <input type="checkbox" checked={isDefault} onChange={(e) => setIsDefault(e.target.checked)} className="size-3.5 accent-neutral-900" />
              Make this the default template
            </label>
            <div className="flex justify-end gap-2">
              <Button variant="outline" size="sm" onClick={() => setEditing(null)}>Cancel</Button>
              <Button size="sm" onClick={save} disabled={busy}>{busy ? "Saving…" : "Save template"}</Button>
            </div>
          </div>
        ) : (
          <div className="no-print space-y-2">
            {templates === null ? (
              <LoadingBlock label="Loading templates…" />
            ) : templates.length === 0 ? (
              <EmptyState title="No templates yet" hint="Create the school's first reminder letter — the standard one is one tap away." />
            ) : (
              templates.map((t) => (
                <div key={t.id} className="rounded-lg border px-3 py-2.5 flex items-start justify-between gap-2">
                  <div className="min-w-0">
                    <div className="flex items-center gap-2 text-sm font-medium">
                      {t.name}
                      {t.isDefault && <Badge variant="secondary" className="text-[10px]">default</Badge>}
                    </div>
                    <p className="mt-0.5 line-clamp-2 text-[11px] text-muted-foreground whitespace-pre-wrap">{t.content}</p>
                    <p className="mt-1 text-[10px] text-muted-foreground">by {t.createdByName} · updated {fmtDate(t.updatedAt)}</p>
                  </div>
                  <div className="flex flex-col gap-1 shrink-0">
                    <Button size="sm" variant="outline" className="h-7 gap-1 text-xs" onClick={() => startEdit(t)}><PencilLine className="size-3" /> Edit</Button>
                    {!t.isDefault && <Button size="sm" variant="ghost" className="h-7 gap-1 text-xs" onClick={() => setDefault(t)}><CalendarClock className="size-3" /> Default</Button>}
                    <Button size="sm" variant="ghost" className="h-7 gap-1 text-xs text-red-600 hover:text-red-700" onClick={() => remove(t)} disabled={busy}><Trash2 className="size-3" /> Delete</Button>
                  </div>
                </div>
              ))
            )}
            <Button variant="outline" size="sm" className="mt-2 gap-1.5 w-full" onClick={() => startEdit("new")}><Plus className="size-3.5" /> New template</Button>
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}


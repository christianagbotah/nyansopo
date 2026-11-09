"use client";
import { useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiPost } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Checkbox } from "@/components/ui/checkbox";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectGroup, SelectLabel, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { EmptyState } from "@/components/ui-helpers";
import { fmtCedi } from "@/lib/format";
import { toast } from "@/hooks/use-toast";
import { cn } from "@/lib/utils";
import { Plus, Save, Trash2, PencilLine, XCircle, ListChecks, Users, Search, CheckSquare, Square, ClipboardPen, FileWarning, Eye, Loader2, ChevronsUpDown, AlertCircle, GraduationCap, Building2 } from "lucide-react";

/* ── CI3 student_payment.php parity — the "Add Billing Item" and
   "Create Invoice" tabs of the Manage Student Invoice page. ── */

export type BillItemRow = {
  id: string; title: string; category: string; classCategory: string;
  specificClassIds: string[]; amountCents: number; description: string | null; active: boolean;
};
export type RosterRow = { id: string; name: string; index: string; classroomId: string; classroom: string; grade: string; guardian: string | null; phone: string | null; residence?: string };
export type ClassRow = { id: string; name: string; gradeLevel: string; stage?: string | null };

const CATEGORIES = ["Tuition", "Exams", "Books", "ICT", "Meals", "Transport", "Sports", "Uniform", "Graduation", "Other"];
const CLASS_CATEGORIES = [
  { value: "ALL", label: "All Classes" },
  { value: "CRECHE", label: "Creche" },
  { value: "NURSERY", label: "Nursery" },
  { value: "KG", label: "Kindergarten" },
  { value: "PRIMARY", label: "Primary" },
  { value: "JHS", label: "JHS" },
  { value: "SENIOR", label: "Senior High" },
];
const CATEGORY_TONE: Record<string, string> = {
  Tuition: "bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300",
  Exams: "bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300",
  Books: "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
  ICT: "bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300",
  Meals: "bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300",
  Transport: "bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300",
  Sports: "bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300",
  Uniform: "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300",
  Graduation: "bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-300",
  Other: "bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300",
};

type Draft = { title: string; category: string; classCategory: string; specificClassIds: string[]; amount: string; description: string };
const EMPTY_DRAFT: Draft = { title: "", category: "Tuition", classCategory: "ALL", specificClassIds: [], amount: "", description: "" };

/* inline field validation — red hints under the row, CI3-style required marks */
function draftErrors(d: Draft): { title?: string; amount?: string } {
  const errs: { title?: string; amount?: string } = {};
  if (!d.title.trim()) errs.title = "Item title is required";
  const c = parseFloat(d.amount);
  if (!d.amount.trim() || !Number.isFinite(c)) errs.amount = "Enter an amount";
  else if (c <= 0) errs.amount = "Amount must be above zero";
  return errs;
}

/* the six entry fields in ONE row (wraps on narrow screens) — shared by the
   add form and the edit modal so both always look and validate the same */
function BillItemFields({ draft, setDraft, errors, classrooms, idPrefix }: {
  draft: Draft;
  setDraft: React.Dispatch<React.SetStateAction<Draft>>;
  errors: { title?: string; amount?: string };
  classrooms: ClassRow[];
  idPrefix: string;
}) {
  return (
    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-[2.1fr_1.1fr_1fr_1.3fr_1.3fr_1.8fr]">
      <div className="space-y-1.5">
        <Label htmlFor={`${idPrefix}-title`}>Bill item title *</Label>
        <Input id={`${idPrefix}-title`} value={draft.title} onChange={(e) => setDraft({ ...draft, title: e.target.value })} placeholder="e.g. TUITION FEE" className={cn("uppercase", errors.title && "border-destructive focus-visible:ring-destructive")} aria-invalid={!!errors.title} />
        {errors.title && <p className="text-[11px] text-destructive flex items-center gap-1"><AlertCircle className="size-3" /> {errors.title}</p>}
      </div>
      <div className="space-y-1.5">
        <Label>Category *</Label>
        <Select value={draft.category} onValueChange={(v) => setDraft({ ...draft, category: v })}>
          <SelectTrigger><SelectValue /></SelectTrigger>
          <SelectContent>{CATEGORIES.map((c) => <SelectItem key={c} value={c}>{c}</SelectItem>)}</SelectContent>
        </Select>
      </div>
      <div className="space-y-1.5">
        <Label htmlFor={`${idPrefix}-amount`}>Amount (₵) *</Label>
        <Input id={`${idPrefix}-amount`} type="number" min="0" step="0.01" value={draft.amount} onChange={(e) => setDraft({ ...draft, amount: e.target.value })} placeholder="250.00" className={cn("text-right", errors.amount && "border-destructive focus-visible:ring-destructive")} aria-invalid={!!errors.amount} />
        {errors.amount && <p className="text-[11px] text-destructive flex items-center gap-1"><AlertCircle className="size-3" /> {errors.amount}</p>}
      </div>
      <div className="space-y-1.5">
        <Label>Class category</Label>
        <Select value={draft.classCategory} onValueChange={(v) => setDraft({ ...draft, classCategory: v, specificClassIds: v === "ALL" ? [] : draft.specificClassIds })}>
          <SelectTrigger><SelectValue /></SelectTrigger>
          <SelectContent>{CLASS_CATEGORIES.map((c) => <SelectItem key={c.value} value={c.value}>{c.label}{c.value === "ALL" ? " (default)" : ""}</SelectItem>)}</SelectContent>
        </Select>
        {draft.specificClassIds.length > 0 && <p className="text-[11px] text-muted-foreground">Overridden by the specific classes</p>}
      </div>
      <div className="space-y-1.5">
        <Label>Specific classes</Label>
        <Popover>
          <PopoverTrigger asChild>
            <Button type="button" variant="outline" className="w-full justify-between font-normal">
              {draft.specificClassIds.length ? `${draft.specificClassIds.length} class${draft.specificClassIds.length > 1 ? "es" : ""} picked` : "All (optional)"}
              <ChevronsUpDown className="size-3.5 opacity-60" />
            </Button>
          </PopoverTrigger>
          <PopoverContent className="w-64 p-2" align="start">
            <div className="max-h-64 overflow-y-auto nice-scroll space-y-0.5">
              {classrooms.length === 0 && <p className="text-xs text-muted-foreground px-2 py-3">No classes yet</p>}
              {classrooms.map((c) => {
                const on = draft.specificClassIds.includes(c.id);
                return (
                  <button key={c.id} type="button"
                    onClick={() => setDraft((d) => ({ ...d, specificClassIds: on ? d.specificClassIds.filter((x) => x !== c.id) : [...d.specificClassIds, c.id] }))}
                    className={cn("flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-xs transition-colors", on ? "bg-primary/10 text-primary font-medium" : "hover:bg-secondary")}>
                    {on ? <CheckSquare className="size-3.5 shrink-0" /> : <Square className="size-3.5 shrink-0 text-muted-foreground" />}
                    <span className="truncate">{c.name}</span>
                  </button>
                );
              })}
            </div>
            {draft.specificClassIds.length > 0 && (
              <Button type="button" variant="ghost" size="sm" className="mt-1 w-full h-7 text-xs" onClick={() => setDraft({ ...draft, specificClassIds: [] })}>Clear classes</Button>
            )}
          </PopoverContent>
        </Popover>
      </div>
      <div className="space-y-1.5">
        <Label htmlFor={`${idPrefix}-desc`}>Description</Label>
        <Input id={`${idPrefix}-desc`} value={draft.description} onChange={(e) => setDraft({ ...draft, description: e.target.value })} placeholder="Shown on the invoice next to the item" />
      </div>
    </div>
  );
}

/* ════════════════ TAB 1 — BILL ITEMS (the reusable fee catalog) ════════════════ */
export function BillItemsTab({ billItems, classrooms, onChanged }: {
  billItems: BillItemRow[];
  classrooms: ClassRow[];
  onChanged: () => void;
}) {
  const { currentSchoolId } = useStore();
  const [draft, setDraft] = useState<Draft>(EMPTY_DRAFT);
  const [touched, setTouched] = useState(false);
  const [busy, setBusy] = useState(false);
  const [editModal, setEditModal] = useState<BillItemRow | null>(null); // single-row edit → modal
  const [editDraft, setEditDraft] = useState<Draft>(EMPTY_DRAFT);
  const [editing, setEditing] = useState<Set<string>>(new Set()); // rows in bulk inline edit mode
  const [edits, setEdits] = useState<Record<string, { title: string; amount: string; category: string }>>({});
  const [checked, setChecked] = useState<Set<string>>(new Set());
  const [confirmDelete, setConfirmDelete] = useState<Set<string> | null>(null);
  const [search, setSearch] = useState("");

  const classById = useMemo(() => new Map(classrooms.map((c) => [c.id, c.name])), [classrooms]);
  const visible = useMemo(() => {
    const q = search.trim().toLowerCase();
    if (!q) return billItems;
    return billItems.filter((b) => `${b.title} ${b.category} ${b.description ?? ""}`.toLowerCase().includes(q));
  }, [billItems, search]);

  const addErrors = touched ? draftErrors(draft) : {};
  const editErrors = editModal ? draftErrors(editDraft) : {};

  function resetForm() { setDraft(EMPTY_DRAFT); setTouched(false); }

  async function save() {
    if (!currentSchoolId) return;
    setTouched(true);
    const errs = draftErrors(draft);
    if (Object.keys(errs).length) return toast({ title: "Check the highlighted fields", description: Object.values(errs)[0], variant: "destructive" });
    const amountCents = Math.round(parseFloat(draft.amount) * 100);
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/fees`, {
        action: "bill-item-save",
        title: draft.title, category: draft.category, classCategory: draft.classCategory,
        specificClassIds: draft.specificClassIds, amountCents, description: draft.description,
      });
      toast({ title: "Bill item added", description: `${draft.title.toUpperCase()} — ${fmtCedi(amountCents)}` });
      resetForm();
      onChanged();
    } catch (e: any) { toast({ title: "Save failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  function openEdit(b: BillItemRow) {
    setEditDraft({ title: b.title, category: b.category, classCategory: b.classCategory, specificClassIds: b.specificClassIds, amount: (b.amountCents / 100).toFixed(2), description: b.description ?? "" });
    setEditModal(b);
  }

  async function saveEdit() {
    if (!currentSchoolId || !editModal) return;
    const errs = draftErrors(editDraft);
    if (Object.keys(errs).length) return toast({ title: "Check the highlighted fields", description: Object.values(errs)[0], variant: "destructive" });
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/fees`, {
        action: "bill-item-save", id: editModal.id,
        title: editDraft.title, category: editDraft.category, classCategory: editDraft.classCategory,
        specificClassIds: editDraft.specificClassIds, amountCents: Math.round(parseFloat(editDraft.amount) * 100), description: editDraft.description,
      });
      toast({ title: "Bill item updated", description: `${editDraft.title.toUpperCase()} — ${fmtCedi(Math.round(parseFloat(editDraft.amount) * 100))}` });
      setEditModal(null);
      onChanged();
    } catch (e: any) { toast({ title: "Save failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  async function remove(ids: Set<string>) {
    if (!currentSchoolId) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${currentSchoolId}/fees`, { action: ids.size > 1 ? "bill-items-bulk-delete" : "bill-item-delete", ids: [...ids], id: [...ids][0] });
      toast({ title: `Deleted ${ids.size} item${ids.size > 1 ? "s" : ""}` });
      setChecked(new Set()); setConfirmDelete(null); setEditing(new Set());
      onChanged();
    } catch (e: any) { toast({ title: "Delete failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  function startInline(ids: Set<string>) {
    const next = new Set<string>(ids);
    setEditing(next);
    const map: Record<string, { title: string; amount: string; category: string }> = {};
    for (const id of ids) {
      const b = billItems.find((x) => x.id === id);
      if (b) map[id] = { title: b.title, amount: (b.amountCents / 100).toFixed(2), category: b.category };
    }
    setEdits(map);
  }

  async function saveInline() {
    if (!currentSchoolId) return;
    const updates = [...editing].map((id) => {
      const e = edits[id];
      return { id, title: e?.title, category: e?.category, amountCents: Math.round(parseFloat(e?.amount || "0") * 100) };
    }).filter((u) => Number.isFinite(u.amountCents) && u.amountCents > 0);
    setBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/fees`, { action: "bill-items-bulk-save", updates });
      toast({ title: `Saved ${res.saved} item${res.saved > 1 ? "s" : ""}` });
      setEditing(new Set()); setChecked(new Set());
      onChanged();
    } catch (e: any) { toast({ title: "Save failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  const allChecked = visible.length > 0 && visible.every((b) => checked.has(b.id));

  return (
    <div className="space-y-4">
      {/* one-row add form — every input on the same line, actions right-aligned below */}
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base flex items-center gap-2">
            <ListChecks className="size-4 text-primary" /> Add billing item
            <span className="ml-auto text-xs font-normal text-muted-foreground hidden lg:block">Catalog items feed the Create Invoices wizard</span>
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <BillItemFields draft={draft} setDraft={setDraft} errors={addErrors} classrooms={classrooms} idPrefix="add" />
          <div className="flex flex-wrap items-center gap-2 pt-1">
            {Object.keys(addErrors).length > 0 && touched && (
              <span className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="size-3.5" /> Fix the highlighted fields to continue</span>
            )}
            <div className="ml-auto flex gap-2">
              {touched && <Button variant="ghost" onClick={resetForm} disabled={busy}>Clear</Button>}
              <Button onClick={save} disabled={busy} className="gap-1.5"><Plus className="size-4" /> Add bill item</Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* bulk toolbar */}
      <div className={cn("flex flex-wrap items-center gap-2 rounded-lg border bg-blue-50 dark:bg-blue-950/40 px-3 py-2 text-sm", checked.size === 0 && "hidden")}>
        <span className="font-medium text-blue-800 dark:text-blue-200">{checked.size} item{checked.size > 1 ? "s" : ""} selected</span>
        <div className="ml-auto flex flex-wrap gap-2">
          {editing.size === 0 ? (
            <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={() => startInline(checked)} disabled={!checked.size}>
              <PencilLine className="size-3.5" /> Edit selected
            </Button>
          ) : (
            <>
              <Button size="sm" className="h-8 gap-1.5" onClick={saveInline} disabled={busy}><Save className="size-3.5" /> Save all changes</Button>
              <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={() => { setEditing(new Set()); setChecked(new Set()); }}><XCircle className="size-3.5" /> Cancel</Button>
            </>
          )}
          <Button size="sm" variant="outline" className="h-8 gap-1.5 text-destructive hover:text-destructive" onClick={() => setConfirmDelete(checked)} disabled={!checked.size}>
            <Trash2 className="size-3.5" /> Delete selected
          </Button>
          <Button size="sm" variant="ghost" className="h-8" onClick={() => setChecked(new Set())}>Clear selection</Button>
        </div>
      </div>

      {/* catalog table */}
      <Card>
        <CardContent className="p-0">
          <div className="flex items-center gap-2 border-b px-3 py-2">
            <div className="relative flex-1 max-w-xs">
              <Search className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
              <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Filter items…" className="h-8 pl-8 text-xs" />
            </div>
            <span className="text-xs text-muted-foreground">{billItems.length} catalog item{billItems.length !== 1 ? "s" : ""}</span>
          </div>
          <div className="overflow-x-auto nice-scroll max-h-[520px]">
            <table className="w-full text-sm">
              <thead className="sticky top-0 bg-secondary/70 backdrop-blur z-10">
                <tr className="text-left border-b">
                  <th className="px-3 py-2.5 w-8">
                    <Checkbox checked={allChecked} onCheckedChange={(v) => setChecked(v ? new Set(visible.map((b) => b.id)) : new Set())} aria-label="Select all" />
                  </th>
                  <th className="px-3 py-2.5 font-medium w-10">#</th>
                  <th className="px-3 py-2.5 font-medium">Title</th>
                  <th className="px-3 py-2.5 font-medium">Category</th>
                  <th className="px-3 py-2.5 font-medium hidden md:table-cell">Applies to</th>
                  <th className="px-3 py-2.5 font-medium text-right">Amount</th>
                  <th className="px-3 py-2.5 font-medium hidden lg:table-cell">Description</th>
                  <th className="px-3 py-2.5 font-medium text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                {visible.map((b, i) => {
                  const isInEdit = editing.has(b.id);
                  const e = edits[b.id];
                  return (
                    <tr key={b.id} className={cn("border-b last:border-0 hover:bg-secondary/30", checked.has(b.id) && "bg-blue-50/50 dark:bg-blue-950/20")}>
                      <td className="px-3 py-2">
                        <Checkbox
                          checked={checked.has(b.id)}
                          onCheckedChange={(v) => setChecked((s) => { const n = new Set(s); v ? n.add(b.id) : n.delete(b.id); return n; })}
                          aria-label={`Select ${b.title}`}
                        />
                      </td>
                      <td className="px-3 py-2 text-muted-foreground">{i + 1}</td>
                      <td className="px-3 py-2 font-medium">
                        {isInEdit ? (
                          <Input value={e?.title ?? b.title} onChange={(ev) => setEdits((m) => ({ ...m, [b.id]: { ...m[b.id], title: ev.target.value } }))} className="h-7 w-44 text-xs uppercase" />
                        ) : b.title}
                      </td>
                      <td className="px-3 py-2">
                        {isInEdit ? (
                          <Select value={e?.category ?? b.category} onValueChange={(v) => setEdits((m) => ({ ...m, [b.id]: { ...m[b.id], category: v } }))}>
                            <SelectTrigger className="h-7 w-28 text-xs"><SelectValue /></SelectTrigger>
                            <SelectContent>{CATEGORIES.map((c) => <SelectItem key={c} value={c}>{c}</SelectItem>)}</SelectContent>
                          </Select>
                        ) : (
                          <span className={cn("inline-block rounded-full px-2.5 py-0.5 text-[11px] font-semibold", CATEGORY_TONE[b.category] ?? CATEGORY_TONE.Other)}>{b.category}</span>
                        )}
                      </td>
                      <td className="px-3 py-2 hidden md:table-cell">
                        {b.specificClassIds.length ? (
                          <div className="flex flex-wrap gap-1">
                            {b.specificClassIds.slice(0, 3).map((id) => <Badge key={id} variant="outline" className="text-[10px] px-1.5">{classById.get(id) ?? "?"}</Badge>)}
                            {b.specificClassIds.length > 3 && <span className="text-[10px] text-muted-foreground">+{b.specificClassIds.length - 3}</span>}
                          </div>
                        ) : (
                          <span className="text-xs text-muted-foreground">{b.classCategory === "ALL" ? "All classes" : b.classCategory}</span>
                        )}
                      </td>
                      <td className="px-3 py-2 text-right tabular-nums font-semibold">
                        {isInEdit ? (
                          <Input type="number" step="0.01" min="0" value={e?.amount ?? (b.amountCents / 100).toFixed(2)} onChange={(ev) => setEdits((m) => ({ ...m, [b.id]: { ...m[b.id], amount: ev.target.value } }))} className="h-7 w-24 text-right text-xs" />
                        ) : fmtCedi(b.amountCents)}
                      </td>
                      <td className="px-3 py-2 hidden lg:table-cell text-xs text-muted-foreground max-w-[220px] truncate" title={b.description ?? ""}>{b.description ?? "—"}</td>
                      <td className="px-3 py-2 text-right">
                        <div className="flex justify-end gap-1">
                          {!isInEdit && (
                            <>
                              <Button size="sm" variant="ghost" className="size-7 p-0" title="Edit in a dialog" onClick={() => openEdit(b)}>
                                <PencilLine className="size-3.5" />
                              </Button>
                              <Button size="sm" variant="ghost" className="size-7 p-0 text-destructive hover:text-destructive" title="Delete" onClick={() => setConfirmDelete(new Set([b.id]))}>
                                <Trash2 className="size-3.5" />
                              </Button>
                            </>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}
                {!visible.length && <tr><td colSpan={8}><EmptyState icon={<ListChecks className="size-5" />} title="No bill items yet" hint="Add the reusable fee items above — they feed the Create Invoices wizard." /></td></tr>}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      {/* edit modal — opens from the row's pencil action */}
      <Dialog open={!!editModal} onOpenChange={(o) => !o && setEditModal(null)}>
        <DialogContent className="max-w-4xl">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><PencilLine className="size-4 text-primary" /> Edit bill item{editModal ? ` — ${editModal.title}` : ""}</DialogTitle>
            <DialogDescription>Change the catalog entry — invoices already billed keep the amounts they were billed with.</DialogDescription>
          </DialogHeader>
          <BillItemFields draft={editDraft} setDraft={setEditDraft} errors={editErrors} classrooms={classrooms} idPrefix="edit" />
          <DialogFooter>
            <Button variant="outline" onClick={() => setEditModal(null)} disabled={busy}>Cancel</Button>
            <Button onClick={saveEdit} disabled={busy || Object.keys(editErrors).length > 0} className="gap-1.5"><Save className="size-4" /> Save changes</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={!!confirmDelete} onOpenChange={(o) => !o && setConfirmDelete(null)}>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><FileWarning className="size-5 text-destructive" /> Delete {confirmDelete?.size} bill item{confirmDelete && confirmDelete.size > 1 ? "s" : ""}?</DialogTitle>
            <DialogDescription>Items already billed onto invoices are refused — invoices keep the amounts they were billed with.</DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setConfirmDelete(null)}>Cancel</Button>
            <Button variant="destructive" onClick={() => confirmDelete && remove(confirmDelete)} disabled={busy}>Delete</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

/* ════════════════ TAB 2 — CREATE INVOICES (mass invoice wizard) ════════════════ */
type ItemRow = { key: string; title: string; amount: string; description: string };

export function CreateInvoicesTab({ billItems, roster, terms, termId, onChanged }: {
  billItems: BillItemRow[];
  roster: RosterRow[];
  terms: { id: string; name: string; isCurrent: boolean }[];
  termId: string | null;
  onChanged: () => void;
}) {
  const { currentSchoolId } = useStore();
  const [scope, setScope] = useState<string>("CLASS"); // CLASS:<id> | ALL | BOARDING | DAY (CI3 students_category)
  const [picked, setPicked] = useState<Set<string>>(new Set());
  const [search, setSearch] = useState("");
  const [rows, setRows] = useState<ItemRow[]>([{ key: "r1", title: "", amount: "", description: "" }]);
  const [dueInDays, setDueInDays] = useState("14");
  const [chosenTerm, setChosenTerm] = useState<string>(termId ?? "");
  const [busy, setBusy] = useState(false);
  const [confirm, setConfirm] = useState(false);

  useEffect(() => { if (termId && !chosenTerm) setChosenTerm(termId); }, [termId]);

  const classGroups = useMemo(() => {
    const map = new Map<string, RosterRow[]>();
    for (const r of roster) {
      const arr = map.get(r.classroomId) ?? [];
      arr.push(r);
      map.set(r.classroomId, arr);
    }
    return map;
  }, [roster]);

  const candidates = useMemo(() => {
    if (scope === "ALL") return roster;
    if (scope === "BOARDING") return roster.filter((r) => r.residence === "BOARDING");
    if (scope === "DAY") return roster.filter((r) => (r.residence ?? "DAY") !== "BOARDING");
    const clsId = scope.startsWith("CLASS:") ? scope.slice(6) : null;
    if (!clsId) return [];
    return roster.filter((r) => r.classroomId === clsId);
  }, [scope, roster]);

  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase();
    if (!q) return candidates;
    return candidates.filter((r) => `${r.name} ${r.index} ${r.classroom}`.toLowerCase().includes(q));
  }, [candidates, search]);

  const total = rows.reduce((s, r) => s + Math.round((parseFloat(r.amount || "0") || 0) * 100), 0);
  const rowValid = (r: ItemRow) => r.title.trim() && Number.isFinite(parseFloat(r.amount)) && parseFloat(r.amount) > 0;
  const [tried, setTried] = useState(false); // show field hints once Create is attempted

  function setRow(key: string, patch: Partial<ItemRow>) { setRows((rs) => rs.map((r) => (r.key === key ? { ...r, ...patch } : r))); }

  async function create() {
    if (!currentSchoolId) return;
    const items = rows.filter(rowValid).map((r) => ({ title: r.title.trim(), amountCents: Math.round(parseFloat(r.amount) * 100), description: r.description.trim() || undefined }));
    if (!items.length || !picked.size) return;
    setBusy(true);
    try {
      const res = await apiPost<any>(`/api/school/${currentSchoolId}/fees`, {
        action: "mass-invoice-create",
        studentIds: [...picked],
        items,
        termId: chosenTerm || undefined,
        dueInDays: parseInt(dueInDays || "14", 10),
      });
      toast({
        title: `${res.created} invoice${res.created !== 1 ? "s" : ""} created · ${res.updated} updated`,
        description: `${fmtCedi(res.totalBilled)} billed${res.discounted ? ` · ${res.discounted} discounted` : ""}${res.creditsApplied ? ` · ${res.creditsApplied} credit-applied` : ""}${res.skipped ? ` · ${res.skipped} already fully billed` : ""}.`,
      });
      setConfirm(false);
      setPicked(new Set());
      setTried(false);
      onChanged();
    } catch (e: any) { toast({ title: "Create failed", description: e.message, variant: "destructive" }); }
    finally { setBusy(false); }
  }

  return (
    <div className="space-y-4">
      {/* wizard header — date/term/scope */}
      <Card>
        <CardContent className="pt-5 pb-4 grid gap-3 md:grid-cols-4">
          <div className="space-y-1.5 md:col-span-2">
            <Label>Billing term</Label>
            <Select value={chosenTerm} onValueChange={setChosenTerm}>
              <SelectTrigger><SelectValue placeholder="Current term" /></SelectTrigger>
              <SelectContent>{terms.map((t) => <SelectItem key={t.id} value={t.id}>{t.name}{t.isCurrent ? " (current)" : ""}</SelectItem>)}</SelectContent>
            </Select>
          </div>
          <div className="space-y-1.5">
            <Label>Payment due (days)</Label>
            <Input type="number" min="0" max="120" value={dueInDays} onChange={(e) => setDueInDays(e.target.value)} />
          </div>
          <div className="space-y-1.5">
            <Label>Students category</Label>
            <Select value={scope} onValueChange={setScope}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="ALL">All Students ({roster.length})</SelectItem>
                <SelectItem value="BOARDING">
                  <span className="flex items-center gap-1.5"><Building2 className="size-3.5" /> All Boarding Students ({roster.filter((r) => r.residence === "BOARDING").length})</span>
                </SelectItem>
                <SelectItem value="DAY">
                  <span className="flex items-center gap-1.5"><GraduationCap className="size-3.5" /> All Day Students ({roster.filter((r) => (r.residence ?? "DAY") !== "BOARDING").length})</span>
                </SelectItem>
                <SelectGroup>
                  <SelectLabel>By class</SelectLabel>
                  {[...classGroups.entries()].map(([clsId, list]) => (
                    <SelectItem key={clsId} value={`CLASS:${clsId}`}>{list[0].classroom} ({list.length})</SelectItem>
                  ))}
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      <div className="grid gap-4 lg:grid-cols-7">
        {/* students list */}
        <Card className="lg:col-span-3">
          <CardHeader className="pb-2 flex-row items-center justify-between space-y-0">
            <CardTitle className="text-base flex items-center gap-2"><Users className="size-4 text-primary" /> Students list</CardTitle>
            <div className="flex gap-1.5">
              <Button size="sm" variant="ghost" className="h-7 gap-1 text-xs" onClick={() => setPicked(new Set(filtered.map((r) => r.id)))}>Select all</Button>
              <Button size="sm" variant="ghost" className="h-7 text-xs" onClick={() => setPicked(new Set())}>Deselect</Button>
            </div>
          </CardHeader>
          <CardContent className="space-y-2">
            <div className="relative">
              <Search className="size-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
              <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search students…" className="h-8 pl-8 text-xs" />
            </div>
            <div className="max-h-[420px] overflow-y-auto nice-scroll rounded-lg border divide-y">
              {filtered.map((r) => {
                const on = picked.has(r.id);
                return (
                  <button key={r.id} onClick={() => setPicked((s) => { const n = new Set(s); on ? n.delete(r.id) : n.add(r.id); return n; })}
                    className={cn("flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm transition-colors", on ? "bg-primary/10" : "hover:bg-secondary/50")}>
                    {on ? <CheckSquare className="size-4 text-primary shrink-0" /> : <Square className="size-4 text-muted-foreground shrink-0" />}
                    <span className="min-w-0">
                      <span className="block font-medium truncate">{r.name}</span>
                      <span className="block text-[11px] text-muted-foreground flex items-center gap-1.5">
                        {r.classroom} · {r.index}
                        {r.residence === "BOARDING" && <Badge variant="outline" className="text-[9px] px-1 py-0 h-3.5 border-amber-500/40 text-amber-700 dark:text-amber-300">Boarder</Badge>}
                      </span>
                    </span>
                  </button>
                );
              })}
              {!filtered.length && <div className="px-3 py-8 text-center text-sm text-muted-foreground">{scope === "CLASS" ? "Pick a class above" : "No students match"}</div>}
            </div>
            <div className="flex items-center justify-between text-xs text-muted-foreground">
              <span>{picked.size} selected</span>
              <span>{candidates.length} in scope</span>
            </div>
          </CardContent>
        </Card>

        {/* invoice items */}
        <Card className="lg:col-span-4">
          <CardHeader className="pb-2 flex-row items-center justify-between space-y-0">
            <CardTitle className="text-base flex items-center gap-2"><ClipboardPen className="size-4 text-primary" /> Invoice items</CardTitle>
            <span className="text-sm font-bold tabular-nums">{fmtCedi(total)}<span className="text-xs font-normal text-muted-foreground"> / learner</span></span>
          </CardHeader>
          <CardContent className="space-y-3">
            {rows.map((r, i) => {
              const badTitle = !r.title.trim();
              const amt = parseFloat(r.amount);
              const badAmount = !r.amount.trim() || !Number.isFinite(amt) || amt <= 0;
              const dupTitle = rows.filter((x) => x.title.trim().toUpperCase() === r.title.trim().toUpperCase() && x.key !== r.key).length > 0;
              const anyBad = badTitle || badAmount || dupTitle;
              return (
              <div key={r.key} className={cn("rounded-lg border p-3 space-y-2", anyBad && tried && "border-destructive/50")}>
                <div className="grid gap-2 sm:grid-cols-[1.5fr_1.3fr_120px_auto]">
                  <Select value={r.title} onValueChange={(v) => {
                    const b = billItems.find((x) => x.title === v);
                    setRow(r.key, { title: v, amount: b ? (b.amountCents / 100).toFixed(2) : r.amount, description: b?.description ?? r.description });
                  }}>
                    <SelectTrigger className="h-9"><SelectValue placeholder="Pick a bill item (or type your own below)" /></SelectTrigger>
                    <SelectContent>
                      {billItems.map((b) => <SelectItem key={b.id} value={b.title}>{b.title} — {fmtCedi(b.amountCents)} · {b.category}</SelectItem>)}
                    </SelectContent>
                  </Select>
                  <div className="space-y-0.5">
                    <Input value={r.title} onChange={(e) => setRow(r.key, { title: e.target.value })} placeholder="OR TYPE AN ITEM TITLE" className={cn("h-9 text-xs uppercase", tried && badTitle && "border-destructive focus-visible:ring-destructive")} />
                    {tried && badTitle && <p className="text-[10px] text-destructive">Title is required</p>}
                    {tried && !badTitle && dupTitle && <p className="text-[10px] text-destructive">Duplicated on another row</p>}
                  </div>
                  <div className="space-y-0.5">
                    <Input type="number" min="0" step="0.01" value={r.amount} onChange={(e) => setRow(r.key, { amount: e.target.value })} placeholder="Amount" className={cn("h-9 text-right text-xs", tried && badAmount && "border-destructive focus-visible:ring-destructive")} />
                    {tried && badAmount && <p className="text-[10px] text-destructive">Amount must be above zero</p>}
                  </div>
                  {rows.length > 1 && (
                    <Button size="sm" variant="ghost" className="size-9 p-0 text-destructive hover:text-destructive shrink-0 self-start" onClick={() => setRows((rs) => rs.filter((x) => x.key !== r.key))} title="Remove row">
                      <Trash2 className="size-4" />
                    </Button>
                  )}
                </div>
                <Input value={r.description} onChange={(e) => setRow(r.key, { description: e.target.value })} placeholder="Description (optional — shown on the invoice)" className="h-8 text-xs" />
              </div>
              );
            })}
            <Button variant="outline" className="w-full gap-1.5 border-dashed" onClick={() => setRows((rs) => [...rs, { key: `r${Date.now()}`, title: "", amount: "", description: "" }])}>
              <Plus className="size-4" /> Add another item
            </Button>
            <div className="rounded-lg bg-secondary/60 p-3 text-xs text-muted-foreground leading-relaxed">
              Approved discounts and scholarships apply to each learner individually at creation, and prepaid credit balances are auto-applied to the fresh bill (CI3 parity).
            </div>
            <Button className="w-full gap-1.5" size="lg" disabled={!picked.size || !rows.some(rowValid) || busy} onClick={() => { if (!rows.every(rowValid)) { setTried(true); toast({ title: "Fix the highlighted fee items", description: "Every row needs a title and an amount above zero.", variant: "destructive" }); return; } setConfirm(true); }}>
              <ClipboardPen className="size-4" /> Create {picked.size || ""} invoice{picked.size !== 1 ? "s" : ""}
            </Button>
          </CardContent>
        </Card>
      </div>

      {/* confirmation modal — CI3 purple-gradient confirm */}
      <Dialog open={confirm} onOpenChange={setConfirm}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle className="bg-gradient-to-r from-indigo-500 to-purple-600 bg-clip-text text-transparent">Confirm invoice creation</DialogTitle>
            <DialogDescription>
              {terms.find((t) => t.id === chosenTerm)?.name ?? "Current term"} · {picked.size} learner{picked.size !== 1 ? "s" : ""}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-2">
            {rows.filter(rowValid).map((r) => (
              <div key={r.key} className="flex items-center justify-between rounded-lg border px-3 py-2 text-sm">
                <span className="font-medium">{r.title.toUpperCase()}</span>
                <span className="tabular-nums font-semibold">{fmtCedi(Math.round(parseFloat(r.amount) * 100))}</span>
              </div>
            ))}
            <div className="flex items-center justify-between rounded-lg bg-secondary px-3 py-2 text-sm font-bold">
              <span>Total per learner</span>
              <span className="tabular-nums">{fmtCedi(total)}</span>
            </div>
            <p className="text-xs text-muted-foreground">
              Grand total {fmtCedi(total * picked.size)} across {picked.size} learner{picked.size !== 1 ? "s" : ""}. Learners already billed this term get only the missing items appended.
            </p>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setConfirm(false)}>Back</Button>
            <Button onClick={create} disabled={busy} className="gap-1.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white hover:opacity-90">
              {busy ? <Loader2 className="size-4 animate-spin" /> : <CheckSquare className="size-4" />}
              Create {picked.size} invoice{picked.size !== 1 ? "s" : ""}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

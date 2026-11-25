"use client";
// ── Nyansapo Office (wave 56 — Microsoft Office-like portal) ──
// A file manager + three editors so teachers & staff can type documents,
// workbooks and presentations directly inside the system:
//   • Documents  — rich-text word processor (letters, notes, schemes)
//   • Spreadsheets — formulas, GH₵ formats, CSV export (marks sheets, budgets)
//   • Presentations — themed slides with speaker notes & present mode
// Files live in folders per school; autosaves run every ~2.5s and on Ctrl+S.
import { useCallback, useEffect, useMemo, useState } from "react";
import { useStore } from "@/lib/store";
import { apiGet, apiPost } from "@/lib/api";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { ModuleHeader, LoadingBlock, EmptyState, StatCard, FormField, FilterSearch } from "@/components/ui-helpers";
import { fmtAgo } from "@/lib/format";
import { useToast } from "@/hooks/use-toast";
import { setOfficeSchoolId, DocEditor, SheetEditor, SlideEditor, exportOfficeFile } from "@/components/modules/office-editors";
import { cn } from "@/lib/utils";
import {
  FileText, FileSpreadsheet, GalleryHorizontal, Plus, MoreHorizontal, Pencil, Copy, FolderInput,
  Star, Trash2, ArrowLeft, Folder, Loader2, CloudUpload, Download,
} from "lucide-react";

const OPS = "/ops/office";

type FileRow = {
  id: string; kind: "DOC" | "SHEET" | "SLIDE"; title: string; folder: string; starred: boolean;
  createdByName: string; updatedByName: string; createdAt: string; updatedAt: string;
};
type OfficeData = { ok: boolean; files: FileRow[]; folders: string[]; counts: { all: number; DOC: number; SHEET: number; SLIDE: number } };
type FullFile = FileRow & { contentJson: string };

const KIND_META: Record<string, { label: string; icon: typeof FileText; color: string; bg: string }> = {
  DOC: { label: "Document", icon: FileText, color: "text-[#2B579A]", bg: "bg-[#2B579A]" },
  SHEET: { label: "Workbook", icon: FileSpreadsheet, color: "text-[#217346]", bg: "bg-[#217346]" },
  SLIDE: { label: "Presentation", icon: GalleryHorizontal, color: "text-[#C43E1C]", bg: "bg-[#C43E1C]" },
};

export function Office() {
  const { currentSchoolId, navTarget } = useStore();
  const { toast } = useToast();
  const schoolId = currentSchoolId ?? "";
  const [data, setData] = useState<OfficeData | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [tab, setTab] = useState<string>("all");
  const [folder, setFolder] = useState<string>("all");
  const [search, setSearch] = useState("");
  const [openId, setOpenId] = useState<string | null>(null);
  const [openFile, setOpenFile] = useState<FullFile | null>(null);
  const [openLoading, setOpenLoading] = useState(false);
  const [dirty, setDirty] = useState(false);
  const [newOpen, setNewOpen] = useState(false);
  const [newKind, setNewKind] = useState<"DOC" | "SHEET" | "SLIDE">("DOC");
  const [newTitle, setNewTitle] = useState("");
  const [newFolder, setNewFolder] = useState("My Documents");
  const [renameRow, setRenameRow] = useState<FileRow | null>(null);
  const [renameVal, setRenameVal] = useState("");
  const [moveRow, setMoveRow] = useState<FileRow | null>(null);
  const [moveVal, setMoveVal] = useState("");
  const [deleteRow, setDeleteRow] = useState<FileRow | null>(null);

  useEffect(() => { setOfficeSchoolId(schoolId); }, [schoolId]);
  useEffect(() => {
    if (navTarget?.tab) setTab(navTarget.tab);
  }, [navTarget]);

  const load = useCallback(async () => {
    if (!schoolId) return;
    setLoading(true);
    try { setData(await apiGet<OfficeData>(`/api/school/${schoolId}${OPS}`)); }
    catch (e: any) { toast({ title: "Could not load Office files", description: e.message, variant: "destructive" }); }
    finally { setLoading(false); }
  }, [schoolId, toast]);

  useEffect(() => { load(); }, [load]);

  /** open a file: fetch full content, then render its editor */
  const openFileById = useCallback(async (id: string) => {
    setOpenId(id);
    setOpenFile(null);
    setOpenLoading(true);
    try {
      const d = await apiGet<{ ok: boolean; file: FullFile }>(`/api/school/${schoolId}${OPS}?id=${id}`);
      setOpenFile(d.file);
    } catch (e: any) {
      toast({ title: "Could not open file", description: e.message, variant: "destructive" });
      setOpenId(null);
    } finally { setOpenLoading(false); }
  }, [schoolId, toast]);

  /** download a file in its native format (.doc/.html/.csv) without opening it */
  const downloadFile = useCallback(async (f: FileRow) => {
    try {
      const d = await apiGet<{ ok: boolean; file: FullFile }>(`/api/school/${schoolId}${OPS}?id=${f.id}`);
      exportOfficeFile(d.file);
    } catch (e: any) {
      toast({ title: "Could not download file", description: e.message, variant: "destructive" });
    }
  }, [schoolId, toast]);

  const act = useCallback(async (body: Record<string, unknown>, successMsg: string, opts?: { silent?: boolean }) => {
    if (!schoolId) return;
    setBusy(true);
    try {
      await apiPost(`/api/school/${schoolId}${OPS}`, body);
      if (!opts?.silent) toast({ title: successMsg });
      await load();
    } catch (e: any) {
      toast({ title: "Action failed", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  }, [schoolId, load, toast]);

  const createFile = async () => {
    if (!newTitle.trim()) { toast({ title: "Give the file a name", variant: "destructive" }); return; }
    setBusy(true);
    try {
      const d = await apiPost<{ ok: boolean; file: { id: string } }>(`/api/school/${schoolId}${OPS}`, { action: "create", kind: newKind, title: newTitle, folder: newFolder });
      toast({ title: "File created" });
      setNewOpen(false);
      setNewTitle("");
      await load();
      await openFileById(d.file.id);
    } catch (e: any) {
      toast({ title: "Could not create file", description: e.message, variant: "destructive" });
    } finally { setBusy(false); }
  };

  /* ── editor view ── */
  if (openId) {
    const meta = openFile ? KIND_META[openFile.kind] : null;
    const Editor = openFile?.kind === "DOC" ? DocEditor : openFile?.kind === "SHEET" ? SheetEditor : openFile?.kind === "SLIDE" ? SlideEditor : null;
    return (
      <div className="fixed inset-0 z-40 flex flex-col bg-background" style={{ top: 0 }}>
        <div className="flex items-center gap-2 border-b px-3 py-2">
          <Button size="icon" variant="ghost" className="size-8" onClick={() => { setOpenId(null); setOpenFile(null); setDirty(false); }} aria-label="Back to files">
            <ArrowLeft className="size-4" />
          </Button>
          {meta && <span className={cn("inline-flex size-7 items-center justify-center rounded text-white", meta.bg)}><meta.icon className="size-4" /></span>}
          <input
            className="min-w-0 flex-1 truncate bg-transparent text-sm font-semibold outline-none focus:underline"
            value={openFile?.title ?? ""}
            disabled={!openFile}
            onChange={(e) => setOpenFile((f) => (f ? { ...f, title: e.target.value } : f))}
            onBlur={() => {
              const t = openFile?.title.trim();
              if (t && openFile && t !== openFile.title) { /* changed via typing */ }
              if (t && openFile) void act({ action: "rename", id: openFile.id, title: t }, "Renamed", { silent: true });
            }}
          />
          {openFile && <Badge variant="outline" className="hidden sm:inline-flex gap-1"><Folder className="size-3" /> {openFile.folder}</Badge>}
          <span className="flex items-center gap-1 text-xs text-muted-foreground">
            {dirty ? (<><CloudUpload className="size-3.5 animate-pulse" /> unsaved — autosaving…</>) : openFile ? "all changes saved" : null}
          </span>
        </div>
        <div className="min-h-0 flex-1">
          {openLoading || !openFile || !Editor ? (
            <div className="flex h-full items-center justify-center"><Loader2 className="size-8 animate-spin text-muted-foreground" /></div>
          ) : (
            <Editor key={openFile.id} file={openFile} onSaved={() => { setDirty(false); void load(); }} onDirtyChange={setDirty} />
          )}
        </div>
      </div>
    );
  }

  if (loading && !data) return <LoadingBlock label="Opening Nyansapo Office…" />;
  if (!data) return <EmptyState icon={<FileText />} title="Office unavailable" hint="Try reloading the module." />;

  const shown = data.files.filter((f) =>
    (tab === "all" || f.kind === tab) &&
    (folder === "all" || f.folder === folder) &&
    (!search || f.title.toLowerCase().includes(search.toLowerCase())),
  );
  const starred = shown.filter((f) => f.starred);

  return (
    <div className="space-y-4">
      <ModuleHeader
        title="Nyansapo Office"
        description="Type documents, workbooks and presentations right inside the system — no separate software needed. Everything saves into the school's cloud."
        actions={
          <Button onClick={() => { setNewKind(tab === "DOC" || tab === "SHEET" || tab === "SLIDE" ? (tab as any) : "DOC"); setNewOpen(true); }}>
            <Plus className="size-4 mr-1" /> New file
          </Button>
        }
      />

      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <StatCard icon={<FileText className="size-4" />} label="All files" value={data.counts.all} sub="across every folder" />
        <StatCard icon={<FileText className="size-4 text-[#2B579A]" />} label="Documents" value={data.counts.DOC} sub="letters & notes" />
        <StatCard icon={<FileSpreadsheet className="size-4 text-[#217346]" />} label="Workbooks" value={data.counts.SHEET} sub="marks & budgets" />
        <StatCard icon={<GalleryHorizontal className="size-4 text-[#C43E1C]" />} label="Presentations" value={data.counts.SLIDE} sub="teaching aids" />
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="flex flex-wrap h-auto gap-1">
          <TabsTrigger value="all">All files ({data.counts.all})</TabsTrigger>
          <TabsTrigger value="DOC">Documents ({data.counts.DOC})</TabsTrigger>
          <TabsTrigger value="SHEET">Spreadsheets ({data.counts.SHEET})</TabsTrigger>
          <TabsTrigger value="SLIDE">Presentations ({data.counts.SLIDE})</TabsTrigger>
        </TabsList>
      </Tabs>

      {/* folder chips + search */}
      <div className="flex flex-wrap items-center gap-1.5">
        <Button size="sm" variant={folder === "all" ? "default" : "outline"} className="h-8 gap-1 text-xs" onClick={() => setFolder("all")}>
          <Folder className="size-3.5" /> All folders
        </Button>
        {data.folders.map((f) => (
          <Button key={f} size="sm" variant={folder === f ? "default" : "outline"} className="h-8 gap-1 text-xs" onClick={() => setFolder(f)}>
            <Folder className="size-3.5" /> {f}
          </Button>
        ))}
        <div className="ml-auto w-56"><FilterSearch value={search} onChange={setSearch} placeholder="Search files…" /></div>
      </div>

      {shown.length === 0 ? (
        <EmptyState
          icon={<FileText />}
          title={search || folder !== "all" ? "No files match" : "No files yet"}
          hint={search || folder !== "all" ? "Adjust the filters above." : "Create your first document, workbook or presentation with the New file button."}
        />
      ) : (
        <div className="space-y-3">
          {starred.length > 0 && folder === "all" && !search && (
            <div>
              <div className="mb-1.5 text-xs font-medium text-muted-foreground">Starred</div>
              <FileGrid files={starred} onOpen={openFileById} onAct={act} onDownload={downloadFile} setRenameRow={setRenameRow} setRenameVal={setRenameVal} setMoveRow={setMoveRow} setMoveVal={setMoveVal} setDeleteRow={setDeleteRow} busy={busy} />
            </div>
          )}
          <div>
            {(starred.length > 0 && folder === "all" && !search) && <div className="mb-1.5 text-xs font-medium text-muted-foreground">Recent files</div>}
            <FileGrid
              files={starred.length > 0 && folder === "all" && !search ? shown.filter((f) => !f.starred) : shown}
              onOpen={openFileById} onAct={act} onDownload={downloadFile} setRenameRow={setRenameRow} setRenameVal={setRenameVal} setMoveRow={setMoveRow} setMoveVal={setMoveVal} setDeleteRow={setDeleteRow} busy={busy}
            />
          </div>
        </div>
      )}

      {/* ── new file dialog ── */}
      <Dialog open={newOpen} onOpenChange={setNewOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>New file</DialogTitle>
            <DialogDescription>Choose the type of file and where to keep it.</DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-1">
            <div className="grid grid-cols-3 gap-2">
              {(Object.keys(KIND_META) as ("DOC" | "SHEET" | "SLIDE")[]).map((k) => {
                const m = KIND_META[k];
                return (
                  <button key={k} type="button" onClick={() => setNewKind(k)}
                    className={cn("flex flex-col items-center gap-1.5 rounded-lg border p-3 text-xs transition-all hover:border-primary/50",
                      newKind === k && "border-primary bg-primary/5 shadow-sm")}>
                    <span className={cn("inline-flex size-9 items-center justify-center rounded text-white", m.bg)}><m.icon className="size-4.5" /></span>
                    {m.label}
                  </button>
                );
              })}
            </div>
            <FormField label="File name" required>
              <Input value={newTitle} onChange={(e) => setNewTitle(e.target.value)} placeholder={newKind === "DOC" ? "Term 2 parents letter" : newKind === "SHEET" ? "B8 maths marks" : "Photosynthesis lesson"} maxLength={120}
                onKeyDown={(e) => e.key === "Enter" && createFile()} />
            </FormField>
            <FormField label="Folder">
              <div className="flex gap-2">
                <Select value={data.folders.includes(newFolder) ? newFolder : "custom"} onValueChange={(v) => v !== "custom" && setNewFolder(v)}>
                  <SelectTrigger className="flex-1"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    {data.folders.map((f) => <SelectItem key={f} value={f}>{f}</SelectItem>)}
                    <SelectItem value="custom">New folder…</SelectItem>
                  </SelectContent>
                </Select>
                {!data.folders.includes(newFolder) && (
                  <Input className="flex-1" value={newFolder} onChange={(e) => setNewFolder(e.target.value)} placeholder="Folder name" maxLength={60} />
                )}
              </div>
            </FormField>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setNewOpen(false)}>Cancel</Button>
            <Button onClick={createFile} disabled={busy}>Create &amp; open</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── rename dialog ── */}
      <Dialog open={!!renameRow} onOpenChange={(v) => !v && setRenameRow(null)}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader><DialogTitle>Rename file</DialogTitle></DialogHeader>
          <FormField label="File name" required>
            <Input value={renameVal} onChange={(e) => setRenameVal(e.target.value)} maxLength={120} />
          </FormField>
          <DialogFooter>
            <Button variant="outline" onClick={() => setRenameRow(null)}>Cancel</Button>
            <Button onClick={() => { if (renameRow) act({ action: "rename", id: renameRow.id, title: renameVal }, "File renamed"); setRenameRow(null); }} disabled={busy}>Rename</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── move dialog ── */}
      <Dialog open={!!moveRow} onOpenChange={(v) => !v && setMoveRow(null)}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader><DialogTitle>Move to folder</DialogTitle><DialogDescription>Move “{moveRow?.title}” into a folder.</DialogDescription></DialogHeader>
          <FormField label="Folder" required>
            <Input value={moveVal} onChange={(e) => setMoveVal(e.target.value)} placeholder="e.g. Shared, Reports, Lesson notes" maxLength={60} />
          </FormField>
          <DialogFooter>
            <Button variant="outline" onClick={() => setMoveRow(null)}>Cancel</Button>
            <Button onClick={() => { if (moveRow && moveVal.trim()) act({ action: "move", id: moveRow.id, folder: moveVal }, "File moved"); setMoveRow(null); }} disabled={busy}>Move</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── delete confirm ── */}
      <AlertDialog open={!!deleteRow} onOpenChange={(v) => !v && setDeleteRow(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete this file?</AlertDialogTitle>
            <AlertDialogDescription>“{deleteRow?.title}” and all its contents will be permanently removed.</AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Keep file</AlertDialogCancel>
            <AlertDialogAction className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
              onClick={() => { if (deleteRow) act({ action: "delete", id: deleteRow.id }, "File deleted"); setDeleteRow(null); }}>
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}

/* file card grid with per-file actions */
function FileGrid({ files, onOpen, onAct, onDownload, setRenameRow, setRenameVal, setMoveRow, setMoveVal, setDeleteRow, busy }: {
  files: FileRow[];
  onOpen: (id: string) => void;
  onAct: (body: Record<string, unknown>, msg: string) => void;
  onDownload: (f: FileRow) => void;
  setRenameRow: (f: FileRow) => void; setRenameVal: (v: string) => void;
  setMoveRow: (f: FileRow) => void; setMoveVal: (v: string) => void;
  setDeleteRow: (f: FileRow) => void; busy: boolean;
}) {
  return (
    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      {files.map((f) => {
        const m = KIND_META[f.kind];
        const dlHint = f.kind === "DOC" ? "Word document (.doc)" : f.kind === "SHEET" ? "CSV (.csv)" : "Deck (.html)";
        return (
          <Card key={f.id} className="group cursor-pointer transition-all hover:shadow-md hover:border-primary/40" onClick={() => onOpen(f.id)}>
            <CardContent className="p-4">
              <div className="flex items-start gap-3">
                <span className={cn("inline-flex size-10 shrink-0 items-center justify-center rounded-md text-white", m.bg)}>
                  <m.icon className="size-5" />
                </span>
                <div className="min-w-0 flex-1">
                  <div className="flex items-center gap-1">
                    <span className="truncate text-sm font-semibold">{f.title}</span>
                    {f.starred && <Star className="size-3.5 shrink-0 fill-amber-400 text-amber-400" />}
                  </div>
                  <div className="text-xs text-muted-foreground">{m.label} · {f.folder}</div>
                  <div className="mt-1 text-[11px] text-muted-foreground">Edited {fmtAgo(f.updatedAt)} by {f.updatedByName}</div>
                </div>
                <div className="flex shrink-0 items-center gap-0.5">
                  <Button size="sm" variant="outline" className="h-7 gap-1 px-2 text-[11px] font-medium" title={`Download — ${dlHint}`}
                    aria-label={`Download ${f.title}`}
                    onClick={(e) => { e.stopPropagation(); onDownload(f); }}>
                    <Download className="size-3.5" /> Download
                  </Button>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild onClick={(e) => e.stopPropagation()}>
                      <Button size="icon" variant="ghost" className="size-7 opacity-0 transition-opacity group-hover:opacity-100" aria-label={`Actions for ${f.title}`}>
                        <MoreHorizontal className="size-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" onClick={(e) => e.stopPropagation()}>
                      <DropdownMenuItem onClick={() => onOpen(f.id)}><Pencil className="size-3.5 mr-1.5" /> Open</DropdownMenuItem>
                      <DropdownMenuItem onClick={() => onDownload(f)}><Download className="size-3.5 mr-1.5" /> Download ({dlHint})</DropdownMenuItem>
                      <DropdownMenuItem onClick={() => onAct({ action: "star", id: f.id, starred: !f.starred }, f.starred ? "Star removed" : "Starred")} disabled={busy}>
                        <Star className={cn("size-3.5 mr-1.5", f.starred && "fill-amber-400 text-amber-400")} /> {f.starred ? "Remove star" : "Star"}
                      </DropdownMenuItem>
                      <DropdownMenuItem onClick={() => { setRenameRow(f); setRenameVal(f.title); }}><Pencil className="size-3.5 mr-1.5" /> Rename</DropdownMenuItem>
                      <DropdownMenuItem onClick={() => onAct({ action: "duplicate", id: f.id }, "Copy created")} disabled={busy}><Copy className="size-3.5 mr-1.5" /> Duplicate</DropdownMenuItem>
                      <DropdownMenuItem onClick={() => { setMoveRow(f); setMoveVal(f.folder); }}><FolderInput className="size-3.5 mr-1.5" /> Move to folder</DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem className="text-destructive focus:text-destructive" onClick={() => setDeleteRow(f)}><Trash2 className="size-3.5 mr-1.5" /> Delete</DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
              </div>
            </CardContent>
          </Card>
        );
      })}
    </div>
  );
}
